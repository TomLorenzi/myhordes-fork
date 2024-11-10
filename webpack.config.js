const webpack = require('webpack');

const envPlugin = new webpack.DefinePlugin({
    'process.env.NODE_ENV': JSON.stringify(process.env.NODE_ENV || 'development')
});

const configs = [
    require('./webpack.fe.config'),
    require('./webpack.service.config'),
    require('./webpack.shared.config'),
    require('./webpack.shared-shim.config')
];
configs.forEach( c => c.plugins.push(envPlugin) );
module.exports = configs;
