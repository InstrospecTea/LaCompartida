<?php

class Paginator extends Helper {

	public $helpers = array(array('\TTB\Html', 'Html'), 'Form');
	private $query_array = array();

	public function pages(Pagination $Pagination, $show_prev_next = true, $show_first_last = false, $show_pages = 10) {
		$tpl = array(
			'first' => '&#171; %s',
			'last' => '%s &#187;',
			'prev' => '&#60; %s',
			'next' => '%s &#62;',
		);
		$this->query_array = $Pagination->query_array();
		$current_page = $Pagination->current_page();
		$last_page = $Pagination->last_page();
		$ppn  = array('prev' => '', 'next' => '');
		if ($show_prev_next) {
			$attrs = null;
			$prev_text = sprintf($tpl['prev'], __('anterior'));
			if ($Pagination->hasPrev()) {
				$page = $current_page - 1;
				$prev_text = $this->Html->link($prev_text, $this->getQueryString(compact('page')));
			} else {
				$attrs = array('class' => 'disablepage');
			}
			$ppn['prev'] = $this->Html->tag('li', $prev_text, $attrs);

			$attrs = null;
			$next_text = sprintf($tpl['next'], __('siguiente'));
			if ($Pagination->hasNext()) {
				$page = $current_page + 1;
				$next_text = $this->Html->link($next_text, $this->getQueryString(compact('page')));
			} else {
				$attrs = array('class' => 'disablepage');
			}
			$ppn['next'] = $this->Html->tag('li', $next_text, $attrs);
		}

		$pfl  = array('first' => '', 'last' => '');
		if ($show_first_last) {
			$attrs = null;
			$first_text = sprintf($tpl['first'], __('primera'));
			if ($Pagination->hasPrev()) {
				$page = 1;
				$first_text = $this->Html->link($first_text, $this->getQueryString(compact('page')));
			} else {
				$attrs = array('class' => 'disablepage');
			}
			$pfl['first'] = $this->Html->tag('li', $first_text, $attrs);

			$attrs = null;
			$last_text = sprintf($tpl['last'], __('última'));
			if ($Pagination->hasNext()) {
				$page = $last_page;
				$last_text = $this->Html->link($last_text, $this->getQueryString(compact('page')));
			} else {
				$attrs = array('class' => 'disablepage');
			}
			$pfl['last'] = $this->Html->tag('li', $last_text, $attrs);
		}

		$medio = floor($show_pages / 2);
		$from = $current_page - $medio;
		if ($from < 0) {
			$from = 0;
			$aumento = $show_pages - $current_page;
		} else {
			$aumento = $medio;
		}

		$to = $current_page + $aumento;
		if ($to > $last_page) {
			$to = $last_page;
			if (($last_page - ($show_pages)) > 0) {
				$from = $last_page - ($show_pages);
			}
		} else if ($to - $from > $show_pages) {
			--$to;
		}
		if ($to - $from < $show_pages) {
			if ($to - $show_pages < 0) {
				$from = 0;
			} else {
				$from = $to - $show_pages;
			}
		}


		$pages = array($pfl['first'], $ppn['prev']);

		for ($i = $from; $i < $to; ++$i) {
			$page = $i + 1;
			$attrs = null;
			$text_page = $page;
			if ($page == $current_page) {
				$attrs = array('class' => 'currentpage');
			} else {
				$text_page = $this->Html->link($page, $this->getQueryString(compact('page')));
			}
			$pages[] = $this->Html->tag('li', $text_page, $attrs);
		}

		$actual = $current_page;
		$pages[] = $ppn['next'];
		$pages[] = $pfl['last'];

		return $this->Html->div(
			$this->Html->tag('ul', implode('', $pages)),
			array('class' => 'pagination')
		) . $this->Form->hidden('page_actual', $actual);
	}

	public function setQueryParam($param, $value = null) {
		if (is_array($param)) {
			foreach ($param as $key => $val) {
				$this->setQueryParam($key, $val);
			}
		}
		$this->query_array[$param] = $value;
	}

	private function getQueryString(array $default = array()) {
		$params = $default + $this->query_array;
		if (empty($params)) {
			return '';
		}
		return '?' . http_build_query($params);
	}

}
