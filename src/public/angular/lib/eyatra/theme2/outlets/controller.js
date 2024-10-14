app.component('eyatraOutlets', {
    templateUrl: eyatra_outlet_list_template_url,
    controller: function(HelperService, $rootScope, $http, $scope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.permissionadd = self.hasPermission('eyatra-outlet-add');
        self.permissionimport = self.hasPermission('eyatra-import-outlet');
        $http.get(
            outlet_filter_data_url
        ).then(function(response) {
            // console.log(response.data);
            self.region_list = response.data.region_list;
            self.cashier_list = response.data.cashier_list;
            self.nodel_list = response.data.nodel_list;
            // self.city_list = response.data.city_list;
            // self.state_list = response.data.state_list;
            self.country_list = response.data.country_list;
            $rootScope.loading = false;
        });

        var dataTable = $('#eyatra_outlet_table').DataTable({
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
                url: laravel_routes['listEYatraOutlet'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.region_id = $('#region_id').val();
                    d.city_id = $('#city_id').val();
                    d.state_id = $('#state_id').val();
                    d.country_id = $('#country_id').val();
                    d.cashier_id = $('#cashier').val();
                }
            },

            columns: [
                { data: 'action', searchable: false, class: 'action', class: 'text-left' },
                { data: 'code', name: 'outlets.code', searchable: true },
                { data: 'name', name: 'outlets.name', searchable: true },
                { data: 'business_name', name: 'businesses.name', searchable: true },
                { data: 'emp_code', name: 'employees.code', searchable: true },
                // { data: 'emp_name', name: 'users.name', searchable: true },
                { data: 'cashier_name', searchable: false },
                { data: 'region_name', name: 'r.name', searchable: true },
                { data: 'city_name', name: 'city.name', searchable: true },
                { data: 'state_name', name: 's.name', searchable: true },
                { data: 'country_name', name: 'c.name', searchable: true },
                { data: 'status', name: 'outlets.deleted_at', searchable: true },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });

        $('.dataTables_length select').select2();
        setTimeout(function() {
            var x = $('.separate-page-header-inner.search .custom-filter').position();
            var d = document.getElementById('eyatra_outlet_table_filter');
            x.left = x.left + 15;
            d.style.left = x.left + 'px';
        }, 500);
        $('#eyatra_outlet_table_filter').find('input').addClass("on_focus");
        $('.on_focus').focus();
        $scope.getCashierData = function(query) {
            $('#cashier').val(query);
            dataTable.draw();
        }
        $scope.getRegionData = function(query) {
            $('#region_id').val(query);
            dataTable.draw();
        }
        $scope.getCityData = function(query) {
            $('#city_id').val(query);
            dataTable.draw();
        }
        $scope.getStateData = function(query) {
            $('#state_id').val(query);
            dataTable.draw();
        }
        $scope.getCountryData = function(query) {
            //alert(1);
            $('#country_id').val(query);
            dataTable.draw();
        }

        $scope.reset_filter = function(query) {
            $('#region_id').val(-1);
            $('#city_id').val(-1);
            $('#state_id').val(-1);
            $('#country_id').val(-1);
            $('#cashier').val();
            dataTable.draw();
        }
        $scope.deleteOutletConfirm = function($outlet_id) {
            $("#delete_outlet_id").val($outlet_id);
        }

        $scope.deleteOutlet = function() {
            $outlet_id = $('#delete_outlet_id').val();
            $http.get(
                outlet_delete_url + '/' + $outlet_id,
            ).then(function(response) {
                // console.log(response.data);
                if (response.data.success) {
                    $noty = new Noty({
                        type: 'success',
                        layout: 'topRight',
                        text: 'Outlet Deleted Successfully',
                        animation: {
                            speed: 500 // unavailable - no need
                        },
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 5000);
                    dataTable.ajax.reload(function(json) {});

                } else {
                    $noty = new Noty({
                        type: 'error',
                        layout: 'topRight',
                        text: 'Outlet not Deleted',
                        animation: {
                            speed: 500 // unavailable - no need
                        },
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 5000);
                }
            });
        }

        $scope.get_state_base_country = function(country_id) {
            $http.get(
                outlet_get_state_filter_list + '/' + country_id
            ).then(function(response) {
                self.state_list = response.data.state_list;

            });
        }

        $scope.get_city_base_state = function(state_id) {
            $http.get(
                outlet_get_city_filter_list + '/' + state_id
            ).then(function(response) {
                self.city_list = response.data.city_list;

            });
        }


        $rootScope.loading = false;

    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('eyatraOutletForm', {
    templateUrl: outlet_form_template_url,
    controller: function($http, $location, HelperService, $routeParams, $rootScope, $scope) {
        $form_data_url = typeof($routeParams.outlet_id) == 'undefined' ? outlet_form_data_url : outlet_form_data_url + '/' + $routeParams.outlet_id;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        $scope.showAmountLimit = false;
        $scope.checkedAmountEligible = false;
        $('#on_focus').focus();
        $http.get(
            $form_data_url
        ).then(function(response) {
            if (!response.data.success) {
                $noty = new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: response.data.error,
                    animation: {
                        speed: 500 // unavailable - no need
                    },
                }).show();
                setTimeout(function() {
                    $noty.close();
                }, 5000);
                $location.path('/outlets')
                $scope.$apply()
                return;
            }
            // console.log(response);
            self.outlet = response.data.outlet;

            console.log(response.data.outlet);
            // self.threshhold_amount = Number(response.data.outlet.amount_limit).toLocaleString('en-IN');
            if (self.action == "Edit") {
                self.outlet.cashier.id = response.data.outlet.cashier.entity_id;
            }

            self.amount_eligiblity = response.data.amount_eligiblity;
            self.amount_approver = response.data.amount_approver;
            // console.log(response.data.outlet.amount_limit);
            self.status = response.data.status;
            self.address = response.data.address;
            //self.lob_outlet = response.data.lob_outlet;
            self.extras = response.data.extras;
            self.lob_outlet = response.data.lob_outlet;
            self.sbu_outlet = response.data.sbu_outlet;
            self.sbu = response.data.sbu;
            self.action = response.data.action;
            console.log(response.data.sbu);
            console.log(response.data.lob_outlet);
            console.log(response.data.sbu_outlet);

            if (self.action == 'Edit') {
                // $scope.getSbuBasedonLob(self.outlet.sbu.lob_id);
                $scope.AmountEligible(self.amount_eligiblity);
            }

            $rootScope.loading = false;

        });


        $('.btn-nxt').on("click", function() {
            $('.editDetails-tabs li.active').next().children('a').trigger("click");
            // tabPaneFooter();
        });
        $('.btn-prev').on("click", function() {
            $('.editDetails-tabs li.active').prev().children('a').trigger("click");
            // tabPaneFooter();
        });
        $('.btn-pills').on("click", function() {
            // tabPaneFooter();
        });
        $scope.btnNxt = function() {}
        $scope.prev = function() {}

        //AMOUNT ELIGIBLE SHOW AMOUNT LIMIT AND CHECKED FUNCTION
        $scope.AmountEligible = function(val) {
            if (val == 1) {
                $scope.showAmountLimit = true;
                $scope.checkedAmountEligible = true;
            } else {
                $scope.showAmountLimit = false;
                $scope.checkedAmountEligible = false;
            }
        }





        $scope.loadState = function(country_id) {
            $.ajax({
                    url: get_state_by_country,
                    method: "POST",
                    data: { country_id: country_id },
                })
                .done(function(res) {
                    self.extras.state_list = [];
                    $(res).each(function(i, v) {
                        self.extras.state_list.push({
                            id: v['id'],
                            name: v['name'],
                        });
                    });
                })
                .fail(function(xhr) {
                    console.log(xhr);
                });
        }
        $scope.loadCity = function(state_id) {
            $.ajax({
                    url: get_city_by_state,
                    method: "POST",
                    data: { state_id: state_id },
                })
                .done(function(res) {
                    self.extras.city_list = [];
                    $(res).each(function(i, v) {
                        self.extras.city_list.push({
                            id: v['id'],
                            name: v['name'],
                        });
                    });
                })
                .fail(function(xhr) {
                    console.log(xhr);
                });
        }

        self.searchCashier = function(query) {
            if (query) {
                return new Promise(function(resolve, reject) {
                    $http
                        .post(
                            search_cashier_url, {
                                key: query,
                            }
                        )
                        .then(function(response) {
                            // console.log(response.data);
                            resolve(response.data);
                        });
                    //reject(response);
                });
            } else {
                return [];
            }
        }
        self.searchNodel = function(query) {
            if (query) {
                return new Promise(function(resolve, reject) {
                    $http
                        .post(
                            search_nodel_url, {
                                key: query,
                            }
                        )
                        .then(function(response) {
                            // console.log(response.data);
                            resolve(response.data);
                        });
                    //reject(response);
                });
            } else {
                return [];
            }
        }

        $scope.getSbus = function() {
            var lob_ids = [];
            $.each($(".lobcheckbox:checked"), function() {
                lob_ids.push($(this).val())
            });

            $.ajax({
                    url: get_sbu_by_lob_outlet,
                    method: "GET",
                    data: {
                        lob_ids: lob_ids,
                        outlet_id: self.outlet.id
                    },
                })
                .done(function(res) {
                    console.log(res.sbus);
                    self.sbu_list = [];
                    self.sbu_outlet = res.sbus.sbu_outlet;
                    $scope.$apply()
                })
                .fail(function(xhr) {
                    console.log(xhr);
                });
        }
        // $scope.getDataBasedonLob = function() {
        //     if (event.target.checked == true) {
        //         $http.get(
        //             lob_sbu_url + '/' + id
        //         ).then(function(response) {
        //             // alert(response.data.sbu_outlet)
        //             response.data.sbu.forEach(function(v) {

        //                 self.sbu.push({
        //                     "name": v.name,
        //                     "id": v.id
        //                 });
        //             });
        //         });
        //     } else {
        if ($('.lobcheckbox:checked').length > 0) {
            alert('in');
            self.sbu_list = [];
            $.each($(".lobcheckbox:checked"), function() {
                $scope.test($(this).val())
            });

        } else {
            // $('#lob').prop('checked', false);
            // $('#sbu tbody tr').addClass('ng-hide');

        }
        // }
        // }
        $scope.test = function(id) {
            $http.get(
                lob_sbu_url + '/' + id
            ).then(function(response) {
                response.data.sbu.forEach(function(v) {
                    // alert(v.lob_id)
                    if (id) {
                        self.sbu.push({
                            "name": v.name,
                            "id": v.id
                        });
                    }
                });
            });
        }

        $('.select_all_sbu').on('click', function() {
            if (event.target.checked == true) {
                $('.sbucheckbox').prop('checked', true);
                $.each($('.sbucheckbox:checked'), function() {
                    $scope.getamountonSbu($(this).val());
                    $('.sbu_table tbody tr #outstation_budget_amount' + $(this).val()).removeClass('ng-hide');
                    $('.sbu_table tbody tr #local_budget_amount' + $(this).val()).removeClass('ng-hide');

                    $('.sbu_table tbody tr #outstation_budget_amount' + $(this).val()).addClass('error');
                    $('.sbu_table tbody tr #local_budget_amount' + $(this).val()).addClass('error');
                    $('.sbu_table tbody tr #outstation_budget_amount' + $(this).val()).addClass('required');
                    $('.sbu_table tbody tr #local_budget_amount' + $(this).val()).addClass('required');
                });
            } else {
                $('.sbucheckbox').prop('checked', false);
                $.each($('.sbucheckbox'), function() {
                    $('.sbu_table tbody tr #outstation_budget_amount' + $(this).val()).addClass('ng-hide');
                    $('.sbu_table tbody tr #outstation_budget_amount' + $(this).val()).removeClass('error');
                    $('.sbu_table tbody tr #outstation_budget_amount' + $(this).val()).removeClass('required');
                    $('.sbu_table tbody tr #outstation_budget_amount' + $(this).val()).closest('.form-group').find('label.error').remove();
                    $('.sbu_table tbody tr #outstation_budget_amount' + $(this).val()).val('');

                    $('.sbu_table tbody tr #local_budget_amount' + $(this).val()).addClass('ng-hide');
                    $('.sbu_table tbody tr #local_budget_amount' + $(this).val()).removeClass('error');
                    $('.sbu_table tbody tr #local_budget_amount' + $(this).val()).removeClass('required');
                    $('.sbu_table tbody tr #local_budget_amount' + $(this).val()).closest('.form-group').find('label.error').remove();
                    $('.sbu_table tbody tr #local_budget_amount' + $(this).val()).val('');
                });
            }
        });
        $scope.getamountonSbu = function(id) {
            if (event.target.checked == true) {
                $("#outstation_budget_amount" + id).removeClass('ng-hide');
                $("#outstation_budget_amount" + id).addClass('required');
                $("#outstation_budget_amount" + id).addClass('error');
                $("#local_budget_amount" + id).removeClass('ng-hide');
                $("#local_budget_amount" + id).addClass('required');
                $("#local_budget_amount" + id).addClass('error');
            } else {
                $("#outstation_budget_amount" + id).addClass('ng-hide');
                $("#outstation_budget_amount" + id).removeClass('required');
                $("#outstation_budget_amount" + id).removeClass('error');
                $("#outstation_budget_amount" + id).closest('.form-group').find('label.error').remove();
                $("#outstation_budget_amount" + id).val('');


                $("#local_budget_amount" + id).addClass('ng-hide');
                $("#local_budget_amount" + id).removeClass('required');
                $("#local_budget_amount" + id).removeClass('error');
                $("#local_budget_amount" + id).closest('.form-group').find('label.error').remove();
                $("#local_budget_amount" + id).val('');
            }
        }


        $('#select_all_lob').on('click', function() {
            if (event.target.checked == true) {
                $('.lobcheckbox').prop('checked', true);
                $.each($(".lobcheckbox:checked"), function() {
                    $scope.getSbus($(this).val())
                });
            } else {
                var unselectall = $('.lobcheckbox').prop('checked', false)
                if (unselectall) {
                    $scope.getSbus()
                }
                $('#sbu tbody tr').html('');
            }
        });

        var form_id = '#outlet-form';
        var v = jQuery(form_id).validate({
            errorPlacement: function(error, element) {
                if (element.hasClass("code")) {
                    error.appendTo($('.code_error'));
                } else if (element.hasClass("outlet_name")) {
                    error.appendTo($('.name_error'));
                } else if (element.hasClass("line_1")) {
                    error.appendTo($('.line1_error'));
                } else if (element.hasClass("line_2")) {
                    error.appendTo($('.line2_error'));
                }
                // else if (element.hasClass("country_id")) {
                // error.appendTo($('.country_error'));
                // } 
                // else if (element.hasClass("state_id")) {
                // error.appendTo($('.state_error'));
                // } 
                else if (element.hasClass("city_id")) {
                    error.appendTo($('.city_error'));
                } else if (element.hasClass("pincode")) {
                    error.appendTo($('.pincode_error'));
                } else {
                    error.insertAfter(element)
                }
            },
            invalidHandler: function(event, validator) {
                $noty = new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: 'You have errors,Please check all tabs',
                    animation: {
                        speed: 500 // unavailable - no need
                    },
                }).show();
                setTimeout(function() {
                    $noty.close();
                }, 5000);
            },
            ignore: '',
            rules: {
                'code': {
                    required: true,
                    minlength: 3,
                    maxlength: 191,
                },
                'outlet_name': {
                    required: true,
                    minlength: 3,
                    maxlength: 191,
                },
                'business_id': {
                    required: true,
                },
                'lob_id': {
                    required: true,
                },
                'sbu_id': {
                    required: true,
                },
                'cashier_id': {
                    required: true,
                },
                'nodel_id': {
                    required: true,
                },
                'amount_limit': {
                    required: true,
                    number: true,
                    min: 1,
                },
                'line_1': {
                    required: true,
                    minlength: 3,
                    maxlength: 255,
                },
                'line_2': {
                    minlength: 3,
                    maxlength: 255,
                },
                'country_id': {
                    required: true,
                },
                'state_id': {
                    required: true,
                },
                'city_id': {
                    required: true,
                },
                'pincode': {
                    required: true,
                    number: true,
                    minlength: 6,
                    maxlength: 6,
                    min: 1,
                }
                // 'sbus[]': {
                //     required: true,
                // }
            },
            /* messages: {
                 'code': {
                     required: 'Outlet code is required',
                     minlength: 'Please enter minimum of 3 characters',
                     maxlength: 'Please enter maximum of 191 characters',
                 },
                 'outlet_name': {
                     required: 'Outlet name is required',
                     minlength: 'Please enter minimum of 3 characters',
                     maxlength: 'Please enter maximum of 191 characters',
                 },
                 'lob_id': {
                     required: 'Lob is required',
                 },
                 'sbu_id': {
                     required: 'Sbu is required',
                 },
                 'cashier_id': {
                     required: 'Cashier name is required',
                 },
                 'amount_limit': {
                     required: 'Petty cash threshold is required',
                     number: 'Enter number only',
                 },
                 'line_1': {
                     required: 'Address Line1 is required',
                     minlength: 'Please enter minimum of 3 characters',
                     maxlength: 'Please enter maximum of 255 characters',
                 },
                 'line_2': {
                     minlength: 'Please enter minimum of 3 characters',
                     maxlength: 'Please enter maximum of 255 characters',
                 },
                 'country_id': {
                     required: 'Country is Required',
                 },
                 'state_id': {
                     required: 'State is Required',
                 },
                 'city_id': {
                     required: 'City is Required',
                 },
                 'pincode': {
                     required: 'PinCode is Required',
                     number: 'Enter numbers only',
                     minlength: 'Please enter minimum of 6 numbers',
                     maxlength: 'Please enter maximum of 6 numbers',
                 },
                 'sbus[]': {
                     required: 'Sbu is Required',
                 }
             },*/
            submitHandler: function(form) {
                var sub_business_check = $('.sbucheckbox:checked').length;
                var business_check = $('.lobcheckbox:checked').length;
                if (business_check > 0) {
                    if (sub_business_check == 0) {
                        custom_noty('error', 'Kindly select atleast one Sub Business!');
                        return;
                    }
                }
                let formData = new FormData($(form_id)[0]);
                $('#submit').button('loading');
                $.ajax({
                        url: laravel_routes['saveEYatraOutlet'],
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
                                text: 'Outlet saved successfully',
                                text: res.message,
                                animation: {
                                    speed: 500 // unavailable - no need
                                },
                            }).show();
                            setTimeout(function() {
                                $noty.close();
                            }, 5000);
                            $location.path('/outlets')
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

app.component('eyatraOutletView', {
    templateUrl: outlet_view_template_url,

    controller: function($http, $location, $routeParams, HelperService, $scope, $rootScope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        $http.get(
            outlet_view_url + '/' + $routeParams.outlet_id
        ).then(function(response) {
            self.outlet = response.data.outlet;
            console.log(response.data.outlet);
            self.lob_name = response.data.lob_name;
            self.sbu_name = response.data.sbu_name;
            self.outstation_budget_amount = response.data.outstation_budget_amount;
            self.local_budget_amount = response.data.local_budget_amount;
            self.action = response.data.action;
            if (self.outlet.claim_req_approver == 0) {
                self.claim_req_approver = 'Financier';
            } else {
                self.claim_req_approver = 'Cashier';
            }
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