module.exports = function(grunt) {

grunt.config('watch', {
	sass : {
		files : ['client/sass/**/*.scss'],
		tasks : ['sass:client']
	},
	js : {
		files : '<%= paths.js.std %>',
		tasks : ['jshint:all', 'uglify:all'],
		options : {
			spawn : false,
		}
	}
});

grunt.event.on('watch', function(action, filepath) {
	//Determine task
	//JS files
	if ( filepath.substr(-3) === '.js' ) {
		grunt.config('paths.js.dyn', [filepath]);
	}
});

};