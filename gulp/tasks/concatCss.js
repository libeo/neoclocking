var gulp = require('gulp');
var concatCss = require('gulp-concat-css');
var config = require('../config.js');

gulp.task('concatCss:vendor', function () {
    return gulp.src([
            config.nodeModuleSrc + 'sweetalert/dist/sweetalert.css',
            config.nodeModuleSrc + 'pickadate/lib/compressed/themes/classic.css',
            config.nodeModuleSrc + 'pickadate/lib/compressed/themes/classic.date.css'
        ])
        .pipe(concatCss('vendor.css'))
        .pipe(gulp.dest(config.build + 'css/'));
});

gulp.task('concatCss', ['concatCss:vendor']);
