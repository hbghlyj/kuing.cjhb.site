window.MathJax = {
  tex: {
    inlineMath: [ ['$','$'], ["\\(","\\)"] ],
    processEscapes: true,
    tags: "ams",
    macros: {
      riff: '\\implies',
      liff: '\\impliedby',
      abs: ['\\left\\lvert #1\\right\\rvert', 1],
      rmd: '\\mathop{}\\!\\mathrm{d}',
      vv: '\\overrightarrow',
      sslash: '\\mathrel{/\\mkern-5mu/}',
      px: '\\mathrel{/\\mkern-5mu/}',
      pqd: '\\stackrel{/\\!/}{=}',
      veps: '\\varepsilon',
      du: '^\\circ',
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
      inR: '\\in\\mathbb R',
      inN: '\\in\\mathbb N',
      inZ: '\\in\\mathbb Z',
      inC: '\\in\\mathbb C',
      inQ: '\\in\\mathbb Q',
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
    packages: {'[+]': ['noerrors','mathtools','img']}
  },
  options: {
    menuOptions: {
      settings: {
        texHints: false,
        enrich: false,
        assistiveMml: false,
        speech: false,
        braille: false,
        zoom: "DoubleClick"
      }
    },
    renderActions: {
      //去掉MathML节点上的data-latex/data-latex-item属性
      removeLatex: [101,
        (doc) => {
          for (const math of doc.math) {
            math.root.walkTree((node) => {
              const attributes = node.attributes;
              attributes.unset('data-latex');
              attributes.unset('data-latex-item');
            });
          }
        },
        '',
        false
      ],
      //去行间公式后的1个br
      removeBr: [202,
        (doc) => {
          for (const math of doc.math) {
            if (math.display && math.typesetRoot.nextSibling?.nodeType === Node.ELEMENT_NODE && math.typesetRoot.nextSibling.matches('br')) {
              math.typesetRoot.nextSibling.remove();
            }
          }
        },
        '',
        false
      ],
    }
  },
  loader: {
    load: ['[tex]/noerrors','[tex]/mathtools','[custom]/img'],
    source: {
      '[custom]/img': '/static/img.js'
    },
    failed: function (error) {
      showError(`MathJax(${error.package || '?'}): ${error.message}`);
    },
    paths: {
      custom: '/static'
    }
  },
  chtml: {
    matchFontHeight: true
  },
  output: {
    font: 'mathjax-newcm',
    displayOverflow: 'scroll',
    fontPath: 'https://unpkg.com/@mathjax/%%FONT%%-font'
  }
};
