# language: es
# encoding: utf-8
Característica: convierto una provision en adelanto
 
  Como el usuario admin
  Para activar el plugin de conversion provision-adelanto
  
  @javascript
  Escenario: inserto el permiso SADM
  Cuando me logeo
  Y visito la pantalla de phpminiadmin
  Y escribo "INSERT IGNORE INTO prm_permisos (`codigo_permiso` ,`glosa`) VALUES ('SADM', 'Super Admin');" en el campo "textoquery"
  Y pincho en "Go"
  Entonces debiera ver "Done. Last inserted id"
  Cuando escribo "INSERT IGNORE INTO usuario_permiso (`id_usuario`, `codigo_permiso`) VALUES ((SELECT id_usuario FROM usuario where rut = '99511620'), 'SADM');" en el campo "textoquery"
  Y pincho en "Go"
  Entonces debiera ver "Done. Last inserted id"


 @javascript  
  Escenario: agrego un gasto
  Cuando me logeo
  Entonces debiera estar en la pagina de inicio
  Cuando visito la pantalla de configuracion
  Y pincho en "Plugins"
  Y activo boton "convertir_provision_en_adelanto"
  Y visito la pantalla de gastos
  Entonces debiera ver "Gastos y Provisiones"

 