<?php
	include_once "user.php";
	session_start();
	require_once "../lib/JsHttpRequest/JsHttpRequest.php";
	// Init JsHttpRequest and specify the encoding. It's important!
	$JsHttpRequest =& new JsHttpRequest("utf-8");
	// Fetch request parameters.
	if(isset($_REQUEST["function"]))
	{
		$function = $_REQUEST["function"];
		$recordId = null;
		//echo "err";
		if($function != "locations_by_name")
		{
			if(isset($_REQUEST["recordId"]))
			{
				$recordId = $_REQUEST["recordId"];
				//не рассматривать запросы для ошибочных id
				if(preg_match("/^[\w\.]+$/", $recordId) != 1)
					$function = null;
			}
		}

		if(isset($_SESSION["User"]))
		{
			$User = $_SESSION["User"];
			//echo "<pre>";
			//print_r($User->parameters);
			//echo "</pre>";

			$accountId = $User->GetAccountId();
			if($function == "edit")
			{
				$min = $_REQUEST["min"];
				$norm = $_REQUEST["norm"];

				if(preg_match("/^[\d]{1,9}$/", $min) == 1 && preg_match("/^[\d]{1,9}$/", $norm) == 1)
				{

					$db = OpenDB2();
					$db->query(sprintf(
						"update ignore api_assets_monitor set quantityMinimum = %d, quantityNormal = %d where recordId = '%s' and accountId = '%s';",
						$db->real_escape_string($min),
						$db->real_escape_string($norm),
						$db->real_escape_string($recordId),
						$db->real_escape_string($accountId)
						));
					if($qr = $db->query(sprintf(
						"select *from  api_assets_monitor where recordId = '%s' and accountId = '%s';",
						$db->real_escape_string($recordId),
						$db->real_escape_string($accountId)
						)))
					{
						$row = $qr->fetch_assoc();
						$min = $row["quantityMinimum"];
						$norm = $row["quantityNormal"];
						$qr->close();
					}
					$db->close();
					$GLOBALS['_RESULT'] = array(
						"recordId" => $_REQUEST["recordId"],
						"min"   => $min,
						"norm"  => $norm
						//"ssid"   => session_id()
					);
				}
			}
			if($function == "delete")
			{

				$db = OpenDB2();
				$db->query(sprintf(
					"delete from api_assets_monitor where recordId = '%s' and accountId = '%s';",
					$db->real_escape_string($recordId),
					$db->real_escape_string($accountId)
					));
				$affected_rows = $db->affected_rows;
				$db->close();

				$GLOBALS['_RESULT'] = array(
					"recordId" => $_REQUEST["recordId"],
					"affected_rows"   => $affected_rows
					//"ssid"   => session_id()
				);
			}
			if($function == "add")
			{
				$min = $_REQUEST["min"];
				$norm = $_REQUEST["norm"];

				if(preg_match("/^[\d]{1,9}$/", $min) == 1 && preg_match("/^[\d]{1,9}$/", $norm) == 1)
				{

					$db = OpenDB2();

					$affected_rows = 0;
					if($qr = $db->query(sprintf(
						"select * from api_assets where recordId = '%s' and accountId = '%s';",
						$db->real_escape_string($recordId),
						$db->real_escape_string($accountId)
						)))
					{
						if($row = $qr->fetch_assoc())
						{
							//print_r($row);
							$typeId = $row["typeId"];
							$locationId = $row["locationId"];
							
							$query = sprintf(
								"insert ignore into api_assets_monitor set recordId = '%s', accountId = '%s', locationId = %d, typeId = %d, quantityMinimum = %d, quantityNormal = %d;",
								GetUniqueId(),
								$db->real_escape_string($accountId),
								$db->real_escape_string($locationId),
								$db->real_escape_string($typeId),
								$db->real_escape_string($min),
								$db->real_escape_string($norm));
							$db->query($query);
							$affected_rows = $db->affected_rows;
							//echo $query . "<br>affected: " . $affected_rows;
							if($affected_rows == 0)
							{
								$query = sprintf(
									"update ignore api_assets_monitor set quantityMinimum = %d, quantityNormal = %d where accountId = '%s' and locationId = %d and typeId = %d;",
									$db->real_escape_string($min),
									$db->real_escape_string($norm),
									$db->real_escape_string($accountId),
									$db->real_escape_string($locationId),
									$db->real_escape_string($typeId));
								$db->query($query);
								$affected_rows = $db->affected_rows;
								//echo $query . "<br>affected: " . $affected_rows;
							}
						}
						$qr->close();
					}

					$db->close();
					$GLOBALS['_RESULT'] = array(
						"recordId" => $_REQUEST["recordId"],
						"affected_rows"   => $affected_rows
					);
				}
			}
			if($function == "get")
			{
				$min = 10;
				$norm = 100;

				$db = OpenDB2();

				$affected_rows = 0;
				if($qr = $db->query(sprintf(
					"select * from api_assets where recordId = '%s' and accountId = '%s';",
					$db->real_escape_string($recordId),
					$db->real_escape_string($accountId)
					)))
				{
					if($row = $qr->fetch_assoc())
					{
						//print_r($row);
						$typeId = $row["typeId"];
						$locationId = $row["locationId"];

						$query = sprintf(
							"select * from api_assets_monitor where accountId = '%s' and locationId = %d and typeId = %d;",
							$db->real_escape_string($accountId),
							$db->real_escape_string($locationId),
							$db->real_escape_string($typeId));
						if($qr2 = $db->query($query))
						{
							//print_r($qr2);
							if($row2 = $qr2->fetch_assoc())
							{
								$min = $row2["quantityMinimum"];
								$norm = $row2["quantityNormal"];
								//echo $min . ", " . $norm;
							}
							$qr2->close();
						}
					}
					$qr->close();
				}


				$db->close();
				$GLOBALS['_RESULT'] = array(
					"recordId" => $_REQUEST["recordId"],
					"min"   => $min,
					"norm"  => $norm
				);
			}
			$limit = 10;
			if($function == "locations_by_name")
			{
				$location = $_REQUEST["location"];

				$bold_pattern = "/(^.*)($location)(.*$)/i";
				$bold_replacement = "\${1}<b>\${2}</b>\${3}";

				$db = OpenDB2();
				$locations = "<p><b>Станции";

				$query = sprintf("select count(*) as _count_ from staStations where stationName like '%%%s%%' order by stationName;", $db->real_escape_string($location));
				$qr = $db->query($query);
				$row = $qr->fetch_assoc();
				$count = $row["_count_"];
				$qr->close();

				$query = sprintf("select * from staStations where stationName like '%%%s%%' order by stationName limit $limit;", $db->real_escape_string($location));
				//echo $query;
				if($qr = $db->query($query))
				{
					if($count > $limit)
						$locations .= " (показаны $limit из {$count})</b>:<br>";
					else if($qr->num_rows > 0)
						$locations .= " ({$qr->num_rows})</b>:<br>";
					else
						$locations .= ": не найдены</b>";
					$index = 0;
					while($row = $qr->fetch_assoc())
					{
						$stationID = $row["stationID"];
						$stationName = $row["stationName"];
						$stationName2 = str_replace("'", "\'", $stationName);
						$stationName = preg_replace($bold_pattern, $bold_replacement, $stationName);

						$locations .= "$stationID <a href='#' onclick=\"applyLocation('$stationName2', 'st'); return false;\">$stationName</a><br>";
						if($index++ > $limit)
							break;
					}
					$qr->close();
				}
				$locations .= "</p>";

				$locations .= "<p><b>Аутпосты";

				$query = sprintf("select count(*) as _count_ from api_outposts where stationName like '%%%s%%' order by stationName;", $db->real_escape_string($location));
				$qr = $db->query($query);
				$row = $qr->fetch_assoc();
				$count = $row["_count_"];
				$qr->close();

				$query = sprintf("select * from api_outposts where stationName like '%%%s%%' order by stationName limit $limit;", $db->real_escape_string($location));
				//echo $query;
				if($qr = $db->query($query))
				{
					if($count > $limit)
						$locations .= " (показаны $limit из {$count})</b>:<br>";
					else if($qr->num_rows > 0)
						$locations .= " ({$qr->num_rows})</b>:<br>";
					else
						$locations .= ": не найдены</b>";
					$index = 0;
					while($row = $qr->fetch_assoc())
					{
						$stationID = $row["stationId"];
						$stationName = $row["stationName"];
						$stationName2 = str_replace("'", "\'", $stationName);
						$stationName = preg_replace($bold_pattern, $bold_replacement, $stationName);

						$locations .= "$stationID <a href='#' onclick=\"applyLocation('$stationName2', 'ou'); return false;\">$stationName</a><br>";
						if($index++ > $limit)
							break;
					}
					$qr->close();
				}
				$locations .= "</p>";

				$locations .= "<p><b>mapDenormalize";

				$query = sprintf("select count(*) as _count_ from mapDenormalize where itemName like '%%%s%%' order by itemName;", $db->real_escape_string($location));
				$qr = $db->query($query);
				$row = $qr->fetch_assoc();
				$count = $row["_count_"];
				$qr->close();

				$query = sprintf("select * from mapDenormalize where itemName like '%%%s%%' order by itemName limit $limit;", $db->real_escape_string($location));
				//echo $query;
				if($qr = $db->query($query))
				{
					if($count > $limit)
						$locations .= " (показаны $limit из {$count})</b>:<br>";
					else if($qr->num_rows > 0)
						$locations .= " ({$qr->num_rows})</b>:<br>";
					else
						$locations .= ": не найдены</b>";
					$index = 0;
					while($row = $qr->fetch_assoc())
					{
						$itemId = $row["itemID"];
						$itemName = $row["itemName"];
						$itemName2 = str_replace("'", "\'", $itemName);
						$itemName = preg_replace($bold_pattern, $bold_replacement, $itemName);

						//$locations .= "$stationName<br>";
						$locations .= "$itemId <a href='#' onclick=\"applyLocation('$itemName2', 'md'); return false;\">$itemName</a><br>";
						if($index++ > $limit)
							break;
					}
					$qr->close();
				}
				$locations .= "</p>";

				$db->close();
				$GLOBALS['_RESULT'] = array(
					"locations"   => $locations
				);
				//echo $locations;
			}
			if($function == "items_by_name")
			{
				$item = $_REQUEST["item"];

				$bold_pattern = "/(^.*)($item)(.*$)/i";
				$bold_replacement = "\${1}<b>\${2}</b>\${3}";

				$db = OpenDB2();
				$items = "<p><b>Вещи";

				$query = sprintf("select count(*) as _count_ from invTypes where typeName like '%%%s%%' order by typeName;", $db->real_escape_string($item));
				$qr = $db->query($query);
				$row = $qr->fetch_assoc();
				$count = $row["_count_"];
				$qr->close();

				$query = sprintf("select * from invTypes where typeName like '%%%s%%' order by typeName limit $limit;", $db->real_escape_string($item));
				//echo $query;
				if($qr = $db->query($query))
				{
					if($count > $limit)
						$items .= " (показаны $limit из {$count})</b>:<br>";
					else if($qr->num_rows > 0)
						$items .= " ({$qr->num_rows})</b>:<br>";
					else
						$items .= ": не найдены</b>";

					$index = 0;
					while($row = $qr->fetch_assoc())
					{
						$typeId = $row["typeID"];
						$typeName = $row["typeName"];
						$typeName2 = str_replace("'", "\'", $typeName);
						$typeName = preg_replace($bold_pattern, $bold_replacement, $typeName);

						$items .= "$typeId <a href='#' onclick=\"applyItem('$typeName2'); return false;\">$typeName</a><br>";
						if($index++ > $limit)
							break;
					}
					$qr->close();
				}
				$items .= "</p>";

				$db->close();
				$GLOBALS['_RESULT'] = array(
					"items"   => $items
				);
				//echo $items;
			}
		}
		else
		{
			echo "Неверный формат вызова";
		}
	}

	// Everything we print will go to 'errors' parameter.
	//echo "<pre>";
	//print_r($_SESSION);
	//echo "</pre>";
	// This includes a PHP fatal error! It will go to the debug stream,
	// frontend may intercept this and act a reaction.
	if(isset($_REQUEST['str']))
	{
		if ($_REQUEST['str'] == 'error')
		{
			error_demonstration__make_a_mistake_calling_undefined_function();
		}
	}
?>
