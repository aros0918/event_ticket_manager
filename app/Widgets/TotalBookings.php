<?php

namespace App\Widgets;

use Illuminate\Support\Str;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Widgets\BaseDimmer;

class TotalBookings extends BaseDimmer
{
    /**
     * The configuration array.
     *
     * @var array
     */
    protected $config = [];

    /**
     * Treat this method as a controller action.
     * Return view() or other content to display.
     */
    public function run()
    {
        $count = \App\Models\Booking::count();
        // $count  = Voyager::model('Page')->count();
        $string = trans_choice('Bookings', $count);

        return view('widgets.total_bookings', array_merge($this->config, [
            'icon' => 'voyager-bag',
            'title' => "{$count} {$string}",
            'text' => __('Total Bookings', ['count' => $count, 'string' => Str::lower($string)]),
            'button' => [
                'text' => __('view all bookings'),
                'link' => route('events_index'),
            ],
        ]));
    }

    /**
     * Determine if the widget should be displayed.
     *
     * @return bool
     */
    public function shouldBeDisplayed()
    {
        return true;
    }
}
