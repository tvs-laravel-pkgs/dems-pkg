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
            self.gender = (response.data.trip.employee.gender).toLowerCase();
            self.travel_cities = response.data.travel_cities;
            self.trip_claim_rejection_list = response.data.trip_claim_rejection_list;
            self.travel_dates = response.data.travel_dates;

            self.total_amount = response.data.trip.employee.trip_employee_claim.total_amount;
            self.trip_justify = response.data.trip_justify;
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
            $rootScope.loading = false;

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

        $scope.tripClaimApproveOne = function(trip_id) {
            $('#modal_trip_id').val(trip_id);
        }
        $scope.confirmTripClaimApproveOne = function() {
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

        $scope.confirmApproveLocalTripClaim = function() {
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
        }

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