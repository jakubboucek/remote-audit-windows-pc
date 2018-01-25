<?php

namespace App\Presenters;

use Nette,
	App\Model;


/**
 * Homepage presenter.
 */
class HomepagePresenter extends BasePresenter
{

	private $currentVersion = '1.3';
	private $limitedSections;

	private $versions=array(
		//'1.0',
		//'1.1',
		'1.2',
		'1.3',
	);

	public function beforeRender( ) {
		$httpRequest = $this->getHttpRequest();
		$httpResponse = $this->getHttpResponse();

		$v = $httpRequest->getQuery('v');
		if( ! $v) {
			$v = $httpRequest->getCookie('audit_version');
		}
		if( $v && in_array($v, $this->versions)) {
			$this->currentVersion = $v;
			$httpResponse->setCookie('audit_version', $this->currentVersion, '10 min');
		}

		$s = $httpRequest->getQuery('s');
		if( ! $s) {
			$s = $httpRequest->getCookie('limited_sections');
		}
		if( $s && preg_match( '/^[a-z,]+$/', $s ) ) {
			$this->limitedSections = $s;
			$httpResponse->setCookie('limited_sections', $this->limitedSections, '10 min');
		}
	}

	public function renderDefault()
	{
		if( $this->getUser()->isLoggedIn() ) {
			$this->redirect('signed');
		}
	}

	public function renderSigned() {
		if( ! $this->getUser()->isLoggedIn() ) {
			$this->redirect('default');
		}

		$this->template->ver = '';
		if( $this->currentVersion ) {
			$this->template->ver = "-v" . $this->currentVersion;
		}
	}

	public function renderNoTrack() {
		setcookie('DoNotGaTrack', '1', time()+(60*60*24*7), '/sw/', $_SERVER['HTTP_HOST'], TRUE, FALSE);
		$this->flashMessage('DEVELOPER MODE: Your browser is now excluded from Analytics tracking');
		$this->redirect('Homepage:');
	}

}
