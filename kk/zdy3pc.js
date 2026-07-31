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

function initializePostImages(root) {
    const images = [];
    if(root.nodeType === Node.ELEMENT_NODE && root.matches('.t_fsz img.zoom')) {
        images.push(root);
    }
    if(root.querySelectorAll) {
        images.push(...root.querySelectorAll('.t_fsz img.zoom'));
    }
    images.forEach(img => {
        if(img.dataset.postImageReady) return;
        img.dataset.postImageReady = '1';

        let wrapper = img.closest('.tupian');
        if(!wrapper) {
            wrapper = document.createElement('div');
            wrapper.className = 'tupian';
            const loader = document.createElement('div');
            loader.className = 'jiaz';
            const drag = document.createElement('div');
            drag.className = 'tuozt';
            drag.addEventListener('mousedown', function(event) {
                event.preventDefault();
                tuozhuai2(event, wrapper);
            });
            const reset = document.createElement('div');
            reset.className = 'guiw';
            reset.addEventListener('click', function(event) {
                event.preventDefault();
                guiwei(wrapper);
            });
            img.parentNode.insertBefore(wrapper, img);
            wrapper.append(loader, drag, reset, img);
        }

        const finishLoading = function() {
            wrapper.classList.add('jiazed');
            if(!img.hasAttribute('width')) img.setAttribute('width', img.width);
            wrapper.style.display = 'inline-block';
        };
        if(img.complete && img.naturalWidth) {
            finishLoading();
        } else {
            img.addEventListener('load', finishLoading, {once: true});
        }
        wrapper.addEventListener('wheel', function(event) {
            if(event.shiftKey) {
                wrapper.style.width = '';
                wrapper.style.height = '';
            }
        });
    });
}
initializePostImages(document);
const postList = document.getElementById('postlist');
if(postList) {
    new MutationObserver(mutations => {
        mutations.forEach(mutation => mutation.addedNodes.forEach(initializePostImages));
    }).observe(postList, {childList: true, subtree: true});
}

//===Shift + 鼠标滚轮缩放图片、点击图片切换原始大小
for (let item of document.querySelectorAll('.t_fsz img.zoom,tikz img,asy img')) {
    const tikzCode = item.getAttribute('data-tikz-code');
    if(tikzCode !== null) {
        item.addEventListener('click', function() {
            show_tikz_window(decodeURIComponent(tikzCode));
        });
    } else if(!item.hasAttribute('onclick')) item.addEventListener("click", function(){
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

/* 点评中的回复按钮 */
document.querySelectorAll('.psti').forEach(pstiElement => {
    const replyButton = document.createElement('button');
    replyButton.className = 'reply-btn';
    replyButton.addEventListener('click', () => {
        const author = pstiElement.previousElementSibling?.lastElementChild?.textContent || '';
        const date_string = pstiElement.querySelector('.xg1')?.textContent || '';
        setCopy('[quote][size=2][url=' + (pstiElement.parentElement?.parentElement?.parentElement?.parentElement?.previousElementSibling?.querySelector('strong>a')?.getAttribute('href') || '#') + '][color=#999]' + author + ' 点评' + '[/color][/url][/size]\n' + pstiElement.textContent.slice(0, -3-date_string.length) + '[/quote]', '点评引用已复制到剪贴板');
        const reppost = pstiElement.parentElement?.parentElement?.parentElement?.parentElement?.parentElement?.parentElement?.parentElement?.querySelector('div.pob a.fastre')?.getAttribute('href')?.replace(/&repquote=/,'&reppost=');
        if (reppost) {
            setTimeout(() => {
                location.href = reppost;
            }, 500);
        }
    });
    pstiElement.appendChild(replyButton);
});
if ($('thread_subject')) {
    $('thread_subject').ondblclick = function() {//选择主题标题
      const selection = window.getSelection();
      selection.removeAllRanges();
      const range = document.createRange();
      range.selectNodeContents(this);
      selection.addRange(range);
    };
}

//楼层目录
if ($('postlist') && $('ct')) {
    const MULU = document.createElement("div");
    MULU.id = "mulu";
    const close = document.createElement("div");
    close.innerText = '×';
    close.onclick = function() {
        MULU.style.display = 'none';
    };
    MULU.appendChild(close);
    const MULUSELECT = document.createElement("select");
    window.MULUSELECT = MULUSELECT;
    MULUSELECT.style = 'padding: 0 !important;background: none !important;overflow-y: hidden;border: none;box-shadow: 0 0 2px #2B7ACD;';
    MULUSELECT.size = 0;
    function addLou(elem) {
        if (!elem) return;
        elem.querySelectorAll('#postlist > div[id^="post_"]').forEach((lou, index) => {
            const floorLink = lou.querySelector('td.plc>div.pi>strong>a');
            if (!MULUSELECT.querySelector('option[value="' + lou.id + '"]')) {
                if (!floorLink || !floorLink.firstChild) return;
                const option = document.createElement('option');
                option.value = lou.id;
                const authorLink = lou.querySelector('td.plc > div.pi .authi > a.xi2:not(.avt)') || lou.querySelector('.favatar > .pi .authi > a');
                option.text = floorLink.firstChild.textContent + (authorLink ? ' ' + authorLink.textContent : '');
                MULUSELECT.appendChild(option);
                ++MULUSELECT.size;
            }
            const pidRef = lou.id.replace('post_', '&pid=');
            document.querySelectorAll("td.t_f > div.quote > blockquote > font > a[href$='" + pidRef + "&ptid=" + tid + "']").forEach(a => {
                if (a.firstElementChild && floorLink) {
                    a.firstElementChild.innerHTML = floorLink.innerHTML + ' ' + a.firstElementChild.innerHTML;
                }
            });
            document.querySelectorAll("td.t_f a[href$='" + pidRef + "&ptid=" + tid + "']").forEach(a => {
                a.removeAttribute("target");
                a.setAttribute("href", "#" + lou.id);
                a.style.cursor = 'pointer';
            });
        });
        const postlistElem = $('postlist');
        if (MULUSELECT.size < 2 || !postlistElem || postlistElem.clientHeight < window.innerHeight) {
            MULU.style.display = 'none';
        } else {
            MULU.style.display = '';
            if (MULUSELECT.firstChild && MULUSELECT.lastChild) {
                MULUSELECT.style.height = MULUSELECT.lastChild.offsetHeight + MULUSELECT.lastChild.offsetTop - MULUSELECT.firstChild.offsetTop + 'px';
            }
        }
    }
    window.updateMulu = function() {
        addLou($('postlist'));
    };
    MULUSELECT.addEventListener("change", function() {//楼层目录选择跳转
        location.hash = '#' + this.value;
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
}
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
