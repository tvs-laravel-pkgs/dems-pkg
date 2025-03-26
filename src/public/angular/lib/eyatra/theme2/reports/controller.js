app.component('eyatraGstReport', {
    templateUrl: eyatra_gst_report_template_url,
    controller: function(HelperService, $rootScope, $http, $scope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;

        $http.get(
            laravel_routes['getReportFormDetail']
        ).then(function(res) {
            self.token = res.data.token;
            self.region_list = res.data.region_list;
            self.outlet_list = [];
            self.business_list = res.data.business_list;

            self.report_export_route = res.data.base_url + '/eyatra/gst/report';
            $rootScope.loading = false;
        });

        $scope.getRegionwiseOutletList = () => {
            self.outlet_list = self.outlets = [];
            var region_ids = self.regions.id;
            if (region_ids) {
                $http.get(
                    eyatra_outlet_detail_url + '/' + region_ids
                ).then(function(res) {
                    self.outlet_list = res.data.outlet_list;
                    $rootScope.loading = false;
                });
            }
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
        var form_id = '#report-form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'regions': {
                    required: true,
                },
                'outlets': {
                    required: true,
                },
                'business_ids': {
                    required: true,
                },
                'period': {
                    required: true,
                },
            },
            errorPlacement: function(error, element) {
                error.insertAfter(element);
            },
            invalidHandler: function(event, validator) {
                custom_noty('error', 'You have errors,Please check all tabs');
            },
        });
    }
});

app.component('eyatraEmployeeGstrReport', {
    templateUrl: eyatra_employee_gstr_report_template_url,

    controller: function(HelperService, $rootScope, $http, $scope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;

        $http.get(
            laravel_routes['getReportFormDetail']
        ).then(function(res) {
            self.token = res.data.token;

            //self.region_list = res.data.region_list;
            //self.outlet_list = [];
            self.business_list = res.data.business_list;

            self.report_export_route = res.data.base_url + '/eyatra/employee/gstr/report';

            $rootScope.loading = false;
        });

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

        var form_id = '#gstr-report-form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'businesses': {
                    required: true,
                },
                'period': {
                    required: true,
                },
            },
            errorPlacement: function(error, element) {
                error.insertAfter(element);
            },
            invalidHandler: function(event, validator) {
                custom_noty('error', 'You have errors,Please check all tabs');
            },
        });
    }
});
app.component('eyatraAgentReport', {
    templateUrl: eyatra_agent_report_template_url,
    controller: function(HelperService, $rootScope, $http, $scope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;

        $http.get(
            laravel_routes['getReportFormDetail']
        ).then(function(res) {
            self.token = res.data.token;
            self.business_list = res.data.business_list;

            self.agent_report_export_route = res.data.base_url + '/eyatra/agent/report';
            $rootScope.loading = false;
        });

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
        var form_id = '#report-form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                /*'businesses': {
    required: true,
}, */
'period': {                   required: true,
                },
            },
            errorPlacement: function(error, element) {
                error.insertAfter(element);
            },
            invalidHandler: function(event, validator) {
                custom_noty('error', 'You have errors,Please check all tabs');
            },
        });
    }
});
app.component('eyatraReportList', {
    templateUrl: eyatra_report_list_template_url,
    controller: function(HelperService, $rootScope, $http, $scope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.filter_type_id = null;
        $('#type_id').val(self.filter_type_id);

        $http.get(
            laravel_routes['getReportListFilter']
        ).then(function(res) {
            self.type_list = res.data.type_list;
            $rootScope.loading = false;
        });

        var dataTable = $('#report_table').DataTable({
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
                url: laravel_routes['getReportListData'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.type_id = $('#type_id').val();
                    d.from_date = $('#from_date').val();
                    d.to_date = $('#to_date').val();
                }
            },
            columns: [
                { data: 'type', name: 'configs.name', searchable: true },
                { data: 'name', name: 'report_details.name', searchable: true },
                { data: 'created_date', name: 'report_details.created_at', searchable: false },
                { data: 'action', searchable: false, class: 'action' },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();

        setTimeout(function() {
            var x = $('.separate-page-header-inner.search .custom-filter').position();
            var d = document.getElementById('report_table_filter');
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
        $scope.getFromDateData = function(query) {
            $('#from_date').val(query);
            dataTable.draw();
        }
        $scope.getToDateData = function(query) {
            $('#to_date').val(query);
            dataTable.draw();
        }
        $scope.reset_filter = function(query) {
            $('#type_id').val(-1);
            $('#from_date').val('');
            $('#to_date').val('');
            self.trip_periods = '';
            self.filter_type_id = '';
            setTimeout(function() {
                dataTable.draw();
            }, 500);
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
app.component('eyatraReportView', {
    templateUrl: eyatra_report_view_template_url,
    controller: function(HelperService, $rootScope, $http, $scope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;

        $http.get(
            laravel_routes['getReportViewDetail']
        ).then(function(res) {

            self.token = res.data.token;
            self.business_list = res.data.business_list;

            self.bank_report_export_route = res.data.base_url + '/eyatra/bank-statement/report';
            $rootScope.loading = false;
        });

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
        var form_id = '#bank-statement-form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'business_ids': {
                    required: true,
                },
            },
            errorPlacement: function(error, element) {
                error.insertAfter(element);
            },
            invalidHandler: function(event, validator) {
                custom_noty('error', 'You have errors,Please check all tabs');
            },
        });
    }
});

app.component('eyatraTripReport', {
    templateUrl: eyatra_trip_report_template_url,
    controller: function(HelperService, $rootScope, $http, $scope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;

        $http.get(
            laravel_routes['getReportFormDetail']
        ).then(function(res) {
            self.token = res.data.token;
            self.business_list = res.data.business_list;

            self.report_export_route = res.data.base_url + '/eyatra/trip/report';
            $rootScope.loading = false;
        });

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
        
        var form_id = '#report-form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'period': {                   
                    required: true,
                },
            },
            errorPlacement: function(error, element) {
                error.insertAfter(element);
            },
            invalidHandler: function(event, validator) {
                custom_noty('error', 'You have errors, Please check');
            },
        });
    }
});

app.component('tripDetailsReport', {
    templateUrl: eyatra_trip_report_template_url,
    controller: function(HelperService, $rootScope, $http, $scope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;

        $http.get(
            laravel_routes['getReportFormDetail']
        ).then(function(res) {
            self.token = res.data.token;
            self.business_list = res.data.business_list;

            self.report_export_route = res.data.base_url + '/eyatra/trip-details/report';
            $rootScope.loading = false;
        });

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
        
        var form_id = '#report-form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'period': {                   
                    required: true,
                },
            },
            errorPlacement: function(error, element) {
                error.insertAfter(element);
            },
            invalidHandler: function(event, validator) {
                custom_noty('error', 'You have errors, Please check');
            },
        });
    }
});

app.component('afterCompleteTripReport', {
    templateUrl: eyatra_trip_report_template_url,
    controller: function(HelperService, $rootScope, $http, $scope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;

        $http.get(
            laravel_routes['getReportFormDetail']
        ).then(function(res) {
            self.token = res.data.token;
            self.business_list = res.data.business_list;

            self.report_export_route = res.data.base_url + '/eyatra/after-trip/report';
            $rootScope.loading = false;
        });

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
        
        var form_id = '#report-form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'period': {                   
                    required: true,
                },
            },
            errorPlacement: function(error, element) {
                error.insertAfter(element);
            },
            invalidHandler: function(event, validator) {
                custom_noty('error', 'You have errors, Please check');
            },
        });
    }
});