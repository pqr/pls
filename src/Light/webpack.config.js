var webpack = require('webpack');

function DtsBundlePlugin(){}
DtsBundlePlugin.prototype.apply = function (compiler) {
    compiler.plugin('done', function(){
        var dts = require('dts-bundle');

        dts.bundle({
            name: 'Light',
            main: 'TypeScript/types/index.d.ts',
            out: 'lightbuild.d.ts',
            removeSource: false,
            outputAsModuleFolder: false
        });
    });
};

console.log(__dirname + '/public/js/build');

module.exports = {
    mode: 'production',
    devtool: "source-map",
    entry: './TypeScript/index.ts',
    output: {
        filename: 'lightbuild.js',
        path: __dirname + '/public/js/build',
        library: 'Light',
        libraryTarget: 'window'
    },
    resolve: {
        extensions: ['.ts']
    },
    externals: {
        'lodash': '_'
    },
    watch:true,
    optimization: {
        noEmitOnErrors: false
    },
    /*
    plugins: [
        new DtsBundlePlugin()
    ],
    */
    module: {
        rules: [
            {
                test: /\.tsx?$/,
                exclude: /node_modules/,
                loader: 'ts-loader'
            },
            {
                test: /\.js$/,
                enforce: "pre",
                loader: "source-map-loader"
            }
        ]
    }
};
