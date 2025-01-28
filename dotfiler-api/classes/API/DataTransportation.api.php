<?php
class DataTransportationApi {

    private $api_url;
    private $response;
    private $errors = [];
    public $filters = [];
    private $verified_filters = [
        'dot_number',
    ];

    public function __construct( $filters=[] ) {

        $this->api_url = 'https://data.transportation.gov/resource/az4n-8mr2.json';

        if ( !empty($filters) ) {
            $this->setFilters($filters);
        }

    }

    public function setFilters( $filters ) {

        // Verify the filter key and remove that are not in the list
        $filters = $this->verifyFilters($filters);

        // Set in a class
        $this->filters = $filters;

        // Add the filter to the api url
        $this->api_url .= '?' . http_build_query($filters);

    }

    public function getFilters() {
        return $this->filters ?? [];
    }

    public function getErrors() {
        return $this->errors;
    }

    private function verifyFilters( $filter ) {

        foreach ($filter as $key => $value) {
            if ( !in_array($key, $this->verified_filters) ) {
                unset($filter[$key]);
            }
        }

        return $filter;

    }

    public function request() {

        $this->response = wp_remote_get($this->api_url);
        return $this->process_response();

    }

    private function process_response() {
            
        $response = $this->response;

        if (is_wp_error($response)) {
            $this->errors[] = $response->get_error_message();
        } else {

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            return $data[0];
        }
    }

}