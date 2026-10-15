<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office" lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="x-apple-disable-message-reformatting" />
    <meta name="format-detection" content="telephone=no,address=no,email=no,date=no,url=no" />
    <meta name="color-scheme" content="light dark" />
    <meta name="supported-color-schemes" content="light dark" />
    <title>New Inquiry - {{ config('app.name', 'Whistle Works') }}</title>

    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->

    <style type="text/css">
        /* Client-specific Resets */
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
        table { border-collapse: collapse !important; }
        body { height: 100% !important; margin: 0 !important; padding: 0 !important; width: 100% !important; background-color: #F1F5F9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }

        /* iOS Blue Links */
        a[x-apple-data-detectors] {
            color: inherit !important;
            text-decoration: none !important;
            font-size: inherit !important;
            font-family: inherit !important;
            font-weight: inherit !important;
            line-height: inherit !important;
        }

        /* Gmail Blue Links */
        u + #body a {
            color: inherit;
            text-decoration: none;
            font-size: inherit;
            font-family: inherit;
            font-weight: inherit;
            line-height: inherit;
        }

        /* Responsive Mobile Queries */
        @media only screen and (max-width: 620px) {
            .mobile-container {
                width: 100% !important;
                max-width: 100% !important;
                padding-left: 12px !important;
                padding-right: 12px !important;
            }
            .mobile-padding {
                padding-left: 20px !important;
                padding-right: 20px !important;
                padding-top: 24px !important;
                padding-bottom: 24px !important;
            }
            .mobile-header-padding {
                padding-left: 20px !important;
                padding-right: 20px !important;
                padding-top: 20px !important;
                padding-bottom: 20px !important;
            }
            .stack-column {
                display: block !important;
                width: 100% !important;
                max-width: 100% !important;
                direction: ltr !important;
                text-align: left !important;
            }
            .stack-column-value {
                display: block !important;
                width: 100% !important;
                max-width: 100% !important;
                padding-top: 4px !important;
                text-align: left !important;
            }
            .button-cell {
                width: 100% !important;
                text-align: center !important;
            }
            .button-link {
                display: block !important;
                width: auto !important;
                text-align: center !important;
            }
        }

        /* Dark Mode Support */
        @media (prefers-color-scheme: dark) {
            body { background-color: #0F172A !important; }
            .email-card { background-color: #1E293B !important; border-color: #334155 !important; }
            .heading-text { color: #F8FAFC !important; }
            .body-text { color: #CBD5E1 !important; }
            .label-text { color: #94A3B8 !important; }
            .value-text { color: #F1F5F9 !important; }
            .message-box { background-color: #0F172A !important; border-left-color: #38BDF8 !important; }
            .row-divider { border-bottom-color: #334155 !important; }
            .footer-text { color: #64748B !important; }
        }
    </style>
</head>
<body id="body" style="margin: 0; padding: 0; background-color: #F1F5F9; -webkit-font-smoothing: antialiased;">
    <!-- Hidden Preheader Text for Inbox Preview -->
    <div style="display: none; font-size: 1px; color: #F1F5F9; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden; mso-hide: all;">
        New inquiry from {{ $contact->name ?: $contact->email }}: {{ \Illuminate\Support\Str::limit($contact->subject ?: $contact->message, 90) }}
    </div>

    <!-- Outer Wrapper Table -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #F1F5F9; table-layout: fixed;">
        <tr>
            <td align="center" style="padding: 32px 12px 40px 12px;">
                <!--[if (gte mso 9)|(IE)]>
                <table align="center" border="0" cellspacing="0" cellpadding="0" width="600">
                <tr>
                <td align="center" valign="top" width="600">
                <![endif]-->

                <table class="mobile-container" role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="max-width: 600px; margin: 0 auto;">
                    <!-- Brand Header -->
                    <tr>
                        <td class="mobile-header-padding" align="left" style="background-color: #0F172A; padding: 24px 32px; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td align="left" valign="middle">
                                        <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 18px; font-weight: 700; letter-spacing: 0.5px; color: #FFFFFF; line-height: 24px;">
                                            {{ strtoupper(config('app.name', 'WHISTLE WORKS')) }}
                                        </div>
                                        <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 12px; font-weight: 400; color: #94A3B8; line-height: 16px; margin-top: 2px;">
                                            Operations & Support Desk
                                        </div>
                                    </td>
                                    <td align="right" valign="middle">
                                        <span style="display: inline-block; padding: 4px 10px; background-color: #1E293B; border: 1px solid #334155; border-radius: 4px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 11px; font-weight: 600; letter-spacing: 0.5px; color: #38BDF8; text-transform: uppercase;">
                                            Contact Form
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Main Content Card -->
                    <tr>
                        <td class="email-card mobile-padding" style="background-color: #FFFFFF; padding: 32px; border-left: 1px solid #E2E8F0; border-right: 1px solid #E2E8F0; border-bottom: 1px solid #E2E8F0;">
                            <!-- Heading -->
                            <div class="heading-text" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 20px; font-weight: 600; color: #0F172A; line-height: 28px; margin: 0 0 8px 0;">
                                New Inquiry Received
                            </div>
                            <div class="body-text" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 14px; font-weight: 400; color: #475569; line-height: 22px; margin: 0 0 24px 0;">
                                A visitor submitted a message through the website contact form. The submission details are recorded below.
                            </div>

                            <!-- Details Table -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 24px; border: 1px solid #E2E8F0; border-radius: 6px; overflow: hidden;">
                                <!-- Name Row -->
                                <tr>
                                    <td class="row-divider" style="padding: 12px 16px; background-color: #FAFAFA; border-bottom: 1px solid #E2E8F0; width: 35%;">
                                        <span class="label-text" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #64748B;">
                                            Full Name
                                        </span>
                                    </td>
                                    <td class="row-divider value-text" style="padding: 12px 16px; background-color: #FFFFFF; border-bottom: 1px solid #E2E8F0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 14px; font-weight: 500; color: #0F172A;">
                                        {{ $contact->name ?: 'Not specified' }}
                                    </td>
                                </tr>

                                <!-- Email Row -->
                                <tr>
                                    <td class="row-divider" style="padding: 12px 16px; background-color: #FAFAFA; border-bottom: 1px solid #E2E8F0; width: 35%;">
                                        <span class="label-text" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #64748B;">
                                            Email Address
                                        </span>
                                    </td>
                                    <td class="row-divider value-text" style="padding: 12px 16px; background-color: #FFFFFF; border-bottom: 1px solid #E2E8F0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 14px; font-weight: 500; color: #0284C7;">
                                        <a href="mailto:{{ $contact->email }}" style="color: #0284C7; text-decoration: none;">
                                            {{ $contact->email }}
                                        </a>
                                    </td>
                                </tr>

                                <!-- Phone Row -->
                                @if(!empty($contact->phone))
                                <tr>
                                    <td class="row-divider" style="padding: 12px 16px; background-color: #FAFAFA; border-bottom: 1px solid #E2E8F0; width: 35%;">
                                        <span class="label-text" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #64748B;">
                                            Phone Number
                                        </span>
                                    </td>
                                    <td class="row-divider value-text" style="padding: 12px 16px; background-color: #FFFFFF; border-bottom: 1px solid #E2E8F0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 14px; font-weight: 500; color: #0F172A;">
                                        {{ $contact->phone }}
                                    </td>
                                </tr>
                                @endif

                                <!-- Subject Row -->
                                <tr>
                                    <td class="row-divider" style="padding: 12px 16px; background-color: #FAFAFA; border-bottom: 1px solid #E2E8F0; width: 35%;">
                                        <span class="label-text" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #64748B;">
                                            Subject
                                        </span>
                                    </td>
                                    <td class="row-divider value-text" style="padding: 12px 16px; background-color: #FFFFFF; border-bottom: 1px solid #E2E8F0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 14px; font-weight: 500; color: #0F172A;">
                                        {{ $contact->subject ?: 'General Inquiry' }}
                                    </td>
                                </tr>

                                <!-- Submission Time Row -->
                                <tr>
                                    <td style="padding: 12px 16px; background-color: #FAFAFA; width: 35%;">
                                        <span class="label-text" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #64748B;">
                                            Submitted At
                                        </span>
                                    </td>
                                    <td class="value-text" style="padding: 12px 16px; background-color: #FFFFFF; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 14px; font-weight: 400; color: #475569;">
                                        {{ $contact->created_at ? $contact->created_at->format('F j, Y, g:i a e') : now()->format('F j, Y, g:i a e') }}
                                    </td>
                                </tr>
                            </table>

                            <!-- Message Section Header -->
                            <div class="label-text" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #64748B; margin-bottom: 8px;">
                                Message Content
                            </div>

                            <!-- Message Content Box -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 28px;">
                                <tr>
                                    <td class="message-box" style="background-color: #F8FAFC; border-left: 3px solid #0284C7; border-top: 1px solid #E2E8F0; border-right: 1px solid #E2E8F0; border-bottom: 1px solid #E2E8F0; border-radius: 4px; padding: 18px 20px;">
                                        <div class="body-text" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 14px; font-weight: 400; color: #1E293B; line-height: 24px; white-space: pre-wrap; word-break: break-word;">{!! nl2br(e($contact->message)) !!}</div>
                                    </td>
                                </tr>
                            </table>

                            <!-- Reply CTA Button -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td class="button-cell" align="left">
                                        <!--[if (gte mso 9)|(IE)]>
                                        <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="mailto:{{ $contact->email }}?subject=Re:%20{{ rawurlencode($contact->subject ?: 'Inquiry to Whistle Works') }}" style="height:44px;v-text-anchor:middle;width:180px;" arcsize="10%" fillcolor="#0F172A" stroke="f">
                                        <w:anchorlock/>
                                        <center style="color:#ffffff;font-family:sans-serif;font-size:14px;font-weight:bold;">Reply via Email</center>
                                        </v:roundrect>
                                        <![endif]-->
                                        <!--[if !mso]><!-- -->
                                        <a class="button-link" href="mailto:{{ $contact->email }}?subject=Re:%20{{ rawurlencode($contact->subject ?: 'Inquiry to Whistle Works') }}" style="display: inline-block; background-color: #0F172A; color: #FFFFFF; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 14px; font-weight: 600; text-decoration: none; padding: 12px 24px; border-radius: 4px; text-align: center; mso-padding-alt: 0;">
                                            Reply via Email
                                        </a>
                                        <!--<![endif]-->
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="padding: 24px 16px;">
                            <div class="footer-text" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 12px; font-weight: 400; color: #64748B; line-height: 18px; text-align: center;">
                                This is an automated notification generated by the {{ config('app.name', 'Whistle Works') }} platform.
                            </div>
                            <div class="footer-text" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 11px; font-weight: 400; color: #94A3B8; line-height: 16px; margin-top: 6px; text-align: center;">
                                Record ID: #{{ $contact->id }} &bull; Status: {{ ucfirst($contact->status ?? 'active') }}
                            </div>
                            <div class="footer-text" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 11px; font-weight: 400; color: #94A3B8; line-height: 16px; margin-top: 6px; text-align: center;">
                                &copy; {{ date('Y') }} {{ config('app.name', 'Whistle Works') }}. All rights reserved.
                            </div>
                        </td>
                    </tr>
                </table>

                <!--[if (gte mso 9)|(IE)]>
                </td>
                </tr>
                </table>
                <![endif]-->
            </td>
        </tr>
    </table>
</body>
</html>
