require 'capistrano/cli'
load 'config/cap_notify'
load 'config/cap_shared'
server "lemontest.thetimebilling.com", :web, {:user => 'root'}

default_branch = `git symbolic-ref HEAD 2> /dev/null`.strip.gsub(/^refs\/heads\//, '')

set :current_stage, "custom"

custom_branch = Capistrano::CLI.ui.ask("Enter Release/Hotfix Branch [#{default_branch}]: ")
custom_branch = (custom_branch && custom_branch.length > 0) ? custom_branch : default_branch

custom_name = custom_branch.split('/').last
set :file_path, "#{deploy_dir_name}/#{application}/#{current_stage}_#{custom_name}"
set :branch, custom_branch
set :deploy_to, "#{base_directory}/#{file_path}"

 
namespace :deploy do

  

  task :update_symlinks do
    update_symlinks(self)
  end

    task :expose_vars do
    expose_vars(self)
  end
 
  task :finalize_update, :except => { :no_release => true } do
    transaction do
      run "chmod -R g+w #{releases_path}/#{release_name}"
      run "echo 'stage: #{current_stage}' > #{releases_path}/#{release_name}/environment.txt"
      run "echo 'branch: #{branch}' >> #{releases_path}/#{release_name}/environment.txt"
    end
  end
 
  task :lemontest_symlink do
      subdominio = 'lemontest'
      dominio = 'thetimebilling.com'
      subdir="#{release_name}"
      dirname=branch.tr('/','_')
      absolute_path = base_directory << '/' << file_path << '/current/'
      symlink_path = '/var/www/virtual/lemontest.thetimebilling.com/htdocs/' << dirname
      #p absolute_path
     #p symlink_path
   
    run " ln -nsf #{absolute_path} #{symlink_path}"
       puts "\n\n\n\e[00;32m ====  SUCCESS: \e[0;37m deployed test environment on \e[04;36mhttp://lemontest.thetimebilling.com/#{dirname}\e[00;32m ====\e[0;37m\n\n\n"

  end

task :create_database do
     dirname=branch.tr('/','_')
       dbname='lemontest_' << dirname.tr('_','').tr('.','')

      existedb = `mysql -h192.168.1.24 -uroot -pasdwsx -e "show databases like '%dbname%'";`
      
      if(existedb=='')  
        puts "\n\e[0;31m   ##########################################################################"
        puts           "   #    No existe la base \e[01;37m #{dbname}\e[0;31m, desea crearla? (y/N) #" 
        puts           "   #########################################################################\e[0m\n"
        proceed = STDIN.gets[0..0] rescue nil
        if (proceed == 'y' || proceed == 'Y'  || proceed == 's' || proceed == 'S'  )
          run "  mysql -h192.168.1.24 -uroot -pasdwsx -e 'CREATE DATABASE IF NOT EXISTS #{dbname}' && mysqldump -uroot -pasdwsx  -h192.168.1.24 --opt  lemontest_molde | mysql -uroot -pasdwsx -h192.168.1.24  #{dbname}"
        end
      
      else
          puts "\n\e[0;32m   ############# NO NECESITO CREAR LA BASE \e[01;37m #{dbname}\e[0;32m (PORQUE YA EXISTE) ################\n\n"
          puts "       __                                  _                 _"
          puts "      (_ )                              __( )_              ( )     "
          puts "       | |  ___    ___ ___    ___    ___\\__  _\\ ___    ___  | |__  "
          puts "       | | / __\\  / _ ` _ \\  / _ \\  / _ \\ | |  / __\\  / ___\\| _  \\ "
          puts "       | |(  ___|| ( ) ( ) |( (_) )| ( ) || |_(  ___|( (___ | | | | "
          puts "      (___)\\____)(_) (_) (_) \\___/ (_) (_)\\___)\\____) \\____)(_) (_) \n \n"

      end


end
    before "deploy:update_code", "deploy:setup"
   
  after "deploy:update", "deploy:cleanup"
  after "deploy:cleanup", "deploy:create_database"
   after "deploy:cleanup", "deploy:lemontest_symlink"
 


end


