//===支持tikz + asymptote
function show_tikz_window(code){
    document.getElementById('tikz_window')?.remove();
    var tikz_window=document.createElement('div');
    tikz_window.id='tikz_window';
    tikz_window.className='tikzww';
    tikz_window.innerHTML='<div onmousedown="tuozhuai(this.parentNode);return false;" style="width:100%;height:26px;cursor:move;"><a href="javascript:document.getElementById(\'tikz_window\')?.remove();" class="flbc" style="float:right;margin:3px 6px 0 0;"></a></div><div><textarea class="tikzta"></textarea></div>';
    code=code.replace(/\u00a0/g,' ');
    tikz_window.querySelector('textarea').value=code;
    document.body.append(tikz_window);
}
//===Html模式下用bbr免打br
var bbrs=document.getElementsByTagName('bbr');
for (let item of bbrs) {
    item.innerHTML = item.innerHTML.replace(/\r\n/g, "<br />").replace(/\n/g, "<br />").replace(/\r/g, "<br />");
}

//===去br等
var blockcodes=document.getElementsByClassName('blockcode');
for (let item of blockcodes) {
    item.innerHTML = item.innerHTML.replace(/<\/li>/g, "\n</li>")//item.innerHTML.replace(/<br>/g, "");
    //在php那里去掉\r后没了<br>但复制代码就没了换行，加回去//代码块去除br
}
document.querySelectorAll('.t_f,.postmessage,.message').forEach(post => {
    post.querySelectorAll('br').forEach(br => {
        //解决mathjax3复制多行代码多余空行
        if (br.nextSibling && br.nextSibling.nodeType === Node.TEXT_NODE) {
            br.nextSibling.nodeValue = br.nextSibling.nodeValue.replace(/^\n/, '');
        }
    });
});
