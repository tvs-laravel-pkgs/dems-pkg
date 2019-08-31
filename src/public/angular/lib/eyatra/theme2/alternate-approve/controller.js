app.component('eyatraAlternateApproveList', {
    templateUrl: eyatra_alternate_approve_list_template_url,
    controller: function(HelperService, $rootScope, $scope, $http) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        var dataTable = $('#alternate_approve_list').DataTable({
            stateSave: true,
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
                url: laravel_routes['listAlternateApproveRequest'],
                type: "GET",
                dataType: "json",
                data: function(d) {}
            },

            columns: [
                { data: 'action', searchable: false, class: 'action' },
                { data: 'empname', name: 'employees.name', searchable: true },
                { data: 'empcode', name: 'employees.code', searchable: true },
                { data: 'altempname', name: 'alternateemp.name', searchable: true },
                { data: 'altempcode', name: 'alternateemp.code', searchable: true },
                { data: 'type', name: 'alternative_approvers.type', searchable: true },
                { data: 'fromdate', name: 'alternative_approvers.from', searchable: false },
                { data: 'todate', name: 'alternative_approvers.to', searchable: true },
                { data: 'status', name: 'status', searchable: false },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();
        $('.separate-page-header-content .data-table-title').html('<p class="breadcrumb">Master / Notification / List</p><h3 class="title">Alternate Approve List</h3>');
        $('.add_new_button').html(
            '<a href="#!/eyatra/alternate-approve/add" type="button" class="btn btn-blue" ng-show="$ctrl.hasPermission(\' \')">' +
            'Add New' +
            '</a>'
        );
        $scope.deleteAlternateapprove = function(id) {
            $('#deletealterapprove_id').val(id);
        }
        $scope.confirmDeleteAlternateapprove = function() {
            var id = $('#deletealterapprove_id').val();
            $http.get(
                alternate_approve_cash_delete_url + '/' + id,
            ).then(function(res) {
                if (!res.data.success) {
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
                    new Noty({
                        type: 'success',
                        layout: 'topRight',
                        text: 'Alternate Approve Deleted Successfully',
                    }).show();
                    dataTable.ajax.reload(function(json) {});
                }
            });
        }
        $rootScope.loading = false;
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('eyatraAlternateApproveForm', {
    templateUrl: alternate_approve_form_template_url,
    controller: function($http, $location, $location, HelperService, $routeParams, $rootScope, $scope) {
        $form_data_url = typeof($routeParams.alternate_id) == 'undefined' ? alternate_approve_form_data_url : alternate_approve_form_data_url + '/' + $routeParams.alternate_id;
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
                $location.path('/eyatra/alternate-approve')
                return;
            }

            self.alternate_approve = response.data.alternate_approve;
            console.log(response.data.alternate_approve);
            console.log(response.data.alternate_approve.alternate_employee_id);
            console.log(response.data.alternate_approve.employee_id);
            // console.log(response.data.alternate_approve);
            /*self.selectedItem = self.alternate_approve.emp_name;
            self.selectedItem1 = self.alternate_approve.alt_emp_name;
            $(".employee_id").val(self.alternate_approve.employee_id);
            $(".alt_employee_id").val(self.alternate_approve.alternate_employee_id);*/
            self.extras = response.data.extras;
            if ($routeParams.alternate_id != undefined) {
                self.date = self.alternate_approve.from + ' to ' + self.alternate_approve.to;
                $('.daterange').daterangepicker({
                    autoUpdateInput: false,
                     startDate: self.alternate_approve.from,
                     endDate: self.alternate_approve.to,
                     "opens": "left", 
                    locale: {
                        cancelLabel: 'Clear',
                        format: "DD-MM-YYYY",
                    }
                });
                    $('.daterange').on('apply.daterangepicker', function(ev, picker) {
                        $(this).val(picker.startDate.format('DD-MM-YYYY') + ' to ' + picker.endDate.format('DD-MM-YYYY'));
                    });
                    $('.daterange').on('cancel.daterangepicker', function(ev, picker) {
                        $(this).val('');
                    });
            } else {
                self.date = '';
            }

        });
        $('.daterange').daterangepicker({
            autoUpdateInput: false,
            /* "opens": "right", */
            locale: {
                cancelLabel: 'Clear',
                format: "DD-MM-YYYY"
            }
        });

    
        $('.align-left.daterange').daterangepicker({
            autoUpdateInput: false,
            "opens": "left",
            locale: {
                cancelLabel: 'Clear',
                format: "DD-MM-YYYY"
            }
        });

        $('.daterange').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('DD-MM-YYYY') + ' to ' + picker.endDate.format('DD-MM-YYYY'));
        });

        $('.daterange').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
        });

        //SEARCH EMPLOYEE
        self.searchEmployee = function(query) {

            if (query) {
                return new Promise(function(resolve, reject) {
                    $http
                        .post(
                            get_manager_name, {
                                key: query,
                            }
                        )
                        .then(function(response) {

                            resolve(response.data);
                        });

                });
            } else {
                return [];
            }
        }

        //SEARCH ALTERNATIVE EMPLOYEE
        self.searchAltEmployee = function(query) {

            if (query) {
                return new Promise(function(resolve, reject) {
                    $http
                        .post(
                            get_manager_name, {
                                key: query,
                            }
                        )
                        .then(function(response) {

                            resolve(response.data);
                        });

                });
            } else {
                return [];
            }
        }
        /* $scope.getmanagerList = function(searchText, chkval) {

             if (chkval == 1) {
                 return $http
                     .get(get_manager_name + '/' + searchText)
                     .then(function(res) {
                         employee_list = res.data.employee_list;
                         return employee_list;
                     });
             } else {
                 $http
                     .get(get_manager_name + '/' + searchText)
                     .then(function(res) {
                         // console.log(res.data.employee_list[0]);
                         self.selectedItem = res.data.employee_list[0];
                     });
             }
         }*/

        $(document).on('click', '#submit', function(e) {

            var form_id = '#alternate-approve';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {
                    'employee_id': {
                        required: true,
                    },

                    'alt_employee_id': {
                        required: true,
                    },
                    'type': {
                        required: true,
                    },
                    'date': {
                        required: true,
                    },
                },
                messages: {
                    'employee_id': {
                        required: 'Employee is required',
                    },
                    'alt_employee_id': {
                        required: 'Alternate employee is required',
                    },
                    'type': {
                        required: 'Type is required',
                    },
                    'date': {
                        required: 'date is required',
                    },
                },
                submitHandler: function(form) {
                    let formData = new FormData($(form_id)[0]);
                    $('#submit').button('loading');
                    $.ajax({
                            url: laravel_routes['alternateapproveSave'],
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
                                custom_noty('error', errors);
                            } else {
                                new Noty({
                                    type: 'success',
                                    layout: 'topRight',
                                    text: 'Alternate Approve saves successfully',
                                }).show();
                                $location.path('/eyatra/alternate-approve')
                                $scope.$apply()
                            }
                        })
                        .fail(function(xhr) {
                            $('#submit').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                },
            });
            if (($("#alt_employee_id").val() != "") && ($("#employee_id").val() == $("#alt_employee_id").val())) {
                $('#alt_employee_id_error').text('Alternate employee name Must be different from employee name');
                e.preventDefault();
            }
        });

    }
});
//------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------