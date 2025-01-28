<?php
/*
Allows the admin to attach a file to an email. 
This is useful when you want to send a file to the user after they submit a form.
Just insert the shortcode [attach field={field_id}] in the email message.
*/

class AttachFilesToEmail {

    private $shortcode_pattern = '/\[attach field=(\d+)\]/';

    public function __construct() {

        // Read files the from form and attach it to the email
        add_filter('frm_notification_attachment', array($this, 'addAttachmentsToEmail'), 10, 3);

        // Remove attach cursors from email message
        add_filter('frm_email_message', array($this, 'modifyMessage'), 10, 2);
    }

    public function addAttachmentsToEmail($attachments, $form, $args) {

        $entry = $args['entry'];
        $entry_id = $entry->id;
        $email_message = $args['settings']['email_message'];

        // Extract all field ids from the email message
        $field_ids = $this->extractFileIds($email_message);
    

        // Get all entry values (metas)
        $filter = [
            'item_id' => $entry_id,
        ];
        $metas = FrmEntryMeta::getAll( $filter );
    
        // Filter file fields and get a file path
        $files = [];
        foreach ($metas as $meta) {
            if( $meta->field_type != 'file' ) { continue; }
            
            if( !in_array($meta->field_id, $field_ids) ) { continue; }
    
            $file_id = $meta->meta_value;
            $files_url = wp_get_attachment_url($file_id);
    
            // Convert $files_url to a file path
            $files_url = str_replace(site_url(), ABSPATH, $files_url);
    
            if (file_exists($files_url)) {
                $attachments[] = $files_url;
            }
    
        }

        /*
        echo "<pre>";
        print_r($field_ids);
        echo "</pre>";

        echo "<pre>";
        print_r($attachments);
        echo "</pre>";

        echo "<pre>";
        print_r($metas);
        echo "</pre>";
        die();
        */
    
        return $attachments;

    }

    private function extractFileIds($email_message) {

        preg_match_all($this->shortcode_pattern, $email_message, $matches);
    
        // Get the field ids from the shortcodes
        $field_ids = [];
        foreach ($matches[0] as $match) {
            preg_match('/\d+/', $match, $field_id);
            $field_ids[] = $field_id[0];
        }

        return $field_ids;
    }

    public function modifyMessage($subject, $atts) {
  
        // Remove shortcode links
        $subject = preg_replace($this->shortcode_pattern, '', $subject);

        // Remove File upload table
        $subject = preg_replace('/<table.*?File Upload.*?<\/table>/s', '', $subject);

        return $subject;

    }

}


// Initialize filters/hooks
new AttachFilesToEmail();