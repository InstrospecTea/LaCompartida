<?php
$Slim=Slim::getInstance('default',true);

$losdatos=$Slim->view()->getData();
echo '<pre>';
print_r($losdatos);

echo '</pre>';
 
?>
