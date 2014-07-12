<?php

class HomeSlidePro extends ObjectModel
{
	public $title;
	public $description;
	public $url;
	public $legend;
	public $image;
	public $active;
	public $position;
	public $id_hook;
	public $new_window;
	public $has_area;
	public $areas;

	/**
	 * @see ObjectModel::$definition
	 */
	public static $definition = array(
		'table' => 'homesliderpro_slides',
		'primary' => 'id_homeslider_slides',
		'multilang' => true,
		'fields' => array(
			'active' =>			array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true),
			'position' =>		array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => true),
			//'id_hook' =>		array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => true),
			// Lang fields
			'description' =>	array('type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml', 'size' => 4000),
			'title' =>			array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml', 'required' => false, 'size' => 255),
			'legend' =>			array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml', 'required' => false, 'size' => 255),
			'url' =>			array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isUrl', 'required' => false, 'size' => 255),
			'new_window' =>		array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => false),
			'image' =>			array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml', 'size' => 255),
			'has_area' =>		array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true),
			'areas' =>			array('type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml', 'required' => false),
		)
	);

	public	function __construct($id_slide = null, $id_lang = null, $id_shop = null, Context $context = null)
	{
		parent::__construct($id_slide, $id_lang, $id_shop);
	}

	public function add($autodate = true, $null_values = false)
	{
		$context = Context::getContext();
	/*	if ($context->shop->getContext() == Shop::CONTEXT_ALL) { //multishop slider duplication?
			$shops = Shop::getShops();
		}*/
		$id_shop = $context->shop->id;
		$hook = $this->id_hook;
		
		//echo $hook;

		$res = parent::add($autodate, $null_values);
		
		/*if (isset($shops) && !empty($shops)) {
			foreach ($shops as $shop)
				$res &= Db::getInstance()->execute('
				INSERT INTO `'._DB_PREFIX_.'homesliderpro` (`id_shop`, `id_homeslider_slides`, `id_hook`)
				VALUES('.(int)$shop['id_shop'].', '.(int)$this->id.', "'.$hook.'")'
			);
		} else*/
		$res &= Db::getInstance()->execute('
			INSERT INTO `'._DB_PREFIX_.'homesliderpro` (`id_shop`, `id_homeslider_slides`, `id_hook`)
			VALUES('.(int)$id_shop.', '.(int)$this->id.', "'.$hook.'")'
		);
		return $res;
	}
	
	public function update($null_values = false)
	{
		$return = parent::update($null_values);
		return $return;
	}

	public function delete()
	{
		$res = true;

		$images = $this->image;
		foreach ($images as $image)
		{
			if (preg_match('/sample/', $image) === 0)
				if ($image){
					if (file_exists(dirname(__FILE__).'/images/'.$image))
						$res &= @unlink(dirname(__FILE__).'/images/'.$image);
					if (file_exists(dirname(__FILE__).'/images/thumb_'.$image))
						$res &= @unlink(dirname(__FILE__).'/images/thumb_'.$image);
					if (file_exists(dirname(__FILE__).'/images/resize_'.$image))
						$res &= @unlink(dirname(__FILE__).'/images/resize_'.$image);
				}
		}

		$res &= $this->reOrderPositions();

		$res &= Db::getInstance()->execute('
			DELETE FROM `'._DB_PREFIX_.'homesliderpro`
			WHERE `id_homeslider_slides` = '.(int)$this->id
		);

		$res &= parent::delete();
		return $res;
	}
	
	public function reOrderPositions()
	{
		$id_slide = $this->id;
		$context = Context::getContext();
		$id_shop = $context->shop->id;

		$max = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
			SELECT MAX(hss.`position`) as position
			FROM `'._DB_PREFIX_.'homesliderpro_slides` hss, `'._DB_PREFIX_.'homesliderpro` hs
			WHERE hss.`id_homeslider_slides` = hs.`id_homeslider_slides` AND hs.`id_shop` = '.(int)$id_shop
		);

		if ((int)$max == (int)$id_slide)
			return true;

		$rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
			SELECT hss.`position` as position, hss.`id_homeslider_slides` as id_slide
			FROM `'._DB_PREFIX_.'homesliderpro_slides` hss
			LEFT JOIN `'._DB_PREFIX_.'homesliderpro` hs ON (hss.`id_homeslider_slides` = hs.`id_homeslider_slides`)
			WHERE hs.`id_shop` = '.(int)$id_shop.' AND hss.`position` > '.(int)$this->position
		);

		foreach ($rows as $row)
		{
			$current_slide = new HomeSlidePro($row['id_slide']);
			--$current_slide->position;
			$current_slide->update();
			unset($current_slide);
		}

		return true;
	}

}
