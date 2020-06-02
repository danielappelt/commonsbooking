document.addEventListener("DOMContentLoaded", function(event) {

    let $bookingForm = $('#booking-form');
    if($bookingForm.length) {
        // showTooltip-event is used here to hide date select
        Litepicker.prototype.showTooltip = function(element, text) {
            $('#booking-form').hide();
        };

        // Updates Time-selects so that no wrong time ranges can be selected
        const initSelectHandler = function() {
            let $bookingForm = $('#booking-form');
            const $startSelect = $bookingForm.find('.time-selection.start-date select');
            const $endSelect = $bookingForm.find('.time-selection.end-date select');

            $startSelect.change(function () {
                const startValue = $(this).val();
                $endSelect.find('option').each(function () {
                    if($(this).val() < startValue) {
                        $(this).attr('disabled', 'disabled');
                        $(this).prop("selected", false)
                    } else {
                        $(this).removeAttr('disabled');
                    }
                });
            });
        };

        // Updates select options by time slots array
        const updateSelectSlots = function($select, slots) {
            $select.empty().attr('required','required');
            $.each(slots, function(index, slot) {
                $select.append(
                    new Option(slot['timestart'] + ' - ' + slot['timeend'], slot['timestampstart'])
                );
            });
        };

        $.post(
            cb_ajax.ajax_url,
            {
                _ajax_nonce: cb_ajax.nonce,
                action: "calendar_data",
                item: $('#booking-form input[name=item-id]').val(),
                location: $('#booking-form input[name=location-id]').val()
            },
            function(data) {
                new Litepicker(
                    {
                        'minDate': data['startDate'],
                        'maxDate': data['endDate'],
                        "element": document.getElementById('litepicker'),
                        "inlineMode": true,
                        "firstDay": 1,
                        "lang": 'de-DE',
                        "numberOfMonths": 2,
                        "numberOfColumns": 2,
                        "singleMode": false,
                        "showWeekNumbers": false,
                        "autoApply": true,
                        "days": data['days'],
                        "lockDays": data['lockDays'],
                        "bookedDays": data['bookedDays'],
                        "bookedDaysInclusivity": "[]",
                        "highlightedDays": data['highlightedDays'],
                        "disallowBookedDaysInRange": true,
                        "disallowLockDaysInRange": true,
                        "mobileFriendly": true,
                        "selectForward": true,
                        "useResetBtn": true,
                        maxDays: 3,
                        'buttonText': {
                            apply: 'Buchen',
                            cancel: 'Abbrechen',
                        },
                        onSelect: function(date1, date2) {
                            let $bookingForm = $('#booking-form');
                            $bookingForm.show();
                            day1 = data['days'][moment(date1).format('YYYY-MM-DD')];
                            day2 = data['days'][moment(date2).format('YYYY-MM-DD')];

                            let $startSelect = $('#booking-form select[name=start-date]');
                            updateSelectSlots($startSelect, day1['slots']);

                            let $endSelect = $('#booking-form select[name=end-date]');
                            updateSelectSlots($endSelect, day2['slots']);

                            $('.time-selection.start-date span.date', $bookingForm).text(moment(date1).format('DD.MM.YYYY'));
                            $('.time-selection.end-date span.date', $bookingForm).text(moment(date2).format('DD.MM.YYYY'));

                            initSelectHandler();
                        }
                    }
                );
            }
        )
    }
});
