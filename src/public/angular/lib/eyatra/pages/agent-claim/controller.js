app.component('eyatraAgentClaimList', {
    templateUrl: eyatra_agent_claim_list_template_url,
    controller: function(HelperService, $rootScope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        // console.log(self.hasPermission);
        var dataTable = $('#agent_claim_list').DataTable({
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
                url: laravel_routes['listAgentClaim'],
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
                { data: 'status', name: 'status.name', searchable: true },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();
        $('.page-header-content .display-inline-block .data-table-title').html('Agent Claims');
        $('.add_new_button').html(
            '<a href="#!/eyatra/agent/claim/add" type="button" class="btn btn-secondary">' +
            'Add New' +
            '</a>'
        );
        $rootScope.loading = false;

    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('eyatraAgentClaimForm', {
    templateUrl: eyatra_agent_claim_form_template_url,
    controller: function($http, $location, $location, HelperService, $routeParams, $rootScope, $scope) {
        $form_data_url = eyatra_agent_claim_form_data_url;
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
                    text: response.data.message,
                }).show();
                $location.path('/eyatra/agent/claim/list')
                $scope.$apply()
                return;
            }

            self.agent_claim = response.data.agent_claim;
            self.booking_list = response.data.booking_list;
            self.action = response.data.action;
            // self.extras = response.data.extras;

            $rootScope.loading = false;

        });

        $('#head_booking').on('click', function() {
            if (event.target.checked == true) {
                $('.booking_list').prop('checked', true);
            } else {
                $('.booking_list').prop('checked', false);
            }
        });

    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------