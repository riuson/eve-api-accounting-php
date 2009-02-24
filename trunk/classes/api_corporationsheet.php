<?php
    class Api_CorporationSheet
    {
    	var $corpInfo;
    	var $result;
    	var $privateInfo;
    	var $request_processor;

		public function __construct()
		{
        	$this->alliance_processor = $_SERVER["PHP_SELF"] . "?mode=Api_Alliances";// . get_class($this);
        	$this->privateInfo = false;
		}
        function PreProcess($page)
        {
        	$this->privateInfo = false;
        	if(isset($_REQUEST["corporationId"]))
        	{
        		$corpId = $_REQUEST["corporationId"];

				$Api = new ApiInterface("");
				$this->corpInfo = $Api->GetCorpCorporationSheet($corpId);
			}
			else
			{
				$this->privateInfo = true;
				$User = User::CheckLogin();
				$User->CheckAccessRights(get_class($this), true);
				$Api = new ApiInterface($User->GetAccountId());
				$Api->userId = $User->GetUserId();
				$Api->apiKey = $User->GetApiKey();
				$Api->characterId = $User->GetCharacterId();
				$this->corpInfo = $Api->GetCorpCorporationSheet();
			}
			$page->Body = $this->GetCorporationSheetTable($this->corpInfo);

			//print_r($this->result);

		}
		function GetCorporationSheetTable($corporationInfo)
		{
			$str = "";
			if($corporationInfo["error"] != "")
			{
				$str = $corporationInfo["error"];
			}
			else
			{
				$corpId = $corporationInfo["corporationId"];
				$corpName = $corporationInfo["corporationName"];
				$ticker = $corporationInfo["ticker"];
				$ceoId = $corporationInfo["ceoId"];
				$ceoName = $corporationInfo["ceoName"];
				$allyId = $corporationInfo["allianceId"];
				$allyName = $corporationInfo["allianceName"];
				$stationName = $corporationInfo["stationName"];
				$description = $corporationInfo["description"];
				$url = $corporationInfo["url"];
				$taxRate = $corporationInfo["taxRate"];
				$memberCount = $corporationInfo["memberCount"];
				$memberLimit = $corporationInfo["memberLimit"];
				$shares = $corporationInfo["shares"];
				$str = "
<table class='b-border b-widthfull' cellspacing='1' cellpadding='1'>
<tr><td>Корпорация</td><td><img src='corplogo:$corpId' alt='$corpName logo'> $corpName [$ticker]</td></tr>
<tr><td>CEO</td><td><img src='http://img.eve.is/serv.asp?s=64&amp;c=$ceoId' alt='$ceoName portrait'> $ceoName</td></tr>
<tr><td>Альянс</td><td><a href='{$this->alliance_processor}&amp;allianceId=$allyId'><img src='alliancelogo:$allyId' alt='$allyName logo'> $allyName</a></td></tr>
<tr><td>Станция</td><td>$stationName</td></tr>
<tr><td>Описание</td><td>$description</td></tr>
<tr><td>URL</td><td><a href='$url'>$url</a></td></tr>
<tr><td>Tax rate</td><td>$taxRate</td></tr>
<tr><td>Численность</td><td>$memberCount / $memberLimit</td></tr>
<tr><td>Shares</td><td>$shares</td></tr>";

				if($this->privateInfo)
				{
					$str .= "
<tr>
	<td colspan='2'>
	<table class='b-widthfull'>
		<tr class='b-table-caption'><td></td><td class='b-center'>Divisions</td><td class='b-center'>Wallet Divisions</td></tr>";
					for($i = 1000; $i < 1007; $i++)
					{
						$str .= sprintf("<tr><td class='b-center'>%s</td><td class='b-center'>%s</td><td class='b-center'>%s</td></tr>",
							$i,
							htmlentities($corporationInfo["divisions"][$i]),
							htmlentities($corporationInfo["walletDivisions"][$i]));
					}
					$str .= "</table>
	</td>
</tr>";
				}
				$str .= "
</table>";
			}
			return $str;
		}
    }
?>
