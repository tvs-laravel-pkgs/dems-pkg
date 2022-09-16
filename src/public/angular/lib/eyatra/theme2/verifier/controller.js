app.component('eyatraOutstationClaimVerificationList', {
    templateUrl: eyatra_outstation_trip_claim_verification_list_template_url,
    controller: function(HelperService, $rootScope, $scope, $http) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        $http.get(
            eyatra_outstation_trip_claim_verification_filter_url
        ).then(function(response) {
            self.employee_list = response.data.employee_list;
            self.outlet_list = response.data.outlet_list;
            self.purpose_list = response.data.purpose_list;
            self.start_date = response.data.start_date;
            self.end_date = response.data.end_date;

            if (response.data.filter_employee_id == '-1') {
                self.filter_employee_id = '-1';
            } else {
                self.filter_employee_id = response.data.filter_employee_id;
            }

            if (response.data.filter_outlet_id == '-1') {
                self.filter_outlet_id = '-1';
            } else {
                self.filter_outlet_id = response.data.filter_outlet_id;
            }

            if (response.data.filter_purpose_id == '-1') {
                self.filter_purpose_id = '-1';
            } else {
                self.filter_purpose_id = response.data.filter_purpose_id;
            }

            var trip_periods = response.data.start_date + ' to ' + response.data.end_date;
            self.trip_periods = trip_periods;

            setTimeout(function() {
                get_employees(self.filter_outlet_id, status = 0);
                $('#from_date').val(self.start_date);
                $('#to_date').val(self.end_date);
                dataTable.draw();
            }, 1500);

            $rootScope.loading = false;
        });

        var dataTable = $('#claim_verification_table').DataTable({
            stateSave: true,
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
                url: laravel_routes['eyatraOutstationClaimVerificationGetData'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.employee_id = $('#employee_id').val();
                    d.purpose_id = $('#purpose_id').val();
                    d.outlet_id = $('#outlet_id').val();
                    d.from_date = $('#from_date').val();
                    d.to_date = $('#to_date').val();
                }
            },

            columns: [
                { data: 'action', searchable: false, class: 'action' },
                { data: 'claim_number', name: 'ey_employee_claims.number', searchable: true },
                { data: 'number', name: 'trips.number', searchable: true },
                { data: 'ecode', name: 'e.code', searchable: true },
                { data: 'ename', name: 'users.name', searchable: true },
                { data: 'outlet_name', name: 'outlets.name', searchable: true },
                { data: 'start_date', name: 'trips.start_date', searchable: true },
                { data: 'end_date', name: 'trips.end_date', searchable: true },
                { data: 'purpose', name: 'purpose.name', searchable: true },
                { data: 'status', searchable: false },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });

        $('.dataTables_length select').select2();

        setTimeout(function() {
            var x = $('.separate-page-header-inner.search .custom-filter').position();
            var d = document.getElementById('claim_verification_table_filter');
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

        $scope.getOutletData = function(outlet_id) {
            $('#outlet_id').val(outlet_id)
            dataTable.draw();
            get_employees(outlet_id, status = 1);
        }

        $(".daterange").daterangepicker({
            autoclose: true,
            locale: {
                cancelLabel: 'Clear',
                format: "DD-MM-YYYY",
                separator: " to ",
            },
            showDropdowns: false,
            autoApply: true,
        });

        $(".daterange").on('change', function() {
            var dates = $("#trip_periods").val();
            var date = dates.split(" to ");
            self.start_date = date[0];
            self.end_date = date[1];
            setTimeout(function() {
                dataTable.draw();
            }, 500);
        });

        $scope.reset_filter = function(query) {
            $('#purpose_id').val(-1);
            $('#employee_id').val(-1);
            $('#outlet_id').val(-1);
            $('#from_date').val('');
            $('#to_date').val('');
            self.trip_periods = '';
            if (self.type_id == 3) {
                get_employees(self.filter_outlet_id, status = 1);
            }
            self.filter_purpose_id = '-1';
            self.filter_employee_id = '-1';
            self.filter_outlet_id = '-1';
            setTimeout(function() {
                dataTable.draw();
            }, 500);
        }

        function get_employees(outlet_id, status) {
            $.ajax({
                    method: "POST",
                    url: laravel_routes['getEmployeeByOutlet'],
                    data: {
                        outlet_id: outlet_id
                    },
                })
                .done(function(res) {
                    self.employee_list = [];
                    if (status == 1) {
                        self.filter_employee_id = '-1';
                    }
                    self.employee_list = res.employee_list;
                    $scope.$apply()
                });
        }
        $rootScope.loading = false;

    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('eyatraOutstationClaimVerificationView', {
    templateUrl: eyatra_outstation_trip_claim_verification_view_template_url,
    controller: function($http, $location, $location, HelperService, $routeParams, $rootScope, $scope) {
        $form_data_url = typeof($routeParams.trip_id) == 'undefined' ? eyatra_trip_claim_verification_one_view_url + '/' : eyatra_trip_claim_verification_one_view_url + '/' + $routeParams.trip_id;
        var self = this;
        var transport_save = 0;
        var lodging_save = 0;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        self.eyatra_trip_claim_verification_one_visit_attachment_url = eyatra_trip_claim_verification_one_visit_attachment_url;
        self.eyatra_trip_claim_verification_one_lodging_attachment_url = eyatra_trip_claim_verification_one_lodging_attachment_url;
        self.eyatra_trip_claim_verification_one_boarding_attachment_url = eyatra_trip_claim_verification_one_boarding_attachment_url;
        self.eyatra_trip_claim_verification_one_local_travel_attachment_url = eyatra_trip_claim_verification_one_local_travel_attachment_url;
        self.eyatra_trip_claim_google_attachment_url = eyatra_trip_claim_google_attachment_url;
        self.eyatra_trip_claim_transport_attachment_url = eyatra_trip_claim_transport_attachment_url;

        $http.get(
            $form_data_url
        ).then(function(response) {
            self.trip = response.data.trip;
            console.log(response.data.trip);
            self.gender = (response.data.trip.employee.gender).toLowerCase();
            self.travel_cities = response.data.travel_cities;
            self.trip_claim_rejection_list = response.data.trip_claim_rejection_list;
            self.travel_dates = response.data.travel_dates;
            self.state_code = response.data.state_code;
            self.total_amount = response.data.trip.employee.trip_employee_claim.total_amount;
            self.trip_justify = response.data.trip_justify;
            if (response.data.trip.trip_attachments.length === 0 && response.data.trip.google_attachments.length === 0) {
                $scope.visit = false;
            } else {
                $scope.visit = true;
            }
            $scope.doSomethingOnClick = function() {
                $scope.visit = false;
            }
            if (self.trip.advance_received) {
                if (parseInt(self.total_amount) > parseInt(self.trip.advance_received)) {
                    self.pay_to_employee = (parseInt(self.total_amount) - parseInt(self.trip.advance_received)).toFixed(2);
                    self.pay_to_company = '0.00';
                } else if (parseInt(self.total_amount) < parseInt(self.trip.advance_received)) {
                    self.pay_to_employee = '0.00';
                    self.pay_to_company = (parseInt(self.trip.advance_received) - parseInt(self.total_amount)).toFixed(2);
                } else {
                    self.pay_to_employee = '0.00';
                    self.pay_to_company = '0.00';
                }
            } else {
                self.pay_to_employee = parseInt(self.total_amount).toFixed(2);
                self.pay_to_company = '0.00';
            }
            $scope.visit = response.data.approval_status;
            self.view = response.data.view;
            $rootScope.loading = false;

        });
        //nodel edit changes on 20-08-2022
        $scope.travelCalculateTax = index => {
            const visitGstin = self.trip.visits[index].self_booking.gstin;
            const visitOtherCharges = parseFloat(self.trip.visits[index].self_booking.other_charges) || 0.00;
            const taxPercentage = self.trip.visits[index].self_booking.tax_percentage;
            const invoiceAmount = self.trip.visits[index].self_booking.invoice_amount;
            console.log(invoiceAmount);
            const gstCode = visitGstin.substr(0, 2);
            if (self.trip.employee_gst_code && self.trip.visits[index].self_booking.amount) {
                let amount = self.trip.visits[index].self_booking.amount;
                if (self.trip.employee_gst_code === gstCode) {
                    let cgstPercentage = taxPercentage / 2;
                    let sgstPercentage = taxPercentage / 2;
                    self.trip.visits[index].self_booking.cgst = parseFloat(amount * (cgstPercentage / 100)).toFixed(2);
                    self.trip.visits[index].self_booking.sgst = parseFloat(amount * (sgstPercentage / 100)).toFixed(2);
                    self.trip.visits[index].self_booking.igst = 0.00;
                } else {
                    let igstPercentage = taxPercentage;
                    self.trip.visits[index].self_booking.cgst = 0.00;
                    self.trip.visits[index].self_booking.sgst = 0.00;
                    self.trip.visits[index].self_booking.igst = parseFloat(amount * (igstPercentage / 100)).toFixed(2);
                }
            }
            const transportAmount = parseFloat(self.trip.visits[index].self_booking.amount) || 0.00;
            const transportCgst = parseFloat(self.trip.visits[index].self_booking.cgst) || 0.00;
            const transportSgst = parseFloat(self.trip.visits[index].self_booking.sgst) || 0.00;
            const transportIgst = parseFloat(self.trip.visits[index].self_booking.igst) || 0.00;

            const Total = parseFloat(transportAmount + transportCgst + transportSgst + transportIgst + visitOtherCharges).toFixed(2);
            console.log(Total, transportAmount, transportCgst, transportSgst, transportIgst);
            self.trip.visits[index].self_booking.round_off = parseFloat(invoiceAmount - Total).toFixed(2);
            console.log(self.trip.visits[index].self_booking.round_off);
        }
        $scope.fareDetailGstChange = (index, gst_number) => {
            //self.trip.visits[index]['fare_gst_detail'] = '';
            self.trip.visits[index]['gstin_name'] = '';
            self.trip.visits[index]['gstin_address'] = '';
            self.trip.visits[index]['gstin_state_code'] = '';
            if (gst_number && gst_number.length == 15) {
                $http({
                    url: laravel_routes['getGstInData'],
                    method: 'GET',
                    params: { 'gst_number': gst_number }
                }).then(function(res) {
                    // console.log(res);
                    if (!res.data.success) {
                        var errors = '';
                        if (res.data.errors) {
                            for (var i in res.data.errors) {
                                errors += '<li>' + res.data.errors[i] + '</li>';
                            }
                        }
                        if (res.data.error) {
                            errors += '<li>' + res.data.error + '</li>';
                        }
                        custom_noty('error', errors);
                    } else {
                        self.trip.visits[index]['gstin_state_code'] = res.data.gst_data.StateCode;
                        self.trip.visits[index]['gstin_name'] = res.data.gst_data.LegalName ? res.data.gst_data.LegalName : res.data.gst_data.TradeName;
                        self.trip.visits[index]['gstin_address'] = self.trip.visits[index]['gstin_name'] + ',' + res.data.gst_data.AddrLoc + ',' + res.data.gst_data.AddrSt + ',' + res.data.gst_data.AddrFlno + ',' + res.data.gst_data.AddrPncd;
                        //self.trip.visits[index]['fare_gst_detail'] = res.data.gst_data.LegalName ? res.data.gst_data.LegalName : res.data.gst_data.TradeName;
                    }
                });
            }
        }
        $scope.lodgeDetailGstChange = (index, gst_number) => {
            self.trip.lodgings[index].lodge_name = '';
            self.trip.lodgings[index].gstin_address = '';
            self.trip.lodgings[index].gstin_state_code = '';
            if (gst_number && gst_number.length == 15) {
                $http({
                    url: laravel_routes['getGstInData'],
                    method: 'GET',
                    params: { 'gst_number': gst_number }
                }).then(function(res) {
                    // console.log(res);
                    if (!res.data.success) {
                        var errors = '';
                        if (res.data.errors) {
                            for (var i in res.data.errors) {
                                errors += '<li>' + res.data.errors[i] + '</li>';
                            }
                        }
                        if (res.data.error) {
                            errors += '<li>' + res.data.error + '</li>';
                        }
                        custom_noty('error', errors);
                    } else {
                        self.trip.lodgings[index].gstin_state_code = res.data.gst_data.StateCode;
                        self.trip.lodgings[index].lodge_name = res.data.gst_data.LegalName ? res.data.gst_data.LegalName : res.data.gst_data.TradeName;
                        self.trip.lodgings[index].gstin_address = self.trip.visits[index].gstin_name + ',' + res.data.gst_data.AddrLoc + ',' + res.data.gst_data.AddrSt + ',' + res.data.gst_data.AddrFlno + ',' + res.data.gst_data.AddrPncd;
                        //self.trip.lodgings[index].lodge_name = res.data.gst_data.LegalName ? res.data.gst_data.LegalName : res.data.gst_data.TradeName;
                    }
                });
            }
        }
        $scope.lodgeCalculateTax = index => {
            const visitGstin = self.trip.lodgings[index].gstin;
            const taxPercentage = self.trip.lodgings[index].tax_percentage;
            const invoiceAmount = self.trip.lodgings[index].invoice_amount;
            const gstCode = visitGstin.substr(0, 2);
            if (self.trip.employee_gst_code && self.trip.lodgings[index].amount) {
                let amount = self.trip.lodgings[index].amount;
                if (self.trip.employee_gst_code === gstCode) {
                    let cgstPercentage = taxPercentage / 2;
                    let sgstPercentage = taxPercentage / 2;
                    self.trip.lodgings[index].cgst = parseFloat(amount * (cgstPercentage / 100)).toFixed(2);
                    self.trip.lodgings[index].sgst = parseFloat(amount * (sgstPercentage / 100)).toFixed(2);
                    self.trip.lodgings[index].igst = 0.00;
                } else {
                    let igstPercentage = taxPercentage;
                    self.trip.lodgings[index].cgst = 0.00;
                    self.trip.lodgings[index].sgst = 0.00;
                    self.trip.lodgings[index].igst = parseFloat(amount * (igstPercentage / 100)).toFixed(2);
                }
            }
            const lodgingAmount = parseFloat(self.trip.lodgings[index].amount) || 0.00;
            const lodgingCgst = parseFloat(self.trip.lodgings[index].cgst) || 0.00;
            const lodgingSgst = parseFloat(self.trip.lodgings[index].sgst) || 0.00;
            const lodgingIgst = parseFloat(self.trip.lodgings[index].igst) || 0.00;
            const Total = parseFloat(lodgingAmount + lodgingCgst + lodgingSgst + lodgingIgst).toFixed(2);
            self.trip.lodgings[index].round_off = parseFloat(invoiceAmount - Total).toFixed(2);
            console.log(Total, self.trip.lodgings[index].round_off);
        }
        $scope.onClickHasMultipleTaxInvoice = (id, val, lodgingIndex) => {
            //console.log(id);

            self.lodgingTaxInvoiceModalIndex = lodgingIndex;
            //$('#lodgingTaxInvoiceView').modal('hide');
            if (val == 'Yes') {
                if (self.trip.lodgings[lodgingIndex].gstin != '') {
                    //LODGE
                    self.lodgingTaxInvoice = {};
                    self.drywashTaxInvoice = {};
                    self.boardingTaxInvoice = {};
                    self.othersTaxInvoice = {};
                    self.roundoffTaxInvoice = {};
                    self.grandTotalTaxInvoice = {};
                    self.trip.lodgings[lodgingIndex].grandtotal_tax_invoice = {};
                    //GRAND TOTAL
                    self.trip.lodgings[self.lodgingTaxInvoiceModalIndex].tax_without_tax_amount = self.trip.lodgings[lodgingIndex].grandtotal_tax_invoice.without_tax_amount;
                    self.trip.lodgings[self.lodgingTaxInvoiceModalIndex].tax_cgst_amount = self.trip.lodgings[lodgingIndex].grandtotal_tax_invoice.cgst;
                    self.trip.lodgings[self.lodgingTaxInvoiceModalIndex].tax_sgst_amount = self.trip.lodgings[lodgingIndex].grandtotal_tax_invoice.sgst;
                    self.trip.lodgings[self.lodgingTaxInvoiceModalIndex].tax_igst_amount = self.trip.lodgings[lodgingIndex].grandtotal_tax_invoice.igst;
                    self.trip.lodgings[self.lodgingTaxInvoiceModalIndex].tax_invoice_amount = self.trip.lodgings[lodgingIndex].tax_invoice_amount;

                    // $scope.calculateLodgeTaxInvoiceAmount();

                    // $('#lodgingTaxInvoiceView').modal('show');
                } else {
                    custom_noty('error', "Kindly enter GSTIN and try after that");
                    self.trip.lodgings[lodgingIndex].has_multiple_tax_invoice = "No";
                    self.trip.lodgings[lodgingIndex].tax_invoice_amount = '';
                    self.lodgingTaxInvoiceModalIndex = '';
                    self.lodgingTaxInvoice = {};
                    self.drywashTaxInvoice = {};
                    self.boardingTaxInvoice = {};
                    self.othersTaxInvoice = {};
                    self.roundoffTaxInvoice = {};
                    self.grandTotalTaxInvoice = {};
                    $('#lodging_has_multiple_tax_invoice_active_' + lodgingIndex).attr('disabled', true);
                    $('#lodging_has_multiple_tax_invoice_inactive_' + lodgingIndex).prop('checked', true);
                }
            } else {
                //NO
                $('#lodgingTaxInvoiceView').modal('show');
                self.lodgingTaxInvoiceModalIndex = '';
                self.lodgingTaxInvoice = {};
                self.drywashTaxInvoice = {};
                self.boardingTaxInvoice = {};
                self.othersTaxInvoice = {};
                self.roundoffTaxInvoice = {};
                self.grandTotalTaxInvoice = {};
            }
        }
        $scope.calculateLodgeTaxInvoiceAmount = (index) => {
            let lodgeWithoutTaxAmount = parseFloat(self.trip.lodgings[index].lodging_tax_invoice.without_tax_amount) || 0;
            let lodgeTaxPercentage = parseFloat(self.trip.lodgings[index].lodging_tax_invoice.tax_percentage) || 0;
            let lodgeCgst = 0;
            let lodgeSgst = 0;
            let lodgeIgst = 0;
            let lodgeTotal = 0;
            let drywashWithoutTaxAmount = parseFloat(self.trip.lodgings[index].drywash_tax_invoice.without_tax_amount) || 0;
            let drywashTaxPercentage = parseFloat(self.trip.lodgings[index].drywash_tax_invoice.tax_percentage) || 0;
            let drywashCgst = 0;
            let drywashSgst = 0;
            let drywashIgst = 0;
            let drywashTotal = 0;
            let boardingWithoutTaxAmount = parseFloat(self.trip.lodgings[index].boarding_tax_invoice.without_tax_amount) || 0;
            let boardingTaxPercentage = parseFloat(self.trip.lodgings[index].boarding_tax_invoice.tax_percentage) || 0;
            let boardingCgst = 0;
            let boardingSgst = 0;
            let boardingIgst = 0;
            let boardingTotal = 0;
            let othersWithoutTaxAmount = parseFloat(self.trip.lodgings[index].others_tax_invoice.without_tax_amount) || 0;
            let othersTaxPercentage = parseFloat(self.trip.lodgings[index].others_tax_invoice.tax_percentage) || 0;
            let othersCgst = 0;
            let othersSgst = 0;
            let othersIgst = 0;
            let othersTotal = 0;
            let roundoffTotal = 0;
            let base = 0;
            let cgst = 0;
            let sgst = 0;
            let igst = 0;
            let grandTotal = 0;

            //LODGE GST CALCULATION
            if (lodgeWithoutTaxAmount && self.trip.lodgings[index].gstin) {
                const lodgeGstin = self.trip.lodgings[index].gstin;
                let lodgeCgstPerc = lodgeSgstPerc = lodgeIgstPerc = 0;
                const lodgeGstCode = lodgeGstin.substr(0, 2);
                console.log(lodgeTaxPercentage, lodgeWithoutTaxAmount);
                let lodgePercentage = 12;
                if (lodgeWithoutTaxAmount >= 7500) {
                    lodgePercentage = 18;
                }

                if (lodgeGstCode == self.state_code) {
                    lodgeCgstPerc = lodgeSgstPerc = lodgePercentage / 2;
                } else {
                    lodgeIgstPerc = lodgePercentage;
                }
                lodgeCgst = lodgeWithoutTaxAmount * (lodgeCgstPerc / 100);
                lodgeSgst = lodgeWithoutTaxAmount * (lodgeSgstPerc / 100);
                lodgeIgst = lodgeWithoutTaxAmount * (lodgeIgstPerc / 100);
                lodgeTotal = lodgeWithoutTaxAmount + lodgeCgst + lodgeSgst + lodgeIgst;
                self.trip.lodgings[index].lodging_tax_invoice.tax_percentage = lodgePercentage;
            }
            //DRYWASH GST CALCULATION
            // drywashTotal = drywashWithoutTaxAmount + drywashCgst + drywashSgst + drywashIgst;
            if (drywashWithoutTaxAmount && drywashTaxPercentage && self.trip.lodgings[index].gstin) {
                const drywashGstin = self.trip.lodgings[index].gstin;
                let drywashCgstPerc = drywashSgstPerc = drywashIgstPerc = 0;
                const drywashGstCode = drywashGstin.substr(0, 2);
                let drywashPercentage = drywashTaxPercentage;
                console.log(drywashPercentage, drywashWithoutTaxAmount, self.state_code);
                if (drywashGstCode == self.state_code) {
                    drywashCgstPerc = drywashSgstPerc = drywashPercentage / 2;
                } else {
                    drywashIgstPerc = drywashPercentage;
                }

                drywashCgst = drywashWithoutTaxAmount * (drywashCgstPerc / 100);
                drywashSgst = drywashWithoutTaxAmount * (drywashSgstPerc / 100);
                drywashIgst = drywashWithoutTaxAmount * (drywashIgstPerc / 100);
                drywashTotal = drywashWithoutTaxAmount + drywashCgst + drywashSgst + drywashIgst;
                console.log(drywashCgst, drywashSgst, drywashIgst);
            }
            //boardingTotal = boardingWithoutTaxAmount + boardingCgst + boardingSgst + boardingIgst;
            //Boarding GST CALCULATION
            if (boardingWithoutTaxAmount && boardingTaxPercentage && self.trip.lodgings[index].gstin) {
                const boardingGstin = self.trip.lodgings[index].gstin;
                let boardingCgstPerc = boardingSgstPerc = boardingIgstPerc = 0;
                const boardingGstCode = boardingGstin.substr(0, 2);
                let boardingPercentage = boardingTaxPercentage;

                if (boardingGstCode == self.state_code) {
                    boardingCgstPerc = boardingSgstPerc = boardingPercentage / 2;
                } else {
                    boardingIgstPerc = boardingPercentage;
                }

                boardingCgst = boardingWithoutTaxAmount * (boardingCgstPerc / 100);
                boardingSgst = boardingWithoutTaxAmount * (boardingSgstPerc / 100);
                boardingIgst = boardingWithoutTaxAmount * (boardingIgstPerc / 100);
                boardingTotal = boardingWithoutTaxAmount + boardingCgst + boardingSgst + boardingIgst;
            }
            // othersTotal = othersWithoutTaxAmount + othersCgst + othersSgst + othersIgst;
            //OTHERS GST CALCULATION
            if (othersWithoutTaxAmount && othersTaxPercentage && self.trip.lodgings[index].gstin) {
                const othersGstin = self.trip.lodgings[index].gstin;
                let othersCgstPerc = othersSgstPerc = othersIgstPerc = 0;
                const othersGstCode = othersGstin.substr(0, 2);
                let othersPercentage = othersTaxPercentage;

                if (othersGstCode == self.state_code) {
                    othersCgstPerc = othersSgstPerc = othersPercentage / 2;
                } else {
                    othersIgstPerc = othersPercentage;
                }

                othersCgst = othersWithoutTaxAmount * (othersCgstPerc / 100);
                othersSgst = othersWithoutTaxAmount * (othersSgstPerc / 100);
                othersIgst = othersWithoutTaxAmount * (othersIgstPerc / 100);
                othersTotal = othersWithoutTaxAmount + othersCgst + othersSgst + othersIgst;
            }
            base = lodgeWithoutTaxAmount + drywashWithoutTaxAmount + boardingWithoutTaxAmount + othersWithoutTaxAmount;
            cgst = lodgeCgst + drywashCgst + boardingCgst + othersCgst;
            sgst = lodgeSgst + drywashSgst + boardingSgst + othersSgst;
            igst = lodgeIgst + drywashIgst + boardingIgst + othersIgst;
            grandTotal = lodgeTotal + drywashTotal + boardingTotal + othersTotal; //+ roundoffTotal;
            roundoffTotal = self.trip.lodgings[index].invoice_amount - grandTotal;
            //console.log(self.trip.lodgings[self.lodgingTaxInvoiceModalIndex]['invoice_amount'], grandTotal);

            self.trip.lodgings[index].grandtotal_tax_invoice = {};

            self.trip.lodgings[index].lodging_tax_invoice.cgst = parseFloat(lodgeCgst).toFixed(2);
            self.trip.lodgings[index].lodging_tax_invoice.sgst = parseFloat(lodgeSgst).toFixed(2);
            self.trip.lodgings[index].lodging_tax_invoice.igst = parseFloat(lodgeIgst).toFixed(2);
            self.trip.lodgings[index].lodging_tax_invoice.total = parseFloat(lodgeTotal).toFixed(2);
            self.trip.lodgings[index].drywash_tax_invoice.cgst = parseFloat(drywashCgst).toFixed(2);
            self.trip.lodgings[index].drywash_tax_invoice.sgst = parseFloat(drywashSgst).toFixed(2);
            self.trip.lodgings[index].drywash_tax_invoice.igst = parseFloat(drywashIgst).toFixed(2);
            self.trip.lodgings[index].drywash_tax_invoice.total = parseFloat(drywashTotal).toFixed(2);
            self.trip.lodgings[index].boarding_tax_invoice.cgst = parseFloat(boardingCgst).toFixed(2);
            self.trip.lodgings[index].boarding_tax_invoice.sgst = parseFloat(boardingSgst).toFixed(2);
            self.trip.lodgings[index].boarding_tax_invoice.igst = parseFloat(boardingIgst).toFixed(2);
            self.trip.lodgings[index].boarding_tax_invoice.total = parseFloat(boardingTotal).toFixed(2);
            self.trip.lodgings[index].others_tax_invoice.cgst = parseFloat(othersCgst).toFixed(2);
            self.trip.lodgings[index].others_tax_invoice.sgst = parseFloat(othersSgst).toFixed(2);
            self.trip.lodgings[index].others_tax_invoice.igst = parseFloat(othersIgst).toFixed(2);
            self.trip.lodgings[index].others_tax_invoice.total = parseFloat(othersTotal).toFixed(2);
            self.trip.lodgings[index].grandtotal_tax_invoice.without_tax_amount = parseFloat(base).toFixed(2);
            self.trip.lodgings[index].grandtotal_tax_invoice.cgst = parseFloat(cgst).toFixed(2);
            self.trip.lodgings[index].grandtotal_tax_invoice.sgst = parseFloat(sgst).toFixed(2);
            self.trip.lodgings[index].grandtotal_tax_invoice.igst = parseFloat(igst).toFixed(2);
            self.trip.lodgings[index].tax_invoice_amount = parseFloat(grandTotal).toFixed(2);
            self.trip.lodgings[index].roundoff_tax_invoice.total = parseFloat(roundoffTotal).toFixed(2);
            //self.trip.lodgings[index].tax_invoice_amount = grandTotal;
            self.trip.lodgings[index].amount = parseFloat(base).toFixed(2);
            self.trip.lodgings[index].cgst = parseFloat(cgst).toFixed(2);
            self.trip.lodgings[index].sgst = parseFloat(sgst).toFixed(2);
            self.trip.lodgings[index].igst = parseFloat(igst).toFixed(2);
            self.trip.lodgings[index].round_off = parseFloat(roundoffTotal).toFixed(2);
            self.trip.lodgings[index].tax_percentage = '--';
            self.trip.lodgings[index].total = parseFloat(self.trip.lodgings[index].tax_invoice_amount + self.trip.lodgings[index].round_off).toFixed(2);
        }

        $scope.onSubmitLodgeTaxInvoice = (index) => {
            if (self.trip.lodgings[index].roundoff_tax_invoice.total > 1 || self.trip.lodgings[index].roundoff_tax_invoice.total < -1) {
                custom_noty('error', "Round Off Amount is with in +1 or -1");
            }
            self.trip.lodgings[self.lodgingTaxInvoiceModalIndex].tax_cgst_amount = self.trip.lodgings[index].grandtotal_tax_invoice.cgst;
            self.trip.lodgings[self.lodgingTaxInvoiceModalIndex].tax_sgst_amount = self.trip.lodgings[index].grandtotal_tax_invoice.sgst;
            self.trip.lodgings[self.lodgingTaxInvoiceModalIndex].tax_igst_amount = self.trip.lodgings[index].grandtotal_tax_invoice.igst;
            self.trip.lodgings[self.lodgingTaxInvoiceModalIndex].tax_invoice_amount = self.trip.lodgings[index].tax_invoice_amount;

            self.lodgingTaxInvoiceModalIndex = '';
            self.lodgingTaxInvoice = {};
            self.drywashTaxInvoice = {};
            self.boardingTaxInvoice = {};
            self.othersTaxInvoice = {};
            self.roundoffTaxInvoice = {};
            self.grandTotalTaxInvoice = {};

            $('#lodgingTaxInvoiceView').modal('hide');
        }
        //end of nodel edit changes

        // UPDATE ATTACHMENT STATUS BY KARTHICK T ON 20-01-2022
        $scope.updateAttchementStatus = function(attchement_id) {
            if (attchement_id) {
                this.view_status = 1;
                $http.post(
                    laravel_routes['updateAttachmentStatus'], {
                        id: attchement_id,
                    }
                ).then(function(res) {
                    if (res.data.success) {
                        $scope.visit = res.data.approval_status;
                    }
                });
            }
        }
        // UPDATE ATTACHMENT STATUS BY KARTHICK T ON 20-01-2022

        $(document).on('mouseover', ".separate-file-attachment", function() {
            var $this = $(this);

            if (this.offsetWidth <= this.scrollWidth && !$this.attr('title')) {
                $this.tooltip({
                    title: $this.children().children(".attachment-file-name").text(),
                    placement: "top"
                });
                $this.tooltip('show');
            }
        });

        $scope.searchRejectedReason;
        $scope.clearSearchRejectedReason = function() {
            $scope.searchRejectedReason = '';
        };

        $scope.tripClaimApproveOne = function(trip_id) {
            $('#modal_trip_id').val(trip_id);
        }
        //nodel edit changes on 20-08-2022
        $(document).on('click', '.claim_submit_btn', function() {
            self.enable_switch_tab = false;
            //GET ACTIVE FORM 
            //alert();
            transport_save = 1;
            lodging_save = 1;
            boarding_save = 1;
            other_expense_save = 1;
            trip_attachment_save = 1;

            var current_form = $(this).attr('data-submit_type');
            var next_form = $(this).attr('data-next_submit_type');
            $('#claim_' + current_form + '_expense_form').submit();
            // $timeout(function() {
            // console.log(' == self.enable_switch_tab ==');
            // console.log(self.enable_switch_tab);
            if (self.enable_switch_tab) {
                $('.tab_li').removeClass('active');
                $('.tab_' + next_form).addClass('active');
                $('.tab-pane').removeClass('in active');
                $('#' + next_form + '-expenses').addClass('in active');
            }
            // }, 1000);
        });
        //TRANSPORT FORM SUBMIT
        var form_transport_id = '#claim_transport_expense_form';
        var v = jQuery(form_transport_id).validate({
            ignore: "",
            rules: {},
            errorElement: "div", // default is 'label'
            errorPlacement: function(error, element) {
                error.insertAfter(element.parent())
            },
            submitHandler: function(form) {
                if (transport_save) {
                    transport_save = 0;
                    let formData = new FormData($(form_transport_id)[0]);
                    $('#transport_submit').html('loading');
                    $("#transport_submit").attr("disabled", true);
                    self.enable_switch_tab = false;
                    $.ajax({
                            url: eyatra_trip_verifier_claim_save_url,
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                            async: false,
                        })
                        .done(function(res) {
                            // console.log(res);
                            if (!res.success) {
                                $('#transport_submit').html('Save');
                                $("#transport_submit").attr("disabled", false);
                                var errors = '';
                                for (var i in res.errors) {
                                    errors += '<li>' + res.errors[i] + '</li>';
                                }
                                custom_noty('error', errors);
                                self.enable_switch_tab = false;
                                $scope.$apply()
                            } else {

                                custom_noty('success', 'Transport expenses saved successfully!');
                                // $(res.lodge_checkin_out_date_range_list).each(function(key, val) {
                                //     self.trip.lodgings[key].date_range_list = val;
                                // });// $scope.$apply()
                                // $('.tab_li').removeClass('active');
                                // $('.tab_lodging').addClass('active');
                                // $('.tab-pane').removeClass('in active');
                                // $('#lodging-expenses').addClass('in active');
                                //self.transport_attachment_removal_ids = [];
                                $('#transport_attach_removal_ids').val('');
                                //self.enable_switch_tab = true;
                                $scope.$apply();
                                //$('#transport_submit').html('Save & Next');
                                $("#transport_submit").attr("disabled", false);
                                //$('#claim_lodge_expense_form').trigger("reset");
                                //$('#claim_board_expense_form').trigger("reset");
                            }
                        })
                        .fail(function(xhr) {
                            //$('#transport_submit').html('Save & Next');
                            //$("#transport_submit").attr("disabled", false);
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            },
        });

        //LODGING FORM SUBMIT
        //TRANSPORT FORM SUBMIT
        var form_lodge_id = '#claim_lodge_expense_form';
        var v = jQuery(form_lodge_id).validate({
            ignore: "",
            rules: {},
            errorElement: "div", // default is 'label'
            errorPlacement: function(error, element) {
                error.insertAfter(element.parent())
            },
            submitHandler: function(form) {
                if (lodging_save) {
                    lodging_save = 0;
                    let formData = new FormData($(form_lodge_id)[0]);
                    $('#lodge_submit').html('loading');
                    $("#lodge_submit").attr("disabled", true);
                    //self.enable_switch_tab = false;
                    $.ajax({
                            url: eyatra_trip_verifier_claim_save_url,
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                            async: false,
                        })
                        .done(function(res) {
                            // console.log(res);
                            if (!res.success) {
                                $('#lodge_submit').html('Save');
                                $("#lodge_submit").attr("disabled", false);
                                var errors = '';
                                for (var i in res.errors) {
                                    errors += '<li>' + res.errors[i] + '</li>';
                                }
                                custom_noty('error', errors);
                                //self.enable_switch_tab = false;
                                $scope.$apply()
                            } else {

                                custom_noty('success', 'Lodge expenses saved successfully!');
                                // $(res.lodge_checkin_out_date_range_list).each(function(key, val) {
                                //     self.trip.lodgings[key].date_range_list = val;
                                // });// $scope.$apply()
                                // $('.tab_li').removeClass('active');
                                // $('.tab_lodging').addClass('active');
                                // $('.tab-pane').removeClass('in active');
                                // $('#lodging-expenses').addClass('in active');
                                //REFRESH LODGINGS
                                self.trip.lodgings = res.saved_lodgings.lodgings;
                                self.lodgings_removal_id = [];
                                self.lodgings_attachment_removal_ids = [];
                                $('#lodgings_attach_removal_ids').val('');
                                //self.enable_switch_tab = true;
                                $scope.$apply();
                                //$('#lodge_submit').html('Save & Next');
                                $("#lodge_submit").attr("disabled", false);
                                //$('#claim_lodge_expense_form').trigger("reset");
                                //$('#claim_board_expense_form').trigger("reset");
                            }
                        })
                        .fail(function(xhr) {
                            //$('#lodge_submit').html('Save & Next');
                            //$("#lodge_submit").attr("disabled", false);
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            },
        });

        // end of nodel edit changes on 20-08-2022

        //Approve

        $(document).on('click', '.outstation_verifier_btn', function() {
            var form_id = '#verifier-approve-form';
            var v = jQuery(form_id).validate({
                ignore: '',

                submitHandler: function(form) {

                    let formData = new FormData($(form_id)[0]);
                    $('#outstation_verifier_btn').button('loading');
                    $.ajax({
                            url: laravel_routes['approveOutstationTripClaimVerification'],
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                        })
                        .done(function(res) {
                            console.log(res.success);
                            if (!res.success) {
                                $('#outstation_verifier_btn').button('reset');
                                var errors = '';
                                for (var i in res.errors) {
                                    errors += '<li>' + res.errors[i] + '</li>';
                                }
                                custom_noty('error', errors);
                            } else {
                                $noty = new Noty({
                                    type: 'success',
                                    layout: 'topRight',
                                    text: 'Trips Claim Approved Successfully',
                                }).show();
                                setTimeout(function() {
                                    $noty.close();
                                }, 1000);
                                $('#trip-claim-modal-approve-one').modal('hide');
                                setTimeout(function() {
                                    $location.path('/outstation-trip/claim/verification/list')
                                    $scope.$apply()
                                }, 500);
                            }
                        })
                        .fail(function(xhr) {
                            $('#outstation_verifier_btn').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                },
            });
        });
        /*$scope.confirmTripClaimApproveOne = function() {
    $trip_id = $('#modal_trip_id').val();
    $http.get(
        eyatra_outstation_trip_claim_verification_approve_url + '/' + $trip_id,
    ).then(function(response) {
        if (!response.data.success) {
            var errors = '';
            for (var i in res.errors) {
                errors += '<li>' + res.errors[i] + '</li>';
            }
            $noty = new Noty({
                type: 'error',
                layout: 'topRight',
                text: errors,
            }).show();
            setTimeout(function() {
                $noty.close();
            }, 1000);
        } else {
            custom_noty('success', 'Trips Claim Approved Successfully');
            $('#trip-claim-modal-approve-one').modal('hide');
            setTimeout(function() {
                $location.path('/outstation-trip/claim/verification/list')
                $scope.$apply()
            }, 500);

        }

    });
}*/

        //Reject
        $(document).on('click', '.reject_btn', function() {
            var form_id = '#trip-claim-reject-form';
            var v = jQuery(form_id).validate({
                ignore: '',

                submitHandler: function(form) {

                    let formData = new FormData($(form_id)[0]);
                    $('#reject_btn').button('loading');
                    $.ajax({
                            url: laravel_routes['rejectOutstationTripClaimVerification'],
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
                                $noty = new Noty({
                                    type: 'success',
                                    layout: 'topRight',
                                    text: 'Trips Claim Rejected successfully',
                                }).show();
                                setTimeout(function() {
                                    $noty.close();
                                }, 1000);
                                $('#trip-claim-modal-reject-one').modal('hide');
                                setTimeout(function() {
                                    $location.path('/outstation-trip/claim/verification/list')
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

        /* Tooltip */
        $('[data-toggle="tooltip"]').tooltip();


        /* Pane Next Button */
        $('.btn-nxt').on("click", function() {
            $('.editDetails-tabs li.active').next().children('a').trigger("click");
        });
        $('.btn-prev').on("click", function() {
            $('.editDetails-tabs li.active').prev().children('a').trigger("click");
        });

    }
});

app.component('eyatraLocalClaimVerificationList', {
    templateUrl: eyatra_local_trip_claim_verification_list_template_url,
    controller: function(HelperService, $rootScope, $scope, $http) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        $http.get(
            eyatra_outstation_trip_claim_verification_filter_url
        ).then(function(response) {
            self.employee_list = response.data.employee_list;
            self.outlet_list = response.data.outlet_list;
            self.purpose_list = response.data.purpose_list;
            self.start_date = response.data.start_date;
            self.end_date = response.data.end_date;

            if (response.data.filter_employee_id == '-1') {
                self.filter_employee_id = '-1';
            } else {
                self.filter_employee_id = response.data.filter_employee_id;
            }

            if (response.data.filter_outlet_id == '-1') {
                self.filter_outlet_id = '-1';
            } else {
                self.filter_outlet_id = response.data.filter_outlet_id;
            }

            if (response.data.filter_purpose_id == '-1') {
                self.filter_purpose_id = '-1';
            } else {
                self.filter_purpose_id = response.data.filter_purpose_id;
            }

            var trip_periods = response.data.start_date + ' to ' + response.data.end_date;
            self.trip_periods = trip_periods;

            setTimeout(function() {
                get_employees(self.filter_outlet_id, status = 0);
                $('#from_date').val(self.start_date);
                $('#to_date').val(self.end_date);
                dataTable.draw();
            }, 1500);

            $rootScope.loading = false;
        });

        var dataTable = $('#local_claim_verification_table').DataTable({
            stateSave: true,
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
                url: laravel_routes['eyatraLocalClaimVerificationGetData'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.employee_id = $('#employee_id').val();
                    d.purpose_id = $('#purpose_id').val();
                    d.outlet_id = $('#outlet_id').val();
                    d.from_date = $('#from_date').val();
                    d.to_date = $('#to_date').val();
                }
            },

            columns: [
                { data: 'action', searchable: false, class: 'action' },
                { data: 'claim_number', name: 'local_trips.claim_number', searchable: false },
                { data: 'number', name: 'local_trips.number', searchable: true },
                { data: 'ecode', name: 'e.code', searchable: true },
                { data: 'ename', name: 'users.name', searchable: true },
                { data: 'outlet_name', name: 'outlets.name', searchable: true },
                { data: 'start_date', name: 'local_trips.start_date', searchable: true },
                { data: 'end_date', name: 'local_trips.end_date', searchable: true },
                { data: 'purpose', name: 'purpose.name', searchable: true },
                { data: 'status', searchable: false },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });

        $('.dataTables_length select').select2();

        setTimeout(function() {
            var x = $('.separate-page-header-inner.search .custom-filter').position();
            var d = document.getElementById('local_claim_verification_table_filter');
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

        $scope.getOutletData = function(outlet_id) {
            $('#outlet_id').val(outlet_id)
            dataTable.draw();
            get_employees(outlet_id, status = 1);
        }

        $(".daterange").daterangepicker({
            autoclose: true,
            locale: {
                cancelLabel: 'Clear',
                format: "DD-MM-YYYY",
                separator: " to ",
            },
            showDropdowns: false,
            autoApply: true,
        });

        $(".daterange").on('change', function() {
            var dates = $("#trip_periods").val();
            var date = dates.split(" to ");
            self.start_date = date[0];
            self.end_date = date[1];
            setTimeout(function() {
                dataTable.draw();
            }, 500);
        });

        $scope.reset_filter = function(query) {
            $('#purpose_id').val(-1);
            $('#employee_id').val(-1);
            $('#outlet_id').val(-1);
            $('#from_date').val('');
            $('#to_date').val('');
            self.trip_periods = '';
            if (self.type_id == 3) {
                get_employees(self.filter_outlet_id, status = 1);
            }
            self.filter_purpose_id = '-1';
            self.filter_employee_id = '-1';
            self.filter_outlet_id = '-1';
            setTimeout(function() {
                dataTable.draw();
            }, 500);
        }

        function get_employees(outlet_id, status) {
            $.ajax({
                    method: "POST",
                    url: laravel_routes['getEmployeeByOutlet'],
                    data: {
                        outlet_id: outlet_id
                    },
                })
                .done(function(res) {
                    self.employee_list = [];
                    if (status == 1) {
                        self.filter_employee_id = '-1';
                    }
                    self.employee_list = res.employee_list;
                    $scope.$apply()
                });
        }
        $rootScope.loading = false;

    }
});

app.component('eyatraLocalClaimVerificationView', {
    templateUrl: eyatra_local_trip_claim_verification_view_template_url,
    controller: function($http, $location, $location, HelperService, $routeParams, $rootScope, $scope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.local_travel_attachment_url = local_travel_attachment_url;
        self.local_travel_google_attachment_url = local_travel_google_attachment_url;

        $http.get(
            local_trip_view_url + '/' + $routeParams.trip_id
        ).then(function(response) {
            self.trip = response.data.trip;
            self.claim_status = response.data.claim_status;
            self.trip_claim_rejection_list = response.data.trip_claim_rejection_list;
            self.gender = (response.data.trip.employee.gender).toLowerCase();
            console.log(self.trip_reject_reasons);

        });



        var local_trip_approve = 0;
        self.approveTrip = function() {
            self.trip.visits.push({
                visit_date: '',
                booking_method: 'Self',
                preferred_travel_modes: '',
            });
        }

        //TOOLTIP MOUSEOVER
        $(document).on('mouseover', ".attachment-view-list", function() {
            var $this = $(this);

            if (this.offsetWidth <= this.scrollWidth && !$this.attr('title')) {
                $this.tooltip({
                    title: $this.children(".attachment-view-file").text(),
                    placement: "top"
                });
                $this.tooltip('show');
            }
        });

        $('.btn-nxt').on("click", function() {
            $('.editDetails-tabs li.active').next().children('a').trigger("click");
        });
        $('.btn-prev').on("click", function() {
            $('.editDetails-tabs li.active').prev().children('a').trigger("click");
        });

        $(document).on('mouseover', ".separate-file-attachment", function() {
            var $this = $(this);

            if (this.offsetWidth <= this.scrollWidth && !$this.attr('title')) {
                $this.tooltip({
                    title: $this.children().children(".attachment-file-name").text(),
                    placement: "top"
                });
                $this.tooltip('show');
            }
        });

        $scope.searchRejectedReason;
        $scope.clearSearchRejectedReason = function() {
            $scope.searchRejectedReason = '';
        };

        //APPROVE TRIP
        self.approveTrip = function(id) {
            $('#trip_id').val(id);
        }

        $scope.clearSearch = function() {
            $scope.search = '';
        };

        $(document).on('click', '.claim_local_approve_btn', function() {
            if (local_trip_approve == 0) {
                local_trip_approve = 1;
                var form_id = '#local-verifier-form';
                var v = jQuery(form_id).validate({
                    ignore: '',

                    submitHandler: function(form) {

                        let formData = new FormData($(form_id)[0]);
                        $('#claim_local_approve_btn').button('loading');
                        $.ajax({
                                url: laravel_routes['approveLocalTripClaimVerification'],
                                method: "POST",
                                data: formData,
                                processData: false,
                                contentType: false,
                            })
                            .done(function(res) {
                                console.log(res.success);
                                if (!res.success) {
                                    $('#claim_local_approve_btn').button('reset');
                                    var errors = '';
                                    for (var i in res.errors) {
                                        errors += '<li>' + res.errors[i] + '</li>';
                                    }
                                    custom_noty('error', errors);
                                } else {
                                    custom_noty('success', 'Local Trip Claim Approved Successfully');
                                    $('#alert-local-claim-modal-approve').modal('hide');
                                    setTimeout(function() {
                                        $location.path('/local-trip/claim/verification/list')
                                        $scope.$apply()
                                    }, 500);

                                }
                            })
                            .fail(function(xhr) {
                                $('#submit').button('reset');
                                custom_noty('error', 'Something went wrong at server');
                            });
                    },
                });
                local_trip_approve = 0;
            }
        });
        /*$scope.confirmApproveLocalTripClaim = function() {
            $id = $('#trip_id').val();
            $('#claim_local_approve_btn').button('loading');
            if (local_trip_approve == 0) {
                local_trip_approve = 1;
                $http.get(
                    eyatra_local_trip_claim_verification_approve_url + '/' + $id,
                ).then(function(response) {
                    console.log(response);
                    $('#claim_local_approve_btn').button('reset');
                    if (!response.data.success) {
                        var errors = '';
                        for (var i in response.data.errors) {
                            errors += '<li>' + response.data.errors[i] + '</li>';
                        }
                        $noty = new Noty({
                            type: 'error',
                            layout: 'topRight',
                            text: errors,
                        }).show();
                        setTimeout(function() {
                            $noty.close();
                        }, 1000);
                    } else {
                        custom_noty('success', 'Local Trip Claim Approved Successfully');
                        $('#alert-local-claim-modal-approve').modal('hide');
                        setTimeout(function() {
                            $location.path('/local-trip/claim/verification/list')
                            $scope.$apply()
                        }, 500);
                    }

                });
                local_trip_approve = 0;
            }
        }*/

        //Reject
        $(document).on('click', '.claim_local_reject_btn', function() {
            var form_id = '#trip-reject-form';
            var v = jQuery(form_id).validate({
                ignore: '',

                submitHandler: function(form) {

                    let formData = new FormData($(form_id)[0]);
                    $('#claim_local_reject_btn').button('loading');
                    $.ajax({
                            url: laravel_routes['rejectLocalTripClaimVerification'],
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                        })
                        .done(function(res) {
                            console.log(res.success);
                            if (!res.success) {
                                $('#claim_local_reject_btn').button('reset');
                                var errors = '';
                                for (var i in res.errors) {
                                    errors += '<li>' + res.errors[i] + '</li>';
                                }
                                custom_noty('error', errors);
                            } else {
                                custom_noty('success', 'Local Trip Claim Rejected Successfully');
                                $('#alert-local-claim-modal-reject').modal('hide');
                                setTimeout(function() {
                                    $location.path('/local-trip/claim/verification/list')
                                    $scope.$apply()
                                }, 500);

                            }
                        })
                        .fail(function(xhr) {
                            $('#submit').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                },
            });
        });

        /* Tooltip */
        $('[data-toggle="tooltip"]').tooltip();

    }
});