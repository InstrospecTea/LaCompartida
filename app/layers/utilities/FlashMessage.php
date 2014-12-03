<?php

/**
 * Class FlashMessage
 */
class FlashMessage extends AbstractUtility {

	protected $content;
 	protected $type;

	/**
	 * Genera un mensaje flash.
	 * @param $type string Define los tipos de mensaje que son reconocidos. Estos son: E (Error), I (Información) y S (Success).
	 * @param $content string Establece el contenido del mensaje.
	 */
	function __construct($type, $content) {
		$this->content = $content;
		$this->type = $type;
	}

} 