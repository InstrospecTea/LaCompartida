<?php

/*
 *Este plugin agrega la capacidad de filtrar por encargado del asunto, por ejemplo en seguimiento cobro
 *
 */


$Slim=Slim::getInstance('default',true);
$Slim->hook('hook_filtros_seguimiento_cobro', 'Filtros_Seguimiento_Cobro');
$Slim->hook('hook_query_seguimiento_cobro', 'Query_Seguimiento_cobro');

$Slim->hook('hook_query_generar_cobro', 'Query_Generacion_cobro');




function Filtros_Seguimiento_Cobro() {
	global $sesion,$_LANG,$query_usuario_activo,$id_encargado_asunto;
	echo ' <tr>
			<td align=right><b>Encargado '. __('Asunto').'&nbsp;</b></td>';
			echo '<td colspan="2" align="left">'. Html::SelectQuery($sesion,$query_usuario_activo,"id_encargado_asunto",$id_encargado_asunto, '',__('Cualquiera'),'210');
		echo '
			</td>
		</tr>';


}

	function Query_Seguimiento_cobro() {
		global $sesion,$_POST,$query,$where;

		 if(!empty($_POST['id_encargado_asunto']))  $where.=" AND asunto.id_encargado=".intval($_POST['id_encargado_asunto']);

	}


		function Query_Generacion_cobro() {
		global $sesion,$_POST,$query,$where;

		 if(!empty($_POST['id_encargado_asunto']))  $where.=" AND contrato.id_contrato in (select id_contrato from asunto where asunto.id_encargado=".intval($_POST['id_encargado_asunto']).") ";

	}
