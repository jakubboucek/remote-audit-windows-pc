<?php

namespace App\Presenters;

use Nette,
	Nette\Application\UI,
	Nette\Application\UI\Form;


/**
 * Homepage presenter.
 */
class EzsCodesPresenter extends BasePresenter
{

	private $ezsCodes;

	public function __construct( \App\Model\EzsCodes $ezsCodes ) {
		$this->ezsCodes = $ezsCodes;
	}

	public function renderDefault( $touch = NULL, $returned = NULL )
	{
		$this->template->logged = $logged = $this->getUser()->isLoggedIn();

		if($logged) {
			$this->template->email = $email = $this->getUser()->id;
			$code = $this->template->code = $this->ezsCodes->find( $email );
			if($touch && $touch != $code['token']) {
				throw new Nette\Application\ForbiddenRequestException();
			}
			$this->template->touch = $touch;
			$this->template->returned = $returned;
			if($code) {
				$this['codePickForm']['iam']->caption="Yes, i am $code[firstname] $code[lastname]";
			}
		}
	}

	public function createComponentCodePickForm() {
		$form = new UI\Form;
        // mohu použít $this->database

        $form->addCheckbox('iam')->addRule(Form::EQUAL, 'You must confirm your name', TRUE);
        $form->addCheckbox('accept_terms', 'I have read, understand and accept Terms & Conditions')->addRule(Form::EQUAL, 'You must confirm Terms', TRUE);
        $form->addProtection('Security issue: Please send it again');
        $form->addSubmit('send', 'Show my code');
        $form->onSuccess[] = array($this, 'acceptTerms');

        return $form;
	}

	public function acceptTerms( $form ) {
		$logged = $this->getUser()->isLoggedIn();
		if(!$logged) {
			throw new Nette\Application\ForbiddenRequestException();
		}
		$email = $this->getUser()->id;
		$code = $this->ezsCodes->find( $email );
		if(!$code) {
			throw new Nette\Application\BadRequestException();
		}
		if($code['touched']) {
			$this->redirect('default', array('touch' => $code['token'], 'returned' => 1));
		}
		else {
			$token = $this->ezsCodes->touch( $email );
			$this->redirect('default', array('touch' => $token));
		}
	}

}
