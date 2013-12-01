/*global module:false*/
module.exports = function(grunt) {
	// Project configuration.
	grunt.initConfig({
		// Metadata.
		pkg : grunt.file.readJSON('package.json'),
		banner : '/*! <%= pkg.title || pkg.name %> - v<%= pkg.version %> - ' + '<%= grunt.template.today("yyyy-mm-dd") %>\n' + '<%= pkg.homepage ? "* " + pkg.homepage + "\\n" : "" %>' + '* Copyright (c) <%= grunt.template.today("yyyy") %> <%= pkg.author.name %>;' + ' Licensed <%= _.pluck(pkg.licenses, "type").join(", ") %> */\n',
		// Task configuration.
		concat : {
			options : {
				banner : '<%= banner %>',
				stripBanners : true
			},
			dist : {
				src : ['js/lib/<%= pkg.name %>.js'],
				dest : 'js/dist/<%= pkg.name %>.js'
			}
		},
		uglify : {
			options : {
				banner : '<%= banner %>'
			},
			dist : {
				src : '<%= concat.dist.dest %>',
				dest : 'dist/<%= pkg.name %>.min.js'
			}
		},
		jshint : {
			options : {
				curly : true,
				eqeqeq : true,
				immed : true,
				latedef : true,
				newcap : true,
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
				src : ['js/lib/**/*.js']
			}
		},
		qunit : {
			files : ['test/**/*.html']
		},
		sass : {
			dist : {
				files : {
					'css/app.css' : 'sass/app.scss',
					'css/admin.css' : 'sass/admin.scss'
				}
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
				files : 'sass/**/*.scss',
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
	grunt.registerTask('default', ['jshint', 'uglify', 'sass:dist', 'watch']);

};
