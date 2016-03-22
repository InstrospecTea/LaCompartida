<?php

namespace TTB;


class Mixpanel
{

	const TOKEN = '35700d667425ad9858d92ff694febf63';

	private $mixpanelInstance;

	function __construct()
	{
		$this->mixpanelInstance = \Mixpanel::getInstance(Mixpanel::TOKEN);
	}

	public function setUser($id, array $data = null) {
		$this->mixpanelInstance->people->set($id, $data);
	}

	public function identifyAndTrack($id, $event, array $data = null) {
		$this->mixpanelInstance->identify($id);
		$this->mixpanelInstance->track($event, $data);
	}

}
