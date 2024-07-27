<?php
class PhoneChecker {
    
    private $service;
    private $number;
    public $response;

    public function __construct( $number, $service = 'numverify' ) {

        if( $service == 'numverify' ) {
            $this->service = new Numverify( get_option('numverify_access_key') );
        }

        $this->number = $number;

    }

    public function verify() {

        $this->response = $this->service->verify_number( $this->number );
        return $this;

    }

    public function is_valid() {
        return $this->response->valid;
    }

    public function is_mobile() {
        return $this->response->line_type == 'mobile' ? true : false;
    }

    public function is_landline() {
        return $this->response->line_type == 'landline' ? true : false;
    }

    public function get_country() {
        return $this->response->country_name;
    }

    public function get_country_code() {
        return $this->response->country_code;
    }

    public function get_location() {
        return $this->response->location;
    }

    public function get_carrier() {
        return $this->response->carrier;
    }

    public function get_line_type() {
        return $this->response->line_type;
    }

    public function get_response() {
        return $this->response;
    }

}    