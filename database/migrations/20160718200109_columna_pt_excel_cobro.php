<?php

namespace Database;

class ColumnaPtExcelCobro extends \Database\Migration implements \Database\ITemplateMigration {

	private $sqls = array(
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'N�' WHERE `nombre_interno` LIKE 'id_trabajo' AND `grupo` like 'Listado de trabajos';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Data' WHERE `nombre_interno` LIKE 'fecha' AND `grupo` like 'Listado de trabajos';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Advogado' WHERE `nombre_interno` LIKE 'abogado' AND `grupo` like 'Listado de trabajos';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Assunto' WHERE `nombre_interno` LIKE 'asunto' AND `grupo` like 'Listado de trabajos';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Solicitante' WHERE `nombre_interno` LIKE 'solicitante' AND `grupo` like 'Listado de trabajos';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Descri��o' WHERE `nombre_interno` LIKE 'descripcion' AND `grupo` like 'Listado de trabajos';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Dura��o trabalhada' WHERE `nombre_interno` LIKE 'duracion_trabajada' AND `grupo` like 'Listado de trabajos';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Dura��o' WHERE `nombre_interno` LIKE 'duracion_cobrable' AND `grupo` like 'Listado de trabajos';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Dura��o Retainer' WHERE `nombre_interno` LIKE 'duracion_retainer' AND `grupo` like 'Listado de trabajos';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Cobr�vel' WHERE `nombre_interno` LIKE 'cobrable' AND `grupo` like 'Listado de trabajos';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Tarifa (%descri��o_moeda%)' WHERE `nombre_interno` LIKE 'tarifa_hh' AND `grupo` like 'Listado de trabajos';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Valor (%descri��o_moeda%)' WHERE `nombre_interno` LIKE 'valor_trabajo' AND `grupo` like 'Listado de trabajos';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Cliente' WHERE `nombre_interno` LIKE 'cliente' AND `grupo` like 'Encabezado';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Endere�o' WHERE `nombre_interno` LIKE 'direccion' AND `grupo` like 'Encabezado';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'CPF (persona) CNPJ (empresa)' WHERE `nombre_interno` LIKE 'rut' AND `grupo` like 'Encabezado';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Contato' WHERE `nombre_interno` LIKE 'contacto' AND `grupo` like 'Encabezado';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Telefone' WHERE `nombre_interno` LIKE 'telefono' AND `grupo` like 'Encabezado';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Resumo Cobran�a' WHERE `nombre_interno` LIKE 'titulo' AND `grupo` like 'Resumen';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Data:' WHERE `nombre_interno` LIKE 'fecha' AND `grupo` like 'Resumen';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Data de:' WHERE `nombre_interno` LIKE 'fecha_desde' AND `grupo` like 'Resumen';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Data at�:' WHERE `nombre_interno` LIKE 'fecha_hasta' AND `grupo` like 'Resumen';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Tipo de Honor�rios:' WHERE `nombre_interno` LIKE 'forma_cobro' AND `grupo` like 'Resumen';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Horas Retainer' WHERE `nombre_interno` LIKE 'horas_retainer' AND `grupo` like 'Resumen';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Montante Retainer' WHERE `nombre_interno` LIKE 'monto_retainer' AND `grupo` like 'Resumen';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Montante inicial CAP' WHERE `nombre_interno` LIKE 'monto_cap_inicial' AND `grupo` like 'Resumen';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Montante CAP utilizado' WHERE `nombre_interno` LIKE 'monto_cap_usado' AND `grupo` like 'Resumen';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Montante restante CAP' WHERE `nombre_interno` LIKE 'monto_cap_restante' AND `grupo` like 'Resumen';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Total horas:' WHERE `nombre_interno` LIKE 'total_horas' AND `grupo` like 'Resumen';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Honor�rios:' WHERE `nombre_interno` LIKE 'honorarios' AND `grupo` like 'Resumen';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Equivalente a:' WHERE `nombre_interno` LIKE 'equivalente' AND `grupo` like 'Resumen';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Desconto:' WHERE `nombre_interno` LIKE 'descuento' AND `grupo` like 'Resumen';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Subtotal:' WHERE `nombre_interno` LIKE 'subtotal' AND `grupo` like 'Resumen';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Gastos:' WHERE `nombre_interno` LIKE 'gastos' AND `grupo` like 'Resumen';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Imposto:' WHERE `nombre_interno` LIKE 'impuesto' AND `grupo` like 'Resumen';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Total cobran�a:' WHERE `nombre_interno` LIKE 'total_cobro' AND `grupo` like 'Resumen';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Detalhe Profissional' WHERE `nombre_interno` LIKE 'titulo' AND `grupo` like 'Detalle profesional';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Nome' WHERE `nombre_interno` LIKE 'nombre' AND `grupo` like 'Detalle profesional';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Hr. Trabalhadas' WHERE `nombre_interno` LIKE 'horas_trabajadas' AND `grupo` like 'Detalle profesional';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Hr. Cobrabr�veis' WHERE `nombre_interno` LIKE 'horas_cobrables' AND `grupo` like 'Detalle profesional';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Hr. Tarificadas' WHERE `nombre_interno` LIKE 'horas_tarificadas' AND `grupo` like 'Detalle profesional';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Tarifa HH. (%descri��o_moeda%)' WHERE `nombre_interno` LIKE 'tarifa_hh' AND `grupo` like 'Detalle profesional';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Total (%descri��o_moeda%)' WHERE `nombre_interno` LIKE 'total' AND `grupo` like 'Detalle profesional';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Gastos' WHERE `nombre_interno` LIKE 'titulo' AND `grupo` like 'Listado de gastos';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Data' WHERE `nombre_interno` LIKE 'fecha' AND `grupo` like 'Listado de gastos';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Descri��o' WHERE `nombre_interno` LIKE 'descripcion' AND `grupo` like 'Listado de gastos';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Montante' WHERE `nombre_interno` LIKE 'monto' AND `grupo` like 'Listado de gastos';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Hr. Retainer' WHERE `nombre_interno` LIKE 'horas_retainer' AND `grupo` like 'Detalle profesional';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Fatura N�' WHERE `nombre_interno` LIKE 'factura' AND `grupo` like 'Resumen';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Minuta da cobran�a N�' WHERE `nombre_interno` LIKE 'minuta' AND `grupo` like 'Encabezado';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Tr�mites' WHERE `nombre_interno` LIKE 'titulo' AND `grupo` like 'Listado de tr�mites';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Data' WHERE `nombre_interno` LIKE 'fecha' AND `grupo` like 'Listado de tr�mites';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'N�' WHERE `nombre_interno` LIKE 'id_trabajo' AND `grupo` like 'Listado de tr�mites';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Advogado' WHERE `nombre_interno` LIKE 'abogado' AND `grupo` like 'Listado de tr�mites';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Assunto' WHERE `nombre_interno` LIKE 'asunto' AND `grupo` like 'Listado de tr�mites';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Solicitante' WHERE `nombre_interno` LIKE 'solicitante' AND `grupo` like 'Listado de tr�mites';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Descri��o' WHERE `nombre_interno` LIKE 'descripcion' AND `grupo` like 'Listado de tr�mites';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Dura��o' WHERE `nombre_interno` LIKE 'duracion' AND `grupo` like 'Listado de tr�mites';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Valor' WHERE `nombre_interno` LIKE 'valor' AND `grupo` like 'Listado de tr�mites';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Dia' WHERE `nombre_interno` LIKE 'fecha_dia' AND `grupo` like 'Listado de trabajos';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'M�s' WHERE `nombre_interno` LIKE 'fecha_mes' AND `grupo` like 'Listado de trabajos';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Ano' WHERE `nombre_interno` LIKE 'fecha_anyo' AND `grupo` like 'Listado de trabajos';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Dia' WHERE `nombre_interno` LIKE 'fecha_dia' AND `grupo` like 'Listado de tr�mites';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'M�s' WHERE `nombre_interno` LIKE 'fecha_mes' AND `grupo` like 'Listado de tr�mites';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Ano' WHERE `nombre_interno` LIKE 'fecha_anyo' AND `grupo` like 'Listado de tr�mites';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Senhores' WHERE `nombre_interno` LIKE 'senores' AND `grupo` like 'Encabezado';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Dia' WHERE `nombre_interno` LIKE 'fecha_dia' AND `grupo` like 'Listado de trabajos';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'M�s' WHERE `nombre_interno` LIKE 'fecha_mes' AND `grupo` like 'Listado de trabajos';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Dia' WHERE `nombre_interno` LIKE 'fecha_dia' AND `grupo` like 'Listado de trabajos';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'M�s' WHERE `nombre_interno` LIKE 'fecha_mes' AND `grupo` like 'Listado de trabajos';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Ano' WHERE `nombre_interno` LIKE 'fecha_anyo' AND `grupo` like 'Listado de trabajos';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Gastos n�o afetados ao IVA' WHERE `nombre_interno` LIKE 'gastos_sin_iva' AND `grupo` like 'Resumen';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Solicitante' WHERE `nombre_interno` LIKE 'solicitante' AND `grupo` like 'Listado de gastos';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Descri��o Fatura' WHERE `nombre_interno` LIKE 'glosa_factura' AND `grupo` like 'Encabezado';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Encarregado comercial' WHERE `nombre_interno` LIKE 'encargado_comercial' AND `grupo` like 'Encabezado';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Conceito' WHERE `nombre_interno` LIKE 'concepto' AND `grupo` like 'Encabezado';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Servi�os Profissionais prestados � companhia durante o m�s de' WHERE `nombre_interno` LIKE 'concepto_glosa' AND `grupo` like 'Encabezado';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Detalhe Cobran�a' WHERE `nombre_interno` LIKE 'detalle_cobranza' AND `grupo` like 'Encabezado';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Montante escalada' WHERE `nombre_interno` LIKE 'bruto_escalonada' AND `grupo` like 'Resumen';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Desconto escalada' WHERE `nombre_interno` LIKE 'descuento_escalonada' AND `grupo` like 'Resumen';",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Valor neto escaladas' WHERE `nombre_interno` LIKE 'neto_escalonada' AND `grupo` like 'Resumen';"
	);

	/**
   * Run the migrations.
   * @return void
   */
	function up() {
		$this->addQueryUp("ALTER TABLE `prm_excel_cobro` ADD `glosa_pt` VARCHAR(255) AFTER `glosa_en`;");

		foreach ($this->sqls as $value) {
			$this->addQueryUp($value);
		}
	}

	/**
   * Reverse the migrations.
   * @return void
   */
	function down() {
		$this->addQueryDown("ALTER TABLE `prm_excel_cobro` DROP `glosa_pt`;");
	}
}
