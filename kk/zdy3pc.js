//===支持tikz + asymptote
function show_tikz_window(code){
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
function tuozhuai2(event,ee) {
    //鼠标相对于盒子的位置
    const imgOffset = ee.querySelector('img').getBoundingClientRect();
    var offsetX = event.clientX - imgOffset.left;
    var offsetY = event.clientY - imgOffset.top;
    ee.style.left = event.clientX - offsetX + "px";
    ee.style.top = event.clientY - offsetY + "px";
    if (!ee.classList.contains('tuoing')) {
        ee.classList.add('tuoing');
    }
    //鼠标移动
    document.onmousemove = function (event) {
        ee.style.left = event.clientX - offsetX + "px";
        ee.style.top = event.clientY - offsetY + "px";
    }
    //鼠标抬起
    document.onmouseup = function () {
        document.onmousemove = null;
        document.onmouseup = null;
    }
}
function guiwei(ee) {
    ee.classList.remove('tuoing');
    ee.style.left=0;
    ee.style.top=0;
    ee.style.width='unset';
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
document.querySelectorAll('.t_f').forEach(post => {
    post.querySelectorAll('br').forEach(br => {
        //解决mathjax3复制多行代码多余空行
        if (br.nextSibling && br.nextSibling.nodeType === Node.TEXT_NODE) {
            br.nextSibling.nodeValue = br.nextSibling.nodeValue.replace(/^\n/, '');
        }
        //去行间公式后的1个br
        if (br.previousSibling && br.previousSibling.nodeType === Node.TEXT_NODE) {
            if (/(\\\]|\\end\{(align|gather|equation|eqnarray|multline)\*?\}|\$\$)( |&nbsp;)*$/.test(br.previousSibling.nodeValue)) {
                br.previousSibling.nodeValue = br.previousSibling.nodeValue.replace(/( |&nbsp;)*$/, '');
                br.remove();
            }
        }
        //去引用后的1个br，代码块后的1个br
        else if (br.previousSibling && br.previousSibling.nodeType === Node.ELEMENT_NODE && br.previousSibling.matches('div.quote,div.blockcode')) {
            br.remove();
        }
    });
});

const show_math_code = function(){
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
};
document.querySelectorAll('.plc .pi').forEach(plcpi => {
    const eye = document.createElement("a");
    eye.addEventListener("click", function(){
        eye.classList.toggle('slashed');
        show_math_code.call(this);
    });
    eye.style = "float:right;margin-left:5px;cursor:pointer;position:relative;"
    eye.innerHTML = "&#x1f441;";
    eye.title = "显示公式代码";
    eye.classList.add('printhides');
    plcpi.insertBefore(eye,plcpi.childNodes[0]);
});

//===Shift + 鼠标滚轮缩放图片、点击图片切换原始大小
for (let item of document.querySelectorAll('.t_fsz img.zoom,tikz img,asy img')) {
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
    item.addEventListener("wheel", function(e){
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
document.querySelectorAll('.tupian').forEach(a=>a.addEventListener("wheel", function(e){if(e.shiftKey){this.style.width="";this.style.height="";}}));

/* 点评中的回复按钮 */
document.querySelectorAll('.psti').forEach(pstiElement => {
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
$('thread_subject').ondblclick=function() {//选择主题标题
  const selection = window.getSelection();
  selection.removeAllRanges();
  const range = document.createRange();
  range.selectNodeContents(this);
  selection.addRange(range);
};

//楼层目录
const MULU = document.createElement("div");
MULU.id = "mulu";
const close = document.createElement("div");
close.innerText = '×';
close.onclick = function() {
    MULU.style.display = 'none';
};
MULU.appendChild(close);
const MULUSELECT = document.createElement("select");
MULUSELECT.style = 'padding: 0;overflow-y: hidden;border: none;box-shadow: 0 0 2px #2B7ACD;';
MULUSELECT.size = 0;
function addLou(elem) {
    elem.querySelectorAll('#postlist > div[id^="post_"]').forEach((lou, index) => {
        const option = document.createElement('option');
        option.value = lou.id;
        option.text = lou.querySelector('td.plc>div.pi>strong>a').firstChild.textContent + ' ' + lou.querySelector('div.authi>a.neiid').innerText;
        MULUSELECT.appendChild(option);
        const pidRef = lou.id.replace('post_', '&pid=');
        document.querySelectorAll("td.t_f > div.quote > blockquote > font > a[href$='" + pidRef + "&ptid=" + tid + "']").forEach(a => {
            if (a.firstElementChild) {
                a.firstElementChild.innerHTML = lou.querySelector('td.plc>div.pi>strong>a').innerHTML + ' ' + a.firstElementChild.innerHTML;
            }
        });
        document.querySelectorAll("td.t_f a[href$='" + pidRef + "&ptid=" + tid + "']").forEach(a => {
            a.removeAttribute("target");
            a.setAttribute("href", "#" + lou.id);
            a.style.cursor = 'pointer';
        });
        ++MULUSELECT.size;
    });
    if (MULUSELECT.size < 2 || $('postlist').clientHeight < window.innerHeight) {
        MULU.style.display = 'none';
    } else {
        MULU.style.display = '';
        MULUSELECT.style.height = MULUSELECT.lastChild.offsetHeight + MULUSELECT.lastChild.offsetTop - MULUSELECT.firstChild.offsetTop + 'px';
    }
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

window.addEventListener('scroll', debounce(function() {
    const posts = document.querySelectorAll('#postlist > div[id^="post_"]');
    let targetPost = null;
    for (const post of posts) {
        const rect = post.getBoundingClientRect();
        if (rect.top <= window.innerHeight / 2 && rect.bottom >= window.innerHeight / 2) {
            targetPost = post;
            break;
        }
    }
    if (targetPost) {
        MULUSELECT.value = targetPost.id;
        const editLink = $('scrolltop')?.querySelector('a.editp');
        const sourceEdit = targetPost.querySelector('a.editp');
        if (editLink && sourceEdit) {
            editLink.href = sourceEdit.href;
        }
    }
}, 200));
function debounce(func, delay) {
    let timeoutId;
    return function() {
        const context = this;
        const args = arguments;
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => {
            func.apply(context, args);
        }, delay);
    }
}
