<?php
class Dotfiler_api {

    private $query;
    private $url_base;
    private $url_authority;
    private $url_oos;

    private $transportationAPI;

    public function __construct( $query ) {

        $api_key = get_option('dotfiler_api');
        $this->query = $query;

        $this->url_base = "https://mobile.fmcsa.dot.gov/qc/services/carriers/{$query}?webKey={$api_key}";
        $this->url_authority = "https://mobile.fmcsa.dot.gov/qc/services/carriers/{$query}/authority?webKey={$api_key}";
        $this->url_oos = "https://mobile.fmcsa.dot.gov/qc/services/carriers/{$query}/oos?webKey={$api_key}";

        $this->transportationAPI = new DataTransportationApi();

    }

    public function getTransGovData() {

        $this->transportationAPI->setFilters([
            'dot_number' => $this->query
        ]);

        return $this->transportationAPI->request();
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

    public function request_oos() {

        $response = wp_remote_get($this->url_oos);

        if (is_wp_error($response)) {
            // Handle error.
            //return 'Error: ' . $response->get_error_message();
        } else {
            // Get the body of the response.
            $body = wp_remote_retrieve_body($response);

            // Decode the JSON data.
            $data = json_decode($body, true);

            /*
            return array(
                "oosDate" => '2024-06-28',
                "oosReason"	=> 'NRC',
                "oosReasonDescription" => 'New Entrant Revoked - Refusal of Audit/No Contact'
            );
            */

            // Return the decoded data.
            return $data['content'][0]['oos'];
        }

    }

    public function request_saferweb() {

        $url = "https://saferwebapi.com/v2/usdot/snapshot/" . $this->query;

        $response = wp_remote_get( $url, array(
            'headers' => array(
            'x-api-key' => get_option('saferweb_api_key')
            )
        ) );

        if (is_wp_error($response)) {
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

}


