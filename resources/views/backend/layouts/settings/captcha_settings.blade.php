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
                    <h1 class="page-title">Captcha Settings <i class="fa-solid fa-triangle-exclamation text-danger" title="Warning"></i></h1>
                    <p class="text-muted mb-0" style="font-size: 13px;">Configure reCAPTCHA verification settings</p>
                </div>
                <ol class="breadcrumb mb-0 py-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.setting.general.index') }}">Settings</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Captcha</li>
                </ol>
            </div>
            {{-- PAGE-HEADER --}}


            <div class="row">
                <div class="col-lg-12 col-xl-12 col-md-12 col-sm-12">
                    <h2 class="card-header">Google Settings</h2>
                    <div class="card box-shadow-0">
                        <div class="card-body">
                            <form class="form form-horizontal" method="post" action="{{ route('admin.setting.captcha.update') }}" enctype="multipart/form-data">
                                @csrf
                                @method('PATCH')

                                <div class="row mb-4">
                                    <label for="recaptcha_site_key" class="col-md-3 form-label">Recaptcha Site Key</label>
                                    <div class="col-md-9">
                                        <input class="form-control @error('recaptcha_site_key') is-invalid @enderror" id="recaptcha_site_key"
                                            name="recaptcha_site_key" placeholder="Enter your stripe key" type="text"
                                            value="{{ env('RECAPTCHA_SITE_KEY') ?? old('recaptcha_site_key') }}">
                                        @error('recaptcha_site_key')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <label for="recaptcha_secret_key" class="col-md-3 form-label">Recaptcha Secret Key</label>
                                    <div class="col-md-9">
                                        <input class="form-control @error('recaptcha_secret_key') is-invalid @enderror" id="recaptcha_secret_key"
                                            name="recaptcha_secret_key" placeholder="Enter your stripe secret" type="text"
                                            value="{{ env('RECAPTCHA_SECRET_KEY') ?? old('recaptcha_secret_key') }}">
                                        @error('recaptcha_secret_key')
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