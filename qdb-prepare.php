<html>
	<head>
		<meta charset="utf-8">
		<title>Формирование changeset</title>
	</head>
	<body>
	</body>
</html>
<?php
	$db = simplexml_load_file("database.xml");
	$dbOld = simplexml_load_file("databaseOld.xml");
	$sResultStr = "";
	$sResultAddTable = "";
	$sResultDropTable = "";
	$sResultAddDropCol = "";
	$sResultAddIndex = "";

	$aResultAddCol = array(); # массив с атрибутами добавленого поля
	$aResultAddIndex = array(); # массив с атрибутами добавленого индекса

	# ФОРМИРУЕМ МАССИВ СО НАЧЕНИЯМИ ПО УМОЛЧАНИЮ ДЛЯ ПОСЛЕДУЮЩЕГО MERGE
#---------------------------------------------------------------------------------------------
	$aColDefault = array();
	foreach ($db->defaults->col_type as $sColAttr )
	{
		$sTypeName = (string)$sColAttr['type'];
		foreach ($sColAttr->attributes() as $key => $value)
		{
 			$aColDefault["$sTypeName"]["$key"] =  "$value";
		}
	}
#---------------------------------------------------------------------------------------------


	function MergeWithDefault($action, $sKey4Default, $aData) 		# Функция мержа атрибутов полей со значениями по умолчанию
	{
		global $aColDefault;
		return array_merge(array("action" => "$action"), $aColDefault[$sKey4Default], $aData);
	}



	function createTable($aCreateTables)		# Функция по созданию объекта для cteate-кода
	{
		$sResultTable = "";
		$sResultTable .= "\t<create tbl=\"" . $aCreateTables->attributes()->name . "\" comment=\"" . $aCreateTables->attributes()->comment . "\" >";
		$sResultTable .= "\n\t\t<cols>";
		foreach ($aCreateTables->cols->col as $col_name) # Цикл по полям новой таблицы
		{
			$sColType = (string)$col_name->attributes()->type;	# Плучаем тип поля чтобы отправить это поля для мержа с дефолтом
			$aCreateTab = array(); # Временный массив для записи имеющихся атрибутов поля
			foreach ($col_name->attributes() as $key => $value) # Цик по атрибутам
			{
				$aCreateTab[$key] = (string)$value;
			}
			$aCreateTab = MergeWithDefault("create", $sColType, $aCreateTab); # Возвращает смерженный массив атрибутов поля


			$sResultTable .= "\n\t\t\t<col name=\"" . $aCreateTab['name'] . "\" type=\"" . $aCreateTab['type'] . "\" ";
			if(isset($aCreateTab['length'])) $sResultTable .= " length=\"" . $aCreateTab['length'] . "\"";
			if(isset($aCreateTab['unsigned'])) $sResultTable .= " unsigned=\"" . $aCreateTab['unsigned'] . "\"";
			if(isset($aCreateTab['zero'])) $sResultTable .= " zero=\"" . $aCreateTab['zero'] . "\"";
			if(isset($aCreateTab['null'])) $sResultTable .= " null=\"" . $aCreateTab['null'] . "\"";
			if(isset($aCreateTab['default'])) $sResultTable .= " default=\"" . $aCreateTab['default'] . "\"";
			if(isset($aCreateTab['comment'])) $sResultTable .= " comment=\"" . $aCreateTab['comment'] . "\"";
			$sResultTable .= " />";


		}
		$sResultTable .= "\n\t\t</cols>\n\t\t<indexes>";

		foreach ($aCreateTables->indexes->index as $key => $value)
		{
			$sResultTable .= "\n\t\t\t<index name=\"" . $value['name'] . "\" ";
			if(isset($value['unique'])) $sResultTable .= " unique=\"" . $value['unique'] . "\"";
			if(isset($value['col'])) $sResultTable .= " col=\"" . $value['col'] . "\"";
			$sResultTable .= " />";
		}
		$sResultTable .= "\n\t\t</indexes>\n\t</create>\n\n";

		return $sResultTable;

	}

	function alterTable($aAddColumns)    # функция для добавления changeset-а в файл
	{
		// printr($aAddColumns);
		$sResultStr = "";
		foreach ($aAddColumns as $table_key => $table_value) 	# Цикл по таблицам. $table_value это массив с полями
		{
			$sResultStr .= "\t<alter tbl=\"" . $table_key . "\" >";
			for ($i=0; $i < count($table_value); $i++)		# Цикл по добавленым полям конкретной таблицы
			{
				$sResultStr .= "\n\t\t<" . $table_value[$i]['action'];
				$sResultStr .= " name=\"" . $table_value[$i]['name'] . "\" ";
				if(isset($table_value[$i]['type']))	    $sResultStr .= "type=\""     . $table_value[$i]['type']     . "\" ";
				if(isset($table_value[$i]['length']))   $sResultStr .= "length=\""   . $table_value[$i]['length']   . "\" ";
				if(isset($table_value[$i]['unsigned'])) $sResultStr .= "unsigned=\"" . $table_value[$i]['unsigned'] . "\" ";
				if(isset($table_value[$i]['zero']))     $sResultStr .= "zero=\""     . $table_value[$i]['zero']     . "\" ";
				if(isset($table_value[$i]['null']))     $sResultStr .=  "null=\""    . $table_value[$i]['null']     . "\" ";
				if(isset($table_value[$i]['default']))  $sResultStr .= "default=\""  . $table_value[$i]['default']  . "\" ";
				if(isset($table_value[$i]['comment']))  $sResultStr .= "comment=\""  . $table_value[$i]['comment']  . "\" ";


				if(isset($table_value[$i]['col']))  $sResultStr .= "col=\""  . $table_value[$i]['col']  . "\" ";
				if(isset($table_value[$i]['unique']))  $sResultStr .= "unique=\""  . $table_value[$i]['unique']  . "\" ";




				$sResultStr .= " />";
			}
			$sResultStr .= "\n\t</alter>\n\n";
		}

		return $sResultStr;
	}




#  СРАВНЕНИЕ ПО ЦЕЛЫМ ТАБЛИЦАМ. УДАЛИЛИ ЛИ ТАБЛИЦУ ИЛИ ДОБАВИЛИ.
#---------------------------------------------------------------------------------------------
	$adbTables = array();
	$adbTablesOld = array();
	foreach ($db->tbls->tbl as  $table)
	{
		$adbTables[] = (string)$table['name'];			# Записываем имена таблиц из нового файла в массив
	}
	foreach ($dbOld->tbls->tbl as  $table)
	{
		$adbTablesOld[] = (string)$table['name'];			# Записываем имена таблиц из страрого файла в массив
		$aXmlTablesOld[(string)$table['name']] = $table; # Массив с simplexml объектами
	}

	$result = array_diff($adbTables, $adbTablesOld); 	# Добавление таблицы
	if (!empty($result))
	{
		$sResultAddTable = "";
		foreach ($result as $key => $value)
		{
			$sResultAddTable .= createTable($db->tbls->tbl[$key]);

		}
		echo "<h1>Добавлена новая таблица!</h1>";
	}
	unset($result);

	$result = array_diff($adbTablesOld, $adbTables); # Уаление таблицы
	if (!empty($result))
	{
		$sResultDropTable = "";
		foreach ($result as $value)
		{
			$sResultDropTable .= "\n\t<drop table=\"" . $value . "\" />";
		}
		echo "<h1>Удалили таблицу!</h1>";
	}
	unset($result);
#---------------------------------------------------------------------------------------------
	/* В этой части будут проводиться основные действия:
			- здесь мы будем проверять добавились ли поля;
			- добавились ли индексы;
			- удалились ли индексы;
			- удалились ли поля;
			- изменили ли что-то в полях.
	*/

	$aColAttr = array();		# Временный массив для формирования массива с добавленными полями
	$aIndexAttr = array();		# Временный массив для формирования массива с добавленными индексами
	foreach ($db->tbls->tbl as  $table)				# Заходим в цикл по названиям таблиц нового файла
	{
			#Проверяем есть ли такая таблица в "старой" БД и, если есть, будем проводить действия с ней
			if(isset($aXmlTablesOld[(string)$table['name']]))
			{
				$tableOld = $aXmlTablesOld[(string)$table['name']];

				$aColName = array();		# Массив с названиями полей в новой базе
				$aColNameOld = array();		# Массив с названиями полей в старой базе
				$aAttrColsNew = array();
				$aAttrColsOld = array();
				foreach ($table->cols->col as  $sColName)
				{
					# Записываем названия колонки совпавшей таблицы(НОВОЙ) в массив
					$aColName[] = $sColName['name'];
					$aAttrKey = (string)$sColName['name'];
					$aAttrColsNew[$aAttrKey] = $sColName;
				}
				foreach ($tableOld->cols->col as  $sColNameOld)
				{
					# Записываем названия колонки совпавшей таблицы(СТАРОЙ) в массив
					$aColNameOld[] = $sColNameOld['name'];
					$aAttrKey = (string)$sColNameOld['name'];
					$aAttrColsOld[$aAttrKey] = $sColNameOld;
				}

#---------------------------------------------------------------
				$result = array_diff($aColName, $aColNameOld); # результат сравнения по добавлению поля
#---------------------------------------------------------------
				if (!empty($result))
				{
					$table_name = (string)$table['name']; # Название таблицы
					foreach ($result as $key => $value)		# Цикл по добавленным полям
					{
						$sKey4Default = (string)$table->cols->col[$key]['type']; 	# $sKey4Default - это тип в массиве значений по умолчанию
						foreach ($table->cols->col[$key]->attributes() as $key2 => $value2)		# цикл по атрибутам добавленного поля
						{
							$aColAttr["$key2"] = "$value2";		# Собрали массив со значениями атрибутов поля
						}
						# $aResultAddCol - это двумерный массив, вторая часть которого тоже массив.
						# Туда записываются добавленные в таблицу $table_name поля после мержа с default-ом.
						// printr($aColAttr);
						$aResultAddCol["$table_name"][] = MergeWithDefault("add", $sKey4Default, $aColAttr);

					}
				}
				unset($aColAttr);
				unset($result);

#---------------Проверка на добавление нового индекса------------------------------------------------
				$aIndexName = array();
				$aIndexNameOld = array();
				foreach ($table->indexes->index as  $sIndexName)
				{
					# Записываем названия индекса совпавшей таблицы(НОВОЙ) в массив
					$aIndexName[] = $sIndexName['name'];
				}
				foreach ($tableOld->indexes->index as  $sIndexNameOld)
				{
					# Записываем названия индекса совпавшей таблицы(СТАРОЙ) в массив
					$aIndexNameOld[] = $sIndexNameOld['name'];
				}
				$result = array_diff($aIndexName, $aIndexNameOld); # результат сравнения по добавлению индекса
				if (!empty($result))
				{
					$table_name = (string)$table['name'];
					foreach ($result as $key => $value)		# Цикл по добавленным индекса
					{
						 // printr($table->indexes->index[$key]);
						foreach ($table->indexes->index[$key]->attributes() as $key2 => $value2)		# цикл по атрибутам добавленного индекса
						{
							$aIndexAttr["$key2"] = "$value2";		# Собрали массив со значениями атрибутов индекса
						}
						# $aResultAddCol - это двумерный массив, вторая часть которого тоже массив.
						# Туда записываются добавленные в таблицу $table_name индексы.
						// printr(array_merge(array("action" => "addindex"), $aIndexAttr));
						$aResultAddCol["$table_name"][] = array_merge(array("action" => "addindex"), $aIndexAttr);

					}
				}
				unset($aIndexAttr);
				unset($result);
				unset($table_name);

				$result = array_diff($aIndexNameOld, $aIndexName); # результат сравнения по добавлению индекса
				if (!empty($result))
				{
					$table_name = (string)$tableOld['name'];
					foreach ($result as $key => $value)		# Цикл по добавленным индекса
					{
						$sNameAttrIndex = $tableOld->indexes->index[$key]->attributes()->name;

						/*	Этот foreach на тот случай если понадобятся остальные аттрибуты удалённого индекса		*/
						// foreach ($tableOld->indexes->index[$key]->attributes() as $key2 => $value2)		# цикл по атрибутам добавленного индекса
						// {
						// 	$aIndexAttr["$key2"] = "$value2";		# Собрали массив со значениями атрибутов индекса
						// }

						# $aResultAddCol - это двумерный массив, вторая часть которого тоже массив.
						# Туда записываются добавленные в таблицу $table_name индексы.
						$aResultAddCol["$table_name"][] = array("action" => "dropindex" , "name" => $sNameAttrIndex);

					}
				}
				unset($aIndexAttr);
				unset($result);
				unset($table_name);


				$result = array_diff($aColNameOld, $aColName); # Результат по проверке удаления полей из БД текущей таблицы
				if (!empty($result))
				{
					$table_name = (string)$table['name'];
					foreach ($result as  $value)
					{
						$aResultAddCol["$table_name"][] = array("action"=> "drop", "name" => (string)$value); # Записываем удалённые поля в массив
					}

				}
				unset($result);
				unset($table_name);

#-------------Проверка на изменение поля или атрибутов поля--------------------------------------------------
#------------------------------------------------------------------------------------------------------------
				$table_name = (string)$table['name'];

				foreach ($aAttrColsNew as $key => $sColAttrNew)
				{
					$aTmpAttrNew = array();
					$aTmpAttrOld = array();
					$aAddAttr = array();
					$aDropAttr = array();
					if (isset($aAttrColsOld[$key]) && !empty($aAttrColsOld[$key]))
					{
						foreach ($aAttrColsOld[$key]->attributes() as $key2 => $value)
						{
							$aTmpAttrOld[$key2] = (string)$value;
						}
					}
					else
					{
						continue;
					}
					foreach ($sColAttrNew->attributes() as $key2 => $value)
					{
						$aTmpAttrNew[$key2] = (string)$value;
					}

					printr($aTmpAttrNew);
					echo "<hr>";
					printr($aAddAttr);


					/*$aAddAttr = array_diff($aTmpAttrNew, $aTmpAttrOld);
					$aDropAttr = array_diff($aTmpAttrOld, $aTmpAttrNew);

					if (!empty($aAddAttr))
					{
						printr($aAddAttr);
						$aResultAddCol["$table_name"][] = MergeWithDefault("change", $aTmpAttrNew['type'], $aTmpAttrNew);
					}*/
					unset($aAddAttr);
					unset($aDropAttr);
				}
#------Конец проверки на изменение поля или атрибутов поля---------------------------------------------------------
#---------------------------------------------------------------


#---------------------------------------------------------------
			}	# Конец действий со сравниваемыми таблицами
			unset($aColName);
			unset($aColNameOld);
		//}

	}
	// printr($aResultAddIndex);
	$sResultAddDropCol = alterTable($aResultAddCol);
	$sResultStr = "<!-- " . date(DATE_RSS) . " -->\n<ddl>\n" ;
	$sResultStr .= $sResultAddTable . $sResultAddDropCol . $sResultAddIndex . $sResultDropTable;
	$sResultStr .= "\n</ddl>\n<!-- ". str_repeat("#", 80) . " -->\n";
	file_put_contents('changeset4qdb_build.xml', $sResultStr, FILE_APPEND);
	// file_put_contents('databaseOld.xml', $db); # Перезаписываем старую базу на новую. УБРАТЬ КОМЕНТАРИЙ В КОНЦЕ!!
?>