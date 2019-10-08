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

	const UNWRITEABLE_ROOT = 'unwritable_root';
	const POPULATED_PLUGIN_FOLDER = 'populated_plugin_folder';
	const POPULATED_UNWRITEABLE_PLUGIN_FOLDER = 'populated_unwriteable_plugin_folder';
	const EMPTY_FOLDER = 'empty_folder';

	const TEST_FOLDER = 'test_folder';

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
			self::POPULATED_UNWRITEABLE_PLUGIN_FOLDER => array(
				Open_Badge_Factory_Credentials::CREDENTIALS_FOLDER_NAME => array(
					Open_Badge_Factory_Credentials::PRIVATE_KEY_FILE_NAME => self::OLD_PRIVATE_KEY,
					Open_Badge_Factory_Credentials::CLIENT_CERTIFICATE_FILE_NAME => self::OLD_CLIENT_CERTIFICATE,
				)
			),
			self::TEST_FOLDER => array(
				Open_Badge_Factory_Credentials::CREDENTIALS_FOLDER_NAME => array(
					Open_Badge_Factory_Credentials::PRIVATE_KEY_FILE_NAME => self::OLD_PRIVATE_KEY,
					Open_Badge_Factory_Credentials::CLIENT_CERTIFICATE_FILE_NAME => self::OLD_CLIENT_CERTIFICATE,
				)
			),
			self::EMPTY_FOLDER => array(),
		);

		$this->root = vfsStream::setup( 'testDirectory', null, $structure );

		$this->root->getChild( self::UNWRITEABLE_ROOT )->chmod( 0000 );

		foreach (
			$this->root->getChild( self::POPULATED_UNWRITEABLE_PLUGIN_FOLDER)->getChild( Open_Badge_Factory_Credentials::CREDENTIALS_FOLDER_NAME )->getChildren() as $child ) {
			$child->chmod( 0444 );
			$child->chown( vfsStream::OWNER_USER_2);
		}

		$test_folder_key_file =$this->root->getChild( self::TEST_FOLDER )->getChild( Open_Badge_Factory_Credentials::CREDENTIALS_FOLDER_NAME )->getChild( Open_Badge_Factory_Credentials::PRIVATE_KEY_FILE_NAME);
		$test_folder_key_file->chmod( 0444 );
		$test_folder_key_file->chown( vfsStream::OWNER_USER_2 );

		$this->root->getChild( self::POPULATED_UNWRITEABLE_PLUGIN_FOLDER)->chmod( 0000 );
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

		$expected_path = $this->root->url()
			. DIRECTORY_SEPARATOR .	Open_Badge_Factory_Credentials::CREDENTIALS_FOLDER_NAME
			. DIRECTORY_SEPARATOR . Open_Badge_Factory_Credentials::PRIVATE_KEY_FILE_NAME;

		$this->assertEquals( $expected_path, $credentials->get_private_key_path() );
	}

	public function test_certificate_path_is_set_correctly() {
		$this->mock_get_option_client_id_success( 1 );
		$credentials = new Open_Badge_Factory_Credentials( $this->root->url() );

		$expected_path = $this->root->url()
			. DIRECTORY_SEPARATOR . Open_Badge_Factory_Credentials::CREDENTIALS_FOLDER_NAME
			. DIRECTORY_SEPARATOR . Open_Badge_Factory_Credentials::CLIENT_CERTIFICATE_FILE_NAME;

		$this->assertEquals( $expected_path, $credentials->get_certificate_path() );
	}

	public function test_save_new_credentials_with_existing_credentials_success() {
		$this->mock_get_option_client_id_success( "+2" );

		$plugin_folder_object = $this->root->getChild( self::POPULATED_PLUGIN_FOLDER );

		$credentials = new Open_Badge_Factory_Credentials( $plugin_folder_object->url() );

		$this->mock_update_option_client_id_success( self::OLD_CLIENT_ID );
		$this->mock_current_time();
		$this->mock_update_option_credentials_created_at();

		$is_save_successful = $credentials->save_new_credentials( self::OLD_CLIENT_ID, self::NEW_PRIVATE_KEY, self::NEW_CLIENT_CERTIFICATE );

		$this->assertTrue( $is_save_successful );
		$this->assertEquals( self::OLD_CLIENT_ID, $credentials->get_client_id() );

		// tests if the private key file was created with the correct contents
		$private_key_path =
			Open_Badge_Factory_Credentials::CREDENTIALS_FOLDER_NAME .
			DIRECTORY_SEPARATOR . Open_Badge_Factory_Credentials::PRIVATE_KEY_FILE_NAME;
		$this->assertTrue( $plugin_folder_object->hasChild( $private_key_path ) );

		$private_key_file = $plugin_folder_object->getChild( $private_key_path );
		$this->assertEquals( self::NEW_PRIVATE_KEY, file_get_contents( $private_key_file->url() ) );

		// tests if the certificate file was created with the correct contents
		$certificate_file_path =
			Open_Badge_Factory_Credentials::CREDENTIALS_FOLDER_NAME .
			DIRECTORY_SEPARATOR . Open_Badge_Factory_Credentials::CLIENT_CERTIFICATE_FILE_NAME;
		$this->assertTrue( $plugin_folder_object->hasChild( $certificate_file_path ) );

		$certificate_file = $plugin_folder_object->getChild( $certificate_file_path );
		$this->assertEquals( self::NEW_CLIENT_CERTIFICATE, file_get_contents( $certificate_file->url() ) );
	}

	public function test_save_new_credentials_without_existing_client_id_but_credential_files_success() {
		$this->mock_get_option_client_id_fail();

		$plugin_folder_object = $this->root->getChild( self::POPULATED_PLUGIN_FOLDER );

		$credentials = new Open_Badge_Factory_Credentials( $plugin_folder_object->url() );

		$this->mock_update_option_client_id_success( self::NEW_CLIENT_ID );
		$this->mock_get_option_client_id_success( 2, self::NEW_CLIENT_ID );
		$this->mock_current_time();
		$this->mock_update_option_credentials_created_at();

		$is_save_successful = $credentials->save_new_credentials( self::NEW_CLIENT_ID, self::NEW_PRIVATE_KEY, self::NEW_CLIENT_CERTIFICATE );

		$this->assertTrue( $is_save_successful );
		$this->assertEquals( self::NEW_CLIENT_ID, $credentials->get_client_id() );

		// tests if the private file was created with the correct contents
		$private_key_path =
			Open_Badge_Factory_Credentials::CREDENTIALS_FOLDER_NAME .
			DIRECTORY_SEPARATOR . Open_Badge_Factory_Credentials::PRIVATE_KEY_FILE_NAME;
		$this->assertTrue( $plugin_folder_object->hasChild( $private_key_path ) );

		$private_key_file = $plugin_folder_object->getChild( $private_key_path );
		$this->assertEquals( self::NEW_PRIVATE_KEY, file_get_contents( $private_key_file->url() ) );

		// tests if the certificate file was created with the correct contents
		$certificate_file_path =
			Open_Badge_Factory_Credentials::CREDENTIALS_FOLDER_NAME .
			DIRECTORY_SEPARATOR . Open_Badge_Factory_Credentials::CLIENT_CERTIFICATE_FILE_NAME;
		$this->assertTrue( $plugin_folder_object->hasChild( $certificate_file_path ) );

		$certificate_file = $plugin_folder_object->getChild( $certificate_file_path );
		$this->assertEquals( self::NEW_CLIENT_CERTIFICATE, file_get_contents( $certificate_file->url() ) );
	}

	public function test_save_new_credentials_without_any_credentials_success() {
		$this->mock_get_option_client_id_fail();

		$plugin_folder_object = $this->root;
		//$plugin_folder_object = $this->root->getChild( self::EMPTY_FOLDER );

		$credentials = new Open_Badge_Factory_Credentials( $plugin_folder_object->url() );

		$this->mock_update_option_client_id_success( self::NEW_CLIENT_ID );
		$this->mock_get_option_client_id_success( 2, self::NEW_CLIENT_ID );
		$this->mock_current_time();
		$this->mock_update_option_credentials_created_at();

		$is_save_successful = $credentials->save_new_credentials( self::NEW_CLIENT_ID, self::NEW_PRIVATE_KEY, self::NEW_CLIENT_CERTIFICATE );

		$this->assertTrue( $is_save_successful );
		$this->assertEquals( self::NEW_CLIENT_ID, $credentials->get_client_id() );

		// tests if the private file was created with the correct contents
		$private_key_path =
			Open_Badge_Factory_Credentials::CREDENTIALS_FOLDER_NAME .
			DIRECTORY_SEPARATOR . Open_Badge_Factory_Credentials::PRIVATE_KEY_FILE_NAME;
		$this->assertTrue( $plugin_folder_object->hasChild( $private_key_path ) );

		$private_key_file = $plugin_folder_object->getChild( $private_key_path );
		$this->assertEquals( self::NEW_PRIVATE_KEY, file_get_contents( $private_key_file->url() ) );

		// tests if the certificate file was created with the correct contents
		$certificate_file_path =
			Open_Badge_Factory_Credentials::CREDENTIALS_FOLDER_NAME .
			DIRECTORY_SEPARATOR . Open_Badge_Factory_Credentials::CLIENT_CERTIFICATE_FILE_NAME;
		$this->assertTrue( $plugin_folder_object->hasChild( $certificate_file_path ) );

		$certificate_file = $plugin_folder_object->getChild( $certificate_file_path );
		$this->assertEquals( self::NEW_CLIENT_CERTIFICATE, file_get_contents( $certificate_file->url() ) );
	}

	public function test_save_new_credentials_with_existing_credentials_and_failed_client_id_update() {
		$this->mock_get_option_client_id_success( "+2" );

		$plugin_folder_object = $this->root->getChild( self::POPULATED_PLUGIN_FOLDER );

		$credentials = new Open_Badge_Factory_Credentials( $plugin_folder_object->url() );

		$this->mock_update_option_client_id_fail( self::NEW_CLIENT_ID );

		$is_save_successful = $credentials->save_new_credentials( self::NEW_CLIENT_ID, self::NEW_PRIVATE_KEY, self::NEW_CLIENT_CERTIFICATE );

		$this->assertFalse( $is_save_successful );
		$this->assertEquals( self::OLD_CLIENT_ID, $credentials->get_client_id() );

		// tests if the old private key file was untouched
		$private_key_path =
			Open_Badge_Factory_Credentials::CREDENTIALS_FOLDER_NAME .
			DIRECTORY_SEPARATOR . Open_Badge_Factory_Credentials::PRIVATE_KEY_FILE_NAME;
		$this->assertTrue( $plugin_folder_object->hasChild( $private_key_path ) );

		$private_key_file = $plugin_folder_object->getChild( $private_key_path );
		$this->assertEquals( self::OLD_PRIVATE_KEY, file_get_contents( $private_key_file->url() ) );

		// tests if the old certificate file was untouched
		$certificate_file_path =
			Open_Badge_Factory_Credentials::CREDENTIALS_FOLDER_NAME .
			DIRECTORY_SEPARATOR . Open_Badge_Factory_Credentials::CLIENT_CERTIFICATE_FILE_NAME;
		$this->assertTrue( $plugin_folder_object->hasChild( $certificate_file_path ) );

		$certificate_file = $plugin_folder_object->getChild( $certificate_file_path );
		$this->assertEquals( self::OLD_CLIENT_CERTIFICATE, file_get_contents( $certificate_file->url() ) );
	}

	public function test_save_new_credentials_with_existing_client_id_no_credential_files_and_failed_client_id_update() {
		$this->mock_get_option_client_id_success( "+2" );

		$credentials = new Open_Badge_Factory_Credentials( $this->root->url() );

		$this->mock_update_option_client_id_fail( self::NEW_CLIENT_ID );

		$is_save_successful = $credentials->save_new_credentials( self::NEW_CLIENT_ID, self::NEW_PRIVATE_KEY, self::NEW_CLIENT_CERTIFICATE );

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
		$this->mock_get_option_client_id_success( 1 );
		$this->mock_get_option_client_id_success( 1, self::NEW_CLIENT_ID );

		$plugin_folder_object = $this->root->getChild( self::TEST_FOLDER );
		$private_key_path =
			Open_Badge_Factory_Credentials::CREDENTIALS_FOLDER_NAME .
			DIRECTORY_SEPARATOR . Open_Badge_Factory_Credentials::PRIVATE_KEY_FILE_NAME;

		var_dump( $plugin_folder_object->getChild( $private_key_path )->getPermissions() );

		$credentials = new Open_Badge_Factory_Credentials( $plugin_folder_object->url() );

		$this->mock_update_option_client_id_success( self::NEW_CLIENT_ID );

		$is_save_successful = $credentials->save_new_credentials( self::NEW_CLIENT_ID, self::NEW_PRIVATE_KEY, self::NEW_CLIENT_CERTIFICATE );

		$this->assertFalse( $is_save_successful );

		// test the private key file was untouched

		$this->assertTrue( $plugin_folder_object->hasChild( $private_key_path ) );

		$private_key_file = $plugin_folder_object->getChild( $private_key_path );
		$this->assertEquals( self::OLD_PRIVATE_KEY, file_get_contents( $private_key_file->url() ) );

		// tests if the old certificate file was untouched
		$certificate_file_path =
			Open_Badge_Factory_Credentials::CREDENTIALS_FOLDER_NAME .
			DIRECTORY_SEPARATOR . Open_Badge_Factory_Credentials::CLIENT_CERTIFICATE_FILE_NAME;
		$this->assertTrue( $plugin_folder_object->hasChild( $certificate_file_path ) );

		$certificate_file = $plugin_folder_object->getChild( $certificate_file_path );
		$this->assertEquals( self::OLD_CLIENT_CERTIFICATE, file_get_contents( $certificate_file->url() ) );
	}

	public function test_save_new_credentials_with_existing_credentials_and_failed_certificate_file_creation() {
		$this->mock_get_option_client_id_success( 1 );
		$this->mock_get_option_client_id_success( 1, self::NEW_CLIENT_ID );

		$plugin_folder_object = $this->root->getChild( self::POPULATED_UNWRITEABLE_PLUGIN_FOLDER );
		// make the private key file writable because we are not testing for a failed write to private key file
		$private_key = $plugin_folder_object->getChild( Open_Badge_Factory_Credentials::CREDENTIALS_FOLDER_NAME )->getChild( Open_Badge_Factory_Credentials::PRIVATE_KEY_FILE_NAME );
		$private_key->chown( vfsStream::OWNER_ROOT );
		$private_key->chmod( 0755 );

		$credentials = new Open_Badge_Factory_Credentials( $plugin_folder_object->url() );

		$this->mock_update_option_client_id_success( self::NEW_CLIENT_ID );

		$is_save_successful = $credentials->save_new_credentials( self::NEW_CLIENT_ID, self::NEW_PRIVATE_KEY, self::NEW_CLIENT_CERTIFICATE );

		$this->assertFalse( $is_save_successful );

		// test the private key file was changed, this should change because it's saved before the unwritable certificate
		$private_key_path =
			Open_Badge_Factory_Credentials::CREDENTIALS_FOLDER_NAME .
			DIRECTORY_SEPARATOR . Open_Badge_Factory_Credentials::PRIVATE_KEY_FILE_NAME;
		$this->assertTrue( $plugin_folder_object->hasChild( $private_key_path ) );

		$private_key_file = $plugin_folder_object->getChild( $private_key_path );
		$this->assertEquals( self::NEW_PRIVATE_KEY, file_get_contents( $private_key_file->url() ) );

		// tests if the old certificate file was untouched
		$certificate_file_path =
			Open_Badge_Factory_Credentials::CREDENTIALS_FOLDER_NAME .
			DIRECTORY_SEPARATOR . Open_Badge_Factory_Credentials::CLIENT_CERTIFICATE_FILE_NAME;
		$this->assertTrue( $plugin_folder_object->hasChild( $certificate_file_path ) );

		$certificate_file = $plugin_folder_object->getChild( $certificate_file_path );
		$this->assertEquals( self::OLD_CLIENT_CERTIFICATE, file_get_contents( $certificate_file->url() ) );
	}
}