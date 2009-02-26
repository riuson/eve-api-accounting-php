<?php
	class Api_RefTypes
	{
		public function PreProcess($page)
		{
			$Api = new ApiInterface("");

			//$Api->UpdateRefTypes();

			//вывод элемента постраничного просмотра
			$db = OpenDB2();

			//print_r($qr);
			$page->Body = "
				<table class='b-border b-widthfull'>\n
					<tr class='b-table-caption'>\n
						<td class='b-center'>#</td>\n";
						//<td>errorCode</td>\n
						//<td>errorText</td>\n";
			$page->Body .= $page->WriteSorter(array (
				"refTypeId" => "Код",
				"refTypeName" => "Название"));
			$page->Body .= "
					</tr>\n";
			$sorter = $page->GetSorter("refTypeId");
			$qr = $db->query("select * from api_reftypes $sorter ;");

			$rowIndex = 0;
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
							<td class='b-center'>$row[refTypeId]</td>\n
							<td>$row[refTypeName]</td>\n
						</tr>\n";
			}
			
			$page->Body .= "
				</table>\n
			";

			$qr->close();
			$db->close();
		}
	}
?>
