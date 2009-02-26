<?php
    class EditAccess
    {
        function PreProcess($page)
        {
    		$User = User::CheckLogin();

			//если не мастер, отослать на главную страницу
			if($User->parameters["master"] != "")
				header("Location:index.php?mode=Index");

			$request_processor = $_SERVER["PHP_SELF"] . "?mode=" . get_class($this);
			
			$accountId = $User->parameters["accountId"];
			$characterName = $User->parameters["characterName"];


			//получение списка полей с права доступа
			//
			$modes = Page::GetModes();
			$access_fields = array();
			$index = 0;
			foreach ($modes as $k=>$v)
			{
				if(preg_match("/(\W|^)$k(\W|$)/", Page::GetLimitedLinks()) == 1)
					$access_fields[$k] = $v;
				$index ++;
			}
			$fields_count = count($access_fields) + 1;

			$db = OpenDB2();

			//если форма была отправлена, записываем данные в базу
			if(isset($_POST["form_submit"]))
			{
				//получение списка ведомых юзеров
				$query = sprintf("select accountId, characterName from api_users where master = '%s';",
					$db->real_escape_string($characterName));
				$qr = $db->query($query);

				while($row = $qr->fetch_assoc())
				{
					$query = "";
					foreach($access_fields as $access_field => $access_fieldName)
					{
						$fieldname = $access_field . "@" . str_replace(" ", "_", $row["characterName"]);

						if(isset($_POST[$fieldname]))
						{
							if($query != "")
								$query = $query . ",";
							$query = $query . "$access_field";
						}
					}
					$query = "update api_users set access = '$query' where accountId = '$row[accountId]';";
					//echo $query;
					$db->query($query);
				}
				$qr->close();
			}

			//$users_count = mysql_num_rows($qr);

			//print_r(mysql_fetch_array($qr));
			$page->Body = "
				<form method='post' name='registration' action='$request_processor'>
					<table class='b-border b-widthfull'>";
/*						<tr class='b-table-caption'>
							<td colspan='$fields_count' class='login_caption'>EA Accounting: Изменение прав доступа</td>
						</tr>\n
						**/
			$page->Body .= "<tr class='b-table-caption'><td>Ник</td>";
			foreach($access_fields as $access_field => $access_fieldName)
			{
				$page->Body .= "<td>$access_fieldName</td>";
			}
			$page->Body .= "</tr>";

			//получение списка ведомых юзеров
			$query = sprintf("select * from api_users where master = '%s';",
				$db->real_escape_string($characterName));
			$qr = $db->query($query);

			$rowIndex = 0;

			while($row = $qr->fetch_assoc())
			{
				if(($rowIndex % 2) == 1)
					$rowClass = "b-row-even";
				else
					$rowClass = "b-row-odd";
				$rowIndex++;

				$page->Body .= "<tr class='$rowClass'><td>$row[characterName]</td>";
				$states = $row["access"];
				foreach($access_fields as $access_field => $access_fieldName)
				{
					$checked = "";
					if(preg_match("/(\W|^)$access_field(\W|$)/", $states) != 0)
						$checked = "checked";
					$page->Body .= "<td><input type='checkbox' $checked name='$access_field@" . str_replace(" ", "_",  $row["characterName"]) . "' value='$access_field'></td>";
				}
				$page->Body .= "</tr>";
			}
			$qr->close();
			$db->close();
			$page->Body .= "
	</table>
	<input type='reset' name='form_reset' value='Сбросить'>
	<input type='submit' name='form_submit' value='Применить'>
</form>";
		}
	}
	//phpinfo();
?>
