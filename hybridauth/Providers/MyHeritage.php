<?php
/**
* HybridAuth
* 
* A Social-Sign-On PHP Library for authentication through identity providers like MyHeritage,
* Twitter, Google, Yahoo, LinkedIn, MySpace, Windows Live, Tumblr, Friendster, OpenID, PayPal,
* Vimeo, Foursquare, AOL, Gowalla, and others.
*
* Copyright (c) 2009-2011 (http://hybridauth.sourceforge.net) 
*/


/**
 * Hybrid_Providers_MyHeritage class, wrapper for MyHeritage Connect   
 */
class Hybrid_Providers_MyHeritage extends Hybrid_Provider_Model
{
	// default permissions

	/**
	* IDp wrappers initializer 
	*/
	function initialize() 
	{
		if ( ! $this->config["keys"]["id"] || ! $this->config["keys"]["secret"] )
		{
			throw new Exception( "Your application id and secret are required in order to connect to {$this->providerId}.", 4 );
		}

		require_once Hybrid_Auth::$config["path_libraries"] . "MyHeritage/base_familygraph.php";
		require_once Hybrid_Auth::$config["path_libraries"] . "MyHeritage/familygraph.php";

		$this->api = new FamilyGraph( $this->config["keys"]["id"], $this->config["keys"]["secret"] ); 
	}

   /**
	* begin login step
	* 
	* simply call MyHeritage::require_login(). 
	*/
	function loginBegin()
	{
		// if we have extra perm
		if( isset( $this->config["scope"] ) && ! empty( $this->config["scope"] ) )
		{
			$this->scope = $this->scope . ", ". $this->config["scope"];
		}

		// get the login url 
		$url = $this->api->getLoginUrl( array( 'scope' => $this->scope, 'redirect_uri' => $this->endpoint ) );

		// redirect to MyHeritage
		Hybrid_Auth::redirect( $url ); 
	}

	/**
	* finish login step 
	*/
	function loginFinish()
	{ 

		// in case we get error_reason=user_denied&error=access_denied
		if ( isset( $_REQUEST['error'] ) && $_REQUEST['error'] == "access_denied" ){ 
			throw new Exception( "Authentification failed! The user denied your request.", 5 );
		}

		// try to get the UID of the connected user from fb, should be > 0 
		if ( ! $this->api->getUserId() ){
			throw new Exception( "Authentification failed! {$this->providerId} returned an invalide user id.", 5 );
		}

		// set user as logged in
		$this->setUserConnected();

		// try to detect the access token for MyHeritage
		foreach( $_SESSION as $k => $v ){ 
			if( strstr( $k, "FamilyGraph_" ) && strstr( $k, "_access_token" ) ){
				$this->token( "access_token", $v );
			}
		}
	}

   /**
	* logout
	*/
	function logout()
	{ 
		$this->api->destroySession();

		parent::logout();
	}
	

   /**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		// request user profile from fb api
		try{ 
			$data = $this->api->api('/me'); 
		}
		catch( FamilyGraphApiException $e ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an error: $e", 6 );
		} 

		// if the provider identifier is not recived, we assume the auth has failed
		if ( ! isset( $data["id"] ) )
		{ 
			throw new Exception( "User profile request failed! {$this->providerId} api returned an invalid response.", 6 );
		}

		# store the user profile.  
		$this->user->profile->identifier    = @ $data['id'];
		$this->user->profile->displayName   = @ $data['nickname'];
		$this->user->profile->firstName     = @ $data['first_name'];
		$this->user->profile->lastName     	= @ $data['last_name'];
		$photo = $this->api->api('/'.$data['personal_photo']['id']);
		$this->user->profile->photoURL      = $photo['url'];
		$this->user->profile->profileURL 	= @ $data['link']; 
		$this->user->profile->gender     	= @ $data['gender'];
		$this->user->profile->address       = @ $data['address'];		
		$this->user->profile->city          = @ $data['city'];
		$this->user->profile->zip           = @ $data['zip_code'];
		$this->user->profile->country       = @ $data['country_code'];	
		$this->user->profile->region        = @ $data['state_or_district'];						
		if( isset( $data['birth_date']['date'] ) ) {
			list($birthday_year, $birthday_month, $birthday_day) = @ explode('-', $data['birth_date']['date'] );
			$this->user->profile->birthDay      = $birthday_day;
			$this->user->profile->birthMonth    = $birthday_month;
			$this->user->profile->birthYear     = $birthday_year;
			$age  = date("Y") - $birthday_year;
			$month_diff = date("m") - $birthday_month;
			$day_diff   = date("d") - $birthday_day;
			if ($month_diff < 0 || $day_diff < 0)
				$age--;
			$this->user->profile->age	        = @ $age;
		}
		

		return $this->user->profile;
 	}
}
