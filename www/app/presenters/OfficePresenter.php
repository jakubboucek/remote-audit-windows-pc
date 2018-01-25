<?php

namespace App\Presenters;

use Nette;


/**
 * Homepage presenter.
 */
class OfficePresenter extends BasePresenter
{

	private $officeLicenses;

	public function __construct( \App\Model\OfficeLicenses $officeLicenses ) {
		$this->officeLicenses = $officeLicenses;
	}

	public function renderDefault( $touch = 0 )
	{
		/*$this->template->logged = $logged = $this->getUser()->isLoggedIn();

		if($logged) {
			$this->template->email = $email = $this->getUser()->id;
			$license = $this->template->license = $this->officeLicenses->find( $email );
			$this->template->alrearyTouched = $license && $license['touched'];
			$this->template->touch = $touch;
			if($license && $touch) {
				$this->officeLicenses->touch( $email );
			}
		}
		*/
	}

}
