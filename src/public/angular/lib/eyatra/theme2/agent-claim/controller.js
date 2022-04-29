app.component('eyatraAgentClaimList', {
    templateUrl: eyatra_agent_claim_list_template_url,
    controller: function(HelperService, $rootScope, $scope, $location, $http) {
        var self = this;

        self.hasPermission = HelperService.hasPermission;
        // console.log(self.hasPermission);
        var dataTable = $('#agent_claim_list').DataTable({
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
                url: laravel_routes['listEYatraAgentClaimList'],
                type: "GET",
                dataType: "json",
                data: function(d) {}
            },
            columns: [
                { data: 'action', searchable: false, class: 'action' },
                { data: 'date', name: 'ey_agent_claims.invoice_date', searchable: false },
                { data: 'number', name: 'ey_agent_claims.number', searchable: true },
                { data: 'agent_code', name: 'agents.code', searchable: true },
                { data: 'invoice_number', name: 'ey_agent_claims.invoice_number', searchable: true },
                { data: 'invoice_date', name: 'ey_agent_claims.invoice_date', searchable: true },
                { data: 'invoice_amount', name: 'ey_agent_claims.invoice_amount', searchable: true },
                { data: 'status', name: 'configs.name', searchable: true },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();

        /* Search Block */
        setTimeout(function() {
            var x = $('.separate-page-header-inner.search .custom-filter').position();
            var d = document.getElementById('agent_claim_list_filter');
            x.left = x.left + 15;
            d.style.left = x.left + 'px';
        }, 500);
        $scope.deleteAgentClaimconfirm = function($id) {
            $('#delete_agent_claim').val($id);
        }

        $scope.deleteAgentClaim = function() {
            var id = $('#delete_agent_claim').val();
            $http.get(
                eyatra_agent_claim_delete_data_url + '/' + id,
            ).then(function(response) {
                if (response.data.success) {
                    $noty = new Noty({
                        type: 'success',
                        layout: 'topRight',
                        text: 'Agent Claim Deleted Successfully',
                        animation: {
                            speed: 500 // unavailable - no need
                        },
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 1000);
                    $('#agent_claim_list').DataTable().ajax.reload(function(json) {});
                    $location.path('/agent/claim/list');
                    // $scope.$apply();
                } else {
                    $noty = new Noty({
                        type: 'error',
                        layout: 'topRight',
                        text: 'Agent Claim not Deleted',
                        animation: {
                            speed: 500 // unavailable - no need
                        },
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 1000);
                }
            });
        }
        $rootScope.loading = false;
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('eyatraAgentClaimForm', {
    templateUrl: eyatra_agent_claim_form_template_url,
    controller: function($http, $location, HelperService, $routeParams, $rootScope, $scope) {
        $form_data_url = typeof($routeParams.agent_claim_id) == 'undefined' ? eyatra_agent_claim_form_data_url : eyatra_agent_claim_form_data_url + '/' + $routeParams.agent_claim_id;
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
                    text: response.data.message,
                    animation: {
                        speed: 500 // unavailable - no need
                    },
                }).show();
                setTimeout(function() {
                    $noty.close();
                }, 1000);
                $location.path('/agent/claim/list')
                // $scope.$apply()
                return;
            }

            self.agent_claim = response.data.agent_claim;
            self.booking_list = response.data.booking_list;
            self.action = response.data.action;
            self.invoice_date = response.data.invoice_date;
            self.attachment = response.data.attachment;
            self.gstin_tax = response.data.gstin_tax;
            console.log(response.data.agent_claim);

            if (self.action == 'Edit') {
                self.trips_count = response.data.trips_count;
                $("#count").html(self.trips_count);
                $(".amount").html(response.data.agent_claim.net_amount);
                $(".separate-bottom-layer").addClass("in");
                $("button.payment-btn").css({ 'display': 'none' });
                $("button.advance-btn").css({ 'display': 'none' });
                $(".btn-close").css({ 'display': 'inline-block' });
                $(".payment-btn").prop('disabled', false);
            } else {
                $(".amount").html(0);
                $("#count").html(0);
            } // self.extras = response.data.extras;
            if (self.gstin_tax[0].gstin == null || self.gstin_tax[0].gstin == '') {
                $("#total_amt").hide().prop('disabled', true);
                $("#tax_amt").hide().prop('disabled', true);
            }
            $(".payment-btn").prop('disabled', true);
            $rootScope.loading = false;
        });
        var total = 0;
        $(document).on('keyup', '#tax', function() {
            // $('#tax').on('change', function() {
            var net_amt = parseFloat($(".net_amount").val());
            var tax = parseFloat($("#tax").val());
            total = (net_amt + tax);
            $("#invoice_amount").val(total.toFixed(2));
        });

        $(document).on("click", ".booking_list", function() {

            if ($('.booking_list:checked').length > 0) {
                $('.booking_all_list').prop('checked', true);
            } else {
                $('.booking_all_list').prop('checked', false);
            }
        });


        $(document).on('click', '.booking_list', function() {

            var total_amount = 0;
            var count = 0;
            var amount = 0;
            if (event.target.checked == true) {
                $.each($('.booking_list:checked'), function() {
                    count++;
                    amount += parseFloat($(this).attr('data-amount'));
                });
            } else {
                $.each($('.booking_list:checked'), function() {
                    count++;
                    amount += parseFloat($(this).attr('data-amount'));
                });
                self.total_amount = 0;
            }
            $(".amount").html(amount.toFixed(2));
            $(".net_amount").val(amount.toFixed(2));
            $("#count").html(count);

            if ($("#tax").val() != '') {
                var net_amt = parseFloat($(".net_amount").val());
                var tax = parseFloat($("#tax").val());
                var total_amount = (net_amt + tax);
                $("#invoice_amount").val(total_amount.toFixed(2));
            }
            if (count > 0) {
                if (self.action != 'Edit') {

                    $(".payment-btn").prop('disabled', false);
                }
            } else {
                $(".payment-btn").prop('disabled', true);
                $(".separate-bottom-layer").removeClass("in");
                $("button.payment-btn").css({ 'display': 'inline-block' });
                $(".btn-close").css({ 'display': 'none' });
            }

        });

        $('#head_booking').on('click', function() {
            $("#tax").val('');
            $("#invoice_amount").val('');
            var total_amount = 0;
            var count = 0;
            var amount = 0;
            if (event.target.checked == true) {
                $('.booking_list').prop('checked', true);
                $.each($('.booking_list:checked'), function() {
                    count++;
                    amount += parseFloat($(this).attr('data-amount'));
                });
            } else {
                $('.booking_list').prop('checked', false);
                $.each($('.booking_list:checked'), function() {
                    count++;
                    amount += parseFloat($(this).attr('data-amount'));
                });
                self.total_amount = 0;
            }
            $(".amount").html(amount.toFixed(2));
            $(".net_amount").val(amount.toFixed(2));
            $("#count").html(count);
            $(".payment-btn").prop('disabled', true);
            if (count > 0) {
                if (self.action == 'Edit') {
                    $(".payment-btn").prop('disabled', false);
                } else {
                    $(".payment-btn").prop('disabled', false);
                }
            } else {
                $(".separate-bottom-layer").removeClass("in");
                $("button.payment-btn").css({ 'display': 'inline-block' });
                $(".btn-close").css({ 'display': 'none' });
            }
        });

        $(".payment-btn").on('click', function() {

            if ($(".separate-bottom-layer").hasClass("in")) {
                $(".separate-bottom-layer").removeClass("in");
            } else {
                $(".separate-bottom-layer").addClass("in");
                $("button.payment-btn").css({ 'display': 'none' });
                $("button.advance-btn").css({ 'display': 'none' });
                $(".btn-close").css({ 'display': 'inline-block' });
            }
        });



        $(".btn-close").on('click', function() {
            /* Bottom Layer Close */
            if ($(".separate-bottom-layer").hasClass("in")) {
                $(".separate-bottom-layer").removeClass("in");
                $("button.payment-btn").css({ 'display': 'inline-block' });
                $("button.advance-btn").css({ 'display': 'inline-block' });
                if (self.action == 'Edit') {
                    $("button.payment-btn").prop('disabled', false);
                }
                $(".btn-close").css({ 'display': 'none' });
                $(".bottom-item").css({ 'display': 'inline-block' });
                $(".bottom-title").css({ 'display': 'none' });
            }

            /* Page Side Wrapper Close */
            if ($(".page-side-wrapper").hasClass("in")) {
                $(".page-side-wrapper").removeClass("in");
                $(".page-side-wrapper-drop").removeClass("in");
                $("body").removeClass("modal-open");
            }
        });

        var form_id = '#agent-claim-form';

        $.validator.addMethod('decimal', function(value, element) {
            return this.optional(element) || /^((\d+(\\.\d{0,2})?)|((\d*(\.\d{1,2}))))$/.test(value);
        }, "Please enter a correct number, format 0.00");

        var v = jQuery(form_id).validate({
            errorPlacement: function(error, element) {
                if (element.attr('name') == 'booking_list[]') {
                    error.appendTo($('.booking_list_error'));
                } else if (element.attr('name') == 'date') {
                    error.appendTo($('.date_error'));
                } else {
                    error.insertAfter(element)
                }
            },
            ignore: '',
            rules: {
                'invoice_number': {
                    required: true,
                    minlength: 3,
                    maxlength: 191,
                },
                'date': {
                    required: true,
                },
                'amount': {
                    required: true,
                    number: true,
                    decimal: true,
                    maxlength: 11,
                },
                // 'invoice_attachmet': {
                //     extension: "docx|rtf|doc|pdf",
                // },

            },
            messages: {
                'invoice_number': {
                    required: 'Agent Invoice Number is Required',
                    minlength: 'Please enter minimum of 3 letters',
                    maxlength: 'Please enter maximum of 191 letters',
                },
                'date': {
                    required: 'Agent Invoice Date is Required',
                },
                'amount': {
                    required: 'Agent Invoice Amount is Required',
                    number: 'Enter Numbers Only',
                    decimal: 'Please enter a correct number, format 0.00',
                    maxlength: 'Enter Maximum 10 Digit Number',

                },
                // 'invoice_attachmet': {
                //     extension: 'Select valied input file format LIKE: docx|rtf|doc|pdf',
                // },
                'booking_list[]': {
                    required: 'Booking list is Required',
                },
            },
            submitHandler: function(form) {

                let formData = new FormData($(form_id)[0]);
                $('#submit').button('loading');
                $.ajax({
                        url: laravel_routes['saveEYatraAgentClaim'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
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
                                text: res.message,
                                animation: {
                                    speed: 500 // unavailable - no need
                                },
                            }).show();
                            setTimeout(function() {
                                $noty.close();
                            }, 1000);
                            $location.path('/agent/claim/list')
                            $scope.$apply()
                        }
                    })
                    .fail(function(xhr) {
                        $('#submit').button('reset');
                        custom_noty('error', 'Something went wrong at server');
                    });
            },
        });
        /* File Upload Start */
        $(function() {
            // We can attach the `fileselect` event to all file inputs on the page
            $(document).on('change', ':file', function() {
                var input = $(this),
                    numFiles = input.get(0).files ? input.get(0).files.length : 1,
                    label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
                input.trigger('fileselect', [numFiles, label]);
            });

            // We can watch for our custom `fileselect` event like this
            $(':file').on('fileselect', function(event, numFiles, label) {
                var input = $(this).parents('.input-group').find(':text'),
                    log = numFiles > 1 ? numFiles + ' files selected' : label;

                if (input.length) {
                    input.val(log);
                } else {

                }
            });
        });
        /* File Upload End */

    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('eyatraAgentClaimView', {
    templateUrl: eyatra_agent_claim_view_template_url,
    controller: function($http, $location, $routeParams, HelperService, $rootScope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        $http.get(
            eyatra_agent_claim_view_data_url + '/' + $routeParams.agent_claim_id
        ).then(function(response) {
            console.log(response.data);
            self.agent_claim_view = response.data.agent_claim_view;
            self.booking_list = response.data.booking_list;
            self.gstin_tax = response.data.gstin_tax;
            self.total_trips = response.data.total_trips;
            self.action = "View ";
            if (self.gstin_tax[0].gstin == null || self.gstin_tax[0].gstin == '') {
                $("#total_amt").hide().prop('disabled', true);
                $("#tax_amt").hide().prop('disabled', true);
            }
            // console.log(self.booking_list);
            self.count = self.booking_list.length;
        });
        $rootScope.loading = false;
        setTimeout(function() {
            var heights = new Array();
            // Loop to get all element Widths
            $('.equal-column').each(function() {
                // Need to let sizes be whatever they want so no overflow on resize
                // Then add size (no units) to array
                heights.push($(this).height());
            });
            // Find max Width of all elements
            var max = Math.max.apply(Math, heights);
            // Set all Width to max Width
            $('.equal-column').each(function() {
                $(this).css('height', max + 'px');
                // Note: IF box-sizing is border-box, would need to manually add border and padding to Width (or tallest element will overflow by amount of vertical border + vertical padding)
            });
        }, 1000);

    }
});

//Booking View 
app.component('eyatraTripBookingView', {
    templateUrl: trip_booking_view_template_url,
    controller: function($http, $location, $location, HelperService, $routeParams, $rootScope, $scope, $timeout, $route) {
        if (typeof($routeParams.trip_id) == 'undefined') {
            $location.path('/agent/requests')
            $scope.$apply()
            return;
        }
        $form_data_url = booking_view_form_data_url + '/' + $routeParams.trip_id;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;

        $scope.showBookingForm = true;
        $scope.showCancelForm = false;
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
                $location.path('/trips/booking-requests')
                $scope.$apply()
                return;
            }
            if (!response.data.trip.visits || response.data.trip.visits.length == 0) {
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
                $location.path('/trips/booking-requests')
                $scope.$apply()
                return;
            }
            console.log(response);
            self.trip = response.data.trip;
            self.age = response.data.age;
            self.total_amount = response.data.total_amount;
            self.ticket_amount = response.data.ticket_amount;
            self.service_charge = response.data.service_charge;
            self.trip_status = response.data.trip_status;
            self.booking_mode_list = response.data.booking_mode_list;
            self.travel_mode_list = response.data.travel_mode_list;
            self.attachment_path = response.data.attach_path;
            self.action = response.data.action;
            $rootScope.loading = false;

        });

        $scope.userDetailId = 0;
        $scope.showUserDetail = function(id) {
            $("#open_cancel_form_" + id).hide();
            $("#close_" + id).show();
            $scope.userDetailId = id;
        }

        $(document).on('click', '.close_icon', function() {
            var id = $(this).attr('data-visit_id');
            $scope.userDetailId = 0;
            $("#open_cancel_form_" + id).show();
            $("#close_" + id).hide();
        });

        $(document).on('click', '.booking_cancel', function() {
            var form_id = '#visit-booking-cancel-form';
            var v = jQuery(form_id).validate({
                errorPlacement: function(error, element) {
                    error.insertAfter(element)
                },
                ignore: '',
                submitHandler: function(form) {

                    let formData = new FormData($(form_id)[0]);
                    $('#cancel').button('loading');
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
                                $('#cancel').button('reset');
                                var errors = '';
                                for (var i in res.errors) {
                                    errors += '<li>' + res.errors[i] + '</li>';
                                }
                                custom_noty('error', errors);
                            } else {
                                $noty = new Noty({
                                    type: 'success',
                                    layout: 'topRight',
                                    text: 'Booking details updated successfully!',
                                    animation: {
                                        speed: 500 // unavailable - no need
                                    },
                                }).show();
                                setTimeout(function() {
                                    $noty.close();
                                }, 1000);
                                $route.reload();
                                $scope.$apply()
                            }
                        })
                        .fail(function(xhr) {
                            $('#submit').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                },
            });
        });
        $(document).on('click', '.submit', function() {
            var form_id = '#trip-booking-updates-form';
            var v = jQuery(form_id).validate({
                errorPlacement: function(error, element) {
                    error.insertAfter(element)
                },
                ignore: '',
                rules: {
                    'ticket_booking[][ticket_amount]': {
                        required: true,
                    },
                    'description': {
                        maxlength: 255,
                    },
                    'advance_received': {
                        maxlength: 10,
                    },
                },
                messages: {
                    'description': {
                        maxlength: 'Please enter maximum of 255 letters',
                    },
                },
                submitHandler: function(form) {

                    let formData = new FormData($(form_id)[0]);
                    $('#submit').button('loading');
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
                                $route.reload();
                                $scope.$apply()
                            }
                        })
                        .fail(function(xhr) {
                            $('#submit').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                },
            });
        });

        // Select and loop the container element of the elements you want to equalise
        setTimeout(function() {
            // Cache the highest
            var highestBox = new Array();
            // Loop to get all element Widths
            $('.match-height').each(function() {
                // Need to let sizes be whatever they want so no overflow on resize   
                // Then add size (no units) to array
                highestBox.push($(this).height());
            });
            // Find max Width of all elements
            var max = Math.max.apply(Math, highestBox);
            // Set the height of all those children to whichever was highest 
            $('.match-height').height(max);

            // Cache the highest
            var highestBox_1 = new Array();
            // Loop to get all element Widths
            $('.match-height-1').each(function() {
                // Need to let sizes be whatever they want so no overflow on resize
                // Then add size (no units) to array
                highestBox_1.push($(this).height());
            });
            // Find max Width of all elements
            var max_1 = Math.max.apply(Math, highestBox_1);
            // Set the height of all those children to whichever was highest 
            $('.match-height-1').height(max_1);

        }, 1400);

        self.approveTrip = function() {
            self.trip.visits.push({
                visit_date: '',
                booking_method: 'Self',
                preferred_travel_modes: '',
            });
        }

        //APPROVE TRIP
        self.approveTrip = function(id) {
            $('#trip_id').val(id);
        }

        self.confirmApproveTrip = function() {
            $id = $('#trip_id').val();
            $http.get(
                trip_verification_approve_url + '/' + $id,
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
                        animation: {
                            speed: 500 // unavailable - no need
                        },
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 1000);
                } else {
                    $noty = new Noty({
                        type: 'success',
                        layout: 'topRight',
                        text: 'Trip Approved Successfully',
                        animation: {
                            speed: 500 // unavailable - no need
                        },
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 1000);
                    $('#approval_modal').modal('hide');
                    $timeout(function() {
                        $location.path('/trip/verifications')
                        $scope.$apply()
                    }, 500);
                }

            });
        }

        //REJECT TRIP
        self.rejectTrip = function(id, type) {
            $('#trip_id').val(id);
        }

        self.confirmRejectTrip = function() {
            $id = $('#trip_id').val();
            $http.get(
                trip_verification_reject_url + '/' + $id,
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
                        animation: {
                            speed: 500 // unavailable - no need
                        },
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 1000);
                } else {
                    $noty = new Noty({
                        type: 'success',
                        layout: 'topRight',
                        text: 'Trip Rejected Successfully',
                        animation: {
                            speed: 500 // unavailable - no need
                        },
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 1000);
                    $('#reject_modal').modal('hide');
                    $timeout(function() {
                        $location.path('/trip/verifications')
                        $scope.$apply()
                    }, 500);
                }

            });
        }

        /* File Upload Start */
        $(function() {
            // We can attach the `fileselect` event to all file inputs on the page
            $(document).on('change', ':file', function() {
                var input = $(this),
                    numFiles = input.get(0).files ? input.get(0).files.length : 1,
                    label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
                input.trigger('fileselect', [numFiles, label]);
            });

            // We can watch for our custom `fileselect` event like this
            $(':file').on('fileselect', function(event, numFiles, label) {
                var input = $(this).parents('.input-group').find(':text'),
                    log = numFiles > 1 ? numFiles + ' files selected' : label;

                if (input.length) {
                    input.val(log);
                } else {

                }
            });
        });
        /* File Upload End */
    }
});
//End

//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------