Chef::Log.info("Running deploy/before_symlink.rb...")
Chef::Log.info("Release path: #{release_path}")

composer_command="/usr/bin/php56 #{release_path}/composer.phar install --no-dev --no-interaction"
run "cd #{release_path} && #{composer_command}"

bower_command="/usr/local/bin/bower install"
run "cd #{release_path} && #{bower_command}"

bower_update="/usr/local/bin/bower update"
run "cd #{release_path} && #{bower_update}"
