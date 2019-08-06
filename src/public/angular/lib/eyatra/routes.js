app.config(['$routeProvider', function($routeProvider) {

    $routeProvider.

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
    when('/eyatra/agent/view/:grade_id', {
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

    //EMPLOYEES
    when('/eyatra/employees', {
        template: '<eyatra-employees></eyatra-employees>',
        title: 'Employees',
    }).
    when('/eyatra/employee/add', {
        template: '<eyatra-employee-form></eyatra-employee-form>',
        title: 'Add Employee',
    }).
    when('/eyatra/employee/edit/:outlet_id', {
        template: '<eyatra-employee-form></eyatra-employee-form>',
        title: 'Edit Employee',
    }).
    when('/eyatra/employee/view/:outlet_id', {
        template: '<eyatra-employee-view></eyatra-employee-view>',
        title: 'View Employee',
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

    //TRIP VERIFICATION
    when('/eyatra/trip/verifications', {
        template: '<eyatra-trip-verifications></eyatra-trip-verifications>',
        title: 'Trips',
    }).
    when('/eyatra/trip/verification/form/:trip_id', {
        template: '<eyatra-trip-verification-form></eyatra-trip-verification-form>',
        title: 'Trip Verification Form',
    }).

    //BOOKING REQUESTS
    when('/eyatra/trips/booking-requests', {
        template: '<eyatra-trip-booking-requests></eyatra-trip-booking-requests>',
        title: 'Trips Booking Requests',
    }).
    when('/eyatra/trips/booking-requests/view/:trip_id', {
        template: '<eyatra-trip-booking-requests-view></eyatra-booking-requests-view>',
        title: 'Trip Booking Request View',
    }).

    //BOOKING UPDATES
    when('/eyatra/trips/booking-updates', {
        template: '<eyatra-trip-booking-updates></eyatra-trip-booking-updates>',
        title: 'Trips Booking Updates',
    }).
    when('/eyatra/trips/booking-updates/form/:visit_id', {
        template: '<eyatra-trip-booking-updates-form></eyatra-booking-updates-form>',
        title: 'Trip Booking Update View',
    });
}]);