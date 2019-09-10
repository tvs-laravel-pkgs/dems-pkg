app.component('eyatraExpenseVoucherAdvanceList', {
    templateUrl: expense_voucher_advance_list_template_url,
    controller: function(HelperService, $rootScope, $scope, $http, $routeParams, $location) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;

        $list_data_url = expense_voucher_advance_list_data_url;

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
        $('.dataTables_length select').select2();
        //$('.separate-page-header-content .data-table-title').html('<p class="breadcrumb">Expense Voucher / Expense Voucher list</p><h3 class="title">Expense Voucher</h3>');
        // if ($location.url() == '/eyatra/petty-cash')
 //     $('.add_new_button').html(
 //         '<a href="#!/eyatra/petty-cash/add/' + $routeParams.type_id + '" type="button" class="btn btn-blue" ng-show="$ctrl.hasPermission(\'eyatra-indv-expense-vouchers\')">' +
 //         'Add New' +
 //         '</a>'
 //     );


        $scope.deletePettycash = function(id) {
            $('#deletepettycash_id').val(id);
        }
        $scope.confirmDeletePettycash = function() {
            var id = $('#deletepettycash_id').val();
            $http.get(
                petty_cash_delete_url + '/' + $routeParams.type_id + '/' + id,
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
                        text: 'Petty Cash Deleted Successfully',
                        animation: {
                            speed: 500 // unavailable - no need
                        },
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 5000);
                    if ($routeParams.type_id == 1) {
                        $('#petty_cash_list').DataTable().ajax.reload(function(json) {});
                        $location.path('eyatra/petty-cash/1');
                        $scope.$apply();
                    } else {
                        $('#petty_cash_list').DataTable().ajax.reload(function(json) {});
                        $location.path('eyatra/petty-cash/2');
                        $scope.$apply();
                    }
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

            self.expense_voucher_advance = response.data.expense_voucher_advance;


            if (self.action == 'Edit') {
                self.expense_voucher_advance = response.data.expense_voucher_advance;

            } else {


            }

            var d = new Date();
            var val = d.getDate() + "-" + (d.getMonth() + 1) + "-" + d.getFullYear();
            $("#date").val(val);
            console.log(val);

            //SEARCH  EMPLOYEE
            self.searchEmployee = function(query) {
                //alert();
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
            },
            messages: {
                'employee_id': {
                    required: 'Employee code is required',
                },
                'date': {
                    required: 'Date is required',
                },
                'advance_amount': {
                    required: 'Amount is required',
                },
                'description': {
                    required: 'Description is required',
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
app.component('eyatraPettyCashView', {
    templateUrl: pettycash_view_template_url,
    controller: function($http, $location, $routeParams, HelperService, $rootScope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        $http.get(
            petty_cash_view_url + '/' + $routeParams.type_id + '/' + $routeParams.pettycash_id
        ).then(function(response) {
            // console.log(response);
            self.petty_cash = response.data.petty_cash;
            self.type_id = $routeParams.type_id;
            self.petty_cash_other = response.data.petty_cash_other;
            self.employee = response.data.employee;
            var local_total = 0;
            $.each(self.petty_cash, function(key, value) {
                local_total += parseFloat(value.amount);
            });
            var total_amount = 0;
            var total_tax = 0;
            $.each(self.petty_cash_other, function(key, value) {
                total_amount += parseFloat(value.amount);
                total_tax += parseFloat(value.tax);
            });
            var other_total = total_amount + total_tax;
            var total_amount = local_total + other_total;
            // console.log(total_amount);
            setTimeout(function() {
                $(".localconveyance").html('₹ ' + local_total.toFixed(2));
                $(".other_expences").html('₹ ' + other_total.toFixed(2));
                $(".Total_amount").html('₹ ' + total_amount.toFixed(2));
            }, 500);

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
//------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------