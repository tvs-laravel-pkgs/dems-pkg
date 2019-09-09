app.component('eyatraGrades', {
    templateUrl: eyatra_grade_list_template_url,
    controller: function($http, HelperService, $rootScope, $scope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        var dataTable = $('#eyatra_grade_table').DataTable({
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
                url: laravel_routes['listEYatraGrade'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.advanced_eligibility = $('#adv_eligibility_id').val();
                    d.status = $('#status').val();
                }
            },

            columns: [
                { data: 'action', searchable: false, class: 'action', class: 'text-left' },
                { data: 'grade_name', name: 'entities.name', searchable: true },
                { data: 'grade_eligiblity', searchable: false },
                { data: 'expense_count', searchable: false },
                { data: 'travel_count', searchable: false },
                { data: 'local_travel_count', searchable: false },
                { data: 'trip_count', searchable: false },
                { data: 'status', searchable: false },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();
        // $('.separate-page-header-content .data-table-title').html('<p class="breadcrumb">Masters / Grades</p><h3 class="title">Grades</h3>');
        // $('.add_new_button').html(
        //     '<a href="#!/eyatra/grade/add" type="button" class="btn btn-secondary" ng-show="$ctrl.hasPermission(\'add-grade\')">' +
        //     'Add New' +
        //     '</a>'
        // );

        setTimeout(function() {
            var x = $('.separate-page-header-inner.search .custom-filter').position();
            var d = document.getElementById('eyatra_grade_table_filter');
            x.left = x.left + 15;
            d.style.left = x.left + 'px';
        }, 500);
        $('#eyatra_grade_table_filter').find('input').addClass("on_focus");
        $('.on_focus').focus();
        //Filter
        $http.get(
            grade_filter_url
        ).then(function(response) {
            console.log(response);
            self.advanced_eligibility_list = response.data.advanced_eligibility_list;
            self.status_list = response.data.status_list;
            $rootScope.loading = false;
        });
        var dataTableFilter = $('#eyatra_grade_table').dataTable();
        $scope.onselectAdvEligible = function(id) {
            $('#adv_eligibility_id').val(id);
            dataTableFilter.fnFilter();
        }
        $scope.onselectStatus = function(id) {
            $('#status').val(id);
            dataTableFilter.fnFilter();
        }

        $scope.resetForm = function() {
            $('#adv_eligibility_id').val(null);
            $('#status').val(null);
            dataTableFilter.fnFilter();
        }

        $scope.deleteGrade = function($id) {
            $('#del').val($id);
        }
        $scope.confirmDeleteDiscount = function() {
            //return confirm(‘Are You sure ‘);
            $id = $('#del').val();
            $http.get(
                grade_delete_url + '/' + $id,
            ).then(function(response) {
                console.log(response.data);
                if (response.data.success) {

                    $noty = new Noty({
                        type: 'success',
                        layout: 'topRight',
                        text: 'Grade Deleted Successfully',
                        animation: {
                            speed: 500 // unavailable - no need
                        },
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 5000);
                } else {
                    $noty = new Noty({
                        type: 'error',
                        layout: 'topRight',
                        text: 'Grade not Deleted',
                        animation: {
                            speed: 500 // unavailable - no need
                        },
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 5000);
                }
                $('#delete_grade').modal('hide');
                dataTable.ajax.reload(function(json) {});
            });
        }
        $rootScope.loading = false;


    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('eyatraGradeForm', {
    templateUrl: grade_form_template_url,
    controller: function($http, $location, $location, HelperService, $routeParams, $rootScope, $scope, $timeout) {
        $form_data_url = typeof($routeParams.grade_id) == 'undefined' ? grade_form_data_url : grade_form_data_url + '/' + $routeParams.grade_id;
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
                $location.path('/eyatra/grades')
                // $scope.$apply()
                return;
            }
            self.entity = response.data.grade;
            self.extras = response.data.extras;
            self.action = response.data.action;
            self.grade_details = response.data.grade_details;
            $rootScope.loading = false;

            if (self.action == 'Edit') {
                if (self.grade_details.advanced_eligibility == 1) {
                    self.grade_advanced_value = 'Yes';
                    // $(".grade_advanced").prop('checked', true);
                } else {
                    self.grade_advanced_value = 'No';
                    // $(".grade_advanced").prop('checked', false);
                }
                if (self.entity.deleted_at == null) {
                    self.switch_value = 'Active';
                } else {
                    self.switch_value = 'Inactive';
                }

                if (self.grade_details.deviation_eligiblity == 1) {
                    self.deviation_eligiblity = 'Yes';
                } else {
                    self.deviation_eligiblity = 'No';
                }

                $timeout(function() {
                    $.each($('.expense_cb:checked'), function() {
                        var id = $(this).val();
                        $(".sub_class_" + id).removeClass("ng-hide");
                        $(".sub_class_" + id).addClass("required");
                        $(".sub_class_" + id).prop('required', true);
                    });
                }, 500);

            } else {
                self.switch_value = 'Active';
                self.grade_advanced_value = 'Yes';
                self.deviation_eligiblity = 'No';
            }
        });

        $('.btn-nxt').on("click", function() {
            $('.editDetails-tabs li.active').next().children('a').trigger("click");
        });
        $('.btn-prev').on("click", function() {
            $('.editDetails-tabs li.active').prev().children('a').trigger("click");
        });



        $('.toggle_cb').on('click', function() {
            var class_name = $(this).data('class');
            if (event.target.checked == true) {
                $('.' + class_name).prop('checked', true);
                if (class_name == 'expense_cb') {
                    $.each($('.' + class_name + ':checked'), function() {
                        $scope.getexpense_type($(this).val());
                    });
                }

            } else {
                $('.' + class_name).prop('checked', false);
                if (class_name == 'expense_cb') {
                    $.each($('.' + class_name), function() {
                        $scope.getexpense_type($(this).val());
                    });

                }
            }
        });

        $scope.getexpense_type = function(id) {
            if (event.target.checked == true) {
                $(".sub_class_" + id).removeClass("ng-hide");
                $(".sub_class_" + id).addClass("required");
                $(".sub_class_" + id).prop('required', true);
                $(".sub_class_" + id).prop("disabled", false);
            } else {
                $(".sub_class_" + id).addClass("ng-hide");
                $(".sub_class_" + id).removeClass("required");
                $(".sub_class_" + id).prop('required', false);
                $(".sub_class_" + id).prop("disabled", true);
                $('.sub_class_' + id).removeClass('error');
                $('.sub_class_' + id).closest('.form-group').find('label.error').remove();
            }
        }

        $(document).on('click', '.expense_cb', function() {
            var id = $(this).val();
            $scope.getexpense_type(id);
        });

        var form_id = '#grade-form';
        var v = jQuery(form_id).validate({
            errorPlacement: function(error, element) {
                error.insertAfter(element)
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
                'grade_name': {
                    required: true,
                    minlength: 2,
                    maxlength: 191,
                },
                'discount_percentage': {
                    required: true,
                    min: 1,
                    max: 100,
                },
            },

            submitHandler: function(form) {

                let formData = new FormData($(form_id)[0]);
                $('#submit').button('loading');
                $.ajax({
                        url: laravel_routes['saveEYatraGrade'],
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
                                text: 'Grade Updated successfully',
                                animation: {
                                    speed: 500 // unavailable - no need
                                },
                            }).show();
                            setTimeout(function() {
                                $noty.close();
                            }, 5000);
                            $location.path('/eyatra/grades')
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

app.component('eyatraGradeView', {
    templateUrl: grade_view_template_url,

    controller: function($http, $location, $routeParams, HelperService, $scope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        $http.get(
            grade_view_url + '/' + $routeParams.grade_id
        ).then(function(response) {
            self.entity = response.data.grade;
            self.extras = response.data.extras;
            self.action = response.data.action;
            self.grade_advanced = response.data.grade_advanced;
            if (response.data.grade.deleted_at == null) {
                self.status = 'Active';
            } else {
                self.status = 'Inactive';
            }
        });
        $('.btn-nxt').on("click", function() {
            $('.editDetails-tabs li.active').next().children('a').trigger("click");
        });
        $('.btn-prev').on("click", function() {
            $('.editDetails-tabs li.active').prev().children('a').trigger("click");
        });
    }
});


//------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------