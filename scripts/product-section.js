import Slider from './product-slider';

export default class ProductSlider {
	static SECTION_CLASS = 'product-section';
	static SPINNER_CLASS = '.product-section__spinner';
	static ERROR_MISSING_PRODUCT_SECTION = 'Missing slider element';
	static ERROR_MISSING_FETCH_URL = 'Missing URL for fetching products';
	static PRODUCT_LIST = 'ul.products';
	static AJAX_ACTION = 'get_product_section';

	constructor( productSection ) {
		this.productSection = productSection;

		if ( ! this.productSection ) {
			throw new Error( ProductSlider.ERROR_MISSING_PRODUCT_SECTION );
		}

		this.fetchProducts();
	}

	getFetchUrl( element ) {
		const fetchUrl = new URL( window.chocante.ajaxurl );
		fetchUrl.searchParams.append( 'action', ProductSlider.AJAX_ACTION );

		for ( const [ key, value ] of Object.entries( element.dataset ) ) {
			fetchUrl.searchParams.append( key, value );
		}

		if ( window.chocante.lang ) {
			fetchUrl.searchParams.append( 'lang', window.chocante.lang );
		}

		if ( window.chocante.currency ) {
			fetchUrl.searchParams.append(
				'currency',
				window.chocante.currency
			);
		}

		return fetchUrl;
	}

	async fetchProducts() {
		const fetchUrl = this.getFetchUrl( this.productSection );

		try {
			const response = await fetch( fetchUrl );
			const featuredHtml = await response.text();

			if ( featuredHtml ) {
				const products = this.shuffleProducts( featuredHtml );
				const spinnerElement = this.productSection.querySelector(
					ProductSlider.SPINNER_CLASS
				);

				if ( spinnerElement ) {
					spinnerElement.remove();
				}

				this.productSection.insertAdjacentHTML( 'beforeend', products );
				new Slider( this.productSection.className );
			}
		} catch ( error ) {
			throw new Error( error );
		}
	}

	shuffleProducts( featuredHtml ) {
		const parser = new DOMParser();
		const doc = parser.parseFromString( featuredHtml, 'text/html' );
		const ul = doc.querySelector( ProductSlider.PRODUCT_LIST );
		const items = Array.from( ul.children );
		const productId = window.chocante.productId;

		// Shuffle items.
		for ( let i = items.length - 1; i > 0; i-- ) {
			const j = Math.floor( Math.random() * ( i + 1 ) );
			[ items[ i ], items[ j ] ] = [ items[ j ], items[ i ] ];
		}

		ul.innerHTML = '';

		items.forEach( ( item, index, products ) => {
			// Remove current product.
			if (
				products.length > 1 &&
				productId &&
				item.classList.contains( `post-${ productId }` )
			) {
				return;
			}

			ul.appendChild( item );
		} );

		return doc.body.innerHTML;
	}
}

const productSection = document.querySelectorAll(
	`.${ ProductSlider.SECTION_CLASS }`
);

for ( const section of Array.from( productSection ) ) {
	new ProductSlider( section );
}
