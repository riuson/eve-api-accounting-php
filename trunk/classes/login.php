<?php
	include_once("database.php");
	include_once("user.php");
	class Login
	{
		public function PreProcess($page)
		{
			$User = new User("empty");
			$User->InitFromLoginFormPost();
			$login = $User->parameters["login"];
			$password = $User->parameters["password"];

			/*if(isset($_REQUEST["logout"]))
			{
				if(isset($_SESSION["User"]))
					unset($_SESSION["User"]);
				$old_sessionid = session_id();
				session_regenerate_id();
				$new_sessionid = session_id();
			}*/
			$request_processor = $_SERVER["PHP_SELF"] . "?mode=" . get_class($this);
			$message = "";
			$loginSuccess = false;
			if($User->CheckUserRegistered())
			{
				if (isset($_REQUEST[session_name()]))
					session_destroy();
				$started = session_start();
				$_SESSION["UserIp"] = $_SERVER["REMOTE_ADDR"];
				$_SESSION["User"] = $User;
				$User->SaveSession();
				//echo "success: $started";
				//$message = "Вход успешно произведён";
				if(isset($_POST["redirect"]))
					$redirect = $_POST["redirect"];
				else
					$redirect = null;

				$loginSuccess = true;
				if($redirect == null)
					$redirect = "index.php?mode=Index";

				$page->Body = "<p><b>{$User->parameters["login"]}</b>, вход произведён успешно, <a href='$redirect'>нажмите</a> для продолжения</p>";
				/*if($redirect == null)
					header("Location:index.php?mode=Index");
				else
					header("Location:$redirect");*/
			}
//echo "###";
			if($loginSuccess == false)
			{
				$message = "";
				//$message = "notregistered";
				if(!isset($_POST["login"]) || !isset($_POST["password"]))
				{
					//$message = "Ошибка передачи данных";
				}

				//если была попытка ввода данных
				if(isset($_POST["form_submit"]))
				{
					if($_POST["password"] == "")
						$message .= "Пароль не указан";
					else
						if($_POST["login"] == "")
							$message .= "Логин не указан";
						else
							if($_POST["login"] != "" && $_POST["password"] != "")
								$message .= "Пользователь не зарегистрирован";
				}
				$page->Body = "
				 <form method='post' action='$request_processor' id='login_form'>\n
						<table class='b-login b-border'>\n
							<tr>\n
								<td>Логин:</td>\n
								<td><input type='text' name='login' class='login_text' value='$login'></td>\n
							</tr>\n
							<tr>\n
								<td>Пароль:</td>\n
								<td><input type='password' name='password' class='login_text' value='$password'></td>\n
							</tr>\n
							<tr>\n
								<td></td>\n
								<td class='login_submit'>\n
									<a href='index.php?mode=Register'>Регистрация</a>\n
									<input type='submit' name='form_submit' value='Войти' >\n
								</td>\n
							</tr>\n
							<tr class='b-login-message'>\n
								<td colspan='2'>\n
									$message\n
								</td>\n
							</tr>\n
						</table>\n
					</form>\n";
			}
		}
	}
?>
