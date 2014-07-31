<?php
require_once dirname(__FILE__) . '/../conf.php';

$sesion = new Sesion(array('PRO', 'REV', 'SEC'));
$pagina = new Pagina($sesion);
//ini_set('display_errors','On');
$t = new Trabajo($sesion);
$permiso_revisor = $sesion->usuario->Es('REV');
$permiso_cobranza = $sesion->usuario->Es('COB');
$permiso_profesional = $sesion->usuario->Es('PRO');
$permiso_secretaria = $sesion->usuario->Es('SEC');

$tipo_ingreso = Conf::GetConf($sesion, 'TipoIngresoHoras');
$actualizar_trabajo_tarifa = true;
$permiso_revisor_usuario = false;
$refresh_parent = false;

if ($id_trabajo > 0) {
    $actualizar_trabajo_tarifa = false;
    $t->Load($id_trabajo);

    // verificar si el usuario que inició sesión es revisor del usuario que se le está revisando las horas ingresadas
    $permiso_revisor_usuario = $sesion->usuario->Revisa($t->fields['id_usuario']);

    if (($t->Estado() == 'Cobrado' || $t->Estado() == __("Cobrado")) && $opcion != 'nuevo') {
        $pagina->AddError(__('Trabajo ya cobrado'));
        $pagina->PrintTop($popup);
        $pagina->PrintBottom($popup);
        exit;
    } else if (($t->Estado() == 'Revisado' || $t->Estado() == __("Revisado")) && $opcion != 'nuevo') {
        if (!$permiso_revisor && !$permiso_revisor_usuario) {
            $pagina->AddError(__('Trabajo ya revisado'));
            $pagina->PrintTop($popup);
            $pagina->PrintBottom($popup);
            exit;
        }
    } else if ($opcion == 'cambiofecha') {
        $semana = Utiles::fecha2sql($fecha);
        $t->Edit('fecha', $semana);
        if ($t->ValidarDiasIngresoTrabajo() && $t->Write(true)) {
            die('semana|' . $semana);
        } else {
            header('HTTP/1.0 401 Unauthorized');
            die($t->error);
        }
    } else if ($opcion == 'clonar') {
        $semana = Utiles::fecha2sql($fecha);
        unset($t->fields['id_trabajo']);
        unset($t->fields['id_cobro']);
        unset($t->fields['estadocobro']);
        unset($t->fields['fecha_creacion']);
        $t->Edit('fecha', $semana);
        foreach ($t->fields as $key => $value) {
            if ($value != '') {
                $t->changes[$key] = 1;
            }
        }

        if ($t->ValidarDiasIngresoTrabajo() && $t->Write(true)) {
            if (date('N', strtotime($t->fields['fecha'])) == 1) {
                $lastmonday = date('Y-m-d', strtotime($t->fields['fecha']));
            } else {
                $lastmonday = date('Y-m-d', strtotime($t->fields['fecha'] . " last Monday"));
            }

            if (Conf::GetConf($sesion, 'UsarHorasMesConsulta')) {
                $hhmes = $sesion->usuario->HorasTrabajadasEsteMes($t->fields['id_usuario'], 'horas_trabajadas', $lastmonday);
            } else {
                $hhmes = $sesion->usuario->HorasTrabajadasEsteMes($t->fields['id_usuario'], 'horas_trabajadas');
            }

            die('id_trabajo|' . $t->fields['id_trabajo'] . '|' . $sesion->usuario->HorasTrabajadasEsteSemana($t->fields['id_usuario'], $lastmonday) . '|' . $hhmes);
        } else {
            header('HTTP/1.0 401 Unauthorized');
            die($t->error);
        }
    }

    if (!$id_usuario) {
        $id_usuario = $t->fields['id_usuario'];
    }

    /*
     *    hemos cambiado el cliente por lo tanto
     *    este trabajo tomará un cobro CREADO del asunto, sino NULL
     */

    if (!$codigo_asunto_secundario) {
        //se carga el codigo secundario
        $asunto = new Asunto($sesion);
        $asunto->LoadByCodigo($t->fields['codigo_asunto']);
        $codigo_asunto_secundario = $asunto->fields['codigo_asunto_secundario'];
        $cliente = new Cliente($sesion);
        $cliente->LoadByCodigo($asunto->fields['codigo_cliente']);
        $codigo_cliente_secundario = $cliente->fields['codigo_cliente_secundario'];
        $codigo_cliente = $asunto->fields['codigo_cliente'];
    } else {
        $asunto = new Asunto($sesion);
        $asunto->LoadByCodigoSecundario($codigo_asunto_secundario);
        $codigo_asunto = $asunto->fields['codigo_asunto'];
    }

    /*
     *  revisar para codigo secundario
     */

    if ($codigo_asunto != $t->fields['codigo_asunto']) {
        $contrato_anterior = new Contrato($sesion);
        $contrato_modificado = new Contrato($sesion);

        $contrato_anterior->LoadByCodigoAsunto($t->fields['codigo_asunto']);
        $contrato_modificado->LoadByCodigoAsunto($codigo_asunto);

        if ($contrato_anterior->fields['id_tarifa'] != $contrato_modificado->fields['id_tarifa']) {
            $actualizar_trabajo_tarifa = true;
        }

        $cambio_asunto = true;
    }
} else {
    // Si no se está editando un trabajo
    if (!$id_usuario) {
        $id_usuario = $sesion->usuario->fields['id_usuario'];
    }
    $es_trabajo_nuevo = 1;
    if ($opcion != "guardar") {
        //para que por defecto aparezcan los trabajos como cobrables
        $t->fields['cobrable'] = 1;
        //para que por defecto aparezcan los trabajos como no visibles cuando sean no cobrables
        $t->fields['visible'] = 0;
    }
}

// OPCION -> Guardar else Eliminar
if ($opcion == "guardar") {
    if (Conf::GetConf($sesion, 'TipoIngresoHoras') == 'decimal') {
        if (round(10 * number_format(str_replace(',', '.', $duracion), 6, '.', '')) != 10 * number_format(str_replace(',', '.', $duracion), 6, '.', '')) {
            $pagina->AddError(__("Solo se permite ingresar un decimal en el campo ") . ' <b>' . __('Duración') . '</b>');
        }
        if (round(10 * number_format(str_replace(',', '.', $duracion_cobrada), 6, '.', '')) != 10 * number_format(str_replace(',', '.', $duracion_cobrada), 6, '.', '')) {
            $pagina->AddError(__("Solo se permite ingresar un decimal en el campo ") . ' <b>' . __('Duración Cobrable') . '</b>');
        }
    }
    if ($duracion == '00:00:00') {
        $pagina->AddError("Las horas ingresadas deben ser mayor a 0.");
    }
    if ((!$codigo_asunto || $codigo_asunto == '') && (!$codigo_asunto_secundario || $codigo_asunto_secundario == '')) {
        $pagina->AddError("Debe seleccionar un " . __('Asunto'));
    }
    if (Conf::GetConf($sesion, 'UsarAreaTrabajos') && (!$id_area_trabajo || $id_area_trabajo == '')) {
        $pagina->AddError("Debe seleccionar una area de trabajo");
    }
    if (!$descripcion || $descripcion == '') {
        $pagina->AddError("Debe Agregar una descripcion");
    }
    if ((!$codigo_cliente || $codigo_cliente == '') && (!$codigo_cliente_secundario || $codigo_cliente_secundario == '')) {
        $pagina->AddError("Debe seleccionar un cliente");
    }
    $errores = $pagina->GetErrors();

    if (empty($errores)) {
        if (Trabajo::CantHorasDia($duracion - $t->fields['duracion'], Utiles::fecha2sql($fecha), $id_usuario, $sesion)) {
            $valida = true;
            $asunto = new Asunto($sesion);

            if (Conf::GetConf($sesion, 'CodigoSecundario')) {
                $asunto->LoadByCodigoSecundario($codigo_asunto_secundario);
                $codigo_asunto = $asunto->fields['codigo_asunto'];
            } else {
                $asunto->LoadByCodigo($codigo_asunto);
            }

            /*
              Ha cambiado el asunto del trabajo se setea nuevo Id_cobo de alguno que esté creado
              y corresponda al nuevo asunto y esté entre las fechas que corresponda, sino, se setea NULL
             */
            if ($cambio_asunto) {
                $cobro = new Cobro($sesion);
                $id_cobro_cambio = $cobro->ObtieneCobroByCodigoAsunto($codigo_asunto, $t->fields['fecha']);

                if ($id_cobro_cambio) {
                    $t->Edit('id_cobro', $id_cobro_cambio);
                } else {
                    $t->Edit('id_cobro', 'NULL');
                }
            }

            $duracion_nueva = $tipo_ingreso == 'decimal' ? UtilesApp::Decimal2Time($duracion) : $duracion;
            $cambio_duracion = strtotime($duracion_nueva) != strtotime($t->fields['duracion']);
            $t->Edit("duracion", $duracion_nueva);

            if ($duracion_cobrada == '') {
                $duracion_cobrada = $duracion;
            }

            $t->Edit("duracion_cobrada", $tipo_ingreso == 'decimal' ? UtilesApp::Decimal2Time($duracion_cobrada) : $duracion_cobrada);

            $query = "SELECT id_categoria_usuario FROM usuario WHERE id_usuario = '$id_usuario' ";
            $resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
            list( $id_categoria_usuario ) = mysql_fetch_array($resp);

            $t->Edit('id_usuario', $id_usuario);

            if (is_numeric($id_usuario)) {
                $query = "UPDATE usuario SET retraso_max_notificado = 0 WHERE id_usuario = '$id_usuario'";
                mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
            }

            $t->Edit('id_categoria_usuario', !empty($id_categoria_usuario) ? $id_categoria_usuario : "NULL" );
            $t->Edit('codigo_asunto', $codigo_asunto);

            if (Conf::GetConf($sesion, 'UsarAreaTrabajos')) {
                //id_area_trabajo
                $t->Edit('id_area_trabajo', empty($id_area_trabajo) ? "NULL" : $id_area_trabajo );
            }

            $Ordenado_por = Conf::GetConf($sesion, 'OrdenadoPor');

            if ($Ordenado_por == 1 || $Ordenado_por == 2) {
                $t->Edit('solicitante', addslashes($solicitante));
            }

            if (Conf::GetConf($sesion, 'TodoMayuscula')) {
                $t->Edit('descripcion', strtoupper($descripcion));
            } else {
                $t->Edit('descripcion', $descripcion);
            }

            $cambio_fecha = strtotime($t->fields['fecha']) != strtotime(Utiles::fecha2sql($fecha));
            $t->Edit('fecha', Utiles::fecha2sql($fecha));
            // $t->Edit('fecha',$fecha);
            if (isset($codigo_actividad)) {
                $t->Edit('codigo_actividad', $codigo_actividad ? $codigo_actividad : 'NULL');
            }
            if (isset($codigo_tarea)) {
                $t->Edit('codigo_tarea', $codigo_tarea ? $codigo_tarea : 'NULL');
            }
            if ($revisado) {
                $t->Edit('revisado', 1);
            }

            if (!$cobrable) {
                $t->Edit("cobrable", '0');

                if (!$visible) {
                    $t->Edit("visible", '0');
                } else {
                    $t->Edit('visible', '1');
                }
            } else {
                $t->Edit('cobrable', '1');
                $t->Edit('visible', '1');
            }

            // Si el asunto no es cobrable
            if ($asunto->fields['cobrable'] == 0) {
                $t->Edit("cobrable", '0');
                /*
                 *  $t->Edit("visible",'0');
                 */
                $pagina->AddInfo(__('El Trabajo se guardó como NO COBRABLE (Por Maestro).'));
            }
            if (!$id_usuario) {
                $t->Edit("id_usuario", $sesion->usuario->fields['id_usuario']);
            } else {
                $t->Edit("id_usuario", $id_usuario);
            }

            // Agregar valores de tarifa
            $asunto = new Asunto($sesion);
            $asunto->LoadByCodigo($t->fields['codigo_asunto']);
            $contrato = new Contrato($sesion);
            $contrato->Load($asunto->fields['id_contrato']);
            if (!$t->fields['tarifa_hh']) {
                $t->Edit('tarifa_hh', Funciones::Tarifa($sesion, $id_usuario, $contrato->fields['id_moneda'], $codigo_asunto));
            }
            if (!$t->fields['costo_hh']) {
                $t->Edit('costo_hh', Funciones::TarifaDefecto($sesion, $id_usuario, $contrato->fields['id_moneda']));
            }


            /*
             *  Comentado a peticion de ICC por nueva definicion (originalmente aplicado a mano en release 13.2.15)
             *
             *   if ($t->fields['cobrable'] == 0) {
             *    $t->fields['duracion_cobrada']='00:00:00';
             *  }
             */

            $ingreso_valido = true;
            if ($cambio_duracion || $cambio_fecha) {
                $ingreso_valido = $t->ValidarDiasIngresoTrabajo();
            }
            if ($ingreso_valido && $t->Write(true)) {
                if ($actualizar_trabajo_tarifa) {
                    $t->InsertarTrabajoTarifa();
                }
                $pagina->AddInfo(__('Trabajo') . ' ' . ($nuevo ? __('guardado con éxito') : __('editado con éxito')));
                // refresca el listado de horas.php cuando se graba la informacion desde el popup
                $refresh_parent = true;
            } else {
                $pagina->AddError($t->error);
            }
        } else {
            $pagina->AddError("No se pueden ingresar mas de 23:59 horas por día.");
        }
    }

    unset($id_trab);
    // Significa que estoy agregando más que editando, así que debo dejar en limpio el formulario
    if ($es_trabajo_nuevo) {
        unset($t);
        unset($codigo_asunto_secundario);
        unset($codigo_cliente_secundario);
        $t = new Trabajo($sesion);
        // para que por defecto aparezcan los trabajos como cobrables
        $t->fields['cobrable'] = 1;
        // para que por defecto aparezcan los trabajos como no visibles cuando sean no cobrables
        $t->fields['visible'] = 0;
    }

    /*
      Nuevo en el caso de ser llamado desde Resumen semana, para que haga
      refresh al form
     */
    if ($nuevo || $edit) {
        $refresh_parent = true;
    }
} else if ($opcion == "eliminar") {
  // ELIMINAR TRABAJO
  $t = new Trabajo($sesion);
  $t->Load($id_trabajo);

  if (!$t->Eliminar()) {
    $pagina->AddError($t->error);
  } else {
    if ($orphan == '0') {
      $refresh_parent = true;
    }

    $pagina->AddInfo(__('Trabajo') . ' ' . __('eliminado con éxito'));

    $t = new Trabajo($sesion);
    $t->fields['cobrable'] = 1;
    $t->fields['visible'] = 0;

    $id_trabajo = null;
    $id_usuario = $sesion->usuario->fields['id_usuario'];
    $es_trabajo_nuevo = 1;
  }
} else if ($opcion == "actualizar_trabajo_tarifa") {
    // Actualizar tarifas en tabla trabajo_tarifa
    $valores = array();
    foreach ($_POST as $index => $valor) {
        list( $key1, $key2, $id_moneda ) = split('_', $index);
        if ($key1 == 'trabajo' && $key2 == 'tarifa' && $id_moneda > 0) {
            if (empty($valor)) {
                $valor = "0";
            }
            $t->ActualizarTrabajoTarifa($id_moneda, $valor);
            $valores[$id_moneda] = $valor;
        }
    }

    // Actualizar campo tarifa_hh de la tabla trabajo
    $asunto = new Asunto($sesion);
    $asunto->LoadByCodigo($t->fields['codigo_asunto']);
    $contrato = new Contrato($sesion);
    $contrato->Load($asunto->fields['id_contrato']);

    if ($valores[$contrato->fields['id_moneda']] > 0) {
        $t->Edit("tarifa_hh", $valores[$contrato->fields['id_moneda']]);
        $t->Write();
    }
    $refresh_parent = true;
    $pagina->AddInfo(__('Tarifas') . ' ' . __('guardado con éxito'));
}

// Título opcion
if ($opcion == '' && $id_trabajo > 0) {
    $txt_opcion = __('Modificación de Trabajo');
} else if ($id_trabajo == NULL) {
    // si no tenemos id de trabajo es porque se está agregando uno nuevo.
    $txt_opcion = __('Agregando nuevo Trabajo');
} else if ($opcion == '') {
    $txt_opcion = '';
}

$codigo_cliente = $t->get_codigo_cliente();
if (Conf::GetConf($sesion, 'CodigoSecundario')) {
    $cliente = new Cliente($sesion);
    $cliente->LoadByCodigo($codigo_cliente);
    $codigo_cliente_secundario = $cliente->fields['codigo_cliente_secundario'];
}
$pagina->titulo = __('Modificación de') . ' ' . __('Trabajo');
$pagina->PrintTop($popup);
$Form = new Form;

if ($refresh_parent) {
  echo $Form->Html->script_block('if (window.opener) {window.opener.Refrescar();}');
}

?>
<style type="text/css">
  a:link, a:visited { text-decoration:none; }
  a:hover { text-decoration:none; color:#990000; background-color:#D9F5D3; }
  a:active { text-decoration:none; color:#990000; background-color:#D9F5D3; }
</style>

<!-- Calendario DIV -->
<div id="calendar-container" style="width:221px; position:absolute; display:none;">
    <div class="floating" id="calendar"></div>
</div>
<!-- Fin calendario DIV -->
<form id="form_editar_trabajo" name="form_editar_trabajo" method="post" action="<?php echo $_SERVER[PHP_SELF] ?>">

    <input type="hidden" id="opcion" name="opcion" value="guardar" />
    <input type="hidden" name="gIsMouseDown" id="gIsMouseDown" value=false />
    <input type="hidden" name="gRepeatTimeInMS" id="gRepeatTimeInMS" value=200 />
    <input type="hidden" name="max_hora" id="max_hora" value=<?php echo Conf::GetConf($sesion, 'MaxDuracionTrabajo') ?> />
    <input type="hidden" name='codigo_asunto_hide' id='codigo_asunto_hide' value="<?php echo Conf::GetConf($sesion, 'CodigoSecundario') ? $asunto->fields['codigo_asunto_secundario'] : $t->fields['codigo_asunto']; ?>" />
    <?php if ($opcion != 'nuevo') { ?>
        <input type="hidden" name='id_trabajo' value="<?php echo $t->fields['id_trabajo'] ?>" id='id_trabajo' />
        <input type="hidden" name='edit' value="<?php echo $opcion == 'edit' ? 1 : '' ?>" id='edit' />
        <input type="hidden" name='fecha_trabajo_hide' value="<?php echo $t->fields['fecha'] ?>" id='fecha_trabajo_hide' />
    <?php } ?>
    <?php if ($id_trabajo == NULL) { ?>
        <input type="hidden" name='nuevo' value="1" id='nuevo' />
    <?php } ?>

    <input type="hidden" name=id_cobro id=id_cobro value="<?php echo $t->fields['id_cobro'] != 'NULL' ? $t->fields['id_cobro'] : '' ?>" />
    <input type="hidden" name=popup value='<?php echo $popup ?>' id="popup"/>

    <!-- TABLA HISTORIAL -->
    <?php if (Conf::GetConf($sesion, 'UsaDisenoNuevo')) {
        $display_none = 'style="display: none;"';
    } else {
        $display_none = '';
    } ?>

    <table id="tr_cliente" cellpadding="0" cellspacing="0"  width="100%" <?php echo $display_none ?>>
        <tr>
            <td colspan="7" class="td_transparente">&nbsp;</td>
        </tr>
        <tr>
            <td class="td_transparente">&nbsp;</td>
            <td class="td_transparente" colspan="5" align="right">
                <img style="filter:alpha(opacity=100);" src="<?php echo Conf::ImgDir() ?>/cruz_roja_13.gif" border="0" class="mano_on" alt="Ocultar" onClick="ShowDiv('tr_cliente', 'none', 'img_historial');">
            </td>
            <td class="td_transparente">&nbsp;</td>
        </tr>
        <tr>
            <td width="5%" class="td_transparente">&nbsp;</td>
            <td width="30%" id="leftcolumn" class="box_historial">
                <div id="titulos">
                <?php echo __('Cliente') ?>
                </div>
                <div id="left_data" class="span_data"></div>
            </td>
            <td class="td_transparente">
            </td>
            <td width="30%" id="content" class="box_historial">
                <div id="titulos">
                <?php echo __('Asunto') ?>
                </div>
                <div id="content_data" class="span_data"></div>
            </td>
            <td class="td_transparente">
            </td>
            <td width="30%" id="rightcolumn" class="box_historial">
                <div id="titulos">
                <?php echo __('Trabajo') ?>
                </div>
                <div id="right_data" class="span_data"></div>
            </td>
            <td width="5%" class="td_transparente">&nbsp;</td>
        </tr>
        <tr>
            <td colspan="7" class="td_transparente" style="height:190px">&nbsp;</td>
        </tr>
    </table>
    <!-- TABLA SOBRE ASUNTOS -->
    <table id="tr_asunto" cellpadding="0" cellspacing="0" width="100%" <?php echo $display_none ?>>
        <tr>
            <td colspan="6" class="td_transparente">&nbsp;</td>
        </tr>
        <tr>
            <td class="td_transparente">&nbsp;</td>
            <td align="right" colspan="4" class="td_transparente">
                <img src="<?php echo Conf::ImgDir() ?>/cruz_roja_13.gif" border="0" class="mano_on" alt="Ocultar" onClick="ShowDiv('tr_asunto', 'none', 'img_asunto');">
            </td>
            <td class="td_transparente">&nbsp;</td>
        </tr>
        <tr>
            <td width="5%" class="td_transparente">&nbsp;</td>
            <td width="45%" id="content" class="box_historial">
                <div id="titulos">
                <?php echo __('Asunto') ?>
                </div>
                <div id="content_data2" class="span_data"></div>
            </td>
            <td class="td_transparente">
            </td>
            <td width="45%" id="rightcolumn" class="box_historial">
                <div id="titulos">
                <?php echo __('Trabajo') ?>
                </div>
                <div id="right_data2" class="span_data"></div>
            </td>
            <td class="td_transparente">
            </td>
            <td width="5%" class="td_transparente">&nbsp;</td>
        </tr>
        <tr>
            <td colspan="6" class="td_transparente" style="height:190px">&nbsp;</td>
        </tr>
    </table>

    <table style="border:0px solid black" style="display:<?php echo $txt_opcion ? 'inline' : 'none'; ?>" width="100%">
        <tr>
            <td align="left">
                <span style="font-weight:bold; font-size:11px;"><?php echo $txt_opcion; ?></span>
            </td>
        </tr>
        <?php if ($id_trabajo > 0) { ?>
            <tr>
                <td width="40%" align="right">
          <?php echo $Form->icon_button(__('Ingresar nuevo Trabajo'), 'agregar', array('onclick' => "AgregarNuevo('trabajo')")); ?>
                </td>
            </tr>
        <?php } ?>
    </table>
    <br>

    <table class="border_plomo"   id="tbl_trabajo" style="width: 665px !important;">
        <tr>
            <td  width="20"  style="width:20px;">
                <span <?php echo Conf::GetConf($sesion, 'TipoSelectCliente') == 'autocompletador' ? 'style="display:none"' : '' ?> id="img_historial" onMouseover="ddrivetip('Historial de trabajos ingresados')" onMouseout="hideddrivetip()"><img src="<?php echo Conf::ImgDir() ?>/mas.gif" border="0" class="mano_on" id="img_historial" onClick="ShowDiv('tr_cliente', 'inline', 'img_historial');"></span>&nbsp;&nbsp;
            </td>
            <td  width="110" style="text-align:right;width:120px;" >
        <?php echo __('Cliente') ?>
            </td>
            <td align=left width="530" nowrap>
        <?php
        $codigo_asunto = $t->fields['codigo_asunto'];
        UtilesApp::CampoCliente($sesion, $codigo_cliente, $codigo_cliente_secundario, $codigo_asunto, $codigo_asunto_secundario, true, '320', "+CargarTarifa();");
        ?>
            </td>
        </tr>

        <tr>
          <td align='center'>
            <span <?php echo Conf::GetConf($sesion, 'TipoSelectCliente') == 'autocompletador' ? 'style="display:none"' : ''; ?> id="img_asunto"><img src="<?php echo Conf::ImgDir() ?>/mas.gif" border="0" id="img_asunto" class="mano_on" onMouseover="ddrivetip('Historial de trabajos ingresados')" onMouseout="hideddrivetip()" onClick="ShowDiv('tr_asunto', 'inline', 'img_asunto');"></span>&nbsp;&nbsp;
          </td>
          <td align='right'>
            <?php echo __('Asunto'); ?>
          </td>
          <td align=left width="440" nowrap>
            <?php
            $oncambio = '+CargarTarifa();';
            if (Conf::GetConf($sesion, 'UsoActividades') || Conf::GetConf($sesion, 'ExportacionLedes')) {
              $oncambio .= 'CargarActividad();';
            }
            UtilesApp::CampoAsunto($sesion, $codigo_cliente, $codigo_cliente_secundario, $codigo_asunto, $codigo_asunto_secundario, 300, $oncambio);
            ?>
          </td>
        </tr>

        <?php if (Conf::GetConf($sesion, 'UsarAreaTrabajos')) { ?>
            <tr>
                <td align='center'>
                    <span id="img_asunto">&nbsp;&nbsp;&nbsp;</span>
                </td>
                <td align='right'>
                    <?php echo __('Área Trabajo') ?>
                </td>
                <td align=left width="440" nowrap>
                <?php
                echo Html::SelectQuery($sesion, "SELECT * FROM prm_area_trabajo ORDER BY id_area_trabajo ASC", 'id_area_trabajo', $t->fields['id_area_trabajo'], '', 'Elegir', '400');
                ?>
                </td>
            </tr>
        <?php } ?>

        <?php if ((Conf::GetConf($sesion, 'UsoActividades') || Conf::GetConf($sesion, 'ExportacionLedes')) && ($permiso_revisor || $permiso_profesional)) { ?>
            <tr id="actividades">
                <?php if ($t->Loaded()) { ?>
                    <td colspan="2" align=right>
                        <?php echo __('Actividad'); ?>
                    </td>
                    <td align=left width="440" nowrap>
                        <?php echo InputId::ImprimirActividad($sesion, 'actividad', 'codigo_actividad', 'glosa_actividad', 'codigo_actividad', $t->fields['codigo_actividad'], '', '', 320, $t->fields['codigo_asunto']); ?>
                    </td>
                <?php } ?>
            </tr>
        <?php } else { ?>
      <tr style="display:none">
        <td>
          <input type="hidden" name="codigo_actividad" id="codigo_actividad"/>
          <input type="hidden" name="campo_codigo_actividad" id="campo_codigo_actividad"/>
        </td>
      </tr>
        <?php } ?>

        <!--
        - El siguiente segmento es utilizado para renderizar el campo 'codigo_tarea' / 'Código UTBMS'
        - SOLO CASO "EDITAR TRABAJO DESDE REVISAR HORAS"
        -->

        <?php if (Conf::GetConf($sesion, 'ExportacionLedes') && ($permiso_revisor || $permiso_profesional)) { ?>
            <tr id="codigo_ledes" >

                <!-- se muestra elemento si es que el trabajo es cargado  -->

                <?php if ($t->Loaded() ) {

                    $contrato_principal = UtilesApp::ObtenerContratoPrincipal($sesion, $t->fields['codigo_asunto']);
                    $contrato = new Contrato($sesion);
                    $contrato->LoadById($contrato_principal);
                    $activo_ledes = $contrato->fields['exportacion_ledes'];

                    if ($t->Loaded() && $activo_ledes == 1) {

                        echo '<td colspan="2" align="right">';
                        echo __('Código UTBMS');
                        echo '</td>';
                        echo '<td align="left" width="440" nowrap>';
                        echo InputId::ImprimirCodigo($sesion, 'UTBMS_TASK', 'codigo_tarea', $t->fields['codigo_tarea']);
                        echo '</td>';

                    }

                } ?>

            </tr>
        <?php } ?>

        <?php if ($fecha == '') {
            $zona_horaria = Conf::GetConf($sesion, 'ZonaHoraria');
            if ($zona_horaria) {
                date_default_timezone_set($zona_horaria);
            }
            $date = new DateTime();
            $fecha = date('d-m-Y', mktime(0, 0, 0, $date->format('m'), $date->format('d'), $date->format('Y')));
        } ?>

        <tr>
            <td colspan="2" align=right>
                <?php
                echo __('Fecha');
                if (!$permiso_cobranza && $sesion->usuario->fields['dias_ingreso_trabajo'] > 0) {
                    $fechamin = date('d-m-Y', mktime(0, 0, 0, date('m'), date('d') - $sesion->usuario->fields['dias_ingreso_trabajo'], date('Y')));
                }
                ?>
            </td>
            <td align=left valign="top">

                <input type="text" name="fecha" class="fechadiff" <?php echo $fechamin ? "minDate='$fechamin'" : ""; ?> value="<?php echo $t->fields['fecha'] ? Utiles::sql2date($t->fields['fecha']) : $fecha ?>" id="fecha" size="11" maxlength="10"/>

                <?php
                $Ordenado_por = Conf::GetConf($sesion, 'OrdenadoPor');
                if ($Ordenado_por == 1 || $Ordenado_por == 2) {
                    ?>
                    &nbsp;
                    <?php echo __('Ordenado por') ?>
                    &nbsp;
                    <input type="text" name="solicitante" value="<?php echo $t->fields['solicitante'] ? $t->fields['solicitante'] : '' ?>" id="solicitante" size="32" />
                    <?php
                }
                ?>
            </td>
        </tr>
        <tr>
            <td colspan="2" align=right>
                <?php echo __('Duración') ?>
            </td>
            <td align=left>
                <?php
                $duracion = '';
                $duracion_cobrada = '';
                ?>
                <table>
                    <tr>
                        <td>
                            <?php
                            $duracion_editable = $nuevo || $sesion->usuario->fields['id_usuario'] == $id_usuario;
                            if (!$duracion_editable) {
                                $usuario = new UsuarioExt($sesion);
                                $duracion_editable = $usuario->LoadSecretario($id_usuario, $sesion->usuario->fields['id_usuario']);
                            }

                            if ($tipo_ingreso == 'selector') {
                                if (!$duracion) {
                                    $duracion = '00:00:00';
                                }
                                echo SelectorHoras::PrintTimeSelector($sesion, "duracion", $t->fields['duracion'] ? $t->fields['duracion'] : $duracion, Conf::GetConf($sesion, 'MaxDuracionTrabajo'), '', $duracion_editable);
                            } else if ($tipo_ingreso == 'decimal') {
                                ?>
                                <input type="text" name="duracion" value="<?php echo $t->fields['duracion'] ? UtilesApp::Time2Decimal($t->fields['duracion']) : $duracion ?>" id="duracion" size="6" maxlength=4 <?php echo!$duracion_editable ? 'readonly' : '' ?> onchange="CambiaDuracion(this.form, 'duracion');"/>
                                <?php
                            } else if ($tipo_ingreso == 'java') {
                                echo Html::PrintTime("duracion", $t->fields[duracion], "onchange='CambiaDuracion(this.form ,\"duracion\");'", $duracion_editable);
                            } else {
                                echo Html::PrintTime("duracion", $t->fields[duracion], "onchange='CambiaDuracion(this.form ,\"duracion\");'", $duracion_editable);
                            }

                            echo '</td>';

                            if ($permiso_revisor) {
                                $where = " usuario_permiso.codigo_permiso='PRO' AND ( ";
                            } else {
                                $where = " usuario_permiso.codigo_permiso='PRO'
                                    AND ( usuario_secretario.id_secretario = '{$sesion->usuario->fields['id_usuario']}'
                  OR usuario.id_usuario IN ('$id_usuario','{$sesion->usuario->fields['id_usuario']}')
                  OR usuario.id_usuario IN (SELECT id_revisado FROM usuario_revisor WHERE id_revisor={$sesion->usuario->fields['id_usuario']}) ) AND ( ";
                            }
                            $where .= " usuario.visible=1 OR usuario.id_usuario = '$id_usuario' ) ";

                            $query = "
                                    SELECT SQL_CALC_FOUND_ROWS usuario.id_usuario,
                                    CONCAT_WS(' ', apellido1, apellido2,',',nombre)
                                    as nombre
                                FROM usuario
                                JOIN usuario_permiso USING(id_usuario)
                                LEFT JOIN usuario_secretario ON usuario.id_usuario = usuario_secretario.id_profesional
                                WHERE $where GROUP BY id_usuario ORDER BY nombre";

                            $resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
                            list($cantidad_usuarios) = mysql_fetch_array(mysql_query("SELECT FOUND_ROWS();", $sesion->dbh));
                            $select_usuario = Html::SelectResultado($sesion, $resp, "id_usuario", $id_usuario, 'onchange="CargarTarifa();"', '', 200);

                            if ($permiso_revisor || Conf::GetConf($sesion, 'AbogadoVeDuracionCobrable') || $permiso_revisor_usuario) {

                                echo '<td class="seccioncobrable">&nbsp;&nbsp;' . __('Duración Cobrable') . '</td><td  class="seccioncobrable">';

                                if ($tipo_ingreso == 'selector') {
                                    $duracion_cobrada = '00:00:00';
                                    echo SelectorHoras::PrintTimeSelector($sesion, "duracion_cobrada", $t->fields['duracion_cobrada'] ? $t->fields['duracion_cobrada'] : $duracion_cobrada, Conf::GetConf($sesion, 'MaxDuracionTrabajo'));
                                } else if ($tipo_ingreso == 'decimal') {
                                    ?>
                                    <input type="text" name="duracion_cobrada" value="<?php echo $t->fields['duracion_cobrada'] ? UtilesApp::Time2Decimal($t->fields['duracion_cobrada']) : $duracion_cobrada ?>" id="duracion_cobrada" size="6" maxlength=4 />
                                    <?php
                                } else if ($tipo_ingreso == 'java') {
                                    echo Html::PrintTime("duracion_cobrada", $t->fields['duracion_cobrada']);
                                } else {
                                    echo Html::PrintTime("duracion_cobrada", $t->fields['duracion_cobrada']);
                                }
                                ?>
                            </td>
            <?php } else { ?>
              <td>
                <input type="hidden" name="duracion_cobrada" id="duracion_cobrada" value="" />
                <input type="hidden" name="hora_duracion_cobrada" id="hora_duracion_cobrada" value="" />
                <input type="hidden" name="minuto_duracion_cobrada" id="minuto_duracion_cobrada" value="" />
              </td>
                        <?php } ?>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="2" align=right>
                <?php
                if (Conf::GetConf($sesion, 'IdiomaGrande')) {
                    ?>
                    <?php echo __('Descripción') ?><br/><span id=txt_span style="background-color: #C6FAAD; font-size:18px"></span>
                    <?php
                } else {
                    ?>
                    <?php echo __('Descripción') ?><br/><span id=txt_span style="background-color: #C6FAAD; font-size:9px"></span>
                    <?php
                }
                ?>
            </td>
            <td align=left>
                <textarea id="descripcion" cols=45 rows=4 name=descripcion><?php echo stripslashes($t->fields[descripcion]) ?></textarea>
      </td>
        </tr>
        <tr>
            <?php
            $mostrar_cobrable = true;
            if (!Conf::GetConf($sesion, 'PermitirCampoCobrableAProfesional') && $permiso_profesional && !$permiso_revisor && !Conf::GetConf($sesion, 'AbogadoVeDuracionCobrable')) {
                $mostrar_cobrable = false;
            }
            ?>
            <td colspan="2" align=right>
                <?php if ($mostrar_cobrable) { ?>
                    <?php echo __('Cobrable') ?><br/>
                <?php } ?>
            </td>
            <td align=left>
                <?php if ($mostrar_cobrable) { ?>
                    <input type="checkbox" style="display:inline;" name="cobrable" <?php echo ($t->fields['cobrable'] == 1 ? " checked='checked'  value='1'" : ""); ?> id="chkCobrable" onClick="CheckVisible();"/>
                <?php } else { ?>
                    <input type="hidden" name="cobrable" id="chkCobrable" value='1' />
                <?php } ?>
                &nbsp;&nbsp;
                <div id=divVisible style="display:inline">
                    <?php if ($permiso_revisor || Conf::GetConf($sesion, 'AbogadoVeDuracionCobrable')) { ?>
                        <?php echo __('Visible'); ?>
                        <input type="hidden" name="visible" value="0" />
                        <input  style="display:inline;" type="checkbox" name="visible" value="1" <?php echo ($t->fields['visible'] == 1) ? 'checked="checked"' : ''; ?> id="chkVisible" onMouseover="ddrivetip('Trabajo será visible en la <?php echo __('Nota de Cobro'); ?>')" onMouseout="hideddrivetip()"/>
                    <?php } else { ?>
                        <input type="hidden" name="visible" value="<?php echo $t->fields['visible'] ? $t->fields['visible'] : 1; ?>" id="hiddenVisible" />
                    <?php } ?>
                </div>
                &nbsp;&nbsp;&nbsp;&nbsp;
                <?php
                // Depende de que no cambie la función Html::SelectQuery(...)
                if ($cantidad_usuarios > 1 || $permiso_secretaria) {
                    echo __('Usuario');
                    echo $select_usuario;
                } else {
                    echo $Form->input('id_usuario', $sesion->usuario->fields['id_usuario'], array('id' => 'id_usuario', 'type' => 'hidden', 'label' => false));
                }
                ?>
            </td>
        </tr>

        <?php if (Conf::GetConf($sesion, 'GuardarTarifaAlIngresoDeHora') && $permiso_revisor) {
            if ($t->Loaded()) {
                if ($t->fields['id_cobro'] > 0) {
                    $cobro = new Cobro($sesion);
                    $cobro->Load($t->fields['id_cobro']);
                    $id_moneda_trabajo = $cobro->fields['id_moneda'];
                } else {
                    $contrato = new Contrato($sesion);
                    $contrato->LoadByCodigoAsunto($t->fields['codigo_asunto']);
                    $id_moneda_trabajo = $contrato->fields['id_moneda'];
                }
                $tarifa_trabajo = Moneda::GetSimboloMoneda($sesion, $id_moneda_trabajo);
                $tarifa_trabajo .= " " . $t->GetTrabajoTarifa($id_moneda_trabajo);
            }
            ?>
            <tr>
                <td colspan="2" align="right">
                    <?php echo __('Tarifa por hora') ?>
                </td>
                <td align="left">
                    <input type="text" size="10" id="tarifa_trabajo" disabled style="background-color: white; display: inline; border: 0px; color:black; vertical-align:middle;" value="<?php echo $tarifa_trabajo != '' ? $tarifa_trabajo : '' ?>" />
                    &nbsp;&nbsp;&nbsp;
                    <?php if ($t->fields['id_trabajo'] > 0) { ?>
                        <img src="<?php echo Conf::ImgDir() ?>/money_16.gif" border=0 /><a href='javascript:void(0)' onclick="MostrarTrabajoTarifas()"><?php echo __('Modificar tarifa del trabajo') ?></a>
                    <?php } ?>
                </td>
            </tr>
                <?php if ($t->fields['id_trabajo'] > 0) { ?>
                <tr>
                    <td>
                        <input type="hidden" id="id_moneda_trabajo" value="<?php echo $id_moneda_trabajo ?>" />
                        <div id="TarifaTrabajo" style="display:none; left: 50px; top: 250px; background-color: white; position:absolute; z-index: 4;">
                            <fieldset style="background-color:white;">
                                <legend><?php echo __('Tarifas por hora') ?></legend>
                                <div id="contenedor_tipo_load">&nbsp;</div>
                                <div id="contenedor_tipo_cambio">
                                    <table style='border-collapse:collapse;' cellpadding='3'>
                                        <tr>
                                            <?php
                                                $query = "SELECT
                          prm_moneda.id_moneda,
                          glosa_moneda,
                          ( SELECT valor FROM trabajo_tarifa WHERE id_trabajo = '" . $t->fields['id_trabajo'] . "'
                                                        AND trabajo_tarifa.id_moneda = prm_moneda.id_moneda )
                                                        FROM prm_moneda";
                                            $resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
                                            $num_monedas = 0;
                                            while (list($id_moneda, $glosa_moneda, $valor) = mysql_fetch_array($resp)) {
                                                ?>
                                            <td>
                                                <span><b><?php echo $glosa_moneda ?></b></span><br>
                                                <input type='text' size=9 id='trabajo_tarifa_<?php echo $id_moneda ?>' name='trabajo_tarifa_<?php echo $id_moneda ?>' onkeyup="MontoValido(this.id);" value='<?php echo $valor ?>' />
                                            </td>
                                            <?php
                                                $num_monedas++;
                                            }
                                            ?>
                                        </tr>
                                        <tr>
                                            <td colspan="<?php echo $num_monedas ?>" align="center">
                        <?php
                        echo $Form->button(__('Guardar'), array('onclick' => 'ActualizarTrabajosTarifas();'));
                        echo $Form->button(__('Cancelar'), array('onclick' => 'CancelarTrabajoTarifas();'));
                        ?>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </fieldset>

                        </div>
                    </td>
                </tr>
                <?php
            }
        }
        ?>
        <tr>
          <td colspan="3" align="right">
            <?php
            if (isset($t) && $t->Loaded() && $opcion != 'nuevo') {
              echo $Form->button(__('Eliminar este trabajo'), array('onclick' => "eliminarTrabajo('{$t->fields['id_trabajo']}', '{$popup}')", 'class' => 'btn_rojo', 'style' => 'margin-right: 2em;'));
            }
            $onclick = ($id_tabajo > 0) ? 'Confirmar' : 'Validar';
            echo $Form->button(__('Guardar'), array('onclick' => "$onclick(jQuery('#form_editar_trabajo')[0])"));
            ?>
          </td>
        </tr>
    </table>

</form>

<?php
echo $Form->script();

function SplitDuracion($time) {
    list($h, $m, $s) = split(":", $time);
    return $h . ":" . $m;
}

function Substring($string) {
    if (strlen($string) > 250) {
        return substr($string, 0, 250) . "...";
    } else {
        return $string;
    }
}
?>

<script type="text/javascript">
  <?php
  UtilesApp::GetConfJS($sesion, 'CodigoSecundario');
  UtilesApp::GetConfJS($sesion, 'OrdenadoPor');
  UtilesApp::GetConfJS($sesion, 'TodoMayuscula');
  UtilesApp::GetConfJS($sesion, 'UsarAreaTrabajos');
  UtilesApp::GetConfJS($sesion, 'LimpiarTrabajo');
  UtilesApp::GetConfJs($sesion, 'UsoActividades');
  UtilesApp::GetConfJS($sesion, "TipoSelectCliente");
  UtilesApp::GetConfJS($sesion, 'IdiomaGrande');
  UtilesApp::GetConfJS($sesion, 'PrellenarTrabajoConActividad');
  ?>

  function AutosizeFrame() {
    if (top.ResizeFrame !== undefined) {
      top.ResizeFrame();
    }
  }

  function CargarActividad() {
    var _codigo_asunto = 'codigo_asunto';
    if (CodigoSecundario) {
      _codigo_asunto = 'codigo_asunto_secundario';
    }
    CargarSelect(_codigo_asunto, 'codigo_actividad', 'cargar_actividades_activas');
  }

  function MostrarTrabajoTarifas() {
    jQuery('#TarifaTrabajo').show();
  }

  function CancelarTrabajoTarifas() {
    jQuery('#TarifaTrabajo').hide();
  }

  function ActualizarTrabajosTarifas() {
    jQuery('#opcion').val("actualizar_trabajo_tarifa");
    jQuery('#form_editar_trabajo').submit();
  }

  function Confirmar(form) {
    var r = confirm("Está modificando un trabajo, desea continuar?");
    if (r == true) {
      Validar(form);
    } else {
      return false;
    }
  }

  function Validar(form) {
    if (CodigoSecundario) {
      if (!form.codigo_asunto_secundario.value) {
        alert("<?php echo __('Debe seleccionar un') . ' ' . __('asunto') ?>");
        form.codigo_asunto_secundario.focus();
        return false;
      }
    } else {
      if (!form.codigo_asunto.value) {
        alert("<?php echo __('Debe seleccionar un') . ' ' . __('asunto') ?>");
        form.codigo_asunto.focus();
        return false;
      }
    }

    if (!form.fecha.value) {
      alert("<?php echo __('Debe ingresar una fecha.') ?>");
      form.fecha.focus();
      return false;
    }

    if (!form.duracion.value) {
      alert("<?php echo __('Debe establecer la duración') ?>");
      form.duracion.focus();
      return false;
    } else {
      if (form.duracion.value == '00:00:00') {
        alert("<?php echo __('La duración debe ser mayor a 0') ?>");
        <?php if ($tipo_ingreso == 'selector') {
          echo "document.getElementById('hora_duracion').focus();";
        } else {
          echo "form.duracion.focus();";
        } ?>

        return false;
      }
    }

    //Revisa el Conf si esta permitido y la función existe
    <?php if ($tipo_ingreso == 'decimal') { ?>
      var dur = form.duracion.value.replace(",", ".");
      var dur_cob = form.duracion_cobrada.value.replace(",", ".");

      if (isNaN(dur) || isNaN(dur_cob)) {
        alert("<?php echo __('Solo se aceptan valores numéricos') ?>");
        form.duracion.focus();
        return false;
      }

      var decimales = dur.split('.');
      var decimales_cobrada = dur_cob.split('.');
      if ((decimales.length > 1 && decimales[1].length > 1) || (decimales_cobrada.length > 1 && decimales_cobrada[1].length > 1)) {
        alert("<?php echo __('Solo se permite ingresar un decimal') ?>");
        form.duracion.focus();
        return false;
      }
    <?php } ?>

    if (!form.descripcion.value) {
      alert("<?php echo __('Debe ingresar la descripción') ?>");
      form.descripcion.focus();
      return false;
    }

    if (UsarAreaTrabajos) {
      if (!form.id_area_trabajo.value) {
        alert("<?php echo __('Debe seleccionar una area de trabajo') ?>");
        form.id_area_trabajo.focus();
        return false;
      }
    }

    //Valida si el asunto ha cambiado para este trabajo que es parte de un cobro, si ha cambiado se emite un mensaje indicandole lo ki pa
    if (form.id_cobro.value != '' && $('id_trabajo').value != '') {
      if (CodigoSecundario) {
        if (!ActualizaCobro(form.codigo_asunto_secundario.value)) {
          //MENSAJE DE ERROR
          return false;
        }
      } else {
        if (!ActualizaCobro(form.codigo_asunto.value)) {
          //MENSAJE DE ERROR
          return false;
        }
      }
    }

    if (OrdenadoPor == 1) {
      if (form.solicitante.value == '') {
        alert("<?php echo __('Debe ingresar la persona que solicitó el trabajo') ?>");
        form.solicitante.focus();
        return false;
      }
    }

    //Se pasa todo a mayúscula por conf
    if (TodoMayuscula) {
      form.descripcion.value = form.descripcion.value.toUpperCase();
      if (OrdenadoPor != 0) {
        form.solicitante.value = form.solicitante.value.toUpperCase();
      }
    }

    // Si el usuario no tiene permiso de cobranza validamos la fecha del trabajo
    <?php if (!$permiso_cobranza && $sesion->usuario->fields['dias_ingreso_trabajo'] > 0) { ?>
      temp = $('fecha').value.split("-");
      fecha = new Date(temp[2] + '//' + temp[1] + '//' + temp[0]);
      hoy = new Date();
      fecha_tope = new Date(hoy.getTime() - (<?php echo ($sesion->usuario->fields['dias_ingreso_trabajo'] + 1) ?> * 24 * 60 * 60 * 1000));

      if (fecha_tope > fecha) {
        var dia = fecha_tope.getDate();
        var mes = fecha_tope.getMonth() + 1;
        var anio = fecha_tope.getFullYear();
        alert("No se pueden ingresar trabajos anteriores a " + dia + "-" + mes + "-" + anio);
        $('fecha').focus;
        return false;
      }
    <?php } ?>

    //Si esta editando desde la página de ingreso de trabajo le pide confirmación para realizar los cambios
    <?php if (isset($t) && $t->Loaded() && $opcion != 'nuevo') { ?>
      var string = new String(top.location);
      //revisa que esté en la página de ingreso de trabajo
      if (string.search('/trabajo.php') > 0) {
        if (!confirm('Está modificando un trabajo, desea continuar?')) {
          return false;
        }
      }
    <?php } ?>

    top.window.jQuery('#semanactual').val(jQuery('#fecha').val());
    form.submit();
    return false;
  }

  function MontoValido(id_campo) {
    var monto = document.getElementById(id_campo).value.replace('\,', '.');
    var arr_monto = monto.split('\.');
    var monto = arr_monto[0];

    for ($i = 1; $i < arr_monto.length - 1; $i++) {
      monto += arr_monto[$i];
    }

    if (arr_monto.length > 1) {
      monto += '.' + arr_monto[arr_monto.length - 1];
    }

    document.getElementById(id_campo).value = monto;
  }

  function CargarTarifa() {
    var id_usuario = jQuery('#id_usuario').val();

    if (CodigoSecundario) {
      var codigo_asunto = jQuery('#codigo_asunto_secundario').val();
      var codigo_cliente = jQuery('#codigo_cliente_secundario').val();
    } else {
      var codigo_asunto = jQuery('#codigo_asunto').val();
      var codigo_cliente = jQuery('#codigo_cliente').val();
    }

    var vurl = 'ajax.php?accion=cargar_tarifa_trabajo&id_usuario=' + id_usuario + '&codigo_asunto=' + codigo_asunto + '&codigo_cliente=' + codigo_cliente;

    jQuery.get(vurl, function(response) {
      if (jQuery('#tarifa_trabajo').length > 0) {
        jQuery('#tarifa_trabajo').val(response);
      }
    });

    return true;
  }

  function IngresarNuevo(form) {
    form.opcion.value = 'nuevo';
    form.id_trabajo.value = '';
    var url = "semana.php?popup=1&semana=" + form.semana.value + "&id_usuario=" +<?php echo $id_usuario ?> + "&opcion=nuevo";
    self.location.href = url;
  }

  function CambiaDuracion(form, input) {
    if (document.getElementById('duracion_cobrada') && input == 'duracion') {
      form.duracion_cobrada.value = form.duracion.value;
    }
  }

  /*Clear los elementos*/
  function DivClear(div, dvimg) {
    var left_data = document.getElementById('left_data');
    var content_data = document.getElementById('content_data');
    var right_data = document.getElementById('right_data');
    left_data.innerHTML = '';
    content_data.innerHTML = '';
    right_data.innerHTML = '';

    var content = document.getElementById('content_data2');
    var right = document.getElementById('right_data2');
    content.innerHTML = '';
    right.innerHTML = '';

    if (div == 'tr_cliente') {
      var img = document.getElementById('img_asunto');
      img.innerHTML = '<img src="<?php echo Conf::ImgDir() ?>/mas.gif" border="0" title="Mostrar" class="mano_on" onClick="ShowDiv(\'tr_asunto\',\'inline\',\'img_asunto\');">';
    } else {
      var img = document.getElementById('img_historial');
      img.innerHTML = '<img src="<?php echo Conf::ImgDir() ?>/mas.gif" border="0" title="Mostrar" class="mano_on" onClick="ShowDiv(\'tr_cliente\',\'inline\',\'img_historial\');">';
    }
  }

  function ShowDiv(div, valor, dvimg) {
    var div_id = document.getElementById(div);
    var img = document.getElementById(dvimg);
    var form = document.getElementById('form_editar_trabajo');

    if (TipoSelectCliente == "autocompletador") {
      var codigo = document.getElementById('codigo_cliente');
    } else {
      var codigo = document.getElementById('campo_codigo_cliente');
    }

    var tr = document.getElementById('tr_cliente');
    var tr2 = document.getElementById('tr_asunto');
    var al = document.getElementById('al');

    DivClear(div, dvimg);

    codigo = (codigo == null) ? "" : codigo.value;

    if (div == 'tr_asunto' && codigo == '') {
      tr.style['display'] = 'none';
      alert("<?php echo __('Debe seleccionar un cliente') ?>");
      form.codigo_cliente.focus();
      return false;
    }

    div_id.style['display'] = valor;

    if (div == 'tr_cliente') {
      WCH.Discard('tr_asunto');
      tr2.style['display'] = 'none';
      Lista('lista_clientes', 'left_data', '', '');
    } else if (div == 'tr_asunto') {
      WCH.Discard('tr_cliente');
      tr.style['display'] = 'none';
      Lista('lista_asuntos', 'content_data2', codigo, '2');
    }

    /*Cambia IMG*/
    if (valor == 'inline') {
      WCH.Apply('tr_asunto');
      WCH.Apply('tr_cliente');
      img.innerHTML = '<img src="<?php echo Conf::ImgDir() ?>/menos.gif" border="0" title="Ocultar" class="mano_on" onClick="ShowDiv(\'' + div + '\',\'none\',\'' + dvimg + '\');">';
    } else {
      WCH.Discard(div);
      img.innerHTML = '<img src="<?php echo Conf::ImgDir() ?>/mas.gif" border="0" onMouseover="ddrivetip(\'Historial de trabajos ingresados\')" onMouseout="hideddrivetip()" class="mano_on" onClick="ShowDiv(\'' + div + '\',\'inline\',\'' + dvimg + '\');">';
    }
  }

  /*
   AJAX Lista de datos historial
   accion -> llama ajax
   div -> que hace update
   codigo -> codigo del parámetro necesario SQL
   div_post -> id div posterior onclick
   */
  function Lista(accion, div, codigo, div_post) {
    var form = document.getElementById('form_editar_trabajo');
    var data = document.getElementById(div);
    hideddrivetip();

    if (accion == 'lista_asuntos') {
      if (TipoSelectCliente == "autocompletador") {
        SetSelectInputId('codigo_cliente', 'glosa_cliente');
      } else {
        form.campo_codigo_cliente.value = codigo;
        SetSelectInputId('campo_codigo_cliente', 'codigo_cliente');
      }

      if (CodigoSecundario) {
        CargarSelect('codigo_cliente_secundario', 'codigo_asunto_secundario', 'cargar_asuntos');
      } else {
        CargarSelect('codigo_cliente', 'codigo_asunto', 'cargar_asuntos');
      }
    } else if (accion == 'lista_trabajos') {
      form.campo_codigo_asunto.value = codigo;
      SetSelectInputId('campo_codigo_asunto', 'codigo_asunto');

      if (UsoActividades) {
        CargarSelect('codigo_asunto', 'codigo_actividad', 'cargar_actividades_activas');
      }
    }

    var http = getXMLHTTP();

    if (div == 'content_data') {
      var right_data = document.getElementById('right_data');
      right_data.innerHTML = '';
    }

    var vurl = 'ajax_historial.php?accion=' + accion + '&codigo=' + codigo + '&div_post=' + div_post + '&div=' + div;
    http.open('get', vurl, false);
    http.onreadystatechange = function() {
      if (http.readyState == 4) {
        var response = http.responseText;
        data.innerHTML = response;
      }
    };

    http.send(null);
  }

  function UpdateTrabajo(id_trabajo, descripcion, codigo_actividad, duracion, duracion_cobrada, cobrable, visible, fecha) {
    var form = document.getElementById('form_editar_trabajo');
    form.campo_codigo_actividad.value = codigo_actividad;
    SetSelectInputId('campo_codigo_actividad', 'codigo_actividad');

    form.duracion.value = duracion;

    if (document.getElementById('duracion_cobrada')) {
      form.duracion_cobrada.value = duracion_cobrada;
    }

    form.cobrable.checked = cobrable > 0 ? true : false;
    form.visible.checked = visible > 0 ? true : false;
    form.descripcion.value = descripcion;
    form.fecha.value = fecha;

    var tr = document.getElementById('tr_cliente');
    var tr2 = document.getElementById('tr_asunto');
    var img = document.getElementById('img_historial');
    var img2 = document.getElementById('img_asunto');

    WCH.Discard('tr_asunto');
    WCH.Discard('tr_cliente');
    tr.style['display'] = 'none';
    tr2.style['display'] = 'none';

    img.innerHTML = '<img src="<?php echo Conf::ImgDir() ?>/mas.gif" border="0" onMouseover="ddrivetip(\'Historial de trabajos ingresados\')" onMouseout="hideddrivetip()" class="mano_on" onClick="ShowDiv(\'tr_cliente\',\'inline\',\'img_historial\');">';

    img2.innerHTML = '<img src="<?php echo Conf::ImgDir() ?>/mas.gif" border="0" onMouseover="ddrivetip(\'Historial de trabajos ingresados\')" onMouseout="hideddrivetip()" class="mano_on" onClick="ShowDiv(\'tr_asunto\',\'inline\',\'img_asunto\');">';
  }

  function CargaIdioma(codigo) {
    return;
  }

  function ActualizaCobro(valor) {
    var codigo_asunto_hide = $('codigo_asunto_hide').value;
    var id_cobro = $('id_cobro').value;
    var id_trabajo = $('id_trabajo').value;
    var fecha_trabajo_hide = $('fecha_trabajo_hide').value;
    var form = $('form_editar_trabajo');

    if (codigo_asunto_hide != valor && id_cobro && id_trabajo) {
      var text_window = "<img src='<?php echo Conf::ImgDir() ?>/alerta_16.gif'>&nbsp;&nbsp;<span style='font-size:12px; color:#FF0000; text-align:center;font-weight:bold'><u><?php echo __("ALERTA") ?></u><br><br>";
      text_window += '<span style="text-align:center; font-size:11px; color:#000; "><?php echo __('Ud. está modificando un trabajo que pertenece al cobro') ?>:' + id_cobro + ' ';
      text_window += '<?php echo __('. Si acepta, el trabajo se desvinculará de ') . __('este cobro') . __(' y eventualmente se vinculará a ') . __('un cobro') . __(' pendiente para el nuevo ' . __('asunto') . 'en caso de que exista') ?>.</span><br>';
      text_window += '<br><table><tr>';
      text_window += '</table>';

      Dialog.confirm(text_window, {
        top: 100,
        left: 80,
        width: 400,
        okLabel: "<?php echo __('Aceptar') ?>",
        cancelLabel: "<?php echo __('Cancelar') ?>",
        buttonClass: "btn",
        className: "alphacube",
        id: "myDialogId",
        cancel: function(win) {
          return false;
        },
        ok: function(win) {
          if (ActualizarCobroAsunto(valor)) {
            form.submit();
          }
          return true;
        }
      });
    } else {
      return true;
    }
  }

  function ActualizarCobroAsunto(valor) {
    var codigo_asunto_hide = $('codigo_asunto_hide').value;
    var id_cobro = $('id_cobro').value;
    var id_trabajo = $('id_trabajo').value;
    var fecha_trabajo_hide = $('fecha_trabajo_hide').value;
    var http = getXMLHTTP();
    var urlget = 'ajax.php?accion=set_cobro_trabajo&codigo_asunto=' + valor + '&id_trabajo=' + id_trabajo + '&fecha=' + fecha_trabajo_hide + '&id_cobro_actual=' + id_cobro;
    http.open('get', urlget, true);
    http.onreadystatechange = function() {
      if (http.readyState == 4) {
        var response = http.responseText;
      }
    };
    http.send(null);
    return true;
  }

  //Cuando se le saca el check de cobrable se hace visible = 0
  function CheckVisible() {
    if (!$('chkCobrable').checked) {
      <?php if ($permiso_revisor || Conf::GetConf($sesion, 'AbogadoVeDuracionCobrable')) { ?>
        $('chkVisible').checked = false;
      <?php } else { ?>
        $('hiddenVisible').value = 0;
      <?php } ?>
    }
  }

  function AgregarNuevo(tipo) {
    if (CodigoSecundario) {
      var codigo_cliente_secundario = $('codigo_cliente_secundario').value;
      var codigo_asunto_secundario = $('codigo_asunto_secundario').value;
    } else {
      var codigo_cliente = $('codigo_cliente').value;
      var codigo_asunto = $('codigo_asunto').value;
    }

    if (tipo == 'trabajo') {
      var urlo = "editar_trabajo.php?popup=1";
      window.location = urlo;
    }
  }

  jQuery('document').ready(function() {
    var tipo_ingreso_hrs = new String("<?php echo Conf::GetConf($sesion, 'TipoIngresoHoras'); ?>");

    if (tipo_ingreso_hrs == 'decimal') {
      jQuery('#duracion').change(function() {
        var str = jQuery(this).val();
        jQuery(this).val(str.replace(',', '.'));
        jQuery(this).parseNumber({format:"0.0", locale:"us"});
        jQuery(this).formatNumber({format:"0.0", locale:"us"});
      });

      jQuery('#duracion_cobrada').focus(function() {
         var str = jQuery(this).val();
        jQuery(this).val(str.replace(',', '.'));
        jQuery(this).parseNumber({format:"0.0", locale:"us"});
        jQuery(this).formatNumber({format:"0.0", locale:"us"});
      });

      jQuery('#descripcion').focus(function() {
        var str = jQuery('#duracion_cobrada').val();
        jQuery('#duracion_cobrada').val(str.replace(',', '.'));
        jQuery('#duracion_cobrada').parseNumber({format:"0.0", locale:"us"});
        jQuery('#duracion_cobrada').formatNumber({format:"0.0", locale:"us"});
      });
    }

      var loadLedesAsunto = function() {
        var campo_asuntos = jQuery('#codigo_asunto');
        if (CodigoSecundario) {
          campo_asuntos = jQuery('#codigo_asunto_secundario');
        }

        jQuery.ajax({
          type: 'POST',
          url: 'ajax/ajax_ledes_trabajos.php',
          async: false,
          data: {
            opcion: 'ledes',
            codigo_cliente: campo_asuntos.val().split('-').first(),
            conf_activa: <?php echo Conf::GetConf($sesion, 'ExportacionLedes'); ?>,
            permiso_revisor: <?php echo $permiso_revisor ? 'true' : 'false'; ?>,
            permiso_profesional: <?php echo $permiso_profesional ? 'true' : 'false'; ?>
          }
        }).done(function(response) {
          jQuery('#codigo_ledes').html(response);
          AutosizeFrame();
        });

        jQuery.ajax({
          type: 'POST',
          url: 'ajax/ajax_ledes_trabajos.php',
          async: false,
          data: {
            opcion: 'act',
            ledes: <?php echo Conf::GetConf($sesion, 'ExportacionLedes'); ?>,
            actividades: <?php echo Conf::GetConf($sesion, 'UsoActividades'); ?>,
            codigo_cliente: campo_asuntos.val().split('-').first(),
            <?php
              if ($t->fields['codigo_asunto']) {
                echo 'codigo_asunto: \''. $t->fields['codigo_asunto'].'\'';
              } else {
                echo 'codigo_asunto: jQuery(\'#campo_codigo_asunto\').val()';
              }
            ?>
          }
        }).done(function(response) {
          jQuery('#actividades').html(response);
          if (response) {
            if (PrellenarTrabajoConActividad) {
              $('codigo_actividad').observe('change', function(evento) {
                actividad_seleccionada = this.options[this.selectedIndex];
                if (actividad_seleccionada.value != '') {
                  descripcion_textarea = document.getElementById('descripcion');
                  descripcion_textarea.value = actividad_seleccionada.text + '\n' + descripcion_textarea.value;
                }
              });
            }
          };
          AutosizeFrame();
        });
      }

      var loadLedesCliente = function() {
        jQuery.ajax({
          type: 'POST',
          url: 'ajax/ajax_ledes_trabajos.php',
          async: false,
          data: {
            opcion: 'ledes',
            codigo_cliente: jQuery('#campo_codigo_cliente').val(),
            conf_activa: <?php echo Conf::GetConf($sesion, 'ExportacionLedes'); ?>,
            permiso_revisor: <?php echo $permiso_revisor ? 'true' : 'false'; ?>,
            permiso_profesional: <?php echo $permiso_profesional ? 'true' : 'false'; ?>
          }
        }).done(function(response) {
          jQuery('#codigo_ledes').html(response);
          AutosizeFrame();;
        });
      }

      jQuery('#codigo_cliente, #codigo_cliente_secundario').change(loadLedesCliente);
      jQuery('#codigo_asunto, #codigo_asunto_secundario').change(loadLedesAsunto);
      jQuery('#campo_codigo_cliente').bind('input',loadLedesCliente);
      jQuery('#campo_codigo_asunto').bind('input',loadLedesAsunto);

      jQuery('#codigo_asunto, #codigo_asunto_secundario').change(function() {
        var codigo = jQuery(this).val();

        if (!codigo) {
          jQuery('#txt_span').html('');
          return false;
        } else {
          jQuery.ajax({
            type: "GET",
            url: "ajax.php",
            contentType: "application/x-www-form-urlencoded;charset=ISO-8859-1",
            data: {accion: 'idioma', codigo_asunto: codigo},
            beforeSend: function(xhr) {
              xhr.overrideMimeType("text/html; charset=ISO-8859-1");
            }
          }).done(function(response) {
            var idio = response.split("|");
            if (idio[1].length == 0) {
              idio[1] = 'Español';
            }

            if (idio[0].length == 0) {
              idio[0] = 'es';
            }

            if (IdiomaGrande) {
              jQuery('#txt_span').html(idio[1]);
            } else {
              jQuery('#txt_span').html('Idioma: ' + idio[1]);
            }

            if (idio[0] == 'es') {
              googie2.setCurrentLanguage('es');
            } else if (idio[0] == 'en') {
              googie2.setCurrentLanguage('en');
            }
          });
        }
      });

      top.window.jQuery('#versemana').click();
      top.window.jQuery('.resizableframe').load();

      jQuery('#chkCobrable').click(function() {
        if (jQuery(this).is(':checked')) {
          jQuery('#duracion_cobrada, #hora_duracion_cobrada, #minuto_duracion_cobrada').removeAttr('disabled');
          jQuery('#divVisible').hide();
          jQuery('.seccioncobrable').show();
        } else {
          jQuery('#divVisible').show();
        }
      });

      if (jQuery('#chkCobrable').is(':checked')) {
        jQuery('#duracion_cobrada, #hora_duracion_cobrada, #minuto_duracion_cobrada').removeAttr('disabled');
        jQuery('#divVisible').hide();
        jQuery('.seccioncobrable').show();
      } else {
        jQuery('#divVisible').show();
      }

      var googie2 = new GoogieSpell("../../fw/js/googiespell/", "sendReq.php?lang=");

      googie2.setLanguages({'es': 'Español', 'en': 'English'});
      googie2.dontUseCloseButtons();
      googie2.setSpellContainer("spell_container");
      googie2.decorateTextarea("descripcion");
  });

  var formObj = $('form_editar_trabajo');

  if (CodigoSecundario) {
    CargaIdioma('<?php echo $codigo_asunto_secundario; ?>');
  } else {
    CargaIdioma('<?php echo $t->fields['codigo_asunto']; ?>');
  }

  <?php if (empty($id_trabajo) && (Conf::GetConf($sesion, 'LimpiarTrabajo') )) { ?>
    $$('#codigo_asunto_hide, #id_cobro, #campo_codigo_cliente, #codigo_cliente, #campo_codigo_cliente_secundario, #codigo_cliente_secundario, #campo_codigo_asunto_secundario, #codigo_asunto_secundario, #codigo_actividad, #campo_codigo_actividad, #descripcion, #solicitante').each(function(elem) {
        elem.value = '';
    });

    if (TipoSelectCliente == 'autocompletador') {
      $$('#glosa_cliente').each(function(elem) {
        elem.value = '';
      });
    }
  <?php } ?>

  if (PrellenarTrabajoConActividad) {
    jQuery('#codigo_actividad').change(function() {
      var actividad_seleccionada = this.options[this.selectedIndex];
      if (actividad_seleccionada.value != '') {
        jQuery('#descripcion').val(actividad_seleccionada.text + '\n' + jQuery('#descripcion').val());
      }
    });
  }

  function eliminarTrabajo(id_trabajo, popup) {
    var orphan = parentExists() ? '0' : '1';

    if (confirm("<?php echo __('¿Desea eliminar este trabajo?'); ?>")) {
      window.location = "editar_trabajo.php?opcion=eliminar&id_trabajo=" + id_trabajo + "&popup=" + popup + "&orphan=" + orphan;
    }
  }

  function parentExists() {
    return (window.opener != null && !window.opener.closed);
  }
</script>

<?php
echo SelectorHoras::Javascript();
$pagina->PrintBottom($popup);
