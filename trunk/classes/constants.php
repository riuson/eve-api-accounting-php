<?php
    if($_SERVER["SERVER_NAME"] == "rius.pvt.454.ru" || $_SERVER["SERVER_NAME"] == "localhost" || $_SERVER["SERVER_NAME"] == "62.182.104.23" || $_SERVER["SERVER_NAME"] == "10.2.21.9")
    {
        $db_host = "localhost";
        //echo(" local version ");
    }
    else
    {
        $db_host = "localhost";
        //echo(" web version ");
    }
    //echo($db_host);
    $db_user = "sleephost_ea";
    $db_pass = "TdfNhbybnb";
    $db_name = "sleephost_ea";
    $dcapicode = "4r8731tsnb";
?>
