<?php
    include_once("api2.php");
    include_once("database.php");

	class Information
	{
		public function PreProcess($page)
		{
			$page->Body = 
"
<p>Проект для получения и просмотра данных с сервера EVE API</p>
<p>Использованная информация:</p>
<ul>
	<li><a href='http://myeve.eve-online.com/api/doc/default.asp'>EVE API Documentation Index</a></li>
	<li><a href='http://wiki.eve-id.net/'>EVE-Development Network</a></li>
	<li><a href='http://bughunters.addix.net/igbtest/IGB-commands.html'>EVE Ingame Webbrowser preliminary documentation</a></li>
</ul>
<p>Предыдущая версия программного обеспечения была написана на C# .Net, с базой данных MS Access.
Программа с исходными кодами и файлом БД выложена <a href='http://code.google.com/p/eve-api-accounting/'>здесь</a>.</p>
";
		}
	}
?>
