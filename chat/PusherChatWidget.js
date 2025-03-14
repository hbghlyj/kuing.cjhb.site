(function(){
const isChinese = navigator.languages && (navigator.languages.indexOf('zh') !== -1 || navigator.languages.indexOf('zh-CN') !== -1 || navigator.languages.indexOf('zh-TW') !== -1 || navigator.languages.indexOf('zh-HK') !== -1 || navigator.languages.indexOf('zh-SG') !== -1);

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
  
  if(this.settings.debug && !Pusher.log) {
    Pusher.log = function(msg) {
      if(console && console.log) {
        console.log(msg);
      }
    }
  }
  // Fetch history messages
  jQuery.ajax({
    url: '/chat/php/history.php',
    type: 'get',
    dataType: 'json',
    success: function(data) {
      data.forEach(function(message) {
        self._chatMessageReceived(message);
      });
      self._messagesEl.scrollTop(self._messagesEl[0].scrollHeight);
    },
    error: function(xhr, status, error) {
      if (console && console.error) {
        console.error('Failed to fetch chat history:', status, error);
      }
    }
  });
  // Subscribe to the chat channel
  this._chatChannel = this._pusher.subscribe(this.settings.channelName);
  
  this._chatChannel.bind('chat_message', function(data) {
    self._chatMessageReceived(data);
    self._messagesEl.animate({ scrollTop: self._messagesEl[0].scrollHeight }, 500);
  })
    
  this._itemCount = 0;
  
  this._widget = PusherChatWidget._createHTML(this.settings.appendTo);
  this._messageInputEl = this._widget.find('textarea');
  this._messagesEl = this._widget.find('ul');
  
  this._widget.find('button').click(function() {
    self._sendChatButtonClicked();
  })  
  this._startTimeMonitor();
};
PusherChatWidget.instances = [];

/* @private */
PusherChatWidget.prototype._chatMessageReceived = function(data) {
  var self = this;
  
  if(this._itemCount === 0) {
    this._messagesEl.html('');
  }
  
  var messageEl = PusherChatWidget._buildListItem(data);
  this._messagesEl.append(messageEl);
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
    alert('please supply a chat message');
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
  jQuery.ajax({
    url: this.settings.chatEndPoint,
    type: 'post',
    dataType: 'json',
    data: {
      'chat_info': data
    },
    complete: function(xhr, status) {
      Pusher.log('Chat message sent. Result: ' + status + ' : ' + xhr.responseText);
      if(xhr.status === 200) {
        self._messageInputEl.val('');
      }
      self._messageInputEl.removeAttr('readonly');
    }
  })
};

/* @private */
PusherChatWidget.prototype._startTimeMonitor = function() {
  var self = this;
  
  setInterval(function() {
    self._messagesEl.children('.activity').each(function(i, el) {
      var timeEl = jQuery(el).find('a.timestamp span[data-activity-published]');
      var time = timeEl.attr('data-activity-published');
      var newDesc = PusherChatWidget.timeToDescription(time);
      timeEl.text(newDesc);
    });
  }, 10 * 1000)
};

/* @private */
PusherChatWidget._createHTML = function(appendTo) {
  var html = '' +
  '<div class="pusher-chat-widget">' +
    '<div class="pusher-chat-widget-header">' +
      '<svg class="toggle-icon" width="24" height="24" viewBox="0 0 24 24">' +
        '<path d="M7 10l5 5 5-5z"/>' + // Downward triangle
      '</svg>' +
    '</div>' +
    '<div class="pusher-chat-widget-messages">' +
      '<ul class="activity-stream">' +
        '<li class="waiting">' +
          (isChinese ? '暂无聊天信息' : 'No chat messages yet.') +       
        '</li>' +
      '</ul>' +
    '</div>' +
    '<div class="pusher-chat-widget-input">' +
      '<label for="message">' +
        (isChinese ? '消息' : 'Message') +
      '</label>' +
      '<textarea name="message"></textarea>' +
      '<button class="pusher-chat-widget-send-btn">'+
        (isChinese ? '发送' : 'Send') +
      '</button>' +
    '</div>' +
  '</div>';

  var widget = jQuery(html);
  jQuery(appendTo).append(widget);

  // Add collapse/expand functionality
  var header = widget.find('.pusher-chat-widget-header');
  var messages = widget.find('.pusher-chat-widget-messages');
  var input = widget.find('.pusher-chat-widget-input');
  var toggleIcon = header.find('.toggle-icon');

  // Read collapse/expand status from cookie
  var isCollapsed = document.cookie.replace(/(?:(?:^|.*;\s*)chatWidgetCollapsed\s*\=\s*([^;]*).*$)|^.*$/, "$1") === 'true';
  if (isCollapsed) {
    messages.hide();
    input.hide();
    toggleIcon.addClass('collapsed');
    toggleIcon.html('<path d="M7 14l5-5 5 5z"/>'); // Upward triangle
  }

  header.click(function() {
    messages.slideToggle();
    input.slideToggle();
    toggleIcon.toggleClass('collapsed');
    var collapsed = toggleIcon.hasClass('collapsed');
    document.cookie = "chatWidgetCollapsed=" + collapsed + "; path=/";
    if (collapsed) {
      toggleIcon.html('<path d="M7 14l5-5 5 5z"/>'); // Upward triangle
    } else {
      toggleIcon.html('<path d="M7 10l5 5 5-5z"/>'); // Downward triangle
    }
  });

  return widget;
};

/* @private */
PusherChatWidget._buildListItem = function(activity) {
  var li = jQuery('<li class="activity"></li>');
  var item = jQuery('<div class="stream-item-content"></div>');
  li.append(item);
  
  var imageInfo = activity.actor.image;
  var image = jQuery('<div class="image">' +
                  '<img src="' + imageInfo.url + '" width="24" height="24" />' +
                '</div>');
  item.append(image);
  
  var content = jQuery('<div class="content"></div>');
  item.append(content);
  
  var user = jQuery('<div class="activity-row">' +
                '<span class="user-name">' +
                  '<a class="screen-name">' + activity.actor.displayName.replace(/\\'/g, "'") + '</a>' +
                '</span>' +
              '</div>');
  content.append(user);
  
  var message = jQuery('<div class="activity-row">' +
                    '<div class="text">' + activity.body.replace(/\\('|&quot;)/g, 'jQuery1') + '</div>' +
                  '</div>');
  content.append(message);
  
  var time = jQuery('<div class="activity-row">' + 
                '<a ' + (activity.link?'href="' + activity.link + '" ':'') + ' class="timestamp">' +
                  '<span data-activity-published="' + activity.published + '">' + PusherChatWidget.timeToDescription(activity.published) + '</span>' +
                '</a>' +
                '<span class="activity-actions">' +
                '</span>' +
              '</div>');
  content.append(time);
                
  
  return li;
};

/**
 * converts a string or date parameter into a 'social media style'
 * time description.
 */
PusherChatWidget.timeToDescription = function(time) {
  const now = new Date();
  const howLongAgo = now - new Date(Date.parse(time));
  let desc = "dunno";
  const seconds = Math.round(howLongAgo/1000);
  const minutes = Math.round(seconds/60);
  const hours = Math.round(minutes/60);
  if(seconds === 0) {
    desc = "just now";
    if(isChinese) {
      desc = "刚刚";
    }
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
    desc = "about " + minutes + " minute" + (minutes !== 1?"s":"") + " ago";
    if(isChinese) {
      desc = minutes +
        (minutes < 10 ? " 分" : " 分钟") +
        "前";
    }
  }
  else if(hours < 24) {
    desc = "about " + hours + " hour"  + (hours !== 1?"s":"") + " ago";
    if(isChinese) {
      desc = hours +
        (hours < 10 ? " 小时" : " 小时") +
        "前";
    }
  }
  else {
    desc = time.getDay() + " " + ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sept", "Oct", "Nov", "Dec"][time.getMonth()]
    if(isChinese) {
      desc = time.getMonth() + "月" + time.getDay() + "日";
    }
  }
  return desc;
};

new PusherChatWidget(new Pusher("91983fb955c5da073f3d", {
	cluster: 'eu'
}), {appendTo: document.body});
})();