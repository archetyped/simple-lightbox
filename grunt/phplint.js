module.exports = function(grunt) {

grunt.config('phplint', {
	options : {
		phpArgs : {
			'-f': null
		}
	},
	all : {
		src : '<%= paths.php.files %>'
	}
});

};