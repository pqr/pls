var webpack = require('webpack');

module.exports = {
    devtool: 'source-map',
    entry: './source/index.tsx',
    output: {
        filename: '../../public/js/UserModuleBuild.js',
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
                'NODE_ENV': JSON.stringify('production'),
                'BABEL_ENV': JSON.stringify('production')
            }
        }),
        new webpack.optimize.ModuleConcatenationPlugin(),
        new webpack.optimize.UglifyJsPlugin({
            sourceMap: true,
            compressor: {
                screw_ie8: true,
                warnings: false
            },
            mangle: {
                screw_ie8: true
            }
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