// app.component('eyatraTripBookingUpdates', {
//     templateUrl: eyatra_booking_updates_list_template_url,
//     controller: function(HelperService, $rootScope) {
//         var self = this;
//         self.hasPermission = HelperService.hasPermission;
//         var dataTable = $('#eyatra_trip_booking_updates_table').DataTable({
//             "dom": dom_structure,
//             "language": {
//                 "search": "",
//                 "searchPlaceholder": "Search",
//                 "lengthMenu": "Rows Per Page _MENU_",
//                 "paginate": {
//                     "next": '<i class="icon ion-ios-arrow-forward"></i>',
//                     "previous": '<i class="icon ion-ios-arrow-back"></i>'
//                 },
//             },
//             pageLength: 10,
//             processing: true,
//             serverSide: true,
//             paging: true,
//             ordering: false,
//             ajax: {
//                 url: laravel_routes['listTripBookingUpdates'],
//                 type: "GET",
//                 dataType: "json",
//                 data: function(d) {}
//             },

//             columns: [
//                 { data: 'action', searchable: false, class: 'action' },
//                 { data: 'number', name: 'trips.number', searchable: true },
//                 { data: 'ecode', name: 'e.code', searchable: true },
//                 { data: 'start_date', name: 'v.date', searchable: true },
//                 { data: 'end_date', name: 'v.date', searchable: true },
//                 { data: 'cities', name: 'c.name', searchable: true },
//                 { data: 'purpose', name: 'purpose.name', searchable: true },
//                 { data: 'advance_received', name: 'trips.advance_received', searchable: false },
//                 { data: 'created_at', name: 'trips.created_at', searchable: true },
//                 { data: 'status', name: 'status.name', searchable: true },
//             ],
//             rowCallback: function(row, data) {
//                 $(row).addClass('highlight-row');
//             }
//         });
//         $('.dataTables_length select').select2();
//         $('.page-header-content .display-inline-block .data-table-title').html('Trips Booking Updates');
//         $('.add_new_button').html();
//         $rootScope.loading = false;

//     }
// });
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('eyatraTripBookingUpdatesForm', {
    templateUrl: booking_updates_form_template_url,
    controller: function($http, $location, $location, HelperService, $routeParams, $rootScope, $scope) {
        if (typeof($routeParams.visit_id) == 'undefined') {
            $location.path('/trip/booking-updates')
            $scope.$apply()
            return;
        }
        $form_data_url = booking_updates_form_data_url + '/' + $routeParams.visit_id;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        $http.get(
            $form_data_url
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
                }, 1000);
                $location.path('/trips')
                $scope.$apply()
                return;
            }
            self.visit = response.data.visit;
            console.log(self.visit);
            $(".datepicker").datepicker({
                todayHighlight: true,
                startDate: self.visit.trip.created_at,
                endDate: self.visit.trip.end_date,
                autoclose: true,
            });
            self.travel_mode_list = response.data.travel_mode_list;
            $rootScope.loading = false;
        });

        $scope.roundOffCal = function() {
            //alert();
            let totalValue = 0.00;
            const taxableValue = parseFloat(self.visit.amount) || 0.00;
            const cgstValue = parseFloat(self.visit.cgst) || 0.00;
            const sgstValue = parseFloat(self.visit.sgst) || 0.00;
            const igstValue = parseFloat(self.visit.igst) || 0.00;
            const roundOff = parseFloat(self.visit.round_off) || 0.00;
            const invoiceAmount = parseFloat(self.visit.invoice_amount) || 0.00;
            totalValue = parseFloat(taxableValue + cgstValue + sgstValue + igstValue).toFixed(2);
            self.visit.round_off = parseFloat(invoiceAmount - totalValue).toFixed(2);
        }

        $scope.calculateTax = index => {
            if (self.visit) {
                //self.visit.gstin = '';
                self.visit.cgst = '';
                self.visit.sgst = '';
                self.visit.igst = '';

                //enable if company gstin needed and self.trip.agent_visits[index].toCityGstCode inside if condition
                //self.trip.agent_visits[index].booking.gstin = self.trip.agent_visits[index].toCityGstin;

                const cgstPercentage = sgstPercentage = 2.5;
                const igstPercentage = 5;
                console.log(self.visit.employee_gst_code, self.visit.amount);
                //if (self.trip.employee_gst_code && self.trip.agent_visits[index].booking.amount && self.trip.agent_visits[index].booking.booking_method_id && self.trip.agent_visits[index].booking.booking_method_id != 13) {
                if (self.visit.employee_gst_code && self.visit.amount && self.visit.travel_mode_id && self.visit.travel_mode_id != 12 && self.visit.gstin) {
                    let enteredGstinCode = self.visit.gstin.substr(0, 2);
                    let taxableValue = parseFloat(self.visit.amount);

                    // if (self.trip.employee_gst_code === self.trip.agent_visits[index].toCityGstCode) {
                    if (self.visit.employee_gst_code === enteredGstinCode) {
                        self.visit.cgst = parseFloat(taxableValue * (cgstPercentage / 100)).toFixed(2);
                        self.visit.sgst = parseFloat(taxableValue * (sgstPercentage / 100)).toFixed(2);
                        self.visit.igst = 0.00;
                        self.visit.tax_percentage = cgstPercentage + sgstPercentage;
                    } else {
                        self.visit.cgst = 0.00;
                        self.visit.sgst = 0.00;
                        sself.visit.igst = parseFloat(taxableValue * (igstPercentage / 100)).toFixed(2);
                        self.visit.tax_percentage = igstPercentage;
                    }
                }
            }
        }

        $scope.fareDetailGstChange = (gst_number) => {
            console.log(gst_number);
            self.visit.gstin_name = '';
            self.visit.gstin_address = '';
            self.visit.gstin_state_code = '';
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
                        self.visit.gstin_name = res.data.gst_data.LegalName ? res.data.gst_data.LegalName : res.data.gst_data.TradeName;
                        self.visit.gstin_address = self.visit.gstin_name + ',' + res.data.gst_data.AddrLoc + ',' + res.data.gst_data.AddrSt + ',' + res.data.gst_data.AddrFlno + ',' + res.data.gst_data.AddrPncd;
                        self.visit.gstin_state_code = res.data.gst_data.StateCode;
                    }
                });
            }
        }


        $('input[type="file"]').imageuploadify();
        fileUpload();

        var form_id = '#visit-booking-update-form';
        var v = jQuery(form_id).validate({
            errorPlacement: function(error, element) {
                if (element.attr('name') == 'attachment') {
                    error.appendTo($('.attachment_error'));
                } else {
                    error.insertAfter(element)
                }
            },
            ignore: '',
            rules: {
                'travel_mode_id': {
                    required: true,
                },
                'reference_number': {
                    required: true,
                    maxlength: 191,
                },
                'amount': {
                    required: true,
                    maxlength: 10,
                    number: true,
                },
                'tax': {
                    maxlength: 10,
                    number: true,
                },
                'attachment': {
                    required: true,
                },
            },
            submitHandler: function(form) {

                let formData = new FormData($(form_id)[0]);
                $('#submit').button('loading');
                var trip_id = $('#trip_id').val();
                $.ajax({
                        url: laravel_routes['saveTripBookingUpdates'],
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
                                text: 'Booking details updated successfully',
                                animation: {
                                    speed: 500 // unavailable - no need
                                },
                            }).show();
                            setTimeout(function() {
                                $noty.close();
                            }, 1000);
                            $location.path('/trip/view/' + trip_id)
                            $scope.$apply()
                        }
                    })
                    .fail(function(xhr) {
                        $('#submit').button('reset');
                        custom_noty('error', 'Something went wrong at server');
                    });
            },
        });
        $scope.gstHelper = function() {
            var cgst = $('#cgst').val();
            var sgst = $('#sgst').val();
            var igst = $('#igst').val();

            if (cgst == '' && sgst == '') {
                $('#igst').attr('readonly', false);
                $('#igst').attr('placeholder', 'Eg:133.50');
            } else {
                $('#igst').attr('readonly', true);
                $('#igst').attr('placeholder', 'N/A');
            }

            if (igst == '') {
                $('#cgst').attr('readonly', false);
                $('#sgst').attr('readonly', false);
                $('#cgst').attr('placeholder', 'Eg:113.50');
                $('#sgst').attr('placeholder', 'Eg:123.50');
            } else {
                $('#cgst').attr('readonly', true);
                $('#sgst').attr('readonly', true);
                $('#cgst').attr('placeholder', 'N/A');
                $('#sgst').attr('placeholder', 'N/A');
            }
        }
    }
});