@extends('emails.layout.master_layout')

@section('header')
    <tr>
        <td class="header" align="center" style="padding:25px 0;">
            <a href="{{ config('app.url') }}">
                <img src="{{ $message->embed(public_path('default/logo.png')) }}" alt="Whistle Works" style="max-width:160px;">
            </a>
        </td>
    </tr>
@endsection

@section('content')
    <h1 style="color:#00aeef;font-size:24px;margin-bottom:10px;">
        Registration Confirmed
    </h1>

    <p style="font-size:16px;">
        Hello <strong>{{ $user->first_name }}</strong>,
    </p>

    <p style="font-size:15px;">
        Your registration and payment have been successfully completed for the following camp.
    </p>

    <br>

    <table width="100%" cellpadding="0" cellspacing="0"
        style="border:1px solid #e5e7eb;border-radius:8px;background:#f9fafb;">

        <tr>
            <td style="padding:20px;">

                <h2 style="margin-top:0;color:#111827;">Camp Details</h2>

                <table width="100%" cellpadding="6" cellspacing="0">

                    <tr>
                        <td style="color:#6b7280;">Camp Name</td>
                        <td><strong>{{ $camp->camp_name }}</strong></td>
                    </tr>

                    @if ($camp->location)
                        <tr>
                            <td style="color:#6b7280;">Location</td>
                            <td><strong>{{ $camp->location }}</strong></td>
                        </tr>
                    @endif

                    @if ($camp->start_date)
                        <tr>
                            <td style="color:#6b7280;">Start Date</td>
                            <td><strong>{{ \Carbon\Carbon::parse($camp->start_date)->format('F j, Y') }}</strong></td>
                        </tr>
                    @endif

                    @if ($camp->end_date)
                        <tr>
                            <td style="color:#6b7280;">End Date</td>
                            <td><strong>{{ \Carbon\Carbon::parse($camp->end_date)->format('F j, Y') }}</strong></td>
                        </tr>
                    @endif

                </table>

            </td>
        </tr>
    </table>

    <br>

    <table width="100%" cellpadding="0" cellspacing="0"
        style="border:1px solid #e5e7eb;border-radius:8px;background:#ffffff;">

        <tr>
            <td style="padding:20px;">

                <h2 style="margin:0 0 15px 0;color:#111827;font-size:20px;">
                    Registration Information
                </h2>

                <table width="100%" cellpadding="8" cellspacing="0" style="font-size:14px;">

                    <tr>
                        <td style="color:#6b7280;width:50%;">Registration ID</td>
                        <td style="text-align:right;">
                            <strong>#{{ $registration->id }}</strong>
                        </td>
                    </tr>

                    <tr>
                        <td style="color:#6b7280;">Registration Date</td>
                        <td style="text-align:right;">
                            <strong>{{ $registration->registered_at->format('F j, Y') }}</strong>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>

    </table>

    <br>

    <table width="100%" cellpadding="0" cellspacing="0"
        style="border:1px solid #e5e7eb;border-radius:8px;background:#f9fafb;">

        <tr>
            <td style="padding:20px;">

                <h2 style="margin:0 0 15px 0;color:#111827;font-size:20px;">
                    Payment Summary
                </h2>

                <table width="100%" cellpadding="8" cellspacing="0" style="font-size:14px; line-height:1.6;">

                    <tr>
                        <td style="color:#6b7280;width:50%;">Payment Status</td>
                        <td style="text-align:right;color:#16a34a;">
                            <strong>Paid Successfully</strong>
                        </td>
                    </tr>

                    @if (isset($payment))
                        <tr>
                            <td style="color:#6b7280;">Amount Paid</td>
                            <td style="text-align:right;">
                                <strong>${{ number_format($payment->amount, 2) }}</strong>
                            </td>
                        </tr>
                    @endif

                    <tr>
                        <td style="color:#6b7280;">Payment Date</td>
                        <td style="text-align:right;">
                            <strong>{{ now()->format('F j, Y') }}</strong>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>

    </table>

    <br>

    <p style="font-size:15px;">
        We look forward to seeing you at the camp. If you have any questions, feel free to contact our support team.
    </p>

    <br>

    <a href="{{ config('app.frontend_url') }}"
        style="display:inline-block;background:#00aeef;color:#ffffff;padding:12px 22px;border-radius:6px;text-decoration:none;font-weight:600;">
        Visit Website
    </a>

    <br><br>

    <p style="font-size:14px;color:#6b7280;">
        Thank you for choosing <strong>Whistle Works</strong>.
    </p>
@endsection
