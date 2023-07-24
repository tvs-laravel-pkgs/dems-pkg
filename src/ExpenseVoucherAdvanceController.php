<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Auth;
use DB;
use Illuminate\Http\Request;
use Storage;
use Uitoux\EYatra\Employee;
use Uitoux\EYatra\ExpenseVoucherAdvanceRequest;
use Uitoux\EYatra\ExpenseVoucherAdvanceRequestClaim;
use Uitoux\EYatra\CoaCode;
use Validator;
use App\FinancialYear;
use App\SerialNumberGroup;
use Yajra\Datatables\Datatables;

class ExpenseVoucherAdvanceController extends Controller {
	public function listExpenseVoucherRequest(Request $r) {
		//dd($r->all());
		$expense_voucher_requests = ExpenseVoucherAdvanceRequest::select(
			'expense_voucher_advance_requests.id',
			'expense_voucher_advance_requests.number as advance_pcv_number',
			'users.name as ename',
			'employees.code as ecode',
			'expense_voucher_advance_requests.expense_amount',
			DB::raw('DATE_FORMAT(expense_voucher_advance_requests.date,"%d-%m-%Y") as date'),
			'expense_voucher_advance_requests.advance_amount as advance_amount',
			DB::raw('IF(expense_voucher_advance_request_claims.balance_amount IS NULL,"--",expense_voucher_advance_request_claims.balance_amount) as balance_amount'),
			'expense_voucher_advance_requests.status_id as status_id',
			'configs.name as status',
			'expense_voucher_advance_request_claims.employee_return_payment_mode_id',
			// 'expense_voucher_advance_request_claims.number as advance_pcv_claim_number',
			DB::raw('IF(expense_voucher_advance_request_claims.number IS NULL,"--",expense_voucher_advance_request_claims.number) as advance_pcv_claim_number'),
			DB::raw('IF(advance_pcv_claim_statuses.name IS NULL,"--",advance_pcv_claim_statuses.name) as advance_pcv_claim_status'),			
			'expense_voucher_advance_request_claims.status_id as advance_pcv_claim_status_id'
		)
			->leftJoin('configs', 'configs.id', 'expense_voucher_advance_requests.status_id')
			->join('employees', 'employees.id', 'expense_voucher_advance_requests.employee_id')
			->leftjoin('expense_voucher_advance_request_claims', 'expense_voucher_advance_request_claims.expense_voucher_advance_request_id', 'expense_voucher_advance_requests.id')
			->leftJoin('configs as advance_pcv_claim_statuses', 'advance_pcv_claim_statuses.id', 'expense_voucher_advance_request_claims.status_id')
			->join('users', 'users.entity_id', 'employees.id')
			->where('users.user_type_id', 3121)
			->where('employees.id', Auth::user()->entity_id)
			->orderBy('expense_voucher_advance_requests.id', 'desc')
			->groupBy('expense_voucher_advance_requests.id')
			->where(function ($query) use ($r) {
				if (!empty($r->status_id)) {
					$query->where('configs.id', $r->status_id);
				}
			})
			->where(function ($query) use ($r) {
				if (!empty($r->created_date)) {
					$query->where('expense_voucher_advance_requests.date', date("Y-m-d", strtotime($r->created_date)));
				}
			})
		;

		return Datatables::of($expense_voucher_requests)
			->addColumn('action', function ($expense_voucher_requests) {
				$img1 = asset('public/img/content/yatra/table/edit.svg');
				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				$img1_active = asset('public/img/content/yatra/table/edit-active.svg');
				$img3 = asset('public/img/content/yatra/table/delete.svg');
				$img3_active = asset('public/img/content/yatra/table/delete-active.svg');
				$payment_detail_icon = asset('public/img/content/yatra/table/payment-detail.svg');
				if ($expense_voucher_requests->status_id == 3460 || $expense_voucher_requests->status_id == 3463 || $expense_voucher_requests->status_id == 3465) {
					return '
				<a href="#!/expense/voucher-advance/edit/' . $expense_voucher_requests->id . '">
					<img src="' . $img1 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '" >
				</a>
				<a href="#!/expense/voucher-advance/view/' . $expense_voucher_requests->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>
				<a href="javascript:;" data-toggle="modal" data-target="#expense_voucher_confirm_box"
				onclick="angular.element(this).scope().deleteExpenseVoucher(' . $expense_voucher_requests->id . ')" dusk = "delete-btn" title="Delete">
                <img src="' . $img3 . '" alt="delete" class="img-responsive" onmouseover=this.src="' . $img3_active . '" onmouseout=this.src="' . $img3 . '" >
                </a>';
				// } elseif ($expense_voucher_requests->status_id == 3464 || $expense_voucher_requests->status_id == 3466 || $expense_voucher_requests->status_id == 3469 || $expense_voucher_requests->status_id == 3471) {
				} elseif ($expense_voucher_requests->status_id == 3464 && (!$expense_voucher_requests->advance_pcv_claim_status_id || $expense_voucher_requests->advance_pcv_claim_status_id == 3466 || $expense_voucher_requests->advance_pcv_claim_status_id == 3469 || $expense_voucher_requests->advance_pcv_claim_status_id == 3471)) {
					$action = '
				<a href="#!/expense/voucher-advance/edit/' . $expense_voucher_requests->id . '">
					<img src="' . $img1 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '" >
				</a>
				<a href="#!/expense/voucher-advance/view/' . $expense_voucher_requests->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>';
					return $action;
				} else {
					
				// 	return '<a href="#!/expense/voucher-advance/view/' . $expense_voucher_requests->id . '">
				// 	<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				// </a>';
					$action = '';
					$action .= '<a href="#!/expense/voucher-advance/view/' . $expense_voucher_requests->id . '">
				 	<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" ></a>';

					if(($expense_voucher_requests->employee_return_payment_mode_id == 4010 || $expense_voucher_requests->employee_return_payment_mode_id == 4011) && ($expense_voucher_requests->advance_pcv_claim_status_id == 3470)){
						$action .= '<a type="button" onclick="angular.element(this).scope().employeeReturnPaymentUpdateHandler(' . $expense_voucher_requests->id . ')"><img src="' . $payment_detail_icon . '" title="Employee Return Payment Detail" alt="Employee Return Payment Detail" class="img-responsive" onmouseover=this.src="' . $payment_detail_icon . '" onmouseout=this.src="' . $payment_detail_icon . '"></a> ';
					}
					return $action;
				}

			})
			->make(true);
	}

	public function expenseVoucherFormData($id = NULL) {
		if (!$id) {
			$this->data['action'] = 'Add';
			$expense_voucher_advance = new ExpenseVoucherAdvanceRequest();
			$expense_voucher_advance_claim = new ExpenseVoucherAdvanceRequestClaim();
			$this->data['success'] = true;
			$this->data['message'] = 'Expense Voucher not found';
			$this->data['employee_list'] = [];
			$this->data['employee'] = '';
		} else {
			$this->data['action'] = 'Edit';
			$expense_voucher_advance = ExpenseVoucherAdvanceRequest::with([
				'employee',
				'employee.user',
			])
				->where('id', $id)
				->first();

			$expense_voucher_advance_claim = ExpenseVoucherAdvanceRequestClaim::where('expense_voucher_advance_request_id', $id)->first();
			$expense_voucher_advance->expense_amount = null;
			$expense_voucher_advance->expense_description = null;
			$expense_voucher_advance->employee_return_payment_mode_id = null;
			if($expense_voucher_advance_claim){
				$expense_voucher_advance->expense_amount = $expense_voucher_advance_claim->expense_amount;
				$expense_voucher_advance->expense_description = $expense_voucher_advance_claim->description;
				$expense_voucher_advance->employee_return_payment_mode_id = $expense_voucher_advance_claim->employee_return_payment_mode_id;
			}
			$this->data['success'] = true;
		}

		$employee_details = Employee::join('users', 'users.entity_id', 'employees.id')
			->where('users.user_type_id', 3121)
			->where('users.company_id', Auth::user()->company_id)
			->where('employees.id', Auth::user()->entity_id)
			->select('employees.id')->first();
		$this->data['employee_details'] = $employee_details;
		$this->data['expense_voucher_advance'] = $expense_voucher_advance;
		$this->data['expense_voucher_advance_claim'] = $expense_voucher_advance_claim;

		$expense_voucher_advance_attachment = Attachment::where('attachment_of_id', 3442)->where('entity_id', $expense_voucher_advance->id)->select('name', 'id')->get();
		$expense_voucher_advance->attachments = $expense_voucher_advance_attachment;
		$this->data['employee_return_payment_mode_list'] = Config::select('name', 'id')->where('config_type_id', 569)->orderBy('id', 'asc')->get();
		$this->data['employee_return_balance_cash_limit'] = Config::where('id', 4036)->first()->name;

		return response()->json($this->data);
	}

	public function ExpenseVoucherAdvanceFilterData() {
		//$list_of_status = array_merge(Config::ExpenseVoucherAdvanceStatus(), Config::ExpenseVoucherAdvanceStatusList());
		$this->data['status_list'] = $status_list = collect(Config::ExpenseVoucherAdvanceStatus())->prepend(['id' => '', 'name' => 'Select Status']);
		$this->data['employee_list'] = collect(Employee::getEmployeeListBasedCompany())->prepend(['id' => '', 'name' => 'Select Employee']);
		$this->data['outlet_list'] = collect(Outlet::getOutletList())->prepend(['id' => '', 'name' => 'Select Outlet']);
		$this->data['employee_return_payment_bank_list'] = Config::where('config_type_id', 570)->select('name','id')->get();

		return response()->json($this->data);
	}
	public function getemployee($searchText) {
		$employee_list = Employee::select('name', 'id', 'code')->where('employees.company_id', Auth::user()->company_id)->where('name', 'LIKE', '%' . $searchText . '%')->orWhere('code', 'LIKE', '%' . $searchText . '%')->get();
		return response()->json(['employee_list' => $employee_list]);
	}

	public function expenseVoucherView($id) {
		// $this->data['expense_voucher_view'] = $expense_voucher_view = ExpenseVoucherAdvanceRequest::select(
		// 	'employees.code',
		// 	'users.name',
		// 	'expense_voucher_advance_requests.id',
		// 	'expense_voucher_advance_requests.number as advance_pcv_number',
		// 	'expense_voucher_advance_requests.date',
		// 	'expense_voucher_advance_requests.advance_amount',
		// 	// 'expense_voucher_advance_requests.expense_amount',
		// 	'expense_voucher_advance_request_claims.number as advance_pcv_claim_number',
		// 	'expense_voucher_advance_request_claims.expense_amount',
		// 	// 'expense_voucher_advance_requests.balance_amount',
		// 	'expense_voucher_advance_request_claims.balance_amount',
		// 	'advance_pcv_claim_statuses.name as advance_pcv_claim_status',
		// 	'expense_voucher_advance_requests.description',
		// 	'expense_voucher_advance_requests.expense_description',
		// 	'configs.name as status'
		// )
		// 	->leftJoin('employees', 'employees.id', 'expense_voucher_advance_requests.employee_id')
		// 	->leftJoin('users', 'users.entity_id', 'employees.id')
		// 	->leftJoin('configs', 'configs.id', 'expense_voucher_advance_requests.status_id')
		// 	->leftJoin('expense_voucher_advance_request_claims', 'expense_voucher_advance_request_claims.expense_voucher_advance_request_id', 'expense_voucher_advance_requests.id')
		// 	->leftJoin('configs as advance_pcv_claim_statuses', 'advance_pcv_claim_statuses.id', 'expense_voucher_advance_request_claims.status_id')
		// 	->where('users.user_type_id', 3121)
		// 	->where('expense_voucher_advance_requests.id', $id)
		// 	->first();
		$this->data['expense_voucher_view'] = $expense_voucher_view = ExpenseVoucherAdvanceRequest::getExpenseVoucherAdvanceRequestData($id);

		$expense_voucher_advance_attachment = Attachment::where('attachment_of_id', 3442)->where('entity_id', $expense_voucher_view->id)->select('name', 'id')->get();
		$expense_voucher_view->attachments = $expense_voucher_advance_attachment;
		return response()->json($this->data);

	}
	public function expenseVoucherSingleRepaidApprove(Request $request){
		//dd($request->all());
		DB::beginTransaction();
		try {
			if ($request->id) {
				$employee_expense_voucher_id = $request->id;
			} else {
				return back()->with('error', 'Expense Voucher Advance not found');
			}
			$expense_voucher_advance = ExpenseVoucherAdvanceRequest::where('id', $employee_expense_voucher_id)->update(['status_id' => 3275]);
			if($expense_voucher_advance){
				//Approval Log Save
				ApprovalLog::saveApprovalLog(3585, $employee_expense_voucher_id, 3258, Auth::user()->entity_id, Carbon::now());
			}
			DB::commit();
			return response()->json(['success' => true]);
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Error_Message' => $e->getMessage()]]);
		}

	}

	//OLD 18 JULY 2023
	// public function expenseVoucherSave(Request $request) {
	// 	// dd($request->all());
	// 	try {
	// 		$validator = Validator::make($request->all(), [
	// 			'employee_id' => [
	// 				'required',
	// 			],
	// 			'date' => [
	// 				'required',
	// 			],
	// 			'advance_amount' => [
	// 				'required',
	// 			],
	// 			'description' => [
	// 				'required',
	// 			],
	// 			'expense_amount' => [
	// 				'nullable',
	// 			],
	// 			'expense_description' => [
	// 				'nullable',
	// 			],
	// 		]);
	// 		if ($validator->fails()) {
	// 			return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
	// 		}
	// 		DB::beginTransaction();
	// 		$employee_cash_check = Employee::select(
	// 			'outlets.expense_voucher_limit'
	// 		)
	// 			->join('outlets', 'outlets.id', 'employees.outlet_id')
	// 			->where('employees.id', $request->employee_id)->first();

	// 			//CHECK VALIDATION FOR MAXIMUM ELEGIBILITY AMOUNT LIMIT
	// 		if ($request->advance_amount > $employee_cash_check->expense_voucher_limit) {
	// 			return response()->json(['success' => false, 'errors' => ['The maximum amount limit is ' . $employee_cash_check->expense_voucher_limit]]);
	// 		}
	// 		if (!empty($request->expense_voucher_attach_removal_ids)) {
	// 			$attachment_remove = json_decode($request->expense_voucher_attach_removal_ids, true);
	// 			Attachment::whereIn('id', $attachment_remove)->where('attachment_of_id', 3442)->delete();
	// 		}

	// 		if ($request->id) {
	// 			if ($request->expense_amount) {
	// 				$expense_voucher_advance = ExpenseVoucherAdvanceRequest::findOrFail($request->id);
	// 				$expense_voucher_advance->updated_by = Auth::user()->id;
	// 				$expense_voucher_advance->status_id = 3466;
	// 			} else {
	// 				$expense_voucher_advance = ExpenseVoucherAdvanceRequest::findOrFail($request->id);
	// 				$expense_voucher_advance->updated_by = Auth::user()->id;
	// 				$expense_voucher_advance->status_id = 3460;
	// 			}
	// 		} else {
	// 			$expense_voucher_advance = new ExpenseVoucherAdvanceRequest;
	// 			$expense_voucher_advance->created_by = Auth::user()->id;
	// 			$expense_voucher_advance->status_id = 3460;
	// 		}

	// 		$expense_voucher_advance->fill($request->all());
	// 		$balence_amount = $request->advance_amount - $request->expense_amount;
	// 		if ($request->expense_amount) {
	// 			if ($balence_amount) {
	// 				$expense_voucher_advance->balance_amount = $balence_amount;
	// 			} else {
	// 				$expense_voucher_advance->balance_amount = NULL;
	// 			}
	// 		} else {
	// 			$expense_voucher_advance->balance_amount = NULL;
	// 		}
	// 		if (isset($request->description)) {
	// 			$expense_voucher_advance->description = $request->description;
	// 		}
	// 		if (isset($request->expense_description)) {
	// 			$expense_voucher_advance->expense_description = $request->expense_description;
	// 		}
	// 		$expense_voucher_advance->save();
	// 		//STORE ATTACHMENT
	// 		$item_images = storage_path('expense-voucher-advance/attachments/');
	// 		Storage::makeDirectory($item_images, 0777);
	// 		if (isset($request->attachments)) {
	// 			foreach ($request->attachments as $key => $attachement) {
	// 				if (!empty($attachement)) {
	// 					// $name = $attachement->getClientOriginalName();
	// 					$random_file_name = $expense_voucher_advance->id . '_Expence_Voucher_Advance_File_' . rand(1, 1000) . '.';
	// 					$extension = $attachement->getClientOriginalExtension();
	// 					$attachement->move(storage_path('app/public/expense-voucher-advance/attachments/'), $random_file_name . $extension);
	// 					$attachement_expense_voucher_advance = new Attachment;
	// 					$attachement_expense_voucher_advance->attachment_of_id = 3442;
	// 					$attachement_expense_voucher_advance->attachment_type_id = 3200;
	// 					$attachement_expense_voucher_advance->entity_id = $expense_voucher_advance->id;
	// 					$attachement_expense_voucher_advance->name = $random_file_name . $extension;
	// 					$attachement_expense_voucher_advance->save();
	// 				}
	// 			}
	// 		}

	// 		DB::commit();
	// 		if ($request->id) {
	// 			return response()->json(['success' => true, 'message' => 'Expense voucher advance updated successfully']);
	// 		} else {
	// 			return response()->json(['success' => true, 'message' => 'Expense voucher advance saved successfully']);
	// 		}
	// 		// $request->session()->flash('success', 'Expense voucher advance saved successfully!');
	// 		// return response()->json(['success' => true]);
	// 	} catch (Exception $e) {
	// 		DB::rollBack();
	// 		return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
	// 	}
	// }

	public function expenseVoucherSave(Request $request) {
		// dd($request->all());
		try {
			$validator = Validator::make($request->all(), [
				'employee_id' => [
					'required',
					'exists:employees,id',
				],
				'date' => [
					'required',
				],
				'advance_amount' => [
					'required',
				],
				'description' => [
					'required',
				],
				'expense_amount' => [
					'nullable',
				],
				'expense_description' => [
					'nullable',
				],
			]);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}
			DB::beginTransaction();
			$employee_cash_check = Employee::select(
				'outlets.expense_voucher_limit'
			)
				->join('outlets', 'outlets.id', 'employees.outlet_id')
				->where('employees.id', $request->employee_id)->first();

				//CHECK VALIDATION FOR MAXIMUM ELEGIBILITY AMOUNT LIMIT
			if ($request->advance_amount > $employee_cash_check->expense_voucher_limit) {
				return response()->json(['success' => false, 'errors' => ['The maximum amount limit is ' . $employee_cash_check->expense_voucher_limit]]);
			}
			if (!empty($request->expense_voucher_attach_removal_ids)) {
				$attachment_remove = json_decode($request->expense_voucher_attach_removal_ids, true);
				Attachment::whereIn('id', $attachment_remove)->where('attachment_of_id', 3442)->delete();
			}

			if ($request->id) {
				$expense_voucher_advance = ExpenseVoucherAdvanceRequest::findOrFail($request->id);
				if(!$expense_voucher_advance){
					return response()->json([
	                    'success' => false,
	                    'error' => 'Validation Error',
	                    'errors' => ['Expense voucher advance data not Found'],
	                ]);
				}
				if ($request->expense_amount) {
					//EXPENSE ADVANCE RFEQUEST CLAIM
					$expense_voucher_advance_request_claim = ExpenseVoucherAdvanceRequestClaim::firstOrNew(['expense_voucher_advance_request_id' => $expense_voucher_advance->id]);
					$expense_voucher_advance_request_claim->company_id = Auth::user()->company_id;
					if(!$expense_voucher_advance_request_claim->number){
						$outlet_id = !empty(Auth::user()->entity->outlet_id) ? Auth::user()->entity->outlet_id : null;
						if (!$outlet_id) {
							return response()->json([
								'success' => false,
								'errors' => 'Outlet not found!'
							]);
						}
			            $financial_year = getFinancialYear();
						$financial_year_id = FinancialYear::where('from', $financial_year)
							->where('company_id', Auth::user()->company_id)
							->pluck('id')
							->first();
						if (!$financial_year_id) {
							return response()->json([
			                    'success' => false,
			                    'error' => 'Validation Error',
			                    'errors' => [
			                        'Financial Year Not Found',
			                    ],
			                ]);
						}

			            $generate_number = SerialNumberGroup::generateNumber(7, $financial_year_id, $outlet_id);
			            if (!$generate_number['success']) {
			                return response()->json([
			                    'success' => false,
			                    'error' => 'Validation Error',
			                    'errors' => [
			                        'No Adance PCV Claim Serial number found for FY : ' . $financial_year->from,
			                    ],
			                ]);
			            }

			            $error_messages = [
			                'number.required' => 'Serial number is required',
			                'number.unique' => 'Serial number is already taken',
			            ];

			            $validator = Validator::make($generate_number, [
			                'number' => [
			                    'required',
			                    'unique:expense_voucher_advance_request_claims,number',
			                ],
			            ], $error_messages);

			            if ($validator->fails()) {
			                return response()->json([
			                    'success' => false,
			                    'error' => 'Validation Error',
			                    'errors' => $validator->errors()->all(),
			                ]);
			            }
						$expense_voucher_advance_request_claim->number = $generate_number['number'];
					}

					$balance_amount = floatval($request->advance_amount) - floatval($request->expense_amount);
					$expense_voucher_advance_request_claim->expense_amount = $request->expense_amount;
					$expense_voucher_advance_request_claim->balance_amount = $balance_amount;
					$expense_voucher_advance_request_claim->description = $request->expense_description;
					$expense_voucher_advance_request_claim->status_id = 3466; //Manager Approval Pending
					if($expense_voucher_advance_request_claim->exists){
	                    $expense_voucher_advance_request_claim->updated_by_id = Auth()->user()->id;
	                    $expense_voucher_advance_request_claim->updated_at = Carbon::now();
	                }else{
	                    $expense_voucher_advance_request_claim->created_by_id = Auth()->user()->id;
	                    $expense_voucher_advance_request_claim->created_at = Carbon::now();
	                }
	                
	                $expense_voucher_advance_request_claim->employee_return_payment_mode_id = isset($request->employee_return_payment_mode_id) ? $request->employee_return_payment_mode_id : null;
					$expense_voucher_advance_request_claim->save();
				} else {
					$expense_voucher_advance->updated_by = Auth::user()->id;
					$expense_voucher_advance->status_id = 3460; //Waiting for Manager Approval
				}
			} else {
				$outlet_id = !empty(Auth::user()->entity->outlet_id) ? Auth::user()->entity->outlet_id : null;
				if (!$outlet_id) {
					return response()->json([
						'success' => false,
						'errors' => 'Outlet not found!'
					]);
				}
	            $financial_year = getFinancialYear();
				$financial_year_id = FinancialYear::where('from', $financial_year)
					->where('company_id', Auth::user()->company_id)
					->pluck('id')
					->first();
				if (!$financial_year_id) {
					return response()->json([
	                    'success' => false,
	                    'error' => 'Validation Error',
	                    'errors' => [
	                        'Financial Year Not Found',
	                    ],
	                ]);
				}

	            $generate_number = SerialNumberGroup::generateNumber(5, $financial_year_id, $outlet_id);
	            if (!$generate_number['success']) {
	                return response()->json([
	                    'success' => false,
	                    'error' => 'Validation Error',
	                    'errors' => [
	                        'No Adance PCV Serial number found for FY : ' . $financial_year->from,
	                    ],
	                ]);
	            }

	            $error_messages = [
	                'number.required' => 'Serial number is required',
	                'number.unique' => 'Serial number is already taken',
	            ];

	            $validator = Validator::make($generate_number, [
	                'number' => [
	                    'required',
	                    'unique:expense_voucher_advance_requests,number',
	                ],
	            ], $error_messages);

	            if ($validator->fails()) {
	                return response()->json([
	                    'success' => false,
	                    'error' => 'Validation Error',
	                    'errors' => $validator->errors()->all(),
	                ]);
	            }

	            $advance_pcv_natural_account_code = Config::where('id', 4031)->first()->name;
	            $coa_code_id = CoaCode::where('oracle_code', $advance_pcv_natural_account_code)
	            	->pluck('id')
	            	->first();
				$expense_voucher_advance = new ExpenseVoucherAdvanceRequest;
				$expense_voucher_advance->company_id = Auth::user()->company_id;
				$expense_voucher_advance->number = $generate_number['number'];
				$expense_voucher_advance->created_by = Auth::user()->id;
				$expense_voucher_advance->coa_code_id = $coa_code_id;
				$expense_voucher_advance->status_id = 3460;//Waiting for Manager Approval
			}

			$expense_voucher_advance->fill($request->all());
			if (isset($request->description)) {
				$expense_voucher_advance->description = $request->description;
			}
			$expense_voucher_advance->save();

			//STORE ATTACHMENT
			$item_images = storage_path('expense-voucher-advance/attachments/');
			Storage::makeDirectory($item_images, 0777);
			if (isset($request->attachments)) {
				foreach ($request->attachments as $key => $attachement) {
					if (!empty($attachement)) {
						// $name = $attachement->getClientOriginalName();
						$random_file_name = $expense_voucher_advance->id . '_Expence_Voucher_Advance_File_' . rand(1, 1000) . '.';
						$extension = $attachement->getClientOriginalExtension();
						$attachement->move(storage_path('app/public/expense-voucher-advance/attachments/'), $random_file_name . $extension);
						$attachement_expense_voucher_advance = new Attachment;
						$attachement_expense_voucher_advance->attachment_of_id = 3442;
						$attachement_expense_voucher_advance->attachment_type_id = 3200;
						$attachement_expense_voucher_advance->entity_id = $expense_voucher_advance->id;
						$attachement_expense_voucher_advance->name = $random_file_name . $extension;
						$attachement_expense_voucher_advance->save();
					}
				}
			}

			DB::commit();
			if ($request->id) {
				return response()->json(['success' => true, 'message' => 'Expense voucher advance updated successfully']);
			} else {
				return response()->json(['success' => true, 'message' => 'Expense voucher advance saved successfully']);
			}
		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json([
				'success' => false,
				'errors' => ['Exception Error' => $e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile()]
			]);
		}
	}

	public function expenseVoucherDelete($id) {
		// dd($type_id, $pettycash_id);
		$expense_voucher = ExpenseVoucherAdvanceRequest::where('id', $id)->forceDelete();
		if (!$expense_voucher) {
			return response()->json(['success' => false, 'errors' => ['Expense Voucher not found']]);
		}
		return response()->json(['success' => true]);
	}


	public function expenseVoucherGetData($expense_voucher_id) {
		$data = [];
		$data['expense_voucher_advance'] = ExpenseVoucherAdvanceRequest::find($expense_voucher_id);
		if (!$data['expense_voucher_advance']) {
			return response()->json([
				'success' => false,
				'errors' => ["Expense voucher advance request detail not found"],
			]);
		}

		$data['expense_voucher_advance_request_claim'] = ExpenseVoucherAdvanceRequestClaim::with([
			'employeeReturnPaymentMode',
			'employeeReturnPaymentBank',
		])
			->where('expense_voucher_advance_request_id', $expense_voucher_id)
			->first();
		return response()->json([
			'success' => true,
			'data' => $data,
		]);
	}

	public function employeeReturnPaymentSave(Request $request){
        // dd($request->all());
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'expense_voucher_advance_request_claim_id' => [
                    'required',
                    'exists:expense_voucher_advance_request_claims,id',
                ],
                'employee_return_payment_mode_id' => [
                    'required',
                    'exists:configs,id',
                ],
                'employee_return_payment_bank_id' => [
                    'nullable',
                    'exists:configs,id',
                ],
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => $validator->errors()->all(),
                ]);
            }

            $expense_voucher_advance_request_claim = ExpenseVoucherAdvanceRequestClaim::where('id', $request->expense_voucher_advance_request_claim_id)->first();
            if($request->employee_return_payment_mode_id != 4010){
            	//NOT CASH
            	$expense_voucher_advance_request_claim->employee_return_payment_reference_no = $request->employee_return_payment_reference_no;
            	$expense_voucher_advance_request_claim->employee_return_payment_date = date('Y-m-d', strtotime((str_replace('/', '-', $request->employee_return_payment_date))));
            	$expense_voucher_advance_request_claim->employee_return_payment_bank_id = $request->employee_return_payment_bank_id;
            }

            if(isset($request->employee_return_payment_receipt_no)){
            	$expense_voucher_advance_request_claim->employee_return_payment_receipt_no = $request->employee_return_payment_receipt_no;
            }
            if(isset($request->employee_return_payment_receipt_date)){
            	$expense_voucher_advance_request_claim->employee_return_payment_receipt_date = date('Y-m-d', strtotime((str_replace('/', '-', $request->employee_return_payment_receipt_date))));
            }
            $expense_voucher_advance_request_claim->is_employee_return_payment_detail_updated = 1;
            $expense_voucher_advance_request_claim->save();

            $message = 'Details updated successfully!';
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
			return response()->json([
				'success' => false,
				'errors' => [
					'Exception Error' => $e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile(),
				],
			]);
        }
    }

    public function searchOracleCoaCodes(Request $request) {
    	return CoaCode::searchOracleCoaCodes($request);
	}
}
