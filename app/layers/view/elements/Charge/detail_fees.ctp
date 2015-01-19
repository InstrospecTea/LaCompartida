<?php 
echo $this->element('Charge/sliding_scale_detail', array('slidingScales' => $slidingScales));
?>
<br/>
<?php 
echo $this->element('Charge/fee_discount_detail', array(
	'feeDetiail' => $feeDetiail,
	'currency' => $currency,
	'language' => $language
));
?>