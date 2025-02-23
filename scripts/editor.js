wp.domReady( function () {
	// core/image.
	wp.blocks.unregisterBlockStyle( 'core/image', 'rounded' );

	// core/button.
	wp.blocks.registerBlockStyle( 'core/button', [
		{
			name: 'inverted',
			label: 'Inverted',
		},
		{
			name: 'inverted-outline',
			label: 'Inverted outline',
		},
	] );

	// core/group.
	wp.blocks.registerBlockStyle( 'core/group', [
		{
			name: 'rounded',
			label: 'Rounded corners',
		},
	] );
} );
