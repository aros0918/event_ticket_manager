{{-- Common between Admin, Customer & Organiser --}}
<li class="nav-item dropdown ">
    @php
$data = notifications();
    @endphp

    <a class="nav-link dropdown-toggle" href="#" id="blogDropdown" role="button" data-bs-toggle="dropdown"
        aria-expanded="false" v-pre>
        <span class="position-relative btn btn-sm btn-primary badge">
            <i class="fas fa-bell text-white"> </i>
            @if ($data['total_notify'] > 0)
                <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger rounded-circle"></span>
            @endif
        </span>
        <i class="fas fa-caret-down"></i>
    </a>
    <ul class="dropdown-menu" aria-labelledby="blogDropdown">
        @if (!empty($data['notifications']))
            @foreach ($data['notifications'] as $notification)
                <li class="nav-item dropdown">
                    <a class="dropdown-item" href="{{ route('notify_read', [$notification->n_type]) }}">
                        {{ $notification->total }}
                        @if ($notification->n_type == 'user')
                            @lang('em.user')
                        @elseif($notification->n_type == 'contact')
                            @lang('em.contact')
                        @elseif($notification->n_type == 'events')
                            @lang('em.event')
                        @elseif($notification->n_type == 'bookings')
                            @lang('em.booking')
                        @endif
                    </a>
                </li>
            @endforeach
        @else
            <li class="nav-item dropdown">
                <a class="dropdown-item"> @lang('em.no_notifications')</a>
            </li>
        @endif
    </ul>
</li>

<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" id="blogDropdown" role="button" data-bs-toggle="dropdown"
        aria-expanded="false" v-pre>
        @if (Auth::user()->hasRole('customer'))
            <i class="fas fa-user-circle"></i>
        @else
            <i class="fas fa-user-shield"></i>
        @endif

        {{ Auth::user()->name }} <i class="fas fa-caret-down"></i>
    </a>
    <ul class="dropdown-menu" aria-labelledby="blogDropdown">

        {{-- Customer --}}
        @if (Auth::user()->hasRole('customer'))
        <li class="nav-item dropdown ">
            <a class="dropdown-item" href="{{ route('profile') }}"><i class="fas fa-id-card"></i>
                @lang('em.profile')</a>
        </li>
        <li class="nav-item dropdown ">
            <a class="dropdown-item" href="{{ route('mybookings_index') }}"><i
                    class="fas fa-money-check-alt"></i> @lang('em.mybookings')</a>
        </li>
        @endif

        {{-- Admin --}}
        @if (Auth::user()->hasRole('admin'))
        <li class="nav-item dropdown">
            <a class="dropdown-item" href="{{ static_url() . '/' . config('custom.route.admin_prefix') }}"><i
                    class="fas fa-tachometer-alt"></i> @lang('em.admin_panel')</a>
        </li>
        <li class="nav-item dropdown">
            <a class="dropdown-item" href="{{ route('profile') }}"><i class="fas fa-id-card"></i>
                @lang('em.profile')</a>
        </li>
        <li class="nav-item dropdown">
            <a class="dropdown-item" href="{{ route('myevents_form') }}"><i
                    class="fas fa-calendar-plus"></i> @lang('em.create_event')</a>
        </li>
        @endif

        <li class="nav-item dropdown">
            <a class="dropdown-item" href="{{ route('logout') }}"
                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <i class="fas fa-sign-out-alt"></i> @lang('em.logout')
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
            </form>
        </li>
        
    </ul>
</li>



{{-- Admin --}}
@if (Auth::user()->hasRole('admin'))
    <li class="nav-item">
        <a class="nav-link" href="{{ route('myevents_form') }}"><i class="fas fa-calendar-plus"></i>
            @lang('em.create_event')</a>
    </li>
@endif

{{-- Customer --}}
@if (Auth::user()->hasRole('customer'))
    <li class="nav-item">
        <a class="nav-link" href="{{ route('mybookings_index') }}"><i class="fas fa-money-check-alt"></i>
            @lang('em.mybookings')</a>
    </li>
@endif
