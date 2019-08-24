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
                { data: 'trip_id', name: 'trips.id', searchable: true },
                { data: 'ename', name: 'e.name', searchable: true },
                { data: 'ecode', name: 'e.code', searchable: true },
                { data: 'agent', name: 'a.name', searchable: true },
                { data: 'status', name: 'status.name', searchable: true },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();
        $('.page-header-content .display-inline-block .data-table-title').html('Trip Requests');
        $('.add_new_button').html();
        $rootScope.loading = false;

    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('eyatraTripBookingRequestsView', {
    templateUrl: agent_request_form_template_url,
    controller: function($http, $location, $location, HelperService, $routeParams, $rootScope, $scope, $timeout, $route) {
        if (typeof($routeParams.trip_id) == 'undefined') {
            $location.path('/eyatra/agent/requests')
            $scope.$apply()
            return;
        }
        $form_data_url = agent_request_form_data_url + '/' + $routeParams.trip_id;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;

        $scope.showBookingForm = true;
        $scope.showCancelForm = false;
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
            if (!response.data.trip.visits || response.data.trip.visits.length == 0) {
                new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: response.data.error,
                }).show();
                $location.path('/eyatra/trips/booking-requests')
                $scope.$apply()
                return;
            }
            self.trip = response.data.trip;
            self.total_amount = response.data.total_amount;
            self.ticket_amount = response.data.ticket_amount;
            self.service_charge = response.data.service_charge;
            self.trip_status = response.data.trip_status;
            self.travel_mode_list = response.data.travel_mode_list;
            self.action = response.data.action;
            $rootScope.loading = false;

        });

        $scope.userDetailId = 0;
        $scope.showUserDetail = function(id) {
            $("#open_cancel_form_" + id).hide();
            $("#close_" + id).show();
            $scope.userDetailId = id;
        }

        $(document).on('click', '.close_icon', function() {
            var id = $(this).attr('data-visit_id');
            $scope.userDetailId = 0;
            $("#open_cancel_form_" + id).show();
            $("#close_" + id).hide();
        });

        $(document).on('click', '.booking_cancel', function() {
            var form_id = '#visit-booking-cancel-form';
            var v = jQuery(form_id).validate({
                errorPlacement: function(error, element) {
                    error.insertAfter(element)
                },
                ignore: '',
                submitHandler: function(form) {

                    let formData = new FormData($(form_id)[0]);
                    $('#cancel').button('loading');
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
                                $('#cancel').button('reset');
                                var errors = '';
                                for (var i in res.errors) {
                                    errors += '<li>' + res.errors[i] + '</li>';
                                }
                                custom_noty('error', errors);
                            } else {
                                new Noty({
                                    type: 'success',
                                    layout: 'topRight',
                                    text: 'Booking details updated successfully!',
                                }).show();
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
        $(document).on('click', '.submit', function() {
            var form_id = '#trip-booking-updates-form';
            var v = jQuery(form_id).validate({
                errorPlacement: function(error, element) {
                    error.insertAfter(element)
                },
                ignore: '',
                rules: {
                    'ticket_booking[][ticket_amount]': {
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
        
        // Select and loop the container element of the elements you want to equalise
        setTimeout(function () {
            // Cache the highest
            var highestBox = new Array();
            // Loop to get all element Widths
            $('.match-height').each(function() { 
                // Need to let sizes be whatever they want so no overflow on resize   
                // Then add size (no units) to array
                highestBox.push($(this).height());
            });
            // Find max Width of all elements
            var max = Math.max.apply( Math, highestBox );
            // Set the height of all those children to whichever was highest 
            $('.match-height').height(max);                

            // Cache the highest
            var highestBox_1 = new Array();
            // Loop to get all element Widths
            $('.match-height-1').each(function() {    
                // Need to let sizes be whatever they want so no overflow on resize
                // Then add size (no units) to array
                highestBox_1.push($(this).height());
            });
            // Find max Width of all elements
            var max_1 = Math.max.apply( Math, highestBox_1 );
            // Set the height of all those children to whichever was highest 
            $('.match-height-1').height(max_1);

        }, 500);

        self.approveTrip = function() {
            self.trip.visits.push({
                visit_date: '',
                booking_method: 'Self',
                preferred_travel_modes: '',
            });
        }

        //APPROVE TRIP
        self.approveTrip = function(id) {
            $('#trip_id').val(id);
        }

        self.confirmApproveTrip = function() {
            $id = $('#trip_id').val();
            $http.get(
                trip_verification_approve_url + '/' + $id,
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
                        text: 'Trip Approved Successfully',
                    }).show();
                    $('#approval_modal').modal('hide');
                    $timeout(function() {
                        $location.path('/eyatra/trip/verifications')
                        $scope.$apply()
                    }, 500);
                }

            });
        }

        //REJECT TRIP
        self.rejectTrip = function(id, type) {
            $('#trip_id').val(id);
        }

        self.confirmRejectTrip = function() {
            $id = $('#trip_id').val();
            $http.get(
                trip_verification_reject_url + '/' + $id,
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
                        text: 'Trip Rejected Successfully',
                    }).show();
                    $('#reject_modal').modal('hide');
                    $timeout(function() {
                        $location.path('/eyatra/trip/verifications')
                        $scope.$apply()
                    }, 500);
                }

            });
        }



    }
});