<?php
    include_once("api2.php");
    include_once("database.php");
    include_once "pageselector.php";

	class Api_Assets
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

			$accountId = $User->GetAccountId();
			$this->request_processor = $_SERVER["PHP_SELF"] . "?mode=" . get_class($this);

			$locationId = null;
			$viewmonitor = null;
			$newmonitor = null;

			//локация, если параметр передан, но не является числом, считаем что он не передан
			if(isset($_REQUEST["locationId"]))
			{
				$locationId = $_REQUEST["locationId"];
				if(preg_match("/^[\d]{1,10}$/", $locationId) != 1)
					$locationId = null;
			}

			//просмотр списка слежения
			if(isset($_REQUEST["viewmonitor"]))
				$viewmonitor = true;

			//добавление нового слежения
			if(isset($_REQUEST["newmonitor"]))
				$newmonitor = true;


			//вывод элемента постраничного просмотра

			$db = OpenDB2();

			/*$qr = $dblink->query("select count(*) as _count_ from api_assets;");
			$row = $qr->fetch_assoc();
			$recordsCount = $row["_count_"];
			$qr->close();

			$pages = new PageSelector();
			$page->Body = $pages->Write($recordsCount);*/

//дерево с ветками http://www.artlebedev.ru/tools/technogrette/html/treeview/#

			if(isset($_POST["submit_new_monitor"]))
			{
				// loctype locname itemname monmin monnorm
				$loctype  = $_POST["loctype"];
				$locname  = $_POST["locname"];
				$itemname = $_POST["itemname"];
				$monmin   = $_POST["monmin"];
				$monnorm   = $_POST["monnorm"];
				if(preg_match("/^\d+$/", $monmin) != 1)
					$monmin = 10;
				if(preg_match("/^\d+$/", $monnorm) != 1)
					$monnorm = 10;

				$locId = 0;
				//echo $loctype;
				if($loctype == "staStations")
				{
					$query = sprintf("select stationID from staStations where stationName = '%s' limit 1;", $db->real_escape_string($locname));
					$qr = $db->query($query);
					if($row = $qr->fetch_assoc())
					{
						$locId = $row["stationID"] + 6000001;
					}
					$qr->close();
				}
				if($loctype == "api_outposts")
				{
					$query = sprintf("select stationId from api_outposts where stationName = '%s' limit 1;", $db->real_escape_string($locname));
					$qr = $db->query($query);
					if($row = $qr->fetch_assoc())
					{
						$locId = $row["stationId"] + 6000000;
					}
					$qr->close();
				}
				if($loctype == "mapDenormalize")
				{
					$query = sprintf("select itemID from mapDenormalize where itemName = '%s' limit 1;", $db->real_escape_string($locname));
					$qr = $db->query($query);
					if($row = $qr->fetch_assoc())
					{
						$locId = $row["itemID"];
					}
					$qr->close();
				}
				
				$typeId = 0;
				$itemname = str_replace("\'", "'", $itemname);
				$query = sprintf("select typeID from invTypes where typeName = '%s' limit 1;", $db->real_escape_string($itemname));
				$qr = $db->query($query);
				if($row = $qr->fetch_assoc())
				{
					$typeId = $row["typeID"];
				}
				$qr->close();
				
				//echo "<pre>$itemname</pre> $locId $typeId";
				if($locId != 0 && $typeId != 0)
				{
					$query = sprintf("insert ignore into api_assets_monitor set recordId = '%s', accountId = '%s', locationId = %d, typeId = %d, quantityMinimum = %d, quantityNormal = %d;",
						GetUniqueId(),
						$db->real_escape_string($accountId),
						$db->real_escape_string($locId),
						$db->real_escape_string($typeId),
						$db->real_escape_string($monmin),
						$db->real_escape_string($monnorm));
					//echo $query;
					$db->query($query);
					$viewmonitor = true;
				}
			}

			if($viewmonitor == null)
			{
				$page->Body = "<a href='{$this->request_processor}&amp;viewmonitor'>Просмотр таблицы слежения за запасами</a><br>";
			}
			else
			{
				$page->Body = "";
			}
			$page->Body .= "<a href='{$this->request_processor}&amp;newmonitor'>Добавление нового объекта слежения</a><br>";

			// добавление нового монитора
			if($newmonitor != null)
			{
				$this->ShowNewMonitor($page);
			}
			// просмотр перечня локаций ********************************
			//если никакой подрежим не выбран
			if($locationId == null && $viewmonitor == null && $newmonitor == null)
			{
				$this->ShowLocationsList($page, $accountId);
			}
			// просмотр содержимого локации ****************************
			if($locationId != null && $newmonitor == null)
			{
				$this->ShowItemsInLocation($page, $accountId, $locationId);
			}
			// просмотр списка мониторинга *****************************
			else if($viewmonitor != null && $newmonitor == null)
			{
				$this->ShowMonitoringList($page, $accountId);
			}
			//$this->ProcessSubscribe($db, $accountId, $page);
			$db->close();
		}
		function ShowChilds($db, $page, $parentId, $accountId, $sorter, $level)
		{
			$query = 
"select api_assets.*, invTypes.typeName, invFlags.flagText
from api_assets
left join invTypes on invTypes.typeID = api_assets.typeId
left join invFlags on invFlags.flagID = api_assets.flag
where api_assets.parentId = '$parentId' and api_assets.accountId = '$accountId'
$sorter;";
			//$qr = ExecuteQuery($query);
			//echo $query;
			$qr = $db->query($query);

			//$rowIndex = $pages->start;
			$rowClass = "even";
			while($row = $qr->fetch_assoc())
			{
				if(($this->rowIndex % 2) == 1)
					$rowClass = "b-row-even";
				else
					$rowClass = "b-row-odd";
				$this->rowIndex++;

				$hasChilds = $row["hasChilds"];

				if($hasChilds)
					$childsColor = "bgcolor='green'";
				else
					$childsColor = "";

				$indent = 20 * $level;// $row["level"];
				$page->Body .= "
						<tr class='$rowClass'>\n
							<td $childsColor>{$this->rowIndex}</td>\n
							<td style='text-indent: {$indent}px;'>$row[typeName]</td>
							<td class='b-right'>$row[quantity]</td>
							<td>$row[flagText]</td>
							<td>$row[singleton]</td>
							<td class='b-center' id='op_$row[recordId]'><a href='' onclick=\"itemsShowAdd('$row[recordId]'); return false;\"><img src='images/b_insrow.png'></a></td>
						</tr>\n";
//						<td class='b-center'><a href='{$this->request_processor}&locationId=$row[locationId]&addmonitor=$row[recordId]'><img src='images/b_insrow.png'></a></td>
				if($hasChilds)
				{
					$recordId = $row["recordId"];
					$this->ShowChilds($db, $page, $recordId, $accountId, $sorter, $level + 1);
				}
			}
		}

		function ShowNewMonitor($page)
		{
			$page->Body .= "
</style>
<script src=\"lib/JsHttpRequest/JsHttpRequest.js\"></script>
<script language='JavaScript'>
	function locationAutoSuggest()
	{
		loc = document.getElementById('locname').value;
		if(loc.length < 3)
		{
			document.getElementById('locations_list').innerHTML = \"\";
		}
		else
		{
			//alert(loc);
			JsHttpRequest.query(
				'classes/api_assets_backend.php',
				{
					'function' : 'locations_by_name',
					'location': loc
				},
				// Function is called when an answer arrives. 
				function(result, errors)
				{
					//alert(errors);
					// Write errors to the debug div.
					document.getElementById('debug').innerHTML = errors; 
					// Write the answer.
					if (result)
					{
						locations = result[\"locations\"];
						//alert(locations);
						document.getElementById('locations_list').innerHTML = locations;
					}
				},
				true  // disable caching
			);
			document.getElementById('locations_list').innerHTML = '<p>Идёт загрузка, подождите пожалуйста...</p>';
		}
	}
	function itemAutoSuggest()
	{
		item = document.getElementById('itemname').value;
		if(item.length < 3)
		{
			document.getElementById('items_list').innerHTML = \"\";
		}
		else
		{
			//alert(item);
			JsHttpRequest.query(
				'classes/api_assets_backend.php',
				{
					'function' : 'items_by_name',
					'item': item
				},
				// Function is called when an answer arrives. 
				function(result, errors)
				{
					//alert(errors);
					// Write errors to the debug div.
					document.getElementById('debug').innerHTML = errors; 
					// Write the answer.
					if (result)
					{
						items = result[\"items\"];
						//alert(items);
						document.getElementById('items_list').innerHTML = items;
					}
				},
				true  // disable caching
			);
			document.getElementById('items_list').innerHTML = '<p>Идёт загрузка, подождите пожалуйста...</p>';
		}
	}
	function clearSuggestList()
	{
		//alert('clear');
		document.getElementById('locations_list').innerHTML = \"\";
		document.getElementById('items_list').innerHTML = \"\";
	}
	function applyLocation(locationName, locType)
	{
		for (i=0;i<document.form_new_item.loctype.length;i++)
		{
			//alert (document.form_new_item.loctype[i].id);
			if (document.form_new_item.loctype[i].id == locType)
				document.form_new_item.loctype[i].checked = true;
			else
				document.form_new_item.loctype[i].checked = false;
		}
		clearSuggestList();

		//alert(document.form_new_item.loctype.length);
		document.getElementById('locname').value = locationName;
	}
	function applyItem(itemName)
	{
		clearSuggestList();
		document.getElementById('itemname').value = itemName;
	}
</script>
<p>
<form action='$this->request_processor' method='post' name='form_new_item'>
	<input type='radio' id='st' name='loctype' value='staStations' checked>Станция&nbsp;
	<input type='radio' id='ou' name='loctype' value='api_outposts'>Аутпост&nbsp;
	<input type='radio' id='md' name='loctype' value='mapDenormalize'>mapDenormalize (Deliveries?)<br>
	<label for='locname'>Локация:</label><br>
	<input id='locname' name='locname' type='text' onKeyUp=\"locationAutoSuggest();\" onClick=\"clearSuggestList();\" size='100'><br>
	<div id='locations_list' class='search_suggest'></div>
	<label for='itemname'>Тип вещи:</label><br>
	<input id='itemname' name='itemname' type='text' onKeyUp=\"itemAutoSuggest();\" onClick=\"clearSuggestList();\" size='100'><br>
	<div id='items_list' class='search_suggest'></div>
	<label for='monmin'>Минимальное количество:</label><br>
	<input id='monmin' name='monmin' type='text' value='10'><br>
	<label for='monnorm'>Нормальное количество:</label><br>
	<input id='monnorm' name='monnorm' type='text' value='100'><br>
	<input type='submit' name='submit_new_monitor' value='Добавить'>
</form>
<br>
<div id='debug'></div>
</p>
			";
//<div id='locations_list' class='search_suggest'></div>
//<div id='items_list' class='search_suggest'></div>
		}

		function ShowLocationsList($page, $accountId)
		{
			$page->Body .= "
				<table class='b-border b-widthfull'>\n
					<tr class='b-table-caption'>\n
						<td>#</td>\n";
			$page->Body .= $page->WriteSorter(array (
				"solarSystemId" => "Solar System  Id",
				"locationId" => "Location Id",
				"locationName" => "Локация"));
			$page->Body .= "
					</tr>\n";

			$sorter = $page->GetSorter("locationName");

			$query = "SELECT CASE
WHEN a.locationid BETWEEN 66000000 AND 66015131 THEN (
	SELECT s.stationName
	FROM staStations AS s
	WHERE s.stationID = a.locationid - 6000001
)
WHEN a.locationid BETWEEN 66015132 AND 67999999 THEN (
	SELECT c.stationname
	FROM api_outposts AS c
	WHERE c.stationid = a.locationid - 6000000
)
ELSE (
	SELECT m.itemName
	FROM mapDenormalize AS m
	WHERE m.itemID = a.locationid
)
END AS locationName, 
case
when a.locationid between 66000000 and 66015131 then (
	select ss.solarSystemID from staStations as ss
	where ss.stationID = a.locationid -6000001
)
when a.locationid between 66015132 and 67999999 then (
	select cc.solarSystemID from api_outposts as cc
	where cc.stationid = a.locationid -6000000
)
else (
	select mm.solarSystemID from mapDenormalize as mm
	where mm.itemID = a.locationid
)
end as solarSystemID, 
a.locationId
FROM api_assets AS a
where a.accountId = '$accountId'
group by a.locationId
$sorter ;";//limit $pages->start, $pages->count
			//echo $query;

			$db = OpenDB2();
			//$qr = ExecuteQuery($query);
			$qr = $db->query($query);

			$rowIndex = 0;//$pages->start;
			$rowClass = "even";
			while($row = $qr->fetch_assoc())
			{
				if(($rowIndex % 2) == 1)
					$rowClass = "b-row-even";
				else
					$rowClass = "b-row-odd";
				$rowIndex++;

				if($row["locationName"] == "")
					$row["locationName"] = "name empty, id = $row[locationId]";
				$page->Body .= "
						<tr class='$rowClass'>\n
							<td class='b-center'>$rowIndex</td>\n
							<td class='b-center'>$row[solarSystemID]</td>\n
							<td class='b-center'>$row[locationId]</td>\n
							<td><a href='{$this->request_processor}&locationId=$row[locationId]'>$row[locationName]</a></td>\n
						</tr>\n";
			}
			$db->close();

			$page->Body .= "
				</table>\n
			";
		}

		function ShowItemsInLocation($page, $accountId, $locationId)
		{
			$page->Body .= "
<script src=\"lib/JsHttpRequest/JsHttpRequest.js\"></script>
<script language='JavaScript'>
	function itemsShowAdd(recordId)
	{
        JsHttpRequest.query(
            'classes/api_assets_backend.php',
            {
            	'function' : 'get',
                'recordId': recordId
            },
            // Function is called when an answer arrives. 
            function(result, errors)
            {
            	//alert('1');
                // Write errors to the debug div.
                document.getElementById('debug').innerHTML = errors; 
                // Write the answer.
                if (result)
                {
                	recordId = result[\"recordId\"];
                	//alert(recordId);

					edit_min_id = \"'edit_min_\" + recordId + \"'\";
					edit_norm_id = \"'edit_norm_\" + recordId + \"'\";
					button_submit_click = \"onclick=itemAddSubmit('\" + recordId + \"')\";
					button_cancel_click = \"onclick=itemAddCancel('\" + recordId + \"')\";

					edit_min = '<input type=\'text\' id=' + edit_min_id + ' value=\'' + result[\"min\"] + '\' size=\'6\'>';
					edit_norm = '<input type=\'text\' id=' + edit_norm_id + ' value=\'' + result[\"norm\"] + '\' size=\'6\'>';
					button_submit = '<button ' + button_submit_click + '><img src=\'images/s_success.png\'></button>';
					button_cancel = '<button ' + button_cancel_click + '><img src=\'images/b_drop.png\'></button>';

					//alert(button_cancel);

					document.getElementById('op_' + recordId).innerHTML = edit_min + edit_norm + button_submit + button_cancel;
                }
            },
            true  // disable caching
        );
	}
	function itemAddSubmit(recordId)
	{
		edit_min_id = 'edit_min_' + recordId;
		edit_norm_id = 'edit_norm_' + recordId;
		min = document.getElementById(edit_min_id).value;
		norm = document.getElementById(edit_norm_id).value;
		//alert(norm);
        JsHttpRequest.query(
            'classes/api_assets_backend.php',
            {
            	'function' : 'add',
                'recordId': recordId,
                'min': min,
                'norm' : norm
            },
            // Function is called when an answer arrives. 
            function(result, errors)
            {
            	//alert('1');
                // Write errors to the debug div.
                document.getElementById('debug').innerHTML = errors; 
                // Write the answer.
                if (result)
                {
					buttons = '<a href=\'\' onclick=\"itemsShowAdd(\'' + recordId + '\'); return false;\"><img src=\'images/b_insrow.png\'></a>';
					//alert(buttons);
					document.getElementById('op_' + recordId).innerHTML = buttons;
                }
            },
            true  // disable caching
        );
	}
	function itemAddCancel(recordId)
	{
		edit_min_id = \"'edit_min_\" + recordId + \"'\";
		edit_norm_id = \"'edit_norm_\" + recordId + \"'\";
		//alert(edit_min_id);
		buttons = '<a href=\'\' onclick=\"itemsShowAdd(\'' + recordId + '\'); return false;\"><img src=\'images/b_insrow.png\'></a>';
		//alert(buttons);
		document.getElementById('op_' + recordId).innerHTML = buttons;
	}
</script>
<div id='debug'></div>
";
			$page->Body .= "
				<table class='b-border b-widthfull'>\n
					<tr class='b-table-caption'>\n
						<td>#</td>\n";
			$page->Body .= $page->WriteSorter(array (
				"typeName" => "Тип",
				"quantity" => "Количество",
				"flagText" => "Место хранения",
				"singleton" => "Singleton"));
			$page->Body .= "
						<td>Слежение</td>
					</tr>\n";

			$sorter = $page->GetSorter("typeName");
			$query = 
"select api_assets.*, invTypes.typeName, invFlags.flagText
from api_assets
left join invTypes on invTypes.typeID = api_assets.typeId
left join invFlags on invFlags.flagID = api_assets.flag
where api_assets.locationId = $locationId and api_assets.accountId = '$accountId' and parentId = ''
$sorter;";
			//$qr = ExecuteQuery($query);
			$db = OpenDB2();
			//echo $query;
			$qr = $db->query($query);

			//$rowIndex = $pages->start;
			$this->rowIndex = 0;
			$rowClass = "even";
			while($row = $qr->fetch_assoc())
			{
				if(($this->rowIndex % 2) == 1)
					$rowClass = "b-row-even";
				else
					$rowClass = "b-row-odd";
				$this->rowIndex++;

				$hasChilds = $row["hasChilds"];
//<td><a href='$request_processor&locationId=$row[locationId]'>$row[locationName]</a></td>\n
				$page->Body .= "
						<tr class='$rowClass'>\n
							<td>{$this->rowIndex}</td>\n
							<td>$row[typeName]</td>
							<td class='b-right'>$row[quantity]</td>
							<td>$row[flagText]</td>
							<td>$row[singleton]</td>
							<td class='b-center' id='op_$row[recordId]'><a href='' onclick=\"itemsShowAdd('$row[recordId]'); return false;\"><img src='images/b_insrow.png'></a></td>
						</tr>\n";
				if($hasChilds)
				{
					$recordId = $row["recordId"];
					$this->ShowChilds($db, $page, $recordId, $accountId, $sorter, 1);
				}
			}
			$db->close();

			$page->Body .= "
				</table>\n
			";
		}

		function ShowMonitoringList($page, $accountId)
		{
			$page->Body .= "
<script src=\"lib/JsHttpRequest/JsHttpRequest.js\"></script>
<script language='JavaScript'>
	function monitorEdit(recordId)
	{
		//alert('min_' + recordId);
		//name = 'min_' + recordId;
		min = document.getElementById('min_' + recordId).innerHTML;
		norm = document.getElementById('norm_' + recordId).innerHTML;
		edit_min_id = \"'edit_min_\" + recordId + \"'\";
		edit_norm_id = \"'edit_norm_\" + recordId + \"'\";
		edit_subm_fun = \"monitorEditSubmit('\" + recordId + \"')\";
		edit_min =\"<input type='text' value='\" + min + \"' id=\" + edit_min_id + \" size='8'>\";
		edit_norm =\"<input type='text' value='\" + norm + \"' id=\" + edit_norm_id + \" size='8'>\";
		edit_submit = '<button onclick=\"' + edit_subm_fun + '\">Ok</button>';
		document.getElementById('min_' + recordId).innerHTML = edit_min;
		document.getElementById('norm_' + recordId).innerHTML = edit_norm;
		document.getElementById('op_' + recordId).innerHTML = edit_submit;
		//alert(edit_submit);
	}
	function monitorEditSubmit(recordId)
	{
		//alert(recordId);
		edit_min_id = \"edit_min_\" + recordId + \"\";
		edit_norm_id = \"edit_norm_\" + recordId + \"\";
		min = document.getElementById(edit_min_id).value;
		norm = document.getElementById(edit_norm_id).value;
		//alert(norm);
        JsHttpRequest.query(
            'classes/api_assets_backend.php',
            {
            	'function' : 'edit',
                'recordId': recordId,
                'min': min,
                'norm' : norm
            },
            // Function is called when an answer arrives. 
            function(result, errors)
            {
            	//alert('1');
                // Write errors to the debug div.
                document.getElementById('debug').innerHTML = errors; 
                // Write the answer.
                if (result)
                {
                	document.getElementById('min_' + recordId).innerHTML = result[\"min\"];
                	document.getElementById('norm_' + recordId).innerHTML = result[\"norm\"];
                	buttons = '<a href=\'\' onclick=\"monitorEdit(\'' + result[\"recordId\"] + '\'); return false;\"><img src=\'images/b_edit.png\'></a>' + 
                			'<a href=\'\' onclick=\"monitorDelete(\'' + result[\"recordId\"] + '\'); return false;\"><img src=\'images/b_drop.png\'></a>';
                	//alert(buttons);
                	document.getElementById('op_' + recordId).innerHTML = buttons;
                }
            },
            false  // do not disable caching
        );
	}
	function monitorDelete(recordId)
	{
		ans = confirm('Вы действительно хотите удалить запись?');
		if(ans == true)
		{
			//alert(ans);
			JsHttpRequest.query(
				'classes/api_assets_backend.php',
				{
					'function' : 'delete',
					'recordId': recordId
				},
				// Function is called when an answer arrives. 
				function(result, errors)
				{
					//alert('1');
					// Write errors to the debug div.
					document.getElementById('debug').innerHTML = errors; 
					// Write the answer.
					if (result)
					{
						//document.getElementById('min_' + recordId).innerHTML = result[\"min\"];
						//document.getElementById('norm_' + recordId).innerHTML = result[\"norm\"];
						if(result[\"affected_rows\"] == 1)
							buttons = 'deleted';
						else
							buttons = '<a href=\'\' onclick=\"monitorEdit(\'' + result[\"recordId\"] + '\'); return false;\"><img src=\'images/b_edit.png\'></a>';
						//alert(buttons);
						document.getElementById('op_' + recordId).innerHTML = buttons;
					}
				},
				false  // do not disable caching
			);
		}
	}
</script>
";
			$page->Body .= "
<div id='debug'></div>
				<table class='b-border b-widthfull'>\n
					<tr class='b-table-caption'>\n
						<td>#</td>\n";
			$page->Body .= $page->WriteSorter(array (
				"locationName" => "Локация",
				"typeName" => "Тип",
				"quantity" => "Количество",
				"quantityMinimum" => "Минимум",
				"quantityNormal" => "Норма",
				));
			$page->Body .= "
				<td>Операция</td>
					</tr>\n";

			$sorter = $page->GetSorter("locationName");
			$query = 
"select a.*, 
case
when a.locationid between 66000000 and 66015131 then (
	select s.stationName from staStations as s
	where s.stationID = a.locationid -6000001
)
when a.locationid between 66015132 and 67999999 then (
	select c.stationname from api_outposts as c
	where c.stationid = a.locationid -6000000
)
else (
	select m.itemName from mapDenormalize as m
	where m.itemID = a.locationid
)
end as locationName, 
case
when a.locationid between 66000000 and 66015131 then (
	select ss.solarSystemID from staStations as ss
	where ss.stationID = a.locationid -6000001
)
when a.locationid between 66015132 and 67999999 then (
	select cc.solarSystemID from api_outposts as cc
	where cc.stationid = a.locationid -6000000
)
else (
	select mm.solarSystemID from mapDenormalize as mm
	where mm.itemID = a.locationid
)
end as solarSystemID, 
invTypes.typeName, sum(api_assets.quantity) as quantity
from api_assets_monitor as a
left join invTypes on invTypes.typeID = a.typeId
left join api_assets on (api_assets.typeId = a.typeId and api_assets.locationId = a.locationId and api_assets.accountId = '$accountId')
where a.accountId = '$accountId' group by a.typeId, a.locationId $sorter;";

			//$qr = ExecuteQuery($query);
			$db = OpenDB2();
			//echo $query;
			$qr = $db->query($query);

			//$rowIndex = $pages->start;
			$this->rowIndex = 0;
			while($row = $qr->fetch_assoc())
			{
				if(($this->rowIndex % 2) == 1)
					$rowClass = "b-row-even";
				else
					$rowClass = "b-row-odd";
				$this->rowIndex++;

				//определение соответсвия заданным пределам
				$quantity = $row["quantity"];
				$quantityMinimum = $row["quantityMinimum"];
				$quantityNormal = $row["quantityNormal"];
				//подсветка
				$highlight = "";
				if($quantity < $quantityNormal)
					$highlight = "bgcolor='orange'";
				if($quantity < $quantityMinimum)
					$highlight = "bgcolor='red'";
				

				$page->Body .= "
						<tr class='$rowClass'>\n
							<td>{$this->rowIndex}</td>\n
							<td>$row[locationName]</td>
							<td>$row[typeName]</td>
							<td class='b-right' $highlight>$row[quantity]</td>
							<td class='b-right' id='min_$row[recordId]'>$row[quantityMinimum]</td>
							<td class='b-right' id='norm_$row[recordId]'>$row[quantityNormal]</td>
							<!--//
							<td class='b-center'><a href='{$this->request_processor}&editmonitor=$row[recordId]'><img src='images/b_edit.png'></a> <a href='{$this->request_processor}&viewmonitor&delmonitor=$row[recordId]'><img src='images/b_drop.png'></a></td>
							//-->
							<td class='b-center' id='op_$row[recordId]'>
								<a href='' onclick=\"monitorEdit('$row[recordId]'); return false;\"><img src='images/b_edit.png'></a>
								<a href='' onclick=\"monitorDelete('$row[recordId]'); return false;\"><img src='images/b_drop.png'></a>
							</td>
						</tr>\n";
			}
			$db->close();

			$page->Body .= "
				</table>\n
			";
		}

		function ProcessSubscribe($db, $accountId)
		{
			include_once "user.php";
			include_once "subscribes.php";

			//include_once "classes/api_corporationsheet.php";
			//получение юзера по аакаунту
			$user = new User("");
			if($user->GetUserInfo($accountId))
			{
				$user->CheckAccessRights(get_class($this), true);

				$Api = new ApiInterface($user->GetAccountId());
				$Api->userId = $user->GetUserId();
				$Api->apiKey = $user->GetApiKey();
				$Api->characterId = $user->GetCharacterId();
				$corpInfo = $Api->GetCorpCorporationSheet();

				$query = 
"select a.*, case

when a.locationid between 66000000 and 66015131 then (
	select s.stationName from staStations as s
	where s.stationID = a.locationid -6000001
)
when a.locationid between 66015132 and 67999999 then (
	select c.stationname from api_outposts as c
	where c.stationid = a.locationid -6000000
)
else (
	select m.itemName from mapDenormalize as m
	where m.itemID = a.locationid
)
end as locationName, invTypes.typeName, sum(api_assets.quantity) as quantity
from api_assets_monitor as a
left join invTypes on invTypes.typeID = a.typeId
left join api_assets on (api_assets.typeId = a.typeId and api_assets.locationId = a.locationId)
where a.accountId = '$accountId' group by a.typeId, a.locationId;";

				$qr = $db->query($query);

				$message = "
<html>
	<head>
		<title>Состояние припасов корпорации $corpInfo[corporationName]</title>
	</head>
<body><p>Состояние припасов корпорации <b>$corpInfo[corporationName]</b>:</p>
	<table bordercolor='silver' border='1' cellspacing='1' cellpadding='1'>\n
		<tr bgcolor='#808080'>\n
			<td>#</td>\n
			<td>Локация</td>\n
			<td>Тип</td>\n
			<td>Количество</td>\n
			<td>Минимум</td>\n
			<td>Норма</td>\n
		</tr>\n";

				//$rowIndex = $pages->start;
				$this->rowIndex = 0;

				while($row = $qr->fetch_assoc())
				{
					if(($this->rowIndex % 2) == 1)
						$rowClass = "b-row-even";
					else
						$rowClass = "b-row-odd";
					$this->rowIndex++;

					//определение соответсвия заданным пределам
					$quantity = $row["quantity"];
					$quantityMinimum = $row["quantityMinimum"];
					$quantityNormal = $row["quantityNormal"];
					//подсветка
					$highlight = "";
					if($quantity < $quantityNormal)
						$highlight = "bgcolor='orange'";
					if($quantity < $quantityMinimum)
						$highlight = "bgcolor='red'";
					

					$message .= "
							<tr >\n
								<td align='center'>{$this->rowIndex}</td>\n
								<td align='center'>$row[locationName]</td>
								<td>$row[typeName]</td>
								<td align='right' $highlight>$row[quantity]</td>
								<td align='right'>$row[quantityMinimum]</td>
								<td align='right'>$row[quantityNormal]</td>
							</tr>\n";
				}

				$message .= "</table><br>";
				$link = "http://ea.mylegion.ru/index.php?mode=" . get_class($this);
				$message .= "<a href='$link'>$link</a>";
				$message .= "</body></html>";
				$qr->close();

				$subject = "Состояние припасов корпорации $corpInfo[corporationName]";

				//получение адресов и подписок этого аккаунта
				$query = "select email, modes from api_subscribes where accountId = '$accountId';";
				$qr = $db->query($query);
				while($row = $qr->fetch_assoc())
				{
					$email = $row["email"];
					$modes = $row["modes"];
					$thissub = get_class($this);
					if(preg_match("/(\W|^)$thissub(\W|$)/", $modes) != 0)
					{
						Subscribes::SendMail($email, $subject, $message);
						//$page->Body .= "$email<br>$subject<br>$message<br>";
						//echo "$email<br>$subject<br>$message<br>";
					}
				}
				$qr->close();
			}
		}
	}
	/*
* 
* **********************************************************************
*  запрос содержимого assets с выводом названия location
SELECT a.*, CASE
WHEN a.locationid
BETWEEN 66000000
AND 66015131
THEN (

SELECT s.stationName
FROM staStations AS s
WHERE s.stationID = a.locationid -6000001
)
WHEN a.locationid
BETWEEN 66015132
AND 67999999
THEN (

SELECT c.stationname
FROM api_outposts AS c
WHERE c.stationid = a.locationid -6000000
)
ELSE (

SELECT m.itemName
FROM mapDenormalize AS m
WHERE m.itemID = a.locationid
)
END AS location
FROM api_assets AS a
* 
* **********************************************************************
* вывод перечня локаций и названий типов
* 
SELECT CASE
WHEN a.locationid
BETWEEN 66000000
AND 66015131
THEN (

SELECT s.stationName
FROM staStations AS s
WHERE s.stationID = a.locationid -6000001
)
WHEN a.locationid
BETWEEN 66015132
AND 67999999
THEN (

SELECT c.stationname
FROM api_outposts AS c
WHERE c.stationid = a.locationid -6000000
)
ELSE (

SELECT m.itemName
FROM mapDenormalize AS m
WHERE m.itemID = a.locationid
)
END AS location, a.*, invTypes.typeName
FROM api_assets AS a
left join invTypes on invTypes.typeID = a.typeId
group by a.locationId;
* **********************************************************************
* список мониторинга
select a.*, case

when a.locationid between 66000000 and 66015131 then (
	select s.stationName from staStations as s
	where s.stationID = a.locationid -6000001
)
when a.locationid between 66015132 and 67999999 then (
	select c.stationname from api_outposts as c
	where c.stationid = a.locationid -6000000
)
else (
	select m.itemName from mapDenormalize as m
	where m.itemID = a.locationid
)
end as locationName, invTypes.typeName
from api_assets_monitor as a
left join invTypes on invTypes.typeID = a.typeId
*/
?>
