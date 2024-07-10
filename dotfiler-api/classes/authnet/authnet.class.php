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

        // Add custom column to post type list
        add_filter('manage_authnet_account_posts_columns', array($this, 'add_custom_column_to_post_type_list'));
        add_action('manage_authnet_account_posts_custom_column', array($this, 'populate_custom_column_with_data'), 10, 2);

        
        add_filter('manage_edit-authnet_account_sortable_columns', array($this, 'make_custom_column_sortable'));

        add_action('pre_get_posts', array($this, 'modify_query_to_sort_by_custom_column'));


        // For meta fields
        add_action('add_meta_boxes', array($this, 'add_authnet_metabox'));
        add_action('save_post', array($this, 'save_authnet_metabox'));

        // Catch Authorize.net credentials and change it
        add_action('init', array($this, 'init_authnet_credentials') );

    }

    // Make custom column sortable
    public function make_custom_column_sortable($columns) {
        $columns['total_sum'] = 'total_sum';
        return $columns;
    }

    // Modify query to sort by custom column
    public function modify_query_to_sort_by_custom_column($query) {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        $orderby = $query->get('orderby');
        if ($orderby === 'total_sum') {
            $query->set('meta_key', 'authnet_login_id');
            $query->set('orderby', 'meta_value_num');
        }
    }

    // Populate custom column with data
    public function populate_custom_column_with_data($column, $post_id) {
        if ($column === 'total_sum') {
            $authnet_login_id = get_post_meta($post_id, 'authnet_login_id', true);
            $total_sum = Dotfiler_authnet::get_account_total_sum($authnet_login_id);
            $monthly_limit = get_post_meta($post_id, 'monthly_limit', true);

            echo round($total_sum, 2)." of ".$monthly_limit;
        }
    }

    // Add custom column to post type list
    public function add_custom_column_to_post_type_list($columns) {
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['total_sum'] = 'Accumulated sum $ (this month)';
            }
        }
        return $new_columns;
    }

    // Calculate total sum paid by account
    public static function get_account_total_sum($login_id)
    {
        global $wpdb;

        $query = "SELECT SUM(amount) as total_sum
            FROM wp_frm_payments_authnet
            WHERE authnet_login_id = '{$login_id}'
            AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')
            AND created_at < DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 1 MONTH), '%Y-%m-01');
        ";
        $result = $wpdb->get_var($query);

        return $result;
    }

    public function init_authnet_credentials() {

        if( isset($_POST['frm_action']) && $_POST['frm_action'] == 'create' ) {

            $form_id = $_POST['form_id'];
            $creds = Dotfiler_authnet::get_custom_credentials( $form_id );

            // HERE, check!
            //print_r($creds);
            //die();  

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

    public function set_authnet_credentials(  ) {



    }

    public static function get_custom_credentials( $form_id=false ) {

        $res = get_posts( 
            array(
                "post_type" => "authnet_account",
                "meta_key" => "formidable_form_id",
                "meta_value" => $form_id,
                'post_status' => "publish"
            )
        );

        if( count( $res ) > 0 ) {

            foreach( $res as $post ) {

                $authnet_login_id = get_post_meta($post->ID, 'authnet_login_id', true);
                $account_info = Dotfiler_authnet::get_account_transactions( $authnet_login_id );
                $last_transaction = Dotfiler_authnet::get_last_transaction( $authnet_login_id );

                $data = array(
                    "login_id" => $authnet_login_id,
                    "transaction_key" => get_post_meta($post->ID, 'authnet_transaction_key', true),
                    "signature_key" => get_post_meta($post->ID, 'authnet_signature_key', true),
                    "monthly_limit" => get_post_meta($post->ID, 'monthly_limit', true),
                    "account" => Dotfiler_authnet::get_account_transactions( $authnet_login_id ),
                    "last_transaction" => $last_transaction['created_at']
                );

                if( $data['monthly_limit'] > $data['account']['sum'] ) {
                    $set[] = $data;
                }

            }

            /*
            echo "<pre>";
            print_r($set);
            echo "</pre>";
            */

            usort($set, function($a, $b) {
                return strtotime($a['last_transaction']) - strtotime($b['last_transaction']);
            });

            return $set[0];

        } 

    }

    public static function get_last_transaction( $login_id ) {

        global $wpdb;

        $query = "SELECT *
            FROM wp_frm_payments_authnet
            WHERE authnet_login_id = '{$login_id}'
            ORDER BY created_at DESC
            LIMIT 1
        ";
        $results = $wpdb->get_results( $query, ARRAY_A );

        return $results[0];

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
        $monthly_limit = get_post_meta($post->ID, 'monthly_limit', true);
    
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
        <?php  ?>
        <p>
            <label for="formidable_form_id">Formidable Form ID:</label><br>
            <select id="formidable_form_id" name="formidable_form_id">
                <option></option>
                <?php foreach ($formidable_forms as $form) : ?>
                    <option value="<?php echo esc_attr($form->id); ?>" <?php selected($form->id, $formidable_form_id); ?>><?php echo esc_html($form->name); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <?php  ?>
        <p>
            <label for="monthly_limit">Monthly Limit ($):</label><br>
            <input type="number" id="monthly_limit" name="monthly_limit" value="<?php echo esc_attr($monthly_limit); ?>">
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
    
        $fields = array('authnet_login_id', 'authnet_transaction_key', 'authnet_signature_key', 'formidable_form_id', 'monthly_limit');
    
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
    
    }

    public static function get_account_transactions( $login_id ) {

        global $wpdb;

        $query = "SELECT *
            FROM wp_frm_payments_authnet
            WHERE authnet_login_id = '{$login_id}'
            AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')
            AND created_at < DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 1 MONTH), '%Y-%m-01');
        ";
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

    }

    public function get_creds_by_login_id( $login_id ) {

        $args = array(
            'post_type' => 'authnet_account',
            'meta_key' => 'authnet_login_id',
            'meta_value' => $login_id,
            'posts_per_page' => -1
        );
        $posts = get_posts( $args );
        $post = $posts[0];

        if( $post ) {

            $data = array(
                "login_id" => get_post_meta($post->ID, 'authnet_login_id', true),
                "transaction_key" => get_post_meta($post->ID, 'authnet_transaction_key', true),
                "signature_key" => get_post_meta($post->ID, 'authnet_signature_key', true),
            );

            return $data;

        }

    }

    public function get_creds_default() {

        $data = get_option('frm_authnet_options');
        return (array) $data;

    }
    
}

new Dotfiler_authnet();



add_action('init', 'init33');
function init33() {

    if( isset($_GET['refund']) ) {

        $payment_id = $_GET['refund'];
        $refund = new Dotfiler_authnet_refund();

        $amount = 0.5;
        $res = $refund->refund_payment( $payment_id, $amount );

        die();

    }

}