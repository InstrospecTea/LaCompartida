<?php

class FormatMiddleware {

	protected $size;

	protected $align;

	protected $valign;

	protected $bold;

	protected $italic;

	protected $color;

	protected $locked;

	protected $top;

	protected $bottom;

	protected $fgcolor;

	protected $textwrap;

	protected $numformat;

	protected $border;

	protected $type;

	public function __construct($properties = null) {
		if (!is_null($properties) && is_array($properties)) {
			foreach ($properties as $key => $value) {
				$this->assignValue($key, $value);
			}
		}
	}


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

	public function setItalic($italic) {
		$this->italic = $italic == 1 ? true : false;
	}

	public function setColor() {

	}

	public function setLocked($locked) {
		$this->locked = $locked == 1 ? true : false;
	}

	public function setTop() {

	}

	public function setBottom($bottom) {
		$this->bottom = $bottom == 1 ? true : false;
	}

	public function setBorder($border) {
		$this->border = $border;
	}

	public function setFgcolor() {

	}

	public function setTextwrap() {

	}

	public function setNumformat($numformat) {
		$this->numformat = $numformat;
	}

	public function setFormat() {

	}

	public function setType($type) {
		$this->type = $type;
	}

	public function getElements() {
		$elements = [];
		foreach ($this as $key => $value) {
			$elements[$key] = $value;
		}

		return $elements;
	}

	private function assignValue($key, $value) {
		switch ($key) {
			case 'Size':
				$this->setSize($value);
				break;
			case 'Align':
				$this->setAlign($value);
				break;
			case 'VAlign':
				$this->setValign($value);
				break;
			case 'Bold':
				$this->setBold($value);
				break;
			case 'Color':
				$this->setColor($value);
				break;
			case 'Locked':
				$this->setLocked($value);
				break;
			case 'Top':
				$this->setTop($value);
				break;
			case 'Bottom':
				$this->setBottom($value);
				break;
			case 'FgColor':
				$this->setFgcolor($value);
				break;
			case 'TextWrap':
				$this->setTextwrap($value);
				break;
			case 'NumFormat':
				$this->setNumformat($value);
				break;
			case 'Border':
				$this->setBorder($value);
				break;
			case 'Italic':
				$this->setItalic($value);
				break;
		}
	}
}
