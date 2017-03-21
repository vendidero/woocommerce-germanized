/* jshint node:true */
module.exports = function( grunt ) {
	'use strict';

	grunt.initConfig({

		// Setting folder templates.
		dirs: {
			css: 'assets/css',
			fonts: 'assets/fonts',
			images: 'assets/images',
			js: 'assets/js'
		},

		// JavaScript linting with JSHint.
		jshint: {
			options: {
				jshintrc: '.jshintrc'
			},
			all: [
				'Gruntfile.js',
				'<%= dirs.js %>/admin/*.js',
				'!<%= dirs.js %>/admin/*.min.js',
				'<%= dirs.js %>/*.js',
				'!<%= dirs.js %>/*.min.js',
				'includes/gateways/direct-debit/assets/js/*.js',
				'!includes/gateways/direct-debit/assets/js/*.min.js'
			]
		},

		// Minify .js files.
		uglify: {
			options: {
				preserveComments: 'some'
			},
			admin: {
				files: [{
					expand: true,
					cwd: '<%= dirs.js %>/admin/',
					src: [
						'*.js',
						'!*.min.js',
						'!Gruntfile.js',
					],
					dest: '<%= dirs.js %>/admin/',
					ext: '.min.js'
				}]
			},
			frontend: {
				files: [{
					expand: true,
					cwd: '<%= dirs.js %>/',
					src: [
						'*.js',
						'!*.min.js'
					],
					dest: '<%= dirs.js %>/',
					ext: '.min.js'
				}]
			},
			direct_debit: {
				files: [{
					expand: true,
					cwd: 'includes/gateways/direct-debit/assets/js/',
					src: [
						'*.js',
						'!*.min.js'
					],
					dest: 'includes/gateways/direct-debit/assets/js/',
					ext: '.min.js'
				}]
			}
		},

		// Minify all .css files.
		cssmin: {
			minify: {
				expand: true,
				cwd: '<%= dirs.css %>/',
				src: ['*.css'],
				dest: '<%= dirs.css %>/',
				ext: '.min.css'
			}
		},

		// Watch changes for assets.
		watch: {
			css: {
				files: ['<%= dirs.css %>/*.css'],
				tasks: ['cssmin']
			},
			js: {
				files: [
					'<%= dirs.js %>/admin/*js',
					'<%= dirs.js %>/*js',
					'!<%= dirs.js %>/admin/*.min.js',
					'!<%= dirs.js %>/*.min.js'
				],
				tasks: ['uglify']
			}
		},

        // PHP Code Sniffer.
        phpcs: {
            options: {
                bin: 'vendor/bin/phpcs',
                standard: './phpcs.ruleset.xml'
            },
            dist: {
                src:  [
                    '**/*.php',                                                  // Include all files
                    '!apigen/**',                                                // Exclude apigen/
                    '!includes/gateways/direct-debit/libraries/**', // Exclude simplify commerce SDK
                    '!includes/libraries/**',                                    // Exclude libraries/
                    '!node_modules/**',                                          // Exclude node_modules/
                    '!tmp/**',                                                   // Exclude tmp/
                    '!vendor/**'                                                 // Exclude vendor/
                ]
            }
        }

	});

	// Load NPM tasks to be used here
	grunt.loadNpmTasks( 'grunt-shell' );
	grunt.loadNpmTasks( 'grunt-contrib-jshint' );
	grunt.loadNpmTasks( 'grunt-contrib-uglify' );
	grunt.loadNpmTasks( 'grunt-contrib-cssmin' );
	grunt.loadNpmTasks( 'grunt-contrib-watch' );
	grunt.loadNpmTasks( 'grunt-contrib-clean' );
    grunt.loadNpmTasks( 'grunt-phpcs' );

	// Register tasks
	grunt.registerTask( 'default', [
		'css',
		'uglify'
	]);

	grunt.registerTask( 'css', [
		'cssmin'
	]);

};