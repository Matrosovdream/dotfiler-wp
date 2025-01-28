<?php
add_action('init', 'init_get_dot_data');
function init_get_dot_data() {

    require_once DOTFILER_BASE_URL.'/settings.php';

    if( isset($_POST['form_id']) ) {

        $form_info = $initial_forms[$_POST['form_id']];

        if( !is_array($form_info) ) {
            return true;
        }

        $val = $_POST['item_meta'][ $form_info['input_id'] ];
        $form_id = $_POST['form_id'];

        $API = new Dotfiler_api( $query=$val );
        $data = $API->request_saferweb();

        // Get FMSCA also
        $data['fmcsa'] = $API->request_base()['content']['carrier'];

        $data['fmcsa_state'] = $data['fmcsa']['phyState'];
        $data['fmcsa_city'] = $data['fmcsa']['phyCity'];
        $data['fmcsa_street'] = $data['fmcsa']['phyStreet'];
        $data['fmcsa_zipcode'] = $data['fmcsa']['phyZipcode'];

        


        // Check logs by admin
        //if ( current_user_can( 'manage_options' ) && get_current_user_id() == 2 ) {

            // Get transportation data
            $data['transportation'] = $API->getTransGovData();

            foreach( $data['transportation'] as $key => $value ) {
                $data['gov_'.$key] = $value;
            }

            /*
            echo "<pre>";
            print_r($data);
            echo "</pre>";
            die();
            */
        //}


        if( !isset($data['usdot']) ) {
            unset( $_SESSION['carrier'][ $form_info['final_form'] ] );

            $data['usdot'] = $val;
            $data['operating_status'] = 'inactive';

            // Deprecated
            /*
            $redirect_url = add_query_arg(array("error" => "y", "value" => $val), $_SERVER['HTTP_REFERER']);
            wp_redirect($redirect_url);
            exit();
            */
        } else { 

            // Prepare fields
            $data['mcs_150_mileage'] = $data['mcs_150_mileage_year']['mileage'];
            $data['mcs_150_year'] = $data['mcs_150_mileage_year']['year'];

        }


        $_SESSION['carrier'][ $form_info['final_form'] ] = $data;

        $actual_link = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]";
        $redirect_url = $actual_link.$form_info['redirect_url']; 

        // Build the URL with parameters
        //$redirect_url = add_query_arg($data, $redirect_url);

        wp_redirect($redirect_url);
        die();

    }

}
   
add_action('frm_display_form_action', 'check_entry_count', 8, 3);
function check_entry_count($params, $fields, $form) {

    // Exclude editing action
    if( $_POST['action'] == 'frm_entries_edit_entry_ajax' ) { return true; }
    if( $_POST['frm_action'] == 'update' ) { return true; }

    require_once DOTFILER_BASE_URL.'/settings.php';
    global $empty_response;

    $form_id = $form->id;
    $back_url = $empty_response[ $form_id ];
    if( $back_url && !$_SESSION['carrier'][$form_id]  ) {
        echo "
            <script>
                window.location.href='".$back_url."';
            </script>
        ";
    }

}


add_filter( 'frm_setup_new_fields_vars', 'prefill_formidable_form', 5, 10 );
function prefill_formidable_form( $field_values, $form_field ) {

    $form_id = $field_values['form_id'];
    if( isset($_SESSION['carrier'][$form_id]) ) {
        $data = $_SESSION['carrier'][$form_id];
    } else {
        $data = array();
    }

    // Log the data here if you need to


    if( $field_values['form_id'] == 2 || $field_values['form_id'] == 9 || $field_values['form_id'] == 11 || $field_values['form_id'] == 13 ) {
        $val = match_form_fields( $data, $form_field->id, $form_id );
        if( $val != '' ) {
            $field_values['value'] = $val;
        }
    }

    return $field_values;
}


function match_form_fields( $data, $field_id, $form_id ) {

    $matches = match_fields( $form_id, $data );

    if( isset($matches[ $field_id ]) ) {
        $api_field = $matches[ $field_id ];
    }

    $api_value = '';
    if( isset($api_field) && is_array($api_field) ) {
        $field_set = [];
        foreach( $api_field as $field ) {
            $field_set[] = $data[ $field ];
        }
        $api_value = implode(', ', $field_set);
    } else {
        if( isset($api_field) ) {
            $api_value = $data[ $api_field ];
        }
    }

    return $api_value;

}


// We need it to work with Sessions
add_action('init', 'start_session', 1);
function start_session() {
    if(!session_id()) {
        @session_start();
    }
}
