app.component('eyatraExpenseVoucherAdvanceList', {
    templateUrl: expense_voucher_advance_list_template_url,
    controller: function(HelperService, $rootScope, $scope, $http, $routeParams, $location) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        if(!self.hasPermission('eyatra-advance-pcv')){
            window.location = "#!/permission-denied";
            return false;
        }
        $list_data_url = expense_voucher_advance_list_data_url;
        // self.add_permission = self.hasPermission('eyatra-indv-expense-vouchers');
        self.add_permission = self.hasPermission('eyatra-advance-pcv-add');
        //alert(self.add_permission);
        var dataTable = '';
        $http.get(
            $list_data_url
        ).then(function(response) {
            var dataTable = $('#expense_advance_list').DataTable({
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
                    url: $list_data_url,
                    type: "GET",
                    dataType: "json",
                    data: function(d) {
                        d.status_id = $('#status').val();
                        d.created_date = $('.created_date').val();
                    }
                },
                columns: [
                    { data: 'action', searchable: false, class: 'action' },
                    { data: 'advance_pcv_number', name: 'expense_voucher_advance_requests.number', searchable: true },
                    { data: 'status', searchable: false },
                    { data: 'advance_pcv_claim_number', name: 'expense_voucher_advance_request_claims.number', searchable: true },
                    { data: 'advance_pcv_claim_status', searchable: false },
                    { data: 'ename', name: 'users.name', searchable: true },
                    { data: 'ecode', name: 'employees.code', searchable: true },
                    { data: 'date', name: 'date', searchable: false },
                    { data: 'advance_amount', name: 'expense_voucher_advance_requests.advance_amount', searchable: false },
                    { data: 'balance_amount', name: 'expense_voucher_advance_requests.balance_amount', searchable: false },
                    // { data: 'status', name: 'configs.name', searchable: false },
                ],
                rowCallback: function(row, data) {
                    $(row).addClass('highlight-row');
                }
            });
            //
            $('.dataTables_length select').select2();

            setTimeout(function() {
                var x = $('.separate-page-header-inner.search .custom-filter').position();
                var d = document.getElementById('expense_advance_list_filter');
                x.left = x.left + 15;
                d.style.left = x.left + 'px';
            }, 500);

            $(document).on('change', '.created_date', function() {
                dataTable.draw();
            });
            $scope.onselectStatus = function(id) {
                //alert();
                $('#status').val(id);
                dataTable.draw();
            }

            $scope.reset_filter = function() {
                $('#status').val('');
                $('.created_date').val('');
                dataTable.draw();
            }
        });
        setTimeout(function() {
            $('div[data-provide = "datepicker"]').datepicker({
                todayHighlight: true,
                autoclose: true,
            });
        }, 1000);
        $http.get(
            expense_voucher_advance_filter_url
        ).then(function(response) {
            self.status_list = response.data.status_list;
            self.employee_return_payment_bank_list = response.data.employee_return_payment_bank_list;
        });
        // var dataTable = $('#expense_advance_list').DataTable();

        $scope.deleteExpenseVoucher = function(id) {
            $('#delete_expense_voucher_id').val(id);
        }
        $scope.confirmDeleteExpenseVoucher = function() {
            var id = $('#delete_expense_voucher_id').val();
            $http.get(
                expense_voucher_advance_delete_url + '/' + id,
            ).then(function(res) {
                if (!res.data.success) {
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
                        text: 'Expense voucher request Deleted Successfully',
                        animation: {
                            speed: 500 // unavailable - no need
                        },
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 5000);
                    $('#expense_advance_list').DataTable().ajax.reload(function(json) {});
                    $location.path('/expense/voucher-advance/list')
                    $scope.$apply()
                }
            });
        }

        $(document.body).on('click', '.paid_amount', function() {
            var id = $(this).data('expense_id');
            $http.post(
                expense_voucher_advance_pending_single_approve_url, { id: id },
            ).then(function(responsse) {
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
                        text: 'Employee Claim Payment Pending Approved Successfully',
                        animation: {
                            speed: 500 // unavailable - no need
                        },
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 5000);

                    $('#approve').css({ 'display': 'none' });

                    var dataTableFilter = $('#payment_pending_list_table').dataTable();
                    dataTableFilter.fnFilter();
                    $location.path('/trip/claim/payment-pending/list');
                    $scope.$apply();
                    // window.location.href = laravel_routes['listEYatraTripClaimPaymentPendingList'];
                }
            });
        });

        $scope.employeeReturnPaymentUpdateHandler = function(expense_voucher_advance_request_id) {
            $.ajax({
                url: expense_voucher_advance_get_data_url + '/' + expense_voucher_advance_request_id,
                method: "GET",
            })
            .done(function(res) {
                if (!res.success) {
                    var errors = '';
                    for (var i in res.errors) {
                        errors += '<li>' + res.errors[i] + '</li>';
                    }
                    custom_noty('error', errors);
                } else {
                    self.expense_voucher_advance_request_claim = res.data.expense_voucher_advance_request_claim;
                    $("#employee-return-payment-detail-modal").modal('show');
                    $(".employee_return_date_picker").datepicker("destroy");
                    $(".employee_return_date_picker").datepicker({
                        startDate: res.data.expense_voucher_advance.date,
                        autoclose: true,
                    });
                }
                $scope.$apply();
            })
            .fail(function(xhr) {
                console.log(xhr);
            });
        }

        $(document).on('click', '#payment-detail-save-btn', function() {
            var form_id = '#employee-return-payment-detail-form';
            var v = jQuery(form_id).validate({
                ignore: '',
                errorPlacement: function(error, element) {
                    if (element.hasClass("employee_return_payment_date")) {
                        error.appendTo($('.employee_return_payment_date_error'));
                    }else{
                        error.insertAfter(element.parent())
                    }
                },
                submitHandler: function(form) {
                    let formData = new FormData($(form_id)[0]);
                    $('#payment-detail-save-btn').button('loading');
                    $.ajax({
                            url: laravel_routes['expenseVoucherAdvanceEmployeeReturnPaymentSave'],
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                        })
                        .done(function(res) {
                            $('#payment-detail-save-btn').button('reset');
                            if (!res.success) {
                                var errors = '';
                                for (var i in res.errors) {
                                    errors += '<li>' + res.errors[i] + '</li>';
                                }
                                custom_noty('error', errors);
                            } else {
                                custom_noty('success', res.message);
                                $('#employee-return-payment-detail-modal').modal('hide');
                                setTimeout(function() {
                                    $('#expense_advance_list').DataTable().draw();
                                }, 700);
                            }
                        })
                        .fail(function(xhr) {
                            $('#payment-detail-save-btn').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                },
            });
        });

        // $rootScope.loading = false;
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('eyatraExpenseVoucherAdvanceForm', {
    templateUrl: expense_voucher_advance_from_template_url,
    controller: function($http, $location, $location, HelperService, $routeParams, $rootScope, $scope) {
        $form_data_url = typeof($routeParams.id) == 'undefined' ? expense_voucher_advance_form_data_url : expense_voucher_advance_form_data_url + '/' + $routeParams.id;
        var self = this;
        self.type_id = $routeParams.type_id;
        self.hasPermission = HelperService.hasPermission;
        self.page_type = typeof($location.search().page_type) === 'undefined' ? '' : $location.search().page_type;
        if(!self.hasPermission('eyatra-advance-pcv-add') && !self.hasPermission('eyatra-advance-pcv-edit')){
            window.location = "#!/permission-denied";
            return false;
        }

        self.angular_routes = angular_routes;
        $http.get(
            $form_data_url
        ).then(function(response) {
            fileUpload();
            $('.attachments').imageuploadify();
            if (!response.data.success) {
                // $noty = new Noty({
                //     type: 'error',
                //     layout: 'topRight',
                //     text: response.data.error,
                //     animation: {
                //         speed: 500 // unavailable - no need
                //     },
                // }).show();
                // setTimeout(function() {
                //     $noty.close();
                // }, 5000);
                // $location.path('/eyatra/expense/voucher-advance/')
                // return;
            }
            // console.log(response);

            self.action = response.data.action;

            self.expense_voucher_advance = response.data.expense_voucher_advance;
            self.expense_voucher_advance_claim = response.data.expense_voucher_advance_claim;
            self.expense_voucher_advance_attachment_url = eyatra_expense_voucher_advance_attachment_url;
            self.employee_return_payment_mode_list = response.data.employee_return_payment_mode_list;
            self.employee_return_balance_cash_limit = response.data.employee_return_balance_cash_limit;
            self.company = response.data.company_data;
            self.expense_voucher_attach_removal_ids = [];
            // console.log(self.expense_voucher_advance_attachment_url);

            if (self.action == 'Edit') {
                // self.action = 'Edit';
                if(self.page_type == 2){
                    self.action = 'Claim';
                }else{
                    self.action = 'Edit';
                }
                // if (self.expense_voucher_advance.status_id == 3464 || self.expense_voucher_advance.status_id == 3466 || self.expense_voucher_advance.status_id == 3469 || self.expense_voucher_advance.status_id == 3471) {
                if (self.expense_voucher_advance.status_id == 3464 && (!self.expense_voucher_advance_claim || self.expense_voucher_advance_claim.status_id == 3466 || self.expense_voucher_advance_claim.status_id == 3469 || self.expense_voucher_advance_claim.status_id == 3471)) {
                    $("#date").prop('readonly', true);
                    $(".date").removeAttr('data-provide');
                    $("#advance_amount").prop('readonly', true);
                    $("#description").prop('readonly', true);
                    self.expense_voucher_advance.employee = response.data.expense_voucher_advance.employee ? (response.data.expense_voucher_advance.employee.user ? response.data.expense_voucher_advance.employee.user.name : null) : null;
                    $("#employee_id").val(response.data.expense_voucher_advance.employee_id);
                } else {
                    self.expense_voucher_advance.employee = response.data.expense_voucher_advance.employee ? (response.data.expense_voucher_advance.employee.user ? response.data.expense_voucher_advance.employee.user.name : null) : null;
                    $("#employee_id").val(response.data.expense_voucher_advance.employee_id);
                }
            } else {
                self.action = 'Add';
                self.employee_id = response.data.employee_details.id;
                // console.log(self.employee_id);
                $("#employee_id").val('');
            }

            setTimeout(function() {
                $('div[data-provide="datepicker"]').datepicker({
                    todayHighlight: true,
                    autoclose: true,
                });
            }, 1000);

            var d = new Date();
            var val = d.getDate() + "-" + (d.getMonth() + 1) + "-" + d.getFullYear();
            $("#date").val(val);
            // console.log(val);

            //REMOVE EXPENCE VOUCHER ADVANCE ATTACHMENT
            self.removeexpenseVoucherAttachment = function(expense_voucher_attachment_id, expense_voucher_attachment_index, expense_voucher_id) {
                console.log(expense_voucher_attachment_id, expense_voucher_attachment_index, expense_voucher_id);
                if (expense_voucher_attachment_id && expense_voucher_id) {
                    self.expense_voucher_attach_removal_ids.push(expense_voucher_attachment_id);
                    $('#expense_voucher_attach_removal_ids').val(JSON.stringify(self.expense_voucher_attach_removal_ids));
                }
                self.expense_voucher_advance.attachments.splice(expense_voucher_attachment_index, 1);
            }

            //SEARCH  EMPLOYEE
            self.searchEmployee = function(query) {
                // alert();
                if (query) {
                    return new Promise(function(resolve, reject) {
                        $http
                            .post(
                                get_manager_name, {
                                    key: query,
                                }
                            )
                            .then(function(response) {

                                resolve(response.data);
                            });

                    });
                } else {
                    return [];
                }
            }

            $rootScope.loading = false;
            /* Datepicker With Current Date */

        });

        $scope.advancePcvSubmitHandler = function(){
            $scope.advancePcvFormValidator();
            if ($("#expense_voucher_advance").valid()) {
                let advance_amount = parseFloat(self.expense_voucher_advance.advance_amount || 0);
                let expense_amount = parseFloat(self.expense_voucher_advance.expense_amount || 0);
                if(expense_amount > 0){
                    let balance_amount = (advance_amount - expense_amount);
                    if(balance_amount > 0)
                    {
                        //BALANCE RETURN BY EMPLOYEE TO COMPANY
                        self.form_submit_type = 2;
                        if(!self.advane_balance_return_payment_mode_id){
                            // self.advane_balance_return_payment_mode_id = self.expense_voucher_advance.employee_return_payment_mode_id;
                            if(!self.expense_voucher_advance.employee_return_payment_mode_id){
                                console.log("self.employee_return_balance_cash_limit")
                                console.log(self.employee_return_balance_cash_limit)
                                if(balance_amount < self.employee_return_balance_cash_limit){
                                    self.advane_balance_return_payment_mode_id = 4010; //CASH
                                }else{
                                    self.advane_balance_return_payment_mode_id = 4011; //Bank Transfer
                                }
                            }else{
                                self.advane_balance_return_payment_mode_id = self.expense_voucher_advance.employee_return_payment_mode_id;
                            }       
                        }
                        
                        $("#employee-return-payment-modal").modal('show');
                    }else{
                        self.form_submit_type = 1;
                        self.advane_balance_return_payment_mode_id = null;
                        $("#employee-return-payment-modal").modal('hide');
                        setTimeout(function() {
                            $('#expense_voucher_advance').submit();
                        }, 400);
                    }
                }else{
                    self.form_submit_type = 1;
                    self.advane_balance_return_payment_mode_id = null;
                    setTimeout(function() {
                        $('#expense_voucher_advance').submit();
                    }, 400);
                }
            }
        }

        $scope.employeeReturnPaymentSubmitHandler = function(){
            self.form_submit_type = 2;
            if(!self.advane_balance_return_payment_mode_id){
                custom_noty('error', 'Kindly select the payment mode');
                return;
            }
            setTimeout(function() {
                $('#expense_voucher_advance').submit();
            }, 400);
        }

        $scope.advancePcvFormValidator = function(){
            var form_id = '#expense_voucher_advance';
            var v = jQuery(form_id).validate({
                errorPlacement: function(error, element) {
                    error.insertAfter(element)
                },
                ignore: '',
                rules: {
                    'employee_id': {
                        required: true,
                    },
                    'date': {
                        required: true,
                    },
                    'advance_amount': {
                        required: true,
                    },
                    'description': {
                        required: true,
                        minlength: 5,
                        // maxlength: 191,
                        maxlength: 250,
                    },
                    'expense_description': {
                        required: true,
                        minlength: 5,
                        // maxlength: 191,
                        maxlength: 250,
                    },
                    'expense_amount': {
                        required: true,
                    },
                }
            });
        }

        var form_id = '#expense_voucher_advance';
        var v = jQuery(form_id).validate({
            errorPlacement: function(error, element) {
                error.insertAfter(element)
            },
            ignore: '',
            rules: {
                'employee_id': {
                    required: true,
                },
                'date': {
                    required: true,
                },
                'advance_amount': {
                    required: true,
                },
                'description': {
                    required: true,
                    minlength: 5,
                    // maxlength: 191,
                    maxlength: 250,
                },
                'expense_description': {
                    required: true,
                    minlength: 5,
                    // maxlength: 191,
                    maxlength: 250,
                },
                'expense_amount': {
                    required: true,
                },
            },
            // messages: {
            //     'employee_id': {
            //         required: 'Employee code is required',
            //     },
            //     'date': {
            //         required: 'Date is required',
            //     },
            //     'advance_amount': {
            //         required: 'Advance Amount is required',
            //     },
            //     'description': {
            //         required: 'Advance Amount Details is required',
            //     },
            //     'expense_description': {
            //         required: 'Expense Amount Details is required',
            //     },
            //     'expense_amount': {
            //         required: 'Expense Amount is required',
            //     },
            // },

            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                if(self.form_submit_type == 2){
                    $('#employee_return_payment_btn').button('loading');
                }
                $('#submit').button('loading');
                $.ajax({
                        url: laravel_routes['expenseVoucherSave'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        async: false,
                    })
                    .done(function(res) {
                        console.log(res.message);
                        if (!res.success) {
                            if(self.form_submit_type == 2){
                                $('#employee_return_payment_btn').button('reset');
                            }
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
                            if(self.form_submit_type == 2){
                                $("#employee-return-payment-detail-modal").modal('hide');
                                $('body').removeClass('modal-open');
                                $('.modal-backdrop').remove();
                            }
                            $location.path('/expense/voucher-advance/list')
                            $scope.$apply()
                        }
                    })
                    .fail(function(xhr) {
                        if(self.form_submit_type == 2){
                            $('#employee_return_payment_btn').button('reset');
                        }
                        $('#submit').button('reset');
                        custom_noty('error', 'Something went wrong at server');
                    });
            },
        });
    }
});
//------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('eyatraExpenseVoucherAdvanceView', {
    templateUrl: expense_voucher_advance_view_template_url,
    controller: function($http, $location, $routeParams, HelperService, $rootScope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        if(!self.hasPermission('eyatra-advance-pcv-view')){
            window.location = "#!/permission-denied";
            return false;
        }
        $http.get(
            expense_voucher_advance_view_data_url + '/' + $routeParams.id
        ).then(function(response) {
            console.log(response);
            self.expense_voucher_view = response.data.expense_voucher_view;
            self.expense_voucher_advance_attachment_url = eyatra_expense_voucher_advance_attachment_url;

        });

        $rootScope.loading = false;
    }
});
//------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------