function toggleAll(element)
{
    var form = document.forms.artseld_openinviter_invite_form, z = 0;
    for ( z = 0; z < form.length; z++ ) {
        if (form[z].type == 'checkbox') {
            form[z].checked = element.checked;
        }
    }
}
