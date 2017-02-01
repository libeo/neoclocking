var gulp = require('gulp');
var argv = require('yargs').argv;
var requireDir = require('require-dir');
var runSequence = require('run-sequence');

requireDir('./gulp/tasks', { recurse: true });

gulp.task('build', ['browserify', 'sass', 'concatCss', 'copy', 'svg2png', 'svgSprite']);
gulp.task('default', function() {
    if (argv.prod) {
        runSequence('clean', 'build');
    } else {
        runSequence('clean', 'build', 'watch');
    }
});
