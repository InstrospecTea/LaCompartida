ToolbarDemo.views.ErrorField = Ext.extend(Ext.Component, {

	// privat
	initComponent: function() {
		config = {
			xtype: 'component',
			id: this.fieldname + 'ErrorField',
			cls: 'errorfield',
			tpl: new Ext.XTemplate( 
						'<tpl if="values.length &gt; 0">',
					'	<ul>',
					'		<tpl for=".">',
					'			<li style="font-size:65%; color:red">&nbsp{[this.error(values.field,values.message)]}</li>',
					'		</tpl>',
					'	</ul>',
					'</tpl>',
					{// XTemplate configuration:
						compiled: true,
						// member functions:
						error: function(field,message){
							var error;
							if(field=='clientes'){
								error='Seleccione un cliente';
							}else if(field=='tareas'){
								error='Seleccione una tarea';
							}						
							//a=values;
							return error;
						},
					}
				),				
			hidden: true
		};

		Ext.apply(this, config);
		ToolbarDemo.views.ErrorField.superclass.initComponent.call(this);
	},

});
Ext.reg('ToolbarDemo.views.ErrorField', ToolbarDemo.views.ErrorField);
