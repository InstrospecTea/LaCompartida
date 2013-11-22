<?
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Lista.php';
require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';
require_once Conf::ServerDir().'/../app/classes/Moneda.php';

class Tarifa extends Objeto
{

	public static $llave_carga_masiva = 'glosa_tarifa';

	function Tarifa($sesion, $fields = "", $params = "")
	{
		$this->tabla = "tarifa";
		$this->campo_id = "id_tarifa";
		$this->sesion = $sesion;
		$this->fields = $fields;
	}

	#Carga a través de ID
	function LoadById($id_tarifa)
	{
		$query = "SELECT id_tarifa FROM tarifa WHERE id_tarifa = '$id_tarifa'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($id) = mysql_fetch_array($resp);
		return $this->Load($id);
	}


	# Elimina Tarifas
	function Eliminar()
	{
		if(!$this->Loaded())
			return false;
		$query = "SELECT COUNT(*) FROM contrato WHERE contrato.id_tarifa = '".$this->fields['id_tarifa']."'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($count) = mysql_fetch_array($resp);
		if($count > 0)
		{
			$query = "SELECT codigo_cliente FROM contrato WHERE id_tarifa = '".$this->fields['id_tarifa']."' LIMIT 1";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			list($contrato) = mysql_fetch_array($resp);
			$this->error = __('No se puede eliminar una').' '.__('tarifa').' '.__('que tiene un contrato asociado. Código contrato asociado: ').$contrato;
			return false;
		}
		$query = "DELETE FROM usuario_tarifa WHERE id_tarifa = '".$this->fields['id_tarifa']."'";
		mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);

		$query = "DELETE FROM categoria_tarifa WHERE id_tarifa = '".$this->fields['id_tarifa']."'";
		mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);

		$query = "DELETE FROM tarifa WHERE id_tarifa = '".$this->fields['id_tarifa']."'";
		mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		return true;
	}


	#Carga la tarifa para el usuario y moneda correspondiente
	function LoadByUsuarioMoneda($id_usuario, $id_moneda, $id_tarifa)
	{
		$query = "SELECT id_usuario_tarifa FROM usuario_tarifa WHERE id_usuario = '$id_usuario' AND id_moneda = '$id_moneda' AND id_tarifa = '$id_tarifa' ";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($id) = mysql_fetch_array($resp);
		return $id;
	}

	#Limpia Tarifa por defecto
	function TarifaDefecto($id_tarifa)
	{
		$query = "UPDATE tarifa SET tarifa_defecto = 0 WHERE id_tarifa != '$id_tarifa'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		return true;
	}

	#Retorna ID tarifa declarada como defecto
	function SetTarifaDefecto()
	{
		$query = "SELECT id_tarifa FROM tarifa WHERE tarifa_defecto = 1 LIMIT 1";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($id_tarifa) = mysql_fetch_array($resp);
		return $id_tarifa;
	}

	//crea (o edita) una tarifa donde todos los usuarios tienen el mismo valor. retorna el id de la tarifa
	function GuardaTarifaFlat($valor, $id_moneda, $id_tarifa=null){
		/*logica pro?
			existe = se busca una tarifa con ese valor/moneda
			mia = si me pasaron un id y solo 1 contrato la esta usando (el q llamo la func)
			if(existe){
				if(mia) borro la q me pasaron
				return existe
			}
			else{
				if(mia) edito la q me pasaron
				else creo una nueva
			}
		*/
		$valor=str_replace(',','.',$valor);

		$moneda = new Moneda($this->sesion);
		$moneda->Load($id_moneda);
		$glosa = 'Tarifa Flat por '.$moneda->fields['simbolo'].number_format($valor,$moneda->fields['cifras_decimales'],'.','');

		//si existe la tarifa y es flat, se edita, si no se crea
		if(!empty($id_tarifa)){
			$this->Load($id_tarifa);
			if(empty($this->fields['tarifa_flat'])){
				$this->fields[$this->campo_id] = null;
				$id_tarifa = null;
			}
		}

		$this->Edit('tarifa_flat', $valor);
		$this->Edit('guardado', 1);
		$this->Edit('glosa_tarifa', $glosa);
		if(!$this->Write()) return false;

		$id_tarifa = $this->fields[$this->campo_id];

		//guardar tarifas de usuarios y categorias
		$usuario_tarifa = new UsuarioTarifa($this->sesion);
		$query = "SELECT id_usuario FROM usuario";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		while(list($id_usuario) = mysql_fetch_array($resp)){
			$usuario_tarifa->GuardarTarifa($id_tarifa, $id_usuario, $id_moneda, $valor);
		}

		$categoria_tarifa = new CategoriaTarifa($this->sesion);
		$query = "SELECT id_categoria_usuario FROM prm_categoria_usuario";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		while(list($id_categoria_usuario) = mysql_fetch_array($resp)){
			$categoria_tarifa->GuardarTarifaCategoria($id_tarifa, $id_categoria_usuario, $id_moneda, $valor);
		}

		//eliminar tarifas flat huachas (se crea 1 por contrato flat, y quedan flotando al cambiarse a variable)
		$query = "SELECT t.id_tarifa FROM tarifa t LEFT JOIN contrato c ON t.id_tarifa = c.id_tarifa WHERE c.id_contrato IS NULL AND t.tarifa_flat IS NOT NULL";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		while(list($id_tarifa_eliminar) = mysql_fetch_array($resp)){
			if($id_tarifa_eliminar == $id_tarifa) continue;
			$this->Load($id_tarifa_eliminar);
			$this->Eliminar();
		}
		$this->Load($id_tarifa);

		return $id_tarifa;
	}
}

Class UsuarioTarifa extends Objeto
{
	function UsuarioTarifa($sesion, $fields = "", $params = "")
	{
		$this->tabla = "usuario_tarifa";
		$this->campo_id = "id_usuario_tarifa";
		$this->sesion = $sesion;
		$this->fields = $fields;
	}

	#Carga a través de ID_TARIF, ID_USUARIO, ID_MONEDA la tarifa(monto)
	function LoadById($id_tarifa, $id_usuario, $id_moneda)
	{
		$query = "SELECT tarifa FROM usuario_tarifa WHERE id_tarifa = '$id_tarifa' AND id_moneda = '$id_moneda' AND id_usuario = '$id_usuario' LIMIT 1";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($tarifa) = mysql_fetch_array($resp);
		return $tarifa;
	}

	#Guardando Tarifa
	function GuardarTarifa($id_tarifa, $id_usuario, $id_moneda, $valor)
	{
		$valor=str_replace(',','.',$valor);
		$this->LogCambio($id_tarifa, $id_usuario, $id_moneda, $valor);
		if($valor == '')
		{
			$query = "DELETE FROM usuario_tarifa WHERE id_tarifa = '$id_tarifa' AND id_moneda = '$id_moneda' AND id_usuario = '$id_usuario'";
		}
		else
		{
			$query = "INSERT usuario_tarifa SET id_tarifa = '$id_tarifa', id_moneda = '$id_moneda',
								id_usuario = '$id_usuario', tarifa = '$valor'
								ON DUPLICATE KEY UPDATE tarifa = '$valor'";
		}
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		return true;
	}

	function LogCambio($id_tarifa, $id_usuario, $id_moneda, $valor){
		$valor_original = (float)$this->LoadById($id_tarifa, $id_usuario, $id_moneda);
		$id = 1000000 * $id_tarifa + $id_usuario;
		$glosa_moneda = Moneda::GetGlosaMoneda($this->sesion, $id_moneda);

		$LogDB = new LogDB($this->sesion);
		$LogDB->Loggear($this->tabla, $id, $glosa_moneda, (float)$valor, $valor_original);
	}
}


Class CategoriaTarifa extends Objeto
{
	function CategoriaTarifa($sesion, $fields = "", $params = "")
	{
		$this->tabla = "categoria_tarifa";
		$this->campo_id = "id_categoria_tarifa";
		$this->sesion = $sesion;
		$this->fields = $fields;
	}

	#Carga a través de ID_TARIF, ID_CATEGORIA_USUARIO, ID_MONEDA la tarifa(monto)
	function LoadById($id_tarifa, $id_categoria_usuario, $id_moneda)
	{
		$query = "SELECT tarifa FROM categoria_tarifa WHERE id_tarifa = '$id_tarifa' AND id_moneda = '$id_moneda' AND id_categoria_usuario = '$id_categoria_usuario' LIMIT 1";
		$resp_categoria = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($tarifa_categoria) = mysql_fetch_array($resp_categoria);
		return $tarifa_categoria;
	}

	#Guardar Tarifa
	function GuardarTarifaCategoria($id_tarifa, $id_categoria_usuario, $id_moneda, $valor)
	{
		$valor=str_replace(',','.',$valor);
		$this->LogCambio($id_tarifa, $id_categoria_usuario, $id_moneda, $valor);
		if($valor == '')
		{
			$query = "DELETE FROM categoria_tarifa WHERE id_tarifa = '$id_tarifa' AND id_moneda = '$id_moneda' AND id_categoria_usuario = '$id_categoria_usuario'";
		}
		else
		{
			$query = "INSERT categoria_tarifa SET id_tarifa = '$id_tarifa', id_moneda = '$id_moneda',
								id_categoria_usuario = '$id_categoria_usuario', tarifa = '$valor'
								ON DUPLICATE KEY UPDATE tarifa = '$valor'";
		}
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		return true;
	}

	function LogCambio($id_tarifa, $id_categoria_usuario, $id_moneda, $valor){
		$valor_original = (float)$this->LoadById($id_tarifa, $id_categoria_usuario, $id_moneda);
		$id = 1000000 * $id_tarifa + $id_categoria_usuario;
		$glosa_moneda = Moneda::GetGlosaMoneda($this->sesion, $id_moneda);

		$LogDB = new LogDB($this->sesion);
		$LogDB->Loggear($this->tabla, $id, $glosa_moneda, (float)$valor, $valor_original);
	}
}
?>