app.component('coaDataList', {
    templateUrl: coa_data_list_template_url,
    controller: function(HelperService, $http, $rootScope, $scope, $routeParams) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        var dataTable;

        var dataTable = $('#coa_data_table').DataTable({
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
                url: coa_data_list_data_url,
                type: "GET",
                dataType: "json",
                data: function(d) {

                },
            },
            columns: [
                { data: 'action', searchable: false, class: 'action' },
                { data: 'name', name: 'entities.name' },
                { data: 'entity_type', name: 'entity_types.name' },
                { data: 'created_by', name: 'users.username' },
                { data: 'updated_by', name: 'updater.username' },
                { data: 'deleted_by', name: 'deactivator.username' },
                { data: 'created_at', name: 'entities.created_at', searchable: false },
                { data: 'updated_at1', name: 'entities.updated_at', searchable: false },
                { data: 'deleted_at', name: 'entities.deleted_at', searchable: false },
                { data: 'status', searchable: false },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }

        });

        $('.dataTables_length select').select2();
        $('.separate-page-header-content .data-table-title').html('<p class="breadcrumb">Masters / ' + 'COA Sub Masters' + '</p><h3 class="title">' + 'COA Sub Masters' + '</h3>');
        var add_url = '#!/eyatra/coa-sub-master/add/';
        $('.add_new_button').html(
            '<a href=' + add_url + ' type="button" class="btn btn-secondary ">' +
            'Add New' +
            '</a>'
        );
        setTimeout(function() {
            var x = $('.separate-page-header-inner.search .custom-filter').position();
            var d = document.getElementById('coa_data_table');
            x.left = x.left + 15;
            d.style.left = x.left + 'px';
        }, 500);

        $scope.deleteCoaData = function($id) {
            $('#del').val($id);
        }
        $scope.deleteCoaDetail = function() {

            $id = $('#del').val();
            $http.get(
                delete_coa_data_url + '/' + $id,
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
app.component('coaDataForm', {
    templateUrl: coa_data_form_template_url,
    controller: function($http, $location, $location, HelperService, $routeParams, $rootScope, $scope) {
        $form_data_url = typeof($routeParams.entity_id) == 'undefined' ? coa_form_ng_data_url : coa_form_ng_data_url + '/' + $routeParams.entity_id;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        $http.get(
            $form_data_url,
        ).then(function(response) {

            if (!response.data.success) {
                new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: response.data.error,
                }).show();
                $location.path('/eyatra/coa-sub-master/list')
                $scope.$apply()
                return;
            }

            self.reject_type_list = response.data.reject_type_list;
            self.entity = response.data.entity;
            self.action = response.data.action;
            $rootScope.loading = false;

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
                    maxlength: 'Please enter maximum of 191 characters',
                },
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('#submit').button('loading');
                $.ajax({
                        url: save_coa_data_url,
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
                                text: res.message,
                            }).show();
                            $location.path('/eyatra/coa-sub-master/list')
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