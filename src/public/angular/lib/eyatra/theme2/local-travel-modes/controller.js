app.component('eyatraLocalTravelModeList', {
    templateUrl: eyatra_local_trip_verification_list_template_url,
    controller: function(HelperService, $http, $rootScope, $scope, $routeParams) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.add_permission = self.hasPermission('eyatra-travel-modes-add');
        var dataTable;

        var dataTable = $('#travel_mode_table').DataTable({
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
                url: local_travel_mode_data_list_data_url,
                type: "GET",
                dataType: "json",
                data: function(d) {

                },
            },
            columns: [
                { data: 'action', searchable: false, class: 'action' },
                { data: 'number', name: 'trips.number', searchable: true },
                { data: 'created_date', name: 'trips.created_date', searchable: false },
                { data: 'ecode', name: 'e.code', searchable: true },
                { data: 'ename', name: 'users.name', searchable: true },
                { data: 'travel_period', name: 'travel_period', searchable: false },
                { data: 'purpose', name: 'purpose.name', searchable: true },
                { data: 'status', name: 'status.name', searchable: true },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }

        });

        $('.dataTables_length select').select2();
        $('.separate-page-header-content .data-table-title').html('<p class="breadcrumb">Masters / ' + 'Travel Modes' + '</p><h3 class="title">' + 'Travel Modes' + '</h3>');
        var add_url = '#!/local-travel-mode/add';
        if (self.add_permission) {
            $('.add_new_button').html(
                '<a href=' + add_url + ' type="button" class="btn btn-secondary ">' +
                'Add New' +
                '</a>'
            );
        }
        setTimeout(function() {
            var x = $('.separate-page-header-inner.search .custom-filter').position();
            var d = document.getElementById('travel_mode_table');
            x.left = x.left + 15;
            d.style.left = x.left + 'px';

        }, 500);
        $('#travel_mode_table_filter').find('input').addClass("on_focus");
        $('.on_focus').focus();
        $scope.deleteTravelMode = function($travel_mode_id) {
            $('#del').val($travel_mode_id);
        }
        $scope.deleteTravelModeDetail = function() {

            $travel_mode_id = $('#del').val();
            $http.get(
                delete_local_travel_mode_data_url + '/' + $travel_mode_id,
            ).then(function(response) {
                console.log(response.data);
                if (response.data.success) {

                    $noty = new Noty({
                        type: 'success',
                        layout: 'topRight',
                        text: 'Travel Mode Deleted Successfully',
                        animation: {
                            speed: 500 // unavailable - no need
                        },
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 5000);

                }
                dataTable.ajax.reload(function(json) {});

            });
        }


        $rootScope.loading = false;
    }
});
app.component('eyatraLocalTravelModeForm', {
    templateUrl: local_travel_mode_data_form_template_url,
    controller: function($http, $location, $location, HelperService, $routeParams, $rootScope, $scope) {
        $form_data_url = typeof($routeParams.travel_mode_id) == 'undefined' ? local_travel_mode_form_data_url : local_travel_mode_form_data_url + '/' + $routeParams.travel_mode_id;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        $http.get(
            $form_data_url,
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
                $location.path('/local-travel-mode/list')
                $scope.$apply()
                return;
            }

            self.extras = response.data.extras;
            self.entity = response.data.entity;
            // self.entity.category_id = response.data.category;
            self.action = response.data.action;
            $rootScope.loading = false;

        });

        $('#on_focus').focus();
        var form_id = form_ids = '#travel_mode_form';
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
                'category_id': {
                    required: true,

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
                        url: save_local_travel_mode_data_url,
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
                                text: res.message,
                                animation: {
                                    speed: 500 // unavailable - no need
                                },
                            }).show();
                            setTimeout(function() {
                                $noty.close();
                            }, 5000);
                            $location.path('/local-travel-mode/list')
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