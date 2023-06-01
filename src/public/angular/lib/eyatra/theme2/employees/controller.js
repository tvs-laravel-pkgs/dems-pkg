app.component('eyatraEmployees', {
    templateUrl: eyatra_employee_list_template_url,
    controller: function(HelperService, $rootScope, $http, $scope, $location) {
        // console.log('s');
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.permissionadd = self.hasPermission('eyatra-employee-add');
        self.permissionimport = self.hasPermission('eyatra-import-employee');


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
                    d.manager = $('#manager_id').val();
                    d.role = $('#role_id').val();
                    d.grade = $('#grade_id').val();
                }
            },

            columns: [
                { data: 'action', searchable: false, class: 'action', class: 'text-left' },
                { data: 'code', name: 'e.code', searchable: true },
                { data: 'name', name: 'u.name', searchable: true },
                { data: 'outlet_code', name: 'o.code', searchable: true },
                { data: 'manager_code', name: 'm.code', searchable: true },
                // { data: 'manager_name', name: 'mngr.name', searchable: true },
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
            self.manager_list = response.data.manager_list;
            console.log(self.manager_list);
            self.manager_id = response.data.filter_manager_id;
            self.role_list = response.data.role_list;
            console.log(response.data.filter_outlet_id);
            if (response.data.filter_outlet_id == '-1') {
                self.filter_outlet_id = '-1';
            } else {
                self.filter_outlet_id = response.data.filter_outlet_id;
            }
            setTimeout(function() {
                //alert('in');
                get_managers(self.filter_outlet_id, status = 0);
            }, 1500);
            $rootScope.loading = false;
        });
        var dataTableFilter = $('#eyatra_employee_table').dataTable();
        $scope.onselectOutlet = function(id) {
            $('#outlet_id').val(id);
            dataTableFilter.fnFilter();
            get_managers(id, status = 1);
            //alert('out');
        }
        $scope.onselectManager = function(id) {
            $('#manager_id').val(id);
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
            $('#manager_id').val(null);
            $('#grade_id').val(null);
            $('#role_id').val(null);
            self.manager_id = '';
            dataTableFilter.fnFilter();
            get_managers(-1, status = 1);
        }
        $scope.resetForm();
        $scope.deleteEmployee = function(id) {
            $('#del').val(id);
        }

        function get_managers(outlet_id, status) {
            $.ajax({
                    method: "POST",
                    url: laravel_routes['getManagerByOutlet'],
                    data: {
                        outlet_id: outlet_id
                    },
                })
                .done(function(res) {
                    self.manager_list = [];
                    if (status == 1) {
                        self.manager_id = '';
                    }
                    self.manager_list = res.manager_list;
                    console.log(self.manager_list);
                    $scope.$apply()
                });
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
                        text: 'Employee Deleted Successfully',
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

        $scope.sendSms = function() {
            var form_id = '#send_sms';
            var v = jQuery(form_id).validate({
                invalidHandler: function(event, validator) {
                    $noty = new Noty({
                        type: 'error',
                        layout: 'topRight',
                        text: 'Kindly check in each tab to fix errors',
                        animation: {
                            speed: 500 // unavailable - no need
                        },
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 5000);
                },
                errorPlacement: function(error, element) {
                    error.insertAfter(element)
                },
                ignore: '',
                rules: {
                    'sms_mobile_number': {
                        required: true,
                        maxlength: 10,
                        minlength: 10,
                    },
                },
                messages: {
                    'sms_mobile_number': {
                        maxlength: 'Please enter maximum of 80 letters',
                    },
                },
                submitHandler: function(form) {

                    let formData = new FormData($(form_id)[0]);
                    $('#submit').button('loading');
                    $.ajax({
                            url: laravel_routes['getSendSms'],
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                        })
                        .done(function(res) {
                            //console.log(res.success);
                            if (!res.success) {
                                $('#submit').button('reset');
                                var errors = '';
                                for (var i in res.errors) {
                                    errors += '<li>' + res.errors[i] + '</li>';
                                }
                                custom_noty('error', errors);
                            } else {
                                console.log('success' + res.msg);
                                $noty = new Noty({
                                    type: 'success',
                                    layout: 'topRight',
                                    text: 'Sms Send Sucessfully!!!',
                                    animation: {
                                        speed: 500 // unavailable - no need
                                    },
                                }).show();
                                dataTable.ajax.reload(function(json) {});
                                $('#submit').button('reset');
                                $('#sms_send_modal').modal('hide');
                                setTimeout(function() {
                                    $noty.close();
                                }, 2500);
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


        $rootScope.loading = false;

    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('eyatraEmployeeForm', {
    templateUrl: employee_form_template_url,
    controller: function($http, $location, $location, HelperService, $routeParams, $rootScope, $scope) {
        $form_data_url = typeof($routeParams.employee_id) == 'undefined' ? employee_form_data_url : employee_form_data_url + '/' + $routeParams.employee_id
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
                }, 5000);
                $location.path('/employees')
                $scope.$apply()
                return;
            }

            self.employee = response.data.employee;
            self.extras = response.data.extras;
            console.log(response.data.employee);

            self.action = response.data.action;
            if (response.data.employee.payment_mode_id == null || !response.data.employee.payment_mode_id) {
                self.employee.payment_mode_id = 3244;
            }

            if (self.action == 'Add') {
                $('#visit-single').prop('checked', true);
            }

            if (self.action == 'Edit') {
                if (response.data.is_grade_active == '0') {
                    self.employee.grade_id = '';
                    self.employee.designation_id = '';
                }
                self.employee.reporting_to_id = response.data.employee.reporting_to_name.id;
                self.switch_password = 'No';
                $("#hide_password").hide();
                $("#password").prop('disabled', true);
                $scope.getDesignation(self.employee.grade_id);
                //$scope.selectPaymentMode(self.employee.payment_mode_id);
                $scope.getApiData(self.employee.code);
                $scope.getSbuBasedonLob(self.employee.sbu.lob_id);
                $scope.getDepartmentBasedonBusiness(self.employee.department.business_id);
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

        $scope.getApiData = function(code, data_source) {
            if (data_source == 1) {
                $.ajax({
                        method: "POST",
                        url: laravel_routes['getEmployeeFromApi'],
                        data: {
                            code: code
                        },
                    })
                    .done(function(res) {
                        // console.log(res.success);
                        if (!res.success) {
                            console.log(self.employee.data_source);
                            var errors = '';
                            for (var i in res.errors) {
                                errors += '<li>' + res.errors[i] + '</li>';
                            }
                            //self.employee.data_source = 1;
                            console.log(self.employee.data_source);
                            custom_noty('error', errors);
                        } else {
                            self.employee = [];
                            self.employee = res.employee;
                            self.employee.reporting_to = res.employee.reporting_to.replace(/ /g, "") + '-' + res.employee.reporting_to_name;
                            self.employee.payment_mode_id = '3244';
                            self.employee.user = {
                                name: res.employee.name,
                                mobile_number: res.employee.mobile_number,
                                username: res.employee.code,
                                password: 'Tvs@123',
                            };
                            self.employee.department = {
                                business_id: res.employee.business_id,
                                id: res.employee.department_id,
                            };
                            self.employee.sbu = {
                                lob_id: res.employee.lob_id,
                                id: res.employee.sbu_id,
                            };
                            console.log(self.employee);
                            $scope.$apply()
                        }
                    })
                    .fail(function(xhr) {
                        $('#submit').button('reset');
                        custom_noty('error', 'Something went wrong at server');
                    });
            }
        }

        $scope.getSbuBasedonLob = function(lob_id) {

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
        $scope.getDepartmentBasedonBusiness = function(business_id) {

            //alert(business_id);
            $.ajax({
                    url: get_department_by_business,
                    method: "POST",
                    data: { business_id: business_id },
                })
                .done(function(res) {
                    self.extras.department_list = [];
                    self.extras.department_list = res.department_list;
                    $scope.$apply()
                })
                .fail(function(xhr) {
                    console.log(xhr);
                });

        }

        $scope.getDesignation = function(grade_id) {

            $.ajax({
                    url: get_designation_by_grade,
                    method: "POST",
                    data: { grade_id: grade_id },
                })
                .done(function(res) {

                    self.extras.designation_list = [];
                    self.extras.designation_list = res.designation_list;
                    $scope.$apply()
                })
                .fail(function(xhr) {
                    console.log(xhr);
                });

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

        $.validator.addMethod("pwcheck", function(value) {
            if (value == '') return true;
            return /^[A-Za-z0-9\d=!\-@._*]*$/.test(value) // consists of only these
                &&
                /[a-z]/.test(value) // has a lowercase letter
                &&
                /[A-Z]/.test(value) // has a uppercase letter
                &&
                /[=!\-@._*]/.test(value) // has a uppercase letter
                &&
                /\d/.test(value) // has a digit
        }, 'Use strong password with atleast one uppercase and digit and special symbol');

        var form_id = '#employee_form';
        var v = jQuery(form_id).validate({
            invalidHandler: function(event, validator) {
                $noty = new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: 'Kindly check in each tab to fix errors',
                    animation: {
                        speed: 500 // unavailable - no need
                    },
                }).show();
                setTimeout(function() {
                    $noty.close();
                }, 5000);
            },
            /*errorPlacement: function(error, element) {
                error.insertAfter(element)
            },*/
            errorPlacement: function(error, element) {
                if (element.hasClass("joining")) {
                    error.appendTo($('.joining_error'));
                } else if (element.hasClass("employee_password_check")) {
                    error.appendTo($('.password_error'));
                }
                // else if (element.hasClass("company_code")) {
                //     error.appendTo($('.company_code_error'));
                // } 
                else {
                    error.insertAfter(element)
                }
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
                'grade_id': {
                    required: true,
                },
                'date_of_joining': {
                    required: true,
                },
                // 'aadhar_no': {
                //     required: true,
                //     maxlength: 12,
                //     minlength: 12,
                // },
                // 'pan_no': {
                //     required: true,
                //     maxlength: 10,
                //     minlength: 8,
                // },
                'bank_name': {

                    required: function(element) {
                        return false;
                        if ($("#bank").is(':checked')) {
                            return true;
                        } else {
                            return false;
                        }
                    },
                    maxlength: 100,
                    // minlength: 3,
                },
                'branch_name': {
                    required: function(element) {
                        return false;
                        if ($("#bank").is(':checked')) {
                            return true;
                        } else {
                            return false;
                        }
                    },
                    maxlength: 50,
                    // minlength: 3,
                },
                'account_name': {
                    required: function(element) {
                        return false;
                        if ($("#bank").is(':checked')) {
                            return true;
                        } else {
                            return false;
                        }
                    },
                    maxlength: 50,
                    // minlength: 3,
                },
                'account_number': {
                    required: function(element) {
                        return false;
                        if ($("#bank").is(':checked')) {
                            return true;
                        } else {
                            return false;
                        }
                    },
                    maxlength: 20,
                    // minlength: 3,
                    // min: 1,
                    number: true,
                    // positiveNumber: true,
                },
                'ifsc_code': {
                    required: function(element) {
                        return false;
                        if ($("#bank").is(':checked')) {
                            return true;
                        } else {
                            return false;
                        }
                    },
                    maxlength: 15,
                    minlength: 3,
                },
                'cheque_favour': {
                    required: function(element) {
                        return false;
                        if ($("#cheque").is(':checked')) {
                            return true;
                        } else {
                            return false;
                        }
                    },
                    maxlength: 100,
                    minlength: 3,
                },
                'type_id': {
                    required: function(element) {
                        return false;
                        if ($("#wallet").is(':checked')) {
                            return true;
                        } else {
                            return false;
                        }
                    },
                },
                'value': {
                    required: function(element) {
                        return false;
                        if ($("#wallet").is(':checked')) {
                            return true;
                        } else {
                            return false;
                        }
                    },
                    maxlength: 20,
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
                    'pwcheck': true,
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
                        // console.log(res.success);
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
                                text: res.message,
                                animation: {
                                    speed: 500 // unavailable - no need
                                },
                            }).show();
                            setTimeout(function() {
                                $noty.close();
                            }, 5000);
                            $location.path('/employees')
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
                            // console.log(response.data);
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

            if (response.data.is_grade_active == '0') {
                self.employee.grade = [];
                self.employee.designation = [];
            }
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

//Employees import 
app.component('eyatraJobsImportList', {
    templateUrl: import_jobs_list_template_url,
    controller: function(HelperService, $rootScope, $http, $scope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.permission = self.hasPermission('eyatra-import-jobs');

        var dataTable = $('#eyatra_employee_import_table').DataTable({
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
                url: get_import_jobs_list_url,
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.from_date = $('.from').val();
                    d.to_date = $('.to').val();
                    d.type = $('#type').val();
                }
            },

            columns: [
                { data: 'action', name: 'action', class: 'action' },
                { data: 'import_date', searchable: false },
                { data: 'type', name: 'type.name', searchable: true },
                { data: 'user_name', name: 'users.name', searchable: true },
                { data: 'total_records', searchable: false },
                { data: 'import_status', name: 'import_status.status', searchable: true },
                { data: 'processed_status', name: 'import_status.status', searchable: true },
                { data: 'job_status', name: 'import_status.status', searchable: true },
                { data: 'server_status', name: 'import_status.status', searchable: true },

            ],
            // rowCallback: function(row, data) {
            //     $(row).addClass('highlight-row');
            // }
        });
        $('.dataTables_length select').select2();
        setTimeout(function() {
            var x = $('.separate-page-header-inner.search .custom-filter').position();
            var d = document.getElementById('eyatra_employee_import_table_filter');
            x.left = x.left + 15;
            d.style.left = x.left + 'px';
        }, 500);

        //Filter
        $http.get(
            get_import_jobs_form_data_url
        ).then(function(response) {
            self.import_type_list = response.data.import_type_list;
        });

        var dataTableFilter = $('#eyatra_employee_import_table').dataTable();

        $('.from,.to').on('change', function() {
            dataTableFilter.fnFilter();
        });
        $scope.onselectType = function(type) {
            $("#type").val(type);
            dataTable.fnFilter();
        }
        $scope.resetForm = function() {
            $('.from').val(null);
            $('.to').val(null);
            $('#type').val(null);
            dataTableFilter.fnFilter();
        }
        $scope.resetForm();
        setInterval(function() {
            dataTableFilter.fnDraw();
        }, 6000);
        $(document).on('click', '#update_job_import_status', function(e) {
            var id = $(this).attr('data-id');
            var data = 'id=' + id;
            $http.post(
                update_import_jobs_url, { id: id }
            ).then(function(response) {
                dataTable.fnDraw();
            });

        });
        $rootScope.loading = false;

    }
});

app.component('importJobs', {
    templateUrl: import_jobs_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope) {
        var self = this;
        $http.get(
            get_import_jobs_form_data_url
        ).then(function(response) {

            self.import_type_list = response.data.import_type_list;
        });

        $scope.onSelectedImportType = function(type) {
            if (type == 3380) {
                $('#employee_import').show();
                $('#outlet_import').hide();
                $('#city_import').hide();
                $('#grade_import').hide();
            } else if (type == 3381) {
                $('#employee_import').hide();
                $('#city_import').hide();
                $('#grade_import').hide();
                $('#outlet_import').show();
            } else if (type == 3382) {
                $('#employee_import').hide();
                $('#outlet_import').hide();
                $('#city_import').show();
                $('#grade_import').hide();
            } else if (type == 3383) {
                $('#employee_import').hide();
                $('#outlet_import').hide();
                $('#city_import').hide();
                $('#grade_import').show();
            } else {
                $('#employee_import').hide();
                $('#outlet_import').hide();
                $('#city_import').hide();
                $('#grade_import').hide();

            }
        }


        /* File Upload Function */
        /* Main Function */
        var img_close = $('#img-close').html();
        var img_file = $('#img-file').html();
        var del_arr = [];
        $(".form-group").on('change', '.input-file', function() {
            var html = [];
            del_arr = [];
            $(".insert-file").empty();
            $(this.files).each(function(i, v) {
                html.push("<div class='file-return-parent'>" + img_file + "<p class='file-return'>" + v.name + "</p><button type='button' onclick='angular.element(this).scope().deletefiles(this)' class='remove-hn btn' >" + img_close + "</button></div>");
                del_arr.push(v.name);
            });
            $(".insert-file").append(html);
        });
        /* Remove Function */
        $scope.deletefiles = function(this_parents) {
            var del_name = $(this_parents).siblings().text();
            var del_index = del_arr.indexOf(del_name);
            if (del_index >= 0) {
                del_arr.splice(del_index, 1);
            }
            $(this_parents).parent().remove();
            $(".file_check").val('');
        }

        $('#submit_employee_import').click(function() {
            var form_id = form_ids = '#employee-import-form';
            var v = jQuery(form_ids).validate({
                ignore: '',
                rules: {

                    'import_type_id': {
                        required: true,
                    },
                },

                submitHandler: function(form) {
                    let formData = new FormData($(form_id)[0]);
                    $('#submit_employee_import').button('loading');
                    $.ajax({
                            url: save_import_jobs_url,
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                        })
                        .done(function(res) {

                            if (!res.success) {
                                $('#submit_employee_import').button('reset');
                                // $('#submit_employee_import').prop('disabled', true);
                                var errors = '';
                                for (var i in res.errors) {
                                    errors += '<li>' + res.errors[i] + '</li>';
                                }
                                // custom_noty('error', errors);

                                new Noty({
                                    type: 'error',
                                    layout: 'topRight',
                                    text: res.errors,
                                }).show();

                            } else {
                                $noty = new Noty({
                                    type: 'success',
                                    layout: 'topRight',
                                    text: 'Upload in Progress',
                                    animation: {
                                        speed: 500 // unavailable - no need
                                    },
                                }).show();
                                setTimeout(function() {
                                    $noty.close();
                                }, 5000);
                                $location.path('/import/job/list')
                                $scope.$apply()
                            }
                        })
                        .fail(function(xhr) {
                            $('#submit_employee_import').button('reset');
                            new Noty({
                                type: 'error',
                                layout: 'topRight',
                                text: 'Something went wrong at server',
                            }).show();

                        });
                },
            });
        });



    }
});

app.component('hrmsEmployeeSyncLogList', {
    templateUrl: hrms_employee_sync_log_list_template_url,
    controller: function(HelperService, $rootScope, $scope, $http, $routeParams) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.type_id = $routeParams.type_id;

        if(self.type_id == 3961){
            //EMPLOYEE ADDITION
            if(!self.hasPermission('hrms-to-travelex-employee-addition')){
                window.location = "#!/permission-denied";
                return false;
            }
        }else if(self.type_id == 3962){
            //EMPLOYEE UPDATION
            if(!self.hasPermission('hrms-to-travelex-employee-updation')){
                window.location = "#!/permission-denied";
                return false;
            }
        }else if(self.type_id == 3963){
            //EMPLOYEE DELETION
            if(!self.hasPermission('hrms-to-travelex-employee-deletion')){
                window.location = "#!/permission-denied";
                return false;
            }
        }else if(self.type_id == 3964){
            //EMPLOYEE REPORTING TO UPDATION
            if(!self.hasPermission('hrms-to-travelex-employee-reporting-to-updation')){
                window.location = "#!/permission-denied";
                return false;
            }
        }

        $http.get(
            laravel_routes['getHrmsEmployeeSyncLogFilterData']
        ).then(function(response) {
            self.user_company = response.data.user_company; 
        });

        setTimeout(function() {
            let cols = null;
            if(self.type_id == 3961){
                //EMPLOYEE ADDITION
                cols = [
                    { data: 'action', searchable: false, class: 'action', class: 'text-left' },
                    { data: 'from_date_time',  searchable: false },
                    { data: 'to_date_time',  searchable: false },
                    { data: 'new_count',  searchable: false },
                ];
            }else if(self.type_id == 3962){
                //EMPLOYEE UPDATION
                cols = [
                    { data: 'action', searchable: false, class: 'action', class: 'text-left' },
                    { data: 'from_date_time',  searchable: false },
                    { data: 'to_date_time',  searchable: false },
                    { data: 'update_count',  searchable: false },
                ];
            }
            else if(self.type_id == 3963){
                //EMPLOYEE DELETION
                cols = [
                    { data: 'action', searchable: false, class: 'action', class: 'text-left' },
                    { data: 'from_date_time',  searchable: false },
                    { data: 'to_date_time',  searchable: false },
                    { data: 'delete_count',  searchable: false },
                ];
            }
            else if(self.type_id == 3964){
                //EMPLOYEE REPORTING TO UPDATION
                cols = [
                    { data: 'action', searchable: false, class: 'action', class: 'text-left' },
                    { data: 'from_date_time',  searchable: false },
                    { data: 'to_date_time',  searchable: false },
                    { data: 'update_count',  searchable: false },
                ];
            }

            var dataTable = $('#employee_sync_log_list_table').DataTable({
                stateSave: true,
                "dom": dom_structure_separate,
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
                    url: laravel_routes['getHrmsEmployeeSyncLogList'],
                    type: "GET",
                    dataType: "json",
                    data: function(d) {
                        d.type_id = self.type_id;
                    }
                },
                columns: cols,
                rowCallback: function(row, data) {
                    $(row).addClass('highlight-row');
                }
            });

            // $('#eyatra_designation_table_filter').find('input').addClass("on_focus");
            $('.on_focus').focus();
            $('.dataTables_length select').select2();
            if(self.type_id == 3961){
                $('.separate-page-header-content .data-table-title').html('<p class="breadcrumb">Masters / HRMS To Travelex Employee Addition</p><h3 class="title">HRMS To Travelex Employee Addition</h3>');
                $('.add_new_button').html('<button type="button" class="btn btn-secondary employee-addition-sync">' +
                'Sync' +'</button>'); 
            }else if(self.type_id == 3962){
                $('.separate-page-header-content .data-table-title').html('<p class="breadcrumb">Masters / HRMS To Travelex Employee Updation</p><h3 class="title">HRMS To Travelex Employee Updation</h3>');
                $('.add_new_button').html('<button type="button" class="btn btn-secondary employee-updation-sync">' +
                'Sync' +'</button>');
            }else if(self.type_id == 3963){
                $('.separate-page-header-content .data-table-title').html('<p class="breadcrumb">Masters / HRMS To Travelex Employee Deletion</p><h3 class="title">HRMS To Travelex Employee Deletion</h3>');
                $('.add_new_button').html('<button type="button" class="btn btn-secondary employee-deletion-sync">' +
                'Sync' +'</button>');
            }else if(self.type_id == 3964){
                $('.separate-page-header-content .data-table-title').html('<p class="breadcrumb">Masters / HRMS To Travelex Employee Reporting To Updation</p><h3 class="title">HRMS To Travelex Employee Reporting To Updation</h3>');
                $('.add_new_button').html('<button type="button" class="btn btn-secondary employee-reporting-to-update-sync">' +
                'Sync' +'</button>');
            }

            $('.employee-addition-sync').on("click", function() {
                $('.employee-addition-sync').button('loading');
                $.ajax({
                    method: "POST",
                    url: laravel_routes['hrmsEmployeeAdditionSync'],
                    data: {
                        company_id: self.user_company.id,
                        company_code: self.user_company.code
                    },
                })
                .done(function(res) {
                    $('.employee-addition-sync').button('reset');
                    if (!res.success) {
                        var errors = '';
                        for (var i in res.errors) {
                            errors += '<li>' + res.errors[i] + '</li>';
                        }
                        custom_noty('error', errors);
                    } else {
                        custom_noty('success', res.message);
                        dataTable.draw();
                    }
                })
                .fail(function(xhr) {
                    $('.employee-addition-sync').button('reset');
                    custom_noty('error', 'Something went wrong at server');
                });
            });

            $('.employee-updation-sync').on("click", function() {
                $('.employee-updation-sync').button('loading');
                $.ajax({
                    method: "POST",
                    url: laravel_routes['hrmsEmployeeUpdationSync'],
                    data: {
                        company_id: self.user_company.id,
                        company_code: self.user_company.code
                    },
                })
                .done(function(res) {
                    $('.employee-updation-sync').button('reset');
                    if (!res.success) {
                        var errors = '';
                        for (var i in res.errors) {
                            errors += '<li>' + res.errors[i] + '</li>';
                        }
                        custom_noty('error', errors);
                    } else {
                        custom_noty('success', res.message);
                        dataTable.draw();
                    }
                })
                .fail(function(xhr) {
                    $('.employee-updation-sync').button('reset');
                    custom_noty('error', 'Something went wrong at server');
                });
            });

            $('.employee-deletion-sync').on("click", function() {
                $('.employee-deletion-sync').button('loading');
                $.ajax({
                    method: "POST",
                    url: laravel_routes['hrmsEmployeeDeletionSync'],
                    data: {
                        company_id: self.user_company.id,
                        company_code: self.user_company.code
                    },
                })
                .done(function(res) {
                    $('.employee-deletion-sync').button('reset');
                    if (!res.success) {
                        var errors = '';
                        for (var i in res.errors) {
                            errors += '<li>' + res.errors[i] + '</li>';
                        }
                        custom_noty('error', errors);
                    } else {
                        custom_noty('success', res.message);
                        dataTable.draw();
                    }
                })
                .fail(function(xhr) {
                    $('.employee-deletion-sync').button('reset');
                    custom_noty('error', 'Something went wrong at server');
                });
            });

            $('.employee-reporting-to-update-sync').on("click", function() {
                $('.employee-reporting-to-update-sync').button('loading');
                $.ajax({
                    method: "POST",
                    url: laravel_routes['hrmsEmployeeReportingToUpdateSync'],
                    data: {
                        company_id: self.user_company.id,
                        company_code: self.user_company.code
                    },
                })
                .done(function(res) {
                    $('.employee-reporting-to-update-sync').button('reset');
                    if (!res.success) {
                        var errors = '';
                        for (var i in res.errors) {
                            errors += '<li>' + res.errors[i] + '</li>';
                        }
                        custom_noty('error', errors);
                    } else {
                        custom_noty('success', res.message);
                        dataTable.draw();
                    }
                })
                .fail(function(xhr) {
                    $('.employee-reporting-to-update-sync').button('reset');
                    custom_noty('error', 'Something went wrong at server');
                });
            });

            $rootScope.loading = false;
        }, 500);
    }
});

app.component('hrmsEmployeeManualAddition', {
    templateUrl: hrms_employee_manual_addition_template_url,
    controller: function($http, $location, $location, HelperService, $routeParams, $rootScope, $scope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.employee = null;
        $http.get(
            laravel_routes['getHrmsEmployeeSyncLogFilterData']
        ).then(function(response) {
            self.user_company = response.data.user_company; 
        });

        $scope.manualAdditionSubmit = function () {
            var split_form_id = '#hrms-employee-manual-addition-form';
            var v = jQuery(split_form_id).validate({
                ignore: '',
                rules: {
                    'employee_code': {
                        required: true,
                    },
                },
                submitHandler: function (form) {
                    let formData = new FormData($(split_form_id)[0]);
                    $('#submit').button('loading');
                    $.ajax({
                        url: laravel_routes['hrmsEmployeeManualAddition'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                        .done(function (response) {
                            $('#submit').button('reset');
                            if (!response.success) {
                                var errors = '';
                                for (var i in response.errors) {
                                    errors += '<li>' + response.errors[i] + '</li>';
                                }
                                custom_noty('error', errors);
                            } else {
                                self.employee_code = null;
                                self.employee = response.employee;
                                custom_noty('success', response.message);
                            }
                            $scope.$apply();
                        })
                        .fail(function (xhr) {
                            $('#submit').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            });
        }
    }
});


//End
//------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------