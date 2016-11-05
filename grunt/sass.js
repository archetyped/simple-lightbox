module.exports = function(grunt) {

grunt.config('sass', {
	options : {
		outputStyle : 'compressed',
	},
	core : {
		files : [{
			expand : true,
			cwd : '<%= paths.sass.base_src %>/',
			dest : '<%= paths.sass.base_dest %>/',
			src : ['<%= paths.sass.target %>', '<%= paths.sass.exclude %>'],
			ext : '<%= paths.sass.ext %>'
		}]
	},
	themes : {
		options : {
		},
		files : [{
			expand : true,
			cwd : 'themes/',
			src : ['*/**/*.scss', '<%= paths.sass.exclude %>'],
			dest : '<%= paths.sass.dest %>/',
			srcd : '<%= paths.sass.src %>/',
			ext : '<%= paths.sass.ext %>',
			rename : function(dest, matchedSrcPath, options) {
				var path = [options.cwd, matchedSrcPath.replace(options.srcd, dest)].join('');
				return path;
			}
		}]
	}
});

};