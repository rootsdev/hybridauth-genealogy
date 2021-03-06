<?php

/**
 * PHP FamilySearch
 * A class to communicate with the FamilySearch API
 */
class FamilySearch
{
	// URLs that we use
	const API_OAUTH_URL = 'https://api.familysearch.org';
	const OAUTH_URL = 'https://ident.familysearch.org/cis-web/oauth2/v3/authorization?';
	const OAUTH_TOKEN_URL = 'https://ident.familysearch.org/cis-web/oauth2/v3/token';

	// current version
	const VERSION = '2.0';


	/**
	 * The registered client id of the app
	 *
	 * @var string
	 */
	private $clientId;


	/**
	 * The registered client secret of the app
	 *
	 * @var string
	 */
	private $clientSecret;


	/**
	 * The access token to make calls
	 *
	 * @var string
	 */
	private $accessToken;


	/**
	 * Timeout
	 *
	 * @var int
	 */
	private $timeout = 50;


	/**
	 * The registered redirect uri of the app
	 *
	 * @var string
	 */
	private $redirectURI;


	/**
	 * The user agent
	 *
	 * @var string
	 */
	private $userAgent;


	/**
	 * A cURL instance
	 *
	 * @var	resource
	 */
	private $cURL;


	/**
	 * Default Constructor
	 *
	 * @return	void
	 * @param	string $clientId		The registered client id of the FamilySearch app
	 * @param	string $clientSecret	The registered client secret of the FamilySearch app
	 * @param	string $redirectURI		The registered redirect uri of the FamilySearch app
	 * @param	string $accessToken		The received access token
	 */
	public function __construct($clientId, $clientSecret, $redirectURI, $accessToken = null)
	{
		$this->clientId = (string) $clientId;
		$this->clientSecret = (string) $clientSecret;
		$this->redirectURI = (string) $redirectURI;
		$this->accessToken = (string) $accessToken;
	}


	/**
	 * Default Destructor
	 *
	 * @return	void
	 */
	public function __destruct()
	{
		// shutdown connection
		if($this->cURL != null) curl_close($this->cURL);
	}


	/**
	 * Lets the user authenticate an app at the FamilySearch website
	 *
	 * @return	void
	 */
	public function authenticate()
	{
		//only redirect if FamilySearch hasn't given us a code
		if(!isset($_GET['code']))
		{
			//build query string
			$queryString = http_build_query(array('client_id' => $this->clientId, 'response_type' => 'code', 'redirect_uri' => $this->redirectURI));

			// redirect to FamilySearch authentication page
			header('Location: '. self::OAUTH_URL . $queryString);

			// exit
			exit();
		}
	}
	
    public function destroySession() {
    	$this->accessToken =null;
	    $this->user = 0;
    }     

	/**
	 * Make a curl api call to FamilySearch
	 *
	 * @return	array
	 * @param	string $URL			The url to make the call to
	 * @param	array $parameters	The parameters to send
	 * @param 	string $method		GET or POST call
	 */
	private function doAPICall($URL, array $parameters = null, $method = 'GET')
	{

		// redefine
		$URL = (string) $URL;
		$parameters = (array) $parameters;
		$parameters['dataFormat'] = "application/json";
		$method = (string) $method;

		// add access token to parameters
		$parameters['sessionId'] = $this->accessToken;

		// build querystring
		$queryString = http_build_query($parameters);


		// set options
		$options[CURLOPT_USERAGENT] = $this->getUserAgent();
		$options[CURLOPT_TIMEOUT] = $this->getTimeOut();
		$options[CURLOPT_RETURNTRANSFER] = true;
		// $options[CURLOPT_FOLLOWLOCATION] = true;
		$options[CURLOPT_SSL_VERIFYPEER] = false;
		$options[CURLOPT_SSL_VERIFYHOST] = true;
		$options[CURLOPT_HTTPHEADER] = array( 'X-FamilySearch-API-Key: ' . $this->clientId,
											  'Accept: application/json' );

		// method is post
		if($method == 'POST')
		{
			// set URL
			$options[CURLOPT_URL] = self::API_OAUTH_URL . $URL;

			// set post
			$options[CURLOPT_POST] = true;

			// set parameters
			$options[CURLOPT_POSTFIELDS] = $queryString;
		}

		// method is get -> set URL with parameters
		else $options[CURLOPT_URL] = self::API_OAUTH_URL . $URL . $queryString;



		// init
		if($this->cURL == null) $this->cURL = curl_init();

		// apply options
		curl_setopt_array($this->cURL, $options);

		// fetch data
		$data = curl_exec($this->cURL);



		// fetch errors
		$errorNumber = curl_errno($this->cURL);
		$errorMessage = curl_error($this->cURL);

		// error in call?
		if($errorNumber != '') throw new FamilySearchException($errorMessage, $errorNumber);

		// get data in assoc array
		$data = json_decode($data, true);

		// data = null?
		if($data === null) throw new FamilySearchException('Unknown error occured. FamilySearch returned null.');

		// error in data?
		if(array_key_exists('error', $data)) throw new FamilySearchException($data['error']);
		// return data
		return $data;
	}


	/**
	 * Make a curl token call
	 *
	 * @return	array
	 * @param	array $parameters		The parameters to send
	 */
	private function doTokenCall(array $parameters)
	{
		// build querystring
		$queryString = http_build_query($parameters);

		// set options
		$options[CURLOPT_USERAGENT] = $this->getUserAgent();
		$options[CURLOPT_TIMEOUT] = $this->getTimeOut();
		$options[CURLOPT_RETURNTRANSFER] = true;
		// $options[CURLOPT_FOLLOWLOCATION] = true;
		$options[CURLOPT_SSL_VERIFYPEER] = false;
		$options[CURLOPT_SSL_VERIFYHOST] = true;
		$options[CURLOPT_HTTPHEADER] = array('Accept: application/json');
		$options[CURLOPT_URL] = self::OAUTH_TOKEN_URL;
		$options[CURLOPT_POST] = true;
		$options[CURLOPT_POSTFIELDS] = $queryString;

		// init
		if($this->cURL == null) $this->cURL = curl_init();

		// apply options
		curl_setopt_array($this->cURL, $options);

		// fetch data
		$data = curl_exec($this->cURL);

		// fetch errors
		$errorNumber = curl_errno($this->cURL);
		$errorMessage = curl_error($this->cURL);

		// error?
		if($errorNumber != '') throw new FamilySearchException($errorMessage, $errorNumber);

		// return data in assoc array
		return json_decode($data, true);
	}


	/**
	 * Retrieve metadata for the user your application is authorized as.
	 *
	 * @return	array
	 */
	public function getMe()
	{
		$data = $this->doAPICall('/identity/v2/user?');
		$data['person'] = $this->doAPICall('/familytree/v2/person?');
		// return info
		return $data;
	}


	/**
	 * Get the timeout
	 *
	 * @return	int
	 */
	public function getTimeout()
	{
		return $this->timeout;
	}


	/**
	 * Retrieve information about a specific user
	 *
	 * @return	array
	 * @param	string $userId		The id of the user (username) to get the info for
	 */
	public function getUser($userId)
	{
		// redefine
		$userId = (string) $userId;

		// return info
 		return $this->doAPICall('users/'. $userId .'/?');
	}


	/**
	 * Get the user agent
	 *
	 * @return	string
	 */
	public function getUserAgent()
	{
		return (string) 'PHP FamilySearch '. self::VERSION .' '. $this->userAgent;
	}


	/**
	 * Get a new acccess token in exchange for an expired token
	 *
	 * @return	array
	 * @param	string $accessToken		The current access token
	 * @param	string $refreshToken	The refresh token
	 */
	public function refreshToken($accessToken, $refreshToken)
	{
		// redefine
		$accessToken = (string) $accessToken;
		$refreshToken = (string) $refreshToken;

		// build parameters
		$parameters = array('grant_type' => 'refresh_token',
							'refresh_token' => $refreshToken,
							'client_id' => $this->clientId,
							'client_secret' => $this->clientSecret);

		// return token information
		return $this->doTokenCall($parameters);
	}


	/**
	 * Request a new token from FamilySearch
	 *
	 * @return void
	 */
	public function requestToken($code)
	{
		// check if code param is set
		if(isset($code))
		{
			// code from FamilySearch
			$code = (string) $code;

			// build parameters
			$parameters = array('grant_type' => 'authorization_code',
		  		  				'client_id' => $this->clientId,
				  				'client_secret' => $this->clientSecret,
				  				'code' => $code);

			// return token information
			return $this->doTokenCall($parameters);
		}

		// code param isn't set
		else throw new FamilySearchException('Can not get token from FamilySearch. No code parameter provided.');
	}


	/**
	 * Set the timeout
	 *
	 * @return	void
	 * @param	int		The timeout
	 */
	public function setTimeOut($timeout)
	{
		$this->timeout = (int) $timeout;
	}


	/**
	 * Set the user agent
	 *
	 * @return	void
	 * @param	string $userAgent	The user agent
	 */
	public function setUserAgent($userAgent)
	{
		$this->userAgent = (string) $userAgent;
	}
}


/**
 * FamilySearch Exception class
 *
 * @author	Lester Lievens <lievens.lester@gmail.com>
 */
class FamilySearchException extends Exception { }

