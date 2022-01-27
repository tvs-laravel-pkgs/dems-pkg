app.component('eyatraLocalTripFinancierVerification', {
    templateUrl: eyatra_local_trip_financier_verification_list_template_url,
    controller: function(HelperService, $rootScope, $http, $scope, $mdSelect) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.eyatra_local_trip_report_export_url = eyatra_local_trip_report_export_url;
        $http.get(
            local_trip_financier_verification_filter_data_url
        ).then(function(response) {
            console.log(response.data);
            self.employee_list = response.data.employee_list;
            self.purpose_list = response.data.purpose_list;
            self.financier_status_list = response.data.financier_status_list;
            self.trip_status_list = response.data.trip_status_list;
            $rootScope.loading = false;
        });
        self.csrf = $('#csrf').val();
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
                url: laravel_routes['listFinancierLocalTripVerification'],
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

        /* Modal Md Select Hide */
        $('.modal').bind('click', function(event) {
            if ($('.md-select-menu-container').hasClass('md-active')) {
                $mdSelect.hide();
            }
        });

        // getCTCProgressBar
        $('.card-transition').hide();
        //Import Progress
        var form_id = '#local_trip_import_form';
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
                $('#import_local_claim').button('loading');
                let Upl = new FormData($('#local_trip_import_form')[0]);
                $.ajax({
                        url: local_trip_financier_import,
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
                            $('#import_local_claim').button('reset');
                            $('#import_errors').html(response.errors)
                            console.log(response.missing_fields);
                            if (response.error == "Invalid File, Mandatory fields are missing.") {
                                for (var i in response.missing_fields) {
                                    $('#import_errors').append('<div class="text-left">' + response.missing_fields[i] + '</div>')
                                }
                                alert('Invalid File, Mandatory fields are missing');
                            }
                            $('#import_local_claim').button('reset');
                        }
                    })
                    .fail(function(xhr, ajaxOptions, thrownError) {
                        alert(thrownError);
                        $('#import_local_claim').button('reset');
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
                    url: local_trip_financier_chunk_import,
                    method: "POST",
                    headers: { 'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content') },
                    data: { skip: imported_rows, file: file, records_per_request: records_per_request, outputfile: outputfile, headings: headings, reference: reference, import_number: import_number},
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

            // $('#import_local_claim').button('reset');
            $("#import_local_claim").prop("disabled", true);
        }

        $scope.popup_close = function() {
            // $window.location.reload();
            location.reload();
        };

        $rootScope.loading = false;
    }
});
app.component('eyatraLocalTripFinancierVerificationView', {
    templateUrl: local_trip_financier_verification_view_template_url,
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
            console.log(self.trip_claim_rejection_list);
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

        $('.btn-nxt').on("click", function() {
            $('.editDetails-tabs li.active').next().children('a').trigger("click");
        });
        $('.btn-prev').on("click", function() {
            $('.editDetails-tabs li.active').prev().children('a').trigger("click");
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

        //APPROVE TRIP
        self.approveTrip = function(id) {
            $('#trip_id').val(id);
        }

        $scope.clearSearch = function() {
            $scope.search = '';
        };

        $(document).on('click', '.approve_btn', function() {
            var form_id = '#trip-claim-finance-form';
            var v = jQuery(form_id).validate({
                ignore: '',

                submitHandler: function(form) {

                    let formData = new FormData($(form_id)[0]);
                    $('#submit').button('loading');
                    $.ajax({
                            url: laravel_routes['financierApproveLocalTrip'],
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
                                    text: 'Financier Approved successfully',
                                    animation: {
                                        speed: 500 // unavailable - no need
                                    },
                                }).show();
                                setTimeout(function() {
                                    $noty.close();
                                }, 1000);
                                setTimeout(function() {
                                    $location.path('/local-trip/financier/verification/list')
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

        //Hold

        $scope.tripClaimHold = function(trip_id) {
            if (trip_id) {
                $('#Hold').button('loading');
                $.ajax({
                        url: laravel_routes['financierHoldLocalTrip'],
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
                            }).show();
                            setTimeout(function() {
                                $noty.close();
                            }, 5000);
                            setTimeout(function() {
                                $location.path('/local-trip/financier/verification/list')
                                $scope.$apply()
                            }, 500);

                        }
                    })
                    .fail(function(xhr) {
                        console.log(xhr);
                    });
            }
        }

        //Reject
        $(document).on('click', '.reject_btn', function() {
            var form_id = '#trip-claim-reject-form';
            var v = jQuery(form_id).validate({
                ignore: '',

                submitHandler: function(form) {

                    let formData = new FormData($(form_id)[0]);
                    $('#reject_btn').button('loading');
                    $.ajax({
                            url: laravel_routes['financierRejectLocalTrip'],
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
                                    text: 'Financier Rejected successfully',
                                    animation: {
                                        speed: 500 // unavailable - no need
                                    },
                                }).show();
                                setTimeout(function() {
                                    $noty.close();
                                }, 1000);
                                $('#trip-claim-modal-reject-three').modal('hide');
                                setTimeout(function() {
                                    $location.path('/local-trip/financier/verification/list')
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
