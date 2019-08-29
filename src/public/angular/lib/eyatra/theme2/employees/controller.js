app.component('eyatraEmployees', {
    templateUrl: eyatra_employee_list_template_url,
    controller: function(HelperService, $rootScope, $http, $scope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        var dataTable = $('#eyatra_employee_table').DataTable({
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
                url: laravel_routes['listEYatraEmployee'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.outlet = $('#outlet_id').val();
                    d.role = $('#role_id').val();
                    d.grade = $('#grade_id').val();
                }
            },

            columns: [
                { data: 'action', searchable: false, class: 'action text-left' },
                { data: 'code', name: 'e.code', searchable: true },
                { data: 'name', name: 'users.name', searchable: true },
                { data: 'outlet_code', name: 'o.code', searchable: true },
                { data: 'manager_code', name: 'm.code', searchable: true },
                { data: 'grade', name: 'grd.name', searchable: true },
                { data: 'status', name: 'c.name', searchable: false },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();
        setTimeout(function() {
            var x = $('.separate-page-header-inner.search .custom-filter').position();
            var d = document.getElementById('eyatra_employee_table_filter');
            x.left = x.left + 15;
            d.style.left = x.left + 'px';
        }, 500);

        //Filter
        $http.get(
            employee_filter_url
        ).then(function(response) {
            console.log(response);
            self.grade_list = response.data.grade_list;
            self.outlet_list = response.data.outlet_list;
            self.role_list = response.data.role_list;
            $rootScope.loading = false;
        });
        var dataTableFilter = $('#eyatra_employee_table').dataTable();
        $scope.onselectOutlet = function(id) {
            $('#outlet_id').val(id);
            dataTableFilter.fnFilter();
        }
        $scope.onselectRole = function(id) {
            $('#role_id').val(id);
            dataTableFilter.fnFilter();
        }
        $scope.onselectGrade = function(id) {
            $('#grade_id').val(id);
            dataTableFilter.fnFilter();
        }
        $scope.resetForm = function() {
            $('#outlet_id').val(null);
            $('#role_id').val(null);
            $('#grade_id').val(null);
            dataTableFilter.fnFilter();
        }

        $scope.deleteEmployee = function(id) {
            $('#del').val(id);
        }
        $scope.confirmDeleteEmployee = function() {
            $id = $('#del').val();
            $http.get(
                employee_delete_url + '/' + $id,
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
                        text: 'Employee Deleted Successfully',
                    }).show();
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

app.component('eyatraEmployeeForm', {
    templateUrl: employee_form_template_url,
    controller: function($http, $location, $location, HelperService, $routeParams, $rootScope, $scope) {
        $form_data_url = typeof($routeParams.employee_id) == 'undefined' ? employee_form_data_url : employee_form_data_url + '/' + $routeParams.employee_id;
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
                new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: response.data.error,
                }).show();
                $location.path('/eyatra/employees')
                $scope.$apply()
                return;
            }
            self.employee = response.data.employee;
            self.extras = response.data.extras;
            self.action = response.data.action;
            // console.log(response.data.extras);
            if (self.action == 'Edit') {
                if (self.employee.user.force_password_change == 1) {
                    self.switch_password = 'No';
                    $("#hide_password").hide();
                    $("#password").prop('disabled', true);
                } else {
                    self.switch_password = 'Yes';
                }
                $scope.selectPaymentMode(self.employee.payment_mode_id);
                $scope.getSbuBasedonLob(self.employee.sbu.lob_id);
            } else {
                $("#hide_password").show();
                $("#password").prop('disabled', false);
                self.switch_password = 'Yes';
            }


            $rootScope.loading = false;

        });

        /* Pane Next Button */
        $('.btn-nxt').on("click", function() {
            $('.editDetails-tabs li.active').next().children('a').trigger("click");
        });
        $('.btn-prev').on("click", function() {
            $('.editDetails-tabs li.active').prev().children('a').trigger("click");
        });


        $scope.getSbuBasedonLob = function(lob_id) {
            if (lob_id) {
                //alert(lob_id);
                $.ajax({
                        url: get_sbu_by_lob,
                        method: "POST",
                        data: { lob_id: lob_id },
                    })
                    .done(function(res) {
                        self.extras.sbu_list = [];
                        self.extras.sbu_list = res.sbu_list;
                        $scope.$apply()
                    })
                    .fail(function(xhr) {
                        console.log(xhr);
                    });
            }
        }


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

        $scope.psw_change = function(val) {
            if (val == 'No') {
                $("#hide_password").hide();
                $("#password").prop('disabled', true);
            } else {
                $("#hide_password").show();
                $("#password").prop('disabled', false);
            }
        }

        $.validator.addMethod('positiveNumber',
            function(value) {
                return Number(value) > 0;
            }, 'Enter a positive number.');

        var form_id = '#employee_form';
        var v = jQuery(form_id).validate({
            invalidHandler: function(event, validator) {
                new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: 'Kindly check in each tab to fix errors'
                }).show();
            },
            errorPlacement: function(error, element) {
                error.insertAfter(element)
            },
            ignore: '',
            rules: {
                'code': {
                    required: true,
                    maxlength: 191,
                    minlength: 3,
                },
                'name': {
                    required: true,
                    maxlength: 80,
                    minlength: 3,
                },
                'outlet_id': {
                    required: true,
                },
                'reporting_to_id': {
                    required: true,
                },
                'grade_id': {
                    required: true,
                },
                'date_of_joining': {
                    required: true,
                },
                'aadhar_no': {
                    required: true,
                    maxlength: 16,
                    minlength: 16,
                },
                'pan_no': {
                    required: true,
                    maxlength: 10,
                    minlength: 8,
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
                    min: 1,
                    number: true,
                    // positiveNumber: true,
                },
                'ifsc_code': {
                    required: true,
                    maxlength: 10,
                    minlength: 3,
                },
                'mobile_number': {
                    required: true,
                    minlength: 8,
                    maxlength: 10,
                },
                'email': {
                    email: true,
                    minlength: 6,
                    maxlength: 191,
                },
                'roles': {
                    required: true,
                },
                'username': {
                    required: true,
                    minlength: 4,
                    maxlength: 191,
                },
                'password': {
                    required: function(element) {
                        if ($("#password_change").val() == 'Yes') {
                            return true;
                        } else {
                            return false;
                        }
                    },
                    minlength: 5,
                    maxlength: 16,
                },
            },
            messages: {
                'code': {
                    maxlength: 'Please enter maximum of 191 letters',
                },
                'name': {
                    maxlength: 'Please enter maximum of 80 letters',
                },
            },
            submitHandler: function(form) {

                let formData = new FormData($(form_id)[0]);
                $('#submit').button('loading');
                $.ajax({
                        url: laravel_routes['saveEYatraEmployee'],
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
                                text: 'Employee updated successfully',
                            }).show();
                            $location.path('/eyatra/employees')
                            $scope.$apply()
                        }
                    })
                    .fail(function(xhr) {
                        $('#submit').button('reset');
                        custom_noty('error', 'Something went wrong at server');
                    });
            },
        });

        //SEARCH MANAGER
        self.searchManager = function(query) {
            if (query) {
                return new Promise(function(resolve, reject) {
                    $http
                        .post(
                            search_manager_url, {
                                key: query,
                            }
                        )
                        .then(function(response) {
                            console.log(response.data);
                            resolve(response.data);
                        });
                    //reject(response);
                });
            } else {
                return [];
            }
        }
    }
});

app.component('eyatraEmployeeView', {
    templateUrl: employee_view_template_url,
    controller: function($http, $location, $routeParams, HelperService, $scope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        $http.get(
            employee_view_url + '/' + $routeParams.employee_id
        ).then(function(response) {
            self.employee = response.data.employee;
            self.roles = self.employee.roles ? self.employee.roles.join() : '';
            $scope.selectPaymentMode(self.employee.payment_mode_id);

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

        /* Pane Next Button */
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