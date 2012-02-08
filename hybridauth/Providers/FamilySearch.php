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
 * Hybrid_Providers_FamilySearch class, wrapper for FamilySearch  
 */
class Hybrid_Providers_FamilySearch extends Hybrid_Provider_Model
{ 
	var $redirect_uri = NULL;  
	
   /**
	* IDp wrappers initializer 
	*/
	function initialize() 
	{
		if ( ! $this->config["keys"]["key"])
		{
			throw new Exception( "Your application key is required in order to connect to {$this->providerId}.", 4 );
		}

		require_once Hybrid_Auth::$config["path_libraries"] . "FamilySearch/FamilySearch.php";

		$this->redirect_uri = $this->endpoint . "&";

		// If we have an access token, we try to init the FamilySearch api with it
		if ( $this->token( "access_token" ) ){
            // inti FamilySearch api with the access_token we have
            $this->api = new FamilySearch( $this->config["keys"]["key"], $this->config["keys"]["secret"], $this->redirect_uri, $this->token( "access_token" ) );

            // check if the access_token has expired, 
            if( $this->token( "expires_at" ) <= time() ){ 
                
                // if token has expired, then call FamilySearch::refreshToken() to get new tokens
                $response = $this->api->refreshToken( $this->token( "access_token" ), $this->token( "refresh_token" ) );

                // check if FamilySearch response is valid 
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

                // inti FamilySearch api with the new access_token
                $this->api = new FamilySearch( $this->config["keys"]["key"], $this->config["keys"]["secret"], $this->redirect_uri, $this->token( "access_token" ) );
            }
		}

        // else we dont have an access token stored
        else{
            $this->api = new FamilySearch( $this->config["keys"]["key"], $this->config["keys"]["secret"], $this->redirect_uri );
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
        $this->token( "access_token"  , $response['access_token']  );
        $this->token( "refresh_token" , $response['refresh_token'] ); 
        if (empty($response['expires_in'])) // FamilySearch SHOULD return this by default. Oy.
        	$response['expires_in'] = time()+60*60;
        $this->token( "expires_in"    , $response['expires_in']    );           // OPTIONAL. The duration in seconds of the access token lifetime
        $this->token( "expires_at"    , ( $response['expires_in'] + time() ) ); // if not provided by the social api, then it should be calculated: expires_at = now + expires_in

		// set user as logged in
		$this->setUserConnected();
 	}
	
   /**
	* load the user profile from the IDp api client 
	*/
	function getUserProfile()
	{
						
		$response = $this->api->getMe();
		$person = $response['person']['persons'][0]['assertions'];
		
		if (!empty($person['names'][0]['value']['forms'][0]['pieces'])) :
			foreach ($person['names'][0]['value']['forms'][0]['pieces'] as $name) {
				if ($name['type'] == "Given") {
					if (!empty($given))
						$given .= " ";
					$given .= $name['predelimiters'].$name['value'].$name['postdelimiters'];			
				} else if ($name['type'] == "Family") {
					if (!empty($family))
						$family .= " ";
					$family .= $name['predelimiters'].$name['value'].$name['postdelimiters'];		
				}
		
			}
		endif;
		
		if ( empty($response) || ! isset( $response['users'][0]['id'] ) )
		{
			throw new Exception( "User profile request failed! {$this->providerId} returned an invalid response.", 6 );
		}

		
		foreach ($response['users'][0]['emails'] as $email) {
			if ($email['type'] == "Primary")
				$this->user->profile->email = @ $email['value'];
		}

		$this->user->profile->identifier  = @ $response['users'][0]['id'];
		$this->user->profile->displayName = @ $person['names'][0]['value']['forms'][0]['fullText'];
		$this->user->profile->firstName   = @ $given;
		$this->user->profile->lastName    = @ $family;
		$this->user->profile->profileURL  = @ "https://familysearch.org/tree/#view=ancestor&person=".$response['person']['persons'][0]['id']; 
		$this->user->profile->gender      = @ $person['genders'][0]['value']['type'];
		$this->user->profile->language      = @ $response['users'][0]['preferences'][0]['value'];
		
		

		return $this->user->profile;
	}
}
