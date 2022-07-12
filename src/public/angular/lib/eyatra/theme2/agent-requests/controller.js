//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('eyatraAgentRequestForm', {
    templateUrl: agent_request_form_template_url,
    controller: function($http, $location, $location, HelperService, $routeParams, $mdSelect, $rootScope, $scope, $timeout) {
        //alert();
        if (typeof($routeParams.trip_id) == 'undefined') {
            $location.path('/agent/requests')
            $scope.$apply()
            return;
        }
        $form_data_url = agent_request_form_data_url + '/' + $routeParams.trip_id;
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
                $location.path('/agent/requests')
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
                $location.path('/agent/requests')
                $scope.$apply()
                return;
            }
            // console.log(response.data.trip);
            self.trip = response.data.trip;

            self.travel_mode_list = response.data.travel_mode_list;
            self.attachments = response.data.attachments;
            //console.log(response.data.attachments);
            self.action = response.data.action;
            $rootScope.loading = false;

        });

        // Select and loop the container element of the elements you want to equalise
        $('.container').each(function() {
            // Cache the highest
            var highestBox = 0;
            // Select and loop the elements you want to equalise
            $('.match-height', this).each(function() {
                // If this box is higher than the cached highest then store it
                if ($(this).height() > highestBox) {
                    highestBox = $(this).height();
                }
            });
            // Set the height of all those children to whichever was highest 
            $('.match-height', this).height(highestBox);

        });

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

        $scope.fareDetailGstChange = (index, gst_number) => {
            console.log(gst_number);
            self.ticket_booking[index]['fare_gst_detail'] = '';
            if (gst_number && gst_number.length == 15) {
                $http({
                    url: laravel_routes['getGstInData'],
                    method: 'GET',
                    params: { 'gst_number': gst_number }
                }).then(function(res) {
                    console.log(res);
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
                        self.ticket_booking[index]['fare_gst_detail'] = res.data.gst_data.LegalName ? res.data.gst_data.LegalName : res.data.gst_data.TradeName;
                    }
                });
            }
            $scope.calculateFareDetailTax(index);
        }

        $scope.calculateFareDetailTax = (index) => {
            const amount = self.ticket_booking[index]['ticket_amount'];
            const gst_number = self.ticket_booking[index]['gstin'];
            var cgst_percentage = sgst_percentage = igst_percentage = 0;
            if (amount != undefined && amount && amount >= 1000 && gst_number && gst_number.length == 15) {
                const gst_state_code = gst_number.substr(0, 2);
                percentage = 12;
                if (amount >= 7500)
                    percentage = 18;
                if (gst_state_code == self.state_code) {
                    cgst_percentage = sgst_percentage = percentage / 2;
                } else {
                    igst_percentage = percentage;
                }
            }
            total_tax = 0;
            total_tax += cgst = amount * (cgst_percentage / 100);
            total_tax += sgst = amount * (sgst_percentage / 100);
            total_tax += igst = amount * (igst_percentage / 100);

            cgst = Number.parseFloat(cgst).toFixed(2);
            sgst = Number.parseFloat(sgst).toFixed(2);
            igst = Number.parseFloat(igst).toFixed(2);

            total_tax = Number.parseFloat(total_tax).toFixed(2);

            self.trip.visits[index].self_booking['cgst'] = cgst;
            self.trip.visits[index].self_booking['sgst'] = sgst;
            self.trip.visits[index].self_booking['igst'] = igst;

            self.trip.visits[index].self_booking['tax'] = total_tax;

            self.trip.visits[index].self_booking['tax_percentage'] = tax_percentage = cgst_percentage + sgst_percentage + igst_percentage;
            self.trip.visits[index].self_booking['cgst_percentage'] = cgst_percentage;
            self.trip.visits[index].self_booking['sgst_percentage'] = sgst_percentage;
            self.trip.visits[index].self_booking['igst_percentage'] = igst_percentage;
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


        var form_id = '#trip-form';
        var v = jQuery(form_id).validate({
            errorPlacement: function(error, element) {
                error.insertAfter(element)
            },
            ignore: '',
            rules: {
                'purpose_id': {
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
                        url: laravel_routes['saveTrip'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        // console.log(res.success);
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
                                text: 'Trip saves successfully',
                                animation: {
                                    speed: 500 // unavailable - no need
                                },
                            }).show();
                            setTimeout(function() {
                                $noty.close();
                            }, 1000);
                            $location.path('/trips')
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