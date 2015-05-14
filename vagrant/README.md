# Entorno de desarrollo TTB Clasic #

#### Vagrant ####

Vagrant nos permite crear y configurar entornos de desarrollo portables y reproducibles. 

#### Instalar Vagrant ####

Antes de usar vagrant con **TTB Clasic** se debe instalar en tú equipo Vagrant 1.6 (o superior) junto
a una version compatible de VirtualBox. Para más detalles de vagrant visitar su <a href="https://docs.vagrantup.com/v2/" target="_blank">documentación</a>

#### Desarrollo de TTB Clasic ####

Clonar el repositorio de <a href="https://github.com/LemontechSA/ttb" target="_blank">TTB Clasic desde GitHub</a> en
tu equipo en un directorio llamado **ttb**.

Una vez descargado el código ejecutar los siguientes comandos de consola (desde el directorio ttb):

```
git hf init
git submodule init
git submodule update
```

Una vez realizado las anteriores tareas, se está en condiciones de usar el entonorno de desarrollo. Para levantar
el entorno de desarrollo de debe ingresar al directorio de vagrant del proyecto TTB Clasic y ejecutar el siguiente comando:

```
vagrant up
```

La primera vez que se ejecute **vagrant up**, Vagrant descargara el **box** (maquína virtual), iniciara
el box y ejecutara todas las tareas de configuración e instalación necesarias para desarrollar TTB Clasic.

## Cómo acceder al entorno desarrollo de TTB Clasic ##

#### Acceso Web ####
Para acceder a TTB Clasic se debe ingresar la URL <a href="http://localhost:8080/ttb" target="_blank">http://localhost:8080/ttb</a>. Las
credenciales del entorno de desarrollo son el usuario **99511620** y la contraseña **pidelaalequipodedesarrollo**.

#### Acceso al box ####

Desde el el directorio **ttb/vagrant** ejecutar el comando **vagrant ssh** para ingresar al box(maquina virtual)

## Cómo utilizar al entorno desarrollo de TTB Clasic ##

#### Base de datos ####

El entorno a creado y cargado una base de datos de ejemplo para el entorno de desarrollo que puedes administrar mediante phpMyAdmin accediendo
a la URL <a href="http://localhost:8080/phpmyadmin" target="_blank">http://localhost:8080/phpmyadmin</a>.

#### Para realizar debug ####

El entorno de desarrollo tiene configurado **Xdebug** que puedes configurar en tu **IDE** con el puerto 9000 y el **idekey** igual a **ide-xdebug**

#### Qué software se instala en el box ####

* Instalación de Apache 2.X
* Instalación de MySQL 5.X
* Instalación de PHP 5.3
* Instalación de phpMyAdmin
* Instalación de composer

#### Qué configuraciones se realizan en el box ####

* Configuración de creación de base de datos con encoding latin1 en MySQL
* Carga de time zone tables en la base de datos "mysql" del MySQL
* Crear usuario de base de datos **admin** para ejecución de **update.php**
* Configuración del charset ISO-8859-1 a usar por Apache2 al servir las paginas web
* Habilitar en **php.ini** los parametros
    * html_errors
    * short_open_tag
    * register_globals
    * Establecer los siguientes parametros en **php.ini**
    * default_charset = "iso-8859-1"
    * error_reporting = E_COMPILE_ERROR|E_ERROR|E_CORE_ERROR
* Xdebug


