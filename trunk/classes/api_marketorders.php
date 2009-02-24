<?php
    class Api_MarketOrders
    {
    	var $result;
    	var $request_processor;
    	var $accountId;
    	var $corpInfo;

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
				$this->corpInfo = $Api->GetCorpCorporationSheet();
				
			//}

			$this->request_processor = $_SERVER["PHP_SELF"] . "?mode=" . get_class($this);

			$orderStates = array(
				0 => "open / active",
				1 => "closed",
				2 => "expired / fullfilled",
				3 => "cancelled",
				4 => "pending",
				5 => "character deleted"
				);

			//print_r($this->result);
			if($this->result["error"] != "")
			{
				$page->Body = $this->result["error"];
			}
			else
			{
				$page->Body = "
					<table class='b-border b-widthfull'>\n
						<tr class='b-table-caption'>\n
							<td>#</td>\n";
				$page->Body .= $page->WriteSorter(array (
					"bid" => "Buy/Sell",
					"stationName" => "Станция",
					"characterName" => "Пилот",
					"typeName" => "Вещь",
					"volEntered" => "Количество",
					"orderState" => "Состояние",
					"range" => "Дистанция",
					"accountKey" => "Кошелёк",
					"escrow" => "Escrow",
					"price" => "Цена/1ед.",
					"issued" => "Начало",
					"duration" => "Длительность"));
				$page->Body .= "
						</tr>\n";

				$sorter = $page->GetSorter("orderId");

				$query = "SELECT orders.*, staStations.stationName, members.name as characterName, invTypes.typeName
FROM api_market_orders AS orders
left join staStations on staStations.stationID = orders.stationId
left join api_member_tracking as members on members.characterId = orders.charId
left join invTypes on invTypes.typeID = orders.typeId
WHERE orders.accountId = '{$this->accountId}' and members.accountId = '{$this->accountId}' $sorter;";

				//echo $query;
				$db = OpenDB2();
				$qr = $db->query($query);
				if($qr)
				{


					$wallets = $this->corpInfo["walletDivisions"];
					$index = 0;
					while($row = $qr->fetch_assoc())
					{
						if(($index % 2) == 1)
							$rowClass = "b-row-even";
						else
							$rowClass = "b-row-odd";
						$index++;

						$volOrdered = $row["volEntered"] - $row["volRemaining"];
						$range = "region";

						if($row["range"] == -1)
							$range = "station";
						else if ($row["range"] == 0)
							$range = "solar system";
						else if ($row["range"] >= 1 && $row["range"] < 32767)
							$range = $row["range"] . " jump";

						$walletName = $wallets[$row["accountKey"]];

						if($row["bid"] == 0)
							$bid = "sell";
						else
							$bid = "buy";

						$page->Body .= "<tr class='$rowClass'>" .
							"<td>$index</td>" .
							//"<td>$row[orderId]</td>" .
							"<td>$bid</td>" .
							"<td>$row[stationName]</td>" .
							"<td>$row[characterName]</td>" .
							"<td>$row[typeName]</td>" .
							"<td>$volOrdered/$row[volEntered]</td>" .
							"<td>{$orderStates[$row['orderState']]}</td>" .
							"<td>$range</td>" .
							"<td>[$row[accountKey]] $walletName</td>" .
							"<td>$row[escrow]</td>" .
							"<td>$row[price]</td>" .
							"<td>$row[issued]</td>" .
							"<td>$row[duration]</td>" .
							"</tr>";
					}
					$qr->close();
				}
				$db->close();
				$page->Body .= "</table>";
			}
		}
    }
?>
