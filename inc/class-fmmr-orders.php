<?php

// class to get woocommerce orders for the past three days, returns json of order and attendee data 

class MROrders{
    /**
     * @var array
     */
    private $orders = [];
    private $date_from;
    private $date_to;

    public function __construct($date_from,$date_to){
        $this->date_from = $date_from;
        $this->date_to = $date_to;
        $this->get_orders();
    }

    private function get_orders(){
        if($this->date_from && $this->date_to){
            $this->get_orders_by_date();
        }
        elseif($this->date_from){
            $this->get_orders_by_date_from();
        }
        else{
            $this->get_all_orders();
        }   
    }

    public function get_orders_by_date(){
        $args = array(
            'date_modified' => strtotime($this->date_from).'...'.strtotime($this->date_to),
            'status' => ['wc-completed','wc-refunded'],
            'type' => 'shop_order',
            'limit' => -1,
        );
        $this->orders = wc_get_orders( $args );
    }

    public function get_orders_by_date_from(){
        $args = array(
            'date_modified' => '>=' . strtotime($this->date_from),
            'status' => ['wc-completed','wc-refunded'],
            'type' => 'shop_order',
            'limit' => -1,
        );
        $this->orders = wc_get_orders( $args );
    }

    public function get_all_orders(){
        $args = array(
            'date_modified' => '>=' . ( time() - DAY_IN_SECONDS * 3 ),
            'status' => ['wc-completed','wc-refunded'],
            'type' => 'shop_order',
            'limit' => -1,
        );
        $this->orders = wc_get_orders( $args );
    }

    public function response(){
        $refunds = [];
        $response = [];
        foreach($this->orders as $order){
            $order_id = $order->get_id();
            $status = $order->get_status();
            
            $types = array( 'line_item','coupon' );
            $items = $order->get_items( $types );
            $user = $order->get_user();
            $class = $order->get_type();
            $refunded = $order->get_refunds();
            $refunded_items = [];
            if(count($refunded)>0){
                if($status!='refunded')
                $status = 'partially refunded';
                foreach($refunded as $refund){
                    $refunded_items[] = $this->process_order_items($refund->get_items(),true,$order);
                }
            }
            $response[] = [
                'order_id'=>$order_id,
                'order_number'=>$order->get_order_number(),
                'order_date'=>$order->get_date_created()->date('Y-m-d H:i:s'),
                'order_modified'=>$order->get_date_modified()->date('Y-m-d H:i:s'),
                'attendee_name'=>$order->get_formatted_billing_full_name(),
                'attendee_id'=>$order->get_user_id(),
                'attendee_email'=>isset($user->user_email)?$user->user_email:$order->get_billing_email(),
                'attendee_phone'=>$order->get_billing_phone(),
                'attendee_address'=>['billing'=>$order->get_formatted_billing_address(),'shipping'=>$order->get_formatted_shipping_address()],
                'amount_paid'=>$order->get_total(),
                'payment_method'=>$order->get_payment_method_title()?:$order->get_payment_method(),
                'order_items'=>$this->process_order_items($items),
                'refunded_items'=>$refunded_items, 
                'discount'=>$this->process_order_discount($items),
                'coupons'=>$order->get_used_coupons(),
                'status'=>$status,
                'refund'=>$order->get_total_refunded(),
                'transferred'=>get_post_meta($order_id,'transferred_from',true),
            ];
            if($response['refund']){
                $response['amount_paid'] = floatval($response['amount_paid']) - floatval($response['refund']);
            }
        }
        return $response;
    }

    private function process_order_items($items,$refund = false,$order = false){
        $response = [];
        foreach($items as $item){
            if( $item->is_type( 'line_item' ) ) {
                $id = $item->get_product_id(); 
                try{
                    $event = new HdKEventsEvent(NULL,[$id]);
                }
                catch(Exception $e){
                    $event = false;
                }
                if($event){
                    if(!isset($response[$event->get_type()])){
                        $response[$event->get_type()]=[];
                    }
                    $response[$event->get_type()][]=[
                        'event_id'=>$event->get_event_id(),
                        'filemaker_id'=>get_post_meta( $event->get_event_id(), 'filemaker_id', true ),
                        'event_name'=>$event->get_name(),
                        'event_type'=>$event->get_type(),
                        'quantity'=>$item->get_quantity(),
                        'date'=>$event->get_product_date($id),
                        'paid'=>$item->get_subtotal(),
                        'coupon_applied'=>$this->has_coupon_applied($item),
                        'paid_after_coupon'=>$item->get_total(),
                        'payment_type'=>$event->get_payment_type()['type'],
                    ];
                    continue;
                }
                try{
                    $journal = new HdKJournal(NULL,$id);
                }
                catch(Exception $e){
                    $journal = false;
                }
                if($journal){
                    if(!isset($response['journal'])){
                        $response['journal']=[];
                    }
                    $response['journal'][]=[
                        'journal_id'=>$journal->get_journal_id(),
                        'journal_name'=>$journal->get_title(),
                        'quantity'=>$item->get_quantity(),
                        'paid'=>$item->get_subtotal(),
                    ];
                    continue;
                }
                /* try{
                    $journal_sub = new HdKJournalSubscription(NULL,$id);
                }
                catch(Exception $e){
                    $journal_sub = false;
                }
                if($journal_sub){
                    if(!isset($response['journal_subscription'])){
                        $response['journal_subscription']=[];
                    }
                    $response['journal_subscription'][]=[
                        'journal_subscription_id'=>$journal->get_journal_subscription_id(),
                        'journal_subscription_name'=>$journal->get_title(),
                        'quantity'=>$item->get_quantity(),
                        'paid'=>$item->get_subtotal(),
                    ];
                    continue;
                } 
                Check if this is a venue booking
               */
                try{
                    $venue = new HdKEventsVenueBooking(NULL,[$id]);
                }
                catch(Exception $e){
                    $venue = false;
                }
                if($venue){
                    if(!isset($response['location_booking'])){
                        $response['location_booking']=[];
                    }
                    if($refund && $order){
                        $refunded_item_id = $item->get_meta('_refunded_item_id');
                        $refunded_item = $order->get_item($refunded_item_id);
                        $slot = $refunded_item->get_meta('_slot');
                    }
                    else{
                        $slot = $item->get_meta('_slot');
                    }
                    $response['location_booking'][]=[
                        'location_id'=>$venue->get_venue_id(),
						'location_name'=>get_the_title($venue->get_venue_id()),
						'slot_length'=>get_post_meta($id,'slot_length',true),
						'slot_date'=> date('Y-m-d H:i:s',strtotime($slot)),
                        'paid'=>$item->get_subtotal(),
                    ];
                    continue;
                }
                if(get_post_meta($id,'class_card',true)){
                    if(!isset($response['class_card'])){
                        $response['class_card']=[];
                    }
                    $response['class_card'][]=[
                        'class_card_id'=>$id,
                        'class_card_name'=>$item->get_name(),
                        'quantity'=>$item->get_quantity(),
                        'paid'=>$item->get_subtotal(),
                    ];
                    continue;
                }
                if(has_term('journal-subscription','product-cat',$id)){
                    if(!isset($response['journal_subscription'])){
                        $response['journal_subscription']=[];
                    }
                    $response['journal_subscription'][]=[
                        'journal_subscription_id'=>$id,
                        'journal_subscription_name'=>$item->get_name(),
                        'quantity'=>$item->get_quantity(),
                        'paid'=>$item->get_subtotal(),
                    ];
                    continue;
                }
                if(has_term('merchandise','product-cat',$id)){
                    if(!isset($response['merchandise'])){
                        $response['merchandise']=[];
                    }
                    $response['merchandise'][]=[
                        'merchandise_id'=>$id,
                        'merchandise_name'=>$item->get_name(),
                        'quantity'=>$item->get_quantity(),
                        'paid'=>$item->get_subtotal(),
                    ];
                    continue;
                }
                if(!isset($response['product'])){
                    $response['product']=[];
                }
                $response['product']=[
                    'product_id'=>$id,
                    'product_name'=>$item->get_name(),
                    'product_type'=>'product',
                    'quantity'=>$item->get_quantity(),
                    'paid'=>$item->get_subtotal(),
                ];

            }
        }
        return $response;
    }

    // a coupon was applied to this line item if its pre-discount subtotal differs from its post-discount total
    private function has_coupon_applied($item){
        return $item->get_subtotal() != $item->get_total();
    }

    private function process_order_discount($items){
        $return = [];
        foreach($items as $item){
            if( $item->is_type( 'coupon' ) ) {
                $coupon_id = $item->get_id();
                $coupon = new WC_Coupon($coupon_id);
                $return[]= [
                    'coupon_id'=>$coupon_id,
                    'coupon_name'=>$item->get_code(),
                    'coupon_type'=>$coupon->get_discount_type(),
                    'quantity'=>$item->get_quantity(),
                    'discount'=>$item->get_discount(),
                ];
            }
        }
        return $return;
    }

}