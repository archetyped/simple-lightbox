/*global module:false*/
module.exports = function(grunt) {
	// Project configuration.
	grunt.initConfig({
		// Metadata.
		pkg : grunt.file.readJSON('package.json'),
		banner : '/*! <%= pkg.title || pkg.name %> - v<%= pkg.version %> - ' + '<%= grunt.template.today("yyyy-mm-dd") %>\n' + '<%= pkg.homepage ? "* " + pkg.homepage + "\\n" : "" %>' + '* Copyright (c) <%= grunt.template.today("yyyy") %> <%= pkg.author.name %>;' + ' Licensed <%= pkg.license %> */\n',
		// Task configuration.
		uglify : {
			options : {
				mangle: false,
				report: 'min'
			},
			dist : {
				files : grunt.file.expandMapping(['**/*.js'], 'client/js/dist/', {
					cwd: 'client/js/dev/'
				})
			},
		},
		jshint : {
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
				globals : {}
			},
			gruntfile : {
				src : 'Gruntfile.js'
			},
			lib_test : {
				src : ['client/js/dev/**/*.js']
			}
		},
		qunit : {
			files : ['test/**/*.html']
		},
		sass : {
			dist : {
				options : {
					outputStyle : 'compressed',
				},
				files : grunt.file.expandMapping(['**/*.scss'], 'client/css/', {
					cwd: 'client/sass/',
					ext: '.css'
				})
			}
		},
		watch : {
			gruntfile : {
				files : '<%= jshint.gruntfile.src %>',
				tasks : ['jshint:gruntfile']
			},
			lib_test : {
				files : '<%= jshint.lib_test.src %>',
				tasks : ['jshint:lib_test']
			},
			sass : {
				files : 'client/sass/**/*.scss',
				tasks : ['sass']
			}
		}
	});

	// These plugins provide necessary tasks.
	grunt.loadNpmTasks('grunt-contrib-concat');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-contrib-qunit');
	grunt.loadNpmTasks('grunt-contrib-jshint');
	grunt.loadNpmTasks('grunt-sass');
	grunt.loadNpmTasks('grunt-contrib-watch');

	// Default task.
	grunt.registerTask('default', ['jshint', 'uglify', 'sass:dist']);

};
