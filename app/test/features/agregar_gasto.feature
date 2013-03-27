# language: es
# encoding: utf-8
Característica: agrego un gasto
 
  Como el usuario admin
  Para comprobar que puedo agregar un gasto

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