<?php
class Formidable_shortlinks_wrapper {

    public $shortcode;
    public $html='';
    private $shortener;

    public function __construct( $shortcode ) {

        $this->shortcode = $shortcode;

        $this->shortener = new Formidable_shortlinks();

    }

    public function replace_link() {

        // Catch the root shortcode
        $this->full_shortcode();

        // Retrieve url
        $url = $this->retrieve_url();

        // Generate shortlink
        $new_url = $this->generate_shortlink( $url );
        if( isset($new_url) ) { $url = $new_url; }

        // Replace url
        $this->replace_url( $url );

        return $this;

    }

    private function full_shortcode() {

        ob_start();
        echo do_shortcode( $this->shortcode );
        $this->html = ob_get_clean();

    }

    private function retrieve_url() {

        preg_match('/href="([^"]+)"/', $this->html, $matches);
        return $matches[1];

    }

    private function replace_url( $url ) {
        $this->html = preg_replace('/href="([^"]+)"/', "href='{$url}'", $this->html);
    }

    private function generate_shortlink( $url ) {
        return $this->shortener->generate_shortlink( $url );
    }

    public function get_html() {
        return $this->html;
    }

}