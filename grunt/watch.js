module.exports = function(grunt) {

grunt.config('watch', {
	gruntfile : {
		files : '<%= jshint.gruntfile.src %>',
		tasks : ['jshint:gruntfile']
	},
	client_js : {
		files : '<%= jshint.client.src %>',
		tasks : ['jshint:client', 'uglify:client']
	},
	client_sass : {
		files : ['client/sass/**/*.scss'],
		tasks : ['sass:client']
	},
	themes_js : {
		files : '<%= jshint.themes.src %>',
		tasks : ['jshint:themes']
	},
	themes_sass : {
		files : ['themes/*/sass/*.scss'],
		tasks : ['sass:themes']
	}
});

};