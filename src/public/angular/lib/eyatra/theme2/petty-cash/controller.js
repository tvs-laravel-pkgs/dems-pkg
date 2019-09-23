app.component('eyatraPettyCashList', {
    templateUrl: eyatra_pettycash_list_template_url,
    controller: function(HelperService, $rootScope, $scope, $http, $routeParams, $location) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.add_permission = self.hasPermission('eyatra-indv-expense-vouchers');
        //alert(self.add_permission);
        var dataTable = '';
        $list_data_url = eyatra_pettycash_get_list;
        $http.get(
            $list_data_url
        ).then(function(response) {
            dataTable = $('#petty_cash_list').DataTable({
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
                    url: laravel_routes['listPettyCashRequest'],
                    type: "GET",
                    dataType: "json",
                    data: function(d) {
                        d.status_id = $('#status').val();
                    }
                },

                columns: [
                    { data: 'action', searchable: false, class: 'action' },
                    { data: 'petty_cash_type', searchable: false },
                    { data: 'ename', name: 'users.name', searchable: true },
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
            setTimeout(function() {
                var x = $('.separate-page-header-inner.search .custom-filter').position();
                var d = document.getElementById('petty_cash_list_filter');
                x.left = x.left + 15;
                d.style.left = x.left + 'px';
            }, 500);


            $http.get(
                expense_voucher_filter_url
            ).then(function(response) {
                // console.log(response.data);
                self.agent_list = response.data.agent_list;
                self.tm_list = response.data.tm_list;
                self.status_list = response.data.status_list;
                // $rootScope.loading = false;
            });

        });
        $scope.onselectStatus = function(id) {
            $('#status').val(id);
            dataTable.draw();
        }

        $scope.reset_filter = function() {
            $('#status').val('');
            dataTable.draw();
        }
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
                    $('#petty_cash_list').DataTable().ajax.reload(function(json) {});
                    $location.path('eyatra/petty-cash/');
                    $scope.$apply();
                }
            });
        }
        // $rootScope.loading = false;
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('eyatraPettyCashForm', {
    templateUrl: pettycash_form_template_url,
    controller: function($http, $location, $location, HelperService, $routeParams, $rootScope, $scope) {
        if ($routeParams.type_id == 1 || $routeParams.type_id == 2) {} else {
            $location.path('/page-not-found')
            return;
        }
        $form_data_url = typeof($routeParams.pettycash_id) == 'undefined' ? pettycash_form_data_url : pettycash_form_data_url + '/' + $routeParams.type_id + '/' + $routeParams.pettycash_id;
        var self = this;
        self.type_id = $routeParams.type_id;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        self.search_permission = self.hasPermission('eyatra-indv-expense-vouchers-verification2');
        // alert(self.search_permission);
        $http.get(
            $form_data_url
        ).then(function(response) {
            // if (!response.data.success) {
            //     $noty = new Noty({
            //         type: 'error',
            //         layout: 'topRight',
            //         text: response.data.error,
            //         animation: {
            //             speed: 500 // unavailable - no need
            //         },
            //     }).show();
            //     setTimeout(function() {
            //         $noty.close();
            //     }, 5000);
            //     $location.path('/eyatra/petty-cash/' + $routeParams.type_id)
            //     return;
            // }
            // console.log(response);
            self.extras = response.data.extras;
            self.localconveyance = response.data.localconveyance;
            self.action = response.data.action;
            self.petty_cash_locals = response.data.petty_cash;
            self.employee_list = response.data.employee_list;
            self.employee = response.data.employee;
            self.petty_cash_others = response.data.petty_cash_other;
            self.user_role = response.data.user_role;
            self.emp_details = response.data.emp_details;
            self.petty_cash_removal_id = [];
            self.petty_cash_other_removal_id = [];

            $("#employee_id").val(self.emp_details.emp_id);

            if (self.action == 'Edit') {
                $.each(self.petty_cash_locals, function(index, value) {
                    setTimeout(function() {
                        $scope.getRatePerkm(value.travel_mode_id, index);
                    }, 500);

                });
            } else {
                // self.selectedItem = response.data.employee_list;
                // $('.employee').val('');
                if (self.type_id == 2) { //OTHER
                    self.addotherexpence();
                } else { //LOCAL CONVEYANCE
                    self.addlocalconveyance();
                }
            }
            setTimeout(function() {
                if (self.type_id == 2) { //OTHER
                    self.otherConveyanceCal();
                } else { // LOCAL CONVEYANCE
                    self.localConveyanceCal();
                }
            }, 500);
            $rootScope.loading = false;
            /* Datepicker With Current Date */

        });

        $scope.getRatePerkm = function(id, index) {
            if (id == 15) {
                $(".ratePerKMtext_" + index).html('Per Km - ₹ ' + self.emp_details.two_wheeler_limit);
                $(".ratePerKMamount_" + index).val(self.emp_details.two_wheeler_limit);
            } else if (id == 16) {
                $(".ratePerKMtext_" + index).html('Per Km - ₹ ' + self.emp_details.four_wheeler_limit);
                $(".ratePerKMamount_" + index).val(self.emp_details.four_wheeler_limit);
            }
        }



        if (self.search_permission == true) {
            $(".removeDetails").on('click', function() {
                self.emp_details = [];
                self.emp_details1 = [];
                $("#employee_id").val('');
                setTimeout(function() {
                    document.querySelector('#autoFocusId').focus();
                }, 0);
            });

            $scope.getEmployee = function() {
                if ($("#employee_id").val() != '') {
                    var id = $("#employee_id").val();
                    $http.get(
                        get_employee_details + '/' + id
                    ).then(function(response) {
                        self.emp_details1 = response.data.emp_details;
                    });
                }
            }

            //SEARCH EMPLOYEE
            self.searchEmployee = function(query) {
                if (query) {
                    return new Promise(function(resolve, reject) {
                        $http
                            .post(
                                search_employee_url, {
                                    key: query,
                                }
                            )
                            .then(function(response) {
                                resolve(response.data);
                            });
                        //reject(response);
                    });
                } else {
                    return [];
                }
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
                autoclose: true,
            });
        }, 1500);

        //LOCAL CONVEYANCE FROM KM & TO KM AMOUNT CALC
        $(document).on('input', '.localconveyance_km', function() {
            var localConveyance_amount = 0;
            var localconveyance_from_km = $(this).closest('tr').find('.localconveyance_from_km').val();
            var localconveyance_to_km = $(this).closest('tr').find('.localconveyance_to_km').val();
            if (localconveyance_from_km && localconveyance_to_km) {
                var localConveyance_from_to_diff = localconveyance_to_km - localconveyance_from_km;
                var localconveyance_base_per_km_amount = parseInt($(this).closest('tr').find('.base_per_km_amount').val() || 0);
                localConveyance_amount = localConveyance_from_to_diff * localconveyance_base_per_km_amount;
                // console.log(' == localconveyance_from_km ==' + localconveyance_from_km + ' == localconveyance_to_km ==' + localconveyance_to_km + ' == localConveyance_from_to_diff ==' + localConveyance_from_to_diff + ' === localConveyance_amount ==' + localConveyance_amount);
                $(this).closest('tr').find('.localConveyance_amount').val(localConveyance_amount.toFixed(2));
                self.localConveyanceCal();
            } else {
                $(this).closest('tr').find('.localConveyance_amount').val('');
                self.localConveyanceCal();
            }

        });

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
            $('.claim_total_amount').val(total_petty_cash_local_amount.toFixed(2));
            $('.claim_total_amount').text('₹ ' + total_petty_cash_local_amount.toFixed(2));
            // caimTotalAmount();
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
            $('.claim_total_amount').val(total_petty_cash_other_amount.toFixed(2));
            $('.claim_total_amount').text('₹ ' + total_petty_cash_other_amount.toFixed(2));
            // caimTotalAmount();
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
            self.petty_cash_locals.push({
                from_place: '',
                to_place: '',
                from_km: '',
                to_km: '',
            });
        }
        self.addotherexpence = function() {
            self.petty_cash_others.push({
                expence_type: '',
                preferred_travel_modes: '',
            });
        }

        self.removepettyCash = function(index, petty_cash_id) {
            if (petty_cash_id) {
                self.petty_cash_removal_id.push(petty_cash_id);
                $('#petty_cash_removal_id').val(JSON.stringify(self.petty_cash_removal_id));
            }
            self.petty_cash_locals.splice(index, 1);
            setTimeout(function() {
                self.localConveyanceCal();
            }, 500);
        }

        self.removeotherexpence = function(index, petty_cash_other_id) {
            if (petty_cash_other_id) {
                self.petty_cash_other_removal_id.push(petty_cash_other_id);
                $('#petty_cash_other_removal_id').val(JSON.stringify(self.petty_cash_other_removal_id));
            }
            self.petty_cash_others.splice(index, 1);
            setTimeout(function() {
                self.otherConveyanceCal();
            }, 500);
        }

        var form_id = '#petty-cash';
        var v = jQuery(form_id).validate({
            errorPlacement: function(error, element) {
                if (element.attr('name') == 'employee_id') {
                    error.appendTo($('.employee_error'));
                } else {
                    error.insertAfter(element)
                }
            },
            ignore: '',
            invalidHandler: function(event, validator) {
                $noty = new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: 'You have errors please check',
                    animation: {
                        speed: 500 // unavailable - no need
                    },
                }).show();
                setTimeout(function() {
                    $noty.close();
                }, 5000);
            },
            submitHandler: function(form) {

                let formData = new FormData($(form_id)[0]);
                $('#submit').button('loading');
                $.ajax({
                        url: laravel_routes['pettycashSave'],
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
                            }, 5000);
                            $location.path('/eyatra/petty-cash')
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