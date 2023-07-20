app.component('eyatraExpenseVoucherAdvanceVerificationList', {
    templateUrl: expense_voucher_advance_verification_list_template_url,
    controller: function(HelperService, $rootScope, $scope, $http, $routeParams, $location) {
        //alert();
        var self = this;
        self.hasPermission = HelperService.hasPermission;

        $list_data_url = expense_voucher_advance_verification_list_data_url;

        var dataTable = $('#expense_advance_verification_list').DataTable({
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
                    d.date = $('#created_date').val();
                }
            },

            columns: [
                { data: 'action', searchable: false, class: 'action' },
                { data: 'request_type', searchable: false },
                { data: 'request_number', searchable: false },
                // { data: 'advance_pcv_number', name: 'expense_voucher_advance_requests.number', searchable: true },
                // { data: 'advance_pcv_claim_number', name: 'expense_voucher_advance_request_claims.number', searchable: true },
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
        $('.dataTables_length select').select2();
        setTimeout(function() {
            var x = $('.separate-page-header-inner.search .custom-filter').position();
            var d = document.getElementById('expense_advance_verification_list_filter');
            x.left = x.left + 15;
            d.style.left = x.left + 'px';
        }, 500);
        setTimeout(function() {
            $('div[data-provide = "datepicker"]').datepicker({
                todayHighlight: true,
                autoclose: true,
            });
        }, 1000);
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
        $scope.onselectCreatedDate = function() {
            dataTable.draw();
        }
        $scope.reset_filter = function() {
            $('#employee_id').val('');
            $('#created_date').val('');
            dataTable.draw();
        }
        // $rootScope.loading = false; 
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('eyatraExpenseVoucherAdvanceVerificationView', {
    templateUrl: expense_voucher_advance_verification_view_template_url,
    controller: function($http, $location, $routeParams, HelperService, $rootScope, $timeout, $mdSelect, $scope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        $http.get(
            expense_voucher_advance_verification_view_data_url + '/' + $routeParams.id
        ).then(function(response) {
            console.log(response);
            self.expense_voucher_view = response.data.expense_voucher_view;
            self.rejection_list = response.data.rejection_list;
            self.expense_voucher_advance_attachment_url = eyatra_expense_voucher_advance_attachment_url;
            if(response.data.proof_view_pending_count > 0){
                self.show_pcv_expense_process_btn = false;
            }else{
                self.show_pcv_expense_process_btn = true;
            }
        });

        var form_id = '#approve';
        var v = jQuery(form_id).validate({
            ignore: '',
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('#accept_button').button('loading');
                $.ajax({
                        url: laravel_routes['expenseVoucherVerificationSave'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        console.log("afafafafdafaf")
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
                                $location.path('/expense/voucher-advance/verification1/')
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
                        url: laravel_routes['expenseVoucherVerificationSave'],
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
                                $location.path('/expense/voucher-advance/verification1/')
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

        $scope.proofUploadViewHandler = function(id,index) {
            $.ajax({
                url: laravel_routes['expenseVoucherAdvanceProofUploadViewStatusUpdate'],
                method: "POST",
                data: {
                    attachment_id : id,
                    expense_voucher_advance_request_id : self.expense_voucher_view.id,
                }
            })
            .done(function(res) {
                if (!res.success) {
                    custom_noty('error', res.errors);
                } else {
                    self.expense_voucher_view.attachments[index].view_status = res.attachment.view_status;
                    if(res.proof_view_pending_count > 0){
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

        $rootScope.loading = false;
    }
});
//------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------