<?php
/**
* HybridAuth
* 
* A Social-Sign-On PHP Library for authentication through identity providers like Facebook,
* Twitter, Google, Yahoo, LinkedIn, MySpace, Windows Live, Tumblr, Friendster, OpenID, PayPal,
* Vimeo, Foursquare, AOL, Gowalla, and others.
*
* Copyright (c) 2009-2011 (http://hybridauth.sourceforge.net) 
*/

/**
 * Hybrid_Providers_Geni class, wrapper for Geni  
 */
class Hybrid_Providers_Geni extends Hybrid_Provider_Model
{ 
	var $redirect_uri = NULL;  
	
   /**
	* IDp wrappers initializer 
	*/
	function initialize() 
	{
		if ( ! $this->config["keys"]["key"] || ! $this->config["keys"]["secret"] )
		{
			throw new Exception( "Your application key and secret are required in order to connect to {$this->providerId}.", 4 );
		}

		require_once Hybrid_Auth::$config["path_libraries"] . "Geni/Geni.php";

		$this->redirect_uri = $this->endpoint . "&";

		// If we have an access token, we try to init the geni api with it
		if ( $this->token( "access_token" ) ){
            // inti geni api with the access_token we have
            $this->api = new Geni( $this->config["keys"]["key"], $this->config["keys"]["secret"], $this->redirect_uri, $this->token( "access_token" ) );

            // check if the access_token has expired, 
            if( $this->token( "expires_at" ) <= time() ){ 
                
                // if token has expired, then call Geni::refreshToken() to get new tokens
                $response = $this->api->refreshToken( $this->token( "access_token" ), $this->token( "refresh_token" ) );

                // check if geni response is valid 
                if ( ! isset( $response["access_token"] ) ){
                    // set the user as disconnected at this point and throw an exception
                    $this->setUserUnconnected();

                    throw new Exception( "Authentification failed! Access token has expired and {$this->providerId} has returned an invalid refresh token.", 5 );
                }

                // store the new access token, refresh token and the access token expire time
                if (isset($response['access_token']))
	                $this->token( "access_token"  , $response['access_token']  );
                if (isset($response['refresh_token']))
	                $this->token( "refresh_token" , $response['refresh_token'] );
                if (isset($response['expires_in']))    
	                $this->token( "expires_in"    , $response['expires_in']    );           // OPTIONAL. The duration in seconds of the access token lifetime
                if (isset($response['expires_at']))                
                $this->token( "expires_at"    , strtotime( $response['expires_at'] )+ time() ); // if not provided by the social api, then it should be calculated: expires_at = now + expires_in

                // inti geni api with the new access_token
                $this->api = new Geni( $this->config["keys"]["key"], $this->config["keys"]["secret"], $this->redirect_uri, $this->token( "access_token" ) );
            }
		}

        // else we dont have an access token stored
        else{
            $this->api = new Geni( $this->config["keys"]["key"], $this->config["keys"]["secret"], $this->redirect_uri );
        }
	}

	/**
	* begin login step 
	*/
	function loginBegin()
	{
		// authenticate app
		$this->api->authenticate();
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
	* finish login step 
	*/ 
	function loginFinish()
	{ 
		$code  = @$_REQUEST['code'];

		$response = $this->api->requestToken( $code );

		if ( ! $response || ! isset( $response["access_token"] ) )
		{
			throw new Exception( "Authentification failed! {$this->providerId} returned an invalid access token.", 5 );
		}

        // set access token, refresh token and access token expire time
        $this->token( "scope"         , $response['scope']         );
        $this->token( "access_token"  , $response['access_token']  );
        $this->token( "refresh_token" , $response['refresh_token'] ); 
        $this->token( "expires_in"    , $response['expires_in']    );           // OPTIONAL. The duration in seconds of the access token lifetime
        $this->token( "expires_at"    , strtotime( $response['expires_at'] ) ); // if not provided by the social api, then it should be calculated: expires_at = now + expires_in

		// set user as logged in
		$this->setUserConnected();
 	}
	
   /**
	* load the user profile from the IDp api client 
	*/
	function getUserProfile()
	{
		$response = $this->api->getMe();

		if ( ! $response || ! isset( $response["id"] ) )
		{
			throw new Exception( "User profile request failed! {$this->providerId} returned an invalid response.", 6 );
		}
		$this->user->profile->identifier    = @ (string) $response["id"]; 
		$this->user->profile->firstName  	= @ (string) $response["first_name"]; 
		$this->user->profile->lastName  	= @ (string) $response["last_name"]; 
		$this->user->profile->displayName  	= trim( $this->user->profile->firstName . " " . $this->user->profile->lastName );

		$this->user->profile->profileURL = @ "http://geni.com/people/" . ( (string) $response["id"] ); 


		$this->user->profile->photoURL   	= @ (string) str_replace("s3.amazonaws.com/", "", $response["mugshot_urls"]['medium']); 
		$this->user->profile->photoURL = str_replace("https://", "http://", $this->user->profile->photoURL);

		$this->user->profile->gender        = @ $response["gender"];
		$this->user->profile->city          = @ $response["homeCity"];
		$this->user->profile->email         = @ $response["email"];
		$this->user->profile->language      = @ $response["language"];		

		$age  = date("Y") - $response["birth"]['date']['year'];
		$month_diff = date("m") - $response["birth"]['date']['month'];
		$day_diff   = date("d") - $response["birth"]['date']['day'];
	    if ($day_diff < 0 || $month_diff < 0)
      		$age--;
		$this->user->profile->age	        = @ $age;
		$this->user->profile->birthDay      = @ $response["birth"]['date']['day'];
		$this->user->profile->birthMonth    = @ $response["birth"]['date']['month'];
		$this->user->profile->birthYear     = @ $response["birth"]['date']['year'];				
		$this->user->profile->phone      	= @ "+".$response['phone_numbers'][0]['country_code']." (".$response['phone_numbers'][0]['area_code'].") ".$response['phone_numbers'][0]['number'];
		$this->user->profile->address       = @ $response["current_residence"]['city']." ".$response["current_residence"]['state'];
		$this->user->profile->country       = @ $response["current_residence"]['country'];
		$this->user->profile->city     		= @ $response["current_residence"]['city'];	
		$this->user->profile->region   		= @ $response["current_residence"]['state'];
		$this->user->profile->latitude   	= @ $response["current_residence"]['latitude'];		
		$this->user->profile->longitude  	= @ $response["current_residence"]['longitude'];				

		return $this->user->profile;
	}
}
