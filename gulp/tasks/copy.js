var gulp = require('gulp');
var gulpif = require('gulp-if');
var imagemin = require('gulp-imagemin');
var config = require('../config.js');

gulp.task('copy:js', function() {
    filesToCopy = [
        config.src + 'js/vendor/modernizr*.js',
        config.src + 'js/vendor/detectizr*.js',
        config.src + 'js/vendor/svg4everybody*.js'
    ];
    return gulp.src(filesToCopy)
               .pipe(gulp.dest(config.build + 'js/vendor'));
});

gulp.task('copy:img', function() {
    return gulp.src(config.src + 'img/**/*')
               .pipe(gulpif(config.imagemin, imagemin()))
               .pipe(gulp.dest(config.build + 'img'));
});

gulp.task('copy:fonts', function() {
    return gulp.src(config.nodeModuleSrc + 'font-awesome/fonts/*')
        .pipe(gulp.dest(config.build + 'fonts'))
});

gulp.task('copy:svg', function() {
    return gulp.src(config.src + 'svg/**/*')
               .pipe(gulp.dest(config.build + 'svg/originals'));
});

gulp.task('copy', ['copy:js', 'copy:img', 'copy:svg', 'copy:fonts']);
