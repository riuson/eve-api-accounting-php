<?php
    class Api_Kills
    {
		var $alliance_processor;
		var $corporation_processor;

		public function __construct()
		{
        	$this->alliance_processor = $_SERVER["PHP_SELF"] . "?mode=Api_Alliances";// . get_class($this);
        	$this->corporation_processor = $_SERVER["PHP_SELF"] . "?mode=Api_CorporationSheet";// . get_class($this);
		}

    	function ParseDate($str, $default)
    	{
    		$result = $default;
			date_default_timezone_set("Etc/Universal");
			if($str)
			{
				if(preg_match("/^\d\d\d\d-\d\d-\d\d$/i", $str) == 1)
				{
					try
					{
						$date = new DateTime($str);
						$result = $date->format("Y-m-d");
					}
					catch(Exception $exc)
					{
						$result = $default;
					}
				}
			}
			return $result;
		}
        function PreProcess($page)
		{
			//session_start();
			date_default_timezone_set("Etc/Universal");
			
			$User = User::CheckLogin();
			$User->CheckAccessRights(get_class($this), true);

			$Api = new ApiInterface($User->GetAccountId());
			$Api->userId = $User->GetUserId();
			$Api->apiKey = $User->GetApiKey();
			$Api->characterId = $User->GetCharacterId();
			//$corpInfo = $Api->UpdateKillLog();
			$accountId = $User->GetAccountId();

			$request_processor = $_SERVER["PHP_SELF"] . "?mode=" . get_class($this);

			if(isset($_REQUEST["killId"]))
				$showKillId = $_REQUEST["killId"];
			else
				$showKillId = null;

			$db = OpenDB2();

			if($showKillId == null)
			{
				//подсчёт числа подходящих строк
				$qr = $db->query("select count(*) as _count_ from api_kills where accountId = '$accountId';");
				$row = $qr->fetch_assoc();
				$recordsCount = $row["_count_"];
				$qr->close();

				$pages = new PageSelector();
				$page->Body = $pages->Write($recordsCount);


				$page->Body .= "
<table class='b-border b-widthfull'>
    <tr class='b-table-caption'>
		<td class='b-center'>#</td>\n";
				$page->Body .= $page->WriteSorter(array (
					"killId" => "Kill ID",
					"killTime" => "Время",
					"victimName" => "Victim",
					"shipTypeName" => "Корабль",
					"solarSystemName" => "Система"
					));
				$page->Body .= "
    </tr>";

				$sorter = $page->GetSorter("killTime");
				//отдельно количество можно не запрашивать, т.к. есть свойство affected_rows
				//нет. оказалось надо отдельно, т.к. число записей нужно для определения покаызываемой страницы
				$query = 
"select api_kills.*,
mapSolarSystems.solarSystemName,
mapSolarSystems.security,
api_kills_victims.characterName as victimName,
api_kills_victims.corporationName as victimCorporationName,
api_kills_victims.corporationId as victimCorporationId,
api_kills_victims.allianceName as victimAllianceName,
api_kills_victims.allianceId as victimAllianceId,
invTypes.typeName as shipTypeName,
invGroups.groupName as shipGroupName 
from api_kills\n
left join api_kills_victims on api_kills.recordId = api_kills_victims.recordKillId\n
left join mapSolarSystems on api_kills.solarSystemId = mapSolarSystems.solarSystemID\n
left join invTypes on api_kills_victims.shipTypeId = invTypes.typeId\n
left join invGroups on invTypes.groupID = invGroups.groupID\n
where api_kills.accountId = '$accountId' $sorter limit $pages->start, $pages->count;";
//echo $query;
				$qr = $db->query($query);

				//$page->Body .= $query;

				$rowIndex = $pages->start;
				while($row = $qr->fetch_assoc())
				{
					if(($rowIndex % 2) == 1)
						$rowClass = "b-row-even";
					else
						$rowClass = "b-row-odd";

					$systemSecurity = number_format($row["security"], 1);
					$page->Body .= "
	<tr class='$rowClass'>
		<td class='b-center'>$rowIndex</td>
		<td class='b-center'><a href='$request_processor&amp;killId=$row[killId]'>$row[killId]</a></td>
		<td class='b-center'>$row[killTime]</td>
		<td>$row[victimName]<br>
			<a href='$this->corporation_processor&amp;corporationId=$row[victimCorporationId]'>Corp:$row[victimCorporationName]</a><br>
			<a href='$this->alliance_processor&amp;allianceId=$row[victimAllianceId]'>Ally:$row[victimAllianceName]</a></td>
		<td>$row[shipTypeName]<br>$row[shipGroupName]</td>
		<td class='b-center'>$row[solarSystemName]<br>($systemSecurity)</td>
	</tr>
";
					$rowIndex++;
				}
				$page->Body .= "
</table>
";
				$page->Body .= $pages->Write($recordsCount);
				$qr->close();
			}

			if($showKillId != null && preg_match("/^\d+$/", $showKillId) == 1)
			{
				$query = "select api_kills.*, mapSolarSystems.solarSystemName, mapSolarSystems.security from api_kills ".
					"left join mapSolarSystems on api_kills.solarSystemId = mapSolarSystems.solarSystemID ".
					"where api_kills.accountId = '$accountId' and api_kills.killId = '$showKillId';";
				//echo $query;
				$qr = $db->query($query);
				$row = $qr->fetch_assoc();

				$recordKillId = $row["recordId"];
				$solarSystemName = $row["solarSystemName"];
				$solarSystemSecurity = $row["security"];

				$date = $row["killTime"];
				$qr->close();

				$query = "select api_kills_victims.*, invTypes.typeName as shipTypeName, invTypes.basePrice, invGroups.groupName as shipGroupName ".
					"from api_kills_victims ".
					"left join invTypes on api_kills_victims.shipTypeId = invTypes.typeId ".
					"left join invGroups on invTypes.groupID = invGroups.groupID ".
					"where api_kills_victims.accountId = '$accountId' and api_kills_victims.recordKillId = '$recordKillId';";
				//echo $query;
				$qr = $db->query($query);
				$row = $qr->fetch_assoc();
				$victimId = $row["characterId"];
				$victimName = $row["characterName"];
				$victimCorporationId = $row["corporationId"];
				$victimCorporationName = $row["corporationName"];
				$victimAllianceId = $row["allianceId"];
				$victimAllianceName = $row["allianceName"];
				$victimShipTypeName = $row["shipTypeName"];
				$victimShipGroupName = $row["shipGroupName"];
				$victimShipPrice = $row["basePrice"];
				$victimDamageTaken = $row["damageTaken"];
				//print_r($row);
				$qr->close();

				//$page->Body .= "Location: $location<br>Date: $date<br>".
				//	"Victim: $victimName<br>Corporation: $victimCorporationName<br>Alliance: $victimAllianceName<br>".
				//	"Ship: $victimShipTypeName ($victimShipGroupName)<br>";
				$victimTable = "<table class='b-border b-widthfull'>
				                    <tr class='b-row-even'>
				                        <td rowspan='4'><img src='http://img.eve.is/serv.asp?s=64&amp;c=$victimId' alt='$victimName'></td>
				                        <td>Victim:</td><td>$victimName</td>
									</tr>
				                    <tr class='b-row-odd'>
				                        <td>Corp:</td><td><a href='$this->corporation_processor&amp;corporationId=$victimCorporationId'>$victimCorporationName</a></td>
									</tr>
				                    <tr class='b-row-even'>
				                        <td>Alliance:</td><td><a href='$this->alliance_processor&amp;allianceId=$victimAllianceId'>$victimAllianceName</a></td>
									</tr>
				                    <tr class='b-row-odd'>
				                        <td>Ship:</td><td>$victimShipTypeName ($victimShipGroupName)</td>
				                    </tr>
				                </table>";

				/* получение файла иконки по typeId
				 * select invTypes.typeID,invTypes.typeName,eveGraphics.icon from invTypes,eveGraphics where invTypes.graphicID = eveGraphics.graphicID; 
				 */

				$query = "SELECT api_kills_attackers.*, types1.typeName as shipTypeName, types2.typeName as weaponTypeName ".
					"FROM `api_kills_attackers` ".
					"left join invTypes as types1 on types1.typeID = api_kills_attackers.shipTypeID ".
					"left join invTypes as types2 on types2.typeID = api_kills_attackers.weaponTypeId ".
					"where api_kills_attackers.accountId = '$accountId' and api_kills_attackers.recordKillId = '$recordKillId' ".
					"order by api_kills_attackers.damageDone desc ";
				//echo $query;

				$involvedTable = "<table class='b-border'>";
				$qr = $db->query($query);
				while($row = $qr->fetch_assoc())
				{
					$attackerId = $row["characterId"];
					$attackerName = $row["characterName"];
					$attackerCorporationId = $row["corporationId"];
					$attackerCorporationName = $row["corporationName"];
					$attackerAllianceId = $row["allianceId"];
					$attackerAllianceName = $row["allianceName"];
					$finalBlow = $row["finalBlow"];
					$damageDone = $row["damageDone"];
					$shipTypeName = $row["shipTypeName"];
					$weaponTypeName = $row["weaponTypeName"];

					if($finalBlow)
						$finalBlow = "(Final blow)";
					else
						$finalBlow = "";
					$involvedTable .=
"
	<tr class='b-row-odd'>
		<td rowspan='5'><img src='http://img.eve.is/serv.asp?s=64&amp;c=$attackerId' alt='$attackerName'></td>
		<td>$attackerName $finalBlow</td></tr>
	<tr class='b-row-even'><td><a href='$this->corporation_processor&amp;corporationId=$attackerCorporationId'>$attackerCorporationName</a></td></tr>
	<tr class='b-row-odd'><td><a href='$this->alliance_processor&amp;allianceId=$attackerAllianceId'>$attackerAllianceName</a></td></tr>
	<tr class='b-row-even'><td>$shipTypeName</td></tr>
	<tr class='b-row-odd'><td>$weaponTypeName</td></tr>
	<tr class='b-row-even'><td>Damage done:</td>
		<td>$damageDone</td></tr>
";
					$page->Body .= "$attackerName, $attackerCorporationName, $attackerAllianceName<br>".
						"$finalBlow, $damageDone, $shipTypeName, $weaponTypeName<br><br>";
					//print_r($row);
				}
				$involvedTable .= "</table>";
				$qr->close();

				$query = "select ".
					"api_kills_items.qtyDropped, ".
					"api_kills_items.qtyDestroyed, ".
					"api_kills_items.hasChilds, ".
					"invFlags.flagText, ".
					"invTypes.typeName, ".
					"invTypes.basePrice ".
					"from api_kills_items ".
					"left join invFlags on api_kills_items.flag = invFlags.flagID ".
					"left join invTypes on api_kills_items.typeId = invTypes.typeID ".
					"where api_kills_items.accountId = '$accountId' and api_kills_items.recordKillId = '$recordKillId' ".
					"order by api_kills_items.flag desc;";
				//echo $query;
				$qr = $db->query($query);

				$summ = 0;
				$summDropped = 0;
				$summDestroyed = 0;

				$itemsTable = "<table class='b-border'>";
				$rowIndex = 0;
				while($row = $qr->fetch_assoc())
				{
					$qtyDropped = $row["qtyDropped"];
					$qtyDestroyed = $row["qtyDestroyed"];
					$hasChilds = $row["hasChilds"];
					$flagText = $row["flagText"];
					$typeName = $row["typeName"];
					$basePrice = $row["basePrice"];
					$summ += $basePrice;

					$priceDropped = $qtyDropped * $basePrice;
					$priceDestroyed = $qtyDestroyed * $basePrice;

					$summDropped += $priceDropped;
					$summDestroyed += $priceDestroyed;

					if(($rowIndex % 2) == 1)
						$rowClass = "b-row-even";
					else
						$rowClass = "b-row-odd";
					$rowIndex++;

					if($qtyDropped > 0)
						$itemsTable .= "<tr bgcolor='green'><td>$flagText</td><td>$typeName</td><td>$qtyDropped</td><td class='b-right'>$priceDropped</td></tr>";
					if($qtyDestroyed > 0)
						$itemsTable .= "<tr class='$rowClass'><td>$flagText</td><td>$typeName</td><td>$qtyDestroyed</td><td class='b-right'>$priceDestroyed</td></tr>";
				}
				$itemsTable .= "<tr><td colspan='3' class='b-right'>Total module loss:</td><td class='b-right'>$summDestroyed</td></tr>";
				$itemsTable .= "<tr bgcolor='green'><td colspan='3' class='b-right'>Total module drop:</td><td class='b-right'>$summDropped</td></tr>";
				$itemsTable .= "<tr><td colspan='3' class='b-right'>Ship loss:</td><td class='b-right'>$victimShipPrice</td></tr>";
				$totalLoss = $summDestroyed + $summDropped + $victimShipPrice;
				$itemsTable .= "<tr class='b-row-even'><td colspan='3' class='b-right'>Total loss:</td><td class='b-right'>$totalLoss</td></tr>";
				$itemsTable .= "</table>";
				$qr->close();

				$locationTable = sprintf("<table class='b-border b-widthfull'>
				                              <tr class='b-row-even'><td>Location:</td><td>%s</td></tr>
				                              <tr class='b-row-odd'><td>System security:</td><td>%s</td></tr>
				                              <tr class='b-row-even'><td>Total ISK loss:</td><td>%s</td></tr>
				                              <tr class='b-row-odd'><td>Total damage taken:</td><td>%s</td></tr>
				                          </table>",
				                          $solarSystemName,
				                          number_format($solarSystemSecurity, 2),
				                          $totalLoss,
				                          $victimDamageTaken);

				$page->Body = "<table>
				                   <tr class='b-top'><td>$victimTable</td><td>$locationTable</td></tr>
				                   <tr class='b-top'><td>$involvedTable</td><td>$itemsTable</td></tr>
				               </table>";
			}

			$db->close();

		}
	}
?>
