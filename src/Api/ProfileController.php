<?php

namespace Uitoux\EYatra\Api;
use App\Http\Controllers\Controller;
use App\User;
use Validator;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Uitoux\EYatra\Employee;
use Uitoux\EYatra\VehicleDetails;
use Carbon\Carbon;

class ProfileController extends Controller {
	public $successStatus = 200;

	public function saveImage(Request $request) {
		//dd($request->all());
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
		try{
				$registration_number_exist = VehicleDetails::where('registration_number',$r->registration_number)->pluck('registration_number')->first();
				if(!empty($registration_number_exist)){
					return response()->json(['success' => false, 'errors' => ['Registration Number Already Exists']]);
				}
				$user = User::where('id', $r->user_id)->first();
			    $employee = Employee::where('code',$user->username)->first();

			    $type=VehicleDetails::select('vehicle_type','id')->where('employee_id',$employee->id)->get()->toArray();
			if(count($type) > 1){
				return response()->json(['success' => false, 'errors' => ['The vehicle max limit is two']]); 
			}
	            return app('App\Http\Controllers\AngularController')->verifyVehicleDtails($r->registration_number);
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
	public function saveVehicleDetails(Request $request) {
		//dd($request->all());
		try {
			$error_messages = [
				'registration_number.required' => "Registration Number is Required",
				'registration_number.unique' => "Registration Number is already taken",
				'vehicle_attachment.required' => "Vehicle Attachment is Required",
				'kilometer_attachment.required' => "Kilometer Attachment is Required",
				
			];

			$validator = Validator::make($request->all(), [
				'registration_number' => 'required',
					'registration_number' => 'required|unique:vehicle_details,registration_number,' . $request->id . ',id',
                //'vehicle_attachment' => 'required|mimes:jpeg,jpg|max:1024',
				//'kilometer_attachment' => 'required|mimes:jpeg,jpg|max:1024',
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}
		$user = User::where('id', $request->user_id)->first();
		$employee = Employee::where('code',$user->username)->first();
		if ($user && $employee) {
			DB::beginTransaction();
			if (!$request->id) {
				$vehicle_details = new VehicleDetails;
				$vehicle_details->created_at = Carbon::now();
				$vehicle_details->updated_at = NULL;
			} else {
				$vehicle_details = VehicleDetails::withTrashed()->find($request->id);
				$vehicle_details->updated_at = Carbon::now();
			}
			$vehicle_details->employee_id=$employee->id;
			$vehicle_details->fill($request->all());
            $vehicle_details->insurance_expiry_date=date("Y-m-d", strtotime($request->insurance_expiry_date));
            $vehicle_details->vehicle_make_model = $request->vehicle_make_model;
            $vehicle_details->vehicle_type = $request->vehicle_type;
            $vehicle_details->vehicle_fuel_description = $request->vehicle_fuel_description;
            $vehicle_details->vehicle_maker_description = $request->vehicle_maker_description;
            $vehicle_details->save();

			$vehicle_images = storage_path('app/public/vehicle/');
			Storage::makeDirectory($vehicle_images, 0777);
			if ($request->hasFile('vehicle_attachment')) {
				$value = rand(1, 100);
				$image = $request->vehicle_attachment;
				$extension = $image->getClientOriginalExtension();
				$name = $vehicle_details->id . 'vehicle_attachment' . $value . '.' . $extension;
				$des_path = storage_path('app/public/vehicle/');
				$image->move($des_path, $name);
				$vehicle_details->vehicle_image = $name;
				$vehicle_details->save();
			}

				$kilometer_images = storage_path('app/public/vehicle/');
			   Storage::makeDirectory($kilometer_images, 0777);
			   if ($request->hasFile('kilometer_attachment')) {
				$value = rand(1, 100);
				$image = $request->kilometer_attachment;
				$extension = $image->getClientOriginalExtension();
				$name = $vehicle_details->id . 'kilometer_attachment' . $value . '.' . $extension;
				$des_path = storage_path('app/public/vehicle/');
				$image->move($des_path, $name);
                $vehicle_details->kilometer_image = $name;
				$vehicle_details->save();
			}
			DB::commit();

				// $path = url('') . '/storage/app/public/profile/' . $name;
			return response()->json(['success' => true, 'message' => 'Vehicle Details saved successfully!', 'vehicle_details' => $vehicle_details]);
			
		} else {
			return response()->json(['success' => false, 'message' => 'User Not Found!']);
		}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
}
