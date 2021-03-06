app.component('eyatraAgents', {
    templateUrl: eyatra_agent_list_template_url,
    controller: function(HelperService, $rootScope, $scope, $http, $location) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.permission = self.hasPermission('eyatra-agent-add');
        var dataTable = $('#agent_list').DataTable({
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
                url: laravel_routes['listEYatraAgent'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    // d.agent = $('#agent_id').val();
                    d.tm = $('#tm_id').val();
                    d.status_id = $('#status').val();
                }
            },

            columns: [
                { data: 'action', searchable: false, class: 'action', class: 'text-left' },
                { data: 'code', name: 'agents.code', searchable: true },
                { data: 'name', name: 'users.name', searchable: true },
                { data: 'gstin', name: 'agents.gstin', searchable: true },
                { data: 'mobile_number', name: 'users.mobile_number', searchable: true },
                { data: 'travel_name', name: 'tm.name', searchable: true },
                { data: 'status', name: 'status', searchable: false },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();
        setTimeout(function() {
            var x = $('.separate-page-header-inner.search .custom-filter').position();
            var d = document.getElementById('agent_list_filter');
            x.left = x.left + 15;
            d.style.left = x.left + 'px';
        }, 500);
        $('#agent_list_filter').find('input').addClass("on_focus");
        $('.on_focus').focus();
        $http.get(
            agents_filter_url
        ).then(function(response) {
            // console.log(response.data);
            self.agent_list = response.data.agent_list;
            self.tm_list = response.data.tm_list;
            self.status_list = response.data.status_list;
            // $rootScope.loading = false;
        });
        var dataTableFilter = $('#agent_list').dataTable();

        // $scope.onselectAgent = function(id) {
        //     //alert(query);
        //     $('#agent_id').val(id);
        //     dataTableFilter.fnFilter();
        // }
        $scope.onselectTravelMode = function(id) {
            $('#tm_id').val(id);
            dataTableFilter.fnFilter();
        }
        $scope.onselectStatus = function(id) {
            $('#status').val(id);
            dataTableFilter.fnFilter();
        }

        $scope.reset_filter = function() {
            // $('#agent_id').val(null);
            $('#tm_id').val(null);
            $('#status').val(null);
            dataTableFilter.fnFilter();
        }
        // $('.separate-page-header-content .data-table-title').html('<p class="breadcrumb">Masters / Agents</p><h3 class="title">Agents</h3>');
        // $('.add_new_button').html()
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
                    }, 5000);
                    $('#agent_list').DataTable().ajax.reload(function(json) {});
                    $location.path('/agents');
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
                    }, 5000);
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
                $location.path('/agents');
                $scope.$apply();
                return;
            }
            self.agent = response.data.agent;
            self.address = response.data.address;

            self.user = response.data.user;
            self.extras = response.data.extras;
            travel_list = response.data.travel_list;
            self.action = response.data.action;

            if (response.data.agent.payment_mode_id == null || !response.data.agent.payment_mode_id) {
                self.agent.payment_mode_id = 3244;
            }
            // console.log(self.user.id);
            if (self.action == 'Edit') {
                //$("#hide_password").hide();
                if (self.agent.deleted_at == null) {
                    self.switch_value = 'Active';
                } else {
                    self.switch_value = 'Inactive';
                }

                self.switch_password = 'No';
                $("#hide_password").hide();

                /*if (self.user.force_password_change == 1) {
                    self.switch_password = 'Yes';
                    $("#hide_password").show();
                    $("#password").prop('disabled', false);
                } else {
                    self.switch_password = 'No';
                    $("#hide_password").hide();
                }*/

                //$scope.selectPaymentMode(self.agent.payment_mode_id);

                if ($('.travelcheckbox:checked').length == $('.travelcheckbox').length) {
                    $('#travel_mode').prop('checked', true);
                }

            } else {
                self.switch_value = 'Active';
                $("#hide_password").show();
                $("#password").prop('disabled', false);
                self.switch_password = 'Yes';
            }
        });
        $('.btn-nxt').on("click", function() {
            $('.editDetails-tabs li.active').next().children('a').trigger("click");
        });
        $('.btn-prev').on("click", function() {
            $('.editDetails-tabs li.active').prev().children('a').trigger("click");
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

        $(document).on('click', '.travelcheckbox', function() {
            var cheked_count = 0;
            $.each($('.travelcheckbox'), function() {
                cheked_count = $('.travelcheckbox:checked').length;
            });
            if (cheked_count > 0) {
                $('#travel_mode').prop('checked', true);
            } else {
                $('#travel_mode').prop('checked', false);
            }

        });
        //SELECT PAYMENT MODE
        // $scope.selectPaymentMode = function(payment_id) {
        //     if (payment_id == 3244) { //BANK
        //         $scope.showBank = true;
        //         $scope.showCheque = false;
        //         $scope.showWallet = false;
        //     } else if (payment_id == 3245) { //CHEQUE
        //         $scope.showBank = false;
        //         $scope.showCheque = true;
        //         $scope.showWallet = false;
        //     } else if (payment_id == 3246) { //WALLET
        //         $scope.showBank = false;
        //         $scope.showCheque = false;
        //         $scope.showWallet = true;
        //     } else {
        //         $scope.showBank = false;
        //         $scope.showCheque = false;
        //         $scope.showWallet = false;
        //     }
        // }

        $scope.psw_change = function(val) {
            if (val == 'No') {
                $("#hide_password").hide();
                $("#password").prop('disabled', true);
            } else {
                $("#hide_password").show();
                $("#password").prop('disabled', false);
            }
        }

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

        $.validator.addMethod('positiveNumber',
            function(value) {
                return Number(value) > 0;
            }, 'Enter a positive number.');


        $.validator.addMethod("pwcheck", function(value) {
            if (value == '') return true;
            return /^[A-Za-z0-9\d=!\-@._*]*$/.test(value) // consists of only these
                &&
                /[a-z]/.test(value) // has a lowercase letter
                &&
                /[A-Z]/.test(value) // has a uppercase letter
                &&
                /[=!\-@._*]/.test(value) // has a uppercase letter
                &&
                /\d/.test(value) // has a digit
        }, 'Use strong password with atleast one uppercase and digit and special symbol');

        var form_id = '#agent-form';
        var v = jQuery(form_id).validate({
            errorPlacement: function(error, element) {
                if (element.attr('name') == 'travel_mode[]') {
                    error.appendTo($('.travel_mode_error'));
                } else if (element.hasClass('agent_password_check')) {
                    error.appendTo($('.password_error'));
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
                    maxlength: 255,
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
                    number: true,
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
                    'pwcheck': true,

                    minlength: 5,
                    maxlength: 16,
                },
                'travel_mode[]': {
                    required: true,
                },
                'bank_name': {
                    required: function(element) {
                        if ($("#bank").is(':checked')) {
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
                        if ($("#bank").is(':checked')) {
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
                        if ($("#bank").is(':checked')) {
                            return true;
                        } else {
                            return false;
                        }
                    },
                    maxlength: 20,
                    minlength: 3,
                    min: 1,
                    number: true,
                    // positiveNumber: true,
                },

                'ifsc_code': {
                    required: function(element) {
                        if ($("#bank").is(':checked')) {
                            return true;
                        } else {
                            return false;
                        }
                    },
                    maxlength: 15,
                    minlength: 3,
                },
                'check_favour': {
                    required: function(element) {
                        if ($("#cheque").is(':checked')) {
                            return true;
                        } else {
                            return false;
                        }
                    },
                    maxlength: 100,
                    minlength: 3,
                },
                'type_id': {
                    required: function(element) {
                        if ($("#wallet").is(':checked')) {
                            return true;
                        } else {
                            return false;
                        }
                    },
                },
                'value': {
                    required: function(element) {
                        if ($("#wallet").is(':checked')) {
                            return true;
                        } else {
                            return false;
                        }
                    },
                    maxlength: 20,
                    minlength: 3,
                },
            },
            messages: {
                'agent_code': {
                    required: 'This field is required',
                    minlength: 'Please enter minimum of 3 letters',
                    maxlength: 'Please enter maximum of 191 letters',
                },
                'agent_name': {
                    minlength: 'Please enter minimum of 3 letters',
                    maxlength: 'Please enter maximum of 255 letters',
                },
                'gstin': {
                    minlength: 'Enter Minimum 3 Characters!',
                    maxlength: 'Enter Maximum 20 Characters!',
                },
                'address_line1': {
                    minlength: 'Please enter minimum of 3 letters',
                    maxlength: 'Please enter maximum of 255 letters',
                },
                'address_line2': {
                    minlength: 'Please enter minimum of 3 letters',
                    maxlength: 'Please enter maximum of 255 letters',
                },

                'pincode': {
                    minlength: 'Please enter minimum of 6 numbers',
                    maxlength: 'Please enter maximum of 6 letters',
                    numbers: 'Enter numbers only',
                },
                'mobile_number': {
                    minlength: 'Please enter minimum of 8 numbers',
                    maxlength: 'Please enter maximum of 10 letters',
                },
                'email': {
                    minlength: 'Please enter minimum of 6 numbers',
                    maxlength: 'Please enter maximum of 191 letters',
                },
                'username': {
                    minlength: 'Please enter minimum of 4 numbers',
                    maxlength: 'Please enter maximum of 191 letters',
                },
                'password': {
                    minlength: 'Please enter minimum of 5 numbers',
                    maxlength: 'Please enter maximum of 16 letters',
                },

            },
            invalidHandler: function(event, validator) {
                $noty = new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: 'You have errors,Please check all tabs'
                }).show();
                setTimeout(function() {
                    $noty.close();
                }, 5000);
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
                            }, 5000);
                            $location.path('/agents');
                            $scope.$apply();
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
    controller: function($http, $location, $routeParams, HelperService, $rootScope, $scope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        $http.get(
            agent_view_url + '/' + $routeParams.agent_id
        ).then(function(response) {
            self.agent = response.data.agent;
            self.agent_address = response.data.address;
            self.user_details = response.data.user_details;
            self.travel_modes = response.data.travel_list;
            $scope.selectPaymentMode(self.agent.payment_mode_id);
            if (self.agent.deleted_at == null) {
                self.status = 'Active';
            } else {
                self.status = 'Inactive';
            }
            self.action = "View ";

        });

        //SELECT PAYMENT MODE
        $scope.selectPaymentMode = function(payment_id) {
            if (payment_id == 3244) { //BANK
                $scope.showBank = true;
                $scope.showCheque = false;
                $scope.showWallet = false;
                self.payment = 'BANK';
            } else if (payment_id == 3245) { //CHEQUE
                $scope.showBank = false;
                $scope.showCheque = true;
                $scope.showWallet = false;
                self.payment = 'CHEQUE';
            } else if (payment_id == 3246) { //WALLET
                $scope.showBank = false;
                $scope.showCheque = false;
                $scope.showWallet = true;
                self.payment = 'WALLET';
            } else {
                $scope.showBank = false;
                $scope.showCheque = false;
                $scope.showWallet = false;
            }
        }

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