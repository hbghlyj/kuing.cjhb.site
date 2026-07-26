(() => {
  const isChinese = navigator.languages && navigator.languages.some(lang => ['zh','zh-CN','zh-TW','zh-HK','zh-SG'].includes(lang));
  const isMobile = typeof popup == 'object';
  function showError(msg){ if(isMobile){ popup.open(msg,'alert'); } else { alert(msg); } }
  function typesetNodes(targets){
    if(typeof MathJax === 'undefined' || typeof MathJax.typesetPromise !== 'function'){
      return Promise.resolve();
    }
    return MathJax.typesetPromise(targets);
  }
  function setVisible(element, visible){
    element.hidden = !visible;
  }
  async function requestJSON(url, options = {}){
    const response = await fetch(url, options);
    let data = null;
    try {
      data = await response.json();
    } catch (error) {
      if(response.ok) throw error;
    }
    if(!response.ok){
      const requestError = new Error(data?.error || response.statusText);
      requestError.response = response;
      requestError.data = data;
      throw requestError;
    }
    return data;
  }
  class PusherChatWidget {
    static instances = [];
    #pusher;
    #chatChannel;
    #pendingMessages = [];
    #isProcessingPendingMessages = false;
    #onQueueDrainedCallback = null;
    #itemCount = 0;
    #totalMessages = 0;
    #messagesLoaded = 0;
    #lastMessageTimestamp = null;
    #wasDisconnected = false;
    #widget;
    #messageInputEl;
    #messagesEl;
    #loadMoreButton;
    settings;
    isCollapsed;
    constructor(pusher, options = {}) {
      PusherChatWidget.instances.push(this);
      this.#pusher = pusher;
      this.settings = Object.assign({
        chatEndPoint: '/chat/php/chat.php',
        channelName: 'Chat',
        appendTo: document.body,
        debug: true
      }, options);
      this.#widget = PusherChatWidget._createHTML(this.settings.appendTo);
      this.#messageInputEl = this.#widget.querySelector('textarea');
      this.#messagesEl = this.#widget.querySelector('ul');
      this.#loadMoreButton = this.#widget.querySelector('.pusher-chat-widget-load-more');
      this.isCollapsed = document.cookie.replace(/(?:(?:^|.*;\s*)isCollapsed\s*=\s*([^;]*).*$)|^.*$/, '$1') == 'true';
      if(isMobile) this.isCollapsed = false;
      this.#chatChannel = this.#pusher.subscribe(this.settings.channelName);
      this.#pusher.connection.bind('connected', () => {
        this.#widget.querySelector('label').textContent = isChinese ? '已连接' : 'Connected';
      });
      this.#pusher.connection.bind('connecting', () => {
        this.#widget.querySelector('label').textContent = isChinese ? '连接中' : 'Connecting';
      });
      this.#chatChannel.bind('pusher:subscription_succeeded', () => {
        this.#widget.querySelector('label').textContent = (isChinese ? '快捷键' : 'Shortcut') + ' Ctrl+Enter';
        this.#widget.querySelectorAll('.pusher-chat-widget-send-btn, .pusher-chat-widget-photo-btn').forEach(button => {
          button.disabled = false;
        });
      });
      this.#pusher.connection.bind('unavailable', () => {
        this.#widget.querySelector('label').textContent = isChinese ? '请检查网络连接' : 'Please check your network connection';
        this.#widget.querySelectorAll('.pusher-chat-widget-send-btn, .pusher-chat-widget-photo-btn').forEach(button => {
          button.disabled = true;
        });
      });
      this.#pusher.connection.bind('state_change', states => {
        if(states.current==='disconnected'||states.current==='unavailable'){
          this.#wasDisconnected = true;
        }else if(states.current==='connected' && this.#wasDisconnected){
          this.#fetchMissedMessages();
          this.#wasDisconnected = false;
        }
      });
      if(typeof tid!=='undefined'){
        this.#chatChannel.bind('newreply', data => {
          const pageNumberElement=document.querySelector('div.pg>strong');
          const pageNumber=pageNumberElement?pageNumberElement.textContent.trim():'1';
          const postId = `post_${data.pid}`;
          if(data.tid!=tid){
            return;
          }
          if($(postId)){
            return;
          }
          if(String(data.page) !== String(pageNumber)){
            const jumpToReply = () => {
              location.href = `forum.php?mod=viewthread&tid=${tid}&page=${data.page}#pid${data.pid}`;
            };
            if(String(data.uid) === String(discuz_uid)){
              jumpToReply();
            }else{
              const msg = isChinese
                ? '本主题有新回复，是否跳转到最新回复？选择取消将留在当前页面。'
                : 'There is a new reply in this thread. Jump to it now? Choose Cancel to stay on this page.';
              const title = isChinese ? '新回复提醒' : 'New reply';
              const confirmTxt = isChinese ? '跳转' : 'Jump';
              const cancelTxt = isChinese ? '留在本页' : 'Stay';
              if(typeof showDialog === 'function'){
                showDialog(msg, 'confirm', title, jumpToReply, 1, function() {}, '', confirmTxt, cancelTxt);
              }else if(confirm(msg)){
                jumpToReply();
              }
            }
            return;
          }
          if(data.tid==tid && data.page==pageNumber){
            ajaxget(`forum.php?mod=viewthread&tid=${tid}&viewpid=${data.pid}`, 'post_new', 'ajaxwaitid', '', null, function() {
              if(typeof appendreply === 'function') {
                appendreply(data.pid);
              }
            });
          }
        });
        this.#chatChannel.bind('editpost', data => {
          if(data.tid==tid && document.getElementById(`pid${data.pid}`)){
            ajaxget(`forum.php?mod=viewthread&tid=${tid}&viewpid=${data.pid}`, `post_${data.pid}`, 'ajaxwaitid', '', null, "if (typeof MathJax !== 'undefined' && typeof MathJax.typesetPromise === 'function') {MathJax.texReset();MathJax.typesetPromise(['#pid"+data.pid+" :is(div.pcb>h2, td.t_f)'])}");
            if(data.subject){
              document.getElementById('thread_subject').innerHTML = data.subject;
              typesetNodes(['#thread_subject']).catch(err => { showError('MathJax typesetting error:'+err); });
            }
            if(document.querySelector('input[name=pid]')?.value==data.pid && discuz_uid!=data.uid){
              showDialog(isChinese?'帖子已被编辑':'Post has been edited');
            }
          }
        });
        this.#chatChannel.bind('commentadd', data => {
          if(data.tid==tid && document.getElementById(`pid${data.pid}`)){
            ajaxget('forum.php?mod=misc&action=commentmore&tid='+tid+'&pid='+data.pid, 'comment_'+data.pid, 'ajaxwaitid', '', null, "if (typeof MathJax !== 'undefined' && typeof MathJax.typesetPromise === 'function') {MathJax.typesetPromise(['#comment_"+data.pid+"'])}");
          }
        });
        this.#chatChannel.bind('deletepost', data => {
          if(data.tid==tid && document.getElementById(`post_${data.pid}`)){
            document.getElementById(`post_${data.pid}`).remove();
            if(typeof MULUSELECT !== 'undefined' && MULUSELECT){
              const option = MULUSELECT.querySelector(`option[value="post_${data.pid}"]`);
              if(option){
                option.remove();
                MULUSELECT.size--;
              }
              if(MULUSELECT.firstChild && MULUSELECT.lastChild){
                MULUSELECT.style.height=MULUSELECT.lastChild.offsetTop-MULUSELECT.firstChild.offsetTop+'px';
              }
            }
          }
        });
      }
      if(document.cookie.replace(/(?:(?:^|.*;\s*)isCollapsed\s*\=\s*([^;]*).*$)|^.*$/, '$1')==''){
        document.cookie='isCollapsed=true; path=/forum.php';
        document.cookie='isCollapsed=true; path=/member.php';
        document.cookie='isCollapsed=true; path=/connect.php';
        document.cookie='isCollapsed=true; path=/misc.php';
        document.cookie='isCollapsed=true; path=/home.php';
      }
      if(this.isCollapsed){
        setVisible(this.#widget.querySelector('.pusher-chat-widget-messages'), false);
        setVisible(this.#widget.querySelector('.pusher-chat-widget-input'), false);
        this.#widget.querySelector('.toggle-icon').innerHTML = '<path d="M7 14l5-5 5 5z"/>';
        if(!isMobile){
          this.#widget.querySelector('.pusher-chat-widget-header').addEventListener('click', () => {
            setVisible(this.#widget.querySelector('.pusher-chat-widget-messages'), true);
            setVisible(this.#widget.querySelector('.pusher-chat-widget-input'), true);
            document.cookie = 'isCollapsed=false; path=' + location.pathname;
            this.isCollapsed = false;
            this.#widget.querySelector('.toggle-icon').innerHTML = '<path d="M7 10l5 5 5-5z"/>';
            this.#init();
          }, {once: true});
        }
      } else {
        this.#init();
      }
    }
    #init(){
      this.#loadHistory();
      this.#chatChannel.bind('chat_message', data => {
        this.#chatMessageReceived(data,true);
        this.#processPendingMessages();
      });
      if(!isMobile){
        this.#widget.querySelector('.pusher-chat-widget-header').addEventListener('click', () => {
          this.isCollapsed = !this.isCollapsed;
          setVisible(this.#widget.querySelector('.pusher-chat-widget-messages'), !this.isCollapsed);
          setVisible(this.#widget.querySelector('.pusher-chat-widget-input'), !this.isCollapsed);
          document.cookie = 'isCollapsed=' + this.isCollapsed + '; path=' + location.pathname;
          this.#widget.querySelector('.toggle-icon').innerHTML = this.isCollapsed ? '<path d="M7 14l5-5 5 5z"/>' : '<path d="M7 10l5 5 5-5z"/>';
        });
      }
      this.#widget.querySelector('.pusher-chat-widget-send-btn').addEventListener('click', () => this.#sendChatButtonClicked());
      this.#widget.querySelector('.pusher-chat-widget-photo-btn').addEventListener('click', () => {
        this.#widget.querySelector('.pusher-chat-widget-photo-input').click();
      });
      this.#widget.querySelector('.pusher-chat-widget-photo-input').addEventListener('change', e => {
        this.#handlePhotoUpload(e.target);
      });
      this.#messageInputEl.addEventListener('keydown', e => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
          this.#sendChatButtonClicked();
        }
      });
      this.#startTimeMonitor();
      this.#loadMoreButton.addEventListener('click', () => { this.#loadHistory(true); });
    }
    async #loadHistory(isLoadingMore){
      if(isLoadingMore){
        this.#loadMoreButton.textContent = isChinese ? '加载中...' : 'Loading...';
        this.#loadMoreButton.disabled = true;
      }
      try {
        const response = await requestJSON('/chat/php/history.php?offset=' + encodeURIComponent(this.#messagesLoaded));
        const data = response.messages;
        this.#totalMessages = response.total;
        if (data && data.length > 0) {
          for (let i = 0; i < data.length; ++i) {
            this.#chatMessageReceived(data[i],false,isLoadingMore);
          }
          if(!isLoadingMore){
            this.#onQueueDrainedCallback=()=>{
              this.#messagesEl.scrollTop = this.#messagesEl.scrollHeight;
              this.#onQueueDrainedCallback=null;
            };
          }
          this.#processPendingMessages();
          this.#messagesLoaded += data.length;
          if(this.#messagesLoaded >= this.#totalMessages){
            setVisible(this.#loadMoreButton, false);
          }else{
            setVisible(this.#loadMoreButton, true);
            this.#loadMoreButton.textContent = isChinese ? '加载更多' : 'Load More';
            this.#loadMoreButton.disabled = false;
          }
        }
        else if(!isLoadingMore){
          setVisible(this.#loadMoreButton, false);
        }
      } catch(error) {
        showError('Error fetching history: ' + error.message);
        if(isLoadingMore){
          this.#loadMoreButton.textContent = isChinese ? '加载失败' : 'Failed to load';
          this.#loadMoreButton.disabled = false;
        }
      }
    }
    async #fetchMissedMessages(){
      if(!this.#lastMessageTimestamp) return;
      try {
        const response = await requestJSON('/chat/php/history.php?offset=0&limit=100');
        const data = response.messages || [];
        const newMessages = [];
        for (let i = 0; i < data.length; ++i) {
          if (new Date(data[i].published) > new Date(this.#lastMessageTimestamp)) {
            newMessages.push(data[i]);
          }
        }
        if (newMessages.length > 0) {
          for (let j = 0; j < newMessages.length; ++j) {
            this.#chatMessageReceived(newMessages[j],true);
          }
          this.#processPendingMessages();
        }
      } catch(error) {
        showError('Error fetching missed messages: ' + error.message);
      }
    }
    #chatMessageReceived(data,isLiveMessage,isPrepending=false){
      const messageEl = PusherChatWidget._buildListItem(data);
      const entry = {data, messageEl, isLiveMessage, isPrepending};
      if(isPrepending){
        this.#pendingMessages.unshift(entry);
      }else{
        this.#pendingMessages.push(entry);
      }
    }
    #processPendingMessages(){
      if(this.#isProcessingPendingMessages) return;
      if(this.#pendingMessages.length===0){
        if(this.#onQueueDrainedCallback){ this.#onQueueDrainedCallback(); }
        return;
      }
      this.#isProcessingPendingMessages=true;
      let currentEntry=this.#pendingMessages[0];
      let messageEl=currentEntry.messageEl;
      let images=messageEl.querySelectorAll('img');
      let oldScrollHeight=0;
      let oldScrollTop=0;
      if(currentEntry.isPrepending){
        oldScrollHeight=this.#messagesEl.scrollHeight;
        oldScrollTop=this.#messagesEl.scrollTop;
      }
      if(images.length===0){
        this.#actuallyAppendMessage(currentEntry,oldScrollHeight,oldScrollTop);
      }else{
        let loaded=0;
        images.forEach(img=>{
          let handled = false;
          const complete = ()=>{
            if(handled) return;
            handled = true;
            if(++loaded===images.length){
              this.#actuallyAppendMessage(currentEntry,oldScrollHeight,oldScrollTop);
            }
          };
          img.addEventListener('load', complete, {once: true});
          img.addEventListener('error', complete, {once: true});
          if(img.complete||img.naturalWidth>0){ complete(); }
        });
      }
    }
    #actuallyAppendMessage(entry,oldScrollHeight,oldScrollTop){
      this.#pendingMessages.shift();
      if(entry.isPrepending){
        this.#loadMoreButton.insertAdjacentElement('afterend', entry.messageEl);
      }else{
        this.#messagesEl.append(entry.messageEl);
      }
      this.#lastMessageTimestamp=entry.data.published;
      if(isMobile){ this.#addSwipeToDeleteHandlers(entry.messageEl, entry.data.published); }
      typesetNodes([entry.messageEl]).catch(err=>{ showError('MathJax typesetting error:'+err); });
      this.#itemCount++;
      if(entry.isLiveMessage){
        this.#messagesEl.scrollTo({top:this.#messagesEl.scrollHeight,behavior:'smooth'});
      }else if(entry.isPrepending){
        let newScrollHeight=this.#messagesEl.scrollHeight;
        this.#messagesEl.scrollTop=oldScrollTop+(newScrollHeight-oldScrollHeight);
      }
      this.#isProcessingPendingMessages=false;
      this.#processPendingMessages();
    }
    #sendChatButtonClicked(){
      const message = this.#messageInputEl.value.trim();
      if(!message){
        showError(isChinese ? '请输入聊天信息' : 'Please enter a chat message');
        this.#messageInputEl.focus();
        return;
      }
      const chatInfo = {text: message};
      this.#sendChatMessage(chatInfo);
    }
    async #sendChatMessage(data){
      this.#messageInputEl.readOnly = true;
      const button = this.#widget.querySelector('.pusher-chat-widget-send-btn');
      button.disabled = true;
      button.classList.add('loading');
      const body = new URLSearchParams();
      body.set('formhash', typeof FORMHASH !== 'undefined' ? FORMHASH : '');
      body.set('chat_info[text]', data.text);
      try {
        await requestJSON(this.settings.chatEndPoint, {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'},
          body
        });
        this.#messageInputEl.value = '';
      } catch(error) {
        if(error.response?.status === 413){
          showError(isChinese ? '聊天信息过长' : 'Chat message too long');
        }else{
          showError(isChinese ? ('网络错误: ' + error.message) : ('Network error: ' + error.message));
        }
      } finally {
        this.#messageInputEl.readOnly = false;
        button.disabled = false;
        button.classList.remove('loading');
      }
    }
    #handlePhotoUpload(inputElement){
      if(!inputElement.files || !inputElement.files[0]) return;
      const file = inputElement.files[0];
      const validExtensions = /\.(jpe?g|png|gif|bmp|webp)$/i;
      if(!validExtensions.test(file.name)){
        showError(isChinese ? '请选择有效的图片格式 (jpg, jpeg, png, gif, bmp, webp)' : 'Please select a valid image file (jpg, jpeg, png, gif, bmp, webp)');
        inputElement.value = '';
        return;
      }
      const formData = new FormData();
      formData.append('file', file);
      if(typeof FORMHASH !== 'undefined'){
        formData.append('formhash', FORMHASH);
      }
      const photoButton = this.#widget.querySelector('.pusher-chat-widget-photo-btn');
      photoButton.disabled = true;
      photoButton.classList.add('loading');
      requestJSON('/chat/php/upload.php', {method: 'POST', body: formData})
        .then(response => {
          if(response && response.status === 200 && response.url){
            this.#sendChatMessage({text: response.url});
          }else{
            showError(response?.error || (isChinese ? '图片上传失败' : 'Failed to upload photo'));
          }
        })
        .catch(error => {
          showError(isChinese ? ('图片上传错误: ' + error.message) : ('Photo upload error: ' + error.message));
        })
        .finally(() => {
          photoButton.disabled = false;
          photoButton.classList.remove('loading');
          inputElement.value = '';
        });
    }
    #startTimeMonitor(){
      setInterval(()=>{
        this.#messagesEl.querySelectorAll('a.timestamp span[data-activity-published]').forEach(el=>{
          const time = el.dataset.activityPublished;
          const desc = PusherChatWidget.timeToDescription(time);
          el.textContent = desc;
        });
      },10000);
    }
    static _createHTML(appendTo){
      const addPhotoSvg = '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M19 7v2.99s-1.99 0-2 0V7h-3s0-1.99 0-2h3V2h2v3h3v2h-3zm-3 4V8h-3V5H5c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2v-8h-3zM5 19l3-3.86 2.14 2.58 3-3.86L17 19H5z"/></svg>';
      const sendSvg = '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>';

      const html='<div class="pusher-chat-widget">'+
        (isMobile?'':'<div class="pusher-chat-widget-header"><svg class="toggle-icon" width="24" height="24" viewBox="0 0 24 24"><path d="M7 10l5 5 5-5z"/></svg></div>')+
        '<div class="pusher-chat-widget-messages"><ul class="activity-stream">'+
        '<li class="pusher-chat-widget-load-more" style="display:none;">'+(isChinese?'加载更多':'Load More')+'</li>'+
        '</ul></div>'+
        '<div class="pusher-chat-widget-input">'+
        '<label for="message"></label><textarea id="message"></textarea>'+
        '<input type="file" class="pusher-chat-widget-photo-input" accept="image/*" style="display:none;" />'+
        '<button type="button" class="pusher-chat-widget-photo-btn" title="'+(isChinese?'发送图片':'Add Photo')+'" disabled>'+addPhotoSvg+'</button>'+
        '<button type="button" class="pusher-chat-widget-send-btn" title="'+(isChinese?'发送消息':'Send Message')+'" disabled>'+sendSvg+'</button>'+
        '</div></div>';
      const holder = document.createElement('div');
      holder.innerHTML = html;
      const widget = holder.firstElementChild;
      appendTo.append(widget);
      return widget;
    }
    static _buildListItem(activity){
      const li = document.createElement('li');
      li.className = 'message-item';
      const contentWrapper = document.createElement('div');
      contentWrapper.className = 'message-content-wrapper';
      const avatar = document.createElement('img');
      avatar.className = 'user_avatar';
      avatar.width = 24;
      avatar.height = 24;
      avatar.dataset.avatarKey = activity.actor.displayName;
      avatar.dataset.avatarName = activity.actor.displayName;
      avatar.alt = activity.actor.displayName;
      avatar.addEventListener('error', function(){ renderInitialAvatar(this); });
      const image = document.createElement('div');
      image.className = 'image';
      image.append(avatar);
      if(activity.actor.image){
        avatar.src = activity.actor.image;
      }else{
        renderInitialAvatar(avatar);
      }
      const content = document.createElement('div');
      content.className = 'content';
      const user = document.createElement('div');
      user.className = 'activity-row';
      const userName = document.createElement('span');
      userName.className = 'user-name';
      const screenName = document.createElement('a');
      screenName.className = 'screen-name';
      screenName.textContent = activity.actor.displayName.replace(/\\'/g,"'");
      const timestamp = document.createElement('a');
      timestamp.className = 'timestamp';
      if(activity.link) timestamp.href = activity.link;
      const timestampText = document.createElement('span');
      timestampText.dataset.activityPublished = activity.published;
      timestampText.textContent = PusherChatWidget.timeToDescription(activity.published);
      timestamp.append(timestampText);
      userName.append(screenName, timestamp);
      user.append(userName);
      content.append(user);
      let bodyText = activity.body || '';
      if (window.location.protocol === 'https:') {
        bodyText = bodyText.replace(/http:\/\/([^\/]+)(\/data\/attachment\/)/gi, '$2');
      }
      const textHtml = bodyText.replace(/(https?:\/\/\S+\b|\/data\/attachment\/\S+\b)/gi,m=>(/\.(png|jpe?g|gif|bmp|svg|webp)$/i.test(m)?'<img src="'+m+'" />':'<a href="'+m+'">'+m+'</a>')).replace(/\n/g,'<br>');
      const message = document.createElement('div');
      message.className = 'activity-row';
      const text = document.createElement('div');
      text.className = 'text';
      text.innerHTML = textHtml;
      message.append(text);
      content.append(message);
      contentWrapper.append(image, content);
      const deleteAction = document.createElement('div');
      deleteAction.className = 'delete-action';
      const deleteButton = document.createElement('button');
      deleteButton.className = 'delete-button';
      deleteButton.textContent = isChinese ? '删除' : 'Delete';
      deleteAction.append(deleteButton);
      li.append(contentWrapper, deleteAction);
      return li;
    }
    #addSwipeToDeleteHandlers(liElement,published){
      const threshold = 50;
      liElement.addEventListener('touchstart',e=>{
        this.#messagesEl.querySelectorAll('li.slide-active').forEach(item => {
          if(item !== liElement) item.classList.remove('slide-active');
        });
        liElement.dataset.touchStartX = e.touches[0].clientX;
      });
      liElement.addEventListener('touchend',e=>{
        const touchStartX = Number(liElement.dataset.touchStartX);
        if(!Number.isFinite(touchStartX)) return;
        const endX = e.changedTouches[0].clientX;
        const deltaX = endX - touchStartX;
        if(e.target.closest('.delete-button')){
          delete liElement.dataset.touchStartX;
          return;
        }
        if(deltaX<-threshold){
          liElement.classList.add('slide-active');
        }else if(deltaX>threshold){
          liElement.classList.remove('slide-active');
        }
        delete liElement.dataset.touchStartX;
      });
      liElement.querySelector('.delete-button').addEventListener('click',async e=>{
        e.stopPropagation();
        const body = new URLSearchParams({published_time: published});
        try {
          const response = await fetch('/chat/php/delete.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'},
            body
          });
          if(!response.ok) throw new Error(await response.text() || response.statusText);
          liElement.remove();
          this.#itemCount--;
        } catch(error) {
          showError((isChinese?'删除消息失败: ':'Error deleting message: ')+error.message);
        }
      });
    }
    static timeToDescription(time){
      const now=new Date();
      const date=new Date(time);
      const diff=now-date;
      const sec=Math.floor(diff/1000);
      const min=Math.floor(sec/60);
      const hr=Math.floor(min/60);
      let desc;
      if(sec<=0){ desc=isChinese?'刚刚':'just now'; }
      else if(min<1){ desc=sec+' second'+(sec!==1?'s':'')+' ago'; if(isChinese){ desc=sec+(sec<10?' 秒':' 秒钟')+'前'; } }
      else if(min<60){ desc=min+' minute'+(min!==1?'s':'')+' ago'; if(isChinese){ desc=min+(min<10?' 分':' 分钟')+'前'; } }
      else if(hr<24){ desc=hr+' hour'+(hr!==1?'s':'')+' ago'; if(isChinese){ desc=hr+(hr<10?' 小时':' 小时')+'前'; } }
      else {
        if (isChinese) {
          desc = (date.getMonth()+1)+'月'+date.getDate()+'日';
        } else {
          const monthName = new Intl.DateTimeFormat('en-US', {month:'short'}).format(date);
          desc = date.getDate() + ' ' + monthName;
        }
      }
      return desc;
    }
  }
  new PusherChatWidget(new Pusher('91983fb955c5da073f3d',{cluster:'eu'}),{appendTo:document.body});
})();
