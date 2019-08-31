app.component('eyatraTripClaimVerificationOneList', {
    templateUrl: eyatra_trip_claim_verification_one_list_template_url,
    controller: function(HelperService, $rootScope, $scope, $http) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        $http.get(
            trip_filter_data_url
        ).then(function(response) {
            console.log(response.data);
            self.employee_list = response.data.employee_list;
            self.purpose_list = response.data.purpose_list;
            self.trip_status_list = response.data.trip_status_list;
            $rootScope.loading = false;
        });

        var dataTable = $('#eyatra_trip_claim_verification_one_list_table').DataTable({
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
                url: laravel_routes['listEYatraTripClaimVerificationOneList'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.employee_id = $('#employee_id').val();
                    d.purpose_id = $('#purpose_id').val();
                    d.status_id = $('#status_id').val();
                }
            },
            columns: [
                { data: 'action', searchable: false, class: 'action' },
                { data: 'number', name: 'trips.number', searchable: true },
                { data: 'ecode', name: 'e.code', searchable: true },
                { data: 'ename', name: 'users.name', searchable: false },
                { data: 'start_date', name: 'v.departure_date', searchable: true },
                { data: 'end_date', name: 'v.departure_date', searchable: true },
                { data: 'cities', name: 'c.name', searchable: true },
                { data: 'purpose', name: 'purpose.name', searchable: true },
                { data: 'advance_received', name: 'trips.advance_received', searchable: false },
                { data: 'status', name: 'status.name', searchable: true },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();

        setTimeout(function() {
            var x = $('.separate-page-header-inner.search .custom-filter').position();
            var d = document.getElementById('eyatra_trip_claim_verification_one_list_table_filter');
            x.left = x.left + 15;
            d.style.left = x.left + 'px';
        }, 500);

        $scope.getEmployeeData = function(query) {
            $('#employee_id').val(query);
            dataTable.draw();
        }
        $scope.getPurposeData = function(query) {
            $('#purpose_id').val(query);
            dataTable.draw();
        }
        $scope.getStatusData = function(query) {
            $('#status_id').val(query);
            dataTable.draw();
        }

        $scope.reset_filter = function(query) {
            $('#employee_id').val(-1);
            $('#purpose_id').val(-1);
            $('#status_id').val(-1);
            dataTable.draw();
        }
        // $('.separate-page-header-content .data-table-title').html('<p class="breadcrumb">Claims</p><h3 class="title">Claimed Trips Verification One</h3>');
        //$('.page-header-content .display-inline-block .data-table-title').html('Employees');

        // $('.add_new_button').html();

        $rootScope.loading = false;

    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('eyatraTripClaimVerificationOneView', {
    templateUrl: eyatra_trip_claim_verification_one_view_template_url,
    controller: function($http, $location, $location, HelperService, $routeParams, $rootScope, $scope) {
        $form_data_url = typeof($routeParams.trip_id) == 'undefined' ? eyatra_trip_claim_verification_one_view_url + '/' : eyatra_trip_claim_verification_one_view_url + '/' + $routeParams.trip_id;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        self.eyatra_trip_claim_verification_one_visit_attachment_url = eyatra_trip_claim_verification_one_visit_attachment_url;
        self.eyatra_trip_claim_verification_one_lodging_attachment_url = eyatra_trip_claim_verification_one_lodging_attachment_url;
        self.eyatra_trip_claim_verification_one_boarding_attachment_url = eyatra_trip_claim_verification_one_boarding_attachment_url;
        self.eyatra_trip_claim_verification_one_local_travel_attachment_url = eyatra_trip_claim_verification_one_local_travel_attachment_url;
        $http.get(
            $form_data_url
        ).then(function(response) {
            if (!response.data.success) {
                new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: response.data.error,
                }).show();
                $location.path('/eyatra/trip/claim/verification1/list')
                $scope.$apply()
                return;
            }
            console.log(response.data.trip.lodgings.city);
            self.trip = response.data.trip;
            self.travel_cities = response.data.travel_cities;
            self.trip_claim_rejection_list = response.data.trip_claim_rejection_list;
            self.travel_dates = response.data.travel_dates;
            self.transport_total_amount = response.data.transport_total_amount;
            self.lodging_total_amount = response.data.lodging_total_amount;
            self.boardings_total_amount = response.data.boardings_total_amount;
            self.local_travels_total_amount = response.data.local_travels_total_amount;
            self.total_amount = response.data.total_amount;
            if (self.trip.advance_received) {
                if (self.total_amount > self.trip.advance_received) {
                    self.pay_to_employee = (self.total_amount - self.trip.advance_received);
                    self.pay_to_company = '0.00';
                } else if (self.total_amount < self.trip.advance_received) {
                    self.pay_to_employee = '0.00';
                    self.pay_to_company = (self.total_amount - self.trip.advance_received);
                } else {
                    self.pay_to_employee = '0.00';
                    self.pay_to_company = '0.00';
                }
            } else {
                self.pay_to_employee = self.total_amount;
                self.pay_to_company = '0.00';
            }
            $rootScope.loading = false;

        });


        $scope.searchRejectedReason;
        $scope.clearSearchRejectedReason = function() {
            $scope.searchRejectedReason = '';
        };

        $scope.tripClaimApproveOne = function(trip_id) {
            $('#modal_trip_id').val(trip_id);
        }
        $scope.confirmTripClaimApproveOne = function() {
            $trip_id = $('#modal_trip_id').val();
            $http.get(
                eyatra_trip_claim_verification_one_approve_url + '/' + $trip_id,
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
                        text: 'Trips Claim Approved Successfully',
                    }).show();
                    $('#trip-claim-modal-approve-one').modal('hide');
                    $location.path('/eyatra/trip/claim/verification1/list')
                    $scope.$apply()
                }

            });
        }


        //Reject
        $(document).on('click', '.reject_btn', function() {
            var form_id = '#trip-claim-reject-form';
            var v = jQuery(form_id).validate({
                ignore: '',

                submitHandler: function(form) {

                    let formData = new FormData($(form_id)[0]);
                    $('#reject_btn').button('loading');
                    $.ajax({
                            url: laravel_routes['rejectTripClaimVerificationOne'],
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                        })
                        .done(function(res) {
                            console.log(res.success);
                            if (!res.success) {
                                $('#reject_btn').button('reset');
                                var errors = '';
                                for (var i in res.errors) {
                                    errors += '<li>' + res.errors[i] + '</li>';
                                }
                                custom_noty('error', errors);
                            } else {
                                new Noty({
                                    type: 'success',
                                    layout: 'topRight',
                                    text: 'Trips Claim Rejected successfully',
                                }).show();
                                $('#trip-claim-modal-reject-one').modal('hide');
                                setTimeout(function() {
                                    $location.path('/eyatra/trip/claim/verification1/list')
                                    $scope.$apply()
                                }, 500);

                            }
                        })
                        .fail(function(xhr) {
                            $('#reject_btn').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                },
            });
        });


        /* Pane Next Button */
        $('.btn-nxt').on("click", function() {
            $('.editDetails-tabs li.active').next().children('a').trigger("click");
        });
        $('.btn-prev').on("click", function() {
            $('.editDetails-tabs li.active').prev().children('a').trigger("click");
        });

    }
});