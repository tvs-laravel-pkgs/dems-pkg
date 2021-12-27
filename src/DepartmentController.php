<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Uitoux\EYatra\Company;
use Uitoux\EYatra\Config;
use Validator;
use Yajra\Datatables\Datatables;
use Uitoux\EYatra\Business;
use Uitoux\EYatra\DepartmentFinance;

class DepartmentController extends Controller
{
    public function listEYatraDepartment(Request $r) {
        $department = Department::withTrashed()->select(
            'departments.id',
            'departments.code',
            'departments.name',
            'departments.short_name',
            'departments.business_id',
            'department_finances.from_date',
            'department_finances.to_date',
            'department_finances.budget_amount',
            DB::raw('IF(departments.deleted_at IS NULL,"Active","Inactive") as status'),
            'users.name as created_by'
        )
            ->join('users', 'departments.created_by', 'users.id')
            ->join('businesses','businesses.id','departments.business_id')
            ->join('department_finances','department_finances.department_id','departments.id')
            ->groupBy('departments.id');

        /*if (!Entrust::can('eyatra-all-company-view')) {
            $companies->where('companies.id', Auth::user()->company_id);
        }*/
        return Datatables::of($department)
            ->addColumn('action', function ($department) {
                $img1 = asset('public/img/content/yatra/table/edit.svg');
                $img1_active = asset('public/img/content/yatra/table/edit-active.svg');
                $img2 = asset('public/img/content/yatra/table/view.svg');
                $img2_active = asset('public/img/content/yatra/table/view-active.svg');
                $img3 = asset('public/img/content/yatra/table/delete.svg');
                $img3_active = asset('public/img/content/yatra/table/delete-active.svg');

                $action = '';
                $edit_class = "visibility:hidden";
                if (Entrust::can('eyatra-outlet-edit')) {
                    $edit_class = "";
                }
                $delete_class = "visibility:hidden";
                if (Entrust::can('eyatra-outlet-delete')) {
                    $delete_class = "";
                }

                $action .= '<a style="' . $edit_class . '" href="#!/department/edit/' . $department->id . '"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '"></a> ';
                $action .= '<a href="#!/company/view/' . $department->id . '"><img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" ></a> ';
                // $action .= '<a style="' . $delete_class . '" href="javascript:;" data-toggle="modal" data-target="#delete_company" onclick="angular.element(this).scope().deleteCompanyConfirm(' . $companies->id . ')" dusk = "delete-btn" title="Delete"><img src="' . $img3 . '" alt="delete" class="img-responsive" onmouseover=this.src="' . $img3_active . '" onmouseout=this.src="' . $img3 . '" ></a>';

                return $action;
            })
            ->addColumn('status', function ($department) {
                if ($department->status == 'Inactive') {
                    return '<span style="color:red">Inactive</span>';
                } else {
                    return '<span style="color:green">Active</span>';
                }
            })
            ->make(true);
    }

    public function eyatraDepartmentFormData($id = NULL) {
        $this->data['business_list']=Business::select('id','name')->get();
        $this->data['company_list']=Company::select('id','name')->get();
        if (!$id) {
            $this->data['action'] = 'Add';
            $department = new Department;
            $this->data['status'] = 'Active';
            $this->data['success'] = true;
            $this->data['departmentFinancesIds'] = [];

        } else {
            $this->data['action'] = 'Edit';
            $department = Department::withTrashed()->findOrFail($id);
            /*if (!$department) {
                $this->data['success'] = false;
                $this->data['message'] = 'Department not found';
            }*/
            if ($department->deleted_at == NULL) {
                $this->data['status'] = 'Active';
            } else {
                $this->data['status'] = 'Inactive';
            }
        }
        $this->data['departmentFinance'] = DepartmentFinance::select('id','department_id','from_date','to_date','budget_amount')->where('department_id','=',$id)->get();
        //$company_id=Company::select('id')->get();
        /*$this->data['financial_year_list'] = $financial_year_list = collect(Config::select('name', 'id')->where('config_type_id', 536)->get());*/
        //dd($company);
       $this->data['department'] = $department;
        $this->data['success'] = true;

        return response()->json($this->data);
    }
    public function eyatraDepartmentFilterData() {
        $this->data['success'] = true;
        return response()->json($this->data);
    }

    public function saveEYatraDepartment(Request $request) {
        //dd($request->all());
        //validation
        try {
            $error_messages = [
                'name.required' => 'Department Name is Required',
                'short_name.required' => 'Department short name is Required',
                'name.unique' => "Company Name is already taken",
                'short_name.unique' => "Department Short Name is already taken",

            ];
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'short_name' => 'required|unique:departments,code,' . $request->id . ',id',
                'name' => 'required|unique:departments,name,' . $request->id . ',id',
                ], $error_messages);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
            }
            // dd($request->all());
            DB::beginTransaction();
            if (!$request->id) {
                $department = new Department;
                $department->created_by = Auth::user()->id;
                $department->created_at = Carbon::now();
                $department->updated_at = NULL;
            } else {
                $department = Department::withTrashed()->find($request->id);
                $department->updated_by = Auth::user()->id;
                $department->updated_at = Carbon::now();
                //$company->outletBudgets()->sync([]);
            }
            if ($request->status == 'Active') {
                $department->deleted_at = NULL;
                $department->deleted_by = NULL;
            } else {
                $department->deleted_at = date('Y-m-d H:i:s');
                $department->deleted_by = Auth::user()->id;
            }
            $department->company_id=$request->company_id;
            $department->business_id=$request->business_id;
            $department->fill($request->all());
            $department->save();
            $department->departmentFinance()->forcedelete();
            $Ids=$department->id;
            if (!empty($Ids)) {
                foreach ($request->departmentFinance as $departmentFinance_data) {
                    $departmentFinance = new DepartmentFinance(['id' => $departmentFinance_data['id']]);
                    $departmentFinance->fill($departmentFinance_data);
                    $departmentFinance->id = $departmentFinance_data['id'];
                    $departmentFinance->department_id = $department->id;
                    $departmentFinance->from_date=date('Y-m-d', strtotime($departmentFinance_data['from_date']));
                    $departmentFinance->to_date=date('Y-m-d', strtotime($departmentFinance_data['to_date']));
                    $business_budget=BusinessFinances::select('business_id','budget_amount')->get();
                    $department_budget=DepartmentFinance::select('department_finances.budget_amount')
                    ->join('departments','departments.id','department_finances.department_id')->get();
                    $departmentFinance->budget_amount=$departmentFinance_data['budget_amount'];
                    $departmentFinance->created_by = Auth::id();
                    $departmentFinance->save();
             }
            }
            DB::commit();
            $request->session()->flash('success', 'Department saved successfully!');
            if (empty($request->id)) {
                return response()->json(['success' => true, 'message' => 'Department Added successfully']);
            } else {
                return response()->json(['success' => true, 'message' => 'Department Updated Successfully']);
            }
            return response()->json(['success' => true]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
        }
    }
    /*public function viewEYatraCompany($id) {
        $company = Company::with('createdBy')->withTrashed()
            ->find($id);
        $company_budget = DB::table('company_budget')->select('configs.name as financial_year', DB::raw('format(outstation_budget_amount,2,"en_IN") as outstation_budget_amount'), DB::raw('format(local_budget_amount,2,"en_IN") as local_budget_amount'))->where('company_budget.company_id', $company->id)
            ->leftJoin('configs', 'configs.id', 'company_budget.financial_year_id')
            ->get()->toArray();
        $this->data['financial_year'] = $lob_name = array_column($company_budget, 'financial_year');
        $this->data['outstation_budget_amount'] = $amount = array_column($company_budget, 'outstation_budget_amount');
        $this->data['local_budget_amount'] = $amount = array_column($company_budget, 'local_budget_amount');

        if (!$company) {
            $this->data['success'] = false;
            $this->data['errors'] = ['Company not found'];
            return response()->json($this->data);
        }
        if ($company->deleted_at == NULL) {
            $this->data['status'] = 'Active';
        } else {
            $this->data['status'] = 'Inactive';
        }

        $this->data['action'] = 'View';
        $this->data['company'] = $company;
        $this->data['success'] = true;

        return response()->json($this->data);
    }*/
    public function deleteEYatraDepartment($id) {
        $department = Department::where('id', $id)->first();
        $department->forceDelete();
        if (!$department) {
            return response()->json(['success' => false, 'errors' => ['Department not found']]);
        }
        return response()->json(['success' => true]);
    }
}
