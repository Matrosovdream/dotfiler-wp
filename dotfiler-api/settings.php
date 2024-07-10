<?php
// Initial forms, it takes the initial form and then redirect to the main one
$initial_forms = array(
    "4" => array(
        "input_id" => 18,
        "final_form" => 3,
        "redirect_url" => "/ucr-registration/"
    ),
    "8" => array(
        "input_id" => 107,
        "final_form" => 7,
        "redirect_url" => "/mcs150-update/"
    ),
    "21" => array(
        "input_id" => 823,
        "final_form" => 20,
        "redirect_url" => "/mcsa-5889-update/"
    ),
    "10" => array(
        "input_id" => 120,
        "final_form" => 9,
        "redirect_url" => "/boc-3-form/"
    ),
    "19" => array(
        "input_id" => 710,
        "final_form" => 15,
        "redirect_url" => "/safety-audit-form/"
    ),
);

// If the API response is empty then we redirect accordingly to this array
$empty_response = array(
    7 => '/mcs-150/',
    20 => '/mcsa-5889/',
    3 => '/ucr/',
    9 => '/boc-3/',
    15 => '/safety-audit/',
);    

// Here is matching happening between API fields and Formidibable form fields
function match_fields( $form_id, $data ) {

    
    if( $form_id == 3 ) {
        $matches = array(
            "21" => "usdot",
            "9" => "legal_name",
            "12" => "physical_address",
            "13" => "operating_status",
            "15" => "mcs_150_form_date",
        );

        if( strpos($data['operating_status'], 'Passenger' ) !== false ) {
            $matches['54'] = "power_units";
            $matches['70'] = "power_units";
            $matches['64'] = "power_units";
        } else {
            $matches['63'] = "power_units";
            $matches['69'] = "power_units";
            $matches['53'] = "power_units";
        }
        
    }
    if( $form_id == 7 ) {
        $matches = array(
            "86" => "usdot",
            "87" => "legal_name",
            "88" => "dba_name",
            "90" => "physical_address",
            "91" => "mailing_address",
            "93" => "phone",
            "106" => "drivers",
            "104" => "power_units",
            "853" => "mc_mx_ff_numbers",
            "852" => "operating_status",
            "89" => "ein",
            "1435" => "mcs_150_form_date",
            //"1" => "oosDate",
            //"2" => "oosReason",
            //"3" => "oosReasonDescription",
        );
    }
    if( $form_id == 20 ) {
        $matches = array(
            "739" => "usdot",
            "740" => "legal_name",
            "741" => "dba_name",
            "742" => "physical_address",
            "743" => "mailing_address",
            "760" => "mc_mx_ff_numbers",
            "842" => "ein"
        );
    }
    if( $form_id == 9 ) {
        $matches = array(
            "121" => "usdot",
            "122" => "legal_name",
            "123" => "dba_name",
            "124" => "physical_address",
            "125" => "mailing_address",
        );
    }
    if( $form_id == 15 ) {
        $matches = array(
            "711" => "usdot",
            "678" => "drivers",
            "679" => "power_units",
        );
    }

    return $matches;

}
