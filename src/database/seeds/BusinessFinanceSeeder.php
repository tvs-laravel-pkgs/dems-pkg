<?php

namespace Uitoux\EYatra\Database\Seeds;
use PHPExcel_IOFactory;
use Validator;
use DB;
use Uitoux\EYatra\Business;
use Uitoux\EYatra\BusinessFinance;
use Illuminate\Database\Seeder;

class BusinessFinanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $objPHPExcel = PHPExcel_IOFactory::load('public/excel-imports/business_finances.xlsx');
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

        $business_finance = $sheet->rangeToArray('A2:'.$highestColumn . $highestRow, null, true, false);
        if(!empty($business_finance)) {
            foreach ($business_finance as $key => $business_financeValue) {
                $val = [];
                foreach ($header as $headerKey => $column) {
                    if (!$column) {
                        continue;
                    } else {
                        $header_col = str_replace(' ', '_', strtolower($column));
                        $val[$header_col] = $business_financeValue[$headerKey];
                    }
                }
                $val = (object)$val;
                try {
                    if (empty($val->business)) {
                        dump('Record No: ' . ($key + 1) . ' - Business is required');
						continue;
                    }if (empty($val->financial_year)) {
                        dump('Record No: ' . ($key + 1) . ' - Financial Year is required');
						continue;
                    }
                    if (empty($val->budget_amount)) {
                        dump('Record No: ' . ($key + 1) . ' - Budget Amount is required');
						continue;
                    }
                    dump($val->business,$val->financial_year,$val->budget_amount);
                    DB::enableQueryLog();

                    $business = Business::select('id')->where('name',$val->business)->first();
                    dump($business);
                    
                    if (!$business) {
                        dump('Record No: ' . ($key + 1) . ' - Business is Not Found');
						continue;
                    }
                    dd(DB::getQueryLog()); 
                    $exist_business_finance = BusinessFinance::where('business_id', $business)
                    ->where('financial_year', $val->financial_year)
                    ->first();

                    if ($exist_business_finance) {
                        dump('Record No: ' . ($key + 1) . ' - Business Finance is ALready Exist');
						continue;
                    }

                    $new_business_finance = new BusinessFinance;
                    $new_business_finance->business_id = $business;
                    $new_business_finance->financial_year = $val->financial_year;
                    $new_business_finance->budget_amount = $val->budget_amount;
                    $new_business_finance->created_by = 1;
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
