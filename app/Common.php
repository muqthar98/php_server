<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use File;
use DB;

class Common extends Model
{

    public static function InitialAvtar($name)
    {

        $fullname = explode(" ", $name);
        $count = count($fullname);
        if ($count >= 3) {
            $firstname = $fullname[count($fullname) - 3];
            $middlename = $fullname[count($fullname) - 2];
            $lastname = $fullname[count($fullname) - 1];
        } else if ($count == 2) {
            $firstname = $fullname[count($fullname) - 2];
            $lastname = $fullname[count($fullname) - 1];
        } else {
            $firstname = $name;
        }

        if (!empty($firstname) && !empty($lastname)) {
            $fullname = $firstname . "+" . $lastname;
        } else {
            $fullname = $firstname;
        }

        return "https://ui-avatars.com/api/?name=" . $fullname . "&length=1&rounded=true&background=fdd900&color=000";
    }

    public static function time_elapsed_string($datetime, $full = false) {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);
    
        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;
    
        $string = array(
            'y' => 'year',
            'm' => 'month',
            'w' => 'week',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        );
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }
    
        if (!$full) $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' ago' : 'just now';
    }

    public static function CheckProductExist($product_id)
    {
        $ProductData =  Product::where('product_id',$product_id)->first();
        return $ProductData;
    }

    public static function CheckProductUnitExist($product_id,$price_unit_id)
    {
        $ProductData =  ProductPriceUnit::where('product_id',$product_id)->where('price_unit_id',$price_unit_id)->first();
        return $ProductData;
    }

    public static function GetProductDataByCategory($data,$user_id="")
    {

        $Productlist = [];
        $i = 0;
        foreach($data as $catkey => $catvalue){
            $ProductData =  Product::select('*')
                        ->where('category_id', $catvalue['id'])
                        ->orderBy('id','DESC')
                        ->offset(0)
                        ->limit(8)
                        ->get();
            foreach($ProductData as $key => $value){
                $list['id'] = $value['id'];
                $list['product_id'] = $value['product_id'];
                $list['product_name'] = $value['name'];
                $list['description'] = $value['description'] ? $value['description'] : "";
                $list['category_id'] = $value['category_id'];
                $list['category_name'] = $catvalue['category_name'];
                if($value['stock_status'] == 1){
                    $stockquantity = 1;
                    $list['stock_status'] = 'In Stock';
                }else{
                    $stockquantity = 0;
                    $list['stock_status'] = 'Out of Stock';
                }
            
                // $list['stock_quantity'] = $stockquantity;
                $ProductPriceUnitData = ProductPriceUnit::select('tbl_product_price_unit.*', 'U.unit_name')
                    ->leftjoin('tbl_unit as U', 'tbl_product_price_unit.unit_id', 'U.id')
                    ->where('tbl_product_price_unit.product_id',$value['product_id'])
                    ->orderBy('tbl_product_price_unit.unit','DESC')
                    ->get();	
                $price_unit = [];

                foreach($ProductPriceUnitData as $k => $val){
                    
                    if($val['discount']){
                        $discount_price = ($val['price']*$val['discount'])/100;
                        $discount_price = $val['price'] - $discount_price;
                        if($discount_price < 0){
                            $discount_price = 0;
                        }
                    }else{
                        $discount_price = 0;
                    }

                    $priceunit['price_unit_id'] = $val['price_unit_id'];
                    $priceunit['price'] = $val['price'];
                    $priceunit['discount_price'] = $discount_price;
                    $priceunit['unit'] = $val['unit'].' '.$val['unit_name'];

                    if($user_id){
                        $CartData =  Cart::where('price_unit_id',$val['price_unit_id'])->where('user_id',$user_id)->first();	
                        if($CartData){
                            $priceunit['is_cart'] = 1;
                            $priceunit['quantity'] = $CartData['quantity'];
                        }else{
                            $priceunit['is_cart'] = 0;
                            $priceunit['quantity'] = 0;
                        }	
                    }else{
                        $priceunit['is_cart'] = 0;
                        $priceunit['quantity'] = 0;
                    }
                    
                    $price_unit[] = $priceunit;
                }

                $list['price_unit'] = $price_unit;
                $product_image = explode(',',$value['product_image']);
                $list['product_image'] = $product_image;
                // $list['coupon_code'] = $value['coupon_code'];
                
                if($user_id){
                    $WishlistData =  Wishlist::where('product_id',$value['product_id'])->where('user_id',$user_id)->first();
                    if($WishlistData){
                        $list['is_wishlist'] = 1;
                    }else{
                        $list['is_wishlist'] = 0;
                    }	
                }else{
                    $list['is_wishlist'] = 0;
                }

                $Productlist[$value['category_id']]['category_id'] = $value['category_id'];
                $Productlist[$value['category_id']]['category_name'] = $catvalue['category_name'];
                $Productlist[$value['category_id']]['products'][] = $list;
            }
             
        }
        return $Productlist;
    }

    public static function GetProductData($data,$user_id="",$flag=0,$sort_by=0)
    {

        $Productlist = [];
        $i = 0;
        foreach($data as $key => $value){

            $list['id'] = $value['id'];
            $list['product_id'] = $value['product_id'];
            $list['product_name'] = $value['name'];
            $list['description'] = $value['description'] ? $value['description'] : "";
            $list['category_id'] = $value['category_id'];
            $list['category_name'] = $value['category_name'];
            if($value['stock_status'] == 1){
                $stockquantity = 1;
                $list['stock_status'] = 'In Stock';
            }else{
                $stockquantity = 0;
                $list['stock_status'] = 'Out of Stock';
            }
           
            // $list['stock_quantity'] = $stockquantity;
            $ProductPriceUnitData = ProductPriceUnit::select('tbl_product_price_unit.*', 'U.unit_name')
                ->leftjoin('tbl_unit as U', 'tbl_product_price_unit.unit_id', 'U.id')
                ->where('tbl_product_price_unit.product_id',$value['product_id'])
                ->orderBy('tbl_product_price_unit.unit','DESC')
                ->get();	
            $price_unit = [];

            foreach($ProductPriceUnitData as $k => $val){
                
                if($val['discount']){
                    $discount_price = ($val['price']*$val['discount'])/100;
                    $discount_price = $val['price'] - $discount_price;
                    if($discount_price < 0){
                        $discount_price = 0;
                    }
                }else{
                    $discount_price = 0;
                }

                $priceunit['price_unit_id'] = $val['price_unit_id'];
                $priceunit['price'] = $val['price'];
                $priceunit['discount_price'] = $discount_price;
                $priceunit['unit'] = $val['unit'].' '.$val['unit_name'];

                if($user_id){
                    $CartData =  Cart::where('price_unit_id',$val['price_unit_id'])->where('user_id',$user_id)->first();	
                    if($CartData){
                        $priceunit['is_cart'] = 1;
                        $priceunit['quantity'] = $CartData['quantity'];
                    }else{
                        $priceunit['is_cart'] = 0;
                        $priceunit['quantity'] = 0;
                    }	
                }else{
                    $priceunit['is_cart'] = 0;
                    $priceunit['quantity'] = 0;
                }
                
                $price_unit[] = $priceunit;
            }

            $list['price_unit'] = $price_unit;
            $product_image = explode(',',$value['product_image']);
            $list['product_image'] = $product_image;
            // $list['coupon_code'] = $value['coupon_code'];
               
            if($user_id){
                $WishlistData =  Wishlist::where('product_id',$value['product_id'])->where('user_id',$user_id)->first();
                if($WishlistData){
                    $list['is_wishlist'] = 1;
                }else{
                    $list['is_wishlist'] = 0;
                }	
            }else{
                $list['is_wishlist'] = 0;
            }

            if($flag == 0){
           
                $Productlist[$value['category_id']]['category_id'] = $value['category_id'];
                $Productlist[$value['category_id']]['category_name'] = $value['category_name'];
                $Productlist[$value['category_id']]['products'][] = $list;
                              
            }elseif($flag == 1){              
                $Productlist[] = $list;
               
            }elseif($flag == 3){     
                $CheckReview = ProductReview::select('tbl_product_review.id','U.user_id','U.first_name','U.last_name','U.profile_image','tbl_product_review.review','tbl_product_review.rating')->leftjoin('tbl_users as U', 'tbl_product_review.user_id', 'U.user_id')->where('tbl_product_review.product_id',$value['product_id'])->get();
                if(count($CheckReview) > 0){
                    $total_rating = ProductReview::where('product_id',$value['product_id'])->sum('rating');
                    $list['total_review'] = count($CheckReview); 
                    $list['avaerage_rating'] = $total_rating/count($CheckReview);    
                    $list['review'] = $CheckReview;           
                    
                }else{
                    $list['total_review'] = 0; 
                    $list['avaerage_rating'] = 0;    
                    $list['review'] = []; 
                }
               
                $Productlist[] = $list;
                
            }elseif($flag == 2){  
                $Productlist['category_id'] = $value['category_id'];
                $Productlist['category_name'] = $value['category_name'];
                $Productlist['products'][] = $list;
            }
        }
        
        return $Productlist;
    }

    public static function GetSortingProductData($data,$user_id="",$sort_by=0)
    {
        $Pricelist = [];
        foreach($data as $key => $val){

            $priceunitdata = explode(',',$val['price_unit_id']);
            $pricedata = explode(',',$val['price']);
            $discountdata = explode(',',$val['discount']);
            $unitdata = explode(',',$val['unit']);
            $unitiddata = explode(',',$val['unit_id']);
            $i=0;

            foreach($priceunitdata as $pricekey => $priceval){

                $list['id'] = $val['id'];
                $list['product_id'] = $val['product_id'];
                $list['product_name'] = $val['name'];
                $list['description'] = $val['description'] ? $val['description'] : "";
                $list['category_id'] = $val['category_id'];
                $list['category_name'] = $val['category_name'];
                if($val['stock_status'] == 1){
                    $stockquantity = 1;
                    $list['stock_status'] = 'In Stock';
                }else{
                    $stockquantity = 0;
                    $list['stock_status'] = 'Out of Stock';
                }
                
                $product_image = explode(',',$val['product_image']);
                $list['product_image'] = $product_image;
                // $list['coupon_code'] = $val['coupon_code'];
                    
                if($user_id){
                    $WishlistData =  Wishlist::where('product_id',$val['product_id'])->where('user_id',$user_id)->first();
                    if($WishlistData){
                        $list['is_wishlist'] = 1;
                    }else{
                        $list['is_wishlist'] = 0;
                    }	
                }else{
                    $list['is_wishlist'] = 0;
                }

                if(isset($discountdata[$i])){
                    $discount_price = ((float)$pricedata[$i]*(float)$discountdata[$i])/100;
                    $discount_price = (float)$pricedata[$i] - (float)$discount_price;
                    if($discount_price < 0){
                        $discount_price = 0;
                    }
                }else{
                    $discount_price = 0;
                }
                $list['price_unit_id'] = $priceval;
                $list['price'] = $pricedata[$i];
                $list['discount_price'] = $discount_price;
                $UnitData1 = Unit::where('id',$unitiddata[$i])->first();
                $list['unit'] = $unitdata[$i].' '.$UnitData1['unit_name'];

                if($user_id){
                    $CartData =  Cart::where('price_unit_id',$priceval)->where('user_id',$user_id)->first();	
                    if($CartData){
                        $list['is_cart'] = 1;
                        $list['quantity'] = $CartData['quantity'];
                    }else{
                        $list['is_cart'] = 0;
                        $list['quantity'] = 0;
                    }	
                }else{
                    $list['is_cart'] = 0;
                    $list['quantity'] = 0;
                }
                $list['discount'] = isset($discountdata[$i]) ? $discountdata[$i] : "";
                $i++;
            }
            $Pricelist[] = $list;
        }
       
        if($sort_by == 1){
            usort($Pricelist, function ($a, $b) {return $a['price'] > $b['price'];});
        }
        if($sort_by == 2){
            usort($Pricelist, function ($a, $b) {return $a['price'] < $b['price'];});
        }
        if($sort_by == 4){
            usort($Pricelist, function ($a, $b) {return $a['discount_price'] < $b['discount_price'];});
        }
        if($sort_by == 5){
            usort($Pricelist, function ($a, $b) {return $a['discount_price'] > $b['discount_price'];});
        }
        if($sort_by == 6){
            usort($Pricelist, function ($a, $b) {return $a['discount'] < $b['discount'];});
        }
      
        $temp_array = $new_array = array();
        foreach($Pricelist as $key => $arr_values){
            if(!in_array($arr_values['product_id'], $temp_array)){
                array_push($temp_array, $arr_values['product_id']);
                $priceData['price_unit_id'] = $arr_values['price_unit_id'];
                $priceData['price'] = $arr_values['price'];
                $priceData['discount_price'] = $arr_values['discount_price'];
                $priceData['unit'] = $arr_values['unit'];
                $priceData['is_cart'] = $arr_values['is_cart'];
                $priceData['quantity'] = $arr_values['quantity'];
                $Pricelist[$key]['price_unit'][] = $priceData;

                $ProductPriceUnitData = ProductPriceUnit::select('tbl_product_price_unit.*', 'U.unit_name')
                ->leftjoin('tbl_unit as U', 'tbl_product_price_unit.unit_id', 'U.id')
                ->where('tbl_product_price_unit.price_unit_id', '!=' ,$Pricelist[$key]['price_unit_id'])
                ->where('tbl_product_price_unit.product_id', $Pricelist[$key]['product_id'])
                ->orderBy('tbl_product_price_unit.price','DESC')
                ->get();
                foreach($ProductPriceUnitData as $k => $val){
                
                    if($val['discount']){
                        $discount_price = ($val['price']*$val['discount'])/100;
                        $discount_price = $val['price'] - $discount_price;
                        if($discount_price < 0){
                            $discount_price = 0;
                        }
                    }else{
                        $discount_price = 0;
                    }
    
                    $priceunit['price_unit_id'] = $val['price_unit_id'];
                    $priceunit['price'] = $val['price'];
                    $priceunit['discount_price'] = $discount_price;
                    $priceunit['unit'] = $val['unit'].' '.$val['unit_name'];
    
                    if($user_id){
                        $CartData =  Cart::where('price_unit_id',$val['price_unit_id'])->where('user_id',$user_id)->first();	
                        if($CartData){
                            $priceunit['is_cart'] = 1;
                            $priceunit['quantity'] = $CartData['quantity'];
                        }else{
                            $priceunit['is_cart'] = 0;
                            $priceunit['quantity'] = 0;
                        }	
                    }else{
                        $priceunit['is_cart'] = 0;
                        $priceunit['quantity'] = 0;
                    }
                    
                    $Pricelist[$key]['price_unit'][] = $priceunit;
                }
                unset($Pricelist[$key]['price_unit_id']);
                unset($Pricelist[$key]['price']);
                unset($Pricelist[$key]['discount_price']);
                unset($Pricelist[$key]['unit']);
                unset($Pricelist[$key]['is_cart']);
                unset($Pricelist[$key]['quantity']);
                	
                array_push($new_array,$Pricelist[$key]);
            } 
        } 
     
        return $new_array;
    }

    public static function in_array_r($needle, $haystack, $strict = false) {
        foreach ($haystack as $item) {
            if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && in_array_r($needle, $item, $strict))) {
                return true;
            }
        }
    
        return false;
    }
    public static function delivery_recursive($delivery,$flag){
        foreach($delivery as $value){
            // print_r($value);
            if($value['start_time'] && $value['end_time']){
                if($flag == 1){
                    $ts1 = strtotime(date("Y-m-d h:iA"));
                    $ts2  = date("Y-m-d h:iA", strtotime($value['start_time']));
                    $ts2 = strtotime($start_time);
                    $ts3  = date("Y-m-d h:iA", strtotime($value['end_time']));
                    $ts3 = strtotime($time_format2);

                    if(($ts1 <= $ts3) || ($ts2 >= $ts1)){               
                        $area[] = $value;
                    }
                }else{
                    $area[] = $value;
                }
            }
        }
        return $area;
    }


    public static function send_push($fcmtoken,$title,$message,$plateform)
    {
        if($plateform == 1)
        {
            $customData =  array("message" =>$message);
            
            $url = 'https://fcm.googleapis.com/fcm/send';

            $api_key = env('FCM_TOKEN');

           // $fields = array (
            //     'registration_ids' => array (
            //         $fcmtoken
            //     ),
            //     'data' => $customData
            // );

            $notification = array('title' =>$title , 'body' => $message, 'sound' => 'default', 'badge' => '1');
            $fields = array('to' => $fcmtoken, 'notification' => $notification,'priority'=>'high');

            $headers = array(
                'Content-Type:application/json',
                'Authorization:key='.$api_key
            );

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
            // print_r(json_encode($fields));
            $result = curl_exec($ch);
            if ($result === FALSE) {
                die('FCM Send Error: ' . curl_error($ch));
            }
            curl_close($ch);
            // print_r($result);
            return $result;
        }
        else
        {
            $url = 'https://fcm.googleapis.com/fcm/send';

            $api_key = env('FCM_TOKEN');

            // $title = $message;

            // $msg = array ( 'title' => 'this is title', 'body' => 'this is a description');

            // $message = array(
            //     "message" => $title,
            //     "data" => $message,
            // );

            // $data = array('registration_ids' => array($fcmtoken));
            // $data['data'] = $message;
            // $data['notification'] = $msg;
            // $data['notification']['sound'] = "default";

            //Creating the notification array.

            $msg = array ( 'title' => $title, 'body' => $message);

            $message = array(
                "message" => $title,
                "data" => $message,
            );
    
            $data = array('registration_ids' => array($fcmtoken));
            $data['data'] = $message;
            $data['notification'] = $msg;
            $data['notification']['sound'] = "default";

            $headers = array(
                'Content-Type:application/json',
                'Authorization:key='.$api_key
            );

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            //echo json_encode($data);
            $result = curl_exec($ch);
            if ($result === FALSE) {
                die('FCM Send Error: ' . curl_error($ch));
            }
            curl_close($ch);
            // print_r($result);
            return $result;
        }

    }

}
