<?php

$Slim=Slim::getInstance('default',true);
$Slim->hook('hook_header', 'Bootstrap_header');

$Slim->hook('hook_footer', 'Tooltip_JS');

$Slim->hook('hook_footer_popup', 'Tooltip_JS');

function Bootstrap_header() {
	
}


function Tooltip_JS() {
	echo "jQuery('.tooltip').each(function() {
			var drivetip=jQuery(this).closest('td').attr('onmouseover');			
			jQuery(this).closest('td').removeAttr('onmouseover');
			
			jQuery(this).closest('td').removeAttr('onmouseout');
			jQuery(this).tooltip({
				title:drivetip,
				placement: jQuery(this).attr('placement')
			});
	});";
	
}
?>
