<?php

namespace Uitoux\EYatra\Database\Seeds;
use App\Department;
use App\DepartmentFinance;
use Illuminate\Database\Seeder;

class DepartmentFinanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $objPHPExcel = PHPExcel_IOFactory::load('public/excel-imports/department_finances.xlsx');
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

        $department_finances = $sheet->rangeToArray('A2:'.$highestColumn . $highestRow, null, true, false);
        if(!empty($department_finances)) {
            foreach ($department_finances as $key => $department_financeValue) {
                $val = [];
                foreach ($header as $headerKey => $column) {
                    if (!$column) {
                        continue;
                    } else {
                        $header_col = str_replace(' ', '_', strtolower($column));
                        $val[$header_col] = $department_financeValue[$headerKey];
                    }
                }
                $val = (object)$val;
                try {
                    if (empty($val->department)) {
                        dump('Record No: ' . ($key + 1) . ' - Department is required');
						continue;
                    }if (empty($val->financial_year)) {
                        dump('Record No: ' . ($key + 1) . ' - Financial Year is required');
						continue;
                    }if (empty($val->budget_amount)) {
                        dump('Record No: ' . ($key + 1) . ' - Budget Amount is required');
						continue;
                    }
                    dump($val->department,$val->financial_year,$val->budget_amount);


                    $validator = Validator::make((array) $val, [
                        'financial_year' => [
                            'string',
                            'max:191',
                        ]
                    ]);
                    if ($validator->fails()) {
						dump('Record No: ' . ($key + 1) . ' ' . implode('', $validator->errors()->all()));
						continue;
					}

                    $department_id = Department::pluck('id')
                    ->where('name',$val->department)
                    // ->where('short_name',$val->department)
                    ->first();

                    dump($val->department,$val->financial_year,$val->budget_amount);

                    $exist_department_finance = DepartmentFinance::where('department_id',$department_id)
                    ->where('financial_year', $val->financial_year)
                    ->first();

                    if ($exist_department_finance) {
                        dump('Record No: ' . ($key + 1) . ' - Department Finance is ALready Exist');
						continue;
                    }

                    $new_department_finance = new DepartmentFinance;
                    $new_department_finance->department_id = $department_id;
                    $new_department_finance->financial_year = $val->financial_year;
                    $new_department_finance->budget_amount = $val->budget_amount;
                    $new_department_finance->created_by = 1;
                    $new_department_finance->save();
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
