const webpack = require('webpack');

const envPlugin = new webpack.DefinePlugin({
    'process.env.NODE_ENV': JSON.stringify(process.env.NODE_ENV || 'development')
});

const fe_config = require('./webpack.fe.config');
const service_config = require('./webpack.service.config');

fe_config.plugins.push( envPlugin );
service_config.plugins.push( envPlugin );

module.exports = [
    fe_config,
    service_config
];