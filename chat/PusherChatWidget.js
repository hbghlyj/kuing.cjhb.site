(() => {
  const isChinese = navigator.languages && navigator.languages.some(lang => ['zh','zh-CN','zh-TW','zh-HK','zh-SG'].includes(lang));
  const isMobile = typeof popup == 'object';
  function showError(msg){ if(isMobile){ popup.open(msg,'alert'); } else { alert(msg); } }
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
      this.settings = jQuery.extend({
        chatEndPoint: '/chat/php/chat.php',
        channelName: 'Chat',
        appendTo: document.body,
        debug: true
      }, options);
      this.#widget = PusherChatWidget._createHTML(this.settings.appendTo);
      this.#messageInputEl = this.#widget.find('textarea');
      this.#messagesEl = this.#widget.find('ul');
      this.#loadMoreButton = this.#widget.find('.pusher-chat-widget-load-more');
      this.isCollapsed = document.cookie.replace(/(?:(?:^|.*;\s*)isCollapsed\s*=\s*([^;]*).*$)|^.*$/, '$1') == 'true';
      if(isMobile) this.isCollapsed = false;
      this.#chatChannel = this.#pusher.subscribe(this.settings.channelName);
      this.#pusher.connection.bind('connected', () => {
        this.#widget.find('label').text(isChinese ? '已连接' : 'Connected');
      });
      this.#pusher.connection.bind('connecting', () => {
        this.#widget.find('label').text(isChinese ? '连接中' : 'Connecting');
      });
      this.#chatChannel.bind('pusher:subscription_succeeded', () => {
        this.#widget.find('label').text((isChinese ? '快捷键' : 'Shortcut') + ' Ctrl+Enter');
        this.#widget.find('.pusher-chat-widget-send-btn').prop('disabled', false);
      });
      this.#pusher.connection.bind('unavailable', () => {
        this.#widget.find('label').text(isChinese ? '请检查网络连接' : 'Please check your network connection');
        this.#widget.find('.pusher-chat-widget-send-btn').prop('disabled', true);
      });
      this.#pusher.connection.bind('state_change', states => {
        if(states.current==='disconnected'||states.current==='unavailable'){
          this.#wasDisconnected = true;
        }else if(states.current==='connected' && this.#wasDisconnected){
          this.#fetchMissedMessages();
          this.#wasDisconnected = false;
        }
      });
      if(this.isCollapsed){
        this.#widget.find('.pusher-chat-widget-messages').hide();
        this.#widget.find('.pusher-chat-widget-input').hide();
        this.#widget.find('.toggle-icon').html('<path d="M7 14l5-5 5 5z"/>');
        if(!isMobile){
          this.#widget.find('.pusher-chat-widget-header').one('click', () => {
            this.#widget.find('.pusher-chat-widget-messages').slideToggle();
            this.#widget.find('.pusher-chat-widget-input').slideToggle();
            document.cookie = 'isCollapsed=false; path=' + location.pathname;
            this.isCollapsed = false;
            this.#widget.find('.toggle-icon').html('<path d="M7 10l5 5 5-5z"/>');
            this.#init();
          });
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
        this.#widget.find('.pusher-chat-widget-header').click(() => {
          this.#widget.find('.pusher-chat-widget-messages').slideToggle();
          this.#widget.find('.pusher-chat-widget-input').slideToggle();
          this.isCollapsed = !this.isCollapsed;
          document.cookie = 'isCollapsed=' + this.isCollapsed + '; path=' + location.pathname;
          this.#widget.find('.toggle-icon').html(this.isCollapsed ? '<path d="M7 14l5-5 5 5z"/>' : '<path d="M7 10l5 5 5-5z"/>');
        });
      }
        this.#widget.find('.pusher-chat-widget-send-btn').click(() => this.#sendChatButtonClicked());
        this.#messageInputEl.keydown(e => {
          if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            this.#sendChatButtonClicked();
          }
        });
      this.#startTimeMonitor();
      this.#loadMoreButton.click(() => { this.#loadHistory(true); });
    }
    #loadHistory(isLoadingMore){
      if(isLoadingMore){
        this.#loadMoreButton.text(isChinese ? '加载中...' : 'Loading...').prop('disabled', true);
      }
      jQuery.ajax({
        url:'/chat/php/history.php',
        type:'get',
        dataType:'json',
        data:{offset:this.#messagesLoaded},
        success:response=>{
          const data = response.messages;
          this.#totalMessages = response.total;
          if (data && data.length > 0) {
            for (let i = 0; i < data.length; ++i) {
              this.#chatMessageReceived(data[i],false,isLoadingMore);
            }
            if(!isLoadingMore){
              this.#onQueueDrainedCallback=()=>{
                this.#messagesEl.scrollTop(this.#messagesEl[0].scrollHeight);
                this.#onQueueDrainedCallback=null;
              };
            }
            this.#processPendingMessages();
            this.#messagesLoaded += data.length;
            if(this.#messagesLoaded >= this.#totalMessages){
              this.#loadMoreButton.hide();
            }else{
              this.#loadMoreButton.show().text(isChinese ? '加载更多' : 'Load More').prop('disabled', false);
            }
          }else if(!isLoadingMore){
            this.#loadMoreButton.hide();
          }
        },
        error:(xhr,status,error)=>{
          showError('Error fetching history:'+status+error);
          if(isLoadingMore){
            this.#loadMoreButton.text(isChinese ? '加载失败' : 'Failed to load').prop('disabled', false);
          }
        }
      });
    }
    #fetchMissedMessages(){
      if(!this.#lastMessageTimestamp) return;
      jQuery.ajax({
        url:'/chat/php/history.php',
        type:'get',
        dataType:'json',
        data:{offset:0,limit:100},
        success:response=>{
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
        }
      });
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
      let images=messageEl.find('img');
      let oldScrollHeight=0;
      let oldScrollTop=0;
      if(currentEntry.isPrepending){
        oldScrollHeight=this.#messagesEl[0].scrollHeight;
        oldScrollTop=this.#messagesEl.scrollTop();
      }
      if(images.length===0){
        this.#actuallyAppendMessage(currentEntry,oldScrollHeight,oldScrollTop);
      }else{
        let loaded=0;
        images.each((_,img)=>{
          jQuery(img).on('load error',()=>{
            if(++loaded===images.length){
              this.#actuallyAppendMessage(currentEntry,oldScrollHeight,oldScrollTop);
            }
          });
          if(img.complete||img.naturalWidth>0){ jQuery(img).trigger('load'); }
        });
      }
    }
    #actuallyAppendMessage(entry,oldScrollHeight,oldScrollTop){
      this.#pendingMessages.shift();
      if(entry.isPrepending){
        entry.messageEl.insertAfter(this.#loadMoreButton);
      }else{
        this.#messagesEl.append(entry.messageEl);
      }
      this.#lastMessageTimestamp=entry.data.published;
      if(isMobile){ this.#addSwipeToDeleteHandlers(entry.messageEl, entry.data.published); }
      if(typeof MathJax!=='undefined' && typeof MathJax.typesetPromise==='function'){
        MathJax.typesetPromise([entry.messageEl[0]]).catch(err=>{ showError('MathJax typesetting error:'+err); });
      }
      this.#itemCount++;
      if(entry.isLiveMessage){
        this.#messagesEl.animate({scrollTop:this.#messagesEl[0].scrollHeight},500);
      }else if(entry.isPrepending){
        let newScrollHeight=this.#messagesEl[0].scrollHeight;
        this.#messagesEl.scrollTop(oldScrollTop+(newScrollHeight-oldScrollHeight));
      }
      this.#isProcessingPendingMessages=false;
      this.#processPendingMessages();
    }
    #sendChatButtonClicked(){
      const message = jQuery.trim(this.#messageInputEl.val());
      if(!message){
        showError(isChinese ? '请输入聊天信息' : 'Please enter a chat message');
        this.#messageInputEl.focus();
        return;
      }
      const chatInfo = {text: message};
      this.#sendChatMessage(chatInfo);
    }
    #sendChatMessage(data){
      this.#messageInputEl.attr('readonly','readonly');
      const $btn = this.#widget.find('.pusher-chat-widget-send-btn');
      $btn.prop('disabled',true).addClass('loading');
      jQuery.ajax({
        url:this.settings.chatEndPoint,
        type:'post',
        dataType:'json',
        data:{'chat_info':data},
        complete:(xhr)=>{
          if(xhr.status===200){
            this.#messageInputEl.val('');
          }else if(xhr.status===413){
            showError(isChinese ? '聊天信息过长' : 'Chat message too long');
          }else{
            showError(isChinese ? '发送失败' : 'Failed to send message');
          }
          this.#messageInputEl.removeAttr('readonly');
          $btn.prop('disabled',false).removeClass('loading');
        },
        error:(xhr,status,error)=>{
          showError(isChinese ? ('网络错误: '+error) : ('Network error: '+error));
          this.#messageInputEl.removeAttr('readonly');
          $btn.prop('disabled',false).removeClass('loading');
        }
      });
    }
    #startTimeMonitor(){
      setInterval(()=>{
        this.#messagesEl.find('a.timestamp span[data-activity-published]').each((i,el)=>{
          const time = jQuery(el).attr('data-activity-published');
          const desc = PusherChatWidget.timeToDescription(time);
          jQuery(el).text(desc);
        });
      },10000);
    }
    static _createHTML(appendTo){
      const html='<div class="pusher-chat-widget">'+
        (isMobile?'':'<div class="pusher-chat-widget-header"><svg class="toggle-icon" width="24" height="24" viewBox="0 0 24 24"><path d="M7 10l5 5 5-5z"/></svg></div>')+
        '<div class="pusher-chat-widget-messages"><ul class="activity-stream">'+
        '<li class="pusher-chat-widget-load-more" style="display:none;">'+(isChinese?'加载更多':'Load More')+'</li>'+
        '</ul></div>'+
        '<div class="pusher-chat-widget-input"><label for="message"></label><textarea id="message"></textarea><button class="pusher-chat-widget-send-btn" disabled>'+(isChinese?'发送':'Send')+'</button></div>'+
        '</div>';
      const widget=jQuery(html);
      jQuery(appendTo).append(widget);
      return widget;
    }
    static _buildListItem(activity){
      const li = jQuery('<li></li>').addClass('message-item');
      const contentWrapper = jQuery('<div class="message-content-wrapper"></div>');
      const image = jQuery('<div class="image"><img src="'+activity.actor.image+'" width="24" height="24" /></div>');
      const content = jQuery('<div class="content"></div>');
      const user = jQuery('<div class="activity-row"><span class="user-name"><a class="screen-name">'+activity.actor.displayName.replace(/\\'/g,"'")+'</a><a '+(activity.link?'href="'+activity.link+'" ':'')+'class="timestamp"><span data-activity-published="'+activity.published+'">'+PusherChatWidget.timeToDescription(activity.published)+'</span></a></span></div>');
      content.append(user);
      const textHtml = activity.body.replace(/(https?:\/\/\S+\b)/g,m=>(/\.(png|jpe?g|gif|bmp|svg|webp)$/i.test(m)?'<img src="'+m+'" />':'<a href="'+m+'">'+m+'</a>')).replace(/\n/g,'<br>');
      const message = jQuery('<div class="activity-row"><div class="text">'+textHtml+'</div></div>');
      content.append(message);
      contentWrapper.append(image).append(content);
      const deleteAction = jQuery('<div class="delete-action"><button class="delete-button">'+(isChinese?'删除':'Delete')+'</button></div>');
      li.append(contentWrapper).append(deleteAction);
      return li;
    }
    #addSwipeToDeleteHandlers(liElement,published){
      const threshold = 50;
      liElement.on('touchstart',e=>{
        this.#messagesEl.find('li.slide-active').not(liElement).removeClass('slide-active');
        const touchStartX = e.originalEvent.touches[0].clientX;
        liElement.data('touchStartX', touchStartX);
      });
      liElement.on('touchend',e=>{
        const touchStartX = liElement.data('touchStartX');
        if(typeof touchStartX==='undefined') return;
        const endX = e.originalEvent.changedTouches[0].clientX;
        const deltaX = endX - touchStartX;
        if(jQuery(e.target).closest('.delete-button').length){
          liElement.removeData('touchStartX');
          return;
        }
        if(deltaX<-threshold){
          liElement.addClass('slide-active');
        }else if(deltaX>threshold){
          liElement.removeClass('slide-active');
        }
        liElement.removeData('touchStartX');
      });
      liElement.find('.delete-button').on('click',e=>{
        e.stopPropagation();
        jQuery.ajax({
          url:'/chat/php/delete.php',
          type:'POST',
          data:{'published_time':published},
          success:()=>{
            liElement.slideUp(()=>{ jQuery(this).remove(); this.#itemCount--; });
          },
          error:(xhr,status,error)=>{
            showError((isChinese?'删除消息失败':'Error deleting message: ')+status+error);
          }
        });
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
