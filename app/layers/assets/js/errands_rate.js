var dm_root = root_dir + '/app/Rate/';
var tarifa_temp = '';

jQuery(function() {
	var messages = {};
	var rateSelected = jQuery('#id_tramite_tarifa').val();

	jQuery.ajax({
		url: dm_root + 'ErrandsRateMessages',
		dataType: 'JSON',
		async: false,
		success: function(response) {
			for (var i in response) {
				messages[i] = response[i];
			};
		},
		error: function() {
			//
		}
	});

	jQuery.loadData(rateSelected);

	jQuery('.tarifas').on('focus', function() {
		tarifa_temp = jQuery.trim(jQuery(this).val());
	});

	jQuery('.tarifas').on('blur', function() {
		if (jQuery.trim(jQuery(this).val()) == '' && tarifa_temp != '') {
			if (confirm(messages['seguro_eliminar_valor'])) {
				jQuery(this).val('0');
			} else {
				jQuery(this).val(tarifa_temp);
				jQuery(this).focus();
			}
		}
	});

	jQuery('#id_tramite_tarifa').on('change', function() {
		if (confirm(messages['confirm_cambio_tarifa'])) {
			jQuery.loadData(jQuery('#id_tramite_tarifa').val());
			rateSelected = jQuery('#id_tramite_tarifa').val();
		} else {
			jQuery('#id_tramite_tarifa').val(rateSelected);
		};
	});

	jQuery('#eliminar_tarifa').on('click', function() {
		if (jQuery('[name="tarifa_defecto"]').val() == 0) {
			var num_contratos = jQuery.contractsWithRate(jQuery('#id_tramite_tarifa').val());
			if (num_contratos > 0) {
				if (confirm(messages['tarifa_posee'] + ' ' + num_contratos + ' ' + messages['contratos_asociados'])) {
					jQuery.changeDefaultRateOnContract(jQuery('#id_tramite_tarifa').val());
				};
			} else if (confirm(messages['seguro_eliminar'])) {
				jQuery.deleteRate(jQuery('#id_tramite_tarifa').val());
			};
		} else {
			alert('No puede eliminar la tarifa est�ndar (por defecto)');
		};
	});

	jQuery('#guardar_tarifa').on('click', function() {
		jQuery.saveRate();
	});

	jQuery('#crear_nueva_tarifa').on('click', function() {
		if (!jQuery('#usar_tarifa_previa').is(':checked')) {
			jQuery('input[type="text"], #id_tramite_tarifa_edicion, input[type="checkbox"], [name="tarifa_defecto"]').val(null);
		} else {
			jQuery('input[type="text"][class!="tarifas"], #id_tramite_tarifa_edicion, input[type="checkbox"], [name="tarifa_defecto"]').val(null);
		};
		jQuery('.edicion_tarifa').hide();
		jQuery('.nueva_tarifa').show();
		jQuery('#usar_tarifa_previa').attr('checked', false);
	});

	jQuery('#cancelar_nueva_tarifa').on('click', function() {
		jQuery('input[type="text"]').val(null);
		jQuery('.edicion_tarifa').show();
		jQuery('.nueva_tarifa').hide();
		jQuery.loadData(jQuery('#id_tramite_tarifa').val());
	});
});

jQuery.loadData = function(rate_id) {
	jQuery.ajax({
		url: dm_root + 'ErrandsRateValue',
		data: {
			'id_tarifa': rate_id
		},
		dataType: 'JSON',
		method: 'POST',
		success: function(response) {
			var tarifa;
			jQuery('[name="glosa_tramite_tarifa"]').val(response.errand_rate_detail.glosa_tramite_tarifa);
			jQuery('[name="tarifa_defecto"]').val(response.errand_rate_detail.tarifa_defecto);
			jQuery('[name="id_tramite_tarifa_edicion"]').val(response.errand_rate_detail.id_tramite_tarifa);
			jQuery('.tarifas').val(null);

			jQuery('#label_tarifa_defecto, #tarifa_no_defecto').css({display: 'none'});

			if (response.errand_rate_detail.tarifa_defecto == 1) {
				jQuery('#label_tarifa_defecto').css({display: 'block'});
			} else {
				jQuery('#tarifa_no_defecto').css({display: 'block'});
			};

			for (var i = 0; i < response['errands_rate_values'].length; ++i) {
				tarifa = response['errands_rate_values'][i];
				jQuery('[name="tarifa_moneda[' + tarifa['id_moneda'] + '][' + tarifa['id_tramite_tipo'] + ']"]').val(tarifa['tarifa']);
			};
		},
		error: function() {
			//
		}
	});
};

jQuery.deleteRate = function(rate_id) {
	jQuery.ajax({
		url: dm_root + 'deleteErrandRate',
		data: {
			'id_tarifa': rate_id
		},
		dataType: 'JSON',
		method: 'POST',
		success: function(response) {
			if (response.success) {
				setTimeout(function(){
					alert(response.message);
					location.reload();
				}, 1);
			} else {
				alert(response.message);
			};
		},
		error: function(response) {
			//
		}
	});
};

jQuery.saveRate = function() {
	var params = {};

	if (parseInt(jQuery('#id_tramite_tarifa_edicion').val())) {
		params.rate_id = jQuery('#id_tramite_tarifa_edicion').val();
	};

	if (jQuery('#checkbox_tarifa_defecto').is(':checked') || jQuery('[name="tarifa_defecto"]').val() == 1) {
		params.tarifa_defecto = 1;
	} else {
		params.tarifa_defecto = 0;
	};

	if (jQuery.trim(jQuery('#glosa_tramite_tarifa').val()) == '') {
		alert('La glosa de la tarifa no puede estar vac�a');
		jQuery('#glosa_tramite_tarifa').focus();
		return false;
	};

	params.glosa_tramite_tarifa = jQuery.trim(jQuery('#glosa_tramite_tarifa').val());

	jQuery('.tarifas').each(function() {
		var rate = jQuery(this);
		if (rate.val() != '') {
			if (typeof params.rates == 'undefined') {
				params.rates = [];
			};

			params.rates.push({
				id_tramite_tipo: rate.data('errandtype'),
				id_moneda: rate.data('coin'),
				tarifa: rate.val()
			});
		};
	});

	jQuery.ajax({
		url: dm_root + 'saveErrandRate',
		data: {
			params
		},
		dataType: 'JSON',
		method: 'POST',
		success: function(response) {
			if (response.success) {
				setTimeout(function(){
					alert(response.message);
					location.reload();
				}, 1);
			} else {
				alert(response.message);
			};
		},
		error: function(response) {
			//
		}
	});
};

jQuery.contractsWithRate = function(rate_id) {
	var num_contratos = 0;

	jQuery.ajax({
		url: dm_root + 'contractsWithErrandRate',
		data: {
			'id_tarifa': rate_id
		},
		dataType: 'JSON',
		method: 'POST',
		async: false,
		success: function(response) {
			num_contratos = response.num_rows;
		},
		error: function() {
			//
		}
	});

	return num_contratos;
};

jQuery.changeDefaultRateOnContract = function(rate_id) {
	var num_contratos = 0;

	jQuery.ajax({
		url: dm_root + 'changeDefaultErrandRateOnContracts',
		data: {
			'id_tarifa': rate_id
		},
		dataType: 'JSON',
		method: 'POST',
		async: false,
		success: function(response) {
			if (response.success) {
				jQuery.deleteRate(rate_id);
			};
		},
		error: function() {
			//
		}
	});

	return num_contratos;
};
