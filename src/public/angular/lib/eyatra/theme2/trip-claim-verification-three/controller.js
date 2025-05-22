app.component('eyatraTripClaimVerificationThreeList', {
    templateUrl: eyatra_trip_claim_verification_three_list_template_url,
    controller: function(HelperService, $rootScope, $scope, $http, $mdSelect) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.eyatra_outstation_trip_report_export_url = eyatra_outstation_trip_report_export_url;
        $http.get(
            trip_filter_data_url
        ).then(function(response) {
            console.log(response.data);
            self.employee_list = response.data.all_employee_list;
            self.purpose_list = response.data.purpose_list;
            self.trip_status_list = response.data.financier_status_list;
            $rootScope.loading = false;
        });
        self.csrf = $('#csrf').val();
        var dataTable = $('#eyatra_trip_claim_verification_three_list_table').DataTable({
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
                url: laravel_routes['listEYatraTripClaimVerificationThreeList'],
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
            var d = document.getElementById('eyatra_trip_claim_verification_three_list_table_filter');
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

        /* Modal Md Select Hide */
        $('.modal').bind('click', function(event) {
            if ($('.md-select-menu-container').hasClass('md-active')) {
                $mdSelect.hide();
            }
        });

        //Import
        /* File Upload Function */
        /* Main Function */
        var img_close = $('#img-close').html();
        var img_file = $('#img-file').html();
        var del_arr = [];
        $(".form-group").on('change', '.input-file', function() {
            var html = [];
            del_arr = [];
            $(".insert-file").empty();
            $(this.files).each(function(i, v) {
                html.push("<div class='file-return-parent'>" + img_file + "<p class='file-return'>" + v.name + "</p><button type='button' onclick='angular.element(this).scope().deletefiles(this)' class='remove-hn btn' >" + img_close + "</button></div>");
                del_arr.push(v.name);
            });
            $(".insert-file").append(html);
        });
        /* Remove Function */
        $scope.deletefiles = function(this_parents) {
            var del_name = $(this_parents).siblings().text();
            var del_index = del_arr.indexOf(del_name);
            if (del_index >= 0) {
                del_arr.splice(del_index, 1);
            }
            $(this_parents).parent().remove();
            $(".file_check").val('');
        }

        // getCTCProgressBar
        $('.card-transition').hide();
        //Import Progress
        var form_id = '#outstaion_trip_import_form';
        var v = jQuery(form_id).validate({
            ignore: "",
            errorPlacement: function(error, element) {
                if (element.hasClass("input_excel")) {
                    error.appendTo('.errors');
                } else if (element.attr("name") == "attachment") {
                    error.appendTo('#error_attach');
                    return;
                } else {
                    error.insertAfter(element)
                }
            },
            rules: {
                // attachment: {
                //     // required: true,
                //     // extension: "xlsx,xls",
                // },
            },

            submitHandler: function(form) {
                $('#import_errors').html('');
                $('.card-transition').show();
                $('#import_outstion_claim').button('loading');
                let Upl = new FormData($('#outstaion_trip_import_form')[0]);
                $.ajax({
                        url: eyatra_trip_claim_verification_three_import,
                        method: "POST",
                        headers: { 'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content') },
                        enctype: 'multipart/form-data',
                        data: Upl,
                        processData: false,
                        contentType: false,
                        cache: false,
                    })
                    .done(function(response) {

                        if (response.success) {
                            $('.card-transition').addClass('card-transition-active');
                            $('.card-button').addClass('card-button-active');
                            $('.card-progress').addClass('card-progress-active');
                            $('.btn-circle').addClass('btn-circle-active');
                            $('.btn-over').addClass('btn-over-active');
                            $('.card-button').hide();
                            $('#total_count').html(response.total_records);
                            // $('#import_status span').html('Importing records');
                            if (response.total_records > 0) {
                                $('#download_error_report').attr('href', response.error_report_url).hide();
                                $('#model_download_error_report').attr('href', response.error_report_url);

                                remaining_rows = response.total_records;
                                total_rows = response.total_records;
                                file = response.file;
                                outputfile = response.outputfile;
                                headings = response.headings;
                                reference = response.reference;

                                $('#import_errors').append('<div class="text-left text-primary">Import Reference ID: ' + response.reference + '</div>')
                                imports();
                            }
                        } else {
                            $('#import_outstion_claim').button('reset');
                            $('#import_errors').html(response.errors)
                            console.log(response.missing_fields);
                            if (response.error == "Invalid File, Mandatory fields are missing.") {
                                for (var i in response.missing_fields) {
                                    $('#import_errors').append('<div class="text-left">' + response.missing_fields[i] + '</div>')
                                }
                                alert('Invalid File, Mandatory fields are missing');
                            }
                            $('#import_outstion_claim').button('reset');
                        }
                    })
                    .fail(function(xhr, ajaxOptions, thrownError) {
                        alert(thrownError);
                        $('#import_outstion_claim').button('reset');
                    })
            }
        });

        $('#download_error_report').hide();

        function getDateTime() {
            var now = new Date();
            var year = now.getFullYear();
            var month = now.getMonth() + 1;
            var day = now.getDate();
            var hour = now.getHours();
            var minute = now.getMinutes();
            var second = now.getSeconds();

            var x = Math.floor((Math.random() * 1000) + 1);

            var dateTime = year + '' + month + '' + day + '' + hour + '' + minute + '' + second + '' + x;
            return dateTime;
        }

        var total_rows = 0;
        var remaining_rows = 0;
        var imported_rows = 0;
        var file = '';
        var outputfile = '';
        var headings = '';
        var reference = '';
        var records_per_request = 50;
        var import_number = getDateTime();


        function imports() {
            $.ajax({
                    url: eyatra_trip_claim_verification_three_chunk_import,
                    method: "POST",
                    headers: { 'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content') },
                    data: { skip: imported_rows, file: file, records_per_request: records_per_request, outputfile: outputfile, headings: headings, reference: reference, import_number: import_number },
                })
                .done(function(response) {
                    console.log(response);
                    if (response.success) {
                        var new_count = parseInt($('#new_count').html()) + response.newCount;
                        var updated_count = parseInt($('#updated_count').html()) + response.updatedCount;
                        var error_count = parseInt($('#error_count').html()) + response.errorCount;
                        var new_ratio = parseFloat((new_count / total_rows) * 100).toFixed(2);
                        var updated_ratio = parseFloat((updated_count / total_rows) * 100).toFixed(2);
                        var error_ratio = parseFloat((error_count / total_rows) * 100).toFixed(2);
                        $('#import_progress .progress-bar-success').attr('style', 'width:' + new_ratio + '%;');
                        $('#import_progress .progress-bar-success').html(new_ratio + '%');
                        $('#import_progress .progress-bar-warning').attr('style', 'width:' + updated_ratio + '%;');
                        $('#import_progress .progress-bar-warning').html(updated_ratio + '%');
                        $('#import_progress .progress-bar-danger').attr('style', 'width:' + error_ratio + '%;');
                        $('#import_progress .progress-bar-danger').html(error_ratio + '%');
                        $('#import_errors').append(response.errors);
                        $('#new_count').html(new_count);
                        $('#updated_count').html(updated_count);
                        $('#error_count').html(error_count);
                        imported_rows += response.processed;

                        $('#import_status span').html('Inprogress (Processed: ' + imported_rows + ' Remaining: ' + (total_rows - imported_rows) + ')')
                        remaining_rows -= response.processed;

                        $('#import_progress span').html(parseInt(new_ratio) + '% Completed')
                        $('.skillbar').attr('style', 'width:' + parseInt(new_ratio) + '%;')

                        $('#import_errors').append(response.errors)
                        $('#new_count').html(new_count)
                        $('#error_count').html(error_count)
                        if (remaining_rows > 0) {
                            imports();
                        } else {
                            $('#import_progress span').html(parseInt(new_ratio) + '% Completed')
                            $('.skillbar').attr('style', 'width:' + parseInt(new_ratio) + '%;')
                            $('#import_status span').html('Completed')
                            $('#download_error_report').attr('href', response.error_report_url);
                            if (error_count > 0) {
                                $('#error_button').css('display', 'inline-block');
                                $('#download_error_report').css('display', 'inline-block');
                                $('#error_table').html(response.errors);
                            } else {
                                $('#error_button').hide();
                                $('#download_error_report').hide();
                                $('#error_table').html('No error Found');
                            }
                        }
                    } else {
                        $('#download_error_report').css('display', 'inline-block');
                        // alert('Error:'+response.error )
                        $('#import_errors').append(response.error)
                    }
                })
                .fail(function(xhr, ajaxOptions, thrownError) {
                    alert('An error occured during import')
                })

            // $('#import_outstion_claim').button('reset');
            $("#import_outstion_claim").prop("disabled", true);
        }

        $scope.popup_close = function() {
            // $window.location.reload();
            location.reload();
        };

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

        $rootScope.loading = false;

    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('eyatraTripClaimVerificationThreeView', {
    templateUrl: eyatra_trip_claim_verification_three_view_template_url,
    controller: function($http, $location, $location, HelperService, $routeParams, $rootScope, $scope) {
        $form_data_url = typeof($routeParams.trip_id) == 'undefined' ? eyatra_trip_claim_verification_three_view_url + '/' : eyatra_trip_claim_verification_three_view_url + '/' + $routeParams.trip_id;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        self.eyatra_trip_claim_verification_three_visit_attachment_url = eyatra_trip_claim_verification_three_visit_attachment_url;
        self.eyatra_trip_claim_verification_three_lodging_attachment_url = eyatra_trip_claim_verification_three_lodging_attachment_url;
        self.eyatra_trip_claim_verification_three_boarding_attachment_url = eyatra_trip_claim_verification_three_boarding_attachment_url;
        self.eyatra_trip_claim_verification_three_local_travel_attachment_url = eyatra_trip_claim_verification_three_local_travel_attachment_url;
        self.eyatra_trip_claim_google_attachment_url = eyatra_trip_claim_google_attachment_url;
        self.eyatra_trip_claim_transport_attachment_url = eyatra_trip_claim_transport_attachment_url;
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
                $location.path('/trip/claim/verification3/list')
                $scope.$apply()
                return;
            }
            self.trip = response.data.trip;
            self.gender = (response.data.trip.employee.gender).toLowerCase();
            self.travel_cities = response.data.travel_cities;
            self.travel_dates = response.data.travel_dates;
            self.trip_claim_rejection_list = response.data.trip_claim_rejection_list;
            // self.transport_total_amount = response.data.transport_total_amount;
            // self.lodging_total_amount = response.data.lodging_total_amount;
            // self.boardings_total_amount = response.data.boardings_total_amount;
            // self.local_travels_total_amount = response.data.local_travels_total_amount;
            self.total_amount = response.data.trip.employee.trip_employee_claim.total_amount;
            self.trip_justify = response.data.trip_justify;
            self.date = response.data.date;
            // console.log(response.data.trip.employee.trip_employee_claim.amount_to_pay);
            self.payment_mode_list = response.data.payment_mode_list;
            self.wallet_mode_list = response.data.wallet_mode_list;
            $scope.selectPaymentMode(self.trip.employee.payment_mode_id);

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
            self.view = response.data.view;
            $rootScope.loading = false;

        });

        // //TOOLTIP MOUSEOVER
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

        $(".bottom-expand-btn").on('click', function() {
            console.log(' click ==');
            if ($(".separate-bottom-fixed-layer").hasClass("in")) {
                console.log(' has ==');

                $(".separate-bottom-fixed-layer").removeClass("in");
            } else {
                console.log(' has not ==');

                $(".separate-bottom-fixed-layer").addClass("in");
                $(".bottom-expand-btn").css({ 'display': 'none' });
            }
        });
        $(".btn_close").on('click', function() {
            if ($(".separate-bottom-fixed-layer").hasClass("in")) {
                $(".separate-bottom-fixed-layer").removeClass("in");
                $(".bottom-expand-btn").css({ 'display': 'inline-block' });
            } else {
                $(".separate-bottom-fixed-layer").addClass("in");
            }
        });

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

        $.validator.addMethod('positiveNumber',
            function(value) {
                return Number(value) > 0;
            }, 'Enter a positive number.');

        var form_id = '#trip-claim-finance-form';
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
                        url: laravel_routes['approveTripClaimVerificationThree'],
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
                                text: 'Trip Claim Approved successfully',
                                animation: {
                                    speed: 500 // unavailable - no need
                                },
                            }).show();
                            setTimeout(function() {
                                $noty.close();
                            }, 1000);
                            $location.path('/trip/claim/verification3/list')
                            $scope.$apply()
                        }
                    })
                    .fail(function(xhr) {
                        $('#submit').button('reset');
                        custom_noty('error', 'Something went wrong at server');
                    });
            },
        });


        //APPROVE
        $(document).on('click', '.btn-approve', function() {
            $trip_id = $('#trip_id').val();
            $('.btn-approve').button('loading');
            $http.get(
                eyatra_trip_claim_verification_three_approve_url + '/' + $trip_id,
            ).then(function(response) {
                if (!response.data.success) {
                    $('.btn-approve').button('reset');
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
                    $('#trip-claim-modal-approve-three').modal('hide');
                    setTimeout(function() {
                        $location.path('/trip/claim/verification3/list')
                        $scope.$apply()
                    }, 1000);
                }

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
                            url: laravel_routes['rejectTripClaimVerificationThree'],
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
                                $('#trip-claim-modal-reject-three').modal('hide');
                                setTimeout(function() {
                                    $location.path('/trip/claim/verification3/list')
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

        //HOLD TRIP CLAIM
        $scope.tripClaimHold = function(trip_id) {
            if (trip_id) {
                $('#Hold').button('loading');
                $.ajax({
                        url: laravel_routes['holdTripClaimVerificationThree'],
                        method: "POST",
                        data: { trip_id: trip_id },
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
                                text: 'Trips Claim Holded successfully',
                                animation: {
                                    speed: 500 // unavailable - no need
                                },
                            }).show();
                            setTimeout(function() {
                                $noty.close();
                            }, 1000);
                            setTimeout(function() {
                                $location.path('/trip/claim/verification3/list')
                                $scope.$apply()
                            }, 500);

                        }
                    })
                    .fail(function(xhr) {
                        console.log(xhr);
                    });
            }
        }

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

//EMPLOYEE PAY TO COMPANY FOR TRIP CLAIM
app.component('eyatraTripClaimPaymentPendingList', {
    templateUrl: eyatra_trip_claim_payment_pending_list_template_url,
    controller: function(HelperService, $location, $rootScope, $scope, $http) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        $http.get(
            trip_filter_data_url
        ).then(function(response) {
            console.log(response.data);
            self.employee_list = response.data.employee_list;
            self.purpose_list = response.data.purpose_list;
            self.trip_status_list = response.data.trip_status_list;
            self.outlet_list = response.data.outlet_list;
            $rootScope.loading = false;
        });
        var dataTable = $('#payment_pending_list_table').DataTable({
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
                url: laravel_routes['listEYatraTripClaimPaymentPendingList'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.employee_id = $('#employee_id').val();
                    d.purpose_id = $('#purpose_id').val();
                    // d.status_id = $('#status_id').val();
                    d.outlet = $('#outlet_id').val();
                }
            },
            columns: [
                { data: 'checkbox', searchable: false },
                { data: 'action', searchable: false, class: 'action' },
                { data: 'number', name: 'trips.number', searchable: true },
                { data: 'ecode', name: 'e.code', searchable: true },
                { data: 'ename', name: 'users.name', searchable: true },
                { data: 'outlet_name', name: 'outlets.name', searchable: true },
                { data: 'advance_received', name: 'trips.advance_received', searchable: false },
                { data: 'total_amount', searchable: false },
                { data: 'balance_amount', searchable: false },
                { data: 'status', name: 'status.name', searchable: true },


            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();

        setTimeout(function() {
            var x = $('.separate-page-header-inner.search .custom-filter').position();
            var d = document.getElementById('payment_pending_list_table_filter');
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
        /* $scope.getStatusData = function(query) {
             $('#status_id').val(query);
             dataTable.draw();
         }*/
        $scope.onSelectOutlet = function(query) {
            $('#outlet_id').val(query);
            dataTable.draw();
        }
        $scope.reset_filter = function(query) {
            $('#employee_id').val(-1);
            $('#purpose_id').val(-1);
            // $('#status_id').val(-1);
            $('#outlet_id').val(-1);
            dataTable.draw();
        }


        $('#head_booking').on('click', function() {
            var count = 0;
            selected_employee_claims = [];
            if (event.target.checked == true) {
                $('.employee_claim_list').prop('checked', true);
                $.each($('.employee_claim_list:checked'), function() {
                    count++;
                    selected_employee_claims.push($(this).val());
                });
            } else {
                $('.employee_claim_list').prop('checked', false);
            }
            if (count > 0) {
                $('#approve').css({ 'display': 'inline-block' });

            } else {
                $('#approve').css({ 'display': 'none' });

            }
            $('.approve_ids').val(selected_employee_claims);

        });

        $(document.body).on('click', '.employee_claim_list', function() {
            var count = 0;
            selected_employee_claims = [];
            $.each($('.employee_claim_list:checked'), function() {
                count++;
                selected_employee_claims.push($(this).val());
            });
            if (count > 0) {
                $('#approve').css({ 'display': 'inline-block' });

            } else {
                $('#approve').css({ 'display': 'none' });

            }
            $('.approve_ids').val(selected_employee_claims);
        });


        $(document.body).on('click', '.approve_claim', function() {
            var id = $(this).data('claim_id');
            $http.post(
                employee_claim_payment_pending_single_approve_url, { id: id },
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
                    $noty = new Noty({
                        type: 'success',
                        layout: 'topRight',
                        text: 'Employee Claim Payment Pending Approved Successfully',
                        animation: {
                            speed: 500 // unavailable - no need
                        },
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 5000);

                    $('#approve').css({ 'display': 'none' });

                    var dataTableFilter = $('#payment_pending_list_table').dataTable();
                    dataTableFilter.fnFilter();
                    $location.path('/trip/claim/payment-pending/list');
                    $scope.$apply();
                    // window.location.href = laravel_routes['listEYatraTripClaimPaymentPendingList'];
                }
            });
        });

        $('#approve').on('click', function() {
            var approve_ids = $('.approve_ids').val();
            $http.post(
                employee_claim_payment_pending_approve_url, { approve_ids: approve_ids },
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
                    $noty = new Noty({
                        type: 'success',
                        layout: 'topRight',
                        text: 'Employee Claim Payment Pending Approved Successfully',
                        animation: {
                            speed: 500 // unavailable - no need
                        },
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 5000);

                    $('#approve').css({ 'display': 'none' });

                    var dataTableFilter = $('#payment_pending_list_table').dataTable();
                    dataTableFilter.fnFilter();
                    $location.path('/trip/claim/payment-pending/list');
                    $scope.$apply();
                    // window.location.href = laravel_routes['listEYatraTripClaimPaymentPendingList'];
                }

            });

        });


        // $('.separate-page-header-content .data-table-title').html('<p class="breadcrumb">Claims</p><h3 class="title">Claimed Trips Verification Three</h3>');
        //$('.page-header-content .display-inline-block .data-table-title').html('Employees');

        // $('.add_new_button').html();

        $rootScope.loading = false;

    }
});