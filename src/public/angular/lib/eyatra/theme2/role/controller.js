app.component('eyatraRoleList', {
    templateUrl: eyatra_roles_list_template_url,
    controller: function($scope, $http, HelperService) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.permission = self.hasPermission('eyatra-role-add');
        var dataTable = $('#list_table').DataTable({
            "dom": dom_structure_separate,
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
                url: laravel_routes['EyatragetRolesList'],
                data: function(d) {}
            },
            columns: [
                { data: 'action', searchable: false, class: 'action' },
                { data: 'role', name: 'roles.display_name', searchable: true },
                { data: 'description', searchable: false },
                { data: 'status', name: 'status', searchable: false },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.on_focus').focus();
        $('.dataTables_length select').select2();
        $('.separate-page-header-content .data-table-title').html('<p class="breadcrumb">Masters / Roles</p><h3 class="title">Roles</h3>');
        if (self.permission == true) {
            $('.add_new_button').html(
                '<a href="#!/roles/add" type="button" class="btn btn-secondary">' +
                'Add New' +
                '</a>'
            );
        }
    }
});

app.component('eyatraRoleForm', {
    templateUrl: eyatra_roles_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope) {
        get_form_data_url = typeof($routeParams.id) == 'undefined' ? get_roles_form_data_url : get_roles_form_data_url + '/' + $routeParams.id;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        $http.get(
            get_form_data_url
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
                $location.path('/roles/list/')
                $scope.$apply()
                return;
            }
            // console.log(response.data.role.deleted_at);
            self.role = response.data.role;
            self.action = response.data.action;
            self.company_list = response.data.company_list;
            self.role_image = response.data.role;
            self.selected_permissions = response.data.selected_permissions;
            selected_permissions = response.data.selected_permissions;
            self.parent_permission_group_list = response.data.parent_permission_group_list;
            self.permission_list = response.data.permission_list;
            self.permission_sub_list = response.data.permission_sub_list;
            self.permission_sub_child_list = response.data.permission_sub_child_list;
            // console.log(self.permission_sub_child_list);
            if (response.data.role.deleted_at == null) {

                self.switch_value = 'Active';


            } else {
                self.switch_value = 'Inactive';
            }


            self.cb2 = 'Active';
            $rootScope.loading = false;
        });
        var form_id = '#form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {

                'display_name': {
                    required: true,
                    minlength: 3,
                    maxlength: 191,
                },
                'permission_ids[]': {
                    required: true,
                },
                'description': {
                    required: true,
                    minlength: 3,
                    maxlength: 191,
                },
            },
            messages: {
                'permission_ids[]': "Select atleast one permission",

            },
            errorPlacement: function(error, element) {
                if (element.hasClass("parent_check")) {
                    error.appendTo($('.permission_errors'));
                } else {
                    error.insertAfter(element)
                }
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('#submit').button('loading');
                $.ajax({
                        url: laravel_routes['EyatrasaveRolesAngular'],
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
                            $location.path('/roles/list/')
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
                            $scope.$apply()
                            location.reload();
                        }
                    })
                    .fail(function(xhr) {
                        $('#submit').button('reset');
                        $noty = new Noty({
                            type: 'error',
                            layout: 'topRight',
                            text: 'Something went wrong at server',
                            animation: {
                                speed: 500 // unavailable - no need
                            },
                        }).show();
                        setTimeout(function() {
                            $noty.close();
                        }, 5000);
                    });
            },
        });
        $scope.myFunction1 = function(id) {
            $scope["show_grand_child2_" + id] = $scope["show_grand_child2_" + id] ? false : true;

            if ($scope["show_grand_child2_" + id] == true) {
                $($scope["show_grand_child2_" + id]).removeClass('fa-plus');
                $($scope["show_grand_child2_" + id]).addClass('fa-minus');
            } else {
                $($scope["show_grand_child2_" + id].target).addClass('fa-plus');
                $($scope["show_grand_child2_" + id].target).removeClass('fa-minus');
            }
        }
        $scope.myFunction = function(id) {



            $scope["show_grand_child_" + id] = $scope["show_grand_child_" + id] ? false : true;
            /*$scope.selectMe = function (event){
               $(event.target).addClass('active');
            }*/
            if ($scope["show_grand_child_" + id] == true) {
                $($scope["show_grand_child_" + id]).removeClass('fa-plus');
                $($scope["show_grand_child_" + id]).addClass('fa-minus');
            } else {
                $($scope["show_grand_child_" + id].target).addClass('fa-plus');
                $($scope["show_grand_child_" + id].target).removeClass('fa-minus');
            }
        }
        $scope.showChild = function(id) {
            $scope["show_child_" + id] = $scope["show_child_" + id] ? false : true;

        }

        $scope.valueChecked = function(id) {
            var value = selected_permissions.indexOf(id);
            return value;
        }

        //try 
        // Sub child on click event check its parent

        $(document).on('change', '.sub_child', function() {

            ids = $(this).data("id");
            id = ids.split("_");

            var its_value = $(this).val();
            if ($(this).is(":checked")) {
                $(this).parents('li').find('.childs2_' + its_value).prop('checked', true);
            } else {
                $(this).parents('li').find('.childs2_' + its_value).prop('checked', false);
            }

            var sub_cheked_count = 0;
            $(this).parents('li').find('.sub_child').each(function() {
                sub_cheked_count =
                    $('.sub_parent_childs_' + id[0]).filter(':checked').length;
            });

            if (sub_cheked_count > 0) {
                $(this).parents('li').find('.sc_' + id[0]).prop('checked', true);
                $(this).parents('li').find('.pc_' + id[1]).prop('checked', true);

            } else {

                $(this).parents('li').find('.sc_' + id[0]).prop('checked', false);
                var cross_check_parent = 0;

                $('.main_parent_' + id[1]).each(function() {
                    cross_check_parent =
                        $('.main_parent_' + id[1]).filter(':checked').length;

                });

                if (cross_check_parent > 0) {
                    $(this).parents('li').find('.pc_' + id[0]).prop('checked', true);
                } else {
                    $(this).parents('li').find('.pc_' + id[1]).prop('checked', false);
                }
            }

        });

        //on click parent check its child 

        $(document).on('change', '.permission_check_class', function() {
            var parent_value = $(this).val();
            var cross_check_parent = 0;
            ids = $(this).data("id");
            id = ids.split("_");

            if ($(this).prop("checked") == true) {

                $('.sub_parent_childs_' + id[0]).prop('checked', true);
                $('.sub_parent_childs2_' + id[0]).prop('checked', true);
                $('.pc_' + id[1]).prop('checked', true);
            } else if ($(this).prop("checked") == false) {

                $('.sub_parent_childs_' + id[0]).prop('checked', false);
                $('.sub_parent_childs2_' + id[0]).prop('checked', false);

                $('.main_parent_' + id[1]).each(function() {
                    cross_check_parent =
                        $('.main_parent_' + id[1]).filter(':checked').length;

                });
                if (cross_check_parent > 0) {
                    $(this).parents('li').find('.pc_' + id[1]).prop('checked', true);
                } else {
                    $(this).parents('li').find('.pc_' + id[1]).prop('checked', false);
                }
            }

        });


        //On click super sub child check its parents

        $(document).on('change', '.super_sub_child', function() {
            ids = $(this).attr("data-id");
            id = ids.split("_");
            if ($(this).is(":checked")) {
                $(this).parents('li').find('.child_' + id[2]).prop('checked', true);
                $(this).parents('li').find('.sc_' + id[0]).prop('checked', true);
                $(this).parents('li').find('.pc_' + id[1]).prop('checked', true);
            } else {
                var super_sub_child_count = 0;
                $(this).parents('li').find('.super_sub_child').each(function() {
                    super_sub_child_count =
                        $('.childs2_' + id[2]).filter(':checked').length;
                });

                if (super_sub_child_count > 0) {

                    $(this).parents('li').find('.child_' + id[2]).prop('checked', true);
                } else {

                    $(this).parents('li').find('.child_' + id[2]).prop('checked', false);
                }

                var total_sub_child_count = 0;
                $(this).parents('li').find('.sub_parent_childs_' + id[0]).each(function() {
                    total_sub_child_count =
                        $('.sub_parent_childs_' + id[0]).filter(':checked').length;
                });

                if (total_sub_child_count > 0) {
                    $(this).parents('li').find('.sc' + id[0]).prop('checked', true);
                } else {
                    $(this).parents('li').find('.sc_' + id[0]).prop('checked', false);
                }

                var total_child_count = 0;
                $(this).parents('li').find('.main_parent_' + id[1]).each(function() {
                    total_child_count =
                        $('.main_parent_' + id[1]).filter(':checked').length;
                });

                if (total_child_count > 0) {
                    $(this).parents('li').find('.pc_' + id[1]).prop('checked', true);
                } else {
                    $(this).parents('li').find('.pc_' + id[1]).prop('checked', false);
                }

            }
        });




        // On click main parent check its child,sub child, super sub child

        $(document).on("change", ".parent_check", function() {
            var parent_id = $(this).val();

            if ($(this).prop("checked") == true) {
                $('.main_parent_' + parent_id).prop('checked', true);
                $('.sub_test_' + parent_id).prop('checked', true);
                $('.sub_childs_test_' + parent_id).prop('checked', true);
            } else {
                $('.main_parent_' + parent_id).prop('checked', false);
                $('.sub_test_' + parent_id).prop('checked', false);
                $('.sub_childs_test_' + parent_id).prop('checked', false);
            }
        });

        //End


        $scope.selectChilds = function(id) {

            // var value = selected_permissions.indexOf(id);
            // console.log(value);
            // return value;
        }
    }
});

//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------