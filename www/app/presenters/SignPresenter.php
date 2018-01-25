<?php

namespace App\Presenters;

use Nette,
	App\Google\OAuth;


class SignPresenter extends BasePresenter
{
	private $OAuth;

	public function __construct(OAuth $OAuth) {
		$this->OAuth = $OAuth;
	}

	public function actionIn( array $params = NULL ) {
		$url = $this->OAuth->getAuthUrl( $params );
		$this->redirectUrl( $url );
	}

	public function actionOauth2callback( $code, $state, $error ) {
		if( $code ) {
			try {
				$auth = $this->OAuth->authenticate( $code, $state );
				$email = $auth['email'];
				$params = $auth['params'];

				$this->getUser()->login($email);
				$this->getUser()->setExpiration('20 minutes', FALSE);
				//$this->flashMessage('You have been signed in.');

				if( isset( $params[ 'redirectNette' ] ) ) {
					$this->redirect( $params[ 'redirectNette' ], ( isset( $params[ 'redirectParams' ] ) ?: array() ) );
				}
				elseif( isset( $params[ 'redirectUrl' ] ) ) {
					$this->redirectUrl( $params[ 'redirectUrl' ] );
				}
				else {
					$this->redirect( 'Homepage:signed' );
				}

			} catch (Nette\Security\AuthenticationException $e) {
				$form->addError($e->getMessage());
			}
		}

	}

	public function actionOut( array $params = NULL )
	{
		$this->getUser()->logout();
		$this->flashMessage('You have been signed out.');

		if( isset( $params[ 'redirectNette' ] ) ) {
			$this->redirect( $params[ 'redirectNette' ], ( isset( $params[ 'redirectParams' ] ) ?: array() ) );
		}
		elseif( isset( $params[ 'redirectUrl' ] ) ) {
			$this->redirectUrl( $params[ 'redirectUrl' ] );
		}
		else {
			$this->redirect( 'Homepage:' );
		}
	}

}
