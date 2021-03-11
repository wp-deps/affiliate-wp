<?php
/**
 * Coupon functions
 *
 * @package     AffiliateWP
 * @subpackage  Core/Coupons
 * @copyright   Copyright (c) 2019, AffiliateWP, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.6
 */

/**
 * Retrieves the coupon object.
 *
 * @since 2.6
 *
 * @param AffWP\Affiliate\Coupon|int|string $coupon Coupon object, coupon ID, or coupon code.
 * @return AffWP\Affiliate\Coupon|false Coupon object if found, otherwise false.
 */
function affwp_get_coupon( $coupon ) {

	if ( is_object( $coupon ) && isset( $coupon->coupon_id ) ) {
		$coupon_id = $coupon->coupon_id;
	} elseif ( is_int( $coupon ) ) {
		$coupon_id = $coupon;
	} elseif ( is_string( $coupon ) ) {
		$coupon = affiliate_wp()->affiliates->coupons->get_by( 'coupon_code', $coupon );

		if ( $coupon ) {
			$coupon_id = $coupon->coupon_id;
		} else {
			return false;
		}
	} else {
		return false;
	}

	return affiliate_wp()->affiliates->coupons->get_object( $coupon_id );
}

/**
 * Retrieves an affiliate's coupon.
 *
 * @since 2.6
 *
 * @param int|AffWP\Affiliate $affiliate Affiliate ID or object.
 * @param int|string          $coupon    Coupon ID or code.
 * @return AffWP\Affiliate\Coupon|\WP_Error Coupon object associated with the given affiliate and coupon or WP_Error object.
 */
function affwp_get_affiliate_coupon( $affiliate, $coupon ) {

	if ( ! $affiliate = affwp_get_affiliate( $affiliate ) ) {
		return new \WP_Error( 'invalid_coupon_affiliate', __( 'Invalid affiliate', 'affiliate-wp' ) );
	}

	$args = array(
		'affiliate_id' => $affiliate->ID,
		'number'       => 1,
	);

	if ( is_string( $coupon ) ) {
		$args['coupon_code'] = $coupon;
	} elseif ( is_numeric( $coupon ) ) {
		$args['coupon_id'] = intval( $coupon );
	}

	$affiliate_coupon = affiliate_wp()->affiliates->coupons->get_coupons( $args );

	if ( ! empty( $affiliate_coupon ) ) {
		return $affiliate_coupon[0];
	}

	return new \WP_Error( 'no_coupons', __( 'No coupons were found.', 'affiliate-wp' ) );
}

/**
 * Retrieves an affiliate's coupon code.
 *
 * @since 2.6
 *
 * @param int|AffWP\Affiliate $affiliate Affiliate ID or object.
 * @param int                 $coupon_id Coupon ID.
 * @return string Affiliate's coupon code or empty string.
 */
function affwp_get_affiliate_coupon_code( $affiliate, $coupon_id ) {

	$coupon_code = '';

	if ( ! is_numeric( $coupon_id ) ) {
		return $coupon_code;
	}

	$coupon = affwp_get_affiliate_coupon( $affiliate, intval( $coupon_id ) );

	if ( ! is_wp_error( $coupon ) ) {
		$coupon_code = $coupon->coupon_code;
	}

	return $coupon_code;
}

/**
 * Sanitizes a global affiliate coupon code.
 *
 * @since 2.6
 *
 * @param string $code Raw coupon code.
 * @return string Sanitized coupon code.
 */
function affwp_sanitize_coupon_code( $code ) {
	$code = strtoupper( sanitize_key( $code ) );

	return $code;
}

/**
 * Retrieves an options list of integrations that supports dynamic coupons and their respective labels.
 *
 * @since 2.6
 *
 * @return array Options array where the key is the integration ID and value is the integration name.
 */
function affwp_get_dynamic_coupons_integrations() {

	$dynamic_coupon_enabled_integrations = affiliate_wp()->integrations->query( array(
		'supports' => 'dynamic_coupons',
		'status'   => 'enabled',
		'fields'   => array( 'ids', 'name' ),
	) );

	if ( ! is_wp_error( $dynamic_coupon_enabled_integrations ) ) {
		$dynamic_coupon_integrations = $dynamic_coupon_enabled_integrations;
	} else {
		$dynamic_coupon_integrations = array();
	}

	return $dynamic_coupon_integrations;
}

/**
 * Retrieves an options list of integrations that supports manual coupons and their respective labels.
 *
 * @since 2.6
 *
 * @return array Options array where the key is the integration ID and value is the integration name.
 */
function affwp_get_manual_coupons_integrations() {

	$manual_coupon_enabled_integrations = affiliate_wp()->integrations->query( array(
		'supports' => 'manual_coupons',
		'status'   => 'enabled',
		'fields'   => array( 'ids', 'name' ),
	) );

	if ( ! is_wp_error( $manual_coupon_enabled_integrations ) ) {
		$manual_coupon_integrations = $manual_coupon_enabled_integrations;
	} else {
		$manual_coupon_integrations = array();
	}

	return $manual_coupon_integrations;
}

/**
 * Checks if dynamic coupons is setup.
 *
 * @since 2.6
 *
 * @return bool True if dynamic coupons is setup, false otherwise.
 */
function affwp_dynamic_coupons_is_setup() {

	$dynamic_coupons_setup = false;

	$enabled_coupons_integrations = affwp_get_dynamic_coupons_integrations();

	if ( $enabled_coupons_integrations ) {

		$dynamic_coupon_template = affiliate_wp()->settings->get( 'coupon_template_woocommerce' );

		if ( $dynamic_coupon_template ) {
			$dynamic_coupons_setup = true;
		}

	}

	return $dynamic_coupons_setup;
}

/**
 * Retrieve all coupons assigned to the affiliate.
 *
 * @since 2.6
 *
 * @param int|\AffWP\Affiliate $affiliate    Affiliate ID or object.
 * @param bool                 $details_only Optional. Whether to retrieve the coupon details only (for display).
 *                                           Default true. If false, the full coupon objects will be retrieved.
 * @return array[]|\AffWP\Affiliate\Coupon[]|array Array of arrays of coupon details, an array of coupon objects,
 *                                                 dependent upon whether `$details_only` is true or false,
 *                                                 respectively, otherwise an empty array.
 */
function affwp_get_affiliate_coupons( $affiliate, $details_only = true ) {

	$coupons = array();

	if ( ! $affiliate = affwp_get_affiliate( $affiliate ) ) {
		return $coupons;
	}

	$manual_coupons  = affwp_get_manual_affiliate_coupons( $affiliate, $details_only );
	$dynamic_coupons = affwp_get_dynamic_affiliate_coupons( $affiliate, $details_only );

	if ( ! empty( $manual_coupons ) ) {
		foreach ( $manual_coupons as $coupon_id => $coupon ) {
			$coupons['manual'][ $coupon_id ] = $coupon;
		}
	}

	if ( ! empty( $dynamic_coupons ) ) {
		foreach ( $dynamic_coupons as $coupon_id => $coupon ) {
			$coupons['dynamic'][ $coupon_id ] = $coupon;
		}
	}

	/**
	 * Filters the list of coupons associated with an affiliate.
	 *
	 * @since 2.6
	 *
	 * @param array $coupons      The affiliate's coupons.
	 * @param int   $affiliate_id Affiliate ID.
	 * @param bool  $details_only Whether only details (for display use) were retrieved or not.
	 */
	return apply_filters( 'affwp_get_affiliate_coupons', $coupons, $affiliate->ID, $details_only );
}

/**
 * Retrieves all manual coupons associated with an affiliate.
 *
 * @since 2.6
 *
 * @param int|\AffWP\Affiliate $affiliate    Affiliate ID or object.
 * @param bool                 $details_only Optional. Whether to retrieve the coupon details only (for display).
 *                                           Default true. If false, the full coupon objects will be retrieved.
 * @return array[]|\AffWP\Affiliate\Coupon[]|array Array of arrays of coupon details, an array of coupon objects,
 *                                                 dependent upon whether `$details_only` is true or false,
 *                                                 respectively, otherwise an empty array.
 */
function affwp_get_manual_affiliate_coupons( $affiliate, $details_only = true ) {
	$coupons = array();

	if ( ! $affiliate = affwp_get_affiliate( $affiliate ) ) {
		return $coupons;
	}

	$manual_coupons_integrations = affwp_get_manual_coupons_integrations();

	if ( ! empty( $manual_coupons_integrations ) ) {
		$coupons = array();

		$integrations = array_keys( $manual_coupons_integrations );

		if ( ! empty( $integrations ) ) {
			foreach ( $integrations as $integration ) {
				$integration = affiliate_wp()->integrations->get( $integration );

				if ( ! is_wp_error( $integration ) && $integration->is_active() ) {
					$integration_coupons = $integration->get_coupons_of_type( 'manual', $affiliate, $details_only );

					if ( ! empty( $integration_coupons ) ) {
						foreach ( $integration_coupons as $coupon_id => $coupon ) {
							$coupons[ $coupon_id ] = $coupon;
						}
					}
				}
			}
		}
	}

	/**
	 * Filters the list of manual coupons associated with an affiliate.
	 *
	 * @since 2.6
	 *
	 * @param array $coupons      The affiliate's coupons.
	 * @param int   $affiliate_id Affiliate ID.
	 * @param bool  $details_only Whether only details (for display use) were retrieved or not.
	 */
	return apply_filters( 'affwp_get_manual_affiliate_coupons', $coupons, $affiliate->ID, $details_only );
}

/**
 * Retrieves all dynamic coupons associated with an affiliate.
 *
 * @since 2.6
 *
 * @param int|\AffWP\Affiliate $affiliate    Affiliate ID or object.
 * @param bool                 $details_only Optional. Whether to retrieve the coupon details only (for display).
 *                                           Default true. If false, the full coupon objects will be retrieved.
 * @return array[]|\AffWP\Affiliate\Coupon[]|array Array of arrays of coupon details, an array of coupon objects,
 *                                                 dependent upon whether `$details_only` is true or false,
 *                                                 respectively, otherwise an empty array.
 */
function affwp_get_dynamic_affiliate_coupons( $affiliate, $details_only = true ) {
	$coupons = array();

	if ( ! $affiliate = affwp_get_affiliate( $affiliate ) ) {
		return $coupons;
	}

	$dynamic_coupons_integrations = affwp_get_dynamic_coupons_integrations();

	if ( ! empty( $dynamic_coupons_integrations ) ) {
		$coupons = array();

		$integrations = array_keys( $dynamic_coupons_integrations );

		if ( ! empty( $integrations ) ) {
			foreach ( $integrations as $integration ) {
				$integration = affiliate_wp()->integrations->get( $integration );

				if ( ! is_wp_error( $integration ) && $integration->is_active() ) {
					$integration_coupons = $integration->get_coupons_of_type( 'dynamic', $affiliate, $details_only );

					if ( ! empty( $integration_coupons ) ) {
						foreach ( $integration_coupons as $coupon_id => $coupon ) {
							$coupons[ $coupon_id ] = $coupon;
						}
					}
				}

			}

			// Remove duplicates (i.e. globally dynamic coupons).
			if ( ! empty( $coupons ) ) {
				$coupons = array_unique( $coupons, SORT_REGULAR );
			}
		}
	}

	/**
	 * Filters the list of dynamic coupons associated with an affiliate.
	 *
	 * @since 2.6
	 *
	 * @param array $coupons      The affiliate's coupons.
	 * @param int   $affiliate_id Affiliate ID.
	 * @param bool  $details_only Whether only details (for display use) were retrieved or not.
	 */
	return apply_filters( 'affwp_get_dynamic_affiliate_coupons', $coupons, $affiliate->ID, $details_only );
}

/**
 * Retrieves a list of potential coupon types.
 *
 * @since 2.6
 *
 * @return array Array of coupon types.
 */
function affwp_get_coupon_types() {
	return array( 'manual', 'dynamic' );
}

/**
 * Retrieves a list of coupon type labels.
 *
 * @since 2.6
 *
 * @return array Coupon type labels, keyed by coupon type.
 */
function affwp_get_coupon_type_labels() {
	return array(
		'manual' => __( 'Manual', 'affiliate-wp' ),
		'dynamic' => __( 'Dynamic', 'affiliate-wp' ),
	);
}
