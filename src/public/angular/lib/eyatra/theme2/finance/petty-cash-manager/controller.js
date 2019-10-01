app.component('eyatraPettyCashManagerList', {
    templateUrl: eyatra_pettycash_manager_list_template_url,
    controller: function(HelperService, $rootScope, $scope, $http, $routeParams) {
        //alert('this');
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        // alert($routeParams.type_id);
        // if ($routeParams.expense_type == 1) {
        $list_data_url = eyatra_pettycash_manager_list_url;
        // } else {
        //     $list_data_url = eyatra_pettycash_manager_list_url + '/' + 2;
        // }
        $http.get(
            $list_data_url
        ).then(function(response) {
            var dataTable = $('#petty_cash_manager_list').DataTable({
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
                    url: laravel_routes['listPettyCashVerificationManager'],
                    type: "GET",
                    dataType: "json",
                    data: function(d) {
                        d.status_id = $('#status').val();
                        d.outlet_id = $('#outlet').val();
                        d.employee_id = $('#employee').val();
                        d.type = $('#petty_cash_type').val();
                        d.date = $('#created_date').val();
                    }
                },

                columns: [
                    { data: 'action', searchable: false, class: 'action' },
                    { data: 'petty_cash_type', name: 'petty_cash_type.name', searchable: true },
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
                var d = document.getElementById('petty_cash_manager_list_filter');
                x.left = x.left + 15;
                d.style.left = x.left + 'px';
            }, 500);
            setTimeout(function() {
                $('div[data-provide = "datepicker"]').datepicker({
                    todayHighlight: true,
                    autoclose: true,
                });
            }, 1000);
            $http.get(
                expense_voucher_filter_url
            ).then(function(response) {

                self.status_list = response.data.status_list;
                self.outlet_list = response.data.outlet_list;
                self.employee_list = response.data.employee_list;
                self.petty_cash_type_list = response.data.petty_cash_type_list;


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
            $scope.onselectPettyCashType = function(id) {
                $('#petty_cash_type').val(id);
                dataTable.draw();
            }
            $scope.onselectCreatedDate = function() {
                dataTable.draw();
            }

            $scope.reset_filter = function() {
                $('#status').val('');
                $('#outlet').val('');
                $('#employee').val('');
                $('#petty_cash_type').val('');
                $('#created_date').val('');
                dataTable.draw();
            }
        });
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('eyatraPettyCashManagerView', {
    templateUrl: pettycash_manager_view_template_url,
    controller: function($http, $location, $routeParams, HelperService, $rootScope, $timeout) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        $http.get(
            petty_cash_manager_view_url + '/' + $routeParams.type_id + '/' + $routeParams.pettycash_id
        ).then(function(response) {
            // console.log(response);
            self.petty_cash = response.data.petty_cash;
            self.type_id = $routeParams.type_id;
            self.petty_cash_other = response.data.petty_cash_other;
            self.rejection_list = response.data.rejection_list;
            self.employee = response.data.employee;
            self.localconveyance_attachment_url = eyatra_petty_cash_local_conveyance_attachment_url;
            self.other_expense_attachment_url = eyatra_petty_cash_other_expense_attachment_url;

            if ($routeParams.type_id == 1) {
                $('.separate-page-title').html('<p class="breadcrumb">Expense Voucher / <a href="#!/eyatra/petty-cash/verification1">Expense Voucher list</a> / View</p><h3 class="title">Localconveyance Voucher Claim</h3>');
            } else {
                $('.separate-page-title').html('<p class="breadcrumb">Expense Voucher / <a href="#!/eyatra/petty-cash/verification1">Expense Voucher list</a> / View</p><h3 class="title">Other Expense Voucher Claim</h3>');
            }

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
                $('#accept_button').button('loading');
                $.ajax({
                        url: laravel_routes['pettycashManagerVerificationSave'],
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
                                $location.path('/eyatra/petty-cash/verification1/')
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
                    minlength: 5,
                    maxlength: 191,
                },
            },
            messages: {
                'remarks': {
                    required: 'Enter Remarks',
                    minlength: 'Enter Minimum 5 Characters',
                    maxlength: 'Enter Maximum 191 Characters',
                },
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_id1)[0]);
                $('#reject_button').button('loading');
                $.ajax({
                        url: laravel_routes['pettycashManagerVerificationSave'],
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
                                $location.path('/eyatra/petty-cash/verification1/')
                            }, 500);
                        }
                    })
                    .fail(function(xhr) {
                        $('#reject_button').button('reset');
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