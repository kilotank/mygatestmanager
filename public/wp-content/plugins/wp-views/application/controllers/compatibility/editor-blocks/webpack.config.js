const path = require( 'path' );
const ExtractTextPlugin = require( 'extract-text-webpack-plugin' );
const StyleLintPlugin = require( 'stylelint-webpack-plugin' );

// Set different CSS extraction for editor only and common block styles
const blocksCSSPlugin = new ExtractTextPlugin( {
    filename: ( getPath ) => {
        return getPath( './css/[name].style.css').replace('editor.', '' );
    }
} );
const editBlocksCSSPlugin = new ExtractTextPlugin( {
    filename: ( getPath ) => {
        return getPath( './css/[name].editor.css').replace('editor.', '' );
    }
} );

// Configuration for the ExtractTextPlugin.
const extractConfig = {
    use: [
        { loader: 'raw-loader' },
        {
            loader: 'postcss-loader',
            options: {
                plugins: [
                    require( 'autoprefixer' ),
                ]
            }
        },
        {
            loader: 'sass-loader',
            query: {
                includePaths: [ '../../../../vendor/toolset/toolset-common/toolset-blocks/assets/stylesheets' ],
                data: '@import "colors"; @import "variables";',
                outputStyle:
                    'production' === process.env.NODE_ENV ? 'compressed' : 'nested'
            }
        }
    ]
};

module.exports = {
    entry: {
        'view.block.editor': './blocks/view/index.js',
		'ct.block.editor': './blocks/ct/index.js',
    },
    output: {
        path: path.resolve( __dirname, 'assets' ),
        filename: './js/[name].js'
    },
    watch: true,
    devtool: 'source-map',
    module: {
        rules: [
            // Setup ESLint loader for JS.
            {
                enforce: 'pre',
                test: /\.js$/,
                exclude: /(node_modules|bower_components)/,
                loader: 'eslint-loader',
                options: {
                    emitWarning: true,
                }
            },
            {
                test: /\.js$/,
                exclude: /(node_modules|bower_components)/,
                use: {
                    loader: 'babel-loader'
                }
            },
            {
                test: /style\.s?css$/,
                use: blocksCSSPlugin.extract( extractConfig )
            },
            {
                test: /editor\.s?css$/,
                use: editBlocksCSSPlugin.extract( extractConfig )
            },
        ]
    },
    plugins: [
        blocksCSSPlugin,
        editBlocksCSSPlugin,
        new StyleLintPlugin({
            configFile: '../../../../vendor/toolset/toolset-common/toolset-blocks/.stylelintrc',
            syntax: 'scss'
        }),
    ],
	resolve: {
		alias: {
			Common: path.resolve( __dirname, '../../../../vendor/toolset/toolset-common/toolset-blocks/blocks/common' ),
		}
	}
};
