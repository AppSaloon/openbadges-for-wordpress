<?php
namespace appsaloon\obwp\external_apis\openbadgefactory;

class Open_Badge_Factory_Credentials {
	const CREDENTIALS_FOLDER_NAME = 'obf-credentials';
	const CREDENTIALS_FOLDER_FILE_PERMISSIONS = 0755;
	const PRIVATE_KEY_FILE_NAME = 'private.key';
	const CLIENT_CERTIFICATE_FILE_NAME = 'certificate.pem';

	const CLIENT_ID_OPTION_NAME = 'obwp_obf_client_id';
	const CREDENTIALS_SAVED_TIMESTAMP_OPTION_NAME = 'obwp_obf_credentials_created_at';

	protected $credentials_path;
	protected $private_key_path;
	protected $certificate_path;
	protected $client_id;

	public function __construct( $plugin_path ) {
		$this->credentials_path = $plugin_path . DIRECTORY_SEPARATOR . static::CREDENTIALS_FOLDER_NAME;
		$this->ensure_credentials_directory_exists();

		$this->private_key_path = $this->credentials_path . DIRECTORY_SEPARATOR . static::PRIVATE_KEY_FILE_NAME;
		$this->certificate_path = $this->credentials_path . DIRECTORY_SEPARATOR . static::CLIENT_CERTIFICATE_FILE_NAME;
		$this->client_id = $this->get_client_id();
	}

	private function ensure_credentials_directory_exists() {
		if( ! file_exists( $this->credentials_path ) ) {
			$old_umask = umask(0);
			$directory_was_created = mkdir( $this->credentials_path, 0755 );
			umask( $old_umask );
			if( ! $directory_was_created ) {
				throw new \Exception( 'Can not write to directory: ' . $this->credentials_path );
			}
		}
	}

	public function save_new_credentials( $client_id, $private_key, $certificate ) {
		if( $this->save_client_id( $client_id ) && $this->save_private_key( $private_key )
			&& $this->save_certificate( $certificate ) ) {
			$this->update_credentials_saved_timestamp();
			return true;
		} else {
			return false;
		}
	}

	private function save_private_key( $private_key ) {
		if( file_exists( $this->private_key_path ) ) {
			if( is_writable( $this->private_key_path ) ) {
				return (bool) file_put_contents( $this->private_key_path, $private_key );
			} else {
				return false;
			}
		} else {
			return (bool) file_put_contents( $this->private_key_path, $private_key );
		}
	}

	public function get_private_key_path() {
		return $this->private_key_path;
	}

	private function save_certificate( $certificate ) {
		if( file_exists( $this->certificate_path ) ) {
			if( is_writable( $this->certificate_path ) ) {
				return (bool) file_put_contents( $this->certificate_path, $certificate );
			} else {
				return false;
			}
		} else {
			return (bool) file_put_contents( $this->certificate_path, $certificate );
		}
	}

	public function get_certificate_path() {
		return $this->certificate_path;
	}

	private function save_client_id( $client_id ) {
		update_option( static::CLIENT_ID_OPTION_NAME, $client_id );

		if( $this->get_client_id() == $client_id ) {
			$this->client_id = $client_id;
			return true;
		} else {
			return false;
		}
	}

	public function get_client_id() {
		return get_option( static::CLIENT_ID_OPTION_NAME );
	}

	private function update_credentials_saved_timestamp() {
		return update_option( static::CREDENTIALS_SAVED_TIMESTAMP_OPTION_NAME,
			current_time( 'timestamp', 1 ) );
	}

	public static function get_formatted_credentials_saved_timestamp() {
		$time = get_option( static::CREDENTIALS_SAVED_TIMESTAMP_OPTION_NAME, 'not found' );

		if( is_numeric( $time ) ) {
			return date_i18n( 'Y-m-d H:i:s', $time );
		} else {
			return 'No date found';
		}
	}


}
