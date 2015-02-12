<?php
require_once dirname(__FILE__) . '/../conf.php';

$Sesion = new Sesion('');
$Grupo = new GrupoCliente($Sesion);
$SelectHelper = new FormSelectHelper();
$Form = new Form;

if (!empty($id)) {
  $Grupo->load($id);
}
?>

<form action="" method="post" id="formulario-grupo">
  <input type="hidden" name="id" value="<?php echo $Grupo->fields['id_grupo_cliente']; ?>">
  <input type="hidden" name="accion" value="guardar" />
  <input type="hidden" name="tabla" value="grupo_cliente" />
  <fieldset id="formularioinicial" class="tb_base" style="border: 1px solid #BDBDBD;">
    <legend><?php echo __('Agregar Grupo')?></legend>
    <table width="90%" cellspacing="3" cellpadding="3" >
      <tr  class="controls controls-row " >
        <td class="ar"  width="200">
          <div class="span2">
            <?php echo __('Glosa'); ?>
          </div >
        </td>
        <td class="al " width="600">
          <div  class="controls controls-row " style="white-space:nowrap;">
            <? echo $Form->input('data[glosa_grupo_cliente]', $Grupo->fields['glosa_grupo_cliente'], array('label' => false, 'id' => 'glosa_grupo_cliente')); ?> 
          </div>
        </td>
      </tr>
      <tr  class="controls controls-row " >
        <td class="ar"  width="200">
          <div class="span2">
            <?php echo __('Codigo'); ?>
          </div >
        </td>
        <td class="al " width="600">
          <div  class="controls controls-row " style="white-space:nowrap;">
            <? echo $Form->input('data[codigo_cliente]', $Grupo->fields['codigo_cliente'], array('label' => false, 'id' => 'codigo_cliente')); ?> 
          </div>
        </td>
      </tr>
      <tr  class="controls controls-row " >
        <td class="ar"  width="200">
          <div class="span2">
            <?php echo __('País') . ' ' . __('procedencia'); ?>
          </div >
        </td>
        <td class="al " width="600">
          <div  class="controls controls-row " style="white-space:nowrap;">
            <? 
              echo $SelectHelper->ajax_select(
                'data[id_pais]',
                $Grupo->fields['id_pais'] ? $Grupo->fields['id_pais'] : '', 
                array('id' => 'id_pais_grupo', 'class' => 'span3', 'style' => 'display:inline'), 
                array(
                  'source' => 'ajax/ajax_prm.php?prm=Pais&fields=nombre,iso_2siglas',
                  'selectedName' => 'selected_pais_grupo')
              );
            ?> 
          </div>
        </td>
      </tr>
    <tr>
      <td align="center" colspan="2">
        <br/>
        <input type="button" id="guardar_grupo" value="<?php echo __('Guardar') ?>">
        <input type="button" id="cancelar_grupo" value="<?php echo __('Cancelar') ?>">
        <?php if ($Grupo->Loaded()) { ?>
          <input type="button" id="eliminar_grupo" value="<?php echo __('Eliminar') ?>">
        <?php } ?>
      </td>
    </tr>
  </table>
</form>