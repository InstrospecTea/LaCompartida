<?php
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/classes/UtilesApp.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/classes/Factura.php';
	require_once Conf::ServerDir().'/classes/Moneda.php';
	
	class FacturaPdfDatos extends Objeto
	{
		function FacturaPdfDatos($sesion, $fields = "", $params = "")
		{
			$this->tabla = "factura_pdf_datos";
			$this->campo_id = "id_tipo_dato";
			$this->guardar_fecha = false;
			$this->sesion = $sesion;
			$this->fields = $fields;
		}
		
		function CargarDatos( $id_factura ) 
		{
			$query = "SELECT 
									tipo_dato,
									activo,
									coordinateX,
									coordinateY,
									font,
									style,
									mayuscula,
									tamano 
								FROM factura_pdf_datos
								WHERE activo = 1";
			$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			
			while( $row = mysql_fetch_assoc($resp) )
			{
				foreach($row as $tipo_dato => $valor) {
					if( $tipo_dato == 'tipo_dato' ) {
						$this->datos[$row['tipo_dato']]['dato_letra'] = $this->CargarGlosaDato($valor, $id_factura);
					} else {
						$this->datos[$row['tipo_dato']][$tipo_dato] = $valor;
					}
				}
			}
		}
		
		function CargarGlosaDato( $tipo_dato, $id_factura )
		{
			$factura = new Factura($this->sesion);
			$factura->Load($id_factura);
			
			$cobro = new Cobro($this->sesion);
			$cobro->Load($factura->fields['id_cobro']);
			
			$idioma = new Objeto($this->sesion,'','','prm_idioma','codigo_idioma');
			$idioma->Load( $cobro->fields['codigo_idioma'] ); 
			
			$arreglo_monedas = ArregloMonedas($this->sesion);
			$monto_palabra=new MontoEnPalabra($this->sesion);
			
			switch( $tipo_dato ) {
				case 'razon_social': 								$glosa_dato = $factura->fields['cliente']; break;
				case 'rut': 				 								$glosa_dato = $factura->fields['RUT_cliente']; break;
				case 'fecha_dia': 	 								$glosa_dato = date("d",strtotime($factura->fields['fecha'])); break;
				case 'fecha_mes':										$glosa_dato = strftime("%B",strtotime($factura->fields['fecha'])); break; 
				case 'fecha_ano':										$glosa_dato = date("Y",strtotime($factura->fields['fecha'])); break;
				case 'direccion':										$glosa_dato = $factura->fields['direccion_cliente']; break;
				case 'descripcion_honorarios':			$glosa_dato = $factura->fields['descripcion']; break;
				case 'descripcion_gastos_con_iva': 	$glosa_dato = $factura->fields['descripcion_subtotal_gastos']; break;
				case 'descripcion_gastos_sin_iva':	$glosa_dato = $factura->fields['descripcion_subtotal_gastos_sin_impuesto']; break;
				case 'monto_honorarios': 						$glosa_dato = number_format($factura->fields['subtotal_sin_descuento'],$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']); break;
				case 'monto_gastos_con_iva': 				$glosa_dato = number_format($factura->fields['subtotal_gastos'],$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']); break;
				case 'monto_gastos_sin_iva': 				$glosa_dato = number_format($factura->fields['subtotal_gastos_sin_impuesto'],$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']); break;
				case 'moneda_honorarios': 					$glosa_dato = $arreglo_monedas[$factura->fields['id_moneda']]['simbolo']; break;
				case 'moneda_gastos_con_iva': 			$glosa_dato = $arreglo_monedas[$factura->fields['id_moneda']]['simbolo']; break;
				case 'moneda_gastos_sin_iva': 			$glosa_dato = $arreglo_monedas[$factura->fields['id_moneda']]['simbolo']; break;
				case 'monto_en_palabra': 						$glosa_dato = strtoupper($monto_palabra->ValorEnLetras($factura->fields['total'],$factura->fields['id_moneda'],$arreglo_monedas[$factura->fields['id_moneda']]['glosa_moneda'],$arreglo_monedas[$factura->fields['id_moneda']]['glosa_moneda_plural'])); break;
				case 'porcentaje_impuesto': 				$glosa_dato = $factura->fields['porcentaje_impuesto']."%"; break;
				case 'moneda_subtotal': 						$glosa_dato = $arreglo_monedas[$factura->fields['id_moneda']]['simbolo']; break;
				case 'moneda_iva': 									$glosa_dato = $arreglo_monedas[$factura->fields['id_moneda']]['simbolo']; break;
				case 'moneda_total': 								$glosa_dato = $arreglo_monedas[$factura->fields['id_moneda']]['simbolo']; break;
				case 'monto_subtotal': 							$glosa_dato = number_format( 
																														$factura->fields['subtotal_sin_descuento'] + $factura->fields['monto_gastos_con_iva'] + $factura->fields['monto_gastos_sin_iva'],
																														$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'],
																														$idioma->fields['separador_decimales'],
																														$idioma->fields['separador_decimales']); break;
				case 'monto_iva': 									$glosa_dato = number_format($factura->fields['iva'],$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']); break;
				case 'monto_total':									$glosa_dato = number_format($factura->fields['total'],$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']); break;
			}
			
			return $glosa_dato;
		}
		
		function generarFacturaPDF($id_factura)
		{
			require_once Conf::ServerDir().'/../app/fpdf/fpdf.php';
			$factura = new Factura( $this->sesion );
			if( !$factura->Load( $id_factura ) ) {
				echo "<html><head><title>Error</title></head><body><p>No se encuentra la factura $numero_factura.</p></body></html>";
				return;
			}
			$this->CargarDatos( $id_factura );
			
			// P: hoja vertical
			// mm: todo se mide en milímetros
			// Letter: formato de hoja
			$pdf = new FPDF('P', 'mm', 'Letter');
	
			// Dimensiones de una hoja tamaño carta.
			$ancho = 216;
			$alto = 279;
			
			$query = " SELECT codigo, glosa FROM prm_documento_legal WHERE id_documento_legal = '".$factura->fields['id_documento_legal']."' ";
			$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			list($codigo_documento_legal, $glosa_documento_legal) = mysql_fetch_array($resp);
	
			$pdf->SetTitle($glosa_documento_legal ." ".$factura->fields['numero']);
			// La orientación y formato de la página son los mismos que del documento
			$pdf->AddPage();
			
			foreach( $this->datos as $tipo_dato => $datos ) {
				$pdf->SetFont($datos['font'], $datos['style'], $datos['tamano']);
				$pdf->SetXY($datos['coordinateX'],$datos['coordinateY']);
				if( $datos['mayuscula'] == 'may' ) {
					$pdf->Write(4, strtoupper($datos['dato_letra']));
				} else if( $datos['mayuscula'] == 'min' ) {
					$pdf->Write(4, strtolower($datos['dato_letra']));
				} else {
					$pdf->Write(4, $datos['dato_letra']);
				}
			}
			
			$pdf->Output($glosa_documento_legal."_".$factura->fields['numero'].".pdf","D");
		}
	}
	
?>
