<?php
/**
 * Contains the Open_Badge_Factory_Api class.
 */
namespace appsaloon\obwp\external_apis\openbadgefactory;

/**
 * Class Open_Badge_Factory_Api
 *
 * Used to communicate with the OpenBadgeFactory api.
 * Provides a layer between WP AJAX calls and the OpenBadgeFactory api.
 *
 * @package appsaloon\obwp\external_apis\openbadgefactory
 */
class Open_Badge_Factory_Api {
	/**
	 * Value for action parameter for the ajax call to regenerate/refresh the credential files
	 *
	 */
	const REFRESH_OBF_API_CREDENTIALS_ACTION = 'refresh_obf_api_credentials';

	/**
	 * Value for action parameter for the ajax call to test the connection to the OpenBadgeFactory api
	 *
	 */
	const TEST_OBF_API_CONNECTION_ACTION = 'test_obf_api_connection';

	/**
	 * Value for action parameter for the ajax call to get all badges from the OpenBadgeFactory api
	 *
	 */
	const OBF_GET_ALL_BADGES = 'obf_get_all_badges';

	/**
	 * Value for action parameter for the ajax call to a badge by id from the OpenBadgeFactory api
	 *
	 */
	const OBF_GET_BADGE_BY_ID = 'obf_get_badge_by_id';

	/**
	 * Value for action parameter for the ajax call to issue a badge from the OpenBadgeFactory api
	 *
	 */
	const OBF_ISSUE_BADGE = 'obf_issue_badge';

	/**
	 * Url to the public certificate provided by OpenBadgeFactory, used to generate credential files
	 *
	 */
	const OBF_PUBLIC_CERTIFICATE_URL = 'https://openbadgefactory.com/v1/client/OBF.rsa.pub';

	/**
	 * Url to sign the request for a client certificate
	 *
	 */
	const OBF_BASE_URL_FOR_SIGNING_CLIENT_CERTIFICATE_REQUEST = 'https://openbadgefactory.com/v1/client/';

	/**
	 * Suffix to append to the OBF_BASE_URL_FOR_SIGNING_CLIENT_CERTIFICATE_REQUEST to sign
	 * the client certificate request.
	 *
	 */
	const OBF_ROUTE_SUFFIX_FOR_CSR_REQUEST_SIGNING = '/sign_request';

	/**
	 * Url to the test the connection to the OpenBadgeFactory api (testing the validity of the credentials files).
	 *
	 */
	const OBF_TEST_CONNECTION_URL = 'https://openbadgefactory.com/v1/ping/';

	/**
	 * Url to do badge operations with the OpenBadgeFactory api.
	 *
	 */
	const OBF_BADGE_OPERATION_URL = 'https://openbadgefactory.com/v1/badge/';

	/**
	 * Open_Badge_Factory_Credentials instance used to sign the requests.
	 *
	 * @var Open_Badge_Factory_Credentials
	 *
	 */
	protected $credentials;

	/**
	 * Url to the plugin main directory
	 * @var string
	 *
	 */
	protected $plugin_url;

	/**
	 * Instance of Issue_Open_Badge_Request_Body to validate and prepare a request body to issue a badge
	 * @var Issue_Open_Badge_Request_Body
	 */
	protected $issue_open_badge_request_body;

	/**
	 * Open_Badge_Factory_Api constructor.
	 * @param Open_Badge_Factory_Credentials $credentials_object
	 * @param string $plugin_url
	 *
	 * @since 1.0.5
	 */
	public function __construct( Open_Badge_Factory_Credentials $credentials_object, $plugin_url, Issue_Open_Badge_Request_Body $issue_open_badge_request_body ) {
    	$this->plugin_url = $plugin_url;
		$this->credentials = $credentials_object;
		$this->issue_open_badge_request_body = $issue_open_badge_request_body;

	}

	/**
	 * Registers all the WP hooks
	 *
	 * @since 1.0.6
	 */
	public function register_hooks() {
		$this->add_actions_for_ajax_calls();
		$this->add_shortcodes();
	}

	/**
	 * Adds all the action hooks to expose the OpenBadgeFactory api through WP ajax calls
	 *
	 * @since 1.0.5
	 */
	private function add_actions_for_ajax_calls() {
		add_action( 'wp_ajax_' . static::REFRESH_OBF_API_CREDENTIALS_ACTION,
			array( $this, 'ajax_refresh_obf_api_credentials' ) );

		add_action( 'wp_ajax_' . static::TEST_OBF_API_CONNECTION_ACTION,
			array( $this, 'ajax_test_connection' )
		);


		add_action( 'wp_ajax_nopriv_' . static::OBF_GET_ALL_BADGES, array( $this, 'ajax_get_all_badges' ) );
		add_action( 'wp_ajax_' . static::OBF_GET_ALL_BADGES, array( $this, 'ajax_get_all_badges' ) );

		add_action( 'wp_ajax_nopriv_' . static::OBF_GET_BADGE_BY_ID, array( $this, 'ajax_get_badge_by_id' ) );
		add_action( 'wp_ajax_' . static::OBF_GET_BADGE_BY_ID, array( $this, 'ajax_get_badge_by_id' ) );

		add_action( 'wp_ajax_nopriv_' . static::OBF_ISSUE_BADGE, array( $this, 'ajax_issue_badge' ) );
		add_action( 'wp_ajax_' . static::OBF_ISSUE_BADGE, array( $this, 'ajax_issue_badge' ) );
	}

	/**
	 * Adds all the shortcodes that make use of the OpenBadgeFactory api.
	 *
	 * @since 1.0.5
	 */
	private function add_shortcodes() {
		// TODO: set tag in a constant
    	add_shortcode( 'obf_display_claimable_badge', array( $this, 'display_claimable_badge' ) );
	}

	/**
	 * Loads the template to display a claimable badge if the current user can claim it.
	 * Returns either the template or an error message as a string or false.
	 *
	 * Parameter $atts should be an array with index 'id' and value is the id of the badge to display.
	 *
	 * @param array $atts
	 * @return false|string
	 *
	 * @since 1.0.5
	 */
	public function display_claimable_badge( $atts ) {
		// TODO only run logic to check if a user can claim a badge when the badge can be retrieved
    	$is_user_allowed_to_claim_badge = is_user_logged_in();

    	if( $is_user_allowed_to_claim_badge ) {
    		$user_email = wp_get_current_user()->data->user_email;
		} else {
    		$user_email = '';
		}

    	//TODO move enqueueing to separate private function
		wp_enqueue_style('openbadge-css', $this->plugin_url .'files/css/openbadges.css');
		wp_enqueue_script('openbadges-js', $this->plugin_url .'files/js/openbadges.js', 'jquery' , '' , true );
		wp_localize_script('openbadges-js', 'openbadges_ajax_object', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );

    	if( $badge = $this->get_badge_by_id( $atts['id'] ) ) {
			return $this->load_claimable_badge_template( $is_user_allowed_to_claim_badge, $user_email, $badge );
		} else {
    		return 'invalid badge_id in shortcode';
		}
	}

	/**
	 * Loads the template for displaying a claimable badge.
	 * Parameters are necessary to make them available to the template.
	 * Allows themes to override the template.
	 *
	 * Uses output buffering because it serves a template to a shortcode.
	 *
	 * @param boolean $is_user_allowed_to_claim_badge
	 * @param string $user_email
	 * @param array $badge
	 *
	 * @return false|string
	 *
	 * @since 1.0.5
	 */
	private function load_claimable_badge_template($is_user_allowed_to_claim_badge, $user_email, $badge) {
		ob_start();
		$template_from_theme = locate_template( 'openbadges-for-wordpress/open-badge-claim.php' );
		if( '' !== $template_from_theme  ){
			include ( $template_from_theme );
		} else {
			include __DIR__ . '/../../templates/open-badge-claim.php';
		}
		return ob_get_clean();
	}

	/**
	 * Handles the WP ajax request for action REFRESH_OBF_API_CREDENTIALS_ACTION.
	 * Upon success it saves new credential files and sends a json success response.
	 * On failure it returns the appropriate json error response.
	 *
	 * @since 1.0.5
	 */
	public function ajax_refresh_obf_api_credentials() {
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

	/**
	 * Checks the security nonce when requesting new credentials.
	 * Sends a json error message if the nonce is invalid, returns void when nonce is valid.
	 *
	 * @since 1.0.5
	 */
	private function send_error_for_invalid_nonce() {
		//TODO set value for action parameter to a class constant
		$is_nonce_valid = check_ajax_referer( 'configuration', 'security', false);

		if( ! $is_nonce_valid ) {
			wp_send_json_error( 'Invalid request' );
		}
	}

	/**
	 * Returns true if the request to refresh the credentials was valid, false if it was not.
	 *
	 * @return bool
	 *
	 * @since 1.0.5
	 */
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

	/**
	 * Returns the $credentials class property
	 *
	 * @return Open_Badge_Factory_Credentials
	 *
	 * @since 1.0.5
	 */
	public function get_credentials() {
    	return $this->credentials;
	}

	/**
	 * Generates a matching client certificate and private key pair based on the api token.
	 * Sends a private key and a certificate signing request (CSR) to the OpenBadgeFactory api to generate
	 * the client certificate.
	 *
	 * @param string $api_token
	 *
	 * @return array|string
	 *
	 * @since 1.0.5
	 */
	private function generate_client_certificate_private_key_pair( $api_token ) {
		//TODO extract private functions to increase readability
		// TODO maybe change return type string?
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

	/**
	 * Handles the WP Ajax request to check if the credential files are valid
	 * Sends a wp json success if credentials are valid, wp json error if not.
	 *
	 * @since 1.0.5
	 */
	public function ajax_test_connection() {
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

	/**
	 * Handles the WP Ajax request to retrieve all badges from OpenBadgeFactory api.
	 * Sends a json response.
	 *
	 * @since 1.0.5
	 */
	public function ajax_get_all_badges() {
		$result = $this->make_api_request( static::OBF_BADGE_OPERATION_URL . $this->credentials->get_client_id() );
		$this->send_json_response( $result );
	}

	/**
	 * Handles the WP Ajax request to retrieve a badge by id from OpenBadgeFactory api.
	 * Sends a json response containing the badge data or json error response.
	 *
	 * @since 1.0.5
	 */
	public function ajax_get_badge_by_id() {
    	if( ! isset( $_POST['badge_id'] ) || strlen( $_POST['badge_id'] ) == 0 ) {
    		wp_send_json_error( 'no badge_id given', 400 );
		}

    	$result = $this->make_api_request(
    		static::OBF_BADGE_OPERATION_URL . $this->credentials->get_client_id() .
			DIRECTORY_SEPARATOR . $_POST['badge_id']
		);

    	$this->send_json_response( $result, false );
	}

	/**
	 * Handles the WP Ajax request to issue a badge.
	 * Sends a json success response or error.
	 *
	 * @since 1.0.5
	 */
	public function ajax_issue_badge() {
    	// TODO: check security nonce!!
		if( ! isset( $_POST['badge_id'] ) || strlen( $_POST['badge_id'] ) == 0 ) {
			wp_send_json_error( 'no badge_id given', 400 );
		}

		$badge_data = $this->get_badge_by_id( $_POST['badge_id'] );
		$this->issue_open_badge_request_body->initialize(
								$this->credentials->get_client_id(),
								$badge_data,
								$_POST
							);

		if( $this->issue_open_badge_request_body->is_valid_incoming_request_body() ) {
			$result = $this->make_api_request( static::OBF_BADGE_OPERATION_URL . $this->credentials->get_client_id() .
				DIRECTORY_SEPARATOR . $_POST['badge_id'], $this->issue_open_badge_request_body->get_request_body() );
			if( $result['http_code'] == 201 ) {
				wp_send_json_success( 'This badge is successfully claimed. Please check your mail for confirmation', 200);
			} else {
				wp_send_json_error( 'badge was not issued' );
			}
		} else {
			wp_send_json_error( 'invalid request', 400 );
		}
	}

	/**
	 * Gets a badge by id.
	 * Returns badge data as array or false
	 *
	 * @param $badge_id
	 * @return array|bool
	 *
	 * TODO determine badge_id type
	 *
	 * @since 1.0.5
	 */
	private function get_badge_by_id($badge_id ) {
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

	/**
	 * Sends the appropriate json response with, converts data in parameter $result as needed for response.
	 *
	 * @param array $result
	 * @param bool $send_as_array
	 *
	 * @since 1.0.5
	 */
	private function send_json_response($result, $send_as_array = true ) {
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

	/**
	 * Converts a line delimited json string to an array and returns the array.
	 *
	 * @param string $ldjson
	 * @return array
	 *
	 * @since 1.0.5
	 */
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

	/**
	 * Makes an api request to $url with $body as post, performs get request when $body is empty (default).
	 * Returns an index array, index 'data' contains the result and index 'http_code' contains the http response code
	 * from the request.
	 *
	 * @param string $url
	 * @param array $body
	 *
	 * @return array
	 *
	 * @since 1.0.5
	 */
	private function make_api_request($url, $body = array() ) {
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