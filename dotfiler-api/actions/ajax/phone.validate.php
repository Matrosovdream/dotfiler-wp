<?php
add_action('wp_ajax_phone_validate', 'phone_validate');
add_action('wp_ajax_nopriv_phone_validate', 'phone_validate');
function phone_validate() {

    $phone = $_POST['phone'];
    $checker = (new PhoneChecker( $phone ))->verify();

    if( $checker->is_error() ) {
        $html =  '<p class="validation-error">' . $checker->get_error() . '</p>';
    } elseif(  $checker->is_valid() ) {

        $fields = array(
            'Country' => $checker->get_country(),
            'Country code' => $checker->get_country_code(),
            'Location' => $checker->get_location(),
            'Carrier' => $checker->get_carrier(),
            'Line type' => $checker->get_line_type()
        );

        $html = '<ul class="phone-validation-list">';
        foreach($fields as $key => $value) {
            $html .= '<li><strong>' . $key . ':</strong> ' . $value . '</li>';
        }
        $html .= '</ul>';

    } else {
        $html = '<p class="validation-error">Invalid phone number</p>';
    }

    echo $html;
    wp_die();
}


add_action('wp_footer', 'phone_validate_ajax');
function phone_validate_ajax() {
    ?>
    
    <?php echo do_shortcode('[phone-validate]'); ?>

    <div style="height: 500px"></div>

    <?php
}