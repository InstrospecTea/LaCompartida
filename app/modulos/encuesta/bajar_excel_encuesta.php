<?
    require_once dirname(__FILE__).'/../../../conf.php';
    require_once Conf::ServerDir().'/app/modulos/encuesta/classes/Encuesta.php';
    require_once Conf::ServerDir().'/app/modulos/encuesta/classes/Reportes.php';
    require_once Conf::ServerDir().'/app/modulos/encuesta/classes/Pregunta.php';
    require_once Conf::ServerDir().'/app/modulos/encuesta/classes/Alternativa.php';
    require_once Conf::ServerDir().'/app/modulos/encuesta/classes/RespuestaAbierta.php';
    require_once Conf::ServerDir().'/app/modulos/encuesta/classes/RespuestaAlternativa.php';

    require_once Conf::ServerDir().'/app/modulos/encuesta/classes/Lista.php';

    require_once Conf::ServerDir().'/fw/classes/Pagina.php';
    require_once Conf::ServerDir().'/fw/classes/Empresa.php';
    require_once Conf::ServerDir().'/fw/classes/Sesion.php';
    require_once Conf::ServerDir().'/fw/classes/Usuario.php';
    require_once Conf::ServerDir().'/fw/classes/Utiles.php';
    require_once Conf::ServerDir().'/fw/classes/Html.php';

	require_once 'Spreadsheet/Excel/Writer.php';

	$sesion = new Sesion( array('ADM') );

	$pagina = new Pagina( $sesion );

	$encuesta = new Encuesta( $sesion );

	$encuesta->Load( $id_encuesta );

	if( $encuesta->Load( $id_encuesta ) )
	{
		$empresa = new Empresa($sesion);
		$empresa->Load($encuesta->fields['id_empresa']);
		// clave aleatoria para bloquear celdas
		#$key = substr(md5(microtime().posix_getpid()), 0, 8);

		// LIBRO

		$wb = new Spreadsheet_Excel_Writer();

		$wb->send($empresa->fields['glosa_empresa'].' - '.$encuesta->fields['titulo'].'.xls');

		// FORMATOS

		$wb->setCustomColor ( 35, 220, 255, 220 );
		$wb->setCustomColor ( 36, 255, 255, 220 );

		$f1 =& $wb->addFormat(array('Size' => 12,
									'Align' => 'right',
									'Bold' => '1',
									'Locked' => 1,
									'Color' => 'black'));

		$f2 =& $wb->addFormat(array('Size' => 12,
									'Align' => 'left',
									'Locked' => 1,
									'Color' => 'black'));

		$f3 =& $wb->addFormat(array('Size' => 11,
									'Align' => 'merge',
									'Bold' => '1',
									'FgColor' => '35',
									'Border' => 1,
									'Locked' => 1,
									'Color' => 'black'));

		$f4 =& $wb->addFormat(array('Size' => 10,
									'Align' => 'left',
									'Locked' => 1,
									'Color' => 'black'));

		$f4b =& $wb->addFormat(array('Size' => 10,
									'Align' => 'left',
									'Bold' => '1',
									'Locked' => 1,
									'Color' => 'black'));

		$f5 =& $wb->addFormat(array('Size' => 11,
									'Align' => 'left',
									'Bold' => '1',
									'Locked' => 1,
									'Color' => 'black'));

		$f6 =& $wb->addFormat(array('Size' => 9,
									'Align' => 'center',
									'Bold' => '1',
									'FgColor' => '36',
									'Border' => 1,
									'Locked' => 1,
									'Color' => 'black'));

		$f7 =& $wb->addFormat(array('Size' => 8,
									'Align' => 'center',
									'Border' => 1,
									'UnLocked' => 1,
									'Color' => 'black'));

		$f8 =& $wb->addFormat(array('Size' => 8,
									'Align' => 'center',
									'Border' => 1,
									'Locked' => 1,
									'Color' => 'red'));

		// HOJA PRODUCTOS

		$ws1 =& $wb->addWorksheet('Detalle por Usuarios');
		$ws1->protect( $key );

		// COLUMNAS

		$ws1->setColumn( 0, 0,  3.29);
		$ws1->setColumn( 1, 1, 15.00);
		$ws1->setColumn( 2, 2, 35.00);
        $ws1->setColumn( 3, 3, 20.00);
        $ws1->setColumn( 4, 4, 15.00);

		// DATOS

		$ws1->write(1, 1, 'Empresa:', $f1);
		$ws1->write(1, 2, $empresa->fields['glosa_empresa'], $f2);

		$ws1->write(2, 1, 'Encuesta:', $f1);
		$ws1->write(2, 2, $encuesta->fields['titulo'], $f2);

        $ws1->write(3, 1, 'Emisión:', $f1);
        $ws1->write(3, 2, Utiles::sql2fecha($encuesta->fields['fecha_modificacion'],'%d de %B de %Y'), $f2);


		$ws1->mergeCells( 5, 1, 5, 4 );
		$ws1->write(5, 1, 'Resumen', $f3);
		$ws1->write(5, 2, '', $f3);
		$ws1->write(5, 3, '', $f3);
		$ws1->write(5, 4, '', $f3);

		$ws1->write( 6, 1, 'Total de Encuestados', $f4b);
		$ws1->write( 7, 1, 'Encuestas Respondidas', $f4b);
		$ws1->write( 8, 1, 'Encuestas no Respondidas', $f4b);

        $universo = Reportes::Universo($sesion,$id_encuesta);

        $ws1->write( 6, 3,$universo['total'] , $f4b);
        $ws1->write( 7, 3,$universo['respondidas'] , $f4b);
        $ws1->write( 8, 3,$universo['no_respondidas'] , $f4b);


		$ws1->write(14, 1, 'Usuarios', $f5);

		$ws1->write(15, 1, 'Rut', $f6);
		$ws1->write(15, 2, 'Nombre', $f6);
		$ws1->write(15, 3, 'Email', $f6);
        $ws1->write(15, 4, 'Estado', $f6);

		$lista_preguntas = new ListaPreguntas($sesion,'',"SELECT * FROM encuesta_pregunta WHERE id_encuesta = $id_encuesta");

    	for($x=0;$x<$lista_preguntas->num;$x++)// Busca las preguntas de la encuesta
	    {
    	    $pregunta = $lista_preguntas->Get($x);

			$largo =  strlen($pregunta->fields['glosa_pregunta']);

			if($pregunta->fields['tipo']=='ALTERNATIVA')
				$largo *=2;
			else
				$largo *=4;

			$ws1->setColumn( $x+5, $x+5, $largo.".00");

	        $ws1->write(15, $x+5, $pregunta->fields['glosa_pregunta'], $f6);

		}



        $id_empresa = $encuesta->fields['id_empresa'];
        $lista_usuarios = new ListaEncuestas($sesion,'',"SELECT * FROM usuario INNER JOIN
																usuario_empresa ON usuario_empresa.rut_usuario = usuario.rut 
																				WHERE usuario_empresa.id_empresa = '$id_empresa' ");

        for($i=0;$i < $lista_usuarios->num; $i++)
        {
            $usuario_rut = $lista_usuarios->Get($i);

			// DATOS DEL USUARIO
			
            $ws1->write(16 + $i, 1, $usuario_rut->fields['rut'].'-'.$usuario_rut->fields['dv_rut'], $f7);
            $ws1->write(16 + $i, 2, $usuario_rut->fields['nombre'].' '.$usuario_rut->fields['apellido1'].' '.$usuario_rut->fields['apellido2'], $f7);
            $ws1->write(16 + $i, 3, $usuario_rut->fields['email'], $f7);

            if($encuesta->IsRespondida($id_encuesta, $usuario_rut->fields['rut']))
			{
    	    	$ws1->write(16 + $i, 4, 'Respondida', $f7);
	           	for($x=0;$x<$lista_preguntas->num;$x++)// Busca las preguntas de la encuesta
	          	{	
        	        $pregunta = $lista_preguntas->Get($x);
    	        	$id_encuesta_pregunta = $pregunta->fields['id_encuesta_pregunta'];

	                if($pregunta->fields['tipo']=='ALTERNATIVA')
                	{
                        $resp = new RespuestaAlternativa($sesion,'','');
                        $resp->LoadResp($id_encuesta_pregunta,$usuario_rut->fields['rut']);
						$glosa = new Alternativa($sesion);
						$glosa->Load($resp->fields['id_encuesta_pregunta_alternativa']);
                        $ws1->write(16 + $i, $x+5,$glosa->fields['glosa_alternativa'], $f7);
        	        }
    	           	else
	                {
						$resp = new RespuestaAbierta($sesion,'','');
						$resp->LoadResp($id_encuesta_pregunta,$usuario_rut->fields['rut']);
        	      		$ws1->write(16 + $i, $x+5, $resp->fields['respuesta'], $f7);
    	           	}
	           	}
			}
            else
			{
                $ws1->write(16 + $i, 4, 'No Respondida', $f8);
	            for($x=0;$x<$lista_preguntas->num;$x++)
                {
                     $ws1->write(16 + $i, $x+5, '-', $f8);
                }

			}

			
		}
		for($j=0; $j<100; $j++)
		{
			$ws1->write(16 + $i + $j, 1, '', $f8);
			$ws1->write(16 + $i + $j, 2, '', $f7);
			$ws1->write(16 + $i + $j, 3, '', $f7);
			$ws1->write(16 + $i + $j, 4, '', $f7);
			 for($x=0;$x<$lista_preguntas->num;$x++)
				{
					 $ws1->write(16 + $i + $j, $x+5, '', $f7);
				}
		}

		$wb->close();

		exit;
	}

	$pagina->FatalError( 'Encuesta inválida.' );

	$pagina->titulo = 'Bajar archivo MS Excel';

	$pagina->PrintHeaders();

	$pagina->PrintTop();
?>

	<a href="javascript:history.go(-1);">volver</a>

<?
	$pagina->PrintBottom();
?>
