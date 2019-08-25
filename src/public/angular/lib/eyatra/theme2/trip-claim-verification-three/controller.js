app.component('eyatraTripClaimVerificationThreeList', {
    templateUrl: eyatra_trip_claim_verification_three_list_template_url,
    controller: function(HelperService, $rootScope, $scope, $http) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        var dataTable = $('#eyatra_trip_claim_verification_three_list_table').DataTable({
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
                url: laravel_routes['listEYatraTripClaimVerificationThreeList'],
                type: "GET",
                dataType: "json",
                data: function(d) {}
            },
            columns: [
                { data: 'action', searchable: false, class: 'action' },
                { data: 'number', name: 'trips.number', searchable: true },
                { data: 'ecode', name: 'e.code', searchable: true },
                { data: 'start_date', name: 'v.date', searchable: true },
                { data: 'end_date', name: 'v.date', searchable: true },
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

        $('.separate-page-header-content .data-table-title').html('<p class="breadcrumb">Claims</p><h3 class="title">Claimed Trips Verification Three</h3>');
        //$('.page-header-content .display-inline-block .data-table-title').html('Employees');

        $('.add_new_button').html();

        $rootScope.loading = false;

    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('eyatraTripClaimVerificationThreeView', {
    templateUrl: eyatra_trip_claim_verification_three_view_template_url,
    controller: function($http, $location, $location, HelperService, $routeParams, $rootScope, $scope) {
        $form_data_url = typeof($routeParams.trip_id) == 'undefined' ? eyatra_trip_claim_verification_three_view_url + '/' : eyatra_trip_claim_verification_three_view_url + '/' + $routeParams.trip_id;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        self.eyatra_trip_claim_verification_three_visit_attachment_url = eyatra_trip_claim_verification_three_visit_attachment_url;
        self.eyatra_trip_claim_verification_three_lodging_attachment_url = eyatra_trip_claim_verification_three_lodging_attachment_url;
        self.eyatra_trip_claim_verification_three_boarding_attachment_url = eyatra_trip_claim_verification_three_boarding_attachment_url;
        self.eyatra_trip_claim_verification_three_local_travel_attachment_url = eyatra_trip_claim_verification_three_local_travel_attachment_url;
        $http.get(
            $form_data_url
        ).then(function(response) {
            if (!response.data.success) {
                new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: response.data.error,
                }).show();
                $location.path('/eyatra/trip/claim/verification3/list')
                $scope.$apply()
                return;
            }
            console.log(response.data.trip.lodgings.city);
            self.trip = response.data.trip;
            self.travel_cities = response.data.travel_cities;
            self.travel_dates = response.data.travel_dates;
            self.transport_total_amount = response.data.transport_total_amount;
            self.lodging_total_amount = response.data.lodging_total_amount;
            self.boardings_total_amount = response.data.boardings_total_amount;
            self.local_travels_total_amount = response.data.local_travels_total_amount;
            self.total_amount = response.data.total_amount.toFixed(2);
            if (self.trip.advance_received) {
                if (self.total_amount > self.trip.advance_received) {
                    self.pay_to_employee = (self.total_amount - self.trip.advance_received);
                    self.pay_to_company = 0.00;
                } else if (self.total_amount < self.trip.advance_received) {
                    self.pay_to_employee = 0.00;
                    self.pay_to_company = (self.trip.advance_received - self.advance_received);
                } else {
                    self.pay_to_employee = 0.00;
                    self.pay_to_company = 0.00;
                }
            } else {
                self.pay_to_employee = self.total_amount;
                self.pay_to_company = 0.00;
            }
            $rootScope.loading = false;

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