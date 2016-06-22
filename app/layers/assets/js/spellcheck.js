(function ($) {
	$.each($('textarea[role="spellcheck"]'), function (k, element) {
		$el = $(element);
		var googie = new GoogieSpell({
			server_url: '//garfield.thetimebilling.com/spell',
			decoration: true,
			img_dir: root_dir + '/app/layers/assets/googiespell/',
			show_spell_img: true
		});
		googie.main_controller = true;
		googie.decorateTextarea($el.attr('id'));
		$el.data('googie', googie);
	});
})(jQuery);
