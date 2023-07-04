<?php

$license            = get_option( 'modula_pro_license_key' );
$status             = get_option( 'modula_pro_license_status', false );
$alternative_server = get_option( 'modula_pro_alernative_server' );
$messages           = array(
		'no-license'       => esc_html__( 'Enter your license key.', 'modula-pro' ),
		'activate-license' => esc_html__( 'Activate your license key.', 'modula-pro' ),
		'all-good'         => __( 'Your license is active until <strong>%s</strong>.', 'modula-pro' ),
		'lifetime'         => __( 'Your license is active <strong>forever</strong>.', 'modula-pro' ),
		'expired'          => esc_html__( 'Your license has expired.', 'modula-pro'),
);

$license_message = '';

if ( '' == $license ) {
	$license_message = $messages['no-license'];
} elseif ( '' != $license && $status === false ) {
	$license_message = $messages['activate-license'];
} elseif ( $status->license === 'expired' ) {
	$license_message = $messages['expired'];
} elseif ( '' != $license && $status !== false && isset( $status->license ) && $status->license == 'valid' ) {
	$date_format = get_option( 'date_format' );

	if ( 'lifetime' == $status->expires ) {
		$license_message = $messages['lifetime'];
	} else {
		$license_expire = date( $date_format, strtotime( $status->expires ) );
		$curr_time      = time();
		// weeks till expiration
		$weeks = (int) ( ( strtotime( $status->expires ) - $curr_time ) / ( 7 * 24 * 60 * 60 ) );

		// set license status based on colors
		if ( 4 >= $weeks ) {
			$l_stat = 'red';
		}  else {
			$l_stat = 'green';
		}

		$license_message = sprintf( '<p class="%s">' . $messages['all-good'] . '</p>', $l_stat, $license_expire );

		if ( 'green' != $l_stat ) {
			$license_message .= sprintf( __( 'You have %s week(s) untill your license will expire.', 'modula-pro' ), $weeks );
		}

	}
}


?>
<div class="row">
	<?php do_action( 'modula_license_errors' ) ?>
	<table class="form-table">
		<tbody>
		<tr valign="top">
			<th scope="row" valign="top">
				<?php esc_html_e( 'License Key', 'modula-pro' ); ?>
			</th>
			<td>
				<input id="modula_pro_license_key" name="modula_pro_license_key" type="password" class="regular-text"
					   value="<?php echo esc_attr( $license ); ?>"/>
				<?php
				if ( '' !== $license && $status && ( 'expired' === $status->license || ( isset( $l_stat ) && 'green' !== $l_stat ) ) ) {
					echo '<a href="' . esc_url( MODULA_PRO_STORE_URL ) . '/checkout/?edd_action=apply_license_renewal&edd_license_key=' . $license . '" class="button button-primary" target="_blank">Renew license</a>';
				}
				?>
				<span class="license_activation_status"></span>
				<label class="description modula-license-label" for="modula_pro_license_key">
					<?php echo wp_kses_post($license_message); ?>
				</label>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row" valign="top">
				<?php esc_html_e( 'Action', 'modula-pro' ); ?>
			</th>
			<td>
				<?php if ( $status !== false && isset( $status->license ) && $status->license == 'valid' ){ ?>
					<a href="#" class="button-secondary"
					   id="modula_pro_license_deactivate"><?php esc_html_e( 'Deactivate License', 'modula-pro' ); ?></a>
				<?php } else { ?>
					<a href="#" class="button-secondary"
					   id="modula_pro_license_activate"><?php esc_html_e( 'Activate License', 'modula-pro' ); ?></a>
				<?php } ?>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row" valign="top">
				<?php esc_html_e( 'Use Alternative Server', 'modula-pro' ); ?>
				<div class="license-tooltip modula-tooltip"><span>[?]</span>
					<div class="modula-tooltip-content"><?php echo esc_html__( 'Sometimes there can be problems with the activation server, in which case please try the alternative one.', 'modula-pro' ); ?></div>
				</div>
			</th>
			<td>
				<div class="modula-toggle">
					<input class="modula-toggle__input" type="checkbox"
						   data-setting="modula_pro_alernative_server"
						   id="modula_pro_alernative_server"
						   name="modula_pro_alernative_server"
						   value="1" <?php checked( 'true', $alternative_server, true ) ?>>
					<div class="modula-toggle__items">
						<span class="modula-toggle__track"></span>
						<span class="modula-toggle__thumb"></span>
						<svg class="modula-toggle__off" width="6" height="6" aria-hidden="true" role="img"
							 focusable="false" viewBox="0 0 6 6">
							<path d="M3 1.5c.8 0 1.5.7 1.5 1.5S3.8 4.5 3 4.5 1.5 3.8 1.5 3 2.2 1.5 3 1.5M3 0C1.3 0 0 1.3 0 3s1.3 3 3 3 3-1.3 3-3-1.3-3-3-3z"></path>
						</svg>
						<svg class="modula-toggle__on" width="2" height="6" aria-hidden="true" role="img"
							 focusable="false" viewBox="0 0 2 6">
							<path d="M0 0h2v6H0z"></path>
						</svg>
					</div>
				</div>
			</td>
		</tr>
		</tbody>
	</table>
	<!--		<?php /*submit_button(); */ ?>
	</form>-->
</div>