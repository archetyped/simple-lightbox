/*global module:false*/
module.exports = function(grunt) {
	var paths = {
		make : function (seg, trailing) {
			var args = Array.prototype.slice.call(arguments);
			trailing = ( typeof args[args.length - 1] === 'boolean' ) ? args.pop() : true;
			//Build path
			var sep = '/';
			var path = args.join(sep);
			if ( trailing ) {
				path += sep;
			}
			return path;
		},
		makeJS : function(base) {
			return {
				base : paths.make(base, paths.js),
				dev : paths.make(base, paths.dev),
				dist : paths.make(base, paths.dist)	
			};
		},
		sass : 'sass',
		css : 'css',
		js : 'js',
		dev : 'dev',
		dist : 'dist',
		client : 'client',
		themes : 'themes'
	};
	
	var files = {
		client : {
			sass : [{
				expand : true,
				cwd : paths.make(paths.client, paths.sass),
				dest : paths.make(paths.client, paths.css),
				src : ['**/*.scss'],
				ext : '.css'
			}],
			js : [{
				expand : true,
				cwd : 'client/js/dev/',
				dest : 'client/js/dist/',
				src : ['**/*.js'],
			}]
		},
		themes : {
			sass : [{
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
	};
	
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
				globals : {}
			},
			gruntfile : {
				options : {
					node : true
				},
				src : 'Gruntfile.js'
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
				files : files.client.js
			}
		},
		sass : {
			options : {
				outputStyle : 'compressed',
			},
			client : {
				files : files.client.sass
			},
			themes : {
				options : {
					includePaths : require('node-bourbon').includePaths
				},
				files : files.themes.sass
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
				tasks : ['jshint:client']
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
	
	// These plugins provide necessary tasks.
	grunt.loadNpmTasks('grunt-contrib-concat');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-contrib-qunit');
	grunt.loadNpmTasks('grunt-contrib-jshint');
	grunt.loadNpmTasks('grunt-sass');
	grunt.loadNpmTasks('grunt-phplint');
	grunt.loadNpmTasks('grunt-contrib-watch');

	// Default Tasks
	grunt.registerTask('build', ['phplint', 'jshint', 'uglify', 'sass']);
	grunt.registerTask('watch_client', ['watch:client_js', 'watch:client_sass']);
	grunt.registerTask('watch_themes', ['watch:themes_js', 'watch:themes_sass']);
};
