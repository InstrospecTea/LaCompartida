<?php

class ConfMigracion {
 
	function dbHost() {
		return 'db1.ccvvg39btzna.us-east-1.rds.amazonaws.com';
	}

	function dbName() {
		return 'Payet_dbo';
	}

	function dbUser() {
		return 'admin';
	}

	function dbPass() {
		return 'admin1awdx';
	}

	function QueriesModificacionesAntes() {
		$queries = array();
		 
		       $llavetrabajocobro=UtilesApp::ExisteLlaveForanea('trabajo.id_cobro', 'cobro.id_cobro', $this->sesion); 
			if($llavetrabajocobro) 	$queries[] = "ALTER TABLE `trabajo` DROP FOREIGN KEY  `$llavetrabajocobro` ;";
			
			  $llavectacorrienteocobro=UtilesApp::ExisteLlaveForanea('cta_corriente.id_cobro', 'cobro.id_cobro', $this->sesion); 
			if($llavectacorrienteocobro) 	$queries[] = "ALTER TABLE `cta_corriente` DROP FOREIGN KEY  `$llavectacorrienteocobro` ;";
			
		 if(!UtilesApp::ExisteCampo('id_estado_factura','cobro', $this->sesion))  	$queries[] = "ALTER TABLE `cobro` ADD `id_estado_factura` INT( 11 ) NULL ;";
		 if(!UtilesApp::ExisteCampo('estado_real','cobro', $this->sesion))  $queries[] = "ALTER TABLE `cobro` ADD  `estado_real` VARCHAR( 20 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL ;";
		 if(!UtilesApp::ExisteCampo('factura_rut','cobro', $this->sesion))   $queries[] = "ALTER TABLE `cobro` ADD  `factura_rut` VARCHAR( 20 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL ";
		if(!UtilesApp::ExisteCampo('factura_razon_social','cobro', $this->sesion))   $queries[] = "ALTER TABLE `cobro`	ADD `factura_razon_social` VARCHAR( 60 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL ;";
if(!UtilesApp::ExisteCampo('id_trabajo_lemontech','cobro', $this->sesion))   $queries[] = "ALTER TABLE `cobro`	ADD `factura_razon_social` VARCHAR( 60 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL ;";
		if (!UtilesApp::ExisteIndex('PRIMARY', DBORIGEN . ".TbTarifaCategoria", $this->sesion))
		
		if(!UtilesApp::ExisteCampo('id_trabajo_lemontech', DBORIGEN . ".HojaTiempoajustado",$this->sesion)) {
						$queries[].="ALTER TABLE ".DBORIGEN.".HojaTiempoajustado  ADD `id_trabajo_lemontech` INT( 11 ) NULL;" ;
						$queries[].="UPDATE ".DBORIGEN.".HojaTiempoajustado set  `id_trabajo_lemontech`=1*hojatiempoajustadoid;" ;
		}
		if (!UtilesApp::ExisteIndex('id_trabajo_lemontech', DBORIGEN . ".HojaTiempoajustado", $this->sesion))  $queries[].="ALTER TABLE ".DBORIGEN.".HojaTiempoajustado ADD INDEX ( `id_trabajo_lemontech` );";

		
					 
		
					
		
		return $queries;
	}

	function QueriesModificacionesDespues() {
		$queries = array();
		$queries[] = "UPDATE trabajo LEFT JOIN cobro USING( id_cobro ) SET trabajo.id_cobro = NULL WHERE cobro.id_cobro IS NULL";
		if(!UtilesApp::ExisteLlaveForanea('trabajo.id_cobro', 'cobro.id_cobro', $this->sesion) ) 		$queries[] = "ALTER TABLE `trabajo` ADD FOREIGN KEY (  `id_cobro` ) REFERENCES `cobro` (`id_cobro`) ON DELETE SET NULL ON UPDATE CASCADE ;";
		if(!UtilesApp::ExisteLlaveForanea('cta_corriente.id_cobro', 'cobro.id_cobro', $this->sesion) )  $queries[] = "ALTER TABLE `cta_corriente` ADD FOREIGN KEY (  `id_cobro` ) REFERENCES  `cobro` (`id_cobro`) ON DELETE SET NULL ON UPDATE CASCADE ;";
		
		$queries[] = "UPDATE cobro SET estado = estado_real WHERE estado_real IS NOT NULL AND estado_real != ''";
		$queries[] = "UPDATE cobro SET estado = 'FACTURADO' WHERE ( SELECT count(*) FROM factura WHERE factura.id_cobro = cobro.id_cobro ) > 0 AND estado IN ('CREADO','EN REVISION','EMISION');";
		$queries[] = "UPDATE cobro 
											JOIN factura USING( id_cobro ) 
											JOIN cta_cte_fact_mvto USING( id_factura ) 
											JOIN cta_cte_fact_mvto_neteo ON cta_cte_fact_mvto.id_cta_cte_mvto = cta_cte_fact_mvto_neteo.id_mvto_deuda 
											SET cobro.estado = 'PAGO PARCIAL';";
		$queries[] = "UPDATE cobro 
											JOIN factura USING( id_cobro ) 
											JOIN cta_cte_fact_mvto USING( id_factura ) 
											SET cobro.estado = 'PAGADO' 
											WHERE cta_cte_fact_mvto.saldo = 0;";
		return $queries;
	}

	function DatosPrm() {
		return array('prm_categoria_usuario' => array(
				'campo_glosa' => 'glosa_categoria',
				'campo_id' => 'id_categoria_usuario',
				'datos' => array('Administrativo', 'Asistente', 'Asociado', 'Asociado Junior', 'Asociado Senior', 'Practicante', 'Secretaria', 'Socio', 'NT', 'Procurador')),
			'prm_area_usuario' => array(
				'campo_glosa' => 'glosa',
				'campo_id' => 'id',
				'datos' => array('Administración', 'Corporativo', 'Laboral', 'Procesal', 'Regulatorio', 'Tributario')),
			'grupo_cliente' => array(
				'campo_glosa' => 'glosa_grupo_cliente',
				'campo_id' => 'id_grupo_cliente',
				'datos' => array('GRUPO BACKUS', 'GRUPO VALE', 'GRUPO SCOTIA', 'GRUPO AMOV', 'GRUPO CHINALCO', 'GRUPO AC CAPITALES', 'GRUPO GOLD',
					'GRUPO WWG', 'GRUPO BBVA', 'GRUPO ENDESA', 'GRUPO BREADT', 'FAMILIA SARFATY', 'GRUPO ILASA', 'GRUPO BNP', 'GRUPO URÍA', 'GRUPO GOURMET')),
			'prm_area_proyecto' => array(
				'campo_glosa' => 'glosa',
				'campo_id' => 'id_area_proyecto',
				'datos' => array('Corporativo', 'Finanzas', 'Laboral', 'Mercado de Valores', 'Procesal', 'Regulatorio', 'Tributario'))
		);
	}

	function QueryUsuario() {
		return "SELECT 
								if(length(CodigoEmpleado)=6,2000+1*CodigoEmpleado 	,1000+1*CodigoEmpleado 	)			as usuario_FFF_id_usuario,
CodigoEmpleado as 		usuario_FFF_id_usuario_antiguo,					
Empleado.Nombres 																								as usuario_FFF_nombre,
								Empleado.ApellidoPaterno 																				as usuario_FFF_apellido1,
								Empleado.ApellidoMaterno 																				as usuario_FFF_apellido2,
								Empleado.Siglas 																								as usuario_FFF_username,
								Empleado.Categoria																							as usuario_FFF_id_categoria_usuario,
								CONCAT_WS(', ',Empleado.Direccion,Empleado.Provincia) 					as usuario_FFF_dir_calle,
								Empleado.Departamento 																					as usuario_FFF_dir_depto,
								Empleado.Telefono1 																							as usuario_FFF_telefono1,
								Empleado.Telefono1 																							as usuario_FFF_telefono2,
								Empleado.DocIdentidadNumero																		  as usuario_FFF_rut,
								Empleado.CorreoElectronico 																			as usuario_FFF_email,
								IF(Empleado.Status='A','1','0') 																as usuario_FFF_activo,
								Empleado.FechaCreacion 																					as usuario_FFF_fecha_creacion,
								IF(Empleado.moneda='D','2','1') 																as usuario_FFF_id_moneda_costo
							FROM Empleado
							LEFT JOIN TbCategoriaEmpleados ON Empleado.Categoria = TbCategoriaEmpleados.CodigoCategoria 
							AND Empleado.TipoEmpleado = TbCategoriaEmpleados.TipoEmpleado";
	}

	function QueryCliente() {
		return "SELECT 
								Cliente.CodigoCliente 																																			as cliente_FFF_codigo_cliente, 
								Cliente.NombreCliente																																				as cliente_FFF_glosa_cliente,
								Cliente.NombreCliente																																				as cliente_FFF_rsocial,
								Cliente.NombreCliente																																				as contrato_FFF_factura_razon_social,
								IF(Cliente.Status='A','1','0') 																															as cliente_FFF_activo, 
								IF(Cliente.Status='A','SI','NO') 																														as contrato_FFF_activo, 
								CONCAT(Cliente.Direccion1,' // ', Cliente.Direccion2) 																			as cliente_FFF_dir_calle,
								CONCAT(Cliente.Direccion1,' // ', Cliente.Direccion2) 																			as contrato_FFF_direccion_contacto,
								CONCAT(Cliente.Direccion1,' // ', Cliente.Direccion2) 																			as contrato_FFF_factura_direccion,
								CONCAT_WS(', ',Cliente.Telefono1, Cliente.Telefono2, Cliente.Telefono3, Cliente.Telefono4) 	as cliente_FFF_fono_contacto, 
								CONCAT_WS(', ',Cliente.Telefono1, Cliente.Telefono2, Cliente.Telefono3, Cliente.Telefono4) 	as contrato_FFF_fono_contacto,
								CONCAT_WS(', ',Cliente.Telefono1, Cliente.Telefono2, Cliente.Telefono3, Cliente.Telefono4) 	as contrato_FFF_factura_telefono,
								CONCAT(Cliente.DocumentoIdentidad, Cliente.DocIdentidadNumero) 															as cliente_FFF_rut, 
								CONCAT(Cliente.DocumentoIdentidad, Cliente.DocIdentidadNumero) 															as contrato_FFF_rut,
								Cliente.Actividad 																																					as cliente_FFF_giro, 
								Cliente.Actividad 																																					as contrato_FFF_factura_giro,
								IF(Cliente.CodigoImpuesto='I','1','0') 																											as contrato_FFF_usa_impuesto_separado,
								IF(Cliente.CodigoImpuesto='I','1','0') 																											as contrato_FFF_usa_impuesto_gastos, 
								IF(Cliente.MonedaDefault='D','2',IF(Cliente.MonedaDefault='E','3','1')) 										as cliente_FFF_id_moneda, 
								IF(Cliente.MonedaDefault='D','2',IF(Cliente.MonedaDefault='E','3','1')) 										as contrato_FFF_id_moneda, 
								IF(Cliente.MonedaDefault='D','2',IF(Cliente.MonedaDefault='E','3','1')) 										as contrato_FFF_opc_moneda_total, 
								IF(Cliente.MonedaTarifaMensual='D','2',IF(Cliente.MonedaTarifaMensual='E','3','1')) 				as contrato_FFF_id_moneda_monto, 
								Cliente.TarifaHora 																																					as contrato_FFF_id_tarifa, 
								Cliente.TarifaMensual 																																			as contrato_FFF_monto, 
								IF(Cliente.Attache IS NOT NULL, Cliente.Attache, 1) 																				as contrato_FFF_id_usuario_responsable,
								Cliente.ContactoDeCobranza 																																	as contrato_FFF_contacto, 
								Cliente.FechaCreacion 																																			as contrato_FFF_fecha_creacion, 
								Cliente.FechaModificacion 																																	as contrato_FFF_fecha_modificacion, 
								IF(Cliente.FlagRetainer='S','Retainer','TASA') 																							as contrato_FFF_forma_cobro, 
								Cliente.CodigoClienteAlterno 																																as cliente_FFF_codigo_cliente_secundario, 
								Cliente.attachesecundario 																																	as cliente_FFF_id_usuario_encargado, 
								Cliente.attachesecundario 																																	as contrato_FFF_id_usuario_secundario, 
								GROUP_CONCAT( Titulo, Nombre, Telefono SEPARATOR '//' ) 																		as cliente_FFF_nombre_contacto, 
								'1'																																													as contrato_FFF_separar_liquidaciones 
							FROM Cliente 
							LEFT JOIN ContactosCliente ON Cliente.CodigoCliente = ContactosCliente.CodigoCliente 
							GROUP BY Cliente.CodigoCliente";
	}

	function QueryAsunto() {
		return "SELECT 
								if(length( Cliente.Cobrador )=6,2000+1* Cliente.Cobrador   	,1000+1* Cliente.Cobrador  	) 	as asunto_FFF_id_cobrador,
						concat(OrdenFacturacion.CodigoCliente,'-',OrdenFacturacion.NumeroOrdenFact) 	as asunto_FFF_codigo_asunto,
					OrdenFacturacion.NumeroOrdenFact	as asunto_FFF_codigo_asunto_secundario,
								OrdenFacturacion.CodigoCliente 					as asunto_FFF_codigo_cliente,
OrdenFacturacion.CodigoCliente as contrato_FFF_codigo_cliente,
CONCAT_WS(' ',ContactosCliente.Titulo, ContactosCliente.Nombre) 						as asunto_FFF_contacto,
CONCAT_WS(' ',Titulo, Nombre) 																							as contrato_FFF_contacto,
if(length( OrdenFacturacion.CodigoAbogadoResponsable)=6,     2000+1* OrdenFacturacion.CodigoAbogadoResponsable  	,    1000+1* OrdenFacturacion.CodigoAbogadoResponsable  	) 				as asunto_FFF_id_encargado,

if(length( OrdenFacturacion.CodigoAbogadoResponsable)=6,2000+1* OrdenFacturacion.CodigoAbogadoResponsable  	,1000+1* OrdenFacturacion.CodigoAbogadoResponsable  	) 																	as contrato_FFF_id_usuario_secundario,
if(length( OrdenFacturacion.CodigoAbogadoResponsable)=6,2000+1* OrdenFacturacion.CodigoAbogadoResponsable  	,1000+1* OrdenFacturacion.CodigoAbogadoResponsable  	) 	 																	as asunto_FFF_id_usuario,
if(length(OrdenFacturacion.Attache )=6,2000+1*OrdenFacturacion.Attache 	,1000+1*OrdenFacturacion.Attache  	) 		as contrato_FFF_id_usuario_responsable,
								IF(OrdenFacturacion.FlagFacturable='S','1','0') 														as asunto_FFF_cobrable,
								IF(OrdenFacturacion.HojaTiemposFlag='O','1','0')														as asunto_FFF_activo,
								IF(OrdenFacturacion.HojaTiemposFlag='O','SI','NO')													as contrato_FFF_activo,
								OrdenFacturacion.Asunto 																										as asunto_FFF_glosa_asunto,
								IF( OrdenFacturacion.Moneda = 'S', '1', IF( OrdenFacturacion.Moneda = 'E', '3', '2' ) ) 	as asunto_FFF_id_moneda,
								IF( OrdenFacturacion.Moneda = 'S', '1', IF( OrdenFacturacion.Moneda = 'E', '3', '2' ) )		as contrato_FFF_id_moneda,
								IF( OrdenFacturacion.Moneda = 'S', '1', IF( OrdenFacturacion.Moneda = 'E', '3', '2' ) )		as contrato_FFF_id_moneda_monto,
								IF( OrdenFacturacion.Moneda = 'S', '1', IF( OrdenFacturacion.Moneda = 'E', '3', '2' ) )		as contrato_FFF_opc_moneda_total,
								OrdenFacturacion.TarifaHora 																								as contrato_FFF_id_tarifa,
								OrdenFacturacion.FechaModificacion 																					as asunto_FFF_fecha_modificacion,
								OrdenFacturacion.FechaCreacion 																							as asunto_FFF_fecha_creacion,
								CONCAT_WS(', ',Cliente.Telefono1, Cliente.Telefono2, Cliente.Telefono3, Cliente.Telefono4) 	as contrato_FFF_factura_telefono,
								CONCAT_WS(', ',Cliente.Telefono1, Cliente.Telefono2, Cliente.Telefono3, Cliente.Telefono4) 	as contrato_FFF_fono_contacto,
								OrdenFacturacion.HorasTope 																									as asunto_FFF_limite_hh,
								IF(Cliente.CodigoImpuesto='I','1','0') 																			as contrato_FFF_usa_impuesto_separado,
								IF(Cliente.CodigoImpuesto='I','1','0') 																			as contrato_FFF_usa_impuesto_gastos, 
								CONCAT(Cliente.Direccion1,' // ', Cliente.Direccion2) 											as contrato_FFF_factura_direccion,
								OrdenFacturacionHistoria.Descripcion 																				as asunto_FFF_descripcion_asunto,  
								CONCAT(Cliente.DocumentoIdentidad,Cliente.DocIdentidadNumero) 							as asunto_FFF_rut, 
								CONCAT(Cliente.DocumentoIdentidad,Cliente.DocIdentidadNumero) 							as contrato_FFF_rut,
								Cliente.FechaCreacion 																											as contrato_FFF_fecha_creacion, 
								Cliente.FechaModificacion 																									as contrato_FFF_fecha_modificacion, 
								Cliente.Actividad																														as asunto_FFF_giro,
								Cliente.Actividad																														as contrato_FFF_factura_giro,
								Cliente.NombreCliente																												as asunto_FFF_razon_social, 
								Cliente.NombreCliente																												as contrato_FFF_factura_razon_social
									,IF(OrdenFacturacion.TipoFactExtraordinaria='A' AND  (OrdenFacturacion.HonorarioPactado>0 OR prop.hf_valorventa>0 ),'FLAT FEE','TASA')					as contrato_FFF_forma_cobro
								,IF(OrdenFacturacion.HonorarioPactado>0, OrdenFacturacion.HonorarioPactado,prop.hf_valorventa)	as contrato_FFF_monto,
								'1'	 as contrato_FFF_separar_liquidaciones 
							FROM  ".DBORIGEN.".OrdenFacturacion left  join  ".DBORIGEN.".propuesta prop using (numeropropuesta) 
							LEFT JOIN   ".DBORIGEN.".Cliente ON OrdenFacturacion.CodigoCliente = Cliente.CodigoCliente 
							 
							LEFT JOIN  ".DBORIGEN.".OrdenFacturacionHistoria ON OrdenFacturacion.NumeroOrdenFact = OrdenFacturacionHistoria.NumeroOrdenFact 
							LEFT JOIN  ".DBORIGEN.".ContactosCliente ON ContactosCliente.CodigoContactoCliente = OrdenFacturacion.CodigoContactoCliente  
								$extra ";
	}

	
	
			
	function QueryHoras($extra) {
		return "SELECT
 if(ifnull(hta.CodigoEmpleadoFacturable, htd.CodigoEmpleado)=6, 2000+1*ifnull(hta.CodigoEmpleadoFacturable, htd.CodigoEmpleado), 1000+1*ifnull(hta.CodigoEmpleadoFacturable, htd.CodigoEmpleado)) as id_usuario,
								
			 					hta.FechaFacturable	as fecha 
		 					,	hta.HoraInicioFacturable	as hora_inicio
		 					,hta.NumeroFactura			as id_cobro ,
						 		SEC_TO_TIME(htd.Tiempo*60)  as duracion,
                IF(hta.Status='C','00:00:00',SEC_TO_TIME(hta.TiempoFacturable*60))	as duracion_cobrada,
							 	IF(hta.FlagFacturable = 'S','1','0') 																												as cobrable,
								 IFNULL(hta.AsuntoLargoFacturable, htd.AsuntoLargo) 				as descripcion,
								 IFNULL(hta.FechaCreacion, htd.FechaCreacion) 											as fecha_creacion,
								 IFNULL(hta.FechaModificacion, htd.FechaModificacion) 					as fecha_modificacion
								   ,IF(hta.TarifaFacturable IS NOT NULL,hta.TarifaFacturable ,htd.Tarifa) 												as tarifa_hh
								
                 ,if(ifnull(Factura.Moneda, hta.moneda)='D',2,if( ifnull(Factura.Moneda, hta.moneda)='E',3,1  ))		as id_moneda
							 	,IF(hta.NumeroOrdenFacturacionFact IS NULL, concat(htd.Cliente,'-', htd.NumeroOrdenFacturacion),concat(hta.ClienteFacturable,'-', hta.NumeroOrdenFacturacionFact)) as codigo_asunto
								 ,hta.id_trabajo_lemontech  as id_trabajo 			
                
               
								FROM     ".DBORIGEN.".HojaTiempoajustado hta
								LEFT JOIN ".DBORIGEN.".Hojatiemporelacion htr ON htr.hojatiempoajustadoid=hta.hojatiempoajustadoid
								LEFT JOIN ".DBORIGEN.".HojaTiempoDetalle htd ON htd.hojatiempoid = htr.hojatiempoid  and hta.NumeroOrdenFacturacionFact=htd.NumeroOrdenFacturacion
								LEFT JOIN ".DBORIGEN.".Factura ON Factura.NumeroFactura = hta.NumeroFactura
								 $extra
								";
	}

	function QueryGastos($extra) {
		return "SELECT
						   	if(CodigoGasto is null, null, 	1*CodigoGasto) 																											as gasto_FFF_id_movimiento,
					  			rucproveedor																																			as gasto_FFF_proveedor_ruc,
						  			razonsocialproveedor																															as gasto_FFF_proveedor_rsocial,
					 			 CodigoGasto																																				as gasto_FFF_numero_documento, 
					 			Gastos.FechaCreacion 																															as gasto_FFF_fecha_creacion,
					 			
						  			
						 		Gastos.FechaModificacion 																													as gasto_FFF_fecha_modificacion,
								 	CONCAT( Gastos.CodigoCliente,'-',Gastos.NumeroOrdenFact ) as gasto_FFF_codigo_asunto,
									Gastos.FechaGasto 																																as gasto_FFF_fecha,
									Gastos.NumeroFactura																															as gasto_FFF_id_cobro, 
									if(length(Empleado.CodigoEmpleado )=6,2000+1*Empleado.CodigoEmpleado  ,1000+1*Empleado.CodigoEmpleado  ) 																													as gasto_FFF_id_usuario,
									if(length(Gastos.CodigoEmpleado 	)=6,2000+1*Gastos.CodigoEmpleado 	 ,1000+1*Gastos.CodigoEmpleado 	 )																													as gasto_FFF_id_usuario_orden,
									Gastos.DescripcionGasto 																													as gasto_FFF_descripcion,
									IF(Gastos.moneda = 'S',Gastos.MontoSoles,Gastos.MontoDolares) 														as gasto_FFF_egreso,
									IF(Gastos.moneda = 'S',Gastos.MontoSoles,Gastos.MontoDolares) 														as gasto_FFF_monto_cobrable,
									Gastos.CodigoCliente 																															as gasto_FFF_codigo_cliente,
									IF(Gastos.flagfacturable='S','1','0') 																						as gasto_FFF_cobrable,
									IF(Gastos.moneda='S','1',IF(Gastos.moneda='E','3','2')) 													as gasto_FFF_id_moneda,
									Factura.CodigoFacturaBoleta																												as gasto_FFF_codigo_factura_gasto,
									Factura.FechaImpresion																														as gasto_FFF_fecha_factura 
									FROM  ".DBORIGEN.".Gastos
									LEFT JOIN ".DBORIGEN.".Empleado ON TRIM(Empleado.Siglas) = TRIM(Gastos.Creadopor)
									LEFT JOIN ".DBORIGEN.".Factura ON Gastos.NumeroFactura = Factura.NumeroFactura 
										$extra ";
	}

	function QueryMonedaHistorial() {
		return "SELECT * FROM TipoDeCambio";
	}

	function QueryCobros() {
		return "SELECT 
									Factura.NumeroFactura 																					as cobro_FFF_id_cobro,
									Factura.FechaGeneracion 																				as cobro_FFF_fecha_creacion,
									Factura.CodigoFacturaBoleta 																		as cobro_FFF_documento,
									IF(Factura.Status='A','5',IF(Factura.Status='C','2','1'))				as cobro_FFF_id_estado_factura, 
									Factura.CodigoCliente 																					as cobro_FFF_codigo_cliente,
									IF(Factura.Moneda='S','1',IF(Factura.Moneda='E','3','2'))				as cobro_FFF_opc_moneda_total,
									Factura.Observacion																							as cobro_FFF_observaciones,
									IF(Factura.Moneda='S','1',IF(Factura.Moneda='E','3','2')) 			as cobro_FFF_id_moneda_monto,
									IF(Factura.Moneda='S','1',IF(Factura.Moneda='E','3','2')) 			as cobro_FFF_id_moneda,
									Factura.MontoNeto 																							as cobro_FFF_monto,
									Factura.MontoBruto 																							as cobro_FFF_monto_contrato, 
									Factura.MontoImpuesto 																					as cobro_FFF_impuesto,
									'FLAT FEE'																											as cobro_FFF_forma_cobro, 
									Factura.MontoBruto 																							as cobro_FFF_monto_subtotal,
									IF(Factura.PorcentajeImpuesto='19.00','19',
										IF(Factura.PorcentajeImpuesto='0.19','19',
											IF(Factura.PorcentajeImpuesto='0.18','18','0')))						as cobro_FFF_porcentaje_impuesto,
									Periodo.FechaInicio 																						as cobro_FFF_fecha_ini,
									Periodo.FechaTermino 																						as cobro_FFF_fecha_fin,
									IF(Factura.Status='A','INCOBRABLE',IF(Factura.Status='C','PAGADO',
										IF(Factura.Status='I','ENVIADO AL CLIENTE','EMITIDO')))				as cobro_FFF_estado_real,
									CONCAT(SUBSTRING(Factura.NumeroOrdenFact,1,4),'-0',SUBSTRING(Factura.NumeroOrdenFact,-3)) as cobro_FFF_codigo_asunto,
									Factura.NombreClienteFacturacion																as cobro_FFF_factura_razon_social,
									Factura.DocIdentidadNumeroFacturacion														as cobro_FFF_factura_rut,
									if(length(Empleado.CodigoEmpleado )=6,2000+1*Empleado.CodigoEmpleado  ,1000+1*Empleado.CodigoEmpleado  ) as cobro_FFF_id_usuario
								FROM  ".DBORIGEN.".Factura
								LEFT JOIN  ".DBORIGEN.".Periodo ON Periodo.CodigoPeriodo = Factura.PeriodoFacturacionFija
								LEFT JOIN  ".DBORIGEN.".Empleado ON LOWER(TRIM(Empleado.Siglas)) = LOWER(TRIM(Factura.creadopor))";
	}

	function QueryFacturas() {
		return "SELECT 
									Factura.NumeroFactura 																					as factura_FFF_id_cobro,
									Factura.NumeroFactura 																					as factura_FFF_id_factura,
									Factura.NumeroFactura																						as factura_FFF_numero,
									Factura.FechaGeneracion 																				as factura_FFF_fecha_creacion,
									Factura.CodigoCliente 																					as cobro_FFF_codigo_cliente,
									IF(Factura.Moneda='S','1',IF(Factura.Moneda='E','3','2'))				as factura_FFF_id_moneda,
									Factura.MontoBruto 																							as factura_FFF_total,
									Factura.MontoImpuesto 																					as factura_FFF_iva,
									Factura.MontoNeto 																							as factura_FFF_honorarios,
									Factura.PorcentajeImpuesto 																			as factura_FFF_porcentaje_impuesto
								FROM Factura
								LEFT JOIN Periodo ON Periodo.CodigoPeriodo = Factura.PeriodoFacturacionFija
								WHERE Factura.CodigoFacturaBoleta IS NOT NULL";
	}

	function QueryTarifas() {
		return "SELECT 
									CodigoTarifaCliente as id_tarifa 
									,Descripcion as glosa_tarifa 
									,'1' as guardado 
									FROM TbTarifaCliente";
	}

	function QueryUsuariosTarifas() {
		return "SELECT 
								T1.id_usuario_tarifa_LMT as id_usuario_tarifa 
								, if(length(T1.CodigoEmpleado )=6,2000+1*T1.CodigoEmpleado  ,1000+1*T1.CodigoEmpleado  ) AS id_usuario 
								, if(T1.moneda='D' ,'2',if(T1.moneda = 'E','3',if(T1.moneda = 'S','1','0'))) AS id_moneda 
								, T1.TarifaHora AS tarifa 
								, T1.CodigoTarifaCliente as id_tarifa 
								FROM  `TbTarifaCategoria` as T1 
								WHERE T1.CodigoPeriodo = 
									( SELECT MAX(T2.CodigoPeriodo) 
										FROM TbTarifaCategoria as T2 
										WHERE T2.CodigoEmpleado = T1.CodigoEmpleado 
											AND T2.CodigoTarifaCliente = T1.CodigoTarifaCliente 
											AND T2.moneda = T1.moneda )";
	}

	function QueryPagos() {
		return "SELECT 
								P.NumeroFactura 																			as documento_FFF_id_cobro,
								P.NumeroFactura 																			as factura_FFF_id_factura,
								P.FechaDePago																					as factura_FFF_fecha,
								P.FechaDePago																					as documento_FFF_fecha,
								P.NumeroDocumentoPago																	as documento_FFF_numero_doc,
								P.NumeroDocumentoPago																	as factura_FFF_nro_documento,
								P.MontoSoles																					as documento_FFF_monto_base,
								IF(MonedaPago='D','2',IF(MonedaPago='E','3','1'))		as documento_FFF_id_moneda,
								IF(MonedaPago='D','2',IF(MonedaPago='E','3','1'))		as factura_FFF_id_moneda,
								P.MontoPago																						as documento_FFF_monto,
								P.MontoPago																						as factura_FFF_monto,
								IF(Factura.Moneda='D','2',IF(Factura.Moneda='E','3','1'))		as factura_FFF_id_moneda_cobro,
								IF(Factura.Moneda='D','2',IF(Factura.Moneda='E','3','1'))		as documento_FFF_id_moneda_cobro,
								IF( Factura.Moneda = P.MonedaPago,
										MontoPago, IF( Factura.Moneda = 'S', MontoSoles,
											IF( Factura.Moneda = 'D', MontoDolares, MontoPago * P.TipoCambioPago / P.TipoCambioFacturacion ) ) )
																																		as factura_FFF_monto_cobro,
								IF( Factura.Moneda = P.MonedaPago,
										MontoPago, IF( Factura.Moneda = 'S', MontoSoles,
											IF( Factura.Moneda = 'D', MontoDolares, MontoPago * P.TipoCambioPago / P.TipoCambioFacturacion ) ) )
																																		as documento_FFF_monto_cobro,
								NombreBanco																					as factura_FFF_glosa_banco,
								P.CodigoCuentaBanco																		as factura_FFF_cuenta_banco,
								NombreBanco																					as documento_FFF_glosa_banco,
								P.CodigoCuentaBanco																		as documento_FFF_cuenta_banco,
								P.FechaModificacion																		as factura_FFF_fecha_modificacion,
								P.FechaModificacion																		as documento_FFF_fecha_modificacion,
								P.CodigoBancoCheque																		as documento_FFF_numero_cheque,
								P.CodigoBancoCheque																		as factura_FFF_nro_cheque,
								P.id_factura_pago_lemontech														as factura_FFF_id_factura_pago,
								P.id_factura_pago_lemontech + 100000									as documento_FFF_id_documento 
							FROM PagosRecibidos P 
							LEFT JOIN Factura ON Factura.NumeroFactura = P.NumeroFactura 
							LEFT JOIN TbBancos ON TbBancos.CodigoBanco = P.CodigoBanco";
	}

}

?>
