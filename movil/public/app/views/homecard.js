ToolbarDemo.views.Homecard = Ext.extend(Ext.Panel, {

	name: 'home',
	ProyectoID:-1,
	tiempo:0,
	interval:1,
	intervalID:null,
    title: "home",
    iconCls: "time",	
    initComponent: function() {
		
	var addProyect = {
            itemId: 'addProyectos',
            iconCls: 'add',
            iconMask: true,
            ui: 'plain',
            handler: this.onAddProyectAction,
            scope: this
        };
	
	var titlebar = {
            dock: 'top',
            xtype: 'toolbar',
            title: '',
            items: [ { xtype: 'spacer' }, addProyect ],
			//leaf:true
        };
	
        Ext.apply(this, {
			//interval: 'null',
            defaults: {
                styleHtmlContent: true
            },			
			
			dockedItems: [titlebar],				
            items: [{
                title: 'Reloj',	
				layout: {
					type : 'vbox',
					pack : 'center',
					//align: 'stretchmax'
				},			
				items: [
						{
							xtype:  'fieldset',
							id:		'idProyecto',
							title:   'ID',
							scope: this,
						},{
							xtype:  'fieldset',
							id:		'reloj',
							title:   '<span class="number"><hr/>00</span><span class="number"><hr/>00</span>00',
							scope: this,
						}]
				},{
					xtype:  'button',
					text:   'start',
					id: 'start',
					width:'100%',
					labelWidth : '100%',
					ui:     'action',
					handler: this.onStartAction,
					scope: this
				},{
					xtype:  'button',
					text:   'stop',
					id: 'stop',
					ui:     'action',
					handler: this.onStopAction,
					scope: this
				}]
        });
        ToolbarDemo.views.Homecard.superclass.initComponent.apply(this, arguments);
		
		this.down('#idProyecto').setCentered(true);
		this.down('#reloj').setCentered(true);
		this.down('#stop').hide();
    },
	
	onAddProyectAction: function() {
		//alert(ToolbarDemo.views.homeCard.ProyectoID);
        Ext.dispatch({
            controller: 'Home',
            action: 'addProyecto',
			caller: 'reloj',
        });
    },
	
	onIntervalAction: function() {        
		var time=ToolbarDemo.views.homeCard.tiempo;
		var mins = parseInt(time/60);		
		var seg = time%60;
		var hour=parseInt(mins/60);
		var min=mins%60;
		ToolbarDemo.views.homeCard.setTime(hour,min,seg);
		ToolbarDemo.views.homeCard.tiempo=time+1;
    },
	
	onStartAction: function() {
		this.down('#stop').show();
		this.down('#start').hide();		
        this.intervalID=setInterval(this.onIntervalAction, this.interval);
    },
	
	onStopAction: function() {
		//alert('antes del if');
		if (this.ProyectoID>=0)
		{
			//alert('entre al if');
			//alert(this.ProyectoID);
			var mins=parseInt(this.tiempo/60);
			Ext.dispatch({
				controller: 'Home',
				action: 'setDuracion',
				index: this.ProyectoID,
				horas: parseInt(mins/60),
				min: mins%60,				
			});
			//var dur=ToolbarDemo.views.usersForm.down('#duracion');
			//var inicio=ToolbarDemo.views.usersForm.down('#inicio');
			//var fin=ToolbarDemo.views.usersForm.down('#fin');			
			//ToolbarDemo.views.usersForm.cambiarDuracion(inicio,fin,dur,dur.value);
		}
	
		this.down('#stop').hide();
		this.down('#start').show();
        clearInterval(this.intervalID);
		
    },
	
	setTime:function(horas,min,seg) {
		var hour1 = parseInt(horas/10);
		var hour2 = horas%10;
		var min1 = parseInt(min/10);
		var min2 = min%10;		
		var seg1 = parseInt(seg/10);
		var seg2 = seg%10;
		var inicio='';
		var hora=inicio.concat('<span class="number"><hr/>',hour1,hour2,'</span><span class="number"><hr/>',min1,min2,'</span>',seg1,seg2);
		ToolbarDemo.views.homeCard.down('#reloj').setTitle(hora);
	
	},	
	
	setProyectID: function(id,duracion) {

		if(id<0){
			ToolbarDemo.views.homeCard.ProyectoID=id;
			ToolbarDemo.views.homeCard.down('#idProyecto').setTitle('ID');
			ToolbarDemo.views.homeCard.tiempo=0;			
			ToolbarDemo.views.homeCard.setTime(0,0,0);
		}else{
	
			var arr = (duracion+"").split(':');
			var hour = parseInt(arr[0],10);
			var min = parseInt(arr[1],10);
		
			var time=(parseInt(min)+parseInt(hour)*60)*60;
		
			ToolbarDemo.views.homeCard.tiempo=time;			
			ToolbarDemo.views.homeCard.setTime(hour,min,0);
		
			ToolbarDemo.views.homeCard.ProyectoID=id;
			ToolbarDemo.views.homeCard.down('#idProyecto').setTitle(id);
			
		}
	},
});

Ext.reg('clock', ToolbarDemo.views.Homecard);
