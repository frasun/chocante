/* eslint-disable import/no-unresolved */
import {
	store,
	withSyncEvent,
	getElement,
	getConfig,
} from '@wordpress/interactivity';
import delayedShowMenu from './delayed-show-menu';
import './product-rating.js';

const COMMENTS = '#comments';
const REVIEW_FORM = '#review_form';
const REVIEW_FORM_FEEDBACK = '#feedback';
const GENERIC_ERROR = 'Something went wrong, please try again.';

const { state, callbacks } = store( 'chocante/product-reviews', {
	state: {
		reviews: {
			isLoading: null,
			get hasLoaded() {
				return state.reviews.isLoading === false;
			},
			forceReload: false,
		},
		form: {
			isVisible: null,
			isFetched: false,
			isPostingReview: null,
			feedback: {
				get show() {
					return state.form.feedback.message !== null;
				},
				status: null,
				message: null,
			},
			config: {
				showForm: null,
				verifiedBuyer: null,
				author: null,
				email: null,
				authorRequired: null,
				mustLogIn: null,
				mustBeVerified: null,
				nonce: null,
				productId: null,
			},
		},
	},
	actions: {
		prefetch: withSyncEvent( function* ( event ) {
			const { actions: router } = yield import(
				'@wordpress/interactivity-router'
			);

			yield router.prefetch( event.target.href );
		} ),
		navigate: withSyncEvent( ( event ) => {
			event.preventDefault();
			callbacks.navigate( event.currentTarget.href );
		} ),
		submitReview: withSyncEvent( function* ( event ) {
			event.preventDefault();

			const { ref: form } = getElement();
			const body = new FormData( form );

			body.append( 'action', 'submit_review' );
			body.append( '_ajax_nonce', state.form.config.nonce );
			body.append( 'comment_post_ID', state.form.config.productId );
			body.append( 'comment_parent', 0 );

			if ( ! state.form.config.authorRequired ) {
				body.delete( 'author' );
				body.delete( 'email' );
			}

			try {
				callbacks.resetFormFeedback();
				state.form.isPostingReview = true;

				const postReview = yield fetch( form.getAttribute( 'action' ), {
					method: form.method,
					body,
				} );
				const response = yield postReview.json();

				state.form.isPostingReview = false;

				if ( ! response.success ) {
					throw new Error( response.data.message );
				}

				callbacks.setFormFeedback(
					true,
					callbacks.getFeedback( response.data?.message )
				);
				form.reset();
				state.reviews.forceReload = true;

				if ( response.data?.redirectTo ) {
					yield callbacks.navigate( response.data.redirectTo, false );
				}
			} catch ( error ) {
				callbacks.setFormFeedback(
					false,
					callbacks.getFeedback( error.message )
				);
			}
		} ),
		resetRating() {
			const { ref: form } = getElement();

			window.requestAnimationFrame( () => {
				form.querySelector( '[name="rating"]' )?.dispatchEvent(
					new Event( 'change' )
				);
			} );
		},
		showReviewForm() {
			state.form.isVisible = true;
			callbacks.scrollTo( REVIEW_FORM );
		},
	},
	callbacks: {
		*navigate( url, scrollToComments = true ) {
			state.reviews.isLoading = true;

			const { actions: router } = yield import(
				'@wordpress/interactivity-router'
			);

			yield router.navigate( url, { force: state.reviews.forceReload } );

			state.reviews.isLoading = false;

			if ( scrollToComments ) {
				callbacks.scrollTo( COMMENTS, delayedShowMenu );
			}
		},
		renderFormFeedback() {
			const { ref: el } = getElement();
			el.innerHTML = state.form.feedback.message;
		},
		getFeedback( code ) {
			const { i18n } = getConfig();

			if ( ! i18n ) {
				return code;
			}

			return i18n[ code ] ?? i18n.comment_save_error;
		},
		*fetchForm() {
			const { ajaxUrl } = getConfig();

			if ( ! ajaxUrl ) {
				return;
			}

			const { ref: el } = getElement();
			const { productId } = el.dataset;

			if ( ! productId ) {
				return;
			}

			const body = new FormData();

			body.append( 'action', 'get_product_review_form' );
			body.append( 'product_id', productId );

			try {
				const response = yield fetch( ajaxUrl, {
					method: 'POST',
					body,
				} );
				const formConfig = yield response.json();

				if ( ! formConfig.success ) {
					throw new Error();
				}

				if ( formConfig.data ) {
					state.form.config = formConfig.data;
				}
			} catch {
				const { i18n } = getConfig();
				el.innerHTML = i18n.generic_error ?? GENERIC_ERROR;
			} finally {
				state.form.isFetched = true;
			}
		},
		scrollTo( elementId, callback ) {
			window.requestAnimationFrame( () => {
				const element = document.querySelector( elementId );

				if ( element ) {
					element.scrollIntoView();

					if ( callback && 'function' === typeof callback ) {
						callback();
					}
				}
			} );
		},
		setFormFeedback( success, message ) {
			state.form.feedback.status = success;
			state.form.feedback.message = message;
			callbacks.scrollTo( REVIEW_FORM_FEEDBACK );
		},
		resetFormFeedback() {
			state.form.feedback.status = null;
			state.form.feedback.message = null;
		},
	},
} );
