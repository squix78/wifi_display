<?php

require_once("provider.php");

class SBBTimesProvider implements ServiceProvider {
	// Widget properties
	static $widgetName = "SBB";
	static $widgetIcon = "sbb.svg";

	public $station;
	public $numdep;
	public $width;
	public $height;

	function SBBTimesProvider() {
		$this->station = 8503000;  // Zurich HB
		$this->numdep = 5;
		$this->width = 400;
		$this->height = 200;
		$this->font_size = 0.65;
		$this->font_family = "Arial";
		$this->strformat = "{time} {dest} [{train}]";
	}

    public function getTunables() {
		return array(
			"station"    => array("type" => "fnum", "display" => "Station ID", "value" => $this->station),
			"numdep"    => array("type" => "fnum", "display" => "Board size", "value" => $this->numdep),
			"strformat"    => array("type" => "text", "display" => "Display format", "value" => $this->strformat),
			"font_family" => array("type" => "text", "display" => "Font Family", "value" => $this->font_family),
			"font_size"   => array("type" => "fnum", "display" => "Font Size", "value" => $this->font_size)
		);
	}
    public function setTunables($v) {
		$this->station = $v["station"]["value"];
		$this->numdep = $v["numdep"]["value"];
		$this->strformat = $v["strformat"]["value"];
		$this->font_family = $v["font_family"]["value"];
		$this->font_size = $v["font_size"]["value"];
	}

    public function shape() {
		// Return default width/height
		return array(
			"width"       => $this->width,
			"height"      => $this->height,
			"resizable"   => true,
			"keep_aspect" => false,
		);
    }

    public function render() {
		// Gather information from OpenWeatherMap
		$raw = file_get_contents("http://transport.opendata.ch/v1/stationboard?id=".$this->station);
		$info = json_decode($raw, true);

		$ret = '';
		$y = $this->font_size * $this->height;
		$xScale = $this->font_size * 10;
		$departureColumn = '';
		$trainColumn = '';
		$directionColumn = '';
		$trainBox = '';
		for ($i = 0; $i < $this->numdep; $i++) {
			$departure = date('G:i', $info["stationboard"][$i]["stop"]["departureTimestamp"]);
			$train = $info["stationboard"][$i]["number"];
			$direction = $info["stationboard"][$i]["to"];

			$trainColumn .= sprintf('<tspan x="%d" y="%d" text-anchor="middle" fill="white" style="font-size: %dpx; font-family: %s;">%s</tspan>',
				20 * $xScale, $y, $this->font_size * $this->height, $this->font_family, $train);
			$trainBox .= sprintf('<rect x="%d" y="%d" width="%d" height="%d" style="fill:gray;stroke-width:1;stroke:black" />',
				0, $y -  $this->font_size * $this->height + 1, $xScale * 40, $this->font_size * $this->height);
			$departureColumn .= sprintf('<tspan x="%d" y="%d" fill="black" style="font-size: %dpx; font-family: %s;">%s</tspan>',
				60 * $xScale, $y, $this->font_size * $this->height, $this->font_family, $departure);

			$directionColumn .= sprintf('<tspan x="%d" y="%d" fill="black" style="font-size: %dpx; font-family: %s;">%s</tspan>',
				120 * $xScale, $y, $this->font_size * $this->height, $this->font_family, $direction);

			$y += $this->font_size * $this->height;
		}
		$ret = $trainBox.'<text xmlns="http://www.w3.org/2000/svg" x="180" y="30" font-size="18px">'
			.$departureColumn.$trainColumn.$directionColumn.'</text>';


		// Generate an SVG image out of this 
		return sprintf('<svg width="%d" height="%d" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">%s</svg>',
			$this->width, $this->height, $ret);
	}
};

?>
