@extends('voyager::master')

@section('page_title', 'Attendees')

@section('page_header')
    <div class="container-fluid">
        <h1 class="page-title">
            <i class="{{ $dataType->icon }}"></i> Attendees
        </h1>
        @include('voyager::multilingual.language-selector')
    </div>
@stop

@section('content')
    <div class="page-content browse container-fluid">
        @include('voyager::alerts')
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered">
                    <div class="panel-body">
                        <!-- Summary section -->
                        <div id="summary-container" class="summary-section"></div>
                        <!-- Events container -->
                        <div id="events-container" class="events-section"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include jQuery and Font Awesome -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <script>
        $(document).ready(function () {
            var dataTypeContent = @json($dataTypeContent);

            // Group data by event_id
            var groupedData = dataTypeContent.data.reduce(function (acc, item) {
                if (!acc[item.event_id]) {
                    acc[item.event_id] = {
                        eventTitle: item.event_title,
                        bookings: []
                    };
                }
                acc[item.event_id].bookings.push(item);
                return acc;
            }, {});

            for (var eventId in groupedData) {
                groupedData[eventId].bookings.sort(function (a, b) {
                    return new Date(a.created_at) - new Date(b.created_at);
                });
            }

            // Calculate total sold and validated bookings
            var totalSold = 0;
            var totalValidated = 0;

            $.each(dataTypeContent.data, function (index, item) {
                totalSold += item.quantity_general + item.quantity_vip;
                if (item.status == 1) {
                    totalValidated += item.quantity_general + item.quantity_vip;
                }
            });

            // Populate summary
            var summaryHtml = '<h2>Total Sold: ' + totalSold + ' / Total Validated: ' + totalValidated + '</h2>';
            $('#summary-container').html(summaryHtml);

            // Populate events
            var eventsContainer = $('#events-container');
            $.each(groupedData, function (eventId, eventGroup) {
                var eventHtml = '<div class="event-group">';
                eventHtml += '<h3 class="event-header"><i class="fas fa-chevron-right toggle-icon"></i> ' + eventGroup.eventTitle + '</h3>';
                eventHtml += '<div class="dates-container hidden">';

                // Group by dates
                var dateGroups = eventGroup.bookings.reduce(function (acc, item) {
                    var date = item.created_at.split('T')[0];
                    if (!acc[date]) {
                        acc[date] = [];
                    }
                    acc[date].push(item);
                    return acc;
                }, {});

                // Populate dates
                $.each(dateGroups, function (date, dateGroup) {
                    eventHtml += '<h4 class="date-header"><i class="fas fa-chevron-right toggle-icon"></i> ' + date + '</h4>';
                    eventHtml += '<div class="times-container hidden">';

                    // Group by times
                    var timeGroups = dateGroup.reduce(function (acc, item) {
                        var time = item.event_start_time;
                        if (!acc[time]) {
                            acc[time] = [];
                        }
                        acc[time].push(item);
                        return acc;
                    }, {});
                    

                    // Populate times
                    $.each(timeGroups, function (time, timeGroup) {
                        var validatedCount = timeGroup.filter(function (item) {
                            return item.status == 1;
                        }).length;
                        var bookingCount = timeGroup.length;
                        eventHtml += '<p><b>' + time + ' - Bookings: ' + bookingCount + ', Validated: ' + validatedCount + '</b></p>';
                    });

                    eventHtml += '</div>'; // Close times-container
                });

                eventHtml += '</div>'; // Close dates-container
                eventHtml += '</div>'; // Close event-group
                eventsContainer.append(eventHtml);
            });

            // Add click event for interactivity
            $('.event-header').click(function () {
                var icon = $(this).find('.toggle-icon');
                icon.toggleClass('fa-chevron-right fa-chevron-down');
                $(this).next('.dates-container').toggleClass('hidden');
            });

            $('.date-header').click(function () {
                var icon = $(this).find('.toggle-icon');
                icon.toggleClass('fa-chevron-right fa-chevron-down');
                $(this).next('.times-container').toggleClass('hidden');
            });
        });
    </script>

    <style>
        .hidden { display: none; }
        .event-header, .date-header { cursor: pointer; padding: 10px; background-color: #f9f9f9; border-bottom: 1px solid #ddd; }
        .event-header:hover, .date-header:hover { background-color: #f1f1f1; }
        .summary-section { padding: 10px; background-color: #f9f9f9; margin-bottom: 20px; border: 1px solid #ddd; }
        .events-section { border: 1px solid #ddd; }
        .toggle-icon { margin-right: 10px; }
        .times-container { padding-left: 20px; }
    </style>
@stop
