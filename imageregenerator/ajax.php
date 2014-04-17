<?php
require_once(dirname(__FILE__).'../../../config/config.inc.php');
require_once(dirname(__FILE__).'../../../init.php');
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
$dir = $process[$baseType];
$success = null;
$errors = null;
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
	else
	{
		$errors = sprintf('Original image is missing or empty (%s)', $existing_img);
	}
}
echo json_encode(array('success'=>$success,'error'=>$errors));
exit;
?>