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
    <h1 style="color: #00aeef;">Assigned as Assistant Director</h1>

    <p>
        Hello <strong>{{ $assistantDirector->first_name }} {{ $assistantDirector->last_name }}</strong>,
    </p>

    <p>
        You have been assigned as an <strong>Assistant Director</strong> for <strong>{{ $camp->camp_name }}</strong> by {{ $director->first_name }} {{ $director->last_name }}.
    </p>

    <hr style="border: 0; border-top: 1px solid #e5e7eb; margin: 20px 0;">

    <h2 style="font-size: 18px; color: #111827; margin-bottom: 12px;">Camp Details</h2>
    <p><strong>Camp Name:</strong> {{ $camp->camp_name }}</p>
    @if (!empty($camp->location))
        <p><strong>Location:</strong> {{ $camp->location }}</p>
    @endif
    @if (!empty($camp->start_date))
        <p><strong>Start Date:</strong> {{ \Carbon\Carbon::parse($camp->start_date)->format('M d, Y') }}</p>
    @endif
    <p><strong>Assigned By:</strong> {{ $director->first_name }} {{ $director->last_name }} ({{ $director->email }})</p>

    <hr style="border: 0; border-top: 1px solid #e5e7eb; margin: 20px 0;">

    <p>
        You can now access and manage this camp directly from your Director Dashboard.
    </p>

    <p style="margin-top: 25px;">
        Regards,<br>
        <strong>Whistle Works Team</strong>
    </p>
@endsection
