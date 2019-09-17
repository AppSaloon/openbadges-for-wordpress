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

	const UNWRITABLE_ROOT = 'unwritable_root';
	const POPULATED_PLUGIN_FOLDER = 'populated_plugin_folder';

	public function setUp() : void {
		WP_Mock::setUp();
		vfsStream::umask( 022 );

		$structure = array(
			self::UNWRITABLE_ROOT => array(),
			self::POPULATED_PLUGIN_FOLDER => array(
				Open_Badge_Factory_Credentials::CREDENTIALS_FOLDER_NAME => array(
					Open_Badge_Factory_Credentials::PRIVATE_KEY_FILE_NAME => self::OLD_PRIVATE_KEY,
					Open_Badge_Factory_Credentials::CLIENT_CERTIFICATE_FILE_NAME => self::OLD_CLIENT_CERTIFICATE,
				)
			)
		);

		$this->root = vfsStream::setup( 'testDirectory', null, $structure );
		$this->root->getChild( 'unwritable_root')->chmod( 0000 );
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
		new Open_Badge_Factory_Credentials( $this->root->getChild( self::UNWRITABLE_ROOT)->url() );
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


	public function test_save_new_credentials_without_existing_client_id_success() {
		$this->mock_get_option_client_id_fail();

		$credentials = new Open_Badge_Factory_Credentials( $this->root->url() );

		$this->mock_get_option_client_id_success( 2 , self::NEW_CLIENT_ID);
		$this->mock_update_option_client_id_success( self::NEW_CLIENT_ID );
		$this->mock_current_time();
		$this->mock_update_option_credentials_created_at();

		$is_save_successful = $credentials->save_new_credentials( self::NEW_CLIENT_ID, 'a private key', 'a certificate' );

		$this->assertTrue( $is_save_successful );
		$this->assertEquals( self::NEW_CLIENT_ID, $credentials->get_client_id() );

		// tests if the private file was created with the correct contents
		$private_key_path =
			Open_Badge_Factory_Credentials::CREDENTIALS_FOLDER_NAME .
			DIRECTORY_SEPARATOR . Open_Badge_Factory_Credentials::PRIVATE_KEY_FILE_NAME;
		$this->assertTrue( $this->root->hasChild( $private_key_path ) );

		$private_key_file = $this->root->getChild( $private_key_path );
		$this->assertEquals( 'a private key', file_get_contents( $private_key_file->url() ) );

		// tests if the certificate file was created with the correct contents

		$certificate_file_path =
			Open_Badge_Factory_Credentials::CREDENTIALS_FOLDER_NAME .
			DIRECTORY_SEPARATOR . Open_Badge_Factory_Credentials::CLIENT_CERTIFICATE_FILE_NAME;
		$this->assertTrue( $this->root->hasChild( $certificate_file_path ) );

		$certificate_file = $this->root->getChild( $certificate_file_path );
		$this->assertEquals( 'a certificate', file_get_contents( $certificate_file->url() ) );
	}
}