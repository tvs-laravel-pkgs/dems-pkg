app.component('eyatraExpenseVoucherAdvanceVerification2List', {
    templateUrl: expense_voucher_advance_verification2_list_template_url,
    controller: function(HelperService, $rootScope, $scope, $http, $routeParams, $location) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        if(!self.hasPermission('eyatra-indv-expense-vouchers-verification2')){
            window.location = "#!/permission-denied";
            return false;
        }
        $list_data_url = expense_voucher_advance_verification2_list_data_url;

        var dataTable = $('#expense_advance_verification2_list').DataTable({
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
            retrieve: true,
            ajax: {
                url: $list_data_url,
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.employee_id = $('#employee_id').val();
                }
            },

            columns: [
                { data: 'action', searchable: false, class: 'action' },
                { data: 'request_type', searchable: false },
                { data: 'request_number', searchable: false },
                // { data: 'advance_pcv_claim_number', name: 'expense_voucher_advance_request_claims.number', searchable: true },
                // { data: 'advance_pcv_number', name: 'expense_voucher_advance_requests.number', searchable: true },
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
        // $('.separate-page-header-content .data-table-title').html('<p class="breadcrumb">Expense Voucher / Expense Voucher Advances list</p><h3 class="title">Expense Voucher Advances</h3>');
        setTimeout(function() {
            var x = $('.separate-page-header-inner.search .custom-filter').position();
            var d = document.getElementById('expense_advance_verification2_list_filter');
            x.left = x.left + 15;
            d.style.left = x.left + 'px';
        }, 500);
        //Filter
        $http.get(
            expense_voucher_advance_filter_url
        ).then(function(response) {
            self.employee_list = response.data.employee_list;
            self.status_list = response.data.status_list;
        });
        $scope.onselectEmployee = function(id) {
            //alert();
            $('#employee_id').val(id);
            dataTable.draw();
        }
        $scope.reset_filter = function() {
            $('#employee_id').val('');
            dataTable.draw();
        }
        // $rootScope.loading = false;
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('eyatraExpenseVoucherAdvanceVerification2View', {
    templateUrl: expense_voucher_advance_verification2_view_template_url,
    controller: function($http, $location, $routeParams, HelperService, $rootScope, $timeout, $scope, $mdSelect) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        if(!self.hasPermission('eyatra-advance-pcv-cashier-view')){
            window.location = "#!/permission-denied";
            return false;
        }
        $http.get(
            expense_voucher_advance_verification2_view_data_url + '/' + $routeParams.id
        ).then(function(response) {
            console.log(response);
            self.expense_voucher_view = response.data.expense_voucher_view;
            self.rejection_list = response.data.rejection_list;
            self.bank_detail = response.data.bank_detail;
            self.cheque_detail = response.data.cheque_detail;
            self.wallet_detail = response.data.wallet_detail;
            self.payment_mode_list = response.data.payment_mode_list;
            self.wallet_mode_list = response.data.wallet_mode_list;
            self.expense_voucher_advance_attachment_url = eyatra_expense_voucher_advance_attachment_url;
            self.cashier_payment_date = response.data.cashier_payment_date;
            console.log(self.expense_voucher_view);
            $scope.showApproveLayout = false;
            $scope.showApproveModal = false;
            self.amount = 0;
            if (self.expense_voucher_view.status_id == '3461') {
                self.type_id = 1;
                $scope.showApproveLayout = true;
            // } else if (self.expense_voucher_view.status_id == '3467') {
            } else if (self.expense_voucher_view.advance_pcv_claim_status_id == '3467') {

                if (parseInt(self.expense_voucher_view.expense_amount) <= parseInt(self.expense_voucher_view.advance_amount)) {
                    $scope.showApproveLayout = false;
                    $scope.showApproveModal = true;
                    self.type_id = 0;
                } else {
                    self.type_id = 2;
                    $scope.showApproveLayout = true;
                }

                if (parseInt(self.expense_voucher_view.expense_amount) > parseInt(self.expense_voucher_view.advance_amount)) {
                    self.amount = parseInt(self.expense_voucher_view.expense_amount) - parseInt(self.expense_voucher_view.advance_amount);
                } else if (parseInt(self.expense_voucher_view.expense_amount) < parseInt(self.expense_voucher_view.advance_amount)) {
                    self.amount = parseInt(self.expense_voucher_view.advance_amount) - parseInt(self.expense_voucher_view.expense_amount);
                } else {
                    self.amount = 0;
                }
            } else {
                self.type_id = 0;
            }
            console.log($scope.showApproveLayout);
            console.log($scope.showApproveModal);

            if(response.data.proof_view_pending == true){
                self.show_pcv_expense_process_btn = false;
            }else{
                self.show_pcv_expense_process_btn = true;
            }
        });

        $(".bottom-expand-btn").on('click', function() {
            console.log(' click ==');
            if ($(".separate-bottom-fixed-layer").hasClass("in")) {
                console.log(' has ==');

                $(".separate-bottom-fixed-layer").removeClass("in");
            } else {
                console.log(' has not ==');

                $(".separate-bottom-fixed-layer").addClass("in");
                $(".bottom-expand-btn").css({ 'display': 'none' });
            }
        });
        $(".btn_close").on('click', function() {
            if ($(".separate-bottom-fixed-layer").hasClass("in")) {
                $(".separate-bottom-fixed-layer").removeClass("in");
                $(".bottom-expand-btn").css({ 'display': 'inline-block' });
            } else {
                $(".separate-bottom-fixed-layer").addClass("in");
            }
        });

        var d = new Date();
        var val = d.getDate() + "-" + (d.getMonth() + 1) + "-" + d.getFullYear();
        $("#cuttent_date").val(val);
        $('div[data-provide = "datepicker"]').datepicker({
            todayHighlight: true,
            autoclose: true,
            endDate: '+0d',
        });

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

        $scope.approveModalHandler = function(){
            // if(!self.coa_code1_detail){
            //     custom_noty('error', 'Kindly select the coa code');
            //     return;
            // }
            $("#alert-modal-approve").modal('show');
        }

        var form_id = '#approve';
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
                'date': {
                    required: true,
                },
                'reference_number': {
                    // required: true,
                    maxlength: 100,
                    minlength: 3,
                },
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('#accept_button').button('loading');
                $.ajax({
                        url: laravel_routes['expenseVoucherVerification2Save'],
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
                            custom_noty('success', 'Expense Voucher Advance Approved successfully');
                            $("#alert-modal-approve").modal('hide');
                            $timeout(function() {
                                $location.path('/expense/voucher-advance/verification2/')
                            }, 500);
                        }
                    })
                    .fail(function(xhr) {
                        $('#accept_button').button('reset');
                        custom_noty('error', 'Something went wrong at server');
                    });
            },
        });
        var approve_modal_from = '#approve_modal_from';
        var v = jQuery(approve_modal_from).validate({
            errorPlacement: function(error, element) {
                error.insertAfter(element)
            },
            ignore: '',
            submitHandler: function(form) {
                let formData = new FormData($(approve_modal_from)[0]);
                $('#accept_button').button('loading');
                $.ajax({
                        url: laravel_routes['expenseVoucherVerification2Save'],
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
                                text: 'Expense Voucher Advance Approved successfully',
                                animation: {
                                    speed: 500 // unavailable - no need
                                },
                            }).show();
                            setTimeout(function() {
                                $noty.close();
                            }, 5000);
                            $("#alert-modal-approve").modal('hide');
                            $timeout(function() {
                                $location.path('/expense/voucher-advance/verification2/')
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
            },
            messages: {
                'remarks': {
                    required: 'Enter Remarks',
                    maxlength: 'Enter Maximum 191 Characters',
                },
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_id1)[0]);
                $('#reject_button').button('loading');
                $.ajax({
                        url: laravel_routes['expenseVoucherVerification2Save'],
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
                                text: 'Expense Voucher Advance Rejected successfully',
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
                                $location.path('/expense/voucher-advance/verification2/')
                            }, 500);
                        }
                    })
                    .fail(function(xhr) {
                        $('#reject_button').button('reset');
                        custom_noty('error', 'Something went wrong at server');
                    });
            },
        });

        $scope.proofUploadViewHandler = function(attachment, index) {
            if(attachment && attachment.view_status == 1){
                //ALREADY VIEWED BY USER
                return;
            }

            $.ajax({
                url: laravel_routes['expenseVoucherAdvanceCashierProofViewUpdate'],
                method: "POST",
                data: {
                    attachment_id : attachment.id,
                    expense_voucher_advance_request_id : self.expense_voucher_view.id,
                }
            })
            .done(function(res) {
                if (!res.success) {
                    custom_noty('error', res.errors);
                } else {
                    self.expense_voucher_view.attachments[index].view_status = 1; //VIEWED
                    if(res.proof_view_pending == true){
                        self.show_pcv_expense_process_btn = false;
                    }else{
                        self.show_pcv_expense_process_btn = true;
                    }
                    $scope.$apply();
                }
            })
            .fail(function(xhr) {
                custom_noty('error', 'Something went wrong at server.');
            });
        }
        
        /* Modal Md Select Hide */
        $('.modal').bind('click', function(event) {
            if ($('.md-select-menu-container').hasClass('md-active')) {
                $mdSelect.hide();
            }
        });

        $rootScope.loading = false;
    }
});
//------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------