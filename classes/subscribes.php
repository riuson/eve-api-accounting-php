<?php
    include_once("api2.php");
    include_once("database.php");
    include_once "pageselector.php";

	class Subscribes
	{
		var $rowIndex;
		var $request_processor;

		public function PreProcess($page)
		{
			$User = User::CheckLogin();
			$User->CheckAccessRights(get_class($this), true);

			$Api = new ApiInterface($User->GetAccountId());
			$Api->userId = $User->GetUserId();
			$Api->apiKey = $User->GetApiKey();
			$Api->characterId = $User->GetCharacterId();

			//$accountId = $User->parameters["accountId"];
			$accountId = $User->GetAccountId();

			//$Api->UpdateOutposts();
			$this->request_processor = $_SERVER["PHP_SELF"] . "?mode=" . get_class($this);

			//список режимов, на которые можно подписаться
			$modes = Page::GetModes();
			$access_fields = array();
			$index = 0;
			foreach ($modes as $k=>$v)
			{
				if(preg_match("/(\W|^)$k(\W|$)/", Page::GetSubscribeLinks()) == 1)
					$access_fields[$k] = $v;
				$index ++;
			}
			$fields_count = count($access_fields) + 1;

			$db = OpenDB2();

			//если форма была отправлена, записываем данные в базу
			if(isset($_POST["subscribe_submit"]))
			{
				//получение списка ведомых юзеров
				$query = "select * from api_subscribes where accountId = '$accountId';";
				$qr = $db->query($query);

				while($row = $qr->fetch_assoc())
				{
					$query = "";
					foreach($access_fields as $access_field => $access_fieldName)
					{
						$fieldname = $access_field . "@@@" . str_replace(".", "_", $row["email"]);

						if(isset($_POST[$fieldname]))
						{
							if($query != "")
								$query = $query . ",";
							$query = $query . "$access_field";
						}
					}
					$query = "update api_subscribes set modes = '$query' where accountId = '$accountId' and recordId = '$row[recordId]';";
					//echo $query;
					$db->query($query);
				}
				$qr->close();
			}
			$messageadd = "";
			if(isset($_POST["addmail_submit"]))
			{
				if(isset($_POST["new_email"]))
				{
					$email = $_POST["new_email"];
					if(preg_match("/^([a-zA-Z0-9_\-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/i", $email) != 0)
					{
						$query = sprintf(
							"insert ignore into api_subscribes set recordId = '%s', accountId = '%s', email='%s', modes = '';",
							GetUniqueId(),
							$accountId,
							mysql_escape_string($email));
						//echo $query;
						$db->query($query);
					}
					else
						$messageadd = "Указанный email не прошёл проверку регулярным выражением";
				}
			}

			if(isset($_POST["drop_address"]))
			{
				$email = $_POST["drop_address"];
				if(preg_match("/^([a-zA-Z0-9_\-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/i", $email) != 0)
				{
					$query = sprintf(
						"delete from api_subscribes where accountId = '%s' and email='%s';",
						$accountId,
						mysql_escape_string($email));
					//echo $query;
					$db->query($query);
				}
				else
					$messageadd = "Удаляемый email не прошёл проверку регулярным выражением";
			}

			$page->Body .= "
<form method='post' name='add_email' action='$this->request_processor'>
	<input type='text' name='new_email'>
	<input type='submit' name='addmail_submit' value='Добавить новый адрес'><br>
	$messageadd
</form>
<br>";
			$page->Body .= "<form method='post' name='subscribe' action='$this->request_processor'>
<table class='b-widthfull b-border'>
	<tr class='b-table-caption'><td>E-mail</td>";
			foreach($access_fields as $access_field => $access_fieldName)
			{
				$page->Body .= "<td>$access_fieldName</td>";
			}
			$page->Body .= "</tr>";

			//получение списка ведомых юзеров
			$query = "select * from api_subscribes where accountId = '$accountId' order by email;";
			$qr = $db->query($query);

			$rowIndex = 0;

			while($row = $qr->fetch_assoc())
			{
				if(($rowIndex % 2) == 1)
					$rowClass = "b-row-even";
				else
					$rowClass = "b-row-odd";
				$rowIndex++;

				$page->Body .= "<tr class='$rowClass'>
					<td>
						<button name='drop_address' value='$row[email]'><img src='images/b_drop.png'></button> 
						$row[email]
					</td>";
				$states = $row["modes"];
				//button_cancel = '<button ' + button_cancel_click + '><img src=\'images/b_drop.png\'></button>';
				foreach($access_fields as $access_field => $access_fieldName)
				{
					$checked = "";
					if(preg_match("/(\W|^)$access_field(\W|$)/", $states) != 0)
						$checked = "checked";
					$page->Body .= "<td><input type='checkbox' $checked name='$access_field@@@" . str_replace(".", "_",  $row["email"]) . "' value='$access_field'></td>";
				}
				$page->Body .= "</tr>";
			}
			$qr->close();
			
			$page->Body .= "
	</table>
	<input type='reset' name='subscribe_reset' value='Сбросить'>
	<input type='submit' name='subscribe_submit' value='Применить'>
</form>";

			$db->close();
		}
		static function SendMail($email, $subject, $message)
		{
			//$subject = "ea.mylegion.ru";

			$header="Content-type: text/html; charset=\"utf-8\"\r\n";
			$header.="From: ea.mylegion.ru <service@ea.mylegion.ru>\r\n";
			$header.="Subject: $subject\r\n";
			$header.="Content-type: text/html; charset=\"utf-8\"\r\n";
			//$message="<body>" . $message . "</body>";
			//echo "<p>To: $email</p><p>Subject: $subject</p><p>$message</p><p>Headers: $header</p>";
			mail($email, $subject, $message, $header);
		}
	}
	//service@ea.mylegion.ru
	//4mrdncy5j138408
?>
