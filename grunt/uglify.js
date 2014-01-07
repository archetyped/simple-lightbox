module.exports = function(grunt) {

grunt.config('uglify', {
	options : {
		mangle: false,
		report: 'min'
	},
	client : {
		files : [{
			expand : true,
			cwd : 'client/js/dev/',
			dest : 'client/js/prod/',
			src : ['**/*.js'],
		}]
	}
});

};