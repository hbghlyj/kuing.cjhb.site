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
      MathJax.startup.defaultReady();
    }
  }
</script>
<script src="https://unpkg.com/mathjax@4.0.0/tex-svg.js" async></script>