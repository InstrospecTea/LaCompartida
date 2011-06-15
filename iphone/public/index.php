<?php require_once(dirname(__FILE__).'/../../app/conf.php'); ?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html lang='en' xml:lang='en' xmlns='http://www.w3.org/1999/xhtml' manifest="../sample.manifest">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>
		T&amp;B
	</title>
	<style media='screen' type='text/css'>
	@import "jqtouch/jqtouch/jqtouch.css";
	</style>
	<style media='screen' type='text/css'>
	@import "jqtouch/themes/jqt/theme.css";
	</style>
	<style media='screen' type='text/css'>
	@import "jqtouch/mobile.css";
	</style>
	<style media="screen">
	@import "jqtouch/sw/spinningwheel.css";
	</style>
	
	<script type="text/javascript" src="jqtouch/sw/spinningwheel.js"></script>
	<script type="text/javascript" src="jqtouch/javascripts/json2.js"></script>
	<script type="text/javascript" src="jqtouch/javascripts/iscroll.js"></script>
	<script charset='utf-8' src='jqtouch/jqtouch/jquery-1.4.2.min.js' type='text/javascript'></script>
	<script charset='utf-8' src='jqtouch/jqtouch/jqtouch.js' type='application/x-javascript'></script>
	<script charset='utf-8' src='jqtouch/mobile.js' type='text/javascript'></script>
	
</head>
<body>
	<!-- Listado de trabajos // Home -->
	<div id='home' class="current">
		<div class='toolbar'>
			<h1>T&amp;B</h1>
			<a href="#config" class="button slideup">Config</a>
		</div>
		<ul class="rounded" id="job_list">
		</ul>
		<!-- <ul class="rounded">
			<li class="arrow">	<a class="action" href="#new_job" >Iniciar trabajo</a></li>
		</ul> -->
		<ul class="rounded">
			<li class="arrow">	<a class="action" href="#old_job" >Enviar trabajo</a></li>
		</ul>
	</div>



	<!-- Configuración // Ingreso de usuario y contraseña -->
	<div id='config'>
		<form id="login" action="#" accept-charset="utf-8">
			<div class="toolbar">
				<a class="back">Atrás</a>
				<h1>Configuración</h1>
			</div>
			<ul class="rounded">
				<li><input name="rut" value="" type="text" placeholder="RUT" /></li>
				<li><input name="password" value="" type="password" placeholder="Contraseña" /></li>
				<li><a class="action submit">Verificar datos</a></li>
			</ul>
		</form>
	</div>



	<!-- Nuevo trabajo  -->
	<div id="new_job">
		<form id="new_job_form" action="#">
			<div class="toolbar">
				<a class="back">Atrás</a>
				<h1>Nuevo trabajo</h1>
			</div>
			<ul class="rounded">
				<li>
					<select class="client_list" name="cliente">
						<option value="">Cliente</option>
					</select>
				</li>
				<li>
					<select class="subject_list" name="asunto">
						<option value="">Asunto</option>
					</select>
				</li>
				<li><a class="action submit">Iniciar</a></li>
			</ul>
		</form>
	</div>		


	<!-- Editar trabajo  -->
	<div id="edit_job">
		<form id="edit_job_form" action="#">
			<div class="toolbar">
				<a class="back">Atrás</a>
				<h1>Editar trabajo</h1>
			</div>
			<ul class="rounded">
				<li>
					<select class="client_list" name="cliente">
						<option value="">Cliente</option>
					</select>
				</li>
				<li>
					<select class="subject_list" name="asunto">
						<option value="">Asunto</option>
					</select>
				</li>
				<li><input name="description" value="" type="text" placeholder="Descripción" /></li>
				<li><input class="sw-calendar" name="fecha" value="" type="text" placeholder="Fecha" /></li>
				<li><input class="sw-duration" name="duration" value="" type="text" placeholder="Duración" /></li>
				<li><a class="action submit">Finalizar</a></li>
			</ul>
		</form>
	</div>		

	<!-- Editar trabajo  -->
	<div id="old_job">
		<form id="old_job_form" action="#">
			<div class="toolbar">
				<a class="back">Atrás</a>
				<h1>Editar trabajo</h1>
			</div>
			<ul class="rounded">
				<li>
					<select class="client_list" name="cliente">
						<option value="">Cliente</option>
					</select>
				</li>
				<li>
					<select class="subject_list" name="asunto">
						<option value="">Asunto</option>
					</select>
				</li>
				<li><input name="description" value="" type="text" placeholder="Descripción" /></li>
				<li><input class="sw-calendar" name="fecha" value="" type="text" placeholder="Fecha" /></li>
				<li><input class="sw-duration" name="duration" value="" type="text" placeholder="Duración" /></li>
				<li><a class="action submit">Finalizar</a></li>
			</ul>
		</form>
	</div>		

</body>
</html>
