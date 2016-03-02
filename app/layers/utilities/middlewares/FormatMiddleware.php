<?php

class FormatMiddleware {

	protected $size;

	protected $align;

	protected $valign;

	protected $bold;

	protected $color;

	protected $locked;

	protected $top;

	protected $bottom;

	protected $fgcolor;

	protected $textwrap;

	protected $numformat;

	protected $border;


	public function setSize($size) {
		$this->size = $size;
	}

	public function setAlign($align) {
		switch ($align) {
			case 'left':
				$this->align = PHPExcel_Style_Alignment::HORIZONTAL_LEFT;
				break;
			case 'right':
				$this->align = PHPExcel_Style_Alignment::HORIZONTAL_RIGHT;
				break;
			case 'center':
				$this->align = PHPExcel_Style_Alignment::HORIZONTAL_CENTER;
				break;
		}
	}

	public function setValign($valign) {
		switch ($valign) {
			case 'top':
				$this->valign = PHPExcel_Style_Alignment::VERTICAL_TOP;
				break;
			case 'middle':
				$this->valign = PHPExcel_Style_Alignment::VERTICAL_CENTER;
				break;
			case 'vjustify':
				$this->valign = PHPExcel_Style_Alignment::VERTICAL_JUSTIFY;
				break;
		}
	}

	public function setBold($bold) {
		$this->bold = $bold == 1 ? true : false;
	}

	public function setColor() {

	}

	public function setLocked($locked) {
		$this->locked = $locked == 1 ? true : false;
	}

	public function setTop() {

	}

	public function setBottom() {

	}

	public function setFgcolor() {

	}

	public function setTextwrap() {

	}

	public function setNumformat() {

	}

	public function setFormat() {

	}
}
