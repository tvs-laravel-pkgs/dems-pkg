<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use Uitoux\EYatra\Business;
use Uitoux\EYatra\BusinessFinance;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Uitoux\EYatra\Company;
use Uitoux\EYatra\Config;
use Validator;
use Input;
use Yajra\Datatables\Datatables;

class BusinessController extends Controller
{
    public function listEYatraBusiness(Request $r) {
        $business = Business::withTrashed()->select(
            'businesses.id',
            'businesses.code',
            'businesses.name',
            'businesses.short_name',
            'business_finances.financial_year',
            'business_finances.budget_amount',
            DB::raw('IF(businesses.deleted_at IS NULL,"Active","Inactive") as status'),
            'users.name as created_by'
        )
            ->join('users', 'businesses.created_by', 'users.id')
            ->join('business_finances','business_id','businesses.id')
            ->groupBy('businesses.id');

        /*if (!Entrust::can('eyatra-all-company-view')) {
            $companies->where('companies.id', Auth::user()->company_id);
        }*/
        return Datatables::of($business)
            ->addColumn('action', function ($business) {
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

                $action .= '<a style="' . $edit_class . '" href="#!/business/edit/' . $business->id . '"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '"></a> ';
                /*$action .= '<a href="#!/company/view/' . $business->id . '"><img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" ></a> ';*/
                 /*$action .= '<a style="' . $delete_class . '" href="javascript:;" data-toggle="modal" data-target="#delete_company" onclick="angular.element(this).scope().deleteCompanyConfirm(' . $business->id . ')" dusk = "delete-btn" title="Delete"><img src="' . $img3 . '" alt="delete" class="img-responsive" onmouseover=this.src="' . $img3_active . '" onmouseout=this.src="' . $img3 . '" ></a>';*/

                return $action;
            })
            ->addColumn('status', function ($business) {
                if ($business->status == 'Inactive') {
                    return '<span style="color:red">Inactive</span>';
                } else {
                    return '<span style="color:green">Active</span>';
                }
            })
            ->make(true);
    }

    public function eyatraBusinessFormData($id = NULL) {
        if (!$id) {
            $this->data['action'] = 'Add';
            $business = new Business;
            $this->data['status'] = 'Active';
            $this->data['success'] = true;
            $this->data['businessFinances'] = [];

        } else {
            $this->data['action'] = 'Edit';
            $business = Business::withTrashed()->findOrFail($id);
            //$this->data['businessFinanceIds'] = $business->businessFinance()->pluck('id')->toArray();
            /*if (!$business) {
                $this->data['success'] = false;
                $this->data['message'] = 'Business not found';
            }*/
            if ($business->deleted_at == NULL) {
                $this->data['status'] = 'Active';
            } else {
                $this->data['status'] = 'Inactive';
            }
        }
         $finances= BusinessFinance::select('id','business_id','financial_year','budget_amount')->where('business_id','=',$id)->get();
         if($finances->isNotEmpty()){
         foreach($finances as $finance){
           $finance->read=true;
           }
         }
         $this->data['businessFinance']=$finances;
         $this->data['company_list']=Company::select('id','name')->get();
        //$company_id=Company::select('id')->get();
        /*$this->data['financial_year_list'] = $financial_year_list = collect(Config::select('name', 'id')->where('config_type_id', 536)->get());*/
        //dd($company);
       $this->data['business'] = $business;
        $this->data['success'] = true;

        return response()->json($this->data);
    }
    public function eyatraBusinessFilterData() {
        $this->data['success'] = true;
        return response()->json($this->data);
    }

    public function saveEYatraBusiness(Request $request) {
       //dd($request->all());
        //validation
        try {
            $error_messages = [
                'name.required' => 'Business Name is Required',
                'short_name.required' => 'Business short name is Required',
                'name.unique' => "Company Name is already taken",
                'short_name.unique' => "Business Short Name is already taken",
           ];
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'short_name' => 'required|unique:businesses,code,' . $request->id . ',id',
                'name' => 'required|unique:businesses,name,' . $request->id . ',id',
                ], $error_messages);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
            }
            // dd($request->all());
            DB::beginTransaction();
            if (!$request->id) {
                $business = new Business;
                $business->created_by = Auth::user()->id;
                $business->created_at = Carbon::now();
                $business->updated_at = NULL;
            } else {
                $business = Business::withTrashed()->find($request->id);
                $business->updated_by = Auth::user()->id;
                $business->updated_at = Carbon::now();
                //$company->outletBudgets()->sync([]);
            }
            if ($request->status == 'Active') {
                $business->deleted_at = NULL;
                $business->deleted_by = NULL;
            } else {
                $business->deleted_at = date('Y-m-d H:i:s');
                $business->deleted_by = Auth::user()->id;
            }
            $business->company_id=$request->company_id;
            $business->fill($request->all());
            $business->save();
            $business->businessFinance()->forcedelete();
            $Ids=$business->id;
            if (!empty($Ids)) {
                foreach ($request->businessFinance as $businessFinance_data) {
                    $businessFinance = new BusinessFinance(['id' => $businessFinance_data['id']]);
                    $businessFinance->fill($businessFinance_data);
                    $businessFinance->id = $businessFinance_data['id'];
                    $businessFinance->business_id = $business->id;
                    $businessFinance->financial_year=$businessFinance_data['financial_year'];
                    $businessFinance->budget_amount=$businessFinance_data['budget_amount'];
                    $businessFinance->created_by = Auth::id();
                    $businessFinance->save();
                }
            }
            DB::commit();
            $request->session()->flash('success', 'Business saved successfully!');
            if (empty($request->id)) {
                return response()->json(['success' => true, 'message' => 'Business Added successfully']);
            } else {
                return response()->json(['success' => true, 'message' => 'Business Updated Successfully']);
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
    public function deleteEYatraBusiness($id) {
        $business = Business::where('id', $id)->first();
        $business->forceDelete();
        if (!$business) {
            return response()->json(['success' => false, 'errors' => ['Business not found']]);
        }
        return response()->json(['success' => true]);
    }
}
