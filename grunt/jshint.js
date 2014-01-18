module.exports = function(grunt) {

grunt.config('jshint', {
	options : {
		reporter: require('jshint-stylish'),
		curly : true,
		eqeqeq : true,
		immed : true,
		latedef : true,
		newcap : false,
		noarg : true,
		sub : true,
		undef : true,
		unused : true,
		boss : true,
		eqnull : true,
		browser : true,
		jquery : true,
		globals : {}
	},
	grunt : {
		options : {
			node : true
		},
		src : ['Gruntfile.js', 'grunt/*.js']
	},
	all : {
		options : {
			globals : {
				'SLB' : true,
				'console' : true
			}
		},
		src : ['<%= paths.js.files %>']
	},
});

};