<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Uitoux\EYatra\Sbu;

class LobController extends Controller {

	public function getLobSbus(Request $request) {
		$this->data['success'] = true;
		$this->data['sbus'] = Sbu::getList($request);
		return response()->json($this->data);
	}

}
