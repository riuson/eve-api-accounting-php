<?php
	function ProcessTemplate($title, $metaTags, $leftMenuItems, $topMenuItems, $activeMode, $restictedModes, $login, $content, $serverStatus, $footer)
	{
		$result = "<html>
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
<link rel=\"stylesheet\" href=\"minibrowser/style.css\" type=\"text/css\" media=\"screen, projection, tv\" />

<link rel=\"shortcut icon\" href=\"favicon.ico\" type=\"image/x-icon\" />

<title>$title</title>
</head>

<body>
<div id=\"main\">

	<!-- Header -->
	<div id='login'>
		$login
		<br>
		<span id='status'>$serverStatus</span>
	</div>
	<!-- Header end -->

	<!-- Menu -->
	<div id=\"menu-box\" class=\"cleaning-box\">
		<h3>Меню</h3>
		<ul id=\"menu\">";
		foreach($topMenuItems as $k=>$v)
		{
			if($k == $activeMode)
				$result .= sprintf("<li><a href=\"index.php?mode=$k\" class=\"active\">$v</a></li>");
			else
				$result .= sprintf("<li><a href=\"index.php?mode=$k\">$v</a></li>");
		}
		$result .= "</ul>
	</div>
	<!-- Menu end -->
	
	<!-- Right column -->
	<div id=\"content-box-in-left\">
			<ul id=\"menu\">";
		foreach($leftMenuItems as $k=>$v)
		{
			if(in_array($k, $restictedModes))
			{
				$result .= sprintf("<li class=\"disabled\">$v</li>");
			}
			else
			{
				if($k == $activeMode)
					$result .= sprintf("<li><a href=\"index.php?mode=$k\" class=\"active\">$v</a></li>");
				else
					$result .= sprintf("<li><a href=\"index.php?mode=$k\">$v</a></li>");
			}
		}
		$result .= "</ul>
	</div>
	<!-- Right column end -->
	
<hr class=\"noscreen\" />
	<div id=\"content\">
		<!-- Left column -->
		<div id=\"content-box-in-right-in\">
			<h3>$title</h3>
			<div class=\"block\">$content</div>
		</div>
		<!-- Left column end -->
	</div>

<hr class=\"noscreen\" />
	<!-- Footer -->
	<div id=\"footer\">
			<p class=\"footer-left\">&copy; 2009 <b><a href='mailto:rius(a)mail.ru'>Rius</a></p>
			<p class=\"footer-right\"><a href=\"http://www.mantisatemplates.com/\">Free web templates</a> 
			by <a href=\"http://www.mantisa.cz/\">Mantis-a</a></p>
	</div>
	<!-- Footer end -->

</div>
</body>
</html>";

		return $result;
	}
?>
