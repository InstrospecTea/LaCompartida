<?php

namespace TTB;


class NewRelic {

	public $tenant;
	public $process;
	public $message;

	public function __construct($process = 'TTB')	{
		$this->tenant = Conf::ServerIP();
		$this->process = $process;
	}

	public function notice() {
		if (extension_loaded('newrelic')) {
			newrelic_notice_error("[{$this->tenant}] {$this->process} : {$this->message}");
		}
	}

	public function addMessage($message) {
		$this->message .= "{$message}\n";
		return $this;
	}
}
