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