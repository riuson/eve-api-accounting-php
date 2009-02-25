<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>

<head>
	<title>Обновление industry jobs всех мастер-аккаунтов</title>
	<meta http-equiv="content-type" content="text/html;charset=utf-8" />
	<meta name="generator" content="Geany 0.14" />
	<link rel="stylesheet" type="text/css" href="ea.css">
</head>

<body class='b-page'>
	<?php
		include_once "classes/api2.php";
		include_once "classes/.config.php";
		
		$msg = "";
		echo "updating...<br>";
		$api = new ApiInterface("");
		$db = OpenDB2();
		$qr = $db->query("select * from api_users where master = '' and length(apiKey) > 20 and characterId > 0 and userId > 0;");
		while($rowUser = $qr->fetch_assoc())
		{
			$accountId = $rowUser["accountId"];
			$userId = $rowUser["userId"];
			$apiKey = RC4($dcapicode, base64_decode($rowUser["apiKey"]));
			$characterId = $rowUser["characterId"];
			$msg .= "Updating industry jobs of $rowUser[login]...<br>";
			$str =  "Updating industry jobs of $rowUser[login]:<br>";

			//$msg .= "<br>";
			$api->accountId = $accountId;
			$api->userId = $userId;
			$api->apiKey = $apiKey;
			$api->characterId = $characterId;
			
			$apires = $api->UpdateIndustryJobs();
			$msg .= $apires["message"];
			$str .= $apires["message"];
			$msg .= "<br>";
			
			$db->query("insert into api_updater_log set _date_ = now(), message = '" . mysql_escape_string($str) . "';");
		}
		$qr->close();
		$db->close();
		echo $msg;
		
	?>
</body>
</html>
