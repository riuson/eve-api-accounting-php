<?php
    class InsuranceMails
    {
        function PreProcess($page)
		{
			//session_start();
			date_default_timezone_set("Etc/Universal");
			
			$User = User::CheckLogin();
			$User->CheckAccessRights(get_class($this), true);

			$Api = new ApiInterface($User->GetAccountId());
			//$Api->userId = $User->GetUserId();
			//$Api->apiKey = $User->GetApiKey();
			//$Api->characterId = $User->GetCharacterId();

			$accountId = $User->GetAccountId();


			//
			$request_processor = $_SERVER["PHP_SELF"] . "?mode=" . get_class($this);

			$page->Body = "
<script type=\"text/javascript\">
	function showdiv(div_id) {
		// скрывает все div`ы
		if(document.getElementById(div_id).style.display == 'none')
		{
			document.getElementById(div_id).style.display = 'block';
			//alert(1);
		}
		else
		{
			document.getElementById(div_id).style.display = 'none';
			//alert(2);
		}
		return false;
	}
</script>

<form action='$request_processor' method='post'>
	<a href='' onClick=\"showdiv('postemail'); return false;\" >Добавить письмо</a><br>
	<div id='postemail' style='display: none;'>
		<textarea rows='8' cols='50' name='new_email_text'></textarea><br>
		<input type='submit' name='new_email_submit' value='Отправить'>
	</div>
</form>
";
			if(isset($_POST["new_email_text"]))
			{
				$db = OpenDB2();
				$emailText = $_POST["new_email_text"];
				$res = $this->AddEmailToBD($db, $accountId, $emailText);
				//print_r($res);
				$res = $this->AddToInsuranceList($db, $accountId, $res);
				//print_r($res);
				$db->close();
			}
			if(isset($_GET["rebuild"]))
			{
				$db = OpenDB2();
				$db->query("delete from api_insurance_list where accountId = '$accountId';");
				$qr = $db->query("select * from api_insurance_emails;");
				while($row = $qr->fetch_assoc())
				{
					$emailText = base64_decode($row["emailText"]);
					$res = $this->AddEmailToBD($db, $accountId, $emailText);
					//print_r($res);
					//echo "<br>";
					$res = $this->AddToInsuranceList($db, $accountId, $res);
					//print_r($res);
					//echo "<hr>";
				}
				$db->close();
			}


			$db = OpenDB2();


			if(isset($_GET["viewmails"]))
			{
				$viewmails = $_GET["viewmails"];
				if(preg_match("/^\d+$/", $viewmails) == 1)
				{
					$page->Body .= "<div class='b-widthfull'>Выборка по refId = $viewmails<br>";
					$qr = $db->query("select * from api_insurance_emails where accountId = '$accountId' and refId = $viewmails;");
					$index = 1;
					while($row = $qr->fetch_assoc())
					{
						$page->Body .= "Письмо $index:<br><div class='b-border'><pre>" . base64_decode($row["emailText"]) . "</pre></div><br>";
						$index++;
					}
					$qr->close();
					$page->Body .= "</div>";
				}
			}
			else
			{

				//подсчёт числа подходящих строк
				$qr = $db->query("select count(*) as _count_ from api_insurance_list where accountId = '$accountId';");
				$row = $qr->fetch_assoc();
				$recordsCount = $row["_count_"];
				$qr->close();

				$pages = new PageSelector();
				$page->Body .= $pages->Write($recordsCount);

				$page->Body .= "
<table class='b-border b-widthfull'>
    <tr class='b-table-caption'>
		<td class='b-center'>#</td>\n";
				$page->Body .= $page->WriteSorter(array (
					"refId" => "refId",
					"status" => "Состояние",
					"insuranceStart" => "Начало",
					"insuranceEnd" => "Конец",
					"insuranceISK" => "Выплата",
					"shipTypeName" => "Тип корабля",
					"shipName" => "Имя корабля"
					));
				$page->Body .= "
    </tr>";

				$sorter = $page->GetSorter("insuranceStart");

				$query = "select * from api_insurance_list where accountId = '$accountId' $sorter limit $pages->start, $pages->count;";
				$qr = $db->query($query);

				$rowIndex = $pages->start;
				while($row = $qr->fetch_assoc())
				{
					if(($rowIndex % 2) == 1)
						$rowClass = "b-row-even";
					else
						$rowClass = "b-row-odd";

					if($row["status"] == "issued")
						$color = "bgcolor='green'";
					else if($row["status"] == "insurance")
						$color = "bgcolor='red'";
					else
						$color = "";

					$shipName = base64_decode($row["shipName"]);
					$insuranceISK = number_format($row["insuranceISK"]);

					$page->Body .= "
	<tr class='$rowClass'>
		<td class='b-center'>$rowIndex</td>
		<td class='b-center'><a href='$request_processor&amp;viewmails=$row[refId]'>$row[refId]</a></td>
		<td class='b-center' $color>$row[status]</td>
		<td class='b-center'>$row[insuranceStart]</td>
		<td class='b-center'>$row[insuranceEnd]</td>
		<td class='b-right'>$insuranceISK</td>
		<td class='b-center'>$row[shipTypeName]</td>
		<td>$shipName</td>
	</tr>
";
					$rowIndex++;
				}
				$page->Body .= "
</table>
";
				$page->Body .= $pages->Write($recordsCount);
				$qr->close();
			}
			$db->close();
		}
		function AddEmailToBD($db, $accountId, $emailText)
		{
			$emailText = trim($emailText);
			$result = "";
			//----------------------------------------------------------------------
			//определяем тип письма: начало страховки, страховая выплата, истечение страховки
			$emailType = "unknown";
			if(preg_match("/insurance company has transferred.*into your account for the recent loss of your ship/i", $emailText) == 1)
				$emailType = "insurance";
			if(preg_match("/insurance contract between.*has expired/i", $emailText) == 1)
				$emailType = "expired";
			if(preg_match("/Congratulations on the insurance on your ship/i", $emailText) == 1)
				$emailType = "issued";
			//echo "Тип письма: $emailType<br>";
			$result["type"] = $emailType;
			
			//----------------------------------------------------------------------
			//выборка даты письма
			preg_match("/^\d\d\d\d\.\d\d\.\d\d \d\d:\d\d/", $emailText, $regs);
			//print_r($regs);
			$emailDate = $regs[0];
			//echo "Дата письма: $emailDate<br>";
			$result["date"] = $emailDate;
			
			//----------------------------------------------------------------------
			//выборка refId
			if($emailType == "insurance")
			{
				preg_match("/(?<=RefID:)\d+/", $emailText, $regs);
				//print_r($regs);
				$emailRefId = $regs[0];
			}
			if($emailType == "expired" || $emailType == "issued")
			{
				preg_match("/(?<=Reference ID: )\d+/", $emailText, $regs);
				//print_r($regs);
				$emailRefId = $regs[0];
			}
			//echo "RefID: $emailRefId<br>";
			$result["refId"] = $emailRefId;
			

			//----------------------------------------------------------------------
			//определение дат начала и конца страховки
			if($emailType == "issued")
			{
				$issuedTime = $emailDate;
				preg_match("/(?<=will expire at )[\d\.: ]+(?=\,)/", $emailText, $regs);
				//print_r($regs);
				$expiredTime = $regs[0];
				//echo "Начало и конец страховки: $issuedTime - $expiredTime<br>";
			}
			if($emailType == "expired")
			{
				$expiredTime = $emailDate;
				preg_match("/(?<=issued at )[\d\.: ]+(?= has expired)/", $emailText, $regs);
				//print_r($regs);
				$issuedTime = $regs[0];
				//echo "Начало и конец страховки: $issuedTime - $expiredTime<br>";
			}
			if($emailType == "insurance")
			{
				$issuedTime = $emailDate;
				$expiredTime = $emailDate;
				//echo "Начало и конец страховки: $issuedTime - $expiredTime<br>";
			}
			//echo "Начало и конец страховки: $issuedTime - $expiredTime<br>";
			$result["issued"] = $issuedTime;
			$result["expired"] = $expiredTime;
			
			//----------------------------------------------------------------------
			//выборка типа корабля
			if($emailType == "issued")
			{
				preg_match("/(?<=\().*(?=\) at a level)/", $emailText, $regs);
				//print_r($regs);
				$shipTypeName = $regs[0];
				//echo "Тип корабля: $shipTypeName<br>";
			}
			else
				$shipTypeName = "";
			$result["shipType"] = $shipTypeName;


			//----------------------------------------------------------------------
			//имя корабля
			$shipName = "";
			if($emailType == "issued")
			{
				preg_match("/(?<=for your ship, ).+(?= \($shipTypeName\))/", $emailText, $regs);
				//print_r($regs);
				$shipName = $regs[0];
			}
			if($emailType == "expired")
			{
				preg_match("/(?<=of the ship ).+(?= issued at)/", $emailText, $regs);
				//print_r($regs);
				$shipName = $regs[0];
			}
			//echo "Имя корабля: $shipName<br>";
			$result["shipName"] = $shipName;
			
			//----------------------------------------------------------------------
			//получение суммы страховки
			if($emailType == "insurance")
			{
				preg_match("/(?<=has transferred ).+(?= ISK into)/", $emailText, $regs);
				//print_r($regs);
				$summ = str_replace(",", "", $regs[0]);
				//echo "Выплаченная страховка: $summ<br>";
			}
			else
				$summ = "";
			$result["summ"] = $summ;
			
			$emailHash = md5($emailText);
			$query = sprintf("insert ignore into api_insurance_emails set ".
				"recordId = '%s', accountId = '%s', refId = %d, ".
				"emailTime = '%s', emailType = '%s', ".
				"issuedTime = '%s', expiredTime = '%s', ".
				"shipTypeName = '%s', shipName = '%s', ".
				"insuranceISK = %d, emailText = '%s', hashtext = '%s';",
				GetUniqueId(), $accountId, $emailRefId,
				$emailDate, $emailType,
				$issuedTime, $expiredTime,
				mysql_escape_string($shipTypeName), mysql_escape_string($shipName),
				$summ, base64_encode($emailText), $emailHash);

			//$db = OpenDB2();
			$db->query($query);
			$result["affected_rows"] = $db->affected_rows;
			$result["query"] = $query;
			$result["emailText"] = $emailText;
			//$db->close();
			
			return $result;
		}
		function AddToInsuranceList($db, $accountId, $parsedInfo)
		{
			/*
			 * $parsedInfo["type"]
			 * $parsedInfo["date"]
			 * $parsedInfo["refId"]
			 * $parsedInfo["issued"]
			 * $parsedInfo["expired"]
			 * $parsedInfo["shipType"]
			 * $parsedInfo["shipName"]
			 * $parsedInfo["summ"]
			 * 
			 * id записи, id аккаунта
			 * refId, status, start, end, isk, shipType, shipName
			 *
			 * issued: refId, status=issued, start, end, isk='', shipType, shipName
			 * insurance: refId, status=insurance, start='', end='', isk='000', shipType='', shipName=''
			 * expired: refId, status=expired, start, end, isk='', shipName
			 *
			 * issued:
			 * вставить refId, статус, начало, конец, тип корабля, имя корабля
			 * обновить только начало, тип корабля, если начало страховки не раньше 13 недель от конца
			 *
			 * insurance:
			 * вставить refId, статус, конец, сумму выплаты
			 * обновить только статус, конец, сумму выплаты
			 *
			 * expired:
			 * вставить refId, статус, начало, конец, имя корабля
			 * обновить только статус, начало, конец, имя корабля
			 */
			if($parsedInfo["type"] == "issued")
			{
				$queryInsert = sprintf("insert ignore into api_insurance_list set ".
					"recordId = '%s', accountId = '%s', ".
					"refId = %d, status = '%s', ".
					"insuranceStart = '%s', insuranceEnd = '%s', ".
					"shipTypeName = '%s', shipName = '%s';",
					GetUniqueId(), $accountId,
					$parsedInfo["refId"], $parsedInfo["type"],
					$parsedInfo["issued"], $parsedInfo["expired"],
					mysql_escape_string($parsedInfo["shipType"]), base64_encode($parsedInfo["shipName"]));

				$queryUpdate = sprintf("update api_insurance_list set ".
					"insuranceStart = '%s', ".
					"shipTypeName = '%s', shipName = '%s' ".
					"where accountId = '%s' and refId = %d and (insuranceEnd between '%s' and adddate('%s', interval 13 week));",
					$parsedInfo["issued"],
					mysql_escape_string($parsedInfo["shipType"]), base64_encode($parsedInfo["shipName"]),
					$accountId, $parsedInfo["refId"], $parsedInfo["issued"], $parsedInfo["issued"]);
			}

			if($parsedInfo["type"] == "insurance")
			{
				$queryInsert = sprintf("insert ignore into api_insurance_list set ".
					"recordId = '%s', accountId = '%s', ".
					"refId = %d, status = '%s', ".
					"insuranceEnd = '%s', insuranceISK = %d;",
					GetUniqueId(), $accountId,
					$parsedInfo["refId"], $parsedInfo["type"],
					$parsedInfo["expired"], $parsedInfo["summ"]);
				$queryUpdate = sprintf("update api_insurance_list set ".
					"status = '%s', ".
					"insuranceEnd = '%s', insuranceISK = %d ".
					"where accountId = '%s' and refId = %d and (insuranceStart between adddate('%s', interval -13 week) and '%s');",
					$parsedInfo["type"],
					$parsedInfo["expired"], $parsedInfo["summ"],
					$accountId, $parsedInfo["refId"], $parsedInfo["expired"], $parsedInfo["expired"]);
			}

			if($parsedInfo["type"] == "expired")
			{
				$queryInsert = sprintf("insert ignore into api_insurance_list set ".
					"recordId = '%s', accountId = '%s', ".
					"refId = %d, status = '%s', ".
					"insuranceStart = '%s', insuranceEnd = '%s', ".
					"shipName = '%s';",
					GetUniqueId(), $accountId,
					$parsedInfo["refId"], $parsedInfo["type"],
					$parsedInfo["issued"], $parsedInfo["expired"],
					base64_encode($parsedInfo["shipName"]));

				$queryTest = sprintf("select * from api_insurance_list ".
					"where accountId = '%s' and refId = %d and (insuranceStart between adddate('%s', interval -13 week) and '%s');",
					$accountId, $parsedInfo["refId"], $parsedInfo["expired"], $parsedInfo["expired"]);
				$qr = $db->query($queryTest);
				$row = $qr->fetch_assoc();
				if($row["insuranceISK"] > 0)
					$shipKilled = true;
				else
					$shipKilled = false;
				$qr->close();

				if($shipKilled == false)
				{
					$queryUpdate = sprintf("update api_insurance_list set ".
						"status = '%s', ".
						"insuranceStart = '%s', insuranceEnd = '%s', ".
						"shipName = '%s' ".
						"where accountId = '%s' and refId = %d and (insuranceStart between adddate('%s', interval -13 week) and '%s');",
						$parsedInfo["type"],
						$parsedInfo["issued"], $parsedInfo["expired"],
						base64_encode($parsedInfo["shipName"]),
						$accountId, $parsedInfo["refId"], $parsedInfo["expired"], $parsedInfo["expired"]);
				}
				else
				{
					$queryUpdate = "";//select * from api_insurance_list where 0;";
				}
			}

			$result = array();
			$result["insert"] = $queryInsert;
			$result["update"] = $queryUpdate;

			if($queryInsert != "")
			{
				$db->query($queryInsert);
				$result["inserted"] = $db->affected_rows;
			}

			if($queryUpdate != "")
			{
				$db->query($queryUpdate);
				$result["updated"] = $db->affected_rows;
			}

			return $result;
		}
	}
?>
