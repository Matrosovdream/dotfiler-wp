<?php
abstract class Dotfiler_api {

    protected $query;
    protected $url;

    public function __construct( $query ) {

        $this->query = $query;

    }    

    abstract public function request();
    
    protected function handle_response( $response ) {

        if (is_wp_error($response)) {
            return 
            //return 'Error: ' . $response->get_error_message();
        } else {
            // Get the body of the response.
            $body = wp_remote_retrieve_body($response);

            // Decode the JSON data.
            $data = json_decode($body, true);

            // Return the decoded data.
            return $this->handle_response_data( $data );
        }

    }

    protected function handle_response_data( $data ) {

        return $data;

    }


}