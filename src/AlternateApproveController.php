<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Uitoux\EYatra\AlternateApprove;
use Uitoux\EYatra\Employee;
use Yajra\Datatables\Datatables;

class AlternateApproveController extends Controller {
	public function listAlternateApproveRequest() {
		$alternate_approve = AlternateApprove::select(
			'alternative_approvers.id',
			// 'employees.name as empname',
			'emp_user.name as empname',
			'employees.code as empcode',
			// 'alternateemp.name as altempname',
			'alter_user.name as altempname',
			'alternateemp.code as altempcode',
			'alternative_approvers.type',
			DB::raw('DATE_FORMAT(alternative_approvers.from,"%d-%m-%Y") as fromdate'),
			DB::raw('DATE_FORMAT(alternative_approvers.to,"%d-%m-%Y") as todate'),
			DB::raw('DATEDIFF(alternative_approvers.to , NOW()) as status')
		)
			->join('employees', 'employees.id', 'alternative_approvers.employee_id')
			->join('employees as alternateemp', 'alternateemp.id', 'alternative_approvers.alternate_employee_id')
			->leftJoin('users as emp_user', 'emp_user.entity_id', 'alternative_approvers.id')
			->leftJoin('users as alter_user', 'alter_user.entity_id', 'alternative_approvers.id')
			->where('emp_user.user_type_id', 3121)
			->where('alter_user.user_type_id', 3121)
		// ->where('alternative_approvers.company_id', Auth::user()->company_id)
			->orderBy('alternative_approvers.id', 'desc')
		;
		// dd($alternate_approve);
		return Datatables::of($alternate_approve)
			->addColumn('action', function ($alternate_approve) {

				$img1 = asset('public/img/content/yatra/table/edit.svg');
				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				$img1_active = asset('public/img/content/yatra/table/edit-active.svg');
				$img3 = asset('public/img/content/yatra/table/delete.svg');
				$img3_active = asset('public/img/content/yatra/table/delete-active.svg');
				return '
				<a href="#!/eyatra/alternate-approve/edit/' . $alternate_approve->id . '">
					<img src="' . $img1 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '" >
				</a>
				<a href="javascript:;" data-toggle="modal" data-target="#alternate_approve_id"
				onclick="angular.element(this).scope().deleteAlternateapprove(' . $alternate_approve->id . ')" dusk = "delete-btn" title="Delete">
                <img src="' . $img3 . '" alt="delete" class="img-responsive" onmouseover=this.src="' . $img3_active . '" onmouseout=this.src="' . $img3 . '" >
                </a>';

			})
			->addColumn('status', function ($alternate_approve) {
				if ($alternate_approve->status < 0) {
					return '<span style="color:#ea4335;">Expired</span>';
				} else {
					return '<span style="color:#63ce63;">Valid</span>';
				}
			})
			->make(true);
	}

	public function alternateapproveFormData($alternate_id = NULL) {
		if (!$alternate_id) {
			$alternate_approve = new AlternateApprove;
			$this->data['success'] = true;
			$this->data['message'] = 'Alternate Approve not found';
			$this->data['employee_list'] = [];
			$this->data['employee'] = '';
		} else {
			$this->data['action'] = 'Edit';
			$alternate_approve = AlternateApprove::select('alternative_approvers.*',
				DB::raw('DATE_FORMAT(alternative_approvers.from,"%d-%m-%Y") as fromdate'),
				DB::raw('DATE_FORMAT(alternative_approvers.to,"%d-%m-%Y") as todate'),
				'employees.name as emp_name', 'alt_employee_id.name as alt_emp_name')
				->join('employees', 'employees.id', 'alternative_approvers.employee_id')
				->join('employees as alt_employee_id', 'alt_employee_id.id', 'alternative_approvers.alternate_employee_id')
				->where('alternative_approvers.id', $alternate_id)->first();

			// dd($petty_cash);
			if (!$alternate_id) {
				$this->data['success'] = false;
				$this->data['message'] = 'Alternate Approve not found';
			}
			$this->data['success'] = true;
		}

		$this->data['extras'] = [
			'type' => collect([
				['id' => '', 'name' => 'Select Type'],
				['id' => 120, 'name' => 'Permanent'],
				['id' => 121, 'name' => 'Temporary'],
			]),
		];
		// dd($this->data['extras']);
		$this->data['alternate_approve'] = $alternate_approve;

		return response()->json($this->data);
	}

	public function getmanagerList($searchText) {
		$employee_list = Employee::select('name', 'id', 'code')->where('employees.company_id', Auth::user()->company_id)->where('name', 'LIKE', '%' . $searchText . '%')->orWhere('code', 'LIKE', '%' . $searchText . '%')->get();
		return response()->json(['employee_list' => $employee_list]);
	}

	public function alternateapproveSave(Request $request) {
		// dd($request->all());
		try {
			// $validator = Validator::make($request->all(), [
			// 	'purpose_id' => [
			// 		'required',
			// 	],
			// ]);
			// if ($validator->fails()) {
			// 	return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			// }
			DB::beginTransaction();
			if (!$request->id) {
				$alternate_approve = new AlternateApprove;
				$alternate_approve->created_by = Auth::user()->id;
				$alternate_approve->created_at = Carbon::now();
				$alternate_approve->updated_by = NULL;
			} else {
				$alternate_approve = AlternateApprove::find($request->id);
				$alternate_approve->updated_by = Auth::user()->id;
				$alternate_approve->updated_at = Carbon::now();
			}
			$date = explode(' to ', $request->date);
			$alternate_approve->alternate_employee_id = $request->alt_employee_id;
			$alternate_approve->from = date("Y-m-d", strtotime($date[0]));
			$alternate_approve->to = date("Y-m-d", strtotime($date[1]));
			$alternate_approve->type = $request->type_id;
			$alternate_approve->fill($request->all());
			$alternate_approve->save();
			DB::commit();
			$request->session()->flash('success', 'Alternate Approve List saved successfully!');
			return response()->json(['success' => true]);
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function alternateapproveDelete($alternate_id) {
		$alternate_approve = AlternateApprove::where('id', $alternate_id)->forceDelete();
		if (!$alternate_approve) {
			return response()->json(['success' => false, 'errors' => ['Alternate Approve not found']]);
		}
		return response()->json(['success' => true]);
	}
}
