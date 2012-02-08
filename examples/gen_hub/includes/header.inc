
<!DOCTYPE html>
<html lang="es">
<head>
	<title>Connection Hub</title>
	<meta charset="utf-8" />
	
	<link rel="stylesheet" href="css/main.css" type="text/css" />

	<!--[if IE]>
		<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script><![endif]-->
	
	<!--[if lte IE 7]>
		<link rel="stylesheet" type="text/css" media="all" href="css/ie.css"/>
		<script src="js/IE8.js" type="text/javascript"></script><![endif]-->
		
	<!--[if lt IE 7]>
		<link rel="stylesheet" type="text/css" media="all" href="css/ie6.css"/><![endif]-->

</head>

<body id="index" class="home">
	<header id="banner" class="body">

			<? if( count( $providers ) ) : ?>
				<? foreach($providers as $key => $value) : ?>	
					<? if ($value['connected']) : ?>	
							<nav><ul>						
								<a style="float: right;"><a href="?logout=all">Logout of all Services</a></a>
							</ul></nav>
						<?
							break;
						endif;
					?>
				<? endforeach; ?>
			<? endif; ?>

		<h1><a href="./">Genealogy Hub <br /><strong>A one-stop shop for genealogy site authentication.</strong></a></h1>
		
	</header><!-- /#banner -->
	
	<? if( isset( $_GET["error"] ) || $error ) : ?>
		<section id="content" class="body error">
			<article>
				<? 	if( $error ) : ?>
					<h2>Error! <?=$error?></h2>
					<pre>Session:<br /><?=print_r( $_SESSION, true )?></pre>
				<? else : ?>
					<h2><?=trim( strip_tags(  $_GET["error"] ) )?></h2>
				<? endif; ?>
			</article>
		</section>
	<? endif; ?>

	<aside id="featured" class="body"><article>
		<hgroup>
			<h2>Select a service to login to and view your profile.</h2>
			<h3>A green checkmark will appear if you are logged into any given service.</h3>
		</hgroup>
		
	<? if( !empty( $providers ) ) :	?>
		<ol id="posts-list" class="hfeed">		
			<? foreach($providers as $key => $value) : ?> 
				<li>
					<article>	
						<header>
							<a class="entry-title hentry <? if (!empty($value['connected'])) echo " connected"; ?>" href="?provider=<?=$key?>" rel="bookmark" title="<?=$key?>"><?=$key?></a>
						</header>
					</article>
				</li>	
			<? endforeach; ?>
		</ol>	
	<? endif; ?> 		
		
	</article></aside><!-- /#featured -->	
	</section><!-- /#content -->		