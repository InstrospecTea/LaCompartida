require 'capistrano/cli'
load 'config/cap_notify'
load 'config/cap_shared'
load 'config/cap_servers'

set :current_stage, "custom"
default_branch = `git symbolic-ref HEAD 2> /dev/null`.strip.gsub(/^refs\/heads\//, '')

custom_branch = Capistrano::CLI.ui.ask("Enter Custom Branch [#{default_branch}]: ")
custom_branch = (custom_branch && custom_branch.length > 0) ? custom_branch : default_branch
custom_name = custom_branch.split('/').last

set :branch, custom_branch
set :file_path, "#{deploy_dir_name}/#{application}/#{current_stage}_#{custom_name}"
set :deploy_to, "#{base_directory}/#{file_path}"

namespace :deploy do

  desc "Send email notification"
  task :send_notification do
    Notifier.deploy_notification(self).deliver 
  end

  task :stablish_symlinks do
    update_symlinks(self)
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
  after "deploy", "deploy:stablish_symlinks"

end
