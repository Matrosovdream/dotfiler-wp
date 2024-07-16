<?php
class Dotfiler_admin {
    private $url;

    public function __construct() {

        $this->setup_admin_page();

    }


    private function setup_admin_page() {
        add_action('admin_menu', array($this, 'dotfiler_api_menu'));
        add_action('admin_init', array($this, 'dotfiler_api_settings_init'));
    }

    public function dotfiler_api_menu() {
        add_menu_page(
            'Dotfiler API Settings',
            'Dotfiler API',
            'manage_options',
            'dotfiler_api_settings',
            array($this, 'dotfiler_api_page')
        );
    }

    public function dotfiler_api_page() {
        ?>
        <div class="wrap">
            <h1>Dotfiler API Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('dotfiler_api_group');
                do_settings_sections('dotfiler_api_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function dotfiler_api_settings_init() {

        register_setting('dotfiler_api_group', 'dotfiler_api');
        register_setting('dotfiler_api_group', 'saferweb_api_key');
        register_setting('dotfiler_api_group', 'frm_remove_failed_entries');

        add_settings_section(
            'dotfiler_api_section',
            'API Key Settings',
            array($this, 'dotfiler_api_section_callback'),
            'dotfiler_api_settings'
        );

        add_settings_field(
            'dotfiler_api',
            'API key',
            array($this, 'dotfiler_api_callback'),
            'dotfiler_api_settings',
            'dotfiler_api_section'
        );

        add_settings_field(
            'saferweb_api_key',
            'API key',
            array($this, 'saferweb_api_key_callback'),
            'dotfiler_api_settings',
            'dotfiler_api_section'
        );

        add_settings_section(
            'dotfiler_api_section',
            'API Key Settings',
            array($this, 'dotfiler_api_section_callback'),
            'dotfiler_api_settings'
        );
    
        // Add new settings section for Formidable entries
        add_settings_section(
            'formidable_entries_section',
            'Formidable Entries Settings',
            array($this, 'formidable_entries_section_callback'),
            'dotfiler_api_settings'
        );
    
        // Add new checkbox field for removing failed entries
        add_settings_field(
            'frm_remove_failed_entries',
            'Remove Failed Entries',
            array($this, 'frm_remove_failed_entries_callback'),
            'dotfiler_api_settings',
            'formidable_entries_section'
        );

    }

    public function dotfiler_api_section_callback() {
        echo '<p>Enter your API key:</p>';
    }

    public function formidable_entries_section_callback() {
        //echo '<p>Formidable Entries Settings:</p>';
    }
    
    public function frm_remove_failed_entries_callback() {
        $value = get_option('frm_remove_failed_entries');
        echo '<input type="checkbox" name="frm_remove_failed_entries" value="1" ' . checked(1, $value, false) . ' />';
    }
    

    public function dotfiler_api_callback() {
        $value = get_option('dotfiler_api');
        echo '<input type="text" name="dotfiler_api" value="' . esc_attr($value) . '" style="width: 400px;" />';
        echo '<br/><span>mobile.fmcsa.dot.gov</span>';
    }

    public function saferweb_api_key_callback() {
        $value = get_option('saferweb_api_key');
        echo '<input type="text" name="saferweb_api_key" value="' . esc_attr($value) . '" style="width: 400px;" />';
        echo '<br/>saferwebapi.com</span>';
    }

}

new Dotfiler_admin();


