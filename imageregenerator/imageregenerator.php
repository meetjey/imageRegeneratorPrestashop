<?php
if (!defined('_PS_VERSION_'))
	exit;

class ImageRegenerator extends Module
{
	public function __construct()
	{
		$this->bootstrap = true;
		$this->name = 'imageregenerator';
		$this->tab = 'administration';
		$this->version = '1.0';
		$this->author = 'Jérémy Besson';
		$this->need_instance = 0;
		$this->ps_versions_compliancy = array('min' => '1.5', 'max' => '1.6');
		$this->dependencies = null;

		parent::__construct();

		$this->displayName = $this->l('Image Regenerator');
		$this->description = $this->l('Use ajax to regenerate image safely.');

		$this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

		if (!Configuration::get('IMAGEREGENERATOR'))      
			$this->warning = $this->l('No name provided');
	}

	public function install()
	{
		if (parent::install() == false || !$this->registerHook('actionAdminControllerSetMedia'))
			return false;
		return true;
	}

	public function hookActionAdminControllerSetMedia()
	{
		if((Tools::getValue('controller') == 'AdminModules') && (Tools::getValue('configure') == $this->name))
			$this->context->controller->addJS(($this->_path).'js/ir-main.js');
	}

	public function getContent()
	{
		$output = null;
		if (Tools::isSubmit('image_regenerator_queue'))
		{
			$image_regenerator_queue = strval(Tools::getValue('image_regenerator_queue'));
			$image_regenerator_queue_what = strval(Tools::getValue('image_regenerator_queue_what'));
			if($image_regenerator_queue_what && !empty($image_regenerator_queue_what)){
				Configuration::updateValue('image_regenerator_queue_what', $image_regenerator_queue_what);
			}
			if($image_regenerator_queue && !empty($image_regenerator_queue)){
				Configuration::updateValue('image_regenerator_queue', $image_regenerator_queue);
				$output .= $this->displayConfirmation($this->l('Queue saved'));
			}
		}else if(Tools::isSubmit('image_regenerator_reinit')){
			$image_regenerator_reinit = strval(Tools::getValue('image_regenerator_reinit'));
			if($image_regenerator_reinit && !empty($image_regenerator_reinit)){
				Configuration::updateValue('image_regenerator_queue', '');
				$output .= $this->displayConfirmation($this->l('Queue cleared'));
			}
		}
		return $output.$this->displayForm();
	}

	public function displayForm(){
		$images = Image::getAllImages();
		$r = '<div class="bootstrap">';
		$config = Configuration::get('image_regenerator_queue');
		$image_regenerator_queue_what = Configuration::get('image_regenerator_queue_what');
		$image_regenerator_queue_what = (empty($image_regenerator_queue_what))? "null" : '"'.$image_regenerator_queue_what.'"';
		if($config){
			$list = json_decode($config,true);
			if(!is_array($list) || count($list)==0){
				$list = false;
			}
		}
		else
			$list = false;

		if(!$list){
			$list = array();
			$process =
			array(
				array('type' => 'categories', 'dir' => _PS_CAT_IMG_DIR_),
				array('type' => 'manufacturers', 'dir' => _PS_MANU_IMG_DIR_),
				array('type' => 'suppliers', 'dir' => _PS_SUPP_IMG_DIR_),
				array('type' => 'scenes', 'dir' => _PS_SCENE_IMG_DIR_),
				array('type' => 'products', 'dir' => _PS_PROD_IMG_DIR_),
				array('type' => 'stores', 'dir' => _PS_STORE_IMG_DIR_)
				);
			foreach ($process as $proc)
			{
				$list[$proc["type"]] = array("todo"=>array(),"done"=>array(),"errors"=>array());
				if($proc["type"]=="products"){
					foreach($images as $img){
						$list["products"]["todo"][] = $img['id_image'];
					}
				}else{
					$scanned_directory = array_diff(scandir($proc['dir']), array('..', '.'));
					foreach ($scanned_directory as $image){
						if (preg_match('/^[0-9]*\.jpg$/', $image)){
							$list[$proc["type"]]["todo"][] = $image;
						}
					}
				}
			}
		}
		$textHIW = $this->l("You can regenerate all your images safely.");
		$r.='<div class="panel"><h3>'.$this->l("Let's go").'</h3><p>'.$textHIW.'</p><div class="clearfix"></div><table width="100%" id="autoImg-buttons"></table><div class="clearfix"></div>
		<div class="btn-toolbar" role="toolbar"><div class="btn-group"><button class="btn btn-primary" id="image_regenerator-pause"><span class="icon-pause"></span> '.$this->l('PAUSE').'</button><button class="btn btn-success" id="image_regenerator-resume"><span class="icon-play"></span> '.$this->l('RESUME').'</button></div>
		<div class="btn-group"><form method="post" id="image_regenerator_save_form"><input type="hidden" name="image_regenerator_queue_what" value=""/><input type="hidden" name="image_regenerator_queue" value=""/></form></div>
		<div class="btn-group"><form method="post"><input type="hidden" name="image_regenerator_reinit" value="1"/><button type="submit" class="btn btn-warning" id="image_regenerator-reinit">'.$this->l('RESET').'</button></form></div></div>
		</div><div class="panel"><h3>'.$this->l('Debug').'</h3><div id="autoImg-progress" style="width:100%;line-height:20px;height:60px;overflow:auto;"></div><br/><div class="clearfix"></div>
		<script>var image_regenerator_can_run_queue = true;var image_regenerator_queuing_what = '.$image_regenerator_queue_what.';
		var autoImg = $.parseJSON(\''.json_encode($list).'\');
		var autoImgPath = "'._PS_BASE_URL_.__PS_BASE_URI__.'";
		</script></div></div>';
		return $r;
	}
}
