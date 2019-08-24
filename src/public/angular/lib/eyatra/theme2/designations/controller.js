app.component('eyatraDesignation', {
    templateUrl: eyatra_designations_list_template_url,
    controller: function(HelperService, $rootScope, $scope, $http) {
        //alert(2)
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        var dataTable = $('#eyatra_designation_table').DataTable({
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
                url: laravel_routes['listEYatraDesignations'],
                type: "GET",
                dataType: "json",
                data: function(d) {}
            },
            columns: [
                { data: 'action', searchable: false, class: 'action', class: 'text-left' },
                { data: 'name', name: 'designations.name', searchable: true },
                { data: 'status', name: 'designations.deleted_at', searchable: false },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();
        $('.separate-page-header-content .data-table-title').html('<p class="breadcrumb">Masters / Designations</p><h3 class="title">Designations</h3>');
        //$('.page-header-content .display-inline-block .data-table-title').html('Designations');
        $('.add_new_button').html(
            '<a href="#!/eyatra/designation/add" type="button" class="btn btn-secondary" ng-show="$ctrl.hasPermission(\'add-designation\')">' +
            'Add New' +
            '</a>'
        );
        $scope.deleteDesignationConfirm = function(designation_id) {
            $("#delete_designation_id").val(designation_id);
        }

        $scope.deleteDesignation = function() {
            var designation_id = $('#delete_designation_id').val();
            console.log(designation_id)
            console.log(designation_delete_url + '/' + designation_id);
            $http.get(
                designation_delete_url + '/' + designation_id,
            ).then(function(response) {
                console.log(response.data);
                if (response.data.success) {
                    new Noty({
                        type: 'success',
                        layout: 'topRight',
                        text: 'Designation Deleted Successfully',
                    }).show();
                    dataTable.ajax.reload(function(json) {});

                } else {
                    new Noty({
                        type: 'error',
                        layout: 'topRight',
                        text: 'Designation not Deleted',
                    }).show();
                }
            });
        }
        $rootScope.loading = false;

    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('eyatraDesignationForm', {
    templateUrl: eyatra_designations_form_template_url,
    controller: function($http, $location, $location, HelperService, $routeParams, $rootScope, $scope) {
        $form_data_url = typeof($routeParams.designation_id) == 'undefined' ? designation_form_data_url : designation_form_data_url + '/' + $routeParams.designation_id;
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
                $location.path('/eyatra/designations')
                $scope.$apply()
                return;
            }
            console.log(response);
            self.designation = response.data.designation;
            self.designation.status = response.data.status;
            //self.status = response.data.status;
            self.action = response.data.action;

        });



        var form_id = '#designation-form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'code': {
                    required: true,
                    minlength: 3,
                    maxlength: 191,
                },
                'name': {
                    required: true,
                    minlength: 3,
                    maxlength: 80,
                },
            },
            messages: {
                'code': {
                    minlength: 'Please enter minimum of 3 letters',
                    maxlength: 'Please enter maximum of 191 letters',
                },
                'name': {
                    minlength: 'Please enter minimum of 3 letters',
                    maxlength: 'Please enter maximum of 80 letters',
                },
            },
            submitHandler: function(form) {
                //alert();
                let formData = new FormData($(form_id)[0]);
                //console.log(formData);
                $('#submit').button('loading');
                $.ajax({
                        url: laravel_routes['saveEYatraDesignation'],
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
                                text: 'Designation saved successfully',
                                text: res.message,
                            }).show();
                            $location.path('/eyatra/designations')
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