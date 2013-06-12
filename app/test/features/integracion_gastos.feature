# language: es
# encoding: utf-8
Característica: comprobar la integración contable de gastos
 
  Como el usuario de Webservice
  Para comprobar que puedo conectarme al webservice

 @javascript  
  Escenario: agrego un gasto
  Cuando me logeo
  Entonces debiera estar en la pagina de inicio
  Cuando visito la pantalla de gastos
  Entonces debiera ver "Revisar Gastos"
  Cuando pincho en "Agregar gasto"
  Y me cambio al popup
  Y genero un gasto aleatorio
  Entonces debiera ver "Gasto Guardado con éxito"

@soap
Escenario: Tiro un request no autenticado al Webservice
  Cuando me conecto al cliente wsdl
  Entonces debiera devolverme los metodos disponibles

@soap
Escenario: Tiro un request al método de listar gastos con credenciales correctas
    Cuando me conecto al cliente wsdl
	Y envio una peticion ":lista_gastos" con params ("lemontest","lemontest",1356998400)
  	Entonces debiera devolver al menos un gasto
@soap
Escenario: Tiro un request al método de listar gastos con credenciales correctas y tiempo futuro
    Cuando me conecto al cliente wsdl
	Y envio una peticion ":lista_gastos" con params ("lemontest","lemontest",2356998400)
  	Entonces no debiera devolver gastos

@soap
Escenario: Tiro un request al método de listar gastos con credenciales incorrectas
  Cuando me conecto al cliente wsdl
  Entonces debiera tirar un error cuando no recibe parametros
  Y debiera tirar un error cuando recibe parametros incorrectos
