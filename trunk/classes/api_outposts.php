<?php
    include_once("api2.php");
    include_once("database.php");
    include_once "pageselector.php";

	class Api_Outposts
	{
		public function PreProcess($page)
		{
			$Api = new ApiInterface("");

			//$Api->UpdateOutposts();

			//вывод элемента постраничного просмотра

			$db = OpenDB2();
			$qr = $db->query("select count(*) as _count_ from api_outposts;");
			$row = $qr->fetch_assoc();
			$recordsCount = $row["_count_"];
			$qr->close();

			$pages = new PageSelector();
			$page->Body = $pages->Write($recordsCount);


			$page->Body .= "
				<table class='b-border b-widthfull'>\n
					<tr class='b-table-caption'>\n
						<td>#</td>\n";
			$page->Body .= $page->WriteSorter(array (
				"stationName" => "Станция",
				"solarSystemName" => "Система",
				"corporationName" => "Корпорация"));
			$page->Body .= "
					</tr>\n";

			$sorter = $page->GetSorter("corporationName");

			$query = "SELECT api_outposts . * , mapSolarSystems.solarSystemName 
FROM `api_outposts` 
LEFT JOIN mapSolarSystems ON ( api_outposts.`solarSystemId` = mapSolarSystems.`solarSystemId` ) 
$sorter limit $pages->start, $pages->count;";

			//$qr = ExecuteQuery($query);
			$qr = $db->query($query);

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
							<td>$rowIndex</td>\n
							<td>$row[stationName]</td>\n
							<td>$row[solarSystemName]</td>\n
							<td><a href='index.php?mode=api_corporationsheet&amp;corporationId=$row[corporationId]'>$row[corporationName]</a></td>\n
						</tr>\n";
			}
			$db->close();

			$page->Body .= "
				</table>\n
			";
		}
	}
?>
