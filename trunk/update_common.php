<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>

<head>
	<title>Обновление общих таблиц</title>
	<meta http-equiv="content-type" content="text/html;charset=utf-8" />
	<meta name="generator" content="Geany 0.14" />
	<link rel="stylesheet" type="text/css" href="ea.css">
</head>

<body class='b-page'>
	<?php
		include_once "classes/api2.php";
		include_once "classes/constants.php";
		
		//$msg = "";
		echo "updating...<br>";
		$api = new ApiInterface("");
		$db = OpenDB2();

		$apires = $api->UpdateAlliances();
		$msg = $apires["message"] . "<br>";
		$str = $apires["message"] . ", ";

		$apires = $api->UpdateErrors();
		$msg .= $apires["message"] . "<br>";
		$str .= $apires["message"] . ", ";

		$apires = $api->UpdateFacWarTopStats();
		$msg .= $apires["message"] . "<br>";
		$str .= $apires["message"] . ", ";

		$apires = $api->UpdateOutposts();
		$msg .= $apires["message"] . "<br>";
		$str .= $apires["message"] . ", ";

		$apires = $api->UpdateRefTypes();
		$msg .= $apires["message"] . "<br>";
		$str .= $apires["message"] . ", ";

		$apires = $api->UpdateSovereignty();
		$msg .= $apires["message"] . "<br>";
		$str .= $apires["message"] . ", ";

		
		$db->query("insert into api_updater_log set _date_ = now(), message = '" . mysql_escape_string($str) . "';");
		
		$db->close();
		echo $msg;
		
	?>
</body>
</html>
