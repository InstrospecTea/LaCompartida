jQuery(function () {
	jQuery('.inlinehelp').each(function() {
		jQuery(this).popover({title: jQuery(this).attr('title'), trigger: 'hover', animation: true, content: jQuery(this).attr('help')});
	});
});
