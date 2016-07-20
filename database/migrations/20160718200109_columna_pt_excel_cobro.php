<?php

namespace Database;

class ColumnaPtExcelCobro extends \Database\Migration implements \Database\ITemplateMigration {

	private $sqls = array(
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'No' WHERE `nombre_interno` LIKE 'id_trabajo' AND `grupo` LIKE 'Listado de trabajos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Data' WHERE `nombre_interno` LIKE 'fecha' AND `grupo` LIKE 'Listado de trabajos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Advogado' WHERE `nombre_interno` LIKE 'abogado' AND `grupo` LIKE 'Listado de trabajos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Assunto' WHERE `nombre_interno` LIKE 'asunto' AND `grupo` LIKE 'Listado de trabajos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Solicitante' WHERE `nombre_interno` LIKE 'solicitante' AND `grupo` LIKE 'Listado de trabajos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Descrição' WHERE `nombre_interno` LIKE 'descripcion' AND `grupo` LIKE 'Listado de trabajos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Duração trabalhada' WHERE `nombre_interno` LIKE 'duracion_trabajada' AND `grupo` LIKE 'Listado de trabajos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Duração' WHERE `nombre_interno` LIKE 'duracion_cobrable' AND `grupo` LIKE 'Listado de trabajos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Duração Retainer' WHERE `nombre_interno` LIKE 'duracion_retainer' AND `grupo` LIKE 'Listado de trabajos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Cobrável' WHERE `nombre_interno` LIKE 'cobrable' AND `grupo` LIKE 'Listado de trabajos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Tarifa (%descrição_moeda%)' WHERE `nombre_interno` LIKE 'tarifa_hh' AND `grupo` LIKE 'Listado de trabajos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Valor (%descrição_moeda%)' WHERE `nombre_interno` LIKE 'valor_trabajo' AND `grupo` LIKE 'Listado de trabajos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Cliente' WHERE `nombre_interno` LIKE 'cliente' AND `grupo` LIKE 'Encabezado'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Endereço' WHERE `nombre_interno` LIKE 'direccion' AND `grupo` LIKE 'Encabezado'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'CPF (persona) CNPJ (empresa)' WHERE `nombre_interno` LIKE 'rut' AND `grupo` LIKE 'Encabezado'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Contato' WHERE `nombre_interno` LIKE 'contacto' AND `grupo` LIKE 'Encabezado'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Telefone' WHERE `nombre_interno` LIKE 'telefono' AND `grupo` LIKE 'Encabezado'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Resumo Cobrança' WHERE `nombre_interno` LIKE 'titulo' AND `grupo` LIKE 'Resumen'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Data:' WHERE `nombre_interno` LIKE 'fecha' AND `grupo` LIKE 'Resumen'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Data de:' WHERE `nombre_interno` LIKE 'fecha_desde' AND `grupo` LIKE 'Resumen'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Data até:' WHERE `nombre_interno` LIKE 'fecha_hasta' AND `grupo` LIKE 'Resumen'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Tipo de Honorários:' WHERE `nombre_interno` LIKE 'forma_cobro' AND `grupo` LIKE 'Resumen'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Horas Retainer' WHERE `nombre_interno` LIKE 'horas_retainer' AND `grupo` LIKE 'Resumen'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Montante Retainer' WHERE `nombre_interno` LIKE 'monto_retainer' AND `grupo` LIKE 'Resumen'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Montante inicial CAP' WHERE `nombre_interno` LIKE 'monto_cap_inicial' AND `grupo` LIKE 'Resumen'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Montante CAP utilizado' WHERE `nombre_interno` LIKE 'monto_cap_usado' AND `grupo` LIKE 'Resumen'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Montante restante CAP' WHERE `nombre_interno` LIKE 'monto_cap_restante' AND `grupo` LIKE 'Resumen'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Total horas:' WHERE `nombre_interno` LIKE 'total_horas' AND `grupo` LIKE 'Resumen'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Honorários:' WHERE `nombre_interno` LIKE 'honorarios' AND `grupo` LIKE 'Resumen'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Equivalente a:' WHERE `nombre_interno` LIKE 'equivalente' AND `grupo` LIKE 'Resumen'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Desconto:' WHERE `nombre_interno` LIKE 'descuento' AND `grupo` LIKE 'Resumen'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Subtotal:' WHERE `nombre_interno` LIKE 'subtotal' AND `grupo` LIKE 'Resumen'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Gastos:' WHERE `nombre_interno` LIKE 'gastos' AND `grupo` LIKE 'Resumen'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Imposto:' WHERE `nombre_interno` LIKE 'impuesto' AND `grupo` LIKE 'Resumen'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Total cobrança:' WHERE `nombre_interno` LIKE 'total_cobro' AND `grupo` LIKE 'Resumen'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Detalhe Profissional' WHERE `nombre_interno` LIKE 'titulo' AND `grupo` LIKE 'Detalle profesional'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Nome' WHERE `nombre_interno` LIKE 'nombre' AND `grupo` LIKE 'Detalle profesional'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Hr. Trabalhadas' WHERE `nombre_interno` LIKE 'horas_trabajadas' AND `grupo` LIKE 'Detalle profesional'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Hr. Cobrabráveis' WHERE `nombre_interno` LIKE 'horas_cobrables' AND `grupo` LIKE 'Detalle profesional'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Hr. Tarificadas' WHERE `nombre_interno` LIKE 'horas_tarificadas' AND `grupo` LIKE 'Detalle profesional'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Tarifa HH. (%descrição_moeda%)' WHERE `nombre_interno` LIKE 'tarifa_hh' AND `grupo` LIKE 'Detalle profesional'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Total (%descrição_moeda%)' WHERE `nombre_interno` LIKE 'total' AND `grupo` LIKE 'Detalle profesional'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Gastos' WHERE `nombre_interno` LIKE 'titulo' AND `grupo` LIKE 'Listado de gastos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Data' WHERE `nombre_interno` LIKE 'fecha' AND `grupo` LIKE 'Listado de gastos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Descrição' WHERE `nombre_interno` LIKE 'descripcion' AND `grupo` LIKE 'Listado de gastos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Montante' WHERE `nombre_interno` LIKE 'monto' AND `grupo` LIKE 'Listado de gastos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Hr. Retainer' WHERE `nombre_interno` LIKE 'horas_retainer' AND `grupo` LIKE 'Detalle profesional'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Fatura N°' WHERE `nombre_interno` LIKE 'factura' AND `grupo` LIKE 'Resumen'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Minuta da cobrança N°' WHERE `nombre_interno` LIKE 'minuta' AND `grupo` LIKE 'Encabezado'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Trâmites' WHERE `nombre_interno` LIKE 'titulo' AND `grupo` LIKE 'Listado de trámites'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Data' WHERE `nombre_interno` LIKE 'fecha' AND `grupo` LIKE 'Listado de trámites'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'No' WHERE `nombre_interno` LIKE 'id_trabajo' AND `grupo` LIKE 'Listado de trámites'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Advogado' WHERE `nombre_interno` LIKE 'abogado' AND `grupo` LIKE 'Listado de trámites'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Assunto' WHERE `nombre_interno` LIKE 'asunto' AND `grupo` LIKE 'Listado de trámites'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Solicitante' WHERE `nombre_interno` LIKE 'solicitante' AND `grupo` LIKE 'Listado de trámites'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Descrição' WHERE `nombre_interno` LIKE 'descripcion' AND `grupo` LIKE 'Listado de trámites'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Duração' WHERE `nombre_interno` LIKE 'duracion' AND `grupo` LIKE 'Listado de trámites'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Valor' WHERE `nombre_interno` LIKE 'valor' AND `grupo` LIKE 'Listado de trámites'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Dia' WHERE `nombre_interno` LIKE 'fecha_dia' AND `grupo` LIKE 'Listado de trabajos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Mês' WHERE `nombre_interno` LIKE 'fecha_mes' AND `grupo` LIKE 'Listado de trabajos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Ano' WHERE `nombre_interno` LIKE 'fecha_anyo' AND `grupo` LIKE 'Listado de trabajos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Dia' WHERE `nombre_interno` LIKE 'fecha_dia' AND `grupo` LIKE 'Listado de trámites'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Mês' WHERE `nombre_interno` LIKE 'fecha_mes' AND `grupo` LIKE 'Listado de trámites'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Ano' WHERE `nombre_interno` LIKE 'fecha_anyo' AND `grupo` LIKE 'Listado de trámites'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Senhores' WHERE `nombre_interno` LIKE 'senores' AND `grupo` LIKE 'Encabezado'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Dia' WHERE `nombre_interno` LIKE 'fecha_dia' AND `grupo` LIKE 'Listado de trabajos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Mês' WHERE `nombre_interno` LIKE 'fecha_mes' AND `grupo` LIKE 'Listado de trabajos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Dia' WHERE `nombre_interno` LIKE 'fecha_dia' AND `grupo` LIKE 'Listado de trabajos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Mês' WHERE `nombre_interno` LIKE 'fecha_mes' AND `grupo` LIKE 'Listado de trabajos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Ano' WHERE `nombre_interno` LIKE 'fecha_anyo' AND `grupo` LIKE 'Listado de trabajos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Gastos não afetados ao IVA' WHERE `nombre_interno` LIKE 'gastos_sin_iva' AND `grupo` LIKE 'Resumen'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Solicitante' WHERE `nombre_interno` LIKE 'solicitante' AND `grupo` LIKE 'Listado de gastos'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Descrição Fatura' WHERE `nombre_interno` LIKE 'glosa_factura' AND `grupo` LIKE 'Encabezado'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Encarregado comercial' WHERE `nombre_interno` LIKE 'encargado_comercial' AND `grupo` LIKE 'Encabezado'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Conceito' WHERE `nombre_interno` LIKE 'concepto' AND `grupo` LIKE 'Encabezado'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Serviços Profissionais prestados à companhia durante o mês de' WHERE `nombre_interno` LIKE 'concepto_glosa' AND `grupo` LIKE 'Encabezado'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Detalhe Cobrança' WHERE `nombre_interno` LIKE 'detalle_cobranza' AND `grupo` LIKE 'Encabezado'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Montante escalada' WHERE `nombre_interno` LIKE 'bruto_escalonada' AND `grupo` LIKE 'Resumen'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Desconto escalada' WHERE `nombre_interno` LIKE 'descuento_escalonada' AND `grupo` LIKE 'Resumen'",
		"UPDATE `prm_excel_cobro` SET glosa_pt = 'Valor neto escaladas' WHERE `nombre_interno` LIKE 'neto_escalonada' AND `grupo` LIKE 'Resumen'"
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
				`glosa_pt` = 'Não modificar esta coluna'");
	}

	/**
   * Reverse the migrations.
   * @return void
   */
	function down() {
		$this->addQueryDown("ALTER TABLE `prm_excel_cobro` DROP `glosa_pt`");
	}
}
