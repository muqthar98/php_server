<?php


namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Validator;
use Session;
use DB;
use Crypt;
use Hash;
use App\Admin;
use App\User;
use App\Product;
use App\Cart;
use App\PaymentSummary;
use App\Coupon;
use App\Order;
use App\OrdersItem;
use App\UserDeliveryAddress;
use App\UsersCoupon;
use App\Complaint;
use App\ProductReview;
use App\OrderReview;
use App\Notification;
use App\Common;
use App\Settings;
use Log;

class OrdersController extends Controller
{
  
	public function getCouponList(Request $request)
    {
        try {
            $user_id = $request->user()->user_id;

            if (empty($user_id)) {
                $msg = "user id is required";
                return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => $msg]);
            }

            $headers = $request->headers->all();
                
            $verify_request_base = Admin::verify_request_base($headers);

            if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
                return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
                exit();
            }
            
            $CheckUser =  User::where('user_id', $user_id)->where('user_type',0)->first();
            if (empty($CheckUser)) {
                return response()->json(['status' => 401, 'message' => "User Not Found"]);
            }

			$CouponData =  Coupon::select('id','coupon_code','discount_type','coupon_discount','minimum_amount')->get();  
            
            $Data = [];
            if (!empty($CouponData)) {
                $i=0;
                foreach ($CouponData as $key => $value) {
                    $usedCoupon = UsersCoupon::where('coupon_code',$value['coupon_code'])->where('user_id', $user_id)->get();  
                    if(count($usedCoupon) <= 0){
                        $Data[$i]['id'] = $value['id'];
                        $Data[$i]['coupon_code'] = $value['coupon_code'];
                        $Data[$i]['discount_type'] = $value['discount_type'];
                        $Data[$i]['coupon_discount'] = $value['coupon_discount'];
                        $Data[$i]['minimum_amount'] = $value['minimum_amount'];
                        $i++;
                    }
                }             
                return response()->json(['status' => 200, 'message' => "Coupon Data Found.", 'data' => $Data]);
            } else {
                return response()->json(['status' => 401, 'message' => "Coupon Data Not Found."]);
            }
        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['status' => 401, 'message' => "Something went wrong. Please try again."]);
        }
    }

     
	public function applyCoupon(Request $request)
    {
        try {
            $user_id = $request->user()->user_id;

            if (empty($user_id)) {
                $msg = "user id is required";
                return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => $msg]);
            }

            $headers = $request->headers->all();
                
            $verify_request_base = Admin::verify_request_base($headers);

            if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
                return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
                exit();
            }
            
            $rules = [
                'coupon_code' => 'required',
                'subtotal' => 'required',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $messages = $validator->errors()->all();
                $msg = $messages[0];
                return response()->json(['status' => 401, 'message' => $msg]);
            }

            $CheckUser =  User::where('user_id', $user_id)->where('user_type',0)->first();
            if (empty($CheckUser)) {
                return response()->json(['status' => 401, 'message' => "User Not Found"]);
            }
            $coupon_code = $request->get('coupon_code');
            $subtotal = $request->get('subtotal');

            $CheckCoupon =  Coupon::where('coupon_code', $coupon_code)->first();
            $CheckUsersCoupon =  UsersCoupon::where('coupon_code', $coupon_code)->where('user_id', $user_id)->first();
            if (empty($CheckCoupon)) {
                return response()->json(['status' => 401, 'message' => "Coupon is Not Valid"]);
            }else if (!empty($CheckUsersCoupon)) {
                return response()->json(['status' => 401, 'message' => "You already used this coupon"]);
            }else{
                if($CheckCoupon['minimum_amount'] > 0 ){
                    if($subtotal >= $CheckCoupon['minimum_amount']){
                        UsersCoupon::insert(['user_id'=>$user_id,'coupon_code'=>$coupon_code]);
                        if($CheckCoupon['discount_type'] == 2){
                            $discount = rand(0,$CheckCoupon['coupon_discount']);
                            $coupon_discount = ($subtotal * ($discount/100) );
                            $Data['coupon_discount'] =  $coupon_discount;
                            $Data['discount_percent'] = $discount.'%';
                        }else{
                            $coupon_discount = $CheckCoupon['coupon_discount'];
                            $Data['coupon_discount'] =  $coupon_discount;
                            $Data['discount_percent'] = $CheckCoupon['coupon_discount'];
                        }
                        
                        $Data['subtotal'] =  $subtotal - $coupon_discount;

                        return response()->json(['status' => 200, 'message' => "Coupon Apply Successfully.", 'data' => $Data]);
                    }else{
                        return response()->json(['status' => 200, 'message' => "Coupon is Valid for Orders Above Amount ₹".$CheckCoupon['minimum_amount'].""]);
                    }
                }else{
                    UsersCoupon::insert(['user_id'=>$user_id,'coupon_code'=>$coupon_code]);
                    if($CheckCoupon['discount_type'] == 2){
                        $coupon_discount = ($subtotal * ($CheckCoupon['coupon_discount']/100) );
                        $Data['coupon_discount'] =  $coupon_discount;
                        $Data['discount_percent'] = $CheckCoupon['coupon_discount'].'%';
                    }else{
                        $coupon_discount = $CheckCoupon['coupon_discount'];
                        $Data['coupon_discount'] =  $coupon_discount;
                        $Data['discount_percent'] = $CheckCoupon['coupon_discount'];
                    }
                    
                    $Data['subtotal'] =  $subtotal - $coupon_discount;

                    return response()->json(['status' => 200, 'message' => "Coupon Apply Successfully.", 'data' => $Data]);
                } 
            }
			
        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['status' => 401, 'message' => "Something went wrong. Please try again."]);
        }
    }

	public function getPaymentSummary(Request $request)
    {
        try {
            $user_id = $request->user()->user_id;

            if (empty($user_id)) {
                $msg = "user id is required";
                return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => $msg]);
            }

            $headers = $request->headers->all();
                
            $verify_request_base = Admin::verify_request_base($headers);

            if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
                return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
                exit();
            }
            $rules = [
                'delivery_address_id' => 'required',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $messages = $validator->errors()->all();
                $msg = $messages[0];
                return response()->json(['status' => 401, 'message' => $msg]);
            }
            
            $CheckUser =  User::where('user_id', $user_id)->where('user_type',0)->first();
            if (empty($CheckUser)) {
                return response()->json(['status' => 401, 'message' => "User Not Found"]);
            }
            $delivery_address_id = $request->get('delivery_address_id');
			$user_address =  UserDeliveryAddress::select('delivery_address_id','user_id','first_name','last_name','mobile_number','home_no','street','street','landmark','city','area','society','pincode','latitude','longitude')->where('delivery_address_id', $delivery_address_id)->where('user_id', $user_id)->get();  
			$Data['user_address'] = $user_address;

			$itemData = Cart::select('tbl_cart.quantity','P.product_id','P.coupon_code','P.name','P.product_image', 'tbl_cart.price_unit_id', 'PU.price', 'PU.unit', 'U.unit_name')
			->leftjoin('tbl_products as P', 'tbl_cart.product_id', 'P.product_id')
			->leftjoin('tbl_product_price_unit as PU', 'tbl_cart.price_unit_id', 'PU.price_unit_id')
			->leftjoin('tbl_unit as U', 'PU.unit_id', 'U.id')
			->where('tbl_cart.user_id',$user_id)
			->get();
			$total = 0;
			if (count($itemData) > 0) {
                $itemlist = [];
                foreach($itemData as $k => $value){
                    $list['product_id'] = $value['product_id'];
                    $list['name'] = $value['name'];
                    $list['price_unit_id'] = $value['price_unit_id'];
                    $list['quantity'] = $value['quantity'];
                    $list['price'] = $value['price'];
                    $list['unit'] = $value['unit'].' '.$value['unit_name'];   
                    $list['total'] =  (int)$value['price']*(int)$value['quantity'];
                    // if(!empty($value['coupon_code'])){
                    //     $CouponData = Coupon::where('coupon_code', $coupon_code)->first();
                    //     $list['coupon_discount'] =  $CouponData['amount'];
                    //     $total += $value['price'];
                    //     $total = $total-$list['coupon_discount'];
                    // }else{
                    //     $total += $value['price'];
                    // }
                    $total += ((int)$value['price']*(int)$value['quantity']);
                    $itemlist[] = $list; 
                    
                    $data['user_id'] = $user_id;
                    $data['product_id'] = $value['product_id'];
                    $data['price_unit_id'] = $value['price_unit_id'];
                    $data['quantity'] = $value['quantity'];
        
                    $ProductData =  PaymentSummary::insert($data);
					         
                }
            }else{
				$itemlist = [];
            }
            
            $settigns = Settings::first();
			$Data['items'] = $itemlist;
            $Data['subtotal'] = $total;
            $Data['shipping_charge'] = $settigns['shipping_charge'] ? (int)$settigns['shipping_charge']: 0;
			
            if (!empty($Data)) {  
                Cart::where('user_id',$user_id)->delete();           
                return response()->json(['status' => 200, 'message' => "Payment Summary Data Found.", 'data' => $Data]);
            } else {
                return response()->json(['status' => 401, 'message' => "Payment Summary Data Not Found."]);
            }
        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['status' => 401, 'message' => "Something went wrong. Please try again."]);
        }
    }


    public function getShippingCharge(Request $request)
    {
        try {
            $user_id = $request->user()->user_id;

            if (empty($user_id)) {
                $msg = "user id is required";
                return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => $msg]);
            }

            $headers = $request->headers->all();
                
            $verify_request_base = Admin::verify_request_base($headers);

            if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
                return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
                exit();
            }

            $CheckUser =  User::where('user_id', $user_id)->where('user_type',0)->first();
            if (empty($CheckUser)) {
                return response()->json(['status' => 401, 'message' => "User Not Found"]);
            }

            $settigns = Settings::first();
            $Data['shipping_charge'] = $settigns['shipping_charge'] ? (int)$settigns['shipping_charge']: 0;
			
            if (!empty($Data)) {  
                return response()->json(['status' => 200, 'message' => "Shipping Charge Data Found.", 'data' => $Data]);
            } else {
                return response()->json(['status' => 401, 'message' => "Shipping Charge Data Not Found."]);
            }
        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['status' => 401, 'message' => "Something went wrong. Please try again."]);
        }
    }
    // 1 for On Hold/2 for Processing / 3 for deliver/4 for canceeled/5 for refunded/6 for completed

    public function placeOrder(Request $request)
    {
        try {
            $user_id = $request->user()->user_id;

            if (empty($user_id)) {
                $msg = "user id is required";
                return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => $msg]);
            }

            $headers = $request->headers->all();
                
            $verify_request_base = Admin::verify_request_base($headers);

            if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
                return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
                exit();
            }
            
            $rules = [
                'total_amount' => 'required',
                'payment_type' => 'required',
                'product_id' => 'required',
                'price_unit_id' => 'required',
                'quantity' => 'required',
                'address' => 'required',
                'latitude' => 'required',
                'longitude' => 'required',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $messages = $validator->errors()->all();
                $msg = $messages[0];
                return response()->json(['status' => 401, 'message' => $msg]);
            }

            $CheckUser =  User::where('user_id', $user_id)->where('user_type',0)->first();
            if (empty($CheckUser)) {
                return response()->json(['status' => 401, 'message' => "User Not Found"]);
            }
            $total_amount = $request->get('total_amount');
            $coupon_discount = $request->get('coupon_discount');
            $shipping_charge = $request->get('shipping_charge');
            $payment_type = $request->get('payment_type');
            $delivery_address_id = $request->get('delivery_address_id');
            $address = $request->get('address');
            $latitude = $request->get('latitude');
            $longitude = $request->get('longitude');
            // if($payment_type == 2){
                 
            //     $rules = [
            //         'transaction_id' => 'required',
            //         'card_type' => 'required',
            //         'card_number' => 'required',
            //         'card_exp_date' => 'required',
            //         'card_holder_name' => 'required',
            //         'card_holder_email' => 'required',
            //     ];

            //     $validator = Validator::make($request->all(), $rules);

            //     if ($validator->fails()) {
            //         $messages = $validator->errors()->all();
            //         $msg = $messages[0];
            //         return response()->json(['status' => 401, 'message' => $msg]);
            //     } 
            //     $transaction_id = $request->get('transaction_id');
            //     $card_type = $request->get('card_type');
            //     $card_number = $request->get('card_number');
            //     $card_exp_date = $request->get('card_exp_date');
            //     $card_holder_name = $request->get('card_holder_name');
            //     $card_holder_email = $request->get('card_holder_email');
            // }

            // if (count($getCart) <= 0) {
            //     return response()->json(['status' => 401, 'message' => "No Order Item Found"]);
            // }else{
                
                $latestOrder = Order::orderBy('created_at','DESC')->first();
                $order_id = Order::get_random_string();
                $data['order_id'] = $order_id;
                $data['user_id'] = $user_id;
                $data['total_amount'] = $total_amount;
                $data['coupon_discount'] = $coupon_discount ? $coupon_discount : 0;
                $data['shipping_charge'] = $shipping_charge ? $shipping_charge : 0;
                $data['payment_type'] = $payment_type;
                $data['delivery_address_id'] = $delivery_address_id;
                $data['address'] = $address;
                $data['latitude'] = $latitude;
                $data['longitude'] = $longitude;
                $data['ordered_at'] = date('Y-m-d h:i:s');
                $data['status'] = 1;
                // if($payment_type == 2){
                //     $data['transaction_id'] = $transaction_id;
                //     $data['card_type'] = $card_type;
                //     $data['card_number'] = $card_number;
                //     $data['card_exp_date'] = $card_exp_date;
                //     $data['card_holder_name'] = $card_holder_name;
                //     $data['card_holder_email'] = $card_holder_email;
                // }
                Order::insert($data);

                $product_id = $request->get('product_id');
                $price_unit_id = $request->get('price_unit_id');
                $quantity = $request->get('quantity');

                foreach($product_id as $key => $value){
                    $data1['item_id'] = OrdersItem::get_random_string();
                    $data1['order_id'] = $order_id;
                    $data1['user_id'] = $user_id;
                    $data1['product_id'] = $value;
                    $data1['price_unit_id'] = $price_unit_id[$key];
                    $data1['quantity'] = $quantity[$key] ? $quantity[$key] : 1;
                    OrdersItem::insert($data1);
                }
            
                return response()->json(['status' => 200, 'message' => "Place Order Successfully."]);
            // }
        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['status' => 401, 'message' => "Something went wrong. Please try again."]);
        }
    }

    public function deletePaymentSummary(Request $request)
    {
        try {
            $user_id = $request->user()->user_id;

            if (empty($user_id)) {
                $msg = "user id is required";
                return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => $msg]);
            }

            $headers = $request->headers->all();
                
            $verify_request_base = Admin::verify_request_base($headers);

            if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
                return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
                exit();
            }           

            $CheckUser =  User::where('user_id', $user_id)->where('user_type',0)->first();
            if (empty($CheckUser)) {
                return response()->json(['status' => 401, 'message' => "User Not Found"]);
            }

            $result = PaymentSummary::where('user_id', $user_id)->get();
            if(count($result) > 0){
                PaymentSummary::where('user_id', $user_id)->delete();
                return response()->json(['status' => 200, 'message' => "Payment Summary Delete Successful."]);
            }else{
                return response()->json(['status' => 200, 'message' => "Payment Summary Data Not Found."]);
            }
            
            
        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['status' => 401, 'message' => "Something went wrong. Please try again."]);
        }
    }

    public function getMyOrderList(Request $request)
    {
        try {
            $user_id = $request->user()->user_id;

            if (empty($user_id)) {
                $msg = "user id is required";
                return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => $msg]);
            }

            $headers = $request->headers->all();
                
            $verify_request_base = Admin::verify_request_base($headers);

            if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
                return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
                exit();
            }

            $rules = [
                'start' => 'required',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $messages = $validator->errors()->all();
                $msg = $messages[0];
                return response()->json(['status' => 401, 'message' => $msg]);
            }
            
            $limit = $request->get('limit') ? $request->get('limit') : 20;
            $start = $request->get('start') ? $request->get('start') : 0;

            $CheckUser =  User::where('user_id', $user_id)->where('user_type',0)->first();
            if (empty($CheckUser)) {
                return response()->json(['status' => 401, 'message' => "User Not Found"]);
            }

            $itemData = Order::select('tbl_orders.*')
            ->where('tbl_orders.user_id',$user_id)
            ->orderBy('tbl_orders.ordered_at','DESC')
            ->offset($start)
            ->limit($limit)
			->get();
			$total = 0;
			if (count($itemData) > 0) {
                $itemlist = [];
                foreach($itemData as $k => $value){
                    $total_item = OrdersItem::where('order_id',$value['order_id'])->count();
                    $list['order_id'] = $value['order_id'];
                    $list['total_amount'] = $value['total_amount'];
                    $list['total_item'] = $total_item;
                    $list['ordered_at'] = $value['ordered_at'];
                    if($value['status'] == 1){
                        $list['status'] = 'Processing';
                    }else  if($value['status'] == 2){
                        $list['status'] = 'Confirmed ';
                    }else  if($value['status'] == 3){
                        $list['status'] = 'Completed';
                    }else  if($value['status'] == 4){
                        $list['status'] = 'On Hold';
                    }else  if($value['status'] == 5){
                        $list['status'] = 'Cancelled';
                    }
					$itemlist[] = $list; 
					         
                }
            }else{
				$itemlist = [];
			}
			$Data = $itemlist;			
            if (!empty($Data)) {             
                return response()->json(['status' => 200, 'message' => "Order Data Found.", 'data' => $Data]);
            } else {
                return response()->json(['status' => 401, 'message' => "Order Data Not Found."]);
            }
        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['status' => 401, 'message' => "Something went wrong. Please try again."]);
        }
    }

    public function getOrderDetailsById(Request $request)
    {
        try {
            $user_id = $request->user()->user_id;

            if (empty($user_id)) {
                $msg = "user id is required";
                return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => $msg]);
            }

            $headers = $request->headers->all();
                
            $verify_request_base = Admin::verify_request_base($headers);

            if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
                return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
                exit();
            }            
            
            $rules = [
                'order_id' => 'required',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $messages = $validator->errors()->all();
                $msg = $messages[0];
                return response()->json(['status' => 401, 'message' => $msg]);
            }
            $order_id = $request->get('order_id');
            $CheckUser =  User::where('user_id', $user_id)->where('user_type',0)->first();
            if (empty($CheckUser)) {
                return response()->json(['status' => 401, 'message' => "User Not Found"]);
            }

            $itemData = OrdersItem::select('tbl_orders_item.*','P.product_id','P.coupon_code','P.name','P.product_image', 'PU.price', 'PU.unit', 'U.unit_name')
            ->leftjoin('tbl_products as P', 'tbl_orders_item.product_id', 'P.product_id')
            ->leftjoin('tbl_product_price_unit as PU', 'tbl_orders_item.price_unit_id', 'PU.price_unit_id')
            ->leftjoin('tbl_unit as U', 'PU.unit_id', 'U.id')
            ->where('tbl_orders_item.order_id',$order_id)
            ->get();
            $total_amount = 0;
            if (count($itemData) > 0) {
                $itemlist = [];
                foreach($itemData as $k => $value){
                    $list3['order_id'] = $value['order_id'];
                    $list3['item_id'] = $value['item_id'];
                    $list3['product_id'] = $value['product_id'];
                    $list3['name'] = $value['name'];
                    $product_image = explode(',',$value['product_image']);
                    $list3['product_image'] = $product_image; 
                    $list3['price_unit_id'] = $value['price_unit_id'];
                    $list3['quantity'] = $value['quantity'];
                    $list3['price'] = $value['price'];
                    $list3['unit'] = $value['unit'].' '.$value['unit_name']; 
                    $itemlist[] = $list3;   
                    $total_amount+=($value['price']*$value['quantity']);
                }
            }else{
                $itemlist = [];
            }
            
            
            $orderData = Order::select('tbl_orders.*', 'D.*', DB::raw("count(I.order_id) as total_item"))
            ->leftjoin('tbl_orders_item as I', 'tbl_orders.order_id', 'I.order_id')
            ->leftjoin('tbl_user_delivery_address as D', 'tbl_orders.delivery_address_id', 'D.delivery_address_id')
            ->where('tbl_orders.order_id',$order_id)
			->get();
            $total = 0;
            $Reviewdata = OrderReview::select('review','rating')->where('order_id',$order_id)->first();

			if (count($orderData) > 0) {
                $orderlist = [];
                $deliverylist = [];
                foreach($orderData as $k => $value){
                    $list1['order_id'] = $value['order_id'];
                    $list1['payment_type'] = $value['payment_type'] == 1 ? 'Cash on Delivery' : 'Card Payment';
                    $list1['total_item'] = $value['total_item'];
                    $list1['sub_total'] = $total_amount;
                    $list1['shipping_charge'] = $value['shipping_charge'];
                    $list1['coupon_discount'] = $value['coupon_discount'];
                    $list1['total_amount'] = ($total_amount+$value['shipping_charge']) - $value['coupon_discount'];
                    if($value['status'] == 1){
                        $list['status'] = 'Processing';
                    }else  if($value['status'] == 2){
                        $list['status'] = 'Confirmed ';
                    }else  if($value['status'] == 3){
                        $list['status'] = 'Completed';
                    }else  if($value['status'] == 4){
                        $list['status'] = 'On Hold';
                    }else  if($value['status'] == 5){
                        $list['status'] = 'Cancelled';
                    }
                    $list1['ordered_at'] = $value['ordered_at'];

                    $list1['review'] = $Reviewdata;

                    $list['delivery_address_id'] = $value['delivery_address_id'];
                    $list['address'] = $value['address'];
                    $list['first_name'] = $value['first_name'];
                    $list['last_name'] = $value['last_name'];
                    $list['mobile_number'] = $value['mobile_number'];
                    $list['home_no'] = $value['home_no'];
                    $list['street'] = $value['street'];
                    $list['landmark'] = $value['landmark'];
                    $list['city'] = $value['city'];
                    $list['area'] = $value['area'];
                    $list['society'] = $value['society'];
                    $list['pincode'] = $value['pincode'];
					$deliverylist[] = $list; 
                    $orderlist[] = $list1;     
                }
            }else{
                $orderlist = [];
				$deliverylist = [];
            }

            $Data['delivery_details'] = $deliverylist;	
            $Data['order_details'] = $orderlist;
            $Data['item_details'] = $itemlist;			
            if (!empty($Data)) {             
                return response()->json(['status' => 200, 'message' => "Order Summary Data Found.", 'data' => $Data]);
            } else {
                return response()->json(['status' => 401, 'message' => "Order Summary Data Not Found."]);
            }
        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['status' => 401, 'message' => "Something went wrong. Please try again."]);
        }
    }

    public function cancelledOrder(Request $request)
    {
        try {
            $user_id = $request->user()->user_id;

            if (empty($user_id)) {
                $msg = "user id is required";
                return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => $msg]);
            }
            
            $headers = $request->headers->all();
                
            $verify_request_base = Admin::verify_request_base($headers);

            if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
                return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
                exit();
            }

            $CheckUSer =  User::where('user_id', $user_id)->where('user_type', 0)->first();
            if (empty($CheckUSer)) {
                return response()->json(['status' => 401, 'message' => "User Not Found"]);
            }

            $rules = [
                'order_id' => 'required',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $messages = $validator->errors()->all();
                $msg = $messages[0];
                return response()->json(['status' => 401, 'message' => $msg]);
            }
            
            $order_id = $request->get('order_id');
            
            $orderData = Order::where('order_id',$order_id)->first();
            if($orderData['status'] != 3){
                $result = Order::where('order_id',$order_id)->update(['status'=>5,'cancelled_at'=>date('Y-m-d h:i:s')]);

                $userData = User::where('user_id',$user_id)->first();
                
                if($userData->device_token){
                    $message = 'Hey '.$userData->first_name.' '.$userData->last_name.', Your Order('.$order_id.') is Cancelled. Please check your order status for more details';
                 
                    $is_send = Common::send_push($userData->device_token,'Order Cancelled',$message,$userData->device_type);
                    if( $is_send ){
                        $notificationdata = array(
                            'user_id'=>$user_id,
                            'item_id'=>$order_id,
                            'notification_type'=>4,
                            'title'=>'Order Cancelled',
                            'message'=>$message,
                        );
        
                        Notification::insert($notificationdata);
                    }
                }
            
                return response()->json(['status' => 200, 'message' => "Order Cancelled Successfully."]);
            }else{
                return response()->json(['status' => 200, 'message' => "You can not cancelled order."]);
            }
           
        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['status' => 401, 'message' => "Something went wrong. Please try again."]);
        }
    }

    public function raiseComplaint(Request $request)
    {
        try {
            $user_id = $request->user()->user_id;

            if (empty($user_id)) {
                $msg = "user id is required";
                return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => $msg]);
            }

            $headers = $request->headers->all();
                
            $verify_request_base = Admin::verify_request_base($headers);

            if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
                return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
                exit();
            }            
            
            $rules = [
                // 'order_id' => 'required',
                'title' => 'required',
                'description' => 'required',
                'mobile_no' => 'required|min:10|max:15|regex:/^[0-9\+\s]+$/',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $messages = $validator->errors()->all();
                $msg = $messages[0];
                return response()->json(['status' => 401, 'message' => $msg]);
            }
            $order_id = $request->get('order_id');
            $title = $request->get('title');
            $description = $request->get('description');
            $mobile_no = $request->get('mobile_no');

            $CheckUser =  User::where('user_id', $user_id)->where('user_type',0)->first();
            if (empty($CheckUser)) {
                return response()->json(['status' => 401, 'message' => "User Not Found"]);
            }

            $complaint_id = Complaint::get_random_string();
            $data['complaint_id'] = $complaint_id;
            $data['order_id'] = $order_id;
            $data['user_id'] = $user_id;
            $data['title'] = $title;
            $data['description'] = $description;
            $data['mobile_no'] = $mobile_no;
            $data['status'] = 1;
            Complaint::insert($data);
        
            return response()->json(['status' => 200, 'message' => "Compliant Sent Successfully."]);
        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['status' => 401, 'message' => "Something went wrong. Please try again."]);
        }
    }

    
    public function getAllComplaint(Request $request)
    {
        try {
            $user_id = $request->user()->user_id;

            if (empty($user_id)) {
                $msg = "user id is required";
                return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => $msg]);
            }

            $headers = $request->headers->all();
                
            $verify_request_base = Admin::verify_request_base($headers);

            if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
                return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
                exit();
            }            

            $rules = [
                'start' => 'required',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $messages = $validator->errors()->all();
                $msg = $messages[0];
                return response()->json(['status' => 401, 'message' => $msg]);
            }
            
            $limit = $request->get('limit') ? $request->get('limit') : 20;
            $start = $request->get('start') ? $request->get('start') : 0;

            $CheckUser =  User::where('user_id', $user_id)->where('user_type',0)->first();
            if (empty($CheckUser)) {
                return response()->json(['status' => 401, 'message' => "User Not Found"]);
            }

            $Complaintdata = Complaint::where('user_id',$user_id)->orderBy('created_at','DESC')->offset($start)
            ->limit($limit)->get();

            $data = [];
            foreach($Complaintdata as $key => $value){
                $list['complaint_id'] = $value['complaint_id'];
                $list['order_id'] = $value['order_id'];
                $list['user_id'] = $value['user_id'];
                $list['description'] = $value['description'];
                // $list['mobile_no'] = $value['mobile_no'];
                $list['status'] = $value['status'] == 1 ? 'Open' : 'Close';
                $list['created_at'] = date('d-m-Y' ,strtotime($value['created_at']));
                $data[] = $list;
            }
            if(count($data) > 0){
                return response()->json(['status' => 200, 'message' => "Compliant Data Found.", 'data' => $data]);
            }else{
                return response()->json(['status' => 400, 'message' => "Compliant Data Not Found.", 'data' => []]);
            }
            
        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['status' => 401, 'message' => "Something went wrong. Please try again."]);
        }
    }

    public function productReviewRating(Request $request)
    {
        try {
            $user_id = $request->user()->user_id;

            if (empty($user_id)) {
                $msg = "user id is required";
                return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => $msg]);
            }

            $headers = $request->headers->all();
                
            $verify_request_base = Admin::verify_request_base($headers);

            if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
                return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
                exit();
            }            
            
            $rules = [
                'product_id' => 'required',
                'review' => 'required',
                'rating' => 'required',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $messages = $validator->errors()->all();
                $msg = $messages[0];
                return response()->json(['status' => 401, 'message' => $msg]);
            }
            $product_id = $request->get('product_id');
            $review = $request->get('review');
            $rating = $request->get('rating');

            $CheckUser =  User::where('user_id', $user_id)->where('user_type',0)->first();
            if (empty($CheckUser)) {
                return response()->json(['status' => 401, 'message' => "User Not Found"]);
            }
            $CheckProduct =  Product::where('product_id', $product_id)->first();
            if (empty($CheckProduct)) {
                return response()->json(['status' => 401, 'message' => "Product Not Found"]);
            }
            $CheckReview = ProductReview::where('user_id', $user_id)->where('product_id', $product_id)->get();
            if(count($CheckReview) <= 0){
                $data['product_id'] = $product_id;
                $data['user_id'] = $user_id;
                $data['review'] = $review;
                $data['rating'] = $rating;
                ProductReview::insert($data);
                return response()->json(['status' => 200, 'message' => "Product Review Add Successfully."]);
            }else{
                return response()->json(['status' => 400, 'message' => "You already added review for this Product."]);
            }               
            
               
        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['status' => 401, 'message' => "Something went wrong. Please try again."]);
        }
    }


    public function orderReviewRating(Request $request)
    {
        try {
            $user_id = $request->user()->user_id;

            if (empty($user_id)) {
                $msg = "user id is required";
                return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => $msg]);
            }

            $headers = $request->headers->all();
                
            $verify_request_base = Admin::verify_request_base($headers);

            if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
                return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
                exit();
            }            
            
            $rules = [
                'order_id' => 'required',
                'review' => 'required',
                'rating' => 'required',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $messages = $validator->errors()->all();
                $msg = $messages[0];
                return response()->json(['status' => 401, 'message' => $msg]);
            }
            $order_id = $request->get('order_id');
            $review = $request->get('review');
            $rating = $request->get('rating');

            $CheckUser =  User::where('user_id', $user_id)->where('user_type',0)->first();
            if (empty($CheckUser)) {
                return response()->json(['status' => 401, 'message' => "User Not Found"]);
            }
            $CheckOrder =  Order::where('order_id', $order_id)->first();
            if (empty($CheckOrder)) {
                return response()->json(['status' => 401, 'message' => "Order Not Found"]);
            }
            $CheckReview = OrderReview::where('user_id', $user_id)->where('order_id', $order_id)->get();
            if(count($CheckReview) <= 0){
                $data['order_id'] = $order_id;
                $data['user_id'] = $user_id;
                $data['review'] = $review;
                $data['rating'] = $rating;
                OrderReview::insert($data);
                return response()->json(['status' => 200, 'message' => "Order Review Add Successfully."]);
            }else{
                return response()->json(['status' => 400, 'message' => "You already added review for this Order."]);
            }               
            
               
        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['status' => 401, 'message' => "Something went wrong. Please try again."]);
        }
    }

    public function getAllOrderRating(Request $request)
    {
        try {
            $user_id = $request->user()->user_id;

            if (empty($user_id)) {
                $msg = "user id is required";
                return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => $msg]);
            }

            $headers = $request->headers->all();
                
            $verify_request_base = Admin::verify_request_base($headers);

            if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
                return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
                exit();
            }            

            $rules = [
                'start' => 'required',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $messages = $validator->errors()->all();
                $msg = $messages[0];
                return response()->json(['status' => 401, 'message' => $msg]);
            }
            
            $limit = $request->get('limit') ? $request->get('limit') : 20;
            $start = $request->get('start') ? $request->get('start') : 0;

            $CheckUser =  User::where('user_id', $user_id)->where('user_type',0)->first();
            if (empty($CheckUser)) {
                return response()->json(['status' => 401, 'message' => "User Not Found"]);
            }

            $Reviewdata = OrderReview::orderBy('rating','DESC')->offset($start)
            ->limit($limit)->get();

            $data = [];
            foreach($Reviewdata as $key => $value){
                $list['id'] = $value['id'];
                $list['order_id'] = $value['order_id'];
                $list['user_id'] = $value['user_id'];
                $list['review'] = $value['review'];
                $list['rating'] = $value['rating'];
                $data[] = $list;
            }
            if(count($data) > 0){
                return response()->json(['status' => 200, 'message' => "Rating Data Found.", 'data' => $data]);
            }else{
                return response()->json(['status' => 400, 'message' => "Rating Data Not Found.", 'data' => []]);
            }
            
        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['status' => 401, 'message' => "Something went wrong. Please try again."]);
        }
    }

    public function stripePaymentIntent(Request $request)
    {
        try {
            $user_id = $request->user()->user_id;

            if (empty($user_id)) {
                $msg = "user id is required";
                return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => $msg]);
            }

            $headers = $request->headers->all();
                
            $verify_request_base = Admin::verify_request_base($headers);

            if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
                return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
                exit();
            }            

            $rules = [
                'amount' => 'required',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $messages = $validator->errors()->all();
                $msg = $messages[0];
                return response()->json(['status' => 401, 'message' => $msg]);
            }
            
            $amount = $request->get('amount');

            $CheckUser =  User::where('user_id', $user_id)->where('user_type',0)->first();
            if (empty($CheckUser)) {
                return response()->json(['status' => 401, 'message' => "User Not Found"]);
            }

            \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

            $intent = \Stripe\PaymentIntent::create([
            'amount' => $amount,
            'currency' => 'inr',
            ]);
            $client_secret = $intent->client_secret;

            if($client_secret){
                return response()->json(['status' => 200, 'message' => "Payment Intent Successful.", 'data' => $client_secret]);
            }else{
                return response()->json(['status' => 400, 'message' => "Error While Payment.", 'data' => []]);
            }
            
        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['status' => 401, 'message' => "Something went wrong. Please try again."]);
        }
    }
}


 