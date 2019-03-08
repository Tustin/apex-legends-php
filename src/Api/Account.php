<?php

namespace ApexLegends\Api;

use ApexLegends\Client;

class Account extends AbstractApi 
{
	const ACCOUNT_ENDPOINT = 'https://gateway.ea.com/proxy/identity/pids/me';

	private $account;

	public function __construct(Client $client) 
	{
		parent::__construct($client);
	}

	/**
	 * Get EA account data.
	 *
	 * @return object Account data.
	 */
	public function data() : object
	{
		if ($this->account === null)
		{
			$this->account = $this->get(self::ACCOUNT_ENDPOINT)->pid;
		}
		
		return $this->account;
	}

	/**
	 * Get EA account's user ID.
	 *
	 * @return int User ID (-1 if error)
	 */
	public function id() : int
	{
		return (int)$this->data()->pidId ?? -1;
	}
}