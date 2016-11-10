var gulp = require('gulp');
var runSequence = require('run-sequence');
var Builder = require('systemjs-builder');
var config = require('../gulp.config')();
var useref = require('gulp-useref');
var gulpif = require('gulp-if');
var rev = require('gulp-rev');
var revReplace = require('gulp-rev-replace');
var uglify = require('gulp-uglify');
var cssnano = require('gulp-cssnano');
var debug = require('gulp-debug');

gulp.task('build', function (done) {
    runSequence('build-systemjs', 'build-assets', done);
});

/* Concat and minify/uglify all css, js, and copy fonts */
gulp.task('build-assets', function (done) {
    moveHtml(config.app + '**/*.html');

    gulp.src(config.index)
        .pipe(useref())
        .pipe(debug())
        .pipe(gulpif('assets/lib.js', uglify()))
        .pipe(gulp.dest(config.build.path))
        .pipe(debug())
        .on('finish', done);
});

gulp.task('html', function(done) {
    gulp.src(config.app + '**/*.html', {
        base: config.app
    })
    .pipe(gulp.dest(config.build.app));
});

function moveHtml(files) {
    gulp.src(files, {
        base: config.app
    })
    .pipe(gulp.dest(config.build.app));
}

gulp.task('watch-html', function () {
    moveHtml(config.app + '**/*.html');
    
    return gulp.watch(config.app + '**/*.html', function (file) {
        console.log('Compiling ' + file.path + '...');
        return moveHtml(file.path);
    });
});

gulp.task('watch', ['tsc-app', 'watch-ts', 'watch-html']);