<?php
add_shortcode( 'api_short_results_mobile', 'api_short_results_mobile_func' );
function api_short_results_mobile_func( $atts ) {

    global $post;
    $post_slug = $post->post_name;

    if( $post_slug == 'mcs150-update' ) { $form_id = 7; }
    if( $post_slug == 'mcsa-5889-update' ) { $form_id = 20; }
    if( $post_slug == 'ucr-registration' ) { $form_id = 3; }
    if( $post_slug == 'boc-3-form' ) { $form_id = 9; }
    if( $post_slug == 'safety-audit-form' ) { $form_id = 15; }

    if( !$form_id ) { return false; }

    $data = $_SESSION['carrier'][$form_id];

    $fields = array(
        "USDOT#" => $data["usdot"],
    );

    $html = '';
	foreach( $fields as $name=>$val ) {
        if( $val == '' ) { $val = '-'; }

        $html .= "
                <b>{$name}: {$val}</b>
            ";
    }

	return $html;
}
