###INFORME###

%COBRO_CARTA%

<hr size="2" class="separador">

%CLIENTE%

<hr size="2" class="separador">

%DETALLE_COBRO%

%RESUMEN_PROFESIONAL%

<hr size="2" class="separador">

%ASUNTOS%

<hr size="2" class="separador">

%GASTOS%

%SALTO_PAGINA%

###CLIENTE###
<span class="titulo_seccion">%glosa_cliente%</span><br>

<table class="tabla_normal" width="50%">
    <tr>
        <td width="30%">RUC</td>
        <td width="70%">%valor_rut_sin_formato%</td>
    </tr>
    <tr>
        <td>%direccion%</td>
        <td>%valor_direccion_uc%</td>
    </tr>
    <tr>
        <td>%contacto%</td>
        <td>%valor_contacto%</td>
    </tr>
    <tr>
        <td>%telefono%</td>
        <td>%valor_telefono%</td>
    </tr>
</table>

###DETALLE_COBRO###
<span class="titulo_seccion">%glosa_cobro%</span><br>

<table class="tabla_normal" width="100%">
    <tr>
        <td width="45%" valign="top">

<table  class="tabla_normal" width="100%">
    <tr>
        <td width="50%" align="left">%factura%</td>
        <td width="50%" align="left">%nro_factura%</td>
    </tr>
    <tr>
        <td width="50%" align="left">%fecha_ini%</td>
        <td width="50%" align="left">%valor_fecha_ini%</td>
    </tr>
    <tr>
        <td width="50%" align="left">%fecha_fin%</td>
        <td width="50%" align="left">%valor_fecha_fin%</td>
    </tr>
    <tr>
        <td align="left">%modalidad%</td>
        <td align="left">%valor_modalidad%</td>
    </tr>
    <tr>
        <td align="left">&nbsp;</td>
        <td align="left">%detalle_modalidad%</td>
    </tr>
</table>

        </td>
        <td width="10%">&nbsp;</td>
        <td width="45%" valign="top">

<table  class="tabla_normal" width="100%">
    <tr class="tr_datos">
        <td width="50%" align="left">%horas%</td>
        <td width="*" align="right">%valor_horas%</td>
    </tr>
    %DETALLE_COBRO_DESCUENTO%
    <tr class="tr_total3">
        <td align="left">%honorarios%</td>
        <td align="right"><b>%valor_honorarios_demo%</b></td>
    </tr>
    %DETALLE_COBRO_MONEDA_TOTAL%
    %DETALLE_TRAMITES%
    <tr class="tr_datos">
        <td align="left">%gastos%</td>
        <td align="right">%valor_gastos%</td>
    </tr>
    %IMPUESTO%
    <tr class="tr_total3">
        <td align="left">%total_cobro%</td>
        <td align="right"><b>%valor_total_cobro_demo%</b></td>
    </tr>
</table>

        </td>
    </tr>
</table>

###DETALLE_COBRO_MONEDA_TOTAL###
    <tr class="tr_datos">
        <td align="left">%monedabase%</td>
        <td align="right">%valor_honorarios_monedabase_demo%</td>
    </tr>

###DETALLE_COBRO_DESCUENTO###
    <tr>
        <td align="left">%honorarios%</td>
        <td align="right">%valor_honorarios_demo%</td>
    </tr>
    <tr>
        <td align="left">%descuento% %porcentaje_descuento_demo%</td>
        <td align="right">%valor_descuento_demo%</td>
    </tr>

###IMPUESTO###
		<tr class="tr_datos">
        <td align="left">%impuesto%</td>
        <td align="right">%valor_impuesto%</td>
    </tr>

###DETALLE_TRAMITES###
		<tr class="tr_datos">
        <td align="left">%tramites%</td>
        <td align="right">%valor_tramites%</td>
    </tr>

###RESUMEN_PROFESIONAL###
<br />
<span class="subtitulo_seccion">%glosa_profesional%</span><br>

<table class="tabla_normal" width="100%">
%RESUMEN_PROFESIONAL_ENCABEZADO%
%RESUMEN_PROFESIONAL_FILAS%
%RESUMEN_PROFESIONAL_TOTAL%
</table>

###RESUMEN_PROFESIONAL_ENCABEZADO###
<br />
<span class="subtitulo_seccion">%glosa_profesional%</span><br>
<table class="tabla_normal" width="100%">
<tr class="tr_titulo">
    <td align="left" width="*">%nombre%</td>
    <td align="center" width="80">%hh_trabajada%</td>
    <td align="center" width=%width_descontada%>%hh_descontada%</td>
    <td align="center" width=%width_cobrable%>%hh_cobrable%</td>
    <td align="center" width=%width_retainer%>%hh_retainer%</td>
    <td align="center" width="80">%hh_demo%</td>
    <td align="center" width="60">%tarifa_horas%</td>
    <td align="center" width="70">%total_horas%</td>
</tr>

###RESUMEN_PROFESIONAL_TOTAL###
<tr class="tr_total">
    <td>%glosa%</td>
    <td align="center">%hh_trabajada%</td>
    <td align="center">%hh_descontada%</td>
    <td align="center">%hh_cobrable%</td>
    <td align="center">%hh_retainer%</td>
    <td align="center">%hh_demo%</td>
    <td>&nbsp;</td>
    <td align="right">%total_horas_demo%</td>
</tr>
</table>

###ASUNTOS###
<table class="asuntos" width="70%">
    <tr>
        <td width="25%">%asunto%</td>
        <td width="75%">%glosa_asunto%</td>
    </tr>
    <tr>
        <td>%contacto%</td>
        <td>%valor_contacto%</td>
    </tr>
    <tr>
        <td>%telefono%</td>
        <td>%valor_telefono%</td>
    </tr>
</table>
<span class="subtitulo_seccion">%servicios%</span><br>
<br>
<table class="tabla_normal" width="100%">
%TRABAJOS_ENCABEZADO%
%TRABAJOS_FILAS%
%TRABAJOS_TOTAL%
</table>
<br>
<br>
<span class="titulo_seccion">%servicios_tramites%</span><br>

<table class="tabla_normal" width="100%">
%TRAMITES_ENCABEZADO%
%TRAMITES_FILAS%
%TRAMITES_TOTAL%
</table>

###TRABAJOS_ENCABEZADO###

<thead>
<tr class="tr_titulo">
    <td width="80" align="center">%fecha%</td>
    <td width="100" align="left">%profesional%</td>
    %td_categoria%
    <td width="5">&nbsp;</td>
    <td width="300" align="left">%descripcion%</td>
    %td_solicitante%
    <td width="5">&nbsp;</td>
    <td width="80" align="center">%duracion_trabajada_bmahj%</td>
    <td width="80" align="center">%duracion_descontada_bmahj%</td>
    <td width="80" align="center">%duracion_bmahj%</td>
    %td_tarifa%
    %td_importe%
</tr>
</thead>

###TRABAJOS_FILAS###
<tr class="tr_datos" style="page-break-inside:avoid;">
    <td align="center">%fecha%</td>
    <td align="left">%profesional%</td>
    %td_categoria%
		<td>&nbsp;</td>
    <td align="left">%descripcion%</td>
    %td_solicitante%
    <td>&nbsp;</td>
    <td align="center">%duracion_trabajada%</td>
    <td align="center">%duracion_descontada%</td>
    <td align="center">%duracion%</td>
    %td_tarifa%
    %td_importe%
</tr>

###TRABAJOS_TOTAL###
<tr class="tr_total">
    <td align="center">%glosa%</td>
		<td>&nbsp;</td>
		%td_categoria%
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
		<td align="center">%duracion_trabajada%</td>
		<td align="center">%duracion_descontada%</td>
    <td align="center">%duracion%</td>
    %td_tarifa%
    %td_importe%
</tr>

###TRAMITES_ENCABEZADO###
<tr class="tr_titulo">
<td width="60" align="left">%profesional%</td>
<td width="70" align="center">%fecha%</td>
	<td width="*" align="left">%descripcion%</td>
	<td width="80" align="center">%duracion_tramites%</td>
	<td width="80" align="center">%valor%</td>
</tr>

###TRAMITES_FILAS###
<tr class="tr_datos">
<td align="left">%iniciales%</td>
	<td align="center">%fecha%</td>
	<td align="left">%descripcion%</td>
	<td align="center">%duracion_tramites%</td>
	<td align="center">%valor%</td>
</tr>

###TRAMITES_TOTAL###
<tr class="tr_total">
	<td align="center" nowrap>%glosa_tramites%</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td align="center">%duracion_tramites%</td>
	<td align="center">%valor_tramites%</td>
</tr>

###DETALLE_PROFESIONAL###
<br>
<span class="subtitulo_seccion">%glosa_profesional%</span><br>

<table class="tabla_normal" width="100%">
%PROFESIONAL_ENCABEZADO%
%PROFESIONAL_FILAS%
%PROFESIONAL_TOTAL%
</table>
###PROFESIONAL_ENCABEZADO###

<tr class="tr_titulo">
    <td align="left" width="120">%nombre%</td>
    <td align="left" width="120">%categoria%</td>
    <td align="center" width="80">%hh_trabajada%</td>
    %td_descontada%
    %td_cobrable%
    %td_retainer%
    <td align="center" width="80">%hh%</td>
    %td_tarifa%
    %td_importe%
</tr>

###PROFESIONAL_FILAS###
<tr class="tr_datos">
    <td align="left">%nombre%</td>
    <td align="left">%categoria%</td>
    <td align="center">%hh_trabajada%</td>
    %td_descontada%
    %td_cobrable%
    %td_retainer%
    <td align="center">%hh_demo%</td>
    %td_tarifa%
    %td_importe%
</tr>

###PROFESIONAL_TOTAL###
<tr class="tr_total">
    <td>%glosa%</td>
    <td>&nbsp;</td>
    <td align="center">%hh_trabajada%</td>
    %td_descontada%
    %td_cobrable%
    %td_retainer%
    <td align="center">%hh_demo%</td>
    %td_tarifa%
    %td_importe%
</tr>

###GASTOS###
<br>
<span class="titulo_seccion">%glosa_gastos%</span><br>
<table class="tabla_normal" width="100%">
%GASTOS_ENCABEZADO%
%GASTOS_FILAS%
%GASTOS_TOTAL%
</table>

###GASTOS_ENCABEZADO###
<tr class="tr_titulo">
    <td align="center" width="80">%fecha%</td>
    <td align="center" width="100">%ruc_proveedor%</td>
    <td align="center">%proveedor%</td>
    <td align="left">%descripcion%</td>
    <td align="center">%solicitante%</td>
    <td align="center" width="80">%monto_moneda_total%</td>
    {% if Cobro.impuesto_gastos > 0 %}
        <td align="center" width="80">%monto_impuesto_total%</td>
        <td align="center" width="80">%monto_moneda_total_con_impuesto%</td>
    {% endif %}
</tr>
###GASTOS_FILAS###
<tr class="tr_datos">
    <td align="center">%fecha%</td>
    <td align="center">%ruc_proveedor%</td>
    <td align="center">%proveedor%</td>
    <td align="left">%descripcion%</td>
    <td align="center">%solicitante%</td>
    <td align="center">%monto_moneda_total%</td>
    {% if Cobro.impuesto_gastos > 0 %}
        <td align="center">%monto_impuesto_total%</td>
        <td align="center">%monto_moneda_total_con_impuesto%</td>
    {% endif %}
</tr>
###GASTOS_TOTAL###
<tr class="tr_total">
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td align="center">%valor_total_monedabase%</td>
    {% if Cobro.impuesto_gastos > 0 %}
        <td align="center">%valor_impuesto_monedabase%</td>
        <td align="center">%valor_total_monedabase_con_impuesto%</td>
    {% endif %}
</tr>

###CTA_CORRIENTE###
<hr size="2" class="separador">
<br>
<span class="titulo_seccion">%titulo_detalle_cuenta%</span>
<br>
<table class="tabla_normal" width="100%">
%CTA_CORRIENTE_SALDO_INICIAL%
%CTA_CORRIENTE_MOVIMIENTOS_ENCABEZADO%
%CTA_CORRIENTE_MOVIMIENTOS_FILAS%
%CTA_CORRIENTE_MOVIMIENTOS_TOTAL%
%CTA_CORRIENTE_SALDO_FINAL%
</table>

###CTA_CORRIENTE_SALDO_INICIAL###
<tr class="tr_total">
	<td align="right" colspan=3>%saldo_inicial_cuenta%</td>
	<td align="right">%valor_saldo_inicial_cuenta%</td>
</tr>


###CTA_CORRIENTE_MOVIMIENTOS_ENCABEZADO###
<tr>
	<td align="center" class="tr_titulo" colspan=4>&nbsp;</td>
</tr>
<tr>
	<td align="center" class="tr_titulo" colspan=4><hr noshade size="1" width="100%" align="center"></td>
</tr>
<tr class="tr_titulo">
	<td align="left" colspan=4>%movimientos%</td>
</tr>
<tr class="tr_titulo">
    <td align="left" width="70">%fecha%</td>
    <td align="left">%descripcion%</td>
    <td align="right" width="80">%egreso%</td>
    <td align="right" width="80">%ingreso%</td>
</tr>


###CTA_CORRIENTE_MOVIMIENTOS_FILAS###
<tr class="tr_datos">
	<td align="left">%fecha%</td>
	<td align="left">%descripcion%</td>
	<td align="right">%monto_egreso%</td>
	<td align="right">%monto_ingreso%</td>
</tr>


###CTA_CORRIENTE_MOVIMIENTOS_TOTAL###
<tr class="tr_total">
    <td>&nbsp;</td>
    <td align="right">%total%</td>
    <td align="right">%total_monto_egreso%</td>
    <td align="right">%total_monto_ingreso%</td>
</tr>
<tr>
	<td align="center" class="tr_titulo" colspan=4><hr noshade size="1" width="100%" align="center" style='height: 1px;'></td>
</tr>
<tr>
	<td align="center" class="tr_titulo" colspan=4>&nbsp;</td>
</tr>
<tr class=tr_total>
    <td align="right" colspan=3>%saldo_periodo%</td>
    <td align="right">%total_monto_gastos%</td>
</tr>


###CTA_CORRIENTE_SALDO_FINAL###
<tr class="tr_total">
	<td align="right" colspan=3>%saldo_final_cuenta%</td>
	<td align="right">%valor_saldo_final_cuenta%</td>
</tr>


###MOROSIDAD###
<br>
<span class="titulo_seccion">%titulo_morosidad%</span>
<br>
<table class="tabla_normal" width="100%" style='border:1px solid;'>
%MOROSIDAD_ENCABEZADO%
%MOROSIDAD_FILAS%
%MOROSIDAD_TOTAL%
</table>

###MOROSIDAD_ENCABEZADO###
<tr class="tr_titulo">
	<td align="center">%numero_nota_cobro%</td>
	<td align="center">%numero_factura%</td>
	<td align="center">%fecha%</td>
	<td align="center">%moneda%</td>
	<td align="center">%monto_moroso%</td>
</tr>

###MOROSIDAD_FILAS###
<tr class="tr_datos">
	<td align="center">%numero_nota_cobro%</td>
	<td align="center">%numero_factura%</td>
	<td align="center">%fecha%</td>
	<td align="center">%moneda_total%</td>
	<td align="right">%monto_moroso_documento%</td>
</tr>

###MOROSIDAD_HONORARIOS_TOTAL###
<tr class="tr_total">
	<td align="center">%numero_nota_cobro%</td>
	<td align="center">%numero_factura%</td>
	<td align="center">%fecha%</td>
	<td align="right">%moneda%</td>
	<td align="right">%monto_moroso_documento%</td>
</tr>

###MOROSIDAD_GASTOS###
<tr class="tr_total">
	<td align="center">%numero_nota_cobro%</td>
	<td align="center">%numero_factura%</td>
	<td align="center">%fecha%</td>
	<td align="right">%moneda%</td>
	<td align="right">%monto_moroso_documento%</td>
</tr>

###MOROSIDAD_TOTAL###
<tr class="tr_total">
	<td align="center">%numero_nota_cobro%</td>
	<td align="center">%numero_factura%</td>
	<td align="center">%fecha%</td>
	<td align="right">%moneda%</td>
	<td align="right">%monto_moroso_documento%</td>
</tr>
<tr class="tr_total">
	<td align="left" colspan=5>%nota%</td>
</tr>

###SALTO_PAGINA###
<br size="1" class="divisor">