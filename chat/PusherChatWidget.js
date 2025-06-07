(function(){
const isChinese = navigator.languages && navigator.languages.some(lang => ['zh', 'zh-CN', 'zh-TW', 'zh-HK', 'zh-SG'].includes(lang));
const isMobile = typeof popup == 'object';
if(isMobile) {
  function showError(msg) {
    popup.open(msg, 'alert');
  };
}
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

  // Properties for message processing queue
  this._pendingMessages = [];
  this._isProcessingPendingMessages = false;
  this._onQueueDrainedCallback = null;
  
  options = options || {};
  this.settings = jQuery.extend({
    chatEndPoint: '/chat/php/chat.php',    channelName: 'Chat',    appendTo: document.body,    debug: true
  }, options);

  this._itemCount = 0;
  this._totalMessages = 0; // To store the total number of messages available on the server
  this._messagesLoaded = 0; // To track how many messages have been loaded
  this._widget = PusherChatWidget._createHTML(this.settings.appendTo);
  this._messageInputEl = this._widget.find('textarea');
  this._messagesEl = this._widget.find('ul');
  this._loadMoreButton = this._widget.find('.pusher-chat-widget-load-more');

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
  if(typeof tid !== 'undefined') {
    this._chatChannel.bind('newreply', function(data) {
      const pageNumberElement = document.querySelector('div.pg>strong');
      const pageNumber = pageNumberElement ? pageNumberElement.textContent.trim() : 1;
      if(data.tid == tid && data.page == pageNumber) {
        ajaxget(`forum.php?mod=viewthread&tid=${tid}&viewpid=${data.pid}`, 'post_new', 'ajaxwaitid', '', null, `appendreply(${data.pid})`);
      }
    });
    this._chatChannel.bind('editpost', function(data) {
      if(data.tid == tid && $(`pid${data.pid}`)) {
        ajaxget(`forum.php?mod=viewthread&tid=${tid}&viewpid=${data.pid}`, `post_${data.pid}`, 'ajaxwaitid', '', null, "if (typeof MathJax.typesetPromise === 'function') {MathJax.texReset();MathJax.typesetPromise([document.querySelector('#pid"+data.pid+" .t_f')]);}");
        if(data.subject){
          $('thread_subject').innerHTML=data.subject;
          if (typeof MathJax.typesetPromise === 'function') {MathJax.typesetPromise([$('thread_subject')]);}
        }
        if(document.querySelector("input[name=pid]")?.value == data.pid && discuz_uid != data.uid) {
          // the edit form is open for this post, notify the user that the post has been updated
          showDialog(isChinese ? '帖子已被编辑' : 'Post has been edited');
        }
      }
    });
    this._chatChannel.bind('commentadd', function(data) {
      if(data.tid == tid && $(`pid${data.pid}`)) {
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
        document.cookie = "isCollapsed=false; path=" + location.pathname;
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
  this._loadHistory(); // Initial load of messages
  
  this._chatChannel.bind('chat_message', function(data) {
    self._chatMessageReceived(data, true /* isLiveMessage */);
    self._processPendingMessages();
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
    if (e.ctrlKey && e.keyCode == 13) {
      self._sendChatButtonClicked();
    }
  });
  // Update the UI with the current time every 10 seconds
  this._startTimeMonitor();

  // Load more messages button functionality
  this._loadMoreButton.click(function() {
    self._loadHistory(true);
  });
};

/* @private */
// NEW: Method to load message history with pagination
PusherChatWidget.prototype._loadHistory = function(isLoadingMore) {
  var self = this;

  // Show loading indicator on button if loading more
  if (isLoadingMore) {
    self._loadMoreButton.text(isChinese ? '加载中...' : 'Loading...').prop('disabled', true);
  }

  jQuery.ajax({
    url: '/chat/php/history.php',
    type: 'get',
    dataType: 'json',
    data: {
      offset: self._messagesLoaded
    },
    success: function(response) {
      var data = response.messages;
      self._totalMessages = response.total;

      if (data && data.length > 0) {
        // Add messages to the queue, ensuring they are added at the beginning if loading more
        for (var i = 0; i < data.length; ++i) {
          self._chatMessageReceived(data[i], false /* isLiveMessage */, isLoadingMore /* isPrepending */);
        }
        // Scroll to bottom for initial load, or maintain position for load more
        if (!isLoadingMore) {
          self._onQueueDrainedCallback = function() {
            self._messagesEl.scrollTop(self._messagesEl[0].scrollHeight);
            self._onQueueDrainedCallback = null; // Reset callback
          };
        }
        self._processPendingMessages();
        self._messagesLoaded += data.length;
        // Update load more button state
        if (self._messagesLoaded >= self._totalMessages) {
          self._loadMoreButton.hide();
        } else {
          self._loadMoreButton.show().text(isChinese ? '加载更多' : 'Load More').prop('disabled', false);
        }
      } else if (!isLoadingMore) {
        // No messages at all
        self._loadMoreButton.hide();
      }

    },
    error: function(xhr, status, error) {
      showError("Error fetching history:"+status+error);
      if (isLoadingMore) {
        self._loadMoreButton.text(isChinese ? '加载失败' : 'Failed to load').prop('disabled', false);
      }
    }
  });
};

/* @private */
// MODIFIED: This function now adds messages to a queue.
PusherChatWidget.prototype._chatMessageReceived = function(data, isLiveMessage, isPrepending) {
  var messageEl = PusherChatWidget._buildListItem(data);
  var entry = { 
    data: data,    messageEl: messageEl, 
    isLiveMessage: isLiveMessage,
    isPrepending: isPrepending || false // New flag
  };
  if (isPrepending) {
    this._pendingMessages.unshift(entry); // Add to the beginning for older messages
  } else {
    this._pendingMessages.push(entry);
  }
};

/* @private */
// NEW: Processes messages from the queue one by one.
PusherChatWidget.prototype._processPendingMessages = function() {
  var self = this;
  var oldScrollHeight = 0;
  var oldScrollTop = 0;

  if (this._isProcessingPendingMessages) {
    return;
  }

  if (this._pendingMessages.length === 0) {
    if (this._onQueueDrainedCallback && this._chatMessageReceived) {
      this._onQueueDrainedCallback();
    }
    return;
  }

  this._isProcessingPendingMessages = true;
  
  let currentEntry = this._pendingMessages[0];  let messageEl = currentEntry.messageEl;
  let imagesInMessage = messageEl.find('img');

  // Store scroll position if prepending to restore it later
  if (currentEntry.isPrepending) {
    oldScrollHeight = this._messagesEl[0].scrollHeight;
    oldScrollTop = this._messagesEl.scrollTop();
  }

  if (imagesInMessage.length === 0) {
    this._actuallyAppendMessage(currentEntry, oldScrollHeight, oldScrollTop);
  } else {
    var loadedCount = 0;
    imagesInMessage.each(function() {
      jQuery(this).on('load error', function() {
        loadedCount++;
        if (loadedCount === imagesInMessage.length) {
          self._actuallyAppendMessage(currentEntry, oldScrollHeight, oldScrollTop);
        }
      });
      // If an image is already loaded (e.g. from cache), trigger the load event manually
      if (this.complete || this.naturalWidth > 0) {
        jQuery(this).trigger('load');
      }
    });
  }
};

/* @private */
// NEW: Handles the actual DOM appending and post-processing logic.
PusherChatWidget.prototype._actuallyAppendMessage = function(entry, oldScrollHeight, oldScrollTop) {
  this._pendingMessages.shift();
  if (entry.isPrepending) {
    entry.messageEl.insertAfter(this._loadMoreButton); // Prepend for older messages
  } else {
    this._messagesEl.append(entry.messageEl);
  }

  if (isMobile) {
    this._addSwipeToDeleteHandlers(entry.messageEl, entry.data.published);
  }

  if (typeof MathJax.typesetPromise === 'function') {
    MathJax.typesetPromise([entry.messageEl[0]]).catch(function (err) {
      showError('MathJax typesetting error:'+err);
    });
  }
  
  ++this._itemCount;

  // Scroll handling
  if (entry.isLiveMessage) {
    if (!this.isCollapsed && (this._messagesEl.scrollTop() + this._messagesEl.innerHeight() >= this._messagesEl[0].scrollHeight - entry.messageEl.outerHeight() * 1.5)) {
      this._messagesEl.animate({scrollTop: this._messagesEl[0].scrollHeight}, 500);
    }
  } else if (entry.isPrepending) {
    // Restore scroll position after prepending new content
    var newScrollHeight = this._messagesEl[0].scrollHeight;
    this._messagesEl.scrollTop(oldScrollTop + (newScrollHeight - oldScrollHeight));
  }
  // For initial history load, scrolling is handled by the _onQueueDrainedCallback after all are processed.
  
  this._isProcessingPendingMessages = false;
  // Process the next message in the queue, or trigger drained callback if empty
  this._processPendingMessages(); 
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
  var html = '<div class="pusher-chat-widget">' +
  (isMobile ? '' :(
    '<div class="pusher-chat-widget-header">' +
      '<svg class="toggle-icon" width="24" height="24" viewBox="0 0 24 24"><path d="M7 10l5 5 5-5z"/></svg>' +
    '</div>')) +
    '<div class="pusher-chat-widget-messages">' +
      '<ul class="activity-stream">' +
        '<li class="pusher-chat-widget-load-more" style="display:none;">' + // Initially hidden
          (isChinese ? '加载更多' : 'Load More') +
        '</li>' +
      '</ul>' +
    '</div>' +
    '<div class="pusher-chat-widget-input">' +
      '<label for="message"></label>' +
      '<textarea id="message"></textarea>' +
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
  
  var image = jQuery('<div class="image"><img src="' + activity.actor.image + '" width="24" height="24" /></div>');
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
PusherChatWidget.prototype._addSwipeToDeleteHandlers = function(liElement, published) {
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
    // AJAX call to delete.php
    jQuery.ajax({
      url: '/chat/php/delete.php', // Endpoint to delete the message
      type: 'POST',
      data: {
        'published_time': published // Send the timestamp of the message to be deleted
      },
      success: function(response) {
        // On success, remove the message from the UI
        liElement.slideUp(function() {
          jQuery(this).remove();
          widgetInstance._itemCount--;
        });
      },
      error: function(xhr, status, error) {
        // Handle error (e.g., show an error message)
        showError((isChinese ? '删除消息失败' : "Error deleting message: ")+status+error);
      }
    });
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