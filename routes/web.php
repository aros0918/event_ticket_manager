<?php

use Composer\DependencyResolver\Request;
use App\Middleware\Authenticate;


use Illuminate\Support\Facades\Schema;
use App\Http\Controllers\EmailPreviewController;
/*
|
| Package Routes
|
*/

$namespace = '\App\Http\Controllers';

Route::post('/dashboard/myevents/api/stripe/webhook', "\App\Http\Controllers\MyEventsController@handle_web_hook")->name('handle_web_hook');
Route::get('/emails/order', [EmailPreviewController::class, 'showOrderEmail']);
/* Localization */
Route::get('/assets/js/lang', function () {
    // default lang
    $lang = config('app.locale');

    // user lang
    if (session('my_lang')) {
        $lang = session('my_lang');
        \App::setLocale(session('my_lang'));
    }

    $strings['em'] = \Lang::get('em');

    header('Content-Type: text/javascript; charset=UTF-8');
    echo ('window.i18n = ' . json_encode($strings) . ';');

    exit ();
})->name('lang');

/* set local timezone */
Route::post('/set/local_timezone', function (\Illuminate\Http\Request $request) {

    if (Schema::hasTable('settings')) {
        session(['local_timezone' => $request->local_timezone]);
    }

    return response()->json(['success' => 'success'], 200);

})->name('timezone');


// Lang selector
Route::get('/lang/{lang?}', $namespace . '\CustomController@change_lang')->name('change_lang');

// Package Asset
Route::get('frontend-assets', $namespace . '\CustomController@assets')->name('assets');


/* Auth */
Auth::routes();

// Login
Route::get('login', $namespace . '\Auth\LoginController@showLoginForm')->name('show_login');
Route::post('login', $namespace . '\Auth\LoginController@login')->name('login');

// Logout
Route::match(['get', 'post'], '/logout', $namespace . '\CustomController@logout')->name('logout');

// Registration
Route::get('register', $namespace . '\Auth\RegisterController@showRegistrationForm')->name('show_register');
Route::post('register', $namespace . '\Auth\RegisterController@register')->name('register');

// Forgot password
Route::get('password/reset', $namespace . '\Auth\ForgotPasswordController@showLinkRequestForm')->name('password_request');
Route::post('password/email', $namespace . '\Auth\ForgotPasswordController@sendResetLinkEmail')->name('password_email');
Route::get('forgot/password/reset/{token}', $namespace . '\Auth\ResetPasswordController@showResetForm')->name('password_reset_show');
Route::post('forgot/password/reset/post', $namespace . '\Auth\ResetPasswordController@reset')->name('password_reset');

// Email Verify
Route::get('email/verify', $namespace . '\Auth\VerificationController@show')->name('verification_notice');
Route::middleware([Authenticate::class])->get('email/verify/{id}', $namespace . '\Auth\VerificationController@verify')->name('verification_verify');
Route::get('email/resend', $namespace . '\Auth\VerificationController@resend')->name('verification_resend');


Route::group([
    'prefix' => config('custom.route.prefix'),
], function () use ($namespace) {

    /* Welcome */
    Route::get('/', $namespace . "\WelcomeController@index")->name('welcome');

    Route::get('/home', function () {
        return redirect()->route('welcome');
    });


    /* Events */
    Route::prefix('/events')->group(function () use ($namespace) {
        $controller = $namespace . '\EventsController';

        Route::get('/', "$controller@index")->name('events_index');

        // Wildcard
        Route::get('/{event}', "$controller@show")->name('events_show');

        // API
        Route::get('/api/get_events', "$controller@events")->name('events');
        Route::get('/api/categories', "$controller@categories")->name('myevents_categories');
    });

    /* Bookings */
    Route::prefix('/bookings')->group(function () use ($namespace) {
        $controller = $namespace . '\BookingsController';

        // Redirect back to event
        Route::get('/login-first', "$controller@login_first")->name('login_first');
        Route::get('/signup-first', "$controller@signup_first")->name('signup_first');

        // API
        Route::post('/api/booking_customers', "$controller@get_customers")->name('get_customers');
        Route::post('/api/book_tickets', "$controller@book_tickets")->name('bookings_book_tickets');
    });

    /* My Bookings (customers) */
    Route::prefix('/mybookings')->group(function () use ($namespace) {
        $controller = $namespace . '\MyBookingsController';

        Route::get('/', "$controller@index")->name('mybookings_index');

        // API
        Route::get('/api/get_mybookings', "$controller@mybookings")->name('mybookings');
    });


    /* Events  */
    Route::prefix('/dashboard/myevents')->group(function () use ($namespace) {
        $controller = $namespace . '\MyEventsController';

        Route::get('/', "$controller@index")->name('myevents_index');
        Route::get('/manage/{slug?}', "$controller@form")->name('myevents_form');
        Route::get('/delete/{slug}', "$controller@delete_event")->name('delete_event');

        // API
        Route::get('/api/get_myevents', "$controller@get_myevents")->name('myevents');
        Route::get('/api/get_all_myevents', "$controller@get_all_myevents")->name('all_myevents');
        Route::post('/api/store', "$controller@store")->name('myevents_store');
        Route::post('/api/store_media', "$controller@store_media")->name('myevents_store_media');
        Route::post('/api/store_location', "$controller@store_location")->name('myevents_store_location');
        Route::post('/api/store_timing', "$controller@store_timing")->name('myevents_store_timing');
        Route::post('/api/store_seo', "$controller@store_seo")->name('myevents_store_seo');
        Route::get('/api/countries', "$controller@countries")->name('myevents_countries');
        Route::post('/api/get_myevent', "$controller@get_user_event")->name('get_myevent');
        Route::post('/api/publish_myevent', "$controller@event_publish")->name('publish_myevent');
        Route::post('/api/create_payment_intent', "$controller@create_payment_intent")->name('create_payment_intent');
        
        

        //delete multiple images
        Route::post('delete/image', "$controller@delete_image")->name('delete_image');
    });

    /* Notification */
    Route::prefix('/notifications')->group(function () use ($namespace) {

        // read notification
        Route::get('/read/{n_type}', function ($n_type) {
            if ($n_type) {
                $id = \Auth::id();
                $user = \App\Models\User::find($id);
                $user->unreadNotifications->where('n_type', $n_type)->markAsRead();
            }

            // Admin: redirect to admin-panel
            if (\Auth::user()->hasRole('admin')) {
                if ($n_type == "user")
                    return redirect()->route('voyager.users.index');
                else
                    return redirect()->route('voyager.dashboard');
            }

            // customer redirect to notification's related page
            if (\Auth::user()->hasRole('customer')) {
                // create events notification
                if ($n_type == "user")
                    return redirect()->route('profile');

                // create booking notification
                if ($n_type == "bookings")
                    return redirect()->route('mybookings_index');
            }

            // Default: redirect to homepage
            return redirect()->route('welcome');
        })->name('notify_read');

    });

    /* Profile */
    Route::prefix('/profile')->group(function () use ($namespace) {
        $controller = $namespace . '\ProfileController';

        Route::get('/', "$controller@index")->name('profile');
        Route::post('/updateAuthUser', "$controller@updateAuthUser")->name('updateAuthUser');
        Route::post('/updatePasswordUser', "$controller@updateSecurity")->name('updatePasswordUser');
    });

    /* Contact */
    Route::prefix('/contact')->group(function () use ($namespace) {
        $controller = $namespace . '\ContactController';

        Route::get('/', "$controller@index")->name('contact');
        Route::post('/save', "$controller@store_contact")->name('store_contact');
    });

    /* Static Pages */
    Route::get('pages/{page}', $namespace . "\PagesController@view")->name('page');

});

// Voyager Routes -----------------------------------------------------------------
Route::group([
    'namespace' => $namespace . '\Voyager',
    'prefix' => config('custom.route.prefix') . '/' . config('custom.route.admin_prefix'),
], function () {
    \Voyager::routes();
});
// --------------------------------------------------------------------------