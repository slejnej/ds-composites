import {deleteSync} from 'del';
import gulp from 'gulp';
import * as _sass from 'sass';
import gulpSass from 'gulp-sass';
import sourcemaps from 'gulp-sourcemaps';
import postcss from 'gulp-postcss';
import rename from 'gulp-rename';
import autoprefixer from 'autoprefixer';
import cleanCss from './../../custom/remora_base_theme/.gulp/mrm-clean-css.mjs';
import browserSync from 'browser-sync';
import uglify from 'gulp-uglify';
import minimist from 'minimist';
import gulpif from 'gulp-if';
import imagemin from 'gulp-imagemin';
import {globSync} from 'glob';

const argv = minimist(process.argv.slice(2));
const sass = gulpSass(_sass);
// only generate sourcemaps on local
const isProductionBuild = !!argv.production;

const paths = {
  assets: {
    src: globSync('./assets/[!scss|js]*/**/*').map(x => x.toString()),
    dest: './build',
    watch: './assets/**/*'
  },
  scss: {
    src: globSync('./assets/scss/[!_]*.scss').map(x => x.toString()), // compile all SCSS files that aren't prefixed with _
    dest: './build/css',
    watch: './assets/scss/**/*.scss',
  },
  js: {
    src: './assets/js/*.js',
    dest: './build/js',
    watch: './assets/js/*.js',
  }
}

/**
 * Swallows an error thrown by gulp, displays it to the user and ends the chain so watch keeps running
 * @param err
 */
function swallowError(err) {
  console.error(err.toString());
  this.emit('end');
}


// Delete the build file
const clean = (cb) => {
  deleteSync([ 'build/' ]);
  cb();
};

// Compile SCSS into CSS, makes it compatible with older browsers and minifies
const styles = (cb) => {
  gulp.src(paths.scss.src)
    .pipe(gulpif(!isProductionBuild, sourcemaps.init({})))
    .pipe(sass())
    .on('error', swallowError)
    .pipe(postcss([
      autoprefixer()
    ]))
    .pipe(rename({ suffix: '.min' }))
    .pipe(cleanCss({
      level: {
        1: {
          tidySelectors: false // need this for rules that are formatted as nth-child(1 of ...) https://github.com/clean-css/clean-css/issues/1246#issuecomment-1651226263
        },
      }
    }))
    .pipe(gulpif(!isProductionBuild, sourcemaps.write('.', undefined)))
    .pipe(gulp.dest(paths.scss.dest))
    .pipe(browserSync.stream());

  cb();
};

// Minify and uglify JS files
const js = (cb) => {
  gulp.src([paths.js.src])
    .pipe(uglify())
    .pipe(rename({ suffix: '.min' }))
    .pipe(gulp.dest(paths.js.dest))
    .pipe(browserSync.stream())

  cb();
};

/**
 * Minify assets and copy them over to the build folder
 * Doesn't add .min so it's easier to reference in scss
 *
 * @param cb
 */
const assets = (cb) => {
  if(paths.assets.src.length > 0) {
    gulp.src(paths.assets.src, {base: './', encoding: false})
      .pipe(gulpif((file) => ['.png', '.jpg', '.jpeg', '.gif', '.svg'].includes(file.extname), imagemin()))
      .pipe(gulp.dest(paths.assets.dest))
      .pipe(browserSync.stream());
  }

  cb();
}

// Serve the assets through browsersync
const serve = (cb) => {
  browserSync.init(null, {
    proxy: 'https://ds-composites.lndo.site',
    open: false
  });

  gulp.watch(paths.scss.watch, styles).on('change', browserSync.reload)
  gulp.watch(paths.js.watch, js).on('change', browserSync.reload)
  gulp.watch(paths.assets.watch, assets).on('change', browserSync.reload)
  cb();
};

gulp.task('assets', gulp.series(assets));
gulp.task('build', gulp.series(clean , styles, js, assets))
gulp.task('watch', gulp.series(styles, gulp.parallel(js, serve, assets)));
gulp.task('clean', gulp.series(clean));

gulp.task('default', gulp.series('build'));
