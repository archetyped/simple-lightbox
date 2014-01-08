module.exports = function(grunt) {

grunt.config('uglify', {
	options : {
		mangle: false,
		report: 'min'
	},
	all : {
		files : [{
			expand : true,
			cwd : '',
			dest : '',
			src : ['<%= paths.js.dyn %>'],
			rename : function(dest, srcPath) {
				return srcPath.replace('/js/dev/', '/js/prod/');
			}
		}]
	},
});

};