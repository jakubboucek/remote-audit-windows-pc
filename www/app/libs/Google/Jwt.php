<?php

namespace App\Google;

class JWT {
	private $secret;

	public function __construct( $credentials_file ) {
		$json = file_get_contents( $credentials_file );
		$data = json_decode( $json );
		$key = isset($data->installed) ? 'installed' : 'web';
	    if (!isset($data->$key)) {
	      throw new Google_Exception("Invalid client secret JSON file.");
	    }
    	$this->secret = $data->$key->client_secret;
	}

	public function decode( $jwt ) {
		return \JWT::decode( $jwt, NULL, FALSE );
	}
}