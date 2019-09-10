<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Uitoux\EYatra\CoaCode;
use Uitoux\EYatra\Entity;
use Validator;
use Yajra\Datatables\Datatables;

class CoaCodeController extends Controller {

	public function eyatraCoaCodeFilter() {
		$option = new Entity;
		$option->name = 'Select Account Type';
		$option->id = null;
		$this->data['acc_type_list'] = $acc_type_list = Entity::where('entity_type_id', 513)->where('company_id', Auth::user()->company_id)->select('name', 'id')->get()->prepend($option);

		$option = new Entity;
		$option->name = 'Select Group';
		$option->id = null;
		$this->data['group_list'] = $group_list = Entity::where('entity_type_id', 516)->where('company_id', Auth::user()->company_id)->select('name', 'id')->get()->prepend($option);
		$option = new Entity;
		$option->name = 'Select Sub Group';
		$option->id = null;
		$this->data['sub_group_list'] = $sub_group_list = Entity::where('entity_type_id', 517)->where('company_id', Auth::user()->company_id)->select('name', 'id')->get()->prepend($option);
		$this->data['status_list'] = array(
			array('name' => "Select Status", 'id' => null),
			array('name' => "All", 'id' => "-1"),
			array('name' => "Active", 'id' => "2"),
			array('name' => "Inactive", 'id' => "1"),
		);
		return response()->json($this->data);
	}

	public function listEYatraCoaCode(Request $r) {
		if (!empty($r->account_type)) {
			$account_type = $r->account_type;
		} else {
			$account_type = null;
		}
		if (!empty($r->group_id)) {
			$group_id = $r->group_id;
		} else {
			$group_id = null;
		}
		if (!empty($r->sub_group_id)) {
			$sub_group_id = $r->sub_group_id;
		} else {
			$sub_group_id = null;
		}
		if (!empty($r->status)) {
			$status = $r->status;
		} else {
			$status = null;

		}
		$coacodes = CoaCode::withTrashed()
			->join('entities as e', 'e.id', 'coa_codes.account_types')
			->leftjoin('entities as e1', 'e1.id', 'coa_codes.normal_balance')
			->leftjoin('entities as e2', 'e2.id', 'coa_codes.final_statement')
			->leftjoin('entities as e3', 'e3.id', 'coa_codes.group')
			->leftjoin('entities as e4', 'e4.id', 'coa_codes.sub_group')
			->select(
				'coa_codes.id',
				'coa_codes.number',
				'coa_codes.account_description',
				'e.name as account_type',
				'e1.name as normal_balance',
				'coa_codes.description',
				'e2.name as final_statement',
				'e3.name as group',
				'e4.name as sub_group',
				DB::raw('IF(coa_codes.deleted_at IS NULL,"Active","Inactive") as status')
			)
			->where(function ($query) use ($r, $account_type) {
				if (!empty($account_type)) {
					$query->where('e.id', $account_type);
				}
			})
			->where(function ($query) use ($r, $group_id) {
				if (!empty($group_id)) {
					$query->where('e3.id', $group_id);
				}
			})
			->where(function ($query) use ($r, $sub_group_id) {
				if (!empty($sub_group_id)) {
					$query->where('e4.id', $sub_group_id);
				}
			})
			->where(function ($query) use ($r, $status) {
				if ($status == '2') {
					$query->whereNull('coa_codes.deleted_at');
				} elseif ($status == '1') {
					$query->whereNotNull('coa_codes.deleted_at');
				}
			})

			->groupBy('coa_codes.id')
			->orderBy('coa_codes.id', 'asc');

		return Datatables::of($coacodes)
			->addColumn('action', function ($coacode) {

				$img1 = asset('public/img/content/yatra/table/edit.svg');
				$img1_active = asset('public/img/content/yatra/table/edit-active.svg');
				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				$img3 = asset('public/img/content/yatra/table/delete.svg');
				$img3_active = asset('public/img/content/yatra/table/delete-active.svg');
				return '
				<a href="#!/eyatra/coa-code/edit/' . $coacode->id . '">
					<img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '">
				</a>
				<a href="#!/eyatra/coa-code/view/' . $coacode->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>
				<a href="javascript:;" data-toggle="modal" data-target="#delete_coa_code"
				onclick="angular.element(this).scope().deleteCoaCodeConfirm(' . $coacode->id . ')" dusk = "delete-btn" title="Delete">
                <img src="' . $img3 . '" alt="delete" class="img-responsive" onmouseover="this.src="' . $img3_active . '" onmouseout="this.src="' . $img3 . '" >
                </a>';

			})
			->addColumn('status', function ($coacode) {
				if ($coacode->status == 'Inactive') {
					return '<span style="color:#ea4335;">Inactive</span>';
				} else {
					return '<span style="color:#63ce63;">Active</span>';
				}

			})
			->make(true);
	}

	public function eyatraCoaCodeFormData($coa_code_id = NULL) {
		if (!$coa_code_id) {
			$this->data['action'] = 'Add';
			$coacode = new CoaCode;
			$this->data['status'] = 'Active';

			$this->data['success'] = true;
		} else {
			$this->data['action'] = 'Edit';
			$coacode = CoaCode::withTrashed()->find($coa_code_id);

			if (!$coacode) {
				$this->data['success'] = false;
				$this->data['message'] = 'Coa Code not found';
			}

			if ($coacode->deleted_at == NULL) {
				$this->data['status'] = 'Active';
			} else {
				$this->data['status'] = 'Inactive';
			}
		}
		$type_list = collect(Entity::accountTypeList())->prepend(['id' => '', 'name' => 'Select Account Type']);
		$normal_balance_list = collect(Entity::normalBalanceList())->prepend(['id' => '', 'name' => 'Select Normal Balance']);
		$statement_list = collect(Entity::finalStatementList())->prepend(['id' => '', 'name' => 'Select Final Statement']);
		$group_list = collect(Entity::accGroupList())->prepend(['id' => '', 'name' => 'Select Group']);
		$sub_group_list = collect(Entity::subGroupList())->prepend(['id' => '', 'name' => 'Select Sub Group']);

		$this->data['extras'] = [
			'type_list' => $type_list,
			'normal_balance_list' => $normal_balance_list,
			'statement_list' => $statement_list,
			'group_list' => $group_list,
			'sub_group_list' => $sub_group_list,
		];

		$this->data['coacode'] = $coacode;
		$this->data['success'] = true;

		return response()->json($this->data);
	}

	public function saveEYatraCoaCode(Request $request) {
		//validation
		//dd($request->all());
		try {
			$error_messages = [
				'number.required' => 'Number is required',
				'account_description.required' => 'Account Description is required',
				'account_types.required' => 'Account Type is required',
				'normal_balance.unique' => 'Normal Balance is required',
				'description.unique' => 'Description is required',
				'final_statement.unique' => 'Final Statement is required',
				'group.unique' => 'Group is required',
				'sub_group.unique' => 'Sub Group is required',

			];

			$validator = Validator::make($request->all(), [
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$coacode = new CoaCode;
				$coacode->created_by = Auth::user()->id;
				$coacode->created_at = Carbon::now();
				$coacode->updated_at = NULL;

			} else {
				$coacode = CoaCode::withTrashed()->where('id', $request->id)->first();

				$coacode->updated_by = Auth::user()->id;
				$coacode->updated_at = Carbon::now();

			}
			if ($request->status == 'Active') {
				$coacode->deleted_at = NULL;
				$coacode->deleted_by = NULL;
			} else {
				$coacode->deleted_at = date('Y-m-d H:i:s');
				$coacode->deleted_by = Auth::user()->id;

			}

			$coacode->fill($request->all());
			$coacode->save();
			$activity['entity_id'] = $coacode->id;
			$activity['entity_type'] = 'COA Codes';
			$activity['details'] = empty($request->id) ? "COA Codes Added" : "COA Codes updated";
			$activity['activity'] = empty($request->id) ? "Add" : "Edit";
			$activity_log = ActivityLog::saveLog($activity);
			DB::commit();
			$request->session()->flash('success', 'Coa Code saved successfully!');
			if (empty($request->id)) {
				return response()->json(['success' => true, 'message' => 'Coa Code Added successfully']);
			} else {
				return response()->json(['success' => true, 'message' => 'Coa Code Updated Successfully']);
			}
			return response()->json(['success' => true]);
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function viewEYatraCoaCode($coa_code_id) {
		$coacode = CoaCode::with([
			'accountType',
			'normalBalance',
			'finalStatement',
			'coaGroup',
			'subGroup',
		])->select('*', DB::raw('IF(coa_codes.deleted_at IS NULL,"Active","Inactive") as status'))
			->withTrashed()
			->find($coa_code_id);
		// dd($coacode);
		$this->data['action'] = 'View';
		if (!$coacode) {
			$this->data['success'] = false;
			$this->data['errors'] = ['Coa Code not found'];
			return response()->json($this->data);
		}
		$this->data['coacode'] = $coacode;
		$this->data['success'] = true;
		return response()->json($this->data);
	}

	public function deleteEYatraCoaCode($coa_code_id) {
		$coacode = CoaCode::withTrashed()->where('id', $coa_code_id)->first();
		$activity['entity_id'] = $coacode->id;
		$activity['entity_type'] = 'COA Codes';
		$activity['details'] = "COA Codes deleted";
		$activity['activity'] = "delete";
		$activity_log = ActivityLog::saveLog($activity);
		$coacode->forceDelete();
		if (!$coacode) {
			return response()->json(['success' => false, 'errors' => ['Coa Code not found']]);
		}
		return response()->json(['success' => true]);
	}

}
