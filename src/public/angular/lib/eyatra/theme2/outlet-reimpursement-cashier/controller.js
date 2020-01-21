app.component('eyatraCashierOutletReimbursement', {
    templateUrl: eyatra_cashier_outlet_reimpursement_list_template_url,
    controller: function(HelperService, $http, $rootScope, $scope, $routeParams, $location) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        var dataTable = $('#outlet_reimpursement_cashier_table').DataTable({
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
                url: laravel_routes['listCashierOutletReimpursement'],
                type: "GET",
                dataType: "json",
                data: function(d) {}
            },

            columns: [
                { data: 'action', searchable: false, class: 'action' },
                { data: 'outlet_name', name: 'outlets.name', searchable: true },
                { data: 'outlet_code', name: 'outlets.code', searchable: true },
                { data: 'cashier_name', name: 'users.name', searchable: true },
                { data: 'cashier_code', name: 'employees.code', searchable: true },
                { data: 'amount', searchable: false },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();
        $('.separate-page-header-content .data-table-title').html('<p class="breadcrumb">Outlet Reimbursement</p><h3 class="title">Reimbursement</h3>');

        // $('.dataTables_length select').select2();
        // setTimeout(function() {
        //     var x = $('.separate-page-header-inner.search .custom-filter').position();
        //     var d = document.getElementById('outlet_reimpursement_cashier_table_filter');
        //     x.left = x.left + 15;
        //     d.style.left = x.left + 'px';
        // }, 500);

        $rootScope.loading = false;
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
//Cashier Outlet reimbursement view
app.component('eyatraCashierOutletReimbursementView', {
    templateUrl: eyatra_outlet_reimpursement_view_template_url,

    controller: function($http, $location, $routeParams, HelperService, $scope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        $http.get(
            eyatra_outlet_reimpursement_view_url + '/' + 'cashier',
        ).then(function(response) {
            console.log(response.data);
            self.reimpurseimpurse_outlet_data = response.data.reimpurseimpurse_outlet_data;
            self.reimbursement_amount = response.data.reimbursement_amount;
            self.reimpurseimpurse_transactions = response.data.reimpurseimpurse_transactions;
        });
    }
});
//------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------