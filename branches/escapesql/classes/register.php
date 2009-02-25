<?php
	include_once("database.php");
	include_once("user.php");
	class Register
	{
		function PreProcess($page)
		{
			$User = new User("empty");

			//print($_SERVER["SERVER_NAME"]);
			$User->InitFromRegisterFormPost();
			
			$login = $User->parameters["login"];
			$password = $User->parameters["password"];
			$email = $User->parameters["email"];
			$characterName = $User->parameters["characterName"];

			$userId = $User->parameters["userId"];
			$apiKey = $User->parameters["apiKey"];
			$characterId = $User->parameters["characterId"];
			
			$master = $User->parameters["master"];
			

			$message = "";
			if($login == "")
				$message .= "Не указан логин ";

			if($message == "" && $password == "")
				$message .= "Не указан пароль ";

			if($message == "" && $email == "")
				$message .= "Не указана почта ";

			if($message == "" && $characterName == "")
				$message .= "Не указан characterName ";

			if($master == "")//если не master
			{
				if($message == "" && $userId == "")
					$message .= "Не указан userId ";

				if($message == "" && $apiKey == "")
					$message .= "Не указан apiKey ";

				if($message == "" && $characterId == "")
					$message .= "Не указан characterId ";
			}
			else
			{

				if($message == "" && $master == "")
					$message .= "Не указан ник управляющего ";
			}
			$db = OpenDB2();
			if($message == "")
			{
				$query = sprintf("select count(*) as _count_ from api_users where login = '%s';", $db->real_escape_string($login));
				//$qr = ExecuteQuery("select * from api_users where login = '$login';");
				$qr = $db->query($query);
				//если результат не false, то строка в базе найдена и юзера с таким логином зарегить нельзя
				//if(mysql_fetch_array($qr) != false)
				if($qr)
				{
					$row = $qr->fetch_assoc();
					if($row["_count_"] > 0)
						$message .= "Пользователь с таким логином уже зарегистрирован";
					$qr->close();
				}
			}
			if($message == "")
			{
				$query = sprintf("select count(*) as _count_ from api_users where characterName = '%s';", $db->real_escape_string($characterName));
				//$qr = ExecuteQuery("select * from api_users where characterName = '$characterName';");
				$qr = $db->query($query);
				//если результат не false, то строка в базе найдена и юзера зарегить нельзя
				//if(mysql_fetch_array($qr) != false)
				if($qr)
				{
					$row = $qr->fetch_assoc();
					if($row["_count_"] > 0)
						$message .= "Пользователь с таким ником уже зарегистрирован";
				}
			}
			if($message == "" && $characterId != 0)
			{
				$query = sprintf("select count(*) as _count_ from api_users where characterId = '%s';", $db->real_escape_string($characterId));
				//$qr = ExecuteQuery("select * from api_users where characterId = '$characterId';");
				$qr = $db->query($query);
				//если результат не false, то строка в базе найдена и юзера зарегить нельзя
				//if(mysql_fetch_array($qr) != false)
				if($qr)
				{
					$row = $qr->fetch_assoc();
					if($row["_count_"] > 0)
						$message .= "Пользователь с таким characterId уже зарегистрирован";
				}
			}
			//если сообщений с ошибками нет, пробуем зарегить юзера
			if($message == "")
			{
				//echo "try register";
				if($User->Register() == true)
				{
					$_SESSION["User"] = $User;
					header("Location:index.php");
				}
			}

			$login_processor = $_SERVER["PHP_SELF"] . "?mode=register";

			$disableMaster = "";
			$disableSlave= "";

			$disabledMaster = "";
			$disabledSlave= "";

			$checkedMaster = "";
			$checkedSlave = "";

			if($master != "")
			{
				$disableMaster = "disable";
				$disabledMaster = "disabled";
				$checkedSlave = "checked";
			}
			else
			{
				$disableSlave = "disable";
				$disabledSlave = "disabled";
				$checkedMaster = "checked";
			}
			$page->Body .= "
<script src=\"lib/JsHttpRequest/JsHttpRequest.js\"></script>
<script language='JavaScript'>
	function getCharactersList()
	{
		//document.getElementById('charSelector').innerHTML = \"<OPTION value='2'> Первый</OPTION>\";
		userId = document.getElementById('userId').value;
		apiKey = document.getElementById('apiKey').value;
		//alert (apiKey);
		//return;
        JsHttpRequest.query(
            'classes/register_backend.php',
            {
            	'function' : 'getCharsList',
            	'userId' : userId,
                'apiKey': apiKey
            },
            // Function is called when an answer arrives. 
            function(result, errors)
            {
            	//alert('1');
                // Write errors to the debug div.
                document.getElementById('msg').innerHTML = errors; 
                // Write the answer.
                if (result)
                {
	            	document.getElementById('charSelector').innerHTML = result['answer'];
                	//recordId = result[\"recordId\"];

					//document.getElementById('op_' + recordId).innerHTML = edit_min + edit_norm + button_submit + button_cancel;
					selectCharacterName();
                }
            },
            true  // disable caching
        );
	}
	function selectCharacterName()
	{
		char = document.getElementById('charSelector').value;
		//alert(char);
		document.getElementById('characterId').value = char;
	}
</script>
<div id='debug'></div>
";			$page->Body .= 
"
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
			$page->Body .= 
"
<form method='post' name='registration' action='$login_processor'>
	<table class='b-login b-border'>
		<tr class='b-table-caption'>
			<td colspan='2' class='login_caption'>EA Accounting: Регистрация</td>
		</tr>
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
			<td><input type='text' name='characterName' id='characterName' class='login_text' value='$characterName'></td>
		</tr>
		<tr>
			<td>Свой apiKey:</td>
			<td colspan='2'><input type='radio' name='type' class='login_text' value='master' $checkedMaster onChange='changeType(this.value);'></td>
		</tr>
		<tr>
			<td colspan='2'><a href='http://myeve.eve-online.com/api/default.asp'>Получить userid & apiKey</a></td>
		</tr>
		<tr>
			<td>userId:</td>
			<td><input type='text' name='userId' id='userId' class='login_text' value='$userId' $disabledMaster></td>
		</tr>
		<tr>
			<td>apiKey:</td>
			<td><input type='password' name='apiKey'  id='apiKey' class='login_text' value='$apiKey' $disabledMaster></td>
		</tr>
		<tr>
			<td>characterId:</td>
			<td><input type='text' name='characterId' id='characterId' class='login_text' value='$characterId' $disabledMaster></td>
		</tr>
		<tr>
			<td><a href='#' onclick=\"getCharactersList(); return false;\">characterName</a>:</td>
			<td><select onChange=\"selectCharacterName()\" id='charSelector' class='login_text' $disabledMaster></select></td>
		</tr>
		<tr>
			<td>Чужой apiKey:</td>
			<td colspan='2'><input type='radio' name='type' class='login_text' value='slave' $checkedSlave onChange='changeType(this.value);'></td>
		</tr>
		<tr>
			<td>Ник управляющего:</td>
			<td><input type='text' name='master' class='login_text' value='$master'  $disabledSlave></td>
		</tr>
		<tr>	
			<td></td>
			<td class='login_submit'>
				<input type='submit' value='Зарегистрироваться'>
			</td>
		</tr>
		<tr class='b-login-message'>\n
			<td colspan='2' id='msg'>\n
				$message\n
			</td>\n
		</tr>\n
	</table>
</form>";
		}
	}
?>
