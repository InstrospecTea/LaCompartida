Chef::Log.info("Running deploy/before_symlink.rb...")
Chef::Log.info("Release path: #{release_path}")

composer_command="/usr/bin/php56 /usr/local/bin/composer.phar install --no-dev --no-interaction"
run "cd #{release_path} && #{composer_command}"

bower_command="/usr/local/bin/bower --allow-root install"
run "cd #{release_path} && #{bower_command}"

bower_update="/usr/local/bin/bower --allow-root update"
run "cd #{release_path} && #{bower_update}"
