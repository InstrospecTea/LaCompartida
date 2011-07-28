<?
    require_once dirname(__FILE__).'/../conf.php';
    require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
    require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
    require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
    require_once Conf::ServerDir().'/../fw/classes/Html.php';
    require_once Conf::ServerDir().'/../app/classes/CtaCteFactMvto.php';
    require_once Conf::ServerDir().'/../app/classes/Debug.php';
		require_once Conf::ServerDir().'/../app/classes/Cobro.php';

    $sesion = new Sesion('');
    #$pagina = new Pagina ($sesion); //no se estaba usando, se comentó por el tema de los headers (SIG 15/12/2009)
		
		if( $accion == "existen_borradores" )
		{
			$query = "SELECT count(*) FROM cobro WHERE estado = 'CREADO' OR estado = 'EN REVISION'";
			$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
			list($cantidad) = mysql_fetch_array($resp);
			
			if($cantidad > 0)
				echo true;
			else
				echo false;
		}
		else if( $accion == "buscar_banco" )
		{
			$query = "SELECT id_banco FROM cuenta_banco WHERE id_cuenta = '".$id."'";
			$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);

			list($id_banco) = mysql_fetch_array($resp);
			echo $id_banco;
		}
		else if( $accion == "cargar_cuentas" )
		{
			if( $id )
				$where = " AND id_banco = '".$id."' ";
			else
				$where = "";
			$query = "SELECT id_cuenta, numero FROM cuenta_banco WHERE 1 $where";
			$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__LIFE__,__LINE__,$sesion->dbh);
			
			$cont=1;
			while( list($id_cuenta, $numero ) = mysql_fetch_array($resp) )
			{
				if($cont==1)
					$respuesta = "$id_cuenta|$numero";
				else
					$respuesta .= "//$id_cuenta|$numero";
				$cont++;
			}
			if(!$respuesta)
				echo "~noexiste";
			else
				echo $respuesta; 
		}
		else if( $accion == "num_abogados_sin_tarifa" )
		{
			$query = "SELECT DISTINCT u.id_usuario, 
																CONCAT_WS(' ',u.nombre,u.apellido1, u.apellido2) as nombre_usuario, 
																ut.tarifa 
													 FROM trabajo AS t 
													 JOIN cobro AS c ON c.id_cobro=t.id_cobro 
											 		 JOIN contrato AS co ON c.id_contrato=co.id_contrato 
											 		 JOIN usuario AS u ON u.id_usuario=t.id_usuario 
											LEFT JOIN usuario_tarifa AS ut ON ( ut.id_moneda=c.id_moneda AND ut.id_usuario=u.id_usuario AND co.id_tarifa=ut.id_tarifa ) 
													WHERE c.id_cobro=".$id_cobro." AND ( ut.tarifa=0 OR ut.tarifa='' OR ut.tarifa IS NULL )";
			$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
			$num = mysql_num_rows($resp);
			
			if($num > 0)
				$respuesta = $num.'//';
			else 
				$respuesta = $num;
			$cont = 1;
			while( list( $id_usuario, $nombre_usuario, $tarifa ) = mysql_fetch_array($resp) )
				{
					if($cont==$num)
						$respuesta .= $id_usuario.'~'.$nombre_usuario;
					else
						$respuesta .= $id_usuario.'~'.$nombre_usuario.'//'; 
					$cont++;
				}
			echo $respuesta;
		}
    else if($accion == "set_duracion_defecto")
		{
			$query = "SELECT duracion_defecto, trabajo_si_no_defecto FROM tramite_tipo WHERE id_tramite_tipo=".$id;
			$resp=mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
			list($duracion, $como_trabajo)=mysql_fetch_array($resp);
			
			echo $duracion.'-'.$como_trabajo;
		}
		else if($accion == "actualizar_trabajo")
    {
		if($valor == "")
			$valor = "NULL";
		else
			$valor = "'$valor'";
	   $query = "UPDATE trabajo SET $campo = $valor 
				   WHERE id_trabajo = '$id'
					";

	   $resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	   echo(utf8_encode("OK"));
	}
	else if($accion == "check_codigo_asunto")
	{
	   $query = "SELECT COUNT(*) FROM asunto WHERE codigo_asunto = '$codigo_asunto'";

	   $resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		list($count) = mysql_fetch_array($resp);
		if($count == 0)
		   echo(utf8_encode("OK"));
		else
			echo(utf8_encode("NO"));
	}
	else if($accion == "info_cobro")
	{
	   $query = "SELECT razon_social,rut,giro,direccion_contacto FROM asunto LEFT JOIN cliente USING (codigo_cliente) WHERE cliente.codigo_cliente = '$codigo_cliente' ORDER BY id_asunto DESC";
	   $resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		if(list($razon,$rut,$giro,$direccion) = mysql_fetch_array($resp))
			echo("$razon|$rut|$giro|$direccion");
		else
			echo("VACIO");
	}
	else if($accion == "idioma")
	{
		if (( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ))
		{
			$where="codigo_asunto_secundario = '$codigo_asunto'";
		}
		else
		{
			$where="codigo_asunto = '$codigo_asunto'";
		}
		$query = "SELECT codigo_idioma, glosa_idioma FROM asunto LEFT JOIN prm_idioma USING (id_idioma) WHERE $where";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		if(list($codigo, $glosa) = mysql_fetch_array($resp))
			echo($codigo.'|'.$glosa);
		else
			echo("VACIO");
	}
	else if($accion == "get_tarifa")
	{
	   $query = "SELECT tarifa FROM usuario_tarifa WHERE id_usuario = '$id_usuario' AND id_moneda = '$id_moneda' AND codigo_asunto = '$codigo_asunto'";
	   $resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		if(list($tarifa) = mysql_fetch_array($resp))
			echo("$tarifa");
		else
			echo("0");
	}
	else if($accion == "set_tarifa")
	{
	   $query = "INSERT INTO usuario_tarifa SET tarifa='$tarifa', id_usuario = '$id_usuario', id_moneda = '$id_moneda', codigo_asunto = '$codigo_asunto'
					ON DUPLICATE KEY UPDATE tarifa = '$tarifa'";
	   $resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		echo("OK");
	}
	else if($accion == "set_tarifa_cliente")
	{
	   $query = "INSERT INTO usuario_tarifa_cliente SET tarifa='$tarifa', id_usuario = '$id_usuario', id_moneda = '$id_moneda', codigo_cliente = '$codigo_cliente'
					ON DUPLICATE KEY UPDATE tarifa = '$tarifa'";
	   $resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		echo("OK");
	}
	else if($accion == "get_tarifa_cliente")
	{
	   $query = "SELECT tarifa FROM usuario_tarifa WHERE id_usuario = '$id_usuario' AND id_moneda = '$id_moneda' AND codigo_cliente = '$codigo_cliente'";
	   $resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		if(list($tarifa) = mysql_fetch_array($resp))
			echo("$tarifa");
		else
			echo("0");
	}
	else if($accion == "check_codigo_contrato")
	{
       $query = "SELECT COUNT(*) FROM contrato WHERE codigo_contrato = '$codigo_contrato'";

       $resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
        list($count) = mysql_fetch_array($resp);
        if($count == 0)
           echo(utf8_encode("OK"));
        else
            echo(utf8_encode("NO"));
	}
	else if($accion == 'update_cobro_moneda')
	{
		$sql = "DELETE FROM cobro_moneda WHERE id_cobro = ".$id_cobro;
		$resp = mysql_query($sql, $sesion->dbh) or Utiles::errorSQL($sql,__FILE__,__LINE__,$sesion->dbh);

		$query_monedas = "SELECT id_moneda, tipo_cambio FROM prm_moneda";
		$resp2 = mysql_query($query_monedas, $sesion->dbh) or Utiles::errorSQL($query_monedas,__FILE__,__LINE__,$sesion->dbh);
		while($row = mysql_fetch_array($resp2))
		{
			$row['id_moneda'];
			$query_insert = "INSERT INTO cobro_moneda SET id_cobro = ".$id_cobro.", id_moneda = ".$row['id_moneda'].", tipo_cambio = ".$row['tipo_cambio']." ";
			$result = mysql_query($query_insert, $sesion->dbh) or Utiles::errorSQL($sql,__FILE__,__LINE__,$sesion->dbh);
		}
		echo("OK");
	}
	else if($accion == 'lista_contrato')
	{
		$sql_query = "SELECT
						DISTINCT id_contrato,glosa_contrato FROM contrato						
						WHERE contrato.codigo_cliente='".$codigo_cliente."'";
		echo Html::SelectQuery($sesion,$sql_query,"id_contrato",$id_contrato,'','','width="170"');
	}
	else if($accion == 'update_cap') #Update cap en valor de COBRO y en su CONTRATO original
	{
		$sql = "UPDATE cobro SET monto_contrato = $monto_update, id_moneda_monto = '$id_moneda_monto' WHERE id_cobro = $id_cobro LIMIT 1";
		$resp = mysql_query($sql, $sesion->dbh) or Utiles::errorSQL($sql,__FILE__,__LINE__,$sesion->dbh);
		
		$query = "UPDATE contrato SET monto = $monto_update, id_moneda_monto = '$id_moneda_monto' WHERE id_contrato = $id_contrato LIMIT 1";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		
		echo "OK";
	}
	else if($accion == 'elimina_cobro')
	{
		if($id_cobro && $id_contrato)
		{
			$cobros = new Cobro($sesion);
			
			$query = "UPDATE trabajo SET id_cobro = NULL, fecha_cobro= 'NULL', monto_cobrado='NULL' WHERE id_cobro = $id_cobro";
			mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);

			$cobro_eliminado = new Cobro($sesion);
			if($cobro_eliminado->Load($id_cobro))
				$cobro_eliminado->Eliminar();
			
			$query = "DELETE FROM cobro WHERE id_cobro = $id_cobro";
			mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
			
			#Tabla con datos..
			$where = 1;
			
			if($fecha_ini != '' )
			{
				$where .= " AND cobro.fecha_creacion >= '".Utiles::fecha2sql($fecha_ini)."' ";
			}
			elseif($fecha_fin != '' )
			{
				$where .= " AND cobro.fecha_creacion <= '".Utiles::fecha2sql($fecha_fin)."' ";
			}
			
			$query_cobros = "SELECT id_cobro,monto,fecha_ini,fecha_fin,prm_moneda.simbolo,prm_moneda.cifras_decimales, cobro.id_proceso
												FROM cobro 
												JOIN prm_moneda ON cobro.id_moneda = prm_moneda.id_moneda
												WHERE $where AND id_contrato = '".$id_contrato."' 
												AND estado IN ( 'CREADO', 'EN REVISION' ) ORDER BY fecha_creacion ASC";
			$resp = mysql_query($query_cobros, $sesion->dbh) or Utiles::errorSQL($query_cobros,__FILE__,__LINE__,$sesion->dbh);
			$html .="<table width=100%><tr valign='middle'>
							<td colspan=4 bgcolor='#CC3300'><span style='font-size:10px; color:#FFF;font-weight:bold'>Cobro eliminado con &eacute;xito</span></td></tr>";#revisar tema de traducción, no queda bien la tilde
    							while($cobro = mysql_fetch_array($resp))
									{
										$total_horas = $cobros->TotalHorasCobro($cobro['id_cobro']);
										$texto_horas = $cobro['fecha_ini'] != '0000-00-00' ? __('desde').' '.Utiles::sql2date($cobro['fecha_ini']).' '._('hasta').' '.Utiles::sql2date($cobro['fecha_fin']) : __('hasta').' '.Utiles::sql2date($cobro['fecha_fin']);
										$html .= "<tr valign='middle'>
															<td width=2%><img src='".Conf::ImgDir()."/color_rojo.gif' border=0></td>
															<td align=center style='font-size:10px' width=5%>#".$cobro['id_cobro']."</td>
															<td align=left style='font-size:10px' width=85%>&nbsp;de ".$cobro['simbolo'].' '.number_format($cobro['monto'],2,',','.')
															.' por '.$total_horas.' Hrs. '.$texto_horas."</td>";
										$html .= "<td align=center width=8%><img src='".Conf::ImgDir()."/coins_16.png' title='".__('Continuar con el cobro')."' border=0 style='cursor:pointer' onclick='self.location.href=\"cobros5.php?id_cobro=".$cobro['id_cobro']."&popup=false\"'>&nbsp;";
										$html .= "<img src='".Conf::ImgDir()."/cruz_roja.gif' title='".__('Eliminar cobro')."' border=0 style='cursor:pointer' onclick=\"DeleteCobro(this.form,'".$cobro['id_cobro']."','".$div."','".$id_contrato."')\"></td></tr>";
    							}			
			$html .= "</tr>
    						</table>";
    						
    	echo $html;
		}
		else
			return false;
	}
	else if($accion == 'update_contrato')
	{
		if($id_contrato)
		{
			$query = "UPDATE contrato SET incluir_en_cierre = '$incluir_en_cierre' WHERE id_contrato = '$id_contrato' LIMIT 1";
			mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
			return true;
		}
		else
			return false;
	}
	else if($accion == 'facturar_cobro') #Update Cobro si esta ha sido Facturado
	{
		if($valor == 'true')
			$up = 1;
		else
			$up = 0;
			
		$sql = "UPDATE cobro SET facturado = '$up' WHERE id_cobro = '$id_cobro' LIMIT 1";
		$resp = mysql_query($sql, $sesion->dbh) or Utiles::errorSQL($sql,__FILE__,__LINE__,$sesion->dbh);
		echo "OK";
	}
	else if($accion == 'gastos_pagados')
	{
		if($valor == 'true')
			$up = 1;
		else
			$up = 0;

		$sql = "UPDATE cobro SET gastos_pagados = '$up' WHERE id_cobro = '$id_cobro' LIMIT 1";
		$resp = mysql_query($sql, $sesion->dbh) or Utiles::errorSQL($sql,__FILE__,__LINE__,$sesion->dbh);
		echo "OK";
	}
	else if ($accion == 'cargar_datos_cliente')
	{
		$query_clientes = "SELECT contrato.factura_razon_social, contrato.factura_direccion, contrato.rut
												FROM contrato
												WHERE contrato.codigo_cliente=$codigo_cliente LIMIT 1";
		$resp = mysql_query($query_clientes, $sesion->dbh) or Utiles::errorSQL($query_clientes,__FILE__,__LINE__,$sesion->dbh);

		for($i = 0; $fila = mysql_fetch_assoc($resp); $i++)
		{
			if($i > 0)
				echo("~");
			echo(join("|",$fila));
		}
		if($i == 0)
			echo("VACIO|");

	}
	else if($accion == 'set_cobro_trabajo')#TIENE UN SOLO = BUG #Setea el trabajo a algún cobro (CREADO) correspondiente al periodo y asunto. 
	{
		if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) )
			{
				$asunto = new Asunto($sesion);
				$asunto->LoadByCodigoSecundario($codigo_asunto);
				$codigo_asunto = $asunto->fields['codigo_asunto'];
			}
		$cobro = new Cobro($sesion);
		$id_cobro_set = $cobro->ObtieneCobroByCodigoAsunto($codigo_asunto, $fecha);
		if($id_cobro_set)
		{
			$sql = "UPDATE trabajo SET id_cobro = '$id_cobro_set' WHERE id_trabajo = '$id_trabajo'";
			$resp = mysql_query($sql, $sesion->dbh) or Utiles::errorSQL($sql,__FILE__,__LINE__,$sesion->dbh);
			
			if($cobro->Load($id_cobro_set))
				$cobro->GuardarCobro();
			else
				return false;
				
			if($cobro->Load($id_cobro_actual))
				$cobro->GuardarCobro();
			else
				return false;
		}
		else
		{
			$sql = "UPDATE trabajo SET id_cobro = NULL WHERE id_trabajo = '$id_trabajo'";
			$resp = mysql_query($sql, $sesion->dbh) or Utiles::errorSQL($sql,__FILE__,__LINE__,$sesion->dbh);
			if($cobro->Load($id_cobro_actual))
				$cobro->GuardarCobro();
			else
				return false;
		}		
		echo "OK";			
	}
	else if($accion == 'set_cobro_tramite')
	{
		$cobro = new Cobro($sesion);
		$id_cobro_set = $cobro->ObtieneCobroByCodigoAsunto($codigo_asunto, $fecha);
		if($id_cobro_set)
		{
			$query = "UPDATE tramite SET id_cobro = '$id_cobro_set' WHERE id_tramite = '$id_tramite'";
			$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
			
			if($cobro->Load($id_cobro_set))
				$cobro->GuardarCobro();
			else
				return false;
		}
		else
		{
			$query = "UPDATE tramite SET id_cobro = NULL WHERE id_tramite = '$id_tramite'";
			$resp =mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__, $sesion->dbh);
			if($cobro->Load($id_cobro_actual))
				$cobro->GuardarCobro();
			else
				return false;
		}
		echo "OK";
	}
	else if($accion == 'actualizar_documento_moneda')
	{
			$documento = new Documento($sesion);
			$documento->Load($id_documento);
			
			$ids_monedas = explode(',',$ids_monedas);
			$tcs = explode(',',$tcs);
			$tipos_cambios = array();
			foreach($ids_monedas as $i => $id_moneda)
			{
				$tipos_cambios[$id_moneda] = $tcs[$i];
			}
			$documento->ActualizarDocumentoMoneda($tipos_cambios);
			echo "EXITO";
	}
	else if($accion == 'actualizar_factura_moneda')
	{
			$mvto = new CtaCteFactMvto($sesion);
			$mvto->LoadByFactura($id_factura);
			
			$ids_monedas = explode(',',$ids_monedas);
			$tcs = explode(',',$tcs);
			$tipos_cambios = array();
			foreach($ids_monedas as $i => $id_moneda)
			{
				$tipos_cambios[$id_moneda] = $tcs[$i];
			}
			$mvto->ActualizarMvtoMoneda($tipos_cambios);
			echo "EXITO";
	}
	else if($accion == 'existe_glosa_cliente')
	{
		//$dato_cliente = str_replace(' ','',$dato_cliente);
		$where = "";
		if($id_cliente)
		{
			$where = " AND id_cliente != '".$id_cliente."'";
		}
		$query = "Select count(id_cliente) FROM cliente WHERE glosa_cliente like '".$dato_cliente."' ".$where;
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
        list($count) = mysql_fetch_array($resp);
        
        if($count>0)
        {
			echo 1;
		}
		else
		{
			echo 0;
		}
	}
	else if($accion == 'existe_rut_cliente')
	{
		$where = "";
		if($id_cliente)
		{
			$where = " AND id_cliente != '".$id_cliente."'";
		}
		$query = "Select count(co.rut) FROM contrato co, cliente cl WHERE co.codigo_cliente = cl.codigo_cliente AND co.rut = '".$dato_cliente."'  ".$where;
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
        list($count) = mysql_fetch_array($resp);
        if($count>0)
        {
			echo 1;
		}
		else
		{
			echo 0;
		}
	}
	else if( $accion == "saldo_cobro_factura" )
	{
		$query = "SELECT
					factura.id_factura,
					SUM(factura_cobro.monto_factura) as monto_factura,
					factura.numero,
					prm_documento_legal.glosa as tipo,
					prm_estado_factura.glosa,
					prm_estado_factura.codigo,
					factura.subtotal_sin_descuento,
					honorarios,
					ccfm.saldo as saldo,
					subtotal_gastos,
					subtotal_gastos_sin_impuesto,
					iva,
					prm_documento_legal.codigo as cod_tipo,
					factura.id_moneda,
					pm.tipo_cambio,
					pm.cifras_decimales
				FROM factura
				JOIN prm_moneda AS pm ON factura.id_moneda = pm.id_moneda
				LEFT JOIN cta_cte_fact_mvto AS ccfm ON factura.id_factura = ccfm.id_factura
				JOIN prm_documento_legal ON factura.id_documento_legal = prm_documento_legal.id_documento_legal
				JOIN prm_estado_factura ON factura.id_estado = prm_estado_factura.id_estado
				LEFT JOIN factura_cobro ON factura_cobro.id_factura = factura.id_factura
				WHERE factura.id_cobro = '$id'
				GROUP BY factura.id_factura";
		
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		$saldo_honorarios =0;
		$saldo_gastos_con_impuestos =0;
		$saldo_gastos_sin_impuestos =0;
		$num_fact = 0;
		while( list( $id_factura, $monto, $numero, $tipo, $estado, $cod_estado, $subtotal_honorarios, $honorarios, $saldo, $subtotal_gastos, $subtotal_gastos_sin_impuesto, $impuesto, $cod_tipo, $id_moneda_factura, $tipo_cambio_factura, $cifras_decimales_factura, $cantidad_facturas ) = mysql_fetch_array($resp) ) {
		//si el documento no esta anulado, lo cuento para el saldo disponible a facturar (notas de credito suman, los demas restan)
			if($cod_estado != 'A'){
				$mult = $cod_tipo == 'NC' ? 1 : -1;
				$saldo_honorarios += $subtotal_honorarios*$mult;
				$saldo_gastos_con_impuestos += $subtotal_gastos*$mult;
				$saldo_gastos_sin_impuestos += $subtotal_gastos_sin_impuesto*$mult;
			}
			$num_fact++;
		}
		if($num_fact>0)
			echo $saldo_honorarios.'//'.$saldo_gastos_con_impuestos.'//'.$saldo_gastos_sin_impuestos;
		else
			echo 'primera_factura';
	}
	else if($accion == 'saldo_facturas_mvto')
	{
		$saldo=0;
		if($id){
		$query = "SELECT SUM( saldo ) AS saldo
					FROM cta_cte_fact_mvto
					WHERE id_factura
					IN (".$id.")";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		list($saldo) = mysql_fetch_array($resp);
		}
		$saldo = $saldo*(-1);
		echo number_format($saldo, 3, '.', '');
	}
	else if( $accion == 'revisar_tarifas')
	{
		$id_usuario = $sesion->usuario->fields['id_usuario'];	
		$id_moneda = ( isset( $_GET["id_moneda"]) && is_numeric($_GET["id_moneda"]) ? $_GET["id_moneda"] : 0 );
		$id_tarifa = ( isset( $_GET["id_tarifa"]) && is_numeric($_GET["id_tarifa"]) ? $_GET["id_tarifa"] : 0 );

		$query_usuarios_profesionales = "SELECT CONCAT( apellido1,' ', apellido2, ', ', nombre) as nombre_completo FROM usuario as u 
			JOIN usuario_permiso as up USING( id_usuario ) 
			WHERE up.codigo_permiso = 'PRO'";
		$resp_usuarios_profesionales = mysql_query($query_usuarios_profesionales, $sesion->dbh) or Utiles::errorSQL($query_usuarios_profesionales,__FILE__,__LINE__,$sesion->dbh);
		$tup = mysql_num_rows( $resp_usuarios_profesionales ); // tup = total de usuarios con permisos PROfesional
		$query_usarios_sin_tarifa = "SELECT CONCAT( apellido1,' ', apellido2, ', ', nombre) as nombre_completo FROM usuario as u 
			JOIN usuario_permiso as up USING( id_usuario ) 
			WHERE up.codigo_permiso = 'PRO' AND u.id_usuario NOT IN ( 
				SELECT ut.id_usuario FROM usuario_tarifa as ut WHERE ut.id_moneda=" . $id_moneda . " AND ut.id_tarifa = " . $id_tarifa . " 
			)";
		$resp_usuarios_sin_tarifa = mysql_query($query_usarios_sin_tarifa, $sesion->dbh) or Utiles::errorSQL($query_usarios_sin_tarifa,__FILE__,__LINE__,$sesion->dbh);
		$numrows = mysql_num_rows($resp_usuarios_sin_tarifa);
		if( $numrows > 0)
		{
			$todos = "";   // $ust = usuarios sin tarifa
			while( $ust = mysql_fetch_array($resp_usuarios_sin_tarifa))
			{
				$todos .= ( strlen( $todos ) > 0 ? "<br />" : "");
				$todos .= htmlentities( $ust["nombre_completo"], ENT_QUOTES, 'ISO-8859-1' );
			}
			echo $numrows . "::" . $todos. "::" . $tup;
		}
		else
		{
			echo $numrows . "::&nbsp;::" . $tup;
		}
	}
	else
		echo("ERROR AJAX. Acción: $accion");
?>
