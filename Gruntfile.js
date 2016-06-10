module.exports = function(grunt) {
	var vendors = [
		'./bower_components/jquery-file-download/src/Scripts/jquery.fileDownload.js'
	];

	grunt.file.defaultEncoding = 'iso-8859-1';

	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		uglify: {
			options: {
				compress: {
					global_defs: {
						'debug': false
					},
					dead_code: true
				}
			},
			dist: {
				files: {
					'./public/js/vendors.js': vendors
				}
			}
		}
	});

	// Load the plugin that provides the "uglify" task.
	grunt.loadNpmTasks('grunt-contrib-uglify');

	// Default task(s).
	grunt.registerTask('default', ['uglify:dist']);
	grunt.registerTask('build', ['uglify:dist']);
	grunt.registerTask('build-all', ['uglify'])
};
