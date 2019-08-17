app.component('eyatraOutlets', {
    templateUrl: eyatra_outlet_list_template_url,
    controller: function(HelperService, $rootScope, $http, $scope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        var dataTable = $('#eyatra_outlet_table').DataTable({
            stateSave: true,
            "dom": dom_structure,
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
                data: function(d) {}
            },

            columns: [
                { data: 'action', searchable: false, class: 'action', class: 'text-left' },
                { data: 'code', name: 'outlets.code', searchable: true },
                { data: 'name', name: 'outlets.name', searchable: true },
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
        $('.page-header-content .display-inline-block .data-table-title').html('Outlets');
        $('.add_new_button').html(
            '<a href="#!/eyatra/outlet/add" type="button" class="btn btn-secondary" ng-show="$ctrl.hasPermission(\'add-outlet\')">' +
            'Add New' +
            '</a>'
        );
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
            self.action = response.data.action;
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

        $scope.getSbuBasedonLob = function(lob_id) {
            if (lob_id) {
                $.ajax({
                        url: get_sbu_by_lob_outlet,
                        method: "POST",
                        data: { lob_id: lob_id },
                    })
                    .done(function(res) {
                        self.extras.sbu_list = [];
                        self.extras.sbu_list = res.sbu_list;
                        $scope.$apply()
                    })
                    .fail(function(xhr) {
                        console.log(xhr);
                    });
            }
        }

        // $scope.loadCity = function(state_id) {
        //     $.ajax({
        //             url: get_city_url,
        //             method: "POST",
        //             data: { state_id: state_id },
        //         })
        //         .done(function(res) {
        //             self.city_list = [];
        //             $(res['city_list']).each(function(i, v) {
        //                 self.city_list.push({
        //                     id: v['id'],
        //                     name: v['name'],
        //                 });
        //             });
        //         })
        //         .fail(function(xhr) {
        //             console.log(xhr);
        //         });
        // }

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

            },
            messages: {
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
                }

            },
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

    controller: function($http, $location, $routeParams, HelperService, $scope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        $http.get(
            outlet_view_url + '/' + $routeParams.outlet_id
        ).then(function(response) {
            self.outlet = response.data.outlet;
            self.action = response.data.action;
        });
    }
});


//------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------