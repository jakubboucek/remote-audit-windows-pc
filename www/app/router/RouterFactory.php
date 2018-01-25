<?php

namespace App;

use Nette,
	Nette\Application\Routers\RouteList,
	Nette\Application\Routers\Route,
	Nette\Application\Routers\SimpleRouter;


/**
 * Router factory.
 */
class RouterFactory
{

	/**
	 * @return \Nette\Application\IRouter
	 */
	public static function createRouter()
	{
		Route::$defaultFlags = Route::SECURED;

		$router = new RouteList();
		$router[] = new Route('sw/signed', 'Homepage:signed');
		$router[] = new Route('sw/done', 'Homepage:done');
		$router[] = new Route('sw/notrack', 'Homepage:noTrack');
		$router[] = new Route('office/<action>', 'Office:default');
		$router[] = new Route('ezs/<action>', 'EzsCodes:default');
		$router[] = new Route('sw/<presenter>/<action>[/<id>]', 'Homepage:default');
		$router[] = new Route('', 'Homepage:default', Route::ONE_WAY);
		return $router;
	}

}
