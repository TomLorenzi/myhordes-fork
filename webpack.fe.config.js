const Encore = require('@symfony/webpack-encore');
const webpack = require('webpack');
const fs = require('fs');

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
    // enable REACT
    .enableReactPreset()
    // directory where compiled assets will be stored
    .setOutputPath( local.output_path ??  'public/build/')
    // public path used by the web server to access the output path
    .setPublicPath( local.public_path ?? '/build/')
    // only needed for CDN's or sub-directory deploy
    .setManifestKeyPrefix('build');

const filename_pattern = (typeof(local.hash_filenames) !== 'undefined' && !local.hash_filenames)
    ? '[path][name].[ext]'
    : '[path][name].[contenthash:8].[ext]'
;

const prime_asset_path = fs.existsSync( 'packages/myhordes-prime/src/Resources/assets' )
    ? 'packages/myhordes-prime/src/Resources/assets'
    : 'packages/myhordes-prime-shim/src/Resources/assets'

// List of folders that contain game assets
const source_folders = [
    'assets', prime_asset_path
];

// List of asset subfolders that should get copied.
// Format: source name: [destination name, allow hashed file name]
const file_copy_map = {
    img: ['images', true],
    video: ['mov', true],
    swf: ['flash', true],
    ext: ['ext', false],
}

source_folders.forEach( folder => {
    for (const [source, [destination, default_pattern]] of Object.entries(file_copy_map))
        Encore.copyFiles( {
            from: `${folder}/${source}`,
            to: default_pattern ? `${destination}/${filename_pattern}` : `${destination}/[path][name].[ext]`
        } )
} )

Encore
    .copyFiles({
        from: 'node_modules/@ruffle-rs/ruffle',
        pattern: /.*\.(js|wasm)$/,
        to: 'ruffle/[path][name].[ext]'
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
    .addEntry( 'prime', `./${prime_asset_path}/js/prime.js`)
    .addEntry('fa', './assets/js/fa.js')

    .addEntry('swagger', './assets/js/swagger.js')

    .addEntry('module-ruffle', './assets/js/modules/ruffle.js')

    .addEntry('module-game', './assets/ts/modules/common-game-modules.ts')
    .addEntry('module-town-creator', './assets/ts/modules/town-creator.ts')
    .addEntry('module-event-creator', './assets/ts/modules/event-creator.ts')
    .addEntry('module-avatar-creator', './assets/ts/modules/avatar-creator.ts')
    .addEntry('module-notification-manager', './assets/ts/modules/notification-manager.ts')
    .addEntry('module-twino-editor', './assets/ts/modules/twino-editor.ts')
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

const FaviconsWebpackPlugin = require('favicons-webpack-plugin');
const HtmlWebpackPlugin = require('html-webpack-plugin');

const config = Encore.getWebpackConfig();
Encore.reset();

config.name = "myhordes-fe";
config.resolve.fallback = { ...config.resolve?.fallback ?? {}, buffer: require.resolve('buffer/'), stream: require.resolve("stream-browserify") }
config.plugins = [
    ...config.plugins ?? [],
    new webpack.ProvidePlugin({ Buffer: ['buffer', 'Buffer'] }),
    new webpack.ProvidePlugin({ process: 'process/browser' }),
    new FaviconsWebpackPlugin({
        logo: local?.favicon?.image ?? './assets/img/favicon.png',
        prefix: 'favicon/',
        devMode: 'webapp',
        favicons: {
            background: local?.favicon?.background ?? '#100C0B',
            theme_color: local?.favicon?.theme_color ?? '#7e4d2a',
            pixel_art: true
        }
    }),
    new HtmlWebpackPlugin({
        filename: '../../templates/build/favicons.html.twig',
        templateContent: ({favicons}) => favicons,
        inject: false,
        templateParameters:  (compilation, assets, assetTags) => {
            return {favicons: assetTags.headTags
                    .filter( a =>
                        a?.tagName === 'meta' ||
                        (
                            a?.tagName === 'link' &&
                            ['icon','shortcut icon','apple-touch-icon','apple-touch-startup-image','manifest','yandex-tableau-widget']
                                .indexOf(a?.attributes?.rel) >= 0
                        )
                    )
                    .map(a => a.toString() )
                    .join('')}
        }
    })
]

module.exports = config;