<?
 require_once dirname(__FILE__).'/../conf.php';
 require_once dirname(__FILE__).'/es.php';
 
	$_LANG['ROL/RUT'] = "RUC";
	$_LANG['RUT/NIT'] = "RUC";
	$_LANG['RUT']     = "RUC";
	$_LANG['Gastos c/ IVA'] = "Gastos c/ IGV";
	$_LANG['Gastos s/ IVA'] = "Gastos s/ IGV";
	$_LANG['IVA'] = "IGV";
	$_LANG['Impuesto'] = "IGV";
	$_LANG['Monto Impuesto'] = "Monto IGV";
	$_LANG['Total con IVA']     = "Total con IGV";
	$_LANG['Pago retenci�n impuestos']     = "Detracci�n";
	$_LANG['Director proyecto']     = "Encargado comercial";

	$_LANG['D�lares']     = "D�lares Americanos";
	$_LANG['Dolar']     = "D�lar Americano";
	
	# Men�
	$_LANG['Cobranza']  = "Facturaci�n";
	$_LANG['Generaci�n de Cobros'] = "Pre-Liquidaciones";
	$_LANG['Seguimiento Cobros'] = "Seguimiento Liquidaciones";
	$_LANG['Seguimiento de cobros'] = "Seguimiento de Liquidaciones";
	$_LANG['Cobro N�'] = "Liquidaci�n N�";
	$_LANG['el cobro N�'] = "la liquidaci�n N�";
	
	$_LANG['N� Cobro'] = "N� Liquidaci�n";
	$_LANG['Eliminar cobro'] = "Eliminar pre-liquidaci�n";
	$_LANG['Revisar Cobro'] = "Revisar Liquidaci�n";
	$_LANG['Este cobro es s�lo de honorarios, y no incluye gastos'] = "Esta liquidaci�n es s�lo de honorarios, y no incluye gastos";
	$_LANG['Este cobro es s�lo de gastos, y no incluye honorarios'] = "Esta liquidaci�n es s�lo de gastos, y no incluye honorarios";
	$_LANG['Emitir Cobro :: Detalle #'] = "Emitir Liquidaci�n :: Detalle #";
	$_LANG['COBRO CREADO'] = "LIQUIDACI�N CREADA";
	$_LANG['Continuar con el cobro'] = "Continuar con la liquidaci�n";
	$_LANG['Ud. est� realizando la emisi�n masiva de cobros, aseg�rese de haber verificado sus datos o cobros en proceso.'] = "Ud. est� realizando la emisi�n masiva de liquidaciones, aseg�rese de haber verificado sus datos o liquidaciones en proceso.";
	$_LANG['�Desea emitir los cobros?'] = "�Desea emitir las liquidaciones?";
	$_LANG['Cobros generado con &eacute;xito'] = "Liquidaciones generado con &eacute;xito";
	$_LANG['Cobros emitidos con &eacute;xito'] = "Liquidaciones emitidas con &eacute;xito";
	$_LANG['Generar borradores'] = "Generar Pre-liquidaci�n y planill�n";
	$_LANG['Excel borradores'] = "Planill�n Excel";
	$_LANG['Imprimir borradores'] = "Pre-liquidaciones";
	$_LANG['Generar cobro individual'] = "Generar liquidaci�n individual";
	$_LANG['Generar cobro individual para gastos'] = "Generar liquidaci�n individual para gastos";
	$_LANG['Generar cobro individual para honorarios'] = "Generar liquidaci�n individual para honorarios";
	
	#Attache
	$_LANG['Encargado Comercial'] = "Attache Primario";
	$_LANG['Usuario encargado'] = "Attache Secundario";
	
	$_LANG['Detalle Cobro'] = "Detalle liquidaci�n";
	$_LANG['No hay gastos en este cobro'] = "No hay gastos en esta liquidaci�n";
	$_LANG['Cobro'] = "Liquidaci�n";
	$_LANG['Resumen Nota de Cobro'] = "Resumen Nota de Liquidaci�n";
	$_LANG['NOTA DE COBRO'] = "NOTA DE LIQUIDACI�N";
	$_LANG['Periodo Cobro'] = "Periodo Liquidaci�n";
	$_LANG['%reference_no%'] = "Liquidaci�n N�";
	$_LANG['Debe especificar un cliente o cobro'] = "Debe especificar un cliente o liquidaci�n";
	$_LANG['Ud. a seleccionado forma de cobro:'] = "Ud. a seleccionado forma de liquidaci�n:";
	$_LANG['Carta de cobro'] = "Carta de liquidaci�n";
	$_LANG['El valor de este cobro ha excedido al <u>CAP</u> acordado, para igualar al <u>CAP</u> se debe realizar un descuento de'] = "El valor de esta liquidaci�n ha excedido al <u>CAP</u> acordado, para igualar al <u>CAP</u> se debe realizar un descuento de";
	$_LANG['Proceso masivo de emisi�n de cobros'] = "Proceso masivo de planill�n y Pre-Liquidaciones";
	$_LANG['Cobros pendientes'] = "Liquidaci�nes pendientes";
	$_LANG['Seleccionar para cobro'] = "Seleccionar para liquidaci�n";
	$_LANG['�Ud. desea generar los cobros'] = "�Ud. desea generar los liquidaciones?";
	$_LANG['�Ud. desea emitir los cobros?'] = "�Ud. desea emitir las liquidaciones?";
	$_LANG['�Ud. desea generar este cobro individualmente?'] = "�Ud. desea generar esta liquidaci�n individualmente?";
	$_LANG['A continuaci�n se imprimir�n los cobros pendientes del periodo, si Ud. desea imprimir todos los cobros deber� chequear la opci�n correspondiente'] = "A continuaci�n se imprimir�n las liquidaciones pendientes del periodo, si Ud. desea imprimir todas las liquidaciones deber� chequear la opci�n correspondiente";
	$_LANG['Ver cobro asociado'] = "Ver liquidaci�n asociado";
	$_LANG['Cobros'] = "Liquidaciones";
	$_LANG['Cobros emitidos'] = "Liquidaciones emitidos";
	$_LANG['Revisar cobros'] = "Revisar liquidaciones";
	$_LANG['Cobro eliminado con �xito'] = "Liquidaciones eliminado con �xito";
	$_LANG['Listado de cobros'] = "Listado de liquidaciones";
	$_LANG['Forma de cobro'] = "Forma de liquidaciones";
	$_LANG['Resumen final del Cobro'] = "Resumen final de la liquidaci�n";
	$_LANG['Emitir Cobro'] = "Emitir Liquidaci�n";
	$_LANG['Emitir cobros'] = "Emisi�n de Liquidaciones";
	$_LANG['Par�metros del Cobro'] = "Par�metros de la liquidaci�n";
	$_LANG['Cobro inv�lido'] = "Liquidaci�n inv�lida";
	$_LANG['Debe especificar un cobro'] = "Debe especificar una liquidaci�n";
	$_LANG['Emitir Cobro :: Selecci�n de asuntos'] = "Emitir Liquidaci�n :: Selecci�n de asuntos";
	$_LANG['Emitir Cobro :: Seleccion de Gastos'] = "Emitir Liquidaci�n :: Seleccion de Gastos";
	$_LANG['Emitir Cobro :: Detalle'] = "Emitir Liquidaci�n :: Detalle";
	$_LANG['Tienes que ingresar la forma de cobro.'] = "Tienes que ingresar la forma de liquidaci�n.";
	$_LANG['Una vez efectuado el cobro, la informaci�n no podr� ser modificada sin reemitir el cobro, �Est� seguro que desea Emitir el Cobro?'] = "Una vez efectuado la liquidaci�n, la informaci�n no podr� ser modificada sin reemitir la liquidaci�n, �Est� seguro que desea Emitir la liquidaci�n?";
	$_LANG['Mostrar modalidad del cobro'] = "Mostrar modalidad de la liquidaci�n";
	$_LANG['Mostrar gastos del cobro'] = "Mostrar gastos de la liquidaci�n";
	$_LANG['Mostrar el descuento del cobro'] = "Mostrar el descuento de la liquidaci�n";
	$_LANG['Imprimir Cobro para '] = "Imprimir Liquidaci�n para";
	$_LANG['�Est� seguro que requiere anular la emisi�n de este cobro?'] = "�Est� seguro que requiere anular la emisi�n de esta liquidaci�n?";
	$_LANG['�Est� seguro de que desea modificar el estado del cobro?'] = "�Est� seguro de que desea modificar el estado del liquidaci�n?";
	$_LANG['Estado del Cobro'] = "Estado de la Liquidaci�n";
	$_LANG['Cobro Periodico'] = "Liquidaci�n Periodica";
	$_LANG['Fecha de Cobro'] = "Fecha de Liquidaci�n";
	$_LANG['Fecha Estimada de Cobro'] = "Fecha Estimada de Liquidaci�n";
	$_LANG['Cobro Periodico'] = "Liquidaci�n Periodica";
	$_LANG['Fecha Primer Cobro'] = "Fecha Primera Liquidaci�n";
	$_LANG['Emitir cobro :: Selecci�n del Cliente'] = "Emitir Liquidaci�n :: Selecci�n del Cliente";
	$_LANG['No se puede eliminar un contrato que tenga cobro(s) asociado(s)'] = "No se puede eliminar un contrato que tenga liquidaciones(s) asociada(s)";
	$_LANG['COBRO EMITIDO'] = "LIQUIDACI�N EMITIDA";
	$_LANG['Total Nota de Cobro'] = "Total Nota de Liquidaci�n";
	$_LANG['Total Cobro'] = "Total Liquidaci�n";
	$_LANG['Saldo aprovisionado restante tras Cobro #'] = "Saldo aprovisionado restante tras Liquidaci�n #";
	$_LANG['Fecha �ltimo cobro'] = "Fecha �ltima liquidaci�n";
	$_LANG['Fecha Prox. Cobro'] = "Fecha Prox. Liquidaci�n";
	$_LANG['horas desde el �ltimo cobro'] = "horas desde el �ltima liquidaci�n";
	$_LANG['monto desde el �ltimo cobro'] = "monto desde el �ltima liquidaci�n";
	$_LANG['Cobro inv�lido'] = "Liquidaci�n inv�lida";
	$_LANG['Hrs cobro/trab.'] = "Hrs liquidaci�n/trab.";
	$_LANG['Hrs Trab./Cobro.'] = "Hrs Trab./Liquidaci�n.";
	$_LANG['Minuta de cobro No'] = "Minuta de Liquidaci�n N� ";
	$_LANG['Fecha Cobro'] = "Fecha Liquidaci�n";
	$_LANG['Tip suma'] = "Es un �nico monto de dinero para el asunto. Aqu� interesa llevar la cuenta de HH para conocer la rentabilidad del proyecto. Esta es la �nica modalida de liquidaci�n que no puede tener l�mites.";
	$_LANG['Tip mensual'] = "La liquidaci�n se har� de forma mensual.";
	$_LANG['Tip individual'] = "La liquidaci�n se har� de forma individual de acuerdo al monto definido por Cliente.";
	$_LANG['No es posible Eliminar el Cobro. Es posible que ya se encuentre eliminado'] = "No es posible Eliminar la liquidaci�n. Es posible que ya se encuentre eliminado";
	$_LANG['Forma Cobro'] = "Forma Liquidaci�n";
	$_LANG['Debe ingresar horas desde el �ltimo cobro'] = "Debe ingresar horas desde el �ltima liquidaci�n";
	$_LANG['Debe seleccionar una forma de cobro'] = "Debe seleccionar una forma de liquidaci�n";
	$_LANG['�Est� seguro de eliminar el cobro?'] = "�Est� seguro de eliminar la liquidaci�n?";
	$_LANG['que tiene cobros asociados'] = "que tiene liquidaciones asociados";
	$_LANG['que tiene cobros asociados.'] = "que tiene liquidaciones asociados.";
	$_LANG['Error no se pudo gardar cobro #'] = "Error no se pudo guardar liquidaci�n #";
	$_LANG['Departamento Cobranza'] = "Departamento Liquidaci�n";
	$_LANG['id_cobro'] = "N� Liquidaci�n";
	$_LANG['forma_cobro'] = "Forma de Liquidaci�n";
	$_LANG['TituloContacto'] = "Indicar titulo de persona en la carta de liquidaci�n";
	$_LANG['ValorSinEspacio'] = "Mostrar valores en nota de liquidaci�n sin espacio entre simbolo y monto";
	$_LANG['ParafoGastosSoloSiHayGastos'] = "Solo imprime parafo de gastos si la liquidaci�n tiene gastos";
	$_LANG['ParafoAsuntosSoloSiHayTrabajos'] = "Solo imprime parafo de asuntos si la liquidaci�n tiene trabajos";
	$_LANG['borradores'] = "pre-liquidaciones";
	$_LANG['Descargar Archivo'] = "Pre-liquidaci�n";
	$_LANG['Descargar Excel'] = "Planill�n Excel";


	
?>
