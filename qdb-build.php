<html>
	<head>
		<meta charset="utf-8">
		<title>Формирование БД</title>
	</head>
	<body>
	</body>
</html>
<?php

$db = simplexml_load_file("changeset4qdb_build.xml");
// printr($db->children()->alter);die;
$res = "";					# Строка с итоговым результатом.



function CollectString($col_attr) # Получаем строку ' `id` INT(11) NOT NULL AUTO_INCREMENT'
{
	$sCol = "" ; # переменная для сбора строки
	# Начинаем формировать строку запроса для create table
	$sCol .= "`" . $col_attr['name'] . "` ";
	$sCol .= strtoupper($col_attr['type']);
	if(isset($col_attr['length'])) $sCol .= " (" . $col_attr['length'] . ") ";

	if(!empty($col_attr['unsigned'])) $sCol .= " UNSIGNED ";
	if(!empty($col_attr['zero'])) $sCol .= " ZEROFILL ";
	if(!$col_attr['null']) $sCol .= " NOT ";
	$sCol .= " NULL ";

	if(isset($col_attr['default']))
	{
		if($col_attr['name'] == "id")
		{
			$sCol .= " AUTO_INCREMENT ";
		}
		else
		{
			$sCol .= " DEFAULT " ;
			if($col_attr['default'] == 'NULL' || $col_attr['default'] == 'CURRENT_TIMESTAMP')
			{
				$sCol .= $col_attr['default'];
			}else
			{
				$sCol .= "'" . $col_attr['default'] . "'";
			}
		}
		if(!empty($col_attr['comment'])) $sCol .= " COMMENT '" . $col_attr['comment'] . "' ";
	}
	return $sCol;
}

function AlterTableOnMySQL($value)
{
	$res = "" ;			# Строка с итоговым результатом.
	$table_name = (string) $value['tbl'];
	$alter_table = "ALTER TABLE `" . $table_name . "` ";

	#########-----Пробую кусок с одним прохом вместо $value->add, drop, ...
// printr($value);
		foreach ($value->children() as $key => $entity)
		{
			$res .= $alter_table . " "; #. preg_replace('/(DROP|ADD)/i', '$1 ', $key) ;
			switch ($key)
			{
				case 'add':
					$res .= "ADD COLUMN ";
					$res .= CollectString($entity);
					$res .= ";<br>";
					break;
				case 'dropindex':
					$res .= "DROP INDEX ";
					if($entity['name'] == 'PRIMARY')
					{
						$res .= "PRIMARY KEY ;<br>";
					}
					else
					{
						$res .= " `" . $entity['name'] . "` ;<br>";
					}
					break;
				case 'drop':
					$res .= "DROP COLUMN `" . $entity['name'] . "` ;<br>";
					break;
				case 'addindex':
					$sCols = "";
					$aCols = explode(",", $entity['col']);
					for($i = 0; $i < count($aCols); $i++)
					{
						$sCols .= "`" . $aCols[$i] . "`";
						if($i != count($aCols) - 1) $sCols .= ",";
					}
					$res .= " ADD ";
					if(isset($entity['unique'])) $res .= " UNIQUE ";
					$res .= " INDEX `" . $entity['name'] . "` (" . $sCols . ");<br>";
					break;
				case 'change':
					$res .= "CHANGE COLUMN ";
					$res .= " `" . $entity['name'] . "` ";
					$res .= CollectString($entity);
					$res .= ";<br>";
					break;
				default:
					# code...
					break;
			}

	#########



	// foreach ($value->addindex as  $add_index) # Добавление индекса
	// {
	// 	$sCols = "";
	// 	$aCols = explode(",", $add_index['col']);
	// 	for($i = 0; $i < count($aCols); $i++)
	// 	{
	// 		$sCols .= "`" . $aCols[$i] . "`";
	// 		if($i != count($aCols) - 1) $sCols .= ",";
	// 	}
	// 	$res .= $alter_table . "ADD ";
	// 	if(isset($add_index['unique'])) $res .= "UNIQUE";
	// 	$res .= " INDEX `" . $add_index['name'] . "` (" . $sCols . ");<br>";
	// }
		}
	return $res;
}

function CreatTableOnMySQL ($value)
{
	$res = "";			# Строка с итоговым результатом.
	$res_index = "";			# Строка для сбора частей индексов
	$res_primary_index = "";	# Строка для сбора auto_increment
	$res_uniq_index = "";		# Строка для сбора уникальных ключей
	$first_col = false;		//  Это переменная-флаг. Т.к. я ставлю ',' в начале строки, то проверяю первая ли это строка
	$res = "CREATE TABLE `" . trim($value['tbl']) . "` (<br>";			// Название таблицы
	foreach ($value->cols->col as $col_name)		// Проходим по полям таблицы
	{
		$res .= "    " . (($first_col)?(", "):(""));			// Если строка первая, то ',' не ставим
		$res .= CollectString($col_name); # Собираем конкретное поле
		$res .= "<br>";
		$first_col = true;
	}
	foreach ($value->indexes as $aIndexes)
	{
		foreach ($aIndexes as $key => $sIndexes)
		{
			if($sIndexes['name'] == 'PRIMARY')
			{
				$res_primary_index = "    , PRIMARY KEY (`" . $sIndexes['col'] . "`)<br>" ;
			}elseif(isset($sIndexes['unique']))
			{
				$res_uniq_index .= "    , UNIQUE INDEX " . $sIndexes['name'] . " (" . $sIndexes['col'] . ")<br>" ;
			}else
			{
				$res_index .= "    , INDEX " . $sIndexes['name'] . " (" . $sIndexes['col'] . ")<br>" ;
			}
		}

	}
	$res .= $res_primary_index . $res_uniq_index . $res_index . ")<br>";
	$res_primary_index = "";
	$res_uniq_index = "";
	$res_index = "";

	if(!empty($value['comment']))
	{
		$res .= "COMMENT='" . $value['comment'] . "'<br>" ;
	}
	$res .= "COLLATE='utf8_general_ci'<br>ENGINE=InnoDB;";

	return $res;
}


foreach ($db->create as $value)
{
	echo CreatTableOnMySQL($value) . "<hr>";
}
foreach ($db->alter as $value)
{
	echo AlterTableOnMySQL($value) . "<hr>";
}
foreach ($db->drop as $value)
{
	echo "DROP TABLE `" . $value['table'] . "`;\n<hr>" ;
}




?>
