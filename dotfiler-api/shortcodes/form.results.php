<?php
add_shortcode( 'api_short_results', 'api_short_results_func' );
function api_short_results_func( $atts ) {

    global $post;
    $post_slug = $post->post_name;

    if( $post_slug == 'mcs150-update' ) { $form_id = 7; }
    if( $post_slug == 'mcsa-5889-update' ) { $form_id = 20; }
    if( $post_slug == 'ucr-registration' ) { $form_id = 3; }
    if( $post_slug == 'boc-3-form' ) { $form_id = 9; }
    if( $post_slug == 'safety-audit-form' ) { $form_id = 15; }

    if( !$form_id ) { return false; }

    $data = $_SESSION['carrier'][$form_id];

    if( !$data ) {
        $html = "<p class='error'>You have entered an invalid USDOT #</p>";
        return $html;
    }

    $passenger = $data['isPassengerCarrier'];

    $address[] = $data['phyStreet'];
    $address[] = $data['phyCity'];
    $address[] = $data['phyState'];
    $address[] = $data['phyZipcode'];

    $fields = array(
        "Operating status" => $data["operating_status"],
        "Entity Type" => $data["entity_type"],
        "MC/MX/FF Number(s)" => $data["mc_mx_ff_numbers"],
        "Out of Service Date" => $data["out_of_service_date"],
        "Legal Name" => $data["legal_name"],
        "DBA Name" => $data["dba_name"],
        "Physical Address" => $data["physical_address"],
        "Power Units" => $data["power_units"],
        "Drivers" => $data["drivers"],
        "MCS-150 Last Update" => $data["mcs_150_form_date"],
        //"Mileage (Year)" => $data["mcs_150_mileage"]. " (".$data["mcs_150_year"].")",
        "EIN" => "-",

        //"oosDate" => $data["oosDate"],
        //"oosReason" => $data["oosReason"],
        "OOS" => 'ajax',
        
        //"DBA" => $data["dbaName"],
        //"EIN" => $data["ein"],
        //"Address" => implode(', ', $address),
        //"Total Drivers" => $data["totalDrivers"],
    );

    if( isset($data["mcs_150_mileage"]) && $data["mcs_150_mileage"] != '' ) {
        $fields["Mileage (Year)"] = $data["mcs_150_mileage"]. " (".$data["mcs_150_year"].")";
    }
    
    /*
    if( $data['dbaName'] ) { $fields['DBA'] = $data["dbaName"]; }
    if( $data["ein"] ) { $fields['EIN'] = $data["ein"]; }
    if( $data["docketNumber"] ) { $fields[ $data["prefix"].' #' ] = $data['docketNumber']; }

    if( $passenger == 'N' ) {
        $fields['Trucks/Tractors'] = $data["totalPowerUnits"];
    } else {
        $fields['Passenger Vehicles'] = $data["totalPowerUnits"];
    }
    */
        

    $html = '';
	foreach( $fields as $name=>$val ) {
        if( $val == '' ) { $val = '-'; continue; }
        if( $name == 'EIN' ) { 
            $class = "ein-ajax"; 
            $style = "display:none;"; 
        } elseif( $name == 'OOS' ) { 
            $class = "oos-ajax"; 
            $style = "display: none;"; 
        } else { 
            $class = ""; 
            $style = ""; 
        }

        $html .= "
            <p style='font-size: 14px;margin: 0;{$style}' class='{$class}'>
                <b>{$name}:</b> 
                <span class='{$class}-val'>{$val}</span>
            </p>";

    }

    $html .= "<input type='hidden' name='usdot' value='{$data['usdot']}' />";

	return $html;
}
