<script>
  window.KK_MATHJAX_XYPIC_URL = "/static/xypic.js?v=<?= filemtime(__DIR__ . '/../static/xypic.js')?>";
</script>
<script src="/kk/mathjax-config.js?<?= filemtime(__DIR__ . '/mathjax-config.js')?>"></script>
<script>
  window.MathJax.startup = {
    ready: function() {
      const {OPTABLE, MO} = MathJax._.core.MmlTree.OperatorDictionary;
      const {MmlMath} = MathJax._.core.MmlTree.MmlNodes.math;
      const {MmlMstyle} = MathJax._.core.MmlTree.MmlNodes.mstyle;
      const {FONTSIZE} = MathJax._.output.chtml.Wrapper;
      const {CHTML} = MathJax._.output.chtml_ts;
      OPTABLE.infix['\u27C2'] = MO.REL;
      MmlMath.defaults.scriptminsize = '0px';
      MmlMath.defaults.scriptsizemultiplier = 0.8;
      MmlMstyle.defaults.scriptminsize = '0px';
      MmlMstyle.defaults.scriptsizemultiplier = 0.8;
      delete FONTSIZE['70.7%'];
      delete FONTSIZE['70%'];
      delete FONTSIZE['50%'];
      FONTSIZE['80%'] = 's';
      FONTSIZE['64%'] = 'ss';
      CHTML.commonStyles['mjx-container [size="s"]']['font-size'] = '80%';
      CHTML.commonStyles['mjx-container [size="ss"]']['font-size'] = '64%';
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
</script>
<script src="https://unpkg.com/mathjax@4/tex-chtml.js" async></script>
