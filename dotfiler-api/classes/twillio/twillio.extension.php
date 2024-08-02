<?php
class FRM_Twillio_extension {

    public function __construct() {

        add_filter('frm_twilio_action_options', [$this, 'frm_twlo_action_options'], 10, 2);

    }

    function frm_twlo_action_options($actions) {

        $actions['event'][] = 'abandoned';
        return $actions;
    
    }


}

new FRM_Twillio_extension();