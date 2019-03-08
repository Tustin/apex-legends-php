<?php
namespace ApexLegends;

use ApexLegends\Http\HttpClient;
use ApexLegends\Http\ResponseParser;
use ApexLegends\Http\TokenMiddleware;

use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

class Client
{
	private $httpClient;
	private $options;

	const LOGIN_FORM_URL = 'https://accounts.ea.com/connect/auth?client_id=ORIGIN_PC&response_type=code id_token&redirect_uri=qrc:///html/login_successful.html&display=originX/login&locale=en_US';
	const USER_SEARCH_URL = 'https://api2.origin.com/xsearch/users';

	/**
	 * Construct new Client
	 *
	 * @param array $options Guzzle options
	 */
	public function __construct(array $options = [])
	{
		$defaultOptions = ['cookies' => true];
		$this->options = array_merge($defaultOptions, $options);
		$this->httpClient = new HttpClient(new \GuzzleHttp\Client($this->options));
	}

	/**
	 * Login to Origin to obtain Origin userids.
	 *
	 * @param string $email Origin email
	 * @param string $password Origin password
	 * @return void
	 */
	public function login(string $email, string $password) 
	{
		$response = $this->httpClient()->get(self::LOGIN_FORM_URL);

		$locationUrl = $response->getHeader('Location')[0];

		if (!$locationUrl)
		{
			// Throw error
			die('bad location');
		}

		// This should set the session id (using the Guzzle cookie jar)
		$response = $this->httpClient()->get($locationUrl);

		$loginFormUrl = $response->getHeader('Location')[0];

		if (!$loginFormUrl)
		{
			die('no url for the login form found');
		}

		$loginFormUrl = 'https://signin.ea.com' . $loginFormUrl;    

		$response = $this->httpClient()->post($loginFormUrl, [
			'email' => $email,
			'password' => $password,
			'_eventId' => 'submit',
			'cid' => $this->generateCid(),
			'showAgeUp' => true, // ??
			'googleCaptchaResponse' => '',
			'_rememberMe' => 'on'
		]);

		// Parse out the redirect url because it's handled by javascript for some reason.
		// EA logic
		if (!preg_match('/window\.location = \"(\S*)"\;/', $response, $matches))
		{
			die('failed parsing window.location redirect');
		}

		$loginUrl = $matches[1];

		if (!$loginUrl)
		{
			die('bad login url from window.location');
		}

		// Please... my son... he's very sick
		$loginUrl .= '&nonce=2272';

		// More cookies!
		$response = $this->httpClient()->get($loginUrl);
		
		$loginSuccessfulUrl = $response->getHeader('Location')[0];
		
		if (!$loginSuccessfulUrl)
		{
			die('no successful url');
		}

		// Ugh, PHP why are you so inconsistent??
		$pieces = [];
		parse_str($loginSuccessfulUrl, $pieces);

		// This is really bad and I'm not too sure why parse_str parses it like this
		$code = $pieces["qrc:/html/login_successful_html#code"];

		$response = $this->httpClient()->post('https://accounts.ea.com/connect/token', [
			'grant_type' => 'authorization_code',
			'code' => $code,
			'client_id' => 'ORIGIN_PC',
			'client_secret' => 'UIY8dwqhi786T78ya8Kna78akjcp0s',
			'redirect_uri' => 'qrc:///html/login_successful.html'
		]);

		$this->accessToken = $response->access_token;
		$this->refreshToken = $response->refresh_token;
		$this->expiresIn = $response->expires_in;

		$handler = \GuzzleHttp\HandlerStack::create();
		$handler->push(Middleware::mapRequest(new TokenMiddleware($this->accessToken(), $this->refreshToken(), $this->expireDate())));

		$newOptions = array_merge(['handler' => $handler], $this->options);
	
		$this->httpClient = new HttpClient(new \GuzzleHttp\Client($newOptions));
	}

	/**
	 * Gets the HttpClient.
	 *
	 * @return HttpClient
	 */
	public function httpClient() : HttpClient
	{
		return $this->httpClient;
	}

	// Should this be a singleton??
	public function account()
	{
		return new Api\Account($this);
	}

	/**
	 * Searches Origin for a list of users.
	 *
	 * @param string $searchUsername Username to search for.
	 * @return array<Api\User> Array of User for each user.
	 */
	public function users(string $searchUsername) : array
	{
		$accountUserId = $this->account()->id();
		$returnUsers = [];

		$response = $this->httpClient()->get(self::USER_SEARCH_URL, [
			'userId' => $accountUserId,
			'searchTerm' => $searchUsername
		]);

		if ($response->totalCount === 0)
		{
			return $returnUsers;
		}

		foreach ($response->infoList as $foundUser)
		{
			$returnUsers[] = $this->user((int)$foundUser->friendUserId);
		}

		return $returnUsers;
	}

	/**
	 * Creates a new User object.
	 *
	 * @param integer $userId The user's EA user ID.
	 * @return Api\User User object.
	 */
	public function user(int $userId) : Api\User
	{
		return new Api\User($this, $userId);
	}

	/**
	 * Gets the access token.
	 *
	 * @return string
	 */
	public function accessToken() : string
	{
		return $this->accessToken;
	}

	/**
	 * Gets the refresh token.
	 *
	 * @return string
	 */
	public function refreshToken() : string
	{
		return $this->refreshToken;
	}

	/**
	 * Gets the access token expire DateTime.
	 *
	 * @return \DateTime
	 */
	public function expireDate() : \DateTime
	{
		return new \DateTime(sprintf('+%d seconds', $this->expiresIn));
	}
	
	/**
	 * Generates a random 32 char CID for login.
	 *
	 * @return string
	 */
	private function generateCid() : string
	{
		$chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		return substr(str_shuffle($chars), 0, 32);
	}
}