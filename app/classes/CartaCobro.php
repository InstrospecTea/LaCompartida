<?php

require_once dirname(__FILE__) . '/../conf.php'; 


class CartaCobro extends NotaCobro {
	var $carta_tabla = 'carta';
	var $carta_id = 'id_carta';
	var $carta_formato = 'formato';

	public $secciones = array(
		'CARTA' => array(
			'FECHA' => 'Sección FECHA',
			'ENVIO_DIRECCION' => 'Sección ENVIO_DIRECCION',
			'DETALLE' => 'Sección DETALLE',
			'ADJ' => 'Sección ADJ',
			'PIE' => 'Sección PIE',
			'DATOS_CLIENTE' => 'Sección DATOS_CLIENTE',
			'SALTO_PAGINA' => 'Sección SALTO_PAGINA'
		),
		'DETALLE' => array(
			'FILAS_ASUNTOS_RESUMEN' => 'FILAS_ASUNTOS_RESUMEN',
			'FILAS_FACTURAS_DEL_COBRO' => 'FILAS_FACTURAS_DEL_COBRO',
			'FILA_FACTURAS_PENDIENTES' => 'FILA_FACTURAS_PENDIENTES'
		)
	);
	public $diccionario = array(
		'CARTA' => array(
			'%cuenta_banco%' => 'Cuenta bancaria',
			'%logo_carta%' => 'Imagen logo',
			'%direccion%' => 'Dirección',
			'%titulo%' => 'Título',
			'%subtitulo%' => 'Subtítulo',
			'%numero_cobro%' => 'Número cobro',
			'%xfecha_mes_dos_digitos%' => 'Mes emision (mm)',
			'%xfecha_ano_dos_digitos%' => 'Año emision (yy)',
			'%xnro_factura%' => 'N° del cobro',
			'%glosa_cliente%' => 'Razón social Factura',
			'%xdireccion%' => 'Dirección Factura',
			'%xrut%' => 'RUT contrato'
		),
		'FECHA' => array(
			'%fecha_especial%' => 'Ciudad (país), día de mes de año',
			'%fecha_especial2%' => 'Santiago, dia de Mes de año',
			'%fecha_espanol%' => 'dia De Mes De año',
			'%fecha%' => 'mes dia, año',
			'%fecha_con_de%' => 'mes dia de año',
			'%fecha_ingles%' => 'month day, year',
			'%fecha_ingles_ordinal%' => 'month dayth, year',
			'%ANO%' => 'año fecha fin',
			'%numero_cobro%' => 'número cobro',
			'%inciales_encargado%' => 'iniciales encargado comercial',
			'%encargado_comercial%' => 'nombre completo enargado comercial'
		),
		'ENVIO_DIRECCION' => array(
			'%Asunto%' => 'Asunto',
			'%CodigoAsunto%' => 'CodigoAsunto',
			'%NombreContacto%' => 'NombreContacto',
			'%NombreContacto_mayuscula%' => 'NombreContacto_mayuscula',
			'%NumeroCliente%' => 'NumeroCliente',
			'%SR%' => 'SR',
			'%asunto_mb%' => 'asunto_mb',
			'%asunto_salto_linea%' => 'asunto_salto_linea',
			'%factura_desc_mta%' => 'factura_desc_mta',
			'%fecha_con_de%' => 'fecha_con_de',
			'%fecha_especial%' => 'fecha_especial',
			'%fecha_especial_minusculas%' => 'fecha_especial_minusculas',
			'%glosa_cliente%' => 'glosa_cliente',
			'%glosa_cliente_mayuscula%' => 'glosa_cliente_mayuscula',
			'%nombre_cliente%' => 'nombre_cliente',
			'%nombre_contacto_mb%' => 'nombre_contacto_mb',
			'%nombre_pais%' => 'nombre_pais',
			'%nombre_pais_mayuscula%' => 'nombre_pais_mayuscula',
			'%num_factura%' => 'num_factura',
			'%num_letter%' => 'num_letter',
			'%num_letter_baz%' => 'num_letter_baz',
			'%num_letter_documento%' => 'num_letter_documento',
			'%pais%' => 'pais',
			'%presente%' => 'presente',
			'%solicitante%' => 'solicitante',
			'%sr%' => 'sr',
			'%titulo_contacto%' => 'titulo_contacto',
			'%valor_direccion%' => 'valor_direccion',
			'%valor_direccion_uc%' => 'valor_direccion_uc'
		),
		'DETALLE' => array(
			'%ApellidoContacto%' => 'Apellido del contacto',
			'%Asunto%' => 'Lista de asuntos',
			'%Asunto_ucwords%' => 'Lista de asuntos con primeros letras en mayuscula',
			'%FILAS_ASUNTOS_RESUMEN%' => 'FILAS_ASUNTOS_RESUMEN',
			'%FILAS_FACTURAS_DEL_COBRO%' => 'FILAS_FACTURAS_DEL_COBRO',
			'%FILA_FACTURAS_PENDIENTES%' => 'FILA_FACTURAS_PENDIENTESS',
			'%NombrePilaContacto%' => 'Nombre del contacto',
			'%SoloNombreContacto%' => 'SoloNombreContacto',
			'%boleta_gastos%' => 'boleta_gastos',
			'%boleta_honorarios%' => 'boleta_honorarios',
			'%categoria_encargado_comercial%' => 'categoria_encargado_comercial',
			'%categoria_encargado_comercial_mayusculas%' => 'categoria_encargado_comercial_mayusculas',
			'%codigo_cci%' => 'codigo_cci',
			'%codigo_cci2%' => 'codigo_cci2',
			'%codigo_swift%' => 'codigo_swift',
			'%codigo_swift2%' => 'codigo_swift2',
			'%codigopropuesta%' => 'codigopropuesta',
			'%concepto_gastos_cuando_hay%' => 'concepto_gastos_cuando_hay',
			'%concepto_honorarios_cuando_hay%' => 'concepto_honorarios_cuando_hay',
			'%cta_cte_gbp_segun_moneda%' => 'Numero de cuenta que va a cambiar segun moneda (si es dolar una cuenta, en caso contrario otra, para gbplegal',
			'%cuenta_banco%' => 'cuenta_banco',
			'%cuenta_mb%' => 'cuenta_mb',
			'%cuenta_mb_boleta%' => 'cuenta_mb_boleta',
			'%cuenta_mb_ny%' => 'direccion de cuenta de MB en Nueva York',
			'%despedida_mb%' => 'Frase de despedida_mb',
			'%detalle_careyallende%' => 'Letra de detalle completo del estudio Carey Allende',
			'%detalle_cuenta_gastos%' => 'detalle_cuenta_gastos',
			'%detalle_cuenta_gastos2%' => 'detalle_cuenta_gastos2',
			'%detalle_cuenta_honorarios%' => 'detalle_cuenta_honorarios',
			'%detalle_cuenta_honorarios_primer_dia_mes%' => 'detalle_cuenta_honorarios_primer_dia_mes',
			'%detalle_ebmo%' => 'Letra de detalle completo del estudio ebmo',
			'%detalle_mb%' => 'Frase especial Morales y Bezas',
			'%detalle_mb_boleta%' => 'Frase descripcion detalle MB',
			'%detalle_mb_ny%' => 'Frase especial MB New York',
			'%duracion_trabajos%' => 'total duracion cobrable de las horas inluido en el cobro',
			'%encargado_comercial%' => 'encargado_comercial',
			'%encargado_comercial_uc%' => 'encargado_comercial_uc',
			'%equivalente_a_baz%' => 'extensión frase de carte en el caso de que se hace una transfería',
			'%equivalente_dolm%' => 'que ascienden a %monto%',
			'%estimado%' => 'Estimada/Estimado',
			'%fecha%' => 'Frase que indica el periodo de la fecha',
			'%fecha_al%' => 'En frase del periodo reemplazar la palabra "hasta" con la palabra "al"',
			'%fecha_al_minuscula%' => 'fecha_al_minuscula',
			'%fecha_con_de%' => 'En frase del periodo reemplazar la palabra "hasta" con la palabra "de"',
			'%fecha_con_prestada%' => 'fecha_con_prestada',
			'%fecha_con_prestada_mayuscula%' => 'fecha_con_prestada_mayuscula',
			'%fecha_con_prestada_minusculas%' => 'fecha_con_prestada_minusculas',
			'%fecha_dia_carta%' => 'Día actual al momento de imprimir la carta',
			'%fecha_diff_prestada_durante%' => 'fecha_diff_prestada_durante',
			'%fecha_diff_prestada_durante_mayuscula%' => 'fecha_diff_prestada_durante_mayuscula',
			'%fecha_diff_prestada_durante_minusculas%' => 'fecha_diff_prestada_durante_minusculas',
			'%fecha_emision%' => 'Fecha de emisión del cobro',
			'%fecha_especial%' => 'fecha_especial',
			'%fecha_especial_mta%' => 'fecha_especial_mta',
			'%fecha_especial_mta_en%' => 'fecha_especial_mta_en',
			'%fecha_facturacion%' => 'fecha_facturacion',
			'%fecha_hasta%' => 'fecha corte del cobro en Formato DIA de MES ( sin año )',
			'%fecha_mes%' => 'fecha_mes',
			'%fecha_mta%' => 'fecha_mta',
			'%fecha_mta_agno%' => 'fecha_mta_agno',
			'%fecha_mta_dia%' => 'fecha_mta_dia',
			'%fecha_mta_mes%' => 'fecha_mta_mes',
			'%fecha_periodo_exacto%' => 'Periodo del cobro con fechas exactas',
			'%fecha_primer_trabajo%' => 'Fecha del primer trabajo del cobro',
			'%fecha_primer_trabajo_de%' => 'fecha_primer_trabajo_de',
			'%fecha_primer_trabajo_durante%' => 'fecha_primer_trabajo_durante',
			'%frase_gastos_egreso%' => 'Frase especial para baz',
			'%frase_gastos_ingreso%' => 'Frase especial para baz',
			'%frase_moneda%' => 'frase_moneda',
			'%glosa_banco_contrato%' => 'glosa_banco_contrato',
			'%glosa_banco_contrato2%' => 'glosa_banco_contrato2',
			'%glosa_cliente%' => 'campo "factura_razon_social" de la tabla contrato',
			'%glosa_cliente_mayuscula%' => 'glosa_cliente_mayuscula',
			'%glosa_contrato%' => 'glosa_contrato',
			'%glosa_cuenta_contrato%' => 'glosa_cuenta_contrato',
			'%glosa_cuenta_contrato2%' => 'glosa_cuenta_contrato2',
			'%lista_asuntos%' => 'lista_asuntos',
			'%lista_asuntos_guion%' => 'lista_asuntos_guion',
			'%logo_carta%' => 'logo_carta',
			'%monto%' => 'Monto total del cobro',
			'%monto_con_gasto%' => 'Monto total',
			'%monto_en_palabras%' => 'monto_en_palabras',
			'%monto_en_palabras_en%' => 'monto_en_palabras_en',
			'%monto_en_pesos%' => 'monto total del cobro en moneda base',
			'%monto_gasto%' => 'total de los gastos',
			'%monto_gasto_separado%' => 'Frase que indica valor de gastos',
			'%monto_gasto_separado_baz%' => 'monto_gasto_separado_baz',
			'%monto_gastos_con_iva%' => 'monto_gastos_con_iva',
			'%monto_gastos_cuando_hay%' => 'monto_gastos_cuando_hay',
			'%monto_gastos_sin_iva%' => 'monto_gastos_sin_iva',
			'%monto_honorarios_cuando_hay%' => 'monto_honorarios_cuando_hay',
			'%monto_iva%' => 'monto_iva',
			'%monto_original%' => 'Monto honorarios en la moneda del tarifa',
			'%monto_sin_gasto%' => 'Monto sin gastos',
			'%monto_solo_gastos%' => 'Monto solo gastos',
			'%monto_total_demo%' => 'Monto total',
			'%monto_total_demo_jdf%' => 'monto_total_demo_jdf',
			'%monto_total_demo_uf%' => 'monto_total_demo_uf',
			'%monto_total_sin_iva%' => 'Monto subtotal',
			'%n_num_factura%' => 'n_num_factura',
			'%num_factura%' => 'campo "documento" de la tabla "cobro"',
			'%num_letter%' => 'num_letter',
			'%num_letter_baz%' => 'num_letter_baz',
			'%num_letter_documento%' => 'num_letter_documento',
			'%num_letter_rebaza%' => 'num_letter_rebaza',
			'%num_letter_rebaza_especial%' => 'num_letter_rebaza_especial',
			'%numero_cuenta_contrato%' => 'numero_cuenta_contrato',
			'%numero_cuenta_contrato2%' => 'numero_cuenta_contrato2',
			'%porcentaje_impuesto%' => 'Numero de Porcentaje (incluye simbolo %)',
			'%porcentaje_impuesto_sin_simbolo%' => 'porcentaje_impuesto_sin_simbolo',
			'%porcentaje_iva_con_simbolo%' => 'porcentaje_iva_con_simbolo',
			'%rut_cliente%' => 'rut_cliente',
			'%saldo_gastos_balance%' => 'saldo_gastos_balance',
			'%saludo_mb%' => 'Dear %sr% %ApellidoContacto%: / De mi consideración:',
			'%si_gastos%' => 'si_gastos',
			'%simbolo_opc_moneda_totall%' => 'simbolo_opc_moneda_totall',
			'%sr%' => 'Titulo del contacto definido en el contrato, por defecto "Señor"',
			'%subtotal_gastos_diff_con_sin_provision%' => 'balance cuenta de gastos',
			'%subtotal_gastos_sin_provision%' => 'monto gastos sin las provisiones',
			'%subtotal_gastos_solo_provision%' => 'monto gastos solo contando las provisiones',
			'%tipo_cuenta%' => 'tipo_cuenta',
			'%tipo_gbp_segun_moneda%' => 'Tipo de moneda (Nacional/Extranjera) que va a cambiar segun moneda (si es dolar Extranjera, en caso contrario Nacional para gbplegal',
		),
		'ADJ' => array(
			'%cliente_fax%' => 'cliente_fax',
			'%firma_careyallende%' => 'firma_careyallende',
			'%iniciales_encargado_comercial%' => 'iniciales_encargado_comercial',
			'%nombre_encargado_comercial%' => 'nombre_encargado_comercial',
			'%nro_factura%' => 'nro_factura',
			'%num_letter%' => 'num_letter',
			'%num_letter_baz%' => 'num_letter_baz',
			'%num_letter_documento%' => 'num_letter_documento',
		),
		'PIE' => array(
			'%direccion%' => 'direccion',
			'%logo_carta%' => 'logo_carta',
			'%num_letter%' => 'num_letter',
			'%num_letter_documento%' => 'num_letter_documento',
		),
		'DATOS_CLIENTE' => array(
			'%ApellidoContacto%' => 'ApellidoContacto',
			'%NombrePilaContacto%' => 'NombrePilaContacto',
			'%SR%' => 'SR',
			'%encargado_comercial_mayusculas%' => 'encargado_comercial_mayusculas',
			'%estimado%' => 'estimado',
			'%glosa_cliente%' => 'glosa_cliente',
			'%sr%' => 'sr',
		),
		'FILAS_FACTURAS_DEL_COBRO' => array(
			'%factura_impuesto%' => 'factura_impuesto',
			'%factura_moneda%' => 'factura_moneda',
			'%factura_numero%' => 'factura_numero',
			'%factura_periodo%' => 'factura_periodo',
			'%factura_total%' => 'factura_total',
			'%factura_total_sin_impuesto%' => 'factura_total_sin_impuesto',
		),

		'FILAS_FACTURAS_DEL_COBRO' => array(
			'%factura_pendiente%' => 'factura_pendiente',
		    ),
		'FILAS_ASUNTOS_RESUMEN' => array(
			'%fecha_mta%' => 'fecha_mta',
			'%gastos_asunto%' => 'gastos_asunto',
			'%gastos_asunto_mi%' => 'gastos_asunto_mi',
			'%glosa_asunto%' => 'glosa_asunto',
			'%honorarios_asunto%' => 'honorarios_asunto',
			'%honorarios_asunto_mi%' => 'honorarios_asunto_mi',
			'%num_factura%' => 'num_factura',
			'%num_letter%' => 'num_letter',
			'%simbolo%' => 'simbolo',
			'%simbolo_mi%' => 'simbolo_mi',
			'%total_asunto%' => 'total_asunto',
			'%total_asunto_mi%' => 'total_asunto_mi',
		),
		'FILA_FACTURAS_PENDIENTES' => array(
			'%facturas_pendientes%' => 'facturas_pendientes'
		),
		'SALTO_PAGINA' => array()
	);
	function __construct($sesion, $fields,$ArrayFacturasDelContrato,$ArrayTotalesDelContrato) {
		$this->sesion=$sesion; 
		$this->fields=$fields;
		$this->ArrayFacturasDelContrato=$ArrayFacturasDelContrato;
		$this->ArrayTotalesDelContrato=$ArrayTotalesDelContrato;
		$this->espacio=UtilesApp::GetConf($this->sesion, 'ValorSinEspacio')?'':'&nbsp;';
	}
	function NuevoRegistro(){
		return array(
			'descripcion' => 'Nueva Carta',
			'margen_superior' => 1.5,
			'margen_inferior' => 2,
			'margen_izquierdo' => 2,
			'margen_derecho' => 2,
			'margen_encabezado' => 0.88,
			'margen_pie_de_pagina' => 0.88
		);
	}

	function GenerarEjemplo($parser){
		extract($this->ParametrosGeneracion());
		return $this->GenerarDocumentoCarta2($parser, 'CARTA', $lang, $moneda_cliente_cambio, $moneda_cli, $idioma, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $cliente, $id_carta);
	}

	function GenerarDocumentoCarta($parser_carta, $theTag = '', $lang, $moneda_cliente_cambio, $moneda_cli, & $idioma, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $cliente, $id_carta) {
		global $id_carta;
		global $contrato;
		global $cobro_moneda;
		global $moneda_total;
		global $x_cobro_gastos;

		if (!isset($parser_carta->tags[$theTag]))
			return;

		$html2 = $parser_carta->tags[$theTag];

		switch ($theTag) {
			case 'CARTA': //GenerarDocumentoCarta
				
				//$html2 = str_replace('%CARTA_GASTOS%', $this->GenerarDocumentoCartaComun($parser_carta, 'CARTA_GASTOS', $lang, $moneda_cliente_cambio, $moneda_cli, $idioma, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $cliente, $id_carta), $html2);
				//$html2 = str_replace('%CARTA_HONORARIOS%', $this->GenerarDocumentoCartaComun($parser_carta, 'CARTA_HONORARIOS', $lang, $moneda_cliente_cambio, $moneda_cli, $idioma, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $cliente, $id_carta), $html2);

				if (method_exists('Conf', 'GetConf')) {
					$PdfLinea1 = Conf::GetConf($this->sesion, 'PdfLinea1');
					$PdfLinea2 = Conf::GetConf($this->sesion, 'PdfLinea2');
					$PdfLinea3 = Conf::GetConf($this->sesion, 'PdfLinea3');
				} else {
					$PdfLinea1 = Conf::PdfLinea1();
					$PdfLinea2 = Conf::PdfLinea2();
					$PdfLinea3 = Conf::PdfLinea3();
				}

				if (strpos($html2, '%cuenta_banco%')) {
					if ($contrato->fields['id_cuenta']) {
						$query_banco = "SELECT glosa FROM cuenta_banco WHERE id_cuenta = '" . $contrato->fields['id_cuenta'] . "'";
						$resp = mysql_query($query_banco, $this->sesion->dbh) or Utiles::errorSQL($query_banco, __FILE__, __LINE__, $this->sesion->dbh);
						list($glosa_cuenta) = mysql_fetch_array($resp);
					}
					else
						$glosa_cuenta = '';
					$html2 = str_replace('%cuenta_banco%', $glosa_cuenta, $html2);
				}

				$html2 = str_replace('%logo_carta%', Conf::Server() . Conf::ImgDir(), $html2);
				$html2 = str_replace('%direccion%', $PdfLinea1, $html2);
				$html2 = str_replace('%titulo%', $PdfLinea1, $html2);
				$html2 = str_replace('%subtitulo%', $PdfLinea2, $html2);
				$html2 = str_replace('%numero_cobro%', $this->fields['id_cobro'], $html2);

				$html2 = str_replace('%FECHA%', $this->GenerarDocumentoCartaComun($parser_carta, 'FECHA', $lang, $moneda_cliente_cambio, $moneda_cli, $idioma, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $cliente, $id_carta), $html2);
				$html2 = str_replace('%ENVIO_DIRECCION%', $this->GenerarDocumentoCartaComun($parser_carta, 'ENVIO_DIRECCION', $lang, $moneda_cliente_cambio, $moneda_cli, $idioma, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $cliente, $id_carta), $html2);
				$html2 = str_replace('%DETALLE%', $this->GenerarDocumentoCarta($parser_carta, 'DETALLE', $lang, $moneda_cliente_cambio, $moneda_cli, $idioma, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $cliente, $id_carta), $html2);
				$html2 = str_replace('%ADJ%', $this->GenerarDocumentoCartaComun($parser_carta, 'ADJ', $lang, $moneda_cliente_cambio, $moneda_cli, $idioma, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $cliente, $id_carta), $html2);
				$html2 = str_replace('%PIE%', $this->GenerarDocumentoCartaComun($parser_carta, 'PIE', $lang, $moneda_cliente_cambio, $moneda_cli, $idioma, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $cliente, $id_carta), $html2);
				$html2 = str_replace('%DATOS_CLIENTE%', $this->GenerarDocumentoCartaComun($parser_carta, 'DATOS_CLIENTE', $lang, $moneda_cliente_cambio, $moneda_cli, $idioma, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $cliente, $id_carta), $html2);
				$html2 = str_replace('%SALTO_PAGINA%', $this->GenerarDocumentoCartaComun($parser_carta, 'SALTO_PAGINA', $lang, $moneda_cliente_cambio, $moneda_cli, $idioma, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $cliente, $id_carta), $html2);

				break;




			case 'DETALLE': //GenerarDocumentoCarta
				/* DICTIONARIO
				 * %saludo_mb%               --- Dear %sr% %ApellidoContacto%: / De mi consideración:
				 * %detalle_mb%              --- Frase especial Morales y Bezas
				 * %detalle_mb_ny%           --- Frase especial MB New York
				 * %detalle_mb_boleta%       --- Frase descripcion detalle MB
				 * %cuenta_mb%               --- ""
				 * %despedida_mb%            --- Frase de despedida_mb
				 * %cuenta_mb_ny%            --- direccion de cuenta de MB en Nueva York
				 * %cuenta_mb_boleta%        --- ""
				 * %detalle_careyallende%    --- Letra de detalle completo del estudio Carey Allende
				 * %detalle_ebmo%            --- Letra de detalle completo del estudio ebmo
				 * %sr%                      --- Titulo del contacto definido en el contrato, por defecto "Señor"
				 * %NombrePilaContacto%      --- Nombre del contacto
				 * %ApellidoContacto%        --- Apellido del contacto
				 * %glosa_cliente%           --- campo "factura_razon_social" de la tabla contrato
				 * %estimado%                --- __('Estimada') / __('Estimado')
				 * %subtotal_gastos_solo_provision%
				  --- monto gastos solo contando las provisiones
				 * %subtotal_gastos_sin_provision%
				  --- monto gastos sin las provisiones
				 * %subtotal_gastos_diff_con_sin_provision%
				  --- balance cuenta de gastos
				 * %duracion_trabajos%       --- total duracion cobrable de las horas inluido en el cobro
				 * %monto_gasto%             --- total de los gastos
				 * %Asunto%                  --- Lista de asuntos
				 * %Asunto_ucwords%          --- Lista de asuntos con primeros letras en mayuscula
				 * %equivalente_dolm%        --- que ascienden a %monto%
				 * %num_factura%             --- campo "documento" de la tabla "cobro"
				 * %fecha_primer_trabajo%    --- Fecha del primer trabajo del cobro
				 * %fecha%                   --- Frase que indica el periodo de la fecha
				 * %fecha_al%                --- En frase del periodo reemplazar la palabra "hasta" con la palabra "al"
				 * %fecha_con_de%            --- En frase del periodo reemplazar la palabra "hasta" con la palabra "de"
				 * %fecha_emision%           --- Fecha de emisión del cobro
				 * %fecha_periodo_exacto%    --- Periodo del cobro con fechas exactas
				 * %fecha_dia_carta%         --- Día actual al momento de imprimir la carta
				 * %monto%                   --- Monto total del cobro
				 * %monto_solo_gastos%       --- Monto solo gastos
				 * %monto_sin_gasto%         --- Monto sin gastos
				 * %monto_total_demo%'       --- Monto total
				 * %monto_con_gasto%'        --- Monto total
				 * %monto_original%'         --- Monto honorarios en la moneda del tarifa
				 * %monto_total_sin_iva%     --- Monto subtotal
				 * %porcentaje_impuesto%     --- Numero de Porcentaje (incluye simbolo %)
				 * %equivalente_a_baz%       --- extensión frase de carte en el caso de que se hace una transfería
				 * %simbolo_moneda%          --- simbolo de id_moneda del cobro
				 * %simbolo_moneda_total%    --- simbolo de opc_moneda_total del cobro
				 * %fecha_hasta%             --- fecha corte del cobro en Formato DIA de MES ( sin año )
				 * %monto_en_pesos%          --- monto total del cobro en moneda base
				 * %monto_gasto_separado%    --- Frase que indica valor de gastos
				 * %frase_gastos_ingreso%    --- Frase especial para baz
				 * %frase_gastos_egreso%     --- Frase especial para baz
				 *
				 * %cta_cte_gbp_segun_moneda% --- Numero de cuenta que va a cambiar segun moneda (si es dolar una cuenta, en caso contrario otra, para gbplegal
				 * %tipo_gbp_segun_moneda% --- Tipo de moneda (Nacional/Extranjera) que va a cambiar segun moneda (si es dolar Extranjera, en caso contrario Nacional para gbplegal
				 */
				/* Primero se hacen las cartas particulares ya que lee los datos que siguen */
				#carta mb
				$html2 = str_replace('%saludo_mb%', __('%saludo_mb%'), $html2);
				$html2 = str_replace('%logo_carta%', Conf::Server() . Conf::ImgDir(), $html2);

				if (count($this->asuntos) > 1) {
					$html2 = str_replace('%detalle_mb%', __('%detalle_mb_asuntos%'), $html2);
					$html2 = str_replace('%detalle_mb_ny%', __('%detalle_mb_ny_asuntos%'), $html2);
					$html2 = str_replace('%detalle_mb_boleta%', __('%detalle_mb_boleta_asuntos%'), $html2);
				} else {
					$html2 = str_replace('%detalle_mb%', __('%detalle_mb%'), $html2);
					$html2 = str_replace('%detalle_mb_ny%', __('%detalle_mb_ny%'), $html2);
					$html2 = str_replace('%detalle_mb_boleta%', __('%detalle_mb_boleta%'), $html2);
				}

				$this->LoadGlosaAsuntos();
				$lista_asuntos = "<ul>";
				foreach ($this->glosa_asuntos as $asunto) {
					$lista_asuntos .= "<li>" . $asunto . "</li>";
				}
				$lista_asuntos .= "</ul>";
				$html2 = str_replace('%lista_asuntos%', $lista_asuntos, $html2);

				$html2 = str_replace('%FILAS_ASUNTOS_RESUMEN%', $this->GenerarDocumentoCartaComun($parser_carta, 'FILAS_ASUNTOS_RESUMEN', $lang, $moneda_cliente_cambio, $moneda_cli, $idioma, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $cliente, $id_carta), $html2);

				$html2 = str_replace('%cuenta_mb%', __('%cuenta_mb%'), $html2);
				$html2 = str_replace('%despedida_mb%', __('%despedida_mb%'), $html2);
				$html2 = str_replace('%cuenta_mb_ny%', __('%cuenta_mb_ny%'), $html2);
				$html2 = str_replace('%cuenta_mb_boleta%', __('%cuenta_mb_boleta%'), $html2);
				#carta careyallende
				$html2 = str_replace('%detalle_careyallende%', __('%detalle_careyallende%'), $html2);
				#carta ebmo
				if ($this->fields['monto_gastos'] > 0 && $this->fields['monto_subtotal'] == 0)
					$html2 = str_replace('%detalle_ebmo%', __('%detalle_ebmo_solo_gastos%'), $html2);
				else if ($this->fields['monto_gastos'] == 0 && $this->fields['monto_subtotal'] > 0)
					$html2 = str_replace('%detalle_ebmo%', __('%detalle_ebmo_solo_honorarios%'), $html2);
				else
					$html2 = str_replace('%detalle_ebmo%', __('%detalle_ebmo%'), $html2);

				#carta gallo barrios pickman (gbplegal)
				if ($cobro_moneda->moneda[$this->fields['opc_moneda_total']]['codigo'] == 'USD') {
					$html2 = str_replace('%cta_cte_gbp_segun_moneda%', __('194-1861108179'), $html2);
					$html2 = str_replace('%tipo_gbp_segun_moneda%', __('Extranjera'), $html2);
				} else {
					$html2 = str_replace('%cta_cte_gbp_segun_moneda%', __('194-1847085-0-23'), $html2);
					$html2 = str_replace('%tipo_gbp_segun_moneda%', __('Nacional'), $html2);
				}

				/* valor porcentaje de impuesto */
				$html2 = str_replace('%porcentaje_impuesto%', (int) ($this->fields['porcentaje_impuesto']) . '%', $html2);
				$html2 = str_replace('%porcentaje_impuesto_sin_simbolo%', (int) ($this->fields['porcentaje_impuesto']), $html2);

				/* Datos detalle */
				if (method_exists('Conf', 'GetConf')) {
					if (Conf::GetConf($this->sesion, 'TituloContacto')) {
						$html2 = str_replace('%sr%', __($contrato->fields['titulo_contacto']), $html2);
						$html2 = str_replace('%NombrePilaContacto%', $contrato->fields['contacto'], $html2);
						$html2 = str_replace('%ApellidoContacto%', $contrato->fields['apellido_contacto'], $html2);
					} else {
						$html2 = str_replace('%sr%', __('Señor'), $html2);
						$NombreContacto = explode(' ', $contrato->fields['contacto']);
						$html2 = str_replace('%NombrePilaContacto%', $NombreContacto[0], $html2);
						$html2 = str_replace('%ApellidoContacto%', $NombreContacto[1], $html2);
					}
				} else if (method_exists('Conf', 'TituloContacto')) {
					if (Conf::TituloContacto()) {
						$html2 = str_replace('%sr%', __($contrato->fields['titulo_contacto']), $html2);
						$html2 = str_replace('%NombrePilaContacto%', $contrato->fields['contacto'], $html2);
						$html2 = str_replace('%ApellidoContacto%', $contrato->fields['apellido_contacto'], $html2);
					} else {
						$html2 = str_replace('%sr%', __('Señor'), $html2);
						$NombreContacto = explode(' ', $contrato->fields['contacto']);
						$html2 = str_replace('%NombrePilaContacto%', $NombreContacto[0], $html2);
						$html2 = str_replace('%ApellidoContacto%', $NombreContacto[1], $html2);
					}
				} else {
					$html2 = str_replace('%sr%', __('Señor'), $html2);
					$NombreContacto = explode(' ', $contrato->fields['contacto']);
					$html2 = str_replace('%NombrePilaContacto%', $NombreContacto[0], $html2);
					$html2 = str_replace('%ApellidoContacto%', $NombreContacto[1], $html2);
				}
				$html2 = str_replace('%glosa_cliente%', $contrato->fields['factura_razon_social'], $html2);
				if (strtolower($contrato->fields['titulo_contacto']) == 'sra.' || strtolower($contrato->fields['titulo_contacto']) == 'srta.')
					$html2 = str_replace('%estimado%', __('Estimada'), $html2);
				else
					$html2 = str_replace('%estimado%', __('Estimado'), $html2);

				/*
				  Total Gastos
				  se suma cuando idioma es inglés
				  se presenta separadamente cuando es en español
				 */
				$total_gastos = 0;
				$total_gastos_balance = 0;
				$saldo_egreso_moneda_total = 0;
				$saldo_ingreso_moneda_total = 0;
				$query = "SELECT SQL_CALC_FOUND_ROWS *
									FROM cta_corriente
									WHERE id_cobro='" . $this->fields['id_cobro'] . "' AND (egreso > 0 OR ingreso > 0)
									ORDER BY fecha ASC";
				$lista_gastos = new ListaGastos($this->sesion, '', $query);

				$sum_egreso = 0;
				$sum_ingreso = 0;
				for ($i = 0; $i < $lista_gastos->num; $i++) {
					$gasto = $lista_gastos->Get($i);

					//Cargar cobro_moneda

					if ($gasto->fields['egreso'] > 0) {
						$saldo = $gasto->fields['monto_cobrable'];
						if ($gasto->fields['id_movimiento'] != $this->fields['id_gasto_generado']) {
							$egreso_moneda_total = $gasto->fields['monto_cobrable'] * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']);
						} else {
							$egreso_moneda_total = 0;
						}
						$ingreso_moneda_total = 0;
						if ($gasto->fields['cobrable_actual'] == 1) {
							$sum_egreso += $gasto->fields['monto_cobrable'];
						}
					} elseif ($gasto->fields['ingreso'] > 0) {
						$saldo = -$gasto->fields['monto_cobrable'];
						$ingreso_moneda_total = $gasto->fields['monto_cobrable'] * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']);
						$egreso_moneda_total = 0;
						if ($gasto->fields['cobrable_actual'] == 1) {
							$sum_ingreso += $gasto->fields['monto_cobrable'];
						}
					}
					if (substr($gasto->fields['descripcion'], 0, 19) == "Saldo aprovisionado") {
						$saldo_balance = $saldo;
					} else {
						$saldo_balance = 0;
					}

					$saldo_balance_moneda_total = $saldo_balance * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']);
					$saldo_egreso_moneda_total += $egreso_moneda_total;
					$saldo_ingreso_moneda_total += $ingreso_moneda_total;
					$total_gastos_balance += $saldo_balance_moneda_total;
					$total_gastos = $this->fields['monto_gastos'];
				}
				$total_gastos_subtotal = $this->fields['subtotal_gastos'];
				$saldo_balance_gastos_moneda_total = max(0, $saldo_ingreso_moneda_total - $saldo_egreso_moneda_total);
				/*
				 * INICIO - CARTA GASTOS DE VFCabogados, 2011-03-04
				 */
				 
					$html2 = str_replace('%saldo_egreso_moneda_total%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($saldo_egreso_moneda_total, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . '.-', $html2); // suma ingresos cobrables
					$html2 = str_replace('%saldo_ingreso_moneda_total%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($saldo_ingreso_moneda_total, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . '.-', $html2); // suma ingresos cobrables
					$html2 = str_replace('%saldo_gastos_balance%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($saldo_balance_gastos_moneda_total, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . '.-', $html2);

					$html2 = str_replace('%subtotal_gastos_solo_provision%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($saldo_balance_gastos_moneda_total, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . '.-', $html2); // en la carta se especifica que el monto debe aparecer como positivo
					$html2 = str_replace('%subtotal_gastos_sin_provision%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($x_cobro_gastos['subtotal_gastos_sin_provision'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . '.-', $html2); // en la carta se especifica que el monto debe aparecer como positivo
					$html2 = str_replace('%subtotal_gastos_diff_con_sin_provision%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($x_cobro_gastos['gasto_total'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . '.-', $html2); // en la carta se especifica que el monto debe aparecer como positivo
				/*
				 * FIN - CARTA GASTOS DE VFCabogados, 2011-03-04
				 */

				/* MONTOS SEGUN MONEDA TOTAL IMPRESION */
				$aproximacion_monto = number_format($this->fields['monto'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
				$aproximacion_monto_subtotal = number_format($this->fields['monto_subtotal'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
				$aproximacion_monto_demo = $aproximacion_monto;
				$monto_moneda_demo = number_format($aproximacion_monto_demo * $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], '.', '');
				$monto_moneda = ((double) $aproximacion_monto * (double) $this->fields['tipo_cambio_moneda']) / ($tipo_cambio_moneda_total > 0 ? $tipo_cambio_moneda_total : $moneda_total->fields['tipo_cambio']);
				$monto_moneda_subtotal = number_format($aproximacion_monto_subtotal * $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], '.', '');
				$monto_moneda_sin_gasto = ((double) $aproximacion_monto * (double) $this->fields['tipo_cambio_moneda']) / ($tipo_cambio_moneda_total > 0 ? $tipo_cambio_moneda_total : $moneda_total->fields['tipo_cambio']);
				$monto_moneda_con_gasto = ((double) $aproximacion_monto * (double) $this->fields['tipo_cambio_moneda']) / ($tipo_cambio_moneda_total > 0 ? $tipo_cambio_moneda_total : $moneda_total->fields['tipo_cambio']);

				//Caso retainer menor de un valor y distinta tarifa (diferencia por decimales)
				if ((($this->fields['total_minutos'] / 60) < $this->fields['retainer_horas']) && ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') && $this->fields['id_moneda'] != $this->fields['id_moneda_monto']) {
					$monto_moneda_con_gasto = ((double) $this->fields['monto'] * (double) $this->fields['tipo_cambio_moneda']) / ($tipo_cambio_moneda_total > 0 ? $tipo_cambio_moneda_total : $moneda_total->fields['tipo_cambio']);
				}
				$query = "SELECT SUM( TIME_TO_SEC( duracion_cobrada )/3600 )
										FROM trabajo
									 WHERE id_cobro = '" . $this->fields['id_cobro'] . "' ";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				list($duracion_trabajos) = mysql_fetch_array($resp);
				$html2 = str_replace('%duracion_trabajos%', number_format($duracion_trabajos, 2, ',', ''), $html2);
				//Caso flat fee
				if ($this->fields['forma_cobro'] == 'FLAT FEE' && $this->fields['id_moneda'] != $this->fields['id_moneda_monto'] && $this->fields['id_moneda_monto'] == $this->fields['opc_moneda_total'] && empty($this->fields['descuento'])) {
					$monto_moneda = $this->fields['monto_contrato'];
					$monto_moneda_con_gasto = $this->fields['monto_contrato'];
					$monto_moneda_sin_gasto = $this->fields['monto_contrato'];
					$monto_moneda_subtotal = $this->fields['monto_contrato'];
				}

				//Caso cap menor de un valor y distinta tarifa (diferencia por decimales)
				/* if($this->fields['forma_cobro']=='CAP' && $this->fields['monto_subtotal'] > $this->fields['monto'] && $this->fields['id_moneda']!=$this->fields['id_moneda_monto'] && $this->fields['opc_moneda_total']==$this->fields['id_moneda_monto'])
				  {
				  $monto_moneda_con_gasto = $this->fields['monto_contrato'];
				  } */

				/* MONTOS SEGUN MONEDA CLIENTE *//*
				  $monto_moneda = ((double)$this->fields['monto']*(double)$this->fields['tipo_cambio_moneda'])/($moneda_cliente_cambio > 0 ? $moneda_cliente_cambio : $moneda_cli->fields['tipo_cambio']);
				  $monto_moneda_sin_gasto = ((double)$this->fields['monto']*(double)$this->fields['tipo_cambio_moneda'])/($moneda_cliente_cambio > 0 ? $moneda_cliente_cambio : $moneda_cli->fields['tipo_cambio']);
				  $monto_moneda_con_gasto = ((double)$this->fields['monto']*(double)$this->fields['tipo_cambio_moneda'])/($moneda_cliente_cambio > 0 ? $moneda_cliente_cambio : $moneda_cli->fields['tipo_cambio']);
				 */
				$monto_moneda_demo += number_format($total_gastos, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], '.', '');
				$monto_moneda_subtotal += number_format($total_gastos_subtotal, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], '.', '');
				$monto_moneda_con_gasto += $total_gastos;
				if ($lang != 'es')
					$monto_moneda += $total_gastos;
				if ($total_gastos > 0) {
						$html2 = str_replace('%monto_gasto%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($total_gastos, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
				}

				#Fechas periodo
				$datefrom = strtotime($this->fields['fecha_ini'], 0);
				$dateto = strtotime($this->fields['fecha_fin'], 0);
				$difference = $dateto - $datefrom; //Dif segundos
				$months_difference = floor($difference / 2678400);
				while (mktime(date("H", $datefrom), date("i", $datefrom), date("s", $datefrom), date("n", $datefrom) + ($months_difference), date("j", $dateto), date("Y", $datefrom)) < $dateto) {
					$months_difference++;
				}

				$datediff = $months_difference;

				/*
				  Mostrando fecha según idioma
				 */
				if ($this->fields['fecha_ini'] != '' && $this->fields['fecha_ini'] != '0000-00-00')
					$texto_fecha_es = __('entre los meses de') . ' ' . ucfirst(Utiles::sql3fecha($this->fields['fecha_ini'], '%B %Y')) . ' ' . __('y') . ' ' . ucfirst(Utiles::sql3fecha($this->fields['fecha_fin'], '%B %Y'));
				else
					$texto_fecha_es = __('hasta el mes de') . ' ' . ucfirst(ucfirst(Utiles::sql3fecha($this->fields['fecha_fin'], '%B %Y')));

				if ($this->fields['fecha_ini'] != '' && $this->fields['fecha_ini'] != '0000-00-00')
					$texto_fecha_en = __('between') . ' ' . ucfirst(date('F Y', strtotime($this->fields['fecha_ini']))) . ' ' . __('and') . ' ' . ucfirst(date('F Y', strtotime($this->fields['fecha_fin'])));
				else
					$texto_fecha_en = __('until') . ' ' . ucfirst(date('F Y', strtotime($this->fields['fecha_fin'])));

				if ($lang == 'es') {
					$fecha_diff = $datediff > 0 && $datediff < 12 ? $texto_fecha_es : __('durante el mes de') . ' ' . ucfirst(Utiles::sql3fecha($this->fields['fecha_fin'], '%B %Y'));
					$fecha_al = $datediff > 0 && $datediff < 12 ? $texto_fecha_es : __('al mes de') . ' ' . ucfirst(Utiles::sql3fecha($this->fields['fecha_fin'], '%B %Y'));
					$fecha_diff_con_de = $datediff > 0 && $datediff < 12 ? $texto_fecha_es : __('durante el mes de') . ' ' . ucfirst(Utiles::sql3fecha($this->fields['fecha_fin'], '%B de %Y'));
					$fecha_diff_prestada = $datediff > 0 && $datediff < 12 ? __('prestada ') . $texto_fecha_es : __('prestada en el mes de') . ' ' . ucfirst(Utiles::sql3fecha($this->fields['fecha_fin'], '%B %Y'));
				} else {
					$fecha_diff = $datediff > 0 && $datediff < 12 ? $texto_fecha_en : __('during') . ' ' . ucfirst(date('F Y', strtotime($this->fields['fecha_fin'])));
					$fecha_al = $datediff > 0 && $datediff < 12 ? $texto_fecha_en : __('to') . ' ' . ucfirst(date('F Y', strtotime($this->fields['fecha_fin'])));
					$fecha_diff_prestada = $datediff > 0 && $datediff < 12 ? $texto_fecha_en : __('during') . ' ' . ucfirst(date('F Y', strtotime($this->fields['fecha_fin'])));
				}

				if (( $fecha_diff == 'durante el mes de No existe fecha' || $fecha_diff == 'hasta el mes de No existe fecha' ) && $lang == 'es') {
					$fecha_diff = __('durante el mes de') . ' ' . ucfirst(Utiles::sql3fecha(date('Y-m-d'), '%B %Y'));
					$fecha_al = __('al mes de') . ' ' . ucfirst(Utiles::sql3fecha(date('Y-m-d'), '%B de %Y'));
					$fecha_diff_con_de = __('durante el mes de') . ' ' . ucfirst(Utiles::sql3fecha(date('Y-m-d'), '%B de %Y'));
					$fecha_diff_prestada = __('prestada en el mes de') . ' ' . ucfirst(Utiles::sql3fecha(date('Y-m-d'), '%B de %Y'));
				}

				//Se saca la fecha inicial según el primer trabajo
				//esto es especial para LyR
				$query = "SELECT fecha FROM trabajo WHERE id_cobro='" . $this->fields['id_cobro'] . "' AND visible='1' ORDER BY fecha LIMIT 1";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				//acá se calcula si hay trabajos o no (porque si no sale como fecha 1969)
				if (mysql_num_rows($resp) > 0)
					list($fecha_primer_trabajo) = mysql_fetch_array($resp);
				else
					$fecha_primer_trabajo = $this->fields['fecha_fin'];

				//También se saca la fecha final según el último trabajo
				$query = "SELECT LAST_DAY(fecha) FROM trabajo WHERE id_cobro='" . $this->fields['id_cobro'] . "' AND visible='1' ORDER BY fecha DESC LIMIT 1";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				//acá se calcula si hay trabajos o no (porque si no sale como fecha 1969)
				if (mysql_num_rows($resp) > 0)
					list($fecha_ultimo_trabajo) = mysql_fetch_array($resp);
				else
					$fecha_ultimo_trabajo = $this->fields['fecha_fin'];
				$fecha_inicial_primer_trabajo = date('Y-m-01', strtotime($fecha_primer_trabajo));
				$fecha_final_ultimo_trabajo = date('Y-m-d', strtotime($fecha_ultimo_trabajo));

				$datefrom = strtotime($fecha_inicial_primer_trabajo, 0);
				$dateto = strtotime($fecha_final_ultimo_trabajo, 0);
				$difference = $dateto - $datefrom; //Dif segundos
				$months_difference = floor($difference / 2678400);
				while (mktime(date("H", $datefrom), date("i", $datefrom), date("s", $datefrom), date("n", $datefrom) + ($months_difference), date("j", $dateto), date("Y", $datefrom)) < $dateto) {
					$months_difference++;
				}

				$datediff = $months_difference;

				$asuntos_doc = '';
				for ($k = 0; $k < count($this->asuntos); $k++) {
					$asunto = new Asunto($this->sesion);
					$asunto->LoadByCodigo($this->asuntos[$k]);
					$espace = $k < count($this->asuntos) - 1 ? ', ' : '';
					$asuntos_doc .= $asunto->fields['glosa_asunto'] . '' . $espace;
					$codigo_asunto .= $asunto->fields['codigo_asunto'] . '' . $espace;
				}
				$html2 = str_replace('%Asunto%', $asuntos_doc, $html2);

				$asunto_ucwords = ucwords(strtolower($asuntos_doc));
				$html2 = str_replace('%Asunto_ucwords%', $asunto_ucwords, $html2);

				/*
				  Mostrando fecha según idioma
				 */
				if ($fecha_inicial_primer_trabajo != '' && $fecha_inicial_primer_trabajo != '0000-00-00') {
					if ($lang == 'es')
						$fecha_diff_periodo_exacto = __('desde el día') . ' ' . date("d-m-Y", strtotime($fecha_primer_trabajo)) . ' ';
					else
						$fecha_diff_periodo_exacto = __('from') . ' ' . date("d-m-Y", strtotime($fecha_primer_trabajo)) . ' ';
					if (Utiles::sql3fecha($fecha_inicial_primer_trabajo, '%Y') == Utiles::sql3fecha($this->fields['fecha_fin'], '%Y')) {
						$texto_fecha_es = __('entre los meses de') . ' ' . ucfirst(Utiles::sql3fecha($fecha_inicial_primer_trabajo, '%B')) . ' ' . __('y') . ' ' . ucfirst(Utiles::sql3fecha($fecha_final_ultimo_trabajo, '%B %Y'));
						$texto_fecha_es_de = __('entre los meses de') . ' ' . ucfirst(Utiles::sql3fecha($fecha_inicial_primer_trabajo, '%B')) . ' ' . __('y') . ' ' . ucfirst(Utiles::sql3fecha($fecha_final_ultimo_trabajo, '%B de %Y'));
					} else {
						$texto_fecha_es = __('entre los meses de') . ' ' . ucfirst(Utiles::sql3fecha($fecha_inicial_primer_trabajo, '%B %Y')) . ' ' . __('y') . ' ' . ucfirst(Utiles::sql3fecha($fecha_final_ultimo_trabajo, '%B %Y'));
						$texto_fecha_es_de = __('entre los meses de') . ' ' . ucfirst(Utiles::sql3fecha($fecha_inicial_primer_trabajo, '%B %Y')) . ' ' . __('y') . ' ' . ucfirst(Utiles::sql3fecha($fecha_final_ultimo_trabajo, '%B de %Y'));
					}
				} else {
					$texto_fecha_es = __('hasta el mes de') . ' ' . ucfirst(ucfirst(Utiles::sql3fecha($fecha_final_ultimo_trabajo, '%B %Y')));
					$texto_fecha_es_de = __('hasta el mes de') . ' ' . ucfirst(ucfirst(Utiles::sql3fecha($fecha_final_ultimo_trabajo, '%B de %Y')));
				}

				if ($lang == 'es')
					$fecha_diff_periodo_exacto .= __('hasta el día') . ' ' . Utiles::sql3fecha($this->fields['fecha_fin'], '%d-%m-%Y');
				else
					$fecha_diff_periodo_exacto .= __('until') . ' ' . Utiles::sql3fecha($this->fields['fecha_fin'], '%d-%m-%Y');

				if ($fecha_inicial_primer_trabajo != '' && $fecha_inicial_primer_trabajo != '0000-00-00') {
					if (Utiles::sql3fecha($fecha_inicial_primer_trabajo, '%Y') == Utiles::sql3fecha($fecha_final_ultimo_trabajo, '%Y'))
						$texto_fecha_en = __('between') . ' ' . ucfirst(date('F', strtotime($fecha_inicial_primer_trabajo))) . ' ' . __('and') . ' ' . ucfirst(date('F Y', strtotime($fecha_final_ultimo_trabajo)));
					else
						$texto_fecha_en = __('between') . ' ' . ucfirst(date('F Y', strtotime($fecha_inicial_primer_trabajo))) . ' ' . __('and') . ' ' . ucfirst(date('F Y', strtotime($fecha_final_ultimo_trabajo)));
				}
				else
					$texto_fecha_en = __('until') . ' ' . ucfirst(date('F Y', strtotime($fecha_final_ultimo_trabajo)));

				if ($lang == 'es')
					$fecha_primer_trabajo = $datediff > 0 && $datediff < 48 ? $texto_fecha_es : __('durante el mes de') . ' ' . ucfirst(Utiles::sql3fecha($fecha_final_ultimo_trabajo, '%B %Y'));
				else
					$fecha_primer_trabajo = $datediff > 0 && $datediff < 48 ? $texto_fecha_en : __('during') . ' ' . ucfirst(date('F Y', strtotime($fecha_final_ultimo_trabajo)));

				if ($fecha_primer_trabajo == 'No existe fecha' && $lang == es)
					$fecha_primer_trabajo = ucfirst(Utiles::sql3fecha(date('Y-m-d'), '%B %Y'));

				if ($lang == 'es')
					$fecha_primer_trabajo_de = $datediff > 0 && $datediff < 48 ? $texto_fecha_es_de : __('durante el mes de') . ' ' . ucfirst(Utiles::sql3fecha($fecha_final_ultimo_trabajo, '%B de %Y'));
				else
					$fecha_primer_trabajo_de = $datediff > 0 && $datediff < 48 ? $texto_fecha_en : __('during') . ' ' . ucfirst(date('F Y', strtotime($fecha_final_ultimo_trabajo)));

				if ($fecha_primer_trabajo_de == 'No existe fecha' && $lang == es)
					$fecha_primer_trabajo_de = ucfirst(Utiles::sql3fecha(date('Y-m-d'), '%B %Y'));

				if ($this->fields['opc_moneda_total'] != $this->fields['id_moneda'])
					$html2 = str_replace('%equivalente_dolm%', ' que ascienden a %monto%', $html2);
				else
					$html2 = str_replace('%equivalente_dolm%', '', $html2);
				$html2 = str_replace('%num_factura%', $this->fields['documento'], $html2);
				$html2 = str_replace('%n_num_factura%', 'N°' . $this->fields['documento'], $html2);
				$html2 = str_replace('%fecha_primer_trabajo%', $fecha_primer_trabajo, $html2);
				$html2 = str_replace('%fecha_primer_trabajo_de%', $fecha_primer_trabajo_de, $html2);
				$html2 = str_replace('%fecha%', $fecha_diff, $html2);
				$html2 = str_replace('%fecha_al%', $fecha_al, $html2);
				$html2 = str_replace('%fecha_al_minuscula%', strtolower($fecha_al), $html2);
				$html2 = str_replace('%fecha_con_de%', $fecha_diff_con_de, $html2);
				$html2 = str_replace('%fecha_con_prestada%', $fecha_diff_prestada, $html2);
				$html2 = str_replace('%fecha_con_prestada_mayuscula%', mb_strtoupper($fecha_diff_prestada), $html2);
				$html2 = str_replace('%fecha_con_prestada_minusculas%', strtolower($fecha_diff_prestada), $html2);
				$html2 = str_replace('%fecha_emision%', $this->fields['fecha_emision'] ? Utiles::sql2fecha($this->fields['fecha_emision'], '%d de %B') : Utiles::sql2fecha($this->fields['fecha_fin'], '%d de %B'), $html2);
				$html2 = str_replace('%monto_total_demo_uf%', number_format($monto_moneda_demo, $cobro_moneda->moneda[3]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . $cobro_moneda->moneda[3]['simbolo'], $html2);
				$html2 = str_replace('%fecha_periodo_exacto%', $fecha_diff_periodo_exacto, $html2);
				$html2 = str_replace('%monto_total_demo_jdf%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . number_format($monto_moneda_demo, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);

				$fecha_dia_carta = ucfirst(Utiles::sql3fecha(date('Y-m-d'), '%d de %B de %Y'));
				$html2 = str_replace('%fecha_dia_carta%', $fecha_dia_carta, $html2);
				if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'ValorSinEspacio') ) || ( method_exists('Conf', 'ValorSinEspacio') && Conf::ValorSinEspacio() )))
					$html2 = str_replace('%monto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . number_format($monto_moneda, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
				else
					$html2 = str_replace('%monto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . ' ' . number_format($monto_moneda, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
				$html2 = str_replace('%monto_solo_gastos%', '$ ' . number_format($gasto_en_pesos, 0, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
				if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'ValorSinEspacio') ) || ( method_exists('Conf', 'ValorSinEspacio') && Conf::ValorSinEspacio() )))
					$html2 = str_replace('%monto_sin_gasto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . number_format($monto_moneda_sin_gasto, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
				else
					$html2 = str_replace('%monto_sin_gasto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . ' ' . number_format($monto_moneda_sin_gasto, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);

				if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'ValorSinEspacio') ) || ( method_exists('Conf', 'ValorSinEspacio') && Conf::ValorSinEspacio() ))) {
					$html2 = str_replace('%monto_total_demo%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . number_format($monto_moneda_demo, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
					$html2 = str_replace('%monto_con_gasto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . number_format($monto_moneda_con_gasto, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
					$html2 = str_replace('%monto_original%', $moneda->fields['simbolo'] . number_format($this->fields['monto'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
					$html2 = str_replace('%monto_total_sin_iva%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . number_format($monto_moneda_subtotal, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
				} else {
					$html2 = str_replace('%monto_total_demo%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . number_format($monto_moneda_demo, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
					$html2 = str_replace('%monto_con_gasto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . ' ' . number_format($monto_moneda_con_gasto, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
					$html2 = str_replace('%monto_original%', $moneda->fields['simbolo'] . ' ' . number_format($this->fields['monto'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
					$html2 = str_replace('%monto_total_sin_iva%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . ' ' . number_format($monto_moneda_subtotal, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
				}

				if ($this->fields['opc_moneda_total'] != $this->fields['id_moneda'])
					$html2 = str_replace('%equivalente_a_baz%', ', equivalentes a ' . $moneda->fields['simbolo'] . ' ' . number_format($this->fields['monto'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
				else
					$html2 = str_replace('%equivalente_a_baz%', '', $html2);
				$html2 = str_replace('%simbolo_moneda%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'], $html2);
				$html2 = str_replace('%simbolo_moneda_total%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'], $html2);
				#Para montos solamente sea distinto a pesos $
				if ($this->fields['tipo_cambio_moneda_base'] <= 0)
					$tipo_cambio_moneda_base_cobro = 1;
				else
					$tipo_cambio_moneda_base_cobro = $this->fields['tipo_cambio_moneda_base'];

				$fecha_hasta_cobro = strftime(Utiles::FormatoStrfTime('%e de %B'), mktime(0, 0, 0, date("m", strtotime($this->fields['fecha_fin'])), date("d", strtotime($this->fields['fecha_fin'])), date("Y", strtotime($this->fields['fecha_fin']))));
				$html2 = str_replace('%fecha_hasta%', $fecha_hasta_cobro, $html2);
				if ($this->fields['id_moneda'] > 1 && $moneda_total->fields['id_moneda'] > 1) { #!= $moneda_cli->fields['id_moneda']
					$en_pesos = (double) $this->fields['monto'] * ($this->fields['tipo_cambio_moneda'] / $tipo_cambio_moneda_base_cobro);
					$html2 = str_replace('%monto_en_pesos%', __(', equivalentes a esta fecha a $ ') . number_format($en_pesos, 0, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . '.-', $html2);
				}
				else
					$html2 = str_replace('%monto_en_pesos%', '', $html2);

				#si hay gastos se muestran
				if ($total_gastos > 0) {
					#$gastos_moneda_total = ($totales['total']*($this->fields['tipo_cambio_moneda']/$tipo_cambio_cobro_moneda_base))/$tipo_cambio_moneda_total;
					#icc$gasto_en_pesos = ($total_gastos*($moneda_total->fields['tipo_cambio']/$tipo_cambio_moneda_base_cobro))/$tipo_cambio_moneda_total;#error gastos 1
					$gasto_en_pesos = $total_gastos;
					$txt_gasto = "Asimismo, se agregan los gastos por la suma total de";
					$html2 = str_replace('%monto_gasto_separado%', $txt_gasto . ' $' . number_format($gasto_en_pesos, 0, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
				}
				else
					$html2 = str_replace('%monto_gasto_separado%', '', $html2);

				$query = "SELECT count(*) FROM cta_corriente WHERE id_cobro = '" . $this->fields['id_cobro'] . "'";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				list($cantidad_de_gastos) = mysql_fetch_array($resp);

				//echo 'simbolo: '.$moneda_total->fields['simbolo'].'<br>
				if (( $this->fields['monto_gastos'] > 0 || $cantidad_de_gastos > 0 ) && $this->fields['opc_ver_gastos']) {
					// Calculo especial para BAZ, en ves de mostrar el total de gastos, se muestra la cuenta corriente al día
					$where_gastos = " 1 ";
					$lista_asuntos = implode(',', $this->asuntos);
					if (!empty($lista_asuntos)) {
						$where_gastos .= " AND cta_corriente.codigo_asunto IN ('$lista_asuntos') ";
					}
					$where_gastos .= " AND cta_corriente.codigo_cliente = '" . $this->fields['codigo_cliente'] . "' ";
					$where_gastos .= " AND cta_corriente.fecha <= '" . $this->fields['fecha_fin'] . "' ";
					$cuenta_corriente_actual = number_format(UtilesApp::TotalCuentaCorriente($this->sesion, $where_gastos), $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);

					$html2 = str_replace('%frase_gastos_ingreso%', '<tr>
												    <td width="5%">&nbsp;</td>
												<td align="left" class="detalle"><p>Adjunto a la presente encontrarás comprobantes de gastos realizados por cuenta de ustedes por la suma de ' . $cuenta_corriente_actual . '</p></td>
												<td width="5%">&nbsp;</td>
												  </tr>
												  <tr>
												    <td>&nbsp;</td>
												    <td valign="top" align="left" class="detalle"><p>&nbsp;</p></td>
												  </tr>', $html2);
					$html2 = str_replace('%frase_gastos_egreso%', '<tr>
												    <td width="5%">&nbsp;</td>
														<td valign="top" align="left" class="detalle"><p>A mayor abundamiento, les recordamos que a esta fecha <u>existen cobros de notaría por la suma de $xxxxxx.-</u>, la que les agradeceré enviar en cheque nominativo a la orden de don Eduardo Avello Concha.</p></td>
														<td width="5%">&nbsp;</td>
												  </tr>
													<tr>
												    <td>&nbsp;</td>
												    <td valign="top" align="left" class="vacio"><p>&nbsp;</p></td>
												<td>&nbsp;</td>
												  </tr>', $html2);
				} else {
					$html2 = str_replace('%frase_gastos_ingreso%', '', $html2);
					$html2 = str_replace('%frase_gastos_egreso%', '', $html2);
				}
				if ($total_gastos > 0) {
					if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'ValorSinEspacio') ) || ( method_exists('Conf', 'ValorSinEspacio') && Conf::ValorSinEspacio() )))
						$html2 = str_replace('%monto_gasto%', $moneda_total->fields['simbolo'] . number_format($total_gastos, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
					else
						$html2 = str_replace('%monto_gasto%', $moneda_total->fields['simbolo'] . ' ' . number_format($total_gastos, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
				}
				else {
					if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'ValorSinEspacio') ) || ( method_exists('Conf', 'ValorSinEspacio') && Conf::ValorSinEspacio() )))
						$html2 = str_replace('%monto_gasto%', $moneda_total->fields['simbolo'] . number_format(0, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
					else
						$html2 = str_replace('%monto_gasto%', $moneda_total->fields['simbolo'] . ' ' . number_format(0, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
				}
				if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'ValorSinEspacio') ) || ( method_exists('Conf', 'ValorSinEspacio') && Conf::ValorSinEspacio() )))
					$html2 = str_replace('%monto_gasto_separado_baz%', $moneda_total->fields['simbolo'] . number_format($this->fields['saldo_final_gastos'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
				else
					$html2 = str_replace('%monto_gasto_separado_baz%', $moneda_total->fields['simbolo'] . ' ' . number_format($this->fields['saldo_final_gastos'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
				$html2 = str_replace('%num_letter%', $this->fields['id_cobro'], $html2);
				$html2 = str_replace('%num_letter_documento%', $this->fields['documento'], $html2);
				$html2 = str_replace('%num_letter_baz%', $this->fields['documento'], $html2);
				if (($this->fields['documento'] != '')) {
					$html2 = str_replace('%num_letter_rebaza%', __('la factura N°') . ' ' . $this->fields['documento'], $html2);
				} else {
					$html2 = str_replace('%num_letter_rebaza%', __('el cobro N°') . ' ' . $this->fields['id_cobro'], $html2);
				}
				# datos detalle carta mb y ebmo
				$html2 = str_replace('%si_gastos%', $total_gastos > 0 ? __('y reembolso de gastos') : '', $html2);
				if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'ValorSinEspacio') ) || ( method_exists('Conf', 'ValorSinEspacio') && Conf::ValorSinEspacio() )))
					$detalle_cuenta_honorarios = '(i) ' . $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . number_format($monto_moneda_sin_gasto, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ' ' . __('por concepto de honorarios');
				else
					$detalle_cuenta_honorarios = '(i) ' . $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . ' ' . number_format($monto_moneda_sin_gasto, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ' ' . __('por concepto de honorarios');
				if ($this->fields['id_moneda'] == 2 && $moneda_total->fields['id_moneda'] == 1) {
					$detalle_cuenta_honorarios .= ' (';
					if ($this->fields['forma_cobro'] == 'FLAT FEE')
						$detalle_cuenta_honorarios .= __('retainer ');
					if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'ValorSinEspacio') ) || ( method_exists('Conf', 'ValorSinEspacio') && Conf::ValorSinEspacio() )))
						$detalle_cuenta_honorarios .= __('equivalente en pesos a ') . $moneda->fields['simbolo'] . number_format($this->fields['monto'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
					else
						$detalle_cuenta_honorarios .= __('equivalente en pesos a ') . $moneda->fields['simbolo'] . ' ' . number_format($this->fields['monto'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
					$detalle_cuenta_honorarios .= __(', conforme al tipo de cambio observado del día de hoy') . ')';
					$detalle_cuenta_honorarios_primer_dia_mes = '';
					if ($this->fields['monto_subtotal'] > 0) {
						if ($this->fields['monto_gastos'] > 0) {
							if ($this->fields['monto'] == round($this->fields['monto']))
								$detalle_cuenta_honorarios_primer_dia_mes .= __('. Esta cantidad corresponde a') . __(' (i) ') . $moneda->fields['simbolo'] . number_format($this->fields['monto'], 0, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ' ' . __('por concepto de honorarios');
							else
								$detalle_cuenta_honorarios_primer_dia_mes .= __('. Esta cantidad corresponde a') . __(' (i) ') . $moneda->fields['simbolo'] . number_format($this->fields['monto'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ' ' . __('por concepto de honorarios');
						}
						else
							$detalle_cuenta_honorarios_primer_dia_mes .= ' ' . __('correspondiente a') . ' ' . $moneda->fields['simbolo'] . number_format($this->fields['monto'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ' ' . __('por concepto de honorarios');
						$detalle_cuenta_honorarios_primer_dia_mes .= ' ( ' . __('conforme a su equivalencia en peso según el Dólar Observado publicado por el Banco Central de Chile, el primer día hábil del presente mes') . ' )';
					}
				}
				if ($this->fields['id_moneda'] == 3 && $moneda_total->fields['id_moneda'] == 1) {
					$detalle_cuenta_honorarios .= ' (';
					if ($this->fields['forma_cobro'] == 'FLAT FEE')
						$detalle_cuenta_honorarios .= __('retainer ');
					if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'ValorSinEspacio') ) || ( method_exists('Conf', 'ValorSinEspacio') && Conf::ValorSinEspacio() )))
						$detalle_cuenta_honorarios .= $moneda->fields['simbolo'] . number_format($this->fields['monto'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
					else
						$detalle_cuenta_honorarios .= $moneda->fields['simbolo'] . ' ' . number_format($this->fields['monto'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
					$detalle_cuenta_honorarios .= __(', conforme a su equivalencia al ');
					$detalle_cuenta_honorarios .= $lang == 'es' ? Utiles::sql3fecha($this->fields['fecha_fin'], '%d de %B de %Y') : Utiles::sql3fecha($this->fields['fecha_fin'], '%m-%d-%Y');
					$detalle_cuenta_honorarios .= ')';
					$detalle_cuenta_honorarios_primer_dia_mes = '';
					if ($this->fields['monto_subtotal'] > 0) {
						if ($this->fields['monto_gastos'] > 0) {
							if ($this->fields['monto'] == round($this->fields['monto']))
								$detalle_cuenta_honorarios_primer_dia_mes = __('. Esta cantidad corresponde a') . __(' (i) ') . $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . number_format($monto_moneda_sin_gasto, 0, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ' ' . __('por concepto de honorarios');
							else
								$detalle_cuenta_honorarios_primer_dia_mes = __('. Esta cantidad corresponde a') . __(' (i) ') . $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . number_format($monto_moneda_sin_gasto, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ' ' . __('por concepto de honorarios');
						}
						$detalle_cuenta_honorarios_primer_dia_mes .= ' ( ' . __('equivalente a') . ' ' . $moneda->fields['simbolo'] . number_format($this->fields['monto'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
						$detalle_cuenta_honorarios_primer_dia_mes .= __(', conforme a su equivalencia en pesos al primer día hábil del presente mes') . ')';
					}
				}
				$boleta_honorarios = __('según Boleta de Honorarios adjunta');
				if ($total_gastos != 0) {
					if ($this->fields['monto_subtotal'] > 0) {
						if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'ValorSinEspacio') ) || ( method_exists('Conf', 'ValorSinEspacio') && Conf::ValorSinEspacio() )))
							$detalle_cuenta_gastos = __('; más') . ' (ii) ' . $moneda_total->fields['simbolo'] . number_format($total_gastos, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ' ' . __('por concepto de gastos incurridos por nuestro Estudio en dicho período');
						else
							$detalle_cuenta_gastos = __('; más') . ' (ii) ' . $moneda_total->fields['simbolo'] . ' ' . number_format($total_gastos, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ' ' . __('por concepto de gastos incurridos por nuestro Estudio en dicho período');
					}
					else
						$detalle_cuenta_gastos = __(' por concepto de gastos incurridos por nuestro Estudio en dicho período');
					if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'ValorSinEspacio') ) || ( method_exists('Conf', 'ValorSinEspacio') && Conf::ValorSinEspacio() )))
						$boleta_gastos = __('; más') . ' (ii) ' . $moneda_total->fields['simbolo'] . number_format($total_gastos, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . __('por gastos a reembolsar') . __(', según Boleta de Recuperación de Gastos adjunta');
					else
						$boleta_gastos = __('; más') . ' (ii) ' . $moneda_total->fields['simbolo'] . ' ' . number_format($total_gastos, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ' ' . __('por gastos a reembolsar') . __(', según Boleta de Recuperación de Gastos adjunta');
					if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'ValorSinEspacio') ) || ( method_exists('Conf', 'ValorSinEspacio') && Conf::ValorSinEspacio() )))
						$detalle_cuenta_gastos2 = __('; más') . ' (ii) CH' . $moneda_total->fields['simbolo'] . number_format($total_gastos, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ' ' . __('por concepto de gastos incurridos por nuestro Estudio');
					else
						$detalle_cuenta_gastos2 = __('; más') . ' (ii) CH' . $moneda_total->fields['simbolo'] . ' ' . number_format($total_gastos, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ' ' . __('por concepto de gastos incurridos por nuestro Estudio');
				}
				$html2 = str_replace('%boleta_honorarios%', $boleta_honorarios, $html2);
				$html2 = str_replace('%boleta_gastos%', $boleta_gastos, $html2);
				$html2 = str_replace('%detalle_cuenta_honorarios%', $detalle_cuenta_honorarios, $html2);
				$html2 = str_replace('%detalle_cuenta_honorarios_primer_dia_mes%', $detalle_cuenta_honorarios_primer_dia_mes, $html2);
				$html2 = str_replace('%detalle_cuenta_gastos%', $detalle_cuenta_gastos, $html2);
				$html2 = str_replace('%detalle_cuenta_gastos2%', $detalle_cuenta_gastos2, $html2);

				$query = "SELECT CONCAT_WS(' ',usuario.nombre,usuario.apellido1,usuario.apellido2) as nombre_encargado
										FROM usuario
										JOIN contrato ON usuario.id_usuario=contrato.id_usuario_responsable
									 	JOIN cobro ON contrato.id_contrato=cobro.id_contrato
									 WHERE cobro.id_cobro=" . $this->fields['id_cobro'];
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				list($nombre_encargado) = mysql_fetch_array($resp);
				$html2 = str_replace('%encargado_comercial%', $nombre_encargado, $html2);
				$html2 = str_replace('%encargado_comercial_uc%', ucwords(strtolower($nombre_encargado)), $html2);
				break;
		}

		return $html2;
	}

	function GenerarDocumentoCarta2($parser_carta, $theTag = '', $lang, $moneda_cliente_cambio, $moneda_cli, & $idioma, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $cliente, $id_carta) {
		global $id_carta;
		global $contrato;
		global $cobro_moneda;
		global $moneda_total;
		global $x_resultados;
		global $x_cobro_gastos;
		global $moneda_cobro;

		if (!isset($parser_carta->tags[$theTag]))
			return;

		$html2 = $parser_carta->tags[$theTag];

		switch ($theTag) {
			case 'CARTA': //GenerarDocumentoCarta2
				
				//$html2 = str_replace('%CARTA_GASTOS%', $this->GenerarDocumentoCartaComun($parser_carta, 'CARTA_GASTOS', $lang, $moneda_cliente_cambio, $moneda_cli, $idioma, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $cliente, $id_carta), $html2);
				//$html2 = str_replace('%CARTA_HONORARIOS%', $this->GenerarDocumentoCartaComun($parser_carta, 'CARTA_HONORARIOS', $lang, $moneda_cliente_cambio, $moneda_cli, $idioma, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $cliente, $id_carta), $html2);

				if (method_exists('Conf', 'GetConf')) {
					$PdfLinea1 = Conf::GetConf($this->sesion, 'PdfLinea1');
					$PdfLinea2 = Conf::GetConf($this->sesion, 'PdfLinea2');
					$PdfLinea3 = Conf::GetConf($this->sesion, 'PdfLinea3');
				} else {
					$PdfLinea1 = Conf::PdfLinea1();
					$PdfLinea2 = Conf::PdfLinea2();
					$PdfLinea3 = Conf::PdfLinea3();
				}

				if (strpos($html2, '%cuenta_banco%')) {
					if ($contrato->fields['id_cuenta']) {
						$query_banco = "SELECT glosa FROM cuenta_banco WHERE id_cuenta = '" . $contrato->fields['id_cuenta'] . "'";
						$resp = mysql_query($query_banco, $this->sesion->dbh) or Utiles::errorSQL($query_banco, __FILE__, __LINE__, $this->sesion->dbh);
						list($glosa_cuenta) = mysql_fetch_array($resp);
					}
					else
						$glosa_cuenta = '';
					$html2 = str_replace('%cuenta_banco%', $glosa_cuenta, $html2);
				}

				$html2 = str_replace('%logo_carta%', Conf::Server() . Conf::ImgDir(), $html2);
				$html2 = str_replace('%direccion%', $PdfLinea1, $html2);
				$html2 = str_replace('%titulo%', $PdfLinea1, $html2);
				$html2 = str_replace('%subtitulo%', $PdfLinea2, $html2);
				$html2 = str_replace('%numero_cobro%', $this->fields['id_cobro'], $html2);
				$html2 = str_replace('%xfecha_mes_dos_digitos%', date("m", strtotime($this->fields['fecha_emision'])), $html2);
				$html2 = str_replace('%xfecha_ano_dos_digitos%', date("y", strtotime($this->fields['fecha_emision'])), $html2);
				$html2 = str_replace('%xnro_factura%', $this->fields['id_cobro'], $html2);

				$html2 = str_replace(array('%xnombre_cliente%', '%glosa_cliente%'), $contrato->fields['factura_razon_social'], $html2); #glosa cliente de factura

				$html2 = str_replace('%xdireccion%', nl2br($contrato->fields['factura_direccion']), $html2);
				$html2 = str_replace('%xrut%', $contrato->fields['rut'], $html2);
				$html2 = str_replace('%FECHA%', $this->GenerarDocumentoCartaComun($parser_carta, 'FECHA', $lang, $moneda_cliente_cambio, $moneda_cli, $idioma, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $cliente, $id_carta), $html2);
				$html2 = str_replace('%ENVIO_DIRECCION%', $this->GenerarDocumentoCartaComun($parser_carta, 'ENVIO_DIRECCION', $lang, $moneda_cliente_cambio, $moneda_cli, $idioma, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $cliente, $id_carta), $html2);
				$html2 = str_replace('%DETALLE%', $this->GenerarDocumentoCarta2($parser_carta, 'DETALLE', $lang, $moneda_cliente_cambio, $moneda_cli, $idioma, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $cliente, $id_carta), $html2);
				$html2 = str_replace('%ADJ%', $this->GenerarDocumentoCartaComun($parser_carta, 'ADJ', $lang, $moneda_cliente_cambio, $moneda_cli, $idioma, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $cliente, $id_carta), $html2);
				$html2 = str_replace('%PIE%', $this->GenerarDocumentoCartaComun($parser_carta, 'PIE', $lang, $moneda_cliente_cambio, $moneda_cli, $idioma, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $cliente, $id_carta), $html2);
				$html2 = str_replace('%DATOS_CLIENTE%', $this->GenerarDocumentoCartaComun($parser_carta, 'DATOS_CLIENTE', $lang, $moneda_cliente_cambio, $moneda_cli, $idioma, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $cliente, $id_carta), $html2);
				$html2 = str_replace('%SALTO_PAGINA%', $this->GenerarDocumentoCartaComun($parser_carta, 'SALTO_PAGINA', $lang, $moneda_cliente_cambio, $moneda_cli, $idioma, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $cliente, $id_carta), $html2);

				break;



			case 'DETALLE': //GenerarDocumentoCarta2

				if (strpos($html2, '%cuenta_banco%')) {
					if ($contrato->fields['id_cuenta']) {
						$query_banco = "SELECT glosa FROM cuenta_banco WHERE id_cuenta = '" . $contrato->fields['id_cuenta'] . "'";
						$resp = mysql_query($query_banco, $this->sesion->dbh) or Utiles::errorSQL($query_banco, __FILE__, __LINE__, $this->sesion->dbh);
						list($glosa_cuenta) = mysql_fetch_array($resp);
					}
					else
						$glosa_cuenta = '';
					$html2 = str_replace('%cuenta_banco%', $glosa_cuenta, $html2);
				}
				if (isset($contrato->fields['glosa_contrato'])) {
					$html2 = str_replace('%glosa_contrato%', $contrato->fields['glosa_contrato'], $html2);
				} else {
					$html2 = str_replace('%glosa_contrato%', '', $html2);
				}

				if (isset($contrato->fields['codigopropuesta'])) {
					$html2 = str_replace('%codigopropuesta%', $contrato->fields['codigopropuesta'], $html2);
				} else {
					$html2 = str_replace('%codigopropuesta%', '', $html2);
				}



				$html2 = str_replace('%logo_carta%', Conf::Server() . Conf::ImgDir(), $html2);

				$html2 = str_replace('%glosa_cliente%', $contrato->fields['factura_razon_social'], $html2);

				$html2 = str_replace('%rut_cliente%', $contrato->fields['rut'], $html2);

				$html2 = str_replace('%glosa_cliente_mayuscula%', strtoupper($contrato->fields['factura_razon_social']), $html2);
				$html2 = str_replace('%num_letter%', $this->fields['id_cobro'], $html2);
				$html2 = str_replace('%num_factura%', $this->fields['documento'], $html2);


				/* Primero se hacen las cartas particulares ya que lee los datos que siguen */
				#carta mb
				$html2 = str_replace('%saludo_mb%', __('%saludo_mb%'), $html2);
				if (count($this->asuntos) > 1) {
					$html2 = str_replace('%detalle_mb%', __('%detalle_mb_asuntos%'), $html2);
					$html2 = str_replace('%detalle_mb_ny%', __('%detalle_mb_ny_asuntos%'), $html2);
					$html2 = str_replace('%detalle_mb_boleta%', __('%detalle_mb_boleta_asuntos%'), $html2);
				} else {
					$html2 = str_replace('%detalle_mb%', __('%detalle_mb%'), $html2);
					$html2 = str_replace('%detalle_mb_ny%', __('%detalle_mb_ny%'), $html2);
					$html2 = str_replace('%detalle_mb_boleta%', __('%detalle_mb_boleta%'), $html2);
				}

				$this->LoadGlosaAsuntos();
				$lista_asuntos = "<ul>";
				foreach ($this->glosa_asuntos as $key => $asunto) {
					$lista_asuntos .= "<li>" . $asunto . "</li>";
				}
				$lista_asuntos .= "</ul>";
				$html2 = str_replace('%lista_asuntos%', $lista_asuntos, $html2);

				$lista_asuntos_guion = implode(" - ", $this->glosa_asuntos);
				$html2 = str_replace('%lista_asuntos_guion%', $lista_asuntos_guion, $html2);

				$html2 = str_replace('%FILAS_ASUNTOS_RESUMEN%', $this->GenerarDocumentoCartaComun($parser_carta, 'FILAS_ASUNTOS_RESUMEN', $lang, $moneda_cliente_cambio, $moneda_cli, $idioma, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $cliente, $id_carta), $html2);

				$html2 = str_replace('%FILAS_FACTURAS_DEL_COBRO%', $this->GenerarDocumentoCartaComun($parser_carta, 'FILAS_FACTURAS_DEL_COBRO', $lang, $moneda_cliente_cambio, $moneda_cli, $idioma, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $cliente, $id_carta), $html2);

				$html2 = str_replace('%FILA_FACTURAS_PENDIENTES%', $this->GenerarDocumentoCartaComun($parser_carta, 'FILA_FACTURAS_PENDIENTES', $lang, $moneda_cliente_cambio, $moneda_cli, $idioma, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $cliente, $id_carta), $html2);


				$html2 = str_replace('%cuenta_mb%', __('%cuenta_mb%'), $html2);
				$html2 = str_replace('%despedida_mb%', __('%despedida_mb%'), $html2);
				$html2 = str_replace('%cuenta_mb_ny%', __('%cuenta_mb_ny%'), $html2);
				$html2 = str_replace('%cuenta_mb_boleta%', __('%cuenta_mb_boleta%'), $html2);
				#carta careyallende
				$html2 = str_replace('%detalle_careyallende%', __('%detalle_careyallende%'), $html2);
				#carta ebmo
				if ($this->fields['monto_gastos'] > 0 && $this->fields['monto_subtotal'] == 0) {
					$html2 = str_replace('%detalle_ebmo%', __('%detalle_ebmo_solo_gastos%'), $html2);

					$html2 = str_replace('%monto_honorarios_cuando_hay%', '', $html2);
					$html2 = str_replace('%concepto_honorarios_cuando_hay%', '', $html2);
					$html2 = str_replace('%monto_gastos_cuando_hay%', '%monto_gasto%', $html2);
					$html2 = str_replace('%concepto_gastos_cuando_hay%', __('por_concepto_de_gastos'), $html2);
				} else if ($this->fields['monto_gastos'] == 0 && $this->fields['monto_subtotal'] > 0) {

					$html2 = str_replace('%detalle_ebmo%', __('%detalle_ebmo_solo_honorarios%'), $html2);
					$html2 = str_replace('%monto_gastos_cuando_hay%', '', $html2);
					$html2 = str_replace('%concepto_gastos_cuando_hay%', '', $html2);
					$html2 = str_replace('%monto_honorarios_cuando_hay%', '%monto_sin_gasto%', $html2);
					$html2 = str_replace('%concepto_honorarios_cuando_hay%', __('por_concepto_de_honorarios'), $html2);
				} else {
					$html2 = str_replace('%detalle_ebmo%', __('%detalle_ebmo%'), $html2);
					$html2 = str_replace('%monto_honorarios_cuando_hay%', '%monto_sin_gasto%', $html2);
					$html2 = str_replace('%concepto_honorarios_cuando_hay%', __('por_concepto_de_honorarios') . ' y ', $html2);
					$html2 = str_replace('%monto_gastos_cuando_hay%', '%monto_gasto%', $html2);
					$html2 = str_replace('%concepto_gastos_cuando_hay%', __('por_concepto_de_gastos'), $html2);
				}
				/* Datos detalle */
				if (method_exists('Conf', 'GetConf')) {
					if (Conf::GetConf($this->sesion, 'TituloContacto')) {
						$html2 = str_replace('%sr%', __($contrato->fields['titulo_contacto']), $html2);
						$html2 = str_replace('%NombrePilaContacto%', $contrato->fields['contacto'], $html2);
						$html2 = str_replace('%ApellidoContacto%', $contrato->fields['apellido_contacto'], $html2);
					} else {
						$html2 = str_replace('%sr%', __('Señor'), $html2);
						$NombreContacto = explode(' ', $contrato->fields['contacto']);
						$html2 = str_replace('%NombrePilaContacto%', $NombreContacto[0], $html2);
						$html2 = str_replace('%ApellidoContacto%', $NombreContacto[1], $html2);
					}
				} else if (method_exists('Conf', 'TituloContacto')) {
					if (Conf::TituloContacto()) {
						$html2 = str_replace('%sr%', __($contrato->fields['titulo_contacto']), $html2);
						$html2 = str_replace('%NombrePilaContacto%', $contrato->fields['contacto'], $html2);
						$html2 = str_replace('%ApellidoContacto%', $contrato->fields['apellido_contacto'], $html2);
					} else {
						$html2 = str_replace('%sr%', __('Señor'), $html2);
						$NombreContacto = explode(' ', $contrato->fields['contacto']);
						$html2 = str_replace('%NombrePilaContacto%', $NombreContacto[0], $html2);
						$html2 = str_replace('%ApellidoContacto%', $NombreContacto[1], $html2);
					}
				} else {
					$html2 = str_replace('%sr%', __('Señor'), $html2);
					$NombreContacto = explode(' ', $contrato->fields['contacto']);
					$html2 = str_replace('%NombrePilaContacto%', $NombreContacto[0], $html2);
					$html2 = str_replace('%ApellidoContacto%', $NombreContacto[1], $html2);
				}
				$html2 = str_replace('%glosa_cliente%', $contrato->fields['factura_razon_social'], $html2);
				if (strtolower($contrato->fields['titulo_contacto']) == 'sra.' || strtolower($contrato->fields['titulo_contacto']) == 'srta.')
					$html2 = str_replace('%estimado%', __('Estimada'), $html2);
				else
					$html2 = str_replace('%estimado%', __('Estimado'), $html2);

				#carta gallo barrios pickman (gbplegal)
				if ($cobro_moneda->moneda[$this->fields['opc_moneda_total']]['codigo'] == 'USD') {
					$html2 = str_replace('%cta_cte_gbp_segun_moneda%', __('194-1861108179'), $html2);
					$html2 = str_replace('%tipo_gbp_segun_moneda%', __('Extranjera'), $html2);
				} else {
					$html2 = str_replace('%cta_cte_gbp_segun_moneda%', __('194-1847085-0-23'), $html2);
					$html2 = str_replace('%tipo_gbp_segun_moneda%', __('Nacional'), $html2);
				}

				/* valor porcentaje de impuesto */
				$html2 = str_replace('%porcentaje_impuesto%', (int) ($this->fields['porcentaje_impuesto']) . '%', $html2);
				$html2 = str_replace('%porcentaje_impuesto_sin_simbolo%', (int) ($this->fields['porcentaje_impuesto']), $html2);

				/*
				  Total Gastos
				  se suma cuando idioma es inglés
				  se presenta separadamente cuando es en español
				 */
				$total_gastos = 0;
				$total_gastos_balance = 0;
				$query = "SELECT SQL_CALC_FOUND_ROWS *
									FROM cta_corriente
									WHERE id_cobro='" . $this->fields['id_cobro'] . "' AND (egreso > 0 OR ingreso > 0)
									ORDER BY fecha ASC";
				$lista_gastos = new ListaGastos($this->sesion, '', $query);

				for ($i = 0; $i < $lista_gastos->num; $i++) {
					$gasto = $lista_gastos->Get($i);

					if ($gasto->fields['egreso'] > 0)
						$saldo = $gasto->fields['monto_cobrable'];
					elseif ($gasto->fields['ingreso'] > 0)
						$saldo = -$gasto->fields['monto_cobrable'];

					if (substr($gasto->fields['descripcion'], 0, 19) != "Saldo aprovisionado") {
						$saldo_balance = $saldo;
					} else {
						$saldo_balance = 0;
					}

					$monto_gasto = $saldo;
					$saldo_moneda_total = $saldo * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']);
					$saldo_balance_moneda_total = $saldo_balance * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']);
					//$total_gastos += $saldo_moneda_total;
					$total_gastos_balance += $saldo_balance_moneda_total;
					$total_gastos = $this->fields['monto_gastos'];
				}

				/*
				 * INICIO - CARTA GASTOS DE VFCabogados, 2011-03-04
				 */
				if (method_exists('Conf', 'ValorSinEspacio') && Conf::ValorSinEspacio()) {
					$html2 = str_replace('%subtotal_gastos_solo_provision%', $moneda_total->fields['simbolo'] . number_format(abs($x_cobro_gastos['subtotal_gastos_solo_provision']), $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . '.-', $html2); // en la carta se especifica que el monto debe aparecer como positivo
					$html2 = str_replace('%subtotal_gastos_sin_provision%', $moneda_total->fields['simbolo'] . number_format($x_cobro_gastos['subtotal_gastos_sin_provision'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . '.-', $html2); // en la carta se especifica que el monto debe aparecer como positivo
					$html2 = str_replace('%subtotal_gastos_diff_con_sin_provision%', $moneda_total->fields['simbolo'] . number_format($x_cobro_gastos['gasto_total'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . '.-', $html2); // en la carta se especifica que el monto debe aparecer como positivo
					$html2 = str_replace('%monto_gastos_con_iva%', $moneda_total->fields['simbolo'] . number_format($x_cobro_gastos['subtotal_gastos_con_impuestos'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ',-', $html2);
					$html2 = str_replace('%monto_gastos_sin_iva%', $moneda_total->fields['simbolo'] . number_format($x_cobro_gastos['subtotal_gastos_sin_impuestos'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ',-', $html2);
					$html2 = str_replace('%saldo_gastos_balance%', $moneda_total->fields['simbolo'] . number_format($total_gastos_balance, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ',-', $html2);
				} else {
					$html2 = str_replace('%subtotal_gastos_solo_provision%', $moneda_total->fields['simbolo'] . ' ' . number_format(abs($x_cobro_gastos['subtotal_gastos_solo_provision']), $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . '.-', $html2); // en la carta se especifica que el monto debe aparecer como positivo
					$html2 = str_replace('%subtotal_gastos_sin_provision%', $moneda_total->fields['simbolo'] . ' ' . number_format($x_cobro_gastos['subtotal_gastos_sin_provision'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . '.-', $html2); // en la carta se especifica que el monto debe aparecer como positivo
					$html2 = str_replace('%subtotal_gastos_diff_con_sin_provision%', $moneda_total->fields['simbolo'] . ' ' . number_format($x_cobro_gastos['gasto_total'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . '.-', $html2); // en la carta se especifica que el monto debe aparecer como positivo
					$html2 = str_replace('%saldo_gastos_balance%', $moneda_total->fields['simbolo'] . ' ' . number_format($total_gastos_balance, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ',-', $html2);
					$html2 = str_replace('%monto_gastos_con_iva%', $moneda_total->fields['simbolo'] . ' ' . number_format($x_cobro_gastos['subtotal_gastos_con_impuestos'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ',-', $html2);
					$html2 = str_replace('%monto_gastos_sin_iva%', $moneda_total->fields['simbolo'] . ' ' . number_format($x_cobro_gastos['subtotal_gastos_sin_impuestos'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ',-', $html2);
				}

				/*
				 * FIN - CARTA GASTOS DE VFCabogados, 2011-03-04
				 */

				/* MONTOS SEGUN MONEDA TOTAL IMPRESION */
				$aproximacion_monto = number_format($this->fields['monto'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
				$monto_moneda = ((double) $aproximacion_monto * (double) $this->fields['tipo_cambio_moneda']) / ($tipo_cambio_moneda_total > 0 ? $tipo_cambio_moneda_total : $moneda_total->fields['tipo_cambio']);
				$monto_moneda_sin_gasto = ((double) $aproximacion_monto * (double) $this->fields['tipo_cambio_moneda']) / ($tipo_cambio_moneda_total > 0 ? $tipo_cambio_moneda_total : $moneda_total->fields['tipo_cambio']);
				$monto_moneda_con_gasto = ((double) $aproximacion_monto * (double) $this->fields['tipo_cambio_moneda']) / ($tipo_cambio_moneda_total > 0 ? $tipo_cambio_moneda_total : $moneda_total->fields['tipo_cambio']);

				$monto_moneda_con_gasto = $x_resultados['monto'][$this->fields['opc_moneda_total']];
				$monto_moneda_sin_gasto = $x_resultados['monto'][$this->fields['opc_moneda_total']];

				//Caso retainer menor de un valor y distinta tarifa (diferencia por decimales)
				if ((($this->fields['total_minutos'] / 60) < $this->fields['retainer_horas']) && ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') && $this->fields['id_moneda'] != $this->fields['id_moneda_monto']) {
					//$monto_moneda_con_gasto = ((double)$this->fields['monto']*(double)$this->fields['tipo_cambio_moneda'])/($tipo_cambio_moneda_total > 0 ? $tipo_cambio_moneda_total : $moneda_total->fields['tipo_cambio']);
					$monto_moneda_con_gasto = $x_resultados['monto'][$this->fields['opc_moneda_total']];
				}
				$query = "SELECT SUM( TIME_TO_SEC( duracion_cobrada )/3600 )
										FROM trabajo
									 WHERE id_cobro = '" . $this->fields['id_cobro'] . "' ";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				list($duracion_trabajos) = mysql_fetch_array($resp);
				$html2 = str_replace('%duracion_trabajos%', number_format($duracion_trabajos, 2, ',', ''), $html2);
				//Caso flat fee
				if ($this->fields['forma_cobro'] == 'FLAT FEE' && $this->fields['id_moneda'] != $this->fields['id_moneda_monto'] && $this->fields['id_moneda_monto'] == $this->fields['opc_moneda_total']) {
					$monto_moneda = $this->fields['monto_contrato'];
					$monto_moneda_con_gasto = $this->fields['monto_contrato'];
					$monto_moneda_sin_gasto = $this->fields['monto_contrato'];
				}

				//Caso cap menor de un valor y distinta tarifa (diferencia por decimales)
				/* if($this->fields['forma_cobro']=='CAP' && $this->fields['monto_subtotal'] > $this->fields['monto'] && $this->fields['id_moneda']!=$this->fields['id_moneda_monto'] && $this->fields['opc_moneda_total']==$this->fields['id_moneda_monto'])
				  {
				  $monto_moneda_con_gasto = $this->fields['monto_contrato'];
				  } */

				/* MONTOS SEGUN MONEDA CLIENTE *//*
				  $monto_moneda = ((double)$this->fields['monto']*(double)$this->fields['tipo_cambio_moneda'])/($moneda_cliente_cambio > 0 ? $moneda_cliente_cambio : $moneda_cli->fields['tipo_cambio']);
				  $monto_moneda_sin_gasto = ((double)$this->fields['monto']*(double)$this->fields['tipo_cambio_moneda'])/($moneda_cliente_cambio > 0 ? $moneda_cliente_cambio : $moneda_cli->fields['tipo_cambio']);
				  $monto_moneda_con_gasto = ((double)$this->fields['monto']*(double)$this->fields['tipo_cambio_moneda'])/($moneda_cliente_cambio > 0 ? $moneda_cliente_cambio : $moneda_cli->fields['tipo_cambio']);
				 */
				$monto_moneda_con_gasto += $total_gastos;
				if ($lang != 'es')
					$monto_moneda += $total_gastos;
				if ($total_gastos > 0) {
					 
						$html2 = str_replace('%monto_gasto%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($total_gastos, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
				}	else {
					 
						$html2 = str_replace('%monto_gasto%', $moneda_total->fields['simbolo'] . $this->espacio . number_format(0, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
				}
				$html2 = str_replace('%saldo_gasto_facturado%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($this->ArrayTotalesDelContrato[$this->fields['id_cobro']]['saldo_gastos'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
				$html2 = str_replace('%saldo_gasto_facturado_moneda_base%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($this->ArrayTotalesDelContrato[$this->fields['id_cobro']]['saldo_gastos_moneda'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
				#Fechas periodo
				$datefrom = strtotime($this->fields['fecha_ini'], 0);
				$dateto = strtotime($this->fields['fecha_fin'], 0);
				$difference = $dateto - $datefrom; //Dif segundos
				$months_difference = floor($difference / 2678400);
				while (mktime(date("H", $datefrom), date("i", $datefrom), date("s", $datefrom), date("n", $datefrom) + ($months_difference), date("j", $dateto), date("Y", $datefrom)) < $dateto) {
					$months_difference++;
				}

				$datediff = $months_difference;

				/*
				  Mostrando fecha según idioma
				 */
				if ($this->fields['fecha_ini'] != '' && $this->fields['fecha_ini'] != '0000-00-00') {
					$texto_fecha_es = __('entre los meses de') . ' ' . ucfirst(Utiles::sql3fecha($this->fields['fecha_ini'], '%B %Y')) . ' ' . __('y') . ' ' . ucfirst(Utiles::sql3fecha($this->fields['fecha_fin'], '%B %Y'));
					$texto_fecha_es_durante = __('durante los meses de') . ' ' . ucfirst(Utiles::sql3fecha($this->fields['fecha_ini'], '%B %Y')) . ' ' . __('y') . ' ' . ucfirst(Utiles::sql3fecha($this->fields['fecha_fin'], '%B %Y'));
					$texto_fecha_en = __('between') . ' ' . ucfirst(date('F Y', strtotime($this->fields['fecha_ini']))) . ' ' . __('and') . ' ' . ucfirst(date('F Y', strtotime($this->fields['fecha_fin'])));
				} else {
					$texto_fecha_es = __('hasta el mes de') . ' ' . ucfirst(ucfirst(Utiles::sql3fecha($this->fields['fecha_fin'], '%B %Y')));
					$texto_fecha_es_durante = __('hasta el mes de') . ' ' . ucfirst(ucfirst(Utiles::sql3fecha($this->fields['fecha_fin'], '%B %Y')));
					$texto_fecha_en = __('until') . ' ' . ucfirst(date('F Y', strtotime($this->fields['fecha_fin'])));
				}

				if ($lang == 'es') {

					$fecha_mes = $datediff > 0 && $datediff < 12 ? $texto_fecha_es : __('realizados el mes de') . ' ' . ucfirst(Utiles::sql3fecha($this->fields['fecha_fin'], '%B'));
					$fecha_diff = $datediff > 0 && $datediff < 12 ? $texto_fecha_es : __('durante el mes de') . ' ' . ucfirst(Utiles::sql3fecha($this->fields['fecha_fin'], '%B %Y'));
					$fecha_al = $datediff > 0 && $datediff < 12 ? $texto_fecha_es : __('al mes de') . ' ' . ucfirst(Utiles::sql3fecha($this->fields['fecha_fin'], '%B %Y'));
					$fecha_diff_con_de = $datediff > 0 && $datediff < 12 ? $texto_fecha_es : __('durante el mes de') . ' ' . ucfirst(Utiles::sql3fecha($this->fields['fecha_fin'], '%B de %Y'));
					$fecha_diff_prestada = $datediff > 0 && $datediff < 12 ? __('prestada ') . $texto_fecha_es : __('prestada en el mes de') . ' ' . ucfirst(Utiles::sql3fecha($this->fields['fecha_fin'], '%B %Y'));
					$fecha_diff_prestada_durante = $datediff > 0 && $datediff < 12 ? $texto_fecha_es_durante : __('prestados durante el mes de') . ' ' . ucfirst(Utiles::sql3fecha($this->fields['fecha_fin'], '%B %Y'));
				} else {
					$fecha_diff = $datediff > 0 && $datediff < 12 ? $texto_fecha_en : __('during') . ' ' . ucfirst(date('F Y', strtotime($this->fields['fecha_fin'])));
					$fecha_al = $datediff > 0 && $datediff < 12 ? $texto_fecha_en : __('to') . ' ' . ucfirst(date('F Y', strtotime($this->fields['fecha_fin'])));
					$fecha_diff_prestada = $datediff > 0 && $datediff < 12 ? $texto_fecha_en : __('during') . ' ' . ucfirst(date('F Y', strtotime($this->fields['fecha_fin'])));
					$fecha_diff_prestada_durante = $datediff > 0 && $datediff < 12 ? $texto_fecha_en : __('during') . ' ' . ucfirst(date('F Y', strtotime($this->fields['fecha_fin'])));
					$fecha_diff_con_de = $datediff > 0 && $datediff < 12 ? $texto_fecha_es : __('during the month of') . ' ' . ucfirst(Utiles::sql3fecha($this->fields['fecha_fin'], '%B de %Y'));
				}

				if (( $fecha_diff == 'durante el mes de No existe fecha' || $fecha_diff == 'hasta el mes de No existe fecha' ) && $lang == 'es') {
					$fecha_diff = __('durante el mes de') . ' ' . ucfirst(Utiles::sql3fecha(date('Y-m-d'), '%B %Y'));
					$fecha_al = __('al mes de') . ' ' . ucfirst(Utiles::sql3fecha(date('Y-m-d'), '%B %Y'));
					$fecha_diff_con_de = __('durante el mes de') . ' ' . ucfirst(Utiles::sql3fecha(date('Y-m-d'), '%B de %Y'));
					$fecha_diff_prestada = __('prestada en el mes de') . ' ' . ucfirst(Utiles::sql3fecha(date('Y-m-d'), '%B de %Y'));
					$fecha_diff_prestada_durante = __('prestados durante el mes de') . ' ' . ucfirst(Utiles::sql3fecha(date('Y-m-d'), '%B %Y'));
				}

				//Se saca la fecha inicial según el primer trabajo
				//esto es especial para LyR
				$query = "SELECT fecha FROM trabajo WHERE id_cobro='" . $this->fields['id_cobro'] . "' AND visible='1' ORDER BY fecha LIMIT 1";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				//acá se calcula si hay trabajos o no (porque si no sale como fecha 1969)
				if (mysql_num_rows($resp) > 0)
					list($fecha_primer_trabajo) = mysql_fetch_array($resp);
				else
					$fecha_primer_trabajo = $this->fields['fecha_fin'];

				//También se saca la fecha final según el último trabajo
				$query = "SELECT LAST_DAY(fecha) FROM trabajo WHERE id_cobro='" . $this->fields['id_cobro'] . "' AND visible='1' ORDER BY fecha DESC LIMIT 1";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				//acá se calcula si hay trabajos o no (porque si no sale como fecha 1969)
				if (mysql_num_rows($resp) > 0)
					list($fecha_ultimo_trabajo) = mysql_fetch_array($resp);
				else
					$fecha_ultimo_trabajo = $this->fields['fecha_fin'];
				$fecha_inicial_primer_trabajo = date('Y-m-01', strtotime($fecha_primer_trabajo));
				$fecha_final_ultimo_trabajo = date('Y-m-d', strtotime($fecha_ultimo_trabajo));

				$datefrom = strtotime($fecha_inicial_primer_trabajo, 0);
				$dateto = strtotime($fecha_final_ultimo_trabajo, 0);
				$difference = $dateto - $datefrom; //Dif segundos
				$months_difference = floor($difference / 2678400);
				while (mktime(date("H", $datefrom), date("i", $datefrom), date("s", $datefrom), date("n", $datefrom) + ($months_difference), date("j", $dateto), date("Y", $datefrom)) < $dateto) {
					$months_difference++;
				}

				$datediff = $months_difference;

				$asuntos_doc = '';
				for ($k = 0; $k < count($this->asuntos); $k++) {
					$asunto = new Asunto($this->sesion);
					$asunto->LoadByCodigo($this->asuntos[$k]);
					$espace = $k < count($this->asuntos) - 1 ? ', ' : '';
					$asuntos_doc .= $asunto->fields['glosa_asunto'] . '' . $espace;
					$codigo_asunto .= $asunto->fields['codigo_asunto'] . '' . $espace;
				}
				$html2 = str_replace('%Asunto%', $asuntos_doc, $html2);
				$asunto_ucwords = ucwords(strtolower($asuntos_doc));
				$html2 = str_replace('%Asunto_ucwords%', $asunto_ucwords, $html2);

				/*
				  Mostrando fecha según idioma
				 */
				if ($fecha_inicial_primer_trabajo != '' && $fecha_inicial_primer_trabajo != '0000-00-00') {
					if ($lang == 'es') {
						$fecha_diff_periodo_exacto = __('desde el día') . ' ' . date("d-m-Y", strtotime($fecha_primer_trabajo)) . ' ';
					} else {
						$fecha_diff_periodo_exacto = __('from') . ' ' . date("d-m-Y", strtotime($fecha_primer_trabajo)) . ' ';
					}

					if (Utiles::sql3fecha($fecha_inicial_primer_trabajo, '%Y') == Utiles::sql3fecha($this->fields['fecha_fin'], '%Y')) {
						$texto_fecha_es = __('entre los meses de') . ' ' . ucfirst(Utiles::sql3fecha($fecha_inicial_primer_trabajo, '%B')) . ' ' . __('y') . ' ' . ucfirst(Utiles::sql3fecha($fecha_final_ultimo_trabajo, '%B %Y'));
						$texto_fecha_es_de = __('entre los meses de') . ' ' . ucfirst(Utiles::sql3fecha($fecha_inicial_primer_trabajo, '%B')) . ' ' . __('y') . ' ' . ucfirst(Utiles::sql3fecha($fecha_final_ultimo_trabajo, '%B de %Y'));
						$texto_fecha_es_durante = __('prestados durante los meses de') . ' ' . ucfirst(Utiles::sql3fecha($fecha_inicial_primer_trabajo, '%B')) . ' ' . __('y') . ' ' . ucfirst(Utiles::sql3fecha($fecha_final_ultimo_trabajo, '%B de %Y'));
					} else {
						$texto_fecha_es = __('entre los meses de') . ' ' . ucfirst(Utiles::sql3fecha($fecha_inicial_primer_trabajo, '%B %Y')) . ' ' . __('y') . ' ' . ucfirst(Utiles::sql3fecha($fecha_final_ultimo_trabajo, '%B %Y'));
						$texto_fecha_es_de = __('entre los meses de') . ' ' . ucfirst(Utiles::sql3fecha($fecha_inicial_primer_trabajo, '%B %Y')) . ' ' . __('y') . ' ' . ucfirst(Utiles::sql3fecha($fecha_final_ultimo_trabajo, '%B de %Y'));
						$texto_fecha_es_durante = __('prestados durante los meses de') . ' ' . ucfirst(Utiles::sql3fecha($fecha_inicial_primer_trabajo, '%B %Y')) . ' ' . __('y') . ' ' . ucfirst(Utiles::sql3fecha($fecha_final_ultimo_trabajo, '%B de %Y'));
					}
				} else {
					$texto_fecha_es = __('hasta el mes de') . ' ' . ucfirst(ucfirst(Utiles::sql3fecha($fecha_final_ultimo_trabajo, '%B %Y')));
					$texto_fecha_es_de = __('hasta el mes de') . ' ' . ucfirst(ucfirst(Utiles::sql3fecha($fecha_final_ultimo_trabajo, '%B de %Y')));
					$texto_fecha_es_durante = __('hasta el mes de') . ' ' . ucfirst(ucfirst(Utiles::sql3fecha($fecha_final_ultimo_trabajo, '%B de %Y')));
				}

				if ($lang == 'es') {
					$fecha_diff_periodo_exacto .= __('hasta el día') . ' ' . Utiles::sql3fecha($this->fields['fecha_fin'], '%d-%m-%Y');
				} else {
					$fecha_diff_periodo_exacto .= __('until') . ' ' . Utiles::sql3fecha($this->fields['fecha_fin'], '%d-%m-%Y');
				}

				if ($fecha_inicial_primer_trabajo != '' && $fecha_inicial_primer_trabajo != '0000-00-00') {
					if (Utiles::sql3fecha($fecha_inicial_primer_trabajo, '%Y') == Utiles::sql3fecha($fecha_final_ultimo_trabajo, '%Y')) {
						$texto_fecha_en = __('between') . ' ' . ucfirst(date('F', strtotime($fecha_inicial_primer_trabajo))) . ' ' . __('and') . ' ' . ucfirst(date('F Y', strtotime($fecha_final_ultimo_trabajo)));
					} else {
						$texto_fecha_en = __('between') . ' ' . ucfirst(date('F Y', strtotime($fecha_inicial_primer_trabajo))) . ' ' . __('and') . ' ' . ucfirst(date('F Y', strtotime($fecha_final_ultimo_trabajo)));
					}
				} else {
					$texto_fecha_en = __('until') . ' ' . ucfirst(date('F Y', strtotime($fecha_final_ultimo_trabajo)));
				}

				if ($lang == 'es') {
					$fecha_primer_trabajo = $datediff > 0 && $datediff < 48 ? $texto_fecha_es : __('durante el mes de') . ' ' . ucfirst(Utiles::sql3fecha($fecha_final_ultimo_trabajo, '%B %Y'));
					$fecha_primer_trabajo_de = $datediff > 0 && $datediff < 48 ? $texto_fecha_es_de : __('durante el mes de') . ' ' . ucfirst(Utiles::sql3fecha($fecha_final_ultimo_trabajo, '%B de %Y'));
					$fecha_primer_trabajo_durante = $datediff > 0 && $datediff < 48 ? $texto_fecha_es_de : __(' prestados durante el mes de') . ' ' . ucfirst(Utiles::sql3fecha($fecha_final_ultimo_trabajo, '%B de %Y'));
				} else {
					$fecha_primer_trabajo = $datediff > 0 && $datediff < 48 ? $texto_fecha_en : __('during') . ' ' . ucfirst(date('F Y', strtotime($fecha_final_ultimo_trabajo)));
					$fecha_primer_trabajo_de = $datediff > 0 && $datediff < 48 ? $texto_fecha_en : __('during') . ' ' . ucfirst(date('F Y', strtotime($fecha_final_ultimo_trabajo)));
					$fecha_primer_trabajo_durante = $datediff > 0 && $datediff < 48 ? $texto_fecha_en : __('during') . ' ' . ucfirst(date('F Y', strtotime($fecha_final_ultimo_trabajo)));
				}

				if ($fecha_primer_trabajo == 'No existe fecha' && $lang == es) {
					$fecha_primer_trabajo = ucfirst(Utiles::sql3fecha(date('Y-m-d'), '%B %Y'));
					$fecha_primer_trabajo_de = ucfirst(Utiles::sql3fecha(date('Y-m-d'), '%B %Y'));
					$fecha_primer_trabajo_durante = ucfirst(Utiles::sql3fecha(date('Y-m-d'), '%B %Y'));
				}

				if ($this->fields['id_moneda'] != $this->fields['opc_moneda_total']) {
					$html2 = str_replace('%equivalente_dolm%', ' que ascienden a %monto%', $html2);
				} else {
					$html2 = str_replace('%equivalente_dolm%', '', $html2);
				}
				$html2 = str_replace('%num_factura%', $this->fields['documento'], $html2);
				$html2 = str_replace('%n_num_factura%', 'N°' . $this->fields['documento'], $html2);
				$html2 = str_replace('%fecha_primer_trabajo%', $fecha_primer_trabajo, $html2);
				$html2 = str_replace('%fecha_primer_trabajo_de%', $fecha_primer_trabajo_de, $html2);
				$html2 = str_replace('%fecha_primer_trabajo_durante%', $fecha_primer_trabajo_durante, $html2);
				$html2 = str_replace('%fecha%', $fecha_diff, $html2);


				/* fecha PEB */ $html2 = str_replace('%fecha_mes%', $fecha_mes, $html2);

				if (method_exists('Conf', 'GetConf')) {
					if ($lang == 'es') {
						$fecha_lang = Conf::GetConf($this->sesion, 'CiudadEstudio') . ', ' . ucfirst(Utiles::sql3fecha(date('Y-m-d'), '%e de %B de %Y'));
					} else {
						$fecha_lang = Conf::GetConf($this->sesion, 'CiudadEstudio') . ' (' . Conf::GetConf($this->sesion, 'PaisEstudio') . '), ' . ucfirst(Utiles::sql3fecha(date('Y-m-d'), '%e de %B de %Y'));
					}
				} else {
					if ($lang == 'es') {
						$fecha_lang = 'Santiago, ' . ucfirst(Utiles::sql3fecha(date('Y-m-d'), '%e de %B de %Y'));
					} else {
						$fecha_lang = 'Santiago (Chile), ' . date('F d, Y');
					}
				}
				$fecha_espanol = ucfirst(Utiles::sql3fecha(date('Y-m-d'), '%e de %B de %Y'));

				$html2 = str_replace('%fecha_especial%', $fecha_lang, $html2);
				$fecha_lang_mta = 'Bogotá, D.C.,' . ucfirst(Utiles::sql3fecha(date('Y-m-d'), '%e de %B de %Y'));
				$actual_locale = setlocale(LC_ALL, 0);
				$fecha_lang_mta_en = (setlocale(LC_ALL, 'en_US.UTF-8')) ? "Bogotá, " . strftime(Utiles::FormatoStrfTime("%B %e, %Y")) : $fecha_lang_mta;
				setlocale(LC_ALL, "$actual_locale");
				$html2 = str_replace('%fecha_especial_mta%', $fecha_lang_mta, $html2);
				$html2 = str_replace('%fecha_especial_mta_en%', $fecha_lang_mta_en, $html2);

				$html2 = str_replace('%fecha_al%', $fecha_al, $html2);
				$html2 = str_replace('%fecha_al_minuscula%', strtolower($fecha_al), $html2);
				$html2 = str_replace('%fecha_con_de%', $fecha_diff_con_de, $html2);
				$html2 = str_replace('%fecha_con_prestada%', $fecha_diff_prestada, $html2);
				$html2 = str_replace('%fecha_con_prestada_mayuscula%', mb_strtoupper($fecha_diff_prestada), $html2);
				$html2 = str_replace('%fecha_con_prestada_minusculas%', strtolower($fecha_diff_prestada), $html2);
				$html2 = str_replace('%fecha_diff_prestada_durante%', $fecha_diff_prestada_durante, $html2);
				$html2 = str_replace('%fecha_diff_prestada_durante_mayuscula%', mb_strtoupper($fecha_diff_prestada_durante), $html2);
				$html2 = str_replace('%fecha_diff_prestada_durante_minusculas%', strtolower($fecha_diff_prestada_durante), $html2);
				$html2 = str_replace('%fecha_emision%', $this->fields['fecha_emision'] ? Utiles::sql2fecha($this->fields['fecha_emision'], '%d de %B') : '', $html2);

				$fecha_mta_emision = $this->fields['fecha_emision'] ? Utiles::sql2fecha($this->fields['fecha_emision'], '%d/%m/%Y') : '';
				$fecha_mta_facturacion = $this->fields['fecha_facturacion'] ? Utiles::sql2fecha($this->fields['fecha_facturacion'], '%d/%m/%Y') : $fecha_mta_emision;
				list($fecha_mta_dia, $fecha_mta_mes, $fecha_mta_agno) = explode("/", $fecha_mta_facturacion);

				$html2 = str_replace('%fecha_mta%', $fecha_mta_facturacion, $html2);
				$html2 = str_replace('%fecha_mta_dia%', $fecha_mta_dia, $html2);
				$html2 = str_replace('%fecha_mta_mes%', $fecha_mta_mes, $html2);
				$html2 = str_replace('%fecha_mta_agno%', $fecha_mta_agno, $html2);

				$fecha_facturacion_carta = $this->fields['fecha_facturacion'] ? Utiles::fecha2sql($this->fields['fecha_facturacion'], '%d de %B de %Y') : $fecha_facturacion_carta;
				$html2 = str_replace('%monto_total_demo_uf%', number_format($monto_moneda_demo, $cobro_moneda->moneda[3]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ' ' . $cobro_moneda->moneda[3]['simbolo'], $html2);
				$html2 = str_replace('%fecha_facturacion%', $fecha_facturacion_carta, $html2);
				$html2 = str_replace('%monto_total_demo_jdf%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . number_format($x_resultados['monto_total_cobro'][$this->fields['opc_moneda_total']], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);

				$html2 = str_replace('%fecha_periodo_exacto%', $fecha_diff_periodo_exacto, $html2);
				$fecha_dia_carta = ucfirst(Utiles::sql3fecha(date('Y-m-d'), '%d de %B de %Y'));
				$html2 = str_replace('%fecha_dia_carta%', $fecha_dia_carta, $html2);
				if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'ValorSinEspacio') ) || ( method_exists('Conf', 'ValorSinEspacio') && Conf::ValorSinEspacio() )))
					$html2 = str_replace('%monto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . number_format($monto_moneda, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
				else
					$html2 = str_replace('%monto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . ' ' . number_format($monto_moneda, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
				$html2 = str_replace('%monto_solo_gastos%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . ' ' . number_format($gasto_en_pesos, 0, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
				if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'ValorSinEspacio') ) || ( method_exists('Conf', 'ValorSinEspacio') && Conf::ValorSinEspacio() )))
					$html2 = str_replace('%monto_sin_gasto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . number_format($monto_moneda_sin_gasto, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
				else
					$html2 = str_replace('%monto_sin_gasto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . ' ' . number_format($monto_moneda_sin_gasto, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);

				if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'ValorSinEspacio') ) || ( method_exists('Conf', 'ValorSinEspacio') && Conf::ValorSinEspacio() ))) {
					$html2 = str_replace('%monto_total_demo%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . number_format($x_resultados['monto_total_cobro'][$this->fields['opc_moneda_total']], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
					$html2 = str_replace('%monto_con_gasto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . number_format($monto_moneda_con_gasto, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
					$html2 = str_replace('%monto_original%', $moneda->fields['simbolo'] . number_format($this->fields['monto'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
					$html2 = str_replace('%monto_total_sin_iva%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . number_format($x_resultados['monto_cobro_original'][$this->fields['opc_moneda_total']], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
					$html2 = str_replace('%monto_iva%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . number_format(( $x_resultados['monto_total_cobro'][$this->fields['opc_moneda_total']] - $x_resultados['monto_cobro_original'][$this->fields['opc_moneda_total']]), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
				} else {
					$html2 = str_replace('%monto_total_demo%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . number_format($x_resultados['monto_total_cobro'][$this->fields['opc_moneda_total']], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
					$html2 = str_replace('%monto_con_gasto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . ' ' . number_format($monto_moneda_con_gasto, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
					$html2 = str_replace('%monto_original%', $moneda->fields['simbolo'] . ' ' . number_format($this->fields['monto'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
					$html2 = str_replace('%monto_total_sin_iva%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . ' ' . number_format($x_resultados['monto_cobro_original'][$this->fields['opc_moneda_total']], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
					$html2 = str_replace('%monto_iva%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . ' ' . number_format(( $x_resultados['monto_total_cobro'][$this->fields['opc_moneda_total']] - $x_resultados['monto_cobro_original'][$this->fields['opc_moneda_total']]), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
				}

				$html2 = str_replace('%porcentaje_iva_con_simbolo%', $this->fields['porcentaje_impuesto'] . "%", $html2);
				$monto_palabra = new MontoEnPalabra($this->sesion);

				//$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo']
				$glosa_moneda_lang = __($cobro_moneda->moneda[$this->fields['opc_moneda_total']]['glosa_moneda']);
				$glosa_moneda_plural_lang = __($cobro_moneda->moneda[$this->fields['opc_moneda_total']]['glosa_moneda_plural']);
				$cobro_id_moneda = $this->fields['opc_moneda_total'];

				$total_mta = number_format($x_resultados['monto_total_cobro'][$this->fields['opc_moneda_total']], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], '.', '');
				$decimales_monto = number_format(($total_mta - (int) $total_mta), 2);
				$decimales_monto = number_format(($decimales_monto * 100), 0);
				$monto_total_palabra = Numbers_Words::toWords((int) $total_mta, "es") . ' ' . ( ( $total_mta > 1 ) ? __("$glosa_moneda_plural_lang") : __("$glosa_moneda_lang") ) . ( ($decimales_monto > 0 ) ? " con $decimales_monto/100" : '' );
				//$monto_total_palabra = strtoupper($monto_palabra->ValorEnLetras($total_mta, $cobro_id_moneda, $glosa_moneda_lang, $glosa_moneda_plural_lang));
				$monto_total_palabra_en = Numbers_Words::toWords((int) $total_mta, "en_US") . ' ' . ( ( $total_mta > 1 ) ? __("$glosa_moneda_plural_lang") : __("$glosa_moneda_lang") ) . ( ($decimales_monto > 0 ) ? " and $decimales_monto/100" : '' );
				$cambio_monedas_texto_en = array(
					'dólar' => 'dollar', 'Dólar' => 'Dolar', 'DÓLAR' => 'DOLLAR',
					'dólares' => 'dollars', 'Dólares' => 'Dollars', 'DÓLARES' => 'DOLLARS',
					'libra' => 'pound', 'Libra' => 'Pound', 'LIBRA' => 'POUND',
					'libras' => 'pounds', 'Libras' => 'Pounds', 'LIBRAS' => 'POUNDS'
				);
				$monto_total_palabra_en = strtr($monto_total_palabra_en, $cambio_monedas_texto_en);
				$html2 = str_replace('%monto_en_palabras%', __(strtoupper($monto_total_palabra)), $html2);
				$html2 = str_replace('%monto_en_palabras_en%', __(strtoupper($monto_total_palabra_en)), $html2);

				$moneda_opc_total = new Moneda($this->sesion);
				$moneda_opc_total->Load($this->fields['opc_moneda_total']);

				if ($x_resultados['monto_total_cobro'][$this->fields['opc_moneda_total']] > 0) {
					$html2 = str_replace('%frase_moneda%', __(strtolower($moneda_opc_total->fields['glosa_moneda_plural'])), $html2);
				} else {
					$html2 = str_replace('%frase_moneda%', __(strtolower($moneda_opc_total->fields['glosa_moneda'])), $html2);
				}

				if ($this->fields['opc_moneda_total'] != $this->fields['id_moneda'])
					$html2 = str_replace('%equivalente_a_baz%', ', equivalentes a ' . $moneda->fields['simbolo'] . ' ' . number_format($this->fields['monto'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
				else
					$html2 = str_replace('%equivalente_a_baz%', '', $html2);
				#Para montos solamente sea distinto a pesos $
				if ($this->fields['tipo_cambio_moneda_base'] <= 0)
					$tipo_cambio_moneda_base_cobro = 1;
				else
					$tipo_cambio_moneda_base_cobro = $this->fields['tipo_cambio_moneda_base'];

				$fecha_hasta_cobro = strftime(Utiles::FormatoStrfTime('%e de %B'), mktime(0, 0, 0, date("m", strtotime($this->fields['fecha_fin'])), date("d", strtotime($this->fields['fecha_fin'])), date("Y", strtotime($this->fields['fecha_fin']))));
				$html2 = str_replace('%fecha_hasta%', $fecha_hasta_cobro, $html2);
				if ($this->fields['id_moneda'] > 1 && $moneda_total->fields['id_moneda'] > 1) { #!= $moneda_cli->fields['id_moneda']
					$en_pesos = (double) $this->fields['monto'] * ($this->fields['tipo_cambio_moneda'] / $tipo_cambio_moneda_base_cobro);
					$html2 = str_replace('%monto_en_pesos%', __(', equivalentes a esta fecha a $ ') . number_format($en_pesos, 0, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . '.-', $html2);
				}
				else
					$html2 = str_replace('%monto_en_pesos%', '', $html2);

				#si hay gastos se muestran
				if ($total_gastos > 0) {
					#$gastos_moneda_total = ($totales['total']*($this->fields['tipo_cambio_moneda']/$tipo_cambio_cobro_moneda_base))/$tipo_cambio_moneda_total;
					#icc$gasto_en_pesos = ($total_gastos*($moneda_total->fields['tipo_cambio']/$tipo_cambio_moneda_base_cobro))/$tipo_cambio_moneda_total;#error gastos 1
					$gasto_en_pesos = $total_gastos;
					$txt_gasto = __("Asimismo, se agregan los gastos por la suma total de");
					$html2 = str_replace('%monto_gasto_separado%', $txt_gasto . ' $' . number_format($gasto_en_pesos, 0, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
				}
				else
					$html2 = str_replace('%monto_gasto_separado%', '', $html2);

				$query = "SELECT count(*) FROM cta_corriente WHERE id_cobro = '" . $this->fields['id_cobro'] . "'";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				list($cantidad_de_gastos) = mysql_fetch_array($resp);

				//echo 'simbolo: '.$moneda_total->fields['simbolo'].'<br>
				if (( $this->fields['monto_gastos'] > 0 || $cantidad_de_gastos > 0 ) && $this->fields['opc_ver_gastos']) {
					// Calculo especial para BAZ, en ves de mostrar el total de gastos, se muestra la cuenta corriente al día
					$where_gastos = " 1 ";
					$lista_asuntos = implode("','", $this->asuntos);
					if (!empty($lista_asuntos)) {
						$where_gastos .= " AND cta_corriente.codigo_asunto IN ('$lista_asuntos') ";
					}
					$where_gastos .= " AND cta_corriente.codigo_cliente = '" . $this->fields['codigo_cliente'] . "' ";
					$where_gastos .= " AND cta_corriente.fecha <= '" . $this->fields['fecha_fin'] . "' ";
					$cuenta_corriente_actual = number_format(UtilesApp::TotalCuentaCorriente($this->sesion, $where_gastos), $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);

					$html2 = str_replace('%frase_gastos_ingreso%', '<tr>
											    <td width="5%">&nbsp;</td>
											    <td align="left" class="detalle"><p>Adjunto a la presente encontrarás comprobantes de gastos realizados por cuenta de ustedes por la suma de ' . $cuenta_corriente_actual . '</p></td>
											    <td width="5%">&nbsp;</td>
											</tr>
											<tr>
											    <td>&nbsp;</td>
											    <td valign="top" align="left" class="detalle"><p>&nbsp;</p></td>
											</tr>', $html2);
					$html2 = str_replace('%frase_gastos_egreso%', '<tr>
											    <td width="5%">&nbsp;</td>
											    <td valign="top" align="left" class="detalle"><p>A mayor abundamiento, les recordamos que a esta fecha <u>existen cobros de notaría por la suma de $xxxxxx.-</u>, la que les agradeceré enviar en cheque nominativo a la orden de don Eduardo Avello Concha.</p></td>
											    <td width="5%">&nbsp;</td>
											</tr>
											<tr>
											    <td>&nbsp;</td>
											    <td valign="top" align="left" class="vacio"><p>&nbsp;</p></td>
											    <td>&nbsp;</td>
											</tr>', $html2);
				} else {
					$html2 = str_replace('%frase_gastos_ingreso%', '', $html2);
					$html2 = str_replace('%frase_gastos_egreso%', '', $html2);
				}
				if ($total_gastos > 0) {
					if (method_exists('Conf', 'ValorSinEspacio') && Conf::ValorSinEspacio())
						$html2 = str_replace('%monto_gasto%', $moneda_total->fields['simbolo'] . number_format($total_gastos, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
					else
						$html2 = str_replace('%monto_gasto%', $moneda_total->fields['simbolo'] . ' ' . number_format($total_gastos, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
				}
				else {
					if (method_exists('Conf', 'ValorSinEspacio') && Conf::ValorSinEspacio())
						$html2 = str_replace('%monto_gasto%', $moneda_total->fields['simbolo'] . number_format(0, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
					else
						$html2 = str_replace('%monto_gasto%', $moneda_total->fields['simbolo'] . ' ' . number_format(0, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
				}
				if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'ValorSinEspacio') ) || ( method_exists('Conf', 'ValorSinEspacio') && Conf::ValorSinEspacio() )))
					$html2 = str_replace('%monto_gasto_separado_baz%', $moneda_total->fields['simbolo'] . number_format($this->fields['saldo_final_gastos'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
				else
					$html2 = str_replace('%monto_gasto_separado_baz%', $moneda_total->fields['simbolo'] . ' ' . number_format($this->fields['saldo_final_gastos'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
				$html2 = str_replace('%num_letter%', $this->fields['id_cobro'], $html2);
				$html2 = str_replace('%num_letter_documento%', $this->fields['documento'], $html2);
				$html2 = str_replace('%num_letter_baz%', $this->fields['documento'], $html2);

				if (($this->fields['documento'] != '')) {
					$html2 = str_replace('%num_letter_rebaza%', __('la factura N°') . ' ' . $this->fields['documento'], $html2);

					$documentos_asociados = explode(",", $this->fields['documento']);
					if (sizeof($documentos_asociados) == 1) {
						if (substr(trim($documentos_asociados[0]), 0, 2) == 'FA') {
							$_doc_tmp = str_replace('FA', '', trim($documentos_asociados[0]));
							$html2 = str_replace('%num_letter_rebaza_especial%', __('la factura N°') . ' ' . $_doc_tmp, $html2);
						} else {
							$html2 = str_replace('%num_letter_rebaza_especial%', __('el cobro N°') . ' ' . $this->fields['id_cobro'], $html2);
						}
					} else if (sizeof($documentos_asociados) > 1) {
						$_documentos = array();
						foreach ($documentos_asociados as $key => $doc_tmp) {
							if (substr(trim($doc_tmp), 0, 2) == 'FA') {
								$_doc_tmp = str_replace('FA', '', trim($doc_tmp));

								$pos_anulada = stripos($_doc_tmp, "anula");
								if (!$pos_anulada) {
									$_documentos[] = $_doc_tmp;
								}
							}
						}

						if (sizeof($_documentos) > 0) {
							$html2 = str_replace('%num_letter_rebaza_especial%', __('las facturas N°') . ' ' . implode(", ", $_documentos), $html2);
						} else {
							$html2 = str_replace('%num_letter_rebaza_especial%', __('el cobro N°') . ' ' . $this->fields['id_cobro'], $html2);
						}
					} else {
						$html2 = str_replace('%num_letter_rebaza_especial%', __('el cobro N°') . ' ' . $this->fields['id_cobro'], $html2);
					}
				} else {
					$html2 = str_replace('%num_letter_rebaza%', __('el cobro N°') . ' ' . $this->fields['id_cobro'], $html2);
					$html2 = str_replace('%num_letter_rebaza_especial%', __('el cobro N°') . ' ' . $this->fields['id_cobro'], $html2);
				}

				# datos detalle carta mb y ebmo
				$html2 = str_replace('%si_gastos%', $total_gastos > 0 ? __('y reembolso de gastos') : '', $html2);
				if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'ValorSinEspacio') ) || ( method_exists('Conf', 'ValorSinEspacio') && Conf::ValorSinEspacio() )))
					$detalle_cuenta_honorarios = '(i) ' . $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . number_format($monto_moneda_sin_gasto, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ' ' . __('por concepto de honorarios');
				else
					$detalle_cuenta_honorarios = '(i) ' . $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . ' ' . number_format($monto_moneda_sin_gasto, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ' ' . __('por concepto de honorarios');
				if ($this->fields['id_moneda'] == 2 && $moneda_total->fields['id_moneda'] == 1) {
					$detalle_cuenta_honorarios .= ' (';
					if ($this->fields['forma_cobro'] == 'FLAT FEE')
						$detalle_cuenta_honorarios .= __('retainer ');
					if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'ValorSinEspacio') ) || ( method_exists('Conf', 'ValorSinEspacio') && Conf::ValorSinEspacio() )))
						$detalle_cuenta_honorarios .= __('equivalente en pesos a ') . $moneda->fields['simbolo'] . number_format($this->fields['monto'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
					else
						$detalle_cuenta_honorarios .= __('equivalente en pesos a ') . $moneda->fields['simbolo'] . ' ' . number_format($this->fields['monto'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
					$detalle_cuenta_honorarios .= __(', conforme al tipo de cambio observado del día de hoy') . ')';
					$detalle_cuenta_honorarios_primer_dia_mes = '';
					if ($this->fields['monto_subtotal'] > 0) {
						if ($this->fields['monto_gastos'] > 0) {
							if ($this->fields['monto'] == round($this->fields['monto']))
								$detalle_cuenta_honorarios_primer_dia_mes .= __('. Esta cantidad corresponde a') . __(' (i) ') . $moneda->fields['simbolo'] . number_format($this->fields['monto'], 0, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ' ' . __('por concepto de honorarios');
							else
								$detalle_cuenta_honorarios_primer_dia_mes .= __('. Esta cantidad corresponde a') . __(' (i) ') . $moneda->fields['simbolo'] . number_format($this->fields['monto'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ' ' . __('por concepto de honorarios');
						}
						else
							$detalle_cuenta_honorarios_primer_dia_mes .= ' ' . __('correspondiente a') . ' ' . $moneda->fields['simbolo'] . number_format($this->fields['monto'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ' ' . __('por concepto de honorarios');
						$detalle_cuenta_honorarios_primer_dia_mes .= ' ( ' . __('conforme a su equivalencia en peso según el Dólar Observado publicado por el Banco Central de Chile, el primer día hábil del presente mes') . ' )';
					}
				}
				if ($this->fields['id_moneda'] == 3 && $moneda_total->fields['id_moneda'] == 1) {
					$detalle_cuenta_honorarios .= ' (';
					if ($this->fields['forma_cobro'] == 'FLAT FEE')
						$detalle_cuenta_honorarios .= __('retainer ');
					if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'ValorSinEspacio') ) || ( method_exists('Conf', 'ValorSinEspacio') && Conf::ValorSinEspacio() )))
						$detalle_cuenta_honorarios .= $moneda->fields['simbolo'] . number_format($this->fields['monto'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
					else
						$detalle_cuenta_honorarios .= $moneda->fields['simbolo'] . ' ' . number_format($this->fields['monto'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
					$detalle_cuenta_honorarios .= __(', conforme a su equivalencia al ');
					$detalle_cuenta_honorarios .= $lang == 'es' ? Utiles::sql3fecha($this->fields['fecha_fin'], '%d de %B de %Y') : Utiles::sql3fecha($this->fields['fecha_fin'], '%m-%d-%Y');
					$detalle_cuenta_honorarios .= ')';
					$detalle_cuenta_honorarios_primer_dia_mes = '';
					if ($this->fields['monto_subtotal'] > 0) {
						if ($this->fields['monto_gastos'] > 0) {
							if ($this->fields['monto'] == round($this->fields['monto']))
								$detalle_cuenta_honorarios_primer_dia_mes = __('. Esta cantidad corresponde a') . __(' (i) ') . $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . number_format($monto_moneda_sin_gasto, 0, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ' ' . __('por concepto de honorarios');
							else
								$detalle_cuenta_honorarios_primer_dia_mes = __('. Esta cantidad corresponde a') . __(' (i) ') . $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . number_format($monto_moneda_sin_gasto, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ' ' . __('por concepto de honorarios');
						}
						$detalle_cuenta_honorarios_primer_dia_mes .= ' ( ' . __('equivalente a') . ' ' . $moneda->fields['simbolo'] . number_format($this->fields['monto'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
						$detalle_cuenta_honorarios_primer_dia_mes .= __(', conforme a su equivalencia en pesos al primer día hábil del presente mes') . ')';
					}
				}
				$boleta_honorarios = __('según Boleta de Honorarios adjunta');
				if ($total_gastos != 0) {
					if ($this->fields['monto_subtotal'] > 0) {
						if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'ValorSinEspacio') ) || ( method_exists('Conf', 'ValorSinEspacio') && Conf::ValorSinEspacio() )))
							$detalle_cuenta_gastos = __('; más') . ' (ii) ' . $moneda_total->fields['simbolo'] . number_format($total_gastos, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ' ' . __('por concepto de gastos incurridos por nuestro Estudio en dicho período');
						else
							$detalle_cuenta_gastos = __('; más') . ' (ii) ' . $moneda_total->fields['simbolo'] . ' ' . number_format($total_gastos, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ' ' . __('por concepto de gastos incurridos por nuestro Estudio en dicho período');
					}
					else
						$detalle_cuenta_gastos = __(' por concepto de gastos incurridos por nuestro Estudio en dicho período');
					if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'ValorSinEspacio') ) || ( method_exists('Conf', 'ValorSinEspacio') && Conf::ValorSinEspacio() )))
						$boleta_gastos = __('; más') . ' (ii) ' . $moneda_total->fields['simbolo'] . number_format($total_gastos, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . __('por gastos a reembolsar') . __(', según Boleta de Recuperación de Gastos adjunta');
					else
						$boleta_gastos = __('; más') . ' (ii) ' . $moneda_total->fields['simbolo'] . ' ' . number_format($total_gastos, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ' ' . __('por gastos a reembolsar') . __(', según Boleta de Recuperación de Gastos adjunta');
					if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'ValorSinEspacio') ) || ( method_exists('Conf', 'ValorSinEspacio') && Conf::ValorSinEspacio() )))
						$detalle_cuenta_gastos2 = __('; más') . ' (ii) CH' . $moneda_total->fields['simbolo'] . number_format($total_gastos, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ' ' . __('por concepto de gastos incurridos por nuestro Estudio');
					else
						$detalle_cuenta_gastos2 = __('; más') . ' (ii) CH' . $moneda_total->fields['simbolo'] . ' ' . number_format($total_gastos, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ' ' . __('por concepto de gastos incurridos por nuestro Estudio');
				}
				$html2 = str_replace('%boleta_honorarios%', $boleta_honorarios, $html2);
				$html2 = str_replace('%boleta_gastos%', $boleta_gastos, $html2);
				$html2 = str_replace('%detalle_cuenta_honorarios%', $detalle_cuenta_honorarios, $html2);
				$html2 = str_replace('%detalle_cuenta_honorarios_primer_dia_mes%', $detalle_cuenta_honorarios_primer_dia_mes, $html2);
				$html2 = str_replace('%detalle_cuenta_gastos%', $detalle_cuenta_gastos, $html2);
				$html2 = str_replace('%detalle_cuenta_gastos2%', $detalle_cuenta_gastos2, $html2);

				$query = "SELECT CONCAT_WS(' ',usuario.nombre,usuario.apellido1,usuario.apellido2) as nombre_encargado, IFNULL( prm_categoria_usuario.glosa_categoria, ' ' ) as categoria_usuario
										FROM usuario
										JOIN contrato ON usuario.id_usuario=contrato.id_usuario_responsable
									 	JOIN cobro ON contrato.id_contrato=cobro.id_contrato
										LEFT JOIN prm_categoria_usuario ON ( usuario.id_categoria_usuario = prm_categoria_usuario.id_categoria_usuario AND usuario.id_categoria_usuario != 0 )
									 WHERE cobro.id_cobro=" . $this->fields['id_cobro'];

				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				list($nombre_encargado, $categoria_usuario) = mysql_fetch_array($resp);
				$html2 = str_replace('%encargado_comercial%', $nombre_encargado, $html2);
				$html2 = str_replace('%encargado_comercial_uc%', ucwords(strtolower($nombre_encargado)), $html2);
				$simbolo_opc_moneda_total = $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'];
				$html2 = str_replace('%simbolo_opc_moneda_totall%', $simbolo_opc_moneda_total, $html2);
				$html2 = str_replace('%categoria_encargado_comercial%', __($categoria_usuario), $html2);
				$html2 = str_replace('%categoria_encargado_comercial_mayusculas%', mb_strtoupper(__($categoria_usuario)), $html2);


				$nombre_contacto_partes = explode(' ', $contrato->fields['contacto']);
				$html2 = str_replace('%SoloNombreContacto%', $nombre_contacto_partes[0], $html2);

				if ($contrato->fields['id_cuenta'] > 0) {
					$query = "	SELECT b.nombre, cb.numero, cb.cod_swift, cb.CCI, cb.glosa
								FROM cuenta_banco cb
								LEFT JOIN prm_banco b ON b.id_banco = cb.id_banco
								WHERE cb.id_cuenta = '" . $contrato->fields['id_cuenta'] . "'";
					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
					list($glosa_banco, $numero_cuenta, $codigo_swift, $codigo_cci, $glosa_cuenta) = mysql_fetch_array($resp);

					if (strpos($glosa_banco, 'Ah')) {
						$glosa_banco = str_replace(' Ah', '', $glosa_banco);
						$tipo_cuenta = 'Cuenta Ahorros';
					} else if (strpos($glosa_banco, 'Cte')) {
						$glosa_banco = str_replace(' Cte', '', $glosa_banco);
						$tipo_cuenta = 'Cuenta Corriente';
					}

					$html2 = str_replace('%numero_cuenta_contrato%', $numero_cuenta, $html2);
					$html2 = str_replace('%glosa_banco_contrato%', $glosa_banco, $html2);
					$html2 = str_replace('%glosa_cuenta_contrato%', $glosa_cuenta, $html2);
					$html2 = str_replace('%codigo_swift%', $codigo_swift, $html2);
					$html2 = str_replace('%codigo_cci%', $codigo_cci, $html2);
					$html2 = str_replace('%tipo_cuenta%', $tipo_cuenta, $html2);
				} else {
					$html2 = str_replace('%numero_cuenta_contrato%', '', $html2);
					$html2 = str_replace('%glosa_banco_contrato%', '', $html2);
					$html2 = str_replace('%glosa_cuenta_contrato%', '', $html2);
					$html2 = str_replace('%codigo_swift%', '', $html2);
					$html2 = str_replace('%codigo_cci%', '', $html2);
					$html2 = str_replace('%tipo_cuenta%', '', $html2);
				}



				if (UtilesApp::GetConf($this->sesion, 'SegundaCuentaBancaria')) {
					if ($contrato->fields['id_cuenta2'] > 0) {
						$query = "	SELECT b.nombre, cb.numero, cb.cod_swift, cb.CCI, cb.glosa
									FROM cuenta_banco cb
									LEFT JOIN prm_banco b ON b.id_banco = cb.id_banco
									WHERE cb.id_cuenta = '" . $contrato->fields['id_cuenta2'] . "'";
						$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
						list($glosa_banco, $numero_cuenta, $codigo_swift, $codigo_cci, $glosa_cuenta) = mysql_fetch_array($resp);
						$html2 = str_replace('%numero_cuenta_contrato2%', $numero_cuenta, $html2);
						$html2 = str_replace('%glosa_banco_contrato2%', $glosa_banco, $html2);
						$html2 = str_replace('%glosa_cuenta_contrato2%', $glosa_cuenta, $html2);
						$html2 = str_replace('%codigo_swift2%', $codigo_swift, $html2);
						$html2 = str_replace('%codigo_cci2%', $codigo_cci, $html2);
					} else {
						$html2 = str_replace('%numero_cuenta_contrato2%', '', $html2);
						$html2 = str_replace('%glosa_banco_contrato2%', '', $html2);
						$html2 = str_replace('%glosa_cuenta_contrato2%', '', $html2);
						$html2 = str_replace('%codigo_swift2%', '', $html2);
						$html2 = str_replace('%codigo_cci2%', '', $html2);
					}
				}

				break;


		}

		return $html2;
	}

	function GenerarDocumentoCartaComun($parser_carta, $theTag = '', $lang, $moneda_cliente_cambio, $moneda_cli, & $idioma, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $cliente, $id_carta) {
		global $id_carta;
		global $contrato;
		global $cobro_moneda;
		global $moneda_total;
		global $x_resultados;
		global $x_cobro_gastos;

		if (!isset($parser_carta->tags[$theTag]))
			return;

		$html2 = $parser_carta->tags[$theTag];

		switch ($theTag) {
			case 'FECHA': //GenerarDocumentoCartaComun
				#formato especial
				if (method_exists('Conf', 'GetConf')) {
					if ($lang == 'es') {
						$fecha_lang = UtilesApp::GetConf($this->sesion, 'CiudadEstudio') . ', ' . ucfirst(Utiles::sql3fecha(date('Y-m-d'), '%e de %B de %Y'));
					} else {
						$fecha_lang = UtilesApp::GetConf($this->sesion, 'CiudadEstudio') . ' (' . Conf::GetConf($this->sesion, 'PaisEstudio') . '), ' . ucfirst(Utiles::sql3fecha(date('Y-m-d'), '%e de %B de %Y'));
					}
				} else {
					if ($lang == 'es') {
						$fecha_lang = 'Santiago, ' . ucfirst(Utiles::sql3fecha(date('Y-m-d'), '%e de %B de %Y'));
					} else {
						$fecha_lang = 'Santiago (Chile), ' . date('F d, Y');
					}
				}
				$transformar = array('De' => 'de', 'DE' => 'de');
				$fecha_lang_esp = 'Santiago, ' . strtr(ucwords(Utiles::sql3fecha(date('Y-m-d'), '%e de %B de %Y')), $transformar);
				$fecha_espanol = ucfirst(Utiles::sql3fecha(date('Y-m-d'), '%e de %B de %Y'));

				$html2 = str_replace('%fecha_especial%', $fecha_lang, $html2);
				$html2 = str_replace('%fecha_especial2%', $fecha_lang_esp, $html2);
				$html2 = str_replace('%fecha_espanol%', $fecha_espanol, $html2);

				#formato normal
				if ($lang == 'es') {
					$fecha_lang_con_de = ucfirst(Utiles::sql3fecha(date('Y-m-d'), '%B %d de %Y'));
					$fecha_lang = ucfirst(Utiles::sql3fecha(date('Y-m-d'), '%B %d, %Y'));
				} else {
					$fecha_lang_con_de = date('F d de Y');
					$fecha_lang = date('F d, Y');
				}

				$fecha_ingles = date('F d, Y');
				$fecha_ingles_ordinal = date('F jS, Y');

				$html2 = str_replace('%fecha%', $fecha_lang, $html2);
				$html2 = str_replace('%fecha_con_de%', $fecha_lang_con_de, $html2);
				$html2 = str_replace('%fecha_ingles%', $fecha_ingles, $html2);
				$html2 = str_replace('%fecha_ingles_ordinal%', $fecha_ingles_ordinal, $html2);

//numero Cobro + año + INICIALES username para PSU abogados

				$fecha_diff_con_de = $datediff > 0 && $datediff < 12 ? $texto_fecha_es : __(' ') . ' ' . ucfirst(Utiles::sql3fecha($this->fields['fecha_fin'], '%Y'));
				$html2 = str_replace('%ANO%', $fecha_diff_con_de, $html2);

				$html2 = str_replace('%numero_cobro%', $this->fields['id_cobro'], $html2);

				$query = "SELECT CONCAT_WS(' ',usuario.nombre,usuario.apellido1,usuario.apellido2) as nombre_encargado
										FROM usuario
										JOIN contrato ON usuario.id_usuario=contrato.id_usuario_responsable
									 	JOIN cobro ON contrato.id_contrato=cobro.id_contrato
									 WHERE cobro.id_cobro=" . $this->fields['id_cobro'];
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				list($nombre_encargado) = mysql_fetch_array($resp);


				$iniciales_encargado = $this->iniciales($nombre_encargado);
				/* encargado comercial y iniciales encargado comercial */
				$html2 = str_replace('%inciales_encargado%', $iniciales_encargado, $html2);
				$html2 = str_replace('%encargado_comercial%', $nombre_encargado, $html2);
				$html2 = str_replace('%xrut%', $contrato->fields['rut'], $html2);


//numero Cobro + año + INICIALES username para PSU abogados

				break;

			case 'ENVIO_DIRECCION': //GenerarDocumentoCartaComun
				$query = "SELECT glosa_cliente FROM cliente
									WHERE codigo_cliente='" . $contrato->fields['codigo_cliente']."'";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				list($glosa_cliente) = mysql_fetch_array($resp);

				if (!empty($contrato->fields['titulo_contacto']) && $contrato->fields['titulo_contacto'] != '-1') {
					$html2 = str_replace('%SR%', __($contrato->fields['titulo_contacto']), $html2);
				} else {
					$html2 = str_replace('%SR%', __('Sr.'), $html2);
				}

				/* PSU optimizacion segmento codigo y creacion ANCHOR NOMBRE CONTACTO MAYUSCULA */
				$html2= str_replace('%glosa_codigo_postal%',__('Código Postal'),$html2);
				$html2= str_replace('%codigo_postal%',$contrato->fields['factura_codigopostal'],$html2);
				$html2 = str_replace('%titulo_contacto%', $contrato->fields['titulo_contacto'], $html2);
				$html2 = str_replace('%nombre_contacto_mb%', __('%nombre_contacto_mb%'), $html2);
				if (UtilesApp::GetConf($this->sesion, 'TituloContacto')) {
					$html2 = str_replace('%NombreContacto%', $contrato->fields['contacto'] . ' ' . $contrato->fields['apellido_contacto'], $html2);
				} else {
					$html2 = str_replace('%NombreContacto%', $contrato->fields['contacto'], $html2);
				}

				if (UtilesApp::GetConf($this->sesion, 'TituloContacto')) {
					$html2 = str_replace('%NombreContacto_mayuscula%', mb_strtoupper($contrato->fields['contacto'] . ' ' . $contrato->fields['apellido_contacto']), $html2);
				} else {
					$html2 = str_replace('%NombreContacto_mayuscula%', mb_strtoupper($contrato->fields['contacto']), $html2);
				}

				/* PSU optimizacion segmento codigo y creacion ANCHOR NOMBRE CONTACTO MAYUSCULA */
				$html2 = str_replace('%solicitante%', $trabajo->fields['solicitante'], $html2);
				$html2 = str_replace('%NombreContacto%', $contrato->fields['contacto'], $html2);
				$html2 = str_replace('%nombre_cliente%', $glosa_cliente, $html2);
				$html2 = str_replace('%glosa_cliente%', $contrato->fields['factura_razon_social'], $html2);
				$html2 = str_replace('%glosa_cliente_mayuscula%', strtoupper($contrato->fields['factura_razon_social']), $html2);


				$direccion=explode('//',$contrato->fields['direccion_contacto']);
				$html2 = str_replace('%valor_direccion%', nl2br($direccion[0]), $html2);
				$html2 = str_replace('%valor_direccion_uc%', ucwords(strtolower(nl2br($direccion[0]))), $html2);

				#formato especial
				if ($lang == 'es')
					$fecha_lang = 'Santiago, ' . ucfirst(Utiles::sql3fecha(date('Y-m-d'), '%e de %B de %Y'));
				else
					$fecha_lang = 'Santiago (Chile), ' . date('F d, Y');

				$html2 = str_replace('%fecha_especial%', $fecha_lang, $html2);
				$html2 = str_replace('%fecha_especial_minusculas%', strtolower($fecha_lang), $html2);

				$this->loadAsuntos();

				$asuntos_doc = '';
				for ($k = 0; $k < count($this->asuntos); $k++) {
					$asunto = new Asunto($this->sesion);
					$asunto->LoadByCodigo($this->asuntos[$k]);
					$espace = $k < count($this->asuntos) - 1 ? ', ' : '';
					$salto_linea = $k < count($this->asuntos) - 1 ? '<br>' : '';
					$asuntos_doc .= $asunto->fields['glosa_asunto'] . '' . $espace;
					$asuntos_doc_con_salto .= $asunto->fields['glosa_asunto'] . '' . $salto_linea;
					$codigo_asunto .= $asunto->fields['codigo_asunto'] . '' . $espace;
				}
				$html2 = str_replace('%Asunto%', $asuntos_doc, $html2);
				$html2 = str_replace('%asunto_salto_linea%', $asuntos_doc_con_salto, $html2);
				#$html2 = str_replace('%NumeroContrato%', $contrato->fields['id_contrato'], $html2);
				$html2 = str_replace('%NumeroCliente%', $cliente->fields['id_cliente'], $html2);
				if (count($this->asuntos) == 1)
					$html2 = str_replace('%CodigoAsunto%', $codigo_asunto, $html2);
				else
					$html2 = str_replace('%CodigoAsunto%', '', $html2);
				$html2 = str_replace('%pais%', 'Chile', $html2);
				$html2 = str_replace('%num_letter%', $this->fields['id_cobro'], $html2);
				$html2 = str_replace('%num_letter_documento%', $this->fields['documento'], $html2);
				$html2 = str_replace('%num_letter_baz%', $this->fields['documento'], $html2);
				#carta mb
				if (method_exists('Conf', 'GetConf')) {
					if (Conf::GetConf($this->sesion, 'TituloContacto')) {
						$html2 = str_replace('%sr%', __($contrato->fields['titulo_contacto']), $html2);
					} else {
						$html2 = str_replace('%sr%', __('Señor'), $html2);
					}
				} else if (method_exists('Conf', 'TituloContacto')) {
					if (Conf::TituloContacto()) {
						$html2 = str_replace('%sr%', __($contrato->fields['titulo_contacto']), $html2);
					} else {
						$html2 = str_replace('%sr%', __('Señor'), $html2);
					}
				} else {
					$html2 = str_replace('%sr%', __('Señor'), $html2);
				}
				$html2 = str_replace('%asunto_mb%', __('%asunto_mb%'), $html2);
				$html2 = str_replace('%presente%', __('Presente'), $html2);

				if ($contrato->fields['id_pais'] > 0) {
					$query = "SELECT nombre FROM prm_pais
										WHERE id_pais=" . $contrato->fields['id_pais'];
					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
					list($nombre_pais) = mysql_fetch_array($resp);
					$html2 = str_replace('%nombre_pais%', $nombre_pais, $html2);
					$html2 = str_replace('%nombre_pais_mayuscula%', strtoupper($nombre_pais), $html2);
				} else {
					$html2 = str_replace('%nombre_pais%', '', $html2);
					$html2 = str_replace('%nombre_pais_mayuscula%', '', $html2);
				}

				/* Fecha Correspondientes al mes de */
				$fecha_diff_con_de = $datediff > 0 && $datediff < 12 ? $texto_fecha_es : __('correspondientes al mes de') . ' ' . ucfirst(Utiles::sql3fecha($this->fields['fecha_fin'], '%B de %Y'));
				$html2 = str_replace('%fecha_con_de%', $fecha_diff_con_de, $html2);

				//%factura_desc_mta%
				if (strtolower($nombre_pais) != 'colombia') {
					$html2 = str_replace('%factura_desc_mta%', 'cuenta de cobro', $html2);
				} else {
					$html2 = str_replace('%factura_desc_mta%', 'factura', $html2);
				}

				$html2 = str_replace('%num_factura%', $this->fields['documento'], $html2);
				$html2 = str_replace('%ciudad_cliente%', $contrato->fields['factura_ciudad'], $html2);
				$html2 = str_replace('%comuna_cliente%', $contrato->fields['factura_comuna'], $html2);
				$html2 = str_replace('%codigo_postal_cliente%', $contrato->fields['factura_codigopostal'], $html2);

				break;


			case 'ADJ': //GenerarDocumentoCartaComun
				#firma careyallende
				$html2 = str_replace('%firma_careyallende%', __('%firma_careyallende%'), $html2);

				#nombre_encargado comercial
				$query = "SELECT CONCAT(a.nombre, ' ', a.apellido1, ' ', a.apellido2) FROM usuario AS a JOIN contrato ON a.id_usuario=contrato.id_usuario_responsable JOIN cobro ON cobro.id_contrato=contrato.id_contrato WHERE cobro.id_cobro=" . $this->fields['id_cobro'];
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				list($nombre_encargado) = mysql_fetch_array($resp);
				list( $nombre, $apellido1, $apellido2 ) = explode(' ', $nombre_encargado);
				$iniciales = substr($nombre, 0, 1) . substr($apellido1, 0, 1) . substr($apellido2, 0, 1);
				$html2 = str_replace('%iniciales_encargado_comercial%', $iniciales, $html2);
				$html2 = str_replace('%nombre_encargado_comercial%', $nombre_encargado, $html2);

				$html2 = str_replace('%nro_factura%', $this->fields['documento'], $html2);
				$html2 = str_replace('%num_letter%', $this->fields['id_cobro'], $html2);
				$html2 = str_replace('%num_letter_documento%', $this->fields['documento'], $html2);
				$html2 = str_replace('%num_letter_baz%', $this->fields['documento'], $html2);
				$html2 = str_replace('%cliente_fax%', $contrato->fields['fono_contacto'], $html2);
				break;

			case 'PIE': //GenerarDocumentoCartaComun
				if (method_exists('Conf', 'GetConf')) {
					$PdfLinea1 = Conf::GetConf($this->sesion, 'PdfLinea1');
					$PdfLinea2 = Conf::GetConf($this->sesion, 'PdfLinea3');
					$SitioWeb = Conf::GetConf($this->sesion, 'SitioWeb');
					$Email = Conf::GetConf($this->sesion, 'Email');
				} else {
					$PdfLinea1 = Conf::PdfLinea1();
					$PdfLinea2 = Conf::PdfLinea3();
					$SitioWeb = Conf::SitioWeb();
					$Email = Conf::Email();
				}

				$html2 = str_replace('%logo_carta%', Conf::Server() . Conf::ImgDir(), $html2);
				$pie_pagina = $PdfLinea2 . ' ' . $PdfLinea3 . '<br>' . $SitioWeb . ' - E-mail: ' . $Email;
				$html2 = str_replace('%direccion%', $pie_pagina, $html2);
				$html2 = str_replace('%num_letter%', $this->fields['id_cobro'], $html2);
				$html2 = str_replace('%num_letter_documento%', $this->fields['documento'], $html2);
				break;

			case 'DATOS_CLIENTE': //GenerarDocumentoCartaComun

				/* Datos detalle */
				if (!empty($contrato->fields['titulo_contacto']) && $contrato->fields['titulo_contacto'] != '-1') {
					$html2 = str_replace('%SR%', __($contrato->fields['titulo_contacto']), $html2);
				} else {
					$html2 = str_replace('%SR%', __('Sr.'), $html2);
				}
				if (method_exists('Conf', 'GetConf')) {
					if (Conf::GetConf($this->sesion, 'TituloContacto')) {
						$html2 = str_replace('%sr%', __($contrato->fields['titulo_contacto']), $html2);
						$html2 = str_replace('%NombrePilaContacto%', $contrato->fields['contacto'], $html2);
						$html2 = str_replace('%ApellidoContacto%', $contrato->fields['apellido_contacto'], $html2);
					} else {
						$html2 = str_replace('%sr%', __('Señor'), $html2);
						$NombreContacto = explode(' ', $contrato->fields['contacto']);
						$html2 = str_replace('%NombrePilaContacto%', $NombreContacto[0], $html2);
						$html2 = str_replace('%ApellidoContacto%', $NombreContacto[1], $html2);
					}
				} else if (method_exists('Conf', 'TituloContacto')) {
					if (Conf::TituloContacto()) {
						$html2 = str_replace('%sr%', __($contrato->fields['titulo_contacto']), $html2);
						$html2 = str_replace('%NombrePilaContacto%', $contrato->fields['contacto'], $html2);
						$html2 = str_replace('%ApellidoContacto%', $contrato->fields['apellido_contacto'], $html2);
					} else {
						$html2 = str_replace('%sr%', __('Señor'), $html2);
						$NombreContacto = explode(' ', $contrato->fields['contacto']);
						$html2 = str_replace('%NombrePilaContacto%', $NombreContacto[0], $html2);
						$html2 = str_replace('%ApellidoContacto%', $NombreContacto[1], $html2);
					}
				} else {
					$html2 = str_replace('%sr%', __('Señor'), $html2);
					$NombreContacto = explode(' ', $contrato->fields['contacto']);
					$html2 = str_replace('%NombrePilaContacto%', $NombreContacto[0], $html2);
					$html2 = str_replace('%ApellidoContacto%', $NombreContacto[1], $html2);
				}
				$html2 = str_replace('%glosa_cliente%', $contrato->fields['factura_razon_social'], $html2);
				if (strtolower($contrato->fields['titulo_contacto']) == 'sra.' || strtolower($contrato->fields['titulo_contacto']) == 'srta.')
					$html2 = str_replace('%estimado%', __('Estimada'), $html2);
				else
					$html2 = str_replace('%estimado%', __('Estimado'), $html2);

				$query = "SELECT CONCAT_WS(' ',usuario.nombre,usuario.apellido1,usuario.apellido2) as nombre_encargado
										FROM usuario
										JOIN contrato ON usuario.id_usuario=contrato.id_usuario_responsable
									 	JOIN cobro ON contrato.id_contrato=cobro.id_contrato
									 WHERE cobro.id_cobro=" . $this->fields['id_cobro'];
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				list($nombre_encargado) = mysql_fetch_array($resp);
				$nombre_encargado_mayuscula = strtoupper($nombre_encargado);
				$html2 = str_replace('%encargado_comercial_mayusculas%', $nombre_encargado_mayuscula, $html2);

				break;


				case 'FILAS_FACTURAS_DEL_COBRO':

 				$row_template = $html2;
				$html2 = '';
					
				
				$facturasRS=$this->ArrayFacturasDelContrato;//($this->sesion,$nuevomodulofactura,$this->fields['id_cobro']);
				
				foreach ($facturasRS as $numfact => $factura) {
					$row=$row_template;
					if($factura[0]['id_cobro']==$this->fields['id_cobro']) {
						$row=str_replace('%factura_numero%',$numfact,$row);
						$row=str_replace('%factura_moneda%',$factura[0]['simbolo_moneda_total'],$row);
						$row=str_replace('%factura_total_sin_impuesto%',number_format($factura[0]['total_sin_impuesto'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']),$row);
						$row=str_replace('%factura_impuesto%',number_format($factura[0]['iva'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']),$row);
						$row=str_replace('%factura_total%',number_format($factura[0]['total'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']),$row);
						$row=str_replace('%factura_periodo%',$factura[0]['periodo'],$row);
						$html2.=$row;
					}
				}
												
				break;

				case 'FILA_FACTURAS_PENDIENTES': //GenerarDocumentoCartaComun
					//original
					//$query = "SELECT numero  FROM `factura` WHERE `estado` NOT IN ('1.3.4') AND `anulado` != 1 AND codigo_cliente=" . $this->fields['codigo_cliente'];
					//fix
					$query = "SELECT numero  FROM `factura` WHERE `id_estado` NOT IN (1, 3, 4) AND `anulado` != 1 AND codigo_cliente=" . $this->fields['codigo_cliente'];
					$facturasST = $this->sesion->pdodbh->query($query);
					$facturasRS = $facturasST->fetchAll();
					$row_template = $html2;
					$html2 = '';
					foreach ($facturasRS as $facturaspendientes) {
					    $row = $row_template;
					    $row = str_replace('%facturas_pendientes%','No '.$facturaspendientes['numero'], $row);

					    $html2.=$row;
					}

				break;


				case 'FILAS_ASUNTOS_RESUMEN': //GenerarDocumentoCarta2
				/**
				 * Esto se hizo para Mu?oz Tamayo y Asociados. (ESM)
				 */
				global $subtotal_hh, $subtotal_gasto, $impuesto_hh, $impuesto_gasto, $simbolo, $cifras_decimales;

				$query_desglose_asuntos = "SELECT pm.cifras_decimales, pm.simbolo, @rownum:=@rownum+1 as rownum, ca.id_cobro, ca.codigo_asunto,a.glosa_asunto
						    ,if(@rownum=kant,@sumat1:=(1.0000-@sumat1), round(ifnull(trabajos.trabajos_thh/monto_thh,0),4)) pthh
						    ,@sumat1:=@sumat1+round(ifnull(trabajos.trabajos_thh/monto_thh,0),4) pthhac
						    ,if(@rownum=kant,@sumat2:=(1.0000-@sumat2), round(ifnull(trabajos.trabajos_thh_estandar/monto_thh_estandar,0),4)) pthhe
						    ,@sumat2:=@sumat2+round(ifnull(trabajos.trabajos_thh_estandar/monto_thh_estandar,0),4) pthheac
						    ,if(@rownum=kant,@sumag:=(1.0000-@sumag), round(ifnull(gastos.gastos/subtotal_gastos,0),4))  pg
						    ,@sumag:=@sumag+round(ifnull(gastos.gastos/subtotal_gastos,0),4) pgac
  					            ,c.monto_trabajos
						    ,c.monto_thh
						    ,c.monto_thh_estandar
						    ,c.subtotal_gastos , c.impuesto, c.impuesto_gastos
						    ,kant.kant

						    FROM cobro_asunto ca
							join cobro c ON (c.id_cobro = ca.id_cobro)
							join asunto a ON (a.codigo_asunto = ca.codigo_asunto)
						    join (select id_cobro, count(codigo_asunto) kant from cobro_asunto group by id_cobro) kant on kant.id_cobro=c.id_cobro
						    join (select @rownum:=0, @sumat1:=0, @sumat2:=0, @sumag:=0) fff
						    join prm_moneda pm on pm.id_moneda=c.id_moneda
						    left join (SELECT id_cobro, codigo_asunto, SUM( TIME_TO_SEC( duracion_cobrada ) /3600 * tarifa_hh ) AS trabajos_thh, SUM( TIME_TO_SEC( duracion_cobrada ) /3600 * tarifa_hh_estandar ) AS trabajos_thh_estandar
						    FROM trabajo

						    GROUP BY codigo_asunto,id_cobro) trabajos on trabajos.id_cobro=c.id_cobro and trabajos.codigo_asunto=ca.codigo_asunto
						    left join (select id_cobro, codigo_asunto, sum(ifnull(egreso,0)-ifnull(ingreso,0)) gastos
						    from cta_corriente where cobrable=1
						    group by id_cobro, codigo_asunto) gastos on gastos.id_cobro=c.id_cobro and gastos.codigo_asunto=ca.codigo_asunto
						    WHERE ca.id_cobro=" . $this->fields['id_cobro'];


				$rest_desglose_asuntos = mysql_query($query_desglose_asuntos, $this->sesion->dbh) or Utiles::errorSQL($query_desglose_asuntos, __FILE__, __LINE__, $this->sesion->dbh);
				$moneda_actual = $this->fields['id_cobro'];
				$row_tmpl = $html2;
				$html2 = '';
				$filas = 1;
				while ($rowdesglose = mysql_fetch_array($rest_desglose_asuntos)) {
					list($subtotal_hh, $subtotal_gasto, $impuesto_hh, $impuesto_gasto, $simbolo, $cifras_decimales) = array($rowdesglose['monto_trabajos'], $rowdesglose['subtotal_gastos'], $rowdesglose['impuesto'], $rowdesglose['impuesto_gastos'], $rowdesglose['simbolo'], $rowdesglose['cifras_decimales']);
					$row = $row_tmpl;


					// _mi = moneda seleccionada para descargar el documento
					$subtotal_hh_mi = ( $subtotal_hh * $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] ) / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'];
					$subtotal_gasto_mi = ( $subtotal_gasto * $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] ) / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'];
					$impuesto_hh_mi = ( $impuesto_hh * $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] ) / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'];
					$impuesto_gasto_mi = ( $impuesto_gasto * $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] ) / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'];
					$simbolo_mi = $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'];
					$cifras_decimales_mi = $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'];

					list($pthh, $pg) = array($rowdesglose['monto_trabajos'], $rowdesglose['subtotal_gastos'], $rowdesglose['impuesto'], $rowdesglose['impuesto_gastos'], $rowdesglose['simbolo'], $rowdesglose['cifras_decimales']);
					$row = str_replace('%glosa_asunto%', $rowdesglose['glosa_asunto'], $row);
					$row = str_replace('%simbolo%', $simbolo, $row);
					$row = str_replace('%honorarios_asunto%', round($rowdesglose['monto_trabajos'] * $rowdesglose['pthh'], $cifras_decimales), $row);
					$row = str_replace('%gastos_asunto%', round($rowdesglose['subtotal_gastos'] * $rowdesglose['pg'], $cifras_decimales), $row);

					$row = str_replace('%total_asunto%', round(floatval($subtotal_hh) + floatval($subtotal_gasto) + floatval($impuesto_hh) + floatval($impuesto_gasto), $cifras_decimales), $row);

					$row = str_replace('%simbolo_mi%', $simbolo_mi, $row);
					$row = str_replace('%honorarios_asunto_mi%', round($subtotal_hh_mi * $rowdesglose['pthh'], $cifras_decimales_mi), $row);
					$row = str_replace('%gastos_asunto_mi%', round($subtotal_gasto_mi * $rowdesglose['pg'], $cifras_decimales_mi), $row);

					//var_dump( $cobro_moneda ); exit;
					$total_asunto_mi = round(floatval($subtotal_hh_mi) + floatval($subtotal_gasto_mi) + floatval($impuesto_hh_mi) + floatval($impuesto_gasto_mi), $cifras_decimales_mi);
					$row = str_replace('%total_asunto_mi%', number_format($total_asunto_mi, $cifras_decimales_mi, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

					$fecha_mta_emision = $this->fields['fecha_emision'] ? Utiles::sql2fecha($this->fields['fecha_emision'], '%d/%m/%Y') : '';
					$fecha_mta_facturacion = $this->fields['fecha_facturacion'] ? Utiles::sql2fecha($this->fields['fecha_facturacion'], '%d/%m/%Y') : $fecha_mta_emision;
					list($fecha_mta_dia, $fecha_mta_mes, $fecha_mta_agno) = explode("/", $fecha_mta_facturacion);


					if ($filas > 1) {
						$row = str_replace('%num_letter%', '', $row);
						$row = str_replace('%num_factura%', '', $row);
						$row = str_replace('%fecha_mta%', '', $row);
					} else {
						$row = str_replace('%num_letter%', $this->fields['id_cobro'], $row);
						$row = str_replace('%num_factura%', $this->fields['documento'], $row);
						$row = str_replace('%fecha_mta%', $fecha_mta_facturacion, $row);
					}
					$html2 .= $row;
					$filas++;
				}


				break;

				case 'SALTO_PAGINA': //GenerarDocumentoComun
				//no borrarle al css el BR.divisor
				break;
		}

		return $html2;
	}

} 
