define({ "api": [  {    "type": "get",    "url": "/agreements/:agreement_id",    "title": "Get agreement data",    "name": "Get_agreement_data",    "version": "2.0.0",    "group": "Agreements",    "description": "<p>Get a list of agreement data</p>",    "header": {      "fields": {        "Header": [          {            "group": "Header",            "type": "String",            "optional": false,            "field": "AUTHTOKEN",            "defaultValue": "136b17e3a34db13c98ec404fa9035796b52cbf8c",            "description": "<p>Login Token</p>"          }        ]      }    },    "parameter": {      "fields": {        "Parameter": [          {            "group": "Parameter",            "type": "Integer",            "optional": false,            "field": "agreement_id",            "description": "<p>The :agreement_id corresponds to an agreement id attribute.</p>"          },          {            "group": "Parameter",            "type": "String",            "optional": false,            "field": "embed",            "description": "<p>(at least one) A list of embed relations</p>"          }        ]      },      "examples": [        {          "title": "Params-Example:",          "content": "?embed=generators\n?embed=generators,clients\n?embed=generators,clients,projects\n?embed=projects",          "type": "json"        }      ]    },    "success": {      "fields": {        "Success 200": [          {            "group": "Success 200",            "type": "Generator",            "optional": false,            "field": "generators",            "description": "<p>If embed=generators is provided, then returns a generator entity</p>"          },          {            "group": "Success 200",            "type": "Client",            "optional": false,            "field": "client",            "description": "<p>If embed=client is provided, then returns a client entity</p>"          },          {            "group": "Success 200",            "type": "Project",            "optional": false,            "field": "projects",            "description": "<p>If embed=projects is provided, then returns a project entity</p>"          }        ]      },      "examples": [        {          "title": "Success-Response:",          "content": "HTTP/1.1 200 OK\n{\n  \"generators\": [\n    {\n      \"id_contrato_generador\": \"1\",\n      \"id_categoria\": \"2\",\n      \"porcentaje_genera\": \"1\",\n      \"area_usuario\": \"Pilot\",\n      \"id_usuario\": \"18\",\n      \"nombre\": \"Bodoque Juan Carlos\",\n      \"nombre_categoria\": \"CQC\"\n    }\n  ],\n  \"client\": [\n    {\n      \"codigo_cliente\": \"001391\",\n      \"glosa_cliente\": \"Abogados y Abogados.\"\n    }\n  ],\n  \"projects\": [\n    {\n      \"codigo_asunto\": \"001391-0003\",\n      \"glosa_asunto\": \"Consulta 007\"\n    }\n  ]\n}",          "type": "json"        }      ]    },    "error": {      "fields": {        "Error 4xx": [          {            "group": "Error 4xx",            "optional": false,            "field": "InvalidAgreementId",            "description": "<p>empty or is not numeric</p>"          },          {            "group": "Error 4xx",            "optional": false,            "field": "InvalidEmbed",            "description": "<p>empty</p>"          }        ]      },      "examples": [        {          "title": "Error-Response:",          "content": "HTTP/1.1 400 Invalid Params\n{\n  \"errors\": [\n    \"code\": \"InvalidAgreementId\",\n    \"message\": \"Invalid agreement ID\"\n  ]\n}",          "type": "json"        },        {          "title": "Error-Response:",          "content": "HTTP/1.1 400 Invalid Params\n{\n  \"errors\": [\n    \"code\": \"InvalidEmbed\",\n    \"message\": \"Invalid embed I need at least one\"\n  ]\n}",          "type": "json"        }      ]    },    "filename": "v2/index.php",    "groupTitle": "Agreements"  },  {    "type": "get",    "url": "/clients",    "title": "Get all clients",    "name": "Get_Clients",    "version": "2.0.0",    "group": "Clients",    "description": "<p>Gets a list of clients</p>",    "header": {      "fields": {        "Header": [          {            "group": "Header",            "type": "String",            "optional": false,            "field": "AUTHTOKEN",            "defaultValue": "136b17e3a34db13c98ec404fa9035796b52cbf8c",            "description": "<p>Login Token</p>"          }        ]      }    },    "parameter": {      "fields": {        "Parameter": [          {            "group": "Parameter",            "type": "String",            "optional": false,            "field": "updated_from",            "description": "<p>updated_from=1462310903 (optional): Returns clients that have been updated after the given timestamp</p>"          },          {            "group": "Parameter",            "type": "String",            "optional": false,            "field": "active",            "description": "<p>active=1 or active=0 (optional): Will only return clientes that have the active attribute set to the value given, the only possible values are 0 or 1. When the parameter is not sent, it won't filter by the active attribute.</p>"          }        ]      },      "examples": [        {          "title": "Params-Example:",          "content": "?updated_from=1462310903&active=1",          "type": "json"        }      ]    },    "success": {      "fields": {        "Success 200": [          {            "group": "Success 200",            "type": "Integer",            "optional": false,            "field": "id",            "description": "<p>Client Id</p>"          },          {            "group": "Success 200",            "type": "String",            "optional": false,            "field": "code",            "description": "<p>Client code</p>"          },          {            "group": "Success 200",            "type": "String",            "optional": false,            "field": "name",            "description": "<p>Name of Client</p>"          },          {            "group": "Success 200",            "type": "Integer",            "optional": false,            "field": "active",            "description": "<p>[0, 1] If client is active</p>"          }        ]      },      "examples": [        {          "title": "Success-Response:",          "content": "HTTP/1.1 200 OK\n[\n  {\n    \"id\": 1,\n    \"code\": \"00001\",\n    \"name\": \"Lemontech S.A.\",\n    \"active\": 1\n  }\n]",          "type": "json"        }      ]    },    "error": {      "fields": {        "Error 4xx": [          {            "group": "Error 4xx",            "optional": false,            "field": "InvalidDate",            "description": "<p>If date provided in updated_from is an invalid timestamp</p>"          }        ]      },      "examples": [        {          "title": "Error-Response:",          "content": "HTTP/1.1 400 Invalid Params\n{\n  \"errors\": [\n    \"code\": \"InvalidDate\",\n    \"message\": \"The date format is incorrect\"\n  ]\n}",          "type": "json"        }      ]    },    "filename": "v2/index.php",    "groupTitle": "Clients"  },  {    "type": "get",    "url": "/clients/:client_id/projects",    "title": "Get Projects of Client",    "name": "Get_Projects",    "version": "2.0.0",    "group": "Clients",    "description": "<p>Gets the list of all projects of a client.</p>",    "header": {      "fields": {        "Header": [          {            "group": "Header",            "type": "String",            "optional": false,            "field": "AUTHTOKEN",            "defaultValue": "136b17e3a34db13c98ec404fa9035796b52cbf8c",            "description": "<p>Login Token</p>"          }        ]      }    },    "parameter": {      "fields": {        "Parameter": [          {            "group": "Parameter",            "type": "String",            "optional": false,            "field": "client_id",            "description": "<p>The :client_id corresponds to a client id attribute.</p>"          }        ]      }    },    "success": {      "fields": {        "Success 200": [          {            "group": "Success 200",            "type": "Integer",            "optional": false,            "field": "id",            "description": "<p>Project Id</p>"          },          {            "group": "Success 200",            "type": "String",            "optional": false,            "field": "code",            "description": "<p>Project code</p>"          },          {            "group": "Success 200",            "type": "String",            "optional": false,            "field": "name",            "description": "<p>Name of Project</p>"          },          {            "group": "Success 200",            "type": "Integer",            "optional": false,            "field": "active",            "description": "<p>[0, 1] If client is active</p>"          },          {            "group": "Success 200",            "type": "Integer",            "optional": false,            "field": "client_id",            "description": "<p>Id of parent client</p>"          },          {            "group": "Success 200",            "type": "Integer",            "optional": false,            "field": "project_area_id",            "description": "<p>Projects' Area</p>"          },          {            "group": "Success 200",            "type": "Integer",            "optional": false,            "field": "project_type_id",            "description": "<p>Projects' Type</p>"          },          {            "group": "Success 200",            "type": "String",            "optional": false,            "field": "language_code",            "description": "<p>Language code of Project</p>"          },          {            "group": "Success 200",            "type": "String",            "optional": false,            "field": "language_name",            "description": "<p>Language name of Project</p>"          },          {            "group": "Success 200",            "type": "String",            "optional": false,            "field": "created_at",            "description": "<p>Creation date</p>"          },          {            "group": "Success 200",            "type": "String",            "optional": false,            "field": "updated_at",            "description": "<p>Date Updated date</p>"          },          {            "group": "Success 200",            "type": "String",            "optional": false,            "field": "currency_code",            "description": "<p>Currency Code</p>"          }        ]      },      "examples": [        {          "title": "Success-Response:",          "content": "HTTP/1.1 200 OK\n[\n {\n    \"id\": 1,\n    \"code\": \"0001-0001\",\n    \"name\": \"Asesorías Generales\",\n    \"active\": 1,\n    \"client_id\": 1,\n    \"project_area_id\": 1,\n    \"project_type_id\": 1,\n    \"language_code\": \"es\",\n    \"language_name\": \"Español\"\n    \"created_at\": \"2014-06-03 11:58:38\",\n    \"updated_at\": \"2014-06-03 11:58:38\",\n    \"currency_code\": \"COLP\"\n  }\n]",          "type": "json"        }      ]    },    "error": {      "fields": {        "Error 4xx": [          {            "group": "Error 4xx",            "optional": false,            "field": "InvalidClientCode",            "description": "<p>If client id is not provided</p>"          },          {            "group": "Error 4xx",            "optional": false,            "field": "ClientDoesntExists",            "description": "<p>If client does not exists</p>"          }        ]      },      "examples": [        {          "title": "Error-Response:",          "content": "HTTP/1.1 400 Invalid Params\n{\n  \"errors\": [\n    \"code\": \"InvalidClientCode\",\n    \"message\": \"The client doesn't exist\"\n  ]\n}",          "type": "json"        }      ]    },    "filename": "v2/index.php",    "groupTitle": "Clients"  },  {    "type": "get",    "url": "/projects/:project_id/payments",    "title": "Get Payments",    "name": "Get_Payments",    "version": "2.0.0",    "group": "Projects",    "description": "<p>Gets a list of all payments of one project</p>",    "header": {      "fields": {        "Header": [          {            "group": "Header",            "type": "String",            "optional": false,            "field": "AUTHTOKEN",            "defaultValue": "136b17e3a34db13c98ec404fa9035796b52cbf8c",            "description": "<p>Login Token</p>"          }        ]      }    },    "parameter": {      "fields": {        "Parameter": [          {            "group": "Parameter",            "type": "String",            "optional": false,            "field": "project_id",            "description": "<p>Corresponds to a project id attribute.</p>"          }        ]      }    },    "success": {      "fields": {        "Success 200": [          {            "group": "Success 200",            "type": "Integer",            "optional": false,            "field": "id",            "description": "<p>Payment Id</p>"          },          {            "group": "Success 200",            "type": "String",            "optional": false,            "field": "project_code",            "description": "<p>Project code</p>"          },          {            "group": "Success 200",            "type": "String",            "optional": false,            "field": "date",            "description": "<p>Date of payment</p>"          },          {            "group": "Success 200",            "type": "Numeric",            "optional": false,            "field": "amount",            "description": "<p>Amount of payment</p>"          },          {            "group": "Success 200",            "type": "String",            "optional": false,            "field": "name",            "description": "<p>Name of payment</p>"          },          {            "group": "Success 200",            "type": "Integer",            "optional": false,            "field": "project_id",            "description": "<p>Project Id</p>"          }        ]      },      "examples": [        {          "title": "Success-Response:",          "content": "HTTP/1.1 200 OK\n[\n  {\n    \"project_code\": \"000134-0001\",\n    \"id\": \"238\",\n    \"date\": \"2015-02-09 11:14:42\",\n    \"amount\": \"1194414\",\n    \"name\": \"Pago de Factura # 001-10186\",\n    \"project_id\": \"205\"\n  },\n  {\n    \"project_code\": \"000134-0001\",\n    \"id\": \"1045\",\n    \"date\": \"2015-12-17 18:14:01\",\n    \"amount\": \"11666474\",\n    \"name\": \"Pago de Factura # 001-11152, 001-11154, 001-11155, 001-11156, 001-11157\",\n    \"project_id\": \"205\"\n  }\n]",          "type": "json"        }      ]    },    "filename": "v2/index.php",    "groupTitle": "Projects"  },  {    "type": "get",    "url": "/projects",    "title": "Get All Projects",    "name": "Get_Projects",    "version": "2.0.1",    "group": "Projects",    "description": "<p>Gets a list of all projects.</p>",    "header": {      "fields": {        "Header": [          {            "group": "Header",            "type": "String",            "optional": false,            "field": "AUTHTOKEN",            "defaultValue": "136b17e3a34db13c98ec404fa9035796b52cbf8c",            "description": "<p>Login Token</p>"          }        ]      }    },    "parameter": {      "fields": {        "Parameter": [          {            "group": "Parameter",            "type": "String",            "optional": false,            "field": "client_id",            "description": "<p>Corresponds to a client id attribute.</p>"          },          {            "group": "Parameter",            "type": "String",            "optional": false,            "field": "updated_from",            "description": "<p>updated_from=1462310903 (optional): Returns projects that have been updated after the given timestamp</p>"          },          {            "group": "Parameter",            "type": "String",            "optional": false,            "field": "active",            "description": "<p>active=1 or active=0 (optional): Will only return projects that have the active attribute set to the value given, the only possible values are 0 or 1. When the parameter is not sent, it won't filter by the active attribute.</p>"          }        ]      }    },    "success": {      "fields": {        "Success 200": [          {            "group": "Success 200",            "type": "Integer",            "optional": false,            "field": "id",            "description": "<p>Project Id</p>"          },          {            "group": "Success 200",            "type": "String",            "optional": false,            "field": "code",            "description": "<p>Project code</p>"          },          {            "group": "Success 200",            "type": "String",            "optional": false,            "field": "name",            "description": "<p>Name of Project</p>"          },          {            "group": "Success 200",            "type": "Integer",            "optional": false,            "field": "active",            "description": "<p>[0, 1] If client is active</p>"          },          {            "group": "Success 200",            "type": "Integer",            "optional": false,            "field": "client_id",            "description": "<p>Id of parent client</p>"          },          {            "group": "Success 200",            "type": "Integer",            "optional": false,            "field": "project_area_id",            "description": "<p>Projects' Area</p>"          },          {            "group": "Success 200",            "type": "Integer",            "optional": false,            "field": "project_type_id",            "description": "<p>Projects' Type</p>"          },          {            "group": "Success 200",            "type": "Integer",            "optional": false,            "field": "agreement_id",            "description": "<p>Agreements' Id</p>"          },          {            "group": "Success 200",            "type": "String",            "optional": false,            "field": "language_code",            "description": "<p>Language code of Project</p>"          },          {            "group": "Success 200",            "type": "String",            "optional": false,            "field": "language_name",            "description": "<p>Language name of Project</p>"          },          {            "group": "Success 200",            "type": "String",            "optional": false,            "field": "created_at",            "description": "<p>Creation date</p>"          },          {            "group": "Success 200",            "type": "String",            "optional": false,            "field": "updated_at",            "description": "<p>Date Updated date</p>"          },          {            "group": "Success 200",            "type": "String",            "optional": false,            "field": "currency_code",            "description": "<p>Currency Code</p>"          }        ]      },      "examples": [        {          "title": "Success-Response:",          "content": "HTTP/1.1 200 OK\n[\n {\n    \"id\": 1,\n    \"code\": \"0001-0001\",\n    \"name\": \"Asesorías Generales\",\n    \"active\": 1,\n    \"client_id\": 1,\n    \"project_area_id\": 1,\n    \"project_type_id\": 1,\n    \"agreement_id\": 1,\n    \"language_code\": \"es\",\n    \"language_name\": \"Español\",\n    \"created_at\": \"2014-06-03 11:58:38\",\n    \"updated_at\": \"2014-06-03 11:58:38\",\n    \"currency_code\": \"COLP\"\n  }\n]",          "type": "json"        }      ]    },    "error": {      "fields": {        "Error 4xx": [          {            "group": "Error 4xx",            "optional": false,            "field": "InvalidClientCode",            "description": "<p>If client id is not provided</p>"          },          {            "group": "Error 4xx",            "optional": false,            "field": "ClientDoesntExists",            "description": "<p>If client does not exists</p>"          }        ]      },      "examples": [        {          "title": "Error-Response:",          "content": "HTTP/1.1 400 Invalid Params\n{\n  \"errors\": [\n    \"code\": \"InvalidClientCode\",\n    \"message\": \"The client doesn't exist\"\n  ]\n}",          "type": "json"        }      ]    },    "filename": "v2/index.php",    "groupTitle": "Projects"  },  {    "type": "post",    "url": "/login",    "title": "User Login",    "name": "Login",    "version": "2.0.0",    "group": "Session",    "description": "<p>Authenticates a users credentials and returns an AUTHENTICATION TOKEN</p>",    "parameter": {      "fields": {        "Parameter": [          {            "group": "Parameter",            "type": "String",            "optional": false,            "field": "user",            "description": "<p>Identification (Ex: 99511620-0)</p>"          },          {            "group": "Parameter",            "type": "String",            "optional": false,            "field": "password",            "description": "<p>Password of user</p>"          },          {            "group": "Parameter",            "type": "String",            "optional": false,            "field": "app_key",            "description": "<p>A key provided for the application that consumes the API.</p>"          }        ]      },      "examples": [        {          "title": "Params-Example:",          "content": "{\n  \"user\": \"99511620-0\",\n  \"password\": \"blabla\",\n  \"app_key\": \"ttb-mobile\"\n}",          "type": "json"        }      ]    },    "success": {      "fields": {        "Success 200": [          {            "group": "Success 200",            "type": "String",            "optional": false,            "field": "auth_token",            "description": "<p>Token for future authorization</p>"          },          {            "group": "Success 200",            "type": "String",            "optional": false,            "field": "user_id",            "description": "<p>The id of the user logged in</p>"          }        ]      },      "examples": [        {          "title": "Success-Response:",          "content": "HTTP/1.1 200 OK\n{\n  \"auth_token\": \"136b17e3a34db13c98ec404fa9035796b52cbf8c\",\n  \"user_id\": \"1\"\n}",          "type": "json"        }      ]    },    "error": {      "fields": {        "Error 4xx": [          {            "group": "Error 4xx",            "optional": false,            "field": "InvalidUserData",            "description": "<p>user is not provided</p>"          },          {            "group": "Error 4xx",            "optional": false,            "field": "InvalidPasswordData",            "description": "<p>password is not provided</p>"          },          {            "group": "Error 4xx",            "optional": false,            "field": "InvalidAppKey",            "description": "<p>app_key is not provided</p>"          },          {            "group": "Error 4xx",            "optional": false,            "field": "UserDoesntExist",            "description": "<p>user does not exists</p>"          },          {            "group": "Error 4xx",            "optional": false,            "field": "UnexpectedSave",            "description": "<p>an error ocurred saving token data</p>"          }        ]      },      "examples": [        {          "title": "Error-Response:",          "content": "HTTP/1.1 400 Invalid Params\n{\n  \"errors\": [\n    \"code\": \"InvalidUserData\",\n    \"message\": \"You must provide an user identifier\"\n  ]\n}",          "type": "json"        }      ]    },    "filename": "v2/index.php",    "groupTitle": "Session"  },  {    "type": "get",    "url": "/users/:id/time_entries",    "title": "Get TimeEntries",    "name": "Get_TimeEntries",    "version": "2.0.0",    "group": "TimeEntries",    "description": "<p>Get a list of time entries (works, jobs, etc..whatever you want)</p>",    "header": {      "fields": {        "Header": [          {            "group": "Header",            "type": "String",            "optional": false,            "field": "AUTHTOKEN",            "defaultValue": "136b17e3a34db13c98ec404fa9035796b52cbf8c",            "description": "<p>Login Token</p>"          }        ]      }    },    "parameter": {      "fields": {        "Parameter": [          {            "group": "Parameter",            "type": "String",            "optional": false,            "field": "id",            "description": "<p>Id of an user (view Login Response)</p>"          },          {            "group": "Parameter",            "type": "String",            "optional": false,            "field": "date",            "description": "<p>(timestamp, optional) Returns time entries betweeen monday to sunday of date</p>"          },          {            "group": "Parameter",            "type": "String",            "optional": false,            "field": "string_date",            "description": "<p>(date YYYY-MM-DD, optional) Returns time entries betweeen monday to sunday of string_date</p>"          },          {            "group": "Parameter",            "type": "String",            "optional": false,            "field": "embed",            "description": "<p>(optional) A list of embed relations</p>"          }        ]      },      "examples": [        {          "title": "Params-Example:",          "content": "?string_date=2016-05-10&embed=project",          "type": "json"        }      ]    },    "success": {      "fields": {        "Success 200": [          {            "group": "Success 200",            "type": "Integer",            "optional": false,            "field": "id",            "description": "<p>Id of a Time Entry</p>"          },          {            "group": "Success 200",            "type": "Float",            "optional": false,            "field": "date",            "description": "<p>Date of work</p>"          },          {            "group": "Success 200",            "type": "String",            "optional": false,            "field": "string_date",            "description": "<p>Date of work in string format</p>"          },          {            "group": "Success 200",            "type": "Float",            "optional": false,            "field": "created_at",            "description": "<p>Creation date of time entry</p>"          },          {            "group": "Success 200",            "type": "String",            "optional": false,            "field": "string_created_at",            "description": "<p>Creation date of time entry in string format</p>"          },          {            "group": "Success 200",            "type": "Float",            "optional": false,            "field": "duration",            "description": "<p>Duration in Minutes</p>"          },          {            "group": "Success 200",            "type": "String",            "optional": false,            "field": "description",            "description": "<p>Notes of a time entry</p>"          },          {            "group": "Success 200",            "type": "Integer",            "optional": false,            "field": "user_id",            "description": "<p>user that did work</p>"          },          {            "group": "Success 200",            "type": "Integer",            "optional": false,            "field": "billable",            "description": "<p>[1, 0] determine if a time entry is billable</p>"          },          {            "group": "Success 200",            "type": "Integer",            "optional": false,            "field": "visible",            "description": "<p>[1, 0] determine if a time entry is visible and not billable</p>"          },          {            "group": "Success 200",            "type": "Integer",            "optional": false,            "field": "read_only",            "description": "<p>[1, 0] determine if a time entry is locked by an invoice or in review process</p>"          },          {            "group": "Success 200",            "type": "Integer",            "optional": false,            "field": "client_id",            "description": "<p>Id of client</p>"          },          {            "group": "Success 200",            "type": "Integer",            "optional": false,            "field": "project_id",            "description": "<p>Id of project</p>"          },          {            "group": "Success 200",            "type": "Integer",            "optional": false,            "field": "activity_id",            "description": "<p>Id of activity</p>"          },          {            "group": "Success 200",            "type": "Integer",            "optional": false,            "field": "area_id",            "description": "<p>Id of Area</p>"          },          {            "group": "Success 200",            "type": "Integer",            "optional": false,            "field": "task_id",            "description": "<p>Id of Task</p>"          },          {            "group": "Success 200",            "type": "String",            "optional": false,            "field": "requester",            "description": "<p>Name of requester</p>"          },          {            "group": "Success 200",            "type": "Project",            "optional": false,            "field": "project",            "description": "<p>If embed=project is provided, then returns a project entity</p>"          }        ]      },      "examples": [        {          "title": "Success-Response:",          "content": "HTTP/1.1 200 OK\n[\n  {\n    \"id\": 1,\n    \"date\": \"121283477\",\n    \"string_date\": \"2015-04-10\",\n    \"created_at\": \"121283477\",\n    \"string_created_at\": \"2015-04-10\",\n    \"duration\": 120,\n    \"description\": \"writing a letter\",\n    \"user_id\": 1,\n    \"billable\": 1,\n    \"visible\": 1,\n    \"read_only\": 0,\n    \"client_id\": 1,\n    \"project_id\": 2,\n    \"activity_id\": 3,\n    \"area_id\": 4,\n    \"task_id\": 5,\n    \"requester\": \"Mario Lavandero\",\n    \"project\": {\n      \"id\": 2,\n      \"code\": \"0001-0002\",\n      \"name\": \"Asesorías Financieras\",\n      \"active\": 1,\n      \"client_id\": 1,\n      \"project_area_id\": 1,\n      \"project_type_id\": 1,\n      \"language_code\": \"es\",\n      \"language_name\": \"Español\"\n    }\n  }\n]",          "type": "json"        }      ]    },    "error": {      "fields": {        "Error 4xx": [          {            "group": "Error 4xx",            "optional": false,            "field": "InvalidUserID",            "description": "<p>If param :id is empty</p>"          },          {            "group": "Error 4xx",            "optional": false,            "field": "UserDoesntExist",            "description": "<p>If User does not exsists</p>"          }        ]      },      "examples": [        {          "title": "Error-Response:",          "content": "HTTP/1.1 400 Invalid Params\n{\n  \"errors\": [\n    \"code\": \"InvalidDate\",\n    \"message\": \"The date format is incorrect\"\n  ]\n}",          "type": "json"        }      ]    },    "filename": "v2/index.php",    "groupTitle": "TimeEntries"  }] });
