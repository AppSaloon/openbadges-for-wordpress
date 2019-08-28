<?php

namespace appsaloon\obwp\internal_apis;

use appsaloon\obwp\external_apis\openbadgefactory\Open_Badge_Factory_Api;
use appsaloon\obwp\external_apis\openbadgefactory\Open_Badge_Factory_Credentials;

class Configuration_Api {
    const REFRESH_OBF_API_CREDENTIALS_ACTION = 'refresh_obf_api_credentials';
    const TEST_OBF_API_CONNECTION_ACTION = 'test_obf_api_connection';

    const OFB_CREDENTIALS_FOLDER_NAME = 'obf-credentials';
    const CREDENTIALS_FOLDER_FILE_PERMISSIONS = 0755;
    const PRIVATE_KEY_FILE_NAME = 'private.key';
    const CLIENT_CERTIFICATE_FILE_NAME = 'certificate.pem';

	protected $credentials;
	protected $obf_api;

    public function __construct( Open_Badge_Factory_Api $obf_api_object ) {
    	$this->credentials = $obf_api_object->get_credentials();
    	$this->obf_api = $obf_api_object;
        add_action( 'wp_ajax_' . static::REFRESH_OBF_API_CREDENTIALS_ACTION,
			array( $this, 'refresh_obf_api_credentials' ) );
        add_action( 'wp_ajax_' . static::TEST_OBF_API_CONNECTION_ACTION,
			array( $this, 'test_obf_connection' )
		);
    }

    public function refresh_obf_api_credentials() {
        $this->send_error_for_invalid_nonce();

        if( current_user_can( 'manage_options' ) ) {
            if( $this->is_valid_refresh_obf_api_credentials_request() ) {
                $refresh_result =
                    Open_Badge_Factory_Api::generate_client_certificate_private_key_pair( $_POST['api_token'] );
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

    public function test_obf_connection() {
		if( current_user_can( 'manage_options' ) ) {
			$client_id = get_option( 'obwp_obf_client_id' );
			$result = $this->obf_api->test_connection();

			if( is_array( $result ) ) {
				if( $result['response_code'] == 200 && $result['data'] == $client_id ) {
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
		} else {
			wp_send_json_error(
				array( 'message' => 'Action requires manage_options capability' ),
				403
			);
		}
	}
}
