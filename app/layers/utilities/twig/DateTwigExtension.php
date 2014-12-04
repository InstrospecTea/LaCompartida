<?php

class DateTwigExtension extends AbstractTwigExtension {

	public function getName() {
		return 'TtbDateExtension';
	}

	public function extStrfTime($d, $format = 'Y-m-d', $locale = 'es_CL') {
		if ($d instanceof \DateTime) {
    	$d = $d->format('Y-m-d H:i:s');
    }
    setlocale(LC_ALL, $locale);
    return utf8_encode(strftime($format, strtotime($d)));
	}
}
