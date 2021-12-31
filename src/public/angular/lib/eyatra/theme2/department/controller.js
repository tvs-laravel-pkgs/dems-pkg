app.component('eyatraDepartment', {
    templateUrl: eyatra_department_list_template_url,
    controller: function(HelperService, $rootScope, $http, $scope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.permissionadd = self.hasPermission('eyatra-all-department-view');
        var dataTable = $('#eyatra_department_table').DataTable({
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
                url: laravel_routes['listEYatraDepartment'],
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
                { data: 'name', name: 'departments.name', searchable: true },
                { data: 'short_name', name: 'departments.short_name', searchable: true },
                { data: 'business_id', name: 'departments.business_id', searchable: true },
                { data: 'financial_year', name: 'department_finances.financial_year', searchable: true },
                { data: 'budget_amount', name: 'department_finances.budget_amount', searchable: true },
                { data: 'created_by', name: 'users.name', searchable: true },
                { data: 'status', name: 'departments.deleted_at', searchable: true },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });

        $('.dataTables_length select').select2();
        setTimeout(function() {
            var x = $('.separate-page-header-inner.search .custom-filter').position();
            var d = document.getElementById('eyatra_department_table_filter');
            x.left = x.left + 15;
            d.style.left = x.left + 'px';
        }, 500);
        $('#eyatra_department_table_filter').find('input').addClass("on_focus");
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
        $scope.deleteDepartmentConfirm = function($id) {
            $("#delete_department_id").val($id);
        }

        $scope.deleteDepartment = function() {
            $business_id = $('#delete_department_id').val();
            $http.get(
                department_delete_url + '/' + $department_id,
            ).then(function(response) {
                // console.log(response.data);
                if (response.data.success) {
                    custom_noty('success', 'Department Deleted Successfully');
                    dataTable.ajax.reload(function(json) {});

                } else {
                    custom_noty('error', 'Department not Deleted');
                }
            });
        }
        $rootScope.loading = false;

    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('eyatraDepartmentForm', {
    templateUrl: department_form_template_url,
    controller: function($http, $location, HelperService, $routeParams, $rootScope, $scope) {
        $form_data_url = typeof($routeParams.id) == 'undefined' ? department_form_data_url : department_form_data_url + '/' + $routeParams.id;
        var self = this;

        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        self.budget_tab = 0;
        $http.get(
            $form_data_url
        ).then(function(response) {
            if (!response.data.success) {
                custom_noty('error', response.data.error);
                $location.path('/departments')
                $scope.$apply()
                return;
            }
            self.department = response.data.department;
            self.departmentFinances = response.data.departmentFinance;
            self.company_list = response.data.company_list;
            self.business_list = response.data.business_list;
            self.departmentFinance_removal_id = [];
            self.status = response.data.status;
            self.action = response.data.action;
            console.log(response.data.business_list);
            console.log(self.departmentFinances);
            // console.log(response.data);
            //self.action = response.data.action;
            $rootScope.loading = false;
            angular.forEach(response.data.departmentFinance, function(value, key) {
                if (value.read == true) {
                    $scope.readonly == true;
                    console.log(value.read);
                } else {
                    $scope.readonly == false;
                }
            });

        });
        if (self.action == 'Edit') {
            $.each(self.departmentFinances, function(index, value) {
                setTimeout(function() {}, 500);

            });
        }
        //ADD LOCALCONVEYANCE
        if (self.action == 'New') {
            self.businessFinances = [];
            arr_ind = 1;
            self.adddepartmentfinance = function() {
                self.departmentFinances.push({
                    financial_year: '2020-2021'.id,
                    budget_amount: '',
                    read: 'false',
                });
            }
        }

        //ADD BUSINESS FINANCE
        self.adddepartmentfinance = function(departmentFinance_array) {
            var departmentFinance_array = self.departmentFinances;
            var arr_length = departmentFinance_array.length;
            // console.log(trip_array);
            arr_vol = arr_length - 1;
            if (!(departmentFinance_array[arr_vol]) || !(departmentFinance_array[arr_vol].financial_year.id)) {
                $noty = new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: 'Please Select Financial Year',
                    animation: {
                        speed: 500 // unavailable - no need
                    },
                }).show();
                setTimeout(function() {
                    $noty.close();
                }, 10000);
            }
            self.departmentFinances.push({
                financial_year: '2020-2021'.id,
                budget_amount: '',
                read: 'false',
            });

        }

        //REMOVE LOCALCONVEYANCE 
        self.removedepartmentfinance = function(index, departmentFinance_id) {
            if (departmentFinance_id) {
                self.departmentFinance_removal_id.push(departmentFinance_id);
                $('#departmentFinance_removal_id').val(JSON.stringify(self.departmentFinance_removal_id));
            }
            self.departmentFinances.splice(index, 1);
        }
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
        var form_id = '#department-form';
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
                'name': {
                    required: true,
                    minlength: 3,
                    maxlength: 255,
                },
                'short_name': {
                    required: true,
                    minlength: 3,
                    maxlength: 255,
                },
                'company_id': {
                    required: true,
                },
                'business_id': {
                    required: true,
                },
                'financial_year': {
                    required: true,
                },
                'budget_amount': {
                    required: true,
                    number: true,
                },
            },
            messages: {
                'name': {
                    required: 'Department Name is Required',
                    minlength: 'Please enter minimum of 3 letters',
                    maxlength: 'Please enter maximum of 255 letters',
                },
                'short_name': {
                    required: 'Department Short Name is Required',
                },
                'comapny_id': {
                    required: 'Please Select Company',
                },
                'business_id': {
                    required: 'Please Select Business',
                },
                'budget_amount': {
                    required: 'Budget Amount is Required',
                    number: 'Enter Numbers Only',
                    decimal: 'Please enter a correct number, format 0.00',
                    maxlength: 'Enter Maximum 10 Digit Number',

                },

            },
            submitHandler: function(form) {

                let formData = new FormData($(form_id)[0]);
                $('#submit').button('loading');
                $.ajax({
                        url: laravel_routes['saveEYatraDepartment'],
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
                            custom_noty('success', 'Department saved successfully');
                            $location.path('/departments')
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