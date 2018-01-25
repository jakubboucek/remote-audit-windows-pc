<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings,
	Nette\Security\Passwords,
	Nette\Caching\Cache;


class TokenCache extends Nette\Object
{
    public $cache;

	public function __construct( Nette\Caching\IStorage $storage ) {
		$this->cache = new Cache($storage, 'tokens');
	}

	public function getToken( $key ) {
		return $this->cache->load( $key );
	}

	public function setToken( $key, $value ) {
		$this->cache->save( $key, $value, array(
		    Cache::EXPIRE => '20 minutes',
		    Cache::SLIDING => TRUE,
		) );
	}
}