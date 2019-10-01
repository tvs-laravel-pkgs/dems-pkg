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
            // stateSave: true,
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
            fixedColumns: true,
            scrollX: true,
            scrollCollapse: true,
            paging: true,
            ordering: false,
            fixedColumns: {
                leftColumns: 1,
            },
            ajax: {
                url: laravel_routes['listTrip'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    // d.employee_id = $('#employee_id').val();
                    d.purpose_id = $('#purpose_id').val();
                    d.from_date = $('#from_date').val();
                    d.to_date = $('#to_date').val();
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

        //CURRENT DATE SHOW IN DATEPICKER
        setTimeout(function() {
            $('div[data-provide="datepicker"]').datepicker({
                todayHighlight: true,
                autoclose: true,
            });
        }, 1000);

        setTimeout(function() {
            var x = $('.separate-page-header-inner.search .custom-filter').position();
            var d = document.getElementById('eyatra_trip_table_filter');
            x.left = x.left + 15;
            d.style.left = x.left + 'px';
        }, 500);


        // $scope.getEmployeeData = function(query) {
        //     $('#employee_id').val(query);
        //     dataTable.draw();
        // }
        $scope.getPurposeData = function(query) {
            $('#purpose_id').val(query);
            dataTable.draw();
        }
        $scope.getFromDateData = function(query) {
            console.log(query);
            $('#from_date').val(query);
            dataTable.draw();
        }
        $scope.getToDateData = function(query) {
            console.log(query);
            $('#to_date').val(query);
            dataTable.draw();
        }
        $scope.getStatusData = function(query) {
            $('#status_id').val(query);
            dataTable.draw();
        }

        $scope.reset_filter = function(query) {
            // $('#employee_id').val(-1);
            $('#purpose_id').val(-1);
            $('#from_date').val('');
            $('#to_date').val('');
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
                    }, 5000);
                } else {
                    $noty = new Noty({
                        type: 'success',
                        layout: 'topRight',
                        text: 'Trip Deleted Successfully',
                        animation: {
                            speed: 500 // unavailable - no need
                        },
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 5000);
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
    controller: function($http, $location, $location, HelperService, $routeParams, $rootScope, $scope, $timeout) {
        $form_data_url = typeof($routeParams.trip_id) == 'undefined' ? trip_form_data_url : trip_form_data_url + '/' + $routeParams.trip_id;
        var self = this;
        var arr_ind;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;

        $scope.options = {
            locale: {
                cancelLabel: 'Clear',
                format: "DD-MM-YYYY",
                separator: " to ",
            },
            showDropdowns: false,
            autoApply: true,
            // singleDatePicker:true
        };

        var startdate;
        var enddate;
        var id;
        $scope.onChange = function() {
            self.trip.start_date = moment($scope.startDate).format('DD-MM-YYYY');
            self.trip.end_date = moment($scope.endDate).format('DD-MM-YYYY');
            startdate = self.trip.start_date;
            enddate = self.trip.end_date;
            var arr_length = self.trip.visits.length;
            for (var i = 0; i < arr_length; i++) {
                $('.datepicker_' + i).datepicker('destroy');
                //id = 0;
                datecall(startdate, enddate, i);
            }
        }

        $('body').on('click', "#datepicker", function() {
            var id = $(this).data('picker');
            datecall(startdate, enddate, id);
        });

        function datecall(startdate, enddate, id) {
            $(".datepicker_" + id).datepicker({
                autoclose: true,
                startDate: startdate,
                endDate: enddate,
            });
        }

        function sameFromTo() {
            $noty = new Noty({
                type: 'error',
                layout: 'topRight',
                text: 'From City and To City should not be same,please choose another To city',
                animation: {
                    speed: 50 // unavailable - no need
                },
            }).show();
            setTimeout(function() {
                $noty.close();
            }, 100000);
        }
        $http.get(
            $form_data_url
        ).then(function(response) {
            console.log(response.data);
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
                return;
            }
            self.trip = response.data.trip;
            self.trip.trip_periods = '';
            if (response.data.trip.start_date && response.data.trip.end_date) {
                var start_date = response.data.trip.start_date;
                var end_date = response.data.trip.end_date;

                //  $('.daterange').val(start_date.format('DD-MM-YYYY') + ' to ' + end_date.format('DD-MM-YYYY'));
                /* $('.daterange').on('show.daterangepicker', function(ev, picker) {
     $('.daterange').val(picker.start_date.format('DD-MM-YYYY') + ' to ' + picker.end_date.format('DD-MM-YYYY'));
 });*/
                trip_periods = response.data.trip.start_date + ' to ' + response.data.trip.end_date;
                $('#trip_periods').val(trip_periods);
                $('#trip_periods').data('daterangepicker').setStartDate(start_date);
                $('#trip_periods').data('daterangepicker').setEndDate(end_date);
            }
            // console.log(self.trip.trip_periods);
            self.advance_eligibility = response.data.advance_eligibility;
            self.grade_advance_eligibility_amount = response.data.grade_advance_eligibility_amount;
            if (self.advance_eligibility == 1) {
                $("#advance").show().prop('disabled', false);
            }
            self.extras = response.data.extras;
            self.action = response.data.action;
            // console.log(self.trip);
            // console.log(response.data.trip.end_date);
            if (self.action == 'New') {
                self.trip.trip_type = 'single';
                self.booking_method_name = 'self';
                self.trip.visits = [];
                arr_ind = 1;
                self.trip.visits.push({
                    from_city_id: response.data.extras.employee_city.id,
                    to_city_id: '',
                    booking_method_name: 'Self',
                    preferred_travel_modes: '',
                    from_city_details: self.trip.from_city_details,

                });
                self.checked = true;
                $scope.round_trip = false;
                $scope.multi_trip = false;
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
        $scope.cityChanging = function(i, id) {
            var index = i + 1;
            id = (!id) ? '' : id;
            if (index <= self.trip.visits.length && self.trip.visits[index]) {
                if (self.trip.visits.length > 1) {
                    self.trip.visits[index].from_city_id = id;
                }
            }
            var error_details = 0;
            var leng = self.trip.visits.length;
            for (j = 0; j < leng; j++) {
                if (self.trip.visits[j].to_city_details) {
                    from_id = self.trip.visits[j].from_city_details ? self.trip.visits[j].from_city_details.id : self.trip.visits[j].from_city_id;
                    if (self.trip.visits[j].to_city_details.id == self.trip.visits[j].from_city_details.id) {
                        error_details = 1;
                        sameFromTo();
                    }
                }
            }
            if (error_details == 1) {
                $('.btn-submit').prop('disabled', true);
            } else {
                $('.btn-submit').prop('disabled', false);
            }
        }
        $scope.addVisit = function(visit_array) {
            var trip_array = self.trip.visits;
            var arr_length = trip_array.length;
            // console.log(trip_array);
            arr_vol = arr_length - 1;
            if (!(trip_array[arr_vol].to_city_details) || !(trip_array[arr_vol].to_city_details.id)) {
                $noty = new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: 'Please Select To City in Last Visit',
                    animation: {
                        speed: 500 // unavailable - no need
                    },
                }).show();
                setTimeout(function() {
                    $noty.close();
                }, 10000);
            }
            self.trip.visits.push({
                from_city_id: trip_array[arr_vol].to_city_details.id,
                to_city_id: trip_array[arr_vol].from_city_id,
                booking_method_name: 'Self',
                preferred_travel_modes: '',
                from_city_details: trip_array[arr_vol].to_city_details,
            });
            $('.datepicker_' + arr_length).datepicker('destroy');
            datecall(startdate, enddate, arr_length);


        }
        $scope.trip_mode = function(id) {
            var trip_array = self.trip.visits;
            console.log(trip_array);

            if (id == 1) {
                var arr_length = self.trip.visits.length;
                while (arr_length > 1) {
                    index = arr_length - 1;
                    self.trip.visits.splice(index, 1);
                    arr_length = self.trip.visits.length;
                }
                datecall(startdate, enddate, id);
            } else if (id == 2) {
                var arr_length = self.trip.visits.length;
                if (arr_length < 2) {
                    if (!(self.trip.visits[0].to_city_details)) {
                        from_city_id_data = '';
                    } else {
                        from_city_id_data = self.trip.visits[0].to_city_details.id;
                    }
                    self.trip.visits.push({
                        from_city_id: from_city_id_data,
                        to_city_id: self.trip.visits[0].from_city_details,
                        booking_method_name: 'Self',
                        preferred_travel_modes: '',
                        to_city_details: self.trip.visits[0].from_city_details,
                    });
                    date_id = arr_length - 1;
                    datecall(startdate, enddate, date_id);
                } else {

                    while (arr_length > 2) {
                        index = arr_length - 1;
                        self.trip.visits.splice(index, 1);
                        arr_length = self.trip.visits.length;
                    }
                }
                self.trip.visits[1].to_city_details = self.trip.visits[0].from_city_details;
            } else if (id == 3) {


                $('.add_multi_trip').hide();
                datecall(startdate, enddate, id);
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
        // $('#trip-form').validate();

        // var user = $('input[name^="visits"]');

        // user.filter('input[name$="[to_city_id]"]').each(function() {
        //     $(this).rules("add", {
        //         messages: {
        //             required: "Name is Mandatory"
        //         }
        //     });
        // });
        $('.advance_amount_check').on('keyup', function() {
            if (parseInt($(".advance_amount_check").val()) > parseInt(self.grade_advance_eligibility_amount)) {
                $('.maximum_amount_eligible').text('Maximum advance amount is ' + parseInt(self.grade_advance_eligibility_amount));
                $('.btn-submit').prop('disabled', true);
            } else {
                $('.maximum_amount_eligible').text('');
                $('.btn-submit').prop('disabled', false);
            }
        })
        var form_id = '#trip-form';
        var v = jQuery(form_id).validate({
            errorPlacement: function(error, element) {
                if (element.attr('name') == 'trip_mode[]') {
                    error.appendTo($('.trip_mode'));
                } else if (element.hasClass("advance_amount_check")) {
                    error.appendTo($('.advance_amount_required'));
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
                $('.btn-submit').button('loading');
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
                            $('.btn-submit').button('reset');
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
                        $('.btn-submit').button('reset');
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
            self.claim_status = response.data.claim_status;
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
                    }, 5000);
                } else {
                    $noty = new Noty({
                        type: 'success',
                        layout: 'topRight',
                        text: 'Trip Deleted Successfully',
                        animation: {
                            speed: 500 // unavailable - no need
                        },
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 5000);
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
                    }, 5000);
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
                    }, 5000);
                    setTimeout(function() {
                        $location.path('/eyatra/trips')
                        $scope.$apply()
                    }, 500);

                }

            });
        }

        //DELETE VISIT BOOKING
        $scope.deleteVisitBookingPopup = function(visit_id) {
            $('#delete_booking_visit_id').val(visit_id);
        }

        $scope.deleteBooking = function() {
            var delete_booking_visit_id = $('#delete_booking_visit_id').val();
            if (delete_booking_visit_id) {
                $.ajax({
                        url: trip_visit_delete_booking_url + '/' + delete_booking_visit_id,
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
                                text: 'Visit Deleted successfully',
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
            // console.log(response)
            self.visit = response.data.visit;
            self.trip = response.data.trip;
            self.bookings = response.data.bookings;
            self.eyatra_trip_claim_visit_attachment_url = eyatra_trip_claim_visit_attachment_url;
            // console.log(response.data.bookings);
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