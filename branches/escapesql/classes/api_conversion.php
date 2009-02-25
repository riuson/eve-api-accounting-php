<?php
    include_once("api2.php");
    include_once("database.php");

	class Api_Conversion
	{
		var $id;
		var $name;

		public function __construct()
		{
			$this->id = 797400947;
			$this->name = "CCP Garthagk";
		}

		public function PreProcess($page)
		{
			if(isset($_POST["id2name"]))
			{
				$this->id = $_POST["id2name"];
				//если это число
				if(preg_match("/^\d+$/i", $this->id) != 0)
				{
					$Api = new ApiInterface("");
					$res = $Api->GetNameById($this->id);
					$this->name = $res["name"];
					//print_r($res);
				}
				else
				{
					$this->name = "";
				}
			}
			if(isset($_POST["name2id"]))
			{
				$this->name = $_POST["name2id"];
				
				//if(preg_match("/^\w+$/i", $this->name) != 0)
				{
					$Api = new ApiInterface("");
					$res = $Api->GetIdByName($this->name);
					$this->id = $res["id"];
					//print_r($res);
				}
			}

			$page->Body = 
"<form name='idtoname' method='post' action='$_SERVER[PHP_SELF]?mode=api_conversion'>
	<fieldset>
		<legend>Преобразование id в имя</legend>
		<label for='id2name'>Id персонажа</label>
		<input type='text' id='id2name' name='id2name' maxlength='10' size='10' value='$this->id'>
		<button name='id2name_submit' type='submit'>OK</button>
	</fieldset>
</form>";
			$page->Body .= 
"<form name='nametoid' method='post' action='$_SERVER[PHP_SELF]?mode=api_conversion'>
	<fieldset class='.b-border'>
		<legend class='.b-border'>Преобразование имени в id</legend>
		<label for='name2id'>Имя персонажа</label>
		<input type='text' id='name2id' name='name2id' maxlength='255' size='10' value='$this->name'>
		<button name='name2id_submit' type='submit'>OK</button>
	</fieldset>
</form>";
			$page->Body .= 
"Результат: characterId = $this->id, name = $this->name<br>
<img src='http://img.eve.is/serv.asp?s=256&amp;c=$this->id' alt='Портрет $this->name'>";
		}
	}
?>
