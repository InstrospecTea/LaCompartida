# The Time Billing

## Prerequisitos del sistema

Se recomienda instalar [brew](http://brew.sh) para entorno de desarrollo OSX

```sh
/usr/bin/ruby -e "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/master/install)"
```

* Apache 2.x
* PHP 5.5.x o mayor
    * OSX

        ```sh
        brew tap homebrew/php
        brew tap homebrew/dupes
        brew tap homebrew/versions
        brew tap homebrew/apache
        brew update
        brew install php56 --with-apache
        brew uninstall httpd24
        ```

* MySQL 5.6.x o mayor
    * OSX

        ```sh
        brew install mysql
        ```
* Git

## Configuraciones personalizadas

* **Git**

    > Agregar llave

```sh
ssh-add ~/.ssh/id_rsa
```

* **Apache (httpd.conf)**

    > habilitar `php5_module` y `rewrite_module` y configurar encoding

```apacheconf
#MÓDULOS

LoadModule rewrite_module libexec/apache2/mod_rewrite.so
LoadModule php5_module libexec/apache2/libphp5.so

#CHARSET ENCODING

AddDefaultCharset ISO-8859-1
AddCharset ISO-8859-1 .iso8859-1 .latin1
```

* **PHP (php.ini)**

```apacheconf
default_charset="iso-8859-1"
```

* **MySQL (my.cnf)**

```apacheconf
[mysqld]
character-set-server=latin1
max_allowed_packet=64M
sql_mode=NO_ENGINE_SUBSTITUTION
```

### Bibliotecas (opcional)

* [WKHTMLTOPDF](http://wkhtmltopdf.org/)

**CENTOS**

```sh
wget http://downloads.sourceforge.net/project/wkhtmltopdf/0.12.1/wkhtmltox-0.12.1_linux-centos6-amd6
sudo yum localinstall -y wkhtmltox-0.12.1_linux-centos6-amd64.rpm
which wkhtmltopdf
sudo yum install urw-fonts

wkhtmltopdf www.google.com google.pdf
```

### Base de datos

* Crear base de datos en servidor localhost con encoding "Latin1"
* La aplicación no carga un modelo por defecto, por lo tanto, necesitas tener un dump y cargarlo.
* Crear usuario `admin`

```sql
CREATE USER 'admin'@'localhost' IDENTIFIED BY 'admin1awdx';
GRANT ALL PRIVILEGES ON *.* TO admin@localhost;
```

### Workspace y setup Proyecto
* **Instalar HubFlow (gitfow)**

```sh
git clone git@github.com:datasift/gitflow.git
cd gitflow
sudo ./install.sh
```

* Clonar el proyecto desde GitHub

```sh
git clone git@github.com:LemontechSA/ttb.git
git hf init
gh update
```

* Abrir el proyecto con tu editor favorito
* Recuerda que el charset para el editor (Eclipse/[Sublime Text 2][10]/NetBeans) debe ser: "iso-8859-1" (se utiliza el plugin `editorconfig`)
* Duplica el archivo app/miconf.php.default con el nombre app/miconf.php
* Edita el archivo **miconf.php** para configurar
    * dbHost: Servidor de base de datos
        * Para OSX, el host debe ser `127.0.0.1`
    * dbName: Nombre de la base de datos creada anteriormente
    * dbUser: Usuario de inicio de sesión con acceso full a la base de datos **dbName**
    * dbPass: El password del usuario **dbUser**
* Agrega la siguiente linea para no desplegar ciertos mensajes:

```php
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
```

> **Opcional**: Crea el directorio virtual time_tracking en tu apache y apuntalo al directorio del repositorio

> Recuerda reiniciar Apache cada vez que hagas cambios en la configuración y tener el servidor Mysql iniciado

### Tips y Troubleshooting en MacOS X

* Instalar Command Line Tools desde XCode
* Instalar `Autoconf` y `automake`
* Instalar xDebug
    * OSX

        ```sh
        brew install php56-xdebug
        ```

* **file or directory not found**: Si eres un usuario OSX e instalaste mysql a través de Homebrew, lo más probable es que el socket por defecto de mysql en la configuración de php no corresponda ya que brew lo deja en /tmp/:
    * Para verificar el socket haz phpinfo(), luego en una consola abre mysql y ejecuta el comando STATUS;
    * Busca el socket y reemplaza la ruta en php.ini

* **unknown or incorrect time zone: 'America/Asuncion'**: Timezones en MySql

```sh
$ mysql_tzinfo_to_sql /usr/share/zoneinfo | mysql -u root -p mysql
```

## Deployment

### Prerequisites

* Instalar rbenv
    * OSX

        ```sh
        brew install rbenv
        ```
* Ruby 1.9.3
    * OSX

        ```sh
        rbenv install 1.9.3-p551
        rbenv global 1.9.3-p551
        ```

* bundler Gem

    ```sh
    gem install bundler
    ```

* Install capistrano and dependences

    ```sh
    bundle install
    ```

* Copy server definition file

    _Es necesario modificar este archivo. Dejar solo el servidor al que se realizará el deployeo_

    ```sh
    cp config/cap_servers.rb.default config/cap_servers.rb
    ```

* Instalar bibliotecas con [Composer][12]:

    ```sh
    composer install
    ```

* Actualizar autoloader

    ```sh
    composer dump-autoload --optimize
    ```

### Deploy a stage (stage56.thetimebilling.com/[nombre del feature-patch])

    ```sh
    cap stage deploy
    ```sh

  And enter the Feature Branch later

## API

### Generates Api Documentation

* Install [ApiDoc](http://apidocjs.com/)  ```npm install apidoc -g```

```sh
cd api/
apidoc -o ../apidoc/
```

* open

```sh
../apidoc/index.html
```

and enjoy!

## Test
### Pruebas de Integración
  * Duplica el archivo app/test/spec/conf.rb.default con el nombre app/test/spec/conf.rb y configura tu sitio local
  * (Opcional) instalar el [driver de Chrome][11].
  * Ejecutar pruebas

    ```sh
    cd app/test
    rspec
    ```

#Configuracion MySQL

En OSX la configuracion es estricta, por eso debe ajustarse para ser identica a la de produccion

```sql
SET @@global.sql_mode= '';
```

[1]: https://gist.github.com/3867988
[2]: https://gist.github.com/3868074
[4]: http://www.mattvsworld.com/blog/2010/02/install-the-latest-autoconf-and-automake-on-mac-os-10-6
[5]: http://xdebug.org/wizard.php
[6]: http://localhost/time_tracking
[7]: https://github.com/nvie/gitflow
[8]: https://github.com/datasift/gitflow
[9]: https://github.com/nvie/gitflow/wiki/Windows
[10]: http://www.sublimetext.com/
[11]: https://github.com/LemontechSA/ttb/wiki/Correr-Capybara-con-Chrome
[12]: https://getcomposer.org/
