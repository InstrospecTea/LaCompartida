<?php

class WorksheetMiddleware {

	protected $title;
	protected $paper_size;
	protected $print_gridlines;
	protected $screen_gridlines;
	protected $margin_left;
	protected $margin_right;
	protected $margin_top;
	protected $margin_bottom;
	protected $fit_page;
	protected $fit_width;
	protected $fit_height;
	protected $columns = [];
	protected $rows = [];
	protected $bitmaps = [];
	protected $cellsMerged = [];
	protected $elements = [];


	public function __construct($title) {
		$this->title = $title;
		$this->print_gridlines = true;
		$this->screen_gridlines = true;
		$this->fit_page = false;
	}

	public function setPaper($size = 0) {
		$this->paper_size = $size;
	}

	public function hideGridlines() {
		$this->print_gridlines = false;
	}

	public function hideScreenGridlines() {
		$this->screen_gridlines = false;
	}

	public function setMargins($margin) {
		$this->setMarginLeft($margin);
		$this->setMarginRight($margin);
		$this->setMarginTop($margin);
		$this->setMarginBottom($margin);
	}

	public function setMarginLeft($margin = 0.75) {
		$this->margin_left = $margin;
	}

	public function setMarginRight($margin = 0.75) {
		$this->margin_right = $margin;
	}

	public function setMarginTop($margin = 1.00) {
		$this->margin_top = $margin;
	}

	public function setMarginBottom($margin = 1.00) {
		$this->margin_bottom = $margin;
	}

	public function fitToPages($width, $height) {
		$this->fit_page = true;
		$this->fit_width = $width;
		$this->fit_height = $height;
	}

	public function setColumn($firstcol, $lastcol, $width, $format = null, $hidden = false, $level = 0) {
		$this->columns[] = array('firstcol' => $firstcol,
															'lastcol' => $lastcol,
															'width' => $width,
															'format' => $format,
															'hidden' => $hidden == 1 ? true : false,
															'level' => $level);
	}

	public function setRow($row, $height, $format = null, $hidden = false, $level = 0) {
		$this->rows[] = array('row' => $row,
													'height' => $height,
													'format' => $format,
													'hidden' => $hidden == 1 ? true : false,
													'level' => $level);
	}

	public function insertBitmap($row, $col, $bitmap, $x = 0, $y = 0, $scale_x = 1, $scale_y = 1) {
		$this->bitmaps[] = array($row, $col, $bitmap, $x, $y, $scale_x, $scale_y);
	}

	public function mergeCells($first_row, $first_col, $last_row, $last_col) {
		$this->cellsMerged[] = array($first_row, $first_col, $last_row, $last_col);
	}

	public function write($row, $col, $token, $format = null) {
		if (is_numeric($token)) {
			$this->writeNumber($row, $col, $token, $format);
		} else {
			$this->writeText($row, $col, $token, $format);
		}
	}

	public function writeText($row, $col, $token, $format = null) {
		$this->elements[] = array('row' => $row,
															'col' => $col,
															'data' => $token,
															'format' => $format,
															'type' => 'text');
	}

	public function writeNumber($row, $col, $num, $format = null) {
		$this->elements[] = array('row' => $row,
															'col' => $col,
															'data' => $num,
															'format' => $format,
															'type' => 'number');
	}

	public function writeFormula($row, $col, $formula, $format = null) {
		$this->elements[] = array('row' => $row,
															'col' => $col,
															'data' => $formula,
															'format' => $format,
															'type' => 'formula');
	}


	public function getTitle() {
		return $this->title;
	}

	public function getElements() {
		return $this->elements;
	}

	public function getCellsMerged() {
		return $this->cellsMerged;
	}

	public function getColumns() {
		return $this->columns;
	}

	public function getRows() {
		return $this->rows;
	}

	public function getPaper() {
		return $this->paper_size;
	}

	public function getMarginLeft() {
		return $this->margin_left;
	}

	public function getMarginRight() {
		return $this->margin_right;
	}

	public function getMarginTop() {
		return $this->margin_top;
	}

	public function getMarginBottom() {
		return $this->margin_bottom;
	}

	public function getPrintGridlines() {
		return $this->print_gridlines;
	}

	public function getScreenGridlines() {
		return $this->screen_gridlines;
	}

	public function getFitPage(){
		return $this->fit_page;
	}

	public function getFitWidth(){
		return $this->fit_width;
	}

	public function getFitHeight(){
		return $this->fit_height;
	}

}
