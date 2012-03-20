Ext.regController('Home', {
	store: ToolbarDemo.stores.users,
	//storeLog: ToolbarDemo.stores.logStore,
	storeLog: null,
	storeUpdate: ToolbarDemo.stores.updateStore,
	actualUpdate:null,
	barrier:0,	
	clientes:ToolbarDemo.stores.clienteStore,
	asuntos:ToolbarDemo.stores.asuntoStore,
	storeActualizar:ToolbarDemo.stores.actualizarStore,
	visible:"",
   
	revealViewport:function(params){
		ToolbarDemo.views.viewport.reveal(params.target);		
	},
   
	rotate:function(){
		ToolbarDemo.views.viewportRotate.show();
		this.visible="rotate";		
	},
   
	deRotate:function(){
		ToolbarDemo.views.viewportRotate.hide();
		this.visible="";
	},
   
	home: function() {
		ToolbarDemo.views.viewportLogin.hide();
		if(this.visible==""){
			ToolbarDemo.views.viewport2.hide({ type: 'slide' , reveal: 'true' ,direction:'left'});
			ToolbarDemo.views.viewport.show({ type: 'slide' , reveal: 'true' ,direction:'left'});			
		}else{
			ToolbarDemo.views.viewport2.hide(false);
			ToolbarDemo.views.viewport.show(false);
		}
		
    },
   
	cambiarViewPort: function() {
		ToolbarDemo.views.viewportLogin.hide();
		if(this.visible==""){
			ToolbarDemo.views.viewport.hide({ type: 'slide' , cover: 'true' ,direction:'left'});
			ToolbarDemo.views.viewport2.show({ type: 'slide' , cover: 'true' ,direction:'left'});		
		}else{
			ToolbarDemo.views.viewport.hide(false);
			ToolbarDemo.views.viewport2.show(false);
		}
    },
	
	endLogin: function(){
		ToolbarDemo.views.viewportLogin.hide();
		if(this.visible==""){
			ToolbarDemo.views.viewport.show({ type: 'slide' , cover: 'true' ,direction:'left'});			
			ToolbarDemo.views.viewport.reveal('listCard');
		}else{
			ToolbarDemo.views.viewport.show(false);
			ToolbarDemo.views.viewport.reveal('listCard');
		}
	},	
   
	onRotate:function(){
		//var body=Ext.getBody();
		//Ext.getBody().dom.style.cssText='top:0px';
		//$(document.body).css("-webkit-transform",  "translate(0px,40px)");
	
	
		var iconoReloj=ToolbarDemo.views.usersForm.down('#iconoReloj');
		
		var botones=ToolbarDemo.views.timer.down('#botones');
		if(Ext.getOrientation()=='landscape'){		
			var w=botones.width;
			if(parseInt((w/window.innerWidth)*100)!=45){
				//botones.setWidth('45%');
			}
			iconoReloj.el.dom.style.cssText="position: inherit; left: -94%; top: 349%; padding-top: 0.8em; padding-right: 0em; padding-bottom: 0em; padding-left: 0em; opacity: 1; z-index: 10; ";
		}else{
			var w=botones.width;
			if(parseInt((w/window.innerWidth)*100)!=68){
				//botones.setWidth('68%');
			}
			iconoReloj.el.dom.style.cssText="position: inherit; left: -97%; top: 349%; padding-top: 0.8em; padding-right: 0em; padding-bottom: 0em; padding-left: 0em; opacity: 1; z-index: 10; ";
		}
	},
   
	timer:function(params) {
		//ToolbarDemo.views.timer.tiempo=(params.value.hour*60+params.value.minute)*60;
		if(!ToolbarDemo.views.timer.running && !ToolbarDemo.views.timer.paused){
			ToolbarDemo.views.timer.setTime(params.value.hour,params.value.minute,0);
		}
		if(!ToolbarDemo.views.timer.running)
		{
			ToolbarDemo.views.timer.milisecond=0;
		}
		ToolbarDemo.views.usersForm.reloj=true;
		//ToolbarDemo.views.timer.onStartAction();
		ToolbarDemo.views.viewport2.reveal('timer');
		ToolbarDemo.views.usersForm.down('#iconoReloj').setVisible(false);
		//this.onRotate();
		
	},
	
	setDuracion:function(options) {
			
		ToolbarDemo.views.usersForm.setDuracion(options.value)
	},
   
	setClientesAsuntos:function(options) {	
		
		if(options.clientes){
			this.clientes.removeAll();
			this.clientes.getProxy().clear();
			this.clientes.data.clear();
			this.clientes.sync();
			this.clientes.add(options.clientes);
			this.clientes.sync();
			ToolbarDemo.views.usersForm.clientes=this.clientes;
		}
		if(options.asuntos){
			this.asuntos.removeAll();
			this.asuntos.getProxy().clear();
			this.asuntos.data.clear();
			this.asuntos.sync();
			this.asuntos.add(options.asuntos);
			this.asuntos.sync();
			ToolbarDemo.views.usersForm.asuntos=this.asuntos;
		}
	},
   
	loadClientesAsuntos:function() {
	
		if(this.clientes.getCount()>0 && this.asuntos.getCount()>0){
			//var c=this.clientes.data.items;
			//var a=this.asuntos.data.items;
			
			//this.clientes.removeAll();
			//this.clientes.sync();
			//this.asuntos.removeAll();
			//this.asuntos.sync();
			
			//this.clientes.add(c);
			//this.clientes.sync();
			//this.asuntos.add(a);
			//this.asuntos.sync();
			ToolbarDemo.views.usersForm.down('#clientes').enable();
		}
	},
   
	usersForm:function() {
		ToolbarDemo.views.viewport2.reveal('usersForm');
		ToolbarDemo.views.usersForm.reloj=false;
	},	
   
	clearLogs: function(){
		this.storeUpdate.removeAll();
		this.storeUpdate.sync();
		this.storeUpdate.getProxy().clear();
		this.storeUpdate.data.clear();
		this.storeUpdate.sync();
		this.actualUpdate=null;
		var store = ToolbarDemo.stores.logStore;
		store.getProxy().clear();
		store.data.clear();
		store.sync();
	},
   
	clearActualizarLogs: function(){
		this.storeActualizar.removeAll();
		this.storeActualizar.sync();
		this.storeActualizar.getProxy().clear();
		this.storeActualizar.data.clear();
		this.storeActualizar.sync();
	},
   
	initListCard: function(){		
		
		//ToolbarDemo.views.viewport.reveal('listCard');
		for(var i=0;i<this.store.getCount();i=i+1){
			ToolbarDemo.views.errorlog.setBadgeText('aumentar');
		}
		
		var storeU= new Array();
		var model;
		var countU=this.storeUpdate.getCount();
		for(var i=0;i<countU;i=i+1){
			model=this.storeUpdate.getAt(i);
			storeU.push({text:model.data.text,OK:model.data.OK,ERROR:model.data.ERROR,id:model.data.id});
		}
		
		this.storeUpdate.removeAll();
		this.storeUpdate.getProxy().clear();
		this.storeUpdate.data.clear();
		this.storeUpdate.sync();
		
		var storeL= new Array();
		var modelLog;
		countL=ToolbarDemo.stores.logStore.getCount();
		for(var i=0;i<countL;i=i+1){
			modelLogData=ToolbarDemo.stores.logStore.getAt(i).data;
			storeL.push({statusText:modelLogData.statusText,responseText:modelLogData.responseText,update_id:modelLogData.update_id,clientes:modelLogData.clientes,tareas:modelLogData.tareas,fecha:modelLogData.fecha,duracion:modelLogData.duracion,ordenado_por:modelLogData.ordenado_por,descripcion:modelLogData.descripcion});
		}
		
		ToolbarDemo.stores.logStore.removeAll();
		ToolbarDemo.stores.logStore.getProxy().clear();
		ToolbarDemo.stores.logStore.data.clear();
		ToolbarDemo.stores.logStore.sync();		
		
		var update;
		for(var i=0;i<countU;i=i+1){
			var update = Ext.ModelMgr.create(storeU[i], 'Update');			
			this.storeUpdate.add(update);
		}
		this.storeUpdate.sync();
		
		var log;
		for(var j=0;j<countL;j=j+1){
			update = this.storeUpdate.getById(storeL[j].update_id);
			log=Ext.ModelMgr.create(storeL[j], 'Log');
			ToolbarDemo.stores.logStore.add(log);	
			update.logs().add(log);
			update.save();
			this.storeUpdate.sync();
			ToolbarDemo.stores.logStore.sync();	
		}
		
		
		if(false){
			this.store.removeAll();
			this.store.sync();
			this.storeUpdate.removeAll();
			this.storeUpdate.sync();
			
			var store = ToolbarDemo.stores.updateStore;
			store.getProxy().clear();
			store.data.clear();
			store.sync();
			store = ToolbarDemo.stores.logStore;
			store.getProxy().clear();
			store.data.clear();
			store.sync();
			store = ToolbarDemo.stores.users;
			store.getProxy().clear();
			store.data.clear();
			store.sync();
		}
	},
   
    verProyectos: function() {
        this.cambiarViewPort();
    },
	
	addProyecto: function(params) {
        var model = new ToolbarDemo.models.User()
        ToolbarDemo.views.usersForm.load(model);
		ToolbarDemo.views.usersForm.caller=params.caller;
        //ToolbarDemo.views.viewport2.reveal('usersForm');		

		this.cambiarViewPort();
		
		var SelectField = ToolbarDemo.views.usersForm.down('#clientes');
		var first = SelectField.store.getAt(0);
		if(first){
			ToolbarDemo.views.usersForm.onClienteChange(SelectField, first.data.codigo);	
		}
		else{
			this.log('No hay un primer elemento al entrar al form');
		}
    },
	
	send: function(options) {
		//var model = this.store.getAt(options.index);
		var unEnviado=false;
		var store=this.store;
		var update = Ext.ModelMgr.create({text:'Actualizacion realizada el '+new Date().format('j \\de F \\a \\l\\a\\s h:iA')}, 'Update');
		//var update = this.storeUpdate.create({text:'Update con fecha: '+new Date().format('d/m/Y')});
		//this.storeUpdate.add(update);
		this.storeLog=update.logs();
		this.actualUpdate=update;
		if(store.getCount()>0)
		{	
			if(options.caller=='syncButton'){
				ToolbarDemo.views.errorlog.syncButton.setIconClass('sync x-button-enviar');			
				//setTimeout(function(){ToolbarDemo.views.errorlog.syncButton.setIconClass('');ToolbarDemo.views.errorlog.syncButton.setIconClass('x-icon-mask sync');},1000);//+store.getCount()*80);
			}else{		
				ToolbarDemo.views.errorlog.tab.setIconClass('sync x-button-enviar');			
				//setTimeout(function(){ToolbarDemo.views.errorlog.tab.setIconClass('');ToolbarDemo.views.errorlog.tab.setIconClass('sync');},1000);//+store.getCount()*80);
			}
			
			
			for(var i=0;i<store.getCount();i=i+1){
				var model = store.getAt(i);
				//alert('enviando i: ' + i);
				if(!model.data.enviado){
					unEnviado=true;
					ToolbarDemo.views.settingsCard.send(model.data,model,options.index);
					this.barrier=this.barrier+1;
				}
			}
			
		}else{
			
			ToolbarDemo.views.listCard.setActualizar(store.getCount());
		}
		
		if(!unEnviado){
			ToolbarDemo.views.errorlog.tab.setIconClass('');
			ToolbarDemo.views.errorlog.tab.setIconClass('sync');
			ToolbarDemo.views.errorlog.syncButton.setIconClass('');
			ToolbarDemo.views.errorlog.syncButton.setIconClass('x-icon-mask sync');
		}
	},
	
	clienteAsunto: function(params){
		var user=ToolbarDemo.views.settingsCard.returnLogin();
		if(user.name==""){
			setTimeout(function(){
				ToolbarDemo.views.actualizacionLog.setError({hide:false,text:"Debe guardar un nombre de usuario y un password en la pesta&ntildea config"});
				ToolbarDemo.views.actualizacionLog.finLogin();
			},100);			
		}else{
			ToolbarDemo.views.settingsCard.cargar_intervaloActualizar(params.contenedor); 
			ToolbarDemo.views.settingsCard.cargar_clientesActualizar(user.name,user.password,params.contenedor);			
			ToolbarDemo.views.settingsCard.cargar_asuntosActualizar(user.name,user.password,params.contenedor);	
		}
	},
	
	newActualizarLog: function(params){
		if(params.sujeto=='intervalo'){
			this.storeActualizar.create({responseText :params.response, statusText :params.status,text:params.text + " el " + params.sujeto + ", el " + new Date().format('j \\de F \\a \\l\\a\\s h:iA')});
		}else{
			this.storeActualizar.create({responseText :params.response, statusText :params.status,text:params.text + " los " + params.sujeto + ", el " + new Date().format('j \\de F \\a \\l\\a\\s h:iA')});
		}
	},
	
	loginActualizar: function(){
		if(this.clientes.getCount()<=0 || this.asuntos.getCount()<=0){
			ToolbarDemo.views.loginCard.actualizar=true;			
		}else{
			ToolbarDemo.views.usersForm.down('#clientes').enable();
		}
	},
	
    editForm: function(options) {	
	
        var model = this.store.getAt(options.index);
		//alert(model.data.index);
		model.data.index=options.index;
		//alert(model)
		//alert(model.data.index);
        ToolbarDemo.views.usersForm.load(model);
        //ToolbarDemo.views.viewport2.reveal('usersForm');		
		
		this.cambiarViewPort();		
		
		ToolbarDemo.views.usersForm.load(model);
		var SelectField = ToolbarDemo.views.usersForm.down('#clientes');
		//SelectField.store.find()
		//var first = SelectField.store.getAt(0);
		SelectField.setValue(model.data.codigo_cliente);
		ToolbarDemo.views.usersForm.onClienteChange(SelectField, model.data.codigo_cliente);
		var SelectField = ToolbarDemo.views.usersForm.down('#tareas');
		SelectField.setValue(model.data.codigo_asunto);
		//ToolbarDemo.views.usersForm.down('#duracion').setValue(model.data.duracion);
		//ToolbarDemo.views.usersForm.down('#fecha').setValue(model.data.fecha);
    },
	
	selectProyect: function(options) {
	
		if(options.index>=0){
			var model = this.store.getAt(options.index);		
			ToolbarDemo.views.homeCard.setProyectID(options.index,model.data.duracion);	
			this.home();
		}else{
			ToolbarDemo.views.homeCard.setProyectID(-1,0);
		}
		
    },
		
	save: function(params) {
        params.record.set(params.data);
        var errors = params.record.validate();

		//params.data.fechaS=ToolbarDemo.views.usersForm.down('#fecha').value.dateFormat('d M,Y');		
		var storeC = ToolbarDemo.views.usersForm.down('#clientes').store;
		var storeT = ToolbarDemo.views.usersForm.down('#tareas').store;
		var indexC = storeC.find('codigo',params.data.clientes);
		var indexT = storeT.find('codigo',params.data.tareas);
		if(indexC>=0 && indexT>=0){
			var cliente = storeC.getAt(indexC);
			var tarea = storeT.getAt(indexT);
			params.data.codigo_cliente=params.data.clientes;
			params.data.clientes=cliente.data.glosa;			
			params.data.codigo_asunto=params.data.tareas;
			params.data.tareas=tarea.data.glosa;
			
		}else{
			setTimeout(function() {throw new Error('indice en save < 0');}, 0);
		}
		
		//alert(params.data.descripcion);
        if (errors.isValid()) {
			//var indexT = this.store.getCount()
			params.data.enviado=false;
            var a = this.store.create(params.data);
			//var holi = this.store.find('codigo',params.data.clientes);			
			//var model = Ext.ModelMgr.create(params.data,'User');
			//var holi = this.store.indexOf(model);
			//alert(holi);
            //this.index();
			//ToolbarDemo.views.homeCard.setProyectID(indexT,params.data.duracion);
			ToolbarDemo.views.listCard.setActualizar(this.store.getCount());
			//ToolbarDemo.views.listCard.setBadgeText('aumentar');		
			ToolbarDemo.views.errorlog.setBadgeText('aumentar');		
			this.home();
        } else {
            params.form.showErrors(errors);
        }		
		
		//ToolbarDemo.views.listCard.setActualizar(this.store.getCount());
		//ToolbarDemo.views.listCard.setBadgeText('aumentar');
    },

    update: function(params) {
        var tmpUser = new ToolbarDemo.models.User(params.data),
            errors = tmpUser.validate()

		//params.data.fechaS=ToolbarDemo.views.usersForm.down('#fecha').value.dateFormat('d M,Y');	
	var storeC = ToolbarDemo.views.usersForm.down('#clientes').store;
		var storeT = ToolbarDemo.views.usersForm.down('#tareas').store;
		var indexC = storeC.find('codigo',params.data.clientes);
		var indexT = storeT.find('codigo',params.data.tareas);
		if(indexC>=0 && indexT>=0){
			var cliente = storeC.getAt(indexC);
			var tarea = storeT.getAt(indexT);
			params.data.codigo_cliente=params.data.clientes;
			params.data.clientes=cliente.data.glosa;
			params.data.codigo_asunto=params.data.tareas;
			params.data.tareas=tarea.data.glosa;
			
		}else{
			setTimeout(function() {throw new Error('indice en update < 0');}, 0);
		}
			
        if (errors.isValid()) {
			params.data.enviado=false;
            params.record.set(params.data);
            params.record.save();
            this.home();
        } else {
            params.form.showErrors(errors);
        }
    },
	
	remove: function(params) {
	
		if(params.record){
			//alert(params.record.data.id);		
			//var model=this.store.getById(params.record.data.id);
			//var index=this.store.indexOf(model);
			var index=params.record.data.index;
			//alert(index);
			//alert(ToolbarDemo.views.homeCard.ProyectoID);			
			this.store.remove(params.record);
			this.store.sync();
			//ToolbarDemo.views.listCard.setBadgeText();
			this.home();
			
		}else{
			//alert('en el removeAt');
			//var index=params.index;
			//var model=this.store.getById(params.model.data.id);
			this.store.remove(params.model);
			this.store.sync();
			//ToolbarDemo.views.listCard.setBadgeText();
			//ToolbarDemo.views.errorlog.setBadgeText('aumentar');
			
		}
		
		//ToolbarDemo.views.listCard.setActualizar(this.store.getCount());
		
		//if(ToolbarDemo.views.homeCard.ProyectoID==index){
		//	ToolbarDemo.views.homeCard.setProyectID(-1,0);
		//	ToolbarDemo.views.listCard.setProyecto(-1);
		//}
		ToolbarDemo.views.errorlog.setBadgeText();
		for(var i=0;i<this.store.getCount();i=i+1){
			ToolbarDemo.views.errorlog.setBadgeText('aumentar');
		}
    },

	newLog: function(params) {
		var ok=0,error=0;
		
		if(params.model){
			params.model.data.enviado=true;
			params.model.save();
			error=this.actualUpdate.data.ERROR;
			error=error+1;
			this.actualUpdate.data.ERROR=error;
			this.actualUpdate.save();
			//this.actualUpdate.sync();
			
			this.storeLog.create({responseText :params.response, statusText :params.status, clientes:params.model.data.clientes, tareas:params.model.data.tareas, fecha:params.model.data.fecha, duracion:params.model.data.duracion, ordenado_por:params.model.data.ordenado_por, descripcion:params.model.data.descripcion,update_id:this.actualUpdate.data.id});
			this.storeLog.sync();
		}
		else{			
			ok=this.actualUpdate.data.OK;
			ok=ok+1;
			this.actualUpdate.data.OK=ok;
			this.actualUpdate.save();
			//this.actualUpdate.sync();
			
			this.storeLog.create({responseText :params.response, statusText :params.status, clientes:params.data.clientes, tareas:params.data.tareas, fecha:params.data.fecha, duracion:params.data.duracion, ordenado_por:params.data.ordenado_por, descripcion:params.data.descripcion,update_id:this.actualUpdate.data.id});
			this.storeLog.sync();			
		}		
		error=this.actualUpdate.data.ERROR;
		ok=this.actualUpdate.data.OK;
		if((ok+error)===this.barrier){
			//this.actualUpdate.logs=this.storeLog;
			this.storeUpdate.add(this.actualUpdate);
			
			var log;
			for(var i=0;i<this.actualUpdate.logs().getCount();i=i+1){
				log=this.actualUpdate.logs().getAt(i);
				log.data.update_id=this.actualUpdate.data.id;
				log.save();
			}
			this.storeUpdate.sync();
			//ToolbarDemo.views.errorlog.setBadgeText('aumentar');
			ToolbarDemo.views.errorlog.setBadgeText();//for para aumentar tanto como los con errores(borrar los con errores tambien??)
			for(var i=0;i<this.store.getCount();i=i+1){
				ToolbarDemo.views.errorlog.setBadgeText('aumentar');
			}
			ToolbarDemo.views.errorlog.setError({hide:true});
			this.barrier=0;
			
			//las dos formas que se necesitan para detener los giros de la sincronizacion 
			//(con una variable extra se podria controlar aunque no deberia seignificar un problemas)
			ToolbarDemo.views.errorlog.tab.setIconClass('');
			ToolbarDemo.views.errorlog.tab.setIconClass('sync');
			ToolbarDemo.views.errorlog.syncButton.setIconClass('');
			ToolbarDemo.views.errorlog.syncButton.setIconClass('x-icon-mask sync');
		}
    },
	
	sendOffline: function(){
		
		//problemas de caida de internet violenta no permite que se envien record y los deja en el limbo, con error y sin posibilidad de recuperarce
		ToolbarDemo.views.errorlog.setError({hide:false,text:'<div>No se puede Sincronizar. No esta conectado</div>'});
		this.barrier=0;
		
		//no deberia haber otro sistema para que esto no genere errores (que unos se envien y otros no, entonces las barrera en 0 provoca problemas)
		//las dos formas que se necesitan para detener los giros de la sincronizacion 
		//(con una variable extra se podria controlar aunque no deberia seignificar un problemas)
		ToolbarDemo.views.errorlog.tab.setIconClass('');
		ToolbarDemo.views.errorlog.tab.setIconClass('sync');
		ToolbarDemo.views.errorlog.syncButton.setIconClass('');
		ToolbarDemo.views.errorlog.syncButton.setIconClass('x-icon-mask sync');
	},
	
	setDuraciones: function(options) {
		//alert(options.index);
		var model = this.store.getAt(options.index);
		//alert(model);
		//alert(options.horas);
		//alert(options.min);
		//alert(''+options.horas+':'+options.min);
		
		if (options.horas==0 && options.min==0)
		{
			model.set('duracion',''+00+':'+00);
			var arr = (model.data.inicio+"").split(':');
			var inicioHour = parseInt(arr[0],10);
			var inicioMinute = parseInt(arr[1],10);
			
			var finHour=(inicioMinute==59)?(inicioHour):(inicioHour);
			var finMinute=((inicioMinute)%60);
			
			var hora=this.formatoHora(finHour,finMinute,0);
			model.set('fin',hora);
		}
		else
		{
			var hora=this.formatoHora(options.horas,options.min,0);
			model.set('duracion',hora);
			var arr = (model.data.inicio+"").split(':');
			var inicioHour = parseInt(arr[0],10);
			var inicioMinute = parseInt(arr[1],10);
			
			var finHour=((inicioMinute+options.min)/60>1)?(inicioHour + 1 + options.horas):(inicioHour + options.horas);
			var finMinute=((inicioMinute + options.min)%60);
			
			hora=this.formatoHora(finHour,finMinute,0);
			model.set('fin',hora);
		}
		//model.set('duracion',''+options.horas+':'+options.min);
		model.save();
		//model.data.duracion= ''+options.horas+':'+options.min;
		//alert(model.data.duracion);
		//this.store.sync();
	},
	
	actualizarHome: function(params) {
		//alert(ToolbarDemo.views.homeCard.ProyectoID+'  '+params.record.data.index);
		if (ToolbarDemo.views.homeCard.ProyectoID==params.record.data.index){
			var hora=this.formatoHora(params.value.hour,params.value.minute,0);
			//alert(hora);
			ToolbarDemo.views.homeCard.setProyectID(params.record.data.index,hora);
		}
	},
	
	initActualizar:function() { 
		//alert('init actualizar');
		//ToolbarDemo.views.listCard.setActualizar(this.store.getCount());
	},
	
	formatoHora:function(hour,min,seg) {
		var hora1=parseInt(hour/10);
		var hora2=hour%10;
			
		var min1=parseInt(min/10);
		var min2=min%10;
	
		var hora=''+hora1+hora2+':'+min1+min2;
	
		return hora;
	},
	
	setOnLine:function(){
		ToolbarDemo.views.errorlog.setOnline();
		ToolbarDemo.views.timer.setOnline();
		ToolbarDemo.views.settingsCard.setOnline();
		ToolbarDemo.views.loginCard.setOnline();
		ToolbarDemo.views.listCard.setOnline();
		ToolbarDemo.views.usersForm.setOnline();
		ToolbarDemo.views.actualizacionLog.setOnline();
	},
	
	setOffLine:function(){
		ToolbarDemo.views.errorlog.setOffline();
		ToolbarDemo.views.timer.setOffline();
		ToolbarDemo.views.settingsCard.setOffline();
		ToolbarDemo.views.loginCard.setOffline();
		ToolbarDemo.views.listCard.setOffline();
		ToolbarDemo.views.usersForm.setOffline();
		ToolbarDemo.views.actualizacionLog.setOffline();
	},
	
	log: function(msg) {
		if(msg.txt)
		setTimeout(function() {
			throw new Error(msg.txt);
		}, 0);
		
		else
		setTimeout(function() {
			throw new Error(msg.txt);
		}, 0);
	}
		
});
