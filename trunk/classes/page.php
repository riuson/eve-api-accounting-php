<?php
	include_once "api2.php";
	include "user.php";

	class Page
	{
		public $mode;
		public $modes;
		var $debug = true;
		var $timer;
		var $modeObject;
		var $igb;
		
		var $Body;
		var $User;

		var $sorters;

		static function GetSystemLinks()
		{
			return "Login,Logout,ChangeDetails,Register,EditAccess,Index,Visitors,WhoIs,Information";
		}
		static function GetLimitedLinks()
		{
			return "Api_CorporationSheet,Api_AccountBalance,Api_Journal,Api_Transactions,Api_MemberTracking,InsuranceMails,Api_StatsFromMissions,Api_Kills,Api_Assets,Api_Standings,Api_MemberSecurity,Api_IndustryJobs,Api_MarketOrders,Subscribes,Api_Starbases";
		}
		static function GetSubscribeLinks()
		{
			return "Api_AccountBalance,Api_Assets,Api_Starbases";
		}
		//запрос списка режимов
		//права доступа регулируются только для элементов с индексом больше Page::GetNotLimitedLinksCount()
		//по умолчанию эти права сброшены, отключены
		static function GetModes()
		{
			$modesArray = array(
				"Index" => "Начало",
				"Login" => "Вход в систему",
				"Logout" => "Выход из системы",
				"ChangeDetails" => "Изменение данных",
				"Register" => "Регистрация",
				"EditAccess" => "Изменение прав доступа",
				"Visitors" => "Список посетителей",
				"WhoIs" => "Информация об адресе",
				"Information" => "Информация",
				"Api_Errors" => "Ошибки API",
				"Api_RefTypes" => "Типы переводов",
				"Api_Outposts" => "Аутпосты",
				"Api_Alliances" => "Альянсы",
				"Api_FacWarTopStats" => "Фракционные войны",
				"Api_Conversion" => "Имена/Id",
				"Api_CorporationSheet" => "Корпорация",
				"Api_AccountBalance" => "Баланс кошельков",
				"Api_Journal" => "Журнал",
				"Api_Transactions" => "Транзакции",
				"Api_MemberTracking" => "MemberTracking",
				"InsuranceMails" => "Страховки",
				"Api_StatsFromMissions" => "Доходы с миссий",
				"Api_Kills" => "Kill log",
				"Api_Assets" => "Имущество",
				"Api_Standings" => "Стенды",
				"Api_MemberSecurity" => "Member Security",
				"Api_IndustryJobs" => "Производство",
				"Api_MarketOrders" => "Ордеры",
				"Api_Starbases" => "ПОСы",
				"Subscribes" => "Рассылка"
			);
			return $modesArray;
		}

		public function __construct()
		{
			date_default_timezone_set("Etc/Universal");
			$this->timer = new Timer();
			$this->timer->start();

			$this->modes = Page::GetModes();
			//получение режима работы из запроса
			if(isset($_REQUEST["mode"]))
				$this->mode = $_REQUEST["mode"];
			else//если там он не указан - индекс по умолчанию
				$this->mode = "index";

			//поиск указанного режима работы среди доступных
			$modeFound = false;
			foreach ($this->modes as $k=>$v)
			{
				if(preg_match("/index/i", $k) == 0)
					include_once strtolower($k) . ".php";

				if(preg_match("/(\W|^)$k(\W|$)/i", $this->mode) == 1)
					$modeFound = true;
			}
			//если не найден - пересылка на индекс, т.к. mode кто-то решил указать левый
			if($modeFound == false)
				//$this->mode = "index";
				header("Location:index.php?mode=Index");
			
			$this->Body = "";
			$this->User = User::CheckLogin(false);
			if($this->User == null)
				$this->User = new User("empty");
			
			//запись посетителя в лог
			$db = OpenDB2();
			$db->query(sprintf(
				"insert into api_visitors set _date_ = '%s', address = '%s', agent = '%s', login = '%s', uri = '%s';",
				date("Y-m-d H:i:s", time()),
				$db->real_escape_string($_SERVER["REMOTE_ADDR"]),
				$db->real_escape_string($_SERVER["HTTP_USER_AGENT"]),
				$db->real_escape_string($this->User->parameters["login"]),
				$db->real_escape_string($_SERVER["REQUEST_URI"])));
			$db->close();

			//определение igb
			if(isset($_SERVER["HTTP_USER_AGENT"]))
				$userAgent = $_SERVER["HTTP_USER_AGENT"];
			else
				$userAgent = "unknown";
			if(preg_match("/eve/i", $userAgent) != 0)
				$this->igb = true;
			else
				$this->igb = false;
		}

		//здесь вызываются функции до отправки данных пользователю
		public function PreProcess()
		{
			if(preg_match("/index/i", $this->mode) == 0)
			{
				$n = $this->mode;
				$this->modeObject = new $n();
				$this->modeObject->PreProcess($this);
				//$this->Body = $this->mode;
			}
		}
		function WriteSorter($list, $uri = null)
		{
			$result = "";
			$this->sorters = $list;
			//строка запроса текущей страницы
			if($uri == null)
				$uri = $_SERVER["REQUEST_URI"];
			//если в ней нет orderby, надо добавить
			if(preg_match("/orderby=\w+/i", $uri) == 0)
			{
				//если адрес не оканчивается на .php, добавить &orderby=
				if(preg_match("/\.php$/i", $uri) == 0)
					$uri .= "&amp;orderby=query";
				else//иначе добавляем ?orderby=
					$uri .= "?orderby=query";
				//echo "$uri";
			}
			//query будет потом заменён на столбец сортировки

			$sorter = "";
			if(isset($_REQUEST["orderby"]))
			{
				$sorter = $_REQUEST["orderby"];
			}
			else
			{
				if(isset($_SESSION[$this->mode . "_sort_by_column"]))
					$sorter = $_SESSION[$this->mode . "_sort_by_column"];
			}
			//echo $sorter;
			foreach ($list as $key=>$value)
			{
				$string = $uri;//"April 15, 2003";
				$pattern = "/(^.*)(orderby=\w+)(.*$)/i";//"/(\w+) (\d+), (\d+)/i";
				$replacement = "\${1}orderby=$key\$3";
				$link = preg_replace($pattern, $replacement, $string);

				if($key == $sorter)
				{
					$img = "";
					if(isset($_SESSION[$this->mode . "_sort_by_direction"]))
					{
						if($_SESSION[$this->mode . "_sort_by_direction"] == "asc")
							$img = "<img src='images/s_asc.png' alt='asc'>";
						if($_SESSION[$this->mode . "_sort_by_direction"] == "desc")
							$img = "<img src='images/s_desc.png' alt='desc'>";
					}
					$result .= "<td class='b-center'><a href='$link'>$value $img</a></td>";
				}
				else
					$result .= "<td class='b-center'><a href='$link'>$value</a></td>";
			}
			return $result;
		}
		function GetSorter($defaultOrder)
		{
			//сортировка может быть сохранена в сессии ранее
			//сортировка может быть указана в get запросе
			//по умолчанию используется, если другие не дали данных
			if(isset($_REQUEST["orderby"]))
				$selectedColumn = $_REQUEST["orderby"];
			else
				$selectedColumn = null;

			//проверка сохранённой в сессии сортировки для этой страницы
			if(isset($_SESSION[$this->mode . "_sort_by_column"]))
				$oldColumn = $_SESSION[$this->mode . "_sort_by_column"];
			else
				$oldColumn = null;

			if(isset($_SESSION[$this->mode . "_sort_by_direction"]))
				$oldDirection = $_SESSION[$this->mode . "_sort_by_direction"];
			else
				$oldDirection = null;

			//если есть, используем их
			if($oldColumn != null && $oldDirection != null && array_key_exists($oldColumn, $this->sorters))
			{
				//если старая колонка и указанная в запросе совпадают, изменить направление
				if($selectedColumn != null && array_key_exists($selectedColumn, $this->sorters) == true)
				{
					if($selectedColumn == $oldColumn)
					{
						if($oldDirection == "asc")
							$oldDirection = "desc";
						else
							$oldDirection = "asc";
					}
					else
					{
						$oldColumn = $selectedColumn;
						$oldDirection = "asc";
					}
				}
				else
				{
					$oldColumn = $defaultOrder;
					$oldDirection = "asc";
				}
			}
			else
			{
				$oldColumn = $selectedColumn;
				$oldDirection = "asc";
				if($selectedColumn == null || array_key_exists($selectedColumn, $this->sorters) == false)
				{
					$oldColumn = $defaultOrder;
					$oldDirection = "asc";
				}
			}
			//сохранение в сессии
			$_SESSION[$this->mode . "_sort_by_column"] = $oldColumn;
			$_SESSION[$this->mode . "_sort_by_direction"] = $oldDirection;
			//построение сортировки
			$result = " order by $oldColumn $oldDirection ";
			//print_r($_SESSION);
			//echo $result;
			//print_r($_SESSION);
			return $result;
		}
		function WriteHtml()
		{
			//include_once "darkit/template.php";
			if($this->igb == true)
				include_once "minibrowser/template.php";
			else
				include_once "delicious/template.php";

			//if(isset($_SESSION["User"]))
			//	$User = $_SESSION["User"];
			//else
			//	$User = new User("empty");

			if(array_key_exists($this->mode, $this->modes) == false)
				$this->mode = "Index";
			
			$title = $this->modes[$this->mode];

			$metaTags = array(
				"<meta http-equiv='reply-to' content='rius@mail.ru'>",
				"<meta http-equiv='content-type' content='text/html; charset=utf-8'>",
				"<meta http-equiv='content-language' content='ru'>",
				"<meta http-equiv='robots' content='none'>",
				"<meta http-equiv='description' content='$title'>",
				"<meta http-equiv='generator' content='Geany'>",
				"<link rel='stylesheet' type='text/css' href='ea2.css'>"
			);

			//левое меню режимов
			$leftMenuItems = array();
			//список режимов с закрытым доступом
			$restictedModes = array();
			foreach ($this->modes as $k=>$v)
			{
				if(preg_match("/(\W|^)$k(\W|$)/", Page::GetSystemLinks()) == 0)
				{
					if($this->User->CheckAccessRights($k, false) == false)
					{
						array_push($restictedModes, $k);
					}

					$leftMenuItems[$k] = $v;
				}
				//$index++;
			}

			$topMenuItems = array(
				"Index" => "Начало",
				"Information" => "Информация",
				"Visitors" => "Посетители"
			);

			$login = $this->User->parameters["login"];

			if($login != "")
				$login = "<a href='index.php?mode=Logout'>Выход</a> [ <span><a href='index.php?mode=ChangeDetails'>$login</a></span> ] ";
			else
				$login = "<span>Гость</span>, <a href='index.php?mode=Login'>Войти</a>";

			$Api = new ApiInterface("");
			//$Api->userid = $User->parameters["userId"];
			//$Api->apikey = $User->parameters["apiKey"];
			//print_r($Api);
			//$serverStatus = "";
			
			$serverStatus = $Api->GetServerStatus();
			if($serverStatus["serverOpen"] == "True")
				$serverOnline = "Online";
			else
				$serverOnline = "Offline";

			$pilots = "пилотов";
			if(($serverStatus["onlinePlayers"] % 10) == 1 && $serverStatus["onlinePlayers"] != 11)
				$pilots = "пилот";
			if(($serverStatus["onlinePlayers"] % 10) >= 2 && ($serverStatus["onlinePlayers"] % 10) <= 4)
				$pilots = "пилота";

			$serverStatus = "EVE сервер: $serverOnline, $serverStatus[onlinePlayers] $pilots<br>$serverStatus[now]";
			
			$this->timer->stop();
			$time = $this->timer->getTime();
			$footer = "Время сборки страницы $time с.";

			ob_start();
			echo ProcessTemplate($title, $metaTags, $leftMenuItems, $topMenuItems, $this->mode, $restictedModes, $login, $this->Body, $serverStatus, $footer);
			ob_end_flush();
		}
		function FormatNum($num, $decimals)
		{
			$result = number_format($num, $decimals, ",", " ");
			$result = str_replace(" ", "&nbsp;", $result);
			return $result;
		}
	}
?>
