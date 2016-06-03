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
* [Pear Fix on OS X](https://github.com/LemontechSA/ttb/wiki/Fix-pear-OS-X)
* [Numbers_Words][3]:

        $ sudo pear install Numbers_Words-0.16.4

 * OLE (0.5)

      $ sudo pear install OLE-0.5

* Spreadsheet Excel writer (unestable version)

        $ sudo pear install Spreadsheet_Excel_Writer-beta

> Recuerda reiniciar Apache cada vez que hagas cambios en la configuración o instalción de nuevos paquetes


* [WKHTMLTOPDF](http://wkhtmltopdf.org/)

  ````
  wget http://downloads.sourceforge.net/project/wkhtmltopdf/0.12.1/wkhtmltox-0.12.1_linux-centos6-amd6$
  sudo yum localinstall -y wkhtmltox-0.12.1_linux-centos6-amd64.rpm
  which wkhtmltopdf
  sudo yum install urw-fonts
  wkhtmltopdf www.google.com google.pdf
  ````

###requisitos adicionales

* PHP 5 CURL sudo apt-get install php5-curl
* CURL		 sudo apt-get install CURL
* PEAR		 sudo apt-get install php-user

###Incluir el framework de lemontech en FW y amazon WSDDKforPHP en backups/AWSSDKforPHP
* https://github.com/LemontechSA/framework, https://github.com/amazonwebservices/aws-sdk-for-php usando:
    git submodule init
    git submodule update

###Deshabilitar Slim Error Handler
* Comentar la variable set_error_handler (linea 186) de la clase Slim.php dentro del framework

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
    error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

* [Probar configuración][6]

> Si AWSSDK arroja un error de certificado SSL, buscar el archivo ```curl-ca-bundle.crt``` (si se instaló Git usando RailsInstaller, debería estar en C:\RailsInstaller\Git\bin\curl-ca-bundle.crt) y agregar la siguiente línea al php.ini: ```curl.cainfo="(path del archivo)"```

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
        (Es necesario modificar este archivo. Dejar solo el servidor al que se realizará el deployeo)

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

##API

Activar el módulo del RewriteEngine de Apache2.
$ sudo a2enmod rewrite

Reiniciar Apache2 para que tenga en cuenta al módulo recién activado.
$ sudo service apache2 restart

### Generates Api Documentation

* Install [ApiDoc](http://apidocjs.com/)  ```npm install apidoc -g```

```
cd api/
apidoc -o ../apidoc/
```

* open ```../apidoc/index.html``` and enjoy!

##Test
###Pruebas de Integración
  * Duplica el archivo app/test/spec/conf.rb.default con el nombre app/test/spec/conf.rb y configura tu sitio local
  * (Opcional) instalar el [driver de Chrome][11].
  * Ejecutar pruebas

        $ cd app/test
        $ rspec


##HubFlow
Es como [GitFlow][7] pero con más flow. Descargar de [acá][8].

Para Windows, instalar siguiendo [estas instrucciones][9] pero editando el archivo msysgit-install.cmd reemplazando "git-flow" por "git-hf" y "gitflow" por "hubflow".

#Configuracion MySQL

En OSX la configuracion es estricta, por eso debe ajustarse para ser identica a la de produccion

```sql
SET @@global.sql_mode= '';
```

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
[11]: https://github.com/LemontechSA/ttb/wiki/Correr-Capybara-con-Chrome
