<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';

	$sesion = new Sesion();
	$pedazo = utf8_decode(addslashes($_POST['glosa_cliente']));
	
if(!$pedazo)
	{
		$query = "SELECT DISTINCT SUBSTRING(codigo_asunto, 1, 4) AS codigo_cliente
					FROM trabajo
					WHERE id_usuario='".$_POST['id_usuario']."'
					ORDER BY fecha DESC
					LIMIT 5";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		$where = '';
		while(list($codigo_cliente) = mysql_fetch_array($resp))
		{
			$where .= "codigo_cliente='$codigo_cliente' OR ";
		}
		$where .= 0;
		$query = "SELECT codigo_cliente, glosa_cliente
				FROM cliente
				WHERE $where";
	}
else if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) )
	{
	$query = "SELECT codigo_cliente_secundario, glosa_cliente
			FROM cliente
			WHERE activo=1 AND glosa_cliente LIKE '%$pedazo%' 
			ORDER BY glosa_cliente
			LIMIT 10";
	
	}
else
	{
	$query = "SELECT codigo_cliente, glosa_cliente
			FROM cliente
			WHERE activo=1 AND glosa_cliente LIKE '%$pedazo%'
			ORDER BY glosa_cliente
			LIMIT 10";
	}
	
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	echo '<ul>';
	$hay_resultados = false;
	while(list($codigo_cliente, $glosa_cliente) = mysql_fetch_array($resp))
		{
			echo "<li id='$codigo_cliente'>$glosa_cliente</li>";
			$hay_resultados = true;
		}
	if(!$hay_resultados)
		echo "<li id='cualquiera'>".__('Cualquiera')."</li>";
	echo '</ul>';
	
?>
