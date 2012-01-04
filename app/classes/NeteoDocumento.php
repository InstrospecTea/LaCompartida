<?
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Lista.php';
require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';
require_once Conf::ServerDir().'/../app/classes/UtilesApp.php';
require_once Conf::ServerDir().'/../app/classes/FacturaPago.php';
require_once Conf::ServerDir().'/../app/classes/CtaCteFact.php';

class NeteoDocumento extends Objeto
{
	function NeteoDocumento($sesion, $fields = "", $params = "")
	{
		$this->tabla = "neteo_documento";
		$this->campo_id = "id_neteo_documento";
		
		$this->sesion = $sesion;
		$this->fields = $fields;
	}

	function Ids($id_documento_pago,$id_documento_cobro)
	{
		if(!$id_documento_pago)
			return false;

		$query = "SELECT id_neteo_documento AS id
					FROM neteo_documento
					WHERE	id_documento_pago = '$id_documento_pago'
					AND		id_documento_cobro = '$id_documento_cobro'";
	
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($id) = mysql_fetch_array($resp);
		if($id)
		{
			return $this->Load($id);
		}
		else
		{
			$this->Edit('id_documento_pago',$id_documento_pago);
			$this->Edit('id_documento_cobro',$id_documento_cobro);
		}
		return false;
	}

	//Establece un Neteo Completo entre los documentos
	function NeteoCompleto($documento_cobro,$documento_pago, $honorarios, $cambio_cobro, $cambio_pago, $write)
	{
		$neteo = new NeteoDocumento($this->sesion);
		
		if(!$neteo->Ids($documento_pago->fields['id_documento'], $documento_cobro->fields['id_documento']) )
		{
			$neteo->Edit('id_documento_pago',$documento_pago->fields['id_documento']);
			$neteo->Edit('id_documento_cobro',$documento_cobro->fields['id_documento']);
		}

		if($honorarios)
		{
			$neteo->Edit('valor_cobro_honorarios',$documento_cobro->fields['honorarios']);
			$neteo->Edit('valor_pago_honorarios',number_format($documento_cobro->fields['honorarios']*$cambio_cobro/$cambio_pago,0,".",""));
		}
		else
		{
			$neteo->Edit('valor_cobro_gastos',$documento_cobro->fields['gastos']);
			$neteo->Edit('valor_pago_gastos',number_format($documento_cobro->fields['gastos']*$cambio_cobro/$cambio_pago,0,".",""));
		}
		if($write)
			$neteo->Write();

		if($neteo->fields['id_neteo_documento'])
			$id = $neteo->fields['id_neteo_documento'];
		else
			$id = "Nuevo";

		$out  = "<tr> <td>".$id."</td><td>";
		$out .= $neteo->fields['id_documento_cobro']."</td><td>";
		$out .= $neteo->fields['id_documento_pago']."</td><td>";
		$out .= $documento_cobro->fields['id_moneda']."</td><td>";
		$out .= $neteo->fields['valor_cobro_honorarios']."</td><td>";
		$out .= $neteo->fields['valor_cobro_gastos']."</td><td>";
		$out .= $documento_pago->fields['id_moneda']."</td><td>";
		$out .= $neteo->fields['valor_pago_honorarios']."</td><td>";
		$out .= $neteo->fields['valor_pago_gastos']."</td></tr>";
		return $out;	
	}

	function Reestablecer($decimales_cobro)
	{
		$out = "<tr><td>";
		if($this->Loaded())
		{
			$documento_cobro = new Documento($this->sesion);
			if($documento_cobro->Load($this->fields['id_documento_cobro']))
			{
				$out.= $documento_cobro->fields['id_cobro']."</td><td>";

				$saldo_cobro_honorarios = $documento_cobro->fields['saldo_honorarios'];
				$saldo_cobro_gastos = $documento_cobro->fields['saldo_gastos'];

				$out .= $saldo_cobro_honorarios."</td><td>";
				$out .= $this->fields['valor_cobro_honorarios']."</td><td>";

				$saldo_cobro_honorarios += $this->fields['valor_cobro_honorarios'];

				$out .= $saldo_cobro_honorarios."</td>";

				$saldo_cobro_gastos += $this->fields['valor_cobro_gastos'];

				if($saldo_cobro_gastos != 0)
					$documento_cobro->Edit('gastos_pagados','NO');
				if($saldo_cobro_honorarios != 0)
					$documento_cobro->Edit('honorarios_pagados','NO');

				$documento_cobro->Edit('saldo_gastos', number_format( $saldo_cobro_gastos, $decimales_cobro, '.' , ''));
				$documento_cobro->Edit('saldo_honorarios', number_format( $saldo_cobro_honorarios, $decimales_cobro, '.' , ''));

				$documento_cobro->Write();

				$this->CambiarEstadoCobro($documento_cobro->fields['id_cobro'],$saldo_cobro_honorarios,$saldo_cobro_gastos);
				
				$documento_pago = new Documento($this->sesion);
				$documento_pago->Load($this->fields['id_documento_pago']);
				
				$documento_pago->Edit('saldo_pago', $documento_pago->fields['saldo_pago'] - $this->fields['valor_pago_honorarios'] - $this->fields['valor_pago_gastos']);
				$documento_pago->Write();
				
				$this->Edit('valor_cobro_honorarios','0');
				$this->Edit('valor_cobro_gastos','0');
				$this->Edit('valor_pago_honorarios','0');
				$this->Edit('valor_pago_gastos','0');
				$this->Write();
			}

			//Elimino la provisión que se pudo haber generado por pagar gastos
			$query = "DELETE from cta_corriente WHERE cta_corriente.neteo_pago = '".$this->fields['id_neteo_documento']."' ";
			mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);

		}
		return $out;
	}

	function Escribir($pago_honorarios, $pago_gastos, $cambio_pago, $cambio_cobro, $decimales_pago, $decimales_cobro, $id_cobro, $pagar_facturas=false)
	{
		$out = "";
		$valor_pago_original = number_format($pago_honorarios + $pago_gastos,$decimales_pago,'.','');
		$pago_gastos = number_format($pago_gastos,$decimales_pago,'.','');
		$pago_honorarios = number_format($pago_honorarios,$decimales_pago,'.','');

		$this->Edit('valor_pago_honorarios', $pago_honorarios);
		$this->Edit('valor_pago_gastos', $pago_gastos);

#echo "Pago_honorarios:".$pago_honorarios."<br>";
#echo "cambioPago:".$cambio_pago."<br>";
#echo "cambioCobro:".$cambio_cobro."<br>";

		$cobro_gastos =		$pago_gastos		* $cambio_pago / $cambio_cobro;
		$cobro_honorarios =	$pago_honorarios	* $cambio_pago / $cambio_cobro;

		$cobro_gastos = number_format($cobro_gastos, $decimales_cobro,'.','');
		$cobro_honorarios = number_format($cobro_honorarios, $decimales_cobro,'.','');

		$this->Edit('valor_cobro_gastos',$cobro_gastos);
		$this->Edit('valor_cobro_honorarios',$cobro_honorarios);

		if( $this->Write() )
		{
			$out .= "<td>";
			$out .= $cobro_honorarios."</td><td>";

			$documento_cobro = new Documento($this->sesion);
			if($documento_cobro->Load($this->fields['id_documento_cobro']))
			{
		
				/* HONORARIOS */
				$saldo_cobro_honorarios = $documento_cobro->fields['saldo_honorarios'];
				$saldo_cobro_honorarios -= $this->fields['valor_cobro_honorarios'];
				if(($saldo_cobro_honorarios <= 0 && $documento_cobro->fields['honorarios']>=0) || ($saldo_cobro_honorarios >= 0 && $documento_cobro->fields['honorarios']<=0) )
				{
					$documento_cobro->Edit('honorarios_pagados','SI');
				}
				$documento_cobro->Edit('saldo_honorarios', number_format( $saldo_cobro_honorarios, $decimales_cobro, '.' , ''));

				
				$out .= $saldo_cobro_honorarios."</td></tr>";

				/* GASTOS */
				$saldo_cobro_gastos = $documento_cobro->fields['saldo_gastos'];
				$saldo_cobro_gastos -= $this->fields['valor_cobro_gastos'];
				if($saldo_cobro_gastos <= 0)
				{
					$documento_cobro->Edit('gastos_pagados','SI');
				}
				$documento_cobro->Edit('saldo_gastos', number_format( $saldo_cobro_gastos, $decimales_cobro, '.' , ''));
				
				/* PAGO */
				$documento_pago = new Documento($this->sesion);
				$documento_pago->Load($this->fields['id_documento_pago']);
				$saldo_pago = $documento_pago->fields['saldo_pago'];
				$saldo_pago += $valor_pago_original;
				$documento_pago->Edit('saldo_pago',number_format($saldo_pago,$decimales_pago,'.',''));

				$documento_cobro->Write();
				$documento_pago->Write();

				if($documento_cobro->fields['saldo_gastos'] <= 0 && $documento_cobro->fields['saldo_honorarios'] == 0 && $id_cobro)
				{
					$cobro = new Cobro($this->sesion);
					$cobro->Load($id_cobro);
					if($cobro->Loaded())
					{ /*
						if($cobro->fields['estado'] != 'PAGADO')
						{
							#Se ingresa la anotación en el historial
							$his = new Observacion($this->sesion);
							$his->Edit('fecha',date('Y-m-d H:i:s'));
							$his->Edit('comentario',__('COBRO PAGADO'));
							$his->Edit('id_usuario',$this->sesion->usuario->fields['id_usuario']);
							$his->Edit('id_cobro',$id_cobro);
							$his->Write();
						}*/
						$cobro->Edit('estado','PAGADO');
						$cobro->Write();
					}
				}

				if($pago_gastos > 0)
				{
						$provision = new Gasto($this->sesion);
						$provision->Edit('id_moneda',$documento_pago->fields['id_moneda']);
						$provision->Edit('ingreso',$pago_gastos);
						$provision->Edit('monto_cobrable',$pago_gastos);
						$provision->Edit('id_cobro','NULL');
                                                $provision->Edit('id_usuario',$this->sesion->usuario->fields['id_usuario']);
                                                $provision->Edit('id_usuario_orden',$this->sesion->usuario->fields['id_usuario']);
						$provision->Edit('codigo_cliente', $documento_pago->fields['codigo_cliente']);

						$query_gastos = "SELECT cta_corriente.codigo_asunto FROM cta_corriente 
											WHERE (cta_corriente.id_cobro = '$id_cobro') LIMIT 1 ";
						$resp = mysql_query($query_gastos, $this->sesion->dbh) or Utiles::errorSQL($query_gastos,__FILE__,__LINE__,$this->sesion->dbh);
						list($codigo_asunto) = mysql_fetch_array($resp);
						if($codigo_asunto)
							$provision->Edit('codigo_asunto',$codigo_asunto);
						else				
							$provision->Edit('codigo_asunto','NULL');

						if($id_cobro)
							$provision->Edit('descripcion',"Pago de Gastos de Cobro #".$id_cobro." por Documento #".$documento_pago->fields['id_documento']);
						else
							$provision->Edit('descripcion',"Pago de Gastos por Documento #".$documento_pago->fields['id_documento']." para documento de cobro externo");
						$provision->Edit('neteo_pago',$this->fields['id_neteo_documento']);
						$provision->Edit('incluir_en_cobro','NO');
						$provision->Edit('fecha',date('Y-m-d H:i:s'));
						$provision->Write();
				}
				
				if(UtilesApp::GetConf($this->sesion, 'NuevoModuloFactura') && !empty($documento_pago->fields['es_adelanto'])){
					$factura_pago = new FacturaPago($this->sesion);
					$factura_pago->LoadByNeteoAdelanto($this->fields[$this->campo_id]);
					
					if(!$factura_pago->Id()){
						$query = "SELECT id_concepto FROM prm_factura_pago_concepto WHERE glosa = 'Adelanto'";
						$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
						list($id_concepto) = mysql_fetch_array($resp);
						
						$factura_pago->Edit('id_concepto', $id_concepto);
						$factura_pago->Edit('fecha', date('Y-m-d H:i:s'));
						$factura_pago->Edit('id_neteo_documento_adelanto', $this->fields['id_neteo_documento']);
						
						//copiar del adelanto
						$factura_pago->Edit('codigo_cliente', $documento_pago->fields['codigo_cliente']);
						$factura_pago->Edit('tipo_doc', $documento_pago->fields['tipo_doc']);
						$factura_pago->Edit('nro_documento', $documento_pago->fields['numero_doc']);
						$factura_pago->Edit('nro_cheque', $documento_pago->fields['numero_cheque']);
						$factura_pago->Edit('descripcion', 'Adelanto #'.$documento_pago->fields['id_documento'].' - '. $documento_pago->fields['glosa_documento']);
						$factura_pago->Edit('id_banco', $documento_pago->fields['id_banco']);
						$factura_pago->Edit('id_cuenta', $documento_pago->fields['id_cuenta']);
						$factura_pago->Edit('pago_retencion', $documento_pago->fields['pago_retencion']);
					}
					$factura_pago->Edit('id_moneda', $documento_pago->fields['id_moneda']);
					$factura_pago->Edit('monto', (float)$this->fields['valor_pago_gastos']+(float)$this->fields['valor_pago_honorarios']);
					$factura_pago->Edit('id_moneda_cobro', $documento_cobro->fields['id_moneda']);
					$factura_pago->Edit('monto_moneda_cobro', (float)$this->fields['valor_cobro_gastos']+(float)$this->fields['valor_cobro_honorarios']);
					
					//agregarle columnas saldo_gastos y saldo_honorarios al factura_pago?
					$nueva = !$factura_pago->Id();
					
					if($factura_pago->Write()){
						$ccf = new CtaCteFact($this->sesion);
						$neteos = array();
						if($pagar_facturas && $nueva){
							$query = "SELECT f.id_factura, ccfm.saldo
								FROM cta_cte_fact_mvto AS ccfm
								JOIN factura AS f ON f.id_factura = ccfm.id_factura
								WHERE ccfm.saldo < 0 AND f.id_cobro = '$id_cobro' AND f.id_moneda = '".$documento_pago->fields['id_moneda']."'";
							$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
							
							$saldo_pago = (float)$this->fields['valor_pago_gastos']+(float)$this->fields['valor_pago_honorarios'];
							while(list($id_factura, $saldo_factura) = mysql_fetch_array($resp)){
								$monto = min($saldo_pago, -$saldo_factura);
								$saldo_pago -= $monto;
								$neteos[] = array($id_factura, $monto);
								if($saldo_pago<=0) break;
							}
						}
						$pagina_fake = ''; //la belleza del TT
						$ccf->IngresarPago($factura_pago, $neteos, $id_cobro, $pagina_fake, '', '', empty($neteos));
					}
				}
			}
		}	
		return $out;
	}

	function CambiarEstadoCobro($id_cobro,$saldo_cobro_honorarios,$saldo_cobro_gastos)
	{
		$cobro = new Cobro($this->sesion);
		$cobro->Load($id_cobro);
		if($cobro->Loaded())
		{
			//echo $cobro->fields['estado'] . "<br>" . $saldo_cobro_honorarios . "<br>" . $saldo_cobro_gastos;
			if( ( ( $cobro->fields['estado']=='PAGADO')  || ($cobro->fields['estado']=='PAGO PARCIAL') ) && (($saldo_cobro_honorarios!=0) || ($saldo_cobro_gastos!=0)))
			{
				if( $cobro->TienePago() )
				{
					$cobro->Edit('estado','PAGO PARCIAL');
				}
				elseif( $cobro->TieneFacturasSinAnular() )
				{
					if( !empty($cobro->fields['fecha_enviado_cliente']) && $cobro->fields['fecha_enviado_cliente'] != '0000-00-00 00:00:00' ) {
						$cobro->Edit('estado','ENVIADO AL CLIENTE');
					} else {
						if(UtilesApp::GetConf($this->sesion,'NuevoModuloFactura'))
						{
							$cobro->Edit('estado','FACTURADO');
						} else {
							$cobro->Edit('estado','EMITIDO');
						}
					}
				}
				else {
					$cobro->Edit('estado','EMITIDO');					
				}
                $cobro->Write();
			}
			elseif((($cobro->fields['estado']=='EMITIDO') || ($cobro->fields['estado']=='ENVIADO AL CLIENTE') || ($cobro->fields['estado']=='FACTURADO') || ($cobro->fields['estado']=='PAGO PARCIAL') ) && (($saldo_cobro_honorarios<=0) && ($saldo_cobro_gastos<=0)))
			{
				$cobro->Edit('estado','PAGADO');
                $cobro->Write();
			}
		}
	}

}



class ListaNeteoDocumentos extends Lista
{
    function ListaNeteoDocumentos($sesion, $params, $query)
    {
        $this->Lista($sesion, 'NeteoDocumento', $params, $query);
    }
}
