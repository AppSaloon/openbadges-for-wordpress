<?php
use appsaloon\obwp\external_apis\openbadgefactory\Open_Badge_Factory_Credentials;

use org\bovigo\vfs\vfsStream,
	org\bovigo\vfs\vfsStreamDirectory;

class Test_Open_Badge_Factory_Credentials extends \WP_Mock\Tools\TestCase {

	/**
	 * @var vfsStreamDirectory
	 */
	private $root;

	const OLD_CLIENT_ID = 'old_client_id';
	const NEW_CLIENT_ID = 'new_client_id';
	const CURRENT_TIME = '123546';
	const OLD_PRIVATE_KEY = 'an old private key';
	const OLD_CLIENT_CERTIFICATE = 'an old certificate';

	const NEW_PRIVATE_KEY = 'a new private key';
	const NEW_CLIENT_CERTIFICATE = 'a new certificate';

	const UNWRITEABLE_ROOT = 'test_folder00';
	const POPULATED_PLUGIN_FOLDER = 'test_folder01';
	const FOLDER_WITH_UNWRITEABLE_PRIVATE_KEY_FILE = 'test_folder02';
	const POPULATED_UNWRITEABLE_PLUGIN_FOLDER = 'test_folder03';
	const EMPTY_FOLDER = 'test_folder04';

	const FOLDER_WITH_UNWRITEABLE_CERTIFICATE_FILE = 'test_folder';
	const RELATIVE_PRIVATE_KEY_PATH = Open_Badge_Factory_Credentials::CREDENTIALS_FOLDER_NAME . DIRECTORY_SEPARATOR .
									  Open_Badge_Factory_Credentials::PRIVATE_KEY_FILE_NAME;
	const RELATIVE_CERTIFICATE_FILE_PATH = Open_Badge_Factory_Credentials::CREDENTIALS_FOLDER_NAME . DIRECTORY_SEPARATOR .
										   Open_Badge_Factory_Credentials::CLIENT_CERTIFICATE_FILE_NAME;

	public function setUp() : void {
		WP_Mock::setUp();
		vfsStream::umask( 022 );

		$structure = array(
			self::UNWRITEABLE_ROOT => array(),
			self::POPULATED_PLUGIN_FOLDER => array(
				Open_Badge_Factory_Credentials::CREDENTIALS_FOLDER_NAME => array(
					Open_Badge_Factory_Credentials::PRIVATE_KEY_FILE_NAME => self::OLD_PRIVATE_KEY,
					Open_Badge_Factory_Credentials::CLIENT_CERTIFICATE_FILE_NAME => self::OLD_CLIENT_CERTIFICATE,
				)
			),
			self::FOLDER_WITH_UNWRITEABLE_PRIVATE_KEY_FILE => array(
				Open_Badge_Factory_Credentials::CREDENTIALS_FOLDER_NAME => array(
					Open_Badge_Factory_Credentials::PRIVATE_KEY_FILE_NAME => self::OLD_PRIVATE_KEY,
					Open_Badge_Factory_Credentials::CLIENT_CERTIFICATE_FILE_NAME => self::OLD_CLIENT_CERTIFICATE,
				)
			),
			self::POPULATED_UNWRITEABLE_PLUGIN_FOLDER => array(
				Open_Badge_Factory_Credentials::CREDENTIALS_FOLDER_NAME => array(
					Open_Badge_Factory_Credentials::PRIVATE_KEY_FILE_NAME => self::OLD_PRIVATE_KEY,
					Open_Badge_Factory_Credentials::CLIENT_CERTIFICATE_FILE_NAME => self::OLD_CLIENT_CERTIFICATE,
				)
			),
			self::FOLDER_WITH_UNWRITEABLE_CERTIFICATE_FILE => array(
				Open_Badge_Factory_Credentials::CREDENTIALS_FOLDER_NAME => array(
					Open_Badge_Factory_Credentials::PRIVATE_KEY_FILE_NAME => self::OLD_PRIVATE_KEY,
					Open_Badge_Factory_Credentials::CLIENT_CERTIFICATE_FILE_NAME => self::OLD_CLIENT_CERTIFICATE,
				)
			),
			self::EMPTY_FOLDER => array(),
		);

		$this->root = vfsStream::setup( 'testDirectory', null, $structure );

		$this->set_filemock_permissions();
	}

	private function set_filemock_permissions() {
		$this->set_unwriteable_root_permissions();
		$this->set_populated_unwriteable_plugin_folder_permissions();
		$this->set_folder_with_unwriteable_private_key_file_permissions();
		$this->set_folder_with_unwriteable_certificate_file_permissions();
	}

	private function set_unwriteable_root_permissions() {
		$this->root->getChild( self::UNWRITEABLE_ROOT )->chmod( 0000 );
	}

	private function set_populated_unwriteable_plugin_folder_permissions() {
		foreach (
			$this->root->getChild( self::POPULATED_UNWRITEABLE_PLUGIN_FOLDER)->getChild( Open_Badge_Factory_Credentials::CREDENTIALS_FOLDER_NAME )->getChildren() as $child ) {
			$child->chmod( 0444 );
			$child->chown( vfsStream::OWNER_USER_2);
		}
		$this->root->getChild( self::POPULATED_UNWRITEABLE_PLUGIN_FOLDER)->chmod( 0000 );
	}

	private function set_folder_with_unwriteable_private_key_file_permissions() {
		$test_folder_key_file =$this->root->getChild( self::FOLDER_WITH_UNWRITEABLE_PRIVATE_KEY_FILE )->getChild( Open_Badge_Factory_Credentials::CREDENTIALS_FOLDER_NAME )->getChild( Open_Badge_Factory_Credentials::PRIVATE_KEY_FILE_NAME);
		$test_folder_key_file->chmod( 0444 );
		$test_folder_key_file->chown( vfsStream::OWNER_USER_2 );
	}

	private function set_folder_with_unwriteable_certificate_file_permissions() {
		$test_folder_key_file =$this->root->getChild( self::FOLDER_WITH_UNWRITEABLE_CERTIFICATE_FILE )->getChild( Open_Badge_Factory_Credentials::CREDENTIALS_FOLDER_NAME )->getChild( Open_Badge_Factory_Credentials::CLIENT_CERTIFICATE_FILE_NAME);
		$test_folder_key_file->chmod( 0444 );
		$test_folder_key_file->chown( vfsStream::OWNER_USER_2 );
	}

	public function tearDown() : void {
		WP_Mock::tearDown();
	}

	private function mock_get_option_client_id_success( $count, $return_value = self::OLD_CLIENT_ID ) {
		WP_Mock::userFunction(
			'get_option',
			array(
				'times' => $count,
				'args' => Open_Badge_Factory_Credentials::CLIENT_ID_OPTION_NAME,
				'return' => $return_value,
			)
		);
	}

	private function mock_get_option_client_id_fail() {
		WP_Mock::userFunction(
			'get_option',
			array(
				'times' => "1",
				'args' => Open_Badge_Factory_Credentials::CLIENT_ID_OPTION_NAME,
				'return' => false,
			)
		);
	}

	private function mock_update_option_client_id_success( $client_id ) {
		WP_Mock::userFunction(
			'update_option',
			array(
				'times' => 1,
				'args' => array( Open_Badge_Factory_Credentials::CLIENT_ID_OPTION_NAME, $client_id ),
				'return' => true,
			)
		);
	}

	private function mock_update_option_client_id_fail( $client_id ) {
		WP_Mock::userFunction(
			'update_option',
			array(
				'times' => 1,
				'args' => array( Open_Badge_Factory_Credentials::CLIENT_ID_OPTION_NAME, $client_id ),
				'return' => false,
			)
		);
	}

	private function mock_update_option_credentials_created_at() {
		WP_Mock::userFunction(
			'update_option',
			array(
				'times' => 1,
				'args' => array( Open_Badge_Factory_Credentials::CREDENTIALS_SAVED_TIMESTAMP_OPTION_NAME, self::CURRENT_TIME ),
				'return' => true,
			)
		);
	}

	private function mock_current_time() {
		WP_Mock::userFunction(
			'current_time',
			array(
				'times' => 1,
				'return' => self::CURRENT_TIME
			)
		);
	}

	public function test_credentials_folder_is_created() {
		$this->mock_get_option_client_id_success( 1 );

		new Open_Badge_Factory_Credentials( $this->root->url() );

		$this->assertTrue( $this->root->hasChild( Open_Badge_Factory_Credentials::CREDENTIALS_FOLDER_NAME) );
		$this->assertEquals( 0755, $this->root->getChild( Open_Badge_Factory_Credentials::CREDENTIALS_FOLDER_NAME )->getPermissions() );
	}

	public function test_can_not_create_credentials_folder() {
		$this->expectException( \Exception::class );

		$this->mock_get_option_client_id_success( 0 );
		new Open_Badge_Factory_Credentials( $this->root->getChild( self::UNWRITEABLE_ROOT)->url() );
	}

	public function test_private_key_path_is_set_correctly() {
		$this->mock_get_option_client_id_success( 1 );
		$credentials = new Open_Badge_Factory_Credentials( $this->root->url() );

		$expected_path = $this->root->url() . DIRECTORY_SEPARATOR . self::RELATIVE_PRIVATE_KEY_PATH;

		$this->assertEquals( $expected_path, $credentials->get_private_key_path() );
	}

	public function test_certificate_path_is_set_correctly() {
		$this->mock_get_option_client_id_success( 1 );
		$credentials = new Open_Badge_Factory_Credentials( $this->root->url() );

		$expected_path = $this->root->url() . DIRECTORY_SEPARATOR . self::RELATIVE_CERTIFICATE_FILE_PATH;

		$this->assertEquals( $expected_path, $credentials->get_certificate_path() );
	}

	public function test_save_new_credentials_with_existing_credentials_success() {
		// mock the wp functions called during test
		$this->mock_get_option_client_id_success( "+2" );
		$this->mock_update_option_client_id_success( self::OLD_CLIENT_ID );
		$this->mock_current_time();
		$this->mock_update_option_credentials_created_at();

		// get folder to use for test
		$plugin_folder_object = $this->root->getChild( self::POPULATED_PLUGIN_FOLDER );

		// run code to test
		$credentials = new Open_Badge_Factory_Credentials( $plugin_folder_object->url() );
		$is_save_successful = $credentials->save_new_credentials( self::OLD_CLIENT_ID,
																  self::NEW_PRIVATE_KEY,
																  self::NEW_CLIENT_CERTIFICATE
																);
		// start assertions
		$this->assertTrue( $is_save_successful );
		$this->assertEquals( self::OLD_CLIENT_ID, $credentials->get_client_id() );

		// tests if the private key file was created with the correct contents
		$this->assertTrue( $plugin_folder_object->hasChild( self::RELATIVE_PRIVATE_KEY_PATH ) );

		$private_key_file = $plugin_folder_object->getChild( self::RELATIVE_PRIVATE_KEY_PATH );
		$this->assertEquals( self::NEW_PRIVATE_KEY, file_get_contents( $private_key_file->url() ) );

		// tests if the certificate file was created with the correct contents
		$this->assertTrue( $plugin_folder_object->hasChild( self::RELATIVE_CERTIFICATE_FILE_PATH ) );

		$certificate_file = $plugin_folder_object->getChild( self::RELATIVE_CERTIFICATE_FILE_PATH );
		$this->assertEquals( self::NEW_CLIENT_CERTIFICATE, file_get_contents( $certificate_file->url() ) );
	}

	public function test_save_new_credentials_without_existing_client_id_but_credential_files_success() {
		// mock the wp functions called during test
		$this->mock_get_option_client_id_fail();
		$this->mock_update_option_client_id_success( self::NEW_CLIENT_ID );
		$this->mock_get_option_client_id_success( 2, self::NEW_CLIENT_ID );
		$this->mock_current_time();
		$this->mock_update_option_credentials_created_at();

		// get folder to use for test
		$plugin_folder_object = $this->root->getChild( self::POPULATED_PLUGIN_FOLDER );

		// run code to test
		$credentials = new Open_Badge_Factory_Credentials( $plugin_folder_object->url() );
		$is_save_successful = $credentials->save_new_credentials( self::NEW_CLIENT_ID,
																  self::NEW_PRIVATE_KEY,
																  self::NEW_CLIENT_CERTIFICATE
																);

		// start assertions
		$this->assertTrue( $is_save_successful );
		$this->assertEquals( self::NEW_CLIENT_ID, $credentials->get_client_id() );

		// tests if the private file was created with the correct contents
		$this->assertTrue( $plugin_folder_object->hasChild( self::RELATIVE_PRIVATE_KEY_PATH ) );

		$private_key_file = $plugin_folder_object->getChild( self::RELATIVE_PRIVATE_KEY_PATH );
		$this->assertEquals( self::NEW_PRIVATE_KEY, file_get_contents( $private_key_file->url() ) );

		// tests if the certificate file was created with the correct contents
		$this->assertTrue( $plugin_folder_object->hasChild( self::RELATIVE_CERTIFICATE_FILE_PATH ) );

		$certificate_file = $plugin_folder_object->getChild( self::RELATIVE_CERTIFICATE_FILE_PATH );
		$this->assertEquals( self::NEW_CLIENT_CERTIFICATE, file_get_contents( $certificate_file->url() ) );
	}

	public function test_save_new_credentials_without_any_credentials_success() {
		// mock the wp functions called during test
		$this->mock_get_option_client_id_fail();
		$this->mock_update_option_client_id_success( self::NEW_CLIENT_ID );
		$this->mock_get_option_client_id_success( 2, self::NEW_CLIENT_ID );
		$this->mock_current_time();
		$this->mock_update_option_credentials_created_at();

		// get folder to use for test
		$plugin_folder_object = $this->root;

		// run code to test
		$credentials = new Open_Badge_Factory_Credentials( $plugin_folder_object->url() );
		$is_save_successful = $credentials->save_new_credentials( self::NEW_CLIENT_ID,
																  self::NEW_PRIVATE_KEY,
																  self::NEW_CLIENT_CERTIFICATE
																);
		// start assertions
		$this->assertTrue( $is_save_successful );
		$this->assertEquals( self::NEW_CLIENT_ID, $credentials->get_client_id() );

		// tests if the private file was created with the correct contents
		$this->assertTrue( $plugin_folder_object->hasChild( self::RELATIVE_PRIVATE_KEY_PATH ) );

		$private_key_file = $plugin_folder_object->getChild( self::RELATIVE_PRIVATE_KEY_PATH );
		$this->assertEquals( self::NEW_PRIVATE_KEY, file_get_contents( $private_key_file->url() ) );

		// tests if the certificate file was created with the correct contents
		$this->assertTrue( $plugin_folder_object->hasChild( self::RELATIVE_CERTIFICATE_FILE_PATH ) );

		$certificate_file = $plugin_folder_object->getChild( self::RELATIVE_CERTIFICATE_FILE_PATH );
		$this->assertEquals( self::NEW_CLIENT_CERTIFICATE, file_get_contents( $certificate_file->url() ) );
	}

	public function test_save_new_credentials_with_existing_credentials_and_failed_client_id_update() {
		// mock the wp functions called during test
		$this->mock_get_option_client_id_success( "+2" );
		$this->mock_update_option_client_id_fail( self::NEW_CLIENT_ID );

		// get folder to use for test
		$plugin_folder_object = $this->root->getChild( self::POPULATED_PLUGIN_FOLDER );

		// run code to test
		$credentials = new Open_Badge_Factory_Credentials( $plugin_folder_object->url() );
		$is_save_successful = $credentials->save_new_credentials( self::NEW_CLIENT_ID,
																  self::NEW_PRIVATE_KEY,
																  self::NEW_CLIENT_CERTIFICATE
																);
		// start assertions
		$this->assertFalse( $is_save_successful );
		$this->assertEquals( self::OLD_CLIENT_ID, $credentials->get_client_id() );

		// tests if the old private key file was untouched
		$this->assertTrue( $plugin_folder_object->hasChild( self::RELATIVE_PRIVATE_KEY_PATH ) );

		$private_key_file = $plugin_folder_object->getChild( self::RELATIVE_PRIVATE_KEY_PATH );
		$this->assertEquals( self::OLD_PRIVATE_KEY, file_get_contents( $private_key_file->url() ) );

		// tests if the old certificate file was untouched
		$this->assertTrue( $plugin_folder_object->hasChild( self::RELATIVE_CERTIFICATE_FILE_PATH ) );

		$certificate_file = $plugin_folder_object->getChild( self::RELATIVE_CERTIFICATE_FILE_PATH );
		$this->assertEquals( self::OLD_CLIENT_CERTIFICATE, file_get_contents( $certificate_file->url() ) );
	}

	public function test_save_new_credentials_with_existing_client_id_no_credential_files_and_failed_client_id_update() {
		// mock the wp functions called during test
		$this->mock_get_option_client_id_success( "+2" );
		$this->mock_update_option_client_id_fail( self::NEW_CLIENT_ID );

		// run code to test
		$credentials = new Open_Badge_Factory_Credentials( $this->root->url() );
		$is_save_successful = $credentials->save_new_credentials( self::NEW_CLIENT_ID,
																  self::NEW_PRIVATE_KEY,
																  self::NEW_CLIENT_CERTIFICATE
																);
		// start assertions
		$this->assertFalse( $is_save_successful );
		$this->assertEquals( self::OLD_CLIENT_ID, $credentials->get_client_id() );

		// test the private key file was not created
		$private_key_path =
			Open_Badge_Factory_Credentials::CREDENTIALS_FOLDER_NAME .
			DIRECTORY_SEPARATOR . Open_Badge_Factory_Credentials::PRIVATE_KEY_FILE_NAME;
		$this->assertFalse( $this->root->hasChild( $private_key_path ) );

		// test the certificate file was not created
		$certificate_file_path =
			Open_Badge_Factory_Credentials::CREDENTIALS_FOLDER_NAME .
			DIRECTORY_SEPARATOR . Open_Badge_Factory_Credentials::CLIENT_CERTIFICATE_FILE_NAME;
		$this->assertFalse( $this->root->hasChild( $certificate_file_path ) );
	}

	public function test_save_new_credentials_with_existing_credentials_and_failed_private_key_file_creation() {
		// mock get_option() and update_option()
		$this->mock_get_option_client_id_success( 1 );
		$this->mock_get_option_client_id_success( 1, self::NEW_CLIENT_ID );
		$this->mock_update_option_client_id_success( self::NEW_CLIENT_ID );

		// get folder for testing
		$plugin_folder_object = $this->root->getChild( self::FOLDER_WITH_UNWRITEABLE_PRIVATE_KEY_FILE );

		// run the code to be tested
		$credentials = new Open_Badge_Factory_Credentials( $plugin_folder_object->url() );
		$is_save_successful = $credentials->save_new_credentials( self::NEW_CLIENT_ID,
																  self::NEW_PRIVATE_KEY,
																  self::NEW_CLIENT_CERTIFICATE
																);

		//begin assertions
		$this->assertFalse( $is_save_successful );

		// test the private key file was untouched
		$this->assertTrue( $plugin_folder_object->hasChild( self::RELATIVE_PRIVATE_KEY_PATH ) );

		$private_key_file = $plugin_folder_object->getChild( self::RELATIVE_PRIVATE_KEY_PATH );
		$this->assertEquals( self::OLD_PRIVATE_KEY, file_get_contents( $private_key_file->url() ) );

		// tests if the old certificate file was untouched
		$this->assertTrue( $plugin_folder_object->hasChild( self::RELATIVE_CERTIFICATE_FILE_PATH ) );

		$certificate_file = $plugin_folder_object->getChild( self::RELATIVE_CERTIFICATE_FILE_PATH );
		$this->assertEquals( self::OLD_CLIENT_CERTIFICATE, file_get_contents( $certificate_file->url() ) );
	}

	public function test_save_new_credentials_with_existing_credentials_and_failed_certificate_file_creation() {
		// mock get_option() and update_option()
		$this->mock_get_option_client_id_success( 1 );
		$this->mock_get_option_client_id_success( 1, self::NEW_CLIENT_ID );
		$this->mock_update_option_client_id_success( self::NEW_CLIENT_ID );

		// get folder for testing
		$plugin_folder_object = $this->root->getChild( self::FOLDER_WITH_UNWRITEABLE_CERTIFICATE_FILE );

		// run the code to be tested
		$credentials = new Open_Badge_Factory_Credentials( $plugin_folder_object->url() );
		$is_save_successful = $credentials->save_new_credentials( self::NEW_CLIENT_ID,
																  self::NEW_PRIVATE_KEY,
																  self::NEW_CLIENT_CERTIFICATE
																);

		//begin assertions
		$this->assertFalse( $is_save_successful );

		// test the private key file was changed, this should change because it's saved before the unwritable certificate
		$this->assertTrue( $plugin_folder_object->hasChild( self::RELATIVE_PRIVATE_KEY_PATH ) );

		$private_key_file = $plugin_folder_object->getChild( self::RELATIVE_PRIVATE_KEY_PATH );
		$this->assertEquals( self::NEW_PRIVATE_KEY, file_get_contents( $private_key_file->url() ) );

		// tests if the old certificate file was untouched
		$this->assertTrue( $plugin_folder_object->hasChild( self::RELATIVE_CERTIFICATE_FILE_PATH ) );

		$certificate_file = $plugin_folder_object->getChild( self::RELATIVE_CERTIFICATE_FILE_PATH );
		$this->assertEquals( self::OLD_CLIENT_CERTIFICATE, file_get_contents( $certificate_file->url() ) );
	}
}