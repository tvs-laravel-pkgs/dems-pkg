app.component('eyatraAdvanceClaimRequests', {
    templateUrl: eyatra_advance_claim_request_list_template_url,
    controller: function(HelperService, $rootScope, $scope, $http, $route, $timeout, $location) {
        var self = this;
        self.export_url = advance_claim_export_data_url;
        self.csrf = $('#csrf').val();
        //console.log(self.export_url, self.csrf);
        self.hasPermission = HelperService.hasPermission;
        $http.get(
            trip_filter_data_url
        ).then(function(response) {
            console.log(response.data);
            self.employee_list = response.data.employee_list;
            self.purpose_list = response.data.purpose_list;
            self.trip_status_list = response.data.trip_status_list;
            self.outlet_list = response.data.outlet_list;
            $rootScope.loading = false;
        });


        var dataTable = $('#eyatra_advance_claim_request_table').DataTable({
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
            // scrollX: true,
            scrollCollapse: true,
            paging: true,
            ordering: false,
            fixedColumns: {
                leftColumns: 1,
            },
            ajax: {
                url: laravel_routes['listAdvanceClaimRequest'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.employee_id = $('#employee_id').val();
                    d.purpose_id = $('#purpose_id').val();
                    d.status_id = $('#status_id').val();
                    d.period = $('#period').val();
                    d.outlet = $('#outlet_id').val();
                }
            },

            columns: [
                { data: 'checkbox', searchable: false },
                { data: 'action', searchable: false },
                { data: 'number', name: 'trips.number', searchable: true },
                { data: 'ecode', name: 'e.code', searchable: true },
                { data: 'start_date', name: 'trips.start_date', searchable: true },
                { data: 'end_date', name: 'triops.end_date', searchable: true },
                { data: 'cities', name: 'c.name', searchable: true },
                { data: 'purpose', name: 'purpose.name', searchable: true },
                { data: 'outlet', name: 'outlets.name', searchable: true },
                { data: 'advance_received', name: 'trips.advance_received', searchable: false },
                { data: 'created_at', name: 'trips.created_at', searchable: true },
                { data: 'status', name: 'status.name', searchable: true },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });

        $('.dataTables_length select').select2();
        /*$('.separate-page-header-content .data-table-title').html('<p class="breadcrumb">Request / Advance Claim Request</p><h3 class="title">Advance Claim Request</h3>');
        $('.add_new_button').html();*/
        setTimeout(function() {
            var x = $('.separate-page-header-inner.search .custom-filter').position();
            var d = document.getElementById('eyatra_advance_claim_request_table_filter');
            x.left = x.left + 15;
            d.style.left = x.left + 'px';
        }, 500);

        // setTimeout(function() {
        //     $('#employee-export').css({ 'display': 'inline-block' });
        // }, 500);
        $scope.getEmployeeData = function(query) {
            //alert(query);
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
        $scope.onSelectOutlet = function(query) {
            $('#outlet_id').val(query);
            dataTable.draw();
        }

        $scope.reset_filter = function(query) {
            $('#employee_id').val(-1);
            $('#purpose_id').val(-1);
            $('#status_id').val(-1);
            $('#outlet_id').val(-1);
            dataTable.draw();
        }

        $('#head_booking').on('click', function() {
            var count = 0;
            selected_trips = [];
            if (event.target.checked == true) {
                $('.booking_list').prop('checked', true);
                $.each($('.booking_list:checked'), function() {
                    count++;
                    selected_trips.push($(this).val());
                });
            } else {
                console.log('unchecked');
                $('.booking_list').prop('checked', false);
            }
            if (count > 0) {
                $('#employee_export').css({ 'display': 'inline-block' });

            } else {
                $('#employee_export').css({ 'display': 'none' });

            }
            $('.export_ids').val(selected_trips);
        });

        $(document.body).on('click', '.booking_list', function() {
            var count = 0;
            selected_trips = [];
            $.each($('.booking_list:checked'), function() {
                count++;
                selected_trips.push($(this).val());
            });
            if (count > 0) {
                $('#employee_export').css({ 'display': 'inline-block' });

            } else {
                $('#employee_export').css({ 'display': 'none' });

            }
            $('.export_ids').val(selected_trips);
        });

        $('#employee_export').on('click', function() {
            var export_ids = $('.export_ids').val();
            console.log(export_ids);
            $http.post(
                advance_claim_approve_url, { export_ids: export_ids },
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
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 1000);
                } else {
                    $noty = new Noty({
                        type: 'success',
                        layout: 'topRight',
                        text: 'Advance Request Approved Successfully',
                        animation: {
                            speed: 500 // unavailable - no need
                        },
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 5000);

                    $('#employee_export').css({ 'display': 'none' });

                    //var dataTableFilter = $('#eyatra_advance_claim_request_table').dataTable();

                    //dataTableFilter.fnFilter();
                    // var table = $('#eyatra_advance_claim_request_table').dataTable();

                    // table.ajax.reload();

                    var dataTableFilter = $('#eyatra_advance_claim_request_table').dataTable();
                    dataTableFilter.fnFilter();

                    window.location.href = laravel_routes['AdvanceClaimRequestExport'];
                    
                     // location.reload();
                    $location.path('/advance-claim/requests');
                    $scope.$apply();


                    // $timeout(function() {
                    //     $location.path('/eyatra/advance-claim/requests')
                    //     // $scope.$apply()
                    // }, 500);
                }

            });

        });
        $rootScope.loading = false;

    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('eyatraAdvanceClaimRequestForm', {
    templateUrl: advance_claim_request_form_template_url,
    controller: function($http, $location, $location, HelperService, $routeParams, $rootScope, $scope, $timeout) {
        if (typeof($routeParams.trip_id) == 'undefined') {
            $location.path('/advance-claim/requests')
            $scope.$apply()
            return;
        }
        $form_data_url = advance_claim_request_form_data_url + '/' + $routeParams.trip_id;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;

        $scope.showBank = false;
        $scope.showCheque = false;
        $scope.showWallet = false;

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
                $location.path('/advance-claim/requests')
                $scope.$apply()
                return;
            }
            self.trip = response.data.trip;
            self.payment_mode_list = response.data.payment_mode_list;
            self.wallet_mode_list = response.data.wallet_mode_list;
            self.trip_advance_rejection = response.data.trip_advance_rejection;
            self.extras = response.data.extras;
            self.date = response.data.date;
            self.action = response.data.action;

            $scope.selectPaymentMode(self.trip.employee.payment_mode_id);

            $rootScope.loading = false;

        });

        //SELECT PAYMENT MODE
        $scope.selectPaymentMode = function(payment_id) {
            if (payment_id == 3244) { //BANK
                $scope.showBank = true;
                $scope.showCheque = false;
                $scope.showWallet = false;
            } else if (payment_id == 3245) { //CHEQUE
                $scope.showBank = false;
                $scope.showCheque = true;
                $scope.showWallet = false;
            } else if (payment_id == 3246) { //WALLET
                $scope.showBank = false;
                $scope.showCheque = false;
                $scope.showWallet = true;
            } else {
                $scope.showBank = false;
                $scope.showCheque = false;
                $scope.showWallet = false;
            }
        }

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
                advance_claim_request_approve_url + '/' + $id,
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
                        $location.path('/advance-claim/requests')
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
                advance_claim_request_reject_url + '/' + $id,
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
                        $location.path('/advance-claim/requests')
                        $scope.$apply()
                    }, 500);
                }

            });
        }



        //Approve
        $(document).on('click', '.approve_btn', function() {

            var form_id = '#advance-request-form';
            $.validator.addMethod('positiveNumber',
                function(value) {
                    return Number(value) > 0;
                }, 'Enter a positive number.');
            var v = jQuery(form_id).validate({
                errorPlacement: function(error, element) {
                    error.insertAfter(element)
                },
                ignore: '',
                rules: {
                    'amount': {
                        min: 1,
                        number: true,
                        required: true,
                    },
                    'date': {
                        required: true,
                    },
                    'bank_name': {
                        required: true,
                        maxlength: 100,
                        minlength: 3,
                    },
                    'branch_name': {
                        required: true,
                        maxlength: 50,
                        minlength: 3,
                    },
                    'account_number': {
                        required: true,
                        maxlength: 20,
                        minlength: 3,
                        positiveNumber: true,
                    },
                    'ifsc_code': {
                        required: true,
                        maxlength: 10,
                        minlength: 3,
                    },
                },
                submitHandler: function(form) {
                    // alert('cecew');
                    let formData = new FormData($(form_id)[0]);
                    $('#submit').button('loading');
                    $.ajax({
                            url: laravel_routes['saveAdvanceClaimRequest'],
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
                                    text: 'Advance Claim Request Approved successfully',
                                    animation: {
                                        speed: 500 // unavailable - no need
                                    },
                                }).show();
                                setTimeout(function() {
                                    $noty.close();
                                }, 1000);
                                setTimeout(function() {
                                    $location.path('/advance-claim/requests')
                                    $scope.$apply()
                                }, 500);

                            }
                        })
                        .fail(function(xhr) {
                            $('#submit').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                },
            });
        });


        //Reject
        $(document).on('click', '.reject_btn', function() {
            var form_id = '#trip-reject-form';
            var v = jQuery(form_id).validate({
                ignore: '',

                submitHandler: function(form) {

                    let formData = new FormData($(form_id)[0]);
                    $('#reject_btn').button('loading');
                    $.ajax({
                            url: laravel_routes['rejectAdvanceClaimRequest'],
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                        })
                        .done(function(res) {
                            console.log(res.success);
                            if (!res.success) {
                                $('#reject_btn').button('reset');
                                var errors = '';
                                for (var i in res.errors) {
                                    errors += '<li>' + res.errors[i] + '</li>';
                                }
                                custom_noty('error', errors);
                            } else {
                                $noty = new Noty({
                                    type: 'success',
                                    layout: 'topRight',
                                    text: 'Manager Rejected successfully',
                                    animation: {
                                        speed: 500 // unavailable - no need
                                    },
                                }).show();
                                setTimeout(function() {
                                    $noty.close();
                                }, 1000);
                                $('#alert-modal-reject').modal('hide');
                                setTimeout(function() {
                                    $location.path('/advance-claim/requests')
                                    $scope.$apply()
                                }, 500);

                            }
                        })
                        .fail(function(xhr) {
                            $('#submit').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                },
            });
        });
    }
});