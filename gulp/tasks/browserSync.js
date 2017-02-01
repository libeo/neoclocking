var gulp = require('gulp');
var browserSync = require('browser-sync');

var config = require('../config.js');

// Static server
gulp.task('browser-sync', function() {
    browserSync({
        proxy: "neoclocking.local",
        // server: {
        //     baseDir: config.build
        // },
        open: config.defaults.browserSync.open
    });
});
