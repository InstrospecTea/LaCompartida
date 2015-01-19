<?php 
$listator = new EntitiesListator();

$fees = new GenericModel();
$fees->set('title', __('Subtotal Honorarios'), false);
$fees->set('amount', $feeDetiail->get('subtotal_honorarios'), false);

$discount = new GenericModel();
$discount->set('title', __('Descuento'), false);
$discount->set('amount', $feeDetiail->get('descuento_honorarios'), false);

$total = new GenericModel();
$total->set('title', __('Total'), false);
$total->set('amount', $feeDetiail->get('saldo_honorarios'), false);

$listator->loadEntities(array($fees, $discount, $total));
$listator->setNumberFormatOptions($currency, $language);
$listator->addColumn('Detalle', 'title');
$listator->addColumn('Monto', 'amount');

echo $listator->render();