app.component('eyatraExpenseVoucherAdvanceCashierRepaidList', {
    templateUrl: expense_voucher_advance_cashier_repaid_list_template_url,
    controller: function(HelperService, $rootScope, $scope, $http, $location) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        $http.get(
            expense_voucher_advance_cahsier_repaid_filter_url
        ).then(function(response) {
            console.log(response.data);
            self.employee_list = response.data.employee_list;
            self.outlet_list = response.data.outlet_list;
            self.purpose_list = response.data.purpose_list;
            self.trip_status_list = response.data.trip_status_list;
            $rootScope.loading = false;
        });
        var dataTable = $('#eyatra_expense_voucher_cashier_repaid_list_table').DataTable({
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
                url: laravel_routes['listExpenseVoucherCashierRepaidList'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.employee_id = $('#employee_id').val();
                    d.outlet = $('#outlet_id').val();
                }
            },
            columns: [
                { data: 'checkbox', searchable: false },
                { data: 'action', searchable: false, class: 'action' },
                { data: 'id', name: 'expense_voucher_advance_requests.id', searchable: true },
                { data: 'ecode', name: 'e.code', searchable: true },
                { data: 'ename', name: 'users.name', searchable: false },
                { data: 'oname', name: 'outlets.name', searchable: false },
                { data: 'date', name: 'expense_voucher_advance_requests.date', searchable: false },
                { data: 'advance_amount', name: 'expense_voucher_advance_requests.advance_amount', searchable: false },
                { data: 'balance_amount', searchable: false },
                { data: 'status', name: 'status.name', searchable: true },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();

        setTimeout(function() {
            var x = $('.separate-page-header-inner.search .custom-filter').position();
            var d = document.getElementById('eyatra_expense_voucher_cashier_repaid_list_table_filter');
            x.left = x.left + 15;
            d.style.left = x.left + 'px';
        }, 500);

        $scope.paidExpenseVoucherAdvance = function(id) {
            $('#expense_voucher_id').val(id);
        }

        $scope.getEmployeeData = function(query) {
            $('#employee_id').val(query);
            dataTable.draw();
        }
        $scope.onSelectOutlet = function(query) {
            $('#outlet_id').val(query);
            dataTable.draw();
        }
        $scope.reset_filter = function(query) {
            $('#employee_id').val(-1);
            // $('#status_id').val(-1);
            $('#outlet_id').val(-1);
            dataTable.draw();
        }
        /*$scope.getPurposeData = function(query) {
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
        $scope.getToDateData = function(query) {

            $('#to_date').val(query);
            dataTable.draw();
        }
        $scope.reset_filter = function(query) {
            $('#employee_id').val(-1);
            $('#purpose_id').val(-1);
            $('#status_id').val(-1);
            $('#from_date').val('');
            $('#to_date').val('');
            dataTable.draw();
        }*/
        $('#head_booking').on('click', function() {
            var count = 0;
            selected_employee_claims = [];
            if (event.target.checked == true) {
                $('.employee_claim_list').prop('checked', true);
                $.each($('.employee_claim_list:checked'), function() {
                    count++;
                    selected_employee_claims.push($(this).val());
                });
            } else {
                $('.employee_claim_list').prop('checked', false);
            }
            if (count > 0) {
                $('#approve').css({ 'display': 'inline-block' });

            } else {
                $('#approve').css({ 'display': 'none' });

            }
            $('.approve_ids').val(selected_employee_claims);

        });

        $(document.body).on('click', '.employee_claim_list', function() {
            var count = 0;
            selected_employee_claims = [];
            $.each($('.employee_claim_list:checked'), function() {
                count++;
                selected_employee_claims.push($(this).val());
            });
            if (count > 0) {
                $('#approve').css({ 'display': 'inline-block' });

            } else {
                $('#approve').css({ 'display': 'none' });

            }
            $('.approve_ids').val(selected_employee_claims);
        });
        $('#approve').on('click', function() {
            var approve_ids = $('.approve_ids').val();
            $http.post(
                expense_voucher_advance_cahsier_multiple_approval_repaid_url, { approve_ids: approve_ids },
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
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 1000);
                } else {
                    custom_noty('success', 'Employee Expense Advance Repaid Payment Approved Successfully');
                    $('#approve').css({ 'display': 'none' });
                    var dataTableFilter = $('#eyatra_expense_voucher_cashier_repaid_list_table').dataTable();
                    dataTableFilter.fnFilter();
                    //$location.path('/expense/voucher-advance/cashier/repaid/list');
                    //$scope.$apply();
                    // window.location.href = laravel_routes['listEYatraTripClaimPaymentPendingList'];
                }

            });

        });
        $(document.body).on('click', '.approve_claim', function() {
            var id = $('#expense_voucher_id').val();
            $http.post(
                expense_voucher_advance_cahsier_single_approval_repaid_url, { id: id },
            ).then(function(response) {
                if (!response.data.success) {
                    var errors = '';
                    for (var i in res.errors) {
                        errors += '<li>' + res.errors[i] + '</li>';
                    }

                    custom_noty('error', errors);
                } else {
                    custom_noty('success', 'Employee Expense Advance Repaid Payment Approved Successfully');
                    $('#approve').css({ 'display': 'none' });
                    var dataTableFilter = $('#eyatra_expense_voucher_cashier_repaid_list_table').dataTable();
                    dataTableFilter.fnFilter();
                    // $location.path('/expense/voucher-advance/cashier/repaid/list');
                    //$scope.$apply();
                    // window.location.href = laravel_routes['listEYatraTripClaimPaymentPendingList'];
                }
            });
        });
        $rootScope.loading = false;

    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('eyatraExpenseVoucherAdvanceCashierRepaidView', {
    templateUrl: expense_voucher_advance_cashier_repaid_view_template_url,
    controller: function($http, $location, $routeParams, HelperService, $rootScope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        $http.get(
            expense_voucher_advance_cashier_repaid_view_data_url + '/' + $routeParams.id
        ).then(function(response) {
            console.log(response);
            self.expense_voucher_view = response.data.expense_voucher_view;
            self.expense_voucher_advance_attachment_url = eyatra_expense_voucher_advance_attachment_url;

        });

        $rootScope.loading = false;
    }
});