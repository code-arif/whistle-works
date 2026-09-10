
<script>
    $('.summernote').summernote({
        minHeight: 800,
        maxHeight: 800,
        focus: false,
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'italic', 'underline', 'clear']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['insert', ['link', 'picture', 'video']],
            ['view', ['fullscreen', 'codeview']],
        ]
    });
</script>



<script>
    const form = document.querySelector('.form');
    const submitBtn = document.querySelector('.submit');

    form.addEventListener('submit', function() {
        submitBtn.disabled = true;
        submitBtn.innerText = 'Submitting...';
    });
</script>
<?php /**PATH /home/whistleworks-admin/htdocs/admin.whistleworks.org/resources/views/backend/partials/_custom-script.blade.php ENDPATH**/ ?>