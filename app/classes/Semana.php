<?
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/classes/Debug.php';

class Semana extends Objeto
{
	// Sesion PHP
    var $sesion = null;
		var $codigos = null;
		
	function Semana($sesion, $id_usuario = "", $usuarios = "")
	{
		$this->sesion = $sesion;
		if( $id_usuario )
			$condicion_usuario = " id_usuario = '".$id_usuario."' ";
		else if( is_array( $usuarios ) )
		{
			$lista_usuarios = implode(",",$usuarios);
			$condicion_usuario = " id_usuario IN (".$lista_usuarios.") ";
		}
		else
			$condicion_usuario = "";
			
		if( $condicion_usuario != "" )
			{
				$query = "SELECT DISTINCT SQL_CALC_FOUND_ROWS codigo_asunto FROM trabajo WHERE ".$condicion_usuario." ORDER BY fecha DESC";
				$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
				$num_asuntos = end(mysql_fetch_array(mysql_query('SELECT FOUND_ROWS()', $this->sesion->dbh)));
				
				$cont=0;
				$codigo_colores = $this->ArregloColores($num_asuntos);
				while(list($codigo_asunto)=mysql_fetch_array($resp))
				{
					$this->colores[$codigo_asunto]=$codigo_colores[$cont++];
				}
			}
	}
	
	function ArregloColores($num)
	{
		$query = "SELECT SQL_CALC_FOUND_ROWS codigo_color FROM prm_color";
		$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		$num_colores = end(mysql_fetch_array(mysql_query('SELECT FOUND_ROWS()', $this->sesion->dbh)));
		
		$cont = 0;
		$codigo_colores = array();
		while( list($codigo) = mysql_fetch_array($resp) )
		{
			$codigo_colores[$cont++] = $codigo;
		}
		$codigo = array();
		for($i=0;$i<$num;$i++)
		{
			$codigo[$i] = $codigo_colores[$i%$num_colores];
		}
		return $codigo;
	}
}