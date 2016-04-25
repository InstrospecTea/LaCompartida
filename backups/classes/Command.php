<?php

class Command {

	private $params = [];
	private $opts = [];
	private $command = '';
	private $params_first = false;

	public function __construct($command, $params_first = true) {
		$this->command = $command;
		$this->params_first = $params_first;
	}

	public function create($command, $params_first = true) {
		$instance = new self($command, $params_first);
		return $instance;
	}
	public function opt($opt, $value = null) {
		if ($value === 0) {
			$value = '0';
		} else if ($value === false) {
			$value = 'false';
		}
		$this->opts[] = "--{$opt}" . (!is_null($value) ? "={$value}" : '');
		return $this;
	}

	public function param($param) {
		$this->params[] = $param;
		return $this;
	}

	public function run() {
		exec($this, $output);
		return $output;
	}

	public function __toString() {
		if ($this->params_first) {
			$params_opts = implode(' ', $this->params) . ' ' . implode(' ', $this->opts);
		} else {
			$params_opts = implode(' ', $this->opts) . ' ' . implode(' ', $this->params);
		}
		return "$this->command $params_opts";
	}
}
