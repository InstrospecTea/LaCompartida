<?php
namespace TTB\Configurations;

class ConfigCargaMasiva
{

	public $actions;
	public $personas;
	public $adicional;
	public $descripcion_trabajos_grandes;
	public $duraciones_trabajos_grandes;
	public $duracion_subtract;
	public $descripciones_gastos;

	public function __construct()
	{


		$this->actions = array(
			array(
				'name' => __('Conversaci�n con'),
				'duration' => '00:20:00',
				'chargeable_duration' => '00:20:00'
			),
			array(
				'name' => __('Escribir correo electronico para'),
				'duration' => '00:10:00',
				'chargeable_duration' => '00:10:00'
			),
			array(
				'name' => __('Reuni�n con'),
				'duration' => '00:30:00',
				'chargeable_duration' => '00:30:00'
			),
			array(
				'name' => __('Almuerzo con'),
				'duration' => '00:45:00',
				'chargeable_duration' => '00:45:00'
			)
		);

		$this->personas = array(
			__('Gerente General'),
			__('jefe de proyecto'),
			__('contador'),
			__('equipo de ventas')
		);
		$this->adicional = array(
			__('para revisi�n de proyecto'),
			__('con respecto a problemas'),
			__('relacionados al proyecto'),
			__('para plantear soluciones')
		);

		$this->descripcion_trabajos_grandes = array(
			__('An�lisis promesa de equipo. Reuni�n con CL y MC para definir escritura.'),
			__('Reuni�n con ICC y SI para definir escritura.'),
			__('Reuni�n con EG y FLL para definir escritura.'),
			__('Redacci�n de contrato y an�lisis antecedentes legales de sociedad.'),
			__('Reuni�n FF y NA para definir t�rminos de contrato. Estudio antecedentes legales.'),
			__('An�lisis avance FF. Listado documentos y gesti�n.'),
			__('Listado de documentos y gesti�n. Reuni�n equipo C.A. '),
			__('Redacci�n de borradores y an�lisis antecedentes legales de sociedad.'),
			__('Estudio antecedentes y redacci�n solicitud a Municipalidad.'),
			__('Redacci�n de borradores y an�lisis antecedentes legales de esta sociedad.'),
			__('Revisi�n de antecedentes para grupo juicios.'),
			__('Reuni�n con J.T., H.D. y F.R. por tema de permisos'),
			__('Reuni�n con el departamento comercial, definici�n'),
			__('Orientaci�n a la gerencia de las implicaciones leg'),
			__('Elaboraci�n de contrato, redacci�n de las cl�usula'),
			__('Se preparan los documentos legales para la compra'),
			__('Elaboraci�n de los documentos legales. Se validan'),
			__('Constituci�n de Sociedad An�nima -Juegos Aleatorio'),
			__('Autenticaci�n de la firma, autenticaci�n de firmas'),
			__('Personer�a Jur�dica: Personer�a con distribuci�n'),
			__('Legalizaci�n de libros en Tributaci�n Directa. Tr�...'),
			__('Reuni�n con GF, JU y JIP'),
			__('Avance en contrato N� 90-390'),
			__('Kickoff proyecto LegalChile'),
			__('Reuni�n con FLM en AS-SOP'),
			__('Revisi�n de contrato 2011'),
			__('Formalizaci�n contrato 2011'),
			__('Correccion contrato 2011'),
			__('Revisi�n acuerdos y seguimiento'),
			__('Confirmaci�n telef�nica alcance contrato'),
			__('Formalizaci�n contrato compraventa inicial'),
			__('Reuni�n inicial contrato 2011 con JT y PC'),
			__('Nuevo contrato laboral seg�n norma 15-21'),
			__('Tr�mites notaria contrato 2011'),
			__('Soporte telef�nico alcance contratos '),
			__('Correcciones generales y seguimiento correos '),
			__('Inicio tr�mites compraventa terreno V regi�n'),
			__('Reuni�n con TA, JPO y IC'),
			__('Tribunales por seguimiento caso 123-2 '),
			__('Tribunales seguimiento caso 948-22'),
			__('Revisi�n contrato'),
			__('Revisi�n contrato 50-43 en conjunto con JA y ES de...'),
			__('Correcciones contrato 50-43 de acuerdo a la revisi...'),
			__('Revisi�n contrato 50-43 con nuevas modificaciones ...'),
			__('Avance en contrato casos 70, seg�n formato enviado...'),
			__('Reuni�n de revisi�n contratos laborales sucursal V...'),
			__('Revisi�n de escrituras de propiedad anterior.'),
			__('Reuni�n con CD, LS y RM para revisar estado actual...'),
			__('Estudio de acciones legales en caso de LA.'),
			__('Revisi�n de contratos de trabajo.'),
			__('Lectura de documentos frente a notario. No se fir...'),
			__('Estudio de nueva revisi�n de escrituras anteriores...'),
			__('Asientos registro de redacci�n y preparaci�n de as...'),
			__('Reuni�n revisi�n de documentos con T.C.'),
			__('Recolecci�n de informaci�n y elaboraci�n de contra...'),
			__('Elaboraci�n de contrato de venta de equipos de com...'),
			__('Correcci�n de escritura objetada. Agendamiento de...'),
			__('Estudio de reglamento escolar y elaboraci�n lista ...'),
			__('Reuni�n con profesores.'),
			__('Estudio de posesi�n efectiva presentada por TL.'),
			__('Estudio de documentos para an�lisis terrenos.'),
			__('Personer�a Jur�dica de Inversiones mobiliarias de ...'),
			__('Salida a terreno con F.D. An�lisis derechos.'),
			__('Redacci�n y Preparaci�n de Contrato: Contrato de E...'),
			__('Asientos Registro de Accionistas-Redacci�n y Prep...'),
			__('Reuni�n con LT, GT y EQ para lectura y revisi�n de...'),
			__('Modificaci�n de Estatutos-Redacci�n y Preparaci�n...'),
			__('An�lisis promesa de equipo LEX'),
			__('Modificaci�n de Estatutos-Redacci�n y Preparaci�n...'),
			__('Preparaci�n de nueva propuesta de contratos de tra...'),
			__('An�lisis avance FF. Listado de documentos y gestio...'),
			__('Reuni�n con DF y CA para redactar contrato.'),
			__('Revisi�n de documentos legales y modificaci�n de l...'),
			__('Revisi�n contrato y compra de acciones para elabor...'),
			__('Redacci�n escrito. Juzgado de trabajo.'),
			__('Redacci�n contrato final.'),
			__('Listado de documentos y gesti�n. Reuni�n equipo C....'),
			__('Redacci�n de borradores y an�lisis antecedentes le...'),
			__('Preparaci�n del contrato laboral del nuevo gerente...'),
			__('Presentaci�n de acciones legales por caso de LA.'),
			__('Reuni�n con TL por posesi�n de inmueble, se logra ...'),
			__('Asientos Registro de Accionistas-Redacci�n y Prep...'),
			__('Estudio antecedentes y redacci�n solicitud a Munic...'),
			__('Recopilar y legalizar las firmas de la compra de a...'),
			__('Redacci�n de borradores y an�lisis antecedentes le...'),
			__('Asesor�a en la negociaci�n con proveedor extranjer...'),
			__('Presentaci�n de nuevo formato de contratos de trab...'),
			__('Revisi�n de antecedentes para grupo juicios.'),
			__('Personer�a Jur�dica: Sociedad de Bac Chile Inversi...'),
			__('Ejecutar correcciones sobre documentos, env�o para...'),
			__('Asientos Registro de Accionistas-Redacci�n y Pr...'),
			__('Seguimiento de caso LA.'),
			__('An�lisis promesa de equipo. Reuni�n con CL y MC pa...'),
			__('Elaborar contrato de arrendamiento de la nueva bod...'),
			__('Reuni�n inicial para presentaci�n de caso.'),
			__('Redacci�n de contrato y an�lisis antecedentes lega...'),
			__('Reuni�n con el proveedor de servicios de tecnolog�...'),
			__('Reuni�n FF y NA para definir t�rminos de contrato....'),
			__('Modificaci�n de Estatutos-Re: Timbres y derecho...'),
			__('An�lisis avance FF. Listado documentos y gesti�n.'),
			__('Redacci�n de borradores y an�lisis antecedentes le...'),
			__('Reuni�n inicial para presentaci�n de caso.'),
			__('Reuni�n revisi�n de documentos con T.C.'),
			__('Revisi�n acuerdos y seguimiento'),
			__('Revisi�n contrato'),
			__('Revisi�n contrato 50-43 con nuevas modificaciones ...'),
			__('Revisi�n contrato 50-43 en conjunto con JA y ES de...'),
			__('Revisi�n contrato y compra de acciones para elabor...'),
			__('Revisi�n contratos y compra de acciones para elabo...'),
			__('Revisi�n de antecedentes para grupo juicios.'),
			__('Revisi�n de contrato 2011'),
			__('Revisi�n de contratos de trabajo.'),
			__('Revisi�n de documentos legales y modificaci�n de l...'),
			__('Revisi�n de escrituras de propiedad anterior.'),
			__('Revisi�n del contrato de trabajo y redacci�n de lo...'),
			__('Salida a terreno con F.D. An�lisis derechos.'),
			__('Se preparan los documentos legales para la compra ...'),
			__('Seguimiento de caso LA.'),
			__('Soporte telef�nico alcance contratos'),
			__('Timbres de Registro. Re: Timbres y derechos de reg...'),
			__('Tr�mites notaria contrato 2011'),
			__('Transcripci�n de Acta. Re: Transcripci�n de Memora...'),
			__('Tribunales por seguimiento caso 123-2'),
			__('Tribunales seguimiento caso 948-22'),
		);

		$this->duraciones_trabajos_grandes = array(
			'01:10:00', '01:20:00', '01:30:00', '01:40:00', '01:50:00', '02:00:00', '02:10:00', '02:20:00', '02:30:00',
			'02:40:00', '02:50:00', '03:00:00', '03:10:00', '03:20:00', '03:30:00', '03:40:00', '03:50:00', '04:00:00',
			'04:10:00', '04:20:00', '04:30:00', '04:40:00', '04:50:00', '05:00:00', '05:10:00', '05:20:00', '05:30:00',
			'05:40:00', '05:50:00', '06:00:00', '06:20:00', '06:40:00', '06:40:00', '07:00:00', '07:20:00', '07:40:00',
			'08:00:00'
		);

		$this->duracion_subtract = array('00:00:00', '00:00:00', '00:00:00', '00:00:00',
			'00:00:00', '00:10:00', '00:20:00', '00:30:00', '00:40:00', '00:50:00',
			'01:00:00');


		$this->descripciones_gastos = array(
			__('Archivo Judicial'),
			__('Arriendo Casilla Banco'),
			__('Biblioteca del Congreso'),
			__('Certificados'),
			__('Compra Bases de Licitaci�n'),
			__('Compulsas (fotocopias)'),
			__('Conservador de Bienes Ra�ces'),
			__('Correspondencia'),
			__('Diario Oficial'),
			__('Dominio Internet'),
			__('Fotocopiado'),
			__('Gastos Visa'),
			__('Hotel y Comidas'),
			__('Impuestos'),
			__('Informes Comerciales'),
			__('Legalizaci�n documentos'),
			__('Materiales de Oficina'),
			__('Ministerio de Relaciones Exteriores'),
			__('Movilizaci�n'),
			__('Notar�a'),
			__('Otros Gastos Miscel�neos'),
			__('Patente Municipal'),
			__('Patentes Mineras'),
			__('Provisi�n de Gastos'),
			__('Publicaciones Diarios Locales'),
			__('Receptor Judicial'),
			__('Servicio de Courier'),
			__('Tel�fono y Fax'),
			__('Tesorer�a'),
			__('T�tulos Accionarios'),
			__('T�tulos de Marcas'),
			__('Traducciones'),
			__('Transferencia de Veh�culos'),
			__('Transporte A�reo')
		);
	}

}
