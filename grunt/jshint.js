module.exports = function(grunt) {

grunt.config('jshint', {
	options : {
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
		globals : {},
		reporter: require('jshint-stylish')
	},
	gruntfile : {
		options : {
			node : true
		},
		src : 'Gruntfile.js'
	},
	all : {
		options : {
			globals : {
				'SLB' : true
			}
		},
		src : ['**/js/dev/**/*.js']
	},
	client : {
		src : ['client/js/dev/**/*.js']
	},
	themes : {
		options : {
			globals : {
				'SLB' : true
			}
		},
		src : ['themes/**/*.js']
	}
});

};