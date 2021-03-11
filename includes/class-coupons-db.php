<?php
/**
 * Coupons database abstraction layer
 *
 * @package     AffiliateWP
 * @subpackage  Core/Database
 * @copyright   Copyright (c) 2019, AffiliateWP, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.3
 */

/**
 * Coupons database class.
 *
 * @since 2.6
 *
 * @see Affiliate_WP_DB
 */
class Affiliate_WP_Coupons_DB extends Affiliate_WP_DB {

	/**
	 * Cache group for queries.
	 *
	 * @internal DO NOT change. This is used externally both as a cache group and shortcut
	 *           for accessing db class instances via affiliate_wp()->{$cache_group}->*.
	 *
	 * @since 2.6
	 * @var   string
	 */
	public $cache_group = 'coupons';

	/**
	 * Database group value.
	 *
	 * @since 2.6
	 * @var string
	 */
	public $db_group = 'affiliates:coupons';

	/**
	 * Object type to query for.
	 *
	 * @since 2.6
	 * @var   string
	 */
	public $query_object_type = 'AffWP\Affiliate\Coupon';

	/**
	 * Get things started.
	 *
	 * @since 2.6
	*/
	public function __construct() {
		global $wpdb, $wp_version;

		if ( defined( 'AFFILIATE_WP_NETWORK_WIDE' ) && AFFILIATE_WP_NETWORK_WIDE ) {
			// Allows a single coupons table for the whole network.
			$this->table_name  = 'affiliate_wp_coupons';
		} else {
			$this->table_name  = $wpdb->prefix . 'affiliate_wp_coupons';
		}
		$this->primary_key = 'coupon_id';
		$this->version     = '1.1';
	}

	/**
	 * Retrieves a coupon record.
	 *
	 * @since 2.6
	 *
	 * @see Affiliate_WP_DB::get_core_object()
	 *
	 * @param int|AffWP\Affiliate\Coupon $coupon Coupon ID or object.
	 * @return AffWP\Affiliate\Coupon|false Coupon object, otherwise false.
	 */
	public function get_object( $coupon ) {
		return $this->get_core_object( $coupon, $this->query_object_type );
	}

	/**
	 * Defines the database columns and their default formats.
	 *
	 * @since 2.6
	*/
	public function get_columns() {
		return array(
			'coupon_id'    => '%d',
			'affiliate_id' => '%d',
			'coupon_code'  => '%s',
		);
	}

	/**
	 * Get default column values
	 *
	 * @access public
	 * @since  2.6
	 */
	public function get_column_defaults() {
		return array(
			'affiliate_id' => 0,
		);
	}

	/**
	 * Retrieves coupons from the database.
	 *
	 * @since 2.6
	 *
	 * @param array $args {
	 *     Optional. Arguments for querying coupons. Default empty array.
	 *
	 *     @type int          $number       Number of coupons to query for. Default 20.
	 *     @type int          $offset       Number of coupons to offset the query for. Default 0.
	 *     @type int|array    $coupon_id    Coupon ID or array of IDs to explicitly retrieve. Default 0 (all).
	 *     @type int|array    $affiliate_id Affiliate ID or array of IDs to explicitly retrieve. Default empty.
	 *     @type string|array $coupon_code  Coupon code or array of coupon codes to explicitly retrieve. Default empty.
	 *     @type string       $order        How to order returned results. Accepts 'ASC' or 'DESC'. Default 'DESC'.
	 *     @type string       $orderby      Coupons table column to order results by. Accepts any AffWP\Affiliate\Coupon
	 *                                      field. Default 'affiliate_id'.
	 *     @type string|array $fields       Specific fields to retrieve. Accepts 'ids', a single coupon field, or an
	 *                                      array of fields. Default '*' (all).
	 * }
	 * @param bool $count Whether to retrieve only the total number of results found. Default false.
	 * @return array|int Array of coupon objects or field(s) (if found), int if `$count` is true.
	 */
	public function get_coupons( $args = array(), $count = false ) {
		global $wpdb;

		$defaults = array(
			'number'       => 20,
			'offset'       => 0,
			'coupon_id'    => 0,
			'affiliate_id' => 0,
			'coupon_code'  => 0,
			'orderby'      => $this->primary_key,
			'order'        => 'ASC',
			'fields'       => '',
		);

		$args = wp_parse_args( $args, $defaults );

		if ( $args['number'] < 1 ) {
			$args['number'] = 999999999999;
		}

		$where = $join = '';

		// Specific coupons.
		if( ! empty( $args['coupon_id'] ) ) {

			$where .= empty( $where ) ? "WHERE " : "AND ";

			if( is_array( $args['coupon_id'] ) ) {
				$coupon_ids = implode( ',', array_map( 'intval', $args['coupon_id'] ) );
			} else {
				$coupon_ids = intval( $args['coupon_id'] );
			}

			$where .= "`coupon_id` IN( {$coupon_ids} ) ";

		}

		// Specific affiliates.
		if ( ! empty( $args['affiliate_id'] ) ) {

			$where .= empty( $where ) ? "WHERE " : "AND ";

			if ( is_array( $args['affiliate_id'] ) ) {
				$affiliate_ids = implode( ',', array_map( 'intval', $args['affiliate_id'] ) );
			} else {
				$affiliate_ids = intval( $args['affiliate_id'] );
			}

			$where .= "`affiliate_id` IN( {$affiliate_ids} ) ";
		}

		// Specific coupon code or codes.
		if ( ! empty( $args['coupon_code'] ) ) {

			$where .= empty( $where ) ? "WHERE " : "AND ";

			if( is_array( $args['coupon_code'] ) ) {
				$where .= "`coupon_code` IN('" . implode( "','", array_map( 'affwp_sanitize_coupon_code', $args['coupon_code'] ) ) . "') ";
			} else {
				$coupons = affwp_sanitize_coupon_code( $args['coupon_code'] );
				$where .= "`coupon_code` = '" . $coupons . "' ";
			}
		}

		// Select valid coupons only.
		$where .= empty( $where ) ? "WHERE " : "AND ";
		$where .= "`$this->primary_key` != ''";

		// There can be only two orders.
		if ( 'ASC' === strtoupper( $args['order'] ) ) {
			$order = 'ASC';
		} else {
			$order = 'DESC';
		}

		$orderby = array_key_exists( $args['orderby'], $this->get_columns() ) ? $args['orderby'] : $this->primary_key;

		// Overload args values for the benefit of the cache.
		$args['orderby'] = $orderby;
		$args['order']   = $order;

		// Fields.
		$callback = '';

		if ( 'ids' === $args['fields'] ) {
			$fields   = "$this->primary_key";
			$callback = 'intval';
		} else {
			$fields = $this->parse_fields( $args['fields'] );

			if ( '*' === $fields ) {
				$callback = 'affwp_get_coupon';
			}
		}

		$key = ( true === $count ) ? md5( 'affwp_coupons_count' . serialize( $args ) ) : md5( 'affwp_coupons_' . serialize( $args ) );

		$last_changed = wp_cache_get( 'last_changed', $this->cache_group );
		if ( ! $last_changed ) {
			$last_changed = microtime();
			wp_cache_set( 'last_changed', $last_changed, $this->cache_group );
		}

		$cache_key = "{$key}:{$last_changed}";

		$results = wp_cache_get( $cache_key, $this->cache_group );

		if ( false === $results ) {

			$clauses = compact( 'fields', 'join', 'where', 'orderby', 'order', 'count' );

			$results = $this->get_results( $clauses, $args, $callback );
		}

		wp_cache_add( $cache_key, $results, $this->cache_group, HOUR_IN_SECONDS );

		return $results;

	}

	/**
	 * Return the number of results found for a given query.
	 *
	 * @since 2.6
	 *
	 * @param array $args Query arguments.
	 * @return int Number of results matching the query.
	 */
	public function count( $args = array() ) {
		return $this->get_coupons( $args, true );
	}

	/**
	 * Adds a new coupon.
	 *
	 * @since 2.6
	 *
	 * @param array $data {
	 *     Optional. Data for adding a new affiliate coupon.
	 *
	 *     @type string $coupon_code  Coupon code to create. If left empty, a code will be auto-generated.
	 *     @type int    $affiliate_id Required. Affiliate ID to associate the coupon code with.
	 * }
	*/
	public function add( $data = array() ) {
		$defaults = array(
			'coupon_code'  => '',
			'affiliate_id' => 0,
		);

		$args = wp_parse_args( $data, $defaults );

		// Bail if the coupon template is not set.
		$woocommerce_coupon_template = affiliate_wp()->settings->get( 'coupon_template_woocommerce', 0 );

		if ( empty( $woocommerce_coupon_template ) && ! defined( 'WP_TESTS_DOMAIN' ) ) {
			return false;
		}

		// Bail if the affiliate ID is invalid.
		if ( empty( $args['affiliate_id'] ) || false === affwp_get_affiliate( $args['affiliate_id'] ) ) {
			return false;
		}

		if ( empty( $args['coupon_code'] ) ) {
			$args['coupon_code'] = $this->generate_code( array( 'affiliate_id' => $args['affiliate_id'] ) );
		} else {
			$args['coupon_code'] = affwp_sanitize_coupon_code( $args['coupon_code'] );
		}

		// Store coupon codes in caps.
		$args['coupon_code'] = strtoupper( $args['coupon_code'] );

		$added = $this->insert( $args, 'coupon' );

		if ( $added ) {
			/**
			 * Fires immediately after a coupon has been added to the database.
			 *
			 * @since 2.6
			 *
			 * @param array $added The coupon data being added.
			 */
			do_action( 'affwp_insert_coupon', $added );

			return $added;
		}

		return false;

	}

	/**
	 * Updates a coupon record in the database.
	 *
	 * @since 2.6
	 *
	 * @param int   $affiliate_id Affiliate ID for the coupon to update.
	 * @param array $data         Optional. Coupon data to update.
	 * @return bool True if the coupon was successfully updated, otherwise false.
	 */
	public function update_coupon( $affiliate_id, $data = array() ) {
		if ( ! $coupon = affwp_get_coupon( $affiliate_id ) ) {
			return false;
		}

		$args = array();

		if ( ! empty( $data['coupon_code'] ) ) {
			$args['coupon_code'] = affwp_sanitize_coupon_code( $data['coupon_code'] );
		}

		$updated = parent::update( $affiliate_id, $args, '', 'coupon' );

		/**
		 * Fires immediately after a coupon update has been attempted.
		 *
		 * @since 2.6
		 * @since 2.6.1 Hook tag fixed to remove inadvertent naming conflict
		 *
		 * @param \AffWP\Affiliate\Coupon $updated_coupon Updated coupon object.
		 * @param \AffWP\Affiliate\Coupon $coupon         Original coupon object.
		 * @param bool                    $updated        Whether the coupon was successfully updated.
		 */
		do_action( 'affwp_updated_coupon', affwp_get_coupon( $affiliate_id ), $coupon, $updated );

		return $updated;
	}

	/**
	 * Retrieves a coupon row based on column and value.
	 *
	 * @since 2.6
	 * @since 2.6.1 Renamed the `$row_id` parameter to `$value`.
	 *
	 * @param  string $column Column name. See get_columns().
	 * @param  mixed  $value  Column value.
	 * @return object|false Database query result object or false on failure.
	 */
	public function get_by( $column, $value ) {
		if ( 'coupon_code' === $column ) {
			$value = strtoupper( $value );
		}

		return parent::get_by( $column, $value );
	}

	/**
	 * Generates a "random" coupon code.
	 *
	 * @since 2.6
	 *
	 * @param array $args {
	 *     Optional. Arguments for modifying the generated coupon code.
	 *
	 *     @type int $affiliate_id Affiliate ID to generate the code for. Default 0 (unused).
	 * }
	 * @return string "Random" coupon code.
	 */
	public function generate_code( $args = array() ) {
		$defaults = array(
			'affiliate_id' => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		$total_length = 10;

		// Generate the actual code.
		$code = wp_generate_password( $total_length, false );

		// Finished coupon code.
		$code = affwp_sanitize_coupon_code( $code );

		/**
		 * Filters the generated affiliate coupon code.
		 *
		 * @since 2.6
		 *
		 * @param string $code Generated coupon code.
		 * @param array  $args Arguments for modifying the generated coupon code.
		 */
		return apply_filters( 'affwp_coupons_generate_code', $code, $args );
	}

	/**
	 * Routine that creates the coupons table.
	 *
	 * @since 2.6
	 */
	public function create_table() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE {$this->table_name} (
			coupon_id bigint(20) NOT NULL AUTO_INCREMENT,
			affiliate_id bigint(20) NOT NULL,
			coupon_code varchar(50) NOT NULL,
			PRIMARY KEY (coupon_id),
			KEY coupon_code (coupon_code)
			) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	}
}
