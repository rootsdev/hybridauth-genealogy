<?php

	session_start(); 

	// change the following paths if necessary 
	$config = dirname(__FILE__) . '/../../hybridauth/config.php';
	require_once( "../../hybridauth/Hybrid/Auth.php" );

	$error = "";
	$user_data = NULL;	
	
	// A simple error function for displaying unexpected errors
	function showError($e) {
		switch( $e->getCode() ){ 
			case 0 : $error = "Unspecified error."; break;
			case 1 : $error = "Hybriauth configuration error."; break;
			case 2 : $error = "Provider not properly configured."; break;
			case 3 : $error = "Unknown or disabled provider."; break;
			case 4 : $error = "Missing provider application credentials."; break;
			case 5 : $error = "Authentification failed. The user has canceled the authentication or the provider refused the connection."; break;
			case 6 : $error = "User profile request failed. Most likely the user is not connected to the provider and he should to authenticate again."; 
				     $adapter->logout(); 
				     break;
			case 7 : $error = "User not connected to the provider."; 
				     $adapter->logout(); 
				     break;
		} 
		
		// well, basically your should not display this to the end user, just give him a hint and move on..
		$error .= "<br /><br /><b>Original error message:</b> " . $e->getMessage(); 
		$error .= "<hr /><pre>Trace:<br />" . $e->getTraceAsString() . "</pre>";
	}


	try{
		// create an instance for Hybridauth with the configuration file path as parameter
		$hybridauth = new Hybrid_Auth( $config );
	} catch( Exception $e ){
		showError($e);
	}


	// Trigger to logout one service or all at once
	if (isset($_GET['logout'])) {
		if ($_GET['logout'] == "all") {
			$hybridauth->logoutAllProviders(); 
		} else {
			$adapter = $hybridauth->getAdapter( $_GET['logout'] );
			$adapter->logout(); 
		}
	}

	// if user select a provider to login with
		// then inlcude hybridauth config and main class
		// then try to authenticate te current user
		// finally redirect him to his profile page
	if( isset( $_GET["provider"] ) && $_GET["provider"] ):
		try{
			// set selected provider name 
			$provider = @ trim( strip_tags( $_GET["provider"] ) );

			// try to authenticate the selected $provider
			$adapter = $hybridauth->authenticate( $provider );

			// grab the user profile
			$user_data = $adapter->getUserProfile();		
		} catch( Exception $e ){
			showError($e);
		}
    endif;
    
    // Grab the currently connected providers (after we may logout one)
	try{
		$providers = $hybridauth->getProviders();		
	} catch( Exception $e ){
		showError($e);
	}    
    
?>
<? include('includes/header.inc') ?>

<? 
if (!empty($user_data)) :
	include('includes/profile.inc');
endif;
 ?>


<? include('includes/footer.inc') ?>