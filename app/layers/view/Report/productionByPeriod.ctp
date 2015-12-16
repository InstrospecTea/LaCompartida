
<form name="form_busca" id="form_busca" action="" method="post">
  <input type=hidden name="opc" id="opc" value="Html">
  <div id="calendar-container" style="width:221px; position:absolute; display:none;">
    <div class="floating" id="calendar"></div>
  </div>
  <fieldset class="tb_base" style="width: 90%; border: 1px solid #BDBDBD;">
    <legend><?php echo __('Filtros') ?></legend>
    <table width="720px" style="border:0px dotted #999999">
      <tr>
        <th align="right" width="30%"><?php echo __('Cliente') ?></th>
        <td align="left" colspan="2">
          <?php
          UtilesApp::CampoCliente($this->Session, $this->data['codigo_cliente'], $this->data['codigo_cliente_secundario'], $this->data['codigo_asunto'], $this->data['codigo_asunto_secundario']);
          if (Configure::read('CodigoSecundario')) {
            echo $this->Form->hidden('codigo_cliente');
          }
          ?>
        </td>
      </tr>
      <tr>
        <th align="right"><?php echo __('Asunto') ?></th>
        <td align="left" colspan="2">
          <?php UtilesApp::CampoAsunto($this->Session, $this->data['codigo_cliente'], $this->data['codigo_cliente_secundario'], $this->data['codigo_asunto'], $this->data['codigo_asunto_secundario']); ?>
        </td>
      </tr>
			<tr>
        <th align="right"><?php echo __('Área') ?></th>
				<td align="left" colspan="2">
          <?php echo $this->Form->select('areas', $areas, $this->data['areas'], array('empty' => __('Todos'))) ?>
				</td>
			</tr>
			<tr>
        <th align="right"><?php echo __('Categoría de asunto'); ?></td>
				<td align="left" colspan="2">
          <?php echo $this->Form->select('id_tipo_asunto', $tipo_asunto, $this->data['id_tipo_asunto'], array('empty' => __('Todos'))) ?>
				</td>
			</tr>
      <tr>
        <th align="right"><?php echo __('Abogado') ?></th>
        <td colspan="2" align="left"><!-- Nuevo Select -->
          <?php echo $this->Form->select('id_usuario', $this->Session->usuario->ListarActivos('', 'PRO'), $this->data['id_usuario'], array('empty' => __('Todos'))); ?>
        </td>
      </tr>
      <tr>
        <th align="right"><?php echo __('Cobrable') ?></th>
        <td colspan="2" align="left">
          <?php echo $this->Form->select('cobrable', $cobrable_estados, $this->data['cobrable'], array('empty' => __('Todos'))) ?>
        </td>
      </tr>
      <tr>
        <th align="right"><?php echo __('Fecha desde') ?></th>
        <td colspan="2" align="left">
          <?php
            $time = strtotime("-1 year", time());
            $date = date("01-m-Y", $time);
            echo $this->Form->input('fecha_ini', empty($this->data['fecha_ini']) ? $date : $this->data['fecha_ini'], array('label' => '', 'class' => 'fechadiff', 'size' => '11', 'maxlength' => '10')) ?>
        </td>
      </tr>
      <tr>
        <th align="right"><?php echo __('Fecha hasta') ?></th>
        <td colspan="2" align="left">
          <?php
            echo $this->Form->input('fecha_fin', empty($this->data['fecha_fin']) ? date('d-m-Y') : $this->data['fecha_fin'], array('label' => '', 'class' => 'fechadiff', 'size' => '11', 'maxlength' => '10')) ?>
        </td>
      </tr>
      <tr>
        <th align="right"><?php echo __('Mostrar valores en') ?></th>
        <td colspan="2" align="left">
          <?php echo $this->Form->select('mostrar_valores', $mostrar_estados, $this->data['mostrar_valores'], array('empty' => false)) ?>
        </td>
      </tr>
      <tr>
        <th align=right >
          <?php echo __('Visualizar en Moneda') ?>:
        </td>
        <td align=left>
          <?php
          echo $this->Form->select('moneda_filtro', $monedas, $this->data['moneda_filtro'] ? $this->data['moneda_filtro'] : $moneda_base, array('empty' => false)) ?>
        </td>
      </tr>
      <tr>
        <td>
        </td>
        <td  align="right" colspan="2">
          <?php
          echo $this->Form->submit(__('Buscar'), array('onclick' => "jQuery('#opc').val('Html')"));
          echo $this->Form->submit(__('Descargar Excel'), array('onclick' => "jQuery('#opc').val('Spreadsheet')"));
          ?>
        </td>
      </tr>
    </table>
  </fieldset>
</form>
<br/>
<?php
  try {
    if (isset($report)) {
      $report->render();
    }
  } catch (ReportEngineException $ex) {

  }

