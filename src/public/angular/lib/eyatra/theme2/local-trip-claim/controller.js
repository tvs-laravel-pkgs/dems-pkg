app.component('eyatraClaimedLocalTrips', {
    templateUrl: eyatra_claimed_local_trip_list_template_url,
    controller: function(HelperService, $rootScope, $scope, $http) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.permission = self.hasPermission('local-trip-add');
        $http.get(
            local_trip_claim_filter_data_url
        ).then(function(response) {
            console.log(response.data);
            self.employee_list = response.data.employee_list;
            self.purpose_list = response.data.purpose_list;
            self.trip_status_list = response.data.trip_status_list;
            $rootScope.loading = false;
        });

        var dataTable = $('#eyatra_claimed_local_trip_table').DataTable({
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
                url: laravel_routes['listClaimedLocalTrip'],
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
                { data: 'claim_number', name: 'local_trips.claim_number', searchable: true },
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

        //CURRENT DATE SHOW IN DATEPICKER
        setTimeout(function() {
            $('div[data-provide="datepicker"]').datepicker({
                todayHighlight: true,
                autoclose: true,
            });
        }, 1000);

        setTimeout(function() {
            var x = $('.separate-page-header-inner.search .custom-filter').position();
            var d = document.getElementById('eyatra_claimed_local_trip_table_filter');
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
                local_trip_delete_url + '/' + $id,
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

app.component('eyatraLocalTripClaimForm', {
    templateUrl: local_trip_claim_form_template_url,
    controller: function($http, $location, $location, HelperService, $routeParams, $rootScope, $scope, $timeout) {
        $form_data_url = typeof($routeParams.trip_id) == 'undefined' ? local_trip_form_data_url : local_trip_form_data_url + '/' + $routeParams.trip_id;
        var self = this;
        var arr_ind;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        self.local_travel_attachment_url = local_travel_attachment_url;
        var trip_detail_removal_id = [];
        var attachment_removal_id = [];
        var from_to_km_error_flag = 0;
        $('.km_amount_label').hide();
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
                $location.path('/local-trip/list')
                return;
            }

            $('.testt1').imageuploadify();
            self.trip = response.data.trip;
            console.log(self.trip);
            self.trip.trip_periods = '';
            self.eligible_date = response.data.eligible_date;
            self.beta_amount = response.data.beta_amount;

            self.sbu_lists = response.data.sbu_lists;

            if (self.trip.expense_attachments.length > 0) {
                self.trip_expense_attachment_status = 'Yes';
            } else {
                self.trip_expense_attachment_status = 'No';
            }
            if (self.trip.other_expense_attachments.length > 0) {
                self.trip_other_expense_attachment_status = 'Yes';
            } else {
                self.trip_other_expense_attachment_status = 'No';
            }


            if (response.data.action == "Edit") {
                if (response.data.trip.start_date && response.data.trip.end_date) {
                    var start_date = response.data.trip.start_date;
                    var end_date = response.data.trip.end_date;
                    trip_periods = response.data.trip.start_date + ' to ' + response.data.trip.end_date;
                    self.trip.trip_periods = trip_periods;
                    setTimeout(function() {
                        $scope.onChange(start_date, end_date);
                    }, 800);
                }

                $(".daterange").daterangepicker({
                    autoclose: true,
                    // minDate: new Date(self.eligible_date),
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
                        // minDate: new Date(self.eligible_date),
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

        self.totalKm = function(key) {
            var from_km = parseInt($('.from_km_' + key).val());
            var to_km = parseInt($('.to_km_' + key).val());
            var total_km = 0;
            if (to_km > from_km) {
                var total_km = to_km - from_km;
                $('.total_km_' + key).val(total_km);
            } else {
                $('.total_km_' + key).val('-');
            }
        }

        self.getStartEndKm = function(travel_mode_id, key) {
            if (travel_mode_id == 15 || travel_mode_id == 16) { 
                $('.travel-mode-change').show();          
                $('.from_km_' + key).val('');
                $('.to_km_' + key).val('');
            } else {
                $('.travel-mode-change').hide();
                $('.from_km_' + key).val('--');
                $('.to_km_' + key).val('--');
            }
        }

        $scope.travelClaimStatus = function(travel_mode_id, index) {
            if (travel_mode_id == 15 || travel_mode_id == 16) {
                $('.from_km_'+index).attr('disabled', false);
                $('.to_km_'+index).attr('disabled', false);
                $('.total_km_'+index).attr('disabled', false);
            } else {
                $('.from_km_'+index).attr('disabled', true);
                $('.to_km_'+index).attr('disabled', true);
                $('.total_km_'+index).attr('disabled', true);
            }
            if (travel_mode_id && self.extras.travel_values[travel_mode_id] && self.extras.travel_values[travel_mode_id] != 'undefined') {
                if (!self.trip.visit_details[index].eligible_km) {
                    self.trip.visit_details[index].eligible_km = {
                        readonly: false
                    };
                } else {
                    self.trip.visit_details[index].eligible_km.readonly = false;
                }
                if (!self.trip.visit_details[index].editable_amount) {
                    self.trip.visit_details[index].editable_amount = {
                        readonly: true
                    };
                } else {
                    self.trip.visit_details[index].editable_amount.readonly = true;
                }
            } else {
                if (!self.trip.visit_details[index].eligible_km) {
                    self.trip.visit_details[index].eligible_km = {
                        readonly: true
                    };
                } else {
                    self.trip.visit_details[index].eligible_km.readonly = true;
                }
                if (!self.trip.visit_details[index].editable_amount) {
                    self.trip.visit_details[index].editable_amount = {
                        readonly: false
                    };
                } else {
                    self.trip.visit_details[index].editable_amount.readonly = false;
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
        self.removeLocalTrip = function(index, local_trip_detail_id) {
            if (local_trip_detail_id) {
                trip_detail_removal_id.push(local_trip_detail_id);
                $('#trip_detail_removal_id').val(JSON.stringify(trip_detail_removal_id));
            }
            self.trip.visit_details.splice(index, 1);
            setTimeout(function() {
                self.calculatetotalamount();
            }, 1500);
        }

        //REMOVE OTHER EXPENSE 
        self.removeExpense = function(index, local_trip_detail_id) {
            // if (local_trip_detail_id) {
            //     trip_detail_removal_id.push(local_trip_detail_id);
            //     $('#trip_detail_removal_id').val(JSON.stringify(trip_detail_removal_id));
            // }
            self.trip.expense.splice(index, 1);
            setTimeout(function() {
                self.calculatetotalamount();
            }, 1500);
        }

        //REMOVE Travel ATTACHMENT
        self.removeExpenseAttachment = function(index, attachment_id) {
            console.log(attachment_id);
            if (attachment_id) {
                attachment_removal_id.push(attachment_id);
                $('#attachment_removal_ids').val(JSON.stringify(attachment_removal_id));
            }
            $('.travel_attachment_' + index).hide();
        }

        //REMOVE VISIT ATTACHMENT
        self.removeOtherAttachment = function(index, attachment_id) {
            console.log(attachment_id);
            if (attachment_id) {
                attachment_removal_id.push(attachment_id);
                $('#attachment_removal_ids').val(JSON.stringify(attachment_removal_id));
            }
            $('.attachment_' + index).hide();
        }



        //AMOUNT CALCULATE
        self.calculatetotalamount = function() {
            var total_amount = 0;
            var total_expense_amount = 0;
            var total_beta_amount = 0;
            var total_travel_amount = 0;
            var total_days = 0;
            var days = [];

            setTimeout(function() {
                $('.trip_date').each(function() {
                    var trip_date = $(this).closest('tr').find('.trip_date').val();
                    if (days.includes(trip_date)) {} else {
                        days.push(trip_date);
                        total_days++;
                    }
                });

                total_beta_amount = total_days * self.beta_amount;

                $('.km_amount_validate').each(function() {
                    var travel_amount = parseFloat($(this).closest('tr').find('.kilo_meter_amount').val() || 0);
                    console.log(travel_amount);
                    if (!$.isNumeric(travel_amount)) {
                        travel_amount = 0;
                    }
                    total_travel_amount += travel_amount;
                });

                $('.expense_amount_validate').each(function() {
                    var expense_amount = parseFloat($(this).closest('tr').find('.expense_amount').val() || 0);
                    console.log(expense_amount);
                    if (!$.isNumeric(expense_amount)) {
                        expense_amount = 0;
                    }
                    total_expense_amount += expense_amount;
                });

                total_amount = total_travel_amount + total_beta_amount + total_expense_amount;

                //Total Travel Amount
                $('.claim_travel_amount').val(total_travel_amount.toFixed(2));
                $('.claim_travel_amount').text('₹ ' + total_travel_amount.toFixed(2));

                //Total Beta Amount
                $('.claim_beta_amount').val(total_beta_amount.toFixed(2));
                $('.claim_beta_amount').text('₹ ' + total_beta_amount.toFixed(2));

                //Total Expense Amount
                $('.claim_expense_amount').val(total_expense_amount.toFixed(2));
                $('.claim_expense_amount').text('₹ ' + total_expense_amount.toFixed(2));

                //Total Amount
                $('.claim_total_amount').val(total_amount.toFixed(2));
                $('.claim_total_amount').text('₹ ' + total_amount.toFixed(2));
                console.log(total_days);
            }, 1000);
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

        self.addOtherExpense = function() {
            self.trip.expense.push({
                amount: '',
                remarks: '',
            });
        }

        $('body').on('click', "#datepicker", function() {
            var id = $(this).data('picker');
            var periods = $("#trip_periods").val();
            var period = periods.split(" to ");
            datecall(period[0], period[1], id);
        });

        $(document).on('input', '.kilo_meter', function() {
            var index = $(this).attr("data-index");
            var km_amount = 0;
            var from_km = parseInt($(this).closest('tr').find('.from_km').val());
            var to_km = parseInt($(this).closest('tr').find('.to_km').val());

            var travel_mode_id = $(".travel_mode_" + index).val();

            $scope.calculateKMAmount(from_km, to_km, travel_mode_id, index);
        });

        $scope.travelMode = function(travel_mode_id, index) {
            if (travel_mode_id == 15 || travel_mode_id == 16) {
                $('.from_km_'+index).attr('disabled', false);
                $('.to_km_'+index).attr('disabled', false);
                $('.total_km_'+index).attr('disabled', false);
            } else {
                $('.from_km_'+index).attr('disabled', true);
                $('.to_km_'+index).attr('disabled', true);
                $('.total_km_'+index).attr('disabled', true);
            }
            if (travel_mode_id && self.extras.travel_values[travel_mode_id] && self.extras.travel_values[travel_mode_id] != 'undefined') {
                $(".km_amount_" + index).prop("readonly", true);
                $(".from_km_" + index).prop("readonly", false);
                $(".to_km_" + index).prop("readonly", false);

                var from_km = $(".from_km_" + index).val();
                var to_km = $(".to_km_" + index).val();

                self.trip.visit_details[index].km_amount = self.extras.travel_values[travel_mode_id];
                $(".km_amount_label_" + index).show();
                self.trip.visit_details[index].km_amount = 0;
                self.trip.visit_details[index].from_km = '';
                self.trip.visit_details[index].to_km = '';
                $scope.calculateKMAmount(from_km, to_km, travel_mode_id, index);
            } else {
                $(".km_amount_" + index).prop("readonly", false);
                self.trip.visit_details[index].km_amount = 0;
                self.trip.visit_details[index].from_km = '-';
                self.trip.visit_details[index].to_km = '-';
                $(".from_km_" + index).prop("readonly", true);
                $(".to_km_" + index).prop("readonly", true);
                $(".km_amount_label_" + index).hide();
            }
            self.totalKm(index);
            self.getStartEndKm(travel_mode_id, index);
        }

        $scope.calculateKMAmount = function(from_km, to_km, travel_mode_id, index) {
            console.log(from_km, to_km, travel_mode_id, index);
            var from_km = parseInt(from_km);
            var to_km = parseInt(to_km);

            if (from_km == to_km) {
                $(".validation_error_" + index).text("From,To km should not be same");
                from_to_km_error_flag = 1;
            } else if (from_km > to_km) {
                $(".validation_error_" + index).text("To km should be greater then From km");
                from_to_km_error_flag = 1;
            } else if (from_km == 0 || to_km == 0) {
                $(".validation_error_" + index).text("Invalid value");
                from_to_km_error_flag = 1;
            } else if (from_km && to_km) {
                $(".validation_error_" + index).text("");

                if (travel_mode_id && self.extras.travel_values[travel_mode_id] && self.extras.travel_values[travel_mode_id] != 'undefined') {
                    var from_to_km_diff = to_km - from_km;

                    kilo_meter_amount = from_to_km_diff * self.extras.travel_values[travel_mode_id];
                    $(".km_amount_" + index).val(kilo_meter_amount);;
                    $(".km_amount_" + index).prop("readonly", true);

                } else {
                    $(this).closest('tr').find('.kilo_meter_amount').val('0');
                    $(".km_amount_" + index).prop("readonly", false);
                }
                from_to_km_error_flag = 0;
            } else {
                $(this).closest('tr').find('.kilo_meter_amount').val('0');
                $(".validation_error_" + index).text("");
                from_to_km_error_flag = 0;
            }

            setTimeout(function() {
                self.calculatetotalamount();
            }, 1000);
        }

        function datecall(startdate, enddate, id) {
            $(".datepicker_" + id).datepicker({
                autoclose: true,
                startDate: startdate,
                endDate: enddate,
            });
        }

        $("#advance").hide().prop('disabled', true);
        $('.btn-nxt').on("click", function() {
            $('.editDetails-tabs li.active').next().children('a').trigger("click");
        });
        $('.btn-prev').on("click", function() {
            $('.editDetails-tabs li.active').prev().children('a').trigger("click");
        });

        var form_id = '#local-trip-form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'trip_mode[]': {
                    required: true,
                },
                'sbu_id': {
                    required: true,
                },
            },
            messages: {
                'trip_mode[]': {
                    required: 'Select Visit Mode',
                },
                'sbu_id': {
                    required: 'Select SBU',
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
                if (from_to_km_error_flag == 0) {
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
                            if (!res.success) {
                                $('.btn-submit').button('reset');
                                custom_noty('error', res.errors);
                            } else {
                                custom_noty('success', 'Local Trip Claim Added Successfully');
                                $('#trip-claim-modal-justify-one').modal('hide');
                                setTimeout(function() {
                                    $location.path('/local-trip/claim/list')
                                    $scope.$apply()
                                }, 2000);
                            }
                        })
                        .fail(function(xhr) {
                            $('.btn-submit').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                } else {
                    custom_noty('error', 'Please provide correct the From and To KMs');
                }
            },
        });
    }
});

app.component('eyatraLocalTripClaimView', {
    templateUrl: local_trip_claim_view_template_url,
    controller: function($http, $location, $routeParams, HelperService, $scope, $route) {

        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.local_travel_attachment_url = local_travel_attachment_url;
        self.local_travel_google_attachment_url = local_travel_google_attachment_url;
        $http.get(
            local_trip_view_url + '/' + $routeParams.trip_id
        ).then(function(response) {
            self.trip = response.data.trip;
            console.log(self.trip);
            self.claim_status = response.data.claim_status;
            self.gender = (response.data.trip.employee.gender).toLowerCase();
        });

        /* Pane Next Button */
        $('.btn-nxt').on("click", function() {
            $('.editDetails-tabs li.active').next().children('a').trigger("click");
        });
        $('.btn-prev').on("click", function() {
            $('.editDetails-tabs li.active').prev().children('a').trigger("click");
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