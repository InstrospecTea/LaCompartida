<?php

require_once dirname(__FILE__) . '/../conf.php';
require_once('Numbers/Words.php');

class NotaCobro extends Cobro {

	// Twig, the flexible, fast, and secure template language for PHP
	protected $twig;
	protected $template_data;
	private $detalle_en_asuntos = FALSE;

	var $asuntos = array();
	var $x_resultados = array();

	/**
	 * @var Sesion
	 */
	var $sesion = null;
	var $carta_tabla = 'cobro_rtf';
	var $carta_id = 'id_formato';
	var $carta_formato = 'cobro_template';
	var $siguiente = array();
	public $secciones = array(
		'INFORME' => array(
			'CLIENTE' => 'CLIENTE / GenerarSeccionCliente',
			'DETALLE_COBRO' => 'DETALLE_COBRO / GenerarDocumento2',
			'ADELANTOS' => 'ADELANTOS / GenerarDocumento2',
			'COBROS_ADEUDADOS' => 'COBROS_ADEUDADOS / GenerarDocumento2',
			'RESUMEN_CAP' => 'RESUMEN_CAP / GenerarDocumento2',
			'ASUNTOS' => 'ASUNTOS / GenerarDocumentoComun',
			'GASTOS' => 'GASTOS / GenerarDocumento2',
			'CTA_CORRIENTE' => 'CTA_CORRIENTE / GenerarDocumento2',
			'TIPO_CAMBIO' => 'TIPO_CAMBIO / GenerarDocumentoComun',
			'MOROSIDAD' => 'MOROSIDAD / GenerarDocumento2',
			'GLOSA_ESPECIAL' => 'GLOSA_ESPECIAL / GenerarDocumentoComun',
			'RESUMEN_PROFESIONAL_POR_CATEGORIA' => 'RESUMEN_PROFESIONAL_POR_CATEGORIA / GenerarSeccionResumenProfesional',
			'RESUMEN_PROFESIONAL' => 'RESUMEN_PROFESIONAL / GenerarSeccionResumenProfesional',
			'ENDOSO' => 'ENDOSO / GenerarDocumentoComun',
			'SALTO_PAGINA' => 'SALTO_PAGINA / GenerarDocumentoComun',
			'DESGLOSE_POR_ASUNTO_DETALLE' => 'DESGLOSE_POR_ASUNTO_DETALLE / GenerarDocumentoComun',
			'DESGLOSE_POR_ASUNTO_TOTALES' => 'DESGLOSE_POR_ASUNTO_TOTALES / GenerarDocumentoComun',
		),
		'DETALLE_COBRO' => array(
			'FACTURA_NUMERO' => 'FACTURA_NUMERO / GenerarDocumento2',
			'NUMERO_FACTURA' => 'NUMERO_FACTURA / GenerarDocumento2',
			'DETALLE_COBRO_RETAINER' => 'DETALLE_COBRO_RETAINER / GenerarDocumentoComun',
			'DETALLE_TARIFA_ADICIONAL' => 'DETALLE_TARIFA_ADICIONAL / GenerarDocumentoComun',
			'RESTAR_RETAINER' => 'RESTAR_RETAINER / GenerarDocumento2',
			'DETALLE_HONORARIOS' => 'DETALLE_HONORARIOS / GenerarDocumento2',
			'DETALLE_GASTOS' => 'DETALLE_GASTOS / GenerarDocumento2',
			'DETALLE_TRAMITES' => 'DETALLE_TRAMITES / GenerarDocumento2',
			'DETALLE_COBRO_MONEDA_TOTAL' => 'DETALLE_COBRO_MONEDA_TOTAL / GenerarDocumento2',
			'DETALLE_COBRO_DESCUENTO' => 'DETALLE_COBRO_DESCUENTO / GenerarDocumento2',
			'IMPUESTO' => 'IMPUESTO / GenerarDocumento2',
			'DETALLES_PAGOS' => 'DETALLES_PAGOS / GenerarSeccionDetallePago',
			'DETALLES_PAGOS_CONTRATO' => 'DETALLES_PAGOS_CONTRATO / GenerarSeccionDetallePagoContrato',
		),
		'DETALLE_HONORARIOS' => array(
			'DETALLE_COBRO_DESCUENTO' => 'DETALLE_COBRO_DESCUENTO / GenerarDocumento2',
			'DETALLE_COBRO_MONEDA_TOTAL' => 'DETALLE_COBRO_MONEDA_TOTAL / GenerarDocumento2',
		),
		'RESUMEN_CAP' => array(
			'COBROS_DEL_CAP' => 'COBROS_DEL_CAP / GenerarDocumento2',
		),
		'GASTOS' => array(
			'GASTOS_ENCABEZADO' => 'GASTOS_ENCABEZADO / GenerarDocumentoComun',
			'GASTOS_FILAS' => 'GASTOS_FILAS / GenerarDocumentoComun',
			'GASTOS_TOTAL' => 'GASTOS_TOTAL / GenerarDocumentoComun',
		),
		'CTA_CORRIENTE' => array(
			'CTA_CORRIENTE_SALDO_INICIAL' => 'CTA_CORRIENTE_SALDO_INICIAL / GenerarDocumentoComun',
			'CTA_CORRIENTE_MOVIMIENTOS_ENCABEZADO' => 'CTA_CORRIENTE_MOVIMIENTOS_ENCABEZADO / GenerarDocumentoComun',
			'CTA_CORRIENTE_MOVIMIENTOS_FILAS' => 'CTA_CORRIENTE_MOVIMIENTOS_FILAS / GenerarDocumentoComun',
			'CTA_CORRIENTE_MOVIMIENTOS_TOTAL' => 'CTA_CORRIENTE_MOVIMIENTOS_TOTAL / GenerarDocumentoComun',
			'CTA_CORRIENTE_SALDO_FINAL' => 'CTA_CORRIENTE_SALDO_FINAL / GenerarDocumentoComun',
		),
		'MOROSIDAD' => array(
			'MOROSIDAD_ENCABEZADO' => 'MOROSIDAD_ENCABEZADO / GenerarDocumentoComun',
			'MOROSIDAD_FILAS' => 'MOROSIDAD_FILAS / GenerarDocumentoComun',
			'MOROSIDAD_HONORARIOS_TOTAL' => 'MOROSIDAD_HONORARIOS_TOTAL / GenerarDocumentoComun',
			'MOROSIDAD_GASTOS' => 'MOROSIDAD_GASTOS / GenerarDocumentoComun',
			'MOROSIDAD_TOTAL' => 'MOROSIDAD_TOTAL / GenerarDocumentoComun',
		),
		'ADELANTOS' => array(
			'ADELANTOS_ENCABEZADO' => 'ADELANTOS_ENCABEZADO / GenerarDocumento2',
			'ADELANTOS_FILAS_TOTAL' => 'ADELANTOS_FILAS_TOTAL / GenerarDocumento2',
		),
		'COBROS_ADEUDADOS' => array(
			'ADELANTOS_ENCABEZADO' => 'ADELANTOS_ENCABEZADO / GenerarDocumento2',
			'COBROS_ADEUDADOS_FILAS_TOTAL' => 'COBROS_ADEUDADOS_FILAS_TOTAL / GenerarDocumento2',
		),
		'ASUNTOS' => array(
			'TRABAJOS_ENCABEZADO' => 'TRABAJOS_ENCABEZADO / GenerarDocumentoComun',
			'TRABAJOS_FILAS' => 'TRABAJOS_FILAS / GenerarDocumentoComun',
			'TRABAJOS_TOTAL' => 'TRABAJOS_TOTAL / GenerarDocumentoComun',
			'DETALLE_PROFESIONAL' => 'DETALLE_PROFESIONAL / GenerarDocumentoComun',
			'HITOS_FILAS' => 'HITOS_FILAS / GenerarDocumentoComun',
			'HITOS_TOTAL' => 'HITOS_TOTAL / GenerarDocumentoComun',
			'HITOS_ENCABEZADO' => 'HITOS_ENCABEZADO / GenerarDocumentoComun',
			'TRAMITES_ENCABEZADO' => 'TRAMITES_ENCABEZADO / GenerarDocumentoComun',
			'TRAMITES_FILAS' => 'TRAMITES_FILAS / GenerarDocumentoComun',
			'TRAMITES_TOTAL' => 'TRAMITES_TOTAL / GenerarDocumentoComun',
			'GASTOS' => 'GASTOS / GenerarDocumentoComun',
		),
		'TRAMITES_FILAS' => array(
			'TRAMITES_TOTAL' => 'TRAMITES_TOTAL / SE LLAMA Y PARSEA SOLO, NO EXISTE EL TAG NI EL CASE',
			'TRAMITES_ENCABEZADO' => 'TRAMITES_ENCABEZADO / SE LLAMA Y PARSEA SOLO, NO EXISTE EL TAG NI EL CASE',
		),
		'TRABAJOS_FILAS' => array(
			'TRABAJOS_TOTAL' => 'TRABAJOS_TOTAL / SE LLAMA Y PARSEA SOLO, NO EXISTE EL TAG NI EL CASE',
			'TRABAJOS_ENCABEZADO' => 'TRABAJOS_ENCABEZADO / GenerarDocumentoComun / SE LLAMA SOLO, NO EXISTE EL TAG',
		),
		'DETALLE_PROFESIONAL' => array(
			'PROFESIONAL_ENCABEZADO' => 'PROFESIONAL_ENCABEZADO / GenerarSeccionResumenProfesional',
			'PROFESIONAL_FILAS' => 'PROFESIONAL_FILAS / GenerarDocumentoComun',
			'PROFESIONAL_TOTAL' => 'PROFESIONAL_TOTAL / GenerarDocumentoComun',
			'DETALLE_COBRO_DESCUENTO' => 'DETALLE_COBRO_DESCUENTO / GenerarDocumentoComun',
			'DETALLE_COBRO_MONEDA_TOTAL_POR_ASUNTO' => 'DETALLE_COBRO_MONEDA_TOTAL_POR_ASUNTO / GenerarDocumentoComun',
			'DETALLE_COBRO_MONEDA_TOTAL' => 'DETALLE_COBRO_MONEDA_TOTAL / GenerarDocumentoComun',
		),
		'PROFESIONAL_TOTAL' => array(
			'DETALLE_PROFESIONAL_RETAINER' => 'DETALLE_PROFESIONAL_RETAINER / GenerarDocumentoComun',
		),
		'RESUMEN_PROFESIONAL' => array(
			'PROFESIONAL_ENCABEZADO' => 'PROFESIONAL_ENCABEZADO / GenerarSeccionResumenProfesional / SE LLAMA SOLO, NO EXISTE EL TAG',
			'PROFESIONAL_FILAS' => 'PROFESIONAL_FILAS / SE LLAMA Y PARSEA SOLO, NO EXISTE EL TAG NI EL CASE',
			'PROFESIONAL_TOTAL' => 'PROFESIONAL_TOTAL / SE LLAMA Y PARSEA SOLO, NO EXISTE EL TAG NI EL CASE',
		),
		'RESUMEN_PROFESIONAL_POR_CATEGORIA' => array(
			'RESUMEN_PROFESIONAL_ENCABEZADO' => 'RESUMEN_PROFESIONAL_ENCABEZADO / GenerarSeccionResumenProfesional / SE LLAMA SOLO, NO EXISTE EL TAG',
			'PROFESIONAL_FILAS' => 'PROFESIONAL_FILAS / SE LLAMA Y PARSEA SOLO, NO EXISTE EL TAG NI EL CASE',
			'RESUMEN_PROFESIONAL_TOTAL' => 'RESUMEN_PROFESIONAL_TOTAL / SE LLAMA Y PARSEA SOLO, NO EXISTE EL TAG NI EL CASE',
		),
	);
	public $diccionario = array(
		'INFORME' => array(
			'%xfecha_mes_dos_digitos%' => 'xfecha_mes_dos_digitos',
			'%xfecha_ano_dos_digitos%' => 'xfecha_ano_dos_digitos',
			'%xfecha_mes_dia_ano%' => 'xfecha_mes_dia_ano',
			'%xfecha_mespalabra_dia_ano%' => 'xfecha_mespalabra_dia_ano',
			'%xnro_factura%' => 'xnro_factura',
			'%xnombre_cliente%' => 'xnombre_cliente',
			'%xglosa_cliente%' => 'xglosa_cliente',
			'%xdireccion%' => 'xdireccion',
			'%xrut%' => 'xrut',
			'%COBRO_CARTA%' => 'COBRO_CARTA',
			'%cobro%' => 'cobro',
			'%valor_cobro%' => 'valor_cobro',
			'%logo%' => 'logo',
			'%titulo%' => 'titulo',
			'%logo_cobro%' => 'logo_cobro',
			'%subtitulo%' => 'subtitulo',
			'%direccion_blr%' => 'direccion_blr',
			'%glosa_fecha%' => 'glosa_fecha',
			'%fecha_gqmc%' => 'fecha_gqmc',
			'%fecha%' => 'fecha',
			'%fecha_mes_dos_digitos%' => 'fecha_mes_dos_digitos',
			'%fecha_ano_dos_digitos%' => 'fecha_ano_dos_digitos',
			'%codigo_cliente%' => 'codigo_cliente',
			'%fecha_mes_del_cobro%' => 'fecha_mes_del_cobro',
			'%fecha_larga%' => 'fecha_larga',
			'%socio%' => 'socio',
			'%socio_cobrador%' => 'socio_cobrador',
			'%nombre_socio%' => 'nombre_socio',
			'%fono%' => 'fono',
			'%fax%' => 'fax',
			'%nota_cobro_acl%' => 'nota_cobro_acl',
			'%reference_no_acl%' => 'reference_no_acl',
			'%servicios%' => 'servicios',
			'%honorarios%' => 'honorarios',
			'%gastos_acl%' => 'gastos_acl',
			'%otros%' => 'otros',
			'%subtotales%' => 'subtotales',
			'%impuestos%' => 'impuestos',
			'%total_deuda%' => 'total_deuda',
			'%instruccion_deposito%' => 'instruccion_deposito',
			'%beneficiario_deposito%' => 'beneficiario_deposito',
			'%banco%' => 'banco',
			'%direccion%' => 'direccion',
			'%cuenta_bancaria%' => 'cuenta_bancaria',
			'%encargado_comercial%' => 'encargado_comercial',
			'%rut_encargado%' => 'rut_encargado',
			'%filas_escalas%' => 'filas_escalas',
			'%TABLA_ESCALONADA%' => 'TABLA_ESCALONADA',
			'%TRAMITES%' => 'TRAMITES',
			'%numero_factura%' => 'numero_factura',
			'%xcorrelativo_aguilar%' => 'xcorrelativo_aguilar',
			'%honorarios_vouga%' => 'honorarios_vouga',
		),
		'DETALLE_COBRO' => array(
			'%honorario_yo_gastos%' => 'honorario_yo_gastos',
			'%materia%' => 'materia',
			'%glosa_asunto_sin_codigo%' => 'glosa_asunto_sin_codigo',
			'%resumen_cobro%' => 'resumen_cobro',
			'%fecha%' => 'fecha',
			'%fecha_emision_glosa%' => 'fecha_emision_glosa',
			'%fecha_emision%' => 'fecha_emision',
			'%glosa_cobro%' => 'glosa_cobro',
			'%cobro%' => 'cobro',
			'%reference%' => 'reference',
			'%valor_cobro%' => 'valor_cobro',
			'%total_simbolo%' => 'total_simbolo',
			'%boleta%' => 'boleta',
			'%encargado%' => 'encargado',
			'%encargado_valor%' => 'encargado_valor',
			'%factura%' => 'factura',
			'%factura_acl%' => 'factura_acl',
			'%pctje_blr%' => 'pctje_blr',
			'%factura_nro%' => 'factura_nro',
			'%cobro_nro%' => 'cobro_nro',
			'%nro_cobro%' => 'nro_cobro',
			'%cobro_factura_nro%' => 'cobro_factura_nro',
			'%nro_factura%' => 'nro_factura',
			'%modalidad%' => 'modalidad',
			'%tipo_honorarios%' => 'tipo_honorarios',
			'%valor_modalidad_tyc%' => 'valor_modalidad_tyc',
			'%valor_modalidad%' => 'valor_modalidad',
			'%valor_modalidad_ucfirst%' => 'valor_modalidad_ucfirst',
			'%detalle_modalidad%' => 'detalle_modalidad',
			'%detalle_modalidad_lowercase%' => 'detalle_modalidad_lowercase',
			'%detalle_modalidad_tyc%' => 'detalle_modalidad_tyc',
			'%tipo_tarifa%' => 'tipo_tarifa',
			'%periodo%' => 'periodo',
			'%periodo_cobro%' => 'periodo_cobro',
			'%valor_periodo_ini%' => 'valor_periodo_ini',
			'%valor_periodo_fin%' => 'valor_periodo_fin',
			'%fecha_ini%' => 'fecha_ini',
			'%fecha_ini_primer_trabajo%' => 'fecha_ini_primer_trabajo',
			'%nota_transferencia%' => 'nota_transferencia',
			'%desde%' => 'desde',
			'%hasta%' => 'hasta',
			'%valor_fecha_ini_primer_trabajo%' => 'valor_fecha_ini_primer_trabajo',
			'%valor_fecha_fin_ultimo_trabajo%' => 'valor_fecha_fin_ultimo_trabajo',
			'%valor_fecha_ini_o_primer_trabajo%' => 'valor_fecha_ini_o_primer_trabajo',
			'%valor_fecha_ini%' => 'valor_fecha_ini',
			'%fecha_fin%' => 'fecha_fin',
			'%valor_fecha_fin%' => 'valor_fecha_fin',
			'%horas%' => 'horas',
			'%valor_horas%' => 'valor_horas',
			'%honorarios%' => 'honorarios',
			'%descuento%' => 'descuento',
			'%saldo%' => 'saldo',
			'%equivalente%' => 'equivalente',
			'%honorarios_con_lang%' => 'honorarios_con_lang',
			'%honorarios_totales%' => 'honorarios_totales',
			'%honorarios_mta%' => 'honorarios_mta',
			'%valor_honorarios_totales%' => 'valor_honorarios_totales',
			'%valor_honorarios_totales_moneda_total%' => 'valor_honorarios_totales_moneda_total',
			'%fees%' => 'fees',
			'%expenses%' => 'expenses',
			'%total_honorarios%' => 'total_honorarios',
			'%valor_honorarios_demo%' => 'valor_honorarios_demo',
			'%valor_honorarios%' => 'valor_honorarios',
			'%horas_decimales%' => 'horas_decimales',
			'%valor_horas_decimales%' => 'valor_horas_decimales',
			'%valor_honorarios_cyc%' => 'valor_honorarios_cyc',
			'%monedabase%' => 'monedabase',
			'%equivalente_a_la_fecha%' => 'equivalente_a_la_fecha',
			'%valor_honorarios_monedabase%' => 'valor_honorarios_monedabase',
			'%valor_honorarios_monedabase_tyc%' => 'valor_honorarios_monedabase_tyc',
			'%gastos%' => 'gastos',
			'%valor_gastos%' => 'valor_gastos',
			'%total_cobro%' => 'total_cobro',
			'%total_cobro_mta%' => 'total_cobro_mta',
			'%total_cobro_cyc%' => 'total_cobro_cyc',
			'%valor_total_cobro_demo%' => 'valor_total_cobro_demo',
			'%valor_total_cobro_cyc%' => 'valor_total_cobro_cyc',
			'%iva_cyc%' => 'iva_cyc',
			'%valor_iva_cyc%' => 'valor_iva_cyc',
			'%total_cyc%' => 'total_cyc',
			'%valor_total_cyc%' => 'valor_total_cyc',
			'%honorarios_y_gastos%' => 'honorarios_y_gastos',
			'%valor_total_cobro%' => 'valor_total_cobro',
			'%valor_total_cobro_sin_simbolo%' => 'valor_total_cobro_sin_simbolo',
			'%valor_uf%' => 'valor_uf',
			'%glosa_tipo_cambio_moneda%' => 'glosa_tipo_cambio_moneda',
			'%valor_tipo_cambio_moneda%' => 'valor_tipo_cambio_moneda',
			'%valor_bruto%' => 'valor_bruto',
			'%valor_descuento%' => 'valor_descuento',
			'%valor_equivalente%' => 'valor_equivalente',
			'%total_subtotal_cobro%' => 'total_subtotal_cobro',
			'%nota_disclaimer%' => 'nota_disclaimer',
		),
		'RESTAR_RETAINER' => array(
			'%retainer%' => 'retainer',
			'%valor_retainer%' => 'valor_retainer',
		),
		'DETALLE_COBRO_RETAINER' => array(
			'%horas_retainer%' => 'horas_retainer',
			'%valor_horas_retainer%' => 'valor_horas_retainer',
			'%horas_adicionales%' => 'horas_adicionales',
			'%valor_horas_adicionales%' => 'valor_horas_adicionales',
			'%honorarios_retainer%' => 'honorarios_retainer',
			'%valor_honorarios_retainer%' => 'valor_honorarios_retainer',
			'%honorarios_adicionales%' => 'honorarios_adicionales',
			'%valor_honorarios_adicionales%' => 'valor_honorarios_adicionales',
		),
		'DETALLE_TARIFA_ADICIONAL' => array(
			'%tarifa_adicional%' => 'tarifa_adicional',
			'%valores_tarifa_adicionales%' => 'valores_tarifa_adicionales',
		),
		'FACTURA_NUMERO' => array(
			'%factura_nro%' => 'factura_nro',
		),
		'NUMERO_FACTURA' => array(
			'%nro_factura%' => 'nro_factura',
		),
		'DETALLE_HONORARIOS' => array(
			'%horas%' => 'horas',
			'%valor_horas%' => 'valor_horas',
			'%honorarios%' => 'honorarios',
			'%valor_honorarios%' => 'valor_honorarios',
		),
		'DETALLE_GASTOS' => array(
			'%gastos%' => 'gastos',
			'%valor_gastos%' => 'valor_gastos',
		),
		'DETALLE_TRAMITES' => array(
			'%tramites%' => 'tramites',
			'%valor_tramites%' => 'valor_tramites',
		),
		'DETALLE_COBRO_MONEDA_TOTAL' => array(
			'%monedabase%' => 'monedabase',
			'%total_pagar%' => 'total_pagar',
			'%valor_honorarios_monedabase_demo%' => 'valor_honorarios_monedabase_demo',
			'%valor_honorarios_monedabase%' => 'valor_honorarios_monedabase',
		),
		'DETALLE_COBRO_DESCUENTO' => array(
			'%honorarios%' => 'honorarios',
			'%valor_honorarios%' => 'valor_honorarios',
			'%valor_descuento%' => 'valor_descuento',
			'%porcentaje_descuento%' => 'porcentaje_descuento',
			'%descuento%' => 'descuento',
			'%valor_honorarios_demo%' => 'valor_honorarios_demo',
			'%valor_descuento_demo%' => 'valor_descuento_demo',
			'%porcentaje_descuento_demo%' => 'porcentaje_descuento_demo',
			'%total_honorarios%' => 'total_honorarios',
			'%valor_honorarios_con_descuento%' => 'valor_honorarios_con_descuento',
		),
		'RESUMEN_CAP' => array(
			'%cap%' => 'cap',
			'%valor_cap%' => 'valor_cap',
			'%restante%' => 'restante',
			'%valor_restante%' => 'valor_restante',
		),
		'COBROS_DEL_CAP' => array(
			'%numero_cobro%' => 'numero_cobro',
			'%valor_cap_del_cobro%' => 'valor_cap_del_cobro',
		),
		'IMPUESTO' => array(
			'%impuesto%' => 'impuesto',
			'%impuesto_mta%' => 'impuesto_mta',
			'%valor_impuesto%' => 'valor_impuesto',
			'%valor_impuesto_honorarios%' => 'valor_impuesto_honorarios',
		),
		'GASTOS' => array(
			'%glosa_gastos%' => 'glosa_gastos',
			'%expenses%' => 'expenses',
			'%factura%' => 'factura',
			'%detalle_gastos%' => 'detalle_gastos',
		),
		'GASTOS_ENCABEZADO' => array(
			'%glosa_gastos%' => 'glosa_gastos',
			'%descripcion_gastos%' => 'descripcion_gastos',
			'%fecha%' => 'fecha',
			'%num_doc%' => 'num_doc',
			'%tipo_gasto%' => 'tipo_gasto',
			'%descripcion%' => 'descripcion',
			'%monto_original%' => 'monto_original',
			'%monto_moneda_total%' => 'monto_moneda_total',
			'%ordenado_por%' => 'ordenado_por',
			'%asunto_id%' => 'asunto_id',
			'%monto_impuesto_total%' => 'monto_impuesto_total',
			'%monto_moneda_total_con_impuesto%' => 'monto_moneda_total_con_impuesto',
			'%proveedor%' => 'proveedor',
		),
		'GASTOS_FILAS' => array(
			'%fecha%' => 'fecha',
			'%descripcion%' => 'descripcion',
			'%descripcion_b%' => 'descripcion_b',
			'%monto_original%' => 'monto_original',
			'%monto%' => 'monto',
			'%monto_moneda_total%' => 'monto_moneda_total',
			'%monto_moneda_total_sin_simbolo%' => 'monto_moneda_total_sin_simbolo',
			'%valor_codigo_asunto%' => 'valor_codigo_asunto',
			'%num_doc%' => 'num_doc',
			'%tipo_gasto%' => 'tipo_gasto',
			'%monto_impuesto_total%' => 'monto_impuesto_total',
			'%monto_moneda_total_con_impuesto%' => 'monto_moneda_total_con_impuesto',
			'%proveedor%' => 'proveedor',
			'%solicitante%' => 'solicitante',
		),
		'GASTOS_TOTAL' => array(
			'%total%' => 'total',
			'%glosa_total%' => 'glosa_total',
			'%sub_total_gastos%' => 'sub_total_gastos',
			'%total_gastos_moneda_total%' => 'total_gastos_moneda_total',
			'%glosa_total_moneda_base%' => 'glosa_total_moneda_base',
			'%valor_total_moneda_carta%' => 'valor_total_moneda_carta',
			'%valor_total_moneda_base%' => 'valor_total_moneda_base',
			'%valor_impuesto_monedabase%' => 'valor_impuesto_monedabase',
			'%valor_total_monedabase_con_impuesto%' => 'valor_total_monedabase_con_impuesto',
		),
		'CTA_CORRIENTE' => array(
			'%titulo_detalle_cuenta%' => 'titulo_detalle_cuenta',
			'%descripcion_cuenta%' => 'descripcion_cuenta',
			'%monto_cuenta%' => 'monto_cuenta',
		),
		'MOROSIDAD' => array(
			'%titulo_morosidad%' => 'titulo_morosidad',
		),
		'ADELANTOS' => array(
			'%titulo_adelantos%' => 'titulo_adelantos',
		),
		'COBROS_ADEUDADOS' => array(
			'%titulo_adelantos%' => 'titulo_adelantos',
		),
		'ADELANTOS_ENCABEZADO' => array(
			'%fecha%' => 'fecha',
			'%descripcion%' => 'descripcion',
			'%monto%' => 'monto',
			'%saldo%' => 'saldo',
		),
		'ADELANTOS_FILAS_TOTAL' => array(
			'%descripcion%' => 'descripcion',
			'%saldo_pago%' => 'saldo_pago',
			'%monto%' => 'monto',
			'%fecha%' => 'fecha',
		),
		'COBROS_ADEUDADOS_FILAS_TOTAL' => array(
			'%descripcion%' => 'descripcion',
			'%saldo_pago%' => 'saldo_pago',
			'%monto%' => 'monto',
			'%fecha%' => 'fecha',
		),
		'ENDOSO' => array(
			'%numero_cuenta_contrato%' => 'numero_cuenta_contrato',
			'%glosa_banco_contrato%' => 'glosa_banco_contrato',
			'%codigo_swift%' => 'codigo_swift',
			'%codigo_cci%' => 'codigo_cci',
			'%valor_total_sin_impuesto%' => 'valor_total_sin_impuesto',
			'%valor_impuesto%' => 'valor_impuesto',
			'%valor_total_con_impuesto%' => 'valor_total_con_impuesto',
			'%tipo_gbp_segun_moneda%' => 'tipo_gbp_segun_moneda',
			'%total_sin_impuesto%' => 'total_sin_impuesto',
			'%impuesto%' => 'impuesto',
			'%total_factura%' => 'total_factura',
			'%instrucciones_deposito%' => 'instrucciones_deposito',
			'%solicitud%' => 'solicitud',
		),
		'DESGLOSE_POR_ASUNTO_DETALLE' => array(
			'%glosa_asunto%' => 'glosa_asunto',
			'%simbolo%' => 'simbolo',
			'%honorarios_asunto%' => 'honorarios_asunto',
			'%gastos_asunto%' => 'gastos_asunto',
			'%tramites_asunto%' => 'tramites_asunto',
		),
		'DESGLOSE_POR_ASUNTO_TOTALES' => array(
			'%simbolo%' => 'simbolo',
			'%desglose_subtotal_hh%' => 'desglose_subtotal_hh',
			'%desglose_subtotal_gasto%' => 'desglose_subtotal_gasto',
			'%desglose_subtotal_tramite%' => 'desglose_subtotal_tramite',
			'%desglose_impuesto_hh%' => 'desglose_impuesto_hh',
			'%desglose_impuesto_gasto%' => 'desglose_impuesto_gasto',
			'%desglose_impuesto_tramite%' => 'desglose_impuesto_tramite',
			'%desglose_grantotal%' => 'desglose_grantotal',
			'%subtotales%' => 'subtotales',
			'%impuestos%' => 'impuestos',
			'%total_deuda%' => 'total_deuda',
		),
		'ASUNTOS' => array(
			'%salto_pagina_varios_asuntos%' => 'salto_pagina_varios_asuntos',
			'%salto_pagina_un_asunto%' => 'salto_pagina_un_asunto',
			'%asunto_extra%' => 'asunto_extra',
			'%glosa_asunto_sin_codigo_extra%' => 'glosa_asunto_sin_codigo_extra',
			'%asunto%' => 'asunto',
			'%asuntos_cliente%' => 'asuntos_cliente',
			'%etiqueta_cliente%' => 'etiqueta_cliente',
			'%codigo_cliente%' => 'codigo_cliente',
			'%glosa_cliente%' => 'glosa_cliente',
			'%glosa_asunto%' => 'glosa_asunto',
			'%glosa_asunto_sin_codigo%' => 'glosa_asunto_sin_codigo',
			'%glosa_asunto_codigo_area%' => 'glosa_asunto_codigo_area',
			'%valor_codigo_asunto%' => 'valor_codigo_asunto',
			'%codigo_cliente_secundario%' => 'codigo_cliente_secundario',
			'%valor_codigo_cliente_secundario%' => 'valor_codigo_cliente_secundario',
			'%contacto%' => 'contacto',
			'%valor_contacto%' => 'valor_contacto',
			'%registro%' => 'registro',
			'%telefono%' => 'telefono',
			'%valor_telefono%' => 'valor_telefono',
			'%espacio_trabajo%' => 'espacio_trabajo',
			'%servicios%' => 'servicios',
			'%hitos%' => 'hitos',
			'%espacio_tramite%' => 'espacio_tramite',
			'%servicios_tramites%' => 'servicios_tramites',
			'%codigo_asunto_mb%' => 'codigo_asunto_mb',
		),
		'HITOS_ENCABEZADO' => array(
			'%fecha%' => 'fecha',
			'%descripcion%' => 'descripcion',
			'%valor%' => 'valor',
		),
		'HITOS_FILAS' => array(
			'%fecha%' => 'fecha',
			'%descripcion%' => 'descripcion',
			'%valor_hitos%' => 'valor_hitos',
		),
		'HITOS_TOTAL' => array(
			'%total%' => 'total',
			'%total_hitos%' => 'total_hitos',
		),
		'TRAMITES_ENCABEZADO' => array(
			'%solicitante%' => 'solicitante',
			'%ordenado_por%' => 'ordenado_por',
			'%periodo%' => 'periodo',
			'%valor_periodo_ini%' => 'valor_periodo_ini',
			'%valor_periodo_fin%' => 'valor_periodo_fin',
			'%cliente%' => 'cliente',
			'%glosa_cliente%' => 'glosa_cliente',
			'%asunto%' => 'asunto',
			'%glosa_asunto%' => 'glosa_asunto',
			'%fecha%' => 'fecha',
			'%descripcion%' => 'descripcion',
			'%servicios_prestados%' => 'servicios_prestados',
			'%servicios_tramites%' => 'servicios_tramites',
			'%detalle_trabajo%' => 'detalle_trabajo',
			'%profesional%' => 'profesional',
			'%abogado%' => 'abogado',
			'%horas%' => 'horas',
			'%cobrable%' => 'cobrable',
			'%categoria_abogado%' => 'categoria_abogado',
			'%duracion_tramites%' => 'duracion_tramites',
			'%valor_tramites%' => 'valor_tramites',
			'%valor%' => 'valor',
			'%valor_siempre%' => 'valor_siempre',
			'%tarifa_fee%' => 'tarifa_fee',
		),
		'TRAMITES_FILAS' => array(
			'%iniciales%' => 'iniciales',
			'%fecha%' => 'fecha',
			'%descripcion%' => 'descripcion',
			'%valor%' => 'valor',
			'%duracion_tramites%' => 'duracion_tramites',
			'%valor_tramites%' => 'valor_tramites',
			'%profesional%' => 'profesional',
			'%duracion_decimal%' => 'duracion_decimal',
			'%duracion%' => 'duracion',
			'%valor_siempre%' => 'valor_siempre',
			'%glosa%' => 'glosa',
			'%cobrable%' => 'cobrable',
			'%abogado%' => 'abogado',
			'%categoria_abogado%' => 'categoria_abogado',
			'%TRAMITES_CATEGORIA%' => 'TRAMITES_CATEGORIA',
		),
		'TRAMITES_TOTAL' => array(
			'%glosa_tramites%' => 'glosa_tramites',
			'%glosa%' => 'glosa',
			'%duracion_decimal%' => 'duracion_decimal',
			'%duracion_tramites%' => 'duracion_tramites',
			'%duracion%' => 'duracion',
			'%valor_tramites%' => 'valor_tramites',
			'%valor_siempre%' => 'valor_siempre',
		),
		'TRABAJOS_ENCABEZADO' => array(
			'%td_solicitante%' => 'td_solicitante',
			'%solicitante%' => 'solicitante',
			'%id_asunto%' => 'id_asunto',
			'%tarifa_hora%' => 'tarifa_hora',
			'%importe%' => 'importe',
			'%ordenado_por%' => 'ordenado_por',
			'%ordenado_por_jjr%' => 'ordenado_por_jjr',
			'%periodo%' => 'periodo',
			'%valor_periodo_ini%' => 'valor_periodo_ini',
			'%valor_periodo_fin%' => 'valor_periodo_fin',
			'%cliente%' => 'cliente',
			'%glosa_cliente%' => 'glosa_cliente',
			'%asunto%' => 'asunto',
			'%glosa_asunto%' => 'glosa_asunto',
			'%fecha%' => 'fecha',
			'%descripcion%' => 'descripcion',
			'%servicios_prestados%' => 'servicios_prestados',
			'%detalle_trabajo%' => 'detalle_trabajo',
			'%profesional%' => 'profesional',
			'%duracion_cobrable%' => 'duracion_cobrable',
			'%monto_total%' => 'monto_total',
			'%staff%' => 'staff',
			'%abogado%' => 'abogado',
			'%horas%' => 'horas',
			'%monto%' => 'monto',
			'%cobrable%' => 'cobrable',
			'%categoria_abogado%' => 'categoria_abogado',
			'%tarifa%' => 'tarifa',
			'%td_retainer%' => 'td_retainer',
			'%duracion_retainer%' => 'duracion_retainer',
			'%duracion_trabajada_bmahj%' => 'duracion_trabajada_bmahj',
			'%duracion_descontada_bmahj%' => 'duracion_descontada_bmahj',
			'%duracion_bmahj%' => 'duracion_bmahj',
			'%duracion_trabajada%' => 'duracion_trabajada',
			'%duracion_descontada%' => 'duracion_descontada',
			'%duracion%' => 'duracion',
			'%duracion_tyc%' => 'duracion_tyc',
			'%valor%' => 'valor',
			'%valor_siempre%' => 'valor_siempre',
			'%tarifa_fee%' => 'tarifa_fee',
			'%td_categoria%' => 'td_categoria',
			'%categoria%' => 'categoria',
			'%td_tarifa%' => 'td_tarifa',
			'%td_tarifa_ajustada%' => 'td_tarifa_ajustada',
			'%td_importe%' => 'td_importe',
			'%td_importe_ajustado%' => 'td_importe_ajustado',
		),
		'TRABAJOS_FILAS' => array(
			'%valor_codigo_asunto%' => 'valor_codigo_asunto',
			'%fecha%' => 'fecha',
			'%descripcion%' => 'descripcion',
			'%td_solicitante%' => 'td_solicitante',
			'%solicitante%' => 'solicitante',
			'%username%' => 'username',
			'%profesional%' => 'profesional',
			'%td_categoria%' => 'td_categoria',
			'%categoria%' => 'categoria',
			'%td_tarifa%' => 'td_tarifa',
			'%td_tarifa_ajustada%' => 'td_tarifa_ajustada',
			'%tarifa%' => 'tarifa',
			'%tarifa_ajustada%' => 'tarifa_ajustada',
			'%td_importe%' => 'td_importe',
			'%td_importe_ajustado%' => 'td_importe_ajustado',
			'%importe%' => 'importe',
			'%importe_ajustado%' => 'importe_ajustado',
			'%paridad%' => 'paridad',
			'%iniciales%' => 'iniciales',
			'%td_retainer%' => 'td_retainer',
			'%duracion_retainer%' => 'duracion_retainer',
			'%duracion_decimal_trabajada%' => 'duracion_decimal_trabajada',
			'%duracion_trabajada%' => 'duracion_trabajada',
			'%duracion_decduracion_trabajadaimal_descontada%' => 'duracion_decduracion_trabajadaimal_descontada',
			'%duracion_descontada%' => 'duracion_descontada',
			'%duracion_decimal%' => 'duracion_decimal',
			'%duracion%' => 'duracion',
			'%duracion_decimal_descontada%' => 'duracion_decimal_descontada',
			'%cobrable%' => 'cobrable',
			'%valor%' => 'valor',
			'%valor_cyc%' => 'valor_cyc',
			'%valor_con_moneda%' => 'valor_con_moneda',
			'%valor_siempre%' => 'valor_siempre',
			'%glosa%' => 'glosa',
			'%TRABAJOS_CATEGORIA%' => 'TRABAJOS_CATEGORIA',
		),
		'TRABAJOS_TOTAL' => array(
			'%sub_total_fees%' => 'sub_total_fees',
			'%td_solicitante%' => 'td_solicitante',
			'%td_categoria%' => 'td_categoria',
			'%td_tarifa%' => 'td_tarifa',
			'%td_tarifa_ajustada%' => 'td_tarifa_ajustada',
			'%td_importe%' => 'td_importe',
			'%td_importe_ajustado%' => 'td_importe_ajustado',
			'%importe%' => 'importe',
			'%importe_ajustado%' => 'importe_ajustado',
			'%td_retainer%' => 'td_retainer',
			'%duracion_retainer%' => 'duracion_retainer',
			'%duracion_decimal_trabajada%' => 'duracion_decimal_trabajada',
			'%duracion_trabajada%' => 'duracion_trabajada',
			'%duracion_descontada%' => 'duracion_descontada',
			'%duracion_decimal_descontada%' => 'duracion_decimal_descontada',
			'%duracion_decimal%' => 'duracion_decimal',
			'%duracion%' => 'duracion',
			'%glosa%' => 'glosa',
			'%cobrable%' => 'cobrable',
			'%valor%' => 'valor',
			'%valor_cyc%' => 'valor_cyc',
			'%valor_siempre%' => 'valor_siempre',
		),
		'DETALLE_PROFESIONAL' => array(
			'%glosa_profesional%' => 'glosa_profesional',
			'%detalle_tiempo_por_abogado%' => 'detalle_tiempo_por_abogado',
			'%detalle_honorarios%' => 'detalle_honorarios',
		),
		'PROFESIONAL_FILAS' => array(
			'%nombre%' => 'nombre',
			'%hrs_retainer%' => 'hrs_retainer',
			'%hrs_descontadas%' => 'hrs_descontadas',
			'%hrs_descontadas_real%' => 'hrs_descontadas_real',
			'%hh%' => 'hh',
			'%valor_hh%' => 'valor_hh',
			'%valor_hh_cyc%' => 'valor_hh_cyc',
			'%iniciales%' => 'iniciales',
			'%username%' => 'username',
			'%hrs_trabajadas_real%' => 'hrs_trabajadas_real',
			'%hrs_trabajadas%' => 'hrs_trabajadas',
			'%horas_retainer%' => 'horas_retainer',
			'%columna_horas_no_cobrables%' => 'columna_horas_no_cobrables',
			'%total%' => 'total',
			'%total_cyc%' => 'total_cyc',
			'%hrs_trabajadas_previo%' => 'hrs_trabajadas_previo',
			'%horas_trabajadas_especial%' => 'horas_trabajadas_especial',
			'%horas_cobrables%' => 'horas_cobrables',
			'%horas%' => 'horas',
			'%tarifa_horas%' => 'tarifa_horas',
			'%total_horas%' => 'total_horas',
		),
		'PROFESIONAL_TOTAL' => array(
			'%hrs_retainer%' => 'hrs_retainer',
			'%hrs_descontadas%' => 'hrs_descontadas',
			'%hrs_descontadas_real%' => 'hrs_descontadas_real',
			'%hh%' => 'hh',
			'%valor_hh%' => 'valor_hh',
			'%valor_hh_cyc%' => 'valor_hh_cyc',
			'%glosa%' => 'glosa',
			'%glosa_honorarios%' => 'glosa_honorarios',
			'%hrs_trabajadas_real%' => 'hrs_trabajadas_real',
			'%hrs_trabajadas%' => 'hrs_trabajadas',
			'%hrs_trabajadas_previo%' => 'hrs_trabajadas_previo',
			'%horas_trabajadas_especial%' => 'horas_trabajadas_especial',
			'%horas_cobrables%' => 'horas_cobrables',
			'%horas_retainer%' => 'horas_retainer',
			'%columna_horas_no_cobrables%' => 'columna_horas_no_cobrables',
			'%total%' => 'total',
			'%total_cyc%' => 'total_cyc',
			'%total_honorarios%' => 'total_honorarios',
			'%horas%' => 'horas',
		),
		'DETALLE_PROFESIONAL_RETAINER' => array(
			'%retainer%' => 'retainer',
			'%valor_retainer%' => 'valor_retainer',
		),
		'DETALLE_COBRO_MONEDA_TOTAL_POR_ASUNTO' => array(
			'%valor_honorarios_monedabase%' => 'valor_honorarios_monedabase',
		),
		'CTA_CORRIENTE_SALDO_INICIAL' => array(
			'%saldo_inicial_cuenta%' => 'saldo_inicial_cuenta',
			'%valor_saldo_inicial_cuenta%' => 'valor_saldo_inicial_cuenta',
		),
		'CTA_CORRIENTE_MOVIMIENTOS_ENCABEZADO' => array(
			'%movimientos%' => 'movimientos',
			'%fecha%' => 'fecha',
			'%descripcion%' => 'descripcion',
			'%egreso%' => 'egreso',
			'%ingreso%' => 'ingreso',
		),
		'CTA_CORRIENTE_MOVIMIENTOS_FILAS' => array(
			'%fecha%' => 'fecha',
			'%descripcion%' => 'descripcion',
			'%monto_egreso%' => 'monto_egreso',
			'%monto_ingreso%' => 'monto_ingreso',
		),
		'CTA_CORRIENTE_MOVIMIENTOS_TOTAL' => array(
			'%total%' => 'total',
			'%total_monto_egreso%' => 'total_monto_egreso',
			'%total_monto_ingreso%' => 'total_monto_ingreso',
			'%saldo_periodo%' => 'saldo_periodo',
			'%total_monto_gastos%' => 'total_monto_gastos',
		),
		'CTA_CORRIENTE_SALDO_FINAL' => array(
			'%saldo_final_cuenta%' => 'saldo_final_cuenta',
			'%valor_saldo_final_cuenta%' => 'valor_saldo_final_cuenta',
		),
		'TIPO_CAMBIO' => array(
			'%titulo_tipo_cambio%' => 'titulo_tipo_cambio',
		),
		'MOROSIDAD_ENCABEZADO' => array(
			'%numero_nota_cobro%' => 'numero_nota_cobro',
			'%numero_factura%' => 'numero_factura',
			'%fecha%' => 'fecha',
			'%moneda%' => 'moneda',
			'%monto_moroso%' => 'monto_moroso',
		),
		'MOROSIDAD_FILAS' => array(
			'%numero_nota_cobro%' => 'numero_nota_cobro',
			'%numero_factura%' => 'numero_factura',
			'%fecha%' => 'fecha',
			'%moneda%' => 'moneda',
			'%moneda_total%' => 'moneda_total',
			'%monto_moroso%' => 'monto_moroso',
			'%monto_moroso_documento%' => 'monto_moroso_documento',
			'%monto_moroso_moneda_total%' => 'monto_moroso_moneda_total',
		),
		'MOROSIDAD_HONORARIOS_TOTAL' => array(
			'%numero_nota_cobro%' => 'numero_nota_cobro',
			'%numero_factura%' => 'numero_factura',
			'%fecha%' => 'fecha',
			'%moneda%' => 'moneda',
			'%monto_moroso_documento%' => 'monto_moroso_documento',
			'%monto_moroso%' => 'monto_moroso',
		),
		'MOROSIDAD_GASTOS' => array(
			'%numero_nota_cobro%' => 'numero_nota_cobro',
			'%numero_factura%' => 'numero_factura',
			'%fecha%' => 'fecha',
			'%moneda%' => 'moneda',
			'%monto_moroso_documento%' => 'monto_moroso_documento',
			'%monto_moroso%' => 'monto_moroso',
		),
		'MOROSIDAD_TOTAL' => array(
			'%numero_nota_cobro%' => 'numero_nota_cobro',
			'%numero_factura%' => 'numero_factura',
			'%fecha%' => 'fecha',
			'%moneda%' => 'moneda',
			'%monto_moroso_documento%' => 'monto_moroso_documento',
			'%monto_moroso%' => 'monto_moroso',
			'%nota%' => 'nota',
		),
		'GLOSA_ESPECIAL' => array(
			'%glosa_especial%' => 'glosa_especial',
		),
		'SALTO_PAGINA' => array(
		),
		'CLIENTE' => array(
			'%materia%' => 'materia',
			'%glosa_asunto_sin_codigo%' => 'glosa_asunto_sin_codigo',
			'%valor_codigo_asunto%' => 'valor_codigo_asunto',
			'%glosa_asuntos_sin_codigo%' => 'glosa_asuntos_sin_codigo',
			'%glosa_numero_cobro%' => 'glosa_numero_cobro',
			'%numero_cobro%' => 'numero_cobro',
			'%servicios_prestados%' => 'servicios_prestados',
			'%a%' => 'a',
			'%a_min%' => 'a_min',
			'%cliente%' => 'cliente',
			'%nombre_cliente%' => 'nombre_cliente',
			'%factura_razon_social_o_nombre_cliente%' => 'factura_razon_social_o_nombre_cliente',
			'%factura%' => 'factura',
			'%fecha_liquidacion%' => 'fecha_liquidacion',
			'%cliente_corporativo%' => 'cliente_corporativo',
			'%id_asunto%' => 'id_asunto',
			'%codigo_cliente%' => 'codigo_cliente',
			'%fecha_emision%' => 'fecha_emision',
			'%glosa_cliente%' => 'glosa_cliente',
			'%direccion%' => 'direccion',
			'%valor_direccion%' => 'valor_direccion',
			'%valor_direccion_uc%' => 'valor_direccion_uc',
			'%direccion_carta%' => 'direccion_carta',
			'%rut%' => 'rut',
			'%rut_minuscula%' => 'rut_minuscula',
			'%valor_rut_sin_formato%' => 'valor_rut_sin_formato',
			'%valor_rut%' => 'valor_rut',
			'%giro_factura%' => 'giro_factura',
			'%giro_factura_valor%' => 'giro_factura_valor',
			'%contacto%' => 'contacto',
			'%atencion%' => 'atencion',
			'%valor_contacto%' => 'valor_contacto',
			'%atte%' => 'atte',
			'%telefono%' => 'telefono',
			'%valor_telefono%' => 'valor_telefono',
			'%numero_factura%' => 'numero_factura',
			'%nombre_pais%' => 'nombre_pais',
			'%nombre_pais_mayuscula%' => 'nombre_pais_mayuscula',
		),
		'DETALLES_PAGOS' => array(
			'%descripcion%' => 'descripcion',
			'%saldo_pago%' => 'saldo_pago',
		),
		'DETALLES_PAGOS_CONTRATO' => array(
			'%descripcion%' => 'descripcion',
			'%saldo_pago%' => 'saldo_pago',
		),
		'RESUMEN_PROFESIONAL' => array(
			'%glosa_profesional%' => 'glosa_profesional',
			'%resumen_profesional%' => 'resumen_profesional',
			'%nombre%' => 'nombre',
			'%categoria%' => 'categoria',
			'%hh_demo%' => 'hh_demo',
			'%hh_trabajada%' => 'hh_trabajada',
			'%td_tarifa%' => 'td_tarifa',
			'%td_tarifa_ajustada%' => 'td_tarifa_ajustada',
			'%tarifa_horas_demo%' => 'tarifa_horas_demo',
			'%td_importe%' => 'td_importe',
			'%td_importe_ajustado%' => 'td_importe_ajustado',
			'%total_horas_demo%' => 'total_horas_demo',
			'%td_descontada%' => 'td_descontada',
			'%td_cobrable%' => 'td_cobrable',
			'%td_retainer%' => 'td_retainer',
			'%glosa%' => 'glosa',
			'%username%' => 'username',
			'%iniciales%' => 'iniciales',
			'%hrs_trabajadas%' => 'hrs_trabajadas',
			'%hrs_trabajadas_vio%' => 'hrs_trabajadas_vio',
			'%hrs_retainer%' => 'hrs_retainer',
			'%hrs_retainer_vio%' => 'hrs_retainer_vio',
			'%hrs_descontadas%' => 'hrs_descontadas',
			'%hh_descontada%' => 'hh_descontada',
			'%hh_cobrable%' => 'hh_cobrable',
			'%hh_retainer%' => 'hh_retainer',
			'%hh%' => 'hh',
			'%tarifa_horas%' => 'tarifa_horas',
			'%tarifa_horas_ajustada%' => 'tarifa_horas_ajustada',
			'%total_horas%' => 'total_horas',
			'%total_horas_ajustado%' => 'total_horas_ajustado',
			'%porcentaje_participacion%' => 'porcentaje_participacion',
			'%columna_horas_no_cobrables%' => 'columna_horas_no_cobrables',
			'%valor_retainer%' => 'valor_retainer',
			'%valor_retainer_vio%' => 'valor_retainer_vio',
			'%valor_cobrado_hh%' => 'valor_cobrado_hh',
			'%total%' => 'total',
			'%RESUMEN_PROFESIONAL_ENCABEZADO%' => 'RESUMEN_PROFESIONAL_ENCABEZADO',
			'%RESUMEN_PROFESIONAL_FILAS%' => 'RESUMEN_PROFESIONAL_FILAS',
			'%RESUMEN_PROFESIONAL_TOTAL%' => 'RESUMEN_PROFESIONAL_TOTAL',
		),
		'RESUMEN_PROFESIONAL_POR_CATEGORIA' => array(
			'%glosa_profesional%' => 'glosa_profesional',
			'%nombre%' => 'nombre',
			'%iniciales%' => 'iniciales',
			'%hrs_trabajadas%' => 'hrs_trabajadas',
			'%hrs_retainer%' => 'hrs_retainer',
			'%hrs_descontadas%' => 'hrs_descontadas',
			'%hh%' => 'hh',
			'%total_horas%' => 'total_horas',
			'%tarifa_horas%' => 'tarifa_horas',
			'%RESUMEN_PROFESIONAL_FILAS%' => 'RESUMEN_PROFESIONAL_FILAS',
			'%glosa%' => 'glosa',
			'%total%' => 'total',
		),
		'RESUMEN_PROFESIONAL_ENCABEZADO' => array(
			'%nombre%' => 'nombre',
			'%hrs_retainer%' => 'hrs_retainer',
			'%hrs_mins_retainer%' => 'hrs_mins_retainer',
			'%hrs_trabajadas%' => 'hrs_trabajadas',
			'%hrs_descontadas%' => 'hrs_descontadas',
			'%hrs_mins_trabajadas%' => 'hrs_mins_trabajadas',
			'%hrs_mins_descontadas%' => 'hrs_mins_descontadas',
		),
		'PROFESIONAL_ENCABEZADO' => array(
			'%horas_trabajadas%' => 'horas_trabajadas',
			'%retainer%' => 'retainer',
			'%extraordinario%' => 'extraordinario',
			'%simbolo_moneda_2%' => 'simbolo_moneda_2',
			'%nombre%' => 'nombre',
			'%valor_hh%' => 'valor_hh',
			'%hrs_trabajadas%' => 'hrs_trabajadas',
			'%porcentaje_participacion%' => 'porcentaje_participacion',
			'%hrs_retainer%' => 'hrs_retainer',
			'%valor_retainer%' => 'valor_retainer',
			'%hh%' => 'hh',
			'%valor_cobrado_hh%' => 'valor_cobrado_hh',
			'%fayca_hrs_descontadas%' => 'fayca_hrs_descontadas',
			'%hrs_mins_trabajadas%' => 'hrs_mins_trabajadas',
			'%hrs_trabajadas_real%' => 'hrs_trabajadas_real',
			'%hrs_mins_trabajadas_real%' => 'hrs_mins_trabajadas_real',
			'%hrs_descontadas_real%' => 'hrs_descontadas_real',
			'%horas_cobrables%' => 'horas_cobrables',
			'%hrs_mins_descontadas_real%' => 'hrs_mins_descontadas_real',
			'%horas_mins_cobrables%' => 'horas_mins_cobrables',
			'%hrs_mins_retainer%' => 'hrs_mins_retainer',
			'%columna_horas_no_cobrables_top%' => 'columna_horas_no_cobrables_top',
			'%columna_horas_no_cobrables%' => 'columna_horas_no_cobrables',
			'%hrs_descontadas%' => 'hrs_descontadas',
			'%hrs_mins_descontadas%' => 'hrs_mins_descontadas',
			'%hrs_trabajadas_previo%' => 'hrs_trabajadas_previo',
			'%hrs_mins_trabajadas_previo%' => 'hrs_mins_trabajadas_previo',
			'%abogados%' => 'abogados',
			'%hh_trabajada%' => 'hh_trabajada',
			'%td_descontada%' => 'td_descontada',
			'%hh_cobrable%' => 'hh_cobrable',
			'%hh_descontada%' => 'hh_descontada',
			'%td_cobrable%' => 'td_cobrable',
			'%td_retainer%' => 'td_retainer',
			'%hh_retainer%' => 'hh_retainer',
			'%hh_mins%' => 'hh_mins',
			'%horas%' => 'horas',
			'%horas_retainer%' => 'horas_retainer',
			'%horas_mins%' => 'horas_mins',
			'%horas_mins_retainer%' => 'horas_mins_retainer',
			'%td_tarifa%' => 'td_tarifa',
			'%valor_horas%' => 'valor_horas',
			'%td_tarifa_ajustada%' => 'td_tarifa_ajustada',
			'%tarifa_fee%' => 'tarifa_fee',
			'%simbolo_moneda%' => 'simbolo_moneda',
			'%td_importe%' => 'td_importe',
			'%td_importe_ajustado%' => 'td_importe_ajustado',
			'%importe%' => 'importe',
			'%importe_ajustado%' => 'importe_ajustado',
			'%total%' => 'total',
			'%honorarios%' => 'honorarios',
			'%categoria%' => 'categoria',
			'%staff%' => 'staff',
			'%valor_siempre%' => 'valor_siempre',
			'%nombre_profesional%' => 'nombre_profesional',
			'%profesional%' => 'profesional',
			'%hora_tarificada%' => 'hora_tarificada',
		),
	);

	function __construct($sesion, $fields = "", $params = "") {
		parent::__construct($sesion, $fields);
		$this->x_resultados = array();
		$valorsinespacio = '&nbsp;';
		if (Conf::GetConf($this->sesion, 'ValorSinEspacio')) {
			$valorsinespacio = '';
		}
		$this->espacio = $valorsinespacio;
	}

	function NuevoRegistro() {
		return array(
			'descripcion' => 'Nueva nota de cobro',
			'html_header' => '',
			'html_pie' => '',
			'cobro_template' => '',
			'cobro_css' => '',
			'pdf_encabezado_imagen' => '',
			'pdf_encabezado_texto' => ''
		);
	}

	public function ObtenerCarta($id = null) {
		if (empty($id)) {
			return $this->NuevoRegistro() + array('secciones' => array(key($this->secciones) => ''));
		}

		$query = "SELECT * FROM {$this->carta_tabla} WHERE {$this->carta_id} = '$id'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		$carta = mysql_fetch_assoc($resp);
		$parser = new TemplateParser($carta[$this->carta_formato]);
		$carta['secciones'] = $parser->tags;
		return $carta;
	}

	function GuardarCarta($data) {
		if (isset($data['secciones'])) {
			$formato = '';
			foreach ($data['secciones'] as $seccion => $html) {
				$formato .= "\n###$seccion###\n$html\n";
			}
			unset($data['secciones']);
			$data[$this->carta_formato] = $formato;
		} else {
			$data[$this->carta_formato] = $data['formato'];
		}
		$Carta = new Objeto($this->sesion, array(), '', $this->carta_tabla, $this->carta_id);
		$Carta->guardar_fecha = false;
		$Carta->editable_fields = array_keys($data);
		$Carta->Fill($data, true);
		if ($Carta->Write()) {
			return $Carta->fields[$this->carta_id];
		}
		return false;
	}

	function PrevisualizarDocumento($data, $id_cobro) {
		$formato = $data['formato'];
		$html = $this->ReemplazarHTML($formato, $id_cobro);
		$doc = new DocGenerator($html, $data['cobro_css'], $this->fields['opc_papel'], $this->fields['opc_ver_numpag'], 'PORTRAIT', $data['margen_superior'], $data['margen_derecho'], $data['margen_inferior'], $data['margen_izquierdo'], $this->fields['estado']);
		libxml_use_internal_errors(true);
		$doc->output('previsualizacion_carta.doc');
		exit;
	}

	function PrevisualizarDocumentoHtml($formato_html, $id_cobro) {
		return $this->ReemplazarHTML($formato_html, $id_cobro);
	}

	function PrevisualizarValores($id_cobro) {
		$html = '';
		$secciones = array(key($this->secciones));
		foreach ($this->secciones as $subsecciones) {
			$secciones = array_merge($secciones, array_keys($subsecciones));
		}
		foreach ($secciones as $seccion) {
			$html .= "\n\n###$seccion###\n<tr><th colspan=3>$seccion</th></tr>\n\n";

			if (isset($this->diccionario[$seccion])) {
				foreach ($this->diccionario[$seccion] as $tag => $desc_tag) {
					$html .= '<tr><td>' . str_replace('%', '&#37;', $tag) . "</td><td>$tag</td><td>" . str_replace('%', '&#37;', $desc_tag) . "</td></tr>\n";
				}
			}
			if (isset($this->secciones[$seccion])) {
				foreach (array_keys($this->secciones[$seccion]) as $subseccion) {
					$html .= "\n%$subseccion%\n";
				}
			}
		}
		return '<table border="1">' . $this->ReemplazarHTML($html, $id_cobro) . '</table>';
	}

	function StrToNumber($str) {
		$legalChars = "%[^0-9\-\ ]%";
		$str = preg_replace($legalChars, "", $str);
		return number_format((float) $str, 0, ',', '.');
	}

	function ReemplazarHTML($html, $id_cobro) {
		$parser = new TemplateParser($html);

		if (empty($id_cobro)) {
			$query = 'SELECT id_cobro FROM cobro ORDER BY id_cobro DESC LIMIT 1';
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			list($id_cobro) = mysql_fetch_array($resp);
		}
		$this->Load($id_cobro);
		return $this->GenerarEjemplo($parser);
	}

	function GenerarEjemplo($parser) {
		return $this->GeneraHTMLCobro(false, $parser, 2);
	}

	function ParametrosGeneracion() {

		// Para mostrar un resumen de horas de cada profesional al principio del documento.
		global $resumen_profesional_id_usuario;
		global $resumen_profesional_nombre;
		global $resumen_profesional_hrs_trabajadas;
		global $resumen_profesional_hrs_retainer;
		global $resumen_profesional_hrs_descontadas;
		global $resumen_profesional_hh;
		global $resumen_profesional_valor_hh;
		global $resumen_profesional_categoria;
		global $resumen_profesional_id_categoria;
		global $resumen_profesionales;
		$resumen_profesional_id_usuario = array();
		$resumen_profesional_nombre = array();
		$resumen_profesional_hrs_trabajadas = array();
		$resumen_profesional_hrs_retainer = array();
		$resumen_profesional_hrs_descontadas = array();
		$resumen_profesional_hh = array();
		$resumen_profesional_valor_hh = array();
		$resumen_profesional_categoria = array();
		$resumen_profesional_id_categoria = array();
		$resumen_profesionales = array();

		global $contrato;
		$contrato = new Contrato($this->sesion);
		$contrato->Load($this->fields['id_contrato']);

		global $x_detalle_profesional;
		global $x_resumen_profesional;
		global $x_factor_ajuste;
		list( $x_detalle_profesional, $x_resumen_profesional, $x_factor_ajuste ) = $this->DetalleProfesional();
		global $x_resultados;
		$x_resultados = UtilesApp::ProcesaCobroIdMoneda($this->sesion, $this->fields['id_cobro']);
		$this->x_resultados = $x_resultados;

		global $x_cobro_gastos;
		$x_cobro_gastos = UtilesApp::ProcesaGastosCobro($this->sesion, $this->fields['id_cobro']);

		$lang = $this->fields['codigo_idioma'];

		$cliente = new Cliente($this->sesion);
		$cliente->LoadByCodigo($this->fields['codigo_cliente']);

		global $cobro_moneda;
		$cobro_moneda = new CobroMoneda($this->sesion);
		$cobro_moneda->Load($this->fields['id_cobro']);

		global $moneda_total;
		$moneda_total = new Objeto($this->sesion, '', '', 'prm_moneda', 'id_moneda');
		$moneda_total->Load($this->fields['opc_moneda_total'] > 0 ? $this->fields['opc_moneda_total'] : 1);

		$tipo_cambio_moneda_total = $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'];
		if ($tipo_cambio_moneda_total == 0) {
			$tipo_cambio_moneda_total = 1;
		}

		$idioma = new Objeto($this->sesion, '', '', 'prm_idioma', 'codigo_idioma');
		$idioma->Load($lang);

		// Moneda
		$moneda = new Objeto($this->sesion, '', '', 'prm_moneda', 'id_moneda');
		$moneda->Load($this->fields['id_moneda']);

		$moneda_base = new Objeto($this->sesion, '', '', 'prm_moneda', 'id_moneda');
		$moneda_base->Load($this->fields['id_moneda_base']);

		//Moneda cliente
		$moneda_cli = new Objeto($this->sesion, '', '', 'prm_moneda', 'id_moneda');
		$moneda_cli->Load($cliente->fields['id_moneda']);
		$moneda_cliente_cambio = $cobro_moneda->moneda[$cliente->fields['id_moneda']]['tipo_cambio'];

		if ($this->fields['codigo_idioma'] == 'es') {
			setlocale(LC_ALL, "es_ES");
		} else if ($this->fields['codigo_idioma'] == 'en') {
			setlocale(LC_ALL, 'en_US.UTF-8');
		}

		return compact('moneda_cliente_cambio', 'moneda_cli', 'lang', 'html2', 'idioma', 'cliente', 'moneda', 'moneda_base', 'trabajo', 'profesionales', 'gasto', 'totales', 'tipo_cambio_moneda_total', 'asunto');
	}

	function GeneraHTMLCobro($masivo = false, $formato = '', $funcion = '', $mostrar_asuntos_cobrables_sin_horas = FALSE) {
		global $masi;
		$masi = $masivo;

		$parametros = $this->ParametrosGeneracion();
		extract($parametros);

		//Usa el segundo formato de nota de cobro
		//solo si lo tiene definido en el conf y solo tiene gastos

		$solo_gastos = true;

		for ($k = 0; $k < count($this->asuntos); $k++) {
			$asunto = new Asunto($this->sesion);
			$asunto->LoadByCodigo($this->asuntos[$k]);
			$query = "SELECT SUM(TIME_TO_SEC(duracion))
						FROM trabajo AS t2
							LEFT JOIN cobro on t2.id_cobro=cobro.id_cobro
						WHERE t2.cobrable = 1
							AND t2.codigo_asunto='" . $asunto->fields['codigo_asunto'] . "'
							AND cobro.id_cobro='" . $this->fields['id_cobro'] . "'";

			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			list($total_monto_trabajado) = mysql_fetch_array($resp);
			if ($total_monto_trabajado > 0 || $this->fields['monto_subtotal'] > 0) {
				$solo_gastos = false;
			}
		}

		if (Conf::GetConf($this->sesion, 'CSSSoloGastos')) {
			if ($solo_gastos && Conf::GetConf($this->sesion, 'CSSSoloGastos')) {
				$formato = 2;
			}
		}

		$templateData_carta = UtilesApp::TemplateCarta($this->sesion, $this->fields['id_carta']);
		$cssData = UtilesApp::TemplateCartaCSS($this->sesion, $this->fields['id_carta']);
		$parser_carta = new TemplateParser($templateData_carta);

		if (is_numeric($formato) || $formato == '') {
			// buscar formato, de no existir buscar el primero
			$CobroRtf = new CobroRtf($this->sesion);
			$CobroRtf->Load($formato);

			if (!$CobroRtf->Loaded()) {
				if ($CobroRtf->loadFirst()) {
					$formato = $CobroRtf->fields['id_formato'];
				}
			}

			$templateData = UtilesApp::TemplateCobro($this->sesion, $formato);
			$cssData .= UtilesApp::CSSCobro($this->sesion, $formato);
			$parser = new TemplateParser($templateData);
		} else {
			$parser = $formato;
		}

		/*
		 * $this->fields['modalidad_calculo'] == 1, hacer calculo de forma nueva con la funcion ProcesaCobroIdMoneda
		 * $this->fields['modalidad_calculo'] == 0, hacer calculo de forma antigua
		 */

		if (empty($funcion)) {
			$funcion = $this->fields['modalidad_calculo'] == 1 ? 2 : 1;
		}

		$generador = 'GenerarDocumento' . ($funcion == 2 ? '2' : '');

		$nuevomodulofactura = Conf::GetConf($this->sesion, 'NuevoModuloFactura');
		$facturasRS = $this->FacturasDelContrato($this->sesion, $nuevomodulofactura);
		$totalescontrato = $this->TotalesDelContrato($facturasRS, $nuevomodulofactura, $this->fields['id_cobro']);

		$this->set_detalle_en_asuntos(FALSE);
		$this->querys_detalle_en_asuntos();

		return $this->$generador($parser, 'INFORME', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto, $mostrar_asuntos_cobrables_sin_horas);
	}

	/**
	 *
	 * Funci�n para determinar si se debe mostrar o no los asuntos cobrados sin horas,
	 * esto se muestra s�lo cuando no hay detalle acerca de alg�n otro asunto.
	 * Las querys determinan si los asuntos tienen informaci�n a ser mostrada, en caso
	 * que as� sea no se muestran los asuntos cobrables sin hora, en caso contrario s�.
	 *
	 */
	private function querys_detalle_en_asuntos() {
		$criteria = new Criteria($this->sesion);
		$criteria->add_select('COUNT(*)', 'total')
				->add_from('tramite')
				->add_restriction(CriteriaRestriction::equals('id_cobro', $this->fields['id_cobro']))
				->add_restriction(CriteriaRestriction::in('codigo_asunto', $this->asuntos));

		try {
			$result = $criteria->run();
			$this->set_detalle_en_asuntos($result[0]['total'] == 0 ? FALSE : TRUE);
		} catch (Exception $e) {
			echo "Error: {$e} {$criteria->__toString()}";
		}

		$criteria = new Criteria($this->sesion);
		$criteria->add_select('COUNT(*)', 'total')
				->add_from('trabajo')
				->add_restriction(CriteriaRestriction::equals('id_cobro', $this->fields['id_cobro']))
				->add_restriction(CriteriaRestriction::equals('id_tramite', 0))
				->add_restriction(CriteriaRestriction::in('codigo_asunto', $this->asuntos));

		try {
			$result = $criteria->run();
			$this->set_detalle_en_asuntos(($result[0]['total'] == 0 ? FALSE : TRUE) || $this->get_detalle_en_asuntos());
		} catch (Exception $e) {
			echo "Error: {$e} {$criteria->__toString()}";
		}

		$criteria = new Criteria($this->sesion);
		$criteria->add_select('COUNT(*)', 'total')
				->add_from('cta_corriente')
				->add_restriction(CriteriaRestriction::equals('id_cobro', $this->fields['id_cobro']))
				->add_restriction(CriteriaRestriction::in('codigo_asunto', $this->asuntos));

		try {
			$result = $criteria->run();
			$this->set_detalle_en_asuntos(($result[0]['total'] == 0 ? FALSE : TRUE) || $this->get_detalle_en_asuntos());
		} catch (Exception $e) {
			echo "Error: {$e} {$criteria->__toString()}";
		}
	}

	/**
	 *
	 * Setea valor a variable $detalle_en_asuntos
	 *
	 * @param $detalle_en_asunto valor a setear en variable
	 */
	public function set_detalle_en_asuntos($detalle_en_asuntos) {
		$this->detalle_en_asuntos = $detalle_en_asuntos;
	}

	/**
	 *
	 * @return valor variable $detalle_en_asunto
	 */
	public function get_detalle_en_asuntos() {
		return $this->detalle_en_asuntos;
	}

	public function iniciales($nombre_encargado) {

		$trozos = explode(' ', $nombre_encargado);
		$cadena = '';

		foreach ($trozos as $nombre) {
			$cadena .= strtoupper(substr($nombre, 0, 1));
		}

		return $cadena;
	}

	function GenerarDocumento($parser, $theTag = 'INFORME', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, & $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto) {
		global $contrato;
		global $cobro_moneda;
		global $masi;

		$moneda_total = new Objeto($this->sesion, '', '', 'prm_moneda', 'id_moneda');
		$moneda_total->Load($this->fields['opc_moneda_total'] > 0 ? $this->fields['opc_moneda_total'] : 1);

		if (!isset($parser->tags[$theTag])) {
			return;
		}

		$this->FillTemplateData($idioma, $moneda);

		$html = $this->RenderTemplate($parser->tags[$theTag]);

		switch ($theTag) {
			case 'INFORME': //GenerarDocumento

				$nuevomodulofactura = Conf::GetConf($this->sesion, 'NuevoModuloFactura');

				if (strpos($html, '%INFORME_GASTOS%') !== false) {

					$this->ArrayFacturasDelContrato = $this->FacturasDelContrato($this->sesion, $nuevomodulofactura, null, 'G');
					$this->ArrayTotalesDelContrato = $this->TotalesDelContrato($this->ArrayFacturasDelContrato, $nuevomodulofactura, $this->fields['id_cobro']);
					$html = str_replace('%INFORME_GASTOS%', '', $html);
				} else if (strpos($html, '%INFORME_HONORARIOS%') !== false) {

					$this->ArrayFacturasDelContrato = $this->FacturasDelContrato($this->sesion, $nuevomodulofactura, null, 'H');
					$this->ArrayTotalesDelContrato = $this->TotalesDelContrato($this->ArrayFacturasDelContrato, $nuevomodulofactura, $this->fields['id_cobro']);
					$html = str_replace('%INFORME_HONORARIOS%', '', $html);
				}

				include_once ('CartaCobro.php');

				$CartaCobro = new CartaCobro($this->sesion, $this->fields, $this->ArrayFacturasDelContrato, $this->ArrayTotalesDelContrato);

				if (isset($this->DetalleLiquidaciones)) {
					$CartaCobro->DetalleLiquidaciones = $this->DetalleLiquidaciones;
				}

				$textocarta = $CartaCobro->GenerarDocumentoCarta($parser_carta, 'CARTA', $lang, $moneda_cliente_cambio, $moneda_cli, $idioma, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $cliente, $id_carta);
				$html = str_replace('%COBRO_CARTA%', $textocarta, $html);

				$PdfLinea1 = Conf::GetConf($this->sesion, 'PdfLinea1');
				$PdfLinea2 = Conf::GetConf($this->sesion, 'PdfLinea2');
				$PdfLinea3 = Conf::GetConf($this->sesion, 'PdfLinea3');

				$query = "SELECT count(*) FROM cta_corriente WHERE id_cobro=" . $this->fields['id_cobro'];
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				list($cont_gastos) = mysql_fetch_array($resp);

				$query = "SELECT count(*) FROM trabajo WHERE id_cobro = " . $this->fields['id_cobro'];
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				list($cont_trab) = mysql_fetch_array($resp);

				$query = "SELECT count(*) FROM tramite WHERE id_cobro = " . $this->fields['id_cobro'];
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				list($cont_tram) = mysql_fetch_array($resp);

				$html = str_replace('%cobro%', __('NOTA DE COBRO') . ' # ', $html);
				$html = str_replace('%valor_cobro%', $this->fields['id_cobro'], $html);
				$html = str_replace('%logo%', Conf::LogoDoc(true), $html);
				$html = str_replace('%titulo%', $PdfLinea1, $html);
				$html = str_replace('%logo_cobro%', Conf::Server() . Conf::ImgDir(), $html);
				$html = str_replace('%subtitulo%', $PdfLinea2, $html);
				$html = str_replace('%direccion%', $PdfLinea3, $html);
				$html = str_replace('%direccion_blr%', __('%direccion_blr%'), $html);
				$html = str_replace('%glosa_fecha%', __('Fecha') . ':', $html);
				$html = str_replace('%glosa_fecha_mayuscula%', __('FECHA'), $html);
				$html = str_replace('%texto_factura%', __('FACTURA'), $html);
				$html = str_replace('%fecha_gqmc%', ($this->fields['fecha_emision'] == '0000-00-00 00:00:00' or $this->fields['fecha_emision'] == '' or $this->fields['fecha_emision'] == 'NULL') ? ucwords(strftime(Utiles::FormatoStrfTime("%e %B %Y"), time())) : ucwords(strftime(Utiles::FormatoStrfTime("%e %B %Y"), strtotime($this->fields['fecha_emision']))), $html);
				$html = str_replace('%fecha%', ($this->fields['fecha_cobro'] == '0000-00-00 00:00:00' or $this->fields['fecha_cobro'] == '' or $this->fields['fecha_cobro'] == 'NULL') ? Utiles::sql2fecha(date('Y-m-d'), $idioma->fields['formato_fecha']) : Utiles::sql2fecha($this->fields['fecha_emision'], $idioma->fields['formato_fecha']), $html);

				if ($lang == 'es') {
					$fecha_lang = ucfirst(Utiles::sql3fecha(date('Y-m-d'), '%d de %B de %Y'));
				} else {
					$fecha_lang = date('F d, Y');
				}

				$fecha_mes_del_cobro = strtotime($this->fields['fecha_fin']);
				$fecha_mes_del_cobro = strftime("%B %Y", mktime(0, 0, 0, date("m", $fecha_mes_del_cobro), date("d", $fecha_mes_del_cobro) - 5, date("Y", $fecha_mes_del_cobro)));

				$html = str_replace('%fecha_mes_del_cobro%', ucfirst($fecha_mes_del_cobro), $html);
				$html = str_replace('%fecha_larga%', $fecha_lang, $html);
				$html = str_replace('%fecha_dia_mes_ano%', date("d/m/Y"), $html);

				$query = "SELECT CONCAT(a.nombre, ' ', a.apellido1, ' ', a.apellido2) FROM usuario AS a JOIN contrato ON a.id_usuario=contrato.id_usuario_responsable JOIN cobro ON cobro.id_contrato=contrato.id_contrato WHERE cobro.id_cobro=" . $this->fields['id_cobro'];
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				list($nombre_encargado) = mysql_fetch_array($resp);

				if ($this->fields['estado'] == 'CREADO' || $this->fields['estado'] == 'EN REVISION') {
					$html = str_replace('%nombre_socio_estado%', $nombre_encargado, $html);
				} else {
					$html = str_replace('%nombre_socio_estado%', '', $html);
				}

				$html = str_replace('%nombre_socio%', $nombre_encargado, $html);
				$html = str_replace('%socio%', __('SOCIO'), $html);
				$html = str_replace('%socio_cobrador%', __('SOCIO COBRADOR'), $html);
				$html = str_replace('%fono%', __('TEL�FONO'), $html);
				$html = str_replace('%fax%', __('TELEFAX'), $html);
				$html = str_replace('%asunto%', __('Asunto'), $html);
				$html = str_replace('%glosa_asunto%', __('Glosa') . ' ' . __('Asunto'), $html);
				$html = str_replace('%codigo_asunto%', __('C�digo') . ' ' . __('Asunto'), $html);

				$cliente = new Cliente($this->sesion);

				if (Conf::GetConf($this->sesion, 'CodigoSecundario')) {
					$codigo_cliente = $cliente->CodigoACodigoSecundario($this->fields['codigo_cliente']);
				} else {
					$codigo_cliente = $this->fields['codigo_cliente'];
				}

				$html = str_replace('%codigo_cliente%', $codigo_cliente, $html);
				$html = str_replace('%CLIENTE%', $this->GenerarSeccionCliente($parser->tags['CLIENTE'], $idioma, $moneda, $asunto), $html);

				if ($this->fields['forma_cobro'] == 'ESCALONADA') {
					$html = str_replace('%DETALLE_COBRO%', "%DETALLE_COBRO%\n\n%TABLA_ESCALONADA%", $html);
				}

				$html = str_replace('%DETALLE_COBRO%', $this->GenerarDocumento($parser, 'DETALLE_COBRO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);

				if ($this->fields['forma_cobro'] == 'ESCALONADA') {
					$this->CargarEscalonadas();

					$html_tabla = "<br /><span class=\"titulo_seccion\">" . __('Detalle Tarifa Escalonada') . "</span>
									<table class=\"tabla_normal\" width=\"50%\">%filas_escalas%</table>";
					$html_fila = "";

					for ($i = 1; $i <= $this->escalonadas['num']; $i++) {

						$detalle_escala = "";
						$detalle_escala .= $this->escalonadas[$i]['tiempo_inicial'] . " - ";
						$detalle_escala .=!empty($this->escalonadas[$i]['tiempo_final']) && $this->escalonadas[$i]['tiempo_final'] != 'NULL' ? $this->escalonadas[$i]['tiempo_final'] . " hrs. " : " " . __('m�s hrs') . " ";
						$detalle_escala .=!empty($this->escalonadas[$i]['id_tarifa']) && $this->escalonadas[$i]['id_tarifa'] != 'NULL' ? " " . __('Tarifa HH') . " " : " " . __('monto fijo') . " ";

						if (!empty($this->fields['esc' . $i . '_descuento']) && $this->fields['esc' . $i . '_descuento'] != 'NULL') {
							$detalle_escala .= " " . __('con descuento') . " {$this->fields['esc' . $i . '_descuento']}% ";
						}

						if (!empty($this->fields['esc' . $i . '_monto']) && $this->fields['esc' . $i . '_monto'] != 'NULL') {
							$query_glosa_moneda = "SELECT simbolo FROM prm_moneda WHERE id_moneda='{$this->escalonadas[$i]['id_moneda']}' LIMIT 1";
							$resp = mysql_query($query_glosa_moneda, $this->sesion->dbh) or Utiles::errorSQL($query_glosa_moneda, __FILE__, __LINE__, $this->sesion->dbh);
							list( $simbolo_moneda ) = mysql_fetch_array($resp);
							$monto_escala = number_format($this->escalonadas[$i]['monto'], $cobro_moneda->moneda[$this->escalonadas[$i]['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
							$detalle_escala .= ": $simbolo_moneda $monto_escala";
						}
						$html_fila .= "	<tr> <td>$detalle_escala</td> </tr>\n";
					}

					$html_tabla = str_replace('%filas_escalas%', $html_fila, $html_tabla);
					$html = str_replace('%TABLA_ESCALONADA%', $html_tabla, $html);
				}

				if ($this->fields['forma_cobro'] == 'CAP') {
					$html = str_replace('%RESUMEN_CAP%', $this->GenerarDocumento($parser, 'RESUMEN_CAP', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				} else {
					$html = str_replace('%RESUMEN_CAP%', '', $html);
				}

				if (Conf::GetConf($this->sesion, 'ParafoAsuntosSoloSiHayTrabajos')) {
					if ($cont_trab || $cont_tram) {
						$html = str_replace('%ASUNTOS%', $this->GenerarDocumento($parser, 'ASUNTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
					} else {
						$html = str_replace('%ASUNTOS%', '', $html);
					}
				} else {
					$html = str_replace('%ASUNTOS%', $this->GenerarDocumento($parser, 'ASUNTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				}

				$html = str_replace('%TRAMITES%', '', $html);

				if (Conf::GetConf($this->sesion, 'ParafoGastosSoloSiHayGastos')) {
					if ($cont_gastos)
						$html = str_replace('%GASTOS%', $this->GenerarDocumento($parser, 'GASTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
					else
						$html = str_replace('%GASTOS%', '', $html);
				} else {
					$html = str_replace('%GASTOS%', $this->GenerarDocumento($parser, 'GASTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				}

				$html = str_replace('%CTA_CORRIENTE%', $this->GenerarDocumento($parser, 'CTA_CORRIENTE', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%TIPO_CAMBIO%', $this->GenerarDocumento($parser, 'TIPO_CAMBIO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%MOROSIDAD%', $this->GenerarDocumentoComun($parser, 'MOROSIDAD', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%GLOSA_ESPECIAL%', $this->GenerarDocumentoComun($parser, 'GLOSA_ESPECIAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%RESUMEN_PROFESIONAL_POR_CATEGORIA%', $this->GenerarSeccionResumenProfesional($parser, 'RESUMEN_PROFESIONAL_POR_CATEGORIA', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%RESUMEN_PROFESIONAL%', $this->GenerarSeccionResumenProfesional($parser, 'RESUMEN_PROFESIONAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%TIPO_CAMBIO%', $this->GenerarDocumento($parser, 'TIPO_CAMBIO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);

				if ($masi) {
					$html = str_replace('%SALTO_PAGINA%', $this->GenerarDocumentoComun($parser, 'SALTO_PAGINA', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				} else {
					$html = str_replace('%SALTO_PAGINA%', '', $html);
				}

				break;

			case 'DETALLE_COBRO': //GenerarDocumento

				/**
				  * Detalle de tarifa escalonada.
				  */
				$chargingBusiness = new ChargingBusiness($this->sesion);
				$coiningBusiness = new CoiningBusiness($this->sesion);
				$translatingBusiness = new TranslatingBusiness($this->sesion);
				$currency = $coiningBusiness->getCurrency($this->fields['opc_moneda_total']);
				$language = $translatingBusiness->getLanguageByCode($idioma->fields['codigo_idioma']);
				$slidingScales = $chargingBusiness->getSlidingScales($this->fields['id_cobro']);
				$table = $chargingBusiness->getSlidingScalesDetailTable($slidingScales, $currency, $language);
				$workDetailTable = $chargingBusiness->getSlidingScalesWorkDetail($chargingBusiness->getCharge($this->fields['id_cobro']));
				$html = str_replace('%detalle_escalones%', $table, $html);
				$html = str_replace('%detalle_trabajos_escalones%', $workDetailTable, $html);

				if ($this->fields['opc_ver_resumen_cobro'] == 0) {
					return '';
				}

				$imprimir_asuntos = '';
				for ($k = 0; $k < count($this->asuntos); $k++) {
					$asunto = new Asunto($this->sesion);
					$asunto->LoadByCodigo($this->asuntos[$k]);
					$imprimir_asuntos .= $asunto->fields['glosa_asunto'];
					if (($k + 1) < count($this->asuntos)) {
						$imprimir_asuntos .= '<br />';
					}
				}

				if (array_key_exists('codigo_contrato', $contrato->fields)) {
					$html = str_replace('%glosa_codigo_contrato%', __('C�digo') . ' ' . __('Contrato'), $html);
					$html = str_replace('%codigo_contrato%', $contrato->fields['codigo_contrato'], $html);
				} else {
					$html = str_replace('%glosa_codigo_contrato%', '', $html);
					$html = str_replace('%codigo_contrato%', '', $html);
				}

				$html = str_replace('%glosa_cliente%', $contrato->fields['factura_razon_social'], $html);
				$html = str_replace('%reporte_servicio%', __('Reporte de Servicios'), $html);
				$html = str_replace('%aviso_de_cobro%', 'Aviso de cobro', $html);
				$html = str_replace('%factura_o_nd%', 'Factura o ND', $html);
				$html = str_replace('%honorario_yo_gastos%', __('honorario_yo_gastos'), $html);
				$html = str_replace('%materia%', __('Materia'), $html);
				$html = str_replace('%glosa_asunto_sin_codigo%', $imprimir_asuntos, $html);
				$html = str_replace('%resumen_cobro%', __('Resumen Nota de Cobro'), $html);
				$html = str_replace('%fecha%', __('Fecha'), $html);
				$html = str_replace('%texto_fecha_emision%', __('Fecha Emisi�n'), $html);
				$html = str_replace('%fecha_emision_glosa%', ($this->fields['fecha_emision'] == '0000-00-00' || $this->fields['fecha_emision'] == '' || $this->fields['fecha_emision'] == NULL ) ? '&nbsp;' : __('Fecha emisi�n'), $html);
				$html = str_replace('%fecha_emision%', ($this->fields['fecha_emision'] == '0000-00-00' || $this->fields['fecha_emision'] == '' || $this->fields['fecha_emision'] == NULL ) ? '&nbsp;' : Utiles::sql2fecha($this->fields['fecha_emision'], $idioma->fields['formato_fecha']), $html);
				$html = str_replace('%cobro%', __('Cobro') . ' ' . __('N�'), $html);
				$html = str_replace('%texto_cobro_nr%', __('Cobro N�'), $html);
				$html = str_replace('%reference%', __('%reference_no%'), $html);
				$html = str_replace('%valor_cobro%', $this->fields['id_cobro'], $html);
				$html = str_replace('%total_simbolo%', __('Total') . ' (' . $moneda_total->fields['simbolo'] . ')', $html);
				$html = str_replace('%boleta%', empty($this->fields['documento']) ? '' : __('Boleta'), $html);
				$html = str_replace('%encargado%', __('Director proyecto'), $html);
				$html = str_replace('%instrucciones_pago%', __('INSTRUCCIONES DE PAGO'), $html);
				$html = str_replace('%giro_bancario%', __('Giro bancario a'), $html);
				$html = str_replace('%fecha_corte%', __('Fecha de Corte'), $html);
				$html = str_replace('%descuento_liquidacion%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['descuento'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%impuesto_liquidacion%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($impuesto_moneda_total, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%subtotal_gastos_honorarios_liquidacion%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($this->fields['monto_subtotal'], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				$horas_cobrables = floor(($this->fields['total_minutos']) / 60);
				$minutos_cobrables = sprintf("%02d", $this->fields['total_minutos'] % 60);

				$detalle_modalidad = $this->ObtenerDetalleModalidad($this->fields, $cobro_moneda->moneda[$this->fields['id_moneda_monto']], $idioma);
				$detalle_modalidad_lowercase = strtolower($detalle_modalidad);

				if (Conf::GetConf($this->sesion, 'ResumenProfesionalVial')) {
					$html = str_replace('%glosa_cobro%', __('Liquidaci�n de honorarios profesionales %desde% hasta %hasta%'), $html);
				} else {
					$html = str_replace('%glosa_cobro%', __('Detalle Cobro'), $html);
				}

				if ($lang == "en") {
					$html = str_replace('%glosa_cobro_aguilar%', __('Debit Note details'), $html);
				} else {
					$html = str_replace('%glosa_cobro_aguilar%', __('Nota de D�bito'), $html);
				}


				if (!$contrato->fields['id_usuario_responsable']) {
					$nombre_encargado = '';
				} else {
					$query = "SELECT CONCAT_WS(' ',nombre,apellido1,apellido2) as nombre_encargado FROM usuario WHERE id_usuario=" . $contrato->fields['id_usuario_responsable'];
					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
					list($nombre_encargado) = mysql_fetch_array($resp);
				}

				$html = str_replace('%encargado_valor%', $nombre_encargado, $html);
				$html = str_replace('%factura%', empty($this->fields['documento']) ? '' : __('Factura'), $html);

				if (empty($this->fields['documento'])) {
					$html = str_replace('%pctje_blr%', '33%', $html);
					$html = str_replace('%FACTURA_NUMERO%', '', $html);
					$html = str_replace('%NUMERO_FACTURA%', '', $html);
				} else {
					$html = str_replace('%pctje_blr%', '25%', $html);
					$html = str_replace('%FACTURA_NUMERO%', $this->GenerarDocumento($parser, 'FACTURA_NUMERO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
					$html = str_replace('%NUMERO_FACTURA%', $this->GenerarDocumento($parser, 'NUMERO_FACTURA', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				}

				$html = str_replace('%factura_nro%', empty($this->fields['documento']) ? '' : __('Factura') . ' ' . __('N�'), $html);
				$html = str_replace('%cobro_nro%', __('Carta') . ' ' . __('N�'), $html);
				$html = str_replace('%nro_cobro%', $this->fields['id_cobro'], $html);
				$html = str_replace('%cobro_factura_nro%', empty($this->fields['documento']) ? '' : $this->fields['documento'], $html);
				$html = str_replace('%nro_factura%', empty($this->fields['documento']) ? '' : $this->fields['documento'], $html);
				$nuevomodulofactura = Conf::GetConf($this->sesion, 'NuevoModuloFactura');
				$facturasRS = $this->ArrayFacturasDelContrato;

				foreach ($facturasRS as $factura => $datos) {
					if ($datos[0]['id_cobro'] != $this->fields['id_cobro']) {
						unset($facturasRS[$factura]);
					}
				}

				$html = str_replace('%lista_facturas%', implode(', ', array_keys($facturasRS)), $html);
				$html = str_replace('%modalidad%', $this->fields['opc_ver_modalidad'] == 1 ? __('Modalidad') : '', $html);
				$html = str_replace('%tipo_honorarios%', $this->fields['opc_ver_modalidad'] == 1 ? __('Tipo de Honorarios') : '', $html);
				if ($this->fields['forma_cobro'] == 'RETAINER' && $contrato->fields['glosa_contrato'] != '') {
					$html = str_replace('%valor_modalidad_tyc%', $this->fields['opc_ver_modalidad'] == 1 ? __($contrato->fields['glosa_contrato']) : '', $html);
				} else {
					$html = str_replace('%valor_modalidad_tyc%', $this->fields['opc_ver_modalidad'] == 1 ? __($this->fields['forma_cobro']) : '', $html);
				}

				$html = str_replace('%valor_modalidad%', $this->fields['opc_ver_modalidad'] == 1 ? __($this->fields['forma_cobro']) : '', $html);

				//La siguiente cosulta extrae la descripcion de forma_cobro de la tabla prm_forma_cobro

				$query = "SELECT descripcion FROM prm_forma_cobro WHERE forma_cobro = '" . $this->fields['forma_cobro'] . "'";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				$row = mysql_fetch_row($resp);
				$descripcion_forma_cobro = $row[0];

				if ($this->fields['forma_cobro'] == 'TASA') {
					$html = str_replace('%valor_modalidad_ucfirst%', $this->fields['opc_ver_modalidad'] == 1 ? __('Tarifa por Hora') : '', $html);
				} else {
					$html = str_replace('%valor_modalidad_ucfirst%', $this->fields['opc_ver_modalidad'] == 1 ? __($descripcion_forma_cobro) : '', $html);
				}

				$html = str_replace('%detalle_modalidad%', $this->fields['opc_ver_modalidad'] == 1 && $this->fields['forma_cobro'] != 'ESCALONADA' ? $detalle_modalidad : '', $html);
				$html = str_replace('%detalle_modalidad_lowercase%', $this->fields['opc_ver_modalidad'] == 1 && $this->fields['forma_cobro'] != 'ESCALONADA' ? $detalle_modalidad_lowercase : '', $html);

				if ($this->fields['forma_cobro'] == 'RETAINER' && $contrato->fields['glosa_contrato'] != '') {
					$html = str_replace('%detalle_modalidad_tyc%', '', $html);
				} else {
					$html = str_replace('%detalle_modalidad_tyc%', $this->fields['opc_ver_modalidad'] == 1 && $this->fields['forma_cobro'] != 'ESCALONADA' ? $detalle_modalidad : '', $html);
				}

				$html = str_replace('%tipo_tarifa%', $this->fields['opc_ver_modalidad'] == 1 ? $detalle_modalidad : '', $html);
				$html = str_replace('%periodo%', (($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') and ( $this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '')) ? '' : __('Periodo'), $html);
				$html = str_replace('%periodo_cobro%', (($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') and ( $this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '')) ? '' : __('Periodo Cobro'), $html);
				$html = str_replace('%valor_periodo_ini%', ($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') ? '' : Utiles::sql2fecha($this->fields['fecha_ini'], $idioma->fields['formato_fecha']), $html);
				$html = str_replace('%valor_periodo_fin%', ($this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '') ? '' : __('hasta') . ' ' . Utiles::sql2fecha($this->fields['fecha_fin'], $idioma->fields['formato_fecha']), $html);
				$html = str_replace('%fecha_ini%', ($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') ? '' : __('Fecha desde'), $html);
				$html = str_replace('%fecha_ini_primer_trabajo%', __('Fecha desde'), $html);
				$html = str_replace('%nota_transferencia%', '<u>' . __('Nota') . '</u>:' . __('Por favor recuerde incluir cualquier tarifa o ') . __('cobro') . __(' por transferencia por parte de vuestro banco con el fin de evitar cargos en las pr�ximas facturas.'), $html);

				/*
				 * 	Se saca la fecha inicial seg�n el primer trabajo
				 * 	esto es especial para LyR
				 */

				$query = "SELECT fecha FROM trabajo WHERE id_cobro='" . $this->fields['id_cobro'] . "' AND visible='1' ORDER BY fecha LIMIT 1";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

				//ac� se calcula si hay trabajos o no (porque si no sale como fecha 1969)
				if (mysql_num_rows($resp) > 0) {
					list($fecha_primer_trabajo) = mysql_fetch_array($resp);
				} else {
					$fecha_primer_trabajo = $this->fields['fecha_fin'];
				}

				//Tambi�n se saca la fecha final seg�n el �ltimo trabajo
				$query = "SELECT LAST_DAY(fecha) FROM trabajo WHERE id_cobro='" . $this->fields['id_cobro'] . "' AND visible='1' ORDER BY fecha DESC LIMIT 1";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				//ac� se calcula si hay trabajos o no (porque si no sale como fecha 1969)
				if (mysql_num_rows($resp) > 0) {
					list($fecha_ultimo_trabajo) = mysql_fetch_array($resp);
				} else {
					$fecha_ultimo_trabajo = $this->fields['fecha_fin'];
				}

				$fecha_inicial_primer_trabajo = date('Y-m-01', strtotime($fecha_primer_trabajo));
				$fecha_final_ultimo_trabajo = date('Y-m-d', strtotime($fecha_ultimo_trabajo));

				if (Conf::GetConf($this->sesion, 'ResumenProfesionalVial')) {
					if ($lang == 'en') {
						$html = str_replace('%desde%', date('m/d/y', ($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') ? strtotime($fecha_inicial_primer_trabajo) : strtotime($this->fields['fecha_ini'])), $html);
						$html = str_replace('%hasta%', date('m/d/y', strtotime($this->fields['fecha_fin'])), $html);
					} else {
						$html = str_replace('%desde%', date('d-m-y', ($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') ? strtotime($fecha_inicial_primer_trabajo) : strtotime($this->fields['fecha_ini'])), $html);
						$html = str_replace('%hasta%', date('d-m-y', strtotime($this->fields['fecha_fin'])), $html);
					}
				}

				$html = str_replace('%valor_fecha_ini_primer_trabajo%', Utiles::sql2fecha($fecha_inicial_primer_trabajo, $idioma->fields['formato_fecha']), $html);
				$html = str_replace('%valor_fecha_fin_ultimo_trabajo%', Utiles::sql2fecha($fecha_final_ultimo_trabajo, $idioma->fields['formato_fecha']), $html);
				$html = str_replace('%valor_fecha_ini_o_primer_trabajo%', ($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') ? Utiles::sql2fecha($fecha_primer_trabajo, $idioma->fields['formato_fecha']) : Utiles::sql2fecha($this->fields['fecha_ini'], $idioma->fields['formato_fecha']), $html);
				$html = str_replace('%valor_fecha_ini%', ($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') ? '' : Utiles::sql2fecha($this->fields['fecha_ini'], $idioma->fields['formato_fecha']), $html);
				$html = str_replace('%fecha_fin%', ($this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '') ? '' : __('Fecha hasta'), $html);
				$html = str_replace('%valor_fecha_fin%', ($this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '') ? '' : Utiles::sql2fecha($this->fields['fecha_fin'], $idioma->fields['formato_fecha']), $html);
				$html = str_replace('%horas%', __('Total Horas'), $html);
				$html = str_replace('%valor_horas%', $horas_cobrables . ':' . $minutos_cobrables, $html);

				if ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') {
					$html = str_replace('%DETALLE_COBRO_RETAINER%', $this->GenerarDocumento($parser, 'DETALLE_COBRO_RETAINER', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
					$html = str_replace('%DETALLE_TARIFA_ADICIONAL%', $this->GenerarDocumento($parser, 'DETALLE_TARIFA_ADICIONAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				} else {
					$html = str_replace('%DETALLE_COBRO_RETAINER%', '', $html);
					$html = str_replace('%DETALLE_TARIFA_ADICIONAL%', '', $html);
				}

				if (Conf::GetConf($this->sesion, 'ResumenProfesionalVial')) {
					$html = str_replace('%honorarios%', __('Honorarios totales'), $html);

					if ($this->fields['opc_restar_retainer']) {
						$html = str_replace('%RESTAR_RETAINER%', $this->GenerarDocumento($parser, 'RESTAR_RETAINER', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
					} else {
						$html = str_replace('%RESTAR_RETAINER%', '', $html);
					}

					$html = str_replace('%descuento%', __('Otros'), $html);
					$html = str_replace('%saldo%', __('Saldo por pagar'), $html);
					$html = str_replace('%equivalente%', __('Equivalente a'), $html);
				} else {
					$html = str_replace('%honorarios%', __('Honorarios'), $html);
				}

				if ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') {
					$html = str_replace('%honorarios_totales%', __('Honorarios Totales'), $html);
				} else {
					$html = str_replace('%honorarios_totales%', __('Honorarios'), $html);
				}

				$html = str_replace('%honorarios_mta%', __('Honorarios totales'), $html);
				$html = str_replace('%valor_honorarios_totales%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . number_format($this->fields['monto'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_honorarios_totales_moneda_total%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($this->fields['monto'], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%fees%', __('%fees%'), $html); //en vez de Legal Fee es Legal Fees en ingl�s
				$html = str_replace('%expenses%', __('%expenses%'), $html); //en vez de Disbursements es Expenses en ingl�s
				$html = str_replace('%total_honorarios%', __('Total Honorarios'), $html);

				$valor_trabajos_demo = number_format($this->fields['monto_trabajos'] - $this->fields['descuento'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');

				//variable que se usa para la nota de cobro de vial

				$monto_cobro_menos_monto_contrato_moneda_tarifa = number_format($this->fields['monto'] - ($this->fields['monto_contrato'] * $cobro_moneda->moneda[$this->fields['id_moneda_monto']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');

				$html = str_replace('%valor_honorarios_demo%', $moneda->fields['simbolo'] . $this->espacio . number_format($valor_trabajos_demo, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				if (Conf::GetConf($this->sesion, 'ResumenProfesionalVial') && ( $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL' ) && $this->fields['opc_restar_retainer']) {
					$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'] . $this->espacio . number_format($monto_cobro_menos_monto_contrato_moneda_tarifa, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				}

				if (Conf::GetConf($this->sesion, 'CalculacionCYC')) {
					$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto_subtotal'] - $this->fields['descuento'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				} else if (Conf::GetConf($this->sesion, 'UsarImpuestoSeparado') && $contrato->fields['usa_impuesto_separado']) {
					$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto_subtotal'] - $this->fields['descuento'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				} else {
					$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				}

				$html = str_replace('%horas_decimales%', __('Horas'), $html);
				$minutos_decimal = $minutos_cobrables / 60;
				$duracion_decimal = $horas_cobrables + $minutos_decimal;
				$html = str_replace('%valor_horas_decimales%', number_format($duracion_decimal, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				if ($this->fields['tipo_cambio_moneda_base'] <= 0) {
					$tipo_cambio_cobro_moneda_base = 1;
				} else {
					$tipo_cambio_cobro_moneda_base = $this->fields['tipo_cambio_moneda_base'];
				}

				$en_pesos = $this->fields['monto'] * ($this->fields['tipo_cambio_moneda'] / $tipo_cambio_cobro_moneda_base);
				$aproximacion_monto = number_format($this->fields['monto'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
				$aproximacion_monto_cyc = number_format($this->fields['monto_subtotal'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
				$aproximacion_monto_trabajos_demo = number_format($this->fields['monto_trabajos'] - $this->fields['descuento'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
				$valor_trabajos_demo_moneda_total = $aproximacion_monto_trabajos_demo * $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'];
				$total_en_moneda = $aproximacion_monto * ($this->fields['tipo_cambio_moneda'] / $tipo_cambio_cobro_moneda_base) / ($tipo_cambio_moneda_total / $tipo_cambio_cobro_moneda_base);
				$subtotal_en_moneda_cyc = $aproximacion_monto_cyc * ($this->fields['tipo_cambio_moneda'] / $tipo_cambio_cobro_moneda_base) / ($tipo_cambio_moneda_total / $tipo_cambio_cobro_moneda_base);

				if ($this->fields['id_moneda'] == 2 && $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'] == 0) {
					$descuento_cyc_approximacion = number_format($this->fields['descuento'], 2, '.', '');
				} else {
					$descuento_cyc_approximacion = number_format($this->fields['descuento'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
				}

				$descuento_cyc = $descuento_cyc_approximacion * ($this->fields['tipo_cambio_moneda'] / $tipo_cambio_cobro_moneda_base) / ($tipo_cambio_moneda_total / $tipo_cambio_cobro_moneda_base);

				if (Conf::GetConf($this->sesion, 'CalculacionCYC')) {
					$impuestos_cyc_approximacion = number_format(($subtotal_en_moneda_cyc - $descuento_cyc) * ($this->fields['porcentaje_impuesto'] / 100), $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
				} else {
					$impuestos_cyc_approximacion = number_format($this->fields['impuesto'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
					$impuestos_cyc_approximacion *= ($this->fields['tipo_cambio_moneda'] / $tipo_cambio_cobro_moneda_base) / ($tipo_cambio_moneda_total / $tipo_cambio_cobro_moneda_base);
				}

				$impuestos_cyc = $impuestos_cyc_approximacion;

				//Caso retainer menor de un valor y distinta tarifa (diferencia por decimales)
				if ((($this->fields['total_minutos'] / 60) < $this->fields['retainer_horas']) && ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') && $this->fields['id_moneda'] != $this->fields['id_moneda_monto']) {
					$total_en_moneda = $this->fields['monto'] * ($this->fields['tipo_cambio_moneda'] / $tipo_cambio_cobro_moneda_base) / ($tipo_cambio_moneda_total / $tipo_cambio_cobro_moneda_base);
				}

				//Caso flat fee
				if ($this->fields['forma_cobro'] == 'FLAT FEE' && $this->fields['id_moneda'] != $this->fields['id_moneda_monto'] && $this->fields['id_moneda_monto'] == $this->fields['opc_moneda_total'] && empty($this->fields['descuento'])) {
					$total_en_moneda = $this->fields['monto_contrato'];
				}

				$html = str_replace('%valor_honorarios_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($subtotal_en_moneda_cyc - $descuento_cyc, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idoma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_honorarios_monedabase%', $this->fields['opc_moneda_total'] == $this->fields['id_moneda'] ? '' : $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($total_en_moneda, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_honorarios_monedabase_tyc%', $this->fields['opc_moneda_total'] == $this->fields['id_moneda'] || ( $this->fields['id_moneda'] == 2 && $this->fields['codigo_idioma'] == 'en' ) ? '' : $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($total_en_moneda, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_honorarios_monedabase_demo%', $this->fields['opc_moneda_total'] == $this->fields['id_moneda'] ? '' : $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($valor_trabajos_demo_moneda_total, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				$html = str_replace('%monedabase%', $this->fields['opc_moneda_total'] == $this->fields['id_moneda'] ? '' : __('Equivalente a'), $html);
				$html = str_replace('%equivalente_a_la_fecha%', $this->fields['opc_moneda_total'] == $this->fields['id_moneda'] ? '' : __('Equivalente a la fecha'), $html);

				#detalle total gastos
				$html = str_replace('%gastos%', __('Gastos'), $html);

				$query = "SELECT SQL_CALC_FOUND_ROWS * FROM cta_corriente WHERE id_cobro='" . $this->fields['id_cobro'] . "' AND (egreso > 0 OR ingreso > 0) AND cta_corriente.incluir_en_cobro = 'SI' ORDER BY fecha ASC";
				$lista_gastos = new ListaGastos($this->sesion, '', $query);

				$total_gastos_moneda = 0;

				for ($i = 0; $i < $lista_gastos->num; $i++) {

					$gasto = $lista_gastos->Get($i);

					if ($gasto->fields['egreso'] > 0) {
						$saldo = $gasto->fields['monto_cobrable'];
					} elseif ($gasto->fields['ingreso'] > 0) {
						$saldo = -$gasto->fields['monto_cobrable'];
					}

					$monto_gasto = $saldo;
					$saldo_moneda_total = $saldo * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']);

					if (Conf::GetConf($this->sesion, 'CalculacionCYC')) {
						$saldo_moneda_total = number_format($saldo_moneda_total, $cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['cifras_decimales'], ".", "");
					}

					$total_gastos_moneda += $saldo_moneda_total;
				}

				if ($this->fields['monto_subtotal'] > 0) {
					$html = str_replace('%DETALLE_HONORARIOS%', $this->GenerarDocumento($parser, 'DETALLE_HONORARIOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				} else {
					$html = str_replace('%DETALLE_HONORARIOS%', '', $html);
				}

				if ($total_gastos_moneda > 0) {
					$html = str_replace('%DETALLE_GASTOS%', $this->GenerarDocumento($parser, 'DETALLE_GASTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				} else {
					$html = str_replace('%DETALLE_GASTOS%', '', $html);
				}

				if ($this->fields['monto_tramites'] > 0) {
					$html = str_replace('%DETALLE_TRAMITES%', $this->GenerarDocumento($parser, 'DETALLE_TRAMITES', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				} else {
					$html = str_replace('%DETALLE_TRAMITES%', '', $html);
				}

				if (Conf::GetConf($this->sesion, 'CalculacionCYC')) {
					$total_gastos_moneda = round($total_gastos_moneda, $moneda_total->fields['cifras_decimales']);
				}

				$impuestos_total_gastos_moneda = round($total_gastos_moneda * ($this->fields['porcentaje_impuesto_gastos'] / 100), $moneda_total->fields['cifras_decimales']);

				#total nota cobro
				$total_cobro = $total_en_moneda + $total_gastos_moneda;
				$total_cobro_demo = number_format(number_format($this->fields['monto'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '') * $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], '.', '') + number_format($this->fields['monto_gastos'], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], '.', '');
				$total_cobro_cyc = $subtotal_en_moneda_cyc + $total_gastos_moneda - $descuento_cyc;
				$iva_cyc = $impuestos_total_gastos_moneda + $impuestos_cyc;

				$html = str_replace('%total_cobro%', __('Total Cobro'), $html);
				$html = str_replace('%total_cobro_mta%', __('GRAN TOTAL'), $html);
				$html = str_replace('%total_cobro_cyc%', __('Honorarios y Gastos'), $html);

				$html = str_replace('%valor_uf%', __('Valor UF') . ' ' . date('d.m.Y'), $html);
				$html = str_replace('%iva_cyc%', __('IVA') . '(' . $this->fields['porcentaje_impuesto'] . '%)', $html);
				$html = str_replace('%honorarios_y_gastos%', '(' . __('Honorarios y Gastos') . ')', $html);
				$html = str_replace('%total_cyc%', __('Total'), $html);

				$html = str_replace('%valor_gastos%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($total_gastos_moneda, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_total_cobro_demo%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($total_cobro_demo, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_total_cobro_cyc%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($total_cobro_cyc, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_iva_cyc%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($iva_cyc, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idoma->fields['separador_miles']), $html);
				$html = str_replace('%valor_total_cyc%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($total_cobro_cyc + $iva_cyc, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_total_cobro%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($total_cobro, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				$html = str_replace('%valor_total_cobro_sin_simbolo%', number_format($total_cobro, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_uf%', __('Valor UF') . ' ' . date('d.m.Y'), $html);

				if ($this->fields['opc_ver_tipo_cambio'] == 0) {
					$html = str_replace('%glosa_tipo_cambio_moneda%', '', $html);
					$html = str_replace('%valor_tipo_cambio_moneda%', '', $html);
				} else {
					$html = str_replace('%glosa_tipo_cambio_moneda%', __('Tipo de Cambio'), $html);
					$html = str_replace('%valor_tipo_cambio_moneda%', $cobro_moneda->moneda[$this->fields['id_moneda_base']]['simbolo'] . $this->espacio . number_format($cobro_moneda->moneda[$moneda->fields['id_moneda']]['tipo_cambio'], 2, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				}

				$html = str_replace('%DETALLE_COBRO_MONEDA_TOTAL%', $this->GenerarDocumentoComun($parser, 'DETALLE_COBRO_MONEDA_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);

				if ($this->fields['opc_ver_descuento']) {
					$html = str_replace('%DETALLE_COBRO_DESCUENTO%', $this->GenerarDocumento($parser, 'DETALLE_COBRO_DESCUENTO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				} else {
					$html = str_replace('%DETALLE_COBRO_DESCUENTO%', '', $html);
				}

				//if(( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'UsarImpuestoSeparado') ) || ( method_exists('Conf','UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() ) ) && $contrato->fields['usa_impuesto_separado'])
				if ($this->fields['porcentaje_impuesto'] > 0 || $this->fields['porcentaje_impuesto_gastos'] > 0) {
					$html = str_replace('%IMPUESTO%', $this->GenerarDocumento($parser, 'IMPUESTO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				} else {
					$html = str_replace('%IMPUESTO%', '', $html);
				}

				if (Conf::GetConf($this->sesion, 'ResumenProfesionalVial')) {
					$valor_bruto = $this->fields['monto'];

					if (Conf::GetConf($this->sesion, 'UsarImpuestoSeparado') && $contrato->fields['usa_impuesto_separado']) {
						$valor_bruto -= $this->fields['impuesto'];
					}

					$valor_bruto += $this->fields['descuento'];
					$monto_cobro_menos_monto_contrato_moneda_total = $monto_cobro_menos_monto_contrato_moneda_tarifa * $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'];

					$html = str_replace('%valor_bruto%', $moneda->fields['simbolo'] . $this->espacio . number_format($valor_bruto, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					$html = str_replace('%valor_descuento%', '(' . $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['descuento'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ')', $html);

					if (( $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL' ) && $this->fields['opc_restar_retainer']) {
						$html = str_replace('%valor_equivalente%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($monto_cobro_menos_monto_contrato_moneda_total, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					} else {
						$html = str_replace('%valor_equivalente%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($total_en_moneda, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					}
				}

				$html = str_replace('%total_subtotal_cobro%', __('Total Cobro'), $html);

				if ($this->fields['id_carta'] == 3) {
					$html = str_replace('%nota_disclaimer%', __('Nota_Disclaimer'), $html);
				} else {
					$html = str_replace('%nota_disclaimer%', ' ', $html);
				}

				if ($this->fields['opc_ver_morosidad']) {
					$html = str_replace('%DETALLES_PAGOS%', $this->GenerarSeccionDetallePago($parser->tags['DETALLES_PAGOS'], $idioma), $html);
					$html = str_replace('%DETALLES_PAGOS_CONTRATO%', $this->GenerarSeccionDetallePagoContrato($parser->tags['DETALLES_PAGOS_CONTRATO'], $idioma), $html);
				} else {
					$html = str_replace('%DETALLES_PAGOS%', '', $html);
					$html = str_replace('%DETALLES_PAGOS_CONTRATO%', '', $html);
				}

				$and_saldo_adelantos = '';
				$and_saldo_gastos = '';
				$and_saldo_liquidaciones = '';

				if (Conf::GetConf($this->sesion, "SaldoClientePorAsunto")) {
					$and_saldo_adelantos = "AND d.id_contrato = '" . $asunto->fields['id_contrato'] . "' ";
					$and_saldo_gastos = "AND asunto.id_contrato = '" . $asunto->fields['id_contrato'] . "' ";
					$and_saldo_liquidaciones = "AND cobro.id_contrato = '" . $asunto->fields['id_contrato'] . "' ";
				}

				$query_saldo_adelantos = "SELECT SUM(- 1 * d.saldo_pago * (moneda_documento.tipo_cambio / moneda_base.tipo_cambio)) AS saldo_adelantos
										FROM documento d
									INNER JOIN prm_moneda moneda_documento ON d.id_moneda = moneda_documento.id_moneda
									INNER join prm_moneda moneda_base ON moneda_base.id_moneda = 1
									INNER JOIN cliente ON cliente.codigo_cliente = d.codigo_cliente
									LEFT JOIN contrato ON d.id_contrato = contrato.id_contrato
									LEFT JOIN usuario encargado_comercial ON encargado_comercial.id_usuario = contrato.id_usuario_responsable
										WHERE cliente.activo = 1
											AND (d.id_contrato IS NULL OR contrato.activo = 'SI')
											AND d.es_adelanto = 1
											AND d.saldo_pago < 0
											$and_saldo_adelantos
											AND d.pago_gastos = '1'
											AND d.codigo_cliente = '" . $this->fields['codigo_cliente'] . "' ";

				$resp_saldo_adelantos = mysql_query($query_saldo_adelantos, $this->sesion->dbh) or Utiles::errorSQL($query_saldo_adelantos, __FILE__, __LINE__, $this->sesion->dbh);
				list($monto_saldo_adelantos) = mysql_fetch_array($resp_saldo_adelantos);

				$query_saldo_gastos = "SELECT SUM( - 1 * cta_corriente.egreso)
												FROM cta_corriente
											INNER JOIN cliente ON cliente.codigo_cliente = cta_corriente.codigo_cliente
											INNER JOIN asunto ON asunto.codigo_asunto = cta_corriente.codigo_asunto
											INNER JOIN contrato ON asunto.id_contrato = contrato.id_contrato
											LEFT JOIN cobro ON cta_corriente.id_cobro = cobro.id_cobro
												WHERE cliente.activo = 1
													AND contrato.activo = 'SI'
													AND cta_corriente.cobrable = 1
													AND (cta_corriente.id_cobro IS NULL
													OR cobro.estado IN ('CREADO' , 'EN REVISION'))
													AND cta_corriente.id_neteo_documento IS NULL
													AND cta_corriente.documento_pago IS NULL
													$and_saldo_gastos
													AND cta_corriente.codigo_cliente = '" . $this->fields['codigo_cliente'] . "' ";

				$resp_saldo_gastos = mysql_query($query_saldo_gastos, $this->sesion->dbh) or Utiles::errorSQL($query_saldo_gastos, __FILE__, __LINE__, $this->sesion->dbh);
				list($monto_saldo_gastos) = mysql_fetch_array($resp_saldo_gastos);

				$query_saldo_liquidaciones = "SELECT SUM(- 1 * (d.saldo_honorarios + d.saldo_gastos) * (tipo_cambio_documento.tipo_cambio / tipo_cambio_base.tipo_cambio)) AS saldo_liquidaciones
								FROM documento d
							INNER JOIN cobro ON cobro.id_cobro = d.id_cobro
							INNER JOIN prm_moneda moneda_documento ON d.id_moneda = moneda_documento.id_moneda
							INNER JOIN cobro_moneda tipo_cambio_documento ON tipo_cambio_documento.id_moneda = moneda_documento.id_moneda AND tipo_cambio_documento.id_cobro = cobro.id_cobro
							INNER JOIN prm_moneda moneda_base ON moneda_base.id_moneda = 1
							INNER JOIN cobro_moneda tipo_cambio_base ON tipo_cambio_base.id_moneda = moneda_base.id_moneda AND tipo_cambio_base.id_cobro = cobro.id_cobro
							INNER JOIN contrato ON contrato.id_contrato = cobro.id_contrato
							INNER JOIN cliente ON cliente.codigo_cliente = d.codigo_cliente
								WHERE
									cliente.activo = 1
									AND contrato.activo = 'SI'
									AND d.tipo_doc = 'N'
									AND cobro.estado NOT IN ('CREADO' , 'EN REVISION', 'INCOBRABLE')
									$and_saldo_liquidaciones
									AND cobro.incluye_gastos = '1'
									AND cobro.incluye_honorarios = '0'
									AND d.saldo_gastos > 0
									AND d.codigo_cliente = '" . $this->fields['codigo_cliente'] . "' ";

				$resp_saldo_liquidaciones = mysql_query($query_saldo_liquidaciones, $this->sesion->dbh) or Utiles::errorSQL($query_saldo_liquidaciones, __FILE__, __LINE__, $this->sesion->dbh);
				list($monto_saldo_liquidaciones) = mysql_fetch_array($resp_saldo_liquidaciones);

				$monto_saldo_cliente = $monto_saldo_adelantos + $monto_saldo_gastos + $monto_saldo_liquidaciones;

				$monto_saldo_moneda_impresion = UtilesApp::CambiarMoneda($monto_saldo_cliente, 1, 0, $x_resultados['tipo_cambio_opc_moneda_total'], $x_resultados['cifras_decimales_opc_moneda_total']);

				if ($monto_saldo_cliente < 0) {
					$texto_saldo_favor_o_contra = 'Saldo en contra';
				} else {
					$texto_saldo_favor_o_contra = 'Saldo a favor';
				}

				$monto_saldo_liquidaciones = $moneda_total->fields['simbolo'] . ' ' . number_format($monto_saldo_liquidaciones, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
				$monto_saldo_adelantos = $moneda_total->fields['simbolo'] . ' ' . number_format($monto_saldo_adelantos, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
				$monto_saldo_final = $moneda_total->fields['simbolo'] . ' ' . number_format($monto_saldo_moneda_impresion, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);

				$html = str_replace('%texto_saldo_liquidaciones%', __('Saldo liquidaciones'), $html);
				$html = str_replace('%monto_saldo_liquidaciones%', $monto_saldo_liquidaciones, $html);

				$html = str_replace('%texto_saldo_adelantos%', __('Saldo adelantos'), $html);
				$html = str_replace('%monto_saldo_adelantos%', $monto_saldo_adelantos, $html);

				$html = str_replace('%texto_saldo_favor_o_contra%', $texto_saldo_favor_o_contra, $html);
				$html = str_replace('%monto_saldo_final%', $monto_saldo_final, $html);

				list($total_parte_entera, $total_parte_decimal) = explode('.', $x_resultados['monto_total_cobro'][$this->fields['opc_moneda_total']]);

				if (strlen($total_parte_decimal) == '2') {
					$fix_decimal = '1';
				} else {
					$fix_decimal = '10';
				}

				if ($lang == 'es') {

					$glosa_moneda_plural_lang = $moneda_total->fields['glosa_moneda_plural'];

					if (empty($total_parte_decimal)) {
						$monto_palabra_parte_entera = strtoupper(Numbers_Words::toWords($total_parte_entera, 'es'));
						$monto_total_palabra = $monto_palabra_parte_entera . ' ' . mb_strtoupper($glosa_moneda_plural_lang);
						$monto_total_palabra_cero_cien = $monto_palabra_parte_entera . ' ' . mb_strtoupper($glosa_moneda_plural_lang) . ' CON 00/100 CENTAVOS';
					} else {
						$monto_palabra_parte_entera = strtoupper(Numbers_Words::toWords($total_parte_entera, 'es'));
						$monto_palabra_parte_decimal = strtoupper(Numbers_Words::toWords($total_parte_decimal * $fix_decimal, 'es'));
						$monto_total_palabra = $monto_palabra_parte_entera . ' ' . mb_strtoupper($glosa_moneda_plural_lang, 'UTF-8') . ' CON ' . $monto_palabra_parte_decimal . ' ' . 'CENTAVOS';
						$monto_total_palabra_cero_cien = $monto_palabra_parte_entera . ' ' . mb_strtoupper($glosa_moneda_plural_lang) . ' CON ' . $total_parte_decimal * $fix_decimal . '/100 CENTAVOS';
					}
				} else {

					$glosa_moneda_plural_lang = $moneda_total->fields['glosa_moneda_plural_lang'];

					if (empty($total_parte_decimal)) {
						$monto_palabra_parte_entera = strtoupper(Numbers_Words::toWords($total_parte_entera, 'en_US'));
						$monto_total_palabra = $monto_palabra_parte_entera . ' ' . mb_strtoupper($glosa_moneda_plural_lang);
						$monto_total_palabra_cero_cien = $monto_palabra_parte_entera . ' ' . mb_strtoupper($glosa_moneda_plural_lang) . ' CON 00/100 CENTAVOS';
					} else {
						$monto_palabra_parte_entera = strtoupper(Numbers_Words::toWords($total_parte_entera, 'en_US'));
						$monto_palabra_parte_decimal = strtoupper(Numbers_Words::toWords($total_parte_decimal, 'en_US'));
						$monto_total_palabra = $monto_palabra_parte_entera . ' ' . mb_strtoupper($glosa_moneda_plural_lang, 'UTF-8') . ' WITH ' . $monto_palabra_parte_decimal . ' ' . 'CENTS';
						$monto_total_palabra_cero_cien = $monto_palabra_parte_entera . ' ' . mb_strtoupper($glosa_moneda_plural_lang, 'UTF-8') . ' WITH ' . $total_parte_decimal * $fix_decimal . '/100 CENTS';
					}
				}

				$html = str_replace('%monto_total_palabra_cero_cien%', $monto_total_palabra_cero_cien, $html);
				$html = str_replace('%monto_total_palabra%', $monto_total_palabra, $html);


				break;

			case 'RESTAR_RETAINER': //GenerarDocumento

				if ($columna_hrs_retainer || $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') {
					$html = str_replace('%retainer%', __('Retainer'), $html);
				} else {
					$html = str_replace('%retainer%', '', $html);
				}

				if ($columna_hrs_retainer || $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') {
					$html = str_replace('%valor_retainer%', '(' . $cobro_moneda->moneda[$this->fields['id_moneda_monto']]['simbolo'] . $this->espacio . number_format($this->fields['monto_contrato'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ')', $html);
				} else {
					$html = str_replace('%valor_retainer%', '', $html);
				}

				break;

			case 'DETALLE_COBRO_RETAINER': //GenerarDocumento
				$monto_contrato_moneda_tarifa = number_format($this->fields['monto_contrato'] * $cobro_moneda->moneda[$this->fields['id_moneda_monto']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
				$monto_cobro_menos_monto_contrato_moneda_tarifa = number_format($this->fields['monto'] - ($this->fields['monto_contrato'] * $cobro_moneda->moneda[$this->fields['id_moneda_monto']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');

				$html = str_replace('%horas_retainer%', 'Horas retainer', $html);
				$html = str_replace('%valor_horas_retainer%', Utiles::horaDecimal2HoraMinuto($this->fields['retainer_horas']), $html);
				$html = str_replace('%horas_adicionales%', 'Horas adicionales', $html);
				$html = str_replace('%valor_horas_adicionales%', Utiles::horaDecimal2HoraMinuto(($this->fields['total_minutos'] / 60) - $this->fields['retainer_horas']), $html);
				$html = str_replace('%honorarios_retainer%', 'Honorarios retainer', $html);
				$html = str_replace('%valor_honorarios_retainer%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . number_format($monto_contrato_moneda_tarifa, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%honorarios_adicionales%', 'Honorarios adicionales', $html);
				$html = str_replace('%valor_honorarios_adicionales%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . number_format($monto_cobro_menos_monto_contrato_moneda_tarifa, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				break;

			case 'DETALLE_TARIFA_ADICIONAL': //GenerarDocumento
				$tarifas_adicionales = $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . " ";

				$query = "SELECT DISTINCT tarifa_hh FROM trabajo WHERE id_cobro = '" . $this->fields['id_cobro'] . "' ORDER BY tarifa_hh DESC";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

				$i = 0;
				while (list($tarifa_hh) = mysql_fetch_array($resp)) {
					if ($i == 0)
						$tarifas_adicionales .= "$tarifa_hh/hr";
					else
						$tarifas_adicionales .= ", $tarifa_hh/hr";
					$i++;
				}

				$html = str_replace('%tarifa_adicional%', __('Tarifa adicional por hora'), $html);
				$html = str_replace('%valores_tarifa_adicionales%', $tarifas_adicionales, $html);
				break;

			case 'FACTURA_NUMERO': //GenerarDocumento
				$html = str_replace('%factura_nro%', __('Factura') . ' ' . __('N�'), $html);
				break;

			case 'NUMERO_FACTURA': //GenerarDocumento
				$html = str_replace('%nro_factura%', $this->fields['documento'], $html);
				break;

			case 'DETALLE_HONORARIOS': //GenerarDocumento

				$horas_cobrables = floor(($this->fields['total_minutos']) / 60);
				$minutos_cobrables = sprintf("%02d", $this->fields['total_minutos'] % 60);
				$html = str_replace('%horas%', __('Total Horas'), $html);
				$html = str_replace('%valor_horas%', $horas_cobrables . ':' . $minutos_cobrables, $html);
				$html = str_replace('%honorarios%', __('Honorarios'), $html);

				if (Conf::GetConf($this->sesion, 'UsarImpuestoSeparado') && $contrato->fields['usa_impuesto_separado']) {
					$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto'] - $this->fields['impuesto'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				} else {
					$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				}

				if ($this->fields['opc_ver_descuento']) {
					$html = str_replace('%DETALLE_COBRO_DESCUENTO%', $this->GenerarDocumento($parser, 'DETALLE_COBRO_DESCUENTO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				} else {
					$html = str_replace('%DETALLE_COBRO_DESCUENTO%', '', $html);
				}

				$html = str_replace('%DETALLE_COBRO_MONEDA_TOTAL%', $this->GenerarDocumentoComun($parser, 'DETALLE_COBRO_MONEDA_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);

				break;

			case 'DETALLE_GASTOS': //GenerarDocumento

				$html = str_replace('%gastos%', __('Gastos'), $html);

				$query = "SELECT SQL_CALC_FOUND_ROWS * FROM cta_corriente WHERE id_cobro='" . $this->fields['id_cobro'] . "' AND (egreso > 0 OR ingreso > 0) AND cta_corriente.incluir_en_cobro = 'SI' ORDER BY fecha ASC";
				$lista_gastos = new ListaGastos($this->sesion, '', $query);
				$total_gastos_moneda = 0;

				for ($i = 0; $i < $lista_gastos->num; $i++) {
					$gasto = $lista_gastos->Get($i);

					if ($gasto->fields['egreso'] > 0) {
						$saldo = $gasto->fields['monto_cobrable'];
					} elseif ($gasto->fields['ingreso'] > 0) {
						$saldo = -$gasto->fields['monto_cobrable'];
					}

					$monto_gasto = $saldo;
					$saldo_moneda_total = $saldo * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']);
					$total_gastos_moneda += $saldo_moneda_total;
				}

				if (Conf::GetConf($this->sesion, 'CalculacionCYC')) {
					$total_gastos_moneda = round($total_gastos_moneda, $moneda_total->fields['cifras_decimales']);
				}

				$impuestos_total_gastos_moneda = round($total_gastos_moneda * ($this->fields['porcentaje_impuesto_gastos'] / 100), $moneda_total->fields['cifras_decimales']);

				$html = str_replace('%valor_gastos%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($total_gastos_moneda, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				break;

			case 'DETALLE_TRAMITES': //GenerarDocumento

				$html = str_replace('%tramites%', __('Tr�mites'), $html);
				$html = str_replace('%tramites_castropal%', __('Otros Servicios'), $html);
				$aproximacion_tramites = number_format($this->fields['monto_tramites'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
				$valor_tramites = $aproximacion_tramites * $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'];

				$html = str_replace('%valor_tramites%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($valor_tramites, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				break;

			case 'DETALLE_COBRO_DESCUENTO': //GenerarDocumento

				if ($this->fields['descuento'] == 0) {

					if (Conf::GetConf($this->sesion, 'FormatoNotaCobroMTA')) {
						$html = str_replace('%honorarios%', __('Subtotal Honorarios'), $html);

						$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto_subtotal'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

						$html = str_replace('%valor_descuento%', '', $html);
						$html = str_replace('%porcentaje_descuento%', '', $html);
						$html = str_replace('%descuento%', '', $html);
						break;
					} else {
						return '';
					}
				}

				$aproximacion_honorarios = number_format($this->fields['monto_subtotal'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
				$aproximacion_descuento = number_format($this->fields['descuento'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
				$valor_trabajos_demo = number_format($this->fields['monto_trabajos'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idoma->fields['separador_miles']);
				$valor_descuento_demo = number_format($this->fields['descuento'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
				$valor_honorarios = number_format($aproximacion_honorarios * $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
				$valor_descuento = number_format($aproximacion_descuento * $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);

				$html = str_replace('%valor_honorarios_demo%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . $this->espacio . $valor_trabajos_demo, $html);
				$html = str_replace('%valor_descuento_demo%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . $this->espacio . $valor_descuento_demo, $html);

				if (Conf::GetConf($this->sesion, 'CalculacionCyC')) {
					$html = str_replace('%valor_honorarios%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . $valor_honorarios, $html);
					$html = str_replace('%valor_descuento%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . $valor_descuento, $html);
				}

				$html = str_replace('%honorarios%', __('Subtotal Honorarios'), $html);
				$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto_subtotal'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%descuento%', __('Descuento'), $html);

				if ($this->fields['monto_trabajos'] > 0) {
					$porcentaje_demo = ($this->fields['descuento'] * 100) / $this->fields['monto_trabajos'];
				}

				$html = str_replace('%porcentaje_descuento_demo%', ' (' . number_format($porcentaje_demo, 0) . '%)', $html);

				if ($this->fields['monto_subtotal'] > 0) {
					$porcentaje = ($this->fields['descuento'] * 100) / $this->fields['monto_subtotal'];
				}

				$html = str_replace('%porcentaje_descuento%', ' (' . number_format($porcentaje, 0) . '%)', $html);

				$html = str_replace('%valor_descuento%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['descuento'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_honorarios_con_descuento%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%total_honorarios%', __('Total Honorarios'), $html);

				break;

			case 'RESUMEN_CAP': //GenerarDocumento

				$monto_restante = $this->fields['monto_contrato'] - ( $this->TotalCobrosCap() + ($this->fields['monto_trabajos'] - $this->fields['descuento']) * $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['tipo_cambio'] );

				$html = str_replace('%cap%', __('Total CAP'), $html);

				$html = str_replace('%valor_cap%', $cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['simbolo'] . $this->espacio . $this->fields['monto_contrato'], $html);
				$html = str_replace('%valor_restante%', $cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['simbolo'] . $this->espacio . number_format($monto_restante, $cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				$html = str_replace('%COBROS_DEL_CAP%', $this->GenerarDocumento($parser, 'COBROS_DEL_CAP', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%restante%', __('Monto restante'), $html);

				break;

			case 'COBROS_DEL_CAP': //GenerarDocumento

				$row_tmpl = $html;
				$html = '';

				$query = "SELECT cobro.id_cobro, (monto_trabajos*cm2.tipo_cambio)/cm1.tipo_cambio
										FROM cobro
										JOIN contrato ON cobro.id_contrato=contrato.id_contrato
										JOIN cobro_moneda as cm1 ON cobro.id_cobro=cm1.id_cobro AND cm1.id_moneda=contrato.id_moneda_monto
										JOIN cobro_moneda as cm2 ON cobro.id_cobro=cm2.id_cobro AND cm2.id_moneda=cobro.id_moneda
									 WHERE cobro.id_contrato=" . $this->fields['id_contrato'] . "
										 AND cobro.forma_cobro='CAP'";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

				while (list($id_cobro, $monto_cap) = mysql_fetch_array($resp)) {
					$row = $row_tmpl;

					$row = str_replace('%numero_cobro%', __('Cobro') . ' ' . $id_cobro, $row);
					$row = str_replace('%valor_cap_del_cobro%', $cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['simbolo'] . $this->espacio . number_format($monto_cap, $cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

					$html .= $row;
				}

				break;

			case 'ASUNTOS': //GenerarDocumento

				$row_tmpl = $html;
				$html = '';

				for ($k = 0; $k < count($this->asuntos); $k++) {
					$asunto = new Asunto($this->sesion);
					$asunto->LoadByCodigo($this->asuntos[$k]);

					unset($GLOBALS['profesionales']);
					$profesionales = array();

					unset($GLOBALS['resumen_profesionales']);
					$resumen_profesionales = array();

					unset($GLOBALS['totales']);
					$totales = array();
					$totales['tiempo'] = 0;
					$totales['tiempo_trabajado'] = 0;
					$totales['tiempo_trabajado_real'] = 0;
					$totales['tiempo_retainer'] = 0;
					$totales['tiempo_flatfee'] = 0;
					$totales['tiempo_descontado'] = 0;
					$totales['tiempo_descontado_real'] = 0;
					$totales['valor'] = 0;
					$categoria_duracion_horas = 0;
					$categoria_duracion_minutos = 0;
					$categoria_valor = 0;
					$total_trabajos_categoria = '';
					$encabezado_trabajos_categoria = '';

					$query = "SELECT count(*) FROM tramite WHERE id_cobro=" . $this->fields['id_cobro'] . " AND codigo_asunto='" . $asunto->fields['codigo_asunto'] . "'";
					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
					list($cont_tramites) = mysql_fetch_array($resp);

					$query = "SELECT count(*) FROM trabajo WHERE id_cobro=" . $this->fields['id_cobro'] . " AND codigo_asunto='" . $asunto->fields['codigo_asunto'] . "' AND id_tramite=0";
					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
					list($cont_trabajos) = mysql_fetch_array($resp);

					$query = "SELECT count(*) FROM cta_corriente WHERE id_cobro=" . $this->fields['id_cobro'] . " AND codigo_asunto='" . $asunto->fields['codigo_asunto'] . "'";
					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
					list($cont_gastos) = mysql_fetch_array($resp);

					$row = $row_tmpl;

					if (count($this->asuntos) > 1) {
						$row = str_replace('%salto_pagina_varios_asuntos%', '&nbsp;<br clear=all style="mso-special-character:line-break; page-break-before:always" size="1" class="divisor">', $row);
						$row = str_replace('%salto_pagina_un_asunto%', '', $row);
						$row = str_replace('%asunto_extra%', __('Asunto'), $row);
						$row = str_replace('%glosa_asunto_sin_codigo_extra%', $asunto->fields['glosa_asunto'], $row);
					} else {
						$row = str_replace('%salto_pagina_varios_asuntos%', '', $row);
						$row = str_replace('%salto_pagina_un_asunto%', '&nbsp;<br clear=all style="mso-special-character:line-break; page-break-before:always" size="1" class="divisor">', $row);
						$row = str_replace('%asunto_extra%', '', $row);
						$row = str_replace('%glosa_asunto_sin_codigo_extra%', '', $row);
					}

					$row = str_replace('%asunto%', __('Asunto'), $row);

					if (Conf::GetConf($this->sesion, 'GlosaAsuntoSinCodigo')) {
						$row = str_replace('%glosa_asunto%', $asunto->fields['glosa_asunto'], $row);
						$row = str_replace('%glosa_asunto_secundario%', $asunto->fields['glosa_asunto'], $row);
					} else {
						$row = str_replace('%glosa_asunto%', $asunto->fields['codigo_asunto'] . " - " . $asunto->fields['glosa_asunto'], $row);
						$row = str_replace('%glosa_asunto_secundario%', $asunto->fields['codigo_asunto_secundario'] . " - " . $asunto->fields['glosa_asunto'], $row);
					}

					$row = str_replace('%glosa_asunto_sin_codigo%', $asunto->fields['glosa_asunto'], $row);
					$row = str_replace('%glosa_asunto_codigo_area%', $asunto->fields['codigo_asunto'] . '-' . sprintf("%02d", ($asunto->fields['id_area_proyecto'] - 1)) . " - " . $asunto->fields['glosa_asunto'], $row);
					$row = str_replace('%valor_codigo_asunto%', $asunto->fields['codigo_asunto'], $row);
					$row = str_replace('%codigo_cliente_secundario%', empty($cliente->fields['codigo_cliente_secundario']) ? '' : __('C�digo Cliente'), $row);
					$row = str_replace('%valor_codigo_cliente_secundario%', empty($cliente->fields['codigo_cliente_secundario']) ? '' : empty($cliente->fields['codigo_cliente_secundario']), $row);
					$row = str_replace('%contacto%', empty($asunto->fields['contacto']) ? '' : __('Contacto'), $row);
					$row = str_replace('%valor_contacto%', empty($asunto->fields['contacto']) ? '' : $asunto->fields['contacto'], $row);
					$row = str_replace('%registro%', __('Registro de Tiempo'), $row);
					$row = str_replace('%telefono%', empty($asunto->fields['fono_contacto']) ? '' : __('Tel�fono'), $row);
					$row = str_replace('%valor_telefono%', empty($asunto->fields['fono_contacto']) ? '' : $asunto->fields['fono_contacto'], $row);

					if ($cont_trabajos > 0) {

						if ($this->fields["opc_ver_detalles_por_hora"] == 1) {
							$row = str_replace('%espacio_trabajo%', '<br>', $row);
							$row = str_replace('%servicios%', __('Servicios prestados'), $row);
							$row = str_replace('%TRABAJOS_ENCABEZADO%', $this->GenerarDocumento($parser, 'TRABAJOS_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
							$row = str_replace('%TRABAJOS_FILAS%', $this->GenerarDocumento($parser, 'TRABAJOS_FILAS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
							$row = str_replace('%TRABAJOS_TOTAL%', $this->GenerarDocumento($parser, 'TRABAJOS_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
						} else {
							$row = str_replace('%espacio_trabajo%', '', $row);
							$row = str_replace('%servicios%', '', $row);
							$row = str_replace('%TRABAJOS_ENCABEZADO%', '', $row);
							$row = str_replace('%TRABAJOS_FILAS%', '', $row);
							$row = str_replace('%TRABAJOS_TOTAL%', '', $row);
						}

						$row = str_replace('%DETALLE_PROFESIONAL%', $this->GenerarDocumento($parser, 'DETALLE_PROFESIONAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
					} else {

						$row = str_replace('%espacio_trabajo%', '', $row);
						$row = str_replace('%DETALLE_PROFESIONAL%', '', $row);
						$row = str_replace('%servicios%', '', $row);
						$row = str_replace('%TRABAJOS_ENCABEZADO%', '', $row);
						$row = str_replace('%TRABAJOS_FILAS%', '', $row);
						$row = str_replace('%TRABAJOS_TOTAL%', '', $row);
					}

					if ($cont_tramites > 0) {

						$row = str_replace('%espacio_tramite%', '<br>', $row);
						$row = str_replace('%servicios_tramites%', __('Tr�mites'), $row);
						$row = str_replace('%titulo_seccion_tramites_castropal%', __('Otros Servicios'), $row);
						$row = str_replace('%servicios_tramites_castropal%', __('Otros Servicios Profesionales'), $row);
						$row = str_replace('%TRAMITES_ENCABEZADO%', $this->GenerarDocumentoComun($parser, 'TRAMITES_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
						$row = str_replace('%TRAMITES_FILAS%', $this->GenerarDocumentoComun($parser, 'TRAMITES_FILAS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
						$row = str_replace('%TRAMITES_TOTAL%', $this->GenerarDocumentoComun($parser, 'TRAMITES_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
					} else {

						$row = str_replace('%espacio_tramite%', '', $row);
						$row = str_replace('%servicios_tramites%', '', $row);
						$row = str_replace('%titulo_seccion_tramites_castropal%', '', $row);
						$row = str_replace('%servicios_tramites_castropal%', '', $row);
						$row = str_replace('%TRAMITES_ENCABEZADO%', '', $row);
						$row = str_replace('%TRAMITES_FILAS%', '', $row);
						$row = str_replace('%TRAMITES_TOTAL%', '', $row);
					}

					if (Conf::GetConf($this->sesion, 'ParafoGastosSoloSiHayGastos')) {
						if ($cont_gastos > 0) {
							$row = str_replace('%GASTOS%', $this->GenerarDocumento($parser, 'GASTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
						} else {
							$row = str_replace('%GASTOS%', '', $row);
						}
					} else {
						$row = str_replace('%GASTOS%', $this->GenerarDocumento($parser, 'GASTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
					}

					#especial mb

					$row = str_replace('%codigo_asunto_mb%', __('C�digo M&B'), $row);

					if ($asunto->fields['trabajos_total_duracion'] > 0 || $asunto->fields['trabajos_total_duracion_trabajada'] > 0 || $cont_tramites > 0 || Conf::GetConf($this->sesion, 'MostrarAsuntosSinTrabajosGastosTramites')) {
						$html .= $row;
					}
				}
				break;

			case 'TRAMITES': //GenerarDocumento

				$row_tmpl = $html;
				$html = '';

				for ($k = 0; $k < count($this->asuntos); $k++) {
					$asunto = new Asunto($this->sesion);
					$asunto->LoadByCodigo($this->asuntos[$k]);

					unset($GLOBALS['profesionales']);
					$profesionales = array();

					unset($GLOBALS['resumen_profesionales']);
					$resumen_profesionales = array();

					unset($GLOBALS['totales']);
					$totales = array();
					$totales['tiempo_tramites'] = 0;
					$totales['tiempo_tramites_trabajado'] = 0;
					$totales['tiempo_tramites_retainer'] = 0;
					$totales['tiempo_tramites_flatfee'] = 0;
					$totales['tiempo_tramites_descontado'] = 0;
					$totales['valor_tramites'] = 0;
					$categoria_duracion_horas = 0;
					$categoria_duracion_minutos = 0;
					$categoria_valor = 0;
					$total_trabajos_categoria = '';
					$encabezado_trabajos_categoria = '';

					$query = "SELECT count(*) FROM CTA_CORRIENTE
									 WHERE id_cobro=" . $this->fields['id_cobro'];
					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, $this->sesion->dbh);
					list($cont_gastos) = mysql_fetch_array($resp);

					$row = $row_tmpl;

					if (count($this->asuntos) > 1) {
						$row = str_replace('%salto_pagina_varios_asuntos%', '&nbsp;<br clear=all style="mso-special-character:line-break; page-break-before:always" size="1" class="divisor">', $row);
						$row = str_replace('%salto_pagina_un_asunto%', '', $row);
						$row = str_replace('%asunto_extra%', __('Asunto'), $row);
						$row = str_replace('%glosa_asunto_sin_codigo_extra%', $asunto->fields['glosa_asunto'], $row);
					} else {
						$row = str_replace('%salto_pagina_varios_asuntos%', '', $row);
						$row = str_replace('%salto_pagina_un_asunto%', '&nbsp;<br clear=all style="mso-special-character:line-break; page-break-before:always" size="1" class="divisor">', $row);
						$row = str_replace('%asunto_extra%', '', $row);
						$row = str_replace('%glosa_asunto_sin_codigo_extra%', '', $row);
					}

					$row = str_replace('%asunto%', __('Asunto'), $row);
					$row = str_replace('%glosa_asunto%', $asunto->fields['codigo_asunto'] . " - " . $asunto->fields['glosa_asunto'], $row);
					$row = str_replace('%glosa_asunto_sin_codigo%', $asunto->fields['glosa_asunto'], $row);
					$row = str_replace('%glosa_asunto_codigo_area%', $asunto->fields['codigo_asunto'] . '-' . sprintf("%02d", ($asunto->fields['id_area_proyecto'] - 1)) . " - " . $asunto->fields['glosa_asunto'], $row);
					$row = str_replace('%valor_codigo_asunto%', $asunto->fields['codigo_asunto'], $row);
					$row = str_replace('%codigo_cliente_secundario%', empty($cliente->fields['codigo_cliente_secundario']) ? '' : __('C�digo Cliente'), $row);
					$row = str_replace('%valor_codigo_cliente_secundario%', empty($cliente->fields['codigo_cliente_secundario']) ? '' : empty($cliente->fields['codigo_cliente_secundario']), $row);
					$row = str_replace('%contacto%', empty($asunto->fields['contacto']) ? '' : __('Contacto'), $row);
					$row = str_replace('%valor_contacto%', empty($asunto->fields['contacto']) ? '' : $asunto->fields['contacto'], $row);
					$row = str_replace('%servicios%', __('Servicios prestados'), $row);
					$row = str_replace('%registro%', __('Registro de Tiempo'), $row);
					$row = str_replace('%telefono%', empty($asunto->fields['fono_contacto']) ? '' : __('Tel�fono'), $row);
					$row = str_replace('%valor_telefono%', empty($asunto->fields['fono_contacto']) ? '' : $asunto->fields['fono_contacto'], $row);

					// SECCION TRAMITES GENERADO EN FUNCION GenerarDcumentoComun
					$row = str_replace('%TRAMITES_ENCABEZADO%', $this->GenerarDocumentoComun($parser, 'TRAMITES_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
					$row = str_replace('%TRAMITES_FILAS%', $this->GenerarDocumentoComun($parser, 'TRAMITES_FILAS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
					$row = str_replace('%TRAMITES_TOTAL%', $this->GenerarDocumentoComun($parser, 'TRAMITES_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);

					$row = str_replace('%DETALLE_PROFESIONAL%', $this->GenerarDocumento($parser, 'DETALLE_PROFESIONAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);

					if (Conf::GetConf($this->sesion, 'ParafoGastosSoloSiHayGastos')) {
						if ($cont_gastos > 0) {
							$row = str_replace('%GASTOS%', $this->GenerarDocumento($parser, 'GASTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
						} else {
							$row = str_replace('%GASTOS%', '', $row);
						}
					} else {
						$row = str_replace('%GASTOS%', $this->GenerarDocumento($parser, 'GASTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
					}

					$row = str_replace('%codigo_asunto_mb%', __('C�digo M&B'), $row);

					if ($asunto->fields['trabajos_total_duracion'] > 0 || $asunto->fields['trabajos_total_duracion_trabajada'] > 0) {
						$html .= $row;
					}
				}
				break;

			case 'TRABAJOS_ENCABEZADO': //GenerarDocumento

				if ($this->fields['estado'] == 'CREADO' || $this->fields['estado'] == 'EN REVISION') {
					$html = str_replace('%td_id_trabajo%', '<td align="center">%ntrabajo%</td>', $html);
				} else {
					$html = str_replace('%td_id_trabajo%', '', $html);
				}

				$html = str_replace('%ntrabajo%', __('N�</br>Trabajo'), $html);

				if ($this->fields['opc_ver_solicitante']) {
					$html = str_replace('%td_solicitante%', '<td width="16%" align="left">%solicitante%</td>', $html);
				} else {
					$html = str_replace('%td_solicitante%', '', $html);
				}

				$html = str_replace('%solicitante%', __('Solicitado Por'), $html);
				$html = str_replace('%ordenado_por%', $this->fields['opc_ver_solicitante'] ? __('Ordenado Por') : '', $html);
				$html = str_replace('%ordenado_por_jjr%', $this->fields['opc_ver_solicitante'] ? __('Solicitado Por') : '', $html);
				$html = str_replace('%periodo%', (($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') and ( $this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '')) ? '' : __('Periodo'), $html);
				$html = str_replace('%valor_periodo_ini%', ($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') ? '' : Utiles::sql2fecha($this->fields['fecha_ini'], $idioma->fields['formato_fecha']), $html);
				$html = str_replace('%valor_periodo_fin%', ($this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '') ? '' : __('hasta') . ' ' . Utiles::sql2fecha($this->fields['fecha_fin'], $idioma->fields['formato_fecha']), $html);
				$html = str_replace('%cliente%', __('Cliente'), $html);
				$html = str_replace('%glosa_cliente%', $cliente->fields['glosa_cliente'], $html);
				$html = str_replace('%asunto%', __('Asunto'), $html);
				$html = str_replace('%glosa_asunto%', $asunto->fields['glosa_asunto'], $html);
				$html = str_replace('%fecha%', __('Fecha'), $html);
				$html = str_replace('%descripcion%', __('Descripci�n'), $html);
				$html = str_replace('%servicios_prestados%', __('Servicios Prestados'), $html);
				$html = str_replace('%detalle_trabajo%', __('Detalle del Trabajo Realizado'), $html);
				$html = str_replace('%profesional%', __('Profesional'), $html);
				$html = str_replace('%staff%', __('Staff'), $html);
				$html = str_replace('%abogado%', __('Abogado'), $html);
				$html = str_replace('%abogado_raz%', __('abogado_raz'), $html);
				$html = str_replace('%tiempo_raz%', __('tiempo_raz'), $html);
				$html = str_replace('%duracion_cobrable%', __('Duraci�n cobrable'), $html);
				$html = str_replace('%monto_total%', __('Monto total'), $html);
				$html = str_replace('%Total%', __('Total'), $html);

				if ($lang == 'es') {
					$html = str_replace('%id_asunto%', __('ID Asunto'), $html);
					$html = str_replace('%tarifa_hora%', __('Tarifa<br>Hora'), $html);
				} else {
					$html = str_replace('%id_asunto%', __('Matter <br> ID'), $html);
					$html = str_replace('%tarifa_hora%', __('Hourly<br> Rate'), $html);
				}

				$html = str_replace('%horas%', __('Horas'), $html);
				$html = str_replace('%monto%', __('Monto'), $html);

				if ($this->fields['opc_ver_columna_cobrable']) {
					$html = str_replace('%cobrable%', __('<td align="center" width="80">Cobrable</td>'), $html);  // tAndres Oestemer
				} else {
					$html = str_replace('%cobrable%', '', $html);
				}

				if ($this->fields['opc_ver_detalles_por_hora_categoria'] == 1) {
					$html = str_replace('%td_categoria%', '<td>&nbsp;</td>', $html);
				} else {
					$html = str_replace('%td_categoria%', '', $html);
				}

				if (Conf::GetConf($this->sesion, 'TrabajosOrdenarPorCategoriaUsuario')) {

					if ($lang == 'es') {
						$query_categoria_lang = "cat.glosa_categoria";
					} else {
						$query_categoria_lang = "IFNULL(cat.glosa_categoria_lang , cat.glosa_categoria)";
					}

					$query = "SELECT $query_categoria_lang
									FROM trabajo
									JOIN usuario ON trabajo.id_usuario=usuario.id_usuario
									JOIN prm_categoria_usuario AS cat ON cat.id_categoria_usuario=usuario.id_categoria_usuario
									WHERE trabajo.id_cobro = '" . $this->fields['id_cobro'] . "'
									AND trabajo.codigo_asunto = '" . $asunto->fields['codigo_asunto'] . "'
									AND trabajo.visible=1
									ORDER BY cat.orden, usuario.id_usuario, trabajo.fecha ASC
									LIMIT 1";
					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
					list($categoria) = mysql_fetch_array($resp);

					$html = str_replace('%categoria_abogado%', __($categoria), $html);
				} else if (Conf::GetConf($this->sesion, 'SepararPorUsuario')) {

					$query = "SELECT
								CONCAT(usuario.nombre,' ',usuario.apellido1),
								trabajo.tarifa_hh
								FROM trabajo
									JOIN usuario ON trabajo.id_usuario=usuario.id_usuario
										WHERE trabajo.id_cobro = '" . $this->fields['id_cobro'] . "'
											AND trabajo.codigo_asunto = '" . $asunto->fields['codigo_asunto'] . "'
											AND trabajo.visible=1
												ORDER BY usuario.id_categoria_usuario, usuario.id_usuario, trabajo.fecha ASC
													LIMIT 1";

					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
					list($abogado, $tarifa) = mysql_fetch_array($resp);
					$html = str_replace('%categoria_abogado%', __($abogado), $html);
					$html = str_replace('%tarifa%', $moneda->fields['simbolo'] . $this->espacio . number_format($tarifa, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				} else {
					$html = str_replace('%categoria_abogado%', '', $html);
				}

				//Por conf se ve si se imprime la duracion trabajada cuando el cobro este en estado creado tambien
				$ImprimirDuracionTrabajada = Conf::GetConf($this->sesion, 'ImprimirDuracionTrabajada');

				/* Lo anchores con la extension _bmahj usa Bofill Mir y lo que hace es que llama a las columnas
				  en la lista de trabajos igual como a las columnas en el resumen profesional */

				if ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') {
					$html = str_replace('%td_retainer%', '<td width="80" align="center">%duracion_retainer%</td>', $html);
					$html = str_replace('%duracion_retainer%', __('Duraci�n Retainer'), $html);
				} else {
					$html = str_replace('%td_retainer%', '', $html);
				}

				if ($this->fields['forma_cobro'] == 'FLAT FEE') {
					$html = str_replace('%duracion_trabajada_bmahj%', '', $html);
					$html = str_replace('%duracion_descontada_bmahj%', '', $html);
					$html = str_replace('%duracion_bmahj%', __('Hrs. Trabajadas'), $html);
					$html = str_replace('%tiempo%', __('Tiempo'), $html);
					$html = str_replace('%duracion_trabajada%', '', $html);
					$html = str_replace('%duracion_descontada%', '', $html);
					$html = str_replace('%duracion%', __('Duraci�n trabajada'), $html);
				}

				if ($ImprimirDuracionTrabajada && ( $this->fields['estado'] == 'CREADO' || $this->fields['estado'] == 'EN REVISION' )) {

					$html = str_replace('%duracion_trabajada_bmahj%', __('Hrs. Trabajadas'), $html);
					$html = str_replace('%duracion_bmahj%', __('Hrs. Tarificadas'), $html);
					$html = str_replace('%tiempo%', __('Tiempo'), $html);

					if ($descontado) {
						$html = str_replace('%duracion_descontada_bmahj%', __('Hrs. Descontadas'), $html);
					} else {
						$html = str_replace('%duracion_descontada_bmahj%', '', $html);
					}

					$html = str_replace('%duracion_trabajada%', __('Duraci�n trabajada'), $html);
					$html = str_replace('%duracion%', __('Duraci�n cobrable'), $html);

					if ($descontado) {
						$html = str_replace('%duracion_descontada%', __('Duraci�n descontada'), $html);
					} else {
						$html = str_replace('%duracion_descontada%', '', $html);
					}
				} else if ($this->fields['opc_ver_horas_trabajadas']) {

					$html = str_replace('%duracion_trabajada_bmahj%', __('Hrs. Trabajadas'), $html);
					$html = str_replace('%duracion_bmahj%', __('Hrs. Tarificadas'), $html);
					$html = str_replace('%tiempo%', __('Tiempo'), $html);
					$html = str_replace('%duracion_descontada_bmahj%', __('Hrs. Descontadas'), $html);

					$html = str_replace('%duracion_trabajada%', __('Duraci�n trabajada'), $html);
					$html = str_replace('%duracion%', __('Duraci�n cobrable'), $html);
					$html = str_replace('%duracion_descontada%', __('Duraci�n castigada'), $html);
				} else {

					$html = str_replace('%duracion_trabajada_bmahj%', '', $html);
					$html = str_replace('%duracion_descontada_bmahj%', '', $html);
					$html = str_replace('%duracion_bmahj%', __('Hrs. Tarificadas'), $html);
					$html = str_replace('%tiempo%', __('Tiempo'), $html);
					$html = str_replace('%duracion_trabajada%', '', $html);
					$html = str_replace('%duracion_descontada%', '', $html);
					$html = str_replace('%duracion%', __('Duraci�n'), $html);
				}

				$html = str_replace('%duracion_tyc%', __('Duraci�n'), $html);
				//Por conf se ve si se imprime o no el valor del trabajo
				$ImprimirValorTrabajo = Conf::GetConf($this->sesion, 'ImprimirValorTrabajo');

				if ($ImprimirValorTrabajo && $this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION') {
					$html = str_replace('%valor%', '', $html);
				} else {
					$html = str_replace('%valor%', __('Valor'), $html);
				}

				$html = str_replace('%valor_siempre%', __('Valor'), $html);
				$html = str_replace('%tarifa_fee%', __('%tarifa_fee%'), $html);

				if ($this->fields['opc_ver_detalles_por_hora_tarifa'] == 1) {
					$html = str_replace('%td_tarifa%', '<td width="80" align="center">%tarifa%</td>', $html);
					$html = str_replace('%td_tarifa_ajustada%', '<td width="80" align="center">%tarifa%</td>', $html);
				} else {
					$html = str_replace('%td_tarifa%', '', $html);
					$html = str_replace('%td_tarifa_ajustada%', '', $html);
				}

				$html = str_replace('%tarifa%', __('Tarifa'), $html);

				if ($this->fields['opc_ver_detalles_por_hora_importe'] == 1) {
					$html = str_replace('%td_importe%', '<td width="80" align="center">%importe%</td>', $html);
					$html = str_replace('%td_importe_ajustado%', '<td width="80" align="center">%importe%</td>', $html);
				} else {
					$html = str_replace('%td_importe%', '', $html);
					$html = str_replace('%td_importe_ajustado%', '', $html);
				}
				$html = str_replace('%importe%', __($this->fields['codigo_idioma'] . '_Importe'), $html);
				break;

			case 'TRABAJOS_FILAS': //GenerarDocumento
				global $categoria_duracion_horas;
				global $categoria_duracion_minutos;
				global $categoria_valor;
				global $resumen_profesional_id_usuario;
				global $resumen_profesional_nombre;
				global $resumen_profesional_username;
				global $resumen_profesional_hrs_trabajadas;
				global $resumen_profesional_hrs_retainer;
				global $resumen_profesional_hrs_descontadas;
				global $resumen_profesional_hh;
				global $resumen_profesional_valor_hh;
				global $resumen_profesional_categoria;
				global $resumen_profesional_id_categoria;
				global $resumen_profesionales;

				$row_tmpl = $html;
				$html = '';
				$where_horas_cero = '';

				if ($lang == 'es') {
					$select_categoria = ", prm_categoria_usuario.glosa_categoria AS categoria, prm_categoria_usuario.id_categoria_usuario";
				} else {
					$select_categoria = ", IFNULL(prm_categoria_usuario.glosa_categoria_lang,prm_categoria_usuario.glosa_categoria) AS categoria, prm_categoria_usuario.id_categoria_usuario";
				}

				$join_categoria = "LEFT JOIN prm_categoria_usuario ON usuario.id_categoria_usuario=prm_categoria_usuario.id_categoria_usuario";

				//esto funciona por Conf si el metodo del conf OrdenarPorCategoriaUsuario es true se ordena por categoria
				if (Conf::GetConf($this->sesion, 'TrabajosOrdenarPorCategoriaNombreUsuario')) {
					$order_categoria = "prm_categoria_usuario.orden, usuario.nombre, usuario.apellido1, usuario.id_usuario, ";
				} else if (Conf::GetConf($this->sesion, 'TrabajosOrdenarPorCategoriaUsuario')) {
					$order_categoria = "prm_categoria_usuario.orden, usuario.id_usuario, ";
				} else if (Conf::GetConf($this->sesion, 'SepararPorUsuario')) {
					$order_categoria = "usuario.id_categoria_usuario, usuario.id_usuario, ";
				} else if (Conf::GetConf($this->sesion, 'TrabajosOrdenarPorCategoriaDetalleProfesional')) {
					$select_categoria = "";
					$order_categoria = "usuario.id_categoria_usuario DESC, ";
				} else if (Conf::GetConf($this->sesion, 'TrabajosOrdenarPorFechaCategoria')) {
					$order_categoria = "trabajo.fecha, usuario.id_categoria_usuario, usuario.id_usuario, ";
				} else {
					$select_categoria = "";
					$join_categoria = "";
					$order_categoria = "";
				}

				if (Conf::GetConf($this->sesion, 'MostrarHorasCero')) {
					if ($this->fields['opc_ver_horas_trabajadas']) {
						$where_horas_cero = "AND trabajo.duracion > '0000-00-00 00:00:00'";
					} else {
						$where_horas_cero = "AND trabajo.duracion_cobrada > '0000-00-00 00:00:00'";
					}
				}

				if ($this->fields['opc_ver_valor_hh_flat_fee'] && $this->fields['forma_cobro'] != 'ESCALONADA')
					$dato_monto_cobrado = " ( trabajo.tarifa_hh * TIME_TO_SEC( trabajo.duracion_cobrada ) ) / 3600 ";
				else
					$dato_monto_cobrado = " trabajo.monto_cobrado ";

				if ($this->fields['opc_ver_cobrable']) {
					$and .= "";
				} else {
					$and .= "AND trabajo.visible = 1";
				}

				//Tabla de Trabajos.
				//se hace select a los visibles y cobrables para diferenciarlos, tambien se selecciona
				//la duracion retainer.
				$query = "SELECT SQL_CALC_FOUND_ROWS
									trabajo.duracion_cobrada,
									trabajo.duracion_retainer,
									trabajo.descripcion,
									trabajo.fecha,
									trabajo.id_usuario,
									$dato_monto_cobrado as monto_cobrado,
									trabajo.visible,
									trabajo.cobrable,
									trabajo.id_trabajo,
									trabajo.tarifa_hh,
									trabajo.codigo_asunto,
									trabajo.solicitante,
									CONCAT_WS(' ', nombre, apellido1) as nombre_usuario,
									usuario.username,
									trabajo.duracion $select_categoria
							FROM trabajo
							LEFT JOIN usuario ON trabajo.id_usuario=usuario.id_usuario
							$join_categoria
							WHERE trabajo.id_cobro = '" . $this->fields['id_cobro'] . "'
							AND trabajo.codigo_asunto = '" . $asunto->fields['codigo_asunto'] . "'
							$and AND trabajo.id_tramite=0 $where_horas_cero
							ORDER BY $order_categoria trabajo.fecha ASC,trabajo.descripcion";

				$lista_trabajos = new ListaTrabajos($this->sesion, '', $query);

				$asunto->fields['trabajos_total_duracion'] = 0;
				$asunto->fields['trabajos_total_valor'] = 0;
				$asunto->fields['trabajos_total_duracion_retainer'] = 0;

				for ($i = 0; $i < $lista_trabajos->num; $i++) {
					$trabajo = $lista_trabajos->Get($i);
					list($ht, $mt, $st) = explode(":", $trabajo->fields['duracion']);
					list($h, $m, $s) = explode(":", $trabajo->fields['duracion_cobrada']);
					list($h_retainer, $m_retainer, $s_retainer) = explode(":", $trabajo->fields['duracion_retainer']);
					$duracion_cobrada_decimal = $h + $m / 60 + $s / 3600;
					$asunto->fields['trabajos_total_duracion'] += $h * 60 + $m + $s / 60;
					$asunto->fields['trabajos_total_valor'] += $trabajo->fields['monto_cobrado'];
					$asunto->fields['trabajos_total_duracion_retainer'] += $h_retainer * 60 + $m_retainer + $s_retainer / 60;
					$categoria_duracion_horas+=round($h);
					$categoria_duracion_minutos+=round($m);
					$categoria_valor+=$trabajo->fields['monto_cobrado'];

					if (!isset($profesionales[$trabajo->fields['nombre_usuario']])) {
						$profesionales[$trabajo->fields['nombre_usuario']] = array();
						$profesionales[$trabajo->fields['nombre_usuario']]['tiempo_trabajado_real'] = 0; // horas realmente trabajadas segun duracion en vez de duracion_cobrada
						$profesionales[$trabajo->fields['nombre_usuario']]['tiempo_trabajado'] = 0; //el tiempo trabajado es cobrable y no cobrable
						$profesionales[$trabajo->fields['nombre_usuario']]['tiempo'] = 0; //tiempo cobrable
						$profesionales[$trabajo->fields['nombre_usuario']]['valor'] = 0;
						$profesionales[$trabajo->fields['nombre_usuario']]['retainer'] = 0;
						$profesionales[$trabajo->fields['nombre_usuario']]['flatfee'] = 0;
						$profesionales[$trabajo->fields['nombre_usuario']]['descontado_real'] = 0;
						$profesionales[$trabajo->fields['nombre_usuario']]['descontado'] = 0; //tiempo no cobrable
						$profesionales[$trabajo->fields['nombre_usuario']]['tarifa'] = $trabajo->fields['tarifa_hh'];
						$profesionales[$trabajo->fields['nombre_usuario']]['id_categoria_usuario'] = $trabajo->fields['id_categoria_usuario']; //nombre de la categoria
						$profesionales[$trabajo->fields['nombre_usuario']]['categoria'] = $trabajo->fields['categoria']; // nombre de la categoria
					}
					if (Conf::GetConf($this->sesion, 'GuardarTarifaAlIngresoDeHora')) {
						$profesionales[$trabajo->fields['nombre_usuario']]['tarifa'] = $trabajo->fields['tarifa_hh'];
					}

					// Para mostrar un resumen de horas de cada profesional al principio del documento.
					for ($k = 0; $k < count($resumen_profesional_nombre); ++$k)
						if ($resumen_profesional_id_usuario[$k] == $trabajo->fields['id_usuario'])
							break;
					// Si el profesional no estaba en el resumen lo agregamos
					if ($k == count($resumen_profesional_nombre)) {
						$resumen_profesional_id_usuario[$k] = $trabajo->fields['id_usuario'];
						$resumen_profesional_nombre[$k] = $trabajo->fields['nombre_usuario'];
						$resumen_profesional_username[$k] = $trabajo->fields['username'];
						$resumen_profesional_hrs_trabajadas[$k] = 0;
						$resumen_profesional_hrs_retainer[$k] = 0;
						$resumen_profesional_hrs_descontadas[$k] = 0;
						$resumen_profesional_hh[$k] = 0;
						$resumen_profesional_valor_hh[$k] = $trabajo->fields['tarifa_hh'];
						$resumen_profesional_categoria[$k] = $trabajo->fields['categoria'];
						$resumen_profesional_id_categoria[$k] = $trabajo->fields['id_categoria_usuario'];
					}
					$resumen_profesional_hrs_trabajadas[$k] += $h + $m / 60 + $s / 3600;

					//se agregan los valores para el detalle de profesionales
					$profesionales[$trabajo->fields['nombre_usuario']]['tiempo_trabajado_real'] += $ht * 60 + $mt + $st / 60;
					$profesionales[$trabajo->fields['nombre_usuario']]['descontado_real'] += ( $ht - $h ) * 60 + ( $mt - $m ) + ( $st - $s ) / 60;
					$profesionales[$trabajo->fields['nombre_usuario']]['tiempo_trabajado'] += $h * 60 + $m + $s / 60;
					if ($this->fields['forma_cobro'] == 'FLAT FEE' && $trabajo->fields['cobrable'] == '1') {
						$profesionales[$trabajo->fields['nombre_usuario']]['flatfee'] += $h * 60 + $m + $s / 60;
					}
					if ($trabajo->fields['cobrable'] == '0') {
						$profesionales[$trabajo->fields['nombre_usuario']]['descontado_real'] += $ht * 60 + $mt + $st / 60;
						$profesionales[$trabajo->fields['nombre_usuario']]['descontado'] += $h * 60 + $m + $s / 60;
					} else {
						$profesionales[$trabajo->fields['nombre_usuario']]['tiempo'] += $h * 60 + $m + $s / 60;
						$profesionales[$trabajo->fields['nombre_usuario']]['valor'] += $trabajo->fields['monto_cobrado'];
					}
					if ($h_retainer * 60 + $m_retainer + $s_retainer / 60 > 0) {
						$profesionales[$trabajo->fields['nombre_usuario']]['retainer'] += $h_retainer * 60 + $m_retainer + $s_retainer / 60;
					}

					$row = $row_tmpl;

					if ($this->fields['opc_ver_detalles_por_hora_categoria'] == 1)
						$row = str_replace('%td_categoria%', '<td>&nbsp;</td>', $row);
					else
						$row = str_replace('%td_categoria%', '', $row);



					if ($this->fields['opc_ver_detalles_por_hora_tarifa'] == 1) {
						$row = str_replace('%td_tarifa%', '<td width="80" align="center">%tarifa%</td>', $row);
						$row = str_replace('%td_tarifa_ajustada%', '<td width="80" align="center">%tarifa%</td>', $row);
					} else {
						$row = str_replace('%td_tarifa%', '', $row);
						$row = str_replace('%td_tarifa_ajustada%', '', $row);
					}




					if ($this->fields['forma_cobro'] == 'ESCALONADA') {
						$row = str_replace('%tarifa%', number_format(($trabajo->fields['monto_cobrado'] / $duracion_cobrada_decimal), $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						$row = str_replace('%tarifa_ajustada%', number_format($trabajo->fields['tarifa_hh'] * $x_factor_ajuste, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					} else {
						$row = str_replace('%tarifa%', number_format($trabajo->fields['tarifa_hh'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						$row = str_replace('%tarifa_ajustada%', number_format($trabajo->fields['tarifa_hh'] * $x_factor_ajuste, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					}

					$row = str_replace('%fecha%', Utiles::sql2fecha($trabajo->fields['fecha'], $idioma->fields['formato_fecha']), $row);
					if ($this->fields['estado'] == 'CREADO' || $this->fields['estado'] == 'EN REVISION') {
						$row = str_replace('%td_id_trabajo%', '<td align="center">%ntrabajo%</td>', $row);
					} else {
						$row = str_replace('%td_id_trabajo%', '', $row);
					}
					$row = str_replace('%ntrabajo%', $trabajo->fields['id_trabajo'], $row);
					$row = str_replace('%descripcion%', ucfirst(stripslashes($trabajo->fields['descripcion'])), $row);
					if ($this->fields['opc_ver_solicitante']) {
						$row = str_replace('%td_solicitante%', '<td align="left">%solicitante%</td>', $row);
					} else {
						$row = str_replace('%td_solicitante%', '', $row);
					}
					$row = str_replace('%solicitante%', $this->fields['opc_ver_solicitante'] ? $trabajo->fields['solicitante'] : '', $row);

					if ($this->fields['opc_ver_detalles_por_hora_iniciales']) {
						$row = str_replace('%profesional%', $trabajo->fields['username'], $row);
					} else {
						$row = str_replace('%profesional%', $trabajo->fields['nombre_usuario'], $row);
					}
					$row = str_replace('%valor_codigo_asunto%', $asunto->fields['codigo_asunto'], $row);
					//paridad
					$row = str_replace('%paridad%', $i % 2 ? 'impar' : 'par', $row);

					//muestra las iniciales de los profesionales
					list($nombre, $apellido_paterno, $extra, $extra2) = explode(' ', $trabajo->fields['nombre_usuario'], 4);
					$row = str_replace('%iniciales%', $nombre[0] . $apellido_paterno[0] . $extra[0] . $extra2[0], $row);

					$row = str_replace('%username%', $trabajo->fields['username'], $row);

					if ($this->fields['opc_ver_columna_cobrable']) {
						if ($trabajo->fields['cobrable'] == 1)
							$row = str_replace('%cobrable%', __('<td align="center">Si</td>'), $row);
						else
							$row = str_replace('%cobrable%', __('<td align="center">No</td>'), $row);
					} else
						$row = str_replace('%cobrable%', __(''), $row);

					if ($ht < $h || ( $ht == $h && $mt < $m ) || ( $ht == $h && $mt == $m && $st < $s ))
						$asunto->fields['trabajos_total_duracion_trabajada'] += $h * 60 + $m + $s / 60;
					else
						$asunto->fields['trabajos_total_duracion_trabajada'] += $ht * 60 + $mt + $st / 60;
					$duracion_decimal_trabajada = $ht + $mt / 60 + $st / 3600;
					$duracion_decimal_descontada = $ht - $h + ($mt - $m) / 60 + ($st - $s) / 3600;
					$minutos_decimal = $m / 60;
					$duracion_decimal = $h + $minutos_decimal + $s / 3600;

					if (($mt - $m) < 0) {
						$horas_descontadas = $ht - $h - 1;
						$minutos_descontadas = $mt - $m + 60;
					} else {
						$horas_descontadas = $ht - $h;
						$minutos_descontadas = $mt - $m;
					}

					$ImprimirDuracionTrabajada = Conf::GetConf($this->sesion, 'ImprimirDuracionTrabajada');

					if ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') {
						$row = str_replace('%td_retainer%', '<td width="80" align="center">%duracion_retainer%</td>', $row);
						$row = str_replace('%duracion_retainer%', $h_retainer . ':' . sprintf("%02d", $m_retainer), $row);
					} else {
						$row = str_replace('%td_retainer%', '', $row);
					}

					if ($this->fields['forma_cobro'] == 'FLAT FEE') {
						$row = str_replace('%duracion_decimal_trabajada%', '', $row);
						$row = str_replace('%duracion_trabajada%', '', $row);
						$row = str_replace('%duracion_decimal_descontada%', '', $row);
						$row = str_replace('%duracion_descontada%', '', $row);
						if (!$this->fields['opc_ver_horas_trabajadas']) {
							$row = str_replace('%duracion_decimal%', number_format($duracion_decimal, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
							$row = str_replace('%duracion%', $h . ':' . sprintf("%02d", $m), $row);
						} else {
							$row = str_replace('%duracion_decimal%', number_format($duracion_decimal_trabajada, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
							$row = str_replace('%duracion%', $ht . ':' . $mt, $row);
						}
					}
					if ($ImprimirDuracionTrabajada && ( $this->fields['estado'] == 'CREADO' || $this->fields['estado'] == 'EN REVISION' )) {
						$row = str_replace('%duracion_decimal_trabajada%', number_format($duracion_decimal_trabajada, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						if ($horas_descontadas < 0 || $minutos_descontadas < 0)
							$row = str_replace('%duracion_trabajada%', $h . ':' . sprintf("%02d", $m), $row);
						else
							$row = str_replace('%duracion_trabajada%', $ht . ':' . sprintf("%02d", $mt), $row);
						if ($horas_descontadas < 0 || $minutos_descontadas < 0)
							$row = str_replace('%duracion_descontada%', '0:00', $row);
						else
							$row = str_replace('%duracion_descontada%', $horas_descontadas . ':' . sprintf("%02d", $minutos_descontadas), $row);
						$row = str_replace('%duracion_decimal_descontada%', number_format($duracion_decimal_descontada, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					}
					else if ($this->fields['opc_ver_horas_trabajadas']) {
						$row = str_replace('%duracion_decimal_trabajada%', number_format($duracion_decimal_trabajada, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						if ($horas_descontadas < 0 || $minutos_descontadas < 0) {
							$row = str_replace('%duracion_trabajada%', $h . ':' . sprintf("%02d", $m), $row);
							$row = str_replace('%duracion_descontada%', '0:00', $row);
						} else {
							$row = str_replace('%duracion_trabajada%', $ht . ':' . sprintf("%02d", $mt), $row);
							$row = str_replace('%duracion_descontada%', $horas_descontadas . ':' . sprintf("%02d", $minutos_descontadas), $row);
						}
						$row = str_replace('%duracion_decimal_descontada%', number_format($duracion_decimal_descontada, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					} else {
						$row = str_replace('%duracion_descontada%', '', $row);
						$row = str_replace('%duracion_decimal_descontada%', '', $row);
						$row = str_replace('%duracion_decimal_trabajada%', '', $row);
						$row = str_replace('%duracion_trabajada%', '', $row);
					}

					$row = str_replace('%duracion_decimal%', number_format($duracion_decimal, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					$row = str_replace('%duracion%', $h . ':' . $m, $row);

					$ImprimirValorTrabajo = Conf::GetConf($this->sesion, 'ImprimirValorTrabajo');

					if ($ImprimirValorTrabajo && $this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION') {
						$row = str_replace('%valor%', '', $row);
						$row = str_replace('%valor_cyc%', '', $row);
					} else {
						$row = str_replace('%valor%', number_format($trabajo->fields['monto_cobrado'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						$row = str_replace('%valor_con_moneda%', $moneda->fields['simbolo'] . " " . number_format($trabajo->fields['monto_cobrado'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						$row = str_replace('%valor_cyc%', number_format($trabajo->fields['monto_cobrado'] * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					}

					if ($this->fields['opc_ver_detalles_por_hora_importe'] == 1) {
						$row = str_replace('%td_importe%', '<td width="80" align="center">%valor_siempre%</td>', $row);
						$row = str_replace('%td_importe_ajustado%', '<td width="80" align="center">%valor_siempre%</td>', $row);
					} else {
						$row = str_replace('%td_importe%', '', $row);
						$row = str_replace('%td_importe_ajustado%', '', $row);
					}

					$row = str_replace('%valor_siempre%', number_format($trabajo->fields['monto_cobrado'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					$row = str_replace('%categoria_usuario%', '', $row);
					if (Conf::GetConf($this->sesion, 'OrdenarPorCategoriaUsuario')) {
						$trabajo_siguiente = $lista_trabajos->Get($i + 1);
						if (!empty($trabajo_siguiente->fields['id_categoria_usuario'])) {
							if ($trabajo->fields['id_categoria_usuario'] != $trabajo_siguiente->fields['id_categoria_usuario']) {
								$html3 = $parser->tags['TRABAJOS_TOTAL'];
								$html3 = str_replace('%glosa%', __('Total'), $html3);
								$categoria_duracion_horas += floor($categoria_duracion_minutos / 60);
								$categoria_duracion_minutos = round($categoria_duracion_minutos % 60);
								$html3 = str_replace('%duracion%', sprintf('%02d', $categoria_duracion_horas) . ':' . sprintf('%02d', $categoria_duracion_minutos), $html3);

								if (Conf::GetConf($this->sesion, 'NoImprimirValorTrabajo') && $this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION') {
									$html3 = str_replace('%valor%', '', $html3);
									$html3 = str_replace('%valor_cyc%', '', $html3);
								} else {
									$html3 = str_replace('%valor%', $moneda->fields['simbolo'] . $this->espacio . number_format($categoria_valor, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
									$html3 = str_replace('%valor_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($categoria_valor * ( $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
								}

								$total_trabajos_categoria .= $html3;

								$html3 = $parser->tags['TRABAJOS_ENCABEZADO'];
								$html3 = str_replace('%duracion%', __('Duraci�n'), $html3);
								$html3 = str_replace('%cobrable%', __('<td align="center" width="80">Cobrable</td>'), $html3);
								$html3 = str_replace('%fecha%', __('Fecha'), $html3);
								$html3 = str_replace('%descripcion%', __('Descripci�n'), $html3);
								$html3 = str_replace('%profesional%', __('Profesional'), $html3);
								$html3 = str_replace('%abogado%', __('Abogado'), $html3);

								$html3 = str_replace('%categoria_abogado%', __($trabajo_siguiente->fields['categoria']), $html3);
								if (Conf::GetConf($this->sesion, 'NoImprimirValorTrabajo') && $this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION') {
									$html3 = str_replace('%valor%', '', $html3);
									$html3 = str_replace('%valor_cyc%', '', $html3);
								} else {
									$html3 = str_replace('%valor%', __('Valor'), $html3);
									$html3 = str_replace('%valor_cyc%', __('Valor'), $html3);
								}
								$encabezado_trabajos_categoria .= $html3;

								$row = str_replace('%TRABAJOS_CATEGORIA%', $total_trabajos_categoria . $encabezado_trabajos_categoria, $row);
								$categoria_duracion_horas = 0;
								$categoria_duracion_minutos = 0;
								$categoria_valor = 0;
								$total_trabajos_categoria = '';
								$encabezado_trabajos_categoria = '';
							} else {
								$row = str_replace('%TRABAJOS_CATEGORIA%', '', $row);
							}
						} else {
							$html3 = $parser->tags['TRABAJOS_TOTAL'];
							$html3 = str_replace('%glosa%', __('Total'), $html3);
							$categoria_duracion_horas += floor($categoria_duracion_minutos / 60);
							$categoria_duracion_minutos = round($categoria_duracion_minutos % 60);
							$html3 = str_replace('%duracion%', sprintf('%02d', $categoria_duracion_horas) . ':' . sprintf('%02d', $categoria_duracion_minutos), $html3);
							if ($this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION' && Conf::GetConf($this->sesion, 'NoImprimirValorTrabajo')) {
								$html3 = str_replace('%valor%', '', $html3);
								$html3 = str_replace('%valor_cyc%', '', $html3);
							} else {
								$html3 = str_replace('%valor%', $moneda->fields['simbolo'] . $this->espacio . number_format($categoria_valor, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
								$html3 = str_replace('%valor_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($categoria_valor * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
							}

							$total_trabajos_categoria .= $html3;
							$row = str_replace('%TRABAJOS_CATEGORIA%', $total_trabajos_categoria, $row);
							$categoria_duracion_horas = 0;
							$categoria_duracion_minutos = 0;
							$categoria_valor = 0;
							$total_trabajos_categoria = '';
							$encabezado_trabajos_categoria = '';
						}
					}
					if (Conf::GetConf($this->sesion, 'SepararPorUsuario')) {
						$trabajo_siguiente = $lista_trabajos->Get($i + 1);
						if (!empty($trabajo_siguiente->fields['nombre_usuario'])) {
							if ($trabajo->fields['nombre_usuario'] != $trabajo_siguiente->fields['nombre_usuario']) {
								$html3 = $parser->tags['TRABAJOS_TOTAL'];
								$html3 = str_replace('%glosa%', __('Total'), $html3);
								$categoria_duracion_horas += floor($categoria_duracion_minutos / 60);
								$categoria_duracion_minutos = round($categoria_duracion_minutos % 60);
								$html3 = str_replace('%duracion%', sprintf('%02d', $categoria_duracion_horas) . ':' . sprintf('%02d', $categoria_duracion_minutos), $html3);


								if (Conf::GetConf($this->sesion, 'NoImprimirValorTrabajo') && $this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION') {
									$html3 = str_replace('%valor%', '', $html3);
									$html3 = str_replace('%valor_cyc%', '', $html3);
								} else {
									$html3 = str_replace('%valor%', $moneda->fields['simbolo'] . $this->espacio . number_format($categoria_valor, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
									$html3 = str_replace('%valor_cyc%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . $this->espacio . number_format($categoria_valor * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
								}

								$total_trabajos_categoria .= $html3;

								$html3 = $parser->tags['TRABAJOS_ENCABEZADO'];
								$html3 = str_replace('%duracion%', __('Duraci�n'), $html3);
								$html3 = str_replace('%cobrable%', __('<td align="center" width="80">Cobrable</td>'), $html3);
								$html3 = str_replace('%fecha%', __('Fecha'), $html3);
								$html3 = str_replace('%descripcion%', __('Descripci�n'), $html3);
								$html3 = str_replace('%profesional%', __('Profesional'), $html3);
								$html3 = str_replace('%abogado%', __('Abogado'), $html3);
								$html3 = str_replace('%categoria_abogado%', __($trabajo_siguiente->fields['nombre_usuario']), $html3);
								$html3 = str_replace('%tarifa%', $moneda->fields['simbolo'] . $this->espacio . number_format($trabajo_siguiente->fields['tarifa_hh'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ' / hr.', $html3);

								if (Conf::GetConf($this->sesion, 'NoImprimirValorTrabajo') && $this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION') {
									$html3 = str_replace('%valor%', '', $html3);
									$html3 = str_replace('%valor_cyc%', '', $html3);
								} else {
									$html3 = str_replace('%valor%', __('Valor'), $html3);
									$html3 = str_replace('%valor_cyc%', __('Valor'), $html3);
								}

								$encabezado_trabajos_categoria .= $html3;

								$row = str_replace('%TRABAJOS_CATEGORIA%', $total_trabajos_categoria . $encabezado_trabajos_categoria, $row);

								$categoria_duracion_horas = 0;
								$categoria_duracion_minutos = 0;
								$categoria_valor = 0;
								$total_trabajos_categoria = '';
								$encabezado_trabajos_categoria = '';
							} else {
								$row = str_replace('%TRABAJOS_CATEGORIA%', '', $row);
							}
						} else {
							$html3 = $parser->tags['TRABAJOS_TOTAL'];
							$html3 = str_replace('%glosa%', __('Total'), $html3);
							$categoria_duracion_horas += floor($categoria_duracion_minutos / 60);
							$categoria_duracion_minutos = round($categoria_duracion_minutos % 60);
							$html3 = str_replace('%duracion%', sprintf('%02d', $categoria_duracion_horas) . ':' . sprintf('%02d', $categoria_duracion_minutos), $html3);
							if ($this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION' && Conf::GetConf($this->sesion, 'NoImprimirValorTrabajo')) {
								$html3 = str_replace('%valor%', '', $html3);
								$html3 = str_replace('%valor_cyc%', '', $html3);
							} else {
								$html3 = str_replace('%valor%', $moneda->fields['simbolo'] . $this->espacio . number_format($categoria_valor, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
								$html3 = str_replace('%valor_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($categoria_valor * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
							}

							$total_trabajos_categoria .= $html3;
							$row = str_replace('%TRABAJOS_CATEGORIA%', $total_trabajos_categoria, $row);
							$categoria_duracion_horas = 0;
							$categoria_duracion_minutos = 0;
							$categoria_valor = 0;
							$total_trabajos_categoria = '';
							$encabezado_trabajos_categoria = '';
						}
					}

					$html .= $row;
				}
				break;

			case 'TRABAJOS_TOTAL': //GenerarDocumento
				if ($this->fields['estado'] == 'CREADO' || $this->fields['estado'] == 'EN REVISION') {
					$html = str_replace('%td_id_trabajo%', '<td align="center">%ntrabajo%</td>', $html);
				} else {
					$html = str_replace('%td_id_trabajo%', '', $html);
				}
				$html = str_replace('%ntrabajo%', __('&nbsp;'), $html);

				$ImprimirDuracionTrabajada = Conf::GetConf($this->sesion, 'ImprimirDuracionTrabajada');

				$horas_cobrables = floor(($asunto->fields['trabajos_total_duracion']) / 60);
				$minutos_cobrables = sprintf("%02d", $asunto->fields['trabajos_total_duracion'] % 60);
				$duracion_retainer_total = ($asunto->fields['trabajos_total_duracion_retainer']) / 60;
				$minutos_decimal = $minutos_cobrables / 60;
				$duracion_decimal = $horas_cobrables + $minutos_decimal;

				$horas_trabajado = floor(($asunto->fields['trabajos_total_duracion_trabajada']) / 60);
				$minutos_trabajado = sprintf("%02d", $asunto->fields['trabajos_total_duracion_trabajada'] % 60);
				$minutos_decimal_trabajada = $minutos_trabajado / 60;
				$duracion_decimal_trabajada = $horas_trabajado + $minutos_decimal_trabajada;

				$horas_retainer = floor(($asunto->fields['trabajos_total_duracion_retainer']) / 60);
				$minutos_retainer = sprintf("%02d", $asunto->fields['trabajos_total_duracion_retainer'] % 60);

				if (($minutos_trabajado - $minutos_cobrables) < 0) {
					$horas_descontadas = $horas_trabajado - $horas_cobrables - 1;
					$minutos_descontadas = $minutos_trabajado - $minutos_cobrables + 60;
				} else {
					$horas_descontadas = $horas_trabajado - $horas_cobrables;
					$minutos_descontadas = $minutos_trabajado - $minutos_cobrables;
				}

				$minutos_decimal_descontadas = $minutos_descontadas / 60;
				$duracion_decimal_descontada = $horas_descontadas + $minutos_decimal_descontadas;

				if ($this->fields['opc_ver_solicitante']) {
					$html = str_replace('%td_solicitante%', '<td>&nbsp;</td>', $html);
				} else {
					$html = str_replace('%td_solicitante%', '', $html);
				}
				if ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') {
					$html = str_replace('%td_retainer%', '<td align="center">%duracion_retainer%</td>', $html);
					$html = str_replace('%duracion_retainer%', Utiles::Decimal2GlosaHora($duracion_retainer_total), $html);
				} else {
					$html = str_replace('%td_retainer%', '', $html);
				}

				if ($this->fields['forma_cobro'] == 'FLAT FEE') {
					$html = str_replace('%duracion_decimal_trabajada%', '', $html);
					$html = str_replace('%duracion_trabajada%', '', $html);
					$html = str_replace('%duracion_descontada%', '', $html);
					$html = str_replace('%duracion_decimal_descontada%', '', $html);
					if ($this->fields['opc_ver_horas_trabajadas']) {
						$html = str_replace('%duracion_decimal%', number_format($duracion_decimal_trabajada, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
						$html = str_replace('%duracion%', $horas_trabajado . ':' . $minutos_trabajado, $html);
					} else {
						$html = str_replace('%duracion_decimal%', number_format($duracion_decimal, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
						$html = str_replace('%duracion%', $horas_cobrables . ':' . $minutos_cobrables, $html);
					}
				}
				if ($ImprimirDuracionTrabajada && ( $this->fields['estado'] == 'CREADO' || $this->fields['estado'] == 'EN REVISION' )) {

					$html = str_replace('%duracion_decimal_trabajada%', number_format($duracion_decimal_trabajada, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					$html = str_replace('%duracion_trabajada%', $horas_trabajado . ':' . $minutos_trabajado, $html);
					if ($descontado) {
						$html = str_replace('%duracion_decimal_descontada%', number_format($duracion_decimal_descontada, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
						$html = str_replace('%duracion_descontada%', $horas_descontadas . ':' . sprintf("%02", $minutos_descontadas), $html);
					} else {
						$html = str_replace('%duracion_decimal_descontada%', '', $html);
						$html = str_replace('%duracion_descontada%', '', $html);
					}
				} else if ($this->fields['opc_ver_horas_trabajadas']) {
					$html = str_replace('%duracion_trabajada%', $horas_trabajado . ':' . $minutos_trabajado, $html);
					$html = str_replace('%duracion_decimal_trabajada%', number_format($duracion_decimal_trabajada, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					$html = str_replace('%duracion_descontada%', $horas_descontadas . ':' . sprintf("%02d", $minutos_descontadas), $html);
					$html = str_replace('%duracion_decimal_descontada%', number_format($duraoion_decimal_descontada, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				} else {
					$html = str_replace('%duracion_decimal_trabajada%', '', $html);
					$html = str_replace('%duracion_trabajada%', '', $html);
					$html = str_replace('%duracion_descontada%', '', $html);
					$html = str_replace('%duracion_decimal_descontada%', '', $html);
				}

				$html = str_replace('%glosa%', __('Total Trabajos'), $html);
				$html = str_replace('%duracion_decimal%', number_format($duracion_decimal, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%duracion%', $horas_cobrables . ':' . $minutos_cobrables, $html);

				if ($this->fields['opc_ver_columna_cobrable'] == 1)
					$html = str_replace('%cobrable%', __('<td>&nbsp;</td>'), $html);
				else
					$html = str_replace('%cobrable%', __(''), $html);

				$ImprimirValorTrabajo = Conf::GetConf($this->sesion, 'ImprimirValorTrabajo');

				$aproximacion_monto_cyc = number_format($this->fields['monto_subtotal'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
				$subtotal_en_moneda_cyc = $aproximacion_monto_cyc * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']);

				if ($ImprimirValorTrabajo && $this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION') {
					$html = str_replace('%valor%', '', $html);
					$html = str_replace('%valor_cyc%', '', $html);
				} else {
					$html = str_replace('%valor_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($subtotal_en_moneda_cyc, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					$html = str_replace('%valor%', $moneda->fields['simbolo'] . $this->espacio . number_format($asunto->fields['trabajos_total_valor'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				}

				if ($this->fields['opc_ver_detalles_por_hora_importe'] == 1) {
					$html = str_replace('%td_importe%', '<td width="80" align="center">%valor_siempre%</td>', $html);
					$html = str_replace('%td_importe_ajustado%', '<td width="80" align="center">%valor_siempre%</td>', $html);
				} else {
					$html = str_replace('%td_importe%', '', $html);
					$html = str_replace('%td_importe_ajustado%', '', $html);
				}

				$html = str_replace('%valor_siempre%', $moneda->fields['simbolo'] . $this->espacio . number_format($asunto->fields['trabajos_total_valor'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				if ($this->fields['opc_ver_detalles_por_hora_categoria'] == 1) {
					$html = str_replace('%td_categoria%', '<td>&nbsp;</td>', $html);
				} else {
					$html = str_replace('%td_categoria%', '', $html);
				}

				if ($this->fields['opc_ver_detalles_por_hora_tarifa'] == 1) {
					$html = str_replace('%td_tarifa%', '<td>&nbsp;</td>', $html);
					$html = str_replace('%td_tarifa_ajustada%', '<td>&nbsp;</td>', $html);
				} else {
					$html = str_replace('%td_tarifa%', '', $html);
					$html = str_replace('%td_tarifa_ajustada%', '', $html);
				}

				$html = str_replace('%total_raz%', __('total_raz'), $html);

				break;

			case 'DETALLE_PROFESIONAL': //GenerarDocumento

				if ($this->fields['opc_ver_profesional'] == 0)
					return '';
				$html = str_replace('%glosa_profesional%', __('Detalle profesional'), $html);
				$html = str_replace('%detalle_tiempo_por_abogado%', __('Detalle tiempo por abogado'), $html);
				$html = str_replace('%detalle_honorarios%', __('Detalle de honorarios profesionales'), $html);
				$html = str_replace('%PROFESIONAL_ENCABEZADO%', $this->GenerarSeccionResumenProfesional($parser, 'PROFESIONAL_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%PROFESIONAL_FILAS%', $this->GenerarDocumento($parser, 'PROFESIONAL_FILAS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%PROFESIONAL_TOTAL%', $this->GenerarDocumento($parser, 'PROFESIONAL_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);

				if ($this->fields['opc_ver_descuento']) {
					$html = str_replace('%DETALLE_COBRO_DESCUENTO%', $this->GenerarDocumento($parser, 'DETALLE_COBRO_DESCUENTO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				} else {
					$html = str_replace('%DETALLE_COBRO_DESCUENTO%', '', $html);
				}

				if (count($this->asuntos) > 1) {
					$html = str_replace('%DETALLE_COBRO_MONEDA_TOTAL_POR_ASUNTO%', $this->GenerarDocumentoComun($parser, 'DETALLE_COBRO_MONEDA_TOTAL_POR_ASUNTO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
					$html = str_replace('%DETALLE_COBRO_MONEDA_TOTAL%', '', $html);
				} else {
					$html = str_replace('%DETALLE_COBRO_MONEDA_TOTAL%', $this->GenerarDocumentoComun($parser, 'DETALLE_COBRO_MONEDA_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
					$html = str_replace('%DETALLE_COBRO_MONEDA_TOTAL_POR_ASUNTO%', '', $html);
				}
				break;

			case 'IMPUESTO': //GenerarDocumento
				$html = str_replace('%impuesto%', __('Impuesto') . ' (' . $this->fields['porcentaje_impuesto'] . '%)', $html);
				$html = str_replace('%impuesto_mta%', __('Impuesto') . ' (' . $this->fields['porcentaje_impuesto'] . '%)', $html);

				if ($this->fields['tipo_cambio_moneda_base'] <= 0) {
					$tipo_cambio_cobro_moneda_base = 1;
				} else {
					$tipo_cambio_cobro_moneda_base = $this->fields['tipo_cambio_moneda_base'];
				}

				$aproximacion_impuesto = number_format($this->fields['impuesto'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
				$impuesto_moneda_total = $aproximacion_impuesto * ($this->fields['tipo_cambio_moneda'] / $tipo_cambio_cobro_moneda_base) / ($tipo_cambio_moneda_total / $tipo_cambio_cobro_moneda_base) + $this->fields['impuesto_gastos'];

				$html = str_replace('%valor_impuesto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($impuesto_moneda_total, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				$impuesto_solo_honorarios = $x_resultados['monto_iva_hh'][$this->fields['opc_moneda_total']];

				$html = str_replace('%valor_impuesto_honorarios%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($impuesto_solo_honorarios, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

			case 'PROFESIONAL_FILAS': //GenerarDocumento
				$row_tmpl = $html;
				$html = '';
				if (is_array($profesionales)) {
					$retainer = false;
					$descontado = false;
					$flatfee = false;

					// Para mostrar un resumen de horas de cada profesional al principio del documento.
					global $resumen_profesional_nombre;
					global $resumen_profesional_hrs_trabajadas;
					global $resumen_profesional_hrs_retainer;
					global $resumen_profesional_hrs_descontadas;
					global $resumen_profesional_hh;
					global $resumen_profesional_valor_hh;
					global $resumen_profesional_categoria;
					global $resumen_profesional_id_categoria;
					global $resumen_profesionales;

					foreach ($profesionales as $prof => $data) {
						if ($data['retainer'] > 0)
							$retainer = true;
						if ($data['descontado'] > 0)
							$descontado = true;
						if ($data['flatfee'] > 0)
							$flatfee = true;
					}

					// Si el conf lo indica, ordenamos los profesionales por categor�a.
					if (Conf::GetConf($this->sesion, 'OrdenarPorTarifa')) {
						foreach ($profesionales as $prof => $data) {
							$tarifa_profesional[$prof] = $data['tarifa'];
						}
						if (sizeof($tarifa_profesional) > 0)
							array_multisort($tarifa_profesional, SORT_DESC, $profesionales);
					} else if (Conf::GetConf($this->sesion, 'OrdenarPorFechaCategoria')) {
						foreach ($profesionales as $prof => $data) {
							$categoria[$prof] = $data['id_categoria_usuario'];
						}
						if (sizeof($categoria) > 0)
							array_multisort($categoria, SORT_ASC, $profesionales);
					}
					foreach ($profesionales as $prof => $data) {
						// Para mostrar un resumen de horas de cada profesional al principio del documento.
						for ($k = 0; $k < count($resumen_profesional_nombre); ++$k)
							if ($resumen_profesional_nombre[$k] == $prof)
								break;
						$totales['valor'] += $data['valor'];
						//se pasan los minutos a horas:minutos
						$horas_trabajadas_real = floor(($data['tiempo_trabajado_real']) / 60);
						$minutos_trabajadas_real = sprintf("%02d", $data['tiempo_trabajado_real'] % 60);
						$horas_trabajadas = floor(($data['tiempo_trabajado']) / 60);
						$minutos_trabajadas = sprintf("%02d", $data['tiempo_trabajado'] % 60);
						$horas_descontado_real = floor(($data['descontado_real']) / 60);
						$minutos_descontado_real = sprintf("%02d", $data['descontado_real'] % 60);
						$horas_descontado = floor(($data['descontado']) / 60);
						$minutos_descontado = sprintf("%02d", $data['descontado'] % 60);
						$horas_retainer = floor(($data['retainer']) / 60);
						$minutos_retainer = sprintf("%02d", $data['retainer'] % 60);
						$segundos_retainer = sprintf("%02d", round(60 * ($data['retainer'] - floor($data['retainer']))));

						$horas_flatfee = floor(($data['flatfee']) / 60);
						$minutos_flatfee = sprintf("%02d", $data['flatfee'] % 60);
						if ($retainer) {
							$totales['tiempo_retainer'] += $data['retainer'];
							$totales['tiempo_trabajado'] += $data['tiempo_trabajado'];
							if ($data['tiempo_trabajado'] > $data['tiempo_trabajado_real'])
								$totales['tiempo_trabajado_real'] += $data['tiempo_trabajado'];
							else
								$totales['tiempo_trabajado_real'] += $data['tiempo_trabajado_real'];

							$totales['tiempo'] += $data['tiempo'] - $data['retainer'];
							$horas_cobrables = floor(($data['tiempo']) / 60) - $horas_retainer;
							$minutos_cobrables = sprintf("%02d", ($data['tiempo'] % 60) - $minutos_retainer);
							if ($this->fields['forma_cobro'] == 'PROPORCIONAL') {
								$segundos_cobrables = sprintf("%02d", 60 - $segundos_retainer);
								--$minutos_cobrables;
							}
							if ($minutos_cobrables < 0) {
								--$horas_cobrables;
								$minutos_cobrables += 60;
							}
						} else {
							$totales['tiempo'] += $data['tiempo'];
							$totales['tiempo_trabajado'] += $data['tiempo_trabajado'];
							if ($data['tiempo_trabajado'] > $data['tiempo_trabajado_real'])
								$totales['tiempo_trabajado_real'] += $data['tiempo_trabajado'];
							else
								$totales['tiempo_trabajado_real'] += $data['tiempo_trabajado_real'];
							$horas_cobrables = floor(($data['tiempo']) / 60);
							$minutos_cobrables = sprintf("%02d", $data['tiempo'] % 60);
						}
						if ($flatfee) {
							$totales['tiempo_flatfee'] += $data['flatfee'];
						}
						if ($descontado || $this->fields['opc_ver_horas_trabajadas']) {
							$totales['tiempo_descontado'] += $data['descontado'];
							if ($data['descontado_real'] >= 0)
								$totales['tiempo_descontado_real'] += $data['descontado_real'];
						}
						$row = $row_tmpl;
						$row = str_replace('%nombre%', $prof, $row);

						if (!$asunto->fields['cobrable']) {
							$row = str_replace('%hrs_retainer%', '', $row);
							$row = str_replace('%hrs_descontadas%', '', $row);
							$row = str_replace('%hrs_descontadas_real%', '', $row);
							$row = str_replace('%hh%', '', $row);
							$row = str_replace('%valor_hh%', '', $row);
							$row = str_replace('%valor_hh_cyc%', '', $row);
						}
						if ($this->fields['opc_ver_detalles_por_hora_tarifa'] == 1) {
							$row = str_replace('%td_tarifa%', '<td>&nbsp;</td>', $row);
							$row = str_replace('%td_tarifa_ajustada%', '<td>&nbsp;</td>', $row);
						} else {
							$row = str_replace('%td_tarifa%', '', $row);
							$row = str_replace('%td_tarifa_ajustada%', '', $row);
						}
						//muestra las iniciales de los profesionales
						list($nombre, $apellido_paterno, $extra) = explode(' ', $prof, 3);
						$row = str_replace('%iniciales%', $nombre[0] . $apellido_paterno[0] . $extra[0], $row);

						$row = str_replace('%username%', $data['username'], $row);


						if ($descontado || $retainer || $flatfee) {
							if ($this->fields['opc_ver_horas_trabajadas']) {
								if ($horas_descontado_real < 0 || substr($minutos_descontado_real, 0, 1) == '-') {
									$row = str_replace('%hrs_trabajadas_real%', $horas_cobrables . ':' . $minutos_cobrables, $row);
									$row = str_replace('%hrs_descontadas_real%', '0:00', $row);
								} else {
									$row = str_replace('%hrs_trabajadas_real%', $horas_trabajadas_real . ':' . $minutos_trabajadas_real, $row);
									$row = str_replace('%hrs_descontadas_real%', $horas_descontado_real . ':' . $minutos_descontado_real, $row);
								}
							} else {
								$row = str_replace('%hrs_trabajadas_real%', '', $row);
								$row = str_replace('%hrs_descontadas_real%', '', $row);
							}
							$row = str_replace('%hrs_trabajadas%', $horas_trabajadas . ':' . $minutos_trabajadas, $row);
							//$resumen_profesional_hrs_trabajadas[$k] += $horas_trabajadas + $minutos_trabajadas/60;
						} else if ($this->fields['opc_ver_horas_trabajadas']) {
							if ($horas_descontado_real < 0 || substr($minutos_descontado_real, 0, 1) == '-') {
								$row = str_replace('%hrs_trabajadas_real%', $horas_cobrables . ':' . $minutos_cobrables, $row);
								$row = str_replace('%hrs_descontadas_real%', '0:00', $row);
							} else {
								$row = str_replace('%hrs_trabajadas_real%', $horas_trabajadas_real . ':' . $minutos_trabajadas_real, $row);
								$row = str_replace('%hrs_descontadas_real%', $horas_descontado_real . ':' . $minutos_descontado_real, $row);
							}
							$row = str_replace('%hrs_trabajadas%', $horas_trabajadas . ':' . $minutos_trabajadas, $row);
						} else {
							$row = str_replace('%hrs_trabajadas%', '', $row);
							$row = str_replace('%hrs_trabajadas_real%', '', $row);
						}
						if ($retainer) {
							if ($data['retainer'] > 0) {
								if ($this->fields['forma_cobro'] == 'PROPORCIONAL') {
									$minutos_retainer_redondeados = sprintf("%02d", $minutos_retainer + round($segundos_retainer / 60));
									$row = str_replace('%hrs_retainer%', $horas_retainer . ':' . $minutos_retainer_redondeados, $row);
									$resumen_profesional_hrs_retainer[$k] += $horas_retainer + $minutos_retainer / 60 + $segundos_retainer / 3600;
								} else { // retainer simple, no imprime segundos
									$row = str_replace('%hrs_retainer%', $horas_retainer . ':' . $minutos_retainer, $row);
									$resumen_profesional_hrs_retainer[$k] += $horas_retainer + $minutos_retainer / 60;
								}
								$minutos_retainer_decimal = $minutos_retainer / 60;
								$duracion_retainer_decimal = $horas_retainer + $minutos_retainer_decimal;
								$row = str_replace('%horas_retainer%', number_format($duracion_retainer_decimal, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
							} else {
								$row = str_replace('%hrs_retainer%', '-', $row);
								$row = str_replace('%horas_retainer%', '', $row);
							}
						} else {
							if ($flatfee) {
								if ($data['flatfee'] > 0) {
									$row = str_replace('%hrs_retainer%', $horas_flatfee . ':' . $minutos_flatfee, $row);
									$resumen_profesional_hrs_retainer[$k] += $horas_flatfee + $minutos_flatfee / 60;
								} else
									$row = str_replace('%hrs_retainer%', '', $row);
							}
							$row = str_replace('%hrs_retainer%', '', $row);
							$row = str_replace('%horas_retainer%', '', $row);
						}
						if ($descontado) {
							$row = str_replace('%columna_horas_no_cobrables%', '<td align="center" width="65">%hrs_descontado%</td>', $row);
							if ($data['descontado'] > 0) {
								$row = str_replace('%hrs_descontadas%', $horas_descontado . ':' . $minutos_descontado, $row);
								$resumen_profesional_hrs_descontadas[$k] += $horas_descontado + $minutos_descontado / 60;
							} else
								$row = str_replace('%hrs_descontadas%', '-', $row);
							if ($data['descontado_real'] > 0) {
								$row = str_replace('%hrs_descontadas_real%', $horas_descontado_real . ':' . $minutos_descontado_real, $row);
							} else
								$row = str_replace('hrs_descontadas_real%', '-', $row);
						}
						else {
							$row = str_replace('%columna_horas_no_cobrables%', '', $row);
							$row = str_replace('%hrs_descontadas_real%', '', $row);
							$row = str_replace('%hrs_descontadas%', '', $row);
						}
						if ($flatfee) {
							$row = str_replace('%hh%', '0:00', $row);
						} else {
							if ($this->fields['forma_cobro'] == 'PROPORCIONAL') {
								$minutos_cobrables_redondeados = sprintf("%02d", $minutos_cobrables + round($segundos_cobrables / 60));
								$row = str_replace('%hh%', "$horas_cobrables:$minutos_cobrables_redondeados", $row);
							} else // Otras formas de cobro, no imprime segundos
								$row = str_replace('%hh%', $horas_cobrables . ':' . sprintf("%02d", $minutos_cobrables), $row);
						}

						$row = str_replace('%valor_hh%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . $this->espacio . number_format($data['tarifa'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						$row = str_replace('%valor_hh_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($data['tarifa'] * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						$row = str_replace('%total%', $moneda->fields['simbolo'] . $this->espacio . number_format($data['valor'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						$row = str_replace('%total_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($data['valor'] * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						$row = str_replace('%hrs_trabajadas_previo%', '', $row);
						$row = str_replace('%horas_trabajadas_especial%', '', $row);
						$row = str_replace('%horas_cobrables%', '', $row);

						#horas en decimal
						$minutos_decimal = $minutos_cobrables / 60;
						$duracion_decimal = $horas_cobrables + $minutos_decimal;
						$row = str_replace('%horas%', number_format($duracion_decimal, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

						if ($this->fields['opc_ver_profesional_tarifa'] == 1) {
							$row = str_replace('%tarifa_horas%', $flatfee ? '' : number_format($data['tarifa'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						} else {
							$row = str_replace('%tarifa_horas%', '', $row);
						}

						$row = str_replace('%total_horas%', $flatfee ? '' : number_format($data['valor'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

						if ($this->fields['opc_ver_horas_trabajadas'] && $horas_trabajadas_real . ':' . $minutos_trabajadas != '0:00') {
							$html .= $row;
						} else if ($horas_trabajadas . ':' . $minutos_trabajadas != '0:00') {
							$html .= $row;
						}

						$resumen_profesional_hh[$k] += $horas_cobrables + $minutos_cobrables / 60;

						if ($segundos_cobrables) { // Se usan solo para el cobro prorrateado.
							$resumen_profesional_hh[$k] += $segundos_cobrables / 3600;
						}
						if ($flatfee) {
							$resumen_profesional_hh[$k] = 0;
						}
					}
				}
				break;

			case 'PROFESIONAL_TOTAL': //GenerarDocumento
				$retainer = false;
				$descontado = false;
				$flatfee = false;
				if (is_array($profesionales)) {
					foreach ($profesionales as $prof => $data) {
						if ($data['retainer'] > 0)
							$retainer = true;
						if ($data['descontado'] > 0)
							$descontado = true;
						if ($data['flatfee'] > 0)
							$flatfee = true;
					}
				}

				if (!$asunto->fields['cobrable']) {
					$html = str_replace('%hrs_retainer%', '', $html);
					$html = str_replace('%hrs_descontadas%', '', $html);
					$html = str_replace('%hrs_descontadas_real%', '', $html);
					$html = str_replace('%hh%', '', $html);
					$html = str_replace('%valor_hh%', '', $html);
					$html = str_replace('%valor_hh_cyc%', '', $html);
				}

				$horas_cobrables = floor(($totales['tiempo']) / 60);
				$minutos_cobrables = sprintf("%02d", $totales['tiempo'] % 60);
				$segundos_cobrables = round(60 * ($totales['tiempo'] - floor($totales['tiempo'])));
				$horas_trabajadas = floor(($totales['tiempo_trabajado']) / 60);
				$minutos_trabajadas = sprintf("%02d", $totales['tiempo_trabajado'] % 60);
				$horas_trabajadas_real = floor(($totales['tiempo_trabajado_real']) / 60);
				$minutos_trabajadas_real = sprintf("%02d", $totales['tiempo_trabajado_real'] % 60);
				$horas_retainer = floor(($totales['tiempo_retainer']) / 60);
				$minutos_retainer = sprintf("%02d", $totales['tiempo_retainer'] % 60);
				$segundos_retainer = sprintf("%02d", round(60 * ($totales['tiempo_retainer'] - floor($totales['tiempo_retainer']))));
				$horas_flatfee = floor(($totales['tiempo_flatfee']) / 60);
				$minutos_flatfee = sprintf("%02d", $totales['tiempo_flatfee'] % 60);
				$horas_descontado = floor(($totales['tiempo_descontado']) / 60);
				$minutos_descontado = sprintf("%02d", $totales['tiempo_descontado'] % 60);
				$horas_descontado_real = floor(($totales['tiempo_descontado_real']) / 60);
				$minutos_descontado_real = sprintf("%02d", $totales['tiempo_descontado_real'] % 60);
				$html = str_replace('%glosa%', __('Total'), $html);
				$html = str_replace('%glosa_honorarios%', __('Total Honorarios'), $html);

				if ($descontado || $retainer || $flatfee) {
					if ($this->fields['opc_ver_horas_trabajadas']) {
						$html = str_replace('%hrs_trabajadas_real%', $horas_trabajadas_real . ':' . $minutos_trabajadas_real, $html);
						$html = str_replace('%hrs_descontadas_real%', $horas_descontado_real . ':' . $minutos_descontado_real, $html);
					} else {
						$html = str_replace('%hrs_trabajadas_real%', '', $html);
						$html = str_replace('%hrs_descontadas_real%', '', $html);
					}
					$html = str_replace('%hrs_trabajadas%', $horas_trabajadas . ':' . $minutos_trabajadas, $html);
				} else if ($this->fields['opc_ver_horas_trabajadas']) {
					$html = str_replace('%hrs_trabajadas%', $horas_trabajadas . ':' . $minutos_trabajadas, $html);
					$html = str_replace('%hrs_trabajadas_real%', $horas_trabajadas_real . ':' . $minutos_trabajadas_real, $html);
					$html = str_replace('%hrs_descontadas_real%', $horas_descontado_real . ':' . $minutos_descontado_real, $html);
				} else {
					$html = str_replace('%hrs_trabajadas%', '', $html);
					$html = str_replace('%hrs_trabajadas_real%', '', $html);
				}


				$html = str_replace('%hrs_trabajadas_previo%', '', $html);
				$html = str_replace('%horas_trabajadas_especial%', '', $html);
				$html = str_replace('%horas_cobrables%', '', $html);
				//$html = str_replace('%horas_cobrables%',$horas_trabajadas.':'.$minutos_trabajadas,$html);

				if ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL')
					$html = str_replace('%DETALLE_PROFESIONAL_RETAINER%', $this->GenerarDocumento($parser, 'DETALLE_PROFESIONAL_RETAINER', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				else
					$html = str_replace('%DETALLE_PROFESIONAL_RETAINER%', '', $html);

				if ($retainer) {
					if ($this->fields['forma_cobro'] == 'PROPORCIONAL') {
						$minutos_retainer_redondeados = sprintf("%02d", $minutos_retainer + round($segundos_retainer / 60));
						$html = str_replace('%hrs_retainer%', $horas_retainer . ':' . $minutos_retainer_redondeados, $html);
					} else // retainer simple, no imprime segundos
						$html = str_replace('%hrs_retainer%', $horas_retainer . ':' . $minutos_retainer, $html);
					$minutos_retainer_decimal = $minutos_retainer / 60;
					$duracion_retainer_decimal = $horas_retainer + $minutos_retainer_decimal;
					$html = str_replace('%horas_retainer%', number_format($duracion_retainer_decimal, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				}
				else {
					$html = str_replace('%horas_retainer%', '', $html);
					if ($flatfee)
						$html = str_replace('%hrs_retainer%', $horas_flatfee . ':' . $minutos_flatfee, $html);
					else
						$html = str_replace('%hrs_retainer%', '', $html);
				}
				if ($descontado) {
					$html = str_replace('%columna_horas_no_cobrables%', '<td align="center" width="65">%hrs_descontadas%</td>', $html);
					$html = str_replace('%hrs_descontadas_real%', $horas_descontadas_real . ':' . $minutos_descontadas_real, $html);
					$html = str_replace('%hrs_descontadas%', $horas_descontado . ':' . $minutos_descontado, $html);
				} else {
					$html = str_replace('%columna_horas_no_cobrables%', '', $html);
					$html = str_replace('%hrs_descontadas_real%', '', $html);
					$html = str_replace('%hrs_descontadas%', '', $html);
				}
				if ($flatfee)
					$html = str_replace('%hh%', '0:00', $html);
				else
				if ($this->fields['forma_cobro'] == 'PROPORCIONAL') {
					$minutos_cobrables_redondeados = sprintf("%02d", $minutos_cobrables + round($segundos_cobrables / 60));
					$html = str_replace('%hh%', "$horas_cobrables:$minutos_cobrables_redondeados", $html);
				} else // retainer simple, no imprime segundos
					$html = str_replace('%hh%', $horas_cobrables . ':' . sprintf("%02d", $minutos_cobrables), $html);

				$aproximacion_monto_cyc = number_format($this->fields['monto_subtotal'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
				$subtotal_en_moneda_cyc = $aproximacion_monto_cyc * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']);

				$html = str_replace('%total%', $moneda->fields['simbolo'] . $this->espacio . number_format($totales['valor'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%total_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($subtotal_en_moneda_cyc, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				$minutos_decimal = $minutos_cobrables / 60;
				$duracion_decimal = $horas_cobrables + $minutos_decimal;

				$html = str_replace('%total_honorarios%', $flatfee ? $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto_subtotal'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) : $moneda->fields['simbolo'] . $this->espacio . number_format($totales['valor'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%horas%', number_format($duracion_decimal, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%horas_mb%', number_format($duracion_decimal, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				break;

			case 'DETALLE_PROFESIONAL_RETAINER': //GenerarDocumento
				$html = str_replace('%retainer%', __('Retainer'), $html);
				$html = str_replace('%valor_retainer%', $cobro_moneda->moneda[$this->fields['id_moneda_monto']]['simbolo'] . $this->espacio . number_format($this->fields['monto_contrato'], $cobro_moneda->moneda[$this->fields['id_moneda_monto']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				break;

			/*
			  GASTOS -> esto s?lo lista los gastos agregados al cobro obteniendo un total
			 */
			case 'GASTOS': //GenerarDocumento
				if ($this->fields['opc_ver_gastos'] == 0)
					return '';

				$html = str_replace('%glosa_gastos%', __('Gastos'), $html);
				$html = str_replace('%expenses%', __('%expenses%'), $html); //en vez de Disbursements es Expenses en ingl?s
				$html = str_replace('%detalle_gastos%', __('Detalle de gastos'), $html);
				$html = str_replace('%detalle_gastos_raz%', __('detalledegastos'), $html);
				if ($lang == 'es') {
					$html = str_replace('%glosa_gasto%', __('GASTOS'), $html);
				} else {
					$html = str_replace('%glosa_gasto%', __('EXPENSES'), $html);
				}
				$html = str_replace('%GASTOS_ENCABEZADO%', $this->GenerarDocumento($parser, 'GASTOS_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%GASTOS_FILAS%', $this->GenerarDocumento($parser, 'GASTOS_FILAS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%GASTOS_TOTAL%', $this->GenerarDocumento($parser, 'GASTOS_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				break;

			case 'GASTOS_ENCABEZADO': //GenerarDocumento
				$html = str_replace('%glosa_gastos%', __('Gastos'), $html);
				$html = str_replace('%descripcion_gastos%', __('Descripci�n de Gastos'), $html);
				$html = str_replace('%fecha%', __('Fecha'), $html);
				$html = str_replace('%num_doc%', __('N? Documento'), $html);
				$html = str_replace('%tipo_gasto%', __('Tipo'), $html);
				$html = str_replace('%descripcion%', __('Descripci�n'), $html);
				$html = str_replace('%monto_original%', __('Monto'), $html);
				$html = str_replace('%ordenado_por%', __('Ordenado<br>Por'), $html);
				$html = str_replace('%monto_moneda_total%', __('Monto') . ' (' . $moneda_total->fields['simbolo'] . ')', $html);

				if ($lang == 'es') {
					$html = str_replace('%asunto_id%', __('ID<br>Asunto'), $html);
				} else {
					$html = str_replace('%asunto_id%', __('Matter<br>ID'), $html);
				}

				$html = str_replace('%monto_impuesto_total%', '', $html);
				$html = str_replace('%monto_moneda_total_con_impuesto%', '', $html);

				if ($this->fields['opc_ver_solicitante']) {
					$html = str_replace('%solicitante%', __('Ordenado<br>Por'), $html);
				} else {
					$html = str_replace('%solicitante%', '', $html);
				}

				if (Conf::GetConf($this->sesion, 'MostrarProveedorenGastos')) {
					$html = str_replace('%proveedor%', __('Proveedor'), $html);
				} else {
					$html = str_replace('%proveedor%', '', $html);
				}
				break;

			case 'GASTOS_FILAS': //GenerarDocumento
				$row_tmpl = $html;
				$html = '';
				if (method_exists('Conf', 'SepararGastosPorAsunto') && Conf::SepararGastosPorAsunto()) {
					$where_gastos_asunto = " AND codigo_asunto='" . $asunto->fields['codigo_asunto'] . "'";
				} else {
					$where_gastos_asunto = "";
				}
				$query = "SELECT SQL_CALC_FOUND_ROWS *, prm_cta_corriente_tipo.glosa AS tipo_gasto
								FROM cta_corriente
								LEFT JOIN prm_cta_corriente_tipo ON cta_corriente.id_cta_corriente_tipo=prm_cta_corriente_tipo.id_cta_corriente_tipo
								WHERE id_cobro='" . $this->fields['id_cobro'] . "'
									AND monto_cobrable > 0
									AND cta_corriente.incluir_en_cobro = 'SI'
									AND cta_corriente.cobrable = 1
								$where_gastos_asunto
								ORDER BY fecha ASC";

				$lista_gastos = new ListaGastos($this->sesion, '', $query);
				$totales['total'] = 0;
				$totales['total_moneda_cobro'] = 0;
				if ($lista_gastos->num == 0) {
					$row = $row_tmpl;
					$row = str_replace('%fecha%', '&nbsp;', $row);
					$row = str_replace('%descripcion%', __('No hay gastos en este cobro'), $row);
					$row = str_replace('%descripcion_b%', '(' . __('No hay gastos en este cobro') . ')', $row);
					$row = str_replace('%monto_original%', '&nbsp;', $row);
					$row = str_replace('%monto%', '&nbsp;', $row);
					$row = str_replace('%monto_moneda_total%', '&nbsp;', $row);
					$row = str_replace('%monto_moneda_total_sin_simbolo%', '&nbsp;', $row);
					$html .= $row;
				}

				for ($i = 0; $i < $lista_gastos->num; $i++) {
					$gasto = $lista_gastos->Get($i);

					//Cargar cobro_moneda

					$cobro_moneda = new CobroMoneda($this->sesion);
					$cobro_moneda->Load($this->fields['id_cobro']);

					if ($gasto->fields['egreso'] > 0)
						$saldo = $gasto->fields['monto_cobrable'];
					elseif ($gasto->fields['ingreso'] > 0)
						$saldo = -$gasto->fields['monto_cobrable'];

					$monto_gasto = $saldo;
					$saldo_moneda_total = $saldo * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']);
					if (Conf::GetConf($this->sesion, 'CalculacionCyC'))
						$saldo_moneda_total = number_format($saldo_moneda_total, $cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['cifras_decimales'], ".", "");

					$totales['total'] += $saldo_moneda_total;
					$totales['total_moneda_cobro'] += $saldo;

					$row = $row_tmpl;
					$row = str_replace('%valor_codigo_asunto%', $gasto->fields['codigo_asunto'], $row);
					$row = str_replace('%fecha%', Utiles::sql2fecha($gasto->fields['fecha'], $idioma->fields['formato_fecha']), $row);
					$row = str_replace('%num_doc%', $gasto->fields['numero_documento'], $row);
					$row = str_replace('%tipo_gasto%', $gasto->fields['tipo_gasto'], $row);

					if (substr($gasto->fields['descripcion'], 0, 41) == 'Saldo aprovisionado restante tras Cobro #') {
						$row = str_replace('%descripcion%', __('Saldo aprovisionado restante tras Cobro #') . substr($gasto->fields['descripcion'], 42), $row);
						$row = str_replace('%descripcion_b%', __('Saldo aprovisionado restante tras Cobro #') . substr($gasto->fields['descripcion'], 42), $row);
					} else {
						$row = str_replace('%descripcion%', __($gasto->fields['descripcion']), $row);
						$row = str_replace('%descripcion_b%', __($gasto->fields['descripcion']), $row); #Ojo, este no deber?a existir
					}

					$row = str_replace('%monto_original%', $cobro_moneda->moneda[$gasto->fields['id_moneda']]['simbolo'] . $this->espacio . number_format($monto_gasto, $cobro_moneda->moneda[$gasto->fields['id_moneda']]['cifras_decimales'], $cobro_moneda->moneda[$gasto->fields['id_moneda']]['separador_decimales'], $cobro_moneda->moneda[$gasto->fields['id_moneda']]['separador_miles']), $row);
					$row = str_replace('%monto_moneda_total%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($saldo_moneda_total, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

					$row = str_replace('%monto_moneda_total_sin_simbolo%', number_format($saldo_moneda_total, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					$html .= $row;
				}

				$html = str_replace('%monto_impuesto_total%', '', $html);
				$html = str_replace('%monto_moneda_total_con_impuesto%', '', $html);

				$html = str_replace('%proveedor%', '', $html);

				$html = str_replace('%solicitante%', '', $html);

				break;

			case 'GASTOS_TOTAL': //GenerarDocumento
				$html = str_replace('%total%', __('Total'), $html);
				$html = str_replace('%glosa_total%', __('Total Gastos'), $html);
				if ($lang == 'es') {
					$html = str_replace('%sub_total_gastos%', __('Sub total gastos'), $html);
				} else {
					$html = str_replace('%sub_total_gastos%', __('Sub total for expenses'), $html);
				}
				$cobro_moneda = new CobroMoneda($this->sesion);
				$cobro_moneda->Load($this->fields['id_cobro']);

				$id_moneda_base = Moneda::GetMonedaBase($this->sesion);

				#$html = str_replace('%valor_total%', ''/*$cobro_moneda->fields['simbolo'].' '.number_format($totales['total_moneda_cobro'],$cobro_moneda->fields['cifras_decimales']*/,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
				if ($this->fields['id_moneda_base'] <= 0)
					$tipo_cambio_cobro_moneda_base = 1;
				else
					$tipo_cambio_cobro_moneda_base = $cobro_moneda->moneda[$id_moneda_base]['tipo_cambio'];

				#$gastos_moneda_total = ($totales['total']*($this->fields['tipo_cambio_moneda']/$this->fields['tipo_cambio_moneda_base']))/$this->fields['opc_moneda_total_tipo_cambio'];
				#$gastos_moneda_total = ($totales['total']*($this->fields['tipo_cambio_moneda']/$tipo_cambio_cobro_moneda_base))/$tipo_cambio_moneda_total;
				# Comentado por ICC $gastos_moneda_total = $totales['total']*$moneda->fields['tipo_cambio']/$tipo_cambio_moneda_total;
				$gastos_moneda_total = $totales['total'];

				$html = str_replace('%total_gastos_moneda_total%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($totales['total'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				if ($moneda_total->fields['id_moneda'] != $id_moneda_base) {
					$html = str_replace('%glosa_total_moneda_base%', __('Total Moneda Base'), $html);
					$gastos_moneda_total_contrato = ( $gastos_moneda_total * ( $cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio'])) / $tipo_cambio_cobro_moneda_base;
					$html = str_replace('%valor_total_moneda_carta%', $cobro_moneda->moneda[$id_moneda_base]['simbolo'] . $this->espacio . number_format($gastos_moneda_total_contrato, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					$html = str_replace('%valor_total_monedabase%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($totales['total'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				} else {
					$html = str_replace('%glosa_total_moneda_base%', '&nbsp;', $html);
					$html = str_replace('%valor_total_moneda_base%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($totales['total'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					$html = str_replace('%valor_total_moneda_carta%', '&nbsp;', $html);
					$html = str_replace('%valor_total_monedabase%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($totales['total'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				}

				$contr = new Contrato($this->sesion);
				$contr->Load($this->fields['id_contrato']);

				$gastos_moneda_total_contrato = ( $totales['total'] * ( $cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio'])) / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'];

				$html = str_replace('%valor_total_moneda_carta%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($gastos_moneda_total_contrato, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				$html = str_replace('%valor_impuesto_monedabase%', '', $html);
				$html = str_replace('%valor_total_monedabase_con_impuesto%', '', $html);
				break;

			case 'TIPO_CAMBIO': //GenerarDocumento
				if ($this->fields['opc_ver_tipo_cambio'] == 0) {
					return '';
				}
				//Tipos de Cambio
				$html = str_replace('%titulo_tipo_cambio%', __('Tipos de Cambio'), $html);
				foreach ($cobro_moneda->moneda as $id => $moneda) {
					$html = str_replace("%glosa_moneda_id_$id%", __($moneda['glosa_moneda']), $html);
					$html = str_replace("%simbolo_moneda_id_$id%", $moneda['simbolo'], $html);
					$html = str_replace("%valor_moneda_id_$id%", number_format($moneda['tipo_cambio'], 2, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				}
				break;

			/*
			  CTA_CORRIENTE -> nuevo tag para la representaci�n de la cuenta corriente (gastos, provisiones)
			  aparecer� como Saldo Inicial; Movimientos del periodo; Saldo Periodo; Saldo Final
			 */
			case 'CTA_CORRIENTE': //GenerarDocumento
				if ($this->fields['opc_ver_gastos'] == 0)
					return '';

				$html = str_replace('%titulo_detalle_cuenta%', __('Saldo de Gastos Adeudados'), $html);
				$html = str_replace('%descripcion_cuenta%', __('Descripci�n'), $html);
				$html = str_replace('%monto_cuenta%', __('Monto'), $html);

				$html = str_replace('%CTA_CORRIENTE_SALDO_INICIAL%', $this->GenerarDocumentoComun($parser, 'CTA_CORRIENTE_SALDO_INICIAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%CTA_CORRIENTE_MOVIMIENTOS_ENCABEZADO%', $this->GenerarDocumentoComun($parser, 'CTA_CORRIENTE_MOVIMIENTOS_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%CTA_CORRIENTE_MOVIMIENTOS_FILAS%', $this->GenerarDocumentoComun($parser, 'CTA_CORRIENTE_MOVIMIENTOS_FILAS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%CTA_CORRIENTE_MOVIMIENTOS_TOTAL%', $this->GenerarDocumentoComun($parser, 'CTA_CORRIENTE_MOVIMIENTOS_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%CTA_CORRIENTE_SALDO_FINAL%', $this->GenerarDocumentoComun($parser, 'CTA_CORRIENTE_SALDO_FINAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				break;
		}
		return $html;
	}

	function GenerarDocumento2($parser, $theTag = 'INFORME', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, &$idioma, & $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto, $mostrar_asuntos_cobrables_sin_horas = FALSE) {

		global $contrato;
		global $cobro_moneda;
		global $masi;
		global $x_detalle_profesional;
		global $x_resumen_profesional;
		global $x_factor_ajuste;
		global $x_resultados;
		global $x_cobro_gastos;

		$moneda_total = new Objeto($this->sesion, '', '', 'prm_moneda', 'id_moneda');
		$moneda_total->Load($this->fields['opc_moneda_total'] > 0 ? $this->fields['opc_moneda_total'] : 1);
		$this->fields['opc_mostrar_asuntos_cobrables_sin_horas'] = $mostrar_asuntos_cobrables_sin_horas ? TRUE : $this->fields['opc_mostrar_asuntos_cobrables_sin_horas'];

		if (!isset($parser->tags[$theTag])) {
			return;
		}

		$this->FillTemplateData($idioma, $moneda);
		$html = $this->RenderTemplate($parser->tags[$theTag]);

		switch ($theTag) {

			case 'INFORME': //GenerarDocumento2
				#INSERTANDO CARTA
				$nuevomodulofactura = Conf::GetConf($this->sesion, 'NuevoModuloFactura');

				if (strpos($html, '%INFORME_GASTOS%') !== false) {
					$this->ArrayFacturasDelContrato = $this->FacturasDelContrato($this->sesion, $nuevomodulofactura, null, 'G');
					$this->ArrayTotalesDelContrato = $this->TotalesDelContrato($this->ArrayFacturasDelContrato, $nuevomodulofactura, $this->fields['id_cobro']);
					$html = str_replace('%INFORME_GASTOS%', '', $html);
				} else if (strpos($html, '%INFORME_HONORARIOS%') !== false) {
					$this->ArrayFacturasDelContrato = $this->FacturasDelContrato($this->sesion, $nuevomodulofactura, null, 'H');
					$this->ArrayTotalesDelContrato = $this->TotalesDelContrato($this->ArrayFacturasDelContrato, $nuevomodulofactura, $this->fields['id_cobro']);
					$html = str_replace('%INFORME_HONORARIOS%', '', $html);
				}

				$html = str_replace('%xfecha_mes_dos_digitos%', date("m", strtotime($this->fields['fecha_emision'])), $html);
				$html = str_replace('%xfecha_ano_dos_digitos%', date("y", strtotime($this->fields['fecha_emision'])), $html);
				$html = str_replace('%xfecha_mes_dia_ano%', date("m-d-Y", strtotime($this->fields['fecha_emision'])), $html);

				$fechacabecera = ($this->fields['fecha_emision'] == 'NULL' || $this->fields['fecha_emision'] == '0000-00-00' || $this->fields['fecha_emision'] == "") ? time() : strtotime($this->fields['fecha_emision']);

				$html = str_replace('%xfecha_mespalabra_dia_ano%', strftime(Utiles::FormatoStrfTime("%B %e, %Y"), $fechacabecera), $html);
				$html = str_replace('%xnro_factura%', $this->fields['id_cobro'], $html);
				$html = str_replace('%xnombre_cliente%', $contrato->fields['factura_razon_social'], $html);
				$html = str_replace('%xglosa_cliente%', $contrato->fields['factura_razon_social'], $html);
				$html = str_replace('%xdireccion%', nl2br($contrato->fields['factura_direccion']), $html);
				$html = str_replace('%xrut%', $contrato->fields['rut'], $html);

				require_once('CartaCobro.php');

				$CartaCobro = new CartaCobro($this->sesion, $this->fields, $this->ArrayFacturasDelContrato, $this->ArrayTotalesDelContrato);

				if (isset($this->DetalleLiquidaciones)) {
					$CartaCobro->DetalleLiquidaciones = $this->DetalleLiquidaciones;
				}

				$textocarta = $CartaCobro->GenerarDocumentoCarta2($parser_carta, 'CARTA', $lang, $moneda_cliente_cambio, $moneda_cli, $idioma, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $cliente, $id_carta);
				$html = str_replace('%COBRO_CARTA%', $textocarta, $html);

				$PdfLinea1 = Conf::GetConf($this->sesion, 'PdfLinea1');
				$PdfLinea2 = Conf::GetConf($this->sesion, 'PdfLinea2');
				$PdfLinea3 = Conf::GetConf($this->sesion, 'PdfLinea3');

				$query = "SELECT count(*) FROM cta_corriente
								 WHERE id_cobro=" . $this->fields['id_cobro'];
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				list($cont_gastos) = mysql_fetch_array($resp);

				$query = "SELECT count(*) FROM trabajo WHERE id_cobro = " . $this->fields['id_cobro'];
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				list($cont_trab) = mysql_fetch_array($resp);

				$query = "SELECT count(*) FROM tramite WHERE id_cobro = " . $this->fields['id_cobro'];
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				list($cont_tram) = mysql_fetch_array($resp);

				$html = str_replace('%cobro%', __('NOTA DE COBRO') . ' # ', $html);
				$html = str_replace('%valor_cobro%', $this->fields['id_cobro'], $html);
				$html = str_replace('%logo%', Conf::LogoDoc(true), $html);
				$html = str_replace('%titulo%', $PdfLinea1, $html);
				$html = str_replace('%logo_cobro%', Conf::Server() . Conf::ImgDir(), $html);
				$html = str_replace('%subtitulo%', $PdfLinea2, $html);
				$html = str_replace('%direccion_blr%', __('%direccion_blr%'), $html);
				$html = str_replace('%glosa_fecha%', __('Fecha') . ':', $html);
				$html = str_replace('%glosa_fecha_mayuscula%', __('FECHA'), $html);
				$html = str_replace('%texto_factura%', __('FACTURA'), $html);
				$html = str_replace('%fecha_gqmc%', ($this->fields['fecha_emision'] == '0000-00-00 00:00:00' or $this->fields['fecha_emision'] == '' or $this->fields['fecha_emision'] == 'NULL') ? ucwords(strftime(Utiles::FormatoStrfTime("%e %B %Y"), time())) : ucwords(strftime(Utiles::FormatoStrfTime("%e %B %Y"), strtotime($this->fields['fecha_emision']))), $html);
				$html = str_replace('%fecha%', ($this->fields['fecha_emision'] == '0000-00-00 00:00:00' or $this->fields['fecha_emision'] == '' or $this->fields['fecha_emision'] == 'NULL') ? Utiles::sql2fecha(date('Y-m-d'), $idioma->fields['formato_fecha']) : Utiles::sql2fecha($this->fields['fecha_emision'], $idioma->fields['formato_fecha']), $html);

				if ($lang == 'es') {
					$fecha_lang = ucfirst(Utiles::sql3fecha(date('Y-m-d'), '%d de %B de %Y'));
				} else {
					$fecha_lang = date('F d, Y');
				}

				$time_fecha_fin = strtotime($this->fields['fecha_fin']);
				$fecha_mes_del_cobro = strftime("%B %Y", mktime(0, 0, 0, date("m", $time_fecha_fin), date("d", $time_fecha_fin) - 5, date("Y", $time_fecha_fin)));

				$cliente = new Cliente($this->sesion);

				if (Conf::GetConf($this->sesion, 'CodigoSecundario')) {
					$codigo_cliente = $cliente->CodigoACodigoSecundario($this->fields['codigo_cliente']);
				} else {
					$codigo_cliente = $this->fields['codigo_cliente'];
				}

				$html = str_replace('%fecha_mes_dos_digitos%', date("m", $time_fecha_fin), $html);
				$html = str_replace('%fecha_ano_dos_digitos%', date("y", $time_fecha_fin), $html);
				$html = str_replace('%fecha_dia_mes_ano%', date("d/m/Y"), $html);
				$html = str_replace('%codigo_cliente%', $codigo_cliente, $html);
				$html = str_replace('%fecha_mes_del_cobro%', ucfirst($fecha_mes_del_cobro), $html);
				$html = str_replace('%fecha_larga%', $fecha_lang, $html);

				$query = "SELECT CONCAT(a.nombre, ' ', a.apellido1, ' ', a.apellido2) FROM usuario AS a JOIN contrato ON a.id_usuario=contrato.id_usuario_responsable JOIN cobro ON cobro.id_contrato=contrato.id_contrato WHERE cobro.id_cobro=" . $this->fields['id_cobro'];
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				list($nombre_encargado) = mysql_fetch_array($resp);

				if ($this->fields['estado'] == 'CREADO' || $this->fields['estado'] == 'EN REVISION') {
					$html = str_replace('%nombre_socio_estado%', $nombre_encargado, $html);
				} else {
					$html = str_replace('%nombre_socio_estado%', '', $html);
				}

				$html = str_replace('%nombre_socio%', $nombre_encargado, $html);
				$html = str_replace('%socio%', __('SOCIO'), $html);
				$html = str_replace('%socio_cobrador%', __('SOCIO COBRADOR'), $html);
				$html = str_replace('%fono%', __('TEL�FONO'), $html);
				$html = str_replace('%fax%', __('TELEFAX'), $html);
				$html = str_replace('%asunto%', __('Asunto'), $html);
				$html = str_replace('%glosa_asunto%', __('Glosa') . ' ' . __('Asunto'), $html);
				$html = str_replace('%codigo_asunto%', __('C�digo') . ' ' . __('Asunto'), $html);
				$html = str_replace('%label_codigo_cliente%', __('C�digo') . ' ' . __('Cliente'), $html);
				$html = str_replace('%nota_cobro_acl%', __('Nota de Cobro ACL'), $html);
				$html = str_replace('%reference_no_acl%', __('reference no acl'), $html);
				$html = str_replace('%servicios%', __('Servicios'), $html);
				$html = str_replace('%honorarios%', __('Honorarios'), $html);
				$html = str_replace('%gastos_acl%', __('Gastos ACL'), $html);
				$html = str_replace('%otros%', __('Otros'), $html);
				$html = str_replace('%subtotales%', __('Subtotal'), $html);
				$html = str_replace('%impuestos%', __('Impuesto'), $html);
				$html = str_replace('%total_deuda%', __('Total Adeudado'), $html);
				$html = str_replace('%instruccion_deposito%', __('Instrucciones Dep�sito'), $html);
				$html = str_replace('%beneficiario_deposito%', __('Titular'), $html);
				$html = str_replace('%banco%', __('Banco'), $html);
				$html = str_replace('%direccion%', __('Direcci�n'), $html);
				$html = str_replace('%cuenta_bancaria%', __('Cuenta'), $html);

				if ($lang == 'es') {
					$query_categoria_lang = "IFNULL( prm_categoria_usuario.glosa_categoria, ' ' ) as categoria_usuario";
				} else {
					$query_categoria_lang = "IFNULL( prm_categoria_usuario.glosa_categoria_lang, ' ' ) as categoria_usuario";
				}

				$query = "SELECT
								CONCAT_WS(' ',usuario.nombre,usuario.apellido1,usuario.apellido2) as nombre_encargado,
								usuario.rut,
								IFNULL(usuario.dv_rut, 'NA'),
								$query_categoria_lang
							FROM usuario
							JOIN contrato ON usuario.id_usuario=contrato.id_usuario_responsable
							JOIN cobro ON contrato.id_contrato=cobro.id_contrato
							LEFT JOIN prm_categoria_usuario ON ( usuario.id_categoria_usuario = prm_categoria_usuario.id_categoria_usuario AND usuario.id_categoria_usuario != 0 )
							WHERE cobro.id_cobro=" . $this->fields['id_cobro'];

				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				list($nombre_encargado, $rut_usuario, $dv_usuario, $categoria_usuario) = mysql_fetch_array($resp);

				$html = str_replace('%encargado_comercial%', $nombre_encargado, $html);

				if (trim($dv_usuario) != 'NA' && strlen(trim($dv_usuario)) != 0) {
					$rut_usuario .= "-" . $dv_usuario;
				}

				$html = str_replace('%rut_encargado%', $rut_usuario, $html);

				$html = str_replace('%CLIENTE%', $this->GenerarSeccionCliente($parser->tags['CLIENTE'], $idioma, $moneda, $asunto), $html);

				if ($this->fields['forma_cobro'] == 'ESCALONADA') {
					$html = str_replace('%DETALLE_COBRO%', "%DETALLE_COBRO%\n\n%TABLA_ESCALONADA%", $html);
				}

				$html = str_replace('%DETALLE_COBRO%', $this->GenerarDocumento2($parser, 'DETALLE_COBRO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%RESUMEN_ASUNTOS%', $this->GenerarDocumento2($parser, 'RESUMEN_ASUNTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);

				if ($this->fields['forma_cobro'] == 'ESCALONADA') {

					$this->CargarEscalonadas();
					$html_tabla = "<br /><span class=\"titulo_seccion\">" . __('Detalle Tarifa Escalonada') . "</span> <table class=\"tabla_normal\" width=\"50%\">%filas_escalas%</table>";
					$html_fila = "";
					for ($i = 1; $i <= self::MAX_ESC; $i++) {
						if ($this->fields['esc' . $i . '_tiempo'] != 0) {
							$detalle_escala = "";
							$detalle_escala .= $this->escalonadas[$i]['tiempo_inicial'] . " - ";
							$detalle_escala .=!empty($this->escalonadas[$i]['tiempo_final']) && $this->escalonadas[$i]['tiempo_final'] != 'NULL' ? $this->escalonadas[$i]['tiempo_final'] . " hrs. " : " " . __('m�s hrs') . " ";
							$detalle_escala .=!empty($this->escalonadas[$i]['id_tarifa']) && $this->escalonadas[$i]['id_tarifa'] != 'NULL' ? " " . __('Tarifa HH') . " " : " " . __('monto fijo') . " ";
							if (!empty($this->fields['esc' . $i . '_descuento']) && $this->fields['esc' . $i . '_descuento'] != 'NULL') {
								$detalle_escala .= " " . __('con descuento') . " {$this->fields['esc' . $i . '_descuento']}% ";
							}
							if (!empty($this->fields['esc' . $i . '_monto']) && $this->fields['esc' . $i . '_monto'] != 'NULL') {
								$query_glosa_moneda = "SELECT simbolo FROM prm_moneda WHERE id_moneda='{$this->escalonadas[$i]['id_moneda']}' LIMIT 1";
								$resp = mysql_query($query_glosa_moneda, $this->sesion->dbh) or Utiles::errorSQL($query_glosa_moneda, __FILE__, __LINE__, $this->sesion->dbh);
								list( $simbolo_moneda ) = mysql_fetch_array($resp);
								$monto_escala = number_format($this->escalonadas[$i]['monto'], $cobro_moneda->moneda[$this->escalonadas[$i]['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
								$detalle_escala .= ": $simbolo_moneda $monto_escala";
							}
							$html_fila .= "	<tr> <td>$detalle_escala</td> </tr>\n";
						}
					}
					$html_tabla = str_replace('%filas_escalas%', $html_fila, $html_tabla);
					$html = str_replace('%TABLA_ESCALONADA%', $html_tabla, $html);
				}

				if ($this->fields['opc_ver_morosidad']) {
					//Tiene adelantos
					$query = "SELECT COUNT(*) AS nro_adelantos
								FROM documento
								LEFT JOIN prm_moneda ON prm_moneda.id_moneda = documento.id_moneda
								WHERE documento.codigo_cliente = '" . $this->fields['codigo_cliente'] . "' AND documento.es_adelanto = 1 AND documento.saldo_pago < 0
								AND (documento.id_contrato = " . $this->fields['id_contrato'] . " OR documento.id_contrato IS NULL)";

					$adelantos = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

					$adelanto = mysql_fetch_assoc($adelantos);

					if ($adelanto['nro_adelantos'] > 0) {
						$html = str_replace('%ADELANTOS%', $this->GenerarDocumento2($parser, 'ADELANTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
					} else {
						$html = str_replace('%ADELANTOS%', '', $html);
					}

					$html = str_replace('%COBROS_ADEUDADOS%', $this->GenerarDocumento2($parser, 'COBROS_ADEUDADOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				} else {

					$html = str_replace('%ADELANTOS%', '', $html);
					$html = str_replace('%COBROS_ADEUDADOS%', '', $html);
				}

				if ($this->fields['forma_cobro'] == 'CAP') {
					$html = str_replace('%RESUMEN_CAP%', $this->GenerarDocumento2($parser, 'RESUMEN_CAP', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				} else {
					$html = str_replace('%RESUMEN_CAP%', '', $html);
				}

				if (Conf::GetConf($this->sesion, 'ParafoAsuntosSoloSiHayTrabajos')) {

					if ($cont_trab || $cont_tram || ( $cont_gastos > 0 && Conf::GetConf($this->sesion, 'SepararGastosPorAsunto') )) {
						$html = str_replace('%ASUNTOS%', $this->GenerarDocumento2($parser, 'ASUNTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
					} else {
						$html = str_replace('%ASUNTOS%', '', $html);
					}
				} else {
					$html = str_replace('%ASUNTOS%', $this->GenerarDocumento2($parser, 'ASUNTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				}

				$html = str_replace('%TRAMITES%', '', $html);

				if (Conf::GetConf($this->sesion, 'ParafoGastosSoloSiHayGastos')) {

					if ($cont_gastos) {
						$html = str_replace('%GASTOS%', $this->GenerarDocumento2($parser, 'GASTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
					} else {
						$html = str_replace('%GASTOS%', '', $html);
					}
				} else {
					$html = str_replace('%GASTOS%', $this->GenerarDocumento2($parser, 'GASTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				}

				$html = str_replace('%CTA_CORRIENTE%', $this->GenerarDocumento2($parser, 'CTA_CORRIENTE', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%TIPO_CAMBIO%', $this->GenerarDocumentoComun($parser, 'TIPO_CAMBIO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%MOROSIDAD%', $this->GenerarDocumentoComun($parser, 'MOROSIDAD', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%GLOSA_ESPECIAL%', $this->GenerarDocumentoComun($parser, 'GLOSA_ESPECIAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%RESUMEN_PROFESIONAL_POR_CATEGORIA%', $this->GenerarSeccionResumenProfesional($parser, 'RESUMEN_PROFESIONAL_POR_CATEGORIA', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);

				if (Conf::GetConf($this->sesion, 'ParafoAsuntosSoloSiHayTrabajos') && ($this->fields['incluye_honorarios'] == 0)) {
					$html = str_replace('%RESUMEN_PROFESIONAL%', '', $html);
				} else {
					$html = str_replace('%RESUMEN_PROFESIONAL%', $this->GenerarSeccionResumenProfesional($parser, 'RESUMEN_PROFESIONAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				}

				$html = str_replace('%ENDOSO%', $this->GenerarDocumentoComun($parser, 'ENDOSO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);

				if ($masi) {
					$html = str_replace('%SALTO_PAGINA%', $this->GenerarDocumentoComun($parser, 'SALTO_PAGINA', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				} else {
					$html = str_replace('%SALTO_PAGINA%', '', $html);
				}

				$html = str_replace('%DESGLOSE_POR_ASUNTO_DETALLE%', $this->GenerarDocumentoComun($parser, 'DESGLOSE_POR_ASUNTO_DETALLE', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%DESGLOSE_POR_ASUNTO_TOTALES%', $this->GenerarDocumentoComun($parser, 'DESGLOSE_POR_ASUNTO_TOTALES', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);

				if (Conf::GetConf($this->sesion, 'NuevoModuloFactura')) {
					$query = "SELECT CAST( GROUP_CONCAT( numero ) AS CHAR ) AS numeros
								FROM factura
								WHERE id_cobro =" . $this->fields['id_cobro'];

					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

					list($numero_factura) = mysql_fetch_array($resp);

					if (!$numero_factura) {
						$numero_factura = '';
					}

					$html = str_replace('%numero_factura%', $numero_factura, $html);
				} else if (Conf::GetConf($this->sesion, 'PermitirFactura')) {
					$html = str_replace('%numero_factura%', $this->fields['documento'], $html);
				} else {
					$html = str_replace('%numero_factura%', $this->fields['documento'], $html);
				}

				if ($this->fields['fecha_emision'] == '0000-00-00 00:00:00' or $this->fields['fecha_emision'] == '' or $this->fields['fecha_emision'] == 'NULL') {
					$html = str_replace('%xcorrelativo_aguilar%', 'N/A', $html);
				} else {
					$html = str_replace('%xcorrelativo_aguilar%', 'DN-' . date("ym", strtotime($this->fields['fecha_emision'])) . '-' . $this->fields['documento'], $html);
				}

				if ($lang == 'es') {
					$html = str_replace('%honorarios_vouga%', __('HONORARIOS'), $html);
				} else {
					$html = str_replace('%honorarios_vouga%', __('FEES'), $html);
				}

				break;

			case 'DETALLE_COBRO': //GenerarDocumento2

				/**
				  * Detalle de tarifa escalonada.
				  */
				$chargingBusiness = new ChargingBusiness($this->sesion);
				$coiningBusiness = new CoiningBusiness($this->sesion);
				$translatingBusiness = new TranslatingBusiness($this->sesion);
				$currency = $coiningBusiness->getCurrency($this->fields['opc_moneda_total']);
				$language = $translatingBusiness->getLanguageByCode($idioma->fields['codigo_idioma']);
				$slidingScales = $chargingBusiness->getSlidingScales($this->fields['id_cobro']);
				$table = $chargingBusiness->getSlidingScalesDetailTable($slidingScales, $currency, $language);
				$workDetailTable = $chargingBusiness->getSlidingScalesWorkDetail($chargingBusiness->getCharge($this->fields['id_cobro']));
				$html = str_replace('%detalle_escalones%', $table, $html);
				$html = str_replace('%detalle_trabajos_escalones%', $workDetailTable, $html);

				if ($this->fields['opc_ver_resumen_cobro'] == 0) {
					return '';
				}

				$imprimir_asuntos = '';
				for ($k = 0; $k < count($this->asuntos); $k++) {
					$asunto = new Asunto($this->sesion);
					$asunto->LoadByCodigo($this->asuntos[$k]);
					$imprimir_asuntos .= $asunto->fields['glosa_asunto'];
					if (($k + 1) < count($this->asuntos)) {
						$imprimir_asuntos .= '<br />';
					}
				}

				$html = str_replace('%glosa_cliente%', $contrato->fields['factura_razon_social'], $html);
				$html = str_replace('%reporte_servicio%', __('Reporte de Servicios'), $html);
				$html = str_replace('%aviso_de_cobro%', 'Aviso de cobro', $html);
				$html = str_replace('%factura_o_nd%', 'Factura o ND', $html);
				$html = str_replace('%fecha_fin_gastos_liq%', 'Gastos liquidados hasta', $html);
				$html = str_replace('%honorario_yo_gastos%', __('honorario_yo_gastos'), $html);
				$html = str_replace('%materia%', __('Materia'), $html);
				$html = str_replace('%glosa_asunto_sin_codigo%', $imprimir_asuntos, $html);
				$html = str_replace('%resumen_cobro%', __('Resumen Nota de Cobro'), $html);
				$html = str_replace('%fecha%', __('Fecha'), $html);
				$html = str_replace('%texto_fecha_emision%', __('Fecha Emisi�n'), $html);
				$html = str_replace('%instrucciones_pago%', __('INSTRUCCIONES DE PAGO'), $html);
				$html = str_replace('%giro_bancario%', __('Giro bancario a'), $html);
				$html = str_replace('%descuento_liquidacion%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['descuento'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%impuesto_liquidacion%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($impuesto_moneda_total, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%subtotal_gastos_honorarios_liquidacion%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($this->fields['monto_subtotal'], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				if (array_key_exists('codigo_contrato', $contrato->fields)) {
					$html = str_replace('%glosa_codigo_contrato%', __('C�digo') . ' ' . __('Contrato'), $html);
					$html = str_replace('%codigo_contrato%', $contrato->fields['codigo_contrato'], $html);
				} else {
					$html = str_replace('%glosa_codigo_contrato%', '', $html);
					$html = str_replace('%codigo_contrato%', '', $html);
				}

				$html = str_replace('%fecha_corte%', __('Fecha de Corte'), $html);

				$html = str_replace('%fecha_emision_glosa%', ($this->fields['fecha_emision'] == '0000-00-00' or $this->fields['fecha_emision'] == '') ? '&nbsp;' : __('Fecha emisi�n'), $html);
				$html = str_replace('%fecha_emision%', ($this->fields['fecha_emision'] == '0000-00-00' or $this->fields['fecha_emision'] == '') ? '&nbsp;' : Utiles::sql2fecha($this->fields['fecha_emision'], $idioma->fields['formato_fecha']), $html);
				$horas_cobrables = floor(($this->fields['total_minutos']) / 60);
				$minutos_cobrables = sprintf("%02d", $this->fields['total_minutos'] % 60);

				$detalle_modalidad = $this->ObtenerDetalleModalidad($this->fields, $cobro_moneda->moneda[$this->fields['id_moneda_monto']], $idioma);
				$detalle_modalidad_lowercase = strtolower($detalle_modalidad);

				if (Conf::GetConf($this->sesion, 'ResumenProfesionalVial')) {
					$html = str_replace('%glosa_cobro%', __('Liquidaci�n de honorarios profesionales %desde% hasta %hasta%'), $html);
				} else {
					$html = str_replace('%glosa_cobro%', __('Detalle Cobro'), $html);
				}

				if ($lang == "en") {
					$html = str_replace('%glosa_cobro_aguilar%', __('Debit Note details'), $html);
				} else {
					$html = str_replace('%glosa_cobro_aguilar%', __('Nota de D�bito'), $html);
				}

				$html = str_replace('%cobro%', __('Cobro') . ' ' . __('N�'), $html);
				$html = str_replace('%texto_cobro_nr%', __('Cobro N�'), $html);
				$html = str_replace('%reference%', __('%reference_no%'), $html);
				$html = str_replace('%valor_cobro%', $this->fields['id_cobro'], $html);
				$html = str_replace('%total_simbolo%', __('Total') . ' (' . $moneda_total->fields['simbolo'] . ')', $html);
				$html = str_replace('%boleta%', empty($this->fields['documento']) ? '' : __('Boleta'), $html);
				$html = str_replace('%encargado%', __('Director proyecto'), $html);

				if (!$contrato->fields['id_usuario_responsable']) {
					$nombre_encargado = '';
				} else {
					$query = "SELECT CONCAT_WS(' ',nombre,apellido1,apellido2) as nombre_encargado
								FROM usuario
								WHERE id_usuario=" . $contrato->fields['id_usuario_responsable'];

					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
					list($nombre_encargado) = mysql_fetch_array($resp);
				}

				$html = str_replace('%encargado_valor%', $nombre_encargado, $html);
				$html = str_replace('%factura%', empty($this->fields['documento']) ? '' : __('Factura'), $html);
				$html = str_replace('%factura_acl%', empty($this->fields['documento']) ? '' : __('Factura ACL'), $html);

				if (empty($this->fields['documento'])) {
					$html = str_replace('%pctje_blr%', '33%', $html);
					$html = str_replace('%FACTURA_NUMERO%', '', $html);
					$html = str_replace('%NUMERO_FACTURA%', '', $html);
				} else {
					$html = str_replace('%pctje_blr%', '25%', $html);
					$html = str_replace('%FACTURA_NUMERO%', $this->GenerarDocumento2($parser, 'FACTURA_NUMERO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
					$html = str_replace('%NUMERO_FACTURA%', $this->GenerarDocumento2($parser, 'NUMERO_FACTURA', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				}

				$html = str_replace('%factura_nro%', empty($this->fields['documento']) ? '' : __('Factura') . ' ' . __('N�'), $html);
				$html = str_replace('%cobro_nro%', __('Carta') . ' ' . __('N�'), $html);
				$html = str_replace('%nro_cobro%', $this->fields['id_cobro'], $html);
				$html = str_replace('%cobro_factura_nro%', empty($this->fields['documento']) ? '' : $this->fields['documento'], $html);
				$html = str_replace('%nro_factura%', empty($this->fields['documento']) ? '' : $this->fields['documento'], $html);

				$facturasRS = $this->ArrayFacturasDelContrato;
				foreach ($facturasRS as $factura => $datos) {
					if ($datos[0]['id_cobro'] != $this->fields['id_cobro']) {
						unset($facturasRS[$factura]);
					}
				}

				$html = str_replace('%lista_facturas%', implode(', ', array_keys($facturasRS)), $html);
				$html = str_replace('%modalidad%', $this->fields['opc_ver_modalidad'] == 1 ? __('Modalidad') : '', $html);
				$html = str_replace('%tipo_honorarios%', $this->fields['opc_ver_modalidad'] == 1 ? __('Tipo de Honorarios') : '', $html);

				if ($this->fields['forma_cobro'] == 'RETAINER' && $contrato->fields['glosa_contrato'] != '') {
					$html = str_replace('%valor_modalidad_tyc%', $this->fields['opc_ver_modalidad'] == 1 ? __($contrato->fields['glosa_contrato']) : '', $html);
				} else {
					$html = str_replace('%valor_modalidad_tyc%', $this->fields['opc_ver_modalidad'] == 1 ? __($this->fields['forma_cobro']) : '', $html);
				}

				$html = str_replace('%valor_modalidad%', $this->fields['opc_ver_modalidad'] == 1 ? __($this->fields['forma_cobro']) : '', $html);

				//el siguiente query extrae la descripcion de forma_cobro de la tabla prm_forma_cobro
				$query = "SELECT descripcion FROM prm_forma_cobro WHERE forma_cobro = '" . $this->fields['forma_cobro'] . "'";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				$row = mysql_fetch_row($resp);
				$descripcion_forma_cobro = $row[0];

				if ($this->fields['forma_cobro'] == 'TASA') {
					$html = str_replace('%valor_modalidad_ucfirst%', $this->fields['opc_ver_modalidad'] == 1 ? __('Tarifa por Hora') : '', $html);
				} else {
					$html = str_replace('%valor_modalidad_ucfirst%', $this->fields['opc_ver_modalidad'] == 1 ? __($descripcion_forma_cobro) : '', $html);
				}

				$html = str_replace('%detalle_modalidad%', $this->fields['opc_ver_modalidad'] == 1 && $this->fields['forma_cobro'] != 'ESCALONADA' ? $detalle_modalidad : '', $html);
				$html = str_replace('%detalle_modalidad_lowercase%', $this->fields['opc_ver_modalidad'] == 1 && $this->fields['forma_cobro'] != 'ESCALONADA' ? $detalle_modalidad_lowercase : '', $html);

				if ($this->fields['forma_cobro'] == 'RETAINER' && $contrato->fields['glosa_contrato'] != '') {
					$html = str_replace('%detalle_modalidad_tyc%', '', $html);
				} else {
					$html = str_replace('%detalle_modalidad_tyc%', $this->fields['opc_ver_modalidad'] == 1 && $this->fields['forma_cobro'] != 'ESCALONADA' ? $detalle_modalidad : '', $html);
				}

				$html = str_replace('%tipo_tarifa%', $this->fields['opc_ver_modalidad'] == 1 ? $detalle_modalidad : '', $html);
				$html = str_replace('%detalle_modalidad_lowercase%', $this->fields['opc_ver_modalidad'] == 1 && $this->fields['forma_cobro'] != 'ESCALONADA' ? $detalle_modalidad_lowercase : '', $html);
				$html = str_replace('%periodo%', (($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') and ( $this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '')) ? '' : __('Periodo'), $html);
				$html = str_replace('%periodo_cobro%', (($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') and ( $this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '')) ? '' : __('Periodo Cobro'), $html);
				$html = str_replace('%valor_periodo_ini%', ($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') ? '' : __('Desde') . ' ' . Utiles::sql2fecha($this->fields['fecha_ini'], $idioma->fields['formato_fecha']), $html);
				$html = str_replace('%valor_periodo_fin%', ($this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '') ? '' : __('hasta') . ' ' . Utiles::sql2fecha($this->fields['fecha_fin'], $idioma->fields['formato_fecha']), $html);
				$html = str_replace('%fecha_ini%', ($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') ? '' : __('Fecha desde'), $html);
				$html = str_replace('%fecha_ini_primer_trabajo%', __('Fecha desde'), $html);

				$html = str_replace('%nota_transferencia%', '<u>' . __('Nota') . '</u>:' . __('Por favor recuerde incluir cualquier tarifa o ') . __('cobro') . __(' por transferencia por parte de vuestro banco con el fin de evitar cargos en las pr�ximas facturas.'), $html);

				$query = "SELECT fecha FROM trabajo WHERE id_cobro='" . $this->fields['id_cobro'] . "' AND visible='1' ORDER BY fecha LIMIT 1";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				//ac� se calcula si hay trabajos o no (porque si no sale como fecha 1969)
				if (mysql_num_rows($resp) > 0) {
					list($fecha_primer_trabajo) = mysql_fetch_array($resp);
				} else {
					$fecha_primer_trabajo = $this->fields['fecha_fin'];
				}

				//Tambi�n se saca la fecha final seg�n el �ltimo trabajo
				$query = "SELECT LAST_DAY(fecha) FROM trabajo WHERE id_cobro='" . $this->fields['id_cobro'] . "' AND visible='1' ORDER BY fecha DESC LIMIT 1";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				//ac� se calcula si hay trabajos o no (porque si no sale como fecha 1969)
				if (mysql_num_rows($resp) > 0) {
					list($fecha_ultimo_trabajo) = mysql_fetch_array($resp);
				} else {
					$fecha_ultimo_trabajo = $this->fields['fecha_fin'];
				}

				$fecha_inicial_primer_trabajo = date('Y-m-01', strtotime($fecha_primer_trabajo));
				$fecha_final_ultimo_trabajo = date('Y-m-d', strtotime($fecha_ultimo_trabajo));

				if (Conf::GetConf($this->sesion, 'ResumenProfesionalVial')) {
					if ($lang == 'en') {
						$html = str_replace('%desde%', date('m/d/y', ($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') ? strtotime($fecha_inicial_primer_trabajo) : strtotime($this->fields['fecha_ini'])), $html);
						$html = str_replace('%hasta%', date('m/d/y', strtotime($this->fields['fecha_fin'])), $html);
					} else {
						$html = str_replace('%desde%', date('d-m-y', ($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') ? strtotime($fecha_inicial_primer_trabajo) : strtotime($this->fields['fecha_ini'])), $html);
						$html = str_replace('%hasta%', date('d-m-y', strtotime($this->fields['fecha_fin'])), $html);
					}
				}

				$html = str_replace('%valor_fecha_ini_primer_trabajo%', Utiles::sql2fecha($fecha_inicial_primer_trabajo, $idioma->fields['formato_fecha']), $html);
				$html = str_replace('%valor_fecha_fin_ultimo_trabajo%', Utiles::sql2fecha($fecha_final_ultimo_trabajo, $idioma->fields['formato_fecha']), $html);
				$html = str_replace('%valor_fecha_ini_o_primer_trabajo%', ($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') ? Utiles::sql2fecha($fecha_primer_trabajo, $idioma->fields['formato_fecha']) : Utiles::sql2fecha($this->fields['fecha_ini'], $idioma->fields['formato_fecha']), $html);
				$html = str_replace('%valor_fecha_ini%', ($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') ? '' : Utiles::sql2fecha($this->fields['fecha_ini'], $idioma->fields['formato_fecha']), $html);
				$html = str_replace('%fecha_fin%', ($this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '') ? '' : __('Fecha hasta'), $html);
				$html = str_replace('%valor_fecha_fin%', ($this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '') ? '' : Utiles::sql2fecha($this->fields['fecha_fin'], $idioma->fields['formato_fecha']), $html);

				$html = str_replace('%horas%', __('Total Horas'), $html);
				$html = str_replace('%total_horas%', __('total_horas'), $html);

				if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
					$duracion_decimal_cobrable = number_format($horas_cobrables + $minutos_cobrables / 60, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', '');
					$html = str_replace('%valor_horas%', $duracion_decimal_cobrable, $html);
				} else {
					$html = str_replace('%valor_horas%', $horas_cobrables . ':' . $minutos_cobrables, $html);
				}

				if ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') {
					$html = str_replace('%DETALLE_COBRO_RETAINER%', $this->GenerarDocumento($parser, 'DETALLE_COBRO_RETAINER', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
					$html = str_replace('%DETALLE_TARIFA_ADICIONAL%', $this->GenerarDocumento($parser, 'DETALLE_TARIFA_ADICIONAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				} else {
					$html = str_replace('%DETALLE_COBRO_RETAINER%', '', $html);
					$html = str_replace('%DETALLE_TARIFA_ADICIONAL%', '', $html);
				}

				if (Conf::GetConf($this->sesion, 'ParafoAsuntosSoloSiHayTrabajos') && ($this->fields['incluye_honorarios'] == 0)) {
					$html = str_replace('%honorarios%', '', $html);
				} else if (Conf::GetConf($this->sesion, 'ResumenProfesionalVial')) {
					$html = str_replace('%honorarios%', __('Honorarios totales'), $html);

					if ($this->fields['opc_restar_retainer']) {
						$html = str_replace('%RESTAR_RETAINER%', $this->GenerarDocumento2($parser, 'RESTAR_RETAINER', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
					} else {
						$html = str_replace('%RESTAR_RETAINER%', '', $html);
					}

					$html = str_replace('%descuento%', __('Otros'), $html);
					$html = str_replace('%saldo%', __('Saldo por pagar'), $html);
					$html = str_replace('%equivalente%', __('Equivalente a'), $html);
				} else {
					$html = str_replace('%honorarios%', __('Honorarios'), $html);
				}

				$html = str_replace('%honorarios_con_lang%', __($this->fields['codigo_idioma'] . '_Honorarios'), $html);

				if ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') {
					$html = str_replace('%honorarios_totales%', __('Honorarios Totales'), $html);
				} else {
					$html = str_replace('%honorarios_totales%', __('Honorarios'), $html);
				}

				$html = str_replace('%honorarios_mta%', __('Honorarios totales'), $html);
				$html = str_replace('%valor_honorarios_totales%', $x_resultados['monto'][$this->fields['id_moneda']], $html);
				//$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo']

				$html = str_replace('%valor_honorarios_totales_moneda_total%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($x_resultados['monto_trabajos'][$this->fields['opc_moneda_total']] + $x_resultados['impuesto'][$this->fields['opc_moneda_total']], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				//$html = str_replace('%valor_honorarios_totales_moneda_total%', $x_resultados['monto'][$this->fields['opc_moneda_total']], $html);

				$html = str_replace('%fees%', __('%fees%'), $html); //en vez de Legal Fee es Legal Fees en ingl�s
				$html = str_replace('%expenses%', __('%expenses%'), $html); //en vez de Disbursements es Expenses en ingl�s
				$html = str_replace('%total_honorarios%', __('Total Honorarios'), $html);

				//variable que se usa para la nota de cobro de vial
				$monto_contrato_id_moneda = UtilesApp::CambiarMoneda($this->fields['monto_contrato'], $cobro_moneda->moneda[$this->fields['id_moneda_monto']]['tipo_cambio'], $cobro_moneda->moneda[$this->fields['id_moneda_monto']]['cifras_decimales'], $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales']);
				//$monto_cobro_menos_monto_contrato_moneda_tarifa = number_format($this->fields['monto']-($this->fields['monto_contrato']*$cobro_moneda->moneda[$this->fields['id_moneda_monto']]['tipo_cambio']/$cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']),$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','');
				$monto_cobro_menos_monto_contrato_moneda_tarifa = number_format($this->fields['monto'] - $monto_contrato_id_moneda, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');

				if (Conf::GetConf($this->sesion, 'ParafoAsuntosSoloSiHayTrabajos') && ($this->fields['incluye_honorarios'] == 0)) {
					$html = str_replace('%valor_honorarios_demo%', '', $html);
				} else {
					if ($this->EsCobrado()) {
						$html = str_replace('%valor_honorarios_demo%', $moneda->fields['simbolo'] . $this->espacio . number_format($x_resultados['monto_trabajos'][$this->fields['id_moneda']] - $x_resultados['descuento_honorarios'][$this->fields['id_moneda']], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					} else {
						$html = str_replace('%valor_honorarios_demo%', $moneda->fields['simbolo'] . $this->espacio . number_format($x_resultados['monto_trabajos'][$this->fields['id_moneda']] - $x_resultados['descuento'][$this->fields['id_moneda']], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					}

					if (( Conf::GetConf($this->sesion, 'ResumenProfesionalVial') ) && ( $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL' ) && $this->fields['opc_restar_retainer']) {
						$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'] . $this->espacio . number_format($monto_cobro_menos_monto_contrato_moneda_tarifa, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					}

					if (Conf::GetConf($this->sesion, 'CalculacionCyC')) {
						$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto_subtotal'] - $this->fields['descuento'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					} else if (Conf::GetConf($this->sesion, 'UsarImpuestoSeparado') && $contrato->fields['usa_impuesto_separado']) {
						$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto_subtotal'] - $this->fields['descuento'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					} else {
						$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					}
				}

				$html = str_replace('%horas_decimales%', __('Horas'), $html);
				$minutos_decimal = $minutos_cobrables / 60;
				$duracion_decimal = $horas_cobrables + $minutos_decimal;
				$html = str_replace('%valor_horas_decimales%', number_format($duracion_decimal, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				$en_pesos = $x_resultados['monto'][$this->fields['id_moneda_base']];
				$total_en_moneda = $x_resultados['monto'][$this->fields['opc_moneda_total']];
				$subtotal_en_moneda_cyc = $x_resultados['monto_subtotal'][$this->fields['opc_moneda_total']];
				$descuento_cyc = $x_resultados['descuento'][$this->fields['opc_moneda_total']];

				if (Conf::GetConf($this->sesion, 'CalculacionCyC')) {
					$impuestos_cyc_approximacion = number_format(($subtotal_en_moneda_cyc - $descuento_cyc) * ($this->fields['porcentaje_impuesto'] / 100), $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
				} else {
					$impuestos_cyc_approximacion = $x_resultados['impuesto'][$this->fields['opc_moneda_total']];
				}

				$impuestos_cyc = $impuestos_cyc_approximacion;

				$html = str_replace('%valor_honorarios_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($subtotal_en_moneda_cyc - $descuento_cyc, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idoma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_honorarios_monedabase%', $this->fields['opc_moneda_total'] == $this->fields['id_moneda'] ? '' : $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($total_en_moneda, $cobro_moneda->moneda[$this->fields['moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_honorarios_monedabase_tyc%', $this->fields['opc_moneda_total'] == $this->fields['id_moneda'] || ( $this->fields['id_moneda'] == 2 && $this->fields['codigo_idioma'] == 'en' ) ? '' : $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($total_en_moneda, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				$html = str_replace('%monedabase%', $this->fields['opc_moneda_total'] == $this->fields['id_moneda'] ? '' : __('Equivalente a'), $html);
				$html = str_replace('%equivalente_a_la_fecha%', $this->fields['opc_moneda_total'] == $this->fields['id_moneda'] ? '' : __('Equivalente a la fecha'), $html);

				#detalle total gastos
				if (Conf::GetConf($this->sesion, 'ParafoGastosSoloSiHayGastos') && ($this->fields['incluye_gastos'] == 0)) {
					$html = str_replace('%gastos%', '', $html);
				} else {
					$html = str_replace('%gastos%', __('Gastos'), $html);
				}

				$total_gastos_moneda = $x_cobro_gastos['gasto_total'];

				if ($this->fields['monto_subtotal'] > 0) {
					$html = str_replace('%DETALLE_HONORARIOS%', $this->GenerarDocumento2($parser, 'DETALLE_HONORARIOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				} else {
					$html = str_replace('%DETALLE_HONORARIOS%', '', $html);
				}

				if ($total_gastos_moneda > 0) {
					$html = str_replace('%DETALLE_GASTOS%', $this->GenerarDocumento2($parser, 'DETALLE_GASTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				} else {
					$html = str_replace('%DETALLE_GASTOS%', '', $html);
				}

				if ($this->fields['monto_tramites'] > 0) {
					$html = str_replace('%DETALLE_TRAMITES%', $this->GenerarDocumento2($parser, 'DETALLE_TRAMITES', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				} else {
					$html = str_replace('%DETALLE_TRAMITES%', '', $html);
				}

				if (Conf::GetConf($this->sesion, 'CalculacionCyC')) {
					$total_gastos_moneda = round($total_gastos_moneda, $moneda_total->fields['cifras_decimales']);
				}

				$impuestos_total_gastos_moneda = round($total_gastos_moneda * ($this->fields['porcentaje_impuesto_gastos'] / 100), $moneda_total->fields['cifras_decimales']);

				if (Conf::GetConf($this->sesion, 'ParafoGastosSoloSiHayGastos') && ($this->fields['incluye_gastos'] == 0)) {
					$html = str_replace('%valor_gastos%', '', $html);
				} else {
					$html = str_replace('%valor_gastos%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($total_gastos_moneda, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				}

				$total_cobro = $total_en_moneda + $total_gastos_moneda;
				$total_cobro_cyc = $subtotal_en_moneda_cyc + $total_gastos_moneda - $descuento_cyc;
				$total_cobro_demo = $x_resultados['monto_total_cobro'][$this->fields['opc_moneda_total']];
				$iva_cyc = $impuestos_total_gastos_moneda + $impuestos_cyc;

				$html = str_replace('%total_cobro%', __('Total Cobro'), $html);
				$html = str_replace('%totalcobro%', __('total_cobro'), $html);
				$html = str_replace('%total_cobro_mta%', __('GRAN TOTAL'), $html);
				$html = str_replace('%total_cobro_cyc%', __('Honorarios y Gastos'), $html);
				$html = str_replace('%total_cyc%', __('Total'), $html);
				$html = str_replace('%iva_cyc%', __('IVA') . '(' . $this->fields['porcentaje_impuesto'] . '%)', $html);
				$html = str_replace('%honorarios_y_gastos%', '(' . __('Honorarios y Gastos') . ')', $html);

				$html = str_replace('%valor_total_cobro_demo%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($total_cobro_demo, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_total_cobro_cyc%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($total_cobro_cyc, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_iva_cyc%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($iva_cyc, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idoma->fields['separador_miles']), $html);
				$html = str_replace('%valor_total_cyc%', $moeda_Total->fields['simbolo'] . $this->espacio . number_format($total_cobro_cyc + $iva_cyc, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_total_cobro%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($total_cobro, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_total_cobro_sin_simbolo%', number_format($total_cobro, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_uf%', __('Valor UF') . ' ' . date('d.m.Y'), $html);

				if ($this->fields['opc_ver_tipo_cambio'] == 0) {
					$html = str_replace('%glosa_tipo_cambio_moneda%', '', $html);
					$html = str_replace('%valor_tipo_cambio_moneda%', '', $html);
				} else {
					$html = str_replace('%glosa_tipo_cambio_moneda%', __('Tipo de Cambio'), $html);
					$html = str_replace('%valor_tipo_cambio_moneda%', $cobro_moneda->moneda[$this->fields['id_moneda_base']]['simbolo'] . $this->espacio . number_format($cobro_moneda->moneda[$moneda->fields['id_moneda']]['tipo_cambio'], 2, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				}

				$html = str_replace('%DETALLE_COBRO_MONEDA_TOTAL%', $this->GenerarDocumentoComun($parser, 'DETALLE_COBRO_MONEDA_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);

				if ($this->fields['opc_ver_descuento']) {
					$html = str_replace('%DETALLE_COBRO_DESCUENTO%', $this->GenerarDocumento2($parser, 'DETALLE_COBRO_DESCUENTO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				} else {
					$html = str_replace('%DETALLE_COBRO_DESCUENTO%', '', $html);
				}

				if ($this->fields['porcentaje_impuesto'] > 0 || $this->fields['porcentaje_impuesto_gastos'] > 0) {
					$html = str_replace('%IMPUESTO%', $this->GenerarDocumento2($parser, 'IMPUESTO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				} else {
					$html = str_replace('%IMPUESTO%', '', $html);
				}

				if (Conf::GetConf($this->sesion, 'ResumenProfesionalVial')) {

					$valor_bruto = $this->fields['monto'];

					if (Conf::GetConf($this->sesion, 'UsarImpuestoSeparado') && $contrato->fields['usa_impuesto_separado']) {
						$valor_bruto -= $this->fields['impuesto'];
					}

					$valor_bruto += $this->fields['descuento'];
					$monto_cobro_menos_monto_contrato_moneda_total = $monto_cobro_menos_monto_contrato_moneda_tarifa * $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'];

					$html = str_replace('%valor_descuento%', '(' . $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['descuento'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ')', $html);
					$html = str_replace('%valor_bruto%', $moneda->fields['simbolo'] . $this->espacio . number_format($valor_bruto, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

					if (( $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL' ) && $this->fields['opc_restar_retainer']) {
						$html = str_replace('%valor_equivalente%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($monto_cobro_menos_monto_contrato_moneda_total, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					} else {
						$html = str_replace('%valor_equivalente%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($total_en_moneda, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					}
				}

				$html = str_replace('%total_subtotal_cobro%', __('Total Cobro'), $html);

				if ($this->fields['id_carta'] == 3) {
					$html = str_replace('%nota_disclaimer%', __('Nota Disclaimer'), $html);
				} else {
					$html = str_replace('%nota_disclaimer%', ' ', $html);
				}

				if ($this->fields['opc_ver_morosidad']) {
					$html = str_replace('%DETALLES_PAGOS%', $this->GenerarSeccionDetallePago($parser->tags['DETALLES_PAGOS'], $idioma), $html);
					$html = str_replace('%DETALLES_PAGOS_CONTRATO%', $this->GenerarSeccionDetallePagoContrato($parser->tags['DETALLES_PAGOS_CONTRATO'], $idioma), $html);
				} else {
					$html = str_replace('%DETALLES_PAGOS%', '', $html);
					$html = str_replace('%DETALLES_PAGOS_CONTRATO%', '', $html);
				}

				$and_saldo_adelantos = '';
				$and_saldo_gastos = '';
				$and_saldo_liquidaciones = '';

				if (Conf::GetConf($this->sesion, "SaldoClientePorAsunto")) {
					$and_saldo_adelantos = "AND d.id_contrato = '" . $asunto->fields['id_contrato'] . "' ";
					$and_saldo_gastos = "AND asunto.id_contrato = '" . $asunto->fields['id_contrato'] . "' ";
					$and_saldo_liquidaciones = "AND cobro.id_contrato = '" . $asunto->fields['id_contrato'] . "' ";
				}

				$query_saldo_adelantos = "SELECT SUM(- 1 * d.saldo_pago * (moneda_documento.tipo_cambio / moneda_base.tipo_cambio)) AS saldo_adelantos
										FROM documento d
									INNER JOIN prm_moneda moneda_documento ON d.id_moneda = moneda_documento.id_moneda
									INNER join prm_moneda moneda_base ON moneda_base.id_moneda = 1
									INNER JOIN cliente ON cliente.codigo_cliente = d.codigo_cliente
									LEFT JOIN contrato ON d.id_contrato = contrato.id_contrato
									LEFT JOIN usuario encargado_comercial ON encargado_comercial.id_usuario = contrato.id_usuario_responsable
										WHERE cliente.activo = 1
											AND (d.id_contrato IS NULL OR contrato.activo = 'SI')
											AND d.es_adelanto = 1
											AND d.saldo_pago < 0
											$and_saldo_adelantos
											AND d.pago_gastos = '1'
											AND d.codigo_cliente = '" . $this->fields['codigo_cliente'] . "' ";

				$resp_saldo_adelantos = mysql_query($query_saldo_adelantos, $this->sesion->dbh) or Utiles::errorSQL($query_saldo_adelantos, __FILE__, __LINE__, $this->sesion->dbh);
				list($monto_saldo_adelantos) = mysql_fetch_array($resp_saldo_adelantos);

				$query_saldo_gastos = "SELECT SUM( - 1 * cta_corriente.egreso)
												FROM cta_corriente
											INNER JOIN cliente ON cliente.codigo_cliente = cta_corriente.codigo_cliente
											INNER JOIN asunto ON asunto.codigo_asunto = cta_corriente.codigo_asunto
											INNER JOIN contrato ON asunto.id_contrato = contrato.id_contrato
											LEFT JOIN cobro ON cta_corriente.id_cobro = cobro.id_cobro
												WHERE cliente.activo = 1
													AND contrato.activo = 'SI'
													AND cta_corriente.cobrable = 1
													AND (cta_corriente.id_cobro IS NULL
													OR cobro.estado IN ('CREADO' , 'EN REVISION'))
													AND cta_corriente.id_neteo_documento IS NULL
													AND cta_corriente.documento_pago IS NULL
													$and_saldo_gastos
													AND cta_corriente.codigo_cliente = '" . $this->fields['codigo_cliente'] . "' ";

				$resp_saldo_gastos = mysql_query($query_saldo_gastos, $this->sesion->dbh) or Utiles::errorSQL($query_saldo_gastos, __FILE__, __LINE__, $this->sesion->dbh);
				list($monto_saldo_gastos) = mysql_fetch_array($resp_saldo_gastos);

				$query_saldo_liquidaciones = "SELECT SUM(- 1 * (d.saldo_honorarios + d.saldo_gastos) * (tipo_cambio_documento.tipo_cambio / tipo_cambio_base.tipo_cambio)) AS saldo_liquidaciones
								FROM documento d
							INNER JOIN cobro ON cobro.id_cobro = d.id_cobro
							INNER JOIN prm_moneda moneda_documento ON d.id_moneda = moneda_documento.id_moneda
							INNER JOIN cobro_moneda tipo_cambio_documento ON tipo_cambio_documento.id_moneda = moneda_documento.id_moneda AND tipo_cambio_documento.id_cobro = cobro.id_cobro
							INNER JOIN prm_moneda moneda_base ON moneda_base.id_moneda = 1
							INNER JOIN cobro_moneda tipo_cambio_base ON tipo_cambio_base.id_moneda = moneda_base.id_moneda AND tipo_cambio_base.id_cobro = cobro.id_cobro
							INNER JOIN contrato ON contrato.id_contrato = cobro.id_contrato
							INNER JOIN cliente ON cliente.codigo_cliente = d.codigo_cliente
								WHERE
									cliente.activo = 1
									AND contrato.activo = 'SI'
									AND d.tipo_doc = 'N'
									AND cobro.estado NOT IN ('CREADO' , 'EN REVISION', 'INCOBRABLE')
									$and_saldo_liquidaciones
									AND cobro.incluye_gastos = '1'
									AND cobro.incluye_honorarios = '0'
									AND d.saldo_gastos > 0
									AND d.codigo_cliente = '" . $this->fields['codigo_cliente'] . "' ";

				$resp_saldo_liquidaciones = mysql_query($query_saldo_liquidaciones, $this->sesion->dbh) or Utiles::errorSQL($query_saldo_liquidaciones, __FILE__, __LINE__, $this->sesion->dbh);
				list($monto_saldo_liquidaciones) = mysql_fetch_array($resp_saldo_liquidaciones);

				$monto_saldo_cliente = $monto_saldo_adelantos + $monto_saldo_gastos + $monto_saldo_liquidaciones;

				$monto_saldo_moneda_impresion = UtilesApp::CambiarMoneda($monto_saldo_cliente, 1, 0, $x_resultados['tipo_cambio_opc_moneda_total'], $x_resultados['cifras_decimales_opc_moneda_total']);

				if ($monto_saldo_cliente < 0) {
					$texto_saldo_favor_o_contra = 'Saldo en contra';
				} else {
					$texto_saldo_favor_o_contra = 'Saldo a favor';
				}

				$monto_saldo_liquidaciones = $moneda_total->fields['simbolo'] . ' ' . number_format($monto_saldo_liquidaciones, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
				$monto_saldo_adelantos = $moneda_total->fields['simbolo'] . ' ' . number_format($monto_saldo_adelantos, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
				$monto_saldo_final = $moneda_total->fields['simbolo'] . ' ' . number_format($monto_saldo_moneda_impresion, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);

				$html = str_replace('%texto_saldo_liquidaciones%', __('Saldo liquidaciones'), $html);
				$html = str_replace('%monto_saldo_liquidaciones%', $monto_saldo_liquidaciones, $html);

				$html = str_replace('%texto_saldo_adelantos%', __('Saldo adelantos'), $html);
				$html = str_replace('%monto_saldo_adelantos%', $monto_saldo_adelantos, $html);

				$html = str_replace('%texto_saldo_favor_o_contra%', $texto_saldo_favor_o_contra, $html);
				$html = str_replace('%monto_saldo_final%', $monto_saldo_final, $html);

				list($total_parte_entera, $total_parte_decimal) = explode('.', $x_resultados['monto_total_cobro'][$this->fields['opc_moneda_total']]);

				if (strlen($total_parte_decimal) == '2') {
					$fix_decimal = '1';
				} else {
					$fix_decimal = '10';
				}

				if ($lang == 'es') {

					$glosa_moneda_plural_lang = $moneda_total->fields['glosa_moneda_plural'];

					if (empty($total_parte_decimal)) {
						$monto_palabra_parte_entera = strtoupper(Numbers_Words::toWords($total_parte_entera, 'es'));
						$monto_total_palabra = $monto_palabra_parte_entera . ' ' . mb_strtoupper($glosa_moneda_plural_lang);
						$monto_total_palabra_cero_cien = $monto_palabra_parte_entera . ' ' . mb_strtoupper($glosa_moneda_plural_lang) . ' CON 00/100 CENTAVOS';
					} else {
						$monto_palabra_parte_entera = strtoupper(Numbers_Words::toWords($total_parte_entera, 'es'));
						$monto_palabra_parte_decimal = strtoupper(Numbers_Words::toWords($total_parte_decimal * $fix_decimal, 'es'));
						$monto_total_palabra = $monto_palabra_parte_entera . ' ' . mb_strtoupper($glosa_moneda_plural_lang, 'UTF-8') . ' CON ' . $monto_palabra_parte_decimal . ' ' . 'CENTAVOS';
						$monto_total_palabra_cero_cien = $monto_palabra_parte_entera . ' ' . mb_strtoupper($glosa_moneda_plural_lang) . ' CON ' . $total_parte_decimal * $fix_decimal . '/100 CENTAVOS';
					}
				} else {

					$glosa_moneda_plural_lang = $moneda_total->fields['glosa_moneda_plural_lang'];

					if (empty($total_parte_decimal)) {
						$monto_palabra_parte_entera = strtoupper(Numbers_Words::toWords($total_parte_entera, 'en_US'));
						$monto_total_palabra = $monto_palabra_parte_entera . ' ' . mb_strtoupper($glosa_moneda_plural_lang);
						$monto_total_palabra_cero_cien = $monto_palabra_parte_entera . ' ' . mb_strtoupper($glosa_moneda_plural_lang) . ' CON 00/100 CENTAVOS';
					} else {
						$monto_palabra_parte_entera = strtoupper(Numbers_Words::toWords($total_parte_entera, 'en_US'));
						$monto_palabra_parte_decimal = strtoupper(Numbers_Words::toWords($total_parte_decimal, 'en_US'));
						$monto_total_palabra = $monto_palabra_parte_entera . ' ' . mb_strtoupper($glosa_moneda_plural_lang, 'UTF-8') . ' WITH ' . $monto_palabra_parte_decimal . ' ' . 'CENTS';
						$monto_total_palabra_cero_cien = $monto_palabra_parte_entera . ' ' . mb_strtoupper($glosa_moneda_plural_lang, 'UTF-8') . ' WITH ' . $total_parte_decimal * $fix_decimal . '/100 CENTS';
					}
				}

				$html = str_replace('%monto_total_palabra_cero_cien%', $monto_total_palabra_cero_cien, $html);
				$html = str_replace('%monto_total_palabra%', $monto_total_palabra, $html);

				break;

			case 'RESUMEN_ASUNTOS':
				$html = str_replace('%resumen_asuntos%', __('Resumen Asuntos'), $html);
				$html = str_replace('%RESUMEN_ASUNTOS_ENCABEZADO%', $this->GenerarDocumento2($parser, 'RESUMEN_ASUNTOS_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%RESUMEN_ASUNTOS_FILAS%', $this->GenerarDocumento2($parser, 'RESUMEN_ASUNTOS_FILAS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%RESUMEN_ASUNTOS_FILAS_FLAT_FEE%', $this->GenerarDocumento2($parser, 'RESUMEN_ASUNTOS_FILAS_FLAT_FEE', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%RESUMEN_ASUNTOS_TOTAL%', $this->GenerarDocumento2($parser, 'RESUMEN_ASUNTOS_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				break;

			case 'ADELANTOS': //GenerarDocumento2
				$html = str_replace('%titulo_adelantos%', __('Adelantos por asignar'), $html);
				$html = str_replace('%ADELANTOS_ENCABEZADO%', $this->GenerarDocumento2($parser, 'ADELANTOS_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%ADELANTOS_FILAS_TOTAL%', $this->GenerarDocumento2($parser, 'ADELANTOS_FILAS_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				break;

			case 'COBROS_ADEUDADOS': //GenerarDocumento2
				$html = str_replace('%titulo_adelantos%', __('Saldo anterior'), $html);
				$html = str_replace('%ADELANTOS_ENCABEZADO%', $this->GenerarDocumento2($parser, 'ADELANTOS_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%COBROS_ADEUDADOS_FILAS_TOTAL%', $this->GenerarDocumento2($parser, 'COBROS_ADEUDADOS_FILAS_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				break;

			case 'HITOS_ENCABEZADO': //GenerarDocumento2
				global $total_hitos, $estehito, $cantidad_hitos, $moneda_hitos, $tipo_cambio_hitos;
				$html = str_replace('%fecha%', __('Fecha'), $html);
				$html = str_replace('%descripcion%', __('Descripci�n'), $html);
				$html = str_replace('%valor%', __('Valor') . ' ' . $moneda_hitos, $html);

				break;

			case 'HITOS_FILAS': //GenerarDocumento2
				global $total_hitos, $estehito, $cantidad_hitos, $moneda_hitos, $tipo_cambio_hitos;
				$query_hitos = "select * from (select  (select count(*) total from cobro_pendiente cp2 where cp2.id_contrato=cp.id_contrato) total,  @a:=@a+1 as rowid, round(if(cbr.id_cobro=cp.id_cobro, @a,0),0) as thisid,  ifnull(cp.fecha_cobro,0) as fecha_cobro, cp.descripcion, cp.monto_estimado, pm.simbolo, pm.codigo, pm.tipo_cambio  FROM `cobro_pendiente` cp join  contrato c using (id_contrato) join prm_moneda pm using (id_moneda) join cobro cbr using(id_contrato)  join (select @a:=0) FFF
					where cp.hito=1 and cbr.id_cobro=" . $this->fields['id_cobro'] . ") hitos where hitos.thisid!=0 ";


				$resp_hitos = mysql_query($query_hitos, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				$row_tmpl = $html;
				$html = '';
				while ($hitos = mysql_fetch_array($resp_hitos)) {
					$row = $row_tmpl;
					$row = str_replace('%fecha%', ($hitos['fecha_cobro'] == 0 ? '' : date('d-m-Y', strtotime($hitos['fecha_cobro']))), $row);
					$row = str_replace('%descripcion%', $hitos['descripcion'], $row);
					$total_hitos = $total_hitos + $hitos['monto_estimado'];
					$moneda_hitos = $hitos['simbolo'];
					$estehito = $hitos['thisid'];
					$cantidad_hitos = $hitos['total'];
					$tipo_cambio_hitos = $hitos['tipo_cambio'];
					$row = str_replace('%valor_hitos%', $hitos['monto_estimado'] . ' ' . $moneda_hitos, $row);
					$html .= $row;
				}

				break;

			case 'HITOS_TOTAL': //GenerarDocumento2
				global $total_hitos, $estehito, $cantidad_hitos, $moneda_hitos, $tipo_cambio_hitos;

				$html = str_replace('%total%', __('Total'), $html);
				$html = str_replace('%total_hitos%', $total_hitos . ' ' . $moneda_hitos, $html);

				break;

			case 'ADELANTOS_ENCABEZADO': //GenerarDocumento2
				$html = str_replace('%fecha%', __('Fecha'), $html);
				$html = str_replace('%descripcion%', __('Descripci�n'), $html);
				$html = str_replace('%monto%', __('Monto'), $html);
				$html = str_replace('%saldo%', __('Saldo'), $html);
				break;

			case 'ADELANTOS_FILAS_TOTAL': //GenerarDocumento2
				$saldo = 0;
				$monto_total = 0;
				$moneda = $cobro_moneda->moneda[$this->fields['opc_moneda_total']];

				//Adelantos
				$query = "
				SELECT documento.id_documento, documento.fecha, documento.glosa_documento, IF(documento.saldo_pago = 0, 0, documento.saldo_pago*-1) AS saldo_pago, IF(documento.monto = 0, 0, documento.monto*-1) AS monto, prm_moneda.tipo_cambio
				FROM documento
				LEFT JOIN prm_moneda ON prm_moneda.id_moneda = documento.id_moneda
				WHERE documento.codigo_cliente = '" . $this->fields['codigo_cliente'] . "' AND documento.es_adelanto = 1 AND documento.saldo_pago < 0
				AND (documento.id_contrato = " . $this->fields['id_contrato'] . " OR documento.id_contrato IS NULL)";
				$adelantos = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				while ($adelanto = mysql_fetch_assoc($adelantos)) {
					$fila_adelanto_ = str_replace('%descripcion%', $adelanto['glosa_documento'], $html);

					$monto_saldo = $adelanto['saldo_pago'] * $adelanto['tipo_cambio'] / $moneda['tipo_cambio'];
					$monto_saldo_simbolo = $moneda['simbolo'] . $this->espacio . number_format($monto_saldo, $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
					$fila_adelanto_ = str_replace('%saldo_pago%', $monto_saldo_simbolo, $fila_adelanto_);

					$monto = $adelanto['monto'] * $adelanto['tipo_cambio'] / $moneda['tipo_cambio'];
					$monto_simbolo = $moneda['simbolo'] . $this->espacio . number_format($monto, $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
					$fila_adelanto_ = str_replace('%monto%', $monto_simbolo, $fila_adelanto_);

					$fila_adelanto_ = str_replace('%fecha%', date("d-m-Y", strtotime($adelanto['fecha'])), $fila_adelanto_);

					$saldo += (float) $monto_saldo;
					$monto_total += (float) $monto;
					$fila_adelantos .= $fila_adelanto_;
				}

				$fila_adelantos .= '<tr class="tr_total">
					<td align="right" colspan="2">' . __('Saldo a favor de cliente') . '</td>
					<td align="right">' . $moneda['simbolo'] . $this->espacio . number_format($monto_total, $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . '</td>
					<td align="right">' . $moneda['simbolo'] . $this->espacio . number_format($saldo, $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . '</td>
					</tr>';

				$html = $fila_adelantos;
				break;

			case 'COBROS_ADEUDADOS_FILAS_TOTAL': //GenerarDocumento2
				$saldo = 0;
				$monto_total = 0;
				$moneda = $cobro_moneda->moneda[$this->fields['opc_moneda_total']];

				//Deuda
				$query = "
				SELECT documento.glosa_documento, documento.fecha, documento.monto * cm1.tipo_cambio / cm2.tipo_cambio AS monto, ( documento.saldo_honorarios + documento.saldo_gastos ) * cm1.tipo_cambio / cm2.tipo_cambio AS saldo_cobro
				FROM documento
				LEFT JOIN cobro ON cobro.id_cobro = documento.id_cobro
				LEFT JOIN cobro_moneda as cm1 ON cm1.id_cobro = documento.id_cobro AND cm1.id_moneda = documento.id_moneda
				LEFT JOIN cobro_moneda as cm2 ON cm2.id_cobro = '" . $this->fields['id_cobro'] . "' AND cm2.id_moneda = '" . $this->fields['opc_moneda_total'] . "'
				WHERE documento.codigo_cliente = '" . $this->fields['codigo_cliente'] . "'
				AND documento.es_adelanto <> 1 AND documento.tipo_doc = 'N'
				AND (documento.saldo_honorarios + documento.saldo_gastos) > 0
				AND documento.id_cobro <> " . $this->fields['id_cobro'] . "
				AND cobro.estado NOT IN ('PAGADO', 'INCOBRABLE', 'CREADO', 'EN REVISION')";

				$adelantos = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				while ($adelanto = mysql_fetch_assoc($adelantos)) {
					$fila_adelanto_ = str_replace('%descripcion%', $adelanto['glosa_documento'], $html);

					$monto_saldo_simbolo = $moneda['simbolo'] . $this->espacio . number_format($adelanto['saldo_cobro'], $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
					$fila_adelanto_ = str_replace('%saldo_pago%', $monto_saldo_simbolo, $fila_adelanto_);

					$monto_simbolo = $moneda['simbolo'] . $this->espacio . number_format($adelanto['monto'], $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
					$fila_adelanto_ = str_replace('%monto%', $monto_simbolo, $fila_adelanto_);

					$fila_adelanto_ = str_replace('%fecha%', date("d-m-Y", strtotime($adelanto['fecha'])), $fila_adelanto_);
					$saldo += (float) $adelanto['saldo_cobro'];
					$monto_total += (float) $adelanto['monto'];
					$fila_adelantos .= $fila_adelanto_;
				}

				if (empty($fila_adelantos)) {
					$fila_adelantos .= '<tr><td colspan="4"><i>' . __('Sin saldo anterior') . '</i></td></tr>';
				} else {
					$fila_adelantos .= '<tr class="tr_total">
				<td align="right" colspan="2">' . __('Saldo anterior') . '</td>
				<td align="right">' . $moneda['simbolo'] . $this->espacio . number_format($monto_total, $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . '</td>
				<td align="right">' . $moneda['simbolo'] . $this->espacio . number_format($saldo, $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . '</td>
				</tr>';
				}

				$html = $fila_adelantos;
				break;

			case 'RESUMEN_ASUNTOS_ENCABEZADO':

				$html = str_replace('%resumenasuntos%', __('resumen_asunto'), $html);
				$html = str_replace('%codigo_asunto%', __('Codigo Asunto'), $html);
				$html = str_replace('%asunto%', __('Asunto'), $html);
				$html = str_replace('%nombre_asunto%', __('Nombre Asunto'), $html);
				$html = str_replace('%glosa_asunto%', __('Descripci�n'), $html);
				$html = str_replace('%horas%', __('Horas'), $html);
				$html = str_replace('%importe%', __('Importe'), $html);
				break;

			case 'RESUMEN_ASUNTOS_FILAS':

				$row_tmpl = $html;
				$html = '';

				for ($k = 0; $k < count($this->asuntos); $k++) {
					$asunto = new Asunto($this->sesion);
					$asunto->LoadByCodigo($this->asuntos[$k]);

					$query = "SELECT
								asunto.codigo_asunto,
								asunto.codigo_asunto_secundario,
								asunto.glosa_asunto,
								SUM(TIME_TO_SEC(duracion_cobrada)) AS duracion_cobrada,
								SUM(monto_cobrado) as importe
							FROM trabajo
							JOIN asunto ON asunto.codigo_asunto=trabajo.codigo_asunto
							WHERE trabajo.codigo_asunto = '" . $asunto->fields['codigo_asunto'] . "'
								AND trabajo.id_cobro = '" . $this->fields['id_cobro'] . "'
								AND trabajo.cobrable = 1
								AND id_tramite=0
								AND duracion_cobrada > 0
								GROUP BY glosa_asunto ASC";

					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
					while (list($codigo_asunto, $codigo_asunto_secundario, $glosa_asunto, $duracion_cobrada, $importe) = mysql_fetch_array($resp)) {
						$row = $row_tmpl;
						$horas = floor($duracion_cobrada / 3600);
						$minutes = (($duracion_cobrada / 60 ) % 60);
						$seconds = ($duracion_cobrada % 60);

						list($solo_codigo_cliente, $solo_codigo_asunto_secundario) = explode("-", $codigo_asunto_secundario);

						$row = str_replace('%solo_codigo_asunto_secundario%', $solo_codigo_asunto_secundario, $row);

						$row = str_replace('%codigo_asunto%', $codigo_asunto, $row);
						$row = str_replace('%codigo_asunto_secundario%', $codigo_asunto_secundario, $row);
						$row = str_replace('%glosa_asunto%', $glosa_asunto, $row);
						$row = str_replace('%horas%', $horas . ':' . sprintf("%02d", $minutes), $row);

						if ($this->fields['forma_cobro'] == 'TASA' || $this->fields['forma_cobro'] == 'CAP' || $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') {
							$row = str_replace('%importe%', number_format($importe, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						}

						if ($this->fields['forma_cobro'] == 'FLAT FEE') {
							$row = str_replace('%importe%', '', $row);
						}

						$html .= $row;
					}
				}

				break;

			case 'RESUMEN_ASUNTOS_FILAS_FLAT_FEE':

				$row_tmpl = $html;
				$html = '';

				//	Asuntos Flat Fee : Lista asuntos asociados al cobro tabla "cobro_asuntos"

				$query = "SELECT asunto.codigo_asunto, asunto.codigo_asunto_secundario, asunto.glosa_asunto FROM cobro_asunto LEFT JOIN asunto ON ( cobro_asunto.codigo_asunto = asunto.codigo_asunto ) WHERE id_cobro ='{$this->fields['id_cobro']}'";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

				while (list($codigo_asunto_secundario_ff, $codigo_asunto_secundario_ff, $glosa_asunto_ff) = mysql_fetch_array($resp)) {
					$row = $row_tmpl;

					$row = str_replace('%codigo_asunto_ff%', $codigo_asunto_secundario_ff, $row);
					$row = str_replace('%codigo_asunto_secundario_ff%', $codigo_asunto_secundario_ff, $row);
					$row = str_replace('%glosa_asunto_ff%', $glosa_asunto_ff, $row);

					$html .= $row;
				}

				break;

			case 'RESUMEN_ESCALONES':

				break;

			case 'RESUMEN_ASUNTOS_TOTAL':

				$query = "
						SELECT SUM(TIME_TO_SEC(duracion_cobrada)) as duracion,SUM(monto_cobrado) as subtotal_sin_impuesto
						FROM trabajo
						JOIN asunto ON asunto.codigo_asunto=trabajo.codigo_asunto
						WHERE trabajo.id_cobro = '" . $this->fields['id_cobro'] . "'
						AND trabajo.cobrable = 1
						AND id_tramite=0";

				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

				while (list($duracion_cobrada, $subtotal_sin_impuesto) = mysql_fetch_array($resp)) {

					$horas = floor($duracion_cobrada / 3600);
					$minutes = (($duracion_cobrada / 60 ) % 60);
					$seconds = ($duracion_cobrada % 60);

					$valor_monto_contrato = $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto_contrato'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);

					$html = str_replace('%impuesto%', __('Impuesto'), $html);
					$html = str_replace('%total%', __('Total'), $html);
					$html = str_replace('%igv%', __('I.G.V.'), $html);
					$html = str_replace('%servicios_prestados%', __('Servicios prestados'), $html);
					$html = str_replace('%fecha_inicial%', __('Fecha desde'), $html);
					$html = str_replace('%fecha_final%', __('Fecha hasta'), $html);

					//	Se saca la fecha inicial seg�n el primer trabajo para evitar que fecha desde sea 1969
					$query = "SELECT fecha FROM trabajo WHERE id_cobro='" . $this->fields['id_cobro'] . "' AND visible='1' ORDER BY fecha LIMIT 1";
					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

					if (mysql_num_rows($resp) > 0) {
						list($fecha_primer_trabajo) = mysql_fetch_array($resp);
					} else {
						$fecha_primer_trabajo = $this->fields['fecha_fin'];
					}

					if ($lang == 'en') {
						$html = str_replace('%desde%', date('m/d/y', ($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') ? strtotime($fecha_primer_trabajo) : strtotime($this->fields['fecha_ini'])), $html);
						$html = str_replace('%hasta%', date('m/d/y', strtotime($this->fields['fecha_fin'])), $html);
					} else {
						$html = str_replace('%desde%', date('d-m-y', ($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') ? strtotime($fecha_primer_trabajo) : strtotime($this->fields['fecha_ini'])), $html);
						$html = str_replace('%hasta%', date('d-m-y', strtotime($this->fields['fecha_fin'])), $html);
					}

					if ($this->fields['forma_cobro'] == 'RETAINER') {
						$glosa_tipo_monto = __('Monto Retainer');
					}

					if ($this->fields['forma_cobro'] == 'FLAT FEE') {
						$glosa_tipo_monto = __('Monto Flat fee');
					}

					if ($this->fields['forma_cobro'] == 'PROPORCIONAL') {
						$glosa_tipo_monto = __('Monto Proporcional');
					}

					$tr_retainer .= '<tr class="tr_datos"><td width="10%">&nbsp;</td><td align="left" width="60%"><b>' . $glosa_tipo_monto . '</b></td><td align="right" width="30%">' . $valor_monto_contrato . '</td></tr>';

					if ($this->fields['forma_cobro'] == 'TASA' || $this->fields['forma_cobro'] == 'CAP') {
						$html = str_replace('%subtotal%', __('Subtotal'), $html);
						$html = str_replace('%monto_retainer%', '', $html);
						$html = str_replace('%valor_monto_contrato%', '', $html);
						$html = str_replace('%monto_total%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['impuesto'] + $subtotal_sin_impuesto, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
						$html = str_replace('%monto_subtotal%', $moneda->fields['simbolo'] . $this->espacio . number_format($subtotal_sin_impuesto, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
						$html = str_replace('%tr_retainer%', '', $html);
					}

					if ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') {
						$html = str_replace('%subtotal%', __('Subtotal Excesos'), $html);
						$html = str_replace('%monto_retainer%', __('Monto Retainer'), $html);
						$html = str_replace('%valor_monto_contrato%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto_contrato'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
						$html = str_replace('%monto_total%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['impuesto'] + $subtotal_sin_impuesto + $this->fields['monto_contrato'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
						$html = str_replace('%monto_subtotal%', $moneda->fields['simbolo'] . $this->espacio . number_format($subtotal_sin_impuesto, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
						$html = str_replace('%tr_retainer%', $tr_retainer, $html);
					}

					if ($this->fields['forma_cobro'] == 'FLAT FEE') {
						$html = str_replace('%subtotal%', '', $html);
						$html = str_replace('%monto_retainer%', __('Monto Flat fee'), $html);
						$html = str_replace('%valor_monto_contrato%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto_contrato'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
						$html = str_replace('%monto_total%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['impuesto'] + $subtotal_sin_impuesto + $this->fields['monto_contrato'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
						$html = str_replace('%monto_subtotal%', '', $html);
						$html = str_replace('%tr_retainer%', $tr_retainer, $html);
					}


					$html = str_replace('%total_horas%', $horas . ':' . sprintf("%02d", $minutes), $html);
					$html = str_replace('%monto_impuesto%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['impuesto'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				}

				break;

			case 'RESTAR_RETAINER': //GenerarDocumento2
				if ($columna_hrs_retainer || $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL')
					$html = str_replace('%retainer%', __('Retainer'), $html);
				else
					$html = str_replace('%retainer%', '', $html);
				if ($columna_hrs_retainer || $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') {
					$html = str_replace('%valor_retainer%', '(' . $cobro_moneda->moneda[$this->fields['id_moneda_monto']]['simbolo'] . $this->espacio . number_format($this->fields['monto_contrato'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ')', $html);
				} else {
					$html = str_replace('%valor_retainer%', '', $html);
				}
				break;

			case 'DETALLE_COBRO_RETAINER': //GenerarDocumento2
				$html = str_replace('%horas_retainer%', 'Horas retainer', $html);
				$html = str_replace('%valor_horas_retainer%', Utiles::horaDecimal2HoraMinuto($this->fields['retainer_horas']), $html);
				$html = str_replace('%horas_adicionales%', 'Horas adicionales', $html);
				$html = str_replace('%valor_horas_adicionales%', Utiles::horaDecimal2HoraMinuto(($this->fields['total_minutos'] / 60) - $this->fields['retainer_horas']), $html);
				$html = str_replace('%honorarios_retainer%', 'Honorarios retainer', $html);
				$html = str_replace('%valor_honorarios_retainer%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . $x_resultados['monto_contrato'][$this->fields['id_moneda']], $html);
				$html = str_replace('%honorarios_adicionales%', 'Honorarios adicionales', $html);
				$html = str_replace('%valor_honorarios_adicionales%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . ($x_resultados['monto'][$this->fields['id_moneda']] - $x_resultados['monto_contrato'][$this->fields['id_moneda']]), $html);
				break;

			case 'DETALLE_TARIFA_ADICIONAL': //GenerarDocumento2
				$tarifas_adicionales = $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . " ";

				$query = "SELECT DISTINCT tarifa_hh FROM trabajo WHERE id_cobro = '" . $this->fields['id_cobro'] . "' ORDER BY tarifa_hh DESC";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

				$i = 0;
				while (list($tarifa_hh) = mysql_fetch_array($resp)) {
					if ($i == 0)
						$tarifas_adicionales .= "$tarifa_hh/hr";
					else
						$tarifas_adicionales .= ", $tarifa_hh/hr";
					$i++;
				}

				$html = str_replace('%tarifa_adicional%', __('Tarifa adicional por hora'), $html);
				$html = str_replace('%valores_tarifa_adicionales%', $tarifas_adicionales, $html);
				break;

			case 'FACTURA_NUMERO': //GenerarDocumento2
				$html = str_replace('%factura_nro%', __('Factura') . ' ' . __('N�'), $html);
				break;

			case 'NUMERO_FACTURA': //GenerarDocumento2
				$html = str_replace('%nro_factura%', $this->fields['documento'], $html);
				break;

			case 'DETALLE_HONORARIOS': //GenerarDocumento2
				$horas_cobrables = floor(($this->fields['total_minutos']) / 60);
				$minutos_cobrables = sprintf("%02d", $this->fields['total_minutos'] % 60);
				$duracion_cobrable_decimal = number_format($horas_cobrables + $minutos_cobrables / 60, 1, ',', '');
				$html = str_replace('%horas%', __('Total Horas'), $html);
				if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
					$html = str_replace('%valor_horas%', $duracion_cobrable_decimal, $html);
				} else {
					$html = str_replace('%valor_horas%', $horas_cobrables . ':' . $minutos_cobrables, $html);
				}
				$html = str_replace('%honorarios%', __('Honorarios'), $html);
				if (Conf::GetConf($this->sesion, 'UsarImpuestoSeparado') && $contrato->fields['usa_impuesto_separado'])
					$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto'] - $this->fields['impuesto'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				else
					$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				if ($this->fields['opc_ver_descuento']) {
					$html = str_replace('%DETALLE_COBRO_DESCUENTO%', $this->GenerarDocumento2($parser, 'DETALLE_COBRO_DESCUENTO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				} else {
					$html = str_replace('%DETALLE_COBRO_DESCUENTO%', '', $html);
				}

				$html = str_replace('%DETALLE_COBRO_MONEDA_TOTAL%', $this->GenerarDocumentoComun($parser, 'DETALLE_COBRO_MONEDA_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				break;

			case 'DETALLE_TRAMITES': //GenerarDocumento2
				$html = str_replace('%tramites%', __('Tr�mites'), $html);
				$html = str_replace('%tramites_castropal%', __('Otros Servicios'), $html);
				$aproximacion_tramites = number_format($this->fields['monto_tramites'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
				$valor_tramites = $aproximacion_tramites * $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'];
				$html = str_replace('%valor_tramites%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($valor_tramites, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				break;

			case 'DETALLE_GASTOS': //GenerarDocumento2
				$html = str_replace('%gastos%', __('Gastos'), $html);
				$total_gastos_moneda = 0;
				$impuestos_total_gastos_moneda = 0;

				$total_gastos_moneda = $x_cobro_gastos['gasto_total'];
				$impuestos_total_gastos_moneda = $x_cobro_gastos['gasto_impuesto'];
				if (Conf::GetConf($this->sesion, 'CalculacionCyC')) {
					$total_gastos_moneda = $x_cobro_gastos['gasto_total'];
				}
				$impuestos_total_gastos_moneda = $x_cobro_gastos['gasto_impuesto'];
				$html = str_replace('%valor_gastos%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($total_gastos_moneda, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				break;

			case 'DETALLE_COBRO_DESCUENTO': //GenerarDocumento2
				$html = str_replace('%honorarios%', __('Subtotal Honorarios'), $html);
				/* $cobro_moneda array de monedas al tiempo de emitir/generar el cobro */
				if ($this->fields['descuento'] == 0) {
					if (Conf::GetConf($this->sesion, 'FormatoNotaCobroMTA')) {

						$valor_honorarios = number_format($x_resultados['monto_subtotal'][$this->fields['opc_moneda_total']], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
						$html = str_replace('%valor_honorarios%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . $valor_honorarios, $html);
						$html = str_replace('%valor_descuento%', '', $html);
						$html = str_replace('%porcentaje_descuento%', '', $html);
						$html = str_replace('%descuento%', '', $html);
						break;
					} else {
						return '';
					}
				}

				$valor_honorarios = number_format($x_resultados['monto_subtotal'][$this->fields['opc_moneda_total']], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
				$valor_descuento = number_format($x_resultados['descuento'][$this->fields['opc_moneda_total']], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
				$valor_honorarios_demo = $x_resultados['monto_trabajos'][$this->fields['id_moneda']];

				if ($this->EsCobrado()) {
					$valor_descuento_demo = $x_resultados['descuento_honorarios'][$this->fields['id_moneda']];
				} else {
					$valor_descuento_demo = $x_resultados['descuento'][$this->fields['id_moneda']];
				}

				$html = str_replace('%valor_honorarios_demo%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . $this->espacio . number_format($valor_honorarios_demo, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_descuento_demo%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . $this->espacio . number_format($valor_descuento_demo, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				if (Conf::GetConf($this->sesion, 'CalculacionCyC')) {
					$html = str_replace('%valor_honorarios%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . $valor_honorarios, $html);
					$html = str_replace('%valor_descuento%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . $valor_descuento, $html);
				}

				$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto_subtotal'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%descuento%', __('Descuento'), $html);

				if ($x_resultados['monto_trabajos'][$this->fields['id_moneda']] > 0) {
					$porcentaje_demo = ($x_resultados['descuento'][$this->fields['id_moneda']] * 100) / $x_resultados['monto_trabajos'][$this->fields['id_moneda']];
				}
				$html = str_replace('%porcentaje_descuento_demo%', ' (' . number_format($porcentaje_demo, 0) . '%)', $html);

				if ($this->fields['monto_subtotal'] > 0) {
					$porcentaje = ($this->fields['descuento'] * 100) / $this->fields['monto_subtotal'];
				}
				$html = str_replace('%porcentaje_descuento%', ' (' . number_format($porcentaje, 0) . '%)', $html);
				$html = str_replace('%valor_descuento%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['descuento'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%total_honorarios%', __('Total Honorarios'), $html);

				$html = str_replace('%valor_honorarios_con_descuento%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				break;

			case 'RESUMEN_CAP': //GenerarDocumento2
				$monto_trabajo_con_descuento = $x_resultados['monto_trabajo_con_descuento'][$this->fields['id_moneda_monto']];

				$monto_restante = $this->fields['monto_contrato'] - ( $this->TotalCobrosCap() + ($this->fields['monto_trabajos'] - $this->fields['descuento']) * $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['tipo_cambio'] );
				//$monto_restante = $this->fields['monto_contrato'] -  $monto_trabajo_con_descuento;

				$html = str_replace('%cap%', __('Total CAP'), $html);
				$html = str_replace('%valor_cap%', $cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['simbolo'] . $this->espacio . $this->fields['monto_contrato'], $html);
				$html = str_replace('%COBROS_DEL_CAP%', $this->GenerarDocumento2($parser, 'COBROS_DEL_CAP', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%restante%', __('Monto restante'), $html);
				$html = str_replace('%valor_restante%', $cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['simbolo'] . $this->espacio . number_format($monto_restante, $cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				break;

			case 'COBROS_DEL_CAP': //GenerarDocumento2
				$row_tmpl = $html;
				$html = '';

				$query = "SELECT cobro.id_cobro, (monto_trabajos*cm2.tipo_cambio)/cm1.tipo_cambio
										FROM cobro
										JOIN contrato ON cobro.id_contrato=contrato.id_contrato
										JOIN cobro_moneda as cm1 ON cobro.id_cobro=cm1.id_cobro AND cm1.id_moneda=contrato.id_moneda_monto
										JOIN cobro_moneda as cm2 ON cobro.id_cobro=cm2.id_cobro AND cm2.id_moneda=cobro.id_moneda
									 WHERE cobro.id_contrato=" . $this->fields['id_contrato'] . "
										 AND cobro.forma_cobro='CAP'";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				while (list($id_cobro, $monto_cap) = mysql_fetch_array($resp)) {
					$row = $row_tmpl;

					$row = str_replace('%numero_cobro%', __('Cobro') . ' ' . $id_cobro, $row);
					$row = str_replace('%valor_cap_del_cobro%', $cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['simbolo'] . $this->espacio . number_format($monto_cap, $cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

					$html .= $row;
				}
				break;

			case 'ASUNTOS': //GenerarDocumento2
				$row_tmpl = $html;
				$html = '';

				for ($k = 0; $k < count($this->asuntos); $k++) {

					$asunto = new Asunto($this->sesion);
					$asunto->LoadByCodigo($this->asuntos[$k]);

					unset($GLOBALS['totales']);
					$totales = array();
					$totales['tiempo'] = 0;
					$totales['tiempo_trabajado'] = 0;
					$totales['tiempo_trabajado_real'] = 0;
					$totales['tiempo_retainer'] = 0;
					$totales['tiempo_flatfee'] = 0;
					$totales['tiempo_descontado'] = 0;
					$totales['tiempo_descontado_real'] = 0;
					$totales['valor'] = 0;
					$categoria_duracion_horas = 0;
					$categoria_duracion_minutos = 0;
					$categoria_valor = 0;
					$total_trabajos_categoria = '';
					$encabezado_trabajos_categoria = '';

					$query = "SELECT count(*) FROM tramite
									WHERE id_cobro=" . $this->fields['id_cobro'] . "
										AND codigo_asunto='" . $asunto->fields['codigo_asunto'] . "'";
					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
					list($cont_tramites) = mysql_fetch_array($resp);

					$query = "SELECT count(*) FROM trabajo
									WHERE id_cobro=" . $this->fields['id_cobro'] . "
										AND codigo_asunto='" . $asunto->fields['codigo_asunto'] . "'
										AND id_tramite=0 " . ($this->fields['opc_ver_cobrable'] ? "" : "AND trabajo.visible = 1");
					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
					list($cont_trabajos) = mysql_fetch_array($resp);

					$query = "SELECT count(*) FROM cta_corriente
									 WHERE id_cobro=" . $this->fields['id_cobro'] . "
										AND codigo_asunto='" . $asunto->fields['codigo_asunto'] . "'";
					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
					list($cont_gastos) = mysql_fetch_array($resp);
					$row = $row_tmpl;
					$row = str_replace('%separador%', '<hr size="2" class="separador">', $row);

					if (count($this->asuntos) > 1) {
						$row = str_replace('%salto_pagina_varios_asuntos%', '&nbsp;<br clear=all style="mso-special-character:line-break; page-break-before:always" size="1" class="divisor">', $row);
						$row = str_replace('%salto_pagina_un_asunto%', '', $row);
						$row = str_replace('%asunto_extra%', __('Asunto'), $row);
						$row = str_replace('%glosa_asunto_sin_codigo_extra%', $asunto->fields['glosa_asunto'], $row);
					} else {
						$row = str_replace('%salto_pagina_varios_asuntos%', '', $row);
						$row = str_replace('%salto_pagina_un_asunto%', '&nbsp;<br clear=all style="mso-special-character:line-break; page-break-before:always" size="1" class="divisor">', $row);
						$row = str_replace('%asunto_extra%', '', $row);
						$row = str_replace('%glosa_asunto_sin_codigo_extra%', '', $row);
					}

					$row = str_replace('%asunto%', __('Asunto'), $row);

					if (Conf::GetConf($this->sesion, 'GlosaAsuntoSinCodigo')) {
						$row = str_replace('%glosa_asunto%', $asunto->fields['glosa_asunto'], $row);
						$row = str_replace('%glosa_asunto_secundario%', $asunto->fields['glosa_asunto'], $row);
					} else {
						$row = str_replace('%glosa_asunto%', $asunto->fields['codigo_asunto'] . " - " . $asunto->fields['glosa_asunto'], $row);
						$row = str_replace('%glosa_asunto_secundario%', $asunto->fields['codigo_asunto_secundario'] . " - " . $asunto->fields['glosa_asunto'], $row);
					}
					$row = str_replace('%glosa_asunto_sin_codigo%', $asunto->fields['glosa_asunto'], $row);
					$row = str_replace('%glosa_asunto_codigo_area%', $asunto->fields['codigo_asunto'] . '-' . sprintf("%02d", ($asunto->fields['id_area_proyecto'] - 1)) . " - " . $asunto->fields['glosa_asunto'], $row);
					$row = str_replace('%valor_codigo_asunto%', $asunto->fields['codigo_asunto'], $row);
					$row = str_replace('%codigo_cliente_secundario%', empty($cliente->fields['codigo_cliente_secundario']) ? '' : __('C�digo Cliente'), $row);
					$row = str_replace('%valor_codigo_cliente_secundario%', empty($cliente->fields['codigo_cliente_secundario']) ? '' : empty($cliente->fields['codigo_cliente_secundario']), $row);
					$row = str_replace('%contacto%', empty($asunto->fields['contacto']) ? '' : __('Contacto'), $row);
					$row = str_replace('%valor_contacto%', empty($asunto->fields['contacto']) ? '' : $asunto->fields['contacto'], $row);

					$row = str_replace('%registro%', __('Registro de Tiempo'), $row);
					$row = str_replace('%telefono%', empty($asunto->fields['fono_contacto']) ? '' : __('Tel�fono'), $row);
					$row = str_replace('%valor_telefono%', empty($asunto->fields['fono_contacto']) ? '' : $asunto->fields['fono_contacto'], $row);

					if ($cont_trabajos > 0) {
						if ($this->fields["opc_ver_detalles_por_hora"] == 1) {
							$row = str_replace('%espacio_trabajo%', '<br>', $row);
							$row = str_replace('%servicios%', __('Servicios prestados'), $row);
							$row = str_replace('%TRABAJOS_ENCABEZADO%', $this->GenerarDocumento2($parser, 'TRABAJOS_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
							$row = str_replace('%TRABAJOS_FILAS%', $this->GenerarDocumento2($parser, 'TRABAJOS_FILAS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
							$row = str_replace('%TRABAJOS_TOTAL%', $this->GenerarDocumento2($parser, 'TRABAJOS_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
						} else {
							$row = str_replace('%espacio_trabajo%', '', $row);
							$row = str_replace('%servicios%', '', $row);
							$row = str_replace('%TRABAJOS_ENCABEZADO%', '', $row);
							$row = str_replace('%TRABAJOS_FILAS%', '', $row);
							$row = str_replace('%TRABAJOS_TOTAL%', '', $row);
						}
						$row = str_replace('%DETALLE_PROFESIONAL%', $this->GenerarDocumento2($parser, 'DETALLE_PROFESIONAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
					} else if ($this->fields['opc_mostrar_asuntos_cobrables_sin_horas'] == 1) {
						$row = str_replace('%espacio_trabajo%', '', $row);
						$row = str_replace('%DETALLE_PROFESIONAL%', '', $row);
						$row = str_replace('%servicios%', 'No existen trabajos asociados a este asunto.', $row);
						$row = str_replace('%TRABAJOS_ENCABEZADO%', '', $row);
						$row = str_replace('%TRABAJOS_FILAS%', '', $row);
						$row = str_replace('%TRABAJOS_TOTAL%', '', $row);
					} else {
						$row = str_replace('%espacio_trabajo%', '', $row);
						$row = str_replace('%DETALLE_PROFESIONAL%', '', $row);
						$row = str_replace('%servicios%', '', $row);
						$row = str_replace('%TRABAJOS_ENCABEZADO%', '', $row);
						$row = str_replace('%TRABAJOS_FILAS%', '', $row);
						$row = str_replace('%TRABAJOS_TOTAL%', '', $row);
					}

					/*
					  Gastos implementado
					 */
					if ($this->fields['opc_ver_gastos'] != 0) {
						/*
						  Revisar si se trate sobre el nuevo template
						 */
						if ($k == 0 && trim(strstr($row, '%GASTOS_FILAS%')) != '') {
							$templateNotaCobroGastosSeparados = 1;
						}
						foreach ($x_cobro_gastos['gasto_detalle'] as $d) {
							if ($this->asuntos[$k] == $d['codigo_asunto']) {
								$asunto_tiene_gastos = 1;
								break;
							}
						}

						if ($templateNotaCobroGastosSeparados && $asunto_tiene_gastos) {
							$asunto_tiene_gastos = 0;
							//$html = str_replace('%separador%', '<hr size="2" class="separador">', $html);
							$row = str_replace('%glosa_gastos%', __('Gastos'), $row);
							if ($lang == 'es') {
								$row = str_replace('%glosa_gasto%', __('GASTOS'), $row);
							} else {
								$row = str_replace('%glosa_gasto%', __('EXPENSES'), $row);
							}
							$row = str_replace('%expenses%', __('%expenses%'), $row); //en vez de Disbursements es Expenses en ingl�s
							$row = str_replace('%detalle_gastos%', __('Detalle de gastos'), $row);

							$row = str_replace('%GASTOS_ENCABEZADO%', $this->GenerarDocumento2($parser, 'GASTOS_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
							$row = str_replace('%GASTOS_FILAS%', $this->GenerarDocumento2($parser, 'GASTOS_FILAS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
							$row = str_replace('%GASTOS_TOTAL%', $this->GenerarDocumento2($parser, 'GASTOS_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
						} else {
							//$html = str_replace('%separador%', '<hr size="2" class="separador">', $html);
							$row = str_replace('%glosa_gastos%', '', $row);
							if ($lang == 'es') {
								$row = str_replace('%glosa_gasto%', '', $row);
							} else {
								$row = str_replace('%glosa_gasto%', '', $row);
							}
							$row = str_replace('%expenses%', '', $row); //en vez de Disbursements es Expenses en ingl�s
							$row = str_replace('%detalle_gastos%', '', $row);

							$row = str_replace('%GASTOS_ENCABEZADO%', '', $row);
							$row = str_replace('%GASTOS_FILAS%', '', $row);
							$row = str_replace('%GASTOS_TOTAL%', '', $row);
						}
					}


					$query_hitos = "SELECT count(*) from cobro_pendiente where hito=1 and id_cobro=" . $this->fields['id_cobro'];
					$resp_hitos = mysql_query($query_hitos, $this->sesion->dbh) or Utiles::errorSQL($query_hitos, __FILE__, __LINE__, $this->sesion->dbh);

					list($cont_hitos) = mysql_fetch_array($resp_hitos);
					$row = str_replace('%hitos%', '<br>' . __('Hitos') . '<br/><br/>', $row);
					if ($cont_hitos > 0) {
						global $total_hitos, $estehito, $cantidad_hitos, $moneda_hitos, $tipo_cambio_hitos;

						$row = str_replace('%HITOS_FILAS%', $this->GenerarDocumento2($parser, 'HITOS_FILAS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
						$row = str_replace('%HITOS_TOTAL%', $this->GenerarDocumento2($parser, 'HITOS_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
						$row = str_replace('%HITOS_ENCABEZADO%', $this->GenerarDocumento2($parser, 'HITOS_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
						$row = str_replace('%hitos%', '<br>' . __('Hitos') . '(' . $estehito . ' de ' . $total_hitos . ')<br/><br/>', $row);
					} else {
						$row = str_replace('%hitos%', '', $row);
						$row = str_replace('%HITOS_ENCABEZADO%', '', $row);
						$row = str_replace('%HITOS_FILAS%', '', $row);
						$row = str_replace('%HITOS_TOTAL%', '', $row);
					}

					if ($cont_tramites > 0) {
						$row = str_replace('%espacio_tramite%', '<br>', $row);
						$row = str_replace('%servicios_tramites%', __('Tr�mites'), $row);
						$row = str_replace('%titulo_seccion_tramites_castropal%', __('Otros Servicios'), $row);
						$row = str_replace('%servicios_tramites_castropal%', __('Otros Servicios Profesionales'), $row);
						$row = str_replace('%TRAMITES_ENCABEZADO%', $this->GenerarDocumentoComun($parser, 'TRAMITES_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
						$row = str_replace('%TRAMITES_FILAS%', $this->GenerarDocumentoComun($parser, 'TRAMITES_FILAS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
						$row = str_replace('%TRAMITES_TOTAL%', $this->GenerarDocumentoComun($parser, 'TRAMITES_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
					} else {
						$row = str_replace('%espacio_tramite%', '', $row);
						$row = str_replace('%servicios_tramites%', '', $row);
						$row = str_replace('%titulo_seccion_tramites_castropal%', '', $row);
						$row = str_replace('%servicios_tramites_castropal%', '', $row);
						$row = str_replace('%TRAMITES_ENCABEZADO%', '', $row);
						$row = str_replace('%TRAMITES_FILAS%', '', $row);
						$row = str_replace('%TRAMITES_TOTAL%', '', $row);
					}
					// El parametro separar_asunto se define para asegurarse que solamente se separan los asuntos,
					// cuando el template de ese cliente lo soporta.
					$asunto->separar_asuntos = true;
					if (Conf::GetConf($this->sesion, 'ParafoGastosSoloSiHayGastos')) {
						if ($cont_gastos > 0) {
							$row = str_replace('%GASTOS%', $this->GenerarDocumento2($parser, 'GASTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
						} else {
							$row = str_replace('%GASTOS%', '', $row);
						}
					} else {
						$row = str_replace('%GASTOS%', $this->GenerarDocumento2($parser, 'GASTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
					}

					$asunto->separar_asuntos = false;
					#especial mb
					$row = str_replace('%codigo_asunto_mb%', __('C�digo M&B'), $row);

					if ($cont_trabajos > 0 || $cont_hitos > 0 || $asunto->fields['trabajos_total_duracion'] > 0 || $asunto->fields['trabajos_total_duracion_trabajada'] > 0 || $cont_tramites > 0 || ( $cont_gastos > 0 && $templateNotaCobroGastosSeparados ) || Conf::GetConf($this->sesion, 'MostrarAsuntosSinTrabajosGastosTramites') || ($this->fields['opc_mostrar_asuntos_cobrables_sin_horas'] == 1 && ! $this->get_detalle_en_asuntos())) {
						$html .= $row;
					}

					$html = str_replace('%texto_servicios_profesionales%', __('Servicios Profesionales por hora'), $html);
					$html = str_replace('%descripcion_servicios%', __('Descripci�n de Servicios'), $html);
					$html = str_replace('%para_los_servicios_prestados%', __('Para los servicios profesionales prestados'), $html);
				}
				break;

			case 'TRAMITES': //GenerarDocumento2
				$row_tmpl = $html;
				$html = '';
				for ($k = 0; $k < count($this->asuntos); $k++) {
					$asunto = new Asunto($this->sesion);
					$asunto->LoadByCodigo($this->asuntos[$k]);

					$categoria_duracion_horas = 0;
					$categoria_duracion_minutos = 0;
					$categoria_valor = 0;
					$total_trabajos_categoria = '';
					$encabezado_trabajos_categoria = '';

					$query = "SELECT count(*) FROM CTA_CORRIENTE WHERE id_cobro=" . $this->fields['id_cobro'];
					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, $this->sesion->dbh);
					list($cont_gastos) = mysql_fetch_array($resp);

					$row = $row_tmpl;

					if (count($this->asuntos) > 1) {
						$row = str_replace('%salto_pagina_varios_asuntos%', '&nbsp;<br clear=all style="mso-special-character:line-break; page-break-before:always" size="1" class="divisor">', $row);
						$row = str_replace('%salto_pagina_un_asunto%', '', $row);
						$row = str_replace('%asunto_extra%', __('Asunto'), $row);
						$row = str_replace('%glosa_asunto_sin_codigo_extra%', $asunto->fields['glosa_asunto'], $row);
					} else {
						$row = str_replace('%salto_pagina_varios_asuntos%', '', $row);
						$row = str_replace('%salto_pagina_un_asunto%', '&nbsp;<br clear=all style="mso-special-character:line-break; page-break-before:always" size="1" class="divisor">', $row);
						$row = str_replace('%asunto_extra%', '', $row);
						$row = str_replace('%glosa_asunto_sin_codigo_extra%', '', $row);
					}

					$row = str_replace('%asunto%', __('Asunto'), $row);
					$row = str_replace('%glosa_asunto%', $asunto->fields['codigo_asunto'] . " - " . $asunto->fields['glosa_asunto'], $row);
					$row = str_replace('%glosa_asunto_sin_codigo%', $asunto->fields['glosa_asunto'], $row);
					$row = str_replace('%glosa_asunto_codigo_area%', $asunto->fields['codigo_asunto'] . '-' . sprintf("%02d", ($asunto->fields['id_area_proyecto'] - 1)) . " - " . $asunto->fields['glosa_asunto'], $row);
					$row = str_replace('%valor_codigo_asunto%', $asunto->fields['codigo_asunto'], $row);
					$row = str_replace('%codigo_cliente_secundario%', empty($cliente->fields['codigo_cliente_secundario']) ? '' : __('C�digo Cliente'), $row);
					$row = str_replace('%valor_codigo_cliente_secundario%', empty($cliente->fields['codigo_cliente_secundario']) ? '' : empty($cliente->fields['codigo_cliente_secundario']), $row);
					$row = str_replace('%contacto%', empty($asunto->fields['contacto']) ? '' : __('Contacto'), $row);
					$row = str_replace('%valor_contacto%', empty($asunto->fields['contacto']) ? '' : $asunto->fields['contacto'], $row);
					$row = str_replace('%servicios%', __('Servicios prestados'), $row);
					$row = str_replace('%registro%', __('Registro de Tiempo'), $row);
					$row = str_replace('%telefono%', empty($asunto->fields['fono_contacto']) ? '' : __('Tel�fono'), $row);
					$row = str_replace('%valor_telefono%', empty($asunto->fields['fono_contacto']) ? '' : $asunto->fields['fono_contacto'], $row);

					$row = str_replace('%TRAMITES_ENCABEZADO%', $this->GenerarDocumentoComun($parser, 'TRAMITES_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
					$row = str_replace('%TRAMITES_FILAS%', $this->GenerarDocumentoComun($parser, 'TRAMITES_FILAS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
					$row = str_replace('%TRAMITES_TOTAL%', $this->GenerarDocumentoComun($parser, 'TRAMITES_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);

					$row = str_replace('%DETALLE_PROFESIONAL%', $this->GenerarDocumento2($parser, 'DETALLE_PROFESIONAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);

					if (Conf::GetConf($this->sesion, 'ParafoGastosSoloSiHayGastos')) {
						if ($cont_gastos > 0) {
							$row = str_replace('%GASTOS%', $this->GenerarDocumento2($parser, 'GASTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
						} else {
							$row = str_replace('%GASTOS%', '', $row);
						}
					} else {
						$row = str_replace('%GASTOS%', $this->GenerarDocumento2($parser, 'GASTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
					}

					$row = str_replace('%codigo_asunto_mb%', __('C�digo M&B'), $row);

					if ($asunto->fields['trabajos_total_duracion'] > 0 || $asunto->fields['trabajos_total_duracion_trabajada'] > 0) {
						$html .= $row;
					}
				}
				break;

			case 'TRABAJOS_ENCABEZADO': //GenerarDocumento2

				if ($this->fields['estado'] == 'CREADO' || $this->fields['estado'] == 'EN REVISION') {
					$html = str_replace('%td_id_trabajo%', '<td align="center">%ntrabajo%</td>', $html);
				} else {
					$html = str_replace('%td_id_trabajo%', '', $html);
				}
				$html = str_replace('%ntrabajo%', __('N�</br>Trabajo'), $html);
				if ($this->fields['opc_ver_solicitante']) {
					$html = str_replace('%td_solicitante%', '<td width="16%" align="left">%solicitante%</td>', $html);
				} else {
					$html = str_replace('%td_solicitante%', '', $html);
				}
				$html = str_replace('%solicitante%', __('Solicitado Por'), $html);
				if ($lang == 'es') {
					$html = str_replace('%id_asunto%', __('ID Asunto'), $html);
					$html = str_replace('%tarifa_hora%', __('Tarifa<br>Hora'), $html);
				} else {
					$html = str_replace('%id_asunto%', __('Matter <br> ID'), $html);
					$html = str_replace('%tarifa_hora%', __('Hourly<br> Rate'), $html);
				}
				$html = str_replace('%num_registro%', __('N� Registro'), $html);
				$html = str_replace('%importe%', __('Importe'), $html);
				$html = str_replace('%tarifa_hora%', __('Tarifa Hora'), $html);
				$html = str_replace('%ordenado_por%', $this->fields['opc_ver_solicitante'] ? __('Ordenado Por') : '', $html);
				$html = str_replace('%ordenado_por_jjr%', $this->fields['opc_ver_solicitante'] ? __('Solicitado Por') : '', $html);
				$html = str_replace('%periodo%', (($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') and ( $this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '')) ? '' : __('Periodo'), $html);
				$html = str_replace('%valor_periodo_ini%', ($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') ? '' : Utiles::sql2fecha($this->fields['fecha_ini'], $idioma->fields['formato_fecha']), $html);
				$html = str_replace('%valor_periodo_fin%', ($this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '') ? '' : __('hasta') . ' ' . Utiles::sql2fecha($this->fields['fecha_fin'], $idioma->fields['formato_fecha']), $html);
				$html = str_replace('%cliente%', __('Cliente'), $html);
				$html = str_replace('%glosa_cliente%', $cliente->fields['glosa_cliente'], $html);
				$html = str_replace('%asunto%', __('Asunto'), $html);
				$html = str_replace('%glosa_asunto%', $asunto->fields['glosa_asunto'], $html);
				$html = str_replace('%fecha%', __('Fecha'), $html);
				$html = str_replace('%descripcion%', __('Descripci�n'), $html);
				$html = str_replace('%servicios_prestados%', __('Servicios Prestados'), $html);
				$html = str_replace('%detalle_trabajo%', __('Detalle del Trabajo Realizado'), $html);
				$html = str_replace('%profesional%', __('Profesional'), $html);
				$html = str_replace('%duracion_cobrable%', __('Duraci�n cobrable'), $html);
				$html = str_replace('%monto_total%', __('Monto total'), $html);
				$html = str_replace('%staff%', __('Staff'), $html);
				$html = str_replace('%abogado%', __('Abogado'), $html);
				$html = str_replace('%horas%', __('Horas'), $html);
				$html = str_replace('%monto%', __('Monto'), $html);
				$html = str_replace('%abogado_raz%', __('abogado_raz'), $html);
				$html = str_replace('%tiempo_raz%', __('tiempo_raz'), $html);

				if ($this->fields['opc_ver_columna_cobrable']) {
					$html = str_replace('%cobrable%', __('<td align="center" width="80">Cobrable</td>'), $html);  // tAndres Oestemer
				} else {
					$html = str_replace('%cobrable%', '', $html);
				}

				if (Conf::GetConf($this->sesion, 'OrdenarPorCategoriaUsuario')) {
					if (!empty($this->siguiente['categoria_abogado'])) {
						$categoria = $this->siguiente['categoria_abogado'];
						unset($this->siguiente['categoria_abogado']);
					} else {

						if ($lang == 'es') {
							$query_categoria_lang = "cat.glosa_categoria";
						} else {
							$query_categoria_lang = "IFNULL(cat.glosa_categoria_lang , cat.glosa_categoria)";
						}

						$query = "SELECT $query_categoria_lang
										FROM trabajo
										JOIN usuario ON trabajo.id_usuario=usuario.id_usuario
										JOIN prm_categoria_usuario AS cat ON cat.id_categoria_usuario=usuario.id_categoria_usuario
										WHERE trabajo.id_cobro = '" . $this->fields['id_cobro'] . "'
										AND trabajo.codigo_asunto = '" . $asunto->fields['codigo_asunto'] . "'
										AND trabajo.visible=1
										ORDER BY cat.orden, usuario.id_usuario, trabajo.fecha ASC
										LIMIT 1";
						$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
						list($categoria) = mysql_fetch_array($resp);
					}
					$html = str_replace('%categoria_abogado%', __($categoria), $html);
				} else if (Conf::GetConf($this->sesion, 'SepararPorUsuario')) {
					if (!empty($this->siguiente['nombre_usuario'])) {
						$abogado = $this->siguiente['nombre_usuario'];
						$tarifa = $this->siguiente['tarifa_usuario'];

						unset($this->siguiente['nombre_usuario']);
						unset($this->siguiente['tarifa_usuario']);
					} else {
						$query = "SELECT CONCAT(usuario.nombre,' ',usuario.apellido1),trabajo.tarifa_hh
										FROM trabajo
										JOIN usuario ON trabajo.id_usuario=usuario.id_usuario
										WHERE trabajo.id_cobro = '" . $this->fields['id_cobro'] . "'
										AND trabajo.codigo_asunto = '" . $asunto->fields['codigo_asunto'] . "'
										AND trabajo.visible=1
										ORDER BY usuario.id_categoria_usuario, usuario.id_usuario, trabajo.fecha ASC
										LIMIT 1";
						$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
						list($abogado, $tarifa) = mysql_fetch_array($resp);
					}
					$html = str_replace('%categoria_abogado%', __($abogado), $html);
					$html = str_replace('%tarifa%', $moneda->fields['simbolo'] . $this->espacio . number_format($tarifa, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				} else {
					$html = str_replace('%categoria_abogado%', '', $html);
				}

				//Por conf se ve si se imprime la duracion trabajada cuando el cobro este en estado creado tambien
				$ImprimirDuracionTrabajada = Conf::GetConf($this->sesion, 'ImprimirDuracionTrabajada');

				/* Lo anchores con la extension _bmahj usa Bofill Mir y lo que hace es que llama a las columnas
				  en la lista de trabajos igual como a las columnas en el resumen profesional */

				if ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') {
					$html = str_replace('%td_retainer%', '<td width="80" align="center">%duracion_retainer%</td>', $html);
					$html = str_replace('%duracion_retainer%', __('Duraci�n Retainer'), $html);
				} else {
					$html = str_replace('%td_retainer%', '', $html);
				}

				if ($this->fields['forma_cobro'] == 'FLAT FEE') {
					$html = str_replace('%duracion_trabajada_bmahj%', '', $html);
					$html = str_replace('%duracion_descontada_bmahj%', '', $html);
					$html = str_replace('%duracion_bmahj%', __('Hrs. Trabajadas'), $html);
					$html = str_replace('%tiempo%', __('Tiempo'), $html);
					$html = str_replace('%duracion_trabajada%', '', $html);
					$html = str_replace('%duracion_descontada%', '', $html);
					$html = str_replace('%duracion%', __('Duraci�n trabajada'), $html);
				}
				if ($ImprimirDuracionTrabajada && ( $this->fields['estado'] == 'CREADO' || $this->fields['estado'] == 'EN REVISION' )) {
					$html = str_replace('%duracion_trabajada_bmahj%', __('Hrs. Trabajadas'), $html);
					$html = str_replace('%duracion_bmahj%', __('Hrs. Tarificadas'), $html);
					$html = str_replace('%tiempo%', __('Tiempo'), $html);
					$html = str_replace('%duracion_descontada_bmahj%', __('Hrs. Castigadas'), $html);
					$html = str_replace('%duracion_descontada%', __('Hrs.:Mins. Descontadas'), $html);

					$html = str_replace('%duracion_trabajada%', __('Duraci�n trabajada'), $html);
					$html = str_replace('%duracion%', __('Duraci�n cobrable'), $html);
					if ($descontado) {
						$html = str_replace('%duracion_descontada%', __('Duraci�n descontada'), $html);
					} else {
						$html = str_replace('%duracion_descontada%', '', $html);
					}
				} else if ($this->fields['opc_ver_horas_trabajadas']) {
					$html = str_replace('%duracion_trabajada_bmahj%', __('Hrs. Trabajadas'), $html);
					$html = str_replace('%duracion_bmahj%', __('Hrs. Tarificadas'), $html);
					$html = str_replace('%tiempo%', __('Tiempo'), $html);
					$html = str_replace('%duracion_descontada_bmahj%', __('Hrs. Castigadas'), $html);
					$html = str_replace('%duracion_descontada%', __('Hrs.:Mins. Descontadas'), $html);

					$html = str_replace('%duracion_trabajada%', __('Duraci�n trabajada'), $html);
					$html = str_replace('%duracion%', __('Duraci�n cobrable'), $html);
					$html = str_replace('%duracion_descontada%', __('Duraci�n castigada'), $html);
				} else {
					$html = str_replace('%duracion_trabajada_bmahj%', '', $html);
					$html = str_replace('%duracion_descontada_bmahj%', '', $html);
					$html = str_replace('%duracion_bmahj%', __('Hrs. Tarificadas'), $html);
					$html = str_replace('%tiempo%', __('Tiempo'), $html);

					$html = str_replace('%duracion_trabajada%', '', $html);
					$html = str_replace('%duracion_descontada%', '', $html);
					$html = str_replace('%duracion%', __('Duraci�n'), $html);
				}
				$html = str_replace('%duracion_tyc%', __('Duraci�n'), $html);

				//Por conf se ve si se imprime o no el valor del trabajo
				$ImprimirValorTrabajo = Conf::GetConf($this->sesion, 'ImprimirValorTrabajo');

				if ($ImprimirValorTrabajo && $this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION') {
					$html = str_replace('%valor%', '', $html);
				} else {
					$html = str_replace('%valor%', __('Valor'), $html);
				}
				$html = str_replace('%valor_siempre%', __('Valor'), $html);
				$html = str_replace('%tarifa_fee%', __('%tarifa_fee%'), $html);

				if ($this->fields['opc_ver_detalles_por_hora_categoria'] == 1) {
					$html = str_replace('%td_categoria%', '<td class="td_categoria" align="left">%categoria%</td>', $html);
				} else {
					$html = str_replace('%td_categoria%', '', $html);
				}
				$html = str_replace('%categoria%', __($this->fields['codigo_idioma'] . '_Categor�a'), $html);

				if ($this->fields['opc_ver_detalles_por_hora_tarifa'] == 1) {
					$html = str_replace('%td_tarifa%', '<td width="80" align="center">%tarifa%</td>', $html);
					$html = str_replace('%td_tarifa_ajustada%', '<td width="80" align="center">%tarifa%</td>', $html);
				} else {
					$html = str_replace('%td_tarifa%', '', $html);
					$html = str_replace('%td_tarifa_ajustada%', '', $html);
				}
				$html = str_replace('%tarifa%', __('Tarifa'), $html);

				if ($this->fields['opc_ver_detalles_por_hora_importe'] == 1) {
					$html = str_replace('%td_importe%', '<td width="80" align="center">%importe%</td>', $html);
					$html = str_replace('%td_importe_ajustado%', '<td width="80" align="center">%importe%</td>', $html);
				} else {
					$html = str_replace('%td_importe%', '', $html);
					$html = str_replace('%td_importe_ajustado%', '', $html);
				}
				$html = str_replace('%importe%', __($this->fields['codigo_idioma'] . '_Importe'), $html);
				break;

			case 'TRABAJOS_FILAS': //GenerarDocumento2
				global $categoria_duracion_horas;
				global $categoria_duracion_minutos;
				global $categoria_valor;

				$row_tmpl = $html;
				$html = '';
				$where_horas_cero = '';

				//esto funciona por Conf si el metodo del conf OrdenarPorCategoriaUsuarioe s true se ordena por categoria
				if (Conf::GetConf($this->sesion, 'TrabajosOrdenarPorCategoriaNombreUsuario')) {
					$select_categoria = ", prm_categoria_usuario.id_categoria_usuario";
					$order_categoria = "prm_categoria_usuario.orden, usuario.id_usuario, ";
				} else if (Conf::GetConf($this->sesion, 'TrabajosOrdenarPorCategoriaUsuario')) {
					$select_categoria = ", prm_categoria_usuario.id_categoria_usuario";
					$order_categoria = "prm_categoria_usuario.orden, usuario.id_usuario, ";
				} elseif (Conf::GetConf($this->sesion, 'SepararPorUsuario')) {
					$select_categoria = ", prm_categoria_usuario.id_categoria_usuario";
					$order_categoria = "usuario.id_categoria_usuario, usuario.id_usuario, ";
				} elseif (Conf::GetConf($this->sesion, 'TrabajosOrdenarPorCategoriaDetalleProfesional')) {
					$select_categoria = "";
					$order_categoria = "usuario.id_categoria_usuario DESC, ";
				} elseif (Conf::GetConf($this->sesion, 'TrabajosOrdenarPorFechaCategoria')) {
					$select_categoria = ", prm_categoria_usuario.id_categoria_usuario";
					$order_categoria = "trabajo.fecha, usuario.id_categoria_usuario, usuario.id_usuario, ";
				} else {
					$select_categoria = "";
					$order_categoria = "";
				}

				if (!method_exists('Conf', 'MostrarHorasCero')) {
					if ($this->fields['opc_ver_horas_trabajadas'])
						$where_horas_cero = "AND trabajo.duracion > '0000-00-00 00:00:00'";
					else
						$where_horas_cero = "AND trabajo.duracion_cobrada > '0000-00-00 00:00:00'";
				}

				if ($this->fields['opc_ver_valor_hh_flat_fee'] && $this->fields['forma_cobro'] != 'ESCALONADA') {
					$dato_monto_cobrado = " ( trabajo.tarifa_hh * TIME_TO_SEC( trabajo.duracion_cobrada ) ) / 3600 ";
				} else {
					$dato_monto_cobrado = " trabajo.monto_cobrado ";
				}

				if ($this->fields['opc_ver_horas_trabajadas'] == 0) {
					$cobrable = " AND trabajo.cobrable = 1";
				}

				if ($this->fields['opc_ver_cobrable']) {
					$visible = "";
					if ($this->fields['opc_ver_horas_trabajadas'] == 0) {
						$cobrable = " AND ((trabajo.cobrable = 0 AND trabajo.visible = 0)
										OR (trabajo.cobrable = 1 AND trabajo.visible = 1))";
					}
				} else {
					$visible = "AND trabajo.visible = 1";
				}

				if ($lang == 'es') {
					$query_categoria_lang = "prm_categoria_usuario.glosa_categoria AS categoria,";
				} else {
					$query_categoria_lang = "IFNULL(prm_categoria_usuario.glosa_categoria_lang ,prm_categoria_usuario.glosa_categoria) AS categoria,";
				}

				/*
				 * 	Contenido de filas de seccion trabajo.
				 */
				$query = "SELECT SQL_CALC_FOUND_ROWS
									IF(trabajo.cobrable,trabajo.duracion_cobrada,'00:00:00') as duracion_cobrada,
									trabajo.duracion_retainer,
									trabajo.descripcion,
									trabajo.fecha,
									trabajo.id_usuario,
									$dato_monto_cobrado as monto_cobrado,
									trabajo.visible,
									trabajo.cobrable,
									trabajo.id_trabajo,
									trabajo.tarifa_hh,
									IF (trabajo.cobrable, trabajo.tarifa_hh * ( TIME_TO_SEC( duracion_cobrada ) / 3600 ),0) as importe,
									trabajo.codigo_asunto,
									trabajo.solicitante,
									$query_categoria_lang
									CONCAT_WS(' ', nombre, apellido1) as nombre_usuario,
									trabajo.duracion,
									usuario.username as username $select_categoria
							FROM trabajo
							LEFT JOIN usuario ON trabajo.id_usuario=usuario.id_usuario
							LEFT JOIN cobro ON cobro.id_cobro = trabajo.id_cobro
							LEFT JOIN prm_categoria_usuario ON usuario.id_categoria_usuario=prm_categoria_usuario.id_categoria_usuario
							WHERE trabajo.id_cobro = '" . $this->fields['id_cobro'] . "'
							AND trabajo.codigo_asunto = '" . $asunto->fields['codigo_asunto'] . "'
							$cobrable
							$visible
							AND trabajo.id_tramite=0 $where_horas_cero
							ORDER BY $order_categoria trabajo.fecha ASC,trabajo.descripcion";

				$lista_trabajos = new ListaTrabajos($this->sesion, '', $query);

				$asunto->fields['trabajos_total_duracion'] = 0;
				$asunto->fields['trabajos_total_valor'] = 0;
				$asunto->fields['trabajos_total_duracion_retainer'] = 0;
				$asunto->fields['trabajos_total_importe'] = 0;

				for ($i = 0; $i < $lista_trabajos->num; $i++) {
					$trabajo = $lista_trabajos->Get($i);

					$total_trabajo_importe = $trabajo->fields['importe'];
					$total_trabajo_monto_cobrado = $trabajo->fields['monto_cobrado'];
					$tarifa_hh = $trabajo->fields['tarifa_hh'];
					$duracion_cobrada = $trabajo->fields['duracion_cobrada'];
					$duracion_retainer = $trabajo->fields['duracion_retainer'];
					$duracion = $trabajo->fields['duracion'];
					$retainer_cobro = $this->fields['retainer_horas'];

					list($h, $m, $s) = explode(":", $duracion_cobrada);
					list($h_retainer, $m_retainer, $s_retainer) = explode(":", $duracion_retainer);
					list($ht, $mt, $st) = explode(":", $duracion);

					/* if ($this->fields['forma_cobro'] == 'RETAINER'){
					  $horas = $h + $m / 60 + $s / 3600;
					  $horas_retainer = $h_retainer + $m_retainer / 60 + $s_retainer / 3600;
					  $horas_tarificadas = $horas - $horas_retainer;
					  $horas_tarificadas_retainer = UtilesApp::Decimal2Time($horas_tarificadas);
					  $horas_trabajadas = $ht + $mt /60 + $st / 3600;

					  list($h, $m ,$s) = explode(":",$horas_tarificadas_retainer);
					  $total_trabajo_importe = $tarifa_hh * $horas_tarificadas;
					  } */

					$duracion_cobrada_decimal = $h + $m / 60 + $s / 3600;
					$asunto->fields['trabajos_total_duracion'] += $h * 60 + $m + $s / 60;
					$asunto->fields['trabajos_total_valor'] += $trabajo->fields['monto_cobrado'];
					$asunto->fields['trabajos_total_duracion_retainer'] += $h_retainer * 60 + $m_retainer + $s_retainer / 60;
					$asunto->fields['trabajos_total_importe'] += $trabajo->fields['importe'];
					$asunto->fields['trabajos_total_duracion_trabajada'] += $ht * 60 + $mt + $st / 60;
					$duracion_decimal_trabajada = $ht + $mt / 60 + $st / 3600;
					$duracion_decimal_retainer = $h_retainer + $m_retainer / 60 + $s_retainer / 3600;
					$duracion_decimal_descontada = $ht - $h + ($mt - $m) / 60 + ($st - $s) / 3600;
					if ($horas_retainer - $horas_trabajadas < 0) {
						$duracion_decimal_descontada = $duracion_decimal_descontada - $duracion_decimal_retainer;
					}

					$minutos_decimal = $m / 60;
					$duracion_decimal = $h + $minutos_decimal + $s / 3600;

					if (($mt - $m) < 0) {
						$horas_descontadas = $ht - $h - 1;
						$minutos_descontadas = $mt - $m + 60;
					} else {
						$horas_descontadas = $ht - $h;
						$minutos_descontadas = $mt - $m;
					}

					$categoria_duracion_horas+=round($h);
					$categoria_duracion_minutos+=round($m);
					$categoria_valor+=$trabajo->fields['monto_cobrado'];
					$categoria_duracion_trabajada += $duracion_decimal_trabajada;
					$categoria_duracion_descontada += $duracion_decimal_descontada;

					$row = $row_tmpl;
					$row = str_replace('%valor_codigo_asunto%', $trabajo->fields['codigo_asunto'], $row);
					$row = str_replace('%fecha%', Utiles::sql2fecha($trabajo->fields['fecha'], $idioma->fields['formato_fecha']), $row);
					if ($this->fields['estado'] == 'CREADO' || $this->fields['estado'] == 'EN REVISION') {
						$row = str_replace('%td_id_trabajo%', '<td align="center">%ntrabajo%</td>', $row);
					} else {
						$row = str_replace('%td_id_trabajo%', '', $row);
					}
					$row = str_replace('%ntrabajo%', $trabajo->fields['id_trabajo'], $row);
					$row = str_replace('%descripcion%', ucfirst(stripslashes($trabajo->fields['descripcion'])), $row);
					$row = str_replace('%descripcion_mayus%', strtoupper($trabajo->fields['descripcion']), $row);
					if ($this->fields['opc_ver_solicitante']) {
						$row = str_replace('%td_solicitante%', '<td align="left">%solicitante%</td>', $row);
					} else {
						$row = str_replace('%td_solicitante%', '', $row);
					}
					$row = str_replace('%solicitante%', $this->fields['opc_ver_solicitante'] ? $trabajo->fields['solicitante'] : '', $row);

					$row = str_replace('%username%', $trabajo->fields['username'], $row);
					if ($this->fields['opc_ver_detalles_por_hora_iniciales']) {
						$row = str_replace('%profesional%', $trabajo->fields['username'], $row);
					} else {
						$row = str_replace('%profesional%', $trabajo->fields['nombre_usuario'], $row);
					}

					if ($this->fields['opc_ver_detalles_por_hora_categoria'] == 1)
						$row = str_replace('%td_categoria%', '<td align="left">%categoria%</td>', $row);
					else
						$row = str_replace('%td_categoria%', '', $row);
					$row = str_replace('%categoria%', __($trabajo->fields['categoria']), $row);

					if ($this->fields['opc_ver_detalles_por_hora_tarifa'] == 1) {
						$row = str_replace('%td_tarifa%', '<td align="center">%tarifa%</td>', $row);
						$row = str_replace('%td_tarifa_ajustada%', '<td align="center">%tarifa_ajustada%</td>', $row);
					} else {
						$row = str_replace('%td_tarifa%', '', $row);
						$row = str_replace('%td_tarifa_ajustada%', '', $row);
					}
					if ($this->fields['forma_cobro'] == 'ESCALONADA') {
						$row = str_replace('%tarifa%', number_format(($total_trabajo_monto_cobrado / $duracion_cobrada_decimal), $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						$row = str_replace('%tarifa_ajustada%', number_format($trabajo->fields['tarifa_hh'] * $x_factor_ajuste, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					} else {
						$row = str_replace('%tarifa%', number_format($trabajo->fields['tarifa_hh'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						$row = str_replace('%tarifa_ajustada%', number_format($trabajo->fields['tarifa_hh'] * $x_factor_ajuste, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					}

					if ($this->fields['opc_ver_detalles_por_hora_importe'] == 1) {
						$row = str_replace('%td_importe%', '<td align="center">%importe%</td>', $row);
						$row = str_replace('%td_importe_ajustado%', '<td align="center">%importe_ajustado%</td>', $row);
					} else {
						$row = str_replace('%td_importe%', '', $row);
						$row = str_replace('%td_importe_ajustado%', '', $row);
					}
					if ($this->fields['forma_cobro'] == 'ESCALONADA') {
						$row = str_replace('%importe%', number_format($total_trabajo_monto_cobrado, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					} else {
						$row = str_replace('%importe%', number_format($total_trabajo_importe, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					}
					$row = str_replace('%importe_ajustado%', number_format($total_trabajo_importe * $x_factor_ajuste, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

					//paridad
					$row = str_replace('%paridad%', $i % 2 ? 'impar' : 'par', $row);

					//muestra las iniciales de los profesionales
					list($nombre, $apellido_paterno, $extra, $extra2) = explode(' ', $trabajo->fields['nombre_usuario'], 4);
					$row = str_replace('%iniciales%', $nombre[0] . $apellido_paterno[0] . $extra[0] . $extra2[0], $row);

					$ImprimirDuracionTrabajada = Conf::GetConf($this->sesion, 'ImprimirDuracionTrabajada');

					if ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') {
						$row = str_replace('%td_retainer%', '<td align="center">%duracion_retainer%</td>', $row);
						if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
							$row = str_replace('%duracion_retainer%', number_format($duracion_decimal_retainer, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $row);
						} else {
							$row = str_replace('%duracion_retainer%', $h_retainer . ':' . sprintf("%02d", $m_retainer), $row);
						}
					} else {
						$row = str_replace('%td_retainer%', '', $row);
					}

					if ($this->fields['forma_cobro'] == 'FLAT FEE') {
						$row = str_replace('%duracion_decimal_trabajada%', '', $row);
						$row = str_replace('%duracion_trabajada%', '', $row);
						$row = str_replace('%duracion_decduracion_trabajadaimal_descontada%', '', $row);
						$row = str_replace('%duracion_descontada%', '', $row);

						if (!$this->fields['opc_ver_horas_trabajadas']) {
							$row = str_replace('%duracion_decimal%', number_format($duracion_decimal, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
							if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
								$row = str_replace('%duracion%', number_format($duracion_decimal, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $row);
							} else {
								$row = str_replace('%duracion%', $h . ':' . sprintf("%02d", $m), $row);
							}
						} else {
							$row = str_replace('%duracion_decimal%', number_format($duracion_decimal_trabajada, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
							if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
								$row = str_replace('%duracion%', number_format($duracion_decimal_trabajada, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $row);
							} else {
								$row = str_replace('%duracion%', $ht . ':' . sprintf("%02d", $mt), $row);
							}
						}
					}
					if ($ImprimirDuracionTrabajada && ( $this->fields['estado'] == 'CREADO' || $this->fields['estado'] == 'EN REVISION' )) {
						$row = str_replace('%duracion_decimal_trabajada%', number_format($duracion_decimal_trabajada, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
							$row = str_replace('%duracion_trabajada%', number_format($duracion_decimal_trabajada, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $row);
							$row = str_replace('%duracion_descontada%', number_format($duracion_decimal_descontada, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $row);
						} else {
							$row = str_replace('%duracion_trabajada%', $ht . ':' . sprintf("%02d", $mt), $row);
							$row = str_replace('%duracion_descontada%', Utiles::Decimal2GlosaHora($duracion_decimal_descontada), $row);
						}
						$row = str_replace('%duracion_decimal_descontada%', number_format($duracion_decimal_descontada, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					} else if ($this->fields['opc_ver_horas_trabajadas']) {
						$row = str_replace('%duracion_decimal_trabajada%', number_format($duracion_decimal_trabajada, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
							$row = str_replace('%duracion_trabajada%', number_format($duracion_decimal_trabajada, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $row);
							$row = str_replace('%duracion_descontada%', number_format($duracion_decimal_descontada, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $row);
						} else {
							$row = str_replace('%duracion_trabajada%', $ht . ':' . sprintf("%02d", $mt), $row);
							$row = str_replace('%duracion_descontada%', Utiles::Decimal2GlosaHora($duracion_decimal_descontada), $row);
						}
						$row = str_replace('%duracion_decimal_descontada%', number_format($duracion_decimal_descontada, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					} else {
						$row = str_replace('%duracion_descontada%', '', $row);
						$row = str_replace('%duracion_decimal_descontada%', '', $row);
						$row = str_replace('%duracion_decimal_trabajada%', '', $row);
						$row = str_replace('%duracion_trabajada%', '', $row);
					}

					$row = str_replace('%duracion_decimal%', number_format($duracion_decimal, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
						$row = str_replace('%duracion%', number_format($duracion_decimal, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $row);
					} else {
						$row = str_replace('%duracion%', $h . ':' . $m, $row);
					}


					if ($this->fields['opc_ver_columna_cobrable']) {
						if ($trabajo->fields['cobrable'] == 1) {
							$row = str_replace('%cobrable%', __('<td align="center">Si</td>'), $row);
						} else {
							$row = str_replace('%cobrable%', __('<td align="center">No</td>'), $row);
						}
					} else {
						$row = str_replace('%cobrable%', __(''), $row);
					}

					$ImprimirValorTrabajo = Conf::GetConf($this->sesion, 'ImprimirValorTrabajo');

					if ($ImprimirValorTrabajo && $this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION') {
						$row = str_replace('%valor%', '', $row);
						$row = str_replace('%valor_cyc%', '', $row);
					} else {
						$row = str_replace('%valor%', number_format($trabajo->fields['monto_cobrado'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						$row = str_replace('%valor_con_moneda%', $moneda->fields['simbolo'] . number_format($trabajo->fields['monto_cobrado'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						$row = str_replace('%valor_cyc%', number_format($trabajo->fields['monto_cobrado'] * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					}
					$row = str_replace('%valor_siempre%', number_format($trabajo->fields['monto_cobrado'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

					if (Conf::GetConf($this->sesion, 'OrdenarPorCategoriaUsuario')) {
						$trabajo_siguiente = $lista_trabajos->Get($i + 1);
						if (!empty($trabajo_siguiente->fields['id_categoria_usuario'])) {
							if ($trabajo->fields['id_categoria_usuario'] != $trabajo_siguiente->fields['id_categoria_usuario']) {
								$html3 = $parser->tags['TRABAJOS_TOTAL'];
								$html3 = str_replace('%glosa%', __('Total'), $html3);
								$categoria_duracion_horas += floor($categoria_duracion_minutos / 60);
								$categoria_duracion_minutos = round($categoria_duracion_minutos % 60);
								$html3 = str_replace('%duracion%', sprintf('%02d:%02d', $categoria_duracion_horas, $categoria_duracion_minutos), $html3);
								$html3 = str_replace('%duracion_trabajada%', sprintf('%02d:%02d', floor($categoria_duracion_trabajada), round(($categoria_duracion_trabajada * 60) % 60)), $html3);
								$html3 = str_replace('%duracion_descontada%', sprintf('%02d:%02d', floor($categoria_duracion_descontada), round(($categoria_duracion_descontada * 60) % 60)), $html3);


								if (Conf::GetConf($this->sesion, 'NoImprimirValorTrabajo') && $this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION') {
									$html3 = str_replace('%valor%', '', $html3);
									$html3 = str_replace('%valor_cyc%', '', $html3);
								} else {
									$html3 = str_replace('%valor%', $moneda->fields['simbolo'] . $this->espacio . number_format($categoria_valor, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
									$html3 = str_replace('%valor_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($categoria_valor * ( $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
								}

								$total_trabajos_categoria .= $html3;

								// Permite a TRABAJOS_ENCABEZADO poner la categor�a correcta reutilizando la l�gica
								$this->siguiente['categoria_abogado'] = $trabajo_siguiente->fields['categoria'];
								$encabezado_trabajos_categoria .= $this->GenerarDocumento2($parser, 'TRABAJOS_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto);

								$row = str_replace('%TRABAJOS_CATEGORIA%', $total_trabajos_categoria . $encabezado_trabajos_categoria, $row);
								$categoria_duracion_horas = 0;
								$categoria_duracion_minutos = 0;
								$categoria_valor = 0;
								$total_trabajos_categoria = '';
								$encabezado_trabajos_categoria = '';
							} else {
								$row = str_replace('%TRABAJOS_CATEGORIA%', '', $row);
							}
						} else {
							$html3 = $parser->tags['TRABAJOS_TOTAL'];
							$html3 = str_replace('%glosa%', __('Total'), $html3);
							$categoria_duracion_horas += floor($categoria_duracion_minutos / 60);
							$categoria_duracion_minutos = round($categoria_duracion_minutos % 60);

							$html3 = str_replace('%duracion%', sprintf('%02d:%02d', $categoria_duracion_horas, $categoria_duracion_minutos), $html3);
							$html3 = str_replace('%duracion_trabajada%', sprintf('%02d:%02d', floor($categoria_duracion_trabajada), round(($categoria_duracion_trabajada * 60) % 60)), $html3);
							$html3 = str_replace('%duracion_descontada%', sprintf('%02d:%02d', floor($categoria_duracion_descontada), round(($categoria_duracion_descontada * 60) % 60)), $html3);

							if ($this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION' && Conf::GetConf($this->sesion, 'NoImprimirValorTrabajo')) {
								$html3 = str_replace('%valor%', '', $html3);
								$html3 = str_replace('%valor_cyc%', '', $html3);
							} else {
								$html3 = str_replace('%valor%', $moneda->fields['simbolo'] . $this->espacio . number_format($categoria_valor, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
								$html3 = str_replace('%valor_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($categoria_valor * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
							}

							$total_trabajos_categoria .= $html3;
							$row = str_replace('%TRABAJOS_CATEGORIA%', $total_trabajos_categoria, $row);
							$categoria_duracion_horas = 0;
							$categoria_duracion_minutos = 0;
							$categoria_valor = 0;
							$total_trabajos_categoria = '';
							$encabezado_trabajos_categoria = '';
						}
					} else if (Conf::GetConf($this->sesion, 'SepararPorUsuario')) {
						$trabajo_siguiente = $lista_trabajos->Get($i + 1);
						if (!empty($trabajo_siguiente->fields['nombre_usuario'])) {
							if ($trabajo->fields['nombre_usuario'] != $trabajo_siguiente->fields['nombre_usuario']) {
								$html3 = $parser->tags['TRABAJOS_TOTAL'];
								$html3 = str_replace('%glosa%', __('Subtotal'), $html3);
								$categoria_duracion_horas += floor($categoria_duracion_minutos / 60);
								$categoria_duracion_minutos = round($categoria_duracion_minutos % 60);
								$html3 = str_replace('%duracion%', sprintf('%02d:%02d', $categoria_duracion_horas, $categoria_duracion_minutos), $html3);

								if (Conf::GetConf($this->sesion, 'NoImprimirValorTrabajo') && $this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION') {
									$html3 = str_replace('%valor%', '', $html3);
									$html3 = str_replace('%valor_cyc%', '', $html3);
								} else {
									$html3 = str_replace('%valor%', $moneda->fields['simbolo'] . $this->espacio . number_format($categoria_valor, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
									$html3 = str_replace('%valor_cyc%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . $this->espacio . number_format($categoria_valor * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
								}

								if ($this->fields['opc_ver_detalles_por_hora_categoria'] == 1) {
									$html3 = str_replace('%td_categoria%', '<td>&nbsp;</td>', $html3);
								} else {
									$html3 = str_replace('%td_categoria%', '', $html3);
								}
								if ($this->fields['opc_ver_detalles_por_hora_tarifa'] == 1) {
									$html3 = str_replace('%td_tarifa%', '<td>&nbsp;</td>', $html3);
									$html3 = str_replace('%td_tarifa_ajustada%', '<td>&nbsp;</td>', $html3);
								} else {
									$html3 = str_replace('%td_tarifa%', '', $html3);
									$html3 = str_replace('%td_tarifa_ajustada%', '', $html3);
								}

								if ($this->fields['opc_ver_detalles_por_hora_importe'] == 1) {
									$html3 = str_replace('%td_importe%', '<td align="center">%importe%</td>', $html3);
								} else {
									$html3 = str_replace('%td_importe%', '', $html3);
								}

								if ($this->fields['opc_ver_horas_trabajadas'] == 1) {
									$html3 = str_replace('%duracion_trabajada%', sprintf('%02d:%02d', floor($categoria_duracion_trabajada), round(($categoria_duracion_trabajada * 60) % 60)), $html3);
									$html3 = str_replace('%duracion_descontada%', sprintf('%02d:%02d', floor($categoria_duracion_descontada), round(($categoria_duracion_descontada * 60) % 60)), $html3);
								} else {
									$html3 = str_replace('%duracion_trabajada%', '', $html3);
									$html3 = str_replace('%duracion_descontada%', '', $html3);
								}

								$html3 = str_replace('%importe%', number_format($categoria_valor, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);

								$total_trabajos_categoria .= $html3;

								// Permite a TRABAJOS_ENCABEZADO poner el nombre correcto reutilizando la l�gica
								$this->siguiente['nombre_usuario'] = $trabajo_siguiente->fields['nombre_usuario'];
								$this->siguiente['tarifa_usuario'] = $trabajo_siguiente->fields['tarifa_hh'];
								$encabezado_trabajos_categoria .= $this->GenerarDocumento2($parser, 'TRABAJOS_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto);

								$row = str_replace('%TRABAJOS_CATEGORIA%', $total_trabajos_categoria . $encabezado_trabajos_categoria, $row);
								$categoria_duracion_horas = 0;
								$categoria_duracion_minutos = 0;
								$categoria_duracion_trabajada = 0;
								$categoria_duracion_descontada = 0;
								$categoria_valor = 0;
								$total_trabajos_categoria = '';
								$encabezado_trabajos_categoria = '';
							} else {
								$row = str_replace('%TRABAJOS_CATEGORIA%', '', $row);
							}
						} else {
							$html3 = $parser->tags['TRABAJOS_TOTAL'];
							$html3 = str_replace('%glosa%', __('Subtotal'), $html3);
							$categoria_duracion_horas += floor($categoria_duracion_minutos / 60);
							$categoria_duracion_minutos = round($categoria_duracion_minutos % 60);

							$html3 = str_replace('%duracion%', sprintf('%02d:%02d', $categoria_duracion_horas, $categoria_duracion_minutos), $html3);

							if ($this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION' && Conf::GetConf($this->sesion, 'NoImprimirValorTrabajo')) {
								$html3 = str_replace('%valor%', '', $html3);
								$html3 = str_replace('%valor_cyc%', '', $html3);
							} else {
								$html3 = str_replace('%valor%', $moneda->fields['simbolo'] . $this->espacio . number_format($categoria_valor, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
								$html3 = str_replace('%valor_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($categoria_valor * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
							}

							if ($this->fields['opc_ver_detalles_por_hora_categoria'] == 1) {
								$html3 = str_replace('%td_categoria%', '<td>&nbsp;</td>', $html3);
							} else {
								$html3 = str_replace('%td_categoria%', '', $html3);
							}

							if ($this->fields['opc_ver_detalles_por_hora_tarifa'] == 1) {
								$html3 = str_replace('%td_tarifa%', '<td>&nbsp;</td>', $html3);
								$html3 = str_replace('%td_tarifa_ajustada%', '<td>&nbsp;</td>', $html3);
							} else {
								$html3 = str_replace('%td_tarifa%', '', $html3);
								$html3 = str_replace('%td_tarifa_ajustada%', '', $html3);
							}

							if ($this->fields['opc_ver_detalles_por_hora_importe'] == 1) {
								$html3 = str_replace('%td_importe%', '<td align="center">%importe%</td>', $html3);
								$html3 = str_replace('%td_importe_ajustado%', '<td align="center">%importe_ajustado%</td>', $html3);
							} else {
								$html3 = str_replace('%td_importe%', '', $html3);
								$html3 = str_replace('%td_importe_ajustado%', '', $html3);
							}

							if ($this->fields['opc_ver_horas_trabajadas'] == 1) {
								$html3 = str_replace('%duracion_trabajada%', sprintf('%02d:%02d', floor($categoria_duracion_trabajada), round(($categoria_duracion_trabajada * 60) % 60)), $html3);
								$html3 = str_replace('%duracion_descontada%', sprintf('%02d:%02d', floor($categoria_duracion_descontada), round(($categoria_duracion_descontada * 60) % 60)), $html3);
							} else {
								$html3 = str_replace('%duracion_trabajada%', '', $html3);
								$html3 = str_replace('%duracion_descontada%', '', $html3);
							}

							$html3 = str_replace('%importe%', number_format($categoria_valor, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);

							$total_trabajos_categoria .= $html3;
							$row = str_replace('%TRABAJOS_CATEGORIA%', $total_trabajos_categoria, $row);
							$categoria_duracion_horas = 0;
							$categoria_duracion_minutos = 0;
							$categoria_duracion_trabajada = 0;
							$categoria_duracion_descontada = 0;
							$categoria_valor = 0;
							$total_trabajos_categoria = '';
							$encabezado_trabajos_categoria = '';
						}
					} else {
						$row = str_replace('%TRABAJOS_CATEGORIA%', '', $row);
					}
					$html .= $row;
				}
				break;

			case 'TRABAJOS_TOTAL': //GenerarDocumento2
				if ($this->fields['estado'] == 'CREADO' || $this->fields['estado'] == 'EN REVISION') {
					$html = str_replace('%td_id_trabajo%', '<td align="center">%ntrabajo%</td>', $html);
				} else {
					$html = str_replace('%td_id_trabajo%', '', $html);
				}
				$html = str_replace('%ntrabajo%', __('&nbsp;'), $html);

				$ImprimirDuracionTrabajada = Conf::GetConf($this->sesion, 'ImprimirDuracionTrabajada');

				$duracion_trabajada_total = ($asunto->fields['trabajos_total_duracion_trabajada']) / 60;
				$duracion_cobrada_total = ($asunto->fields['trabajos_total_duracion']) / 60;
				$duracion_retainer_total = ($asunto->fields['trabajos_total_duracion_retainer']) / 60;
				$duracion_descontada_total = $duracion_trabajada_total - $duracion_cobrada_total;

				if ($this->fields['opc_ver_solicitante']) {
					$html = str_replace('%td_solicitante%', '<td>&nbsp;</td>', $html);
				} else {
					$html = str_replace('%td_solicitante%', '', $html);
				}
				if ($this->fields['opc_ver_detalles_por_hora_categoria'] == 1) {
					$html = str_replace('%td_categoria%', '<td>&nbsp;</td>', $html);
				} else {
					$html = str_replace('%td_categoria%', '', $html);
				}

				if ($this->fields['opc_ver_detalles_por_hora_tarifa'] == 1) {
					$html = str_replace('%td_tarifa%', '<td>&nbsp;</td>', $html);
					$html = str_replace('%td_tarifa_ajustada%', '<td>&nbsp;</td>', $html);
				} else {
					$html = str_replace('%td_tarifa%', '', $html);
					$html = str_replace('%td_tarifa_ajustada%', '', $html);
				}

				if ($this->fields['opc_ver_detalles_por_hora_importe'] == 1) {
					$html = str_replace('%td_importe%', '<td align="center">%importe%</td>', $html);
					$html = str_replace('%td_importe_ajustado%', '<td align="center">%importe_ajustado%</td>', $html);
				} else {
					$html = str_replace('%td_importe%', '', $html);
					$html = str_replace('%td_importe_ajustado%', '', $html);
				}
				$html = str_replace('%importe%', $moneda->fields['simbolo'] . $this->espacio . number_format($asunto->fields['trabajos_total_valor'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%importe_ajustado%', $moneda->fields['simbolo'] . $this->espacio . number_format($asunto->fields['trabajos_total_importe'] * $x_factor_ajuste, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				if ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') {
					$html = str_replace('%td_retainer%', '<td align="center">%duracion_retainer%</td>', $html);
					if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
						$html = str_replace('%duracion_retainer%', number_format($duracion_retainer_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html);
					} else {
						$html = str_replace('%duracion_retainer%', Utiles::Decimal2GlosaHora($duracion_retainer_total), $html);
					}
				} else {
					$html = str_replace('%td_retainer%', '', $html);
				}

				if ($this->fields['forma_cobro'] == 'FLAT FEE') {
					$html = str_replace('%duracion_decimal_trabajada%', '', $html);
					$html = str_replace('%duracion_trabajada%', '', $html);
					$html = str_replace('%duracion_descontada%', '', $html);
					$html = str_replace('%duracion_decimal_descontada%', '', $html);
					if ($this->fields['opc_ver_horas_trabajadas']) {
						$html = str_replace('%duracion_decimal%', number_format($duracion_trabajada_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
						if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
							$html = str_replace('%duracion%', number_format($duracion_trabajada_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html);
						} else {
							$html = str_replace('%duracion%', Utiles::Decimal2GlosaHora($duracion_trabajada_total), $html);
						}
					} else {
						$html = str_replace('%duracion_decimal%', number_format($duracion_cobrada_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
						if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
							$html = str_replace('%duracion%', number_format($duracion_cobrada_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html);
						} else {
							$html = str_replace('%duracion%', Utiles::Decimal2GlosaHora($duracion_cobrada_total), $html);
						}
					}
				}
				if ($ImprimirDuracionTrabajada && ( $this->fields['estado'] == 'CREADO' || $this->fields['estado'] == 'EN REVISION' )) {
					$html = str_replace('%duracion_decimal_trabajada%', number_format($duracion_trabajada_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					$html = str_replace('%duracion_decimal_descontada%', number_format($duracion_descontada_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
						$html = str_replace('%duracion_trabajada%', number_format($duracion_trabajada_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html);
						$html = str_replace('%duracion_descontada%', number_format($duracion_descontada_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html);
					} else {
						$html = str_replace('%duracion_trabajada%', Utiles::Decimal2GlosaHora($duracion_trabajada_total), $html);
						$html = str_replace('%duracion_descontada%', Utiles::Decimal2GlosaHora($duracion_descontada_total), $html);
					}
				} else if ($this->fields['opc_ver_horas_trabajadas']) {
					$html = str_replace('%duracion_decimal_trabajada%', number_format($duracion_trabajada_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					$html = str_replace('%duracion_decimal_descontada%', number_format($duracion_descontada_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
						$html = str_replace('%duracion_trabajada%', number_format($duracion_trabajada_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html);
						$html = str_replace('%duracion_descontada%', number_format($duracion_descontada_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html);
					} else {
						$html = str_replace('%duracion_trabajada%', Utiles::Decimal2GlosaHora($duracion_trabajada_total), $html);
						$html = str_replace('%duracion_descontada%', Utiles::Decimal2GlosaHora($duracion_descontada_total), $html);
					}
				} else {
					$html = str_replace('%duracion_decimal_trabajada%', '', $html);
					$html = str_replace('%duracion_trabajada%', '', $html);
					$html = str_replace('%duracion_descontada%', '', $html);
					$html = str_replace('%duracion_decimal_descontada%', '', $html);
				}

				$html = str_replace('%glosa%', __('Total Trabajos'), $html);
				$html = str_replace('%duracion_decimal%', number_format($duracion_cobrada_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
					$html = str_replace('%duracion%', number_format($duracion_cobrada_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html);
				} else {
					$html = str_replace('%duracion%', Utiles::Decimal2GlosaHora($duracion_cobrada_total), $html);
				}


				if ($this->fields['opc_ver_columna_cobrable'] == 1) {
					$html = str_replace('%cobrable%', __('<td>&nbsp;</td>'), $html);
				} else {
					$html = str_replace('%cobrable%', __(''), $html);
				}

				$ImprimirValorTrabajo = Conf::GetConf($this->sesion, 'ImprimirValorTrabajo');

				$aproximacion_monto_cyc = number_format($this->fields['monto_subtotal'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
				$subtotal_en_moneda_cyc = $aproximacion_monto_cyc * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']);

				if ($ImprimirValorTrabajo && $this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION') {
					$html = str_replace('%valor%', '', $html);
					$html = str_replace('%valor_cyc%', '', $html);
				} else {
					$html = str_replace('%valor_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($subtotal_en_moneda_cyc, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					$html = str_replace('%valor%', $moneda->fields['simbolo'] . $this->espacio . number_format($asunto->fields['trabajos_total_valor'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				}
				$html = str_replace('%valor_siempre%', $moneda->fields['simbolo'] . $this->espacio . number_format($asunto->fields['trabajos_total_valor'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%total_raz%', __('total_raz'), $html);

				break;

			case 'DETALLE_PROFESIONAL': //GenerarDocumento2
				global $columna_hrs_retainer;
				if ($this->fields['opc_ver_profesional'] == 0) {
					return '';
				}
				$html = str_replace('%glosa_profesional%', __('Detalle profesional'), $html);
				$html = str_replace('%detalle_tiempo_por_abogado%', __('Detalle tiempo por abogado'), $html);
				$html = str_replace('%detalle_honorarios%', __('Detalle de honorarios profesionales'), $html);
				$html = str_replace('%PROFESIONAL_ENCABEZADO%', $this->GenerarSeccionResumenProfesional($parser, 'PROFESIONAL_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%PROFESIONAL_FILAS%', $this->GenerarDocumento2($parser, 'PROFESIONAL_FILAS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%PROFESIONAL_TOTAL%', $this->GenerarDocumento2($parser, 'PROFESIONAL_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);

				if ($this->fields['opc_ver_descuento']) {
					$html = str_replace('%DETALLE_COBRO_DESCUENTO%', $this->GenerarDocumento2($parser, 'DETALLE_COBRO_DESCUENTO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				} else {
					$html = str_replace('%DETALLE_COBRO_DESCUENTO%', '', $html);
				}

				if (count($this->asuntos) > 1) {
					$html = str_replace('%DETALLE_COBRO_MONEDA_TOTAL_POR_ASUNTO%', $this->GenerarDocumentoComun($parser, 'DETALLE_COBRO_MONEDA_TOTAL_POR_ASUNTO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
					$html = str_replace('%DETALLE_COBRO_MONEDA_TOTAL%', '', $html);
				} else {
					$html = str_replace('%DETALLE_COBRO_MONEDA_TOTAL%', $this->GenerarDocumentoComun($parser, 'DETALLE_COBRO_MONEDA_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
					$html = str_replace('%DETALLE_COBRO_MONEDA_TOTAL_POR_ASUNTO%', '', $html);
				}
				break;

			case 'IMPUESTO': //GenerarDocumento2
				if ($this->fields['porcentaje_impuesto'] > 0 && $this->fields['porcentaje_impuesto_gastos'] > 0 && $this->fields['porcentaje_impuesto'] != $this->fields['porcentaje_impuesto_gastos'])
					$html = str_replace('%impuesto%', __('Impuesto') . ' (' . $this->fields['porcentaje_impuesto'] . '% / ' . $this->fields['porcentaje_impuesto_gastos'] . '% )', $html);
				else if ($this->fields['porcentaje_impuesto'] > 0)
					$html = str_replace('%impuesto%', __('Impuesto') . ' (' . $this->fields['porcentaje_impuesto'] . '%)', $html);
				else if ($this->fields['porcentaje_impuesto_gastos'] > 0)
					$html = str_replace('%impuesto%', __('Impuesto') . ' (' . $this->fields['porcentaje_impuesto_gastos'] . '%)', $html);
				else
					$html = str_replace('%impuesto%', '', $html);

				$html = str_replace('%impuesto_mta%', __('Impuesto') . ' (' . $this->fields['porcentaje_impuesto'] . '%)', $html);

				$impuesto_moneda_total = $x_resultados['monto_iva'][$this->fields['opc_moneda_total']];

				$html = str_replace('%valor_impuesto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($impuesto_moneda_total, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				// Mu�oz y Tamayo
				$impuesto_solo_honorarios = $x_resultados['monto_iva_hh'][$this->fields['opc_moneda_total']];
				$html = str_replace('%valor_impuesto_honorarios%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($impuesto_solo_honorarios, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				break;

			case 'ADELANTOS_FILAS': //GenerarDocumento2
				$saldo = 0;
				$moneda = $cobro_moneda->moneda[$this->fields['opc_moneda_total']];

				//Adelantos
				$query = "
				SELECT documento.glosa_documento, documento.saldo_pago, prm_moneda.tipo_cambio
				FROM documento
				LEFT JOIN prm_moneda ON prm_moneda.id_moneda = documento.id_moneda
				WHERE documento.codigo_cliente = '" . $this->fields['codigo_cliente'] . "' AND documento.es_adelanto = 1 AND documento.saldo_pago < 0";
				$adelantos = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				while ($adelanto = mysql_fetch_assoc($adelantos)) {
					$fila_adelanto_ = str_replace('%descripcion%', $adelanto['glosa_documento'], $html);
					$fila_adelanto_ = str_replace('%saldo_pago%', $moneda['simbolo'] . $adelanto['saldo_pago'] * $adelanto['tipo_cambio'] / $moneda['tipo_cambio'], $fila_adelanto_);
					$saldo += (int) $adelanto['saldo_pago'];
					$fila_adelantos .= $fila_adelanto_;
				}

				$fila_adelantos .= '<tr><td colspan="2">&nbsp;</td></tr>';

				//Pagos
				$query = "
				SELECT documento.glosa_documento, IF(documento.saldo_pago = 0, 0, (documento.saldo_pago * -1)) AS saldo_pago, prm_moneda.tipo_cambio
				FROM documento
				LEFT JOIN prm_moneda ON prm_moneda.id_moneda = documento.id_moneda
				WHERE documento.codigo_cliente = '" . $this->fields['codigo_cliente'] . "' AND documento.es_adelanto <> 1 AND documento.tipo_doc NOT IN ('N') AND documento.saldo_pago > 0";
				$adelantos = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				while ($adelanto = mysql_fetch_assoc($adelantos)) {
					$fila_adelanto_ = str_replace('%descripcion%', $adelanto['glosa_documento'], $html);
					$fila_adelanto_ = str_replace('%saldo_pago%', $moneda['simbolo'] . $adelanto['saldo_pago'] * $adelanto['tipo_cambio'] / $moneda['tipo_cambio'], $fila_adelanto_);
					$saldo += (int) $adelanto['saldo_pago'];
					$fila_adelantos .= $fila_adelanto_;
				}

				$fila_adelantos .= '<tr><td colspan="2">&nbsp;</td></tr>';

				//Deuda
				$query = "
				SELECT documento.glosa_documento, ( documento.saldo_honorarios + documento.saldo_gastos ) * cm1.tipo_cambio / cm2.tipo_cambio AS saldo_cobro
				FROM documento
				LEFT JOIN cobro_moneda as cm1 ON cm1.id_cobro = documento.id_cobro AND cm1.id_moneda = documento.id_moneda
				LEFT JOIN cobro_moneda as cm2 ON cm2.id_cobro = '" . $this->fields['id_cobro'] . "' AND cm2.id_moneda = '" . $this->fields['opc_moneda_total'] . "'
				WHERE documento.codigo_cliente = '" . $this->fields['codigo_cliente'] . "' AND documento.es_adelanto <> 1 AND documento.tipo_doc = 'N' AND documento.saldo_honorarios + documento.saldo_gastos > 0 AND documento.id_cobro <> " . $this->fields['id_cobro'];
				$adelantos = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				while ($adelanto = mysql_fetch_assoc($adelantos)) {
					$fila_adelanto_ = str_replace('%descripcion%', $adelanto['glosa_documento'], $html);
					$fila_adelanto_ = str_replace('%saldo_pago%', $moneda['simbolo'] . $adelanto['saldo_cobro'], $fila_adelanto_);
					$saldo += (int) $adelanto['saldo_cobro'];
					$fila_adelantos .= $fila_adelanto_;
				}

				$fila_adelantos .= '<tr><td colspan="2">&nbsp;</td></tr>';
				$fila_adelantos .= '<tr class="tr_total"><td>' . __('Total por pagar') . '</td><td align="right">' . $moneda['simbolo'] . $saldo . '</td></tr>';

				$html = $fila_adelantos;
				break;

			case 'PROFESIONAL_FILAS': //GenerarDocumento2
				$row_tmpl = $html;
				$html = '';
				if (is_array($x_detalle_profesional[$asunto->fields['codigo_asunto']])) {
					$retainer = false;
					$descontado = false;
					$flatfee = false;

					if (is_array($x_resumen_profesional)) {
						foreach ($x_resumen_profesional as $data) {
							if ($data['duracion_retainer'] > 0 && ( $this->fields['forma_cobro'] != 'FLAT FEE' || Conf::GetConf($this->sesion, 'ResumenProfesionalVial'))) {
								$retainer = true;
							}
							if (( $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL' ) && Conf::GetConf($this->sesion, 'ResumenProfesionalVial')) {
								$retainer = true;
							}
							if ($data['duracion_incobrables'] > 0)
								$descontado = true;
							if ($data['flatfee'] > 0 || $this->fields['forma_cobro'] == 'FLAT FEE')
								$flatfee = true;
						}
					}

					$totales['tiempo_retainer'] = 0;
					$totales['tiempo_trabajado'] = 0;
					$totales['tiempo_trabajado_real'] = 0;
					$totales['tiempo'] = 0;
					$totales['tiempo_flatfee'] = 0;
					$totales['tiempo_descontado'] = 0;
					$totales['tiempo_descontado_real'] = 0;
					$totales['valor_total'] = 0;

					foreach ($x_detalle_profesional[$asunto->fields['codigo_asunto']] as $prof => $data) {
						// Para mostrar un resumen de horas de cada profesional al principio del documento.
						$row = $row_tmpl;
						$totales['valor'] += $data['valor_tarificada'];
						$totales['tiempo_retainer'] += 60 * $data['duracion_retainer'];
						$totales['tiempo_trabajado'] += 60 * $data['duracion_cobrada'];
						$totales['tiempo_trabajado_real'] += 60 * $data['duracion_trabajada'];
						$totales['tiempo'] += 60 * $data['duracion_tarificada'];
						$totales['tiempo_flatfee'] += 60 * $data['flatfee'];
						$totales['tiempo_descontado'] += 60 * $data['duracion_incobrables'];
						$totales['tiempo_descontado_real'] += 60 * $data['duracion_descontada'];
						if ($this->fields['forma_cobro'] == 'ESCALONADA') {
							$totales['valor_total'] += $data['monto_cobrado_escalonada'];
						} else {
							$totales['valor_total'] += $data['valor_tarificada'];
						}

						if ($this->fields['opc_ver_profesional_iniciales'] == 1)
							$row = str_replace('%nombre_siglas%', $data['username'], $row);
						else
							$row = str_replace('%nombre_siglas%', $data['nombre_usuario'], $row);
						$row = str_replace('%nombre%', $data['nombre_usuario'], $row);
						$row = str_replace('%username%', $data['username'], $row);

						if (!$asunto->fields['cobrable']) {
							$row = str_replace('%hrs_retainer%', '', $row);
							$row = str_replace('%hrs_descontadas%', '', $row);
							$row = str_replace('%hh%', '', $row);
							$row = str_replace('%valor_hh%', '', $row);
							$row = str_replace('%valor_hh_cyc%', '', $row);
						}

						if ($this->fields['opc_ver_horas_trabajadas']) {
							$row = str_replace('%hh_trabajada%', $data['glosa_duracion_trabajada'], $row);
							$row = str_replace('%hrs_trabajadas%', $data['glosa_duracion_trabajada'], $row);
							if ($descontado) {
								$row = str_replace('%td_descontada%', '<td align=\'center\'>%hh_descontada%</td>', $row);
								$row = str_replace('%hh_descontada%', $data['glosa_duracion_descontada'], $row);
							} else {
								$row = str_replace('%td_descontada%', '', $row);
								$row = str_replace('%hh_descontada%', '', $row);
							}
						} else {
							$row = str_replace('%td_descontada%', '', $row);
							$row = str_replace('%hh_trabajada%', '', $row);
							$row = str_replace('%hh_descontada%', '', $row);
						}
						if ($retainer || $flatfee) {
							$row = str_replace('%td_cobrable%', '<td align=\'center\'>%hh_cobrable%</td>', $row);
							$row = str_replace('%hh_cobrable%', $data['glosa_duracion_cobrada'], $row);
							if ($retainer) {
								$row = str_replace('%td_retainer%', '<td align=\'center\'>%hh_retainer%</td>', $row);
								$row = str_replace('%hh_retainer%', $data['glosa_duracion_retainer'], $row);
							} else {
								$row = str_replace('%td_retainer%', '', $row);
								$row = str_replace('%hh_retainer%', '', $row);
							}
						} else {
							$row = str_replace('%td_cobrable%', '', $row);
							$row = str_replace('%td_retainer%', '', $row);
							$row = str_replace('%hh_cobrable%', '', $row);
							$row = str_replace('%hh_retainer%', '', $row);
						}
						$row = str_replace('%hh_demo%', $data['glosa_duracion_tarificada'], $row);

						if ($this->fields['opc_ver_profesional_tarifa'] == 1) {
							$row = str_replace('%td_tarifa%', '<td align="center">%tarifa_horas_demo%</td>', $row);
							$row = str_replace('%tarifa_horas_demo%', number_format($data['tarifa'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
							$row = str_replace('%td_monto_tarifa%', '<td align="center">%monto_tarifa_horas%</td>', $row);
						} else {
							$row = str_replace('%td_tarifa%', '', $row);
							$row = str_replace('%tarifa_horas_demo%', '', $row);
							$row = str_replace('%td_monto_tarifa%', '', $row);
						}

						if ($this->fields['opc_ver_profesional_importe'] == 1) {
							$row = str_replace('%td_importe%', '<td align="right">%total_horas_demo%</td>', $row);
							$row = str_replace('%total_horas_demo%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . $this->espacio . number_format($data['valor_tarificada'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						} else {
							$row = str_replace('%td_importe%', '', $row);
							$row = str_replace('%total_horas_demo%', '', $row);
						}

						if (!$asunto->fields['cobrable']) {
							$row = str_replace('%hrs_retainer%', '', $row);
							$row = str_replace('%hrs_descontadas%', '', $row);
							$row = str_replace('%hh%', '', $row);
							$row = str_replace('%valor_hh%', '', $row);
							$row = str_replace('%valor_hh_cyc%', '', $row);
						}

						//muestra las iniciales de los profesionales
						list($nombre, $apellido_paterno, $extra) = explode(' ', $date['nombre_usuario'], 3);
						$row = str_replace('%iniciales%', $nombre[0] . $apellido_paterno[0] . $extra[0], $row);

						if ($descontado || $retainer || $flatfee) {
							if ($this->fields['opc_ver_horas_trabajadas']) {
								$row = str_replace('%hrs_trabajadas_real%', $data['glosa_duracion_trabajada'], $row);
								$row = str_replace('%hrs_descontadas_real%', $data['glosa_duracion_descontada'], $row);
							} else {
								$row = str_replace('%hrs_trabajadas_real%', '', $row);
								$row = str_replace('%hrs_descontadas_real%', '', $row);
							}
							$row = str_replace('%hrs_trabajadas%', $data['glosa_duracion_cobrada'], $row);
						} else if ($this->fields['opc_ver_horas_trabajadas']) {
							$row = str_replace('%hrs_trabajadas_real%', $data['glosa_duracion_trabajada'], $row);
							$row = str_replace('%hrs_trabajadas%', $data['glosa_duracion_cobrada'], $row);
							$row = str_replace('%hrs_descontadas_real%', $data['glosa_duracion_descontada'], $row);
						} else {
							$row = str_replace('%hrs_trabajadas%', '', $row);
							$row = str_replace('%hrs_trabajadas_real%', '', $row);
						}
						if ($retainer) {
							if ($data['duracion_retainer'] > 0) {
								if ($this->fields['forma_cobro'] == 'PROPORCIONAL')
									$row = str_replace('%hrs_retainer%', floor($data['duracion_retainer']) . ':' . sprintf('%02d', floor(( floor($data['duracion_retainer']) - $data['duracion_retainer']) * 60)) . ':' . sprintf('%02d', round(( floor($data['duracion_retainer']) - $data['duracion_retainer']) * 3600)), $row);
								else // retainer simple, no imprime segundos
									$row = str_replace('%hrs_retainer%', $data['glosa_duracion_retainer'], $row);
								$row = str_replace('%horas_retainer%', number_format($data['duracion_retainer'], 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
							}
							else {
								$row = str_replace('%hrs_retainer%', '-', $row);
								$row = str_replace('%horas_retainer%', '', $row);
							}
						} else {
							if ($flatfee) {
								if ($data['flatfee'] > 0)
									$row = str_replace('%hrs_retainer%', $data['flatfee'], $row);
								else
									$row = str_replace('%hrs_retainer%', '', $row);
							}
							$row = str_replace('%hrs_retainer%', '', $row);
							$row = str_replace('%horas_retainer%', '', $row);
						}
						if ($descontado) {
							$row = str_replace('%columna_horas_no_cobrables%', '<td align="center" width="65">%hrs_descontado%</td>', $row);
							if ($data['duracion_incobrables'] > 0)
								$row = str_replace('%hrs_descontadas%', $data['glosa_duracion_incobrables'], $row);
							else
								$row = str_replace('%hrs_descontadas%', '-', $row);
							if ($data['duracion_descontada'] > 0)
								$row = str_replace('%hrs_descontadas_real%', $data['glosa_duracion_descontada'], $row);
							else
								$row = str_replace('hrs_descontadas_real%', '-', $row);
						}
						else {
							$row = str_replace('%columna_horas_no_cobrables%', '', $row);
							$row = str_replace('%hrs_descontadas_real%', '', $row);
							$row = str_replace('%hrs_descontadas%', '', $row);
						}
						if ($flatfee) {
							$row = str_replace('%hh%', '0:00', $row);
						} else if ($this->fields['forma_cobro'] == 'ESCALONADA') {
							$row = str_replace('%hh%', $data['glosa_duracion_cobrada'], $row);
						} else {
							$row = str_replace('%hh%', $data['glosa_duracion_tarificada'], $row);
						}

						$row = str_replace('%valor_hh%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . $this->espacio . number_format($data['tarifa'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

						if ($this->fields['forma_cobro'] == 'ESCALONADA') {
							$row = str_replace('%valor_hh_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format(($data['monto_cobrado_escalonada'] / $data['duracion_cobrada']) * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						} else {
							$row = str_replace('%valor_hh_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($data['tarifa'] * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						}

						$row = str_replace('%total%', $moneda->fields['simbolo'] . number_format($data['valor_tarificada'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

						if ($this->fields['forma_cobro'] == 'ESCALONADA') {
							$row = str_replace('%total_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($data['monto_cobrado_escalonada'] * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						} else {
							$row = str_replace('%total_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($data['valor_tarificada'] * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						}

						$row = str_replace('%hrs_trabajadas_previo%', '', $row);
						$row = str_replace('%horas_trabajadas_especial%', '', $row);
						$row = str_replace('%horas_cobrables%', '', $row);

						if ($this->fields['opc_ver_profesional_categoria'] == 1) {
							$row = str_replace('%categoria%', __($data['glosa_categoria']), $row);
						} else {
							$row = str_replace('%categoria%', '', $row);
						}

						if ($this->fields['forma_cobro'] == 'FLAT FEE') {
							$row = str_replace('%horas%', number_format($data['duracion_cobrada'], 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						} else {
							$row = str_replace('%horas%', number_format($data['duracion_tarificada'], 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						}

						$row = str_replace('%tarifa_horas%', $flatfee ? '' : number_format($data['tarifa'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						$row = str_replace('%total_horas%', $flatfee ? '' : number_format($data['valor_tarificada'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

						if ($this->fields['opc_ver_horas_trabajadas'] && $data['duracion_trabajada'] && $data['duracion_trabajada'] != '0:00') {
							$html .= $row;
						} else if ($data['duracion_cobrada'] && $data['duracion_cobrada'] != '0:00') {
							$html .= $row;
						}
					}
				}

				break;

			case 'PROFESIONAL_TOTAL': //GenerarDocumento2
				$retainer = false;
				$descontado = false;
				$flatfee = false;

				if (is_array($x_resumen_profesional)) {
					foreach ($x_resumen_profesional as $data) {
						if ($data['duracion_retainer'] > 0 && ($this->fields['forma_cobro'] != 'FLAT FEE' || Conf::GetConf($this->sesion, 'ResumenProfesionalVial'))) {
							$retainer = true;
						}
						if (($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') && Conf::GetConf($this->sesion, 'ResumenProfesionalVial')) {
							$retainer = true;
						}
						if ($data['duracion_descontada'] > 0)
							$descontado = true;
						if ($data['flatfee'] > 0)
							$flatfee = true;
					}
				}

				if ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') {
					$html = str_replace('%DETALLE_PROFESIONAL_RETAINER%', $this->GenerarDocumento2($parser, 'DETALLE_PROFESIONAL_RETAINER', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				} else {
					$html = str_replace('%DETALLE_PROFESIONAL_RETAINER%', '', $html);
				}

				if (!$asunto->fields['cobrable']) {
					$html = str_replace('%hh_trabajada%', '', $html);
					$html = str_replace('%hh_descontada%', '', $html);
					$html = str_replace('%hh_cobrable%', '', $html);
					$html = str_replace('%hh_retainer%', '', $html);
					$html = str_replace('%hh_demo%', '', $html);
					$html = str_replace('%valor_hh%', '', $html);
					$html = str_replace('%valor_hh_cyc%', '', $html);
					$html = str_replace('%hh%', '', $html);
					$html = str_replace('%hrs_retainer%', '', $html);
					$html = str_replace('%hrs_descontadas%', '', $html);
					$html = str_replace('%hrs_descontadas_real%', '', $html);
				}

				$horas_cobrables = floor(($totales['tiempo']) / 60);
				$minutos_cobrables = sprintf("%02d", round($totales['tiempo']) % 60);
				$segundos_cobrables = round(60 * ($totales['tiempo'] - floor($totales['tiempo'])));
				$horas_trabajadas = floor(($totales['tiempo_trabajado']) / 60);
				$minutos_trabajadas = sprintf("%02d", round($totales['tiempo_trabajado']) % 60);
				$horas_trabajadas_real = floor(($totales['tiempo_trabajado_real']) / 60);
				$minutos_trabajadas_real = sprintf("%02d", round($totales['tiempo_trabajado_real']) % 60);
				#RETAINER
				$horas_retainer = floor(($totales['tiempo_retainer']) / 60);
				$minutos_retainer = sprintf("%02d", round($totales['tiempo_retainer'] % 60));
				$segundos_retainer = sprintf("%02d", round(60 * ($totales['tiempo_retainer'] - floor($totales['tiempo_retainer']))));
				$horas_flatfee = floor(($totales['tiempo_flatfee']) / 60);
				$minutos_flatfee = sprintf("%02d", round($totales['tiempo_flatfee']) % 60);
				$horas_descontado = floor(($totales['tiempo_descontado']) / 60);
				$minutos_descontado = sprintf("%02d", round($totales['tiempo_descontado']) % 60);
				$horas_descontado_real = floor(($totales['tiempo_descontado_real']) / 60);
				$minutos_descontado_real = sprintf("%02d", round($totales['tiempo_descontado_real']) % 60);

				$html = str_replace('%glosa%', __('Total'), $html);
				$html = str_replace('%glosa_honorarios%', __('Total Honorarios'), $html);

				if ($this->fields['opc_ver_horas_trabajadas']) {
					$html = str_replace('%hh_trabajada%', $horas_trabajadas_real . ':' . $minutos_trabajadas_real, $html);
					if ($descontado) {
						$html = str_replace('%td_descontada%', '<td align=\'center\'>%hh_descontada%</td>', $html);
						$html = str_replace('%hh_descontada%', Utiles::Decimal2GlosaHora($totales['tiempo_descontado'] / 60), $html);
						$html = str_replace('%hrs_descontadas%', Utiles::Decimal2GlosaHora($totales['tiempo_descontado'] / 60), $html);
					} else {
						$html = str_replace('%td_descontada%', '', $html);
						$html = str_replace('%hh_descontada%', '', $html);
						$html = str_replace('%hrs_descontadas%', '', $html);
					}
				} else {
					$html = str_replace('%td_descontada%', '', $html);
					$html = str_replace('%hh_trabajada%', '', $html);
					$html = str_replace('%hh_descontada%', '', $html);
					$html = str_replace('%hrs_descontadas%', '', $html);
				}

				if ($retainer || $flatfee) {
					$html = str_replace('%td_cobrable%', '<td align=\'center\'>%hh_cobrable%</td>', $html);
					$html = str_replace('%hh_cobrable%', $horas_trabajadas . ':' . $minutos_trabajadas, $html);
					if ($retainer) {
						$html = str_replace('%td_retainer%', '<td align=\'center\'>%hh_retainer%</td>', $html);
						$html = str_replace('%hh_retainer%', $horas_retainer . ':' . $minutos_retainer, $html);
					} else {
						$html = str_replace('%td_retainer%', '', $html);
						$html = str_replace('%hh_retainer%', '', $html);
					}
				} else {
					$html = str_replace('%td_cobrable%', '', $html);
					$html = str_replace('%td_retainer%', '', $html);
					$html = str_replace('%hh_cobrable%', '', $html);
					$html = str_replace('%hh_retainer%', '', $html);
				}

				$html = str_replace('%hh_demo%', $horas_cobrables . ':' . $minutos_cobrables, $html);
				$html = str_replace('%td_tarifa%', '<td>&nbsp;</td>', $html);

				if ($this->fields['opc_ver_profesional_importe'] == 1) {
					$html = str_replace('%td_importe%', '<td align="right">%total_horas_demo%</td>', $html);
					$html = str_replace('%total_horas_demo%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . $this->espacio . number_format($totales['valor_total'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				} else {
					$html = str_replace('%td_importe%', '', $html);
					$html = str_replace('%total_horas_demo%', '', $html);
				}

				if ($this->fields['opc_ver_profesional_importe'] == 1) {
					$html = str_replace('%td_importe%', '<td>&nbsp;</td>', $html);
				} else {
					$html = str_replace('%td_importe%', '', $html);
				}

				if ($descontado || $retainer || $flatfee) {
					if ($this->fields['opc_ver_horas_trabajadas']) {
						$html = str_replace('%hrs_trabajadas_real%', $horas_trabajadas_real . ':' . $minutos_trabajadas_real, $html);
						$html = str_replace('%hrs_descontadas_real%', Utiles::Decimal2GlosaHora($totales['tiempo_descontado_real'] / 60), $html);
					} else {
						$html = str_replace('%hrs_trabajadas_real%', '', $html);
						$html = str_replace('%hrs_descontadas_real%', '', $html);
					}
					$html = str_replace('%hrs_trabajadas%', $horas_trabajadas . ':' . $minutos_trabajadas, $html);
				} else if ($this->fields['opc_ver_horas_trabajadas']) {
					$html = str_replace('%hrs_trabajadas%', $horas_trabajadas . ':' . $minutos_trabajadas, $html);
					$html = str_replace('%hrs_trabajadas_real%', $horas_trabajadas_real . ':' . $minutos_trabajadas_real, $html);
					$html = str_replace('%hrs_descontadas_real%', Utiles::Decimal2GlosaHora($totales['tiempo_descontado_real'] / 60), $html);
				} else {
					$html = str_replace('%hrs_trabajadas%', '', $html);
					$html = str_replace('%hrs_trabajadas_real%', '', $html);
				}

				$html = str_replace('%hrs_trabajadas_previo%', '', $html);
				$html = str_replace('%horas_trabajadas_especial%', '', $html);
				$html = str_replace('%horas_cobrables%', '', $html);

				if ($retainer) {
					if ($this->fields['forma_cobro'] == 'PROPORCIONAL') {
						$minutos_retainer_redondeados = sprintf("%02d", $minutos_retainer + round($segundos_retainer / 60));
						$html = str_replace('%hrs_retainer%', $horas_retainer . ':' . $minutos_retainer_redondeados, $html);
					} else {// retainer simple, no imprime segundos
						$html = str_replace('%hrs_retainer%', $horas_retainer . ':' . $minutos_retainer, $html);
					}
					$minutos_retainer_decimal = $minutos_retainer / 60;
					$duracion_retainer_decimal = $horas_retainer + $minutos_retainer_decimal;
					$html = str_replace('%horas_retainer%', number_format($duracion_retainer_decimal, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				} else {
					$html = str_replace('%horas_retainer%', '', $html);
					if ($flatfee) {
						$html = str_replace('%hrs_retainer%', $horas_flatfee . ':' . $minutos_flatfee, $html);
					} else {
						$html = str_replace('%hrs_retainer%', '', $html);
					}
				}

				if ($descontado) {
					$html = str_replace('%columna_horas_no_cobrables%', '<td align="center" width="65">%hrs_descontadas%</td>', $html);
					$html = str_replace('%hrs_descontadas_real%', Utiles::Decimal2GlosaHora($totales['tiempo_descontado_real'] / 60), $html);
					$html = str_replace('%hrs_descontadas%', $horas_descontado . ':' . $minutos_descontado, $html);
				} else {
					$html = str_replace('%columna_horas_no_cobrables%', '', $html);
					$html = str_replace('%hrs_descontadas_real%', '', $html);
					$html = str_replace('%hrs_descontadas%', '', $html);
				}

				if ($flatfee) {
					$html = str_replace('%hh%', '0:00', $html);
				} else if ($this->fields['forma_cobro'] == 'PROPORCIONAL') {
					$minutos_cobrables_redondeados = sprintf("%02d", $minutos_cobrables + round($segundos_cobrables / 60));
					$html = str_replace('%hh%', "$horas_cobrables:$minutos_cobrables_redondeados", $html);
				} else if ($this->fields['forma_cobro'] == 'ESCALONADA') {
					$html = str_replace('%hh%', $horas_trabajadas . ':' . sprintf("%02d", $minutos_trabajadas), $html);
				} else { // retainer simple, no imprime segundos
					$html = str_replace('%hh%', $horas_cobrables . ':' . sprintf("%02d", $minutos_cobrables), $html);
				}

				$aproximacion_monto_cyc = number_format($this->fields['monto_subtotal'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
				$subtotal_en_moneda_cyc = $aproximacion_monto_cyc * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']);

				$html = str_replace('%total%', $moneda->fields['simbolo'] . $this->espacio . number_format($totales['valor'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%total_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($subtotal_en_moneda_cyc, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				#horas en decimal
				if ($this->fields['forma_cobro'] == 'FLAT FEE') {
					$minutos_decimal = $minutos_trabajadas / 60;
					$duracion_decimal = $horas_trabajadas + $minutos_decimal;
					$html = str_replace('%horas_mb%', number_format($duracion_decimal, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				} else {
					$minutos_decimal = $minutos_cobrables / 60;
					$duracion_decimal = $horas_cobrables + $minutos_decimal;
					$html = str_replace('%horas_mb%', number_format($totales['tiempo'] / 60 + $minutos_decimal, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				}

				$html = str_replace('%total_honorarios%', $flatfee ? $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto_subtotal'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) : $moneda->fields['simbolo'] . $this->espacio . number_format($totales['valor'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%horas%', number_format($duracion_decimal, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				break;

			case 'DETALLE_PROFESIONAL_RETAINER': //GenerarDocumento2
				$html = str_replace('%retainer%', __('Retainer'), $html);
				$html = str_replace('%valor_retainer%', $cobro_moneda->moneda[$this->fields['id_moneda_monto']]['simbolo'] . $this->espacio . number_format($this->fields['monto_contrato'], $cobro_moneda->moneda[$this->fields['id_moneda_monto']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				break;

			case 'GASTOS': //GenerarDocumento2
				if ($this->fields['opc_ver_gastos'] == 0) {
					return '';
				}
				/*
				  Solamente para antiguas templates
				 */
				if ($templateNotaCobroGastosSeparados) {
					break;
				}
				$html = str_replace('%separador%', '<hr size="2" class="separador">', $html);
				$html = str_replace('%glosa_gastos%', __('Gastos'), $html);
				$html = str_replace('%detalle_gastos_raz%', __('detalledegastos'), $html);
				if ($lang == 'es') {
					$html = str_replace('%glosa_gasto%', __('GASTOS'), $html);
				} else {
					$html = str_replace('%glosa_gasto%', __('EXPENSES'), $html);
				}
				$html = str_replace('%expenses%', __('%expenses%'), $html); //en vez de Disbursements es Expenses en ingl�s
				$html = str_replace('%detalle_gastos%', __('Detalle de gastos'), $html);

				$html = str_replace('%GASTOS_ENCABEZADO%', $this->GenerarDocumento2($parser, 'GASTOS_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%GASTOS_FILAS%', $this->GenerarDocumento2($parser, 'GASTOS_FILAS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%GASTOS_TOTAL%', $this->GenerarDocumento2($parser, 'GASTOS_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);

				break;

			case 'GASTOS_ENCABEZADO': //GenerarDocumento2
				$html = str_replace('%td_monto_original%', $moneda_total->fields['id_moneda'] == $this->fields['id_moneda_base'] ? '' : '<td align="center" width="80">%monto_original%</td>', $html);

				$query = "SELECT count(*) FROM cta_corriente WHERE id_cobro = '" . $this->fields['id_cobro'] . "' AND id_moneda != '" . $this->fields['opc_moneda_total'] . "' ";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				list($cantidad_gastos_en_otra_moneda) = mysql_fetch_array($resp);

				$html = str_replace('%glosa_gastos%', __('Gastos'), $html);
				$html = str_replace('%descripcion_gastos%', __('Descripci�n de Gastos'), $html);
				$html = str_replace('%descripcion%', __('Descripci�n'), $html);
				$html = str_replace('%fecha%', __('Fecha'), $html);
				$html = str_replace('%num_doc%', __('N� Documento'), $html);
				$html = str_replace('%tipo_gasto%', __('Tipo'), $html);
				$html = str_replace('%monto%', __('Monto') . ' (' . $moneda_total->fields['simbolo'] . ')', $html);
				$html = str_replace('%monto_moneda_total%', __('Monto') . ' (' . $moneda_total->fields['simbolo'] . ')', $html);

				$html = str_replace('%glosa_asunto%', __('Asunto'), $html);

				if ($lang == 'es') {
					$html = str_replace('%asunto_id%', __('ID<br>Asunto'), $html);
				} else {
					$html = str_replace('%asunto_id%', __('Matter<br>ID'), $html);
				}

				if (Conf::GetConf($this->sesion, 'MostrarProveedorenGastos')) {
					$html = str_replace('%proveedor%', __('Proveedor'), $html);
				} else {
					$html = str_replace('%proveedor%', '', $html);
				}

				if ($this->fields['opc_ver_solicitante']) {
					$html = str_replace('%solicitante%', __('Ordenado<br>Por'), $html);
				} else {
					$html = str_replace('%solicitante%', '', $html);
				}

				if ($cantidad_gastos_en_otra_moneda > 0 || !Conf::GetConf($this->sesion, 'MontoGastoOriginalSiMonedaDistinta')) {
					$html = str_replace('%monto_original%', __('Monto'), $html);
				} else {
					$html = str_replace('%monto_original%', '', $html);
				}

				if ($this->fields['porcentaje_impuesto_gastos'] > 0) {
					$html = str_replace('%td_monto_impuesto_total%', '<td style="text-align:center;">%monto_impuesto_total%</a>', $html);
					$html = str_replace('%td_monto_moneda_total_con_impuesto%', '<td style="text-align:center;">%monto_moneda_total_con_impuesto%</a>', $html);

					$html = str_replace('%monto_impuesto_total%', __('Monto Impuesto') . ' (' . $moneda_total->fields['simbolo'] . ')', $html);
					$html = str_replace('%monto_iva_total%', __('IVA') . ' (' . $moneda_total->fields['simbolo'] . ')', $html);
					$html = str_replace('%monto_impuesto_total_cc%', __('Monto_Impuesto_cc') . ' (' . $moneda_total->fields['simbolo'] . ')', $html);
					$html = str_replace('%monto_moneda_total_con_impuesto%', __('Monto total') . ' (' . $moneda_total->fields['simbolo'] . ')', $html);
					$html = str_replace('%total_monto_moneda%', __('Total') . ' (' . $moneda_total->fields['simbolo'] . ')', $html);
				} else {
					$html = str_replace('%monto_impuesto_total%', '', $html);
					$html = str_replace('%monto_iva_total%', '', $html);
					$html = str_replace('%monto_impuesto_total_cc%', '', $html);
					$html = str_replace('%monto_moneda_total_con_impuesto%', '', $html);
					$html = str_replace('%total_monto_moneda%', '', $html);
					//si no hay impuesto para los gastos, no dibujo esas celdas
					$html = str_replace('%td_monto_impuesto_total%', '&nbsp;', $html);
					$html = str_replace('%td_monto_moneda_total_con_impuesto%', '&nbsp;', $html);
				}
				break;

			case 'GASTOS_FILAS':  //GenerarDocumento2
				$html = str_replace('%td_monto_original%', $moneda_total->fields['id_moneda'] == $this->fields['id_moneda_base'] ? '' : '<td align="center">%monto_original%</td>', $html);

				$row_tmpl = $html;
				$html = '';
				if (Conf::GetConf($this->sesion, 'SepararGastosPorAsunto')) {
					if (!empty($asunto->fields['codigo_asunto']) && $asunto->separar_asuntos) {
						$where_gastos_asunto = " AND codigo_asunto='" . $asunto->fields['codigo_asunto'] . "'";
					}
				} else {
					$where_gastos_asunto = "";
				}
				$query = "SELECT SQL_CALC_FOUND_ROWS
						cta_corriente.*
						, prm_cta_corriente_tipo.glosa AS tipo_gasto
					FROM cta_corriente
					LEFT JOIN prm_cta_corriente_tipo ON cta_corriente.id_cta_corriente_tipo = prm_cta_corriente_tipo.id_cta_corriente_tipo
					WHERE id_cobro = '{$this->fields['id_cobro']}'
						AND monto_cobrable > 0
						AND cta_corriente.incluir_en_cobro = 'SI'
						AND cta_corriente.cobrable = 1
					$where_gastos_asunto
					ORDER BY fecha ASC";

				$lista_gastos = new ListaGastos($this->sesion, '', $query);
				$totales['total'] = 0;
				$totales['total_moneda_cobro'] = 0;
				if ($lista_gastos->num == 0) {
					$row = $row_tmpl;
					$row = str_replace('%fecha%', '&nbsp;', $row);
					$row = str_replace('%proveedor%', '&nbsp;', $row);
					$row = str_replace('%solicitante%', '&nbsp;', $row);
					$row = str_replace('%descripcion%', __('No hay gastos en este cobro'), $row);
					$row = str_replace('%descripcion_b%', '(' . __('No hay gastos en este cobro') . ')', $row);
					$row = str_replace('%monto_original%', '&nbsp;', $row);
					$row = str_replace('%monto%', '&nbsp;', $row);
					$row = str_replace('%monto_moneda_total%', '&nbsp;', $row);
					$row = str_replace('%monto_moneda_total_sin_simbolo%', '&nbsp;', $row);
					$row = str_replace('%valor_codigo_asunto%', $detalle->fields['codigo_asunto'], $row);
					$row = str_replace('%monto_impuesto_total%', '&nbsp;', $row);
					$row = str_replace('%monto_moneda_total_con_impuesto%', '&nbsp;', $row);
					$row = str_replace('%td_monto_impuesto_total%', '&nbsp;', $row);
					$row = str_replace('%td_monto_moneda_total_con_impuesto%', '&nbsp;', $row);
					$row = str_replace('%glosa_asunto%', '&nbsp;', $row);
					$row = str_replace('%ruc_proveedor%', '&nbsp;', $row);
					$row = str_replace('%total_impuesto%', '&nbsp;', $row);
					$row = str_replace('%total_con_impuesto%', '&nbsp;', $row);
					$html .= $row;
				}
				$cont_gasto_egreso = 0;
				$cont_gasto_ingreso = 0;

				global $monto_gastos_neto_por_asunto;
				global $monto_gastos_impuesto_por_asunto;
				global $monto_gastos_bruto_por_asunto;

				$monto_gastos_neto_por_asunto = 0;
				$monto_gastos_impuesto_por_asunto = 0;
				$monto_gastos_bruto_por_asunto = 0;

				foreach ($x_cobro_gastos['gasto_detalle'] as $id_gasto => $detalle) {
					if (Conf::GetConf($this->sesion, 'SepararGastosPorAsunto') && $asunto->separar_asuntos && !empty($asunto->fields['codigo_asunto']) && $asunto->fields['codigo_asunto'] != $detalle['codigo_asunto']) {
						continue;
					}
					$row = $row_tmpl;
					$row = str_replace('%fecha%', Utiles::sql2fecha($detalle['fecha'], $idioma->fields['formato_fecha']), $row);
					$row = str_replace('%num_doc%', $detalle['numero_documento'], $row);
					$row = str_replace('%tipo_gasto%', $detalle['tipo_gasto'], $row);
					$row = str_replace('%glosa_asunto%', $detalle['glosa_asunto'], $row);

					$query = "SELECT rut FROM prm_proveedor WHERE id_proveedor = '" . $detalle['id_proveedor'] . "' ";
					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
					list($rut) = mysql_fetch_array($resp);

					$row = str_replace('%ruc_proveedor%', $rut, $row);

					if (Conf::GetConf($this->sesion, 'MostrarProveedorenGastos')) {
						$row = str_replace('%proveedor%', $detalle['glosa_proveedor'], $row);
					} else {
						$row = str_replace('%proveedor%', '', $row);
					}

					if ($this->fields['opc_ver_solicitante']) {
						$row = str_replace('%solicitante%', $detalle['username'], $row);
					} else {
						$row = str_replace('%solicitante%', '', $row);
					}

					if (substr($detalle['descripcion'], 0, 41) == 'Saldo aprovisionado restante tras Cobro #') {
						$row = str_replace('%descripcion%', __('Saldo aprovisionado restante tras Cobro #') . substr($detalle['descripcion'], 42), $row);
						$row = str_replace('%descripcion_b%', __('Saldo aprovisionado restante tras Cobro #') . substr($detalle['descripcion'], 42), $row);
					} else if (substr($gasto->fields['descripcion'], 0, 41) == 'Saldo aprovisionado restante tras Cobro #') {
						$row = str_replace('%descripcion%', __('Saldo aprovisionado restante tras Cobro #') . substr($gasto->fields['descripcion'], 42), $row);
						$row = str_replace('%descripcion_b%', __('Saldo aprovisionado restante tras Cobro #') . substr($gasto->fields['descripcion'], 42), $row);
					} else {
						$row = str_replace('%descripcion%', __($detalle['descripcion']), $row);
						$row = str_replace('%descripcion_b%', __($detalle['descripcion']), $row); #Ojo, este no deber�a existir
					}

					if ($detalle['id_moneda'] != $this->fields['opc_moneda_total'] && Conf::GetConf($this->sesion, 'MontoGastoOriginalSiMonedaDistinta')) {
						$row = str_replace('%monto_original%', $cobro_moneda->moneda[$detalle['id_moneda']]['simbolo'] . $this->espacio . number_format($detalle['monto_original'], $cobro_moneda->moneda[$detalle['id_moneda']]['cifras_decimales'], $cobro_moneda->moneda[$gasto->fields['id_moneda']]['separador_decimales'], $cobro_moneda->moneda[$gasto->fields['id_moneda']]['separador_miles']), $row);
					} else {
						$row = str_replace('%monto_original%', '', $row);
					}
					#$row = str_replace('%monto%', $moneda_total->fields['simbolo'].' '.number_format($saldo_moneda_total,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);

					$monto_gastos_neto_por_asunto += $detalle['monto_total'];
					$monto_gastos_impuesto_por_asunto += $detalle['monto_total_impuesto'];
					$monto_gastos_bruto_por_asunto += $detalle['monto_total_mas_impuesto'];

					$row = str_replace('%monto_moneda_total%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($detalle['monto_total'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					$row = str_replace('%monto%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($detalle['monto_total'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

					// El c�digo de aqu� a 10 lineas m�s abajo es in�til ya que los reemplazos se hacen el las lineas anteriores bajos las mismas condiciones
					$row = str_replace('%monto_moneda_total%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($detalle['monto_total'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					$row = str_replace('%monto%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($saldo_moneda_total, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					$row = str_replace('%monto_moneda_total%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($detalle['monto_total'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

					$row = str_replace('%monto_moneda_total_sin_simbolo%', number_format($detalle['monto_total'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					$row = str_replace('%valor_codigo_asunto%', $gasto->fields['codigo_asunto'], $row);

					if ($this->fields['porcentaje_impuesto_gastos'] > 0) {
						$row = str_replace('%td_monto_impuesto_total%', '<td style="text-align:center;">%monto_impuesto_total%</a>', $row);
						$row = str_replace('%td_monto_moneda_total_con_impuesto%', '<td style="text-align:center;">%monto_moneda_total_con_impuesto%</a>', $row);
						$row = str_replace('%monto_impuesto_total%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($detalle['monto_total_impuesto'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						$row = str_replace('%monto_moneda_total_con_impuesto%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($detalle['monto_total_mas_impuesto'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						$row = str_replace('%total_con_impuesto%', number_format($detalle['monto_total_mas_impuesto'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						$row = str_replace('%total_impuesto%', number_format($detalle['monto_total_impuesto'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					} else {
						$row = str_replace('%td_monto_impuesto_total%', ' ', $row);
						$row = str_replace('%td_monto_moneda_total_con_impuesto%', ' ', $row);
						$row = str_replace('%monto_impuesto_total%', '', $row);
						$row = str_replace('%monto_moneda_total_con_impuesto%', '', $row);
						$row = str_replace('%total_con_impuesto%', '', $row);
						$row = str_replace('%total_impuesto%', '', $row);
					}

					// FACTURA FABARA

					if ($this->fields['porcentaje_impuesto_gastos'] > 0) {
						$row = str_replace('%columna_impuesto%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($detalle['monto_total_impuesto'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					} else {
						$row = str_replace('%columna_impuesto%', '--', $row);
					}

					$row = str_replace('%columna_monto_gasto%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($detalle['monto_total'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					$row = str_replace('%columna_subtotal_con_impuesto%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($detalle['monto_total_mas_impuesto'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

					$html .= $row;
				}
				break;

			case 'GASTOS_TOTAL': //GenerarDocumento2
				global $monto_gastos_neto_por_asunto;
				global $monto_gastos_impuesto_por_asunto;
				global $monto_gastos_bruto_por_asunto;

				$html = str_replace('%td_monto_original%', $moneda_total->fields['id_moneda'] == $this->fields['id_moneda_base'] ? '' : '<td>&nbsp;</td>', $html);
				$html = str_replace('%total%', __('Total'), $html);
				$html = str_replace('%glosa_total%', __('Total Gastos'), $html);
				if ($lang == 'es') {
					$html = str_replace('%sub_total_gastos%', __('Sub total gastos'), $html);
				} else {
					$html = str_replace('%sub_total_gastos%', __('Sub total for expenses'), $html);
				}
				#$html = str_replace('%valor_total%', ''/*$cobro_moneda->fields['simbolo'].' '.number_format($totales['total_moneda_cobro'],$cobro_moneda->fields['cifras_decimales']*/,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);

				/* pegar lo que hice pal otro caso */
				$id_moneda_base = Moneda::GetMonedaBase($this->sesion);

				#$html = str_replace('%valor_total%', ''/*$cobro_moneda->fields['simbolo'].' '.number_format($totales['total_moneda_cobro'],$cobro_moneda->fields['cifras_decimales']*/,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
				if ($this->fields['id_moneda_base'] <= 0) {
					$tipo_cambio_cobro_moneda_base = 1;
				} else {
					$tipo_cambio_cobro_moneda_base = $cobro_moneda->moneda[$id_moneda_base]['tipo_cambio'];
				}

				# Comentado por ICC $gastos_moneda_total = $totales['total']*$moneda->fields['tipo_cambio']/$tipo_cambio_moneda_total;
				if (Conf::GetConf($this->sesion, 'SepararGastosPorAsunto') && !empty($asunto->fields['codigo_asunto']) && $asunto->separar_asuntos) {
					$gastos_moneda_total = $monto_gastos_neto_por_asunto;
				} else {
					$gastos_moneda_total = $x_cobro_gastos['gasto_total'];
				}

				$html = str_replace('%total_gastos_moneda_total%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($totales['total'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				#$gastos_moneda_total = ($totales['total']*($moneda->fields['tipo_cambio']/$moneda_base->fields['tipo_cambio']))/$this->fields['opc_moneda_total_tipo_cambio'];
				$html = str_replace('%valor_total_monedabase%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($gastos_moneda_total, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				$contr = new Contrato($this->sesion);
				$contr->Load($this->fields['id_contrato']);

				if ($moneda_total->fields['id_moneda'] != $id_moneda_base) {
					$html = str_replace('%glosa_total_moneda_base%', __('Total Moneda Base'), $html);
					$gastos_moneda_total_contrato = ( $gastos_moneda_total * ( $cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio'])) / $tipo_cambio_cobro_moneda_base;
					$html = str_replace(array('%valor_total_moneda_carta%', '%valor_total_monedabase%'), $cobro_moneda->moneda[$id_moneda_base]['simbolo'] . $this->espacio . number_format($gastos_moneda_total_contrato, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				} else {
					$html = str_replace('%glosa_total_moneda_base%', '&nbsp;', $html);
					$html = str_replace('%valor_total_moneda_carta%', '&nbsp;', $html);
				}

				if (Conf::GetConf($this->sesion, 'SepararGastosPorAsunto') && !empty($asunto->fields['codigo_asunto']) && $asunto->separar_asuntos) {
					$gasto_impuesto_moneda_total = $monto_gastos_impuesto_por_asunto;
					$gasto_bruto_moneda_total = $monto_gastos_bruto_por_asunto;
				} else {
					$gasto_impuesto_moneda_total = $x_cobro_gastos['gasto_impuesto'];
					$gasto_bruto_moneda_total = $x_cobro_gastos['gasto_total_con_impuesto'];
				}

				$html = str_replace('%valor_total_monedabase_sin_impuesto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($gasto_bruto_moneda_total - $gasto_impuesto_moneda_total, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				if ($this->fields['porcentaje_impuesto_gastos'] > 0) {
					$html = str_replace('%td_valor_impuesto_monedabase%', '<td style="text-align:center;">%valor_impuesto_monedabase%</a>', $html);
					$html = str_replace('%valor_impuesto_monedabase_fabara%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($gasto_impuesto_moneda_total, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					$html = str_replace('%td_valor_total_monedabase_con_impuesto%', '<td style="text-align:center;">%valor_total_monedabase_con_impuesto%</a>', $html);

					$html = str_replace('%valor_impuesto_monedabase%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($gasto_impuesto_moneda_total, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					$html = str_replace('%valor_total_monedabase_con_impuesto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($gasto_bruto_moneda_total, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					$html = str_replace('%valor_total_monedabase_sin_impuesto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($gasto_bruto_moneda_total - $gasto_impuesto_moneda_total, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				} else {
					$html = str_replace('%td_valor_impuesto_monedabase%', '', $html);
					$html = str_replace('%valor_impuesto_monedabase_fabara%', '--', $html);
					$html = str_replace('%td_valor_total_monedabase_con_impuesto%', '', $html);
					$html = str_replace('%valor_impuesto_monedabase%', '', $html);
					$html = str_replace('%valor_total_monedabase_con_impuesto%', '', $html);
				}

				$html = str_replace('%valor_total_monedabase_con_impuesto_fabara%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($gasto_bruto_moneda_total, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				break;

			case 'CTA_CORRIENTE': //GenerarDocumento2
				if ($this->fields['opc_ver_gastos'] == 0)
					return '';

				$html = str_replace('%titulo_detalle_cuenta%', __('Saldo de Gastos Adeudados'), $html);
				$html = str_replace('%descripcion_cuenta%', __('Descripci�n'), $html);
				$html = str_replace('%monto_cuenta%', __('Monto'), $html);

				$html = str_replace('%CTA_CORRIENTE_SALDO_INICIAL%', $this->GenerarDocumentoComun($parser, 'CTA_CORRIENTE_SALDO_INICIAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%CTA_CORRIENTE_MOVIMIENTOS_ENCABEZADO%', $this->GenerarDocumentoComun($parser, 'CTA_CORRIENTE_MOVIMIENTOS_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%CTA_CORRIENTE_MOVIMIENTOS_FILAS%', $this->GenerarDocumentoComun($parser, 'CTA_CORRIENTE_MOVIMIENTOS_FILAS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%CTA_CORRIENTE_MOVIMIENTOS_TOTAL%', $this->GenerarDocumentoComun($parser, 'CTA_CORRIENTE_MOVIMIENTOS_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%CTA_CORRIENTE_SALDO_FINAL%', $this->GenerarDocumentoComun($parser, 'CTA_CORRIENTE_SALDO_FINAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				break;
		}
		return $html;
	}

	function GenerarDocumentoComun($parser, $theTag = 'INFORME', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, & $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto) {
		// Reune los anchors que no var�an entre ambas modalidades de c�lculo
		global $contrato;
		global $cobro_moneda;
		//global $moneda_total;
		global $masi;

		$moneda_total = new Objeto($this->sesion, '', '', 'prm_moneda', 'id_moneda');
		$moneda_total->Load($this->fields['opc_moneda_total'] > 0 ? $this->fields['opc_moneda_total'] : 1);

		if (!isset($parser->tags[$theTag])) {
			return;
		}

		$this->FillTemplateData($idioma, $moneda);

		$html = $this->RenderTemplate($parser->tags[$theTag]);

		switch ($theTag) {

			case 'ENDOSO': //GenerarDocumentoComun
				global $x_resultados;

				$subtotalgastos = ($totales['valor_total']) + ($totales['total_egreso']);
				$monto_total_neto = $monto_gastos_bruto_por_asunto + $subtotalgastos;

				$query = "	SELECT b.nombre, cb.numero, cb.cod_swift, cb.CCI
								FROM cuenta_banco cb
								LEFT JOIN prm_banco b ON b.id_banco = cb.id_banco
								WHERE cb.id_cuenta = '" . $contrato->fields['id_cuenta'] . "'";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				list($glosa_banco, $numero_cuenta, $codigo_swift, $codigo_cci) = mysql_fetch_array($resp);
				$html = str_replace('%numero_cuenta_contrato%', $numero_cuenta, $html);
				$html = str_replace('%glosa_banco_contrato%', $glosa_banco, $html);
				$html = str_replace('%codigo_swift%', $codigo_swift, $html);
				$html = str_replace('%codigo_cci%', $codigo_cci, $html);

				$html = str_replace('%valor_total_sin_impuesto%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($subtotalgastos, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_impuesto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($x_resultados['impuesto'][$this->fields['opc_moneda_total']], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_total_con_impuesto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($x_resultados['monto_total_cobro'][$this->fields['opc_moneda_total']], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				$html = str_replace('%tipo_gbp_segun_moneda%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['glosa_moneda_plural'], $html);
				$html = str_replace('%texto_instrucciones%', __('texto_instrucciones'), $html);

				if ($lang == 'es') {

					$html = str_replace('%total_sin_impuesto%', __('TOTAL SIN IMPUESTOS'), $html);
					$html = str_replace('%impuesto%', __('I.V.A. (10%)'), $html);
					$html = str_replace('%total_factura%', __('TOTAL FACTURA'), $html);
					$html = str_replace('%instrucciones_deposito%', __('INSTRUCCIONES DE TRANSFERENCIA BANCARIA A VOUGA & OLMEDO ABOGADOS'), $html);
					$html = str_replace('%solicitud%', __('FAVOR INCLUIR NOMBRE DE LA EMPRESA Y N�MERO DE FACTURA.'), $html);
					##Textos Cuesta Campos##
					$html = str_replace('%pago_via%', __('Pago v�a transferencia a la siguiente cuenta:'), $html);
					$html = str_replace('%solicitud_cheques%', __('textoSolicitudCheque'), $html);
					$html = str_replace('%caso_dudas%', __('En caso de dudas o comentarios al respecto no dude en contactarnos.'), $html);
					$html = str_replace('%atentamente%', __('Atentamente,'), $html);
					$html = str_replace('%sucursal%', __('Sucursal'), $html);
					$html = str_replace('%cuenta%', __('Cuenta'), $html);
					$html = str_replace('%direccion%', __('Direci�n'), $html);
					$html = str_replace('%banco%', __('Banco'), $html);
					$html = str_replace('%beneficiario%', __('Beneficiario'), $html);
				} else {

					$html = str_replace('%total_sin_impuesto%', __('TOTAL BEFORE TAXES'), $html);
					$html = str_replace('%impuesto%', __('V.A.T. (10%)'), $html);
					$html = str_replace('%total_factura%', __('TOTAL INVOICE'), $html);
					$html = str_replace('%instrucciones_deposito%', __('INSTRUCTIONS FOR PAYMENTS TO VOUGA & OLMEDO ABOGADOS:<br>ELECTRONIC TRANSFER VIA SWIFT MT103 MESSAGE:'), $html);
					$html = str_replace('%solicitud%', __('CORPORATE NAME AND INVOICE # MUST BE INCLUDED.'), $html);

					##Textos Cuesta Campos##
					$html = str_replace('%pago_via%', __(' Payment by wire transfer to the following account:'), $html);
					$html = str_replace('%solicitud_cheques%', __('textoSolicitudCheque'), $html);
					$html = str_replace('%caso_dudas%', __('Please feel free to contact us should you have any questions or comments on the above.'), $html);
					$html = str_replace('%atentamente%', __('Very truly yours'), $html);
					$html = str_replace('%sucursal%', __('Branch'), $html);
					$html = str_replace('%cuenta%', __('Account'), $html);
					$html = str_replace('%direccion%', __('Address'), $html);
					$html = str_replace('%banco%', __('Bank'), $html);
					$html = str_replace('%beneficiario%', __('Bnf'), $html);
				}

				$html = str_replace('%tipo_gbp_segun_moneda%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['glosa_moneda_plural'], $html);

				if ($this->fields['id_carta'] == 3) {
					$html = str_replace('%nota_disclaimer%', __('nota_disclaimer'), $html);
				} else {
					$html = str_replace('%nota_disclaimer%', ' ', $html);
				}


				break;

			case 'DESGLOSE_POR_ASUNTO_DETALLE': //GenerarDocumentoComun
				global $subtotal_hh, $subtotal_gasto, $subtotal_tramite, $impuesto_hh, $impuesto_gasto, $impuesto_tramite, $simbolo, $cifras_decimales;

				$query_desglose_asuntos = "SELECT    pm.cifras_decimales, pm.simbolo, @rownum:=@rownum+1 as rownum, ca.id_cobro, ca.codigo_asunto,a.glosa_asunto
							,if(@rownum=kant,@sumat1:=(1.0000-@sumat1), round(ifnull(trabajos.trabajos_thh/monto_thh,0),8)) pthh
							,@sumat1:=@sumat1+round(ifnull(trabajos.trabajos_thh/monto_thh,0),8) pthhac
							,if(@rownum=kant,@sumat2:=(1.0000-@sumat2), round(ifnull(trabajos.trabajos_thh_estandar/monto_thh_estandar,0),4)) pthhe
							,@sumat2:=@sumat2+round(ifnull(trabajos.trabajos_thh_estandar/monto_thh_estandar,0),8) pthheac
							,if(@rownum=kant,@sumag:=(1.0000-@sumag), round(ifnull(gastos.gastos/subtotal_gastos,0),8))  pg
							,@sumag:=@sumag+round(ifnull(gastos.gastos/subtotal_gastos,0),8) pgac
							,if(@rownum=kant,@sumat3:=(1.0000-@sumat3), round(ifnull(tramites.tramites/monto_tramites,0),8))  pt
							,@sumat3:=@sumat3+round(ifnull(tramites.tramites/monto_tramites,0),8) ptac
							,c.monto_trabajos
							,c.monto_thh
							,c.monto_thh_estandar
							,c.subtotal_gastos
							,c.monto_tramites
							,c.impuesto
							,c.impuesto_gastos
							, (c.monto_tramites * c.porcentaje_impuesto / 100) as impuesto_tramites
							,kant.kant

							FROM cobro_asunto ca
							join cobro c on (c.id_cobro = ca.id_cobro)
							join asunto a on (a.codigo_asunto = ca.codigo_asunto)
							join (select id_cobro, count(codigo_asunto) kant from cobro_asunto group by id_cobro) kant on kant.id_cobro=c.id_cobro
							join (select @rownum:=0, @sumat1:=0, @sumat2:=0, @sumag:=0, @sumat3:=0) fff
							join prm_moneda pm on pm.id_moneda=c.id_moneda
						join prm_moneda doc_moneda on doc_moneda .id_moneda=c.opc_moneda_total

							left join (SELECT id_cobro, codigo_asunto, SUM( TIME_TO_SEC( duracion_cobrada ) /3600 * tarifa_hh ) AS trabajos_thh, SUM( TIME_TO_SEC( duracion_cobrada ) /3600 * tarifa_hh_estandar ) AS trabajos_thh_estandar
							FROM trabajo  WHERE trabajo.id_tramite is null
							GROUP BY codigo_asunto,id_cobro) trabajos on trabajos.id_cobro=c.id_cobro and trabajos.codigo_asunto=ca.codigo_asunto

							left join (select id_cobro, codigo_asunto, sum(ifnull(egreso,0)-ifnull(ingreso,0)) gastos
							from cta_corriente where cobrable=1
							group by id_cobro, codigo_asunto) gastos on gastos.id_cobro=c.id_cobro and gastos.codigo_asunto=ca.codigo_asunto

							left join (SELECT id_cobro, codigo_asunto, SUM( IFNULL(tarifa_tramite,0)) AS tramites
							FROM tramite
							GROUP BY codigo_asunto,id_cobro) tramites on tramites.id_cobro=c.id_cobro and tramites.codigo_asunto=ca.codigo_asunto

							WHERE ca.id_cobro=" . $this->fields['id_cobro'];

				$rest_desglose_asuntos = mysql_query($query_desglose_asuntos, $this->sesion->dbh) or Utiles::errorSQL($query_desglose_asuntos, __FILE__, __LINE__, $this->sesion->dbh);
				$row_tmpl = $html;

				$html = '';
				while ($rowdesglose = mysql_fetch_array($rest_desglose_asuntos)) {
					list($subtotal_hh, $subtotal_gasto, $subtotal_tramite, $impuesto_hh, $impuesto_gasto, $impuesto_tramite, $simbolo, $cifras_decimales) = array($rowdesglose['monto_trabajos'], $rowdesglose['subtotal_gastos'], $rowdesglose['monto_tramites'], $rowdesglose['impuesto'], $rowdesglose['impuesto_gastos'], $rowdesglose['impuesto_tramites'], $rowdesglose['simbolo'], $rowdesglose['cifras_decimales']);
					$row = $row_tmpl;

					$row = str_replace('%codigo_asunto%', $rowdesglose['codigo_asunto'], $row);
					$row = str_replace('%glosa_asunto%', $rowdesglose['glosa_asunto'], $row);
					$row = str_replace('%simbolo%', $simbolo, $row);
					$row = str_replace('%honorarios_asunto%', number_format(round($rowdesglose['monto_trabajos'] * $rowdesglose['pthh'], $cifras_decimales), 2), $row);
					$row = str_replace('%gastos_asunto%', number_format(round($rowdesglose['subtotal_gastos'] * $rowdesglose['pg'], $cifras_decimales), 2), $row);
					$row = str_replace('%tramites_asunto%', number_format(round($rowdesglose['monto_tramites'] * $rowdesglose['pt'], $cifras_decimales), 2), $row);

					$html .= $row;
				}


				break;
			//FFF Esto se hizo para Aguilar Castillo Love
			case 'DESGLOSE_POR_ASUNTO_TOTALES': //GenerarDocumentoComun
				global $subtotal_hh, $subtotal_gasto, $subtotal_tramite, $impuesto_hh, $impuesto_gasto, $impuesto_tramite, $simbolo, $cifras_decimales;

				$html = str_replace('%simbolo%', $simbolo, $html);
				$html = str_replace('%desglose_subtotal_hh%', number_format(round($subtotal_hh, $cifras_decimales), 2), $html);
				$html = str_replace('%desglose_subtotal_gasto%', number_format(round($subtotal_gasto, $cifras_decimales), 2), $html);
				$html = str_replace('%desglose_subtotal_tramite%', number_format(round($subtotal_tramite, $cifras_decimales), 2), $html);
				$html = str_replace('%desglose_impuesto_hh%', number_format(round($impuesto_hh, $cifras_decimales), 2), $html);
				$html = str_replace('%desglose_impuesto_gasto%', number_format(round($impuesto_gasto, $cifras_decimales), 2), $html);
				$html = str_replace('%desglose_impuesto_tramite%', number_format(round($impuesto_tramite, $cifras_decimales), 2), $html);

				$html = str_replace('%desglose_grantotal%', number_format(round(floatval($subtotal_hh) + floatval($subtotal_gasto) + floatval($subtotal_tramite) + floatval($impuesto_hh) + floatval($impuesto_gasto) + floatval($impuesto_tramites), $cifras_decimales), 2), $html);

				$html = str_replace('%subtotales%', __('Subtotal'), $html);
				$html = str_replace('%impuestos%', __('Impuesto'), $html);
				$html = str_replace('%total_deuda%', __('Total Adeudado'), $html);

				break;



			case 'RESTAR_RETAINER': //GenerarDocumentoComun
				if ($columna_hrs_retainer || $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') {
					$html = str_replace('%retainer%', __('Retainer'), $html);
				} else {
					$html = str_replace('%retainer%', '', $html);
				}
				if ($columna_hrs_retainer || $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') {
					$html = str_replace('%valor_retainer%', '(' . $cobro_moneda->moneda[$this->fields['id_moneda_monto']]['simbolo'] . $this->espacio . number_format($this->fields['monto_contrato'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ')', $html);
				} else {
					$html = str_replace('%valor_retainer%', '', $html);
				}
				break;

			case 'DETALLE_COBRO_RETAINER': //GenerarDocumentoComun
				$monto_contrato_moneda_tarifa = number_format($this->fields['monto_contrato'] * $cobro_moneda->moneda[$this->fields['id_moneda_monto']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
				$monto_cobro_menos_monto_contrato_moneda_tarifa = number_format($this->fields['monto'] - ($this->fields['monto_contrato'] * $cobro_moneda->moneda[$this->fields['id_moneda_monto']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');

				$html = str_replace('%horas_retainer%', 'Horas retainer', $html);
				$html = str_replace('%valor_horas_retainer%', Utiles::horaDecimal2HoraMinuto($this->fields['retainer_horas']), $html);
				$html = str_replace('%horas_adicionales%', 'Horas adicionales', $html);
				$html = str_replace('%valor_horas_adicionales%', Utiles::horaDecimal2HoraMinuto(($this->fields['total_minutos'] / 60) - $this->fields['retainer_horas']), $html);
				$html = str_replace('%honorarios_retainer%', 'Honorarios retainer', $html);
				$html = str_replace('%valor_honorarios_retainer%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . number_format($monto_contrato_moneda_tarifa, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%honorarios_adicionales%', 'Honorarios adicionales', $html);
				$html = str_replace('%valor_honorarios_adicionales%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . number_format($monto_cobro_menos_monto_contrato_moneda_tarifa, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				break;

			case 'DETALLE_TARIFA_ADICIONAL': //GenerarDocumentoComun
				$tarifas_adicionales = $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . " ";

				$query = "SELECT DISTINCT tarifa_hh FROM trabajo WHERE id_cobro = '" . $this->fields['id_cobro'] . "' ORDER BY tarifa_hh DESC";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

				$i = 0;
				while (list($tarifa_hh) = mysql_fetch_array($resp)) {
					if ($i == 0)
						$tarifas_adicionales .= "$tarifa_hh/hr";
					else
						$tarifas_adicionales .= ", $tarifa_hh/hr";
					$i++;
				}

				$html = str_replace('%tarifa_adicional%', __('Tarifa adicional por hora'), $html);
				$html = str_replace('%valores_tarifa_adicionales%', $tarifas_adicionales, $html);
				break;

			case 'FACTURA_NUMERO': //GenerarDocumentoComun
				$html = str_replace('%factura_nro%', __('Factura') . ' ' . __('N�'), $html);
				break;

			case 'NUMERO_FACTURA': //GenerarDocumentoComun
				$html = str_replace('%nro_factura%', $this->fields['documento'], $html);
				break;

			case 'DETALLE_HONORARIOS': //GenerarDocumentoComun
				$horas_cobrables = floor(($this->fields['total_minutos']) / 60);
				$minutos_cobrables = sprintf("%02d", $this->fields['total_minutos'] % 60);
				$html = str_replace('%horas%', __('Total Horas'), $html);
				$html = str_replace('%valor_horas%', $horas_cobrables . ':' . $minutos_cobrables, $html);
				$html = str_replace('%honorarios%', __('Honorarios'), $html);
				if (Conf::GetConf($this->sesion, 'UsarImpuestoSeparado') && $contrato->fields['usa_impuesto_separado'])
					$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto'] - $this->fields['impuesto'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				else
					$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				if ($this->fields['opc_ver_descuento']) {
					$html = str_replace('%DETALLE_COBRO_DESCUENTO%', $this->GenerarDocumentoComun($parser, 'DETALLE_COBRO_DESCUENTO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				} else {
					$html = str_replace('%DETALLE_COBRO_DESCUENTO%', '', $html);
				}

				$html = str_replace('%DETALLE_COBRO_MONEDA_TOTAL%', $this->GenerarDocumentoComun($parser, 'DETALLE_COBRO_MONEDA_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				break;

			case 'DETALLE_GASTOS': //GenerarDocumentoComun
				$html = str_replace('%gastos%', __('Gastos'), $html);
				$query = "SELECT SQL_CALC_FOUND_ROWS *
								FROM cta_corriente
								WHERE id_cobro='" . $this->fields['id_cobro'] . "' AND (egreso > 0 OR ingreso > 0) AND cta_corriente.incluir_en_cobro = 'SI'
								ORDER BY fecha ASC";
				$lista_gastos = new ListaGastos($this->sesion, '', $query);
				$total_gastos_moneda = 0;
				for ($i = 0; $i < $lista_gastos->num; $i++) {
					$gasto = $lista_gastos->Get($i);

					if ($gasto->fields['egreso'] > 0)
						$saldo = $gasto->fields['monto_cobrable'];
					elseif ($gasto->fields['ingreso'] > 0)
						$saldo = -$gasto->fields['monto_cobrable'];

					$monto_gasto = $saldo;
					$saldo_moneda_total = $saldo * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']);
					$total_gastos_moneda += $saldo_moneda_total;
				}

				if (Conf::GetConf($this->sesion, 'CalculacionCyC')) {
					$total_gastos_moneda = round($total_gastos_moneda, $moneda_total->fields['cifras_decimales']);
				}

				$impuestos_total_gastos_moneda = round($total_gastos_moneda * ($this->fields['porcentaje_impuesto_gastos'] / 100), $moneda_total->fields['cifras_decimales']);
				$html = str_replace('%valor_gastos%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($total_gastos_moneda, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				break;

			case 'DETALLE_TRAMITES': //GenerarDocumentoComun
				$html = str_replace('%tramites%', __('Tr�mites'), $html);
				$html = str_replace('%tramites_castropal%', __('Otros Servicios'), $html);
				$aproximacion_tramites = number_format($this->fields['monto_tramites'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
				$valor_tramites = $aproximacion_tramites * $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'];
				$html = str_replace('%valor_tramites%', $moneda_total->fields['simbolo'] . number_format($valor_tramites, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				break;

			case 'DETALLE_COBRO_MONEDA_TOTAL': //GenerarDocumentoComun

				global $x_resultados;

				if ($this->fields['opc_moneda_total'] == $this->fields['id_moneda']) {
					return '';
				}

				#valor en moneda previa selecci�n para impresi�n
				if ($this->fields['tipo_cambio_moneda_base'] <= 0) {
					$tipo_cambio_cobro_moneda_base = 1;
				} else {
					$tipo_cambio_cobro_moneda_base = $this->fields['tipo_cambio_moneda_base'];
				}

				if (Conf::GetConf($this->sesion, 'CalculacionCyC')) {
					$aproximacion_monto = number_format($this->fields['monto_subtotal'] - $this->fields['descuento'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
					$total_en_moneda = $aproximacion_monto * ($this->fields['tipo_cambio_moneda'] / $tipo_cambio_cobro_moneda_base) / ($tipo_cambio_moneda_total / $tipo_cambio_cobro_moneda_base);
				} else {
					$aproximacion_monto = number_format($this->fields['monto'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
					$total_en_moneda = $aproximacion_monto * ($this->fields['tipo_cambio_moneda'] / $tipo_cambio_cobro_moneda_base) / ($tipo_cambio_moneda_total / $tipo_cambio_cobro_moneda_base);
				}
				//Caso retainer menor de un valor y distinta tarifa (diferencia por decimales)
				if ((($this->fields['total_minutos'] / 60) < $this->fields['retainer_horas']) && ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') && $this->fields['id_moneda'] != $this->fields['id_moneda_monto']) {
					$total_en_moneda = $this->fields['monto'] * ($this->fields['tipo_cambio_moneda'] / $tipo_cambio_cobro_moneda_base) / ($tipo_cambio_moneda_total / $tipo_cambio_cobro_moneda_base);
				}

				//Caso flat fee
				if ($this->fields['forma_cobro'] == 'FLAT FEE' && $this->fields['id_moneda'] != $this->fields['id_moneda_monto'] && $this->fields['id_moneda_monto'] == $this->fields['opc_moneda_total'] && empty($this->fields['descuento'])) {
					$total_en_moneda = $this->fields['monto_contrato'];
				}

				$aproximacion_monto_trabajos_demo = number_format($this->fields['monto_trabajos'] - $this->fields['descuento'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
				$valor_trabajos_demo_moneda_total = $aproximacion_monto_trabajos_demo * $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'];

				$html = str_replace('%monedabase%', __('Equivalente a'), $html);
				$html = str_replace('%total_pagar%', __('Total a Pagar'), $html);

				if ((Conf::GetConf($this->sesion, 'UsarImpuestoSeparado')) && $contrato->fields['usa_impuesto_separado'] && (!Conf::GetConf($this->sesion, 'CalculacionCyC'))) {
					$total_en_moneda -= $this->fields['impuesto'] * ($this->fields['tipo_cambio_moneda'] / $tipo_cambio_cobro_moneda_base) / ($tipo_cambio_moneda_total / $tipo_cambio_cobro_moneda_base);
				}

				$html = str_replace('%valor_honorarios_monedabase_demo%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($valor_trabajos_demo_moneda_total, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_honorarios_monedabase%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format(floor($total_en_moneda), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_honorarios_monedabase_mb%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($x_resultados['monto'][$this->fields['opc_moneda_total']], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				break;

			case 'DETALLE_COBRO_DESCUENTO': //GenerarDocumentoComun

				if ($this->fields['descuento'] == 0) {
					if (Conf::GetConf($this->sesion, 'FormatoNotaCobroMTA')) {
						$html = str_replace('%honorarios%', __('Subtotal Honorarios'), $html);
						$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto_subtotal'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
						$html = str_replace('%valor_descuento%', '', $html);
						$html = str_replace('%porcentaje_descuento%', '', $html);
						$html = str_replace('%descuento%', '', $html);
						break;
					} else {
						return '';
					}
				}

				$aproximacion_honorarios = number_format($this->fields['monto_subtotal'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
				$aproximacion_descuento = number_format($this->fields['descuento'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
				$valor_trabajos_demo = number_format($this->fields['monto_trabajos'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idoma->fields['separador_miles']);
				$valor_descuento_demo = number_format($this->fields['descuento'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
				$valor_honorarios = number_format($aproximacion_honorarios * $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
				$valor_descuento = number_format($aproximacion_descuento * $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);

				$html = str_replace('%valor_honorarios_demo%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . $this->espacio . $valor_trabajos_demo, $html);
				$html = str_replace('%valor_descuento_demo%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . $this->espacio . $valor_descuento_demo, $html);

				if (Conf::GetConf($this->sesion, 'CalculacionCyC')) {
					$html = str_replace('%valor_honorarios%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . $valor_honorarios, $html);
					$html = str_replace('%valor_descuento%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . $valor_descuento, $html);
				}

				$html = str_replace('%honorarios%', __('Subtotal Honorarios'), $html);
				$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto_subtotal'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%descuento%', __('Descuento'), $html);

				if ($this->fields['monto_trabajos'] > 0) {
					$porcentaje_demo = ($this->fields['descuento'] * 100) / $this->fields['monto_trabajos'];
				}

				$html = str_replace('%porcentaje_descuento_demo%', ' (' . number_format($porcentaje_demo, 0) . '%)', $html);

				if ($this->fields['monto_subtotal'] > 0) {
					$porcentaje = ($this->fields['descuento'] * 100) / $this->fields['monto_subtotal'];
				}

				$html = str_replace('%porcentaje_descuento%', ' (' . number_format($porcentaje, 0) . '%)', $html);
				$html = str_replace('%valor_descuento%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['descuento'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%total_honorarios%', __('Total Honorarios'), $html);
				$html = str_replace('%valor_honorarios_con_descuento%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);


				break;

			case 'ASUNTOS': //GenerarDocumentoComun
				$row_tmpl = $html;
				$html = '';
				$cliente = "";
				global $profesionales;
				$profesionales = array();


				$queryasuntos = "SELECT asunto.codigo_asunto, asunto.codigo_cliente, cliente.glosa_cliente
									FROM trabajo
									LEFT JOIN asunto
									USING ( codigo_asunto )
									LEFT JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente
									WHERE id_cobro ={$this->fields['id_cobro']}
									UNION
									SELECT asunto.codigo_asunto, asunto.codigo_cliente, cliente.glosa_cliente
									FROM cta_corriente
									LEFT JOIN asunto
									USING ( codigo_asunto )
									LEFT JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente
									WHERE id_cobro ={$this->fields['id_cobro']}
									UNION
									SELECT asunto.codigo_asunto, asunto.codigo_cliente, cliente.glosa_cliente
									FROM tramite
									LEFT JOIN asunto
									USING ( codigo_asunto )
									LEFT JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente
									WHERE id_cobro ={$this->fields['id_cobro']}";

				try {
					$arregloasuntos = $this->sesion->pdodbh->query($queryasuntos);
				} catch (PDOException $e) {
					Utiles::errorSQL($queryasuntos, "", "", NULL, "", $e);
					exit;
				}
				foreach ($arregloasuntos as $filaasunto) {
					$asunto = new Asunto($this->sesion);
					$asunto->LoadByCodigo($filaasunto['codigo_asunto']);

					unset($GLOBALS['profesionales']);
					$profesionales = array();

					unset($GLOBALS['resumen_profesionales']);
					$resumen_profesionales = array();

					unset($GLOBALS['totales']);
					$totales = array();
					$totales['tiempo'] = 0;
					$totales['tiempo_trabajado'] = 0;
					$totales['tiempo_trabajado_real'] = 0;
					$totales['tiempo_retainer'] = 0;
					$totales['tiempo_flatfee'] = 0;
					$totales['tiempo_descontado'] = 0;
					$totales['tiempo_descontado_real'] = 0;
					$totales['valor'] = 0;
					$categoria_duracion_horas = 0;
					$categoria_duracion_minutos = 0;
					$categoria_valor = 0;
					$total_trabajos_categoria = '';
					$encabezado_trabajos_categoria = '';

					$query = "SELECT count(*) FROM tramite
									WHERE id_cobro=" . $this->fields['id_cobro'] . "
										AND codigo_asunto='" . $asunto->fields['codigo_asunto'] . "'";
					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
					list($cont_tramites) = mysql_fetch_array($resp);

					$query = "SELECT count(*) FROM trabajo
									WHERE id_cobro=" . $this->fields['id_cobro'] . "
										AND codigo_asunto='" . $asunto->fields['codigo_asunto'] . "'
										AND id_tramite=0";
					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
					list($cont_trabajos) = mysql_fetch_array($resp);

					$query = "SELECT count(*) FROM cta_corriente
									 WHERE id_cobro=" . $this->fields['id_cobro'] . "
										AND codigo_asunto='" . $asunto->fields['codigo_asunto'] . "'";
					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
					list($cont_gastos) = mysql_fetch_array($resp);

					$row = $row_tmpl;

					if (count($this->asuntos) > 1) {
						$row = str_replace('%salto_pagina_varios_asuntos%', '&nbsp;<br clear=all style="mso-special-character:line-break; page-break-before:always" size="1" class="divisor">', $row);
						$row = str_replace('%salto_pagina_un_asunto%', '', $row);
						$row = str_replace('%asunto_extra%', __('Asunto'), $row);
						$row = str_replace('%glosa_asunto_sin_codigo_extra%', $asunto->fields['glosa_asunto'], $row);
					} else {
						$row = str_replace('%salto_pagina_varios_asuntos%', '', $row);
						$row = str_replace('%salto_pagina_un_asunto%', '&nbsp;<br clear=all style="mso-special-character:line-break; page-break-before:always" size="1" class="divisor">', $row);
						$row = str_replace('%asunto_extra%', '', $row);
						$row = str_replace('%glosa_asunto_sin_codigo_extra%', '', $row);
					}

					$row = str_replace('%asunto%', __('Asunto'), $row);

					if ($filaasunto['codigo_cliente'] != $cliente) {  //empiezo una nueva seccion de clientes
						$row = str_replace('%asuntos_cliente%', 'background:#EFEFEF;border-top:1px solid #999;height:20px;vertical-align:middle', $row);
						$row = str_replace('%etiqueta_cliente%', __('Asuntos') . '  del ' . __('Cliente'), $row);
						$row = str_replace('%codigo_cliente_cambio%', $filaasunto['codigo_cliente'], $row);
						$row = str_replace('%glosa_cliente%', $filaasunto['glosa_cliente'], $row);
						$cliente = $filaasunto['codigo_cliente'];
					} else {
						$row = str_replace('%asuntos_cliente%', 'height:0px;', $row);
						$row = str_replace('%etiqueta_cliente%', '', $row);
						$row = str_replace('%codigo_cliente_cambio%', '', $row);
						$row = str_replace('%glosa_cliente%', '', $row);
						$cliente = $filaasunto['codigo_cliente'];
					}

					if (Conf::GetConf($this->sesion, 'GlosaAsuntoSinCodigo')) {
						$row = str_replace('%glosa_asunto%', $asunto->fields['glosa_asunto'], $row);
						$row = str_replace('%glosa_asunto_secundario%', $asunto->fields['glosa_asunto'], $row);
					} else {
						$row = str_replace('%glosa_asunto%', $asunto->fields['codigo_asunto'] . " - " . $asunto->fields['glosa_asunto'], $row);
						$row = str_replace('%glosa_asunto_secundario%', $asunto->fields['codigo_asunto_secundario'] . " - " . $asunto->fields['glosa_asunto'], $row);
					}
					$row = str_replace('%codigo_cliente%', $filaasunto['codigo_cliente'], $row);
					$row = str_replace('%glosa_asunto_sin_codigo%', $asunto->fields['glosa_asunto'], $row);
					$row = str_replace('%glosa_asunto_codigo_area%', $asunto->fields['codigo_asunto'] . '-' . sprintf("%02d", ($asunto->fields['id_area_proyecto'] - 1)) . " - " . $asunto->fields['glosa_asunto'], $row);
					$row = str_replace('%valor_codigo_asunto%', $asunto->fields['codigo_asunto'], $row);
					$row = str_replace('%codigo_cliente_secundario%', empty($cliente->fields['codigo_cliente_secundario']) ? '' : __('C�digo Cliente'), $row);
					$row = str_replace('%valor_codigo_cliente_secundario%', empty($cliente->fields['codigo_cliente_secundario']) ? '' : empty($cliente->fields['codigo_cliente_secundario']), $row);
					$row = str_replace('%contacto%', empty($asunto->fields['contacto']) ? '' : __('Contacto'), $row);
					$row = str_replace('%valor_contacto%', empty($asunto->fields['contacto']) ? '' : $asunto->fields['contacto'], $row);

					$row = str_replace('%registro%', __('Registro de Tiempo'), $row);
					$row = str_replace('%telefono%', empty($asunto->fields['fono_contacto']) ? '' : __('Tel�fono'), $row);
					$row = str_replace('%valor_telefono%', empty($asunto->fields['fono_contacto']) ? '' : $asunto->fields['fono_contacto'], $row);
					if ($cont_trabajos > 0) {
						if ($this->fields["opc_ver_detalles_por_hora"] == 1) {
							$row = str_replace('%espacio_trabajo%', '<br>', $row);
							$row = str_replace('%servicios%', __('Servicios prestados'), $row);
							$row = str_replace('%TRABAJOS_ENCABEZADO%', $this->GenerarDocumentoComun($parser, 'TRABAJOS_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
							$row = str_replace('%TRABAJOS_FILAS%', $this->GenerarDocumentoComun($parser, 'TRABAJOS_FILAS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
							$row = str_replace('%TRABAJOS_TOTAL%', $this->GenerarDocumentoComun($parser, 'TRABAJOS_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
						} else {
							$row = str_replace('%espacio_trabajo%', '', $row);
							$row = str_replace('%servicios%', '', $row);
							$row = str_replace('%TRABAJOS_ENCABEZADO%', '', $row);
							$row = str_replace('%TRABAJOS_FILAS%', '', $row);
							$row = str_replace('%TRABAJOS_TOTAL%', '', $row);
						}
						$row = str_replace('%DETALLE_PROFESIONAL%', $this->GenerarDocumentoComun($parser, 'DETALLE_PROFESIONAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
					} else {
						$row = str_replace('%espacio_trabajo%', '', $row);
						$row = str_replace('%DETALLE_PROFESIONAL%', '', $row);
						$row = str_replace('%servicios%', '', $row);
						$row = str_replace('%TRABAJOS_ENCABEZADO%', '', $row);
						$row = str_replace('%TRABAJOS_FILAS%', '', $row);
						$row = str_replace('%TRABAJOS_TOTAL%', '', $row);
					}

					$query_hitos = "SELECT count(*) from cobro_pendiente where hito=1 and id_cobro=" . $this->fields['id_cobro'];
					$resp_hitos = mysql_query($query_hitos, $this->sesion->dbh) or Utiles::errorSQL($query_hitos, __FILE__, __LINE__, $this->sesion->dbh);

					list($cont_hitos) = mysql_fetch_array($resp_hitos);
					$row = str_replace('%hitos%', '<br>' . __('Hitos') . '<br/><br/>', $row);
					if ($cont_hitos > 0) {
						global $total_hitos, $estehito, $cantidad_hitos, $moneda_hitos, $tipo_cambio_hitos;

						$row = str_replace('%HITOS_FILAS%', $this->GenerarDocumentoComun($parser, 'HITOS_FILAS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
						$row = str_replace('%HITOS_TOTAL%', $this->GenerarDocumentoComun($parser, 'HITOS_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
						$row = str_replace('%HITOS_ENCABEZADO%', $this->GenerarDocumentoComun($parser, 'HITOS_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
						$row = str_replace('%hitos%', '<br>' . __('Hitos') . '(' . $estehito . ' de ' . $total_hitos . ')<br/><br/>', $row);
					} else {
						$row = str_replace('%hitos%', '', $row);
						$row = str_replace('%HITOS_ENCABEZADO%', '', $row);
						$row = str_replace('%HITOS_FILAS%', '', $row);
						$row = str_replace('%HITOS_TOTAL%', '', $row);
					}

					if ($cont_tramites > 0) {
						$row = str_replace('%espacio_tramite%', '<br>', $row);
						$row = str_replace('%servicios_tramites%', __('Tr�mites'), $row);
						$row = str_replace('%titulo_seccion_tramites_castropal%', __('Otros Servicios'), $row);
						$row = str_replace('%servicios_tramites_castropal%', __('Otros Servicios Profesionales'), $row);
						$row = str_replace('%TRAMITES_ENCABEZADO%', $this->GenerarDocumentoComun($parser, 'TRAMITES_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
						$row = str_replace('%TRAMITES_FILAS%', $this->GenerarDocumentoComun($parser, 'TRAMITES_FILAS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
						$row = str_replace('%TRAMITES_TOTAL%', $this->GenerarDocumentoComun($parser, 'TRAMITES_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
					} else {
						$row = str_replace('%espacio_tramite%', '', $row);
						$row = str_replace('%servicios_tramites%', '', $row);
						$row = str_replace('%titulo_seccion_tramites_castropal%', '', $row);
						$row = str_replace('%servicios_tramites_castropal%', '', $row);
						$row = str_replace('%TRAMITES_ENCABEZADO%', '', $row);
						$row = str_replace('%TRAMITES_FILAS%', '', $row);
						$row = str_replace('%TRAMITES_TOTAL%', '', $row);
					}
					if (Conf::GetConf($this->sesion, 'ParafoGastosSoloSiHayGastos')) {
						if ($cont_gastos > 0)
							$row = str_replace('%GASTOS%', $this->GenerarDocumentoComun($parser, 'GASTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
						else
							$row = str_replace('%GASTOS%', '', $row);
					} else
						$row = str_replace('%GASTOS%', $this->GenerarDocumentoComun($parser, 'GASTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);

					#especial mb
					$row = str_replace('%codigo_asunto_mb%', __('C�digo M&B'), $row);

					if ($asunto->fields['trabajos_total_duracion'] > 0 || $asunto->fields['trabajos_total_duracion_trabajada'] > 0 || $cont_tramites > 0 || Conf::GetConf($this->sesion, 'MostrarAsuntosSinTrabajosGastosTramites')) {
						$html .= $row;
					}
				}
				$arregloasuntos->closeCursor();
				break;

			//FFF DESGLOSE DE HITOS
			case 'HITOS_ENCABEZADO': //GenerarDocumentoComun
				global $total_hitos, $estehito, $cantidad_hitos, $moneda_hitos, $tipo_cambio_hitos;
				$html = str_replace('%fecha%', __('Fecha'), $html);
				$html = str_replace('%descripcion%', __('Descripci�n'), $html);
				$html = str_replace('%valor%', __('Valor') . ' ' . $moneda_hitos, $html);

				break;

			case 'HITOS_FILAS': //GenerarDocumentoComun
				global $total_hitos, $estehito, $cantidad_hitos, $moneda_hitos, $tipo_cambio_hitos;
				$query_hitos = "select * from (select  (select count(*) total from cobro_pendiente cp2 where cp2.id_contrato=cp.id_contrato) total,  @a:=@a+1 as rowid, round(if(cbr.id_cobro=cp.id_cobro, @a,0),0) as thisid,  ifnull(cp.fecha_cobro,0) as fecha_cobro, cp.descripcion, cp.monto_estimado, pm.simbolo, pm.codigo, pm.tipo_cambio  FROM `cobro_pendiente` cp join  contrato c on (c.id_contrato = cp.id_contrato) join prm_moneda pm on (pm.id_moneda = cp.id_moneda) join cobro cbr on (cbr.id_contrato = cp.id_contrato)  join (select @a:=0) FFF
					where cp.hito=1 and cbr.id_cobro=" . $this->fields['id_cobro'] . ") hitos where hitos.thisid!=0 ";


				$resp_hitos = mysql_query($query_hitos, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				$row_tmpl = $html;
				$html = '';
				while ($hitos = mysql_fetch_array($resp_hitos)) {
					$row = $row_tmpl;
					$row = str_replace('%fecha%', ($hitos['fecha_cobro'] == 0 ? '' : date('d-m-Y', strtotime($hitos['fecha_cobro']))), $row);
					$row = str_replace('%descripcion%', $hitos['descripcion'], $row);
					$total_hitos = $total_hitos + $hitos['monto_estimado'];
					$moneda_hitos = $hitos['simbolo'];
					$estehito = $hitos['thisid'];
					$cantidad_hitos = $hitos['total'];
					$tipo_cambio_hitos = $hitos['tipo_cambio'];
					$row = str_replace('%valor_hitos%', $hitos['monto_estimado'] . ' ' . $moneda_hitos, $row);
					$html .= $row;
				}

				break;

			case 'HITOS_TOTAL': //GenerarDocumentoComun
				global $total_hitos, $estehito, $cantidad_hitos, $moneda_hitos, $tipo_cambio_hitos;

				$html = str_replace('%total%', __('Total'), $html);
				$html = str_replace('%total_hitos%', $total_hitos . ' ' . $moneda_hitos, $html);

				break;

			case 'TRAMITES_ENCABEZADO': //GenerarDocumentoComun

				$html = str_replace('%tramites%', __('Tr�mites'), $html);
				$html = str_replace('%tramites_castropal%', __('Otros Servicios'), $html);
				$html = str_replace('%solicitante%', __('Solicitado Por'), $html);
				$html = str_replace('%ordenado_por%', $this->fields['opc_ver_solicitante'] ? __('Ordenado Por') : '', $html);
				$html = str_replace('%periodo%', (($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') and ( $this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '')) ? '' : __('Periodo'), $html);
				$html = str_replace('%valor_periodo_ini%', ($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') ? '' : Utiles::sql2fecha($this->fields['fecha_ini'], $idioma->fields['formato_fecha']), $html);
				$html = str_replace('%valor_periodo_fin%', ($this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '') ? '' : __('hasta') . ' ' . Utiles::sql2fecha($this->fields['fecha_fin'], $idioma->fields['formato_fecha']), $html);
				$html = str_replace('%cliente%', __('Cliente'), $html);
				$html = str_replace('%glosa_cliente%', $cliente->fields['glosa_cliente'], $html);
				$html = str_replace('%asunto%', __('Asunto'), $html);
				$html = str_replace('%glosa_asunto%', $asunto->fields['glosa_asunto'], $html);
				$html = str_replace('%fecha%', __('Fecha'), $html);
				$html = str_replace('%descripcion%', __('Descripci�n'), $html);
				$html = str_replace('%servicios_prestados%', __('Servicios Prestados'), $html);
				$html = str_replace('%servicios_tramites%', __('Tr�mites'), $html);
				$html = str_replace('%servicios_tramites_castropal%', 'Otros Servicios Profesionales', $html);
				$html = str_replace('%detalle_trabajo%', __('Detalle del Tr�mite Realizado'), $html);
				$html = str_replace('%profesional%', __('Profesional'), $html);
				$html = str_replace('%abogado%', __('Abogado'), $html);
				$html = str_replace('%horas%', __('Horas'), $html);
				$html = str_replace('%cobrable%', __('<td align="center" width="80">Cobrable</td>'), $html);

				if (Conf::GetConf($this->sesion, 'OrdenarPorCategoriaUsuario')) {
					if ($lang == 'es') {
						$query_categoria_lang = "cat.glosa_categoria";
					} else {
						$query_categoria_lang = "IFNULL(cat.glosa_categoria_lang , cat.glosa_categoria)";
					}

					$query = "SELECT $query_categoria_lang
								FROM trabajo
									JOIN usuario ON trabajo.id_usuario=usuario.id_usuario
									JOIN prm_categoria_usuario AS cat ON cat.id_categoria_usuario=usuario.id_categoria_usuario
										WHERE trabajo.id_cobro = '" . $this->fields['id_cobro'] . "'
											AND trabajo.codigo_asunto = '" . $asunto->fields['codigo_asunto'] . "'
											AND trabajo.visible=1
											ORDER BY cat.orden, usuario.id_usuario, trabajo.fecha ASC
												LIMIT 1";

					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
					list($categoria) = mysql_fetch_array($resp);

					$html = str_replace('%categoria_abogado%', __($categoria), $html);
				} else
					$html = str_replace('%categoria_abogado%', '', $html);

				//Por conf se ve si se imprime la duracion trabajada cuando el cobro este en estado creado tambien
				//Por conf se ve si se imprime o no el valor del trabajo
				$html = str_replace('%duracion_tramites%', __('Duraci�n'), $html);
				$html = str_replace('%valor_tramites%', __('Valor'), $html);
				$html = str_replace('%valor%', __('Valor'), $html);
				$html = str_replace('%valor_siempre%', __('Valor'), $html);
				$html = str_replace('%tarifa_fee%', __('%tarifa_fee%'), $html);
				break;

			case 'TRAMITES_FILAS': //GenerarDocumentoComun
				global $categoria_duracion_horas;
				global $categoria_duracion_minutos;
				global $categoria_valor;

				$row_tmpl = $html;
				$html = '';

				if ($lang == 'es') {
					$select_categoria = ", prm_categoria_usuario.glosa_categoria AS categoria, prm_categoria_usuario.id_categoria_usuario";
				} else {
					$select_categoria = ", IFNULL(prm_categoria_usuario.glosa_categoria_lang,prm_categoria_usuario.glosa_categoria) AS categoria, prm_categoria_usuario.id_categoria_usuario";
				}

				$join_categoria = "LEFT JOIN prm_categoria_usuario ON usuario.id_categoria_usuario=prm_categoria_usuario.id_categoria_usuario";

				if (Conf::GetConf($this->sesion, 'TramitesOrdenarPorCategoriaNombreUsuario')) {
					$order_categoria = "prm_categoria_usuario.orden, usuario.nombre, usuario.apellido1, usuario.id_usuario, ";
				} else if (Conf::GetConf($this->sesion, 'TramitesOrdenarPorCategoriaUsuario')) {
					$order_categoria = "prm_categoria_usuario.orden, usuario.id_usuario, ";
				} else if (Conf::GetConf($this->sesion, 'TramitesOrdenarPorCategoriaDetalleProfesional')) {
					$select_categoria = "";
					$order_categoria = "usuario.id_categoria_usuario DESC, ";
				} else if (Conf::GetConf($this->sesion, 'TramitesOrdenarPorFechaCategoria')) {
					$join_categoria = "LEFT JOIN prm_categoria_usuario ON usuario.id_categoria_usuario=prm_categoria_usuario.id_categoria_usuario";
					$order_categoria = "tramite.fecha, usuario.id_categoria_usuario, usuario.id_usuario, ";
				} else {
					$select_categoria = "";
					$join_categoria = "";
					$order_categoria = "";
				}

				$query_lista_tramites = "
					SELECT SQL_CALC_FOUND_ROWS
						tramite.duracion,
						tramite_tipo.glosa_tramite as glosa_tramite,
						tramite.descripcion,
						tramite.fecha,
						tramite.id_usuario,
						tramite.id_tramite,
						tramite.solicitante,
						tramite.tarifa_tramite as tarifa,
						tramite.codigo_asunto,
						tramite.id_moneda_tramite,
						concat(left(usuario.nombre,1), left(usuario.apellido1,1), left(usuario.apellido2,1)) as iniciales,
						CONCAT_WS(' ', nombre, apellido1) as nombre_usuario $select_categoria, usuario.username
					FROM tramite
						JOIN asunto ON asunto.codigo_asunto=tramite.codigo_asunto
						JOIN contrato ON asunto.id_contrato=contrato.id_contrato
						JOIN tramite_tipo ON tramite.id_tramite_tipo=tramite_tipo.id_tramite_tipo
						LEFT JOIN usuario ON tramite.id_usuario=usuario.id_usuario
						$join_categoria
							WHERE tramite.id_cobro = '" . $this->fields['id_cobro'] . "'
								AND tramite.codigo_asunto = '" . $asunto->fields['codigo_asunto'] . "'
								AND tramite.cobrable=1
								AND tramite.fecha BETWEEN '" . $this->fields['fecha_ini'] . "' AND '" . $this->fields['fecha_fin'] . "'
						ORDER BY $order_categoria tramite.fecha ASC,tramite.descripcion";

				$lista_tramites = new ListaTramites($this->sesion, '', $query_lista_tramites);

				$asunto->fields['tramites_total_duracion'] = 0;
				$asunto->fields['tramites_total_valor'] = 0;

				if ($lista_tramites->num == 0) {
					$row = $row_tmpl;
					$row = str_replace('%iniciales%', '&nbsp;', $row);
					$row = str_replace('%fecha%', '&nbsp;', $row);
					$row = str_replace('%descripcion%', __('No hay tr�mites en este asunto'), $row);
					$row = str_replace('%valor%', '&nbsp;', $row);
					$row = str_replace('%duracion_tramites%', '&nbsp;', $row);
					$row = str_replace('%valor_tramites%', '&nbsp;', $row);
					$html .= $row;
				}

				for ($i = 0; $i < $lista_tramites->num; $i++) {
					$tramite = $lista_tramites->Get($i);
					list($h, $m, $s) = explode(":", $tramite->fields['duracion']);
					$asunto->fields['tramites_total_duracion'] += $h * 60 + $m + $s / 60;
					$asunto->fields['tramites_total_valor'] += $tramite->fields['tarifa'];
					$categoria_duracion_horas+=round($h);
					$categoria_duracion_minutos+=round($m);
					$categoria_valor+=$tramite->fields['tarifa'];

					$row = $row_tmpl;
					$row = str_replace('%fecha%', Utiles::sql2fecha($tramite->fields['fecha'], $idioma->fields['formato_fecha']), $row);
					$row = str_replace('%descripcion%', ucfirst(stripslashes($tramite->fields['glosa_tramite'] . '<br>' . $tramite->fields['descripcion'])), $row);
					$row = str_replace('%tramite_glosa%', ucfirst(stripslashes($tramite->fields['glosa_tramite'])), $row);
					$row = str_replace('%tramite_descripcion%', ucfirst(stripslashes($tramite->fields['descripcion'])), $row);

					$row = str_replace('%solicitante%', $this->fields['opc_ver_solicitante'] ? $tramite->fields['solicitante'] : '', $row);

					//muestra las iniciales de los profesionales
					list($nombre, $apellido_paterno, $extra, $extra2) = explode(' ', $tramite->fields['nombre_usuario'], 4);
					$row = str_replace('%iniciales%', $nombre[0] . $apellido_paterno[0] . $extra[0] . $extra2[0], $row);
					$row = str_replace('%username%', $tramite->fields['username'], $row);

					if ($this->fields['opc_ver_detalles_por_hora_iniciales'] == 1) {
						$row = str_replace('%profesional%', $tramite->fields['iniciales'], $row);
					} else {
						$row = str_replace('%profesional%', $tramite->fields['nombre_usuario'], $row);
					}


					list($ht, $mt, $st) = explode(":", $tramite->fields['duracion']);
					$asunto->fields['tramites_total_duracion_trabajado'] += $ht * 60 + $mt + $st / 60;
					$asunto->fields['trabajos_total_duracion_trabajada'] += $ht * 60 + $mt + $st / 60;
					$duracion_decimal_trabajada = $ht + $mt / 60 + $st / 3600;

					$ImprimirDuracionTrabajada = Conf::GetConf($this->sesion, 'ImprimirDuracionTrabajada');

					$saldo = $tramite->fields['tarifa'];
					$monto_tramite = $saldo;
					$monto_tramite_moneda_total = $saldo * ($cobro_moneda->moneda[$tramite->fields['id_moneda_tramite']]['tipo_cambio'] / $cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']);
					$totales['total_tramites'] += $saldo;

					$minutos_decimal = $m / 60;
					$duracion_decimal = $h + $minutos_decimal + $s / 3600;
					$row = str_replace('%duracion_decimal%', number_format($duracion_decimal, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					$row = str_replace('%duracion%', $h . ':' . $m, $row);
					$row = str_replace('%duracion_tramites%', $h . ':' . $m, $row);

					$row = str_replace('%valor%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . $this->espacio . number_format($saldo, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					$row = str_replace('%valor_siempre%', number_format($tramite->fields['tarifa'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					$row = str_replace('%valor_tramites%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . $this->espacio . number_format($saldo, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

					if (Conf::GetConf($this->sesion, 'TramitesOrdenarPorCategoriaUsuario')) {

						$tramite_siguiente = $lista_tramites->Get($i + 1);

						if (!empty($tramite_siguiente->fields['id_categoria_usuario'])) {

							if ($tramite->fields['id_categoria_usuario'] != $tramite_siguiente->fields['id_categoria_usuario']) {

								$html3 = $parser->tags['TRAMITES_TOTAL'];
								$html3 = str_replace('%glosa%', __('Total'), $html3);
								$categoria_duracion_horas += floor($categoria_duracion_minutos / 60);
								$categoria_duracion_minutos = round($categoria_duracion_minutos % 60);
								$html3 = str_replace('%duracion%', sprintf('%02d', $categoria_duracion_horas) . ':' . sprintf('%02d', $categoria_duracion_minutos), $html3);


								if ((Conf::GetConf($this->sesion, 'NoImprimirValorTrabajo')) && ($this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION')) {
									$html3 = str_replace('%valor%', '', $html3);
								} else {
									$html3 = str_replace('%valor%', $moneda->fields['simbolo'] . $this->espacio . number_format($categoria_valor, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
								}

								$total_tramites_categoria .= $html3;

								$html3 = $parser->tags['TRAMITES_ENCABEZADO'];
								$html3 = str_replace('%duracion%', __('Duraci�n'), $html3);
								$html3 = str_replace('%cobrable%', __('<td align="center" width="80">Cobrable</td>'), $html3);
								$html3 = str_replace('%fecha%', __('Fecha'), $html3);
								$html3 = str_replace('%descripcion%', __('Descripci�n'), $html3);
								$html3 = str_replace('%profesional%', __('Profesional'), $html3);
								$html3 = str_replace('%abogado%', __('Abogado'), $html3);
								$html3 = str_replace('%categoria_abogado%', __($tramite_siguiente->fields['categoria']), $html3);
								if (Conf::GetConf($this->sesion, 'NoImprimirValorTrabajo') && $this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION')
									$html3 = str_replace('%valor%', '', $html3);
								else
									$html3 = str_replace('%valor%', __('Valor'), $html3);
								$encabezado_tramites_categoria .= $html3;

								$row = str_replace('%TRAMITES_CATEGORIA%', $total_tramites_categoria . $encabezado_tramites_categoria, $row);
								$categoria_duracion_horas = 0;
								$categoria_duracion_minutos = 0;
								$categoria_valor = 0;
								$total_trabajos_categoria = '';
								$encabezado_trabajos_categoria = '';
							}
							else {
								$row = str_replace('%TRAMITES_CATEGORIA%', '', $row);
							}
						} else {
							$html3 = $parser->tags['TRAMITES_TOTAL'];
							$html3 = str_replace('%glosa%', __('Total'), $html3);
							$categoria_duracion_horas += floor($categoria_duracion_minutos / 60);
							$categoria_duracion_minutos = round($categoria_duracion_minutos % 60);
							$html3 = str_replace('%duracion%', sprintf('%02d', $categoria_duracion_horas) . ':' . sprintf('%02d', $categoria_duracion_minutos), $html3);

							if ($this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION' && Conf::GetConf($this->sesion, 'NoImprimirValorTrabajo')) {
								$html3 = str_replace('%valor%', '', $html3);
							} else {
								$html3 = str_replace('%valor%', $moneda->fields['simbolo'] . $this->espacio . number_format($categoria_valor, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
							}

							$total_tramites_categoria .= $html3;
							$row = str_replace('%TRAMITES_CATEGORIA%', $total_tramites_categoria, $row);
							$categoria_duracion_horas = 0;
							$categoria_duracion_minutos = 0;
							$categoria_valor = 0;
							$total_tramites_categoria = '';
							$encabezado_tramites_categoria = '';
						}
					}
					$html .= $row;
				}
				break;

			case 'TRAMITES_TOTAL': //GenerarDocumentoComun
				$horas_cobrables_tramites = floor(($asunto->fields['tramites_total_duracion_trabajado']) / 60);
				$minutos_cobrables_tramites = sprintf("%02d", $asunto->fields['tramites_total_duracion_trabajado'] % 60);
				$horas_cobrables = floor(($asunto->fields['trabajos_total_duracion_trabajada']) / 60);
				$minutos_cobrables = sprintf("%02d", $asunto->fields['trabajos_total_duracion_trabajada'] % 60);

				$html = str_replace('%glosa_tramites%', __('Total ' . __('Tr�mites')), $html);
				$html = str_replace('%glosa_tramites_castropal%', __('Total otros servicios'), $html);
				$html = str_replace('%glosa%', __('Total'), $html);
				$minutos_decimal = $minutos_cobrables / 60;
				$duracion_decimal = $horas_cobrables + $minutos_decimal;
				$html = str_replace('%duracion_decimal%', number_format($duracion_decimal, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%duracion_tramites%', $horas_cobrables_tramites . ':' . $minutos_cobrables_tramites, $html);
				$html = str_replace('%duracion%', $horas_cobrables . ':' . $minutos_cobrables, $html);

				$html = str_replace('%valor_tramites%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . $this->espacio . number_format($totales['total_tramites'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_siempre%', $moneda->fields['simbolo'] . $this->espacio . number_format($asunto->fields['tramites_total_valor'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				break;

			case 'TRABAJOS_ENCABEZADO': //GenerarDocumentoComun
				if ($this->fields['estado'] == 'CREADO' || $this->fields['estado'] == 'EN REVISION') {
					$html = str_replace('%td_id_trabajo%', '<td align="center">%ntrabajo%</td>', $html);
				} else {
					$html = str_replace('%td_id_trabajo%', '', $html);
				}
				$html = str_replace('%ntrabajo%', __('N�</br>Trabajo'), $html);
				if ($this->fields['opc_ver_solicitante']) {
					$html = str_replace('%td_solicitante%', '<td width="16%" align="left">%solicitante%</td>', $html);
				} else {
					$html = str_replace('%td_solicitante%', '', $html);
				}
				$html = str_replace('%solicitante%', __('Solicitado Por'), $html);
				if ($lang == 'es') {
					$html = str_replace('%id_asunto%', __('ID Asunto'), $html);
					$html = str_replace('%tarifa_hora%', __('Tarifa<br>Hora'), $html);
				} else {
					$html = str_replace('%id_asunto%', __('Matter <br> ID'), $html);
					$html = str_replace('%tarifa_hora%', __('Hourly<br> Rate'), $html);
				}
				$html = str_replace('%importe%', __('Importe'), $html);
				$html = str_replace('%tarifa_hora%', __('Tarifa Hora'), $html);
				$html = str_replace('%ordenado_por%', $this->fields['opc_ver_solicitante'] ? __('Ordenado Por') : '', $html);
				$html = str_replace('%ordenado_por_jjr%', $this->fields['opc_ver_solicitante'] ? __('Solicitado Por') : '', $html);
				$html = str_replace('%periodo%', (($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') and ( $this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '')) ? '' : __('Periodo'), $html);
				$html = str_replace('%valor_periodo_ini%', ($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') ? '' : Utiles::sql2fecha($this->fields['fecha_ini'], $idioma->fields['formato_fecha']), $html);
				$html = str_replace('%valor_periodo_fin%', ($this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '') ? '' : __('hasta') . ' ' . Utiles::sql2fecha($this->fields['fecha_fin'], $idioma->fields['formato_fecha']), $html);
				$html = str_replace('%cliente%', __('Cliente'), $html);
				$html = str_replace('%glosa_cliente%', $cliente->fields['glosa_cliente'], $html);
				$html = str_replace('%asunto%', __('Asunto'), $html);
				$html = str_replace('%glosa_asunto%', $asunto->fields['glosa_asunto'], $html);
				$html = str_replace('%fecha%', __('Fecha'), $html);
				$html = str_replace('%descripcion%', __('Descripci�n'), $html);
				$html = str_replace('%servicios_prestados%', __('Servicios Prestados'), $html);
				$html = str_replace('%detalle_trabajo%', __('Detalle del Trabajo Realizado'), $html);
				$html = str_replace('%profesional%', __('Profesional'), $html);
				$html = str_replace('%duracion_cobrable%', __('Duraci�n cobrable'), $html);
				$html = str_replace('%monto_total%', __('Monto total'), $html);
				$html = str_replace('%staff%', __('Staff'), $html);
				$html = str_replace('%abogado%', __('Abogado'), $html);
				$html = str_replace('%abogado_raz%', __('abogado_raz'), $html);
				$html = str_replace('%tiempo_raz%', __('tiempo_raz'), $html);
				$html = str_replace('%horas%', __('Horas'), $html);
				$html = str_replace('%monto%', __('Monto'), $html);

				if ($this->fields['opc_ver_columna_cobrable'])
					$html = str_replace('%cobrable%', __('<td align="center" width="80">Cobrable</td>'), $html);  // tAndres Oestemer
				else
					$html = str_replace('%cobrable%', '', $html);

				if (Conf::GetConf($this->sesion, 'TrabajosOrdenarPorCategoriaUsuario')) {

					if ($lang == 'es') {
						$query_categoria_lang = "cat.glosa_categoria";
					} else {
						$query_categoria_lang = "IFNULL(cat.glosa_categoria_lang , cat.glosa_categoria)";
					}

					$query = "SELECT $query_categoria_lang
									FROM trabajo
									JOIN usuario ON trabajo.id_usuario=usuario.id_usuario
									JOIN prm_categoria_usuario AS cat ON cat.id_categoria_usuario=usuario.id_categoria_usuario
									WHERE trabajo.id_cobro = '" . $this->fields['id_cobro'] . "'
									AND trabajo.codigo_asunto = '" . $asunto->fields['codigo_asunto'] . "'
									AND trabajo.visible=1
									ORDER BY cat.orden, usuario.id_usuario, trabajo.fecha ASC
									LIMIT 1";
					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
					list($categoria) = mysql_fetch_array($resp);
					$html = str_replace('%categoria_abogado%', __($categoria), $html);
				} else if (Conf::GetConf($this->sesion, 'SepararPorUsuario')) {
					$query = "SELECT CONCAT(usuario.nombre,' ',usuario.apellido1),trabajo.tarifa_hh
									FROM trabajo
									JOIN usuario ON trabajo.id_usuario=usuario.id_usuario
									WHERE trabajo.id_cobro = '" . $this->fields['id_cobro'] . "'
									AND trabajo.codigo_asunto = '" . $asunto->fields['codigo_asunto'] . "'
									AND trabajo.visible=1
									ORDER BY usuario.id_categoria_usuario, usuario.id_usuario, trabajo.fecha ASC
									LIMIT 1";
					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
					list($abogado, $tarifa) = mysql_fetch_array($resp);
					$html = str_replace('%categoria_abogado%', __($abogado), $html);

					$html = str_replace('%tarifa%', $moneda->fields['simbolo'] . $this->espacio . number_format($tarifa, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				} else {
					$html = str_replace('%categoria_abogado%', '', $html);
				}

				//Por conf se ve si se imprime la duracion trabajada cuando el cobro este en estado creado tambien
				$ImprimirDuracionTrabajada = Conf::GetConf($this->sesion, 'ImprimirDuracionTrabajada');

				/* Lo anchores con la extension _bmahj usa Bofill Mir y lo que hace es que llama a las columnas
				  en la lista de trabajos igual como a las columnas en el resumen profesional */

				if ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') {
					$html = str_replace('%td_retainer%', '<td width="80" align="center">%duracion_retainer%</td>', $html);
					$html = str_replace('%td_sobre_retainer%', '<td width="80" align="center">%duracion_sobre_retainer%</td>', $html);
					$html = str_replace('%duracion_retainer%', __('Duraci�n Retainer'), $html);
					$html = str_replace('%duracion_sobre_retainer%', __('Duraci�n Tarificada'), $html);
				} else {
					$html = str_replace('%td_retainer%', '', $html);
					$html = str_replace('%td_sobre_retainer%', '', $html);
				}

				if ($this->fields['forma_cobro'] == 'FLAT FEE') {
					$html = str_replace('%duracion_trabajada_bmahj%', '', $html);
					$html = str_replace('%duracion_descontada_bmahj%', '', $html);
					$html = str_replace('%duracion_bmahj%', __('Hrs. Trabajadas'), $html);
					$html = str_replace('%tiempo%', __('Tiempo'), $html);

					$html = str_replace('%duracion_trabajada%', '', $html);
					$html = str_replace('%duracion_descontada%', '', $html);
					$html = str_replace('%duracion%', __('Duraci�n trabajada'), $html);
				}
				if ($ImprimirDuracionTrabajada && ( $this->fields['estado'] == 'CREADO' || $this->fields['estado'] == 'EN REVISION' )) {
					$html = str_replace('%duracion_trabajada_bmahj%', __('Hrs. Trabajadas'), $html);
					$html = str_replace('%duracion_bmahj%', __('Hrs. Tarificadas'), $html);
					$html = str_replace('%tiempo%', __('Tiempo'), $html);
					$html = str_replace('%duracion_descontada_bmahj%', __('Hrs. Castigadas'), $html);
					$html = str_replace('%duracion_descontada%', __('Hrs.:Mins. Descontadas'), $html);

					$html = str_replace('%duracion_trabajada%', __('Duraci�n trabajada'), $html);
					$html = str_replace('%duracion%', __('Duraci�n cobrable'), $html);
					if ($descontado)
						$html = str_replace('%duracion_descontada%', __('Duraci�n descontada'), $html);
					else
						$html = str_replace('%duracion_descontada%', '', $html);
				}
				else if ($this->fields['opc_ver_horas_trabajadas']) {
					$html = str_replace('%duracion_trabajada_bmahj%', __('Hrs. Trabajadas'), $html);
					$html = str_replace('%duracion_bmahj%', __('Hrs. Tarificadas'), $html);
					$html = str_replace('%tiempo%', __('Tiempo'), $html);
					$html = str_replace('%duracion_descontada_bmahj%', __('Hrs. Castigadas'), $html);
					$html = str_replace('%duracion_descontada%', __('Hrs.:Mins. Descontadas'), $html);

					$html = str_replace('%duracion_trabajada%', __('Duraci�n trabajada'), $html);
					$html = str_replace('%duracion%', __('Duraci�n cobrable'), $html);
					$html = str_replace('%duracion_descontada%', __('Duraci�n castigada'), $html);
				} else {
					$html = str_replace('%duracion_trabajada_bmahj%', '', $html);
					$html = str_replace('%duracion_descontada_bmahj%', '', $html);
					$html = str_replace('%duracion_bmahj%', __('Hrs. Tarificadas'), $html);
					$html = str_replace('%tiempo%', __('Tiempo'), $html);

					$html = str_replace('%duracion_trabajada%', '', $html);
					$html = str_replace('%duracion_descontada%', '', $html);
					$html = str_replace('%duracion%', __('Duraci�n'), $html);
				}
				$html = str_replace('%duracion_tyc%', __('Duraci�n'), $html);
				//Por conf se ve si se imprime o no el valor del trabajo
				$ImprimirValorTrabajo = Conf::GetConf($this->sesion, 'ImprimirValorTrabajo');

				if ($ImprimirValorTrabajo && $this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION')
					$html = str_replace('%valor%', '', $html);
				else
					$html = str_replace('%valor%', __('Valor'), $html);
				$html = str_replace('%valor_siempre%', __('Valor'), $html);
				$html = str_replace('%tarifa_fee%', __('%tarifa_fee%'), $html);

				if ($this->fields['opc_ver_detalles_por_hora_categoria'] == 1)
					$html = str_replace('%td_categoria%', '<td class="td_categoria" align="left">%categoria%</td>', $html);
				else
					$html = str_replace('%td_categoria%', '', $html);
				$html = str_replace('%categoria%', __($this->fields['codigo_idioma'] . '_Categor�a'), $html);

				if ($this->fields['opc_ver_detalles_por_hora_tarifa'] == 1) {
					$html = str_replace('%td_tarifa%', '<td width="80" align="center">%tarifa%</td>', $html);
					$html = str_replace('%td_tarifa_ajustada%', '<td width="80" align="center">%tarifa%</td>', $html);
				} else {
					$html = str_replace('%td_tarifa%', '', $html);
					$html = str_replace('%td_tarifa_ajustada%', '', $html);
				}
				$html = str_replace('%tarifa%', __('Tarifa'), $html);

				if ($this->fields['opc_ver_detalles_por_hora_importe'] == 1) {
					$html = str_replace('%td_importe%', '<td width="80" align="center">%importe%</td>', $html);
					$html = str_replace('%td_importe_ajustado%', '<td width="80" align="center">%importe%</td>', $html);
				} else {
					$html = str_replace('%td_importe%', '', $html);
					$html = str_replace('%td_importe_ajustado%', '', $html);
				}
				$html = str_replace('%importe%', __($this->fields['codigo_idioma'] . '_Importe'), $html);
				break;

			case 'TRABAJOS_FILAS': //GenerarDocumentoComun
				global $categoria_duracion_horas;
				global $categoria_duracion_minutos;
				global $categoria_valor;
				global $profesionales;
				$row_tmpl = $html;
				$html = '';
				$where_horas_cero = '';

				if ($lang == 'es') {
					$select_categoria = ", prm_categoria_usuario.glosa_categoria AS categoria, prm_categoria_usuario.id_categoria_usuario";
				} else {
					$select_categoria = ", IFNULL(prm_categoria_usuario.glosa_categoria_lang, prm_categoria_usuario.glosa_categoria) AS categoria, prm_categoria_usuario.id_categoria_usuario";
				}

				$join_categoria = "LEFT JOIN prm_categoria_usuario ON usuario.id_categoria_usuario=prm_categoria_usuario.id_categoria_usuario";

				//esto funciona por Conf si el metodo del conf OrdenarPorCategoriaUsuario es true se ordena por categoria
				if (Conf::GetConf($this->sesion, 'TrabajosOrdenarPorCategoriaNombreUsuario')) {
					$order_categoria = "prm_categoria_usuario.orden, usuario.nombre, usuario.apellido1, usuario.id_usuario, ";
				} else if (Conf::GetConf($this->sesion, 'TrabajosOrdenarPorCategoriaUsuario')) {
					$order_categoria = "prm_categoria_usuario.orden, usuario.id_usuario, ";
				} else if (Conf::GetConf($this->sesion, 'SepararPorUsuario')) {
					$order_categoria = "usuario.id_categoria_usuario, usuario.id_usuario, ";
				} else if (Conf::GetConf($this->sesion, 'TrabajosOrdenarPorCategoriaDetalleProfesional')) {
					$select_categoria = "";
					$order_categoria = "usuario.id_categoria_usuario DESC, ";
				} else if (Conf::GetConf($this->sesion, 'TrabajosOrdenarPorFechaCategoria')) {
					$order_categoria = "trabajo.fecha, usuario.id_categoria_usuario, usuario.id_usuario, ";
				} else {
					$select_categoria = "";
					$join_categoria = "";
					$order_categoria = "";
				}

				if (!method_exists('Conf', 'MostrarHorasCero')) {
					if ($this->fields['opc_ver_horas_trabajadas']) {
						$where_horas_cero = "AND trabajo.duracion > '0000-00-00 00:00:00'";
					} else {
						$where_horas_cero = "AND trabajo.duracion_cobrada > '0000-00-00 00:00:00'";
					}
				}

				if ($this->fields['opc_ver_valor_hh_flat_fee'] && $this->fields['forma_cobro'] != 'ESCALONADA') {
					$dato_monto_cobrado = " ( trabajo.tarifa_hh * TIME_TO_SEC( trabajo.duracion_cobrada ) ) / 3600 ";
				} else {
					$dato_monto_cobrado = " trabajo.monto_cobrado ";
				}

				if ($this->fields['opc_ver_cobrable']) {
					$and .= "";
				} else {
					$and .= "AND trabajo.visible = 1";
				}

				if ($lang == 'es') {
					$query_categoria_lang = "prm_categoria_usuario.glosa_categoria AS categoria,";
				} else {
					$query_categoria_lang = "IFNULL(prm_categoria_usuario.glosa_categoria_lang ,prm_categoria_usuario.glosa_categoria) AS categoria,";
				}

				//Tabla de Trabajos.
				//se hace select a los visibles y cobrables para diferenciarlos, tambien se selecciona
				//la duracion retainer.
				$query = "SELECT SQL_CALC_FOUND_ROWS
									trabajo.duracion_cobrada,
									trabajo.duracion_retainer,
									trabajo.duracion_cobrada-trabajo.duracion_retainer as duracion_tarificada,
									trabajo.descripcion,
									trabajo.fecha,
									trabajo.id_usuario,
									$dato_monto_cobrado as monto_cobrado,
									trabajo.visible,
									trabajo.cobrable,
									trabajo.id_trabajo,
									trabajo.tarifa_hh,
									trabajo.tarifa_hh * ( TIME_TO_SEC( duracion_cobrada ) / 3600 ) as importe,

									trabajo.codigo_asunto,
									trabajo.solicitante,
									$query_categoria_lang
									CONCAT_WS(' ', nombre, apellido1) as nombre_usuario,
									trabajo.duracion,
									usuario.username as username $select_categoria
							FROM trabajo
							LEFT JOIN usuario ON trabajo.id_usuario=usuario.id_usuario
							LEFT JOIN cobro ON cobro.id_cobro = trabajo.id_cobro
							LEFT JOIN prm_categoria_usuario ON usuario.id_categoria_usuario=prm_categoria_usuario.id_categoria_usuario
							WHERE trabajo.id_cobro = '" . $this->fields['id_cobro'] . "'
							AND trabajo.codigo_asunto = '" . $asunto->fields['codigo_asunto'] . "'
							$and AND trabajo.id_tramite=0 $where_horas_cero
							ORDER BY $order_categoria trabajo.fecha ASC,trabajo.descripcion";

				$lista_trabajos = new ListaTrabajos($this->sesion, '', $query);

				$asunto->fields['trabajos_total_duracion'] = 0;
				$asunto->fields['trabajos_total_valor'] = 0;
				$asunto->fields['trabajos_total_duracion_retainer'] = 0;
				$asunto->fields['trabajos_total_importe'] = 0;



				for ($i = 0; $i < $lista_trabajos->num; $i++) {
					$trabajo = $lista_trabajos->Get($i);
					list($ht, $mt, $st) = explode(":", $trabajo->fields['duracion']);
					list($h, $m, $s) = explode(":", $trabajo->fields['duracion_cobrada']);
					list($h_retainer, $m_retainer, $s_retainer) = explode(":", $trabajo->fields['duracion_retainer']);
					$duracion_cobrada_decimal = $h + $m / 60 + $s / 3600;
					$asunto->fields['trabajos_total_duracion'] += $h * 60 + $m + $s / 60;
					$asunto->fields['trabajos_total_valor'] += $trabajo->fields['monto_cobrado'];
					$asunto->fields['trabajos_total_duracion_retainer'] += $h_retainer * 60 + $m_retainer + $s_retainer / 60;
					$asunto->fields['trabajos_total_duracion_sobre_retainer'] += ($h_retainer - $h) * 60 + ($m_retainer - $m) + ($s_retainer - $s) / 60;

					$asunto->fields['trabajos_total_importe'] += $trabajo->fields['importe'];
					$asunto->fields['trabajos_total_duracion_trabajada'] += $ht * 60 + $mt + $st / 60;
					$duracion_decimal_trabajada = $ht + $mt / 60 + $st / 3600;
					$duracion_decimal_descontada = $ht - $h + ($mt - $m) / 60 + ($st - $s) / 3600;
					$duracion_decimal_retainer = $h_retainer + $m_retainer / 60 + $s_retainer / 3600;
					$duracion_decimal_sobre_retainer = ($h - $h_retainer) + ($m - $m_retainer) / 60 + ($s - $s_retainer) / 3600;
					$minutos_decimal = $m / 60;
					$duracion_decimal = $h + $minutos_decimal + $s / 3600;

					if (($mt - $m) < 0) {
						$horas_descontadas = $ht - $h - 1;
						$minutos_descontadas = $mt - $m + 60;
					} else {
						$horas_descontadas = $ht - $h;
						$minutos_descontadas = $mt - $m;
					}

					$categoria_duracion_horas+=round($h);
					$categoria_duracion_minutos+=round($m);
					$categoria_valor+=$trabajo->fields['monto_cobrado'];

					if (!isset($profesionales[$trabajo->fields['nombre_usuario']])) {
						$profesionales[$trabajo->fields['nombre_usuario']] = array();
						$profesionales[$trabajo->fields['nombre_usuario']]['tiempo_trabajado_real'] = 0; // horas realmente trabajadas segun duracion en vez de duracion_cobrada
						$profesionales[$trabajo->fields['nombre_usuario']]['tiempo_trabajado'] = 0; //el tiempo trabajado es cobrable y no cobrable
						$profesionales[$trabajo->fields['nombre_usuario']]['tiempo'] = 0; //tiempo cobrable
						$profesionales[$trabajo->fields['nombre_usuario']]['valor'] = 0;
						$profesionales[$trabajo->fields['nombre_usuario']]['retainer'] = 0;
						$profesionales[$trabajo->fields['nombre_usuario']]['flatfee'] = 0;
						$profesionales[$trabajo->fields['nombre_usuario']]['descontado_real'] = 0;
						$profesionales[$trabajo->fields['nombre_usuario']]['descontado'] = 0; //tiempo no cobrable
						$profesionales[$trabajo->fields['nombre_usuario']]['tarifa'] = $trabajo->fields['tarifa_hh'];
						$profesionales[$trabajo->fields['nombre_usuario']]['id_categoria_usuario'] = $trabajo->fields['id_categoria_usuario']; //nombre de la categoria
						$profesionales[$trabajo->fields['nombre_usuario']]['categoria'] = $trabajo->fields['categoria']; // nombre de la categoria
					}
					if (Conf::GetConf($this->sesion, 'GuardarTarifaAlIngresoDeHora')) {
						$profesionales[$trabajo->fields['nombre_usuario']]['tarifa'] = $trabajo->fields['tarifa_hh'];
					}

					$categoria_duracion_trabajada += $duracion_decimal_trabajada;
					$categoria_duracion_descontada += $duracion_decimal_descontada;

					//se agregan los valores para el detalle de profesionales
					$profesionales[$trabajo->fields['nombre_usuario']]['tiempo_trabajado_real'] += $ht * 60 + $mt + $st / 60;
					$profesionales[$trabajo->fields['nombre_usuario']]['descontado_real'] += ( $ht - $h ) * 60 + ( $mt - $m ) + ( $st - $s ) / 60;
					$profesionales[$trabajo->fields['nombre_usuario']]['tiempo_trabajado'] += $h * 60 + $m + $s / 60;
					if ($this->fields['forma_cobro'] == 'FLAT FEE' && $trabajo->fields['cobrable'] == '1') {
						$profesionales[$trabajo->fields['nombre_usuario']]['flatfee'] += $h * 60 + $m + $s / 60;
					}
					if ($trabajo->fields['cobrable'] == '0') {
						$profesionales[$trabajo->fields['nombre_usuario']]['descontado_real'] += $ht * 60 + $mt + $st / 60;
						$profesionales[$trabajo->fields['nombre_usuario']]['descontado'] += $h * 60 + $m + $s / 60;
					} else {
						$profesionales[$trabajo->fields['nombre_usuario']]['tiempo'] += $h * 60 + $m + $s / 60;
						$profesionales[$trabajo->fields['nombre_usuario']]['valor'] += $trabajo->fields['monto_cobrado'];
					}
					if ($h_retainer * 60 + $m_retainer + $s_retainer / 60 > 0) {
						$profesionales[$trabajo->fields['nombre_usuario']]['retainer'] += $h_retainer * 60 + $m_retainer + $s_retainer / 60;
					}

					if ($this->fields['opc_ver_detalles_por_hora_categoria'] == 1)
						$row = str_replace('%td_categoria%', '<td align="left">%categoria%</td>', $row);
					else
						$row = str_replace('%td_categoria%', '', $row);
					$row = str_replace('%categoria%', __($trabajo->fields['categoria']), $row);

					if ($this->fields['opc_ver_detalles_por_hora_tarifa'] == 1) {
						$row = str_replace('%td_tarifa%', '<td align="center">%tarifa%</td>', $row);
						$row = str_replace('%td_tarifa_ajustada%', '<td align="center">%tarifa_ajustada%</td>', $row);
					} else {
						$row = str_replace('%td_tarifa%', '', $row);
						$row = str_replace('%td_tarifa_ajustada%', '', $row);
					}
					if ($this->fields['forma_cobro'] == 'ESCALONADA') {
						$row = str_replace('%tarifa%', number_format(($trabajo->fields['monto_cobrado'] / $duracion_cobrada_decimal), $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						$row = str_replace('%tarifa_ajustada%', number_format($trabajo->fields['tarifa_hh'] * $x_factor_ajuste, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					} else {
						$row = str_replace('%tarifa%', number_format($trabajo->fields['tarifa_hh'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						$row = str_replace('%tarifa_ajustada%', number_format($trabajo->fields['tarifa_hh'] * $x_factor_ajuste, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					}
					$row = $row_tmpl;
					$row = str_replace('%valor_codigo_asunto%', $trabajo->fields['codigo_asunto'], $row);
					$row = str_replace('%fecha%', Utiles::sql2fecha($trabajo->fields['fecha'], $idioma->fields['formato_fecha']), $row);
					$row = str_replace('%descripcion%', ucfirst(stripslashes($trabajo->fields['descripcion'])), $row);
					if ($this->fields['estado'] == 'CREADO' || $this->fields['estado'] == 'EN REVISION') {
						$row = str_replace('%td_id_trabajo%', '<td align="center">%ntrabajo%</td>', $row);
					} else {
						$row = str_replace('%td_id_trabajo%', '', $row);
					}
					$row = str_replace('%ntrabajo%', $trabajo->fields['id_trabajo'], $row);
					if ($this->fields['opc_ver_solicitante']) {
						$row = str_replace('%td_solicitante%', '<td align="left">%solicitante%</td>', $row);
					} else {
						$row = str_replace('%td_solicitante%', '', $row);
					}

					$row = str_replace('%solicitante%', $this->fields['opc_ver_solicitante'] ? $trabajo->fields['solicitante'] : '', $row);
					if ($this->fields['opc_ver_detalles_por_hora_iniciales']) {
						$row = str_replace('%profesional%', $trabajo->fields['username'], $row);
					} else {
						$row = str_replace('%profesional%', $trabajo->fields['nombre_usuario'], $row);
					}

					//paridad
					$row = str_replace('%paridad%', $i % 2 ? 'impar' : 'par', $row);

					//muestra las iniciales de los profesionales
					list($nombre, $apellido_paterno, $extra, $extra2) = explode(' ', $trabajo->fields['nombre_usuario'], 4);
					$row = str_replace('%iniciales%', $nombre[0] . $apellido_paterno[0] . $extra[0] . $extra2[0], $row);

					$row = str_replace('%username%', $trabajo->fields['username'], $row);

					if ($this->fields['opc_ver_columna_cobrable']) {
						if ($trabajo->fields['cobrable'] == 1) {
							$row = str_replace('%cobrable%', __('<td align="center">Si</td>'), $row);
						} else {
							$row = str_replace('%cobrable%', __('<td align="center">No</td>'), $row);
						}
					} else {
						$row = str_replace('%cobrable%', __(''), $row);
					}

					if ($ht < $h || ( $ht == $h && $mt < $m ) || ( $ht == $h && $mt == $m && $st < $s ))
						$asunto->fields['trabajos_total_duracion_trabajada'] += $h * 60 + $m + $s / 60;
					else
						$asunto->fields['trabajos_total_duracion_trabajada'] += $ht * 60 + $mt + $st / 60;
					$duracion_decimal_trabajada = $ht + $mt / 60 + $st / 3600;
					$duracion_decimal_descontada = $ht - $h + ($mt - $m) / 60 + ($st - $s) / 3600;
					$minutos_decimal = $m / 60;
					$duracion_decimal = $h + $minutos_decimal + $s / 3600;

					if (($mt - $m) < 0) {
						$horas_descontadas = $ht - $h - 1;
						$minutos_descontadas = $mt - $m + 60;
					} else {
						$horas_descontadas = $ht - $h;
						$minutos_descontadas = $mt - $m;
					}

					$ImprimirDuracionTrabajada = Conf::GetConf($this->sesion, 'ImprimirDuracionTrabajada');

					if ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') {
						$row = str_replace('%td_retainer%', '<td align="center">%duracion_retainer%</td>', $row);
						$row = str_replace('%td_sobre_retainer%', '<td align="center">%duracion_retainer%</td>', $row);
						if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
							$row = str_replace('%duracion_retainer%', number_format($duracion_decimal_retainer, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $row);
							$row = str_replace('%duracion_sobre_retainer%', number_format($duracion_decimal_sobre_retainer, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $row);
						} else {
							$row = str_replace('%duracion_retainer%', $h_retainer . ':' . sprintf("%02d", $m_retainer), $row);
							$row = str_replace('%duracion_sobre_retainer%', ($h - $h_retainer) . ':' . sprintf("%02d", ($m - $m_retainer)), $row);
						}
					} else {
						$row = str_replace('%duracion_sobre_retainer%', '%duracion%', $row);
						$row = str_replace('%td_retainer%', '', $row);
					}

					if ($this->fields['forma_cobro'] == 'FLAT FEE') {
						$row = str_replace('%duracion_decimal_trabajada%', '', $row);
						$row = str_replace('%duracion_trabajada%', '', $row);
						$row = str_replace('%duracion_decimal_descontada%', '', $row);
						$row = str_replace('%duracion_descontada%', '', $row);

						if (!$this->fields['opc_ver_horas_trabajadas']) {
							$row = str_replace('%duracion_decimal%', number_format($duracion_decimal, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
							if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
								$row = str_replace('%duracion%', number_format($duracion_decimal, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $row);
							} else {
								$row = str_replace('%duracion%', $h . ':' . sprintf("%02d", $m), $row);
							}
						} else {
							$row = str_replace('%duracion_decimal%', number_format($duracion_decimal_trabajada, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
							if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
								$row = str_replace('%duracion%', number_format($duracion_decimal_trabajada, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $row);
							} else {
								$row = str_replace('%duracion%', $ht . ':' . sprintf("%02d", $mt), $row);
							}
						}
					}
					if ($ImprimirDuracionTrabajada && ( $this->fields['estado'] == 'CREADO' || $this->fields['estado'] == 'EN REVISION' )) {
						$row = str_replace('%duracion_decimal_trabajada%', number_format($duracion_decimal_trabajada, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
							$row = str_replace('%duracion_trabajada%', number_format($duracion_decimal_trabajada, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $row);
							$row = str_replace('%duracion_descontada%', number_format($duracion_decimal_descontada, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $row);
						} else {
							$row = str_replace('%duracion_trabajada%', $ht . ':' . sprintf("%02d", $mt), $row);
							$row = str_replace('%duracion_descontada%', Utiles::Decimal2GlosaHora($duracion_decimal_descontada), $row);
						}
						$row = str_replace('%duracion_decimal_descontada%', number_format($duracion_decimal_descontada, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					} else if ($this->fields['opc_ver_horas_trabajadas']) {
						$row = str_replace('%duracion_decimal_trabajada%', number_format($duracion_decimal_trabajada, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
							$row = str_replace('%duracion_trabajada%', number_format($duracion_decimal_trabajada, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $row);
							$row = str_replace('%duracion_descontada%', number_format($duracion_decimal_descontada, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $row);
						} else {
							$row = str_replace('%duracion_trabajada%', $ht . ':' . sprintf("%02d", $mt), $row);
							$row = str_replace('%duracion_descontada%', Utiles::Decimal2GlosaHora($duracion_decimal_descontada), $row);
						}
						$row = str_replace('%duracion_decimal_descontada%', number_format($duracion_decimal_descontada, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					} else {
						$row = str_replace('%duracion_descontada%', '', $row);
						$row = str_replace('%duracion_decimal_descontada%', '', $row);
						$row = str_replace('%duracion_decimal_trabajada%', '', $row);
						$row = str_replace('%duracion_trabajada%', '', $row);
					}

					$row = str_replace('%duracion_decimal%', number_format($duracion_decimal, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
						$row = str_replace('%duracion%', number_format($duracion_decimal, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $row);
					} else {
						$row = str_replace('%duracion%', $h . ':' . $m, $row);
					}

					if ($this->fields['opc_ver_columna_cobrable']) {
						if ($trabajo->fields['cobrable'] == 1) {
							$row = str_replace('%cobrable%', __('<td align="center">Si</td>'), $row);
						} else {
							$row = str_replace('%cobrable%', __('<td align="center">No</td>'), $row);
						}
					} else
						$row = str_replace('%cobrable%', __(''), $row);


					$ImprimirValorTrabajo = Conf::GetConf($this->sesion, 'ImprimirValorTrabajo');

					if ($ImprimirValorTrabajo && $this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION') {
						$row = str_replace('%valor%', '', $row);
						$row = str_replace('%valor_cyc%', '', $row);
					} else {
						$row = str_replace('%valor%', number_format($trabajo->fields['monto_cobrado'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						$row = str_replace('%valor_con_moneda%', $moneda->fields['simbolo'] . number_format($trabajo->fields['monto_cobrado'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						$row = str_replace('%valor_cyc%', number_format($trabajo->fields['monto_cobrado'] * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					}
					$row = str_replace('%valor_siempre%', number_format($trabajo->fields['monto_cobrado'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

					if (Conf::GetConf($this->sesion, 'OrdenarPorCategoriaUsuario')) {
						$trabajo_siguiente = $lista_trabajos->Get($i + 1);
						if (!empty($trabajo_siguiente->fields['id_categoria_usuario'])) {
							if ($trabajo->fields['id_categoria_usuario'] != $trabajo_siguiente->fields['id_categoria_usuario']) {
								$html3 = $parser->tags['TRABAJOS_TOTAL'];
								$html3 = str_replace('%glosa%', __('Total'), $html3);
								$categoria_duracion_horas += floor($categoria_duracion_minutos / 60);
								$categoria_duracion_minutos = round($categoria_duracion_minutos % 60);
								$html3 = str_replace('%duracion%', sprintf('%02d:%02d', $categoria_duracion_horas, $categoria_duracion_minutos), $html3);
								$html3 = str_replace('%duracion_trabajada%', sprintf('%02d:%02d', floor($categoria_duracion_trabajada), round(($categoria_duracion_trabajada * 60) % 60)), $html3);
								$html3 = str_replace('%duracion_descontada%', sprintf('%02d:%02d', floor($categoria_duracion_descontada), round(($categoria_duracion_descontada * 60) % 60)), $html3);

								if (Conf::GetConf($this->sesion, 'NoImprimirValorTrabajo') && $this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION') {
									$html3 = str_replace('%valor%', '', $html3);
									$html3 = str_replace('%valor_cyc%', '', $html3);
								} else {
									$html3 = str_replace('%valor%', $moneda->fields['simbolo'] . $this->espacio . number_format($categoria_valor, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
									$html3 = str_replace('%valor_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($categoria_valor * ( $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
								}

								$total_trabajos_categoria .= $html3;

								$encabezado_trabajos_categoria .= $this->GenerarDocumentoComun($parser, 'TRABAJOS_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto);

								$row = str_replace('%TRABAJOS_CATEGORIA%', $total_trabajos_categoria . $encabezado_trabajos_categoria, $row);
								$categoria_duracion_horas = 0;
								$categoria_duracion_minutos = 0;
								$categoria_valor = 0;
								$total_trabajos_categoria = '';
								$encabezado_trabajos_categoria = '';
							} else {
								$row = str_replace('%TRABAJOS_CATEGORIA%', '', $row);
							}
						} else {
							$html3 = $parser->tags['TRABAJOS_TOTAL'];
							$html3 = str_replace('%glosa%', __('Total'), $html3);
							$categoria_duracion_horas += floor($categoria_duracion_minutos / 60);
							$categoria_duracion_minutos = round($categoria_duracion_minutos % 60);

							$html3 = str_replace('%duracion%', sprintf('%02d:%02d', $categoria_duracion_horas, $categoria_duracion_minutos), $html3);
							$html3 = str_replace('%duracion_trabajada%', sprintf('%02d:%02d', floor($categoria_duracion_trabajada), round(($categoria_duracion_trabajada * 60) % 60)), $html3);
							$html3 = str_replace('%duracion_descontada%', sprintf('%02d:%02d', floor($categoria_duracion_descontada), round(($categoria_duracion_descontada * 60) % 60)), $html3);

							if ($this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION' && Conf::GetConf($this->sesion, 'NoImprimirValorTrabajo')) {
								$html3 = str_replace('%valor%', '', $html3);
								$html3 = str_replace('%valor_cyc%', '', $html3);
							} else {
								$html3 = str_replace('%valor%', $moneda->fields['simbolo'] . $this->espacio . number_format($categoria_valor, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
								$html3 = str_replace('%valor_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($categoria_valor * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
							}

							$total_trabajos_categoria .= $html3;
							$row = str_replace('%TRABAJOS_CATEGORIA%', $total_trabajos_categoria, $row);
							$categoria_duracion_horas = 0;
							$categoria_duracion_minutos = 0;
							$categoria_valor = 0;
							$total_trabajos_categoria = '';
							$encabezado_trabajos_categoria = '';
						}
					} else if (Conf::GetConf($this->sesion, 'SepararPorUsuario')) {
						$trabajo_siguiente = $lista_trabajos->Get($i + 1);
						if (!empty($trabajo_siguiente->fields['nombre_usuario'])) {
							if ($trabajo->fields['nombre_usuario'] != $trabajo_siguiente->fields['nombre_usuario']) {
								$html3 = $parser->tags['TRABAJOS_TOTAL'];
								$html3 = str_replace('%glosa%', __('Subtotal'), $html3);
								$categoria_duracion_horas += floor($categoria_duracion_minutos / 60);
								$categoria_duracion_minutos = round($categoria_duracion_minutos % 60);
								$html3 = str_replace('%duracion%', sprintf('%02d:%02d', $categoria_duracion_horas, $categoria_duracion_minutos), $html3);

								if (Conf::GetConf($this->sesion, 'NoImprimirValorTrabajo') && $this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION') {
									$html3 = str_replace('%valor%', '', $html3);
									$html3 = str_replace('%valor_cyc%', '', $html3);
								} else {
									$html3 = str_replace('%valor%', $moneda->fields['simbolo'] . $this->espacio . number_format($categoria_valor, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
									$html3 = str_replace('%valor_cyc%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . $this->espacio . number_format($categoria_valor * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
								}

								if ($this->fields['opc_ver_detalles_por_hora_categoria'] == 1) {
									$html3 = str_replace('%td_categoria%', '<td>&nbsp;</td>', $html3);
								} else {
									$html3 = str_replace('%td_categoria%', '', $html3);
								}
								if ($this->fields['opc_ver_detalles_por_hora_tarifa'] == 1) {
									$html3 = str_replace('%td_tarifa%', '<td>&nbsp;</td>', $html3);
									$html3 = str_replace('%td_tarifa_ajustada%', '<td>&nbsp;</td>', $html3);
								} else {
									$html3 = str_replace('%td_tarifa%', '', $html3);
									$html3 = str_replace('%td_tarifa_ajustada%', '', $html3);
								}

								$html3 = $parser->tags['TRABAJOS_ENCABEZADO'];
								$html3 = str_replace('%duracion%', __('Duraci�n'), $html3);
								$html3 = str_replace('%cobrable%', __('<td align="center" width="80">Cobrable</td>'), $html3);
								$html3 = str_replace('%fecha%', __('Fecha'), $html3);
								$html3 = str_replace('%descripcion%', __('Descripci�n'), $html3);
								$html3 = str_replace('%profesional%', __('Profesional'), $html3);
								$html3 = str_replace('%abogado%', __('Abogado'), $html3);
								$html3 = str_replace('%categoria_abogado%', __($trabajo_siguiente->fields['nombre_usuario']), $html3);
								$html3 = str_replace('%tarifa%', $moneda->fields['simbolo'] . $this->espacio . number_format($trabajo_siguiente->fields['tarifa_hh'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ' / hr.', $html3);

								if ((Conf::GetConf($this->sesion, 'NoImprimirValorTrabajo')) && $this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION') {
									$html3 = str_replace('%valor%', '', $html3);
									$html3 = str_replace('%valor_cyc%', '', $html3);
								} else {
									$html3 = str_replace('%valor%', __('Valor'), $html3);
									$html3 = str_replace('%valor_cyc%', __('Valor'), $html3);
								}
								$encabezado_trabajos_categoria .= $html3;

								if ($this->fields['opc_ver_horas_trabajadas'] == 1) {
									$html3 = str_replace('%duracion_trabajada%', sprintf('%02d:%02d', floor($categoria_duracion_trabajada), round(($categoria_duracion_trabajada * 60) % 60)), $html3);
									$html3 = str_replace('%duracion_descontada%', sprintf('%02d:%02d', floor($categoria_duracion_descontada), round(($categoria_duracion_descontada * 60) % 60)), $html3);
								} else {
									$html3 = str_replace('%duracion_trabajada%', '', $html3);
									$html3 = str_replace('%duracion_descontada%', '', $html3);
								}

								$html3 = str_replace('%importe%', number_format($categoria_valor, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);

								$total_trabajos_categoria .= $html3;

								$encabezado_trabajos_categoria .= $this->GenerarDocumentoComun($parser, 'TRABAJOS_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto);

								$row = str_replace('%TRABAJOS_CATEGORIA%', $total_trabajos_categoria . $encabezado_trabajos_categoria, $row);
								$categoria_duracion_horas = 0;
								$categoria_duracion_minutos = 0;
								$categoria_duracion_trabajada = 0;
								$categoria_duracion_descontada = 0;
								$categoria_valor = 0;
								$total_trabajos_categoria = '';
								$encabezado_trabajos_categoria = '';
							} else {
								$row = str_replace('%TRABAJOS_CATEGORIA%', '', $row);
							}
						} else {
							$html3 = $parser->tags['TRABAJOS_TOTAL'];
							$html3 = str_replace('%glosa%', __('Subtotal'), $html3);
							$categoria_duracion_horas += floor($categoria_duracion_minutos / 60);
							$categoria_duracion_minutos = round($categoria_duracion_minutos % 60);

							$html3 = str_replace('%duracion%', sprintf('%02d:%02d', $categoria_duracion_horas, $categoria_duracion_minutos), $html3);

							if ($this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION' && Conf::GetConf($this->sesion, 'NoImprimirValorTrabajo')) {
								$html3 = str_replace('%valor%', '', $html3);
								$html3 = str_replace('%valor_cyc%', '', $html3);
							} else {
								$html3 = str_replace('%valor%', $moneda->fields['simbolo'] . $this->espacio . number_format($categoria_valor, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
								$html3 = str_replace('%valor_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($categoria_valor * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
							}

							if ($this->fields['opc_ver_detalles_por_hora_categoria'] == 1) {
								$html3 = str_replace('%td_categoria%', '<td>&nbsp;</td>', $html3);
							} else {
								$html3 = str_replace('%td_categoria%', '', $html3);
							}

							if ($this->fields['opc_ver_detalles_por_hora_tarifa'] == 1) {
								$html3 = str_replace('%td_tarifa%', '<td>&nbsp;</td>', $html3);
								$html3 = str_replace('%td_tarifa_ajustada%', '<td>&nbsp;</td>', $html3);
							} else {
								$html3 = str_replace('%td_tarifa%', '', $html3);
								$html3 = str_replace('%td_tarifa_ajustada%', '', $html3);
							}

							if ($this->fields['opc_ver_detalles_por_hora_importe'] == 1) {
								$html3 = str_replace('%td_importe%', '<td align="right">%importe%</td>', $html3);
								$html3 = str_replace('%td_importe_ajustado%', '<td align="right">%importe_ajustado%</td>', $html3);
							} else {
								$html3 = str_replace('%td_importe%', '', $html3);
								$html3 = str_replace('%td_importe_ajustado%', '', $html3);
							}

							if ($this->fields['opc_ver_horas_trabajadas'] == 1) {
								$html3 = str_replace('%duracion_trabajada%', sprintf('%02d:%02d', floor($categoria_duracion_trabajada), round(($categoria_duracion_trabajada * 60) % 60)), $html3);
								$html3 = str_replace('%duracion_descontada%', sprintf('%02d:%02d', floor($categoria_duracion_descontada), round(($categoria_duracion_descontada * 60) % 60)), $html3);
							} else {
								$html3 = str_replace('%duracion_trabajada%', '', $html3);
								$html3 = str_replace('%duracion_descontada%', '', $html3);
							}

							$html3 = str_replace('%importe%', number_format($categoria_valor, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);

							$total_trabajos_categoria .= $html3;
							$row = str_replace('%TRABAJOS_CATEGORIA%', $total_trabajos_categoria, $row);
							$categoria_duracion_horas = 0;
							$categoria_duracion_minutos = 0;
							$categoria_duracion_trabajada = 0;
							$categoria_duracion_descontada = 0;
							$categoria_valor = 0;
							$total_trabajos_categoria = '';
							$encabezado_trabajos_categoria = '';
						}
					} else {
						$row = str_replace('%TRABAJOS_CATEGORIA%', '', $row);
					}
					$html .= $row;
				}
				break;


			case 'TRABAJOS_TOTAL': //GenerarDocumentoComun
				if ($this->fields['estado'] == 'CREADO' || $this->fields['estado'] == 'EN REVISION') {
					$html = str_replace('%td_id_trabajo%', '<td align="center">%ntrabajo%</td>', $html);
				} else {
					$html = str_replace('%td_id_trabajo%', '', $html);
				}
				$html = str_replace('%ntrabajo%', __('&nbsp;'), $html);

				$ImprimirDuracionTrabajada = Conf::GetConf($this->sesion, 'ImprimirDuracionTrabajada');

				$duracion_trabajada_total = ($asunto->fields['trabajos_total_duracion_trabajada']) / 60;
				$duracion_cobrada_total = ($asunto->fields['trabajos_total_duracion']) / 60;
				$duracion_retainer_total = ($asunto->fields['trabajos_total_duracion_retainer']) / 60;
				$duracion_descontada_total = $duracion_trabajada_total - $duracion_cobrada_total;
				$duracion_sobre_retainer_total = $duracion_cobrada_total - $duracion_retainer_total;
				if ($this->fields['opc_ver_solicitante']) {
					$html = str_replace('%td_solicitante%', '<td>&nbsp;</td>', $html);
				} else {
					$html = str_replace('%td_solicitante%', '', $html);
				}
				if ($this->fields['opc_ver_detalles_por_hora_categoria'] == 1)
					$html = str_replace('%td_categoria%', '<td>&nbsp;</td>', $html);
				else
					$html = str_replace('%td_categoria%', '', $html);

				if ($this->fields['opc_ver_detalles_por_hora_tarifa'] == 1) {
					$html = str_replace('%td_tarifa%', '<td>&nbsp;</td>', $html);
					$html = str_replace('%td_tarifa_ajustada%', '<td>&nbsp;</td>', $html);
				} else {
					$html = str_replace('%td_tarifa%', '', $html);
					$html = str_replace('%td_tarifa_ajustada%', '', $html);
				}

				if ($this->fields['opc_ver_detalles_por_hora_importe'] == 1) {
					$html = str_replace('%td_importe%', '<td align="right">%importe%</td>', $html);
					$html = str_replace('%td_importe_ajustado%', '<td align="right">%importe_ajustado%</td>', $html);
				} else {
					$html = str_replace('%td_importe%', '', $html);
					$html = str_replace('%td_importe_ajustado%', '', $html);
				}
				$html = str_replace('%importe%', number_format($asunto->fields['trabajos_total_valor'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%importe_ajustado%', number_format($asunto->fields['trabajos_total_importe'] * $x_factor_ajuste, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				if ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') {
					$html = str_replace('%td_retainer%', '<td align="center">%duracion_retainer%</td>', $html);
					$html = str_replace('%td_sobre_retainer%', '<td align="center">%duracion_retainer%</td>', $html);
					if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
						$html = str_replace('%duracion_retainer%', number_format($duracion_retainer_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html);
						$html = str_replace('%duracion_sobre_retainer%', number_format($duracion_sobre_retainer_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html);
					} else {
						$html = str_replace('%duracion_retainer%', Utiles::Decimal2GlosaHora($duracion_retainer_total), $html);
						$html = str_replace('%duracion_sobre_retainer%', Utiles::Decimal2GlosaHora($duracion_sobre_retainer_total), $html);
					}
				} else {
					$html = str_replace('%duracion_sobre_retainer%', '%duracion%', $html);
					$html = str_replace('%td_retainer%', '', $html);
					$html = str_replace('%td_sobre_retainer%', '', $html);
				}

				if ($this->fields['forma_cobro'] == 'FLAT FEE') {
					$html = str_replace('%duracion_decimal_trabajada%', '', $html);
					$html = str_replace('%duracion_trabajada%', '', $html);
					$html = str_replace('%duracion_descontada%', '', $html);
					$html = str_replace('%duracion_decimal_descontada%', '', $html);
					if ($this->fields['opc_ver_horas_trabajadas']) {
						$html = str_replace('%duracion_decimal%', number_format($duracion_trabajada_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
						if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
							$html = str_replace('%duracion%', number_format($duracion_trabajada_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html);
						} else {
							$html = str_replace('%duracion%', Utiles::Decimal2GlosaHora($duracion_trabajada_total), $html);
						}
					} else {
						$html = str_replace('%duracion_decimal%', number_format($duracion_cobrada_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
						if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
							$html = str_replace('%duracion%', number_format($duracion_cobrada_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html);
						} else {
							$html = str_replace('%duracion%', Utiles::Decimal2GlosaHora($duracion_cobrada_total), $html);
						}
					}
				}
				if ($ImprimirDuracionTrabajada && ( $this->fields['estado'] == 'CREADO' || $this->fields['estado'] == 'EN REVISION' )) {
					$html = str_replace('%duracion_decimal_trabajada%', number_format($duracion_trabajada_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					$html = str_replace('%duracion_decimal_descontada%', number_format($duracion_descontada_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
						$html = str_replace('%duracion_trabajada%', number_format($duracion_trabajada_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html);
						$html = str_replace('%duracion_descontada%', number_format($duracion_descontada_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html);
					} else {
						$html = str_replace('%duracion_trabajada%', Utiles::Decimal2GlosaHora($duracion_trabajada_total), $html);
						$html = str_replace('%duracion_descontada%', Utiles::Decimal2GlosaHora($duracion_descontada_total), $html);
					}
				} else if ($this->fields['opc_ver_horas_trabajadas']) {
					$html = str_replace('%duracion_decimal_trabajada%', number_format($duracion_trabajada_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					$html = str_replace('%duracion_decimal_descontada%', number_format($duracion_descontada_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
						$html = str_replace('%duracion_trabajada%', number_format($duracion_trabajada_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html);
						$html = str_replace('%duracion_descontada%', number_format($duracion_descontada_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html);
					} else {
						$html = str_replace('%duracion_trabajada%', Utiles::Decimal2GlosaHora($duracion_trabajada_total), $html);
						$html = str_replace('%duracion_descontada%', Utiles::Decimal2GlosaHora($duracion_descontada_total), $html);
					}
				} else {
					$html = str_replace('%duracion_decimal_trabajada%', '', $html);
					$html = str_replace('%duracion_trabajada%', '', $html);
					$html = str_replace('%duracion_descontada%', '', $html);
					$html = str_replace('%duracion_decimal_descontada%', '', $html);
				}

				$html = str_replace('%glosa%', __('Total Trabajos'), $html);
				$html = str_replace('%duracion_decimal%', number_format($duracion_cobrada_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
					$html = str_replace('%duracion%', number_format($duracion_cobrada_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html);
				} else {
					$html = str_replace('%duracion%', Utiles::Decimal2GlosaHora($duracion_cobrada_total), $html);
				}


				if ($this->fields['opc_ver_columna_cobrable'] == 1)
					$html = str_replace('%cobrable%', __('<td>&nbsp;</td>'), $html);
				else
					$html = str_replace('%cobrable%', __(''), $html);

				$ImprimirValorTrabajo = Conf::GetConf($this->sesion, 'ImprimirValorTrabajo');

				$aproximacion_monto_cyc = number_format($this->fields['monto_subtotal'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
				$subtotal_en_moneda_cyc = $aproximacion_monto_cyc * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']);


				if ($ImprimirValorTrabajo && $this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION') {
					$html = str_replace('%valor%', '', $html);
					$html = str_replace('%valor_cyc%', '', $html);
				} else {
					$html = str_replace('%valor_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($subtotal_en_moneda_cyc, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					$html = str_replace('%valor%', $moneda->fields['simbolo'] . $this->espacio . number_format($asunto->fields['trabajos_total_valor'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				}

				$html = str_replace('%valor_siempre%', $moneda->fields['simbolo'] . $this->espacio . number_format($asunto->fields['trabajos_total_valor'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%total_raz%', __('total_raz'), $html);

				break;

			case 'DETALLE_PROFESIONAL': //GenerarDocumentoComun

				if ($this->fields['opc_ver_profesional'] == 0) {
					return '';
				}

				$html = str_replace('%glosa_profesional%', __('Detalle profesional'), $html);
				$html = str_replace('%detalle_tiempo_por_abogado%', __('Detalle tiempo por abogado'), $html);
				$html = str_replace('%detalle_honorarios%', __('Detalle de honorarios profesionales'), $html);
				$html = str_replace('%PROFESIONAL_ENCABEZADO%', $this->GenerarSeccionResumenProfesional($parser, 'PROFESIONAL_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%PROFESIONAL_FILAS%', $this->GenerarDocumentoComun($parser, 'PROFESIONAL_FILAS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%PROFESIONAL_TOTAL%', $this->GenerarDocumentoComun($parser, 'PROFESIONAL_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);

				if ($this->fields['opc_ver_descuento']) {
					$html = str_replace('%DETALLE_COBRO_DESCUENTO%', $this->GenerarDocumentoComun($parser, 'DETALLE_COBRO_DESCUENTO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				} else {
					$html = str_replace('%DETALLE_COBRO_DESCUENTO%', '', $html);
				}

				if (count($this->asuntos) > 1) {
					$html = str_replace('%DETALLE_COBRO_MONEDA_TOTAL_POR_ASUNTO%', $this->GenerarDocumentoComun($parser, 'DETALLE_COBRO_MONEDA_TOTAL_POR_ASUNTO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
					$html = str_replace('%DETALLE_COBRO_MONEDA_TOTAL%', '', $html);
				} else {
					$html = str_replace('%DETALLE_COBRO_MONEDA_TOTAL%', $this->GenerarDocumentoComun($parser, 'DETALLE_COBRO_MONEDA_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
					$html = str_replace('%DETALLE_COBRO_MONEDA_TOTAL_POR_ASUNTO%', '', $html);
				}
				break;


			case 'IMPUESTO': //GenerarDocumentoComun
				$html = str_replace('%impuesto%', __('Impuesto') . ' (' . $this->fields['porcentaje_impuesto'] . '%)', $html);
				$html = str_replace('%impuesto_mta%', __('Impuesto') . ' (' . $this->fields['porcentaje_impuesto'] . '% )', $html);

				if ($this->fields['tipo_cambio_moneda_base'] <= 0) {
					$tipo_cambio_cobro_moneda_base = 1;
				} else {
					$tipo_cambio_cobro_moneda_base = $this->fields['tipo_cambio_moneda_base'];
				}

				$aproximacion_impuesto = number_format($this->fields['impuesto'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
				$impuesto_moneda_total = $aproximacion_impuesto * ($this->fields['tipo_cambio_moneda'] / $tipo_cambio_cobro_moneda_base) / ($tipo_cambio_moneda_total / $tipo_cambio_cobro_moneda_base) + $this->fields['impuesto_gastos'];
				$html = str_replace('%valor_impuesto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($impuesto_moneda_total, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				// Mu�oz y Tamayo
				$impuesto_solo_honorarios = $x_resultados['monto_iva_hh'][$this->fields['opc_moneda_total']];

				$html = str_replace('%valor_impuesto_honorarios%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($impuesto_solo_honorarios, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				break;

			case 'PROFESIONAL_FILAS': //GenerarDocumentoComun
				$row_tmpl = $html;
				$html = '';

				if (is_array($profesionales)) {
					$retainer = false;
					$descontado = false;
					$flatfee = false;

					// Para mostrar un resumen de horas de cada profesional al principio del documento.
					global $resumen_profesional_nombre;
					global $resumen_profesional_hrs_trabajadas;
					global $resumen_profesional_hrs_retainer;
					global $resumen_profesional_hrs_descontadas;
					global $resumen_profesional_hh;
					global $resumen_profesional_valor_hh;
					global $resumen_profesional_categoria;
					global $resumen_profesional_id_categoria;
					global $resumen_profesionales;

					foreach ($profesionales as $prof => $data) {
						if ($data['retainer'] > 0)
							$retainer = true;
						if ($data['descontado'] > 0)
							$descontado = true;
						if ($data['flatfee'] > 0)
							$flatfee = true;
					}

					// Si el conf lo indica, ordenamos los profesionales por categor�a.
					if (Conf::GetConf($this->sesion, 'OrdenarPorTarifa')) {
						foreach ($profesionales as $prof => $data) {
							$tarifa_profesional[$prof] = $data['tarifa'];
						}
						if (sizeof($tarifa_profesional) > 0)
							array_multisort($tarifa_profesional, SORT_DESC, $profesionales);
					} else if (Conf::GetConf($this->sesion, 'OrdenarPorFechaCategoria')) {
						foreach ($profesionales as $prof => $data) {
							$categoria[$prof] = $data['id_categoria_usuario'];
						}
						if (sizeof($categoria) > 0)
							array_multisort($categoria, SORT_ASC, $profesionales);
					}
					foreach ($profesionales as $prof => $data) {
						// Para mostrar un resumen de horas de cada profesional al principio del documento.
						for ($k = 0; $k < count($resumen_profesional_nombre); ++$k)
							if ($resumen_profesional_nombre[$k] == $prof)
								break;
						$totales['valor'] += $data['valor'];
						//se pasan los minutos a horas:minutos
						$horas_trabajadas_real = floor(($data['tiempo_trabajado_real']) / 60);
						$minutos_trabajadas_real = sprintf("%02d", $data['tiempo_trabajado_real'] % 60);
						$horas_trabajadas = floor(($data['tiempo_trabajado']) / 60);
						$minutos_trabajadas = sprintf("%02d", $data['tiempo_trabajado'] % 60);
						$horas_descontado_real = floor(($data['descontado_real']) / 60);
						$minutos_descontado_real = sprintf("%02d", $data['descontado_real'] % 60);
						$horas_descontado = floor(($data['descontado']) / 60);
						$minutos_descontado = sprintf("%02d", $data['descontado'] % 60);
						$horas_retainer = floor(($data['retainer']) / 60);
						$minutos_retainer = sprintf("%02d", $data['retainer'] % 60);
						$segundos_retainer = sprintf("%02d", round(60 * ($data['retainer'] - floor($data['retainer']))));

						$horas_flatfee = floor(($data['flatfee']) / 60);
						$minutos_flatfee = sprintf("%02d", $data['flatfee'] % 60);
						if ($retainer) {
							$totales['tiempo_retainer'] += $data['retainer'];
							$totales['tiempo_trabajado'] += $data['tiempo_trabajado'];
							if ($data['tiempo_trabajado'] > $data['tiempo_trabajado_real'])
								$totales['tiempo_trabajado_real'] += $data['tiempo_trabajado'];
							else
								$totales['tiempo_trabajado_real'] += $data['tiempo_trabajado_real'];

							$totales['tiempo'] += $data['tiempo'] - $data['retainer'];
							$horas_cobrables = floor(($data['tiempo']) / 60) - $horas_retainer;
							$minutos_cobrables = sprintf("%02d", ($data['tiempo'] % 60) - $minutos_retainer);
							if ($this->fields['forma_cobro'] == 'PROPORCIONAL') {
								$segundos_cobrables = sprintf("%02d", 60 - $segundos_retainer);
								--$minutos_cobrables;
							}
							if ($minutos_cobrables < 0) {
								--$horas_cobrables;
								$minutos_cobrables += 60;
							}
						} else {
							$totales['tiempo'] += $data['tiempo'];
							$totales['tiempo_trabajado'] += $data['tiempo_trabajado'];
							if ($data['tiempo_trabajado'] > $data['tiempo_trabajado_real'])
								$totales['tiempo_trabajado_real'] += $data['tiempo_trabajado'];
							else
								$totales['tiempo_trabajado_real'] += $data['tiempo_trabajado_real'];
							$horas_cobrables = floor(($data['tiempo']) / 60);
							$minutos_cobrables = sprintf("%02d", $data['tiempo'] % 60);
						}
						if ($flatfee) {
							$totales['tiempo_flatfee'] += $data['flatfee'];
						}
						if ($descontado || $this->fields['opc_ver_horas_trabajadas']) {
							$totales['tiempo_descontado'] += $data['descontado'];
							if ($data['descontado_real'] >= 0)
								$totales['tiempo_descontado_real'] += $data['descontado_real'];
						}
						$row = $row_tmpl;
						$row = str_replace('%nombre%', $prof, $row);

						if (!$asunto->fields['cobrable']) {
							$row = str_replace('%hrs_retainer%', '', $row);
							$row = str_replace('%hrs_descontadas%', '', $row);
							$row = str_replace('%hrs_descontadas_real%', '', $row);
							$row = str_replace('%hh%', '', $row);
							$row = str_replace('%valor_hh%', '', $row);
							$row = str_replace('%valor_hh_cyc%', '', $row);
						}

						//muestra las iniciales de los profesionales
						list($nombre, $apellido_paterno, $extra) = explode(' ', $prof, 3);
						$row = str_replace('%iniciales%', $nombre[0] . $apellido_paterno[0] . $extra[0], $row);
						$row = str_replace('%username%', $data['username'], $row);

						if ($descontado || $retainer || $flatfee) {
							if ($this->fields['opc_ver_horas_trabajadas']) {
								if ($horas_descontado_real < 0 || substr($minutos_descontado_real, 0, 1) == '-') {
									$row = str_replace('%hrs_trabajadas_real%', $horas_cobrables . ':' . $minutos_cobrables, $row);
									$row = str_replace('%hrs_descontadas_real%', '0:00', $row);
								} else {
									$row = str_replace('%hrs_trabajadas_real%', $horas_trabajadas_real . ':' . $minutos_trabajadas_real, $row);
									$row = str_replace('%hrs_descontadas_real%', $horas_descontado_real . ':' . $minutos_descontado_real, $row);
								}
							} else {
								$row = str_replace('%hrs_trabajadas_real%', '', $row);
								$row = str_replace('%hrs_descontadas_real%', '', $row);
							}
							$row = str_replace('%hrs_trabajadas%', $horas_trabajadas . ':' . $minutos_trabajadas, $row);
							//$resumen_profesional_hrs_trabajadas[$k] += $horas_trabajadas + $minutos_trabajadas/60;
						} else if ($this->fields['opc_ver_horas_trabajadas']) {
							if ($horas_descontado_real < 0 || substr($minutos_descontado_real, 0, 1) == '-') {
								$row = str_replace('%hrs_trabajadas_real%', $horas_cobrables . ':' . $minutos_cobrables, $row);
								$row = str_replace('%hrs_descontadas_real%', '0:00', $row);
							} else {
								$row = str_replace('%hrs_trabajadas_real%', $horas_trabajadas_real . ':' . $minutos_trabajadas_real, $row);
								$row = str_replace('%hrs_descontadas_real%', $horas_descontado_real . ':' . $minutos_descontado_real, $row);
							}
							$row = str_replace('%hrs_trabajadas%', $horas_trabajadas . ':' . $minutos_trabajadas, $row);
						} else {
							$row = str_replace('%hrs_trabajadas%', '', $row);
							$row = str_replace('%hrs_trabajadas_real%', '', $row);
						}
						if ($retainer) {
							if ($data['retainer'] > 0) {
								if ($this->fields['forma_cobro'] == 'PROPORCIONAL') {
									$minutos_retainer_redondeados = sprintf("%02d", $minutos_retainer + round($segundos_retainer / 60));
									$row = str_replace('%hrs_retainer%', $horas_retainer . ':' . $minutos_retainer_redondeados, $row);
									$resumen_profesional_hrs_retainer[$k] += $horas_retainer + $minutos_retainer / 60 + $segundos_retainer / 3600;
								} else { // retainer simple, no imprime segundos
									$row = str_replace('%hrs_retainer%', $horas_retainer . ':' . $minutos_retainer, $row);
									$resumen_profesional_hrs_retainer[$k] += $horas_retainer + $minutos_retainer / 60;
								}
								$minutos_retainer_decimal = $minutos_retainer / 60;
								$duracion_retainer_decimal = $horas_retainer + $minutos_retainer_decimal;
								$row = str_replace('%horas_retainer%', number_format($duracion_retainer_decimal, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
							} else {
								$row = str_replace('%hrs_retainer%', '-', $row);
								$row = str_replace('%horas_retainer%', '', $row);
							}
						} else {
							if ($flatfee) {
								if ($data['flatfee'] > 0) {
									$row = str_replace('%hrs_retainer%', $horas_flatfee . ':' . $minutos_flatfee, $row);
									$resumen_profesional_hrs_retainer[$k] += $horas_flatfee + $minutos_flatfee / 60;
								} else
									$row = str_replace('%hrs_retainer%', '', $row);
							}
							$row = str_replace('%hrs_retainer%', '', $row);
							$row = str_replace('%horas_retainer%', '', $row);
						}

						if ($descontado) {
							$row = str_replace('%columna_horas_no_cobrables%', '<td align="center" width="65">%hrs_descontado%</td>', $row);
							if ($data['descontado'] > 0) {
								$row = str_replace('%hrs_descontadas%', $horas_descontado . ':' . $minutos_descontado, $row);
								$resumen_profesional_hrs_descontadas[$k] += $horas_descontado + $minutos_descontado / 60;
							} else
								$row = str_replace('%hrs_descontadas%', '-', $row);
							if ($data['descontado_real'] > 0) {
								$row = str_replace('%hrs_descontadas_real%', $horas_descontado_real . ':' . $minutos_descontado_real, $row);
							} else
								$row = str_replace('hrs_descontadas_real%', '-', $row);
						} else {
							$row = str_replace('%columna_horas_no_cobrables%', '', $row);
							$row = str_replace('%hrs_descontadas_real%', '', $row);
							$row = str_replace('%hrs_descontadas%', '', $row);
						}

						if ($flatfee) {
							$row = str_replace('%hh%', '0:00', $row);
						} else {
							if ($this->fields['forma_cobro'] == 'PROPORCIONAL') {
								$minutos_cobrables_redondeados = sprintf("%02d", $minutos_cobrables + round($segundos_cobrables / 60));
								$row = str_replace('%hh%', "$horas_cobrables:$minutos_cobrables_redondeados", $row);
							} else // Otras formas de cobro, no imprime segundos
								$row = str_replace('%hh%', $horas_cobrables . ':' . sprintf("%02d", $minutos_cobrables), $row);
						}

						$row = str_replace('%valor_hh%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . $this->espacio . number_format($data['tarifa'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						$row = str_replace('%valor_hh_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($data['tarifa'] * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						$row = str_replace('%total%', $moneda->fields['simbolo'] . $this->espacio . number_format($data['valor'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						$row = str_replace('%total_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($data['valor'] * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

						$row = str_replace('%hrs_trabajadas_previo%', '', $row);
						$row = str_replace('%horas_trabajadas_especial%', '', $row);
						$row = str_replace('%horas_cobrables%', '', $row);

						#horas en decimal

						$minutos_decimal = $minutos_cobrables / 60;
						$duracion_decimal = $horas_cobrables + $minutos_decimal;

						$row = str_replace('%horas%', number_format($duracion_decimal, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

						if ($this->fields['opc_ver_profesional_tarifa'] == 1) {
							$row = str_replace('%tarifa_horas%', $flatfee ? '' : number_format($data['tarifa'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						} else {
							$row = str_replace('%tarifa_horas%', '', $row);
						}

						$row = str_replace('%total_horas%', $flatfee ? '' : number_format($data['valor'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

						if ($this->fields['opc_ver_horas_trabajadas'] && $horas_trabajadas_real . ':' . $minutos_trabajadas != '0:00') {
							$html .= $row;
						} else if ($horas_trabajadas . ':' . $minutos_trabajadas != '0:00') {
							$html .= $row;
						}

						$resumen_profesional_hh[$k] += $horas_cobrables + $minutos_cobrables / 60;

						// Se usan solo para el cobro prorrateado.
						if ($segundos_cobrables) {
							$resumen_profesional_hh[$k] += $segundos_cobrables / 3600;
						}
						if ($flatfee) {
							$resumen_profesional_hh[$k] = 0;
						}
					}
				}
				break;

			case 'PROFESIONAL_TOTAL': //GenerarDocumentoComun

				$retainer = false;
				$descontado = false;
				$flatfee = false;
				if (is_array($profesionales)) {
					foreach ($profesionales as $prof => $data) {
						if ($data['retainer'] > 0) {
							$retainer = true;
						}
						if ($data['descontado'] > 0) {
							$descontado = true;
						}
						if ($data['flatfee'] > 0) {
							$flatfee = true;
						}
					}
				}

				if (!$asunto->fields['cobrable']) {
					$html = str_replace('%hrs_retainer%', '', $html);
					$html = str_replace('%hrs_descontadas%', '', $html);
					$html = str_replace('%hrs_descontadas_real%', '', $html);
					$html = str_replace('%hh%', '', $html);
					$html = str_replace('%valor_hh%', '', $html);
					$html = str_replace('%valor_hh_cyc%', '', $html);
				}

				$horas_cobrables = floor(($totales['tiempo']) / 60);
				$minutos_cobrables = sprintf("%02d", $totales['tiempo'] % 60);
				$segundos_cobrables = round(60 * ($totales['tiempo'] - floor($totales['tiempo'])));
				$horas_trabajadas = floor(($totales['tiempo_trabajado']) / 60);
				$minutos_trabajadas = sprintf("%02d", $totales['tiempo_trabajado'] % 60);
				$horas_trabajadas_real = floor(($totales['tiempo_trabajado_real']) / 60);
				$minutos_trabajadas_real = sprintf("%02d", $totales['tiempo_trabajado_real'] % 60);
				$horas_retainer = floor(($totales['tiempo_retainer']) / 60);
				$minutos_retainer = sprintf("%02d", $totales['tiempo_retainer'] % 60);
				$segundos_retainer = sprintf("%02d", round(60 * ($totales['tiempo_retainer'] - floor($totales['tiempo_retainer']))));
				$horas_flatfee = floor(($totales['tiempo_flatfee']) / 60);
				$minutos_flatfee = sprintf("%02d", $totales['tiempo_flatfee'] % 60);
				$horas_descontado = floor(($totales['tiempo_descontado']) / 60);
				$minutos_descontado = sprintf("%02d", $totales['tiempo_descontado'] % 60);
				$horas_descontado_real = floor(($totales['tiempo_descontado_real']) / 60);
				$minutos_descontado_real = sprintf("%02d", $totales['tiempo_descontado_real'] % 60);
				$html = str_replace('%glosa%', __('Total'), $html);
				$html = str_replace('%glosa_honorarios%', __('Total Honorarios'), $html);

				if ($descontado || $retainer || $flatfee) {
					if ($this->fields['opc_ver_horas_trabajadas']) {
						$html = str_replace('%hrs_trabajadas_real%', $horas_trabajadas_real . ':' . $minutos_trabajadas_real, $html);
						$html = str_replace('%hrs_descontadas_real%', $horas_descontado_real . ':' . $minutos_descontado_real, $html);
					} else {
						$html = str_replace('%hrs_trabajadas_real%', '', $html);
						$html = str_replace('%hrs_descontadas_real%', '', $html);
					}
					$html = str_replace('%hrs_trabajadas%', $horas_trabajadas . ':' . $minutos_trabajadas, $html);
				} else if ($this->fields['opc_ver_horas_trabajadas']) {
					$html = str_replace('%hrs_trabajadas%', $horas_trabajadas . ':' . $minutos_trabajadas, $html);
					$html = str_replace('%hrs_trabajadas_real%', $horas_trabajadas_real . ':' . $minutos_trabajadas_real, $html);
					$html = str_replace('%hrs_descontadas_real%', $horas_descontado_real . ':' . $minutos_descontado_real, $html);
				} else {
					$html = str_replace('%hrs_trabajadas%', '', $html);
					$html = str_replace('%hrs_trabajadas_real%', '', $html);
				}

				$html = str_replace('%hrs_trabajadas_previo%', '', $html);
				$html = str_replace('%horas_trabajadas_especial%', '', $html);
				$html = str_replace('%horas_cobrables%', '', $html);

				if ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL')
					$html = str_replace('%DETALLE_PROFESIONAL_RETAINER%', $this->GenerarDocumentoComun($parser, 'DETALLE_PROFESIONAL_RETAINER', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				else
					$html = str_replace('%DETALLE_PROFESIONAL_RETAINER%', '', $html);

				if ($retainer) {
					if ($this->fields['forma_cobro'] == 'PROPORCIONAL') {
						$minutos_retainer_redondeados = sprintf("%02d", $minutos_retainer + round($segundos_retainer / 60));
						$html = str_replace('%hrs_retainer%', $horas_retainer . ':' . $minutos_retainer_redondeados, $html);
					} else // retainer simple, no imprime segundos
						$html = str_replace('%hrs_retainer%', $horas_retainer . ':' . $minutos_retainer, $html);
					$minutos_retainer_decimal = $minutos_retainer / 60;
					$duracion_retainer_decimal = $horas_retainer + $minutos_retainer_decimal;
					$html = str_replace('%horas_retainer%', number_format($duracion_retainer_decimal, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				}
				else {
					$html = str_replace('%horas_retainer%', '', $html);
					if ($flatfee)
						$html = str_replace('%hrs_retainer%', $horas_flatfee . ':' . $minutos_flatfee, $html);
					else
						$html = str_replace('%hrs_retainer%', '', $html);
				}
				if ($descontado) {
					$html = str_replace('%columna_horas_no_cobrables%', '<td align="center" width="65">%hrs_descontadas%</td>', $html);
					$html = str_replace('%hrs_descontadas_real%', $horas_descontadas_real . ':' . $minutos_descontadas_real, $html);
					$html = str_replace('%hrs_descontadas%', $horas_descontado . ':' . $minutos_descontado, $html);
				} else {
					$html = str_replace('%columna_horas_no_cobrables%', '', $html);
					$html = str_replace('%hrs_descontadas_real%', '', $html);
					$html = str_replace('%hrs_descontadas%', '', $html);
				}
				if ($flatfee)
					$html = str_replace('%hh%', '0:00', $html);
				else
				if ($this->fields['forma_cobro'] == 'PROPORCIONAL') {
					$minutos_cobrables_redondeados = sprintf("%02d", $minutos_cobrables + round($segundos_cobrables / 60));
					$html = str_replace('%hh%', "$horas_cobrables:$minutos_cobrables_redondeados", $html);
				} else // retainer simple, no imprime segundos
					$html = str_replace('%hh%', $horas_cobrables . ':' . sprintf("%02d", $minutos_cobrables), $html);

				$aproximacion_monto_cyc = number_format($this->fields['monto_subtotal'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
				$subtotal_en_moneda_cyc = $aproximacion_monto_cyc * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']);

				$html = str_replace('%total%', $moneda->fields['simbolo'] . $this->espacio . number_format($totales['valor'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%total_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($subtotal_en_moneda_cyc, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				#horas en decimal
				$minutos_decimal = $minutos_cobrables / 60;
				$duracion_decimal = $horas_cobrables + $minutos_decimal;

				$html = str_replace('%total_honorarios%', $flatfee ? $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto_subtotal'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) : $moneda->fields['simbolo'] . number_format($totales['valor'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%horas%', number_format($duracion_decimal, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				break;

			case 'DETALLE_PROFESIONAL_RETAINER': //GenerarDocumentoComun
				$html = str_replace('%retainer%', __('Retainer'), $html);
				$html = str_replace('%valor_retainer%', $cobro_moneda->moneda[$this->fields['id_moneda_monto']]['simbolo'] . $this->espacio . number_format($this->fields['monto_contrato'], $cobro_moneda->moneda[$this->fields['id_moneda_monto']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				break;

			case 'DETALLE_COBRO_MONEDA_TOTAL_POR_ASUNTO': //GenerarDocumentoComun
				if ($this->fields['opc_moneda_total'] == $this->fields['id_moneda'])
					return '';

				//valor en moneda previa selecci�n para impresi�n
				if ($this->fields['tipo_cambio_moneda_base'] <= 0) {
					$tipo_cambio_cobro_moneda_base = 1;
				} else {
					$tipo_cambio_cobro_moneda_base = $this->fields['tipo_cambio_moneda_base'];
				}

				$aproximacion_monto = number_format($totales['valor'], $moneda->fields['cifras_decimales'], '.', '');
				$total_en_moneda = $aproximacion_monto * ($this->fields['tipo_cambio_moneda'] / $tipo_cambio_cobro_moneda_base) / ($tipo_cambio_moneda_total / $tipo_cambio_cobro_moneda_base);

				$html = str_replace('%valor_honorarios_monedabase%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . '&nbsp;' . number_format($total_en_moneda, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				break;

			/*
			  GASTOS -> esto s?lo lista los gastos agregados al cobro obteniendo un total
			 */
			case 'GASTOS': //GenerarDocumentoComun
				if ($this->fields['opc_ver_gastos'] == 0)
					return '';

				$html = str_replace('%glosa_gastos%', __('Gastos'), $html);
				$html = str_replace('%expenses%', __('%expenses%'), $html); //en vez de Disbursements es Expenses en ingl?s
				$html = str_replace('%detalle_gastos_raz%', __('detalledegastos'), $html);
				if ($lang == 'es') {
					$html = str_replace('%factura%', __('Factura'), $html);
				} else {
					$html = str_replace('%factura%', __('Factura'), $html);
				}
				$html = str_replace('%detalle_gastos%', __('Detalle de gastos'), $html);

				$html = str_replace('%GASTOS_ENCABEZADO%', $this->GenerarDocumentoComun($parser, 'GASTOS_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%GASTOS_FILAS%', $this->GenerarDocumentoComun($parser, 'GASTOS_FILAS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%GASTOS_TOTAL%', $this->GenerarDocumentoComun($parser, 'GASTOS_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				break;

			case 'GASTOS_ENCABEZADO': //GenerarDocumentoComun
				$html = str_replace('%glosa_gastos%', __('Gastos'), $html);
				$html = str_replace('%descripcion_gastos%', __('Descripci�n de Gastos'), $html);
				$html = str_replace('%fecha%', __('Fecha'), $html);
				$html = str_replace('%num_doc%', __('N? Documento'), $html);
				$html = str_replace('%tipo_gasto%', __('Tipo'), $html);
				$html = str_replace('%descripcion%', __('Descripci�n'), $html);
				$html = str_replace('%monto_original%', __('Monto'), $html);
				$html = str_replace('%monto_moneda_total%', __('Monto') . ' (' . $moneda_total->fields['simbolo'] . ')', $html);
				$html = str_replace('%ordenado_por%', __('Ordenado<br>Por'), $html);
				if ($lang == 'es') {
					$html = str_replace('%asunto_id%', __('ID<br>Asunto'), $html);
				} else {
					$html = str_replace('%asunto_id%', __('Matter<br>ID'), $html);
				}

				$html = str_replace('%monto_impuesto_total%', '', $html);
				$html = str_replace('%monto_moneda_total_con_impuesto%', '', $html);

				if (Conf::GetConf($this->sesion, 'MostrarProveedorenGastos')) {
					$html = str_replace('%proveedor%', __('Proveedor'), $html);
				} else {
					$html = str_replace('%proveedor%', '', $html);
				}

				break;

			case 'GASTOS_FILAS': //GenerarDocumentoComun
				$row_tmpl = $html;
				$html = '';
				if (method_exists('Conf', 'SepararGastosPorAsunto') && Conf::SepararGastosPorAsunto()) {
					$where_gastos_asunto = " AND codigo_asunto='" . $asunto->fields['codigo_asunto'] . "'";
				} else {
					$where_gastos_asunto = "";
				}
				$query = "SELECT SQL_CALC_FOUND_ROWS *, prm_cta_corriente_tipo.glosa AS tipo_gasto
								FROM cta_corriente
								LEFT JOIN prm_cta_corriente_tipo ON cta_corriente.id_cta_corriente_tipo=prm_cta_corriente_tipo.id_cta_corriente_tipo
								WHERE id_cobro='" . $this->fields['id_cobro'] . "'
									AND monto_cobrable > 0
									AND cta_corriente.incluir_en_cobro = 'SI'
									AND cta_corriente.cobrable = 1
								$where_gastos_asunto
								ORDER BY fecha ASC";

				$lista_gastos = new ListaGastos($this->sesion, '', $query);
				$totales['total'] = 0;
				$totales['total_moneda_cobro'] = 0;
				if ($lista_gastos->num == 0) {
					$row = $row_tmpl;
					$row = str_replace('%fecha%', '&nbsp;', $row);
					$row = str_replace('%descripcion%', __('No hay gastos en este cobro'), $row);
					$row = str_replace('%descripcion_b%', '(' . __('No hay gastos en este cobro') . ')', $row);
					$row = str_replace('%monto_original%', '&nbsp;', $row);
					$row = str_replace('%monto%', '&nbsp;', $row);
					$row = str_replace('%monto_moneda_total%', '&nbsp;', $row);
					$row = str_replace('%monto_moneda_total_sin_simbolo%', '&nbsp;', $row);
					$row = str_replace('%valor_codigo_asunto%', $gasto->fields['codigo_asunto'], $row);
					$html .= $row;
				}

				for ($i = 0; $i < $lista_gastos->num; $i++) {
					$gasto = $lista_gastos->Get($i);

					//Cargar cobro_moneda

					$cobro_moneda = new CobroMoneda($this->sesion);
					$cobro_moneda->Load($this->fields['id_cobro']);

					if ($gasto->fields['egreso'] > 0)
						$saldo = $gasto->fields['monto_cobrable'];
					elseif ($gasto->fields['ingreso'] > 0)
						$saldo = -$gasto->fields['monto_cobrable'];

					$monto_gasto = $saldo;
					$saldo_moneda_total = $saldo * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']);
					if (Conf::GetConf($this->sesion, 'CalculacionCyC'))
						$saldo_moneda_total = number_format($saldo_moneda_total, $cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['cifras_decimales'], ".", "");

					$totales['total'] += $saldo_moneda_total;
					$totales['total_moneda_cobro'] += $saldo;

					$row = $row_tmpl;
					$row = str_replace('%fecha%', Utiles::sql2fecha($gasto->fields['fecha'], $idioma->fields['formato_fecha']), $row);
					$row = str_replace('%num_doc%', $gasto->fields['numero_documento'], $row);
					$row = str_replace('%tipo_gasto%', $gasto->fields['tipo_gasto'], $row);

					if (substr($gasto->fields['descripcion'], 0, 41) == 'Saldo aprovisionado restante tras Cobro #') {
						$row = str_replace('%descripcion%', __('Saldo aprovisionado restante tras Cobro #') . substr($gasto->fields['descripcion'], 42), $row);
						$row = str_replace('%descripcion_b%', __('Saldo aprovisionado restante tras Cobro #') . substr($gasto->fields['descripcion'], 42), $row);
					} else {
						$row = str_replace('%descripcion%', __($gasto->fields['descripcion']), $row);
						$row = str_replace('%descripcion_b%', __($gasto->fields['descripcion']), $row); #Ojo, este no deber?a existir
					}

					$row = str_replace('%monto_original%', $cobro_moneda->moneda[$gasto->fields['id_moneda']]['simbolo'] . $this->espacio . number_format($monto_gasto, $cobro_moneda->moneda[$gasto->fields['id_moneda']]['cifras_decimales'], $cobro_moneda->moneda[$gasto->fields['id_moneda']]['separador_decimales'], $cobro_moneda->moneda[$gasto->fields['id_moneda']]['separador_miles']), $row);

					$row = str_replace('%monto_moneda_total%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($saldo_moneda_total, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

					$row = str_replace('%monto_moneda_total_sin_simbolo%', number_format($saldo_moneda_total, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					$html .= $row;
				}

				$html = str_replace('%monto_impuesto_total%', '', $html);
				$html = str_replace('%monto_moneda_total_con_impuesto%', '', $html);

				if (Conf::GetConf($this->sesion, 'MostrarProveedorenGastos')) {
					$row = str_replace('%proveedor%', $detalle['glosa_proveedor'], $row);
				} else {
					$row = str_replace('%proveedor%', '', $row);
				}

				if ($this->fields['opc_ver_solicitante']) {
					$row = str_replace('%solicitante%', $detalle['username'], $row);
				} else {
					$row = str_replace('%solicitante%', '', $row);
				}

				break;

			case 'GASTOS_TOTAL': //GenerarDocumentoComun
				$html = str_replace('%total%', __('Total'), $html);
				$html = str_replace('%glosa_total%', __('Total Gastos'), $html);
				if ($lang == 'es') {
					$html = str_replace('%sub_total_gastos%', __('Sub total gastos'), $html);
				} else {
					$html = str_replace('%sub_total_gastos%', __('Sub total for expenses'), $html);
				}
				$cobro_moneda = new CobroMoneda($this->sesion);
				$cobro_moneda->Load($this->fields['id_cobro']);

				$id_moneda_base = Moneda::GetMonedaBase($this->sesion);

				#$html = str_replace('%valor_total%', ''/*$cobro_moneda->fields['simbolo'].' '.number_format($totales['total_moneda_cobro'],$cobro_moneda->fields['cifras_decimales']*/,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
				if ($this->fields['id_moneda_base'] <= 0)
					$tipo_cambio_cobro_moneda_base = 1;
				else
					$tipo_cambio_cobro_moneda_base = $cobro_moneda->moneda[$id_moneda_base]['tipo_cambio'];

				#$gastos_moneda_total = ($totales['total']*($this->fields['tipo_cambio_moneda']/$this->fields['tipo_cambio_moneda_base']))/$this->fields['opc_moneda_total_tipo_cambio'];
				#$gastos_moneda_total = ($totales['total']*($this->fields['tipo_cambio_moneda']/$tipo_cambio_cobro_moneda_base))/$tipo_cambio_moneda_total;
				# Comentado por ICC $gastos_moneda_total = $totales['total']*$moneda->fields['tipo_cambio']/$tipo_cambio_moneda_total;
				$gastos_moneda_total = $totales['total'];

				$html = str_replace('%total_gastos_moneda_total%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($totales['total'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				if ($moneda_total->fields['id_moneda'] != $id_moneda_base) {
					$html = str_replace('%glosa_total_moneda_base%', __('Total Moneda Base'), $html);
					$gastos_moneda_total_contrato = ( $gastos_moneda_total * ( $cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio'])) / $tipo_cambio_cobro_moneda_base;
					$html = str_replace('%valor_total_moneda_carta%', $cobro_moneda->moneda[$id_moneda_base]['simbolo'] . $this->espacio . number_format($gastos_moneda_total_contrato, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				} else {
					$html = str_replace('%glosa_total_moneda_base%', '&nbsp;', $html);
					$html = str_replace('%valor_total_moneda_base%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($totales['total'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					$html = str_replace('%valor_total_moneda_carta%', '&nbsp;', $html);
				}

				$contr = new Contrato($this->sesion);
				$contr->Load($this->fields['id_contrato']);

				$gastos_moneda_total_contrato = ( $totales['total'] * ( $cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio'])) / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'];
				$html = str_replace('%valor_total_moneda_carta%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($gastos_moneda_total_contrato, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				$html = str_replace('%valor_impuesto_monedabase%', '', $html);
				$html = str_replace('%valor_total_monedabase_con_impuesto%', '', $html);
				break;

			/*
			  CTA_CORRIENTE -> nuevo tag para la representaci�n de la cuenta corriente (gastos, provisiones)
			  aparecer� como Saldo Inicial; Movimientos del periodo; Saldo Periodo; Saldo Final
			 */
			case 'CTA_CORRIENTE': //GenerarDocumentoComun
				if ($this->fields['opc_ver_gastos'] == 0)
					return '';

				$html = str_replace('%titulo_detalle_cuenta%', __('Saldo de Gastos Adeudados'), $html);
				$html = str_replace('%descripcion_cuenta%', __('Descripci�n'), $html);
				$html = str_replace('%monto_cuenta%', __('Monto'), $html);

				$html = str_replace('%CTA_CORRIENTE_SALDO_INICIAL%', $this->GenerarDocumentoComun($parser, 'CTA_CORRIENTE_SALDO_INICIAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%CTA_CORRIENTE_MOVIMIENTOS_ENCABEZADO%', $this->GenerarDocumentoComun($parser, 'CTA_CORRIENTE_MOVIMIENTOS_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%CTA_CORRIENTE_MOVIMIENTOS_FILAS%', $this->GenerarDocumentoComun($parser, 'CTA_CORRIENTE_MOVIMIENTOS_FILAS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%CTA_CORRIENTE_MOVIMIENTOS_TOTAL%', $this->GenerarDocumentoComun($parser, 'CTA_CORRIENTE_MOVIMIENTOS_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%CTA_CORRIENTE_SALDO_FINAL%', $this->GenerarDocumentoComun($parser, 'CTA_CORRIENTE_SALDO_FINAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				break;

			case 'CTA_CORRIENTE_SALDO_INICIAL': //GenerarDocumentoComun
				$saldo_inicial = $this->SaldoInicialCuentaCorriente();

				$html = str_replace('%saldo_inicial_cuenta%', __('Saldo inicial'), $html);
				$html = str_replace('%valor_saldo_inicial_cuenta%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($saldo_inicial, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				break;

			case 'CTA_CORRIENTE_MOVIMIENTOS_ENCABEZADO': //GenerarDocumentoComun
				$html = str_replace('%movimientos%', __('Movimientos del periodo'), $html);
				$html = str_replace('%fecha%', __('Fecha'), $html);
				$html = str_replace('%descripcion%', __('Descripci�n'), $html);
				$html = str_replace('%egreso%', __('Egreso') . ' (' . $moneda_total->fields['simbolo'] . ')', $html);
				$html = str_replace('%ingreso%', __('Ingreso') . ' (' . $moneda_total->fields['simbolo'] . ')', $html);
				break;

			case 'CTA_CORRIENTE_MOVIMIENTOS_FILAS': //GenerarDocumentoComun
				$row_tmpl = $html;
				$html = '';
				$query = "SELECT SQL_CALC_FOUND_ROWS * FROM cta_corriente
								WHERE id_cobro='" . $this->fields['id_cobro'] . "' AND (egreso > 0 OR ingreso > 0) AND cta_corriente.incluir_en_cobro = 'SI' ORDER BY fecha ASC";
				$lista_gastos = new ListaGastos($this->sesion, '', $query);
				$totales['total'] = 0;
				global $total_egreso;
				global $total_ingreso;
				$total_egreso = 0;
				$total_ingreso = 0;
				if ($lista_gastos->num == 0) {
					$row = $row_tmpl;
					$row = str_replace('%fecha%', '&nbsp;', $row);
					$row = str_replace('%descripcion%', __('No hay gastos en este cobro'), $row);
					$row = str_replace('%monto_egreso%', '&nbsp;', $row);
					$row = str_replace('%monto_ingreso%', '&nbsp;', $row);
					$html .= $row;
				}

				for ($i = 0; $i < $lista_gastos->num; $i++) {
					$gasto = $lista_gastos->Get($i);

					$row = $row_tmpl;

					if ($gasto->fields['egreso'] > 0) {

						$monto_egreso = $gasto->fields['monto_cobrable'];
						$totales['total'] += $gasto->fields['monto_cobrable'] * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']); #error gasto 2
						$totales['total_egreso'] += $gasto->fields['monto_cobrable'] * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']); #error gasto 3
						$row = str_replace('%fecha%', Utiles::sql2fecha($gasto->fields['fecha'], $idioma->fields['formato_fecha']), $row);
						$row = str_replace('%descripcion%', $gasto->fields['descripcion'], $row);
						$row = str_replace('%monto_egreso%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($monto_egreso * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']), $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row); #error gasto 4
						$row = str_replace('%monto_ingreso%', '', $row);
					} elseif ($gasto->fields['ingreso'] > 0) {

						$monto_ingreso = $gasto->fields['monto_cobrable'];
						$totales['total'] -= $gasto->fields['monto_cobrable'] * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']); #error gasto 5
						$totales['total_ingreso'] += $gasto->fields['monto_cobrable'] * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']); #error gasto 6
						$row = str_replace('%fecha%', Utiles::sql2fecha($gasto->fields['fecha'], $idioma->fields['formato_fecha']), $row);
						$row = str_replace('%descripcion%', $gasto->fields['descripcion'], $row);
						$row = str_replace('%monto_egreso%', '', $row);

						$row = str_replace('%monto_ingreso%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($monto_ingreso * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']), $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row); #error gasto 7
					}
					$html .= $row;
				}
				break;

			case 'CTA_CORRIENTE_MOVIMIENTOS_TOTAL': //GenerarDocumentoComun

				$html = str_replace('%total%', __('Total'), $html);
				$gastos_moneda_total = $totales['total'];

				$html = str_replace('%total_monto_egreso%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($totales['total_egreso'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%total_monto_ingreso%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($totales['total_ingreso'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%saldo_periodo%', __('Saldo del periodo'), $html);
				$html = str_replace('%total_monto_gastos%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($totales['total_ingreso'] - $totales['total_egreso'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				break;

			case 'CTA_CORRIENTE_SALDO_FINAL': //GenerarDocumentoComun
				#Total de gastos en moneda que se muestra el cobro.
				$saldo_inicial = $this->SaldoInicialCuentaCorriente();
				$gastos_moneda_total = $totales['total'];
				$saldo_cobro = $gastos_moneda_total;
				$saldo_final = $saldo_inicial - $saldo_cobro;
				$html = str_replace('%saldo_final_cuenta%', __('Saldo final'), $html);

				$html = str_replace('%valor_saldo_final_cuenta%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($saldo_final, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				break;

			case 'TIPO_CAMBIO': //GenerarDocumentoComun
				if ($this->fields['opc_ver_tipo_cambio'] == 0)
					return '';
				//Tipos de Cambio
				$html = str_replace('%titulo_tipo_cambio%', __('Tipos de Cambio'), $html);
				foreach ($cobro_moneda->moneda as $id => $moneda) {
					$html = str_replace("%glosa_moneda_id_$id%", __($moneda['glosa_moneda']), $html);
					$html = str_replace("%simbolo_moneda_id_$id%", $moneda['simbolo'], $html);
					$html = str_replace("%valor_moneda_id_$id%", number_format($moneda['tipo_cambio'], 2, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				}
				break;

			case 'INFORME_GASTOS':
				$nuevomodulofactura = Conf::GetConf($this->sesion, 'NuevoModuloFactura');
				$this->ArrayFacturasDelContrato = $this->FacturasDelContrato($this->sesion, $nuevomodulofactura, null, 'G');
				$totalescontrato = $this->TotalesDelContrato($this->ArrayFacturasDelContrato, $nuevomodulofactura, $this->fields['id_cobro']);
				break;


			case 'INFORME_HONORARIOS':
				$nuevomodulofactura = Conf::GetConf($this->sesion, 'NuevoModuloFactura');
				$this->ArrayFacturasDelContrato = $this->FacturasDelContrato($this->sesion, $nuevomodulofactura, null, 'H');
				$totalescontrato = $this->TotalesDelContrato($this->ArrayFacturasDelContrato, $nuevomodulofactura, $this->fields['id_cobro']);
				break;

			case 'MOROSIDAD': //GenerarDocumentoComun
				if ($this->fields['opc_ver_morosidad'] == 0)
					return '';

				$html = str_replace('%titulo_morosidad%', __('Saldo Adeudado'), $html);
				$html = str_replace('%nota_disclaimer2%', __('nota_morosidad'), $html);
				$html = str_replace('%MOROSIDAD_ENCABEZADO%', $this->GenerarDocumentoComun($parser, 'MOROSIDAD_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%MOROSIDAD_FILAS%', $this->GenerarDocumentoComun($parser, 'MOROSIDAD_FILAS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%MOROSIDAD_HONORARIOS_TOTAL%', $this->GenerarDocumentoComun($parser, 'MOROSIDAD_HONORARIOS_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%MOROSIDAD_GASTOS%', $this->GenerarDocumentoComun($parser, 'MOROSIDAD_GASTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%MOROSIDAD_TOTAL%', $this->GenerarDocumentoComun($parser, 'MOROSIDAD_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				break;


			case 'MOROSIDAD_ENCABEZADO': //GenerarDocumentoComun
				$html = str_replace('%numero_nota_cobro%', __('Folio Carta'), $html);
				$html = str_replace('%numero_factura%', __('Factura'), $html);
				$html = str_replace('%fecha%', __('Fecha'), $html);
				$html = str_replace('%moneda%', __('Moneda'), $html);
				$html = str_replace('%monto_moroso%', __('Monto'), $html);
				break;

			case 'MOROSIDAD_FILAS': //GenerarDocumentoComun
				$row_tmpl = $html;
				$html = '';

				$nuevomodulofactura = Conf::GetConf($this->sesion, 'NuevoModuloFactura');
				$facturasRS = $this->ArrayFacturasDelContrato; //($this->sesion,$nuevomodulofactura);
				$totalescontrato = $this->ArrayTotalesDelContrato; //($facturasRS,$nuevomodulofactura,$this->fields['id_cobro']);

				$totales = $totalescontrato['contrato'];
				$totalescobro = $totalescontrato[$this->fields['id_cobro']];

				if (count($facturasRS) > 0) {

					if ($nuevomodulofactura) {

						foreach ($facturasRS as $facturanumero => $facturaarray) {

							$factura = $facturaarray[0];
							$factura['facturanumero'] = $facturanumero;

							$monto_honorarios = number_format($factura['subtotal_honorarios'], $factura['cifras_decimales'], '.', '');
							$monto_gastos_c_iva = number_format($factura['subtotal_gastos'], $factura['cifras_decimales'], '.', '');
							$monto_gastos_s_iva = number_format($factura['subtotal_gastos_sin_impuesto'], $factura['cifras_decimales'], '.', '');
							$monto_gastos = $monto_gastos_c_iva + $monto_gastos_s_iva;
							$monto_honorarios_moneda = $monto_honorarios * $factura['tasa_cambio'];
							$monto_gastos_c_iva_moneda = $monto_gastos_c_iva * $factura['tasa_cambio'];
							$monto_gastos_s_iva_moneda = $monto_gastos_s_iva * $factura['tasa_cambio'];
							$monto_gastos_moneda = $monto_gastos * $factura['tasa_cambio'];
							$total_en_moneda = $monto_honorarios_moneda = $total_honorarios * ($factura['tipo_cambio_moneda'] / $factura['tipo_cambio']);

							if ($factura['incluye_honorarios'] == 1) {
								$saldo_honorarios = -1 * $factura['saldo'];
								$saldo_gastos = 0;
							} else {
								$saldo_honorarios = 0;
								$saldo_gastos = -1 * $factura['saldo'];
							}

							if (($saldo_honorarios + $saldo_gastos) == 0) {
								continue;
							}

							$row = $row_tmpl;
							$row = str_replace('%numero_nota_cobro%', $factura['id_cobro'], $row);
							$row = str_replace('%numero_factura%', $factura['facturanumero'] ? $factura['facturanumero'] : ' - ', $row);
							$row = str_replace('%fecha%', Utiles::sql2fecha($factura['fecha_enviado_cliente'], '%d-%m-%Y') == 'No existe fecha' ? Utiles::sql2fecha($factura['fecha_emision'], '%d-%m-%Y') : Utiles::sql2fecha($factura['fecha_enviado_cliente'], '%d-%m-%Y'), $row);
							$row = str_replace('%moneda%', $factura['simbolo'] . '&nbsp;', $row);
							$row = str_replace('%moneda_total%', $factura['simbolo_moneda_total'] . '&nbsp;', $row);

							$row = str_replace('%monto_honorarios%', number_format($monto_honorarios, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
							$row = str_replace('%monto_honorarios_moneda%', number_format($monto_honorarios_moneda, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

							$row = str_replace('%monto_gastos_c_iva%', number_format($monto_gastos_c_iva, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
							$row = str_replace('%monto_gastos_c_iva_moneda%', number_format($monto_gastos_c_iva_moneda, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

							$row = str_replace('%monto_gastos_s_iva%', number_format($monto_gastos_s_iva, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
							$row = str_replace('%monto_gastos_s_iva_moneda%', number_format($monto_gastos_s_iva_moneda, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

							$row = str_replace('%monto_gastos%', number_format($monto_gastos, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
							$row = str_replace('%monto_gastos_moneda%', number_format($monto_gastos_moneda, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

							$row = str_replace('%monto_total%', number_format($monto_gastos + $monto_honorarios, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

							$row = str_replace('%saldo_honorarios%', number_format($saldo_honorarios, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
							$row = str_replace('%saldo_gastos%', number_format($saldo_gastos, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

							$row = str_replace(array('%saldo_total%', '%monto_moroso_documento%'), number_format($saldo_honorarios + $saldo_gastos, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

							$row = str_replace('%monto_moroso_moneda_total%', number_format(($monto_gastos_moneda + $monto_honorarios_moneda), $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
							$row = str_replace('%monto_moroso%', number_format(($monto_gastos_moneda + $monto_honorarios_moneda), $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

							$html.=$row;
						}
					} else {

						$query_saldo_adeudado = "
							SELECT d.id_cobro, c.documento, d.fecha, prm_m.simbolo, d.subtotal_honorarios, c.monto_gastos,d.saldo_honorarios, d.saldo_gastos, d.honorarios_pagados, d.gastos_pagados
								FROM documento d
							LEFT JOIN cobro c ON d.id_cobro = c.id_cobro
							LEFT JOIN prm_moneda prm_m ON d.id_moneda = prm_m.id_moneda
								WHERE d.codigo_cliente = '" . $this->fields['codigo_cliente'] . "'
									AND (d.saldo_honorarios + d.saldo_gastos) > 0
									AND c.documento IS NOT NULL
									AND c.id_cobro IS NOT NULL
									AND c.estado != 'CREADO'
									AND c.estado != 'EMITIDO'
									AND c.estado != 'EN REVISION'
									AND c.estado != 'INCOBRABLE'
									AND c.estado != 'PAGADO'
									GROUP BY id_cobro";

						$resp = mysql_query($query_saldo_adeudado, $this->sesion->dbh) or Utiles::errorSQL($query_saldo_adeudado, __FILE__, __LINE__, $this->sesion->dbh);

						while (list($d_numero_cobro, $c_numero_factura, $d_fecha_documento, $d_simbolo_moneda_documento, $d_subtotal_honorarios, $c_montogastos, $d_saldo_honorarios, $d_saldo_gasto, $d_honorarios_pagados, $d_gastos_pagados) = mysql_fetch_array($resp)) {

							$saldo_adeudado = $d_saldo_honorarios + $d_saldo_gasto;

							$row = $row_tmpl;

							//Lyr
							$row = str_replace('%numero_nota_cobro%', $d_numero_cobro, $row);
							$row = str_replace('%numero_factura%', $c_numero_factura ? $c_numero_factura : ' - ', $row);
							$row = str_replace('%fecha%', Utiles::sql2fecha($d_fecha_documento, '%d-%m-%Y'), $row);
							$row = str_replace('%moneda_total%', $d_simbolo_moneda_documento . '&nbsp;', $row);
							$row = str_replace('%monto_moroso_documento%', number_format($saldo_adeudado, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

							//Modulo Facturacion
							$row = str_replace('%moneda%', $factura['simbolo'] . '&nbsp;', $row);

							$row = str_replace('%monto_honorarios%', number_format($d_subtotal_honorarios, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
							$row = str_replace('%monto_gastos%', number_format($c_montogastos, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

							$row = str_replace('%saldo_honorarios%', number_format($d_saldo_honorarios, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
							$row = str_replace('%saldo_gastos%', number_format($d_saldo_gasto, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

							$row = str_replace('%monto_moroso_moneda_total%', number_format(($monto_gastos_moneda + $monto_honorarios_moneda), $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

							$html.=$row;
						}
					}
				} else {
					$html = str_replace('%numero_nota_cobro%', __('No hay facturas adeudadas'), $html);
				}

				break;

			case 'MOROSIDAD_HONORARIOS_TOTAL': //GenerarDocumentoComun
			case 'MOROSIDAD_HONORARIOS': //GenerarDocumentoComun
				$html = str_replace('%numero_nota_cobro%', '', $html);
				$html = str_replace('%numero_factura%', '', $html);
				$html = str_replace('%fecha%', '', $html);
				$html = str_replace('%moneda%', __('Total Honorarios Adeudados') . ':', $html);


				$html = str_replace('%monto_moroso_documento%', $totales['simbolo_moneda_total'] . $this->espacio . number_format($totales['saldo_honorarios'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%monto_moroso%', $totales['simbolo_moneda_total'] . $this->espacio . number_format($totales['saldo_honorarios_moneda'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				//$html = str_replace('%nota%', __('Nota: Si al recibo de esta carta su cuenta se encuentra al d�a, por favor dejar sin efecto.'), $html);
				$html = str_replace('%nota%', __('nota_morosidad_honorarios'), $html);
				break;

			case 'MOROSIDAD_GASTOS_TOTAL': //GenerarDocumentoComun
			case 'MOROSIDAD_GASTOS': //GenerarDocumentoComun
				$html = str_replace('%numero_nota_cobro%', '', $html);
				$html = str_replace('%numero_factura%', '', $html);
				$html = str_replace('%fecha%', '', $html);
				$html = str_replace('%moneda%', __('Total Gastos Adeudados') . ':', $html);

				$html = str_replace('%monto_moroso_documento%', $totales['simbolo_moneda_total'] . $this->espacio . number_format($totales['saldo_gastos'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%monto_moroso%', $totales['simbolo_moneda_total'] . $this->espacio . number_format($totales['saldo_gastos_moneda'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				// $html = str_replace('%nota%', __('Nota: Si al recibo de esta carta su cuenta se encuentra al d�a, por favor dejar sin efecto.'), $html);
				$html = str_replace('%nota%', __('nota_morosidad_gastos'), $html);
				break;

			case 'MOROSIDAD_TOTAL': //GenerarDocumentoComun
				$html = str_replace('%numero_nota_cobro%', '', $html);
				$html = str_replace('%numero_factura%', '', $html);
				$html = str_replace('%fecha%', '', $html);
				$html = str_replace('%moneda%', __('Total Adeudado') . ':', $html);

				if ($nuevomodulofactura) {
					$html = str_replace('%monto_moroso_documento%', $totales['simbolo_moneda_total'] . $this->espacio . number_format(($totales['saldo_honorarios'] + $totales['saldo_gastos']), $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					$html = str_replace('%monto_moroso%', $totales['simbolo_moneda_total'] . $this->espacio . number_format(($totales['saldo_gastos_moneda'] + $totales['saldo_honorarios_moneda']), $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				} else {

					$query_saldo_adeudado_total = "
							SELECT SUM(d.saldo_honorarios + d.saldo_gastos) as saldo_total
								FROM documento d
							LEFT JOIN cobro c ON d.id_cobro = c.id_cobro
								WHERE d.codigo_cliente = '" . $this->fields['codigo_cliente'] . "'
									AND (d.saldo_honorarios + d.saldo_gastos) > 0
									AND c.documento IS NOT NULL
									AND c.id_cobro IS NOT NULL
									AND c.estado != 'CREADO'
									AND c.estado != 'EMITIDO'
									AND c.estado != 'EN REVISION'
									AND c.estado != 'INCOBRABLE'
									AND c.estado != 'PAGADO'";

					$resp = mysql_query($query_saldo_adeudado_total, $this->sesion->dbh) or Utiles::errorSQL($query_saldo_adeudado_total, __FILE__, __LINE__, $this->sesion->dbh);
					list($saldo_total) = mysql_fetch_array($resp);
					$html = str_replace('%monto_moroso_documento%', $totales['simbolo_moneda_total'] . $this->espacio . number_format($saldo_total, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				}

				// $html = str_replace('%nota%', __('Nota: Si al recibo de esta carta su cuenta se encuentra al d�a, por favor dejar sin efecto.'), $html);
				$html = str_replace('%nota%', __('nota_morosidad_total'), $html);
				break;

			case 'GLOSA_ESPECIAL': //GenerarDocumentoComun
				if ($this->fields['codigo_idioma'] != 'en')
					$html = str_replace('%glosa_especial%', 'Emitir cheque/transferencia a nombre de<br />
														TORO Y COMPA��A LIMITADA<br />
														Rut.: 77.440.670-0<br />
														Banco Bice<br />
														Cta. N� 15-72569-9<br />
														Santiago - Chile', $html);
				else
					$html = str_replace('%glosa_especial%', 'Beneficiary: Toro y Compa�ia Limitada, Abogados-Consultores<br />
														Tax Identification Number:  77.440.670-0<br />
														DDA Number:  50704183518<br />
														Bank:  Banco de Chile<br />
														Address:  Apoquindo 5470, Las Condes<br />
														City:  Santiago<br />
														Country: Chile<br />
														Swift code:  BCHICLRM', $html);
				break;

			case 'SALTO_PAGINA': //GenerarDocumentoComun
				//no borrarle al css el BR.divisor
				break;
		}
		return $html;
	}

	function GenerarSeccionCliente($htmlplantilla, $idioma, $moneda, $asunto) {

		global $contrato;
		global $cobro_moneda;
		global $lang;

		$this->FillTemplateData($idioma, $moneda);
		$htmlplantilla = $this->RenderTemplate($htmlplantilla);

		$moneda_total = new Objeto($this->sesion, '', '', 'prm_moneda', 'id_moneda');
		$moneda_total->Load($this->fields['opc_moneda_total'] > 0 ? $this->fields['opc_moneda_total'] : 1);

		if (array_key_exists('codigo_contrato', $contrato->fields)) {
			$htmlplantilla = str_replace('%glosa_codigo_contrato%', __('C�digo') . ' ' . __('Contrato'), $htmlplantilla);
			$htmlplantilla = str_replace('%codigo_contrato%', $contrato->fields['codigo_contrato'], $htmlplantilla);
			$htmlplantilla = str_replace('%glosa_contrato%', $contrato->fields['glosa_contrato'], $htmlplantilla);
		} else {
			$htmlplantilla = str_replace('%glosa_codigo_contrato%', '', $htmlplantilla);
			$htmlplantilla = str_replace('%codigo_contrato%', '', $htmlplantilla);
			$htmlplantilla = str_replace('%glosa_contrato%', '', $htmlplantilla);
		}

		#se carga el primer asunto del cobro (solo usar con clientes que usan un contrato por cada asunto)
		$asunto = new Asunto($this->sesion);
		$asunto->LoadByCodigo($this->asuntos[0]);
		$asuntos = $asunto->fields['glosa_asunto'];
		$i = 1;

		while ($this->asuntos[$i]) {
			$asunto_extra = new Asunto($this->sesion);
			$asunto_extra->LoadByCodigo($this->asuntos[$i]);
			$asuntos .= ', ' . $asunto_extra->fields['glosa_asunto'];
			$i++;
		}

		$htmlplantilla = str_replace('%materia%', __('Materia'), $htmlplantilla);
		$htmlplantilla = str_replace('%factura_emitida_a%', __('Factura emitida a'), $htmlplantilla);
		$htmlplantilla = str_replace('%glosa_asunto_sin_codigo%', $asunto->fields['glosa_asunto'], $htmlplantilla);
		$htmlplantilla = str_replace('%valor_codigo_asunto%', $asunto->fields['codigo_asunto'], $htmlplantilla);
		$htmlplantilla = str_replace('%glosa_asuntos_sin_codigo%', $asuntos, $htmlplantilla);

		$htmlplantilla = str_replace('%glosa_numero_cobro%', __($this->fields['codigo_idioma'] . '_numero_cobro'), $htmlplantilla);
		$htmlplantilla = str_replace('%numero_cobro%', $this->fields['id_cobro'], $htmlplantilla);

		$htmlplantilla = str_replace('%servicios_prestados%', __('POR SERVICIOS PROFESIONALES PRESTADOS'), $htmlplantilla);
		$htmlplantilla = str_replace('%a%', __('A'), $htmlplantilla);
		$htmlplantilla = str_replace('%a_min%', empty($contrato->fields['contacto']) ? '' : __('a'), $htmlplantilla);
		$htmlplantilla = str_replace('%glosa_codigo_postal%', __('C�digo Postal'), $htmlplantilla);
		$htmlplantilla = str_replace('%codigo_postal%', $contrato->fields['factura_codigopostal'], $htmlplantilla);
		$htmlplantilla = str_replace('%cliente%', __('Cliente'), $htmlplantilla);
		$htmlplantilla = str_replace('%nota_cargo%', __('Nota de Cargo'), $htmlplantilla);
		$htmlplantilla = str_replace('%asunto%', __('Asunto'), $htmlplantilla);

		$query = "SELECT glosa_cliente FROM cliente
					WHERE codigo_cliente='" . $this->fields['codigo_cliente'] . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($glosa_cliente) = mysql_fetch_array($resp);

		$htmlplantilla = str_replace('%nombre_cliente%', $glosa_cliente, $htmlplantilla);
		$htmlplantilla = str_replace('%factura_razon_social_o_nombre_cliente%', ( isset($contrato->fields['factura_razon_social']) && $contrato->fields['factura_razon_social'] != '') ? $contrato->fields['factura_razon_social'] : $glosa_cliente, $htmlplantilla);

		if ($this->fields['codigo_idioma'] == 'es') {
			$htmlplantilla = str_replace('%fecha_liquidacion%', __('Fecha Liquidaci�n'), $htmlplantilla);
			$htmlplantilla = str_replace('%cliente_corporativo%', __('Cliente Corporativo'), $htmlplantilla);
			$htmlplantilla = str_replace('%id_asunto%', __('ID Asunto'), $htmlplantilla);
		} else {
			$htmlplantilla = str_replace('%fecha_liquidacion%', __('Invoice Date'), $htmlplantilla);
			$htmlplantilla = str_replace('%cliente_corporativo%', __('Corporate Customer '), $htmlplantilla);
			$htmlplantilla = str_replace('%id_asunto%', __('Matter ID'), $htmlplantilla);
		}

		$htmlplantilla = str_replace('%factura%', __('Factura'), $htmlplantilla);
		$htmlplantilla = str_replace('%factura_mayuscula%', __('FACTURA'), $htmlplantilla);
		$htmlplantilla = str_replace('%codigo_cliente%', $contrato->fields['codigo_cliente'], $htmlplantilla);
		$htmlplantilla = str_replace('%fecha_emision%', ($this->fields['fecha_emision'] == '0000-00-00' || $this->fields['fecha_emision'] == '' || $this->fields['fecha_emision'] == NULL ) ? '&nbsp;' : Utiles::sql2fecha($this->fields['fecha_emision'], $idioma->fields['formato_fecha']), $htmlplantilla);
		$htmlplantilla = str_replace('%glosa_cliente%', $contrato->fields['factura_razon_social'], $htmlplantilla);
		$htmlplantilla = str_replace('%direccion%', __('Direcci�n'), $htmlplantilla);
		$htmlplantilla = str_replace('%valor_direccion%', nl2br($contrato->fields['factura_direccion']), $htmlplantilla);
		$htmlplantilla = str_replace('%valor_direccion_uc%', nl2br(ucwords(strtolower($contrato->fields['factura_direccion']))), $htmlplantilla);
		$direccion = explode('//', $contrato->fields['direccion_contacto']);
		$htmlplantilla = str_replace('%direccion_carta%', nl2br($direccion[0]), $htmlplantilla);
		$htmlplantilla = str_replace('%rut%', __('RUT'), $htmlplantilla);
		$htmlplantilla = str_replace('%rut_minuscula%', __('Rut'), $htmlplantilla);

		if ($contrato->fields['rut'] != '0' || $contrato->fields['rut'] != '') {
			$rut_split = explode('-', $contrato->fields['rut']);
		}

		$htmlplantilla = str_replace('%valor_rut_sin_formato%', $contrato->fields['rut'], $htmlplantilla);
		$htmlplantilla = str_replace('%valor_rut%', $rut_split[0] ? $this->StrToNumber($rut_split[0]) . "-" . $rut_split[1] : __(''), $htmlplantilla);
		$htmlplantilla = str_replace('%giro_factura%', __('Giro'), $htmlplantilla);
		$htmlplantilla = str_replace('%giro_factura_valor%', $contrato->fields['factura_giro'], $htmlplantilla);
		$htmlplantilla = str_replace('%contacto%', empty($contrato->fields['contacto']) ? '' : __('Contacto'), $htmlplantilla);
		$htmlplantilla = str_replace('%atencion%', empty($contrato->fields['contacto']) ? '' : __('Atenci�n'), $htmlplantilla);

		if (Conf::GetConf($this->sesion, 'TituloContacto')) {
			$htmlplantilla = str_replace('%valor_contacto%', empty($contrato->fields['contacto']) ? '' : $contrato->fields['contacto'] . ' ' . $contrato->fields['apellido_contacto'], $htmlplantilla);
		} else {
			$htmlplantilla = str_replace('%valor_contacto%', empty($contrato->fields['contacto']) ? '' : $contrato->fields['contacto'], $htmlplantilla);
		}

		$htmlplantilla = str_replace('%atte%', empty($contrato->fields['contacto']) ? '' : '(' . __('Atte') . ')', $htmlplantilla);
		$htmlplantilla = str_replace('%telefono%', empty($contrato->fields['fono_contacto']) ? '' : __('Tel�fono'), $htmlplantilla);
		$htmlplantilla = str_replace('%valor_telefono%', empty($contrato->fields['fono_contacto']) ? '' : $contrato->fields['fono_contacto'], $htmlplantilla);

		if (Conf::GetConf($this->sesion, 'NuevoModuloFactura')) {
			$query = "SELECT CAST( GROUP_CONCAT( numero ) AS CHAR ) AS numeros
									FROM factura
									WHERE id_cobro ='{$this->fields['id_cobro']}'";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			list($numero_factura) = mysql_fetch_array($resp);

			if (!$numero_factura) {
				$numero_factura = '';
			}
			$htmlplantilla = str_replace('%numero_factura%', $numero_factura, $htmlplantilla);
		} else if (Conf::GetConf($this->sesion, 'PermitirFactura')) {
			$htmlplantilla = str_replace('%numero_factura%', $this->fields['documento'], $htmlplantilla);
		} else {
			$htmlplantilla = str_replace('%numero_factura%', '', $htmlplantilla);
		}

		$htmlplantilla = str_replace('%documentos_tributarios%', $this->fields['documento'], $htmlplantilla);

		$htmlplantilla = str_replace('%liquidacion%', __('Liquidaci�n'), $htmlplantilla);
		$htmlplantilla = str_replace('%solo_num_factura%', $this->fields['id_cobro'], $htmlplantilla);
		$htmlplantilla = str_replace('%ciudad_cliente%', $contrato->fields['factura_ciudad'], $htmlplantilla);
		$htmlplantilla = str_replace('%comuna_cliente%', $contrato->fields['factura_comuna'], $htmlplantilla);

		if ($lang == 'es') {
			$fecha_lang = Conf::GetConf($this->sesion, 'CiudadEstudio') . ', ' . ucfirst(Utiles::sql3fecha(date('Y-m-d'), '%e de %B de %Y'));
		} else {
			$fecha_lang = Conf::GetConf($this->sesion, 'CiudadEstudio') . ' (' . Conf::GetConf($this->sesion, 'PaisEstudio') . '), ' . ucfirst(Utiles::sql3fecha(date('Y-m-d'), '%e de %B de %Y'));
		}

		if ($lang == "en") {
			$ciudad_fecha_ingles = Conf::GetConf($this->sesion, 'CiudadEstudio') . ' ' . date('F d, Y');
			$fecha_segun_lenguaje = date('F d, Y');
		} else {
			$ciudad_fecha_ingles = Conf::GetConf($this->sesion, 'CiudadEstudio') . ' ' . ucfirst(Utiles::sql3fecha(date('Y-m-d'), '%e de %B de %Y'));
			$fecha_segun_lenguaje = ucfirst(Utiles::sql3fecha(date('Y-m-d'), '%e de %B de %Y'));
		}

		$htmlplantilla = str_replace('%fecha_especial%', $fecha_lang, $htmlplantilla);
		$htmlplantilla = str_replace('%ciudad_fecha_ingles%', $ciudad_fecha_ingles, $htmlplantilla);
		$htmlplantilla = str_replace('%fecha_segun_lenguaje%', $fecha_segun_lenguaje, $htmlplantilla);
		$htmlplantilla = str_replace('%fecha_slash%', date('d/m/Y'), $htmlplantilla);
		$htmlplantilla = str_replace('%numero_cobro%', $this->fields['id_cobro'], $htmlplantilla);

		if ($contrato->fields['id_pais'] > 0) {
			$query = "SELECT nombre FROM prm_pais
										WHERE id_pais=" . $contrato->fields['id_pais'];
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			list($nombre_pais) = mysql_fetch_array($resp);
			$htmlplantilla = str_replace('%nombre_pais%', $nombre_pais, $htmlplantilla);
			$htmlplantilla = str_replace('%nombre_pais_mayuscula%', strtoupper($nombre_pais), $htmlplantilla);
		} else {
			$htmlplantilla = str_replace('%nombre_pais%', '', $htmlplantilla);
			$htmlplantilla = str_replace('%nombre_pais_mayuscula%', '', $htmlplantilla);
		}

		return $htmlplantilla;
	}

	function GenerarSeccionDetallePago($html, $idioma) {

		global $cobro_moneda;

		$fila = $html;
		$monto_total = (float) $x_resultados['monto_cobro_original_con_iva'][$this->fields['opc_moneda_total']];
		$moneda = $cobro_moneda->moneda[$this->fields['opc_moneda_total']];

		//Pagos
		if ($this->TienePagosAdelantos()) {
			$query = "
					SELECT doc_pago.glosa_documento, (neteo_documento.valor_cobro_honorarios + neteo_documento.valor_cobro_gastos) * -1 AS monto, doc_pago.fecha, prm_moneda.tipo_cambio
					FROM documento AS doc_pago
					JOIN neteo_documento ON doc_pago.id_documento = neteo_documento.id_documento_pago
					JOIN documento AS doc ON doc.id_documento = neteo_documento.id_documento_cobro
					JOIN prm_moneda ON prm_moneda.id_moneda = doc_pago.id_moneda
					WHERE doc.id_cobro = " . $this->fields['id_cobro'];
			$pagos = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			while ($pago = mysql_fetch_assoc($pagos)) {
				$fila_adelanto_ = str_replace('%descripcion%', substr($pago['glosa_documento'], 0, 30 + strpos(' ', substr($pago['glosa_documento'], 30, 50))) . ' (' . $pago['fecha'] . ')', $html);
				$monto_pago = $pago['monto'];
				$monto_pago_simbolo = $moneda['simbolo'] . $this->espacio . number_format($monto_pago, $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
				$fila_adelanto_ = str_replace('%saldo_pago%', $monto_pago_simbolo, $fila_adelanto_);

				$saldo += (float) $monto_pago;
				$fila_adelantos .= $fila_adelanto_;
			}
			$monto_total += (float) $saldo;
			$monto_total_simbolo = $moneda['simbolo'] . $this->espacio . number_format($monto_total, $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
			$fila_adelantos .= '<tr class="tr_total"><td>' . __('Saldo del cobro') . '</td><td align="right">' . $monto_total_simbolo . '</td></tr>';
		}

		//Deuda
		$query = "
				SELECT SUM(( documento.saldo_honorarios + documento.saldo_gastos ) * cm1.tipo_cambio / cm2.tipo_cambio) AS saldo_total_cobro
				FROM documento
				LEFT JOIN cobro ON cobro.id_cobro = documento.id_cobro
				LEFT JOIN cobro_moneda as cm1 ON cm1.id_cobro = documento.id_cobro AND cm1.id_moneda = documento.id_moneda
				LEFT JOIN cobro_moneda as cm2 ON cm2.id_cobro = '" . $this->fields['id_cobro'] . "' AND cm2.id_moneda = '" . $this->fields['opc_moneda_total'] . "'
				WHERE documento.codigo_cliente = '" . $this->fields['codigo_cliente'] . "'
				AND documento.es_adelanto <> 1 AND documento.tipo_doc = 'N'
				AND (documento.saldo_honorarios + documento.saldo_gastos) > 0
				AND documento.id_cobro <> " . $this->fields['id_cobro'] . "
				AND cobro.estado NOT IN ('PAGADO', 'INCOBRABLE', 'CREADO', 'EN REVISION')";
		$saldo_total_cobro = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		$saldo_total_cobro = mysql_fetch_assoc($saldo_total_cobro);
		$saldo_total_cobro = (float) $saldo_total_cobro['saldo_total_cobro'];
		$saldo_total_cobro_simbolo = $moneda['simbolo'] . $this->espacio . number_format($saldo_total_cobro, $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
		$fila_adelantos .= '<tr class="tr_total"><td>' . __('Saldo anterior') . '</td><td align="right">' . $saldo_total_cobro_simbolo . '</td></tr>';

		$saldo_adeudado = $moneda['simbolo'] . $this->espacio . number_format($monto_total + $saldo_total_cobro, $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
		$fila_adelantos .= '<tr class="tr_total"><td>' . __('Saldo total adeudado') . '</td><td align="right">' . $saldo_adeudado . '</td></tr>';

		return $fila_adelantos;
	}

	function GenerarSeccionDetallePagoContrato($html, $idioma) {
		global $cobro_moneda, $x_resultados;
		/**
		 * Etiquetas
		 * %documentos_de_pago% pagos sin contar adelantos
		 * %documentos_de_adelanto% pagos por concepto de adelantos
		 * %pagos_liquidacion% la suma de los dos anteriores
		 * %blank_line% inserta una fila en blanco para ayudar a diagramar
		 *
		 * %saldo_del_cobro% El total facturado menos los pagos que se hayan hecho
		 * %saldo_anterior% La suma de los %saldo_del_cobro% de OTRAS liquidaciones que pertenezcan al mismo contrato
		 * %saldo_total_adeudado% La suma %saldo_del_cobro% de todas las liquiedaciones que pertenezcan al contrato incluida la actual
		 *
		 * %saldo_total_cobro_sinfactura% Hom�logo de %saldo_del_cobro% pero considera el monto total de la liquidaci�n, en vez de lo facturado
		 * %saldo_otras_liquidaciones_sinfactura% La suma de los saldos de OTRAS liquidaciones que pertenezcan al mismo contrato
		 * %saldo_contrato_sinfactura% La suma de los saldos de OTRAS liquidaciones que pertenezcan al mismo contrato + %saldo_total_cobro_sinfactura%
		 *
		 * %adelantos_sin_asignar% adelantos del mismo cliente no asignados, restringidos al presente contrato (o sin restricci�n de contrato cuando estamos en un cobro del contrato por defecto para este cliente)
		 * */
		$fila = $html;
		$fila_adelantos = "";
		$htmltemporal = $html;

		$moneda = $cobro_moneda->moneda[$this->fields['opc_moneda_total']];
		$moneda_base = Utiles::MonedaBase($this->sesion);

		$seccion_detalle_pago_contrato = $this->DetallePagoContrato($this->sesion, $this->fields['id_cobro']);

		$montoadelantosinasignar = $seccion_detalle_pago_contrato['montoadelantosinasignar'];
		$saldo = $seccion_detalle_pago_contrato['saldo'];
		$saldo_adelantos = $seccion_detalle_pago_contrato['saldo_adelantos'];
		$saldo_pagos = $seccion_detalle_pago_contrato['saldo_pagos'];
		$fila_adelantos = $seccion_detalle_pago_contrato['fila_adelantos'];
		$monto_total_cobro = $seccion_detalle_pago_contrato['monto_total_cobro'];
		$saldo_total_cobro = $seccion_detalle_pago_contrato['saldo_total_cobro'];
		$saldo_total_cobro_sinfactura = $seccion_detalle_pago_contrato['saldo_total_cobro_sinfactura'];
		$moneda_saldo = $cobro_moneda->moneda[$seccion_detalle_pago_contrato['moneda_saldo']];

		$saldo_otras_liquidaciones = UtilesApp::CambiarMoneda(
						$seccion_detalle_pago_contrato['saldo_otras_liquidaciones'], $moneda_saldo['tipo_cambio'], $moneda_saldo['cifras_decimales'], $x_resultados['tipo_cambio_opc_moneda_total'], $x_resultados['cifras_decimales_opc_moneda_total']
		);

		$saldo_total_adeudado = UtilesApp::CambiarMoneda(
						$seccion_detalle_pago_contrato['saldo_contrato'], $moneda_saldo['tipo_cambio'], $moneda_saldo['cifras_decimales'], $x_resultados['tipo_cambio_opc_moneda_total'], $x_resultados['cifras_decimales_opc_moneda_total']
		);

		$saldo_otras_liquidaciones_sinfactura = UtilesApp::CambiarMoneda(
						$seccion_detalle_pago_contrato['saldo_otras_liquidaciones_sinfactura'], $moneda_saldo['tipo_cambio'], $moneda_saldo['cifras_decimales'], $x_resultados['tipo_cambio_opc_moneda_total'], $x_resultados['cifras_decimales_opc_moneda_total']
		);

		$saldo_contrato_sinfactura = UtilesApp::CambiarMoneda(
						$seccion_detalle_pago_contrato['saldo_contrato_sinfactura'], $moneda_saldo['tipo_cambio'], $moneda_saldo['cifras_decimales'], $x_resultados['tipo_cambio_opc_moneda_total'], $x_resultados['cifras_decimales_opc_moneda_total']
		);

		$documentos_de_pago .=number_format($saldo_pagos, $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
		$documentos_de_adelanto .=number_format($saldo_adelantos, $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
		$pagos_liquidacion .=number_format($saldo_pagos + $saldo_adelantos, $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);

		$saldo_total_cobro = number_format($saldo_total_cobro, $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
		$saldo_otras_liquidaciones = number_format($saldo_otras_liquidaciones, $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
		$saldo_total_adeudado = number_format($saldo_total_adeudado, $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);

		$saldo_total_cobro_sinfactura = number_format($saldo_total_cobro_sinfactura, $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
		$saldo_otras_liquidaciones_sinfactura = number_format($saldo_otras_liquidaciones_sinfactura, $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
		$saldo_contrato_sinfactura = number_format($saldo_contrato_sinfactura, $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);

		$htmltemporal = str_replace('%documentos_de_pago%', '<tr class="tr_total"><td>' . __('Pagos Realizados') . '</td><td align="right">' . $moneda['simbolo'] . $this->espacio . $documentos_de_pago . '</td></tr>', $htmltemporal);
		$htmltemporal = str_replace('%documentos_de_adelanto%', '<tr class="tr_total"><td>' . __('Adelantos Utilizados') . '</td><td align="right">' . $moneda['simbolo'] . $this->espacio . $documentos_de_adelanto . '</td></tr>', $htmltemporal);
		$htmltemporal = str_replace('%pagos_liquidacion%', '<tr><td>' . __($this->fields['codigo_idioma'] . '_pagos_liquidacion') . '</td><td align="right">' . $moneda['simbolo'] . $this->espacio . $pagos_liquidacion . '</td></tr>', $htmltemporal);
		$htmltemporal = str_replace('%saldo_del_cobro%', '<tr class="tr_total"><td>' . __('Saldo del cobro') . '</td><td align="right">' . $moneda['simbolo'] . $this->espacio . $saldo_total_cobro . '</td></tr>', $htmltemporal);
		$htmltemporal = str_replace('%blank_line%', "<tr><td> </td><td> </td></tr>", $htmltemporal);
		$htmltemporal = str_replace('%saldo_anterior%', '<tr><td>' . __('Saldo anterior') . '</td><td align="right">' . $moneda['simbolo'] . $this->espacio . $saldo_otras_liquidaciones . '</td></tr>', $htmltemporal);
		$htmltemporal = str_replace('%saldo_total_adeudado%', '<tr class="tr_total"><td>' . __('Saldo total adeudado') . '</td><td align="right">' . $moneda['simbolo'] . $this->espacio . $saldo_total_adeudado . '</td></tr>', $htmltemporal);

		$htmltemporal = str_replace('%saldo_total_cobro_sinfactura%', '<tr class="tr_total"><td>' . __('Saldo del cobro') . '</td><td align="right">' . $moneda['simbolo'] . $this->espacio . $saldo_total_cobro_sinfactura . '</td></tr>', $htmltemporal);
		$htmltemporal = str_replace('%saldo_otras_liquidaciones_sinfactura%', '<tr><td>' . __('Saldo anterior') . '</td><td align="right">' . $moneda['simbolo'] . $this->espacio . $saldo_otras_liquidaciones_sinfactura . '</td></tr>', $htmltemporal);
		$htmltemporal = str_replace('%saldo_contrato_sinfactura%', '<tr class="tr_total"><td>' . __('Saldo total adeudado') . '</td><td align="right">' . $moneda['simbolo'] . $this->espacio . $saldo_contrato_sinfactura . '</td></tr>', $htmltemporal);

		if ($montoadelantosinasignar > 0) {
			$montoadelantosinasignar = number_format($montoadelantosinasignar, $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
			$htmltemporal = str_replace('%adelantos_sin_asignar%', '<tr><td>' . __($this->fields['codigo_idioma'] . '_adelantos_sin_asignar') . '</td><td align="right">' . $moneda['simbolo'] . $this->espacio . $montoadelantosinasignar . '</td></tr>', $htmltemporal);
		} else {
			$htmltemporal = str_replace('%adelantos_sin_asignar%', '', $htmltemporal);
		}

		return $htmltemporal;
	}

	function GenerarSeccionResumenProfesional($parser, $theTag, $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, &$idioma, & $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto) {

		global $contrato;
		global $cobro_moneda;
		//global $moneda_total;
		global $masi;
		global $x_detalle_profesional;
		global $x_resumen_profesional;
		global $x_factor_ajuste;
		global $x_resultados;
		global $x_cobro_gastos;

		$moneda_total = new Objeto($this->sesion, '', '', 'prm_moneda', 'id_moneda');
		$moneda_total->Load($this->fields['opc_moneda_total'] > 0 ? $this->fields['opc_moneda_total'] : 1);


		if (!isset($parser->tags[$theTag]))
			return;

		$html = $parser->tags[$theTag];

		switch ($theTag) {

			case 'RESUMEN_PROFESIONAL':
				if ($this->fields['forma_cobro'] == 'ESCALONADA') {
					// Obtener datos escalonados
					$chargingBusiness = new ChargingBusiness($this->sesion);
					$slidingScales = $chargingBusiness->getSlidingScalesArrayDetail($this->fields['id_cobro']);

					$cobro_valores = array();

					$cobro_valores['totales'] = array();
					$cobto_valores['datos_escalonadas'] = array();

					$this->CargarEscalonadas();
					$cobro_valores['datos_escalonadas'] = $this->escalonadas;


					$dato_monto_cobrado = " ( trabajo.tarifa_hh * TIME_TO_SEC( trabajo.duracion_cobrada ) ) / 3600 ";

					// Se seleccionan todos los trabajos del cobro, se incluye que sea cobrable ya que a los trabajos visibles
					// tambien se consideran dentro del cobro, tambien se incluye el valor del retainer del trabajo.

					if ($lang == 'es') {
						$query_categoria_lang = "prm_categoria_usuario.glosa_categoria as categoria";
					} else {
						$query_categoria_lang = "IFNULL(prm_categoria_usuario.glosa_categoria_lang, prm_categoria_usuario.glosa_categoria) as categoria";
					}

					$query = "SELECT SQL_CALC_FOUND_ROWS trabajo.duracion_cobrada,
									trabajo.descripcion,
									trabajo.fecha,
									trabajo.id_usuario,
									$dato_monto_cobrado as monto_cobrado,
									trabajo.id_moneda as id_moneda_trabajo,
									trabajo.id_trabajo,
									trabajo.tarifa_hh,
									trabajo.cobrable,
									trabajo.visible,
									trabajo.codigo_asunto,
									CONCAT_WS(' ', nombre, apellido1) as usr_nombre,
									$query_categoria_lang
							FROM trabajo
							JOIN usuario ON trabajo.id_usuario=usuario.id_usuario
							LEFT JOIN prm_categoria_usuario ON prm_categoria_usuario.id_categoria_usuario = usuario.id_categoria_usuario
							WHERE trabajo.id_cobro = '" . $this->fields['id_cobro'] . "'
							AND trabajo.id_tramite=0
							ORDER BY trabajo.fecha ASC";
					$lista_trabajos = new ListaTrabajos($this->sesion, '', $query);

					list($cobro_total_honorario_cobrable, $total_minutos_tmp, $detalle_trabajos) = $this->MontoHonorariosEscalonados($lista_trabajos);

					$cobro_valores['totales']['valor'] = $cobro_total_honorario_cobrable;
					$cobro_valores['totales']['duracion'] = ($total_minutos_tmp / 60);

					// Asignar datos escalonados que vienen de ChargingBusiness
					$cobro_valores['detalle'] = $slidingScales['detalle'];

					$cantidad_escalonadas = $cobro_valores['datos_escalonadas']['num'];

					$resumen_encabezado = $this->GenerarSeccionResumenProfesional($parser, 'PROFESIONAL_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto);

					$html = "<br /><span class=\"subtitulo_seccion\">%glosa_profesional%</span><br>";
					$html = str_replace('%glosa_profesional%', __('Resumen detalle profesional'), $html);

					if ($lang == 'es') {
						$html = str_replace('%resumen_profesional%', __('Resumen Detalle Profesional'), $html);
					} else {
						$html = str_replace('%resumen_profesional%', __('TIMEKEEPER SUMMARY'), $html);
					}

					$esc = 1;
					while ($esc <= $cantidad_escalonadas) {
						if (is_array($cobro_valores['detalle']['detalle_escalonadas'][$esc]['usuarios'])) {
							$html .= "<h4>Escalon $esc: ";
							if ($cobro_valores['datos_escalonadas'][$esc]['monto'] > 0) {
								$html .= " Monto Fijo " . $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . $this->espacio . number_format($cobro_valores['datos_escalonadas'][$esc]['monto'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . "</h4>";
							} else {
								$html .= " Tarifa HH";
								if ($cobro_valores['datos_escalonadas'][$esc]['descuento'] > 0) {
									$html .= " con " . $cobro_valores['datos_escalonadas'][$esc]['descuento'] . "% de descuento";
								}
								$html .= "</h4>";
							}
							$html .= "<table class=\"tabla_normal\" width=\"100%\">";
							$html .= $resumen_encabezado;

							foreach ($cobro_valores['detalle']['detalle_escalonadas'][$esc]['usuarios'] as $id_usuario => $usuarios) {
								$resumen_fila = $parser->tags['PROFESIONAL_FILAS'];

								$resumen_fila = str_replace('%nombre%', $usuarios['usuario'], $resumen_fila);
								if ($this->fields['opc_ver_profesional_categoria']) {
									$resumen_fila = str_replace('%categoria%', __($usuarios['categoria']), $resumen_fila);
								} else {
									$resumen_fila = str_replace('%categoria%', '', $resumen_fila);
								}
								$resumen_fila = str_replace('%hh_demo%', Utiles::Decimal2GlosaHora(round($usuarios['duracion']/60, 2)), $resumen_fila);
								$resumen_fila = str_replace('%hh_trabajada%', '', $resumen_fila);
								if ($this->fields['opc_ver_profesional_tarifa']) {
									$resumen_fila = str_replace('%td_tarifa%', '<td align="center">%tarifa_horas_demo%</td>', $resumen_fila);
									$resumen_fila = str_replace('%td_tarifa_ajustada%', '<td align="center">%tarifa_horas_demo%</td>', $resumen_fila);
								} else {
									$resumen_fila = str_replace('%td_tarifa%', '', $resumen_fila);
									$resumen_fila = str_replace('%td_tarifa_ajustada%', '', $resumen_fila);
								}
								$resumen_fila = str_replace('%tarifa_horas_demo%', number_format($usuarios['tarifa'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $resumen_fila);
								if ($this->fields['opc_ver_profesional_importe']) {
									$resumen_fila = str_replace('%td_importe%', '<td align="right">%total_horas_demo%</td>', $resumen_fila);
									$resumen_fila = str_replace('%td_importe_ajustado%', '<td align="right">%total_horas_demo%</td>', $resumen_fila);
								} else {
									$resumen_fila = str_replace('%td_importe%', '', $resumen_fila);
									$resumen_fila = str_replace('%td_importe_ajustado%', '', $resumen_fila);
								}
								$resumen_fila = str_replace('%total_horas_demo%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . $this->espacio . number_format($usuarios['valor'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $resumen_fila);

								$resumen_fila = str_replace('%td_descontada%', '', $resumen_fila);
								$resumen_fila = str_replace('%td_cobrable%', '', $resumen_fila);
								$resumen_fila = str_replace('%td_retainer%', '', $resumen_fila);
								if ($usuarios['duracion'] > 0) {
									$html .= $resumen_fila;
								}
							}
							// Total
							$resumen_total = $parser->tags['PROFESIONAL_TOTAL'];

							$resumen_total = str_replace('%glosa%', __('Total'), $resumen_total);
							$resumen_total = str_replace('%hh_trabajada%', '', $resumen_total);
							$resumen_total = str_replace('%td_descontada%', '', $resumen_total);
							$resumen_total = str_replace('%td_cobrable%', '', $resumen_total);
							$resumen_total = str_replace('%td_retainer%', '', $resumen_total);
							$resumen_total = str_replace('%hh_demo%', Utiles::Decimal2GlosaHora(round($cobro_valores['detalle']['detalle_escalonadas'][$esc]['totales']['duracion']/60, 2)), $resumen_total);
							if ($this->fields['opc_ver_profesional_tarifa']) {
								$resumen_total = str_replace('%td_tarifa%', '<td>&nbsp;</td>', $resumen_total);
								$resumen_total = str_replace('%td_tarifa_ajustada%', '<td>&nbsp;</td>', $resumen_total);
							} else {
								$resumen_total = str_replace('%td_tarifa%', '', $resumen_total);
								$resumen_total = str_replace('%td_tarifa_ajustada%', '', $resumen_total);
							}
							if ($this->fields['opc_ver_profesional_importe']) {
								$resumen_total = str_replace('%td_importe%', '<td align="right">%total_horas_demo%</td>', $resumen_total);
								$resumen_total = str_replace('%td_importe_ajustado%', '<td align="right">%total_horas_demo%</td>', $resumen_total);
							} else {
								$resumen_total = str_replace('%td_importe%', '', $resumen_total);
								$resumen_total = str_replace('%td_importe_ajustado%', '', $resumen_total);
							}
							$resumen_total = str_replace('%total_horas_demo%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . $this->espacio . number_format($cobro_valores['detalle']['detalle_escalonadas'][$esc]['totales']['valor'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $resumen_total);
							$html .= $resumen_total;
							$html .= "</table>";
						}
						$esc++;
					}
					return $html;
				}
				$columna_hrs_retainer = $this->fields['opc_ver_detalle_retainer'] && ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL');


				$columna_hrs_trabajadas_categoria = $GLOBALS['columna_hrs_trabajadas_categoria'];

				$columna_hrs_trabajadas = $this->fields['opc_ver_horas_trabajadas'];

				if ($this->fields['opc_ver_profesional'] == 0)
					return '';
				// Encabezado
				$resumen_encabezado = $this->GenerarSeccionResumenProfesional($parser, 'PROFESIONAL_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto);

				// Filas
				$resumen_filas = array();

				//Se ve si la cantidad de horas trabajadas son menos que las horas del retainer esto para que no hayan problemas al mostrar los datos
				$han_trabajado_menos_del_retainer = (($this->fields['total_minutos'] / 60) < $this->fields['retainer_horas']) && ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL');

				$retainer = false;
				$descontado = false;
				$flatfee = false;
				$incobrables = false;
				if (is_array($x_resumen_profesional)) {
					foreach ($x_resumen_profesional as $prof => $data) {
						if ($data['duracion_retainer'] > 0 && ( $this->fields['forma_cobro'] != 'FLAT FEE' || ( Conf::GetConf($this->sesion, 'ResumenProfesionalVial') ) ))
							$retainer = true;
						//if ($data['duracion_descontada'] > 0)
						$descontado = true;
						if ($data['flatfee'] > 0)
							$flatfee = true;
						if ($data['duracion_incobrables'] > 0)
							$incobrables = true;
					}
				}

				$resumen_hrs_trabajadas = 0;
				$resumen_hrs_cobradas = 0;
				$resumen_hrs_cobradas_cob = 0;
				$resumen_hrs_retainer = 0;
				$resumen_hrs_descontadas = 0;
				$resumen_hrs_incobrables = 0;
				$resumen_hh = 0;
				$resumen_valor = 0;

				foreach ($x_resumen_profesional as $prof => $data) {
					// Calcular totales
					$resumen_hrs_trabajadas += $data['duracion_trabajada'];
					$resumen_hrs_cobradas += $data['duracion_cobrada'];
					$resumen_hrs_cobradas_cob += $data['duracion_cobrada'];
					$resumen_hrs_cobradas_cob -= $data['duracion_incobrables'];
					$resumen_hrs_retainer += $data['duracion_retainer'];
					$resumen_hrs_descontadas += $data['duracion_descontada'];
					$resumen_hrs_incobrables += $data['duracion_incobrables'];
					$resumen_hh += $data['duracion_tarificada'];
					if ($this->fields['forma_cobro'] == 'ESCALONADA') {
						$resumen_valor += $data['monto_cobrado_escalonada'];
					} else {
						$resumen_valor += $data['valor_tarificada'];
					}

					$html3 = $parser->tags['PROFESIONAL_FILAS'];
					$html3 = str_replace('%username%', $data['username'], $html3);
					if ($this->fields['opc_ver_profesional_iniciales'] == 1) {
						$html3 = str_replace('%nombre%', $data['username'], $html3);
					} else {
						$html3 = str_replace('%nombre%', $data['nombre_usuario'] . ' (' . $data['username'] . ')', $html3);
					}
					if ($this->fields['opc_ver_profesional_tarifa'] == 1) {
						$html3 = str_replace('%td_tarifa%', '<td align="center">%tarifa_horas_demo%</td>', $html3);
						$html3 = str_replace('%td_tarifa_ajustada%', '<td align="center">%tarifa_horas_ajustada%</td>', $html3);
					} else {
						$html3 = str_replace('%td_tarifa%', '', $html3);
						$html3 = str_replace('%td_tarifa_ajustada%', '', $html3);
					}
					if ($this->fields['opc_ver_profesional_importe'] == 1) {
						$html3 = str_replace('%td_importe%', '<td align="right">%total_horas_demo%</td>', $html3);
						$html3 = str_replace('%td_importe_ajustado%', '<td align="right">%total_horas_ajustado%</td>', $html3);
					} else {
						$html3 = str_replace('%td_importe%', '', $html3);
						$html3 = str_replace('%td_importe_ajustado%', '', $html3);
					}
					//muestra las iniciales de los profesionales
					list($nombre, $apellido_paterno, $extra, $extra2) = explode(' ', $data['nombre_usuario'], 4);
					$html3 = str_replace('%iniciales%', $nombre[0] . $apellido_paterno[0] . $extra[0] . $extra2[0], $html3);
					if ($this->fields['forma_cobro'] == 'FLAT FEE' || $this->fields['opc_ver_horas_trabajadas'] || Conf::GetConf($this->sesion, 'NotaDeCobroVFC')) {
						$html3 = str_replace('%hrs_trabajadas%', $data['glosa_duracion_cobrada'], $html3);
						$html3 = str_replace('%hrs_trabajadas_vio%', $data['glosa_duracion_cobrada'], $html3);
					} else if ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') {
						if ($han_trabajado_menos_del_retainer)
							$html3 = str_replace('%hrs_trabajadas%', $data['glosa_duracion_cobrada'], $html3);
						else
							$html3 = str_replace('%hrs_trabajadas%', ($columna_hrs_trabajadas ? $data['glosa_duracion_cobrada'] : ''), $html3);
						$html3 = str_replace('%hrs_trabajadas_vio%', $data['glosa_duracion_cobrada'], $html3);
					}
					else {
						$html3 = str_replace('%hrs_trabajadas%', '', $html3);
						$html3 = str_replace('%hrs_trabajadas_vio%', ($columna_hrs_trabajadas ? $data['glosa_duracion_cobrada'] : ''), $html3);
					}
					if ($han_trabajado_menos_del_retainer)
						$html3 = str_replace('%hrs_retainer%', $data['glosa_duracion_retainer'], $html3);
					else
						$html3 = str_replace('%hrs_retainer%', ($columna_hrs_retainer ? $data['glosa_duracion_retainer'] : ''), $html3);
					if ($han_trabajado_menos_del_retainer && !$this->fields['opc_ver_detalle_retainer'])
						$html3 = str_replace('%hrs_retainer_vio%', '', $html3);
					else if ($this->fields['forma_cobro'] == 'FLAT FEE' || $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL')
						$html3 = str_replace('%hrs_retainer_vio%', $data['glosa_duracion_retainer'], $html3);
					else
						$html3 = str_replace('%hrs_retainer_vio%', '', $html3);
					$html3 = str_replace('%hrs_descontadas%', ($columna_hrs_descontadas ? $data['glosa_duracion_descontada'] : ''), $html3);

					if ($this->fields['opc_ver_horas_trabajadas']) {
						if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
							$html3 = str_replace('%hh_trabajada%', number_format($data['duracion_trabajada'], Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html3);
						} else {
							$html3 = str_replace('%hh_trabajada%', $data['glosa_duracion_trabajada'], $html3);
						}
						if ($descontado) {
							$html3 = str_replace('%td_descontada%', '<td align=\'center\'>%hh_descontada%</td>', $html3);
							if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
								$html3 = str_replace('%hh_descontada%', number_format($data['duracion_descontada'], Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html3);
							} else {
								$html3 = str_replace('%hh_descontada%', $data['glosa_duracion_descontada'], $html3);
							}
						} else {
							$html3 = str_replace('%td_descontada%', '', $html3);
							$html3 = str_replace('%hh_descontada%', '', $html3);
						}
					} else {
						$html3 = str_replace('%td_descontada%', '', $html3);
						$html3 = str_replace('%hh_trabajada%', '', $html3);
						$html3 = str_replace('%hh_descontada%', '', $html3);
					}
					if ($retainer || $flatfee) {
						$html3 = str_replace('%td_cobrable%', '<td align=\'center\'>%hh_cobrable%</td>', $html3);
						if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
							$html3 = str_replace('%hh_cobrable%', number_format($data['duracion_cobrada'], Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html3);
						} else {
							$html3 = str_replace('%hh_cobrable%', $data['glosa_duracion_cobrada'], $html3);
						}
						if ($retainer) {
							$html3 = str_replace('%td_retainer%', '<td align=\'center\'>%hh_retainer%</td>', $html3);
							if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
								$html3 = str_replace('%hh_retainer%', number_format($data['duracion_retainer'], Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html3);
							} else {
								$html3 = str_replace('%hh_retainer%', $data['glosa_duracion_retainer'], $html3);
							}
						} else {
							$html3 = str_replace('%td_retainer%', '', $html3);
							$html3 = str_replace('%hh_retainer%', '', $html3);
						}
					} else {
						$html3 = str_replace('%td_cobrable%', '', $html3);
						$html3 = str_replace('%td_retainer%', '', $html3);
						$html3 = str_replace('%hh_cobrable%', '', $html3);
						$html3 = str_replace('%hh_retainer%', '', $html3);
					}
					if ($this->fields['forma_cobro'] == 'ESCALONADA') {
						$html3 = str_replace('%hh_demo%', $data['glosa_duracion_cobrada'], $html3);
					} else if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
						$html3 = str_replace('%hh_demo%', number_format($data['duracion_tarificada'], Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html3);
					} else {
						$html3 = str_replace('%hh_demo%', $data['glosa_duracion_tarificada'], $html3);
					}
					if ($han_trabajado_menos_del_retainer) {
						$html3 = str_replace('%hh%', UtilesApp::Hora2HoraMinuto(0), $html3);
					} else {
						$html3 = str_replace('%hh%', $data['glosa_duracion_tarificada'], $html3);
					}

					if ($this->fields['opc_ver_profesional_categoria'] == 1) {
						$html3 = str_replace('%categoria%', __($data['glosa_categoria']), $html3);
					} else {
						$html3 = str_replace('%categoria%', '', $html3);
					}

					if ($this->fields['forma_cobro'] == 'ESCALONADA') {
						$html3 = str_replace('%tarifa_horas_demo%', number_format($data['monto_cobrado_escalonada'] / $data['duracion_cobrada'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
						$html3 = str_replace('%tarifa_horas%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . $this->espacio . number_format($data['monto_cobrado_escalonada'] / $data['duracion_cobrada'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
						$html3 = str_replace('%tarifa_horas_ajustada%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . $this->espacio . number_format($data['monto_cobrado_escalonada'] / $data['duracion_cobrada'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
					} else if ($this->fields['opc_ver_profesional_tarifa'] == 1) {
						$html3 = str_replace('%tarifa_horas_demo%', number_format($data['tarifa'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
						$html3 = str_replace('%tarifa_horas%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . $this->espacio . number_format($data['tarifa'] > 0 ? $data['tarifa'] : 0, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
						$html3 = str_replace('%tarifa_horas_ajustada%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . $this->espacio . number_format($data['tarifa'] * $x_factor_ajuste, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
					} else {
						$html3 = str_replace('%tarifa_horas_demo%', '', $html3);
						$html3 = str_replace('%tarifa_horas%', '', $html3);
						$html3 = str_replace('%tarifa_horas_ajustada%', '', $html3);
					}

					if ($this->fields['forma_cobro'] == 'ESCALONADA') {
						$html3 = str_replace('%total_horas_demo%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . $this->espacio . number_format($data['monto_cobrado_escalonada'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
						$html3 = str_replace('%total_horas%', number_format($data['monto_cobrado_escalonada'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
						$html3 = str_replace('%total_horas_ajustado%', number_format($data['monto_cobrado_escalonada'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
					} else if ($this->fields['opc_ver_profesional_importe'] == 1) {
						$html3 = str_replace('%total_horas_demo%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . $this->espacio . number_format($data['valor_tarificada'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
						$html3 = str_replace('%total_horas%', number_format($data['valor_tarificada'] > 0 ? $data['valor_tarificada'] : 0, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
						$html3 = str_replace('%total_horas_ajustado%', number_format($data['duracion_cobrada'] * $data['tarifa'] * $x_factor_ajuste, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
					} else {
						$html3 = str_replace('%total_horas_demo%', '', $html3);
						$html3 = str_replace('%total_horas%', '', $html3);
						$html3 = str_replace('%total_horas_ajustado%', '', $html3);
					}

					$resumen_filas[$prof] = $html3;
				}
				// Se escriben despu�s porque necesitan que los totales ya est�n calculados para calcular porcentajes.
				if (Conf::GetConf($this->sesion, 'ResumenProfesionalVial')) {
					$total_valor = 0;
					foreach ($x_resumen_profesional as $prof => $data) {
						$resumen_hrs_cobradas_temp = $resumen_hrs_cobradas > 0 ? $resumen_hrs_cobradas : 1;
						$resumen_filas[$prof] = str_replace('%porcentaje_participacion%', number_format($x_resumen_profesional[$prof]['duracion_cobrada'] / $resumen_hrs_cobradas_temp * 100, 2, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . '%', $resumen_filas[$prof]);

						if ($incobrables)
							$resumen_filas[$prof] = str_replace('%columna_horas_no_cobrables%', '<td align="center">' . $x_resumen_profesional[$prof]['glosa_duracion_incobrables'] . '</td>', $resumen_filas[$prof]);
						else
							$resumen_filas[$prof] = str_replace('%columna_horas_no_cobrables%', '', $resumen_filas[$prof]);
						if ($han_trabajado_menos_del_retainer && !$this->fields['opc_ver_detalle_retainer']) {
							$resumen_filas[$prof] = str_replace('%valor_retainer%', '', $resumen_filas[$prof]);
							$resumen_filas[$prof] = str_replace('%valor_retainer_vio%', '', $resumen_filas[$prof]);
						} else
							$resumen_filas[$prof] = str_replace('%valor_retainer%', $columna_hrs_retainer ? number_format($x_resumen_profesional[$prof]['duracion_cobrada'] / $resumen_hrs_cobradas_temp * $this->fields['monto_contrato'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) : '', $resumen_filas[$prof]);
						if ($this->fields['forma_cobro'] == 'FLAT FEE' || $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL')
							$resumen_filas[$prof] = str_replace('%valor_retainer_vio%', number_format($x_resumen_profesional[$prof]['duracion_cobrada'] / $resumen_hrs_cobradas_temp * $this->fields['monto_contrato'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $resumen_filas[$prof]);
						else
							$resumen_filas[$prof] = str_replace('%valor_retainer_vio%', '', $resumen_filas[$prof]);
						if ($han_trabajado_menos_del_retainer)
							$resumen_filas[$prof] = str_replace('%valor_cobrado_hh%', number_format(0, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $resumen_filas[$prof]);
						else {
							$resumen_filas[$prof] = str_replace('%valor_cobrado_hh%', number_format($x_resumen_profesional[$prof]['valor_tarificada'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $resumen_filas[$prof]);
							$total_valor += $x_resumen_profesional[$prof]['valor_tarificada'];
						}
					}
				}
				$resumen_filas = implode($resumen_filas);

				// Total
				if ($this->fields['forma_cobro'] == 'FLAT FEE' || $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') {
					$valor_cobrado_hh = $this->fields['monto'] - UtilesApp::CambiarMoneda($this->fields['monto_contrato'], $cobro_moneda->moneda[$this->fields['id_moneda_monto']]['tipo_cambio'], $cobro_moneda->moneda[$this->fields['id_moneda_monto']]['cifras_decimales'], $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales']);
				} else {
					$valor_cobrado_hh = $this->fields['monto'];
				}

				$html3 = $parser->tags['PROFESIONAL_TOTAL'];
				if (Conf::GetConf($this->sesion, 'ResumenProfesionalVial')) {
					if ($han_trabajado_menos_del_retainer)
						$html3 = str_replace('%valor_retainer%', number_format($this->fields['monto_contrato'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
					else
						$html3 = str_replace('%valor_retainer%', $columna_hrs_retainer ? number_format($this->fields['monto_contrato'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) : '', $html3);

					if ($han_trabajado_menos_del_retainer)
						$html3 = str_replace('%valor_cobrado_hh%', number_format(0, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
					else
						$html3 = str_replace('%valor_cobrado_hh%', number_format($valor_cobrado_hh, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
				}
				$html3 = str_replace('%glosa%', __('Total'), $html3);
				if ($han_trabajado_menos_del_retainer || $this->fields['forma_cobro'] == 'FLAT FEE' || $this->fields['opc_ver_horas_trabajadas']) {
					$html3 = str_replace('%hrs_trabajadas%', UtilesApp::Hora2HoraMinuto($resumen_hrs_cobradas), $html3);
				} else {
					$html3 = str_replace('%hrs_trabajadas%', ($columna_hrs_trabajadas ? UtilesApp::Hora2HoraMinuto($resumen_hrs_cobradas) : ''), $html3);
				}
				if ($han_trabajado_menos_del_retainer || $this->fields['forma_cobro'] == 'FLAT FEE' || $this->fields['opc_ver_horas_trabajadas']) {
					$html3 = str_replace('%hrs_trabajadas_vio%', UtilesApp::Hora2HoraMinuto($resumen_hrs_cobradas), $html3);
				} else {
					$html3 = str_replace('%hrs_trabajadas_vio%', ($columna_hrs_trabajadas ? UtilesApp::Hora2HoraMinuto($resumen_hrs_cobradas) : ''), $html3);
				}
				if ($han_trabajado_menos_del_retainer) {
					$html3 = str_replace('%hrs_retainer%', UtilesApp::Hora2HoraMinuto($resumen_hrs_cobradas_cob), $html3);
				} else {
					$html3 = str_replace('%hrs_retainer%', ($columna_hrs_retainer ? UtilesApp::Hora2HoraMinuto($this->fields['retainer_horas']) : ''), $html3);
				}
				$html3 = str_replace('%hrs_descontadas%', ($columna_hrs_descontadas ? UtilesApp::Hora2HoraMinuto($resumen_hrs_descontadas) : ''), $html3);
				if ($han_trabajado_menos_del_retainer)
					$html3 = str_replace('%hh%', UtilesApp::Hora2HoraMinuto(0), $html3);

				if ($this->fields['opc_ver_horas_trabajadas']) {
					if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
						$html3 = str_replace('%hh_trabajada%', number_format($resumen_hrs_trabajadas, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html3);
					} else {
						$html3 = str_replace('%hh_trabajada%', UtilesApp::Hora2HoraMinuto(round($resumen_hrs_trabajadas, 2)), $html3);
					}
					if ($descontado) {
						$html3 = str_replace('%td_descontada%', '<td align=\'center\'>%hh_descontada%</td>', $html3);
						if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
							$html3 = str_replace('%hh_descontada%', number_format($resumen_hrs_descontadas, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html3);
						} else {
							$html3 = str_replace('%hh_descontada%', Utiles::Decimal2GlosaHora(round($resumen_hrs_descontadas, 2)), $html3);
						}
					} else {
						$html3 = str_replace('%td_descontada%', '', $html3);
						$html3 = str_replace('%hh_descontada%', '', $html3);
					}
				} else {
					$html3 = str_replace('%td_descontada%', '', $html3);
					$html3 = str_replace('%hh_trabajada%', '', $html3);
					$html3 = str_replace('%hh_descontada%', '', $html3);
				}
				if ($retainer || $flatfee) {
					$html3 = str_replace('%td_cobrable%', '<td align=\'center\'>%hh_cobrable%</td>', $html3);
					if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
						$html3 = str_replace('%hh_cobrable%', number_format($resumen_hrs_cobradas, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html3);
					} else {
						$html3 = str_replace('%hh_cobrable%', UtilesApp::Hora2HoraMinuto(round($resumen_hrs_cobradas, 2)), $html3);
					}
					if ($retainer) {
						$html3 = str_replace('%td_retainer%', '<td align=\'center\'>%hh_retainer%</td>', $html3);
						if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
							$html3 = str_replace('%hh_retainer%', number_format($resumen_hrs_retainer, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html3);
						} else {
							$html3 = str_replace('%hh_retainer%', UtilesApp::Hora2HoraMinuto(round($resumen_hrs_retainer, 2)), $html3);
						}
					} else {
						$html3 = str_replace('%td_retainer%', '', $html3);
						$html3 = str_replace('%hh_retainer%', '', $html3);
					}
				} else {
					$html3 = str_replace('%td_cobrable%', '', $html3);
					$html3 = str_replace('%td_retainer%', '', $html3);
					$html3 = str_replace('%hh_cobrable%', '', $html3);
					$html3 = str_replace('%hh_retainer%', '', $html3);
				}
				if ($incobrables) {
					$html3 = str_replace('%columna_horas_no_cobrables%', '<td align="center">' . UtilesApp::Hora2HoraMinuto(round($resumen_hrs_incobrables, 2)) . '</td>', $html3);
				} else {
					$html3 = str_replace('%columna_horas_no_cobrables%', '', $html3);
				}
				if ($this->fields['forma_cobro'] == 'ESCALONADA') {
					$html3 = str_replace('%hh_demo%', UtilesApp::Hora2HoraMinuto(round($resumen_hrs_cobradas, 2)), $html3);
				} else if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
					$html3 = str_replace('%hh_demo%', number_format($resumen_hh, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html3);
				} else {
					$html3 = str_replace('%hh_demo%', UtilesApp::Hora2HoraMinuto(round($resumen_hh, 2)), $html3);
				}
				if (Conf::GetConf($this->sesion, 'ResumenProfesionalVial') && ( $this->fields['forma_cobro'] == 'PROPORCIONAL' || $this->fields['forma_cobro'] == 'RETAINER' ) && !$han_trabajado_menos_del_retainer) {
					$html3 = str_replace('%hh%', UtilesApp::Hora2HoraMinuto($resumen_hrs_cobradas - $resumen_hrs_incobrables - $this->fields['retainer_horas']), $html3);
				} else {
					$html3 = str_replace('%hh%', UtilesApp::Hora2HoraMinuto(round($resumen_hh, 2)), $html3);
				}
				if ($this->fields['opc_ver_profesional_importe'] == 1) {
					$html3 = str_replace('%td_importe%', '<td align="right">%total_horas_demo%</td>', $html3);
					$html3 = str_replace('%td_importe_ajustado%', '<td align="right">%total_horas_ajustado%</td>', $html3);
					$html3 = str_replace('%total_horas_demo%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . $this->espacio . number_format($resumen_valor, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
					$html3 = str_replace('%total_horas_ajustado%', number_format($this->fields['monto_subtotal'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
				} else {
					$html3 = str_replace('%td_importe%', '', $html3);
					$html3 = str_replace('%td_importe_ajustado%', '', $html3);
					$html3 = str_replace('%total_horas_demo%', '', $html3);
					$html3 = str_replace('%total_horas_ajustado%', '', $html3);
				}

				if ($this->fields['opc_ver_profesional_tarifa'] == 1) {
					$html3 = str_replace('%td_tarifa%', '<td>&nbsp;</td>', $html3);
					$html3 = str_replace('%td_tarifa_ajustada%', '<td>&nbsp;</td>', $html3);
				} else {
					$html3 = str_replace('%td_tarifa%', '', $html3);
					$html3 = str_replace('%td_tarifa_ajustada%', '', $html3);
				}

				$html3 = str_replace('%total%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto_trabajos'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);

				$resumen_fila_total = $html3;
				$html = str_replace('%glosa_profesional%', __('Resumen detalle profesional'), $html);

				if ($lang == 'es') {
					$html = str_replace('%resumen_profesional%', __('Resumen Detalle Profesional'), $html);
				} else {
					$html = str_replace('%resumen_profesional%', __('TIMEKEEPER SUMMARY'), $html);
				}

				$html = str_replace('%glosa_profesional%', __('Resumen detalle profesional'), $html);
				$html = str_replace('%RESUMEN_PROFESIONAL_ENCABEZADO%', $resumen_encabezado, $html);
				$html = str_replace('%RESUMEN_PROFESIONAL_FILAS%', $resumen_filas, $html);
				$html = str_replace('%RESUMEN_PROFESIONAL_TOTAL%', $resumen_fila_total, $html);

				$html = str_replace('%seccion_resumen_profesional%', __('resumen_raz'), $html);
				break;

			case 'RESUMEN_PROFESIONAL_POR_CATEGORIA': //GenerarDocumento2

				if ($this->fields['opc_ver_profesional'] == 0) {
					return '';
				}

				global $columna_hrs_trabajadas;
				global $columna_hrs_retainer;
				global $columna_hrs_descontadas;
				global $x_resumen_profesional;

				$columna_hrs_incobrables = false;

				$array_categorias = array();
				foreach ($x_resumen_profesional as $id => $data) {
					array_push($array_categorias, $data['id_categoria_usuario']);
					if ($data['duracion_incobrables'] > 0)
						$columna_hrs_incobrables = true;
				}

				// Array que guardar los ids de usuarios para recorrer
				if (sizeof($array_categorias) > 0)
					array_multisort($array_categorias, SORT_ASC, $x_resumen_profesional);

				$array_profesionales = array();
				foreach ($x_resumen_profesional as $id_usuario => $data) {
					array_push($array_profesionales, $id_usuario);
				}

				// Encabezado
				$resumen_encabezado = $this->GenerarSeccionResumenProfesional($parser, 'RESUMEN_PROFESIONAL_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto);
				$html = str_replace('%RESUMEN_PROFESIONAL_ENCABEZADO%', $resumen_encabezado, $html);
				$html = str_replace('%glosa_profesional%', __('Resumen detalle profesional'), $html);

				// Partimos los subtotales de la primera categor�a con los datos del primer profesional.
				$resumen_hrs_trabajadas = $x_resumen_profesional[$array_profesionales[0]]['duracion_trabajada'];
				$resumen_hrs_cobradas = $x_resumen_profesional[$array_profesionales[0]]['duracion_cobrada'];
				$resumen_hrs_retainer = $x_resumen_profesional[$array_profesionales[0]]['duracion_retainer'];
				$resumen_hrs_descontadas = $x_resumen_profesional[$array_profesionales[0]]['duracion_descontada'];
				$resumen_hrs_incobrables = $x_resumen_profesional[$array_profesionales[0]]['duracion_incobrables'];
				$resumen_hh = $x_resumen_profesional[$array_profesionales[0]]['duracion_tarificada'];
				$resumen_total = $x_resumen_profesional[$array_profesionales[0]]['valor_tarificada'];
				// Partimos los totales con 0
				$resumen_total_hrs_trabajadas = 0;
				$resumen_total_hrs_cobradas = 0;
				$resumen_total_hrs_retainer = 0;
				$resumen_total_hrs_descontadas = 0;
				$resumen_total_hrs_incobrables = 0;
				$resumen_total_hh = 0;
				$resumen_total_total = 0;

				for ($k = 1; $k < count($array_profesionales); ++$k) {

					// El profesional actual es de la misma categor�a que el anterior, solo aumentamos los subtotales de la categor�a.
					if ($x_resumen_profesional[$array_profesionales[$k]]['id_categoria_usuario'] == $x_resumen_profesional[$array_profesionales[$k - 1]]['id_categoria_usuario']) {
						$resumen_hrs_trabajadas += $x_resumen_profesional[$array_profesionales[$k]]['duracion_trabajada'];
						$resumen_hrs_cobradas += $x_resumen_profesional[$array_profesionales[$k]]['duracion_cobrada'];
						$resumen_hrs_retainer += $x_resumen_profesional[$array_profesionales[$k]]['duracion_retainer'];
						$resumen_hrs_descontadas += $x_resumen_profesional[$array_profesionales[$k]]['duracion_descontada'];
						$resumen_hrs_incobrables += $x_resumen_profesional[$array_profesionales[$k]]['duracion_incobrables'];
						$resumen_hh += $x_resumen_profesional[$array_profesionales[$k]]['duracion_tarificada'];
						$resumen_total += $x_resumen_profesional[$array_profesionales[$k]]['valor_tarificada'];
					} else {
						// El profesional actual es de distinta categor�a que el anterior, imprimimos los subtotales de la categor�a anterior y ponemos en cero los de la actual.
						$html3 = $parser->tags['PROFESIONAL_FILAS'];
						$html3 = str_replace('%nombre%', $x_resumen_profesional[$array_profesionales[$k - 1]]['glosa_categoria'], $html3);
						$html3 = str_replace('%iniciales%', $x_resumen_profesional[$array_profesionales[$k - 1]]['glosa_categoria'], $html3);

						$html3 = str_replace('%hrs_trabajadas%', ($columna_hrs_trabajadas ? UtilesApp::Hora2HoraMinuto($resumen_hrs_cobradas) : ''), $html3);
						$html3 = str_replace('%hrs_retainer%', ($columna_hrs_retainer ? UtilesApp::Hora2HoraMinuto($resumen_hrs_retainer) : ''), $html3);
						$html3 = str_replace('%hrs_descontadas%', ($columna_hrs_incobrables ? UtilesApp::Hora2HoraMinuto($resumen_hrs_incobrables) : ''), $html3);
						$html3 = str_replace('%hh%', UtilesApp::Hora2HoraMinuto($resumen_hh), $html3);

						$html3 = str_replace('%total_horas%', $moneda->fields['simbolo'] . $this->espacio . number_format($resumen_total, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);

						// Se asume que dentro de la misma categor�a todos tienen la misma tarifa.
						$html3 = str_replace('%tarifa_horas%', number_format($x_resumen_profesional[$array_profesionales[$k - 1]]['tarifa'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);

						// Para imprimir la siguiente categor�a de usuarios
						$siguiente = " \n%RESUMEN_PROFESIONAL_FILAS%\n";
						$html = str_replace('%RESUMEN_PROFESIONAL_FILAS%', $html3 . $siguiente, $html);

						// Aumentamos los totales
						$resumen_total_hrs_trabajadas += $resumen_hrs_trabajadas;
						$resumen_total_hrs_cobradas += $resumen_hrs_cobradas;
						$resumen_total_hrs_retainer += $resumen_hrs_retainer;
						$resumen_total_hrs_descontadas += $resumen_hrs_descontadas;
						$resumen_total_hrs_incobrables += $resumen_hrs_incobrables;
						$resumen_total_hh += $resumen_hh;
						$resumen_total_total += $resumen_total;
						// Resetear subtotales
						$resumen_hrs_trabajadas = $x_resumen_profesional[$array_profesionales[$k]]['duracion_trabajada'];
						$resumen_hrs_cobradas = $x_resumen_profesional[$array_profesionales[$k]]['duracion_cobrada'];
						$resumen_hrs_retainer = $x_resumen_profesional[$array_profesionales[$k]]['duracion_retainer'];
						$resumen_hrs_descontadas = $x_resumen_profesional[$array_profesionales[$k]]['duracion_descontada'];
						$resumen_hrs_incobrables = $x_resumen_profesional[$array_profesionales[$k]]['duracion_incobrables'];
						$resumen_hh = $x_resumen_profesional[$array_profesionales[$k]]['duracion_tarificada'];
						$resumen_total = $x_resumen_profesional[$array_profesionales[$k]]['valor_tarificada'];
					}
				}

				// Imprimir la �ltima categor�a
				$html3 = $parser->tags['PROFESIONAL_FILAS'];
				$html3 = str_replace('%nombre%', $x_resumen_profesional[$array_profesionales[$k - 1]]['glosa_categoria'], $html3);
				$html3 = str_replace('%iniciales%', $x_resumen_profesional[$array_profesionales[$k - 1]]['glosa_categoria'], $html3);
				$html3 = str_replace('%hrs_trabajadas%', ($columna_hrs_trabajadas ? UtilesApp::Hora2HoraMinuto($resumen_hrs_cobradas) : ''), $html3);
				$html3 = str_replace('%hrs_retainer%', ($columna_hrs_retainer ? UtilesApp::Hora2HoraMinuto($resumen_hrs_retainer) : ''), $html3);
				$html3 = str_replace('%hrs_descontadas%', ($columna_hrs_incobrables ? UtilesApp::Hora2HoraMinuto($resumen_hrs_incobrables) : ''), $html3);
				$html3 = str_replace('%hh%', UtilesApp::Hora2HoraMinuto($resumen_hh), $html3);
				// Se asume que dentro de la misma categor�a todos tienen la misma tarifa.
				$html3 = str_replace('%tarifa_horas%', number_format($x_resumen_profesional[$array_profesionales[$k - 1]]['tarifa'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
				$html3 = str_replace('%total_horas%', $moneda->fields['simbolo'] . $this->espacio . number_format($resumen_total, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);

				$html = str_replace('%RESUMEN_PROFESIONAL_FILAS%', $html3, $html);

				//cargamos el dato del total del monto en moneda tarifa (dato se calculo en detalle cobro) para mostrar en resumen segun conf
				global $monto_cobro_menos_monto_contrato_moneda_tarifa;

				// Aumentamos los totales
				$resumen_total_hrs_trabajadas += $resumen_hrs_trabajadas;
				$resumen_total_hrs_cobradas += $resumen_hrs_cobradas;
				$resumen_total_hrs_retainer += $resumen_hrs_retainer;
				$resumen_total_hrs_descontadas += $resumen_hrs_descontadas;
				$resumen_total_hrs_incobrables += $resumen_hrs_incobrables;
				$resumen_total_hh += $resumen_hh;
				$resumen_total_total += $resumen_total;

				//se muestra el mismo valor que sale en el detalle de cobro
				if (Conf::GetConf($this->sesion, 'ResumenProfesionalVial')) {
					$resumen_total_total = $monto_cobro_menos_monto_contrato_moneda_tarifa;
				}

				// Imprimir el total
				$html3 = $parser->tags['RESUMEN_PROFESIONAL_TOTAL'];
				$html3 = str_replace('%glosa%', __('Total'), $html3);

				$html3 = str_replace('%hrs_trabajadas%', ($columna_hrs_trabajadas ? UtilesApp::Hora2HoraMinuto($resumen_total_hrs_cobradas) : ''), $html3);
				$html3 = str_replace('%hrs_retainer%', ($columna_hrs_retainer ? UtilesApp::Hora2HoraMinuto($resumen_total_hrs_retainer) : ''), $html3);
				$html3 = str_replace('%hrs_descontadas%', ($columna_hrs_incobrables ? UtilesApp::Hora2HoraMinuto($resumen_total_hrs_incobrables) : ''), $html3);
				$html3 = str_replace('%hh%', UtilesApp::Hora2HoraMinuto($resumen_total_hh), $html3);
				$html3 = str_replace('%total%', $moneda->fields['simbolo'] . $this->espacio . number_format($resumen_total_total, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
				$html = str_replace('%RESUMEN_PROFESIONAL_TOTAL%', $html3, $html);
				break;

			case 'RESUMEN_PROFESIONAL_ENCABEZADO': //GenerarDocumentoComun
				$html = str_replace('%nombre%', __('Categor�a profesional'), $html);
				global $columna_hrs_trabajadas_categoria;
				global $columna_hrs_retainer_categoria;
				global $columna_hrs_flatfee_categoria;
				global $columna_hrs_descontadas_categoria;
				global $columna_hrs_incobrables_categoria;

				if ($columna_hrs_retainer_categoria) {
					$html = str_replace('%hrs_retainer%', __('Hrs. Retainer'), $html);
					$html = str_replace('%hrs_mins_retainer%', __('Hrs.:Mins. Retainer'), $html);
				}

				$html = str_replace('%hrs_retainer%', $columna_hrs_flatfee_categoria ? __('Hrs. Flat Fee') : '', $html);
				$html = str_replace('%hrs_trabajadas%', $columna_hrs_trabajadas_categoria ? __('Hrs. Trabajadas') : '', $html);
				$html = str_replace('%hrs_descontadas%', $columna_hrs_incobrables_categoria ? __('Hrs. Descontadas') : '', $html);
				$html = str_replace('%hrs_mins_retainer%', $columna_hrs_flatfee_categoria ? __('Hrs.:Mins. Flat Fee') : '', $html);
				$html = str_replace('%hrs_mins_trabajadas%', $columna_hrs_trabajadas_categoria ? __('Hrs.:Mins. Trabajadas') : '', $html);
				$html = str_replace('%hrs_mins_descontadas%', $columna_hrs_descontadas_categoria ? __('Hrs.:Mins. Descontadas') : '', $html);
				// El resto se llena igual que PROFESIONAL_ENCABEZADO, pero tiene otra estructura, no debe tener 'break;'.

			case 'PROFESIONAL_ENCABEZADO': //GenerarDocumentoComun
				global $columna_hrs_trabajadas;
				global $columna_hrs_retainer;
				global $columna_hrs_descontadas;
				global $columna_hrs_trabajadas_categoria;
				global $columna_hrs_retainer_categoria;
				global $columna_hrs_flatfee_categoria;
				global $columna_hrs_descontadas_categoria;


				if ($this->fields['forma_cobro'] == 'FLAT FEE' || $this->fields['opc_ver_horas_trabajadas']) {
					$html = str_replace('%horas_trabajadas%', __('Hrs Trabajadas'), $html);
				}

				if (Conf::GetConf($this->sesion, 'ResumenProfesionalVial')) {
					$mostrar_columnas_retainer = $columna_hrs_retainer || $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL';


					if ($mostrar_columnas_retainer) {
						$html = str_replace('%horas_trabajadas%', __('Hrs Trabajadas'), $html);
						$html = str_replace('%retainer%', __('RETAINER'), $html);
						$html = str_replace('%extraordinario%', __('EXTRAORDINARIO'), $html);
						$html = str_replace('%simbolo_moneda_2%', ' (' . $moneda->fields['simbolo'] . ')', $html);
					} else {
						$html = str_replace('%horas_trabajadas%', '', $html);
						$html = str_replace('%retainer%', '', $html);
						$html = str_replace('%extraordinario%', '', $html);
						$html = str_replace('%simbolo_moneda_2%', '', $html);
					}

					$html = str_replace('%nombre%', __('ABOGADO'), $html);

					if ($this->fields['opc_ver_profesional_tarifa'] == 1) {
						$html = str_replace('%valor_hh%', __('TARIFA'), $html);
					} else {
						$html = str_replace('%valor_hh%', '', $html);
					}

					$html = str_replace('%hrs_trabajadas%', ($mostrar_columnas_retainer || $columna_hrs_trabajadas) ? __('HRS TOT TRABAJADAS') : '', $html);
					$html = str_replace('%porcentaje_participacion%', __('PARTICIPACI�N POR ABOGADO'), $html);
					$html = str_replace('%hrs_retainer%', $mostrar_columnas_retainer ? __('HRS TRABAJADAS VALOR RETAINER') : '', $html);
					$html = str_replace('%valor_retainer%', $mostrar_columnas_retainer ? __('COBRO') . __(' HRS VALOR RETAINER') : '', $html);
					$html = str_replace('%hh%', __('HRS TRABAJADAS VALOR TARIFA'), $html);
					$html = str_replace('%valor_cobrado_hh%', __('COBRO') . __(' HRS VALOR TARIFA'), $html);
				} else {
					$html = str_replace('%horas_trabajadas%', '', $html);
				}

				//recorriendo los datos para los titulos
				$retainer = false;
				$descontado = false;
				$flatfee = false;

				if (is_array($x_resumen_profesional)) {
					foreach ($x_resumen_profesional as $index => $data) {
						if ($data['duracion_retainer'] > 0 && ($this->fields['forma_cobro'] != 'FLAT FEE' || Conf::GetConf($this->sesion, 'ResumenProfesionalVial'))) {
							$retainer = true;
						}
						if (($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') && Conf::GetConf($this->sesion, 'ResumenProfesionalVial')) {
							$retainer = true;
						}
						if ($data['duracion_incobrables'] > 0)
							$descontado = true;
						if ($data['flatfee'] > 0)
							$flatfee = true;
					}
				}

				$html = str_replace('%nombre%', __('Nombre'), $html);
				$html = str_replace('%abogado%', __('abogado_raz'), $html);
				$html = str_replace('%tiempo_raz%', __('tiempo_raz'), $html);
				$html = str_replace('%tarifa_raz%', __('tarifa_raz'), $html);
				$html = str_replace('%importe_raz%', __('importe_raz'), $html);

				if ($this->fields['opc_ver_horas_trabajadas']) {
					$html = str_replace('%fayca_hrs_descontadas%', '<td align="center">' . __('Hrs. Descontadas') . '</td>', $html);
					$html = str_Replace('%td_hrs_mins_descontadas_real%', '<td align="center">' . __('Hrs. Descontadas') . '</td>', $html);
				} else {
					$html = str_replace('%fayca_hrs_descontadas%', '', $html);
					$html = str_Replace('%td_hrs_mins_descontadas_real%', '', $html);
				}

				if ($descontado || $retainer || $flatfee) {
					$html = str_replace('%hrs_trabajadas%', __('Hrs. Trabajadas'), $html);
					$html = str_replace('%hrs_mins_trabajadas%', __('Hrs.:Mins. Trabajadas'), $html);
					$columna_hrs_trabajadas = true;
					$columna_hrs_trabajadas_categoria = true;
					if ($this->fields['opc_ver_horas_trabajadas']) {
						$html = str_replace('%hrs_trabajadas_real%', __('Hrs. Trabajadas'), $html);
						$html = str_Replace('%hrs_descontadas_real%', __('Hrs. Descontadas'), $html);
						$html = str_replace('%hrs_mins_trabajadas_real%', __('Hrs.:Mins. Trabajadas'), $html);
						$html = str_Replace('%hrs_mins_descontadas_real%', __('Hrs.:Mins. Descontadas'), $html);
					} else {
						$html = str_replace('%hrs_trabajadas_real%', '', $html);
						$html = str_Replace('%hrs_descontadas_real%', '', $html);
						$html = str_replace('%hrs_mins_trabajadas_real%', '', $html);
						$html = str_Replace('%hrs_mins_descontadas_real%', '', $html);
					}
				} else if ($this->fields['opc_ver_horas_trabajadas']) {
					$html = str_replace('%hrs_trabajadas_real%', __('Hrs. Trabajadas'), $html);
					$html = str_replace('%hrs_trabajadas%', __('Hrs. Trabajadas'), $html);
					$html = str_replace('%hrs_descontadas_real%', __('Hrs. Descontadas'), $html);
					$html = str_replace('%horas_cobrables%', '', $html);
					$html = str_replace('%hrs_mins_trabajadas_real%', __('Hrs.:Mins. Trabajadas'), $html);
					$html = str_replace('%hrs_mins_descontadas_real%', __('Hrs.:Mins. Descontadas'), $html);
					$html = str_replace('%horas_mins_cobrables%', '', $html);
				} else {
					$html = str_replace('%hrs_trabajadas%', '', $html);
					$html = str_replace('%hrs_trabajadas_real%', '', $html);
					$html = str_replace('%hrs_mins_trabajadas%', '', $html);
					$html = str_replace('%hrs_mins_trabajadas_real%', '', $html);
				}

				if ($retainer) {
					$html = str_replace('%hrs_retainer%', __('Hrs. Retainer'), $html);
					$html = str_replace('%hrs_mins_retainer%', __('Hrs.:Mins. Retainer'), $html);
					$columna_hrs_retainer = true;
					$columna_hrs_retainer_categoria = true;
				} elseif ($flatfee) {
					$html = str_replace('%hrs_retainer%', __('Hrs. Flat Fee'), $html);
					$html = str_replace('%hrs_mins_retainer%', __('Hrs.:Mins. Flat Fee'), $html);
					$columna_hrs_retainer = true;
					$columna_hrs_flatfee_categoria = true;
				} else {
					$html = str_replace('%hrs_retainer%', '', $html);
					$html = str_replace('%hrs_mins_retainer%', '', $html);
				}

				if ($descontado) {
					$html = str_replace('%columna_horas_no_cobrables_top%', '<td align="center">&nbsp;</td>', $html);
					$html = str_replace('%columna_horas_no_cobrables%', '<td align="center">' . __('HRS NO<br>COBRABLES') . '</td>', $html);
					$html = str_replace('%hrs_descontadas%', __('Hrs. Descontadas'), $html);
					$html = str_replace('%hrs_descontadas_real%', __('Hrs. Descontadas'), $html);
					$html = str_replace('%hrs_mins_descontadas%', __('Hrs.:Mins. Descontadas'), $html);
					$html = str_replace('%hrs_mins_descontadas_real%', __('Hrs.:Mins. Descontadas'), $html);
					$columna_hrs_descontadas = true;
					$columna_hrs_descontadas_categoria = true;
				} else {
					$html = str_replace('%columna_horas_no_cobrables_top%', '', $html);
					$html = str_replace('%columna_horas_no_cobrables%', '', $html);
					$html = str_replace('%hrs_descontadas_real%', '', $html);
					$html = str_replace('%hrs_descontadas%', '', $html);
					$html = str_replace('%hrs_mins_descontadas_real%', '', $html);
					$html = str_replace('%hrs_mins_descontadas%', '', $html);
				}

				$html = str_replace('%horas_cobrables%', __('Hrs. Cobrables'), $html);
				$html = str_replace('%horas_mins_cobrables%', __('Hrs.:Mins. Cobrables'), $html);
				$html = str_replace('%hrs_trabajadas_previo%', '', $html);
				$html = str_replace('%hrs_mins_trabajadas_previo%', '', $html);
				$html = str_replace('%abogados%', __('Abogados que trabajaron'), $html);

				if ($this->fields['opc_ver_horas_trabajadas']) {
					$html = str_replace('%hh_trabajada%', __($this->fields['codigo_idioma'] . '_Hrs Trabajadas'), $html);
					$html = str_replace('%td_descontada%', '<td align=\'center\' width=\'80\'>' . __('Hrs. Castigadas') . '</td>', $html);
					if ($retainer || $flatfee) {
						$html = str_replace('%hh_cobrable%', __('Hrs Cobradas'), $html);
					} else {
						$html = str_replace('%hh_cobrable%', '', $html);
					} if ($descontado) {
						$html = str_replace('%td_descontada%', '<td align=\'center\' width=\'80\'>%hh_descontada%</td>', $html);
						$html = str_replace('%hh_descontada%', __('Hrs.:Mins. Descontadas'), $html);
					} else {
						$html = str_replace('%td_descontada%', '', $html);
						$html = str_replace('%hh_descontada%', '', $html);
					}
				} else {
					$html = str_replace('%td_descontada%', '', $html);
					$html = str_replace('%hh_trabajada%', '', $html);
					$html = str_replace('%hh_descontada%', '', $html);
				}

				if ($retainer || $flatfee) {
					$html = str_replace('%td_cobrable%', '<td align=\'center\' width=\'80\'>%hh_cobrable%</td>', $html);
					$html = str_replace('%hh_cobrable%', __('Hrs. Trabajadas'), $html);
					if ($retainer) {
						$html = str_replace('%td_retainer%', '<td align=\'center\' width=\'80\'>%hh_retainer%</td>', $html);
						$html = str_replace('%hh_retainer%', __('Hrs. Retainer'), $html);
					} else {
						$html = str_replace('%td_retainer%', '', $html);
						$html = str_replace('%hh_retainer%', '', $html);
					}
				} else {
					$html = str_replace('%td_cobrable%', '', $html);
					$html = str_replace('%td_retainer%', '', $html);
					$html = str_replace('%hh_cobrable%', '', $html);
					$html = str_replace('%hh_retainer%', '', $html);
				}

				$html = str_replace('%hh%', __('Hrs. Tarificadas'), $html);
				$html = str_replace('%tiempo%', __('Tiempo'), $html);
				$html = str_replace('%hh_mins%', __('Hrs.:Mins. Tarificadas'), $html);
				$html = str_replace('%horas%', $retainer ? __('Hrs. Tarificadas') : __('Horas'), $html);
				$html = str_replace('%horas_retainer%', $retainer ? __('Hrs. Retainer') : '', $html);
				$html = str_replace('%horas_mins%', $retainer ? __('Hrs.:Mins. Tarificadas') : __('Horas'), $html);
				$html = str_replace('%horas_mins_retainer%', $retainer ? __('Hrs.:Mins. Retainer') : '', $html);

				if ($this->fields['opc_ver_profesional_tarifa'] == 1) {
					$html = str_replace('%td_tarifa%', '<td align="center" width="60">%valor_hh%</td>', $html);
					$html = str_replace('%td_tarifa_min%', '<td align="center" width="60">'.__('Tarifa').'</td>', $html);
					$html = str_replace('%td_tarifa_simbolo%', '<td align="center" width="60">%valor_hh_simbolo%</td>', $html);
					$html = str_replace('%valor_horas%', $flatfee ? '' : __('Tarifa'), $html);
					$html = str_replace('%valor_hh%', __('TARIFA'), $html);
					$html = str_replace('%valor_hh_simbolo%', __('TARIFA') . ' (' .$moneda->fields['simbolo'] . ')', $html);
					$html = str_replace('%td_tarifa_ajustada%', '<td align="center" width="60">' . __('TARIFA') . '</td>', $html);
				} else {
					$html = str_replace('%td_tarifa%', '', $html);
					$html = str_replace('%td_tarifa_simbolo%', '', $html);
					$html = str_replace('%valor_horas%', '', $html);
					$html = str_replace('%valor_hh%', '', $html);
					$html = str_replace('%td_tarifa_min%', '', $html);
					$html = str_replace('%valor_hh_simbolo%', '', $html);
					$html = str_replace('%td_tarifa_ajustada%', '', $html);
				}

				$html = str_replace('%tarifa_fee%', __('%tarifa_fee%'), $html);
				$html = str_replace('%simbolo_moneda%', $flatfee ? '' : ' (' . $moneda->fields['simbolo'] . ')', $html);

				if ($this->fields['opc_ver_profesional_importe'] == 1) {
					$html = str_replace('%td_importe%', '<td align="right" width="70">%importe%</td>', $html);
					$html = str_replace('%td_importe_ajustado%', '<td align="right" width="70">%importe_ajustado%</td>', $html);
					$html = str_replace('%importe%', __($this->fields['codigo_idioma'] . '_IMPORTE'), $html);
					$html = str_replace('%importe_ajustado%', __($this->fields['codigo_idioma'] . '_IMPORTE'), $html);
				} else {
					$html = str_replace('%td_importe%', '', $html);
					$html = str_replace('%td_importe_ajustado%', '', $html);
					$html = str_replace('%importe%', '', $html);
					$html = str_replace('%importe_ajustado%', '', $html);
				}

				$html = str_replace('%total%', __('Total'), $html);
				$html = str_replace('%honorarios%', __('Honorarios'), $html);

				if ($this->fields['opc_ver_profesional_categoria'] == 1){
					$html = str_replace('%categoria%', __($this->fields['codigo_idioma'] . '_CATEGOR�A'), $html);
					$html = str_replace('%categoria_min%', __('Categor�a'), $html);
				} else {
					$html = str_replace('%categoria%', '', $html);
					$html = str_replace('%categoria_min%', '', $html);
				}

								$html = str_replace('%staff%', __('Staff'), $html);
				$html = str_replace('%valor_siempre%', __('Valor'), $html);
				$html = str_replace('%nombre_profesional%', __('Nombre Profesional'), $html);

				if ($lang == 'es') {
					$html = str_replace('%profesional%', __('Profesional'), $html);
					$html = str_replace('%hora_tarificada%', __('Trarifa<br>Hora'), $html);
				} else {
					$html = str_replace('%profesional%', __('Biller'), $html);
					$html = str_replace('%hora_tarificada%', __('Hourly<br>Rate'), $html);
				}
				break;
		}
		return $html;
	}

	public function ObtenerDetalleModalidad($campos, $moneda, $idioma) {
		$detalle_modalidad = $campos['forma_cobro'] == 'TASA' ? '' : __('POR') . ' ' . $moneda['simbolo'] . $this->espacio . number_format($campos['monto_contrato'], $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
		if (($campos['forma_cobro'] == 'RETAINER' || $campos['forma_cobro'] == 'PROPORCIONAL') and $campos['retainer_horas'] != '') {
			$detalle_modalidad .= '<br>' . sprintf(__('Hasta') . ' %s ' . __('Horas'), $campos['retainer_horas']);
		}

		return $detalle_modalidad;
	}

	public function GeneraCobrosMasivos($cobros, $imprimir_cartas, $agrupar_cartas, $id_formato = null, $mostrar_asuntos_cobrables_sin_horas = FALSE) {
		global $_LANG;
		$carta_multiple = null;

		set_time_limit(300);

		$NotaCobro = new NotaCobro($this->sesion);

		$orientacion_papel = Conf::GetConf($this->sesion, 'OrientacionPapelPorDefecto');

		if (empty($orientacion_papel) || !in_array($orientacion_papel, array('PORTRAIT', 'LANDSCAPE'))) {
			$orientacion_papel = 'PORTRAIT';
		}

		if ($agrupar_cartas) {
			$Carta = new Carta($this->sesion);

			// Se asigna el identificador del la carta m�ltiple
			if ($Carta->LoadByDescripcion('MULTIPLE')) {
				$carta_multiple = $Carta->fields['id_carta'];
			}

			$totales_cobros = array();
			$primer_cliente = '';

			foreach ($cobros as $cobro) {
				if (!is_numeric($cobro)) {
					$cobro = $cobro['id_cobro'];
				}

				if (!$NotaCobro->Load($cobro)) {
					continue;
				}

				$NotaCobro->LoadAsuntos();
				$NotaCobro->ParametrosGeneracion();

				$totales_cobros[$NotaCobro->fields['codigo_cliente']][$cobro]['totales'] = $NotaCobro->x_resultados;
				$totales_cobros[$NotaCobro->fields['codigo_cliente']][$cobro]['campos'] = $NotaCobro->fields;
				$totales_cobros[$NotaCobro->fields['codigo_cliente']][$cobro]['asuntos'] = $NotaCobro->asuntos;
			}
		}

		foreach ($cobros as $cobro) {
			if (!is_numeric($cobro)) {
				$cobro = $cobro['id_cobro'];
			}

			if (!$NotaCobro->Load($cobro)) {
				continue;
			}

			if ($imprimir_cartas) {
				if (!$NotaCobro->fields['id_carta']) {
					$NotaCobro->fields['id_carta'] = 1;
					$NotaCobro->fields['opc_ver_carta'] = 1;
				}
			} else {
				$NotaCobro->fields['id_carta'] = null;
			}

			if ($agrupar_cartas) {
				$codigo_cliente = $NotaCobro->fields['codigo_cliente'];

				if ($codigo_cliente != $primer_cliente) {
					$primer_cliente = $codigo_cliente;

					// solo si existe una carta MULTIPLE se sobre escribe el identificador de la carta
					if (!is_null($carta_multiple)) {
						$NotaCobro->fields['id_carta'] = $carta_multiple;
					}

					$NotaCobro->fields['opc_ver_carta'] = 1;
					$NotaCobro->DetalleLiquidaciones = $totales_cobros[$codigo_cliente];
				}
			}

			$lang = $NotaCobro->fields['codigo_idioma'];

			// Limpia $_LANG para que no se choquen los LANGS entre liquidaciones
			$_LANG = array_merge($_LANG, UtilesApp::LoadLang($lang, true));

			if (empty($id_formato)) {
				$id_formato = $NotaCobro->fields['id_formato'];
			}

			$NotaCobro->LoadAsuntos();
			$html = $NotaCobro->GeneraHTMLCobro(true, $id_formato, NULL, $mostrar_asuntos_cobrables_sin_horas);

			if (empty($html)) {
				continue;
			}

			$cssData = UtilesApp::TemplateCartaCSS($this->sesion, $NotaCobro->fields['id_carta']);
			list($docm_top, $docm_right, $docm_bottom, $docm_left, $docm_header, $docm_footer) = UtilesApp::ObtenerMargenesCarta($this->sesion, $NotaCobro->fields['id_carta']);

			if ($html) {
				$cssData .= UtilesApp::CSSCobro($this->sesion);

				if (is_object($doc)) {
					$doc->newSession($html);
				} else {
					$doc = new DocGenerator(
						$html,
						$cssData,
						$NotaCobro->fields['opc_papel'],
						$NotaCobro->fields['opc_ver_numpag'],
						$orientacion_papel,
						$docm_top,
						$docm_right,
						$docm_bottom,
						$docm_left,
						$NotaCobro->fields['estado'],
						$id_formato,
						'',
						$docm_header,
						$docm_footer,
						$lang,
						$this->sesion
					);
				}

				$doc->chunkedOutput("cobro_masivo.doc");
			}
		}

		$doc->endChunkedOutput("cobro_masivo.doc");
	}

	/**
	 * Realiza el render del template HTML con Twig y los datos disponibles
	 * para reemplazar en $template
	 *
	 * @param array Valores posibles para reemplazar en el template HTML
	 * @return string
	 */
	protected function RenderTemplate($template) {

		if (!$this->twig) {
			$loader = new Twig_Loader_String();
			$this->twig = new Twig_Environment($loader);
			$this->twig->setCharset('ISO-8859-1');
			$this->twig->addExtension(new DateTwigExtension());
		}
		return $this->twig->render($template, $this->template_data);
	}

	/**
	 * Llena los datos a utilizar en el RenderTemplate, se llama desde
	 * GenerarDocumento* y GenerarCarta
	 *
	 * @param array Fields del idioma
	 * @param array Fields de la moneda
	 */
	protected function FillTemplateData($idioma, $moneda) {
		$Contrato = new Contrato($this->sesion);
		$Contrato->Load($this->fields['id_contrato']);
		$this->template_data['Contrato'] = $Contrato->fields;

		$this->template_data['Cobro'] = $this->fields;
		$this->template_data['UsuarioActual'] = $this->sesion->usuario->fields;
		$this->template_data['Idioma'] = $idioma->fields;
		$this->template_data['Moneda'] = $moneda->fields;

		$CobroPendiente = new CobroPendiente($this->sesion);
		$this->template_data['CobroPendiente'] = $CobroPendiente->LoadFirstByIdCobro($this->template_data['Cobro']['id_cobro']);
	}
}
