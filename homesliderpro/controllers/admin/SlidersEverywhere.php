<?php
if (!defined('_PS_VERSION_'))
	exit;

include_once(_PS_MODULE_DIR_.'homesliderpro/homesliderpro.php');
include_once(_PS_MODULE_DIR_.'homesliderpro/classes/PerfectResizer.php');

class SlidersEverywhereController extends AdminController {
	private $_html = '';
	private $baseHooks;
	public $context;
	public $langs;
	public $module;
	private $settings;
	private $slide;
	private $temp_html = '';
	public $image_folder;

	public function __construct()
	{
		$this->html = '';
		$this->langs = Language::getLanguages(true, true);
		$this->context = Context::getContext();
		$this->display = 'view';
		$this->meta_title = $this->l('Sliders Everywhere');
		$this->toolbar_title = $this->l('Sliders Everywhere');
		$this->name = 'SlidersEverywhere';
		$this->module = new HomeSliderPro;

		$this->displayName = $this->module->displayName;
		$this->secure_key = $this->module->secure_key;
		
		$this->tabEmptyName = $this->l('No Name');
		$this->areaTitleLabel = $this->l('Title').':';
		$this->areaUrlLabel = $this->l('Url').':';
		$this->areaDescLabel = $this->l('Description').':';
		$this->areaButtLabel = $this->l('Button Text').':';
		$this->areaStyleSimple = $this->l('Simple').':';
		$this->areaStyleBlock = $this->l('Block').':';
		$this->areaColorLight = $this->l('Light').':';
		$this->areaColorDark = $this->l('Dark').':';
		$this->areaColorTrans = $this->l('Transparent').':';
		$this->areaStyleLegend = $this->l('Style').':';
		$this->areaColorLegend = $this->l('Color').':';
				
		$hooks = Configuration::get('HOMESLIDERPRO_HOOKS');
		
		$this->settings = unserialize(Configuration::get('SLIDERSEVERYWHERE_SETS'));
		
		$this->standardHooks = unserialize(Configuration::get('HOMESLIDERPRO_STANDARD'));
		
		$this->image_folder = __PS_BASE_URI__.'modules/'.$this->module->name.'/images/';
		
		$this->counter=0;

		if (!empty($hooks))
			$this->hook = unserialize($hooks);
		else
			$this->hook = array();
		
		$this->baseHooks = $this->module->baseHooks;

		$this->defaultConf = $this->module->defaultConf;

		parent::__construct();
	}

	public function initContent()
	{
		//$this->_postProcess();
		$this->context->controller->addCSS(_MODULE_DIR_.$this->module->name.'/css/font-awesome.css');
		$this->context->controller->addCSS(_MODULE_DIR_.$this->module->name.'/css/imgareaselect-animated.css');	
		$this->context->controller->addCSS(_MODULE_DIR_.$this->module->name.'/css/config.css');
		
		$this->context->controller->addJS(__PS_BASE_URI__.'js/tiny_mce/tiny_mce.js');
		$this->context->controller->addJS(_MODULE_DIR_.$this->module->name.'/js/jquery.imgareaselect.pack.js');
		$this->context->controller->addJS(_MODULE_DIR_.$this->module->name.'/js/config.js');
		
		$this->show_toolbar = true;
		$this->display = 'view';
		$this->meta_title = $this->l('Sliders Everywhere');
		$this->confirmations = array();
		parent::initContent();	
	}
	
	public function initToolBarTitle()
	{
		$this->toolbar_title = $this->l('Sliders E');
	}
	
	public function initToolBar()
	{
		return false;
	}
	
	private function getCategoryTree($id_category = 1, $id_lang = false, $id_shop = false, $recursive = true)
	{
		$id_lang = $id_lang ? (int)$id_lang : (int)Context::getContext()->language->id;
		$category = new Category((int)$id_category, (int)$id_lang, (int)$id_shop);

		if (is_null($category->id))
			return;

		if ($recursive)
		{
			$children = Category::getChildren((int)$id_category, (int)$id_lang, true, (int)$id_shop);
			$spacer = 12 * (int)$category->level_depth;
		}

		$shop = (object) Shop::getShop((int)$category->getShopID());
		$this->temp_html .= '<li style="padding-left:'.(isset($spacer) ? ($spacer+10).'px' : '10px').';" data-cat="'.(int)$category->id.'" >'.$category->name.' <span>('.$shop->name.')</span><i class="fa fa-circle-o"></i></li>';

		if (isset($children) && count($children))
			foreach ($children as $child)
				$this->getCategoryTree((int)$child['id_category'], (int)$id_lang, (int)$child['id_shop']);
	}
	
	public function renderView() {
			
		$this->_html .='<div id="SESlides">';
		$this->_html .= $this->headerHTML();
		$this->getCategoryTree();
		$this->_html .= '<ul class="catTree">'. $this->temp_html .'<li class="closeme">'.$this->l('Close and remove Category').'<span class="fa fa-times"></span></li></ul>';
		
		$this->_html .= '<div id="overlayer"></div>';
		
		$headStart = '<div class="toolbarBox toolbarHead">
			<ul class="cc_button">';
		$headSaveConfig	= '';
		$headSaveSlide = '<li>
					<a style="display: block;" id="single-save" class="toolbar_btn" href="#" title="'.$this->l('Save').'">
						<span class="fa fa-check-circle savebig "></span>
						<div>'.$this->l('Save').'</div>
					</a>
				</li>';
		$headBack = '
				<li>
					<a id="desc-product-back" class="toolbar_btn" href="'.AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite($this->name).'" title="'.$this->l('Back').'">
						<span class="fa fa-reply backbutton"></span>
						<div>'.$this->l('Back').'</div>
					</a>
				</li>							
		';
		$headFoot = '</ul>
			<div class="pageTitle">
				<h3><img src="'.__PS_BASE_URI__.'modules/'.$this->module->name.'/logo.png" alt="Logo" title="Put your sliders Everywhere!"/>'.$this->displayName.'<span class="small"><span class="small"><span class="small">
				(v:'.$this->module->version.')
				</span></span></span></h3>
			</div>
		</div>';

		/* Validate & process */
		if (
			Tools::isSubmit('delete_id_slide') ||
			Tools::isSubmit('submitSlider') ||
			Tools::isSubmit('changeStatus') ||
			Tools::isSubmit('addHook') ||
			Tools::isSubmit('deleteHook') ||
			Tools::isSubmit('updateConfiguration') ||
			Tools::isSubmit('saveHooks')
			)
		{		
			if ($this->_postValidation()) {
				$this->_html .= $headStart.$headSaveConfig.$headFoot;
				$this->_postProcess();
			} else {
				$this->_html .= $headStart.$headSaveConfig.$headFoot;
			}
			$this->_displayForm();
		}
		elseif (Tools::isSubmit('addSlide') || ( Tools::isSubmit('id_slide') && $this->slideExists((int)Tools::getValue('id_slide')))) {
			if ($this->_postValidation()) {
				if (Tools::isSubmit('submitSlide')){
					$this->_html .= $headStart.$headSaveConfig.$headSaveSlide.$headBack.$headFoot;
					$this->_postProcess();
					if (Validate::isLoadedObject($this->slide))
						$slide = $this->slide;
					header("location:".$_SERVER["REQUEST_URI"].(isset($slide) && $slide->id ? '&id_slide='.$slide->id : ''));
					$this->_displayAddForm();
					//$this->_displayForm();
				} else {
					$this->_html .= $headStart.$headSaveSlide.$headBack.$headFoot;
					$this->_displayAddForm();
				}					
			} else {
				$this->_html .= $headStart.$headSaveSlide.$headBack.$headFoot;
				$this->_displayAddForm();
			}
		}
		else {
			$this->_html .= $headStart.$headSaveConfig.$headFoot;
			$this->_displayForm();
		}
		$this->_html .='</div>';
		
		return $this->_html;
	}
	
	public function headerHTML()
	{
		if (Tools::getValue('controller') != $this->name)
			return;
		if ($this->module->settings['need_update']) {
			$this->_html .= '<div class="module_confirmation conf confirm">
			'.$this->l('NEW VERSION Available for ').$this->module->displayName.' (v:'.$this->module->settings['need_update'].')
			</div>';
			$this->_html .= '<form action="#" id="moduleUpdate" method="post"><input type="submit" class="button centered big" id="moduleUpdate" name="moduleUpdate" value="'.$this->l('Update Now!').'"/></form>';
		}
		
		$html = '<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/jquery-ui.min.js"></script>';
		$html .= '<script type="text/javascript">
			var ajaxUrl = "'._PS_BASE_URL_.__PS_BASE_URI__.'modules/'.$this->module->name.'/ajax_'.$this->module->name.'.php?secure_key='.$this->secure_key.'";
		</script>';

		return $html;
	}
	
	private function _displayForm()
	{
				
		$confs = unserialize(Configuration::get('HOMESLIDERPRO_CONFIG'));
		$standardHooks = unserialize(Configuration::get('HOMESLIDERPRO_STANDARD'));

		$enabled = false;
		if ( $this->context->employee->id_profile == _PS_ADMIN_PROFILE_ || $this->settings['permissions']['hooks'] == 0)
			$enabled = true;
		
		/** Genearl settings */
		if ($enabled) {
		$this->_html .= '
		<form id="sliders_setup" action="'.AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite($this->name).'" method="post">
			<fieldset>
				<legend><img src="'.__PS_BASE_URI__.'modules/'.$this->module->name.'/logo.png" alt="logo" />'.$this->l('Slides Setup').' </legend>
				<p>'.$this->l('Choose where you want your sliders to appear, add new sliders or delete it.').'</p>
				<table class="table confighooks" >
					<tr >
						<th>'.$this->l('Slider Name').'</th>
						<th>'.$this->l('Top').'</th>
						<th>'.$this->l('Home').'</th>
						<th>'.$this->l('Left Sidebar').'</th>
						<th>'.$this->l('Left Product Sidebar').'</th>
						<th>'.$this->l('Right Sidebar').'</th>
						<th>'.$this->l('Right Product Sidebar').'</th>
						<th>'.$this->l('Footer').'</th>
						<th>'.$this->l('Product Footer').'</th>';
						
					
					if ($this->module->isPS6){
						$this->_html .= '
						<th>'.$this->l('Top column').'</th>
						<th>'.$this->l('Home Tab Content').'</th>
						<th>'.$this->l('Product Tab').'</th>
						<th>'.$this->l('Shopping Cart Foot').'</th>
						';
					}
					$this->_html .= '
						<th>'.$this->l('Category').'</th>
						<th>'.$this->l('Custom Hook').'</th>
						<th>'.$this->l('Shortcode').'</th>
						<th><b>'.$this->l('Delete').'</b></th>
					</tr>';
		if (is_array($this->hook) && !empty($this->hook)){
			$i=0;
			foreach ($this->hook as $hookid=>$hookname) {
				$this->_html .= '<tr class="'.($i%2 == 0? 'odd':'even').'">
					<td>'.$hookname.'</td>';
				foreach ($this->baseHooks as $shook){ // standard prestashop hooks
					if (isset($standardHooks[$shook]) && is_array($standardHooks[$shook]) && in_array($hookname, $standardHooks[$shook])) {
						$checked="checked='checked'";
						$class = 'active';
					} else {
						$checked = '';
						$class = '';
					}
					$this->_html .= '<td class="'.$class.'">
						<input '.$checked.' type="checkbox" name="standardHooks['.$shook.'][]" value="'.$hookname.'" />
						</td>';
				}
				// category hook
				if (!$chosenCat = $this->module->getCategoryIdBySlide($hookname))
					$chosenCat = '';
				$this->_html .= '<td>'.$this->l('Category ID').': <input size="2" class="catnumber" type="number" value="'.$chosenCat.'" name="cat['.$hookname.']"/></td>';

				
				$this->_html .= '<td><span class="hookCode">{hook h="displaySlidersPro" slider="'.$hookname.'"}</span></td>';
				$this->_html .= '<td><span class="hookCode">[SE:'.$hookname.']</span></td>';
				$this->_html .= '<td class="delete"><input type="checkbox" name="hooksetup['.$hookid.']" value="'.$hookname.'"/></td>';				
				$this->_html .= '</tr>';
				$i++;
			}
		}
		
		
		$this->_html .= '</table><br/>
			<div class="margin-form">
				<input class="button" type="submit" name="saveHooks" value="'.$this->l('Save Hook Configuration').'"/>
				<input class="button deleteSlide" type="submit" name="deleteHook" value="'.$this->l('Delete Selected Slides').'"/>
			</div>';
		
		$this->_html .= '<hr/><label><span class="fa fa-plus-circle"></span> '.$this->l('New Slider name').'</label>
			<div class="margin-form"><input type="text" name="newSlide" value=""/> ('.$this->l('Only lowercase letters and underscores, no special characters, no numbers or blank spaces.').')</div>
			<div class="margin-form"><input class="button" type="submit" name="addHook" value="'.$this->l('Add New Slider').'"/></div>';
			
		$this->_html .= '</fieldset></form>';
		} // end if enabled permissions

		/** End Genearl settings */
		
		
		
		/** slide CHOOSER **/
		
		$slideArray = array();// cache slides to avoide double query
		$this->_html .= '<div class="slideChooserCont">';
		if (is_array($this->hook) && !empty($this->hook)){
			foreach ($this->hook as $hookId => $hookname) {
				$slideArray[$hookname] = $this->module->getSlides(null, $hookname);
				$empty = (!$slideArray[$hookname] ? true : false);
				$count = count($slideArray[$hookname]);
				$this->_html .= '<a class="slideChoose '.($hookId == 0 ? 'active':'').'" href="#'.$hookname.'slideConf"><span class="anim">'.$hookname.' '.($empty ? '<span title="'.$this->l('You have not yet added any slides.').'" class="fa fa-exclamation"></span>' : '<span class="number">'.$count.'</span>').'</span></a>';
			}
		}
		$this->_html .= '</div>';
		
		
		/** slides configuration **/
		$this->_html .= '<form id="sliders_config" class="fixsize" action="'.AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite($this->name).'" method="post">';
		if (is_array($this->hook) && !empty($this->hook)){		
			foreach ($this->hook as $hookId => $hookname) {
				$this->_html .= '
				<fieldset class="position '.$hookname.' '.($hookId == 0 ? 'open':'').'" id="'.$hookname.'slideConf">
					<legend><img src="'.__PS_BASE_URI__.'modules/'.$this->module->name.'/logo.png" alt="logo" /> '.$this->l('Slider').': "'.$hookname.'"</legend>
					<div class="position"></div>
					<fieldset class="slideOptions"><legend>'.$this->l('Edit slider options').'</legend>
					<div class="dashed"><span class="label">'.$this->l('Sizes and Timings').'</span>
					';
										
					if ($this->context->employee->id_profile == _PS_ADMIN_PROFILE_ || $this->settings['permissions']['sizes'] == 0) {
						$this->_html .= '
							<div class="margin-form">
								<label>'.$this->l('Width').': </label>
								<input size="3" maxlength="4" class="config resConf" data-hook="'.$hookname.'" name="conf['.$hookname.'][width]" type="text" value="'.$confs[$hookname]['width'].'" /> px
							</div>
							
							<div class="margin-form">
								<label>'.$this->l('Height').': </label>
								<input size="3" maxlength="4" class="config resConf" data-hook="'.$hookname.'" name="conf['.$hookname.'][height]" type="text" value="'.$confs[$hookname]['height'].'"/> px
							</div>';
						$this->_html .= '
						<div class="margin-form">
							<label>'.$this->l('Speed').': </label>
							<input size="3" maxlength="4" class="config" name="conf['.$hookname.'][speed]" type="text" value="'.$confs[$hookname]['speed'].'"/> ms
						</div>
						<div class="margin-form">
							<label>'.$this->l('Pause').': </label>
							<input size="3" maxlength="4" class="config" name="conf['.$hookname.'][pause]" type="text" value="'.$confs[$hookname]['pause'].'"/> ms
						</div>';
						$this->_html .= '<div class="center"><a href="" data-hook="'.$hookname.'" class="batchResize button">'.$this->l('Save and Resize images').' <i class="fa fa-expand"></i></a></div>';
					} else {
						$this->_html .= '
						<div class="margin-form">
							<label>'.$this->l('Width').': </label> '.$confs[$hookname]['width'].' px
							<input type="hidden" name="conf['.$hookname.'][width]" value="'.$confs[$hookname]['width'].'">
						</div>';
						$this->_html .= '
						<div class="margin-form">
							<label>'.$this->l('Height').': </label> '.$confs[$hookname]['height'].' px
							<input type="hidden" name="conf['.$hookname.'][height]" value="'.$confs[$hookname]['height'].'">
						</div>';
						$this->_html .= '
						<div class="margin-form">
							<label>'.$this->l('Speed').': </label> '.$confs[$hookname]['speed'].' ms
							<input type="hidden" size="3" maxlength="4" class="config" name="conf['.$hookname.'][speed]" value="'.$confs[$hookname]['speed'].'"/> 
						</div>
						<div class="margin-form">
							<label>'.$this->l('Pause').': </label> '.$confs[$hookname]['pause'].' ms
							<input size="3" maxlength="4" class="config" name="conf['.$hookname.'][pause]" type="hidden" value="'.$confs[$hookname]['pause'].'"/> 
						</div>';
					}
					
					$this->_html .= '</div><div class="dashed"><span class="label">'.$this->l('Animation').'</span><div class="margin-form">
						<label>'.$this->l('Mode').': </label>
						<select name="conf['.$hookname.'][mode]">
							<option value="horizontal" '.(($confs[$hookname]['mode'] == 'horizontal') ? 'selected="selected"' : '' ).'>'.$this->l('Horizontal').'  &nbsp;</option>
							<option value="vertical" '.(($confs[$hookname]['mode'] == 'vertical') ? 'selected="selected"' : '' ).'>'.$this->l('Vertical').'  &nbsp;</option>
							<option value="fade" '.(($confs[$hookname]['mode'] == 'fade') ? 'selected="selected"' : '' ).'>'.$this->l('Fade').'  &nbsp;</option>
							<option value="3Dflip" '.(($confs[$hookname]['mode'] == '3Dflip') ? 'selected="selected"' : '' ).'>'.$this->l('3D Flip').'  &nbsp;</option>
							<option value="carousel" '.(($confs[$hookname]['mode'] == 'carousel') ? 'selected="selected"' : '' ).'>'.$this->l('Carousel').'  &nbsp;</option>
						</select>
					</div>
					
					<div class="margin-form">
						<label>'.$this->l('Direction').': </label>
						<select name="conf['.$hookname.'][direction]">
							<option value="next" '.(($confs[$hookname]['direction'] == 'next') ? 'selected="selected"' : '' ).'>'.$this->l('Forward').'  &nbsp;</option>
							<option value="prev" '.(($confs[$hookname]['direction'] == 'prev') ? 'selected="selected"' : '' ).'>'.$this->l('Backward').'  &nbsp;</option>
						</select>
					</div>
					
					<div class="margin-form">
						<label>'.$this->l('Auto Start').': </label>
						<label class="t" for="enableauto_'.$hookname.'"><i class="fa fa-check"></i>
						<input id="enableauto_'.$hookname.'" name="conf['.$hookname.'][auto]" type="radio" value="1" '. (($confs[$hookname]['auto'] == 1) ? 'checked="checked"' : '' ).'/> '.$this->l('Yes').'</label>
						<label class="t" for="disableauto_'.$hookname.'"><i class="fa fa-times"></i>
						<input id="disableauto_'.$hookname.'" name="conf['.$hookname.'][auto]" type="radio" value="0" '. (($confs[$hookname]['auto'] == 1) ? '' : 'checked="checked"' ).'/> '.$this->l('No').'</label>
					</div>

					<div class="margin-form">
						<label>'.$this->l('Auto Restart').': </label>
						<label class="t" for="enableRestart_'.$hookname.'"><i class="fa fa-check"></i>
						<input id="enableRestart_'.$hookname.'" name="conf['.$hookname.'][restartAuto]" type="radio" value="1" '. (($confs[$hookname]['restartAuto'] == 1) ? 'checked="checked"' : '' ).'/> '.$this->l('Yes').'</label>
						<label class="t" for="disableRestart_'.$hookname.'"><i class="fa fa-times"></i>
						<input id="disableRestart_'.$hookname.'" name="conf['.$hookname.'][restartAuto]" type="radio" value="0" '. (($confs[$hookname]['restartAuto'] == 1) ? '' : 'checked="checked"' ).'/> '.$this->l('No').'</label>
						<div class="helper"><div class="help">'.$this->l('After clicking on controls the slider stops, if this is enabled the slider will start again').'</div></div>
					</div>
					
					<div class="margin-form">
						<label>'.$this->l('Loop').': </label>
						<label class="t" for="enableloop_'.$hookname.'"><i class="fa fa-check"></i>
						<input id="enableloop_'.$hookname.'" name="conf['.$hookname.'][loop]" type="radio" value="1" '. (($confs[$hookname]['loop'] == 1) ? 'checked="checked"' : '' ).'/> '.$this->l('Yes').'</label>
						<label class="t" for="disableloop_'.$hookname.'"><i class="fa fa-times"></i>
						<input id="disableloop_'.$hookname.'" name="conf['.$hookname.'][loop]" type="radio" value="0" '. (($confs[$hookname]['loop'] == 1) ? '' : 'checked="checked"' ).'/> '.$this->l('No').'</label>
						<div class="helper"><div class="help">'.$this->l('This option make the slider infinite').'</div></div>
					</div>
					
					</div>
					
					<div class="dashed"><span class="label">'.$this->l('Buttons').'</span><div class="margin-form clearfix">
						<label>'.$this->l('Show Play / Stop').': </label>
						<label class="t" ><i class="fa fa-check"></i>
						<input id="showplay'.$hookname.'" name="conf['.$hookname.'][autoControls]" type="radio" value="1" '. (($confs[$hookname]['autoControls'] == 1) ? 'checked="checked"' : '' ).'/> '.$this->l('Yes').'</label>
						<label class="t" ><i class="fa fa-times"></i>
						<input id="hidePlay_'.$hookname.'" name="conf['.$hookname.'][autoControls]" type="radio" value="0" '. (($confs[$hookname]['autoControls'] == 1) ? '' : 'checked="checked"' ).'/> '.$this->l('No').'</label>
						<div class="helper"><div class="help">'.$this->l('This will show play and stop icons to allow the user to control the automatic slideshow').'</div></div>
					</div>
					
					<div class="margin-form">
						<label>'.$this->l('Show Controls').': </label>
						<label class="t" for="enablecontrols_'.$hookname.'"><i class="fa fa-check"></i>
						<input id="enablecontrols_'.$hookname.'" name="conf['.$hookname.'][controls]" type="radio" value="1" '. (($confs[$hookname]['controls'] == 1) ? 'checked="checked"' : '' ).'/> '.$this->l('Yes').'</label>
						<label class="t" for="disablecontrols_'.$hookname.'"><i class="fa fa-times"></i>
						<input id="disablecontrols_'.$hookname.'" name="conf['.$hookname.'][controls]" type="radio" value="0" '. (($confs[$hookname]['controls'] == 1) ? '' : 'checked="checked"' ).'/> '.$this->l('No').'</label>
						<div class="helper"><div class="help">'.$this->l('Show or hide navigation arrows').'</div></div>
					</div>
					
					<div class="margin-form">
						<label>'.$this->l('Show Pager').': </label>
						<label class="t" for="enablepager_'.$hookname.'"><i class="fa fa-check"></i>
						<input id="enablepager_'.$hookname.'" name="conf['.$hookname.'][pager]" type="radio" value="1" '. (($confs[$hookname]['pager'] == 1) ? 'checked="checked"' : '' ).'/> '.$this->l('Yes').'</label>
						<label class="t" for="disablepager_'.$hookname.'"><i class="fa fa-times"></i>
						<input id="disablepager_'.$hookname.'" name="conf['.$hookname.'][pager]" type="radio" value="0" '. (($confs[$hookname]['pager'] == 1) ? '' : 'checked="checked"' ).'/> '.$this->l('No').'</label>
						<div class="helper"><div class="help">'.$this->l('Show or hide pager on bottom').'</div></div>
					</div>
					</div>
					
					<div class="dashed"><span class="label">'.$this->l('Title').'</span>
					<div class="margin-form">
						<label>'.$this->l('Show Title').': </label>
						<label class="t" for="enabletitle_'.$hookname.'"><i class="fa fa-check"></i>
						<input id="enabletitle_'.$hookname.'" name="conf['.$hookname.'][show_title]" type="radio" value="1" '. (($confs[$hookname]['show_title'] == 1) ? 'checked="checked"' : '' ).'/> '.$this->l('Show').'</label>
						<label class="t" for="disabletitle_'.$hookname.'"><i class="fa fa-times"></i>
						<input id="disabletitle_'.$hookname.'" name="conf['.$hookname.'][show_title]" type="radio" value="0" '. (($confs[$hookname]['show_title'] == 1) ? '' : 'checked="checked"' ).'/> '.$this->l('Hide').'</label>
						<div class="helper"><div class="help">'.$this->l('Hide the title for every slide').'</div></div>
					</div>
					
					<div class="margin-form">
						<label>'.$this->l('Title Position').': </label>
						<select name="conf['.$hookname.'][title_pos]">
							<option value="1" '.(($confs[$hookname]['title_pos'] == 1) ? 'selected="selected"' : '' ).'>'.$this->l('Right').' &nbsp;</option>
							<option value="2" '.(($confs[$hookname]['title_pos'] == 2) ? 'selected="selected"' : '' ).'>'.$this->l('Left').' &nbsp;</option>
						</select>
					</div>
					</div>
					
					<div class="dashed"><span class="label">'.$this->l('Positions').'</span>
					<div class="margin-form">
						<label>'.$this->l('Center Slider').': </label>
						<label class="t" for="centerslide_'.$hookname.'"><i class="fa fa-check"></i>
						<input id="centerslide_'.$hookname.'" name="conf['.$hookname.'][center]" type="radio" value="1" '. ((isset($confs[$hookname]['center']) && $confs[$hookname]['center'] == 1) ? 'checked="checked"' : '' ).'/> '.$this->l('Yes').'</label>
						<label class="t" for="nocenterslide_'.$hookname.'"><i class="fa fa-times"></i>
						<input id="nocenterslide_'.$hookname.'" name="conf['.$hookname.'][center]" type="radio" value="0" '. ((isset($confs[$hookname]['center']) && $confs[$hookname]['center'] == 1) ? '' : 'checked="checked"' ).'/> '.$this->l('No').'</label>
						<div class="helper"><div class="help">'.$this->l('Activate this if you want your slide centered').'</div></div>
					</div>
					<div class="margin-form">
						<label>'.$this->l('Side by side').': </label>
						<label class="t" for="sideyes_'.$hookname.'"><i class="fa fa-check"></i>
						<input id="sideyes_'.$hookname.'" name="conf['.$hookname.'][side]" type="radio" value="1" '. ((isset($confs[$hookname]['side']) && $confs[$hookname]['side'] == 1) ? 'checked="checked"' : '' ).'/> '.$this->l('Yes').'</label>
						<label class="t" for="sideno_'.$hookname.'"><i class="fa fa-times"></i>
						<input id="sideno_'.$hookname.'" name="conf['.$hookname.'][side]" type="radio" value="0" '. ((isset($confs[$hookname]['side']) && $confs[$hookname]['side'] == 1) ? '' : 'checked="checked"' ).'/> '.$this->l('No').'</label>
						<div class="helper"><div class="help">'.$this->l('For this option to work you need 2 or more sliders with this option activated in the same hook and make the sliders small enough to fill the available space. This may also help if you need the slider side by side with another block.').'</div></div>
					</div>
					<div class="margin-form">
						<label>'.$this->l('Horizontal space').': </label>
						<input class="config" size="2" type="number" name="conf['.$hookname.'][hspace]" value="'.(isset($confs[$hookname]['hspace']) ? $confs[$hookname]['hspace'] : 0).'" />
					</div>
					<div class="margin-form">
						<label>'.$this->l('Vertical space').': </label>
						<input class="config" size="2" type="number" name="conf['.$hookname.'][vspace]" value="'.(isset($confs[$hookname]['vspace']) ? $confs[$hookname]['vspace'] : 0).'" />
					</div>
					</div>
					
					<div class="dashed"><span class="label">'.$this->l('Carousel').'</span>
					<div class="margin-form">
						<label>'.$this->l('Max Slides').': </label>
						<input class="config" size="2" type="number" name="conf['.$hookname.'][max_slides]" value="'.(isset($confs[$hookname]['max_slides']) ? $confs[$hookname]['max_slides'] : 4).'" />
						<div class="helper"><div class="help">'.$this->l('Maximum number of slides in carousel mode').'</div></div>
					</div>
					<div class="margin-form">
						<label>'.$this->l('Min Slides').': </label>
						<input class="config" size="2" type="number" name="conf['.$hookname.'][min_slides]" value="'.(isset($confs[$hookname]['min_slides']) ? $confs[$hookname]['min_slides'] : 1).'" />
						<div class="helper"><div class="help">'.$this->l('Minimum number of slides in carousel mode if there is not enough space (responsive)').'</div></div>
					</div>
					<div class="margin-form">
						<label>'.$this->l('Slide distance').': </label>
						<input class="config" size="2" type="number" name="conf['.$hookname.'][margin]" value="'.(isset($confs[$hookname]['margin']) ? $confs[$hookname]['margin'] : 0).'" /> px
						<div class="helper"><div class="help">'.$this->l('Distance in pixel between slides.').'</div></div>
					</div>
					</div>
					
						<input class="button centered" type="submit" value="'.$this->l('Save').'" name="updateConfiguration" />
					
					</fieldset><br/>
				<strong>
					<a href="'.AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite($this->name).'&addSlide&hook='.$hookname.'">
						<i class="fa fa-plus-circle addslide"></i> '.$this->l('Add Slide').'
					</a>
				</strong>';
				
				/* Gets Slides from stored array*/
				$slides = $slideArray[$hookname];
				
				/* Display notice if there are no slides yet */
				if (!$slides)
					$this->_html .= '<p style="margin-left: 40px;">'.$this->l('You have not yet added any slides.').'</p>';
				else /* Display slides */
				{
					$this->_html .= '
					<div id="slidesContent_'.$hookname.'" class="slideList">
						<ul class="slides">';
					$pos = 0;

					foreach ($slides as $slide)
					{
						$pos++;
						$this->_html .= '
							<li id="slides_'.$slide['id_slide'].'"><i class="fa fa-bars list"></i>
								<img class="thumb" src="'.__PS_BASE_URI__.'modules/'.$this->module->name.($slide['image'] != '' ? '/images/thumb_'.$slide['image'] : '/css/img/nolanguage.png').'" width="50" height="40" />
								<strong>'.$pos.' : </strong> '.$slide['title'].'
								<p class="icons" >'.
									$this->displayStatus($slide['id_slide'], $slide['active'], $hookname).'
									<a href="'.AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite($this->name).'&id_slide='.(int)($slide['id_slide']).'&hook='.$hookname.'" title="'.$this->l('Edit').'"><i class="fa fa-pencil"></i></a>
									<a href="'.AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite($this->name).'&delete_id_slide='.(int)($slide['id_slide']).'#'.$hookname.'slideConf" title="'.$this->l('Delete').'"><i class="fa fa-trash-o"></i></a>
								</p>
							</li>';
					}
					$this->_html .= '</ul></div>';
				}
				// End fieldset
				$this->_html .= '</fieldset>';
			}
		}
		$this->_html .= '</form>';
		
		$this->_html .= "<script type='text/javascript'>

			$('#hints').click(function(){
				$('#hints').parent().find('.margin-form').slideToggle();
			});
			$('#slide-save').click(function(){
				$('#sliders_config').submit();
			})
		
			$('#sliders_config').submit(function(e){
				//e.preventDefault();
				var valid = true;
				$('input.config').each(function(){
					if ($(this).val() =='' || isNaN($(this).val()) ) {
						valid = false;
					}
				})
				
				if (valid == true) {
					return true;
				} else {
					alert('".$this->l('Insert a valid number')."');
					return false;
				}
				
			})
		</script>";
		$this->_html .= $this->module->getCreds();
	}

	
	public function displayStatus($id_slide, $active, $hookname)
	{
		$title = ((int)$active == 0 ? $this->l('Disabled') : $this->l('Enabled'));
		$img = ((int)$active == 0 ? 'fa-times' : 'fa-check');
		$fakeParam =  ((int)$active == 0 ? 'enable=1' : 'enable=0'); //used to force window reload wen the same slide is activated and than deactivated
		$html = '<a class="changeStatus" data-slide-id="'.(int)$id_slide.'" href="'.AdminController::$currentIndex.
				'&token='.Tools::getAdminTokenLite($this->name).'&changeStatus=1&id_slide='.(int)$id_slide.'&'.$fakeParam.'#'.$hookname.'slideConf" title="'.$title.'"><i class="fa '.$img.'"></i></a>';
		return $html;
	}
	private function _postValidation()
	{
		$errors = array();

		if (Tools::isSubmit('changeStatus'))
		{
			if (!Validate::isInt(Tools::getValue('id_slide')))
				$errors[] = $this->l('Invalid slide');
		}
		
		/* Validation for Slide */
		elseif (Tools::isSubmit('submitSlide'))
		{
			/* Checks state (active) */
			if (!Validate::isInt(Tools::getValue('active_slide')) || (Tools::getValue('active_slide') != 0 && Tools::getValue('active_slide') != 1))
				$errors[] = $this->l('Invalid slide state');
			/* Checks position */
			if (!Validate::isInt(Tools::getValue('position')) || (Tools::getValue('position') < 0))
				$errors[] = $this->l('Invalid slide position');
			/* If edit : checks id_slide */
			if (Tools::isSubmit('id_slide'))
			{
				if (!Validate::isInt(Tools::getValue('id_slide')) && !$this->slideExists(Tools::getValue('id_slide')))
					$errors[] = $this->l('Invalid id_slide');
			}
			/* Checks title/url/legend/description/image */
			$languages = Language::getLanguages(false);
			foreach ($languages as $language)
			{
				if (Tools::strlen(Tools::getValue('title_'.$language['id_lang'])) > 255)
					$errors[] = $this->l('The title is too long.');
				if (Tools::strlen(Tools::getValue('legend_'.$language['id_lang'])) > 255)
					$errors[] = $this->l('The legend is too long.');
				if (Tools::strlen(Tools::getValue('url_'.$language['id_lang'])) > 255)
					$errors[] = $this->l('The URL is too long.');
				if (Tools::strlen(Tools::getValue('description_'.$language['id_lang'])) > 4000)
					$errors[] = $this->l('The description is too long.');
				if (Tools::strlen(Tools::getValue('url_'.$language['id_lang'])) > 0 && !Validate::isUrl(Tools::getValue('url_'.$language['id_lang'])))
					$errors[] = $this->l('The URL format is not correct.');
				if (Tools::getValue('image_'.$language['id_lang']) != null && !Validate::isFileName(Tools::getValue('image_'.$language['id_lang'])))
					$errors[] = $this->l('Invalid filename');
				if (Tools::getValue('image_old_'.$language['id_lang']) != null && !Validate::isFileName(Tools::getValue('image_old_'.$language['id_lang'])))
					$errors[] = $this->l('Invalid filename');
			}

			/* Checks title/url/legend/description for default lang */
			$id_lang_default = (int)Configuration::get('PS_LANG_DEFAULT');
			
			if (!Tools::isSubmit('has_picture') && (!isset($_FILES['image_'.$id_lang_default]) || empty($_FILES['image_'.$id_lang_default]['tmp_name'])))
				$errors[] = $this->l('The image is not set.');
			if (Tools::getValue('image_old_'.$id_lang_default) && !Validate::isFileName(Tools::getValue('image_old_'.$id_lang_default)))
				$errors[] = $this->l('The image is not set.');
		} /* Validation for deletion */
		elseif (Tools::isSubmit('delete_id_slide') && (!Validate::isInt(Tools::getValue('delete_id_slide')) || !$this->slideExists((int)Tools::getValue('delete_id_slide'))))
			$errors[] = $this->l('Invalid id_slide');

		/* Display errors if needed */
		if (count($errors))
		{
			$this->_html .= $this->displayError(implode('<br />', $errors));
			return false;
		}

		/* Returns if validation is ok */
		return true;
	}
	
	private function _postProcess()
	{
		$errors = array();
		/* Processes Slider */
		if (Tools::isSubmit('changeStatus') && Tools::isSubmit('id_slide'))
		{
			$slide = new HomeSlidePro((int)Tools::getValue('id_slide'));
			if ($slide->active == 0)
				$slide->active = 1;
			else
				$slide->active = 0;
			$res = $slide->update();
			$this->_html .= ($res ? $this->displayConfirmation($this->l('Configuration updated')) : $this->displayError($this->l('The configuration could not be updated.')));
		}
		/* Processes Slide */
		elseif (Tools::isSubmit('submitSlide'))
		{
			//get slide configuration
			$position = Tools::getValue('hook'); 
			$confs = unserialize(Configuration::get('HOMESLIDERPRO_CONFIG'));	
			$configuration = $confs[$position];
			
		
			/* Sets ID if needed */
			if (Tools::getValue('id_slide'))
			{
				$slide = new HomeSlidePro((int)Tools::getValue('id_slide'));
				if (!Validate::isLoadedObject($slide))
				{
					$this->_html .= $this->displayError($this->l('Invalid id_slide'));
					return;
				}
			}
			else
				$slide = new HomeSlidePro();
			
			/* Sets position */
			$slide->position = (int)Tools::getValue('position');
			/* Sets active */
			$slide->active = (int)Tools::getValue('active_slide');
			/* Sets new_window */
			$slide->new_window = (int)Tools::getValue('new_window');
			/* Sets has_area */
			$slide->has_area = (int)Tools::getValue('has_area');
			/* set hook */
			$slide->id_hook = Tools::getValue('hook');
			
			$languages = Language::getLanguages(false);
			
			$id_lang_default = (int)Configuration::get('PS_LANG_DEFAULT');
			$usedefault = false;
			if ((int)Tools::getValue('crosslanguage') == 1) {
				$usedefault = true;
				if ( isset($_FILES['image_'.$id_lang_default]) ) {
					$fileDefaultName = $_FILES['image_'.$id_lang_default]['name'];
					$typeDefault = strtolower(substr(strrchr($fileDefaultName, '.'), 1));
				}
			}

			/* Sets each langue fields */
			
			$salt = sha1(microtime());
			
			$tempAreas = Tools::getValue('areas');
			$areas = array();
			if (!empty($tempAreas)) { // reset index of areas
				foreach ($tempAreas as $lang => $areaData) {
					$n = 0;
					foreach ($areaData as $area) {
						foreach ($area as $i=>$field){
							$area[$i] = $field;
						}
						$areas[$lang][$n] = $area;
						$n++;
					}
				}
			}
			
			foreach ($languages as $language)
			{
				$slide->title[$language['id_lang']] = Tools::getValue('title_'.$language['id_lang']);
				$slide->url[$language['id_lang']] = Tools::getValue('url_'.$language['id_lang']);
				$slide->legend[$language['id_lang']] = Tools::getValue('legend_'.$language['id_lang']);
				$slide->description[$language['id_lang']] = addslashes(Tools::getValue('description_'.$language['id_lang']));
				
				
				$areaLang = $language['id_lang'];
				if (Tools::getValue('areacrosslang') == 1)
					$areaLang = $id_lang_default;
				
				if (isset($areas[$areaLang]))
					$slide->areas[$language['id_lang']] = json_encode($areas[$areaLang], JSON_HEX_QUOT | JSON_HEX_TAG);
				else
					$slide->areas[$language['id_lang']] = '';
								
				$langID = $language['id_lang'];
				if ($usedefault) {
					$langID = $id_lang_default;
				}
				$type = strtolower(substr(strrchr($_FILES['image_'.$langID]['name'], '.'), 1));
				$cleanFileName = str_replace('.'.$type, '', $_FILES['image_'.$langID]['name']);

				/* Uploads image and sets slide only if current language is the same  */
				if ($langID == $language['id_lang']) {
					$imagesize = array();
					$imagesize = @getimagesize($_FILES['image_'.$langID]['tmp_name']);
					if (isset($_FILES['image_'.$langID]) &&
						isset($_FILES['image_'.$langID]['tmp_name']) &&
						!empty($_FILES['image_'.$langID]['tmp_name']) &&
						!empty($imagesize) &&
						in_array(strtolower(substr(strrchr($imagesize['mime'], '/'), 1)), array('jpg', 'gif', 'jpeg', 'png')) &&
						in_array($type, array('jpg', 'gif', 'jpeg', 'png')))
					{
						$fileName = $this->file_newname(_PS_MODULE_DIR_.$this->module->name.'/images/', $cleanFileName, $type, $langID);
						//$temp_name = tempnam(_PS_TMP_IMG_DIR_, 'PS');
						$temp_name = _PS_MODULE_DIR_.$this->module->name.'/images/'.$fileName.'.'.$type;
						if ($error = PerfectResize::validateUpload($_FILES['image_'.$langID])){
							$errors[] = $error;
						} elseif (!$temp_name || !move_uploaded_file($_FILES['image_'.$langID]['tmp_name'], $temp_name)){
							return false;
						} else {
							// *** 1) Initialize / load image
							$resizeObj = new PerfectResize($temp_name);		 
							// *** 2) Resize image (options: exact, portrait, landscape, auto, crop)
							$resizeObj->resizeImage($configuration['width'], $configuration['height'], 'crop');
							// *** 3) Save image
							$resizeObj->saveImage(_PS_MODULE_DIR_.$this->module->name.'/images/resize_'.$fileName.'.'.$type, 90);
							//creaThumb
							$resizeObj->resizeImage(60, 40, 'crop');
							$resizeObj->saveImage(_PS_MODULE_DIR_.$this->module->name.'/images/thumb_'.$fileName.'.'.$type, 90);
						}
						// save image into slide object
						$slide->image[$language['id_lang']] = $fileName.'.'.$type;
					} 
				} elseif ($usedefault) {
					$fileName = $this->file_newname(_PS_MODULE_DIR_.$this->module->name.'/images/', $cleanFileName, $type, $langID);
					$slide->image[$language['id_lang']] = $fileName.'.'.$typeDefault;
				} elseif (Tools::getValue('image_old_'.$language['id_lang']) != '') {
					$slide->image[$language['id_lang']] = Tools::getValue('image_old_'.$language['id_lang']);
				}
			}

			/* Processes if no errors  */
			if (!$errors)
			{
				/* Adds */
				if (!Tools::getValue('id_slide'))
				{
					if (!$slide->add())
						$errors[] = $this->l('The slide could not be added.');
					else
						$this->slide = $slide;
				}
				/* Update */
				elseif (!$slide->update())
					$errors[] = $this->l('The slide could not be updated.');
			}
		} /* Deletes */
		elseif (Tools::isSubmit('delete_id_slide'))
		{
			$slide = new HomeSlidePro((int)Tools::getValue('delete_id_slide'));
			$res = $slide->delete();
			if (!$res)
				$this->_html .= $this->displayError('Could not delete');
			else
				$this->_html .= $this->displayConfirmation($this->l('Slide deleted'));
		} 
		/** add HOOK Slide **/
		else if (Tools::isSubmit('addHook')) {
			$slideName = Tools::getValue('newSlide');
			$slideName = strtolower(str_replace(' ', '_', $slideName));
			$slideName = preg_replace('/[^a-za-z_\']/', '', $slideName);
			if (!empty($slideName) && $slideName != '') { //check if something is entered in hook name
				if (!in_array($slideName,$this->hook)){ //check if hook name is already used
					$this->hook[] = $slideName;
					Configuration::updateValue('HOMESLIDERPRO_HOOKS', serialize($this->hook));
					$config = unserialize(Configuration::get('HOMESLIDERPRO_CONFIG'));
					$config[$slideName] = $this->defaultConf;
					Configuration::updateValue('HOMESLIDERPRO_CONFIG', serialize($config));
				} else
					$errors[] = $this->l('Slider name already used.');
			} else {
				$errors[] = $this->l('Slider name cannot be empty.');
			}
			
		}
		/** remove HOOK SLIDE **/
		else if (Tools::isSubmit('deleteHook')) {
			$choiches = Tools::getValue('hooksetup');
			if (!empty($choiches) && is_array($choiches)) {
				foreach ($choiches as $key=>$hook){
					$this->module->removeCatHook($hook);
					$slides = $this->module->getSlides(null, $hook);
					if ($slides){
						foreach ($slides as $slide){
							$slide = new HomeSlidePro((int)$slide['id_slide']);
							$slide->delete();
						}
					}
					unset($this->hook[$key]);
				}
				Configuration::updateValue('HOMESLIDERPRO_HOOKS', serialize($this->hook));
			}
			
		} else if (Tools::isSubmit('updateConfiguration')) {
			$configs = Tools::getValue('conf');
			$newconfigs = serialize($configs);		
			if (Configuration::updateValue('HOMESLIDERPRO_CONFIG', $newconfigs)){
				$this->_html .= $this->displayConfirmation($this->l('Configuration updated'));
			}

		} else if (Tools::isSubmit('saveHooks')){
			$error = false;
			$standardHooks = Tools::getValue('standardHooks');
			Configuration::updateValue('HOMESLIDERPRO_STANDARD', serialize($standardHooks));
			$catHooks = Tools::getValue('cat');
			if ($this->checkduplicates($catHooks)){
				foreach ($catHooks as $hook=>$idCat){
					if (Validate::isInt($idCat)){
						if (!$this->module->saveCatHook($hook, $idCat)){
							$error = true;
						}
					} else if (empty($idCat) || $idCat == ''){
						$this->module->removeCatHook($hook);
					} else {
						$error = true;
					}
				}
			} else {
				$error = true;
				$this->_html .= $this->displayError($this->l('Cannot set the same Category id on multiple hooks!'));
			}
			
			if (!$error)
				$this->_html .= $this->displayConfirmation($this->l('Positions updated'));
		} 

		/* Display errors if needed */
		if (count($errors))
			$this->_html .= $this->displayError(implode('<br />', $errors));
		elseif (Tools::isSubmit('submitSlide') && Tools::getValue('id_slide'))
			$this->_html .= $this->displayConfirmation($this->l('Slide updated'));
		elseif (Tools::isSubmit('submitSlide'))
			$this->_html .= $this->displayConfirmation($this->l('Slide added'));
		
	}
	
	/** if there is already an image with the same name rename it **/
	public function file_newname($path, $filename, $ext, $idlang = ''){
		
		$appendString = $ext;
		$newpath = $path.'/'.$filename.$appendString;
		$newname = $filename;
		$counter = 0;
		$splitResult = array();
		while (file_exists($newpath)) {
			   $newname = $filename .'c'. $counter;
			   $newpath = $path.'/'.$newname.$appendString;
			   $counter++;
		}

		return $newname;
	}
	
	public function checkduplicates($array = array()){
		if (!empty($array) && is_array($array)) {
			$temp = array();
			foreach ($array as $key=>$value) {
				if (!in_array($value, $temp)){
					if ($value != '')
						$temp[$key] = $value;
				} else
					return false;
			}
			return true;
		}
	}
	
	public function slideExists($id_slide)
	{
		$req = 'SELECT hs.`id_homeslider_slides` as id_slide
				FROM `'._DB_PREFIX_.'homesliderpro` hs
				WHERE hs.`id_homeslider_slides` = '.(int)$id_slide;
		$row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($req);
		return ($row);
	}
	
	private function _displayAddForm()
	{
		$confs = unserialize(Configuration::get('HOMESLIDERPRO_CONFIG'));
		/* Sets Slide : depends if edited or added */
		
		//print_r($this->slide);
		
		
		$slide = null;
		if (Tools::isSubmit('id_slide') && $this->slideExists((int)Tools::getValue('id_slide')))
			$slide = new HomeSlidePro((int)Tools::getValue('id_slide'));

				
		
		$hook = Tools::getValue('hook');
		
		//$this->_html .= '<pre>'. print_r($slide, true) .'</pre>';
		
		
		/* Checks if directory is writable */
		if (!is_writable(_PS_MODULE_DIR_.$this->module->name))
			parent::DisplayWarning(sprintf($this->l('Modules %s must be writable (CHMOD 755 / 777)'), $this->name));

		/* Gets languages and sets which div requires translations */
		$id_lang_default = (int)Configuration::get('PS_LANG_DEFAULT');
		$languages = Language::getLanguages(false);
		//$divLangName = 'image¤title¤url¤legend¤description';
		$this->_html .= '<script type="text/javascript">id_language = Number('.$id_lang_default.');</script>';

		/* Form */
		$this->_html .= '<form id="single-slide" class="clearfix" action="'.Tools::safeOutput($_SERVER['REQUEST_URI']).'" method="post" enctype="multipart/form-data">';
		
		
		/* Fieldset Upload */
		$this->_html .= '<div class="clearfix">
		<fieldset class="single-fieldset">
			<legend><span class="fa fa-plus-circle addslide"></span> 1 - '.$this->l('Upload your slide').'</legend>';
		
		/** carica la stessa immagine per tutte le lingue?? */
		$this->_html .= '<label>'.$this->l('Same image for all languages?').' : </label>
			<div class="margin-form">
			<input '.(Tools::getValue('id_slide') == '' ? 'checked="checked"' : '') .' type="checkbox" id="crosslanguage" name="crosslanguage" style="margin:5px 0 0;" value="1"/>
			<div class="helper"><div class="help">'.$this->l('If checked the next image you upload for your DEFAULT LANGUAGE will be copied over for all languages!').'</div></div>
			<input type="hidden" id="langID" name="langID" value="'.$id_lang_default.'" />
			</div>';
		/* Image */
		$this->_html .= '<label>'.$this->l('Select a file:').' * </label>
			<div id="imgchooser" class="margin-form">'.(count($languages)>1 ? '<div class="translatable">' : '');
		
		//$this->_html .= '<pre style="width:400px;background:#fff;">'.print_r($slide->areas,true).'</pre>';
		$areas = array();
		foreach ($languages as $language)
		{
			
			if ($slide && isset($slide->areas[$language['id_lang']]))
				$areas = json_decode($slide->areas[$language['id_lang']]);
			
			//$this->_html .= '<pre style="width:400px;background:#fff;">'.print_r($areas,true).'</pre>';
			
			$this->_html .= '<div class="lang_'.$language['id_lang'].'" id="image_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $id_lang_default ? 'block' : 'none').';float: left;">';
			$this->_html .= '<input type="file" name="image_'.$language['id_lang'].'" id="image_'.$language['id_lang'].'" size="30" value="'.(isset($slide->image[$language['id_lang']]) ? $slide->image[$language['id_lang']] : '').'"/>';
			
			/* Sets image as hidden in case it does not change */
			if ($slide && isset($slide->image[$language['id_lang']]))
				$this->_html .= '<input type="hidden" name="image_old_'.$language['id_lang'].'" value="'.($slide->image[$language['id_lang']]).'" id="image_old_'.$language['id_lang'].'" />';
				
			/* Display image */
			
			if ( $slide && isset($slide->image[$language['id_lang']]) && !empty($slide->image[$language['id_lang']]) ) {
				$imgUrl = '';
				if ( file_exists(dirname(dirname(dirname(__FILE__))).'/images/resize_'.$slide->image[$language['id_lang']]) ) { //new naming check				
					$imgUrl = $this->image_folder.'resize_'.$slide->image[$language['id_lang']];
				} elseif (file_exists(dirname(dirname(dirname(__FILE__))).'/images/'.$slide->image[$language['id_lang']]) ){
					$imgUrl = $this->image_folder.$slide->image[$language['id_lang']];
				}
				$this->_html .= '<input type="hidden" name="has_picture" value="1" />
					<span id="fixer_'.$language['id_lang'].'" class="areafixer">
						<img data-areanum="'.count($areas).'" data-lang="'.$language['id_lang'].'" class="preview" src="'.$imgUrl.'" width="'.($confs[$hook]['width']/2).'" height="'.($confs[$hook]['height']/2).'" alt=""/>
						<div id="area_prev_'.$language['id_lang'].'">';
				if (!empty($areas)) {
					foreach ($areas as $k=>$area) {
						$this->_html .= '<div class="area_prev" id="prev_'.$k.'_l_'.$language['id_lang'].'" data-tab="area_'.$k.'_l_'.$language['id_lang'].'" ';
						$this->_html .= ' style="left:'.$area->left.'%;top:'.$area->top.'%;width:'.$area->selWidth.'%;height:'.$area->selHeight.'%;"';
						$this->_html .= '><div class="iconA"></div></div>';
					}
				}
				$this->_html .= '</div></span>';
			} else
				$this->_html .= '<input type="hidden" name="has_picture" value="0" />';
			$this->_html .= '</div>';
		}
		
		$jsonize = array();
		if ($slide)
			foreach ($slide->areas as $lang => $areas){
				$jsonize[$lang] = json_decode($areas);
			}
		
		//$this->_html .= '<pre>'.print_r($jsonize,true).'</pre>';

		$this->_html .= '<script type="text/javascript">
			var areaData = '.($jsonize ? json_encode($jsonize) : '{}').';
			var tabEmptyName = "'.$this->tabEmptyName.'";
			var areaTitleLabel = "'.$this->areaTitleLabel.'";
			var areaUrlLabel = "'.$this->areaUrlLabel.'";
			var areaButtLabel = "'.$this->areaButtLabel.'";
			var areaDescLabel = "'.$this->areaDescLabel.'";
			var areaStyleSimple = "'.$this->areaStyleSimple.'";
			var areaStyleBlock = "'.$this->areaStyleBlock.'";
			var areaColorLight = "'.$this->areaColorLight.'";
			var areaColorDark = "'.$this->areaColorDark.'";
			var areaColorTrans = "'.$this->areaColorTrans.'";
			var areaStyleLegend = "'.$this->areaStyleLegend.'";
			var areaColorLegend = "'.$this->areaColorLegend.'";
		
			$(document).ready(function(){
				manageAreas(areaData);
			})
			
		</script>';
		
		$this->_html .= (count($languages)>1 ? '</div>' : '');
		$this->_html .= '<div class="areacontrols">
		
			<a class="button" href="#" id="add_area">'.$this->l('Draw Area').'</a> 
			<a class="button" id="save_area" href="#">'.$this->l('Done!').'</a>
			<span class="helper"><span class="help">'.$this->l('Click "Draw area" button and drag your mouse over the picture. You can move and resize the area as much as you want. Once done click "Done!"').'</span></span>
			<br/>
			<label>'.$this->l('Same areas for all languages?').' <input type="checkbox" name="areacrosslang" value="1"/>
			<div class="helper"><div class="help">'.$this->l('If checked EVERY area from your DEFAULT LANGUAGE will be copied over for all languages!').'</div></div></label>
			</div>';
		/* End Fieldset Upload */
		$this->_html .= '</div></fieldset>'; //end imgchooser
		
		
		/* map tabs */		
		$this->_html .= '<fieldset class="single-fieldset"><legend>'.$this->l('Areas').'</legend>'.(count($languages)>1 ? '<div class="area_config translatable">' : '');
		
		foreach ($languages as $language) {
			if ($slide && isset($slide->areas[$language['id_lang']]))
				$areas = json_decode($slide->areas[$language['id_lang']]);
			
			
			$this->_html .= '<div class="mapsTabs lang_'.$language['id_lang'].'" id="tabsLang_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $id_lang_default ? 'block' : 'none').';">';
			
			$this->_html .= '<ul class="slideAreaTabs">'; // tabs
			if (!empty($areas)) {
				foreach ($areas as $k=>$area) {
						$this->_html .= '<li class="tab" data-lang="'.$language['id_lang'].'" data-num="'.$k.'" data-prev="prev_'.$k.'_l_'.$language['id_lang'].'" >';
						$this->_html .= '<a href="#area_'.$k.'_l_'.$language['id_lang'].'">'.($area->title ? $area->title : $this->tabEmptyName).'</a>';
						$this->_html .= '</li>';
				}
			}
			
			
			$this->_html .= '</ul>';
			if (!empty($areas)) {
				//tabs content
				foreach ($areas as $k=>$area) {
					$this->_html .= '<div id="area_'.$k.'_l_'.$language['id_lang'].'">'; //tab contents
					//text fields
					$this->_html .= '<div class="textfields">';
					$this->_html .= '<label>'.$this->areaTitleLabel.'<input autocomplete="off" type="text" class="areaTitle" name="areas['.$language['id_lang'].']['.$k.'][title]" value="'.($area->title ? $area->title : '').'" /></label>';
					$this->_html .= '<label>'.$this->areaUrlLabel.'<input autocomplete="off" type="text" name="areas['.$language['id_lang'].']['.$k.'][url]" value="'.($area->url ? $area->url:'').'" /></label>';
					$this->_html .= '<label>'.$this->areaButtLabel.'<input autocomplete="off" type="text" name="areas['.$language['id_lang'].']['.$k.'][button]" value="'.($area->button ? $area->button:'').'" /></label>';
					$this->_html .= '<label>'.$this->areaDescLabel.'<textarea autocomplete="off" class="areadesc" name="areas['.$language['id_lang'].']['.$k.'][description]" >'.($area->description ? $area->description : '').'</textarea></label>';
					$this->_html .= '</div>';
					//style fields
					$this->_html .= '<div class="stylfields">';
					$this->_html .= '<fieldset><legend>'.$this->areaStyleLegend.'</legend>';
					$this->_html .= '<label>'.$this->areaStyleSimple.'<input type="radio" name="areas['.$language['id_lang'].']['.$k.'][style]" value="simple" '.(isset($area->style) && $area->style == 'simple' ? 'checked="checked"' : '').' /></label>';
					$this->_html .= '<label>'.$this->areaStyleBlock.'<input type="radio" name="areas['.$language['id_lang'].']['.$k.'][style]" value="block" '.(isset($area->style) && $area->style == 'block' ? 'checked="checked"' : '').'/></label></fieldset>';
					$this->_html .= '<fieldset><legend>'.$this->areaColorLegend.'</legend>';
					$this->_html .= '<label>'.$this->areaColorLight.'<input type="radio" name="areas['.$language['id_lang'].']['.$k.'][color]" value="light" '.(isset($area->color) && $area->color == 'light' ? 'checked="checked"' : '').'/></label>';
					$this->_html .= '<label>'.$this->areaColorDark.'<input type="radio" name="areas['.$language['id_lang'].']['.$k.'][color]" value="dark" '.(isset($area->color) && $area->color == 'dark' ? 'checked="checked"' : '').'/></label>';
					$this->_html .= '<label>'.$this->areaColorTrans.'<input type="radio" name="areas['.$language['id_lang'].']['.$k.'][color]" value="transparent" '.(isset($area->color) && $area->color == 'transparent' ? 'checked="checked"' : '').'/></label></fieldset>';
					$this->_html .= '</div>';
					// hidden fields
					$this->_html .= '<div class="hiddens">';
					$this->_html .= '<input type="hidden" name="areas['.$language['id_lang'].']['.$k.'][left]" value="'.$area->left.'"/>';
					$this->_html .= '<input type="hidden" name="areas['.$language['id_lang'].']['.$k.'][top]" value="'.$area->top.'"/>';
					$this->_html .= '<input type="hidden" name="areas['.$language['id_lang'].']['.$k.'][selWidth]" value="'.$area->selWidth.'"/>';
					$this->_html .= '<input type="hidden" name="areas['.$language['id_lang'].']['.$k.'][selHeight]" value="'.$area->selHeight.'"/>';
					$this->_html .= '</div>';
					$this->_html .= '</div>';
				}
				
			}
			$this->_html .= '</div>';
		}
		$this->_html .= (count($languages)>1 ? '</div>' : '').'<div class="areabuttons"><!-- <a href="#" id="editArea">'.$this->l('Edit area').'</a>--> <a href="#" id="removeArea" class="button">'.$this->l('Remove area').'</a></div></fieldset></div>';

		/* Fieldset edit/add */
		$this->_html .= '<fieldset class="single-fieldset">';
		if (Tools::isSubmit('addSlide')) /* Configure legend */
			$this->_html .= '<legend><span class="fa fa-plus-circle addslide"></span> 2 - '.$this->l('Configure your slide').'</legend>';
		elseif (Tools::isSubmit('id_slide')) /* Edit legend */
			$this->_html .= '<legend><img src="'.__PS_BASE_URI__.'modules/'.$this->module->name.'/logo.png" alt="" /> 2 - '.$this->l('Edit your slide').'</legend>';
		/* Sets id slide as hidden */
		if ($slide && Tools::getValue('id_slide'))
			$this->_html .= '<input type="hidden" name="id_slide" value="'.$slide->id.'" id="id_slide" />';
		/* Sets position as hidden */
		$this->_html .= '<input type="hidden" name="position" value="'.(($slide != null) ? ($slide->position) : ($this->getNextPosition())).'" id="position" />';
		
		/* Form content */
		
		/***** Title ******/
		$this->_html .= '<label>'.$this->l('Title:').'</label><div class="margin-form '.(count($languages)>1 ? 'translatable' : '').'">';
		foreach ($languages as $language)
		{
			$this->_html .= '
					<div class="lang_'.$language['id_lang'].'" id="title_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $id_lang_default ? 'block' : 'none').';float: left;">
						<input type="text" name="title_'.$language['id_lang'].'" id="title_'.$language['id_lang'].'" size="30" value="'.(isset($slide->title[$language['id_lang']]) ? $slide->title[$language['id_lang']] : '').'"/>
					</div>';
		}

		$this->_html .= '</div>';

		/* URL */
		$this->_html .= '<label>'.$this->l('URL:').'</label><div class="margin-form '.(count($languages)>1 ? 'translatable' : '').'">';
		foreach ($languages as $language)
		{
			$this->_html .= '
					<div class="lang_'.$language['id_lang'].'" id="url_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $id_lang_default ? 'block' : 'none').';float: left;">
						<input type="text" name="url_'.$language['id_lang'].'" id="url_'.$language['id_lang'].'" size="30" value="'.(isset($slide->url[$language['id_lang']]) ? $slide->url[$language['id_lang']] : '').'"/>
					</div>';
		}

		$this->_html .= '</div>';
		
		/* New Window */

		$this->_html .= '
		<label for="new_window_on">'.$this->l('Open url in New Window:').'</label>
		<div class="margin-form">
			<label class="t" for="new_window_on"><i class="fa fa-check"></i>
			<input type="radio" name="new_window" id="new_window_on" '.( ( isset($slide->new_window) && (int)$slide->new_window == 1 ) ? ' checked="checked" ' : '').' value="1" />'.$this->l('Yes').'</label>
			<label class="t" for="new_window_off"><i class="fa fa-times"></i>
			<input type="radio" name="new_window" id="new_window_off" '. ( ( (isset($slide->new_window) && (int)$slide->new_window == 0) || !isset($slide->new_window) ) ? 'checked="checked" ' : '').' value="0" />'.$this->l('No').'</label>
		</div>';

		/***** Legend ********/
		$this->_html .= '<label>'.$this->l('Legend:').'</label><div class="margin-form '.(count($languages)>1 ? 'translatable' : '').'">';
		foreach ($languages as $language)
		{
			$this->_html .= '
					<div class="lang_'.$language['id_lang'].'" id="legend_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $id_lang_default ? 'block' : 'none').';float: left;">
						<input type="text" name="legend_'.$language['id_lang'].'" id="legend_'.$language['id_lang'].'" size="30" value="'.(isset($slide->legend[$language['id_lang']]) ? $slide->legend[$language['id_lang']] : '').'"/>
					</div>';
		}

		$this->_html .= '</div>';
		
		

		/* Description */
		$this->_html .= '
		<label>'.$this->l('Description:').' </label>
		<div class="margin-form '.(count($languages)>1 ? 'translatable' : '').'">';
		foreach ($languages as $language)
		{
			$this->_html .= '<div class="lang_'.$language['id_lang'].'" id="description_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $id_lang_default ? 'block' : 'none').';float: left;">
				<textarea name="description_'.$language['id_lang'].'" rows="10" cols="29">'.(isset($slide->description[$language['id_lang']]) ? $slide->description[$language['id_lang']] : '').'</textarea>
			</div>';
		}

		$this->_html .= '</div><div class="clear"></div><br />';

		/* Active */
		$active = false;
		if (isset($slide->active)){
			if ($slide->active == 1)
				$active = true;
		} else {
			$active = true;
		}
		$this->_html .= '
		<label for="active_on">'.$this->l('Active:').'</label>
		<div class="margin-form">
			<label class="t" for="active_on"><i class="fa fa-check">
			<input type="radio" name="active_slide" id="active_on" '.($active ? ' checked="checked" ' : '').' value="1" /></i>'.$this->l('Yes').'</label>
			<label class="t" for="active_off"><i class="fa fa-times"></i>
			<input type="radio" name="active_slide" id="active_off" '.(!$active ? 'checked="checked" ' : '').' value="0" />'.$this->l('No').'</label>
		</div>';

		/* Save */
		$this->_html .= '
		<p class="center">
			<input type="submit" class="button" name="submitSlide" value="'.$this->l('Save').'" />
			<input type="hidden" name="id_hook" value="'.Tools::getValue('hook').'" />
			<input type="hidden" id="has_area" name="has_area" value="'.($slide && $slide->has_area ? $slide->has_area : '0').'" />
			<a class="button" href="'.AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite($this->name).'">'.$this->l('Cancel').'</a>
		</p>';


		/* End of fieldset & form */
		$this->_html .= '
			<p><sup>*</sup> '.$this->l('Required fields').'</p>
			</fieldset>
		</form>';
		$this->_html .= "<script type='text/javascript'>
			$('#single-save').click(function(){
				$('#single-slide input[name=submitSlide]').trigger('click');
			})
			var languages = new Array();";
			foreach ($languages as $k => $language){
			$this->_html .= 'languages['.$k.'] = {
					id_lang: "'.$language['id_lang'].'",
					iso_code: "'.$language['iso_code'].'",
					name: "'.$language['name'].'",
					is_default: "'.($language['id_lang'] == $id_lang_default ? '1' : '0').'"
				};';
			}
			$this->_html .= 'displayFlags(languages, '.$id_lang_default.', 0);
			
			$(document).ready(function(){
				function flagposition(){
					$(".language_flags").each(function(){
						var offset = $(this).prev().position();
						$(this).css({
							top:offset.top+20+"px",
							left:offset.left+20+"px"
						})
					})
				}
				flagposition();
				$(window).bind("scroll resize", function(){
					flagposition();
				})
			})
			
			
		</script>';
		
	}
	
	public function getNextPosition()
	{
		$row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('
				SELECT MAX(hss.`position`) AS `next_position`
				FROM `'._DB_PREFIX_.'homesliderpro_slides` hss, `'._DB_PREFIX_.'homesliderpro` hs
				WHERE hss.`id_homeslider_slides` = hs.`id_homeslider_slides` AND hs.`id_shop` = '.(int)$this->context->shop->id
		);

		return (++$row['next_position']);
	}
	
	public function displayConfirmation($string)
	{
		$this->confirmations = array();
	 	$output = '
		<div class="module_confirmation conf confirm">
			'.$string.'
		</div>';
		return $output;
	}
	
	public function displayError($error)
	{
	 	$output = '
		<div class="module_error alert error">
			'.$error.'
		</div>';
		//$this->error = true;
		return $output;
	}
}