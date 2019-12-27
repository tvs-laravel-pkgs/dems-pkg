app.component('eyatraLocalTrips', {
    templateUrl: eyatra_local_trip_list_template_url,
    controller: function(HelperService, $rootScope, $scope, $http) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.permission = self.hasPermission('trip-add');
        $http.get(
            local_trip_filter_data_url
        ).then(function(response) {
            console.log(response.data);
            self.employee_list = response.data.employee_list;
            self.purpose_list = response.data.purpose_list;
            self.trip_status_list = response.data.trip_status_list;
            $rootScope.loading = false;
        });

        var dataTable = $('#eyatra_local_trip_table').DataTable({
            // stateSave: true,
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
            "bProcessing": true,
            fixedColumns: true,
            scrollX: true,
            scrollCollapse: true,
            paging: true,
            ordering: false,
            fixedColumns: {
                leftColumns: 1,
            },
            ajax: {
                url: laravel_routes['listLocalTrip'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    // d.employee_id = $('#employee_id').val();
                    d.purpose_id = $('#purpose_id').val();
                    d.from_date = $('#from_date').val();
                    d.to_date = $('#to_date').val();
                    d.status_id = $('#status_id').val();
                }
            },

            columns: [
                { data: 'action', searchable: false, class: 'action' },
                { data: 'number', name: 'trips.number', searchable: true },
                { data: 'created_date', name: 'trips.created_date', searchable: false },
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

        //CURRENT DATE SHOW IN DATEPICKER
        setTimeout(function() {
            $('div[data-provide="datepicker"]').datepicker({
                todayHighlight: true,
                autoclose: true,
            });
        }, 1000);

        setTimeout(function() {
            var x = $('.separate-page-header-inner.search .custom-filter').position();
            var d = document.getElementById('eyatra_trip_table_filter');
            x.left = x.left + 15;
            d.style.left = x.left + 'px';
        }, 500);


        // $scope.getEmployeeData = function(query) {
        //     $('#employee_id').val(query);
        //     dataTable.draw();
        // }
        $scope.getPurposeData = function(query) {
            $('#purpose_id').val(query);
            dataTable.draw();
        }
        $scope.getFromDateData = function(query) {
            console.log(query);
            $('#from_date').val(query);
            dataTable.draw();
        }
        $scope.getToDateData = function(query) {
            console.log(query);
            $('#to_date').val(query);
            dataTable.draw();
        }
        $scope.getStatusData = function(query) {
            $('#status_id').val(query);
            dataTable.draw();
        }

        $scope.reset_filter = function(query) {
            // $('#employee_id').val(-1);
            $('#purpose_id').val(-1);
            $('#from_date').val('');
            $('#to_date').val('');
            $('#status_id').val(-1);
            dataTable.draw();
        }
        $scope.reset_filter();
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
                    for (var i in response.data.errors) {
                        errors += '<li>' + response.data.errors[i] + '</li>';
                    }
                    $noty = new Noty({
                        type: 'error',
                        layout: 'topRight',
                        text: errors,
                        animation: {
                            speed: 500 // unavailable - no need
                        },
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 5000);
                } else {
                    $noty = new Noty({
                        type: 'success',
                        layout: 'topRight',
                        text: 'Trip Deleted Successfully',
                        animation: {
                            speed: 500 // unavailable - no need
                        },
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 5000);
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
app.component('eyatraTripLocalForm', {
    templateUrl: local_trip_form_template_url,
    controller: function($http, $location, $location, HelperService, $routeParams, $rootScope, $scope, $timeout) {
        $form_data_url = typeof($routeParams.trip_id) == 'undefined' ? local_trip_form_data_url : local_trip_form_data_url + '/' + $routeParams.trip_id;
        var self = this;
        var arr_ind;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        self.local_travel_attachment_url = local_travel_attachment_url;
        var trip_detail_removal_id = [];
        $http.get(
            $form_data_url
        ).then(function(response) {
            if (!response.data.success) {
                $noty = new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: response.data.error,
                }).show();
                setTimeout(function() {
                    $noty.close();
                }, 1000);
                $location.path('/local-trips')
                return;
            }

            self.trip = response.data.trip;
            self.trip.trip_periods = '';
            self.eligible_date = response.data.eligible_date;

            if (response.data.action == "Edit") {
                if (response.data.trip.start_date && response.data.trip.end_date) {
                    var start_date = response.data.trip.start_date;
                    var end_date = response.data.trip.end_date;
                    trip_periods = response.data.trip.start_date + ' to ' + response.data.trip.end_date;
                    self.trip.trip_periods = trip_periods;
                }
                $(".daterange").daterangepicker({
                    autoclose: true,
                    minDate: new Date(self.eligible_date),
                    locale: {
                        cancelLabel: 'Clear',
                        format: "DD-MM-YYYY",
                        separator: " to ",
                    },
                    showDropdowns: false,
                    startDate: start_date,
                    endDate: end_date,
                    autoApply: true,
                });
            } else {
                setTimeout(function() {
                    $(".daterange").daterangepicker({
                        autoclose: true,
                        minDate: new Date(self.eligible_date),
                        locale: {
                            cancelLabel: 'Clear',
                            format: "DD-MM-YYYY",
                            separator: " to ",
                        },
                        showDropdowns: false,
                        autoApply: true,
                    });
                    $(".daterange").val('');
                }, 500);
            }

            if (self.advance_eligibility == 1) {
                $("#advance").show().prop('disabled', false);
            }
            self.extras = response.data.extras;
            self.action = response.data.action;

            $rootScope.loading = false;
        });

        $(".daterange").on('change', function() {
            var dates = $("#trip_periods").val();
            var date = dates.split(" to ");
            self.trip.start_date = date[0];
            self.trip.end_date = date[1];
        });

        $('.btn-nxt').on("click", function() {
            $('.editDetails-tabs li.active').next().children('a').trigger("click");
        });
        $('.btn-prev').on("click", function() {
            $('.editDetails-tabs li.active').prev().children('a').trigger("click");
        });

        self.searchCity = function(query) {
            if (query) {
                return new Promise(function(resolve, reject) {
                    $http
                        .post(
                            laravel_routes['searchCity'], {
                                key: query,
                            }
                        )
                        .then(function(response) {
                            resolve(response.data);
                        });
                });
            } else {
                return [];
            }
        }
        var form_id = '#local-trip-form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'trip_mode[]': {
                    required: true,
                },
            },
            messages: {
                'trip_mode[]': {
                    required: 'Select Visit Mode',
                },
            },
            invalidHandler: function(event, validator) {
                $noty = new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: 'You have errors,Please check all tabs',
                }).show();
                setTimeout(function() {
                    $noty.close();
                }, 1000);
            },
            submitHandler: function(form) {

                let formData = new FormData($(form_id)[0]);
                $('.btn-submit').button('loading');
                $.ajax({
                        url: laravel_routes['saveLocalTrip'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        // console.log(res.success);
                        if (!res.success) {
                            $('.btn-submit').button('reset');
                            custom_noty('error', res.errors);
                        } else {
                            $noty = new Noty({
                                type: 'success',
                                layout: 'topRight',
                                text: res.message,
                            }).show();
                            setTimeout(function() {
                                $noty.close();
                            }, 1000);
                            $location.path('/local-trips')
                            $scope.$apply()
                        }
                    })
                    .fail(function(xhr) {
                        $('.btn-submit').button('reset');
                        custom_noty('error', 'Something went wrong at server');
                    });
            },
        });
    }
});

app.component('eyatraLocalTripForm', {
    templateUrl: local_trip_visit_form_template_url,
    controller: function($http, $location, $location, HelperService, $routeParams, $rootScope, $scope, $timeout) {
        $form_data_url = typeof($routeParams.trip_id) == 'undefined' ? local_trip_form_data_url : local_trip_form_data_url + '/' + $routeParams.trip_id;
        var self = this;
        var arr_ind;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        self.local_travel_attachment_url = local_travel_attachment_url;
        var trip_detail_removal_id = [];
        var attachment_removal_id = [];
        $http.get(
            $form_data_url
        ).then(function(response) {
            // console.log(response.data);
            if (!response.data.success) {
                $noty = new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: response.data.error,
                }).show();
                setTimeout(function() {
                    $noty.close();
                }, 1000);
                $location.path('/local-trips')
                return;
            }

            self.trip = response.data.trip;
            console.log(self.trip);
            self.trip.trip_periods = '';
            self.eligible_date = response.data.eligible_date;

            if (response.data.action == "Edit") {
                if (response.data.trip.start_date && response.data.trip.end_date) {
                    var start_date = response.data.trip.start_date;
                    var end_date = response.data.trip.end_date;
                    trip_periods = response.data.trip.start_date + ' to ' + response.data.trip.end_date;
                    self.trip.trip_periods = trip_periods;
                    $scope.onChange(start_date, end_date);
                }

                $(".daterange").daterangepicker({
                    autoclose: true,
                    minDate: new Date(self.eligible_date),
                    locale: {
                        cancelLabel: 'Clear',
                        format: "DD-MM-YYYY",
                        separator: " to ",
                    },
                    showDropdowns: false,
                    startDate: start_date,
                    endDate: end_date,
                    autoApply: true,
                });

                setTimeout(function() {
                    self.calculatetotalamount();
                }, 1000);
            } else {
                setTimeout(function() {
                    $(".daterange").daterangepicker({
                        autoclose: true,
                        minDate: new Date(self.eligible_date),
                        locale: {
                            cancelLabel: 'Clear',
                            format: "DD-MM-YYYY",
                            separator: " to ",
                        },
                        showDropdowns: false,
                        autoApply: true,
                    });
                    $(".daterange").val('');
                }, 500);
            }


            if (self.advance_eligibility == 1) {
                $("#advance").show().prop('disabled', false);
            }
            self.extras = response.data.extras;
            self.action = response.data.action;

            $rootScope.loading = false;
        });


        $scope.travelClaimStatus = function(travel_mode_id, index) {
            if (self.extras.eligible_travel_mode_list.includes(travel_mode_id)) {
                if (!self.trip.visit_details[index].eligible_amount) {
                    self.trip.visit_details[index].eligible_amount = {
                        extra_amount: self.trip.visit_details[index].extra_amount,
                        readonly: false
                    };
                } else {
                    self.trip.visit_details[index].eligible_amount.readonly = false;
                    self.trip.visit_details[index].eligible_amount.extra_amount = self.trip.visit_details[index].extra_amount;
                }
            } else {
                if (!self.trip.visit_details[index].eligible_amount) {
                    self.trip.visit_details[index].eligible_amount = {
                        extra_amount: '0.00',
                        readonly: true
                    };
                } else {
                    self.trip.visit_details[index].eligible_amount.readonly = true;
                    self.trip.visit_details[index].eligible_amount.extra_amount = '0';
                }
            }

        }

        $(".daterange").on('change', function() {
            var dates = $("#trip_periods").val();
            var date = dates.split(" to ");
            self.trip.start_date = date[0];
            self.trip.end_date = date[1];
            $scope.onChange(self.trip.start_date, self.trip.end_date);
        });

        var startdate;
        var enddate;
        var id;
        $scope.onChange = function(start_date, end_date) {
            startdate = start_date;
            enddate = end_date;
            console.log(startdate, enddate);
            var arr_length = self.trip.visit_details.length;
            for (var i = 0; i < arr_length; i++) {
                $('.datepicker_' + i).datepicker('destroy');
                //id = 0;
                datecall(startdate, enddate, i);
            }
        }

        //REMOVE VISIT 
        self.removelocaltrip = function(index, local_trip_detail_id) {
            if (local_trip_detail_id) {
                trip_detail_removal_id.push(local_trip_detail_id);
                $('#trip_detail_removal_id').val(JSON.stringify(trip_detail_removal_id));
            }
            self.trip.visit_details.splice(index, 1);
            setTimeout(function() {
                self.calculatetotalamount();
            }, 1500);
        }

        //REMOVE VISIT ATTACHMENT
        self.removeOtherAttachment = function(index, attachment_id) {
            console.log(attachment_id);
            if (attachment_id) {
                attachment_removal_id.push(attachment_id);
                $('#attachment_removal_ids').val(JSON.stringify(attachment_removal_id));
            }
            $('.attachment_'+index).hide();
        }

         

        //OTHER EXPENSE AMOUNT CALCULATE
        self.calculatetotalamount = function() {
            var total_petty_cash_other_amount = 0;
            var total_extra_amount = 0;
            var total_amount = 0;
            $('.amount_validate').each(function() {
                var claim_amount = parseFloat($(this).closest('tr').find('.claim_amount').val() || 0);
                if (!$.isNumeric(claim_amount)) {
                    claim_amount = 0;
                }
                total_amount = total_amount + claim_amount;

            });
            $('.amount_validate1').each(function() {
                var extra_amount = parseFloat($(this).closest('tr').find('.claim_extra_amount').val() || 0);
                if (!$.isNumeric(extra_amount)) {
                    extra_amount = 0;
                }
                total_extra_amount += extra_amount;
            });
            total_petty_cash_other_amount = total_extra_amount + total_amount;
            $('.other_expenses').text('₹ ' + total_petty_cash_other_amount.toFixed(2));
            $('.total_petty_cash_other_amount').val(total_petty_cash_other_amount.toFixed(2));
            $('.claim_total_amount').val(total_petty_cash_other_amount.toFixed(2));
            $('.claim_total_amount').text('₹ ' + total_petty_cash_other_amount.toFixed(2));
            // caimTotalAmount();
        }

        self.addotherexpence = function() {
            self.trip.visit_details.push({
                other_expence: '',
                date_other: '',
                amount: '',
                tax: '',
                remarks: '',
            });
        }

        $('body').on('click', "#datepicker", function() {
            var id = $(this).data('picker');
            datecall(startdate, enddate, id);
        });

        function datecall(startdate, enddate, id) {
            $(".datepicker_" + id).datepicker({
                autoclose: true,
                startDate: startdate,
                endDate: enddate,
            });
        }

        function sameFromTo() {
            $noty = new Noty({
                type: 'error',
                layout: 'topRight',
                text: 'From City and To City should not be same,please choose another To city',
                animation: {
                    speed: 50 // unavailable - no need
                },
            }).show();
            setTimeout(function() {
                $noty.close();
            }, 100000);
        }


        $("#advance").hide().prop('disabled', true);
        $('.btn-nxt').on("click", function() {
            $('.editDetails-tabs li.active').next().children('a').trigger("click");
        });
        $('.btn-prev').on("click", function() {
            $('.editDetails-tabs li.active').prev().children('a').trigger("click");
        });

        self.searchCity = function(query) {
            if (query) {
                return new Promise(function(resolve, reject) {
                    $http
                        .post(
                            laravel_routes['searchCity'], {
                                key: query,
                            }
                        )
                        .then(function(response) {
                            resolve(response.data);
                        });
                });
            } else {
                return [];
            }
        }

        self.removeLodging = function(index, lodging_id) {
            if (lodging_id) {
                lodgings_removal_id.push(lodging_id);
                $('#lodgings_removal_id').val(JSON.stringify(lodgings_removal_id));
            }
            self.trip.visits.splice(index, 1);
        }

        var form_id = '#local-trip-form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'trip_mode[]': {
                    required: true,
                },
            },
            messages: {
                'trip_mode[]': {
                    required: 'Select Visit Mode',
                },
            },
            invalidHandler: function(event, validator) {
                $noty = new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: 'You have errors,Please check all tabs',
                }).show();
                setTimeout(function() {
                    $noty.close();
                }, 1000);
            },
            submitHandler: function(form) {

                let formData = new FormData($(form_id)[0]);
                $('.btn-submit').button('loading');
                $.ajax({
                        url: laravel_routes['saveLocalTrip'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        // console.log(res.success);
                        if (!res.success) {
                            $('.btn-submit').button('reset');
                            custom_noty('error', res.errors);
                        } else {
                            $noty = new Noty({
                                type: 'success',
                                layout: 'topRight',
                                text: res.message,
                            }).show();
                            setTimeout(function() {
                                $noty.close();
                            }, 1000);
                            $location.path('/local-trips')
                            $scope.$apply()
                        }
                    })
                    .fail(function(xhr) {
                        $('.btn-submit').button('reset');
                        custom_noty('error', 'Something went wrong at server');
                    });
            },
        });
    }
});

app.component('eyatraTripLocalView', {
    templateUrl: local_trip_view_template_url,
    controller: function($http, $location, $routeParams, HelperService, $scope, $route) {

        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.local_travel_attachment_url = local_travel_attachment_url;
        $http.get(
            local_trip_view_url + '/' + $routeParams.trip_id
        ).then(function(response) {
            self.trip = response.data.trip;
            console.log(self.trip);
            self.claim_status = response.data.claim_status;
        });

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
    }
});