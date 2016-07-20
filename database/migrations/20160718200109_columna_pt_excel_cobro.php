<?php

namespace Database;

class ColumnaPtExcelCobro extends \Database\Migration implements \Database\ITemplateMigration {

	private $sqls = array(
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'No' WHERE `nombre_interno` = 'id_trabajo' AND `grupo` = 'Listado de trabajos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Data' WHERE `nombre_interno` = 'fecha' AND `grupo` = 'Listado de trabajos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Advogado' WHERE `nombre_interno` = 'abogado' AND `grupo` = 'Listado de trabajos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Assunto' WHERE `nombre_interno` = 'asunto' AND `grupo` = 'Listado de trabajos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Solicitante' WHERE `nombre_interno` = 'solicitante' AND `grupo` = 'Listado de trabajos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Descri��o' WHERE `nombre_interno` = 'descripcion' AND `grupo` = 'Listado de trabajos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Dura��o trabalhada' WHERE `nombre_interno` = 'duracion_trabajada' AND `grupo` = 'Listado de trabajos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Dura��o' WHERE `nombre_interno` = 'duracion_cobrable' AND `grupo` = 'Listado de trabajos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Dura��o Retainer' WHERE `nombre_interno` = 'duracion_retainer' AND `grupo` = 'Listado de trabajos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Cobr�vel' WHERE `nombre_interno` = 'cobrable' AND `grupo` = 'Listado de trabajos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Tarifa (%descri��o_moeda%)' WHERE `nombre_interno` = 'tarifa_hh' AND `grupo` = 'Listado de trabajos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Valor (%descri��o_moeda%)' WHERE `nombre_interno` = 'valor_trabajo' AND `grupo` = 'Listado de trabajos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Cliente' WHERE `nombre_interno` = 'cliente' AND `grupo` = 'Encabezado'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Endere�o' WHERE `nombre_interno` = 'direccion' AND `grupo` = 'Encabezado'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'CPF (persona) CNPJ (empresa)' WHERE `nombre_interno` = 'rut' AND `grupo` = 'Encabezado'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Contato' WHERE `nombre_interno` = 'contacto' AND `grupo` = 'Encabezado'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Telefone' WHERE `nombre_interno` = 'telefono' AND `grupo` = 'Encabezado'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Resumo Cobran�a' WHERE `nombre_interno` = 'titulo' AND `grupo` = 'Resumen'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Data:' WHERE `nombre_interno` = 'fecha' AND `grupo` = 'Resumen'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Data de:' WHERE `nombre_interno` = 'fecha_desde' AND `grupo` = 'Resumen'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Data at�:' WHERE `nombre_interno` = 'fecha_hasta' AND `grupo` = 'Resumen'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Tipo de Honor�rios:' WHERE `nombre_interno` = 'forma_cobro' AND `grupo` = 'Resumen'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Horas Retainer' WHERE `nombre_interno` = 'horas_retainer' AND `grupo` = 'Resumen'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Montante Retainer' WHERE `nombre_interno` = 'monto_retainer' AND `grupo` = 'Resumen'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Montante inicial CAP' WHERE `nombre_interno` = 'monto_cap_inicial' AND `grupo` = 'Resumen'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Montante CAP utilizado' WHERE `nombre_interno` = 'monto_cap_usado' AND `grupo` = 'Resumen'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Montante restante CAP' WHERE `nombre_interno` = 'monto_cap_restante' AND `grupo` = 'Resumen'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Total horas:' WHERE `nombre_interno` = 'total_horas' AND `grupo` = 'Resumen'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Honor�rios:' WHERE `nombre_interno` = 'honorarios' AND `grupo` = 'Resumen'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Equivalente a:' WHERE `nombre_interno` = 'equivalente' AND `grupo` = 'Resumen'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Desconto:' WHERE `nombre_interno` = 'descuento' AND `grupo` = 'Resumen'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Subtotal:' WHERE `nombre_interno` = 'subtotal' AND `grupo` = 'Resumen'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Gastos:' WHERE `nombre_interno` = 'gastos' AND `grupo` = 'Resumen'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Imposto:' WHERE `nombre_interno` = 'impuesto' AND `grupo` = 'Resumen'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Total cobran�a:' WHERE `nombre_interno` = 'total_cobro' AND `grupo` = 'Resumen'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Detalhe Profissional' WHERE `nombre_interno` = 'titulo' AND `grupo` = 'Detalle profesional'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Nome' WHERE `nombre_interno` = 'nombre' AND `grupo` = 'Detalle profesional'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Hr. Trabalhadas' WHERE `nombre_interno` = 'horas_trabajadas' AND `grupo` = 'Detalle profesional'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Hr. Cobrabr�veis' WHERE `nombre_interno` = 'horas_cobrables' AND `grupo` = 'Detalle profesional'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Hr. Tarificadas' WHERE `nombre_interno` = 'horas_tarificadas' AND `grupo` = 'Detalle profesional'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Tarifa HH. (%descri��o_moeda%)' WHERE `nombre_interno` = 'tarifa_hh' AND `grupo` = 'Detalle profesional'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Total (%descri��o_moeda%)' WHERE `nombre_interno` = 'total' AND `grupo` = 'Detalle profesional'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Gastos' WHERE `nombre_interno` = 'titulo' AND `grupo` = 'Listado de gastos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Data' WHERE `nombre_interno` = 'fecha' AND `grupo` = 'Listado de gastos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Descri��o' WHERE `nombre_interno` = 'descripcion' AND `grupo` = 'Listado de gastos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Montante' WHERE `nombre_interno` = 'monto' AND `grupo` = 'Listado de gastos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Hr. Retainer' WHERE `nombre_interno` = 'horas_retainer' AND `grupo` = 'Detalle profesional'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Fatura N�' WHERE `nombre_interno` = 'factura' AND `grupo` = 'Resumen'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Minuta da cobran�a N�' WHERE `nombre_interno` = 'minuta' AND `grupo` = 'Encabezado'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Tr�mites' WHERE `nombre_interno` = 'titulo' AND `grupo` = 'Listado de tr�mites'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Data' WHERE `nombre_interno` = 'fecha' AND `grupo` = 'Listado de tr�mites'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'No' WHERE `nombre_interno` = 'id_trabajo' AND `grupo` = 'Listado de tr�mites'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Advogado' WHERE `nombre_interno` = 'abogado' AND `grupo` = 'Listado de tr�mites'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Assunto' WHERE `nombre_interno` = 'asunto' AND `grupo` = 'Listado de tr�mites'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Solicitante' WHERE `nombre_interno` = 'solicitante' AND `grupo` = 'Listado de tr�mites'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Descri��o' WHERE `nombre_interno` = 'descripcion' AND `grupo` = 'Listado de tr�mites'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Dura��o' WHERE `nombre_interno` = 'duracion' AND `grupo` = 'Listado de tr�mites'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Valor' WHERE `nombre_interno` = 'valor' AND `grupo` = 'Listado de tr�mites'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Dia' WHERE `nombre_interno` = 'fecha_dia' AND `grupo` = 'Listado de trabajos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'M�s' WHERE `nombre_interno` = 'fecha_mes' AND `grupo` = 'Listado de trabajos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Ano' WHERE `nombre_interno` = 'fecha_anyo' AND `grupo` = 'Listado de trabajos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Dia' WHERE `nombre_interno` = 'fecha_dia' AND `grupo` = 'Listado de tr�mites'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'M�s' WHERE `nombre_interno` = 'fecha_mes' AND `grupo` = 'Listado de tr�mites'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Ano' WHERE `nombre_interno` = 'fecha_anyo' AND `grupo` = 'Listado de tr�mites'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Senhores' WHERE `nombre_interno` = 'senores' AND `grupo` = 'Encabezado'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Dia' WHERE `nombre_interno` = 'fecha_dia' AND `grupo` = 'Listado de trabajos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'M�s' WHERE `nombre_interno` = 'fecha_mes' AND `grupo` = 'Listado de trabajos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Dia' WHERE `nombre_interno` = 'fecha_dia' AND `grupo` = 'Listado de trabajos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'M�s' WHERE `nombre_interno` = 'fecha_mes' AND `grupo` = 'Listado de trabajos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Ano' WHERE `nombre_interno` = 'fecha_anyo' AND `grupo` = 'Listado de trabajos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Gastos n�o afetados ao IVA' WHERE `nombre_interno` = 'gastos_sin_iva' AND `grupo` = 'Resumen'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Solicitante' WHERE `nombre_interno` = 'solicitante' AND `grupo` = 'Listado de gastos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Descri��o Fatura' WHERE `nombre_interno` = 'glosa_factura' AND `grupo` = 'Encabezado'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Encarregado comercial' WHERE `nombre_interno` = 'encargado_comercial' AND `grupo` = 'Encabezado'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Conceito' WHERE `nombre_interno` = 'concepto' AND `grupo` = 'Encabezado'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Servi�os Profissionais prestados � companhia durante o m�s de' WHERE `nombre_interno` = 'concepto_glosa' AND `grupo` = 'Encabezado'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Detalhe Cobran�a' WHERE `nombre_interno` = 'detalle_cobranza' AND `grupo` = 'Encabezado'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Montante escalada' WHERE `nombre_interno` = 'bruto_escalonada' AND `grupo` = 'Resumen'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Desconto escalada' WHERE `nombre_interno` = 'descuento_escalonada' AND `grupo` = 'Resumen'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Valor neto escaladas' WHERE `nombre_interno` = 'neto_escalonada' AND `grupo` = 'Resumen'"
	);

	/**
   * Run the migrations.
   * @return void
   */
	function up() {
		$this->addQueryUp("ALTER TABLE `prm_excel_cobro` ADD `glosa_pt` VARCHAR(255) AFTER `glosa_en`");

		foreach ($this->sqls as $value) {
			$this->addQueryUp($value);
		}

		$this->addQueryUp("INSERT INTO `prm_excel_cobro`
			SET
				`nombre_interno` = 'no_modificar',
				`grupo` = 'General',
				`glosa_es` = 'NO MODIFICAR ESTA COLUMNA',
				`glosa_en` = 'Do not modify this column',
				`glosa_pt` = 'N�o modificar esta coluna'");
	}

	/**
   * Reverse the migrations.
   * @return void
   */
	function down() {
		$this->addQueryDown("ALTER TABLE `prm_excel_cobro` DROP `glosa_pt`");
	}
}
