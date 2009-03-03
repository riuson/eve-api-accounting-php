<?php
	include_once "database.php";
	class Timer {
		private $timer = false;
		private $time = false;
		public $keepAliveText = '<!-- -->';
		function start() {
			$this->time = false;
			$this->timer = microtime(true);
		}
		function stop() {
			if ($this->timer==false) {
				trigger_error('You should start timer first');
				return false;
			}
			$this->time = microtime(true) - $this->timer;
			$this->timer = false;
			return $this->getTime();
		}
		function getTime() {
			return round($this->time * 1000)/1000;
		}
		function keepAlive() {
			echo($this->keepAliveText);
			flush();
			ob_flush();
		}
	}
	class ApiInterface
	{
		var $showinfo = false;
		var $updateFromCache = true;
		var $clearOldCache = true;
		var $useBase64Caching = true;
		public $accountId;
		public $userId = 0;
		public $characterId = 0;
		public $apiKey = "";
		public $apiroot = "http://api.eve-online.com";

		public function __construct($accountId)
		{
			$this->accountId = $accountId;
		}
		function stringToTime($strDateTime)
		{
			//$format = "%Y-%m-%d %H:%M:%S";
			//$dt = strptime($strDateTime, $format);
			$dt = explode(" ", $strDateTime);
			$d = explode("-", $dt[0]);
			$t = explode(":", $dt[1]);
			//print("parse $strDateTime ");
			$result = mktime($t[0], $t[1], $t[2], $d[1], $d[2], $d[0]);
			return $result;
		}
		function parseStringTime($strDateTime)
		{
			//$format = "%Y-%m-%d %H:%M:%S";
			//$dt = strptime($strDateTime, $format);
			$dt = explode(" ", $strDateTime);
			$d = explode("-", $dt[0]);
			$t = explode(":", $dt[1]);
			//print("parse $strDateTime ");
			$result = array();
			$result["hour"] = $t[0];
			$result["minute"] = $t[1];
			$result["seconds"] = $t[2];
			$result["day"] = $d[2];
			$result["month"] = $d[1];
			$result["year"] = $d[0];
			//$result = mktime($t[0], $t[1], $t[2], $d[1], $d[2], $d[0]);
			return $result;
		}

		//запрос апи страницы
		//$target: адрес страницы
		//$paramarray: параметры get-запроса
		//возвращаемый результат - массив:
		//	[success]		- булевый результат, удалось ли получить читаемые данные
		//	[response]		- запрашиваемый xml документ в виде DomDocument
		//	[error]			- строка с сообщением об ошибке, при отсутствии ошибки - пустая
		//	[cached]		- время запроса cached
		//	[cachedUntil]	- время кеширования cachedUntil
		//	[source5]		- откуда получены данные, "server" или "cache"
		//	[now]			- текущее время
		function apiRequest($target,$paramarray) {
			date_default_timezone_set("Etc/Universal");
			$result = array();
			$result["success"] = false;
			$result["response"] = "";
			$result["error"] = "";
			$result["cached"] = "";
			$result["cachedUntil"] = "";
			$result["source"] = "server";
			//Sat, 27 Dec 2008 18:57:11
			$result["now"] = date("D j M Y G:i:s", time());

			$this->apiroot = "http://api.eve-online.com";

			$serverResponse = null;
			$db = OpenDB2();
			try
			{
				//добавляем параметры аутентификации
				//$paramarray["userId"] = $this->characterId;
				//$paramarray["apiKey"] = $this->apiKey;
				//$paramarray["characterId"] = $this->characterId;
				//формируем строку запроса
				$t = $target . "?";
				//добавляем к ней параметры в виде key = value &
				foreach ($paramarray as $k=>$v) {
					//пропуск пустых значений
					if($k == "userId" && $v == 0)
						continue;
					if($k == "characterId" && $v == 0)
						continue;
					if($k == "apiKey" && $v == "")
						continue;
					//добавление в строку get-запроса
					$t .= $k."=".$v."&";
				}
				//удаляем с конца &
				$t = substr($t,0,strlen($t)-1);
				//echo $t;
				//устанавливаем параметры запроса curl
				$uri = $this->apiroot . $t;

				//проверяем наличие в кеше
				$query = sprintf("select * from api_cache where accountId = '%s' and uri = '%s' order by `cached` desc limit 1;",
					$db->real_escape_string($this->accountId),
					$db->real_escape_string($t));
				//echo $query;
				//$qr = ExecuteQuery($query);
				if($qr = $db->query($query))
				{
					$row = $qr->fetch_assoc();
					$qr->close();
				}
				else
				{
					$row = false;
				}
				//echo mysql_error();
				//echo($qr);
				//$qr = mysql_fetch_array($qr);
				
				//print_r($qr);
				//если  $qr = false, строки в таблице нет

				//удалить устаревшие записи из кеша
				if($this->clearOldCache)
				{
					$oltTime = date("Y-m-d H:i:s", strtotime("-2 day"));
					$query = sprintf("delete from api_cache where cached < '%s';",
						$db->real_escape_string($oltTime));
					//ExecuteQuery($query);
					$db->query($query);
				}
				//if($this->showinfo == true) echo "url: <pre>$uri</pre>";
				//иначе читаем строку
				if($row != false)
				{
					if($this->showinfo == true) echo "cache not empty<br>";
					//время запроса
					$cached = $this->stringToTime($row["cached"]);
					//время кеширования
					$cachedUntil = $this->stringToTime($row["cachedUntil"]);
					//текущее время
					$now = time();

					$result["cached"] = $row["cached"];
					$result["cachedUntil"] = $row["cachedUntil"];
					//если $cachedUntil < $now, то время обновиться пришло
					//если $cachedUntil > $now, то берём прошлый результат запроса из кеша
					if($cachedUntil < $now)//обновляемся
					{
						$url_without_spaces = str_replace(" ", "%20", $uri);
						 
						if($this->showinfo == true) echo "but data too old, downloading from server...<br>";
						$ch = curl_init();
						curl_setopt($ch, CURLOPT_URL, $url_without_spaces);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
						//выполняем запрос и помещаем результат в строку
						$serverResponse = curl_exec($ch);
						//закрываем запрос
						curl_close($ch);
						
						$result["source"] = "server";
					}
					else//берём из кеша
					{
						if($this->showinfo == true) echo "get from cache, because cachedUntil > now<br>";
						$serverResponse = base64_decode($row["cachedValue"]);
						$result["source"] = "cache";
					}
				}
				else//если в кеше ничего не было, скачиваем
				{
					$url_without_spaces = str_replace(" ", "%20", $uri);
					if($this->showinfo == true) echo "cache empty, downloading from server...<br>";
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $url_without_spaces);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					//выполняем запрос и помещаем результат в строку
					$serverResponse = curl_exec($ch);
					//закрываем запрос
					curl_close($ch);
					//echo "<pre>$serverResponse</pre><br>";
					$result["source"] = "server";
				}
				//проверка ответа сервера. если не xml, а html, сбой в работе сервера.
				if(eregi("<html><body>.*</body></html>", $serverResponse))// || strlen($serverResponse) < 10 || )
				{
					//
					$result["error"] = str_ireplace("<html><body>", "<b>Сообщение сервера eve api:</b>", $serverResponse);
					$result["error"] = str_ireplace("</body></html>", "", $result["error"]);
					//print($result);
					$result["success"] = false;
				}
				else
				{
					//здесь надо сделать проверку на выдачу ошибки в виде xml

					//проверка, есть ли вообще распознаваемый xml в принятом пакете
					if(preg_match("/eveapi/i", $serverResponse) != 0)
					{
						$result["error"] = "";
						$result["success"] = true;
					}
					else
					{
						$result["error"] = "data received without <eveapi/> xml";
						$result["success"] = false;
					}
					//print_r($result);
				}
				//если значение получено не из кеша, сохраняем полученный ответ в кеш
				if($result["source"] == "server" && $result["success"] == true)
				{
					$recordId = GetUniqueId();
					$accountId = $this->accountId;

					$cachedStr = "?";
					$count = preg_match("/(?<=currentTime\>).*(?=\<\/currentTime)/", $serverResponse, $regs);
					if($count > 0)
						$cachedStr = $regs[0];
					//if($this->showinfo == true) print_r($regs);

					$cachedUntilStr = "?";
					$count = preg_match("/(?<=cachedUntil\>).*(?=\<\/cachedUntil)/", $serverResponse, $regs);
					if($count > 0)
						$cachedUntilStr = $regs[0];
					//if($this->showinfo == true) print_r($regs);

					$result["cached"] = $cachedStr;
					$result["cachedUntil"] = $cachedUntilStr;

					$serverResponseStr = base64_encode($serverResponse);

					$query = sprintf("insert into api_cache (recordId, accountId, uri, cached, cachedUntil, cachedValue) " .
							"values('%s', '%s', '%s', '%s', '%s', '%s');",
							$db->real_escape_string($recordId),
							$db->real_escape_string($accountId),
							$db->real_escape_string($t),
							$db->real_escape_string($cachedStr),
							$db->real_escape_string($cachedUntilStr),
							$db->real_escape_string($serverResponseStr));
					if($this->showinfo == true) echo "saving to cache<br>";
					//$qr = ExecuteQuery($query);
					$db->query($query);
					if($this->showinfo == true) echo "query result $db->affected_rows<br>";
				}

				if($result["success"] == true)
				{
					//print_r($result);
					//print("<pre>$serverResponse</pre>");
					if($this->showinfo == true) echo "<pre>" . htmlentities($serverResponse) . "</pre>";
					$DomDoc = null;
					try
					{
						//echo "1<br>";
						//echo htmlentities($serverResponse);
						$DomDoc = DomDocument::loadXML($serverResponse);
						//echo "2<br>";
						//навигационный класс
						$domPath = new DOMXPath($DomDoc);
						//echo "3";

						$row = $domPath->query("descendant::error");
						//смотрим число ошибок. если ноль - значит их не было, если 1 - были
						$cnt = $row->length;
						if($cnt == 1)
						{
							$row = $row->item(0);
							$result["success"] = false;
							$result["error"] = $row->nodeValue;//текст ошибки
							//print("$result[2]<br>");
						}
						else
						{
							$result["success"] = true;
							$result["response"] = $DomDoc;
							$result["error"] = "";
						}
						//echo "success";
					}
					catch(Exception $exc)
					{
						$result["error"] = "document not rerognised";
						$result["success"] = false;
						echo "error";
						//print("$result[error]<br>");
					}
				}

				if($this->showinfo == true) print_r($result);
			}
			catch(Exception $exc)
			{
				//print("<p>что-то не загрузилось нифига...</p>");
				$result["success"] = false;
			}
			return $result;
		}
		//запрос статуса сервера
		//возвращаемый результат - массив:
		//	["serverOpen"] - онайлн ли сервер, True/False
		//	["onlinePlayers"] - число пилотов онлайн
		function GetServerStatus()
		{
			$result = array();
			$result["serverOpen"] = "unknown";
			$result["onlinePlayers"] = "unknown";
			
			$params = array();
			$params["version"] = "2";
			//print($this->userId);
			$apires = $this->ApiRequest("/server/ServerStatus.xml.aspx", $params);
			$result["now"] = $apires["now"];
			if($apires["success"] == true)
			{
				//навигационный класс
				$domPath = new DOMXPath($apires["response"]);
				//получение массива нод, подходящих под описание: result
				$status = $domPath->query("descendant::result");
				//берём первую из них, т.к. их всего одна штука
				//print_r($status);
				//print("<br>");
				$status = $status->item(0);
				//берём список дочерних нод, с инфой о статусе
				//print($status->nodeName);//это нода result
				//print("<br>");
				$status = $status->childNodes;
				//перебираем и добавляем в массив
				//print($status->length);//5 дочерних нод
				//print("<br>");
				$index = 0;
				foreach($status as $statusElement)
				{
					//print($statusElement->localName);//5 дочерних нод
					//print("<br>");
					if($statusElement->localName == "serverOpen")
					{
						$result["serverOpen"] = $statusElement->nodeValue;
					}
					if($statusElement->localName == "onlinePlayers")
					{
						$result["onlinePlayers"] = $statusElement->nodeValue;
					}
				}
			}

			return $result;
		}
		//обновление списка альянсов и корпораций в них
	    function UpdateAlliances()//$accountId, $bar = null)
		{
			//$result = array();
			$params = array();
			//$params["characterId"] = $this->characterId;
			$params["version"] = "2";
			$apires = $this->ApiRequest("/eve/AllianceList.xml.aspx", $params);
			$result = $apires;
			if($apires["success"] == true)
			{
				if($apires["source"] == "server" || $this->updateFromCache)
				{
					//echo $apires[2]->SaveXML();
					//навигационный класс
					$domPath = new DOMXPath($apires["response"]);
					//получение массива нод, подходящих под описание
					$allianceNodes = $domPath->query("descendant::rowset[@name='alliances']");
					//берём первую из них, т.к. их всего одна штука
					$allianceNodes = $allianceNodes->item(0);
					//берём список дочерних нод
					$allianceNodes = $allianceNodes->childNodes;
					//перебираем и добавляем в массив
					$allianceIndex = 0;
					//ExecuteQuery("LOCK TABLES `api_sovereignty` WRITE; delete * from api_sovereignty where accountId = '$accountId';");

					$dblink = OpenDB2();
					//подготовка в обновлению
					$dblink->query("update api_alliances set updateFlag = 0;");
					$dblink->query("update api_corporations set updateFlag = 0;");

					$allianceQuery = "";
					$recordAdded = 0;

					$corporationIndex = 0;
					$corporationQuery = "";

					//if($bar != null) $bar->initialize($nodes->length);

					//$timer = new Timer();
					//$timer->start();

					foreach($allianceNodes as $allianceNode)
					{
						//if($bar != null) $bar->increase();

						if($allianceNode->localName == "row")
						{
							if($allianceIndex == 0)
								$allianceQuery = "replace into api_alliances values ";
							else
								$allianceQuery = $allianceQuery . ",";

							$name = mysql_escape_string($allianceNode->getAttribute("name"));
							$shortName = mysql_escape_string($allianceNode->getAttribute("shortName"));
							$allianceId = $allianceNode->getAttribute("allianceID");
							$executorCorpId = $allianceNode->getAttribute("executorCorpID");
							$memberCount = $allianceNode->getAttribute("memberCount");
							$startDate = $allianceNode->getAttribute("startDate");
							$allianceQuery = $allianceQuery . "('$name', '$shortName', $allianceId, $executorCorpId, $memberCount, '$startDate', 1)";
							$allianceIndex++;
							$recordAdded++;
							if($allianceIndex >= 100)
							{
								//echo($index);
								$allianceIndex = 0;
								$allianceQuery = $allianceQuery . ";";
								$dblink->query($allianceQuery);
								//echo($query);
								//print("<br/>");
							}

							//список корпораций в альянсе
							if($allianceNode->hasChildNodes())
							{
								$corporationNodes = $allianceNode->childNodes;
								foreach($corporationNodes as $corporationNode)
								{
									if($corporationNode->nodeName == "rowset")
									{
										$corporationInfos = $corporationNode->childNodes;
										foreach($corporationInfos as $corporationInfo)
										{
											if($corporationInfo->nodeName == "row")
											{
												if($corporationIndex == 0)
													$corporationQuery = "replace into api_corporations values ";
												else
													$corporationQuery = $corporationQuery . ",";

												$corporationId = $corporationInfo->getAttribute("corporationID");
												$corporationStartDate = $corporationInfo->getAttribute("startDate");
												$corporationQuery = $corporationQuery . "($corporationId, '$startDate', '$allianceId', 1)";
												$corporationIndex++;
												if($corporationIndex >= 100)
												{
													$corporationIndex = 0;
													$corporationQuery = $corporationQuery . ";";
													//echo $corporationQuery;
													$dblink->query($corporationQuery);
													//echo $dblink->error;
												}
											}
										}
									}
								}
							}
						}
					}
					if($allianceIndex > 0)
					{
						$allianceQuery = $allianceQuery . ";";
						$dblink->query($allianceQuery);
					}
					if($corporationIndex >= 100)
					{
						$corporationIndex = 0;
						$corporationQuery = $corporationQuery . ";";
						//echo $corporationQuery;
						$dblink->query($corporationQuery);
						//echo $dblink->error;
					}
					$dblink->query("delete from api_alliances where updateFlag = 0;");
					$dblink->query("delete from api_corporations where updateFlag = 0;");
					$dblink->close();
					//$timer->stop();
					//printf("time: %s seconds", $timer->getTime());
					
					$result["message"] = "Processed $recordAdded alliance(s)";
				}
			}
			else
			{
				$result = "";//$apires[2];
			}
			return $result;
		}

		//обновление списка альянсов и корпораций в них
	    function UpdateOutposts()//$accountId, $bar = null)
		{
			//$result = array();
			$params = array();
			//$params["characterId"] = $this->characterId;
			$params["version"] = "2";
			$apires = $this->ApiRequest("/eve/ConquerableStationList.xml.aspx", $params);
			$result = $apires;
			if($apires["success"] == true)
			{
				if($apires["source"] == "server" || $this->updateFromCache)
				{
					//echo $apires[2]->SaveXML();
					//навигационный класс
					$domPath = new DOMXPath($apires["response"]);
					//получение массива нод, подходящих под описание
					$outpostNodes = $domPath->query("/eveapi/result/rowset[@name='outposts']/row");

					$outpostIndex = 0;
					//ExecuteQuery("LOCK TABLES `api_sovereignty` WRITE; delete * from api_sovereignty where accountId = '$accountId';");

					$dblink = OpenDB2();
					$dblink->query("update api_outposts set updateFlag = 0;");

					$outpostQuery = "";
					$recordAdded = 0;

					//if($bar != null) $bar->initialize($nodes->length);

					//$statementOutpost = $dblink->prepare("insert into api_outposts values (?, ?, ?, ?, ?, ?);");
					//$statementOutpost->bind_param("ssddds", $stationId, $stationName, $stationTypeId, $solarSystemId, $corporationId, $corporationName);

					foreach($outpostNodes as $outpostNode)
					{
						if($outpostIndex == 0)
							$outpostQuery = "insert into api_outposts values ";
						else
							$outpostQuery = $outpostQuery . ",";


						$stationId = $outpostNode->getAttribute("stationID");
						$stationName = $outpostNode->getAttribute("stationName");
						$stationTypeId = $outpostNode->getAttribute("stationTypeID");
						$solarSystemId = $outpostNode->getAttribute("solarSystemID");
						$corporationId = $outpostNode->getAttribute("corporationID");
						$corporationName = $outpostNode->getAttribute("corporationName");

						//$statementOutpost->execute();
						$stationName = mysql_escape_string($stationName);
						$corporationName = mysql_escape_string($corporationName);
						$outpostQuery = $outpostQuery . "('$stationId', '$stationName', $stationTypeId, $solarSystemId, $corporationId, '$corporationName', 1)";
						$outpostIndex++;
						$recordAdded ++;//= $statementOutpost->affected_rows;

						if($outpostIndex >= 100)
						{
							//echo($index);
							$outpostIndex = 0;
							$outpostQuery = $outpostQuery . ";";
							$dblink->query($outpostQuery);
							//echo "<p>$outpostQuery</p>";
							//print("<br/>");
						}
					}
					//$statementOutpost->close();

					if($outpostIndex > 0)
					{
						$outpostQuery = $outpostQuery . ";";
						$dblink->query($outpostQuery);
						//echo "<p>$outpostQuery</p>";
					}

					$dblink->query("delete from api_outposts where updateFlag = 0;");
					$dblink->close();

					$result["message"] = "Processed $recordAdded outposts";
					//ExecuteQuery("UNLOCK TABLES");
					//$value = $domPath->query("descendant::currentTime")->item(0);
					//$result->currentTime = $value->nodeValue;
					//$value = $domPath->query("descendant::cachedUntil")->item(0);
				}
			}
			else
			{
				$result = "";//$apires[2];
			}
			return $result;
		}

		//обновление списка ошибок
	    function UpdateErrors()//$accountId, $bar = null)
		{
			//$result = array();
			$params = array();
			//$params["characterId"] = $this->characterId;
			$params["version"] = "2";
			$apires = $this->ApiRequest("/eve/ErrorList.xml.aspx", $params);
			$result = $apires;
			if($apires["success"] == true)
			{
				if($apires["source"] == "server" || $this->updateFromCache)
				{
					//echo $apires[2]->SaveXML();
					//навигационный класс
					$domPath = new DOMXPath($apires["response"]);
					//получение массива нод, подходящих под описание
					$errorNodes = $domPath->query("/eveapi/result/rowset[@name='errors']/row");

					$errorIndex = 0;
					//ExecuteQuery("LOCK TABLES `api_sovereignty` WRITE; delete * from api_sovereignty where accountId = '$accountId';");

					$dblink = OpenDB2();
					$dblink->query("update api_errors set updateFlag = 0;");

					$errorQuery = "";
					$recordAdded = 0;

					//if($bar != null) $bar->initialize($nodes->length);

					$updateFlag = 1;
					$statementError = $dblink->prepare("replace into api_errors values (?, ?, ?);");
					$statementError->bind_param("dsd", $errorCode, $errorText, $updateFlag);

					foreach($errorNodes as $errorNode)
					{
						$errorCode = $errorNode->getAttribute("errorCode");
						$errorText = $errorNode->getAttribute("errorText");

						$statementError->execute();

						$recordAdded += $statementError->affected_rows;
					}
					$statementError->close();

					$dblink->query("delete from api_errors where updateFlag = 0;");
					$dblink->close();

					$result["message"] = "Processed $recordAdded errors";
				}
			}
			else
			{
				$result = "";//$apires[2];
			}
			return $result;
		}

		//обновление списка ошибок
	    function UpdateSovereignty()//$accountId, $bar = null)
		{
			//$result = array();
			$params = array();
			//$params["characterId"] = $this->characterId;
			$params["version"] = "2";
			$apires = $this->ApiRequest("/map/Sovereignty.xml.aspx", $params);
			$result = $apires;
			if($apires["success"] == true)
			{
				if($apires["source"] == "server" || $this->updateFromCache)
				{
					//echo $apires[2]->SaveXML();
					//навигационный класс
					$domPath = new DOMXPath($apires["response"]);
					//получение массива нод, подходящих под описание
					$nodes = $domPath->query("/eveapi/result/rowset[@name='solarSystems']/row");

					$index = 0;
					//ExecuteQuery("LOCK TABLES `api_sovereignty` WRITE; delete * from api_sovereignty where accountId = '$accountId';");

					$db = OpenDB2();
					$db->query("update api_sovereignty set updateFlag = 0;");

					$recordAdded = 0;

					//if($bar != null) $bar->initialize($nodes->length);

					$updateFlag = 1;
					$statement = $db->prepare("insert into api_sovereignty values (?, ?, ?, ?, ?, ?, ?);");
					$statement->bind_param("dddddsd", $solarSystemId, $allianceId, $constellationSovereignty, $sovereigntyLevel, $factionId, $solarSystemName, $updateFlag);

					foreach($nodes as $node)
					{
						$solarSystemId = $node->getAttribute("solarSystemID");
						$allianceId = $node->getAttribute("allianceID");
						$constellationSovereignty = $node->getAttribute("constellationSovereignty");
						$sovereigntyLevel = $node->getAttribute("sovereigntyLevel");
						$factionId = $node->getAttribute("factionID");
						$solarSystemName = $node->getAttribute("solarSystemName");

						$statement->execute();

						$recordAdded += $statement->affected_rows;
					}
					$statement->close();

					$db->query("delete from api_sovereignty where updateFlag = 0;");
					$db->close();

					$result["message"] = "Processed $recordAdded sovereignty";
				}
			}
			else
			{
				$result = "";//$apires[2];
			}
			return $result;
		}

		//обновление Factional Warfare Top 100 Stats 
	    function UpdateFacWarTopStats()//$accountId, $bar = null)
		{
			//$result = array();
			$params = array();
			//$params["characterId"] = $this->characterId;
			$params["version"] = "2";
			$apires = $this->ApiRequest("/eve/FacWarTopStats.xml.aspx", $params);
			$result = $apires;

			if($apires["success"] == true)
			{
				if($apires["source"] == "server" || $this->updateFromCache)
				{
					$domPath = new DOMXPath($apires["response"]);
					//получение массива нод, подходящих под описание rowset, здесь их будет много разных
					$rowsets = $domPath->query("descendant::rowset");


					$dblink = OpenDB2();
					$dblink->query("delete from api_facwartopstats;");

					$statementStat = $dblink->prepare("insert into api_facwartopstats values (?, ?, ?, ?, ?);");
					$statementStat->bind_param("ssdsd", $forWho, $statName, $id, $name, $value);

					$recordAdded = 0;
					//перебираем все rowset'ы
					for($rowsetIndex = 0; $rowsetIndex < $rowsets->length; $rowsetIndex++)
					{
						$rowset = $rowsets->item($rowsetIndex);
						$rowsetParent = $rowset->parentNode;
						//printf("%s %s<br>", $rowset->getAttribute("name"), $rowsetParent->nodeName);

						$forWho = $rowsetParent->nodeName;
						$statName = $rowset->getAttribute("name");
						$columns = split(",", $rowset->getAttribute("columns"));

						//берём список дочерних нод
						$rows = $rowset->childNodes;
						//$rows = $rows->item(0);
						//echo $row->length;
						foreach($rows as $row)
						{
							//if($bar != null) $bar->increase();

							if($row->localName == "row")
							{
								$id = $row->getAttribute($columns[0]);
								$name = $row->getAttribute($columns[1]);
								$value = $row->getAttribute($columns[2]);
								$statementStat->execute();

								$recordAdded += $statementStat->affected_rows;
							}
						}
					}

					$statementStat->close();

					$dblink->close();

					$result["message"] = "Processed $recordAdded FacWarTopStats";
					
				}
			}
			else
			{
				$result = "";//$apires[2];
			}
			return $result;
		}

		//обновление списка refTypes
	    function UpdateRefTypes()//$accountId, $bar = null)
		{
			//$result = array();
			$params = array();
			//$params["characterId"] = $this->characterId;
			$params["version"] = "2";
			$apires = $this->ApiRequest("/eve/RefTypes.xml.aspx", $params);
			$result = $apires;
			if($apires["success"] == true)
			{
				if($apires["source"] == "server" || $this->updateFromCache)
				{
					//echo $apires[2]->SaveXML();
					//навигационный класс
					$domPath = new DOMXPath($apires["response"]);
					//получение массива нод, подходящих под описание
					$refNodes = $domPath->query("/eveapi/result/rowset[@name='refTypes']/row");

					$errorIndex = 0;
					//ExecuteQuery("LOCK TABLES `api_sovereignty` WRITE; delete * from api_sovereignty where accountId = '$accountId';");

					$dblink = OpenDB2();
					$dblink->query("update api_reftypes set updateFlag = 0;");

					$errorQuery = "";
					$recordAdded = 0;

					//if($bar != null) $bar->initialize($nodes->length);

					$updateFlag = 1;
					$statementRef = $dblink->prepare("insert into api_reftypes values (?, ?, ?);");
					$statementRef->bind_param("dsd", $refTypeId, $refTypeName, $updateFlag);

					foreach($refNodes as $refNode)
					{
						$refTypeId = $refNode->getAttribute("refTypeID");
						$refTypeName = $refNode->getAttribute("refTypeName");

						$statementRef->execute();

						$recordAdded += $statementRef->affected_rows;
					}
					$statementRef->close();

					$dblink->query("delete from api_reftypes where updateFlag = 0;");
					$dblink->close();

					$result["message"] = "Processed $recordAdded refTypes";
				}
			}
			else
			{
				$result = "";//$apires[2];
			}
			return $result;
		}
		//получение названия по id
	    function GetNameById($id)
		{
			$result = array();
			$result["id"] = $id;
			$result["name"] = "";
			$params = array();
			//$params["characterId"] = $this->characterId;
			$params["version"] = "2";
			$params["ids"] = $id;
			$apires = $this->ApiRequest("/eve/CharacterName.xml.aspx", $params);
			if($apires["success"] == true)
			{
				//if($apires["source"] == "server")// || $this->updateFromCache)
				{
					//навигационный класс
					$domPath = new DOMXPath($apires["response"]);
					//получение массива нод, подходящих под описание
					$nodes = $domPath->query("descendant::rowset[@name='characters']/row");

					foreach($nodes as $node)
					{
						$charId = $node->getAttribute("characterID");
						$charName = $node->getAttribute("name");
						$result["name"] = $charName;
					}
				}
			}
			else
			{
				$result["name"] = "";//$apires[2];
			}
			return $result;
		}
		//получение названия по id
	    function GetIdByName($name)
		{
			$result = array();
			$result["name"] = $name;
			$result["id"] = "";
			$params = array();
			//$params["characterId"] = $this->characterId;
			$params["version"] = "2";
			$params["names"] = $name;
			$apires = $this->ApiRequest("/eve/CharacterID.xml.aspx", $params);
			if($apires["success"] == true)
			{
				//if($apires["source"] == "server")// || $this->updateFromCache)
				{
					//echo $apires[2]->SaveXML();
					//навигационный класс
					$domPath = new DOMXPath($apires["response"]);
					//получение массива нод, подходящих под описание
					$nodes = $domPath->query("descendant::rowset[@name='characters']/row");

					foreach($nodes as $node)
					{
						$charId = $node->getAttribute("characterID");
						$charName = $node->getAttribute("name");
						$result["id"] = $charId;
					}
				}
			}
			else
			{
				$result["id"] = "";//$apires[2];
			}
			return $result;
		}
		//получение информации о корпорации
		function GetCorpCorporationSheet($corporationId = null)
		{
			$result = array();
			$params = array();
			if($corporationId == null)
			{
				$params["userID"] = $this->userId;
				$params["apiKey"] = $this->apiKey;
				$params["characterID"] = $this->characterId;
			}
			else
			{
				$params["corporationID"] = $corporationId;
			}
			$params["version"] = "2";
			$apires = $this->ApiRequest("/corp/CorporationSheet.xml.aspx", $params);
			//print_r($apires);
			$result = $apires;
			if($apires["success"] == true)
			{
				//навигационный класс
				$domPath = new DOMXPath($apires["response"]);
				//получение массива нод, подходящих под описание
				//берём первую из них, т.к. их всего одна штука

				$value = $domPath->query("descendant::currentTime")->item(0);
				//$result->currentTime = $this->StringToDateTime($value->nodeValue);
				$result["currentTime"] = $value->nodeValue;
				$value = $domPath->query("descendant::cachedUntil")->item(0);
				//$result->cachedUntil = $this->StringToDateTime($value->nodeValue);
				$result["cachedUntil"] = $value->nodeValue;
				$value = $domPath->query("descendant::corporationID")->item(0);
				$result["corporationId"] = $value->nodeValue;
				$value = $domPath->query("descendant::corporationName")->item(0);
				$result["corporationName"] = $value->nodeValue;
				$value = $domPath->query("descendant::ticker")->item(0);
				$result["ticker"] = $value->nodeValue;
				$value = $domPath->query("descendant::ceoID")->item(0);
				$result["ceoId"] = $value->nodeValue;
				$value = $domPath->query("descendant::ceoName")->item(0);
				$result["ceoName"] = $value->nodeValue;
				$value = $domPath->query("descendant::stationID")->item(0);
				$result["stationId"] = $value->nodeValue;
				$value = $domPath->query("descendant::stationName")->item(0);
				$result["stationName"] = $value->nodeValue;
				$value = $domPath->query("descendant::description")->item(0);
				$result["description"] = $value->nodeValue;
				$value = $domPath->query("descendant::url")->item(0);
				$result["url"] = $value->nodeValue;
				$value = $domPath->query("descendant::allianceID")->item(0);
				$result["allianceId"] = $value->nodeValue;
				$value = $domPath->query("descendant::allianceName")->item(0);
				$result["allianceName"] = $value->nodeValue;
				$value = $domPath->query("descendant::taxRate")->item(0);
				$result["taxRate"] = $value->nodeValue;
				$value = $domPath->query("descendant::memberCount")->item(0);
				$result["memberCount"] = $value->nodeValue;
				$value = $domPath->query("descendant::memberLimit")->item(0);
				if($value != null)
					$result["memberLimit"] = $value->nodeValue;
				else
					$result["memberLimit"] = "?";
				$value = $domPath->query("descendant::shares")->item(0);
				$result["shares"] = $value->nodeValue;
				//$value = $domPath->query("descendant::corporationID")->item(0);

				//$divisions = $domPath->query("descendant::rowset[@name='divisions']//row[@accountKey='1000']");
				//$node = $divisions->item(0);//$domPath->query("descendant::rowset[@name='divisions']\row[@accountKey='1000']");
				//$val = $node->getAttribute('description');
				//print("$val");
				$divisionsArray = array();
				$divisions = $domPath->query("descendant::rowset[@name='divisions']/row");
				if($divisions != null)
				{
					foreach($divisions as $division)
					{
						$accountKey = $division->getAttribute("accountKey");
						$description = $division->getAttribute("description");
						$divisionsArray[$accountKey] = $description;
					}
					$result["divisions"] = $divisionsArray;
				}

				//$tempArray = array();
				$walletDivisionsArray = array();
				$divisions = $domPath->query("descendant::rowset[@name='walletDivisions']/row");
				if($divisions != null)
				{
					foreach($divisions as $division)
					{
						$accountKey = $division->getAttribute("accountKey");
						$description = $division->getAttribute("description");
						$walletDivisionsArray[$accountKey] = $description;
						//print("<p>$accountKey - $description</p>");
						//$sss = $result->walletDivisions[$accountKey];
						//print("<p>$accountKey -- $sss</p>");
					}
					$result["walletDivisions"] = $walletDivisionsArray;
				}
				//$result->walletDivisions = $tempArray;
				//$sss = count($result->walletDivisions);
				//print("<p>walletDivision - $sss</p>");
			}
			else
			{
				//print("<p>Ошибка: $apires[2]</p>");
				//$result = null;
			}
			//print_r($result);
			return $result;
		}
		//
		function UpdateAccountBalances()
		{
			$result = array();
			$params = array();
			$params["userID"] = $this->userId;
			$params["apiKey"] = $this->apiKey;
			$params["characterID"] = $this->characterId;
			$params["version"] = "2";
			$apires = $this->ApiRequest("/corp/AccountBalance.xml.aspx", $params);
			$result = $apires;
			$result["message"] = "";
			if($apires["success"] == true && $apires["error"] == "")
			{
				if($apires["source"] == "server" || $this->updateFromCache)
				{
					//echo $apires[2]->SaveXML();
					//навигационный класс
					$domPath = new DOMXPath($apires["response"]);
					//получение массива нод, подходящих под описание
					$nodes = $domPath->query("/eveapi/result/rowset[@name='accounts']/row");

					$index = 0;
					//ExecuteQuery("LOCK TABLES `api_sovereignty` WRITE; delete * from api_sovereignty where accountId = '$accountId';");
					//$qr = ExecuteQuery("select count(*) as _count_ from api_account_balance where accountId = '$accountId';");
					//$row = mysql_fetch_array($qr);
					//$records_exists = $row["_count_"];
					//if($records_exists
					$query = "";
					$recordAdded = 0;

					//if($bar != null) $bar->initialize($nodes->length);

					$dblink = OpenDB2();

					foreach($nodes as $node)
					{
						//if($bar != null) $bar->increase();

						//вместо прежней проверки и вставки/обновления теперь делаем insert ignore и update
						$accountKey = $node->getAttribute("accountKey");
						$balance = $node->getAttribute("balance");
						$recordId = GetUniqueId();
						$now = date("Y-m-d H:i:s");

						$query = "insert ignore into api_account_balance set recordId = '$recordId', accountId = '$this->accountId', accountKey = $accountKey, balance = $balance, balanceUpdated = '$now';";
						$dblink->query($query);
						//echo "$query<br>";
						$query = "update api_account_balance set balance = $balance where accountId = '$this->accountId' and accountKey = $accountKey;";
						$dblink->query($query);
						//echo "$query<br>";
//insert ignore into api_account_balance values('2', '1', 1000, 1, '2009-01-01');
//update api_account_balance set `balance` = 2, `balanceUpdated` = '2009-01-03' where `accountId` = 1 and `accountKey` = 1000;

						//echo $dblink->multi_query($query);
						//echo "<br>";
						//$max_time = ini_get("max_execution_time");
						//set_time_limit(600);

						$res = $this->UpdateWalletJournal($accountKey);
						$result["message"] .= $res["message"] . "; ";

						$res = $this->UpdateWalletTransactions($accountKey);
						$result["message"] .= $res["message"] . ";<br>";

						//set_time_limit($max_time);
					}
					$dblink->close();
				}
			}
			return $result;
		}
		function UpdateWalletJournal($accountKey)
		{
			$result = array();
			$params = array();
			$params["userID"] = $this->userId;
			$params["apiKey"] = $this->apiKey;
			$params["characterID"] = $this->characterId;
			$params["version"] = "2";

			$params["accountKey"] = "$accountKey";
			$params["beforeRefId"] = 0;
			$apires = $this->ApiRequest("/corp/WalletJournal.xml.aspx", $params);
			$result = $apires;
			$beforeRefId = 0;
			$recordAdded = 0;
			$recordNotAdded = false;
			while($apires["success"] == true && $apires["error"] == "")
			{
				//echo $apires[2]->SaveXML();
				//навигационный класс
				$domPath = new DOMXPath($apires["response"]);
				//получение массива нод, подходящих под описание
				$nodes = $domPath->query("/eveapi/result/rowset[@name='entries']/row");

				$index = 0;
				$counter = 0;
				//ExecuteQuery("LOCK TABLES `api_sovereignty` WRITE; delete * from api_sovereignty where accountId = '$accountId';");
				//$qr = ExecuteQuery("select count(*) as _count_ from api_account_balance where accountId = '$accountId';");
				//$row = mysql_fetch_array($qr);
				//$records_exists = $row["_count_"];
				//if($records_exists

				$dblink = OpenDB2();

				$query = "insert ignore into api_wallet_journal values ";
				foreach($nodes as $node)
				{
					$counter++;

					if($index > 0)
						$query = $query . ",";

					$refId = $node->getAttribute("refID");
					$beforeRefId = $refId;

					$query = $query . "('" . GetUniqueId() . "', '$this->accountId', $accountKey, $refId, '" .
						$node->getAttribute("date") . "', " .
						$node->getAttribute("refTypeID") . ", '" .
						mysql_escape_string($node->getAttribute("ownerName1")) . "', " .
						$node->getAttribute("ownerID1") . ", '" .
						mysql_escape_string($node->getAttribute("ownerName2")) . "', " .
						$node->getAttribute("ownerID2") . ", '" .
						mysql_escape_string($node->getAttribute("argName1")) . "', " .
						$node->getAttribute("argID1") . ", " .
						$node->getAttribute("amount") . ", " .
						$node->getAttribute("balance") . ", '" .
						mysql_escape_string($node->getAttribute("reason")) . "')";

					$index++;
					if($this->showinfo) echo(".");
					if($index >= 100)
					{
						//echo($index);
						$index = 0;
						$query = $query . ";";
						$dblink->query($query);
						$recordAdded += $dblink->affected_rows;
						$query = "insert ignore into api_wallet_journal values ";
						//echo($query);
						//print("<br/>");
						if($this->showinfo) echo("<br/>");
					}
				}
				if($index > 0)
				{
					$query = $query . ";";
					$dblink->query($query);
					$recordAdded += $dblink->affected_rows;
					//echo($index);
					if($this->showinfo) echo("<br/>");
				}
				$dblink->close();
				if(!$recordNotAdded && $counter > 0)//если $counter = 0, это пустой rowset, данных больше не скачать
				{
					$params["beforeRefId"] = $beforeRefId;
					$apires = $this->ApiRequest("/corp/WalletJournal.xml.aspx", $params);
				}
				else
				{
					$apires["success"] = false;
				}
			}
			$result["message"] = "Journal $accountKey: added $recordAdded rows";
			if($this->showinfo) echo($result["message"]);
			return $result;
		}
		function UpdateWalletTransactions($accountKey)
		{
			$result = array();
			$params = array();
			$params["userID"] = $this->userId;
			$params["apiKey"] = $this->apiKey;
			$params["characterID"] = $this->characterId;
			$params["version"] = "2";

			$params["accountKey"] = "$accountKey";
			$params["beforeTransId"] = 0;
			$apires = $this->ApiRequest("/corp/WalletTransactions.xml.aspx", $params);
			$result = $apires;
			$beforeTransId = 0;
			$recordAdded = 0;
			$recordNotAdded = false;
			while($apires["success"] == true && $apires["error"] == "")
			{
				//echo $apires[2]->SaveXML();
				//навигационный класс
				$domPath = new DOMXPath($apires["response"]);
				//получение массива нод, подходящих под описание
				$nodes = $domPath->query("/eveapi/result/rowset[@name='transactions']/row");

				//перебираем и добавляем в массив
				$index = 0;
				$counter = 0;

				$dblink = OpenDB2();

				//ExecuteQuery("LOCK TABLES `api_sovereignty` WRITE; delete * from api_sovereignty where accountId = '$accountId';");
				//$qr = ExecuteQuery("select count(*) as _count_ from api_account_balance where accountId = '$accountId';");
				//$row = mysql_fetch_array($qr);
				//$records_exists = $row["_count_"];
				//if($records_exists
				$query = "insert ignore into api_wallet_transactions values ";
				foreach($nodes as $node)
				{
					$counter++;

					if($index > 0)
						$query = $query . ",";

					$transId = $node->getAttribute("transactionID");
					$beforeTransId = $transId;

					$query = $query . "('" . GetUniqueId() . "', '$this->accountId', $accountKey, $transId, '" .
						$node->getAttribute("transactionDateTime") . "', " .
						$node->getAttribute("quantity") . ", '" .
						mysql_escape_string($node->getAttribute("typeName")) . "', " .
						$node->getAttribute("typeID") . ", " .
						$node->getAttribute("price") . ", " .
						$node->getAttribute("clientID") . ", '" .
						mysql_escape_string($node->getAttribute("clientName")) . "', " .
						$node->getAttribute("characterID") . ", '" .
						mysql_escape_string($node->getAttribute("characterName")) . "', " .
						$node->getAttribute("stationID") . ", '" .
						mysql_escape_string($node->getAttribute("stationName")) . "', '" .
						mysql_escape_string($node->getAttribute("transactionType")) . "', '" .
						mysql_escape_string($node->getAttribute("transactionFor")) . "')";

					$index++;
					if($this->showinfo)echo(".");
					if($index >= 100)
					{
						//echo($index);
						$index = 0;
						$query = $query . ";";
						$dblink->query($query);
						$recordAdded += $dblink->affected_rows;
						$query = "insert ignore into api_wallet_transactions values ";
						//echo($query);
						//echo " ";
						//print("<br/>");
						if($this->showinfo) echo("<br/>");
					}
					//else
					//	$recordNotAdded = true;
					//$affected_rows = mysql_affected_rows($qr);
					//print("$query<br>$qr<hr>");
					//mysql_free_result($qr);
				}
				if($index > 0)
				{
					//$index = 0;
					$query = $query . ";";
					//echo($query);
					$dblink->query($query);
					$recordAdded += $dblink->affected_rows;
					//echo($index);
					if($this->showinfo) echo("<br/>");
				}
				//ExecuteQuery("UNLOCK TABLES");
				//$value = $domPath->query("descendant::currentTime")->item(0);
				//$result->currentTime = $value->nodeValue;
				//$value = $domPath->query("descendant::cachedUntil")->item(0);
				$dblink->close();
				if(!$recordNotAdded && $counter > 0)//если $counter = 0, это пустой rowset, данных больше не скачать
				{
					$params["beforeTransId"] = $beforeTransId;
					$apires = $this->ApiRequest("/corp/WalletTransactions.xml.aspx", $params);
				}
				else
				{
					$apires["success"] = false;
				}
			}
			$result["message"] = "Trans $accountKey: added $recordAdded rows";
			if($this->showinfo) echo($result["message"]);
			return $result;
		}
		
		function UpdateMemberTracking()
		{
			$result = array();
			$params = array();
			$params["userID"] = $this->userId;
			$params["apiKey"] = $this->apiKey;
			$params["characterID"] = $this->characterId;
			$params["version"] = "2";
			$apires = $this->ApiRequest("/corp/MemberTracking.xml.aspx", $params);
			$result = $apires;
			$result["message"] = "";
			if($apires["success"] == true && $apires["error"] == "")
			{
				if($apires["source"] == "server" || $this->updateFromCache)
				{
					//echo $apires[2]->SaveXML();
					//навигационный класс
					$domPath = new DOMXPath($apires["response"]);
					//получение массива нод, подходящих под описание
					$nodes = $domPath->query("/eveapi/result/rowset[@name='members']/row");
					
					$index = 0;
					//ExecuteQuery("LOCK TABLES `api_sovereignty` WRITE; delete * from api_sovereignty where accountId = '$accountId';");
					//$qr = ExecuteQuery("select count(*) as _count_ from api_account_balance where accountId = '$accountId';");
					//$row = mysql_fetch_array($qr);
					//$records_exists = $row["_count_"];
					//if($records_exists
					$query = "";
					$recordAdded = 0;

					//if($bar != null) $bar->initialize($nodes->length);

					$db = OpenDB2();
					//$db->query("delete * from api_member_tracking where accountId = '$this->accountId';");

					foreach($nodes as $node)
					{
						//if($bar != null) $bar->increase();

						//вместо прежней проверки и вставки/обновления теперь делаем insert ignore и update
						$recordId = GetUniqueId();
						$now = date("Y-m-d H:i:s");

						$query = sprintf(
							"insert ignore into api_member_tracking set ".
							"recordId = '%s', accountId = '%s', ".
							"characterId = %d, name = '%s', startDateTime = '%s', ".
							"baseId = %d, base = '%s', title = '%s', ".
							"logonDateTime = '%s', logoffDateTime = '%s', ".
							"locationId = %d, location = '%s', ".
							"shipTypeId = %d, shipType = '%s', ".
							"roles = %d, grantableRoles = %d, updated = '$now';",
							$recordId, $this->accountId,
							$node->getAttribute("characterID"), mysql_escape_string($node->getAttribute("name")), $node->getAttribute("startDateTime"),
							$node->getAttribute("baseID"), mysql_escape_string($node->getAttribute("base")), mysql_escape_string($node->getAttribute("title")),
							$node->getAttribute("logonDateTime"), $node->getAttribute("logoffDateTime"),
							$node->getAttribute("locationID"), mysql_escape_string($node->getAttribute("location")),
							$node->getAttribute("shipTypeID"), mysql_escape_string($node->getAttribute("shipType")),
							$node->getAttribute("roles"), $node->getAttribute("grantableRoles")
							);
						$db->query($query);
						//echo "$query<br>";
						$query = sprintf(
							"update api_member_tracking set ".
							"name = '%s', startDateTime = '%s', ".
							"baseId = %d, base = '%s', title = '%s', ".
							"logonDateTime = '%s', logoffDateTime = '%s', ".
							"locationId = %d, location = '%s', ".
							"shipTypeId = %d, shipType = '%s', ".
							"roles = %d, grantableRoles = %d, updated = '$now' ".
							"where accountId = '%s' and characterId = %d;",
							mysql_escape_string($node->getAttribute("name")), $node->getAttribute("startDateTime"),
							$node->getAttribute("baseID"), mysql_escape_string($node->getAttribute("base")), mysql_escape_string($node->getAttribute("title")),
							$node->getAttribute("logonDateTime"), $node->getAttribute("logoffDateTime"),
							$node->getAttribute("locationID"), mysql_escape_string($node->getAttribute("location")),
							$node->getAttribute("shipTypeID"), mysql_escape_string($node->getAttribute("shipType")),
							$node->getAttribute("roles"), $node->getAttribute("grantableRoles"),
							$this->accountId, $node->getAttribute("characterID")
							);
						$db->query($query);
					}
					$old = date("Y-m-d H:i:s", strtotime("-3 second"));
					$db->query("delete from api_member_tracking where accountId = '$this->accountId' and updated < '$old';");
					$db->close();
				}
			}
			return $result;
		}

		//обновление списка киллов
		function UpdateKillLog()
		{
			$result = array();
			$params = array();
			$params["userID"] = $this->userId;
			$params["apiKey"] = $this->apiKey;
			$params["characterID"] = $this->characterId;
			$params["version"] = "2";

			//$params["beforeKillId"] = 0;
			$apires = $this->ApiRequest("/corp/KillLog.xml.aspx", $params);
			$result = $apires;
			$recordAdded = 0;
			$index = 0;
			$counter = 0;
			while($apires["success"] == true && $apires["error"] == "")
			{
				//echo $apires[2]->SaveXML();
				//навигационный класс
				$xpath = new DOMXPath($apires["response"]);
				//получение массива нод, подходящих под описание
				$nodes = $xpath->query("/eveapi/result/rowset[@name='kills']/row");
				//перебираем и добавляем в массив
				//ExecuteQuery("LOCK TABLES `api_sovereignty` WRITE; delete * from api_sovereignty where accountId = '$accountId';");
				//$qr = ExecuteQuery("select count(*) as _count_ from api_account_balance where accountId = '$accountId';");
				//$row = mysql_fetch_array($qr);
				//$records_exists = $row["_count_"];
				//if($records_exists

				$accountId = $this->accountId;

				$db = OpenDB2();
				$insertKill = $db->prepare("insert ignore into api_kills values (?, ?, ?, ?, ?, ?);");
				$insertKill->bind_param("ssddsd", $recordKillId, $accountId, $killId, $solarSystemId, $killTime, $moonId);

				$insertVictim = $db->prepare("insert ignore into api_kills_victims values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);");
				$insertVictim->bind_param("sssdsdsdsdd", $recordVictimId, $accountId, $recordKillId, $characterId, $characterName, $corporationId, $corporationName, $allianceId, $allianceName, $damageTaken, $shipTypeId);

				$insertAttacker = $db->prepare("insert ignore into api_kills_attackers values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);");
				$insertAttacker->bind_param("sssdsdsdsddddd", $recordAttackerId, $accountId, $recordKillId, $characterId, $characterName, $corporationId, $corporationName, $allianceId, $allianceName, $securityStatus, $damageDone, $finalBlow, $weaponTypeId, $shipTypeId);

				foreach($nodes as $node)
				{
					$counter++;

					$recordKillId = GetUniqueId();
					$killId = $node->getAttribute("killID");
					$solarSystemId = $node->getAttribute("solarSystemID");
					$killTime = $node->getAttribute("killTime");
					$moonId = $node->getAttribute("moonID");

					$insertKill->execute();
					if($insertKill->affected_rows == 1)
					{
						$index++;

						$path = "/eveapi/result/rowset[@name='kills']/row[@killID='$killId']/victim";
						$r1 = $xpath->query($path);

						$rowVictim = $r1->item(0);
						$recordVictimId = GetUniqueId();
						$characterId = $rowVictim->getAttribute("characterID");
						$characterName = $rowVictim->getAttribute("characterName");
						$corporationId = $rowVictim->getAttribute("corporationID");
						$corporationName = $rowVictim->getAttribute("corporationName");
						$allianceId = $rowVictim->getAttribute("allianceID");
						$allianceName = $rowVictim->getAttribute("allianceName");
						$damageTaken = $rowVictim->getAttribute("damageTaken");
						$shipTypeId = $rowVictim->getAttribute("shipTypeID");

						$insertVictim->execute();

						$path = "/eveapi/result/rowset[@name='kills']/row[@killID='$killId']/rowset[@name='attackers']/row";
						$r1 = $xpath->query($path);
						foreach($r1 as $rowAttacker)
						{
							$recordAttackerId = GetUniqueId();
							$characterId = $rowAttacker->getAttribute("characterID");
							$characterName = $rowAttacker->getAttribute("characterName");
							$corporationId = $rowAttacker->getAttribute("corporationID");
							$corporationName = $rowAttacker->getAttribute("corporationName");
							$allianceId = $rowAttacker->getAttribute("allianceID");
							$allianceName = $rowAttacker->getAttribute("allianceName");
							$securityStatus = $rowAttacker->getAttribute("securityStatus");
							$damageDone = $rowAttacker->getAttribute("damageDone");
							$finalBlow = $rowAttacker->getAttribute("finalBlow");
							$weaponTypeId = $rowAttacker->getAttribute("weaponTypeID");
							$shipTypeId = $rowAttacker->getAttribute("shipTypeID");

							$insertAttacker->execute();
						}

						$path = "/eveapi/result/rowset[@name='kills']/row[@killID='$killId']/rowset[@name='items']/row";
						$this->ProcessKillItems($xpath, $db, $path, $recordKillId, "");
					}
					if($this->showinfo) echo(".");
				}
				if($this->showinfo) echo("<br/>");
				$db->close();

				if($counter > 0)//если $counter = 0, это пустой rowset, данных больше не скачать
				{
					$params["beforeKillId"] = $killId;
					$apires = $this->ApiRequest("/corp/KillLog.xml.aspx", $params);
				}
				else
				{
					$apires["success"] = false;
				}
			}
			//$result["message"] = "Journal $accountKey: added $recordAdded rows";
			//if($this->showinfo) echo($result["message"]);
			$result["message"] = "Added: $index / $counter<br>";
			return $result;
		}
		function ProcessKillItems($xpath, $db, $path, $recordKillId, $parentId)
		{
			$rowItems = $xpath->query($path);
			foreach($rowItems as $rowItem)
			{
				$recordItemId = GetUniqueId();
				$hasChilds = $rowItem->hasChildNodes();
				$db->query(sprintf("insert into api_kills_items values ('%s', '%s', '%s', '%s', %d, %d, %d, %d, %d);",
					$recordItemId, $this->accountId, $recordKillId, $parentId,
					$rowItem->getAttribute("typeID"),
					$rowItem->getAttribute("flag"),
					$rowItem->getAttribute("qtyDropped"),
					$rowItem->getAttribute("qtyDestroyed"),
					$hasChilds));
				//printf("%s %s<br>", $rowItem->nodeName, $path);
				if($hasChilds)
				{
					$this->ProcessKillItems($xpath, $db, $path . "/rowset[@name='items']/row", $recordKillId, $recordItemId);
				}
			}
		}

		//обновление списка assets
		function UpdateAssets()
		{
			$result = array();
			$params = array();
			$params["userID"] = $this->userId;
			$params["apiKey"] = $this->apiKey;
			$params["characterID"] = $this->characterId;
			$params["version"] = "2";

			//$params["beforeKillId"] = 0;
			$apires = $this->ApiRequest("/corp/AssetList.xml.aspx", $params);
			$result = $apires;
			$recordAdded = 0;
			//$index = 0;
			$count = 0;
			if($apires["success"] == true && $apires["error"] == "")
			{
				//echo $apires[2]->SaveXML();
				//навигационный класс
				$xpath = new DOMXPath($apires["response"]);

				$accountId = $this->accountId;

				$db = OpenDB2();

				$db->query("delete from api_assets where accountId = '$accountId';");

				$path = "/eveapi/result/rowset[@name='assets']/row";
				//$this->AssetIndex = 0;
				$count = $this->ProcessAssetItems($xpath, $db, $path, 0, "", 0);

				$db->close();
			}
			//$result["message"] = "Journal $accountKey: added $recordAdded rows";
			//if($this->showinfo) echo($result["message"]);
			$result["message"] = "Added: $count";
			return $result;
		}
		//var $AssetIndex;
		function ProcessAssetItems($xpath, $db, $path, $locationId, $parentId, $level)
		{
			//echo $path . "<br>\n";
			$rowItems = $xpath->query($path);
			$index = 0;
			$count = 0;
			//echo $rowItems->length . "\n";
			//return;
			foreach($rowItems as $rowItem)
			{
				$index++;
				$recordItemId = GetUniqueId();
				$hasChilds = $rowItem->hasChildNodes();

				//print_r($rowItem);
				if($rowItem->hasAttribute("locationID"))
				{
					$locationId = $rowItem->getAttribute("locationID");
					//echo "{$this->AssetIndex} $locationId <br>\n";
					//$this->locindex++;
				}
				$itemId = $rowItem->getAttribute("itemID");
				/*printf("%d\t%d\t%d\t%d\t%d\t%d\t%d<br>\n",
					$locationId,
					$itemId,
					$rowItem->getAttribute("typeID"),
					$rowItem->getAttribute("quantity"),
					$rowItem->getAttribute("flag"),
					$rowItem->getAttribute("singleton"),
					$hasChilds);*/
				$db->query(sprintf("insert into api_assets values ('%s', '%s', '%s', %d, %d, %d, %d, %d, %d, %d);",
					$recordItemId, $this->accountId, $parentId,
					$locationId,
					$rowItem->getAttribute("itemID"),
					$rowItem->getAttribute("typeID"),
					$rowItem->getAttribute("quantity"),
					$rowItem->getAttribute("flag"),
					$rowItem->getAttribute("singleton"),
					$hasChilds
					//$this->AssetIndex,
					//$level
					));
				//$this->AssetIndex++;
				//echo $locationId . "<br>\n";
				$count += $db->affected_rows;
				if($hasChilds)
				{
					$path2 = $path . "[@itemID='$itemId']/rowset[@name='contents']/row";
					$count += $this->ProcessAssetItems($xpath, $db, $path2, $locationId, $recordItemId, $level + 1);
				}
			}
			//echo $rowItem->getAttribute("itemID") . "\n";
			//echo $index . "\n";
			return $count;
		}

	    function UpdateStandings()//$accountId, $bar = null)
		{
			// чьи стенды: 1 - corporationStandings, 2 - allianceStandings
			// стенды "к" или "от": 1 - standingsTo, 2 - standingsFrom
			// обьект назначения: 1 - пилоты, 2 - корпорации, 3 - альянсы, 4 - агенты, 5 - нпц корпорации, 6 - фракции
			//$result = array();
			$params = array();
			$params["userID"] = $this->userId;
			$params["apiKey"] = $this->apiKey;
			$params["characterID"] = $this->characterId;
			$params["version"] = "2";
			$apires = $this->ApiRequest("/corp/Standings.xml.aspx", $params);
			$result = $apires;
			$result["message"] = "";

			$arTargets = array(
				"characters" => 1,
				"corporations" => 2,
				"alliances" => 3,
				"agents" => 4,
				"NPCCorporations" => 5,
				"factions" => 6);
			$arStandsFromTo = array(
				"standingsTo" => 1,
				"standingsFrom" => 2);
			$arStandsOf = array(
				"corporationStandings" => 1,
				"allianceStandings" => 2);

			if($apires["success"] == true)
			{
				if($apires["source"] == "server" || $this->updateFromCache)
				{
					$domPath = new DOMXPath($apires["response"]);
					//получение массива нод, подходящих под описание rowset, здесь их будет много разных
					$rows = $domPath->query("//row");


					$accountId = $this->accountId;
					$db = OpenDB2();
					$db->query(sprintf("delete from api_standings where accountId = '%s';", mysql_escape_string($accountId)));

					$statementStat = $db->prepare("insert into api_standings values (?, ?, ?, ?, ?, ?, ?, ?);");
					$statementStat->bind_param("ssdsdddd", $recordId, $accountId, $id, $name, $value, $standsOf, $standsFrom, $target);

					$recordAdded = 0;
					//перебираем все rowset'ы
					foreach($rows as $row)
					{
						$nodeRowset = $row->parentNode;
						$nodeStandsFromTo = $nodeRowset->parentNode;
						$nodeStandsOf = $nodeStandsFromTo->parentNode;

						$recordId = GetUniqueId();
						$columns = split(",", $nodeRowset->getAttribute("columns"));

						$id = $row->getAttribute($columns[0]);
						$name = $row->getAttribute($columns[1]);
						$value = $row->getAttribute($columns[2]);

						$standsOf = $arStandsOf[$nodeStandsOf->localName];
						$standsFrom = $arStandsFromTo[$nodeStandsFromTo->localName];
						$target = $arTargets[$nodeRowset->getAttribute("name")];

						$statementStat->execute();

						//echo $db->error;

						$recordAdded += $statementStat->affected_rows;
						//echo "$fromTo2 $fromTo $type $id $name $value<br>";
					}

					$statementStat->close();

					$db->close();

					$result["message"] = "Processed $recordAdded standings";
					
				}
			}
			else
			{
				//$result = "";//$apires[2];
			}
			return $result;
		}
	    function UpdateMemberSecurity()//$accountId, $bar = null)
		{
			$params = array();
			$params["userID"] = $this->userId;
			$params["apiKey"] = $this->apiKey;
			$params["characterID"] = $this->characterId;
			$params["version"] = "2";
			$apires = $this->ApiRequest("/corp/MemberSecurity.xml.aspx", $params);
			$result = $apires;
			$result["message"] = "";

			if($apires["success"] == true)
			{
				if($apires["source"] == "server" || $this->updateFromCache)
				{
					$domPath = new DOMXPath($apires["response"]);
					//получение массива нод, подходящих под описание rowset, здесь их будет много разных
					$path = "/eveapi/result/member";
					$rows = $domPath->query($path);


					$accountId = $this->accountId;
					$db = OpenDB2();
					$db->query(sprintf("delete from api_member_security where accountId = '%s';", mysql_escape_string($accountId)));
					$db->query(sprintf("delete from api_titles where accountId = '%s';", mysql_escape_string($accountId)));

					$statementMemSec = $db->prepare("insert into api_member_security values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);");
					$statementMemSec->bind_param("sdsddddddddd", $accountId, $characterId, $characterName, $roles, $grantableRoles, $rolesAtHQ, $grantableRolesAtHQ, $rolesAtBase, $grantableRolesAtBase, $rolesAtOther, $grantableRolesAtOther, $titles);

					$statementTitles = $db->prepare("insert ignore into api_titles values (?, ?, ?);");
					$statementTitles->bind_param("sds", $accountId, $titleId, $titleName);

					$recordAdded = 0;
					//перебираем все rowset'ы
					foreach($rows as $row)
					{
						$characterId = $row->getAttribute("characterID");
						$characterName = $row->getAttribute("name");
						//echo "$characterId $characterName<br>";

						$path = "/eveapi/result/member[@characterID = '$characterId']/rowset";
						$roles = $this->GetSecurityRowsetBits($domPath, $path, "roles");
						$grantableRoles = $this->GetSecurityRowsetBits($domPath, $path, "grantableRoles");
						$rolesAtHQ = $this->GetSecurityRowsetBits($domPath, $path, "rolesAtHQ");
						$grantableRolesAtHQ = $this->GetSecurityRowsetBits($domPath, $path, "grantableRolesAtHQ");
						$rolesAtBase = $this->GetSecurityRowsetBits($domPath, $path, "rolesAtBase");
						$grantableRolesAtBase = $this->GetSecurityRowsetBits($domPath, $path, "grantableRolesAtBase");
						$rolesAtOther = $this->GetSecurityRowsetBits($domPath, $path, "rolesAtOther");
						$grantableRolesAtOther = $this->GetSecurityRowsetBits($domPath, $path, "grantableRolesAtOther");
						$titles = $this->GetSecurityRowsetBits($domPath, $path, "titles");

						$statementMemSec->execute();
						//echo $db->error;

						$recordAdded++;
					}
					$statementMemSec->close();

					$rows = $domPath->query("//rowset[@name = 'titles']/row");
					foreach($rows as $row)
					{
						$titleId = $row->getAttribute("titleID");
						$titleName = base64_encode($row->getAttribute("titleName"));
						//echo "title: $titleId $titleName<br>";
						$statementTitles->execute();
					}
					$statementTitles->close();

					$db->close();

					$result["message"] = "Processed $recordAdded members";
					
				}
			}
			else
			{
				$result["message"] = "api request failed";//$apires[2];
			}
			return $result;
		}
		function GetSecurityRowsetBits($domPath, $path, $rowsetName)
		{
			//$path = "/eveapi/result/member[@characterID = '$characterId']/rowset";
			//$rowsets = $domPath->query($path);
			
			//$rowsetName = $rowset->getAttribute("name");
			$value = 0;
			$path2 = $path . "[@name = '$rowsetName']/row";
			$rows2 = $domPath->query($path2);
			foreach($rows2 as $row2)
			{
				if($row2->hasAttribute("roleID"))
					$roleId = $row2->getAttribute("roleID");
				if($row2->hasAttribute("titleID"))
					$roleId = $row2->getAttribute("titleID");
				$value += $roleId;
			}
			//echo "$rowsetName $value<br>";

			//$dataName = $rowsetName;
			//$dataValue = $value;
			//if($value > 0)
			//{
			//	$statementMemSec->execute();
			//	echo $db->error;
			//}
			return $value;
		}
			/*
			 * 1 roleDirector
			 * ...
			 * битовые маски корп. ролей: http://wiki.eve-id.net/Corporation_roles_bitmask
1 => corpRoleDirector,
128 => corpRolePersonnelManager,
256 => corpRoleAccountant,
512 => corpRoleSecurityOfficer,
1024 => corpRoleFactoryManager,
2048 => corpRoleStationManager,
4096 => corpRoleAuditor,
8192 => corpRoleHangarCanTake1,
16384 => corpRoleHangarCanTake2,
32768 => corpRoleHangarCanTake3,
65536 => corpRoleHangarCanTake4,
131072 => corpRoleHangarCanTake5,
262144 => corpRoleHangarCanTake6,
524288 => corpRoleHangarCanTake7,
1048576 => corpRoleHangarCanQuery1,
2097152 => corpRoleHangarCanQuery2,
4194304 => corpRoleHangarCanQuery3,
8388608 => corpRoleHangarCanQuery4,
16777216 => corpRoleHangarCanQuery5,
33554432 => corpRoleHangarCanQuery6,
67108864 => corpRoleHangarCanQuery7,
134217728 => corpRoleAccountCanTake1,
268435456 => corpRoleAccountCanTake2,
536870912 => corpRoleAccountCanTake3,
1073741824 => corpRoleAccountCanTake4,
2147483648 => corpRoleAccountCanTake5,
4294967296 => corpRoleAccountCanTake6,
8589934592 => corpRoleAccountCanTake7,
17179869184 => corpRoleAccountCanQuery1,
34359738368 => corpRoleAccountCanQuery2,
68719476736 => corpRoleAccountCanQuery3,
137438953472 => corpRoleAccountCanQuery4,
274877906944 => corpRoleAccountCanQuery5,
549755813888 => corpRoleAccountCanQuery6,
1099511627776 => corpRoleAccountCanQuery7,
2199023255552 => corpRoleEquipmentConfig,
4398046511104 => corpRoleContainerCanTake1,
8796093022208 => corpRoleContainerCanTake2,
17592186044416 => corpRoleContainerCanTake3,
35184372088832 => corpRoleContainerCanTake4,
70368744177664 => corpRoleContainerCanTake5,
140737488355328 => corpRoleContainerCanTake6,
281474976710656 => corpRoleContainerCanTake7,
562949953421312 => corpRoleCanRentOffice,
1125899906842624 => corpRoleCanRentFactorySlot,
2251799813685248 => corpRoleCanRentResearchSlot,
4503599627370496 => corpRoleJuniorAccountant,
9007199254740992 => corpRoleStarbaseConfig,
18014398509481984 => corpRoleTrader,
36028797018963968 => corpRoleChatManager,
72057594037927936 => corpRoleContractManager,
144115188075855872 => corpRoleInfrastructureTacticalOfficer,
288230376151711744  => corpRoleStarbaseCaretaker  
			 */
		
		function UpdateIndustryJobs()
		{
			$result = array();
			$params = array();
			$params["userID"] = $this->userId;
			$params["apiKey"] = $this->apiKey;
			$params["characterID"] = $this->characterId;
			$params["version"] = "2";
			$apires = $this->ApiRequest("/corp/IndustryJobs.xml.aspx", $params);
			$result = $apires;
			$result["message"] = "";
			if($apires["success"] == true && $apires["error"] == "")
			{
				if($apires["source"] == "server" || $this->updateFromCache)
				{
					//echo $apires[2]->SaveXML();
					//навигационный класс
					$domPath = new DOMXPath($apires["response"]);
					//получение массива нод, подходящих под описание
					$nodes = $domPath->query("/eveapi/result/rowset[@name='jobs']/row");
					
					$index = 0;
					//ExecuteQuery("LOCK TABLES `api_sovereignty` WRITE; delete * from api_sovereignty where accountId = '$accountId';");
					//$qr = ExecuteQuery("select count(*) as _count_ from api_account_balance where accountId = '$accountId';");
					//$row = mysql_fetch_array($qr);
					//$records_exists = $row["_count_"];
					//if($records_exists
					$query = "";
					$recordAdded = 0;

					//if($bar != null) $bar->initialize($nodes->length);

					$db = OpenDB2();
					$db->query("delete from api_industry_jobs where accountId = '$this->accountId';");
					echo $db->error;

					foreach($nodes as $node)
					{
						//вместо прежней проверки и вставки/обновления теперь делаем insert ignore и update
						$recordId = GetUniqueId();
						$now = date("Y-m-d H:i:s");

						$query = sprintf(
							"insert into api_industry_jobs values (".
							"'%s', '%s', ".//ид записи и аккаунта
							"%d, %d, %d, %d, %d, %d, %d, %d, %d, %d, %d, %d, %d, %d, %d, ".//11 целых
							"%d, %d, %d, %d, ".//4 дробных
							"%d, %d, %d, %d, %d, %d, %d, %d, %d, %d, ".//10 целых
							"'%s', '%s', '%s', '%s');",//4 даты
							//recordId 	accountId 	jobId 	assemblyLineId 	containerId 	installedItemId 	installedItemLocationId 	installedItemQuantity 	installedItemProductivityLevel 	installedItemMaterialLevel 	installedItemLicensedProductionRunsRemaining 	outputLocationId 	installerId 	runs 	licensedProductionRuns 	installedInSolarSystemId 	containerLocationId 	materialMultiplier 	charMaterialMultiplier 	timeMultiplier 	charTimeMultiplier 	installedItemTypeId 	outputTypeId 	containerTypeId 	installedItemCopy 	completed 	completedSuccessfully 	installedItemFlag 	outputFlag 	activityId 	completedStatus 	installTime 	beginProductionTime 	endProductionTime 	pauseProductionTime 
							$recordId, $this->accountId,
							$node->getAttribute("jobID"),
							$node->getAttribute("assemblyLineID"),
							$node->getAttribute("containerID"),
							$node->getAttribute("installedItemID"),
							$node->getAttribute("installedItemLocationID"),
							$node->getAttribute("installedItemQuantity"),
							$node->getAttribute("installedItemProductivityLevel"),
							$node->getAttribute("installedItemMaterialLevel"),
							$node->getAttribute("installedItemLicensedProductionRunsRemaining"),
							$node->getAttribute("outputLocationID"),
							$node->getAttribute("installerID"),
							$node->getAttribute("runs"),
							$node->getAttribute("licensedProductionRuns"),
							$node->getAttribute("installedInSolarSystemID"),
							$node->getAttribute("containerLocationID"),
							$node->getAttribute("materialMultiplier"),
							$node->getAttribute("charMaterialMultiplier"),
							$node->getAttribute("timeMultiplier"),
							$node->getAttribute("charTimeMultiplier"),
							$node->getAttribute("installedItemTypeId"),
							$node->getAttribute("outputTypeID"),
							$node->getAttribute("containerTypeID"),
							$node->getAttribute("installedItemCopy"),
							$node->getAttribute("completed"),
							$node->getAttribute("completedSuccessfully"),
							$node->getAttribute("installedItemFlag"),
							$node->getAttribute("outputFlag"),
							$node->getAttribute("activityId"),
							$node->getAttribute("completedStatus"),
							$node->getAttribute("installTime"),
							$node->getAttribute("beginProductionTime"),
							$node->getAttribute("endProductionTime"),
							$node->getAttribute("pauseProductionTime")
							);
						$db->query($query);
						$recordAdded++;
						//echo "$query<br><hr>";
						//echo $db->error;
					}
					//$old = date("Y-m-d H:i:s", strtotime("-3 second"));
					//$db->query("delete from api_member_tracking where accountId = '$this->accountId' and updated < '$old';");
					$db->close();
				}
				$result["message"] = "$recordAdded records";
			}
			return $result;
		}
		function GetCharactersList()
		{
			$result = array();
			$params = array();
			$params["userID"] = $this->userId;
			$params["apiKey"] = $this->apiKey;
			$params["version"] = "1";
			$apires = $this->ApiRequest("/account/Characters.xml.aspx", $params);
			$result = $apires;
			$result["message"] = "";
			$result["characters"] = array();
			if($apires["success"] == true && $apires["error"] == "")
			{
				if($apires["source"] == "server" || $this->updateFromCache)
				{
					//echo $apires[2]->SaveXML();
					//навигационный класс
					$domPath = new DOMXPath($apires["response"]);
					//получение массива нод, подходящих под описание
					$nodes = $domPath->query("/eveapi/result/rowset[@name='characters']/row");
					
					$index = 0;
					
					foreach($nodes as $node)
					{
						$charInfo = array(
							"characterId" => $node->getAttribute("characterID"),
							"characterName" => $node->getAttribute("name"),
							"corporationId" => $node->getAttribute("corporationID"),
							"corporationName" => $node->getAttribute("corporationName"));
						array_push($result["characters"], $charInfo);
					}
				}
			}
			return $result;
		}
		function UpdateMarketOrders()
		{
			$result = array();
			$params = array();
			$params["userID"] = $this->userId;
			$params["apiKey"] = $this->apiKey;
			$params["characterID"] = $this->characterId;
			$params["version"] = "2";
			$apires = $this->ApiRequest("/corp/MarketOrders.xml.aspx", $params);
			$result = $apires;
			$result["message"] = "";
			if($apires["success"] == true && $apires["error"] == "")
			{
				if($apires["source"] == "server" || $this->updateFromCache)
				{
					//echo $apires[2]->SaveXML();
					//навигационный класс
					$domPath = new DOMXPath($apires["response"]);
					//получение массива нод, подходящих под описание
					$nodes = $domPath->query("/eveapi/result/rowset[@name='orders']/row");
					
					$index = 0;
					//ExecuteQuery("LOCK TABLES `api_sovereignty` WRITE; delete * from api_sovereignty where accountId = '$accountId';");
					//$qr = ExecuteQuery("select count(*) as _count_ from api_account_balance where accountId = '$accountId';");
					//$row = mysql_fetch_array($qr);
					//$records_exists = $row["_count_"];
					//if($records_exists
					$query = "";
					$recordAdded = 0;

					//if($bar != null) $bar->initialize($nodes->length);

					$db = OpenDB2();
					$db->query("delete from api_market_orders where accountId = '$this->accountId';");
					echo $db->error;

					foreach($nodes as $node)
					{
						$recordId = GetUniqueId();
						$now = date("Y-m-d H:i:s");

						$query = sprintf(
							"insert into api_market_orders values (".
							"'%s', '%s', ".//ид записи и аккаунта
							"%d, %d, %d, %d, %d, %d, ".//6 целых
							"%d, ".//1 байт
							"%d, %d, %d, %d, ".//4 int
							"%d, %d, ".//2 decimal
							"%d, ".//1 byte
							"'%s');",//1 дата
							$recordId, $this->accountId,
							$node->getAttribute("orderID"),
							$node->getAttribute("charID"),
							$node->getAttribute("stationID"),
							$node->getAttribute("volEntered"),
							$node->getAttribute("volRemaining"),
							$node->getAttribute("minVolume"),
							$node->getAttribute("orderState"),
							$node->getAttribute("typeID"),
							$node->getAttribute("range"),
							$node->getAttribute("accountKey"),
							$node->getAttribute("duration"),
							$node->getAttribute("escrow"),
							$node->getAttribute("price"),
							$node->getAttribute("bid"),
							$node->getAttribute("issued")
							);
						$db->query($query);
						$recordAdded++;
						//echo "$query<br><hr>";
						//echo $db->error;
					}
					//$old = date("Y-m-d H:i:s", strtotime("-3 second"));
					//$db->query("delete from api_member_tracking where accountId = '$this->accountId' and updated < '$old';");
					$db->close();
				}
				$result["message"] = "$recordAdded records";
			}
			return $result;
		}
		function UpdateStarbaseList()
		{
			include_once "starbase.php";
			$result = array();
			$params = array();
			$params["userID"] = $this->userId;
			$params["apiKey"] = $this->apiKey;
			$params["characterID"] = $this->characterId;
			$params["version"] = "2";
			$apires = $this->ApiRequest("/corp/StarbaseList.xml.aspx", $params);
			$result = $apires;
			$result["message"] = "";
			if($apires["success"] == true && $apires["error"] == "")
			{
				if($apires["source"] == "server" || $this->updateFromCache)
				{
					//echo $apires[2]->SaveXML();
					//навигационный класс
					$domPath = new DOMXPath($apires["response"]);
					//получение массива нод, подходящих под описание
					$nodes = $domPath->query("/eveapi/result/rowset[@name='starbases']/row");
					
					$index = 0;
					//ExecuteQuery("LOCK TABLES `api_sovereignty` WRITE; delete * from api_sovereignty where accountId = '$accountId';");
					//$qr = ExecuteQuery("select count(*) as _count_ from api_account_balance where accountId = '$accountId';");
					//$row = mysql_fetch_array($qr);
					//$records_exists = $row["_count_"];
					//if($records_exists
					$query = "";
					$recordAdded = 0;

					//if($bar != null) $bar->initialize($nodes->length);

					$db = OpenDB2();
					//$db->query("delete from api_market_orders where accountId = '$this->accountId';");
					//echo $db->error;

					$db->query("update api_starbases set updateFlag = 0 where accountId = '{$this->accountId}';");
					foreach($nodes as $node)
					{
						$itemId = $node->getAttribute("itemID");
					
						$params["itemID"] = $itemId;
						$detailsRes = $this->ApiRequest("/corp/StarbaseDetail.xml.aspx", $params);
						if($detailsRes["success"] == true && $detailsRes["error"] == "")
						{
							if($detailsRes["source"] == "server" || $this->updateFromCache)
							{
								$details = $detailsRes["response"]->saveXML();
								$recordId = GetUniqueId();
								$now = date("Y-m-d H:i:s");

								$query = sprintf(
									"replace into api_starbases values (".
									"'%s', '%s', ".//id записи и аккаунта
									"%d, %d, %d, %d, %d, ".//itemId, typeId, locationId, moonId, state
									"'%s', '%s', " .//stateTimestamp, onlineTimestamp
									"'%s', '0000-00-00 00:00:00', 1);",
									$recordId, $this->accountId,
									$node->getAttribute("itemID"),
									$node->getAttribute("typeID"),
									$node->getAttribute("locationID"),
									$node->getAttribute("moonID"),
									$node->getAttribute("state"),
									$node->getAttribute("stateTimestamp"),
									$node->getAttribute("onlineTimestamp"),
									mysql_escape_string($details)
									);
								$db->query($query);
								//чтение из базы для заполнения класса Starbase
								$starbase = new Starbase($this->accountId, $node->getAttribute("itemID"));
								$starbase->UpdateFuel();
								$a = $starbase->GetTowerSettings();
								$calcRes = $starbase->calcFuelEndTime();
								$endTimestamp = $calcRes["strEndTime"];
								//echo $endTimestamp;

								$domPath2 = new DOMXPath($detailsRes["response"]);
								$nodeStateTimestamp = $domPath2->query("/eveapi/result/stateTimestamp");
								$nodeStateTimestamp = $nodeStateTimestamp->item(0);
								$stateTimestamp = $nodeStateTimestamp->nodeValue;

								$query = "update api_starbases set endTimestamp = '$endTimestamp', stateTimeStamp = '$stateTimestamp' where recordId = '$recordId';";
								$db->query($query);
								$recordAdded++;
							}
						//echo "$query<br><hr>";
						//echo $db->error;
						}
					}
					//удаление из базы посов, отсутствующих в новом списке
					$db->query("delete from api_starbases where accountId = '{$this->accountId}' and updateFlag = 0;");
					//$old = date("Y-m-d H:i:s", strtotime("-3 second"));
					//$db->query("delete from api_member_tracking where accountId = '$this->accountId' and updated < '$old';");
					$db->close();
				}
				$result["message"] = "$recordAdded records";
			}
			return $result;
		}
   	}
?>
