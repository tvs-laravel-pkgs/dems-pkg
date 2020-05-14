app.component('eyatraLocalTripVerifications', {
    templateUrl: eyatra_local_trip_verification_list_template_url,
    controller: function(HelperService, $rootScope, $http, $scope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        $http.get(
            local_trip_verification_filter_data_url
        ).then(function(response) {
            console.log(response.data);
            self.employee_list = response.data.employee_list;
            self.purpose_list = response.data.purpose_list;
            self.trip_status_list = response.data.trip_status_list;
            $rootScope.loading = false;
        });
        var dataTable = $('#eyatra_local_trip_verification_table').DataTable({
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
                url: laravel_routes['listLocalTripVerification'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.employee_id = $('#employee_id').val();
                    d.purpose_id = $('#purpose_id').val();
                    d.status_id = $('#status_id').val();
                    d.period = $('#period').val();
                    d.from_date = $('#from_date').val();
                    d.to_date = $('#to_date').val();
                }
            },

            columns: [
                { data: 'action', searchable: false, class: 'action' },
                { data: 'number', name: 'local_trips.number', searchable: true },
                { data: 'created_date', name: 'local_trips.created_date', searchable: false },
                { data: 'ecode', name: 'e.code', searchable: true },
                { data: 'ename', name: 'users.name', searchable: true },
                { data: 'travel_period', name: 'travel_period', searchable: false },
                { data: 'purpose', name: 'purpose.name', searchable: true },
                { data: 'status', name: 'status.name', searchable: true },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();

        setTimeout(function() {
            var x = $('.separate-page-header-inner.search .custom-filter').position();
            var d = document.getElementById('eyatra_local_trip_verification_table_filter');
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
            //alert(query);
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
            // console.log(query);
            $('#from_date').val(query);
            dataTable.draw();
        }
        $scope.getToDateData = function(query) {
            // console.log(query);
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
        $rootScope.loading = false;

    }
});
app.component('eyatraLocalTripVerificationView', {
    templateUrl: local_trip_verification_view_template_url,
    controller: function($http, $location, $routeParams, HelperService, $scope, $route) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.local_travel_attachment_url = local_travel_attachment_url;

        $http.get(
            local_trip_view_url + '/' + $routeParams.trip_id
        ).then(function(response) {
            self.trip = response.data.trip;
            self.claim_status = response.data.claim_status;
            self.trip_reject_reasons = response.data.trip_reject_reasons;
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

        //APPROVE TRIP
        self.approveTrip = function(id) {
            $('#trip_id').val(id);
        }

        $scope.clearSearch = function() {
            $scope.search = '';
        };

        //APPROVE
        $scope.confirmApproveLocalTrip = function() {
            if (local_trip_approve == 0) {
                local_trip_approve = 1;
                $id = $('#trip_id').val();
                $('#local_approve_btn').button('loading');
                $http.get(
                    local_trip_verification_approve_url + '/' + $id,
                ).then(function(response) {
                    console.log(response);
                    $('#local_approve_btn').button('reset');
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
                        custom_noty('success', 'Local Trip Approved Successfully');
                        $('#alert-local-modal-approve').modal('hide');
                        setTimeout(function() {
                            $location.path('/local-trip/verification/list')
                            $scope.$apply()
                        }, 500);
                    }

                });
                local_trip_approve = 0;
            }
        }

        //Reject
        $(document).on('click', '.local_reject_btn', function() {
            var form_id = '#trip-reject-form';

            var v = jQuery(form_id).validate({
                ignore: '',

                submitHandler: function(form) {

                    let formData = new FormData($(form_id)[0]);
                    $('#local_reject_btn').button('loading');
                    $.ajax({
                            url: laravel_routes['rejectLocalTrip'],
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                        })
                        .done(function(res) {
                            console.log(res.success);
                            if (!res.success) {
                                $('#local_reject_btn').button('reset');
                                var errors = '';
                                for (var i in res.errors) {
                                    errors += '<li>' + res.errors[i] + '</li>';
                                }
                                custom_noty('error', errors);
                            } else {
                                custom_noty('success', 'Local Trip Rejected Successfully');
                                $('#alert-local-modal-reject').modal('hide');
                                setTimeout(function() {
                                    $location.path('/local-trip/verification/list')
                                    $scope.$apply()
                                }, 1000);

                            }
                        })
                        .fail(function(xhr) {
                            $('#submit').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                },
            });
        });


    }
});

app.component('eyatraLocalTripVerificationDetailView', {
    templateUrl: local_trip_verification_detail_view_template_url,
    controller: function($http, $location, $routeParams, HelperService, $scope, $route) {

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
                    local_trip_verification_approve_url + '/' + $id,
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
                            $location.path('/local-trip/verification/list')
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
                            url: laravel_routes['rejectLocalTrip'],
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
                                    $location.path('/local-trip/verification/list')
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


    }
});