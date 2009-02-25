<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
	<title>Проверка работоспособности apiKeys </title>
	<meta http-equiv="content-type" content="text/html;charset=utf-8" />
	<meta name="generator" content="Geany 0.14" />
</head>

<body>
	<?php
		include_once "classes/api2.php";
		include_once "classes/.config.php";
		
		$msg = "";

		$db = OpenDB2();
		$qr = $db->query("select * from api_users where master = '';");
		while($rowUser = $qr->fetch_assoc())
		{
			$accountId = $rowUser["accountId"];
			$userId = $rowUser["userId"];
			$apiKey = $rowUser["apiKey"];
			$characterId = $rowUser["characterId"];
			$msg .= "Проверка данных $rowUser[login]: $userId, $characterId...<br>";

			if(CheckUserData($dcapicode, $userId, $apiKey, $characterId) == false)
			{
				$msg .= "Проверка данных $rowUser[login] не прошла, проверка запасных ключей...<br>";
				$msg .= "Проверка $accountId<br>";
				$qr2 = $db->query("select * from api_users_reserve where accountId = '$accountId';");
				
				while($rowUserReserve = $qr2->fetch_assoc())
				{
					$userId = $rowUserReserve["userId"];
					$apiKey = $rowUserReserve["apiKey"];
					$characterId = $rowUserReserve["characterId"];
					$msg .= "Проверка запасных ключей для данных $rowUser[login]: $userId, $characterId...<br>";

					if(CheckUserData($dcapicode, $userId, $apiKey, $characterId) == false)
					{
						$msg .= "Проверка запасных ключей для данных $rowUser[login]: $userId, $characterId не прошла<br>";
						$db->query("update set valid = 0 where accountId = '$accountId' and userId = '$userId' and apiKey = '$apiKey' and characterId = '$characterId';");
					}
					else
					{
						$msg .= "Найден рабочий ключ для данных $rowUser[login]: $userId, $characterId<br>";
						$query = "update api_users_reserve set valid = 1 where accountId = '$accountId' and userId = '$userId' and apiKey = '$apiKey' and characterId = '$characterId';";
						//$msg .= $query;
						$db->query($query);
						$query = "update api_users set userId = '$userId', apiKey = '$apiKey', characterId = '$characterId' where accountId = '$accountId';";
						//$msg .= $query;
						$db->query($query);
						break;
					}
				}
				$qr2->close();
			}
			else
			{
				$msg .= "Проверка данных $rowUser[login] прошла успешно.<br>";
			}
		}
		$qr->close();
		$db->close();
		//echo $msg;
		
		function CheckUserData($dc, $userId, $apiKey, $characterId)
		{
			//$dc = "4r8731tsnb";
			//$dcapicode = "4r8731tsnb";
			$apiKey = RC4($dc, base64_decode($apiKey));
			//echo $apiKey . "<br>";
			//return;

			$result = false;
			$api = new ApiInterface("");
			$api->userId = $userId;
			$api->apiKey = $apiKey;
			$api->characterId = $characterId;
			
			$apires = $api->GetCorpCorporationSheet();
			if($apires["error"] == "")
				$result = true;

			return $result;
		}
	?>
</body>
</html>
