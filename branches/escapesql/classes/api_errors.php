<?php
	class Api_Errors
	{
		public function PreProcess($page)
		{
			$Api = new ApiInterface("");

			//$Api->UpdateErrors();

			//вывод элемента постраничного просмотра
			$dblink = OpenDB2();

			//print_r($qr);
			$page->Body = "
				<table class='b-border b-widthfull'>\n
					<tr class='b-table-caption'>\n
						<td class='b-center'>#</td>\n";
			$page->Body .= $page->WriteSorter(array (
				"errorCode" => "Код",
				"errorText" => "Текст ошибки"));
			$page->Body .= "
					</tr>\n";

			$sorter = $page->GetSorter("errorCode");
			$qr = $dblink->query("select * from api_errors $sorter ;");

			$rowIndex = 0;

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
							<td class='b-center'>$row[errorCode]</td>\n
							<td>$row[errorText]</td>\n
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
