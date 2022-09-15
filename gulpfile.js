"use strict";

// modules
const csso = require('gulp-csso');
const del = require('del');
const fs = require('fs');
const glob = require('glob');
const gap = require('gulp-append-prepend');
const replace = require('replace-in-file');
const gulp = require('gulp');
const argv = require('minimist')(process.argv.slice(2));
const gulpif = require('gulp-if');
const prefix = require('autoprefixer');
const postcss = require('gulp-postcss');
const rename = require('gulp-rename');
const sass = require('gulp-dart-sass');
const sourcemaps = require('gulp-sourcemaps');
const gulp_watch = require('gulp-watch');
const injector = require('gulp-inject-string');
const colors = require('colors');

// package.json
const packages = require('./package.json');

const logprefix = "[OpenEMR]".bold.cyan + " ";

// configuration
let config = {
    all: [], // must always be empty

    // Command Line Arguments
    dev: argv['dev'],
    build: argv['b'],
    install: argv['i'],

    // Source file locations
    src: {
        styles: {
            style_portal: 'interface/themes/patientportal-*.scss',
            style_tabs: 'interface/themes/tabs_style_*.scss',
            style_uni: 'interface/themes/oe-styles/style_*.scss',
            style_color: 'interface/themes/colors/*.scss',
            directional: 'interface/themes/directional.scss',
            misc: 'interface/themes/misc/**/*.scss'
        }
    },
    dist: {
        assets: 'public/assets/'
    },
    dest: {
        themes: 'public/themes',
        misc_themes: 'public/themes/misc'
    }
};

if (config.install) {
    console.log("\nCopying OpenEMR dependencies using Gulp".bold.yellow + "\n");
} else if (config.build) {
    console.log("\nBuilding OpenEMR themes using Gulp".bold.yellow + "\n");
} else if (config.dev) {
    console.log("\nBuilding OpenEMR themes using Dev Flag for Gulp".bold.yellow + "\n");
} else if (config.all) {
    console.log("\nBuilding OpenEMR themes using All Flag for Gulp".bold.yellow + "\n");
} else {
    // This is used for gulp watch & other misc things
    console.log("\nRunning Gulp for OpenEMR".bold.yellow + "\n");
}

function log_error(isSuccess, err) {
    isSuccess = false;
    console.error(logprefix + "An error occured! Check the log for details.");
    // Log error to console
    console.error(err.toString().red);
    // Kills gulp on error since if we keep running it will
    // still fail
    process.exit(1);
}

// Clean up lingering static themes
function clean(done) {
    del.sync([config.dest.themes + "/*"]);
    done();
}

// Parses command line arguments
function ingest(done) {
    if (config.dev && typeof config.dev !== "boolean") {
        config.dev = true;
    }
    done();
}

// definition of header for all compiled css
const autoGeneratedHeader = `
/*! This style sheet was autogenerated using gulp + scss
 *  For usage instructions, see: https://github.com/openemr/openemr/blob/master/interface/README.md
 */
`;

// standard themes css compilation
function styles_style_portal() {
    let isSuccess = true;
    return gulp.src(config.src.styles.style_portal)
        .pipe(injector.replace('// bs4import', '@import "../../public/assets/bootstrap/scss/bootstrap";'))
        .pipe(sourcemaps.init())
        .pipe(sass().on('error', (err) => {
            log_error(isSuccess, err);
        }))
        .pipe(postcss([prefix()]))
        .pipe(gap.prependText(autoGeneratedHeader))
        .pipe(gulpif(!config.dev, csso()))
        .pipe(gulpif(!config.dev, sourcemaps.write()))
        .on('error', (err) => {
            log_error(isSuccess, err);
        })
        .pipe(gulp.dest(config.dest.themes))
        .on('end', () => {
            if (isSuccess) {
                console.log(logprefix + "Finished compiling OpenEMR portal styles");
            }
        });
}
// standard themes css compilation
function styles_style_uni() {
    let isSuccess = true;
    return gulp.src(config.src.styles.style_uni)
        .pipe(gap.prependText('$compact-theme: false;\n'))
        .pipe(injector.replace('// bs4import', '@import "../../../public/assets/bootstrap/scss/bootstrap";'))
        .pipe(sourcemaps.init())
        .pipe(sass().on('error', (err) => {
            log_error(isSuccess, err);
        }))
        .pipe(postcss([prefix()]))
        .pipe(gap.prependText(autoGeneratedHeader))
        .pipe(gulpif(!config.dev, csso()))
        .pipe(gulpif(!config.dev, sourcemaps.write()))
        .on('error', (err) => {
            log_error(isSuccess, err);
        })
        .pipe(gulp.dest(config.dest.themes))
        .on('end', () => {
            if (isSuccess) {
                console.log(logprefix + "Finished compiling OpenEMR base themes");
            }
        });
}

// standard themes compact css compilation
function styles_style_uni_compact() {
    let isSuccess = true;
    return gulp.src(config.src.styles.style_uni)
        .pipe(gap.prependText('@import "../compact-theme-defaults";\n'))
        .pipe(injector.replace('// bs4import', '@import "../oemr_compact_imports";'))
        .pipe(sourcemaps.init())
        .pipe(sass().on('error', (err) => {
            log_error(isSuccess, err);
        }))
        .pipe(postcss([prefix()]))
        .pipe(gap.prependText(autoGeneratedHeader))
        .pipe(gulpif(!config.dev, csso()))
        .pipe(gulpif(!config.dev, sourcemaps.write()))
        .pipe(rename({
            prefix: "compact_"
        }))
        .on('error', (err) => {
            log_error(isSuccess, err);
        })
        .pipe(gulp.dest(config.dest.themes))
        .on('end', () => {
            if (isSuccess) {
                console.log(logprefix + "Finished compiling OpenEMR compact base themes");
            }
        });
}

// color themes css compilation
function styles_style_color() {
    let isSuccess = true;
    return gulp.src(config.src.styles.style_color)
        .pipe(gap.prependText('$compact-theme: false;\n'))
        .pipe(injector.replace('// bs4import', '@import "../../../public/assets/bootstrap/scss/bootstrap";'))
        .pipe(sourcemaps.init())
        .pipe(sass().on('error', (err) => {
            log_error(isSuccess, err);
        }))
        .pipe(postcss([prefix()]))
        .pipe(gap.prependText(autoGeneratedHeader))
        .pipe(gulpif(!config.dev, csso()))
        .pipe(gulpif(!config.dev, sourcemaps.write()))
        .on('error', (err) => {
            log_error(isSuccess, err);
        })
        .pipe(gulp.dest(config.dest.themes))
        .on('end', () => {
            if (isSuccess) {
                console.log(logprefix + "Finished compiling OpenEMR color themes");
            }
        });
}

// color themes compact css compilation
function styles_style_color_compact() {
    let isSuccess = true;
    return gulp.src(config.src.styles.style_color)
        .pipe(gap.prependText('@import "../compact-theme-defaults";\n'))
        .pipe(injector.replace('// bs4import', '@import "../oemr_compact_imports";'))
        .pipe(sourcemaps.init())
        .pipe(sass().on('error', (err) => {
            log_error(isSuccess, err);
        }))
        .pipe(postcss([prefix()]))
        .pipe(gap.prependText(autoGeneratedHeader))
        .pipe(gulpif(!config.dev, csso()))
        .pipe(gulpif(!config.dev, sourcemaps.write()))
        .pipe(rename({
            prefix: "compact_"
        }))
        .on('error', (err) => {
            log_error(isSuccess, err);
        })
        .pipe(gulp.dest(config.dest.themes))
        .on('end', () => {
            if (isSuccess) {
                console.log(logprefix + "Finished compiling OpenEMR compact color themes");
            }
        });
}

// Tabs CSS compilation
function styles_style_tabs() {
    let isSuccess = true;
    return gulp.src(config.src.styles.style_tabs)
        .pipe(sourcemaps.init())
        .pipe(sass().on('error', (err) => {
            log_error(isSuccess, err);
        }))
        .pipe(postcss([prefix()]))
        .pipe(gap.prependText(autoGeneratedHeader))
        .pipe(gulpif(!config.dev, csso()))
        .pipe(gulpif(!config.dev, sourcemaps.write()))
        .on('error', (err) => {
            log_error(isSuccess, err);
        })
        .pipe(gulp.dest(config.dest.themes))
        .on('end', () => {
            if (isSuccess) {
                console.log(logprefix + "Finished compiling OpenEMR tab navigation styles");
            }
        });
}

// For anything else that needs to be moved, use misc themes
function styles_style_misc() {
    let isSuccess = true;
    return gulp.src(config.src.styles.misc)
        .pipe(sourcemaps.init())
        .pipe(sass().on('error', (err) => {
            log_error(isSuccess, err);
        }))
        .pipe(postcss([prefix()]))
        .pipe(gap.prependText(autoGeneratedHeader))
        .pipe(gulpif(!config.dev, csso()))
        .pipe(gulpif(!config.dev, sourcemaps.write()))
        .on('error', (err) => {
            log_error(isSuccess, err);
        })
        .pipe(gulp.dest(config.dest.misc_themes))
        .on('end', () => {
            if (isSuccess) {
                console.log(logprefix + "Finished compiling miscellaneous styles");
            }
        });
}

// rtl standard themes css compilation
function rtl_style_portal() {
    let isSuccess = true;
    return gulp.src(config.src.styles.style_portal)
        .pipe(gap.prependText('$dir: rtl;\n@import "rtl";\n@import "directional";\n')) // watch out for this relative path!
        .pipe(gap.appendText('@include if-rtl { @include rtl_style; @include portal_style; }\n'))
        .pipe(injector.replace('// bs4import', '@import "oemr-rtl";'))
        .pipe(sourcemaps.init())
        .pipe(sass().on('error', (err) => {
            log_error(isSuccess, err);
        }))
        .pipe(postcss([prefix()]))
        .pipe(gap.prependText(autoGeneratedHeader))
        .pipe(gulpif(!config.dev, csso()))
        .pipe(gulpif(!config.dev, sourcemaps.write()))
        .pipe(rename({
            prefix: "rtl_"
        }))
        .on('error', (err) => {
            log_error(isSuccess, err);
        })
        .pipe(gulp.dest(config.dest.themes))
        .on('end', () => {
            if (isSuccess) {
                console.log(logprefix + "Finished compiling portal styles");
            }
        });
}

// rtl standard themes css compilation
function rtl_style_uni() {
    let isSuccess = true;
    return gulp.src(config.src.styles.style_uni)
        .pipe(gap.prependText('$compact-theme: false;\n$dir: rtl;\n@import "../rtl";\n')) // watch out for this relative path!
        .pipe(gap.appendText('@include if-rtl { @include rtl_style; #bigCal { border-right: 1px solid $black !important; } }\n'))
        .pipe(injector.replace('// bs4import', '@import "../oemr-rtl";'))
        .pipe(sourcemaps.init())
        .pipe(sass().on('error', (err) => {
            log_error(isSuccess, err);
        }))
        .pipe(postcss([prefix()]))
        .pipe(gap.prependText(autoGeneratedHeader))
        .pipe(gulpif(!config.dev, csso()))
        .pipe(gulpif(!config.dev, sourcemaps.write()))
        .pipe(rename({
            prefix: "rtl_"
        }))
        .on('error', (err) => {
            log_error(isSuccess, err);
        })
        .pipe(gulp.dest(config.dest.themes))
        .on('end', () => {
            if (isSuccess) {
                console.log(logprefix + "Finished compiling RTL base themes");
            }
        });
}

// rtl standard themes compact css compilation
function rtl_style_uni_compact() {
    let isSuccess = true;
    return gulp.src(config.src.styles.style_uni)
        .pipe(gap.prependText('@import "../compact-theme-defaults";\n'))
        .pipe(gap.prependText('$dir: rtl;\n@import "../rtl";\n')) // watch out for this relative path!
        .pipe(gap.appendText('@include if-rtl { @include rtl_style; #bigCal { border-right: 1px solid $black !important; } }\n'))
        .pipe(injector.replace('// bs4import', '@import "../oemr_rtl_compact_imports";'))
        .pipe(sourcemaps.init())
        .pipe(sass().on('error', (err) => {
            log_error(isSuccess, err);
        }))
        .pipe(postcss([prefix()]))
        .pipe(gap.prependText(autoGeneratedHeader))
        .pipe(gulpif(!config.dev, csso()))
        .pipe(gulpif(!config.dev, sourcemaps.write()))
        .pipe(rename({
            prefix: "rtl_compact_"
        }))
        .on('error', (err) => {
            log_error(isSuccess, err);
        })
        .pipe(gulp.dest(config.dest.themes))
        .on('end', () => {
            if (isSuccess) {
                console.log(logprefix + "Finished compiling RTL base compact themes");
            }
        });
}

// rtl color themes css compilation
function rtl_style_color() {
    let isSuccess = true;
    return gulp.src(config.src.styles.style_color)
        .pipe(gap.prependText('$compact-theme: false;\n$dir: rtl;\n@import "../rtl";\n')) // watch out for this relative path!
        .pipe(gap.appendText('@include if-rtl { @include rtl_style; #bigCal { border-right: 1px solid $black !important; } }\n'))
        .pipe(injector.replace('// bs4import', '@import "../oemr-rtl";'))
        .pipe(sourcemaps.init())
        .pipe(sass().on('error', (err) => {
            log_error(isSuccess, err);
        }))
        .pipe(postcss([prefix()]))
        .pipe(gap.prependText(autoGeneratedHeader))
        .pipe(gulpif(!config.dev, csso()))
        .pipe(gulpif(!config.dev, sourcemaps.write()))
        .pipe(rename({
            prefix: "rtl_"
        }))
        .on('error', (err) => {
            log_error(isSuccess, err);
        })
        .pipe(gulp.dest(config.dest.themes))
        .on('end', () => {
            if (isSuccess) {
                console.log(logprefix + "Compiled OpenEMR RTL color themes");
            }
        });
}

// rtl color themes compact css compilation
function rtl_style_color_compact() {
    let isSuccess = true;
    return gulp.src(config.src.styles.style_color)
        .pipe(gap.prependText('@import "../compact-theme-defaults";\n'))
        .pipe(gap.prependText('$dir: rtl;\n@import "../rtl";\n')) // watch out for this relative path!
        .pipe(gap.appendText('@include if-rtl { @include rtl_style; #bigCal { border-right: 1px solid $black !important; } }\n'))
        .pipe(injector.replace('// bs4import', '@import "../oemr_rtl_compact_imports";'))
        .pipe(sourcemaps.init())
        .pipe(sass().on('error', (err) => {
            log_error(isSuccess, err);
        }))
        .pipe(postcss([prefix()]))
        .pipe(gap.prependText(autoGeneratedHeader))
        .pipe(gulpif(!config.dev, csso()))
        .pipe(gulpif(!config.dev, sourcemaps.write()))
        .pipe(rename({
            prefix: "rtl_compact_"
        }))
        .on('error', (err) => {
            log_error(isSuccess, err);
        })
        .pipe(gulp.dest(config.dest.themes))
        .on('end', () => {
            if (isSuccess) {
                console.log(logprefix + "Finished compiling RTL compact color themes");
            }
        });
}

// rtl standard themes css compilation
function rtl_style_tabs() {
    let isSuccess = true;
    return gulp.src(config.src.styles.style_tabs)
        .pipe(gap.prependText('$dir: rtl;\n@import "rtl";\n')) // watch out for this relative path!
        .pipe(sourcemaps.init())
        .pipe(sass().on('error', (err) => {
            log_error(isSuccess, err);
        }))
        .pipe(postcss([prefix()]))
        .pipe(gap.prependText(autoGeneratedHeader))
        .pipe(gulpif(!config.dev, csso()))
        .pipe(gulpif(!config.dev, sourcemaps.write()))
        .pipe(rename({
            prefix: "rtl_"
        }))
        .on('error', (err) => {
            log_error(isSuccess, err);
        })
        .pipe(gulp.dest(config.dest.themes))
        .on('end', () => {
            if (isSuccess) {
                console.log(logprefix + "Finished compiling RTL tabs styles");
            }
        });
}

// For anything else that needs to be moved, use misc themes
function rtl_style_misc() {
    let isSuccess = true;
    return gulp.src(config.src.styles.misc)
        .pipe(gap.prependText('$dir: rtl;\n')) // Simply a flag here due to a hierarchy possibly being created
        .pipe(sourcemaps.init())
        .pipe(sass().on('error', (err) => {
            log_error(isSuccess, err);
        }))
        .pipe(postcss([prefix()]))
        .pipe(gap.prependText(autoGeneratedHeader))
        .pipe(gulpif(!config.dev, csso()))
        .pipe(gulpif(!config.dev, sourcemaps.write()))
        .pipe(rename({
            prefix: "rtl_"
        }))
        .on('error', (err) => {
            log_error(isSuccess, err);
        })
        .pipe(gulp.dest(config.dest.misc_themes))
        .on('end', () => {
            if (isSuccess) {
                console.log(logprefix + "Compiled rest of RTL SCSS");
            }
        });
}

// compile themes
const styles = gulp.parallel(styles_style_color, styles_style_color_compact, styles_style_uni, styles_style_uni_compact, styles_style_portal, styles_style_tabs, styles_style_misc, rtl_style_color, rtl_style_color_compact, rtl_style_uni, rtl_style_uni_compact, rtl_style_portal, rtl_style_tabs, rtl_style_misc);

// Copies (and distills, if possible) assets from node_modules to public/assets
function install(done) {
    console.log(logprefix + "Running OpenEMR gulp install task...");
    // combine dependencies and napa sources into one object
    const dependencies = packages.dependencies;
    for (let key in packages.napa) {
        if (packages.napa.hasOwnProperty(key)) {
            dependencies[key] = packages.napa[key];
        }
    }

    for (let key in dependencies) {
        // check if the property/key is defined in the object itself, not in parent
        if (dependencies.hasOwnProperty(key)) {
            if (key == 'dwv') {
                // dwv is special and need to copy dist, decoders and locales
                gulp.src('node_modules/' + key + '/dist/**/*')
                    .pipe(gulp.dest(config.dist.assets + key + '/dist'));
                gulp.src('node_modules/' + key + '/decoders/**/*')
                    .pipe(gulp.dest(config.dist.assets + key + '/decoders'));
                gulp.src('node_modules/' + key + '/locales/**/*')
                    .pipe(gulp.dest(config.dist.assets + key + '/locales'));
            } else if (key == 'bootstrap' || key == 'bootstrap-rtl') {
                // bootstrap and bootstrap-v4-rtl are special and need to copy dist and scss
                gulp.src('node_modules/' + key + '/dist/**/*')
                    .pipe(gulp.dest(config.dist.assets + key + '/dist'));
                gulp.src('node_modules/' + key + '/scss/**/*')
                    .pipe(gulp.dest(config.dist.assets + key + '/scss'));
            } else if (key == '@fortawesome/fontawesome-free') {
                // @fortawesome/fontawesome-free is special and need to copy css, scss, and webfonts
                gulp.src('node_modules/' + key + '/css/**/*')
                    .pipe(gulp.dest(config.dist.assets + key + '/css'));
                gulp.src('node_modules/' + key + '/scss/**/*')
                    .pipe(gulp.dest(config.dist.assets + key + '/scss'));
                gulp.src('node_modules/' + key + '/webfonts/**/*')
                    .pipe(gulp.dest(config.dist.assets + key + '/webfonts'));
            } else if (key == '@ttskch/select2-bootstrap4-theme') {
                // @ttskch/select2-bootstrap4-theme is special and need to copy dist and src
                gulp.src('node_modules/' + key + '/dist/**/*')
                    .pipe(gulp.dest(config.dist.assets + key + '/dist'));
                gulp.src('node_modules/' + key + '/src/**/*')
                    .pipe(gulp.dest(config.dist.assets + key + '/src'));
            } else if (key == "moment") {
                gulp.src('node_modules/' + key + '/min/**/*')
                    .pipe(gulp.dest(config.dist.assets + key + '/min'));
                gulp.src('node_modules/' + key + '/moment.js')
                    .pipe(gulp.dest(config.dist.assets + key));
            } else if (fs.existsSync('node_modules/' + key + '/dist')) {
                // only copy dist directory, if it exists
                gulp.src('node_modules/' + key + '/dist/**/*')
                    .pipe(gulp.dest(config.dist.assets + key + '/dist'));
            } else {
                // copy everything
                gulp.src('node_modules/' + key + '/**/*')
                    .pipe(gulp.dest(config.dist.assets + key));
            }
        }
    }

    console.log(logprefix + "Finished running OpenEMR gulp install task");
    done();
}

function watch() {
    let isSuccess = true;
    console.log(logprefix + "Running gulp watch task...");
    // watch all changes and re-run styles
    gulp.watch('./interface/**/*.scss', {
            interval: 1000,
            mode: 'poll'
        }, styles)
        .on('error', (err) => {
            log_error(isSuccess, err);
        });

    // watch php separately since autoprefix is not needed
    gulp_watch('./interface/themes/*.php', {
            ignoreInitial: false
        })
        .pipe(gulp.dest(config.dest.themes))
        .on('error', (err) => {
            log_error(isSuccess, err);
        });

    // watch all changes to css files in themes and
    // autoprefix them before copying to public
    return gulp_watch('./interface/themes/*.css', {
            ignoreInitial: false
        })
        .pipe(postcss([prefix()]))
        .on('error', (err) => {
            log_error(isSuccess, err);
        })
        .pipe(gulp.dest(config.dest.themes))
        .on('end', () => {
            if (isSuccess) {
                console.log(logprefix + "Finished running gulp watch task");
            }
        });
}

function sync() {
    let isSuccess = true;
    console.log(logprefix + "Running gulp sync task...");
    // copy all leftover root-level components to the theme directory
    // hoping this is only temporary
    // Copy php file separately since we don't need to autoprefix them
    gulp.src(['interface/themes/*.php'])
        .pipe(gulp.dest(config.dest.themes))
        .on('error', (err) => {
            log_error(isSuccess, err);
        });

    // Copy CSS files and autoprefix them
    return gulp.src(['interface/themes/*.css'])
        .pipe(postcss([prefix()]))
        .on('error', (err) => {
            log_error(isSuccess, err);
        })
        .pipe(gulp.dest(config.dest.themes))
        .on('end', () => {
            if (isSuccess) {
                console.log(logprefix + "Finished running gulp sync task");
            }
        });
}

// Export watch task
exports.watch = watch;

// Export pertinent default task
// - Note that the default task runs if no other task is chosen,
//    which is generally how this script is always used (except in
//    rare case where the user is running the watch task).
if (config.install) {
    exports.default = gulp.series(install);
} else {
    exports.default = gulp.series(clean, ingest, styles, sync);
}
