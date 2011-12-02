<?php

require_once dirname(__FILE__) . '/../conf.php';
require_once dirname(__FILE__) . '/es_liquidaciones.php';

$_LANG['ROL/RUT'] = 'RUC';
$_LANG['RUT/NIT'] = 'RUC';
$_LANG['RUT'] = 'RUC';
$_LANG['Rut'] = 'Ruc';
$_LANG['RUT personal'] = 'DNI';
$_LANG['RUT Proveedor'] = 'RUC Proveedor';
$_LANG['Gastos c/ IVA'] = 'Gastos c/ IGV';
$_LANG['Gastos s/ IVA'] = 'Gastos s/ IGV';
$_LANG['Subtotal Gastos c/IVA'] = 'Subtotal Gastos c/IGV';
$_LANG['Subtotal Gastos s/IVA'] = 'Subtotal Gastos s/IGV';
$_LANG['Gasto c/ IVA'] = 'Gasto c/ IGV';
$_LANG['Gasto s/ IVA'] = 'Gasto s/ IGV';
$_LANG['Gasto c/IVA'] = 'Gasto c/IGV';
$_LANG['Gasto s/IVA'] = 'Gasto s/IGV';
$_LANG['G SIN IVA'] = 'G SIN IGV';
$_LANG['IVA'] = 'IGV';
$_LANG['Impuesto'] = 'IGV';
$_LANG['Monto Impuesto'] = 'Monto IGV';
$_LANG['Total con IVA'] = 'Total con IGV';
$_LANG['Pago retención impuestos'] = 'Detracción';
$_LANG['Director proyecto'] = 'Encargado comercial';
$_LANG['Dólares'] = 'Dólares Americanos';
$_LANG['Dolar'] = 'Dólar Americano';
$_LANG['Mostrar detalle por profesional'] = 'Mostrar resumen por profesional';
$_LANG['Mostrar detalle por hora'] = 'Mostrar detalle por trabajo';

$_LANG['WIP'] = 'Stock';
$_LANG['WIP (Work in progress)'] = 'Stock';

// Menú
$_LANG['Cobranza'] = 'Facturación';

$_LANG['Generar borradores'] = 'Generar Pre-liquidación y planillón';
$_LANG['Excel borradores'] = 'Planillón Excel';
$_LANG['Imprimir borradores'] = 'Descargar Word';

// Attache
$_LANG['Encargado Comercial'] = 'Attache Primario';
$_LANG['Encargado comercial'] = 'Attache principal';
$_LANG['Usuario encargado'] = 'Attache Secundario';
$_LANG['Encargado'] = 'Attache';
$_LANG['Encargado Secundario'] = 'Attache Secundario';

$_LANG['id_usuario_responsable'] = 'Attache Primario';
$_LANG['id_usuario_secundario'] = 'Attache Secundario';

$_LANG['Cobrado'] = 'Facturado';
$_LANG['Cobrable'] = 'Facturable';

// $_LANG['Descargar Excel'] = 'Planillón Excel';
$_LANG['Descargar Excel'] = 'Planillón Excel';

$_LANG['Debe ingresar un monto IVA para los honorarios'] = 'Debe ingresar un monto IGV para los honorarios';
$_LANG['Debe ingresar una descripción para los gastos c/ IVA'] = 'Debe ingresar una descripción para los gastos c/ IGV';
$_LANG['Debe ingresar un monto para los gastos c/ IVA'] = 'Debe ingresar un monto para los gastos c/ IGV';
$_LANG['Debe ingresar un monto iva para los gastos c/ IVA'] = 'Debe ingresar un monto IGV para los gastos c/ IGV';
$_LANG['Debe ingresar una descripción para los gastos s/ IVA'] = 'Debe ingresar una descripción para los gastos s/ IGV';
$_LANG['Debe ingresar un monto para los gastos s/ IVA'] = 'Debe ingresar un monto para los gastos s/ IGV';

// Reportes Avanzados
$_LANG['horas_cobrables'] = 'Horas Facturables';
$_LANG['horas_no_cobrables'] = 'Horas no Facturables';
$_LANG['horas_por_cobrar'] = 'Horas Pendientes de liquidar';
$_LANG['horas_cobradas'] = 'Horas liquidadas';
$_LANG['valor_cobrado'] = 'Valor liquidado';
$_LANG['valor_por_cobrar'] = 'Valor Pendiente de liquidar';

// Reportes - Tipo de Dato
$_LANG['horas_cobrables'] = 'Horas Facturables';
$_LANG['horas_no_cobrables'] = 'Horas no Facturables';
$_LANG['horas_visibles'] = 'Horas Facturables Corregidas';
$_LANG['horas_cobradas'] = 'Horas liquidadas';
$_LANG['horas_por_cobrar'] = 'Horas Pendientes de liquidar';
$_LANG['valor_cobrado'] = 'Valor liquidado';
$_LANG['valor_por_cobrar'] = 'Valor por liquidar';

$_LANG['Total horas cobrables corregidas'] = 'Total horas facturables corregidas';

$_LANG['Duración Cobrable'] = 'Hora Facturable';

// Usuarios
$_LANG['Para agregar un nuevo usuario ingresa su RUT aquí.'] = 'Para agregar un nuevo usuario ingresa su DNI aquí.';

// Facturas
$_LANG['Honorarios Legales'] = 'Asesoría Legal';
$_LANG['Pago de Documentos tributarios'] = 'Reporte de Recaudación';

// Gastos
$_LANG['Monto cobrable'] = 'Monto facturable';

// Asuntos
$_LANG['Horas a cobrar'] = 'Horas por facturar';

// Reportes
$_LANG['Hrs. Cobradas'] = 'Hrs. Facturadas';
$_LANG['Horas cobrables'] = 'Horas facturables';
$_LANG['Horas cobrables corregidas'] = 'Horas facturables corregidas';
$_LANG['Horas cobradas'] = 'Horas facturadas';
$_LANG['Valor cobrado'] = 'Valor facturado';
$_LANG['Valor cobrado por hora'] = 'Valor facturado por hora';
$_LANG['Cobrado'] = "Facturado";
$_LANG['% Cobrado'] = "% Facturado";
$_LANG['Valor cobrado'] = "Valor Facturado";
$_LANG['cobrabilidad'] = 'Facturabilidad';
$_LANG['horas_incobrables'] = "Horas no Facturables";
$_LANG['valor_cobrado'] = "Valor Facturado";
$_LANG['valor_por_cobrar'] = "Valor por Facturar";

$_LANG['valor_incobrable'] = "Valor no Facturable";
$_LANG['horas_cobrables'] = "Horas Facturables";
$_LANG['horas_no_cobrables'] = "Horas no Facturables";
$_LANG['horas_visibles'] = "Horas Facturables Corregidas";

$_LANG['UsarImpuestoSeparado'] = "Facturar impuestos por separado";
$_LANG['UsarImpuestoPorGastos'] = "Facturar impuestos por separado a los gastos";
$_LANG['ValorImpuestoGastos'] = "Valor impuesto (%) que se cobra a los gastos";
	
$_LANG['Cobrado'] = "Facturado";

$_LANG['Valor estimado p/cobrar'] = "Valor estimado p/facturar";
$_LANG['Valor cobrado + p/cobrar'] = "Valor cobrado + p/facturar";

$_LANG['hr_cobrable'] = 'Horas Facturables';
$_LANG['horas_trabajadas_cobrables'] = 'Horas Trabajadas/Facturables';
$_LANG['hr_no_cobrables'] = 'Horas no facturables';

#algunos que faltaron
$_LANG['El valor cobrado es menor al valor según tasa de horas hombres. Cobrado/THH :'] = "El valor facturado es menor al valor según tasa de horas hombres. Cobrado/THH :";
$_LANG['Ultimo periodo cobrado'] = "Ultimo periodo facturado";

$_LANG['SOCIO COBRADOR'] = "SOCIO FACTURADOR";

$_LANG['Trabajo ya cobrado'] = "No se puede modificar un trabajo que ya ha sido facturado";
$_LANG['Trabajos masivos ya cobrados'] = "Uno o varios de los trabajos seleccionados ya han sido facturados, por favor intente nuevamente sin seleccionarlo(s).";

$_LANG['HORAS COBRADAS'] = "HORAS FACTURADAS";
$_LANG['HORAS NO COBRABLES'] = "HORAS NO FACTURADAS";
$_LANG['HORAS POR COBRAR'] = "HORAS POR FACTURAR";
$_LANG['Hrs. Cobradas'] = "Hrs. Facturadas";
$_LANG['Hrs. No Cobrables'] = "Hrs. No Facturables";
$_LANG['Hrs. por Cobrar'] = "Hrs. por Facturar";
$_LANG['Cobrado'] = "Facturado";

$_LANG['hr_asunto_cobrable'] = "Hrs. Trabajadas(Asuntos Facturables)";
$_LANG['hr_asunto_no_cobrable'] = "Hrs. Trabajadas(Asuntos No Facturables)";
?>
