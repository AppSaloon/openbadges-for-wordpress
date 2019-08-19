<?php

namespace appsaloon\obwp\internal_apis;

use appsaloon\obwp\external_apis\openbadgefactory\Open_Badge_Factory_Api;

class Configuration_Api {
    const REFRESH_OBF_API_CREDENTIALS = 'refresh_obf_api_credentials';

    public function __construct() {
        add_action( 'wp_ajax_' . static::REFRESH_OBF_API_CREDENTIALS, array( $this, 'refresh_obf_api_credentials' ) );
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
                if( is_array( $refresh_result ) ) {
                    $api_credentials_were_updated = $this->save_obf_api_credentials(
                        $refresh_result['private_key'], $refresh_result['client_certificate']
                    );
                    if( $api_credentials_were_updated ) {
                        wp_send_json_success( 'new OBF api credentials saved to database' );
                    } else {
                        wp_send_json_error( 'OBF api credentials could not be saved' );
                    }
                } else {
                    wp_send_json_error( $refresh_result );
                }
            } else {
                wp_send_json_error( 'No api token provided' );
            }
        } else {
            wp_send_json_error( 'Action requires manage_options capability', 403 );
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

    private function save_obf_api_credentials( $private_key, $client_certificate ) {
        $private_key_was_updated = update_option( 'obwp_obf_private_key', $private_key );
        $client_certificate_was_updated = update_option( 'obwp_obf_client_certificate', $client_certificate );

        if( $private_key_was_updated && $client_certificate_was_updated ) {
            return true;
        } else {
            return false;
        }
    }
}
