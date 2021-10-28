<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Validator;
use DB;
use File;
use Log;
use App\Admin;
use App\Common;
use App\Product;
use App\Category;
use App\Cart;
use App\ProductPriceUnit;
use App\Unit;
use App\Wishlist;
use App\User;
use App\Coupon;
use App\ProductReview;
use Laravel\Passport\Token;

class ProductController extends Controller
{
  
    public function getProductList(Request $request)
    {
        try{
         
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
            $user_id = $request->get('user_id');
            // $ProductData =  Product::select('tbl_products.*', 'C.category_name')
                        // ->leftjoin('tbl_category as C', 'tbl_products.category_id', 'C.id')
                        // ->orderBy('tbl_products.id','DESC')
                        // ->offset($start)
                        // ->limit($limit)
                        // ->get();
            $CategoryData =  Category::orderBy('id','DESC')
                            ->offset($start)
                            ->limit($limit)
                            ->get();
                        
            if (count($CategoryData) > 0) {
                $Productlist = Common::GetProductDataByCategory($CategoryData,$user_id);
                $Productlist = array_values($Productlist);
                return response()->json(['status' => 200, 'message' => "Product Data Get Successfully.", 'data' => $Productlist]);
            } else {
                return response()->json(['status' => 401, 'message' => "No Data Found."]);
            }
        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => "Something went wrong. Please try again."]);
        }
    }

    public function getCategoryList(Request $request)
    {
        try{
            $headers = $request->headers->all();
                
            $verify_request_base = Admin::verify_request_base($headers);

            if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
                return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
                exit();
            }

            $CategoryData =  Category::select('id','category_name')->orderBy('id','DESC')->get();
                        
            if (count($CategoryData) > 0) {
                return response()->json(['status' => 200, 'message' => "Category Data Get Successfully.", 'data' => $CategoryData]);
            } else {
                return response()->json(['status' => 401, 'message' => "No Data Found."]);
            }
        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => "Something went wrong. Please try again."]);
        }
    }

    public function searchProduct(Request $request)
    {
        try{

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
            $user_id = $request->get('user_id');
            $search = $request->get('search_keyword');
            $limit = $request->get('limit') ? $request->get('limit') : 20;
            $start = $request->get('start') ? $request->get('start') : 0;
            if($search){
                $ProductData =  Product::select('tbl_products.*', 'C.category_name')
                ->leftjoin('tbl_category as C', 'tbl_products.category_id', 'C.id')
                ->Where('tbl_products.name', 'LIKE',"%{$search}%")
                ->orWhere('C.category_name', 'LIKE',"%{$search}%")
                ->offset($start)
                ->limit($limit)
                ->orderBy('tbl_products.id','DESC')
                ->get();
            }else{
                $ProductData =  Product::select('tbl_products.*', 'C.category_name')
                ->leftjoin('tbl_category as C', 'tbl_products.category_id', 'C.id')
                ->offset($start)
                ->limit($limit)
                ->orderBy('tbl_products.id','DESC')
                ->get();
            }

            $CartTotal = Cart::select( DB::raw('SUM(PU.price) as total_amount'))
                ->leftjoin('tbl_product_price_unit as PU', 'tbl_cart.price_unit_id', 'PU.price_unit_id')
                ->first();
            $CartCount= Cart::count();

            $CartData['total_amount'] =  $CartTotal['total_amount'];
            $CartData['count'] =  $CartCount;

            if (count($ProductData) > 0) {
                $Productlist = Common::GetProductData($ProductData,$user_id,1);
                return response()->json(['status' => 200, 'message' => "Product Data Get Successfully.", 'cartdata' => $CartData, 'data' => $Productlist]);
            } else {
                return response()->json(['status' => 401, 'message' => "No Data Found."]);
            }
        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => "Something went wrong. Please try again."]);
        }
    }
    
    public function searchProductByCategory(Request $request)
    {
        try{

            $headers = $request->headers->all();
                
            $verify_request_base = Admin::verify_request_base($headers);

            if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
                return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
                exit();
            }

            $rules = [
                'start' => 'required',
                'category_id' => 'required',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $messages = $validator->errors()->all();
                $msg = $messages[0];
                return response()->json(['status' => 401, 'message' => $msg]);
            }

            $user_id = $request->get('user_id');
            $category_id = $request->get('category_id');
            $search = $request->get('search_keyword');
            $limit = $request->get('limit') ? $request->get('limit') : 20;
            $start = $request->get('start') ? $request->get('start') : 0;
            if($search){
                $ProductData =  Product::select('tbl_products.*', 'C.category_name')
                ->leftjoin('tbl_category as C', 'tbl_products.category_id', 'C.id')
                ->Where('tbl_products.name', 'LIKE',"%{$search}%")
                ->Where('C.id', $category_id)
                ->offset($start)
                ->limit($limit)
                ->orderBy('tbl_products.id','DESC')
                ->get();
            }else{
                $ProductData =  Product::select('tbl_products.*', 'C.category_name')
                ->leftjoin('tbl_category as C', 'tbl_products.category_id', 'C.id')
                ->Where('C.id', $category_id)
                ->offset($start)
                ->limit($limit)
                ->orderBy('tbl_products.id','DESC')
                ->get();
            }

            $CartTotal = Cart::select( DB::raw('SUM(PU.price) as total_amount'))
                ->leftjoin('tbl_product_price_unit as PU', 'tbl_cart.price_unit_id', 'PU.price_unit_id')
                ->first();
            $CartCount= Cart::count();

            $CartData['total_amount'] =  $CartTotal['total_amount'];
            $CartData['count'] =  $CartCount;

            if (count($ProductData) > 0) {
                $Productlist = Common::GetProductData($ProductData,$user_id,1);
                return response()->json(['status' => 200, 'message' => "Product Data Get Successfully.", 'cartdata' => $CartData, 'data' => $Productlist]);
            } else {
                return response()->json(['status' => 401, 'message' => "No Data Found."]);
            }
        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => "Something went wrong. Please try again."]);
        }
    }

    // 1 - Price Low to High
    // 2 - Price High to Low
    // 3 - Alphabetically
    // 4 - Rupee Saving High to Low
    // 5 - Rupee Saving Low to High
    // 6 - % Off - High to Low

    public function sortByProduct(Request $request)
    {
        try{

            $headers = $request->headers->all();
                
            $verify_request_base = Admin::verify_request_base($headers);

            if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
                return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
                exit();
            }

            $rules = [
                'start' => 'required',
                'sort_by' => 'required',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $messages = $validator->errors()->all();
                $msg = $messages[0];
                return response()->json(['status' => 401, 'message' => $msg]);
            }
            $user_id = $request->get('user_id');
            $sort_by = $request->get('sort_by');
            $search = $request->get('search_keyword');
            $category_id = $request->get('category_id');
            $limit = $request->get('limit') ? $request->get('limit') : 20;
            $start = $request->get('start') ? $request->get('start') : 0;
            
            // if($category_id){
                if($sort_by == 1 || $sort_by == 2 || $sort_by == 4 || $sort_by == 5){
                    $query =  Product::select('tbl_products.*', 'C.category_name',DB::raw('group_concat(PU.price_unit_id) as price_unit_id'),DB::raw('group_concat(PU.price) as price'), DB::raw('group_concat(PU.unit) as unit'), DB::raw('group_concat(PU.unit_id) as unit_id'), DB::raw('group_concat(PU.discount) as discount'))
                   ->join('tbl_product_price_unit as PU', 'tbl_products.product_id', 'PU.product_id')
                   ->leftjoin('tbl_category as C', 'tbl_products.category_id', 'C.id'); 
                   if ($search) {
                        $query->Where('tbl_products.name', 'LIKE',"%{$search}%");
                    }                    
                    if ($category_id) {
                        $query->where('C.id', $category_id);
                    } 
                   $query->groupBy('tbl_products.product_id');
                                // ->offset($start)
                                // ->limit($limit)
                    if($sort_by == 1){
                        $query->orderBy('PU.price','ASC');
                    }
                    if($sort_by == 2){
                        $query->orderBy('PU.price','DESC');
                    }
                    if($sort_by == 4){
                        $query->orderBy('PU.price','ASC');
                    }
                    if($sort_by == 5){
                        $query->orderBy('PU.price','DESC');
                    }
                    $ProductData = $query->get();
               }elseif($sort_by == 3){
                    $query =  Product::select('tbl_products.*', 'C.category_name',DB::raw('group_concat(PU.price_unit_id) as price_unit_id'),DB::raw('group_concat(PU.price) as price'), DB::raw('group_concat(PU.unit) as unit'), DB::raw('group_concat(PU.unit_id) as unit_id'), DB::raw('group_concat(PU.discount) as discount'))
                    ->leftjoin('tbl_product_price_unit as PU', 'tbl_products.product_id', 'PU.product_id')
                    ->leftjoin('tbl_category as C', 'tbl_products.category_id', 'C.id');
                    if ($search) {
                            $query->Where('tbl_products.name', 'LIKE',"%{$search}%");
                        }                    
                        if ($category_id) {
                            $query->where('C.id', $category_id);
                        } 
                        $ProductData = $query->groupBy('tbl_products.product_id')
                        // ->offset($start)
                        //                 ->limit($limit)
                                        ->orderBy('tbl_products.name','ASC')
                                        ->get();
               }elseif($sort_by == 6){
                    $query =  Product::select('tbl_products.*', 'C.category_name',DB::raw('group_concat(PU.price_unit_id) as price_unit_id'),DB::raw('group_concat(PU.price) as price'), DB::raw('group_concat(PU.unit) as unit'), DB::raw('group_concat(PU.unit_id) as unit_id'), DB::raw('group_concat(PU.discount) as discount'))
                    ->leftjoin('tbl_product_price_unit as PU', 'tbl_products.product_id', 'PU.product_id')
                    ->leftjoin('tbl_category as C', 'tbl_products.category_id', 'C.id');  
                    if ($search) {
                            $query->Where('tbl_products.name', 'LIKE',"%{$search}%");
                        }                    
                        if ($category_id) {
                            $query->where('C.id', $category_id);
                        } 
                //    ->whereNotNull('PU.discount')              
                     $ProductData = $query->groupBy('tbl_products.product_id')
                    //  ->offset($start)
                    //                     ->limit($limit)
                                        ->get();
               }
            // }else{
            //     if($sort_by == 1 || $sort_by == 2 || $sort_by == 4 || $sort_by == 5){
            //         $ProductData =  Product::select('tbl_products.*', 'C.category_name', 'PU.price','PU.discount','PU.unit','PU.unit_id','PU.price_unit_id')
            //        ->join('tbl_product_price_unit as PU', 'tbl_products.product_id', 'PU.product_id')
            //        ->leftjoin('tbl_category as C', 'tbl_products.category_id', 'C.id')     
            //        ->offset($start)
            //        ->limit($limit)
            //        ->get();
            //    }elseif($sort_by == 3){
            //        $ProductData =  Product::select('tbl_products.*', 'C.category_name', 'PU.price')
            //        ->leftjoin('tbl_category as C', 'tbl_products.category_id', 'C.id')
            //        ->offset($start)
            //        ->limit($limit)
            //        ->orderBy('tbl_products.name','ASC')
            //        ->get();
            //    }elseif($sort_by == 6){
   
            //        $ProductData =  Product::select('tbl_products.*', 'C.category_name', 'PU.price','PU.discount','PU.unit','PU.unit_id','PU.price_unit_id')
            //        ->leftjoin('tbl_product_price_unit as PU', 'tbl_products.product_id', 'PU.product_id')
            //        ->leftjoin('tbl_category as C', 'tbl_products.category_id', 'C.id')  
            //     //    ->whereNotNull('PU.discount')   
            //        ->offset($start)
            //        ->limit($limit)
            //        ->get();
            //    }
            // }

            $CartTotal = Cart::select( DB::raw('SUM(PU.price) as total_amount'))
                ->leftjoin('tbl_product_price_unit as PU', 'tbl_cart.price_unit_id', 'PU.price_unit_id')
                ->first();
            $CartCount= Cart::count();

            $CartData['total_amount'] =  $CartTotal['total_amount'];
            $CartData['count'] =  $CartCount;

            if (count($ProductData) > 0) {
                $Productlist = Common::GetSortingProductData($ProductData,$user_id,$sort_by);
                $Productlist = array_slice($Productlist, $start, $limit);
                return response()->json(['status' => 200, 'message' => "Product Data Get Successfully.", 'cartdata' => $CartData, 'data' => $Productlist]);
            } else {
                return response()->json(['status' => 401, 'message' => "No Data Found."]);
            }
        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => "Something went wrong. Please try again."]);
        }
    }

    public function getProductById(Request $request)
    {
        try{

            $headers = $request->headers->all();
                
            $verify_request_base = Admin::verify_request_base($headers);

            if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
                return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
                exit();
            }
            
            $rules = [
                'product_id' => 'required',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $messages = $validator->errors()->all();
                $msg = $messages[0];
                return response()->json(['status' => 401, 'message' => $msg]);
            }
            $user_id = $request->get('user_id');
            $product_id = $request->get('product_id');
            
            $ProductData =  Product::select('tbl_products.*', 'C.category_name')
                        ->leftjoin('tbl_category as C', 'tbl_products.category_id', 'C.id')
                        ->Where('tbl_products.product_id', $product_id)
                        ->orderBy('tbl_products.id','DESC')
                        ->get();
    
                        
            if (count($ProductData) > 0) {
                $Productlist = Common::GetProductData($ProductData,$user_id,3);
                
                return response()->json(['status' => 200, 'message' => "Product Data Get Successfully.", 'data' => $Productlist]);
            } else {
                return response()->json(['status' => 401, 'message' => "No Data Found."]);
            }
        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => "Something went wrong. Please try again."]);
        }
    }

    public function getProductByCategoryId(Request $request)
    {
        try{

            $headers = $request->headers->all();
                
            $verify_request_base = Admin::verify_request_base($headers);

            if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
                return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
                exit();
            }
            
            $rules = [
                'category_id' => 'required',
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
            $user_id = $request->get('user_id');
            $category_id = $request->get('category_id');
            
            $ProductData =  Product::select('tbl_products.*', 'C.category_name')
                        ->leftjoin('tbl_category as C', 'tbl_products.category_id', 'C.id')
                        ->Where('tbl_products.category_id', $category_id)
                        ->orderBy('tbl_products.id','DESC')
                        ->offset($start)
                        ->limit($limit)
                        ->get();
    
                        
            if (count($ProductData) > 0) {
                $Productlist = Common::GetProductData($ProductData,$user_id,2);
                return response()->json(['status' => 200, 'message' => "Product Data Get Successfully.", 'data' => $Productlist]);
            } else {
                return response()->json(['status' => 401, 'message' => "No Data Found."]);
            }
        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => "Something went wrong. Please try again."]);
        }
    }

    public function addupdateToCart(Request $request)
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
                'price_unit_id' => 'required',
                'quantity' => 'required',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $messages = $validator->errors()->all();
                $msg = $messages[0];
                return response()->json(['status' => 401, 'message' => $msg]);
            }

            $product_id = $request->get('product_id');
            $price_unit_id = $request->get('price_unit_id');
            $quantity = $request->get('quantity');
            // print_r($product_id);
            // print_r($price_unit_id);
            // print_r($quantity);
            // die;
            // $CheckProductExist = Common::CheckProductExist($product_id);
            // if(empty($CheckProductExist)){
            //     return response()->json(['status' => 401, 'message' => "Product Not Found."]);
            // }

            // $CheckProductUnitExist = Common::CheckProductUnitExist($product_id,$price_unit_id);
            // if(empty($CheckProductUnitExist)){
            //     return response()->json(['status' => 401, 'message' => "Product Not Found With This Price Unit Id."]);
            // }
            foreach($product_id as $key => $value){
                $cartData = Cart::where('user_id',$user_id)->where('product_id',$value)->where('price_unit_id',$price_unit_id[$key])->first();
            
                if(empty($cartData)){
    
                    $data['user_id'] = $user_id;
                    $data['product_id'] = $value;
                    $data['price_unit_id'] = $price_unit_id[$key];
                    $data['quantity'] = $quantity[$key] ? $quantity[$key] : 1;
    
                    $ProductData =  Cart::insert($data);
        
                }else{
                    $data['quantity'] =  $quantity[$key] ? $quantity[$key] : 1;
                    $ProductData =  Cart::where('user_id',$user_id)->where('product_id',$value)->where('price_unit_id',$price_unit_id)->update($data);
                }
            }
           
            return response()->json(['status' => 200, 'message' => "Product Updated to Cart Successfully."]);

        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => "Something went wrong. Please try again."]);
        }
    }

    public function getCartList(Request $request)
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
            
            // $cartData = Cart::where('user_id',$user_id)->get();
            $CartData = Cart::select('tbl_cart.quantity','P.product_id','P.name','P.product_image', 'tbl_cart.price_unit_id', 'PU.price', 'PU.unit', 'U.unit_name')
                ->leftjoin('tbl_products as P', 'tbl_cart.product_id', 'P.product_id')
                ->leftjoin('tbl_product_price_unit as PU', 'tbl_cart.price_unit_id', 'PU.price_unit_id')
                ->leftjoin('tbl_unit as U', 'PU.unit_id', 'U.id')
                ->where('tbl_cart.user_id',$user_id)
				->get();
            $total = 0;
            if (count($CartData) > 0) {
                $Cartlist = [];
                foreach($CartData as $k => $value){
                    $list['product_id'] = $value['product_id'];
                    $list['name'] = $value['name'];
                    $product_image = explode(',',$value['product_image']);
                    $list['product_image'] = $product_image; 
                    $list['price_unit_id'] = $value['price_unit_id'];
                    $list['quantity'] = $value['quantity'];
                    $list['price'] = $value['price'];
                    $list['unit'] = $value['unit'].' '.$value['unit_name'];  
                    // if($value['coupon_code']){
                    //     $CouponData = Coupon::where('coupon_code', $value['coupon_code'])->first();
                    //     $list['coupon_discount'] =  $CouponData['amount'];
                    //     $total += $value['price'];
                    //     $total = $total-$list['coupon_discount'];
                    // }else{
                    //     $list['coupon_discount'] =  0;
                    //     $total += $value['price'];
                    // } 
                    $Cartlist[] = $list;     
                    $total += ((int)$value['price']*(int)$value['quantity']);        
                }

                return response()->json(['status' => 200, 'message' => "Cart Data Get Successfully.", 'total_amount' => $total, 'data' => $Cartlist]);
            } else {
                return response()->json(['status' => 401, 'message' => "No Data Found."]);
            }

        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => "Something went wrong. Please try again."]);
        }
    }

    public function removeProductFromCart(Request $request)
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
                'price_unit_id' => 'required',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $messages = $validator->errors()->all();
                $msg = $messages[0];
                return response()->json(['status' => 401, 'message' => $msg]);
            }

            $product_id = $request->get('product_id');
            $price_unit_id = $request->get('price_unit_id');
            
            $CheckProductExist = Common::CheckProductExist($product_id);
            if(empty($CheckProductExist)){
                return response()->json(['status' => 401, 'message' => "Product Not Found."]);
            }

            $CheckProductUnitExist = Common::CheckProductUnitExist($product_id,$price_unit_id);
            if(empty($CheckProductUnitExist)){
                return response()->json(['status' => 401, 'message' => "Product Not Found With This Price Unit Id."]);
            }

            $cartData = Cart::where('user_id',$user_id)->where('product_id',$product_id)->where('price_unit_id',$price_unit_id)->first();
            
            if(!empty($cartData)){
                $RemoveCart =  Cart::where('user_id',$user_id)->where('product_id',$product_id)->where('price_unit_id',$price_unit_id)->delete();
                return response()->json(['status' => 200, 'message' => "Product Removed From Cart Successfully."]);

            }else{
                return response()->json(['status' => 200, 'message' => "Product Not Found in Cart."]);
            }

        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => "Something went wrong. Please try again."]);
        }
    }


    public function addupdateToWishlist(Request $request)
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
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $messages = $validator->errors()->all();
                $msg = $messages[0];
                return response()->json(['status' => 401, 'message' => $msg]);
            }

            
            $product_id = $request->get('product_id');
                        
            $CheckProductExist = Common::CheckProductExist($product_id);
            if(empty($CheckProductExist)){
                return response()->json(['status' => 401, 'message' => "Product Not Found."]);
            }

            $wishlistData = Wishlist::where('user_id',$user_id)->where('product_id',$product_id)->first();
            
            if(empty($wishlistData)){

                $data['user_id'] = $user_id;
                $data['product_id'] = $product_id;
                // $data['price_unit_id'] = $price_unit_id;
                // $data['quantity'] = $quantity;

                $ProductData =  Wishlist::insert($data);

                return response()->json(['status' => 200, 'message' => "Product Inserted to Wishlist Successfully."]);

            }else{
                     
                // $data['quantity'] = $quantity;
                // $ProductData =  Wishlist::where('user_id',$user_id)->where('product_id',$product_id)->where('price_unit_id',$price_unit_id)->update($data);
                return response()->json(['status' => 401, 'message' => "Product Already into Wishlist."]);
            }

        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => "Something went wrong. Please try again."]);
        }
    }

    public function getWishlistList(Request $request)
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
            
            
           $WishlistData = Wishlist::select('P.product_id','P.name','P.product_image',DB::raw('group_concat(PU.price_unit_id) as price_unit_id'),DB::raw('group_concat(PU.price) as price'), DB::raw('group_concat(PU.unit) as unit'), DB::raw('group_concat(U.unit_name) as unit_name'))
                ->leftjoin('tbl_products as P', 'tbl_wishlist.product_id', 'P.product_id')
                ->leftjoin('tbl_product_price_unit as PU', 'tbl_wishlist.product_id', 'PU.product_id')
                ->leftjoin('tbl_unit as U', 'PU.unit_id', 'U.id')
                ->where('tbl_wishlist.user_id',$user_id)
                ->groupBy('tbl_wishlist.product_id')
                ->orderBy('tbl_wishlist.id','DESC')
				->get();

            if (count($WishlistData) > 0) {
                $Wishlistlist = [];
                foreach($WishlistData as $k => $value){
                    $list['product_id'] = $value['product_id'];
                    $list['name'] = $value['name'];
                    $product_image = explode(',',$value['product_image']);
                    $list['product_image'] = $product_image; 

                    $price_unit_id = explode(',',$value['price_unit_id']);
                    $price = explode(',',$value['price']);
                    $unit = explode(',',$value['unit']);
                    $unit_name = explode(',',$value['unit_name']);
                    $price_unit = [];
                    foreach($price_unit_id as $k => $val){
                        $priceunit['price_unit_id'] = $val;
                        $priceunit['price'] = $price[$k];
                        $priceunit['unit'] = $unit[$k].' '.$unit_name[$k];

                        if($user_id){
                            $CartData =  Cart::where('price_unit_id',$val)->where('user_id',$user_id)->first();	
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
                    // $list['quantity'] = $value['quantity'];
                    $list['price_unit'] = $price_unit;
                    $Wishlistlist[] = $list;             
                }

                return response()->json(['status' => 200, 'message' => "Wishlist Data Get Successfully.", 'data' => $Wishlistlist]);
            } else {
                return response()->json(['status' => 401, 'message' => "No Data Found."]);
            }

        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => "Something went wrong. Please try again."]);
        }
    }

    public function removeProductFromWishlist(Request $request)
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
                'product_id' => 'required'
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $messages = $validator->errors()->all();
                $msg = $messages[0];
                return response()->json(['status' => 401, 'message' => $msg]);
            }

            $product_id = $request->get('product_id');
            
            $CheckProductExist = Common::CheckProductExist($product_id);
            if(empty($CheckProductExist)){
                return response()->json(['status' => 401, 'message' => "Product Not Found."]);
            }

            $wishlistData = Wishlist::where('user_id',$user_id)->where('product_id',$product_id)->first();
            
            if(!empty($wishlistData)){
                $RemoveWishlist =  Wishlist::where('user_id',$user_id)->where('product_id',$product_id)->delete();
                return response()->json(['status' => 200, 'message' => "Product Removed From Cart Successfully."]);

            }else{
                return response()->json(['status' => 200, 'message' => "Product Not Found in Cart."]);
            }

        } catch (\Exception $e) {
            Log::info($e->getMessage() . ', ' . basename((parse_url($e->getFile()))['path']) . '_line: ' . $e->getLine());
            return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => "Something went wrong. Please try again."]);
        }
    }
}
