jQuery(document).ready(function($){
    // Toggle between All Days and Custom mode.
    $('#operating_mode').on('change', function(){
        var mode = $(this).val();
        if(mode == 'all'){
            $('#all_days_hours').show();
            $('#custom_days_hours').hide();
        } else {
            $('#all_days_hours').hide();
            $('#custom_days_hours').show();
        }
    });
    // For All Days: Add new shift.
    $('.add-shift-all').on('click', function(){
        var container = $('#all_days_hours').find('.shifts-container');
        var index = container.find('.shift').length;
        var shiftHtml = '<div class="shift">' +
                        '<input type="time" name="branch_hours[all]['+index+'][start]" value="" />' +
                        '<input type="time" name="branch_hours[all]['+index+'][end]" value="" />' +
                        '<button type="button" class="remove-shift">Remove</button>' +
                        '</div>';
        container.append(shiftHtml);
    });
    // For Custom mode: Add new shift for a specific day.
    $('.add-shift').on('click', function(){
        var day = $(this).data('day');
        var container = $(this).siblings('.shifts-container');
        var index = container.find('.shift').length;
        var shiftHtml = '<div class="shift">' +
                        '<input type="time" name="branch_hours['+day+'][shifts]['+index+'][start]" value="" />' +
                        '<input type="time" name="branch_hours['+day+'][shifts]['+index+'][end]" value="" />' +
                        '<button type="button" class="remove-shift">Remove</button>' +
                        '</div>';
        container.append(shiftHtml);
    });
    // Remove shift.
    $(document).on('click', '.remove-shift', function(){
        $(this).closest('.shift').remove();
    });
    // Show the Get Coordinates modal when button is clicked.
    $('#get-coordinates-btn').on('click', function(){
        $('#get-coordinates-modal').fadeIn();
    });
    // Close modal.
    $('#close-modal-btn').on('click', function(){
        $('#get-coordinates-modal').fadeOut();
    });
    // Fetch coordinates when "Get" is clicked in the modal.
    $('#fetch-coordinates-btn').on('click', function(){
        var url = $('#branch-url-input').val();
        if(url === ''){
            alert('Please enter a URL.');
            return;
        }
        // Example: Simulate an API call to fetch coordinates.
        // Replace this simulation with an actual API call if needed.
        var latitude = "25.276987";
        var longitude = "55.296249";
        // Set the values in the latitude and longitude fields.
        $('#branch_latitude').val(latitude);
        $('#branch_longitude').val(longitude);
        // Hide the modal.
        $('#get-coordinates-modal').fadeOut();
    });
});
