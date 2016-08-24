<?php

namespace TTB\Graficos;

class DatasetPie extends Dataset {

	public function setData(array $data) {
		$background_color = [];
		foreach ($data as $d) {
			$background_color[] = $this->getRandomColor();
		}
		$this->backgroundColor = $background_color;
		return parent::setData($data);
	}
}
