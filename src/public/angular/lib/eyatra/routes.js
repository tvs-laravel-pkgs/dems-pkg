app.config(['$routeProvider', function($routeProvider) {

    $routeProvider.

    //EMPLOYEES
    when('/eyatra/master/employees', {
        template: '<eyatra-employee></eyatra-employee>',
        title: 'Trips',
    }).
    when('/eyatra/master/employees/add', {
        template: '<eyatra-employee-form></eyatra-employee-form>',
        title: 'Add Trip',
    }).
    when('/eyatra/master/employees/edit/:employee_id', {
        template: '<eyatra-employee-form></eyatra-employee-form>',
        title: 'Edit Trip',
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
        title: 'Add Trip',
    });
}]);