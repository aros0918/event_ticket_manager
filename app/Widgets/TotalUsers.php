<?php

namespace App\Widgets;

use Illuminate\Support\Str;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Widgets\BaseDimmer;

class TotalUsers extends BaseDimmer
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
        $count = \App\Models\User::count();
        // $count  = Voyager::model('Page')->count();
        $string = trans_choice('Users', $count);

        return view('widgets.total_users', array_merge($this->config, [
            'icon' => 'voyager-people',
            'title' => "{$count} {$string}",
            'text' => __('Total Users', ['count' => $count, 'string' => Str::lower($string)]),
            'button' => [
                'text' => __('view all users'),
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
