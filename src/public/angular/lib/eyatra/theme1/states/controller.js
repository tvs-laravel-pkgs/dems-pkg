app.component('eyatraStates', {
    templateUrl: eyatra_state_list_template_url,
    controller: function(HelperService, $rootScope, $scope, $http) {
        // alert(2)
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        var dataTable = $('#eyatra_state_table').DataTable({
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
                url: laravel_routes['listEYatraState'],
                type: "GET",
                dataType: "json",
                data: function(d) {}
            },
            columns: [
                { data: 'action', searchable: false, class: 'action', class: 'text-left' },
                { data: 'code', name: 'nstates.code', searchable: true },
                { data: 'name', name: 'nstates.name', searchable: true },
                { data: 'country', name: 'c.name', searchable: true },
                { data: 'status', name: 'nstates.deleted_at', searchable: false },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();
        $('.page-header-content .display-inline-block .data-table-title').html('States');
        $('.add_new_button').html(
            '<a href="#!/eyatra/state/add" type="button" class="btn btn-secondary" ng-show="$ctrl.hasPermission(\'add-state\')">' +
            'Add New' +
            '</a>'
        );
        $scope.deleteStateConfirm = function($state_id) {
            $("#delete_state_id").val($state_id);
        }

        $scope.deleteState = function() {
            $state_id = $('#delete_state_id').val();
            $http.get(
                state_delete_url + '/' + $state_id,
            ).then(function(response) {
                console.log(response.data);
                if (response.data.success) {
                    $noty = new Noty({
                        type: 'success',
                        layout: 'topRight',
                        text: 'State Deleted Successfully',
                        animation: {
                            speed: 500 // unavailable - no need
                        },
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 1000);
                    dataTable.ajax.reload(function(json) {});

                } else {
                    $noty = new Noty({
                        type: 'error',
                        layout: 'topRight',
                        text: 'State not Deleted',
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

app.component('eyatraStateForm', {
    templateUrl: state_form_template_url,
    controller: function($http, $location, $location, HelperService, $routeParams, $rootScope, $scope) {
        $form_data_url = typeof($routeParams.state_id) == 'undefined' ? state_form_data_url : state_form_data_url + '/' + $routeParams.state_id;
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
                $location.path('/eyatra/states')
                $scope.$apply()
                return;
            }

            self.state = response.data.state;
            self.country_list = response.data.country_list;
            self.status = response.data.status;
            self.travel_mode_list = response.data.travel_mode_list;
            self.agents_list = response.data.agents_list;
            self.state.agent = response.data.agent;
            self.action = response.data.action;

        });

        // $('#travel_mode').on('click', function() {
        //     if (event.target.checked == true) {
        //         $('.travelmodecheckbox').prop('checked', true);

        //         $.each($('.travelmodecheckbox:checked'), function() {
        //             $scope.getTravelMode($(this).val());
        //         });

        //     } else {
        //         $('.travelmodecheckbox').prop('checked', false);
        //         $.each($('.travelmodecheckbox'), function() {
        //             $scope.getTravelMode($(this).val());
        //         });
        //         //     if ($('.travelmodecheckbox').is(":checked")) {
        //         //         $('#sc_' + id).show();
        //         //         $('.agent_select').show();
        //         //     } else {
        //         //         $('#sc_' + id).hide();
        //         //         $('.agent_select').hide();
        //         //     }
        //     }
        // });

        // $scope.getTravelMode = function(id) {
        //     if (event.target.checked == true) {
        //         $("#sc_" + id).removeClass("ng-hide");
        //         $("#sc_" + id).prop('required', true);
        //         $("#sc_" + id).prop('min', 1);
        //         $(".agent_select").removeClass("ng-hide");
        //         $(".agent_select").prop('required', true);
        //         //alert('fsdghgf');
        //     } else {
        //         $("#sc_" + id).addClass("ng-hide");
        //         $("#sc_" + id).prop('required', false);
        //         $(".agent_select").addClass("ng-hide");
        //         $(".agent_select").prop('required', false);
        //     }

        // }

        // $(document).on('click', '#submit', function() {
        //     //     var id = $(this).val();
        //     //     if ($(this).prop("checked") == true) {
        //     $(".sc_").prop('required', true);
        //     $(".sc_").prop('number', true);
        //     $(".sc_").prop('min', 1);
        //     //         $(".agent_select_" + id).prop('required', true);
        //     // }

        // });

        // $('#travel_mode').on('click', function() {
        //     if (event.target.checked == true) {
        //         $('.travelmodecheckbox').prop('checked', true);
        //         $.each($('.travelmodecheckbox:checked'), function() {
        //             $scope.getTravelMode($(this).val());
        //             $('.state_agent_travel_mode_table tbody tr #sc_' + $(this).val()).removeClass('ng-hide');
        //             $('.agent_select').removeClass('ng-hide');
        //         });
        //     } else {
        //         $('.travelmodecheckbox').prop('checked', false);
        //         $.each($('.travelmodecheckbox'), function() {
        //             $('.state_agent_travel_mode_table tbody tr #sc_' + $(this).val()).addClass('ng-hide');
        //             $('.agent_select').addClass('ng-hide');
        //         });
        //     }
        // });
        // $scope.getTravelMode = function(id) {
        //     if (event.target.checked == true) {
        //         $('#sc_' + id).removeClass("ng-hide");
        //         $('#agent_select_' + id).removeClass("ng-hide");
        //     } else {
        //         $('#sc_' + id).addClass("ng-hide");
        //         $('#agent_select_' + id).addClass("ng-hide");
        //     }
        // }
        $('.btn-nxt').on("click", function() {
            $('.editDetails-tabs li.active').next().children('a').trigger("click");
        });
        $('.btn-prev').on("click", function() {
            $('.editDetails-tabs li.active').prev().children('a').trigger("click");
        });
        $('.btn-pills').on("click", function() {

        });
        $scope.btnNxt = function() {}
        $scope.prev = function() {}

        var form_id = '#state-form';
        var v = jQuery(form_id).validate({
            errorPlacement: function(error, element) {
                if (element.hasClass("code")) {
                    error.appendTo($('.code_error'));
                } else if (element.hasClass("name")) {
                    error.appendTo($('.name_error'));
                }
                // else if (element.attr('name') == 'travel_modes[]') {
                //     error.appendTo($('.travel_mode_error'));
                // }
                // else if (element.attr('name') == 'travel_modes[{{travel_mode.id}}][agent_id]') {
                //     error.appendTo($('.agent_error'));
                // }
                // else if (element.hasClass('travel_modes[{{travel_mode.id}}][agent_id]')) {
                //     error.appendTo($('.agent_error'));
                // } 
                else {
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
                }, 1000);
            },
            ignore: '',
            rules: {
                'code': {
                    required: true,
                    minlength: 2,
                    maxlength: 2,
                },
                'name': {
                    required: true,
                    minlength: 3,
                    maxlength: 191,
                },
                'country_id': {
                    required: true,
                },
                // 'service_charge[]': {
                //     required: true,
                //     number: true,
                //     min: 1,
                // },
                // 'travel_modes[{{travel_mode.id}}][agent_id]': {
                //     required: true,
                // },
                // 'travel_modes[{{travel_mode.id}}][service_charge]': {
                //     required: true,
                //     number: true,
                //     min: 1,
                // },

            },
            messages: {
                'code': {
                    minlength: 'Please enter minimum of 2 letters',
                    maxlength: 'Please enter maximum of 2 letters',
                },
                'name': {
                    minlength: 'Please enter minimum of 3 letters',
                    maxlength: 'Please enter maximum of 191 letters',
                },
                // 'travel_modes[]': {
                //     required: 'Travel mode required',
                // }
            },
            submitHandler: function(form) {

                let formData = new FormData($(form_id)[0]);
                $('#submit').button('loading');
                $.ajax({
                        url: laravel_routes['saveEYatraState'],
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
                                text: 'State saved successfully',
                                text: res.message,
                                animation: {
                                    speed: 500 // unavailable - no need
                                },
                            }).show();
                            setTimeout(function() {
                                $noty.close();
                            }, 1000);
                            $location.path('/eyatra/states')
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

app.component('eyatraStateView', {
    templateUrl: state_view_template_url,

    controller: function($http, $location, $routeParams, HelperService, $scope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        $http.get(
            state_view_url + '/' + $routeParams.state_id
        ).then(function(response) {
            self.state = response.data.state;
            self.travel_mode_name = response.data.travel_mode_name;
            self.agents = response.data.agents;
            self.service_charge = response.data.service_charge;
            self.action = response.data.action;
        });
    }
});


//------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------