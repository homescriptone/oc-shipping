<?php

class Sendbox_Shipping_API{
    //make a post to sendbox api using curl.
    public function post_on_api_by_curl($url, $data, $api_key)
    {
        $ch = curl_init($url);
        // Setup request to send json via POST.
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Authorization:' . $api_key));
        // Return response instead of printing.
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Send request.
        $result = curl_exec($ch);
        curl_close($ch);
        // Print response.
        return $result;
    }

    //make a get request using curl to sendbox

    public function get_api_response_by_curl($url)
    {
        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec($handle);
        curl_close($handle);
        return $output;
    }
    //make request to sendbox with header

    public function get_api_response_with_header($url, $request_headers){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
        $season_data = curl_exec($ch);
        if (curl_errno($ch)) {
            print "Error: " . curl_error($ch);
            exit();
        }
        // Show me the result
        curl_close($ch);
        return $season_data;

    }


    //all sendbox endpoints
    public function get_sendbox_api_url($url_type)
    {
        if ('delivery_quote' == $url_type) {
            $url = 'https://api.sendbox.ng/v1/merchant/shipments/delivery_quote';
        } elseif ('countries' == $url_type) {
            $url = 'https://api.sendbox.co/auth/countries?page_by={' . '"per_page"' . ':264}';
        } elseif ('states' == $url_type) {
            $url = 'https://api.sendbox.co/auth/states';
        } elseif ('shipments' == $url_type) {
            $url = 'https://api.sendbox.ng/v1/merchant/shipments';
        } elseif ('item_type' == $url_type) {
            $url = 'https://api.sendbox.ng/v1/item_types';
        } elseif ('profile' == $url_type) {
            $url = 'https://api.sendbox.co/oauth/profile';
        }else {
            $url = '';
        }
        return $url;
    }

}

class ControllerExtensionShippingSendbox extends Controller {
    private $error = array();

    public function index(){

        $this->load->language('extension/shipping/sendbox');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('sendbox', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('extension/shipping', 'token=' . $this->session->data['token'], 'SSL'));
        }

        $data['heading_title'] = $this->language->get('heading_title');
        $data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_state'] = $this->language->get('entry_state');

        //loading the breadcrumbs
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_shipping'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'].'&type=shipping', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/shipping/sendbox', 'user_token=' . $this->session->data['user_token'], true)
        );

        //get geo zones and status
        if (isset($this->request->post['custom_geo_zone_id'])) {
            $data['custom_geo_zone_id'] = $this->request->post['custom_geo_zone_id'];
        } else {
            $data['custom_geo_zone_id'] = $this->config->get('custom_geo_zone_id');
        }


        if (isset($this->request->post['sendbox_status'])) {
            $data['sendbox_status'] = $this->request->post['sendbox_status'];
        } else {
            $data['sendbox_status'] = $this->config->get('sendbox_status');
        }

        $sendbox_obj = new Sendbox_Shipping_API();
        $url = 'https://api.sendbox.co/auth/states?page_by={' . '"per_page"' . ':264}&filter_by={'.'"country_code"'.':"NG"'.'}';
        $nigeria_states = $sendbox_obj->get_api_response_by_curl($url);
        $nigeria_states = json_decode($nigeria_states)->results;


        if(isset($this->request->post['sendbox_state'])){
            $data['sendbox_state'] = $this->request->post['sendbox_state'];
        }else{
            $data['sendbox_state'] = $nigeria_states;
        }

        //get query params code form the new url and make a call to sendbox oauth

        $server_obj2 = $_SERVER;
        $query = parse_str($server_obj2["QUERY_STRING"], $get_code);
        $get_code = $query.($_GET["code"]);


        $oauth_obj = new Sendbox_Shipping_API();
        $url_oauth =('https://api.sendbox.co/oauth/access/access_token?app_id=5e2ee99918685a512f2c28c7&redirect_url=http://kidsthatcode.com.ng/webhook.php&client_secret=9cd808e9c64f24c35fb0d78025972d6f18462d5104eeb20f5f62ae998e60bcbc8e602d31a8bc71139062e960322b884c3074fcb58477675b9e95727b5faf708f&code='.$get_code.'');
        //$url_oauth =('https://api.sendbox.co/oauth/access/access_token?app_id=5e2ee99918685a512f2c28c7&redirect_url=http://kidsthatcode.com.ng/webhook.php&client_secret=9cd808e9c64f24c35fb0d78025972d6f18462d5104eeb20f5f62ae998e60bcbc8e602d31a8bc71139062e960322b884c3074fcb58477675b9e95727b5faf708f&code=589cc865665c');
        $oauth_res = $oauth_obj->get_api_response_by_curl($url_oauth);
        $oauth_obj = json_decode($oauth_res);
        $oauth = $oauth_obj->access_token;
        //var_dump($oauth);
        //die();

        if(isset($this->request->post['sendbox_auth_key'])){
            $data['sendbox_auth_key'] = $this->request->post['sendbox_auth_key'];
        }else{
            $data['sendbox_auth_key'] = $oauth;
        }

        //call profile endpoint for profile details
        $profile_obj = new Sendbox_Shipping_API();

        $auth_header = $oauth;
        $type = "application/json";

        $request_headers = array(
            "Content-Type: " .$type,
            "Authorization: " .$auth_header,
        );
        $profile_url = $profile_obj->get_sendbox_api_url('profile');
        $profile_res = $profile_obj->get_api_response_with_header($profile_url,$request_headers);


        $profile_obj = json_decode($profile_res);
        $sendbox_username = $profile_obj->name;
        $sendbox_phone =$profile_obj->phone;
        $sendbox_email = $profile_obj->email;

        if(isset($this->request->post['sendbox_username'])){
            $data['sendbox_username'] = $this->request->post['sendbox_username'];
        }else {
            $data['sendbox_username'] = $sendbox_username;
        }

        if(isset($this->request->post['sendbox_phone'])){
            $data['sendbox_phone'] = $this->request->post['sendbox_phone'];
        }else {
            $data['sendbox_phone'] = $sendbox_phone;
        }

        if(isset($this->request->post['sendbox_email'])){
            $data['sendbox_email'] = $this->request->post['sendbox_email'];
        }else {
            $data['sendbox_email'] = $sendbox_email;
        }

        if(isset($this->request->post['sendbox_store_address'])){
            $data['sendbox_store_address'] = $this->request->post['sendbox_store_address'];
        }else{
            $data['sendbox_store_address'] =  $this->config->get('config_address');
        }


        if(isset($this->request->post['sendbox_rates'])){
            $data['sendbox_rates'] = $this->request->post['sendbox_rates'];
        }else{
            $data['sendbox_rates'] = array('minimum', 'maximum');
        }

        if(isset($this->request->post['sendbox_pickup_types'])){
            $data['sendbox_pickup_types'] = $this->request->post['sendbox_pickup_types'];
        }else{
            $data['sendbox_pickup_types '] = array('pickup', 'drop-off');
        }

        //get zones status from oc root model
        $this->load->model('localisation/geo_zone');
        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();


        //calling header, leftcolumn and footer
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $static_url = HTTP_SERVER."admin/controller/extension/shipping/sendbox-webhook.php";
        $app_id = "5e2ee99918685a512f2c28c7";
        $server_obj = $_SERVER;
        $scopes= "profile";
        $user_token = $server_obj["QUERY_STRING"];
        parse_str($user_token, $output);
        $token = $output['user_token'];
        $_url = $server_obj["PHP_SELF"];
        $domain = $server_obj["HTTP_HOST"];
        $domain_name = $domain.$_url;

        $http_scheme = $server_obj['HTTPS'];
        $http_sch = "http://";
        if ($http_scheme){
            $http_sch = "https://";
        }

        $route = $output['route'];

        $state_params =  $http_sch.$domain_name.'?route='.$route.'&user_token='.$token ;

        var_dump($state_params);
        die();

        $sendbox_url = 'https://api.sendbox.co/oauth/access?app_id='.$app_id.'&redirect_url='.$static_url.'&state='.$state_params.'&scopes='.$scopes;
        //SEND EVERYBODY TO TWIG


        // $sendbox_url = 'https://api.sendbox.co/oauth/access?' ;
        $data['sendbox_url'] = $sendbox_url;
        $data['app_id']= $app_id;
        $data['state_params'] = $state_params;
        $data['static_url'] = $static_url;
        $data['scopes'] = $scopes;
        $data['url'] = $domain_name;
        $data['route'] = $route;
        $data['token']=$token;

        var_dump($sendbox_url);
        die();

        $this->response->setOutput($this->load->view('/shipping/sendbox', $data));

    }
}