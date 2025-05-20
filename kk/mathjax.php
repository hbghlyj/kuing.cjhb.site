<script src="/kk/mathjax-config.js?<?= $_G['style']['verhash']?>"></script>
<script>
<?php if(!empty($_GET['highlight'])): ?>
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
<?php endif; ?>
window.MathJax.svg.scale = <?php if ($_G['mobile']):?>0.9<?php else: ?>1.1<?php endif;?>;
</script>
<script src="/static/mathjax3/es5/tex-svg.js"></script>