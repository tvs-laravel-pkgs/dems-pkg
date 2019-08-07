app.component('eyatraTripClaimList', {
    templateUrl: eyatra_agent_claim_list_template_url,
    controller: function(HelperService, $rootScope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        var dataTable = $('#eyatra_trip_claim_list_table').DataTable({
            "dom": dom_structure,
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
                url: laravel_routes['listTripClaim'],
                type: "GET",
                dataType: "json",
                data: function(d) {}
            },
            columns: [
                { data: 'action', searchable: false, class: 'action' },
                { data: 'number', name: 't.number', searchable: true },
                { data: 'ecode', name: 'e.code', searchable: true },
                { data: 'date', name: 'v.date', searchable: true },
                { data: 'from', name: 'fc.name', searchable: true },
                { data: 'to', name: 'tc.name', searchable: true },
                { data: 'travel_mode', name: 'tm.name', searchable: true },
                { data: 'booking_status', name: 'bs.name', searchable: false },
                { data: 'agent', name: 'a.name', searchable: true },
                { data: 'status', name: 'status.name', searchable: true },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();
        $('.page-header-content .display-inline-block .data-table-title').html('Trip Claims');
        $('.add_new_button').html();
        $rootScope.loading = false;

    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('eyatraTripClaimForm', {
    templateUrl: eyatra_trip_claim_form_template_url,
    controller: function($http, $location, $location, HelperService, $routeParams, $rootScope, $scope) {
        $form_data_url = typeof($routeParams.trip_id) == 'undefined' ? eyatra_trip_claim_form_data_url + '/' : eyatra_trip_claim_form_data_url + '/' + $routeParams.trip_id;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        self.eyatra_trip_claim_visit_attachment_url = eyatra_trip_claim_visit_attachment_url;
        $http.get(
            $form_data_url
        ).then(function(response) {
            if (!response.data.success) {
                new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: response.data.error,
                }).show();
                $location.path('/eyatra/trip/claim/list')
                $scope.$apply()
                return;
            }
            console.log(response.data);
            self.extras = response.data.extras;
            self.trip = response.data.trip;
            self.action = response.data.action;

            if (self.trip.lodgings.length == 0) {
                self.addNewLodgings();
            }
            if (self.trip.boardings.length == 0) {
                self.addNewBoardings();
            }
            if (self.trip.local_travels.length == 0) {
                self.addNewLocalTralvels();
            }
            $rootScope.loading = false;

        });

        // $(function() {
        //     $('.form_datetime').datetimepicker();
        // });

        // $(".form_datetime").datetimepicker({
        //     format: "yyyy-m-dd hh:ii",
        //     autoclose: true,
        //     todayBtn: true,
        //     pickerPosition: "bottom-left"
        // });

        /* Pane Next Button */
        $('.btn-nxt').on("click", function() {
            $('.editDetails-tabs li.active').next().children('a').trigger("click");
        });
        $('.btn-prev').on("click", function() {
            $('.editDetails-tabs li.active').prev().children('a').trigger("click");
        });

        // Lodgings
        self.addNewLodgings = function() {
            self.trip.lodgings.push({
                city_id: '',
                lodge_name: '',
                stay_type_id: '',
                amount: '',
                tax: '',
                remarks: '',
            });
        }
        self.removeLodging = function(index) {
            self.trip.lodgings.splice(index, 1);
        }

        // Boardings
        self.addNewBoardings = function() {
            self.trip.boardings.push({
                city_id: '',
                expense_name: '',
                date: '',
                amount: '',
                remarks: '',
            });
        }
        self.removeBoarding = function(index) {
            self.trip.boardings.splice(index, 1);
        }

        // LocalTralvels
        self.addNewLocalTralvels = function() {
            self.trip.local_travels.push({
                mode_id: '',
                date: '',
                from_id: '',
                to_id: '',
                amount: '',
                description: '',
            });
        }
        self.removeLocalTralvel = function(index) {
            self.trip.local_travels.splice(index, 1);
        }
    }
});