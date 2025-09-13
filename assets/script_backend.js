/*assets/script_backend.js*/
/*
jQuery(document).ready(function($) {
    var optionIndex = $('.dq-option-container').length; // Count existing options to ensure correct indexing

    // 🟢 1. Add a new main option when clicking "Add Option"
    $('#add-option').click(function(event) {
        event.preventDefault(); // Prevent multiple executions on click

        optionIndex++; // Increment index to ensure unique values

        var optionGroup = `
        <tr class="dq-option-group">
            <td colspan="5">
                <div class="dq-option-container">
                    <table class="dq-option-table">
                        <tr class="dq-option-row">
                            <td><input type="text" name="dq_product_options[${optionIndex}][name]" required></td>
                            <td>
                                <select name="dq_product_options[${optionIndex}][type]">
                                    <option value="checkbox">Checkbox</option>
                                    <option value="radio">Radio</option>
                                </select>
                            </td>
                            <td><input type="number" name="dq_product_options[${optionIndex}][max_addons]" value="1" min="1"></td>
                            <td><input type="checkbox" name="dq_product_options[${optionIndex}][required]" value="1"></td>
                            <td><button type="button" class="button remove-option">×</button></td>
                        </tr>
                    </table>
                    
                    <table class="dq-sub-options-table">
                        <thead>
                            <tr>
                                <th>Add-on Name</th>
                                <th>Price</th>
                                <th>Max Qty</th>
                                <th>Remove</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Add-ons will be added here -->
                        </tbody>
                    </table>

                    <button type="button" class="button add-addon" data-option-index="${optionIndex}">Add Add-on</button>
                </div>
            </td>
        </tr>`;

        // Append the new option group to the table body
        $('#dq-product-options-table tbody').append(optionGroup);
    });

    // 🟢 2. Add a new Add-on inside the correct main option
    $(document).on('click', '.add-addon', function() {
        var optionIdx = $(this).data('option-index'); // Get the correct main option index

        var addonRow = `
        <tr>
            <td><input type="text" name="dq_product_options[${optionIdx}][addons][][name]" required></td>
            <td><input type="number" name="dq_product_options[${optionIdx}][addons][][price]" step="0.01" min="0"></td>
            <td><input type="number" name="dq_product_options[${optionIdx}][addons][][max_qty]" value="1" min="1"></td>
            <td><button type="button" class="button remove-addon">×</button></td>
        </tr>`;

        // Append the Add-on inside the correct main option
        $(this).closest('.dq-option-container').find('.dq-sub-options-table tbody').append(addonRow);
    });

    // 🟢 3. Remove an entire main option
    $(document).on('click', '.remove-option', function() {
        $(this).closest('.dq-option-group').remove(); // Remove the entire option group
    });

    // 🟢 4. Remove a specific Add-on inside the correct option
    $(document).on('click', '.remove-addon', function() {
        $(this).closest('tr').remove(); // Remove only the specific Add-on row
    });
});  
*/


jQuery(document).ready(function($) {
    // Add New Addon
    $('#add-addon').on('click', function() {
        var addonIndex = Date.now();
        var newAddon  = '<tr class="cpa-addon-box">' +
            '<td><input type="text" name="custom_product_addons[' + addonIndex + '][title]" placeholder="Addon Title" required></td>' +
            '<td><select name="custom_product_addons[' + addonIndex + '][type]">' +
                '<option value="single">Single Choice</option>' +
                '<option value="multiple">Multiple Choices</option>' +
            '</select></td>' +
            '<td><input type="number" name="custom_product_addons[' + addonIndex + '][max_options]" min="1" placeholder="Max Options"></td>' +
            '<td><input type="checkbox" name="custom_product_addons[' + addonIndex + '][required]"></td>' +
            '<td><button type="button" class="button remove-addon">Delete</button></td>' +
        '</tr>' +
        '<tr>' +
            '<td colspan="5">' +
                '<table class="widefat cpa-suboptions-table">' +
                    '<thead>' +
                        '<tr>' +
                            '<th>Option Name</th>' +
                            '<th>Quantity</th>' +
                            '<th>Price</th>' +
                            '<th>Action</th>' +
                        '</tr>' +
                    '</thead>' +
                    '<tbody class="cpa-sub-options"></tbody>' +
                '</table>' +
                '<button type="button" class="button button-secondary add-sub-option">Add Sub Option</button>' +
            '</td>' +
        '</tr>';
        $('#cpa-addons-container').append(newAddon);
    });

    // Add Sub Option
    $(document).on('click', '.add-sub-option', function() {
        var addonRow = $(this).closest('tr').prev('.cpa-addon-box');
        var nameAttr  = addonRow.find('input[type="text"]').attr('name');
        var matches   = nameAttr.match(/\[(\d+)\]/);
        var addonIndex = matches ? matches[1] : Date.now();
        var optionIndex = Date.now();
        var newSubOption = '<tr class="cpa-sub-option">' +
            '<td><input type="text" name="custom_product_addons[' + addonIndex + '][options][' + optionIndex + '][label]" placeholder="Option Name" required></td>' +
            '<td><input type="number" name="custom_product_addons[' + addonIndex + '][options][' + optionIndex + '][quantity]" min="1" placeholder="Quantity"></td>' +
            '<td><input type="number" step="0.01" name="custom_product_addons[' + addonIndex + '][options][' + optionIndex + '][price]" min="0" placeholder="Price"></td>' +
            '<td><button type="button" class="button remove-sub-option">Delete</button></td>' +
        '</tr>';
        $(this).siblings('table').find('.cpa-sub-options').append(newSubOption);
    });

    // Remove Addon (both main row and sub-options row)
    $(document).on('click', '.remove-addon', function() {
        var row = $(this).closest('tr');
        var nextRow = row.next('tr');
        row.remove();
        nextRow.remove();
    });

    // Remove Sub Option
    $(document).on('click', '.remove-sub-option', function() {
        $(this).closest('tr').remove();
    });
});



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
