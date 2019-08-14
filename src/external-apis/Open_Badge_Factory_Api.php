<?php

namespace appsaloon\obfw\openbadgefactory;

class Open_Badge_Factory_Api {
    const OBF_PUBLIC_CERTIFICATE_URL = 'https://openbadgefactory.com/v1/client/OBF.rsa.pub';
    const OBF_BASE_URL_FOR_SIGNING_CLIENT_CERTIFICATE_REQUEST = 'https://openbadgefactory.com/v1/client/';
    const OBF_ROUTE_SUFFIX_FOR_CSR_REQUEST_SIGNING = '/sign_request';

    static function get_client_certificate( $api_token ) {
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

        // TODO: write $new_private_key to DB

        $csr_res = openssl_csr_new( array( 'commonName' => $decrypted_api_token->subject ), $private_key_res );

        openssl_csr_export( $csr_res, $csr_output );

        $url = static::OBF_BASE_URL_FOR_SIGNING_CLIENT_CERTIFICATE_REQUEST . $decrypted_api_token->id
            . static::OBF_ROUTE_SUFFIX_FOR_CSR_REQUEST_SIGNING;

        $json_data_for_csr_signing = json_encode( array(
            'signature' => $api_token,
            'request' => $csr_output
        ) );

        $result = wp_remote_post( $url, array( 'body' => $json_data_for_csr_signing) );
        echo wp_remote_retrieve_body( $result ); die;
    }
}