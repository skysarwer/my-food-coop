const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

const lifecycleEvent = process.env.npm_lifecycle_event || '';
const isBuildLifecycle = /^build(?::|$)/.test( lifecycleEvent );
const isBuildCommand = process.argv.includes( 'build' ) || isBuildLifecycle;
const isWatchMode = process.argv.includes( '--watch' );

const shouldDisableSourceMaps = ( config ) =>
	( isBuildCommand && ! isWatchMode ) ||
	( config?.mode === 'production' && ! isWatchMode );

// With Script Module support wp-scripts exports [ classic, module ]; this plugin
// has no script modules, so only the classic config is used.
const classicConfig = Array.isArray( defaultConfig )
	? defaultConfig[ 0 ]
	: defaultConfig;

module.exports = {
	...classicConfig,
	devtool: shouldDisableSourceMaps( classicConfig )
		? false
		: classicConfig.devtool,
	entry: {
		'admin/index': path.resolve( process.cwd(), 'src/admin', 'index.js' ),
		'sass/admin': path.resolve(
			process.cwd(),
			'src/sass',
			'admin.scss'
		),
	},
	output: {
		...classicConfig.output,
		chunkFilename: 'vendors/[name].js',
	},
};
