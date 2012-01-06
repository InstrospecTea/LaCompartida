<?
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Lista.php';
require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
require_once Conf::ServerDir().'/../app/classes/Cobro.php';
require_once Conf::ServerDir().'/../fw/classes/Usuario.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';

class Trabajo extends Objeto
{
	//Etapa actual del proyecto
	var $etapa = null;
	//Primera etapa del proyecto
	var $primera_etapa = null;
 
	var $monto = null;

	function Trabajo($sesion, $fields = "", $params = "")
	{
		$this->tabla = "trabajo";
		$this->campo_id = "id_trabajo";
		$this->sesion = $sesion;
		$this->fields = $fields;
	}
	
	function get_codigo_cliente()
	{
		$query = "SELECT codigo_cliente
					FROM trabajo
						JOIN asunto ON asunto.codigo_asunto=trabajo.codigo_asunto
					WHERE id_trabajo='".$this->fields[id_trabajo]."'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		$row = mysql_fetch_assoc($resp);
		return $row['codigo_cliente'];
	}

	function Estado()
	{
		if(!$this->fields[estado_cobro] && $this->fields[id_cobro])
		{
			$cobro= new Cobro($this->sesion);
			$cobro->Load($this->fields[id_cobro]);
			$this->fields[estado_cobro] = $cobro->fields['estado'];
		}
		if($this->fields[estado_cobro] <> "CREADO" && $this->fields[estado_cobro] <> "EN REVISION" && $this->fields[estado_cobro] != '')
			return __("Cobrado");
		if($this->fields[revisado] == 1)
			return __("Revisado");

			return __("Abierto");
	}

	function Write($ingreso_historial = true)
	{
		if($this->Loaded())
			{
				if( $ingreso_historial )
					{
						$query = "SELECT fecha, descripcion, duracion, duracion_cobrada, id_usuario, codigo_asunto, cobrable FROM trabajo WHERE id_trabajo=".$this->fields['id_trabajo'];
						$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
						list($fecha,$descripcion,$duracion,$duracion_cobrada,$id_usuario, $codigo_asunto, $cobrable) = mysql_fetch_array($resp);
						$query = "INSERT INTO trabajo_historial 
												 (id_trabajo, id_usuario, fecha, fecha_trabajo, fecha_trabajo_modificado, descripcion, descripcion_modificado, duracion, duracion_modificado, duracion_cobrada, duracion_cobrada_modificado, id_usuario_trabajador, id_usuario_trabajador_modificado, accion, codigo_asunto, codigo_asunto_modificado, cobrable, cobrable_modificado) 
									VALUES ('".$this->fields['id_trabajo']."','".$this->sesion->usuario->fields['id_usuario']."','".date("Y-m-d H:i:s")."','".$fecha."','".$this->fields['fecha']."','".addslashes($descripcion)."','".addslashes($this->fields['descripcion'])."','".$duracion."','".$this->fields['duracion']."','".$duracion_cobrada."','".$this->fields['duracion_cobrada']."','".$id_usuario."','".$this->fields['id_usuario']."','MODIFICAR','".$codigo_asunto."','".$this->fields['codigo_asunto']."','".$cobrable."','".$this->fields['cobrable']."')";
					}
			}
		else
			{
				if( $ingreso_historial )
					{
						// Creamos un trabajo nuevo, logueamos la creaciÃ³n.
						$query = "INSERT INTO trabajo_historial 
														 (id_trabajo, id_usuario, fecha, fecha_trabajo_modificado, descripcion_modificado, duracion_modificado, duracion_cobrada_modificado, id_usuario_trabajador_modificado, accion, codigo_asunto_modificado, cobrable_modificado) 
											VALUES ('".$this->fields[id_trabajo]."','".$this->sesion->usuario->fields[id_usuario]."','".date("Y-m-d H:i:s")."','".$this->fields['fecha']."','".addslashes($this->fields['descripcion'])."','".$this->fields['duracion']."','".$this->fields['duracion_cobrada']."','".$this->fields['id_usuario']."','CREAR','".$this->fields[codigo_asunto]."','".$this->fields[cobrable]."')";
					}
			}
		if(parent::Write())
			{
				// Modificamos un trabajo que ya existÃ­a, logueamos el cambio.
				if( $ingreso_historial )
					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
				return true;
			}
		return false;
	}

	function InsertarTrabajoTarifa()
	{
		$id_trabajo = $this->fields['id_trabajo'];
		$codigo_asunto = $this->fields['codigo_asunto'];
		$id_usuario = $this->fields['id_usuario'];
		$dbh = $this->sesion->dbh;
		
		$contrato = new Contrato($this->sesion);
		$contrato->LoadByCodigoAsunto($codigo_asunto);
		
		$query = "SELECT 
									prm_moneda.id_moneda, 
									( SELECT usuario_tarifa.tarifa 
											FROM usuario_tarifa 
											LEFT JOIN contrato ON contrato.id_tarifa = usuario_tarifa.id_tarifa 
											LEFT JOIN asunto ON asunto.id_contrato = contrato.id_contrato 
										WHERE usuario_tarifa.id_usuario = '$id_usuario' AND 
													asunto.codigo_asunto = '$codigo_asunto' 
													AND usuario_tarifa.id_moneda = prm_moneda.id_moneda)
								FROM prm_moneda";
		$resp = mysql_query($query, $dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$dbh);
		
		while( list( $id_moneda, $valor ) = mysql_fetch_array($resp) )
		{
			if( empty($valor) ) $valor = 0;
			$query_insert = "INSERT trabajo_tarifa 
													SET id_trabajo = '$id_trabajo',
															id_moneda = '$id_moneda',
															valor = '$valor' 
												ON DUPLICATE KEY UPDATE valor = '$valor' ";
			mysql_query($query_insert, $dbh) or Utiles::errorSQL($query_insert,__FILE__,__LINE__,$dbh);
			
			if( $contrato->fields['id_moneda'] == $id_moneda ) {
				$this->Edit("tarifa_hh", $valor);
				$this->Write();
			}
		}
	}
	
	function GetTrabajoTarifa( $id_moneda, $id_trabajo = '')
	{
		if( $id_trabajo == '' ) {
			$id_trabajo = $this->fields['id_trabajo'];
		}
		$query = "SELECT valor 
								FROM trabajo_tarifa 
								WHERE id_trabajo = '$id_trabajo' 
									AND id_moneda = '$id_moneda' ";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($valor) = mysql_fetch_array($resp);
		return $valor;
	}
	
	function ActualizarTrabajoTarifa( $id_moneda, $valor, $id_trabajo = '')
	{
		if( $id_trabajo == '' ) {
			$id_trabajo = $this->fields['id_trabajo'];
		}
		$query = "INSERT INTO trabajo_tarifa 
													SET id_trabajo = '$id_trabajo', 
															id_moneda = '$id_moneda',
															valor = '$valor' 
									ON DUPLICATE KEY UPDATE valor = '$valor' ";
		mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
	}
	
	function Eliminar()
	{
		/*if($this->sesion->usuario->fields[id_usuario] != $this->fields[id_usuario])
		{
			$this->error = $this->sesion->usuario->fields[id_usuario]." A ".$this->fields[id_usuario]." No puede eliminar un trabajo que no fue agregado por usted";
			return false;
		}*/
		if($this->Estado() == "Abierto")
		{
			/*Eliminar el Trabajo del Comentario asociado*/
			$query = "UPDATE tarea_comentario SET id_trabajo = NULL WHERE id_trabajo='".$this->fields[id_trabajo]."'";;
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);

			$query = "DELETE FROM trabajo WHERE id_trabajo='".$this->fields[id_trabajo]."'";;
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			// Si se pudo eliminar, loguear el cambio.
			if($resp)
			{
				$query = "INSERT INTO trabajo_historial 
												(id_trabajo, id_usuario, fecha, fecha_trabajo, descripcion, duracion, duracion_cobrada, id_usuario_trabajador, accion, codigo_asunto, cobrable) 
								 VALUES ('".$this->fields[id_trabajo]."','".$this->sesion->usuario->fields[id_usuario]."','".date("Y-m-d H:i:s")."','".$this->fields['fecha']."','".$this->fields['descripcion']."','".$this->fields['duracion']."','".$this->fields['duracion_cobrada']."',".$this->fields['id_usuario'].",'ELIMINAR','".$this->fields[codigo_asunto]."','".$this->fields[cobrable]."')";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			}
		}
		else
		{
			$this->error = __("No se puede eliminar un trabajo que no está abierto");
			return false;
		}
		return true;
	}

	//Función que entrega false si supera las 23:59 horas trabajadas un usuario en un dia
	function CantHorasDia($duracion_trabajo,$dia,$id_usuario,$sesion)
	{
		list($h,$m,$s)=split(":",$duracion_trabajo);
		$total_minutos=($h*60)+$m;
		$query = "SELECT trabajo.duracion FROM trabajo WHERE trabajo.fecha='".$dia."'
							AND trabajo.id_usuario='".$id_usuario."'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);

		while($duracion = mysql_fetch_array($resp))
		{
			list($h,$m,$s)=split(":",$duracion['duracion']);
			$total_minutos+=($h*60)+$m;
		}
		if(method_exists('Conf','CantidadHorasDia'))
		{
			$minutos_dia_total = Conf::CantidadHorasDia();
		}
		else
		{
			$minutos_dia_total = 1439;
		}
		if($total_minutos > $minutos_dia_total)
			return false;
		else
			return true;
	}

	static function FechaExcel2sql($dias)
	{
		// number of seconds in a day
		$seconds_in_a_day = 86400;
		// Unix timestamp to Excel date difference in seconds
		$ut_to_ed_diff = $seconds_in_a_day * 25569;
		
		return gmdate('Y-m-d',($dias * $seconds_in_a_day)-$ut_to_ed_diff);
	}


	static function ActualizarConExcel($archivo_data, $sesion)
	{
		/*
		Los campos que pueden ser modificados son:
			fecha
			solicitante (es opcional que aparezca en el excel)
			descripción
			duración cobrable
		*/
		if(!$archivo_data["tmp_name"])
			return __('Debe seleccionar un archivo a subir.');
		require_once Conf::ServerDir().'/classes/ExcelReader.php';
		$excel = new Spreadsheet_Excel_Reader();
		if(!$excel->read($archivo_data["tmp_name"]))
			return __('Error, el archivo no se puede leer, intente nuevamente.');
			
		$query = "SELECT MAX(id_trabajo) FROM trabajo";
		$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		list($max_id_trabajo) = mysql_fetch_array($resp);

		// Las columnas están definidas en /interfaces/cobros_xls.php
		$col_id_trabajo = 1;
		$col_fecha_ini = 2;
		$col_fecha_med = 3;
		$col_fecha_fin = 4;

		// Desde aquí en adelante las columnas pueden estar en distintas posiciones.
		$col_solicitante = 23;
		$col_descripcion = 23;
		$col_duracion_cobrable = 23;
		$col_duracion_trabajada = 23;
		$col_abogado = 23;
		$col_asunto = 23;

		// Encontrar en qué fila están los títulos.
		$fila_base=1;
		while(	!(  $excel->sheets[0]['cells'][$fila_base][$col_id_trabajo] == __('N°') 
					&& 
					in_array(trim($excel->sheets[0]['cells'][$fila_base][$col_fecha_ini]),array('Dia','Día','Day','Month','Mes')) ) 
					&& 
					$fila_base<$excel->sheets[0]['numRows'] )
						{
							++$fila_base;
						}
		//Paso de ubicación ini,med,fin (2,3,4) a glosa dia,mes,anyo (2,3,4)-> español (3,2,4)->inglés
		if(in_array(trim($excel->sheets[0]['cells'][$fila_base][$col_fecha_ini]),array('Dia','Día','Day')) )
		{
			$col_fecha_dia = $col_fecha_ini;
			$col_fecha_mes = $col_fecha_med;
		}
		else
		{
			$col_fecha_dia = $col_fecha_med;
			$col_fecha_mes = $col_fecha_ini;
		}
		$col_fecha_anyo = $col_fecha_fin;
		
		// Encontrar las posiciones de las columnas, usando los nombres de la base de datos.
		$nombre_descripcion_es = Utiles::glosa($sesion, 'descripcion', 'glosa_es', 'prm_excel_cobro', 'nombre_interno');
		$nombre_descripcion_en = Utiles::glosa($sesion, 'descripcion', 'glosa_en', 'prm_excel_cobro', 'nombre_interno');
		$nombre_solicitante_es = Utiles::glosa($sesion, 'solicitante', 'glosa_es', 'prm_excel_cobro', 'nombre_interno');
		$nombre_solicitante_en = Utiles::glosa($sesion, 'solicitante', 'glosa_en', 'prm_excel_cobro', 'nombre_interno');
		$nombre_duracion_cobrable_es = Utiles::glosa($sesion, 'duracion_cobrable', 'glosa_es', 'prm_excel_cobro', 'nombre_interno');
		$nombre_duracion_cobrable_en = Utiles::glosa($sesion, 'duracion_cobrable', 'glosa_en', 'prm_excel_cobro', 'nombre_interno');
		$nombre_duracion_trabajada_es = Utiles::glosa($sesion, 'duracion_trabajada', 'glosa_es', 'prm_excel_cobro', 'nombre_interno');
		$nombre_duracion_trabajada_en = Utiles::glosa($sesion, 'duracion_trabajada', 'glosa_en', 'prm_excel_cobro', 'nombre_interno');
		$nombre_asunto_es = Utiles::GlosaMult($sesion, 'asunto', 'Listado de trabajos', "glosa_es", 'prm_excel_cobro', 'nombre_interno', 'grupo');
		$nombre_asunto_en = Utiles::GlosaMult($sesion, 'asunto', 'Listado de trabajos', "glosa_en", 'prm_excel_cobro', 'nombre_interno', 'grupo');
		$nombre_abogado_es = Utiles::glosa($sesion, 'abogado', 'glosa_es', 'prm_excel_cobro', 'nombre_interno');
		$nombre_abogado_en = Utiles::glosa($sesion, 'abogado', 'glosa_en', 'prm_excel_cobro', 'nombre_interno');
		for($i=2; $i<$excel->sheets[0]['numCols']; ++$i)
		{
			switch($excel->sheets[0]['cells'][$fila_base][$i])
			{
				case $nombre_descripcion_es:
					$col_descripcion = $i;
					break;
				case $nombre_descripcion_en:
					$col_descripcion = $i;
					break;
				case $nombre_solicitante_es:
					$col_solicitante = $i;
					break;
				case $nombre_solicitante_en:
					$col_solicitante = $i;
					break;
				case $nombre_duracion_cobrable_es:
					$col_duracion_cobrable = $i;
					break;
				case $nombre_duracion_cobrable_en:
					$col_duracion_cobrable = $i;
					break;
				case $nombre_duracion_trabajada_es:
					$col_duracion_trabajada = $i;
					break;
				case $nombre_duracion_trabajada_en:
					$col_duracion_trabajada = $i;
					break;
				case $nombre_abogado_es:
					$col_abogado = $i;
					break;
				case $nombre_abogado_en:
					$col_abogado = $i;
					break;
				case $nombre_asunto_es:
					$col_asunto = $i;
					break;
				case $nombre_asunto_en:
					$col_asunto = $i;
			}
		}
		if($col_descripcion == 23 || $col_duracion_cobrable == 23 || $col_abogado == 23)
			return __('Error, los nombres de las columnas no corresponden, por favor revise que está subiendo el archivo correcto.');
  
		// Para dara feedback al usuario.
		$num_modificados = 0;
		$num_insertados = 0;
		$mensajes = '';
		$trabajos_en_hoja = array();
		$cobros_en_excel = array();
		
		
		// Leemos todas las hojas
		foreach($excel->sheets as $hoja)
		{
			if( $hoja['cells'][1][$col_id_trabajo] == __('N°') || 
					$hoja['cells'][2][$col_id_trabajo] == __('N°') || 
					$hoja['cells'][3][$col_id_trabajo] == __('N°') || 
					$hoja['cells'][4][$col_id_trabajo] == __('N°') )
				continue;
			// Busca numero de cobro
					for ($i=1; $i< $hoja['numCols']; ++$i)
							{
								$posicion_principal_es = strlen(Utiles::GlosaMult($sesion, 'minuta', 'Encabezado', "glosa_es", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
								$posicion_principal_en = strlen(Utiles::GlosaMult($sesion, 'minuta', 'Encabezado', "glosa_en", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
								
								if( (int) substr(strstr($hoja['cells'][1][$i],Utiles::GlosaMult($sesion, 'minuta', 'Encabezado', "glosa_es", 'prm_excel_cobro', 'nombre_interno', 'grupo')),$posicion_principal_es+1) > 0 )
									{ 
										$id_cobro = (int) substr(strstr($hoja['cells'][1][$i],Utiles::GlosaMult($sesion, 'minuta', 'Encabezado', "glosa_es", 'prm_excel_cobro', 'nombre_interno', 'grupo')),$posicion_principal_es+1);
									}
								else if( (int) substr(strstr($hoja['cells'][1][$i],Utiles::GlosaMult($sesion, 'minuta', 'Encabezado', "glosa_en", 'prm_excel_cobro', 'nombre_interno', 'grupo')),$posicion_principal_en+1) > 0 )
									{
										$id_cobro = (int) substr(strstr($hoja['cells'][1][$i],Utiles::GlosaMult($sesion, 'minuta', 'Encabezado', "glosa_en", 'prm_excel_cobro', 'nombre_interno', 'grupo')),$posicion_principal_en+1);
									}
							}
							
							if(!$id_cobro)
									{
										$mensajes .= "<br/>no se pude detectar el numero " . __("del cobro.");
										continue;
									}
										
							$cobro = new Cobro($sesion);
							$cobro->Load($id_cobro);
							$cobro->LoadAsuntos();
			$continuar = false;
			// Para cambiar el asunto en el caso de ver los asuntos por separado necesitamos una variable para 
			// saber en que asunto estamos actualmente, por defecto la definemos vacio para indicar que los asuntos
			// no se muestran por separado.
			$codigo_asunto_escondido = '';
			for($fila=1; $fila<=$hoja['numRows']; ++$fila)
			{
				// Buscamos por indicaciones escondidos en el caso de asuntos por separado para saber en que asunto estamos 
				// actualmente.
				if( $hoja['cells'][$fila][$col_fecha] == 'asuntos_separado' )
					$codigo_asunto_escondido = $hoja['cells'][$fila][$col_descripcion];
					
				// Importan solo las filas que guardan trabajos.
				if(!is_numeric($hoja['cells'][$fila][$col_id_trabajo]) && ( $hoja['cells'][$fila][$col_id_trabajo] || !$hoja['cells'][$fila][$col_fecha_dia] || !$hoja['cells'][$fila][$col_fecha_mes] || !$hoja['cells'][$fila][$col_fecha_anyo] || !$hoja['cells'][$fila][$col_descripcion] || !$hoja['cells'][$fila][$col_duracion_cobrable] ) )
					continue;
				
				// Para ignorar los lineas escondidas que se usan por el resumen profesional
				if( $continuar ) 
					{
						$continuar = false;
						continue;
					}
				if( $hoja['cells'][$fila][$col_fecha] == __('NO MODIFICAR ESTA COLUMNA') )
					{
						$continuar = true;
						continue;
					}
					
				// Leemos los campos del trabajo en el excel
				$id_trabajo = $hoja['cells'][$fila][$col_id_trabajo];
					
				$descripcion = $hoja['cells'][$fila][$col_descripcion];
				
				//La fecha se genera concatenando.
				$error_en_fecha = '';

				
				$dia = intval($hoja['cells'][$fila][$col_fecha_dia]);
				$mes = intval($hoja['cells'][$fila][$col_fecha_mes]);
				if($mes < 1 && $mes > 13)
				{
					$mensajes .= "Error en el trabajo $id_trabajo - Fecha: mes = ".$hoja['cells'][$fila][$col_fecha_mes].'<br />';
					continue;
				}
				if($dia < 1 && $dia > 32)
				{
					$mensajes .= "Error en el trabajo $id_trabajo - Fecha: día = ".$hoja['cells'][$fila][$col_fecha_dia].'<br />';
					continue;
				}
				if($hoja['cells'][$fila][$col_fecha_anyo] < 1930)
				{
					$mensajes .= "Error en el trabajo $id_trabajo - Fecha: año = ".$hoja['cells'][$fila][$col_fecha_anyo].'<br />';
					continue;
				}

				if($mes < 10)
					$mes = '0'.$mes;
				if($dia < 10)
					$dia = '0'.$dia;

				$fecha = $hoja['cells'][$fila][$col_fecha_anyo].'-'.$mes.'-'.$dia;
									
				if($col_solicitante != 23)
					$solicitante = $hoja['cells'][$fila][$col_solicitante];
					
					
				$abogado = $hoja['cells'][$fila][$col_abogado];
				// cargar usuario con su username
				$usuario = new Usuario($sesion);
				$usuario->LoadByNick( $abogado );
				if( !$usuario->fields['id_usuario'] || $abogado == '' )
					{
						$mensajes .= "No se puede modificar el trabajo $id_trabajo ($descripcion) porque el $nombre_abogado_es ingresado no existe.<br />";
						continue;
					}
				// Excel guarda la duración como número, donde 1 es un día.
				
				$duracion_cobrable = UtilesApp::tiempoExcelASQL($hoja['cells'][$fila][$col_duracion_cobrable]);
				if($duracion_cobrable != '00:00:00')
					$cobrable=1;
				else 
					$cobrable=0;
					
				// Si existe una columna duracion_trabajada, toma el valor si no ponga lo igual a la duracion cobrable.
				if($col_duracion_trabajada != 23 ) 
					$duracion_trabajada = UtilesApp::tiempoExcelASQL($hoja['cells'][$fila][$col_duracion_trabajada]);
				else
					$duracion_trabajada = $duracion_cobrable;
			 
			 // Revisamos la variable $codigo_asunto_escondido:
			 // Por sia caso que tiene valor sabemos que los asuntos se muestran por separado y que el asunto actual 
			 // es igual al valor de la variable, si no tiene valor tenemos que revisar la columna $col_asunto y averiguar
			 // si el valor indicado ahi esta valido.
			 		if( $codigo_asunto_escondido != '' ) 
			 			$codigo_asunto = $codigo_asunto_escondido;
			 		else	
			 			{
			 				// Por defecto seleccionamos el primer asunto del cobro, si es que el usuario ha indica un codigo,
							// veremos si esta coincide con codigo_asunto o codigo_asunto_secundario de uno de lo asuntos dentro
							// del cobro, y selecionamos el asunto correspondiente. Si no corresponde con nada avisamos al cliente 
							// que el codigo ingresado no existe.
							$asunto_data = $hoja['cells'][$fila][$col_asunto];
							$codigo_asunto = $cobro->asuntos[0];
							if( $asunto_data != '' )
							{
							$codigo_existe = false;
							foreach($cobro->asuntos as $asunto => $data )
								{ 
									$asunto = new Asunto($sesion);
									$asunto->LoadByCodigo($data);
									if( substr($asunto->fields['codigo_asunto'],-4)==$asunto_data || $asunto->fields['codigo_asunto'] == $asunto_data ) 
										{
											$codigo_asunto = $data;
											$codigo_existe = true;
											break;
										}
									else if( substr($asunto->fields['codigo_asunto_secundario'],-4)==$asunto_data || $asunto->fields['codigo_asunto_secundario'] == $asunto_data )
										{
											$codigo_asunto = $data;
											$codigo_existe = true;
											break;
										}
								}
							if( !$codigo_existe ) 
								{
									$mensajes .= "<br />No se puede modificar el trabajo $id_trabajo ($descripcion) porque el codigo de asunto ingresado (cod: $asunto_data) no existe.<br/>";
									continue;
								}
							}
						}

				// Tratamos de cargar el trabajo antes de los cambios.
				$trabajo_original = new Trabajo($sesion);
				$trabajo_original->Load($id_trabajo);
				// Si no existe el original o no hay cambios ignoramos la línea.
				if(($id_trabajo > 0 && !$trabajo_original )
					|| ( $trabajo_original->fields['fecha']==$fecha
					&& ($col_solicitante == 23 || $trabajo_original->fields['solicitante']==$solicitante) 
					&& ($col_duracion_trabajada == 23 || $trabajo_original->fields['duracion']==$duracion_trabajada )
					&& $trabajo_original->fields['descripcion']==$descripcion 
					&& ( ( $col_asunto == 23 && $codigo_asunto_escondido == '' ) || $trabajo_original->fields['codigo_asunto']==$codigo_asunto ) 
					&& ($trabajo_original->fields['duracion_cobrada']==$duracion_cobrable || $trabajo_original->fields['duracion_cobrada']=='' && $duracion_cobrable=='00:00:00') 
					&& $trabajo_original->fields['id_usuario']==$usuario->fields['id_usuario'] 
					&& $trabajo_original->fields['id_cobro']==$id_cobro) )
					continue;
					
				if( !$id_trabajo )
					{	
						$tarifa_hh = Funciones::Tarifa($sesion,$usuario->fields['id_usuario'],$cobro->fields['id_moneda'],$codigo_asunto);
						$costo_hh = Funciones::TarifaDefecto($sesion,$usuario->fields['id_usuario'],$cobro->fields['id_moneda']);
						$tarifa_hh_estandar = Funciones::MejorTarifa($sesion,$usuario->fields['id_usuario'],$cobro->fields['id_moneda'],$id_cobro);

							$query = "INSERT INTO trabajo
												( codigo_asunto,
													id_cobro,
													id_usuario,
													descripcion,
													fecha,
													duracion,
													duracion_cobrada,
													".($col_solicitante != 23?'solicitante,':'')."
													tarifa_hh,
													costo_hh,
													tarifa_hh_estandar,
													fecha_creacion,
													fecha_modificacion ) 
												VALUES
												( '".$codigo_asunto."',
													'".$id_cobro."',
													".$usuario->fields['id_usuario'].",
													'".$descripcion."',
													'".$fecha."',
													'".$duracion_trabajada."',
													'".$duracion_cobrable."',
													".($col_solicitante != 23?"'".addslashes($solicitante)."',":'')."
													".$tarifa_hh.",
													".$costo_hh.",
													".$tarifa_hh_estandar.",
													NOW(),
													NOW())";
							mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
							++$num_insertados;
					}
				else
					{
						$estado_cobro = Utiles::Glosa($sesion, $trabajo_original->fields['id_cobro'], 'estado', 'cobro');
						if($estado_cobro == 'No existe información')
							continue;
						if($estado_cobro != 'CREADO' && $estado_cobro != 'EN REVISION')
						{
							$mensajes .= "No se puede modificar el trabajo $id_trabajo ($descripcion) porque " . __("el cobro") . " se encuentra en estado $estado_cobro.<br />";
							continue;
						}
		
						// Respaldar el trabajo antes de modificarlo.
						$query = "INSERT INTO trabajo_respaldo_excel
									(id_trabajo,
									fecha,
									codigo_asunto,
									id_cobro, 
									id_usuario,
									".($col_solicitante != 23?'solicitante,':'')."
									descripcion,
									duracion_cobrada)
								VALUES
									('$id_trabajo',
									'".$trabajo_original->fields['fecha']."',
									'".$trabajo_original->fields['codigo_asunto']."',
									".$trabajo_original->fields['id_cobro'].",
									".$trabajo_original->fields['id_usuario'].",
									".($col_solicitante != 23?"'".addslashes($trabajo_original->fields['solicitante'])."',":'')."
									'".addslashes($trabajo_original->fields['descripcion'])."',
									'".$trabajo_original->fields['duracion_cobrada']."')";
						$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		
						// Anotar el cambio en el historial.
						$query = "SELECT MAX(id_trabajo_respaldo_excel) FROM trabajo_respaldo_excel";
						$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
						list($id_trabajo_respaldo_excel) = mysql_fetch_array($resp);
						$query = "INSERT INTO trabajo_historial
									(id_trabajo,
									id_usuario,
									fecha,
									fecha_trabajo, 
									fecha_trabajo_modificado, 
									descripcion, 
									descripcion_modificado, 
									duracion_cobrada, 
									duracion_cobrada_modificado, 
									id_usuario_trabajador, 
									id_usuario_trabajador_modificado, 
									accion,
									codigo_asunto,
									codigo_asunto_modificado, 
									cobrable,
									cobrable_modificado, 
									id_trabajo_respaldo_excel)
								VALUES
									('$id_trabajo',
									'".$sesion->usuario->fields['id_usuario']."',
									'".date("Y-m-d H:i:s")."',
									'".$trabajo_original->fields['fecha']."',
									'".$fecha."', 
									'".addslashes($trabajo_original->fields['descripcion'])."', 
									'".addslashes($descripcion)."', 
									'".$duracion_cobrable."', 
									'".$trabajo_original->fields['duracion_cobrada']."', 
									".$trabajo_original->fields['id_usuario'].", 
									".$usuario->fields['id_usuario'].",
									'SUBIR_XLS',
									'".$trabajo_original->fields['codigo_asunto']."',
									'".$codigo_asunto."', 
									'".$trabajo_original->fields['cobrable']."',
									'".$cobrable."', 
									'$id_trabajo_respaldo_excel')";
						$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		
						// Actualizar el trabajo.
						$query = "UPDATE trabajo
								SET fecha='$fecha', 
										".($abogado==''?'':"id_usuario = ".$usuario->fields['id_usuario'].", ")." 
										".((($asunto_data==''||$col_asunto==23)&&$codigo_asunto_escondido=='')?'':"codigo_asunto = '".$codigo_asunto."', ")."
										id_cobro = ".$id_cobro.", 
										".($col_solicitante != 23?"solicitante='".addslashes($solicitante)."',":'')." 
										descripcion='".addslashes($descripcion)."', 
										".($col_duracion_trabajada != 23?"duracion='".$duracion_trabajada."',":'')." 
										duracion_cobrada='$duracion_cobrable' 
								WHERE id_trabajo='$id_trabajo'"; 
						$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
						++$num_modificados;
				}
			}
		}
		if($num_modificados==1)
			{
			if($num_insertados==1)
				return "$mensajes$num_modificados trabajo actualizado.<br/>$num_insertados trabajo agregado.";
			else
				return "$mensajes$num_modificados trabajo actualizado.<br/>$num_insertados trabajos agregados.";
			}
		else
			{
			if($num_insertados==1)
				return "$mensajes$num_modificados trabajos actualizados.<br/>$num_insertados trabajo agregado.";
			else
				return "$mensajes$num_modificados trabajos actualizados.<br/>$num_insertados trabajos agregados.";
			}
	}
}

class ListaTrabajos extends Lista
{
	function ListaTrabajos($sesion, $params, $query)
	{
		$this->Lista($sesion, 'Trabajo', $params, $query);
	}
}
