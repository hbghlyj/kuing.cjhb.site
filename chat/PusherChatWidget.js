(function(){
const isChinese = navigator.languages && (navigator.languages.indexOf('zh') !== -1 || navigator.languages.indexOf('zh-CN') !== -1 || navigator.languages.indexOf('zh-TW') !== -1 || navigator.languages.indexOf('zh-HK') !== -1 || navigator.languages.indexOf('zh-SG') !== -1);
const isMobile = location.search.startsWith('?mod=find');

/**
 * Creates an instance of a PusherChatWidget, binds to a chat channel on the pusher instance and
 * and creates the UI for the chat widget.
 *
 * @param {Pusher} pusher The Pusher object used for the chat widget.
 * @param {Map} options A hash of key value options for the widget.
 */
function PusherChatWidget(pusher, options) {
  PusherChatWidget.instances.push(this);
  var self = this;
  
  this._pusher = pusher;
  
  options = options || {};
  this.settings = jQuery.extend({
    maxItems: 50, // max items to show in the UI. Items beyond this limit will be removed as new ones come in.
    chatEndPoint: '/chat/php/chat.php', // the end point where chat messages should be sanitized and then triggered
    channelName: 'Chat', // the name of the channel the chat will take place on
    appendTo: document.body, // A jQuery selector or object. Defines where the element should be appended to
    debug: true
  }, options);

  this._itemCount = 0;
  this._widget = PusherChatWidget._createHTML(this.settings.appendTo);
  this._messageInputEl = this._widget.find('textarea');
  this._messagesEl = this._widget.find('ul');

  // Subscribe to the chat channel
  this._chatChannel = this._pusher.subscribe(this.settings.channelName);
  this._pusher.connection.bind('connected', function() {
    self._widget.find('label').text(isChinese ? '已连接' : 'Connected');
  });
  this._pusher.connection.bind('connecting', function() {
    self._widget.find('label').text(isChinese ? '连接中' : 'Connecting');
  });
  this._chatChannel.bind('pusher:subscription_succeeded', function(){
    self._widget.find('label').text((isChinese ? '快捷键' : 'Shortcut') + ' Ctrl+Enter');
    self._widget.find('.pusher-chat-widget-send-btn').prop('disabled', false);
  });
  this._pusher.connection.bind('unavailable', function() {
    self._widget.find('label').text((isChinese ? '请检查网络连接' : 'Please check your network connection'));
    self._widget.find('.pusher-chat-widget-send-btn').prop('disabled', true);
  });
  if(typeof currentPage !== 'undefined' && typeof tid !== 'undefined') {
    this._chatChannel.bind('newreply', function(data) {
      if(data.tid == tid && data.page == currentPage) {
        ajaxget(`forum.php?mod=viewthread&tid=${tid}&viewpid=${data.pid}`, 'post_new', 'ajaxwaitid', '', null, `appendreply(${data.pid})`);
      }
    });
    this._chatChannel.bind('editpost', function(data) {
      if(data.tid == tid && $(`pid${data.pid}`)) {
        ajaxget(`forum.php?mod=viewthread&tid=${tid}&viewpid=${data.pid}`, `post_${data.pid}`, 'ajaxwaitid', '', null, "if (typeof MathJax.typesetPromise === 'function') {MathJax.texReset();MathJax.typesetPromise([document.querySelector('#pid"+data.pid+" .t_f')]);}");
        if(data.subject){
          $('thread_subject').innerText=data.subject;
          if (typeof MathJax.typesetPromise === 'function') {MathJax.typesetPromise([$('thread_subject')]);}
        }
        if(document.querySelector("input[name=pid]")?.value == data.pid && discuz_uid != data.uid) {
          // the edit form is open for this post, notify the user that the post has been updated
          showDialog(isChinese ? '帖子已被编辑' : 'Post has been edited');
        }
      }
    });
    this._chatChannel.bind('commentadd', function(data) {
      if(data.tid == tid && data.page == currentPage && $(`pid${data.pid}`)) {
        ajaxget('forum.php?mod=misc&action=commentmore&tid=' + tid + '&pid=' + data.pid, 'comment_' + data.pid, 'ajaxwaitid', '', null, "if (typeof MathJax.typesetPromise === 'function') {MathJax.typesetPromise([document.getElementById('comment_"+data.pid+"')]);}");
      }
    });
    this._chatChannel.bind('deletepost', function(data) {
      if(data.tid == tid && $(`post_${data.pid}`)) {
        $(`post_${data.pid}`).remove();
        MULUSELECT.querySelector(`option[value="post_${data.pid}"]`).remove();
        MULUSELECT.size--;
        if(MULUSELECT.size < 2 || $('postlist').clientHeight < window.innerHeight) {
          MULUSELECT.style.display = 'none';
        } else {
          MULUSELECT.style.height = MULUSELECT.lastChild.offsetHeight + MULUSELECT.lastChild.offsetTop - MULUSELECT.firstChild.offsetTop + 'px';
        }
      }
    });
  }
  // Read collapse/expand status from cookie
 if(document.cookie.replace(/(?:(?:^|.*;\s*)isCollapsed\s*\=\s*([^;]*).*$)|^.*$/, "$1") == ''){
    document.cookie = "isCollapsed=true; path=/forum.php";
    document.cookie = "isCollapsed=true; path=/member.php";
    document.cookie = "isCollapsed=true; path=/connect.php";
    document.cookie = "isCollapsed=true; path=/misc.php";
    document.cookie = "isCollapsed=true; path=/home.php";
  }
  this.isCollapsed = document.cookie.replace(/(?:(?:^|.*;\s*)isCollapsed\s*\=\s*([^;]*).*$)|^.*$/, "$1") == 'true';
  if (isMobile) {
    this.isCollapsed = false;
  }
  if (this.isCollapsed) {
    this._widget.find('.pusher-chat-widget-messages').hide();
    this._widget.find('.pusher-chat-widget-input').hide();
    this._widget.find('.toggle-icon').html('<path d="M7 14l5-5 5 5z"/>'); // Upward triangle
    // Once event listener, expand the widget and intialize the widget
    if(!isMobile) {
      self._widget.find('.pusher-chat-widget-header').one('click', function() {
        self._widget.find('.pusher-chat-widget-messages').slideToggle();
        self._widget.find('.pusher-chat-widget-input').slideToggle();
        document.cookie = "isCollapsed=false; path=/forum.php";
        self.isCollapsed = false;
        self._widget.find('.toggle-icon').html('<path d="M7 10l5 5 5-5z"/>');
        self._init();
      });
    }
  } else {
    this._init();
  }
};
PusherChatWidget.instances = [];

/* @private */
PusherChatWidget.prototype._init = function() {
  var self = this;
  // Fetch history messages
  jQuery.ajax({
    url: '/chat/php/history.php',
    type: 'get',
    dataType: 'json',
    success: function(data) {
      data.forEach(function(message) {
        self._chatMessageReceived(message);
      });
      self._messagesEl.scrollTop(self._messagesEl.prop("scrollHeight"));
    },
    error: function(xhr, status, error) {
      self._messagesEl.html('<li class="error">' + (isChinese ? '无法获取聊天历史:' : 'Failed to fetch chat history:') + error + '</li>');
    }
  });
  
  this._chatChannel.bind('chat_message', function(data) {
    self._chatMessageReceived(data);
    self._messagesEl.animate({scrollTop: self._messagesEl.prop("scrollHeight")}, 500);
  });
  
  // Toggle collapse/expand status
  if(!isMobile) {
    self._widget.find('.pusher-chat-widget-header').click(function() {
      self._widget.find('.pusher-chat-widget-messages').slideToggle();
      self._widget.find('.pusher-chat-widget-input').slideToggle();
      self.isCollapsed = !self.isCollapsed;
      document.cookie = "isCollapsed=" + self.isCollapsed + "; path=" + location.pathname;
      self._widget.find('.toggle-icon').html(self.isCollapsed ? '<path d="M7 14l5-5 5 5z"/>' : '<path d="M7 10l5 5 5-5z"/>');
    });
  }

  // Add send button functionality
  this._widget.find('.pusher-chat-widget-send-btn').click(function() {
    self._sendChatButtonClicked();
  });
  // Shortcut Ctrl+Enter to send message
  this._messageInputEl.keydown(function(e) {
    if (e.ctrlKey && e.keyCode === 13) {
      self._sendChatButtonClicked();
    }
  });
  // Update the UI with the current time every 10 seconds
  this._startTimeMonitor();
}

/* @private */
PusherChatWidget.prototype._chatMessageReceived = function(data) {
  if(this._itemCount === 0) {
    this._messagesEl.html('');
  }
  
  var messageEl = PusherChatWidget._buildListItem(data);
  this._messagesEl.append(messageEl);

  if (isMobile) {
    this._addSwipeToDeleteHandlers(messageEl, data);
  }

  if (typeof MathJax.typesetPromise === 'function') {
    MathJax.typesetPromise([messageEl.find('.text').get(0)]);
  }
  
  ++this._itemCount;
  
  if(this._itemCount > this.settings.maxItems) {
    /* get first li of list */
    this._messagesEl.children(':first').slideUp(function() {
      jQuery(this).remove();
    });
  }
};

/* @private */
PusherChatWidget.prototype._sendChatButtonClicked = function() {
  var message = jQuery.trim(this._messageInputEl.val());
  if(!message) {
    showError(isChinese ? '请输入聊天信息' : 'Please enter a chat message');
    this._messageInputEl.focus();
    return;
  }

  var chatInfo = {
    text: message
  };
  this._sendChatMessage(chatInfo);
};

/* @private */
PusherChatWidget.prototype._sendChatMessage = function(data) {
  var self = this;
  
  this._messageInputEl.attr('readonly', 'readonly');
  // Get button and disable it
  var $sendBtn = this._widget.find('.pusher-chat-widget-send-btn');
  $sendBtn.prop('disabled', true);
  $sendBtn.addClass('loading');

  jQuery.ajax({
    url: this.settings.chatEndPoint,
    type: 'post',
    dataType: 'json',
    data: {
      'chat_info': data
    },
    complete: function(xhr, status) {
      if(xhr.status === 200) {
        self._messageInputEl.val('');
      }else if(xhr.status === 413) {
        showError(isChinese ? '聊天信息过长' : 'Chat message too long');
      }else{
        showError(isChinese ? '发送失败' : 'Failed to send message');
      }
      self._messageInputEl.removeAttr('readonly');
      // Re-enable button and clear loading image
      $sendBtn.prop('disabled', false);
      $sendBtn.removeClass('loading');
    },
    error: function(xhr, status, error) {
      showError(isChinese ? ('网络错误: ' + error) : ('Network error: ' + error));
      self._messageInputEl.removeAttr('readonly');
      $sendBtn.prop('disabled', false);
      $sendBtn.removeClass('loading');
    }
  })
};

/* @private */
PusherChatWidget.prototype._startTimeMonitor = function() {
  var self = this;
  
  setInterval(function() {
    self._messagesEl.find('a.timestamp span[data-activity-published]').each(function(i, el) {
      var time = jQuery(el).attr('data-activity-published');
      var newDesc = PusherChatWidget.timeToDescription(time);
      jQuery(el).text(newDesc);
    });
  }, 10 * 1000)
};

/* @private */
PusherChatWidget._createHTML = function(appendTo) {
  var html = '' +
  '<div class="pusher-chat-widget">' +
  (isMobile ? '' :(
    '<div class="pusher-chat-widget-header">' +
      '<svg class="toggle-icon" width="24" height="24" viewBox="0 0 24 24">' +
        '<path d="M7 10l5 5 5-5z"/>' + // Downward triangle
      '</svg>' +
    '</div>')) +
    '<div class="pusher-chat-widget-messages">' +
      '<ul class="activity-stream">' +
        '<li class="waiting">' +
          (isChinese ? '暂无聊天信息' : 'No chat messages yet.') +       
        '</li>' +
      '</ul>' +
    '</div>' +
    '<label for="message"></label>' +
    '<div class="pusher-chat-widget-input">' +
      '<textarea name="message"></textarea>' +
      '<button class="pusher-chat-widget-send-btn" disabled>' +
        (isChinese ? '发送' : 'Send') +
      '</button>' +
    '</div>' +
  '</div>';

  var widget = jQuery(html);
  jQuery(appendTo).append(widget);
  return widget;
};

/* @private */
PusherChatWidget._buildListItem = function(activity) {
  var li = jQuery('<li></li>').addClass('message-item');
  var contentWrapper = jQuery('<div class="message-content-wrapper"></div>');
  
  var imageInfo = activity.actor.image;
  var image = jQuery('<div class="image"><img src="' + imageInfo.url + '" width="24" height="24" /></div>');
  var content = jQuery('<div class="content"></div>');
  
  var user = jQuery('<div class="activity-row"><span class="user-name"><a class="screen-name">' + activity.actor.displayName.replace(/\\'/g, "'") + '</a><a ' + (activity.link ? 'href="' + activity.link + '" ' : '') + 'class="timestamp"><span data-activity-published="' + activity.published + '">' + PusherChatWidget.timeToDescription(activity.published) + '</span></a></span></div>');
  content.append(user);
  
  var textHtml = activity.body.replace(/(https?:\/\/\S+\b)/g, function(match) {
    return /\.(png|jpe?g|gif|bmp|svg|webp)$/i.test(match) ? '<img src="' + match + '" />' : '<a href="' + match + '">' + match + '</a>';
  }).replace(/\n/g, '<br>');

  var message = jQuery('<div class="activity-row"><div class="text">' + textHtml + '</div></div>');
  content.append(message);
  
  contentWrapper.append(image).append(content);
  var deleteAction = jQuery('<div class="delete-action"><button class="delete-button">' + (isChinese ? '删除' : 'Delete') + '</button></div>');

  li.append(contentWrapper).append(deleteAction);
  return li;
};

/* @private */
PusherChatWidget.prototype._addSwipeToDeleteHandlers = function(liElement, activityData) {
  var widgetInstance = this;
  let touchStartX = 0;
  const swipeThreshold = 50;

  liElement.on('touchstart', function(e) {
    widgetInstance._messagesEl.find('li.slide-active').not(this).removeClass('slide-active');
    touchStartX = e.originalEvent.touches[0].clientX;
    jQuery(this).data('touchStartX', touchStartX);
  });

  liElement.on('touchend', function(e) {
    touchStartX = jQuery(this).data('touchStartX');
    if (typeof touchStartX === 'undefined') return;

    const touchEndX = e.originalEvent.changedTouches[0].clientX;
    const deltaX = touchEndX - touchStartX;
    const $thisLi = jQuery(this);

    if (jQuery(e.target).closest('.delete-button').length) {
      $thisLi.removeData('touchStartX');
      return;
    }

    if (deltaX < -swipeThreshold) {
      $thisLi.addClass('slide-active');
    } else if (deltaX > swipeThreshold) {
      $thisLi.removeClass('slide-active');
    }
    $thisLi.removeData('touchStartX');
  });

  liElement.find('.delete-button').on('click', function(e) {
    e.stopPropagation();
    liElement.slideUp(function() {
      jQuery(this).remove();
      widgetInstance._itemCount--;
      if (widgetInstance._itemCount === 0) {
        widgetInstance._messagesEl.html('<li class="waiting">' + (isChinese ? '暂无聊天信息' : 'No chat messages yet.') + '</li>');
      }
    });
    // TODO: Add logic to delete the message from the server
    // e.g.
  //   if (typeof currentPage !== 'undefined' && typeof tid !== 'undefined') {
  //     widgetInstance._pusher.trigger(widgetInstance.settings.channelName, 'deletepost', {
  //       tid: tid,
  //       pid: activityData.id
  //     });
  //   }
  });
};

/**
 * converts a string or date parameter into a 'social media style'
 * time description.
 */
PusherChatWidget.timeToDescription = function(time) {
  const now = new Date();
  const date = new Date(time);
  const howLongAgo = now - date;
  let desc;
  const seconds = Math.floor(howLongAgo/1000);
  const minutes = Math.floor(seconds/60);
  const hours = Math.floor(minutes/60);
  if(seconds <= 0) {
    desc = isChinese ? "刚刚" : "just now";
  }
  else if(minutes < 1) {
    desc = seconds + " second" + (seconds !== 1?"s":"") + " ago";
    if(isChinese) {
      desc = seconds +
        (seconds < 10 ? " 秒" : " 秒钟") +
        "前";
    }
  }
  else if(minutes < 60) {
    desc = minutes + " minute" + (minutes !== 1?"s":"") + " ago";
    if(isChinese) {
      desc = minutes +
        (minutes < 10 ? " 分" : " 分钟") +
        "前";
    }
  }
  else if(hours < 24) {
    desc = hours + " hour"  + (hours !== 1?"s":"") + " ago";
    if(isChinese) {
      desc = hours +
        (hours < 10 ? " 小时" : " 小时") +
        "前";
    }
  }
  else {
    desc = date.getDay() + " " + ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sept", "Oct", "Nov", "Dec"][date.getMonth()]
    if(isChinese) {
      desc = date.getMonth()+1 + "月" + date.getDate() + "日";
    }
  }
  return desc;
};

new PusherChatWidget(new Pusher("91983fb955c5da073f3d", {
	cluster: 'eu'
}), {appendTo: document.body});
})();