/* eslint-disable import/no-unresolved */
import {
	store,
	withSyncEvent,
	getContext,
	getElement,
} from '@wordpress/interactivity';

store( 'chocante/product-rating', {
	callbacks: {
		onSelectedChange() {
			const context = getContext();
			const {
				selected,
				rating: { value },
			} = context;

			if ( selected === value ) {
				const { ref: input } = getElement();

				input.focus();
				input.checked = true;
				input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
			}
		},
	},
	actions: {
		setSelectedWithKeyboard: withSyncEvent( ( event ) => {
			const context = getContext();
			const {
				labels,
				rating: { value: index },
			} = context;

			switch ( event.key ) {
				case 'ArrowRight':
				case 'ArrowDown':
					context.selected = ( index % labels.length ) + 1;
					break;
				case 'ArrowLeft':
				case 'ArrowUp':
					context.selected =
						( ( index - 2 + labels.length ) % labels.length ) + 1;
					break;
				case 'Home':
					context.selected = 1;
					break;
				case 'End':
					context.selected = labels.length;
					break;
				default:
					return;
			}

			event.preventDefault();
		} ),
		setSelectedLabel() {
			const { ref: input } = getElement();
			const context = getContext();

			if ( ! input.checked ) {
				context.selectedLabel = null;
				return;
			}

			const {
				labels,
				rating: { value },
			} = context;

			const ratingLabel = labels.find(
				( { value: rating } ) => rating === value
			);

			context.selectedLabel = ratingLabel ? ratingLabel.label : null;
		},
	},
} );
