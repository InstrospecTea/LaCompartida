<?php

namespace Database;

class ConfiguracionFormatoExcelCobro extends \Database\Migration implements \Database\ITemplateMigration {

	/**
   * Run the migrations.
   * @return void
   */
	function up() {
		$this->addQueryUp("INSERT INTO `configuracion_categoria` (`id_configuracion_categoria`, `glosa_configuracion_categoria`) VALUES (15,'Formato Excel Cobro');");
		$query = <<<SQL
		INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`)
		VALUES
		(null,'FormatoExcelCobro_default', '[{"Size":7,"VAlign":"top","Color":"black","Align":"left"},{"FgColor":"38"},{"FgColor":"white"}]', '','string',15,0),
		(null,'FormatoExcelCobro_encabezado', '[{"Size":10,"VAlign":"middle","Bold":1}]', '','string',15,0),
		(null,'FormatoExcelCobro_encabezado2', '[{"Size":10,"VAlign":"middle"}]', '','string',15,0),
		(null,'FormatoExcelCobro_encabezado_derecha', '[{"Size":10,"Align":"right","Bold":1}]', '','string',15,0),
		(null,'FormatoExcelCobro_titulo', '[{"Size":10,"Bold":1,"Locked":1,"Bottom":1,"FgColor":35}]', '','string',15,0),
		(null,'FormatoExcelCobro_titulo_vcentrado', '[{"Size":10,"VAlign":"vjustify","FgColor":35,"Bottom":1,"Locked":1,"Bold":1}]', '','string',15,0),
		(null,'FormatoExcelCobro_normal_centrado', '[{"Align":"center"}]', '','string',15,0),
		(null,'FormatoExcelCobro_normal', '[]', '','string',15,0),
		(null,'FormatoExcelCobro_descripcion', '[{"TextWrap":1}]', '','string',15,0),
		(null,'FormatoExcelCobro_observacion', '[{"Bold":1,"Italic":1}]', '','string',15,0),
		(null,'FormatoExcelCobro_tiempo', '[{"NumFormat":"[h]:mm"}]', '','string',15,0),
		(null,'FormatoExcelCobro_total', '[{"Size":10,"Bold":1,"Top":1}]', '','string',15,0),
		(null,'FormatoExcelCobro_tiempo_total', '[{"Size":10,"Bold":1,"Top":1,"NumFormat":"[h]:mm"}]', '','string',15,0),
		(null,'FormatoExcelCobro_instrucciones12', '[{"Size":12,"Bold":1}]', '','string',15,0),
		(null,'FormatoExcelCobro_instrucciones10', '[{"Size":10,"Bold":1}]', '','string',15,0),
		(null,'FormatoExcelCobro_resumen_text', '[{"Border":1,"TextWrap":1}]', '','string',15,0),
		(null,'FormatoExcelCobro_resumen_text_derecha', '[{"Align":"right","Border":1}]', '','string',15,0),
		(null,'FormatoExcelCobro_resumen_text_titulo', '[{"Size":9,"Bold":1,"Border":1}]', '','string',15,0),
		(null,'FormatoExcelCobro_resumen_text_amarillo', '[{"Border":1,"FgColor":"37","TextWrap":1}]', '','string',15,0),
		(null,'FormatoExcelCobro_numeros', '[{"Border":1,"Align":"right","NumFormat":"0"}]', '','string',15,0),
		(null,'FormatoExcelCobro_numeros_amarillo', '[{"Border":1,"Align":"right","TextWrap":"0","FgColor":"37"}]', '','string',15,0),
		(null,'FormatoExcelCobro_numero_rut', '[{"Align":"right","NumFormat":"#"}]', '','string',15,0),
		(null,'FormatoExcelCobro_encabezado_numero_rut', '[{"Size":10,"VAlign":"middle","Bold":1,"NumFormat":"#"}]', '','string',15,0);
SQL;
		$this->addQueryUp($query);
	}

	/**
   * Reverse the migrations.
   * @return void
   */
	function down() {
		$this->addQueryDown('DELETE FROM configuracion WHERE id_configuracion_categoria = 15;');
		$this->addQueryDown('DELETE FROM configuracion_categoria WHERE id_configuracion_categoria = 15;');
	}
}
