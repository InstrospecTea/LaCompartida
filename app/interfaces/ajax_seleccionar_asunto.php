<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';

	$sesion = new Sesion();
	$pedazo = utf8_decode($_POST['glosa_asunto']);
	$codigo_cliente = $_REQUEST['codigo_cliente'];
	
	if(!$pedazo)
	{
		$query = "SELECT DISTINCT SUBSTRING(codigo_asunto, 6, 4) AS codigo_asunto
					FROM trabajo
					WHERE id_usuario='".$_POST['id_usuario']."'
					ORDER BY fecha DESC
					LIMIT 5";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		$where = '';
		while(list($codigo_asunto) = mysql_fetch_array($resp))
		{
			$where .= "codigo_asunto='$codigo_asunto' OR ";
		}
		$where .= 0;
		$query = "SELECT codigo_asunto, glosa_asunto
				FROM asunto
				WHERE $where";
	}
	else if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) )
	{
		if( $codigo_cliente != '0')
		{
			$query = "SELECT codigo_asunto_secundario, glosa_asunto
					FROM asunto
					WHERE activo=1 AND glosa_asunto LIKE '%$pedazo%'
					AND codigo_cliente = '$codigo_cliente'
					ORDER BY glosa_asunto
					LIMIT 10";
		}
		else
		{
			$query = "SELECT codigo_asunto_secundario, glosa_asunto
					FROM asunto
					WHERE activo=1 AND glosa_asunto LIKE '%$pedazo%'
					AND 1 = 2
					ORDER BY glosa_asunto
					LIMIT 10";
		}
	
	}
	
	else
	{
		if( $codigo_cliente != '0')
		{
			$query = "SELECT codigo_asunto, glosa_asunto
					FROM asunto
					WHERE activo=1 AND glosa_asunto LIKE '%$pedazo%'
					AND codigo_cliente = '$codigo_cliente'
					ORDER BY glosa_asunto
					LIMIT 10";
		}
		else
		{
			$query = "SELECT codigo_asunto, glosa_asunto
					FROM asunto
					WHERE activo=1 AND glosa_asunto LIKE '%$pedazo%'
					AND 1 = 2
					ORDER BY glosa_asunto
					LIMIT 10";
		}
	}
	
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	echo '<ul>';
	if( mysql_num_rows ( $resp ) > 0 )
	{		
		$hay_resultados = false;
		while(list($codigo_asunto, $glosa_asunto) = mysql_fetch_array($resp))
			{
				echo "<li id='$codigo_asunto'>$glosa_asunto</li>";
				$hay_resultados = true;
			}
		if(!$hay_resultados)
			echo "<li id='cualquiera'>".__('Cualquiera')."</li>";
	}
	echo '</ul>';
	
?>
