<?php
	include_once("database.php");
	include_once("user.php");
	class Logout
	{
		public function PreProcess()
		{
			//session_start();
			if(isset($_SESSION["User"]))
			{
				$User = $_SESSION["User"];
				$User->DestroySession(session_id());
				unset($_SESSION["User"]);
			}
			if (isset($_COOKIE[session_name()]))
			{
				setcookie(session_name(), '', time()-42000);
				setcookie(session_name(), '', time()-42000, "/");
			}
			if (isset($_REQUEST[session_name()]))
				session_destroy();
			header("Location:$_SERVER[PHP_SELF]");
		}
	}
?>
