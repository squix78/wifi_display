<?php

require_once("provider.php");

class ImageProvider implements ServiceProvider {
	// Widget properties
	static $widgetName = "Web Image";
	static $widgetIcon = "sbb.svg";

	public $href;
	public $width;
	public $height;

	function ImageProvider() {
		$this->href = "https://loremflickr.com/320/240";  // Zurich HB
		$this->width = 320;
		$this->height = 240;

	}

    public function getTunables() {
		return array(
			"href"    => array("type" => "text", "display" => "Image Url", "value" => $this->href)
		);
	}
    public function setTunables($v) {
		$this->href = $v["href"]["value"];
	}

    public function shape() {
		// Return default width/height
		return array(
			"width"       => $this->width,
			"height"      => $this->height,
			"resizable"   => true,
			"keep_aspect" => false
		);
    }

	public  function getImageDataFromUrl($url)
{
		$urlParts = pathinfo($url);
		$extension = $urlParts['extension'];
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$response = curl_exec($ch);
		curl_close($ch);
		$base64 = 'data:image/' . $extension . ';base64,' . base64_encode($response);
		return $base64;
}

    public function render() {
		// Gather information from OpenWeatherMap
		$data = file_get_contents($this->href);
		$imagick = new Imagick();
    	$imagick->readImageBlob($data);
		$containerFactor = 4;
		$imageFactor = 5;

		$paletteImage = clone $imagick;
		//$paletteImage->quantizeImage(4, 4, 0, true, false);
		//$paletteImage->setImageDepth(2);
		$paletteImage->quantizeImage(16,Imagick::COLORSPACE_GRAY,0,false,false);
		//$imagickFrame->quantizeImage(15,Imagick::COLORSPACE_TRANSPARENT,0,false,false);
		//Imagick::mapImage ( Imagick $map , bool $dither )
		$imagick->remapImage($paletteImage, Imagick::DITHERMETHOD_FLOYDSTEINBERG);
		$imagick->quantizeImage(16,Imagick::COLORSPACE_GRAY,0,false,false);

		$base64 = 'data:image/'.$imagick->getFormat().';base64,' . base64_encode($imagick->getImageBlob());

		$ret = sprintf('<image href="%s" height="%d" width="%d" x="%d" y="%d"/>' , $base64, $this->width * $imageFactor, $this->height * $imageFactor, 0, - $containerFactor / 2 * $this->height);

		// Generate an SVG image out of this 
		return sprintf('<svg width="%d" height="%d" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">%s</svg>',
			$this->width * $containerFactor, $this->height * $containerFactor, $ret);
	}
};

?>
