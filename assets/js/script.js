jQuery(document).ready(function($) {
    $('#payPingDonate_Amount').on('input', function(event) {
      let inputVal = $(this).val().replace(/\D/g, ''); // Remove non-digit characters
  
      // Add commas as a separator every three digits from the right
      inputVal = inputVal.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  
      // Update the input value with the formatted number
      $(this).val(inputVal);
    });
  });


  jQuery(document).ready(function($) {
    $('#payping-donate-form').on('submit', function(e) {
        e.preventDefault();
        $('.payping-loader').show();
        $('.payPingDonate_Submit').css('pointer-events', 'none');
        // Clear previous messages
        $('#payPingDonate_Message').hide().text('');
        $('#payPingDonate_Error').hide().text('');
        var returnPageUrl = window.location.href;
        // Gather form data
        var formData = {
            action: 'process_donation',
            payPingDonate_Amount: $('#payPingDonate_Amount').val(),
            payPingDonate_Name: $('input[name="payPingDonate_Name"]').val(),
            mobile: $('input[name="mobile"]').val(),
            email: $('input[name="email"]').val(),
            payPingDonate_Description: $('.payping_description_input').val(),
            page_url: returnPageUrl
        };
        // Send AJAX request
        $.ajax({
            url: payping_ajax.ajax_url,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Redirect to PayPing payment gateway
                    window.location.href = response.data;
                } else {
                    var errorElement = $('<div id="payPingDonate_Error">' + response.data +'</div>');
                    $('#payPingDonate_Form').prepend(errorElement);
                }
            },
            error: function(xhr, status, error) {
                var errorElement = $('<div id="payPingDonate_Error">خطایی رخ داده است. لطفاً دوباره تلاش کنید.</div>');
                $('#payPingDonate_Form').prepend(errorElement);
            },
            complete: function() {
                $('.payPingDonate_Submit').css('pointer-events', 'auto');
                $('.payping-loader').hide();
            }
        });
    });
});




  
