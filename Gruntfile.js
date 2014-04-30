module.exports = function(grunt) {
	// Load tasks
	require('load-grunt-tasks')(grunt);
	// Display task timing
	require('time-grunt')(grunt);
	// Project configuration.
	grunt.initConfig({
		// Metadata
		pkg : grunt.file.readJSON('package.json'),
		// Variables
		paths : {
			// Base dir assets dir
			base : 'client',
			
			// PHP assets
			php : {
				files_std : ['*.php', '**/*.php', '!node_modules/**/*.php'], // Standard file match
				files : '<%= paths.php.files_std %>' // Dynamic file match
			},
			
			// JavaScript assets
			js : {
				base : 'js', // Base dir
				src : '<%= paths.js.base %>/dev', // Development code
				dest : '<%= paths.js.base %>/prod', // Production code
				files_std : '**/<%= paths.js.src %>/**/*.js', // Standard file match
				files : '<%= paths.js.files_std %>' // Dynamic file match
			},
			
			// Sass assets
			sass : {
				src : 'sass', // Source files dir
				dest : 'css', // Compiled files dir
				ext : '.css', // Compiled extension
				target : '*.scss', // Only Sass files in CWD
				exclude : '!_*.scss', // Do not process partials
				base_src : '<%= paths.base %>/<%= paths.sass.src %>', // Base source dir
				base_dest : '<%= paths.base %>/<%= paths.sass.dest %>', // Base compile dir
			}
		},
	});
	
	// Load task configurations
	grunt.loadTasks('grunt');
	
	// Default Tasks
	grunt.registerTask('build', ['phplint', 'jshint:all', 'uglify', 'sass']);
	grunt.registerTask('watch_all', ['watch:js', 'watch:sass']);
};