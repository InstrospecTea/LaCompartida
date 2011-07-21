<?
	class ConfMigracion 
	{
		function dbHost() { return 'lab.lemontech.cl'; }
		function dbName() { return 'Payet_dbo'; }
		function dbUser() { return 'Mario'; }
		function dbPass() { return 'Mario.asdwsx'; }
		function DatosPrm() { return array( 'prm_categoria_usuario' => array( 
																					'campo_glosa' 					=> 'glosa_categoria', 
																				  'campo_id'            	=> 'id_categoria_usuario', 
																				  'datos'              	  => array('Administrativo','Asistente','Asociado','Asociado Junior','Asociado Senior','Practicante','Secretaria','Socio','NT','Procurador')),
																				'prm_area_usuario'      => array( 
																					'campo_glosa' 					=> 'glosa',           
																				  'campo_id'            	=> 'id',                   
																				  'datos'              	  => array('Administración','Corporativo','Laboral','Procesal','Regulatorio','Tributario')),
																				'grupo_cliente'         => array( 
																					'campo_glosa' 					=> 'glosa_grupo_cliente', 
																				  'campo_id'            	=> 'id_grupo_cliente',
																				  'datos'               	=> array('GRUPO BACKUS','GRUPO VALE','GRUPO SCOTIA','GRUPO AMOV','GRUPO CHINALCO','GRUPO AC CAPITALES','GRUPO GOLD',
																				  																 'GRUPO WWG','GRUPO BBVA','GRUPO ENDESA','GRUPO BREADT','FAMILIA SARFATY','GRUPO ILASA','GRUPO BNP','GRUPO URÍA','GRUPO GOURMET')),
																				'prm_area_proyecto'     => array( 
																					'campo_glosa' 					=> 'glosa', 
																				  'campo_id'            	=> 'id_area_proyecto',
																				  'datos'               	=> array('Corporativo','Finanzas','Laboral','Mercado de Valores','Procesal','Regulatorio','Tributario'))
																			); 
		}
		function QueryUsuario() 
		{
			return "SELECT 
								Empleado.CodigoEmpleado 																				as usuario_FFF_id_usuario,
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
		function QueryCliente() 
		{ 
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
								GROUP_CONCAT( Titulo, Nombre, Telefono SEPARATOR '//' ) 																		as cliente_FFF_nombre_contacto  
							FROM Cliente 
							LEFT JOIN ContactosCliente ON Cliente.CodigoCliente = ContactosCliente.CodigoCliente 
							GROUP BY Cliente.CodigoCliente";
		}
		function QueryAsunto() 
		{ 
			return "SELECT 
								Cliente.Cobrador 																														as asunto_FFF_id_cobrador,
								CONCAT(SUBSTRING(OrdenFacturacion.NumeroOrdenFact,1,4),'-0',SUBSTRING(OrdenFacturacion.NumeroOrdenFact,-3)) 	as asunto_FFF_codigo_asunto,
								OrdenFacturacion.CodigoCliente 																							as asunto_FFF_codigo_cliente,
								OrdenFacturacion.CodigoCliente 																							as contrato_FFF_codigo_cliente,
								CONCAT_WS(' ',ContactosCliente.Titulo, ContactosCliente.Nombre) 						as asunto_FFF_contacto,
								CONCAT_WS(' ',Titulo, Nombre) 																							as contrato_FFF_contacto,
								OrdenFacturacion.CodigoAbogadoResponsable 																	as asunto_FFF_id_encargado,
								OrdenFacturacion.CodigoAbogadoResponsable 																	as asunto_FFF_id_usuario,
								OrdenFacturacion.Attache 																										as contrato_FFF_id_usuario_responsable,
								IF(OrdenFacturacion.FlagFacturable='S','1','0') 														as asunto_FFF_cobrable,
								IF(OrdenFacturacion.HojaTiemposFlag='O','1','0')														as asunto_FFF_activo,
								IF(OrdenFacturacion.HojaTiemposFlag='O','SI','NO')													as contrato_FFF_activo,
								OrdenFacturacion.Asunto 																										as asunto_FFF_glosa_asunto,
								IF( OrdenFacturacion.Moneda = 'S', '1', IF( OrdenFacturacion.Moneda = 'E', '3', '2' ) ) 	as asunto_FFF_id_moneda,
								IF( OrdenFacturacion.Moneda = 'S', '1', IF( OrdenFacturacion.Moneda = 'E', '3', '2' ) )		as contrato_FFF_id_moneda,
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
								,IF(OrdenFacturacion.HonorarioPactado>0, if(OrdenFacturacion.HonorarioPactado=OrdenFacturacion.HonorarioFacturado,NULL,'FLAT FEE' ),'TASA')        as contrato_FFF_forma_cobro
								,IF(OrdenFacturacion.HonorarioPactado>0, if(OrdenFacturacion.HonorarioPactado=OrdenFacturacion.HonorarioFacturado, OrdenFacturacion.HonorarioPactado , OrdenFacturacion.HonorarioPactado ),0)        as contrato_FFF_monto
							FROM OrdenFacturacion 
							LEFT JOIN Cliente ON OrdenFacturacion.CodigoCliente = Cliente.CodigoCliente 
							LEFT JOIN OrdenFacturacionHistoria ON OrdenFacturacion.NumeroOrdenFact = OrdenFacturacionHistoria.NumeroOrdenFact 
							LEFT JOIN ContactosCliente ON ContactosCliente.CodigoContactoCliente = OrdenFacturacion.CodigoContactoCliente";
		}
		function QueryHoras()
		{
			return "SELECT
								if(hta.CodigoEmpleadoFacturable is not null, hta.CodigoEmpleadoFacturable,htd.CodigoEmpleado) as id_usuario
								,hta.FechaFacturable																																					as fecha 
								,hta.HoraInicio 																																							as hora_inicio
								,hta.NumeroFactura																																						as id_cobro 
								,SEC_TO_TIME(hta.Tiempo*60)																																		as duracion
								,SEC_TO_TIME(hta.TiempoFacturable*60) 																												as duracion_cobrada
								,IF(hta.FlagFacturable = 'S',1,0) 																														as cobrable
								,IF(hta.AsuntoLargoFacurable IS NOT NULL,hta.AsuntoLargoFacurable, htd.AsuntoLargo) 					as descripcion
								,IF(hta.FechaCreacion IS NOT NULL,hta.FechaCreacion, htd.FechaCreacion) 											as fecha_creacion
								,IF(hta.FechaModificacion IS NOT NULL,hta.FechaModificacion, htd.FechaModificacion) 					as fecha_modificacion
								,IF(hta.tarifacliente IS NOT NULL,hta.tarifacliente ,htd.tarifacliente) 											as tarifa_hh
								,IF(hta.moneda IS NOT NULL, hta.moneda, htd.moneda)																						as id_moneda
								,IF(hta.NumeroOrdenFacturacionFact, hta.NumeroOrdenFacturacionFact,htd.NumeroOrdenFacturacion) as codigo_asunto
								,hta.id_trabajo_lemontech 																																		as id_trabajo
								FROM HojaTiempoajustado hta
								LEFT JOIN Hojatiemporelacion htr ON htr.hojatiempoid=hta.hojatiempoid
								LEFT JOIN HojaTiempoDetalle htd ON htd.hojatiempoajustadoid = htr.hojatiempoajustadoid";
		}
		
		function QueryGastos() 
		{ 
			return "SELECT
									IdGastoLemontech																																	as gasto_FFF_id_movimiento,
									CodigoGasto																																				as gasto_FFF_numero_documento, 
									Gastos.FechaCreacion 																															as gasto_FFF_fecha_creacion,
									Gastos.NumeroFactura																															as gasto_FFF_id_cobro,
									Gastos.FechaModificacion 																													as gasto_FFF_fecha_modificacion,
									CONCAT( SUBSTRING(Gastos.NumeroOrdenFact,1,4),'-0',SUBSTRING(Gastos.NumeroOrdenFact,-3) ) as gasto_FFF_codigo_asunto,
									Gastos.FechaGasto 																																as gasto_FFF_fecha,
									Gastos.NumeroFactura																															as gasto_FFF_id_cobro, 
									Gastos.CodigoEmpleado 																														as gasto_FFF_id_usuario,
									Gastos.DescripcionGasto 																													as gasto_FFF_descripcion,
									IF(moneda = 'S',Gastos.MontoSoles,Gastos.MontoDolares) 														as gasto_FFF_egreso,
									IF(moneda = 'S',Gastos.MontoSoles,Gastos.MontoDolares) 														as gasto_FFF_monto_cobrable,
									Gastos.CodigoCliente 																															as gasto_FFF_codigo_cliente,
									IF(Gastos.flagfacturable='S','1','0') 																						as gasto_FFF_cobrable,
									IF(Gastos.moneda='S','1',IF(Gastos.moneda='E','3','2')) 													as gasto_FFF_id_moneda
									FROM Gastos";
		}

		function QueryCobros() 
		{
			return "SELECT 
									Factura.NumeroFactura 																					as cobro_FFF_id_cobro,
									Factura.FechaGeneracion 																				as cobro_FFF_fecha_creacion,
									Factura.CodigoFacturaBoleta 																		as cobro_FFF_documento,
									Factura.CodigoCliente 																					as cobro_FFF_codigo_cliente,
									IF(Factura.Moneda='S','1',IF(Factura.Moneda='E','3','2'))				as cobro_FFF_opc_moneda_total,
									IF(Factura.Moneda='S','1',IF(Factura.Moneda='E','3','2')) 			as cobro_FFF_id_moneda_monto,
									IF(Factura.Moneda='S','1',IF(Factura.Moneda='E','3','2')) 			as cobro_FFF_id_moneda,
									Factura.MontoBruto 																							as cobro_FFF_monto,
									Factura.MontoBruto 																						as cobro_FFF_monto_contrato, 
									Factura.MontoImpuesto 																					as cobro_FFF_impuesto,
									'FLAT FEE'																								as cobro_FFF_forma_cobro, 
									Factura.MontoNeto 																							as cobro_FFF_monto_subtotal,
									Factura.PorcentajeImpuesto 																			as cobro_FFF_porcentaje_impuesto,
									Periodo.FechaInicio 																						as cobro_FFF_fecha_ini,
									Periodo.FechaTermino 																						as cobro_FFF_fecha_fin,
									CONCAT(SUBSTRING(Factura.NumeroOrdenFact,1,4),'-0',SUBSTRING(Factura.NumeroOrdenFact,-3)) as cobro_FFF_codigo_asunto,
									Empleado.CodigoEmpleado as cobro_FFF_id_usuario
									FROM Factura
									LEFT JOIN Periodo ON Periodo.CodigoPeriodo = Factura.PeriodoFacturacionFija
									LEFT JOIN Empleado ON LOWER(TRIM(Empleado.Siglas)) = LOWER(TRIM(Factura.creadopor))";
		}
		function QueryFacturas()
		{
			return "SELECT 
									Factura.NumeroFactura 																					as factura_FFF_id_cobro,
									Factura.NumeroFactura 																					as factura_FFF_id_factura,
									Factura.CodigoFacturaBoleta																		as factura_FFF_numero,
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
		function QueryTarifas() 
		{ 
			return "SELECT
									CodigoTarifaCliente as id_tarifa
									,Descripcion as glosa_tarifa
									FROM TbTarifaCliente"; 
		}
		function QueryUsuariosTarifas() 
		{ 
			return "SELECT
									id_usuario_tarifa_LMT as id_usuario_tarifa
									, CodigoEmpleado AS id_usuario
									, if(moneda='D' ,'2',if(moneda = 'E','3',if(moneda = 'S','1','0'))) AS id_moneda
									, TarifaHora AS tarifa
									, CodigoTarifaCliente as id_tarifa
									FROM  `TbTarifaCategoria`
									Group by CodigoTarifaCliente, CodigoEmpleado, moneda
									Order by CodigoPeriodo DESC "; 
		}
		function QueryPagos()
		{
			return "SELECT 
								NumeroFactura 																			as documento_FFF_id_cobro,
								NumeroFactura 																			as factura_FFF_id_factura,
								FechaDePago																					as factura_FFF_fecha,
								FechaDePago																					as documento_FFF_fecha,
								NumeroDocumentoPago																	as documento_FFF_numero_doc,
								NumeroDocumentoPago																	as factura_FFF_nro_documento,
								MontoSoles																					as documento_FFF_monto_base,
								IF(MonedaPago='D','2',IF(MonedaPago='E','3','1'))		as documento_FFF_id_moneda,
								IF(MonedaPago='D','2',IF(MonedaPago='E','3','1'))		as factura_FFF_id_moneda,
								MontoPago																						as documento_FFF_monto,
								MontoPago																						as factura_FFF_monto,
								NombreBanco																					as factura_FFF_glosa_banco,
								CodigoCuentaBanco																		as factura_FFF_cuenta_banco,
								NombreBanco																					as documento_FFF_glosa_banco,
								CodigoCuentaBanco																		as documento_FFF_cuenta_banco,
								FechaModificacion																		as factura_FFF_fecha_modificacion,
								FechaModificacion																		as documento_FFF_fecha_modificacion,
								CodigoBancoCheque																		as documento_FFF_numero_cheque,
								CodigoBancoCheque																		as factura_FFF_nro_cheque,
								id_factura_pago_lemontech														as factura_FFF_id_factura_pago,
								id_factura_pago_lemontech 													as documento_FFF_id_documento
							FROM PagosRecibidos
							LEFT JOIN TbBancos ON TbBancos.CodigoBanco = PagosRecibidos.CodigoBanco";
		}
	}
?>
