app.component('eyatraOutstationTrip', {
    templateUrl: eyatra_outstation_trip_report_list_template_url,
    controller: function(HelperService, $rootScope, $http, $scope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.eyatra_outstation_trip_report_export_url = eyatra_outstation_trip_report_export_url;
        $http.get(
            eyatra_outstation_trip_report_filter_data_url
        ).then(function(response) {
            console.log(response.data);
            self.employee_list = response.data.employee_list;
            self.purpose_list = response.data.purpose_list;
            self.trip_status_list = response.data.trip_status_list;
            self.outlet_list = response.data.outlet_list;
            self.start_date = response.data.outstation_start_date;
            self.end_date = response.data.outstation_end_date;
            self.filter_purpose_id = response.data.filter_purpose_id;
            self.filter_status_id = response.data.filter_status_id;
            var trip_periods = response.data.outstation_start_date + ' to ' + response.data.outstation_end_date;
            self.trip_periods = trip_periods;
            if (response.data.filter_outlet_id == '-1') {
                self.filter_outlet_id = '-1';
            } else {
                self.filter_outlet_id = response.data.filter_outlet_id;
            }
            setTimeout(function() {
                $('#from_date').val(self.start_date);
                $('#to_date').val(self.end_date);
                $('#outlet_id').val(self.filter_outlet_id);
                get_employees(self.filter_outlet_id, status = 0);
                self.filter_employee_id = response.data.filter_employee_id;
                $('#employee_id').val(self.filter_employee_id);
                $('#status_id').val(self.filter_status_id);
                dataTable.draw();
            }, 1500);
            $rootScope.loading = false;
        });

        var dataTable = $('#eyatra_outstation_trip_table').DataTable({
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
                url: laravel_routes['listOutstationTripReport'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.outlet_id = $('#outlet_id').val();
                    d.employee_id = $('#employee_id').val();
                    d.purpose_id = $('#purpose_id').val();
                    d.period = $('#period').val();
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
                { data: 'outlet_name', name: 'outlets.name', searchable: true },
                { data: 'travel_period', name: 'travel_period', searchable: false },
                { data: 'purpose', name: 'purpose.name', searchable: true },
                { data: 'total_amount', searchable: false },
                { data: 'status', searchable: false },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();

        setTimeout(function() {
            var x = $('.separate-page-header-inner.search .custom-filter').position();
            var d = document.getElementById('eyatra_outstation_trip_table_filter');
            x.left = x.left + 15;
            d.style.left = x.left + 'px';
        }, 500);

        setTimeout(function() {
            $('div[data-provide = "datepicker"]').datepicker({
                todayHighlight: true,
                autoclose: true,
            });
        }, 1000);
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
        $scope.getFromDateData = function(query) {
            $('#from_date').val(query);
            dataTable.draw();
        }

        $scope.getOutletData = function(outlet_id) {
            dataTable.draw();
            get_employees(outlet_id, status = 1);
        }

        $scope.getToDateData = function(query) {
            $('#to_date').val(query);
            dataTable.draw();
        }
        $scope.reset_filter = function(query) {
            $('#outlet_id').val(-1);
            $('#employee_id').val(-1);
            $('#purpose_id').val(-1);
            $('#from_date').val('');
            $('#to_date').val('');
            $('#status_id').val('');
            self.trip_periods = '';
            self.filter_employee_id = '';
            self.filter_purpose_id = '';
            self.filter_outlet_id = '-1';
            self.filter_status_id = '-1';
            setTimeout(function() {
                dataTable.draw();
            }, 500);
        }

        function get_employees(outlet_id, status) {
            $.ajax({
                    method: "POST",
                    url: laravel_routes['getEmployeeByOutlet'],
                    data: {
                        outlet_id: outlet_id
                    },
                })
                .done(function(res) {
                    self.employee_list = [];
                    if (status == 1) {
                        self.filter_employee_id = '';
                    }
                    self.employee_list = res.employee_list;
                    $scope.$apply()
                });
        }
        $(".daterange").daterangepicker({
            autoclose: true,
            locale: {
                cancelLabel: 'Clear',
                format: "DD-MM-YYYY",
                separator: " to ",
            },
            showDropdowns: false,
            autoApply: true,
        });

        $(".daterange").on('change', function() {
            var dates = $("#trip_periods").val();
            var date = dates.split(" to ");
            self.start_date = date[0];
            self.end_date = date[1];
            setTimeout(function() {
                dataTable.draw();
            }, 500);
        });

        $rootScope.loading = false;

    }
});

app.component('eyatraLocalTrip', {
    templateUrl: eyatra_local_trip_report_list_template_url,
    controller: function(HelperService, $rootScope, $http, $scope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.eyatra_local_trip_report_export_url = eyatra_local_trip_report_export_url;
        $http.get(
            eyatra_local_trip_report_filter_data_url
        ).then(function(response) {
            console.log(response.data);
            self.employee_list = response.data.employee_list;
            self.purpose_list = response.data.purpose_list;
            self.outlet_list = response.data.outlet_list;
            self.start_date = response.data.local_trip_start_date;
            self.end_date = response.data.local_trip_end_date;
            self.filter_employee_id = response.data.filter_employee_id;
            self.filter_purpose_id = response.data.filter_purpose_id;
            self.filter_status_id = response.data.filter_status_id;
            var trip_periods = response.data.local_trip_start_date + ' to ' + response.data.local_trip_end_date;
            self.trip_periods = trip_periods;
            if (response.data.filter_outlet_id == '-1') {
                self.filter_outlet_id = '-1';
            } else {
                self.filter_outlet_id = response.data.filter_outlet_id;
            }
            setTimeout(function() {
                $('#from_date').val(self.start_date);
                $('#to_date').val(self.end_date);
                $('#outlet_id').val(self.filter_outlet_id);
                get_employees(self.filter_outlet_id, status = 0);
                self.filter_employee_id = response.data.filter_employee_id;
                $('#employee_id').val(self.filter_employee_id);
                $('#status_id').val(self.filter_status_id);
                dataTable.draw();
            }, 1500);
            $rootScope.loading = false;
        });
        var dataTable = $('#eyatra_local_trip_table').DataTable({
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
                url: laravel_routes['listLocalTripReport'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.outlet_id = $('#outlet_id').val();
                    d.employee_id = $('#employee_id').val();
                    d.purpose_id = $('#purpose_id').val();
                    d.period = $('#period').val();
                    d.from_date = $('#from_date').val();
                    d.to_date = $('#to_date').val();
                    d.status_id = $('#status_id').val();
                }
            },

            columns: [
                { data: 'action', searchable: false, class: 'action' },
                { data: 'number', name: 'local_trips.number', searchable: true },
                { data: 'created_date', name: 'local_trips.created_date', searchable: false },
                { data: 'ecode', name: 'e.code', searchable: true },
                { data: 'ename', name: 'users.name', searchable: true },
                { data: 'travel_period', name: 'travel_period', searchable: false },
                { data: 'purpose', name: 'purpose.name', searchable: true },
                { data: 'total_amount', searchable: false },
                { data: 'status', searchable: false },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();

        setTimeout(function() {
            var x = $('.separate-page-header-inner.search .custom-filter').position();
            var d = document.getElementById('eyatra_local_trip_table_filter');
            x.left = x.left + 15;
            d.style.left = x.left + 'px';
        }, 500);

        setTimeout(function() {
            $('div[data-provide = "datepicker"]').datepicker({
                todayHighlight: true,
                autoclose: true,
            });
        }, 1000);
        $scope.getEmployeeData = function(query) {
            $('#employee_id').val(query);
            dataTable.draw();
        }
        $scope.getPurposeData = function(query) {
            $('#purpose_id').val(query);
            dataTable.draw();
        }
        $scope.getFromDateData = function(query) {
            $('#from_date').val(query);
            dataTable.draw();
        }
        $scope.getToDateData = function(query) {
            $('#to_date').val(query);
            dataTable.draw();
        }
        $scope.getOutletData = function(outlet_id) {
            dataTable.draw();
            get_employees(outlet_id, status = 1);
        }
        $scope.getStatusData = function(query) {
            $('#status_id').val(query);
            dataTable.draw();
        }
        $scope.reset_filter = function(query) {
            $('#outlet_id').val(-1);
            $('#employee_id').val(-1);
            $('#purpose_id').val(-1);
            $('#from_date').val('');
            $('#to_date').val('');
            $('#status_id').val('');
            self.trip_periods = '';
            self.filter_employee_id = '';
            self.filter_purpose_id = '';
            self.filter_outlet_id = '-1';
            self.filter_status_id = '-1';
            setTimeout(function() {
                dataTable.draw();
            }, 500);
        }

        function get_employees(outlet_id, status) {
            $.ajax({
                    method: "POST",
                    url: laravel_routes['getEmployeeByOutlet'],
                    data: {
                        outlet_id: outlet_id
                    },
                })
                .done(function(res) {
                    self.employee_list = [];
                    if (status == 1) {
                        self.filter_employee_id = '';
                    }
                    self.employee_list = res.employee_list;
                    $scope.$apply()
                });
        }

        $(".daterange").daterangepicker({
            autoclose: true,
            locale: {
                cancelLabel: 'Clear',
                format: "DD-MM-YYYY",
                separator: " to ",
            },
            showDropdowns: false,
            autoApply: true,
        });

        $(".daterange").on('change', function() {
            var dates = $("#trip_periods").val();
            var date = dates.split(" to ");
            self.start_date = date[0];
            self.end_date = date[1];
            setTimeout(function() {
                dataTable.draw();
            }, 500);
        });

        $rootScope.loading = false;

    }
});

//OUTSTATION TRIP
app.component('eyatraOutstationTripList', {
    templateUrl: eyatra_outstation_trip_list_template_url,
    controller: function(HelperService, $rootScope, $http, $scope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        $http.get(
            outstation_trip_verification_filter_data_url
        ).then(function(response) {
            self.type_list = response.data.type_list;
            self.employee_list = response.data.employee_list;
            self.purpose_list = response.data.purpose_list;
            self.trip_status_list = response.data.trip_status_list;
            self.type_id_value = response.data.type_id
            
            self.type_id = response.data.type_id;
            setTimeout(function() {
                $("#type_id").val(self.type_id);
                $('#select').trigger('change');
                dataTable.draw();
            }, 1500);
            
            $rootScope.loading = false;
        });
        
        var dataTable = $('#eyatra_trip_verification_table').DataTable({
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
                url: laravel_routes['eyatraOutstationTripData'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.type_id = $('#type_id').val();
                    d.employee_id = $('#employee_id').val();
                    d.purpose_id = $('#purpose_id').val();
                    d.status_id = $('#status_id').val();
                    d.period = $('#period').val();
                    d.from_date = $('#from_date').val();
                    d.to_date = $('#to_date').val();
                }
            },

            columns: [
                { data: 'action', searchable: false, class: 'action' },
                { data: 'number', name: 'trips.number', searchable: true },
                { data: 'ecode', name: 'e.code', searchable: true },
                { data: 'ename', name: 'users.name', searchable: true },
                { data: 'start_date', name: 'trips.start_date', searchable: true },
                { data: 'end_date', name: 'trips.end_date', searchable: true },
                { data: 'purpose', name: 'purpose.name', searchable: true },
                { data: 'date', name: 'trips.created_at', searchable: true },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });

        $('.dataTables_length select').select2();

        setTimeout(function() {
            var x = $('.separate-page-header-inner.search .custom-filter').position();
            var d = document.getElementById('eyatra_trip_verification_table_filter');
            x.left = x.left + 15;
            d.style.left = x.left + 'px';
        }, 500);

        setTimeout(function() {
            $('div[data-provide = "datepicker"]').datepicker({
                todayHighlight: true,
                autoclose: true,
            });
        }, 1000);
        $scope.getTypeData = function(query) {
            // alert(query);
            $('#type_id').val(query);
            dataTable.draw();
        }
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
        $scope.getFromDateData = function(query) {
            // console.log(query);
            $('#from_date').val(query);
            dataTable.draw();
        }
        $scope.getToDateData = function(query) {
            // console.log(query);
            $('#to_date').val(query);
            dataTable.draw();
        }
        $scope.reset_filter = function(query) {
            self.type_id = self.type_id_value;
            // alert(self.type_id_value);
            $('#type_id').val(self.type_id_value);
            $('#employee_id').val(-1);
            $('#purpose_id').val(-1);
            $('#status_id').val(-1);
            $('#from_date').val('');
            $('#to_date').val('');
            dataTable.draw();
        }
        $rootScope.loading = false;

    }
});

//OUTSTATION TRIP VIEW
app.component('eyatraOutstationTripView', {
    templateUrl: eyatra_outstation_trip_view_template_url,
    controller: function($http, $location, $location, HelperService, $routeParams, $rootScope, $scope, $timeout) {
        if (typeof($routeParams.trip_id) == 'undefined') {
            $location.path('/outstation-trip/list')
            $scope.$apply()
            return;
        }
        $form_data_url = trip_verification_form_data_url + '/' + $routeParams.trip_id;
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
                $location.path('/outstation-trip/list')
                $scope.$apply()
                return;
            }
            self.trip = response.data.trip;
            console.log(response);
            self.advance_received = Number(response.data.trip.advance_received).toLocaleString('en-IN', {
                maximumFractionDigits: 2,
                style: 'currency',
                currency: 'INR'
            });
            self.trip_reject_reasons = response.data.trip_reject_reasons;
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

        //APPROVE TRIP
        self.approveTrip = function(id) {
            $('#trip_id').val(id);
        }

        $scope.clearSearch = function() {
            $scope.search = '';
        };

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
                    $('#alert-approval_modal').modal('hide');
                    $timeout(function() {
                        $location.path('/outstation-trip/list')
                        $scope.$apply()
                    }, 500);
                }

            });
        }

        //Approve
        $(document).on('click', '.approve_btn', function() {
            var form_id = '#trip-approve-form';
            var v = jQuery(form_id).validate({
                ignore: '',

                submitHandler: function(form) {

                    let formData = new FormData($(form_id)[0]);
                    $('#approve_btn').button('loading');
                    $.ajax({
                            url: laravel_routes['approveTripVerification'],
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                        })
                        .done(function(res) {
                            console.log(res.success);
                            if (!res.success) {
                                $('#approve_btn').button('reset');
                                var errors = '';
                                for (var i in res.errors) {
                                    errors += '<li>' + res.errors[i] + '</li>';
                                }
                                custom_noty('error', errors);
                            } else {
                                $noty = new Noty({
                                    type: 'success',
                                    layout: 'topRight',
                                    text: res.message,
                                    animation: {
                                        speed: 500 // unavailable - no need
                                    },
                                }).show();
                                setTimeout(function() {
                                    $noty.close();
                                }, 1000);
                                $('#alert-modal-approve').modal('hide');
                                setTimeout(function() {
                                    $location.path('/outstation-trip/list')
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
                            url: laravel_routes['rejectTripVerification'],
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
                                    $location.path('/outstation-trip/list')
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

//OUTSTATION CLAIM VIEW
app.component('eyatraOutstationClaimView', {
    templateUrl: eyatra_outstation_claim_view_template_url,
    controller: function($http, $location, $location, HelperService, $routeParams, $rootScope, $scope) {
        $form_data_url = typeof($routeParams.claim_id) == 'undefined' ? eyatra_trip_claim_verification_one_view_url + '/' : eyatra_trip_claim_verification_one_view_url + '/' + $routeParams.claim_id;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        self.eyatra_trip_claim_verification_one_visit_attachment_url = eyatra_trip_claim_verification_one_visit_attachment_url;
        self.eyatra_trip_claim_verification_one_lodging_attachment_url = eyatra_trip_claim_verification_one_lodging_attachment_url;
        self.eyatra_trip_claim_verification_one_boarding_attachment_url = eyatra_trip_claim_verification_one_boarding_attachment_url;
        self.eyatra_trip_claim_verification_one_local_travel_attachment_url = eyatra_trip_claim_verification_one_local_travel_attachment_url;
        self.eyatra_trip_claim_google_attachment_url = eyatra_trip_claim_google_attachment_url;
        
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
                $location.path('/outstation-trip/list')
                $scope.$apply()
                return;
            }
            self.trip = response.data.trip;
            self.gender = (response.data.trip.employee.gender).toLowerCase();
            self.travel_cities = response.data.travel_cities;
            self.trip_claim_rejection_list = response.data.trip_claim_rejection_list;
            self.travel_dates = response.data.travel_dates;
            // self.transport_total_amount = response.data.transport_total_amount;
            // self.lodging_total_amount = response.data.lodging_total_amount;
            // self.boardings_total_amount = response.data.boardings_total_amount;
            // self.local_travels_total_amount = response.data.local_travels_total_amount;
            self.total_amount = response.data.trip.employee.trip_employee_claim.total_amount;
            self.trip_justify = response.data.trip_justify;
            if (self.trip.advance_received) {
                if (parseInt(self.total_amount) > parseInt(self.trip.advance_received)) {
                    self.pay_to_employee = (parseInt(self.total_amount) - parseInt(self.trip.advance_received)).toFixed(2);
                    self.pay_to_company = '0.00';
                } else if (parseInt(self.total_amount) < parseInt(self.trip.advance_received)) {
                    self.pay_to_employee = '0.00';
                    self.pay_to_company = (parseInt(self.trip.advance_received) - parseInt(self.total_amount)).toFixed(2);
                } else {
                    self.pay_to_employee = '0.00';
                    self.pay_to_company = '0.00';
                }
            } else {
                self.pay_to_employee = parseInt(self.total_amount).toFixed(2);
                self.pay_to_company = '0.00';
            }
            $rootScope.loading = false;

        });

        //TOOLTIP MOUSEOVER
        // $(document).on('mouseover', ".attachment_tooltip", function() {
        //     var $this = $(this);
        //     $this.tooltip({
        //         title: $this.attr('data-title'),
        //         placement: "top"
        //     });
        //     $this.tooltip('show');
        // });
        $(document).on('mouseover', ".separate-file-attachment", function() {
            var $this = $(this);

            if (this.offsetWidth <= this.scrollWidth && !$this.attr('title')) {
                $this.tooltip({
                    title: $this.children().children(".attachment-file-name").text(),
                    placement: "top"
                });
                $this.tooltip('show');
            }
        });

        $scope.searchRejectedReason;
        $scope.clearSearchRejectedReason = function() {
            $scope.searchRejectedReason = '';
        };

        $scope.tripClaimApproveOne = function(claim_id) {
            $('#modal_claim_id').val(claim_id);
        }
        $scope.confirmTripClaimApproveOne = function() {
            $claim_id = $('#modal_claim_id').val();
            $http.get(
                eyatra_trip_claim_verification_one_approve_url + '/' + $claim_id,
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
                        text: 'Trips Claim Approved Successfully',
                        animation: {
                            speed: 500 // unavailable - no need
                        },
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 1000);
                    $('#trip-claim-modal-approve-one').modal('hide');
                    setTimeout(function() {
                        $location.path('/outstation-trip/list')
                        $scope.$apply()
                    }, 500);

                }

            });
        }


        //Reject
        $(document).on('click', '.reject_btn', function() {
            var form_id = '#trip-claim-reject-form';
            var v = jQuery(form_id).validate({
                ignore: '',

                submitHandler: function(form) {

                    let formData = new FormData($(form_id)[0]);
                    $('#reject_btn').button('loading');
                    $.ajax({
                            url: laravel_routes['rejectTripClaimVerificationOne'],
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
                                    text: 'Trips Claim Rejected successfully',
                                    animation: {
                                        speed: 500 // unavailable - no need
                                    },
                                }).show();
                                setTimeout(function() {
                                    $noty.close();
                                }, 1000);
                                $('#trip-claim-modal-reject-one').modal('hide');
                                setTimeout(function() {
                                    $location.path('/outstation-trip/list')
                                    $scope.$apply()
                                }, 500);
                            }
                        })
                        .fail(function(xhr) {
                            $('#reject_btn').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                },
            });
        });

        /* Tooltip */
        $('[data-toggle="tooltip"]').tooltip();


        /* Pane Next Button */
        $('.btn-nxt').on("click", function() {
            $('.editDetails-tabs li.active').next().children('a').trigger("click");
        });
        $('.btn-prev').on("click", function() {
            $('.editDetails-tabs li.active').prev().children('a').trigger("click");
        });   
    }
});