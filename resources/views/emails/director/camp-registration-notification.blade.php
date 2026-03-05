@extends('emails.layout.master_layout')

@section('header')
    <tr>
        <td align="center" style="background:#00aeef;padding:25px;">
            <img src="{{ $message->embed(public_path('default/logo.png')) }}" width="180" style="display:block;">
        </td>
    </tr>
@endsection


@section('content')
    <h1 style="color:#00aeef;margin-bottom:10px;font-size:26px;">
        New Referee Registration
    </h1>

    <p style="margin-bottom:25px;color:#4b5563;">
        A referee has successfully registered and completed payment for your camp.
        Below are the registration details.
    </p>


    <!-- CARD WRAPPER -->
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
        <tr>
            <td style="background:#ffffff;border:1px solid #e5e7eb;padding:20px;">

                <h2 style="margin:0 0 15px 0;font-size:18px;color:#111827;">
                    Referee Information
                </h2>

                <table width="100%" cellpadding="6" cellspacing="0" style="font-size:14px;">
                    <tr>
                        <td style="color:#6b7280;">Name</td>
                        <td align="right">
                            <strong>{{ $referee->first_name }} {{ $referee->last_name }}</strong>
                        </td>
                    </tr>

                    <tr>
                        <td style="color:#6b7280;">Email</td>
                        <td align="right">
                            <strong>{{ $referee->email }}</strong>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>



    <!-- CAMP CARD -->
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
        <tr>
            <td style="background:#ffffff;border:1px solid #e5e7eb;padding:20px;">

                <h2 style="margin:0 0 15px 0;font-size:18px;color:#111827;">
                    Camp Information
                </h2>

                <table width="100%" cellpadding="6" cellspacing="0" style="font-size:14px;">

                    <tr>
                        <td style="color:#6b7280;">Camp Name</td>
                        <td align="right">
                            <strong>{{ $camp->camp_name }}</strong>
                        </td>
                    </tr>

                    @if ($camp->location)
                        <tr>
                            <td style="color:#6b7280;">Location</td>
                            <td align="right">
                                <strong>{{ $camp->location }}</strong>
                            </td>
                        </tr>
                    @endif

                    @if ($camp->start_date)
                        <tr>
                            <td style="color:#6b7280;">Start Date</td>
                            <td align="right">
                                <strong>{{ \Carbon\Carbon::parse($camp->start_date)->format('F j, Y') }}</strong>
                            </td>
                        </tr>
                    @endif

                    @if ($camp->end_date)
                        <tr>
                            <td style="color:#6b7280;">End Date</td>
                            <td align="right">
                                <strong>{{ \Carbon\Carbon::parse($camp->end_date)->format('F j, Y') }}</strong>
                            </td>
                        </tr>
                    @endif

                </table>

            </td>
        </tr>
    </table>



    <!-- PAYMENT CARD -->
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
        <tr>
            <td style="background:#ffffff;border:1px solid #e5e7eb;padding:20px;">

                <h2 style="margin:0 0 15px 0;font-size:18px;color:#111827;">
                    Payment Summary
                </h2>

                <table width="100%" cellpadding="6" cellspacing="0" style="font-size:14px;">

                    <tr>
                        <td style="color:#6b7280;">Payment Status</td>
                        <td align="right" style="color:#16a34a;">
                            <strong>{{ ucfirst($payment->status) }}</strong>
                        </td>
                    </tr>

                    <tr>
                        <td style="color:#6b7280;">Amount Paid</td>
                        <td align="right">
                            <strong>${{ number_format($payment->amount, 2) }}</strong>
                        </td>
                    </tr>

                    <tr>
                        <td style="color:#6b7280;">Payment ID</td>
                        <td align="right">
                            <strong>#{{ $payment->id }}</strong>
                        </td>
                    </tr>

                    <tr>
                        <td style="color:#6b7280;">Payment Date</td>
                        <td align="right">
                            <strong>{{ optional($payment->paid_at)->format('F j, Y') }}</strong>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>



    <p style="margin-top:25px;color:#4b5563;">
        You can manage referee assignments and camp details from your director dashboard.
    </p>

    <p style="margin-top:20px;">
        Regards,<br>
        <strong>Whistle Works Team</strong>
    </p>
@endsection
