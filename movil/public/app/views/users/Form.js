ToolbarDemo.views.UsersForm = Ext.extend(Ext.form.FormPanel, {
    defaultInstructions: 'Por favor introduzca la informacion solicitada.',

	clientes:null,//ToolbarDemo.stores.clienteStore,
	asuntos:null,//ToolbarDemo.stores.asuntoStore,
	duracion:null,
	timeInterval:{interval:false},
	tiempo:0,
	caller:null,
	reloj:false,
	showOverlay:null,
	
    initComponent: function(){
		//var fechaHoy = new Date()
        var clockCheckBox, titlebar, buttonbar, cancelButton, saveButtonbar, sendButton, saveButton, fields, horaInicio, horaFin, iconoReloj;// duracion;

		var reloj =	{
						xtype:'button',
						id:'enviar',
						iconMask: true,
						iconCls:  'time',						
						//disabled: true,
						ui: 'plain',
						//style:{
							//position:'inherit',
							//top:'350%',
						//},
						//hidden: 'true',
						handler: this.onClockAction,
						scope:this
					};		
		
		iconoReloj =	{
						xtype:'button',
						id:'iconoReloj',
						iconMask: true,
						iconCls:  'time relojBlink',						
						disabled: true,
						hidden:true,
						ui: 'plain',
						style:{
							position: "inherit",
							left: Ext.is.Phone?"-97%":"-91%",
							//left: Ext.is.Phone?"-94%":"-91%",
							//top: "340%",
							top: "349%",
							//color:"white",
							//"background-color": "#333",
							//height: "1.5em",
							padding: ".8em 0em 0em 0em",
							//"background-image": "-webkit-linear-gradient(white, white 100%, white)",
							opacity:"1",
							"z-index": "10"
						},
						handler: function(){
							Ext.dispatch({
								controller: 'Home',
								action    : 'timer',
								value		  : {minute:0,hour:0}
							});						
						},
						scope:this
					};	
		
		var overlayTb = new Ext.Toolbar({
            dock: 'top'
        });
        
        var overlay = new Ext.Panel({
			layout: 'fit',
			overlay:true,
            floating: true,
            modal: true,
            centered: false,
            width: Ext.is.Phone ? 130 : 200,
            height: Ext.is.Phone ? 110 : 200,
            styleHtmlContent: true,
            //dockedItems: overlayTb,
            scroll: 'vertical',
            //contentEl: 'timer',
            cls: 'htmlcontent'
        });

        this.showOverlay = function() {
            overlay.setCentered(true);
            overlayTb.setTitle('Attached Overlay');
            overlay.show();
			//overlay.show();
        };
		
		
		//this.clientes=clienteStore;
		//this.asuntos=tareaStore;
		
		
		//this.clientes = new Ext.data.Store({
		//	model: 'Cliente',
		//	data:[],
		//	autoLoad: true
		//});
			
		//this.asuntos = new Ext.data.Store({
		//	model: 'Asunto',
		//	data: [],
		//	autoLoad: true
		//});
		
        cancelButton = {
            text: 'cancel',
			//cls: 'x-list-header',
            ui: 'back',
            handler: this.onCancelAction
        };

		var cancel2 = {
            text: 'cancel',
			//cls: 'x-list-header',
            ui: 'myBack'
            //handler: this.onCancelAction
        };
		
		var cancel = {
            //text: 'cancel',
			//cls: 'x-list-header',
            //ui: 'back',
			iconCls:'arrow_left',
			iconMask:true,
			ui: 'plain',
            handler: this.onCancelAction
        };
		
        titlebar = {
            id: 'Titlebar',
            xtype: 'toolbar',
			id:'toolbarForm',
			cls: 'logoLemon'
            //title: 'Lemon',
            //items: [ cancel ]
        };

		buttonbar = {
            xtype: 'toolbar',
			id: 'userFormTitlebar',
            //dock: 'bottom',
			//cls: 'x-list-header',
			title: 'Nuevo',
			//titleCls:'x-list-header2',
			//style: 'height:50%'
            items: [cancel, {xtype: 'spacer'},reloj]
        };
		
		sendButton = {
            id: 'userFormSendButton',
            text: 'send',
            ui: 'confirm',
            handler: this.onSendAction,
            scope: this
        };
		
        saveButton = {
            id: 'userFormSaveButton',
            text: 'save',
            ui: 'confirm',
            handler: this.onSaveAction,
            scope: this
        };

        deleteButton = {
            id: 'userFormDeleteButton',
            text: 'Eliminar',
            ui: 'decline',
            handler: this.onDeleteAction,
            scope: this
        };

        saveButtonbar = {
            xtype: 'toolbar',
            dock: 'bottom',
			//cls: 'x-button x-button-confirm',
            items: [deleteButton, {xtype: 'spacer'}, saveButton]
        };

		clockCheckBox = {
			xtype: 'checkboxfield',
            id: 'relojCheckBox',
			name: 'reloj',
            label: 'reloj',
			vaue: false,
			hidden: 'true',
			listeners:{
				check: function(){this.duracion.disable();},//this.onClockAction()},
				uncheck: function(){this.duracion.enable()},
				scope:this
			}
            //ui: 'confirm',
            //handler: this.onClockAction,
            //scope: this
        };
		
		horaInicio = new Ext.form.TimePicker({
			name : 'inicio',
			id: 'inicio',
			label: 'hora inicio',
			//minuteScale: 5,
			hidden: 'true',
			hourFrom: 8,
			hourTo: 18,			
			value: {
				hour: (new Date().getHours())%24,
				minute: (new Date().getMinutes())
			}
		});

		horaFin = new Ext.form.TimePicker({
			name : 'fin',
			id: 'fin',
			label: 'hora fin',
			//minuteScale: 5,
			hidden: 'true',
			hourFrom: 8,
			hourTo: 18,		
			value: {
				hour: (new Date().getHours())%24,
				minute: (new Date().getMinutes())+1
			}
		});
		
		this.duracion = //new Ext.form.TimePicker(
		{
			xtype: 'timepickerfield',
			name : 'duracion',
			id: 'duracion',
			label: 'duraci&oacute;n',
			button: 'button',
			minuteScale: 1,
			picker: { hourTo: 99 }
			//hourFrom: 8,
			//hourTo: 18,
			//labelWidth: '42%',
			//style: {
             //       width: '95%',
					
            //    }
			
		};//);
		
        fields = {
            xtype: 'fieldset',
            id: 'userFormFieldset',
			name: 'proyecto',
            title: 'Detalles del Trabajo',
			//width: '200%',
			style: {
					left: '-34px',
					width: '128%'
                },
            instructions: this.defaultInstructions,
            defaults: {
                xtype: 'textfield',
                labelAlign: 'left',
                labelWidth: '30%',
                required: false,
                useClearIcon: true,
                autoCapitalize : false
            },
            items: [//{ 
					//xtype: 'button',},
					//name: 'proyecto',
					//title: 'Detalles de proyecto',
					//items:[
					{
						xtype: 'selectfield',
						name: 'clientes',
						id: 'clientes',
						label: 'cliente',
						disabled :true,
						store: ToolbarDemo.stores.clienteStore,//this.clientes,
						displayField: 'glosa',						
						valueField: 'codigo',
						//valueField: 'glosa',
						placeHolder:'No hay cliente'
					},{
                    xtype: 'ToolbarDemo.views.ErrorField',
                    fieldname: 'clientes'
					},{
						xtype: 'selectfield',
						name: 'tareas',
						label: 'asunto',
						id: 'tareas',
						disabled :true,
						store: ToolbarDemo.stores.asuntoStore,//this.asuntos,
						displayField: 'glosa',
						valueField: 'codigo',
						//valueField: 'glosa',
						placeHolder:'No hay asunto'
					},{
                    xtype: 'ToolbarDemo.views.ErrorField',
                    fieldname: 'tareas'
					},
						this.duracion
					,{
						xtype: 'fieldset',
						name: 'inicio_fin',
						hidden: true,
						//title: 'Seleccione la fecha y la hora',					
						items: [
							horaInicio,
							horaFin,							
						]
					},clockCheckBox,{
						xtype: 'datepickerfield',
						name: 'fecha',
						id: 'fecha',
						tittle: 'fecha',
						label: 'fecha',
						//format: 'd/m/Y',
						value: new Date(),
						picker: { yearFrom: 2011 }
					},{
						xtype: 'textfield',
						name: 'ordenado_por',
						id: 'ordenado_por',
						label: 'ordenado por'
					},{
						xtype: 'textareafield',
						name: 'descripcion',
						id: 'descripcion',							
						label: 'descripcion',
						maxLength : 1000,
						maxRows : 6		
					}//,
					//]
				//}
				,{
                    xtype: 'ToolbarDemo.views.ErrorField',
                    fieldname: 'ordenado_por'
                },{
                    xtype: 'ToolbarDemo.views.ErrorField',
                    fieldname: 'descripcion'
                },{
                    xtype: 'ToolbarDemo.views.ErrorField',
                    fieldname: 'fecha'
                },{
                    xtype: 'ToolbarDemo.views.ErrorField',
                    fieldname: 'inicio'
                },{
                    xtype: 'ToolbarDemo.views.ErrorField',
                    fieldname: 'fin'
                },{
                    xtype: 'ToolbarDemo.views.ErrorField',
                    fieldname: 'duracion'
                },{
                    xtype: 'ToolbarDemo.views.ErrorField',
                    fieldname: 'reloj'
                }]
        };	
							
        Ext.apply(this, {
            scroll: 'vertical',
            dockedItems: [ titlebar, saveButtonbar, buttonbar ],
			//items: [deleteButton, {xtype: 'spacer'}, saveButton]
            items: [{
						layout: {
							type: 'hbox',
							pack: 'start',
							align: 'stretch',
							padding: '0em',
							style: {
								//width: '3%',
								padding: '0em'
							}
							//align: 'stretch',
							//align: 'vertical'							
						},
						items:[fields,iconoReloj]
					}  ],
            listeners: {
                show: function() {
					if(!this.reloj){
						var deleteButton = this.down('#userFormDeleteButton'),
							saveButton = this.down('#userFormSaveButton'),
							titlebar = this.down('#userFormTitlebar'),
							model = this.getRecord();

							ToolbarDemo.views.usersForm.initForm();
							//ToolbarDemo.views.usersForm.show();
							
						if (model.phantom) {
							//titlebar.setTitle('Nuevo trabajo');
							saveButton.setText('Guardar');
							deleteButton.hide();
							//ToolbarDemo.views.usersForm.initForm();
						} else {
							titlebar.setTitle('Editar trabajo');
							saveButton.setText('Actualizar');
							deleteButton.show();
						}
					}
                },
                hide: function() {
					if(!this.reloj){
						this.resetForm();
						//ToolbarDemo.views.timer.init()
					}
                },
				listeners: {
						beforeorientationchange:function(panel, orientation, width, height ){
							//return false;
						}
				}
            }
				
			
			
        });				
		
        ToolbarDemo.views.UsersForm.superclass.initComponent.call(this);
		

		this.down('#clientes').on({
						change: this.onClienteChange,
						scope: this
					});
					
		this.down('#inicio').on({
						change: this.onInicioChange,
						scope: this
					});
					
		this.down('#fin').on({
						change: this.onFinChange,
						scope: this
					});
					
		this.down('#duracion').on({
						change: this.onDuracionChange,
						scope: this
					});
		
    },
	
	setOffline:function(){
		var tool = ToolbarDemo.views.usersForm.down('#toolbarForm');
		tool.removeCls('logoLemon');
		tool.addCls('logoLemonOff');
	},
	
	setOnline:function(){
		var tool = ToolbarDemo.views.usersForm.down('#toolbarForm');
		tool.addCls('logoLemon');
		tool.removeCls('logoLemonOff');
	},
	
	initForm:function() {
		ToolbarDemo.views.usersForm.down('#fecha').setValue({month: (new Date().getMonth()+1), day: (new Date().getDate()), year: (new Date().getFullYear())},true);
		//ToolbarDemo.views.usersForm.down('#fecha').getDatePicker().setValue({month: (new Date().getMonth()+1), day: (new Date().getDate()), year: (new Date().getFullYear())},true);
		ToolbarDemo.views.usersForm.down('#duracion').setValue({hour: 0, minute: 0},true);
	},
	
	
	setDuracion: function(value){
		var inicio = ToolbarDemo.views.usersForm.down('#inicio');
		var fin = ToolbarDemo.views.usersForm.down('#fin');
		var duracion = ToolbarDemo.views.usersForm.down('#duracion');
		
		ToolbarDemo.views.usersForm.cambiarDuracion(inicio,fin,duracion,value);
	},	
	
	setTiempo: function(tiempo) {
		ToolbarDemo.views.usersForm.tiempo=tiempo;
	},
	
	getTiempo: function(formato) {
		var hora='';
	
		if(formato==''){
			hora = ToolbarDemo.views.usersForm.tiempo;
		}else if (formato='formato'){
			var tiempo = ToolbarDemo.views.usersForm.tiempo;			
			var mins = parseInt(tiempo/60);		
			var seg = tiempo%60;
			var horas=parseInt(mins/60);
			var min=mins%60;
			var hour1 = parseInt(horas/10);
			var hour2 = horas%10;
			var min1 = parseInt(min/10);
			var min2 = min%10;		
			var seg1 = parseInt(seg/10);
			var seg2 = seg%10;
			var inicio='';
			hora=inicio.concat(hour1,hour2,':',min1,min2,':',seg1,seg2);			
		}
		
		return hora;
	},
	
	onIntervalAction: function() {
		var tiempo = ToolbarDemo.views.usersForm.getTiempo('formato');
		Ext.Msg.updateText(tiempo,true) ;
		ToolbarDemo.views.usersForm.setTiempo(ToolbarDemo.views.usersForm.getTiempo('')+1);	
    },
	
	onClockAction: function() {	
		var value=this.formatoHoras(this.down('#duracion').value);
		var arr = (value+"").split(':');
		var hour = parseInt(arr[0],10);
		var minute = parseInt(arr[1],10);
		
		Ext.dispatch({
			controller: 'Home',
            action    : 'timer',
			value		  : {minute:minute,hour:hour}
        });
	},
	
	onClienteChange: function(selectField, value){
	//alert('holi??????????');
		var cField = this.down('#clientes').store;
		var SelectField = this.down('#tareas');
		//SelectField.doLayout();
		SelectField.enable();
		//alert(value);
		//alert('holi');
		SelectField.store.clearFilter(); // remove the previous filter
		
		
		// Apply the selected Country's ID as the new Filter
		SelectField.store.filter({property: 'codigo_padre',value: value,exactMatch: true});
		
		// Select the first City in the List if there is one, otherwise set the value to an empty string
		var first = SelectField.store.getAt(0);
		if(first){
			//SelectField.setValue(first.data.TareaID);
			SelectField.setValue(first.data.codigo);
			//SelectField.setValue('');
		} else {
			SelectField.setValue('');
		}
	},
	
	formatoHoras: function(value)
	{
		if(!isNaN(parseInt(value.hour)))
		{
			var Hour = value.hour;
			var Minute = value.minute;
		}else{
			if(!value==''){
				var arr = (value+"").split(':');
				var Hour = parseInt(arr[0],10);
				var Minute = parseInt(arr[1],10);
			}else{
				var Hour = 0;
				var Minute = 0;
			}			
		}	
	
		return Hour + ':' + Minute;
	},
	
	onHoraChange: function(inicio,fin,duracion,value)
	{	
		var finValue=this.formatoHoras(fin.value);
		var arr = (finValue+"").split(':');
		var finHour = parseInt(arr[0],10);
		var finMinute = parseInt(arr[1],10);
		
		var inicioValue=this.formatoHoras(inicio.value);
		arr = (inicioValue+"").split(':');
		var inicioHour = parseInt(arr[0],10);
		var inicioMinute = parseInt(arr[1],10);
	
		if((finHour<inicioHour) || (finHour==inicioHour && finMinute<=inicioMinute) || (finHour==inicioHour && finMinute==inicioMinute)){
			fin.setValue({hour:(inicioMinute==59)?(inicioHour+1):inicioHour, minute:((inicioMinute+1)%60)}, false);
			duracion.setValue({hour:'0', minute:'1'}, false);
		}else{
			duracion.setValue({hour:(finHour-inicioHour), minute:(finMinute-inicioMinute)}, true);
		}
	},
	
	onInicioChange: function(inicio,value) {
		var fin = this.down('#fin');
		var duracion = this.down('#duracion');
		
		this.onHoraChange(inicio,fin,duracion,value);
		
	},
	
	onFinChange: function(fin,value) {
		var inicio = this.down('#inicio');
		var duracion = this.down('#duracion');
		
		this.onHoraChange(inicio,fin,duracion,value);
	},
	
	onDuracionChange: function(duracion,value) {
		var fin = this.down('#fin');
		var inicio = this.down('#inicio');
				
		this.cambiarDuracion(inicio,fin,duracion,value);
	},
	
	numInterval:function(interval,num) {
		var numero=0;
		//alert(interval);
		interval=interval?interval:1;
		//alert(interval);
		if(num%interval==0){
			numero=num;
		}else{
			numero=parseInt(num/interval)*interval;//+interval;
			numero=num%interval>parseInt(interval/2)?numero+interval:numero;
		}
		
		return numero;
	},	
	
	cambiarDuracion: function(inicio,fin,duracion,value) {
		
		if(value.usar){		
			//var duracionValue = ToolbarDemo.views.usersForm.formatoHoras(value);
			if(ToolbarDemo.views.usersForm.timeInterval.interval){
				var inicioValue=ToolbarDemo.views.usersForm.formatoHoras(inicio.value);
				var arr = (inicioValue+"").split(':');
				var inicioHour = parseInt(arr[0],10);
				var inicioMinute = parseInt(arr[1],10);		
				
				if (value.minute==0 && value.hour==0)	{
					fin.setValue({hour:(inicioMinute==59)?(inicioHour+0):inicioHour, minute:((inicioMinute+1)%60)}, false);
					var min=0,hour=0;
					min=ToolbarDemo.views.usersForm.numInterval(ToolbarDemo.views.usersForm.timeInterval.min,min);
					hour=ToolbarDemo.views.usersForm.numInterval(ToolbarDemo.views.usersForm.timeInterval.hour,hour);
					duracion.setValue({hour:hour, minute:min}, false);
				}
				else{
					var min = ((inicioMinute + value.minute)%60);
					var hour = ((inicioMinute + value.minute)/60)>1?(inicioHour + 1 + value.hour):inicioHour + value.hour;
					fin.setValue({hour:hour, minute:min}, false);
					min=ToolbarDemo.views.usersForm.numInterval(ToolbarDemo.views.usersForm.timeInterval.min,value.minute);
					hour=ToolbarDemo.views.usersForm.numInterval(ToolbarDemo.views.usersForm.timeInterval.hour,value.hour);
					duracion.setValue({hour:hour, minute:min}, false);
				}

			}else{
				var inicioValue=ToolbarDemo.views.usersForm.formatoHoras(inicio.value);
				var arr = (inicioValue+"").split(':');
				var inicioHour = parseInt(arr[0],10);
				var inicioMinute = parseInt(arr[1],10);		
				
				if (value.minute==0 && value.hour==0)	{
					fin.setValue({hour:(inicioMinute==59)?(inicioHour+1):inicioHour, minute:((inicioMinute)%60)}, false);
					duracion.setValue({hour:'0', minute:'0'}, false);
				}
				else{
					var min = ((inicioMinute + value.minute)%60);
					var hour = ((inicioMinute + value.minute)/60)>1?(inicioHour + 1 + value.hour):inicioHour + value.hour;
					fin.setValue({hour:hour, minute:min}, false);
					duracion.setValue({hour:value.hour, minute:value.minute}, false);
				}
			}
		}else if(value=="vacio"){
			duracion.setValue(value,false);
		}else{
			var inicioValue=ToolbarDemo.views.usersForm.formatoHoras(inicio.value);
			var arr = (inicioValue+"").split(':');
			var inicioHour = parseInt(arr[0],10);
			var inicioMinute = parseInt(arr[1],10);		
			
			if (duracion.value.minute==0 && duracion.value.hour==0)	{
				fin.setValue({hour:(inicioMinute==59)?(inicioHour+1):inicioHour, minute:((inicioMinute+1)%60)}, false);
				duracion.setValue({hour:'0', minute:'0'}, false);
			}
			else{
				var min = ((inicioMinute + duracion.value.minute)%60);
				var hour = ((inicioMinute + duracion.value.minute)/60)>1?(inicioHour + 1 + duracion.value.hour):inicioHour + duracion.value.hour;
				fin.setValue({hour:hour, minute:min}, false);
			}
		}
		
		//ToolbarDemo.views.homeCard.setProyectID(,duracion.value.hour+':'+duracion.value.hour);
	},
	
    onCancelAction: function() {
		Ext.Msg.confirm("&#191Seguro que desea salir?", "Se perderan todos los cambios que no han sido guardados", function(answer) {		
            if (answer === "yes") {
                 Ext.dispatch({
					controller: 'Home',
					action: 'home'
				});
            }
        }, this);
       
    },

	onSendAction: function() {
        Ext.dispatch({
            controller: 'Home',
            action: 'index',
			data: this.getValues()
        });
    },
	
    onSaveAction: function() {
	
		if(ToolbarDemo.views.timer.running){
			Ext.Msg.alert("No se puede guardar", "El reloj aun esta funcionando", function(answer) {},this);		
		}else{
			var model = this.getRecord();
			//var record = this.getValues();

			//var holi = this.down('#fecha');
			//var fechaCorta =this.down('#fecha').value.dateFormat('d M,Y');
			//alert(hol);
			
			//var holi = this.down('#fecha');
			if(!ToolbarDemo.views.timer.running){
				ToolbarDemo.views.timer.detenerTimer();
			}else{
				ToolbarDemo.views.timer.stop();
			}			
			
			if( ToolbarDemo.views.usersForm.down('#duracion').value == '00:00' ) {
				Ext.Msg.alert("AVISO", "Debe ingresar la duraci&oacute;n del trabajo.");
				return false;
			}
			
			if( ToolbarDemo.views.usersForm.down('#descripcion').getValue().length == 0 ) {
				Ext.Msg.alert("AVISO", "Debe ingresar la descripci&oacute;n del trabajo.") ;
				return false;
			}
			
			Ext.dispatch({
				controller: 'Home',
				action    : (model.phantom ? 'save' : 'update'),
				data      : this.getValues(),
				record    : this.getRecord(),
				//fecha	  : fechaCorta,
				form      : this
			});
			
		}
    },

    onDeleteAction: function() {
		ToolbarDemo.views.timer.detenerTimer();
	
        Ext.Msg.confirm("Borrar este Trabajo?", "", function(answer) {		
            if (answer === "yes") {
                Ext.dispatch({
                    controller: 'Home',
                    action    : 'remove',
                    record    : this.getRecord()
                });
            }
        }, this);
    },

    showErrors: function(errors) {
        var fieldset = this.down('#userFormFieldset');
        this.fields.each(function(field) {
            var fieldErrors = errors.getByField(field.name),
                errorField = this.resetField(field);

            if (fieldErrors.length > 0) {
                field.addCls('invalid-field');
                errorField.update(fieldErrors);
                errorField.show();
            }
        }, this);
        fieldset.setInstructions("Please amend the flagged fields");
    },

    resetForm: function() {
        var fieldset = this.down('#userFormFieldset');		
        this.fields.each(function(field) {
			//alert('estoy en reset');
			//alert(this.fields);
            this.resetField(field);
        }, this);
        fieldset.setInstructions(this.defaultInstructions);
        this.reset();
    },

    resetField: function(field) {
        var errorField = this.down('#'+field.name+'ErrorField');
		//alert(field.name);
        errorField.hide();
        field.removeCls('invalid-field');
        return errorField;
    },
	
	cargarClientes:function(clientes) {		
		//ToolbarDemo.views.usersForm.clientes.add(clientes);
		 Ext.dispatch({
                    controller: 'Home',
                    action    : 'setClientesAsuntos',
                    clientes    : clientes
                });
		
		
		//ToolbarDemo.views.usersForm.clientes.sync();
		ToolbarDemo.views.usersForm.down('#clientes').enable();
		//ToolbarDemo.views.usersForm.onClienteChange(ToolbarDemo.views.usersForm.down('#clientes'), 1);
	},
	
	cargarAsuntos:function(asuntos) {		
		//ToolbarDemo.views.usersForm.asuntos.add(asuntos);
		 Ext.dispatch({
                    controller: 'Home',
                    action    : 'setClientesAsuntos',
                    asuntos    : asuntos
                });
		
		
		//ToolbarDemo.views.usersForm.asuntos.sync();
		//ToolbarDemo.views.usersForm.down('#Asuntos').enable();
		//var SelectField = ToolbarDemo.views.usersForm.down('#clientes');
		//var first = SelectField.store.getAt(0);
		//ToolbarDemo.views.usersForm.onClienteChange(SelectField, first.data.codigo);		
	},
	
	cargarIntervalo:function(intervalo) {		
		//ToolbarDemo.views.usersForm.timeInterval=intervalo;
		//alert(intervalo);
		//var num = parseInt(intervalo);
		var inter = parseInt(intervalo);
		
        if(!isNaN(inter)){
			//ToolbarDemo.views.usersForm.down('#duracion').minuteScale=inter;a.__proto__ = b
			ToolbarDemo.views.usersForm.down('#duracion').picker.__proto__ ={minuteScale:inter};
			ToolbarDemo.views.usersForm.timeInterval.min=inter;			
			localStorage.removeItem("intervalMin"); 
			localStorage.setItem("intervalMin", inter);
			//ToolbarDemo.views.usersForm.timeInterval.hour=interval;
			ToolbarDemo.views.usersForm.timeInterval.interval=true;
		}else if(!(navigator.onLine)){	
			var inter = localStorage.getItem("intervalMin");
			if(inter!=null){
				inter=parseInt(inter);
				ToolbarDemo.views.usersForm.down('#duracion').picker.__proto__ ={minuteScale:inter};
				localStorage.removeItem("intervalMin"); 
				localStorage.setItem("intervalMin", inter);
				ToolbarDemo.views.usersForm.timeInterval.min=inter;
				ToolbarDemo.views.usersForm.timeInterval.interval=true;				
			}
		}
		
		ToolbarDemo.views.usersForm.down('#duracion').onChangeInterval();
			//ToolbarDemo.views.usersForm.down('#Asuntos').enable();
			//ToolbarDemo.views.usersForm.onClienteChange(ToolbarDemo.views.usersForm.down('#clientes'), 1);		
	},	
	
	prueba: function(yo) {
	
		var clientes;
		var asuntos;
		var cantAsuntos=[13,7,19,27,23];
		for(var i=1;i<=200;i=i+1) {
			yo.clientes.add({codigo:i,glosa:'cliente'+i});
			//alert(cantAsuntos[(i%5)]);
			for(var j=1;j<=cantAsuntos[(i%5)];j=j+1) {
				yo.asuntos.add({codigo:j,glosa:'asunto'+i+'_'+j,codigo_padre:i});
			
			}
		
		}
		
		yo.down('#clientes').enable();
	
	}
	//prueba:function(id)
	//{
		//var listaC = ToolbarDemo.views.usersForm.down('#clientes').store;
		
		
		//var num = listaC.find('ClienteID',id);
		//var holi = listaC.getAt(num);
		//alert('holi');
	
	//}
});

Ext.reg('ToolbarDemo.views.UsersForm', ToolbarDemo.views.UsersForm);
