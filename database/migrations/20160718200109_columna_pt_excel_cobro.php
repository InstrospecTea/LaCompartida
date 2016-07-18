<?php

namespace Database;

class ColumnaPtExcelCobro extends \Database\Migration implements \Database\ITemplateMigration {

	private $relations = array(
		'id_trabajo' => 'N°',
		'fecha' => 'Data',
		'abogado' => 'Advogado',
		'asunto' => 'Assunto',
		'solicitante' => 'Solicitante',
		'descripcion' => 'Descrição',
		'duracion_trabajada' => 'Duração trabalhada',
		'duracion_cobrable' => 'Duração',
		'duracion_retainer' => 'Duração Retainer',
		'cobrable' => 'Cobrável',
		'tarifa_hh' => 'Tarifa (%descrição_moeda%)',
		'valor_trabajo' => 'Valor (%descrição_moeda%)',
		'cliente' => 'Cliente',
		'direccion' => 'Endereço',
		'rut' => 'CPF (persona) CNPJ (empresa)',
		'contacto' => 'Contato',
		'telefono' => 'Telefone',
		'titulo' => 'Resumo Cobrança',
		'fecha_desde' => 'Data de:',
		'fecha_hasta' => 'Data até:',
		'forma_cobro' => 'Tipo de Honorários:',
		'horas_retainer' => 'Horas Retainer',
		'monto_retainer' => 'Montante Retainer',
		'monto_cap_inicial' => 'Montante inicial CAP',
		'monto_cap_usado' => 'Montante CAP utilizado',
		'monto_cap_restante' => 'Montante restante CAP',
		'total_horas' => 'Total horas:',
		'honorarios' => 'Honorários:',
		'equivalente' => 'Equivalente a:',
		'descuento' => 'Desconto:',
		'subtotal' => 'Subtotal:',
		'gastos' => 'Gastos:',
		'impuesto' => 'Imposto:',
		'total_cobro' => 'Total cobrança:',
		'nombre' => 'Nome',
		'horas_trabajadas' => 'Hr. Trabalhadas',
		'horas_cobrables' => 'Hr. Cobrabráveis',
		'horas_tarificadas' => 'Hr. Tarificadas',
		'total' => 'Total (%descrição_moeda%)',
		'titulo' => 'Gastos',
		'monto' => 'Montante',
		'horas_retainer' => 'Hr. Retainer',
		'factura' => 'Fatura N°',
		'minuta' => 'Minuta da cobrança N°',
		'titulo' => 'Trâmites',
		'duracion' => 'Duração',
		'valor' => 'Valor',
		'fecha_mes' => 'Mês',
		'fecha_anyo' => 'Ano',
		'fecha_dia' => 'Dia',
		'senores' => 'Senhores',
		'gastos_sin_iva' => 'Gastos não afetados ao IVA',
		'glosa_factura' => 'Descrição Fatura',
		'encargado_comercial' => 'Encarregado comercial',
		'concepto' => 'Conceito',
		'concepto_glosa' => 'Serviços Profissionais prestados à companhia durante o mês de ',
		'detalle_cobranza' => 'Detalhe Cobrança',
		'bruto_escalonada' => 'Montante escalada',
		'descuento_escalonada' => 'Desconto escalada',
		'neto_escalonada' => 'Valor neto escaladas'
	);

	/**
   * Run the migrations.
   * @return void
   */
	function up() {
		$this->addQueryUp("ALTER TABLE `prm_excel_cobro` ADD `glosa_pt` VARCHAR(255) AFTER `glosa_en`;");

		foreach ($this->relations as $key => $value) {
			$this->addQueryUp("UPDATE `prm_excel_cobro` SET glosa_pt = '{$value}' WHERE `nombre_interno` LIKE '{$key}';");
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
