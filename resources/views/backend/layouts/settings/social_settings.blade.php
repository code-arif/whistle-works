@extends('backend.app')

@section('content')
<!--app-content open-->
<div class="app-content main-content mt-0">
    <div class="side-app">

        <!-- CONTAINER -->
        <div class="main-container container-fluid">

            {{-- PAGE-HEADER --}}
            <div class="page-header d-flex flex-wrap align-items-center justify-content-between">
                <div>
                    <h1 class="page-title">Stripe Settings <i class="fa-solid fa-triangle-exclamation text-danger" title="Warning"></i></h1>
                    <p class="text-muted mb-0" style="font-size: 13px;">Configure Google social login settings</p>
                </div>
                <ol class="breadcrumb mb-0 py-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.setting.general.index') }}">Settings</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Stripe</li>
                </ol>
            </div>
            {{-- PAGE-HEADER --}}


            <div class="row">
                <div class="col-lg-12 col-xl-12 col-md-12 col-sm-12">
                    <h2 class="card-header">Google Settings</h2>
                    <div class="card box-shadow-0">
                        <div class="card-body">
                            <form class="form form-horizontal" method="post" action="{{ route('admin.setting.social.update') }}" enctype="multipart/form-data">
                                @csrf
                                @method('PATCH')

                                <div class="row mb-4">
                                    <label for="google_client_id" class="col-md-3 form-label">Google Client ID</label>
                                    <div class="col-md-9">
                                        <input class="form-control @error('google_client_id') is-invalid @enderror" id="google_client_id"
                                            name="google_client_id" placeholder="Enter your stripe key" type="text"
                                            value="{{ env('GOOGLE_CLIENT_ID') ?? old('google_client_id') }}">
                                        @error('google_client_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <label for="google_client_secret" class="col-md-3 form-label">Google Client Secret</label>
                                    <div class="col-md-9">
                                        <input class="form-control @error('google_client_secret') is-invalid @enderror" id="google_client_secret"
                                            name="google_client_secret" placeholder="Enter your stripe secret" type="text"
                                            value="{{ env('GOOGLE_CLIENT_SECRET') ?? old('google_client_secret') }}">
                                        @error('google_client_secret')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <label for="google_redirect_uri" class="col-md-3 form-label">Google Redirect Uri</label>
                                    <div class="col-md-9">
                                        <input class="form-control @error('google_redirect_uri') is-invalid @enderror" id="google_redirect_uri"
                                            name="google_redirect_uri" placeholder="Enter your stripe webhook secret" type="text"
                                            value="{{ env('GOOGLE_REDIRECT_URI') ?? old('google_redirect_uri') }}">
                                        @error('google_redirect_uri')
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

        </div>
    </div>
</div>
<!-- CONTAINER CLOSED -->
@endsection



@push('scripts')
@endpush