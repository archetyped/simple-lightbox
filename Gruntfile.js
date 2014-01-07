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
	});
	
	grunt.loadTasks('grunt');
	
	// Default Tasks
	grunt.registerTask('build', ['phplint', 'jshint:gruntfile', 'jshint:all', 'uglify', 'sass']);
	grunt.registerTask('watch_client', ['watch:client_js', 'watch:client_sass']);
	grunt.registerTask('watch_themes', ['watch:themes_js', 'watch:themes_sass']);
};
