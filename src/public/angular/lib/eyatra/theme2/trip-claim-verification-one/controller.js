app.component('eyatraTripClaimVerificationOneList', {
    templateUrl: eyatra_trip_claim_verification_one_list_template_url,
    controller: function(HelperService, $rootScope, $scope, $http) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        $http.get(
            trip_filter_data_url
        ).then(function(response) {
            console.log(response.data);
            self.employee_list = response.data.employee_list;
            self.purpose_list = response.data.purpose_list;
            self.trip_status_list = response.data.trip_status_list;
            $rootScope.loading = false;
        });

        var dataTable = $('#eyatra_trip_claim_verification_one_list_table').DataTable({
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
                url: laravel_routes['listEYatraTripClaimVerificationOneList'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.employee_id = $('#employee_id').val();
                    d.purpose_id = $('#purpose_id').val();
                    d.status_id = $('#status_id').val();
                    d.from_date = $('#from_date').val();
                    d.to_date = $('#to_date').val();
                }
            },
            columns: [
                { data: 'action', searchable: false, class: 'action' },
                { data: 'claim_number', name: 'ey_employee_claims.number', searchable: true },
                { data: 'number', name: 'trips.number', searchable: true },
                { data: 'ecode', name: 'e.code', searchable: true },
                { data: 'ename', name: 'users.name', searchable: false },
                { data: 'start_date', name: 'trips.start_date', searchable: false },
                { data: 'end_date', name: 'trips.end_date', searchable: false },
                // { data: 'cities', name: 'c.name', searchable: true },
                { data: 'purpose', name: 'purpose.name', searchable: true },
                { data: 'advance_received', name: 'trips.advance_received', searchable: false },
                { data: 'reason', name: 'reason', searchable: true },
                { data: 'status', name: 'status.name', searchable: true },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();

        setTimeout(function() {
            var x = $('.separate-page-header-inner.search .custom-filter').position();
            var d = document.getElementById('eyatra_trip_claim_verification_one_list_table_filter');
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

            $('#from_date').val(query);
            dataTable.draw();
        }
        $scope.getToDateData = function(query) {

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
        // $('.separate-page-header-content .data-table-title').html('<p class="breadcrumb">Claims</p><h3 class="title">Claimed Trips Verification One</h3>');
        //$('.page-header-content .display-inline-block .data-table-title').html('Employees');

        // $('.add_new_button').html();

        $rootScope.loading = false;

    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('eyatraTripClaimVerificationOneView', {
    templateUrl: eyatra_trip_claim_verification_one_view_template_url,
    controller: function($http, $location, $location, HelperService, $routeParams, $rootScope, $scope, $mdSelect) {
        $form_data_url = typeof($routeParams.trip_id) == 'undefined' ? eyatra_trip_claim_verification_one_view_url + '/' : eyatra_trip_claim_verification_one_view_url + '/' + $routeParams.trip_id;
        var self = this;
        $scope.apiLoaded = false;  // Hide bottom bar initially
        $scope.visit = false; 
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        self.eyatra_trip_claim_verification_one_visit_attachment_url = eyatra_trip_claim_verification_one_visit_attachment_url;
        self.eyatra_trip_claim_verification_one_lodging_attachment_url = eyatra_trip_claim_verification_one_lodging_attachment_url;
        self.eyatra_trip_claim_verification_one_boarding_attachment_url = eyatra_trip_claim_verification_one_boarding_attachment_url;
        self.eyatra_trip_claim_verification_one_local_travel_attachment_url = eyatra_trip_claim_verification_one_local_travel_attachment_url;
        self.eyatra_trip_claim_verification_one_local_travel_attachment_url_new = eyatra_trip_claim_verification_one_local_travel_attachment_url_new;
        self.eyatra_trip_claim_google_attachment_url = eyatra_trip_claim_google_attachment_url;
        self.eyatra_trip_claim_transport_attachment_url = eyatra_trip_claim_transport_attachment_url;
        self.page_type = typeof($location.search().type) === 'undefined' ? '' : $location.search().type;
        if(self.page_type == 1){
            self.list_page_url = '/trip/approvals';
        }else{
            self.list_page_url = '/trip/claim/verification1/list';
        }
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
                // $location.path('/trip/claim/verification1/list')
                $location.path(self.list_page_url)
                $scope.$apply()
                return;
            }
            self.trip = response.data.trip;
            self.gender = (response.data.trip.employee.gender).toLowerCase();
            self.travel_cities = response.data.travel_cities;
            self.trip_claim_rejection_list = response.data.trip_claim_rejection_list;
            self.travel_dates = response.data.travel_dates;
            // self.transport_total_amount = response.data.transport_total_amount;
            // self.lodging_total_amount = response.data.lodging_total_amount;
            // self.boardings_total_amount = response.data.boardings_total_amount;
            // self.local_travels_total_amount = response.data.local_travels_total_amount;
            self.total_amount = response.data.trip.employee.trip_employee_claim.total_amount;
            self.trip_justify = response.data.trip_justify;
            // console.log(response.data.trip.transport_attachments);
            // console.log(response.data.trip.boarding_attachments);
            // console.log(response.data.trip.lodging_attachments);
            // console.log(response.data.trip.local_travel_attachments);
            console.log(response.data.trip.trip_attachments);
            console.log(response.data.trip.google_attachments);
            if (response.data.trip.trip_attachments.length === 0 && response.data.trip.google_attachments.length === 0) {
                $scope.visit = false;
            } else {
                $scope.visit = true;
            }
            $scope.apiLoaded = true;
            console.log($scope.apiLoaded);
            $scope.doSomethingOnClick = function() {
                $scope.visit = false;
            }
            self.scrollToTop = function() {
                self.scrollTo(0);
            };
            
            self.scrollToBottom = function() {
                var scrollHeight = document.documentElement.scrollHeight || document.body.scrollHeight;
                self.scrollTo(scrollHeight);
            };
            
            self.scrollTo = function(position) {
                window.scrollTo({
                    top: position,
                    behavior: 'smooth'
                });
            };
            
            if (self.trip.advance_received) {
                if (parseFloat(self.total_amount) > parseFloat(self.trip.advance_received)) {
                    self.pay_to_employee = Math.round(parseFloat(self.total_amount) - parseFloat(self.trip.advance_received)).toFixed(2);
                    self.pay_to_company = '0.00';
                } else if (parseFloat(self.total_amount) < parseFloat(self.trip.advance_received)) {
                    self.pay_to_employee = '0.00';
                    self.pay_to_company = Math.round(parseFloat(self.trip.advance_received) - parseFloat(self.total_amount)).toFixed(2);
                } else {
                    self.pay_to_employee = '0.00';
                    self.pay_to_company = '0.00';
                }
            } else {
                self.pay_to_employee = Math.round(parseFloat(self.total_amount)).toFixed(2);
                self.pay_to_company = '0.00';
            }
            $scope.visit = response.data.approval_status;
            self.view = response.data.view;
            $rootScope.loading = false;

        });
        // UPDATE ATTACHMENT STATUS BY KARTHICK T ON 20-01-2022
        $scope.updateAttchementStatus = function(attchement_id) {
            if (attchement_id) {
                this.view_status = 1;
                $http.post(
                    laravel_routes['updateAttachmentStatus'], {
                        id: attchement_id,
                    }
                ).then(function(res) {
                    if (res.data.success) {
                        $scope.visit = res.data.approval_status;
                    }
                });
            }
        }
        // UPDATE ATTACHMENT STATUS BY KARTHICK T ON 20-01-2022
        $scope.viewAllAttachments = function() {
            angular.forEach($scope.$ctrl.trip.trip_attachments, function(attachment) {
                var url = '';
                if (attachment.attachment_name.name === 'Tour Report') {
                    // Open each Google attachment for Tour Report
                    angular.forEach($scope.$ctrl.trip.google_attachments, function(attach) {
                        var gUrl = $scope.$ctrl.eyatra_trip_claim_google_attachment_url + '/' + attach.name;
                        window.open(gUrl, '_blank');
                        // Update status for Google attachments
                        if (attach.id) {
                            $http.post(laravel_routes['updateAttachmentStatus'], { id: attach.id });
                        }
                    });
                } else {
                    url = 'storage/app/public/trip/claim/' + attachment.entity_id + '/' + attachment.name;
                    window.open(url, '_blank');
                    if (attachment.id) {
                        $http.post(laravel_routes['updateAttachmentStatus'], { id: attachment.id });
                    }
                }
            });
            setTimeout(function() {
                location.reload();
            }, 1000);
        };
        
        //TOOLTIP MOUSEOVER
        // $(document).on('mouseover', ".attachment_tooltip", function() {
        //     var $this = $(this);
        //     $this.tooltip({
        //         title: $this.attr('data-title'),
        //         placement: "top"
        //     });
        //     $this.tooltip('show');
        // });
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
        /*$scope.confirmTripClaimApproveOne = function() {
            $trip_id = $('#modal_trip_id').val();
            $http.get(
                eyatra_trip_claim_verification_one_approve_url + '/' + $trip_id,
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
                        text: 'Trips Claim Approved Successfully',
                        animation: {
                            speed: 500 // unavailable - no need
                        },
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 1000);
                    $('#trip-claim-modal-approve-one').modal('hide');
                    setTimeout(function() {
                        $location.path('/trip/claim/verification1/list')
                        $scope.$apply()
                    }, 500);

                }

            });
        }
*/
        //APPROVE
        $(document).on('click', '.verification_one_btn', function() {
            var form_id = '#verification-one-form';
            var v = jQuery(form_id).validate({
                ignore: '',

                submitHandler: function(form) {

                    let formData = new FormData($(form_id)[0]);
                    $('#verification_one_btn').button('loading');
                    $.ajax({
                            url: laravel_routes['approveTripClaimVerificationOne'],
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                        })
                        .done(function(res) {
                            console.log(res.success);
                            if (!res.success) {
                                $('#verification_one_btn').button('reset');
                                var errors = '';
                                for (var i in res.errors) {
                                    errors += '<li>' + res.errors[i] + '</li>';
                                }
                                custom_noty('error', errors);
                            } else {
                                $noty = new Noty({
                                    type: 'success',
                                    layout: 'topRight',
                                    text: 'Trips Claim Approved Successfully',
                                    animation: {
                                        speed: 500 // unavailable - no need
                                    },
                                }).show();
                                setTimeout(function() {
                                    $noty.close();
                                }, 1000);
                                $('#trip-claim-modal-approve-one').modal('hide');
                                setTimeout(function() {
                                    // $location.path('/trip/claim/verification1/list')
                                    $location.path(self.list_page_url)
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

        //Reject
        $(document).on('click', '.reject_btn', function() {
            var form_id = '#trip-claim-reject-form';
            var v = jQuery(form_id).validate({
                ignore: '',

                submitHandler: function(form) {

                    let formData = new FormData($(form_id)[0]);
                    $('#reject_btn').button('loading');
                    $.ajax({
                            url: laravel_routes['rejectTripClaimVerificationOne'],
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
                                    animation: {
                                        speed: 500 // unavailable - no need
                                    },
                                }).show();
                                setTimeout(function() {
                                    $noty.close();
                                }, 1000);
                                $('#trip-claim-modal-reject-one').modal('hide');
                                setTimeout(function() {
                                    // $location.path('/trip/claim/verification1/list')
                                    $location.path(self.list_page_url)
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
        /* Modal Md Select Hide */
        $('.modal').bind('click', function(event) {
            if ($('.md-select-menu-container').hasClass('md-active')) {
                $mdSelect.hide();
            }
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