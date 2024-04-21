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

    }

    public function dotfiler_api_section_callback() {
        echo '<p>Enter your API key:</p>';
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


