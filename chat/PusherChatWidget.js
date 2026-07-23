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
        this.#widget.find('.pusher-chat-widget-send-btn, .pusher-chat-widget-photo-btn').prop('disabled', false);
      });
      this.#pusher.connection.bind('unavailable', () => {
        this.#widget.find('label').text(isChinese ? '请检查网络连接' : 'Please check your network connection');
        this.#widget.find('.pusher-chat-widget-send-btn, .pusher-chat-widget-photo-btn').prop('disabled', true);
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
          if(data.tid==tid && jQuery(`#pid${data.pid}`).length){
            ajaxget(`forum.php?mod=viewthread&tid=${tid}&viewpid=${data.pid}`, `post_${data.pid}`, 'ajaxwaitid', '', null, "if (typeof MathJax !== 'undefined' && typeof MathJax.typesetPromise === 'function') {MathJax.texReset();MathJax.typesetPromise(['#pid"+data.pid+" :is(div.pcb>h2, td.t_f)'])}");
            if(data.subject){
              jQuery('#thread_subject').html(data.subject);
              typesetNodes(['#thread_subject']).catch(err => { showError('MathJax typesetting error:'+err); });
            }
            if(document.querySelector('input[name=pid]')?.value==data.pid && discuz_uid!=data.uid){
              showDialog(isChinese?'帖子已被编辑':'Post has been edited');
            }
          }
        });
        this.#chatChannel.bind('commentadd', data => {
          if(data.tid==tid && jQuery(`#pid${data.pid}`).length){
            ajaxget('forum.php?mod=misc&action=commentmore&tid='+tid+'&pid='+data.pid, 'comment_'+data.pid, 'ajaxwaitid', '', null, "if (typeof MathJax !== 'undefined' && typeof MathJax.typesetPromise === 'function') {MathJax.typesetPromise(['#comment_"+data.pid+"'])}");
          }
        });
        this.#chatChannel.bind('deletepost', data => {
          if(data.tid==tid && jQuery(`#post_${data.pid}`).length){
            jQuery(`#post_${data.pid}`).remove();
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
      this.#widget.find('.pusher-chat-widget-photo-btn').click(() => {
        this.#widget.find('.pusher-chat-widget-photo-input').click();
      });
      this.#widget.find('.pusher-chat-widget-photo-input').change(e => {
        this.#handlePhotoUpload(e.target);
      });
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
      typesetNodes([entry.messageEl[0]]).catch(err=>{ showError('MathJax typesetting error:'+err); });
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
        data:{'formhash': typeof FORMHASH !== 'undefined' ? FORMHASH : '', 'chat_info':data},
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
      const $photoBtn = this.#widget.find('.pusher-chat-widget-photo-btn');
      $photoBtn.prop('disabled', true).addClass('loading');

      jQuery.ajax({
        url: '/chat/php/upload.php',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: response => {
          if(response && response.status === 200 && response.url){
            this.#sendChatMessage({text: response.url});
          }else{
            showError(response && response.error ? response.error : (isChinese ? '图片上传失败' : 'Failed to upload photo'));
          }
        },
        error: (xhr, status, error) => {
          let errDetail = error;
          try {
            const res = JSON.parse(xhr.responseText);
            if(res.error) errDetail = res.error;
          } catch(e){}
          showError(isChinese ? ('图片上传错误: ' + errDetail) : ('Photo upload error: ' + errDetail));
        },
        complete: () => {
          $photoBtn.prop('disabled', false).removeClass('loading');
          inputElement.value = '';
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
      const widget=jQuery(html);
      jQuery(appendTo).append(widget);
      return widget;
    }
    static _buildListItem(activity){
      const li = jQuery('<li></li>').addClass('message-item');
      const contentWrapper = jQuery('<div class="message-content-wrapper"></div>');
      const avatar = jQuery('<img class="user_avatar" width="24" height="24" />')
        .attr('data-avatar-key', activity.actor.displayName)
        .attr('data-avatar-name', activity.actor.displayName)
        .attr('alt', activity.actor.displayName)
        .on('error', function(){ renderInitialAvatar(this); });
      const image = jQuery('<div class="image"></div>').append(avatar);
      if(activity.actor.image){
        avatar.attr('src', activity.actor.image);
      }else{
        renderInitialAvatar(avatar[0]);
      }
      const content = jQuery('<div class="content"></div>');
      const user = jQuery('<div class="activity-row"><span class="user-name"><a class="screen-name">'+activity.actor.displayName.replace(/\\'/g,"'")+'</a><a '+(activity.link?'href="'+activity.link+'" ':'')+'class="timestamp"><span data-activity-published="'+activity.published+'">'+PusherChatWidget.timeToDescription(activity.published)+'</span></a></span></div>');
      content.append(user);
      let bodyText = activity.body || '';
      if (window.location.protocol === 'https:') {
        bodyText = bodyText.replace(/http:\/\/([^\/]+)(\/data\/attachment\/)/gi, '$2');
      }
      const textHtml = bodyText.replace(/(https?:\/\/\S+\b|\/data\/attachment\/\S+\b)/gi,m=>(/\.(png|jpe?g|gif|bmp|svg|webp)$/i.test(m)?'<img src="'+m+'" />':'<a href="'+m+'">'+m+'</a>')).replace(/\n/g,'<br>');
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
            liElement.slideUp(() => { liElement.remove(); this.#itemCount--; });
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
