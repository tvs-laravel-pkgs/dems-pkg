app.component('eyatraTripBookingRequests', {
    templateUrl: eyatra_booking_requests_list_template_url,
    controller: function(HelperService, $rootScope, $http, $scope) {

        var self = this;
        self.hasPermission = HelperService.hasPermission;
        var dataTable = $('#eyatra_trip_booking_requests_table').DataTable({
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
            paging: true,
            ordering: false,
            ajax: {
                url: laravel_routes['listTripBookingRequests'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.employee = $('#employee_name').val();
                    //alert($('#status').val());
                    d.status = $('#status').val();
                }
            },
            columns: [
                { data: 'action', searchable: false, class: 'action', class: 'text-left' },
                { data: 'trip_number', name: 'trips.id', searchable: true },
                { data: 'ecode', name: 'e.code', searchable: true },
                { data: 'ename', name: 'users.name', searchable: true },
                { data: 'tickets_count', searchable: false },
                { data: 'trip_status', searchable: false },
                { data: 'created_on', searchable: false },
                { data: 'booking_status', searchable: false },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });

        /* Search Block */


        $('.dataTables_length select').select2();

        // $('.page-header-content .display-inline-block .data-table-title').html('Trip Requests');
        setTimeout(function() {
            var x = $('.separate-page-header-inner.search .custom-filter').position();
            var d = document.getElementById('eyatra_trip_booking_requests_table_filter');
            x.left = x.left + 15;
            d.style.left = x.left + 'px';
        }, 500);
        // $('.add_new_button').html();
        //Filter
        $http.get(
            eyatra_booking_requests_filter_url
        ).then(function(response) {
            // console.log(response);
            self.employee_list = response.data.employee_list;
            self.status_list = response.data.status_list;
            $rootScope.loading = false;
        });
        var dataTableFilter = $('#eyatra_trip_booking_requests_table').dataTable();
        $scope.onselectEmployee = function(id) {
            $('#employee_name').val(id);
            dataTableFilter.fnFilter();
        }
        $scope.onselectStatus = function(id) {
            $('#status').val(id);
            dataTableFilter.fnFilter();
        }

        $scope.resetForm = function() {
            $('#employee_name').val(null);
            $('#status').val(null);
            dataTableFilter.fnFilter();
        }

        /* $('.page-header-content .display-inline-block .data-table-title').html('Trip Requests');
        $('.add_new_button').html(); */
        $rootScope.loading = false;

    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('eyatraTripBookingRequestsView', {
    templateUrl: agent_request_form_template_url,
    controller: function($http, $location, $location, HelperService, $routeParams, $rootScope, $scope, $timeout, $route) {
        //alert();
        if (typeof($routeParams.trip_id) == 'undefined') {
            $location.path('/agent/requests')
            $scope.$apply()
            return;
        }
        $form_data_url = agent_request_form_data_url + '/' + $routeParams.trip_id;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;

        $scope.showBookingForm = false;
        $scope.showCancelForm = false;
        $http.get(
            $form_data_url
        ).then(function(response) {
            if (!response.data.success) {
                $noty = new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: response.data.error,
                }).show();
                setTimeout(function() {
                    $noty.close();
                }, 1000);
                $location.path('/trips/booking-requests')
                $scope.$apply()
                return;
            }
            // if (!response.data.trip.visits || response.data.trip.visits.length == 0) {
            //     $noty = new Noty({
            //         type: 'error',
            //         layout: 'topRight',
            //         text: response.data.error,
            //     }).show();
            //     setTimeout(function() {
            //         $noty.close();
            //     }, 1000);
            //     $location.path('/eyatra/trips/booking-requests')
            //     $scope.$apply()
            //     return;
            // }
            console.log(response.data.trip.agent_visits);
            self.trip = response.data.trip;
            self.age = response.data.age;
            self.total_amount = response.data.total_amount;
            self.ticket_amount = response.data.ticket_amount;
            self.service_charge = response.data.service_charge;
            self.trip_status = response.data.trip_status;
            self.booking_mode_list = response.data.booking_mode_list;
            self.travel_mode_list = response.data.travel_mode_list;
            self.attachment_path = response.data.attach_path;
            self.action = response.data.action;
            $rootScope.loading = false;
        });

        $scope.userDetailId = 0;
        $scope.bookDetailId = 0;
        $scope.showUserDetail = function(id, amount) {
            //alert();
            // console.log(id, parseInt(amount));
            $("#open_cancel_form_" + id).hide();
            $(".close_to_hide_" + id).show();
            $("#close_" + id).show();
            $scope.userDetailId = id;
            $scope.bookDetailId = 0;
            setTimeout(function() {
                $(".cancellation_amount_" + id).val(parseInt(amount));
            }, 500);
            $scope.checkDetail(id, 'cancel');

        }
        $(document).on('click', '.close_icon', function() {
            var id = $(this).attr('data-visit_id');
            $scope.userDetailId = 0;
            $scope.bookDetailId = 0;
            $("#open_cancel_form_" + id).show();
            $(".close_to_hide_" + id).hide();
            $("#close_" + id).hide();
            $scope.checkDetail(id, 'cancel');
        });


        $scope.showBookDetail = function(id, amount) {
            //alert();
            // console.log(id, parseInt(amount));
            $("#open_book_form_" + id).hide();
            $(".book_open_" + id).show();
            $("#book_close_" + id).show();
            $scope.bookDetailId = id;
            $scope.userDetailId = 0;
            $scope.checkDetail(id, 'book');
        }

        $(document).on('click', '.book_close', function() {
            var id = $(this).attr('data-visit_id');
            $scope.bookDetailId = 0;
            $scope.userDetailId = 0;
            $("#open_book_form_" + id).show();
            $(".book_open_" + id).hide();
            $("#book_close_" + id).hide();
            $scope.checkDetail(id, 'book');
        });

        $scope.checkDetail = function(id, type) {
            //console.log(id, type);

            angular.forEach(self.trip.agent_visits, function(value, key) {
                console.log(value, key);
                if (value.id != id) {
                    if (value.booking_status_id == 3060) {
                        $("#open_book_form_" + value.id).show();
                        $("#book_close_" + value.id).hide();
                    } else {
                        $("#open_cancel_form_" + value.id).show();
                        $("#close_" + value.id).hide();
                    }

                }
                //this.push(key + ': ' + value);
            });
        }


        $(document).on('input', '.refund_amount', function() {
            var refund_amount = $(".refund_amount").val();
            var cancel_amount = $(".amount_cancel").val();
            if (parseInt(refund_amount) > parseInt(cancel_amount)) {
                $('.error_refund_amount').text('Amount should be less than Ticket Amount');
                $("#cancel").attr('disabled', true);
            } else {
                $('.error_refund_amount').text('');
                $("#cancel").attr('disabled', false);
            }
        });

        $scope.gstHelper = function(key) {
            console.log(key);
            var cgst = $('#cgst_'+key).val();
            var sgst = $('#sgst_'+key).val();
            var igst = $('#igst_'+key).val();

            if(cgst == '' && sgst == ''){
                $('#igst_'+key).attr('readonly', false);
                $('#igst_'+key).attr('placeholder', 'Eg:60');
            } else {
                $('#igst_'+key).attr('readonly', true);                
                $('#igst_'+key).attr('placeholder', '0');
            }

            if(igst == ''){
                $('#cgst_'+key).attr('readonly', false);
                $('#sgst_'+key).attr('readonly', false);
                $('#cgst_'+key).attr('placeholder', 'Eg:40');
                $('#sgst_'+key).attr('placeholder', 'Eg:50');
            } else {
                $('#cgst_'+key).attr('readonly', true);
                $('#sgst_'+key).attr('readonly', true);
                $('#cgst_'+key).attr('placeholder', '0');
                $('#sgst_'+key).attr('placeholder', '0');
            }
        }


        //old booking cancel
        $(document).on('click', '.booking_cancel', function() {
            var form_id = '#visit-booking-cancel-form';
            //alert(form_id);
            var v = jQuery(form_id).validate({
                errorPlacement: function(error, element) {
                    error.insertAfter(element)
                },
                ignore: '',
                submitHandler: function(form) {
                    //alert('in');
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
                                $noty = new Noty({
                                    type: 'success',
                                    layout: 'topRight',
                                    text: 'Booking details updated successfully!',
                                    animation: {
                                        speed: 500 // unavailable - no need
                                    },
                                }).show();
                                setTimeout(function() {
                                    $noty.close();
                                }, 1000);
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
            //alert();
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
                    /*'description': {
                        maxlength: 255,
                    },
                    'advance_received': {
                        maxlength: 10,
                    },*/
                    // 'ticket_booking[][attachments]': {
                    //     required: true,
                    // },
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
                                $noty = new Noty({
                                    type: 'success',
                                    layout: 'topRight',
                                    text: 'Booking details updated successfully',
                                    animation: {
                                        speed: 500 // unavailable - no need
                                    },
                                }).show();
                                setTimeout(function() {
                                    $noty.close();
                                }, 1000);
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

        //Change booking into Tatkal Booking
        //CANCEL VISIT BOOKING
        $scope.tatkalModal = function(visit_id) {
            $('#visit_id').val(visit_id);
        }

        $scope.changeTatkalBooking = function() {
            var visit_id = $('#visit_id').val();
            // alert(visit_id);
            if (visit_id) {
                $.ajax({
                        url: tatkal_booking_change_url + '/' + visit_id,
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
                                text: 'Booking Status Changed successfully',
                                animation: {
                                    speed: 500 // unavailable - no need
                                },
                            }).show();
                            setTimeout(function() {
                                $noty.close();
                            }, 1000);
                            $('#tatkal-modal').modal('hide');
                            $route.reload();
                        }
                    })
                    .fail(function(xhr) {
                        console.log(xhr);
                    });
            }
        }

        // Select and loop the container element of the elements you want to equalise
        setTimeout(function() {
            // Cache the highest
            var highestBox = new Array();
            // Loop to get all element Widths
            $('.match-height').each(function() {
                // Need to let sizes be whatever they want so no overflow on resize   
                // Then add size (no units) to array
                highestBox.push($(this).height());
            });
            // Find max Width of all elements
            var max = Math.max.apply(Math, highestBox);
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
            var max_1 = Math.max.apply(Math, highestBox_1);
            // Set the height of all those children to whichever was highest 
            $('.match-height-1').height(max_1);

        }, 1400);

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
                        text: 'Trip Approved Successfully',
                        animation: {
                            speed: 500 // unavailable - no need
                        },
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 1000);
                    $('#approval_modal').modal('hide');
                    $timeout(function() {
                        $location.path('/trip/verifications')
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
                        text: 'Trip Rejected Successfully',
                        animation: {
                            speed: 500 // unavailable - no need
                        },
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 1000);
                    $('#reject_modal').modal('hide');
                    $timeout(function() {
                        $location.path('/trip/verifications')
                        $scope.$apply()
                    }, 500);
                }

            });
        }

        /* File Upload Start */
        $(function() {
            // We can attach the `fileselect` event to all file inputs on the page
            $(document).on('change', ':file', function() {
                var input = $(this),
                    numFiles = input.get(0).files ? input.get(0).files.length : 1,
                    label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
                input.trigger('fileselect', [numFiles, label]);
            });

            // We can watch for our custom `fileselect` event like this
            $(':file').on('fileselect', function(event, numFiles, label) {
                var input = $(this).parents('.input-group').find(':text'),
                    log = numFiles > 1 ? numFiles + ' files selected' : label;

                if (input.length) {
                    input.val(log);
                } else {

                }
            });
        });
        /* File Upload End */
    }
});
//Agent Tatkal Booking List
app.component('eyatraTripTatkalBookingRequests', {
    templateUrl: eyatra_tatkal_booking_requests_list_template_url,
    controller: function(HelperService, $rootScope, $http, $scope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        var dataTable = $('#eyatra_trip_tatkal_booking_requests_table').DataTable({
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
            paging: true,
            ordering: false,
            ajax: {
                url: laravel_routes['listTripTatkalBookingRequests'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.employee = $('#employee_name').val();
                    d.status = $('#status').val();
                }
            },
            columns: [
                { data: 'action', searchable: false, class: 'action', class: 'text-left' },
                { data: 'trip_number', name: 'trips.id', searchable: true },
                { data: 'ename', name: 'users.name', searchable: true },
                { data: 'ecode', name: 'e.code', searchable: true },
                { data: 'tickets_count', searchable: false },
                // { data: 'booking_status', searchable: false },
                { data: 'created_on', searchable: false },
                { data: 'status', name: 'status.name', searchable: true },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });

        /* Search Block */


        $('.dataTables_length select').select2();

        // $('.page-header-content .display-inline-block .data-table-title').html('Trip Requests');
        setTimeout(function() {
            var x = $('.separate-page-header-inner.search .custom-filter').position();
            var d = document.getElementById('eyatra_trip_tatkal_booking_requests_table_filter');
            x.left = x.left + 15;
            d.style.left = x.left + 'px';
        }, 500);
        // $('.add_new_button').html();
        //Filter
        $http.get(
            eyatra_booking_requests_filter_url
        ).then(function(response) {
            console.log(response);
            self.employee_list = response.data.employee_list;
            self.status_list = response.data.status_list;
            $rootScope.loading = false;
        });
        var dataTableFilter = $('#eyatra_trip_tatkal_booking_requests_table').dataTable();
        $scope.onselectEmployee = function(id) {
            $('#employee_name').val(id);
            dataTableFilter.fnFilter();
        }
        $scope.onselectStatus = function(id) {
            $('#status').val(id);
            dataTableFilter.fnFilter();
        }

        $scope.resetForm = function() {
            $('#employee_name').val(null);
            $('#status').val(null);
            dataTableFilter.fnFilter();
        }

        /* $('.page-header-content .display-inline-block .data-table-title').html('Trip Requests');
        $('.add_new_button').html(); */
        $rootScope.loading = false;

    }
});

//Agent Tatkal Booking Form
app.component('eyatraTripTatkalBookingRequestsView', {
    templateUrl: eyatra_tatkal_booking_requests_view_template_url,
    controller: function($http, $location, HelperService, $routeParams, $rootScope, $scope, $timeout, $route) {
        if (typeof($routeParams.trip_id) == 'undefined') {
            $location.path('/agent/requests')
            $scope.$apply()
            return;
        }
        $form_data_url = agent_request_form_data_url + '/' + $routeParams.trip_id;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;

        //alert();
        $scope.showBookingForm = true;
        $scope.showCancelForm = false;
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
                $location.path('/trips/tatkal/booking-requests')
                $scope.$apply();
                return;
            }
            // if (!response.data.trip.visits || response.data.trip.visits.length == 0) {
            //     $noty = new Noty({
            //         type: 'error',
            //         layout: 'topRight',
            //         text: response.data.error,
            //         animation: {
            //             speed: 500 // unavailable - no need
            //         },
            //     }).show();
            //     setTimeout(function() {
            //         $noty.close();
            //     }, 1000);
            //     $location.path('/eyatra/trips/tatkal/booking-requests')
            //     $scope.$apply();
            //     return;
            // }
            console.log(response.data.trip);
            self.trip = response.data.trip;
            self.age = response.data.age;
            self.total_amount = response.data.total_amount;
            self.ticket_amount = response.data.ticket_amount;
            self.service_charge = response.data.service_charge;
            self.trip_status = response.data.trip_status;
            self.booking_mode_list = response.data.booking_mode_list;
            self.travel_mode_list = response.data.travel_mode_list;
            self.attachment_path = response.data.attach_path;
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
                    'ticket_booking[][attachments]': {
                        required: true,
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
                                $noty = new Noty({
                                    type: 'success',
                                    layout: 'topRight',
                                    text: 'Booking details updated successfully',
                                    animation: {
                                        speed: 500 // unavailable - no need
                                    },
                                }).show();
                                setTimeout(function() {
                                    $noty.close();
                                }, 5000);
                                $location.path('/trips/tatkal/booking-requests')
                                $scope.$apply();
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
        setTimeout(function() {
            // Cache the highest
            var highestBox = new Array();
            // Loop to get all element Widths
            $('.match-height').each(function() {
                // Need to let sizes be whatever they want so no overflow on resize   
                // Then add size (no units) to array
                highestBox.push($(this).height());
            });
            // Find max Width of all elements
            var max = Math.max.apply(Math, highestBox);
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
            var max_1 = Math.max.apply(Math, highestBox_1);
            // Set the height of all those children to whichever was highest 
            $('.match-height-1').height(max_1);

        }, 1400);

        /* File Upload Start */
        $(function() {
            // We can attach the `fileselect` event to all file inputs on the page
            $(document).on('change', ':file', function() {
                var input = $(this),
                    numFiles = input.get(0).files ? input.get(0).files.length : 1,
                    label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
                input.trigger('fileselect', [numFiles, label]);
            });

            // We can watch for our custom `fileselect` event like this
            $(':file').on('fileselect', function(event, numFiles, label) {
                var input = $(this).parents('.input-group').find(':text'),
                    log = numFiles > 1 ? numFiles + ' files selected' : label;

                if (input.length) {
                    input.val(log);
                } else {

                }
            });
        });
        /* File Upload End */
    }
});