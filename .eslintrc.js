module.exports = {
	extends: [ 'plugin:@wordpress/eslint-plugin/recommended' ],
	env: {
		browser: true,
	},
	settings: {
		jsdoc: {
			mode: 'typescript',
		},
	},
	rules: {
		// @wordpress/* packages are provided by the WordPress runtime.
		'import/no-unresolved': [ 'error', { ignore: [ '^@wordpress/' ] } ],
		'import/no-extraneous-dependencies': 'off',
		'jsdoc/require-param-type': 'off',
		'jsdoc/require-returns-description': 'off',
	},
};
