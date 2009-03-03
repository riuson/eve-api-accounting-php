<?php
    class Api_AccountBalance
    {
    	var $balances;
    	var $corpInfo;

        function PreProcess($page)
        {
        	$User = User::CheckLogin();
        	$User->CheckAccessRights(get_class($this), true);

			$Api = new ApiInterface($User->GetAccountId());
			$Api->userId = $User->GetUserId();
			$Api->apiKey = $User->GetApiKey();
			$Api->characterId = $User->GetCharacterId();
			
			$accountId = $User->GetAccountId();

			$message = "";
			//только мастер может обновлять свой кошелёк
        	if($User->parameters["master"] == "")
        	{
        		//$this->balances = $Api->UpdateAccountBalances();
        		//обновление отсюда отключено, перенесено в планировщик
        		//$message = $this->balances["error"];
			}
			//if($this->balances["success"] == true)
			{
				$this->corpInfo = $Api->GetCorpCorporationSheet();

				//$User = User::CheckLogin();
				//$accountId = $User->GetAccountId();
				$query = "select * from api_account_balance where accountId = '$accountId' order by accountKey;";
				$db = OpenDB2();

				//загрузка данных в случае, если кошельки никогда ранее не загружались
				if($User->parameters["master"] == "")
				{
					$qr = $db->query("select count(*) as _count_ from api_account_balance where accountId = '$accountId';");
					$row = $qr->fetch_assoc();
					$count = $row["_count_"];
					$qr->close();
					
					if($count < 7)
						$this->balances = $Api->UpdateAccountBalances();
				}

				$qr = $db->query($query);

				$page->Body = "<br>
<table class='b-border b-widthfull' cellspacing='1' cellpadding='1'>
<tr class='b-table-caption'>
	<td class='b-center'>accountKey</td>
	<td class='b-center'>accountName</td>
	<td class='b-right'>balance</td>
	<td class='b-center'>balance updated</td>
</tr>";
				$index = 0;
				$wallets = $this->corpInfo["walletDivisions"];
				//print_r($wallets);
				while($row = $qr->fetch_assoc())
				{
					//print_r($qr);					if($index  % 2 == 1)
						$page->Body .= "<tr class='b-row-even'>";
					else
						$page->Body .= "<tr class='b-row-odd'>";
					$index++;

					$page->Body .= "<td class='b-center'>$row[accountKey]</td>";
					$walletName = $wallets[$row["accountKey"]];
					$page->Body .= "<td class='b-center'>" . $walletName . "</td>";

					$balance = number_format($row["balance"], 2, ",", " ");
					$balance = str_replace(" ", "&nbsp;", $balance);
					$page->Body .= "
<td class='b-right'>$balance</td>
<td class='b-center'>$row[balanceUpdated]</td>
</tr>";
				}
				$page->Body .= "</table>";
				//echo "<form method='post'>";
				//echo "<input type='submit' value='Обновить данные с сервера'>";
				//echo "<input type='hidden' name='do_update' value='do_update'>";
				//echo "</form>";
				//$page->Body .= $this->balances["message"];
				$qr->close();

				//$this->ProcessSubscribe($dblink, $accountId);

				$db->close();
			}
			//else
			{
				$page->Body .= $this->balances["error"];
			}
		}
		function ProcessSubscribe($db, $accountId)
		{
			include_once "classes/user.php";
			include_once "classes/subscribes.php";
			include_once "classes/api_corporationsheet.php";
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
				$query = "select * from api_account_balance where accountId = '$accountId' order by accountKey;";
				$qr = $db->query($query);

				$message = "
<html>
<head>
<title>Состояние кошельков корпорации $corpInfo[corporationName]</title>
</head>
<body><p>Состояние кошельков корпорации <b>$corpInfo[corporationName]</b>:</p>
<table bordercolor='silver' border='1' cellspacing='1' cellpadding='1'>
<tr bgcolor='#808080'>
	<td align='center' valign='middle'>accountKey</td>
	<td align='center' valign='middle'>accountName</td>
	<td align='center' valign='middle'>balance</td>
	<td align='center' valign='middle'>balance updated</td>
</tr>";
				$index = 0;
				$wallets = $corpInfo["walletDivisions"];
				while($row = $qr->fetch_assoc())
				{
					$message .= "<tr>";

					$message .= "<td align='center' valign='middle'>$row[accountKey]</td>";
					$walletName = $wallets[$row["accountKey"]];
					$message .= "<td align='center' valign='middle'>" . $walletName . "</td>";

					$balance = number_format($row["balance"], 2);
					$message .= "
<td align='center' valign='middle'>$balance</td>
<td align='center' valign='middle'>$row[balanceUpdated]</td>
</tr>";
				}
				$message .= "</table><br>";
				$link = "http://ea.mylegion.ru/index.php?mode=" . get_class($this);
				$message .= "<a href='$link'>$link</a>";
				$message .= "</body></html>";
				$qr->close();

				$subject = "Состояние кошельков корпорации $corpInfo[corporationName]";

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
					}
				}
				$qr->close();
			}
		}
    }
?>
