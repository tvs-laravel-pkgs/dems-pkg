<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use App\Permission;
use App\Role;
use Auth;
use DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Validator;
use Yajra\Datatables\Datatables;

class RoleController extends Controller {
	public function getRolesList(Request $request) {
		$roles = Role::select('roles.id', 'roles.display_name as role', DB::raw('IF(roles.deleted_at IS NULL,"Active","Inactive") as status'),
			DB::raw('IF(roles.description IS NULL,"N/A",roles.description) as description'))
			->orderBy('roles.display_order', 'ASC');
		return Datatables::of($roles)
			->addColumn('action', function ($roles) {
				if ($roles->fixed_roles == 0) {
					$img1 = asset('public/img/content/table/edit-yellow.svg');
					$img1_active = asset('public/img/content/table/edit-yellow-active.svg');

					return '<a href="#!/eyatra/master/roles/edit/' . $roles->id . '" id = "" ><img src="' . $img1 . '" alt="Account Management" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '"></a>';
					// return '<a href="' . route('editRolesAngular', ['role_id' => $roles->id]) . '" id = "" ><img src="' . $img1 . '" alt="Account Management" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '"></a>';
				} else {
					return '-';
				}
			})
			->addColumn('status', function ($role) {
				$status = $role->status == 'Active' ? 'color-green' : 'color-red';
				return '<span class="status-indigator ' . $status . '">' . $role->status . '</span>';

			})
			->make(true);
	}

	public function editRolesAngular($id = NULL) {

		if (!$id) {
			$data['role'] = new Role;
			$data['action'] = 'Add';
			$data['selected_permissions'] = [];
		} else {
			$data['role'] = $role = Role::where('id', $id)->first();
			if (!$data['role']) {
				return response()->json(['success' => false, 'error' => 'Roles Not Found']);
			}
			$data['selected_permissions'] = $role->permissions()->pluck('id')->toArray();
			$data['action'] = 'Edit';
		}
		$data['parent_permission_group_list'] = Permission::select('parent_id', 'id', 'display_name')->whereNull('parent_id')->get();
		foreach ($data['parent_permission_group_list'] as $key => $value) {
			$permission_group_id = $data['parent_permission_group_list'][$key]['id'];
			$permission_list[$permission_group_id] = Permission::where('parent_id', $permission_group_id)->get();

			foreach ($permission_list[$permission_group_id] as $permission_list_key => $permission_list_value) {
				$permission_group_sub_id = $permission_list_value['id'];
				$permission_sub_list[$permission_group_sub_id] = Permission::where('parent_id', $permission_group_sub_id)
				//->where('display_order', '!=', 0)
					->orderBy('display_order', 'ASC')->get();

				foreach ($permission_sub_list[$permission_group_sub_id] as $key => $sub_value) {
					$permission_group_sub_child_id = $sub_value['id'];
					$permission_sub_child_list[$permission_group_sub_child_id] = Permission::where('parent_id', $permission_group_sub_child_id)

						->orderBy('display_order', 'ASC')->get();

				}

			}

		}
		$data['permission_list'] = $permission_list;
		$data['permission_sub_list'] = $permission_sub_list;
		$data['permission_sub_child_list'] = $permission_sub_child_list;
		$data['success'] = true;
		return response()->json($data);

	}
	public function saveRolesAngular(Request $request) {
		//dd($request->all());
		try {
			$error_messages = [
				'display_name.required' => 'Role name is required',
				'display_name.unique' => 'Role name has already been taken',
				'display_name.max' => 'Maximum length of Role name is 255',
				'permission_id.required' => 'select atleast one page to set permission',
			];
			$validator = Validator::make($request->all(), [
				'display_name' => [
					'required',
					Rule::unique('roles')->ignore($request->id),
					'max:191',
				],
			]);
			DB::beginTransaction();
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}
			if (empty($request->id)) {
				$roles = new Role;
			} else {
				$roles = Role::where('id', $request->id)->first();
				$roles->permissions()->sync([]);
			}
			// $role_name = ucfirst(str_replace(' ', '_', strtolower($request->display_name)));
			// dd($role_name);
			// $roles->name = $role_name;
			$roles->created_by = Auth::user()->id;
			$roles->fill($request->all());
			$roles->display_name = $request->display_name;
			$roles->name = $request->display_name;
			$roles->description = $request->description;
			if ($request->deleted_at == "Active") {
				$roles->deleted_at = null;
			} else {
				$roles->deleted_at = date('Y-m-d');
			}
			$roles->save();
			$roles->permissions()->attach($request->permission_ids);
			//dd($request->permission_ids);
			DB::commit();
			$request->session()->flash('success', 'Roles is saved successfully');
			return response()->json(['success' => true]);
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}

	}
}