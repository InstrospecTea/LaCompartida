<?php

/**
 * Extensi�n para el control de formato en fechas
 */
class DateTwigExtension extends AbstractTwigExtension {

	/**
   * Retorna el nombre de la extensi�n
   *
   * @return string
   */
	public function getName() {
		return 'TtbDateExtension';
	}

	/**
	 * Formatea una fecha/hora local seg�n la configuraci�n regional
	 *
	 * {{'now'|strftime('%B %d, %Y')}}
	 *
	 * @param $d string|DateTime (puede ser un string con formato fecha o un objeto de tipo fecha)
	 * @param $format string (seg�n documentaci�n http://php.net/strftime)
	 * @param $locale string (seg�n documentaci�n http://php.net/locale)
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