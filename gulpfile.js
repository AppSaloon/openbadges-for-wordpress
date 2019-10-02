// Include gulp
var gulp = require('gulp');

// Include Our Plugins
var concat    = require('gulp-concat');
var uglify    = require('gulp-uglify');
var rename    = require('gulp-rename');
var sass      = require('gulp-sass');
var minifycss = require('gulp-cssnano');
var size      = require('gulp-filesize');
var gutil     = require('gulp-util');
var plumber   = require('gulp-plumber');
var jsconfig  = require('./jsconfig.json');

var onError = function(err) {
  console.log(err.toString());
  this.emit('end');
};

// Concatenate & Minify JS
// gulp scripts
gulp.task('scripts', function() {
  return gulp.src(jsconfig.scripts.src)
    .pipe(plumber({errorHandler: onError}))
    .pipe(concat('openbadges.js'))
    .pipe(uglify())
    .pipe(gulp.dest('dist/js/'))
    .pipe(size());
});

//css task
// gulp sass
gulp.task('sass', function() {
    gulp.src('assets/scss/**/*.scss')
      .pipe(plumber({errorHandler: onError}))
      .pipe(sass())
      .pipe(minifycss({zindex: false}))
      .pipe(rename('openbadges.css'))
      .pipe(size())
      .pipe(gulp.dest('dist/css/'));
});

// Watch Files For Changes
gulp.task('watch', function() {
    gulp.watch( 'assets/scss/**/*.scss', gulp.series('sass'));
    gulp.watch(jsconfig.scripts.src, gulp.series('scripts'));
    //gulp.watch(jsconfig.scripts.src, ['scripts']).on('change', function(evt) {
    //  changeScripts(evt);
    //});
    //gulp.watch('assets/scss/**/*.scss', ['sass']).on('change', function(et) {
    //  changeCss(et);
    //});
});

var changeScripts = function(evt) {
  gutil.log('File', gutil.colors.cyan(evt.path.replace(new RegExp('/.*(?=/assets/js/)/'), '')), 'is', gutil.colors.magenta(evt.type));
};
var changeCss = function(et) {
  gutil.log('File', gutil.colors.cyan(et.path.replace(new RegExp('/.*(?=/assets/scss/)/'), '')), 'is', gutil.colors.magenta(et.type));
};