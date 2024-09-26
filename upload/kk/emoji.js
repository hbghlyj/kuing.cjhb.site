//添加 css
var emstyle = document.createElement("style");
emstyle.innerHTML = `.dspn { display:none; }
.emojiww { position:fixed; z-index:201; width:20em; height:346px;
    border:2px green solid; background: #EDEDED; }
.emjbt { width: 2em; height: 2em; border: none; padding: 0; }
.emjbt:hover { background-color: yellow; }
`;
document.getElementsByTagName("head")[0].appendChild(emstyle);

//拖拽（抄网上略改）
function tuozhuai(ee) {
    //var ee = document.querySelector(ele);
    var event = event || window.event;
    //鼠标相对于盒子的位置
    var offsetX = event.clientX - ee.offsetLeft;
    var offsetY = event.clientY - ee.offsetTop;
    //鼠标移动
    document.onmousemove = function () {
        var event = event || window.event;
        ee.style.left = event.clientX - offsetX + "px";
        ee.style.top = event.clientY - offsetY + "px";
        ee.style.right = "unset";
        ee.style.bottom = "unset";
    }
    //鼠标抬起
    document.onmouseup = function () {
        document.onmousemove = null;
        document.onmouseup = null;
    }
    return false;
}

//emoji键盘，仿照tikz及草稿本
function toggle_emoji_window(){
    var emoji_window=document.getElementById('emoji_window');
    if(emoji_window){emoji_window.classList.toggle("dspn");}
}
function show_emoji_window(ele){
  if(document.getElementById('emoji_window')) { toggle_emoji_window(); } else {
    var emoji_window=document.createElement('div');
    emoji_window.id='emoji_window';
    emoji_window.className='emojiww';
    var event = event || window.event;
    emoji_window.style.left = Math.min(event.clientX, document.body.clientWidth-320-4) + "px";
    emoji_window.style.top = Math.min(event.clientY, window.innerHeight-346-4) + "px";
    emoji_window.style.right = "unset";
    emoji_window.style.bottom = "unset";
    emoji_window.innerHTML=`<div onmousedown="tuozhuai(this.parentNode);return false;" style="width:100%;height:26px;cursor:move;">
        <a href="javascript:;" style="margin-left:6px;" onclick="document.getElementById('emoji_window').style.cssText+='display:initial;';this.innerHTML='Locked';">Lock</a>
        <a href="javascript:;" class="flbc" style="float:right;margin:3px 6px 0 0;" onclick="this.parentNode.parentNode.remove();">关闭</a></div>`;
    document.body.append(emoji_window);
    addbtemoji(ele,0x1F600,0x1F637)//黄脸常用一堆
    addbtemoji(ele,0x1F641,0x1F644)//黄脸x4小难过--白眼
    //addbtemoji(ele,0x2639,0x263A)//黄脸难过,微笑//改为下面两个
    emoji_window.appendChild(createbtemoji(ele,'&#x2639;&#xFE0F;','0x2639+0xFE0F'));
    emoji_window.appendChild(createbtemoji(ele,'&#x263A;&#xFE0F;','0x263A+0xFE0F'));
    addbtemoji(ele,0x1F47F)//恶魔
    addbtemoji(ele,0x1F910,0x1F915)//黄脸x6封嘴--受伤
    addbtemoji(ele,0x1F917)//黄脸双手
    addbtemoji(ele,0x1F920)//黄脸牛仔
    addbtemoji(ele,0x1F922,0x1F925)//黄脸x4绿脸--长鼻
    addbtemoji(ele,0x1F927,0x1F92F)//黄脸x9喷嚏--炸头
    addbtemoji(ele,0x1F970,0x1F976)//黄脸x7三心--冻
    addbtemoji(ele,0x1F97A)//黄脸哀求
    addbtemoji(ele,0x1F9D0)//黄脸找
    addbtemoji(ele,0x1F440)//双眼
    addbtemoji(ele,0x1F64F)//合十
    addbtemoji(ele,0x1F4AF)//100分
  }
}
function addbtemoji(f,a,b) {
    var b = arguments[2] ? arguments[2] : a;
    for(var i = a; i < b+1; i++){
        var emoji_window=document.getElementById('emoji_window');
        emoji_window.appendChild(createbtemoji(f,`&#${i};`,`0x${i.toString(16).toUpperCase()}`));
    }
}
function createbtemoji(ele,str,tt) {
    var bt=document.createElement("button");
    bt.className="emjbt";
    bt.setAttribute("onclick",`chaemoji('`+ele+`','`+str+`');toggle_emoji_window();`);
    bt.setAttribute("title",tt);
    bt.innerHTML=str;
    return bt;
}
function chaemoji(a,codes) {
    var ele = document.querySelector(a);
    insertAtCursor(ele,entityToString(codes));
}
function entityToString(entity) {
    var div = document.createElement('div');
    div.innerHTML = entity;
    var res = div.innerText || div.textContent;
    return res;
}
function insertAtCursor(myField, myValue) {
    if (myField.selectionStart || myField.selectionStart == '0') {
        var startPos = myField.selectionStart;
        var endPos = myField.selectionEnd;
        // save scrollTop before insert
        var restoreTop = myField.scrollTop;
        myField.value = myField.value.substring(0, startPos) + myValue + myField.value.substring(endPos, myField.value.length);
        myField.focus();
        myField.scrollTop = restoreTop;
        myField.selectionStart = startPos + myValue.length;
        myField.selectionEnd = startPos + myValue.length;
    } else {
        myField.value += myValue;
        myField.focus();
    }
}
