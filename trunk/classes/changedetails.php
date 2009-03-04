<?php
    include_once("database.php");

	class ChangeDetails
	{
		public function PreProcess($page)
		{
			$User = User::CheckLogin();
			//print_r($_POST);
			//echo "<br>";
			//print_r($User);
			//echo "<br>";
			$accountId = $User->parameters["accountId"];

			$login = $User->parameters["login"];
			$password = $User->parameters["password"];
			$email = $User->parameters["email"];
			$characterName = $User->parameters["characterName"];

			$userId = $User->parameters["userId"];
			$apiKey = $User->parameters["apiKey"];
			$characterId = $User->parameters["characterId"];
			
			$master = $User->parameters["master"];

			$message = "";
			if(isset($_POST["form_submit"]))
			{
				$User->InitFromRegisterFormPost();
				//print_r($User);
				//echo "<br>";
				$accountId = $User->parameters["accountId"];

				$login = $User->parameters["login"];
				$password = $User->parameters["password"];
				$email = $User->parameters["email"];
				$characterName = $User->parameters["characterName"];

				$userId = $User->parameters["userId"];
				$apiKey = $User->parameters["apiKey"];
				$characterId = $User->parameters["characterId"];
				
				$master = $User->parameters["master"];
				//print_r($User->parameters);
				

				$message = "";
				if($login == "")
					$message = "Не указан логин";
				else
					if($password == "")
						$message = "Не указан пароль";
					else
						if($email == "")
							$message = "Не указана почта";
						else
							if($characterName == "")
								$message = "Не указан characterName";

				//$edit_access = "";//ссылка на страницу редактирования прав доступа ведомых аккаунтов

				if($master == "")//если master
				{
					if($userId == "")
						$message = "Не указан userId";
					else
						if($apiKey == "")
							$message = "Не указан apiKey";
						else
							if($characterId == "")
								$message = "Не указан characterId";
					
					//$edit_access = "<a href='edit_access.php'>Права доступа</a>";
				}
				$db = OpenDB2();
				if($message == "")
				{
					$query = sprintf("select count(*) as _count_ from api_users where login = '%s' and accountId <> '%s';",
						$db->real_escape_string($login),
						$db->real_escape_string($accountId));
					//$qr = ExecuteQuery($query);
					//если результат не false, то строка в базе найдена и юзера с таким логином зарегить нельзя
					//echo $query;
					//$qr = mysql_fetch_array($qr);
					$qr = $db->query($query);
					if($row = $qr->fetch_assoc())
					{
						if($row["_count_"] > 0)
							$message = "Логин уже кем-то занят";
					}
					$qr->close();
				}
				if($message == "" && $characterId != 0)
				{
					$query = sprintf("select count(*) as _count_ from api_users where characterId = '%s' and accountId <> '%s';",
						$db->real_escape_string($characterId),
						$db->real_escape_string($accountId));
					//$query = "select * from api_users where characterId = '$characterId' and accountId <> '$accountId';";
					//$qr = ExecuteQuery($query);
					//echo $query;
					$qr = $db->query($query);
					if($row = $qr->fetch_assoc())
					{
						if($row["_count_"] > 0)
							$message = "characterId уже кем-то занят";
					}
					$qr->close();
				}
				if($message == "")
				{
					$query = sprintf("select count(*) as _count_ from api_users where characterName = '%s' and accountId <> '%s';",
						$db->real_escape_string($characterName),
						$db->real_escape_string($accountId));
					//$query = "select * from api_users where characterName = '$characterName' and accountId <> '$accountId';";
					//$qr = ExecuteQuery($query);
					//echo $query;
					$qr = $db->query($query);
					if($row = $qr->fetch_assoc())
					{
						if($row["_count_"] > 0)
							$message = "characterName уже кем-то занят";
					}
					$qr->close();
				}
				$db->close();

				//если сообщений с ошибками нет, пробуем зарегить юзера
				if($message == "")
				{
					//$query = "update api_users set login = '$login', password = md5('$password'), email = '$email', 
					//$User->parameters["login"] = $login;
					//$User->parameters["password"] = $password;
					//$User->parameters["email"] = $email;
					//$User->parameters["characterName"] = $characterName;

					//$User->parameters["userId"] = $userId;
					//$User->parameters["apiKey"] = $apiKey;
					//$User->parameters["characterId"] = $characterId;

					//$User->parameters["master"] = $master;

					if($User->ChangeInfo() == true)
					{
						if($User->CheckUserRegistered())
						{
							$_SESSION["User"] = $User;
							header("Location:index.php");
							//$message = "Данные изменены";
						}
						else
						{
							$message = "Не удалось обновить регистрацию";
						}
					}
					else
					{
						$message = "Не удалось изменить данные";
					}
				}
			}
			//echo $message;

			/*$User = User::CheckLogin();

			$accountId = $User->parameters["accountId"];
			$login = $User->parameters["login"];
			$password = $User->parameters["password"];
			$email = $User->parameters["email"];
			$characterName = $User->parameters["characterName"];
			$userId = $User->parameters["userId"];
			$apiKey = $User->parameters["apiKey"];
			$characterId = $User->parameters["characterId"];
			$master = $User->parameters["master"];*/
			
			$disableMaster = "";
			$disableSlave= "";

			$disabledMaster = "";
			$disabledSlave= "";

			$checkedMaster = "";
			$checkedSlave = "";

			//echo "master: $master";
			if($master != "")
			{
				$disableMaster = "disable";
				$disabledMaster = "disabled";
				$checkedSlave = "checked";
				$edit_access = "";
			}
			else
			{
				$disableSlave = "disable";
				$disabledSlave = "disabled";
				$checkedMaster = "checked";
				$edit_access = "<a href='index.php?mode=EditAccess'>Права доступа</a>";
			}

			$request_processor = $_SERVER["PHP_SELF"] . "?mode=" . get_class($this);
			

	//print_r(mysql_fetch_array($qr));

			$page->Body = "
<script type='text/javascript'>\n
	function changeType(str)\n
	{\n
		if(str == 'master')\n
		{\n
			document.registration.userId.disabled = false;\n
			document.registration.apiKey.disabled = false;\n
			document.registration.characterId.disabled = false;\n
			document.registration.master.disabled = true;\n
		}\n
		if(str == 'slave')\n
		{\n
			document.registration.userId.disabled = true;\n
			document.registration.apiKey.disabled = true;\n
			document.registration.characterId.disabled = true;\n
			document.registration.master.disabled = false;\n
		}\n
	}\n
</script>\n";
			$page->Body .= "
<form method='post' name='registration' action='$request_processor'>
	<table class='b-login b-border'>
		<tr>
			<td>Логин:</td>
			<td><input type='text' name='login' class='login_text' value='$login'></td>
		</tr>
		<tr>
			<td>Пароль:</td>
			<td><input type='password' name='password' class='login_text' value='$password'></td>
		</tr>
		<tr>
			<td>Email:</td>
			<td><input type='text' name='email' class='login_text' value='$email'></td>
		</tr>
		<tr>
			<td>characterName:</td>
			<td><input type='text' name='characterName' class='login_text' value='$characterName'></td>
		</tr>
		<tr>
			<td>У вас есть свой ApiKey:</td>
			<td colspan='2'><input type='radio' name='type' class='login_text' value='master' $checkedMaster onChange='changeType(this.value);'></td>
		</tr>
		<tr>
			<td>userId:</td>
			<td><input type='text' name='userId' class='login_text' value='$userId' $disabledMaster></td>
		</tr>
		<tr>
			<td>apiKey:</td>
			<td><input type='password' name='apiKey' class='login_text' value='$apiKey'  $disabledMaster></td>
		</tr>
		<tr>
			<td>characterId:</td>
			<td><input type='text' name='characterId' class='login_text' value='$characterId' $disabledMaster></td>
		</tr>
		<tr>
			<td>ApiKey другого аккаунта:</td>
			<td colspan='2'><input type='radio' name='type' class='login_text' value='slave' $checkedSlave onChange='changeType(this.value);'></td>
		</tr>
		<tr>
			<td>Ник управляющего:</td>
			<td><input type='text' name='master' class='login_text' value='$master'  $disabledSlave></td>
		</tr>
		<tr>	
			<td>$edit_access</td>\n
			<td class='login_submit'>
				<input type='submit' name='form_submit' value='Применить'>
			</td>
		</tr>
		<tr class='login_message'>\n
			<td colspan='2'>\n
				$message\n
			</td>\n
		</tr>\n
	</table>
</form>";
		}
	}
?>
