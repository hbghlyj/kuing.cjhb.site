(() => {
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
    const metadata = [['pusher_tab_id', window.KK_PUSHER_TAB_ID || '']];
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
    document.addEventListener('submit', event => {
      const form = event.target;
      if(form instanceof HTMLFormElement) window.KK_addPusherMetadata(form, form.action);
    }, true);
    const nativeSubmit = HTMLFormElement.prototype.submit;
    HTMLFormElement.prototype.submit = function(){
      window.KK_addPusherMetadata(this, this.action);
      return nativeSubmit.call(this);
    };
    window.KK_pusherSubmitPatched = true;
  }
})();
