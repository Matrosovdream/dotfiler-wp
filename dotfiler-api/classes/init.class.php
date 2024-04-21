<?php
class Dotfiler_init {

    public function __construct() {

        require_once(DOTFILER_BASE_URL.'/classes/api.class.php');
        require_once(DOTFILER_BASE_URL.'/classes/admin.class.php');
        require_once(DOTFILER_BASE_URL.'/classes/posttypes.class.php');
        require_once(DOTFILER_BASE_URL.'/classes/authnet.class.php');

        require_once(DOTFILER_BASE_URL.'/actions/ajax.php');

    }

}

new Dotfiler_init();