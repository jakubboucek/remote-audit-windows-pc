<?php

namespace App;

use \Crypto as CryptoDefuse,
	\Nette\Utils\Json;

class Crypto
{
	private $key;

	public function __construct( $key ) {
		$this->key = hex2bin( $key );
	}

	public function encrypt( $plaintext ) {
		return CryptoDefuse::Encrypt( $plaintext, $this->key );
	}

	public function decrypt( $ciphertext ) {
		return CryptoDefuse::Decrypt( $ciphertext, $this->key );
	}

	public function encryptArray( $plainArray ) {
		$json = Json::encode( $plainArray );
		$cipher = self::encrypt( $json );
		return base64_encode( $cipher );
	}

	public function decryptArray( $ciphertext ) {
		$cipher = base64_decode( $ciphertext );
		$json = self::decrypt( $cipher );
		return Json::decode( $json, Json::FORCE_ARRAY );
	}
}
