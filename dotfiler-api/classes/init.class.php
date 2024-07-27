<?php
class Dotfiler_init {

    public function __construct() {

        // Authorize.net
        require_once DOTFILER_BASE_URL.'/classes/authnet/authnet.class.php';
        require_once DOTFILER_BASE_URL.'/classes/authnet/authnet.refund.php';
        require_once DOTFILER_BASE_URL.'/classes/authnet/authnet.errors.php';

        // API class
        require_once DOTFILER_BASE_URL.'/classes/dotfiler/api.class.php';

        // Admin classes
        require_once DOTFILER_BASE_URL.'/classes/admin/admin.class.php';
        require_once DOTFILER_BASE_URL.'/classes/admin/posttypes.class.php';
        require_once DOTFILER_BASE_URL.'/classes/admin/authnet.account.php';
        require_once DOTFILER_BASE_URL.'/classes/admin/authnet.error.php';

        // Ajax
        require_once DOTFILER_BASE_URL.'/actions/ajax.php';

        // Formidable Extensions
        require_once DOTFILER_BASE_URL.'/classes/shortlinks/shortlinks.class.php';
        require_once DOTFILER_BASE_URL.'/classes/shortlinks/shortlinks.actions.php';
        require_once DOTFILER_BASE_URL.'/classes/shortlinks/shortlinks.wrapper.php';

        // Shortcodes
        $this->include_shortcodes();

        // Hooks
        $this->include_hooks();

    }

    private function include_shortcodes() {

        // Refund
        require_once DOTFILER_BASE_URL.'/shortcodes/payment.refund.php';
        require_once DOTFILER_BASE_URL.'/shortcodes/refund.history.php';
        require_once DOTFILER_BASE_URL.'/shortcodes/charged.history.php';
        require_once DOTFILER_BASE_URL.'/shortcodes/creds.history.php';

        // Failed payment
        require_once DOTFILER_BASE_URL.'/shortcodes/paystatus.history.php';

        // Form handling with the API data
        require_once DOTFILER_BASE_URL.'/shortcodes/form.results.php';
        require_once DOTFILER_BASE_URL.'/shortcodes/form.results.mobile.php';
        require_once DOTFILER_BASE_URL.'/shortcodes/form.errorblock.php';

        // Formidable entries
        require_once DOTFILER_BASE_URL.'/shortcodes/entry.shortlink.php';


    }

    private function include_hooks() {

        // Formidable forms processing
        require_once DOTFILER_BASE_URL.'/actions/formidable.php';

    }

}

new Dotfiler_init();