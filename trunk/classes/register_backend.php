<?php
	include_once "api2.php";
	//session_start();
	require_once "../lib/JsHttpRequest/JsHttpRequest.php";
	// Init JsHttpRequest and specify the encoding. It's important!
	$JsHttpRequest =& new JsHttpRequest("utf-8");
	// Fetch request parameters.
	if(isset($_REQUEST["function"]) && isset($_REQUEST["userId"]) && isset($_REQUEST["apiKey"]))
	{
		if($_REQUEST["function"] == "getCharsList")
		{
			$userId = $_REQUEST["userId"];
			$apiKey = $_REQUEST["apiKey"];
			
			$api = new ApiInterface("");
			$api->userId = $userId;
			$api->apiKey = $apiKey;
			$res = $api->GetCharactersList();
			//print_r($res);
			if($res["error"] != "")
				echo $res["error"];
			$characters = $res["characters"];
			//print_r($characters);
			$answer = "";
			foreach($characters as $character)
			{
				$answer .= "<option value='$character[characterId]'>$character[characterName]</option>";
			}
			//echo htmlentities( $answer);
			$GLOBALS['_RESULT'] = array
			(
				"function" => ($_REQUEST["function"]),
				"answer"   => $answer
			);
		}
		else
		{
			echo "Неверный формат вызова";
		}
	}

	// Everything we print will go to 'errors' parameter.
	//echo "<pre>";
	//print_r($_SESSION);
	//echo "</pre>";
	// This includes a PHP fatal error! It will go to the debug stream,
	// frontend may intercept this and act a reaction.
	if(isset($_REQUEST['str']))
	{
		if ($_REQUEST['str'] == 'error')
		{
			error_demonstration__make_a_mistake_calling_undefined_function();
		}
	}
?>
