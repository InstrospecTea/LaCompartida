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

  desc "Send email notification"
  task :send_notification do
    Notifier.deploy_notification(self).deliver 
  end

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
      p absolute_path
     p symlink_path
    p dbname='lemontest_' << dirname.tr('_','')
    run " ln -nsf #{absolute_path} #{symlink_path}"
    run "mysql -ulemontest -plemontest -e 'create database #{dbname}' && mysqldump  -ulemontest -plemontest --opt  lemontest_hotfix | mysql  -ulemontest -plemontest #{dbname}"
  end

   before "deploy:update_code", "deploy:setup"
    before "deploy:update_code", "deploy:lemontest_symlink"
  after "deploy:update", "deploy:cleanup"
 

end
