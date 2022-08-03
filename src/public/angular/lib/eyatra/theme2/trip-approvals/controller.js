app.component('eyatraTripApprovals', {
    templateUrl: eyatra_trip_approval_list_template_url,
    controller: function(HelperService, $rootScope, $scope, $http) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;

        $http.get(
            trip_filter_data_url
        ).then(function(response) {
            self.employee_list = response.data.employee_list;
            self.purpose_list = response.data.purpose_list;
            self.trip_status_list = response.data.trip_status_list;
            const cols = [
                { data: 'action', searchable: false, class: 'action' },
                { data: 'title', searchable: false, class: 'title' },
                { data: 'claim_number', name: 'ey_employee_claims.number', searchable: true },
                { data: 'number', name: 'trips.number', searchable: true },
                { data: 'ecode', name: 'e.code', searchable: true },
                { data: 'ename', name: 'users.name', searchable: false },
                { data: 'start_date', name: 'trips.start_date', searchable: false },
                { data: 'end_date', name: 'trips.end_date', searchable: false },
                // { data: 'cities', name: 'c.name', searchable: true },
                { data: 'purpose', name: 'purpose.name', searchable: true },
                { data: 'advance_received', name: 'trips.advance_received', searchable: false },
                { data: 'reason', name: 'reason', searchable: true },
                { data: 'status', name: 'status.name', searchable: true },
            ];
            var dataTable = $('#eyatraTripApprovalListTable').DataTable({
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
                    url: laravel_routes['getEyatraTripApprovalList'],
                    type: "GET",
                    dataType: "json",
                    data: function(d) {
                        d.employee_id = $('#employee_id').val();
                        d.purpose_id = $('#purpose_id').val();
                        d.status_id = $('#status_id').val();
                        d.from_date = $('#from_date').val();
                        d.to_date = $('#to_date').val();
                    }
                },
                columns: cols,
                rowCallback: function(row, data) {
                    $(row).addClass('highlight-row');
                }
            });
            $('.dataTables_length select').select2();

            setTimeout(function() {
                var x = $('.separate-page-header-inner.search .custom-filter').position();
                var d = document.getElementById('eyatraTripApprovalListTable_filter');
                x.left = x.left + 15;
                d.style.left = x.left + 'px';
            }, 500);

            setTimeout(function() {
                $('div[data-provide = "datepicker"]').datepicker({
                    todayHighlight: true,
                    autoclose: true,
                });
            }, 1000);

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
            $scope.getFromDateData = function(query) {
                $('#from_date').val(query);
                dataTable.draw();
            }
            $scope.getToDateData = function(query) {
                $('#to_date').val(query);
                dataTable.draw();
            }
            $scope.reset_filter = function(query) {
                $('#employee_id').val(-1);
                $('#purpose_id').val(-1);
                $('#status_id').val(-1);
                $('#from_date').val('');
                $('#to_date').val('');
                dataTable.draw();
            }
        });

        $rootScope.loading = false;
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------