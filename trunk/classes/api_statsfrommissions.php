<?php
    class Api_StatsFromMissions
    {
    	function ParseDate($str, $default)
    	{
    		$result = $default;
			date_default_timezone_set("Etc/Universal");
			if($str)
			{
				if(preg_match("/^\d\d\d\d-\d\d-\d\d$/i", $str) == 1)
				{
					try
					{
						$date = new DateTime($str);
						$result = $date->format("Y-m-d");
					}
					catch(Exception $exc)
					{
						$result = $default;
					}
				}
			}
			return $result;
		}
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
			$corpInfo = $Api->GetCorpCorporationSheet();
			$accountId = $User->GetAccountId();

			//проверка ввода первой даты
			if(isset($_POST["date1"]))
			{
				$date1Str = $this->ParseDate($_POST["date1"], date("Y-m-d", strtotime("-1 day")));
			}
			else
			{
				//если сохранено в сессии, берём оттуда
				if(isset($_SESSION["date1"]))
					$date1Str = $_SESSION["date1"];
				else
					$date1Str = date("Y-m-d", strtotime("-1 day"));
			}
			$_SESSION["date1"] = $date1Str;

			//проверка ввода второй даты
			if(isset($_POST["date2"]))
			{
				$date2Str = $this->ParseDate($_POST["date2"], date("Y-m-d"));
			}
			else
			{
				//если сохранено в сессии, берэм оттуда
				if(isset($_SESSION["date2"]))
					$date2Str = $_SESSION["date2"];
				else
					$date2Str = date("Y-m-d");
			}
			$_SESSION["date2"] = $date2Str;

			$request_processor = $_SERVER["PHP_SELF"] . "?mode=" . get_class($this);

			//построение выражения для where
			$where = " where accountId = '$accountId' and (_date_ between '$date1Str' and '$date2Str')".
			" and refTypeId in (17, 33, 34, 85)";

			$db = OpenDB2();

			//подсчёт числа подходящих строк
			$query = "select count(distinct(ownerId2)) as _count_ from api_wallet_journal $where;";
			//echo $query;
			$qr = $db->query($query);
			$row = $qr->fetch_assoc();
			$recordsCount = $row["_count_"];
			$qr->close();


			$pages = new PageSelector();
			$page->Body = $pages->Write($recordsCount);


			//$page->Body .= $query;

			$page->Body .= "
<form action='$request_processor' method='post'>
	<label for='date1'>Дата:</label>
	<input type='text' id='date1' name='date1' value='$date1Str' size='10' maxlength='10'>
	<label for='date2'>-</label>
	<input type='text' id='date2' name='date2' value='$date2Str' size='10' maxlength='10'>
	<button type='submit'>OK</button>
</form>
<table class='b-border b-widthfull'>
    <tr class='b-table-caption'>
		<td class='b-center'>#</td>\n";
			$page->Body .= $page->WriteSorter(array (
				"ownerName2" => "Имя",
				"sumAmount" => "Сумма"
				));
			$page->Body .= "
    </tr>";
			$sorter = $page->GetSorter("sum(amount)");
			//отдельно количество можно не запрашивать, т.к. есть свойство affected_rows
			//нет. оказалось надо отдельно, т.к. число записей нужно для определения покаызываемой страницы
			$query = 
"select Sum(amount) as sumAmount, ownerName2 from api_wallet_journal\n
$where group by ownerName2 $sorter limit $pages->start, $pages->count;";
			//echo $query;
			$qr = $db->query($query);

    		$rowIndex = $pages->start;
			while($row = $qr->fetch_assoc())
			{
				if(($rowIndex % 2) == 1)
					$rowClass = "b-row-even";
				else
					$rowClass = "b-row-odd";

				$amount = number_format($row["sumAmount"], 2, ",", " ");
				$amount = str_replace(" ", "&nbsp;", $amount);

				$page->Body .= "
	<tr class='$rowClass'>
		<td class='b-center'>$rowIndex</td>
		<td class='b-center'>$row[ownerName2]</td>
		<td class='b-right'>$amount</td>
	</tr>
";
				$rowIndex++;
			}
    		$page->Body .= "
</table>
";
			$page->Body .= $pages->Write($recordsCount);
			$qr->close();
			$db->close();

		}
	}
?>
