<script src="/kk/mathjax-config.js?<?= $_G['style']['verhash']?>"></script>
<script>
  window.MathJax.startup = {
    ready: function() {
      const {OPTABLE, MO} = MathJax._.core.MmlTree.OperatorDictionary;
      OPTABLE.infix['\u27C2'] = MO.REL;
      <?php if(!empty($_GET['highlight'])): ?>
        const {HTMLDomStrings} = MathJax._.handlers.html.HTMLDomStrings;
        HTMLDomStrings.OPTIONS.includeHtmlTags['mark'] = '';
        var handleTag = HTMLDomStrings.prototype.handleTag;
        HTMLDomStrings.prototype.handleTag = function (node, ignore) {
          if (this.adaptor.kind(node) === 'mark') {
>>>>>>> a4baa9d5f (https://github.com/mathjax/MathJax/issues/3441#issuecomment-3343002443)
          const text = this.adaptor.textContent(node);
          this.snodes.push([node, text.length]);
          this.string += text;
        }
        return handleTag.call(this, node, ignore);
      }
      <?php endif; ?>
      const { TEXCLASS } = MathJax._.core.MmlTree.MmlNode;
      const Macro = MathJax._.input.tex.Token.Macro;
      const {MakeBig} = MathJax._.input.tex.base.BaseMethods.default;
      MathJax._.input.tex.MapHandler.MapHandler.getMap('macros').map.set('big',new Macro('big', MakeBig, [TEXCLASS.ORD, .84]));
      MathJax._.input.tex.MapHandler.MapHandler.getMap('macros').map.set('bigl',new Macro('bigl', MakeBig, [TEXCLASS.OPEN, .84]));
      MathJax._.input.tex.MapHandler.MapHandler.getMap('macros').map.set('bigr',new Macro('bigr', MakeBig, [TEXCLASS.CLOSE, .84]));
      MathJax._.input.tex.MapHandler.MapHandler.getMap('macros').map.set('bigm',new Macro('bigm', MakeBig, [TEXCLASS.REL, .84]));
      MathJax._.input.tex.MapHandler.MapHandler.getMap('macros').map.set('bigg',new Macro('bigg', MakeBig, [TEXCLASS.ORD, 1.6]));
      MathJax._.input.tex.MapHandler.MapHandler.getMap('macros').map.set('biggl',new Macro('biggl', MakeBig, [TEXCLASS.OPEN, 1.6]));
      MathJax._.input.tex.MapHandler.MapHandler.getMap('macros').map.set('biggr',new Macro('biggr', MakeBig, [TEXCLASS.CLOSE, 1.6]));
      MathJax._.input.tex.MapHandler.MapHandler.getMap('macros').map.set('biggm',new Macro('biggm', MakeBig, [TEXCLASS.REL, 1.6]));
      MathJax.startup.defaultReady();
    }
  }
  window.MathJax.svg.scale = <?php if(defined("IN_MOBILE")) { ?>0.9<?php } else { ?>1.1<?php } ?>;
</script>
<script src="/static/mathjax3/es5/tex-svg.js"></script>
