<?php

if (!defined('_PS_VERSION_'))
	exit;

function upgrade_module_1_5_54($module)
{
	$res = 1;
	
	if (!columnExists(_DB_PREFIX_.'category','proslider')){
		$res &= (bool)Db::getInstance()->execute(
			'ALTER TABLE `'._DB_PREFIX_.'category`
			ADD proslider varchar(255) NULL'
		);
	}
	
	if (!columnExists(_DB_PREFIX_.'homesliderpro_slides','has_area')){
		$res &= (bool)Db::getInstance()->execute(
			'ALTER TABLE `'._DB_PREFIX_.'homesliderpro_slides`
			ADD `has_area` tinyint(1) unsigned NOT NULL DEFAULT \'0\' 
		');
	}
	
	if (!columnExists(_DB_PREFIX_.'homesliderpro_slides_lang','areas')){
		$res &= (bool)Db::getInstance()->execute(
			'ALTER TABLE `'._DB_PREFIX_.'homesliderpro_slides_lang`
			ADD `areas` text NULL
		');
	}
	
	return $res;
}


function columnExists($tablename,$columname) {
	$sql = 'SELECT * 
		FROM information_schema.COLUMNS
			WHERE TABLE_SCHEMA = "'._DB_NAME_.'"
			AND TABLE_NAME = "'.$tablename.'"
			AND COLUMN_NAME = "'.$columname.'"';
	if (Db::getInstance()->executeS($sql))
		return true;
	return false;
}