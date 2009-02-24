<?php
    class Starbase
    {
        var $recordId;
        var $accountId;
        var $itemId;
        var $typeId;
        var $locationId;
        var $moonId;
        var $state;
        var $stateTimeStamp;
        var $onlineTimeStamp;
        var $fuelBay;
        var $cpuMax;
        var $powerMax;
        var $details;
        
        var $refuellingTimestamp;

        var $consumptionOzone;
        var $consumptionWater;
        var $consumptionOzoneDefault;
        var $consumptionWaterDefault;

        var $typeName;
        var $locationName;
        var $moonName;
        var $stateName;
        
        var $knownStates;

		public function __construct($accountId, $itemId)
		{
			$this->knownStates = array(
				0 => "Unanchored",
				1 => "Anchored / Offline",
				2 => "Onlining",
				3 => "Reinforced",
				4 => "Online"
				);

			$query = "SELECT api_starbases.* , invTypes.typeName, mapSolarSystems.solarSystemName AS locationName, eveNames.itemName AS moonName, api_starbase_fuel.refuelling 
FROM api_starbases
LEFT JOIN invTypes ON api_starbases.typeId = invTypes.typeID
LEFT JOIN mapSolarSystems ON api_starbases.locationId = mapSolarSystems.solarSystemID
LEFT JOIN eveNames ON api_starbases.moonId = eveNames.itemID
left join api_starbase_fuel on api_starbase_fuel.accountId = '$accountId' and api_starbase_fuel.itemId = $itemId
WHERE api_starbases.accountId = '$accountId' and api_starbases.itemId = $itemId;";

        	$result = null;
        	$db = OpenDB2();
        	$qr = $db->query($query);
        	$starbase = null;
        	if($row = $qr->fetch_assoc())
        	{
        		$this->recordId = $row["recordId"];
        		$this->accountId = $row["accountId"];
        		$this->itemId = $row["itemId"];
        		$this->typeId = $row["typeId"];
        		$this->locationId = $row["locationId"];
        		$this->moonId = $row["moonId"];
        		$this->state = $row["state"];
        		$this->stateTimeStamp = $row["stateTimestamp"];
        		$this->onlineTimeStamp = $row["onlineTimestamp"];

        		$this->typeName = $row["typeName"];
        		$this->locationName = $row["locationName"];
        		$this->moonName = $row["moonName"];
        		$this->stateName = $this->knownStates[$this->state];
        		
        		$this->fuelBay = 0;
        		$this->cpuMax = 0;
        		$this->powerMax = 0;
        		
        		$this->refuellingTimestamp = $row["refuelling"];
        		
        		$this->details = DomDocument::loadXML($row["details"]);

        		//замена stateTimestamp на данные из details
        		$domPath2 = new DOMXPath($this->details);
				$nodeStateTimestamp = $domPath2->query("/eveapi/result/stateTimestamp");
				$nodeStateTimestamp = $nodeStateTimestamp->item(0);
				$this->stateTimeStamp = $nodeStateTimestamp->nodeValue;

				$this->consumptionOzone = null;
				$this->consumptionWater = null;
				$this->consumptionOzoneDefault = null;
				$this->consumptionWaterDefault = null;

        		$qr->close();
        	}
        	$db->close();
        	if($this->itemId != 0)
        	{
        		$info = $this->GetCpuPowerCapacity($this->itemId);

        		$this->fuelBay = $info["capacity"];
        		$this->cpuMax = $info["cpu"];
        		$this->powerMax = $info["power"];
			}
		}
        function GetCpuPowerCapacity($typeId)
        {
        	$result = array(
        		"cpu" => 0,
        		"power" => 0,
        		"capacity" => 0);

			$query = "SELECT dgmAttributeTypes.attributeName, dgmTypeAttributes.valueInt, dgmAttributeTypes.attributeId, invTypes.capacity ".
"FROM invTypes ".
"LEFT JOIN dgmTypeAttributes ON invTypes.typeId = dgmTypeAttributes.typeId ".
"LEFT JOIN dgmAttributeTypes ON dgmTypeAttributes.attributeId = dgmAttributeTypes.attributeId ".
"WHERE (((dgmAttributeTypes.attributeId)=48 Or (dgmAttributeTypes.attributeId)=11) AND ((invTypes.typeId)=$this->typeId)) ".
"ORDER BY dgmAttributeTypes.attributeName;";
			$db = OpenDB2();
			$qr = $db->query($query);

			while($row = $qr->fetch_assoc())
			{
				if($row["attributeId"] == 48)
					$result["cpu"] = $row["valueInt"];
				if($row["attributeId"] == 11)
					$result["power"] = $row["valueInt"];
				$result["capacity"] = $row["capacity"];
			}
			$qr->close();
			$db->close();
			return $result;
		}
        function getFuelList($typeId)
        {
        	$query = "SELECT invTypes.typeId, invControlTowerResourcePurposes.purposeText, invControlTowerResources.resourceTypeID, invTypes_1.typeName, invControlTowerResources.quantity, invTypes_1.volume ".
			"FROM ((invTypes LEFT JOIN invControlTowerResources ON invTypes.typeId=invControlTowerResources.controlTowerTypeID) LEFT JOIN invTypes AS invTypes_1 ON invControlTowerResources.resourceTypeID=invTypes_1.typeId) LEFT JOIN invControlTowerResourcePurposes ON invControlTowerResources.purpose=invControlTowerResourcePurposes.purpose ".
			"WHERE (((invTypes.typeId)=$typeId)) ".
			"ORDER BY invControlTowerResourcePurposes.purpose, invControlTowerResources.resourceTypeID;";
			$db = OpenDB2();
			$qr = $db->query($query);
			$result = array();
			$index = 0;
			while($row = $qr->fetch_assoc())
			{
				$result[$index++] = $row;
			}
			$qr->close();
			$db->close();
			return $result;
        }
        function getSecurityFraction($locationId)
        {
        	$result = array();
        	$result["security"] = 0;
        	$result["fractionName"] = "no fraction";

        	$query = "SELECT round(mapSolarSystems.security,1) AS security, mapSolarSystems.solarSystemName, eveNames.itemName AS fractionName, api_sovereignty.sovereigntyLevel, api_alliances.name AS allianceName ".
			"FROM ((api_sovereignty LEFT JOIN mapSolarSystems ON api_sovereignty.solarSystemID = mapSolarSystems.solarSystemID) ".
			"LEFT JOIN eveNames ON api_sovereignty.factionID = eveNames.itemId) ".
			"LEFT JOIN api_alliances ON api_sovereignty.allianceID = api_alliances.allianceID ".
			"WHERE ((api_sovereignty.solarSystemID = $locationId));";
			
			$db = OpenDB2();
			//echo "<b>$query</b>";
			$qr = $db->query($query);
			if($row = $qr->fetch_assoc())
			{
				//print_r($row);
				$result["security"] = $row["security"];
				if($row["fractionName"] != null)
				{
					$result["fractionName"] = $row["fractionName"];
				}
				else
				{
					$result["fractionName"] = null;
				}
				if($row["allianceName"] != null)
				{
					$result["allianceName"] = $row["allianceName"];
				}
				else
				{
					$result["allianceName"] = null;
				}
				if($row["sovereigntyLevel"] != null)
				{
					$result["sovereigntyLevel"] = $row["sovereigntyLevel"];
				}
				else
				{
					$result["sovereigntyLevel"] = null;
				}
				$qr->close();
			}
			$db->close();
        	return $result;
        }
		private function stringToTime($strDateTime)
		{
			//$format = "%Y-%m-%d %H:%M:%S";
			//$dt = strptime($strDateTime, $format);
			$dt = explode(" ", $strDateTime);
			$d = explode("-", $dt[0]);
			$t = explode(":", $dt[1]);
			//print("parse $strDateTime ");
			$result = mktime($t[0], $t[1], $t[2], $d[1], $d[2], $d[0]);
			return $result;
		}
        function calcFuelEndTime()
        {
        	//$this->fullViewInfo();
        	$fuellist = $this->getFuelList($this->typeId);
	        //print_r($fuellist);
            //навигационный класс
            $domPath = new DOMXPath($this->details);
            //получение массива нод, подходящих под описание: rowset со списком чаров
            $rowset = $domPath->query("/eveapi/result/rowset[@name='fuel']/row");
            //получение security и фракции, которой принадлежит система
	        $fraction_security = $this->getSecurityFraction($this->locationId);
	        //print_r($fraction_security);
            //наложение на список требуемых башней ресурсов того, что есть в топливном баке
	        //foreach($fuellist as $fuel)
	        $nowTS = time();
	        for($index = 0; $index  < count($fuellist); $index++)
	        {
	        	$fuel = $fuellist[$index];
        		//$index = 0;
        		//кол-во топлива в момент TS
        		$fuel["quantityInBayAtTS"] = 0;
	            foreach($rowset as $row)
	            {
	            	if($row->localName == "row")
	            	{
	            		if($row->getAttribute("typeID") == $fuel["resourceTypeID"])
	            		{
	            			$fuel["quantityInBayAtTS"] = $row->getAttribute("quantity");
	                    }
	            	}
	            }
	            $fuel["quantityAtHourDefault"] = $fuel["quantity"];
	            //если это чартер на пос
	            if(eregi("Charter",  $fuel["typeName"]))
	            {
	            	//если чартер не для фракции, которой принадлежит система, расход ноль
	            	$fractionName = $fraction_security["fractionName"];
	            	if($fractionName == "")
	            		$fractionName = "no fraction"; 
	            	//print("<p>fractionName: $fractionName, typeName: $fuel[typeName]</p>");
		            if(!eregi($fractionName, $fuel["typeName"]) || $fraction_security["security"] < 0.4)
		            {
		               $fuel["quantity"] = 0;
		            }
	            }
	            //если это Liquid Ozone для Power
	            if($fuel["purposeText"] == "CPU")
	            {
	            	//$mul = $this->cpu / $this->cpuMax;
	            	//$fuel["quantity"] = ceil($fuel["quantity"] * $mul);//round

	            	$this->consumptionWaterDefault = $fuel["quantity"];
	            	//если расход известен и не было заправки
	            	if($this->consumptionWater != null && $this->consumptionWater >= 0)
	            		$fuel["quantity"] = $this->consumptionWater;
	            	//print("<p>cpu: $starbase->cpu / $starbase->cpuMax = $mul : $fuel[quantity]</p>");
	            }
	            //если это Heavy Water для CPU
	            if($fuel["purposeText"] == "Power")
	            {
	            	//$mul = $this->power / $this->powerMax;
	            	//$fuel["quantity"] = ceil($fuel["quantity"] * $mul);//round

	            	//если расход известен и не было заправки
	            	$this->consumptionOzoneDefault = $fuel["quantity"];
	            	if($this->consumptionOzone != null && $this->consumptionOzone >= 0)
	            		$fuel["quantity"] = $this->consumptionOzone;
	            	//print("<p>power: $starbase->power / $starbase->powerMax = $mul : $fuel[quantity]</p>");
	            }
	            $fuellist[$index] = $fuel;
	            //print_r($fuellist[$index]);
	            //print("<hr/><br/>");
	        }
			$statusTS = $this->stringToTime($this->stateTimeStamp) - 3600;//время TS (time stamp), -1 час т.к. TS это следующая точка кормления поса
			$hoursFromTS = floor((time() - $statusTS) / 3600);//сколько часов прошло с TS
			$nowTS = $statusTS + 3600 * $hoursFromTS;//ближайщий timestamp (время обеда)
	        //вычисление оставшегося времени для имеющегося топлива
	        for($index = 0; $index  < count($fuellist); $index++)
	        {
	        	$fuel = $fuellist[$index];
	        	//получение количества и потребления топлива
	        	//перенос из одной колонки в другую, потребление в час
	        	$fuel["quantityAtHour"] = $fuel["quantity"];
	        	$quantityAtHour = $fuel["quantityAtHour"];
	        	//кол-во топлива в момент TS
	        	$quantityInBayAtTS = $fuel["quantityInBayAtTS"];
	        	//расчёт оставшегося со штампа времени
	        	//если потребление не нулевое, вычисляем, на сколько часов от TS хватит имеющегося топлива
	        	if($quantityAtHour != 0)
	        	{
	        		//округление в меньшую сторону, т.к. для работы пос кушает одну ЦЕЛУЮ порцию в час
	        	    $hoursToEndFromTimeStamp = floor($quantityInBayAtTS / $quantityAtHour);
	        	}
	        	else//если нулевое - время бесконечно
	        	{
	        	    $hoursToEndFromTimeStamp = 9999;
	        	}
				$fuel["hoursToEndFromTimeStamp"] = ($hoursToEndFromTimeStamp);
				//расчёт конечного времени
				$endTime = $statusTS + 3600 * $hoursToEndFromTimeStamp;//когда закончится топливо = на сколько его хватит + TS
				$fuel["endTime"] = $endTime;
				//расчёт оставшегося времени от текущего
				$fuel["hoursFromTS"] = $hoursFromTS;

				$hoursToEndFromNow = $hoursToEndFromTimeStamp - $hoursFromTS;
				$fuel["hoursToEndFromNow"] = $hoursToEndFromNow;
				//расчёт оставшегося сейчас топлива
				if($fuel["purposeText"] == "Reinforce")
				{
					if($this->stateName != "reinforced")
					{
						$fuel["quantityCurrent"] = $fuel["quantityInBayAtTS"];
						$fuel["hoursToEndFromNow"] = $hoursToEndFromTimeStamp;
					}
					else
						$fuel["quantityCurrent"] = $fuel["quantityInBayAtTS"] - $fuel["quantityAtHour"] * $hoursFromTS;
				}
				else
				{
					$fuel["quantityCurrent"] = $fuel["quantityInBayAtTS"] - $fuel["quantityAtHour"] * $hoursFromTS;
				}
				if($fuel["quantityCurrent"] < 0)
					$fuel["quantityCurrent"] = 0;
				//echo(date("Y-m-d H:i:s", $estimatedTime));
				//print(" = $starbase->stateTimeStamp ");
				//$fuel["estimatedTime"] =
				$fuellist[$index] = $fuel;
				//print_r($fuellist[$index]);
				//print("<hr/><br/>");
	        }
            $minEndTime = $fuellist[0]["endTime"];
	        for($index = 0; $index  < count($fuellist); $index++)
	        {
	        	$fuel = $fuellist[$index];
	        	$strEndTime = date("Y-m-d H:i:s", $fuel["endTime"]);
	        	//выбор минимального времени работы ПОСа по минимальному времени топлива
	        	if($minEndTime > $fuel["endTime"] && $fuel["purposeText"] != "Reinforce")
	        		$minEndTime = $fuel["endTime"];
	            $hoursToEndFromNow = $fuel["hoursToEndFromNow"];
	        	if($hoursToEndFromNow < 2000)
	            {
		            $daysToEndFromNow = floor($hoursToEndFromNow / 24);
		            $hoursToEndFromNow = ($hoursToEndFromNow - $daysToEndFromNow * 24);
		            if($daysToEndFromNow != 0)
		            {
		            	$hoursToEndFromNow = $daysToEndFromNow . "д " . $hoursToEndFromNow . "ч";
		            }
		            else
		            {
		            	$hoursToEndFromNow = $hoursToEndFromNow . "ч";
		            }
	            }
	            else
	            {
	            	$hoursToEndFromNow = "∞";
	            	$strEndTime = "∞";
	            }
	            $fuel["hoursToEndFromNow"] = $hoursToEndFromNow;
	            $fuel["strEndTime"] = $strEndTime;
	            $fuellist[$index] = $fuel;
	        }
	        $strEndTime = date("Y-m-d H:i:s", $minEndTime);
            //расчёт времени до конца топлива
	        $timeToEnd = ($minEndTime - $nowTS) / 3600;//сколько часов
	        $daysToEnd = floor($timeToEnd / 24);//сколько среди них целых дней
	        $hoursToEnd = floor($timeToEnd - 24 * $daysToEnd);//сколько часов в оставшемся дне
            if($daysToEnd == 0)
            {
            	$strTimeToEnd = $hoursToEnd . "ч";
            }
            else
            {
            	$strTimeToEnd = $daysToEnd . "д " . $hoursToEnd . "ч";
            }
	        //print("_ $strTimeToEnd _");
	        $result = array();
	        $result["strEndTime"] = $strEndTime;
	        $result["strTimeToEnd"] = $strTimeToEnd;
	        $result["fuelTable"] = $fuellist;
	        $result["nowTS"] = $nowTS;
	        $result["fractionName"] = $fraction_security["fractionName"];
	        $result["security"] = $fraction_security["security"];
	        return $result;
		}
		function GetTowerSettings()
		{
			$this->consumptionOzone = null;
			$this->consumptionWater = null;

			$db = OpenDB2();
			$query = "select round((minute(timediff(time2, time1)) + hour(timediff(time2, time1)) * 60)/60) as hours, (ozone1 - ozone2) as ozone, (water1 - water2) as water " .
				"from api_starbase_fuel where accountId = '$this->accountId' and itemId = $this->itemId;";
			$qr = $db->query($query);
			if($qr)
			{
				if($row = $qr->fetch_assoc())
				{
					//интервал времени, округлённый до часов
					$hours = $row["hours"];
					//израсходованное за этот интервал топливо
					$this->consumptionOzone = $row["ozone"];
					$this->consumptionWater = $row["water"];
					//если интервал равен 0, то данных о потреблении нет
					if($hours == 0)
					{
						$this->consumptionOzone = null;
						$this->consumptionWater = null;
					}
					else//иначе вычисляем расход в час
					{
						$this->consumptionOzone /= $hours;
						$this->consumptionWater /= $hours;
					}
				}
				$qr->close();
			}
			$db->close();
		}
		function UpdateFuel()
		{
			$domPath2 = new DOMXPath($this->details);
			//получение текущего количества топлива
			//озон
			$nodeOzoneCurrent = $domPath2->query("/eveapi/result/rowset[@name='fuel']/row[@typeID='16273']");
			$nodeOzoneCurrent = $nodeOzoneCurrent->item(0);
			$ozoneCurrent = $nodeOzoneCurrent->getAttribute("quantity");
			//вода
			$nodeWaterCurrent = $domPath2->query("/eveapi/result/rowset[@name='fuel']/row[@typeID='16272']");
			$nodeWaterCurrent = $nodeWaterCurrent->item(0);
			$waterCurrent = $nodeWaterCurrent->getAttribute("quantity");
			//запрос предыдущих значений из базы
			$db = OpenDB2();
			$query = "select * from api_starbase_fuel where accountId = '$this->accountId' and itemId = $this->itemId;";
			echo "<p>запрос из базы<br>$query</p>";

			$qr = $db->query($query);
			echo $db->error;

			$ozone1 = $ozone2 = $water1 = $water2 = 0;
			$time1 = $time2 = $refuelling = null;
			//если в базе есть, считать,
			if($qr)
			{
				print_r($qr);
				echo $qr->num_rows;
				$row = $qr->fetch_assoc();
				echo $row;
				if($row)
				//if($row = $qr->fetch_assoc())
				{
					echo "<p>считываются сохранённые ранее данные<br>";
					print_r($row);
					echo "</p>";
					$time1  = $row["time1"];
					$time2  = $row["time2"];
					$ozone1 = $row["ozone1"];
					$ozone2 = $row["ozone2"];
					$water1 = $row["water1"];
					$water2 = $row["water2"];
					$refuelling = $row["refuelling"];
				}
				$qr->close();
			}
			//если нет в базе, принять текущие значения по умолчанию и добавить в базу
			if($ozone1 == 0 || $ozone2 == 0 ||  $water1 == 0 ||  $water2 == 0 || $time1 == null || $time2 == null || $refuelling == null)
			{
				$time1 = $this->stateTimeStamp;
				$time2 = $this->stateTimeStamp;
				$ozone1 = $ozoneCurrent;
				$ozone2 = $ozoneCurrent;
				$water1 = $waterCurrent;
				$water2 = $waterCurrent;
				$query = "replace into api_starbase_fuel values('$this->accountId', $this->itemId, '$time1', '$time2', $ozone1, $ozone2, $water1, $water2, '0000-00-00 00:00:00');";
				echo "<p>в базе нет, добавляем<br>$query</p>";
				$db->query($query);
			}
			else
			{
				//далее сдвигаем 2 на место 1, в 2 записываем текущие данные
				$ozone1 = $ozone2;
				$ozone2 = $ozoneCurrent;
				$water1 = $water2;
				$water2 = $waterCurrent;
				$time1  = $time2;
				$time2  = $this->stateTimeStamp;
				//если время не изменилось, базу не обновлять
				if($time1 != $time2)
				{
					//если количество топлива увеличилось, значит была заправка
					if($ozone2 > $ozone1 || $water2 > $water1)
						$refuelling = $this->stateTimeStamp;
					$query = "replace into api_starbase_fuel values('$this->accountId', $this->itemId, '$time1', '$time2', $ozone1, $ozone2, $water1, $water2, '$refuelling');";
					echo "<p>в базе есть, обновляем<br>$query</p>";
					$db->query($query);
				}
			}
			$db->close();
		}
	}
?>
