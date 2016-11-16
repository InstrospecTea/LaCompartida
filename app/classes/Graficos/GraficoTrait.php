<?php

namespace TTB\Graficos;

trait GraficoTrait {
	protected function isUTF8($text) {
		return mb_detect_encoding($text, 'UTF-8', true) === 'UTF-8';
	}
}
