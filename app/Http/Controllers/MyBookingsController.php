<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Carbon\CarbonPeriod;

use Auth;

use App\Models\Event;
use App\Models\Category;
use App\Models\Country;
use App\Models\Booking;
use App\Models\User;
use App\Notifications\MailNotification;

class MybookingsController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // language change
        $this->middleware('common');

        $this->middleware(['customer']);

        $this->event = new Event;
        $this->booking = new Booking;
    }

    /**
     * Customer bookings
     *
     * @return array
     */
    public function index($view = 'bookings.customer_bookings', $extra = [])
    {
        $path = false;
        if (!empty(config('custom.route.prefix')))
            $path = config('custom.route.prefix');

        // if have booking email data then send booking notification
        $is_success = 1;
        return view($view, compact('path', 'is_success', 'extra'));
    }

    /**
     * Get customer bookings
     *  */
    public function mybookings()
    {
        $params = [
            'customer_id' => Auth::id(),
        ];

        $bookings = $this->booking->get_my_bookings($params);

        // check expired booking
        // event end_date <= today_date
        foreach ($bookings as $key => $val)
            $val->expired = $val->event_end_date <= Carbon::now()->format('Y-m-d') ? 1 : 0;

        return response([
            'bookings' => $bookings->jsonSerialize(),
        ], Response::HTTP_OK);

    }


}
