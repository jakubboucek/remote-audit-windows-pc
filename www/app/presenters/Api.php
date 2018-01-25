<?php

namespace App\Presenters;

use Nette,
	App\Model,
	App\Model\TokenCache,
	Nette\Application\Responses\JsonResponse;


/**
 * Homepage presenter.
 */
class ApiPresenter extends BasePresenter
{
	private $tokens;
	private $httpRequest;

	public function __construct( TokenCache $tokens, \Nette\Http\Request $httpRequest ) {
		$this->tokens = $tokens;
		$this->httpRequest = $httpRequest;
	}

	public function renderStatus( $token )
	{
		$token = $this->tokens->getToken( $token );

		$response = array(
			'status' => ( !! $token ),
			'token' => $token,
		);

		$this->sendResponse(new JsonResponse($response));
	}

	public function renderUpdate( $token )
	{
		$data = $this->tokens->getToken( $token );

		$httpRequest = $this->httpRequest;
		if($data) {
			if($httpRequest->getPost('progress')) {
				$data['progress'] = floatval($httpRequest->getPost('progress'));
			}
			if($httpRequest->getPost('complete')) {
				$data['complete'] = TRUE;
				$data['finished'] = time();
				$this->saveResultFile($token, $data);
			}
			$data['description'] = $httpRequest->getPost('description');
			$data['connected'] = TRUE;

			$this->tokens->setToken( $token, $data );
		}

		$response = array(
			'status' => ( !! $data ),
			'token' => $data,
		);

		$this->sendResponse(new JsonResponse($response));
	}

	private function saveResultFile( $token, $tokenData ) {
		$user = $tokenData['user'];
		$username = preg_replace('/@.+$/', '', $user);
		$outputDir = __DIR__.'/../../data/'.$username;
		if(! file_exists($outputDir)) mkdir($outputDir);

		$output = json_encode($tokenData, JSON_PRETTY_PRINT);
		file_put_contents($outputDir.'/token_'.$token.'.json', $output);
	}

	public function renderUpload( $token )
	{
		$data = $this->tokens->getToken( $token );
		try {
			$httpRequest = $this->httpRequest;
			if($data) {
				$user = $data['user'];
				$username = preg_replace('/@.+$/', '', $user);
				$outputDir = __DIR__.'/../../data/'.$username;
				if(! file_exists($outputDir)) mkdir($outputDir);

				if(!isset($_FILES['uploaded_file'])) {
					throw new \Exception('No uploaded_file');
				}
				if($_FILES['uploaded_file']['error']) {
					throw new \Exception('Error during upload files: '.$_FILES['uploaded_file']['error']);
				}

				$hash = substr(md5(time()), 0, 5) . '-';
				$filename = $hash . $_FILES['uploaded_file']['name'];
				$deflated = file_get_contents($_FILES['uploaded_file']['tmp_name']);
				$output = gzinflate( $deflated );
				file_put_contents($outputDir.'/'.$filename, $output);
				unset($output, $deflated);

				$data['files'][] = $filename;

				$this->tokens->setToken( $token, $data );
			}

			$response = array(
				'status' => ( !! $data ),
				'token' => $data,
			);

			$this->sendResponse(new JsonResponse($response));
		} catch( Exception $e ) {
			$this->sendResponse(new JsonResponse(array(
				'status' => FALSE,
				'token' => NULL,
				'error' => $e->getMessage(),
			)));
		}
	}

}
