// localStorage.clear();exit();
duration_ready = false;
meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

var jQT = new $.jQTouch({
	icon:'apple-touch-icon.png',
	//addGlossToIcon: false,
	startupScreen:"apple-touch-startup.png",
	statusBar:'black-translucent',
	formSelector: '.form',
	preloadImages:[
	'jqtouch/themes/jqt/img/back_button.png',
	'jqtouch/themes/jqt/img/back_button_clicked.png',
	'jqtouch/themes/jqt/img/button_clicked.png',
	'jqtouch/themes/jqt/img/loading.gif'
	]
});

jQuery(function() {
  
	// Cargar datos en pantalla de configuración
	$('form#login input[name=rut]').val(localStorage.rut);
	$('form#login input[name=password]').val(localStorage.password);
	
	app.mostrar_clientes('form#new_job_form .client_list');
	app.mostrar_clientes('form#edit_job_form .client_list');
	app.mostrar_clientes('form#old_job_form .client_list');
	app.update_job_list();
	app.setup_sw_calendars();
	if(localStorage.intervalo && !duration_ready) {
		app.setup_sw_durations(parseInt(localStorage.intervalo));
		duration_ready = true;
	}

	$('form#login').submit(function(e){
		return app.login($(this.rut).val(), $(this.password).val());
	});

	$('form#new_job_form .client_list').change(function(e){
		return app.mostrar_asuntos('form#new_job_form .subject_list','form#new_job_form .client_list');
	});

	$('form#old_job_form .client_list').change(function(e){
		return app.mostrar_asuntos('form#old_job_form .subject_list','form#old_job_form .client_list');
	});

	$('form#edit_job_form .client_list').change(function(e){
		return app.mostrar_asuntos('form#edit_job_form .subject_list','form#edit_job_form .client_list');
	});

	$('form#new_job_form').submit(function(e){
		return app.new_job($(this));
	});

	$('form#edit_job_form').submit(function(e){
		return app.send_job($(this));
	});

	$('form#old_job_form').submit(function(e){
		return app.send_job($(this));
	});

	$('#old_job').bind('pageAnimationStart', function(event, info){
		if(info.direction == "in") {
			app.old_job();
		}
	});

	$('ul#job_list li.job').bind('tap', function(e){
		return app.edit_job($(this));
	});	

	if(!localStorage.rut) {
	 	$('#config a.back').hide();
		jQT.goTo('#config');
	}


});

var app = {
	login: function(rut, password) {
		localStorage.rut = rut;
		localStorage.password = password;
		$.ajax({
			type:"post",
			url:"../login",
			data: {"rut": rut, "password": password},
			complete:function(req) {
				if(req.status == 200 || req.status == 0) {
					alert("El usuario y la contraseña son correctos");
					jQT.goBack();
					app.cargar_clientes();
					app.cargar_asuntos();
					app.cargar_intervalo();
					$('#config a.back').show();
				} else {
					alert("El usuario o la contraseña es incorrecto");
				}
			}
		});
		return false;
	},
	cargar_clientes: function(){
		var rut = localStorage.rut;
		var password = localStorage.password;
		$.ajax({
			type:"post",
			url:"../clientes",
			data: {"rut": rut, "password": password},
			complete:function(req) {
				if(req.status == 200 || req.status == 0) {
					localStorage.clientes = req.responseText;
					app.mostrar_clientes('form#new_job_form .client_list');
					app.mostrar_clientes('form#edit_job_form .client_list');
					app.mostrar_clientes('form#old_job_form .client_list');
				} else {
					alert("Los clientes no fueron cargados");					
				}			
			}
		});
		return false;
	},
	cargar_intervalo: function(){
		$.ajax({
			type:"get",
			url:"../intervalo",
			complete:function(req) {
				if(req.status == 200 || req.status == 0) {
					localStorage.intervalo = req.responseText;
					if(localStorage.intervalo && !duration_ready) {
						app.setup_sw_durations(parseInt(localStorage.intervalo));
						duration_ready = true;
					}
				} else {
					alert("El intervalo no fue cargado");					
				}			
			}
		});
		return false;
	},
	cargar_asuntos: function($form){
		var rut = localStorage.rut;
		var password = localStorage.password;
		$.ajax({
			type:"post",
			url:"../asuntos",
			data: {"rut": rut, "password": password},
			complete:function(req) {
				if(req.status == 200 || req.status == 0) {
					localStorage.asuntos = req.responseText;
				} else {
					alert("Los asuntos no fueron cargados");					
				}			
			}
		});
		return false;
	},
	mostrar_clientes: function(client_list_selector){
		$select = $(client_list_selector);
		if(localStorage.clientes) {
			$select.empty();
			$select.append("<option value=\"\">Cliente</option>");
			$.each($.parseJSON(localStorage.clientes), function(i, cl){
				$select.append("<option value="+cl.codigo+">"+cl.glosa+"</option>");
			});
		}
	},
	mostrar_asuntos: function(subject_list_selector, client_list_selector){
		$select = $(subject_list_selector);
		$select.empty();
		var client_code = $(client_list_selector).val();
		var asuntos = $.parseJSON(localStorage.asuntos);
		asuntos = $.grep(asuntos,function(el,i){return(el.codigo_padre==client_code)});
		$select.append("<option value=\"\">Asunto</option>");
		$.each(asuntos, function(i,as){
			$select.append("<option value="+as.codigo+">"+as.glosa+"</option>");
		});
	},
	new_job: function($form){
		var client = $form.find(":input.client_list :selected");
		var subject = $form.find(":input.subject_list :selected");
		var client_code = client.val();
		var client_name = client.text();
		var subject_code = subject.val();
		var subject_name = subject.text();
		var current_jobs = $.parseJSON(localStorage.jobs);
		if(current_jobs == null)
			current_jobs = []
		current_jobs.push({client:{code:client_code, name:client_name}, subject:{code:subject_code, name:subject_name}, start_time: Date()});
		localStorage.jobs = JSON.stringify(current_jobs);
		this.update_job_list();
		jQT.goBack();
		return false;
	},
	send_job: function($form){
		var rut = localStorage.rut;
		var password = localStorage.password;
		var subject_code = $form.find(":input.subject_list :selected").val();
		var description = $form.find("[name=description]").val();
		var date_input = $form.find("[name=fecha]");
		var date = date_input.attr("year")+"-"+date_input.attr("month")+"-"+date_input.attr("day");
		var duration_input = $form.find("[name=duration]");
		var duration = parseInt(duration_input.attr("hours"))*60+parseInt(duration_input.attr("minutes"));
		$.ajax({
			type:"post",
			url:"../trabajos",
			data: {"rut": rut, "password": password, "codigo_asunto": subject_code, "descripcion": description, "fecha": date, "duracion": duration},
			complete:function(req, msg) {
				if(req.status == 200 || req.status == 0) {
					var job_id = $form.attr("job_id");
					if(job_id) {
						var current_jobs = $.parseJSON(localStorage.jobs);
						current_jobs.splice(job_id,1);
						localStorage.jobs = JSON.stringify(current_jobs);
						app.update_job_list();
					}	
					alert("El trabajo fue enviado.");					
				} else {
					alert("El trabajo no pudo ser enviado.");					
				}			
			}
		});
		jQT.goBack();
		this.update_job_list();
		return false;
	},
	update_job_list: function(){
		$list = $('ul#job_list');
		var asuntos = $.parseJSON(localStorage.asuntos);
		var clientes = $.parseJSON(localStorage.clientes);
		if(localStorage.jobs && localStorage.jobs != '[]') {
			$list.empty();
			$list.show();
			$.each($.parseJSON(localStorage.jobs), function(i, job){
				var $job = $("<li class=\"arrow job\"><a href=\"#edit_job\"><span class='client'>"+job.client.name+"</span><span class='subject'>"+job.subject.name+"</subject></a></li>");
				$job.attr("client_name", job.client.name);
				$job.attr("client_code", job.client.code);
				$job.attr("subject_name", job.subject.name);
				$job.attr("subject_code", job.subject.code);
				$job.attr("job_id", i);
				$list.append($job);
			});
		} else {
			$list.empty();
			$list.hide();
		}
	},
	setup_sw_calendars: function(){
		$('input[type=text].sw-calendar')
			.attr('readonly', 'readonly')
			.bind('click',function(){
				var $input = $(this);
				SpinningWheel.addSlot({1:1, 2:2, 3:3, 4:4, 5:5, 6:6, 7:7, 8:8, 9:9, 10:10, 11:11, 12:12, 13:13, 14:14, 15:15, 16:16, 17:17, 18:18, 19:19, 20:20, 21:21, 22:22, 23:23, 24:24, 25:25, 26:26, 27:27, 28:28, 29:29, 30:30, 31:31}, 'right', $input.attr('day'));
				SpinningWheel.addSlot({ 1: 'enero', 2: 'febrero', 3: 'marzo', 4: 'abril', 5: 'mayo', 6: 'junio', 7: 'julio', 8: 'agosto', 9: 'septiembre', 10: 'octubre', 11: 'noviembre', 12: 'diciembre' }, 'left', $input.attr('month'));
				// TODO: hacerlo dinámico; año actual y anterior
				SpinningWheel.addSlot(years={2009:2009, 2010:2010}, '', $input.attr('year'));
				SpinningWheel.setDoneAction(function(){
					var result = SpinningWheel.getSelectedValues(); 
					$input.val(result.values.join(" de "));
					$input.attr('day', result.keys[0] )
					$input.attr('month', result.keys[1] )
					$input.attr('year', result.keys[2] )
				});
				SpinningWheel.open();
			});
	},
	setup_sw_durations: function(interval){
		$('input[type=text].sw-duration')
			.attr('readonly', 'readonly')
			.bind('click',function(){
				var $input = $(this);

				var hours_array = [];
				for(i=0;i<24;i++) {
					hours_array.push(i);
				}
				SpinningWheel.addSlot(hours_array, 'right shrink', $input.attr('hours'));

				var minutes_array = {};
				for(i=0;i<60;i+=interval){
					minutes_array[i] = (i<10?"0":"")+i;
				};
				SpinningWheel.addSlot(minutes_array, 'left shrink', $input.attr('minutes'));

				SpinningWheel.setDoneAction(function(){
					var result = SpinningWheel.getSelectedValues(); 
					$input.val(result.values.join(":"));
					$input.attr('hours', result.keys[0] )
					$input.attr('minutes', result.keys[1] )
				});
				SpinningWheel.open();
			});
	},
	edit_job: function($job){
		$form = $('form#edit_job_form');
		$form.find('.client_list').val($job.attr("client_code"));
		$form.attr('job_id', $job.attr("job_id"));
		app.mostrar_asuntos('form#edit_job_form .subject_list','form#edit_job_form .client_list');
		$form.find('.subject_list').val($job.attr("subject_code"));
		$form.find('[name=description]').val("");
		$form.find('[name=fecha]').val("");
		$form.find('[name=duration]').val("");
	},
	old_job: function(){
		$form = $('form#old_job_form');
		$form.find('.client_list').val("");
		$form.find('.subject_list').val("");
		$form.find('[name=description]').val("");
		var d = new Date();
		var mes = d.getMonth();
		var dia = d.getDate();
		var ano = d.getFullYear();
		$input = $form.find('[name=fecha]');
		$input.val([dia, meses[mes], ano].join(" de "));
		$input.attr('day', dia );
		$input.attr('month', mes+1 );
		$input.attr('year', ano );	
		$form.find('[name=duration]').val("");
	}
};