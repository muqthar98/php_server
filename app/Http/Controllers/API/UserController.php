<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Validator;
use Hash;
use DB;
use File;
use Log;
use Image;
use App\User;
use App\UserDeliveryAddress;
use App\Admin;
use App\Common;
use Laravel\Passport\Token;
use Storage;

class UserController extends Controller
{

    public function registration(Request $request)
    {
        try {
            $headers = $request->headers->all();
            
            $verify_request_base = Admin::verify_request_base($headers);

            if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
                return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
                exit();
            }

            $messages = [
                'first_name.regex' => 'Firstname contains only alphabetic and space.',
                'last_name.regex' => 'Lastname contains only alphabetic and space.',
            ];
    
            $rules = [
                'first_name' => 'required|regex:/^[a-zA-Z\s]+$/',
                'last_name' => 'required|regex:/^[a-zA-Z\s]+$/',
                'email' => 'required',
                'login_type' => 'required',
                'identity' => 'required',
                'device_token' => 'required',
                'device_type' => 'required',
            ];
    
            $validator = Validator::make($request->all(), $rules, $messages);
    
            if ($validator->fails()) {
                $messages = $validator->errors()->all();
                $msg = $messages[0];
                return response()->json(['status' => 401, 'message' => $msg]);
            }
            
            $CheckUSer =  User::where('identity', $request->get('identity'))->first();

            $s3 = Storage::disk('s3');
            if ($request->get('profile_image')) {
                // $image = Image::make('url');
                // $image->encode('jpg');
                $file = $request->get('profile_image');
                $imageFileName='user_' . rand(111,999) . '.jpg';
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
            
            if (empty($CheckUSer)) {

                // $image = Image::make('url');
                // $image->encode('jpg');
                // $s3 = Storage::disk('s3');
                // $filePath = '/profilePhotos/'.$time();
                // $s3->put($filePath, $image->__toString(), 'public');

                $UserTypeModel = new User;
                $user_id = $UserTypeModel->get_random_string();
                $data['user_id'] = $user_id;
                $data['first_name'] = $request->get('first_name');
                $data['last_name'] = $request->get('last_name');
                $data['email'] = $request->get('email');
                $data['login_type'] = $request->get('login_type');
                $data['identity'] = $request->get('identity');
                $data['device_token'] = $request->get('device_token');
                $data['device_type'] = $request->get('device_type');

                $result =  User::insert($data);
            } else {
                $data['login_type'] = $request->get('login_type');
                $data['identity'] = $request->get('identity');
                $data['device_token'] = $request->get('device_token');
                $data['device_type'] = $request->get('device_type');
                $user_id = $CheckUSer->user_id;
                $result =  User::where('user_id', $user_id)->update($data);
            }
            if (!empty($result)) {

                $User =  User::where('user_id', $user_id)->where('user_type',0)->first();

                $User['token'] = 'Bearer ' . $User->createToken(env('APP_NAME'))->accessToken;

                unset($User->username);
                unset($User->password);
                unset($User->fullname);
                unset($User->user_type);
                unset($User->mobile_no);
                unset($User->created_at);
                unset($User->updated_at);

                return response()->json(['status' => 200, 'message' => "User registered successfully.", 'data' => $User]);
            } else {
                return response()->json(['status' => 401, 'message' => "Error While User Registeration"]);
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
                    $data['device_token'] = "";
                    $data['device_type'] = 0;
                    $result =  User::where('user_id', $user->user_id)->update($data);
                    return response()->json(['success_code' => 200, 'response_code' => 1, 'response_message' => "User logout successfully."]);
                } else {
                    return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => "User Id is required"]);
                }
               
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
                       
            $User =  User::where('user_id', $user_id)->where('user_type',0)->first();
            if (empty($User)) {
                return response()->json(['status' => 401, 'message' => "User Not Found"]);
            }
            $User->profile_image = $User->profile_image ? $User->profile_image : "";
            $UserDeliveryAddress =  UserDeliveryAddress::where('user_id', $user_id)->where('is_default', 1)->where('is_delete', 0)->get();
            // if($UserDeliveryAddress){
            //     $address = $UserDeliveryAddress->home_no.', '.$UserDeliveryAddress->society.', '.$UserDeliveryAddress->street.', '.$UserDeliveryAddress->landmark.', '.$UserDeliveryAddress->area.', '.$UserDeliveryAddress->city.', '.$UserDeliveryAddress->pincode;
            // }
            
            $User->delivery_address = $UserDeliveryAddress;

            unset($User->username);
            unset($User->password);
            unset($User->fullname);
            unset($User->user_type);
            unset($User->mobile_no);
            unset($User->created_at);
            unset($User->updated_at);

            return response()->json(['status' => 200, 'message' => "User Profile Get successfully.", 'data' => $User]);
        
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
            
            $messages = [
                'first_name.regex' => 'Firstname contains only alphabetic and space.',
                'last_name.regex' => 'Lastname contains only alphabetic and space.',
            ];
    
            $rules = [
                'first_name' => 'required|regex:/^[a-zA-Z\s]+$/',
                'last_name' => 'required|regex:/^[a-zA-Z\s]+$/',
                'email' => 'required',
            ];
    
            $validator = Validator::make($request->all(), $rules, $messages);
    
            if ($validator->fails()) {
                $messages = $validator->errors()->all();
                $msg = $messages[0];
                return response()->json(['status' => 401, 'message' => $msg]);
            }
            
            $CheckUSer =  User::where('user_id', $user_id)->where('user_type',0)->first();
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
                // File::makeDirectory($destinationPath, $mode = 0777, true, true);
                $filePath = $destinationPath . $imageFileName;
                if ($s3->put($filePath, file_get_contents($file)) ){
                    // $image_url = $s3->url($imagePath);
                    $profile_image = $imageFileName;
                }else{
                    $profile_image = "";
                }
                $data['profile_image'] = $profile_image;
            }
        
            $data['user_id'] = $user_id;
            $data['first_name'] = $request->get('first_name');
            $data['last_name'] = $request->get('last_name');
            $data['email'] = $request->get('email');

            $result =  User::where('user_id', $user_id)->update($data);

            if (!empty($result)) {

                $User =  User::where('user_id', $user_id)->where('user_type',0)->first();

                unset($User->username);
                unset($User->password);
                unset($User->fullname);
                unset($User->user_type);
                unset($User->mobile_no);
                unset($User->created_at);
                unset($User->updated_at);


                return response()->json(['status' => 200, 'message' => "User Profile Update successfully.", 'data' => $User]);
            } else {
                return response()->json(['status' => 401, 'message' => "Error While User Profile Update"]);
            }
        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['status' => 401, 'message' => "Something went wrong. Please try again."]);
        }
    }

    public function addDeliveryAddress(Request $request)
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
            
            $messages = [
                'first_name.regex' => 'Firstname contains only alphabetic and space.',
                'last_name.regex' => 'Lastname contains only alphabetic and space.',
                'mobile_no.regex' => 'Mobile Number contains only numeric value.',
                'alt_mobile_no.regex' => 'Mobile Number contains only numeric value.',
            ];
    
            $rules = [
                'first_name' => 'required|regex:/^[a-zA-Z\s]+$/',
                'last_name' => 'required|regex:/^[a-zA-Z\s]+$/',
                'mobile_no' => 'required|min:10|max:15|regex:/^[0-9\+\s]+$/',
                'alt_mobile_no' => 'min:10|max:15|regex:/^[0-9\+\s]+$/',
                'home_no' => 'required',
                'street' => 'required',
                'landmark' => 'required',
                'city' => 'required',
                'area' => 'required',
                'society' => 'required',
                'pincode' => 'required',
                'latitude' => 'required',
                'longitude' => 'required',
                'address_type' => 'required'
            ];
    
            $validator = Validator::make($request->all(), $rules, $messages);
    
            if ($validator->fails()) {
                $messages = $validator->errors()->all();
                $msg = $messages[0];
                return response()->json(['status' => 401, 'message' => $msg]);
            }
            
            $CheckUSer =  User::where('user_id', $user_id)->where('user_type',0)->first();
            if (empty($CheckUSer)) {
                return response()->json(['status' => 401, 'message' => "User Not Found"]);
            }
            if($request->get('is_default')){
               UserDeliveryAddress::where('user_id', $user_id)->update(['is_default'=>0]);
            } 

            $UserDeliveryAddressModel = new UserDeliveryAddress;
            $delivery_address_id = $UserDeliveryAddressModel->get_random_string();

            $data['delivery_address_id'] = $delivery_address_id;
            $data['user_id'] = $user_id;
            $data['first_name'] = $request->get('first_name');
            $data['last_name'] = $request->get('last_name');
            $data['mobile_number'] = $request->get('mobile_no');
            $data['alt_mobile_number'] = $request->get('alt_mobile_no');
            $data['home_no'] = $request->get('home_no');
            $data['street'] = $request->get('street');
            $data['landmark'] = $request->get('landmark');
            $data['city'] = $request->get('city');
            $data['area'] = $request->get('area');
            $data['society'] = $request->get('society');
            $data['pincode'] = $request->get('pincode');
            $data['latitude'] = $request->get('latitude');
            $data['longitude'] = $request->get('longitude');
            $data['address_type'] = $request->get('address_type');
            $data['is_default'] = $request->get('is_default') ? $request->get('is_default') : 0;

            $result = UserDeliveryAddress::insert($data);

            if (!empty($result)) {

                // $User =  UserDeliveryAddress::select('delivery_address_id','user_id','first_name','last_name','mobile_number','alt_mobile_number','home_no','street','street','landmark','city','area','society','pincode','address_type','is_default')->where('user_id', $user_id)->get();

                return response()->json(['status' => 200, 'message' => "User Delivery Address Inserted successfully."]);
            } else {
                return response()->json(['status' => 401, 'message' => "Error While User Delivery Address Insert"]);
            }
        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['status' => 401, 'message' => "Something went wrong. Please try again."]);
        }
    }

    
    public function getAllDeliveryAddress(Request $request)
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
            
            
            $CheckUSer =  User::where('user_id', $user_id)->where('user_type',0)->first();
            if (empty($CheckUSer)) {
                return response()->json(['status' => 401, 'message' => "User Not Found"]);
            }

            $User =  UserDeliveryAddress::select('delivery_address_id','user_id','first_name','last_name','mobile_number','alt_mobile_number','home_no','street','street','landmark','city','area','society','pincode','latitude','longitude','address_type','is_default')->where('user_id', $user_id)->where('is_delete', 0)->get();

            if (!empty($User)) {             
                return response()->json(['status' => 200, 'message' => "User Delivery Address Data Not Found.", 'data' => $User]);
            } else {
                return response()->json(['status' => 401, 'message' => "User Delivery Address Data Not Found."]);
            }
        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['status' => 401, 'message' => "Something went wrong. Please try again."]);
        }
    }

    public function updateDeliveryAddress(Request $request)
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
            
            $messages = [
                'first_name.regex' => 'Firstname contains only alphabetic and space.',
                'last_name.regex' => 'Lastname contains only alphabetic and space.',
                'mobile_no.regex' => 'Mobile Number contains only numeric value.',
                'alt_mobile_no.regex' => 'Mobile Number contains only numeric value.',
            ];
    
            $rules = [
                'delivery_address_id' => 'required',
                'first_name' => 'required|regex:/^[a-zA-Z\s]+$/',
                'last_name' => 'required|regex:/^[a-zA-Z\s]+$/',
                'mobile_no' => 'required|min:10|max:15|regex:/^[0-9\+\s]+$/',
                'alt_mobile_no' => 'min:10|max:15|regex:/^[0-9\+\s]+$/',
                'home_no' => 'required',
                'street' => 'required',
                'landmark' => 'required',
                'city' => 'required',
                'area' => 'required',
                'society' => 'required',
                'pincode' => 'required',
                'latitude' => 'required',
                'longitude' => 'required',
                'address_type' => 'required'
            ];
    
            $validator = Validator::make($request->all(), $rules, $messages);
    
            if ($validator->fails()) {
                $messages = $validator->errors()->all();
                $msg = $messages[0];
                return response()->json(['status' => 401, 'message' => $msg]);
            }
            
            $CheckUSer =  User::where('user_id', $user_id)->where('user_type',0)->first();
            if (empty($CheckUSer)) {
                return response()->json(['status' => 401, 'message' => "User Not Found"]);
            }
            if($request->get('is_default')){
               UserDeliveryAddress::where('user_id', $user_id)->update(['is_default'=>0]);
            }           
            $delivery_address_id = $request->get('delivery_address_id');

            $CheckUserDeliveryAddress =  UserDeliveryAddress::where('delivery_address_id',$delivery_address_id)->where('user_id', $user_id)->where('is_delete', 0)->first();
            if (empty($CheckUserDeliveryAddress)) {
                return response()->json(['status' => 401, 'message' => "User Delivery Address Not Found"]);
            }

            $data['first_name'] = $request->get('first_name');
            $data['last_name'] = $request->get('last_name');
            $data['mobile_number'] = $request->get('mobile_no');
            $data['alt_mobile_number'] = $request->get('alt_mobile_no');
            $data['home_no'] = $request->get('home_no');
            $data['street'] = $request->get('street');
            $data['landmark'] = $request->get('landmark');
            $data['city'] = $request->get('city');
            $data['area'] = $request->get('area');
            $data['society'] = $request->get('society');
            $data['pincode'] = $request->get('pincode');
            $data['latitude'] = $request->get('latitude');
            $data['longitude'] = $request->get('longitude');
            $data['address_type'] = $request->get('address_type');
            $data['is_default'] = $request->get('is_default') ? $request->get('is_default') : 0;

            $result =  UserDeliveryAddress::where('delivery_address_id',$delivery_address_id)->where('user_id',$user_id)->update($data);

            if (!empty($result)) {

                // $User =  UserDeliveryAddress::select('delivery_address_id','user_id','first_name','last_name','mobile_number','alt_mobile_number','home_no','street','street','landmark','city','area','society','pincode','address_type','is_default')->where('user_id', $user_id)->get();

                return response()->json(['status' => 200, 'message' => "User Delivery Address Updated successfully."]);
            } else {
                return response()->json(['status' => 401, 'message' => "Error While User Delivery Address Update"]);
            }
        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['status' => 401, 'message' => "Something went wrong. Please try again."]);
        }
    }


    public function deleteDeliveryAddress(Request $request)
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
                'delivery_address_id' => 'required'
            ];
    
            $validator = Validator::make($request->all(), $rules);
    
            if ($validator->fails()) {
                $messages = $validator->errors()->all();
                $msg = $messages[0];
                return response()->json(['status' => 401, 'message' => $msg]);
            }
            
            $CheckUSer =  User::where('user_id', $user_id)->where('user_type',0)->first();
            if (empty($CheckUSer)) {
                return response()->json(['status' => 401, 'message' => "User Not Found"]);
            }

            $delivery_address_id = $request->get('delivery_address_id');
        
            $CheckUserDeliveryAddress =  UserDeliveryAddress::where('delivery_address_id',$delivery_address_id)->where('user_id', $user_id)->where('is_delete', 0)->first();
            if (empty($CheckUserDeliveryAddress)) {
                return response()->json(['status' => 401, 'message' => "User Delivery Address Not Found"]);
            }

            if($CheckUserDeliveryAddress->is_default == 1){
                return response()->json(['status' => 401, 'message' => "You can not Delete Default User Delivery Address"]); 
            }

            // $result =  UserDeliveryAddress::where('delivery_address_id',$delivery_address_id)->where('user_id',$user_id)->delete();
            $result =  UserDeliveryAddress::where('delivery_address_id',$delivery_address_id)->where('user_id',$user_id)->update(['is_delete'=>1]);
            if (!empty($result)) {

                // $User =  UserDeliveryAddress::select('delivery_address_id','user_id','first_name','last_name','mobile_number','alt_mobile_number','home_no','street','street','landmark','city','area','society','pincode','address_type','is_default')->where('user_id', $user_id)->get();

                return response()->json(['status' => 200, 'message' => "User Delivery Address Deleted successfully."]);
            } else {
                return response()->json(['status' => 401, 'message' => "Error While User Delivery Address Delete"]);
            }
        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['status' => 401, 'message' => "Something went wrong. Please try again."]);
        }
    }

    
    public function getDefaultDeliveryDetails(Request $request)
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
            
            
            $CheckUSer =  User::where('user_id', $user_id)->where('user_type',0)->first();
            if (empty($CheckUSer)) {
                return response()->json(['status' => 401, 'message' => "User Not Found"]);
            }

            $User =  UserDeliveryAddress::select('delivery_address_id','user_id','first_name','last_name','mobile_number','alt_mobile_number','home_no','street','street','landmark','city','area','society','pincode','latitude','longitude','address_type','is_default')->where('is_default', 1)->where('user_id', $user_id)->where('is_delete', 0)->get();   

            if (!empty($User)) {             
                return response()->json(['status' => 200, 'message' => "User Delivery Address Data Found.", 'data' => $User]);
            } else {
                return response()->json(['status' => 401, 'message' => "User Delivery Address Data Not Found."]);
            }
        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['status' => 401, 'message' => "Something went wrong. Please try again."]);
        }
    }
}
