<?php
/*
Plugin Name: Dotfiler API + Formiddable forms
Description: Dotfiler API + Formiddable forms
Version: 1.0
Plugin URI: 
Author URI: 
Author: Stanislav Matrosov
*/

// Variables
define('DOTFILER_BASE_URL', __DIR__);

// Initialize core
require_once 'classes/init.class.php';


add_action('init', 'init44');
function init44() {

    if( $_GET['test'] ) {


        print_r($creds);

        die();

    }

}

