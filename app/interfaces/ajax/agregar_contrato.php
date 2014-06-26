<?php
require_once dirname(__FILE__) . '/../../conf.php';

$Sesion = new Sesion(array('DAT'));
$Criteria = new Criteria($Sesion);
$where = new CriteriaRestriction("id_contrato = '{$_POST['id_contrato']}' AND activo");
$Criteria
		->add_from('asunto')
		->add_restriction($where);

$Html = new \TTB\Html;
switch ($_POST['accion']) {
	case 'msg_desactivar':
		$asuntos = $Criteria
			->add_select('count(*)', 'total')
			->execute();
		$img = Conf::ImgDir() . '/alerta_16.gif';
		$ask = __('¿Está seguro de desactivar este contrato?');
		$msg1 = __('Ud. está desactivando este contrato, por lo tanto este contrato no aparecerá en la lista de la generación de ');
		$cobro = __('cobros');
		$msg_cobros = '';
		if ($asuntos[0]['total'] > 0) {
			$msg_cobros .= '<br/><br/>' . __('Esta acción desactivará') . " {$asuntos[0]['total']} " . __('asuntos');
			$a = $Html->link(__('ver detalle'), 'javascript:void(0)', array('onclick' => 'ver_cobros_contrato()'));
			$msg_cobros .= " ($a)";
		}
		$html = <<<HTML
			<img src="$img" alt="!"/>
			&nbsp;&nbsp;
			$msg1 $cobro.
			$msg_cobros
			<br/>
			<br/>
			<div style="color:#FF0000;">$ask:</div>
HTML;

		if (!empty($msg_cobros)) {
			$titulo = __('Asuntos') . ' del ' . __('contrato');
			$script = <<<SCRIPT
				function ver_cobros_contrato() {
					jQuery('<p/>')
							.attr('title', '{$titulo}')
							.load('ajax/agregar_contrato.php', {accion: 'listar_asuntos', id_contrato: '{$_POST['id_contrato']}'})
							.dialog({
								modal: true,
								width: 350,
								height: 300,
								'buttons': {
									'Cerrar': function() {
										jQuery(this).dialog('close');
									}
								}
							});
				};
SCRIPT;
			$html .= $Html->script_block($script);
		}
		echo $html;
		break;

	case 'listar_asuntos':
		if (Conf::GetConf($Sesion, 'CodigoSecundario')) {
			$Criteria->add_select('codigo_asunto_secundario', 'codigo');
		} else {
			$Criteria->add_select('codigo_asunto', 'codigo');
		}
		$asuntos = $Criteria
				->add_select('glosa_asunto', 'glosa')
				->add_ordering('glosa')
				->execute();
		$lis = array();
		foreach ($asuntos as $asunto) {
			$li_text = sprintf('%s - %s', $asunto['codigo'], $asunto['glosa']);
			$lis[] = $Html->Tag('li', $li_text);
		}
		$text = $Html->tag('strong', 'Los siguientes asuntos de desactivaran junto con el contrato');
		echo $Html->tag('p', $text) . $Html->tag('ul', implode('', $lis));
		break;
}