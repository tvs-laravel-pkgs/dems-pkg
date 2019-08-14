app.component('eyatraTrips', {
    templateUrl: eyatra_trip_list_template_url,
    controller: function(HelperService, $rootScope, $scope, $http) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        var dataTable = $('#eyatra_trip_table').DataTable({
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
                url: laravel_routes['listTrip'],
                type: "GET",
                dataType: "json",
                data: function(d) {}
            },

            columns: [
                { data: 'action', searchable: false, class: 'action' },
                { data: 'number', name: 'trips.number', searchable: true },
                { data: 'ecode', name: 'e.code', searchable: true },
                { data: 'start_date', name: 'v.date', searchable: true },
                { data: 'end_date', name: 'v.date', searchable: true },
                { data: 'cities', name: 'c.name', searchable: true },
                { data: 'purpose', name: 'purpose.name', searchable: true },
                { data: 'advance_received', name: 'trips.advance_received', searchable: false },
                { data: 'status', name: 'status.name', searchable: true },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();
        $('.page-header-content .display-inline-block .data-table-title').html('Trips');
        $('.add_new_button').html(
            '<a href="#!/eyatra/trip/add" type="button" class="btn btn-secondary" ng-show="$ctrl.hasPermission(\'add-trip\')">' +
            'Add New' +
            '</a>'
        );

        $scope.deleteTrip = function(id) {
            $('#del').val(id);
        }
        $scope.confirmDeleteTrip = function() {
            $id = $('#del').val();
            $http.get(
                eyatra_trip_claim_delete_url + '/' + $id,
            ).then(function(response) {
                if (!response.data.success) {
                    var errors = '';
                    for (var i in res.errors) {
                        errors += '<li>' + res.errors[i] + '</li>';
                    }
                    new Noty({
                        type: 'error',
                        layout: 'topRight',
                        text: errors
                    }).show();
                } else {
                    new Noty({
                        type: 'success',
                        layout: 'topRight',
                        text: 'Trips Deleted Successfully',
                    }).show();
                    $('#delete_emp').modal('hide');
                    dataTable.ajax.reload(function(json) {});
                }

            });
        }

        $rootScope.loading = false;

    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('eyatraTripForm', {
    templateUrl: trip_form_template_url,
    controller: function($http, $location, $location, HelperService, $routeParams, $rootScope, $scope) {
        $form_data_url = typeof($routeParams.trip_id) == 'undefined' ? trip_form_data_url : trip_form_data_url + '/' + $routeParams.trip_id;
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
                $location.path('/eyatra/trips')
                $scope.$apply()
                return;
            }
            self.trip = response.data.trip;
            self.extras = response.data.extras;
            self.action = response.data.action;
            $rootScope.loading = false;

        });

        self.searchCity = function(query) {
            if (query) {
                return new Promise(function(resolve, reject) {
                    $http
                        .post(
                            laravel_routes['searchCity'], {
                                key: query,
                            }
                        )
                        .then(function(response) {
                            resolve(response.data);
                        });
                });
            } else {
                return [];
            }
        }

        self.addVisit = function() {
            self.trip.visits.push({
                visit_date: '',
                booking_method: 'Self',
                preferred_travel_modes: '',
            });
        }

        var form_id = '#trip-form';
        var v = jQuery(form_id).validate({
            errorPlacement: function(error, element) {
                error.insertAfter(element)
            },
            ignore: '',
            rules: {
                'purpose_id': {
                    required: true,
                },
                'description': {
                    maxlength: 255,
                },
                'advance_received': {
                    maxlength: 10,
                },
            },
            messages: {
                'description': {
                    maxlength: 'Please enter maximum of 255 letters',
                },
            },
            submitHandler: function(form) {

                let formData = new FormData($(form_id)[0]);
                $('#submit').button('loading');
                $.ajax({
                        url: laravel_routes['saveTrip'],
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
                                text: 'Trip saves successfully',
                            }).show();
                            $location.path('/eyatra/trips')
                            $scope.$apply()
                        }
                    })
                    .fail(function(xhr) {
                        $('#submit').button('reset');
                        custom_noty('error', 'Something went wrong at server');
                    });
            },
        });
    }
});

app.component('eyatraTripView', {
    templateUrl: trip_view_template_url,

    controller: function($http, $location, $routeParams, HelperService, $scope, $route) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        $http.get(
            trip_view_url + '/' + $routeParams.trip_id
        ).then(function(response) {
            self.trip = response.data.trip;
        });

        //REQUEST AGENT FOR CANCEL VISIT BOOKING
        $scope.requestVisitBookingPopup = function(visit_id) {
            $('#booking_visit_id').val(visit_id);
        }

        $scope.requestCancelBooking = function() {
            var cancel_booking_visit_id = $('#booking_visit_id').val();
            if (cancel_booking_visit_id) {
                $.ajax({
                        url: trip_visit_request_cancel_booking_url + '/' + cancel_booking_visit_id,
                        method: "GET",
                    })
                    .done(function(res) {
                        console.log(res);
                        if (!res.success) {
                            var errors = '';
                            for (var i in res.errors) {
                                errors += '<li>' + res.errors[i] + '</li>';
                            }
                            custom_noty('error', errors);
                        } else {
                            new Noty({
                                type: 'success',
                                layout: 'topRight',
                                text: 'Booking cancelled successfully',
                            }).show();
                            $route.reload();
                        }
                    })
                    .fail(function(xhr) {
                        console.log(xhr);
                    });
            }
        }

        //CANCEL VISIT BOOKING
        $scope.cancelVisitBookingPopup = function(visit_id) {
            $('#cancel_booking_visit_id').val(visit_id);
        }

        $scope.cancelVisitBooking = function() {
            var cancel_booking_visit_id = $('#cancel_booking_visit_id').val();
            if (cancel_booking_visit_id) {
                $.ajax({
                        url: trip_visit_cancel_booking_url + '/' + cancel_booking_visit_id,
                        method: "GET",
                    })
                    .done(function(res) {
                        console.log(res);
                        if (!res.success) {
                            var errors = '';
                            for (var i in res.errors) {
                                errors += '<li>' + res.errors[i] + '</li>';
                            }
                            custom_noty('error', errors);
                        } else {
                            new Noty({
                                type: 'success',
                                layout: 'topRight',
                                text: 'Booking cancelled successfully',
                            }).show();
                            $route.reload();
                        }
                    })
                    .fail(function(xhr) {
                        console.log(xhr);
                    });
            }
        }

        //DELETE TRIP
        $scope.deleteTrip = function(id) {
            $('#del').val(id);
        }
        $scope.confirmDeleteTrip = function() {
            $id = $('#del').val();
            $http.get(
                trip_delete_url + '/' + $id,
            ).then(function(response) {
                if (!response.data.success) {
                    var errors = '';
                    for (var i in res.errors) {
                        errors += '<li>' + res.errors[i] + '</li>';
                    }
                    new Noty({
                        type: 'error',
                        layout: 'topRight',
                        text: errors
                    }).show();
                } else {
                    new Noty({
                        type: 'success',
                        layout: 'topRight',
                        text: 'Trips Deleted Successfully',
                    }).show();
                    $location.path('/eyatra/trips')
                    $scope.$apply()
                }

            });
        }

        self.verificationRequest = function(trip_id) {
            $.ajax({
                    url: trip_verification_request_url + '/' + trip_id,
                    method: "GET",
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
                            text: 'Trip successfully sent for verification',
                        }).show();
                        $location.path('/eyatra/trips')
                        $scope.$apply()
                    }
                })
                .fail(function(xhr) {
                    $('#submit').button('reset');
                    custom_noty('error', 'Something went wrong at server');
                });
        }

        //CANCEL TRIP
        $scope.confirmCancelTrip = function() {
            $id = $('#trip_id').val();

            $http.get(
                trip_cancel_url + '/' + $id,
            ).then(function(response) {
                if (!response.data.success) {
                    var errors = '';
                    for (var i in res.errors) {
                        errors += '<li>' + res.errors[i] + '</li>';
                    }
                    new Noty({
                        type: 'error',
                        layout: 'topRight',
                        text: errors
                    }).show();
                } else {
                    $('#cancel_trip').modal('hide');
                    new Noty({
                        type: 'success',
                        layout: 'topRight',
                        text: 'Trip Cancelled Successfully',
                    }).show();
                    setTimeout(function() {
                        $location.path('/eyatra/trips')
                        $scope.$apply()
                    }, 500);

                }

            });
        }

    }
});

app.component('eyatraTripVisitView', {
    templateUrl: trip_visit_view_template_url,
    controller: function($http, $location, $location, HelperService, $routeParams, $rootScope, $scope) {
        if (typeof($routeParams.visit_id) == 'undefined') {
            $location.path('/eyatra/trips')
            $scope.$apply()
            return;
        }
        $form_data_url = trip_visit_view_form_data_url + '/' + $routeParams.visit_id;
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
                $location.path('/eyatra/trips')
                $scope.$apply()
                return;
            }
            self.visit = response.data.visit;
            self.trip = response.data.trip;
            self.bookings = response.data.bookings;
            console.log(response.data.trip);
            $rootScope.loading = false;
        });

        //Tab Navigation
        $('.btn-nxt').on("click", function() {
            $('.editDetails-tabs li.active').next().children('a').trigger("click");
        });
        $('.btn-prev').on("click", function() {
            $('.editDetails-tabs li.active').prev().children('a').trigger("click");
        });
    }
});


//------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------