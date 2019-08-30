// app.component('eyatraRoleList', {
//     templateUrl: eyatra_roles_list_template_url,
//     controller: function(HelperService, $rootScope, $scope, $http) {
//         var self = this;
//         self.hasPermission = HelperService.hasPermission;
//         var dataTable = $('#list_table').DataTable({
//             'ordering': false,
//             processing: true,
//             serverSide: true,
//             ajax: {
//                 url: laravel_routes['EyatragetRolesList'],
//                 data: function(d) {}
//             },

//             columns: [
//                 { data: 'action', searchable: false },
//                 { data: 'role', name: 'roles.display_name', searchable: true },
//                 { data: 'description', searchable: false },
//                 { data: 'status', name: 'status', searchable: false },
//             ]
//         });
//         setTimeout(function() {
//             $('select').not('.not_style').select2();
//         }, 100);
//         $scope.deleteConfirm = function($id) {
//             //return confirm('Are You sure ');
//             $http.get(
//                 delete_role_url + '/' + $id,
//             ).then(function(response) {
//                 if (response.data.success) {
//                     new Noty({
//                         type: 'success',
//                         layout: 'topRight',
//                         text: 'Role Deleted Successfully',
//                     }).show();
//                     dataTable.ajax.reload(function(json) {});
//                 }
//             });
//         }
//         $rootScope.loading = false;
//     }
// });
// //------------------------------------------------------------------------------------------------------------------------
// //------------------------------------------------------------------------------------------------------------------------


// app.component('eyatraRoleForm', {
//     templateUrl: eyatra_roles_form_template_url,
//     controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope) {
//         get_form_data_url = typeof($routeParams.id) == 'undefined' ? get_role_form_data_url : get_role_form_data_url + '/' + $routeParams.id;
//         var self = this;
//         self.hasPermission = HelperService.hasPermission;
//         self.angular_routes = angular_routes;
//         $http.get(
//             get_form_data_url
//         ).then(function(response) {
//             console.log(response);
//             self.role = response.data.role;
//             self.company_list = response.data.company_list;
//             self.role_image = response.data.role;
//             self.selected_permissions = response.data.selected_permissions;
//             selected_permissions = response.data.selected_permissions;
//             self.parent_permission_group_list = response.data.parent_permission_group_list;
//             self.permission_list = response.data.permission_list;
//             self.permission_sub_list = response.data.permission_sub_list;
//             $rootScope.loading = false;
//         });


//         var form_id = '#form';
//         var v = jQuery(form_id).validate({
//             ignore: '',
//             rules: {

//                 'display_name': {
//                     required: true,
//                     minlength: 3,
//                     maxlength: 191,
//                 },
//                 'permission_id': {
//                     required: true,
//                 },
//             },
//             errorPlacement: function(error, element) {
//                 if (element.hasClass("parent_check")) {
//                     error.appendTo($('.permission_errors'));
//                 } else {
//                     error.insertAfter(element)
//                 }
//             },
//             submitHandler: function(form) {
//                 let formData = new FormData($(form_id)[0]);
//                 $('#submit').button('loading');
//                 $.ajax({
//                         url: laravel_routes['EyatrasaveRolesAngular'],
//                         method: "POST",
//                         data: formData,
//                         processData: false,
//                         contentType: false,
//                     })
//                     .done(function(res) {
//                         // console.log(res.success);
//                         if (!res.success) {
//                             $('#submit').button('reset');
//                             var errors = '';
//                             for (var i in res.errors) {
//                                 errors += '<li>' + res.errors[i] + '</li>';
//                             }
//                             new Noty({
//                                 type: 'error',
//                                 layout: 'topRight',
//                                 text: errors
//                             }).show();

//                         } else {
//                             $location.path('/eyatra/roles/list/')
//                             new Noty({
//                                 type: 'success',
//                                 layout: 'topRight',
//                                 text: 'Role saved Successfully',
//                             }).show();
//                             $scope.$apply()
//                         }
//                     })
//                     .fail(function(xhr) {
//                         $('#submit').button('reset');
//                         new Noty({
//                             type: 'error',
//                             layout: 'topRight',
//                             text: 'Something went wrong at server',
//                         }).show();
//                     });
//             },
//         });

//         $scope.myFunction = function(id) {
//             $scope["show_grand_child_" + id] = $scope["show_grand_child_" + id] ? false : true;
//             /*$scope.selectMe = function (event){
//                $(event.target).addClass('active');
//             }*/
//             if ($scope["show_grand_child_" + id] == true) {
//                 $($scope["show_grand_child_" + id]).removeClass('fa-plus');
//                 $($scope["show_grand_child_" + id]).addClass('fa-minus');
//             } else {
//                 $($scope["show_grand_child_" + id].target).addClass('fa-plus');
//                 $($scope["show_grand_child_" + id].target).removeClass('fa-minus');
//             }
//         }
//         $scope.showChild = function(id) {
//             $scope["show_child_" + id] = $scope["show_child_" + id] ? false : true;
//             /*var icon = $(this).find('i').hasClass('fa-plus');
//             if(icon)
//             {
//                 alert();
//                 $(this).find('i').addClass('fa-minus');
//                 $(this).find('i').removeClass('fa-plus');
//             }else
//             {
//                 $(this).find('i').addClass('fa-plus');
//                 $(this).find('i').removeClass('fa-minus');
//             }*/
//         }

//         $scope.valueChecked = function(id) {
//             var value = selected_permissions.indexOf(id);
//             return value;
//         }


//         $(document).on("click", ".parent_checkbox", function() {
//             var id = $(this).data('id');
//             var c = $(this).attr('checked');
//             if ($(this).prop("checked") == true) {
//                 $('.sub_childs_' + id).prop('checked', 'checked');
//             } else if ($(this).prop("checked") == false) {
//                 $('.sub_childs_' + id).prop('checked', '');
//             }
//         });

//         $(document).on("click", ".sub_parent", function() {
//             var id = $(this).data('id');
//             var c = $(this).attr('checked');
//             if ($(this).prop("checked") == true) {
//                 $('.sub_parent_childs_' + id).prop('checked', 'checked');
//             } else if ($(this).prop("checked") == false) {
//                 $('.sub_parent_childs_' + id).prop('checked', '');
//             }
//         });
//         $('.permission_check_class').change(function() {
//             var parent_count = 0;
//             $(this).parents('li').find('.permission_check_class').each(function() {
//                 if ($(this).is(":checked")) {
//                     console.log(' == parent count checked ===');
//                     parent_count = 1;
//                 }
//             });

//             if (parent_count == 0) {
//                 console.log(' == parent count 0 ===');
//                 $(this).parents('li').find('.parent_check').prop('checked', false);
//             }
//         });


//         $scope.selectChilds = function(id) {

//             /* var value = selected_permissions.indexOf(id);
//              console.log(value);
//              return value;*/
//         }
//     }

// });


app.component('eyatraRoleList', {
    templateUrl: eyatra_roles_list_template_url,
    controller: function($scope, $http, HelperService) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        var dataTable = $('#list_table').DataTable({
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
                url: laravel_routes['EyatragetRolesList'],
                data: function(d) {}
            },
            columns: [
                { data: 'role', name: 'roles.display_name', searchable: true },
                { data: 'description', searchable: false },
                { data: 'status', name: 'status', searchable: false },
                { data: 'action', searchable: false, class: 'action' },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();
        $('.page-header-content .display-inline-block .data-table-title').html('Role');
        $('.add_new_button').html(
            '<a href="#!/eyatra/master/roles/add" type="button" class="btn btn-secondary" dusk="add-btn">' +
            'Add Role' +
            '</a>'
        );

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
                new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: response.data.error,
                }).show();
                $location.path('/eyatra/roles/list/')
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
                'permission_id': {
                    required: true,
                },
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
                            new Noty({
                                type: 'error',
                                layout: 'topRight',
                                text: errors
                            }).show();

                        } else {
                            $location.path('/eyatra/roles/list/')
                            new Noty({
                                type: 'success',
                                layout: 'topRight',
                                text: 'Role saved Successfully',
                            }).show();
                            $scope.$apply()
                            location.reload();
                        }
                    })
                    .fail(function(xhr) {
                        $('#submit').button('reset');
                        new Noty({
                            type: 'error',
                            layout: 'topRight',
                            text: 'Something went wrong at server',
                        }).show();
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


        $(document).on("click", ".parent_checkbox", function() {
            var id = $(this).data('id');
            var c = $(this).attr('checked');
            parent_id = id.split('_');
            if ($(this).prop("checked") == true) {
                $('.sub_childs_' + parent_id[1]).prop('checked', 'checked');
                $('.sub_childs_test_' + parent_id[1]).prop('checked', 'checked');

            } else if ($(this).prop("checked") == false) {
                $('.sub_childs_' + parent_id[1]).prop('checked', '');
                $('.sub_childs_test_' + parent_id[1]).prop('checked', '');

            }
        });

        $(document).on("click", ".sub_parent", function() {
            var id = $(this).data('id');
            var c = $(this).attr('checked');
            if ($(this).prop("checked") == true) {
                $('.sub_parent_childs_' + id).prop('checked', 'checked');
                $('.sub_parent_childs2_' + id).prop('checked', 'checked');
            } else if ($(this).prop("checked") == false) {
                $('.sub_parent_childs_' + id).prop('checked', '');
                $('.sub_parent_childs2_' + id).prop('checked', '');
            }
        });


        $(document).on("click", ".check_its_child", function() {
            var id = $(this).data('item');
            var c = $(this).attr('checked');
            if ($(this).prop("checked") == true) {
                $('.childs2_' + id).prop('checked', 'checked');
            } else if ($(this).prop("checked") == false) {
                $('.childs2_' + id).prop('checked', '');
            }
        });

        $(document).on("change", ".permission_check_class", function() {
            var parent_count = 0;
            $(this).parents('li').find('.permission_check_class').each(function() {
                if ($(this).is(":checked")) {
                    // console.log(' == parent count checked ===');
                    parent_count = 1;
                }
            });

            if (parent_count == 0) {
                // console.log(' == parent count 0 ===');
                $(this).parents('li').find('.parent_check').prop('checked', false);
            } else {
                $(this).parents('li').find('.parent_check').prop('checked', true);
            }
        });

        $(document).on("change", ".sub_child", function() {

            ids = $(this).data("id");
            id = ids.split("_");
            var sub_parent_count = 0;
            if ($(this).is(":checked")) {

                $('.pc_' + id[1]).prop('checked', true);
                $('.sc_' + id[0]).prop('checked', true);
                $('.childs2_' + id[2]).prop('checked', true);
            } else {
                var countCheckedCheckboxes = 0;
                $(this).parents('li').find('.sub_child').each(function() {
                    countCheckedCheckboxes = $(this).parents('li').find('.sub_parent_childs_' + id[0]).filter(':checked').length;
                });

                if (countCheckedCheckboxes == 0) {

                    var subCheckedCheckboxes = 0;
                    $('.sc_' + id[0]).prop('checked', false);
                    $('.childs2_' + id[2]).prop('checked', false);
                    $(this).parents('li').find('.permission_check_class').each(function() {

                        subCheckedCheckboxes = $(this).parents('li').find('.sub_test_' + id[1]).filter(':checked').length;
                        subCheckedcount = $(this).parents('li').find('.sc_' + id[0]).filter(':checked').length;

                    });


                }

            }


        });

        $(document).on("change", ".super_sub_child", function() {

            ids = $(this).data("id");
            id = ids.split("_");
            // console.log(ids);
            if ($(this).is(":checked")) {
                $('.pc_' + id[1]).prop('checked', true);
                $('.sc_' + id[0]).prop('checked', true);
                $('.child_' + id[2]).prop('checked', true);
            } else {
                var countCheckedCheckboxes1 = 0;
                $(this).parents('li').find('.super_sub_child').each(function() {
                    countCheckedCheckboxes1 = $(this).parents('li').find('.childs2_' + id[2]).filter(':checked').length;
                });
                if (countCheckedCheckboxes1 == 0) {
                    $('.child_' + id[2]).prop('checked', false);
                }

                var countCheckedCheckboxes2 = 0;
                $(this).parents('li').find('.check_its_child').each(function() {
                    countCheckedCheckboxes2 = $(this).parents('li').find('.sub_parent_childs_' + id[0]).filter(':checked').length;
                });
                if (countCheckedCheckboxes2 == 0) {
                    $('.sc_' + id[0]).prop('checked', false);
                }

                var countCheckedCheckboxes3 = 0;



                //  $(this).parents('li').find('.permission_check_class').each(function() {
                //      countCheckedCheckboxes3 = $(this).parents('li').find('.sc_' + id[0]).filter(':checked').length;
                // });
                //  if(countCheckedCheckboxes3 == 0)
                //  {
                //     $('.pc_' + id[1]).prop('checked', false);
                //  }


                // console.log(countCheckedCheckboxes2);


                // var countCheckedCheckboxes1 = 0;
                // $(this).parents('li').find('.super_sub_child').each(function() {
                //      countCheckedCheckboxes1 = $(this).parents('li').find('.childs2_' + id[2]).filter(':checked').length;
                // });

                // if (countCheckedCheckboxes1 == 0) {

                //     var subCheckedCheckboxes1 = 0;

                //     $('.child_' + id[2]).prop('checked', false);

                //     $(this).parents('li').find('.check_its_child').each(function() {


                //     subCheckedCheckboxes1 = $(this).parents('li').find('.sub_childs_test_' + id[1]).filter(':checked').length;
                //     // if(subCheckedCheckboxes1){
                //     //     subCheckedCheckboxes1 = subCheckedCheckboxes1;
                //     // }else{
                //     //     subCheckedCheckboxes1 =1;
                //     // }
                // });
                //      console.log("super_sub"+subCheckedCheckboxes1);
                //   // console.log("super_sub"+subCheckedCheckboxes1);
                //     if(subCheckedCheckboxes1 == 0){
                //         // alert("suppp");

                //         // $('.pc_' + id[1]).prop('checked', false);
                //         $('.sc_' + id[0]).prop('checked', false);
                //     }

                // } 
            }

        });

        $scope.selectChilds = function(id) {

            // var value = selected_permissions.indexOf(id);
            // console.log(value);
            // return value;
        }
    }
});

//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------