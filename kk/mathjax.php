<script src="/kk/mathjax-config.js?<?= $_G['style']['verhash']?>"></script>
<?php if(!empty($_GET['highlight'])): ?>
<script>
  window.MathJax.startup = {
    ready: function() {
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
      MathJax.startup.defaultReady();
    }
  }
</script>
<?php endif; ?>
<!--<script src="//polyfill.io/v3/polyfill.min.js?features=es6"></script>-->
<!--<script src="//cdn.jsdelivr.net/npm/mathjax@3/es5/tex-svg.js"></script>-->
<!--<script src="//cdn.jsdelivr.net/npm/mathjax@3/es5/tex-chtml.js"></script>-->
<script src="mathjax3/es5/tex-svg.js"></script>
<!--<script src="mathjax3/es5/tex-chtml.js"></script>-->
<!--<script src="//unpkg.com/mathjax@3.2.2/es5/tex-svg.js"></script>-->
<!--<script src="//unpkg.com/mathjax@3.2.2/es5/tex-chtml.js"></script>-->
<!--<script src="//cdn.bootcdn.net/ajax/libs/mathjax/3.2.2/es5/tex-svg.min.js"></script>-->
<!--<script src="//cdn.bootcdn.net/ajax/libs/mathjax/3.2.2/es5/tex-chtml.js"></script>-->
