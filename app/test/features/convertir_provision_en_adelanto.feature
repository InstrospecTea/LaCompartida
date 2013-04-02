# language: es
# encoding: utf-8
 

 # @javascript
 # Escenario: inserto el permiso SADM
 # Cuando me logeo
 # Y visito la pantalla de phpminiadmin
 # Y escribo "INSERT IGNORE INTO prm_permisos (`codigo_permiso` ,`glosa`) VALUES ('SADM', 'Super Admin');" en el campo "textoquery"
 # Y pincho en "Go"
 # Entonces debiera ver "Done. Last inserted id"
 # Cuando escribo "INSERT IGNORE INTO usuario_permiso (`id_usuario`, `codigo_permiso`) VALUES ((SELECT id_usuario FROM usuario where rut = '99511620'), 'SADM');" en el campo "textoquery"
 
 Característica: convierto una provision en adelanto

  Como el usuario admin
  Para activar el plugin de conversion provision-adelanto
  

 @javascript 
  Escenario: activo el plugin y el nuevo modulo de gastos
  Cuando me logeo
  Entonces debiera estar en la pagina de inicio
  Cuando visito la pantalla de configuracion
  Y pincho el link "Configuracion por Lemontech"
  Y activo "NuevoModuloGastos"
  Y pincho el link "Plugins"
  Y activo "convertir_provision_en_adelanto"
  Y pincho el seudoboton "Guardar"
  Y visito la pantalla de gastos
  Entonces debiera ver "Gastos y Provisiones"
  

  @javascript 
  Escenario: filtro provisiones y edito la primera
  Cuando me logeo
  Y visito la pantalla de gastos
  Y elijo "Sólo provisiones" en "egresooingreso"
  Y pincho el seudoboton "Buscar"
  Entonces debiera ver css "td.tablagastos"
  Cuando pincho en el primer "a.editargasto"