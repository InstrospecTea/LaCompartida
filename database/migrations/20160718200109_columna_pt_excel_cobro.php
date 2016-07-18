<?php

namespace Database;

class ColumnaPtExcelCobro extends \Database\Migration implements \Database\ITemplateMigration {

	private $relations = array(
		'id_trabajo' => 'N�',
		'fecha' => 'Data',
		'abogado' => 'Advogado',
		'asunto' => 'Assunto',
		'solicitante' => 'Solicitante',
		'descripcion' => 'Descri��o',
		'duracion_trabajada' => 'Dura��o trabalhada',
		'duracion_cobrable' => 'Dura��o',
		'duracion_retainer' => 'Dura��o Retainer',
		'cobrable' => 'Cobr�vel',
		'tarifa_hh' => 'Tarifa (%descri��o_moeda%)',
		'valor_trabajo' => 'Valor (%descri��o_moeda%)',
		'cliente' => 'Cliente',
		'direccion' => 'Endere�o',
		'rut' => 'CPF (persona) CNPJ (empresa)',
		'contacto' => 'Contato',
		'telefono' => 'Telefone',
		'titulo' => 'Resumo Cobran�a',
		'fecha_desde' => 'Data de:',
		'fecha_hasta' => 'Data at�:',
		'forma_cobro' => 'Tipo de Honor�rios:',
		'horas_retainer' => 'Horas Retainer',
		'monto_retainer' => 'Montante Retainer',
		'monto_cap_inicial' => 'Montante inicial CAP',
		'monto_cap_usado' => 'Montante CAP utilizado',
		'monto_cap_restante' => 'Montante restante CAP',
		'total_horas' => 'Total horas:',
		'honorarios' => 'Honor�rios:',
		'equivalente' => 'Equivalente a:',
		'descuento' => 'Desconto:',
		'subtotal' => 'Subtotal:',
		'gastos' => 'Gastos:',
		'impuesto' => 'Imposto:',
		'total_cobro' => 'Total cobran�a:',
		'nombre' => 'Nome',
		'horas_trabajadas' => 'Hr. Trabalhadas',
		'horas_cobrables' => 'Hr. Cobrabr�veis',
		'horas_tarificadas' => 'Hr. Tarificadas',
		'total' => 'Total (%descri��o_moeda%)',
		'titulo' => 'Gastos',
		'monto' => 'Montante',
		'horas_retainer' => 'Hr. Retainer',
		'factura' => 'Fatura N�',
		'minuta' => 'Minuta da cobran�a N�',
		'titulo' => 'Tr�mites',
		'duracion' => 'Dura��o',
		'valor' => 'Valor',
		'fecha_mes' => 'M�s',
		'fecha_anyo' => 'Ano',
		'fecha_dia' => 'Dia',
		'senores' => 'Senhores',
		'gastos_sin_iva' => 'Gastos n�o afetados ao IVA',
		'glosa_factura' => 'Descri��o Fatura',
		'encargado_comercial' => 'Encarregado comercial',
		'concepto' => 'Conceito',
		'concepto_glosa' => 'Servi�os Profissionais prestados � companhia durante o m�s de ',
		'detalle_cobranza' => 'Detalhe Cobran�a',
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
