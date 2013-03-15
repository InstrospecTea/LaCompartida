# Changelog
## Hotfix: 13.2.10
* **Fixed:** [En vez de subir los backups a un bucket para cada cliente, usa el mismo bucket y luego un subdirectorio. Esto, porque Amazon tiene un límite de 100 buckets y ya lo pasamos](https://github.com/LemontechSA/ttb/pull/125)

## Hotfix: 13.2.9
* **Fixed:** [Corrección en validación de fecha cuando usuarios de cobranza editan trabajos](https://github.com/LemontechSA/ttb/pull/117)

## Hotfix: 13.2.8
* **Fixed:** [Modifica la forma de presentar y aplicar los filtros](https://github.com/LemontechSA/ttb/pull/116)

# Changelog
## Hotfix: 13.2.7
* **Fixed:** [No adivinar una fecha de inicio del cobro cuando no se declara especificamente](https://github.com/LemontechSA/ttb/pull/113)

## Hotfix: 13.2.6
Martes 5 de marzo 2013.
* **Fixed:** [Copiar datos del semestre anterior](https://github.com/LemontechSA/ttb/pull/112)

## Hotfix: 13.2.5
Martes 5 de marzo 2013.
* **Fixed:** [Filtra por código asunto secundario](https://github.com/LemontechSA/ttb/pull/110)

## Hotfix: 13.2.4
Martes 5 de marzo 2013.
* **Fixed:** [Envia mail al administrador al enviar correos si el estudio tiene configurado el MailAdmin](https://github.com/LemontechSA/ttb/pull/109)

## Hotfix: 13.2.3
Martes 5 de marzo 2013.
* **Fixed:** [Corrige los filtros area profesional y categoria enr reportes avanzados](https://github.com/LemontechSA/ttb/pull/108)

## Hotfix: 13.2.2
Lunes 4 de marzo 2013.
* **New:** [Agrega tag de rut, ciudad, comuna y código postal a la carta de cobro](https://github.com/LemontechSA/ttb/pull/106)

## Hotfix: 13.2.1
Lunes 4 de marzo 2013.
* **Fixed:** [Corrige el ingreso de adelantos en el sistema](https://github.com/LemontechSA/ttb/pull/104)

## Release: 13.2.0
Domingo 3 de marzo 2013.
* **New:** [Validación horas en trabajos diarios](https://github.com/LemontechSA/ttb/pull/71)
* **Fixed:** [Uso correcto de código secundario en planilla de asuntos](https://github.com/LemontechSA/ttb/pull/75)
* **New:** [Nuevo cálculo de deuda en base a lo facturado](https://github.com/LemontechSA/ttb/pull/77)
* **Fixed:** [Corrección a la dirección en la carta de Morales y Besa](https://github.com/LemontechSA/ttb/pull/93)
* **New:** [Agrupador de área del trabajo en reportes avanzados](https://github.com/LemontechSA/ttb/pull/94)
* **Fixed:** [Corrección al ingresar documentos de pago sin NuevoModuloFactura](https://github.com/LemontechSA/ttb/pull/96)
* **New:** [Plugins para planillas de factura (desde CPB)](https://github.com/LemontechSA/ttb/pull/97)
* **Fixed:** [Cambio de boton "copiar del cliente" por llenar por defecto datos del contrato al crear asunto](https://github.com/LemontechSA/ttb/pull/98)
* **Fixed:** [Centraliza los filtros para el reporte avanzado, asi evita inconsistencias entre planilla y excel.](https://github.com/LemontechSA/ttb/pull/101)

## Hotfix: 13.1.9
Miercoles 27 de febrero 2013.
* **Fixed** [Agrega hook al inicio de la interfaz factura. Complementario con pull request 76](https://github.com/LemontechSA/ttb/pull/76)

## Hotfix: 13.1.8
Lunes 25 de febrero 2013.
* **Fixed** [Mustra Duración correctamente](https://github.com/LemontechSA/ttb/pull/95)

## Hotfix: 13.1.7
Viernes 22 de febrero 2013.
* **Fixed** [Ocultar botón "Copiar datos de Cliente"](https://github.com/LemontechSA/ttb/pull/92)

## Hotfix: 13.1.6
Viernes 22 de febrero 2013.
* **Fixed** [Correccion filtros en reporte avanzado](https://github.com/LemontechSA/ttb/pull/91)

## Hotfix: 13.1.5
Miércoles 20 de febrero 2013.
* **Fixed** [Hotfix/13.1.5](https://github.com/LemontechSA/ttb/pull/90)

## Hotfix: 13.1.4
Martes 19 de febrero 2013.
* **Fixed** [Comentar drag and drop de trabajos](https://github.com/LemontechSA/ttb/pull/88)

## Hotfix: 13.1.3
Lunes 18 de febrero 2013.
* **Fixed** [Corrige el reporte profesional vs cliente](https://github.com/LemontechSA/ttb/pull/86)

## Hotfix: 13.1.2
Lunes 18 de febrero 2013.
* **Fixed** [Corrige la creación de clientes](https://github.com/LemontechSA/ttb/pull/85)

## Hotfix: 13.1.1
Viernes 15 de febrero 2013.
* **Fixed** [Corrige la descarga masiva de borradores](https://github.com/LemontechSA/ttb/pull/84)

## Release: 13.1.0
Jueves 14 de febrero 2013.
* **New:** [Borradores cobro batch](https://github.com/LemontechSA/ttb/pull/78)
* **New:** [Refactorización y mejoras al Cron](https://github.com/LemontechSA/ttb/pull/55)
* **New:** [Plugins resumen factura y archivo contabilidad cpb](https://github.com/LemontechSA/ttb/pull/76)
* **New:** [Clonar trabajos ctrl drag](https://github.com/LemontechSA/ttb/pull/70)
* **Fixed:** [En la pantalla de asunto, al elegir un cliente se quedaba eternamiente intentando refrescar asunto](https://github.com/LemontechSA/ttb/pull/68)
* **New:** [Al crear un asunto, guarda un registro en la tabla log_db](https://github.com/LemontechSA/ttb/pull/68)
* **New:** [Al editar un asunto, guarda los campos clave (codigo asunto, usuario, cobrable, activo) en la log_db](https://github.com/LemontechSA/ttb/pull/68)
* **New:** [Uso de Slim](https://github.com/LemontechSA/ttb/pull/67)
* **New:** [Plugin convertir provision en adelanto](https://github.com/LemontechSA/ttb/pull/66)
* **Fixed:** [Corrige el problema del login cuando el identificador es Rut en Chrome/Windows](https://github.com/LemontechSA/ttb/pull/64)
* **New:** [Forzar cambio de password por los administradores](https://github.com/LemontechSA/ttb/pull/61)
* **New:** [Permite clonar trabajos usando CTRL+drag](https://github.com/LemontechSA/ttb/pull/32)
* **New:** [Mejoras reporte Avanzado](https://github.com/LemontechSA/ttb/pull/31)


## Hotfix: 13.0.4
MiÃ©rcoles 13 de febrero 2013.
* **Fixed** [Corrige la consulta de seguimiento de cobros](https://www.pivotaltracker.com/projects/286009#!/stories/44387509)

## Hotfix: 13.0.3
Jueves 7 de febrero 2013.
* **Fixed** [Corrige el objeto $contacto (que no existe) por $contrato. Pasaba en 2 lugares que nunca imprimian nada](https://github.com/LemontechSA/ttb/pull/74)
MiÃ©rcoles 6 de febrero 2013.
* **Fixed** [Evita error en IE cuando los abogados no pueden ver la duración cobrable] (http://soporte.thetimebilling.com/tickets/7618?col=22917348&page=1)

## Hotfix: 13.0.2

Viernes 1 de febrero, 2013.
* **Fixed** [Le da ancho al dv](https://github.com/LemontechSA/ttb/commit/6cfa4ad56dc3756c62d3911f3ac67216a14b88f7#index.php)

Jueves 31 de enero, 2013.

* **Fixed:** [En la pantalla de asunto, al elegir un cliente se quedaba eternamiente intentando refrescar asunto](https://github.com/LemontechSA/ttb/issues/69)
* **New:** [Al crear un asunto, guarda un registro en la tabla log_db](https://github.com/LemontechSA/ttb/issues/69)
* **New:** [Al editar un asunto, guarda los campos clave (codigo asunto, usuario, cobrable, activo) en la log_db](https://github.com/LemontechSA/ttb/issues/69)

## Hotfix: 13.0.1

Lunes 28 de enero, 2013.

* **Fixed:** [Corrige el problema del login cuando el identificador es Rut en Chrome/Windows](https://github.com/LemontechSA/ttb/issues/64)
* **Fixed:** [Guarda la moneda correcta cuando se crean los saldos aprovisionados luego de emitir un cobro](https://github.com/LemontechSA/ttb/issues/64)
* **Fixed:** [Modificación a agregar contrato y asunto para recuperar tarifa plana](https://github.com/LemontechSA/ttb/issues/64)
* **Fixed:** [Corrección para el cron que ingresa datos al demo no termine su ejecución](https://github.com/LemontechSA/ttb/issues/64)
* **Fixed:** [Modificación a agregar contrato para definir tarifa trÃ¡mites](https://github.com/LemontechSA/ttb/issues/64)
* **Fixed:** [Corrige el problema del cron correo que no enviaba copia al administrador](https://github.com/LemontechSA/ttb/issues/64)

## Release: 13.0.0

Jueves 24 de enero, 2013.

* **New:** [Recupera archivo Backup Dynamo](https://github.com/LemontechSA/ttb/issues/58)
* **New:** [Cambios para clientes mexicanos](https://github.com/LemontechSA/ttb/issues/54)
* **New:** [Se agregan los campos giro y lugar de emisión a la factura factura](https://github.com/LemontechSA/ttb/issues/49)
* **New:** [Restablecer Password](https://github.com/LemontechSA/ttb/issues/48)
* **New:** [Muestra la versión de software que estÃ¡ corriendo un ambiente](https://github.com/LemontechSA/ttb/issues/45)
* **New:** [Elimina popup al descargar excel revisar horas](https://github.com/LemontechSA/ttb/issues/43)
* **New:** [filtra correos segÃºn dirección vÃ¡lida](https://github.com/LemontechSA/ttb/issues/39)
* **Fixed:** [Traducción a anchor %fecha_con_de% al inglÃ©s](https://github.com/LemontechSA/ttb/issues/37)


## Hotfix: 12.1.11

Jueves 24 de enero, 2013.

* **Fixed:** [Diferencias en el código del profesional en los trÃ¡mites de Cobro entre Archivo Excel y Word](https://github.com/LemontechSA/ttb/pull/59)

## Hotfix: 12.1.10

Martes 22 de enero, 2013.

* **Fixed:** [Permite crear nuevos asuntos (y de pasada nuevos contratos) cuando el cliente tiene autocompletador de asuntos y asunto secundario](https://github.com/LemontechSA/ttb/pull/57)
* **Fixed:** [Corrige la sección cliente de la nota de cobro cuando se utiliza desde la factura](https://github.com/LemontechSA/ttb/pull/57)
* **Fixed:** [Corrige el selector de fecha en el buscador de cobros, parÃ©ntesis y selector](https://github.com/LemontechSA/ttb/pull/57)

Para ver mÃ¡s historia de este hotfix, [ver aquÃ­](https://github.com/LemontechSA/ttb/pull/57)

## Hotfix: 12.1.9

Viernes 18 de enero, 2013.

* **Fixed:** [Arregla el hotfix 12.1.7 y corrige el problema de Memcached con acÃ©ntos](https://github.com/LemontechSA/ttb/pull/53)

## Hotfix: 12.1.8

Viernes 18 de enero, 2013.

* **Fixed:** [Permite ingresar trabajos con código secundario](https://github.com/LemontechSA/ttb/commit/f1392f48fe40f22d11d4916ae47d40daf984eb4b)

## Hotfix: 12.1.7

Viernes 18 de enero, 2013.

* **Fixed:** [Al agregar un pago en /agregar_pago_factura.php y definir un valor en el selector "concepto" (id_concepto) y posteriormente guardar no se postea el valor en la bd](https://github.com/LemontechSA/ttb/pull/52)

## Hotfix: 12.1.6

MiÃ©rcoles 16 de enero, 2013.

* **Fixed:** [Arregla filtro UsaCobranzaFechaDesde al emitir borradores](https://github.com/LemontechSA/ttb/pull/47)

## Hotfix: 12.1.5

Viernes 11 de enero, 2013.

* **Fixed:** [Excluye cobros en borrador/revision/incobrable del reporte de deudas](https://github.com/LemontechSA/ttb/pull/44)

## Hotfix: 12.1.4

Jueves 10 de enero, 2013.

* **Fixed:** [Elimina archivo admin/index.php que permitÃ­a pasar un valor arbitrario por GET para incluir ese archivo.](https://github.com/LemontechSA/ttb/commit/1b84914e831a8d2fce0bea1e1816bd6f210e3c49)

## Hotfix: 12.1.3

Viernes 4 de Enero, 2013.

* **Fixed:** [Corrige diferencia de valores entre la interfaz y la planilla excel](https://github.com/LemontechSA/ttb/pull/42)

## Hotfix: 12.1.2

MiÃ©rcoles 2 de Enero, 2013.

* **Fixed:** [Corrige el reporte de factura para que sume por moneda y considere el nÃºmero de factura como nÃºmero](https://github.com/LemontechSA/ttb/pull/41)

## Hotfix: 12.1.1

Jueves 27 de Diciembre, 2012.

* **Fixed:** [Elimina las funciones de la clase Conf que se obtienen desde la BD](https://github.com/LemontechSA/ttb/pull/40)

## Release: 12.1.0

MiÃ©rcoles 26 de Diciembre, 2012.

* **New:** [Nuevo sistema de Avisos de actualización](https://github.com/LemontechSA/ttb/issues/5)
* **New:** [Utilización de cache para Conf::GetConf()](https://github.com/LemontechSA/ttb/issues/10)
* **New:** [Autoloader de clases en el sistema](https://github.com/LemontechSA/ttb/issues/13)
* **New:** [Utilización de miconf.php para desarrollo local sin adddb.php](https://github.com/LemontechSA/ttb/issues/15)
* **New:** [Nuevo sistema de deploy automÃ¡tico con notificaciones al equipo](https://github.com/LemontechSA/ttb/issues/17) y #25
* **Fixed:** [Corrección de impresión con Mediaprint CSS para reporte de saldo](https://github.com/LemontechSA/ttb/issues/19)
* **Fixed:** [Limpieza a carpeta admin](https://github.com/LemontechSA/ttb/issues/24)
* **Fixed:** [Limpieza a Encabezados y pie de pÃ¡gina para cartas de cobro](https://github.com/LemontechSA/ttb/issues/28)

## Hotfix: 12.0.3

Viernes 21 de Diciembre, 2012.

* **Fixed:** [Corrige el problema al descargar el Excel de Asuntos](https://github.com/LemontechSA/ttb/pull/35)

## Hotfix: 12.0.2

MiÃ©rcoles 12 de Diciembre, 2012.

* **Fixed:** [Al emitir una factura y presionar guardar, sistema muestra alerta que hay Adelantos disponibles para el cliente. Estos adelantos son solo para pagos de gastos y no Honorarios. Realiza la alerta pero de igual forma se emite la Factura sin problemas, no se toman los pagos de los adelantos](https://github.com/LemontechSA/ttb/pull/27)

## Hotfix: 12.0.1

Lunes 3 de Diciembre, 2012.

* **Fixed:** [Correccion para poder pasar un cobro pagado con adelantos a revision y volver a emitir](https://github.com/LemontechSA/ttb/issues/12)
