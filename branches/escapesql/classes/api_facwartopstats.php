<?php

	class Api_FacWarTopStats
	{
		public function PreProcess($page)
		{
			$Api = new ApiInterface("");

			//$Api->UpdateErrors();
			//$Api->UpdateFacWarTopStats();

			////вывод элемента постраничного просмотра
			$dblink = OpenDB2();
			//$qr = $dblink->query("select count(*) as _count_ from api_alliances;");
			//$row = $qr->fetch_assoc();
			//$recordsCount = $row["_count_"];
			//$qr->close();

			//$stmt =  $dblink->prepare("select count(*) as _count_ from api_alliances;");
			//$stmt->execute();
			//$stmt->bind_result($recordsCount);
			//$stmt->fetch();
			//$stmt->close();

			if(isset($_REQUEST["who"]))
				$who = $_REQUEST["who"];
			else
				$who = "characters";

			if(isset($_REQUEST["stat"]))
				$stat = $_REQUEST["stat"];
			else
				$stat = "KillsYesterday";

			$uri = $_SERVER["REQUEST_URI"];
			//если в строке адреса нету этих данных, добавить
			if(preg_match("/who=\w+/i", $uri) == 0)
				$uri .= "&amp;who=$who";
			else//если есть - заменить
			{
				$pattern = "/(^.*)(who=\w+)(.*$)/i";//"/(\w+) (\d+), (\d+)/i";
				$replacement = "\${1}who=$who\$3";
				$uri = preg_replace($pattern, $replacement, $uri);
			}
			if(preg_match("/stat=\w+/i", $uri) == 0)
				$uri .= "&amp;stat=$stat";
			else//если есть - заменить
			{
				$pattern = "/(^.*)(stat=\w+)(.*$)/i";//"/(\w+) (\d+), (\d+)/i";
				$replacement = "\${1}stat=$stat\$3";
				$uri = preg_replace($pattern, $replacement, $uri);
			}

			$_SERVER["REQUEST_URI"] = $uri;
//print_r($_SERVER);
//print($uri);
			//$pages = new PageSelector();
			//$pages->Write($recordsCount);
			$forWho = array(
				"characters" => "пилоты",
				"corporations" => "корпорации",
				"factions" => "фракции");
			$stats = array(
				"KillsYesterday" => "сбито за сегодня",
				"KillsLastWeek" => "сбито за неделю",
				"KillsTotal" => "сбито всего",
				"VictoryPointsYesterday" => "счёт за сегодня",
				"VictoryPointsLastWeek" => "счёт за неделю",
				"VictoryPointsTotal" => "счёт общий");

			$page->Body = "
		<form action='$uri' method='post'>
			Выберите статистику: 
			<select name='who'>";

			foreach ($forWho as $k=>$v)
			{
				if($k == $who)
					$selected = "selected";
				else
					$selected = "";
				$page->Body .= "<option value='$k' $selected>$v</option>";
			}

			$page->Body .= "
			</select>
			<select name='stat'>
			";

			foreach ($stats as $k=>$v)
			{
				if($k == $stat)
					$selected = "selected";
				else
					$selected = "";
				$page->Body .= "<option value='$k' $selected>$v</option>";
			}

			$page->Body .= "
			</select>";


			$page->Body .= "
			<input type='submit' value='Показать'> 
		</form>";
			//print_r($qr);
			$page->Body .= "
				<table class='b-border b-widthfull'>\n
					<tr class='b-table-caption'>\n
						<td>#</td>\n";
			$page->Body .= $page->WriteSorter(array (
					"name" => $forWho[$who],
					"value" => "Результат"),
				$uri);
			$page->Body .= "
					</tr>\n";

			$sorter = $page->GetSorter("value");
			$qr = $dblink->query("select * from api_facwartopstats where forWho = '$who' and statName = '$stat' $sorter ;");

			$rowIndex = 0;
			$rowClass = "even";
			while($row = $qr->fetch_assoc())
			{
				if(($rowIndex % 2) == 1)
					$rowClass = "b-row-even";
				else
					$rowClass = "b-row-odd";
				$rowIndex++;

				{
					$name = $row["name"];
					if($who == "corporations")
						$name = "<a href='index.php?mode=Api_CorporationSheet&amp;corporationId=$row[id]'>$row[name]</a>";
					$value = $row["value"];
					$page->Body .= "
							<tr class='$rowClass'>\n
								<td>$rowIndex</td>\n
								<td>$name</td>\n
								<td>$value</td>\n
							</tr>\n";
				}
			}
			
			$page->Body .= "
				</table>\n
			";

			$qr->close();
			$dblink->close();
		}
	}
?>
