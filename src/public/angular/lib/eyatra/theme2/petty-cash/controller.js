app.component('eyatraTrips1', {
    templateUrl: eyatra_trip_list_template_url,
    controller: function(HelperService, $rootScope, $scope, $http) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        var dataTable = $('#eyatra_trip_table').DataTable({
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
                url: laravel_routes['listTrip'],
                type: "GET",
                dataType: "json",
                data: function(d) {}
            },

            columns: [
                { data: 'action', searchable: false, class: 'action' },
                { data: 'number', name: 'trips.number', searchable: true },
                { data: 'created_date', name: 'trips.created_date', searchable: false },
                { data: 'ecode', name: 'e.code', searchable: true },
                { data: 'ename', name: 'e.name', searchable: true },
                { data: 'travel_period', name: 'travel_period', searchable: false },
                { data: 'purpose', name: 'purpose.name', searchable: true },
                { data: 'advance_received', name: 'trips.advance_received', searchable: false },
                { data: 'status', name: 'status.name', searchable: true },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();
        $('.separate-page-header-content .data-table-title').html('<p class="breadcrumb">Request / Trips</p><h3 class="title">Trips</h3>');
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
            console.log(response.data);
            self.employee_eligible_grade = response.data.employee_eligible_grade;
            if (self.employee_eligible_grade.advanced_eligibility == 1) {
                $("#advance").show().prop('disabled', false);
            }
            self.trip = response.data.trip;
            self.extras = response.data.extras;
            self.action = response.data.action;
            $rootScope.loading = false;
            $scope.showBank = false;
            $scope.showCheque = false;
            $scope.showWallet = false;

        });
        $("#advance").hide().prop('disabled', true);
        $('.btn-nxt').on("click", function() {
            $('.editDetails-tabs li.active').next().children('a').trigger("click");
        });
        $('.btn-prev').on("click", function() {
            $('.editDetails-tabs li.active').prev().children('a').trigger("click");
        });

        self.searchCity = function(query) {
            if (query) {
                return new Promise(function(resolve, reject) {
                    $http
                        .post(
                            laravel_routes['searchCity'], {
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

        self.addVisit = function() {
            self.trip.visits.push({
                visit_date: '',
                booking_method: 'Self',
                preferred_travel_modes: '',
            });
        }

        $scope.trip_mode = function(id) {
            if (id == 1) {
                $scope.single_trip = true;
                $scope.round_trip = false;
                $scope.multi_trip = false;
            } else if (id == 2) {
                $scope.single_trip = false;
                $scope.round_trip = true;
                $scope.multi_trip = false;
            } else if (id == 3) {
                $scope.round_trip = false;
                $scope.single_trip = false;
                $scope.multi_trip = true;
            }
        }

        self.removeLodging = function(index, lodging_id) {
            if (lodging_id) {
                lodgings_removal_id.push(lodging_id);
                $('#lodgings_removal_id').val(JSON.stringify(lodgings_removal_id));
            }
            self.trip.visits.splice(index, 1);
        }

        var form_id = '#trip-form';
        var v = jQuery(form_id).validate({
            errorPlacement: function(error, element) {
                if (element.attr('name') == 'trip_mode[]') {
                    error.appendTo($('.trip_mode'));
                } else {
                    error.insertAfter(element)
                }
            },
            ignore: '',
            rules: {
                'purpose_id': {
                    required: true,
                },
                'description': {
                    maxlength: 255,
                },
                'advance_received': {
                    maxlength: 10,
                },
                'trip_mode[]': {
                    required: true,
                },
            },
            messages: {
                'description': {
                    maxlength: 'Please enter maximum of 255 letters',
                },
                'advance_received': {
                    maxlength: 'Please enter maximum of 10 Numbers',
                },
                'trip_mode[]': {
                    required: 'Select Visit Mode',
                },
            },
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
                        url: laravel_routes['saveTrip'],
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
                                text: 'Trip saves successfully',
                            }).show();
                            $location.path('/eyatra/trips')
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