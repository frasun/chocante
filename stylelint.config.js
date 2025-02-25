module.exports = {
	extends: [ '@wordpress/stylelint-config/scss-stylistic' ],
	rules: {
		'selector-class-pattern': null,
		'selector-id-pattern': null,
	},
	overrides: [
		{
			files: [ '**/*.scss' ],
			customSyntax: 'postcss-scss',
		},
	],
};
