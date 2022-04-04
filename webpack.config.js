var Encore = require('@symfony/webpack-encore');

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

var local = [];
try { local = require('./webpack.config.local'); } catch (e) {}

Encore
    // enable REACT
    .enableReactPreset()
    // directory where compiled assets will be stored
    .setOutputPath( local.output_path ? local.output_path :  'public/build/')
    // public path used by the web server to access the output path
    .setPublicPath( local.public_path ? local.public_path : '/build/')
    // only needed for CDN's or sub-directory deploy
    .setManifestKeyPrefix('build')

    .copyFiles({
        from: 'assets/img',
        to: (typeof(local.hash_filenames) !== 'undefined' && !local.hash_filenames)
            ? 'images/[path][name].[ext]'
            : 'images/[path][name].[contenthash:8].[ext]',
    })
    .copyFiles({
        from: 'assets/video',
        to: (typeof(local.hash_filenames) !== 'undefined' && !local.hash_filenames)
            ? 'mov/[path][name].[ext]'
            : 'mov/[path][name].[contenthash:8].[ext]',
    })
    .copyFiles({
        from: 'assets/swf',
        to: (typeof(local.hash_filenames) !== 'undefined' && !local.hash_filenames)
            ? 'flash/[path][name].[ext]'
            : 'flash/[path][name].[contenthash:8].[ext]',
    })
    .copyFiles({
        from: 'node_modules/@ruffle-rs/ruffle',
        pattern: /.*\.(js|wasm)$/,
        to: 'ruffle/[path][name].[ext]'
    })
    .copyFiles({
        from: 'assets/ext',
        to: 'ext/[path][name].[ext]'
    })

    .configureFilenames({
        js: (typeof(local.hash_filenames) !== 'undefined' && !local.hash_filenames)
        ? '[name].js'
        : '[name].[contenthash:8].js',
        css: (typeof(local.hash_filenames) !== 'undefined' && !local.hash_filenames)
        ? '[name].css'
        : '[name].[contenthash:8].css',
    })
    .configureImageRule({
        filename: (typeof(local.hash_filenames) !== 'undefined' && !local.hash_filenames)
            ? 'images/[path][name].[ext]'
            : 'images/[path][name].[contenthash:8].[ext]',
    }).configureFontRule({
        filename: (typeof(local.hash_filenames) !== 'undefined' && !local.hash_filenames)
            ? 'fonts/[path][name].[ext]'
            : 'fonts/[path][name].[contenthash:8].[ext]'
    })

    /*
     * ENTRY CONFIG
     *
     * Add 1 entry for each "page" of your app
     * (including one that's included on every page - e.g. "app")
     *
     * Each entry will result in one JavaScript file (e.g. app.js)
     * and one CSS file (e.g. app.css) if your JavaScript imports CSS.
     */
    .addEntry('app', './assets/js/app.js')
    //.addEntry('page1', './assets/js/page1.js')
    //.addEntry('page2', './assets/js/page2.js')

    // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
    .splitEntryChunks()

    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    .enableSingleRuntimeChunk()

    /*
     * FEATURE CONFIG
     *
     * Enable & configure other features below. For a full
     * list of features, see:
     * https://symfony.com/doc/current/frontend.html#adding-more-features
     */
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning((typeof(local.hash_filenames) !== 'undefined' && !local.hash_filenames) ? false : Encore.isProduction())

    // enables @babel/preset-env polyfills
    .configureBabel(() => {}, {
        useBuiltIns: 'usage',
        corejs: 3
    })

    // enables Sass/SCSS support
    //.enableSassLoader()

    // enables LessCSS support
    .enableLessLoader()

    // uncomment if you use TypeScript
    .enableTypeScriptLoader()

    // uncomment to get integrity="..." attributes on your script & link tags
    // requires WebpackEncoreBundle 1.4 or higher
    .enableIntegrityHashes(Encore.isProduction())

    // uncomment if you're having problems with a jQuery plugin
    //.autoProvidejQuery()

    // uncomment if you use API Platform Admin (composer req api-admin)
    //.enableReactPreset()
    //.addEntry('admin', './assets/js/admin.js')
;

module.exports = Encore.getWebpackConfig();