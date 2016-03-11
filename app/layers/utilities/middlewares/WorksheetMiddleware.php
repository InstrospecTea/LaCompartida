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


	/**
	 * Construct of the class
	 * @param string $title
	 */
	public function __construct($title) {
		$this->title = $title;
		$this->print_gridlines = true;
		$this->screen_gridlines = true;
		$this->fit_page = false;
	}

	/**
	 * Set the type of papper
	 * http://www.osakac.ac.jp/labs/koeda/tmp/phpexcel/Documentation/API/PHPExcel_Worksheet/PHPExcel_Worksheet_PageSetup.html#methodsetPaperSize
	 * @param string $size
	 */
	public function setPaper($size = 0) {
		$this->paper_size = $size;
	}

	/**
	 * Hide printed Gridlines
	 */
	public function hideGridlines() {
		$this->print_gridlines = false;
	}

	/**
	 * Hide document Gridlines
	 */
	public function hideScreenGridlines() {
		$this->screen_gridlines = false;
	}

	/**
	 * Set document margins
	 * @param float $margin
	 */
	public function setMargins($margin) {
		$this->setMarginLeft($margin);
		$this->setMarginRight($margin);
		$this->setMarginTop($margin);
		$this->setMarginBottom($margin);
	}

	/**
	 * Set left margin document
	 * @param float $margin
	 */
	public function setMarginLeft($margin = 0.75) {
		$this->margin_left = $margin;
	}

	/**
	 * Set right margin document
	 * @param float $margin
	 */
	public function setMarginRight($margin = 0.75) {
		$this->margin_right = $margin;
	}

	/**
	 * Set top margin document
	 * @param float $margin
	 */
	public function setMarginTop($margin = 1.00) {
		$this->margin_top = $margin;
	}

	/**
	 * Set bottom margin document
	 * @param float $margin
	 */
	public function setMarginBottom($margin = 1.00) {
		$this->margin_bottom = $margin;
	}

	/**
	 * Set fit to pages
	 * @param int $width
	 * @param int $height
	 */
	public function fitToPages($width, $height) {
		$this->fit_page = true;
		$this->fit_width = $width;
		$this->fit_height = $height;
	}

	/**
	 * Add a column element (properties)
	 * @param int $firstcol
	 * @param int $lastcol
	 * @param int $width
	 * @param FormatMiddleware $format
	 * @param boolean $hidden
	 * @param int $level
	 */
	public function setColumn($firstcol, $lastcol, $width, $format = null, $hidden = false, $level = 0) {
		$this->columns[] = array('firstcol' => $firstcol,
															'lastcol' => $lastcol,
															'width' => $width,
															'format' => $format,
															'hidden' => $hidden == 1 ? true : false,
															'level' => $level);
	}

	/**
	 * Add a row element (properties)
	 * @param int $row
	 * @param int $height
	 * @param FormatMiddleware $format
	 * @param boolean $hidden
	 * @param int $level
	 */
	public function setRow($row, $height, $format = null, $hidden = false, $level = 0) {
		$this->rows[] = array('row' => $row,
													'height' => $height,
													'format' => $format,
													'hidden' => $hidden == 1 ? true : false,
													'level' => $level);
	}

	/**
	 * Insert a bitmap
	 * @param int $row
	 * @param int $col
	 * @param  $bitmap
	 * @param int $x
	 * @param int $y
	 * @param int $scale_x
	 * @param int $scale_y
	 *
	 * @todo: UNUSED
	 */
	public function insertBitmap($row, $col, $bitmap, $x = 0, $y = 0, $scale_x = 1, $scale_y = 1) {
		$this->bitmaps[] = array('row' => $row,
															'col' => $col,
															'bitmap' => $bitmap,
															'x' => $x,
															'y' => $y,
															'scale_x' => $scale_x,
															'scale_y' => $scale_y);
	}

	/**
	 * Add cells merged
	 * @param int $first_row
	 * @param int $first_col
	 * @param int $last_row
	 * @param int $last_col
	 */
	public function mergeCells($first_row, $first_col, $last_row, $last_col) {
		$this->cellsMerged[] = array('first_row' => $first_row,
																	'first_col' => $first_col,
																	'last_row' => $last_row,
																	'last_col' => $last_col);
	}

	/**
	 * Add data to cell
	 * @param int $row
	 * @param int $col
	 * @param string $token
	 * @param FormatMiddleware $format
	 */
	public function write($row, $col, $token, $format = null) {
		if (is_numeric($token)) {
			$this->writeNumber($row, $col, $token, $format);
		} else {
			$this->writeText($row, $col, $token, $format);
		}
	}

	/**
	 * Add text to cell
	 * @param int $row
	 * @param int $col
	 * @param string $token
	 * @param FormatMiddleware $format
	 */
	public function writeText($row, $col, $token, $format = null) {
		$this->elements[] = array('row' => $row,
															'col' => $col,
															'data' => $token,
															'format' => $format,
															'type' => 'text');
	}

	/**
	 * Add number to cell
	 * @param int $row
	 * @param int $col
	 * @param number $num
	 * @param FormatMiddleware $format
	 */
	public function writeNumber($row, $col, $num, $format = null) {
		$this->elements[] = array('row' => $row,
															'col' => $col,
															'data' => $num,
															'format' => $format,
															'type' => 'number');
	}

	/**
	 * Add formula to cell
	 * @param int $row
	 * @param int $col
	 * @param string $formula
	 * @param FormatMiddleware $format
	 */
	public function writeFormula($row, $col, $formula, $format = null) {
		$this->elements[] = array('row' => $row,
															'col' => $col,
															'data' => $formula,
															'format' => $format,
															'type' => 'formula');
	}

	/**
	 * Get title
	 * @return title
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Get array elements
	 * @return array elements
	 */
	public function getElements() {
		return $this->elements;
	}

	/**
	 * Get array cell merged
	 * @return array cells merged
	 */
	public function getCellsMerged() {
		return $this->cellsMerged;
	}

	/**
	 * Get array columns
	 * @return array columns
	 */
	public function getColumns() {
		return $this->columns;
	}

	/**
	 * Get array rows
	 * @return array rows
	 */
	public function getRows() {
		return $this->rows;
	}

	/**
	 * Get the type of paper
	 * @return paper size
	 */
	public function getPaper() {
		return $this->paper_size;
	}

	/**
	 * Get left margin
	 * @return left margin
	 */
	public function getMarginLeft() {
		return $this->margin_left;
	}

	/**
	 * Get right margin
	 * @return right margin
	 */
	public function getMarginRight() {
		return $this->margin_right;
	}

	/**
	 * Get top margin
	 * @return top margin
	 */
	public function getMarginTop() {
		return $this->margin_top;
	}

	/**
	 * Get bottom margin
	 * @return bottom margin
	 */
	public function getMarginBottom() {
		return $this->margin_bottom;
	}

	/**
	 * Get printed Gridlines
	 * @return boolean printed Gridlines
	 */
	public function getPrintGridlines() {
		return $this->print_gridlines;
	}

	/**
	 * Get document Gridlines
	 * @return boolean document Gridlines
	 */
	public function getScreenGridlines() {
		return $this->screen_gridlines;
	}

	/**
	 * Get fit to page
	 * @return boolean fit to page
	 */
	public function getFitPage(){
		return $this->fit_page;
	}

	/**
	 * Get width fit
	 * @return width fit
	 */
	public function getFitWidth(){
		return $this->fit_width;
	}

	/**
	 * Get height fit
	 * @return height fit
	 */
	public function getFitHeight(){
		return $this->fit_height;
	}

}
