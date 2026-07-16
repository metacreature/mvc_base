
function clickGenPassword(e, popuptitle, popuptext, popupbutton) {
    e.preventDefault();
  let pass = generatePassword(18);
  $('input#password').val(pass);
  $('input#password_confirmation').val(pass);
  let copy = copyTextToClipboard(pass);
  
  let text = copy ? popuptext : pass;
  
  $.alert({
        title: popuptitle,
        content: popuptext,
        escapeKey: 'cancel',
        backgroundDismiss: true,
        onOpenBefore: function () {
            $('body').addClass('blockkeyactions');
        },
        onClose: function () {
            $('body').removeClass('blockkeyactions');
        },
        buttons: {
            cancel: {
                text: popupbutton
            }
        }
    });
    
}

$(document).ready(function() {
    $(".line-password .input-group-text").on('click', function(event) {
        event.preventDefault();
        if($(this).data("show") == "yes") {
            $(this).data("show", "no");
            $(this).find("svg use").attr("href", "/static/node_modules/bootstrap-icons/bootstrap-icons.svg#eye-slash");
            $(this).parent().find(".fieldpassword").attr("type", "password");
        }else{
            $(this).data("show", "yes");
            $(this).find("svg use").attr("href", "/static/node_modules/bootstrap-icons/bootstrap-icons.svg#eye-fill");
            $(this).parent().find(".fieldpassword").attr("type", "text");
        }
    });
});
