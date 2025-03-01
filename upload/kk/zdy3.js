//===tikz支持 + asy
function close_tikz_window(){
        var tikz_window=document.getElementById('tikz_window');
        if(tikz_window){
                tikz_window.remove();
        }
}
function show_tikz_window(tikz_code){
        close_tikz_window();
        var tikz_window=document.createElement('div');
        tikz_window.id='tikz_window';
        tikz_window.className='tikzww';
        tikz_window.innerHTML=`<div onmousedown="tuozhuai(this.parentNode);return false;" style="width:100%;height:26px;cursor:move;">
            <a href="javascript:close_tikz_window();" class="flbc" style="float:right;margin:3px 6px 0 0;">关闭</a></div>
            <div><textarea class="tikzta">`+decodeURIComponent(tikz_code)+'</textarea></div>';
        document.body.append(tikz_window);
}

//这里原先的 tikz 部分已改写为 php 实现

let asys = document.getElementsByTagName('asy');
for (let item of asys) {
    //var str = item.innerHTML.replace(/<br>/g, "").replace(/&nbsp;/g,' ').replace(/&lt;/g, "<").replace(/&gt;/g, ">").replace(/&amp;/g, "&");
    item.innerHTML = item.innerHTML.replace(/&nbsp;/g,' ');
    var str = item.textContent;
    var str_for_show = encodeURI(str).replace(/\'/g,'’');
    var str_for_link = encodeURIComponent(str);
    item.innerHTML = '<div class="jiaz"></div><img src="/asy/?format=svg&code='+str_for_link+'" onclick="show_tikz_window(\''+str_for_show+'\');" onload="this.parentNode.classList.add(\'jiazed\')" />';
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
var posts=document.querySelectorAll('.t_f,.postmessage,.message');//.getElementsByClassName('t_f');
for(var i = 0; i < posts.length; i++){
        var post = posts[i];
        var html = post.innerHTML;
        html = html
        .replace(/<br>\n/g,'<br>')
            //解决mathjax3复制多行代码多余空行
        .replace(/(\\\]|\\end\{align\*?\}|\\end\{gather\*?\}|\\end\{equation\*?\}|\$\$)( |&nbsp;)*<br>/g,'$1')
            //去行间公式后的1个br
        //.replace(/([\u4E00-\u9FA5])([A-Za-z0-9\$])/g,'$1 $2')
        //.replace(/([A-Za-z0-9\$,\.])([\u4E00-\u9FA5])/g,'$1 $2')
            //中文与公式、英文、数字间加空格
        .replace(/ 编辑 <\/i><br>(<br>)?/g,' 编辑 </i>')
        .replace(/<\/blockquote><\/div><br>\n*(<br>)?/g,'</blockquote></div>')
        .replace(/复制代码<\/em><\/div><br>/g,'复制代码</em></div>')
            //去编辑痕迹、引用后的1-2个br，代码块后的1个br
        //.replace(/<br><br><br>/g,'<br><br>')
            //减少1/3的br
        ;
        post.innerHTML = html;
}
