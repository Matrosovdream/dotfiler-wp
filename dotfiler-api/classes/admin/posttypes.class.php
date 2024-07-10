<?php
class Dotfiler_posttypes {

    public function __construct() {

        // Authorize.net multiple accounts for Formidable forms
        add_action( 'init', array($this, 'register_cpt_authnet') );    

    }

    public function register_cpt_authnet() {

        /**
         * Post Type: Authorize.net Accounts.
         */
    
        $labels = [
            "name" => esc_html__( "Authorize.net Accounts", "Avada" ),
            "singular_name" => esc_html__( "Authorize.net Account", "Avada" ),
        ];
    
        $args = [
            "label" => esc_html__( "Authorize.net Accounts", "Avada" ),
            "labels" => $labels,
            "description" => "",
            "public" => true,
            "publicly_queryable" => true,
            "show_ui" => true,
            "show_in_rest" => true,
            "rest_base" => "",
            "rest_controller_class" => "WP_REST_Posts_Controller",
            "rest_namespace" => "wp/v2",
            "has_archive" => false,
            "show_in_menu" => true,
            "show_in_nav_menus" => true,
            "delete_with_user" => false,
            "exclude_from_search" => false,
            "capability_type" => "post",
            "map_meta_cap" => true,
            "hierarchical" => false,
            "can_export" => false,
            "rewrite" => [ "slug" => "authnet_account", "with_front" => true ],
            "query_var" => true,
            "supports" => [ "title", "editor", "thumbnail" ],
            "show_in_graphql" => false,
        ];
    
        register_post_type( "authnet_account", $args );
    }

}

new Dotfiler_posttypes();