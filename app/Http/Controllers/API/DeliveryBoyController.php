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
use Storage;
use File;
use Carbon\Carbon;
use App\Admin;
use App\User;
use App\Product;
use App\Unit;
use App\ProductPriceUnit;
use App\Order;
use App\OrdersItem;
use App\UserDeliveryAddress;
use App\DeliveryBoy;
use App\Notification;
use App\Common;
use Log;
use Laravel\Passport\Token;

class DeliveryBoyController extends Controller
{
  
    public function userLogin(Request $request)
    {
        try {
            $headers = $request->headers->all();
            
            $verify_request_base = Admin::verify_request_base($headers);

            if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
                return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
                exit();
            }
    
            $rules = [
                'username' => 'required',
                'password' => 'required',
                'device_type' => 'required',
                'device_token' => 'required',
            ];
    
            $validator = Validator::make($request->all(), $rules, []);
    
            if ($validator->fails()) {
                $messages = $validator->errors()->all();
                $msg = $messages[0];
                return response()->json(['status' => 401, 'message' => $msg]);
            }
            $username = $request->get('username');
            $password = $request->get('password');
            $device_token = $request->get('device_token');
            $device_type = $request->get('device_type');

            $CheckUSer =  User::where('username', $username)->where('user_type', 1)->first();
           
            if (!empty($CheckUSer)) {
                if ($CheckUSer->password == md5($password) || Hash::check($password, $CheckUSer->password)) {
                    $data['device_token'] = $device_token;
                    $data['device_type'] = $device_type;
                    $result =  User::where('user_id', $CheckUSer->user_id)->update($data);

                    $User =  User::select('id','user_id','fullname','username','mobile_no','profile_image','device_type','device_token')->where('user_id', $CheckUSer->user_id)->where('user_type', 1)->first();

                    $User['token'] = 'Bearer ' . $CheckUSer->createToken(env('APP_NAME'))->accessToken;

                    return response()->json(['status' => 200, 'message' => "User login successfully.", 'data' => $User]);

                } else {
                    return response()->json(['status' => 401, 'message' => "Password is Wrong"]);
                }
            } else {
                return response()->json(['status' => 401, 'message' => "User Does not Exist"]);
            }
        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['status' => 401, 'message' => "Something went wrong. Please try again."]);
        }
    }

    public function Logout()
    {
        try {
            if (Auth::check()) {
                $user = Auth::user();
                $accessToken = Auth::user()->token();
                if (isset($user->id)) {
                    DB::table('oauth_access_tokens')->where('id', $accessToken->id)->delete();
                }
                return response()->json(['success_code' => 200, 'response_code' => 1, 'response_message' => "User logout successfully."]);
            } else {
                return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => "User Id is required"]);
            }
        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => "Something went wrong. Please try again."]);
        }
    }

    public function getProfile(Request $request)
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
            
            $CheckUSer =  User::where('user_id', $user_id)->where('user_type', 1)->first();
            if (empty($CheckUSer)) {
                return response()->json(['status' => 401, 'message' => "User Not Found"]);
            }
            
            $User =  User::select('id','user_id','fullname','username','mobile_no','profile_image','status as is_avialable')->where('user_id', $user_id)->where('user_type', 1)->first();
            $orderData = Order::where('delivery_boy_user_id', $user_id)->count();
            $User['total_deliveries'] = $orderData;
            $payData = DeliveryBoy::where('user_id', $user_id)->where('status', 1)->sum('amount_to_pay');
            $User['cash_to_pay'] = $payData ? $payData : 0;
            if (!empty($User)) {

                return response()->json(['status' => 200, 'message' => "User Profile Found successfully.", 'data' => $User]);
            } else {
                return response()->json(['status' => 401, 'message' => "Not Found Data"]);
            }
        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['status' => 401, 'message' => "Something went wrong. Please try again."]);
        }
    }

    public function changeAvialableStatus(Request $request)
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
            
            $CheckUSer =  User::where('user_id', $user_id)->where('user_type', 1)->first();
            if (empty($CheckUSer)) {
                return response()->json(['status' => 401, 'message' => "User Not Found"]);
            }

            $rules = [
                'is_avialable' => 'required',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $messages = $validator->errors()->all();
                $msg = $messages[0];
                return response()->json(['status' => 401, 'message' => $msg]);
            }
            
            $data['status'] = $request->get('is_avialable');

            $result =  User::where('user_id', $user_id)->update($data);

            if (!empty($result)) {

                return response()->json(['status' => 200, 'message' => "User available status change successfully."]);
            } else {
                return response()->json(['status' => 401, 'message' => "Error While User available status change"]);
            }
        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['status' => 401, 'message' => "Something went wrong. Please try again."]);
        }
    }

    public function updateProfile(Request $request)
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
            
            $CheckUSer =  User::where('user_id', $user_id)->where('user_type', 1)->first();
            if (empty($CheckUSer)) {
                return response()->json(['status' => 401, 'message' => "User Not Found"]);
            }
            
            $s3 = Storage::disk('s3');
            if ($request->hasfile('profile_image')) {
                $file = $request->file('profile_image');
                $filename = $file->getClientOriginalName();
                $filename = str_replace(' ','_',$filename);
                $imageFileName='user_' . rand(111,999) . '.' . $file->getClientOriginalExtension();
                $destinationPath = '/uploads/';
                $filePath = $destinationPath . $imageFileName;
                if ($s3->put($filePath, file_get_contents($file)) ){
                    $profile_image = $imageFileName;
                    $filename = public_path() . 'uploads/' . $imageFileName;
                    if (File::exists($filename)) {
                        unlink($filename);
                    }
                }else{
                    $profile_image = "";
                }
                $data['profile_image'] = $profile_image;
            }
        
            $data['user_id'] = $user_id;
            $data['fullname'] = $request->get('fullname');

            $result =  User::where('user_id', $user_id)->update($data);

            if (!empty($result)) {

                $User =  User::select('id','user_id','fullname','username','mobile_no','profile_image')->where('user_id', $user_id)->where('user_type', 1)->first();

                return response()->json(['status' => 200, 'message' => "User Profile Update successfully.", 'data' => $User]);
            } else {
                return response()->json(['status' => 401, 'message' => "Error While User Profile Update"]);
            }
        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['status' => 401, 'message' => "Something went wrong. Please try again."]);
        }
    }

    public function getPendingOrders(Request $request)
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
                'latitude' => 'required',
                'longitude' => 'required',
                'start' => 'required',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $messages = $validator->errors()->all();
                $msg = $messages[0];
                return response()->json(['status' => 401, 'message' => $msg]);
            }
            $latitude = $request->get('latitude');
            $longitude = $request->get('longitude');
            $limit = $request->get('limit') ? $request->get('limit') : 20;
            $start = $request->get('start') ? $request->get('start') : 0;
            
            $CheckUSer =  User::where('user_id', $user_id)->where('user_type', 1)->first();
            if (empty($CheckUSer)) {
                return response()->json(['status' => 401, 'message' => "User Not Found"]);
            }
            
            $itemData = Order::select('tbl_orders.*', 'U.profile_image', 'D.first_name', 'D.last_name','D.mobile_number','D.alt_mobile_number','D.home_no','D.street','D.landmark','D.city','D.area','D.society','D.pincode')
            ->leftjoin('tbl_user_delivery_address as D', 'tbl_orders.delivery_address_id', 'D.delivery_address_id')
            ->leftjoin('tbl_users as U', 'tbl_orders.user_id', 'U.user_id')
            ->where('tbl_orders.delivery_boy_user_id',$user_id)
            ->where('tbl_orders.status',2)
            ->orWhere('tbl_orders.status',6)
            ->orderBy('tbl_orders.ordered_at','DESC')
            ->offset($start)
            ->limit($limit)
            ->get();
            
            if (count($itemData) > 0) {
                $itemlist = [];
                $distance = 0;
                foreach($itemData as $k => $value){
                    $distance = 0;
                    if($value['latitude'] &&  $value['longitude']){
                        $distance = User::distance($latitude, $longitude, $value['latitude'], $value['longitude'], "K");
                    }
                    $list['order_id'] = $value['order_id'];
                    $list['assigned_at'] = $value['assigned_at'] ? \Carbon\Carbon::parse($value['assigned_at'])->diffForHumans() : "";
                    $list['user_id'] = $value['user_id'];
                    $list['first_name'] = $value['first_name'];
                    $list['last_name'] = $value['last_name'];
                    $list['profile_image'] = $value['profile_image'];
                    $list['mobile_number'] = $value['mobile_number'];
                    $list['home_no'] = $value['home_no'];
                    $list['street'] = $value['street'];
                    $list['landmark'] = $value['landmark'];
                    $list['city'] = $value['city'];
                    $list['area'] = $value['area'];
                    $list['society'] = $value['society'];
                    $list['pincode'] = $value['pincode'];
                    $list['address'] = $value['address'];
                    $list['distance'] = number_format($distance,2);
                    // $list['ordered_at'] = $value['ordered_at'];
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

    public function getCompletedOrders(Request $request)
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
            $latitude = $request->get('latitude');
            $longitude = $request->get('longitude');
            $limit = $request->get('limit') ? $request->get('limit') : 20;
            $start = $request->get('start') ? $request->get('start') : 0;

            $CheckUSer =  User::where('user_id', $user_id)->where('user_type', 1)->first();
            if (empty($CheckUSer)) {
                return response()->json(['status' => 401, 'message' => "User Not Found"]);
            }
            
            $itemData = Order::select('tbl_orders.*', 'U.profile_image', 'D.first_name', 'D.last_name','D.mobile_number','D.alt_mobile_number','D.home_no','D.street','D.landmark','D.city','D.area','D.society','D.pincode')
            ->leftjoin('tbl_user_delivery_address as D', 'tbl_orders.delivery_address_id', 'D.delivery_address_id')
            ->leftjoin('tbl_users as U', 'tbl_orders.user_id', 'U.user_id')
            ->where('tbl_orders.delivery_boy_user_id',$user_id)
            ->where('tbl_orders.status',3)
            ->orderBy('tbl_orders.completed_at','DESC')
            ->offset($start)
            ->limit($limit)->get();
            // print_r($itemData);
            // die;
            if (count($itemData) > 0) {
                $itemlist = [];
                $distance = 0;
                foreach($itemData as $k => $value){
                    $distance = 0;
                    if($value['latitude'] &&  $value['longitude']){
                        $distance = User::distance($latitude, $longitude, $value['latitude'], $value['longitude'], "K");
                    }
                    $list['order_id'] = $value['order_id'];
                    $list['user_id'] = $value['user_id'];
                    $list['first_name'] = $value['first_name'];
                    $list['last_name'] = $value['last_name'];
                    $list['profile_image'] = $value['profile_image'];
                    $list['mobile_number'] = $value['mobile_number'];
                    $list['home_no'] = $value['home_no'];
                    $list['street'] = $value['street'];
                    $list['landmark'] = $value['landmark'];
                    $list['city'] = $value['city'];
                    $list['area'] = $value['area'];
                    $list['society'] = $value['society'];
                    $list['pincode'] = $value['pincode'];
                    $list['address'] = $value['address'];
                    $list['distance'] = number_format($distance,2);
                    $list['completed_at'] = $value['completed_at'] ? date('Y/m/d',strtotime($value['completed_at'])) : "";
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

    public function getOrderDetails(Request $request)
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
                'user_id' => 'required',
                'order_id' => 'required',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $messages = $validator->errors()->all();
                $msg = $messages[0];
                return response()->json(['status' => 401, 'message' => $msg]);
            }
            $order_user_id = $request->get('user_id');
            $order_id = $request->get('order_id');
            $CheckUSer =  User::where('user_id', $user_id)->where('user_type', 1)->first();
            if (empty($CheckUSer)) {
                return response()->json(['status' => 401, 'message' => "User Not Found"]);
            }
            
            $orderData = Order::select('tbl_orders.*','U.profile_image',  'D.*')
            ->leftjoin('tbl_user_delivery_address as D', 'tbl_orders.delivery_address_id', 'D.delivery_address_id')
            ->leftjoin('tbl_users as U', 'tbl_orders.user_id', 'U.user_id')
            ->where('tbl_orders.user_id',$order_user_id)
            ->where('tbl_orders.order_id',$order_id)
			->first();
			$total = 0;
			if (!empty($orderData)) {
                $Data['order_id'] = $orderData['order_id'];
                $Data['payment_type'] = $orderData['payment_type'] == 1 ? 'Cash on Delivery' : 'Card Payment';
                $Data['total_amount'] = $orderData['total_amount'];
                if($orderData['status'] == 1){
                    $Data['status'] = 'Processing';
                }else  if($orderData['status'] == 2){
                    $Data['status'] = 'Confirmed ';
                }else  if($orderData['status'] == 3){
                    $Data['status'] = 'Completed';
                }else  if($orderData['status'] == 4){
                    $Data['status'] = 'On Hold';
                }else  if($orderData['status'] == 5){
                    $Data['status'] = 'Cancelled';
                }else  if($orderData['status'] == 6){
                    $Data['status'] = 'Delivery Started';
                }
                $Data['delivery_address_id'] = $orderData['delivery_address_id'];
                $Data['address'] = $orderData['address'];
                $Data['user_id'] = $orderData['user_id'];
                $Data['first_name'] = $orderData['first_name'];
                $Data['last_name'] = $orderData['last_name'];
                $Data['profile_image'] = $orderData['profile_image'];
                $Data['mobile_number'] = $orderData['mobile_number'];
                $Data['home_no'] = $orderData['home_no'];
                $Data['street'] = $orderData['street'];
                $Data['landmark'] = $orderData['landmark'];
                $Data['city'] = $orderData['city'];
                $Data['area'] = $orderData['area'];
                $Data['society'] = $orderData['society'];
                $Data['pincode'] = $orderData['pincode'];
                $Data['latitude'] = $orderData['latitude'];
                $Data['longitude'] = $orderData['longitude'];

                $itemData = OrdersItem::select('tbl_orders_item.*','P.product_id','P.coupon_code','P.name','P.product_image', 'PU.price', 'PU.unit', 'U.unit_name')
                ->leftjoin('tbl_products as P', 'tbl_orders_item.product_id', 'P.product_id')
                ->leftjoin('tbl_product_price_unit as PU', 'tbl_orders_item.price_unit_id', 'PU.price_unit_id')
                ->leftjoin('tbl_unit as U', 'PU.unit_id', 'U.id')
                ->where('tbl_orders_item.user_id',$order_user_id)
                ->where('tbl_orders_item.order_id',$order_id)
                ->get();
    
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
                    }
                }else{
                    $itemlist = [];
                }
                
                $Data['item_details'] = $itemlist;

            }else{
                $Data = [];
            }
            
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

    public function startDelivery(Request $request)
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
            
            $CheckUSer =  User::where('user_id', $user_id)->where('user_type', 1)->first();
            if (empty($CheckUSer)) {
                return response()->json(['status' => 401, 'message' => "User Not Found"]);
            }
            $result = Order::where('order_id',$order_id)->update(['status'=>6]);
           
            return response()->json(['status' => 200, 'message' => "Delivery Started."]);

        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['status' => 401, 'message' => "Something went wrong. Please try again."]);
        }
    }

    public function completeDelivery(Request $request)
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

            $CheckUSer =  User::where('user_id', $user_id)->where('user_type', 1)->first();
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
            if($orderData['payment_type'] == 1){
                $data['user_id'] = $user_id;
                $data['order_id'] = $order_id;
                $data['amount_to_pay'] = $orderData['total_amount'];
                DeliveryBoy::insert($data);
                $user_id1 = $orderData->user_id;
                $userData = User::where('user_id',$user_id1)->first();
                if($userData->device_token){
                    $message = 'Hey '.$userData->first_name.' '.$userData->last_name.', Your Order('.$order_id.') is Completed';

                    $is_send = Common::send_push($userData->device_token,'Order Completed',$message,$userData->device_type);
                    if($is_send){
                        $notificationdata = array(
                            'user_id'=>$user_id1,
                            'item_id'=>$order_id,
                            'notification_type'=>3,
                            'title'=>'Order Completed',
                            'message'=>$message,
                        );
    
                        Notification::insert($notificationdata);
                    }
                }
            
                $result = Order::where('order_id',$order_id)->update(['status'=>3,'completed_at'=>date('Y-m-d h:i:s')]);
                return response()->json(['status' => 200, 'message' => "Order Completed Successfully."]);
            }else{
                $result = Order::where('order_id',$order_id)->update(['status'=>3,'completed_at'=>date('Y-m-d h:i:s')]);
                return response()->json(['status' => 200, 'message' => "Order Completed Successfully."]);
            }
           
        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['status' => 401, 'message' => "Something went wrong. Please try again."]);
        }
    }

    public function onHoldDelivery(Request $request)
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

            $CheckUSer =  User::where('user_id', $user_id)->where('user_type', 1)->first();
            if (empty($CheckUSer)) {
                return response()->json(['status' => 401, 'message' => "User Not Found"]);
            }

            $rules = [
                'order_id' => 'required',
                'reason_type' => 'required',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $messages = $validator->errors()->all();
                $msg = $messages[0];
                return response()->json(['status' => 401, 'message' => $msg]);
            }
            
            $order_id = $request->get('order_id');
            $reason_type = $request->get('reason_type');
            $orderData = Order::where('order_id',$order_id)->first();
            $user_id1 = $orderData->user_id;
            $userData = User::where('user_id',$user_id1)->first();
            if($userData->device_token){

                //1 for refused to accept/ 2 for not at home/ 3 for didn't paid/ 4 for address not found	
                if($reason_type == 1){
                    $reason = 'refused to accept';
                }else if($reason_type == 2){
                    $reason = 'not at home';
                }else if($reason_type == 3){
                    $reason = " didn't paid";
                }else if($reason_type == 4){
                    $reason = 'address not found';
                }
                $message = 'Hey '.$userData->first_name.' '.$userData->last_name.', Your Order('.$order_id.') is On Hold due to '.$reason;
               
                $is_send = Common::send_push($userData->device_token,'Order On Hold',$message,$userData->device_type);
                if( $is_send ){
                    $notificationdata = array(
                        'user_id'=>$user_id1,
                        'item_id'=>$order_id,
                        'notification_type'=>5,
                        'title'=>'Order On Hold',
                        'message'=>$message,
                    );
    
                    Notification::insert($notificationdata);
                }
            }

            $result = Order::where('order_id',$order_id)->update(['status'=>4,'hold_reason_type'=>$reason_type]);
            return response()->json(['status' => 200, 'message' => "Order On Hold Successfully."]);
           
        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['status' => 401, 'message' => "Something went wrong. Please try again."]);
        }
    }
}