<?php

namespace App\Model;

use Nette;


/**
 * Licenses management.
 */
class EzsCodes extends Nette\Object
{
	const
		TABLE_NAME = 'ezscodes',
		COLUMN_ID = 'email',
		COLUMN_DATE = 'touched',
		COLUMN_TOKEN = 'token';


	/** @var Nette\Database\Context */
	private $database;


	public function __construct(Nette\Database\Context $database)
	{
		$this->database = $database;
	}


	/**
	 * Get user pass
	 * @return string
	 */
	public function find( $email )
	{
		$row = $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID, $email)->fetch();

		if (!$row) {
			return NULL;
		}

		return $row;
	}


	/**
	 * Adds new user.
	 * @param  string
	 * @param  string
	 * @return void
	 */
	public function touch( $email )
	{
		$token = $this->genToken();

		$this->database->table(self::TABLE_NAME)
		->where(self::COLUMN_ID, $email)
		->update(array(
			self::COLUMN_DATE => new \DateTime(),
			self::COLUMN_TOKEN => $token,
		));
		return $token;
	}

	private function genToken() {
		return bin2hex(openssl_random_pseudo_bytes(16));
	}

}
