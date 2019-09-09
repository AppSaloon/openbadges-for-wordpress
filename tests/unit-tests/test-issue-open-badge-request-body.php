<?php

require_once __DIR__ . '/../../src/external-apis/Issue_Open_Badge_Request_Body.php';
use appsaloon\obwp\external_apis\openbadgefactory\Issue_Open_Badge_Request_Body;

class Test_Issue_Open_Badge_Request_Body extends WP_UnitTestCase {
	const TEST_CLIENT_ID = 'test_id';
	protected $complete_badge_data = array(
		'badge_id' => '2136546',
		'email_subject' => 'You have earned a badge',
		'email_body' => 'This is the body for the email',
		'email_link_text' => 'click here to claim your badge',
		'email_footer' => 'This will appear at the bottom of the email',
	);

	protected $complete_request_body = array(
		'recipient' => array(
			'john@test.com',
			'roger@test.com',
			),
		'expires' => 2134564,
		'issued_on' => 56456456,
		'email_subject' => 'You have earned a badge',
		'email_body' => 'This is the body for the email',
		'email_link_text' => 'click here to claim your badge',
		'email_footer' => 'This will appear at the bottom of the email',
		'log_entry' => array(
			'type' => 'issue_badge',
			'issued_by' => 'Wordpress Open Badges integration'
		),
		'api_consumer_id' => 'test_id'
	);

	public function test_complete_badge_data_and_request_body() {
		$request_body = new Issue_Open_Badge_Request_Body(
			'test_id',
			$this->complete_badge_data,
			$this->complete_request_body
		);

		$this->assertTrue( $request_body->is_valid_incoming_request_body() );
	}

	public function test_get_request_body() {
		$request_body_object = new Issue_Open_Badge_Request_Body(
			static::TEST_CLIENT_ID,
			$this->complete_badge_data,
			$this->complete_request_body
		);

		$expected_request_body = $this->complete_request_body;
		$expected_request_body['api_consumer_id'] = static::TEST_CLIENT_ID;

		$request_body_to_use = $request_body_object->get_request_body();

		$this->assertSame( $expected_request_body, $request_body_to_use );

		// should return null when invalid request_body was passed to constructor
		$request_body_data = $this->complete_request_body;
		$request_body_data['recipient'] = '';
		$request_body_to_use = new Issue_Open_Badge_Request_Body(
			static::TEST_CLIENT_ID,
			$this->complete_badge_data,
			$request_body_data
		);
		$this->assertNull( $request_body_to_use->get_request_body() );
	}

	public function test_no_recipients_provided() {
		$request_body_data = $this->complete_request_body;
		$request_body_data['recipient'] = array( );

		$request_body = new Issue_Open_Badge_Request_Body(
			static::TEST_CLIENT_ID,
			$this->complete_badge_data,
			$request_body_data
		);
		$this->assertFalse( $request_body->is_valid_incoming_request_body() );

		unset( $request_body_data['recipient'] );
		$request_body = new Issue_Open_Badge_Request_Body(
			static::TEST_CLIENT_ID,
			$this->complete_badge_data,
			$request_body_data
		);
		$this->assertFalse( $request_body->is_valid_incoming_request_body() );
	}

	public function test_invalid_recipients_provided() {
		$request_body_data = $this->complete_request_body;
		$request_body_data['recipient'] = array( 1, 'roger@test.com' );

		$request_body = new Issue_Open_Badge_Request_Body(
			static::TEST_CLIENT_ID,
			$this->complete_badge_data,
			$request_body_data
		);
		$this->assertFalse( $request_body->is_valid_incoming_request_body() );

		$request_body_data['recipient'] = array( 'not_an_email' );
		$request_body = new Issue_Open_Badge_Request_Body(
			static::TEST_CLIENT_ID,
			$this->complete_badge_data,
			$request_body_data
		);
		$this->assertFalse( $request_body->is_valid_incoming_request_body() );
	}

	public function test_invalid_expiration_timestamp() {
		$request_body_data = $this->complete_request_body;
		$request_body_data['expires'] = 'not_a_timestamp';
		$request_body = new Issue_Open_Badge_Request_Body(
			static::TEST_CLIENT_ID,
			$this->complete_badge_data,
			$request_body_data
		);

		// An invalid expiration timestamp is simply ignored and the request body is deemed valid
		$this->assertTrue( $request_body->is_valid_incoming_request_body() );
	}

	public function test_invalid_issued_on_timestamp() {
		$request_body_data = $this->complete_request_body;
		$request_body_data['issued_on'] = 'not_a_timestamp';
		$request_body = new Issue_Open_Badge_Request_Body(
			static::TEST_CLIENT_ID,
			$this->complete_badge_data,
			$request_body_data
		);

		// An invalid issued on timestamp is simply ignored and the request body is deemed valid
		$this->assertTrue( $request_body->is_valid_incoming_request_body() );
	}

	public function test_only_email_subject_missing() {
		// email_subject missing from badge data but present in request body
		$badge_data = $this->complete_badge_data;
		unset( $badge_data['email_subject'] );
		$request_body = new Issue_Open_Badge_Request_Body( static::TEST_CLIENT_ID, $badge_data, $this->complete_request_body );
		$this->assertTrue( $request_body->is_valid_incoming_request_body() );

		// email subject missing from badge data and request body
		$request_body = $this->complete_request_body;
		unset( $request_body['email_subject'] );
		$request_body = new Issue_Open_Badge_Request_Body( static::TEST_CLIENT_ID, $badge_data, $request_body );
		$this->assertFalse( $request_body->is_valid_incoming_request_body() );
	}

	public function test_only_email_body_missing() {
		// email_body missing from badge data but present in request body
		$badge_data = $this->complete_badge_data;
		unset( $badge_data['email_body'] );
		$request_body = new Issue_Open_Badge_Request_Body( static::TEST_CLIENT_ID, $badge_data, $this->complete_request_body );
		$this->assertTrue( $request_body->is_valid_incoming_request_body() );

		// email_body missing from badge data and request body
		$request_body = $this->complete_request_body;
		unset( $request_body['email_body'] );
		$request_body = new Issue_Open_Badge_Request_Body( static::TEST_CLIENT_ID, $badge_data, $request_body );
		$this->assertFalse( $request_body->is_valid_incoming_request_body() );
	}

	public function test_only_email_link_text_missing() {
		// email_link_text missing from badge data but present in request body
		$badge_data = $this->complete_badge_data;
		unset( $badge_data['email_link_text'] );
		$request_body = new Issue_Open_Badge_Request_Body( static::TEST_CLIENT_ID, $badge_data, $this->complete_request_body );
		$this->assertTrue( $request_body->is_valid_incoming_request_body() );

		// email_link_text missing from badge data and request body
		$request_body = $this->complete_request_body;
		unset( $request_body['email_link_text'] );
		$request_body = new Issue_Open_Badge_Request_Body( static::TEST_CLIENT_ID, $badge_data, $request_body );
		$this->assertFalse( $request_body->is_valid_incoming_request_body() );
	}

	public function test_only_email_footer_missing() {
		// email_footer missing from badge data but present in request body
		$badge_data = $this->complete_badge_data;
		unset( $badge_data['email_footer'] );
		$request_body = new Issue_Open_Badge_Request_Body( static::TEST_CLIENT_ID, $badge_data, $this->complete_request_body );
		$this->assertTrue( $request_body->is_valid_incoming_request_body() );

		// email_footer missing from badge data and request body
		$request_body = $this->complete_request_body;
		unset( $request_body['email_footer'] );
		$request_body = new Issue_Open_Badge_Request_Body( static::TEST_CLIENT_ID, $badge_data, $request_body );
		$this->assertFalse( $request_body->is_valid_incoming_request_body() );
	}
}
