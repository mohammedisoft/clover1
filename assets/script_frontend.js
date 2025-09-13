jQuery(function($){
  // 1) Show the modal on page load only if no branch in session AND no 'branch_selected' flag in localStorage
  if ( ! dq_vars.current_branch_id && ! localStorage.getItem('branch_selected') ) {
    $('#dq-branch-selector-modal').fadeIn();
  }

  // 2) Close button in the modal
  $('#branch-selector-close').on('click', function(){
    $('#dq-branch-selector-modal').fadeOut();
  });

  // 3) Confirm modal selection: AJAX → set session, set localStorage, then reload
  $('#branch-selector-confirm').on('click', function(){
    var branchId = $('#branch-selector').val();
    if ( ! branchId ) {
      alert('Please select a branch.');
      return;
    }
    $.post(dq_vars.ajax_url, {
      action:    'set_branch',
      branch_id: branchId
    }).done(function(){
      localStorage.setItem('branch_selected', '1');
      $('#dq-branch-selector-modal').fadeOut(function(){
        location.reload();
      });
    }).fail(function(){
      alert('Could not set branch. Try again.');
    });
  });

  // 4) When the checkout branch field changes, save session and reload to recalc taxes
  $('form.checkout').on('change', 'select#branch-selector', function(){
    var branchId = $(this).val();
    if ( ! branchId ) {
      return;
    }
    $.post(dq_vars.ajax_url, {
      action:    'set_branch',
      branch_id: branchId
    }).always(function(){
      location.reload();
    });
  });
});
