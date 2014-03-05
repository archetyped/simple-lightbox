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
			src : ['<%= paths.js.files %>'],
			rename : function(dest, srcPath) {
				return srcPath.replace('/' + grunt.config.get('paths.js.src') + '/', '/' + grunt.config.get('paths.js.dest') + '/');
			}
		}]
	},
});

};