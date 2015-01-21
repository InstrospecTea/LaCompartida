<?php

class EntitiesListator {

	private $entities;
	private $columnHandler;
	private $trWriter;
	private $totalizedFields;
	private $formatOptions;

	/**
	 * @param array $entities
	 * @throws UtilityException
	 */
	public function loadEntities(array $entities) {
		foreach ($entities as $element) {
			if (!$this->checkElementClass($element)) {
				throw new UtilityException('One of the elements on the Listator is not a subclass of Entity.');
			}
		}
		$this->entities = $entities;
		$this->columnHandler = array();
	}

	/**
	 * @param $element
	 * @return bool
	 */
	private function checkElementClass($element) {
		if (!is_subclass_of($element, 'Entity')) {
			return false;
		} else {
			return true;
		}
	}

	public function setNumberFormatOptions(Currency $currency, Language $language) {
		if (is_null($currency) || is_null($language)) {
			$this->formatOptions = array();
		} else {
			$this->formatOptions['currency'] = $currency;
			$this->formatOptions['language'] = $language;
		}

	}

	/**
	 * @param $columnName
	 * @param $calculationExpression
	 */
	public function addColumn($columnName, $calculationExpression) {
		$this->columnHandler[] = array(
			'columnName' => $columnName,
			'calculationExpression' => $calculationExpression
		);
	}

	public function totalizeFields(array $fields) {
		$this->totalizedFields = $fields;
	}

	public function addOption($columnName, $optionType) {
		$calculationExpression = function(Entity $entity) {
			return '';
		};
		switch ($optionType) {
			case 'check':
				$calculationExpression = function (Entity $entity) {
					$htmlBuilder = new HtmlBuilder();
					$htmlBuilder->set_closure(false)->set_tag('input');
					$htmlBuilder->add_attribute('type', 'checkbox');
					$htmlBuilder->add_attribute('name', 'selected[]');
					$htmlBuilder->add_attribute('value', $entity->get($entity->getIdentity()));
					return $htmlBuilder->render();
				};
				break;
			default:
				break;
		}
		$this->columnHandler[] = array(
			'columnName' => $columnName,
			'calculationExpression' => $calculationExpression
		);
	}


	/**
	 * @return HtmlBuilder
	 */
	private function generateHeader() {
		$thead = new HtmlBuilder();
		$thead
			->set_tag('thead')
			->set_closure(true);
		$tr = new HtmlBuilder();
		$tr
			->set_tag('tr')
			->add_attribute('class', 'encabezado')
			->set_closure(true);

		foreach ($this->columnHandler as $columnHandler) {
			$th = new HtmlBuilder();
			$th->set_tag('td')
				->set_closure(true)
				->add_attribute('class', 'encabezado')
				->add_attribute('style','padding-left:10px;padding-right:10px;')
				->set_html($columnHandler['columnName']);
			$tr->add_child($th);
		}
		$thead->add_child($tr);
		return $thead;
	}

	/**
	 * @return HtmlBuilder
	 */
	private function generateBody() {
		$currency = $this->formatOptions['currency'];
		$language = $this->formatOptions['language'];
		$tbody = new HtmlBuilder();
		$tbody
			->set_tag('tbody')
			->set_tag('class', 'cuerpobuscador')
			->set_closure(true);
		foreach ($this->entities as $key => $entity) {
			if (!empty($this->trWriter)) {
				$tr = call_user_func($this->trWriter, $entity, $key);
			} else {
				$tr = new HtmlBuilder();
				$tr
					->set_tag('tr')
					->set_closure(true);
				foreach ($this->columnHandler as $columnHandler) {
					$th = new HtmlBuilder();
					$th->set_tag('td')->set_closure(true)->add_attribute('style','padding-left:10px;padding-right:10px;text-align:center;');
					if (is_callable($columnHandler['calculationExpression'])) {
						$th->set_html(call_user_func($columnHandler['calculationExpression'], $entity));
					} else {
						$currency = $this->formatOptions['currency'];
						$language = $this->formatOptions['language'];
						if (!empty($currency) && !empty($language)) {
							$th->add_attribute('style', 'text-align:right;');
							$th->set_html(number_format($entity->get($columnHandler['calculationExpression']), $currency->get('cifras_decimales'), $language->get('separador_decimales'), $language->get('separador_miles')));
						} else {
							$th->set_html($entity->get($columnHandler['calculationExpression']));
						}
					}
					$tr->add_child($th);
				}
			}
			$tbody->add_child($tr);
		}
		if (!empty($this->totalizedFields)) {
			$tbody->add_child($this->generateTotalization());
		}
		return $tbody;
	}

	private function generateTotalization() {
		$currency = $this->formatOptions['currency'];
		$language = $this->formatOptions['language'];
		$accumulators = array();
		foreach ($this->columnHandler as $column) {
			if (in_array($column['columnName'], $this->totalizedFields)) {
				if (!is_callable($column['calculationExpression'])) {
					$accumulators[$column['calculationExpression']] = 0;
				} else {
					throw new UtilityException('The totalization could not be done for a column with custom function row writer.');
				}
			}
		}
		foreach ($this->entities as $entity) {
			foreach ($accumulators as $acumKey => $accumulator) {
				$toAcumulate = $entity->get($acumKey);
				$accumulators[$acumKey] = $accumulator + $toAcumulate;
			}
		}
		$tr = new HtmlBuilder();
		$tr
			->set_tag('tr')
			->set_closure(true);
		foreach ($this->columnHandler as $column) {
			if (array_key_exists($column['calculationExpression'], $accumulators)) {
				$th = new HtmlBuilder();
				$th->set_tag('td')->set_closure(true)->add_attribute('style','padding-left:10px;padding-right:10px;text-align:center;');
				if (!empty($currency) && !empty($language)) {
					$th->add_attribute('style', 'text-align:right;');
					$th->set_html($currency->get('simbolo').' '.number_format($accumulators[$column['calculationExpression']], $currency->get('cifras_decimales'), $language->get('separador_decimales'), $language->get('separador_miles')));
				} else {
					$th->set_html($accumulators[$column['calculationExpression']]);
				}
				$tr->add_child($th);
			} else {
				$th = new HtmlBuilder();
				$th->set_tag('td')->set_closure(true)->add_attribute('style','padding-left:10px;padding-right:10px;');
				$th->set_html('');
				$tr->add_child($th);
			}
		}
		return $tr;
	}

	/**
	 * @return string
	 */
	public function render() {
		$table = new HtmlBuilder();
		$table
			->set_tag('table')
			->add_attribute('class', 'buscador')
			->add_attribute('cellpading', 2)
			->add_attribute('style', 'width: 100%')
			->set_closure(true);
		$table->add_child($this->generateHeader());
		$table->add_child($this->generateBody());
		return $table->render();
	}

	/**
	 * Redefine la función para escribir las filas del listado.
	 * @param type $callable
	 * @throws Exception
	 */
	public function trWriter($callable) {
		if (!is_callable($callable)) {
			throw new Exception('El valor definido en trWriter debe ser una función válida.');
		}
		$this->trWriter = $callable;
	}


}
