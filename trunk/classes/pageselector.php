<?php
	class PageSelector
	{
		var $recordOnPage = 50;
		var $currentIndex;
		var $uri;
		public $start;
		public $count;

		//конструктор получает выбраную страницу из запроса
		public function __construct()
		{
			//адрес этой страницы
			$this->uri = $_SERVER["REQUEST_URI"];

			//получение ранее выбранного индекса из запроса
			if(isset($_REQUEST["page"]))
			{
				$this->currentIndex = $_REQUEST["page"];
				//если этот параметр вдруг не число, обнуляем
				if(eregi("^[0-9]+$", $this->currentIndex) == false)
				{
					$this->currentIndex = 0;
				}
			}
			else//иначе 0 по умолчанию
			{
				$this->currentIndex = 0;
				//и добавляем в адрес
				//если есть знак вопроса, значит в строке что-то было передано уже
				if(stripos($this->uri, "?") != false)
					$this->uri = $this->uri . "&amp;page=0";
				else
					$this->uri = $this->uri . "?page=0";
			}
			$this->start = $this->recordOnPage * $this->currentIndex;
			$this->count = $this->recordOnPage;
		}
		public function Write($recordsCount)
		{
			$pagesCount = ceil($recordsCount / $this->recordOnPage);
			$result = "";

			//если меньше 10 страниц, показывать все
			if($pagesCount < 10)
			{
				for($i = 0; $i < $pagesCount; $i ++)
				{
					$string = $this->uri;//"April 15, 2003";
					$pattern = "/(^.*)(page=\w+)(.*$)/i";//"/(\w+) (\d+), (\d+)/i";
					$replacement = "\${1}page=$i\$3";
					$link = preg_replace($pattern, $replacement, $string);
								
					if($this->currentIndex == $i)
						$addstr = "<b>$i</b>";
					else
						$addstr = "<a href='$link'>$i</a>";
					if($result != "")
						$result = $result . ", ";
					$result = $result . "$addstr";
				}
			}
			else//иначе
			{
				$a = 3;
				if($this->currentIndex > $a)
				{
					$i = 0;
					$string = $this->uri;//"April 15, 2003";
					$pattern = "/(^.*)(page=\w+)(.*$)/i";//"/(\w+) (\d+), (\d+)/i";
					$replacement = "\${1}page=$i\$3";
					$link = preg_replace($pattern, $replacement, $string);
								
					if($this->currentIndex == $i)
						$addstr = "<b>$i</b>";
					else
						$addstr = "<a href='$link'>$i</a>";
					if($result != "")
						$result = $result . ", ";
					$result = $result . "$addstr ... ";
				}

				{
					for($i = $this->currentIndex - $a; $i < $this->currentIndex + $a + 1; $i ++)
					{
						if($i < 0) continue;
						if($i >= $pagesCount) break;
						$string = $this->uri;//"April 15, 2003";
						$pattern = "/(^.*)(page=\w+)(.*$)/i";//"/(\w+) (\d+), (\d+)/i";
						$replacement = "\${1}page=$i\$3";
						$link = preg_replace($pattern, $replacement, $string);
									
						if($this->currentIndex == $i)
							$addstr = "<b>$i</b>";
						else
							$addstr = "<a href='$link'>$i</a>";
						if($result != "")
							$result = $result . ", ";
						$result = $result . "$addstr";
					}
				}

				if($this->currentIndex < $pagesCount - $a - 1)
				{
					$i = $pagesCount - 1;
					$string = $this->uri;//"April 15, 2003";
					$pattern = "/(^.*)(page=\w+)(.*$)/i";//"/(\w+) (\d+), (\d+)/i";
					$replacement = "\${1}page=$i\$3";
					$link = preg_replace($pattern, $replacement, $string);
								
					if($this->currentIndex == $i)
						$addstr = "<b>$i</b>";
					else
						$addstr = "<a href='$link'>$i</a>";
					if($result != "")
						$result = $result . ", ";
					$result = $result . "... $addstr";
				}


			}
			if($recordsCount - $this->start > $this->recordOnPage)
				$this->count = $this->recordOnPage;
			else
				$this->count = $recordsCount - $this->start;
			$last = $this->start + $this->count;

			$result = "<div class='b-pager'>Страницы: " . $result . "<br>Показаны записи $this->start...$last</div>";

			return $result;
		}
	}
	/*
	 * постраничный просмотр.
	 * вывести 7 страниц подряд.
	 * 1 ... 8 9 10 11 12 13 14 ... 50
	 * 1 2 3 4 5 6 7 ... 50
	 * 1 ... 44 45 46 47 48 49 50
	 */
?>
