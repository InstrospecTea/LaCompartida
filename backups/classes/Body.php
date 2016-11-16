<?php

class Body {

	private $body = '';
	private $endbody = '';

	public function add($text, $to_end = false) {
		$timestamp = date('Y-m-d H:i:s');
		$entry = "[$timestamp] $text";
		echo "$entry\n";
		if ($to_end) {
			$this->endbody .= "$entry <br/>\n";
		} else {
			$this->body .= "$entry <br/>\n";
		}
		return $entry;
	}

	public function __toString() {
		return "{$this->body}<br/><hr/><br/>{$this->endbody}";
	}

}
