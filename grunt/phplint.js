module.exports = function(grunt) {
	
grunt.config('phplint', {
	options : {
		phpArgs : {
			'-lf': null
		}
	},
	core : ['*.php'],
	includes : ['includes/**/*.php']
});

};