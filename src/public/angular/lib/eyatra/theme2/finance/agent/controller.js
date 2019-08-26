app.component('eyatraAgentClaimVerificationList', {
    templateUrl: eyatra_finance_agent_claim_list_template_url,
    controller: function(HelperService, $rootScope, $scope, $http) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;

        $eyatra_agent_data = eyatra_agent_data_url;
        $http.get(
            $eyatra_agent_data
        ).then(function(response) {
            console.log();
            self.agent_claim_data = response.data.agent_claim_data;
            self.status = response.data.status;

            var dataTable = $('#eyatra_finance_agent_claim_list_table').DataTable({
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
                    url: laravel_routes['listFinanceEYatraAgentClaimList'],
                    type: "GET",
                    dataType: "json",
                    data: function(d) {
                        d.created_date = $('#created_date_filter').val();
                        d.invoice_date = $('#invoice_date_filter').val();
                        d.Agent_name = $('#Agent_id').val();
                        d.Agent_status = $('#Status_id').val();
                    }
                },
                columns: [
                    { data: 'action', searchable: false, class: 'action' },
                    { data: 'date', name: 'ey_agent_claims.invoice_date', searchable: false },
                    { data: 'number', name: 'ey_agent_claims.number', searchable: true },
                    { data: 'agent_code', name: 'agents.code', searchable: true },
                    { data: 'agent_name', name: 'agents.name', searchable: true },
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
            $('.page-header-content .display-inline-block .data-table-title').html('Claimed Trips');
            $('.add_new_button').html();

            $('#created_date_filter').change(function() {

                dataTable.draw();
            });
            $('#invoice_date_filter').change(function() {

                dataTable.draw();
            });
            $scope.getAgentClaimId = function(id) {
                $('#Agent_id').val(id);
                dataTable.draw();
            }
            $scope.getAgentStatusId = function(id) {
                $('#Status_id').val(id);
                dataTable.draw();
            }
            $('#reset_values').click(function() {
                $('#Agent_id').val('');
                $('#Status_id').val('');
                $('#invoice_date_filter').val('');
                $('#created_date_filter').val('');
                dataTable.draw();
            });

            /* Search Block */
            setTimeout(function() {
                var x = $('.separate-page-header-inner.search .custom-filter').position();
                var d = document.getElementById('eyatra_finance_agent_claim_list_table_filter');
                /* var d = $('.search-block-hide-inner .dataTables_filter').position(); */
                /* alert("Top: " + x.top + " Left: " + x.left); */
                /* x.top = x.top + 7; */
                x.left = x.left + 15;
                /* d.style.position = "absolute"; */
                d.style.left = x.left + 'px';
                /* d.style.top = x.top+'px'; */
            }, 500);

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
        });

    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('eyatraAgentClaimVerificationView', {
    templateUrl: eyatra_finance_agent_claim_form_template_url,
    controller: function($http, $location, HelperService, $routeParams, $rootScope, $scope) {
        $form_data_url = typeof($routeParams.agent_claim_id) == 'undefined' ? eyatra_finance_agent_claim_view_data_url : eyatra_finance_agent_claim_view_data_url + '/' + $routeParams.agent_claim_id;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        $http.get(
            $form_data_url
        ).then(function(response) {
            if (!response.data.success) {
                new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: response.data.message,
                }).show();
                $location.path('/eyatra/agent/claim/verification1/list')
                // $scope.$apply()
                return;
            }

            self.agent = response.data.agent;
            // console.log(response.data.agent);
            $scope.selectPaymentMode(self.agent.payment_mode_id);
            self.agent_claim_view = response.data.agent_claim_view;
            self.total_trips = response.data.total_trips;
            self.payment_mode_list = response.data.payment_mode_list;
            self.wallet_mode_list = response.data.wallet_mode_list;
            self.date = response.data.date;
            self.booking_list = response.data.booking_list;
            self.action = response.data.action;
            self.agent_claim_rejection = response.data.agent_claim_rejection;
            $rootScope.loading = false;
        });

        $scope.clearSearch = function() {
            $scope.search = '';
        };
        //SELECT PAYMENT MODE
        $scope.selectPaymentMode = function(payment_id) {
            if (payment_id == 3244) { //BANK
                $scope.showBank = true;
                $scope.showCheque = false;
                $scope.showWallet = false;
            } else if (payment_id == 3245) { //CHEQUE
                $scope.showBank = false;
                $scope.showCheque = true;
                $scope.showWallet = false;
            } else if (payment_id == 3246) { //WALLET
                $scope.showBank = false;
                $scope.showCheque = false;
                $scope.showWallet = true;
            } else {
                $scope.showBank = false;
                $scope.showCheque = false;
                $scope.showWallet = false;
            }
        }

        setTimeout(function() {
            var heights = new Array();
            // Loop to get all element Widths
            $('.equal-column').each(function() {
                // Need to let sizes be whatever they want so no overflow on resize
                // Then add size (no units) to array
                heights.push($(this).height());
            });
            /* alert(heights); */
            // Find max Width of all elements
            var max = Math.max.apply(Math, heights);
            // Set all Width to max Width
            $('.equal-column').each(function() {
                $(this).css('height', max + 'px');
                // Note: IF box-sizing is border-box, would need to manually add border and padding to Width (or tallest element will overflow by amount of vertical border + vertical padding)
            });
        }, 1500);

        $(".bottom-expand-btn").on('click', function() {
            if ($(".separate-bottom-fixed-layer").hasClass("in")) {
                $(".separate-bottom-fixed-layer").removeClass("in");
            } else {
                $(".separate-bottom-fixed-layer").addClass("in");
                $(".bottom-expand-btn").css({ 'display': 'none' });
            }
        });
        $(".btn-close").on('click', function() {
            if ($(".separate-bottom-fixed-layer").hasClass("in")) {
                $(".separate-bottom-fixed-layer").removeClass("in");
                $(".bottom-expand-btn").css({ 'display': 'inline-block' });
            } else {
                $(".separate-bottom-fixed-layer").addClass("in");
            }
        });

        $.validator.addMethod('positiveNumber',
            function(value) {
                return Number(value) > 0;
            }, 'Enter a positive number.');

        var form_id = '#agent-claim-form';
        var v = jQuery(form_id).validate({
            errorPlacement: function(error, element) {
                error.insertAfter(element)
            },
            ignore: '',
            rules: {
                'amount': {
                    min: 1,
                    number: true,
                    required: true,
                },
                'date': {
                    required: true,
                },
                'bank_name': {
                    required: true,
                    maxlength: 100,
                    minlength: 3,
                },
                'branch_name': {
                    required: true,
                    maxlength: 50,
                    minlength: 3,
                },
                'account_number': {
                    required: true,
                    maxlength: 20,
                    minlength: 3,
                    positiveNumber: true,
                },
                'ifsc_code': {
                    required: true,
                    maxlength: 10,
                    minlength: 3,
                },
            },
            submitHandler: function(form) {

                let formData = new FormData($(form_id)[0]);
                $('#submit').button('loading');
                $.ajax({
                        url: laravel_routes['payAgentClaimRequest'],
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
                            new Noty({
                                type: 'success',
                                layout: 'topRight',
                                text: 'Agent Claim Paid successfully',
                            }).show();
                            $location.path('/eyatra/agent/claim/verification1/list')
                            $scope.$apply()
                        }
                    })
                    .fail(function(xhr) {
                        $('#submit').button('reset');
                        custom_noty('error', 'Something went wrong at server');
                    });
            },
        });

        //Reject
        $(document).on('click', '.reject_btn', function() {
            var form_id = '#trip-reject-form';
            var v = jQuery(form_id).validate({
                ignore: '',

                submitHandler: function(form) {

                    let formData = new FormData($(form_id)[0]);
                    $('#reject_btn').button('loading');
                    $.ajax({
                            url: laravel_routes['rejectAgentClaimRequest'],
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
                                new Noty({
                                    type: 'success',
                                    layout: 'topRight',
                                    text: 'Agent Claim Rejected successfully',
                                }).show();
                                $('#alert-modal-reject').modal('hide');
                                setTimeout(function() {
                                    $location.path('/eyatra/agent/claim/verification1/list')
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