<?php

namespace TTB\Graficos;

class DatasetPie extends Dataset {

	public function setData(array $data) {
		$background_color = [];
		foreach ($data as $d) {
			$color = $this->getRandomColor(0.8);
			$background_color[] = $color;
			$color = str_replace('0.80', '0.50', $color);
			$hover_background_color[] = $color;
		}
		$this->backgroundColor = $background_color;
		$this->hoverBackgroundColor = $hover_background_color;
		return parent::setData($data);
	}
}
