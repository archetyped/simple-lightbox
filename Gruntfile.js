/*global module:false*/
module.exports = function(grunt) {
	// Load tasks
	require('load-grunt-tasks')(grunt);
	// Display task timing
	require('time-grunt')(grunt);
	// Project configuration.
	grunt.initConfig({
		// Metadata.
		pkg : grunt.file.readJSON('package.json'),
		paths : {
			js : {
				std : '**/js/dev/**/*.js',
				dyn : '<%= paths.js.std %>'
			}
		},
	});
	
	grunt.loadTasks('grunt');
	
	// Default Tasks
	grunt.registerTask('build', ['phplint', 'jshint:gruntfile', 'jshint:all', 'uglify', 'sass']);
	grunt.registerTask('watch_all', ['watch:js', 'watch:sass']);
};
