<?php
	function ProcessTemplate($title, $metaTags, $leftMenuItems, $topMenuItems, $activeMode, $restictedModes, $login, $content, $serverStatus, $footer)
	{
		$result = "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">\n<html>\n<head>\n";
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
		/*$result .= "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">\n";
		$result .= "<link rel='stylesheet' type='text/css' href='ea2.css'>\n";*/
		//$result .= "<meta content=\"text/html; charset=windows-1251\" http-equiv=\"Content-Type\"> ";
		$result .= "<title>$title</title>\n";
		$result .= "</head>\n";
		$result .= "<body>";//  bgcolor=\"#0000FF80\" text=\"#FFCC99\" link=\"#FF9966\" vlink=\"#009900\" alink=\"#FFFFFF\">
		$result .= "
	<!-- Header -->
	<div id='login'>
		$login
		<br>
		<span id='status'>$serverStatus</span>
	</div>
	<!-- Menu -->
	<div id=\"menu-box\" class=\"cleaning-box\">
		<h3>Меню</h3>
		";
		foreach($topMenuItems as $k=>$v)
		{
			if($k == $activeMode)
				$result .= "<a href=\"index.php?mode=$k\" class=\"active\">$v</a> ";
			else
				$result .= "<a href=\"index.php?mode=$k\">$v</a> ";
		}
		$result .= "
	</div>
	<!-- Menu end -->";
		//$result .= "<ul>\n";
		foreach($leftMenuItems as $k=>$v)
		{
			if(in_array($k, $restictedModes))
			{
				$result .= "$v\n";
			}
			else
			{
				if($k == $activeMode)
					$result .= "<a href=\"index.php?mode=$k\">$v</a> ";
				else
					$result .= "<a href=\"index.php?mode=$k\">$v</a> ";
			}
		}
		//$result .= "</ul>\n";
		$result .= "<hr><h3>$title</h3>\n";
		$result .= "<div class=\"block\">$content</div>\n";
		//print_r($_COOKIE);
		//print_r($_SESSION);
		$result .= "
<hr>
	<!-- Footer -->
	<div id=\"footer\">
			<p>&copy; 2009 <b><a href='mailto:rius(a)mail.ru'>Rius</a></p>
	</div>
	<!-- Footer end -->";
		$result .= "</body></html>";

		//$result = iconv("utf-8", "windows-1251", $result);
		return $result;
	}
?>
