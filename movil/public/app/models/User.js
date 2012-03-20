ToolbarDemo.models.User = Ext.regModel('User', {
	//idProperty: 'id',
    fields: [{
            name: 'id',
            type: 'int',
        },{
            name: 'index',
            type: 'int',
        },{
            name: 'enviado',
            type: 'boolean',
        },{
            name: 'clientes',
            type: 'string',
        },{
            name: 'tareas',
            type: 'string',
        },{
			name:'codigo_cliente',
			type:'string',
		},{
			name:'codigo_asunto',
			type:'string',
		},{
            name: 'fecha',
            type: 'date',
        },{
            name: 'inicio',
            type: 'string',
        },{
            name: 'fin',
            type: 'string',
        },{
            name: 'duracion',
            type: 'string',
        },{
            name: 'ordenado_por',
            type: 'string',
        },{
            name: 'descripcion',
            type: 'string',
        }
    ],

    validations: [
        {
            type: 'presence',
            name: 'clientes'
        },{
            type: 'presence',
            name: 'tareas'
        },],
		
    proxy: {
        type: 'localstorage',
        id: 'sencha-users'
    }
});


ToolbarDemo.models.Cliente = Ext.regModel('Cliente', {
	//idProperty: 'codigo',
    fields: [{
            name: 'codigo',
            type: 'string',
           },{
            name: 'glosa',
            type: 'string'
		   },{
            name: 'codigo_padre',
            type: 'string'
		   },{
            name: 'id',
            type: 'int',
		}],
	
	proxy: {
        type: 'localstorage',
        id: 'Cliente'
    },	
});
			
ToolbarDemo.models.Asunto = Ext.regModel('Asunto', {
	//idProperty: 'codigo',
    fields: [{
            name: 'codigo',
            type: 'string',
           },{
            name: 'glosa',
            type: 'string'
		   },{
            name: 'codigo_padre',
            type: 'string'
		   },{
            name: 'id',
            type: 'int',
		}],
		
	proxy: {
        type: 'localstorage',
        id: 'Asunto'
    },
});


ToolbarDemo.models.Usuario = Ext.regModel('Usuario', {
	
    fields: [{
            name: 'name',
            type: 'string',
           },{
            name: 'password',
            type: 'string'
		   },{
            name: 'id',
            type: 'int',
   }],
	
	proxy: {
        type: 'localstorage',
        id: 'usuario'
    },	
	
});

ToolbarDemo.models.Log = Ext.regModel('Log', {
	
    fields: [{
				name: 'statusText',
				type: 'string',
			},{
				name: 'responseText',
				type: 'string',
			},{
				name: 'update_id',
				type: 'int',
			},{
				name: 'id',
				type: 'int',
			},{
				name: 'clientes',
				type: 'string',
			},{
				name: 'tareas',
				type: 'string',
			},{
				name: 'fecha',
				type: 'date',
			},{
				name: 'duracion',
				type: 'string',
			},{
				name: 'ordenado_por',
				type: 'string',
			},{
				name: 'descripcion',
				type: 'string',
		}],
	
	
	proxy: {
        type: 'localstorage',
        id: 'log'
    },	
	
});

ToolbarDemo.models.Update = Ext.regModel('Update', {
	
    fields: [{
			name: 'text',
			type: 'string',
			},{
			name: 'OK',
			type: 'int',
			},{
			name: 'ERROR',
			type: 'int',
			},{
			name: 'id',
			type: 'int',
	}],
	
	associations: [
        {type: 'hasMany', model: 'Log', name: 'logs'}
    ],
	
	proxy: {
        type: 'localstorage',
        id: 'update'
	},	
	
});


ToolbarDemo.models.Actualizar = Ext.regModel('Actualizar', {
	
    fields: [{
			name: 'text',
			type: 'string',
			},{
			name: 'statusText',
			type: 'string',
			},{
			name: 'responseText',
			type: 'string',
			},{
			name: 'id',
			type: 'int',
	}],
	
	proxy: {
        type: 'localstorage',
        id: 'actualizar'
	},	
	
});