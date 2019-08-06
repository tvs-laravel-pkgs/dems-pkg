app.component('eyatraGrades', {
    templateUrl: eyatra_grade_list_template_url,
    controller: function(HelperService, $rootScope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        var dataTable = $('#eyatra_grade_table').DataTable({
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
                url: laravel_routes['listEYatraGrade'],
                type: "GET",
                dataType: "json",
                data: function(d) {}
            },

            columns: [
                { data: 'action', searchable: false, class: 'action' },
                { data: 'grade_name', name: 'entities.name', searchable: false },
                { data: 'expense_count', searchable: false },
                { data: 'travel_count', searchable: true },
                { data: 'trip_count', searchable: true },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();
        $('.page-header-content .display-inline-block .data-table-title').html('Grades List');
        $('.add_new_button').html(
            '<a href="#!/eyatra/grade/add" type="button" class="btn btn-secondary" ng-show="$ctrl.hasPermission(\'add-trip\')">' +
            'Add New' +
            '</a>'
        );
        $rootScope.loading = false;

    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('eyatraGradeForm', {
    templateUrl: grade_form_template_url,
    controller: function($http, $location, $location, HelperService, $routeParams, $rootScope, $scope) {
        $form_data_url = typeof($routeParams.grade_id) == 'undefined' ? grade_form_data_url : grade_form_data_url + '/' + $routeParams.grade_id;
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
                $location.path('/eyatra/grades')
                $scope.$apply()
                return;
            }
            self.entity = response.data.entity;
            self.extras = response.data.extras;
            self.expense_type = response.data.extras.expense_type;
            self.selected_expense_types = response.data.selected_expense_types;
            self.selected_travel_types = response.data.selected_purposeList;
            self.selected_travel_modes = response.data.selected_localTravelModes;
            self.purpose_list = response.data.extras.purpose_list;
            self.travel_mode_list = response.data.extras.travel_mode_list;
            self.action = response.data.action;
            $rootScope.loading = false;
            selected_expense_types = response.data.selected_expense_types;
            selected_travel_types = response.data.selected_purposeList;
            selected_travel_modes = response.data.selected_localTravelModes;
            self.title = "New Grade";
            if (self.action == 'Edit') {
                self.title = "Edit Grade";
                load_checked_values(selected_expense_types, selected_travel_types, selected_travel_modes);
            }
        });

        $('.btn-nxt').on("click", function() {
            $('.editDetails-tabs li.active').next().children('a').trigger("click");
        });
        $('.btn-prev').on("click", function() {
            $('.editDetails-tabs li.active').prev().children('a').trigger("click");
        });

        function load_checked_values(selected_expense_types, selected_travel_types, selected_travel_modes) {
            $scope.valueChecked = function(id) {
                var value = selected_expense_types.indexOf(id);
                return value;
            }

            $scope.travel_purpose_checked = function(id) {
                var value = selected_travel_types.indexOf(id);
                return value;
            }

            $scope.local_travel = function(id) {
                var value = selected_travel_modes.indexOf(id);
                return value;
            }
        }


        $scope.getexpense_type = function(id) {
            if (event.target.checked == true) {
                // $(".outlet_code_" + id).prop('disabled', false);
                $("#outlet_code_" + id).removeAttr("disabled");
                // $("#outlet_code_" + id).attr('disabled', false);
                $("#outlet_code_" + id).prop('readonly', false);
                $("#outlet_code_" + id).prop('required', true);
            } else {

                $("#outlet_code_" + id).attr("disabled", "disabled");
                // $("#outlet_code_" + id).prop('disabled', true);
                // $("#outlet_code_" + id).attr('disabled', true);
                // $(".outlet_code_" + id).prop('disabled', true);
                $("#outlet_code_" + id).prop('readonly', true);
                $("#outlet_code_" + id).prop('required', false);
            }
        }


        $('#select_all_expense').on('click', function() {
            if (event.target.checked == true) {
                $('.expense_sub_check').prop('checked', true);
                $('.expense_sub_check').prop('required', true);
                $.each($('.expense_sub_check:checked'), function() {
                    $scope.getexpense_type($(this).val());
                });
            } else {
                $('.expense_sub_check').prop('checked', false);
                $('.expense_sub_check').prop('required', false);
                $.each($('.expense_sub_check'), function() {
                    $scope.getexpense_type($(this).val());
                });
            }
        });

        $('#travel_purpose').on('click', function() {
            if (event.target.checked == true) {
                $('.travel_purpose_sub_check').prop('checked', true);
                $.each($('.travel_purpose_sub_check:checked'), function() {
                    $scope.getexpense_type($(this).val());
                });
            } else {
                $('.travel_purpose_sub_check').prop('checked', false);
                $.each($('.travel_purpose_sub_check'), function() {
                    $scope.getexpense_type($(this).val());
                });
            }
        });


        $('#local_travel').on('click', function() {
            if (event.target.checked == true) {
                $('.local_travel_sub_check').prop('checked', true);
                $.each($('.local_travel_sub_check:checked'), function() {
                    $scope.getexpense_type($(this).val());
                });
            } else {
                $('.local_travel_sub_check').prop('checked', false);
                $.each($('.local_travel_sub_check'), function() {
                    $scope.getexpense_type($(this).val());
                });
            }
        });


        var form_id = '#grade-form';
        var v = jQuery(form_id).validate({
            errorPlacement: function(error, element) {
                error.insertAfter(element)
            },
            ignore: '',
            rules: {
                'grade_name': {
                    required: true,
                },
                'selected_expense_amounts[]': {
                    required: true,
                    number: true,
                },

            },
            messages: {
                'grade_name': {
                    required: 'Please enter Grade Name',
                },
                'selected_expense_amounts': {
                    required: 'Please enter Valid Amount',
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
                            new Noty({
                                type: 'success',
                                layout: 'topRight',
                                text: 'Grade saved successfully',
                            }).show();
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
            self.grade = response.data.grade;
        });
    }
});


//------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------