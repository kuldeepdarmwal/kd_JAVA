/*
 * This config is only used during development and build phase only
 * It will not be available on production
 *
 */

(function(global) {
    // ENV
    global.ENV = global.ENV || 'development';

    // map tells the System loader where to look for things
    var map = {
        'app': 'angular/src/tmp/app',
        'angular2-in-memory-web-api': 'https://npmcdn.com/angular2-in-memory-web-api', // get latest
        'angular2-datatable':         '../../node_modules/angular2-datatable'
    };

    // packages tells the System loader how to load when no filename and/or no extension
    var packages = {
        'app': { 
            defaultExtension: 'js'
        },
        'test': {
            defaultExtension: 'js'
        },
        'rxjs': {
            defaultExtension: 'js'
        },
        'angular2-materialize': { main: 'dist/materialize-directive', defaultExtension: 'js' },
        'materialize': { main: 'dist/js/materialize', defaultExtension: 'js' },
        'dragula': { main: 'dist/dragula', defaultExtension: 'js' },
        'angular2-in-memory-web-api': { defaultExtension: 'js' },
        'angular2-datatable':         { defaultExtension: 'js' },
        'angular2-uuid': { main: 'index', defaultExtension: 'js' }
    };

    // List npm packages here
    var npmPackages = [
        '@angular',
        'rxjs',
        'lodash',
        '@ngrx',
        'angular2-materialize',
        'angular2-datatable',
        'materialize',
        'dragula',
        'angular2-uuid'
    ];

    // Add package entries for packages that expose barrels using index.js
    var packageNames = [
        // App barrels
        'app/shared',

        // 3rd party barrels
        'lodash'
    ];

    // Add package entries for angular packages
    var ngPackageNames = [
        'common',
        'compiler',
        'core',
        'http',
        'platform-browser',
        'platform-browser-dynamic',
        'router-deprecated'
    ];

    var otherPackages = [
        '@ngrx/store'
    ]

    npmPackages.forEach(function (pkgName) {
        map[pkgName] = 'node_modules/' + pkgName;
    });

    packageNames.forEach(function(pkgName) {
        packages[pkgName] = { main: 'index.js', defaultExtension: 'js' };
    });

    ngPackageNames.forEach(function(pkgName) {
        var main = global.ENV === 'testing' ? 'index.js' :
            pkgName + '.umd.js';

        packages['@angular/'+pkgName] = { main: main, defaultExtension: 'js' };
    });

    // Add package entries for other packages
    otherPackages.forEach(function(pkgName) {
        packages[pkgName] = { main: 'index.js', defaultExtension: 'js' };
    });
    
    var config = {
        map: map,
        packages: packages
    };

    // filterSystemConfig - index.html's chance to modify config before we register it.
    if (global.filterSystemConfig) { global.filterSystemConfig(config); }

    System.config(config);

})(this);