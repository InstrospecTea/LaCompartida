<?php

class NewRelic {

	public $tenant;
	public $process;
	public $message;

	public function __construct($process = 'TTB')	{
		$this->tenant = Conf::ServerIP();
		$this->process = $process;
		$this->message = '';
	}

	public function notice() {
		if (extension_loaded('newrelic')) {
			newrelic_notice_error("[{$this->tenant}] {$this->process} : {$this->message}");
			$this->message = '';
		} else {
			Log::write('La extension newrelic no esta cargada');
			Log::write("[{$this->tenant}] {$this->process} : {$this->message}");
		}
	}

	public function addMessage($message) {
		$this->message = $this->message . "{$message}\n";
		return $this;
	}
}
