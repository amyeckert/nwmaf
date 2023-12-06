// new dart sass jawn
const { src, dest } = require('gulp');

const sass = require('gulp-sass')(require('sass'));

const autoprefixer = require('autoprefixer'),
    postcss = require('gulp-postcss'),
    cssnano = require('cssnano'),
    babel = require('gulp-babel'),
    cleancss = require('gulp-clean-css'),
    concat = require('gulp-concat'),
    eslint = require('gulp-eslint'),
    gulp = require('gulp'),
    notify = require('gulp-notify'),
    notifysend = require('notify-send'),
    plumber = require('gulp-plumber'),
    rename = require('gulp-rename'),
    sasslint = require('gulp-sass-lint'),
    sourcemaps = require('gulp-sourcemaps'),
    uglify = require('gulp-uglify')
    ;
const jsFunctionalitySource = 'scripts/source/functionality/*.js';
const jsVendorBasePath = 'scripts/source/vendor';
const jsDest = 'scripts';

const watch = gulp.parallel(watchFiles);
const build = gulp.series(
    stylesSassLint,
    stylesCompile
);

const styles = gulp.parallel(
    stylesSassLint,
    stylesCompile
);

const all = gulp.series(
    stylesCompile,
    stylesSassLint,
    // scripts,
    // scriptsJsLint,
    // scriptsPlugins
);

function stylesCompile() {
    return src('src/scss/**/*.{scss,sass}')
        .pipe(sass().on('error', sass.logError))
        .pipe(plumber())
        .pipe(cleancss({
            keepSpecialComments: false,
            processImport: false
        }))
        .pipe(postcss([autoprefixer(), cssnano()]))
        .pipe(rename({
            suffix: '.min'
        }))
        .pipe(sourcemaps.write())
        .pipe(dest('dist/css'))
        .pipe(notify({
            message: '"Styles - Site" Task Completed'
        }))
}

function stylesSassLint() {
    return (
        gulp
            .src('src/scss/**/*.s+(a|c)ss')
            .pipe(sasslint({
                options: {
                    formatter: 'stylish',
                    'merge-default-rules': true
                },
                files: {
                    ignore: ['src/scss/vendor/*.scss', 'src/scss/vendor/**/*.scss', 'src/scss/global/_fonts.scss']
                },
                configFile: '.sass-lint.yml'
            }))
            .pipe(sasslint.format())
            .pipe(sasslint.failOnError())
            .on('error', notify.onError({
                message: 'SASS Lint Errors',
                onLast: true
            }))
    );
}

// SCRIPTS
// function scriptsJsLint() {
//     return (
//         gulp
//             .src([
//                 jsFunctionalitySource,
//                 '!scripts/source/functionality/utility.js',
//                 '!scripts/source/functionality/image-slider.js'
//             ])
//             .pipe(eslint())
//             .pipe(eslint.format())
//             .pipe(eslint.failAfterError())
//             .on('error', notify.onError({
//                 message: 'ESLint Errors',
//                 onLast: true
//             }))
//     );
// }

function scripts() {
    return (
        gulp
            .src([jsFunctionalitySource])
            .pipe(plumber())
            .pipe(concat('scripts.js'))
            .pipe(babel({
                presets: ['es2015']
            }))
            .pipe(uglify())
            .pipe(rename({
                suffix: '.min'
            }))
            .pipe(gulp.dest(jsDest))
            .pipe(notify({
                message: 'Scripts Compiled',
                onLast: true
            }))
    );
}


// function scriptsPlugins() {
//     return (
//         gulp
//             .src([
//                 'scripts/source/vendor/**/*.js',
//                 'scripts/source/vendor/**/**/*.js'
//             ])
//             .pipe(plumber())
//             .pipe(concat('plugins.min.js'))
//             .pipe(uglify())
//             .pipe(gulp.dest(jsDest))
//             .pipe(notify({
//                 message: 'Plugins Compiled',
//                 onLast: true
//             }))
//     );
// }

// // SVG SPRITE
// // function svgSpriteBuild() {
// //     const config = {
// //         mode: {
// //             symbol: { // symbol mode to build the SVG
// //                 render: {
// //                     scss: {
// //                         dest: '../../../../styles/partial/_iconography.scss'
// //                     }
// //                 },
// //                 prefix: '.u-icon-%s',
// //                 sprite: '../../../../images/sprite-ui.svg', //generated sprite name
// //                 example: {
// //                     template: 'style-guide/templates/icon-template/symbol.tpl.html',
// //                     dest: 'iconography.njk'
// //                 },
// //                 dest: 'style-guide/templates/pages/elements/',
// //                 inline: true
// //             }
// //         }
// //     };

// //     return (
// //         gulp
// //             .src(svgSource, {
// //                 cwd: ''
// //             })
// //             .pipe(plumber())
// //             .pipe(imagemin())
// //             .pipe(svgsprite(config))
// //             .pipe(gulp.dest('.'))
// //             .pipe(notify({
// //                 message: '"SVG Sprite Build" Task Completed'
// //             }))
// //     );
// // }


// WATCH
function watchFiles() {
    gulp.watch(['src/scss/**/*.scss', 'sr/scss/*.scss'], gulp.series(gulp.parallel(stylesCompile, stylesSassLint)));
}

exports.watch = watch;
// exports.js = js;
exports.build = build;
exports.styles = styles;
exports.all = all; // formerly known as the 'default' task

// To run functions individually use these
exports.stylesCompile = stylesCompile;
// exports.scriptsJsLint = scriptsJsLint;


