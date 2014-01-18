module.exports = function(grunt) {
	
grunt.config('phplint', {
	options : {
		phpArgs : {
			'-lf': null
		}
	},
	all : {
		src : '<%= paths.php.files %>'
	}
});

};