<?php

namespace Uitoux\EYatra\Api;
use App\Http\Controllers\Controller;
// use Mail;
use App\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Validator;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller {
	public $successStatus = 200;

	/**
	 * login api
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function login(Request $request) {
		$validator = Validator::make($request->all(), [
			'username' => 'required|string',
			'password' => 'required|string',
			'device_token' => 'required|string',
		]);

		if ($validator->fails()) {
			return response()->json(['success' => false, 'message' => 'Reuired parameters missing', 'errors' => $validator->errors()], $this->successStatus);
		}

		if (Auth::attempt(['mobile_number' => request('username'), 'password' => request('password')])) {

			//Check Device Token already available or not
			User::where('device_token', request('device_token'))->update(['device_token' => NULL]);

			//Save Device Token
			$user = User::where('id', Auth::user()->id)->update(['device_token' => request('device_token')]);

			//Get User Information
			$user = User::with([
				'employee_details',
				'employee_details.reportingTo',
				'employee_details.reportingTo.user',
				'employee_details.paymentMode',
				'employee_details.grade',
				'employee_details.designation',
				'employee_details.bankDetail',
				'employee_details.chequeDetail',
				'employee_details.walletDetail',
				'employee_details.walletDetail.type',
				'employee_details.sbu',
				'employee_details.sbu.lob',
				'roles',
			])
				->find(Auth::user()->id);
			// $user = Auth::user();

			// $user->employee_details;

			// dd($user);
			// if (!$user->imei) {
			// 	$user->otp = generateOtp($user->mobile);
			// 	$user->imei = request('imei');
			// 	$user->save();
			// 	$user['is_active'] = false;
			// 	$user['otp'] = $user->otp;
			// } else {
			// 	if (!config('app.debug')) {

			// 		if ($user->imei != request('imei')) {
			// 			return response()->json(['status' => 'false', 'error' => 'IMEI number not registered'], $this->successStatus);
			// 		}
			// 	}
			// 	$user['otp'] = "";

			// }
			$user->permissions = $user->permissions($only_mobile = true);
			$user->entity->designation;
			$user->entity->grade;
			$user->entity->outlet->address;
			$user->employee = $user->entity;
			$user['token'] = $user->createToken('eYatra')->accessToken;
			return response()->json(['success' => true, 'user' => $user], $this->successStatus);
		} else {
			return response()->json(['success' => false, 'message' => 'Invalid username/password'], $this->successStatus);
		}
	}

	public function forgotPassword(Request $request) {
		if ($request->mobile == '') {
			return response()->json(['status' => 'false', 'msg' => 'Enter mobile number'], $this->successStatus);
		}

		if ($request->emp_code == '') {
			return response()->json(['status' => 'false', 'msg' => 'Enter Employee Code'], $this->successStatus);
		}

		$user = User::join('employees', 'employees.id', 'users.entity_id')->where('users.mobile_number', $request->input('mobile'))->where('users.user_type_id', 3121)->where('employees.code', $request->input('emp_code'))->select('users.*')->first();

		if ($user) {
			$sender_id = config('custom.sms_sender_id');
			$mobile_number = $user->mobile_number;
			$otp_no = mt_rand(100000, 999999);
			$user->otp = $otp_no;
			$user->save();
			$message = "Your OTP is " . $otp_no . " to reset password in DEMS Application. Please enter OTP to verify your mobile number.";
			sendTxtMsg($user->id, $message, $mobile_number, $sender_id);
			$result = 1;
			$user_id = $user->id;
			return response()->json(['status' => 'true', 'data' => $user], $this->successStatus);
		} else {
			return response()->json(['status' => 'false', 'error' => 'Incorrect mobile number'], $this->successStatus);
		}

	}
	public function changePassword(Request $request) {
		// dump($request->all());
		if ($request->user_id) {
			// $user = User::where('users.entity_id', $request->input('emp_id'))->where('users.user_type_id', 3121)->first();
			$user = User::find($request->user_id);
			// dd($user);
			if ($user) {
				// $user = User::where('users.entity_id', $request->input('emp_id'))->where('users.user_type_id', 3121)->update(array('password' => $request->input('password')));
				$user->password = $request->password;
				$user->save();
				return response()->json(['status' => 'true', 'data' => $user], $this->successStatus);
			} else {
				return response()->json(['status' => 'false', 'error' => 'Invalid user name'], $this->successStatus);
			}
		}

	}

	public function mpinLogin(Request $request) {
		// dd($request->all());
		try {
			$validator = Validator::make($request->all(), [
				'mpin' => [
					'required',
					'string',
					'min:4',
					'max:10',
				],
				'imei' => 'required|min:3|max:16',
				// 'app_type_id' => 'nullable|integer|exists:configs,id',
				// 'company_code' => 'required|string|exists:companies,code',
			]);

			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				], $this->successStatus);
			}

			// $company = Company::where('code', $request->company_code)->first();

			$user = User::where('imei', $request->imei)
				->where('mpin', $request->mpin)
				->where('company_id', 4)
				->first();

			if (!$user) {
				$user = User::where('imei', $request->imei)
					->where('mpin', $request->mpin)
					->first();
			}
			if ($user && $token = JWTAuth::fromUser($user)) {
			//Get User Information
			$user = User::with([
				'employee_details',
				'employee_details.reportingTo',
				'employee_details.reportingTo.user',
				'employee_details.paymentMode',
				'employee_details.grade',
				'employee_details.designation',
				'employee_details.bankDetail',
				'employee_details.chequeDetail',
				'employee_details.walletDetail',
				'employee_details.walletDetail.type',
				'employee_details.sbu',
				'employee_details.sbu.lob',
				'roles',
			])
				->find($user->id);
			$user->permissions = $user->permissions($only_mobile = true);
			$user->entity->designation;
			$user->entity->grade;
			$user->entity->outlet->address;
			$user->employee = $user->entity;
			$user['token'] = $user->createToken('eYatra')->accessToken;
			return response()->json(['success' => true, 'user' => $user], $this->successStatus);
			} else {
				return response()->json([
					'success' => false,
					'errors' => [
						'Invalid credentials',
					],
				], $this->successStatus);
			}

		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Exception Error',
				'errors' => [
					$e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile(),
				],
			], $this->successStatus);
		}
	}

	public function checkMobileNumber(Request $request) {
		$result = 0;
		$user_id = "";
		$cust_save_otp = 0;
		$empl_save_otp = 0;
		$otp_no = null;
		if ($request->mobile == '') {
			return response()->json(['status' => 'false', 'msg' => 'Enter mobile number'], $this->successStatus);
		}
		if ($request->emp_code == '') {
			return response()->json(['status' => 'false', 'msg' => 'Enter Employee Code'], $this->successStatus);
		}
		$user = User::join('employees', 'employees.id', 'users.entity_id')->where('users.mobile_number', $request->input('mobile'))->where('users.user_type_id', 3121)->where('employees.code', $request->input('emp_code'))->select('users.*')->first();
		//$user = User::where('mobile_number', $request->contact_number)->first();
		if ($user) {
			$sender_id = config('custom.sms_sender_id');
			$mobile_number = $user->mobile_number;
			$otp_no = mt_rand(100000, 999999);
			$user->otp = $otp_no;
			$user->save();
			$message = "Your OTP is " . $otp_no . " to reset Mpin in DEMS Application. Please enter OTP to verify your mobile number.";
			sendTxtMsg($user->id, $message, $mobile_number, $sender_id);
			$result = 1;
			$user_id = $user->id;
		}
		return response()->json([
			'user_id' => $user_id,
			'result' => $result,
			'otp'=>$otp_no,
		]);

	}

	public function confirmOTPForm(Request $request) {
		$result = 0;
		$user_id = "";
		$check = 0;
		$user = User::where('id', $request->user_id)->first();
		if ($user) {
			$employee = User::where('id', $user->id)->where('OTP', $request->otp_no)->first();
			$check = $employee ? 1 : 0;
		}
		if ($user && $check) {
			$result = 1;
			$user_id = $user->id;
		}
		return response()->json([
			'user_id' => $user_id,
			'result' => $result,
		]);
	}
	public function setMpinForm(Request $request) {

		if ($request->mpin != $request->confirm_mpin) {
			return redirect()->back()->with(['error' => "Please Enter Same Mpin"]);
		}

		$mpin = Hash::make($request->mpin);
		$user = User::where('id', $request->user_id)->first();

		$user = User::where('id', $request->user_id)->update(['password' => $mpin]);

		if ($user) {
			return redirect()->back()->with(['success' => "Mpin Changed Successfully"]);
		} else {
			return redirect()->back()->with(['error' => "Something went wrong. Please try Again"]);

		}
	}

	public function loginWithOtp(Request $request) {
		try {
			$validator = Validator::make($request->all(), [
				'mobile' => [
					'required',
					'string',
					'min:10',
					'max:10',
				],
			]);

			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				], $this->successStatus);
			}
        $result = 0;
		$user_id = "";
		$cust_save_otp = 0;
		$empl_save_otp = 0;
		$otp_no = null;
		$user = User::where('mobile_number', $request->mobile)->first();
		if ($user) {
			$sender_id = config('custom.sms_sender_id');
			$mobile_number = $user->mobile_number;
			$otp_no = mt_rand(100000, 999999);
			$user->otp = $otp_no;
			$user->save();
			$message = "Your OTP is " . $otp_no . " to reset Mpin in DEMS Application. Please enter OTP to verify your mobile number.";
			sendTxtMsg($user->id, $message, $mobile_number, $sender_id);
			$result = 1;
			$user_id = $user->id;
		return response()->json([
			'user_id' => $user_id,
			'result' => $result,
			'otp_no'=>$otp_no,
		]);
	}else{
		return response()->json([
					'success' => false,
					'errors' => [
						'Invalid Mobile Number',
					],
				], $this->successStatus);
	}
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Exception Error',
				'errors' => [
					$e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile(),
				],
			], $this->successStatus);
		}

	}
	public function confirmOTP(Request $request) {
		$validator = Validator::make($request->all(), [
				'otp_no' => [
					'required',
					'string',
					'min:6',
					'max:6',
				],
				'user_id' => [
					'required',
					'string',
				],
			]);

			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				], $this->successStatus);
			}
		$result = 0;
		$user_id = "";
		$check = 0;
		$user = User::where('id', $request->user_id)->first();
		if ($user) {
			$employee = User::where('id', $user->id)->where('otp', $request->otp_no)->first();
			//dd($employee);
			$check = $employee ? 1 : 0;
		}
		if ($user && $check) {

			//Check Device Token already available or not
			//User::where('device_token', request('device_token'))->update(['device_token' => NULL]);

			//Save Device Token
			//$user = User::where('id', Auth::user()->id)->update(['device_token' => request('device_token')]);

			//Get User Information
			$user = User::with([
				'employee_details',
				'employee_details.reportingTo',
				'employee_details.reportingTo.user',
				'employee_details.paymentMode',
				'employee_details.grade',
				'employee_details.designation',
				'employee_details.bankDetail',
				'employee_details.chequeDetail',
				'employee_details.walletDetail',
				'employee_details.walletDetail.type',
				'employee_details.sbu',
				'employee_details.sbu.lob',
				'roles',
			])
				->find($employee->id);
			$user->permissions = $user->permissions($only_mobile = true);
			$user->entity->designation;
			$user->entity->grade;
			$user->entity->outlet->address;
			$user->employee = $user->entity;
			$user['token'] = $user->createToken('eYatra')->accessToken;
			return response()->json(['success' => true, 'user' => $user], $this->successStatus);
		}
		 else {
				return response()->json([
					'success' => false,
					'errors' => [
						'Invalid credentials',
					],
				], $this->successStatus);
			}
	}

	public function logout(Request $request) {
		// dd($request->all());
		if ($request->user_id) {
			$user = User::find($request->user_id);
			if ($user) {
				$user->device_token = NULL;
				$user->save();
				return response()->json(['status' => 'true'], $this->successStatus);
			} else {
				return response()->json(['status' => 'false', 'error' => 'Invalid user'], $this->successStatus);
			}
		}

	}

}
