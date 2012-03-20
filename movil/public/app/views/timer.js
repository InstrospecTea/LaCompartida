ToolbarDemo.views.Timer = Ext.extend(Ext.Panel, {

	name: 'timer',
	milisecond:0,
	tiempo:0,
	interval:Ext.is.Phone?10:1,
	intervalID:null,
	running:false,
	paused:false,
    iconCls: "time",	
    initComponent: function() {
	
	var volverButton = {
            //text: 'volver',
			//hidden: true,
            //ui: 'back',
			iconCls:'arrow_left',
			iconMask:true,
			 ui: 'plain',
            handler: this.onVolverAction,
			scope:this,
        };
	
	var titlebar = {
            dock: 'top',
            xtype: 'toolbar',
			id:'toolbarTimer',
			cls: 'logoLemon',
            //title: 'Lemon',
            //items: [ volverButton, { xtype: 'spacer' } ],
			//leaf:true
        };
	
	var buttonbar = {
            xtype: 'toolbar',
            //dock: 'bottom',
			//cls: 'x-list-header',
			title: 'Reloj',
			//titleCls:'x-list-header2',
			//style: 'height:50%'
            items: [volverButton, {xtype: 'spacer'}]
        };
	
	var bottomButtonbar = {
            xtype: 'toolbar',
            dock: 'bottom',
            //items: [deleteButton, {xtype: 'spacer'}, saveButton]
        };
		
        Ext.apply(this, {
			//interval: 'null',
			scroll:'vertical',
            defaults: {
                styleHtmlContent: true
            },			
			
			style:	{
					'background-color': '#111',
				},
			
			dockedItems: [titlebar, buttonbar, bottomButtonbar],				
            items: [{
				
                title: 'Reloj',	
				style:{
					top: '-40px',
				},
				layout: {
					type : 'vbox',
					pack : 'center',
					//align: 'constrains'
				},			
				items: [{
							xtype:  'fieldset',
							id:		'reloj',
							title:   '<span class="number"><hr/>00</span><span class="number"><hr/>00</span>00',
						},{					
							xtype:  'button',
							text:   '+5',
							id: 'masMin',
							//width:'100%',
							//height: '80px',
							//labelWidth : '100%',
							ui:     'confirm',
							handler: this.onAddAction,
							scope: this,
							style:{
								position:'absolute',
								top:'22%',
								left:'90%',
								width:'13%',
							},
						},{					
							xtype:  'button',
							text:   '-5',
							id: 'menosMin',
							//width:'100%',
							//height: '80px',
							//labelWidth : '100%',
							ui:     'confirm',
							handler: this.onSubAction,
							scope: this,
							style:{
								position:'absolute',
								top:'35%',
								left:'90%',
								width:'13%',
							},
						},{
							id:'botones',
							width:'68%',
							//height:'80%',
							layout: {
								type : 'vbox',
								pack : 'center',
								//align: 'stretch'
							},
							style:{
								position:'inherit',
							},
							items:[{					
								xtype:  'button',
								text:   'Comenzar',
								id: 'start',
								width:'100%',
								//height: '80px',
								//labelWidth : '100%',
								ui:     'confirm',
								handler: this.onStartAction,
								scope: this,
								style:{
								position:'inherit',
							},
							},{
								xtype:  'button',
								text:   'Pausar',
								id: 'pause',
								hidden:true,
								width:'100%',
								//height: '80px',
								ui:     'confirm',
								//color:     'red',
								handler: this.onPauseAction,
								scope: this,
								style:{
								position:'inherit',
							},
							},{
								xtype:  'button',
								text:   'Detener',
								id: 'stop',
								width:'100%',
								//height: '80px',
								ui:     'decline',
								//color:     'red',
								handler: this.onStopAction,
								scope: this,
								style:{
								position:'inherit',
							},
							},//{xtype:'fieldset',width:'10%',title:'  '},
							{
								xtype:  'button',
								text:   'reset',
								hidden: true,
								id: 'reset',
								//width:'65%',
								ui:     'confirm',
								handler: this.onResetAction,
								scope: this,
								style:{
								position:'inherit',
							},
							}]
						}
						]
				}],
				
				listeners: {
					beforeorientationchange:function(panel, orientation, width, height ){
						//alert('holi :D 1');
						//alert(width + '__' + height);
						var botones=ToolbarDemo.views.timer.down('#botones');							
						if(width>height){
							var w=botones.width;
							if(parseInt((w/width)*100)!=45){
								botones.setWidth('45%');
							}													
						}else{
							var w=botones.width;
							if(parseInt((w/width)*100)!=68){
								botones.setWidth('68%');
							}
						}
					},
					scope:this,
				},			
        });
		
        ToolbarDemo.views.Timer.superclass.initComponent.apply(this, arguments);
		
			
			
		
		//this.down('#reloj').setCentered(true);
		//this.down('#start').setCentered(true);
		var height=screen.height;
		this.down('#start').setHeight( parseInt(height*0.08)+'px');
		this.down('#pause').setHeight( parseInt(height*0.08)+'px');
		this.down('#stop').setHeight( parseInt(height*0.08)+'px');
		//this.down('#stop').hide();
		//this.down('#start').hide();
		//alert(screen.height);
    },
	
	setOffline:function(){
		var tool = ToolbarDemo.views.timer.down('#toolbarTimer');
		tool.removeCls('logoLemon');
		tool.addCls('logoLemonOff');
	},
	
	setOnline:function(){
		var tool = ToolbarDemo.views.timer.down('#toolbarTimer');
		tool.addCls('logoLemon');
		tool.removeCls('logoLemonOff');
	},
	
	init:function()	{
		ToolbarDemo.views.timer.running=false;
		ToolbarDemo.views.timer.paused=false;
		ToolbarDemo.views.usersForm.down('#iconoReloj').setVisible(false);
		this.down('#pause').hide();
		this.down('#start').show();
        clearInterval(this.intervalID);	
		ToolbarDemo.views.timer.setTime(0,0,0);
		//ToolbarDemo.views.timer.tiempo=0;
	},
	
	onVolverAction: function() {
		//this.stop();
		if(ToolbarDemo.views.timer.running){
			ToolbarDemo.views.usersForm.down('#iconoReloj').setVisible(true);
		}
        Ext.dispatch({
            controller: 'Home',
            action: 'usersForm'
        });
		
		//ToolbarDemo.views.timer.onStopAction();
		//ToolbarDemo.views.timer.setTime(0,0,0);
		//ToolbarDemo.views.timer.tiempo=0;
    },

	onAddAction: function() {
		var seg = ToolbarDemo.views.timer.tiempo;
		
		var res = seg +5*60;
		
		var seg = res%60;
		var mins = parseInt(res/60);
		var horas=parseInt(mins/60);
		var min=mins%60;
		
		ToolbarDemo.views.timer.setTime(horas,min,seg);
		
	},
	
	onSubAction: function() {
		var seg = ToolbarDemo.views.timer.tiempo;
		
		var res = seg - 5*60;
		
		if(res<0){
			ToolbarDemo.views.timer.setTime(0,0,0);
		}else{
			var seg = res%60;
			var mins = parseInt(res/60);
			var horas=parseInt(mins/60);
			var min=mins%60;
			
			ToolbarDemo.views.timer.setTime(horas,min,seg);
		}
	},
	
	onStartAction: function() {
		//ToolbarDemo.views.timer.down('#stop').show();		
		ToolbarDemo.views.timer.down('#pause').show();
		ToolbarDemo.views.timer.down('#pause').setWidth('100%');
		ToolbarDemo.views.timer.down('#start').hide();        
		ToolbarDemo.views.timer.running=true;
		ToolbarDemo.views.timer.paused=false;
		//ToolbarDemo.views.usersForm.down('#iconoReloj').setVisible(true);
		ToolbarDemo.views.timer.intervalID=setInterval(ToolbarDemo.views.timer.onIntervalAction, ToolbarDemo.views.timer.interval);
		Ext.dispatch({
			controller: 'Home',
			action: 'setDuracion',
			value: "vacio",				
		});
    },
	
	onPauseAction: function() {		
		ToolbarDemo.views.timer.running=false;
		ToolbarDemo.views.timer.paused=true;
		//ToolbarDemo.views.usersForm.down('#iconoReloj').setVisible(false);
		ToolbarDemo.views.timer.pause();
    },
	
	pause:function(){
		var time=ToolbarDemo.views.timer.tiempo;
		var mins = parseInt(time/60);
		var hour=parseInt(mins/60);
		var min=mins%60;
		var value={usar:true , minute:min, hour:hour};
		Ext.dispatch({
			controller: 'Home',
			action: 'setDuracion',
			value: value,				
		});	
	
		ToolbarDemo.views.timer.paused=true;
		ToolbarDemo.views.timer.detenerTimer();
	},
	
	detenerTimer:function(){
		ToolbarDemo.views.timer.down('#pause').hide();		
		ToolbarDemo.views.timer.down('#start').show();
		ToolbarDemo.views.timer.down('#start').setWidth('100%');        
		clearInterval(ToolbarDemo.views.timer.intervalID);
	},
	
	onStopAction: function() {
		this.down('#start').setWidth('100%');
		ToolbarDemo.views.timer.running=false;
		ToolbarDemo.views.timer.paused=false;
		ToolbarDemo.views.usersForm.down('#iconoReloj').setVisible(false);
		this.stop();		
    },
	
	stop:function(){
		//var time=ToolbarDemo.views.timer.tiempo;
		//var mins = parseInt(time/60);
		//var hour=parseInt(mins/60);
		//var min=mins%60;
		//var value={usar:true , minute:min, hour:hour};
		//Ext.dispatch({
		//	controller: 'Home',
		//	action: 'setDuracion',
		//	value: value,				
		//});
	
	
		//ToolbarDemo.views.timer.down('#pause').hide();
		//ToolbarDemo.views.timer.down('#start').show();
        //clearInterval(ToolbarDemo.views.timer.intervalID);
		var time=ToolbarDemo.views.timer.tiempo;
		var mins = parseInt(time/60);
		var hour=parseInt(mins/60);
		var min=mins%60;
		var value={usar:true , minute:min, hour:hour};
		Ext.dispatch({
			controller: 'Home',
			action: 'setDuracion',
			value: value,				
		});	
		
		ToolbarDemo.views.timer.detenerTimer();
		
		Ext.dispatch({
            controller: 'Home',
            action: 'usersForm'
        });
		
	},
	
	onResetAction: function(){
		this.init();
	},
	
	onIntervalAction: function() {        
		var time=ToolbarDemo.views.timer.tiempo;
		var mins = parseInt(time/60);		
		var seg = time%60;
		var hour=parseInt(mins/60);
		var min=mins%60;
		ToolbarDemo.views.timer.setTime(hour,min,seg);
		//ToolbarDemo.views.timer.tiempo=time+1;
		var seg=ToolbarDemo.views.timer.segundo();
		//alert(seg+'seg!!!');
		//alert(time+'time!!!');
		//Ext.dispatch({controller: 'Home',action: 'log', txt:'seg onInterval '+seg});
		//Ext.dispatch({controller: 'Home',action: 'log', txt:'time onInterval '+time});
		//Ext.dispatch({controller: 'Home',action: 'log', txt:'+++++++++++++++++++++++++++++++++++++'});
		
		ToolbarDemo.views.timer.tiempo=seg+time;
    },
	
	segundo:function(){
		
		var time=new Date();
		var mili=time.getTime();
		//alert(mili);
		if(ToolbarDemo.views.timer.milisecond==0){
			ToolbarDemo.views.timer.milisecond=mili-1000;
		}
		var seg=(mili-ToolbarDemo.views.timer.milisecond)/1000;
		var seg=parseInt(seg);
		//Ext.dispatch({controller: 'Home',action: 'log', txt:'mili '+mili});
		//Ext.dispatch({controller: 'Home',action: 'log', txt:'seg '+seg});
		//Ext.dispatch({controller: 'Home',action: 'log', txt:'timer mili '+ToolbarDemo.views.timer.milisecond});
		if(seg!=0){		
			ToolbarDemo.views.timer.milisecond=mili;
		}
		
		//alert(seg);
		return seg;
	},	
	
	setTime:function(horas,min,seg) {
		ToolbarDemo.views.timer.tiempo=(horas*60+min)*60+seg;
		//if(seg==0){
			//ToolbarDemo.views.timer.milisecond=0;
		//}		
		
		var hour1 = parseInt(horas/10);
		var hour2 = horas%10;
		var min1 = parseInt(min/10);
		var min2 = min%10;		
		var seg1 = parseInt(seg/10);
		var seg2 = seg%10;
		var inicio='';
		var hora=inicio.concat('<span class="number"><hr/>',hour1,hour2,'</span><span class="number"><hr/>',min1,min2,'</span>',seg1,seg2);
		ToolbarDemo.views.timer.down('#reloj').setTitle(hora);
	
	},
});

Ext.reg('ToolbarDemo.views.Timer', ToolbarDemo.views.Timer);
