require 'capistrano/cli'
load 'config/cap_notify'
load 'config/cap_shared'
load 'config/cap_servers'

set :current_stage, "release"
default_branch = fetch(:branch, "master")

if (default_branch == "master")
  release_branch = Capistrano::CLI.ui.ask("Enter Release/Hotfix Branch [#{default_branch}]: ")
end
release_branch = (release_branch && release_branch.length > 0) ? release_branch : default_branch

set :branch, release_branch
set :file_path, "#{deploy_dir_name}/#{application}/#{current_stage}"
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
