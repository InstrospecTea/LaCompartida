<table >
  <tr>
    <th align="right"><?php echo __('Agrupado') ?> por:</th>
    <td colspan="2" align="left">
      <?php
      echo $this->Form->select('agrupado_por', $gropued_by, $gropued_by_defaul, array('empty' => false));
      ?>
    </td>
    <td style="display: none" colspan="2">
      <label><input type="checkbox" value="1" id="por_socio" name="por_socio"  /> Agrupar por socio</label>
    </td>
  </tr>

  <tr>
    <th align="right"><?php echo __('Mostrar Valores') ?> en:</th>
    <td colspan="4" align="left">
      <?php
      echo $this->Form->select('mostrar_valores', $mostrar_valores, 0, array('empty' => false));
      ?>
    </td>
  </tr>

  <tr>
    <th>Mostrar valor facturado</th>
    <td colspan="4">
      <label><input type="checkbox" value="1" id="valor_facturado" name="valor_facturado" /></label>
    </td>
  </tr>

  <tr>
    <th align="right"><?php echo __('Visualizar en Moneda') ?>:</th>
    <td colspan="4" align="left">
      <?php
          echo $this->Form->select('moneda_filtro', $monedas, $this->data['moneda_filtro'] ? $this->data['moneda_filtro'] : $moneda_base, array('empty' => false, 'disabled' => 'disabled'))
      ?>
    </td>
  </tr>

</table>
