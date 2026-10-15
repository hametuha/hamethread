import wpPlugin from '@wordpress/eslint-plugin';
import { createRequire } from 'node:module';
import globals from 'globals';

const require = createRequire( import.meta.url );

export default [
	{
		ignores: [ 'assets/**', 'vendor/**', 'node_modules/**', 'wordpress/**' ],
	},
	...wpPlugin.configs[ 'recommended-with-formatting' ],
	{
		languageOptions: {
			parserOptions: {
				requireConfigFile: false,
				babelOptions: {
					presets: [
						require.resolve( '@wordpress/babel-preset-default' ),
					],
				},
			},
			globals: {
				...globals.browser,
				jQuery: 'readonly',
				alert: 'readonly',
				confirm: 'readonly',
			},
		},
		rules: {
			// WordPress uses snake_case for some API parameters
			camelcase: 'off',
			// Allow var for legacy code
			'no-var': 'warn',
			// Allow alert/confirm for user interaction
			'no-alert': 'off',
			// JSDoc parameter descriptions are optional
			'jsdoc/require-param-description': 'off',
			'jsdoc/require-returns-description': 'off',
		},
	},
];
