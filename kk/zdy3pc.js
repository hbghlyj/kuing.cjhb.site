//PC端主题页自定义脚本
function show_tikz_window(code){//显示 TikZ代码窗口
    $('tikz_window')?.remove();
    var tikz_window=document.createElement('div');
    tikz_window.id='tikz_window';
    tikz_window.className='tikzww';
    tikz_window.innerHTML='<div style="width:100%;height:26px;cursor:move;"><a href="javascript:$(\'tikz_window\')?.remove();" class="flbc" style="float:right;margin:3px 6px 0 0;"></a></div><div><textarea class="tikzta"></textarea></div>';
    tikz_window.querySelector('textarea').value=code.replace(/\u00a0/g,' ');
    tikz_window.setAttribute("onmousedown", "dragMenu($(\'tikz_window\'), event, 1)");
    document.body.append(tikz_window);
    tikz_window.style.left = (document.body.clientWidth - tikz_window.clientWidth) / 2 + 'px';
    tikz_window.style.top = (document.documentElement.clientHeight - tikz_window.clientHeight) / 2 + 'px';
}
function tuozhuai2(event,ee) {//拖动图片
    //鼠标相对于盒子的位置
    const imgOffset = ee.querySelector('img').getBoundingClientRect();
    var offsetX = event.clientX - imgOffset.left;
    var offsetY = event.clientY - imgOffset.top;
    ee.style.left = event.clientX - offsetX + "px";
    ee.style.top = event.clientY - offsetY + "px";
    if (!ee.classList.contains('tuoing')) {
        ee.classList.add('tuoing');
    }
    document.onmousemove = function (event) {
        ee.style.left = event.clientX - offsetX + "px";
        ee.style.top = event.clientY - offsetY + "px";
    }
    document.onmouseup = function () {
        document.onmousemove = null;
        document.onmouseup = null;
    }
}
function guiwei(ee) {//归位图片
    ee.classList.remove('tuoing');
    ee.style.left=0;
    ee.style.top=0;
    ee.style.width='unset';
}
for (let item of document.getElementsByTagName('bbr')) {//HTML模式下用<bbr>免打<br>
    item.innerHTML = item.innerHTML.replace(/\r\n/g, "<br />").replace(/\n/g, "<br />").replace(/\r/g, "<br />");
}
for (let item of document.getElementsByClassName('blockcode')) {//去br等 + 代码显示
    item.innerHTML = item.innerHTML.replace(/<\/li>/g, "\n</li>");//在php那里去掉\r后没了<br>但复制代码就没了换行，代码块加回去
}
document.querySelectorAll('.t_f').forEach(post => {//解决mathjax3复制多行代码多余空行
    post.querySelectorAll('br').forEach(br => {
        if (br.nextSibling && br.nextSibling.nodeType === Node.TEXT_NODE) {
            br.nextSibling.nodeValue = br.nextSibling.nodeValue.replace(/^\n/, '');
        }
    });
});
document.querySelectorAll('.plc .pi').forEach(plcpi => {//在帖子信息栏添加显示公式代码按钮
    const eye = document.createElement("a");
    eye.addEventListener("click", function(){
        MathJax.startup.document.getMathItemsWithin(this.parentElement.nextElementSibling).forEach(function (item) {
            [item.math,item.typesetRoot[item.typesetRoot.firstElementChild ? "innerText" : "innerHTML"]]=[item.typesetRoot[item.typesetRoot.firstElementChild ? "innerHTML" : "innerText"],item.math];
            if(item.start.n){
                item.math = item.math.slice(item.start.n);
                item.start.n = 0;
            }else{
                item.typesetRoot.prepend(item.start.delim);
                item.start.n = item.start.delim.length;
            }
            if(item.end.n){
                item.math = item.math.slice(0,-item.end.n);
                item.end.n = 0;
            }else{
                item.typesetRoot.append(item.end.delim);
                item.end.n = item.end.delim.length;
            }
        });
    });
    eye.style = "float:right;margin-left:5px;cursor:pointer;";
    eye.innerHTML = "&#x1f441;";
    eye.title = "显示公式代码";
    eye.classList.add('printhides');
    plcpi.insertBefore(eye,plcpi.childNodes[0]);
});
for (let item of document.querySelectorAll('.t_fsz img.zoom,tikz img,asy img')) {//点击图片切换原始大小
    item.hasAttribute('onclick') || item.addEventListener("click", function(){
        if(this.getAttribute('width')) {
            this.setAttribute('savewidth',this.getAttribute('width'));
            this.removeAttribute('width');
            this.classList.remove('mw100');
        }else if(this.getAttribute('savewidth')) {
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
    });
    item.addEventListener("wheel", function(e){//Shift + 鼠标滚轮缩放图片
        if(!e.shiftKey) return;
        e.preventDefault();
        let scale = e.deltaY>0 ? 0.9 : 1.11,
            temp_w=parseFloat(this.getBoundingClientRect().width),
            temp_h=parseFloat(this.getBoundingClientRect().height);
        this.classList.remove('mw100');
        this.setAttribute("width", temp_w*scale);
        this.setAttribute("height", temp_h*scale);
    });
}
document.querySelectorAll('.psti').forEach(pstiElement => {//点评中的回复按钮
    const replyButton = document.createElement('button');
    replyButton.className = 'reply-btn';
    replyButton.addEventListener('click', () => {
        const author = pstiElement.previousElementSibling.lastElementChild.textContent;
        const date_string = pstiElement.querySelector('.xg1').textContent;
        setCopy('[quote][size=2][url=' +pstiElement.parentElement.parentElement.parentElement.parentElement.previousElementSibling.querySelector('strong>a').getAttribute('href') + '][color=#999]' + author + ' 点评' + '[/color][/url][/size]\n' + pstiElement.textContent.slice(0, -3-date_string.length) + '[/quote]', '点评引用已复制到剪贴板');
        const reppost = pstiElement.parentElement.parentElement.parentElement.parentElement.parentElement.parentElement.parentElement.querySelector('div.pob a.fastre').getAttribute('href').replace(/&repquote=/,'&reppost=');
        setTimeout(() => {
            location.href = reppost;
        }, 500);
    });
    pstiElement.appendChild(replyButton);
});
$('thread_subject').setAttribute("ondblclick",function() {//选择主题标题
  const selection = window.getSelection();
  selection.removeAllRanges();
  const range = document.createRange();
  range.selectNodeContents(this);
  selection.addRange(range);
});

//楼层目录
const MULU = document.createElement("div");
MULU.id = "mulu";
const summ = document.createElement("div");
summ.align = "center";
summ.innerText = document.querySelector('#fj > label').innerText;
var mlx = document.createElement("a");
mlx.innerHTML = '×';
mlx.style.float = 'left';
mlx.href="javascript:MULU.remove()";
summ.appendChild(mlx);
MULU.appendChild(summ);
const MULUSELECT = document.createElement("select");
MULUSELECT.style='padding: 0;overflow-y: hidden;border: none;box-shadow: 0 0 2px #2B7ACD;'
MULUSELECT.size = 0;
function addLou(elem) {
    elem.querySelectorAll('#postlist > div[id^="post_"]').forEach(lou => {
        const id = lou.id;
        const option = document.createElement('option');
        option.value = id;
        option.innerHTML = ++MULUSELECT.size + ' ' + lou.querySelector('div.authi>a.neiid').innerHTML;
        MULUSELECT.appendChild(option);
        document.querySelectorAll("td.t_f > div.quote > blockquote > font > a[href$='" + id.replace('postnum','&pid=') + "&ptid=" + tid + "']").forEach(a=>{//将引用的楼层链接改为锚点
            a.firstElementChild.innerHTML = lou.innerHTML + ' ' + a.firstElementChild.innerHTML;
        });
        document.querySelectorAll("td.t_f a[href$='" + id.replace('postnum','&pid=') + "&ptid=" + tid + "']").forEach(a=>{//将楼层链接改为锚点
            a.removeAttribute("target");
            a.setAttribute("href", "#" + id);
            a.style.cursor = 'pointer';
        });
    });
}
MULUSELECT.addEventListener("change", function() {//楼层目录选择跳转
    $(this.value).scrollIntoView({
            behavior: 'auto',
            block: 'center',
            inline: 'center'
        });
});
MULU.appendChild(MULUSELECT);
$('ct').appendChild(MULU);
addLou($('postlist'));

window.addEventListener('scroll', throttle(function() {
    const posts = document.querySelectorAll('#postlist > div[id^="post_"]');
    let targetPost = null;
    for (const post of posts) {
        const rect = post.getBoundingClientRect();
        if (rect.top <= window.innerHeight/2 && rect.bottom >= window.innerHeight/2) {
            targetPost = post;
            break;
        }
    }
    if (targetPost) {
        MULUSELECT.value = targetPost.id;
        $('scrolltop').querySelector('a.editp').href = targetPost.querySelector('a.editp').href;
    }
}));
function throttle(func) {//ensures that func is only executed once within 200ms, even if func is called multiple times
    let inThrottle;
    return function() {
        if (!inThrottle) {
            func.apply(this, arguments);
            inThrottle = true;
            setTimeout(() => inThrottle = false, 200);
        }
    }
}