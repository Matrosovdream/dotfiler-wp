<?php
class Dotfiler_api {

    private $query;
    private $url_base;
    private $url_authority;

    public function __construct( $query ) {

        //$api_key = '54ccd007f7995f515f3e9ede3e73572e69650390';
        $api_key = get_option('dotfiler_api');
        $this->query = $query;

        $this->url_base = "https://mobile.fmcsa.dot.gov/qc/services/carriers/{$query}?webKey={$api_key}";
        $this->url_authority = "https://mobile.fmcsa.dot.gov/qc/services/carriers/{$query}/authority?webKey={$api_key}";

    }

    public function request_base() {

        $response = wp_remote_get($this->url_base);

        if (is_wp_error($response)) {
            // Handle error.
            //return 'Error: ' . $response->get_error_message();
        } else {
            // Get the body of the response.
            $body = wp_remote_retrieve_body($response);

            // Decode the JSON data.
            $data = json_decode($body, true);

            // Return the decoded data.
            return $data;
        }

    }

    public function request_authority() {

        $response = wp_remote_get($this->url_authority);

        if (is_wp_error($response)) {
            // Handle error.
            //return 'Error: ' . $response->get_error_message();
        } else {
            // Get the body of the response.
            $body = wp_remote_retrieve_body($response);

            // Decode the JSON data.
            $data = json_decode($body, true);

            // Return the decoded data.
            return $data['content'][0]['carrierAuthority'];
        }

    }

    public function request_saferweb() {

        $url = "https://saferwebapi.com/v2/usdot/snapshot/" . $this->query;

        $curl = curl_init();
        
        curl_setopt_array($curl, array(
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'GET',
          CURLOPT_HTTPHEADER => array(
            'x-api-key: '.get_option('saferweb_api_key')
          ),
        ));
        
        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode( $response, true );        

    }

}


