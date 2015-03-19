<?php 
if ($charge->get('forma_cobro') == 'ESCALONADA') {
	echo $this->element('Charge/sliding_scale_detail', array('slidingScales' => $slidingScales, 'currency' => $currency, 'language' => $language));
}
?>
<br/>
<?php 
echo $this->element('Charge/fee_discount_detail', array(
	'feeDetiail' => $feeDetiail,
	'currency' => $currency,
	'language' => $language
));
?>