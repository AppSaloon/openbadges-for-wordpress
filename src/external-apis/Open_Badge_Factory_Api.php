<?php

namespace appsaloon\obwp\external_apis\openbadgefactory;

class Open_Badge_Factory_Api {
	// WP AJAX actions
	const REFRESH_OBF_API_CREDENTIALS_ACTION = 'refresh_obf_api_credentials';
	const TEST_OBF_API_CONNECTION_ACTION = 'test_obf_api_connection';
	const OBF_GET_ALL_BADGES = 'obf_get_all_badges';
	const OBF_GET_BADGE_BY_ID = 'obf_get_badge_by_id';
	const OBF_ISSUE_BADGE = 'obf_issue_badge';

    // OpenBadgeFactory URLS
	const OBF_PUBLIC_CERTIFICATE_URL = 'https://openbadgefactory.com/v1/client/OBF.rsa.pub';
    const OBF_BASE_URL_FOR_SIGNING_CLIENT_CERTIFICATE_REQUEST = 'https://openbadgefactory.com/v1/client/';
    const OBF_ROUTE_SUFFIX_FOR_CSR_REQUEST_SIGNING = '/sign_request';

    const OBF_TEST_CONNECTION_URL = 'https://openbadgefactory.com/v1/ping/';
    const OBF_BADGE_OPERATION_URL = 'https://openbadgefactory.com/v1/badge/';

    protected $credentials;

    public function __construct( Open_Badge_Factory_Credentials $credentials_object ) {
		$this->credentials = $credentials_object;
		$this->add_actions_for_ajax_calls();
	}

	private function add_actions_for_ajax_calls() {
		add_action( 'wp_ajax_' . static::REFRESH_OBF_API_CREDENTIALS_ACTION,
			array( $this, 'refresh_obf_api_credentials' ) );

		add_action( 'wp_ajax_' . static::TEST_OBF_API_CONNECTION_ACTION,
			array( $this, 'test_connection' )
		);


		add_action( 'wp_ajax_nopriv_' . static::OBF_GET_ALL_BADGES, array( $this, 'get_all_badges' ) );
		add_action( 'wp_ajax_' . static::OBF_GET_ALL_BADGES, array( $this, 'get_all_badges' ) );

		add_action( 'wp_ajax_nopriv_' . static::OBF_GET_BADGE_BY_ID, array( $this, 'get_badge_by_id' ) );
		add_action( 'wp_ajax_' . static::OBF_GET_BADGE_BY_ID, array( $this, 'get_badge_by_id' ) );

		add_action( 'wp_ajax_nopriv_' . static::OBF_ISSUE_BADGE, array( $this, 'ajax_issue_badge' ) );
		add_action( 'wp_ajax_' . static::OBF_ISSUE_BADGE, array( $this, 'ajax_issue_badge' ) );
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
			$result =
				$this->make_api_request( static::OBF_TEST_CONNECTION_URL . $this->credentials->get_client_id() );

			if( $result['http_code'] == 200 && $result['data'] == $this->credentials->get_client_id() ) {
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

	public function get_all_badges() {
		$result = $this->make_api_request( static::OBF_BADGE_OPERATION_URL . $this->credentials->get_client_id() );
		$this->send_json_response( $result );
	}

	public function get_badge_by_id() {
    	if( ! isset( $_POST['badge_id'] ) || strlen( $_POST['badge_id'] ) == 0 ) {
    		wp_send_json_error( 'no badge_id given', 400 );
		}

    	$result = $this->make_api_request(
    		static::OBF_BADGE_OPERATION_URL . $this->credentials->get_client_id() .
			DIRECTORY_SEPARATOR . $_POST['badge_id']
		);

    	$this->send_json_response( $result, false );
	}

	public function ajax_issue_badge() {
		if( ! isset( $_POST['badge_id'] ) || strlen( $_POST['badge_id'] ) == 0 ) {
			wp_send_json_error( 'no badge_id given', 400 );
		}

		$badge_data = $this->get_badge_data( $_POST['badge_id'] );
		$obf_request_body = new Issue_Open_Badge_Request_Body(
								$this->credentials->get_client_id(),
								$badge_data,
								$_POST
							);

		if( $obf_request_body->is_valid_incoming_request_body() ) {
			$result = $this->make_api_request( static::OBF_BADGE_OPERATION_URL . $this->credentials->get_client_id() .
				DIRECTORY_SEPARATOR . $_POST['badge_id'], $obf_request_body->get_request_body() );
			if( $result['http_code'] == 201 ) {
				wp_send_json_success( 'badge was issued', 200);
			} else {
				wp_send_json_error( 'badge was not issued' );
			}
		} else {
			wp_send_json_error( 'invalid request', 400 );
		}
	}

	private function get_badge_data( $badge_id ) {
		$result = $this->make_api_request(
			static::OBF_BADGE_OPERATION_URL . $this->credentials->get_client_id() .
			DIRECTORY_SEPARATOR . $badge_id
		);

		if( $result['http_code'] == 200 ) {
			return json_decode( $result['data'], true );
		} else {
			return false;
		}
	}

	private function send_json_response( $result, $send_as_array = true ) {
		if( $result['http_code'] != 200 ) {
			if( $result['http_code'] == 201 ) {
				wp_send_json_success( $result['data'] );
			} else {
				wp_send_json_error( $result['data'], $result['http_code'] );
			}
		} else {
			if( $send_as_array ) {
				wp_send_json_success( $this->convert_ldjson_to_array( $result['data'] ) );
			} else {
				wp_send_json_success( json_decode( $result['data'] ) ) ;
			}
		}
	}

	private function convert_ldjson_to_array($ldjson ) {
		$lines = explode( PHP_EOL, $ldjson );
		foreach( $lines as $key => $value ) {
			if( strlen( $lines[$key] ) != 0 ) {
				$lines[$key] = json_decode( $value, true );
			} else {
				unset( $lines[$key] );
			}
		}

		return $lines;
	}

	private function make_api_request( $url, $body = array() ) {
		$ch = curl_init();

		$options = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYHOST => 2,
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_HEADER => false,

			CURLOPT_URL => $url,
			CURLOPT_SSLCERT => $this->credentials->get_certificate_path(),
			CURLOPT_SSLKEY => $this->credentials->get_private_key_path(),
		);

		if( sizeof( $body ) > 0 ) {
			$options[CURLOPT_POSTFIELDS] = json_encode( $body );
		}

		curl_setopt_array($ch, $options);

		$result = curl_exec($ch);
		$info = curl_getinfo($ch);

		curl_close($ch);

		return array( 'data' => $result, 'http_code' => $info['http_code'] );
	}
}