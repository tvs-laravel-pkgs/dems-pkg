app.component('eyatraPettyCashCashierList', {
    templateUrl: eyatra_pettycash_cashier_list_template_url,
    controller: function(HelperService, $rootScope, $scope, $http, $routeParams) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        if(!self.hasPermission('eyatra-indv-expense-vouchers-verification2')){
            window.location = "#!/permission-denied";
            return false;
        }
        // if ($routeParams.expence_type == 1) {
        $list_data_url = eyatra_pettycash_cashier_list_url;
        // } else {
        //     $list_data_url = eyatra_pettycash_cashier_list_url + '/' + 2;
        // }
        $http.get(
            $list_data_url
        ).then(function(response) {
            var dataTable = $('#petty_cash_cashier_list').DataTable({
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
                    url: laravel_routes['listPettyCashVerificationCashier'],
                    type: "GET",
                    dataType: "json",
                    data: function(d) {
                        d.status_id = $('#status').val();
                        d.outlet_id = $('#outlet').val();
                        d.employee_id = $('#employee').val();
                    }
                },

                columns: [
                    { data: 'action', searchable: false, class: 'action' },
                    { data: 'petty_cash_type', name: 'petty_cash_type.name', searchable: true },
                    { data: 'number', name: 'petty_cash.number', searchable: true },
                    { data: 'ename', name: 'users.name', searchable: true },
                    { data: 'ecode', name: 'employees.code', searchable: true },
                    { data: 'oname', name: 'outlets.name', searchable: true },
                    { data: 'ocode', name: 'outlets.code', searchable: true },
                    { data: 'date', name: 'date', searchable: false },
                    { data: 'total', name: 'total', searchable: true },
                    { data: 'status', name: 'configs.name', searchable: true },
                ],
                rowCallback: function(row, data) {
                    $(row).addClass('highlight-row');
                }
            });
            $('.dataTables_length select').select2();
            setTimeout(function() {
                var x = $('.separate-page-header-inner.search .custom-filter').position();
                var d = document.getElementById('petty_cash_cashier_list_filter');
                x.left = x.left + 15;
                d.style.left = x.left + 'px';
            }, 500);
            $http.get(
                expense_voucher_filter_url
            ).then(function(response) {

                self.status_list = response.data.status_list;
                self.outlet_list = response.data.outlet_list;
                self.employee_list = response.data.employee_list;

            });
            $scope.onselectStatus = function(id) {
                $('#status').val(id);
                dataTable.draw();
            }
            $scope.onselectOutlet = function(id) {
                $('#outlet').val(id);
                dataTable.draw();
            }
            $scope.onselectEmployee = function(id) {
                $('#employee').val(id);
                dataTable.draw();
            }
            $scope.reset_filter = function() {
                $('#status').val('');
                $('#outlet').val('');
                $('#employee').val('');
                dataTable.draw();
            }
        });
    }
});
//------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('eyatraPettyCashCashierView', {
    templateUrl: pettycash_cashier_view_template_url,
    controller: function($http, $location, $routeParams, HelperService, $rootScope, $timeout, $scope, $mdSelect) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        if($routeParams.type_id == 2){
            if(!self.hasPermission('eyatra-pcv-cashier-view')){
                window.location = "#!/permission-denied";
                return false;
            }
        }
        
        $http.get(
            petty_cash_cashier_view_url + '/' + $routeParams.type_id + '/' + $routeParams.pettycash_id
        ).then(function(response) {
            // console.log(response);
            self.type_id = $routeParams.type_id;
            self.bank_detail = response.data.bank_detail;
            self.cheque_detail = response.data.cheque_detail;
            self.wallet_detail = response.data.wallet_detail;
            self.petty_cash = response.data.petty_cash;
            self.petty_cash_detail = response.data.petty_cash_detail;
            self.petty_cash_other = response.data.petty_cash_other;
            self.payment_mode_list = response.data.payment_mode_list;
            self.wallet_mode_list = response.data.wallet_mode_list;
            self.rejection_list = response.data.rejection_list;
            self.employee = response.data.employee;
            self.cashier_payment_date = response.data.cashier_payment_date;
            console.log(self.employee);
            self.localconveyance_attachment_url = eyatra_petty_cash_local_conveyance_attachment_url;
            self.other_expense_attachment_url = eyatra_petty_cash_other_expense_attachment_url;

            if ($routeParams.type_id == 1) {
                $('.separate-page-title').html('<p class="breadcrumb">Expense Voucher / <a href="#!/petty-cash/verification2">Expense Voucher list</a> / View</p><h3 class="title">Localconveyance Voucher Claim</h3>');
            } else {
                $('.separate-page-title').html('<p class="breadcrumb">Expense Voucher / <a href="#!/petty-cash/verification2">Expense Voucher list</a> / View</p><h3 class="title">Other Expense Voucher Claim</h3>');
            }
            var d = new Date();
            var val = d.getDate() + "-" + (d.getMonth() + 1) + "-" + d.getFullYear();
            $("#cuttent_date").val(val);
            // console.log(val);

            if(response.data.proof_view_pending == true){
                self.show_pcv_process_btn = false;
            }else{
                self.show_pcv_process_btn = true;
            }
        });

        $(".bottom-expand-btn").on('click', function() {
            if ($(".separate-bottom-fixed-layer").hasClass("in")) {
                $(".separate-bottom-fixed-layer").removeClass("in");
            } else {
                $(".separate-bottom-fixed-layer").addClass("in");
                $(".bottom-expand-btn").css({ 'display': 'none' });
            }
        });
        $(".approve-close").on('click', function() {
            if ($(".separate-bottom-fixed-layer").hasClass("in")) {
                $(".separate-bottom-fixed-layer").removeClass("in");
                $(".bottom-expand-btn").css({ 'display': 'inline-block' });
            } else {
                $(".separate-bottom-fixed-layer").addClass("in");
            }
        });

        $scope.proofUploadViewHandler = function(pcv_detail_index,pcv_detail_id,attachment,attachment_index) {
            if(attachment && attachment.view_status == 1){
                //ALREADY VIEWED BY USER
                return;
            }

            $.ajax({
                url: laravel_routes['pettyCashProofCashierViewSave'],
                method: "POST",
                data: {
                    attachment_id : attachment.id,
                    petty_cash_detail_id : pcv_detail_id,
                }
            })
            .done(function(res) {
                if (!res.success) {
                    custom_noty('error', res.errors);
                } else {
                    self.petty_cash_other[pcv_detail_index].attachments[attachment_index].view_status = 1;
                    if(res.proof_view_pending == true){
                        self.show_pcv_process_btn = false;
                    }else{
                        self.show_pcv_process_btn = true;
                    }
                    $scope.$apply();
                }
            })
            .fail(function(xhr) {
                custom_noty('error', 'Something went wrong at server.');
            });
        }


        self.searchCoaCodes = function(query) {
            if (query) {
                return new Promise(function(resolve, reject) {
                    $http
                        .post(
                            laravel_routes['expenseVoucherAdvanceSearchOracleCoaCodes'], {
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

        $.validator.addMethod('positiveNumber',
            function(value) {
                return Number(value) > 0;
            }, 'Enter a positive number.');

        var form_id = '#petty-cash-form';
        var v = jQuery(form_id).validate({
            errorPlacement: function(error, element) {
                error.insertAfter(element)
            },
            ignore: '',
            rules: {
                'amount': {
                    min: 1,
                    number: true,
                    required: true,
                },
                'reference_number': {
                    // required: true,
                },

                'date': {
                    required: true,
                },
                'bank_name': {
                    required: function(element) {
                        if ($('#payment_mode_id').val() == 3244) {
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
                        if ($('#payment_mode_id').val() == 3244) {
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
                        if ($('#payment_mode_id').val() == 3244) {
                            return true;
                        } else {
                            return false;
                        }
                    },
                    maxlength: 20,
                    minlength: 3,
                    positiveNumber: function(element) {
                        if ($('#payment_mode_id').val() == 3244) {
                            return true;
                        } else {
                            return false;
                        }
                    },
                },
                'ifsc_code': {
                    required: function(element) {
                        if ($('#payment_mode_id').val() == 3244) {
                            return true;
                        } else {
                            return false;
                        }
                    },
                    maxlength: 10,
                    minlength: 3,
                },
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('#accept_button').button('loading');
                $.ajax({
                        url: laravel_routes['pettycashCashierVerificationSave'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        if (!res.success) {
                            $('#accept_button').button('reset');
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
                            }, 5000);
                            $("#alert-modal-approve").modal('hide');
                            $timeout(function() {
                                $location.path('/petty-cash/verification2/')
                            }, 500);
                        }
                    })
                    .fail(function(xhr) {
                        $('#accept_button').button('reset');
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
                $('#reject_button').button('loading');
                $.ajax({
                        url: laravel_routes['pettycashCashierVerificationSave'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        if (!res.success) {
                            $('#reject_button').button('reset');
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
                            }, 5000);
                            $(".remarks").val('');
                            $("#alert-modal-reject").modal('hide');
                            $timeout(function() {
                                $location.path('/petty-cash/verification2/')
                            }, 500);
                        }
                    })
                    .fail(function(xhr) {
                        $('#reject_button').button('reset');
                        custom_noty('error', 'Something went wrong at server');
                    });
            },
        });
        /* Modal Md Select Hide */
        $('.modal').bind('click', function(event) {
            if ($('.md-select-menu-container').hasClass('md-active')) {
                $mdSelect.hide();
            }
        });


        $rootScope.loading = false;
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------