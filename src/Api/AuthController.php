<?php

namespace Uitoux\EYatra\Api;
use App\Http\Controllers\Controller;
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

			// dd(Auth::user()->id);

			$user = User::with([
				'employee_details',
				'employee_details.grade',
				'employee_details.designation',
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

}
