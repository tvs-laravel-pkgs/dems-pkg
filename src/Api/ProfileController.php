<?php

namespace Uitoux\EYatra\Api;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Uitoux\EYatra\Employee;

class ProfileController extends Controller {
	public $successStatus = 200;

	public function saveImage(Request $request) {
		$user = User::where('id', $request->id)->first();
		if ($user) {
			$profile_images = storage_path('app/public/profile/');
			Storage::makeDirectory($profile_images, 0777);
			if ($request->hasFile('image')) {
				$value = rand(1, 100);
				$image = $request->image;
				$extension = $image->getClientOriginalExtension();
				$name = $user->id . 'profile_image' . $value . '.' . $extension;
				$des_path = storage_path('app/public/profile/');
				$image->move($des_path, $name);

				$user->profile_image = $name;
				$user->save();
				// $path = url('') . '/storage/app/public/profile/' . $name;
				return response()->json(['success' => true, 'message' => 'Profile Image saved successfully!', 'path' => $name]);
			}
		} else {
			return response()->json(['success' => false, 'message' => 'User Not Found!']);
		}
	}
	public function getVehicleData(Request $r) {
		return app('App\Http\Controllers\AngularController')->verifyVehicleDtails($r->registration_number);
	}
	public function saveVehicleDetails(Request $request) {
		$user = User::where('id', $request->user_id)->first();
		$employee = Employee::where('code',$user->username)->first();
		if ($user && $employee) {
            $employee->registration_number=$request->registration_number;
            $employee->vehicle_user_name=$request->vehicle_user_name;
            $employee->current_km=$request->current_km;
            $employee->insurance_company=$request->insurance_company;
            $employee->insurance_expiry_date=date("Y-m-d", strtotime($request->insurance_expiry_date));
            $employee->insurance_policy_number=$request->insurance_policy_number;
            $employee->rc_chassis_number=$request->rc_chassis_number;
            $employee->vehicle_make_model=$request->vehicle_make_model;
            $employee->vehicle_type=$request->vehicle_type;
            $employee->vehicle_fuel_description=$request->vehicle_fuel_description;
            $employee->vehicle_maker_description=$request->vehicle_maker_description;
            $employee->save();

			$vehicle_images = storage_path('app/public/vehicle/');
			Storage::makeDirectory($vehicle_images, 0777);
			if ($request->hasFile('vehicle_attachment')) {
				$value = rand(1, 100);
				$image = $request->vehicle_attachment;
				$extension = $image->getClientOriginalExtension();
				$name = $user->id . 'vehicle_attachment' . $value . '.' . $extension;
				$des_path = storage_path('app/public/vehicle/');
				$image->move($des_path, $name);
				$employee->vehicle_image = $name;
				$employee->save();
			}

				$kilometer_images = storage_path('app/public/vehicle/');
			   Storage::makeDirectory($kilometer_images, 0777);
			   if ($request->hasFile('kilometer_attachment')) {
				$value = rand(1, 100);
				$image = $request->kilometer_attachment;
				$extension = $image->getClientOriginalExtension();
				$name = $user->id . 'kilometer_attachment' . $value . '.' . $extension;
				$des_path = storage_path('app/public/vehicle/');
				$image->move($des_path, $name);
                $employee->kilometer_image = $name;
				$employee->save();
			}

				// $path = url('') . '/storage/app/public/profile/' . $name;
			return response()->json(['success' => true, 'message' => 'Vehicle Details saved successfully!', 'employee' => $employee]);
			
		} else {
			return response()->json(['success' => false, 'message' => 'User Not Found!']);
		}
	}
}
