Feature: comprobar que puedo ingresar gastos
 
  Como el usuario admin
  Para comprobar que puedo ingresar un gasto


  @javascript  
  Scenario: Voy a la pantalla de gastos
  When I am on la pagina de login
  And I fill in "99511620" for "rut"
  And I fill in "admin.asdwsx" for "password"
  And I press "Entrar"
  And I go to la pantalla de gastos
  Then I should see "Revisar Gastos"


  When I click on "Agregar gasto"
  And me cambio al popup
  And I fill in "0025-0002" for "campo_codigo_asunto"
  And I fill in "150" for "monto"
  And I fill in "Gasto generado por cucumber" for "descripcion"
  And I click on "Guardar"
  Then I should see "Gasto Guardado con éxito"