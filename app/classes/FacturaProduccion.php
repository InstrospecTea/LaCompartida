<?php

require_once dirname(__FILE__) . '/../conf.php';

class FacturaProduccion {

    public static $configuracion_reporte = array (
        array (
            'field' => 'codigo_cliente',
            'title' => 'Código Cliente',
            'visible' => false,
        ),
        array (
            'field' => 'glosa_cliente',
            'title' => 'Cliente',
        ),
        array (
            'field' => 'fecha',
            'format' => 'date',
            'title' => 'Fecha Documento',
        ),
        array (
            'field' => 'tipo',
            'title' => 'Tipo',
        ),
        array (
            'field' => 'serie_documento_legal',
            'title' => 'Serie Documento',
            'visible' => false,
        ),
        array (
            'field' => 'numero',
            'format' => 'number',
            'title' => 'N° Documento',
            'extras' => array(
                'subtotal' => false
            )
        ),
        array (
            'field' => 'factura_rsocial',
            'title' => 'Razón Social',
        ),
        array (
            'field' => 'glosas_asunto',
            'title' => 'Asuntos',
        ),
        array (
            'field' => 'codigos_asunto',
            'title' => 'Códigos Asuntos',
        ),
        array (
            'field' => 'encargado_comercial',
            'title' => 'Encargado Comercial',
        ),
        array (
            'field' => 'descripcion',
            'title' => 'Descripción Factura',
        ),
        array (
            'field' => 'id_cobro',
            'title' => 'N° Liquidación',
        ),
        array (
            'field' => 'idcontrato',
            'title' => 'Acuerdo Comercial',
            'visible' => false,
        ),
        array (
            'field' => 'codigo_contrato',
            'title' => 'codigo_contrato' ,
            'visible' => false,
        ),
        array (
            'field' => 'simbolo',
            'visible' => false,
            'title' => 'Símbolo Moneda',
        ),
        array (
            'field' => 'tipo_cambio',
            'format' => 'number',
            'title' => 'Tipo Cambio',
            'visible' => false,
        ),
        array (
            'field' => 'honorarios',
            'format' => 'number',
            'title' => 'Honorarios',
            'extras' =>
            array (
                'symbol' => 'simbolo',
                'subtotal' => 'simbolo'
            ),
        ),
        array (
            'field' => 'subtotal_gastos',
            'format' => 'number',
            'title' => 'Subtotal Gastos',
            'extras' =>
            array (
                'symbol' => 'simbolo',
                'subtotal' => 'simbolo'
            ),
        ),
        array (
            'field' => 'subtotal_gastos_sin_impuesto',
            'format' => 'number',
            'title' => 'Subtotal Gastos sin impuesto',
            'extras' =>
            array (
                'symbol' => 'simbolo',
                'subtotal' => 'simbolo'
            ),
        ),
        array (
            'field' => 'subtotal',
            'format' => 'number',
            'title' => 'Subtotal',
            'extras' =>
            array (
                'symbol' => 'simbolo',
                'subtotal' => 'simbolo'
            ),
        ),
        array (
            'field' => 'iva',
            'format' => 'number',
            'title' => 'IVA',
            'extras' =>
            array (
                'symbol' => 'simbolo',
                'subtotal' => 'simbolo'
            ),
        ),
        array (
            'field' => 'total',
            'format' => 'number',
            'title' => 'Total',
            'extras' =>
            array (
                'symbol' => 'simbolo',
                'subtotal' => 'simbolo'
            ),
        ),
        array (
            'field' => 'monto_real',
            'format' => 'number',
            'title' => 'Monto Real',
            'extras' =>
            array (
                'symbol' => 'simbolo',
                'subtotal' => 'simbolo'
            ),
        ),
        array (
            'field' => 'observaciones',
            'title' => 'Observaciones',
        ),
        array (
            'field' => 'pagos',
            'format' => 'number',
            'title' => 'Pagos',
            'extras' =>
            array (
                'symbol' => 'simbolo',
                'subtotal' => 'simbolo'
            ),
        ),
        array (
            'field' => 'saldo',
            'format' => 'number',
            'title' => 'Saldo',
            'extras' =>
            array (
                'symbol' => 'simbolo',
                'subtotal' => 'simbolo'
            ),
        ),
        array (
            'field' => 'fecha_ultimo_pago',
            'format' => 'date',
            'title' => 'Fecha Último Pago',
        ),
        array (
            'field' => 'estado',
            'title' => 'Estado Documento',
        ),
        array (
            'field' => 'codigo_idioma',
            'title' => 'Código Idioma',
            'visible' => false,
        ),
        array (
            'field' => 'cifras_decimales',
            'title' => 'Cifras Decimales',
            'visible' => false,
        ),

            array (
            'field' => 'id_factura',
            'title' => 'id_factura',
            'visible' => false,
        ),
    );

    function FacturaProduccion($sesion) {
        $this->sesion = $sesion;
    }

    public function QueryReporte() {
        $query = "SELECT
            prm_documento_legal.codigo as tipo
          , factura.numero
          , factura.serie_documento_legal
          , factura.codigo_cliente
          , cliente.glosa_cliente
          , contrato.id_contrato as idcontrato
          , IF( TRIM(contrato.factura_razon_social) = TRIM( factura.cliente )
                        OR contrato.factura_razon_social IN ('',' ')
                        OR contrato.factura_razon_social IS NULL,
                    factura.cliente,
                    CONCAT_WS(' ',factura.cliente,'(',contrato.factura_razon_social,')')
                ) as factura_rsocial
          , usuario.username AS encargado_comercial
          , factura.fecha
          , factura.descripcion
          , prm_estado_factura.codigo as codigo_estado
          , prm_estado_factura.glosa as estado
          , factura.id_cobro
          , cobro.codigo_idioma as codigo_idioma
          , prm_moneda.simbolo
          , prm_moneda.cifras_decimales
          , prm_moneda.tipo_cambio
          , factura.id_moneda
          , factura.honorarios
          , factura.subtotal
          , factura.subtotal_gastos
          , factura.subtotal_gastos_sin_impuesto
          , factura.iva
          , factura.total
          , factura.id_factura
          , GROUP_CONCAT(asunto.codigo_asunto SEPARATOR ';') AS codigos_asunto
          , GROUP_CONCAT(asunto.glosa_asunto SEPARATOR ';') AS glosas_asunto
          , factura.RUT_cliente
          , (   SELECT SUM(ccfmn.monto)
                FROM factura_pago AS fp
                    INNER JOIN cta_cte_fact_mvto AS ccfm ON fp.id_factura_pago = ccfm.id_factura_pago
                    INNER JOIN cta_cte_fact_mvto_neteo AS ccfmn ON ccfmn.id_mvto_pago = ccfm.id_cta_cte_mvto
                    LEFT JOIN cta_cte_fact_mvto AS ccfm2 ON ccfmn.id_mvto_deuda = ccfm2.id_cta_cte_mvto
                    WHERE ccfm2.id_factura = factura.id_factura
                    GROUP BY ccfm2.id_factura
            ) AS pagos
        FROM factura
       JOIN prm_documento_legal ON (factura.id_documento_legal = prm_documento_legal.id_documento_legal)
       JOIN prm_moneda ON prm_moneda.id_moneda=factura.id_moneda
       LEFT JOIN prm_estado_factura ON prm_estado_factura.id_estado = factura.id_estado
       LEFT JOIN cta_cte_fact_mvto ON cta_cte_fact_mvto.id_factura = factura.id_factura
       LEFT JOIN cobro ON cobro.id_cobro=factura.id_cobro
       LEFT JOIN cliente ON cliente.codigo_cliente=cobro.codigo_cliente
       LEFT JOIN contrato ON contrato.id_contrato=cobro.id_contrato
       LEFT JOIN usuario ON usuario.id_usuario=contrato.id_usuario_responsable
       LEFT JOIN cobro_asunto ON cobro_asunto.id_cobro = factura.id_cobro
       LEFT JOIN asunto ON asunto.codigo_asunto = cobro_asunto.codigo_asunto
       WHERE factura.fecha >= :fecha1 AND factura.fecha <= :fecha2";
       return $query;
    }

    public function DatosReporte($query, $params) {
        $Statement = $this->sesion->pdodbh->prepare($query);
        foreach ($params as $key => $item) {
            if (strpos($key, 'fecha') !== false) {
                $Statement->bindParam($key, Utiles::fecha2sql($item));
            } else {
                $Statement->bindParam($key, $item);
            }
        }
        $Statement->execute();
        return $Statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Descarga el reporte excel básico según configuraciones
     */
    public function DownloadReport($results, $tipo = 'Json') {
        $SimpleReport = new SimpleReport($this->sesion);
        $SimpleReport->SetRegionalFormat(UtilesApp::ObtenerFormatoIdioma($this->sesion));
        $SimpleReport->LoadConfiguration('FACTURA_PRODUCCION');
        $SimpleReport->LoadResults($results);
        $writer = SimpleReport_IOFactory::createWriter($SimpleReport, $tipo);
        $writer->save(__('Facturas'));
    }


}