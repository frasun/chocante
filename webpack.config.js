// WordPress webpack config.
const defaultConfig = require('@wordpress/scripts/config/webpack.config');

// Plugins.
const RemoveEmptyScriptsPlugin = require('webpack-remove-empty-scripts');

// Utilities.
const path = require('path');

// Add any new entry points by extending the webpack config.
module.exports = {
	...defaultConfig,
	...{
		entry: {
			'styles': path.resolve(process.cwd(), 'styles', 'theme.scss'),
			'scripts': path.resolve(process.cwd(), 'scripts', 'theme.js'),
			'cart': path.resolve(process.cwd(), 'scripts', 'cart.js'),
		},
		plugins: [
			// Include WP's plugin config.
			...defaultConfig.plugins,

			// Removes the empty `.js` files generated by webpack but
			// sets it after WP has generated its `*.asset.php` file.
			new RemoveEmptyScriptsPlugin({
				stage: RemoveEmptyScriptsPlugin.STAGE_AFTER_PROCESS_PLUGINS
			})
		],
		stats: {
			warnings: false
		}
	}
};