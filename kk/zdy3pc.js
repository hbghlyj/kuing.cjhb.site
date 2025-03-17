
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
function tuozhuai2(ee) {
    if (!ee.classList.contains('tuoing')) {
        ee.style.width = ee.getBoundingClientRect().width + "px";
        ee.style.top = offSet(ee).top - Math.max(document.documentElement.scrollTop, document.body.scrollTop) + "px";
        ee.style.left = offSet(ee).left - Math.max(document.documentElement.scrollLeft, document.body.scrollLeft) + "px";
        ee.classList.add('tuoing');
    }
    tuozhuai(ee)//tuozhuai 是在 emoji.js 里定义的
}
function guiwei(ee) {
    ee.classList.remove('tuoing');
    ee.style.left=0;
    ee.style.top=0;
    ee.style.width='unset';
}

//这里原先的 tikz 部分已改写为 php 实现

let asys = document.getElementsByTagName('asy');
for (let item of asys) {
    //var str = item.innerHTML.replace(/<br>/g, "").replace(/&nbsp;/g,' ').replace(/&lt;/g, "<").replace(/&gt;/g, ">").replace(/&amp;/g, "&");
    item.innerHTML = item.innerHTML.replace(/&nbsp;/g,' ');
    var str = item.textContent;
    var str_for_show = encodeURI(str).replace(/\'/g,'’');
    var str_for_link = encodeURIComponent(str);
    item.innerHTML = '<div class="jiaz"></div><div class="tuozt" onmousedown="tuozhuai2(this.parentNode);return false;"><!--拖动--></div><div class="guiw" onclick="guiwei(this.parentNode);return false;"><!--归位--></div><img src="/asy/?format=svg&code='+str_for_link+'" onclick="show_tikz_window(\''+str_for_show+'\');" onload="this.parentNode.classList.add(\'jiazed\')" />';
}

//===Html模式下用bbr免打br
var bbrs=document.getElementsByTagName('bbr');
for (let item of bbrs) {
    item.innerHTML = item.innerHTML.replace(/\r\n/g, "<br />").replace(/\n/g, "<br />").replace(/\r/g, "<br />");
}

//===去br等 + 代码显示
var blockcodes=document.getElementsByClassName('blockcode');
for (let item of blockcodes) {
    item.innerHTML = item.innerHTML.replace(/<\/li>/g, "\n</li>")//item.innerHTML.replace(/<br>/g, "");
    //在php那里去掉\r后没了<br>但复制代码就没了换行，加回去//代码块去除br
}
var posts=document.getElementsByClassName('t_f');//.querySelectorAll('.t_f,.postmessage,.message');//兼容手机时的
var post_codes = [];
for (let item of posts) {
    post_codes.push(item.innerHTML);
    item.innerHTML = item.innerHTML
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
}
var plcpi = document.querySelectorAll('.plc .pi');
for (var i=0;i<plcpi.length;i++) {
    var eye = document.createElement("a");
    eye.href = `javascript:show_post_code(${i});`;
    eye.style = "float:right;margin-left:5px;";
    eye.innerHTML = "&#x1f441;";
    eye.title = "显示公式代码";
    eye.classList.add('printhides');
    plcpi[i].insertBefore(eye,plcpi[i].childNodes[0]);
}
function show_post_code(n){
    var tmphtml = posts[n].innerHTML;
    posts[n].innerHTML = post_codes[n];
    post_codes[n] = tmphtml;
}


//===Shift + 鼠标滚轮缩放图片、点击图片切换原始大小
function bbimg(e){
    if(!e.shiftKey) return;
    let scale = e.deltaY>0 ? 0.9 : 1.11,
        temp_w=parseFloat(this.getBoundingClientRect().width),
        temp_h=parseFloat(this.getBoundingClientRect().height);
    this.classList.remove('mw100');
    this.setAttribute("width", temp_w*scale);
    this.setAttribute("height", temp_h*scale);
    var ev = window.event || e;
    ev.preventDefault();
}
function togglemw100(){
    //this.removeAttribute('width');
    //this.removeAttribute('height');
    //this.classList.toggle('mw100');
    if(this.getAttribute('width')) {
        this.setAttribute('savewidth',this.getAttribute('width'));
        this.removeAttribute('width');
        this.classList.remove('mw100');
    } else if(this.getAttribute('savewidth')) {
        this.setAttribute('width',this.getAttribute('savewidth'));
        this.removeAttribute('savewidth');
        this.classList.add('mw100');
    } else {
        this.classList.toggle('mw100');
    }
    if(this.getAttribute('height')) {
        this.setAttribute('saveheight',this.getAttribute('height'));
        this.removeAttribute('height');
    } else if(this.getAttribute('saveheight')) {
        this.setAttribute('height',this.getAttribute('saveheight'));
        this.removeAttribute('saveheight');
    }
}
var images=document.querySelectorAll('.t_fsz img.zoom');
for (let item of images) {
    //togglemw100.call(item);
    //item.removeAttribute("alt");
    //item.classList.add('mw100');
    //item.removeAttribute("onclick");
    item.addEventListener("click", togglemw100);
    item.addEventListener("wheel", bbimg);
}
document.querySelectorAll('tikz img,asy img').forEach(a=>a.addEventListener("wheel", bbimg));
document.querySelectorAll('.tupian').forEach(a=>a.addEventListener("wheel", function(e){if(e.shiftKey){this.style.width="";this.style.height="";}}));


//===选择节点内容
function sNC(n) {
  const selection = window.getSelection();
  selection.removeAllRanges();
  const range = document.createRange();
  range.selectNodeContents(n);
  selection.addRange(range);
}
var ztbt=document.getElementById('thread_subject');//主题标题
ztbt.setAttribute("ondblclick", "sNC(this)");

//===楼层目录
//任意元素与页面顶部及左边的距离，抄网上的
function offSet(curEle) {
    var totalLeft = null;
    var totalTop = null;
    var par = curEle.offsetParent;
    //首先把自己本身的相加
    totalLeft += curEle.offsetLeft;
    totalTop += curEle.offsetTop;
    //现在开始一级一级往上查找，只要没有遇到body，我们就把父级参照物的边框和偏移相加
    while (par) {
        if (navigator.userAgent.indexOf("MSIE 8.0") === -1) {
            //不是IE8我们才进行累加父级参照物的边框
            totalTop += par.clientTop;
            totalLeft += par.clientLeft;
        }
        //把父级参照物的偏移相加
        totalTop += par.offsetTop;
        totalLeft += par.offsetLeft;
        par = par.offsetParent;
    }
    return {
        left: totalLeft,
        top: totalTop
    };
    //返回一个数组，方便我们使用哦。
}
//建目录
var lous = document.querySelectorAll("a[id^=postnum]");
var names = document.querySelectorAll(".favatar .pi .authi a");
var MULU = document.createElement("details");
MULU.className = "mlcls";
MULU.setAttribute("open", "");
//MULU.insertAdjacentHTML('beforeend', '<summary>目录</summary>');
var summ = document.createElement("summary");
summ.innerText = '目录';
var mlx = document.createElement("a");
mlx.innerHTML = '×';
mlx.style = 'margin-left:1em';
mlx.setAttribute("onclick", "document.querySelector('.mlcls').style.display='none';");
summ.appendChild(mlx);
MULU.appendChild(summ);
var mlul = document.createElement("ul");
for (var i = 0; i < lous.length; i++) {
    var louid = lous[i].getAttribute('id');
    var htm = lous[i].innerHTML + ' ' + names[i].innerHTML;
    mlul.innerHTML += '<li id="muluid' + i + '"><a href="#' + louid + '">' + htm + '</a></li>';
}
MULU.appendChild(mlul);
document.body.appendChild(MULU);
var muleft = offSet(document.getElementById('ct')).left - MULU.offsetWidth - 20;
if (muleft < 0) {
    MULU.removeAttribute("open");
    muleft = 0;
}
MULU.style = "left:" + muleft + "px;";
//滚动监听
window.onscroll = function() {
    let slTop = Math.max(document.documentElement.scrollTop, document.body.scrollTop);
    let arr = [];
    for (let i = 0; i < lous.length; i++) {
        arr.push(offSet(lous[i]).top);
    }
    arr.push(offSet(document.getElementById('postlistreply')).top); //兜底（最后一层的底部）
    for (let i = 0; i < arr.length - 1; i++) {
        let d = 200; //分界线，可考虑半窗口高 0.5*window.innerHeight;
        if (slTop >= arr[i] - d && slTop <= arr[i + 1] - d) {
            document.getElementById('muluid' + i).classList.add("mlcur");
        } else {
            document.getElementById('muluid' + i).classList.remove("mlcur");
        }
    }
}
