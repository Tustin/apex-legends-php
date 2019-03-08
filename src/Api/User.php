<?php

namespace ApexLegends\Api;

use ApexLegends\Client;

class User extends AbstractApi 
{
	const USER_ID_SEARCH_URL = 'https://api1.origin.com/atom/users';

    private $account;
    private $userId;

    public function __construct(Client $client, int $userId) 
    {
        parent::__construct($client);

        $this->userId = $userId;
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
			$this->account = $this->get(self::USER_ID_SEARCH_URL, [
                'userIds' => $this->id()
            ]);
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
		return $this->userId;
	}
}