<?php

namespace Uitoux\EYatra\Api;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
}
