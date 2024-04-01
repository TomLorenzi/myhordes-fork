const Encore = require('@symfony/webpack-encore');
const fs = require('fs');
const FaviconsWebpackPlugin = require("favicons-webpack-plugin");

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'development');
}

const local = fs.existsSync( 'webpack.config.local.js' )
    ? require('./webpack.config.local')
    : []
;

Encore
    // directory where compiled assets will be stored
    .setOutputPath( local.output_service_path ??  'public/service/')
    // public path used by the web server to access the output path
    .setPublicPath( local.public_service_path ?? '/service/')
    // only needed for CDN's or sub-directory deploy
    .setManifestKeyPrefix('service');

Encore
    .configureFilenames({
        js: '[name].[contenthash].js'
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
    .addEntry('service', './assets/ts/v2/service.ts')

    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    .disableSingleRuntimeChunk()

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
    .enableVersioning(true)

    // enables @babel/preset-env polyfills
    .configureBabel(() => {}, {
        useBuiltIns: 'usage',
        corejs: 3
    })

    // enables Sass/SCSS support
    //.enableSassLoader()

    // enables LessCSS support
    //.enableLessLoader()

    // uncomment if you use TypeScript
    .enableTypeScriptLoader()

    // uncomment to get integrity="..." attributes on your script & link tags
    // requires WebpackEncoreBundle 1.4 or higher
    .enableIntegrityHashes(true)

    // uncomment if you're having problems with a jQuery plugin
    //.autoProvidejQuery()

    // uncomment if you use API Platform Admin (composer req api-admin)
    //.enableReactPreset()
    //.addEntry('admin', './assets/js/admin.js')
;

const config = Encore.getWebpackConfig();
Encore.reset();

config.name = "myhordes-service";
config.plugins = [
    ...config.plugins ?? [],
]

module.exports = config;