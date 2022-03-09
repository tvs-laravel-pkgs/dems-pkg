<?php

namespace Uitoux\EYatra\Database\Seeds;
use PHPExcel_IOFactory;
use Validator;
use Uitoux\EYatra\NState;
use DB;
use Illuminate\Database\Seeder;

class nstateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $objPHPExcel = PHPExcel_IOFactory::load('public/excel-imports/nstates.xlsx');
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

        $nstates = $sheet->rangeToArray('A2:'.$highestColumn . $highestRow, null, true, false);
        if(!empty($nstates)) {
            foreach ($nstates as $key => $nstateValues) {
                $val = [];
                foreach ($header as $headerKey => $column) {
                    if (!$column) {
                        continue;
                    } else {
                        $header_col = str_replace(' ', '_', strtolower($column));
                        $val[$header_col] = $nstateValues[$headerKey];
                    }
                }
                $val = (object)$val;
                try {
                    if (empty($val->country)) {
                        dump('Record No: ' . ($key + 1) . ' - country is required');
						continue;
                    }if (empty($val->code)) {
                        dump('Record No: ' . ($key + 1) . ' - code is required');
						continue;
                    }
                    if (empty($val->name)) {
                        dump('Record No: ' . ($key + 1) . ' - name is required');
						continue;
                    }
                    dump($val->country,$val->code,$val->name);

                    $validator = Validator::make((array) $val, [
                        'code' => [
                            'string',
                            'max:2',
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
                    dump($val->country,$val->code,$val->name);

                    $country = DB::table('countries')::pluck('id')->where('name',$val->country)->first();

                    if(!$country){
                        dump('Record No: ' . ($key + 1) . ' - Country Not Found');
						continue;
                    }

                    $exist_nstate = NState::where('country_id',$country)
                    ->where('code', $val->code)
                    ->where('name', $val->name)
                    ->first();

                    if ($exist_nstate) {
                        dump('Record No: ' . ($key + 1) . ' - NState is ALready Exist');
						continue;
                    }

                    $new_nstate = new NState;
                    $new_nstate->country_id = $country;
                    $new_nstate->code = $val->code;
                    $new_nstate->name = $val->name;
                    $new_nstate->created_by = 1;
                    $new_nstate->save();
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
