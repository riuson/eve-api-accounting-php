<?php
    include_once("api2.php");
    include_once("database.php");
    include_once "pageselector.php";

	class WhoIs
	{
		public function PreProcess($page)
		{
			if(isset($_REQUEST["ip"]))
			{
				$ip = $_REQUEST["ip"];
				if(preg_match("/^([\d]{1,3}\.){3}[\d]{1,3}$/", $ip))
				{
					$page->Body = $this->GetInfo("whois.arin.net", $ip);
				}
			}
		}
		function GetInfo($url, $ip)
		{
			// Соединение с сокетом TCP, ожидающим на сервере "whois.arin.net" по 
			// 43 порту. В результате возвращается дескриптор соединения $sock.
			$sock = fsockopen($url, 43, $errno, $errstr);
			if (!$sock)
				exit("$errno($errstr)");
			else
			{
				//echo $url."<br>";
				// Записываем строку из переменной $_POST["ip"] в дескриптор сокета.
				fputs ($sock, $ip."\r\n");
				// Осуществляем чтение из дескриптора сокета.
				$text = "";
				while (!feof($sock))
				{
					$text .= fgets ($sock, 128)."<br>";
				}
				// закрываем соединение
				fclose ($sock);

				// Ищем реферальный сервере
				$pattern = "|ReferralServer: whois://([^\n<:]+)|i";
				preg_match($pattern, $text, $out);
				if(!empty($out[1])) return $this->GetInfo($out[1], $ip);
					else return $text;
			}
		}
	}
?>
