

The Time Billing
================================

The Time Billing - Time Tracking


###Prerequisitos del sistema
* Apache web server con PHP habilitado [(MacOSX Users)][1]
* Mysql
* Pear [(MacOSX Users)][2]

###Configuraciones personalizadas
* Apache charset encoding:

        AddDefaultCharset ISO-8859-1
        AddCharset ISO-8859-1 .iso8859-1 .latin1

* PHP Configurations

        register_globals = On
        error_reporting = E_COMPILE_ERROR|E_ERROR|E_CORE_ERROR (or E_ALL & ~E_NOTICE)
        short_open_tag = On
        default_charset = "iso-8859-1"

* MySQL Configurations

        [mysqld]
        character-set-server = latin1
        character-set-client = latin1

###Librerías
* [Numbers_Words][3]:

        $ sudo pear install Numbers_Words-0.16.4

* Spreadsheet Excel writer (unestable version)

        $ sudo pear install Spreadsheet_Excel_Writer-beta

> Recuerda reiniciar Apache cada vez que hagas cambios en la configuración o instalción de nuevos paquetes


###Base de datos
* Crear base de datos en servidor localhost con encoding "Latin1"
* Crear un login
* La aplicación no carga un modelo por defecto, por lo tanto, necesitas tener un dump y cargarlo.

###Workspace y setup Proyecto
* Clonar el proyecto desde GitHub

        $ git clone git@github.com:LemontechSA/ttb.git

* Abrir el proyecto con tu editor favorito
* Recuerda que el charset para el editor (Eclipse/[Sublime Text 2][10]/NetBeans) debe ser: "iso-8859-1"
  > En Sublime Text 2 hay que editar el proyecto y agregar las siguientes líneas:

  >       "settings":
  >       [
  >        {
  >         "default_encoding": "Western (ISO 8859-1)"
  >        }
  >       ]

* Duplica el archivo app/miconf.php.default con el nombre app/miconf.php
* Edita el archivo **miconf.php** para configurar
  * dbHost: Servidor de base de datos
  * dbName: Nombre de la base de datos creada anteriormente
  * dbUser: Usuario de inicio de sesión con acceso full a la base de datos **dbName**
  * dbPass: El password del usuario **dbUser**
  * Agrega la siguiente linea para no desplegar ciertos mensajes:

        $ error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

* [Probar configuración][6]

> **Opcional**: Crea el directorio virtual time_tracking en tu apache y apuntalo al directorio del repositorio

> Recuerda reiniciar Apache cada vez que hagas cambios en la configuración y tener el servidor Mysql iniciado


###Tips y Troubleshooting en MacOS X
* Instalar Command Line Tools desde XCode
* Instalar [Autoconf y automake][4]
* Compilar e instalar [xDebug][5]

* **file or directory not found**: Si eres un usuario OSX e instalaste mysql a través de Homebrew, lo más probable es que el socket por defecto de mysql en la configuración de php no corresponda ya que brew lo deja en /tmp/:
  * Para verificar el socket haz phpinfo(), luego en una consola abre mysql y ejecuta el comando STATUS;
  * Busca el socket y reemplaza la ruta en php.ini

* **unknown or incorrect time zone: 'America/Asuncion'**: Timezones en MySql

        $ mysql_tzinfo_to_sql /usr/share/zoneinfo | mysql -u root -p mysql


##Deployment
###Prerequisites
  * Ruby 1.9.3
  * bundler Gem

        $ gem install bundler

  * Install capistrano and dependences

        $ bundle install

  * Copy server definition file

        $ cp config/cap_servers.rb.default config/cap_servers.rb

###Deploy in local machinne

    $ cap local deploy

  With  specific branch (default=develop):

    $ cap -s branch=master local deploy

###Deploy a Feature ([client].thetimebilling.com/time_tracking_feature)

    $ cap feature deploy

  And enter the Feature Branch later

###Deploy to release ([client].thetimebilling.com/time_tracking_release)

    $ cap release deploy

  And enter the Release/Hotfix Branch later

###Deploy to production environment ([client].thetimebilling.co/time_tracking)

    $ cap production deploy

##Test
###Pruebas de Integración
  * Duplica el archivo app/test/spec/conf.rb.default con el nombre app/test/spec/conf.rb y configura tu sitio local
  * Ejecutar pruebas

        $ cd app/test
        $ rspec

##HubFlow
Es como [GitFlow][7] pero con más flow. Descargar de [acá][8].

Para Windows, instalar siguiendo [estas instrucciones][9] pero editando el archivo msysgit-install.cmd reemplazando "git-flow" por "git-hf" y "gitflow" por "hubflow".


[1]: https://gist.github.com/3867988
[2]: https://gist.github.com/3868074
[3]: http://pear.php.net/package/Numbers_Words
[4]: http://www.mattvsworld.com/blog/2010/02/install-the-latest-autoconf-and-automake-on-mac-os-10-6
[5]: http://xdebug.org/wizard.php
[6]: http://localhost/time_tracking
[7]: https://github.com/nvie/gitflow
[8]: https://github.com/datasift/gitflow
[9]: https://github.com/nvie/gitflow/wiki/Windows
[10]: http://www.sublimetext.com/
