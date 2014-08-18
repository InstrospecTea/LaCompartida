<?php 

/**
 *
 * Clase abstracta que establece, enumera y tipifica los distintos campos específicos de cada reporte de historial.
 *
 */
abstract class TipoHistorialEnum extends BasicEnumeration {

	//Gastos
	const gastoStoryTable = 'gasto_historial';
	const gastoMainTable = 'cta_corriente';
	const gastoStoryKey = 'id_movimiento';
	const gastoCreationDate = 'cta_corriente.fecha_creacion';

	//Cobros
	const cobroStoryTable = 'cobro_movimiento';
	const cobroMainTable = 'cobro';
	const cobroStoryKey = 'id_cobro';
	const cobroCreationDate = 'cobro.fecha_creacion';


	//Trabajo
	const trabajoStoryTable = 'trabajo_historial';
	const trabajoMainTable = 'trabajo';
	const trabajoStoryKey = 'id_trabajo';
	const trabajoCreationDate = 'trabajo.fecha_creacion';

	//Tramite
	const tramiteStoryTable = 'tramite_historial';
	const tramiteMainTable = 'tramite';
	const tramiteStoryKey = 'id_tramite';
	const tramiteCreationDate = 'tramite.fecha_creacion';

}