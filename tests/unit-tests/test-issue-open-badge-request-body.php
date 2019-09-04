<?php

require_once __DIR__ . '/../../src/external-apis/Issue_Open_Badge_Request_Body.php';
use appsaloon\obwp\external_apis\openbadgefactory\Issue_Open_Badge_Request_Body;

class Test_Issue_Open_Badge_Request_Body extends WP_UnitTestCase {

	public function test_is_valid_incoming_request_body() {
		$badge_data = array(
		);

		$body_to_check = array(
			'recipient' => array()
		);
		$request_body = new Issue_Open_Badge_Request_Body('test_id', $badge_data, $body_to_check );
		$this->assertFalse( $request_body->is_valid_incoming_request_body() );
	}
}
