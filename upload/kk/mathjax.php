<script>
window.MathJax = {
<?php if(!empty($_GET['highlight'])) { ?>
  startup: {
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
  },
<?php } ?>
  tex: {
    inlineMath: [ ['$','$'], ['`','`'], ["\\(","\\)"] ],
    processEscapes: true,
    tags: "ams",
    macros: {
      mbb: '\\mathbb',
      riff: '\\implies',
      liff: '\\impliedby',
      abs: ['\\left\\lvert #1\\right\\rvert', 1],
      rmd: '\\mathop{}\\!\\mathrm{d}',
      vv: '\\overrightarrow',
      sslash: '\\mathrel{/\\mkern-5mu/}',
      px: '\\mathrel{/\\mkern-5mu/}',
      pqd: '\\stackrel{\\smash[b]{/\\!/}}{\\raise-.3ex{=}}',
      veps: '\\varepsilon',
      du: '^\\circ',
      bsb: '\\boldsymbol',
      bm: '\\boldsymbol',
      kongji: '\\varnothing',
      buji: '\\complement',
      S: ['S_{\\triangle #1}', 1],
      led: '\\left\\{\\begin{aligned}',
      endled: '\\end{aligned}\\right.',
      edr: '\\left.\\begin{aligned}',
      endedr: '\\end{aligned}\\right\\}',
      an: '\\{a_n\\}',
      bn: '\\{b_n\\}',
      cn: '\\{c_n\\}',
      xn: '\\{x_n\\}',
      Sn: '\\{S_n\\}',
      inR: '\\in\\mbb R',
      inN: '\\in\\mbb N',
      inZ: '\\in\\mbb Z',
      inC: '\\in\\mbb C',
      inQ: '\\in\\mbb Q',
      Rtt: '\\text{Rt}\\triangle',
      LHS: '\\text{LHS}',
      RHS: '\\text{RHS}',
      arccot: '\\operatorname{arccot}',
      arcsinh: '\\operatorname{arcsinh}',
      arccosh: '\\operatorname{arccosh}',
      arctanh: '\\operatorname{arctanh}',
      arccoth: '\\operatorname{arccoth}',
    },
    autoload: {
      color: [],
      colorv2: ['color']
    },
    packages: {'[+]': ['noerrors','mathtools','xypic']}
  },
  options: {
    ignoreHtmlClass: 'blockcode',
    menuOptions: {
      settings: {
        zoom: "DoubleClick"
      }
    },
    processHtmlClass: 'tex2jax_process',
    renderActions: {
      assistiveMml: []
    }
  },
  //chtml: {
  //  scale: 0.9
  //},
  loader: {
    load: ['[tex]/noerrors','[tex]/mathtools','[custom]/xypic.js'],
    //paths: {custom: '//cdn.jsdelivr.net/gh/sonoisa/XyJax-v3@3.0.1/build'}
    paths: {custom: 'kk'}
  },
  svg: {
    scale: 0.9,
    fontCache: 'global'
  }
};
</script>
<!--<script src="//polyfill.io/v3/polyfill.min.js?features=es6"></script>-->
<!--<script src="//cdn.jsdelivr.net/npm/mathjax@3/es5/tex-svg.js"></script>-->
<!--<script src="//cdn.jsdelivr.net/npm/mathjax@3/es5/tex-chtml.js"></script>-->
<script src="mathjax3/es5/tex-svg.js"></script>
<!--<script src="mathjax3/es5/tex-chtml.js"></script>-->
<!--<script src="//unpkg.com/mathjax@3.2.2/es5/tex-svg.js"></script>-->
<!--<script src="//unpkg.com/mathjax@3.2.2/es5/tex-chtml.js"></script>-->
<!--<script src="//cdn.bootcdn.net/ajax/libs/mathjax/3.2.2/es5/tex-svg.min.js"></script>-->
<!--<script src="//cdn.bootcdn.net/ajax/libs/mathjax/3.2.2/es5/tex-chtml.js"></script>-->
