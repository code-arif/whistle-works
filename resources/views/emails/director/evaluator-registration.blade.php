@extends('emails.layout.master_layout')

@section('header')
    <tr>
        <td class="header">
            <a href="{{ config('app.url') }}" style="display: inline-block;">
                <img src="{{ $message->embed(public_path('default/logo.png')) }}" alt="Whistle Works" class="logo">
            </a>
        </td>
    </tr>
@endsection

@section('content')
    <h1 style="color: #00aeef;">New Evaluator Registration</h1>

    <p>
        An evaluator has submitted a registration request for your camp.
    </p>

    <hr>

    <p><strong>Evaluator Name:</strong>
        {{ $evaluator->first_name }} {{ $evaluator->last_name }}
    </p>

    <p><strong>Email:</strong> {{ $evaluator->email }}</p>

    @if (optional($evaluator->profile)->phone)
        <p><strong>Phone:</strong> {{ $evaluator->profile->phone }}</p>
    @endif

    <hr>

    <p><strong>Camp Name:</strong> {{ $camp->camp_name }}</p>
    <p><strong>Camp Start Date:</strong>
        {{ optional($camp->start_date)->format('M d, Y') }}
    </p>

    <p><strong>Camp Location:</strong> {{ $camp->location ?? 'N/A' }}</p>

    <hr>

    <p>
        Please review the registration request from your director dashboard and approve or reject accordingly.
    </p>

    <p>
        Regards,<br>
        <strong>Whistle Works Team</strong>
    </p>
@endsection
