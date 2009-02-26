<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
	<title>Просмотр писем для отладки парсера</title>
	<meta http-equiv="content-type" content="text/html;charset=utf-8" />
	<meta name="generator" content="Geany 0.14" />
</head>

<body>

<?php
	include_once "database.php";
	$db = OpenDB2();
	$qr = $db->query("select * from api_insurance_emails;");
	while($row = $qr->fetch_assoc())
	{
		$emailText = base64_decode($row["emailText"]);
		echo "<pre>$emailText</pre><br>";
		
		$md = md5($emailText);
		echo "md5: $md<br>";
		
		//----------------------------------------------------------------------
		//определяем тип письма: начало страховки, страховая выплата, истечение страховки
		$emailType = "unknown";
		if(preg_match("/insurance company has transferred.*into your account for the recent loss of your ship/i", $emailText) == 1)
			$emailType = "insurance";
		if(preg_match("/insurance contract between.*has expired/i", $emailText) == 1)
			$emailType = "expired";
		if(preg_match("/Congratulations on the insurance on your ship/i", $emailText) == 1)
			$emailType = "issued";
		echo "Тип письма: $emailType<br>";
		
		//----------------------------------------------------------------------
		//выборка даты письма
		preg_match("/^\d\d\d\d\.\d\d\.\d\d \d\d:\d\d/", $emailText, $regs);
		//print_r($regs);
		$emailDate = $regs[0];
		echo "Дата письма: $emailDate<br>";
		
		//----------------------------------------------------------------------
		//выборка refId
		if($emailType == "insurance")
		{
			preg_match("/(?<=RefID:)\d+/", $emailText, $regs);
			//print_r($regs);
			$emailRefId = $regs[0];
		}
		if($emailType == "expired" || $emailType == "issued")
		{
			preg_match("/(?<=Reference ID: )\d+/", $emailText, $regs);
			//print_r($regs);
			$emailRefId = $regs[0];
		}
		echo "RefID: $emailRefId<br>";
		

		//----------------------------------------------------------------------
		//определение дат начала и конца страховки
		if($emailType == "issued")
		{
			$issuedTime = $emailDate;
			preg_match("/(?<=will expire at )[\d\.: ]+(?=\,)/", $emailText, $regs);
			//print_r($regs);
			$expiredTime = $regs[0];
			//echo "Начало и конец страховки: $issuedTime - $expiredTime<br>";
		}
		if($emailType == "expired")
		{
			$expiredTime = $emailDate;
			preg_match("/(?<=issued at )[\d\.: ]+(?= has expired)/", $emailText, $regs);
			//print_r($regs);
			$issuedTime = $regs[0];
			//echo "Начало и конец страховки: $issuedTime - $expiredTime<br>";
		}
		if($emailType == "insurance")
		{
			$issuedTime = null;
			$expiredTime = $emailDate;
			//echo "Начало и конец страховки: $issuedTime - $expiredTime<br>";
		}
		echo "Начало и конец страховки: $issuedTime - $expiredTime<br>";
		
		//----------------------------------------------------------------------
		//выборка типа корабля
		if($emailType == "issued")
		{
			preg_match("/(?<=\().*(?=\) at a level)/", $emailText, $regs);
			//print_r($regs);
			$shipTypeName = $regs[0];
			echo "Тип корабля: $shipTypeName<br>";
		}
		else
			$shipTypeName = null;


		//----------------------------------------------------------------------
		//имя корабля
		if($emailType == "issued")
		{
			preg_match("/(?<=for your ship, ).+(?= \($shipTypeName\))/", $emailText, $regs);
			//print_r($regs);
			$shipName = $regs[0];
		}
		if($emailType == "expired")
		{
			preg_match("/(?<=of the ship ).+(?= issued at)/", $emailText, $regs);
			//print_r($regs);
			$shipName = $regs[0];
		}
		echo "Имя корабля: $shipName<br>";
		
		//----------------------------------------------------------------------
		//получение суммы страховки
		if($emailType == "insurance")
		{
			preg_match("/(?<=has transferred ).+(?= ISK into)/", $emailText, $regs);
			//print_r($regs);
			$summ = str_replace(",", "", $regs[0]);
			echo "Выплаченная страховка: $summ<br>";
		}
		
		$db->query("update api_insurance_emails set hashtext = '$md', refId = $emailRefId where recordId = '$row[recordId]';");
		echo "<hr>";
	}
	$db->close();
?>
</body>
</html>
