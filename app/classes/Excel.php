<?
require_once dirname(__FILE__).'/../conf.php';

require_once Conf::ServerDir().'/../app/classes/Cliente.php';
require_once Conf::ServerDir().'/../app/classes/Contrato.php';
require_once Conf::ServerDir().'/../app/classes/Asunto.php';

require_once Conf::ServerDir().'/../fw/classes/Lista.php';
require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
require_once Conf::ServerDir().'/interfaces/excel/components/reader.php';

class Excel
{
	var $sesion;
	var	$libro;
	var $info;
	var $fila;
	var $hoja;

	var $campos = array();
	var $clientes = array();
	var $c = 0;

	var $contador=0;
	var $insertar=0;

	var $causa;
	var $w=1;

	function Excel($sesion,$nombre_archivo,$insertar,$id_usuario)
	{
		$this->sesion = $sesion;
		$this->libro = new Spreadsheet_Excel_Reader();
		$this->libro->setOutputEncoding('iso-8859-1');
		$this->libro->setUTFEncoder('mb');
		if(!$nombre_archivo) exit();
			$this->libro->read($nombre_archivo);

		$this->info = array(
								'base'=>		array('hoja'=>0, 'head'=>1,'cols'=>12, 'rows'=>166),
								'asunto'=>		array('hoja'=>1, 'head'=>1,'cols'=>12, 'rows'=>236)
							);
		$this->insertar = $insertar;

		$this->id_usuario = $id_usuario;
	}

	function CargarEncabezados()
		{
		for($i=1;$i<=$this->libro->sheets[0]['numCols'];$i++) {
			$this->encabezado[$i] = $this->LeerEncabezado($i);
		}
	}

	function LeerEncabezado($columna)
	{
		$encabezado = $this->libro->sheets[0]['cells'][1][$columna];
		$encabezado = str_replace('\'','',$encabezado);
		return trim($encabezado);
	}

	function C($columna)
	{
			$out = $this->libro->sheets[0]['cells'][$this->fila][$columna];
			$out = str_replace('\'','',$out);
			return trim($out);
	}

	function LeerTodo()
	{
		/*
		$this->hoja = 'base';
		for($this->fila = $this->info[$this->hoja]['head']+1 ; $this->fila <= $this->info[$this->hoja]['rows']; $this->fila++)
			$this->parsear();

		$this->hoja = 'asunto';
		for($this->fila = $this->info[$this->hoja]['head']+1 ; $this->fila <= $this->info[$this->hoja]['rows']; $this->fila++)
			$this->parsearAsunto();
		*/
		$this->CargarEncabezados();
		$this->datos = array();
		for($this->fila = 2; $this->fila <= $this->libro->sheets[0]['numRows']; $this->fila++) {
			$this->datos[$this->fila] = array();
			$this->parsearGeneral();
		}

		//$this->comprobar();
		//$this->ImprimirTablas();
		if($this->insertar == 1)
		{
			echo "Asuntos con cliente no declarado:<br>";
			$this->ingresar();
		}
	}

	function sumar($campo,$instancia,$valor = 1)
	{
		if(!isset($this->campos[$campo]))
			$this->campos[$campo] = array();

		if(!isset($this->campos[$campo][$instancia]))
			$this->campos[$campo][$instancia] = 1;
		else
		   $this->campos[$campo][$instancia]++;
	}

	function parsearGeneral()
	{
		if(!$this->C(1))
			return 0;
		
		foreach($this->encabezado as $index => $data) {
			$this->datos[$this->fila][$data] = $this->C($index);
		}
	}

	function parsear()
	{
		if(!$this->C(1))
			return 0;
		$nombre =		$this->C(1); //
		$rut =			$this->C(2); //
		$social =		$this->C(3); //
		$direccion =		$this->C(4); //
		$comuna =		$this->C(5); //
		$contacto = $this->C(7);
		$fono= $this->C(8);
		$mail= $this->C(9);

		$cliente = array();
		$cliente['nombre'] = $nombre;
		$cliente['rut'] = $rut;
		$cliente['social'] = $social;
		$cliente['direccion'] = $direccion;
		$cliente['comuna'] = $comuna;

		$contacto = explode(' ',$contacto);
			if(isset($contacto[1]))
				$apellido_contacto = $contacto[1];
		$contacto = $contacto[0];

		$cliente['contacto'] = $contacto;
		$cliente['apellido_contacto'] = $apellido_contacto;

		$cliente['fono_contacto'] = $fono;
		$cliente['email_contacto'] = $mail;

		$this->clientes[$nombre] = $cliente;
	}

	function parsearAsunto()
	{
		if(!$this->C(2))
			return 0;

		$cliente = $this->C(2);
		$nombre = $this->C(3);
		$contacto = $this->C(6);
		$fono= $this->C(7);
		$mail= $this->C(8);

		$asunto = array();
		$asunto['nombre'] = $nombre;
		$asunto['contacto'] = $contacto;
		$asunto['fono_contacto'] = $fono;
		$asunto['email_contacto'] = $mail;

		if(!isset($this->clientes[$cliente]))
		{
			echo '- '.$cliente.'<br>';

			$contacto = explode(' ',$contacto);
			if(isset($contacto[1]))
				$apellido_contacto = $contacto[1];
			$contacto = $contacto[0];

			$cliente_nuevo = array();
			$cliente_nuevo['nombre'] = $cliente;
			$cliente_nuevo['rut'] = '9900000-0';
			$cliente_nuevo['social'] = '-';
			$cliente_nuevo['direccion'] = '-';
			$cliente_nuevo['comuna'] = '-';
			$cliente_nuevo['contacto'] = $contacto;
			$cliente_nuevo['apellido_contacto'] = $contacto;
			$cliente_nuevo['fono_contacto'] = $fono;
			$cliente_nuevo['email_contacto'] = $mail;


			$this->clientes[$cliente] = $cliente_nuevo;
		}
		if(!isset($this->clientes[$cliente]['asuntos']))
			$this->clientes[$cliente]['asuntos'] = array();
		$this->clientes[$cliente]['asuntos'][] = $asunto;
	}

	function comprobar()
	{
		echo "Clientes sin asunto: (Se ingresará asunto base 'Servicios Legales')<br>";
		foreach($this->clientes as $nombre => $cliente)
		if(!isset($cliente['asuntos']))
		{
			$this->clientes[$nombre]['asuntos'][0] = array(
				'nombre'=>'Servicios Legales',
				'contacto'=>$cliente['contacto'].' '.$cliente['apellido_contacto'],
				'fono_contacto'=>$cliente['fono_contacto'],
				'email_contacto'=>$cliente['email_contacto']);
			echo "-- ".$nombre.'<br>';
		}
	}

	function ingresar()
	{
		echo 'Ingresando..<br>';
		foreach($this->clientes as $nombre => $cli)
		{
			if(!isset($cli['asuntos']))
			{
				echo 'ERROR: asunto faltante';
			}
			else
			{
				$cliente = new Cliente($this->sesion);
				$codigo_cliente = $cliente->AsignarCodigoCliente();

				$cliente->Edit('codigo_cliente',$codigo_cliente);
				$cliente->Edit('codigo_cliente_secundario',$codigo_cliente);
				$cliente->Edit('glosa_cliente',$cli['nombre']);
				$cliente->Edit('id_moneda',1);
				$cliente->Edit('id_usuario_encargado',$this->id_usuario);

				if($cliente->Write())
				{

					echo 'Ingresado Cliente: "'.$cli['nombre'].'"<br>';
					$contrato = new Contrato($this->sesion);

					$contrato->Edit('activo','SI');
					$contrato->Edit('usa_impuesto_separado','0');
					$contrato->Edit('usa_impuesto_gastos','0');
					$contrato->Edit('codigo_cliente',$cliente->fields['codigo_cliente']);
					$contrato->Edit('id_usuario_responsable',$this->id_usuario);
					$contrato->Edit('titulo_contacto',-1);
					$contrato->Edit('contacto',$cli['contacto']);
					$contrato->Edit('apellido_contacto',$cli['apellido_contacto']);
					$contrato->Edit('fono_contacto',$cli['fono_contacto']);
					$contrato->Edit('email_contacto',$cli['email_contacto']);

					$contrato->Edit("rut",$cli['rut']);
  					$contrato->Edit("factura_razon_social",$cli['social']);

					$contrato->Edit('periodo_fecha_inicio','2011-04-10');
					$contrato->Edit('periodo_intervalo',1);
					$contrato->Edit('monto','0');
					$contrato->Edit('id_moneda',1);
					$contrato->Edit('forma_cobro','TASA');
					$contrato->Edit('fecha_inicio_cap','2011-04-10');

					//1077
					$contrato->Edit('id_usuario_modificador',$this->id_usuario);
					$contrato->Edit('id_carta',1);
					$contrato->Edit('id_tarifa',1);
					$contrato->Edit('id_tramite_tarifa',1);
					$contrato->Edit('opc_ver_modalidad',1);
					$contrato->Edit('opc_ver_profesional',1);
					$contrato->Edit('opc_ver_gastos',1);
					$contrato->Edit('opc_ver_morosidad',1);
					$contrato->Edit('opc_ver_descuento',1);
					$contrato->Edit('opc_ver_tipo_cambio',1);
					$contrato->Edit('opc_ver_numpag',1);
					$contrato->Edit('opc_ver_resumen_cobro',1);
					$contrato->Edit('opc_ver_carta',1);
					if (UtilesApp::GetConf($this->sesion, 'PapelPorDefecto')) {
						$contrato->Edit('opc_papel', UtilesApp::GetConf($this->sesion, 'PapelPorDefecto'));
					} else {
						$contrato->Edit('opc_papel','LETTER');
					}
					$contrato->Edit('opc_moneda_total',1);
					$contrato->Edit('opc_ver_solicitante','0');
					$contrato->Edit('codigo_idioma','es');
					$contrato->Edit('descuento','0');
					$contrato->Edit('porcentaje_descuento','0');
					$contrato->Edit('id_moneda_monto',1);

					if($contrato->Write())
					{
						echo 'Ingresado Contrato<br>';
						$cliente->Edit('id_contrato',$contrato->fields['id_contrato']);
						$cliente->Write();

						foreach($cli['asuntos'] as $num => $as)
						{
							$asunto = new Asunto($this->sesion);
							$codigo_asunto = $asunto->AsignarCodigoAsunto($codigo_cliente);
							$asunto->Edit('codigo_asunto',$codigo_asunto);
							$asunto->Edit('codigo_asunto_secundario',$codigo_asunto);

							$asunto->Edit('glosa_asunto',$as['nombre']);
							$asunto->Edit('codigo_cliente',$cliente->fields['codigo_cliente']);
							$asunto->Edit('id_contrato',$contrato->fields['id_contrato']);

							$asunto->Edit('id_usuario',$this->id_usuario);
							$asunto->Edit('contacto',$as['contacto']);
							$asunto->Edit('fono_contacto',$as['fono_contacto']);
							$asunto->Edit('email_contacto',$as['email_contacto']);
							$asunto->Edit('id_encargado',$this->id_usuario);

							$asunto->Write();
							echo 'Ingresado Asunto: "'.$as['nombre'].'".<br>';
						}
					}
				}
			}
		}
	}


	function ImprimirTablas()
	{
		print_r($this->clientes);
	}

}

?>
