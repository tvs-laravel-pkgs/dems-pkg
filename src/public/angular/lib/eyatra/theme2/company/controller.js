app.component('eyatraCompany', {
    templateUrl: eyatra_company_list_template_url,
    controller: function(HelperService, $rootScope, $http, $scope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.permissionadd = self.hasPermission('eyatra-all-company-view');
        var dataTable = $('#eyatra_company_table').DataTable({
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
                url: laravel_routes['listEYatraCompany'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.region_id = $('#region_id').val();
                    d.city_id = $('#city_id').val();
                    d.state_id = $('#state_id').val();
                    d.country_id = $('#country_id').val();
                    d.cashier_id = $('#cashier').val();
                }
            },

            columns: [
                { data: 'action', searchable: false, class: 'action', class: 'text-left' },
                { data: 'code', name: 'companies.code', searchable: true },
                { data: 'name', name: 'companies.name', searchable: true },
                { data: 'address', name: 'companies.address', searchable: true },
                { data: 'cin_number', name: 'companies.cin_number', searchable: true },
                { data: 'gst_number', name: 'companies.gst_number', searchable: true },
                { data: 'customer_care_email', name: 'companies.customer_care_email', searchable: true },
                { data: 'customer_care_phone', name: 'companies.customer_care_phone', searchable: true },
                { data: 'created_by', name: 'users.name', searchable: true },
                { data: 'status', name: 'companies.deleted_at', searchable: true },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });

        $('.dataTables_length select').select2();
        setTimeout(function() {
            var x = $('.separate-page-header-inner.search .custom-filter').position();
            var d = document.getElementById('eyatra_company_table_filter');
            x.left = x.left + 15;
            d.style.left = x.left + 'px';
        }, 500);
        $('#eyatra_company_table_filter').find('input').addClass("on_focus");
        $('.on_focus').focus();
        $scope.getCashierData = function(query) {
            $('#cashier').val(query);
            dataTable.draw();
        }
        $scope.getRegionData = function(query) {
            $('#region_id').val(query);
            dataTable.draw();
        }
        $scope.getCityData = function(query) {
            $('#city_id').val(query);
            dataTable.draw();
        }
        $scope.getStateData = function(query) {
            $('#state_id').val(query);
            dataTable.draw();
        }
        $scope.getCountryData = function(query) {
            //alert(1);
            $('#country_id').val(query);
            dataTable.draw();
        }

        $scope.reset_filter = function(query) {
            $('#region_id').val(-1);
            $('#city_id').val(-1);
            $('#state_id').val(-1);
            $('#country_id').val(-1);
            $('#cashier').val();
            dataTable.draw();
        }
        $scope.deleteCompanyConfirm = function($id) {
            $("#delete_company_id").val($id);
        }

        $scope.deleteCompany = function() {
            $company_id = $('#delete_company_id').val();
            $http.get(
                company_delete_url + '/' + $company_id,
            ).then(function(response) {
                // console.log(response.data);
                if (response.data.success) {
                    custom_noty('success', 'Company Deleted Successfully');
                    dataTable.ajax.reload(function(json) {});

                } else {
                    custom_noty('error', 'Company not Deleted');
                }
            });
        }
        $rootScope.loading = false;

    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('eyatraCompanyForm', {
    templateUrl: company_form_template_url,
    controller: function($http, $location, HelperService, $routeParams, $rootScope, $scope) {
        $form_data_url = typeof($routeParams.id) == 'undefined' ? company_form_data_url : company_form_data_url + '/' + $routeParams.id;
        var self = this;

        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        self.budget_tab = 0;
        $http.get(
            $form_data_url
        ).then(function(response) {
            if (!response.data.success) {
                custom_noty('error', response.data.error);
                $location.path('/companies')
                $scope.$apply()
                return;
            }
            self.company = response.data.company;
            self.financial_year_list = response.data.financial_year_list;
            self.status = response.data.status;
            self.action = response.data.action;
            console.log(response.data);
            $scope.Invisible = true;
            if (self.company.additional_approve == 1) {
                $scope.visible = true;
                $scope.Invisible = false;
            } else if (self.company.additional_approve == 0) {
                $scope.Invisible = true;
                $scope.visible = false;
            }
            if (self.action == "Add") {
                self.company.company_budgets = [];
                self.company.company_budgets.push({
                    financial_year_id: '',
                    outstation_budget_amount: '',
                    local_budget_amount: '',
                });
            }
            //self.action = response.data.action;
            $rootScope.loading = false;

        });

        $('.btn-nxt').on("click", function() {
            $('.editDetails-tabs li.active').next().children('a').trigger("click");
            // tabPaneFooter();
        });
        $('.btn-prev').on("click", function() {
            $('.editDetails-tabs li.active').prev().children('a').trigger("click");
            // tabPaneFooter();
        });
        $('.btn-pills').on("click", function() {
            // tabPaneFooter();
        });
        $scope.btnNxt = function() {}
        $scope.prev = function() {}

        $scope.addMore = function(array) {
            var company_budgets_array = self.company.company_budgets;
            var arr_length = company_budgets_array.length;
            self.company.company_budgets.push({
                financial_year_id: '',
                outstation_budget_amount: '',
                local_budget_amount: '',
            });
        }
        self.removeBudget = function(index, id) {
            self.company.company_budgets.splice(index, 1);
        }
        var form_id = '#company-form';
        var v = jQuery(form_id).validate({
            errorPlacement: function(error, element) {
                if (element.hasClass("code")) {
                    error.appendTo($('.code_error'));
                } else if (element.hasClass("name")) {
                    error.appendTo($('.name_error'));
                } else if (element.hasClass("line_1")) {
                    error.appendTo($('.line1_error'));
                } else if (element.hasClass("line_2")) {
                    error.appendTo($('.line2_error'));
                } else {
                    error.insertAfter(element)
                }
            },

            ignore: '',
            rules: {
                'code': {
                    required: true,
                    minlength: 3,
                    maxlength: 12,
                },
                'name': {
                    required: true,
                    minlength: 3,
                    maxlength: 191,
                },
                'address': {
                    required: true,
                    maxlength: 250,
                },
                'cin_number': {
                    maxlength: 191,
                },
                'customer_care_email': {
                    required: true,
                    email: true,
                    maxlength: 255,
                },
                'customer_care_phone': {
                    required: true,
                    maxlength: 10,
                    minlength: 10,
                    number: true,
                },
                'reference_code': {
                    maxlength: 10,
                },
            },
            submitHandler: function(form) {

                let formData = new FormData($(form_id)[0]);
                $('#submit').button('loading');
                $.ajax({
                        url: laravel_routes['saveEYatraCompany'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        // console.log(res.success);
                        if (!res.success) {
                            $('#submit').button('reset');
                            var errors = '';
                            for (var i in res.errors) {
                                errors += '<li>' + res.errors[i] + '</li>';
                            }
                            custom_noty('error', errors);
                        } else {
                            custom_noty('success', 'Company saved successfully');
                            $location.path('/companies')
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

app.component('eyatraCompanyView', {
    templateUrl: company_view_template_url,

    controller: function($http, $location, $routeParams, HelperService, $scope, $rootScope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        $http.get(
            company_view_url + '/' + $routeParams.id
        ).then(function(response) {
            self.company = response.data.company;
            self.status = response.data.status;
            self.financial_year = response.data.financial_year;
            self.outstation_budget_amount = response.data.outstation_budget_amount;
            self.local_budget_amount = response.data.local_budget_amount;

            self.action = response.data.action;
            console.log(response.data.company);
        });
        $('.btn-nxt').on("click", function() {
            $('.editDetails-tabs li.active').next().children('a').trigger("click");
        });
        $('.btn-prev').on("click", function() {
            $('.editDetails-tabs li.active').prev().children('a').trigger("click");
        });

        $rootScope.loading = false;

    }
});