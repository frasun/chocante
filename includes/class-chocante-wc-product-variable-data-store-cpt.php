<?php
/**
 * Variable data store override
 *
 * @package WordPress
 * @subpackage Chocante
 */

/**
 * Override native class method
 */
class Chocante_WC_Product_Variable_Data_Store_CPT extends WC_Product_Variable_Data_Store_CPT {
	/**
	 * Check if the prices for a product will be different with or without taxes.
	 *
	 * @param WC_Product $product Product to check.
	 * @return bool True if the prices will be different with or without taxes.
	 */
	protected function taxes_influence_price( $product ): bool {
		if ( ! $product->is_taxable() ) {
			return false;
		}

		if ( empty( WC_Tax::get_rates( $product->get_tax_class() ) ) ) {
			return false;
		}

		return true;
	}
}
