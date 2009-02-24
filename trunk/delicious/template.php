<?php
	function ProcessTemplate($title, $metaTags, $leftMenuItems, $topMenuItems, $activeMode, $restictedModes, $login, $content, $serverStatus, $footer)
	{
		$result = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">
<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">
<head>

<!-- This template was created by Mantis-a [http://www.mantisa.cz/]. For more templates visit Free website templates [http://www.mantisatemplates.com/]. -->
";
/*<meta name=\"Description\" content=\"...\" />
<meta name=\"Keywords\" content=\"...\" />
<meta name=\"robots\" content=\"all,follow\" />
<meta name=\"author\" content=\"...\" />
<meta name=\"copyright\" content=\"Mantis-a [http://www.mantisa.cz/]\" />

<meta http-equiv=\"Content-Script-Type\" content=\"text/javascript\" />

<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />
*/
		foreach($metaTags as $item)
		{
			$result .= $item . "\n";
		}
		$result .= "
<!-- CSS -->
<link rel=\"stylesheet\" href=\"delicious/css/style.css\" type=\"text/css\" media=\"screen, projection, tv\" />
<!--[if lte IE 6]><link rel=\"stylesheet\" type=\"text/css\" href=\"css/style-ie.css\" media=\"screen, projection, tv\" /><![endif]-->
<link rel=\"stylesheet\" href=\"css/style-print.css\" type=\"text/css\" media=\"print\" />

<link rel=\"shortcut icon\" href=\"favicon.ico\" type=\"image/x-icon\" />

<title>$title</title>
</head>

<body>
<div id=\"main\">

	<!-- Header -->
	<div id=\"header\">
		<div id=\"header-content\">
			<!-- Your website name  -->
			<h1><a href='#'><span>Legio Octae</span>'s EA Site</a></h1>
			<!-- Your website name end -->
			
				<!-- Your slogan -->
				<h2>Обработка и вывод данных EVE API</h2>
				<!-- Your slogan end -->
			
				<div id='login'>
					$login
					<br>
					<br>
					<span id='status'>$serverStatus</span>
				</div>";
/*			<!-- Search form -->	
				<form class=\"searching\" action=\"\">
				<fieldset>
					<label></label>
						<div id=\"picture-input\">
							<input type=\"text\" class=\"search\" onfocus=\"if(this.value==this.defaultValue)this.value=''\" 
							onblur=\"if(this.value=='')this.value=this.defaultValue\" value=\"Search&hellip;\" />
						</div>
							<input class=\"hledat\" type=\"image\" src=\"delicious/img/search-button.gif\" name=\"\" value=\"Search\" alt=\"Search\" />
				</fieldset>
				</form>
				<!-- Search form end -->
*/
		$result .= "</div>
	<div id=\"rss-block\"><a id=\"rss-icon\" href=\"#\">RSS</a></div>		
	</div>
	<!-- Header end -->
	
	<!-- Menu -->
	<div id=\"menu-box\" class=\"cleaning-box\">
	<a href=\"#skip-menu\" class=\"hidden\">Skip menu</a>
		<ul id=\"menu\">";
/*			<li><a href=\"#\" class=\"active\">Home</a></li>
			<li><a href=\"#\">About me</a></li>
			<li><a href=\"#\">My work</a></li>
			<li><a href=\"#\">Support</a></li>
			<li><a href=\"#\">Contact me</a></li>
*/
		foreach($topMenuItems as $k=>$v)
		{
			if($k == $activeMode)
				$result .= sprintf("<li><a href='index.php?mode=$k' class='active'>$v</a></li>");
			else
				$result .= sprintf("<li><a href='index.php?mode=$k'>$v</a></li>");
		}
		$result .= "</ul>
	</div>
	<!-- Menu end -->
	
<hr class=\"noscreen\" />

<div id=\"skip-menu\"></div>
	
	<div id=\"content\">
	
		<div id=\"content-box\">
		
			<!-- Left column -->
			<div id=\"content-box-in-right\">
				<div id=\"content-box-in-right-in\">
					<h3>$title</h3>
						<div class=\"block\">
						$content
						</div>";
						
/*					<h3>LOREM IPSUM DOLOR SIT AMET</h3>
					
					<div id=\"small-gallery\">
						<a href=\"#\"><img src=\"img/foto/01.jpg\" alt=\"Image 1\" width=\"96\" height=\"72\" /></a>
						<a href=\"#\"><img src=\"img/foto/02.jpg\" alt=\"Image 1\" width=\"96\" height=\"72\" /></a>
						<a href=\"#\"><img src=\"img/foto/03.jpg\" alt=\"Image 1\" width=\"96\" height=\"72\" /></a>
						<a href=\"#\"><img src=\"img/foto/04.jpg\" alt=\"Image 1\" width=\"96\" height=\"72\" /></a>
						<a href=\"#\"><img src=\"img/foto/05.jpg\" alt=\"Image 1\" width=\"96\" height=\"72\" /></a>
						<a href=\"#\"><img src=\"img/foto/06.jpg\" alt=\"Image 1\" width=\"96\" height=\"72\" /></a>
						<a href=\"#\"><img src=\"img/foto/07.jpg\" alt=\"Image 1\" width=\"96\" height=\"72\" /></a>
						<a href=\"#\"><img src=\"img/foto/08.jpg\" alt=\"Image 1\" width=\"96\" height=\"72\" /></a>
					</div>
*/
		$result .= "
				</div>
			</div>
			<!-- Left column end -->

<hr class=\"noscreen\" />
			
			<!-- Right column -->
			<div id=\"content-box-in-left\">
				<div id=\"content-box-in-left-in\">
					<h3>Меню</h3>
					<ul>";
/*						<li><a href=\"#\">Lorem ipsum</a></li>
						<li><a href=\"#\" class=\"active\">Lorem ipsum</a></li>
						<li><a href=\"#\">Lorem ipsum</a></li>
						<li><a href=\"#\">Lorem ipsum</a></li>
						<li><a href=\"#\">Lorem ipsum</a></li>
*/
		foreach($leftMenuItems as $k=>$v)
		{
			if(in_array($k, $restictedModes))
			{
				$result .= sprintf("<li class='disabled'>$v</li>");
			}
			else
			{
				if($k == $activeMode)
					$result .= sprintf("<li><a href='index.php?mode=$k' class='active'>$v</a></li>");
				else
					$result .= sprintf("<li><a href='index.php?mode=$k'>$v</a></li>");
			}
		}
		$result .= "</ul>";
/*
			<h3>Lorem ipsum</h3>
				<ul>
						<li><a href=\"#\">Lorem ipsum</a></li>
						<li><a href=\"#\">Lorem ipsum</a></li>
						<li><a href=\"#\">Lorem ipsum</a></li>
						<li><a href=\"#\">Lorem ipsum</a></li>
						<li><a href=\"#\">Lorem ipsum</a></li>
						<li><a href=\"#\">Lorem ipsum</a></li>
						<li><a href=\"#\">Lorem ipsum</a></li>
					</ul>
*/
		$result .= "
			</div>
			</div>
			<div class=\"cleaner\">&nbsp;</div>
			<!-- Right column end -->
		</div>
	</div>

<hr class=\"noscreen\" />
	
	<!-- Footer -->
	<div id=\"footer\">
			<p class=\"footer-left\">&copy; 2009 <b><a href='mailto:rius(a)mail.ru'>Rius</a></p>
			<p class=\"footer-right\">Modified <a href=\"http://free-templates.ru/template-view/178/\">Delicious 1</a> template<br>
			<a href=\"http://www.mantisatemplates.com/\">Free web templates</a>
			by <a href=\"http://www.mantisa.cz/\">Mantis-a</a></p>
	</div>
	<!-- Footer end -->

</div>
</body>
</html>";

		return $result;
	}
?>
