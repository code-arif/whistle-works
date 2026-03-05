@extends('backend.app', ['title' => 'Camps Management'])

@section('content')
    <!--app-content open-->
    <div class="app-content main-content mt-0">
        <div class="side-app" style="margin-bottom: 50px">

            <!-- CONTAINER -->
            <div class="main-container container-fluid">

                <!-- PAGE-HEADER -->
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Camps Management</h1>
                    </div>
                    <div class="ms-auto pageheader-btn">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Camps</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Index</li>
                        </ol>
                    </div>
                </div>
                <!-- PAGE-HEADER END -->

                <!-- ROW-4 -->
                <div class="row">
                    <div class="col-12 col-sm-12">
                        <div class="card product-sales-main">
                            <div class="card-header border-bottom">
                                <h3 class="card-title mb-0">Camps List</h3>
                                <div class="card-options ms-auto">
                                    <button type="button"
                                        class="btn btn-primary btn-sm d-inline-flex align-items-center me-2"
                                        onclick="showCreateModal()">
                                        <i class="fe fe-plus"></i> Add Camp
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered text-nowrap border-bottom" id="datatable">
                                        <thead>
                                            <tr>
                                                <th class="bg-transparent border-bottom-0">ID</th>
                                                <th class="bg-transparent border-bottom-0">Director</th>
                                                <th class="bg-transparent border-bottom-0">Camp Info</th>
                                                <th class="bg-transparent border-bottom-0">Sports Type</th>
                                                <th class="bg-transparent border-bottom-0">Dates</th>
                                                <th class="bg-transparent border-bottom-0">Price</th>
                                                <th class="bg-transparent border-bottom-0">Status</th>
                                                <th class="bg-transparent border-bottom-0">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div><!-- COL END -->
                </div>
                <!-- ROW-4 END -->

            </div>
        </div>
    </div>
    <!-- CONTAINER CLOSED -->


    <!-- Create/Edit Camp Modal -->
    <div class="modal fade" id="campModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="campModalLabel">Add New Camp</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"> &times; </button>
                </div>
                <form id="campForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="camp_id" name="camp_id">
                    <input type="hidden" id="form_method" value="POST">
                    <input type="hidden" id="latitude" name="latitude">
                    <input type="hidden" id="longitude" name="longitude">

                    <div class="modal-body">
                        <div class="row g-3">
                            <!-- Director Selection -->
                            <div class="col-md-6">
                                <label for="director_id" class="form-label">Director <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="director_id" name="director_id" required>
                                    <option value="">Select Director</option>
                                    @foreach ($directors as $director)
                                        <option value="{{ $director->id }}">
                                            {{ trim($director->first_name . ' ' . $director->last_name) ?: $director->username }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Sports Type Selection -->
                            <div class="col-md-6">
                                <label for="sports_type_id" class="form-label">Sports Type <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="sports_type_id" name="sports_type_id" required>
                                    <option value="">Select Sports Type</option>
                                    @foreach ($sportsTypes as $sportsType)
                                        <option value="{{ $sportsType->id }}">{{ $sportsType->sports_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Camp Name -->
                            <div class="col-12">
                                <label for="camp_name" class="form-label">Camp Name <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="camp_name" name="camp_name" required>
                            </div>

                            <!-- Location Search with Google Maps -->
                            <div class="col-md-6">
                                <label for="location" class="form-label">Location <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="location" name="location"
                                    placeholder="Search location or click on map..." required>
                                <small class="text-muted">Search for a location or click on the map to set
                                    coordinates</small>
                            </div>

                            {{-- address --}}
                            <div class="col-md-6">
                                <label for="address" class="form-label"> Address </label>
                                <input type="text" class="form-control" id="address" name="address">
                            </div>


                            <!-- Google Map Container -->
                            <div class="col-12">
                                <div id="map-container"
                                    style="height: 400px; border-radius: 8px; overflow: hidden; border: 1px solid #dee2e6;">
                                    <div id="map" style="height: 100%; width: 100%;"></div>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="fe fe-map-pin text-primary me-1"></i>
                                        <span id="coordinates-display">Click on map or search to set location</span>
                                    </small>
                                </div>
                            </div>

                            <!-- Start Date & End Date -->
                            <div class="col-md-6">
                                <label for="start_date" class="form-label">Start Date <span
                                        class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="start_date" name="start_date" required>
                            </div>

                            <div class="col-md-6">
                                <label for="end_date" class="form-label">End Date <span
                                        class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="end_date" name="end_date" required>
                            </div>

                            <!-- Price -->
                            <div class="col-12">
                                <label for="price" class="form-label">Price (USD) <span
                                        class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control" id="price" name="price"
                                    required>
                                <small class="text-muted d-block mt-1">Note: An extra $25 will be automatically added to
                                    this price.</small>
                            </div>

                            <!-- Camp Logo -->
                            <div class="col-12">
                                <label for="camp_logo" class="form-label">Camp Logo</label>
                                <input type="file" class="form-control" id="camp_logo" name="camp_logo"
                                    accept="image/*">
                                <div id="current_logo" class="mt-2"></div>
                            </div>

                            <!-- Camp Details -->
                            <div class="col-12">
                                <label for="camp_details" class="form-label">Camp Details</label>
                                <textarea id="camp_details" name="camp_details"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">Save Camp</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Camp Modal -->
    <div class="modal fade" id="viewCampModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewCampModalLabel">Camp Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body" id="viewCampContent">
                    <!-- Content will be loaded dynamically -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <!-- Google Maps API -->
    <script
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyA-9OmmV8vaRrLOOW57zlW9ws4QPUb8S0c&libraries=places&callback=initMap"
        async defer></script>

    <script>
        let map;
        let marker;
        let autocomplete;
        let geocoder;

        // Initialize Google Map
        function initMap() {
            // Default center (Dhaka, Bangladesh)
            const defaultLocation = {
                lat: 23.8103,
                lng: 90.4125
            };

            // Initialize map
            map = new google.maps.Map(document.getElementById('map'), {
                center: defaultLocation,
                zoom: 12,
                mapTypeControl: true,
                streetViewControl: false,
                fullscreenControl: true,
            });

            // Initialize geocoder
            geocoder = new google.maps.Geocoder();

            // Initialize marker
            marker = new google.maps.Marker({
                map: map,
                draggable: true,
                animation: google.maps.Animation.DROP,
            });

            // Initialize autocomplete
            const locationInput = document.getElementById('location');
            autocomplete = new google.maps.places.Autocomplete(locationInput, {
                fields: ['formatted_address', 'geometry', 'name'],
                types: ['geocode', 'establishment']
            });

            // Autocomplete place changed
            autocomplete.addListener('place_changed', function() {
                const place = autocomplete.getPlace();

                if (!place.geometry || !place.geometry.location) {
                    toastr.warning('No details available for this location');
                    return;
                }

                // Set map center and marker
                map.setCenter(place.geometry.location);
                map.setZoom(15);
                marker.setPosition(place.geometry.location);
                marker.setVisible(true);

                // Update fields
                updateLocationFields(
                    place.geometry.location.lat(),
                    place.geometry.location.lng(),
                    place.formatted_address || place.name
                );
            });

            // Map click event
            map.addListener('click', function(event) {
                placeMarker(event.latLng);
                reverseGeocode(event.latLng);
            });

            // Marker drag end event
            marker.addListener('dragend', function(event) {
                reverseGeocode(event.latLng);
            });
        }

        // Place marker on map
        function placeMarker(location) {
            marker.setPosition(location);
            marker.setVisible(true);
            map.panTo(location);

            updateLocationFields(
                location.lat(),
                location.lng(),
                null
            );
        }

        // Reverse geocode to get address
        function reverseGeocode(latLng) {
            geocoder.geocode({
                location: latLng
            }, function(results, status) {
                if (status === 'OK') {
                    if (results[0]) {
                        document.getElementById('location').value = results[0].formatted_address;
                        updateLocationFields(
                            latLng.lat(),
                            latLng.lng(),
                            results[0].formatted_address
                        );
                    }
                } else {
                    console.error('Geocoder failed: ' + status);
                }
            });
        }

        // Update location fields
        function updateLocationFields(lat, lng, address) {
            document.getElementById('latitude').value = lat.toFixed(7);
            document.getElementById('longitude').value = lng.toFixed(7);

            if (address) {
                document.getElementById('location').value = address;
            }

            // Update coordinates display
            document.getElementById('coordinates-display').innerHTML =
                `<strong>Latitude:</strong> ${lat.toFixed(7)}, <strong>Longitude:</strong> ${lng.toFixed(7)}`;
        }

        // Reset map to default or specific location
        function resetMap(lat = null, lng = null, address = '') {
            if (lat && lng) {
                const location = {
                    lat: parseFloat(lat),
                    lng: parseFloat(lng)
                };
                map.setCenter(location);
                map.setZoom(15);
                marker.setPosition(location);
                marker.setVisible(true);

                document.getElementById('location').value = address;
                updateLocationFields(lat, lng, address);
            } else {
                // Reset to default
                const defaultLocation = {
                    lat: 23.8103,
                    lng: 90.4125
                };
                map.setCenter(defaultLocation);
                map.setZoom(12);
                marker.setVisible(false);
                document.getElementById('location').value = '';
                document.getElementById('latitude').value = '';
                document.getElementById('longitude').value = '';
                document.getElementById('coordinates-display').textContent = 'Click on map or search to set location';
            }
        }

        $(document).ready(function() {
            $.ajaxSetup({
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                }
            });

            // Initialize DataTable
            if (!$.fn.DataTable.isDataTable('#datatable')) {
                let dTable = $('#datatable').DataTable({
                    order: [],
                    lengthMenu: [
                        [10, 25, 50, 100, -1],
                        [10, 25, 50, 100, "All"]
                    ],
                    processing: true,
                    serverSide: true,

                    language: {
                        processing: `<div class="text-center">
                        <img src="{{ asset('default/loader.gif') }}" alt="Loader" style="width: 50px;">
                        </div>`
                    },

                    scroller: {
                        loadingIndicator: false
                    },
                    pagingType: "full_numbers",
                    dom: "<'row justify-content-between table-topbar'<'col-md-4 col-sm-3'l><'col-md-5 col-sm-5 px-0'f>>tipr",
                    ajax: {
                        url: "{{ route('admin.camps.index') }}",
                        type: "GET",
                    },

                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'director',
                            name: 'director',
                            orderable: false,
                            searchable: true
                        },
                        {
                            data: 'camp_info',
                            name: 'camp_info',
                            orderable: false,
                            searchable: true
                        },
                        {
                            data: 'sports_type',
                            name: 'sports_type',
                            orderable: false,
                            searchable: true
                        },
                        {
                            data: 'dates',
                            name: 'dates',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'price',
                            name: 'price',
                            orderable: true,
                            searchable: false
                        },
                        {
                            data: 'status',
                            name: 'status',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'action',
                            name: 'action',
                            orderable: false,
                            searchable: false,
                            className: 'dt-center text-center'
                        },
                    ],
                });
            }
        });

        // Initialize Summernote
        $('#camp_details').summernote({
            placeholder: 'Enter camp details...',
            tabsize: 2,
            height: 200,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'underline', 'italic', 'clear']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ]
        });

        // Show Create Modal
        function showCreateModal() {
            $('#campModalLabel').text('Add New Camp');
            $('#campForm')[0].reset();
            $('#camp_id').val('');
            $('#form_method').val('POST');
            $('#current_logo').html('');
            $('#submitBtn').text('Save Camp');

            // Reset Summernote
            $('#camp_details').summernote('reset');

            setTimeout(function() {
                resetMap();
            }, 300);

            $('#campModal').modal('show');
        }

        // Form Submit Handler
        $('#campForm').on('submit', function(e) {
            e.preventDefault();

            // Validate coordinates
            // if (!$('#latitude').val() || !$('#longitude').val()) {
            //     toastr.error('Please select a location on the map');
            //     return;
            // }

            // Sync Summernote content to textarea before serializing
            $('#camp_details').val($('#camp_details').summernote('code'));

            NProgress.start();

            let formData = new FormData(this);
            let campId = $('#camp_id').val();
            let method = $('#form_method').val();
            let url = campId ? "{{ route('admin.camps.update', ':id') }}".replace(':id', campId) :
                "{{ route('admin.camps.store') }}";

            if (method === 'POST') {
                formData.append('_method', 'POST');
            }

            $.ajax({
                type: "POST",
                url: url,
                data: formData,
                processData: false,
                contentType: false,
                success: function(resp) {
                    NProgress.done();
                    if (resp.status === 't-success') {
                        toastr.success(resp.message);
                        $('#campModal').modal('hide');
                        $('#campForm')[0].reset();
                        $('#datatable').DataTable().ajax.reload();
                    } else {
                        toastr.error(resp.message);
                    }
                },
                error: function(error) {
                    NProgress.done();
                    if (error.responseJSON && error.responseJSON.errors) {
                        let errors = error.responseJSON.errors;
                        Object.keys(errors).forEach(function(key) {
                            toastr.error(errors[key][0]);
                        });
                    } else {
                        toastr.error('An error occurred. Please try again.');
                    }
                }
            });
        });

        // Show Edit Modal
        function showEditModal(id) {
            NProgress.start();
            let url = "{{ route('admin.camps.show', ':id') }}";

            $.ajax({
                type: "GET",
                url: url.replace(':id', id),
                success: function(resp) {
                    NProgress.done();
                    if (resp.status === 't-success') {
                        let camp = resp.data;

                        $('#campModalLabel').text('Edit Camp');
                        $('#camp_id').val(camp.id);
                        $('#form_method').val('PUT');
                        $('#director_id').val(camp.director_id);
                        $('#sports_type_id').val(camp.sports_type_id);
                        $('#camp_name').val(camp.camp_name);
                        $('#location').val(camp.location);
                        $('#address').val(camp.address);
                        $('#start_date').val(camp.start_date);
                        $('#end_date').val(camp.end_date);
                        $('#price').val(camp.price);
                        // $('#camp_details').val(camp.camp_details);
                        $('#camp_details').summernote('code', camp.camp_details || '');

                        // Show current logo
                        if (camp.camp_logo) {
                            $('#current_logo').html(
                                '<div class="text-center"><img src="/' + camp.camp_logo +
                                '" alt="Current Logo" class="camp-logo-preview"><p class="text-muted mt-2">Current Logo</p></div>'
                            );
                        } else {
                            $('#current_logo').html('');
                        }

                        $('#submitBtn').text('Update Camp');

                        // Set map location
                        setTimeout(function() {
                            resetMap(camp.latitude, camp.longitude, camp.location);
                        }, 300);

                        $('#campModal').modal('show');
                    }
                },
                error: function(error) {
                    NProgress.done();
                    toastr.error('Failed to load camp data');
                }
            });
        }

        // Show View Modal
        function showViewModal(id) {
            NProgress.start();
            let url = "{{ route('admin.camps.show', ':id') }}";

            $.ajax({
                type: "GET",
                url: url.replace(':id', id),
                success: function(resp) {
                    NProgress.done();
                    if (resp.status === 't-success') {
                        let camp = resp.data;
                        let director = camp.director;

                        // Prepare data
                        let logoHtml = camp.camp_logo ?
                            '<img src="/' + camp.camp_logo + '" alt="Camp Logo" class="camp-logo-preview">' :
                            '<div class="text-center"><i class="fe fe-image fs-2 text-muted"></i><p class="text-muted mt-2">No Logo</p></div>';

                        let directorAvatar = director && director.avatar ?
                            '<img src="/' + director.avatar +
                            '" alt="Director Avatar" class="director-avatar">' :
                            '<div class="text-center"><i class="fe fe-user fs-2 text-muted"></i><p class="text-muted mt-2">No Avatar</p></div>';

                        let directorName = director ? ((director.first_name + ' ' + director.last_name)
                            .trim() || director.username) : 'N/A';

                        let startDate = camp.start_date ? new Date(camp.start_date).toLocaleDateString(
                            'en-US', {
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric'
                            }) : 'N/A';

                        let endDate = camp.end_date ? new Date(camp.end_date).toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        }) : 'N/A';

                        let duration = camp.start_date && camp.end_date ?
                            Math.ceil((new Date(camp.end_date) - new Date(camp.start_date)) / (1000 * 60 * 60 *
                                24)) + 1 : 'N/A';

                        // Generate clean HTML
                        let html = `
                        <div class="camp-details-container">
                            <!-- Camp Information -->
                            <div class="camp-info-card">
                                <h4 class="section-title"><i class="fe fe-map-pin me-2"></i>Camp Information</h4>

                                <div class="text-center mb-4">
                                    ${logoHtml}
                                </div>

                                <div class="info-grid">
                                    <div class="info-item">
                                        <div class="info-label">Camp Name</div>
                                        <div class="info-value">${camp.camp_name || 'N/A'}</div>
                                    </div>

                                    <div class="info-item">
                                        <div class="info-label">Sports Type</div>
                                        <div class="info-value">${camp.sports_type_name || 'N/A'}</div>
                                    </div>

                                    <div class="info-item">
                                        <div class="info-label">Location</div>
                                        <div class="info-value">${camp.location || 'N/A'}</div>
                                    </div>

                                    <div class="info-item">
                                        <div class="info-label">Location</div>
                                        <div class="info-value">${camp.address || 'N/A'}</div>
                                    </div>

                                    <div class="info-item">
                                        <div class="info-label">Price</div>
                                        <div class="info-value">
                                            <strong>$${parseFloat(camp.price || 0).toFixed(2)} USD</strong>
                                        </div>
                                    </div>

                                    <div class="info-item">
                                        <div class="info-label">Status</div>
                                        <div class="info-value">
                                            <span class="badge badge-custom ${camp.status === 'active' ? 'badge-active' : 'badge-inactive'}">
                                                ${camp.status ? camp.status.toUpperCase() : 'N/A'}
                                            </span>
                                        </div>
                                    </div>

                                ${camp.latitude && camp.longitude ? `
                                                            <div class="info-item">
                                                                <div class="info-label">Coordinates</div>
                                                                <div class="info-value">
                                                                    <a href="https://www.google.com/maps?q=${camp.latitude},${camp.longitude}"
                                                                    target="_blank"
                                                                    rel="noopener noreferrer"
                                                                    style="color: #0d6efd; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;">
                                                                        <i class="fe fe-map-pin"></i>
                                                                        ${camp.latitude}, ${camp.longitude}
                                                                        <i class="fe fe-external-link" style="font-size: 0.8rem;"></i>
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        ` : ''}
                                </div>

                                <!-- Duration -->
                                <div class="duration-box">
                                    <div class="info-label">Duration</div>
                                    <div class="mb-2">
                                        <div class="mb-1"><strong>Start:</strong> ${startDate}</div>
                                        <div><strong>End:</strong> ${endDate}</div>
                                    </div>
                                    ${duration !== 'N/A' ? `
                                                                            <div class="duration-days">${duration} Days</div>
                                                                        ` : ''}
                                </div>

                                ${camp.camp_details ? `
                                                                        <div class="info-item mt-3">
                                                                            <div class="info-label">Camp Details</div>
                                                                            <div class="info-value" style="line-height: 1.6;">${camp.camp_details}</div>
                                                                        </div>
                                                                    ` : ''}
                            </div>


                            <div class="director-info-card">
                                ${director ? `
                                                                        <h4 class="section-title"><i class="fe fe-user me-2"></i>Director Information</h4>

                                                                        <div class="text-center mb-4">
                                                                            ${directorAvatar}
                                                                        </div>

                                                                        <div class="info-grid">
                                                                            <div class="info-item">
                                                                                <div class="info-label">Full Name</div>
                                                                                <div class="info-value">${directorName}</div>
                                                                            </div>

                                                                            <div class="info-item">
                                                                                <div class="info-label">Username</div>
                                                                                <div class="info-value">${director.username ? '' + director.username : 'N/A'}</div>
                                                                            </div>

                                                                            <div class="info-item">
                                                                                <div class="info-label">Email Address</div>
                                                                                <div class="info-value">${director.email || 'N/A'}</div>
                                                                            </div>

                                                                            ${director.phone ? `
                                            <div class="info-item">
                                                <div class="info-label">Phone Number</div>
                                                <div class="info-value">${director.phone}</div>
                                            </div>
                                        ` : ''}

                                                                            ${director.address ? `
                                            <div class="info-item">
                                                <div class="info-label">Address</div>
                                                <div class="info-value">${director.address}</div>
                                            </div>
                                        ` : ''}

                                                                            <div class="info-item">
                                                                                <div class="info-label">Account Status</div>
                                                                                <div class="info-value">
                                                                                    <span class="badge badge-custom ${director.status === 'active' ? 'badge-active' : 'badge-inactive'}">
                                                                                        ${director.status ? director.status.toUpperCase() : 'N/A'}
                                                                                    </span>
                                                                                </div>
                                                                            </div>
                                                                        </div>

                                                                        ${director.biography ? `
                                        <div class="info-item mt-3">
                                            <div class="info-label">Biography</div>
                                            <div class="info-value" style="line-height: 1.6;">${director.biography}</div>
                                        </div>
                                    ` : ''}
                                                                    ` : `
                                                                        <div class="alert alert-warning">
                                                                            <i class="fe fe-alert-triangle me-2"></i>Director information not available
                                                                        </div>
                                                                    `}
                            </div>
                        </div>
                    `;

                        $('#viewCampContent').html(html);
                        $('#viewCampModal').modal('show');
                    }
                },
                error: function(error) {
                    NProgress.done();
                    toastr.error('Failed to load camp details');
                }
            });
        }

        // Status Change Confirm Alert
        function showStatusChangeAlert(id) {
            event.preventDefault();

            Swal.fire({
                title: 'Are you sure?',
                text: 'You want to update the camp status?',
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'Yes, Update it!',
                cancelButtonText: 'No, Cancel',
            }).then((result) => {
                if (result.isConfirmed) {
                    statusChange(id);
                }
            });
        }

        // Status Change
        function statusChange(id) {
            NProgress.start();
            let url = "{{ route('admin.camps.status', ':id') }}";
            $.ajax({
                type: "GET",
                url: url.replace(':id', id),
                success: function(resp) {
                    NProgress.done();
                    toastr.success(resp.message);
                    $('#datatable').DataTable().ajax.reload();
                },
                error: function(error) {
                    NProgress.done();
                    toastr.error('Failed to update status');
                }
            });
        }

        // Delete Confirm
        function showDeleteConfirm(id) {
            event.preventDefault();
            Swal.fire({
                title: 'Are you sure?',
                text: 'You want to delete this camp? This action cannot be undone!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, Delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    deleteItem(id);
                }
            });
        }

        // Delete Button
        function deleteItem(id) {
            NProgress.start();
            let url = "{{ route('admin.camps.destroy', ':id') }}";
            let csrfToken = '{{ csrf_token() }}';
            $.ajax({
                type: "DELETE",
                url: url.replace(':id', id),
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                success: function(resp) {
                    NProgress.done();
                    toastr.success(resp.message);
                    $('#datatable').DataTable().ajax.reload();
                },
                error: function(error) {
                    NProgress.done();
                    toastr.error('Failed to delete camp');
                }
            });
        }
    </script>
@endpush

@push('styles')
    <style>
        /* Custom scrollable modal - CAMP CREATE/EDIT */
        #campModal .modal-dialog {
            max-width: 1140px;
            max-height: 90vh;
            margin: 1.75rem auto;
        }

        #campModal .modal-content {
            display: flex;
            flex-direction: column;
            max-height: 90vh;
            height: 90vh;
        }

        #campModal .modal-header {
            flex-shrink: 0;
            padding: 1rem 1.5rem;
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        #campModal .modal-footer {
            flex-shrink: 0;
            padding: 1rem 1.5rem;
            border-top: 1px solid #dee2e6;
            background-color: #f8f9fa;
        }

        /* Modal body scrollable */
        #campModal .modal-body {
            flex: 1 1 auto;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 1.5rem;
            max-height: calc(90vh - 130px);
        }

        /* Form styling */
        #campModal .row.g-3 {
            margin-bottom: 0;
        }

        #campModal .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 0.5rem;
        }

        #campModal .form-control,
        #campModal .form-select {
            border: 1px solid #ced4da;
            border-radius: 6px;
            padding: 0.5rem 0.75rem;
            font-size: 0.95rem;
            width: 100%;
        }

        #campModal .form-control:focus,
        #campModal .form-select:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

        /* Google Maps Autocomplete Styling */
        .pac-container {
            z-index: 10000 !important;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            margin-top: 4px;
            font-family: inherit;
        }

        .pac-item {
            padding: 10px;
            cursor: pointer;
            border-bottom: 1px solid #e9ecef;
        }

        .pac-item:hover {
            background-color: #f8f9fa;
        }

        .pac-icon {
            margin-top: 6px;
        }

        /* Map Container */
        #map-container {
            margin-bottom: 1rem;
        }

        #map {
            border-radius: 8px;
        }

        /* Coordinates Display */
        #coordinates-display {
            display: inline-block;
            font-size: 0.875rem;
        }

        /* Textarea specific styling */
        #camp_details {
            min-height: 120px;
            resize: vertical;
            max-height: 200px;
        }

        /* Current logo preview */
        #current_logo img {
            max-width: 120px;
            max-height: 120px;
            object-fit: contain;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 5px;
            background-color: #f8f9fa;
        }

        /* Custom scrollbar */
        #campModal .modal-body::-webkit-scrollbar {
            width: 8px;
        }

        #campModal .modal-body::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        #campModal .modal-body::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        #campModal .modal-body::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Responsive */
        @media (max-width: 992px) {
            #campModal .modal-dialog {
                margin: 0.5rem;
                max-width: 95%;
            }

            #map-container {
                height: 300px !important;
            }
        }

        @media (max-width: 768px) {
            #campModal .modal-body {
                padding: 1rem;
            }

            #map-container {
                height: 250px !important;
            }
        }
    </style>
@endpush

@push('styles')
    <style>
        /* View Camp Modal - SCROLLABLE FIXED */
        #viewCampModal .modal-dialog {
            max-width: 900px;
            height: 90vh;
            max-height: 90vh;
            margin: 1.75rem auto;
        }

        #viewCampModal .modal-content {
            height: 100%;
            display: flex;
            flex-direction: column;
            border-radius: 10px;
            overflow: hidden;
        }

        #viewCampModal .modal-header {
            flex-shrink: 0;
            background-color: #fff;
            border-bottom: 1px solid #dee2e6;
            padding: 1rem 1.5rem;
        }

        #viewCampModal .modal-title {
            font-weight: 600;
            color: #2c3e50;
            font-size: 1.25rem;
        }

        /* FIXED: Modal body scrollable */
        #viewCampModal .modal-body {
            flex: 1 1 auto;
            overflow-y: auto !important;
            overflow-x: hidden;
            padding: 0;
            background-color: #f8f9fa;
        }

        #viewCampModal .modal-footer {
            flex-shrink: 0;
            background-color: #fff;
            border-top: 1px solid #dee2e6;
            padding: 1rem 1.5rem;
        }

        /* Content Container */
        .camp-details-container {
            padding: 1.5rem;
        }

        /* Cards */
        .camp-info-card,
        .director-info-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border: 1px solid #e9ecef;
        }

        /* Section Title */
        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #e9ecef;
        }

        /* Info Items */
        .info-item {
            margin-bottom: 1rem;
        }

        .info-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }

        .info-value {
            font-size: 1rem;
            color: #2c3e50;
            font-weight: 500;
        }

        /* Images */
        .camp-logo-preview {
            max-width: 150px;
            max-height: 150px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            padding: 5px;
            background: white;
            display: block;
            margin: 0 auto 1.5rem auto;
            object-fit: contain;
        }

        .director-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 3px solid #dee2e6;
            object-fit: cover;
            display: block;
            margin: 0 auto 1.5rem auto;
            background: white;
        }

        /* Badges */
        .badge-custom {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .badge-active {
            background-color: #28a745;
            color: white;
        }

        .badge-inactive {
            background-color: #6c757d;
            color: white;
        }

        /* Grid Layout */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        /* Duration Box */
        .duration-box {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
            text-align: center;
            border: 1px solid #e9ecef;
        }

        .duration-days {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-top: 0.5rem;
        }

        /* Custom Scrollbar for View Modal */
        #viewCampModal .modal-body::-webkit-scrollbar {
            width: 8px;
        }

        #viewCampModal .modal-body::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        #viewCampModal .modal-body::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        #viewCampModal .modal-body::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        /* Responsive */
        @media (max-width: 768px) {
            #viewCampModal .modal-dialog {
                margin: 0.5rem;
                height: 95vh;
                max-height: 95vh;
            }

            .camp-details-container {
                padding: 1rem;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .camp-info-card,
            .director-info-card {
                padding: 1.25rem;
            }
        }

        /* Fix Summernote z-index inside Bootstrap modal */
        .modal .note-editor.note-frame {
            border: 1px solid #ced4da;
            border-radius: 6px;
        }

        .note-popover,
        .note-editor .note-toolbar {
            z-index: 1060 !important;
        }

        .note-dropdown-menu {
            z-index: 1070 !important;
        }
    </style>
@endpush
