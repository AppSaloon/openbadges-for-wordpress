<?php

namespace appsaloon\obwp\external_apis\openbadgefactory;

class Open_Badge_Factory_Api {
	// WP AJAX actions
	const REFRESH_OBF_API_CREDENTIALS_ACTION = 'refresh_obf_api_credentials';
	const TEST_OBF_API_CONNECTION_ACTION = 'test_obf_api_connection';

    // OpenBadgeFactory URLS
	const OBF_PUBLIC_CERTIFICATE_URL = 'https://openbadgefactory.com/v1/client/OBF.rsa.pub';
    const OBF_BASE_URL_FOR_SIGNING_CLIENT_CERTIFICATE_REQUEST = 'https://openbadgefactory.com/v1/client/';
    const OBF_ROUTE_SUFFIX_FOR_CSR_REQUEST_SIGNING = '/sign_request';

    const OBF_TEST_CONNECTION_URL = 'https://openbadgefactory.com/v1/ping/';

    protected $credentials;

    public function __construct( Open_Badge_Factory_Credentials $credentials_object ) {
		$this->credentials = $credentials_object;

		add_action( 'wp_ajax_' . static::REFRESH_OBF_API_CREDENTIALS_ACTION,
			array( $this, 'refresh_obf_api_credentials' ) );

		add_action( 'wp_ajax_' . static::TEST_OBF_API_CONNECTION_ACTION,
			array( $this, 'test_connection' )
		);
	}

	public function refresh_obf_api_credentials() {
		$this->send_error_for_invalid_nonce();

		if( current_user_can( 'manage_options' ) ) {
			if( $this->is_valid_refresh_obf_api_credentials_request() ) {
				$refresh_result =
					$this->generate_client_certificate_private_key_pair( $_POST['api_token'] );
				if( $refresh_result['response_code'] == 200  ) {
					if( $this->credentials->save_new_credentials(
						$refresh_result['data']['client_id'],
						$refresh_result['data']['private_key'],
						$refresh_result['data']['client_certificate']
					)
					) {
						wp_send_json_success(
							array(
								'message' => 'new OBF api credentials saved',
								'date' =>  Open_Badge_Factory_Credentials::get_formatted_credentials_saved_timestamp()
							),
							200
						);
					} else {
						wp_send_json_error(
							array( 'message' => 'OBF api credentials could not be saved' ),
							500
						);
					}
				} else {
					wp_send_json_error(
						array(
							'message' => $refresh_result['data'],
							'data' => $refresh_result
						),
						500
					);
				}
			} else {
				wp_send_json_error(
					array( 'message' => 'No api token provided' ),
					400
				);
			}
		} else {
			wp_send_json_error(
				array( 'message' => 'Action requires manage_options capability' ),
				403
			);
		}
	}

	private function send_error_for_invalid_nonce() {
		$is_nonce_valid = check_ajax_referer( 'configuration', 'security', false);

		if( ! $is_nonce_valid ) {
			wp_send_json_error( 'Invalid request' );
		}
	}

	private function is_valid_refresh_obf_api_credentials_request() {
		if( empty( $_POST ) ) {
			return false;
		} else {
			if( isset( $_POST['api_token'] ) && ! empty( $_POST['api_token'] ) && is_string( $_POST['api_token']  ) ) {
				return true;
			} else {
				return false;
			}
		}
	}

	public function get_credentials() {
    	return $this->credentials;
	}

	private function generate_client_certificate_private_key_pair( $api_token ) {
        if( current_user_can( 'manage_options' ) ) {
            $obf_public_certificate = wp_remote_get( static::OBF_PUBLIC_CERTIFICATE_URL );

            $obf_public_key = openssl_pkey_get_public( $obf_public_certificate['body'] );

            $decrypted_api_token = '';
            openssl_public_decrypt(
                base64_decode( $api_token ),
                $decrypted_api_token,
                $obf_public_key,
                OPENSSL_PKCS1_PADDING
            );

            $decrypted_api_token = json_decode( $decrypted_api_token );

            $private_key_generation_arguments = array(
                'digest_alg' => 'sha512',
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
                'private_key_bits' => 2048,
            );

            $private_key_result = openssl_pkey_new( $private_key_generation_arguments );

            openssl_pkey_export( $private_key_result, $new_private_key );

            $csr_res = openssl_csr_new( array( 'commonName' => $decrypted_api_token->subject ), $private_key_result );

            openssl_csr_export( $csr_res, $csr_output );

            $url = static::OBF_BASE_URL_FOR_SIGNING_CLIENT_CERTIFICATE_REQUEST . $decrypted_api_token->id
                . static::OBF_ROUTE_SUFFIX_FOR_CSR_REQUEST_SIGNING;

            $json_data_for_csr_signing = json_encode( array(
                'signature' => $api_token,
                'request' => $csr_output
            ) );

            $result = wp_remote_post( $url, array( 'body' => $json_data_for_csr_signing) );
            $response_code = wp_remote_retrieve_response_code( $result );
            $response_body = wp_remote_retrieve_body( $result );

            if( $response_code == 200 ) {
                $data = array(
                    'private_key' => $new_private_key,
                    'client_certificate' => $response_body,
                    'client_id' => $decrypted_api_token->id
                );
            } else {
                $decoded_json_response = json_decode( $response_body );
                $data = $decoded_json_response->error;
            }

            return array(
                'response_code' => $response_code,
                'data' => $data
            );

        } else {
            return 'Action requires manage_options capability';
        }
    }

    public function test_connection() {
		if( current_user_can( 'manage_options' ) ) {

			$ch = curl_init();

			$options = array(
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_SSL_VERIFYHOST => 2,
				CURLOPT_SSL_VERIFYPEER => true,
				CURLOPT_HEADER => false,

				CURLOPT_URL => static::OBF_TEST_CONNECTION_URL . $this->credentials->get_client_id(),
				CURLOPT_SSLCERT => $this->credentials->get_certificate_path(),
				CURLOPT_SSLKEY => $this->credentials->get_private_key_path(),
			);

			curl_setopt_array($ch, $options);

			$result = curl_exec($ch);
			$info = curl_getinfo($ch);

			curl_close($ch);

			

			if( $info['http_code'] == 200 && $result == $this->credentials->get_client_id() ) {
				wp_send_json_success( array() );
			} else {
				wp_send_json_error( array() );
			}
		} else {
			wp_send_json_error(
				array( 'message' => 'Action requires manage_options capability' ),
				403
			);
		}
	}
}