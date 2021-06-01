<?php

require_once("provider.php");

class DateTimeText implements ServiceProvider {
	// Widget properties
	static $widgetName = "Date Time Text";
	static $widgetIcon = "stock.svg";

	public $cpair;
	public $width;
	public $height;

	function DateTimeText() {
		$this->text = "Current Date Time: Y-m-d H:i:s";
		$this->width = 800;
		$this->height = 100;
		$this->font_size = 1;
		$this->font_family = "Arial";
	}

    public function getTunables() {
		return array(
			"text"       => array("type" => "text", "display" => "Text", "value" => $this->text),
			"font_family" => array("type" => "text", "display" => "Font Family", "value" => $this->font_family),
			"font_size"   => array("type" => "fnum", "display" => "Font Size", "value" => $this->font_size)
		);
	}
    public function setTunables($v) {
		$this->text = $v["text"]["value"];
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
		$date = new DateTime();
		$dateTime = $date->format($this->text);

		// Generate an SVG image out of this 
		return sprintf(
			'<svg width="%d" height="%d" version="1.1" xmlns="http://www.w3.org/2000/svg" 
				xmlns:xlink="http://www.w3.org/1999/xlink">
                <text text-anchor="middle" x="50%%" y="80%%" fill="black" style="font-size: %dpx; font-family: %s;">
					%s
				</text>
			</svg>', $this->width, $this->height,
			    $this->font_size * $this->height, $this->font_family,
				$dateTime
		);
	}

};

?>
