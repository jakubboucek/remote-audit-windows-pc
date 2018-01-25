<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings,
	Nette\Security\Passwords;


/**
 * Users management.
 */
class UserManager extends Nette\Object implements Nette\Security\IAuthenticator
{
	public function __construct()
	{
	}


	/**
	 * Performs an authentication.
	 * @return Nette\Security\Identity
	 * @throws Nette\Security\AuthenticationException
	 */
	public function authenticate(array $credentials)
	{
		list($email) = $credentials;

		if( ! preg_match('/.+@company\.com$/i', $email)) {
			throw new Nette\Security\AuthenticationException('The user e-mail is incorrect.', self::IDENTITY_NOT_FOUND);
		}

		return new Nette\Security\Identity($email);
	}
}
