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
				'name' => __('Conversación con'),
				'duration' => '00:20:00',
				'chargeable_duration' => '00:20:00'
			),
			array(
				'name' => __('Escribir correo electronico para'),
				'duration' => '00:10:00',
				'chargeable_duration' => '00:10:00'
			),
			array(
				'name' => __('Reunión con'),
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
			__('para revisión de proyecto'),
			__('con respecto a problemas'),
			__('relacionados al proyecto'),
			__('para plantear soluciones')
		);

		$this->descripcion_trabajos_grandes = array(
			__('Análisis promesa de equipo. Reunión con CL y MC para definir escritura.'),
			__('Reunión con ICC y SI para definir escritura.'),
			__('Reunión con EG y FLL para definir escritura.'),
			__('Redacción de contrato y análisis antecedentes legales de sociedad.'),
			__('Reunión FF y NA para definir términos de contrato. Estudio antecedentes legales.'),
			__('Análisis avance FF. Listado documentos y gestión.'),
			__('Listado de documentos y gestión. Reunión equipo C.A. '),
			__('Redacción de borradores y análisis antecedentes legales de sociedad.'),
			__('Estudio antecedentes y redacción solicitud a Municipalidad.'),
			__('Redacción de borradores y análisis antecedentes legales de esta sociedad.'),
			__('Revisión de antecedentes para grupo juicios.'),
			__('Reunión con J.T., H.D. y F.R. por tema de permisos'),
			__('Reunión con el departamento comercial, definición'),
			__('Orientación a la gerencia de las implicaciones leg'),
			__('Elaboración de contrato, redacción de las cláusula'),
			__('Se preparan los documentos legales para la compra'),
			__('Elaboración de los documentos legales. Se validan'),
			__('Constitución de Sociedad Anónima -Juegos Aleatorio'),
			__('Autenticación de la firma, autenticación de firmas'),
			__('Personería Jurídica: Personería con distribución'),
			__('Legalización de libros en Tributación Directa. Trá...'),
			__('Reunión con GF, JU y JIP'),
			__('Avance en contrato N° 90-390'),
			__('Kickoff proyecto LegalChile'),
			__('Reunión con FLM en AS-SOP'),
			__('Revisión de contrato 2011'),
			__('Formalización contrato 2011'),
			__('Correccion contrato 2011'),
			__('Revisión acuerdos y seguimiento'),
			__('Confirmación telefónica alcance contrato'),
			__('Formalización contrato compraventa inicial'),
			__('Reunión inicial contrato 2011 con JT y PC'),
			__('Nuevo contrato laboral según norma 15-21'),
			__('Trámites notaria contrato 2011'),
			__('Soporte telefónico alcance contratos '),
			__('Correcciones generales y seguimiento correos '),
			__('Inicio trámites compraventa terreno V región'),
			__('Reunión con TA, JPO y IC'),
			__('Tribunales por seguimiento caso 123-2 '),
			__('Tribunales seguimiento caso 948-22'),
			__('Revisión contrato'),
			__('Revisión contrato 50-43 en conjunto con JA y ES de...'),
			__('Correcciones contrato 50-43 de acuerdo a la revisi...'),
			__('Revisión contrato 50-43 con nuevas modificaciones ...'),
			__('Avance en contrato casos 70, según formato enviado...'),
			__('Reunión de revisión contratos laborales sucursal V...'),
			__('Revisión de escrituras de propiedad anterior.'),
			__('Reunión con CD, LS y RM para revisar estado actual...'),
			__('Estudio de acciones legales en caso de LA.'),
			__('Revisión de contratos de trabajo.'),
			__('Lectura de documentos frente a notario. No se fir...'),
			__('Estudio de nueva revisión de escrituras anteriores...'),
			__('Asientos registro de redacción y preparación de as...'),
			__('Reunión revisión de documentos con T.C.'),
			__('Recolección de información y elaboración de contra...'),
			__('Elaboración de contrato de venta de equipos de com...'),
			__('Corrección de escritura objetada. Agendamiento de...'),
			__('Estudio de reglamento escolar y elaboración lista ...'),
			__('Reunión con profesores.'),
			__('Estudio de posesión efectiva presentada por TL.'),
			__('Estudio de documentos para análisis terrenos.'),
			__('Personería Jurídica de Inversiones mobiliarias de ...'),
			__('Salida a terreno con F.D. Análisis derechos.'),
			__('Redacción y Preparación de Contrato: Contrato de E...'),
			__('Asientos Registro de Accionistas-Redacción y Prep...'),
			__('Reunión con LT, GT y EQ para lectura y revisión de...'),
			__('Modificación de Estatutos-Redacción y Preparación...'),
			__('Análisis promesa de equipo LEX'),
			__('Modificación de Estatutos-Redacción y Preparación...'),
			__('Preparación de nueva propuesta de contratos de tra...'),
			__('Análisis avance FF. Listado de documentos y gestio...'),
			__('Reunión con DF y CA para redactar contrato.'),
			__('Revisión de documentos legales y modificación de l...'),
			__('Revisión contrato y compra de acciones para elabor...'),
			__('Redacción escrito. Juzgado de trabajo.'),
			__('Redacción contrato final.'),
			__('Listado de documentos y gestión. Reunión equipo C....'),
			__('Redacción de borradores y análisis antecedentes le...'),
			__('Preparación del contrato laboral del nuevo gerente...'),
			__('Presentación de acciones legales por caso de LA.'),
			__('Reunión con TL por posesión de inmueble, se logra ...'),
			__('Asientos Registro de Accionistas-Redacción y Prep...'),
			__('Estudio antecedentes y redacción solicitud a Munic...'),
			__('Recopilar y legalizar las firmas de la compra de a...'),
			__('Redacción de borradores y análisis antecedentes le...'),
			__('Asesoría en la negociación con proveedor extranjer...'),
			__('Presentación de nuevo formato de contratos de trab...'),
			__('Revisión de antecedentes para grupo juicios.'),
			__('Personería Jurídica: Sociedad de Bac Chile Inversi...'),
			__('Ejecutar correcciones sobre documentos, envío para...'),
			__('Asientos Registro de Accionistas-Redacción y Pr...'),
			__('Seguimiento de caso LA.'),
			__('Análisis promesa de equipo. Reunión con CL y MC pa...'),
			__('Elaborar contrato de arrendamiento de la nueva bod...'),
			__('Reunión inicial para presentación de caso.'),
			__('Redacción de contrato y análisis antecedentes lega...'),
			__('Reunión con el proveedor de servicios de tecnologí...'),
			__('Reunión FF y NA para definir términos de contrato....'),
			__('Modificación de Estatutos-Re: Timbres y derecho...'),
			__('Análisis avance FF. Listado documentos y gestión.'),
			__('Redacción de borradores y análisis antecedentes le...'),
			__('Reunión inicial para presentación de caso.'),
			__('Reunión revisión de documentos con T.C.'),
			__('Revisión acuerdos y seguimiento'),
			__('Revisión contrato'),
			__('Revisión contrato 50-43 con nuevas modificaciones ...'),
			__('Revisión contrato 50-43 en conjunto con JA y ES de...'),
			__('Revisión contrato y compra de acciones para elabor...'),
			__('Revisión contratos y compra de acciones para elabo...'),
			__('Revisión de antecedentes para grupo juicios.'),
			__('Revisión de contrato 2011'),
			__('Revisión de contratos de trabajo.'),
			__('Revisión de documentos legales y modificación de l...'),
			__('Revisión de escrituras de propiedad anterior.'),
			__('Revisión del contrato de trabajo y redacción de lo...'),
			__('Salida a terreno con F.D. Análisis derechos.'),
			__('Se preparan los documentos legales para la compra ...'),
			__('Seguimiento de caso LA.'),
			__('Soporte telefónico alcance contratos'),
			__('Timbres de Registro. Re: Timbres y derechos de reg...'),
			__('Trámites notaria contrato 2011'),
			__('Transcripción de Acta. Re: Transcripción de Memora...'),
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
			__('Compra Bases de Licitación'),
			__('Compulsas (fotocopias)'),
			__('Conservador de Bienes Raíces'),
			__('Correspondencia'),
			__('Diario Oficial'),
			__('Dominio Internet'),
			__('Fotocopiado'),
			__('Gastos Visa'),
			__('Hotel y Comidas'),
			__('Impuestos'),
			__('Informes Comerciales'),
			__('Legalización documentos'),
			__('Materiales de Oficina'),
			__('Ministerio de Relaciones Exteriores'),
			__('Movilización'),
			__('Notaría'),
			__('Otros Gastos Misceláneos'),
			__('Patente Municipal'),
			__('Patentes Mineras'),
			__('Provisión de Gastos'),
			__('Publicaciones Diarios Locales'),
			__('Receptor Judicial'),
			__('Servicio de Courier'),
			__('Teléfono y Fax'),
			__('Tesorería'),
			__('Títulos Accionarios'),
			__('Títulos de Marcas'),
			__('Traducciones'),
			__('Transferencia de Vehículos'),
			__('Transporte Aéreo')
		);
	}

}
