<?php
class Dotfiler_init {

    public function __construct() {

        // Authorize.net
        require_once DOTFILER_BASE_URL.'/classes/authnet/authnet.class.php';
        require_once DOTFILER_BASE_URL.'/classes/authnet/authnet.refund.php';
        require_once DOTFILER_BASE_URL.'/classes/authnet/authnet.errors.php';

        // API class
        require_once DOTFILER_BASE_URL.'/classes/dotfiler/api.class.php';
        require_once DOTFILER_BASE_URL.'/classes/numverify/numverify.api.php';

        // Admin classes
        require_once DOTFILER_BASE_URL.'/classes/admin/admin.class.php';
        require_once DOTFILER_BASE_URL.'/classes/admin/posttypes.class.php';
        require_once DOTFILER_BASE_URL.'/classes/admin/authnet.account.php';
        require_once DOTFILER_BASE_URL.'/classes/admin/authnet.error.php';
        //require_once DOTFILER_BASE_URL.'/classes/admin/phone.validation.php';

        // Formidable Extensions
        require_once DOTFILER_BASE_URL.'/classes/shortlinks/shortlinks.class.php';
        require_once DOTFILER_BASE_URL.'/classes/shortlinks/shortlinks.actions.php';
        require_once DOTFILER_BASE_URL.'/classes/shortlinks/shortlinks.wrapper.php';
        require_once DOTFILER_BASE_URL.'/classes/twillio/twillio.extension.php';
        require_once DOTFILER_BASE_URL.'/classes/formidable/entry.cleaner.php';
        require_once DOTFILER_BASE_URL.'/classes/formidable/entry.helper.php';
        require_once DOTFILER_BASE_URL.'/classes/formidable/entry.archive.php';

        // Validators
        require_once DOTFILER_BASE_URL.'/classes/validators/phonechecker.class.php';
        require_once DOTFILER_BASE_URL.'/classes/validators/phonechecker.helper.php';

        // CRON
        require_once DOTFILER_BASE_URL.'/classes/cron/schedules.cron.php';
        require_once DOTFILER_BASE_URL.'/classes/cron/formidable/entrycleaner.cron.php';

        // Migrations
        $this->include_migrations();

        // Shortcodes
        $this->include_shortcodes();

        // Hooks
        $this->include_hooks();

    }

    private function include_migrations() {

        // Entries cleaner extra tables
        require_once DOTFILER_BASE_URL.'/classes//migrations/archive.entries.php';

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

        // Phone validation
        require_once DOTFILER_BASE_URL.'/shortcodes/phone.validate.php';


    }

    private function include_hooks() {

        // Formidable forms processing
        require_once DOTFILER_BASE_URL.'/actions/formidable.php';

        // Ajax
        require_once DOTFILER_BASE_URL.'/actions/ajax.php';
        require_once DOTFILER_BASE_URL.'/actions/ajax/phone.validate.php';

        // Page CSS/JS scripts
        require_once DOTFILER_BASE_URL.'/actions/page.php';

    }

}

new Dotfiler_init();