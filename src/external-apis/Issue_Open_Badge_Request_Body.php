<?php

/**
 * Contains class Issue_Open_Badge_Request_Body
 */
namespace appsaloon\obwp\external_apis\openbadgefactory;

/**
 * Class Issue_Open_Badge_Request_Body
 *
 * Used to create and validate a request body for the api call to issue a badge
 *
 * @package appsaloon\obwp\external_apis\openbadgefactory
 *
 * @since 1.0.5
 */
class Issue_Open_Badge_Request_Body {
	/**
	 * The client id for the account on OpenBadgeFactory.org
	 *
	 * @var string
	 */
	protected $client_id;

	/**
	 * Indexed array containing the data for the request body as it is passed to the class
	 *
	 * @var array
	 *
	 * @since 1.0.5
	 */
	protected $incoming_request_body;

	/**
	 * Indexed array containing the badge data
	 *
	 * @var array
	 *
	 * @since 1.0.5
	 */
	protected $badge_data;

	/**
	 * Indexed array containing the data for the request body as it should be send to the OBF api.
	 *
	 * @var array
	 *
	 * @since 1.0.5
	 */
	private $request_body;

	/**
	 * Issue_Open_Badge_Request_Body constructor.
	 *
	 * @param string $client_id
	 * @param array $badge_data
	 * @param array $data
	 *
	 * @since 1.0.5
	 */
	public function __construct($client_id, $badge_data, $data ) {
		$this->client_id = $client_id;
		$this->badge_data = $badge_data;
		$this->incoming_request_body = $data;

		if( $this->is_valid_incoming_request_body() ) {
			$this->set_request_body_for_api_use();
		}
	}

	/**
	 * Getter for the request_body ready for use with the OBF api
	 *
	 * @return array
	 *
	 * @since 1.0.5
	 */
	public function get_request_body() {
		// TODO: if incoming request body is not valid $this->>request_body is not set -> find better error handling!
		// because now the consumer needs to handle this
		return $this->request_body;
	}

	/**
	 * Adds index-value pairs to the request body that are constants but needed for a valid api request.
	 *
	 * @since 1.0.5
	 */
	private function set_request_body_for_api_use() {
		$this->request_body = array(
			'recipient' => $this->incoming_request_body['recipient'],
		);

		if( $this->has_valid_expiration_timestamp_in_incoming_request_body() ) {
			$this->request_body['expires'] = $this->incoming_request_body['expires'];
		}

		if( $this->has_valid_issued_on_timestamp_in_incoming_request_body() ) {
			$this->request_body['issued_on'] = $this->incoming_request_body['issued_on'];
		}

		$this->set_email_chunks_in_request_body();

		if( $this->has_valid_log_entry_in_incoming_request_body() ) {
			$this->request_body['log_entry'] = $this->incoming_request_body['log_entry'];
		}

		$this->request_body['api_consumer_id'] = $this->client_id;
	}

	/**
	 * Adds the email chunks from the incoming request body to the (outgoing) request body.
	 *
	 * @since 1.0.5
	 */
	private function set_email_chunks_in_request_body() {
		if( $this->has_email_subject_in_incoming_request_body() ) {
			$this->request_body['email_subject'] = $this->incoming_request_body['email_subject'];
		}

		if( $this->has_email_body_in_incoming_request_body() ) {
			$this->request_body['email_body'] = $this->incoming_request_body['email_body'];
		}

		if( $this->has_email_body_in_incoming_request_body() ) {
			$this->request_body['email_link_text'] = $this->incoming_request_body['email_link_text'];
		}

		if( $this->has_email_footer_in_incoming_request_body() ) {
			$this->request_body['email_footer'] = $this->incoming_request_body['email_footer'];
		}
	}

	/**
	 * Returns true if the incoming request body is valid, false if not.
	 *
	 * @return bool
	 *
	 * @since 1.0.5
	 */
	public function is_valid_incoming_request_body() {
		if( ! $this->has_valid_recipient_list_in_incoming_request_body() ) {
			return false;
		}

		if( ! $this->are_all_email_chunks_valid_for_api_request() ) {
			return false;
		}

		return true;
	}

	/**
	 * Returns true if all the email chunks are valid for the api request, false if not.
	 *
	 * @return bool
	 *
	 * @since 1.0.5
	 */
	private function are_all_email_chunks_valid_for_api_request() {
		if( ! $this->has_email_body_in_badge_data() && ! $this->has_email_body_in_incoming_request_body() ) {
			return false;
		}

		if( ! $this->has_email_footer_in_badge_data() && ! $this->has_email_footer_in_incoming_request_body() ) {
			return false;
		}

		if( ! $this->has_email_link_text_in_badge_data() && ! $this->has_email_link_text_in_incoming_request_body() ) {
			return false;
		}

		if( ! $this->has_email_subject_in_badge_data() && ! $this->has_email_subject_in_incoming_request_body() ) {
			return false;
		}

		return true;
	}

	// Checks for the badge data

	/**
	 * Returns true if there is an email subject in the badge data, false if not.
	 *
	 * @return bool
	 *
	 * @since 1.0.5
	 */
	private function has_email_subject_in_badge_data() {
		return $this->array_has_non_empty_string_at_index( $this->badge_data, 'email_subject' );
	}

	/**
	 * Returns true if there is an email body in the badge data, false if not.
	 *
	 * @return bool
	 *
	 * @since 1.0.5
	 */
	private function has_email_body_in_badge_data() {
		return $this->array_has_non_empty_string_at_index( $this->badge_data, 'email_body' );
	}

	/**
	 * Returns true if there is an email link text in the badge data, false if not.
	 *
	 * @return bool
	 *
	 * @since 1.0.5
	 */
	private function has_email_link_text_in_badge_data() {
		return $this->array_has_non_empty_string_at_index( $this->badge_data, 'email_link_text' );
	}

	/**
	 * Returns true if there is an email footer in the badge data, false if not.
	 *
	 * @return bool
	 *
	 * @since 1.0.5
	 */
	private function has_email_footer_in_badge_data() {
		return $this->array_has_non_empty_string_at_index( $this->badge_data, 'email_footer' );
	}

	// Checks for the incoming request body

	/**
	 * Returns true if there is a valid recipient list in the incoming request body, false if not.
	 *
	 * @return bool
	 *
	 * @since 1.0.5
	 */
	private function has_valid_recipient_list_in_incoming_request_body() {
		if (
			isset( $this->incoming_request_body['recipient'] )
			&&
			is_array( $this->incoming_request_body['recipient'] )
			&&
			sizeof( $this->incoming_request_body['recipient'] ) > 0
		) {
			return $this->has_only_email_adresses_in_recipient_list_of_incoming_request_body();

		} else {
			return false;
		}
	}

	/**
	 * Returns true if the recipient list in the incoming request body are all email addresses, false if not.
	 *
	 * @return bool
	 *
	 * @since 1.0.5
	 */
	private function has_only_email_adresses_in_recipient_list_of_incoming_request_body() {
		foreach ( $this->incoming_request_body['recipient'] as $recipient ) {
			if( ! is_email( $recipient ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Returns true if the expiration timestamp in the incoming request body is valid, false if not.
	 *
	 * @return bool
	 *
	 * @since 1.0.5
	 */
	private function has_valid_expiration_timestamp_in_incoming_request_body() {
		return $this->array_has_integer_at_index( $this->incoming_request_body, 'expires' );
	}

	/**
	 * Returns true if the issued on timestamp in the incoming request body is valid, false if not.
	 *
	 * @return bool
	 *
	 * @since 1.0.5
	 */
	private function has_valid_issued_on_timestamp_in_incoming_request_body() {
		return $this->array_has_integer_at_index( $this->incoming_request_body, 'issued_on' );
	}

	/** Returns true if the log entry in the incoming request body is valid, false if not.
	 *
	 * @return bool
	 *
	 * @since 1.0.5
	 */
	private function has_valid_log_entry_in_incoming_request_body() {
		return ( isset( $this->incoming_request_body['log_entry'] ) && is_array( $this->incoming_request_body['log_entry'] ) );
	}

	/**
	 * Returns true if the incoming request body has an email subject, false if not.
	 *
	 * @return bool
	 *
	 * @since 1.0.5
	 */
	private function has_email_subject_in_incoming_request_body() {
		return $this->array_has_non_empty_string_at_index( $this->incoming_request_body, 'email_subject' );
	}

	/**
	 * Returns true if the incoming request body has an email body, false if not.
	 *
	 * @return bool
	 *
	 * @since 1.0.5
	 */
	private function has_email_body_in_incoming_request_body() {
		return $this->array_has_non_empty_string_at_index( $this->incoming_request_body, 'email_body' );
	}

	/**
	 * Returns true if the incoming request body has an email link text, false if not.
	 *
	 * @return bool
	 *
	 * @since 1.0.5
	 */
	private function has_email_link_text_in_incoming_request_body() {
		return $this->array_has_non_empty_string_at_index( $this->incoming_request_body, 'email_link_text' );
	}

	/**
	 * Returns true if the incoming request body has an email footer, false if not.
	 *
	 * @return bool
	 *
	 * @since 1.0.5
	 */
	private function has_email_footer_in_incoming_request_body() {
		return $this->array_has_non_empty_string_at_index( $this->incoming_request_body, 'email_footer' );
	}

	/**
	 * Returns true if the array has a non-empty string at the given index, false if not.
	 *
	 * @param array $array
	 * @param string $index
	 *
	 * @return bool
	 *
	 * @since 1.0.5
	 */
	private function array_has_non_empty_string_at_index($array, $index ) {
		return ( isset( $array[$index] ) && strlen( $array[$index] ) > 0 );
	}

	/**
	 * Returns true if the array has an integer at the given index, false if not.
	 *
	 * @param array $array
	 * @param string $index
	 *
	 * @return bool
	 *
	 * @since 1.0.5
	 */
	private function array_has_integer_at_index($array, $index ) {
		return ( isset( $array[$index] ) && is_int( $array[$index] ) );

	}
}