<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Carbon;
use Carbon\CarbonPeriod;
use Auth;
use Redirect;
use File;
use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use App\Models\Category;
use App\Models\Country;
use App\Notifications\MailNotification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;
use DB;
use Laravel\Cashier\Cashier;
use Stripe\StripeClient;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use ErrorException;
use Illuminate\Support\Facades\Mail;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

class MyEventsController extends Controller
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
        // $this->middleware('auth')->except('get_customers');

        // exclude routes
        $this->event = new Event;
        $this->category = new Category;
        $this->country = new Country;
        $this->booking = new Booking;
        $this->user = new User;
        $this->customer_id = null;
    }

    /**
     *  Manage events list
     */
    public function index()
    {
        return redirect()->route('voyager.events.index');
    }

    /**
     * Create-edit event
     *
     * @return array
     */
    public function form($slug = null, $view = 'events.form', $extra = [])
    {

        $event = [];

        // get event by event_slug
        if ($slug) {
            $event = $this->event->get_event($slug);
        }

        return view($view, compact('event', 'extra'));
    }

    // create event
    public function store(Request $request)
    {
        // 1. validate data
        $request->validate([
            'title' => 'required|max:256',
            'slug' => 'required|max:512',
            'category_id' => 'required|numeric|min:1|regex:^[1-9][0-9]*$^',
            'description' => 'required',
            'faq' => 'nullable',
        ], [
            'category_id.*' => __('em.category') . ' ' . __('em.required')
        ]);


        $result = (object) [];
        $result->title = null;
        $result->slug = null;

        // in case of edit
        if (!empty($request->event_id)) {
            $request->validate([
                'event_id' => 'required|numeric|min:1|regex:^[1-9][0-9]*$^',
            ]);

            // check this event id have login user relationship
            $result = (object) $this->event->get_user_event($request->event_id);

            if (empty($result))
                return error('access denied', Response::HTTP_BAD_REQUEST);

        }

        // title is not equal to before title then apply unique column rule    
        if ($result->title != $request->title) {
            $request->validate([
                'title' => 'unique:events,title',
            ]);
        }

        // slug is not equal to before slug then apply unique column rule    
        if ($result->slug != $request->slug) {
            $request->validate([
                'slug' => 'unique:events,slug',
            ]);
        }

        $params = [
            "title" => $request->title,
            "slug" => $this->slugify($request->slug),
            "description" => $request->description,
            "faq" => $request->faq,
            "category_id" => $request->category_id,
        ];


        $event = $this->event->save_event($params, $request->event_id);

        if (empty($event))
            return error(__('em.event_not_created'), Response::HTTP_BAD_REQUEST);

        // ====================== Notification ====================== 
        //send notification after bookings
        $msg[] = __('em.event') . ' - ' . $event->title;
        $extra_lines = $msg;

        $mail['mail_subject'] = __('em.event_created');
        $mail['mail_message'] = __('em.event_info');
        $mail['action_title'] = __('em.manage_events');
        $mail['action_url'] = route('myevents_index');
        $mail['n_type'] = "events";

        $notification_ids = [1];

        $users = User::whereIn('id', $notification_ids)->get();
        try {
            \Notification::locale(\App::getLocale())->send($users, new MailNotification($mail, $extra_lines));
        } catch (\Throwable $th) {
        }
        // ====================== Notification ======================     


        // in case of create
        if (empty($request->event_id)) {
            // set step complete
            $this->complete_step($event->is_publishable, 'detail', $event->id);
            return response()->json(['status' => true, 'id' => $event->id, 'slug' => $event->slug]);
        }
        // update event in case of edit
        $event = $this->event->get_user_event($request->event_id);
        return response()->json(['status' => true, 'slug' => $event->slug]);
    }

    // crate media of event
    public function store_media(Request $request)
    {
        $images = [];
        $poster = null;
        $thumbnail = null;

        // 1. validate data
        $request->validate([
            'event_id' => 'required|numeric|min:1|regex:^[1-9][0-9]*$^',
            'thumbnail' => 'required',
            'poster' => 'required',
        ]);

        $result = [];
        // check this event id have login user or not
        $result = $this->event->get_user_event($request->event_id);

        if (empty($result)) {
            return error('access denied', Response::HTTP_BAD_REQUEST);
        }

        // for multiple image
        $path = 'events/' . Carbon::now()->format('FY') . '/';

        // for thumbnail
        if (!empty($_REQUEST['thumbnail'])) {
            $params = [
                'image' => $_REQUEST['thumbnail'],
                'path' => 'events',
                'width' => 854,
                'height' => 480,
            ];
            $thumbnail = $this->upload_base64_image($params);
        }

        if (!empty($_REQUEST['poster'])) {
            $params = [
                'image' => $_REQUEST['poster'],
                'path' => 'events',
                'width' => 1280,
                'height' => 720,
            ];

            $poster = $this->upload_base64_image($params);
        }

        // for image
        if ($request->hasfile('images')) {
            // if have  image and database have images no images this event then apply this rule 
            $request->validate([
                'images' => 'required',
                'images.*' => 'mimes:jpeg,png,jpg,gif,svg',
            ]);

            $files = $request->file('images');

            foreach ($files as $key => $file) {
                $extension = $file->getClientOriginalExtension(); // getting image extension
                $image[$key] = time() . rand(1, 988) . '.' . 'webp';

                $image_resize = Image::make($file)->encode('webp', 90)->resize(854, null, function ($constraint) {
                    $constraint->aspectRatio();
                });

                // if directory not exist then create directiory
                if (!File::exists(storage_path('/app/public/') . $path)) {
                    File::makeDirectory(storage_path('/app/public/') . $path, 0775, true);
                }

                $image_resize->save(storage_path('/app/public/' . $path . $image[$key]));
                $images[$key] = $path . $image[$key];
            }
        }

        $params = [
            "thumbnail" => !empty($thumbnail) ? $path . $thumbnail : null,
            "poster" => !empty($poster) ? $path . $poster : null,
        ];

        // if images uploaded
        if (!empty($images)) {
            if (!empty($result->images)) {
                $exiting_images = json_decode($result->images, true);

                $images = array_merge($images, $exiting_images);
            }

            $params["images"] = json_encode(array_values($images));

        }

        $status = $this->event->save_event($params, $request->event_id);

        if (empty($status)) {
            return error('Database failure!', Response::HTTP_BAD_REQUEST);
        }

        // get media  related event_id who have created now
        $images = $this->event->get_user_event($request->event_id);

        // set step complete
        $this->complete_step($images->is_publishable, 'media', $request->event_id);

        return response()->json(['images' => $images, 'status' => true]);
    }

    /** 
     * Store Location
     */
    public function store_location(Request $request)
    {
        // 1. validate data
        $request->validate([
            // 'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'venue' => 'required|max:256',
            'country_id' => 'required|numeric|min:1|regex:^[1-9][0-9]*$^',
            'address' => 'required|max:512',
            'city' => 'required|max:256',
            'state' => 'required|max:256',
            'zipcode' => 'required|max:64',
            'event_id' => 'required|numeric|min:1|regex:^[1-9][0-9]*$^',
        ]);

        $params = [
            "country_id" => $request->country_id,
            "venue" => $request->venue,
            "address" => $request->address,
            "city" => $request->city,
            "zipcode" => $request->zipcode,
            "state" => $request->state,
        ];

        // check this event id have login user or not
        $event = $this->event->get_user_event($request->event_id);
        if (empty($event))
            return error('access denied', Response::HTTP_BAD_REQUEST);

        $location = $this->event->save_event($params, $request->event_id);
        if (empty($location)) {
            return error('Database failure!', Response::HTTP_BAD_REQUEST);
        }

        // get update event
        $event = $this->event->get_user_event($request->event_id);

        // set step complete
        $this->complete_step($event->is_publishable, 'location', $request->event_id);

        return response()->json(['status' => true, 'event' => $event]);
    }
    /**
     * StripeWebHook
     */
    public function handle_web_hook(Request $request)
    {

        // $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET_KEY'));
        // $endpoint_secret = env('STRIPE_WEBHOOK_KEY');
        $stripe = new \Stripe\StripeClient(getenv('STRIPE_SECRET_KEY'));
        $endpoint_secret = getenv('STRIPE_WEBHOOK_KEY');

        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $webhookevent = null;

        try {
            $webhookevent = \Stripe\Webhook::constructEvent(
                $payload,
                $sig_header,
                $endpoint_secret
            );
        } catch (\UnexpectedValueException $e) {
            http_response_code(400);
            exit();
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            http_response_code(400);
            exit();
        }

        // Handle the event
        switch ($webhookevent->type) {
            case 'checkout.session.completed':
                $paymentIntent = $webhookevent->data->object;
                Log::info('Request Success Hook data', $request->all());
                // Log::info('Request Success Hook data', ['success_url' => $request->data['object']['success_url']]);
                $queryParams = parse_url($request->data['object']['success_url'], PHP_URL_QUERY);

                // Parse the query string into an associative array
                parse_str($queryParams, $params);

                // Create a new request instance with the extracted parameters
                $pay_request = new Request([
                    'event_id' => $params['event_id'] ?? null,
                    'booking_date' => $params['booking_time'] ?? null,
                    'customer_id' => $params['customer_id'] ?? null,
                    'quantity_general' => $params['quantity_general'] ?? 0,
                    'quantity_vip' => $params['quantity_vip'] ?? 0,
                    'price' => $params['price'] ?? 0,
                ]);
                $quantity_vip = $params['quantity_vip'] ?? null;
                $quantity_general = $params['quantity_general'] ?? null;
                $booking_time = $params['booking_time'] ?? null;


                $order_number = $this->book_tickets($pay_request);
                $startPos = strpos($order_number, '{');
                $jsonString = substr($order_number, $startPos);
                $data = json_decode($jsonString, true);
                if ($data && isset($data['order_number'], $data['customer_email'])) {
                    $orderNumber = $data['order_number'];
                    $customerEmail = $data['customer_email'];
                }

                $email = new \SendGrid\Mail\Mail();
                $email->setFrom("contacto@immmu.mx", "IMMMU");
                $email->setSubject("Confirmación de compra en Fever: IMMMU");
                $imageKit = new \ImageKit\ImageKit(
                    "public_SzSRyG4JH2wW/r4akI/x/NSjMbM=",
                    "private_H8tI/uvVQ+UjA1tIi2Ld+NqceL0=",
                    "https://ik.imagekit.io/g5kjmqmav"

                );

                $qrCodeData = base64_encode(QrCode::format('png')->size(150)->generate($orderNumber));
                $uploadFile = $imageKit->uploadFile([
                    'file' => $qrCodeData,
                    'fileName' => 'qr-code'
                ]);

                if (isset($uploadFile->result->url)) {
                    $uploadedImageUrl = $uploadFile->result->url;
                    $qrCodes = [
                        'qr_code' => $uploadedImageUrl,
                        'order_number' => $orderNumber,
                        'customer_email' => $customerEmail,
                        'quantity_vip' => $quantity_vip,
                        'quantity_general'=> $quantity_general,
                        'booking_time'=> $booking_time,
                    ];
                } else {
                    $qrCodes = [
                        'qr_code' => '',
                        'order_number' => $orderNumber,
                        'customer_email' => $customerEmail,
                        'quantity_vip' => $quantity_vip,
                        'quantity_general'=> $quantity_general,
                        'booking_time'=> $booking_time,
                    ];
                }
                $quantity_vip = $params['quantity_vip'] ?? null;
                $quantity_general = $params['quantity_general'] ?? null;
                $booking_time = $params['booking_time'] ?? null;
                $htmlContent = view('emails.order', $qrCodes)->render();

                $email->addTo($customerEmail, "customer");

                $email->addContent("text/html", $htmlContent);
                $sendgrid = new \SendGrid(getenv('SENDGRID_API_KEY'));
                try {
                    $response = $sendgrid->send($email);
                    Log::info('Email sending now');
                    $responseArray = [
                        'statusCode' => $response->statusCode(),
                        'body' => $response->body(),
                        'headers' => $response->headers()
                    ];

                    Log::info("email send", $responseArray);
                    // Log::info("email send", $response->body());

                } catch (Exception $e) {
                    Log::error('Caught exception: ' . $e->getMessage());
                }

                break;

            // case 'checkout.session.async_payment_success':
            //     $paymentIntent = $webhookevent->data->object;
            //     break;

            // case 'checkout.session.async_payment_failed':
            //     $paymentIntent = $webhookevent->data->object;
            //     break;

            // case 'checkout.session.expired':
            //     $paymentIntent = $webhookevent->data->object;
            //     break;

            default:
                echo 'Received unknown event type ' . $webhookevent->type;
                break;

        }

        http_response_code(200);
    }
    public function book_tickets(Request $request)
    {
        Log::info('Request booking data:', $request->all());

        // 1. If admin is making booking then it will be for a customer

        // 2. General validation
        $data = $this->general_validation($request);
        // $this->customer_id = $request->customer_id;
        Log::info('Received webhook event', ['customer_id' => $request->customer_id]);

        if (!$data['status'])
            return error($data['error'], Response::HTTP_BAD_REQUEST);

        // 3. Timing & Date check 
        $pre_time_booking = $this->time_validation($data);

        if (!$pre_time_booking['status'])
            Log::info('pre_time_booking status data:', $pre_time_booking);


        // 4. Check if it's a valid customer
        $params = ['customer_id' => $request->customer_id,];
        $customer = $this->user->get_customer($params);
        if (empty($customer))
            Log::info('pre_time_booking customer_id :');


        // 5. Create booking
        $booking = [];
        $booking['customer_id'] = $request->customer_id;
        $booking['customer_name'] = $customer['name'];
        $booking['customer_email'] = $customer['email'];
        $booking['event_id'] = $data['event']['id'];
        $booking['quantity_general'] = $data['quantity_general'];
        $booking['quantity_vip'] = $data['quantity_vip'];

        $booking['status'] = 1;
        $booking['created_at'] = Carbon::now();
        $booking['updated_at'] = Carbon::now();
        $booking['event_title'] = $data['event']['title'];
        $booking['event_category'] = $data['event']['category_name'];
        $booking['event_start_date'] = $data['event']['start_date'];
        $booking['event_end_date'] = $data['event']['end_date'];
        $booking['event_start_time'] = $data['event']['start_time'];
        $booking['event_end_time'] = $data['event']['end_time'];
        $booking['price'] = $data['price'];
        $booking['net_price'] = 0;
        $booking['order_number'] = time() . rand(1, 988);

        // Free events only
        $flag = $this->booking->make_booking($booking);

        // in case of database failure
        if (empty($flag))
            return error('Database failure!', Response::HTTP_REQUEST_TIMEOUT);

        // 6. Send notifications
        $this->finish_booking($booking);

        // redirect no matter what so that it never turns backreturn response

        return response()->json([
            'order_number' => $booking['order_number'],
            'customer_email' => $booking['customer_email'],
        ]);
    }


    private function general_validation(Request $request)
    {
        $request->validate([
            'event_id' => 'required|numeric|min:1|regex:^[1-9][0-9]*$^',
            'price' => 'required|numeric|min:1|regex:^[1-9][0-9]*$^',
            'quantity_general' => 'required|numeric|min:1|regex:^[1-9][0-9]*$^',
            'quantity_vip' => 'required|numeric|min:1|regex:^[1-9][0-9]*$^',
        ]);

        // check if selected event exists
        $event = $this->event->get_event(null, $request->event_id);
        if (empty($event))
            return ['status' => false, 'error' => __('em.event') . ' ' . __('em.not_found')];

        return [
            'status' => true,
            'event_id' => $request->event_id,
            'price' => $request->price,
            'quantity_general' => $request->quantity_general,
            'quantity_vip' => $request->quantity_vip,
            'event' => $event,
            'booking_date' => $event['start_date'],
            'start_time' => $event['start_time'],
            'end_time' => $event['end_time'],
        ];

    }

    // pre booking time validation
    private function time_validation($params = [])
    {
        $booking_date = $params['booking_date'];
        $start_time = $params['start_time'];
        $start_time = $params['end_time'];

        // booking date is event start date and it is less then today date then user can't book tickets
        $start_date = Carbon::parse($booking_date . '' . $start_time);
        $today_date = Carbon::parse(Carbon::now());

        // 1.Booking date should not be less than today's date
        if ($start_date < $today_date)
            return ['status' => false, 'error' => __('em.event') . ' ' . __('em.ended')];

        return ['status' => true];
    }

    // Finish booking
    private function finish_booking($booking = [])
    {
        // ====================== Notification ====================== 
        //send notification after bookings
        $mail['mail_message'] = "Email message body";
        $mail['greeting'] = "Greetings";
        $mail['mail_subject'] = "Booking Successfully";
        $mail['line'] = "Thank you for using our application!";
        $mail['n_type'] = "bookings";

        $notification_ids = [1, $booking['customer_id']];

        $users = User::whereIn('id', $notification_ids)->get();
        \Notification::send($users, new MailNotification($mail));
        // ====================== Notification ====================== 

        return true;
    }


    /**
     * Payment
     */
    public function create_payment_intent(Request $request)
    {
        // Stripe::setApiKey('sk_test_4eC39HqLyjWDarjtT1zdp7dc');
        // Stripe::setApiKey('sk_test_51PEPW8ITkovMOcdYwPtOxu7X0NXTsE7Vmr4K6eB6OTuBQeEgllJShgM3XnKb1e6yItJBGaZe4ORiLrLaPhlMfhgI00Q9L9Duhj');
        Stripe::setApiKey(getenv('STRIPE_SECRET_KEY'));


        try {

            $checkout_session = Session::create([
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => 'MXN',
                            'product_data' => [
                                'name' => 'Tickets',
                            ],
                            'unit_amount' => $request->price * 100,
                        ],
                        'quantity' => 1,
                    ]
                ],
                'mode' => 'payment',
                'success_url' => $request->success_url
            ]);

            return response()->json(['url' => $checkout_session->url]);

        } catch (ErrorException $e) {
            return response()->json(['error' => $e->getMessage()]);
        }

    }
    /** 
     * Store Timings
     */
    public function store_timing(Request $request)
    {
        Log::info('Request data:', $request->all());

        $request->validate([
            'event_id' => 'required',
        ]);

        $event = $this->event->get_user_event($request->event_id);
        if (empty($event)) {
            return error('access denied!', Response::HTTP_BAD_REQUEST);
        }

        $single_event = $this->prepare_single_event($request);
        if (!$single_event['status'])
            return error($single_event['error'], Response::HTTP_BAD_REQUEST);

        $data = $single_event['data'];


        $event_timing = $this->event->save_event($data, $request->event_id);

        if (empty($event_timing))
            return error('Database failure!', Response::HTTP_BAD_REQUEST);

        // get updated event
        $event = $this->event->get_user_event($request->event_id);

        // set step complete
        $this->complete_step($event->is_publishable, 'timing', $request->event_id);

        return response()->json(['status' => true]);
    }

    /** 
     * Store SEO
     */
    public function store_seo(Request $request)
    {
        // 1. validate data
        $request->validate([
            'meta_title' => 'max:256',
            'meta_keywords' => 'max:256',
            'meta_description' => 'max:512',
        ]);

        $params = [
            "meta_title" => $request->meta_title,
            "meta_keywords" => $request->meta_keywords,
            "meta_description" => $request->meta_description,

        ];


        // check this event id have login user or not
        $event = $this->event->get_user_event($request->event_id);
        if (empty($event))
            return error('access denied', Response::HTTP_BAD_REQUEST);

        $seo = $this->event->save_event($params, $request->event_id);

        if (empty($seo)) {
            return error('Database failure!', Response::HTTP_BAD_REQUEST);
        }

        // get updated event
        $event = $this->event->get_user_event($request->event_id);

        return response()->json(['status' => true, 'event' => $event]);
    }

    /** 
     * Publish Event
     * after completing all steps
     */
    public function event_publish(Request $request)
    {
        $request->validate([
            'event_id' => 'required|numeric|min:1|regex:^[1-9][0-9]*$^',
        ]);

        // check event is valid or not
        $event = $this->event->get_user_event($request->event_id);

        if (empty($event)) {
            return error('access denied!', Response::HTTP_BAD_REQUEST);
        }

        // check all step is completed or not 
        $is_publishable = json_decode($event->is_publishable, true);

        if (count($is_publishable) != 4)
            return error(__('em.please_complete_steps'), Response::HTTP_BAD_REQUEST);

        // do not unpublish in demo mode
        if (config('voyager.demo_mode')) {
            if ($event->publish)
                return error('Demo mode', Response::HTTP_BAD_REQUEST);
        }

        $params = [
            'publish' => $event->publish == 1 ? 0 : 1,
        ];

        $publish_event = $this->event->save_event($params, $request->event_id);

        if (empty($publish_event)) {
            return error('Database failure!', Response::HTTP_BAD_REQUEST);
        }

        return response()->json(['status' => true]);

    }

    // get event
    public function get_user_event(Request $request)
    {
        $request->validate([
            'event_id' => 'required',

        ]);

        // check event is valid or not
        $event = $this->event->get_user_event($request->event_id);

        if (empty($event)) {
            return error('access denied!', Response::HTTP_BAD_REQUEST);
        }

        return response()->json(['status' => true, 'event' => $event]);

    }


    /** 
     * Get countries list
     */
    public function countries()
    {
        $countries = $this->country->get_countries();

        if (empty($countries)) {
            return response()->json(['status' => false]);
        }
        return response()->json(['status' => true, 'countries' => $countries]);

    }


    /**
     *   only admin can delete event
     */
    public function delete_event($slug = null)
    {
        if (config('voyager.demo_mode')) {
            return redirect()
                ->route("voyager.events.index")
                ->with([
                    'message' => 'Demo mode',
                    'alert-type' => 'info',
                ]);
        }

        // only admin can delete event

        if (Auth::check() && !Auth::user()->hasRole('admin')) {
            return redirect()->route('events');
        }

        // get event by event_slug
        if (empty($slug))
            return error('Event Not Found!', Response::HTTP_BAD_REQUEST);


        $event = $this->event->get_event($slug);

        if (empty($event))
            return error('Event Not Found!', Response::HTTP_BAD_REQUEST);

        $params = [
            'event_id' => $event->id,
        ];

        $delete_event = $this->event->delete_event($params);

        if (empty($delete_event)) {
            return error('Event Could Not Deleted!', Response::HTTP_BAD_REQUEST);
        }

        $msg = __('em.event_deleted');

        return redirect()
            ->route("voyager.events.index")
            ->with([
                'message' => $msg,
                'alert-type' => 'success',
            ]);

    }


    /* ==================== Private fucntions ==================== */

    /**
     *  Upload base64 image 
     */
    protected function upload_base64_image($params = [])
    {
        if (!empty($params['image'])) {
            $image = base64_encode(file_get_contents($params['image']));
            $image = str_replace('data:image/png;base64,', '', $image);
            $image = str_replace(' ', '+', $image);

            if (class_exists('\Str'))
                $filename = time() . \Str::random(10) . '.' . 'webp';
            else
                $filename = time() . str_random(10) . '.' . 'webp';

            $path = '/storage/' . $params['path'] . '/' . Carbon::now()->format('FY') . '/';
            $image_resize = Image::make(base64_decode($image))->encode('webp', 90)->resize($params['width'], $params['height']);

            // first check if directory exists or not
            if (!File::exists(public_path() . $path)) {
                File::makeDirectory(public_path() . $path, 0775, true);
            }

            $image_resize->save(public_path($path . $filename));

            return $filename;
        }
    }

    /**
     *  prepare_single_event
     */

    protected function prepare_single_event(Request $request)
    {
        $event = Event::where(['id' => $request->event_id])->first();

        // start validation will not apply if database start_date and request start is same
        if ($event->start_date != $request->start_date) {
            $request->validate([
                'start_date' => 'required|date|after_or_equal:today',
            ]);

        }

        // 1. validate data
        $request->validate([
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'required|date_format:H:i:s',
            'end_time' => 'required|date_format:H:i:s',
            'interval_time' => 'required|numeric',
            'disable_date' => 'required|string',
        ]);

        $data = [
            "start_date" => serverTimezone($request->start_date . ' ' . $request->start_time, 'Y-m-d H:i:s', 'Y-m-d'),
            "start_time" => serverTimezone($request->start_date . ' ' . $request->start_time, 'Y-m-d H:i:s', 'H:i:s'),
            "end_date" => serverTimezone($request->end_date . ' ' . $request->end_time, 'Y-m-d H:i:s', 'Y-m-d'),
            "end_time" => serverTimezone($request->end_date . ' ' . $request->end_time, 'Y-m-d H:i:s', 'H:i:s'),
            "interval_time" => $request->interval_time,
            "disable_date" => $request->disable_date,
        ];

        return [
            'status' => true,
            'data' => $data
        ];
    }

    // complete specific step
    protected function complete_step($is_publishable = [], $type = 'detail', $event_id = null)
    {
        if (!empty($is_publishable))
            $is_publishable = json_decode($is_publishable, true);

        $is_publishable[$type] = 1;

        // save is_publishable
        $params = ['is_publishable' => json_encode($is_publishable)];
        $status = $this->event->save_event($params, $event_id);

        return true;
    }

    /**
     *  delete image
     */

    public function delete_image(Request $request)
    {
        // 1. validate data
        $request->validate([
            'event_id' => 'required|numeric|min:1|regex:^[1-9][0-9]*$^',
            'image' => 'required|string',

        ]);

        $event = $this->event->get_user_event($request->event_id);

        if (empty($event)) {
            return error('access denied', Response::HTTP_BAD_REQUEST);
        }

        $images = json_decode($event->images);

        $filtered_images = [];
        foreach ($images as $key => $val) {
            if ($val != $request->image)
                $filtered_images[$key] = $val;
        }

        $params = [
            'images' => !empty($filtered_images) ? json_encode(array_values($filtered_images)) : null,
        ];

        $event = $this->event->save_event($params, $request->event_id);

        if (empty($event)) {
            return error('Database failure!', Response::HTTP_BAD_REQUEST);
        }


        // get media  related event_id who have created now
        return response()->json(['images' => $event, 'status' => true]);

    }

    // Make event title -> slug properly
    protected function slugify($text)
    {
        // replace non letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);

        // trim
        $text = trim($text, '-');

        // lowercase
        $text = strtolower($text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }

}