<?php
/*
CREATE TABLE `wp_frm_payments_auth` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `invoice_id` int(100) NOT NULL,
  `payment_item_id` int(100) NOT NULL,
  `authnet_login_id` varchar(100) NOT NULL
);
*/


class Dotfiler_authnet {

    // For meta fields
    private $meta_box_id = 'authnet_metabox';
    private $meta_box_title = 'Authorize.net Settings';
    private $post_type = 'authnet_account';

    public function __construct() {


        // For meta fields
        add_action('add_meta_boxes', array($this, 'add_authnet_metabox'));
        add_action('save_post', array($this, 'save_authnet_metabox'));

        // Catch Authorize.net credentials and change it
        add_action('init', array($this, 'init_authnet_credentials') );

    }

    public function init_authnet_credentials() {

        if( isset($_POST['frm_action']) && $_POST['frm_action'] == 'create' ) {

            $form_id = $_POST['form_id'];
            $creds = Dotfiler_authnet::get_custom_credentials( $form_id );

            if( is_countable( $creds ) ) {

                if( 
                    trim($creds['login_id']) == '' || 
                    trim($creds['transaction_key']) == ''
                    ) { return true; }

                // Allows us not to modify Authorize.net FRM plugin
                define('AUTHORIZENET_API_LOGIN_ID', $creds['login_id']);
                define('AUTHORIZENET_TRANSACTION_KEY', $creds['transaction_key']);

            }
    
        }

    }

    public static function get_custom_credentials( $form_id=false ) {

        $res = get_posts( 
            array(
                "post_type" => "authnet_account",
                "meta_key" => "formidable_form_id",
                "meta_value" => $form_id
            )
        );

        if( count( $res ) > 0 ) {

            $post = $res[0];

            return array(
                "login_id" => get_post_meta($post->ID, 'authnet_login_id', true),
                "transaction_key" => get_post_meta($post->ID, 'authnet_transaction_key', true),
                "signature_key" => get_post_meta($post->ID, 'authnet_signature_key', true),
            );

        }

    }

    public function add_authnet_metabox() {
        add_meta_box(
            $this->meta_box_id,
            $this->meta_box_title,
            array($this, 'render_authnet_metabox'),
            $this->post_type,
            'normal',
            'high'
        );
    }

    static function get_formidadable_forms() {

        global $wpdb;

        $query = "SELECT * from wp_frm_forms";
        $res = $wpdb->get_results( $query );

        return $res;

    }

    public function render_authnet_metabox($post) {

        wp_nonce_field(basename(__FILE__), 'authnet_metabox_nonce');
        
        // Get saved values
        $authnet_login_id = get_post_meta($post->ID, 'authnet_login_id', true);
        $authnet_transaction_key = get_post_meta($post->ID, 'authnet_transaction_key', true);
        $authnet_signature_key = get_post_meta($post->ID, 'authnet_signature_key', true);
        $formidable_form_id = get_post_meta($post->ID, 'formidable_form_id', true);

        $formidable_forms = Dotfiler_authnet::get_formidadable_forms();
        ?>
        <p>
            <label for="authnet_login_id">API Login ID:</label><br>
            <input type="text" id="authnet_login_id" name="authnet_login_id" value="<?php echo esc_attr($authnet_login_id); ?>">
        </p>
        <p>
            <label for="authnet_transaction_key">Transaction Key:</label><br>
            <input type="text" id="authnet_transaction_key" name="authnet_transaction_key" value="<?php echo esc_attr($authnet_transaction_key); ?>">
        </p>
        <p>
            <label for="authnet_signature_key">Signature Key:</label><br>
            <input type="text" id="authnet_signature_key" name="authnet_signature_key" value="<?php echo esc_attr($authnet_signature_key); ?>">
        </p>
        <p>
            <label for="formidable_form_id">Formidable Form ID:</label><br>
            <select id="formidable_form_id" name="formidable_form_id">
                <option></option>
                <?php foreach ($formidable_forms as $form) : ?>
                    <option value="<?php echo esc_attr($form->id); ?>" <?php selected($form->id, $formidable_form_id); ?>><?php echo esc_html($form->name); ?></option>
                <?php endforeach; ?>
            </select>
        </p>

        <?php
    }

    public function save_authnet_metabox($post_id) {

        if (!isset($_POST['authnet_metabox_nonce']) || !wp_verify_nonce($_POST['authnet_metabox_nonce'], basename(__FILE__))) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $fields = array('authnet_login_id', 'authnet_transaction_key', 'authnet_signature_key', 'formidable_form_id');

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }

    }

    public static function get_account_transactions( $login_id ) {

        global $wpdb;

        $query = 'SELECT *
            FROM wp_frm_payments_authnet
            WHERE authnet_login_id = "6CN4np2p"
            AND created_at >= DATE_FORMAT(NOW(), "%Y-%m-01")
            AND created_at < DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 1 MONTH), "%Y-%m-01");
        ';
        $results = $wpdb->get_results( $query, ARRAY_A );

        $sum = array_sum(array_column($results, 'amount'));

        return array(
            "sum" => $sum
        );

        /*
        echo $sum;

        echo "<pre>";
        print_r($results);
        echo "</pre>";
        */

    }

    public function get_payment_by_id( $payment_item_id ) {

        global $wpdb;
        $table = $wpdb->prefix.'frm_payments';

        // Get frm payment
        $query = "SELECT * FROM {$table} WHERE `item_id`={$payment_item_id}";
        $res = $wpdb->get_results( $query );

        // Get extra information
        if( isset( $res[0]->invoice_id ) && $res[0]->invoice_id != '' ) {

            $table = $wpdb->prefix.'frm_payments_authnet';
            $invoice_id = $res[0]->invoice_id;

            $query = "SELECT * FROM {$table} WHERE `invoice_id`={$invoice_id}";
            $records = $wpdb->get_results( $query );

            return $records[0];
        }

        /*
        $query = "SELECT p.*, i.*
            FROM wp_frm_payments p
            JOIN wp_frm_payments_authnet i ON p.invoice_id = i.invoice_id
            WHERE p.item_id = 1617
        ";
        $res = $wpdb->get_results( $query );

        print_r($res);
        */

        //return $res[0];

    }
    
}

new Dotfiler_authnet();
