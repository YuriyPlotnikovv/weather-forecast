const gulp = require('gulp');
const fs = require('fs');
const path = require('path');
const plumber = require('gulp-plumber');
const sourcemaps = require('gulp-sourcemaps');
const sass = require('gulp-dart-sass');
const postcss = require('gulp-postcss');
const autoprefixer = require('autoprefixer');
const csso = require('postcss-csso');
const rename = require('gulp-rename');
const terser = require('gulp-terser');
const imagemin = require('gulp-imagemin');
const webp = require('gulp-webp');
const svgstore = require('gulp-svgstore');
const cheerio = require('gulp-cheerio');
const del = require('del');
const concat = require('gulp-concat');
const cleanCSS = require('gulp-clean-css');
const babel = require('gulp-babel');
const cached = require('gulp-cached');
const remember = require('gulp-remember');
const notify = require('gulp-notify');

const paths = {
  styles: {
    global: [
      './global/fonts',
      './global/variables',
      './global/settings'
    ],
    blocks: 'src/css/blocks',
    src: 'src/css/style.scss',
    dest: 'public/css',
    watch: 'src/css/**/*.scss'
  },
  scripts: {
    src: 'src/js/modules/*.js',
    dest: 'public/js',
    watch: 'src/js/modules/**/*.js'
  },
  images: {
    src: ['src/img/**/*.{jpg,jpeg,png,svg}', '!src/img/svg/**/*'],
    dest: 'public/img',
    watch: 'src/img/**/*.{jpg,jpeg,png,svg}'
  },
  sprite: {
    src: 'src/img/svg/**/*.svg',
    dest: 'public/img'
  },
  copy: {
    src: [
      'src/js/vendor/*.js',
      'src/css/vendor/*.css',
      'src/fonts/*.{woff,woff2}',
      'src/img/*.gif',
    ],
    dest: 'public/'
  },
  clean: 'public'
};

// Styles
const generateStyleFile = (done) => {
  const styleFilePath = 'src/css/style.scss';

  let content = paths.styles.global.map(filePath => `@use "${filePath}";`).join('\n') + '\n\n';

  fs.readdir(paths.styles.blocks, (err, files) => {
    if (err) {
      console.error('Error reading blocks directory:', err);
      return done(err);
    }

    files.filter(file => path.extname(file) === '.scss').forEach(file => {
      const blockName = path.basename(file, '.scss');
      content += `@use "./blocks/${blockName}";\n`;
    });

    fs.writeFile(styleFilePath, content, (err) => {
      if (err) {
        console.error('Error writing style.scss:', err);
        return done(err);
      }
      done();
    });
  });
};
exports.generateStyleFile = generateStyleFile;

const styles = () => {
  return gulp.src(paths.styles.src)
    .pipe(plumber({errorHandler: notify.onError('Error: <%= error.message %>')}))
    .pipe(sourcemaps.init())
    .pipe(sass.sync({
      outputStyle: 'compressed'
    }).on('error', sass.logError))
    .pipe(postcss([autoprefixer(), csso()]))
    .pipe(cleanCSS({level: 2}))
    .pipe(concat('style.min.css'))
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest(paths.styles.dest));
};
exports.styles = styles;

// Scripts
const scripts = () => {
  return gulp.src(paths.scripts.src)
    .pipe(cached('scripts'))
    .pipe(sourcemaps.init())
    .pipe(babel({
      'presets': [
        [
          '@babel/preset-env'
        ]
      ]
    }))
    .pipe(terser())
    .pipe(remember('scripts'))
    .pipe(concat('script.min.js'))
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest(paths.scripts.dest));
};
exports.scripts = scripts;

// Images
const images = () => {
  return gulp.src(paths.images.src)
    .pipe(imagemin([
      imagemin.mozjpeg({quality: 75, progressive: true}),
      imagemin.optipng({optimizationLevel: 5}),
      imagemin.svgo({
        plugins: [
          {removeViewBox: true},
          {cleanupIDs: false}
        ]
      })
    ]))
    .pipe(webp({quality: 80}))
    .pipe(gulp.dest(paths.images.dest))
    .on('end', () => {
      gulp.src('src/img/**/*.webp')
        .pipe(gulp.dest(paths.images.dest));
    });
};
exports.images = images;

// Sprite
const sprite = () => {
  return gulp.src(paths.sprite.src)
    .pipe(cheerio({
      run: function ($, file) {
        const fileName = path.basename(file.relative, path.extname(file.relative));
        const idMap = {};

        $('[id]').each(function () {
          const oldId = $(this).attr('id');
          const newId = `${fileName}-${oldId}`;
          idMap[oldId] = newId;
        });

        Object.entries(idMap).forEach(([oldId, newId]) => {
          $(`[id="${oldId}"]`).attr('id', newId);
        });

        Object.entries(idMap).forEach(([oldId, newId]) => {
          $(`[xlink\\:href="#${oldId}"]`).attr('xlink:href', `#${newId}`);
          $(`[href="#${oldId}"]`).attr('href', `#${newId}`);

          $(`[fill="url(#${oldId})"]`).attr('fill', `url(#${newId})`);
          $(`[stroke="url(#${oldId})"]`).attr('stroke', `url(#${newId})`);

          $('[style]').each(function () {
            const styleVal = $(this).attr('style');
            if (styleVal && styleVal.includes(`url(#${oldId})`)) {
              const newStyleVal = styleVal.replace(new RegExp(`url\\(#${oldId}\\)`, 'g'), `url(#${newId})`);
              $(this).attr('style', newStyleVal);
            }
          });

          $('style').each(function () {
            const styleContent = $(this).html();
            if (styleContent && styleContent.includes(`url(#${oldId})`)) {
              const updatedStyleContent = styleContent.replace(new RegExp(`url\\(#${oldId}\\)`, 'g'), `url(#${newId})`);
              $(this).html(updatedStyleContent);
            }
          });
        });
        },
      parserOptions: { xmlMode: true }
    }))
    .pipe(svgstore({ inlineSvg: true }))
    .pipe(rename('sprite.svg'))
    .pipe(gulp.dest(paths.sprite.dest));
};
exports.sprite = sprite;

// Copy
const copy = (done) => {
  gulp.src(paths.copy.src, {base: 'src'})
    .pipe(gulp.dest(paths.copy.dest));
  done();
};
exports.copy = copy;

// Clean
const clean = () => {
  return del([paths.clean], {force: true});
};
exports.clean = clean;

// Build
const build = gulp.series(
  clean,
  copy,
  images,
  generateStyleFile,
  gulp.parallel(styles, scripts, sprite)
);
exports.build = build;

// Watch
const watchFiles = () => {
  gulp.watch(paths.styles.watch, styles);
  gulp.watch(paths.scripts.watch, scripts);
  gulp.watch(paths.images.watch, images);
};

// Default
exports.default = build;
