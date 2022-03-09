<?php

namespace Uitoux\EYatra\Database\Seeds;
use PHPExcel_IOFactory;
use Validator;
use Uitoux\EYatra\Company;
use Uitoux\EYatra\Business;
use Illuminate\Database\Seeder;

class BusinessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $objPHPExcel = PHPExcel_IOFactory::load('public/excel-imports/businesses.xlsx');
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

        $business = $sheet->rangeToArray('A2:'.$highestColumn . $highestRow, null, true, false);
        if(!empty($business)) {
            foreach ($business as $key => $businessValue) {
                $val = [];
                foreach ($header as $headerKey => $column) {
                    if (!$column) {
                        continue;
                    } else {
                        $header_col = str_replace(' ', '_', strtolower($column));
                        $val[$header_col] = $businessValue[$headerKey];
                    }
                }
                $val = (object)$val;
                try {
                    if (empty($val->company)) {
                        dump('Record No: ' . ($key + 1) . ' - Company is required');
						continue;
                    }if (empty($val->name)) {
                        dump('Record No: ' . ($key + 1) . ' - Name is required');
						continue;
                    }
                    if (empty($val->short_name)) {
                        dump('Record No: ' . ($key + 1) . ' - Short name is required');
						continue;
                    }
                    dump($val->company,$val->name,$val->short_name);

                    // $company_id = Company::select(['id'])
                    // ->where('code',$val->company)->first();

                    // if (!$company_id) {
					// 	dump('Record No: ' . ($key + 1) . ' -Company is invalid');
					// 	continue;
					// }

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
                    dump($val->company,$val->name,$val->short_name);

                    $exist_business = Business::where('company_id',$val->company)
                    ->where('name', $val->name)
                    ->where('short_name', $val->short_name)
                    ->first();

                    if ($exist_business) {
                        dump('Record No: ' . ($key + 1) . ' - Business is ALready Exist');
						continue;
                    }

                    $new_business = new Business;
                    $new_business->company_id = $val->company;
                    $new_business->name = $val->name;
                    $new_business->short_name = $val->short_name;
                    $new_business->created_by = 1;
                    $new_business->save();
                    $new_business->code = $new_business->id;
                    $new_business->save();
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
