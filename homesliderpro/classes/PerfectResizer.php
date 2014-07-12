<?php


ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(1);

Class PerfectResize
{
    // *** Class variables
	private $image;
	private $width;
	private $height;
	private $imageResized;
	
	function __construct($fileName)
    {
        // *** Open up the file
        $this->image = $this->openImage($fileName);
 
        // *** Get width and height
        $this->width  = imagesx($this->image);
        $this->height = imagesy($this->image);
    }
	
	private function openImage($file)
	{
		// *** Get extension
		$extension = strtolower(strrchr($file, '.'));

		switch($extension)
		{
			case '.jpg':
			case '.jpeg':
				$img = @imagecreatefromjpeg($file);
				break;
			case '.gif':
				$img = @imagecreatefromgif($file);
				break;
			case '.png':
				$img = @imagecreatefrompng($file);
				break;
			default:
				$img = false;
				break;
		}
		return $img;
	}
	
	
	public function resizeImage($newWidth, $newHeight, $option="auto")
	{

		// *** Get optimal width and height - based on $option
		$optionArray = $this->getDimensions($newWidth, $newHeight, strtolower($option));

		$optimalWidth  = $optionArray['optimalWidth'];
		$optimalHeight = $optionArray['optimalHeight'];

		// *** Resample - create image canvas of x, y size
		$this->imageResized = imagecreatetruecolor($optimalWidth, $optimalHeight);
		imagecopyresampled($this->imageResized, $this->image, 0, 0, 0, 0, $optimalWidth, $optimalHeight, $this->width, $this->height);

		// *** if option is 'crop', then crop too
		if ($option == 'crop') {
			$this->crop($optimalWidth, $optimalHeight, $newWidth, $newHeight);
		}
	}
	
	private function getDimensions($newWidth, $newHeight, $option)
	{
	 
	   switch ($option)
		{
			case 'exact':
				$optimalWidth = $newWidth;
				$optimalHeight= $newHeight;
				break;
			case 'portrait':
				$optimalWidth = $this->getSizeByFixedHeight($newHeight);
				$optimalHeight= $newHeight;
				break;
			case 'landscape':
				$optimalWidth = $newWidth;
				$optimalHeight= $this->getSizeByFixedWidth($newWidth);
				break;
			case 'auto':
				$optionArray = $this->getSizeByAuto($newWidth, $newHeight);
				$optimalWidth = $optionArray['optimalWidth'];
				$optimalHeight = $optionArray['optimalHeight'];
				break;
			case 'crop':
				$optionArray = $this->getOptimalCrop($newWidth, $newHeight);
				$optimalWidth = $optionArray['optimalWidth'];
				$optimalHeight = $optionArray['optimalHeight'];
				break;
		}
		return array('optimalWidth' => $optimalWidth, 'optimalHeight' => $optimalHeight);
	}
	
	private function getSizeByFixedHeight($newHeight)
	{
		$ratio = $this->width / $this->height;
		$newWidth = $newHeight * $ratio;
		return $newWidth;
	}
 
	private function getSizeByFixedWidth($newWidth)
	{
		$ratio = $this->height / $this->width;
		$newHeight = $newWidth * $ratio;
		return $newHeight;
	}
 
	private function getSizeByAuto($newWidth, $newHeight)
	{
		if ($this->height < $this->width)
		// *** Image to be resized is wider (landscape)
		{
			$optimalWidth = $newWidth;
			$optimalHeight= $this->getSizeByFixedWidth($newWidth);
		}
		elseif ($this->height > $this->width)
		// *** Image to be resized is taller (portrait)
		{
			$optimalWidth = $this->getSizeByFixedHeight($newHeight);
			$optimalHeight= $newHeight;
		}
		else
		// *** Image to be resizerd is a square
		{
			if ($newHeight < $newWidth) {
				$optimalWidth = $newWidth;
				$optimalHeight= $this->getSizeByFixedWidth($newWidth);
			} else if ($newHeight > $newWidth) {
				$optimalWidth = $this->getSizeByFixedHeight($newHeight);
				$optimalHeight= $newHeight;
			} else {
				// *** Sqaure being resized to a square
				$optimalWidth = $newWidth;
				$optimalHeight= $newHeight;
			}
		}
	 
		return array('optimalWidth' => $optimalWidth, 'optimalHeight' => $optimalHeight);
	}
 
	private function getOptimalCrop($newWidth, $newHeight)
	{
	 
		$heightRatio = $this->height / $newHeight;
		$widthRatio  = $this->width /  $newWidth;
	 
		if ($heightRatio < $widthRatio) {
			$optimalRatio = $heightRatio;
		} else {
			$optimalRatio = $widthRatio;
		}
	 
		$optimalHeight = $this->height / $optimalRatio;
		$optimalWidth  = $this->width  / $optimalRatio;
	 
		return array('optimalWidth' => $optimalWidth, 'optimalHeight' => $optimalHeight);
	}
	
	private function crop($optimalWidth, $optimalHeight, $newWidth, $newHeight)
	{
		// *** Find center - this will be used for the crop
		$cropStartX = ( $optimalWidth / 2) - ( $newWidth /2 );
		$cropStartY = ( $optimalHeight/ 2) - ( $newHeight/2 );
	 
		$crop = $this->imageResized;
		//imagedestroy($this->imageResized);
	 
		// *** Now crop from center to exact requested size
		$this->imageResized = imagecreatetruecolor($newWidth , $newHeight);
		imagecopyresampled($this->imageResized, $crop , 0, 0, $cropStartX, $cropStartY, $newWidth, $newHeight , $newWidth, $newHeight);
	}
	
	public function saveImage($savePath, $imageQuality="100")
	{
		// *** Get extension
		$extension = strrchr($savePath, '.');
		$extension = strtolower($extension);
	 
		switch($extension)
		{
			case '.jpg':
			case '.jpeg':
				if (imagetypes() & IMG_JPG) {
					imagejpeg($this->imageResized, $savePath, $imageQuality);
				}
				break;
	 
			case '.gif':
				if (imagetypes() & IMG_GIF) {
					imagegif($this->imageResized, $savePath);
				}
				break;
	 
			case '.png':
				// *** Scale quality from 0-100 to 0-9
				$scaleQuality = round(($imageQuality/100) * 9);
	 
				// *** Invert quality setting as 0 is best, not 9
				$invertScaleQuality = 9 - $scaleQuality;
	 
				if (imagetypes() & IMG_PNG) {
					imagepng($this->imageResized, $savePath, $invertScaleQuality);
				}
				break;
	 
			// ... etc
	 
			default:
				// *** No extension - No save.
				break;
		}
	 
		imagedestroy($this->imageResized);
	}
	
	public static function validateUpload($file, $max_file_size = 0)
	{
		if ((int)$max_file_size > 0 && $file['size'] > (int)$max_file_size)
			return sprintf(Tools::displayError('Image is too large (%1$d kB). Maximum allowed: %2$d kB'), $file['size'] / 1024, $max_file_size / 1024);
		if (!PerfectResize::isRealImage($file['tmp_name'], $file['type']) || !PerfectResize::isCorrectImageFileExt($file['name']))
			return Tools::displayError('Image format not recognized, allowed formats are: .gif, .jpg, .png');
		if ($file['error'])
			return sprintf(Tools::displayError('Error while uploading image; please change your server\'s settings. (Error code: %s)'), $file['error']);
		return false;
	}
	
	/**
	 * Check if file is a real image
	 *
	 * @param string $filename File path to check
	 * @param string $file_mime_type File known mime type (generally from $_FILES)
	 * @param array $mime_type_list Allowed MIME types
	 * @return bool
	 */
	public static function isRealImage($filename, $file_mime_type = null, $mime_type_list = null)
	{
		// Detect mime content type
		$mime_type = false;
		if (!$mime_type_list)
			$mime_type_list = array('image/gif', 'image/jpg', 'image/jpeg', 'image/pjpeg', 'image/png', 'image/x-png');

		// Try 4 different methods to determine the mime type
		if (function_exists('finfo_open'))
		{
			$const = defined('FILEINFO_MIME_TYPE') ? FILEINFO_MIME_TYPE : FILEINFO_MIME;
			$finfo = finfo_open($const);
			$mime_type = finfo_file($finfo, $filename);
			finfo_close($finfo);
		}
		elseif (function_exists('mime_content_type'))
			$mime_type = mime_content_type($filename);
		elseif (function_exists('exec'))
		{
			$mime_type = trim(exec('file -b --mime-type '.escapeshellarg($filename)));
			if (!$mime_type)
				$mime_type = trim(exec('file --mime '.escapeshellarg($filename)));
			if (!$mime_type)
				$mime_type = trim(exec('file -bi '.escapeshellarg($filename)));
		}

		if ($file_mime_type && (empty($mime_type) || $mime_type == 'regular file' || $mime_type == 'text/plain'))
			$mime_type = $file_mime_type;

		// For each allowed MIME type, we are looking for it inside the current MIME type
		foreach ($mime_type_list as $type)
			if (strstr($mime_type, $type))
				return true;

		return false;
	}
	
	/**
	 * Check if image file extension is correct
	 *
	 * @static
	 * @param $filename real filename
	 * @return bool true if it's correct
	 */
	public static function isCorrectImageFileExt($filename)
	{
		// Filter on file extension
		$authorized_extensions = array('gif', 'jpg', 'jpeg', 'png');
		$name_explode = explode('.', $filename);
		if (count($name_explode) >= 2)
		{
			$current_extension = strtolower($name_explode[count($name_explode) - 1]);
			if (!in_array($current_extension, $authorized_extensions))
				return false;
		}
		else
			return false;

		return true;
	}
}