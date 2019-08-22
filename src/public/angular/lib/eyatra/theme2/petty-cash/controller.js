app.component('eyatraPettyCashList', {
    templateUrl: eyatra_pettycash_list_template_url,
    controller: function(HelperService, $rootScope, $scope, $http) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        var dataTable = $('#petty_cash_list').DataTable({
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
                url: laravel_routes['listPettyCashRequest'],
                type: "GET",
                dataType: "json",
                data: function(d) {}
            },

            columns: [
                { data: 'action', searchable: false, class: 'action' },
                { data: 'ename', name: 'ename', searchable: true },
                { data: 'ecode', name: 'ecode', searchable: false },
                { data: 'oname', name: 'oname', searchable: true },
                { data: 'ocode', name: 'ocode', searchable: true },
                { data: 'date', name: 'date', searchable: false },
                { data: 'total', name: 'total', searchable: true },
                { data: 'status', name: 'status.name', searchable: true },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();
        $('.separate-page-header-content .data-table-title').html('<p class="breadcrumb">Claim / Claim list</p><h3 class="title">Expense Voucher Claim</h3>');
        $('.add_new_button').html(
            '<a href="#!/eyatra/trip/add" type="button" class="btn btn-grey" ng-show="$ctrl.hasPermission(\'add-trip\')">' +
            'Add New' +
            '</a>'
        );
        $scope.deleteTrip = function(id) {
            $('#del').val(id);
        }
        $scope.confirmDeleteTrip = function() {
            $id = $('#del').val();
            $http.get(
                eyatra_trip_claim_delete_url + '/' + $id,
            ).then(function(response) {
                if (!response.data.success) {
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
                        text: 'Trips Deleted Successfully',
                    }).show();
                    $('#delete_emp').modal('hide');
                    dataTable.ajax.reload(function(json) {});
                }

            });
        }

        $rootScope.loading = false;

    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('eyatraPettyCashForm', {
    templateUrl: pettycash_form_template_url,
    controller: function($http, $location, $location, HelperService, $routeParams, $rootScope, $scope) {
        $form_data_url = typeof($routeParams.trip_id) == 'undefined' ? pettycash_form_data_url : pettycash_form_data_url + '/' + $routeParams.pettycash_id;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        // alert($form_data_url);
        $http.get(
            $form_data_url
        ).then(function(response) {
            if (!response.data.success) {
                new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: response.data.error,
                }).show();
                //$location.path('/eyatra/trips')
                //$scope.$apply()
                // return;
            }
            console.log(response);
            self.extras = response.data.extras;
            self.action = response.data.action;
            self.petty_cash = response.data.petty_cash;
            self.petty_cash_other = response.data.petty_cash_other;

            if (self.petty_cash.length == 0) {
                self.addlocalconveyance();
                self.addotherexpence();
            }

        });
        $('.btn-nxt').on("click", function() {
            $('.editDetails-tabs li.active').next().children('a').trigger("click");
        });
        $('.btn-prev').on("click", function() {
            $('.editDetails-tabs li.active').prev().children('a').trigger("click");
        });

        // self.searchCity = function(query) {
        //     if (query) {
        //         return new Promise(function(resolve, reject) {
        //             $http
        //                 .post(
        //                     laravel_routes['searchCity'], {
        //                         key: query,
        //                     }
        //                 )
        //                 .then(function(response) {
        //                     resolve(response.data);
        //                 });
        //         });
        //     } else {
        //         return [];
        //     }
        // }

        self.addlocalconveyance = function() {
            self.petty_cash.push({
                booking_method: 'Self',
                from_place: '',
                to_place: '',
                from_km: '',
                to_km: '',
            });
        }
        self.addotherexpence = function() {
            self.petty_cash_other.push({
                expence_type: '',
                booking_method: 'Self',
                preferred_travel_modes: '',
            });
        }

        self.removepettyCash = function(index, lodging_id) {
            if (lodging_id) {
                lodgings_removal_id.push(lodging_id);
                $('#lodgings_removal_id').val(JSON.stringify(lodgings_removal_id));
            }
            self.petty_cash.splice(index, 1);
        }

        self.removeotherexpence = function(index, lodging_id) {
            if (lodging_id) {
                lodgings_removal_id.push(lodging_id);
                $('#lodgings_removal_id').val(JSON.stringify(lodgings_removal_id));
            }
            self.petty_cash_other.splice(index, 1);
        }

        var form_id = '#petty-cash';
        var v = jQuery(form_id).validate({
            errorPlacement: function(error, element) {
                error.insertAfter(element)
            },
            ignore: '',
            invalidHandler: function(event, validator) {
                new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: 'Check all tabs for errors'
                }).show();
            },
            submitHandler: function(form) {

                let formData = new FormData($(form_id)[0]);
                $('#submit').button('loading');
                $.ajax({
                        url: laravel_routes['pettycashSave'],
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
                                text: 'Petty Cash saves successfully',
                            }).show();
                            $location.path('/eyatra/pettycash')
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