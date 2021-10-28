<?php
namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Session;
use DB;
use Log;
use App\Admin;
use App\User;
use App\Banner;
use App\FAQ;
use App\City;
use App\Area;
use App\Notification;
use Validator;

class SettingsController extends Controller
{

	public function getBannerList(Request $request)
	{
		try{
         
            $headers = $request->headers->all();
                
            $verify_request_base = Admin::verify_request_base($headers);

            if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
                return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
                exit();
			}
			
			$BannerData =  Banner::get();
                       
            if (count($BannerData) > 0) {
                return response()->json(['status' => 200, 'message' => "Banner Data Get Successfully.", 'data' => $BannerData]);
            } else {
                return response()->json(['status' => 401, 'message' => "No Data Found."]);
            }
        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => "Something went wrong. Please try again."]);
        }
    }

    public function getFAQList(Request $request)
	{
		try{
         
            $headers = $request->headers->all();
                
            $verify_request_base = Admin::verify_request_base($headers);

            if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
                return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
                exit();
			}
			
            $FAQData =  FAQ::get();
            $data = [];
            $i=0;
            foreach($FAQData as $value){
                $data[$i]['id'] = $value['id'];
                $data[$i]['question'] = $value['question'];
                $data[$i]['answer'] = str_replace('\r\n','',$value['answer']);
                $i++;
            }           
            if (count($data) > 0) {
                return response()->json(['status' => 200, 'message' => "FAQ Data Get Successfully.", 'data' => $data]);
            } else {
                return response()->json(['status' => 401, 'message' => "No Data Found."]);
            }
        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => "Something went wrong. Please try again."]);
        }
    }
    
    public function getCityList(Request $request)
	{
		try{
         
            $headers = $request->headers->all();
                
            $verify_request_base = Admin::verify_request_base($headers);

            if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
                return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
                exit();
			}
			
			$CityData =  City::select('id','city_name')->get();
                       
            if (count($CityData) > 0) {
                return response()->json(['status' => 200, 'message' => "City Data Get Successfully.", 'data' => $CityData]);
            } else {
                return response()->json(['status' => 401, 'message' => "No Data Found."]);
            }
        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => "Something went wrong. Please try again."]);
        }
    }
    
    public function getAreaList(Request $request)
	{
		try{
         
            $headers = $request->headers->all();
                
            $verify_request_base = Admin::verify_request_base($headers);

            if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
                return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
                exit();
			}
			
			$AreaData =  Area::select('tbl_area.id','tbl_area.city_id','tbl_area.area_name','C.city_name')->leftjoin('tbl_city as C', 'tbl_area.city_id', 'C.id')->get();
                       
            if (count($AreaData) > 0) {
                return response()->json(['status' => 200, 'message' => "Area Data Get Successfully.", 'data' => $AreaData]);
            } else {
                return response()->json(['status' => 401, 'message' => "No Data Found."]);
            }

        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => "Something went wrong. Please try again."]);
        }
    }
    
    public function getAddressList(Request $request)
	{
		try{
         
            $headers = $request->headers->all();
                
            $verify_request_base = Admin::verify_request_base($headers);

            if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
                return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
                exit();
			}
			
            $data =  Area::select('tbl_area.id','tbl_area.city_id','tbl_area.area_name','C.city_name')->leftjoin('tbl_city as C', 'tbl_area.city_id', 'C.id')->get();
            $addressData = [];
            foreach($data as $key => $value){
                $list['id'] = $value['id'];
                $list['area_name'] = $value['area_name'];
                $addressData[$value['city_id']]['city_id'] = $value['city_id'];
                $addressData[$value['city_id']]['city_name'] = $value['city_name'];
                $addressData[$value['city_id']]['area'][] = $list;
            }
            if (count($addressData) > 0) {
                $addressData = array_values($addressData);
                return response()->json(['status' => 200, 'message' => "Area Data Get Successfully.", 'data' => $addressData]);
            } else {
                return response()->json(['status' => 401, 'message' => "No Data Found."]);
            }

        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => "Something went wrong. Please try again."]);
        }
    }
    
    public function getAreaByCity(Request $request)
	{
		try{
         
            $headers = $request->headers->all();
                
            $verify_request_base = Admin::verify_request_base($headers);

            if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
                return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
                exit();
			}
            $rules = [
                'city_id' => 'required',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $messages = $validator->errors()->all();
                $msg = $messages[0];
                return response()->json(['status' => 401, 'message' => $msg]);
            }
            $city_id = $request->get('city_id');
            
			$AreaData =  Area::select('tbl_area.id','tbl_area.city_id','tbl_area.area_name','C.city_name')->leftjoin('tbl_city as C', 'tbl_area.city_id', 'C.id')->where('tbl_area.city_id',$city_id)->get();
                       
            if (count($AreaData) > 0) {
                return response()->json(['status' => 200, 'message' => "Area Data Get Successfully.", 'data' => $AreaData]);
            } else {
                return response()->json(['status' => 401, 'message' => "No Data Found."]);
            }

        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => "Something went wrong. Please try again."]);
        }
    }


    public function getAllNotification(Request $request)
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
            
            $CheckUSer =  User::where('user_id', $user_id)->first();
            if (empty($CheckUSer)) {
                return response()->json(['status' => 401, 'message' => "User Not Found"]);
            }
            
            $notificationData = Notification::where('user_id',$user_id)->orWhereNull('user_id')->orWhere('notification_type',7)
                            ->offset($start)
                            ->limit($limit)
                            ->get();
            
            if (count($notificationData) > 0) {
                $Data = [];
                foreach($notificationData as $k => $value){
                    $list['user_id'] = $value['user_id'] ? $value['user_id'] : $user_id;
                    $list['item_id'] = $value['item_id'] ? $value['item_id'] : 0;
                    $list['notification_type'] = $value['notification_type'] ? $value['notification_type'] : 0;
                    $list['title'] = $value['title'];
                    $list['message'] = $value['message'];
                    $list['image'] = $value['image'];
					$Data[] = $list; 
					         
                }
            }else{
				$Data = [];
			}
            
            if (!empty($Data)) {             
                return response()->json(['status' => 200, 'message' => "Notification Data Found.", 'data' => $Data]);
            } else {
                return response()->json(['status' => 401, 'message' => "Notification Data Not Found."]);
            }

        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['status' => 401, 'message' => "Something went wrong. Please try again."]);
        }
    }

}


 