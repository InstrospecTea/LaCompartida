<?
	class ConfMigracion 
	{
		function dbHost() { return 'localhost'; 		}
		function dbName() { return 'Payet_dbo'; 		}
		function dbUser() { return 'root'; 					}
		function dbPass() { return 'chantasio'; 		}
		function QueryCliente() 
		{ 
			return "SELECT 
								Cliente.CodigoCliente 																																			as cliente_FFF_codigo_cliente, 
								Cliente.NombreCliente																																				as cliente_FFF_glosa_cliente,
								Cliente.NombreCliente																																				as cliente_FFF_rsocial,
								CLiente.NombreCliente																																				as contrato_FFF_factura_razon_social,
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
								IF(Cliente.MonedaDefault='D','2',IF(Cliente.MonedaDefault='E','3','1')) 										as cliente_FFF_id_moneda, 
								IF(Cliente.MonedaDefault='D','2',IF(Cliente.MonedaDefault='E','3','1')) 										as contrato_FFF_id_moneda, 
								IF(Cliente.MonedaDefault='D','2',IF(Cliente.MonedaDefault='E','3','1')) 										as contrato_FFF_opc_moneda_total, 
								IF(Cliente.MonedaTarifaMensual='D','2',IF(Cliente.MonedaTarifaMensual='E','3','1')) 				as contrato_FFF_id_moneda_monto, 
								Cliente.TarifaHora 																																					as contrato_FFF_id_tarifa, 
								Cliente.TarifaMensual 																																			as contrato_FFF_monto, 
								Cliente.Attache 																																						as contrato_FFF_id_usuario_responsable, 
								Cliente.ContactoDeCobranza 																																	as contrato_FFF_contacto, 
								Cliente.FechaCreacion 																																			as contrato_FFF_fecha_creacion, 
								Cliente.FechaModificacion 																																	as contrato_FFF_fecha_modificacion, 
								IF(Cliente.FlagRetainer='S','Retainer','') 																									as contrato_FFF_forma_cobro, 
								Cliente.CodigoClienteAlterno 																																as cliente_FFF_codigo_cliente_secundario, 
								Cliente.attachesecundario 																																	as cliente_FFF_id_usuario_encargado, 
								GROUP_CONCAT( Titulo, Nombre, Telefono SEPARATOR '//' ) 																		as cliente_FFF_nombre_contacto  
							FROM Cliente 
							LEFT JOIN ContactosCliente ON Cliente.CodigoCliente = ContactosCliente.CodigoCliente 
							GROUP BY Cliente.CodigoCliente"; 
		}
		function QueryAsunto() { return ""; }
		function QueryUsuario() { return "SELECT 
																				Empleado.CodigoEmpleado 																				as usuario_FFF_id_usuario,
																				Empleado.Nombres 																								as usuario_FFF_nombre,
																				Empleado.ApellidoPaterno 																				as usuario_FFF_apellido1,
																				Empleado.ApellidoMaterno 																				as usuario_FFF_apellido2,
																				Empleado.Siglas 																								as usuario_FFF_username,
																				TbCategoriaEmpleados.DescripcionCategoria												as usuario_FFF_id_categoria_usuario,
																				CONCAT_WS(', ',Empleado.Direccion,Empleado.Provincia) 					as usuario_FFF_dir_calle,
																				Empleado.Departamento 																					as usuario_FFF_dir_depto,
																				Empleado.Distrito 																							as usuario_FFF_dir_comuna,
																				Empleado.Telefono1 																							as usuario_FFF_telefono1,
																				Empleado.Telefono1 																							as usuario_FFF_telefono2,
																				CONCAT(Empleado.DocumentoIdentidad,Empleado.DocIdentidadNumero) as usuario_FFF_rut,
																				Empleado.CorreoElectronico 																			as usuario_FFF_email,
																				IF(Empleado.Status='A','1','0') 																as usuario_FFF_activo,
																				Empleado.FechaModificacion 																			as usuario_FFF_fecha_modificacion,
																				Empleado.FechaCreacion 																					as usuario_FFF_fecha_creacion,
																				IF(Empleado.moneda='D','2','1') 																as usuario_FFF_id_moneda,
																				IF(Empleado.moneda='D','2','1') 																as usuario_FFF_id_moneda_costo
																			FROM Empleado
																			LEFT JOIN TbCategoriaEmpleados ON Empleado.Categoria = TbCategoriaEmpleados.CodigoCategoria 
																			AND Empleado.TipoEmpleado = TbCategoriaEmpleados.TipoEmpleado"; }
		function QueryHoras() { return ""; }
		function QueryGastos() { return ""; }
		function QueryCobros() { return ""; }
		function QueryFacturas() { return ""; }
	}
?>
