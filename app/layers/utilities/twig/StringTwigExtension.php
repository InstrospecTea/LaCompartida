<?php

class StringTwigExtension extends AbstractTwigExtension {

	public function getName() {
		return 'TtbStringExtension';
	}

	public function extUcFirst($s) {
		return ucfirst($s);
	}

}
