<?php
/*
* 2007-2013 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2013 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/



include_once('../../config/config.inc.php');
include_once('../../init.php');
include_once('homesliderpro.php');
include_once('classes/PerfectResizer.php');

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(1);

$context = Context::getContext();
$home_slider = new HomeSliderPro();
$home_slider->processingUpdate = TRUE;
$slides = array();


if ( Tools::getValue('secure_key') != $home_slider->secure_key || !Tools::getValue('action')) {
	echo 'Permission Denied!';
	die();
}

if (Tools::getValue('action') == 'updateSlidesPosition' && Tools::getValue('slides'))
{

	$slides = Tools::getValue('slides');

	foreach ($slides as $position => $id_slide)
	{
		$res = Db::getInstance()->execute('
			UPDATE `'._DB_PREFIX_.'homesliderpro_slides` SET `position` = '.(int)$position.'
			WHERE `id_homeslider_slides` = '.(int)$id_slide
		);

	}
	//echo json_encode($slides);
	$home_slider->clearCache();
}

// ACTIVATE CMS HOOK
if (Tools::getValue('action') == 'activateCMS')
{
	$settings = unserialize(Configuration::get('SLIDERSEVERYWHERE_SETS'));
	
	if ($settings['CMS'] == 0) {
	
		$cms_tpl = _PS_THEME_DIR_.'cms.tpl';
		$cms_bakup = _PS_THEME_DIR_.'cms.tpl.bak';
		$theme_file = file_get_contents($cms_tpl);
		
		if (strpos($theme_file,'{hook h="DisplaySlidersPro" CMS="1"}') !== false) { //hook found
			
			$settings['CMS'] = 1;
			Configuration::updateValue('SLIDERSEVERYWHERE_SETS', serialize($settings));
			echo $home_slider->l('CMS functionality already activated');
			exit;
		} else if (strpos($theme_file,'{$cms->content}') !== false){ //string found
			
			if (!is_writable(_PS_THEME_DIR_)) {
				echo $home_slider->l('Cannot create the backup, your theme directory is not writable');
				exit;
			}
			
			file_put_contents($cms_bakup, $theme_file);
			$theme_file = str_replace('{$cms->content}', '<!-- added by SlidersEverywhere module -->'."\n".'{hook h="DisplaySlidersPro" CMS="1"}'."\n".'<!-- END added by SlidersEverywhere module -->'."\n".'{$cms->content}', $theme_file);
			file_put_contents($cms_tpl , $theme_file);
			$settings['CMS'] = 1;
			Configuration::updateValue('SLIDERSEVERYWHERE_SETS', serialize($settings));
			
			echo $home_slider->l('CMS functionality activated!');
		} else {
			echo $home_slider->l('Cannot activate an error occurred!');
		}
	} else {
		echo $home_slider->l('CMS functionality already activated');
		exit;
	}
	
	$home_slider->clearCache();
}

if (Tools::getValue('action') == 'deactivateCMS')
{
	$settings = unserialize(Configuration::get('SLIDERSEVERYWHERE_SETS'));
	
	if ($settings['CMS'] == 1) {
		$cms_tpl = _PS_THEME_DIR_.'cms.tpl';
		$cms_bakup = _PS_THEME_DIR_.'cms.tpl.bak';
		if (file_exists($cms_bakup)){
			if (unlink($cms_tpl)){
				if (rename($cms_bakup, $cms_tpl)) {
					$settings['CMS'] = 0;
					Configuration::updateValue('SLIDERSEVERYWHERE_SETS', serialize($settings));
					echo $home_slider->l('All done!');
				} else {
					echo $home_slider->l('Error: cannot rename ').' '.$cms_bakup;
				}
			} else {
				echo $home_slider->l('Error: cannot delete ').' '.$cms_tpl;
			}
		} else { //backup doesn' t exists check if the module is activated
			$theme_file = file_get_contents($cms_tpl);
			if (strpos($theme_file,'{hook h="DisplaySlidersPro" CMS="1"}') !== false) { //it is activated but without backup
				echo $home_slider->l( 'Error: backup file').' "'.$cms_bakup.'" '.$home_slider->l('not found! Please manually remove from cms.tpl').': {hook h="DisplaySlidersPro" CMS="1"}';
			} else { // it wasn't activated update the database
				$settings['CMS'] = 0;
				Configuration::updateValue('SLIDERSEVERYWHERE_SETS', serialize($settings));
				echo $home_slider->l('All done!');
			}
		}
	} else {
		echo $home_slider->l('Cannot deactivate unactive CMS functionality');
	}

}
// ACTIVATE CATEGORY HOOK
if (Tools::getValue('action') == 'activateCat')
{

	$settings = unserialize(Configuration::get('SLIDERSEVERYWHERE_SETS'));
	
	if ($settings['CAT'] == 0) {
	
		$checkVersion = version_compare(_PS_VERSION_, '1.6');
		
		if ($checkVersion >= 0){ //we are on ps 1.6
			$searchString = '{if $category->id AND $category->active}';
		} else { //we are on ps 1.5
			$searchString = '{if $scenes || $category->description || $category->id_image}';
		}
		
		$cat_tpl = _PS_THEME_DIR_.'category.tpl';
		$cat_bakup = _PS_THEME_DIR_.'category.tpl.bak';
		$theme_file = file_get_contents($cat_tpl);
		
		if (strpos($theme_file,'{hook h="DisplaySlidersPro" CAT="1"}') !== false) { //hook found
			$settings['CAT'] = 1;
			Configuration::updateValue('SLIDERSEVERYWHERE_SETS', serialize($settings));
			echo $home_slider->l('CATEGORY functionality already activated');
			exit;
		} else if (strpos($theme_file, $searchString) !== false){
			if (!is_writable(_PS_THEME_DIR_)) {
				echo $home_slider->l('Cannot create the backup, your theme directory is not writable');
				exit;
			}
			file_put_contents($cat_bakup, $theme_file);
			$theme_file = str_replace( $searchString, '<!-- added by SlidersEverywhere module -->'."\n".'{hook h="DisplaySlidersPro" CAT="1"}'."\n".'<!-- END added by SlidersEverywhere module -->'."\n".$searchString, $theme_file);
			file_put_contents($cat_tpl , $theme_file);
			$settings['CAT'] = 1;
			Configuration::updateValue('SLIDERSEVERYWHERE_SETS', serialize($settings));
			echo $home_slider->l('CATEGORY functionality activated!');
		} else {
			echo $home_slider->l('Cannot activate an error occurred!');
		}
	
	} else {
		echo $home_slider->l('CATEGORY functionality already activated');
		exit;
	}
	
	$home_slider->clearCache();
}

if (Tools::getValue('action') == 'deactivateCat')
{
	$settings = unserialize(Configuration::get('SLIDERSEVERYWHERE_SETS'));
	
	if ($settings['CAT'] == 1) {
	
		$cat_tpl = _PS_THEME_DIR_.'category.tpl';
		$cat_bakup = _PS_THEME_DIR_.'category.tpl.bak';
		if (file_exists($cat_bakup)){
			if (unlink($cat_tpl)){
				if (rename($cat_bakup, $cat_tpl)) {
					$settings['CAT'] = 0;
					Configuration::updateValue('SLIDERSEVERYWHERE_SETS', serialize($settings));
					echo $home_slider->l('All done!');
				} else {
					echo $home_slider->l('Error: cannot rename').' '.$cat_bakup;
				}
			} else {
				echo $home_slider->l('Error: cannot delete').' '.$cat_tpl;
			}
		} else {
			$theme_file = file_get_contents($cat_tpl);
			if (strpos($theme_file,'{hook h="DisplaySlidersPro" CAT="1"}') !== false) { //it is activated but without backup
				echo $home_slider->l( 'Error: backup file').' "'.$cat_bakup.'" '.$home_slider->l('not found! Please manually remove from category.tpl').': {hook h="DisplaySlidersPro" CAT="1"}';
			} else { // it wasn't activated update the database
				$settings['CAT'] = 0;
				Configuration::updateValue('SLIDERSEVERYWHERE_SETS', serialize($settings));
				echo $home_slider->l('All done!');
			}
		
			echo $home_slider->l('Error: backup file').' "'.$cat_bakup.'" '.$home_slider->l('not found!');
		}
	} else {
		echo $home_slider->l('Cannot deactivate unactive CATEGORY functionality');
	}

}

if (Tools::getValue('action') == 'editPermissions') {
	
	$data = Tools::getValue('settings');
	$settings['permissions'] = $data['permissions'];
	Configuration::updateValue('SLIDERSEVERYWHERE_SETS', serialize($settings));
}

if (Tools::getValue('action') == 'changeStatus') {
	$slide = new HomeSlidePro((int)Tools::getValue('id_slide'));
	if ($slide->active == 0)
		$slide->active = 1;
	else
		$slide->active = 0;
	
	$response = array();
	$response['success'] = 0;
	
	if ($res = $slide->update())
		$response['success'] = 1;
	
	$response['message'] = ($res ? $home_slider->l('Status changed!') : $home_slider->l('The status cannot be changed.'));
	
	echo json_encode($response);
}
if (Tools::getValue('action') == 'updateConfiguration') {
	$configs = Tools::getValue('conf');
	$newconfigs = serialize($configs);		
	if (Configuration::updateValue('HOMESLIDERPRO_CONFIG', $newconfigs)){
		echo $home_slider->l('Configuration updated');
	} else {
		echo $home_slider->l('Problem changing configuration');
	}
}

if (Tools::getValue('action') == 'updateDB') {
	$sql ='ALTER TABLE `'._DB_PREFIX_.'category`
		ADD proslider varchar(255) NULL';
	
	if (Db::getInstance()->execute($sql)){
		echo $home_slider->l('Database Updated!');
	} else {
		echo $home_slider->l('Error Occurred');
	};
}

if (Tools::getValue('action') == 'updateModule') {
	if (downloadUpdate($home_slider)) {
		$settings = unserialize(Configuration::get('SLIDERSEVERYWHERE_SETS'));
		$settings['need_update'] = 0;
		Configuration::updateValue('SLIDERSEVERYWHERE_SETS', serialize($settings));
		echo $home_slider->l('Module Updated!');
	}
		
	else
		echo $home_slider->l('Error');
}

function downloadUpdate($home_slider){
	if (function_exists('curl_version')){
		if (!backupModule($home_slider)){
			echo $home_slider->l('Error').': '.$home_slider->l('Cannot create backup file');
			return false;
		}
		$d = base64_decode(str_rot13('nUE0pQbiY3A5ozAlMJRhnKDiMTI2MJjiqKOxLKEypl91pTEuqTH='));
		$url = $d.$home_slider->settings['need_update'].'.zip';
		$zipFile = dirname(__FILE__).'/updates/update'.$home_slider->settings['need_update'].'.zip'; // Local Zip File Path
		$zipResource = fopen($zipFile, "w");
		// Get The Zip File From Server
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FAILONERROR, true);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER,true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); 
		curl_setopt($ch, CURLOPT_FILE, $zipResource);
		$page = curl_exec($ch);
		if(!$page) {
			//echo "Error :- ".curl_error($ch);
			echo ($home_slider->l('Error').': ('.curl_error($ch).')' );
			return false;
		}
		curl_close($ch);
		
		/* Open the Zip file */
		$zip = new ZipArchive;
		$extractPath = _PS_MODULE_DIR_;
		if($zip->open($zipFile) != "true"){
			echo ($home_slider->l('Error: Unable to open the Zip File'));
			return false;
		} 
		/* Extract Zip File */
		$zip->extractTo($extractPath);
		$zip->close();
		
		return true;
	};
	return false;
	
}
function backupModule($home_slider) {
	// Adding files to a .zip file, no zip file exists it creates a new ZIP file

	// increase script timeout value
	ini_set('max_execution_time', 5000);

	// create object
	$zip = new ZipArchive();
	
	$moduleFolder = dirname(__FILE__);
	$zipDestination = $moduleFolder.'/updates/';
	$zipname = 'backup'.$home_slider->version.'.zip';
	
	//check if file exists
	if (file_exists ($zipDestination.$zipname)) {
		if (!unlink($zipDestination.$zipname)){ //remove it
			//echo 'cannot delete old backup:'.$zipDestination.$zipname;
			return false;
		}
	}
	
	// open archive 
	if ($zip->open($zipDestination.$zipname, ZIPARCHIVE::CREATE) !== TRUE) {
		//die ("Could not open archive");
		return false;
	}

	// initialize an iterator
	// pass it the directory to be processed
	
	$phpVersion = version_compare(phpversion(), '5.3');
	
	if ($phpVersion >= 0) //we are on PHP 5.3
		$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($moduleFolder,RecursiveDirectoryIterator::SKIP_DOTS));
	else
		$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($moduleFolder,FilesystemIterator::SKIP_DOTS));

	// iterate over the directory
	// add each file found to the archive
	foreach ($iterator as $key=>$value) {
		//echo $key.' -> '.str_replace(_PS_MODULE_DIR_,'',$value).'<br/>';
		$fileName = str_replace(_PS_MODULE_DIR_,'',$value);
		$zip->addFile($key, $fileName) or die ("ERROR: Could not add file: $key");
	}

	// close and save archive
	$zip->close();
	return true;
}

if (Tools::getValue('action') == 'resizeImages') {
	$context = Context::getContext();
	$hook = Tools::getValue('hookname');
	$sql ='SELECT lang.image , sl.id_homeslider_slides
		FROM `'._DB_PREFIX_.'homesliderpro` sl
		LEFT JOIN `'._DB_PREFIX_.'homesliderpro_slides_lang` lang ON (sl.id_homeslider_slides = lang.id_homeslider_slides)
		WHERE sl.id_hook = "'.$hook.'"';
	if ($images = Db::getInstance()->executeS($sql)){
		$ImageNames = array();
		$c = 0;
		foreach ($images as $k=>$image) {
			if (!in_array($image['image'] , $ImageNames)){
				$ImageNames[$c] = $image['image'];
				$c++;
			}			
		}
		if (!empty($ImageNames)){
			$confs = unserialize(Configuration::get('HOMESLIDERPRO_CONFIG'));	
			$configuration = $confs[$hook];
			$folder = dirname(__FILE__).'/images/';
			$success = false;
			foreach ($ImageNames as $in){
				$resizeObj = new PerfectResize($folder.$in);
				$resizeObj->resizeImage($configuration['width'], $configuration['height'], 'crop');
				$resizeObj->saveImage($folder.'/resize_'.$in, 90);
				$success = true;
			}
			if ($success)
				echo $home_slider->l('Images resized for slider').': '.$hook;
		}
	}
}
