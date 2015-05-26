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
		$this->version = '1.1';
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
		$r.='
		<div class="panel">
			<h3>'.$this->l("Let's go").'</h3>
			<p>'.$textHIW.'</p>
			<div class="clearfix"></div>
			<table width="100%" id="autoImg-buttons"></table>
			<div class="clearfix"></div>
			<div class="btn-toolbar" role="toolbar">
				<div class="btn-group">
					<button class="btn btn-primary" id="image_regenerator-pause"><span class="icon-pause"></span> '.$this->l('PAUSE').'</button>
					<button class="btn btn-success" id="image_regenerator-resume"><span class="icon-play"></span> '.$this->l('RESUME').'</button>
				</div>
				<div class="btn-group">
					<form method="post" id="image_regenerator_save_form">
						<input type="hidden" name="image_regenerator_queue_what" value=""/>
						<input type="hidden" name="image_regenerator_queue" value=""/>
					</form>
				</div>
				<div class="btn-group">
					<form method="post">
						<input type="hidden" name="image_regenerator_reinit" value="1"/>
						<button type="submit" class="btn btn-warning" id="image_regenerator-reinit">'.$this->l('RESET').'</button>
					</form>
				</div>
				<div class="btn-group">
					<div class="checkbox">
						<label><input type="checkbox" value="1" id="image_regenerator-watermark"> '.$this->l('Watermark ? (module watermark need to be enable)').'</label>
					</div>
				</div>
			</div>
		</div>
		<div class="panel"><h3>'.$this->l('Debug').'</h3><div id="autoImg-progress" style="width:100%;line-height:20px;height:60px;overflow:auto;"></div><br/><div class="clearfix"></div>
		<script>var image_regenerator_can_run_queue = true;var image_regenerator_queuing_what = '.$image_regenerator_queue_what.';
		var autoImg = $.parseJSON(\''.json_encode($list).'\');
		var autoImgPath = "'.$this->context->link->getAdminLink('AdminModules').'&configure='.$this->name.'";
		</script></div></div>';
		return $r;
	}

	function ajaxProcessRegenerateMethod(){
		$process =array('categories' => _PS_CAT_IMG_DIR_,
			'manufacturers' => _PS_MANU_IMG_DIR_,
			'suppliers' => _PS_SUPP_IMG_DIR_,
			'scenes' => _PS_SCENE_IMG_DIR_,
			'products' => _PS_PROD_IMG_DIR_,
			'stores' => _PS_STORE_IMG_DIR_
			);
		$baseType = Tools::getValue('type');
		$type = ImageType::getImagesTypes($baseType);
		$image = Tools::getValue('image');
		$watermark = (int) Tools::getValue('watermark');
		$dir = $process[$baseType];
		$success = null;
		$errors = null;
		$watermarked = 0;
		if($baseType!="products"){
			if (preg_match('/^[0-9]*\.jpg$/', $image)){
				foreach ($type as $k => $imageType)
				{
					// Customizable writing dir
					$newDir = $dir;
					if ($imageType['name'] == 'thumb_scene')
						$newDir .= 'thumbs/';
					if (!file_exists($newDir))
						$errors = 1;
					$newFile = $newDir.substr($image, 0, -4).'-'.stripslashes($imageType['name']).'.jpg';
					if(file_exists($newFile) && !unlink($newFile))
						$errors = 1;
					if (!file_exists($newFile))
					{

						if (!file_exists($dir.$image) || !filesize($dir.$image))
						{
							$errors = sprintf(Tools::displayError('Source file does not exist or is empty (%s)', $dir.$image));
						}
						elseif (!ImageManager::resize($dir.$image, $newFile, (int)$imageType['width'], (int)$imageType['height']))
						{
							$errors = 1;
						}else{
							$success = 1;
						}
					}else{
						$errors = 1;
					}
				}
			}else{
				$success=1;
			}
		}else{
			$imageObj = new Image($image);
			$existing_img = $dir.$imageObj->getExistingImgPath().'.jpg';
			if (file_exists($existing_img) && filesize($existing_img))
			{
				if($watermark == 1){
					$watermarked = 1;
					$watermark = ModuleCore::getInstanceByName("watermark");
					$valid_types = array();
					$watermark_types = explode(',', Configuration::get('WATERMARK_TYPES'));
					foreach($type as $k => $t){
						if(in_array($t['id_image_type'],$watermark_types)){
							$valid_types[] = $t;
							unset($type[$k]);
						}
					}
					if($watermark->hookActionWatermark(array('image_type'=>$valid_types,'id_image'=>$imageObj->id_image,'id_product'=>$imageObj->id_product))){
						$success = 1;
					}else{
						$errors = 1;
					}
				}
				if(count($type)>0){
					foreach ($type as $imageType){
						$newFile = $dir.$imageObj->getExistingImgPath().'-'.stripslashes($imageType['name']).'.jpg';
						if(file_exists($newFile) && !unlink($newFile))
							$errors = 1;
						if (!file_exists($newFile)){
							if (!ImageManager::resize($existing_img, $newFile, (int)($imageType['width']), (int)($imageType['height'])))
							{
								$errors = sprintf('Original image is corrupt (%s) or bad permission on folder', $existing_img);
							}else{
								$success = 1;
							}
						}else{
							$errors = 1;
						}
					}
				}
			}
			else
			{
				$errors = sprintf('Original image is missing or empty (%s)', $existing_img);
			}
		}
		echo json_encode(array('success'=>$success,'error'=>$errors,'watermark'=>$watermarked));
		exit;
	}
}
