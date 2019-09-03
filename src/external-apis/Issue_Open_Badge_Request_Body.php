<?php
namespace appsaloon\obwp\external_apis\openbadgefactory;

class Issue_Open_Badge_Request_Body {
	protected $client_id;
	protected $incoming_request_body;
	protected $badge_data;

	private $request_body;
	
	public function __construct( $client_id, $badge_data, $data ) {
		$this->client_id = $client_id;
		$this->badge_data = $badge_data;
		$this->incoming_request_body = $data;

		if( $this->is_valid_incoming_request_body() ) {
			$this->set_request_body_for_api_use();
		}
	}

	public function get_request_body() {
		return $this->request_body;
	}

	private function set_request_body_for_api_use() {
		$this->request_body = array(
			'recipient' => $this->incoming_request_body['recipient'],
			'api_consumer_id' => $this->client_id,
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
	}

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

	public function is_valid_incoming_request_body() {
		if( ! $this->has_valid_recipient_list_in_incoming_request_body() ) {
			return false;
		}

		if( ! $this->are_all_email_chunks_valid_for_api_request() ) {
			return false;
		}

		return true;
	}

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
	private function has_email_subject_in_badge_data() {
		return $this->array_has_non_empty_string_at_index( $this->badge_data, 'email_subject' );
	}

	private function has_email_body_in_badge_data() {
		return $this->array_has_non_empty_string_at_index( $this->badge_data, 'email_body' );
	}

	private function has_email_link_text_in_badge_data() {
		return $this->array_has_non_empty_string_at_index( $this->badge_data, 'email_link_text' );
	}

	private function has_email_footer_in_badge_data() {
		return $this->array_has_non_empty_string_at_index( $this->badge_data, 'email_footer' );
	}

	// Checks for the incoming request body
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

	private function has_only_email_adresses_in_recipient_list_of_incoming_request_body() {
		foreach ( $this->incoming_request_body['recipient'] as $recipient ) {
			if( ! is_email( $recipient ) ) {
				return false;
			}
		}
		return true;
	}

	private function has_valid_expiration_timestamp_in_incoming_request_body() {
		return $this->array_has_integer_at_index( $this->incoming_request_body, 'expires' );
	}

	private function has_valid_issued_on_timestamp_in_incoming_request_body() {
		return $this->array_has_integer_at_index( $this->incoming_request_body, 'issued_on' );
	}

	private function has_valid_log_entry_in_incoming_request_body() {
		return ( isset( $this->request_body['log_entry'] ) && is_array( $this->request_body['log_entry'] ) );
	}

	private function has_email_subject_in_incoming_request_body() {
		return $this->array_has_non_empty_string_at_index( $this->incoming_request_body, 'email_subject' );
	}

	private function has_email_body_in_incoming_request_body() {
		return $this->array_has_non_empty_string_at_index( $this->incoming_request_body, 'email_body' );
	}

	private function has_email_link_text_in_incoming_request_body() {
		return $this->array_has_non_empty_string_at_index( $this->incoming_request_body, 'email_link_text' );
	}

	private function has_email_footer_in_incoming_request_body() {
		return $this->array_has_non_empty_string_at_index( $this->incoming_request_body, 'email_footer' );
	}

	private function array_has_non_empty_string_at_index( $array, $index ) {
		return ( isset( $array[$index] ) && strlen( $array[$index] ) > 0 );
	}

	private function array_has_integer_at_index( $array, $index ) {
		return ( isset( $array[$index] ) && is_int( $array[$index] ) );

	}
}