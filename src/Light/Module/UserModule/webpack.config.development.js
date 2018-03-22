module.exports = {
    mode: "development",
    entry: './src/index.tsx',
    output: {
        filename: 'UserModuleBuild.js',
        path: __dirname + '/../../public/js/build/'
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
