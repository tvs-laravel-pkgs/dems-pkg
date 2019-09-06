app.component('eyatraCoaCode', {
    templateUrl: eyatra_coa_code_list_template_url,
    controller: function(HelperService, $rootScope, $http, $scope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        var dataTable = $('#eyatra_coa_code_table').DataTable({
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
                url: laravel_routes['listEYatraCoaCode'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.account_type = $('#acc_type_id').val();
                    d.group_id = $('#group_id').val();
                    d.sub_group_id = $('#sub_group_id').val();
                    d.status = $('#status').val();
                }
            },
            columns: [
                { data: 'action', searchable: false, class: 'action', class: 'text-left' },
                { data: 'number', name: 'coa_codes.number', searchable: true },
                { data: 'account_description', name: 'coa_codes.account_description', searchable: true },
                { data: 'account_type', name: 'e.name', searchable: true },
                { data: 'normal_balance', name: 'e1.name', searchable: true },
                { data: 'description', name: 'coa_codes.description', searchable: true },
                { data: 'final_statement', name: 'e2.name', searchable: true },
                { data: 'group', name: 'e3.name', searchable: true },
                { data: 'sub_group', name: 'e4.name', searchable: true },
                { data: 'status', name: 'coa_codes.deleted_at', searchable: false },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();

        setTimeout(function() {
            var x = $('.separate-page-header-inner.search .custom-filter').position();
            var d = document.getElementById('eyatra_coa_code_table_filter');
            x.left = x.left + 15;
            d.style.left = x.left + 'px';

        }, 500);
        $('#eyatra_coa_code_table_filter').find('input').addClass("on_focus");
        $('.on_focus').focus();
        //Filter
        $http.get(
            coa_code_filter_url
        ).then(function(response) {
            // console.log(response);
            self.acc_type_list = response.data.acc_type_list;
            self.group_list = response.data.group_list;
            self.sub_group_list = response.data.sub_group_list;
            self.status_list = response.data.status_list;
            $rootScope.loading = false;
        });
        var dataTableFilter = $('#eyatra_coa_code_table').dataTable();
        $scope.onselectAccountType = function(id) {
            $('#acc_type_id').val(id);
            dataTableFilter.fnFilter();
        }
        $scope.onselectGroup = function(id) {
            $('#group_id').val(id);
            dataTableFilter.fnFilter();
        }
        $scope.onselectSubGroup = function(id) {
            $('#sub_group_id').val(id);
            dataTableFilter.fnFilter();
        }
        $scope.onselectStatus = function(id) {
            $('#status').val(id);
            dataTableFilter.fnFilter();
        }
        $scope.resetForm = function() {
            $('#acc_type_id').val(null);
            $('#group_id').val(null);
            $('#sub_group_id').val(null);
            $('#status').val(null);
            dataTableFilter.fnFilter();
        }
        $scope.deleteCoaCodeConfirm = function($coa_code_id) {
            $("#del").val($coa_code_id);
        }

        $scope.deleteCoaCode = function() {
            $coa_code_id = $('#del').val();
            $http.get(
                coa_code_delete_url + '/' + $coa_code_id,
            ).then(function(response) {
                console.log(response.data);
                if (response.data.success) {
                    $noty = new Noty({
                        type: 'success',
                        layout: 'topRight',
                        text: 'Coa Code Deleted Successfully',
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
                        text: 'Coa Code not Deleted',
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

app.component('eyatraCoaCodeForm', {
    templateUrl: coa_code_form_template_url,
    controller: function($http, $location, $location, HelperService, $routeParams, $rootScope, $scope) {
        $form_data_url = typeof($routeParams.coa_code_id) == 'undefined' ? coa_code_form_data_url : coa_code_form_data_url + '/' + $routeParams.coa_code_id;
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

                $location.path('/eyatra/coa-codes')
                $scope.$apply()
                return;
            }

            self.coacode = response.data.coacode;
            // self.state_list = response.data.state_list;
            self.status = response.data.status;
            self.extras = response.data.extras;
            self.action = response.data.action;

        });

        $('#on_focus').focus();

        $('.btn-nxt').on("click", function() {
            $('.editDetails-tabs li.active').next().children('a').trigger("click");
        });
        $('.btn-prev').on("click", function() {
            $('.editDetails-tabs li.active').prev().children('a').trigger("click");
        });
        $('.btn-pills').on("click", function() {});
        $scope.btnNxt = function() {}
        $scope.prev = function() {}

        var form_id = '#coa-code-form';
        var v = jQuery(form_id).validate({
            errorPlacement: function(error, element) {
                if (element.hasClass("number")) {
                    error.appendTo($('.number_error'));
                } else {
                    error.insertAfter(element)
                }
            },
            // invalidHandler: function(event, validator) {
            /* $noty = //     new Noty({
     //         type: 'error',
     //         layout: 'topRight',
     //         text: 'You have errors,Please check all tabs',

     //     }).show();
     setTimeout(function() {
         $noty.close();
     }, 1000);*/
            // },
            ignore: '',
            rules: {

                'number': {
                    required: true,
                    number: true,
                    min: 1,
                },
                'account_description': {
                    required: true,
                    minlength: 3,
                    maxlength: 191,
                },
                'account_types': {
                    required: true,
                },
                'normal_balance': {
                    required: true,
                },
                'description': {
                    required: true,
                    minlength: 3,
                    maxlength: 191,
                },
                'final_statement': {
                    required: true,
                },
                'group': {
                    required: true,
                },

                'sub_group': {
                    required: true,
                },
            },
            messages: {
                'number': {
                    number: 'Enter numbers only',
                },
                'account_description': {
                    minlength: 'Please enter minimum of 3 letters',
                    maxlength: 'Please enter maximum of 191 letters',
                },
                'description': {
                    minlength: 'Please enter minimum of 3 letters',
                    maxlength: 'Please enter maximum of 191 letters',
                }

            },
            invalidHandler: function(event, validator) {
                $noty = new Noty({
                    type: 'error',
                    layout: 'topRight', 
                    text: 'You have errors, Please check'

                    animation: {
                        speed: 500 // unavailable - no need
                    },

                }).show();
                setTimeout(function() {
                    $noty.close();
                }, 1000);
            },
            submitHandler: function(form) {

                let formData = new FormData($(form_id)[0]);
                $('#submit').button('loading');
                $.ajax({
                        url: laravel_routes['saveEYatraCoaCode'],
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
                                text: 'Coa Code saved successfully',
                                text: res.message,
                                animation: {
                                    speed: 500 // unavailable - no need
                                },
                            }).show();
                            setTimeout(function() {
                                $noty.close();
                            }, 1000);
                            $location.path('/eyatra/coa-codes')
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

app.component('eyatraCoaCodeView', {
    templateUrl: coa_code_view_template_url,

    controller: function($http, $location, $routeParams, HelperService, $scope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        $http.get(
            coa_code_view_url + '/' + $routeParams.coa_code_id
        ).then(function(response) {
            self.coacode = response.data.coacode;
            self.action = response.data.action;
        });
    }
});


//------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------