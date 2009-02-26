<?php
    class Api_IndustryJobs
    {
    	var $corpInfo;
    	var $result;
    	var $request_processor;
    	var $accountId;

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

			$this->request_processor = $_SERVER["PHP_SELF"] . "?mode=" . get_class($this);

			//print_r($this->result);
			if($this->result["error"] != "")
			{
				$page->Body = $this->result["error"];
			}
			else
			{
				$installedInSolarSystemId = null;
				if(isset($_REQUEST["installedInSolarSystemId"]))
				{
					$installedInSolarSystemId = $_REQUEST["installedInSolarSystemId"];
					if(preg_match("/^[\d]{1,10}$/", $installedInSolarSystemId) != 1)
						$installedInSolarSystemId = null;
				}
				if($installedInSolarSystemId == null)
					$this->ShowContainersList($page);
				if($installedInSolarSystemId != null)
					$this->ShowJobsInSolarSystem($page, $installedInSolarSystemId);
			}
		}
		function ShowContainersList($page)
		{
			$query = "SELECT jobs.installedInSolarSystemId, mapSolarSystems.solarSystemName
FROM api_industry_jobs AS jobs
left join mapSolarSystems on mapSolarSystems.solarSystemID = jobs.installedInSolarSystemId 
WHERE jobs.accountId = '{$this->accountId}'
GROUP BY jobs.installedInSolarSystemId ;";

			//echo $query;
			$db = OpenDB2();
			$qr = $db->query($query);
			if($qr)
			{
				$page->Body = "<table class='b-border b-widthfull' cellspacing='1' cellpadding='1'>";
				$page->Body .= "<tr class='b-table-caption'>
					<td>Системы, где установлены работы</td>
					</tr>";
				
				$index = 0;
				while($row = $qr->fetch_assoc())
				{
					if(($index % 2) == 1)
						$rowClass = "b-row-even";
					else
						$rowClass = "b-row-odd";
					$index++;

					$page->Body .= "<tr class='$rowClass'>
						<td><a href='{$this->request_processor}&amp;installedInSolarSystemId=$row[installedInSolarSystemId]'>$row[solarSystemName]</a></td>
						</tr>";
				}
				$page->Body .= "</table>";
				$qr->close();
			}
			$db->close();
		}
		function ShowJobsInSolarSystem($page, $installedInSolarSystemId)
		{
			$page->Body .= "<p>Список работ</p>";
			$page->Body .= "<table class='b-border b-widthfull' cellspacing='1' cellpadding='1'>";
			$page->Body .= "<tr class='b-table-caption'>\n
						<td>#</td>\n";
			$page->Body .= $page->WriteSorter(array (
				"jobId" => "Id",
				"installerName" => "Установщик",
				"outputTypeName" => "Выход",
				"containerTypeName" => "Место производства",
				"containerName" => "Место производства",
				"installedItemCopy" => "Оригинал",
				"completed" => "Завершено",
				"installTime" => "Установка",
				"beginProductionTime" => "Начало",
				"endProductionTime" => "Конец",
				"pauseProductionTime" => "Простой"
				));
			$page->Body .= "</tr>";
			$sorter = $page->GetSorter("jobId");

			$query = "select
			CASE
WHEN jobs.containerId
BETWEEN 66000000
AND 66015131
THEN (

SELECT s.stationName
FROM staStations AS s
WHERE s.stationID = jobs.containerId -6000001
)
WHEN jobs.containerId
BETWEEN 66015132
AND 67999999
THEN (

SELECT c.stationname
FROM api_outposts AS c
WHERE c.stationid = jobs.containerId -6000000
)
ELSE (

SELECT m.itemName
FROM mapDenormalize AS m
WHERE m.itemID = jobs.containerId
)
END AS containerName, " . 
			 	"jobs.*, " .
			 	"members.name as installerName, " .
			 	"invTypes.typeName as outputTypeName, " .
			 	"invTypes2.typeName as containerTypeName, " .
			 	"invFlags1.flagName as installedItemFlagName, " .
			 	"invFlags2.flagName as outputFlagName " .

				"from api_industry_jobs as jobs " . 
				"left join api_member_tracking as members on members.characterId = jobs.installerId " .
				"left join invTypes on invTypes.typeID = jobs.outputTypeId " .
				"left join invTypes as invTypes2 on invTypes2.typeID = jobs.containerTypeId " .
				"left join invFlags as invFlags1 on invFlags1.flagID = jobs.installedItemFlag " .
				"left join invFlags as invFlags2 on invFlags2.flagID = jobs.outputFlag " .
				"where jobs.accountId = '{$this->accountId}' and members.accountId = '{$this->accountId}' ".
				" and jobs.installedInSolarSystemId = $installedInSolarSystemId $sorter;";
			//echo $query;
			$db = OpenDB2();
			$qr = $db->query($query);
			if($qr)
			{
				$index = 0;
				while($row = $qr->fetch_assoc())
				{
					//if($row["containerName"] == null)
					//	$row["containerName"] = "Неизвестно где, containerId = $row[containerId]";
					if(($index % 2) == 1)
						$rowClass = "b-row-even";
					else
						$rowClass = "b-row-odd";
					$index++;

					if($row["installedItemCopy"] == 0)
						$installedItemCopy = "orig";
					else
						$installedItemCopy = "copy";

					$status = "?";
					if($row["completed"] == 0 && $row["completedStatus"] == 0)
						$status = "progress";
					if($row["completed"] == 1 && $row["completedStatus"] == 1)
						$status = "completed";
					if($row["completed"] == 1 && $row["completedStatus"] == 0)
						$status = "failed";
					if($row["completed"] == 0 && $row["completedStatus"] == 1)
						$status = "??";
					$page->Body .= "<tr class='$rowClass'>" .
						//"<td><a href='{$this->request_processor}&amp;installedInSolarSystemId=$installedInSolarSystemId&amp;jobId=$row[jobId]'>$row[jobId]</a></td>" .
						"<td>$index</td>" .
						"<td>$row[jobId]</td>" .
						"<td>$row[installerName]</td>" .
						"<td>$row[outputTypeName]</td>" .
						"<td>$row[containerTypeName]</td>" .
						"<td>$row[containerName]</td>" .
						"<td>$installedItemCopy</td>" .
						"<td>$status</td>" .
						//"<td>$row[installedItemFlagName]</td>" .
						//"<td>$row[outputFlagName]</td>" .
						"<td>$row[installTime]</td>" .
						"<td>$row[beginProductionTime]</td>" .
						"<td>$row[endProductionTime]</td>" .
						"<td>$row[pauseProductionTime]</td>" .
						"</tr>";
				}
				$page->Body .= "</table>";
				$qr->close();
			}
			$db->close();
		}
    }
?>
