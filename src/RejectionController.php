<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use DB;
use Illuminate\Http\Request;
use Uitoux\EYatra\ActivityLog;
use Uitoux\EYatra\Entity;
use Validator;
use Yajra\Datatables\Datatables;

class RejectionController extends Controller {

	public function listEYatraEntityNg(Request $r) {

		$entities = Entity::withTrashed()->from('entities')
			->select(
				'entities.id',
				'entities.entity_type_id',
				'entities.name',
				'users.username as created_by',
				'entity_types.name as entity_type',
				DB::raw('IF(updater.username IS NULL,"---",updater.username) as updated_by'),
				DB::raw('IF(deactivator.username IS NULL,"---",deactivator.username) as deleted_by'),
				'entities.created_at',
				DB::raw('IF(entities.updated_at IS NULL,"---",entities.updated_at) as updated_at1'),
				DB::raw('IF(entities.deleted_at IS NULL,"---",entities.deleted_at) as deleted_at'),
				DB::raw('IF(entities.deleted_at IS NULL,"Active","Inactive") as status')
			)

			->join('entity_types', 'entity_types.id', '=', 'entities.entity_type_id')
			->join('users', 'users.id', '=', 'entities.created_by')
			->leftjoin('users as updater', 'updater.id', '=', 'entities.updated_by')
			->leftjoin('users as deactivator', 'deactivator.id', '=', 'entities.deleted_by')
			->where('entities.company_id', Auth::user()->company_id)
			->whereIn('entities.entity_type_id', [507, 508, 509, 510, 511])
			->orderBy('entities.id', 'desc');

		// dd($entities->get());

		return Datatables::of($entities)
			->addColumn('action', function ($entity) {
				$img1 = asset('public/img/content/yatra/table/edit.svg');
				$img1_active = asset('public/img/content/yatra/table/edit-active.svg');
				$img2 = asset('public/img/content/yatra/table/delete.svg');
				$img2_active = asset('public/img/content/yatra/table/delete-active.svg');
				return '

				<a href="#!/eyatra/rejection-reason/edit/' . $entity->id . '">
					<img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '">
				</a>
				 <a href="javascript:;"  data-toggle="modal" data-target="#delete_entity_modal" onclick="angular.element(this).scope().deleteEntityData(' . $entity->id . ')" title="Delete"><img src="' . $img2 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '"></a>';

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

	public function eyatraEntityFormDataNg($entity_id = NULL) {

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
		$entity_type_ids = [507, 508, 509, 510, 511];
		$this->data['reject_type_list'] = DB::table('entity_types')->select('id', 'name')->whereIn('id', $entity_type_ids)->get();

		$this->data['entity'] = $entity;
		$this->data['success'] = true;
		return response()->json($this->data);
	}

	public function saveEYatraEntityNg(Request $request) {
		// dd($request->all());

		try {
			$error_messages = [
				'name.required' => 'Name is required',
				'name.unique' => 'Name has already been taken',
			];

			$validator = Validator::make($request->all(), [
				'name' => [
					'required',
					'unique:entities,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id . ',entity_type_id,' . $request->reject_type,
					'max:191',
				],

			], $error_messages);

			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}
			//validate

			DB::beginTransaction();
			//dd($request->all());
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

			$entity->company_id = Auth::user()->company_id;
			$entity->entity_type_id = $request->reject_type;
			$entity->name = $request->name;

			if ($request->status == 0) {
				$entity->deleted_at = date('Y-m-d H:i:s');
				$entity->deleted_by = Auth::user()->id;
			} else {
				$entity->deleted_by = NULL;
				$entity->deleted_at = NULL;
			}
			$entity->save();
			$e_name = DB::table('entity_types')->where('id', $entity->entity_type_id)->first();
			$activity['entity_id'] = $entity->id;
			$activity['entity_type'] = "Rejection Reasons"; //entity_type_id =511
			$activity['details'] = empty($request->id) ? "Rejection Reason is added" : "Rejection Reason is Updated";
			$activity['activity'] = empty($request->id) ? "add" : "edit";
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

	public function deleteEYatraEntityNg($entity_id) {
		$entity = Entity::withTrashed()->where('id', $entity_id)->first();
		$e_name = DB::table('entity_types')->where('id', $entity->entity_type_id)->first();
		$activity['entity_id'] = $entity->id;
		$activity['entity_type'] = "Rejection Reasons"; //entity_type_id =511
		$activity['details'] = "Rejection Reason is deleted";
		$activity['activity'] = "delete";
		$activity_log = ActivityLog::saveLog($activity);
		$entity->forceDelete();
		if (!$entity) {
			return response()->json(['success' => false, 'errors' => ['Entity not found']]);
		}

		return response()->json(['success' => true]);
	}

}
