<?php
/*
Plugin Name: Dotfiler API + Formiddable forms
Description: Dotfiler API + Formiddable forms
Version: 1.0
Plugin URI: 
Author URI: 
Author: Stanislav Matrosov
*/

// We need it to work with Sessions
add_action('init', 'start_session', 1);
function start_session() {
    if(!session_id()) {
        @session_start();
    }
}

define('DOTFILER_BASE_URL', __DIR__);

// Init class
require_once('classes/init.class.php');