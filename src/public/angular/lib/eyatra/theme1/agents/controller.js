app.component('eyatraAgents', {
    templateUrl: eyatra_agent_list_template_url,
    controller: function(HelperService, $rootScope, $scope, $http, $location) {
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
                { data: 'name', name: 'users.name', searchable: true },
                { data: 'mobile_number', name: 'users.mobile_number', searchable: true },
                { data: 'travel_name', name: 'tm.name', searchable: true },
                { data: 'status', name: 'status', searchable: false },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();
        $('.page-header-content .display-inline-block .data-table-title').html('Agents');
        $('.add_new_button').html(
            '<a href="#!/agent/add" type="button" class="btn btn-secondary" ng-show="$ctrl.hasPermission(\'add-agent\')">' +
            'Add New' +
            '</a>'
        );
        $scope.deleteAgentConfirm = function($id) {
            $("#deleteAgent_id").val($id);
        }

        $scope.deleteAgent = function() {
            var id = $("#deleteAgent_id").val();
            $http.get(
                agent_delete_url + '/' + id,
            ).then(function(response) {
                if (response.data.success) {
                    $noty = new Noty({
                        type: 'success',
                        layout: 'topRight',
                        text: 'Agent Deleted Successfully',
                        animation: {
                            speed: 500 // unavailable - no need
                        },
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 1000);
                    $('#agent_list').DataTable().ajax.reload(function(json) {});
                    $location.path('/eyatra/agents');
                    // $scope.$apply();
                } else {
                    $noty = new Noty({
                        type: 'error',
                        layout: 'topRight',
                        text: 'Agent not Deleted',
                        animation: {
                            speed: 500 // unavailable - no need
                        },
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 1000);
                }
            });
        }
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
                }, 1000);
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
            // console.log(self.user.id);
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
                    $("#password").prop('disabled', true);
                } else {
                    self.switch_password = 'Yes';
                }
                $scope.selectPaymentMode(self.employee.payment_mode_id);
            } else {
                self.switch_value = 'Active';
                $("#hide_password").show();
                /*setTimeout(function() {
    $noty.close();
}, 1000);*/
                $("#password").prop('disabled', false);
                self.switch_password = 'Yes';
            }
        });
        $scope.travelChecked = function(id) {
            var value = travel_list.indexOf(id);
            return value;
        }

        $("#travel_mode").on('click', function() {
            if (event.target.checked == true) {
                $('.travelcheckbox').prop('checked', true);
            } else {
                $('.travelcheckbox').prop('checked', false);
            }
        });

        //SELECT PAYMENT MODE
        $scope.selectPaymentMode = function(payment_id) {
            if (payment_id == 3244) { //BANK
                $scope.showBank = true;
                $scope.showCheque = false;
                $scope.showWallet = false;
            } else if (payment_id == 3245) { //CHEQUE
                $scope.showBank = false;
                $scope.showCheque = true;
                $scope.showWallet = false;
            } else if (payment_id == 3246) { //WALLET
                $scope.showBank = false;
                $scope.showCheque = false;
                $scope.showWallet = true;
            } else {
                $scope.showBank = false;
                $scope.showCheque = false;
                $scope.showWallet = false;
            }
        }

        $scope.psw_change = function(val) {
            if (val == 'No') {
                $("#hide_password").hide();
                $("#password").prop('disabled', true);
            } else {
                $("#hide_password").show();
                setTimeout(function() {
                    $noty.close();
                }, 1000);
                $("#password").prop('disabled', false);
            }
        }

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
                'gstin': {
                    minlength: 3,
                    maxlength: 20,
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
                },
                'bank_name': {
                    required: true,
                    maxlength: 100,
                    minlength: 3,
                },
                'branch_name': {
                    required: true,
                    maxlength: 50,
                    minlength: 3,
                },
                'account_number': {
                    required: true,
                    maxlength: 20,
                    minlength: 3,
                    positiveNumber: true,
                },
                'ifsc_code': {
                    required: true,
                    maxlength: 10,
                    minlength: 3,
                },
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
                'gstin': {
                    minlength: 'Enter Minimum 3 Characters!',
                    maxlength: 'Enter Maximum 20 Characters!',
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
                }, 1000);
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
                            $noty = new Noty({
                                type: 'success',
                                layout: 'topRight',
                                text: res.message,
                                animation: {
                                    speed: 500 // unavailable - no need
                                },
                            }).show();
                            setTimeout(function() {
                                $noty.close();
                            }, 1000);
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
        $http.get(
            agent_view_url + '/' + $routeParams.agent_id
        ).then(function(response) {
            self.agent = response.data.agent;
            self.agent_address = response.data.address;
            self.user_details = response.data.user_details;
            self.travel_modes = response.data.travel_list;
            if (self.agent.deleted_at == null) {
                self.status = 'Active';
            } else {
                self.status = 'Inactive';
            }
            self.action = "View ";

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