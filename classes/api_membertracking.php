<?php
    class Api_MemberTracking
    {
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
			$members = $Api->UpdateMemberTracking();

			$accountId = $User->GetAccountId();

			$request_processor = $_SERVER["PHP_SELF"] . "?mode=" . get_class($this);

			$db = OpenDB2();

			//подсчёт числа подходящих строк
			$qr = $db->query("select count(*) as _count_ from api_member_tracking where accountId = '$accountId';");
			$row = $qr->fetch_assoc();
			$recordsCount = $row["_count_"];
			$qr->close();


			$pages = new PageSelector();
			$page->Body = $pages->Write($recordsCount);

			//$page->Body .= $query;

			$page->Body .= "
<table class='b-border b-widthfull'>
    <tr class='b-table-caption'>
		<td>#</td>\n";
			$page->Body .= $page->WriteSorter(array (
				"name" => "Имя",
				"startDateTime" => "Дата приёма",
				"base" => "База",
				"logonDateTime" => "Логон",
				"logoffDateTime" => "Логофф",
				"location" => "Местоположение",
				"shipType" => "Корабль"
				));
			$page->Body .= "
    </tr>";

			$sorter = $page->GetSorter("name");
			//отдельно количество можно не запрашивать, т.к. есть свойство affected_rows
			//нет. оказалось надо отдельно, т.к. число записей нужно для определения покаызываемой страницы
			$query = "select * from api_member_tracking where accountId = '$accountId' $sorter limit $pages->start, $pages->count;";
			//echo $query;
			$qr = $db->query($query);

    		$rowIndex = $pages->start;
			while($row = $qr->fetch_assoc())
			{
				if(($rowIndex % 2) == 1)
					$rowClass = "b-row-even";
				else
					$rowClass = "b-row-odd";

				$page->Body .= "
	<tr class='$rowClass'>
		<td>$rowIndex</td>
		<td>$row[name]</td>
		<td>$row[startDateTime]</td>
		<td>$row[base]</td>
		<td>$row[logonDateTime]</td>
		<td>$row[logoffDateTime]</td>
		<td>$row[location]</td>
		<td>$row[shipType]</td>
	</tr>
";
				$rowIndex++;
			}
    		$page->Body .= "
</table>
";
			$page->Body .= $pages->Write($recordsCount);
			$qr->close();
			$db->close();

		}
	}
?>
