$(document).ready(function() {
 $("#login").submit(function (e) {
  e.preventDefault();
  var formdata = new FormData($('#login')[0]);
  $.ajax({
   url: 'login.php',
   type: 'POST',
   data: formdata,
   contentType: false,
   processData: false
  }).done(function (response) {
   var result = jQuery.parseJSON(response);
   if (result.error != 0) {
    $('#error').show();
    $('#error-message').text(result.message);
   } else window.location.href = './';
  });
 });
});
