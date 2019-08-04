<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Uitoux\EYatra\NCity;

class CityController extends Controller {

	public function searchCity(Request $request) {

		$key = $request->key;

		$list = NCity::from('ncities')
			->join('nstates as s', 's.id', 'ncities.state_id')
			->select(
				'ncities.id',
				'ncities.name',
				's.name as state_name'
			)
			->where(function ($q) use ($key) {
				$q->where('ncities.name', 'like', '%' . $key . '%')
				;
			})
			->get();
		return response()->json($list);
	}

}
