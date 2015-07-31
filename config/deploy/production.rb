require 'capistrano/cli'

load 'config/cap_notify'
load 'config/cap_shared'
load 'config/cap_servers_production'

set :current_stage, "production"
set :notify_emails, notify_emails << "areacomercial@lemontech.cl"

# Prompt to make really sure we want to deploy into prouction
puts "   ######################################################################".red
puts "   #       Are you REALLY sure you want to deploy to '".red + current_stage.white + "'?".red
puts "   ######################################################################".red

proceed = ask_option ["Y", "N"]
exit unless proceed == 'Y'

set :branch, "master"
# TODO CHANGE BRANCH!!!!!!!!!!!!
set :branch, "feature/migration_nginx"

set :file_path, "#{deploy_dir_name}/#{application}/#{current_stage}"
set :deploy_to, "#{base_directory}/#{file_path}"

namespace :deploy do

  desc "Send email notification"
  task :send_notification do
    Notifier.deploy_notification(self).deliver
  end

  task :run_updates do
    puts " ".white.on_red*50
    puts " * Now I want mark the clients to update later... ".white.on_red
    puts " * Can I?                                         ".white.on_red
    puts " ".white.on_red*50

    sure = ask_option ["Y", "N"]

    update_database(self) if sure == 'Y'
    puts " OK NO :( ".yellow if sure == 'N'
  end

  task :finalize_update, :except => { :no_release => true } do
    transaction do
      run "chmod -R g+w #{releases_path}/#{release_name}"
      run "echo 'stage: #{current_stage}' > #{releases_path}/#{release_name}/environment.txt"
      run "echo 'branch: #{branch}' >> #{releases_path}/#{release_name}/environment.txt"
    end
  end

  task :invalidate_opcache, :role => :web do
      run "curl 'http://localhost/time_tracking/admin/opcache.php?invalidate-cache-plz&json'"
  end

  before "deploy:update_code", "deploy:setup"
  after "deploy:update", "deploy:cleanup"
  after "deploy", 'deploy:send_notification'
  after "deploy", "deploy:run_updates"
  after "deploy", "deploy:invalidate_opcache"

end
