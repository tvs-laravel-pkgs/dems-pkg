app.component('eyatraAgentBookingUpdateForm', {
    templateUrl: eyatra_agent_booking_update_form_template_url,
    controller: function($http, $location, $location, HelperService, $routeParams, $rootScope, $scope) {
        var fall_back_url = '/page-not-found';
        if (typeof($routeParams.visit_id) == 'undefined') {
            $location.path(fall_back_url)
            $scope.$apply()
            return;
        }
        $form_data_url = eyatra_agent_booking_update_form_data_url + '/' + $routeParams.visit_id;
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
                $location.path(fall_back_url)
                $scope.$apply()
                return;
            }
            self.visit = response.data.visit;
            self.travel_mode_list = response.data.travel_mode_list;
            $rootScope.loading = false;
        });

        self.show_form = false;
        var form_id = '#visit-booking-update-form';
        var v = jQuery(form_id).validate({
            errorPlacement: function(error, element) {
                error.insertAfter(element)
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
                    required: true,
                    maxlength: 10,
                    number: true,
                },
            },
            submitHandler: function(form) {

                let formData = new FormData($(form_id)[0]);
                $('#submit').button('loading');
                // var trip_id = $('#trip_id').val();
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
                            $location.path('/trips/booking-requests/view/' + $routeParams.visit_id)
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