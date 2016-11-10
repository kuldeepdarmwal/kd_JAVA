var gulp = require('gulp');
var runSequence = require('run-sequence');
var Builder = require('systemjs-builder');
var config = require('../gulp.config')();

gulp.task('build-systemjs', function (done) {
    runSequence('tsc-app', buildSJS);

    function buildSJS () {
        var builder = new Builder();
        builder.loadConfig(config.src + 'systemjs.conf.js')
        .then(function() {
            var path = config.tmpApp;
            return builder
                .buildStatic(
                    path + 'io/io.js',
                    path + 'io.js',
                    config.systemJs.builder);
        })
        .then(function() {
            var path = config.tmpApp;
            return builder
                .buildStatic(
                    path + 'rfp/rfp.js',
                    path + 'rfp.js',
                    config.systemJs.builder);
        })
        .then(function() {
            var path = config.tmpApp;
            return builder
                .buildStatic(
                    path + 'campaign/campaign.js',
                    path + 'campaign.js',
                    config.systemJs.builder);
        })
        .then(function() {
            var path = config.tmpApp;
            return builder
                    .buildStatic(
                            path + 'campaign_setup/campaign-setup.js',
                            path + 'campaign-setup.js',
                            config.systemJs.builder);
        })
        .then(function() {
            console.log('Build complete');
            done();
        })
        .catch(function (ex) {
            console.log('error', ex);
            done('Build failed.');
        });
    }
});