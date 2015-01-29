<?php

class Pagination extends AbstractUtility {

	protected $current_page = 1;
	protected $last_page = 1;
	protected $rows_per_page = 20;
	protected $current_row = 0;
	protected $total_rows = 0;

	public function __call($name, $arguments) {
		if (empty($arguments)) {
			return parent::__call($name, $arguments);
		}
		parent::__call($name, $arguments);
		$this->calculate();
	}

	private function calculate() {
		if (empty($this->current_page)) {
			$this->current_page = 1;
		}
		$this->current_row = $this->rows_per_page * ($this->current_page - 1);

		$mod = ($this->total_rows % $this->rows_per_page);
		$num_pages = ((($this->total_rows - $mod) / $this->rows_per_page) + ($mod ? 1 : 0));
		if ($num_pages < 1) {
			$num_pages = 1;
		}
		$this->last_page = $num_pages;
	}

	public function hasPrev() {
		return $this->current_page != 1;
	}

	public function hasNext() {
		return $this->current_page != $this->last_page;
	}

}
