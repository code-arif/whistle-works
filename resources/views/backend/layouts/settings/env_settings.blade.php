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
                    <h1 class="page-title">Env Settings <i class="fa-solid fa-triangle-exclamation text-danger" title="Warning"></i></h1>
                    <p class="text-muted mb-0" style="font-size: 13px;">Manage environment configuration</p>
                </div>
                <ol class="breadcrumb mb-0 py-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.setting.general.index') }}">Settings</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Env</li>
                </ol>
            </div>
            {{-- PAGE-HEADER --}}


            <div class="row">
                <div class="col-lg-12 col-xl-12 col-md-12 col-sm-12">
                    <div class="card box-shadow-0">
                        <div class="card-body">
                            <form class="form form-horizontal" method="post" action="{{ route('admin.setting.env.update') }}" enctype="multipart/form-data">
                                @csrf
                                @method('PATCH')

                                <div class="row mb-4">
                                    <label for="app_name" class="col-md-3 form-label">App Name</label>
                                    <div class="col-md-9">
                                        <input class="form-control @error('stripe_key') is-invalid @enderror" id="stripe_key" name="app_name" placeholder="Enter your app name" type="text" value="{{ env('APP_NAME') ?? old('app_name') }}">
                                        @error('app_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <label for="app_url" class="col-md-3 form-label">App url</label>
                                    <div class="col-md-9">
                                        <input class="form-control @error('app_url') is-invalid @enderror" id="app_url" name="app_url" placeholder="Enter your app url" type="text" value="{{ env('APP_URL') ?? old('app_url') }}">
                                        @error('app_url')
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