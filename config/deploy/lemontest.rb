require 'capistrano/cli'
load 'config/cap_notify'
load 'config/cap_shared'
server "lemontest.thetimebilling.com", :web, {:user => 'root'}

 set :current_stage, "custom"
  custom_branch = Capistrano::CLI.ui.ask("Enter Feature Branch []: ")
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
  puts "\n\e[0;31m   #######################################################################"
  puts           "   #    Do you need me to create database \e[01;37m #{dbname}\e[0;31m? (y/N)   #" 
  puts           "   #######################################################################\e[0m\n"
  proceed = STDIN.gets[0..0] rescue nil
  if (proceed == 'y' || proceed == 'Y'  || proceed == 's' || proceed == 'S'  )
    run "  mysql -uroot -pasdwsx -e 'create database #{dbname}' && mysqldump -uroot -pasdwsx --opt  lemontest_molde | mysql -uroot -pasdwsx #{dbname}"
  end

end
    before "deploy:update_code", "deploy:setup"
   
  after "deploy:update", "deploy:cleanup"
  after "deploy:cleanup", "deploy:create_database"
   after "deploy:cleanup", "deploy:lemontest_symlink"
 


end
