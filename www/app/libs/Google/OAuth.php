<?php

namespace App\Google;

use Nette,
	Nette\Utils\Strings,
	Nette\Http\Session,
	Nette\Security\Passwords;


class OAuth
{
	private $client;
	private $crypto;
	private $jwt;
	private $session;

	public function __construct( $credentials_file, $crypto, $jwt, Session $session ) {
		$client = new \Google_Client();
		$client->setAuthConfigFile( $credentials_file );
		$client->addScope( "openid email" );
		$client->setHostedDomain( "company.com" );
		$this->client = $client;

		$this->crypto = $crypto;

		$this->jwt = $jwt;

		$this->session = $session->getSection(__CLASS__);
	}

	public function getAuthUrl( $params = NULL ) {
		if( ! is_array( $params ) ){
			$params = array();
		}
		$csrfToken = md5(mt_rand());
		$this->session->csrfToken = $csrfToken;
		$params['csrf'] = $csrfToken;
		$this->client->setState( $this->crypto->encryptArray( $params ) );

		return $this->client->createAuthUrl();
	}

	public function authenticate( $code, $state ) {

		$this->client->authenticate( $_GET[ 'code' ] );
		$access_token = json_decode( $this->client->getAccessToken(), FALSE );
		$id_token = $access_token->id_token;
		$decoded = $this->jwt->decode( $id_token );
		$email = $decoded->email;

		$params = $this->crypto->decryptArray( $state );
		if( !isset( $params[ 'csrf' ] ) || $params[ 'csrf' ] != $this->session->csrfToken ) {
			throw new Nette\Security\AuthenticationException( 'Invalid CSRF token' );
		}
		unset( $params[ 'csrf' ] );

		return array( 'email' => $email, 'params' => $params );
	}
}
