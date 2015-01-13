<?php
require_once dirname(__FILE__) . '/../conf.php';

$validationExceptions = Conf::GetConf($Sesion, 'ValidacionesClienteExcepciones');
$skippedArray = !is_null($validationExceptions) ? explode(',', $validationExceptions) : array();
if (!isset($validacionesClienteJS) || is_null($validacionesClienteJS)) {
	$validacionesClienteJS = 'true';
}
$contractValidation = new ValidationHelper($Sesion, array('disableServer' => !$validacionesCliente,  'validateClient' => $validacionesClienteJS, 'skipped' => $skippedArray));

	# Validar la existencia del RUT del cliente 
	# datos de para facturación
	$error_message = __('Debe ingresar el') . ' ' . __('RUT') . ' ' . __('del cliente');
	$contractValidation->registerValidation(
		'factura_rut', array(
			'value' => $factura_rut,
			'server' => function($field) use ($Pagina, $error_message, $validacionesCliente) {
					if ($validacionesCliente) {
						if (empty($field)) {
							$Pagina->AddError($error_message);
						}
					}
				},
			'client' => function($field_name) use ($error_message) {
				$script = <<<SCRIPT
					if (!form.$field_name.value){
						alert("{$error_message}");
						if (typeof MuestraPorValidacion != 'undefined') {
							MuestraPorValidacion('datos_factura');
						}
						form.$field_name.focus();
						return false;
					}
SCRIPT;
				return $script;
			}
		)
	);

	$error_message = __('Debe ingresar la razón social del cliente');
	$contractValidation->registerValidation(
		'factura_razon_social', array(
			'value' => $factura_razon_social,
			'server' => function($field) use ($Pagina, $error_message, $validacionesCliente) {
					if ($validacionesCliente) {
						if (empty($field)) {
							$Pagina->AddError($error_message);
						}
					}
				},
			'client' => function($field_name) use ($error_message) {
				$script = <<<SCRIPT
					if (!form.$field_name.value){
						alert("{$error_message}");
						if (typeof MuestraPorValidacion != 'undefined') {
							MuestraPorValidacion('datos_factura');
						}
						form.$field_name.focus();
						return false;
					}
SCRIPT;
				return $script;
			}
		)
	);


	$error_message = __('Debe ingresar el giro del cliente');
	$contractValidation->registerValidation(
		'factura_giro', array(
			'value' => $factura_giro,
			'server' => function($field) use ($Pagina, $error_message, $validacionesCliente) {
					if ($validacionesCliente) {
						if (empty($field)) {
							$Pagina->AddError($error_message);
						}
					}
				},
			'client' => function($field_name) use ($error_message) {
				$script = <<<SCRIPT
					if (!form.$field_name.value){
						alert("{$error_message}");
						if (typeof MuestraPorValidacion != 'undefined') {
							MuestraPorValidacion('datos_factura');
						}
						form.$field_name.focus();
						return false;
					}
SCRIPT;
				return $script;
			}
		)
	);

	$error_message = __('Debe ingresar la dirección del cliente');
	$contractValidation->registerValidation(
		'factura_direccion', array(
			'value' => $factura_direccion,
			'server' => function($field) use ($Pagina, $error_message, $validacionesCliente) {
					if ($validacionesCliente) {
						if (empty($field)) {
							$Pagina->AddError($error_message);
						}
					}
				},
			'client' => function($field_name) use ($error_message) {
				$script = <<<SCRIPT
					if (!form.$field_name.value){
						alert("{$error_message}");
						if (typeof MuestraPorValidacion != 'undefined') {
							MuestraPorValidacion('datos_factura');
						}
						form.$field_name.focus();
						return false;
					}
SCRIPT;
				return $script;
			}
		)
	);

	$error_message = __('Debe ingresar la comuna del cliente');
	$contractValidation->registerValidation(
		'factura_comuna', array(
			'value' => $factura_comuna,
			'server' => function($field) use ($Pagina, $error_message, $validacionesCliente) {
					if ($validacionesCliente) {
						if (empty($field)) {
							$Pagina->AddError($error_message);
						}
					}
				},
			'client' => function($field_name) use ($error_message) {
				$script = <<<SCRIPT
					if (!form.$field_name.value){
						alert("{$error_message}");
						if (typeof MuestraPorValidacion != 'undefined') {
							MuestraPorValidacion('datos_factura');
						}
						form.$field_name.focus();
						return false;
					}
SCRIPT;
				return $script;
			}
		)
	);

	$error_message = __('Debe ingresar la ciudad del cliente');
	$contractValidation->registerValidation(
		'factura_ciudad', array(
			'value' => $factura_ciudad,
			'server' => function($field) use ($Pagina, $error_message, $validacionesCliente) {
				if ($validacionesCliente) {
					if (empty($field)) {
						$Pagina->AddError($error_message);
					}
				}
			},
			'client' => function($field_name) use ($error_message) {
				$script = <<<SCRIPT
						if (!form.$field_name.value){
							alert("{$error_message}");
							if (typeof MuestraPorValidacion != 'undefined') {
								MuestraPorValidacion('datos_factura');
							}
							form.$field_name.focus();
							return false;
						}
SCRIPT;
				return $script;
			}
		)
	);

	if (Conf::GetConf($Sesion, 'RegionCliente')) {
		$error_message = __('Debe ingresar el estado del cliente');
		$contractValidation->registerValidation(
			'region_cliente', array(
				'value' => $region_cliente,
				'server' => function($field) use ($Pagina, $error_message, $validacionesCliente) {
					if ($validacionesCliente) {
						if (empty($field)) {
							$Pagina->AddError($error_message);
						}
					}
				},
				'client' => function($field_name) use ($error_message) {
					$script = <<<SCRIPT
						if (!form.$field_name.value){
							alert("{$error_message}");
							if (typeof MuestraPorValidacion != 'undefined') {
								MuestraPorValidacion('datos_factura');
							}
							form.$field_name.focus();
							return false;
						}
SCRIPT;
					return $script;
				}
			)
		);
	}

	$error_message = __('Debe ingresar el pais del cliente');
	$contractValidation->registerValidation(
		'id_pais', array(
			'value' => $id_pais,
			'server' => function($field) use ($Pagina, $error_message, $validacionesCliente) {
					if ($validacionesCliente) {
						if (empty($field)) {
							$Pagina->AddError($error_message);
						}
					}
				},
			'client' => function($field_name) use ($error_message) {
				$script = <<<SCRIPT
					if (form.$field_name.options[0].selected == true){
						alert("{$error_message}");
						if (typeof MuestraPorValidacion != 'undefined') {
							MuestraPorValidacion('datos_factura');
						}
						form.$field_name.focus();
						return false;
					}
SCRIPT;
				return $script;
			}
		)
	);


	$error_message = __('Debe ingresar el codigo de area del teléfono');
	$contractValidation->registerValidation(
		'cod_factura_telefono', array(
			'value' => $cod_factura_telefono,
			'server' => function($field) use ($Pagina, $error_message, $validacionesCliente) {
					if ($validacionesCliente) {
						if (empty($field)) {
							$Pagina->AddError($error_message);
						}
					}
				},
			'client' => function($field_name) use ($error_message) {
				$script = <<<SCRIPT
					if (!form.$field_name.value){
						alert("{$error_message}");
						if (typeof MuestraPorValidacion != 'undefined') {
							MuestraPorValidacion('datos_factura');
						}
						form.$field_name.focus();
						return false;
					}
SCRIPT;
				return $script;
			}
		)
	);


	$error_message = __('Debe ingresar el número de telefono');
	$contractValidation->registerValidation(
		'factura_telefono', array(
			'value' => $factura_telefono,
			'server' => function($field) use ($Pagina, $error_message, $validacionesCliente) {
					if ($validacionesCliente) {
						if (empty($field)) {
							$Pagina->AddError($error_message);
						}
					}
				},
			'client' => function($field_name) use ($error_message) {
				$script = <<<SCRIPT
					if (!form.$field_name.value){
						alert("{$error_message}");
						if (typeof MuestraPorValidacion != 'undefined') {
							MuestraPorValidacion('datos_factura');
						}
						form.$field_name.focus();
						return false;
					}
SCRIPT;
				return $script;
			}
		)
	);


	$error_message = __('Debe ingresar el número de telefono');
	$contractValidation->registerValidation(
		'factura_telefono', array(
			'value' => $factura_telefono,
			'server' => function($field) use ($Pagina, $error_message, $validacionesCliente) {
					if ($validacionesCliente) {
						if (empty($field)) {
							$Pagina->AddError($error_message);
						}
					}
				},
			'client' => function($field_name) use ($error_message) {
				$script = <<<SCRIPT
					if (!form.$field_name.value){
						alert("{$error_message}");
						if (typeof MuestraPorValidacion != 'undefined') {
							MuestraPorValidacion('datos_factura');
						}
						form.$field_name.focus();
						return false;
					}
SCRIPT;
				return $script;
			}
		)
	);

	 
	if (Conf::GetConf($Sesion, 'TituloContacto')) {
		$error_message = __('Debe ingresar el titulo del solicitante');
		$contractValidation->registerValidation(
			'titulo_contacto', array(
				'value' => $titulo_contacto,
				'server' => function($field) use ($Pagina, $error_message, $validacionesCliente) {
					if ($validacionesCliente) {
						if (empty($field)) {
							$Pagina->AddError($error_message);
						}
					}
				},
				'client' => function($field_name) use ($error_message) {
					$script = <<<SCRIPT
						if (form.$field_name.options[0].selected == true){
							alert("{$error_message}");
							if (typeof MuestraPorValidacion != 'undefined') {
								MuestraPorValidacion('datos_solicitante');
							}
							form.$field_name.focus();
							return false;
						}
SCRIPT;
					return $script;
				}
			)
		); 

		$error_message = __('Debe ingresar el nombre del solicitante');
		$contractValidation->registerValidation(
			'nombre_contacto', array(
				'value' => $nombre_contacto,
				'server' => function($field) use ($Pagina, $error_message, $validacionesCliente) {
					if ($validacionesCliente) {
						if (empty($field)) {
							$Pagina->AddError($error_message);
						}
					}
				},
				'client' => function($field_name) use ($error_message) {
					$script = <<<SCRIPT
						if (!form.$field_name.value){
							alert("{$error_message}");
						if (typeof MuestraPorValidacion != 'undefined') {
							MuestraPorValidacion('datos_solicitante');
							}
						form.$field_name.focus();
						return false;
					}
SCRIPT;
				return $script;
			}
		)
	);


	$error_message =  __('Debe ingresar el apellido del solicitante');
	$contractValidation->registerValidation(
		'apellido_contacto', array(
			'value' => $apellido_contacto,
			'server' => function($field) use ($Pagina, $error_message, $validacionesCliente) {
				if ($validacionesCliente) {
					if (empty($field)) {
						$Pagina->AddError($error_message);
					}
				}
			},
			'client' => function($field_name) use ($error_message) {
				$script = <<<SCRIPT
					if (!form.$field_name.value){
						alert("{$error_message}");
						if (typeof MuestraPorValidacion != 'undefined') {
							MuestraPorValidacion('datos_solicitante');
						}
						form.$field_name.focus();
						return false;
					}
SCRIPT;
				return $script;
			}
		)
	);
} else {
	$error_message = __("Por favor ingrese los datos de contacto del solicitante");
	$contractValidation->registerValidation(
		'contacto', array(
			'value' => $contacto,
			'server' => function($field) use ($Pagina, $error_message, $validacionesCliente) {
				if ($validacionesCliente) {
					if (empty($field)) {
						$Pagina->AddError($error_message);
					}
				}
			},
			'client' => function($field_name) use ($error_message) {
				$script = <<<SCRIPT
					if (!form.$field_name.value){
						alert("{$error_message}");
						if (typeof MuestraPorValidacion != 'undefined') {
							MuestraPorValidacion('datos_solicitante');
						}
						form.$field_name.focus();
						return false;
					}
SCRIPT;
				return $script;
			}
		)
	);
}

$error_message =  __('Debe ingresar el teléfono del solicitante');
$contractValidation->registerValidation(
	'fono_contacto_contrato', array(
		'value' => $fono_contacto_contrato,
		'server' => function($field) use ($Pagina, $error_message, $validacionesCliente) {
			if ($validacionesCliente) {
				if (empty($field)) {
					$Pagina->AddError($error_message);
				}
			}
		},
		'client' => function($field_name) use ($error_message) {
			$script = <<<SCRIPT
				if (!form.$field_name.value){
					alert("{$error_message}");
					if (typeof MuestraPorValidacion != 'undefined') {
						MuestraPorValidacion('datos_solicitante');
					}
					form.$field_name.focus();
					return false;
				}
SCRIPT;
			return $script;
		}
	)
);

$error_message = __('Debe ingresar el email del solicitante');
$contractValidation->registerValidation(
	'email_contacto_contrato', array(
		'value' => $email_contacto_contrato,
		'server' => function($field) use ($Pagina, $error_message, $validacionesCliente) {
			if ($validacionesCliente) {
				if (empty($field)) {
					$Pagina->AddError($error_message);
				}
			}
		},
		'client' => function($field_name) use ($error_message) {
			$script = <<<SCRIPT
				if (!form.$field_name.value){
					alert("{$error_message}");
					if (typeof MuestraPorValidacion != 'undefined') {
						MuestraPorValidacion('datos_solicitante');
					}
					form.$field_name.focus();
					return false;
				}
SCRIPT;
			return $script;
		}
	)
);

$error_message =  __('Debe ingresar la dirección de envío del solicitante');
$contractValidation->registerValidation(
	'direccion_contacto_contrato', array(
		'value' => $direccion_contacto_contrato,
		'server' => function($field) use ($Pagina, $error_message, $validacionesCliente) {
			if ($validacionesCliente) {
				if (empty($field)) {
					$Pagina->AddError($error_message);
				}
			}
		},
		'client' => function($field_name) use ($error_message) {
			$script = <<<SCRIPT
				if (!form.$field_name.value){
					alert("{$error_message}");
					if (typeof MuestraPorValidacion != 'undefined') {
						MuestraPorValidacion('datos_solicitante');
					}
					form.$field_name.focus();
					return false;
				}
SCRIPT;
			return $script;
		}
	)
);


$error_message =  __('Debe seleccionar un tipo de tarifa');
$contractValidation->registerValidation(
	'tipo_tarifa', array(
		'value' => $tipo_tarifa,
		'server' => null,
		'client' => function($field_name) use ($error_message) {
			$script = <<<SCRIPT
				if (!(form.tipo_tarifa[0].checked || form.tipo_tarifa[1].checked)){
					alert("{$error_message}");
					if (typeof MuestraPorValidacion != 'undefined') {
						MuestraPorValidacion('datos_cobranza');
					}
					form.tipo_tarifa[0].focus();
					return false;
				}
SCRIPT;
			return $script;
		}
	)
);


$error_message =  __("Por favor ingrese la tarifa en la tarificación");
$contractValidation->registerValidation(
	'id_tarifa', array(
		'value' => $id_tarifa,
		'server' => function($field) use ($Pagina, $error_message, $validacionesCliente) {
			if ($validacionesCliente) {
				if (empty($field)) {
					$Pagina->AddError($error_message);
				}
			}
		},
		'client' => null
	)
);

$error_message =  __("Por favor ingrese la moneda de la tarifa en la tarificación");
$contractValidation->registerValidation(
	'id_moneda', array(
		'value' => $id_moneda,
		'server' => function($field) use ($Pagina, $error_message, $validacionesCliente) {
			if ($validacionesCliente) {
				if (empty($field)) {
					$Pagina->AddError($error_message);
				}
			}
		},
		'client' => null
	)
);



$error_message =  __('Ud. ha seleccionado una tarifa plana pero no ha ingresado el monto.');
$contractValidation->registerValidation(
	'tarifa_flat', array(
		'value' => $tarifa_flat,
		'server' => function($field) use ($Pagina, $tipo_tarifa, $error_message, $validacionesCliente) {
			if ($validacionesCliente) {
				if (empty($field) && !empty($tipo_tarifa) && $tipo_tarifa == 'flat') {
					$Pagina->AddError($error_message);
				}
			}
		},
		'client' => function($field_name) use ($error_message) {
			$script = <<<SCRIPT
				if (form.tipo_tarifa[1].checked && form.$field_name.value.length == 0){
					alert("{$error_message}");
					if (typeof MuestraPorValidacion != 'undefined') {
						MuestraPorValidacion('datos_cobranza');
					}
					form.$field_name.focus();
					return false;
				}
SCRIPT;
			return $script;
		}
	)
);
		 
$error_message =  __("Por favor ingrese la forma de ") . __("cobro") . __(" en la tarificación");
$contractValidation->registerValidation(
	'forma_cobro', array(
		'value' => $forma_cobro,
		'server' => function($field) use 
			($Pagina, $error_message, $forma_cobro, $monto, $retainer_horas, $id_moneda_monto, $fecha_inicio_cap, $hito_fecha, $hito_descripcion, $hito_monto_estimado, $validacionesCliente) {
			if ($validacionesCliente) {
				if (empty($field)) {
					$Pagina->AddError($error_message);
				} else {
					switch ($forma_cobro) {
						case "RETAINER":
							if ((empty($monto) && $monto != 0) || $monto == '') {
								$Pagina->AddError(__("Por favor ingrese el monto para el retainer en la tarificación"));
							}
							if ($retainer_horas <= 0) {
								$Pagina->AddError(__("Por favor ingrese las horas para el retainer en la tarificación"));
							}
							if (empty($id_moneda_monto)) {
								$Pagina->AddError(__("Por favor ingrese la moneda para el retainer en la tarificación"));
							}
							break;
						case "FLAT FEE":
							if (empty($monto)) {
								$Pagina->AddError(__("Por favor ingrese el monto para el flat fee en la tarificación"));
							}
							if (empty($id_moneda_monto)) {
								$Pagina->AddError(__("Por favor ingrese la moneda para el flat fee en la tarificación"));
							}
							break;
						case "CAP":
							if (empty($monto)) {
								$Pagina->AddError(__("Por favor ingrese el monto para el cap en la tarificación"));
							}
							if (empty($id_moneda_monto)) {
								$Pagina->AddError(__("Por favor ingrese la moneda para el cap en la tarificación"));
							}
							if (empty($fecha_inicio_cap)) {
								$Pagina->AddError(__("Por favor ingrese la fecha de inicio para el cap en la tarificación"));
							}
							break;
						case "PROPORCIONAL":
							if (empty($monto)) {
								$Pagina->AddError(__("Por favor ingrese el monto para el proporcional en la tarificación"));
							}
							if ($retainer_horas <= 0) {
								$Pagina->AddError(__("Por favor ingrese las horas para el proporcional en la tarificación"));
							}
							if (empty($id_moneda_monto)) {
								$Pagina->AddError(__("Por favor ingrese la moneda para el proporcional en la tarificación"));
							}
							break;
						case "ESCALONADA":
							if (empty($_POST['esc_tiempo'][0])) {
								$Pagina->AddError(__("Por favor ingrese el tiempo para la primera escala"));
							}
							break;
						case "TASA":
						case "HITOS":
							$valid_date = (count($hito_fecha) <= 1 && empty($hito_fecha[1]));
							$valid_description = (count($hito_descripcion) <= 1 && empty($hito_descripcion[1]));
							$valid_amount = (count($hito_monto_estimado) <= 1 && empty($hito_descripcion[1]));

							if ($valid_date || $valid_description || $valid_amount) {
								$Pagina->AddError(__("Debe ingresar a lo menos un hito cuando HITOS es la forma de cobro"));
							}
							break;
						default:
							$Pagina->AddError($error_message);
					}
				}
			}
		},
		'client' => function($field_name) use ($error_message) {
			$script = <<<SCRIPT
				if (!$$('[name="forma_cobro"]').any(function(elem) {
					return elem.checked;
				})) {
					alert("{$error_message}");
					form.forma_cobro[0].focus();
					return false;
				}

				var forma_cobro = jQuery('#div_cobro').children("input:checked").val();

				if (forma_cobro == 'RETAINER' && (form.monto.value <= 0 || form.monto.value == '') && (form.monto_posterior.value != form.monto.value || form.forma_cobro_posterior.value != forma_cobro)) {
					alert('Ha seleccionado la forma de cobro ' + forma_cobro + ' e ingresó el monto en 0');
					return false;
				}

				if ((forma_cobro == 'RETAINER' || forma_cobro == 'FLAT FEE' || forma_cobro == 'CAP' || forma_cobro == 'PROPORCIONAL') && (form.monto.value <= 0 || form.monto.value == '')) {
					alert('Atención: Ha seleccionado la forma de cobro ' + forma_cobro + ' e ingresó el monto en 0');
					return false;
				}

SCRIPT;
			return $script;
		}
	)
);


$error_message =  __('Debe ingresar al menos un hito válido');
$contractValidation->registerValidation(
	'fc7', array(
		'value' => $forma_cobro,
		'server' => function($field) use ($Pagina, $error_message, $validacionesCliente) {
			if ($validacionesCliente) {
				if (empty($field)) {
					$Pagina->AddError($error_message);
				}
			}
		},
		'client' => function($field_name) {
			$script = <<<SCRIPT
				if ($('fc7').checked) {
					if ($$('[id^="fila_hito_"]').any(function(elem) {
						return !validarHito(elem, true);
					})) {
						return false;
					}
				}
SCRIPT;
			return $script;
		}
	)
);


$error_message =  __("Por favor ingrese la moneda de la tarifa en la tarificación");
$contractValidation->registerValidation(
	'opc_moneda_total', array(
		'value' => $opc_moneda_total,
		'server' => function($field) use ($Pagina, $error_message, $validacionesCliente) {
			if ($validacionesCliente) {
				if (empty($field)) {
					$Pagina->AddError($error_message);
				}
			}
		},
		'client' => null
	)
);


$error_message = __('Debe ingresar un detalle para la cobranza');
$contractValidation->registerValidation(
	'observaciones', array(
		'value' => $observaciones,
		'server' => function($field) use ($Pagina, $error_message, $validacionesCliente) {
			if ($validacionesCliente) {
				if (empty($field)) {
					$Pagina->AddError($error_message);
				}
			}
		},
		'client' => function($field_name) use ($error_message) {
			$script = <<<SCRIPT
				if (!form.$field_name.value) {
					alert("{$error_message}");
					if (typeof MuestraPorValidacion != 'undefined') {
						MuestraPorValidacion('datos_cobranza');
					}
					form.$field_name.focus();
					return false;
				}
SCRIPT;
			return $script;
		}
	)
);


$usuario_responsable_obligatorio = Conf::GetConf($Sesion, 'ObligatorioEncargadoComercial');
$usuario_secundario_obligatorio = (Conf::GetConf($Sesion, 'ObligatorioEncargadoSecundarioCliente') && Conf::GetConf($Sesion, 'EncargadoSecundario'));

if ($usuario_responsable_obligatorio) {
	$error_message = __("Debe ingresar el") . " " . __('Encargado Comercial');
	$contractValidation->registerValidation(
		'id_usuario_responsable', array(
			'value' => $id_usuario_responsable,
			'server' => function($field) use ($Pagina, $error_message, $validacionesCliente) {
				if ($validacionesCliente) {
					if (empty($field) or $field == '-1') {
						$Pagina->AddError($error_message);
					}
				}
			},
			'client' => function($field_name) use ($error_message) {
				$script = <<<SCRIPT
				if ($('id_usuario_responsable').value == '-1') {
					alert("{$error_message}");
					$('id_usuario_responsable').focus();
					return false;
				}
SCRIPT;
				return $script;
			}
		)
	);
}

if ($usuario_secundario_obligatorio) {
	$error_message = __("Debe ingresar el") . " " . __('Encargado Secundario');
	$contractValidation->registerValidation(
		'id_usuario_secundario', array(
			'value' => $id_usuario_secundario,
			'server' => function($field) use ($Pagina, $error_message, $validacionesCliente) {
				if ($validacionesCliente) {
					if (empty($field) or $field == '-1') {
						$Pagina->AddError($error_message);
					}
				}
			},
			'client' => function($field_name) use ($error_message) {
				$script = <<<SCRIPT
				if ($('id_usuario_secundario').value == '-1') {
					alert("$error_message");
					$('id_usuario_secundario').focus();
					return false;
				}
SCRIPT;
				return $script;
			}
		)
	);
}

 


