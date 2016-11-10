'use strict';

module.exports = function (grunt) {

  // load all grunt tasks
  require('matchdep').filterDev('grunt-*').forEach(grunt.loadNpmTasks);

  grunt.initConfig({

    pkg: grunt.file.readJSON('package.json'),

    concat: {
      min: {
        files: {
          'assets/js/ad_machina/ad_platform.js' : [
            'assets/js/ad_machina/src/publish/AdPlatform.begin.js',
            'assets/js/ad_machina/src/Utility.js',
            'assets/js/ad_machina/src/EventDispatcher.js',
            'assets/js/ad_machina/src/Widget.js',
            'assets/js/ad_machina/src/Widgets/ImageWidget.js',
            'assets/js/ad_machina/src/Widgets/ButtonWidget.js',
            'assets/js/ad_machina/src/Widgets/TextWidget.js',
            'assets/js/ad_machina/src/Widgets/VideoWidget.js',
            'assets/js/ad_machina/src/Ad.js',
            'assets/js/ad_machina/src/publish/AdPlatform.end.js'
          ]
        }
      }
    },

    uglify: {
      min: {
        files: {
          'assets/js/mpq/insertion_order.min.js' : 'assets/js/mpq/insertion_order.js',
          'assets/js/mpq/proposal.min.js' : 'assets/js/mpq/proposal.js',
          'assets/js/embed/embed.min.js' : 'assets/js/embed/embed.js',
          'assets/js/proposals/proposals.min.js' : 'assets/js/proposals/proposals.js',
          'assets/js/ad_machina/ad_platform.min.js' : 'assets/js/ad_machina/ad_platform.js'
        }
      }
    },

    cssmin: {
      target: {
        files: {
          'assets/css/mpq/main.min.css': 'assets/css/mpq/main.css'
        }
      }
    },

    compass: {
      dist: {
        options: {
          config: 'assets/style/config.rb',
          sassDir: 'assets/style/sass',
          imagesDir: 'assets/img',
          cssDir: 'assets/style',
          javascriptsDir: 'assets/js',
          environment: 'production',
          outputStyle: 'compressed',
          force: true
        }
      }
    },

    browserSync: {
      bsFiles: {
        src: 'assets/style/screen.css'
      },
      options: {
          host: "localhost",
          watchTask: true
      }
    },

    watch: {
      options: {
        livereload: true
      },
      styles: {
        files: ['assets/style/**/*.{sass,scss}','assets/img/ui/*.png'],
        tasks: ['compass']
      },
      scripts: {
        files: ['assets/js/mpq/insertion_order.js', 'assets/js/mpq/proposal.js', 'assets/js/embed/embed.js', 'assets/js/ad_machina/src/**/*.js'],
        tasks: ['concat', 'uglify']
      }
    }

  });

  // Development task checks and concatenates JS, compiles SASS preserving comments and nesting, runs dev server, and starts watch
  grunt.registerTask('default', ['concat', 'uglify', 'cssmin', 'compass', 'browserSync', 'watch']);
};
