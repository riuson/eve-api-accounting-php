<?php
    include_once("api2.php");
    include_once("database.php");
    include_once "pageselector.php";

	class Api_Alliances
	{
		public function PreProcess($page)
		{
			$Api = new ApiInterface("");

			//$Api->UpdateAlliances();

			$request_processor = $_SERVER["PHP_SELF"] . "?mode=" . get_class($this);
			
			$allianceId = null;
			if(isset($_REQUEST["allianceId"]))
			{
				$allianceId = $_REQUEST["allianceId"];
				if(preg_match("/^\d+$/", $allianceId) == 0)
					$allianceId = null;
			}

			if($allianceId == null)
			{
				//вывод элемента постраничного просмотра
				$dblink = OpenDB2();
				$qr = $dblink->query("select count(*) as _count_ from api_alliances;");
				$row = $qr->fetch_assoc();
				$recordsCount = $row["_count_"];
				$qr->close();
				//$stmt =  $dblink->prepare("select count(*) as _count_ from api_alliances;");
				//$stmt->execute();
				//$stmt->bind_result($recordsCount);
				//$stmt->fetch();
				//$stmt->close();
				
				

				$pages = new PageSelector();
				$page->Body = $pages->Write($recordsCount);

				//print_r($qr);
				$page->Body .= "
					<table class='b-border b-widthfull'>\n
						<tr class='b-table-caption'>\n
							<td class='b-center'>#</td>\n";
				$page->Body .= $page->WriteSorter(array (
					"shortName" => "Тикер",
					"name" => "Название",
					"memberCount" => "Состав",
					"startDate" => "Основан"));
				$page->Body .= "
						</tr>\n";

				$sorter = $page->GetSorter("name");
				$qr = $dblink->query("select * from api_alliances $sorter limit $pages->start, $pages->count;");

				$rowIndex = $pages->start;
				$rowClass = "even";
				while($row = $qr->fetch_assoc())
				{
					if(($rowIndex % 2) == 1)
						$rowClass = "b-row-even";
					else
						$rowClass = "b-row-odd";
					$rowIndex++;

					$page->Body .= "
							<tr class='$rowClass'>\n
								<td class='b-center'>$rowIndex</td>\n
								<td class='b-center'>$row[shortName]</td>\n
								<td><a href='$request_processor&amp;allianceId=$row[allianceId]'>$row[name]</a></td>\n
								<td class='b-center'>$row[memberCount]</td>\n
								<td class='b-center'>$row[startDate]</td>\n
							</tr>\n";
				}
				
				$page->Body .= "
					</table>\n
				";

				$qr->close();
				$dblink->close();
			}
			else
			{
				include_once "api_corporationsheet.php";
				$corpSheet = new Api_CorporationSheet($page);
				//вывод элемента постраничного просмотра
				$dblink = OpenDB2();
				$qr = $dblink->query("select count(*) as _count_ from api_corporations where allianceId = $allianceId;");
				$row = $qr->fetch_assoc();
				$recordsCount = $row["_count_"];
				$qr->close();
				//$stmt =  $dblink->prepare("select count(*) as _count_ from api_alliances;");
				//$stmt->execute();
				//$stmt->bind_result($recordsCount);
				//$stmt->fetch();
				//$stmt->close();
				
				

				$pages = new PageSelector();
				$page->Body = $pages->Write($recordsCount);

				//print_r($qr);
				$qr = $dblink->query("select * from api_corporations where allianceId = $allianceId limit $pages->start, $pages->count;");

				$rowIndex = $pages->start;
				$rowClass = "even";
				while($row = $qr->fetch_assoc())
				{
					if(($rowIndex % 2) == 1)
						$rowClass = "b-row-even";
					else
						$rowClass = "b-row-odd";
					$rowIndex++;

					//$page->Body .= $row["corporationId"] . "<br>";
					$page->Body .= "<br>";

					$corpInfo = $Api->GetCorpCorporationSheet($row["corporationId"]);
					$page->Body .= "<div class='$rowClass b-widthfull'>";
					$page->Body .= $corpSheet->GetCorporationSheetTable($corpInfo);
					$page->Body .= "</div>";
				}

				$qr->close();
				$dblink->close();
			}
		}
	}
?>
