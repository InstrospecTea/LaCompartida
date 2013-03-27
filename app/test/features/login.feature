# language: es
# encoding: utf-8
Característica:  comprobar el acceso autenticado
 
  Como el usuario admin
  Para comprobar que realmente pide el user y password

@javascript  
Escenario: Entro al sitio de pruebas
  Cuando visito la pagina de login
  Entonces debiera ver css "input[name='rut']"
  Entonces debiera ver css "input[name='password']"


@javascript  
Escenario: Intento ingresar con credenciales falsas
  Cuando visito la pagina de login
  Y escribo "19951162" en el campo "rut"
  Y escribo "qwerty" en el campo "password"
  Y pincho en "Entrar"
  Entonces debiera ver "RUT o password inválidos"

  @javascript  
  Escenario: Intento ingresar con credenciales correctas
  Cuando visito la pagina de login
  Y pongo usuario y password
  Y pincho en "Entrar"
  Entonces debiera estar en la pagina de inicio