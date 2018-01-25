<?php

namespace App\Presenters;

use Nette,
	App\Model,
	App\Model\TokenCache;


/**
 * Homepage presenter.
 */
class AutomaticPresenter extends BasePresenter
{
	private $tokens;

	public function __construct( TokenCache $tokens ) {
		$this->tokens = $tokens;
	}

	public function startup() {
		parent::startup();
		if( ! $this->getUser()->isLoggedIn() ) {
			$this->flashMessage('Please sign up before use Audit application');
			$this->redirect('Homepage:default');
		}
	}


	public function renderRun( $token, $audit_version )
	{

		$httpRequest = $this->getHttpRequest();
		$limitedSection = $httpRequest->getCookie('limited_sections');

		$this->template->error = FALSE;

		if ( version_compare($audit_version, '1.2', '<') ) {
			$this->template->error = TRUE;
			return;
		}

		$data = $this->tokens->getToken( $token );

		$baseinfo = $_GET;
		unset($baseinfo['token']);

		if( !$data ) {
			$data = array(
				'user' => $this->getUser()->id,
				'baseinfo'=> $baseinfo,
				'limited_sections' => $limitedSection,
				'connected' => FALSE,
				'complete' => FALSE,
				'progress' => 0,
				'created' => time(),
				'finished' => NULL,
			);
		}

		$this->tokens->setToken( $token, $data );

		$this->template->token = $token;
	}

}
