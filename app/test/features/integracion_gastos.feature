Feature: comprobar la integración contable de gastos
 
  Como el usuario de Webservice
  Para comprobar que puedo conectarme al webservice

 

Scenario: Tiro un request no autenticado al Webservice
  When me conecto al cliente wsdl
  Then debiera devolverme los metodos disponibles


Scenario: Tiro un request al método de listar gastos con credenciales correctas
    When me conecto al cliente wsdl
	And envio una peticion ":lista_gastos" con params ("lemontest","lemontest",1356998400)
  	Then debiera devolver al menos un gasto

Scenario: Tiro un request al método de listar gastos con credenciales correctas y tiempo futuro
    When me conecto al cliente wsdl
	And envio una peticion ":lista_gastos" con params ("lemontest","lemontest",2356998400)
  	Then no debiera devolver gastos


Scenario: Tiro un request al método de listar gastos con credenciales incorrectas
  When me conecto al cliente wsdl
  Then debiera tirar un error cuando no recibe parametros
  And debiera tirar un error cuando recibe parametros incorrectos
