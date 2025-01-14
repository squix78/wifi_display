<?php

require_once("config.php");
require_once("auth.php");
require_once("provider.php");
require_once("weather.php");
require_once("sbb.php");
require_once("web-image.php");
require_once("publibike.php");
require_once("currency.php");
require_once("stock.php");
require_once("btc.php");
require_once("forecast.php");
require_once("hourlyForecast.php");
require_once("staticText.php");
require_once("dateTimeText.php");

error_reporting(-1);
ini_set('display_errors', 'On');

function array2js($a) {
	if (is_array($a)) {
		$ret = "";
		foreach ($a as $key => $value)
			$ret .= array2js($key).": ".array2js($value).", ";
		return "{".$ret."}";
	}
	else if (is_string($a))
		return "'$a'";

	return $a;
}

class Providers {
	// Returns list of providers
	static function getProvidersList() {
		$providers = array();
		foreach(get_declared_classes() as $klass) {
			$reflect = new ReflectionClass($klass);
			if ($reflect->implementsInterface('ServiceProvider')) {
				$prop = $reflect->getStaticProperties();
				$wname = $prop["widgetName"];
				$providers[$wname] = array("class" => $klass, "icon" => $prop["widgetIcon"]);
			}
		}
		return $providers;
	}

	static function getRender($widget_name, $settings, $ws, $hs) {
		$plist = Providers::getProvidersList();
		$w = new $plist[$widget_name]["class"];
		$w->setTunables($settings);
		if ($ws > 0 && $hs > 0) {
			$w->width = $ws;
			$w->height = $hs;
		}

		return $w->render();
	}
};

// Image rendering stuff

function renderSVG($id) {
	//header('Content-type: image/svg+xml');


	// Read the screen and parse it as JSON
	$scr = file_get_contents("screens/".$id);
	$scr = json_decode($scr, true);

	$body = array();
	for ($i = 0; $i < count($scr["widgets"]); $i++) {
		$widget = $scr["widgets"][$i];
		$params = array();
		foreach ($widget["params"] as $p => $v)
			$params[$p] = array("value" => $v);

		$wi = Providers::getRender($widget["type"], $params, 0, 0);

		
		$body[] = sprintf('<image x="%d" y="%d" width="%d" height="%d" xlink:href="%s" />',
		                  $widget["geo"]["x"] * $scr["width"],
		                  $widget["geo"]["y"] * $scr["height"],
		                  $widget["geo"]["w"] * $scr["width"],
		                  $widget["geo"]["h"] * $scr["height"],
		                  "data:image/svg+xml;base64,".base64_encode($wi)
		          );
	}

	$body = implode("\n", $body);

	$svg = sprintf('<svg width="%d" height="%d" version="1.1" xmlns="http://www.w3.org/2000/svg" 
	                  xmlns:xlink="http://www.w3.org/1999/xlink"  shape-rendering="crispEdges">
					  <line x1="0" y1="0" x2="1" y2="1" stroke="red" />
	                  %s
	                </svg>', $scr["width"], $scr["height"], $body);

	return array(
		"width"  => $scr["width"],
		"height" => $scr["height"],
		"svg"    => '<?xml version="1.0" encoding="UTF-8" standalone="no"?>'.$svg
	);
}

function renderBMP($id, $numc, $maxwidth, $maxheight) {

	// Render image
	$data = renderSVG($id);
	$svg = $data["svg"];
	$svgf = tempnam("/tmp", "svgconv");
	$svgPng = $svgf .".png";
	$svgPdf = $svgf .".pdf";
	file_put_contents($svgf, $svg);

	// Call convert
	exec("rsvg-convert -o " . $svgPng . " " . $svgf);
	//exec("convert -filter Lanczos " . $svgf . " " . $svgf . ".png");
	//exec("rsvg-convert -f pdf -o " . $svgPdf . " " . $svgf);
	//exec("gs -dSAFER -dBATCH -dNOPAUSE -sDEVICE=png16m -r72 -dGraphicsAlphaBits=1 -sOutputFile=" .$svgPng . " " . $svgPdf);
	$im = new Imagick();

	$im->readImageFile(fopen($svgPng, "rb"));
	//$im->readImageBlob($svg);
	$im->setImageFormat("jpeg");
	$im->setImageCompressionQuality(30);
	//$im->setImageFormat("jpeg");

	//$im->transformImageColorspace(Imagick::IMGTYPE_GRAYSCALE);
	//$im->posterizeImage(2, imagick::DITHERMETHOD_NO);
	//$im->blackThresholdImage('grey');
	//$im->setOption('jpeg:colors', '2');
	//$im->setImageBackgroundColor('white');
	
	///

	$paletteImage = clone $im;
	//$paletteImage->quantizeImage(4, 4, 0, true, false);
	//$paletteImage->setImageDepth(2);
	$paletteImage->quantizeImage(2,Imagick::COLORSPACE_GRAY,0,false,false);
	//$im->remapImage($paletteImage, Imagick::DITHERMETHOD_FLOYDSTEINBERG);
	//$im->quantizeImage(2,Imagick::COLORSPACE_GRAY,0,false,false);
	//$im->setImageFormat("jpeg");



	///

	$im = $im->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
	unlink($svgf);
	unlink($svgf.".png");
	return $im;
}

function renderJPG($id, $numc, $maxwidth, $maxheight) {
	// Render image
	$data = renderSVG($id);
	$svg = $data["svg"];
	$svgf = tempnam("/tmp", "svgconv");
	file_put_contents($svgf, $svg);
	// Call convert
	exec("rsvg-convert --background-color=white -o " . $svgf . ".png " . $svgf);
	//exec("convert " . $svgf . " " . $svgf . ".png");


	$im = new Imagick();
	$im->readImageFile(fopen($svgf.".png", "rb"));
	$im->setImageFormat("jpeg");
	//$im->quantizeImage(2,Imagick::COLORSPACE_GRAY,0,true,false);
	//$im->posterizeImage(16, imagick::DITHERMETHOD_NO);
	//$im->setImageBackgroundColor('white');
	//$im = $im->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
	//$im->transformImageColorspace(imagick::COLORSPACE_SRGB);

	unlink($svgf);
    unlink($svgf .".png");
	return $im;
}


// RLE compression:
// Chunk header is one byte, decoded means:
//  0XXX XXXX: The following byte is repeated XXXXXXX times + 1 (from 1 to 128)
//  1XXX XXXX: Just copy the following XXXXXXX+1 bytes (means the pattern is not compressible)

// Function that performs RLE compression!

function img_compress($buf) {
	// Array to hold the number of repeated elements starting from that position
	$reps = array_fill(0, count($buf), 0);
	$prev = -1;
	for ($i = count($buf)-1; $i >= 0; $i--) {
		if ($buf[$i] != $prev)
			$ctr = 0;
		$ctr += 1;

		$reps[$i] = $ctr;
		$prev = $buf[$i];
	}

	$outb = array_fill(0, 60*1024, 0);
	$outp = 0;
	$i = 0;
	$accum = 0;
	while ($i < count($buf)) {
		$bytec = min($reps[$i], 128);
		$encoderle = ($bytec > 3);

		if ($encoderle || $accum == 128) {
			// Flush noncompressable pattern
			if ($accum > 0) {
				$b = $accum - 1;
				$b |= 0x80;
				$outb[$outp - $accum - 1] = $b;
				$accum = 0;
			}
		}

		if ($encoderle) {
			# Emit a runlegth
			$outb[$outp++] = $bytec-1;
			$outb[$outp++] = $buf[$i];
			$i += $bytec;
		} else {
			if ($accum == 0)
				$outp++;
			$outb[$outp++] = $buf[$i++];
			$accum++;
		}
	}

	# Make sure to flush it all
	if ($accum > 0) {
		$b = $accum - 1;
		$b |= 0x80;
		$outb[$outp - $accum - 1] = $b;
	}

	return $outb;
}

?>
