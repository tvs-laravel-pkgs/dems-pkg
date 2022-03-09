<?php

namespace Uitoux\EYatra\Database\Seeds;
use PHPExcel_IOFactory;
use Validator;
use Uitoux\EYatra\Business;
use Uitoux\EYatra\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $objPHPExcel = PHPExcel_IOFactory::load('public/excel-imports/departments.xlsx');
        $sheet = $objPHPExcel->getSheet(0);
        $highestRow = $sheet->getHighestDataRow();
        $highestColumn = $sheet->getHighestDataColumn();
        $header = $sheet->rangeToArray('A1:'.$highestColumn. '1',null,true,false);
        $header = $header[0];
        foreach ($header as $key => $column) {
            if ($column == null) {
                unset($header[$key]);
            }
        }

        $departments = $sheet->rangeToArray('A2:'.$highestColumn . $highestRow, null, true, false);
        if(!empty($departments)) {
            foreach ($departments as $key => $departmentsValue) {
                $val = [];
                foreach ($header as $headerKey => $column) {
                    if (!$column) {
                        continue;
                    } else {
                        $header_col = str_replace(' ', '_', strtolower($column));
                        $val[$header_col] = $departmentsValue[$headerKey];
                    }
                }
                $val = (object)$val;
                try {
                    if (empty($val->company)) {
                        dump('Record No: ' . ($key + 1) . ' - Company is required');
						continue;
                    }if (empty($val->business)) {
                        dump('Record No: ' . ($key + 1) . ' - Business is required');
						continue;
                    }if (empty($val->name)) {
                        dump('Record No: ' . ($key + 1) . ' - Name is required');
						continue;
                    }
                    if (empty($val->short_name)) {
                        dump('Record No: ' . ($key + 1) . ' - Short name is required');
						continue;
                    }
                    dump($val->company,$val->name,$val->short_name,$val->business);


                    $validator = Validator::make((array) $val, [
                        'name' => [
                            'string',
                            'max:255',
                        ],
                        'short_name' => [
                            'string',
                            'max:191',
                        ]
                    ]);
                    if ($validator->fails()) {
						dump('Record No: ' . ($key + 1) . ' ' . implode('', $validator->errors()->all()));
						continue;
					}

                    $business->id = Business::select('id')->where('name',$val->business)->first();

                    dump($val->company,$val->name,$val->short_name, $business->id);
dd($business->id);
                    $exist_department = Department::where('company_id',$val->company)
                    ->where('name', $val->name)
                    ->where('short_name', $val->short_name)
                    ->first();

                    if ($exist_department) {
                        dump('Record No: ' . ($key + 1) . ' - Department is ALready Exist');
						continue;
                    }

                    $new_department = new Department;
                    $new_department->company_id = $val->company;
                    $new_department->business_id =  $business->id;
                    $new_department->name = $val->name;
                    $new_department->short_name = $val->short_name;
                    $new_department->created_by = 1;
                    $new_department->save();
                    // $new_department->code = $new_department->id;
                    // $new_department->save();
                    dump(' === updated === ');

                } catch (\Exception $e) {
					dump($e);
					continue;
				}
            }
            dd(' == completed ==');
        }
    }
}