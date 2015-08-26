<?php

	echo "Probar aqui";

	$filtersFields = array();
	$grouperFields = array();
	$selectFields = array();

	$currencyId = 1;
	$proportionality = 'cliente';

	$calculator = new BilledAmountDataCalculator(
		$this->Session,
		$filtersFields,
		$grouperFields,
		$selectFields,
		$currencyId,
		$proportionality
	);

	$calculator->calculate();

	$Criteria = $calculator->getWorksCriteria();

	pr($Criteria->get_plain_query());

?>