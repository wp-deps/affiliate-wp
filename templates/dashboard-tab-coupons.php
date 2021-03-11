<?php
$affiliate_id = affwp_get_affiliate_id();
$all_coupons  = affwp_get_affiliate_coupons( $affiliate_id );
?>

<div id="affwp-affiliate-dashboard-coupons" class="affwp-tab-content">

	<h4><?php _e( 'Coupons', 'affiliate-wp' ); ?></h4>

	<?php
	/**
	 * Fires at the top of the Coupons dashboard tab.
	 *
	 * @since 2.6
	 *
	 * @param int $affiliate_id Affiliate ID of the currently logged-in affiliate.
	 */
	do_action( 'affwp_affiliate_dashboard_coupons_top', $affiliate_id );
	?>

	<?php
	/**
	 * Fires right before displaying the affiliate coupons dashboard table.
	 *
	 * @since 2.6
	 *
	 * @param int $affiliate_id Affiliate ID.
	 */
	do_action( 'affwp_coupons_dashboard_before_table', $affiliate_id ); ?>

	<?php if ( ! empty( $all_coupons ) ) : ?>
		<table class="affwp-table affwp-table-responsive">
			<thead>
				<tr>
					<th><?php _e( 'Coupon Code', 'affiliate-wp' ); ?></th>
					<th><?php _e( 'Amount', 'affiliate-wp' ); ?></th>
					<?php
					/**
					 * Fires right after displaying the last affiliate coupons dashboard table header.
					 *
					 * @since 2.6
					 *
					 * @param int $affiliate_id Affiliate ID.
					 */
					do_action( 'affwp_coupons_dashboard_th' ); ?>
				</tr>
			</thead>

			<tbody>

			<?php if ( $all_coupons ) :
				foreach ( $all_coupons as $type => $coupons ) :
					foreach ( $coupons as $coupon ) : ?>
						<tr>
							<td data-th="<?php esc_attr_e( 'Coupon Code', 'affiliate-wp' ); ?>"><?php echo $coupon['code']; ?></td>
							<td data-th="<?php esc_attr_e( 'Amount', 'affiliate-wp' ); ?>"><?php echo $coupon['amount']; ?></td>
							<?php
							/**
							 * Fires right after displaying the last affiliate coupons dashboard table data.
							 *
							 * @since 2.6
							 *
							 * @param array $coupons Coupons array.
							 */
							do_action( 'affwp_coupons_dashboard_td', $coupon ); ?>
						</tr>
					<?php endforeach; ?>
				<?php endforeach; ?>
			<?php endif; ?>

			</tbody>
		</table>
	<?php else : ?>
		<p><?php _e( 'There are currently no coupon codes to display.', 'affiliate-wp' ); ?></p>
	<?php endif; ?>

	<?php
	/**
	 * Fires right after displaying the affiliate coupons dashboard table.
	 *
	 * @since 2.6
	 *
	 * @param int $affiliate_id Affiliate ID.
	 */
	do_action( 'affwp_coupons_dashboard_after_table', $affiliate_id ); ?>

</div>
