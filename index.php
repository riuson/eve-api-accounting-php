<?php
	ini_set('display_errors',1);
	error_reporting(E_ALL);
	//ini_alter("session.use_cookies","1"); 
    include("classes/page.php");
    //header("Content-type: text/html; charset=utf-8");

    /*session_start();
    session_destroy();
    session_start();*/
    if (isset($_REQUEST[session_name()])) session_start();
    if (isset($_SESSION['UserIp']) AND $_SESSION['UserIp'] != $_SERVER['REMOTE_ADDR']) session_destroy();

	$page = new Page();

	$page->PreProcess();

	$page->WriteHtml();
	//print_r($_COOKIE);
	//print_r($_SESSION);
	//print_r($_SESSION);
	//phpinfo();
	//$page->WriteHeader();
	//$page->WriteCaption();

	//$page->Process();
	//echo "<font style=\"LAYOUT-FLOW: vertical-ideographic\">text</font>";
	//$page->WriteFooter();
	//phpinfo();
?>
