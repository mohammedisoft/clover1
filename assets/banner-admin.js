jQuery(document).ready(function($){

    // Function to generate options for the product select element.
    function generateProductOptions() {
        var options = '<option value="">Select Product</option>';
        if (dqBannerData && dqBannerData.products) {
            $.each(dqBannerData.products, function(index, product) {
                options += '<option value="' + product.id + '">' + product.title + '</option>';
            });
        }
        return options;
    }

    // Add new banner item when the "Add Banner Image" button is clicked.
    $('#add-banner-image').on('click', function(e) {
        e.preventDefault();
        var index = $('#banner-images-container .banner-image-row').length;
        var newItem = `
            <div class="banner-image-row">
                <input type="hidden" name="banner_images[${index}][image_id]" class="banner-image-id" value="" />
                <div class="banner-image-preview"></div>
                <button type="button" class="button dq-upload-banner-image">Upload Image</button>
                <select name="banner_images[${index}][product_id]" class="banner-product-select">
                    ${dqBannerData.productOptions}
                </select>
                <button type="button" class="button dq-remove-banner-image">Remove</button>
            </div>
        `;
        $('#banner-images-container').append(newItem);
    });

    // Remove banner item.
    $(document).on('click', '.dq-remove-banner-image', function(e) {
        e.preventDefault();
        $(this).closest('.banner-image-row').remove();
    });

    // Handle image upload using WordPress media uploader.
    $(document).on('click', '.dq-upload-banner-image', function(e) {
        e.preventDefault();
        var button = $(this);
        var container = button.closest('.banner-image-row');
        var previewContainer = container.find('.banner-image-preview');
        var hiddenInput = container.find('.banner-image-id');

        var mediaFrame = wp.media({
            title: 'Select Banner Image',
            button: { text: 'Use this image' },
            multiple: false
        });

        mediaFrame.on('select', function() {
            var attachment = mediaFrame.state().get('selection').first().toJSON();
            var width = attachment.width || 0;
            var height = attachment.height || 0;
            var recommendedWidth = dqBannerData.recommendedWidth;
            var recommendedHeight = dqBannerData.recommendedHeight;

            // If image dimensions exceed recommended, open cropping modal.
            if ( width > recommendedWidth || height > recommendedHeight ) {
                var modalHtml = `
                    <div id="cropModal" style="position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.8); display:flex; align-items: center; justify-content: center; z-index:9999;">
                        <div style="background: #fff; padding:20px; max-width:90%; max-height:90%; overflow:auto;">
                            <h2>Crop Banner Image</h2>
                            <img id="cropImage" src="${attachment.url}" style="max-width:100%; display:block;" />
                            <br>
                            <button id="cropConfirm" class="button">Crop</button>
                            <button id="cropCancel" class="button">Cancel</button>
                        </div>
                    </div>
                `;
                $('body').append(modalHtml);

                var cropper;
                var image = document.getElementById('cropImage');
                $(image).on('load', function() {
                    cropper = new Cropper(image, {
                        aspectRatio: recommendedWidth / recommendedHeight,
                        viewMode: 1,
                        autoCropArea: 1
                    });
                });

                $('#cropConfirm').on('click', function() {
                    if (cropper) {
                        var croppedCanvas = cropper.getCroppedCanvas({
                            width: recommendedWidth,
                            height: recommendedHeight
                        });
                        var croppedDataUrl = croppedCanvas.toDataURL('image/jpeg');
                        $.ajax({
                            url: dqBannerData.ajaxUrl,
                            type: 'POST',
                            data: {
                                action: 'dq_save_cropped_banner_image',
                                image_data: croppedDataUrl
                            },
                            success: function(response) {
                                if(response.success) {
                                    var imageUrl = response.data.url;
                                    hiddenInput.val(imageUrl);
                                    previewContainer.html('<img src="' + imageUrl + '" style="max-width:100%; height:auto;" />');
                                } else {
                                    alert('Error saving image: ' + response.data);
                                }
                                cropper.destroy();
                                $('#cropModal').remove();
                            },
                            error: function() {
                                alert('AJAX error while saving image.');
                                cropper.destroy();
                                $('#cropModal').remove();
                            }
                        });
                    }
                });

                $('#cropCancel').on('click', function() {
                    if (cropper) {
                        cropper.destroy();
                    }
                    $('#cropModal').remove();
                });
            } else {
                hiddenInput.val(attachment.url);
                previewContainer.html('<img src="' + attachment.url + '" style="max-width:100%; height:auto;" />');
            }
        });

        mediaFrame.open();
    });
});
