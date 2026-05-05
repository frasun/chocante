export default class POD {
	static ERROR_INIT = '[POD service] Missing required data';
	static ERROR_FETCH = '[POD service] Error fetching data';

	constructor( apiUrl, courier ) {
		this.apiUrl = apiUrl;
		this.courier = courier;
		this.points = [];

		if ( ! this.apiUrl ) {
			throw new Error( POD.ERROR_INIT );
		}
	}

	getPoints( postalCode, country ) {
		if ( postalCode === this.postalCode && country === this.country ) {
			return this.fetchPromise ? this.fetchPromise : this.points;
		}

		this.fetchPromise = this.fetchData( postalCode, country ).finally(
			() => {
				this.fetchPromise = null;
			}
		);

		return this.fetchPromise;
	}

	async fetchData( postalCode, country ) {
		this.postalCode = postalCode;
		this.country = country;

		try {
			const pointsUrl = new URL( this.apiUrl );
			pointsUrl.searchParams.append( 'country', this.country );
			pointsUrl.searchParams.append(
				'postcode',
				this.normalizePostcode( this.postalCode )
			);
			pointsUrl.searchParams.append( 'courier', this.courier );

			const response = await fetch( pointsUrl );
			const pointsData = await response.json();

			if ( ! pointsData.success ) {
				throw new Error( POD.ERROR_FETCH );
			}

			this.points = this.preparePointsData( pointsData );
		} catch {
			this.points = [];
		} finally {
			return this.points;
		}
	}

	preparePointsData( pointsData ) {
		return Object.values( pointsData.data.PudoPoints ).map(
			( {
				description,
				point_id: pointId,
				address,
				city,
				postal_code: postalCode,
			} ) => ( {
				id: pointId,
				text: `${ pointId } - ${ address }, ${ postalCode } ${ city } (${ description })`,
			} )
		);
	}

	normalizePostcode( postalCode ) {
		return postalCode
			.trim()
			.toUpperCase()
			.replace( /[\s\-]/g, '' );
	}
}
