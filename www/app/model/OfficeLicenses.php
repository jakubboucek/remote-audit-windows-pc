<?php

namespace App\Model;

use Nette;


/**
 * Licenses management.
 */
class OfficeLicenses extends Nette\Object
{
	const
		TABLE_NAME = 'officepasswords',
		COLUMN_ID = 'email',
		COLUMN_PASS = 'password',
		COLUMN_DATE = 'touched';


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
		//try {
			$this->database->table(self::TABLE_NAME)
			->where(self::COLUMN_ID, $email)
			->update(array(
				self::COLUMN_DATE => new \DateTime(),
			));
		//}
	}

}



class DuplicateNameException extends \Exception
{}
