<?php
    class Api_MemberSecurity
    {
        function PreProcess($page)
		{
			//session_start();
			date_default_timezone_set("Etc/Universal");
			
			$User = User::CheckLogin();
			$User->CheckAccessRights(get_class($this), true);

			$Api = new ApiInterface($User->GetAccountId());
			$Api->userId = $User->GetUserId();
			$Api->apiKey = $User->GetApiKey();
			$Api->characterId = $User->GetCharacterId();

			$accountId = $User->GetAccountId();

			$request_processor = $_SERVER["PHP_SELF"] . "?mode=" . get_class($this);

			if(isset($_REQUEST["type"]))
				$type = $_REQUEST["type"];
			else
				$type = "roles";

			$types = array(
				"roles" => "roles",
				"grantableRoles" => "grantableRoles",
				"rolesAtHQ" => "rolesAtHQ",
				"grantableRolesAtHQ" => "grantableRolesAtHQ",
				"rolesAtBase" => "rolesAtBase",
				"grantableRolesAtBase" => "grantableRolesAtBase",
				"rolesAtOther" => "rolesAtOther",
				"grantableRolesAtOther" => "grantableRolesAtOther",
				"titles" => "titles",
			);
			if(!array_key_exists($type, $types))
			{
				$type = "roles";
			}

			$db = OpenDB2();


			$page->Body = "
		<form action='$request_processor' method='post'>
			Выбирите информацию: 
			<select name='type'>";

			foreach ($types as $k=>$v)
			{
				if($k == $type)
					$selected = "selected";
				else
					$selected = "";
				$page->Body .= "<option value='$k' $selected>$v</option>";
			}

			$page->Body .= "
			</select>
			<input type='submit' value='Показать'> 
		</form>";

			//вывод не титлов
			if($type != "titles")
			{

			//подсчёт числа подходящих строк
			//$qr = $db->query("select count(*) as _count_ from api_member_tracking where accountId = '$accountId';");
			//$row = $qr->fetch_assoc();
			//$recordsCount = $row["_count_"];
			//$qr->close();


			//$pages = new PageSelector();
			//$page->Body = $pages->Write($recordsCount);

			//$page->Body .= $query;

			/*$page->Body .= "
<table class='b-border'>
    <tr class='b-table-caption'>
		<td>#</td>\n";
			$page->Body .= $page->WriteSorter(array (
				"name" => "Имя",
				"startDateTime" => "Дата приёма",
				"base" => "База",
				"logonDateTime" => "Логон",
				"logoffDateTime" => "Логофф",
				"location" => "Местоположение",
				"shipType" => "Корабль"
				));
			$page->Body .= "
    </tr>";*/
    			$page->Body .= "
	<table class='b-border b-widthfull'>
	    <tr class='b-table-caption'>
	        <td rowspan='2'>Character</td>
	        <td rowspan='2'>Director</td>
	        <td colspan='2'>Accountant</td>
	        <td rowspan='2'>Security officer</td>
	        <td colspan='5'>Manager</td>
	        <td rowspan='2'>Auditor</td>
	        <td colspan='7'>Hangar</td>
	        <td colspan='7'>Account</td>
	        <td rowspan='2'>Equipment config</td>
	        <td colspan='7'>Container</td>
	        <td colspan='3'>Can rent</td>
	        <td rowspan='2'>Starbase config</td>
	        <td rowspan='2'>Trader</td>
	        <td rowspan='2'>Infrastructure tactical officer</td>
	        <td rowspan='2'>Starbase caretaker</td>
	    </tr>
	    <tr class='b-table-caption'>
	        <td>Full</td>
	        <td>Junior</td>
	        <td>Personnel</td>
	        <td>Factory</td>
	        <td>Station</td>
	        <td>Chat</td>
	        <td>Contract</td>
	        <td>1</td><td>2</td><td>3</td><td>4</td><td>5</td><td>6</td><td>7</td>
	        <td>1</td><td>2</td><td>3</td><td>4</td><td>5</td><td>6</td><td>7</td>
	        <td>1</td><td>2</td><td>3</td><td>4</td><td>5</td><td>6</td><td>7</td>
	        <td>Office</td>
	        <td>Factory slot</td>
	        <td>Research slot</td>
	    </tr>";

			//$sorter = $page->GetSorter("name");
			//отдельно количество можно не запрашивать, т.к. есть свойство affected_rows
			//нет. оказалось надо отдельно, т.к. число записей нужно для определения покаызываемой страницы
			//$query = "select * from api_member_tracking where accountId = '$accountId' $sorter limit $pages->start, $pages->count;";
				$query = "select * from api_member_security where accountId = '$accountId' order by characterName;";
				//echo $query;
				$qr = $db->query($query);

				$rowIndex = 0;//$pages->start;
				while($row = $qr->fetch_assoc())
				{
					if(($rowIndex % 2) == 1)
						$rowClass = "b-row-even";
					else
						$rowClass = "b-row-odd";

					$page->Body .= "<tr class='$rowClass'>";
					$page->Body .= $this->SprintSecurityRow($row, $type);
					$page->Body .= "</tr>";
					$rowIndex++;
				}
				$page->Body .= "</table>";
				$qr->close();
			}
			else//вывод титлов
			{
				$aTitles = array();
				$query = "select * from api_titles where accountId = '$accountId';";
				$qr = $db->query($query);
				while($row = $qr->fetch_assoc())
				{
					$aTitles[$row["titleId"]] = $row["titleName"];
				}
				$qr->close();

				$page->Body .= "<table class='b-border b-widthfull'>
					<tr class='b-table-caption'>
						<td>Пилот</td>
						<td>Титлы</td>
					</tr>";
				$query = "select * from api_member_security where accountId = '$accountId' order by characterName;";
				$qr = $db->query($query);
				$rowIndex = 0;//$pages->start;
				while($row = $qr->fetch_assoc())
				{
					if(($rowIndex % 2) == 1)
						$rowClass = "b-row-even";
					else
						$rowClass = "b-row-odd";

					$page->Body .= "<tr class='$rowClass'>
						<td>$row[characterName]</td>";
					$str = "";
					$bits = $row["titles"];
					for($i = 0; $i < 32; $i++)
					{
						if($bits & (1 << $i))
						{
							if($str != "")
								$str .= "<br>";
							$str .= base64_decode($aTitles[1 << $i]);
						}
					}
					$page->Body .= "<td>$str</td>";
					$page->Body .= "</tr>";
					$rowIndex++;
				}
				$page->Body .= "</table>";
			}
			//$page->Body .= $pages->Write($recordsCount);
			$db->close();
		}
		function SprintSecurityRow($row, $field)
		{
			$str = "";
			$bits = $row[$field];
			$str .= "<td>$row[characterName]</td>";
			if($field != "titles")
			{
				$str .= $this->PrintSuccessImage($bits & 1);
				$str .= $this->PrintSuccessImage($bits & 256);
				$str .= $this->PrintSuccessImage($bits & 4503599627370496);
				$str .= $this->PrintSuccessImage($bits & 512);
				$str .= $this->PrintSuccessImage($bits & 128);
				$str .= $this->PrintSuccessImage($bits & 1024);
				$str .= $this->PrintSuccessImage($bits & 2048);
				$str .= $this->PrintSuccessImage($bits & 36028797018963968);
				$str .= $this->PrintSuccessImage($bits & 72057594037927936);
				$str .= $this->PrintSuccessImage($bits & 4096);

				$str .= $this->PrintAccessImages($bits & 1048576, $bits & 8192);
				$str .= $this->PrintAccessImages($bits & 2097152, $bits & 16384);
				$str .= $this->PrintAccessImages($bits & 4194304, $bits & 32768);
				$str .= $this->PrintAccessImages($bits & 8388608, $bits & 65536);
				$str .= $this->PrintAccessImages($bits & 16777216, $bits & 131072);
				$str .= $this->PrintAccessImages($bits & 33554432, $bits & 262144);
				$str .= $this->PrintAccessImages($bits & 67108864, $bits & 524288);

				$str .= $this->PrintAccessImages($bits & 17179869184, $bits & 134217728);
				$str .= $this->PrintAccessImages($bits & 34359738368, $bits & 268435456);
				$str .= $this->PrintAccessImages($bits & 68719476736, $bits & 536870912);
				$str .= $this->PrintAccessImages($bits & 137438953472, $bits & 1073741824);
				$str .= $this->PrintAccessImages($bits & 274877906944, $bits & 2147483648);
				$str .= $this->PrintAccessImages($bits & 549755813888, $bits & 4294967296);
				$str .= $this->PrintAccessImages($bits & 1099511627776, $bits & 8589934592);

				$str .= $this->PrintSuccessImage($bits & 2199023255552);

				$str .= $this->PrintAccessImages(0, $bits & 4398046511104);
				$str .= $this->PrintAccessImages(0, $bits & 8796093022208);
				$str .= $this->PrintAccessImages(0, $bits & 17592186044416);
				$str .= $this->PrintAccessImages(0, $bits & 35184372088832);
				$str .= $this->PrintAccessImages(0, $bits & 70368744177664);
				$str .= $this->PrintAccessImages(0, $bits & 140737488355328);
				$str .= $this->PrintAccessImages(0, $bits & 281474976710656);

				$str .= $this->PrintSuccessImage($bits & 562949953421312);
				$str .= $this->PrintSuccessImage($bits & 1125899906842624);
				$str .= $this->PrintSuccessImage($bits & 2251799813685248);

				$str .= $this->PrintSuccessImage($bits & 9007199254740992);
				$str .= $this->PrintSuccessImage($bits & 18014398509481984);
				$str .= $this->PrintSuccessImage($bits & 144115188075855872);
				$str .= $this->PrintSuccessImage($bits & 288230376151711744);
			}
			return $str;
		}
		function PrintSuccessImage($condition)
		{
			$imageOk = "<image src='images/s_success.png' alt='granted'>";
			if($condition != 0)
				return "<td>$imageOk</td>";
			else
				return "<td></td>";
		}
		function PrintAccessImages($conditionQuery, $conditionTake)
		{
			$imageQuery = "<image src='images/see.png' alt='can query'>";
			$imageTake = "<image src='images/hand.png' alt='can take'>";
			$str = "<td>";
			if($conditionQuery != 0)
				$str .= $imageQuery;
			if($conditionTake != 0)
				$str .= $imageTake;
			$str .= "</td>";
			return $str;
		}
	}
?>
