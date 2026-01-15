<?php

class FMMR_init{

    public function __construct(){
        $this->create_endpoints();
    }

    public function create_endpoints(){
        add_action( 'rest_api_init', [$this,'create_event_endpoint']);
    }

    public function create_event_endpoint(){
        register_rest_route( 'fmmr/v1', '/create_update', array(
            'methods' => 'POST',
            'callback' => [$this,'create_update_event'],
            'permission_callback' => function () {
                return true;
                return current_user_can( 'edit_others_posts' );
              }
          ) );
        register_rest_route( 'fmmr/v1', '/location_block', array(
            'methods' => 'POST',
            'callback' => [$this,'create_update_location_block'],
            'permission_callback' => function () {
                return true;
                return current_user_can( 'edit_others_posts' );
              }
          ) );
        register_rest_route( 'fmmr/v1', '/ticket_attendee', array(
            'methods' => 'GET',
            'callback' => [$this,'get_ticket_data'],
            'permission_callback' => function () {
                return current_user_can( 'edit_others_posts' );
              }
          ) );
    }
/*'permission_callback' => function () {
    return current_user_can( 'edit_others_posts' );
}*/
    public function create_update_event($data){
        $params = $data->get_json_params();
        $event = new MREvent($params);
        return $event->response();
        
    }
    
    public function create_update_location_block($data){
        $params = $data->get_json_params();
        $location = new MRLocation($params);
        $location->sync_booking_blocks();
        return $location->response();
    }

    public function get_ticket_data($data){
        $data = $data->get_params();
        $date_from = null;
        $date_to = null;
        if(isset($data['date_from'])){
            $date_from = urldecode($data['date_from']);
        }
        if(isset($data['date_to'])){
            $date_to = urldecode($data['date_to']);
        }
        $orders = new MROrders($date_from,$date_to);
        $check_ins = new MRCheckIns($date_from,$date_to);
        return ['purchase'=>$orders->response(),'check-in'=>$check_ins->response()];
    }

    
}