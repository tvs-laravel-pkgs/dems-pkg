<?php
//AUTH
Route::post('eyatra/api/login', 'Uitoux\EYatra\Api\AuthController@login');

Route::group(['middleware' => ['api']], function () {
	Route::group(['middleware' => ['auth:api'], 'prefix' => 'eyatra/api'], function () {
		//HELPERS
		Route::get('city/search', 'Uitoux\EYatra\Api\CityController@searchCity');
		Route::post('state/get', 'Uitoux\EYatra\Api\StateController@getStateList');
		Route::post('city/get', 'Uitoux\EYatra\Api\CityController@getCityList');

		//TRIPS
		Route::post('trip/list', 'Uitoux\EYatra\Api\TripController@listTrip');
		Route::post('trip/get-form-data', 'Uitoux\EYatra\Api\TripController@getTripFormData');
		Route::post('trip/add', 'Uitoux\EYatra\Api\TripController@addTrip');
		Route::post('trip/view/{trip_id}', 'Uitoux\EYatra\Api\TripController@viewTrip');

		//TRIP VERIFICATION
		Route::post('trip/verification/list', 'Uitoux\EYatra\Api\TripVerificationController@listTrip');
		Route::post('trip/verification/view/{trip_id}', 'Uitoux\EYatra\Api\TripVerificationController@viewTrip');
		Route::post('trip/verification/save', 'Uitoux\EYatra\Api\TripVerificationController@viewTrip');

		//COMPLETED TRIP & CLAIM
		Route::post('trip/completed/list', 'Uitoux\EYatra\Api\TripClaimController@listCompletedTrips');
		Route::post('trip/claim/form/data', 'Uitoux\EYatra\Api\TripClaimController@getClaimFormData');
		Route::post('trip/claim/save', 'Uitoux\EYatra\Api\TripClaimController@saveClaim');

	});
});

Route::group(['middleware' => ['web']], function () {
	Route::get('eyatra/login', 'Uitoux\EYatra\AuthController@showLoginForm')->name('eyatraLoginForm');

	Route::group(['middleware' => ['auth']], function () {

		//ENTITIES
		Route::get('eyatra/entity/get-list-data/{entity_type_id?}', 'Uitoux\EYatra\EntityController@getEntityListData')->name('getEntityListData');
		Route::get('eyatra/entity/get-list', 'Uitoux\EYatra\EntityController@listEYatraEntity')->name('listEYatraEntity');
		Route::get('eyatra/entity/get-form-data/{entity_type_id}/{entity_id?}', 'Uitoux\EYatra\EntityController@eyatraEntityFormData')->name('eyatraEntityFormData');
		Route::post('eyatra/entity/save/{entity_type_id}', 'Uitoux\EYatra\EntityController@saveEYatraEntity')->name('saveEYatraEntity');
		Route::get('eyatra/entity/view/{entity_id}', 'Uitoux\EYatra\EntityController@viewEYatraEntity')->name('viewEYatraEntity');
		Route::get('eyatra/entity/delete/{entity_id}', 'Uitoux\EYatra\EntityController@deleteEYatraEntity')->name('deleteEYatraEntity');

		//GRADES
		Route::get('eyatra/grade/get-list', 'Uitoux\EYatra\GradeController@listEYatraGrade')->name('listEYatraGrade');
		Route::get('eyatra/grade/get-form-data/{grade_id?}', 'Uitoux\EYatra\GradeController@eyatraGradeFormData')->name('eyatraGradeFormData');
		Route::post('eyatra/grade/save', 'Uitoux\EYatra\GradeController@saveEYatraGrade')->name('saveEYatraGrade');
		Route::get('eyatra/grade/view/{grade_id}', 'Uitoux\EYatra\GradeController@viewEYatraGrade')->name('viewEYatraGrade');
		Route::get('eyatra/grade/delete/{grade_id}', 'Uitoux\EYatra\GradeController@deleteEYatraGrade')->name('deleteEYatraGrade');

		//COA-CODES
		Route::get('eyatra/coa-code/get-list', 'Uitoux\EYatra\CoaCodeController@listEYatraCoaCode')->name('listEYatraCoaCode');
		Route::get('eyatra/coa-code/get-form-data/{coa_code_id?}', 'Uitoux\EYatra\CoaCodeController@eyatraCoaCodeFormData')->name('eyatraCoaCodeFormData');
		Route::post('eyatra/coa-code/save', 'Uitoux\EYatra\CoaCodeController@saveEYatraCoaCode')->name('saveEYatraCoaCode');
		Route::get('eyatra/coa-code/view/{coa_code_id}', 'Uitoux\EYatra\CoaCodeController@viewEYatraCoaCode')->name('viewEYatraCoaCode');
		Route::get('eyatra/coa-code/delete/{coa_code_id}', 'Uitoux\EYatra\CoaCodeController@deleteEYatraCoaCode')->name('deleteEYatraCoaCode');

		//AGENTS
		Route::get('eyatra/agent/get-list', 'Uitoux\EYatra\AgentController@listEYatraAgent')->name('listEYatraAgent');
		Route::get('eyatra/agent/get-form-data/{agent_id?}', 'Uitoux\EYatra\AgentController@eyatraAgentFormData')->name('eyatraAgentFormData');
		Route::post('eyatra/agent/save', 'Uitoux\EYatra\AgentController@saveEYatraAgent')->name('saveEYatraAgent');
		Route::get('eyatra/agent/view/{agent_id}', 'Uitoux\EYatra\AgentController@viewEYatraAgent')->name('viewEYatraAgent');
		Route::get('eyatra/agent/delete/{agent_id}', 'Uitoux\EYatra\AgentController@deleteEYatraAgent')->name('deleteEYatraAgent');

		//STATES
		Route::get('eyatra/state/get-list', 'Uitoux\EYatra\StateController@listEYatraState')->name('listEYatraState');
		Route::get('eyatra/state/get-form-data/{state_id?}', 'Uitoux\EYatra\StateController@eyatraStateFormData')->name('eyatraStateFormData');
		Route::post('eyatra/state/save', 'Uitoux\EYatra\StateController@saveEYatraState')->name('saveEYatraState');
		Route::get('eyatra/state/view/{state_id}', 'Uitoux\EYatra\StateController@viewEYatraState')->name('viewEYatraState');
		Route::get('eyatra/state/delete/{state_id}', 'Uitoux\EYatra\StateController@deleteEYatraState')->name('deleteEYatraState');
		Route::get('eyatra/state/filter/', 'Uitoux\EYatra\StateController@filterEYatraState')->name('filterEYatraState');

		//CITY
		Route::get('eyatra/city/get-list', 'Uitoux\EYatra\CityController@listEYatraCity')->name('listEYatraCity');
		Route::get('eyatra/city/get-form-data/{state_id?}', 'Uitoux\EYatra\CityController@eyatraCityFormData')->name('eyatraCityFormData');
		Route::post('eyatra/city/save', 'Uitoux\EYatra\CityController@saveEYatraCity')->name('saveEYatraCity');
		Route::get('eyatra/city/view/{city_id}', 'Uitoux\EYatra\CityController@viewEYatraCity')->name('viewEYatraCity');
		Route::get('eyatra/city/delete/{city_id}', 'Uitoux\EYatra\CityController@deleteEYatraCity')->name('deleteEYatraCity');
		Route::get('eyatra/city/get-filter-data', 'Uitoux\EYatra\CityController@eyatraCityFilterData')->name('eyatraCityFilterData');

		//OUTLETS
		Route::get('eyatra/outlet/get-list', 'Uitoux\EYatra\OutletController@listEYatraOutlet')->name('listEYatraOutlet');
		Route::get('eyatra/outlet/get-form-data/{outlet_id?}', 'Uitoux\EYatra\OutletController@eyatraOutletFormData')->name('eyatraOutletFormData');
		Route::post('eyatra/outlet/save', 'Uitoux\EYatra\OutletController@saveEYatraOutlet')->name('saveEYatraOutlet');
		Route::get('eyatra/outlet/view/{outlet_id}', 'Uitoux\EYatra\OutletController@viewEYatraOutlet')->name('viewEYatraOutlet');
		Route::get('eyatra/outlet/delete/{outlet_id}', 'Uitoux\EYatra\OutletController@deleteEYatraOutlet')->name('deleteEYatraOutlet');
		Route::post('eyatra/outlet/cashier-search', 'Uitoux\EYatra\OutletController@searchCashier')->name('searchCashier');
		Route::get('eyatra/lob/get-sbus', 'Uitoux\EYatra\LobController@getLobSbus')->name('getLobSbus');
		Route::get('eyatra/outlet/get-filter-data', 'Uitoux\EYatra\OutletController@eyatraOutletFilterData')->name('eyatraOutletFilterData');

		//EMPLOYEES
		Route::get('eyatra/employee/get-list', 'Uitoux\EYatra\EmployeeController@listEYatraEmployee')->name('listEYatraEmployee');
		Route::get('eyatra/employee/get-form-data/{employee_id?}', 'Uitoux\EYatra\EmployeeController@eyatraEmployeeFormData')->name('eyatraEmployeeFormData');
		Route::post('eyatra/employee/save', 'Uitoux\EYatra\EmployeeController@saveEYatraEmployee')->name('saveEYatraEmployee');
		Route::get('eyatra/employee/view/{employee_id}', 'Uitoux\EYatra\EmployeeController@viewEYatraEmployee')->name('viewEYatraEmployee');
		Route::get('eyatra/employee/delete/{employee_id}', 'Uitoux\EYatra\EmployeeController@deleteEYatraEmployee')->name('deleteEYatraEmployee');
		Route::post('eyatra/employee/manager/search', 'Uitoux\EYatra\EmployeeController@searchManager')->name('searchManager');
		Route::post('eyatra/employee/get/sbu', 'Uitoux\EYatra\EmployeeController@getSbuByLob')->name('getSbuByLob');
		Route::get('eyatra/employee/filter', 'Uitoux\EYatra\EmployeeController@filterEYatraEmployee')->name('filterEYatraEmployee');

		//DESIGNATIONS
		Route::get('eyatra/designations/get-list', 'Uitoux\EYatra\DesignationController@listEYatraDesignation')->name('listEYatraDesignations');
		Route::get('eyatra/designations/get-form-data/{designation_id?}', 'Uitoux\EYatra\DesignationController@eyatraDesignationFormData')->name('eyatraDesignationFormData');
		Route::post('eyatra/designation/save', 'Uitoux\EYatra\DesignationController@saveEYatraDesignation')->name('saveEYatraDesignation');
		Route::get('eyatra/designation/view/{designation_id}', 'Uitoux\EYatra\DesignationController@viewEYatraDesignation')->name('viewEYatraDesignation');
		Route::get('eyatra/designation/delete/{designation_id}', 'Uitoux\EYatra\DesignationController@deleteEYatraDesignation')->name('deleteEYatraDesignation');

		//REGIONS
		Route::get('eyatra/region/get-list', 'Uitoux\EYatra\RegionController@listEYatraRegion')->name('listEYatraRegion');
		Route::get('eyatra/region/get-form-data/{region_id?}', 'Uitoux\EYatra\RegionController@eyatraRegionFormData')->name('eyatraRegionFormData');
		Route::post('eyatra/region/save', 'Uitoux\EYatra\RegionController@saveEYatraRegion')->name('saveEYatraRegion');
		Route::get('eyatra/region/view/{region_id}', 'Uitoux\EYatra\RegionController@viewEYatraRegion')->name('viewEYatraRegion');
		Route::get('eyatra/region/delete/{region_id}', 'Uitoux\EYatra\RegionController@deleteEYatraRegion')->name('deleteEYatraRegion');
		Route::post('eyatra/region/get/state', 'Uitoux\EYatra\RegionController@getStateByCountry')->name('getStateByCountry');
		Route::get('eyatra/region/get-filter-data', 'Uitoux\EYatra\RegionController@eyatraRegionFilterData')->name('eyatraRegionFilterData');

		//TRIPS
		Route::get('eyatra/trip/get-list', 'Uitoux\EYatra\TripController@listTrip')->name('listTrip');
		Route::get('eyatra/trip/get-form-data/{trip_id?}', 'Uitoux\EYatra\TripController@tripFormData')->name('tripFormData');
		Route::post('eyatra/trip/save', 'Uitoux\EYatra\TripController@saveTrip')->name('saveTrip');
		Route::get('eyatra/trip/view/{trip_id}', 'Uitoux\EYatra\TripController@viewTrip')->name('viewTrip');
		Route::get('eyatra/trip/delete/{trip_id}', 'Uitoux\EYatra\TripController@deleteTrip')->name('deleteTrip');
		Route::get('eyatra/trip/cancel/{trip_id}', 'Uitoux\EYatra\TripController@cancelTrip')->name('cancelTrip');
		Route::get('eyatra/trip/verification-request/{trip_id}', 'Uitoux\EYatra\TripController@tripVerificationRequest')->name('tripVerificationRequest');
		Route::get('/eyatra/trip/visit/booking-cancel/{visit_id}', 'Uitoux\EYatra\TripController@cancelTripVisitBooking')->name('cancelTripVisitBooking');
		Route::get('eyatra/trips/visit/get-form-data/{trip_id}', 'Uitoux\EYatra\TripController@visitFormData')->name('visitFormData');
		Route::get('/eyatra/trip/visit/request-booking-cancel/{visit_id}', 'Uitoux\EYatra\TripController@requestCancelVisitBooking')->name('requestCancelVisitBooking');
		// Route::post('eyatra/trip/city/search', 'Uitoux\EYatra\TripController@searchCity')->name('searchCity');
		Route::post('eyatra/trip/city/search', 'Uitoux\EYatra\CityController@searchCity')->name('searchCity');
		Route::get('eyatra/trip/get-filter-data', 'Uitoux\EYatra\TripController@eyatraTripFilterData')->name('eyatraTripFilterData');

		//TRIPS VERIFICATION
		Route::get('eyatra/trip/verification/get-list', 'Uitoux\EYatra\TripVerificationController@listTripVerification')->name('listTripVerification');
		Route::get('eyatra/trip/verification/get-form-data/{trip_id?}', 'Uitoux\EYatra\TripVerificationController@tripVerificationFormData')->name('tripVerificationFormData');
		Route::post('eyatra/trip/verification/save', 'Uitoux\EYatra\TripVerificationController@saveTripVerification')->name('saveTripVerification');

		Route::get('eyatra/trip/verification/get-filter-data', 'Uitoux\EYatra\TripVerificationController@eyatraTripVerificationFilterData')->name('eyatraTripVerificationFilterData');
		Route::post('eyatra/trip/verification/approve', 'Uitoux\EYatra\TripVerificationController@approveTripVerification')->name('approveTripVerification');
		Route::post('eyatra/trip/verification/reject', 'Uitoux\EYatra\TripVerificationController@rejectTripVerification')->name('rejectTripVerification');

		// FINANCE EMPLOYEE CLAIMS
		Route::get('eyatra/finance-emp/claims/get-list', 'Uitoux\EYatra\FinanceEmployeeClaimController@listEYatraFinanceEmployeeClaim')->name('listEYatraFinanceEmployeeClaim');
		Route::get('eyatra/finance-emp/claims/get-form-data/{trip_id?}', 'Uitoux\EYatra\FinanceEmployeeClaimController@eyatraFinanceEmployeeClaimFormData')->name('eyatraFinanceEmployeeClaimFormData');

		//FINANCE AGENT CLAIMS
		Route::get('eyatra/finance/agent/claim/get-list', 'Uitoux\EYatra\AgentClaimController@listFinanceEYatraAgentClaimList')->name('listFinanceEYatraAgentClaimList');
		Route::get('eyatra/finance/agent/claim/view/{agent_claim_id}', 'Uitoux\EYatra\AgentClaimController@viewEYatraFinanceAgentClaim')->name('viewEYatraFinanceAgentClaim');
		Route::post('eyatra/finance/agent-claim/payment', 'Uitoux\EYatra\AgentClaimController@payAgentClaimRequest')->name('payAgentClaimRequest');
		Route::post('eyatra/finance/agent-claim/reject', 'Uitoux\EYatra\AgentClaimController@rejectAgentClaimRequest')->name('rejectAgentClaimRequest');

		//ADVANCE CLAIM REQUESTS
		Route::get('eyatra/advance-claim/request/get-list', 'Uitoux\EYatra\AdvanceClaimRequestController@listAdvanceClaimRequest')->name('listAdvanceClaimRequest');
		Route::get('eyatra/advance-claim/request/get-form-data/{trip_id?}', 'Uitoux\EYatra\AdvanceClaimRequestController@advanceClaimRequestFormData')->name('advanceClaimRequestFormData');
		Route::post('eyatra/advance-claim/request/save', 'Uitoux\EYatra\AdvanceClaimRequestController@saveAdvanceClaimRequest')->name('saveAdvanceClaimRequest');
		Route::get('eyatra/advance-claim/request/approve/{trip_id}', 'Uitoux\EYatra\AdvanceClaimRequestController@approveAdvanceClaimRequest')->name('approveAdvanceClaimRequest');
		Route::post('eyatra/advance-claim/request/reject', 'Uitoux\EYatra\AdvanceClaimRequestController@rejectAdvanceClaimRequest')->name('rejectAdvanceClaimRequest');
		Route::get('eyatra/trip/verification/get-filter-data', 'Uitoux\EYatra\AdvanceClaimRequestController@eyatraAdvanceClaimFilterData')->name('eyatraAdvanceClaimFilterData');

		//AGENT REQUESTS
		Route::get('eyatra/agent/request/get-list', 'Uitoux\EYatra\AgentRequestController@listAgentRequest')->name('listAgentRequest');
		Route::get('eyatra/agent/request/get-form-data/{trip_id?}', 'Uitoux\EYatra\AgentRequestController@agentRequestFormData')->name('agentRequestFormData');
		Route::post('eyatra/agent/request/save', 'Uitoux\EYatra\AgentRequestController@saveAgentRequest')->name('saveAgentRequest');

		//TRIPS BOOKING REQUESTS
		Route::get('eyatra/trips/booking-requests/get-list', 'Uitoux\EYatra\TripBookingRequestController@listTripBookingRequests')->name('listTripBookingRequests');
		Route::get('eyatra/trips/booking-requests/get-view-data/{visit_id?}', 'Uitoux\EYatra\TripBookingRequestController@tripBookingRequestsViewData')->name('tripBookingUpdatesFormData');

		//AGENT CLAIMS
		Route::get('eyatra/agent/claim/get-list', 'Uitoux\EYatra\AgentClaimController@listEYatraAgentClaimList')->name('listEYatraAgentClaimList');
		Route::get('eyatra/agent/claim/add/{agent_claim_id?}', 'Uitoux\EYatra\AgentClaimController@eyatraAgentClaimFormData')->name('eyatraAgentClaimFormData');
		Route::post('eyatra/agent/claim/save', 'Uitoux\EYatra\AgentClaimController@saveEYatraAgentClaim')->name('saveEYatraAgentClaim');
		Route::get('eyatra/agent/claim/view/{agent_claim_id}', 'Uitoux\EYatra\AgentClaimController@viewEYatraAgentClaim')->name('viewEYatraAgentClaim');
		Route::get('eyatra/agent/claim/delete/{agent_claim_id}', 'Uitoux\EYatra\AgentClaimController@deleteEYatraAgentClaim')->name('deleteEYatraAgentClaim');

		//TRIP CLAIM
		Route::get('eyatra/trip/claim/get-list', 'Uitoux\EYatra\TripClaimController@listEYatraTripClaimList')->name('listEYatraTripClaimList');
		Route::get('eyatra/trip/claim/get-form-data/{trip_id?}', 'Uitoux\EYatra\TripClaimController@eyatraTripClaimFormData')->name('eyatraTripClaimFormData');
		Route::post('eyatra/trip/claim/save', 'Uitoux\EYatra\TripClaimController@saveEYatraTripClaim')->name('saveEYatraTripClaim');
		Route::get('eyatra/trip/claim/view/{trip_id?}', 'Uitoux\EYatra\TripClaimController@viewEYatraTripClaim')->name('viewEYatraTripClaim');
		Route::get('eyatra/trip/claim/delete/{trip_id}', 'Uitoux\EYatra\TripClaimController@deleteEYatraTripClaim')->name('deleteEYatraTripClaim');
		Route::get('eyatra/trip/claim/get-eligible-amount', 'Uitoux\EYatra\TripClaimController@getEligibleAmtBasedonCitycategoryGrade')->name('getEligibleAmtBasedonCitycategoryGrade');

		//TRIP CLAIM VERIFICATION ONE
		Route::get('eyatra/trip/claim/verification/one/get-list', 'Uitoux\EYatra\TripClaimVerificationOneController@listEYatraTripClaimVerificationOneList')->name('listEYatraTripClaimVerificationOneList');
		Route::get('eyatra/trip/claim/verification/one/view/{trip_id?}', 'Uitoux\EYatra\TripClaimVerificationOneController@viewEYatraTripClaimVerificationOne')->name('viewEYatraTripClaimVerificationOne');
		Route::post('eyatra/trip/claim/verification/one/reject', 'Uitoux\EYatra\TripClaimVerificationOneController@rejectTripClaimVerificationOne')->name('rejectTripClaimVerificationOne');
		Route::get('eyatra/trip/claim/verification/one/approve/{trip_id}', 'Uitoux\EYatra\TripClaimVerificationOneController@approveTripClaimVerificationOne')->name('approveTripClaimVerificationOne');

		//TRIP CLAIM VERIFICATION TWO
		Route::get('eyatra/trip/claim/verification/two/get-list', 'Uitoux\EYatra\TripClaimVerificationTwoController@listEYatraTripClaimVerificationTwoList')->name('listEYatraTripClaimVerificationTwoList');
		Route::get('eyatra/trip/claim/verification/two/view/{trip_id?}', 'Uitoux\EYatra\TripClaimVerificationTwoController@viewEYatraTripClaimVerificationTwo')->name('viewEYatraTripClaimVerificationTwo');
		Route::post('eyatra/trip/claim/verification/two/reject', 'Uitoux\EYatra\TripClaimVerificationTwoController@rejectTripClaimVerificationTwo')->name('rejectTripClaimVerificationTwo');
		Route::get('eyatra/trip/claim/verification/two/approve/{trip_id}', 'Uitoux\EYatra\TripClaimVerificationTwoController@approveTripClaimVerificationTwo')->name('approveTripClaimVerificationTwo');

		//TRIP CLAIM VERIFICATION THREE
		Route::get('eyatra/trip/claim/verification/three/get-list', 'Uitoux\EYatra\TripClaimVerificationThreeController@listEYatraTripClaimVerificationThreeList')->name('listEYatraTripClaimVerificationThreeList');
		Route::get('eyatra/trip/claim/verification/three/view/{trip_id?}', 'Uitoux\EYatra\TripClaimVerificationThreeController@viewEYatraTripClaimVerificationThree')->name('viewEYatraTripClaimVerificationThree');
		Route::post('eyatra/trip/claim/verification/three/reject', 'Uitoux\EYatra\TripClaimVerificationThreeController@rejectTripClaimVerificationThree')->name('rejectTripClaimVerificationThree');
		Route::post('eyatra/trip/claim/verification/three/approve', 'Uitoux\EYatra\TripClaimVerificationThreeController@approveTripClaimVerificationThree')->name('approveTripClaimVerificationThree');

		//TRIPS BOOKING UPDATES
		Route::get('eyatra/trips/booking-updates/get-list', 'Uitoux\EYatra\TripBookingUpdateController@listTripBookingUpdates')->name('listTripBookingUpdates');
		Route::get('eyatra/trips/booking-updates/get-form-data/{visit_id?}', 'Uitoux\EYatra\TripBookingUpdateController@tripBookingUpdatesFormData')->name('tripBookingUpdatesFormData');
		Route::post('eyatra/trips/booking-updates/save', 'Uitoux\EYatra\TripBookingUpdateController@saveTripBookingUpdates')->name('saveTripBookingUpdates');

		//OUTLET REIMPURSEMENT
		Route::get('eyatra/outlet-reimpursement/get-list', 'Uitoux\EYatra\OutletReimpursementController@listOutletReimpursement')->name('listOutletReimpursement');

		//HELPERS
		Route::post('eyatra/city/search', 'Uitoux\EYatra\CityController@searchCity')->name('searchCity');
		Route::post('eyatra/state/get', 'Uitoux\EYatra\StateController@getStateList')->name('getStateList');
		Route::post('eyatra/city/get', 'Uitoux\EYatra\CityController@getCityList')->name('getCityList');

		//PETTY CASH
		Route::get('eyatra/petty-cash/request/get-list', 'Uitoux\EYatra\PettyCashController@listPettyCashRequest')->name('listPettyCashRequest');
		Route::get('eyatra/petty-cash/request/get-form-data/{pettycash_id?}', 'Uitoux\EYatra\PettyCashController@pettycashFormData')->name('pettycashFormData');
		Route::post('eyatra/petty-cash/request/save', 'Uitoux\EYatra\PettyCashController@pettycashSave')->name('pettycashSave');
		Route::get('eyatra/petty-cash/employee/{searchText}', 'Uitoux\EYatra\PettyCashController@getemployee')->name('getemployee');
		Route::get('eyatra/petty-cash/request/delete/{pettycash_id}', 'Uitoux\EYatra\PettyCashController@pettycashDelete')->name('pettycashDelete');
		Route::get('eyatra/petty-cash/request/view/{pettycash_id}', 'Uitoux\EYatra\PettyCashController@pettycashView')->name('pettycashView');

		//PETTY CASH VERIFICATION VIEW FOR MANAGER
		Route::get('eyatra/petty-cash/manager/get-list', 'Uitoux\EYatra\PettyCashManagerVerificationController@listPettyCashVerificationManager')->name('listPettyCashVerificationManager');
		Route::post('eyatra/petty-cash/manager/save', 'Uitoux\EYatra\PettyCashManagerVerificationController@pettycashManagerVerificationSave')->name('pettycashManagerVerificationSave');
		Route::get('eyatra/petty-cash/manager/view/{pettycash_id}', 'Uitoux\EYatra\PettyCashManagerVerificationController@pettycashManagerVerificationView')->name('pettycashManagerVerificationView');

		//PETTY CASH VERIFICATION VIEW FOR Finance
		Route::get('eyatra/petty-cash/finance/get-list', 'Uitoux\EYatra\PettyCashFinanceVerificationController@listPettyCashVerificationFinance')->name('listPettyCashVerificationFinance');
		Route::post('eyatra/petty-cash/finance/save', 'Uitoux\EYatra\PettyCashFinanceVerificationController@pettycashFinanceVerificationSave')->name('pettycashFinanceVerificationSave');
		Route::get('eyatra/petty-cash/finance/view/{pettycash_id}', 'Uitoux\EYatra\PettyCashFinanceVerificationController@pettycashFinanceVerificationView')->name('pettycashFinanceVerificationView');
	});
});