<?php

class TranslatorController extends AbstractController {

	public function index() {
		die(__($this->params['text']));
	}
}