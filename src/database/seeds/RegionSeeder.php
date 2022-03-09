<?php

namespace Uitoux\EYatra\Database\Seeds;
use PHPExcel_IOFactory;
use Validator;
use Uitoux\EYatra\NState;
use Uitoux\EYatra\Region;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $objPHPExcel = PHPExcel_IOFactory::load('public/excel-imports/regions.xlsx');
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

        $regions = $sheet->rangeToArray('A2:'.$highestColumn . $highestRow, null, true, false);
        if(!empty($regions)) {
            foreach ($regions as $key => $regionValue) {
                $val = [];
                foreach ($header as $headerKey => $column) {
                    if (!$column) {
                        continue;
                    } else {
                        $header_col = str_replace(' ', '_', strtolower($column));
                        $val[$header_col] = $regionValue[$headerKey];
                    }
                }
                $val = (object)$val;
                try {
                    if (empty($val->name)) {
                        dump('Record No: ' . ($key + 1) . ' - name is required');
						continue;
                    }if (empty($val->company)) {
                        dump('Record No: ' . ($key + 1) . ' - company is required');
						continue;
                    }
                    if (empty($val->code)) {
                        dump('Record No: ' . ($key + 1) . ' - code is required');
						continue;
                    }
                    if (empty($val->state)) {
                        dump('Record No: ' . ($key + 1) . ' - state is required');
						continue;
                    }
                    dump($val->company,$val->code,$val->name,$val->state);

                    $validator = Validator::make((array) $val, [
                        'code' => [
                            'string',
                            'max:191',
                        ],
                        'name' => [
                            'string',
                            'max:191',
                        ]
                    ]);

                    if ($validator->fails()) {
						dump('Record No: ' . ($key + 1) . ' ' . implode('', $validator->errors()->all()));
						continue;
					}

                    $state_id = NState::pluck('id')->where('name',$val->state)->first();

                    if(!$state_id) {
                        dump('Record No: ' . ($key + 1) . ' - State not Found');
						continue;
                    }

                    dump($val->company,$val->code,$val->name,$val->state);

                    $exist_region = Region::where('company_id',$val->company)
                    ->where('code', $val->code)->where('name', $val->name)
                    ->where('state_id', $state_id)->first();

                    if ($exist_region) {
                        dump('Record No: ' . ($key + 1) . ' - Region is ALready Exist');
						continue;
                    }

                    $new_region = new Region;
                    $new_region->name = $val->name;
                    $new_region->company_id = $val->company;
                    $new_region->code = $val->code;
                    $new_region->state_id = $state_id;
                    $new_region->created_by = 1;
                    $new_region->save();
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
