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
    <h1 style="color: #00aeef;">Camp Registration Submitted</h1>

    <p>
        Dear {{ $evaluator->first_name }},
    </p>

    <p>
        Your registration request for the following camp has been successfully submitted and is currently pending director
        approval.
    </p>

    <hr>

    <p><strong>Camp Name:</strong> {{ $camp->camp_name }}</p>

    <p><strong>Start Date:</strong>
        {{ optional($camp->start_date)->format('M d, Y') }}
    </p>

    <p><strong>Location:</strong> {{ $camp->location ?? 'N/A' }}</p>

    <hr>

    <p>
        You will receive another email once your registration has been reviewed.
    </p>

    <p>
        Thank you for being part of Whistle Works.
    </p>

    <p>
        Regards,<br>
        <strong>Whistle Works Team</strong>
    </p>
@endsection
