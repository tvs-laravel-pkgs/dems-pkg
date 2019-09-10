app.config(['$routeProvider', function($routeProvider) {

    $routeProvider.

    //ENTITIES
    when('/eyatra/entity/list/:entity_type_id', {
        template: '<eyatra-entity-list></eyatra-entity-list>',
        title: 'Entity Master List',
    }).
    when('/eyatra/entity/add/:entity_type_id', {
        template: '<eyatra-entity-form></eyatra-entity-form>',
        title: 'Add Entity Master',
    }).
    when('/eyatra/entity/edit/:entity_type_id/:entity_id', {
        template: '<eyatra-entity-form></eyatra-entity-form>',
        title: 'Edit Entity Master',

    }).
    //TRAVEL MODE
    when('/eyatra/travel-mode/list', {
        template: '<eyatra-travel-mode-list></eyatra-travel-mode-list>',
        title: 'Travel Mode List',
    }).
    when('/eyatra/travel-mode/add', {
        template: '<eyatra-travel-mode-form></eyatra-travel-mode-form>',
        title: 'Add Travel Mode',
    }).
    when('/eyatra/travel-mode/edit/:travel_mode_id', {
        template: '<eyatra-travel-mode-form></eyatra-travel-mode-form>',
        title: 'Edit Travel Mode',

    }).

    //REJECTION REASON
    when('/eyatra/rejection-reason/list', {
        template: '<entity-data-list></entity-data-list>',
        title: 'Entity list',
    }).
    when('/eyatra/rejection-reason/add', {
        template: '<entity-data-form></entity-data-form>',
        title: 'Add Entity',
    }).
    when('/eyatra/rejection-reason/edit/:entity_id', {
        template: '<entity-data-form></entity-data-form>',
        title: 'Edit Entity',
    }).

    //COA SUB MASTERS
    when('/eyatra/coa-sub-master/list', {
        template: '<coa-data-list></coa-data-list>',
        title: 'COA list',
    }).
    when('/eyatra/coa-sub-master/add', {
        template: '<coa-data-form></coa-data-form>',
        title: 'Add COA',
    }).
    when('/eyatra/coa-sub-master/edit/:entity_id', {
        template: '<coa-data-form></coa-data-form>',
        title: 'Edit COA',
    }).


    //GRADES
    when('/eyatra/grades', {
        template: '<eyatra-grades></eyatra-grades>',
        title: 'Grades',
    }).
    when('/eyatra/grade/add', {
        template: '<eyatra-grade-form></eyatra-grade-form>',
        title: 'Add Grade',
    }).
    when('/eyatra/grade/edit/:grade_id', {
        template: '<eyatra-grade-form></eyatra-grade-form>',
        title: 'Edit Grade',
    }).
    when('/eyatra/grade/view/:grade_id', {
        template: '<eyatra-grade-view></eyatra-grade-view>',
        title: 'View Grade',
    }).

    //Coa Codes
    when('/eyatra/coa-codes', {
        template: '<eyatra-coa-code></eyatra-coa-code>',
        title: 'Coa Codes',
    }).
    when('/eyatra/coa-code/add', {
        template: '<eyatra-coa-code-form></eyatra-coa-code-form>',
        title: 'Add Coa Codes',
    }).
    when('/eyatra/coa-code/edit/:coa_code_id', {
        template: '<eyatra-coa-code-form></eyatra-coa-code-form>',
        title: 'Edit Coa Codes',
    }).
    when('/eyatra/coa-code/view/:coa_code_id', {
        template: '<eyatra-coa-code-view></eyatra-coa-code-view>',
        title: 'View Coa Codes',
    }).


    //AGENTS
    when('/eyatra/agents', {
        template: '<eyatra-agents></eyatra-agents>',
        title: 'Agents',
    }).
    when('/eyatra/agent/add', {
        template: '<eyatra-agent-form></eyatra-agent-form>',
        title: 'Add Agent',
    }).
    when('/eyatra/agent/edit/:agent_id', {
        template: '<eyatra-agent-form></eyatra-agent-form>',
        title: 'Edit Agent',
    }).
    when('/eyatra/agent/view/:agent_id', {
        template: '<eyatra-agent-view></eyatra-agent-view>',
        title: 'View Agent',
    }).

    //STATES
    when('/eyatra/states', {
        template: '<eyatra-states></eyatra-states>',
        title: 'States',
    }).
    when('/eyatra/state/add', {
        template: '<eyatra-state-form></eyatra-state-form>',
        title: 'Add State',
    }).
    when('/eyatra/state/edit/:state_id', {
        template: '<eyatra-state-form></eyatra-state-form>',
        title: 'Edit State',
    }).
    when('/eyatra/state/view/:state_id', {
        template: '<eyatra-state-view></eyatra-state-view>',
        title: 'View State',
    }).

    //CITIES
    when('/eyatra/cities', {
        template: '<eyatra-city></eyatra-city>',
        title: 'Cities',
    }).
    when('/eyatra/city/add', {
        template: '<eyatra-city-form></eyatra-city-form>',
        title: 'Add City',
    }).
    when('/eyatra/city/edit/:city_id', {
        template: '<eyatra-city-form></eyatra-city-form>',
        title: 'Edit City',
    }).
    when('/eyatra/city/view/:city_id', {
        template: '<eyatra-city-view></eyatra-city-view>',
        title: 'View City',
    }).

    //DEASIGNATIONS
    when('/eyatra/designations', {
        template: '<eyatra-designation></eyatra-designation>',
        title: 'Designations',
    }).
    when('/eyatra/designation/add', {
        template: '<eyatra-designation-form></eyatra-designation-form>',
        title: 'Add Designation',
    }).
    when('/eyatra/designation/edit/:designation_id', {
        template: '<eyatra-designation-form></eyatra-designation-form>',
        title: 'Edit Designation',
    }).
    when('/eyatra/designation/view/:designation_id', {
        template: '<eyatra-designation-view></eyatra-designation-view>',
        title: 'View Designation',
    }).

    //OUTLETS
    when('/eyatra/outlets', {
        template: '<eyatra-outlets></eyatra-outlets>',
        title: 'Outlets',
    }).
    when('/eyatra/outlet/add', {
        template: '<eyatra-outlet-form></eyatra-outlet-form>',
        title: 'Add Outlet',
    }).
    when('/eyatra/outlet/edit/:outlet_id', {
        template: '<eyatra-outlet-form></eyatra-outlet-form>',
        title: 'Edit Outlet',
    }).
    when('/eyatra/outlet/view/:outlet_id', {
        template: '<eyatra-outlet-view></eyatra-outlet-view>',
        title: 'View Outlet',
    }).

    //REGIONS
    when('/eyatra/regions', {
        template: '<eyatra-regions></eyatra-regions>',
        title: 'Regions',
    }).
    when('/eyatra/region/add', {
        template: '<eyatra-region-form></eyatra-region-form>',
        title: 'Add Region',
    }).
    when('/eyatra/region/edit/:region_id', {
        template: '<eyatra-region-form></eyatra-region-form>',
        title: 'Edit Region',
    }).
    when('/eyatra/region/view/:region_id', {
        template: '<eyatra-region-view></eyatra-region-view>',
        title: 'View Region',
    }).

    //EMPLOYEES
    when('/eyatra/employees', {
        template: '<eyatra-employees></eyatra-employees>',
        title: 'Employees',
    }).
    when('/eyatra/employee/add', {
        template: '<eyatra-employee-form></eyatra-employee-form>',
        title: 'Add Employee',
    }).
    when('/eyatra/employee/edit/:employee_id', {
        template: '<eyatra-employee-form></eyatra-employee-form>',
        title: 'Edit Employee',
    }).
    when('/eyatra/employee/view/:employee_id', {
        template: '<eyatra-employee-view></eyatra-employee-view>',
        title: 'View Employee',
    }).

    //EMPLOYEES IMPORT
    when('/eyatra/import/job/list', {
        template: '<eyatra-jobs-import-list></eyatra-jobs-import-list>',
        title: 'Import List',
    }).
    when('/eyatra/import/jobs', {
        template: '<import-jobs></import-jobs>',
        title: 'Import Employees',
    }).
    //END

    //EXPENSE VOUCHER ADVANCE
    when('/eyatra/expense/voucher-advance/list', {
        template: '<eyatra-expense-voucher-advance-list></eyatra-expense-voucher-advance-list>',
        title: 'Expense Voucher Advance',
    }).
    when('/eyatra/expense/voucher-advance/add', {
        template: '<eyatra-expense-voucher-advance-form></eyatra-expense-voucher-advance-form>',
        title: 'Add Expense Voucher Advance',
    }).
    when('/eyatra/expense/voucher-advance/edit/:id', {
        template: '<eyatra-expense-voucher-advance-form></eyatra-expense-voucher-advance-form>',
        title: 'Add Expense Voucher Advance',
    }).


    //TRIP
    when('/eyatra/trips', {
        template: '<eyatra-trips></eyatra-trips>',
        title: 'Trips',
    }).
    when('/eyatra/trip/add', {
        template: '<eyatra-trip-form></eyatra-trip-form>',
        title: 'Add Trip',
    }).
    when('/eyatra/trip/edit/:trip_id', {
        template: '<eyatra-trip-form></eyatra-trip-form>',
        title: 'Edit Trip',
    }).
    when('/eyatra/trip/view/:trip_id', {
        template: '<eyatra-trip-view></eyatra-trip-view>',
        title: 'View Trip',
    }).
    when('/eyatra/trip/visit/view/:visit_id', {
        template: '<eyatra-trip-visit-view></eyatra-trip-visit-view>',
        title: 'View Trip',
    }).

    //TRIP CLAIM
    when('/eyatra/trip/claim/list', {
        template: '<eyatra-trip-claim-list></eyatra-trip-claim-list>',
        title: 'Trip Claim List',
    }).
    when('/eyatra/trip/claim/add/:trip_id', {
        template: '<eyatra-trip-claim-form></eyatra-trip-claim-form>',
        title: 'Trip Claim Form',
    }).
    when('/eyatra/trip/claim/edit/:trip_id', {
        template: '<eyatra-trip-claim-form></eyatra-trip-claim-form>',
        title: 'Edit Trip Claim',
    }).
    when('/eyatra/trip/claim/view/:trip_id', {
        template: '<eyatra-trip-claim-view></eyatra-trip-claim-view>',
        title: 'View Trip Claim',
    }).

    //MANAGER - EMPLOYEE CLAIM VERIFICATION
    when('/eyatra/trip/claim/verification1/list', {
        template: '<eyatra-trip-claim-verification-one-list></eyatra-trip-claim-verification-one-list>',
        title: 'Employee Claim Verification One',
    }).
    when('/eyatra/trip/claim/verification1/view/:trip_id', {
        template: '<eyatra-trip-claim-verification-one-view></eyatra-trip-claim-verification-one-view>',
        title: 'View Employee Claim Verification One',
    }).

    //MANAGER - EMPLOYEE CLAIM VERIFICATION
    when('/eyatra/trip/claim/verification2/list', {
        template: '<eyatra-trip-claim-verification-two-list></eyatra-trip-claim-verification-two-list>',
        title: 'Employee Claim Verification Two',
    }).
    when('/eyatra/trip/claim/verification2/view/:trip_id', {
        template: '<eyatra-trip-claim-verification-two-view></eyatra-trip-claim-verification-two-view>',
        title: 'View Employee Claim Verification Two',
    }).

    //FINANCIER - EMPLOYEE CLAIM VERIFICATION
    when('/eyatra/trip/claim/verification3/list', {
        template: '<eyatra-trip-claim-verification-three-list></eyatra-trip-claim-verification-three-list>',
        title: 'Employee Claim Verification Three',
    }).
    when('/eyatra/trip/claim/verification3/view/:trip_id', {
        template: '<eyatra-trip-claim-verification-three-view></eyatra-trip-claim-verification-three-view>',
        title: 'View Employee Claim Verification Three',
    }).


    //TRIP VERIFICATION
    when('/eyatra/trip/verifications', {
        template: '<eyatra-trip-verifications></eyatra-trip-verifications>',
        title: 'Trips',
    }).
    when('/eyatra/trip/verification/form/:trip_id', {
        template: '<eyatra-trip-verification-form></eyatra-trip-verification-form>',
        title: 'Trip Verification Form',
    }).

    //ADVANCE CLAIM REQUEST
    when('/eyatra/advance-claim/requests', {
        template: '<eyatra-advance-claim-requests></eyatra-advance-claim-requests>',
        title: 'Advance Claim Requests',
    }).
    when('/eyatra/advance-claim/request/form/:trip_id', {
        template: '<eyatra-advance-claim-request-form></eyatra-advance-claim-request-form>',
        title: 'Advance Claim Request Form',
    }).

    //AGENT - REQUEST VIEW
    when('/eyatra/agent/requests', {
        template: '<eyatra-agent-requests></eyatra-agent-requests>',
        title: 'Agent Requests',
    }).
    when('/eyatra/agent/request/view/:trip_id', {
        template: '<eyatra-agent-request-form></eyatra-agent-request-form>',
        title: 'Agent Request Form',
    }).


    //AGENT - BOOKING REQUESTS
    when('/eyatra/trips/booking-requests', {
        template: '<eyatra-trip-booking-requests></eyatra-trip-booking-requests>',
        title: 'Trips Booking Requests',
    }).
    when('/eyatra/trips/booking-requests/view/:trip_id', {
        template: '<eyatra-trip-booking-requests-view></eyatra-booking-requests-view>',
        title: 'Trip Booking Request View',
    }).

    //AGENT - BOOKING UPDATES
    when('/eyatra/agent/booking-update/form/:visit_id', {
        template: '<eyatra-agent-booking-update-form></eyatra-agent-booking-update-form>',
        title: 'Agent Booking Update Form',
    }).

    //AGENT CLAIM
    when('/eyatra/agent/claim/list', {
        template: '<eyatra-agent-claim-list></eyatra-agent-claim-list>',
        title: 'Agent Claim List',
    }).
    when('/eyatra/agent/claim/add', {
        template: '<eyatra-agent-claim-form></eyatra-agent-claim-form>',
        title: 'New Agent Claim',
    }).
    when('/eyatra/agent/claim/edit/:agent_claim_id', {
        template: '<eyatra-agent-claim-form></eyatra-agent-claim-form>',
        title: 'Edit Agent Claim',
    }).
    when('/eyatra/agent/claim/view/:agent_claim_id', {
        template: '<eyatra-agent-claim-view></eyatra-agent-claim-view>',
        title: 'View Agent Claim',
    }).

    //EMPLOYEE - BOOKING UPDATES
    when('/eyatra/trips/booking-updates', {
        template: '<eyatra-trip-booking-updates></eyatra-trip-booking-updates>',
        title: 'Trips Booking Updates',
    }).
    when('/eyatra/trips/booking-updates/form/:visit_id', {
        template: '<eyatra-trip-booking-updates-form></eyatra-trip-booking-updates-form>',
        title: 'Employee Booking Update Form',
    }).

    //EMPLOYEE - FINANCE TEAM
    when('/eyatra/finance-emp-claim', {
        template: '<eyatra-finance-emp-list></eyatra-finance-emp-list>',
        title: 'Finance Employee Claims',
    }).
    when('/eyatra/finance-emp-claim/add', {
        template: '<eyatra-finance-emp-form></eyatra-finance-emp-form>',
        title: 'Finance Employee Claims Add',
    }).

    //PETTY CASH
    when('/eyatra/petty-cash', {
        template: '<eyatra-petty-cash-list></eyatra-petty-cash-list>',
        title: 'Petty Cash',
    }).
    when('/eyatra/petty-cash/add/:type_id', {
        template: '<eyatra-petty-cash-form></eyatra-petty-cash-form>',
        title: 'Add Petty Cash',
    }).
    when('/eyatra/petty-cash/edit/:type_id/:pettycash_id', {
        template: '<eyatra-petty-cash-form></eyatra-petty-cash-form>',
        title: 'Edit Petty Cash',
    }).
    when('/eyatra/petty-cash/view/:type_id/:pettycash_id', {
        template: '<eyatra-petty-cash-view></eyatra-petty-cash-view>',
        title: 'View Petty Cash',
    }).

    //PETTY CASH VIEW AND LIST FOR MANAGER
    when('/eyatra/petty-cash/verification1/', {
        template: '<eyatra-petty-cash-manager-list></eyatra-petty-cash-manager-list>',
        title: 'Petty Cash Manager Verification',
    }).
    when('/eyatra/petty-cash/verification1/view/:type_id/:pettycash_id', {
        template: '<eyatra-petty-cash-manager-view></eyatra-petty-cash-manager-view>',
        title: 'View Petty Cash Manager Verification',
    }).

    //PETTY CASH VIEW AND LIST FOR CASHIER 
    when('/eyatra/petty-cash/verification2/', {
        template: '<eyatra-petty-cash-cashier-list></eyatra-petty-cash-cashier-list>',
        title: 'Petty Cash Cashier Verification',
    }).
    when('/eyatra/petty-cash/verification2/view/:type_id/:pettycash_id', {
        template: '<eyatra-petty-cash-cashier-view></eyatra-petty-cash-cashier-view>',
        title: 'View Petty Cash Cashier Verification',
    }).

    //PETTY CASH VIEW AND LIST FOR FINANCE
    when('/eyatra/petty-cash/verification3/', {
        template: '<eyatra-petty-cash-finance-list></eyatra-petty-cash-finance-list>',
        title: 'Petty Cash Finance Verification',
    }).
    when('/eyatra/petty-cash/verification3/view/:type_id/:pettycash_id', {
        template: '<eyatra-petty-cash-finance-view></eyatra-petty-cash-finance-view>',
        title: 'View Petty Cash Finance Verification',
    }).

    //ALTERNATE APPROVE LIST
    when('/eyatra/alternate-approve', {
        template: '<eyatra-alternate-approve-list></eyatra-alternate-approve-list>',
        title: 'Alternate Approve List',
    }).
    when('/eyatra/alternate-approve/add', {
        template: '<eyatra-alternate-approve-form></eyatra-alternate-approve-form>',
        title: 'Add Alternate Approve',
    }).
    when('/eyatra/alternate-approve/edit/:alternate_id', {
        template: '<eyatra-alternate-approve-form></eyatra-alternate-approve-form>',
        title: 'Edit Alternate Approve',
    }).

    //OUTLET - OUTLET REIMBURSEMENT
    when('/eyatra/outlet-reimbursement', {
        template: '<eyatra-outlet-reimbursement></eyatra-outlet-reimbursement>',
        title: 'Reimbursements',
    }).
    when('/eyatra/outlet-reimbursement/add', {
        template: '<eyatra-outlet-reimbursement-form></eyatra-outlet-reimbursement-form>',
        title: 'Add Region',
    }).
    when('/eyatra/outlet-reimbursement/edit/:outlet_id', {
        template: '<eyatra-outlet-reimbursement-form></eyatra-outlet-reimbursement-form>',
        title: 'Edit Reimbursement',
    }).
    when('/eyatra/outlet-reimbursement/view/:outlet_id', {
        template: '<eyatra-outlet-reimbursement-view></eyatra-outlet-reimbursement-view>',
        title: 'View Reimbursement',
    }).

    //FINANCIER - AGENT CLAIM
    when('/eyatra/agent/claim/verification1/list', {
        template: '<eyatra-agent-claim-verification-list></eyatra-agent-claim-verification-list>',
        title: 'Agent Claim Verification',
    }).
    when('/eyatra/finance/agent/claim/view/:agent_claim_id', {
        template: '<eyatra-agent-claim-verification-view></eyatra-agent-claim-verification-view>',
        title: 'View Agent Claim',
    });

}]);