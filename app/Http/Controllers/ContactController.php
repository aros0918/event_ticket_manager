<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Models\Contact;
use App\Models\User;

use App\Notifications\MailNotification;


class ContactController extends Controller
{


    public function __construct()
    {
        // language change
        $this->middleware('common');

        $this->contact = new Contact;
    }


    // get featured events
    public function index($view = 'contact', $extra = [])
    {
        return view($view, compact('extra'));
    }

    // contact save
    public function store_contact(Request $request)
    {
        $request->validate([
            'name' => 'required|min:5|max:256',
            'email' => 'required|email',
            'title' => 'required|min:3|max:256',
            'message' => 'required|min:2|max:1000',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'title' => $request->title,
            'message' => $request->message,
            'created_at' => \Carbon\Carbon::now(),
            'updated_at' => \Carbon\Carbon::now(),
        ];

        $contact = $this->contact->store_contact($data);

        if (empty($contact)) {
            return redirect()->back()->with('msg', __('em.message_sent_fail'));
        }

        // ====================== Notification ====================== 
        //send notification after bookings
        $msg[] = __('em.name') . ' - ' . $contact->name;
        $msg[] = __('em.email') . ' - ' . $contact->email;
        $msg[] = __('em.title') . ' - ' . $contact->title;
        $msg[] = __('em.message') . ' - ' . $contact->message;
        $extra_lines = $msg;

        $mail['mail_subject'] = __('em.message_sent');
        $mail['mail_message'] = __('em.get_tickets');
        $mail['action_title'] = __('em.view') . ' ' . __('em.all') . ' ' . __('em.events');
        $mail['action_url'] = route('events_index');
        $mail['n_type'] = "contact";

        // notification for
        $notification_ids = [
            User::whereId(1)->first(), // admin
            $contact->email
        ];

        // $users = User::whereIn('id', $notification_ids)->get();

        $users = $notification_ids;

        try {
            \Notification::route('mail', $users)->notify(new MailNotification($mail, $extra_lines));
        } catch (\Throwable $th) {
        }
        // ====================== Notification ====================== 

        return redirect()->back()->with('msg', __('em.message_sent'));
    }
}