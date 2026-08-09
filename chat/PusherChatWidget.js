(() => {
  const isMobile = typeof popup == 'object';
  function isForumMutationRequest(url){
    try {
      const requestUrl = new URL(url, window.location.href);
      if(!requestUrl.pathname.endsWith('/forum.php')) return false;
      return requestUrl.searchParams.get('mod') === 'post' ||
        (requestUrl.searchParams.get('mod') === 'misc' && requestUrl.searchParams.get('action') === 'postdelete');
    } catch(error) {
      return false;
    }
  }
  // All forum mutation transports use this instead of maintaining per-template fields.
  window.KK_addPusherMetadata = function(target, url){
    if(!target || !isForumMutationRequest(url)) return target;
    const metadata = [
      ['pusher_tab_id', window.KK_PUSHER_TAB_ID || '']
    ];
    if(target instanceof HTMLFormElement) {
      metadata.forEach(([name, value]) => {
        let field = target.querySelector('input[name="' + name + '"]');
        if(!field) {
          field = document.createElement('input');
          field.type = 'hidden';
          field.name = name;
          target.appendChild(field);
        }
        field.value = value;
      });
    } else if(typeof target.set === 'function') {
      metadata.forEach(([name, value]) => target.set(name, value));
    }
    return target;
  };
  if(!window.KK_pusherSubmitPatched) {
    const nativeSubmit = HTMLFormElement.prototype.submit;
    HTMLFormElement.prototype.submit = function(){
      window.KK_addPusherMetadata(this, this.action);
      return nativeSubmit.call(this);
    };
    window.KK_pusherSubmitPatched = true;
  }
  function showError(msg){ if(isMobile){ popup.open(msg,'alert'); } else { alert(msg); } }
  function typesetNodes(targets){
    if(typeof MathJax === 'undefined' || typeof MathJax.typesetPromise !== 'function'){
      return Promise.resolve();
    }
    return MathJax.typesetPromise(targets);
  }
  function setVisible(element, visible){
    element.hidden = !visible;
    element.style.display = visible ? '' : 'none';
  }
  function isOwnMessage(activity){
    const uid = Number(window.discuz_uid || 0);
    if(uid > 0){
      return Number(activity.actor?.id) === uid;
    }
    const mySid = window.discuz_sid || '';
    return !!mySid && !!activity.actor?.sessionId && String(activity.actor.sessionId) === String(mySid);
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
  class EventDispatcher {
    #listeners = new Map();
    bind(event, callback){
      if(!this.#listeners.has(event)) this.#listeners.set(event, []);
      this.#listeners.get(event).push(callback);
    }
    emit(event, data){
      (this.#listeners.get(event) || []).forEach(callback => callback(data));
    }
  }
  class LeaderTabPusher {
    #appKey;
    #options;
    #tabId;
    #channel = new EventDispatcher();
    #broadcast;
    #realPusher = null;
    #isLeader = false;
    #leaseTimer;
    #subscriptionReady = false;
    #connectionState = 'disconnected';
    connection = new EventDispatcher();
    constructor(appKey, options){
      this.#appKey = appKey;
      this.#options = options;
      this.#tabId = this.#getTabId();
      window.KK_PUSHER_TAB_ID = this.#tabId;
      this.connection.socket_id = '';
      if(!('BroadcastChannel' in window)) {
        this.#becomeLeader();
        return;
      }
      this.#broadcast = new BroadcastChannel('kuing-pusher-events-v1');
      this.#broadcast.addEventListener('message', event => this.#receive(event.data));
      window.addEventListener('storage', event => {
        if(event.key === 'kuing-pusher-leader-v1') this.#electLeader();
      });
      window.addEventListener('beforeunload', () => this.#releaseLeader());
      this.#electLeader();
      this.#leaseTimer = window.setInterval(() => this.#electLeader(), 2000);
      this.#post({type: 'state-request', tabId: this.#tabId});
    }
    subscribe(){
      return this.#channel;
    }
    #getTabId(){
		const serverToken = typeof window.KK_PUSHER_TAB_ID == 'string' ? window.KK_PUSHER_TAB_ID : '';
		if(serverToken) return serverToken;
      const key = 'kuing-pusher-tab-v1';
      let tabId = '';
      try {
        tabId = sessionStorage.getItem(key) || '';
        if(!tabId) {
          const bytes = new Uint32Array(4);
          crypto.getRandomValues(bytes);
          tabId = Array.from(bytes, value => value.toString(36).padStart(7, '0')).join('');
          sessionStorage.setItem(key, tabId);
        }
      } catch(error) {
        tabId = String(Date.now()) + Math.random().toString(36).slice(2);
      }
      return tabId;
    }
    #post(message){
      this.#broadcast?.postMessage(message);
    }
    #readLease(){
      try {
        return JSON.parse(localStorage.getItem('kuing-pusher-leader-v1') || 'null');
      } catch(error) {
        return null;
      }
    }
    #electLeader(){
      const now = Date.now();
      const lease = this.#readLease();
      if(!lease || lease.expires <= now || lease.tabId === this.#tabId) {
        try {
          localStorage.setItem('kuing-pusher-leader-v1', JSON.stringify({tabId: this.#tabId, expires: now + 5000}));
        } catch(error) {
          this.#becomeLeader();
          return;
        }
      }
      const current = this.#readLease();
      if(current?.tabId === this.#tabId) {
        this.#becomeLeader();
      } else {
        this.#becomeFollower();
      }
    }
    #becomeLeader(){
      if(this.#isLeader) return;
      this.#isLeader = true;
      this.#realPusher = new Pusher(this.#appKey, this.#options);
      ['connected', 'connecting', 'unavailable', 'state_change'].forEach(event => {
        this.#realPusher.connection.bind(event, data => this.#emitConnection(event, data, true));
      });
      const realChannel = this.#realPusher.subscribe('Chat');
      ['pusher:subscription_succeeded', 'newreply', 'editpost', 'commentadd', 'deletepost', 'chat_message', 'chat_delete'].forEach(event => {
        realChannel.bind(event, data => {
          if(event === 'pusher:subscription_succeeded') this.#subscriptionReady = true;
          this.#channel.emit(event, data);
          this.#post({type: 'event', event, data, tabId: this.#tabId});
        });
      });
    }
    #becomeFollower(){
      if(!this.#isLeader) return;
      this.#isLeader = false;
      this.#realPusher?.disconnect();
      this.#realPusher = null;
      this.#subscriptionReady = false;
      this.connection.socket_id = '';
    }
    #emitConnection(event, data, broadcast){
      if(event === 'connected') {
        this.#connectionState = 'connected';
        this.connection.socket_id = this.#isLeader ? (this.#realPusher.connection.socket_id || '') : '';
      } else if(event === 'connecting' || event === 'unavailable') {
        this.#connectionState = event;
      } else if(event === 'state_change') {
        this.#connectionState = data.current;
      }
      this.connection.emit(event, data);
      if(broadcast) this.#post({type: 'connection', event, data, state: this.#connectionState, tabId: this.#tabId});
    }
    #receive(message){
      if(!message || message.tabId === this.#tabId) return;
      if(message.type === 'event') {
        if(message.event === 'pusher:subscription_succeeded') this.#subscriptionReady = true;
        this.#channel.emit(message.event, message.data);
      } else if(message.type === 'connection') {
        this.#connectionState = message.state;
        this.#emitConnection(message.event, message.data, false);
      } else if(message.type === 'state-request' && this.#isLeader) {
        this.#post({type: 'connection', event: this.#connectionState === 'connected' ? 'connected' : this.#connectionState, data: this.#connectionState === 'connected' ? {} : {current: this.#connectionState}, state: this.#connectionState, tabId: this.#tabId});
        if(this.#subscriptionReady) this.#post({type: 'event', event: 'pusher:subscription_succeeded', data: {}, tabId: this.#tabId});
      }
    }
    #releaseLeader(){
      if(!this.#isLeader) return;
      const lease = this.#readLease();
      if(lease?.tabId === this.#tabId) localStorage.removeItem('kuing-pusher-leader-v1');
    }
  }
  function isOriginatingForumTab(data){
    return !!data?.origin_tab_id && data.origin_tab_id === window.KK_PUSHER_TAB_ID;
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
    #lastMessageTime = null;
    #wasDisconnected = false;
    #isSending = false;
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
        this.#widget.querySelector('label').textContent = $L('chat_connected');
      });
      document.addEventListener('submit', event => {
        const form = event.target;
        if(form instanceof HTMLFormElement) window.KK_addPusherMetadata(form, form.action);
      }, true);
      this.#pusher.connection.bind('connecting', () => {
        this.#widget.querySelector('label').textContent = $L('chat_connecting');
      });
      this.#chatChannel.bind('pusher:subscription_succeeded', () => {
        this.#widget.querySelector('label').textContent = $L('chat_shortcut') + ' Ctrl+Enter';
        this.#widget.querySelectorAll('.pusher-chat-widget-send-btn, .pusher-chat-widget-photo-btn').forEach(button => {
          button.disabled = false;
        });
      });
      this.#pusher.connection.bind('unavailable', () => {
        this.#widget.querySelector('label').textContent = $L('chat_network_unavailable');
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
          if(isOriginatingForumTab(data)) return;
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
              const msg = $L('chat_new_reply_message');
              const title = $L('chat_new_reply_title');
              const confirmTxt = $L('chat_jump');
              const cancelTxt = $L('chat_stay');
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
          if(isOriginatingForumTab(data)) return;
          if(data.tid==tid && document.getElementById(`pid${data.pid}`)){
            ajaxget(`forum.php?mod=viewthread&tid=${tid}&viewpid=${data.pid}`, `post_${data.pid}`, 'ajaxwaitid', '', null, "if (typeof MathJax !== 'undefined' && typeof MathJax.typesetPromise === 'function') {MathJax.texReset();MathJax.typesetPromise(['#pid"+data.pid+" :is(div.pcb>h2, td.t_f)'])}");
            if(data.subject){
              document.getElementById('thread_subject').innerHTML = data.subject;
              typesetNodes(['#thread_subject']).catch(err => { showError($L('chat_mathjax_error', [err])); });
            }
            if(document.querySelector('input[name=pid]')?.value==data.pid && discuz_uid!=data.uid){
              showDialog($L('chat_post_edited'));
            }
          }
        });
        this.#chatChannel.bind('commentadd', data => {
          if(isOriginatingForumTab(data)) return;
          if(data.tid==tid && document.getElementById(`pid${data.pid}`)){
            ajaxget('forum.php?mod=misc&action=commentmore&tid='+tid+'&pid='+data.pid, 'comment_'+data.pid, 'ajaxwaitid', '', null, "if (typeof MathJax !== 'undefined' && typeof MathJax.typesetPromise === 'function') {MathJax.typesetPromise(['#comment_"+data.pid+"'])}");
          }
        });
        this.#chatChannel.bind('deletepost', data => {
          if(isOriginatingForumTab(data)) return;
          if(data.tid==tid){
            const post = document.getElementById(`pid${data.pid}`) || document.getElementById(`post_${data.pid}`);
            if(!post) return;
            post.remove();
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
      this.#chatChannel.bind('chat_delete', data => {
        if(data && data.message_time){
          PusherChatWidget.instances.forEach(instance => instance.#removeChatMessage(data.message_time));
        }
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
        if (!e.repeat && (e.ctrlKey || e.metaKey) && e.key === 'Enter') {
          this.#sendChatButtonClicked();
        }
      });
      this.#startTimeMonitor();
      this.#loadMoreButton.addEventListener('click', () => { this.#loadHistory(true); });
    }
    async #loadHistory(isLoadingMore){
      if(isLoadingMore){
        this.#loadMoreButton.textContent = $L('chat_loading');
        this.#loadMoreButton.disabled = true;
      }
      try {
        const response = await requestJSON('/chat/php/history.php?offset=' + encodeURIComponent(this.#messagesLoaded));
        if(!window.discuz_sid && response.sessionId){
          window.discuz_sid = response.sessionId;
        }
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
            this.#loadMoreButton.textContent = $L('chat_load_more');
            this.#loadMoreButton.disabled = false;
          }
        }
        else if(!isLoadingMore){
          setVisible(this.#loadMoreButton, false);
        }
      } catch(error) {
        showError($L('chat_history_error', [error.message]));
        if(isLoadingMore){
          this.#loadMoreButton.textContent = $L('chat_load_failed');
          this.#loadMoreButton.disabled = false;
        }
      }
    }
    async #fetchMissedMessages(){
      if(!this.#lastMessageTime) return;
      try {
        const response = await requestJSON('/chat/php/history.php?offset=0&limit=100');
        const data = response.messages || [];
        const newMessages = [];
        for (let i = 0; i < data.length; ++i) {
          if(String(data[i].message_time || '') > this.#lastMessageTime) {
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
        showError($L('chat_missed_error', [error.message]));
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
    #removeChatMessage(messageTime){
      const key = String(messageTime);
      this.#pendingMessages = this.#pendingMessages.filter(entry => String(entry.data.message_time || entry.data.id || '') !== key);
      this.#messagesEl.querySelectorAll('li.message-item').forEach(li => {
        if(li.dataset.messageTime === key){
          li.remove();
          this.#itemCount--;
        }
      });
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
      if(!this.#lastMessageTime || String(entry.data.message_time || '') > this.#lastMessageTime) {
        this.#lastMessageTime = String(entry.data.message_time || '');
      }
      if(isOwnMessage(entry.data)) {
        this.#addDeleteHandlers(entry.messageEl, entry.data);
      }
      typesetNodes([entry.messageEl]).catch(err=>{ showError($L('chat_mathjax_error', [err])); });
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
      if(this.#isSending) return;
      const message = this.#messageInputEl.value.trim();
      if(!message){
        showError($L('chat_message_empty'));
        this.#messageInputEl.focus();
        return;
      }
      const chatInfo = {text: message};
      this.#isSending = true;
      this.#sendChatMessage(chatInfo).finally(() => {
        this.#isSending = false;
      });
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
          showError($L('chat_message_too_long'));
        }else{
          showError($L('chat_network_error', [error.message]));
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
        showError($L('chat_invalid_image'));
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
            showError(response?.error || $L('chat_upload_failed'));
          }
        })
        .catch(error => {
          showError($L('chat_upload_error', [error.message]));
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
        '<li class="pusher-chat-widget-load-more" style="display:none;">'+$L('chat_load_more')+'</li>'+
        '</ul></div>'+
        '<div class="pusher-chat-widget-input">'+
        '<label for="message"></label><textarea id="message" placeholder="'+$L('chat_message_placeholder')+'"></textarea>'+
        '<input type="file" class="pusher-chat-widget-photo-input" accept="image/*" style="display:none;" />'+
        '<button type="button" class="pusher-chat-widget-photo-btn" title="'+$L('chat_add_photo')+'" disabled>'+addPhotoSvg+'</button>'+
        '<button type="button" class="pusher-chat-widget-send-btn" title="'+$L('chat_send_message')+'" disabled>'+sendSvg+'</button>'+
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
      li.dataset.messageTime = activity.message_time || activity.id || '';
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
      li.append(contentWrapper);
      if(isOwnMessage(activity)) {
        li.classList.add('own-message');
        const deleteAction = document.createElement('div');
        deleteAction.className = 'delete-action';
        const deleteButton = document.createElement('button');
        deleteButton.className = 'delete-button';
        deleteButton.textContent = $L('delete');
        deleteAction.append(deleteButton);
        const hoverDeleteButton = document.createElement('button');
        hoverDeleteButton.type = 'button';
        hoverDeleteButton.className = 'delete-hover-button';
        hoverDeleteButton.title = $L('delete');
        hoverDeleteButton.setAttribute('aria-label', $L('delete'));
        hoverDeleteButton.innerHTML = '<svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>';
        li.append(deleteAction, hoverDeleteButton);
      }
      return li;
    }
    #addDeleteHandlers(liElement,message){
      const threshold = 50;
      // Touch: swipe left to reveal the red .delete-action panel, swipe right/tap away to dismiss.
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
        if(e.target.closest('.delete-button, .delete-hover-button')){
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
      // Desktop: the hover-revealed .delete-hover-button sits in the top-right corner;
      // both buttons trigger the same deletion.
      liElement.querySelectorAll('.delete-button, .delete-hover-button').forEach(deleteButton => {
        deleteButton.addEventListener('click',async e=>{
          e.stopPropagation();
          await this.#deleteChatMessage(liElement,message);
        });
      });
    }
    async #deleteChatMessage(liElement,message){
      const body = new URLSearchParams({
        message_time: message.message_time,
        formhash: typeof FORMHASH !== 'undefined' ? FORMHASH : ''
      });
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
        showError($L('chat_delete_failed', [error.message]));
      }
    }
    static timeToDescription(time){
      const now=new Date();
      const date=new Date(time);
      const diff=now-date;
      const sec=Math.floor(diff/1000);
      const min=Math.floor(sec/60);
      const hr=Math.floor(min/60);
      let desc;
      if(sec<=0){ desc=$L('chat_just_now'); }
      else if(min<1){ desc=$L('chat_seconds_ago', [sec]); }
      else if(min<60){ desc=$L('chat_minutes_ago', [min]); }
      else if(hr<24){ desc=$L('chat_hours_ago', [hr]); }
      else {
        const locale = _i18n_ === 'TC' ? 'zh-TW' : (_i18n_ === 'SC' ? 'zh-CN' : 'en-US');
        desc = new Intl.DateTimeFormat(locale, {month:'short', day:'numeric'}).format(date);
      }
      return desc;
    }
  }
  new PusherChatWidget(new LeaderTabPusher('91983fb955c5da073f3d',{cluster:'eu'}),{appendTo:document.body});
})();
