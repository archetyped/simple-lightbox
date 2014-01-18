module.exports = function(grunt) {

grunt.config('watch', {
	phplint : {
		files : '<%= paths.php.files_std %>',
		tasks : ['phplint'],
		options : {
			spawn : false
		}
	},
	sass_core : {
		files : ['<%= paths.sass.base_src %>/**/*.scss'],
		tasks : ['sass:core']
	},
	sass_themes : {
		files : ['themes/**/<%= paths.sass.src %>/**/*.scss'],
		tasks : ['sass:themes']
	},
	jshint : {
		files : '<%= paths.js.files_std %>',
		tasks : ['jshint:all'],
		options : {
			spawn : false
		}
	},
	js : {
		files : '<%= paths.js.files_std %>',
		tasks : ['jshint:all', 'uglify:all'],
		options : {
			spawn : false
		}
	}
});

grunt.event.on('watch', function(action, filepath) {
	// Determine task based on filepath
	var get_ext = function(path) {
		var ret = '';
		var i = path.lastIndexOf('.');
		if ( -1 !== i && i <= path.length ) {
			ret = path.substr(i + 1);
		}
		return ret;
	};
	switch ( get_ext(filepath) ) {
		// PHP
		case 'php' :
			grunt.config('paths.php.files', [filepath]);
			break;
		// JavaScript
		case 'js' :
			grunt.config('paths.js.files', [filepath]);
			break;
	}
});

};