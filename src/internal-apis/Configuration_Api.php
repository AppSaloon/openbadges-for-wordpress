<?php

namespace appsaloon\obwp\internal_apis;

use appsaloon\obwp\external_apis\openbadgefactory\Open_Badge_Factory_Api;

class Configuration_Api {
    const REFRESH_OBF_API_CREDENTIALS_ACTION = 'refresh_obf_api_credentials';
    const OFB_CREDENTIALS_FOLDER_NAME = 'obf-credentials';
    const CREDENTIALS_FOLDER_FILE_PERMISSIONS = 0755;
    const PRIVATE_KEY_FILE_NAME = 'private.key';
    const CLIENT_CERTIFICATE_FILE_NAME = 'certificate.pem';

    protected $plugin_path;
    protected $obf_credentials_path;

    public function __construct( $plugin_path ) {
    	$this->plugin_path = $plugin_path;
    	$this->obf_credentials_path = $plugin_path . DIRECTORY_SEPARATOR . static::OFB_CREDENTIALS_FOLDER_NAME;
        add_action(
        	'wp_ajax_' . static::REFRESH_OBF_API_CREDENTIALS_ACTION,
			array( $this, 'refresh_obf_api_credentials' )
		);
    }

    public function refresh_obf_api_credentials() {
        $is_nonce_valid = check_ajax_referer( 'configuration', 'security', false);

        if( ! $is_nonce_valid ) {
            wp_send_json_error( 'Invalid request' );
        }

        if( current_user_can( 'manage_options' ) ) {
            if( $this->is_valid_refresh_obf_api_credentials_request() ) {
                $refresh_result =
                    Open_Badge_Factory_Api::generate_client_certificate_private_key_pair( $_POST['api_token'] );
                if( $refresh_result['response_code'] == 200  ) {
                    $api_credentials_were_updated =
                        $this->save_obf_api_credentials(
                            $refresh_result['data']['private_key'],
                            $refresh_result['data']['client_certificate'],
                            $refresh_result['data']['client_id']
                        );
                    if( $api_credentials_were_updated ) {
                        wp_send_json_success(
                            array(
                                'message' => 'new OBF api credentials saved',
                                'date' =>  static::get_formatted_obf_api_credentials_date()
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

    private function save_obf_api_credentials( $private_key, $client_certificate, $client_id ) {
    	if( $this->is_credentials_directory_present() ) {

			$private_key_was_updated = file_put_contents(
				$this->obf_credentials_path  . DIRECTORY_SEPARATOR . static::PRIVATE_KEY_FILE_NAME,
				$private_key
			);

			$client_certificate_was_updated = file_put_contents(
				$this->obf_credentials_path  . DIRECTORY_SEPARATOR . static::CLIENT_CERTIFICATE_FILE_NAME,
				$client_certificate
			);

			update_option( 'obwp_obf_client_id', $client_id );

			if( $private_key_was_updated && $client_certificate_was_updated ) {
				update_option( 'obwp_obf_credentials_created_at', current_time( 'timestamp', 1 ) );
				return true;
			} else {
    			return false;
			}
        } else {
            return false;
        }
    }

	private function is_credentials_directory_present() {
		if( ! file_exists( $this->obf_credentials_path ) ) {
			return mkdir( $this->obf_credentials_path, 0755 );
		} else {
			return true;
		}
	}

    static function get_formatted_obf_api_credentials_date() {
        $time = get_option( 'obwp_obf_credentials_created_at', 'not found' );

        if( is_numeric( $time ) ) {
            return date_i18n( 'Y-m-d H:i:s', $time );
        } else {
            return 'No date found';
        }
    }
}
