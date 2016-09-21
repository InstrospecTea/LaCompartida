Chef::Log.info("Running deploy/before_symlink.rb...")
Chef::Log.info("Release path: #{release_path}")

composer_command="/usr/bin/php56 /srw/www/thetimebilling/shared/composer.phar install --no-dev- --no-interaction"
run "cd #{release_path} && #{composer_command}"
