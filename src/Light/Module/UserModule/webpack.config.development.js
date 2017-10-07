var webpack = require('webpack');

module.exports = {
    devtool: '#cheap-module-inline-source-map',
    entry: './source/index.tsx',
    output: {
        filename: '../../public/js/UserModuleBuild.js'
    },
    resolve: {
        extensions: ['.ts', '.tsx', '.js']
    },
    externals: {
        'react': 'React',
        'react-dom': 'ReactDOM',
        'Light': 'Light',
        'lodash': '_',
        'mobx': 'mobx',
        'mobx-react': 'mobxReact',
        'serializr': 'serializr'
    },
    plugins: [
        new webpack.DefinePlugin({
            'process.env': {
                'NODE_ENV': JSON.stringify('development'),
                'BABEL_ENV': JSON.stringify('development'),

            },
            '__DEV__': true
        })
    ],
    module: {
        rules: [
            {
                test: /\.js$/,
                enforce: "pre",
                loader: "source-map-loader"
            },
            {
                test: /\.tsx?$/,
                exclude: /node_modules/,
                loader: 'ts-loader'
            }
        ]
    }
};