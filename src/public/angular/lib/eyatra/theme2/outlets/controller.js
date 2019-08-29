app.component('eyatraOutlets', {
    templateUrl: eyatra_outlet_list_template_url,
    controller: function(HelperService, $rootScope, $http, $scope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;

        $http.get(
            outlet_filter_data_url
        ).then(function(response) {
            console.log(response.data);
            self.region_list = response.data.region_list;
            self.city_list = response.data.city_list;
            self.state_list = response.data.state_list;
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
                }
            },

            columns: [
                { data: 'action', searchable: false, class: 'action', class: 'text-left' },
                { data: 'code', name: 'outlets.code', searchable: true },
                { data: 'name', name: 'outlets.name', searchable: true },
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

        /*$('.separate-page-header-content .data-table-title').html('<p class="breadcrumb">Masters / Outlets</p><h3 class="title">Outlets</h3>');
        // $('.page-header-content .display-inline-block .data-table-title').html('Outlets');
        $('.add_new_button').html(
            '<a href="#!/eyatra/outlet/add" type="button" class="btn btn-secondary" ng-show="$ctrl.hasPermission(\'add-outlet\')">' +
            'Add New' +
            '</a>'
        );*/
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
                console.log(response.data);
                if (response.data.success) {
                    new Noty({
                        type: 'success',
                        layout: 'topRight',
                        text: 'Outlet Deleted Successfully',
                    }).show();
                    dataTable.ajax.reload(function(json) {});

                } else {
                    new Noty({
                        type: 'error',
                        layout: 'topRight',
                        text: 'Outlet not Deleted',
                    }).show();
                }
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

        $http.get(
            $form_data_url
        ).then(function(response) {
            if (!response.data.success) {
                new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: response.data.error,
                }).show();
                $location.path('/eyatra/outlets')
                $scope.$apply()
                return;
            }
            self.outlet = response.data.outlet;
            self.status = response.data.status;
            self.address = response.data.address;
            self.extras = response.data.extras;
            self.lob_outlet = response.data.lob_outlet;
            self.sbu_outlet = response.data.sbu_outlet;
            self.action = response.data.action;

            if (self.action == 'Edit') {
                $scope.getSbuBasedonLob(self.outlet.sbu.lob_id);
                $scope.AmountEligible(self.outlet.amount_eligible);
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

        $scope.getSbus = function() {
            var lob_ids = [];
            $.each($(".lobcheckbox:checked"), function() {
                lob_ids.push($(this).val())
            });
            $.ajax({
                    url: get_sbu_by_lob_outlet,
                    method: "GET",
                    data: { lob_ids: lob_ids },
                })
                .done(function(res) {
                    self.extras.sbu_list = [];
                    self.extras.sbu_list = res.sbus;

                    console.log(self.extras.sbu_list);
                    $scope.$apply()
                })
                .fail(function(xhr) {
                    console.log(xhr);
                });
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
                            console.log(response.data);
                            resolve(response.data);
                        });
                    //reject(response);
                });
            } else {
                return [];
            }
        }

        $scope.getDataBasedonLob = function() {
            if (event.target.checked == true) {
                $http.get(
                    lob_sbu_url + '/' + id
                ).then(function(response) {
                    // alert(response.data.sbu_outlet)
                    response.data.sbu_outlet.forEach(function(v) {

                        self.sbu_outlet.push({
                            "name": v.name,
                            "id": v.id
                        });
                    });
                });
            } else {
                if ($('.lobcheckbox:checked').length > 0) {
                    self.sbu_outlet = [];
                    $.each($(".lobcheckbox:checked"), function() {
                        $scope.test($(this).val())
                    });

                } else {
                    $('#lob').prop('checked', false);
                    $('#sbu tbody tr').html('');
                }
            }
        }
        $scope.test = function(id) {
            $http.get(
                lob_sbu_url + '/' + id
            ).then(function(response) {
                response.data.sbu_outlet.forEach(function(v) {
                    // alert(v.lob_id)
                    if (id) {
                        self.sbu_outlet.push({
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
                // $('#budget_table').css('display', 'block');
                $.each($('.sbucheckbox:checked'), function() {
                    $scope.getamountonSbu($(this).val());
                    $('.sbu_table tbody tr #amount' + $(this).val()).removeClass('ng-hide');
                });
            } else {
                $('.sbucheckbox').prop('checked', false);
                $.each($('.sbucheckbox'), function() {
                    $('.sbu_table tbody tr #amount' + $(this).val()).addClass('ng-hide');
                });
            }
        });
        $scope.getamountonSbu = function(id) {
            if (event.target.checked == true) {
                $("#amount" + id).removeClass('ng-hide');
            } else {
                $("#amount" + id).addClass('ng-hide');
                // $("#dms_" + id).val('');
            }
        }


        // $('#select_all_sbu').prop('disabled', 'disabled');
        $('#select_all_lob').on('click', function() {
            if (event.target.checked == true) {
                $('#sbu tbody tr').html('');
                $('.lobcheckbox').prop('checked', true);
                $('#select_all_sbu').prop('checked', true);
                $('.sbucheckbox').prop('checked', true);
                $.each($(".lobcheckbox:checked"), function() {
                    // $('.sbucheckbox').prop('checked', true);
                    $scope.getDataBasedonLob($(this).val())
                });
            } else {
                $('.lobcheckbox').prop('checked', false);
                $('.sbucheckbox').prop('checked', false);
                $('#select_all_sbu').prop('checked', false);
                $('#sbu tbody tr').html('');
            }
        });

        // $('#select_all_sbu').on('click', function() {
        //     if (event.target.checked == true) {
        //         $('.sbucheckbox').prop('checked', true);
        //     } else {
        //         $('.sbucheckbox').prop('checked', false);
        //     }
        // });




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
                new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: 'You have errors,Please check all tabs'
                }).show();
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
                'lob_id': {
                    required: true,
                },
                'sbu_id': {
                    required: true,
                },
                'cashier_id': {
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
                },
                'sbus[]': {
                    required: true,
                }
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
                        console.log(res.success);
                        if (!res.success) {
                            $('#submit').button('reset');
                            var errors = '';
                            for (var i in res.errors) {
                                errors += '<li>' + res.errors[i] + '</li>';
                            }
                            custom_noty('error', errors);
                        } else {
                            new Noty({
                                type: 'success',
                                layout: 'topRight',
                                text: 'Outlet saved successfully',
                                text: res.message,
                            }).show();
                            $location.path('/eyatra/outlets')
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
            self.lob_name = response.data.lob_name;
            self.sbu_name = response.data.sbu_name;
            self.amount = response.data.amount;
            self.action = response.data.action;
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