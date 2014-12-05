<?php

class DateTwigExtension extends AbstractTwigExtension {

	public function getName() {
		return 'TtbDateExtension';
	}

	/**
	 * Formatea una fecha/hora local según la configuración regional (http://php.net/strftime)
	 *
	 * {{'now'|strftime('%B %d, %Y')}}
	 *
	 * @param $d string|DateTime
	 * @param $format string
	 * @param $locale string
	 * @return string
	 */
	public function extStrfTime($d, $format = 'Y-m-d', $locale = 'es_ES') {
		if ($d instanceof \DateTime) {
			$d = $d->format('Y-m-d H:i:s');
		}

		setlocale(LC_ALL, $locale);

		return utf8_encode(strftime($format, strtotime($d)));
	}
}
