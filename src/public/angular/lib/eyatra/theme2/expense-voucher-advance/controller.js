app.component('eyatraExpenseVoucherAdvanceList', {
    templateUrl: expense_voucher_advance_list_template_url,
    controller: function(HelperService, $rootScope, $scope, $http, $routeParams, $location) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;

        $list_data_url = expense_voucher_advance_list_data_url;

        $http.get(
            $list_data_url
        ).then(function(response) {
            var dataTable = $('#expense_advance_list').DataTable({
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
                    url: $list_data_url,
                    type: "GET",
                    dataType: "json",
                    data: function(d) {
                        //d.type_id = $routeParams.type_id;
                    }
                },
                columns: [
                    { data: 'action', searchable: false, class: 'action' },
                    { data: 'ename', name: 'users.name', searchable: true },
                    { data: 'ecode', name: 'employees.code', searchable: true },
                    { data: 'date', name: 'date', searchable: false },
                    { data: 'advance_amount', name: 'expense_voucher_advance_requests.advance_amount', searchable: false },
                    { data: 'balance_amount', name: 'expense_voucher_advance_requests.balance_amount', searchable: false },
                    { data: 'status', name: 'configs.name', searchable: false },
                ],
                rowCallback: function(row, data) {
                    $(row).addClass('highlight-row');
                }
            });
            //
            $('.dataTables_length select').select2();
            $('.separate-page-header-content .data-table-title').html('<p class="breadcrumb">Expense Voucher / Expense Voucher Advances list</p><h3 class="title">Expense Voucher Advances</h3>');
            // if ($location.url() == '/eyatra/petty-cash')
            $('.add_new_button').html(
                '<a href="#!/eyatra/expense/voucher-advance/add" type="button" class="btn btn-blue" ng-show="$ctrl.hasPermission(\'eyatra-indv-expense-vouchers\')">' +
                'Add New' +
                '</a>'
            );
            // setTimeout(function() {
            //     var x = $('.separate-page-header-inner.search .custom-filter').position();
            //     var d = document.getElementById('expense_advance_list_filter');
            //     x.left = x.left + 15;
            //     d.style.left = x.left + 'px';
            // }, 500);
        });
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
                    dataTable.ajax.reload(function(json) {});
                    $location.path('/eyatra/expense/voucher-advance/list')
                    $scope.$apply()
                }
            });
        }
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
        self.angular_routes = angular_routes;
        $http.get(
            $form_data_url
        ).then(function(response) {
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
                // $location.path('/eyatra/petty-cash/' + $routeParams.type_id)
                // return;
            }
            console.log(response);

            self.action = response.data.action;

            self.expense_voucher_advance = response.data.expense_voucher_advance;
            console.log(response.data.expense_voucher_advance.employee);

            if (self.action == 'Edit') {
                self.action = 'Edit';
                self.expense_voucher_advance.employee = response.data.expense_voucher_advance.employee.user.name;
                $("#employee_id").val(response.data.expense_voucher_advance.employee_id);
            } else {
                self.action = 'Add';
                self.employee_id = response.data.employee_details.id;
                console.log(self.employee_id);
                $("#employee_id").val('');
            }

            var d = new Date();
            var val = d.getDate() + "-" + (d.getMonth() + 1) + "-" + d.getFullYear();
            $("#date").val(val);
            // console.log(val);

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
                },
                'expense_amount': {
                    required: true,
                },
            },
            messages: {
                'employee_id': {
                    required: 'Employee code is required',
                },
                'date': {
                    required: 'Date is required',
                },
                'advance_amount': {
                    required: 'Advance Amount is required',
                },
                'description': {
                    required: 'Description is required',
                },
                'expense_amount': {
                    required: 'Expense Amount is required',
                },
            },

            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('#submit').button('loading');
                $.ajax({
                        url: laravel_routes['expenseVoucherSave'],
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
                                text: 'Expense voucher advance saved successfully!',
                                animation: {
                                    speed: 500 // unavailable - no need
                                },
                            }).show();
                            setTimeout(function() {
                                $noty.close();
                            }, 5000);
                            $location.path('/eyatra/expense/voucher-advance/list')
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
//------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('eyatraExpenseVoucherAdvanceView', {
    templateUrl: expense_voucher_advance_view_template_url,
    controller: function($http, $location, $routeParams, HelperService, $rootScope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        $http.get(
            expense_voucher_advance_view_data_url + '/' + $routeParams.id
        ).then(function(response) {
            // console.log(response);
            self.expense_voucher_view = response.data.expense_voucher_view;
        });

        $rootScope.loading = false;
    }
});
//------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------