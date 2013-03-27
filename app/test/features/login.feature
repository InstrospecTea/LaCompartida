Feature: comprobar el acceso autenticado
 
  Como el usuario admin
  Para comprobar que realmente pide el user y password

@javascript  
Scenario: Entro al sitio de pruebas
  When I am on la pagina de login
  Then I should see css "input[name='rut']"
  Then I should see css "input[name='password']"


@javascript  
Scenario: Intento ingresar con credenciales falsas
  When I am on la pagina de login
  And I fill in "199511620" for "rut"
  And I fill in "admin.asdwsx" for "password"
  And I press "Entrar"
  Then I should see "RUT o password inválidos"

  @javascript  
Scenario: Intento ingresar con credenciales correctas
  When I am on la pagina de login
  And I fill in "99511620" for "rut"
  And I fill in "admin.asdwsx" for "password"
  And I press "Entrar"
  Then I should be on la pagina de inicio