app.component('masterReportingManager', {
    templateUrl: 'public/angular/lib/eyatra/theme2/master/reporting-manager/form.html',
    controller: function($http, HelperService, $rootScope, $scope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;

        $scope.employeeDetails = []
        //SEARCH MANAGER
        $scope.searchManager = (key, type="from") => {
            if (type == 'from') {
                $scope.employeeDetails = []
                $('.employee_all_list').prop('checked', false)
                $scope.selectedEmployeeCount = 0
            }
            if (!key)
                return []

            return new Promise((resolve, reject) => {
                $http
                    .post( search_manager_url, { key } )
                    .then(res => {
                        // console.log(res.data)
                        resolve(res.data)
                    })
                //reject(res);
            });
        }
        $scope.reportingManagerChange = () => {
            // console.log($scope.from_reporting_manager?.id)
            $('.employee_all_list').prop('checked', false)
            $scope.selectedEmployeeCount = 0

            const from_manager_id = $scope.from_reporting_manager?.id || null
            if (!from_manager_id)
                return $scope.employeeDetails = []

            $http({
                url: laravel_routes['reportingEmployees'],
                method: 'GET',
                params: {id: from_manager_id}
            }).then(res => {
                const { data } = res
                // console.log(data)
                $scope.employeeDetails = data
            })
        }

        var selected_count = count = 0
        $scope.selectedEmployeeCount = 0

        //SELECT ALL
        $(document).on('click', '.employee_all_list', function(event) {
            $('.employee_list:checkbox').not(this).prop('checked', this.checked);
            var count = 0
            if ($(this).is(':checked')) {
                $.each($('.employee_list').prop('checked', this.checked), function() {
                    count = count + 1
                });
                $('.employee_list').attr('checked', true)
            } else {
                count = 0
                $('.employee_list').attr('checked', false)
            }
            $scope.selectedEmployeeCount = selected_count = count
            $scope.$apply()
        });

        //INDIVIDUAL SELECT
        $(document).on('click', '.employee_list:checkbox', function() {
            var total_count = $('.employee_list:checkbox').length
            var checked_count = $('.employee_list:checkbox:checked').length
            if ($(this).is(":checked")) {
                selected_count++
            } else {
                selected_count--
            }

            if (parseInt(total_count) == parseInt(checked_count))
                $('.employee_all_list').prop('checked', true)
            else
                $('.employee_all_list').prop('checked', false)

            $scope.selectedEmployeeCount = selected_count
            $scope.$apply()
        })

        var reportingManagerForm = '#reporting-manager-form';
        var v = jQuery(reportingManagerForm).validate({
            invalidHandler: function(event, validator) {
                custom_noty('error', 'Kindly check in each tab to fix errors')
            },
            errorPlacement: function(error, element) {
                error.insertAfter(element)
            },
            ignore: '',
            rules: {
                'from_reporting_manager_id': {
                    required: true,
                },
                'to_reporting_manager_id': {
                    required: true,
                },
            },
            submitHandler: function(form) {
                if ($scope.selectedEmployeeCount == 0)
                    return custom_noty('error', 'Kindly select employees')

                let formData = new FormData($(reportingManagerForm)[0]);
                $('#submit').button('loading');
                $.ajax({
                        url: laravel_routes['saveEYatraReportingEmployees'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(res => {
                        // console.log(res.success);
                        if (!res.success) {
                            $('#submit').button('reset')
                            var errors = ''
                            for (var i in res.errors) {
                                errors += '<li>' + res.errors[i] + '</li>'
                            }
                            custom_noty('error', errors)
                        } else {
                            $('#submit').button('reset')
                            custom_noty('success', res.message)
                            $scope.reportingManagerChange()
                            $scope.$apply()
                        }
                    })
                    .fail(xhr => {
                        $('#submit').button('reset');
                        custom_noty('error', 'Something went wrong at server')
                    })
            },
        })

        $rootScope.loading = false
    }
})