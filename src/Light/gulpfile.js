var gulp = require('gulp'),
    uglify = require('gulp-uglify'),
    gulpFilter = require('gulp-filter'),
    concat = require('gulp-concat'),
    plumber = require('gulp-plumber'),
    rename = require("gulp-rename");

gulp.task('vendorsjs', function () {

    var allSrc = [
        'node_modules/jquery/dist/jquery.min.js',
        'node_modules/sticky-table-headers/js/jquery.stickytableheaders.min.js',
        'node_modules/keypress.js/keypress-2.1.4.min.js',
        'public/js/jquery-ui.min.js',
        'public/js/tooltip.js',
        'public/js/lodash.custom.min.js'
    ];
    var toUglifyFilter = gulpFilter(
        [
            'public/js/tooltip.js'
        ],
        {restore: true});

    return gulp.src(allSrc)
        .pipe(plumber())
        .pipe(toUglifyFilter)
        .pipe(uglify({
            compressor: {
                screw_ie8: true
            },
            mangle: {
                screw_ie8: true
            }
        }))
        .pipe(toUglifyFilter.restore)
        .pipe(concat('vendors.js'))
        .pipe(gulp.dest('public/js'));

});

gulp.task('rollbar', function () {
    "use strict";
    var src = [
        'node_modules/rollbar/dist/rollbar.umd.min.js'
    ];
    return gulp.src(src)
        .pipe(gulp.dest('public/js'));
});

gulp.task('raven', function () {
    "use strict";
    var src = [
        'node_modules/raven-js/dist/raven.min.js',
        'node_modules/raven-js/dist/raven.min.js.map',
    ];
    return gulp.src(src)
        .pipe(gulp.dest('public/js'));
});

gulp.task('select2', function () {
    "use strict";
    gulp.src('node_modules/select2/dist/js/select2.min.js')
        .pipe(gulp.dest('public/js'));
    gulp.src('node_modules/select2/dist/css/select2.min.css')
        .pipe(gulp.dest('public/css'));
});


gulp.task('fileupload', function () {

    var allSrc = [
        'node_modules/blueimp-file-upload/js/vendor/jquery.ui.widget.js',
        'node_modules/blueimp-file-upload/js/jquery.iframe-transport.js',
        'node_modules/blueimp-file-upload/js/jquery.fileupload.js'
    ];
    return gulp.src(allSrc)
        .pipe(plumber())
        .pipe(uglify({
            compressor: {
                screw_ie8: true
            },
            mangle: {
                screw_ie8: true
            }
        }))
        .pipe(concat('fileupload.min.js'))
        .pipe(gulp.dest('public/js'));

});

gulp.task('react', function () {
    "use strict";
    var src = [
        'node_modules/react/umd/react.development.js',
        'node_modules/react/umd/react.production.min.js',
        'node_modules/react-dom/umd/react-dom.development.js',
        'node_modules/react-dom/umd/react-dom.production.min.js'
    ];
    return gulp.src(src)
        .pipe(gulp.dest('public/js/react'));
});

gulp.task('mobx', function () {
    "use strict";
    var src = [
        'node_modules/mobx/lib/mobx.umd.js',
        'node_modules/mobx/lib/mobx.umd.min.js',
    ];
    gulp.src(src)
        .pipe(gulp.dest('public/js/mobx'));

    gulp.src('node_modules/mobx/lib/mobx.umd.min.js.map')
        .pipe(gulp.dest('public/js/mobx/lib'));

    gulp.src('node_modules/mobx-react/index.js')
        .pipe(rename('mobx-react.js'))
        .pipe(gulp.dest('public/js/mobx'));

    gulp.src('node_modules/mobx-react/index.min.js')
        .pipe(rename('mobx-react.min.js'))
        .pipe(gulp.dest('public/js/mobx'));

    var serializrSrc = [
        'node_modules/serializr/serializr.js',
        'node_modules/serializr/serializr.min.js',
        'node_modules/serializr/serializr.min.js.map',
    ];
    gulp.src(serializrSrc)
        .pipe(gulp.dest('public/js/mobx'));
});

gulp.task('preact', function() {
    "use strict";
    var src = [
        'node_modules/preact/dist/preact.dev.js',
        'node_modules/preact/dist/preact.dev.js.map',
        'node_modules/preact/dist/preact.min.js',
        'node_modules/preact/dist/preact.min.js.map',
        'node_modules/preact-compat/dist/preact-compat.js',
        'node_modules/preact-compat/dist/preact-compat.js.map',
        'node_modules/preact-compat/dist/preact-compat.min.js',
        'node_modules/preact-compat/dist/preact-compat.min.js.map',
        'node_modules/prop-types/prop-types.js',
        'node_modules/prop-types/prop-types.min.js',
    ];
    gulp.src(src)
        .pipe(gulp.dest('public/js/preact'));

});

gulp.task('fontawesome', function() {
    "use strict";
    return gulp.src('node_modules/font-awesome/fonts/*')
        .pipe(gulp.dest('public/fonts'));
});

gulp.task('buildcss', function () {

    var allSrc = [
        'public/css/jquery-ui.min.css',
        'node_modules/font-awesome/css/font-awesome.min.css',
        'public/css/Light.css',
        'public/css/InvStatus.css',
        'public/css/Table.css'
    ];

    return gulp.src(allSrc)
        .pipe(plumber())
        .pipe(concat('all.css'))
        .pipe(gulp.dest('public/css'));

});

gulp.task('watch', function () {
    gulp.watch(['public/css/Light.css', 'public/css/Table.css'], ['buildcss']);
});

gulp.task('buildjs', ['vendorsjs', 'rollbar', 'select2', 'fileupload', 'react', 'mobx', 'preact']);

gulp.task('default', ['buildjs', 'buildcss', 'fontawesome']);

gulp.task('dev', ['watch', 'buildcss']);
