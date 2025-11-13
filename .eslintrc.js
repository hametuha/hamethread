module.exports = {
	extends: [ 'plugin:@wordpress/eslint-plugin/recommended' ],
	globals: {
		jQuery: 'readonly',
		alert: 'readonly',
		confirm: 'readonly',
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
};