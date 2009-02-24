<?php
    //$db_link = mysql_connect($host, $user, $password);
    //mysql_close($dblink);
    function GetUniqueId()
    {
        srand((double) microtime() * 1000000);
        $uniq_id = uniqid(rand(), true);
        return $uniq_id;
    }
	//http://web-tribe.net/one_news/518.html
	function RC4($keyString, $data)
	{		//ecncrypt $data with the key in $keyfile with an rc4 algorithm
		//$pwd = implode('', file($keyfile));
		$pwd = $keyString . "jkf*(Y*(#%uioHO9H890AH5IOUNUIOH90JHGIOVN IOHFG8OEY89huiniojgerioj ;ojioJHIOTJ(Pui56'4JV9u(*)u#hiojngngIOHJGNi#nignioehOIN3KMNFGDHNFGIO:nik#niogHIOegnKLen GL4INGIO4NGJHNVIOEJ ERG45ERt5%c45R5%5^&j4j&u5JKh guiHruiohtOI4Y8YTH :o ngOIJHGIRJGKLMNKLMoijmopiJ()Jtopi jiopJIOPTJMop4jtyopmkgk;lmkl;fmhk;rjij(_U*()U^(PRJKGp jIO J RGio RJHT(UJ() :TUJTU(jv4otj9 JTO(vji o;IJTYJ VTP?mo9vjPTRMgiojm ropgjropiuT()UpUJ^V(PJITG) JOgpoij(PUT<V(_IUT9IKRUITUPTU (PGJp9weriHG oERHGoiWHGO?WHGIO?hIO HTOU*Hou8 hO:*Y?TEH*G()ut'0U)U(R#PJKLHIO WEHRLE*F eo;ej8iejfsejgposet/p)TUJ(UJTopi4jtw4'J G jGpwj'GP4jw9tjow49ptjuw490TJ:)U(T:)49";
		$pwd_length = strlen($pwd);
		$x = 0;
		$a = 0;
		$j = 0;
		$Zcrypt = "";
		for ($i = 0; $i < 256; $i++)
		{
			$key[$i] = ord(substr($pwd, ($i % $pwd_length)+1, 1));
			$counter[$i] = $i;
		}
		//print_r($counter);
		for ($i = 0; $i < 256; $i++)
		{
			$x = ($x + $counter[$i] + $key[$i]) % 256;
			$temp_swap = $counter[$i];
			$counter[$i] = $counter[$x];
			$counter[$x] = $temp_swap;
		}
		for ($i = 0; $i < strlen($data); $i++)
		{
			$a = ($a + 1) % 256;
			$j = ($j + $counter[$a]) % 256;
			$temp = $counter[$a];
			$counter[$a] = $counter[$j];
			$counter[$j] = $temp;
			$k = $counter[(($counter[$a] + $counter[$j]) % 256)];
			$Zcipher = ord(substr($data, $i, 1)) ^ $k;
			$Zcrypt .= chr($Zcipher);
		}
		return $Zcrypt;
	}
	function OpenDB2()
	{
		include "constants.php";
		$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);

		if (mysqli_connect_errno())
		{
			printf("Подключение к серверу MySQL невозможно. Код ошибки: %s\n", mysqli_connect_error()); 
			exit; 
		} 
		else
		{
			//$db_link->set_charset("utf8");
		}
		return $mysqli;
	}
    function LogVisitor()
    {
    	$recordId = GetUniqueId();
    	//$dt = date_create();
        //$t = date_timezone_get($dt);
        //$t = timezone_offset_get($t , date_create());
        date_default_timezone_set("Etc/Universal");
        //echo(date_default_timezone_get());
        $t = time();// - $t;
        $strtime = date("Y-m-d H:i:s", $t);
        //проверка, не этот ли юзер заходил в последний раз
        $query = "select * from api_visitors where _date_ in (select max(_date_) from api_visitors);";
        $db = OpenDB2();
        $qr = $db->query($query);
        if($row = $qr->fetch_assoc())
        {
			if($row["ip"] != $_SERVER["REMOTE_ADDR"] || $row["userAgent"] != $_SERVER["HTTP_USER_AGENT"])
			{
				$query = sprintf("insert into api_visitors values ('%s', '%s', '%s', '%s');",
					$db->real_escape_string($recordId),
					$db->real_escape_string($strtime),
					$db->real_escape_string($_SERVER["REMOTE_ADDR"]),
					$db->real_escape_string($_SERVER["HTTP_USER_AGENT"]));
				//echo ($query);
				//print("<br/>$strtime");
				$db->query($query);
			}
		}
		else
		{
			$query = sprintf("insert into api_visitors values ('%s', '%s', '%s', '%s');",
				$db->real_escape_string($recordId),
				$db->real_escape_string($strtime),
				$db->real_escape_string($_SERVER["REMOTE_ADDR"]),
				$db->real_escape_string($_SERVER["HTTP_USER_AGENT"]));
			//echo ($query);
			//print("<br/>$strtime");
			$db->query($query);
		}
		$db->close();
    }
?>
