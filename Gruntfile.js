/*global module:false*/
module.exports = function(grunt) {
	var paths = {
		make : function (seg, trailing) {
			var args = Array.prototype.slice.call(arguments);
			trailing = ( typeof args[args.length - 1] === 'boolean' ) ? args.pop() : true;
			//Build path
			var x, sep = '/';
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
			sass : grunt.file.expandMapping(['**/*.scss'], paths.make(paths.client, paths.css), {
				cwd: paths.make(paths.client, paths.sass),
				ext: '.css'
			})
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
				src : 'Gruntfile.js'
			},
			client : {
				src : ['client/js/dev/**/*.js']
			},
			themes : {
				src : ['themes/**/*.js']
			}
		},
		qunit : {
			files : ['test/**/*.html']
		},
		uglify : {
			options : {
				mangle: false,
				report: 'min'
			},
			client : {
				files : grunt.file.expandMapping(['**/*.js'], 'client/js/dist/', {
					cwd: 'client/js/dev/'
				})
			},
		},
		sass : {
			options : {
				outputStyle : 'compressed',
			},
			client : {
				files : files.client.sass
			}
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
