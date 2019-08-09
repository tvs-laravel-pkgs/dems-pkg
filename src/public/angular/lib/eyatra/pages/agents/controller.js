app.component('eyatraAgents', {
    templateUrl: eyatra_agent_list_template_url,
    controller: function(HelperService, $rootScope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        var dataTable = $('#agent_list').DataTable({
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
                url: laravel_routes['listEYatraAgent'],
                type: "GET",
                dataType: "json",
                data: function(d) {}
            },

            columns: [
                { data: 'action', searchable: false, class: 'action' },
                { data: 'code', name: 'agents.code', searchable: true },
                { data: 'name', name: 'agents.name', searchable: true },
                { data: 'mobile_number', name: 'users.mobile_number', searchable: true },
                { data: 'travel_name', name: 'entity_types.name', searchable: true },
                { data: 'status', name: 'status', searchable: false },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();
        $('.page-header-content .display-inline-block .data-table-title').html('Agents');
        $('.add_new_button').html(
            '<a href="#!/eyatra/agent/add" type="button" class="btn btn-secondary" ng-show="$ctrl.hasPermission(\'add-agent\')">' +
            'Add New' +
            '</a>'
        );
        $rootScope.loading = false;

    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('eyatraAgentForm', {
    templateUrl: agent_form_template_url,
    controller: function($http, $location, HelperService, $routeParams, $rootScope, $scope) {
        $form_data_url = typeof($routeParams.agent_id) == 'undefined' ? agent_form_data_url : agent_form_data_url + '/' + $routeParams.agent_id;
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
                $location.path('/eyatra/agents')
                $scope.$apply()
                return;
            }
            self.agent = response.data.agent;
            self.address = response.data.address;
            self.user = response.data.user;
            self.extras = response.data.extras;
            travel_list = response.data.travel_list;
            self.action = response.data.action;
            console.log(travel_list);
            if (self.action == 'Edit') {
                //$("#hide_password").hide();
                if (self.agent.deleted_at == null) {
                    self.switch_value = 'Active';
                } else {
                    self.switch_value = 'Inactive';
                }
                if (self.user.force_password_change == 1) {
                    self.switch_password = 'No';
                    $("#hide_password").hide();
                } else {
                    self.switch_password = 'Yes';
                }
                if (self.user.force_password_change == 1) {
                    self.switch_password = 'No';
                    $("#hide_password").hide();
                } else {
                    self.switch_password = 'Yes';
                }
            } else {
                self.switch_value = 'Active';
                $("#hide_password").show();
            }
        });
        $scope.travelChecked = function(id) {
            var value = travel_list.indexOf(id);
            return value;
        }

        $scope.psw_change = function(val) {
            if (val == 'No') {
                $("#hide_password").hide();
                $("#password_change").val('');
            } else {
                $("#hide_password").show();
            }
        }

        $('.btn-nxt').on("click", function() {
            $('.editDetails-tabs li.active').next().children('a').trigger("click");
            tabPaneFooter();
        });
        $('.btn-prev').on("click", function() {
            $('.editDetails-tabs li.active').prev().children('a').trigger("click");
            tabPaneFooter();
        });
        $('.btn-pills').on("click", function() {
            tabPaneFooter();
        });
        $scope.btnNxt = function() {}
        $scope.prev = function() {}

        $scope.getDataBasedonCountry = function(country_id) {
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
        $scope.getDataBasedonState = function(state_id) {
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


        var form_id = '#agent-form';
        var v = jQuery(form_id).validate({
            errorPlacement: function(error, element) {
                if (element.attr('name') == 'travel_mode[]') {
                    error.appendTo($('.travel_mode_error'));
                } else {
                    error.insertAfter(element)
                }
            },
            ignore: '',
            rules: {
                'agent_code': {
                    required: true,
                    minlength: 3,
                    maxlength: 191,
                },
                'agent_name': {
                    required: true,
                    minlength: 3,
                    maxlength: 191,
                },
                'address_line1': {
                    required: true,
                    minlength: 3,
                    maxlength: 255,
                },
                'address_line2': {
                    minlength: 3,
                    maxlength: 255,
                },
                'country': {
                    required: true,
                },
                'state': {
                    required: true,
                },
                'city': {
                    required: true,
                },
                'pincode': {
                    required: true,
                    minlength: 6,
                    maxlength: 6,
                    number: true,
                },
                'mobile_number': {
                    required: true,
                    minlength: 8,
                    maxlength: 10,
                },
                'email': {
                    email: true,
                    minlength: 6,
                    maxlength: 191,
                },
                'username': {
                    required: true,
                    minlength: 4,
                    maxlength: 191,
                },
                'password': {
                    required: function(element) {
                        if ($("#password_change").val() == 'Yes') {
                            return true;
                        } else {
                            return false;
                        }
                    },
                    minlength: 5,
                    maxlength: 16,
                },
                'travel_mode[]': {
                    required: true,
                }
            },
            messages: {
                'agent_code': {
                    required: 'Agent code is required',
                    minlength: 'Please enter minimum of 3 letters',
                    maxlength: 'Please enter maximum of 191 letters',
                },
                'agent_name': {
                    required: 'Agent name is required',
                    minlength: 'Please enter minimum of 3 letters',
                    maxlength: 'Please enter maximum of 191 letters',
                },
                'address_line1': {
                    required: 'Address Line1 is required',
                    minlength: 'Please enter minimum of 3 letters',
                    maxlength: 'Please enter maximum of 255 letters',
                },
                'address_line2': {
                    minlength: 'Please enter minimum of 3 letters',
                    maxlength: 'Please enter maximum of 255 letters',
                },
                'country': {
                    required: 'Country is Required',
                },
                'state': {
                    required: 'State is Required',
                },
                'city': {
                    required: 'City is Required',
                },
                'pincode': {
                    required: 'PinCode is Required',
                    minlength: 'Please enter minimum of 6 numbers',
                    maxlength: 'Please enter maximum of 6 letters',
                    numbers: 'Enter numbers only',
                },
                'mobile_number': {
                    required: 'Enter Mobile Number',
                    minlength: 'Please enter minimum of 8 numbers',
                    maxlength: 'Please enter maximum of 10 letters',
                },
                'email': {
                    email: 'Enter Valid Email',
                    minlength: 'Please enter minimum of 6 numbers',
                    maxlength: 'Please enter maximum of 191 letters',
                },
                'username': {
                    required: 'Username is Required',
                    minlength: 'Please enter minimum of 4 numbers',
                    maxlength: 'Please enter maximum of 191 letters',
                },
                'password': {
                    required: 'Password is required',
                    minlength: 'Please enter minimum of 5 numbers',
                    maxlength: 'Please enter maximum of 16 letters',
                },
                'travel_mode[]': {
                    required: 'Travel mode is Required',
                }
            },
            invalidHandler: function(event, validator) {
                new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: 'Please check in each tab and fix errors!'
                }).show();
            },
            submitHandler: function(form) {

                let formData = new FormData($(form_id)[0]);
                $('#submit').button('loading');
                $.ajax({
                        url: laravel_routes['saveEYatraAgent'],
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
                                text: res.message,
                            }).show();
                            $location.path('/eyatra/agents')
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
app.component('eyatraAgentView', {
    templateUrl: agent_view_template_url,
    controller: function($http, $location, $routeParams, HelperService, $rootScope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        alert(agent_view_url + '/' + $routeParams.agent_id);
        $http.get(
            agent_view_url + '/' + $routeParams.agent_id
        ).then(function(response) {
            self.agent = response.data.agent;
            console.log(self.agent);

        });
        $rootScope.loading = false;
    }
});


//------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------