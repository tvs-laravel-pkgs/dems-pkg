app.component('eyatraPettyCashFinanceList', {
    templateUrl: eyatra_pettycash_finance_list_template_url,
    controller: function(HelperService, $rootScope, $scope, $http) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        var dataTable = $('#petty_cash_finance_list').DataTable({
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
                url: laravel_routes['listPettyCashVerificationFinance'],
                type: "GET",
                dataType: "json",
                data: function(d) {}
            },

            columns: [
                { data: 'action', searchable: false, class: 'action' },
                { data: 'ename', name: 'employees.name', searchable: true },
                { data: 'ecode', name: 'employees.code', searchable: true },
                { data: 'oname', name: 'outlets.name', searchable: true },
                { data: 'ocode', name: 'outlets.code', searchable: true },
                { data: 'date', name: 'date', searchable: false },
                { data: 'total', name: 'total', searchable: true },
                { data: 'status', name: 'configs.name', searchable: false },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();
        $('.separate-page-header-content .data-table-title').html('<p class="breadcrumb">Claim / Claim list</p><h3 class="title">Expense Voucher Claim</h3>');
        $('.add_new_button').html(
            '<a href="#!/eyatra/petty-cash/verification2/add" type="button" class="btn btn-grey" ng-show="$ctrl.hasPermission(\'eyatra-indv-expense-vouchers-verification2\')">' +
            'Add New' +
            '</a>'
        );
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('eyatraPettyCashFinanceForm', {
    templateUrl: pettycash_finance_form_template_url,
    controller: function($http, $location, $location, HelperService, $routeParams, $rootScope, $scope) {
        $form_data_url = typeof($routeParams.pettycash_id) == 'undefined' ? pettycash_finance_form_data_url : pettycash_finance_form_data_url + '/' + $routeParams.pettycash_id;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        $http.get(
            $form_data_url
        ).then(function(response) {
            if (!response.data.success) {
                $noty = $noty = new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: response.data.error,
                }).show();
                setTimeout(function() {
                    $noty.close();
                }, 1000);

                $location.path('/eyatra/petty-cash')
                return;
            }
            // console.log(response);
            self.extras = response.data.extras;
            self.localconveyance = response.data.localconveyance;
            self.action = response.data.action;
            self.petty_cash = response.data.petty_cash;
            self.employee_list = response.data.employee_list;
            self.employee = response.data.employee;
            self.petty_cash_other = response.data.petty_cash_other;
            self.user_role = response.data.user_role;
            self.emp_details = response.data.emp_details;
            self.petty_cash_removal_id = [];
            self.petty_cash_other_removal_id = [];

            if (self.petty_cash.length == 0) {
                self.addlocalconveyance();
                self.addotherexpence();
            }
            if (self.action == 'Edit') {
                self.selectedItem = response.data.petty_cash_other[0].ename;
                $('.employee').val(response.data.petty_cash_other[0].employee_id);
            } else {
                self.selectedItem = response.data.employee_list;
                $('.employee').val('');
            }
            setTimeout(function() {
                self.localConveyanceCal();
                self.otherConveyanceCal();
            }, 500);
            $rootScope.loading = false;
            /* Datepicker With Current Date */

        });
        $scope.selectempname = function(id) {
            if (id) {
                $http.get(
                    get_employee_details_finance + '/' + id
                ).then(function(response) {
                    self.employee_list = response.data.emp_details;
                });
            } else {
                self.employee_list = '';
            }
        }
        $('.btn-nxt').on("click", function() {
            $('.editDetails-tabs li.active').next().children('a').trigger("click");
        });
        $('.btn-prev').on("click", function() {
            $('.editDetails-tabs li.active').prev().children('a').trigger("click");
        });
        setTimeout(function() {
            $('div[data-provide="datepicker"]').datepicker({
                todayHighlight: true,
            });
        }, 1500);

        self.localConveyanceCal = function() {
            var total_petty_cash_local_amount = 0;
            $('.localConveyance_amount').each(function() {
                var local_amount = parseInt($(this).closest('tr').find('#localConveyance_amount').val() || 0);
                if (!$.isNumeric(local_amount)) {
                    local_amount = 0;
                }
                total_petty_cash_local_amount += local_amount;
            });
            $('.localConveyance').text('₹ ' + total_petty_cash_local_amount.toFixed(2));
            $('.total_petty_cash_local_amount').val(total_petty_cash_local_amount.toFixed(2));
            caimTotalAmount();
        }
        self.otherConveyanceCal = function() {
            var total_petty_cash_other_amount = 0;
            $('.otherConveyance_amount').each(function() {
                var other_amount = parseInt($(this).closest('tr').find('#otherConveyance_amount').val() || 0);
                var other_tax = parseInt($(this).closest('tr').find('#otherConveyance_tax').val() || 0);
                if (!$.isNumeric(other_amount)) {
                    other_amount = 0;
                }
                if (!$.isNumeric(other_tax)) {
                    other_tax = 0;
                }
                current_total = other_amount + other_tax;
                total_petty_cash_other_amount += current_total;
            });
            $('.other_expenses').text('₹ ' + total_petty_cash_other_amount.toFixed(2));
            $('.total_petty_cash_other_amount').val(total_petty_cash_other_amount.toFixed(2));
            caimTotalAmount();
        }

        function caimTotalAmount() {
            var total_petty_cash_local_amount = parseFloat($('.total_petty_cash_local_amount').val() || 0);
            var total_petty_cash_other_amount = parseFloat($('.total_petty_cash_other_amount').val() || 0);
            var total_claim_amount = total_petty_cash_local_amount + total_petty_cash_other_amount;
            $('.claim_total_amount').val(total_claim_amount.toFixed(2));
            $('.claim_total_amount').text('₹ ' + total_claim_amount.toFixed(2));
        }

        $scope.employeechecker = function(searchText, chkval) {
            if (chkval == 1) {
                return $http
                    .get(get_employee_name + '/' + searchText)
                    .then(function(res) {
                        employee_list = res.data.employee_list;
                        return employee_list;
                    });
            } else {
                $http
                    .get(get_employee_name + '/' + searchText)
                    .then(function(res) {
                        // console.log(res.data.employee_list[0]);
                        self.selectedItem = res.data.employee_list[0];
                    });
            }
        }

        self.addlocalconveyance = function() {
            self.petty_cash.push({
                from_place: '',
                to_place: '',
                from_km: '',
                to_km: '',
            });
        }
        self.addotherexpence = function() {
            self.petty_cash_other.push({
                expence_type: '',
                preferred_travel_modes: '',
            });
        }

        self.removepettyCash = function(index, petty_cash_id) {

            if (petty_cash_id) {
                self.petty_cash_removal_id.push(petty_cash_id);
                $('#petty_cash_removal_id').val(JSON.stringify(self.petty_cash_removal_id));
            }
            self.petty_cash.splice(index, 1);
            setTimeout(function() {
                self.localConveyanceCal();
            }, 500);
        }

        self.removeotherexpence = function(index, petty_cash_other_id) {

            if (petty_cash_other_id) {
                self.petty_cash_other_removal_id.push(petty_cash_other_id);
                $('#petty_cash_other_removal_id').val(JSON.stringify(self.petty_cash_other_removal_id));
            }
            self.petty_cash_other.splice(index, 1);
            setTimeout(function() {
                self.otherConveyanceCal();
            }, 500);
        }

        var form_id = '#petty-cash';
        var v = jQuery(form_id).validate({
            errorPlacement: function(error, element) {
                error.insertAfter(element)
            },
            ignore: '',
            invalidHandler: function(event, validator) {
                $noty = new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: 'Check all tabs for errors',
                    animation: {
                        speed: 500 // unavailable - no need
                    },
                }).show();
                setTimeout(function() {
                    $noty.close();
                }, 1000);
            },
            submitHandler: function(form) {

                let formData = new FormData($(form_id)[0]);
                $('#submit').button('loading');
                $.ajax({
                        url: laravel_routes['pettycashFinanceSave'],
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
                                text: 'Petty Cash saves successfully',
                                animation: {
                                    speed: 500 // unavailable - no need
                                },
                            }).show();
                            setTimeout(function() {
                                $noty.close();
                            }, 1000);
                            $location.path('/eyatra/petty-cash/verification2')
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
app.component('eyatraPettyCashFinanceView', {
    templateUrl: pettycash_finance_view_template_url,
    controller: function($http, $location, $routeParams, HelperService, $rootScope, $timeout) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        $http.get(
            petty_cash_finance_view_url + '/' + $routeParams.pettycash_id
        ).then(function(response) {
            console.log(response);
            self.petty_cash = response.data.petty_cash;
            self.petty_cash_other = response.data.petty_cash_other;
            self.rejection_list = response.data.rejection_list;
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

        var form_id = '#approve';
        var v = jQuery(form_id).validate({
            ignore: '',
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('#submit').button('loading');
                $.ajax({
                        url: laravel_routes['pettycashFinanceVerificationSave'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
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
                                text: 'Petty Cash Approved successfully',
                                animation: {
                                    speed: 500 // unavailable - no need
                                },
                            }).show();
                            setTimeout(function() {
                                $noty.close();
                            }, 1000);
                            $("#alert-modal-approve").modal('hide');
                            $timeout(function() {
                                $location.path('/eyatra/petty-cash/verification2')
                            }, 500);
                        }
                    })
                    .fail(function(xhr) {
                        $('#submit').button('reset');
                        custom_noty('error', 'Something went wrong at server');
                    });
            },
        });
        var form_id1 = '#reject';
        var v = jQuery(form_id1).validate({
            ignore: '',
            rules: {
                'remarks': {
                    required: true,
                    maxlength: 191,
                },
                'rejection_id': {
                    required: true,
                },
            },
            messages: {
                'remarks': {
                    required: 'Enter Remarks',
                    maxlength: 'Enter Maximum 191 Characters',
                },
                'rejection_id': {
                    required: 'Enter Rejection Reason required',
                },
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_id1)[0]);
                $('#submit').button('loading');
                $.ajax({
                        url: laravel_routes['pettycashFinanceVerificationSave'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
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
                                text: 'Petty Cash Rejected successfully',
                                animation: {
                                    speed: 500 // unavailable - no need
                                },
                            }).show();
                            setTimeout(function() {
                                $noty.close();
                            }, 1000);
                            $(".remarks").val('');
                            $("#alert-modal-reject").modal('hide');
                            $timeout(function() {
                                $location.path('/eyatra/petty-cash/verification2')
                            }, 500);
                        }
                    })
                    .fail(function(xhr) {
                        $('#submit').button('reset');
                        custom_noty('error', 'Something went wrong at server');
                    });
            },
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
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------