/* jshint node:true */
module.exports = function( grunt ) {
    'use strict';
    var sass = require( 'node-sass' );

    grunt.initConfig({

        // Setting folder templates.
        dirs: {
            css: 'assets/css',
            fonts: 'assets/fonts',
            images: 'assets/images',
            js: 'assets/js'
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
						'!Gruntfile.js'
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
        // Compile all .scss files.
        sass: {
            compile: {
                options: {
                    implementation: sass,
                    sourceMap: 'none'
                },
                files: [{
                    expand: true,
                    cwd: '<%= dirs.css %>/',
                    src: ['*.scss'],
                    dest: '<%= dirs.css %>/',
                    ext: '.css'
                }]
            }
        },

        // Minify all .css files.
        cssmin: {
            minify: {
                expand: true,
                cwd: '<%= dirs.css %>/',
                src: ['*.css', '!*.min.css'],
                dest: '<%= dirs.css %>/',
                ext: '.min.css'
            }
        },

        // Concatenate select2.css onto the admin.css files.
        concat: {
            main: {
                files: {}
            }
        },

        // Watch changes for assets.
        watch: {
            css: {
                files: ['<%= dirs.css %>/*.scss'],
                tasks: ['sass', 'postcss', 'cssmin', 'concat']
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

        // Check textdomain errors.
        checktextdomain: {
            options:{
                text_domain: 'woocommerce-germanized',
                keywords: [
                    '__:1,2d',
                    '_e:1,2d',
                    '_x:1,2c,3d',
                    'esc_html__:1,2d',
                    'esc_html_e:1,2d',
                    'esc_html_x:1,2c,3d',
                    'esc_attr__:1,2d',
                    'esc_attr_e:1,2d',
                    'esc_attr_x:1,2c,3d',
                    '_ex:1,2c,3d',
                    '_n:1,2,4d',
                    '_nx:1,2,4c,5d',
                    '_n_noop:1,2,3d',
                    '_nx_noop:1,2,3c,4d'
                ]
            },
            files: {
                src:  [
                    '**/*.php',               // Include all files
                    '!includes/libraries/**', // Exclude libraries/
                    '!includes/gateways/direct-debit/libraries/**', // Exclude libraries/
                    '!node_modules/**',       // Exclude node_modules/
                    '!tests/**',              // Exclude tests/
                    '!vendor/**',             // Exclude vendor/
                    '!tmp/**',                // Exclude tmp/
                    '!packages/*/vendor/**'   // Exclude packages/*/vendor
                ],
                expand: true
            }
        },

        // Autoprefixer.
        postcss: {
            options: {
                processors: [
                    require( 'autoprefixer' )
                ]
            },
            dist: {
                src: [
                    '<%= dirs.css %>/*.css'
                ]
            }
        }
	});

	// Load NPM tasks to be used here
    grunt.loadNpmTasks( 'grunt-sass' );
    grunt.loadNpmTasks( 'grunt-shell' );
    grunt.loadNpmTasks( 'grunt-rtlcss' );
    grunt.loadNpmTasks( 'grunt-postcss' );
    grunt.loadNpmTasks( 'grunt-contrib-uglify' );
    grunt.loadNpmTasks( 'grunt-contrib-cssmin' );
    grunt.loadNpmTasks( 'grunt-contrib-concat' );
    grunt.loadNpmTasks( 'grunt-contrib-copy' );
    grunt.loadNpmTasks( 'grunt-contrib-watch' );
    grunt.loadNpmTasks( 'grunt-contrib-clean' );

    // Register tasks
    grunt.registerTask( 'default', [
        'css',
        'uglify'
    ]);

    grunt.registerTask( 'css', [
        'sass',
        'postcss',
        'cssmin',
        'concat'
    ]);

    grunt.registerTask( 'assets', [
        'css',
        'uglify'
    ]);
};