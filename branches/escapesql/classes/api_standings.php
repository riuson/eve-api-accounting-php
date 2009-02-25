<?php

	class Api_Standings
	{
		public function PreProcess($page)
		{
			$User = User::CheckLogin();
			$User->CheckAccessRights(get_class($this), true);

			$Api = new ApiInterface($User->GetAccountId());
			$Api->userId = $User->GetUserId();
			$Api->apiKey = $User->GetApiKey();
			$Api->characterId = $User->GetCharacterId();

			$accountId = $User->GetAccountId();

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

			if(isset($_REQUEST["from"]))
				$from = $_REQUEST["from"];
			else
				$from = 1;

			if(isset($_REQUEST["of"]))
				$of = $_REQUEST["of"];
			else
				$of = 1;

			if(isset($_REQUEST["target"]))
				$target = $_REQUEST["target"];
			else
				$target = 1;

			$uri = $_SERVER["REQUEST_URI"];

			/*//если в строке адреса нету этих данных, добавить
			if(preg_match("/type=\w+/i", $uri) == 0)
				$uri .= "&amp;type=$type";
			else//если есть - заменить
			{
				$pattern = "/(^.*)(type=\w+)(.*$)/i";//"/(\w+) (\d+), (\d+)/i";
				$replacement = "\${1}type=$type\$3";
				$uri = preg_replace($pattern, $replacement, $uri);
			}

			$_SERVER["REQUEST_URI"] = $uri;
			*/
//print_r($_SERVER);
//print($uri);
			//$pages = new PageSelector();
			//$pages->Write($recordsCount);
			$arTargets = array(
				1 => "Пилоты",
				2 => "Корпорации",
				3 => "Альянсы",
				4 => "Агенты",
				5 => "NPC корпорации",
				6 => "Фракции");
			$arStandsFromTo = array(
				1 => "К",
				2 => "От");
			$arStandsOf = array(
				1 => "Для корпорации",
				2 => "Для альянса");

			$page->Body = "<form action='$uri' method='post'>";
			$page->Body .= "Выберите стенды: ";

			$sel1 = ($of == 1 ? "selected" : "");
			$sel2 = ($of == 2 ? "selected" : "");
			$page->Body .= "<select name='of'>
				<option value='1' $sel1>{$arStandsOf[1]}</option>
				<option value='2' $sel2>{$arStandsOf[2]}</option>
				</select>";

			$sel1 = ($from == 1 ? "selected" : "");
			$sel2 = ($from == 2 ? "selected" : "");
			$page->Body .= "<select name='from'>
				<option value='1' $sel1>{$arStandsFromTo[1]}</option>
				<option value='2' $sel2>{$arStandsFromTo[2]}</option>
				</select>";

			$page->Body .= "<select name='target'>";
			foreach ($arTargets as $k=>$v)
			{
				if($k == $target)
					$selected = "selected";
				else
					$selected = "";
				$page->Body .= "<option value='$k' $selected>$v</option>";
			}
			$page->Body .= "</select>";

			$page->Body .= "
			<input type='submit' value='Показать'> 
		</form>";
			//print_r($qr);
			$page->Body .= "
				<table class='b-widthfull b-border'>\n
					<tr class='b-table-caption'>\n
						<td>#</td>\n";
			$page->Body .= $page->WriteSorter(array (
					"name" => "Имя",
					"standings" => "Стенд"),
				$uri);
			$page->Body .= "
					</tr>\n";

			$sorter = $page->GetSorter("name");
			$query = "select * from api_standings where accountId = '$accountId' and standsOf = $of and fromTo = $from and target = $target $sorter ;";
			//echo $query;
			$qr = $dblink->query($query);

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
					if($name == "")
						$name = "name empty, id = " . $row["id"];
					if($target == 2)
						$name = "<a href='index.php?mode=Api_CorporationSheet&amp;corporationId=$row[id]'>$name</a>";
					if($target == 3)
						$name = "<a href='index.php?mode=Api_Alliances&amp;allianceId=$row[id]'>$name</a>";
					$standings = $row["standings"];
					$page->Body .= "
							<tr class='$rowClass'>\n
								<td>$rowIndex</td>\n
								<td>$name</td>\n
								<td>$standings</td>\n
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
