<?php
    class Api_MemberTracking
    {
    	var $request_processor;
    	var $accountId;
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

			$this->accountId = $User->GetAccountId();

			$this->request_processor = $_SERVER["PHP_SELF"] . "?mode=" . get_class($this);
			
			$characterId = null;
			if(isset($_GET["characterId"]))
				$characterId = $_GET["characterId"];

			if($characterId == null)
				$this->ShowMembersList($page);
			else
				$this->ShowMemberInfo($page, $characterId);
		}
		function ShowMemberInfo($page, $characterId)
		{
			$db = OpenDB2();

			$comedit = null;
			if(isset($_POST["comedit"]))
			{
				$comedit = $_POST["comedit"];
				$query = sprintf(
					"update api_member_tracking set comments = '%s' where accountId = '{$this->accountId}' and characterId = '$characterId';",
					$db->real_escape_string($comedit));
				$db->query($query);
			}

			$query = "select * from api_member_tracking where accountId = '{$this->accountId}' and characterId = '$characterId';";
			$qr = $db->query($query);
			if($row = $qr->fetch_assoc())
			{
				$page->Body .= "<p>";
				$page->Body .= "Имя: $row[name]<br>";
				$page->Body .= "Дата приёма: $row[startDateTime]<br>";
				$history = $row["joinlog"];
				if($history == null || $history == "")
				{
					$history = "нет";
				}
				else
				{
					$history = str_replace("#out", " Вышел ", $history);
					$history = str_replace("#joined", " Принят ", $history);
				}
				$page->Body .= "История приёма: $history<br>";
				$page->Body .= "<form action='{$this->request_processor}&amp;characterId=$characterId' method='post'>
<label for='comedit'>Комментарии</label>:<br>
<textarea rows='7' cols='40' dir='ltr' id='comedit' name='comedit'>$row[comments]</textarea>
<input type='submit' name='submit_comment' value='Применить'>
</form>";
				$page->Body .= "</p>";
			}
			$qr->close();

			$db->close();
		}
		function ShowMembersList($page)
		{
			$db = OpenDB2();

			//подсчёт числа подходящих строк
			$qr = $db->query("select count(*) as _count_ from api_member_tracking where accountId = '{$this->accountId}';");
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
		<td></td>
    </tr>";

			$sorter = $page->GetSorter("name");
			//отдельно количество можно не запрашивать, т.к. есть свойство affected_rows
			//нет. оказалось надо отдельно, т.к. число записей нужно для определения покаызываемой страницы
			$query = "select * from api_member_tracking where accountId = '{$this->accountId}' $sorter limit $pages->start, $pages->count;";
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
		<td><a href={$this->request_processor}&amp;characterId=$row[characterId]><img src='images/b_edit.png'></a></td>
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
