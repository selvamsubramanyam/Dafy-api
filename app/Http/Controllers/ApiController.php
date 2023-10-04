<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\OTPVerification;
use Illuminate\Support\Facades\Auth;
class ApiController extends Controller
{
    public function register(Request $request){
        $validator = Validator::make($request->all(),
            [
                'name'=>'required',
                'email'=>'required|string|email|max:255|unique:users',
                'mobile' => 'required|numeric|digits:10|unique:users',
                'password'=>'required',                
                'c_password'=>'required|same:password'
            ]
            );

        if ($validator->fails()) {
            return response()->json(['message'=>$validator->messages()],401);
        }

        $data = $request->all();
        $data['mobile'] = $data['mobile'];
        $data['password'] = Hash::make($data['password']);        
        $user = User::create($data);

        $response['token'] = $user->createToken('Dafyapp')->plainTextToken;
        $response['name'] = $user->name;
        return response()->json($response,200);

    }

    public function OtpRegister(Request $request){
        $validator = Validator::make($request->all(),
            [                
                'mobile' => 'required|numeric|digits:10|unique:users'
            ]
            );

        if ($validator->fails()) {
            return response()->json(['message'=>$validator->messages()],401);
        }

        $data = $request->all();  
                   
        $user = User::create($data);

        $response['token'] = $user->createToken('Dafyapp')->plainTextToken;
        $response['user_id'] = $user->id;        
        //return response()->json($response,200);

        # Generate An OTP
        $verificationCode = $this->generateOtp($request->mobile);

        $message = "Your OTP To Login is - ".$verificationCode->otp;
        # Return With OTP 
        return response()->json(['user_id' => $verificationCode->user_id,'message'=>$message,'userType'=>'newuser'],200);

    }
    public function login(Request $request){
        if (Auth::attempt(['email'=>$request->input('email'),'password'=>$request->input('password')] )) {
           $user = Auth::user();
           $response['token'] = $user->createToken('Dafyapp')->plainTextToken;
           $response['name'] = $user->name;           
           return response()->json($response,200);
        }else{
            return response()->json(['message'=>'invalid credentials error'],401);
        }
    }


    public function detail(){
        $user = Auth::user();

        $data = [
            'name'=>$user->name,
            'email'=>$user->email,
            'mobile'=>$user->mobile,
            'age'=>$user->age,
            'gender'=>$user->gender,
            'step_count'=>$user->step_count,
            'tems_conditions'=>$user->tems_conditions,
            //'otp_status'=>$user->otp_status,
        ];
        $response['user'] = $data;
        return response()->json($response,200);
    }

    // Generate OTP
    public function OtpLogin(Request $request)
    {   
        # Validate Data
        $validator = Validator::make($request->all(),
            [
                'mobile' => 'required|numeric|digits:10|exists:users'
            ]
            );

        if ($validator->fails()) {
            $error_message = $validator->messages(); 
            $error_msg = json_decode($error_message);
           
            if($error_msg->mobile[0] == 'The selected mobile is invalid.' && preg_match('/^[0-9]{10}+$/', $request->mobile)){
                return $this->OtpRegister($request);                
            }
            // echo '<pre>';
            // print_r($validator->errors()->all());
            // exit;
            //return response()->json([$validator->messages()],401);
            return response()->json(['message'=>$validator->errors()->all()],401);
        }
        

            # Generate An OTP
            $verificationCode = $this->generateOtp($request->mobile);

            $message = "Your OTP To Login is - ".$verificationCode->otp;
            
            # Return With OTP 
            return response()->json(['user_id' => $verificationCode->user_id,'message'=>$message,'userType'=>'olduser'],200);
            
    }

    public function generateOtp($mobile)
    {
        //echo $mobile;
        $user = User::where('mobile', $mobile)->first();
        // echo '<pre>';
        // print_r($user); 
        // exit;
        # User Does not Have Any Existing OTP
        $verificationCode = OTPVerification::where('user_id', $user->id)->latest()->first();

        $now = Carbon::now();

        if($verificationCode && $now->isBefore($verificationCode->expire_at)){
            return $verificationCode;
        }
        
        // Create a New OTP
        $otpcode = rand(123456, 999999);
        //$this->SendSms($mobile,$otpcode); // Send verification code sms
        return OTPVerification::create([
            'user_id' => $user->id,
            'otp' => $otpcode,
            'expire_at' => Carbon::now()->addMinutes(10)
        ]);
    }    

    public function VerifyLoginOtp(Request $request)
    {
        #Validation
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'otp' => 'required'
        ]);

        #Validation Logic
        $verificationCode   = OTPVerification::where('user_id', $request->user_id)->where('otp', $request->otp)->first();

        $now = Carbon::now();
        if (!$verificationCode) {
            return response()->json(['message'=>'Your Otp is not correct'],401);
        }elseif($verificationCode && $now->isAfter($verificationCode->expire_at)){            
            return response()->json(['message'=>'Your OTP has been expired'],401);
        }

        $user = User::whereId($request->user_id)->first();

        if($user){
            // Expire The OTP
            $verificationCode->update([
                'expire_at' => Carbon::now()
            ]);

            Auth::login($user);
            
            $response['message'] = 'You have loggedin successfully';
            $userdetails['name'] = $user->name;
            $userdetails['email'] = $user->email;
            $userdetails['mobile'] = $user->mobile;
            $response['userdetail'] = $userdetails;
            $response['token'] = $user->createToken('Dafyapp')->plainTextToken;
            return response()->json($response,200);
        }

        return response()->json(['message'=>'Your Otp is not correct'],401);
    }

    public function SendSms($mobile,$otpcode){

            //$userOtp = rand(1000, 9999);
            $userOtp = $otpcode;
            $sms_content = 'Dear customer, '.$userOtp.' is your login OTP for DAFY. Please do not share this OTP with anyone. Happy shopping.';
            $mobil_no = $mobile;
            //$mobil_no = '9940614308';
            $url1 = 'thesmsbuddy.com/api/v1/sms/send?key=YEpXB7CZtP3q0nA1lQJOC75kG94jSlWd&type=1&to='.$mobil_no.'&sender=KLDAFY&message='.urlencode($sms_content).'&flash=0&template_id=1307161520816203526';
            $response = '';
            $ch = curl_init();
            curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url1
            ));
            $response = json_decode(curl_exec($ch));
            // echo '<pre>';
            // print_r($response);

            $result['status'] = $response->status;
            $result['message'] = $response->message;
            curl_close ($ch);
            
            return $result;
    }

    public function updateUserProfile(Request $request){
        $user_id = $request['user_id'];
        $user = user::find($user_id);
        // exit;
        // $user = Auth::user();
        $validator = Validator::make($request->all(),
            [
                'name'=>'required',
                'email'=>'required|string|email|max:255|unique:users,email,'.$user->id,
                'age'=>'required',
                'gender'=>'required',
                //'step_count'=>'required',
                //'tems_conditions'=>'required'
            ]
            );

        if ($validator->fails()) {
            return response()->json(['message'=>$validator->messages()],401);
        }
        
        $user->name = $request['name'];
        $user->email = $request['email'];
        $user->age = $request['age'];
        $user->gender = $request['gender'];
        $user->step_count = $request['step_count'];
        $user->tems_conditions = $request['tems_conditions'];
        $user->save();
        $response['message'] = 'Profile Updated';
        return response()->json($response,200);
    }
}
