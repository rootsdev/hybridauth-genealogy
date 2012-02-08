<style type="text/css">
	#profile {
		clear: right;
		margin-left: 175px;			
	}
	#profile .pitem {		
		list-style: none;
		font-size: 1.15em;
		padding: 5px; margin: 3px 0;
		border: 1px solid #e8e8e8;
		background: #f6f6f6;
		white-space: pre;           /* CSS 2.0 */
		white-space: pre-wrap;      /* CSS 2.1 */
		white-space: pre-line;      /* CSS 3.0 */
		white-space: -pre-wrap;     /* Opera 4-6 */
		white-space: -o-pre-wrap;   /* Opera 7 */
		white-space: -moz-pre-wrap; /* Mozilla */
		white-space: -hp-pre-wrap;  /* HP Printers */
		word-wrap: break-word;      /* IE 5+ */
	}
	#profile .pitem:hover {		
		background: #ffffcc;
	}
	#profileImage {
		float: left !important; 
		border: 3px solid #ccc; 
		width: 150px !important; 
		clear: right;	
	}
</style>
		<section id="content" class="body">
			<article>
							<nav><ul>						
								<a style="float: right;"><a href="?logout=<?=$_GET['provider']?>">Logout of <?=$_GET['provider']?></a></a>
							</ul></nav>			
				<h2>Your <b><?=$_GET['provider']?></b> Profile:</h2>
							
				
				<img id="profileImage" src="<?=$user_data->photoURL?$user_data->photoURL:"img/noimage.png"?>" alt="" style="width: 150px;" /> 
				<ul id="profile">
				<? foreach ($user_data as $key=>$value) : ?>
					<? if (!empty($value)) : ?>
						<li class="pitem"><b><?=ucfirst($key)?></b>: <? if (strlen(strstr($value,"http"))>0) echo '<a href="'.$value.'">'; ?><?=$value?><? if (strlen(strstr($value,"http"))>0) echo '</a>'; ?></li>
					<? endif; ?>
				<? endforeach; ?>
				</ul>
			</article>
		</section>