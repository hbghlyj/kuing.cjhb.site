window.MathJax = {
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
      pqd: '\\stackrel{/\\!/}{=}',
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
        texHints: false,
        enrich: false,
        assistiveMml: false,
        speech: false,
        braille: false,
        zoom: "DoubleClick"
      }
    },
    processHtmlClass: 'tex2jax_process',
    renderActions: {
      addTeX: [151,
        (doc) => {
            for (const math of doc.math) {
              if (math.state() >= 200) return;
              const spanElement = document.createElement("span");
              spanElement.style.position = "absolute";
              spanElement.style.left = "-9999px";
              spanElement.style.opacity = "0";
              spanElement.textContent = math.start.delim + math.math + math.end.delim;
              math.typesetRoot.appendChild(spanElement);
              if(math.math.length < 100) { doc.adaptor.setAttribute(math.typesetRoot, 'title', math.math); }
            }
        },
        ''
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
        ''
      ],
    }
  },
  loader: {
    load: ['[tex]/noerrors','[tex]/mathtools','[static]/xypic'],
    failed: function (error) {
      showError(`MathJax(${error.package || '?'}): ${error.message}`);
    },
    paths: {static: '/static'}
  },
  svg: {
    fontCache: 'global',
    mtextInheritFont: true
  },
  output: {
    displayOverflow: 'scroll',
    fontPath: 'https://unpkg.com/@mathjax/mathjax-newcm-font@4.0.0'
  }
};