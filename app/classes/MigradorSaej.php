<?php

class MigradorSaej extends Migracion {

	var $sesion = null;
	var $etapa='nulo';


	var $forzar = 0;
	var $from = 0;
	var $size= 0;
	var $apagallaves = 0;
	var $enciendellaves= 0;
	function __construct($sesion) {
		$this->sesion = $sesion;
	}

	function QueriesModificacionesAntes() {
		$queries = array();

		$llavetrabajocobro = UtilesApp::ExisteLlaveForanea('trabajo.id_cobro', 'cobro.id_cobro', $this->sesion);
		if ($llavetrabajocobro)
			$queries[] = "ALTER TABLE `trabajo` DROP FOREIGN KEY  `$llavetrabajocobro` ;";

		$llavectacorrienteocobro = UtilesApp::ExisteLlaveForanea('cta_corriente.id_cobro', 'cobro.id_cobro', $this->sesion);
		if ($llavectacorrienteocobro)
			$queries[] = "ALTER TABLE `cta_corriente` DROP FOREIGN KEY  `$llavectacorrienteocobro` ;";

		if (!UtilesApp::ExisteCampo('id_estado_factura', 'cobro', $this->sesion))
			$queries[] = "ALTER TABLE `cobro` ADD `id_estado_factura` INT( 11 ) NULL ;";
		if (!UtilesApp::ExisteCampo('estado_real', 'cobro', $this->sesion))
			$queries[] = "ALTER TABLE `cobro` ADD  `estado_real` VARCHAR( 20 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL ;";
		if (!UtilesApp::ExisteCampo('factura_rut', 'cobro', $this->sesion))
			$queries[] = "ALTER TABLE `cobro` ADD  `factura_rut` VARCHAR( 20 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL ";
		if (!UtilesApp::ExisteCampo('factura_razon_social', 'cobro', $this->sesion))
			$queries[] = "ALTER TABLE `cobro`	ADD `factura_razon_social` VARCHAR( 60 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL ;";
		if (!UtilesApp::ExisteCampo('id_trabajo_lemontech', 'cobro', $this->sesion))
			$queries[] = "ALTER TABLE `cobro`	ADD `factura_razon_social` VARCHAR( 60 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL ;";
	 
			





		return $queries;
	}

	function QueriesModificacionesDespues() {
		$queries = array();
		$queries[] = "UPDATE trabajo LEFT JOIN cobro USING( id_cobro ) SET trabajo.id_cobro = NULL WHERE cobro.id_cobro IS NULL";
		if (!UtilesApp::ExisteLlaveForanea('trabajo.id_cobro', 'cobro.id_cobro', $this->sesion))
			$queries[] = "ALTER TABLE `trabajo` ADD FOREIGN KEY (  `id_cobro` ) REFERENCES `cobro` (`id_cobro`) ON DELETE SET NULL ON UPDATE CASCADE ;";
		if (!UtilesApp::ExisteLlaveForanea('cta_corriente.id_cobro', 'cobro.id_cobro', $this->sesion))
			$queries[] = "ALTER TABLE `cta_corriente` ADD FOREIGN KEY (  `id_cobro` ) REFERENCES  `cobro` (`id_cobro`) ON DELETE SET NULL ON UPDATE CASCADE ;";



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
				'datos' => array('CORPORACION RADIAL DEL PERU S.A.C.',
					'CUSA S.A.C.',
					'DUBROVNIK S.A.C.',
					'GHL PERU SOCIEDAD ANONIMA CERRADA',
					'GRUPO 5 SAC',
					'LUBRICANTES DEL SUR S.A.C.',
					'MADERERA BOZOVICH S.A.C.',
					'MINERA SULLIDEN SHAHUINDO S.A.C.',
					'NUEVAS RAICES S.A.C.',
					'PERU MINERALS S.A.C.',
					'PERUVIAN SEA FOOD S.A.',
					'PRIME FISHMEAL S.A.C.',
					'QUINTO S.A.C.',
					'SALAZAR PRADO CARLOS ALBERTO',
					'SERVICIOS DE FRANQUICIA PARDO\'S S.A.C.',
					'STRACON S.A.C.',
					'SUMA INVERSIONES INMOBILIARIAS SOCIEDAD ANONIMA CERRADA',
					'TELEFONICA DEL PERU S.A.A.',
					'TURISMO EL DORAL S.A.C.',
					'URBI PROPIEDADES S.A.')),
			'prm_area_proyecto' => array(
				'campo_glosa' => 'glosa',
				'campo_id' => 'id_area_proyecto',
				'datos' => array('Corporativo', 'Finanzas', 'Laboral', 'Mercado de Valores', 'Procesal', 'Regulatorio', 'Tributario'))
		);
	}

	function QueryUsuario() {
		return "SELECT			if(length(CodigoEmpleado)=6,
								2000+1*CodigoEmpleado 	,
								1000+1*CodigoEmpleado 	)					as usuario_FFF_id_usuario,
							CodigoEmpleado as								usuario_FFF_id_usuario_antiguo,
							Empleado.Nombres 								as usuario_FFF_nombre,
							Empleado.ApellidoPaterno							as usuario_FFF_apellido1,
							Empleado.ApellidoMaterno							as usuario_FFF_apellido2,
							Empleado.Siglas 								as usuario_FFF_username,
							TbCategoriaEmpleados.DescripcionCategoria			as usuario_FFF_id_categoria_usuario,
							CONCAT_WS(', ',Empleado.Direccion,Empleado.Provincia) 	as usuario_FFF_dir_calle,
							Empleado.Departamento 							as usuario_FFF_dir_depto,
							Empleado.Telefono1 								as usuario_FFF_telefono1,
							Empleado.Telefono1 								as usuario_FFF_telefono2,
							Empleado.DocIdentidadNumero						as usuario_FFF_rut,
							Empleado.CorreoElectronico 						as usuario_FFF_email,
							IF(Empleado.Status='A','1','0') 						as usuario_FFF_activo,
							Empleado.FechaCreacion 							as usuario_FFF_fecha_creacion,
							IF(Empleado.moneda='D','2','1') 						as usuario_FFF_id_moneda_costo
							FROM " . DBORIGEN . ".Empleado
							LEFT JOIN " . DBORIGEN . ".TbCategoriaEmpleados ON Empleado.Categoria = TbCategoriaEmpleados.CodigoCategoria
							AND Empleado.TipoEmpleado = TbCategoriaEmpleados.TipoEmpleado 
							 
							";
		// se omite el usuario dummy
	}

	function QueryCliente() {
		return "SELECT 1*Cliente.CodigoCliente as cliente_FFF_id_cliente, 
								Cliente.CodigoCliente 																as cliente_FFF_codigo_cliente,                
								Cliente.NombreCliente																as cliente_FFF_glosa_cliente,
								Cliente.NombreCliente																as cliente_FFF_rsocial,
								Cliente.NombreCliente																as contrato_FFF_factura_razon_social,
								IF(Cliente.Status='A','1','0')																as cliente_FFF_activo, 
								IF(Cliente.Status='A','SI','NO') 															as contrato_FFF_activo, 
								CONCAT(Cliente.Direccion1,' // ', Cliente.Direccion2) 											as cliente_FFF_dir_calle,
								CONCAT(Cliente.Direccion1,' // ', Cliente.Direccion2) 											as contrato_FFF_direccion_contacto,
								CONCAT(Cliente.Direccion1,' // ', Cliente.Direccion2) 											as contrato_FFF_factura_direccion,
								CONCAT_WS(', ',Cliente.Telefono1, Cliente.Telefono2, Cliente.Telefono3, Cliente.Telefono4)				as cliente_FFF_fono_contacto,
								CONCAT_WS(', ',Cliente.Telefono1, Cliente.Telefono2, Cliente.Telefono3, Cliente.Telefono4)				as contrato_FFF_fono_contacto,
								CONCAT_WS(', ',Cliente.Telefono1, Cliente.Telefono2, Cliente.Telefono3, Cliente.Telefono4)				as contrato_FFF_factura_telefono,
								CONCAT(Cliente.DocumentoIdentidad, Cliente.DocIdentidadNumero) 								as cliente_FFF_rut, 
								CONCAT(Cliente.DocumentoIdentidad, Cliente.DocIdentidadNumero) 								as contrato_FFF_rut,
								Cliente.Actividad 																	as cliente_FFF_giro, 
								Cliente.Actividad 																	as contrato_FFF_factura_giro,
								IF(Cliente.CodigoImpuesto='I','1','0') 														as contrato_FFF_usa_impuesto_separado,
								IF(Cliente.CodigoImpuesto='I','1','0') 														as contrato_FFF_usa_impuesto_gastos, 
								IF(Cliente.MonedaDefault='D','2',IF(Cliente.MonedaDefault='E','3','1')) 								as cliente_FFF_id_moneda, 
								IF(Cliente.MonedaDefault='D','2',IF(Cliente.MonedaDefault='E','3','1')) 								as contrato_FFF_id_moneda, 
								IF(Cliente.MonedaDefault='D','2',IF(Cliente.MonedaDefault='E','3','1')) 								as contrato_FFF_opc_moneda_total, 
								IF(Cliente.MonedaTarifaMensual='D','2',IF(Cliente.MonedaTarifaMensual='E','3','1'))						as contrato_FFF_id_moneda_monto,
								Cliente.TarifaHora 																	as contrato_FFF_id_tarifa, 
								Cliente.TarifaMensual																	as contrato_FFF_monto, 
								IFNULL(if(length(Cliente.Attache)=6,2000+1*Cliente.Attache 	,1000+1*Cliente.Attache 	),1) 				as contrato_FFF_id_usuario_responsable,
								Cliente.ContactoDeCobranza 															as contrato_FFF_contacto, 
								Cliente.FechaCreacion 																as contrato_FFF_fecha_creacion, 
								Cliente.FechaModificacion 																as contrato_FFF_fecha_modificacion, 
								IF(Cliente.FlagRetainer='S','Retainer','TASA') 												as contrato_FFF_forma_cobro, 
								Cliente.CodigoClienteAlterno 															as cliente_FFF_codigo_cliente_secundario, 
								if(length(Cliente.attachesecundario)=6,2000+1*Cliente.attachesecundario	,1000+1*Cliente.attachesecundario 	)  	as cliente_FFF_id_usuario_encargado, 
								if(length(Cliente.attachesecundario)=6,2000+1*Cliente.attachesecundario	,1000+1*Cliente.attachesecundario 	)  	as contrato_FFF_id_usuario_secundario, 
								GROUP_CONCAT( Titulo, Nombre, Telefono SEPARATOR '//' ) 										as cliente_FFF_nombre_contacto, 
								'1'																					as contrato_FFF_separar_liquidaciones 
							FROM " . DBORIGEN . ".Cliente
							LEFT JOIN " . DBORIGEN . ".ContactosCliente ON Cliente.CodigoCliente = ContactosCliente.CodigoCliente
							GROUP BY Cliente.CodigoCliente";
	}

	function QueryAsunto($extra = '') {
		$QueryAsunto="";
		$QueryAsunto.= "SELECT
					if(length( Cliente.Cobrador )=6,
						2000+1* Cliente.Cobrador   	,
						1000+1* Cliente.Cobrador  	)											as asunto_FFF_id_cobrador,
					OrdenFacturacion.NumeroOrdenFact												as asunto_FFF_codigo_asunto,
					OrdenFacturacion.NumeroOrdenFact												as asunto_FFF_codigo_asunto_secundario,
					OrdenFacturacion.CodigoCliente													as asunto_FFF_codigo_cliente,
					OrdenFacturacion.CodigoCliente													as contrato_FFF_codigo_cliente,
					CONCAT_WS(' ',ContactosCliente.Titulo, ContactosCliente.Nombre)						as asunto_FFF_contacto,
					CONCAT_WS(' ',Titulo, Nombre)													as contrato_FFF_contacto,
								if(length( OrdenFacturacion.CodigoAbogadoResponsable)=6,
									2000+1* OrdenFacturacion.CodigoAbogadoResponsable  	,
									1000+1* OrdenFacturacion.CodigoAbogadoResponsable  	) 		as asunto_FFF_id_encargado,
								if(length( OrdenFacturacion.CodigoAbogadoResponsable)=6,
									2000+1* OrdenFacturacion.CodigoAbogadoResponsable  	,
									1000+1* OrdenFacturacion.CodigoAbogadoResponsable  	) 		as contrato_FFF_id_usuario_secundario,
								if(length( OrdenFacturacion.CodigoAbogadoResponsable)=6,
									2000+1* OrdenFacturacion.CodigoAbogadoResponsable  	,
									1000+1* OrdenFacturacion.CodigoAbogadoResponsable  	) 		as asunto_FFF_id_usuario,
								if(length(OrdenFacturacion.Attache )=6,
									2000+1*OrdenFacturacion.Attache 	,
									1000+1*OrdenFacturacion.Attache  	)						as contrato_FFF_id_usuario_responsable,
								IF(OrdenFacturacion.FlagFacturable='S','1','0') 									as asunto_FFF_cobrable,
								IF(OrdenFacturacion.HojaTiemposFlag='O','1','0')									as asunto_FFF_activo,
								IF(OrdenFacturacion.HojaTiemposFlag='O','SI','NO')								as contrato_FFF_activo,
								OrdenFacturacion.Asunto 													as asunto_FFF_glosa_asunto,
								IF( OrdenFacturacion.Moneda = 'S', '1', IF( OrdenFacturacion.Moneda = 'E', '3', '2' ) )		as asunto_FFF_id_moneda,
								IF( OrdenFacturacion.Moneda = 'S', '1', IF( OrdenFacturacion.Moneda = 'E', '3', '2' ) )		as contrato_FFF_id_moneda,
								IF( OrdenFacturacion.Moneda = 'S', '1', IF( OrdenFacturacion.Moneda = 'E', '3', '2' ) )		as contrato_FFF_id_moneda_monto,
								IF( OrdenFacturacion.Moneda = 'S', '1', IF( OrdenFacturacion.Moneda = 'E', '3', '2' ) )		as contrato_FFF_opc_moneda_total,
								OrdenFacturacion.TarifaHora												as contrato_FFF_id_tarifa,
								OrdenFacturacion.FechaModificacion											as asunto_FFF_fecha_modificacion,
								OrdenFacturacion.FechaCreacion												as asunto_FFF_fecha_creacion,
								CONCAT_WS(', ',Cliente.Telefono1, Cliente.Telefono2, Cliente.Telefono3, Cliente.Telefono4) 	as contrato_FFF_factura_telefono,
								CONCAT_WS(', ',Cliente.Telefono1, Cliente.Telefono2, Cliente.Telefono3, Cliente.Telefono4) 	as contrato_FFF_fono_contacto,
								OrdenFacturacion.HorasTope												as asunto_FFF_limite_hh,
								IF(Cliente.CodigoImpuesto='I','1','0')											as contrato_FFF_usa_impuesto_separado,
								IF(Cliente.CodigoImpuesto='I','1','0')											as contrato_FFF_usa_impuesto_gastos,
								CONCAT(Cliente.Direccion1,' // ', Cliente.Direccion2)								as contrato_FFF_factura_direccion,
								OrdenFacturacionHistoria.Descripcion											as asunto_FFF_descripcion_asunto,
								CONCAT(Cliente.DocumentoIdentidad,Cliente.DocIdentidadNumero) 					as asunto_FFF_rut, 
								CONCAT(Cliente.DocumentoIdentidad,Cliente.DocIdentidadNumero) 					as contrato_FFF_rut,
								Cliente.FechaCreacion													as contrato_FFF_fecha_creacion,
								Cliente.FechaModificacion													as contrato_FFF_fecha_modificacion,
								Cliente.Actividad														as asunto_FFF_giro,
								Cliente.Actividad														as contrato_FFF_factura_giro,
								Cliente.NombreCliente													as asunto_FFF_razon_social,
								Cliente.NombreCliente													as contrato_FFF_factura_razon_social
								,IF(OrdenFacturacion.TipoFactExtraordinaria='A' AND  (OrdenFacturacion.HonorarioPactado>0 OR prop.hf_valorventa>0 ),'FLAT FEE','TASA')	as contrato_FFF_forma_cobro
								,IF(OrdenFacturacion.HonorarioPactado>0, OrdenFacturacion.HonorarioPactado,prop.hf_valorventa)									as contrato_FFF_monto,
								'1'																										as contrato_FFF_separar_liquidaciones,
								prop.numeropropuesta		as contrato_FFF_id_contrato,
								prop.numeropropuesta		as contrato_FFF_numeropropuesta,
								prop.numeropropuesta		as asunto_FFF_numeropropuesta,
								prop.codigopropuesta			as contrato_FFF_codigopropuesta,
								prop.codigopropuesta			as asunto_FFF_codigopropuesta

							FROM  " . DBORIGEN . ".OrdenFacturacion left  join  " . DBORIGEN . ".propuesta prop using (numeropropuesta)
							LEFT JOIN   " . DBORIGEN . ".Cliente ON OrdenFacturacion.CodigoCliente = Cliente.CodigoCliente
							LEFT JOIN  " . DBORIGEN . ".OrdenFacturacionHistoria ON OrdenFacturacion.NumeroOrdenFact = OrdenFacturacionHistoria.NumeroOrdenFact
							LEFT JOIN  " . DBORIGEN . ".ContactosCliente ON ContactosCliente.CodigoContactoCliente = OrdenFacturacion.CodigoContactoCliente
								$extra ";
		// LOS RETAINER LOS CORRIJO DESPUES DE INSERTAR ESTOS DATOS
		if($this->size >0) $QueryAsunto.="limit ".intval($this->from).",".intval($this->size);

		return $QueryAsunto;
	}

	function QueryHoras($extra="") {
		$QueryHoras="";
		$QueryHoras= "SELECT
				if(length(ifnull(hta.CodigoEmpleadoFacturable, htd.CodigoEmpleado))=6,
				2000+1*ifnull(hta.CodigoEmpleadoFacturable, htd.CodigoEmpleado),
				1000+1*ifnull(hta.CodigoEmpleadoFacturable, htd.CodigoEmpleado))					as id_usuario,
								
			 					hta.FechaFacturable										as fecha
		 					,	hta.HoraInicioFacturable									as hora_inicio
		 					,hta.NumeroFactura											as id_cobro ,
						 		SEC_TO_TIME(htd.Tiempo*60)								as duracion,
                IF(hta.Status='C','00:00:00',SEC_TO_TIME(hta.TiempoFacturable*60))							as duracion_cobrada,
							 	IF(hta.Status = 'P','1','0')								as cobrable,
								 IFNULL(hta.AsuntoLargoFacturable, htd.AsuntoLargo) 				as descripcion,
								 IFNULL(hta.FechaCreacion, htd.FechaCreacion) 					as fecha_creacion,
								 IFNULL(hta.FechaModificacion, htd.FechaModificacion) 				as fecha_modificacion
								   ,IF(hta.TarifaFacturable IS NOT NULL,hta.TarifaFacturable ,htd.Tarifa) 	as tarifa_hh
								
                 ,if(ifnull(Factura.Moneda, hta.moneda)='D',2,if( ifnull(Factura.Moneda, hta.moneda)='E',3,1  ))			as id_moneda
							 	,IF(hta.NumeroOrdenFacturacionFact IS NULL,  htd.NumeroOrdenFacturacion,hta.NumeroOrdenFacturacionFact) as codigo_asunto
								 ,hta.id_trabajo_lemontech  as id_trabajo 			
                
               
								FROM     " . DBORIGEN . ".HojaTiempoajustado hta
								LEFT JOIN " . DBORIGEN . ".Hojatiemporelacion htr ON htr.hojatiempoajustadoid=hta.hojatiempoajustadoid
								LEFT JOIN " . DBORIGEN . ".HojaTiempoDetalle htd ON htd.hojatiempoid = htr.hojatiempoid  and hta.NumeroOrdenFacturacionFact=htd.NumeroOrdenFacturacion
								LEFT JOIN " . DBORIGEN . ".Factura ON Factura.NumeroFactura = hta.NumeroFactura
								 $extra
								";
		if($this->size >0) $QueryHoras.="limit ".intval($this->from).",".intval($this->size);

		return $QueryHoras;
	}

	function QueryGastos($extra="") {
		$QueryGastos="";
		$QueryGastos.= "SELECT
						   	if(CodigoGasto is null, null, 	1*CodigoGasto)					as gasto_FFF_id_movimiento,
					  			rucproveedor												as gasto_FFF_proveedor_ruc,
						  			razonsocialproveedor									as gasto_FFF_proveedor_rsocial,
									CodigoGasto												as gasto_FFF_numero_documento,
									Gastos.FechaCreacion									as gasto_FFF_fecha_creacion,
					 				Gastos.FechaModificacion 								as gasto_FFF_fecha_modificacion,
								 	Gastos.NumeroOrdenFact									as gasto_FFF_codigo_asunto,
									Gastos.FechaGasto 										as gasto_FFF_fecha,
									Gastos.numerodocumentoreferencia						as gasto_FFF_numero_documento,
									
									1*Gastos.TipoGasto										as gasto_FFF_id_cta_corriente_tipo,
									if(length(Empleado.CodigoEmpleado )=6,
									2000+1*Empleado.CodigoEmpleado  ,
									1000+1*Empleado.CodigoEmpleado  )						as gasto_FFF_id_usuario,
									if(length(Gastos.CodigoEmpleado 	)=6,
									2000+1*Gastos.CodigoEmpleado 	 ,
									1000+1*Gastos.CodigoEmpleado 	 )					as gasto_FFF_id_usuario_orden,
									Gastos.DescripcionGasto								as gasto_FFF_descripcion,
									if(TipoGasto!='A', Gastos.MontoSoles,0)				as gasto_FFF_egreso,
									if(TipoGasto='A', Gastos.MontoSoles,0)				as gasto_FFF_ingreso,
									Gastos.MontoSoles									as gasto_FFF_monto_cobrable,
									Gastos.CodigoCliente 								as gasto_FFF_codigo_cliente,
									IF(Gastos.flagfacturable='S','1','0')							as gasto_FFF_cobrable,
									IF(Gastos.moneda='S','1',IF(Gastos.moneda='E','3','2')) 			as gasto_FFF_id_moneda,
									Factura.CodigoFacturaBoleta								as gasto_FFF_codigo_factura_gasto,
									Factura.FechaImpresion									as gasto_FFF_fecha_factura 
									FROM  " . DBORIGEN . ".Gastos
									LEFT JOIN " . DBORIGEN . ".Empleado ON TRIM(Empleado.Siglas) = TRIM(Gastos.Creadopor)
									LEFT JOIN " . DBORIGEN . ".Factura ON Gastos.NumeroFactura = Factura.NumeroFactura
										$extra ";
		if($this->size >0) $QueryGastos.="limit ".intval($this->from).",".intval($this->size);
		return $QueryGastos;
	}

	function QueryMonedaHistorial() {
		return "SELECT * FROM " . DBORIGEN . ".TipoDeCambio";
	}

	function QueryCobros($extra) {
		$QueryCobros="";
		$QueryCobros.= "SELECT 
									Factura.NumeroFactura									as cobro_FFF_id_cobro,
									Factura.FechaGeneracion									as cobro_FFF_fecha_creacion,
									Factura.CodigoFacturaBoleta								as cobro_FFF_documento,
									IF(Factura.Status='A','5',IF(Factura.Status='C','2','1'))				as cobro_FFF_id_estado_factura,
									Factura.CodigoCliente										as cobro_FFF_codigo_cliente,
									IF(Factura.Moneda='S','1',IF(Factura.Moneda='E','3','2'))			as cobro_FFF_opc_moneda_total,
									Factura.Observacion										as cobro_FFF_observaciones,
									IF(Factura.Moneda='S','1',IF(Factura.Moneda='E','3','2'))			as cobro_FFF_id_moneda_monto,
									IF(Factura.Moneda='S','1',IF(Factura.Moneda='E','3','2'))			as cobro_FFF_id_moneda,
									Factura.MontoNeto										as cobro_FFF_monto,
									Factura.MontoBruto										as cobro_FFF_monto_contrato,
									Factura.MontoImpuesto									as cobro_FFF_impuesto,
									'FLAT FEE'												as cobro_FFF_forma_cobro,
									Factura.MontoBruto										as cobro_FFF_monto_subtotal,
									IF(Factura.PorcentajeImpuesto='19.00','19',
										IF(Factura.PorcentajeImpuesto='0.19','19',
											IF(Factura.PorcentajeImpuesto='0.18','18','0')))			as cobro_FFF_porcentaje_impuesto,
									Periodo.FechaInicio										as cobro_FFF_fecha_ini,
									Factura.FechaGeneracion									as cobro_FFF_fecha_emision,
									Periodo.FechaTermino 									as cobro_FFF_fecha_fin,
									IF(Factura.Status='A','INCOBRABLE',IF(Factura.Status='C','PAGADO',
										IF(Factura.Status='I','ENVIADO AL CLIENTE','EMITIDO')))		as cobro_FFF_estado_real,
									CONCAT(SUBSTRING(Factura.NumeroOrdenFact,1,4),'-0',
									SUBSTRING(Factura.NumeroOrdenFact,-3))						as cobro_FFF_codigo_asunto,
									Factura.NombreClienteFacturacion							as cobro_FFF_factura_razon_social,
									Factura.DocIdentidadNumeroFacturacion						as cobro_FFF_factura_rut,
									if(length(Empleado.CodigoEmpleado )=6,
										2000+1*Empleado.CodigoEmpleado  ,
										1000+1*Empleado.CodigoEmpleado  )						as cobro_FFF_id_usuario
								FROM  " . DBORIGEN . ".Factura
								LEFT JOIN  " . DBORIGEN . ".Periodo ON Periodo.CodigoPeriodo = Factura.PeriodoFacturacionFija
								LEFT JOIN  " . DBORIGEN . ".Empleado ON LOWER(TRIM(Empleado.Siglas)) = LOWER(TRIM(Factura.creadopor))
																	$extra ";
		if($this->size >0) $QueryCobros.="limit ".intval($this->from).",".intval($this->size);
		$this->sesion->debug($QueryCobros);
		return $QueryCobros;
	}

	function QueryFacturas() {
		return "SELECT 
									Factura.NumeroFactura 									as factura_FFF_id_cobro,
									Factura.NumeroFactura 									as factura_FFF_id_factura,
									Factura.NumeroFactura									as factura_FFF_numero,
									Factura.FechaGeneracion 									as factura_FFF_fecha_creacion,
									Factura.CodigoCliente 									as cobro_FFF_codigo_cliente,
									IF(Factura.Moneda='S','1',IF(Factura.Moneda='E','3','2'))			as factura_FFF_id_moneda,
									Factura.MontoBruto 										as factura_FFF_total,
									Factura.MontoImpuesto 									as factura_FFF_iva,
									Factura.MontoNeto 										as factura_FFF_honorarios,
									Factura.PorcentajeImpuesto 								as factura_FFF_porcentaje_impuesto
								FROM  " . DBORIGEN . ".Factura
								LEFT JOIN  " . DBORIGEN . ".Periodo ON Periodo.CodigoPeriodo = Factura.PeriodoFacturacionFija
								WHERE Factura.CodigoFacturaBoleta IS NOT NULL";
	}

	function QueryTarifas() {
		return "SELECT 
									CodigoTarifaCliente as id_tarifa 
									,Descripcion as glosa_tarifa 
									,'1' as guardado 
									FROM  " . DBORIGEN . ".TbTarifaCliente";
	}

	function QueryUsuariosTarifas() {

		
		return "SELECT
								T1.id_usuario_tarifa_LMT as id_usuario_tarifa 
								, if(length(T1.CodigoEmpleado )=6,2000+1*T1.CodigoEmpleado  ,1000+1*T1.CodigoEmpleado  ) AS id_usuario 
								, if(T1.moneda='D' ,'2',if(T1.moneda = 'E','3',if(T1.moneda = 'S','1','0'))) AS id_moneda 
								, T1.TarifaHora AS tarifa 
								, T1.CodigoTarifaCliente as id_tarifa 
								FROM  " . DBORIGEN . ".TbTarifaCategoria as T1
								WHERE T1.CodigoPeriodo = 
									( SELECT MAX(T2.CodigoPeriodo) 
										FROM  " . DBORIGEN . ".TbTarifaCategoria as T2
										WHERE T2.CodigoEmpleado = T1.CodigoEmpleado 
											AND T2.CodigoTarifaCliente = T1.CodigoTarifaCliente 
											AND T2.moneda = T1.moneda )";
	}

	function QueryPagos() {
		return "SELECT 
								P.NumeroFactura																				as documento_FFF_id_cobro,
								P.NumeroFactura																				as factura_FFF_id_factura,
								P.FechaDePago																					as factura_FFF_fecha,
								P.FechaDePago																					as documento_FFF_fecha,
								P.NumeroDocumentoPago																			as documento_FFF_numero_doc,
								P.NumeroDocumentoPago																			as factura_FFF_nro_documento,
								P.MontoSoles																					as documento_FFF_monto_base,
								IF(MonedaPago='D','2',IF(MonedaPago='E','3','1'))															as documento_FFF_id_moneda,
								IF(MonedaPago='D','2',IF(MonedaPago='E','3','1'))															as factura_FFF_id_moneda,
								P.MontoPago																					as documento_FFF_monto,
								P.MontoPago																					as factura_FFF_monto,
								IF(Factura.Moneda='D','2',IF(Factura.Moneda='E','3','1'))													as factura_FFF_id_moneda_cobro,
								IF(Factura.Moneda='D','2',IF(Factura.Moneda='E','3','1'))													as documento_FFF_id_moneda_cobro,
								IF( Factura.Moneda = P.MonedaPago,
										MontoPago, IF( Factura.Moneda = 'S', MontoSoles,
											IF( Factura.Moneda = 'D', MontoDolares, MontoPago * P.TipoCambioPago / P.TipoCambioFacturacion ) ) )		as factura_FFF_monto_cobro,
								IF( Factura.Moneda = P.MonedaPago,
										MontoPago, IF( Factura.Moneda = 'S', MontoSoles,
											IF( Factura.Moneda = 'D', MontoDolares, MontoPago * P.TipoCambioPago / P.TipoCambioFacturacion ) ) )		as documento_FFF_monto_cobro,
								NombreBanco																					as factura_FFF_glosa_banco,
								P.CodigoCuentaBanco																				as factura_FFF_cuenta_banco,
								NombreBanco																					as documento_FFF_glosa_banco,
								P.CodigoCuentaBanco																				as documento_FFF_cuenta_banco,
								P.FechaModificacion																				as factura_FFF_fecha_modificacion,
								P.FechaModificacion																				as documento_FFF_fecha_modificacion,
								P.CodigoBancoCheque																			as documento_FFF_numero_cheque,
								P.CodigoBancoCheque																			as factura_FFF_nro_cheque,
								P.id_factura_pago_lemontech																		as factura_FFF_id_factura_pago,
								P.id_factura_pago_lemontech + 100000																as documento_FFF_id_documento
							FROM  " . DBORIGEN . ".PagosRecibidos P
							LEFT JOIN  " . DBORIGEN . ".Factura ON Factura.NumeroFactura = P.NumeroFactura
							LEFT JOIN " . DBORIGEN . ".TbBancos ON TbBancos.CodigoBanco = P.CodigoBanco";
	}

	/**
	 *  @param bool $confirmacion  solamente encender la primera vez que se corre el script en un proceso de migración
	 */
	function QueryCero($confirmacion = false) { // Esto significa que no entra al ciclo, a menos que 1 sea igual a cero en cuyo caso algo debe estar realmente mal y estemos en un universo paralelo.

		 
		
		if ($confirmacion == false)
			return;
		$queryprevia1 = $this->sesion->pdodbh->exec("CREATE TABLE IF NOT EXISTS `log_migracion` (
  `id_migracion` int(2) NOT NULL AUTO_INCREMENT,
  `etapa_migracion` varchar(32) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `estado` int(1) not null default '0',
  `id_usuario` int(6)  null ,
  PRIMARY KEY (`id_migracion`),
  UNIQUE KEY `etapa_migracion` (`etapa_migracion`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Registra las etapas de una migración hecha con migracion_scr' AUTO_INCREMENT=1 ;");


		// Asuntos Dummy

	 
		if (!UtilesApp::ExisteIndex('NumeroOrdenFact', DBORIGEN . ".OrdenFacturacion", $this->sesion))
			$this->sesion->pdodbh->exec("ALTER TABLE OrdenFacturacion ADD INDEX (NumeroOrdenFact );");
		if (!UtilesApp::ExisteIndex('NumeroOrdenFact', DBORIGEN . ".Gastos", $this->sesion))
			$this->sesion->pdodbh->exec("ALTER TABLE  `Gastos` ADD INDEX (  `NumeroOrdenFact` );");
		if (!UtilesApp::ExisteIndex('NumeroOrdenFacturacionFact', DBORIGEN . ".HojaTiempoajustado", $this->sesion))
			$this->sesion->pdodbh->exec("ALTER TABLE  `HojaTiempoajustado` ADD INDEX (  `NumeroOrdenFacturacionFact` );");
		if (!UtilesApp::ExisteIndex('NumeroOrdenFact', DBORIGEN . ".Factura", $this->sesion))
			$this->sesion->pdodbh->exec("ALTER TABLE  Factura ADD INDEX (NumeroOrdenFact);");
		if (!UtilesApp::ExisteIndex('NumeroOrdenFacturacion', DBORIGEN . ".HojaTiempoDetalle", $this->sesion))
			$this->sesion->pdodbh->exec("ALTER TABLE  `HojaTiempoDetalle` ADD INDEX (  `NumeroOrdenFacturacion` );");

		







		if (!UtilesApp::ExisteCampo('id_usuario_antiguo', 'usuario', $this->sesion)) {
			$queryusuarioantiguo = "ALTER TABLE  `usuario` ADD  `id_usuario_antiguo` VARCHAR( 32 ) NULL ,  ADD UNIQUE (  `id_usuario_antiguo`  )";

			mysql_query($queryusuarioantiguo, $this->sesion->dbh);
		}
	}

	/**
	 *
	 * @param no recibe nada, sólo verifica y completa las tablas
	 * @return no devuelve nada, pero la tabla queda consistente
	 */
	function QueryPreviaTarifasUsuario() {
		if (!UtilesApp::ExisteIndex('PRIMARY', DBORIGEN . ".TbTarifaCategoria", $this->sesion)) 		$previatarifasusuarios = "ALTER TABLE  " . DBORIGEN . ".TbTarifaCategoria ADD  `id_usuario_tarifa_LMT` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;";
			$previatarifasusuarios = "update " . DBORIGEN . ".TbTarifaCategoria tbc left join " . DBORIGEN . ".Empleado emp on tbc.codigoempleado=emp.codigoempleado  set  tbc.`CodigoCategoria`= emp.categoria where  tbc.`CodigoCategoria` is null;";
			
			$previatarifasusuarios="truncate table usuario_tarifa";
			$previatarifasusuarios = $this->sesion->pdodbh->exec($previatarifasusuarios);


	}


	/**
	 *
	 * @param int $forzar solamente ejecuta la serie de queries si forzar es  1
	 * @return type
	 */
	function QueryPreviaUsuario($forzar = 0) {


		/** Usuarios Dummy
		  $querypreviausuarios = $this->sesion->pdodbh->exec("SELECT distinct id_usuario FROM " . DBORIGEN . ".HojaTiempoajustado
		  where id_usuario not in (select id_usuario from " . DBORIGEN . ".Empleado)
		  union
		  SELECT distinct id_usuario FROM " . DBORIGEN . ".HojaTiempoDetalle
		  where id_usuario not in (select id_usuario from " . DBORIGEN . ".Empleado)
		  union
		  SELECT distinct id_usuario FROM " . DBORIGEN . ".Gastos
		  where id_usuario not in (select id_usuario from " . DBORIGEN . ".Empleado)");


		  $queryprevia2 = $this->sesion->pdodbh->exec("INSERT INTO usuario  (`id_usuario`, `rut`,  `username`,   `nombre` )
		  SELECT distinct ut.id_usuario,
		  1000+ut.id_usuario as rut,
		  1000+ut.id_usuario as username,
		  concat('dummy ',ut.id_usuario) as nombre
		  FROM  usuario_temporal ut left join usuario us on ut.id_usuario=us.id_usuario
		  where us.id_usuario is null and ut.id_usuario is not null;");* */
		$queryprevia2 = $this->sesion->pdodbh->exec("REPLACE  INTO `prm_categoria_usuario` (`id_categoria_usuario`, `glosa_categoria`, `id_categoria_lemontech`, `orden`) VALUES
  (1, 'Socio', 1, 1),
  (2, 'Asociado Senior', 2, 2),
  (3, 'Asociado Junior', 3, 3),
  (4, 'Procurador', 4, 4),
  (5, 'Administración', 5, 5),
  (6, 'Asociado', 3, 0),
  (7, 'Administrativo', 5, 0),
  (8, 'Asistente', 5, 0),
  (9, 'Practicante', 5, 0);");
		if ($forzar != 1)
			return;
		$querypreviausuario = "delete from usuario where rut!='99511620' and id_usuario!=" . $this->sesion->usuario->fields['id_usuario'] . ";";
		if (!UtilesApp::ExisteCampo('codigo_usuario_antiguo', 'usuario', $this->sesion))
			$querypreviausuario.="ALTER TABLE  usuario  ADD  `id_usuario_antiguo` varchar( 16 ) NULL;";
		if (!UtilesApp::ExisteCampo('username_antiguo', 'usuario', $this->sesion))
			$querypreviausuario.="ALTER TABLE  usuario  ADD  username_antiguo varchar( 16 ) NULL;";
		if (!UtilesApp::ExisteCampo('id_usuario', DBORIGEN . '.Empleado', $this->sesion))
			$querypreviausuario.="ALTER TABLE  " . DBORIGEN . ".Empleado ADD  `id_usuario` INT( 5 ) NULL;";
		if (!UtilesApp::ExisteCampo('username', DBORIGEN . '.Empleado', $this->sesion))
			$querypreviausuario.="ALTER TABLE   " . DBORIGEN . ".Empleado ADD    `username` VARCHAR( 4 ) NULL;";
		if (!UtilesApp::ExisteIndex('id_usuario', DBORIGEN . '.Empleado', $this->sesion))
			$querypreviausuario.="ALTER TABLE    " . DBORIGEN . ".Empleado ADD   UNIQUE (`id_usuario` );";
		if (!UtilesApp::ExisteIndex('username', DBORIGEN . '.Empleado', $this->sesion))
			$querypreviausuario.="ALTER TABLE   " . DBORIGEN . ".Empleado  ADD  UNIQUE (`username`);";

		$querypreviausuario.="update `Empleado` set id_usuario=if(length(  `CodigoEmpleado` )=6,2000+1*  `CodigoEmpleado`  	,1000+1* `CodigoEmpleado`	) ";

		if (!UtilesApp::ExisteCampo('id_usuario', DBORIGEN . '.HojaTiempoajustado', $this->sesion))
			$querypreviausuario.="alter table HojaTiempoajustado add id_usuario INT(11) NULL, ADD index (id_usuario);";
		if (!UtilesApp::ExisteCampo('id_usuario', DBORIGEN . '.HojaTiempoDetalle', $this->sesion))
			$querypreviausuario.="alter table HojaTiempoDetalle add id_usuario INT(11) NULL, ADD index (id_usuario);";
		if (!UtilesApp::ExisteCampo('id_usuario', DBORIGEN . '.Gastos', $this->sesion))
			$querypreviausuario.="alter table Gastos add id_usuario INT(11) NULL, ADD index (id_usuario);";
		if (!UtilesApp::ExisteCampo('id_usuario', DBORIGEN . '.TbTarifaCategoria', $this->sesion))
			$querypreviausuario.="alter table Gastos add id_usuario INT(11) NULL, ADD index (id_usuario);";
		$querypreviausuario.="update HojaTiempoajustado set id_usuario=if(length(CodigoEmpleadoFacturable)=6,2000,1000)+1*CodigoEmpleadoFacturable;";
		$querypreviausuario.="update HojaTiempoDetalle set id_usuario=if(length(CodigoEmpleado)=6,2000,1000)+1*CodigoEmpleado;";
		$querypreviausuario.="update Gastos set id_usuario=if(length(CodigoEmpleado)=6,2000,1000)+1*CodigoEmpleado;";
		$querypreviausuario.="update TbTarifaCategoria set id_usuario=if(length(CodigoEmpleado)=6,2000,1000)+1*CodigoEmpleado;";




		$querypreviausuarioprepare = $this->sesion->pdodbh->prepare($querypreviausuario);
		$querypreviausuarioprepare->execute();
	}

	function QueryPostUsuario() {
		$querypostusuario = "create temporary table  categorias_usr as select if(length(CodigoEmpleado)=6,2000+1*CodigoEmpleado 	,1000+1*CodigoEmpleado 	) as id_usuario,
		lower(TbCategoriaEmpleados.DescripcionCategoria) as usuario_FFF_id_categoria_usuario,
		case lower(TbCategoriaEmpleados.DescripcionCategoria)
				when 'socio' then 1
			when 	'asociado senior' then 2
			when 	'asociado junior' then 3
			when 	'procurador' then 4
			when 	'administracion' then 5
			when 	'secretaria' then 5
			when 	'asociado' then 6
			when 	'contratado' then 7
			when 	'administrativo' then 7
			when 	'asistente' then 8
			when 	'practicante' then 9
			else  5
		end as id_categoria_usuario
		FROM cpb_saej.Empleado
		LEFT JOIN  cpb_saej.TbCategoriaEmpleados ON Empleado.Categoria = TbCategoriaEmpleados.CodigoCategoria
									AND Empleado.TipoEmpleado = TbCategoriaEmpleados.TipoEmpleado;";
		$querypostusuario = "update  usuario join  categorias_usr using (id_usuario) set usuario.id_categoria_usuario=categorias_usr.id_categoria_usuario
		where usuario.id_categoria_usuario is null;";

		$querypostusuario="truncate table prm_area_usuario;";
		$querypostusuario="insert into prm_area_usuario (id, glosa) SELECT 1*codigo, descripcion FROM ". DBORIGEN.".`tabladetabla` join  ". DBORIGEN.".tabladetablavalor using (codigotabla) where nombretabla ='saej_area_encargada' ;";

		$querypostusuario="update usuario join   ". DBORIGEN.".Areas on usuario.id_usuario=if(length(CodigoEmpleado)=6,2000+1*CodigoEmpleado  ,1000+1*CodigoEmpleado  )   set id_area_usuario=1*Areas.CodigoArea  ;";

		$this->sesion->pdodbh->beginTransaction();
		$querypostusuarioprepare = $this->sesion->pdodbh->prepare($querypreviausuario);
		$querypostusuarioprepare->execute();
		$this->sesion->pdodbh->commit();
	}

	function QueryPostGastos() {

		$querypostgastos= "update cta_corriente cc join  " . DBORIGEN . ".Gastos gs on cc.id_movimiento=1*gs.codigogasto set  cc.fecha=gs.FechaGasto, cc.con_impuesto=if(gs.flagfacturable='S','SI','NO');";
		$querypostgastos= "update  cta_corriente cc
							join " . DBORIGEN . ".Gastos gs on cc.id_movimiento=1*gs.codigogasto
							set cc.cobrable=0,cc.incluir_en_cobro='NO', cc.monto_cobrable=0, id_cobro=NULL
							where gs.status='A'";
				
		
		$truncar = $this->sesion->pdodbh->exec($querypostgastos);
	}

	/**
	 *
	 * @param int $forzar solamente ejecuta la serie de queries si forzar es true o 1
	 */
	function QueryPreviaClientes($forzar = 0) {
		if ($forzar == 1) {


			$querypreviaClientes = "truncate table asunto; ";
			$querypreviaClientes .= "truncate table contrato;";
			$querypreviaClientes.= "truncate table cliente;";
			$querypreviaClientes.= "delete from log_migracion where   in ('clientes','asuntos','gastos','horas','cobros','documentos')  ";
			$truncar = $this->sesion->pdodbh->exec($querypreviaClientes);
			$this->sesion->debug(json_encode($querypreviaClientes));

			$nextlink = "migracion_script.php?etapa={$this->etapa}&from=0&size=".$this->size;
			echo '<br>Se limpiaron las tablas objetivo, se retomará el proceso de inserción<script>';
			echo "setTimeout(\"location.href = '$nextlink';\",3500);";
			echo '</script>';
			exit();
		}
	}

	function QueryPostClientes($forzar = 0) {



		$QueryPostClientes = "insert ignore into  cliente (codigo_cliente, glosa_cliente) select distinct propuesta.codigocliente,
						concat('Cliente Dummy ',propuesta.codigocliente) glosa_cliente 
						from " . DBORIGEN . ".propuesta left join cliente on propuesta.codigocliente=cliente.codigo_cliente where codigo_cliente is null; ";
		
		if (!UtilesApp::ExisteCampo('codigo_cliente', 'grupo_cliente', $this->sesion)) {
			$QueryPostClientes.="ALTER TABLE  `grupo_cliente` ADD  `codigo_cliente` VARCHAR( 15 ) NULL DEFAULT NULL COMMENT  'Opcional: cuando el grupo de clientes es también un cliente en la tabla cliente';";
			$QueryPostClientes.="ALTER TABLE  `grupo_cliente` ADD UNIQUE (`codigo_cliente`);";

		}
		 $QueryPostClientesx="TRUNCATE TABLE  grupo_cliente;";
		 $QueryPostClientesx.="TRUNCATE TABLE  prm_cliente_referencia;";
		$QueryPostClientesx.="insert into prm_cliente_referencia (id_cliente_referencia, glosa_cliente_referencia) SELECT  1*codigo, descripcion
						FROM  " . DBORIGEN . ".tabladetabla
						JOIN   " . DBORIGEN . ".`tabladetablavalor`
						USING ( codigotabla )
						WHERE nombretabla =  'saej_referencia'
						ORDER BY  `tabladetablavalor`.`codigo` ASC  ;";
		 $QueryPostClientesx.=" ALTER TABLE grupo_cliente AUTO_INCREMENT =1;";
		$QueryPostClientesx.="replace into grupo_cliente (glosa_grupo_cliente, codigo_cliente) select nombrecliente, codigocliente from " . DBORIGEN . ".Cliente join
						(SELECT distinct codigoclientepadre as codigocliente FROM 	" . DBORIGEN . ".Cliente ) as padres using (codigocliente);";
		$truncarx = $this->sesion->pdodbh->exec($QueryPostClientesx);

		$QueryPostClientes.="UPDATE  `cliente` clttb JOIN " . DBORIGEN . ".Cliente clsaej ON 1 * clttb.codigo_cliente =1 * clsaej.codigocliente   SET clttb.id_cliente_referencia =clsaej.codigoreferencia;";

		$QueryPostClientes.="UPDATE  `cliente` clttb JOIN " . DBORIGEN . ".Cliente clsaej ON 1 * clttb.codigo_cliente =1 * clsaej.codigocliente JOIN grupo_cliente gc ON 1 * gc.codigo_cliente =1 * clsaej.codigoclientepadre SET clttb.id_grupo_cliente = gc.id_grupo_cliente WHERE clsaej.codigoclientepadre IS NOT NULL";

		$truncar = $this->sesion->pdodbh->exec($QueryPostClientes);
	}

	/**
	 *
	 * @param int $forzar 1 para reiniciar importación de asuntos, 2 para completar
	 * @return string cuando $forzar es 2, devuelve un string que se añade al query de asuntos en la forma "left join bla bla asuntos que existen en SAEJ y no en Time Tracking'
	 */
	function QueryPreviaAsuntos($forzar = 0) {

		$querypreviaAsuntosA="";
		$describeAsunto=$this->sesion->pdodbh->query( "SHOW COLUMNS FROM     asunto");
		$describeAsuntoRS=$describeAsunto->fetchALL(PDO::FETCH_COLUMN );
		$describeContrato=$this->sesion->pdodbh->query( "SHOW COLUMNS FROM     contrato");
		$describeContratoRS=$describeContrato->fetchALL(PDO::FETCH_COLUMN );

			if(! in_array('numeropropuesta',$describeAsuntoRS) )		$querypreviaAsuntosA .= "ALTER TABLE  `asunto` ADD  numeropropuesta int(11) null;";
			if(! in_array('codigopropuesta',$describeAsuntoRS) )		$querypreviaAsuntosA .= "ALTER TABLE  `asunto` ADD   `codigopropuesta` VARCHAR( 8 ) NULL;";
			if(! in_array('numeropropuesta',$describeContratoRS) )		$querypreviaAsuntosA .= "ALTER TABLE  `contrato` ADD  numeropropuesta int(11) null;";
			if(! in_array('codigopropuesta',$describeContratoRS) )		$querypreviaAsuntosA .= "ALTER TABLE  `contrato` ADD  codigopropuesta VARCHAR( 8 ) NULL;";
			if(! in_array('primer_codigo_asunto',$describeContratoRS) )  $querypreviaAsuntosA .= "ALTER TABLE  `contrato` ADD  `primer_codigo_asunto` VARCHAR( 32 ) NULL;";
			if(! in_array('primer_codigo_asunto_secundario',$describeContratoRS) )  $querypreviaAsuntosA .= "ALTER TABLE  `contrato` ADD `primer_codigo_asunto_secundario` VARCHAR( 32 ) NULL;";

		 if($querypreviaAsuntosA!="") 		$truncar = $this->sesion->pdodbh->exec($querypreviaAsuntosA);

		if ($forzar = 0)
			return '';
		if ($forzar == 1) {
			$querypreviaAsuntos = "delete from log_migracion where   etapa_migracion in ('asuntos','gastos','horas','cobros','documentos') ;";
			$querypreviaAsuntos .= "truncate table asunto; ";
			$querypreviaAsuntos .= " ALTER TABLE `asunto` CHANGE `codigo_asunto` `codigo_asunto` VARCHAR( 20 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL; "; // originalmente la tabla es varchar(10)

			

			$truncar = $this->sesion->pdodbh->exec($querypreviaAsuntos);
			$this->sesion->debug(json_encode($querypreviaAsuntos));
			$nextlink = "migracion_script.php?etapa={$this->etapa}&from=0&size=".$this->size;
			echo '<br>Se limpiaron las tablas objetivo, se retomará el proceso de inserción<script>';
			echo "setTimeout(\"location.href = '$nextlink';\",3500);";
			echo '</script>';
			exit();
		} else if ($forzar == 2) { // esto insiste con los que no se insertaron
			return "left join asuntos on codigo_asunto = OrdenFacturacion.NumeroOrdenFact  WHERE asunto.codigo_asunto IS NULL  ";
		}
	}

	function QueryPostAsuntos() {

		$querypostasunto = "CREATE TABLE IF NOT EXISTS `asuntos_faltantes` (
			`codigo_asunto` varchar(7) NOT NULL DEFAULT '',
			`id_contrato` int(7)  DEFAULT NULL,
			`codigo_cliente` varchar(12) default NULL,
			`ProvieneDe` varchar(32) DEFAULT NULL,
			PRIMARY KEY (`codigo_asunto`)
		  ) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

		$querypostasunto .= "insert ignore into asuntos_faltantes (codigo_asunto, ProvieneDe)
		select distinct  hta.NumeroOrdenFacturacionFact, 'HojaTiempoajustado' as ProvieneDe from " . DBORIGEN . ".HojaTiempoajustado hta left join " . DBORIGEN . ".OrdenFacturacion ofc on hta.NumeroOrdenFacturacionFact = ofc.NumeroOrdenFact
		where ofc.NumeroOrdenFact is null;";

		$querypostasunto .= "insert ignore into asuntos_faltantes (codigo_asunto, ProvieneDe)
		select distinct gas.NumeroOrdenFact, 'Gastos' as ProvieneDe  from " . DBORIGEN . ".Gastos gas left join " . DBORIGEN . ".OrdenFacturacion ofc on gas.NumeroOrdenFact  = ofc.NumeroOrdenFact
		where ofc.NumeroOrdenFact is null;";

		$querypostasunto .= "insert ignore into asuntos_faltantes (codigo_asunto, ProvieneDe)
		select distinct htd.NumeroOrdenFacturacion, 'HojaTiempoDetalle' as ProvieneDe   from " . DBORIGEN . ".HojaTiempoDetalle htd left join " . DBORIGEN . ".OrdenFacturacion ofc on htd.NumeroOrdenFacturacion = ofc.NumeroOrdenFact
		where ofc.NumeroOrdenFact is null and htd.NumeroOrdenFacturacion is not null and htd.NumeroOrdenFacturacion>0;";
		
		$querypostasunto .= "update `asuntos_faltantes` asn join " . DBORIGEN . ".Gastos gs on asn.codigo_asunto=gs.NumeroOrdenFact set codigo_cliente=gs.codigocliente where asn.ProvieneDe='Gastos';";
		$querypostasunto .= "update `asuntos_faltantes` asn join " . DBORIGEN . ".HojaTiempoajustado hta on asn.codigo_asunto=hta.NumeroOrdenFacturacionFact set codigo_cliente=hta.ClienteFacturable where asn.ProvieneDe='HojaTiempoajustado';";
		$querypostasunto .= "update `asuntos_faltantes` asn join " . DBORIGEN . ".HojaTiempoDetalle htd on asn.codigo_asunto=htd.NumeroOrdenFacturacion set codigo_cliente=htd.Cliente where asn.ProvieneDe='HojaTiempoDetalle';";
		
		 

		//$querypostasunto .= "update `contrato` set id_contrato=(select count(*)  from " . DBORIGEN . ". propuesta)+id_contrato;";  // desplaza el id contrato para no colisionar con las operaciones que haremos a continuación
		
		$querypostasunto .= "update contrato set primer_codigo_asunto = (select codigo_asunto  from asunto where asunto.id_contrato=contrato.id_contrato order by id_asunto asc limit 0,1) ,  primer_codigo_asunto_secundario= (select codigo_asunto_secundario from asunto where asunto.id_contrato=contrato.id_contrato order by id_asunto asc limit 0,1) ;"; // Obtiene el valor del primer codigo asunto válido en este contrato
		$querypostasunto .= "update asunto asn join " . DBORIGEN . ". OrdenFacturacion ofc on asn.codigo_asunto=ofc.NumeroOrdenFact set asn.numeropropuesta=ofc.numeropropuesta, asn.codigopropuesta=ofc.codigopropuesta,  asn.codigo_cliente=ofc.codigocliente ;"; // Con el codigo_asunto_secundario puede saber el codigo y numeropropuesta del saej
		//$querypostasunto .= "update contrato cnt join " . DBORIGEN . ". OrdenFacturacion ofc on cnt.primer_codigo_asunto_secundario=ofc.NumeroOrdenFact set numeropropuesta=ofc.numeropropuesta, codigopropuesta=ofc.codigopropuesta;";  // con el codigo_asunto_secundario obtenido antes, tambien sabe dato del numeropropuesta y codigopropuesta para nuestros contratos

		$querypostasunto .= "update contrato cnt join  " . DBORIGEN . ".propuesta prp on cnt.id_contrato=prp.numeropropuesta 
							join  " . DBORIGEN . ".Cliente clnt on prp.codigocliente=clnt.codigocliente
						  set cnt.codigo_cliente=prp.codigocliente , cnt.codigopropuesta=prp.codigopropuesta
						  where  cnt.codigo_cliente !=prp.codigocliente;";
		
		
		
		//$querypostasunto .= "update contrato join (select min(id_contrato) id_contrato, numeropropuesta from asunto where numeropropuesta>0 group by numeropropuesta) asuntox using (id_contrato) set contrato.id_contrato=asuntox.numeropropuesta;";  // Actualiza EL ID CONTRATO igualándolo  AL NUMERO PROPUESTA PARA EL PRIMER ASUNTO VÁLIDO de cada propuesta
		$querypostasunto .= "update`asunto` join contrato on asunto.numeropropuesta=contrato.id_contrato set asunto.id_contrato= asunto.numeropropuesta;"; // los asuntos del mismo numeropropuesta pasan al mismo contrato

		$querypostasunto .= "insert ignore into asunto
						(codigo_asunto,
						codigo_asunto_secundario,
						id_usuario,
						id_encargado,
						id_encargado2,
						id_cobrador,
						codigo_cliente,
						id_contrato,
						id_tipo_asunto,
						id_area_proyecto,
						glosa_asunto,
						id_idioma,
						activo,
						cobrable,
						id_moneda)

								SELECT
						af.codigo_asunto as codigo_asunto,
						af.codigo_asunto as codigo_asunto_secundario,
						contrato.id_usuario_responsable as id_usuario,
						contrato.id_usuario_responsable as id_encargado,
						contrato.id_usuario_responsable as id_encargado2,
						 1 as id_cobrador,
						 cliente.codigo_cliente,
						 contrato.id_contrato,
						 1 as id_tipo_asunto,
						 1 as id_area_proyecto,
						 concat('Expediente Proveniende de ',af.provienede) as glosa_asunto,
						 null as id_idioma,
						 0 as activo,
						1 as cobrable,
						2 as id_moneda
						FROM `asuntos_faltantes`  af
						left join cliente on af.codigo_cliente= cliente.codigo_cliente
						left join contrato on cliente.id_contrato=contrato.id_contrato
						LEFT JOIN asunto
						USING ( codigo_asunto )
						WHERE asunto.codigo_asunto IS NULL ;";
		//$querypostasunto="delete FROM `contrato` where id_contrato not in (select id_contrato from cliente)  and id_contrato not in (select id_contrato from asunto) and id_contrato not in (select id_contrato from cobro);"; //limpia la tabla contrato quitando los que no tienen asunto

		$querypostasunto.="update contrato cnt join `asunto` asn using (id_contrato)  join " . DBORIGEN . ".OrdenFacturacion ofn on asn.codigo_asunto=ofn.NumeroOrdenFact
						set cnt.forma_cobro='RETAINER',
						cnt.monto=IF(ofn.HonorarioPactado>0, ofn.HonorarioPactado, cnt.monto),
						cnt.retainer_horas=ofn.HorasTope
						where ofn.Flagretainer is not NULL;"; //repara retainers
		
			$querypostasunto="update  contrato join  " . DBORIGEN . ".propuesta using (numeropropuesta) 
set 
contrato.id_moneda_monto=if(propuesta.moneda='S',1,if(propuesta.moneda='D',2,3)),
contrato.id_moneda=if(propuesta.moneda='S',1,if(propuesta.moneda='D',2,3)), 
contrato.monto=propuesta.hf_valorventa,contrato.retainer_horas=propuesta.c_horas,
contrato.forma_cobro=case propuesta.tiposervicio
when '010' then 'RETAINER' 
when '014' then 'FLAT FEE'
when '017' then 'FLAT FEE'
else   'TASA' end, contrato.glosa_contrato=propuesta.referencia";

		$querypostasunto.=" insert into  contrato (id_contrato, id_usuario_responsable, id_usuario_secundario, codigo_cliente, fecha_creacion, codigopropuesta, numeropropuesta)
						select prp.numeropropuesta,
						if(length(prp.attache )=6,2000+1*prp.attache ,1000+1*prp.attache  ) ,
						if(length(prp.attachesecundario )=6,2000+1*prp.attachesecundario ,1000+1*prp.attachesecundario  ) ,
						 codigocliente, fechageneracion, prp.codigopropuesta, prp.numeropropuesta
						 from  " . DBORIGEN . ".propuesta prp left join contrato on prp.numeropropuesta=contrato.id_contrato
		where contrato.id_contrato is null ; ";
		
		$querypostasunto="update cpb_timetracking.contrato join " . DBORIGEN . ".propuesta 
						on contrato.id_contrato=propuesta.numeropropuesta
						set contrato.numeropropuesta=propuesta.numeropropuesta,
						contrato.codigopropuesta=propuesta.codigopropuesta
						contrato.codigo_cliente=propuesta.codigocliente;";

		$querypostasunto.="update contrato join " . DBORIGEN . ".`OrdenFacturacion` using (numeropropuesta) set activo='SI'  where `Status`='P' or HojaTiemposFlag='O'; ";
		$querypostasunto.="update cliente join (select min(id_contrato) id_contrato, codigo_cliente from contrato where activo='SI' group by codigo_cliente) contrato using (codigo_cliente)
						set cliente.id_contrato= contrato.id_contrato;";
		
		$querypostasunto="update contrato join " . DBORIGEN . ".propuesta on contrato.id_contrato=propuesta.numeropropuesta set contrato.activo='NO' where propuesta.tipodocumento!='SER'";
		
			$this->sesion->pdodbh->exec($querypostasunto);
	}

	/**
	 *
	 * @param int $forzar 1 para reiniciar importación de asuntos, 2 para completar
	 * @return string cuando $forzar es 2, devuelve un string que se añade al query de asuntos en la forma "left join bla bla asuntos que existen en SAEJ y no en Time Tracking'
	 */
	function QueryPreviaGastos($forzar) {

		$querypreviagastos0="truncate table prm_cta_corriente_tipo;";

		

		$querypreviagastos0.="replace into prm_cta_corriente_tipo (id_cta_corriente_tipo, glosa) SELECT 1* tabladetablavalor.codigo, descripcion
						FROM ".DBORIGEN.".tabladetabla
						JOIN  ".DBORIGEN.".`tabladetablavalor`
						USING ( codigotabla )
						WHERE nombretabla =  'saej_tipo_control_gasto'
						ORDER BY  `tabladetablavalor`.`codigo` ASC ;";


		$this->sesion->pdodbh->beginTransaction();
		$this->sesion->pdodbh->exec($querypreviagastos0);
		$this->sesion->pdodbh->commit();



		if ($forzar == 0)
			return '';
		if ($forzar == 1) {
			$querypreviagastos = "delete from log_migracion where   etapa_migracion in ('gastos','horas','cobros','documentos') ;";
			$querypreviagastos .= "truncate table cta_corriente;";
			$querypreviagastos .= " ALTER TABLE `cta_corriente` CHANGE `codigo_asunto` `codigo_asunto` VARCHAR( 20 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL ;"; // originalmente la tabla es varchar(10)
			$truncar = $this->sesion->pdodbh->exec($querypreviagastos);
			$this->sesion->debug(json_encode($querypreviaClientes));
			$nextlink = "migracion_script.php?etapa={$this->etapa}&from=0&size=".$this->size;
			echo '<br>Se limpiaron las tablas objetivo, se retomará el proceso de inserción<script>';
			echo "setTimeout(\"location.href = '$nextlink';\",3500);";
			echo '</script>';
			exit();
		} else if ($forzar == 2) {
			return " left join cta_corriente on 1*CodigoGasto=cta_corriente.id_movimiento where cta_corriente.id_movimiento is null";
		}
	}

	/**
	 *
	 * @param int $forzar 1 para reiniciar importación de asuntos, 2 para completar
	 * @return string cuando $forzar es 2, devuelve un string que se añade al query de asuntos en la forma "left join bla bla asuntos que existen en SAEJ y no en Time Tracking'
	 */
	function QueryPreviaHoras($forzar) {

	 

		$querypreviatrabajo0="";
		if (!UtilesApp::ExisteCampo('id_trabajo_lemontech', DBORIGEN . ".HojaTiempoajustado", $this->sesion)) {
				$querypreviatrabajo0.="ALTER TABLE " . DBORIGEN . ".HojaTiempoajustado  ADD `id_trabajo_lemontech` INT( 11 ) NULL;";
				$querypreviatrabajo0.="UPDATE " . DBORIGEN . ".HojaTiempoajustado set  `id_trabajo_lemontech`=1*hojatiempoajustadoid;";
			}

			if (!UtilesApp::ExisteIndex('id_trabajo_lemontech', DBORIGEN . ".HojaTiempoajustado", $this->sesion))
			$querypreviatrabajo0.="ALTER TABLE " . DBORIGEN . ".HojaTiempoajustado ADD INDEX ( `id_trabajo_lemontech` );";

			if($querypreviatrabajo0!="") $truncar = $this->sesion->pdodbh->exec($querypreviatrabajo0);

		if ($forzar == 1) {
			$querypreviatrabajo = "delete from log_migracion where   etapa_migracion in ('horas','cobros','documentos') ;";
			$querypreviatrabajo .= "truncate table trabajo;";
			$querypreviatrabajo .= "truncate table tramite;";
			$querypreviatrabajo .= " ALTER TABLE `trabajo` CHANGE `codigo_asunto` `codigo_asunto` VARCHAR( 20 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL; "; // originalmente la tabla es varchar(10)
			$querypreviatrabajo .= " ALTER TABLE `tramite` CHANGE `codigo_asunto` `codigo_asunto` VARCHAR( 20 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL ;"; // originalmente la tabla es varchar(10)
			$truncar = $this->sesion->pdodbh->exec($querypreviatrabajo);

			$this->sesion->debug(json_encode($querypreviatrabajo));
			$nextlink = "migracion_script.php?etapa={$this->etapa}&from=0&size=".$this->size;
			echo '<br>Se limpiaron las tablas objetivo, se retomará el proceso de inserción<script>';
			echo "setTimeout(\"location.href = '$nextlink';\",3500);";
			echo '</script>';
			exit();
		} if ($forzar == 2) {
			return " left join trabajo on trabajo.id_trabajo=hta.id_trabajo_lemontech   where trabajo.id_trabajo is NULL ";
		}
	}

	/**
	 * 
	 * @param type $forzar 1 para recomenzar, 2 para completar
	 * @return string es vacio si no hay un extra para filtrar
	 */
	function QueryPreviaCobros($forzar) {
		if ($forzar == 0) {
			return '';
		} else if ($forzar == 1) {

 
		$querypreviacobro2="";
		if (!UtilesApp::ExisteCampo('id_estado_factura', 'cobro', $this->sesion))
			$querypreviacobro2.= "ALTER TABLE `cobro` ADD `id_estado_factura` INT( 11 ) NULL ;";
		if (!UtilesApp::ExisteCampo('estado_real', 'cobro', $this->sesion))
			$querypreviacobro2 .= "ALTER TABLE `cobro` ADD  `estado_real` VARCHAR( 20 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL ;";
		if (!UtilesApp::ExisteCampo('factura_rut', 'cobro', $this->sesion))
			$querypreviacobro2 .= "ALTER TABLE `cobro` ADD  `factura_rut` VARCHAR( 20 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL; ";
		
		if (!UtilesApp::ExisteCampo('periodo', 'cobro', $this->sesion))
			$querypreviacobro2 .= "ALTER TABLE  `cobro` ADD  `periodo` INT( 6 ) NULL ; ";
			
		if (!UtilesApp::ExisteCampo('periodo', 'trabajo', $this->sesion))
			$querypreviacobro2 .= "ALTER TABLE  `trabajo` ADD  `periodo` INT( 6 ) NULL ; ";
			
		if (!UtilesApp::ExisteCampo('id_contrato', 'trabajo', $this->sesion))
			$querypreviacobro2 .= "ALTER TABLE  `trabajo`  ADD `id_contrato` INT( 11 ) NULL  ; ";
			
		if (!UtilesApp::ExisteCampo('codigo_cliente', 'trabajo', $this->sesion))
			$querypreviacobro2 .= "ALTER TABLE  `trabajo`  ADD `codigo_cliente_tmp` VARCHAR( 20 ) NULL ; ";
		
		if (!UtilesApp::ExisteCampo('incluye_honorarios', DBORIGEN . '.Factura', $this->sesion))
			$querypreviacobro2 .="ALTER TABLE  `Factura` ADD  `incluye_honorarios` INT( 1 ) NOT NULL DEFAULT  '0'; ";
		if (!UtilesApp::ExisteCampo('incluye_gastos', DBORIGEN . '.Factura', $this->sesion))
			$querypreviacobro2 .="ALTER TABLE  `Factura`  ADD  `incluye_gastos` INT( 1 ) NOT NULL DEFAULT  '0'  ;";

			
		$querypreviacobro2 .="update ".DBORIGEN.".Factura join ".DBORIGEN.".Gastos using (NumeroFactura) set incluye_gastos=1;";

		$querypreviacobro2 .="update ".DBORIGEN.".Factura set incluye_gastos=1 where TipoDocumento='L';";
		
		$querypreviacobro2 .="update ".DBORIGEN.".Factura set incluye_honorarios=1 where TipoDocumento in ('F','B','D') and incluye_gastos=0;";
 
			
		if (!UtilesApp::ExisteCampo('factura_razon_social', 'cobro', $this->sesion))
			$querypreviacobro2 .= "ALTER TABLE `cobro`	ADD `factura_razon_social` VARCHAR( 60 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL ;";
		if($querypreviacobro2!="") $querycobro = $this->sesion->pdodbh->exec($querypreviacobro2);

		
	 
		

			$querypreviacobro .= "delete from log_migracion where   etapa_migracion in ('cobros','documentos','facturas') ;";
			$querypreviacobro.= "truncate table cobro;";
			$querypreviacobro.="truncate table documento;";
			$querypreviacobro.="truncate table neteo_documento;";
			$querypreviacobro.="truncate table factura;";
			$querypreviacobro.="truncate table factura_cobro;";
			$querypreviacobro.="truncate table factura;";

			$querypreviacobro.="truncate table cta_cte_fact_mvto";
			$querypreviacobro.="truncate table cta_cte_fact_mvto_moneda";
			$querypreviacobro.="truncate table cta_cte_fact_mvto_neteo";

			$this->sesion->pdodbh->beginTransaction();
			$querycobro = $this->sesion->pdodbh->exec($querypreviacobro);
			$this->sesion->pdodbh->commit();



			$this->sesion->debug(json_encode($querypreviacobro));
			$nextlink = "migracion_script.php?etapa={$this->etapa}&from=0&size=".$this->size;
			echo '<br>Se limpiaron las tablas objetivo, se retomará el proceso de inserción<script>';
			echo "setTimeout(\"location.href = '$nextlink';\",1500);";
			echo '</script>';
			exit();
		} elseif ($forzar == 2) {
			return " left join cobro on cobro.id_cobro = Factura.NumeroFactura  where cobro.id_cobro is NULL	";
		}
	}

	function QueryPostCobros() {

		 
		$querypostcobro = "UPDATE  `trabajo` JOIN  " . DBORIGEN . ".HojaTiempoajustado ON trabajo.id_trabajo = HojaTiempoajustado.id_trabajo_lemontech SET trabajo.id_cobro =1 * HojaTiempoajustado.NumeroFactura;";

		$querypostcobro.= "update cta_corriente cc join  " . DBORIGEN . ".Gastos gs on cc.id_movimiento=1*gs.codigogasto set  cc.id_cobro=gs.NumeroFactura;";

		$querypostcobro.="update cobro set incluye_gastos=0, incluye_honorarios=1;";
		$querypostcobro.="update cobro join cta_corriente using (id_cobro) set incluye_gastos=1, incluye_honorarios=0;";
		$querypostcobro.="update  cta_corriente cc  join cobro c  using (id_cobro) 	set cc.estadocobro=c.estado;";



		$querypostcobro.="update cobro  join   " . DBORIGEN . ".Factura on cobro.id_cobro=Factura.NumeroFactura set cobro.fecha_emision=Factura.FechaGeneracion;";

		$querypostcobro.="update cobro  join  " . DBORIGEN . ".Factura on cobro.id_cobro=Factura.NumeroFactura
						set monto_subtotal=0, monto_original=0,monto_trabajos=0,  monto=0, impuesto_gastos=1*Factura.MontoImpuesto,
						subtotal_gastos=1*Factura.MontoBruto,
						monto_gastos=1*Factura.MontoNeto,
						porcentaje_impuesto_gastos=round(1*Factura.MontoImpuesto/1*Factura.MontoBruto,2)*100
					where incluye_gastos=1  and incluye_honorarios=0;";
		$querypostcobro .= "update cobro set incluye_gastos=1, incluye_honorarios=1 where incluye_gastos=0 and incluye_honorarios=0;";

		$querypostcobro .= "update cobro join
						(SELECT  gs.numerofactura, max(1*ofn.numeropropuesta) numeropropuesta FROM " . DBORIGEN . ".Gastos gs join " . DBORIGEN . ".OrdenFacturacion ofn using (NumeroOrdenFact) where numerofactura is not null
						group by  gs.numerofactura ) gasto_asunto on cobro.id_cobro=gasto_asunto.numerofactura
						set cobro.id_contrato=gasto_asunto.numeropropuesta
						where cobro.incluye_gastos=1;";
		$querypostcobro .= "update cobro join " . DBORIGEN . ".Factura fc on cobro.id_cobro=fc.numerofactura  join " . DBORIGEN . ".propuesta prp on fc.observacion=prp.codigopropuesta
					set cobro.id_contrato=prp.numeropropuesta 
					where fc.observacion is not null  and fc.observacion>1 and prp.tipodocumento='SER';";
		$querypostcobro .= "drop table if exists periodosfactura ;";

		$querypostcobro .= "drop table if exists periodosfactura;";
		$querypostcobro .= " create temporary table periodos_aux as select codigoperiodo as periodo, cast(least(@fechavar, fechainicio) as DATETIME)
					as fechadesdecorregida, fechainicio as fechadesde, fechatermino as fechahasta, @fechavar:=(fechatermino+ interval 1 day) fecha_aux 
						from " . DBORIGEN . ".Periodo , (select @fechavar:=STR_TO_DATE('01,01,1990','%d,%m,%Y')) a order by codigoperiodo ASC;";

		$querypostcobro .= "create table periodosfactura as select * from periodos_aux;";
		

			 
		$querypostcobro .= "update cobro join periodosfactura pf on cobro.fecha_emision between pf.fechadesdecorregida and pf.fechahasta
						set cobro.periodo=pf.periodo;";

		$querypostcobro .= "update  `trabajo` tr join periodosfactura pdf on tr.fecha between pdf.fechadesdecorregida and pdf.fechahasta
						join asunto asn on asn.codigo_asunto=tr.codigo_asunto
						set tr.periodo=pdf.periodo, tr.id_contrato=asn.id_contrato, tr.codigo_cliente_tmp=asn.codigo_cliente;";

		$querypostcobro .= "update trabajo set id_cobro=NULL;";
		$querypostcobro .= "drop table if exists minfactura;";
		$querypostcobro .= "create  table minfactura as SELECT min(cobro.id_cobro) id_cobro, cobro.periodo, cobro.id_contrato 	FROM cobro where incluye_honorarios=1 group by  cobro.periodo, cobro.id_contrato;";

		$querypostcobro .= "ALTER TABLE  `minfactura` ADD INDEX (  `periodo` );";
		$querypostcobro .= "ALTER TABLE  `minfactura` ADD INDEX (  `id_contrato` );";
		$querypostcobro .= "ALTER TABLE  `trabajo` ADD INDEX (  `periodo` );";
		$querypostcobro .= "ALTER TABLE  `trabajo` ADD INDEX (  `id_contrato` );";

		$querypostcobro .= "update trabajo join minfactura on minfactura.periodo=trabajo.periodo and minfactura.id_contrato=trabajo.id_contrato
						set trabajo.id_cobro=minfactura.id_cobro;";
		
		$querypostcobro .= "create temporary table trabajosincobro as
							select distinct periodo, id_contrato from trabajo where id_cobro is null;";

		$querypostcobro .= "create temporary table minfacturaplus as select trabajosincobro.*,
							min(cobro.id_cobro) id_cobro from trabajosincobro join cobro using (id_contrato)
							where cobro.periodo>=trabajosincobro.periodo and cobro.incluye_honorarios=1
							group by trabajosincobro.periodo, trabajosincobro.id_contrato;";

		$querypostcobro .= "update trabajo join minfacturaplus using (periodo, id_contrato) 
							set trabajo.id_cobro=minfacturaplus.id_cobro
							where trabajo.id_cobro is null;							";

		$querypostcobro .= "update `trabajo` join cobro using (id_cobro) set trabajo.estadocobro=cobro.estado;";

		$querypostcobro .= "create temporary table montothh as select id_cobro, 
							SUM( IF( trabajo.cobrable =1, trabajo.tarifa_hh * TIME_TO_SEC( trabajo.duracion_cobrada ) /3600, 0 ) ) monto_thh
							FROM trabajo group by id_cobro;";
		$querypostcobro .= "update cobro join montothh using (id_cobro)                                set cobro.monto_thh=montothh.monto_thh                        WHERE cobro.estado NOT IN ('CREADO','EN REVISION') ;";




		$querypostcobro .= "update `cobro` set monto_ajustado=monto_subtotal where monto_ajustado=0;";

		$querypostcobro .= "insert ignore into cobro_asunto (id_cobro, codigo_asunto) select distinct trabajo.id_cobro, trabajo.codigo_asunto from trabajo join asunto using (codigo_asunto) join cobro using (id_cobro);";
		$querypostcobro .= "insert ignore into cobro_asunto (id_cobro, codigo_asunto) select distinct cc.id_cobro, cc.codigo_asunto from cta_corriente cc join asunto using (codigo_asunto) join cobro using (id_cobro);";
		
		
		
			$this->sesion->pdodbh->beginTransaction();
			$querycobro = $this->sesion->pdodbh->exec($querypostcobro);
			$this->sesion->pdodbh->commit();
	}

	function QueryPreviaDocumentos($forzar) {
		if($forzar==1) {
		 
		$querypreviadoc= "delete from log_migracion where   etapa_migracion in ('documentos','facturas') ;";
		$querypreviadoc.= "update cobro set estado='EN REVISION' where estado in ('EMITIDO','PAGO PARCIAL','FACTURADO','ENVIADO AL CLIENTE')";

		$querypreviaexec = $this->sesion->pdodbh->exec($querypreviadoc);
		
			$nextlink = "migracion_script.php?etapa={$this->etapa}&from=0&size=".$this->size;
				echo '<br>Se limpiaron las tablas objetivo y los cobros pasaron a EN REVISION: se retomará el proceso de inserción<script>';
				echo "setTimeout(\"location.href = '$nextlink';\",1500);";
				echo '</script>';
				exit();
			} else {
				return "";
			}
		
		}
		
	 

	function QueryPostDocumentos() {
		$querypostdocumento = "update cta_corriente join cobro c on  cta_corriente.id_cobro=c.id_cobro  set cta_corriente.estadocobro=c.estado;";
		$querypostdocumento.="update trabajo join cobro c on trabajo.id_cobro=c.id_cobro set trabajo.estadocobro=c.estado;";
		$querypostdocumento.="update tramite join cobro c on tramite.id_cobro=c.id_cobro set tramite.estadocobro=c.estado;";

		$querycobro = $this->sesion->pdodbh->exec($querypostdocumento);
	}

	function QueryPreviaFacturas($forzar) {
		if ($forzar == 1) {
			$querypreviafactura = "update cobro set documento=null";
			$querypreviafactura = "update cobro join  " . DBORIGEN . ".Factura on cobro.id_cobro=Factura.NumeroFactura set cobro.documento=Factura.CodigoFacturaBoleta where Factura.TipoDocumento='F';";
			$querypreviafactura.="truncate table factura;";
			$querypreviafactura.="truncate table factura_cobro;";

			$querypreviafactura.="truncate table cta_cte_fact_mvto";
			$querypreviafactura.="truncate table cta_cte_fact_mvto_moneda";
			$querypreviafactura.="truncate table cta_cte_fact_mvto_neteo";

			$this->sesion->pdodbh->beginTransaction();
			$querycobro = $this->sesion->pdodbh->exec($querypreviacobro);
			$this->sesion->pdodbh->commit();
			$nextlink = "migracion_script.php?etapa={$this->etapa}&from=0&size=".$this->size;
			echo '<br>Se limpiaron las tablas objetivo, se retomará el proceso de inserción<script>';
			echo "setTimeout(\"location.href = '$nextlink';\",1500);";
			echo '</script>';
			exit();
		} else {
			return "";
		}
	}

	function QueryPostFacturas() {
		$querypostfactura = "update factura ltf join " . DBORIGEN . ".Factura sjf on ltf.id_factura=sjf.NumeroFactura left join  " . DBORIGEN . ".DescripcionFactura df set  ltf.fecha= sjf.FechaGeneracion, ltf.descripcion=df.Descripcion;";
		$querypostfactura.="update cobro join factura set cobro.estado='FACTURADO' where cobro.estado in ('CREADO','EN REVISION','EMITIDO');";

		$querypostfactura.= "UPDATE cobro SET estado = estado_real WHERE estado_real IS NOT NULL AND estado_real != ''";
		$querypostfactura.= "UPDATE cobro SET estado = 'FACTURADO' WHERE ( SELECT count(*) FROM factura WHERE factura.id_cobro = cobro.id_cobro ) > 0 AND estado IN ('CREADO','EN REVISION','EMISION');";
		$querypostfactura.= "UPDATE cobro
											JOIN factura USING( id_cobro )
											JOIN cta_cte_fact_mvto USING( id_factura )
											JOIN cta_cte_fact_mvto_neteo ON cta_cte_fact_mvto.id_cta_cte_mvto = cta_cte_fact_mvto_neteo.id_mvto_deuda
											SET cobro.estado = 'PAGO PARCIAL';";
		$querypostfactura.= "UPDATE cobro
											JOIN factura USING( id_cobro )
											JOIN cta_cte_fact_mvto USING( id_factura )
											SET cobro.estado = 'PAGADO'
											WHERE cta_cte_fact_mvto.saldo = 0;";
		if (UtilesApp::ExisteCampo('id_estado_factura', 'cobro', $this->sesion))
			$querypostfactura.= "ALTER TABLE `cobro`			  DROP `id_estado_factura`";
		if (UtilesApp::ExisteCampo('estado_real', 'cobro', $this->sesion))
			$querypostfactura.= "ALTER TABLE `cobro`			  DROP `estado_real`";


		$execquerypostfactura = $this->sesion->pdodbh->exec($querypostfactura);
	}

}

