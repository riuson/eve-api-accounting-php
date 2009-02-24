<?php
    include_once("api2.php");
    include_once("database.php");
    include_once "pageselector.php";

	class Api_Visitors
	{
		public function PreProcess($page)
		{
			//вывод элемента постраничного просмотра
			$dblink = OpenDB2();
			$qr = $dblink->query("select count(*) as _count_ from api_visitors;");
			$row = $qr->fetch_assoc();
			$recordsCount = $row["_count_"];
			$qr->close();

			$pages = new PageSelector();
			$page->Body = $pages->Write($recordsCount);

			//print_r($qr);
			$page->Body .= "
				<table class='b-border b-widthfull'>\n
					<tr class='b-table-caption'>\n
						<td>#</td>\n
						<td>Дата</td>\n
						<td>Адрес</td>\n
						<td>Агент</td>\n";
			//$page->Body .= $page->WriteSorter(array (
			//	"shortName" => "Тикер",
			//	"name" => "Название",
			//	"memberCount" => "Состав",
			//	"startDate" => "Основан"));
			$page->Body .= "
					</tr>\n";

			$sorter = $page->GetSorter("name");
			$qr = $dblink->query("SELECT * FROM `api_visitors` group by address, date(_date_) order by _date_ desc limit $pages->start, $pages->count;");

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
							<td>$row[_date_]</td>\n
							<td>$row[address]</td>\n
							<td>$row[agent]</td>\n
						</tr>\n";
			}
			
			$page->Body .= "
				</table>\n
			";

			$qr->close();
			$dblink->close();
		}
	}
?>
