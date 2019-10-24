<?php
/**
 * Contains the Open_Badge_Factory_Credentials class.
 */
namespace appsaloon\obwp\external_apis\openbadgefactory;

/**
 * Class Open_Badge_Factory_Credentials
 *
 * Handles all file operations on the credentials needed to communicate with the OpenBadgeFactory api.
 * Credentials consist of a matching private.key and certificate.pem file.
 *
 * Saving and retrieving the client id for the account on OpenBadgeFactory is also handled in this class.
 *
 * @package appsaloon\obwp\external_apis\openbadgefactory
 */

class Open_Badge_Factory_Credentials {
	/**
	 * Name of the folder where the credentials are stored.
	 */
	const CREDENTIALS_FOLDER_NAME = 'obf-credentials';

	/**
	 * The file permissions in octal notation for the credential files.
	 */
	const CREDENTIALS_FOLDER_FILE_PERMISSIONS = 0755;

	/**
	 * The name of the private key file
	 */
	const PRIVATE_KEY_FILE_NAME = 'private.key';

	/**
	 * The name of the certificate file
	 */
	const CLIENT_CERTIFICATE_FILE_NAME = 'certificate.pem';

	/**
	 * Option name for saving and retrieving the client id for the account on OpenBadgeFactory in and from the DB.
	 */
	const CLIENT_ID_OPTION_NAME = 'obwp_obf_client_id';

	/**
	 * Option name for saving and retrieving the timestamp of the last time credentials were created, in and from the DB.
	 */
	const CREDENTIALS_SAVED_TIMESTAMP_OPTION_NAME = 'obwp_obf_credentials_created_at';

	/**
	 * Path to the folder where the credential files are stored.
	 *
	 * @var string
	 */
	protected $credentials_path;

	/** Path to the private key file
	 *
	 * @var string
	 */
	protected $private_key_path;

	/**
	 * Path to the certificate file
	 *
	 * @var string
	 */
	protected $certificate_path;

	/**
	 * The client id for the account on OpenBadgeFactory
	 *
	 * @var mixed|void
	 */
	protected $client_id;

	/**
	 * Open_Badge_Factory_Credentials constructor.
	 *
	 * @param string $plugin_path
	 * @throws \Exception
	 *
	 * @since 1.0.5
	 */
	public function __construct( $plugin_path ) {
		// TODO: maybe move code so the constructor can't throw an exception
		$this->credentials_path = $plugin_path . DIRECTORY_SEPARATOR . static::CREDENTIALS_FOLDER_NAME;
		$this->ensure_credentials_directory_exists();

		$this->private_key_path = $this->credentials_path . DIRECTORY_SEPARATOR . static::PRIVATE_KEY_FILE_NAME;
		$this->certificate_path = $this->credentials_path . DIRECTORY_SEPARATOR . static::CLIENT_CERTIFICATE_FILE_NAME;
		$this->client_id = $this->get_client_id();
	}

	/**
	 * Checks if the folder for the credentials exists and creates it if it does not.
	 * Throws an exception when the folder can not be created.
	 *
	 * @throws \Exception
	 *
	 * @since 1.0.5
	 */
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

	/**
	 * Saves the given credentials and logs the time if successful.
	 * Returns true on success, false on failure.
	 *
	 * @param string $client_id
	 * @param string $private_key
	 * @param string $certificate
	 *
	 * @return bool
	 *
	 * @since 1.0.5
	 */
	public function save_new_credentials( $client_id, $private_key, $certificate ) {
		if( $this->save_client_id( $client_id ) && $this->save_private_key( $private_key )
			&& $this->save_certificate( $certificate ) ) {
			$this->update_credentials_saved_timestamp();
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Saves the private key string to the private key file.
	 * Returns true on success, false on failure.
	 *
	 * @param string $private_key
	 *
	 * @return bool
	 *
	 * @since 1.0.5
	 */
	private function save_private_key($private_key ) {
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

	/**
	 * Returns the path to the private key file
	 *
	 * @return string
	 *
	 * @since 1.0.5
	 */
	public function get_private_key_path() {
		return $this->private_key_path;
	}

	/**
	 * Saves the certificate string to the certificate file.
	 * Returns true on success, false on failure.
	 *
	 * @param string $certificate
	 *
	 * @return bool
	 *
	 * @since 1.0.5
	 */
	private function save_certificate($certificate ) {
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

	/**
	 * Returns the path to the certificate path
	 *
	 * @return string
	 *
	 * @since 1.0.5
	 */
	public function get_certificate_path() {
		return $this->certificate_path;
	}

	/**
	 * Saves the client id to the database.
	 * Returns true on success, false on failure.
	 *
	 * @param $client_id
	 *
	 * @return bool
	 *
	 * @since 1.0.5
	 */
	private function save_client_id($client_id ) {
		update_option( static::CLIENT_ID_OPTION_NAME, $client_id );

		if( $this->get_client_id() == $client_id ) {
			$this->client_id = $client_id;
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Returns the client id that is stored in the DB
	 *
	 * @return string
	 *
	 * @since 1.0.5
	 */
	public function get_client_id() {
		// TODO Add error handling for this function in consumers
		return get_option( static::CLIENT_ID_OPTION_NAME );
	}

	/**
	 * Saves the current time as a timestamp to DB as CREDENTIALS_SAVED_TIMESTAMP_OPTION_NAME.
	 * Returns true on success, false on failure.
	 *
	 * @return bool
	 *
	 * @since 1.0.5
	 */
	private function update_credentials_saved_timestamp() {
		return update_option( static::CREDENTIALS_SAVED_TIMESTAMP_OPTION_NAME,
			current_time( 'timestamp', 1 ) );
	}

	/**
	 * Gets the timestamp of the last time credentials were saved or updated as a formatted string.
	 * The returned format is Y-m-d H:i:s
	 *
	 * @return string
	 *
	 * @since 1.0.5
	 */
	public static function get_formatted_credentials_saved_timestamp() {
		$time = get_option( static::CREDENTIALS_SAVED_TIMESTAMP_OPTION_NAME, 'not found' );

		if( is_numeric( $time ) ) {
			return date_i18n( 'Y-m-d H:i:s', $time );
		} else {
			return 'No date found';
		}
	}


}
