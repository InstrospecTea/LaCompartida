<?php

class DateTwigExtension extends AbstractTwigExtension {

	public function getName() {
		return 'TtbDateExtension';
	}

	public function extStrfTime($d, $format = 'Y/m/d') {
		if ($d instanceof \DateTime) {
    	$d = $d->format('Y/m/d');
    }
    // setlocale(LC_ALL,'French');
    return utf8_encode(strftime($format, strtotime($d)));
	}
}
