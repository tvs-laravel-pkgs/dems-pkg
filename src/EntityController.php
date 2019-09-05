<?php

namespace Uitoux\EYatra;
use App\EntityType;
use App\Http\Controllers\Controller;
use Auth;
use DB;
use Illuminate\Http\Request;
use Uitoux\EYatra\Entity;
use Validator;
use Yajra\Datatables\Datatables;

class EntityController extends Controller {
	public function getEntityListData($entity_type_id) {
		$entity_type = EntityType::find($entity_type_id);
		if (!$entity_type) {
			return response()->json(['success' => false, 'error' => "Entity Type Not found"]);
		}
		$this->data['entity_type'] = $entity_type;
		return response()->json($this->data);
	}

	public function listEYatraEntity(Request $r) {
		$entities = Entity::withTrashed()->from('entities')
			->select(
				'entities.id',
				'entities.entity_type_id',
				'entities.name',
				'users.username as created_by',
				DB::raw('IF(updater.username IS NULL,"---",updater.username) as updated_by'),
				DB::raw('IF(deactivator.username IS NULL,"---",deactivator.username) as deleted_by'),
				'entities.created_at',
				//'entities.updated_at',
				DB::raw('IF(entities.updated_at IS NULL,"---",entities.updated_at) as updated_at1'),
				DB::raw('IF(entities.deleted_at IS NULL,"---",entities.deleted_at) as deleted_at'),
				DB::raw('IF(entities.deleted_at IS NULL,"ACTIVE","INACTIVE") as status')
			)

			->join('users', 'users.id', '=', 'entities.created_by')
			->leftjoin('users as updater', 'updater.id', '=', 'entities.updated_by')
			->leftjoin('users as deactivator', 'deactivator.id', '=', 'entities.deleted_by')
			->where('entities.company_id', Auth::user()->company_id)
			->where('entities.entity_type_id', $r->entity_type_id)
			->orderBy('entities.id', 'desc');
		return Datatables::of($entities)
			->addColumn('action', function ($entity) {
				$img1 = asset('public/img/content/yatra/table/edit.svg');
				$img1_active = asset('public/img/content/yatra/table/edit-active.svg');
				$img2 = asset('public/img/content/yatra/table/delete.svg');
				$img2_active = asset('public/img/content/yatra/table/delete-active.svg');
				return '
				<a href="#!/eyatra/entity/edit/' . $entity->entity_type_id . '/' . $entity->id . '">
					<img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '">
				</a>
				 <a href="javascript:;"  data-toggle="modal" data-target="#delete_entity" onclick="angular.element(this).scope().deleteEntityDetail(' . $entity->id . ')" title="Delete"><img src="' . $img2 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '"></a>';

			})
			->addColumn('status', function ($entity) {
				if ($entity->status == 'Inactive') {
					return '<span style="color:#ea4335;">Inactive</span>';
				} else {
					return '<span style="color:#63ce63;">Active</span>';
				}

			})
			->make(true);
	}

	public function eyatraEntityFormData($entity_type_id, $entity_id = NULL) {
		$entity_type = EntityType::find($entity_type_id);
		if (!$entity_id) {
			$entity = new Entity;
			$this->data['action'] = 'Add';
			$entity->status = 'Active';
		} else {
			$entity = Entity::withTrashed()->find($entity_id);
			if (!$entity) {
				return response()->json(['success' => false, 'error' => 'Entity not found']);
			}
			$entity->status = $entity->deleted_at == NULL ? 'Active' : 'Inactive';
			$this->data['action'] = 'Edit';
		}

		$this->data['entity_type'] = $entity_type;
		$this->data['entity'] = $entity;
		$this->data['success'] = true;
		return response()->json($this->data);
	}

	public function saveEYatraEntity(Request $request) {
		try {
			$error_messages = [
				'name.required' => 'Name is required',
				'name.unique' => 'Name has already been taken',
			];

			$validator = Validator::make($request->all(), [
				'name' => [
					'required',
					'unique:entities,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id . ',entity_type_id,' . $request->entity_type_id,
					'max:191',
				],

			], $error_messages);

			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}
			//validate

			DB::beginTransaction();
			//$entity_type = EntityType::select('id')->find($request->type_id);
			if (!$request->id) {
				$entity = new Entity;
				$entity->created_by = Auth::user()->id;
				$entity->updated_at = NULL;
			} else {
				$entity = Entity::withTrashed()->find($request->id);
				if (!$entity) {
					return response()->json(['success' => false, 'errors' => ['Entity not found']]);
				}
				$entity->updated_by = Auth::user()->id;
			}

			$entity->fill($request->all());
			$entity->company_id = Auth::user()->company_id;
			$entity->entity_type_id = $request->type_id;
			$entity->name = $request->name;

			if ($request->status == 0) {
				$entity->deleted_at = date('Y-m-d H:i:s');
				$entity->deleted_by = Auth::user()->id;
			} else {
				$entity->deleted_by = NULL;
				$entity->deleted_at = NULL;
			}
			$entity->save();
			//dd('ss');
			$e_name = EntityType::where('id', $request->type_id)->first();
			//dd($e_name);
			$activity['entity_id'] = $entity->id;
			$activity['entity_type'] = $e_name->name;
			$activity['details'] = empty($request->id) ? $e_name->name . " is Added" : $e_name->name . " is updated";
			$activity['activity'] = empty($request->id) ? "Add" : "Edit";
			$activity_log = ActivityLog::saveLog($activity);
			DB::commit();
			if (empty($request->id)) {
				return response()->json(['success' => true, 'message' => 'Entity added successfully']);
			} else {
				return response()->json(['success' => true, 'message' => 'Entity updated successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => [$e->getMessage()]]);
		}
	}

	public function deleteEYatraEntity($entity_id) {
		$entity = Entity::withTrashed()->where('id', $entity_id)->first();
		$e_name = EntityType::where('id', $entity->entity_type_id)->first();
		$activity['entity_id'] = $entity->id;
		$activity['entity_type'] = $e_name->name;
		$activity['details'] = $e_name->name . " is deleted";
		$activity['activity'] = "Delete";
		$activity_log = ActivityLog::saveLog($activity);
		$entity->forceDelete();

		if (!$entity) {
			return response()->json(['success' => false, 'errors' => ['Entity not found']]);
		}

		return response()->json(['success' => true]);
	}

}
