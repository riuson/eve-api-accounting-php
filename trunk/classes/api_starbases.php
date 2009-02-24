<?php
	include_once "starbase.php";
    class Api_Starbases
    {
    	var $corpInfo;
    	var $result;
    	var $request_processor;
    	var $accountId;
    	var $knownStates;
		
		public function __construct()
		{
			$this->knownStates = array(
				0 => "Unanchored",
				1 => "Anchored / Offline",
				2 => "Onlining",
				3 => "Reinforced",
				4 => "Online"
				);
			$this->request_processor = $_SERVER["PHP_SELF"] . "?mode=" . get_class($this);
		}
        function PreProcess($page)
        {
        	/*$privateInfo = false;
        	if(isset($_REQUEST["corporationId"]))
        	{
        		$corpId = $_REQUEST["corporationId"];

				$Api = new ApiInterface("");
				$this->corpInfo = $Api->GetCorpCorporationSheet($corpId);
				$this->result = $this->corpInfo;
			}
			else
			{*/
				$privateInfo = true;
				$User = User::CheckLogin();
				$User->CheckAccessRights(get_class($this), true);
				$this->accountId = $User->GetAccountId();

				$Api = new ApiInterface($this->accountId);
				$Api->userId = $User->GetUserId();
				$Api->apiKey = $User->GetApiKey();
				$Api->characterId = $User->GetCharacterId();
				//$this->result = $Api->UpdateIndustryJobs();
				
			//}

			//print_r($this->result);
			if($this->result["error"] != "")
			{
				$page->Body = $this->result["error"];
			}
			else
			{
				$itemId = null;
				if(isset($_REQUEST["itemId"]))
				{
					$itemId = $_REQUEST["itemId"];
					if(preg_match("/^\d+$/", $itemId) == 0)
						$itemId = null;
				}
				if($itemId == null)
					$this->StarbasesList($page);
				else
					$this->StarbaseInfo($page, $this->accountId, $itemId);
				//if($installedInSolarSystemId != null)
				//	$this->ShowJobsInSolarSystem($page, $installedInSolarSystemId);
			}
		}
		function StarbasesList($page)
		{
			$page->Body = "<table class='b-border b-widthfull' cellspacing='1' cellpadding='1'>";
			$page->Body .= "<tr class='b-table-caption'>
				<td class='b-center'>#</td>";
			$page->Body .= $page->WriteSorter(array (
				"moonName" => "Луна",
				"typeName" => "Тип башни",
				"state" => "Состояние",
				"stateTimestamp" => "Время состояния",
				"onlineTimestamp" => "Время включения",
				"endTimestamp" => "Время отключения (расч.)",
				));
			$page->Body .= "</tr>";
			$sorter = $page->GetSorter("locationId");

			$query = "SELECT api_starbases.* , invTypes.typeName, mapSolarSystems.solarSystemName AS locationName, eveNames.itemName AS moonName
FROM api_starbases
LEFT JOIN invTypes ON api_starbases.typeId = invTypes.typeID
LEFT JOIN mapSolarSystems ON api_starbases.locationId = mapSolarSystems.solarSystemID
LEFT JOIN eveNames ON api_starbases.moonId = eveNames.itemID
WHERE api_starbases.accountId = '{$this->accountId}' $sorter;";

			//echo $query;
			$db = OpenDB2();
			$qr = $db->query($query);
			if($qr)
			{
				$index = 0;
				while($row = $qr->fetch_assoc())
				{
					if(($index % 2) == 1)
						$rowClass = "b-row-even";
					else
						$rowClass = "b-row-odd";
					$index++;

					$page->Body .= "<tr class='$rowClass'>
						<td class='b-center'>$index</td>
						<td class='b-center'><a href='{$this->request_processor}&amp;itemId=$row[itemId]'>$row[moonName]</a></td>
						<td class='b-center'>$row[typeName]</td>
						<td class='b-center'>{$this->knownStates[$row["state"]]}</td>
						<td class='b-center'>$row[stateTimestamp]</td>
						<td class='b-center'>$row[onlineTimestamp]</td>
						<td class='b-center'>$row[endTimestamp]</td>
						</tr>";
				}
				$qr->close();
			}
			$page->Body .= "</table>";
			$db->close();
		}
		function StarbaseInfo($page, $accountId, $itemId)
		{
			$starbase = new Starbase($accountId, $itemId);
			//$calcRes = $starbase->calcFuelEndTime();
			
			$settingsChanged = false;

	        $a = $starbase->GetTowerSettings();
	        $calcRes = $starbase->calcFuelEndTime();

	        $fuellist = $calcRes["fuelTable"];
	        //print_r($starbase);
			$nowTS = $calcRes["nowTS"];

			if($starbase->consumptionOzone <= 0)
			{
				$power = "?";
				$powerPercent = "?";
			}
			else
			{
				$power = round($starbase->powerMax * $starbase->consumptionOzone / $starbase->consumptionOzoneDefault);
				$powerPercent = round($starbase->consumptionOzone * 100 / $starbase->consumptionOzoneDefault);
			}

			if($starbase->consumptionWater <= 0)
			{
				$cpu = "?";
				$cpuPercent = "?";
			}
			else
			{
				$cpu = round($starbase->cpuMax * $starbase->consumptionWater / $starbase->consumptionWaterDefault);
				$cpuPercent = round($starbase->consumptionWater * 100 / $starbase->consumptionWaterDefault);
			}

	        $strEndTime = $calcRes["strEndTime"];
	        //$page->Body .= "<p>Топливо закончится: $strEndTime</p>";
	        $page->Body .= "<p>
$starbase->locationName [$calcRes[security]]<br>
$starbase->moonName, $starbase->typeName, $starbase->stateName</p>
<!--// Время последнего состояния топлива: $starbase->stateTimeStamp<br> //-->
<p>Включен: $starbase->onlineTimeStamp<br>
Заправлен: $starbase->refuellingTimestamp<br>
Выключится: $strEndTime</p>

<p>Параметры обвеса башни:<br>
CPU $cpu of $starbase->cpuMax max ($cpuPercent%),&nbsp;Power $power of $starbase->powerMax max ($powerPercent%)</p>
";
            //if($cpuPowerChanged == true)
            //{
          //  	$page->Body .= "<br>Данные изменены";
            //}
            //вывод таблицы имеющегося топлива

	        $page->Body .= "<p>
Имеющееся топливо и его расход:<br>
<table class='b-widthfull b-border'>
<tr class='b-table-caption'>
<td class='b-center'>Назначение</td>
<td class='b-center'>Ресурс</td>
<td class='b-center'>Потребление, ед./час</td>
<td class='b-center'>В наличии, ед.</td>
<td class='b-center'>Осталось</td>
<td class='b-center'>до</td>
</tr>";
	        for($index = 0; $index  < count($fuellist); $index++)
	        {
	        	$fuel = $fuellist[$index];
				//вывод строк таблицы
				if(($index % 2) == 1)
					$rowClass = "b-row-even";
				else
					$rowClass = "b-row-odd";

				$page->Body .= "<tr class='$rowClass'>
<td class='b-center'>$fuel[purposeText]</td>
<td>$fuel[typeName]</td>
<td class='b-center'>$fuel[quantityAtHour]</td>
<td class='b-center'>$fuel[quantityCurrent]</td>
<td class='b-center'>$fuel[hoursToEndFromNow]</td>
<td class='b-center'>$fuel[strEndTime]</td>
</tr>";
	        }
	        $page->Body .= "</table></p>";
	        //$page->Body .= "<hr>";
	        if(!isset($_POST["calcFuelparameter"]))
	        {
	        	$calcFuelparameter = 12;
	        }
	        else
	        {
		        $calcFuelparameter = $_POST["calcFuelparameter"];
		        if($calcFuelparameter != "full_bay" && ($calcFuelparameter < 1 || $calcFuelparameter > 29))
		        	$calcFuelparameter = 12;
	        }
	        $page->Body .= "<p><form name='calcFuel' action='{$this->request_processor}&itemId=$itemId' method='post'>
	        Выберите дату заправки:&nbsp;до&nbsp;
	        <select name='calcFuelparameter'>";
	        //print("<option value='full_bay'>До заполнения fuel bay</option>");
	        //$parsedStatusTS = getdate($statusTS + 3600);
	        //$parsedTimeNow = getdate(time());
	        //$nowTS = mktime($parsedTimeNow["hours"], $parsedStatusTS["minutes"], $parsedStatusTS["seconds"], $parsedTimeNow["mon"], $parsedTimeNow["mday"], $parsedTimeNow["year"]);
	        for($index = 1; $index < 30; $index++)
	        {
	        	$str = date("Y-m-d H:i:s", $nowTS + 86400 * $index);
	        	if($calcFuelparameter == $index)
	        	    $page->Body .= "<option selected value='$index'>$str ($index д.)</option>";
	            else
	                $page->Body .= "<option value='$index'>$str ($index д.)</option>";
	        }
	        $page->Body .= "</select>
	        &nbsp;<input type='submit' value='Рассчитать'>
	        </form></p>";
            if(isset($_POST["calcFuelparameter"]))
            {
            	//print("расчёты");
            	/*
            	минимальное время: $minEndTime
            	можно просто помножить требуемый интервал на расход, но при этом не будут учитываться
            	уже (ещё) имеющиеся в посе запасы.
            	чтобы учесть - надо вычесть из рассчитанного выше то, что останется в посе к $minEndTime.
            	перебираем строки, вычисляем кол-во за интервал, и вычитаем.
            	что вычитаем - имеющееся сейчас. считать будем от текущего времени, а не от $minEndTime
            	*/
            	$fuelCalcTime = $nowTS + 86400 * $calcFuelparameter;
            	$strFuelCalcTime = date("Y-m-d H:i:s", $fuelCalcTime);
            	$page->Body .= "<p>Таблица топлива для заправки до $strFuelCalcTime";
            	$hoursCalc = 24 * $calcFuelparameter;
				$page->Body .= "<table class='b-widthfull b-border'>
<tr class='b-table-caption'>
<td class='b-center' rowspan='2'>Назначение</td>
<td class='b-center' rowspan='2'>Ресурс</td>
<td class='b-center' rowspan='2'>Объём 1ед, м&sup3</td>
<td class='b-center' colspan='2'>Заправка дополнением</td>
<td class='b-center' colspan='2'>Заправка на интервал</td>
</tr>
<tr class='b-table-caption'>
<td class='b-center'>Количество, ед.</td>
<td class='b-center'>Объём, м&sup3</td>
<td class='b-center'>Количество, ед.</td>
<td class='b-center'>Объём, м&sup3</td>
</tr>";
				$summaryCalcVolume = 0;
				$summaryCalcWithoutCurrentVolume = 0;
				for($index = 0; $index  < count($fuellist); $index++)
				{
					$fuel = $fuellist[$index];
					//$strEndTime = date("Y-m-d H:i:s", $fuel["endTime"]);
					if($fuel["quantityAtHour"] > 0)
					{
						$fuel["quantityCalc"] = $fuel["quantityAtHour"] * $hoursCalc - $fuel["quantityCurrent"];
						$fuel["volumeCalc"] = $fuel["quantityCalc"] * $fuel["volume"];
						$fuel["quantityCalcWithoutCurrent"] = $fuel["quantityAtHour"] * $hoursCalc;
						$fuel["volumeCalcWithoutCurrent"] = $fuel["quantityCalcWithoutCurrent"] * $fuel["volume"];
					}
					else
					{
                        $fuel["quantityCalc"] = 0;
						$fuel["volumeCalc"] = 0;
						$fuel["quantityCalcWithoutCurrent"] = 0;
						$fuel["volumeCalcWithoutCurrent"] = 0;
					}
					//стронций для реинформа не считать
					if($fuel["resourceTypeID"] != 16275)
					{
						if($fuel["volumeCalc"] > 0)
							$summaryCalcVolume += $fuel["volumeCalc"];
						if($fuel["volumeCalcWithoutCurrent"] > 0)
							$summaryCalcWithoutCurrentVolume += $fuel["volumeCalcWithoutCurrent"];
					}
					//вывод строк таблицы
					if(($index % 2) == 1)
						$rowClass = "b-row-even";
					else
						$rowClass = "b-row-odd";

					$page->Body .= "<tr class='$rowClass'>
<td class='b-center'>$fuel[purposeText]</td>
<td>$fuel[typeName] [$fuel[resourceTypeID]]</td>
<td class='b-center'>$fuel[volume]</td>
<td class='b-center'>$fuel[quantityCalc]</td>
<td class='b-center'>$fuel[volumeCalc]</td>
<td class='b-center'>$fuel[quantityCalcWithoutCurrent]</td>
<td class='b-center'>$fuel[volumeCalcWithoutCurrent]</td>
</tr>";
						//print_r($fuel);
				}
				if(($index % 2) == 1)
					$rowClass = "b-row-even";
				else
					$rowClass = "b-row-odd";
				$page->Body .= "
<tr class='$rowClass'>
<td class='b-center' colspan='3'>Суммарный объём (без стронция), м&sup3;</td>
<td>&nbsp;</td>
<td class='b-center'>{$page->FormatNum($summaryCalcVolume, 0)}</td>
<td>&nbsp;</td>
<td class='b-center'>{$page->FormatNum($summaryCalcWithoutCurrentVolume, 0)}</td>
</tr>";
				$page->Body .= "</table></p>";
            }
			//$page->Body .= "<br><div class='b-border b-widthfull'><pre>";
			//$page->Body .= htmlentities(($starbase->details->saveXML()));
			//$page->Body .= "</pre></div><br>";
		}

		function ProcessSubscribe($db, $accountId)
		{
			include_once "classes/user.php";
			include_once "classes/subscribes.php";
			//include_once "classes/api_corporationsheet.php";
			//получение юзера по аакаунту
			$user = new User("");
			if($user->GetUserInfo($accountId))
			{
				$user->CheckAccessRights(get_class($this), true);

				$Api = new ApiInterface($user->GetAccountId());
				$Api->userId = $user->GetUserId();
				$Api->apiKey = $user->GetApiKey();
				$Api->characterId = $user->GetCharacterId();
				$corpInfo = $Api->GetCorpCorporationSheet();
				$link = "http://ea.mylegion.ru/index.php?mode=" . get_class($this);
				$weekTime = date("Y-m-d H:i:s", strtotime("+1 week"));
				$query = "SELECT api_starbases.* , invTypes.typeName, mapSolarSystems.solarSystemName AS locationName, eveNames.itemName AS moonName
FROM api_starbases
LEFT JOIN invTypes ON api_starbases.typeId = invTypes.typeID
LEFT JOIN mapSolarSystems ON api_starbases.locationId = mapSolarSystems.solarSystemID
LEFT JOIN eveNames ON api_starbases.moonId = eveNames.itemID
WHERE api_starbases.accountId = '$accountId' and api_starbases.endTimestamp < '$weekTime' order by endTimestamp;";
				$qr = $db->query($query);
				$countPoses = $qr->num_rows;

				if($countPoses > 0)
				{
					$message = "
<html>
<head>
<title>Состояние посов корпорации $corpInfo[corporationName]</title>
</head>
<body><p>Состояние посов корпорации <b>$corpInfo[corporationName]</b>, топливо в которых закончится за неделю:</p>
<table bordercolor='silver' border='1' cellspacing='1' cellpadding='1'>
<tr bgcolor='#808080'>
	<td align='center' valign='middle'>#</td>
	<td align='center' valign='middle'>Луна</td>
	<td align='center' valign='middle'>Тип башни</td>
	<td align='center' valign='middle'>Состояние</td>
	<td align='center' valign='middle'>Время состояния</td>
	<td align='center' valign='middle'>Время включения</td>
	<td align='center' valign='middle'>Время отключения (расч.)</td>
</tr>";
					$index = 0;
					$details = "";
					if($qr)
					{
						$index = 0;
						while($row = $qr->fetch_assoc())
						{
							$index++;
							$message .= "<tr>
								<td align='center'>$index</td>
								<td align='center'><a href='{$this->request_processor}&amp;itemId=$row[itemId]'>$row[moonName]</a></td>
								<td align='center'>$row[typeName]</td>
								<td align='center'>{$this->knownStates[$row["state"]]}</td>
								<td align='center'>$row[stateTimestamp]</td>
								<td align='center'>$row[onlineTimestamp]</td>
								<td align='center'>$row[endTimestamp]</td>
								</tr>";
							$details .= "<pre>" . htmlentities($row["details"]) . "</pre><br>";
						}
						$qr->close();
					}
					$message .= "</table><br>";
					$link = "http://ea.mylegion.ru/index.php?mode=" . get_class($this);
					$message .= "<a href='$link'>$link</a>";
					//$message .= $details;
					$message .= "</body></html>";

					$subject = "Состояние посов корпорации $corpInfo[corporationName]";

					//получение адресов и подписок этого аккаунта
					$query = "select email, modes from api_subscribes where accountId = '$accountId';";
					$qr = $db->query($query);
					while($row = $qr->fetch_assoc())
					{
						$email = $row["email"];
						$modes = $row["modes"];
						$thissub = get_class($this);
						if(preg_match("/(\W|^)$thissub(\W|$)/", $modes) != 0)
						{
							Subscribes::SendMail($email, $subject, $message);
						}
					}
				}
				$qr->close();
			}
		}
    }
?>
