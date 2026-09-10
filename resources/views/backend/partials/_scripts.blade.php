<!-- BACK-TO-TOP -->
<a href="#top" id="back-to-top"><i class="fa fa-long-arrow-up"></i></a>


<!-- JQUERY JS -->
<script src="{{ asset('backend/plugins/jquery/jquery.min.js') }}"></script>

<!-- BOOTSTRAP JS -->
<script src="{{ asset('backend/plugins/bootstrap/js/popper.min.js') }}"></script>
<script src="{{ asset('backend/plugins/bootstrap/js/bootstrap.min.js') }}"></script>

<!-- SIDE-MENU JS -->
<script src="{{ asset('backend/plugins/sidemenu/sidemenu.js') }}"></script>

<!-- Perfect SCROLLBAR JS-->
<script src="{{ asset('backend/plugins/p-scroll/perfect-scrollbar.js') }}"></script>
<!-- <script src="{{ asset('backend/plugins/p-scroll/pscroll.js') }}"></script> -->

<!-- STICKY JS -->
<script src="{{ asset('backend/js/sticky.js') }}"></script>


<!-- INTERNAL SELECT2 JS -->
<script src="{{ asset('backend/plugins/select2/select2.full.min.js') }}"></script>

<!-- INDEX JS -->
<script src="{{ asset('backend/js/index1.js') }}"></script>
<script src="{{ asset('backend/js/index.js') }}"></script>

<!-- Reply JS-->
<script src="{{ asset('backend/js/reply.js') }}"></script>


<!-- COLOR THEME JS -->
<script src="{{ asset('backend/js/themeColors.js') }}"></script>

<!-- CUSTOM JS -->
<script src="{{ asset('backend/js/custom.js') }}"></script>

<!-- SWITCHER JS -->
<script src="{{ asset('backend/switcher/js/switcher.js') }}"></script>

{{-- toaster js --}}
<script src="{{ asset('backend/js/toastr.min.js') }}"></script>


{{-- SweetAlert2 JS --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

{{-- dropify --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/Dropify/0.2.2/js/dropify.min.js" integrity="sha512-8QFTrG0oeOiyWo/VM9Y8kgxdlCryqhIxVeRpWSezdRRAvarxVtwLnGroJgnVW9/XBRduxO/z1GblzPrMQoeuew==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
    $('.dropify').dropify();
</script>



<!-- loader -->
<script src="{{ asset('default') }}/nprogress/nprogress.js"></script>


<!-- Toster -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>


<script>
    toastr.options = {
        "closeButton": true,
        "debug": false,
        "newestOnTop": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "preventDuplicates": true,
        "onclick": null,
        "showDuration": 300,
        "hideDuration": 300,
        "timeOut": 5000,
        "extendedTimeOut": 1000,
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    };
</script>

@include('backend.partials._toster')

@include('backend.partials._ajax')

@include('backend.partials._notification')

@include('backend.partials._custom-script')

@stack('scripts')
@stack('page-scripts')
