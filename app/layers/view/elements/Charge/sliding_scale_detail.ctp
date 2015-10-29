<?php
	$this->EntitiesListator->loadEntities($slidingScales);
	$this->EntitiesListator->setNumberFormatOptions($currency, $language);
	$this->EntitiesListator->addColumn('#', 'order_number');
	$this->EntitiesListator->addColumn('Monto Bruto', 'amount');
	$this->EntitiesListator->addColumn('% Descuento', 'discountRate');
	$this->EntitiesListator->addColumn('Descuento', 'discount');
	$this->EntitiesListator->addColumn('Monto Neto', 'netAmount');
	$this->EntitiesListator->totalizeFields(array('Monto Neto'));
	echo $this->EntitiesListator->render();
