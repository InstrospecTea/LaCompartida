The-Time-Billing
================

The Time Billing - Time Tracking


###Prerequisitos del sistema
- Apache web server con PHP habilitado [(MacOSX Users)][1]
- Mysql
- Pear [(MacOSX Users)][2]

###Configuraciones personalizadas
- Apache charset encoding:

      AddDefaultCharset ISO-8859-1
    AddCharset ISO-8859-1  .iso8859-1  .latin1

- Php Configurations

      register_globals=On
    error\_reporting = E\_COMPILE\_ERROR|E\_ERROR|E\_CORE_ERROR  (or E\_ALL & ~E\_NOTICE)
    short\_open\_tag=On
    default\_charset = "iso-8859-1"

- MySQL Configurations

      [mysqld]
      character-set-server = latin1
    character-set-client = latin1

###Librerías
* [Numbers_Words][3]:

      $ sudo pear install Numbers_Words-0.16.4

* Spreadsheet Excel writer (unestable version)

      $ sudo pear install Spreadsheet_Excel_Writer-beta


###Base de datos
* Crear base de datos en servidor localhost con encoding "Latin1"
* Crear un login
* La aplicación no carga un modelo por defecto, por lo tanto, necesitas tener un dump y cargarlo.

###Workspace y setup Proyecto
- Clonar el proyecto
- Clonar en otro directorio [Amazon WS SDK][awssdk]
- Inicializar [HubFlow](#hubflow)
      $ git hf init
- Abrir el proyecto con tu editor favorito
- Recuerda que el charset para el editor (Eclipse/SublimeText/NetBeans) debe ser: "iso-8859-1"
- Duplica el archivo app/miconf.php.default con el nombre app/miconf.php
- Duplica el archivo version.php.default con el nombre version.php
- Edita el archivo **miconf.php** para configurar
  * DBHOST: Servidor de base de datos
  * DBNAME: Nombre de la base de datos creada anteriormente
  * DBUSER: Usuario de inicio de sesión con acceso full a la base de datos **DBNAME**
  * DBPASS: El password del usuario **DBUSER**
  * CACHEDIR: Path del directorio donde guardar el cache
  * Incluir AWSSDK usando el directorio en que se clonó
- Crea el directorio virtual time_tracking en tu apache y apuntalo al directorio trunk dentro de tu repositorio
- Recuerda reiniciar Apache cada vez que hagas cambios en la configuración y tener el servidor Mysql iniciado
- [Test][6]
- Si AWSSDK arroja un error de certificado SSL, editar el archivo (path de awssdk)/lib/requestcore/requestcore.class.php y en la función send_request (línea 844) editar el siguiente código:

		$curl_handle = $this->prep_request();
		curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, false);
		$this->response = curl_exec($curl_handle);



###Tips y Troubleshooting en MacOS X
- Instalar Command Line Tools desde XCode
- Instalar [Autoconf y automake][4]
- Compilar e instalar [xDebug][5]

- **file or directory not found**: Si eres un usuario OSX e instalaste mysql a través de Homebrew, lo más probable es que el socket por defecto de mysql en la configuración de php no corresponda ya que brew lo deja en /tmp/:

      Para verificar el socket haz phpinfo(), luego en una consola abre mysql y ejecuta el comando STATUS;
    Busca el socket y reemplaza la ruta en php.ini

- **unknown or incorrect time zone: 'America/Asuncion'**: Timezones en MySql

      $ mysql\_tzinfo\_to_sql /usr/share/zoneinfo | mysql -u root -p mysql


##Deployment
###Prerequisites
  * Ruby 1.9.3
  * bundler Gem

        $ gem install bundler
  * Install capistrano and dependences

        $ bundle install

###Deploy in local machinne
    $ cap develop deploy

  With  specific branch (default=develop):

    $ cap -s branch=master develop deploy

###Deploy to staging (staging.thetimebilling.com/time_tracking)
    $ cap staging deploy

  With  specific branch (default=develop):

    $ cap -s branch=master staging deploy

###Deploy to release ([client].thetimebilling.com/time_tracking_release)
    $ cap release deploy

  With  specific branch (default=master):

    $ cap -s branch=release/feat2010 release deploy

    $ cap -s branch=hotfix/fix2011 release deploy

###Deploy to production environment ([client].thetimebilling.com/time_tracking)
    $ cap production deploy

  Only can deploy the master branch


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
[awssdk]: https://github.com/amazonwebservices/aws-sdk-for-php