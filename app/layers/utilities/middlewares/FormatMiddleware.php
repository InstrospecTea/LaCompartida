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

	public function setColor($color) {
		if (!ctype_xdigit($color)) {
			switch ($color) {
				case 'black':
					$this->color = PHPExcel_Style_Color::COLOR_BLACK;
					break;
				case 'white':
					$this->color = PHPExcel_Style_Color::COLOR_WHITE;
					break;
				case 'blue':
					$this->color = PHPExcel_Style_Color::COLOR_BLUE;
					break;
				case 'darkblue':
					$this->color = PHPExcel_Style_Color::COLOR_DARKBLUE;
					break;
				case 'green':
					$this->color = PHPExcel_Style_Color::COLOR_GREEN;
					break;
				case 'darkgreen':
					$this->color = PHPExcel_Style_Color::COLOR_DARKGREEN;
					break;
				case 'red':
					$this->color = PHPExcel_Style_Color::COLOR_RED;
					break;
				case 'darkred':
					$this->color = PHPExcel_Style_Color::COLOR_DARKRED;
					break;
				case 'yellow':
					$this->color = PHPExcel_Style_Color::COLOR_YELLOW;
					break;
				case 'darkyellow':
					$this->color = PHPExcel_Style_Color::COLOR_DARKYELLOW;
					break;
			}
		} else {
			$this->color = $color;
		}
	}

	public function setLocked($locked) {
		$this->locked = $locked == 1 ? true : false;
	}

	public function setTop($top) {
		$this->top = $top == 1 ? true : false;
	}

	public function setBottom($bottom) {
		$this->bottom = $bottom == 1 ? true : false;
	}

	public function setBorder($border) {
		$this->border = $border;
	}

	public function setFgcolor($fgcolor) {
		$this->fgcolor = $fgcolor;
	}

	public function setTextwrap($textwrap) {
		$this->textwrap = $textwrap == 1 ? true : false;
	}

	public function setNumformat($numformat) {
		$this->numformat = $numformat;
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
