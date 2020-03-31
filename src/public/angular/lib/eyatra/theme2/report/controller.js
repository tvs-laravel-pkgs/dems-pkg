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
            self.status_list = response.data.trip_status_list;
            self.outlet_list = response.data.outlet_list;
            self.start_date = response.data.outstation_start_date;
            self.end_date = response.data.outstation_end_date;
            self.filter_purpose_id = response.data.filter_purpose_id;
            var trip_periods = response.data.outstation_start_date + ' to ' + response.data.outstation_end_date;
            self.trip_periods = trip_periods;
            if (response.data.filter_outlet_id == '-1') {
                self.filter_outlet_id = '-1';
            } else {
                self.filter_outlet_id = response.data.filter_outlet_id;
            }
            if (response.data.filter_status_id == '-1') {
                self.filter_status_id = '-1';
            } else {
                self.filter_status_id = response.data.filter_status_id;
            }
            setTimeout(function() {
                // self.filter_status_id = response.data.filter_status_id;
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
            self.status_list = response.data.trip_status_list;
            self.filter_employee_id = response.data.filter_employee_id;
            self.filter_purpose_id = response.data.filter_purpose_id;
            var trip_periods = response.data.local_trip_start_date + ' to ' + response.data.local_trip_end_date;
            self.trip_periods = trip_periods;
            if (response.data.filter_outlet_id == '-1') {
                self.filter_outlet_id = '-1';
            } else {
                self.filter_outlet_id = response.data.filter_outlet_id;
            }
            if (response.data.filter_status_id == '-1') {
                self.filter_status_id = '-1';
            } else {
                self.filter_status_id = response.data.filter_status_id;
            }
            setTimeout(function() {
                // self.filter_status_id = response.data.filter_status_id;
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


//APPROVAL LOGS
//OUTSTATION TRIP
app.component('eyatraOutstationTripList', {
    templateUrl: eyatra_outstation_trip_list_template_url,
    controller: function(HelperService, $rootScope, $http, $scope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        $http.get(
            outstation_trip_filter_data_url
        ).then(function(response) {
            // console.log(response.data);
            self.type_list = response.data.type_list;
            self.employee_list = response.data.employee_list;
            self.purpose_list = response.data.purpose_list;
            self.start_date = response.data.manager_filter_start_date;
            self.end_date = response.data.manager_filter_end_date;
            self.filter_type_id = response.data.filter_type_id;
            if (response.data.filter_purpose_id == '-1') {
                self.filter_purpose_id = '-1';
            } else {
                self.filter_purpose_id = response.data.filter_purpose_id;
            }
            if (response.data.filter_employee_id == '-1') {
                self.filter_employee_id = '-1';
            } else {
                self.filter_employee_id = response.data.filter_employee_id;
            }
            var trip_periods = response.data.manager_filter_start_date + ' to ' + response.data.manager_filter_end_date;
            self.trip_periods = trip_periods;

            setTimeout(function() {
                $("#type_id").val(self.filter_type_id);
                $('#from_date').val(self.start_date);
                $('#to_date').val(self.end_date);
                $('#select').trigger('change');
                dataTable.draw();
            }, 1500);

            $rootScope.loading = false;
        });

        var dataTable = $('#eyatra_trip_approval_table').DataTable({
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
                { data: 'type', searchable: false },
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
            var d = document.getElementById('eyatra_trip_approval_table_filter');
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
            $('#type_id').val(query);
            dataTable.draw();
        }
        $scope.getEmployeeData = function(query) {
            $('#employee_id').val(query);
            dataTable.draw();
        }
        $scope.getPurposeData = function(query) {
            $('#purpose_id').val(query);
            dataTable.draw();
        }

        $scope.reset_filter = function(query) {
            self.filter_employee_id = '-1';
            self.filter_purpose_id = '-1';
            self.filter_type_id = 3600;
            $('#type_id').val(3600);
            self.trip_periods = '';
            $('#employee_id').val('-1');
            $('#purpose_id').val('-1');
            $('#from_date').val('');
            $('#to_date').val('');
            dataTable.draw();
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

//OUTSTATION TRIP VIEW
app.component('eyatraOutstationTripView', {
    templateUrl: eyatra_outstation_trip_view_template_url,
    controller: function($http, $location, $location, HelperService, $routeParams, $rootScope, $scope, $timeout) {
        if (typeof($routeParams.trip_id) == 'undefined') {
            $location.path('/reports/outstation-trip')
            $scope.$apply()
            return;
        }
        $form_data_url = report_trip_view_url + '/' + $routeParams.trip_id;
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
                $location.path('/reports/outstation-trip')
                //$scope.$apply()
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

//LOCAL TRIP
app.component('eyatraReportsLocalTripList', {
    templateUrl: eyatra_report_local_trip_list_template_url,
    controller: function(HelperService, $rootScope, $http, $scope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        $http.get(
            local_trip_report_filter_data_url
        ).then(function(response) {
            self.type_list = response.data.type_list;
            self.employee_list = response.data.employee_list;
            self.purpose_list = response.data.purpose_list;
            self.start_date = response.data.manager_filter_start_date;
            self.end_date = response.data.manager_filter_end_date;
            self.filter_type_id = response.data.filter_type_id;
            if (response.data.filter_purpose_id == '-1') {
                self.filter_purpose_id = '-1';
            } else {
                self.filter_purpose_id = response.data.filter_purpose_id;
            }
            if (response.data.filter_employee_id == '-1') {
                self.filter_employee_id = '-1';
            } else {
                self.filter_employee_id = response.data.filter_employee_id;
            }
            var trip_periods = response.data.manager_filter_start_date + ' to ' + response.data.manager_filter_end_date;
            self.trip_periods = trip_periods;

            setTimeout(function() {
                $("#type_id").val(self.filter_type_id);
                $('#from_date').val(self.start_date);
                $('#to_date').val(self.end_date);
                $('#select').trigger('change');
                dataTable.draw();
            }, 1500);
            $rootScope.loading = false;
        });

        var dataTable = $('#eyatra_local_trip_report_table').DataTable({
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
                url: laravel_routes['eyatraLocalTripData'],
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
                { data: 'type', searchable: false },
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
            var d = document.getElementById('eyatra_local_trip_report_table_filter');
            x.left = x.left + 15;
            d.style.left = x.left + 'px';
        }, 500);

        $scope.getTypeData = function(query) {
            $('#type_id').val(query);
            dataTable.draw();
        }
        $scope.getEmployeeData = function(query) {
            $('#employee_id').val(query);
            dataTable.draw();
        }
        $scope.getPurposeData = function(query) {
            $('#purpose_id').val(query);
            dataTable.draw();
        }

        $scope.reset_filter = function(query) {
            self.filter_employee_id = '-1';
            self.filter_purpose_id = '-1';
            self.filter_type_id = 3606;
            $('#type_id').val(3606);
            self.trip_periods = '';
            $('#employee_id').val('-1');
            $('#purpose_id').val('-1');
            $('#from_date').val('');
            $('#to_date').val('');
            dataTable.draw();
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
//LOCAL TRIP VIEW
app.component('eyatraReportsLocalTripView', {
    templateUrl: eyatra_local_trip_view_template_url,
    controller: function($http, $location, $location, HelperService, $routeParams, $rootScope, $scope, $timeout) {
        if (typeof($routeParams.trip_id) == 'undefined') {
            $location.path('/reports/local-trip')
            $scope.$apply()
            return;
        }
        $form_data_url = local_trip_view_url + '/' + $routeParams.trip_id;
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
                }).show();
                setTimeout(function() {
                    $noty.close();
                }, 1000);
                $location.path('/reports/local-trip')
                $scope.$apply()
                return;
            }
            self.trip = response.data.trip;
            console.log(response);
            self.extras = response.data.extras;
            self.action = response.data.action;
            $rootScope.loading = false;
        });
    }
});

//LOCAL TRIP CLAIM VIEW
app.component('eyatraReportsLocalTripClaimView', {
    templateUrl: eyatra_local_claim_view_template_url,
    controller: function($http, $location, $location, HelperService, $routeParams, $rootScope, $scope) {
        if (typeof($routeParams.trip_id) == 'undefined') {
            $location.path('/reports/local-trip')
            $scope.$apply()
            return;
        }
        $form_data_url = local_trip_view_url + '/' + $routeParams.trip_id;
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
                }).show();
                setTimeout(function() {
                    $noty.close();
                }, 1000);
                $location.path('/reports/local-trip')
                $scope.$apply()
                return;
            }
            self.trip = response.data.trip;
            console.log(response);
            self.extras = response.data.extras;
            self.action = response.data.action;
            $rootScope.loading = false;
        });

        //TOOLTIP MOUSEOVER
        $(document).on('mouseover', ".attachment-view-list", function() {
            var $this = $(this);

            if (this.offsetWidth <= this.scrollWidth && !$this.attr('title')) {
                $this.tooltip({
                    title: $this.children(".attachment-view-file").text(),
                    placement: "top"
                });
                $this.tooltip('show');
            }
        });

        $('.btn-nxt').on("click", function() {
            $('.editDetails-tabs li.active').next().children('a').trigger("click");
        });
        $('.btn-prev').on("click", function() {
            $('.editDetails-tabs li.active').prev().children('a').trigger("click");
        });

    }
});

//OUTSTATION TRIP ADVANCE REQUEST LIST
app.component('eyatraReportsTripAdvanceRequest', {
    templateUrl: eyatra_trip_advance_list_template_url,
    controller: function(HelperService, $rootScope, $http, $scope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        $http.get(
            trip_advance_request_filter_data_url
        ).then(function(response) {
            self.employee_list = response.data.employee_list;
            self.outlet_list = response.data.outlet_list;
            self.filter_outlet_id = response.data.filter_outlet_id;
            self.start_date = response.data.financier_trip_start_date;
            self.end_date = response.data.financier_trip_end_date;
            if (response.data.filter_employee_id == '-1') {
                self.filter_employee_id = '-1';
            } else {
                self.filter_employee_id = response.data.filter_employee_id;
            }
            var trip_periods = response.data.financier_trip_start_date + ' to ' + response.data.financier_trip_end_date;
            self.trip_periods = trip_periods;

            setTimeout(function() {
                get_employees(self.filter_outlet_id, status = 0);
                $('#from_date').val(self.start_date);
                $('#to_date').val(self.end_date);
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
                url: laravel_routes['eyatraTripAdvanceRequestData'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.employee_id = $('#employee_id').val();
                    d.from_date = $('#from_date').val();
                    d.to_date = $('#to_date').val();
                    d.outlet_id = $('#outlet_id').val();
                }
            },

            columns: [
                { data: 'action', searchable: false, class: 'action' },
                { data: 'number', name: 'trips.number', searchable: true },
                //{ data: 'type', searchable: false },
                { data: 'ecode', name: 'e.code', searchable: true },
                { data: 'ename', name: 'users.name', searchable: true },
                { data: 'start_date', name: 'trips.start_date', searchable: true },
                { data: 'end_date', name: 'trips.end_date', searchable: true },
                { data: 'purpose', name: 'purpose.name', searchable: true },
                { data: 'advance_amount', name: 'trips.advance_received', searchable: false },
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

         $scope.getEmployeeData = function(query) {
            $('#employee_id').val(query);
            dataTable.draw();
        }

        $scope.getType = function(query) {
            $('#type_id').val(query);
            dataTable.draw();
        }

        $scope.getOutletData = function(outlet_id) {
            $('#outlet_id').val(outlet_id)
            dataTable.draw();
            get_employees(outlet_id, status = 1);
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

        $scope.reset_filter = function(query) {
            $('#type_id').val(-1);
            $('#employee_id').val(-1);
            $('#outlet_id').val(-1);
            $('#from_date').val('');
            $('#to_date').val('');
            self.trip_periods = '';
            if (self.type_id == 3) {
                get_employees(self.filter_outlet_id, status = 1);
            }
            self.filter_type_id = '-1';
            self.filter_employee_id = '-1';
            self.filter_outlet_id = '-1';
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
                        self.filter_employee_id = '-1';
                    }
                    self.employee_list = res.employee_list;
                    $scope.$apply()
                });
        }

        $rootScope.loading = false;

    }
});
//OUTSTATION TRIP SR MANAGER APPROVAL
app.component('eyatraReportsTripSrManagerApproval', {
    templateUrl: eyatra_trip_sr_manager_approval_list_template_url,
    controller: function(HelperService, $rootScope, $http, $scope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        $http.get(
            trip_sr_manager_approval_filter_data_url
        ).then(function(response) {
            self.type_list = response.data.type_list;
            self.employee_list = response.data.employee_list;
            self.purpose_list = response.data.purpose_list;
            self.trip_status_list = response.data.trip_status_list;
            self.type_id_value = response.data.type_id

            setTimeout(function() {
                self.type_id = response.data.type_id;
                $("#type_id").val(self.type_id);
                $('#select').trigger('change');
                dataTable.draw();
            }, 1500);

            $rootScope.loading = false;
        });

        var dataTable = $('#eyatra_trip_verification_table_sr').DataTable({
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
                url: laravel_routes['eyatraTripSrManagerApprovalData'],
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
                //{ data: 'type', searchable: false },
                { data: 'ecode', name: 'e.code', searchable: true },
                { data: 'ename', name: 'users.name', searchable: true },
                { data: 'start_date', name: 'trips.start_date', searchable: true },
                { data: 'end_date', name: 'trips.end_date', searchable: true },
                { data: 'purpose', name: 'purpose.name', searchable: true },
                { data: 'total_amount', searchable: false },
                { data: 'date', name: 'trips.created_at', searchable: true },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });

        $('.dataTables_length select').select2();

        setTimeout(function() {
            var x = $('.separate-page-header-inner.search .custom-filter').position();
            var d = document.getElementById('eyatra_trip_verification_table_sr_filter');
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
//OUTSTATION TRIP Financier Approval
app.component('eyatraReportsTripFinancierApproval', {
    templateUrl: eyatra_trip_financier_approval_list_template_url,
    controller: function(HelperService, $rootScope, $http, $scope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        $http.get(
            trip_financier_approval_filter_data_url
        ).then(function(response) {
            self.type_list = response.data.type_list;
            self.employee_list = response.data.employee_list;
            self.purpose_list = response.data.purpose_list;
            self.trip_status_list = response.data.trip_status_list;
            self.type_id_value = response.data.type_id

            setTimeout(function() {
                self.type_id = response.data.type_id;
                $("#type_id").val(self.type_id);
                $('#select').trigger('change');
                dataTable.draw();
            }, 1500);

            $rootScope.loading = false;
        });

        var dataTable = $('#eyatra_trip_verification_table_financier').DataTable({
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
                url: laravel_routes['eyatraTripFinancierApprovalData'],
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
                //{ data: 'type', searchable: false },
                { data: 'ecode', name: 'e.code', searchable: true },
                { data: 'ename', name: 'users.name', searchable: true },
                { data: 'start_date', name: 'trips.start_date', searchable: true },
                { data: 'end_date', name: 'trips.end_date', searchable: true },
                { data: 'purpose', name: 'purpose.name', searchable: true },
                { data: 'total_amount', searchable: false },
                { data: 'date', name: 'trips.created_at', searchable: true },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });

        $('.dataTables_length select').select2();

        setTimeout(function() {
            var x = $('.separate-page-header-inner.search .custom-filter').position();
            var d = document.getElementById('eyatra_trip_verification_table_financier_filter');
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
//OUTSTATION TRIP Financier PAID
app.component('eyatraReportsTripFinancierPaid', {
    templateUrl: eyatra_trip_financier_paid_list_template_url,
    controller: function(HelperService, $rootScope, $http, $scope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        $http.get(
            trip_financier_paid_filter_data_url
        ).then(function(response) {
            self.type_list = response.data.type_list;
            self.employee_list = response.data.employee_list;
            self.purpose_list = response.data.purpose_list;
            self.trip_status_list = response.data.trip_status_list;
            self.type_id_value = response.data.type_id

            setTimeout(function() {
                self.type_id = response.data.type_id;
                $("#type_id").val(self.type_id);
                $('#select').trigger('change');
                dataTable.draw();
            }, 1500);

            $rootScope.loading = false;
        });

        var dataTable = $('#outstation_trip_financier_paid_table').DataTable({
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
                url: laravel_routes['eyatraTripFinancierPaidData'],
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
                //{ data: 'type', searchable: false },
                { data: 'ecode', name: 'e.code', searchable: true },
                { data: 'ename', name: 'users.name', searchable: true },
                { data: 'start_date', name: 'trips.start_date', searchable: true },
                { data: 'end_date', name: 'trips.end_date', searchable: true },
                { data: 'purpose', name: 'purpose.name', searchable: true },
                { data: 'total_amount', searchable: false },
                { data: 'date', name: 'trips.created_at', searchable: true },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });

        $('.dataTables_length select').select2();

        setTimeout(function() {
            var x = $('.separate-page-header-inner.search .custom-filter').position();
            var d = document.getElementById('outstation_trip_financier_paid_table_filter');
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
//OUTSTATION TRIP Employee PAID
app.component('eyatraReportsTripEmployeePaid', {
    templateUrl: eyatra_employee_paid_list_template_url,
    controller: function(HelperService, $rootScope, $http, $scope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        $http.get(
            trip_employee_paid_filter_data_url
        ).then(function(response) {
            self.type_list = response.data.type_list;
            self.employee_list = response.data.employee_list;
            self.purpose_list = response.data.purpose_list;
            self.trip_status_list = response.data.trip_status_list;
            self.type_id_value = response.data.type_id

            setTimeout(function() {
                self.type_id = response.data.type_id;
                $("#type_id").val(self.type_id);
                $('#select').trigger('change');
                dataTable.draw();
            }, 1500);

            $rootScope.loading = false;
        });

        var dataTable = $('#outstation_trip_employee_paid_table').DataTable({
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
                url: laravel_routes['eyatraTripEmployeePaidData'],
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
                //{ data: 'type', searchable: false },
                { data: 'ecode', name: 'e.code', searchable: true },
                { data: 'ename', name: 'users.name', searchable: true },
                { data: 'start_date', name: 'trips.start_date', searchable: true },
                { data: 'end_date', name: 'trips.end_date', searchable: true },
                { data: 'purpose', name: 'purpose.name', searchable: true },
                { data: 'total_amount', searchable: false },
                { data: 'date', name: 'trips.created_at', searchable: true },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });

        $('.dataTables_length select').select2();

        setTimeout(function() {
            var x = $('.separate-page-header-inner.search .custom-filter').position();
            var d = document.getElementById('outstation_trip_employee_paid_table_filter');
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
//LOCAL TRIP Financier PAID
app.component('eyatraReportsLocalTripFinancierPaid', {
    templateUrl: eyatra_local_trip_financier_paid_list_template_url,
    controller: function(HelperService, $rootScope, $http, $scope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        $http.get(
            trip_financier_paid_filter_data_url
        ).then(function(response) {
            self.type_list = response.data.type_list;
            self.employee_list = response.data.employee_list;
            self.purpose_list = response.data.purpose_list;
            self.trip_status_list = response.data.trip_status_list;
            self.type_id_value = response.data.type_id

            setTimeout(function() {
                self.type_id = response.data.type_id;
                $("#type_id").val(self.type_id);
                $('#select').trigger('change');
                dataTable.draw();
            }, 1500);

            $rootScope.loading = false;
        });

        var dataTable = $('#local_trip_financier_paid_table').DataTable({
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
                url: laravel_routes['eyatraLocalTripFinancierPaidData'],
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
                { data: 'number', name: 'local_trips.number', searchable: true },
                { data: 'ecode', name: 'e.code', searchable: true },
                { data: 'ename', name: 'users.name', searchable: true },
                { data: 'start_date', name: 'local_trips.start_date', searchable: true },
                { data: 'end_date', name: 'local_trips.end_date', searchable: true },
                { data: 'purpose', name: 'purpose.name', searchable: true },
                { data: 'total_amount', searchable: false },
                { data: 'date', searchable: false },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });

        $('.dataTables_length select').select2();

        setTimeout(function() {
            var x = $('.separate-page-header-inner.search .custom-filter').position();
            var d = document.getElementById('local_trip_financier_paid_table_filter');
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

//Petty Cash Manager Approved
app.component('eyatraReportsPettyCashManager', {
    templateUrl: eyatra_petty_cash_manager_report_list_template_url,
    controller: function(HelperService, $rootScope, $http, $scope, $routeParams, $location) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.type_id = $routeParams.type_id;

        // Type ID 1 -> Manager
        // Type ID 2 -> Cashier
        // Type ID 3 -> Financier
        if (self.type_id == 1) {
            self.permission = self.hasPermission('eyatra-petty-cash-manager-report');
        } else if (self.type_id == 2) {
            self.permission = self.hasPermission('eyatra-petty-cash-cashier-report');
        } else if (self.type_id == 3) {
            self.permission = self.hasPermission('eyatra-petty-cash-financier-report');
        } else {
            $location.path('/permission-denied')
            return;
        }
        if (self.permission == false) {
            $location.path('/permission-denied')
            return;
        }

        $http.get(
            eyatra_petty_cash_report_filter_data_url + '/' + self.type_id
        ).then(function(response) {
            // console.log(response.data);
            self.employee_list = response.data.employee_list;
            self.outlet_list = response.data.outlet_list;
            self.filter_type_id = response.data.filter_type_id;
            self.filter_outlet_id = response.data.filter_outlet_id;
            self.start_date = response.data.petty_cash_start_date;
            self.end_date = response.data.petty_cash_end_date;
            if (response.data.filter_type_id == '-1') {
                self.filter_type_id = '-1';
            } else {
                self.filter_type_id = response.data.filter_type_id;
            }
            if (response.data.filter_employee_id == '-1') {
                self.filter_employee_id = '-1';
            } else {
                self.filter_employee_id = response.data.filter_employee_id;
            }
            var trip_periods = response.data.petty_cash_start_date + ' to ' + response.data.petty_cash_end_date;
            self.trip_periods = trip_periods;

            setTimeout(function() {
                get_employees(self.filter_outlet_id, status = 0);
                $('#from_date').val(self.start_date);
                $('#to_date').val(self.end_date);
                dataTable.draw();
            }, 1500);
            $rootScope.loading = false;
        });

        var dataTable = $('#eyatra_petty_cash_filter_table').DataTable({
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
                url: laravel_routes['eyatraPettyCashData'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.type_id = $('#type_id').val();
                    d.employee_id = $('#employee_id').val();
                    d.list_type = self.type_id;
                    d.from_date = $('#from_date').val();
                    d.to_date = $('#to_date').val();
                    d.outlet_id = $('#outlet_id').val();
                }
            },

            columns: [
                { data: 'action', searchable: false, class: 'action' },
                { data: 'petty_cash_type', name: 'petty_cash_type.name', searchable: true },
                { data: 'date', searchable: false },
                { data: 'ecode', name: 'employees.code', searchable: true },
                { data: 'ename', name: 'users.name', searchable: true },
                { data: 'outlet_name', name: 'outlets.name', searchable: true },
                { data: 'total', searchable: false },
                { data: 'approval_date', searchable: false },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();

        setTimeout(function() {
            var x = $('.separate-page-header-inner.search .custom-filter').position();
            var d = document.getElementById('eyatra_petty_cash_filter_table_filter');
            x.left = x.left + 15;
            d.style.left = x.left + 'px';
        }, 500);


        $scope.getEmployeeData = function(query) {
            $('#employee_id').val(query);
            dataTable.draw();
        }

        $scope.getType = function(query) {
            $('#type_id').val(query);
            dataTable.draw();
        }

        $scope.getOutletData = function(outlet_id) {
            $('#outlet_id').val(outlet_id)
            dataTable.draw();
            get_employees(outlet_id, status = 1);
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

        $scope.reset_filter = function(query) {
            $('#type_id').val(-1);
            $('#employee_id').val(-1);
            $('#outlet_id').val(-1);
            $('#from_date').val('');
            $('#to_date').val('');
            self.trip_periods = '';
            if (self.type_id == 3) {
                get_employees(self.filter_outlet_id, status = 1);
            }
            self.filter_type_id = '-1';
            self.filter_employee_id = '-1';
            self.filter_outlet_id = '-1';
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
                        self.filter_employee_id = '-1';
                    }
                    self.employee_list = res.employee_list;
                    $scope.$apply()
                });
        }

        $rootScope.loading = false;
    }
});