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
	protected $underline;
	protected $textrotation;


	/**
	 * Construct of the class
	 * @param array $properties
	 */
	public function __construct($properties = null) {
		if (!is_null($properties) && is_array($properties)) {
			foreach ($properties as $key => $value) {
				$this->assignValue($key, $value);
			}
		}
	}

	/**
	 * Set size text cell
	 * @param int $size
	 */
	public function setSize($size) {
		$this->size = $size;
	}

	/**
	 * Set horizontal alignment text cell
	 * @param string $align
	 */
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
			case 'justify':
				$this->align = PHPExcel_Style_Alignment::HORIZONTAL_JUSTIFY;
				break;
		}
	}

	/**
	 * Set vertical alignment of text cell
	 * @param string $valign
	 */
	public function setValign($valign) {
		switch ($valign) {
			case 'top':
				$this->valign = PHPExcel_Style_Alignment::VERTICAL_TOP;
				break;
			case 'middle':
			case 'center':
				$this->valign = PHPExcel_Style_Alignment::VERTICAL_CENTER;
				break;
			case 'vjustify':
				$this->valign = PHPExcel_Style_Alignment::VERTICAL_JUSTIFY;
				break;
		}
	}

	/**
	 * Set bold text
	 * @param int $bold
	 */
	public function setBold($bold) {
		$this->bold = $bold == 1 ? true : false;
	}

	/**
	 * Set italic text
	 * @param int $italic
	 */
	public function setItalic($italic) {
		$this->italic = $italic == 1 ? true : false;
	}

	/**
	 * Set color text
	 * @param string $bold (hexadecimal code or name)
	 */
	public function setColor($color) {
		$this->color = $this->getColor($color);
	}

	/**
	 * Set locked text
	 * @param int $locked
	 */
	public function setLocked($locked) {
		$this->locked = $locked == 1 ? true : false;
	}

	/**
	 * Set top border line
	 * @param int $top
	 */
	public function setTop($top) {
		$this->top = $top == 1 ? true : false;
	}

	/**
	 * Set bottom border line
	 * @param int $top
	 */
	public function setBottom($bottom) {
		$this->bottom = $bottom;
	}

	/**
	 * Set border line
	 * @param int $top
	 */
	public function setBorder($border) {
		$this->border = $border;
	}

	/**
	 * Set foreground color
	 * @param string $fgcolor
	 */
	public function setFgcolor($fgcolor) {
		if (is_numeric($fgcolor)) {
			$this->fgcolor = intval($fgcolor);
		} else {
			$this->fgcolor = $this->getColor($fgcolor);
		}
	}

	/**
	 * Set text wrap
	 * @param string $textwrap
	 */
	public function setTextwrap($textwrap) {
		$this->textwrap = $textwrap == 1 ? true : false;
	}

	/**
	 * Set number format
	 * @param string $numformat
	 */
	public function setNumformat($numformat) {
		$this->numformat = $numformat;
	}

	/**
	 * Set underline
	 * @param int $underline
	 */
	public function setUnderline($underline) {
		$this->underline = $underline;
	}

	public function setTextRotation($angle) {
		$this->textrotation = $angle;
	}

	/**
	 * Get element of this class
	 * @return element of this class
	 */
	public function getElements() {
		$elements = [];
		foreach ($this as $key => $value) {
			if (!is_null($value)) {
				$elements[$key] = $value;
			}
		}

		return $elements;
	}

	public function merge($format = null) {
		$elements = $this->getElements() + $format->getElements();

		return new self($elements);
	}

	/**
	 * Assign properties of this class
	 * @param string $key
	 * @param string $value
	 */
	private function assignValue($key, $value) {
		switch (strtolower($key)) {
			case 'size':
				$this->setSize($value);
				break;
			case 'align':
				$this->setAlign($value);
				break;
			case 'valign':
				$this->setValign($value);
				break;
			case 'bold':
				$this->setBold($value);
				break;
			case 'color':
				$this->setColor($value);
				break;
			case 'locked':
				$this->setLocked($value);
				break;
			case 'top':
				$this->setTop($value);
				break;
			case 'bottom':
				$this->setBottom($value);
				break;
			case 'fgcolor':
				$this->setFgcolor($value);
				break;
			case 'textwrap':
				$this->setTextwrap($value);
				break;
			case 'numformat':
				$this->setNumformat($value);
				break;
			case 'border':
				$this->setBorder($value);
				break;
			case 'italic':
				$this->setItalic($value);
				break;
			case 'underline':
				$this->setUnderline($value);
				break;
			case 'textrotation':
				$this->setTextRotation($value);
				break;
		}
	}

	/**
	 * Assign properties of this class
	 * @param string int $color
	 * @return PHPExcel_Style_Color
	 */
	private function getColor($color) {
		if (!ctype_xdigit($color)) {
			switch (strtolower($color)) {
				case 'black':
					return PHPExcel_Style_Color::COLOR_BLACK;
					break;
				case 'white':
					return PHPExcel_Style_Color::COLOR_WHITE;
					break;
				case 'blue':
					return PHPExcel_Style_Color::COLOR_BLUE;
					break;
				case 'darkblue':
					return PHPExcel_Style_Color::COLOR_DARKBLUE;
					break;
				case 'green':
					return PHPExcel_Style_Color::COLOR_GREEN;
					break;
				case 'darkgreen':
					return PHPExcel_Style_Color::COLOR_DARKGREEN;
					break;
				case 'red':
					return PHPExcel_Style_Color::COLOR_RED;
					break;
				case 'darkred':
					return PHPExcel_Style_Color::COLOR_DARKRED;
					break;
				case 'yellow':
					return PHPExcel_Style_Color::COLOR_YELLOW;
					break;
				case 'darkyellow':
					return PHPExcel_Style_Color::COLOR_DARKYELLOW;
					break;
			}
		} else {
			return $color;
		}
	}
}
