<?php
	require_once 'C:/downloads/smarty-3.1.27/libs/Smarty.class.php';
	//Создадим объект класса Smarty
	$smarty = new Smarty();
	$name = 'Pavel';
	//Передаем переменную в шаблонизатор Smarty
	$smarty->assign('name',$name="DEFAULT");
	//Выводим шаблон на экран
	$smarty->display('C:/downloads/smarty-3.1.27/demo/templates/index.tpl');

	function CollectCols($sData)
	{
		# ff
	}


	$aCreatedDB = array();
	$sqlCreatedDB = "SELECT table_schema
					 FROM information_schema.`TABLES`
					 GROUP BY table_schema";
	$sqlCreatedDBResult = QDBC::resultAssoc($sqlCreatedDB, true);
	if($sqlCreatedDBResult && count($sqlCreatedDBResult))
	{
		foreach ($sqlCreatedDBResult as $databases)
		{
			$aCreatedDB[] = $databases['table_schema'];
		}
	}

	// $link = mysql_connect('http://asupb.ru:22', 'asupb', 'qwa1181' );
	// if (!$link)
	// {
 //    die('Ошибка соединения: ' . mysql_error());
	// }
	// echo 'Успешно соединились';
	// mysql_close($link);

?>
<html>
	<head>

	</head>
	<body>
		<form method="POST" action="" >
			<select name="databases">
				<?php
					foreach ($aCreatedDB as $value) {
						echo "<option selected='" . $_POST['databases'] . "' value='$value'>$value</option>";
					}
				?>
			</select>
			<select name="databases2diff">
				<?php
					foreach ($aCreatedDB as $value) {
						echo "<option selected='" . $_POST['databases'] . "' value='$value'>$value</option>";
					}
				?>
			</select>
			<input type="submit" name="go" value="Сравнить выбранные БД">
		</form>
	</body>
</html>

<?php
	# Сделать это через функции
	if(!empty($_POST['go']))
	{
		for ($flag=0; $flag < 2; $flag++)
		{
			$sColName = "";
			$iLength = 0;
			$iUnsigned = 0;
			$iZerofill = 0;
			$title = "";
			$res = "";

			$aDefaultsGlobal["tinyint"] =   array("length"=> "1",   "unsigned"=>"0", "zero"=>"0",  "null" => "0", "default" => "0",            "comment" => "");
			$aDefaultsGlobal["smallint"] =  array("length"=> "1",   "unsigned"=>"0", "zero"=>"0",  "null" => "0", "default" => "0",            "comment" => "");
			$aDefaultsGlobal["int"] =       array("length"=> "10",  "unsigned"=>"0", "zero"=>"0",  "null" => "0", "default" => "0",            "comment" => "");
			$aDefaultsGlobal["bigint"] =    array("length" => "20", "unsigned"=>"0", "zero"=>"0",  "null" => "0", "default" => "0",            "comment" => "");
			$aDefaultsGlobal["bit"] =       array("length" => "1",  "unsigned"=>"0", "zero"=>"0",  "null" => "0", "default" => "0",            "comment" => "");

			$aDefaultsGlobal["float"] =     array(                  "unsigned"=>"0", "zero"=>"0",  "null" => "0", "default" => "NULL",         "comment" => "");
			$aDefaultsGlobal["double"]=     array(                  "unsigned"=>"0", "zero"=>"0",  "null" => "0", "default" => "NULL",         "comment" => "");

			$aDefaultsGlobal["char"] =      array("length" => "1",                                 "null" => "0", "default" => "NULL",         "comment" => "");
			$aDefaultsGlobal["varchar"] =   array("length" => "255",                               "null" => "0", "default" => "NULL",         "comment" => "");
			$aDefaultsGlobal["tinytext"] =  array(                                                 "null" => "0",                              "comment" => "");
			$aDefaultsGlobal["text"] =      array(                                                 "null" => "0",                              "comment" => "");
			$aDefaultsGlobal["mediumtext"]= array(                                                 "null" => "0",                              "comment" => "");
			$aDefaultsGlobal["longtext"]=   array(                                                 "null" => "0",                              "comment" => "");

			$aDefaultsGlobal["varbinary"] = array("length" => "50",                                "null" => "0", "default" => "NULL",         "comment" => "");
			$aDefaultsGlobal["longblob"] =  array(                                                 "null" => "0", "default" => "NULL",         "comment" => "");

			$aDefaultsGlobal["date"] =      array(                                                 "null" => "0", "default" => "NULL",         "comment" => "");
			$aDefaultsGlobal["time"] =      array(                                                 "null" => "0", "default" => "NULL",         "comment" => "");
			$aDefaultsGlobal["datetime"] =  array(                                                 "null" => "0", "default" => "NULL",         "comment" => "");
			$aDefaultsGlobal["timestamp"] = array(                                                 "null" => "0", "default" => "NULL",         "comment" => "");


			$aDefaultsGlobal["id"] =        array( "length" => "10", "increment"=> "1");

			$aColParams = array();


			if ($flag == 0)
			{
				$database_name = $_POST['databases'];
			}
			else
			{
				$database_name = $_POST['databases2diff'];
			}
			echo "# Начало формирования xml файла (" . date('d-m-Y H:i:s') . ")<br>";
			$sql="SELECT table_name FROM information_schema.tables WHERE table_schema = '$database_name';"; // получаем список всех таблиц
			// echo $sql . "<br>";
			$rResult = mysql_query($sql);
			while($sTable = mysql_fetch_array($rResult))
			{
				$aTables[] = $sTable[0];			// Массив с таблицами
			}
			foreach ($aTables as $key => $table)
			{
				$sql = "SELECT column_name
						, column_type
						#, ordinal_position
						, data_type
						, is_nullable
						, column_default
						#, column_key
						, extra
						, column_comment
						FROM information_schema.columns WHERE table_schema = '$database_name' AND table_name = '$table' ;";
				$rResult = QDBC::mysql_query($sql);
				while($sRes = QDBC::mysql_fetch_assoc($rResult))
				{
					$aaaColumns[$table][] = $sRes;		// Двумерный массив который состоит ключ → (название таблицы), значение → (вся остальная информация)
				}
			}
			unset($aTables);		# Удаляем массив, т.к. он записывается по второму кругу.
				$res = ""; 		// итоговый результат, который будет записан в файл
				foreach ($aaaColumns as $keyTableName => $aaColumns)
				{
					$sqlTableComment = "SELECT table_name, table_comment, table_collation, engine FROM information_schema.tables WHERE table_schema = '$database_name' AND table_name = '$keyTableName';";
					$aSqlTableComment = QDBC::resultAssoc($sqlTableComment, true);
					$res .= str_repeat("\t", 3) . "<tbl name=\""
						 . htmlspecialchars($aSqlTableComment[0]['table_name']) . "\" comment=\""
						 . htmlspecialchars($aSqlTableComment[0]['table_comment']) . "\" >\n";
					$res .=  str_repeat("\t", 4) . "<cols " . ((1)?(""):(""))  .">\n";  // контейнер для полей
					foreach ($aaColumns as $aColumns)
					{
						$res .= str_repeat("\t", 5) . "<col ";		// контейнер для конкретного поля
						foreach ($aColumns as $keyColumns => $sColumns)
						{
							switch (mb_strtolower($keyColumns))
							{
								case 'column_name':
									$res .= 'name="'. ((is_null($sColumns))?("NULL"):(str_replace("\$", "\\$", htmlspecialchars($sColumns)))) . '" ';
									break;
								case 'column_type':

									preg_match('/^(\w+)(\(?(\d+)?\)?)( )?(unsigned)?( )?(zerofill)?/', $sColumns, $dd);
									$sColName = $dd[1];							# Присвоим контейнерам поиска регулярного выражния
									if (isset($dd[3])) $iLength = (int)$dd[3];		# осмысленные значения.
									if (isset($dd[5])) $iUnsigned = (int)$dd[5];	# $sColName = название типа, $iLength = длина,
									if (isset($dd[7])) $iZerofill = (int)$dd[7];	# $iUnsigned и $iZerofill соответствующие флаги.

									$res .= 'type="'. str_replace("\$", "\\$", htmlspecialchars($sColName)) . '" ';

									if (!empty($iLength) && strlen($iLength)) $aColParams[$sColName]["length"] = $iLength;
									if (!empty($iUnsigned) && strlen($iUnsigned)) $aColParams[$sColName]["unsigned"] = $iUnsigned;
									if (!empty($iZerofill) && strlen($iZerofill)) $aColParams[$sColName]["zero"] = $iZerofill;

									if (isset($aColParams[$sColName]["length"]))
									{
										// printr($aColParams[$sColName]);
										if(isset($aDefaultsGlobal[$sColName]["length"]) && $aColParams[$sColName]["length"] != $aDefaultsGlobal[$sColName]["length"] ) $res .= 'length="'. $iLength . '" ';
									}
									if (isset($aColParams[$sColName]["unsigned"]))
									{
										if($aColParams[$sColName]["unsigned"] != $aDefaultsGlobal[$sColName]["unsigned"] ) $res .= 'unsigned="' . ((empty($iUnsigned)?(0):(1))) . '" ';
									}
									if (isset($aColParams[$sColName]["zero"]))
									{
										if($aColParams[$sColName]["zero"] != $aDefaultsGlobal[$sColName]["zero"] ) $res .= 'zero="' . ((empty($iZerofill)?(0):(1))) . '" ';
									}
									// $res .= 'zero="' . ((empty($iZerofill)?(0):(1))) . '" ';
									break;
								case 'column_default':
									$aColParams[$sColName]["default"] = ((is_null($sColumns))?("NULL"):(str_replace("\$", "\\$", htmlspecialchars($sColumns))));
									if(isset($aDefaultsGlobal[$sColName]["default"]) && $aColParams[$sColName]["default"] != $aDefaultsGlobal[$sColName]["default"])
									{
										$res .= 'default="'. $aColParams[$sColName]["default"] . '" ';
									}
									break;
								case 'column_comment':
									$aColParams[$sColName]["comment"] = ((is_null($sColumns))?("NULL"):(str_replace("\$", "\\$", htmlspecialchars($sColumns))));
									if($aColParams[$sColName]["comment"] != $aDefaultsGlobal[$sColName]["comment"])
									{
										$res .= 'comment="'. $aColParams[$sColName]["comment"]. '" ';
									}
									break;
								case 'is_nullable':
									// $col_name = 'nullable';
									if($sColumns=='YES')
									{
										$aColParams[$sColName]["null"] =  1 ;
									}else
									{
										$aColParams[$sColName]["null"] =  0;
									}
									if($aColParams[$sColName]["null"] != $aDefaultsGlobal[$sColName]["null"])
									{
										$res .= 'null="'. $aColParams[$sColName]["null"] . '" ';
									}
									break;
								default:
									continue;
							}

						}
						// echo "<br> ----- То что получили из базы ------</br>";
						// printr($aColParams);
						// echo "<br> ----- Конец То что получили из базы ------</br>";
						$res .= "/>\n";
					}
					$res .= str_repeat("\t", 4) . "</cols>\n";
						// echo "<br> ----- По умолчанию ------</br>";
						// printr($aDefaultsGlobal);
						// echo "<br> ----- Конец по умолчанию ------</br>";
					// die;
		// начало формирования контейнера с unique key
					$sql = "select GROUP_CONCAT(statistics.column_name SEPARATOR ',') AS cols
								 , index_name
								 , non_unique
							FROM information_schema.statistics
							WHERE table_schema = '$database_name' AND table_name = '$keyTableName'
							GROUP BY index_name
							ORDER BY non_unique;";
// echo $sql;die;
					$rResult = QDBC::mysql_query($sql);
					if($rResult)
					{
						$res .=  str_repeat("\t", 4) . "<indexes>\n";
						$aAllIndexes = array();  // временный массив, который каждый раз буду отчищать  когда будет новая таблица
							while($sRes = QDBC::mysql_fetch_assoc($rResult))
							{
								$aAllIndexes[] = $sRes;
							}
						foreach ($aAllIndexes as $aIndex)
						{
							$res .= str_repeat("\t", 5) . '<index name="' . $aIndex['index_name'] . '" ';
							// if($aIndex['non_unique'] == 0 and $aIndex['index_name'] == 'PRIMARY') $res .= 'type="PRIMARY" ';
							if($aIndex['non_unique'] == 0 and $aIndex['index_name'] != 'PRIMARY') $res .= 'unique="1" ';
							$res .= "col=\"" . $aIndex['cols'] ."\"/>\n";
						}
						$res .=  str_repeat("\t", 4) . "</indexes>\n";
					}
		// конец формирования контейнера с индексами таблицы

					$res .= str_repeat("\t", 3) . "</tbl>\n";	// первый тэг -  название таблицы
				}
				unset($aaaColumns);		# Удаляем массив, т.к. он записывается по второму кругу.
				$title = "<?xml version='1.0' standalone='yes'?>\n\t<db name=\"$database_name\">\n\t\t<defaults>";
				foreach ($aDefaultsGlobal as $key => $value)				# формируем значения defaults ($db->defaults)
				{
					$title .= "\n\t\t\t<col_type type=\"$key\" ";
					foreach ($value as $key2 => $attr)
					{
						$title .=  " $key2=\"$attr\" ";
					}
					$title .= "/>";
				}
				$title .= "\n\t\t</defaults> \n\t\t<tbls>\n";
				echo $title;
				if ($flag == 0)
				{
					$file_in = SYSTEM_ROOT."database.xml";
				}
				else
				{
					$file_in = SYSTEM_ROOT."databaseOld.xml";
				}
					if(!file_exists($file_in));
					file_put_contents($file_in, $title . $res . "\t\t</tbls>\n\t</db>\n");
					unset($aColParams);
					unset($res);
					unset($title);
					//echo nl2br(htmlentities(file_get_contents($file_in)));
				echo "# Формирование файла завершено! (" . date('d-m-Y H:i:s') . ")<br>";
				echo "Чтобы посмотреть запрос на создание базы данных в СУБД MySQL перейдите по <a href='\create_db_on_mysql'>ссылке</a><br>";
				echo "Чтобы посмотреть запрос на создание базы данных в СУБД Oracle перейдите по <a href='\create_db_on_oracle'>ссылке</a> (Пока не работает)<br>";
				echo "Чтобы посмотреть запрос на создание базы данных в СУБД MsSQL перейдите по <a href='\create_db_on_mssql'>ссылке</a> (Пока не работает)<br>";
				echo "Чтобы посмотреть запрос на создание базы данных в СУБД PostgreSQL перейдите по <a href='\create_db_on_postgresql'>ссылке</a> (Пока не работает)<br>";
		}
		include('qdb-prepare.php');
		include('qdb-build.php');
	# Здесь нужно вызвать файл qdb-prepare.php
	}

?>
