<script src="/kk/mathjax-config.js?<?= $_G['style']['verhash']?>"></script>
<script>
  window.MathJax.startup = {
    ready: function() {
      const { RANGES }   = MathJax._.core.MmlTree.OperatorDictionary;
      const { TEXCLASS } = MathJax._.core.MmlTree.MmlNode;
      function promote(codepoint, texClass, nodeType) {
        const i = RANGES.findIndex(([a, b]) => a <= codepoint && codepoint <= b);
        if (i < 0) return;
        const [a, b, cls, node] = RANGES[i];
        const before = (a <= codepoint - 1) ? [[a, codepoint - 1, cls, node]] : [];
        const middle = [[codepoint, codepoint, texClass, nodeType]];
        const after  = (codepoint + 1 <= b) ? [[codepoint + 1, b, cls, node]] : [];
        RANGES.splice(i, 1, ...before, ...middle, ...after);
      }
      promote(0x27C2, TEXCLASS.REL, 'mo');
      <?php if(!empty($_GET['highlight'])): ?>
      const {HTMLDomStrings} = MathJax._.handlers.html.HTMLDomStrings;
      HTMLDomStrings.OPTIONS.includeHtmlTags['mark'] = '';
      var handleTag = HTMLDomStrings.prototype.handleTag;
      HTMLDomStrings.prototype.handleTag = function (node, ignore) {
        if (this.adaptor.kind(node) === 'mark') {
          const text = this.adaptor.textContent(node);
          this.snodes.push([node, text.length]);
          this.string += text;
        }
        return handleTag.call(this, node, ignore);
      }
      <?php endif; ?>
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
</script>
<script src="https://unpkg.com/mathjax@4.0.0/tex-svg.js" async></script>