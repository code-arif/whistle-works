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
                    <h1 class="page-title">Firebase Settings</h1>
                    <p class="text-muted mb-0" style="font-size: 13px;">Configure Firebase cloud messaging</p>
                </div>
                <ol class="breadcrumb mb-0 py-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.setting.general.index') }}">Settings</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Firebase</li>
                </ol>
            </div>
            {{-- PAGE-HEADER --}}

            <div class="row">
                <div class="col-lg-12 col-xl-12 col-md-12 col-sm-12">
                    <div class="card box-shadow-0">
                        <div class="card-body">
                            <form class="form form-horizontal" method="post" action="{{ route('admin.setting.firebase.update') }}" enctype="multipart/form-data">
                                @csrf
                                @method('PATCH')
                                <div class="row mb-4">
                                    <label for="firebase_credentials" class="col-md-3 form-label">Firebase</label>
                                    <div class="col-md-9">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="btn" title="Carefully Change Your Firebase Credentials">
                                                    <i class="fa-solid fa-triangle-exclamation text-danger"></i>
                                                </span>
                                            </div>
                                            <input class="form-control @error('firebase_credentials') is-invalid @enderror" id="firebase_credentials"
                                                name="firebase_credentials" placeholder="Enter your stripe key" type="text"
                                                value="{{ env('FIREBASE_CREDENTIALS') ?? old('firebase_credentials') }}">
                                        </div>
                                        @error('firebase_credentials')
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