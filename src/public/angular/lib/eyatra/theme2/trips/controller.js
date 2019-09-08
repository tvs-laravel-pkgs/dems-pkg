app.component('eyatraTrips', {
    templateUrl: eyatra_trip_list_template_url,
    controller: function(HelperService, $rootScope, $scope, $http) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        $http.get(
            trip_filter_data_url
        ).then(function(response) {
            console.log(response.data);
            self.employee_list = response.data.employee_list;
            self.purpose_list = response.data.purpose_list;
            self.trip_status_list = response.data.trip_status_list;
            $rootScope.loading = false;
        });

        var dataTable = $('#eyatra_trip_table').DataTable({
            stateSave: true,
            "dom": dom_structure_separate_2,
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
            "bProcessing": true,
            paging: true,
            ordering: false,
            ajax: {
                url: laravel_routes['listTrip'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.employee_id = $('#employee_id').val();
                    d.purpose_id = $('#purpose_id').val();
                    d.status_id = $('#status_id').val();
                }
            },

            columns: [
                { data: 'action', searchable: false, class: 'action' },
                { data: 'number', name: 'trips.number', searchable: true },
                { data: 'created_date', name: 'trips.created_date', searchable: false },
                { data: 'ecode', name: 'e.code', searchable: true },
                { data: 'ename', name: 'users.name', searchable: true },
                { data: 'travel_period', name: 'travel_period', searchable: false },
                { data: 'purpose', name: 'purpose.name', searchable: true },
                { data: 'advance_received', name: 'trips.advance_received', searchable: false },
                { data: 'status', name: 'status.name', searchable: true },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();
        /*
        $('.separate-page-header-content .data-table-title').html('<p class="breadcrumb">Request / Trips</p><h3 class="title">Trips</h3>');
        $('.add_new_button').html(
            '<a href="#!/eyatra/trip/add" type="button" class="btn btn-blue" ng-show="$ctrl.hasPermission(\'add-trip\')">' +
            'Add New' +
            '</a>'
        );*/

        setTimeout(function() {
            var x = $('.separate-page-header-inner.search .custom-filter').position();
            var d = document.getElementById('eyatra_trip_table_filter');
            x.left = x.left + 15;
            d.style.left = x.left + 'px';
        }, 500);


        $scope.getEmployeeData = function(query) {
            $('#employee_id').val(query);
            dataTable.draw();
        }
        $scope.getPurposeData = function(query) {
            $('#purpose_id').val(query);
            dataTable.draw();
        }
        $scope.getStatusData = function(query) {
            $('#status_id').val(query);
            dataTable.draw();
        }

        $scope.reset_filter = function(query) {
            $('#employee_id').val(-1);
            $('#purpose_id').val(-1);
            $('#status_id').val(-1);
            dataTable.draw();
        }
        $scope.reset_filter();
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
                    for (var i in response.data.errors) {
                        errors += '<li>' + response.data.errors[i] + '</li>';
                    }
                    $noty = new Noty({
                        type: 'error',
                        layout: 'topRight',
                        text: errors,
                        animation: {
                            speed: 500 // unavailable - no need
                        },
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 1000);
                } else {
                    $noty = new Noty({
                        type: 'success',
                        layout: 'topRight',
                        text: 'Trips Deleted Successfully',
                        animation: {
                            speed: 500 // unavailable - no need
                        },
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 1000);
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
        var arr_ind;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        $http.get(
            $form_data_url
        ).then(function(response) {
            if (!response.data.success) {
                $noty = new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: response.data.error,
                    animation: {
                        speed: 500 // unavailable - no need
                    },
                }).show();
                setTimeout(function() {
                    $noty.close();
                }, 1000);
                $location.path('/eyatra/trips')
                //$scope.$apply()
                return;
            }
            console.log(response.data);
            self.advance_eligibility = response.data.advance_eligibility;
            if (self.advance_eligibility == 1) {
                $("#advance").show().prop('disabled', false);
            }
            self.trip = response.data.trip;

            self.extras = response.data.extras;
            self.action = response.data.action;
            if (self.action == 'New') {
                self.trip.trip_type = 'Self';
                self.booking_method = "self";
                self.trip.visits = [];
                arr_ind = 1;

                /*self.trip.visits[0].booking_method = 'Self';
self.trip.visits.push({
    booking_method: 'Self'
});*/
                $scope.single_trip = true;
                self.checked = true;
                $scope.round_trip = false;
                $scope.multi_trip = false;
                //self.visits = [];

            } else {
                visit = self.trip.visits;
                arr_ind = visit.length;
            }

            $rootScope.loading = false;
            $scope.showBank = false;
            $scope.showCheque = false;
            $scope.showWallet = false;


        });



        $("#advance").hide().prop('disabled', true);
        $('.btn-nxt').on("click", function() {
            $('.editDetails-tabs li.active').next().children('a').trigger("click");
        });
        $('.btn-prev').on("click", function() {
            $('.editDetails-tabs li.active').prev().children('a').trigger("click");
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

        $scope.addVisit = function(visit_array) {
            // alert(arr_ind);
            /*self.trip.visits.push({
    visit_date: '',
    booking_method: 'Self',
    preferred_travel_modes: '',
});*/
            /*add_block = $('.or_block').html();
add_block = add_block.replace(/XXX/g, arr_ind);*/
            // alert(add_block);
            // console.log(self.trip.visits);

            var trip_array = visit_array;

            console.log(trip_array[0]);
            var trip_array = self.trip.visits;
            console.log('ss');
            console.log(trip_array)
            var arr_length = trip_array.length;
            arr_vol = arr_length - 1;
            console.log(arr_length, arr_vol);
            self.multi_trip.visits.push({
                start_city: trip_array[arr_length].end_city,
                end_city: trip_array.start_city,
                booking_method: 'Self',
                preferred_travel_modes: '',
            });
            console.log(trip_array[arr_vol]);

            /*add_block = $('.or_block').html();
add_block = add_block.replace(/XXX/g, arr_ind);
alert(add_block);
self.trip.visits[arr_ind].to_main_city = self.extras.employee_city.id;

arr_ind = arr_ind + 1;
$('.extra_block').append(add_block);*/
            //$('.add_multi_trip').show();

        }
        // $('.visits-wrp').hide();

        // $(".add_visit").on('click', function() {
        //     $('.add_multi_trip').hide();
        // });

        $scope.trip_mode = function(id) {
            if (id == 1) {
                $scope.single_trip = true;
                $scope.round_trip = false;
                $scope.multi_trip = false;
            } else if (id == 2) {

                $scope.single_trip = false;
                $scope.round_trip = true;
                $scope.multi_trip = false;
            } else if (id == 3) {

                $scope.round_trip = false;
                $scope.single_trip = false;
                $scope.multi_trip = true;

                $('.add_multi_trip').hide();
            }
        }

        self.removeLodging = function(index, lodging_id) {
            // alert('remove');
            // alert(index);
            // alert(lodging_id);
            if (lodging_id) {
                lodgings_removal_id.push(lodging_id);
                $('#lodgings_removal_id').val(JSON.stringify(lodgings_removal_id));
            }
            self.trip.visits.splice(index, 1);
        }

        //SEARCH CITY
        // self.searchCity = function(query) {
        //     if (query) {
        //         return new Promise(function(resolve, reject) {
        //             $http
        //                 .post(
        //                     trip_search_city_url, {
        //                         key: query,
        //                     }
        //                 )
        //                 .then(function(response) {
        //                     console.log(response.data);
        //                     resolve(response.data);
        //                 });
        //             //reject(response);
        //         });
        //     } else {
        //         return [];
        //     }
        // }

        var form_id = '#trip-form';
        var v = jQuery(form_id).validate({
            errorPlacement: function(error, element) {
                if (element.attr('name') == 'trip_mode[]') {
                    error.appendTo($('.trip_mode'));
                } else {
                    error.insertAfter(element)
                }
            },
            ignore: '',
            rules: {
                'purpose_id': {
                    required: true,
                },
                'description': {
                    minlength: 3,
                    maxlength: 255,
                },
                'advance_received': {
                    minlength: 3,
                    maxlength: 10,
                },
                'trip_mode[]': {
                    required: true,
                },
            },
            messages: {
                'description': {
                    minlength: 'Please enter minimum of 3 letters',
                    maxlength: 'Please enter maximum of 255 letters',
                },
                'advance_received': {
                    minlength: 'Please enter minimum of 3 Numbers',
                    maxlength: 'Please enter maximum of 10 Numbers',
                },
                'trip_mode[]': {
                    required: 'Select Visit Mode',
                },
            },
            invalidHandler: function(event, validator) {
                $noty = new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: 'You have errors,Please check all tabs',
                    animation: {
                        speed: 500 // unavailable - no need
                    },
                }).show();
                setTimeout(function() {
                    $noty.close();
                }, 1000);
            },
            submitHandler: function(form) {

                let formData = new FormData($(form_id)[0]);
                $('#btn-submit').button('loading');
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
                            $('#btn-submit').button('reset');
                            /*var errors = '';
                            for (var i in res.errors) {
                                errors += '<li>' + res.errors[i] + '</li>';
                            }*/
                            custom_noty('error', res.errors);
                        } else {
                            $noty = new Noty({
                                type: 'success',
                                layout: 'topRight',
                                text: 'Trip saved successfully',
                                animation: {
                                    speed: 500 // unavailable - no need
                                },
                            }).show();
                            setTimeout(function() {
                                $noty.close();
                            }, 1000);
                            $location.path('/eyatra/trips')
                            $scope.$apply()
                        }
                    })
                    .fail(function(xhr) {
                        $('#btn-submit').button('reset');
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
                            $noty = new Noty({
                                type: 'success',
                                layout: 'topRight',
                                text: 'Booking cancelled successfully',
                                animation: {
                                    speed: 500 // unavailable - no need
                                },
                            }).show();
                            setTimeout(function() {
                                $noty.close();
                            }, 1000);
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
                            $noty = new Noty({
                                type: 'success',
                                layout: 'topRight',
                                text: 'Booking cancelled successfully',
                                animation: {
                                    speed: 500 // unavailable - no need
                                },
                            }).show();
                            setTimeout(function() {
                                $noty.close();
                            }, 1000);
                            $('#request_cancel_trip').modal('hide');
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
                    $noty = new Noty({
                        type: 'error',
                        layout: 'topRight',
                        text: errors,
                        animation: {
                            speed: 500 // unavailable - no need
                        },
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 1000);
                } else {
                    $noty = new Noty({
                        type: 'success',
                        layout: 'topRight',
                        text: 'Trips Deleted Successfully',
                        animation: {
                            speed: 500 // unavailable - no need
                        },
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 1000);
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
                        $noty = new Noty({
                            type: 'success',
                            layout: 'topRight',
                            text: 'Trip successfully sent for verification',
                            animation: {
                                speed: 500 // unavailable - no need
                            },
                        }).show();
                        setTimeout(function() {
                            $noty.close();
                        }, 1000);
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
                    $noty = new Noty({
                        type: 'error',
                        layout: 'topRight',
                        text: errors,
                        animation: {
                            speed: 500 // unavailable - no need
                        },
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 1000);
                } else {
                    $('#cancel_trip').modal('hide');
                    $noty = new Noty({
                        type: 'success',
                        layout: 'topRight',
                        text: 'Trip Cancelled Successfully',
                        animation: {
                            speed: 500 // unavailable - no need
                        },
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 1000);
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
                $noty = new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: response.data.error,
                    animation: {
                        speed: 500 // unavailable - no need
                    },
                }).show();
                setTimeout(function() {
                    $noty.close();
                }, 1000);
                $location.path('/eyatra/trips')
                $scope.$apply()
                return;
            }
            //console.log(response.data.visit)
            self.visit = response.data.visit;
            self.trip = response.data.trip;
            self.bookings = response.data.bookings;
            console.log(response.data.bookings);
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