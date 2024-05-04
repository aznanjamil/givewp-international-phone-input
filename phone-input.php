/**
 * Enhancements for GiveWP phone fields with international phone input using jQuery and few other functionality.
 * Choose country code you need
 * Save phone number to donation and donor meta
 * Show donor phone numbers on donor profile
 * add a new custom field to export.
 */
function give_add_donor_phone_form_field($form_id) {
    // Inline styles and scripts are included here to ensure they apply within the iframe.
    echo '<style>
        .intl-tel-input {
            width: 100%!important;
        }
        .country-list {
            width: 300px!important;
        }
    </style>';

    ?>
    <p id="give-phone-wrap" class="form-row form-row-wide">
        <label for="give-phone" class="give-label"><?php esc_html_e('Phone', 'give'); ?>
            <?php if (give_field_is_required('give_phone', $form_id)): ?>
                <span class="give-required-indicator">*</span>
            <?php endif; ?>
            <?php echo Give()->tooltips->render_help(__('Include your country code.', 'give')); ?>
        </label>
        <input type="tel" name="give_phone" id="give-phone" class="give-input required" placeholder="<?php esc_html_e('e.g., +60123456789', 'give'); ?>" required>
    </p>
    <script>
        jQuery(document).ready(function($) {
            $('#give-phone').intlTelInput({
                utilsScript: 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js',
				//list all the country you need
                onlyCountries: ['my', 'bn', 'sg'],
                initialCountry: 'auto',
                geoIpLookup: function(callback) {
                    $.get('https://ipinfo.io', function() {}, 'jsonp').always(function(resp) {
                        var countryCode = (resp && resp.country) ? resp.country : '';
                        callback(countryCode);
                    });
                }
            });
        });
    </script>
    <?php
}
add_action('give_donation_form_after_email', 'give_add_donor_phone_form_field');


/**
 * Set donor phone form field as required
 *
 * @param array $required_fields List of required fields.
 * @param int   $form_id         Donation Form ID.
 *
 * @return array
 */
function give_required_donor_phone_form_field( $required_fields, $form_id ){

	$required_fields['give_phone'] = array(
		'error_id'      => 'invalid_phone',
		'error_message' => __( 'Please enter phone number.', 'give' ),
	);

	return $required_fields;
}
add_action( 'give_donation_form_required_fields', 'give_required_donor_phone_form_field', 10, 2 );

/**
 * Save phone number to donation and donor meta
 * Note: donor phone will update in donor meta if donor changes the phone number.
 * So on a second donation with a new number, the old number will be changed in the DONOR meta, 
 * but the donation meta of the first donation will have the old number.
 *
 * @param int $donation_id Donation ID.
 */
function give_save_donor_phone_number( $donation_id ){

	$donor_id         = give_get_payment_donor_id( $donation_id );
	$new_phone_number = give_clean( $_POST['give_phone'] );
	$phone_numbers    = Give()->donor_meta->get_meta( $donor_id, 'give_phone' );

	// Add phone number to donor meta only if not exist.
	if ( ! in_array( $new_phone_number, $phone_numbers, true ) ) {
		Give()->donor_meta->add_meta( $donor_id, 'give_phone', $new_phone_number );
	}

	// Save phone number to donation meta.
	Give()->payment_meta->update_meta( $donation_id, '_give_phone', $new_phone_number );
}
add_action( 'give_insert_payment', 'give_save_donor_phone_number', 10 );

/**
 * Show donor phone numbers on donor profile
 *
 * @param Give_Donor $donor Donor Object.
 */
function give_show_donor_phone_numbers( $donor ) {
	$phone_numbers = $donor->get_meta( 'give_phone', false );
	?>
	<div class="donor-section clear">
		<h3><?php esc_html_e( 'Phone Numbers', 'give' ); ?></h3>

		<div class="postbox">
			<div class="inside">
				<?php if ( empty( $phone_numbers ) ) : ?>
					<p><?php esc_html_e( 'This donor does not have any phone number saved.', 'give' ); ?></p>
				<?php else: ?>
					<?php foreach ( $phone_numbers as $phone_number ) : ?>
						<p><?php echo $phone_number; ?></p>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php
}
add_action( 'give_donor_before_address', 'give_show_donor_phone_numbers' );

/**
 * This function will add a new custom field to export.
 *
 * @param array $default_columns List of default columns.
 *
 * @return array
 */
function give_add_custom_column_to_export_donor( $default_columns ) {

	$default_columns['phone_number'] = esc_html__( 'Phone Number', 'give' );

	return $default_columns;
}
add_filter( 'give_export_donors_get_default_columns', 'give_add_custom_column_to_export_donor' );

/**
 * This function will be used to set the value of new custom field which will be displayed in exported CSV.
 *
 * @param array      $data  List of data which is displayed in exported CSV.
 * @param Give_Donor $donor Donor Object.
 *
 * @return mixed
 */
function give_export_set_custom_donor_data( $data, $donor ) {

	$phone_number         = Give()->donor_meta->get_meta( $donor->id, 'give_phone', true );
	$data['phone_number'] = ! empty( $phone_number ) ? $phone_number : '- N/A - ';

	return $data;
}
add_filter( 'give_export_set_donor_data', 'give_export_set_custom_donor_data', 10, 2 );


 

/**
 *  The multi-Step form template and the donor dashboard load in an iframe, which prevents theme styles from interfering with their styles.
 *  To style them, use this PHP snippet to add inline styles. Replace lines 16-26 with your custom styles.
 */

function override_iframe_template_styles_with_inline_styles() {
	
	wp_enqueue_script('int-tel-phone-js','https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/3.6.1/js/intlTelInput.min.js');
	wp_enqueue_script('int-tel-phone-js','https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js');

	wp_enqueue_style( 'int-tel-phone-style', 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/3.6.1/css/intlTelInput.css' );

	
    wp_add_inline_style(
        /**
         *  Below, use give-sequoia-template-css to style the multi-step donation form
         *  or use give-donor-dashboards-app to style the donor dashboard
         */
        'give-classic-template',
        '
        /* add styles here! A sample (turns the headline text blue): */
        
		.intl-tel-input{
		width: inherit!important;
		}
	
	.country-list{
		width: 400px!important;
	}
	
		 #give-phone-wrap label{
    clip:rect(0,0,0,0);
    border-width:0;
    height:1px;
    margin:-1px;
    overflow:hidden;
    padding:0;
    position:absolute;
    white-space:nowrap;
    width:1px
}




#give-phone-wrap{
    position:relative
}
#give-phone-wrap:before{
    block-size:1em;
    color:#8d8e8e;
    font-family:Font Awesome\ 5 Free;
    font-weight:900;
    inset-block-end:.1875em;
    inset-block-start:0;
    inset-inline-start:1.1875rem;
    margin-block:auto;
    pointer-events:none;
    position:absolute
}
#give-phone-wrap input{
    -webkit-padding-start:2.6875rem;
    padding-inline-start:2.6875rem
}

#give-phone-wrap:before{
    content:"\f095"
}
		
		
		
        ',
		
		
    );
}

add_action('wp_print_styles', 'override_iframe_template_styles_with_inline_styles', 10);
