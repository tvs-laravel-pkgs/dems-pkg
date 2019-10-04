app.component('eyatraEmployees', {
    templateUrl: eyatra_employee_list_template_url,
    controller: function(HelperService, $rootScope, $http, $scope) {
        // console.log('s');
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
            // console.log(response);
            self.grade_list = response.data.grade_list;
            self.outlet_list = response.data.outlet_list;
            self.manager_list = response.data.manager_list;
            self.role_list = response.data.role_list;
            $rootScope.loading = false;
        });
        var dataTableFilter = $('#eyatra_employee_table').dataTable();
        $scope.onselectOutlet = function(id) {
            $('#outlet_id').val(id);
            dataTableFilter.fnFilter();
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
            dataTableFilter.fnFilter();
        }
        $scope.resetForm();
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
                $location.path('/eyatra/employees')
                $scope.$apply()
                return;
            }
            console.log(response);
            self.employee = response.data.employee;
            self.extras = response.data.extras;


            self.action = response.data.action;
            if (response.data.employee.payment_mode_id == null || !response.data.employee.payment_mode_id) {
                self.employee.payment_mode_id = 3244;
            }

            if (self.action == 'Add') {
                $('#visit-single').prop('checked', true);
            }

            if (self.action == 'Edit') {
                self.switch_password = 'No';
                $("#hide_password").hide();
                $("#password").prop('disabled', true);
                $scope.getDesignation(self.employee.grade_id);
                //$scope.selectPaymentMode(self.employee.payment_mode_id);
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
                }
                else if (element.hasClass("employee_password_check")) {
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

                    required: function(element) {
                        if ($("#bank").is(':checked')) {
                            return true;
                        } else {
                            return false;
                        }
                    },
                    maxlength: 100,
                    minlength: 3,
                },
                'branch_name': {
                    required: function(element) {
                        if ($("#bank").is(':checked')) {
                            return true;
                        } else {
                            return false;
                        }
                    },
                    maxlength: 50,
                    minlength: 3,
                },
                'account_number': {
                    required: function(element) {
                        if ($("#bank").is(':checked')) {
                            return true;
                        } else {
                            return false;
                        }
                    },
                    maxlength: 20,
                    minlength: 3,
                    min: 1,
                    number: true,
                    // positiveNumber: true,
                },
                'ifsc_code': {
                    required: function(element) {
                        if ($("#bank").is(':checked')) {
                            return true;
                        } else {
                            return false;
                        }
                    },
                    maxlength: 10,
                    minlength: 3,
                },
                'cheque_favour': {
                    required: function(element) {
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
                        if ($("#wallet").is(':checked')) {
                            return true;
                        } else {
                            return false;
                        }
                    },
                },
                'value': {
                    required: function(element) {
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
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();
        setTimeout(function() {
            var x = $('.separate-page-header-inner.search .custom-filter').position();
            var d = document.getElementById('eyatra_employee_import_table_filter');
            x.left = x.left + 15;
            d.style.left = x.left + 'px';
        }, 500);

        var dataTableFilter = $('#eyatra_employee_import_table').dataTable();

        $('.from,.to').on('change', function() {

            dataTableFilter.fnFilter();
        });
        $scope.resetForm = function() {
            $('.from').val(null);
            $('.to').val(null);
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
            } 
            else if (type == 3382) {
                $('#employee_import').hide();
                $('#outlet_import').hide();
                $('#city_import').show();
                $('#grade_import').hide();
            }
             else if (type == 3383) {
                $('#employee_import').hide();
                $('#outlet_import').hide();
                $('#city_import').hide();
                $('#grade_import').show();
            }  

            else {
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
        $(".form-group").on('change','.input-file',function(){
            var html = [];
            del_arr = [];
            $(".insert-file").empty();
            $(this.files).each(function(i,v){
                html.push("<div class='file-return-parent'>"+img_file+"<p class='file-return'>"+v.name+"</p><button type='button' onclick='angular.element(this).scope().deletefiles(this)' class='remove-hn btn' >"+img_close+"</button></div>");
                del_arr.push(v.name);
            });
            $(".insert-file").append(html);
        });
        /* Remove Function */
        $scope.deletefiles=function (this_parents) {
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
                    $.ajax({
                            url: save_import_jobs_url,
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                        })
                        .done(function(res) {

                            if (!res.success) {
                                $('#submit').button('reset');
                                $('#submit').prop('disabled', true);
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
                                $location.path('/eyatra/import/job/list')
                                $scope.$apply()
                            }
                        })
                        .fail(function(xhr) {
                            $('#submit').button('reset');
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


//End
//------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------