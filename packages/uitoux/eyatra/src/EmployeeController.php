<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EmployeeController extends Controller {
	public function employeeList(Request $r) {
		$this->data = [
			'result' => 234,
		];
		return view('eyatra::employees/list', $this->data);
	}

}
