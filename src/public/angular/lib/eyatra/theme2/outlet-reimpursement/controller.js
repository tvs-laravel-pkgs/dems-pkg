app.component('eyatraOutletReimbursement', {
    templateUrl: eyatra_outlet_reimpursement_list_template_url,
    controller: function(HelperService, $http, $rootScope, $scope, $routeParams) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        var dataTable = $('#outlet_reimpursement_table').DataTable({
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
                url: laravel_routes['listOutletReimpursement'],
                type: "GET",
                dataType: "json",
                data: function(d) {}
            },

            columns: [
                { data: 'action', searchable: false, class: 'action' },
                { data: 'outlet_name', name: 'outlets.name', searchable: true },
                { data: 'outlet_code', name: 'outlets.code', searchable: true },
                { data: 'cashier_name', name: 'employees.name', searchable: true },
                { data: 'cashier_code', name: 'employees.code', searchable: true },
                { data: 'amount', searchable: false },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });

        $('.dataTables_length select').select2();
        var add_url = '#!/eyatra/entity/add/' + self.id;
        if (self.id) {
            $('.add_new_button').html(
                '<a href=' + add_url + ' type="button" class="btn btn-secondary ">' +
                'Add New' +
                '</a>'
            );
        }
        $scope.deleteEntityDetail = function($id) {
            $('#del').val($id);
        }
        $scope.deleteEntity = function() {

            $id = $('#del').val();
            $http.get(
                delete_entity_component_url + '/' + $id,
            ).then(function(response) {
                console.log(response.data);
                if (response.data.success) {

                    new Noty({
                        type: 'success',
                        layout: 'topRight',
                        text: 'Entity Detail Deleted Successfully',
                    }).show();
                }
                dataTable.ajax.reload(function(json) {});

            });
        }
        $rootScope.loading = false;
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('eyatraEntityForm', {
    templateUrl: eyatra_entity_form_template_url,
    controller: function($http, $location, $location, HelperService, $routeParams, $rootScope, $scope) {
        $form_data_url = typeof($routeParams.entity_id) == 'undefined' ? eyatra_entity_form_data_url + '/' + $routeParams.entity_type_id : eyatra_entity_form_data_url + '/' + $routeParams.entity_type_id + '/' + $routeParams.entity_id;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        $http.get(
            $form_data_url,
        ).then(function(response) {
            //console.log(response.data);
            if (!response.data.success) {
                new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: response.data.error,
                }).show();
                $location.path('/eyatra/entity/list' + '/' + $routeParams.entity_type_id)
                $scope.$apply()
                return;
            }
            self.entity = response.data.entity;
            self.entity_type = response.data.entity_type;
            self.action = response.data.action;
            $rootScope.loading = false;

        });
        $('input').on('blur keyup', function() {
            if ($("#entity_form").valid()) {
                $('#submit').prop('disabled', false);
            } else {
                $('#submit').prop('disabled', 'disabled');
            }
        });
        $('#submit').click(function() {
            if ($("#entity_form").valid()) {
                $('#submit').prop('disabled', false);
            } else {
                $('#submit').prop('disabled', 'disabled');
            }
        });
        var form_id = form_ids = '#entity_form';
        var v = jQuery(form_ids).validate({
            errorPlacement: function(error, element) {
                if (element.hasClass("name")) {
                    error.appendTo($('.name_error'));
                } else {
                    error.insertAfter(element)
                }
            },
            ignore: '',
            rules: {
                'name': {
                    required: true,
                    minlength: 1,
                    maxlength: 191,
                },

            },
            messages: {
                'name': {
                    minlength: 'Please enter minimum of 1 characters',
                    minlength: 'Please enter maximum of 191 characters',
                },
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $.ajax({
                        url: eyatra_save_entity_url + '/' + $routeParams.entity_type_id,
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        console.log(res.success);
                        if (!res.success) {
                            $('#submit').prop('disabled', 'disabled');
                            var errors = '';
                            for (var i in res.errors) {
                                errors += '<li>' + res.errors[i] + '</li>';
                            }
                            custom_noty('error', errors);
                        } else {
                            new Noty({
                                type: 'success',
                                layout: 'topRight',
                                text: 'Entity Details Added Successfully',
                                text: res.message,
                            }).show();
                            $location.path('/eyatra/entity/list' + '/' + $routeParams.entity_type_id)
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

app.component('eyatraEntityView', {
    templateUrl: eyatra_entity_view_template_url,

    controller: function($http, $location, $routeParams, HelperService, $scope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        $http.get(
            eyatra_entity_view_url + '/' + $routeParams.entity_id
        ).then(function(response) {
            self.entity = response.data.entity;
        });
    }
});


//------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------