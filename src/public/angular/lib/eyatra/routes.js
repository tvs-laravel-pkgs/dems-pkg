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
        title: 'Edit Trip',
    });
}]);