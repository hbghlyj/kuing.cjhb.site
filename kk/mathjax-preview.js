function initLivePreview() {
	var output = $("output");
	if (!output) return;

	var updatePreview = function(el) {
		if (!el) return;
		if (typeof MathJax !== 'undefined' && typeof MathJax.texReset === 'function') {
			MathJax.texReset();
		}
		output.innerHTML = bbcode2html(el.value || '');
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
	{ "n": "$\\alpha$", "o": "\\alpha", "greek": true },

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

var greekTexItems = [
	'alpha', 'beta', 'gamma', 'delta', 'epsilon', 'zeta', 'eta', 'theta', 'iota', 'kappa', 'lambda', 'mu', 'nu', 'xi', 'o', 'pi', 'rho', 'sigma', 'tau', 'upsilon', 'phi', 'chi', 'psi', 'omega',
	'Gamma', 'Delta', 'Theta', 'Lambda', 'Xi', 'Pi', 'Sigma', 'Upsilon', 'Phi', 'Psi', 'Omega'
];

function createGreekMenu() {
	var menu = document.createElement('div');
	menu.className = 'tex_greek_menu';
	menu.innerHTML = '$\\alpha$';

	var palette = document.createElement('div');
	palette.className = 'tex_greek_palette';
	for (var i = 0; i < greekTexItems.length; i++) {
		var name = greekTexItems[i];
		var letter = document.createElement('a');
		letter.href = 'javascript:;';
		letter.title = '\\' + name;
		letter.innerHTML = name === 'o' ? 'o' : '$\\' + name + '$';
		letter.onclick = (function(command) {
			return function(event) {
				event.preventDefault();
				event.stopPropagation();
				insertTexToEditor(command === 'o' ? 'o' : '\\' + command);
			};
		})(name);
		palette.appendChild(letter);
	}
	menu.appendChild(palette);
	menu.onclick = function() {
		insertTexToEditor('\\alpha');
	};
	return menu;
}

function renderFastTexSmilies() {
	var fs = $("fastsmilies");
	if (fs) {
		var controlPanel = fs.closest('.post_fasttex_panel');
		var columns = controlPanel ? 10 : 3;
		fs.innerHTML = '';
		var table = document.createElement("table");
		table.className = "cp0";
		table.style.width = controlPanel ? "auto" : "160px";
		table.style.tableLayout = "auto";
		var tr = document.createElement("tr");

		for (var i = 0; i < fastTexItems.length; i++) {
			if (i > 0 && i % columns === 0) {
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
			if (item.greek) {
				td.className = 'tex_greek_cell';
				td.appendChild(createGreekMenu());
			} else {
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
			}

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

function initFullEditorMathEntry() {
	var textarea = document.querySelector('.edt textarea[id$="_textarea"]');
	if (!textarea || !textarea.id.endsWith('_textarea')) return;

	var editorid = textarea.id.slice(0, -'_textarea'.length);
	var button = document.getElementById(editorid + '_button');
	if (!button || document.getElementById('post_math_entry')) return;

	var labels = window.MATH_EDITOR_LABELS || {};
	textarea.placeholder = labels.hint || 'Inline formula: $...$; display formula: \\[ ... \\]';
	var entryGroup = document.createElement('div');
	entryGroup.id = 'post_math_entry';
	entryGroup.className = 'b2r';
	[
		{ label: labels.inline || 'Inline', code: ['$', '$'] },
		{ label: labels.display || 'Display', code: ['\\[\n', '\n\\]', 0, 0] }
	].forEach(function(item) {
		var entry = document.createElement('a');
		entry.href = 'javascript:;';
		entry.textContent = item.label;
		entry.title = item.label;
		entry.onclick = function() {
			insertTexToEditor(item.code);
			return false;
		};
		entryGroup.appendChild(entry);
	});
	button.insertBefore(entryGroup, button.firstChild);
}

function initFastTexMode() {
	var textarea = document.querySelector('.edt textarea[id$="_textarea"]');
	if (!textarea || !textarea.id.endsWith('_textarea')) return;

	var editorid = textarea.id.slice(0, -'_textarea'.length);
	var controls = document.getElementById(editorid + '_controls');
	var panel = document.getElementById('fastsmiliesdiv');
	if (!controls || !panel || !panel.classList.contains('post_fasttex_panel')) return;

	var marker = document.createComment('fast TeX panel');
	panel.parentNode.insertBefore(marker, panel);

	function isEscaped(value, index) {
		var slashes = 0;
		while (index > 0 && value[--index] === '\\') slashes++;
		return slashes % 2 === 1;
	}

	function isMathCursor(value, cursor) {
		var before = value.slice(0, cursor);
		if (before.lastIndexOf('\\[') > before.lastIndexOf('\\]') || before.lastIndexOf('\\(') > before.lastIndexOf('\\)')) return true;
		if ((before.match(/\\begin\{[^}]+\}/g) || []).length > (before.match(/\\end\{[^}]+\}/g) || []).length) return true;

		var inline = false;
		var display = false;
		for (var i = 0; i < before.length; i++) {
			if (before[i] !== '$' || isEscaped(before, i)) continue;
			if (before[i + 1] === '$') {
				display = !display;
				i++;
			} else if (!display) {
				inline = !inline;
			}
		}
		return inline || display;
	}

	function update() {
		if (isMathCursor(textarea.value, textarea.selectionStart)) {
			controls.appendChild(panel);
			controls.classList.add('math_mode');
		} else {
			marker.parentNode.insertBefore(panel, marker.nextSibling);
			controls.classList.remove('math_mode');
		}
	}

	['input', 'keyup', 'click', 'focus'].forEach(function(eventName) {
		textarea.addEventListener(eventName, update);
	});
	document.addEventListener('selectionchange', function() {
		if (document.activeElement === textarea) update();
	});
	update();
}

function initMathJaxPreview() {
	renderFastTexSmilies();
	initFullEditorMathEntry();
	initFastTexMode();
	initLivePreview();
}

if (typeof bbcode2html === 'function') {
	initMathJaxPreview();
} else {
	// Simple editors do not include bbcode.js, unlike the full post editor.
	// Supply safe preview defaults before loading the shared converter.
	window.EXTRAFUNC = window.EXTRAFUNC || [];
	window.allowhtml = window.allowhtml || 0;
	window.allowsmilies = window.allowsmilies || 0;
	window.allowbbcode = typeof window.allowbbcode === 'undefined' ? 1 : window.allowbbcode;
	window.allowimgcode = typeof window.allowimgcode === 'undefined' ? 1 : window.allowimgcode;

	var bbcodeScript = document.createElement('script');
	bbcodeScript.src = (typeof JSPATH !== 'undefined' ? JSPATH : 'static/js/') + 'bbcode.js';
	bbcodeScript.onload = initMathJaxPreview;
	document.head.appendChild(bbcodeScript);
}
