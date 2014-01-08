module.exports = function(grunt) {

grunt.config('sass', {
	options : {
		outputStyle : 'compressed',
	},
	client : {
		files : [{
			expand : true,
			cwd : 'client/sass/',
			dest : 'client/css/',
			src : ['**/*.scss'],
			ext : '.css'
		}]
	},
	themes : {
		options : {
			includePaths : require('node-bourbon').includePaths
		},
		files : [{
			expand : true,
			cwd : 'themes/',
			src : ['*/**/*.scss'],
			dest : 'css/',
			srcd : 'sass/',
			ext : '.css',
			rename : function(dest, matchedSrcPath, options) {
				var path = [options.cwd, matchedSrcPath.replace(options.srcd, dest)].join('');
				return path;
			}
		}]
	}
});

};