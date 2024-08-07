<?php
class FRM_Twillio_extension {

    public function __construct() {

        /* 
        Attach trigger "abandoned" to Twillio actions list.
        Abandonded comes from extension plugin Formidable-abandonment.
        */
        add_filter('frm_twilio_action_options', [$this, 'frm_twlo_action_options'], 10, 2);


        // Turn on Autoresponder for abandoned entries with all statuses
        // By default it doesn't take Draft entries
        add_filter('formidable_autoresponder_should_trigger', [$this, 'frm_twilio_autoresponder_should_trigger'], 10, 3);

        // Prevent trigger action
        //add_filter('frm_custom_trigger_action', [$this, 'frm_trigger_action_func'], 10, 3);

        // Set the period of time to mark the entry as abandoned
        //add_filter( 'frm_mark_abandonment_entries_period', [$this, 'frm_mark_abandonment_entries_period_func'], 10, 2 );

    }

    public function frm_mark_abandonment_entries_period_func($interval_in_minutes) {
        return 60 * 24;
    }

    public function frm_twlo_action_options($actions) {

        $actions['event'][] = 'abandoned';

        return $actions;
    
    }

    public function frm_twilio_autoresponder_should_trigger($should_trigger, $entry, $action) {

        if( in_array( 'abandoned', $action->post_content['event'], true ) ) {
            return true;
        }

        return $should_trigger;
    }

    public function frm_trigger_action_func($prevent, $action, $entry, $form, $event) {
        return $prevent;
    }


}

new FRM_Twillio_extension();


