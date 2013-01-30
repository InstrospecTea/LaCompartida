# Changelog

## Hotfix: 13.0.1

Lunes 28 de enero, 2013.

* **Fixed:** [Corrige el problema del login cuando el identificador es Rut en Chrome/Windows](https://github.com/LemontechSA/ttb/issues/64)
* **Fixed:** [Guarda la moneda correcta cuando se crean los saldos aprovisionados luego de emitir un cobro](https://github.com/LemontechSA/ttb/issues/64)
* **Fixed:** [Modificación a agregar contrato y asunto para recuperar tarifa plana](https://github.com/LemontechSA/ttb/issues/64)
* **Fixed:** [Corrección para el cron que ingresa datos al demo no termine su ejecución](https://github.com/LemontechSA/ttb/issues/64)
* **Fixed:** [Modificación a agregar contrato para definir tarifa trámites](https://github.com/LemontechSA/ttb/issues/64)
* **Fixed:** [Corrige el problema del cron correo que no enviaba copia al administrador](https://github.com/LemontechSA/ttb/issues/64)

## Release: 13.0.0

Jueves 24 de enero, 2013.

* **New:** [Recupera archivo Backup Dynamo](https://github.com/LemontechSA/ttb/issues/58)
* **New:** [Cambios para clientes mexicanos](https://github.com/LemontechSA/ttb/issues/54)
* **New:** [Se agregan los campos giro y lugar de emisión a la factura factura](https://github.com/LemontechSA/ttb/issues/49)
* **New:** [Restablecer Password](https://github.com/LemontechSA/ttb/issues/48)
* **New:** [Muestra la versión de software que está corriendo un ambiente](https://github.com/LemontechSA/ttb/issues/45)
* **New:** [Elimina popup al descargar excel revisar horas](https://github.com/LemontechSA/ttb/issues/43)
* **New:** [filtra correos según dirección válida](https://github.com/LemontechSA/ttb/issues/39)
* **Fixed:** [Traducción a anchor %fecha_con_de% al inglés](https://github.com/LemontechSA/ttb/issues/37)


## Hotfix: 12.1.11

Jueves 24 de enero, 2013.

* **Fixed:** [Diferencias en el código del profesional en los trámites de Cobro entre Archivo Excel y Word](https://github.com/LemontechSA/ttb/pull/59)

## Hotfix: 12.1.10

Martes 22 de enero, 2013.

* **Fixed:** [Permite crear nuevos asuntos (y de pasada nuevos contratos) cuando el cliente tiene autocompletador de asuntos y asunto secundario](https://github.com/LemontechSA/ttb/pull/57)
* **Fixed:** [Corrige la sección cliente de la nota de cobro cuando se utiliza desde la factura](https://github.com/LemontechSA/ttb/pull/57)
* **Fixed:** [Corrige el selector de fecha en el buscador de cobros, paréntesis y selector](https://github.com/LemontechSA/ttb/pull/57)

Para ver más historia de este hotfix, [ver aquí](https://github.com/LemontechSA/ttb/pull/57)

## Hotfix: 12.1.9

Viernes 18 de enero, 2013.

* **Fixed:** [Arregla el hotfix 12.1.7 y corrige el problema de Memcached con acéntos](https://github.com/LemontechSA/ttb/pull/53)

## Hotfix: 12.1.8

Viernes 18 de enero, 2013.

* **Fixed:** [Permite ingresar trabajos con código secundario](https://github.com/LemontechSA/ttb/commit/f1392f48fe40f22d11d4916ae47d40daf984eb4b)

## Hotfix: 12.1.7

Viernes 18 de enero, 2013.

* **Fixed:** [Al agregar un pago en /agregar_pago_factura.php y definir un valor en el selector "concepto" (id_concepto) y posteriormente guardar no se postea el valor en la bd](https://github.com/LemontechSA/ttb/pull/52)

## Hotfix: 12.1.6

Miércoles 16 de enero, 2013.

* **Fixed:** [Arregla filtro UsaCobranzaFechaDesde al emitir borradores](https://github.com/LemontechSA/ttb/pull/47)

## Hotfix: 12.1.5

Viernes 11 de enero, 2013.

* **Fixed:** [Excluye cobros en borrador/revision/incobrable del reporte de deudas](https://github.com/LemontechSA/ttb/pull/44)

## Hotfix: 12.1.4

Jueves 10 de enero, 2013.

* **Fixed:** [Elimina archivo admin/index.php que permitía pasar un valor arbitrario por GET para incluir ese archivo.](https://github.com/LemontechSA/ttb/commit/1b84914e831a8d2fce0bea1e1816bd6f210e3c49)

## Hotfix: 12.1.3

Viernes 4 de Enero, 2013.

* **Fixed:** [Corrige diferencia de valores entre la interfaz y la planilla excel](https://github.com/LemontechSA/ttb/pull/42)

## Hotfix: 12.1.2

Miércoles 2 de Enero, 2013.

* **Fixed:** [Corrige el reporte de factura para que sume por moneda y considere el número de factura como número](https://github.com/LemontechSA/ttb/pull/41)

## Hotfix: 12.1.1

Jueves 27 de Diciembre, 2012.

* **Fixed:** [Elimina las funciones de la clase Conf que se obtienen desde la BD](https://github.com/LemontechSA/ttb/pull/40)

## Release: 12.1.0

Miércoles 26 de Diciembre, 2012.

* **New:** [Nuevo sistema de Avisos de actualización](https://github.com/LemontechSA/ttb/issues/5)
* **New:** [Utilización de cache para Conf::GetConf()](https://github.com/LemontechSA/ttb/issues/10)
* **New:** [Autoloader de clases en el sistema](https://github.com/LemontechSA/ttb/issues/13)
* **New:** [Utilización de miconf.php para desarrollo local sin adddb.php](https://github.com/LemontechSA/ttb/issues/15)
* **New:** [Nuevo sistema de deploy automático con notificaciones al equipo](https://github.com/LemontechSA/ttb/issues/17) y #25
* **Fixed:** [Corrección de impresión con Mediaprint CSS para reporte de saldo](https://github.com/LemontechSA/ttb/issues/19)
* **Fixed:** [Limpieza a carpeta admin](https://github.com/LemontechSA/ttb/issues/24)
* **Fixed:** [Limpieza a Encabezados y pie de página para cartas de cobro](https://github.com/LemontechSA/ttb/issues/28)

## Hotfix: 12.0.3

Viernes 21 de Diciembre, 2012.

* **Fixed:** [Corrige el problema al descargar el Excel de Asuntos](https://github.com/LemontechSA/ttb/pull/35)

## Hotfix: 12.0.2

Miércoles 12 de Diciembre, 2012.

* **Fixed:** [Al emitir una factura y presionar guardar, sistema muestra alerta que hay Adelantos disponibles para el cliente. Estos adelantos son solo para pagos de gastos y no Honorarios. Realiza la alerta pero de igual forma se emite la Factura sin problemas, no se toman los pagos de los adelantos](https://github.com/LemontechSA/ttb/pull/27)

## Hotfix: 12.0.1

Lunes 3 de Diciembre, 2012.

* **Fixed:** [Correccion para poder pasar un cobro pagado con adelantos a revision y volver a emitir](https://github.com/LemontechSA/ttb/issues/12)
