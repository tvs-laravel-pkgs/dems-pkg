app.config(['$routeProvider', function($routeProvider) {

    $routeProvider.

    //ENTITIES
    when('/entity/list/:entity_type_id', {
        template: '<eyatra-entity-list></eyatra-entity-list>',
        title: 'Entity Master List',
    }).
    when('/entity/add/:entity_type_id', {
        template: '<eyatra-entity-form></eyatra-entity-form>',
        title: 'Add Entity Master',
    }).
    when('/entity/edit/:entity_type_id/:entity_id', {
        template: '<eyatra-entity-form></eyatra-entity-form>',
        title: 'Edit Entity Master',

    }).
    //LOCAL TRAVEL MODE
    when('/local-travel-mode/list', {
        template: '<eyatra-local-travel-mode-list></eyatra-local-travel-mode-list>',
        title: 'Local Travel Mode List',
    }).
    when('/local-travel-mode/add', {
        template: '<eyatra-local-travel-mode-form></eyatra-local-travel-mode-form>',
        title: 'Add Local Travel Mode',
    }).
    when('/local-travel-mode/edit/:travel_mode_id', {
        template: '<eyatra-local-travel-mode-form></eyatra-local-travel-mode-form>',
        title: 'Edit Local Travel Mode',
    }).

    //TRAVEL MODE
    when('/travel-mode/list', {
        template: '<eyatra-travel-mode-list></eyatra-travel-mode-list>',
        title: 'Travel Mode List',
    }).
    when('/travel-mode/add', {
        template: '<eyatra-travel-mode-form></eyatra-travel-mode-form>',
        title: 'Add Travel Mode',
    }).
    when('/travel-mode/edit/:travel_mode_id', {
        template: '<eyatra-travel-mode-form></eyatra-travel-mode-form>',
        title: 'Edit Travel Mode',

    }).

    //REJECTION REASON
    when('/rejection-reason/list', {
        template: '<entity-data-list></entity-data-list>',
        title: 'Rejection Reasons List',
    }).
    when('/rejection-reason/add', {
        template: '<entity-data-form></entity-data-form>',
        title: 'Add Rejection Reason',
    }).
    when('/rejection-reason/edit/:entity_id', {
        template: '<entity-data-form></entity-data-form>',
        title: 'Edit Rejection Reason',
    }).

    //COA SUB MASTERS
    when('/coa-sub-master/list', {
        template: '<coa-data-list></coa-data-list>',
        title: 'COA list',
    }).
    when('/coa-sub-master/add', {
        template: '<coa-data-form></coa-data-form>',
        title: 'Add COA',
    }).
    when('/coa-sub-master/edit/:entity_id', {
        template: '<coa-data-form></coa-data-form>',
        title: 'Edit COA',
    }).

    //ROLES
    when('/roles/list', {
        template: '<eyatra-role-list></eyatra-role-list>',
        title: 'Roles',
    }).
    when('/roles/add', {
        template: '<eyatra-role-form></eyatra-role-form>',
        title: 'Add Role',
    }).
    when('/roles/edit/:id', {
        template: '<eyatra-role-form></eyatra-role-form>',
        title: 'Edit Role',
    }).

    //GRADES
    when('/grades', {
        template: '<eyatra-grades></eyatra-grades>',
        title: 'Grades',
    }).
    when('/grade/add', {
        template: '<eyatra-grade-form></eyatra-grade-form>',
        title: 'Add Grade',
    }).
    when('/grade/edit/:grade_id', {
        template: '<eyatra-grade-form></eyatra-grade-form>',
        title: 'Edit Grade',
    }).
    when('/grade/view/:grade_id', {
        template: '<eyatra-grade-view></eyatra-grade-view>',
        title: 'View Grade',
    }).

    //Coa Codes
    when('/coa-codes', {
        template: '<eyatra-coa-code></eyatra-coa-code>',
        title: 'Coa Codes',
    }).
    when('/coa-code/add', {
        template: '<eyatra-coa-code-form></eyatra-coa-code-form>',
        title: 'Add Coa Codes',
    }).
    when('/coa-code/edit/:coa_code_id', {
        template: '<eyatra-coa-code-form></eyatra-coa-code-form>',
        title: 'Edit Coa Codes',
    }).
    when('/coa-code/view/:coa_code_id', {
        template: '<eyatra-coa-code-view></eyatra-coa-code-view>',
        title: 'View Coa Codes',
    }).


    //AGENTS
    when('/agents', {
        template: '<eyatra-agents></eyatra-agents>',
        title: 'Agents',
    }).
    when('/agent/add', {
        template: '<eyatra-agent-form></eyatra-agent-form>',
        title: 'Add Agent',
    }).
    when('/agent/edit/:agent_id', {
        template: '<eyatra-agent-form></eyatra-agent-form>',
        title: 'Edit Agent',
    }).
    when('/agent/view/:agent_id', {
        template: '<eyatra-agent-view></eyatra-agent-view>',
        title: 'View Agent',
    }).

    //STATES
    when('/states', {
        template: '<eyatra-states></eyatra-states>',
        title: 'States',
    }).
    when('/state/add', {
        template: '<eyatra-state-form></eyatra-state-form>',
        title: 'Add State',
    }).
    when('/state/edit/:state_id', {
        template: '<eyatra-state-form></eyatra-state-form>',
        title: 'Edit State',
    }).
    when('/state/view/:state_id', {
        template: '<eyatra-state-view></eyatra-state-view>',
        title: 'View State',
    }).

    //CITIES
    when('/cities', {
        template: '<eyatra-city></eyatra-city>',
        title: 'Cities',
    }).
    when('/city/add', {
        template: '<eyatra-city-form></eyatra-city-form>',
        title: 'Add City',
    }).
    when('/city/edit/:city_id', {
        template: '<eyatra-city-form></eyatra-city-form>',
        title: 'Edit City',
    }).
    when('/city/view/:city_id', {
        template: '<eyatra-city-view></eyatra-city-view>',
        title: 'View City',
    }).

    //DEASIGNATIONS
    when('/designations', {
        template: '<eyatra-designation></eyatra-designation>',
        title: 'Designations',
    }).
    when('/designation/add', {
        template: '<eyatra-designation-form></eyatra-designation-form>',
        title: 'Add Designation',
    }).
    when('/designation/edit/:designation_id', {
        template: '<eyatra-designation-form></eyatra-designation-form>',
        title: 'Edit Designation',
    }).
    when('/designation/view/:designation_id', {
        template: '<eyatra-designation-view></eyatra-designation-view>',
        title: 'View Designation',
    }).

    //OUTLETS
    when('/outlets', {
        template: '<eyatra-outlets></eyatra-outlets>',
        title: 'Outlets',
    }).
    when('/outlet/add', {
        template: '<eyatra-outlet-form></eyatra-outlet-form>',
        title: 'Add Outlet',
    }).
    when('/outlet/edit/:outlet_id', {
        template: '<eyatra-outlet-form></eyatra-outlet-form>',
        title: 'Edit Outlet',
    }).
    when('/outlet/view/:outlet_id', {
        template: '<eyatra-outlet-view></eyatra-outlet-view>',
        title: 'View Outlet',
    }).

    //COMPANY
    when('/companies', {
        template: '<eyatra-company></eyatra-company>',
        title: 'Companies',
    }).
    when('/company/add', {
        template: '<eyatra-company-form></eyatra-company-form>',
        title: 'Add Company',
    }).
    when('/company/edit/:id', {
        template: '<eyatra-company-form></eyatra-company-form>',
        title: 'Edit Company',
    }).
    when('/company/view/:id', {
        template: '<eyatra-company-view></eyatra-company-view>',
        title: 'View Company',
    }).

    //BUSSINESS
    when('/businesses', {
        template: '<eyatra-business></eyatra-business>',
        title: 'Business',
    }).
    when('/business/add', {
        template: '<eyatra-business-form></eyatra-business-form>',
        title: 'Add Business',
    }).
    when('/business/edit/:id', {
        template: '<eyatra-business-form></eyatra-business-form>',
        title: 'Edit Business',
    }).

    //DEPARTMENT
    when('/departments', {
        template: '<eyatra-department></eyatra-department>',
        title: 'Departments',
    }).
    when('/department/add', {
        template: '<eyatra-department-form></eyatra-department-form>',
        title: 'Add Department',
    }).
    when('/department/edit/:id', {
        template: '<eyatra-department-form></eyatra-department-form>',
        title: 'Edit Department',
    }).
    when('/department/view/:id', {
        template: '<eyatra-department-view></eyatra-department-view>',
        title: 'View Department',
    }).

    //REGIONS
    when('/regions', {
        template: '<eyatra-regions></eyatra-regions>',
        title: 'Regions',
    }).
    when('/region/add', {
        template: '<eyatra-region-form></eyatra-region-form>',
        title: 'Add Region',
    }).
    when('/region/edit/:region_id', {
        template: '<eyatra-region-form></eyatra-region-form>',
        title: 'Edit Region',
    }).
    when('/region/view/:region_id', {
        template: '<eyatra-region-view></eyatra-region-view>',
        title: 'View Region',
    }).

    //EMPLOYEES
    when('/employees', {
        template: '<eyatra-employees></eyatra-employees>',
        title: 'Employees',
    }).
    when('/employee/add', {
        template: '<eyatra-employee-form></eyatra-employee-form>',
        title: 'Add Employee',
    }).
    when('/employee/edit/:employee_id', {
        template: '<eyatra-employee-form></eyatra-employee-form>',
        title: 'Edit Employee',
    }).
    when('/employee/view/:employee_id', {
        template: '<eyatra-employee-view></eyatra-employee-view>',
        title: 'View Employee',
    }).

    //EMPLOYEES IMPORT
    when('/import/job/list', {
        template: '<eyatra-jobs-import-list></eyatra-jobs-import-list>',
        title: 'Import List',
    }).
    when('/import/jobs', {
        template: '<import-jobs></import-jobs>',
        title: 'Import Jobs',
    }).
    //END

    //EXPENSE VOUCHER ADVANCE
    when('/expense/voucher-advance/list', {
        template: '<eyatra-expense-voucher-advance-list></eyatra-expense-voucher-advance-list>',
        title: 'Expense Voucher Advance',
    }).
    when('/expense/voucher-advance/add', {
        template: '<eyatra-expense-voucher-advance-form></eyatra-expense-voucher-advance-form>',
        title: 'Add Expense Voucher Advance',
    }).
    when('/expense/voucher-advance/edit/:id', {
        template: '<eyatra-expense-voucher-advance-form></eyatra-expense-voucher-advance-form>',
        title: 'Edit Expense Voucher Advance',
    }).
    when('/expense/voucher-advance/view/:id', {
        template: '<eyatra-expense-voucher-advance-view></eyatra-expense-voucher-advance-view>',
        title: 'view Expense Voucher Advance',
    }).

    //EXPENSE VOUCHER ADVANCE VERIFICATION MANAGER
    when('/expense/voucher-advance/verification1', {
        template: '<eyatra-expense-voucher-advance-verification-list></eyatra-expense-voucher-advance-verification-list>',
        title: 'Expense Voucher Advance',
    }).
    when('/expense/voucher-advance/verification1/view/:id', {
        template: '<eyatra-expense-voucher-advance-verification-view></eyatra-expense-voucher-advance-verification-view>',
        title: 'view Expense Voucher Advance',
    }).

    //EXPENSE VOUCHER ADVANCE VERIFICATION CASHIER
    when('/expense/voucher-advance/verification2', {
        template: '<eyatra-expense-voucher-advance-verification2-list></eyatra-expense-voucher-advance-verification2-list>',
        title: 'Expense Voucher Advance',
    }).
    when('/expense/voucher-advance/verification2/view/:id', {
        template: '<eyatra-expense-voucher-advance-verification2-view></eyatra-expense-voucher-advance-verification2-view>',
        title: 'view Expense Voucher Advance',
    }).

    //EXPENSE VOUCHER ADVANCE VERIFICATION FINANCIER
    when('/expense/voucher-advance/verification3', {
        template: '<eyatra-expense-voucher-advance-verification3-list></eyatra-expense-voucher-advance-verification3-list>',
        title: 'Expense Voucher Advance',
    }).
    when('/expense/voucher-advance/verification3/view/:id', {
        template: '<eyatra-expense-voucher-advance-verification3-view></eyatra-expense-voucher-advance-verification3-view>',
        title: 'view Expense Voucher Advance',
    }).


    //EXPENSE VOUCHER ADVANCE RE-PAID CASHIER
    when('/expense/voucher-advance/cashier/repaid/list', {
        template: '<eyatra-expense-voucher-advance-cashier-repaid-list></eyatra-expense-voucher-advance-cashier-repaid-list>',
        title: 'Expense Voucher Advance Repaid',
    }).
    when('/expense/voucher-advance/cashier/repaid/view/:id', {
        template: '<eyatra-expense-voucher-advance-cashier-repaid-view></eyatra-expense-voucher-advance-cashier-repaid-view>',
        title: 'view Expense Voucher Advance Repaid',
    }).
    //EXPENSE VOUCHER ADVANCE RE-PAID FINANCIER
    when('/expense/voucher-advance/financier/repaid/list', {
        template: '<eyatra-expense-voucher-advance-financier-repaid-list></eyatra-expense-voucher-advance-financier-repaid-list>',
        title: 'Expense Voucher Advance Repaid',
    }).
    when('/expense/voucher-advance/financier/repaid/view/:id', {
        template: '<eyatra-expense-voucher-advance-financier-repaid-view></eyatra-expense-voucher-advance-financier-repaid-view>',
        title: 'view Expense Voucher Advance Repaid',
    }).

    //TRIP
    when('/trips', {
        template: '<eyatra-trips></eyatra-trips>',
        title: 'Trips',
    }).
    when('/trip/add', {
        template: '<eyatra-trip-form></eyatra-trip-form>',
        title: 'Add Trip',
    }).
    when('/trip/edit/:trip_id', {
        template: '<eyatra-trip-form></eyatra-trip-form>',
        title: 'Edit Trip',
    }).
    when('/trip/view/:trip_id', {
        template: '<eyatra-trip-view></eyatra-trip-view>',
        title: 'View Trip',
    }).
    when('/trip/visit/view/:visit_id', {
        template: '<eyatra-trip-visit-view></eyatra-trip-visit-view>',
        title: 'View Trip',
    }).

    //TRIP CLAIM
    when('/trip/claim/list', {
        template: '<eyatra-trip-claim-list></eyatra-trip-claim-list>',
        title: 'Trip Claim List',
    }).
    when('/trip/claim/add/:trip_id', {
        template: '<eyatra-trip-claim-form></eyatra-trip-claim-form>',
        title: 'Trip Claim Form',
    }).
    when('/trip/claim/edit/:trip_id', {
        template: '<eyatra-trip-claim-form></eyatra-trip-claim-form>',
        title: 'Edit Trip Claim',
    }).
    when('/trip/claim/view/:trip_id', {
        template: '<eyatra-trip-claim-view></eyatra-trip-claim-view>',
        title: 'View Trip Claim',
    }).

    //TRIP APPROVALS
    when('/trip/approvals', {
        template: '<eyatra-trip-approvals></eyatra-trip-approvals>',
        title: 'Trip Approvals',
    }).

    //MANAGER - EMPLOYEE CLAIM VERIFICATION
    when('/trip/claim/verification1/list', {
        template: '<eyatra-trip-claim-verification-one-list></eyatra-trip-claim-verification-one-list>',
        title: 'Employee Claim Verification One',
    }).
    when('/trip/claim/verification1/view/:trip_id', {
        template: '<eyatra-trip-claim-verification-one-view></eyatra-trip-claim-verification-one-view>',
        title: 'View Employee Claim Verification One',
    }).

    //MANAGER - EMPLOYEE CLAIM VERIFICATION
    when('/trip/claim/verification2/list', {
        template: '<eyatra-trip-claim-verification-two-list></eyatra-trip-claim-verification-two-list>',
        title: 'Employee Claim Verification Two',
    }).
    when('/trip/claim/verification2/view/:trip_id', {
        template: '<eyatra-trip-claim-verification-two-view></eyatra-trip-claim-verification-two-view>',
        title: 'View Employee Claim Verification Two',
    }).

    //FINANCIER - EMPLOYEE CLAIM VERIFICATION
    when('/trip/claim/verification3/list', {
        template: '<eyatra-trip-claim-verification-three-list></eyatra-trip-claim-verification-three-list>',
        title: 'Employee Claim Verification Three',
    }).
    when('/trip/claim/verification3/view/:trip_id', {
        template: '<eyatra-trip-claim-verification-three-view></eyatra-trip-claim-verification-three-view>',
        title: 'View Employee Claim Verification Three',
    }).

    //EMPLOYEE - CLAIM PAYMENT PENDING 
    when('/trip/claim/payment-pending/list', {
        template: '<eyatra-trip-claim-payment-pending-list></eyatra-trip-claim-payment-pending-list>',
        title: 'Employee Claim Payment Pending',
    }).

    //TRIP VERIFICATION
    when('/trip/verifications', {
        template: '<eyatra-trip-verifications></eyatra-trip-verifications>',
        title: 'Trips',
    }).
    when('/trip/verification/form/:trip_id', {
        template: '<eyatra-trip-verification-form></eyatra-trip-verification-form>',
        title: 'Trip Verification Form',
    }).

    //ADVANCE CLAIM REQUEST
    when('/advance-claim/requests', {
        template: '<eyatra-advance-claim-requests></eyatra-advance-claim-requests>',
        title: 'Advance Claim Requests',
    }).
    when('/advance-claim/request/form/:trip_id', {
        template: '<eyatra-advance-claim-request-form></eyatra-advance-claim-request-form>',
        title: 'Advance Claim Request Form',
    }).

    //AGENT - REQUEST VIEW
    when('/agent/requests', {
        template: '<eyatra-agent-requests></eyatra-agent-requests>',
        title: 'Agent Requests',
    }).
    when('/agent/request/view/:trip_id', {
        template: '<eyatra-agent-request-form></eyatra-agent-request-form>',
        title: 'Agent Request Form',
    }).


    //AGENT - BOOKING REQUESTS
    when('/trips/booking-requests', {
        template: '<eyatra-trip-booking-requests></eyatra-trip-booking-requests>',
        title: 'Trips Booking Requests',
    }).
    when('/trips/booking-requests/view/:trip_id', {
        template: '<eyatra-trip-booking-requests-view></eyatra-booking-requests-view>',
        title: 'Trip Booking Request View',
    }).

    //AGENT - BOOKING UPDATES
    when('/agent/booking-update/form/:visit_id', {
        template: '<eyatra-agent-booking-update-form></eyatra-agent-booking-update-form>',
        title: 'Agent Booking Update Form',
    }).

    //AGENT - TATKAL BOOKING REQUESTS
    when('/trips/tatkal/booking-requests', {
        template: '<eyatra-trip-tatkal-booking-requests></eyatra-trip-booking-requests>',
        title: 'Tatkal Booking Requests List',
    }).
    when('/trips/tatkal/booking-requests/view/:trip_id', {
        template: '<eyatra-trip-tatkal-booking-requests-view></eyatra-booking-requests-view>',
        title: 'Tatkal Booking Requests View',
    }).

    //AGENT CLAIM
    when('/agent/claim/list', {
        template: '<eyatra-agent-claim-list></eyatra-agent-claim-list>',
        title: 'Agent Claim List',
    }).
    when('/agent/claim/add', {
        template: '<eyatra-agent-claim-form></eyatra-agent-claim-form>',
        title: 'New Agent Claim',
    }).
    when('/agent/claim/edit/:agent_claim_id', {
        template: '<eyatra-agent-claim-form></eyatra-agent-claim-form>',
        title: 'Edit Agent Claim',
    }).
    when('/agent/claim/view/:agent_claim_id', {
        template: '<eyatra-agent-claim-view></eyatra-agent-claim-view>',
        title: 'View Agent Claim',
    }).

    when('/trips/booking/view/:trip_id', {
        template: '<eyatra-trip-booking-view></eyatra-trip-booking-view>',
        title: 'Trip Booking View',
    }).


    //EMPLOYEE - BOOKING UPDATES
    when('/trips/booking-updates', {
        template: '<eyatra-trip-booking-updates></eyatra-trip-booking-updates>',
        title: 'Trips Booking Updates',
    }).
    when('/trips/booking-updates/form/:visit_id', {
        template: '<eyatra-trip-booking-updates-form></eyatra-trip-booking-updates-form>',
        title: 'Employee Booking Update Form',
    }).

    //EMPLOYEE - FINANCE TEAM
    when('/finance-emp-claim', {
        template: '<eyatra-finance-emp-list></eyatra-finance-emp-list>',
        title: 'Finance Employee Claims',
    }).
    when('/finance-emp-claim/add', {
        template: '<eyatra-finance-emp-form></eyatra-finance-emp-form>',
        title: 'Finance Employee Claims Add',
    }).

    //PETTY CASH
    when('/petty-cash', {
        template: '<eyatra-petty-cash-list></eyatra-petty-cash-list>',
        title: 'Petty Cash',
    }).
    when('/petty-cash/add/:type_id', {
        template: '<eyatra-petty-cash-form></eyatra-petty-cash-form>',
        title: 'Add Petty Cash',
    }).
    when('/petty-cash/edit/:type_id/:pettycash_id', {
        template: '<eyatra-petty-cash-form></eyatra-petty-cash-form>',
        title: 'Edit Petty Cash',
    }).
    when('/petty-cash/view/:type_id/:pettycash_id', {
        template: '<eyatra-petty-cash-view></eyatra-petty-cash-view>',
        title: 'View Petty Cash',
    }).

    //PETTY CASH VIEW AND LIST FOR MANAGER
    when('/petty-cash/verification1/', {
        template: '<eyatra-petty-cash-manager-list></eyatra-petty-cash-manager-list>',
        title: 'Petty Cash Manager Verification',
    }).
    when('/petty-cash/verification1/view/:type_id/:pettycash_id', {
        template: '<eyatra-petty-cash-manager-view></eyatra-petty-cash-manager-view>',
        title: 'View Petty Cash Manager Verification',
    }).

    //PETTY CASH VIEW AND LIST FOR CASHIER 
    when('/petty-cash/verification2/', {
        template: '<eyatra-petty-cash-cashier-list></eyatra-petty-cash-cashier-list>',
        title: 'Petty Cash Cashier Verification',
    }).
    when('/petty-cash/verification2/view/:type_id/:pettycash_id', {
        template: '<eyatra-petty-cash-cashier-view></eyatra-petty-cash-cashier-view>',
        title: 'View Petty Cash Cashier Verification',
    }).

    //PETTY CASH VIEW AND LIST FOR FINANCE
    when('/petty-cash/verification3/', {
        template: '<eyatra-petty-cash-finance-list></eyatra-petty-cash-finance-list>',
        title: 'Petty Cash Finance Verification',
    }).
    when('/petty-cash/verification3/view/:type_id/:pettycash_id', {
        template: '<eyatra-petty-cash-finance-view></eyatra-petty-cash-finance-view>',
        title: 'View Petty Cash Finance Verification',
    }).

    //ALTERNATE APPROVE LIST
    when('/alternate-approve', {
        template: '<eyatra-alternate-approve-list></eyatra-alternate-approve-list>',
        title: 'Alternate Approve List',
    }).
    when('/alternate-approve/add', {
        template: '<eyatra-alternate-approve-form></eyatra-alternate-approve-form>',
        title: 'Add Alternate Approve',
    }).
    when('/alternate-approve/edit/:alternate_id', {
        template: '<eyatra-alternate-approve-form></eyatra-alternate-approve-form>',
        title: 'Edit Alternate Approve',
    }).

    //ADMIN - OUTLET - OUTLET REIMBURSEMENT
    when('/outlet-reimbursement', {
        template: '<eyatra-outlet-reimbursement></eyatra-outlet-reimbursement>',
        title: 'Reimbursements',
    }).
    when('/outlet-reimbursement/add', {
        template: '<eyatra-outlet-reimbursement-form></eyatra-outlet-reimbursement-form>',
        title: 'Add Region',
    }).
    when('/outlet-reimbursement/edit/:outlet_id', {
        template: '<eyatra-outlet-reimbursement-form></eyatra-outlet-reimbursement-form>',
        title: 'Edit Reimbursement',
    }).
    when('/outlet-reimbursement/view/:outlet_id', {
        template: '<eyatra-outlet-reimbursement-view></eyatra-outlet-reimbursement-view>',
        title: 'View Reimbursement',
    }).
    //CASHIER - OUTLET REIMBURSEMENT
    when('/outlet-reimbursement/list', {
        template: '<eyatra-cashier-outlet-reimbursement></eyatra-cashier-outlet-reimbursement>',
        title: 'Cashier Reimbursements',
    }).
    when('/cashier-outlet-reimbursement/view', {
        template: '<eyatra-cashier-outlet-reimbursement-view></eyatra-cashier-outlet-reimbursement-view>',
        title: 'View Reimbursement',
    }).

    //FINANCIER - AGENT CLAIM
    when('/agent/claim/verification1/list', {
        template: '<eyatra-agent-claim-verification-list></eyatra-agent-claim-verification-list>',
        title: 'Agent Claim Verification',
    }).
    when('/finance/agent/claim/view/:agent_claim_id', {
        template: '<eyatra-agent-claim-verification-view></eyatra-agent-claim-verification-view>',
        title: 'View Agent Claim',
    }).

    //LOCAL TRIP
    when('/local-trip/list', {
        template: '<eyatra-local-trips></eyatra-local-trips>',
        title: 'Local Trips',
    }).
    when('/local-trip/add', {
        template: '<eyatra-trip-local-form></eyatra-trip-local-form>',
        title: 'Add Local Trip',
    }).
    when('/local-trip/edit/:trip_id', {
        template: '<eyatra-trip-local-form></eyatra-trip-local-form>',
        title: 'Edit Local Trip',
    }).

    when('/local-trip/view/:trip_id', {
        template: '<eyatra-trip-local-view></eyatra-trip-local-view>',
        title: 'View Local Trip',
    }).

    //LOCAL TRIP CLAIM
    when('/local-trip/claim/list', {
        template: '<eyatra-claimed-local-trips></eyatra-claimed-local-trips>',
        title: 'Local Trip Claim List',
    }).
    when('/local-trip/claim/add/:trip_id', {
        template: '<eyatra-local-trip-claim-form></eyatra-local-trip-claim-form>',
        title: 'Local Trip Claim Form',
    }).
    when('/local-trip/claim/edit/:trip_id', {
        template: '<eyatra-local-trip-claim-form></eyatra-local-trip-claim-form>',
        title: 'Local Trip Claim Form',
    }).
    when('/local-trip/claim/view/:trip_id', {
        template: '<eyatra-local-trip-claim-view></eyatra-local-trip-claim-view>',
        title: 'View Local Trip Claim',
    }).


    //LOCAL TRIP MANAGER VERIFICATION
    when('/local-trip/verification/list', {
        template: '<eyatra-local-trip-verifications></eyatra-local-trip-verifications>',
        title: 'Local Trips Verification',
    }).
    when('/local-trip/verification/view/:trip_id', {
        template: '<eyatra-local-trip-verification-view></eyatra-local-trip-verification-view>',
        title: 'Lcoal Trip Verification View',
    }).
    when('/local-trip/verification/detail-view/:trip_id', {
        template: '<eyatra-local-trip-verification-detail-view></eyatra-local-trip-verification-detail-view>',
        title: 'View Local Trip',
    }).

    //LOCAL TRIP FINANCIER VERIFICATION
    when('/local-trip/financier/verification/list', {
        template: '<eyatra-local-trip-financier-verification></eyatra-local-trip-financier-verification>',
        title: 'Local Trips',
    }).
    when('/local-trip/financier/verification/view/:trip_id', {
        template: '<eyatra-local-trip-financier-verification-view></eyatra-local-trip-financier-verification-view>',
        title: 'Lcoal Trip Verification View',
    }).

    //REPORT
    //OUTSTATION TRIP REPORT
    when('/report/outstation-trip/list', {
        template: '<eyatra-outstation-trip></eyatra-outstation-trip>',
        title: 'Outstation Trip Reports',
    }).
    //LOCAL TRIP REPORT
    when('/report/local-trip/list', {
        template: '<eyatra-local-trip></eyatra-local-trip>',
        title: 'Local Trip Reports',
    }).

    //APPROVAL LOGS >> OUTSTATION TRIP
    when('/reports/outstation-trip', {
        template: '<eyatra-outstation-trip-list></eyatra-outstation-trip-list>',
        title: 'Outstation Trip',
    }).
    //APPROVAL LOGS >> OUTSTATION TRIP VIEW
    when('/outstation-trip/view/:trip_id', {
        template: '<eyatra-outstation-trip-view></eyatra-outstation-trip-view>',
        title: 'Outstation Trip View',
    }).
    //APPROVAL LOGS >> OUTSTATION TRIP CLAIM VIEW
    when('/outstation-claim/view/:claim_id', {
        template: '<eyatra-outstation-claim-view></eyatra-outstation-claim-view>',
        title: 'Outstation Claim View',
    }).

    //APPROVAL LOGS >> LOCAL TRIP
    when('/reports/local-trip', {
        template: '<eyatra-reports-local-trip-list></eyatra-reports-local-trip-list>',
        title: 'Local Trip',
    }).
    //APPROVAL LOGS >> LOCAL TRIP VIEW
    when('/report/local-trip/view/:trip_id', {
        template: '<eyatra-reports-local-trip-view></eyatra-reports-local-trip-view>',
        title: 'Local Trip View',
    }).
    //APPROVAL LOGS >> LOCAL TRIP CLAIM VIEW
    when('/report/local-trip-claim/view/:trip_id', {
        template: '<eyatra-reports-local-trip-claim-view></eyatra-reports-local-trip-claim-view>',
        title: 'Local Trip Claim View',
    }).
    //APPROVAL LOGS >> TRIP ADVANCE REQUEST
    when('/reports/trip-advance-request', {
        template: '<eyatra-reports-trip-advance-request></eyatra-reports-trip-advance-request>',
        title: 'Trip Advance Request',
    }).
    //APPROVAL LOGS >> SR MANAGER APPROVAL
    when('/reports/sr-manager-approval', {
        template: '<eyatra-reports-trip-sr-manager-approval></eyatra-reports-trip-sr-manager-approval>',
        title: 'Claim Report',
    }).
    //APPROVAL LOGS >>  FINANCIER APPROVAL
    when('/reports/financier-approval', {
        template: '<eyatra-reports-trip-financier-approval></eyatra-reports-trip-financier-approval>',
        title: 'Financier Approval Report',
    }).
    //APPROVAL LOGS >>  FINANCIER PAID OUTSTATION TRIP
    when('/reports/financier-paid', {
        template: '<eyatra-reports-trip-financier-paid></eyatra-reports-trip-financier-paid>',
        title: 'Financier Paid Report',
    }).
    //APPROVAL LOGS >>  EMPLOYEE PAID
    when('/reports/employee-paid', {
        template: '<eyatra-reports-trip-employee-paid></eyatra-reports-trip-employee-paid>',
        title: 'Employee Paid Report',
    }).
    //APPROVAL LOGS >>  FINANCIER PAID LOCAL TRIP
    when('/reports/local-trip-financier-paid', {
        template: '<eyatra-reports-local-trip-financier-paid></eyatra-reports-local-trip-financier-paid>',
        title: 'Local Trip Claim List',
    }).

    //APPROVAL LOGS >> PETTY CASH MANAGER APPROVED
    when('/reports/expense-voucher/:type_id', {
        template: '<eyatra-reports-petty-cash-manager></eyatra-reports-petty-cash-manager>',
        title: 'Expense Voucher',
    }).
    //APPROVAL LOGS >> EXPENSE VOUCHER ADVANCE APPROVED
    when('/reports/expense-voucher-advances/:type_id', {
        template: '<eyatra-reports-expense-voucher-advance></eyatra-reports-expense-voucher-advance>',
        title: 'Expense Voucher Advance',
    }).
    //APPROVAL LOGS >> EXPENSE VOUCHER ADVANCE RE-PAID
    when('/reports/expense-voucher-advances-repaid/:type_id', {
        template: '<eyatra-reports-expense-voucher-advance-repaid></eyatra-reports-expense-voucher-advance>',
        title: 'Expense Voucher Advance Repaid',
    }).
    //APPROVAL LOGS >> VERIFIER >> OUTSTATION TRIP
    when('/reports/verifier/outstation-trip', {
        template: '<eyatra-reports-verifier-outstation-trip-approval></eyatra-reports-verifier-outstation-trip-approval>',
        title: 'Outstation Trip Claim Report',
    }).
    //APPROVAL LOGS >> VERIFIER >> LOCAL TRIP
    when('/reports/verifier/local-trip', {
        template: '<eyatra-reports-verifier-local-trip-approval></eyatra-reports-verifier-local-trip-approval>',
        title: 'Local/ Trip Claim Report',
    }).

    //CLAIM VERIFIER - EMPLOYEE CLAIM VERIFICATION
    when('/outstation-trip/claim/verification/list', {
        template: '<eyatra-outstation-claim-verification-list></eyatra-outstation-claim-verification-list>',
        title: 'Outstation Trip Claim Verification',
    }).
    when('/outstation-trip/claim/verification/view/:trip_id', {
        template: '<eyatra-outstation-claim-verification-view></eyatra-outstation-claim-verification-view>',
        title: 'View Outstation Trip Claim Verification',
    }).
    when('/local-trip/claim/verification/list', {
        template: '<eyatra-local-claim-verification-list></eyatra-local-claim-verification-list>',
        title: 'Local Trip Claim Verification',
    }).
    when('/local-trip/claim/verification/view/:trip_id', {
        template: '<eyatra-local-claim-verification-view></eyatra-local-claim-verification-view>',
        title: 'Local Trip Claim Verification',
    }).
    when('/gst/report', {
        template: '<eyatra-gst-report></eyatra-gst-report>',
        title: 'GST Report',
    }).

    when('/employee/gstr/report', {
        template: '<eyatra-employee-gstr-report></eyatra-employee-gstr-report>',
        title: 'GSTR2 Report',
    }).

    when('/agent/report', {
        template: '<eyatra-agent-report></eyatra-agent-report>',
        title: 'Agent Report',
    }).
    when('/report/list', {
        template: '<eyatra-report-list></eyatra-report-list>',
        title: 'Report List',
    }).
    when('/report/view', {
        template: '<eyatra-report-view></eyatra-report-view>',
        title: 'Report View',
    }).
    when('/shared-claim/detail', {
        template: '<shared-claim-detail></shared-claim-detail>',
        title: 'Shared Claim Detail',
    })

    //HRMS TO TRAVELEX EMPLOYEE SYNC
    .when('/hrms-employee-sync/log-list/:type_id', {
        template: '<hrms-employee-sync-log-list></hrms-employee-sync-log-list>',
        title: 'HRMS Employee Sync Log List',
    })
    //HRMS EMPLOYEE MANUAL ADDITION
    .when('/hrms/employee-manual-addition', {
        template: '<hrms-employee-manual-addition></hrms-employee-manual-addition>',
        title: 'HRMS Employee Manual Addition',
    })

    .when('/trip/report', {
        template: '<eyatra-trip-report></eyatra-trip-report>',
        title: 'Trip Report',
    });
}]);