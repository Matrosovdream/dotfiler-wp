<?php
add_action( 'wp_footer', 'api_quick_test_javascript', 99 );
function api_quick_test_javascript() {
	?>
	<script>
	jQuery(document).ready( function($) {

        var ein_isset = jQuery('.ein-ajax').length;

        if( ein_isset > 0 ) {

            var data = {
                action: 'api_ajax_load_ein',
                ein: jQuery("input[name='usdot']").val()
            };

            jQuery.post( '/wp-admin/admin-ajax.php', data, function( response ){

                var val = response;
                if( val ) {

                    // Right sidebar
                    jQuery('.ein-ajax').show();
                    jQuery('.ein-ajax-val').html( val );

                    // Hidden inputs
                    jQuery('input[name="item_meta[89]"]').val(val);
                    jQuery('input[name="item_meta[842]"]').val(val);

                }

            });

        }

	});
	</script>
	<?php
}


add_action( 'wp_ajax_api_ajax_load_ein', 'api_ajax_load_ein_callback' );
add_action( 'wp_ajax_nopriv_api_ajax_load_ein', 'api_ajax_load_ein_callback' );
function api_ajax_load_ein_callback() {

    $ein = $_POST['ein'];
	
    $API = new Dotfiler_api( $query=$ein );
    $result = $API->request_base();
    $data = $result['content']['carrier'];

    //$data['ein'] = 555;

    if( isset( $data['ein'] ) ) {
        echo $data['ein'];
    }
    
	wp_die();
}


add_action( 'wp_enqueue_scripts', 'myajax_data', 99 );
function myajax_data(){

	wp_localize_script( 'leonardo-script', 'myajax',
		array(
			'url' => admin_url('admin-ajax.php')
		)
	);

}





//add_filter( 'frm_entry_values_fields', 'sort_remove_fields', 10, 2 );
function sort_remove_fields( $fields, $args ) {

    if( $fields['type'] == 'gateway' ) {}

    echo "<pre>";
    print_r($fields[25]);
    echo "</pre>";
    
	return $fields;
}

add_filter( 'frm_display_gateway_value_custom', 'frm_gateway_val', 15, 2 );
function frm_gateway_val( $value, $atts ) {
  
    // Show just on admin entries page
    if( $_GET['page'] == 'formidable-entries' && $value == 'authnet_aim' ) {

        $payment_item_id = $_GET['id'];

        $authnet = new Dotfiler_authnet();
        $payment_info = $authnet->get_payment_by_id( $payment_item_id );
        $authnet_login_id = $payment_info->authnet_login_id;

        if( $authnet_login_id ) {
            $value .= " (<b>".$authnet_login_id."</b> account)";
        }

    }

    return $value;
}