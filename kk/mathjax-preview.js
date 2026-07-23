function parseBBCode(str) {
	if (!str) return '';
	var html = str.replace(/</g, '&lt;').replace(/>/g, '&gt;');
	html = html.replace(/(\\\]|\\end\{align\*?\}|\\end\{gather\*?\}|\\end\{equation\*?\}|\$\$) *\n/g, '$1');

	var prev;
	do {
		prev = html;
		html = html
			.replace(/\[b\]([\s\S]*?)\[\/b\]/gi, '<b>$1</b>')
			.replace(/\[i\]([\s\S]*?)\[\/i\]/gi, '<i>$1</i>')
			.replace(/\[u\]([\s\S]*?)\[\/u\]/gi, '<u>$1</u>')
			.replace(/\[s\]([\s\S]*?)\[\/s\]/gi, '<del>$1</del>')
			.replace(/\[color=([^\]]+)\]([\s\S]*?)\[\/color\]/gi, '<span style="color:$1">$2</span>')
			.replace(/\[backcolor=([^\]]+)\]([\s\S]*?)\[\/backcolor\]/gi, '<span style="background-color:$1">$2</span>')
			.replace(/\[size=(\d+)\]([\s\S]*?)\[\/size\]/gi, function(_, sz, text) {
				var sizes = ['10px', '12px', '14px', '16px', '18px', '24px', '32px'];
				var fontSize = sizes[parseInt(sz) - 1] || (sz + 'px');
				return '<span style="font-size:' + fontSize + '">' + text + '</span>';
			})
			.replace(/\[font=([^\]]+)\]([\s\S]*?)\[\/font\]/gi, '<span style="font-family:$1">$2</span>')
			.replace(/\[align=(left|center|right)\]([\s\S]*?)\[\/align\]/gi, '<div style="text-align:$1">$2</div>')
			.replace(/\[url=([^\]]+)\]([\s\S]*?)\[\/url\]/gi, '<a href="$1" target="_blank" rel="noopener">$2</a>')
			.replace(/\[url\]([\s\S]*?)\[\/url\]/gi, '<a href="$1" target="_blank" rel="noopener">$1</a>')
			.replace(/\[img=(\d+),(\d+)\]([\s\S]*?)\[\/img\]/gi, '<img src="$3" width="$1" height="$2" />')
			.replace(/\[img\]([\s\S]*?)\[\/img\]/gi, '<img src="$1" style="max-width:100%" />')
			.replace(/\[quote\]([\s\S]*?)\[\/quote\]/gi, '<div class="quote"><blockquote>$1</blockquote></div>')
			.replace(/\[code\]([\s\S]*?)\[\/code\]/gi, '<div class="blockcode"><pre>$1</pre></div>')
			.replace(/\[tikz\]([\s\S]*?)\[\/tikz\]/gi, '<div class="blockcode"><pre>$1</pre></div>')
			.replace(/\[asy\]([\s\S]*?)\[\/asy\]/gi, '<div class="blockcode"><pre>$1</pre></div>')
			.replace(/\[hr\]/gi, '<hr />');
	} while (html !== prev);

	return html;
}

function initLivePreview() {
	var output = $("output");
	if (!output) return;

	var updatePreview = function(el) {
		if (!el) return;
		MathJax.texReset();
		output.innerHTML = parseBBCode(el.value || '');
		if (typeof MathJax !== 'undefined' && MathJax.typesetPromise) {
			MathJax.typesetPromise([output]).catch(() => {});
		}
	};

	var textareas = document.querySelectorAll("#fastpostmessage, #postmessage, #e_textarea, #inputText");
	textareas.forEach(function(textarea) {
		textarea.addEventListener('input', function() {
			updatePreview(this);
		});
		textarea.addEventListener('keyup', function() {
			updatePreview(this);
		});
		updatePreview(textarea);
	});
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
	
	var event = new Event('input', { bubbles: true });
	textarea.dispatchEvent(event);
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

function selectBracePair() {
	var targetEl = (document.activeElement && (document.activeElement.id === 'fastpostmessage' || document.activeElement.id === 'postmessage' || document.activeElement.id === 'e_textarea' || document.activeElement.id === 'inputText'))
		? document.activeElement
		: document.querySelector("#fastpostmessage, #postmessage, #e_textarea, #inputText");

	if (!targetEl || !targetEl.setSelectionRange) return;

	let start = (function() {
		let brace = -1, i = targetEl.selectionStart;
		do {
			switch(targetEl.value[--i]) {
				case '{': brace++; break;
				case '}': brace--; break;
			}
		} while (brace !== 0 && i > 0);
		return i;
	})();

	let end = (function() {
		let brace = 1, i = targetEl.selectionEnd;
		do {
			switch(targetEl.value[i++]) {
				case '{': brace++; break;
				case '}': brace--; break;
			}
		} while (brace !== 0 && i < targetEl.value.length);
		return i;
	})();

	targetEl.setSelectionRange(start, end);
	targetEl.focus();
}

// 快捷 TeX 公式数据（按使用频率排序）
var fastTexItems = [
	// 基础结构
	{ "n": "行内公式", "o": ["$", "$"] },
	{ "n": "行间公式", "o": ["\\[\n", "\n\\]", 0, 0] },
	{ "n": "{}", "o": selectBracePair },
	{ "n": "$\\frac{a}{b}$", "o": ["\\frac{", "}{}", 2] },
	{ "n": "$\\sqrt{x}$", "o": ["\\sqrt{", "}"] },
	{ "n": "$\\sqrt[n]{x}$", "o": ["\\sqrt[]{", "}", -2, -2] },

	// 微积分与极限
	{ "n": "$\\int\\rmd x$", "o": ["\\int ", "\\rmd x"] },
	{ "n": "$\\lim_{x\\to 0}$", "o": ["\\lim_{x\\to ", "}"] },
	{ "n": "$\\infty$", "o": "\\infty " },
	{ "n": "$\\partial$", "o": "\\partial " },
	{ "n": "$\\nabla$", "o": "\\nabla " },
	{ "n": "$\\cdots$", "o": "\\cdots " },

	// 关系与运算
	{ "n": "$\\leqslant$", "o": "\\leqslant " },
	{ "n": "$\\geqslant$", "o": "\\geqslant " },
	{ "n": "$\\times$", "o": "\\times " },
	{ "n": "$\\cdot$", "o": "\\cdot " },
	{ "n": "$\\approx$", "o": "\\approx " },
	{ "n": "$\\equiv$", "o": "\\equiv " },

	// 函数与序列
	{ "n": "$\\ln$", "o": "\\ln " },
	{ "n": "$\\log$", "o": "\\log " },
	{ "n": "$\\pmod{m}$", "o": ["\\pmod{", "}"] },
	{ "n": "$\\sin$", "o": "\\sin " },
	{ "n": "$\\cos$", "o": "\\cos " },
	{ "n": "$\\tan$", "o": "\\tan " },
	{ "n": "$\\{a_n\\}$", "o": "\\{a_n\\}" },
	{ "n": "$\\vec{v}$", "o": ["\\vv{", "}"] },
	{ "n": "$\\mathbf{v}$", "o": ["\\bm{", "}"] },

	// 希腊字母
	{ "n": "$\\alpha$", "o": "\\alpha " },
	{ "n": "$\\beta$", "o": "\\beta " },
	{ "n": "$\\gamma$", "o": "\\gamma " },
	{ "n": "$\\theta$", "o": "\\theta " },
	{ "n": "$\\lambda$", "o": "\\lambda " },
	{ "n": "$\\varepsilon$", "o": "\\veps " },
	{ "n": "$\\varphi$", "o": "\\varphi " },
	{ "n": "$\\omega$", "o": "\\omega " },
	{ "n": "$\\Delta$", "o": "\\Delta " },

	// 几何符号
	{ "n": "$\\triangle$", "o": "\\triangle " },
	{ "n": "$\\angle$", "o": "\\angle " },
	{ "n": "$^{\\circ}$", "o": "\\du " },
	{ "n": "$\\perp$", "o": "\\perp " },
	{ "n": "$\\parallel$", "o": "\\px " },
	{ "n": "$\\odot$", "o": "\\odot " },
	{ "n": "$\\sim$", "o": "\\sim " },
	{ "n": "$\\cong$", "o": "\\cong " },

	// 环境
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
}

renderFastTexSmilies();
initLivePreview();
