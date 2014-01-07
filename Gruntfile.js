/*global module:false*/
module.exports = function(grunt) {
	// Load tasks
	require('load-grunt-tasks')(grunt);
	// Display task timing
	require('time-grunt')(grunt);
	// Project configuration.
	grunt.initConfig({
		// Metadata.
		pkg : grunt.file.readJSON('package.json'),
		banner : '/*! <%= pkg.title || pkg.name %> - v<%= pkg.version %> - ' + '<%= grunt.template.today("yyyy-mm-dd") %>\n' + '<%= pkg.homepage ? "* " + pkg.homepage + "\\n" : "" %>' + '* Copyright (c) <%= grunt.template.today("yyyy") %> <%= pkg.author.name %>;' + ' Licensed <%= pkg.license %> */\n',
		// Task configuration.
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
		},
		uglify : {
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
		},
		sass : {
			options : {
				outputStyle : 'compressed',
			},
			client : {
				files : [{
					expand : true,
					cwd : 'client/sass/',
					dest : 'client/css/',
					src : ['**/*.scss'],
					ext : '.css'
				}]
			},
			themes : {
				options : {
					includePaths : require('node-bourbon').includePaths
				},
				files : [{
					expand : true,
					cwd : 'themes/',
					src : ['*/**/*.scss'],
					dest : 'css/',
					srcd : 'sass/',
					ext : '.css',
					rename : function(dest, matchedSrcPath, options) {
						var path = [options.cwd, matchedSrcPath.replace(options.srcd, dest)].join('');
						return path;
					}
				}]
			}
		},
		phplint : {
			options : {
				phpArgs : {
					'-lf': null
				}
			},
			core : ['*.php'],
			includes : ['includes/**/*.php']
		},
		watch : {
			gruntfile : {
				files : '<%= jshint.gruntfile.src %>',
				tasks : ['jshint:gruntfile']
			},
			client_js : {
				files : '<%= jshint.client.src %>',
				tasks : ['jshint:client', 'uglify:client']
			},
			client_sass : {
				files : ['client/sass/**/*.scss'],
				tasks : ['sass:client']
			},
			themes_js : {
				files : '<%= jshint.themes.src %>',
				tasks : ['jshint:themes']
			},
			themes_sass : {
				files : ['themes/*/sass/*.scss'],
				tasks : ['sass:themes']
			}
		}
	});
	
	// Default Tasks
	grunt.registerTask('build', ['phplint', 'jshint:gruntfile', 'jshint:all', 'uglify', 'sass']);
	grunt.registerTask('watch_client', ['watch:client_js', 'watch:client_sass']);
	grunt.registerTask('watch_themes', ['watch:themes_js', 'watch:themes_sass']);
};
