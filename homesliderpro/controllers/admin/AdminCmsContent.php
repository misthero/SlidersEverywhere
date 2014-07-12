<?php

class AdminCmsController extends AdminCmsControllerCore {
	public function __construct()
	{
		parent::__construct();
	}

	public function renderView()
	{		

		echo 'rendeview';
	}
	
	public function renderForm()
	{
		
		if (!$this->loadObject(true))
			return;
		
		if (Validate::isLoadedObject($this->object))
			$this->display = 'edit';
		else
			$this->display = 'add';

		$this->toolbar_btn['save-and-preview'] = array(
			'href' => '#',
			'desc' => $this->l('Save and preview')
		);
		$this->toolbar_btn['save-and-stay'] = array(
			'short' => 'SaveAndStay',
			'href' => '#',
			'desc' => $this->l('Save and stay'),
		);
		$this->initToolbar();

		$categories = CMSCategory::getCategories($this->context->language->id, false);
		$html_categories = CMSCategory::recurseCMSCategory($categories, $categories[0][1], 1, $this->getFieldValue($this->object, 'id_cms_category'), 1);
		
		$galleries = $this->getSliders();

		$this->fields_form = array(
			'tinymce' => true,
			'legend' => array(
				'title' => $this->l('CMS Page'),
				'image' => '../img/admin/tab-categories.gif'
			),
			'input' => array(
				// custom template
				array(
					'type' => 'select_category',
					'label' => $this->l('CMS Category'),
					'name' => 'id_cms_category',
					'options' => array(
						'html' => $html_categories,
					),
				),
				array(
					'type' => 'text',
					'label' => $this->l('Meta title:'),
					'name' => 'meta_title',
					'id' => 'name', // for copy2friendlyUrl compatibility
					'lang' => true,
					'required' => true,
					'class' => 'copy2friendlyUrl',
					'hint' => $this->l('Invalid characters:').' <>;=#{}',
					'size' => 50
				),
				array( //syncrea
					'type' => 'select_category',
					'label' => $this->l('Slider'),
					'name' => 'proslider',
					'empty_message' => $this->l('None'),
					'options' => array(                                  // only if type == select
						'html' => $galleries,
                            // key that will be used for each option "value" attribute
					  ),
				),
				array(
					'type' => 'text',
					'label' => $this->l('Meta description'),
					'name' => 'meta_description',
					'lang' => true,
					'hint' => $this->l('Invalid characters:').' <>;=#{}',
					'size' => 70
				),
				array(
					'type' => 'tags',
					'label' => $this->l('Meta keywords'),
					'name' => 'meta_keywords',
					'lang' => true,
					'hint' => $this->l('Invalid characters:').' <>;=#{}',
					'size' => 70,
					'desc' => $this->l('To add "tags" click in the field, write something, and then press "Enter."')
				),
				array(
					'type' => 'text',
					'label' => $this->l('Friendly URL'),
					'name' => 'link_rewrite',
					'required' => true,
					'lang' => true,
					'hint' => $this->l('Only letters and the minus (-) character are allowed')
				),
				array(
					'type' => 'textarea',
					'label' => $this->l('Page content'),
					'name' => 'content',
					'autoload_rte' => true,
					'lang' => true,
					'rows' => 5,
					'cols' => 40,
					'hint' => $this->l('Invalid characters:').' <>;=#{}'
				),
				array(
					'type' => 'radio',
					'label' => $this->l('Displayed:'),
					'name' => 'active',
					'required' => false,
					'class' => 't',
					'is_bool' => true,
					'values' => array(
						array(
							'id' => 'active_on',
							'value' => 1,
							'label' => $this->l('Enabled')
						),
						array(
							'id' => 'active_off',
							'value' => 0,
							'label' => $this->l('Disabled')
						)
					),
				),
			),
			'submit' => array(
				'title' => $this->l('Save'),
				'class' => 'button'
			)
		);

		if (Shop::isFeatureActive())
		{
			$this->fields_form['input'][] = array(
				'type' => 'shop',
				'label' => $this->l('Shop association:'),
				'name' => 'checkBoxShopAsso',
			);
		}

		$this->tpl_form_vars = array(
			'active' => $this->object->active,
			'PS_ALLOW_ACCENTED_CHARS_URL', (int)Configuration::get('PS_ALLOW_ACCENTED_CHARS_URL')
		);
		return AdminController::renderForm();
	}
	
	public function getSliders()
	{
		if (Module::isInstalled('homesliderpro')) {
			$hooks = unserialize(Configuration::get('HOMESLIDERPRO_HOOKS'));
		
		//	$sql = 'SELECT * FROM `'._DB_PREFIX_.'homesliderpro`';
			$cmsPage = Tools::getValue('id_cms');
			//echo $cmsPage;
			$html = '<option value="0">'.$this->l('None').'</option>';
			if (!empty($hooks)) {
			//$slider = Db::getInstance()->executeS('SELECT DISTINCT id_hook FROM `'._DB_PREFIX_.'homesliderpro`');
				if ($cmsPage != '') {
					foreach ($hooks as $hook) {
						if ($sel = Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'cms` WHERE proslider = "'.$hook.'" AND id_cms='.$cmsPage)) {
							$selected = 'selected="selected"';
						} else {
							$selected = '';
						}
						$html.='<option '.$selected.' value="'.$hook.'">'.$hook.'</option>';
					}
				} else {
					foreach ($slider as $slide) {
						$selected = '';
						$html.='<option '.$selected.' value="'.$hook.'">'.$hook.'</option>';
					}
				}
			} 
			return $html;
		}
		return false;
	}
	
	public function viewAccess() {
		return true;
	}
	
	public function checkAccess() {
		return true;
	}
	public function postProcess() {
		//echo ' - PostProcess';
		return parent::postProcess();
	}
	public function display() {
		return true;
	}
	public function initHeader() {
		return true;
	}
	public function initFooter() {
		return true;
	}
	public function initCursedPage() {
		return true;
	}
	public function setMedia() {
		return true;
	}
	
	public function redirect() {
		return parent::redirect();
	}
	
}

class CMS extends CMSCore
	{
		
		public $proslider;

		/**
		 * @see ObjectModel::$definition
		 */
		public static $definition = array(
			'table' => 'cms',
			'primary' => 'id_cms',
			'multilang' => true,
			'fields' => array(
				'id_cms_category' => 	array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
				'position' => 			array('type' => self::TYPE_INT),
				'active' => 			array('type' => self::TYPE_BOOL),

				// Lang fields
				'meta_description' => 	array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255),
				'meta_keywords' => 		array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255),
				'meta_title' =>			array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 128),
				'link_rewrite' => 		array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isLinkRewrite', 'required' => true, 'size' => 128),
				'content' => 			array('type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isString', 'size' => 3999999999999),
				'proslider' => 			array('type' => self::TYPE_STRING, 'validate' => 'isConfigName'),
			),
		);
}

