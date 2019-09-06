app.component('eyatraTripClaimList', {
    templateUrl: eyatra_trip_claim_list_template_url,
    controller: function(HelperService, $rootScope, $scope, $http) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        var dataTable = $('#eyatra_trip_claim_list_table').DataTable({
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
        $('.page-header-content .display-inline-block .data-table-title').html('Claimed Trips');
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
                        text: 'Trips Deleted Successfully',
                        animation: {
                            speed: 500 // unavailable - no need
                        },
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 1000);
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
    controller: function($http, $location, $location, HelperService, $routeParams, $rootScope, $scope) {
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
                $location.path('/eyatra/trip/claim/list')
                $scope.$apply()
                return;
            }
            self.extras = response.data.extras;
            self.trip = response.data.trip;

            if (self.trip.lodgings.length == 0) {
                self.addNewLodgings();
            }
            if (self.trip.boardings.length == 0) {
                self.addNewBoardings();
            }
            if (self.trip.local_travels.length == 0) {
                self.addNewLocalTralvels();
            }
            $rootScope.loading = false;

        });

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
                amount: '',
                tax: '',
                remarks: '',
            });
        }
        self.removeLodging = function(index, lodging_id) {
            if (lodging_id) {
                lodgings_removal_id.push(lodging_id);
                $('#lodgings_removal_id').val(JSON.stringify(lodgings_removal_id));
            }
            self.trip.lodgings.splice(index, 1);
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
            });
        }
        self.removeBoarding = function(index, boarding_id) {
            if (boarding_id) {
                boardings_removal_id.push(boarding_id);
                $('#boardings_removal_id').val(JSON.stringify(boardings_removal_id));
            }
            self.trip.boardings.splice(index, 1);
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
            });
        }
        self.removeLocalTralvel = function(index, local_travel_id) {
            if (local_travel_id) {
                local_travels_removal_id.push(local_travel_id);
                $('#local_travels_removal_id').val(JSON.stringify(local_travels_removal_id));
            }
            self.trip.local_travels.splice(index, 1);
        }

        //Form submit validation
        self.claimSubmit = function() {
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
                $noty = new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: 'Kindly check in each tab to fix errors',
                    animation: {
                        speed: 500 // unavailable - no need
                    },
                }).show();
                setTimeout(function() {
                    $noty.close();
                }, 1000);
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
                            $noty = new Noty({
                                type: 'success',
                                layout: 'topRight',
                                text: 'Claim saved successfully!!',
                                animation: {
                                    speed: 500 // unavailable - no need
                                },
                            }).show();
                            setTimeout(function() {
                                $noty.close();
                            }, 1000);

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
                $location.path('/eyatra/trip/claim/list')
                $scope.$apply()
                return;
            }
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