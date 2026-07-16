module.exports = function(grunt) {

	// Project configuration.
	grunt.initConfig({
		pkg: grunt.file.readJSON('../package.json'),
		
		uglify: {
			options: {
		      target: 'browser-no-eval'
		    },
		    js_page: {
		      files: {
				'static/js/fw/fw_utils.min.js': ['static/js/fw/fw_utils.js'],
				'static/js/fw/fw_ajax_form.min.js': ['static/js/fw/fw_ajax_form.js'],
				'static/js/fw/fw_ajax_form_button.min.js': ['static/js/fw/fw_ajax_form_button.js'],
				'static/js/jquery_confirm/jquery-confirm.min.js': ['static/js/jquery_confirm/jquery-confirm.js'],
				'static/js/main.min.js': ['static/js/main.js'],
				}
		    },
		},
		concat: {
			options: {
			  separator: ';',
			},
			js_page: {
		      src: [	
				'static/js/fw/fw_utils.min.js',
				'static/js/fw/fw_ajax_form.min.js',
				'static/js/fw/fw_ajax_form_button.min.js',
				'static/js/jquery_confirm/jquery-confirm.min.js',
				'static/js/main.min.js'
			  ],
		      dest: 'static/bundle.min.js',
		    },
		  },
		
		cssmin: {
		  options: {
		    mergeIntoShorthands: true
		  },
		  css_page: {
		    files: {
		    	'static/external_libs/bootstrap/css/bootstrap.min.css': ['static/external_libs/bootstrap/css/bootstrap.css'],
		    	'static/external_libs/bootstrap/css/bootstrap-theme.min.css': ['static/external_libs/bootstrap/css/bootstrap-theme.css'],
		    	'static/external_libs/tagify-4.34.0/tagify.min.css': ['static/external_libs/tagify-4.34.0/tagify.css'],
		    	'static/css/font.min.css': ['static/css/font.css'],
		    	'static/css/main.min.css': ['static/css/main.css'],
	    	}
		  }
		},
		concat_css: {
		    options: {
		      // Task-specific options go here.
		    },
		    all: {

		      src: [
					'static/external_libs/select2/css/select2.min.css',
					'static/external_libs/tagify-4.34.0/tagify.min.css',
					'static/external_libs/jquery_confirm/jquery-confirm.min.css',
					'static/css/font.min.css',
					'static/css/main.min.css'],
		      dest: "static/bundle.min.css"
		    },
		  },
	});

	grunt.loadTasks('../node_modules/grunt-contrib-cssmin/tasks');
	grunt.loadTasks('../node_modules/grunt-contrib-uglify/tasks');
	grunt.loadTasks('../node_modules/grunt-contrib-concat/tasks');
	grunt.loadTasks('../node_modules/grunt-concat-css/tasks');

	// Default task.
	grunt.registerTask('default', ['uglify', 'concat','cssmin', 'concat_css']);
}