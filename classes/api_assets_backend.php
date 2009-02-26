<?php
	include_once "user.php";
	session_start();
	require_once "../lib/JsHttpRequest/JsHttpRequest.php";
	// Init JsHttpRequest and specify the encoding. It's important!
	$JsHttpRequest =& new JsHttpRequest("utf-8");
	// Fetch request parameters.
	if(isset($_REQUEST["function"]) && isset($_REQUEST["recordId"]))
	{
		$function = $_REQUEST["function"];
		$recordId = $_REQUEST["recordId"];
		
		//не рассматривать запросы для ошибочных id
		if(preg_match("/^\w+$/", $recordId) != 1)
			$function = null;

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
