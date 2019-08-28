<?php

namespace appsaloon\obwp\external_apis\openbadgefactory;

class Open_Badge_Factory_Api {
    const OBF_PUBLIC_CERTIFICATE_URL = 'https://openbadgefactory.com/v1/client/OBF.rsa.pub';
    const OBF_BASE_URL_FOR_SIGNING_CLIENT_CERTIFICATE_REQUEST = 'https://openbadgefactory.com/v1/client/';
    const OBF_ROUTE_SUFFIX_FOR_CSR_REQUEST_SIGNING = '/sign_request';

    const OBF_TEST_CONNECTION_URL = 'https://openbadgefactory.com/v1/ping/';

    protected $credentials;

    public function __construct( Open_Badge_Factory_Credentials $credentials_object ) {
		$this->credentials = $credentials_object;
	}

	public function get_credentials() {
    	return $this->credentials;
	}

	static function generate_client_certificate_private_key_pair( $api_token ) {
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

            $csr_res = openssl_csr_new( array( 'commonName' => $decrypted_api_token->subject ), $private_key_res );

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

			curl_setopt_array( $ch, $options );

			$result = curl_exec( $ch );
			$info = curl_getinfo( $ch );

			curl_close( $ch );

			return array(
				'response_code' => $info['http_code'],
				'data' => $result
			);
		} else {
			return 'Action requires manage_options capability';
		}
	}
}