<?php
/**
 * Ofrece validaciones específicas para Dentons CyC
 *
 * @package The Time Billing
 * @subpackage Plugins
 */

require_once dirname(__FILE__) . '/../conf.php';

$Slim = Slim::getInstance('default', true);

$Slim->hook('hook_validacion_guardar_generadores', function() {
  echo <<<JAVASCRIPT
		/* Valida que la suma de las CRC y PMC no supere el 100% */
		var error = false;
		var crc_user = false;
		var total_pmc_percent = 0;
		var total_crc_percent = 0;
		jQuery('.category-data').each(function() {
			var percent_row = parseInt(jQuery.trim(jQuery(this).parent().find('.percent-data').data('percent_value')));
			var category_name = jQuery.trim(jQuery(this).html()).toUpperCase();

			if (category_name == 'CRC') {
				total_crc_percent += percent_row;
			};

			if (category_name == 'PMC') {
				total_pmc_percent += percent_row;
			};

			if (category_name == 'CRC') {
				crc_user = true;
			};
		});

		if (!crc_user) {
			showGeneratorAlert('alerta', "<?= __('Debe haber al menos un profesional con categoría CRC.') ?>");
			jQuery('#percent_generator').focus();
			error = true;
		};

		if (total_pmc_percent != 100) {
			showGeneratorAlert('alerta', "<?= __('La suma de la categoría PMC debe sumar 100%.') ?>");
			jQuery('#percent_generator').focus();
			error = true;
		};

		if (total_crc_percent != 100) {
			showGeneratorAlert('alerta', "<?= __('La suma de la categoría CRC debe sumar 100%.') ?>");
			jQuery('#percent_generator').focus();
			error = true;
		};

		if (error) {
			return false;
		}
JAVASCRIPT;
});
$Slim->hook('hook_validacion_agregar_usuario', function() {
  echo <<<JAVASCRIPT
		if (!category) {
			showGeneratorAlert('alerta', 'Ingrese todos los datos para agregar el usuario');
			return false;
		};

		/* Valida porcentajes permitidos para REC */
		if ($.trim(\$category.find('option:selected').html()) == 'REC' && (percent < 10 || percent > 100)) {
			showGeneratorAlert('alerta', "<?= __('El porcentaje para la categiría REC debe estar entre 10% y 100%.') ?>");
			error = true;
		};

		/* Valida que el mismo usuario no tenga REC y CRC al mismo tiempo */
		if (category_name == 'CRC' || category_name == 'REC') {
			var change_category = true;
			$('td[data-user_id="' + user + '"]').each(function() {
				var category_name_user = $.trim($(this).parent().find('.category-data').html()).toUpperCase();
				var id = $(this).parent().find('.edit_generator').data('id');

				if (id_agreement_generator != id) {
					if (category_name_user == 'REC' && category_name == 'CRC' ||
							category_name_user == 'CRC' && category_name == 'REC') {
						change_category = false;
					};
				};
			});

			if (!change_category) {
				showGeneratorAlert('alerta', "<?= __('El mismo profesional no puede pertenecer a la categiría REC y CRC al mismo tiempo.') ?>");
				error = true;
			};
		};

		/* Valida que el porcentaje de REC sea 25, 50, 75 ó 100 */
		if ($.trim(\$category.find('option:selected').html()) == 'REC') {
			if (percent != 25 && percent != 50 && percent != 75 && percent != 100) {
				showGeneratorAlert('alerta', "<?= __('El porcentaje de REC debe ser 25%, 50%, 75% ó 100%') ?>");
				$('#percent_generator').focus();
				error = true;
			};
		};

		/* Valida que la suma de las REC no supere el 100% */
		if ($.trim(\$category.find('option:selected').html()) == 'REC') {
			var total_rec_percent = 0;
			$('.category-data').each(function() {
				var percent_row = parseInt($.trim($(this).parent().find('.percent-data').data('percent_value')));
				var category_name = $.trim($(this).html()).toUpperCase();
				var id = $(this).parent().find('.edit_generator').data('id');

				if (category_name == 'REC' && id_agreement_generator != id) {
					total_rec_percent += percent_row;
				};
			});

			total_rec_percent += percent;

			if (total_rec_percent > 100) {
				showGeneratorAlert('alerta', "<?= __('La suma de la categoría REC debe estar entre 10% y 100%.') ?>");
				$('#percent_generator').focus();
				error = true;
			}
		};

		/* Valida que la suma de PMC no supere el 100% */
		if ($.trim(\$category.find('option:selected').html()) == 'PMC') {
			var total_pmc_percent = 0;
			$('.category-data').each(function() {
				var percent_row = parseInt($.trim($(this).parent().find('.percent-data').data('percent_value')));
				var category_name = $.trim($(this).html()).toUpperCase();
				var id = $(this).parent().find('.edit_generator').data('id');

				if (category_name == 'PMC' && id_agreement_generator != id) {
					total_pmc_percent += percent_row;
				};
			});

			total_pmc_percent += percent;

			if (total_pmc_percent > 100) {
				showGeneratorAlert('alerta', "<?= __('La suma de la categoría PMC debe sumar 100%.') ?>");
				$('#percent_generator').focus();
				error = true;
			}
		};

		/* Valida que la suma de CRC no supere el 100% */
		if ($.trim(\$category.find('option:selected').html()) == 'CRC') {
			var total_crc_percent = 0;
			$('.category-data').each(function() {
				var percent_row = parseInt($.trim($(this).parent().find('.percent-data').data('percent_value')));
				var category_name = $.trim($(this).html()).toUpperCase();
				var id = $(this).parent().find('.edit_generator').data('id');

				if (category_name == 'CRC' && id_agreement_generator != id) {
					total_crc_percent += percent_row;
				};
			});

			total_crc_percent += percent;

			if (total_crc_percent > 100) {
				showGeneratorAlert('alerta', "<?= __('La suma de la categoría CRC debe sumar 100%.') ?>");
				$('#percent_generator').focus();
				error = true;
			}
		};
JAVASCRIPT;
});
