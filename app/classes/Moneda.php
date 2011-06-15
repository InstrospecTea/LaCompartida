<?
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Lista.php';
require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';

class Moneda extends Objeto
{
	function Moneda($sesion, $fields = "", $params = "")
	{
		$this->tabla = "prm_moneda";
		$this->campo_id = "id_moneda";
		$this->sesion = $sesion;
		$this->fields = $fields;
		$this->guardar_fecha = false;
	}
}

class ListaMonedas extends Lista
{
    function ListaMonedas($sesion, $params, $query)
    {
        $this->Lista($sesion, 'Moneda', $params, $query);
    }
}

function GetMonedaBase(&$sesion)
{
	$query = "SELECT id_moneda FROM prm_moneda WHERE moneda_base = 1";
	$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	if(list($id_moneda)= mysql_fetch_array($resp))
		return $id_moneda;
  else
		return false;	
}

function GetMonedaTarifaPorDefecto(&$sesion)
{
	if( method_exists('Conf','GetConf') )
		{
			$query = "SELECT id_moneda FROM prm_moneda WHERE glosa_moneda = '".Conf::GetConf($sesion,'MonedaTarifaPorDefecto')."'";
			$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
			if(list($id_moneda) = mysql_fetch_array($resp))
				return $id_moneda;
			else
				return false;
		}
	else
		return false;
}

function GetTipoCambioMoneda (&$sesion, $id_moneda)
{
  $query = "SELECT tipo_cambio FROM prm_moneda WHERE id_moneda='$id_moneda'";
  $resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
  if(list($tipo_cambio)= mysql_fetch_array($resp))
		return $tipo_cambio;
  else
		return false;	
}


function ArregloMonedas($sesion)
  {
    $query = "SELECT prm_moneda.id_moneda, prm_moneda.tipo_cambio, prm_moneda.cifras_decimales,prm_moneda.glosa_moneda, prm_moneda.simbolo 
                  FROM prm_moneda 
                   ";
    $resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
    while( list($id_moneda, $tipo_cambio, $cifras_decimales,$glosa_moneda, $simbolo) = mysql_fetch_array($resp) )
    {
      $moneda[$id_moneda]['tipo_cambio']  = $tipo_cambio;
      $moneda[$id_moneda]['glosa_moneda']    = $glosa_moneda;
      $moneda[$id_moneda]['cifras_decimales']      = $cifras_decimales;
      $moneda[$id_moneda]['simbolo']         = $simbolo;
    }
    return $moneda;
  }

