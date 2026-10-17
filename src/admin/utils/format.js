import { config } from './config';

let formatter;
const getFormatter = () => {
	if ( ! formatter ) {
		try {
			formatter = new Intl.NumberFormat( config.locale, {
				style: 'currency',
				currency: config.currency,
			} );
		} catch ( e ) {
			formatter = new Intl.NumberFormat( undefined, {
				style: 'currency',
				currency: 'CAD',
			} );
		}
	}
	return formatter;
};

/**
 * Minor units per major unit (100 for cents), from the currency's decimals.
 */
export const minorFactor = () =>
	10 ** getFormatter().resolvedOptions().maximumFractionDigits;

/**
 * Formats an amount stored in minor units (e.g. cents).
 *
 * @param {number} minor
 * @return {string} Formatted amount.
 */
export const formatMoney = ( minor ) =>
	getFormatter().format( ( Number( minor ) || 0 ) / minorFactor() );

export const toMajor = ( minor ) =>
	minor === null || minor === undefined
		? undefined
		: Number( minor ) / minorFactor();

export const toMinor = ( major ) => {
	const value = Number( major );
	return Number.isFinite( value ) ? Math.round( value * minorFactor() ) : 0;
};

/**
 * Quantities have up to 3 decimals; trailing zeros are dropped.
 *
 * @param {number} value
 * @return {string} Formatted quantity.
 */
export const formatQty = ( value ) =>
	new Intl.NumberFormat( config.locale, { maximumFractionDigits: 3 } ).format(
		Number( value ) || 0
	);

export const roundQty = ( value ) =>
	Math.round( ( Number( value ) || 0 ) * 1000 ) / 1000;
