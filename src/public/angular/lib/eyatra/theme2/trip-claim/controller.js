app.component('eyatraTripClaimList', {
    templateUrl: eyatra_trip_claim_list_template_url,
    controller: function(HelperService, $rootScope, $scope, $http) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        var dataTable = $('#eyatra_trip_claim_list_table').DataTable({
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
                url: laravel_routes['listEYatraTripClaimList'],
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

        $('.separate-page-header-content .data-table-title').html('<p class="breadcrumb">Claims</p><h3 class="title">Claimed Trips</h3>');
        //$('.page-header-content .display-inline-block .data-table-title').html('Employees');

        $('.add_new_button').html();

        $scope.deleteTrip = function(id) {
            $('#del').val(id);
        }
        $scope.confirmDeleteTrip = function() {
            $id = $('#del').val();
            $http.get(
                trip_delete_url + '/' + $id,
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
app.component('eyatraTripClaimForm', {
    templateUrl: eyatra_trip_claim_form_template_url,
    controller: function($http, $location, $location, HelperService, $routeParams, $rootScope, $element, $scope) {
        $form_data_url = typeof($routeParams.trip_id) == 'undefined' ? eyatra_trip_claim_form_data_url + '/' : eyatra_trip_claim_form_data_url + '/' + $routeParams.trip_id;
        var self = this;
        var lodgings_removal_id = [];
        var boardings_removal_id = [];
        var local_travels_removal_id = [];

        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        self.eyatra_trip_claim_visit_attachment_url = eyatra_trip_claim_visit_attachment_url;
        $http.get(
            $form_data_url
        ).then(function(response) {
            /*if (!response.data.success) {
                new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: response.data.error,
                }).show();
                $location.path('/eyatra/trip/claim/list')
                $scope.$apply()
                return;
            }*/
            // console.log(response.data.trip);
            // console.log(response.data.travel_dates);
            //console.log(response.data.extras);
            self.employee = response.data.employee;
            self.travel_cities = response.data.travel_cities;
            self.travel_dates = response.data.travel_dates;
            self.extras = response.data.extras;
            self.trip = response.data.trip;
            self.lodgings_removal_id = [];
            self.boardings_removal_id = [];
            self.local_travels_removal_id = [];

            if (self.trip.lodgings.length == 0) {
                self.addNewLodgings();
            }
            if (self.trip.boardings.length == 0) {
                self.addNewBoardings();
            }
            if (self.trip.local_travels.length == 0) {
                self.addNewLocalTralvels();
            }
            setTimeout(function() {
                self.travelCal();
                self.lodgingCal();
                self.boardingCal();
                self.localTravelCal();
            }, 500);

            $rootScope.loading = false;

        });

        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });

        $scope.searchBookedBy;
        $scope.clearSearchBookedBy = function() {
            $scope.searchBookedBy = '';
        };

        $scope.searchTravelMode;
        $scope.clearSearchTravelMode = function() {
            $scope.searchTravelMode = '';
        };

        $scope.searchLodgingCity;
        $scope.clearSearchLodgingCity = function() {
            $scope.searchLodgingCity = '';
        };

        $scope.searchStayType;
        $scope.clearSearchStayType = function() {
            $scope.searchStayType = '';
        };

        $scope.searchBoardingCity;
        $scope.clearSearchBoardingCity = function() {
            $scope.searchBoardingCity = '';
        };

        $scope.searchLocalTravelMode;
        $scope.clearSearchLocalTravelMode = function() {
            $scope.searchLocalTravelMode = '';
        };

        $scope.searchLocalTravelFrom;
        $scope.clearSearchLocalTravelFrom = function() {
            $scope.searchLocalTravelFrom = '';
        };

        $scope.searchLocalTravelTo;
        $scope.clearSearchLocalTravelTo = function() {
            $scope.searchLocalTravelTo = '';
        };


        $scope.getEligibleAmtBasedonCitycategoryGrade = function(grade_id, city_id, expense_type_id, key) {
            if (city_id && grade_id && expense_type_id) {
                console.log(grade_id, city_id, expense_type_id, key);
                $.ajax({
                        url: get_eligible_amount_by_city_category_grade,
                        method: "GET",
                        data: { city_id: city_id, grade_id: grade_id, expense_type_id: expense_type_id },
                    })
                    .done(function(res) {
                        var eligible_amount = res.grade_expense_type ? res.grade_expense_type.eligible_amount : '0.00';
                        console.log(' == eligible_amount ==' + eligible_amount);
                        if (expense_type_id == 3000) { //TRANSPORT EXPENSES
                            self.trip.self_visits[key].eligible_amount = eligible_amount;
                        } else if (expense_type_id == 3001) { // LODGING EXPENSE
                            self.trip.lodgings[key].eligible_amount = eligible_amount;
                        } else if (expense_type_id == 3002) { // BOARDING EXPENSE
                            self.trip.boardings[key].eligible_amount = eligible_amount;
                        } else if (expense_type_id == 3003) { // LOCAL TRAVEL EXPENSE
                            self.trip.local_travels[key].eligible_amount = eligible_amount;
                        }
                        $scope.$apply()
                    })
                    .fail(function(xhr) {
                        console.log(xhr);
                    });
            }
        }

        /*self.addLodgingExpenses = function() {
            self.lodging.push({
                visit_date: '',
                booking_method: 'Self',
                preferred_travel_modes: '',
            });
        }*/
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
                id: '',
                city_id: '',
                lodge_name: '',
                stay_type_id: '',
                eligible_amount: '0.00',
                amount: '',
                tax: '',
                remarks: '',
            });
        }
        self.removeLodging = function(index, lodging_id) {
            if (lodging_id) {
                self.lodgings_removal_id.push(lodging_id);
                $('#lodgings_removal_id').val(JSON.stringify(self.lodgings_removal_id));
            }
            self.trip.lodgings.splice(index, 1);
            setTimeout(function() {
                self.lodgingCal();
            }, 500);
        }

        // Boardings
        self.addNewBoardings = function() {
            self.trip.boardings.push({
                id: '',
                city_id: '',
                expense_name: '',
                date: '',
                amount: '',
                remarks: '',
                eligible_amount: '0.00',
            });
        }
        self.removeBoarding = function(index, boarding_id) {
            if (boarding_id) {
                self.boardings_removal_id.push(boarding_id);
                $('#boardings_removal_id').val(JSON.stringify(self.boardings_removal_id));
            }
            self.trip.boardings.splice(index, 1);
            setTimeout(function() {
                self.boardingCal();
            }, 500);
        }

        // LocalTralvels
        self.addNewLocalTralvels = function() {
            self.trip.local_travels.push({
                id: '',
                mode_id: '',
                date: '',
                from_id: '',
                to_id: '',
                amount: '',
                description: '',
                eligible_amount: '0.00',
            });
        }
        self.removeLocalTralvel = function(index, local_travel_id) {
            if (local_travel_id) {
                self.local_travels_removal_id.push(local_travel_id);
                $('#local_travels_removal_id').val(JSON.stringify(self.local_travels_removal_id));
            }
            self.trip.local_travels.splice(index, 1);
            setTimeout(function() {
                self.localTravelCal();
            }, 500);
        }
        self.travelCal = function() {
            var total_travel_amount = 0;
            $('.travel_amount').each(function() {
                var travel_amount = parseInt($(this).closest('tr').find('#travel_amount').val() || 0);
                //alert(lodging_amount);
                var travel_tax = parseInt($(this).closest('tr').find('#travel_tax').val() || 0);
                if (!$.isNumeric(travel_amount)) {
                    travel_amount = 0;
                }
                if (!$.isNumeric(travel_tax)) {
                    travel_tax = 0;
                }
                travel_current_total = travel_amount + travel_tax;
                total_travel_amount += travel_current_total;
            });
            // console.log(total_travel_amount);
            $('.transport_expenses').text('₹ ' + total_travel_amount.toFixed(2));
            $('.total_travel_amount').val(total_travel_amount.toFixed(2));
            caimTotalAmount();
        }
        self.lodgingCal = function() {
            var total_lodging_amount = 0;
            $('.lodging_amount').each(function() {
                var lodging_amount = parseInt($(this).closest('tr').find('#lodging_amount').val() || 0);
                //alert(lodging_amount);
                var lodging_tax = parseInt($(this).closest('tr').find('#lodging_tax').val() || 0);
                if (!$.isNumeric(lodging_amount)) {
                    lodging_amount = 0;
                }
                if (!$.isNumeric(lodging_tax)) {
                    lodging_tax = 0;
                }
                current_total = lodging_amount + lodging_tax;
                total_lodging_amount += current_total;
            });
            // console.log(total_lodging_amount);
            $('.lodging_expenses').text('₹ ' + total_lodging_amount.toFixed(2));
            $('.total_lodging_amount').val(total_lodging_amount.toFixed(2));
            caimTotalAmount();
        }

        self.boardingCal = function() {
            //alert();
            var total_boarding_amount = 0;
            $('.boarding_amount').each(function() {
                var boarding_amount = parseFloat($(this).closest('tr').find('#boarding_amount').val() || 0);
                var boarding_tax = parseFloat($(this).closest('tr').find('#boarding_tax').val() || 0);
                //console.log(boarding_amount, boarding_tax);
                if (!$.isNumeric(boarding_amount)) {
                    boarding_amount = 0;
                }
                if (!$.isNumeric(boarding_tax)) {
                    boarding_tax = 0;
                }
                current_boarding_total = boarding_amount + boarding_tax;
                total_boarding_amount += current_boarding_total;
            });
            // console.log(total_boarding_amount);
            $('.boarding_expenses').text('₹ ' + total_boarding_amount.toFixed(2));
            $('.total_boarding_amount').val(total_boarding_amount.toFixed(2));
            caimTotalAmount();
        }
        self.boardingCal = function() {
            //alert();
            var total_boarding_amount = 0;
            $('.boarding_amount').each(function() {
                var boarding_amount = parseFloat($(this).closest('tr').find('#boarding_amount').val() || 0);
                var boarding_tax = parseFloat($(this).closest('tr').find('#boarding_tax').val() || 0);
                //console.log(boarding_amount, boarding_tax);
                if (!$.isNumeric(boarding_amount)) {
                    boarding_amount = 0;
                }
                if (!$.isNumeric(boarding_tax)) {
                    boarding_tax = 0;
                }
                current_boarding_total = boarding_amount + boarding_tax;
                total_boarding_amount += current_boarding_total;
            });
            // console.log(total_boarding_amount);
            $('.boarding_expenses').text('₹ ' + total_boarding_amount.toFixed(2));
            $('.total_boarding_amount').val(total_boarding_amount.toFixed(2));
            caimTotalAmount();
        }


        self.localTravelCal = function() {
            //alert();
            var total_local_travel_amount = 0;
            $('.local_travel_amount').each(function() {
                var local_travel_amount = parseFloat($(this).closest('tr').find('#local_travel_amount').val() || 0);
                var local_travel_tax = parseFloat($(this).closest('tr').find('#local_travel_tax').val() || 0);
                // console.log(local_travel_amount, local_travel_tax);
                if (!$.isNumeric(local_travel_amount)) {
                    local_travel_amount = 0;
                }
                if (!$.isNumeric(local_travel_tax)) {
                    local_travel_tax = 0;
                }
                current_boarding_total = local_travel_amount + local_travel_tax;
                total_local_travel_amount += current_boarding_total;
            });
            // console.log(total_local_travel_amount);
            $('.local_expenses').text('₹ ' + total_local_travel_amount.toFixed(2));
            $('.total_local_travel_amount').val(total_local_travel_amount.toFixed(2));
            caimTotalAmount();
        }

        function caimTotalAmount() {

            var total_travel_amount = parseFloat($('.total_travel_amount').val() || 0);
            var total_lodging_amount = parseFloat($('.total_lodging_amount').val() || 0);
            var total_boarding_amount = parseFloat($('.total_boarding_amount').val() || 0);
            var total_local_travel_amount = parseFloat($('.total_local_travel_amount').val() || 0);
            var total_claim_amount = total_travel_amount + total_lodging_amount + total_boarding_amount + total_local_travel_amount;
            $('.claim_total_amount').val(total_claim_amount.toFixed(2));
            $('.claim_total_amount').text('₹ ' + total_claim_amount.toFixed(2));

            // console.log('total claim' + total_claim_amount);
        }

        //Form submit validation
        self.claimSubmit = function() {
            //alert();
            // $('#claim_form').on('submit', function(event) {
            //Add validation rule for dynamically generated name fields
            $('.maxlength_name').each(function() {
                $(this).rules("add", {
                    required: true,
                    maxlength: 191,
                });
            });
            $('.num_amount').each(function() {
                $(this).rules("add", {
                    maxlength: 12,
                    number: true,
                    required: true,
                });
            });
            $('.boarding_expense').each(function() {
                $(this).rules("add", {
                    maxlength: 255,
                    required: true,
                });
            });
        }

        var form_id = '#claim_form';
        $.validator.addClassRules({
            // maxlength_name: {
            //     maxlength: 191,
            //     required: true,
            // },
            // num_amount: {
            //     maxlength: 12,
            //     number: true,
            //     required: true,
            // },
            // boarding_expense: {
            //     maxlength: 255,
            //     required: true,
            // }
            attachments: {
                // extension: "xlsx,xls",
            }
        });

        var v = jQuery(form_id).validate({
            invalidHandler: function(event, validator) {
                new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: 'Kindly check in each tab to fix errors'
                }).show();
            },
            ignore: "",
            rules: {},
            submitHandler: function(form) {
                //console.log(self.item);
                let formData = new FormData($(form_id)[0]);
                $('#submit').button('loading');
                $.ajax({
                        url: eyatra_trip_claim_save_url,
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        //console.log(res.success);
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
                                text: 'Claim saved successfully!!',
                            }).show();

                            $location.path('/eyatra/trip/claim/list')
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
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('eyatraTripClaimView', {
    templateUrl: eyatra_trip_claim_view_template_url,
    controller: function($http, $location, $location, HelperService, $routeParams, $rootScope, $scope) {
        $form_data_url = typeof($routeParams.trip_id) == 'undefined' ? eyatra_trip_claim_view_url + '/' : eyatra_trip_claim_view_url + '/' + $routeParams.trip_id;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        self.eyatra_trip_claim_visit_attachment_url = eyatra_trip_claim_visit_attachment_url;
        self.eyatra_trip_claim_lodging_attachment_url = eyatra_trip_claim_lodging_attachment_url;
        self.eyatra_trip_claim_boarding_attachment_url = eyatra_trip_claim_boarding_attachment_url;
        self.eyatra_trip_claim_local_travel_attachment_url = eyatra_trip_claim_local_travel_attachment_url;
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
            console.log(response.data.trip.lodgings.city);
            console.log(response.data.extras);
            self.extras = response.data.extras;
            self.trip = response.data.trip;
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