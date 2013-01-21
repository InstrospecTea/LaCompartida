require 'capistrano/cli'
load 'config/cap_notify'
load 'config/cap_shared'
load 'config/cap_servers'

set :current_stage, "production"

# Prompt to make really sure we want to deploy into prouction
puts "\n\e[0;31m   ######################################################################"
puts "   #\n   #       Are you REALLY sure you want to deploy to #{current_stage}?"
puts "   #\n   #               Enter y/N + enter to continue\n   #"
puts "   ######################################################################\e[0m\n"
proceed = STDIN.gets[0..0] rescue nil
exit unless proceed == 'y' || proceed == 'Y'

set :branch, "master"
set :file_path, "#{deploy_dir_name}/#{application}/#{current_stage}"
set :deploy_to, "#{base_directory}/#{file_path}"

namespace :deploy do

  desc "Send email notification"
  task :send_notification do
    Notifier.deploy_notification(self).deliver 
  end

  task :run_udpates do
    update_database(self)
  end

  task :finalize_update, :except => { :no_release => true } do
    transaction do
      run "chmod -R g+w #{releases_path}/#{release_name}"
      run "echo 'stage: #{current_stage}' > #{releases_path}/#{release_name}/environment.txt"
      run "echo 'branch: #{branch}' >> #{releases_path}/#{release_name}/environment.txt"
    end
  end
  
  before "deploy:update_code", "deploy:setup"
  after "deploy:update", "deploy:cleanup"
  after "deploy", 'deploy:send_notification'
  after "deploy", "deploy:run_udpates"

end
