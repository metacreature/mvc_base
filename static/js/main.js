
function clickGenPassword(popuptitle, popuptext, popupbutton) {
  let pass = generatePassword(30);
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
            //$('body').addClass('blockkeyactions');
        },
        onClose: function () {
            //$('body').removeClass('blockkeyactions');
        },
        buttons: {
            cancel: {
                text: popupbutton
            }
        }
    });
}
