<?php
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';

	$sesion = new Sesion();

	// 'codigo_cliente' es el nombre del campo para el que se generan las sugerencias
	if(!empty($_POST['codigo_cliente']))
	{
		$query = "SELECT id_cliente, glosa_cliente
							FROM cliente
							WHERE (glosa_cliente LIKE '%". $_POST['codigo_cliente'] ."%')
							ORDER BY glosa_cliente ASC LIMIT 10";

		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query_f,__FILE__,__LINE__,$sesion->dbh);
		if(mysql_num_rows($resp)>0)
		{
			echo '	<div class="entry">'."\n";
			echo '		<ul>'."\n";
			while ( $row = mysql_fetch_assoc ( $resp ) )
			{
				echo "			<li>\n";
				echo '				<a href="#" onclick="document.getElementById(\'campo_codigo_cliente\').value=\''.sprintf("%04d", $row['id_cliente']).'\';document.getElementById(\'codigo_cliente\').value=\''.$row['glosa_cliente'].'\';CargarSelect(\'campo_codigo_cliente\',\'codigo_asunto\',\'cargar_asuntos\');">'.$row['glosa_cliente']."\n</a>\n";
				echo "			</li>\n";
			}
			echo "		</ul>\n";
			echo "	</div>\n";
		}
	}
?>