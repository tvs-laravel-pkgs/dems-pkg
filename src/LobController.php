<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Uitoux\EYatra\Sbu;

class LobController extends Controller {

	public function getLobSbus(Request $request) {
		if (!empty($request->lob_ids)) {
			$sbu_list = Sbu::getList($request);
		} else {
			$sbu_list = [];
		}
		$this->data['success'] = true;
		$this->data['sbus'] = $sbu_list;
		return response()->json($this->data);
	}

}
