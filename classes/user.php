<?php
	include_once("database.php");

	class User
	{
		var $sessionId;
		var $parameters;
		var $masterUser;
		var $dc;

		public function Clear()
		{
			$this->parameters["email"] = "";
			$this->parameters["master"] = "";
			$this->parameters["userId"] = 0;
			$this->parameters["apiKey"] = "";
			$this->parameters["characterId"] = 0;
			$this->parameters["characterName"] = "";
			$this->parameters["access"] = "";
		}
		public function ClearAll()
		{
			$this->parameters["accountId"] = "";
			$this->parameters["login"] = "";
			$this->parameters["password"] = "";
			$this->Clear();
		}
		public function __construct($foo)
		{
			$this->sessionId = $foo;
			$this->parameters = array();
			$this->dc = "4r8731tsnb";

			$this->ClearAll();
		}
		public function InitFromLoginFormPost()
		{
			if(isset($_POST["login"]))
				$this->parameters["login"] = $_POST["login"];
			if(isset($_POST["password"]))
				$this->parameters["password"] = $_POST["password"];
		}
		public function InitFromRegisterFormPost()
		{
			if(isset($_POST["login"]))
				$this->parameters["login"] = $_POST["login"];
			if(isset($_POST["password"]))
				$this->parameters["password"] = $_POST["password"];
			if(isset($_POST["email"]))
				$this->parameters["email"] = $_POST["email"];
			if(isset($_POST["characterName"]))
				$this->parameters["characterName"] = $_POST["characterName"];
			if(isset($_POST["characterName"]))
				$this->parameters["characterName"] = $_POST["characterName"];


			$type = "master";
			if(isset($_POST["type"]))
			{
				$type = $_POST["type"];
			}
			if($type == "master")
			{
				if(isset($_POST["userId"]))
					$this->parameters["userId"] = $_POST["userId"];
				if(isset($_POST["apiKey"]))
					$this->parameters["apiKey"] = $_POST["apiKey"];
				if(isset($_POST["characterId"]))
					$this->parameters["characterId"] = $_POST["characterId"];

				$this->parameters["master"] = "";
			}
			if($type == "slave")
			{
				if(isset($_POST["master"]))
					$this->parameters["master"] = $_POST["master"];

				$this->parameters["userId"] = 0;
				$this->parameters["apiKey"] = "";
				$this->parameters["characterId"] = 0;
			}
		}
		public function CheckUserRegistered()
		{
			$result = false;

			$login = $this->parameters["login"];
			$pass = $this->parameters["password"];

			//поиск юзера в базе данных
			$db = OpenDB2();
			$query = sprintf("select * from api_users where login = '%s' and password = md5('%s');",
				$db->real_escape_string($login),
				$db->real_escape_string($pass));
			//$qr = ExecuteQuery($query);
			$qr = $db->query($query);
			//$qr = mysql_fetch_array($qr);

			//если юзер не найден, очищаем все данные, кроме логина и пароля
			//print($qr->num_rows);
			if($qr == null || $qr->num_rows == 0)
			{
				if($login != null && $login != "")
				{
					$query = sprintf("insert into api_log set _date_ = now(), message = '%s';",
						$db->real_escape_string("Login failed as ($login, $pass) from $_SERVER[REMOTE_ADDR]"));
					$db->query($query);
				}
				//print_r($User);
				//echo $query;
				$this->Clear();
				$result = false;
				if($qr != null)
					$qr->close();
			}
			else//если найден, получаем его данные
			{
				$row = $qr->fetch_assoc();
				$this->parameters["accountId"] = $row["accountId"];
				$this->parameters["email"] = $row["email"];
				$this->parameters["master"] = $row["master"];
				$this->parameters["userId"] = $row["userId"];
				$this->parameters["apiKey"] = RC4($this->dc, base64_decode($row["apiKey"]));
				//$this->parameters["apiKey"] = $qr["apiKey"];
				$this->parameters["characterId"] = $row["characterId"];
				$this->parameters["characterName"] = $row["characterName"];
				$this->parameters["access"] = $row["access"];
				$qr->close();
				//echo "##########<br>";
				//print_r($this->parameters);
				//echo "##########<br>";

				//если этот акк ссылается на другого мастера, создаём его и грузим данные
				if($this->parameters["master"] != "")
				{
					$this->masterUser = new User("master");
					$masterLogin = $this->parameters["master"];
					$query = sprintf("select * from api_users where characterName = '%s';",
						$db->real_escape_string($masterLogin));
					//$qr = ExecuteQuery("select * from api_users where characterName = '$masterLogin';");
					$qr = $db->query($query);
					//$qr = mysql_fetch_array($qr);
					//echo $query;
					if($qr != null && $qr->num_rows == 1)
					{
						$row = $qr->fetch_assoc();

						$this->masterUser->parameters["accountId"] = $row["accountId"];
						$this->masterUser->parameters["email"] = $row["email"];
						$this->masterUser->parameters["master"] = $row["master"];
						$this->masterUser->parameters["userId"] = $row["userId"];
						$this->masterUser->parameters["apiKey"] = RC4($this->dc, base64_decode($row["apiKey"]));
						$this->masterUser->parameters["characterId"] = $row["characterId"];
						$this->masterUser->parameters["characterName"] = $row["characterName"];
						//echo "master user loaded<br>";
					}
					if($qr != null)
						$qr->close();
					//print_r($this->masterUser);
				}
				else
				{
					$this->masterUser = null;
				}

				$result = true;
			}
			$db->close();
			//print_r($this->masterUser);
			return $result;
		}
		public function GetUserInfo($accountId)
		{
			$result = false;

			//поиск юзера в базе данных
			$db = OpenDB2();
			$query = sprintf("select * from api_users where accountId = '%s';", $db->real_escape_string($accountId));
			//$qr = ExecuteQuery("select * from api_users where accountId = '$accountId';");
			//$qr = mysql_fetch_array($qr);
			$qr = $db->query($query);

			//если юзер не найден, очищаем все данные, кроме логина и пароля
			if($qr == false)
			{
				$result = false;
			}
			else//если найден, получаем его данные
			{
				$row = $qr->fetch_assoc();
				$this->parameters["login"] = $row["login"];
				$this->parameters["accountId"] = $row["accountId"];
				$this->parameters["email"] = $row["email"];
				$this->parameters["master"] = $row["master"];
				$this->parameters["userId"] = $row["userId"];
				$this->parameters["apiKey"] = RC4($this->dc, base64_decode($row["apiKey"]));
				//$this->parameters["apiKey"] = $qr["apiKey"];
				$this->parameters["characterId"] = $row["characterId"];
				$this->parameters["characterName"] = $row["characterName"];
				$this->parameters["access"] = $row["access"];
				$qr->close();
				//echo "##########<br>";
				//print_r($this->parameters);
				//echo "##########<br>";

				//если этот акк ссылается на другого мастера, создаём его и грузим данные
				if($this->parameters["master"] != "")
				{
					$this->masterUser = new User("master");
					$masterLogin = $this->parameters["master"];
					$query = sprintf("select * from api_users where characterName = '%s';", $db->real_escape_string($masterLogin));
					//$qr = ExecuteQuery("select * from api_users where characterName = '$masterLogin';");
					//$qr = mysql_fetch_array($qr);
					$qr = $db->query($query);

					if($qr)
					{
						$row = $qr->fetch_assoc();
						$this->masterUser->parameters["accountId"] = $row["accountId"];
						$this->masterUser->parameters["email"] = $row["email"];
						$this->masterUser->parameters["master"] = $row["master"];
						$this->masterUser->parameters["userId"] = $row["userId"];
						$this->masterUser->parameters["apiKey"] = RC4($this->dc, base64_decode($row["apiKey"]));
						$this->masterUser->parameters["characterId"] = $row["characterId"];
						$this->masterUser->parameters["characterName"] = $row["characterName"];
						//echo "master user loaded<br>";
						//print_r($this->masterUser);
						$qr->close();
					}
				}
				else
				{
					$this->masterUser = null;
				}

				$result = true;
			}
			$db->close();
			//print_r($this->parameters);
			return $result;
		}
		public function Register()
		{
			$accountId = GetUniqueId();
			$login = $this->parameters["login"];
			$password = $this->parameters["password"];
			$email = $this->parameters["email"];
			$characterName = $this->parameters["characterName"];

			$userId = $this->parameters["userId"];
			$apiKey = $this->parameters["apiKey"];
			$apiKey = base64_encode(RC4($this->dc, $apiKey));
			$characterId = $this->parameters["characterId"];

			$master = $this->parameters["master"];

			$db = OpenDB2();
			//$password = md5($password);
			//print_r($this->parameters);
			//если slave
			if($master != "")
			{
				$query = sprintf("insert into api_users (accountId, login, password, email, characterName, master) ".
					"values ('%s', '%s', md5('%s'), '%s', '%s', '%s');",
						$db->real_escape_string($accountId),
						$db->real_escape_string($login),
						$db->real_escape_string($password),
						$db->real_escape_string($email),
						$db->real_escape_string($characterName),
						$db->real_escape_string($master));
			}
			else//если master
			{
				$query = sprintf("insert into api_users (accountId, login, password, email, characterName, userId, apiKey, characterId) ".
					"values ('%s', '%s', md5('%s'), '%s', '%s', '%s', '%s', '%s');",
						$db->real_escape_string($accountId),
						$db->real_escape_string($login),
						$db->real_escape_string($password),
						$db->real_escape_string($email),
						$db->real_escape_string($characterName),
						$db->real_escape_string($userId),
						$db->real_escape_string($apiKey),
						$db->real_escape_string($characterId));
			}
			//echo $query;
			$db->query($query);

			if($db->affected_rows == 1)
				$result = true;
			else
				$result = false;

			$db->close();

			return $result;
		}
		public function ChangeInfo()
		{
			$accountId = $this->parameters["accountId"];
			$login = $this->parameters["login"];
			$password = $this->parameters["password"];
			$email = $this->parameters["email"];
			$characterName = $this->parameters["characterName"];

			$userId = $this->parameters["userId"];
			$apiKey = $this->parameters["apiKey"];
			$apiKey = base64_encode(RC4($this->dc, $apiKey));
			$characterId = $this->parameters["characterId"];

			$master = $this->parameters["master"];

			//$password = md5($password);

			$db = OpenDB2();
			//если slave
			if($master != "")
			{
				$query = sprintf("update api_users set login = '%s', password = md5('%s'), email = '%s', characterName = '%s', master = '%s', userId = 0, apiKey = '', characterId = 0, access = '' where accountId = '%s';",
					$db->real_escape_string($login),
					$db->real_escape_string($password),
					$db->real_escape_string($email),
					$db->real_escape_string($characterName),
					$db->real_escape_string($master),
					$db->real_escape_string($accountId));
			}
			else//если master
			{
				$query = sprintf("update api_users set login = '%s', password = md5('%s'), email = '%s', characterName = '%s', master = '', userId = %s, apiKey = '%s', characterId = %s, access = '' where accountId = '%s';",
					$db->real_escape_string($login),
					$db->real_escape_string($password),
					$db->real_escape_string($email),
					$db->real_escape_string($characterName),
					$db->real_escape_string($userId),
					$db->real_escape_string($apiKey),
					$db->real_escape_string($characterId),
					$db->real_escape_string($accountId));
			}
			//echo $query;
			$db->query($query);
			//$qr = ExecuteQuery($query);
			if($db->affected_rows == 1)
				$result = true;
			else
				$result = false;

			$db->close();

			return $result;
		}
		static function CheckLogin($redirectIfFalse = true)
		{
			$User = null;
			$req_uri = urlencode($_SERVER["REQUEST_URI"]);
			//проверка кукисов
			$cookieSuccess = false;
			//если имя сессии передано через куки
			if (isset($_COOKIE[session_name()]))
			{
				//проверка наличия этой сессии в бд
				$db = OpenDB2();
				$query = sprintf("select * from api_sessions where sessionId = '%s' and address = '%s' and expiredTime > '%s';",
					$db->real_escape_string($_COOKIE[session_name()]),
					$db->real_escape_string($_SERVER["REMOTE_ADDR"]),
					$db->real_escape_string(date("Y-m-d H:i:s")));
				$qr = $db->query($query);
				if($row = $qr->fetch_assoc())
				{
					$accId = $row["accountId"];
					$User = new User("");
					if($User->GetUserInfo($accId))
					{
						//echo "3";
						//session_start();
						if($row["sessionId"] != session_id())
						{
							$User->DestroySession($row["sessionId"]);
							//session_id($row["sessionId"]);
						}
						$_SESSION["User"] = $User;
						$User->SaveSession();
						$cookieSuccess = true;
					}
				}
				$qr->close();
				$db->close();
			}
			if($cookieSuccess == false)
			{
				if(isset($_SESSION["User"]))
				{
					$User = $_SESSION["User"];
					if(!$User->CheckUserRegistered())
					{
						if($redirectIfFalse == true)
							header("Location:index.php?mode=Index");
						//echo "not registered";
						$User = null;
					}
					else
					{
						//header("Location:$req_uri");
					}
				}
				else
				{
					if($redirectIfFalse == true)
						header("Location:index.php?mode=Index");
					//echo "var not set";
				}
			}
			return $User;
		}
		public function GetUserId()
		{
			$result = "";
			if($this->parameters["master"] != "")
			{
				if($this->masterUser != null)
				{
					$result = $this->masterUser->parameters["userId"];
				}
			}
			else
			{
				$result = $this->parameters["userId"];
			}
			//echo " get user id: $result<br>";
			return $result;
		}
		public function GetApiKey()
		{
			$result = "";
			if($this->parameters["master"] != "")
			{
				if($this->masterUser != null)
				{
					$result = $this->masterUser->parameters["apiKey"];
				}
			}
			else
			{
				$result = $this->parameters["apiKey"];
			}
			return $result;
		}
		public function GetCharacterId()
		{
			$result = "";
			if($this->parameters["master"] != "")
			{
				if($this->masterUser != null)
				{
					$result = $this->masterUser->parameters["characterId"];
				}
			}
			else
			{
				$result = $this->parameters["characterId"];
			}
			return $result;
		}
		public function GetAccountId()
		{
			$result = "";
			if($this->parameters["master"] != "")
			{
				if($this->masterUser != null)
				{
					$result = $this->masterUser->parameters["accountId"];
				}
			}
			else
			{
				$result = $this->parameters["accountId"];
			}
			return $result;
		}
		public function CheckAccessRights($modename, $redirectIfFalse = false)
		{
			$modes = Page::GetModes();
			$result = false;
			//если мастер, права все есть
			if($this->parameters["master"] == "" && $this->parameters["userId"] != 0 && $this->parameters["apiKey"] != "" && $this->parameters["characterId"] != 0)
			{
				$result = true;
				//echo "master<br>";
			}
			else
			{
				//проверяем права доступа по хранимому в базе перечню
				if($this->masterUser != null)
				{
					if(preg_match("/(\W|^)$modename(\W|$)/", $this->parameters["access"]) != 0)
					{
						$result = true;
					}
				}
				//если их нет, проверяем по списку всем доступных страниц
				if($result == false)
				{
					//$index = 0;
					foreach($modes as $k => $v)
					{
						//если режим совпадает с проверяемым и не содержится в списке ограниченных режимов
						if(($k == $modename) && (preg_match("/(\W|^)$k(\W|$)/", Page::GetLimitedLinks()) == 0))
						{
							$result = true;
						}
						//$index++;
					}
				}
			}
			if($result == false && $redirectIfFalse == true)
			{
				header("Location:index.php?mode=Index");
			}
			//echo "$modename - $result<br>	";
			return $result;
		}
		public function SaveSession()
		{
			$db = OpenDB2();
			$query = sprintf("replace into api_sessions values ('%s', '%s', '%s', '%s', '%s');",
				GetUniqueId(),
				$db->real_escape_string(session_id()),
				$this->parameters["accountId"],
				$db->real_escape_string($_SERVER["REMOTE_ADDR"]),
				date("Y-m-d H:i:s", strtotime("+30 day")));
			$db->query($query);
			$db->close();
			setcookie(session_name(), session_id(), time()+3600*24*30);
		}
		public function DestroySession($sessId)
		{
			$db = OpenDB2();
			$query = sprintf("delete from api_sessions where sessionId = '%s' and address = '%s';",
				$db->real_escape_string($sessId),
				$db->real_escape_string($_SERVER["REMOTE_ADDR"]));
			//echo $query;
			$db->query($query);
			$db->close();
		}
	}
?>
