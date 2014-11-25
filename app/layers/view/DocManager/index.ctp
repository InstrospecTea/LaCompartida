<form role="form" id="form_doc" method="post">

    <div class="container-fluid">
        <div class="row" style="padding: 15px 0;">
            <div class="col-sm-4"><h4>Mantenedor de Cartas</h4></div>
            <div class="col-sm-4"><?php echo $this->Form->select('carta[id_carta]', $cartas, $id_carta, array('class' => 'form-control', 'id' => 'carta_id_carta')); ?></div>
            <div class="col-sm-3" id="nrel_charges"></div>
            <div class="col-sm-1 text-right">
				<button type="button" id="nueva_carta" name="nueva_carta" class="btn btn-primary" data-toggle="modal" data-target="#nuevo_formato"><span class="glyphicon glyphicon-plus"></span>&nbsp; Crear Carta</button>
			</div>
        </div>
    </div>

    <!-- Panel HTML-CSS del mantenedor -->
    <div class="container-fluid" style="height: calc(100vh - 70px);" id="editor_preview">
        <div class="row" style="height: calc(100vh - 70px);">

            <div class="col-md-6"  style="height:calc(100vh - 70px);">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <ul id="tabs" class="nav nav-tabs" role="tablist" data-tabs="tabs" style="margin-bottom:-1.3%;">
                            <li class="active"><a href="#html_code" data-toggle="tab">HTML</a></li>
                            <li><a href="#css_code" data-toggle="tab">CSS</a></li>
                        </ul>
                    </div>

                    <div class="panel-body" style="height:calc(100vh - 70px - 60px - 57px);">
                        <div id="content" style="height: calc(100vh - 70px - 60px - 30px - 57px);">
                            <div id="my-tab-content" class="tab-content">
                                <div class="tab-pane active" id="html_code">

                                    <div class="row">

                                        <div class="col-md-5">
                                            <?php echo $this->Form->select('secciones', $secciones, '', array('class' => 'form-control')); ?>
                                        </div>
                                        <div class="col-md-5">
                                            <select id="tag_selector" class="form-control"></select>
                                        </div>
                                        <div class="col-md-2">
                                            <button id="insertar_elemento" type="button" class="btn btn-primary pull-right">Insertar</button>
                                        </div>
                                    </div>

                                    <textarea class="ckeditor" id="carta_formato" name="carta[formato]" style="width:100%; height: calc(100vh - 70px - 60px - 30px - 45px - 57px); margin-top: 10px;"></textarea>
                                </div>
                                <div class="tab-pane" id="css_code">
                                    <textarea id="carta_formato_css" name="carta[formato_css]" style="width:100%; height: calc(100vh - 70px - 60px - 30px - 57px); min-height: calc(100vh - 70px - 60px - 30px - 57px"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="panel-footer">
                        <div class="row">
                            <div class="col-md-2">
                                <button type="button" class="btn btn-default" data-toggle="modal" data-target="#margenes">Margenes</button>
                            </div>
                            <div class="col-md-10">
                                <button type="button" class="btn btn-success pull-right" id="guardar_formato" style="margin-right: 1%;">Guardar</button>
                                <button type="button" class="btn btn-danger pull-right" id="eliminar_formato" style="margin-right: 1%;">Eliminar</button>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Panel Previsualizacion HTML -->

            <div class="col-md-6" style="height:calc(100vh - 70px);">
                <div class="panel panel-default">

                    <div class="panel-heading">
                        <div class="row">
                            <div class="col-md-4">
                                <h5>Previsualizando liquidación N°</h5>
                            </div>
                            <div class="col-md-3">
                                <input id="id_cobro" name="id_cobro" type="text" class="form-control" maxlength="10" value="<?php echo $id_cobro ?>">
                            </div>
                            <div class="col-md-3">
                                <h6 id="errmsg" style="color:red; font-weight: bold;"></h6>
                            </div>
                            <div class="col-md-2">
                                <button id="btn_previsualizar" name="btn_previsualizar" class="btn btn-default pull-right" type="button"><span class="glyphicon glyphicon-print"></span>&nbsp; Descargar Word</button>
                            </div>
                        </div>
                    </div>

                    <div class="panel-body" style="overflow-y:auto; height: calc(100vh - 70px - 60px);">
                        <div id="letter_preview" class="col-md-12" style="height:100%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para definir margenes -->

    <div class="modal fade" id="margenes" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header" align="center">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h3 class="modal-title" id="myModalLabel">Configuración de Margenes</h3>
                </div>

                <div class="row">
                    <div class="col-md-3"></div>
                    <div class="col-md-2"><h5>Superior :</h5></div>
                    <div class="col-md-3">
						<div class="input-group">
							<input type="text" class="form-control" name="carta[margen_superior]" id="carta_margen_superior" value=""/>
							<span class="input-group-addon">cm</span>
						  </div>
					</div>
                </div>

                <div class="row">
                    <div class="col-md-3"></div>
                    <div class="col-md-2"><h5>Inferior :</h5></div>
                    <div class="col-md-3">
						<div class="input-group">
							<input type="text" class="form-control" name="carta[margen_inferior]" id="carta_margen_inferior" value=""/>
							<span class="input-group-addon">cm</span>
						  </div>
					</div>
                </div>

                <div class="row">
                    <div class="col-md-3"></div>
                    <div class="col-md-2"><h5>Izquierdo :</h5></div>
                    <div class="col-md-3">
						<div class="input-group">
							<input type="text" class="form-control" name="carta[margen_derecho]" id="carta_margen_derecho" value=""/>
							<span class="input-group-addon">cm</span>
						  </div>
					</div>
                </div>

                <div class="row">
                    <div class="col-md-3"></div>
                    <div class="col-md-2"><h5>Derecho :</h5></div>
                    <div class="col-md-3">
						<div class="input-group">
							<input type="text" class="form-control" name="carta[margen_izquierdo]" id="carta_margen_izquierdo" value=""/>
							<span class="input-group-addon">cm</span>
						  </div>
					</div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Cancelar</button>
                    <button class="btn btn-success" data-dismiss="modal">Confirmar</button>
                </div>
            </div>
        </div>
    </div>

</form>

<div class="modal fade" id="nuevo_formato" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">

			<div class="modal-header" align="center">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
				<h3 class="modal-title" id="myModalLabel">Agregar Nueva Carta</h3>
			</div>

			<div class="modal-body">
				<div class="row">
					<div class="col-md-5"><h5 class="pull-right">Descripción :</h5></div>
					<div class="col-md-6">
						<input type="text" class="form-control" id="carta_descripcion"/>
					</div>
				</div>
				<div class="row">
					<div class="col-md-5"><h5 class="pull-right">Utilizar formato :</h5></div>
					<div class="col-md-6"><?php echo $this->Form->select('id_new_formato', $cartas, $id_carta, array('class' => 'form-control')); ?></div>
				</div>
			</div>

			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
				<button type="button" class="btn btn-success" data-dismiss="modal" id="guardar_nuevo">Confirmar</button>
			</div>
		</div>
	</div>
</div>

<?php
	echo $this->Html->script(Conf::RootDir() . '/app/layers/assets/js/doc_manager.js');
