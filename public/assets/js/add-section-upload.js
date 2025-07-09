(function(){
    function insertAtCursor(myField, myValue) {
        if (myField.selectionStart || myField.selectionStart === 0) {
            var startPos = myField.selectionStart;
            var endPos = myField.selectionEnd;
            var restoreTop = myField.scrollTop;
            myField.value = myField.value.substring(0, startPos) + myValue + myField.value.substring(endPos);
            myField.focus();
            myField.scrollTop = restoreTop;
            myField.selectionStart = startPos + myValue.length;
            myField.selectionEnd = startPos + myValue.length;
        } else {
            myField.value += myValue;
            myField.focus();
        }
    }

    $(function(){
        var $input = $('#frm-image');
        var $textarea = $('#frm-markdown');
        if (!$input.length || !$textarea.length) {
            return;
        }
        $input.on('change', function(){
            if (!this.files || !this.files.length) {
                return;
            }
            var fd = new FormData();
            fd.append('image', this.files[0]);
            $.ajax({
                url: '/page/upload-image',
                type: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                dataType: 'json'
            }).done(function(res){
                if (res.path) {
                    insertAtCursor($textarea[0], '\n![](' + res.path + ')');
                }
            }).always(function(){
                $input.val('');
            });
        });
    });
})();
