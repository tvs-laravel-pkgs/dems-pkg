app.component('eyatraTripBookingRequests', {
    templateUrl: eyatra_booking_requests_list_template_url,
    controller: function(HelperService, $rootScope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        var dataTable = $('#eyatra_trip_booking_requests_table').DataTable({
            stateSave: true,
            "dom": dom_structure,
            "language": {
                "search": "",
                "searchPlaceholder": "Search",
                "lengthMenu": "Rows Per Page _MENU_",
                "paginate": {
                    "next": '<i class="icon ion-ios-arrow-forward"></i>',
                    "previous": '<i class="icon ion-ios-arrow-back"></i>'
                },
            },
            pageLength: 10,
            processing: true,
            serverSide: true,
            paging: true,
            ordering: false,
            ajax: {
                url: laravel_routes['listTripBookingRequests'],
                type: "GET",
                dataType: "json",
                data: function(d) {}
            },
            columns: [
                { data: 'action', searchable: false, class: 'action' },
                { data: 'number', name: 't.number', searchable: true },
                { data: 'ecode', name: 'e.code', searchable: true },
                { data: 'date', name: 'v.date', searchable: true },
                { data: 'from', name: 'fc.name', searchable: true },
                { data: 'to', name: 'tc.name', searchable: true },
                { data: 'travel_mode', name: 'tm.name', searchable: true },
                { data: 'booking_status', name: 'bs.name', searchable: false },
                { data: 'agent', name: 'a.name', searchable: true },
                { data: 'status', name: 'status.name', searchable: true },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();
        $('.page-header-content .display-inline-block .data-table-title').html('Visits');
        $('.add_new_button').html();
        $rootScope.loading = false;

    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('eyatraTripBookingRequestsView', {
    templateUrl: eyatra_booking_requests_view_template_url,
    controller: function($http, $location, $location, HelperService, $routeParams, $rootScope, $scope, $route) {
        if (typeof($routeParams.trip_id) == 'undefined') {
            $location.path('/eyatra/trips/booking-requests')
            $scope.$apply()
            return;
        }
        $form_data_url = eyatra_booking_requests_view_data_url + '/' + $routeParams.trip_id;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        $http.get(
            $form_data_url
        ).then(function(response) {
            if (!response.data.success) {
                new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: response.data.error,
                }).show();
                $location.path('/eyatra/trips/booking-requests')
                $scope.$apply()
                return;
            }
            self.visit = response.data.visit;
            self.trip = response.data.trip;
            self.bookings = response.data.bookings;
            self.travel_mode_list = response.data.travel_mode;
            self.extras = response.data.extras;
            self.action = response.data.action;
            $rootScope.loading = false;

        });

        self.approveTrip = function() {
            self.trip.visits.push({
                visit_date: '',
                booking_method: 'Self',
                preferred_travel_modes: '',
            });
        }

        //Tab Navigation
        $('.btn-nxt').on("click", function() {
            $('.editDetails-tabs li.active').next().children('a').trigger("click");
        });
        $('.btn-prev').on("click", function() {
            $('.editDetails-tabs li.active').prev().children('a').trigger("click");
        });

        //Add Booking Form Show/Hide
        $scope.ShowForm = function() {
            $scope.IsVisible = $scope.IsVisible ? false : true;
        }

        //Add Booking Cancel Form Show/Hide
        $scope.ShowCancelForm = function() {
            $scope.CancelFormIsVisible = $scope.CancelFormIsVisible ? false : true;
        }

        $(document).on('click', '.add_booking', function() {
            $('.add_booking').hide();
        });

        $(document).on('click', '.cancel_booking', function() {
            $('.cancel_booking').hide();
        });

        //Booking Form
        $(document).on('click', '#submit', function() {
            var form_id = '#trip-booking-updates-form';
            var v = jQuery(form_id).validate({
                errorPlacement: function(error, element) {
                    error.insertAfter(element)
                },
                ignore: '',
                rules: {
                    'travel_mode_id': {
                        required: true,
                    },
                    'reference_number': {
                        required: true,
                        maxlength: 191,
                    },
                    'amount': {
                        required: true,
                        maxlength: 10,
                        number: true,
                    },
                    'tax': {
                        required: true,
                        maxlength: 10,
                        number: true,
                    },
                },
                submitHandler: function(form) {

                    let formData = new FormData($(form_id)[0]);
                    $('#submit').button('loading');
                    // var trip_id = $('#trip_id').val();
                    $.ajax({
                            url: laravel_routes['saveTripBookingUpdates'],
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                        })
                        .done(function(res) {
                            console.log(res.success);
                            if (!res.success) {
                                $('#submit').button('reset');
                                var errors = '';
                                for (var i in res.errors) {
                                    errors += '<li>' + res.errors[i] + '</li>';
                                }
                                custom_noty('error', errors);
                            } else {
                                new Noty({
                                    type: 'success',
                                    layout: 'topRight',
                                    text: 'Booking details updated successfully',
                                }).show();
                                // $location.path('/eyatra/trips/booking-requests/view/' + $routeParams.trip_id)
                                $route.reload();
                                $scope.$apply()
                            }
                        })
                        .fail(function(xhr) {
                            $('#submit').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                },
            });
        });

        //Cancellation Form
        $(document).on('click', '#cancellation', function() {
            var form_id = '#trip-booking-cancellation-form';
            var v = jQuery(form_id).validate({
                errorPlacement: function(error, element) {
                    error.insertAfter(element)
                },
                ignore: '',
                rules: {

                    'reference_number': {
                        required: true,
                        maxlength: 191,
                    },
                    'amount': {
                        required: true,
                        maxlength: 10,
                        number: true,
                    },
                    'tax': {
                        required: true,
                        maxlength: 10,
                        number: true,
                    },
                },
                submitHandler: function(form) {

                    let formData = new FormData($(form_id)[0]);
                    $('#cancellation').button('loading');
                    $.ajax({
                            url: laravel_routes['saveTripBookingUpdates'],
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                        })
                        .done(function(res) {
                            console.log(res.success);
                            if (!res.success) {
                                $('#cancellation').button('reset');
                                var errors = '';
                                for (var i in res.errors) {
                                    errors += '<li>' + res.errors[i] + '</li>';
                                }
                                custom_noty('error', errors);
                            } else {
                                new Noty({
                                    type: 'success',
                                    layout: 'topRight',
                                    text: 'Cancellation Booking details updated successfully',
                                }).show();
                                // $location.path('/eyatra/trips/booking-requests/view/' + $routeParams.trip_id)
                                $route.reload();
                                $scope.$apply()
                            }
                        })
                        .fail(function(xhr) {
                            $('#cancellation').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                },
            });
        });
    }
});