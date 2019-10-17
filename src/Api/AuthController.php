<?php

namespace Uitoux\EYatra\Api;
use App\Http\Controllers\Controller;
// use Mail;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Validator;

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
		]);
		if ($validator->fails()) {
			return response()->json(['success' => false, 'message' => 'Reuired parameters missing', 'errors' => $validator->errors()], $this->successStatus);
		}

		if (Auth::attempt(['mobile_number' => request('username'), 'password' => request('password')])) {

			$user = User::with([
				'employee_details',
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
			$user->otp = generateOtp($user->contact_number);
			$user->save();

			//OTP MAIL SEND
			// $arr = array();
			// $arr['id'] = $user->id;
			// $MailInstance = new ForgetPasswordOTPMail($arr);
			// $Mail = Mail::send($MailInstance);
			// $user['token'] = $user->createToken('eYatra')->accessToken;

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

}
