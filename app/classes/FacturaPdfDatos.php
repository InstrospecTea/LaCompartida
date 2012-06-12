<?php
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/classes/UtilesApp.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/classes/Factura.php';
	require_once Conf::ServerDir().'/classes/Moneda.php';
        require_once Conf::ServerDir() . '/classes/Contrato.php';
	
	class FacturaPdfDatos extends Objeto
	{
		function FacturaPdfDatos($sesion, $fields = "", $params = "")
		{
			$this->tabla = "factura_pdf_datos";
			$this->campo_id = "id_dato";
			$this->guardar_fecha = false;
			$this->sesion = $sesion;
			$this->fields = $fields;
                        $this->papel=array();
		}
		
		function CargarDatos( $id_factura, $id_documento_legal ) 
		{
			$query = "SELECT 
						codigo_tipo_dato, 
						activo, 
						coordinateX, 
						coordinateY, 
                                                cellW,
                                                cellH,
						font, 
						style, 
						mayuscula, 
						tamano , align
					FROM factura_pdf_datos 
                                        JOIN factura_pdf_tipo_datos USING( id_tipo_dato ) 
					WHERE activo = 1 AND id_documento_legal = '$id_documento_legal' ";
			$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			
			while( $row = mysql_fetch_assoc($resp) )
			{
				foreach($row as $tipo_dato => $valor) {
					if( $tipo_dato == 'codigo_tipo_dato' ) {
						$this->datos[$row['codigo_tipo_dato']]['dato_letra'] = $this->CargarGlosaDato($valor, $id_factura);
					} else {
						$this->datos[$row['codigo_tipo_dato']][$tipo_dato] = $valor;
					}
				}
			}
			/*$factura = new Factura($this->sesion);
			$factura->Load($id_factura);
		
			echo '<pre>';print_r($factura->fields);echo '</pre>';*/
			$querypapel = "SELECT 
						codigo_tipo_dato, 
						activo, 
						coordinateX, 
						coordinateY, 
                                                cellW,
                                                cellH,
						font, 
						style, 
						mayuscula, 
						tamano 
					FROM factura_pdf_datos 
                                        JOIN factura_pdf_tipo_datos USING( id_tipo_dato ) 
					WHERE codigo_tipo_dato = 'tipo_papel' AND id_documento_legal= '$id_documento_legal' limit 1";
			
			$resppapel = mysql_query($querypapel,$this->sesion->dbh) or Utiles::errorSQL($querypapel,__FILE__,__LINE__,$this->sesion->dbh);
			
			$this->papel = mysql_fetch_assoc($resppapel);
			
		}
		
		function CargarGlosaDato( $tipo_dato, $id_factura )
		{
			$factura = new Factura($this->sesion);
			$factura->Load($id_factura);
			
			$cobro = new Cobro($this->sesion);
			$cobro->Load($factura->fields['id_cobro']);
                        
			$contrato = new Contrato($this->sesion);
                        $contrato->Load($cobro->fields['id_contrato']);
                        
			$idioma = new Objeto($this->sesion,'','','prm_idioma','codigo_idioma');
			$idioma->Load( $cobro->fields['codigo_idioma'] ); 
			
			$arreglo_monedas = ArregloMonedas($this->sesion);
			$monto_palabra=new MontoEnPalabra($this->sesion);
			
			switch( $tipo_dato ) {
				case 'razon_social': 			$glosa_dato = $factura->fields['cliente']; break;
				case 'rut': 				$glosa_dato = $factura->fields['RUT_cliente']; break;
                                case 'telefono': 			$glosa_dato = $contrato->fields['factura_telefono']; break;
				case 'fecha_dia': 	 		$glosa_dato = date("d",strtotime($factura->fields['fecha'])); break;
				case 'fecha_mes':			$glosa_dato = strftime("%B",strtotime($factura->fields['fecha'])); break; 
				case 'fecha_ano':			$glosa_dato = date("Y",strtotime($factura->fields['fecha'])); break;
				case 'fecha_ano_ultima_cifra':		$glosa_dato = substr(date("Y",strtotime($factura->fields['fecha'])),-1); break;
				case 'direccion':			$glosa_dato = $factura->fields['direccion_cliente']; break;
				case 'descripcion_honorarios':		$glosa_dato = $factura->fields['descripcion']; break;
				case 'descripcion_gastos_con_iva': 	$glosa_dato = $factura->fields['descripcion_subtotal_gastos']; break;
				case 'descripcion_gastos_sin_iva':	$glosa_dato = $factura->fields['descripcion_subtotal_gastos_sin_impuesto']; break;
				case 'monto_honorarios': 		$glosa_dato = number_format($factura->fields['subtotal_sin_descuento'],$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']); break;
				case 'monto_gastos_con_iva': 		$glosa_dato = number_format($factura->fields['subtotal_gastos'],$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']); break;
				case 'monto_gastos_sin_iva': 		$glosa_dato = number_format($factura->fields['subtotal_gastos_sin_impuesto'],$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']); break;
				case 'moneda_honorarios': 		$glosa_dato = $arreglo_monedas[$factura->fields['id_moneda']]['simbolo']; break;
				case 'moneda_gastos_con_iva': 		$glosa_dato = $arreglo_monedas[$factura->fields['id_moneda']]['simbolo']; break;
				case 'moneda_gastos_sin_iva': 		$glosa_dato = $arreglo_monedas[$factura->fields['id_moneda']]['simbolo']; break;
				case 'monto_en_palabra': 		$glosa_dato = strtoupper($monto_palabra->ValorEnLetras($factura->fields['total'],$factura->fields['id_moneda'],$arreglo_monedas[$factura->fields['id_moneda']]['glosa_moneda'],$arreglo_monedas[$factura->fields['id_moneda']]['glosa_moneda_plural'])); break;
				case 'porcentaje_impuesto': 		$glosa_dato = $factura->fields['porcentaje_impuesto']."%"; break;
				case 'moneda_subtotal': 		$glosa_dato = $arreglo_monedas[$factura->fields['id_moneda']]['simbolo']; break;
				case 'moneda_iva': 			$glosa_dato = $arreglo_monedas[$factura->fields['id_moneda']]['simbolo']; break;
				case 'moneda_total': 			$glosa_dato = $arreglo_monedas[$factura->fields['id_moneda']]['simbolo']; break;
				case 'monto_subtotal': 			$glosa_dato = number_format( 
												$factura->fields['subtotal_sin_descuento'] + $factura->fields['subtotal_gastos'] + $factura->fields['subtotal_gastos_sin_impuesto'],
												$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'],
												$idioma->fields['separador_decimales'],
												$idioma->fields['separador_decimales']); break;
				case 'monto_iva': 			$glosa_dato = number_format($factura->fields['iva'],$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']); break;
				case 'monto_total':			$glosa_dato = number_format($factura->fields['total'],$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']); break;
			}
			
			return $glosa_dato;
		}
		
                function CargarFilaDato( $id_factura )
		{
			$factura = new Factura($this->sesion);
			$factura->Load($id_factura);
			
			$cobro = new Cobro($this->sesion);
			$cobro->Load($factura->fields['id_cobro']);
			
			$contrato = new Contrato($this->sesion);
                        $contrato->Load($cobro->fields['id_contrato']);
                        
                        
                        
			$idioma = new Objeto($this->sesion,'','','prm_idioma','codigo_idioma');
			$idioma->Load( $cobro->fields['codigo_idioma'] ); 
			
			$arreglo_monedas = ArregloMonedas($this->sesion);
			$monto_palabra=new MontoEnPalabra($this->sesion);
			$fila=array();
			
				$fila['razon_social']= $factura->fields['cliente']; 
				$fila[ 'rut']= 				$factura->fields['RUT_cliente'];
				$fila[ 'telefono']=  $contrato->fields['factura_telefono']; 
                                $fila[ 'fecha_dia']= 	 		date("d",strtotime($factura->fields['fecha'])); 
				$fila[ 'fecha_mes']=			strftime("%B",strtotime($factura->fields['fecha']));  
				$fila[ 'fecha_ano']=			date("Y",strtotime($factura->fields['fecha']));
				$fila[ 'fecha_ano_ultima_cifra']=		substr(date("Y",strtotime($factura->fields['fecha'])),-1); 
				$fila[ 'direccion']=			$factura->fields['direccion_cliente']; 
				$fila[ 'descripcion_honorarios']=		$factura->fields['descripcion']; 
				$fila[ 'descripcion_gastos_con_iva']= 	$factura->fields['descripcion_subtotal_gastos']; 
				$fila[ 'descripcion_gastos_sin_iva']=	$factura->fields['descripcion_subtotal_gastos_sin_impuesto']; 
				$fila[ 'monto_honorarios']= 		number_format($factura->fields['subtotal_sin_descuento'],$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']); 
				$fila[ 'monto_gastos_con_iva']= 		number_format($factura->fields['subtotal_gastos'],$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']); 
				$fila[ 'monto_gastos_sin_iva']= 		number_format($factura->fields['subtotal_gastos_sin_impuesto'],$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']); 
				$fila[ 'moneda_honorarios']= 		$arreglo_monedas[$factura->fields['id_moneda']]['simbolo']; 
				$fila[ 'moneda_gastos_con_iva']= 		$arreglo_monedas[$factura->fields['id_moneda']]['simbolo']; 
				$fila[ 'moneda_gastos_sin_iva']= 		$arreglo_monedas[$factura->fields['id_moneda']]['simbolo']; 
				$fila[ 'monto_en_palabra']= 		strtoupper($monto_palabra->ValorEnLetras($factura->fields['total'],$factura->fields['id_moneda'],$arreglo_monedas[$factura->fields['id_moneda']]['glosa_moneda'],$arreglo_monedas[$factura->fields['id_moneda']]['glosa_moneda_plural'])); 
				$fila[ 'porcentaje_impuesto']= 		$factura->fields['porcentaje_impuesto']."%"; 
				$fila[ 'moneda_subtotal']= 		$arreglo_monedas[$factura->fields['id_moneda']]['simbolo']; 
				$fila[ 'moneda_iva']= 			$arreglo_monedas[$factura->fields['id_moneda']]['simbolo']; 
				$fila[ 'moneda_total']= 			$arreglo_monedas[$factura->fields['id_moneda']]['simbolo']; 
				$fila[ 'monto_subtotal']= 			number_format( 
												$factura->fields['subtotal_sin_descuento'] + $factura->fields['subtotal_gastos'] + $factura->fields['subtotal_gastos_sin_impuesto'],
												$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'],
												$idioma->fields['separador_decimales'],
												$idioma->fields['separador_decimales']); 
				$fila[ 'monto_iva']= 			number_format($factura->fields['iva'],$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']); 
				$fila[ 'monto_total']=			number_format($factura->fields['total'],$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']); 
						
			return $fila;
		}
                
		function generarFacturaPDF($id_factura, $mantencion = false,$orientacion='P',$format='Letter')
		{
			require_once Conf::ServerDir().'/fpdf/fpdf.php';
			$factura = new Factura( $this->sesion );
			if( !$factura->Load( $id_factura ) ) {
				echo "<html><head><title>Error</title></head><body><p>No se encuentra la factura $id_factura.</p></body></html>";
				return;
			}
                        
                        $query = " SELECT id_documento_legal, codigo, glosa FROM prm_documento_legal WHERE id_documento_legal = '".$factura->fields['id_documento_legal']."' ";
			$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			list( $id_documento_legal, $codigo_documento_legal, $glosa_documento_legal) = mysql_fetch_array($resp);
	
			$this->CargarDatos( $id_factura, $id_documento_legal ); // esto trae la posicion, tamaño y glosa de todos los campos más los datos del papel en la variable $this->papel;
		 	
                 if(count($this->papel)) {
                        $pdf = new FPDF($orientacion, 'mm', array($this->papel['cellW'],$this->papel['cellH']));
							$pdf->SetMargins($this->papel['coordinateX'],$this->papel['coordinateY']);
							$pdf->SetAutoPageBreak(true,2*$margin);
                } else {
			// P: hoja vertical
			// mm: todo se mide en milímetros
			// Letter: formato de hoja
						$pdf = new FPDF($orientacion, 'mm', $format);
                 }
	
			
			$query = " SELECT codigo, glosa FROM prm_documento_legal WHERE id_documento_legal = '".$factura->fields['id_documento_legal']."' ";
			$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			list($codigo_documento_legal, $glosa_documento_legal) = mysql_fetch_array($resp);
	
			$pdf->SetTitle($glosa_documento_legal ." ".$factura->fields['numero']);
			// La orientación y formato de la página son los mismos que del documento
			$pdf->AddPage();
			$datos['dato_letra']=str_replace(array("<br>\n","<br/>\n","<br />\n" ),"\n",$datos['dato_letra']);
			
			//echo '<pre>';print_r($this->datos);echo '</pre>';
			if (intval($this->datos['monto_honorarios']['dato_letra'])===0) {
			    unset($this->datos['monto_honorarios']);
			    unset($this->datos['moneda_honorarios']);
			    unset($this->datos['descripcion_honorarios']);
			}
			if (intval($this->datos['monto_gastos_con_iva']['dato_letra'])===0) {
			    unset($this->datos['monto_gastos_con_iva']);
			    unset($this->datos['moneda_gastos_con_iva']);
			    unset($this->datos['descripcion_gastos_con_iva']);
			}
			if (intval($this->datos['monto_gastos_sin_iva']['dato_letra'])===0) {
			    unset($this->datos['monto_gastos_sin_iva']);
			    unset($this->datos['moneda_gastos_sin_iva']);
			    unset($this->datos['descripcion_gastos_sin_iva']);
			}
			 
			foreach( $this->datos as $tipo_dato => $datos ) {
			    
			  
				$pdf->SetFont($datos['font'], $datos['style'], $datos['tamano']);
				$pdf->SetXY($datos['coordinateX'],$datos['coordinateY']);
				
                               if( $datos['cellH'] > 0 || $datos['cellW'] > 0 ) {
                                        $pdf->MultiCell( $datos['cellW'], $datos['cellH'], $datos['dato_letra'],0,( $datos['align']?:'L') );
                                } else if( $datos['mayuscula'] == 'may' ) {
					$pdf->Write(4, strtoupper($datos['dato_letra']));
				} else if( $datos['mayuscula'] == 'min' ) {
					$pdf->Write(4, strtolower($datos['dato_letra']));
				} else {
					$pdf->Write(4, $datos['dato_letra']);
				}
			}
			
                        if( $mantencion ) {
                         //   $pdf->Output("../../pdf/factura.pdf","F");
                        } else {
                            $pdf->Output($glosa_documento_legal."_".$factura->fields['numero'].".pdf","D");
                        }
		}
	}
	
?>
