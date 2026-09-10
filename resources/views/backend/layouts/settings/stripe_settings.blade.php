@extends('backend.app')

@section('content')
    <!--app-content open-->
    <div class="app-content main-content mt-0">
        <div class="side-app" style="margin-bottom: 50px">

            <!-- CONTAINER -->
            <div class="main-container container-fluid">

                {{-- PAGE-HEADER --}}
                <div class="page-header d-flex flex-wrap align-items-center justify-content-between">
                    <div>
                        <h1 class="page-title">Stripe Settings
                            {{-- <i class="fa-solid fa-triangle-exclamation text-danger"
                                title="Warning"></i> --}}
                            </h1>
                        <p class="text-muted mb-0" style="font-size: 13px;">Configure Stripe payment gateway credentials</p>
                    </div>
                    <ol class="breadcrumb mb-0 py-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.setting.general.index') }}">Settings</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Stripe</li>
                    </ol>
                </div>
                {{-- PAGE-HEADER --}}

                <div class="row">
                    <div class="row col-md-12">
                        <div class="col-lg-12 col-xl-12 col-md-12 col-sm-12">
                            <h3>Stripe Boosting Credential</h3>
                            <div class="card box-shadow-0">
                                <div class="card-body">
                                    <form class="form form-horizontal" method="post"
                                        action="{{ route('admin.setting.stripe.update') }}" enctype="multipart/form-data">
                                        @csrf
                                        @method('PATCH')
                                        <div class="row mb-4">
                                            <label for="stripe_key" class="col-md-3 form-label">Stripe Key</label>
                                            <div class="col-md-9">
                                                <input class="form-control @error('stripe_key') is-invalid @enderror"
                                                    id="stripe_key" name="stripe_key" placeholder="Enter your stripe key"
                                                    type="text" value="{{ env('STRIPE_KEY') ?? old('stripe_key') }}">
                                                @error('stripe_key')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="row mb-4">
                                            <label for="stripe_secret" class="col-md-3 form-label">Stripe Secret</label>
                                            <div class="col-md-9">
                                                <input class="form-control @error('stripe_secret') is-invalid @enderror"
                                                    id="stripe_secret" name="stripe_secret"
                                                    placeholder="Enter your stripe secret" type="text"
                                                    value="{{ env('STRIPE_SECRET') ?? old('stripe_secret') }}">
                                                @error('stripe_secret')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="row mb-4">
                                            <label for="stripe_webhook_secret" class="col-md-3 form-label">Stripe Boosting
                                                Webhook Secret</label>
                                            <div class="col-md-9">
                                                <input
                                                    class="form-control @error('stripe_webhook_secret') is-invalid @enderror"
                                                    id="stripe_webhook_secret" name="stripe_webhook_secret"
                                                    placeholder="Enter your stripe webhook secret" type="text"
                                                    value="{{ env('STRIPE_WEBHOOK_SECRET') ?? old('stripe_webhook_secret') }}">
                                                @error('stripe_webhook_secret')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>


                                        <!-- checkout webhook -->
                                        <div class="row mb-4">
                                            <label for="stripe_webhook_secret" class="col-md-3 form-label">Stripe Checkout
                                                Webhook Secret</label>
                                            <div class="col-md-9">
                                                <input
                                                    class="form-control @error('stripe_checkout_webhook_secret') is-invalid @enderror"
                                                    id="stripe_checkout_webhook_secret"
                                                    name="stripe_checkout_webhook_secret"
                                                    placeholder="Enter your stripe webhook secret" type="text"
                                                    value="{{ env('STRIPE_CHECKOUT_WEBHOOK_SECRET') ?? old('stripe_checkout_webhook_secret') }}">
                                                @error('stripe_checkout_webhook_secret')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>


                                        <!-- rental webhook -->
                                        <div class="row mb-4">
                                            <label for="stripe_webhook_secret" class="col-md-3 form-label">Stripe Rental
                                                Webhook Secret</label>
                                            <div class="col-md-9">
                                                <input
                                                    class="form-control @error('stripe_rented_webhook_secret') is-invalid @enderror"
                                                    id="stripe_rental_checkout_webhook_secret"
                                                    name="stripe_rented_webhook_secret"
                                                    placeholder="Enter your stripe webhook secret" type="text"
                                                    value="{{ env('STRIPE_RENTED_WEBHOOK_SECRET') ?? old('stripe_rented_checkout_webhook_secret') }}">
                                                @error('stripe_rented_webhook_secret')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>


                                        <div class="row justify-content-end">
                                            <div class="col-sm-9">
                                                <div>
                                                    <button class="submit btn btn-primary" type="submit">Submit</button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- for onboarding -->
                    {{-- <div class="row col-md-6">
                    <div class="col-lg-12 col-xl-12 col-md-12 col-sm-12">
                        <h3>Stripe Onboarding Credential</h3>
                        <div class="card box-shadow-0">
                            <div class="card-body">
                                <form class="form form-horizontal" method="post" action="{{ route('admin.setting.stripe.update-onboarding') }}" enctype="multipart/form-data">
                                    @csrf
                                    @method('PATCH')
                                    <div class="row mb-4">
                                        <label for="stripe_key" class="col-md-3 form-label">Stripe Client Id</label>
                                        <div class="col-md-9">
                                            <input class="form-control @error('STRIPE_CLIENT_ID') is-invalid @enderror" id="stripe_key"
                                                name="stripe_client_id" placeholder="Enter your stripe Client Id" type="text"
                                                value="{{ env('STRIPE_CLIENT_ID') ?? old('stripe_client_id') }}">
                                            @error('stripe_client_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="row mb-4">
                                        <label for="stripe_redirect_url" class="col-md-3 form-label">Stripe Redirect Url</label>
                                        <div class="col-md-9">
                                            <input class="form-control @error('stripe_redirect_url') is-invalid @enderror" id="stripe_secret"
                                                name="stripe_redirect_url" placeholder="Enter your stripe redirect url" type="text"
                                                value="{{ env('STRIPE_REDIRECT_URI') ?? old('stripe_redirect_url') }}">
                                            @error('stripe_redirect_url')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="row justify-content-end">
                                        <div class="col-sm-9">
                                            <div>
                                                <button class="submit btn btn-primary" type="submit">Submit</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div> --}}


                    {{-- Sports Fee per Sports Type (replaces global CAMP_EXTRA_PRICE) --}}
                    <div class="row col-md-6">
                        <div class="col-lg-12 col-xl-12 col-md-12 col-sm-12">
                            {{-- <h3>Sports Fees <i class="fa-solid fa-circle-info text-primary" title="Fees are now configured per sports type"></i> --}}
                            </h3>
                            <div class="card box-shadow-0">
                                <div class="card-body">
                                    <div class="alert alert-info d-flex align-items-center" role="alert">
                                        {{-- <i class="fe fe-info me-2"></i> --}}
                                        <div>
                                            <strong>Per-Sports-Type Fees Active</strong><br>
                                            The software fee is now configured individually for each sports type.
                                            <a href="{{ route('admin.sports-type.index') }}" class="alert-link">Manage Sports Fees</a>
                                        </div>
                                    </div>

                                    <p class="text-muted mb-2" style="font-size: 13px;">Current sports fees:</p>
                                    @php
                                        $sportsTypes = \App\Models\SportsType::where('status', 'active')->get();
                                    @endphp
                                    @if($sportsTypes->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>Sports Type</th>
                                                        <th class="text-end">Fee ($)</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($sportsTypes as $st)
                                                        <tr>
                                                            <td>{{ $st->sports_name }}</td>
                                                            <td class="text-end fw-bold">${{ number_format($st->sports_fee, 2) }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <p class="text-muted">No active sports types found.</p>
                                    @endif

                                    <hr>
                                    <p class="text-muted mb-0" style="font-size: 12px;">
                                        {{-- <i class="fe fe-alert-circle"></i> --}}
                                        The global <code>CAMP_EXTRA_PRICE</code> env setting is no longer used.
                                        Each sports type has its own fee configured in <a href="{{ route('admin.sports-type.index') }}">Sports Type Management</a>.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- CONTAINER CLOSED -->
@endsection



@push('scripts')
@endpush
