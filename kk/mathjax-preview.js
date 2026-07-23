const input = $("inputText") || {};
input.shili = `行内公式：$c=\\sqrt{a^2+b^2}$
行间公式：\\[c=\\sqrt{a^2+b^2}\\]
多行公式：align* 环境（在 & 处对齐）
\\begin{align*}
f(x)&=ax^2+bx+c\\\\
&=a(x-x_1)(x-x_2)\\\\
&=\\cdots
\\end{align*}`;
input.yl = true;
if ($("inputText")) {
	input.oninput = function() {
		if (input.yl && $("output")) {
			MathJax.texReset();
			var ltx = (input.value || '').replace(/</g,'&lt;').replace(/>/g,'&gt;')
								.replace(/(\\\]|\\end\{align\*?\}|\\end\{gather\*?\}|\\end\{equation\*?\}|\$\$) *\n/g,'$1');
			$("output").innerHTML=ltx;
			MathJax.typesetPromise([$("output")]).catch(() => {});
		}
	};
}

function insertTexToEditor(va) {
	var textarea = (document.activeElement && (document.activeElement.id === 'fastpostmessage' || document.activeElement.id === 'postmessage' || document.activeElement.id === 'e_textarea' || document.activeElement.id === 'inputText'))
		? document.activeElement
		: document.querySelector("#fastpostmessage, #postmessage, #e_textarea, #inputText");

	if (!textarea) return;

	textarea.focus();
	var start = textarea.selectionStart,
		end = textarea.selectionEnd,
		scrollTop = textarea.scrollTop;

	if (va instanceof Array) {
		var [vaStr, vbStr, n, m] = va;
		if (start !== undefined && end !== undefined) {
			textarea.value = textarea.value.substring(0, start) + vaStr + textarea.value.substring(start, end) + vbStr + textarea.value.substring(end);
			textarea.scrollTop = scrollTop;
			if (start === end) {
				textarea.selectionStart = textarea.selectionEnd = start + vaStr.length + (m || 0);
			} else {
				textarea.selectionStart = start;
				textarea.selectionEnd = end + vaStr.length + vbStr.length;
			}
		} else {
			textarea.value += vaStr + vbStr;
		}
	} else {
		if (start !== undefined && end !== undefined) {
			textarea.value = textarea.value.substring(0, start) + va + textarea.value.substring(end);
			textarea.scrollTop = scrollTop;
			textarea.selectionStart = textarea.selectionEnd = start + va.length;
		} else {
			textarea.value += va;
		}
	}
	if (typeof textarea.oninput === 'function') {
		textarea.oninput();
	}
}

function insertArrayCode() {
	const numRows = prompt("请输入行数：");
	const numCols = prompt("请输入列数：");
	if (numRows === null || numCols === null || isNaN(numRows) || isNaN(numCols) || parseInt(numRows) <= 0 || parseInt(numCols) <= 0) {
		alert("输入无效，请输入有效的正整数。");
		return;
	}
	let latexCode = "\\begin{array}{", n = 0;
	for (let i = 0; i < parseInt(numCols); i++) {
		latexCode += "|c";
	}
	latexCode += "|}\\hline\n";
	for (let i = 0; i < parseInt(numRows); i++) {
		for (let j = 0; j < parseInt(numCols); j++) {
			if (i === 0 && j === 0) {
				n = latexCode.length;
			}
			if (j !== parseInt(numCols) - 1) {
				latexCode += " & ";
			} else {
				latexCode += " \\\\ \n";
			}
		}
		latexCode += "\\hline\n";
	}
	latexCode += "\\end{array}";
	insertTexToEditor([latexCode.slice(0, n), latexCode.slice(n), 0, 0]);
}

input.cha = function(va) {
	insertTexToEditor(va);
};

input.fangru = function() {
	var textarea = document.querySelector("#postmessage, #e_textarea, #fastpostmessage");
	if (textarea && input.value) {
		textarea.value += input.value;
		textarea.focus();
	} else if (!textarea) {
		alert("找不到编辑框");
	}
};

// 快捷 TeX 公式数据
var fastTexItems = [
	{ "n": "行内公式", "o": ["$ ", " $"] },
	{ "n": "行间公式", "o": ["\\[ ", " \\]"] },
	{ "n": "$\\frac{a}{b}$", "o": ["\\frac{", "}{}", 2] },
	{ "n": "$\\sqrt{x}$", "o": ["\\sqrt{", "}"] },
	{ "n": "$\\sqrt[n]{x}$", "o": ["\\sqrt[]{", "}", -2, -2] },
	{ "n": "$\\geqslant$", "o": "\\geqslant " },
	{ "n": "$\\leqslant$", "o": "\\leqslant " },
	{ "n": "$\\times$", "o": "\\times " },
	{ "n": "$\\cdot$", "o": "\\cdot " },
	{ "n": "$\\cdots$", "o": "\\cdots " },
	{ "n": "$\\approx$", "o": "\\approx " },
	{ "n": "$\\equiv$", "o": "\\equiv " },
	{ "n": "$\\pmod{m}$", "o": ["\\pmod{", "}"] },
	{ "n": "$\\lim_{x\\to 0}$", "o": ["\\lim_{x\\to ", "}"] },
	{ "n": "$\\infty$", "o": "\\infty " },
	{ "n": "$\\int$", "o": ["\\int ", "\\rmd x"] },
	{ "n": "$\\log$", "o": "\\log " },
	{ "n": "$\\ln$", "o": "\\ln " },
	{ "n": "$\\sin$", "o": "\\sin " },
	{ "n": "$\\cos$", "o": "\\cos " },
	{ "n": "$\\tan$", "o": "\\tan " },
	{ "n": "$\\alpha$", "o": "\\alpha " },
	{ "n": "$\\beta$", "o": "\\beta " },
	{ "n": "$\\gamma$", "o": "\\gamma " },
	{ "n": "$\\theta$", "o": "\\theta " },
	{ "n": "$\\lambda$", "o": "\\lambda " },
	{ "n": "$\\varepsilon$", "o": "\\veps " },
	{ "n": "$\\varphi$", "o": "\\varphi " },
	{ "n": "$\\omega$", "o": "\\omega " },
	{ "n": "$\\Delta$", "o": "\\Delta " },
	{ "n": "$\\triangle$", "o": "\\triangle " },
	{ "n": "$\\odot$", "o": "\\odot " },
	{ "n": "$\\angle$", "o": "\\angle " },
	{ "n": "$^{\\circ}$", "o": "\\du " },
	{ "n": "$\\perp$", "o": "\\perp " },
	{ "n": "$\\parallel$", "o": "\\px " },
	{ "n": "$\\sim$", "o": "\\sim " },
	{ "n": "$\\cong$", "o": "\\cong " },
	{ "n": "$\\{a_n\\}$", "o": "\\{a_n\\}" },
	{ "n": "$\\vec{v}$", "o": ["\\vv{", "}"] },
	{ "n": "$\\mathbf{v}$", "o": ["\\bm{", "}"] },
	{ "n": "align*", "o": ["\\begin{align*}\n", "\n\\end{align*}", 0, 0] },
	{ "n": "gather*", "o": ["\\begin{gather*}\n", "\n\\end{gather*}", 0, 0] },
	{ "n": "cases", "o": ["\\begin{cases}\n", "\n\\end{cases}", 0, 0] },
	{ "n": "array", "o": insertArrayCode }
];

function renderFastTexSmilies() {
	var fs = $("fastsmilies");
	if (fs) {
		fs.innerHTML = '';
		var table = document.createElement("table");
		table.className = "cp0";
		table.style.width = "160px";
		table.style.tableLayout = "auto";
		var tr = document.createElement("tr");

		for (var i = 0; i < fastTexItems.length; i++) {
			if (i > 0 && i % 3 === 0) {
				table.appendChild(tr);
				tr = document.createElement("tr");
			}
			var item = fastTexItems[i];
			var td = document.createElement("td");
			td.style.padding = "3px 2px";
			td.style.cursor = "pointer";
			td.style.textAlign = "center";
			td.style.fontSize = "12px";
			td.style.border = "1px solid #e8ece6";
			td.style.background = "#fff";
			td.innerHTML = item.n;

			(function(action) {
				td.onclick = function() {
					if (typeof action === 'function') {
						action();
					} else {
						insertTexToEditor(action);
					}
				};
			})(item.o);

			tr.appendChild(td);
		}
		if (tr.children.length > 0) {
			table.appendChild(tr);
		}
		fs.appendChild(table);

		if (typeof MathJax !== 'undefined' && MathJax.typesetPromise) {
			MathJax.typesetPromise([fs]).catch(() => {});
		}
	}

	var inputWrap = $("inputWrap");
	if (inputWrap) {
		inputWrap.innerHTML = '';
		for(var annius of ctrls) {
			for(var i = 0; i < annius.length; i++){
				var bt=document.createElement("button");
				bt.type="button";
				bt.className="anniu";
				bt.setAttribute("onclick",annius[i].o);
				bt.innerHTML=annius[i].n;
				inputWrap.appendChild(bt);
			}
			inputWrap.appendChild(document.createElement("div"));
		}
	}
}

// 侧边栏按钮列表
var ctrls = [[
	{ "n":"暂停预览", "o":"this.innerHTML=((input.yl = !input.yl) ? (input.oninput && input.oninput(), '暂停') : '继续')+'预览'" },
	{ "n":"清空", "o":"if(input.value !== undefined){input.tmp_input=input.value;input.value='';input.oninput && input.oninput();}" },
	{ "n":"示例", "o":"if(input.value !== undefined){input.tmp_input=input.value;input.value=input.shili;input.oninput && input.oninput();}" },
	{ "n":"撤销", "o":"if(input.value !== undefined){input.value=input.tmp_input || '';input.oninput && input.oninput();}" },
	{ "n":"{}", "o":"if(input.setSelectionRange){input.setSelectionRange((function(){let brace=-1,i=input.selectionStart;do{switch(input.value[--i]){case '{':brace++;break;case '}':brace--;}}while(brace!=0&&i>0)return i})(),(function(){let brace=1,i=input.selectionEnd;do{switch(input.value[i++]){case '{':brace++;break;case '}':brace--;}}while(brace!=0&&i<input.value.length)return i})());input.focus();}" }
],[],['align*','gather*','cases'].map(v=>{ return{"n":v,"o":'input.cha(["\\\\begin{'+v+'}\\n","\\n\\\\end{'+v+'}",0,0])'} })];
ctrls[2].push({ "n":'array',"o":"insertArrayCode()" });

for(v of [
	{ "o":["$ "," $"], "n":"行内公式" },
	{ "o":["\\\\[ "," \\\\]"], "n":"行间公式" },
	{ "o":["\\\\frac{",'}{}',2], "n":"分式" },
	{ "o":["\\\\sqrt{","}"] , "n":"√‾‾" },
	{ "o":['\\\\sqrt[]{','}',-2,-2], "n":"<sup style='margin-right:-6px;line-height:14px;'>□</sup>√‾‾" },
	{ "o":"\\\\geqslant ", "n":"⩾" },
	{ "o":"\\\\leqslant ", "n":"⩽" },
	{ "o":"\\\\times ", "n":"×" },
	{ "o":"\\\\cdot ", "n":"·" },
	{ "o":"\\\\cdots ", "n":"…" },
	{ "o":"\\\\approx ", "n":"≈" },
	{ "o":"\\\\equiv ", "n":"≡" },
	{ "o":['\\\\pmod{','}'], "n":"(mod )" },
	{ "o":["\\\\lim_{x\\\\to ","}"] , "n":"lim<sub style='line-height:12px;'>x→</sub>" },
	{ "o":"\\\\infty ", "n":"∞" },
	{ "o":['\\\\int ','\\\\rmd x'], "n":"∫ dx" },
	{ "o":"\\\\log", "n":"log" },
	{ "o":"\\\\ln ", "n":"ln" },
	{ "o":"\\\\sin ", "n":"sin" },
	{ "o":"\\\\cos ", "n":"cos" },
	{ "o":"\\\\tan ", "n":"tan" },
	{ "o":"\\\\alpha ", "n":"α" },
	{ "o":"\\\\beta ", "n":"β" },
	{ "o":"\\\\gamma ", "n":"γ" },
	{ "o":"\\\\theta ", "n":"θ" },
	{ "o":"\\\\lambda ", "n":"λ" },
	{ "o":"\\\\veps ", "n":"ε" },
	{ "o":"\\\\varphi ", "n":"φ" },
	{ "o":"\\\\omega ", "n":"ω" },
	{ "o":"\\\\Delta ", "n":"Δ<span style='font-size:12px;'>(判别式)</span>" },
	{ "o":"\\\\triangle ", "n":"△<span style='font-size:12px;'>(三角形)</span>" },
	{ "o":"\\\\odot ", "n":"⊙" },
	{ "o":"\\\\angle ", "n":"∠" },
	{ "o":"\\\\du ", "n":"°" },
	{ "o":"\\\\perp ", "n":"⊥" },
	{ "o":"\\\\px ", "n":"∥" },
	{ "o":"\\\\sim ", "n":"~" },
	{ "o":"\\\\cong ", "n":"≌" },
	{ "o":"\\\\{a_n\\\\}", "n":"{a<sub style='line-height:12px;'>n</sub>}" },
	{ "o":["\\\\vv{","}"], "n":"箭头向量" },
	{ "o":["\\\\bm{","}"], "n":"粗体向量" }
]) {
    if(v.o instanceof Array) {
        v.o = "input.cha(['" + v.o[0] + "', '" + v.o[1] + "', " + (v.o[2]||0) + ", " + (v.o[3]||0) + "])";
    } else {
        v.o = "input.cha('" + v.o + "')";
    }
    ctrls[1].push(v);
}

renderFastTexSmilies();

if ($("output") && $("inputWrap")) {
	$("output").style.minHeight = $("inputWrap").offsetHeight + "px";
}
