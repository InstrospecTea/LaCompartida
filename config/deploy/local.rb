require 'capistrano/cli'
load 'config/cap_shared'

set :current_stage, "develop"
set :branch, fetch(:branch, "develop")
role :web, "localhost"

namespace :deploy do

  task :finalize_update, :except => { :no_release => true } do
    transaction do
      run "chmod -R g+w #{releases_path}/#{release_name}"
      run "echo 'stage: #{current_stage}' > #{releases_path}/#{release_name}/environment.txt"
      run "echo 'branch: #{branch}' >> #{releases_path}/#{release_name}/environment.txt"
    end
  end
 
  before "deploy:update_code", "deploy:setup"
  after "deploy:update", "deploy:cleanup"

end
