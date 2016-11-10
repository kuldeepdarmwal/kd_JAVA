var gulp = require('gulp'),
    requireDir = require('require-dir'),
    tasks = requireDir('./gulp_tasks');

var config = require('./gulp.config')();

/* Default task */
gulp.task('default', ['tsc-app']);