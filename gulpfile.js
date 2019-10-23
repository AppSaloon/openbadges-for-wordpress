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

// Concatenate & Minify Admin JS
// gulp scripts
gulp.task('adminscripts', function() {
  return gulp.src(jsconfig.adminscripts.src)
    .pipe(plumber({errorHandler: onError}))
    .pipe(concat('admin_openbadges.js'))
    .pipe(uglify())
    .pipe(gulp.dest('files/js/'))
    .pipe(size());
});
gulp.task('frontendscripts', function() {
  return gulp.src(jsconfig.frontendscripts.src)
    .pipe(plumber({errorHandler: onError}))
    .pipe(concat('openbadges.js'))
    .pipe(uglify())
    .pipe(gulp.dest('files/js/'))
    .pipe(size());
});

//css task
// gulp sass
gulp.task('adminsass', function() {
  return gulp.src('assets/scss/admin-pages/*.scss')
      .pipe(plumber({errorHandler: onError}))
      .pipe(sass())
      .pipe(minifycss({zindex: false}))
      .pipe(rename('admin-openbadges.css'))
      .pipe(size())
      .pipe(gulp.dest('files/css/'));
});
gulp.task('sass', function() {
  return gulp.src('assets/scss/frontend/*.scss')
      .pipe(plumber({errorHandler: onError}))
      .pipe(sass())
      .pipe(minifycss({zindex: false}))
      .pipe(rename('openbadges.css'))
      .pipe(size())
      .pipe(gulp.dest('files/css/'));
});

// Watch Files For Changes
gulp.task('watch', function() {
    gulp.watch( 'assets/scss/admin-pages/*.scss', gulp.series('adminsass'));
    gulp.watch( 'assets/scss/frontend/*.scss', gulp.series('sass'));
    gulp.watch(jsconfig.adminscripts.src, gulp.series('adminscripts'));
    gulp.watch(jsconfig.frontendscripts.src, gulp.series('frontendscripts'));
});
