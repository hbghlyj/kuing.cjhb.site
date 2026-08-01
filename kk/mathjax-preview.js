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
		var columns = 3;
		fs.innerHTML = '';
		var table = document.createElement("table");
		table.className = "cp0";
		table.style.width = "160px";
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
	if (!button || document.getElementById('post_math_button')) return;

	var labels = window.MATH_EDITOR_LABELS || {};
	var entryGroup = document.createElement('div');
	entryGroup.id = 'post_math_button';
	entryGroup.className = 'b2r';
	var entry = document.createElement('a');
	entry.href = 'javascript:;';
	entry.className = 'mathfx';
	entry.innerHTML = '<i>f</i>x';
	entry.title = labels.title || 'Insert/Edit Math';
	entry.onclick = function(event) {
		if (event) event.preventDefault();
		showFullEditorMathDialog(labels, getSelectedMathEquation());
		return false;
	};
	entryGroup.appendChild(entry);
	button.insertBefore(entryGroup, button.firstChild);
	renderMathEditorContent();
}

function escapeMathHtml(value) {
	return String(value).replace(/[&<>"']/g, function(character) {
		return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[character];
	});
}

function getMathDialogValue(value) {
	var display = /^\$\$([\s\S]*)\$\$$/.exec(value);
	if (display) return { equation: display[1], wrap: 'display' };
	var inline = /^\$([^$][\s\S]*?)\$$/.exec(value);
	if (inline) return { equation: inline[1], wrap: 'inline' };
	return { equation: value, wrap: 'inline' };
}

function getSelectedMathEquation() {
	return wysiwyg && editdoc ? editdoc.querySelector('.math-editor-rendered.math-editor-selected') : null;
}

function createMathEditorCaret() {
	var caret = editdoc.createElement('span');
	caret.className = 'math-editor-caret';
	caret.setAttribute('data-math-caret', '1');
	caret.setAttribute('aria-hidden', 'true');
	caret.textContent = '\u200B';
	return caret;
}

function ensureMathEditorCarets(rendered) {
	if (!rendered.parentNode) return;
	if (!rendered.previousSibling || rendered.previousSibling.nodeType !== 1 || !rendered.previousSibling.hasAttribute('data-math-caret')) {
		rendered.parentNode.insertBefore(createMathEditorCaret(), rendered);
	}
	if (!rendered.nextSibling || rendered.nextSibling.nodeType !== 1 || !rendered.nextSibling.hasAttribute('data-math-caret')) {
		rendered.parentNode.insertBefore(createMathEditorCaret(), rendered.nextSibling);
	}
}

function placeMathEditorCaret(rendered, after) {
	ensureMathEditorCarets(rendered);
	var caret = after ? rendered.nextSibling : rendered.previousSibling;
	if (!caret || !caret.firstChild) return;
	var range = editdoc.createRange();
	range.setStart(caret.firstChild, after ? caret.firstChild.nodeValue.length : 0);
	range.collapse(true);
	var selection = editwin.getSelection();
	selection.removeAllRanges();
	selection.addRange(range);
}

function selectMathEquation(rendered) {
	if (!editdoc) return;
	clearMathEquationSelection();
	rendered.classList.add('math-editor-selected');
	var container = rendered.querySelector('mjx-container');
	if (container) container.classList.add('math-editor-selected');
	var button = document.querySelector('#post_math_button .mathfx');
	if (button) button.classList.add('hover');
	if (typeof setEditorTip === 'function') {
		setEditorTip((window.MATH_EDITOR_LABELS && window.MATH_EDITOR_LABELS.selected) || 'Double-click the formula to edit it. Press Escape to deselect.');
	}
}

function clearMathEquationSelection() {
	if (!editdoc) return;
	var selected = editdoc.querySelectorAll('.math-editor-rendered.math-editor-selected, mjx-container.math-editor-selected');
	for (var i = 0; i < selected.length; i++) {
		selected[i].classList.remove('math-editor-selected');
	}
	if (selected.length) {
		var button = document.querySelector('#post_math_button .mathfx');
		if (button) button.classList.remove('hover');
		if (typeof setEditorTip === 'function') setEditorTip('');
	}
}

function initMathEditorSelection() {
	if (!editdoc || editdoc._mathEditorSelectionInitialized) return;
	editdoc._mathEditorSelectionInitialized = true;
	editdoc.addEventListener('click', function(event) {
		var rendered = event.target.closest ? event.target.closest('.math-editor-rendered') : null;
		if (!rendered) {
			clearMathEquationSelection();
		} else if (!rendered._mathEditorInitialized) {
			event.preventDefault();
			selectMathEquation(rendered);
			var bounds = rendered.getBoundingClientRect();
			placeMathEditorCaret(rendered, event.clientX >= bounds.left + bounds.width / 2);
		}
	});
	editdoc.addEventListener('dblclick', function(event) {
		var rendered = event.target.closest ? event.target.closest('.math-editor-rendered') : null;
		if (!rendered || rendered._mathEditorInitialized) return;
		event.preventDefault();
		showFullEditorMathDialog(window.MATH_EDITOR_LABELS || {}, rendered);
	});
	editdoc.addEventListener('keydown', function(event) {
		if (event.key === 'Escape') clearMathEquationSelection();
	});
}

function syncMathJaxEditorStyles() {
	if (!editdoc || !editdoc.head) return;

	var sources = document.querySelectorAll('style[id^="MJX-"], link[id^="MJX-"]');
	for (var i = 0; i < sources.length; i++) {
		var source = sources[i];
		var id = 'math_editor_' + source.id;
		var target = editdoc.getElementById(id);
		if (!target) {
			target = editdoc.createElement(source.tagName.toLowerCase());
			target.id = id;
			editdoc.head.appendChild(target);
		}
		if (source.tagName === 'LINK') {
			target.rel = source.rel;
			target.href = source.href;
		} else {
			var cssText = source.textContent;
			if (source.sheet && source.sheet.cssRules) {
				cssText = Array.from(source.sheet.cssRules, function(rule) { return rule.cssText; }).join('\n');
			}
			target.textContent = cssText;
		}
	}
}

var pendingMathEditorEquations = [];
var mathEditorLoaderListening = false;

function flushPendingMathEditorEquations() {
	if (typeof MathJax === 'undefined' || typeof MathJax.typesetPromise !== 'function') return;
	var pending = pendingMathEditorEquations;
	pendingMathEditorEquations = [];
	for (var i = 0; i < pending.length; i++) {
		if (pending[i].rendered.isConnected) renderMathEquation(pending[i].rendered, pending[i].math);
	}
}

function queueMathEditorEquation(rendered, math) {
	var queued = false;
	for (var i = 0; i < pendingMathEditorEquations.length; i++) {
		if (pendingMathEditorEquations[i].rendered === rendered) {
			pendingMathEditorEquations[i].math = math;
			queued = true;
			break;
		}
	}
	if (!queued) pendingMathEditorEquations.push({ rendered: rendered, math: math });
	if (mathEditorLoaderListening) return;
	mathEditorLoaderListening = true;
	var loader = document.querySelector('script[src*="/mathjax@4/tex-chtml.js"]');
	if (loader) loader.addEventListener('load', flushPendingMathEditorEquations, { once: true });
	window.addEventListener('load', flushPendingMathEditorEquations, { once: true });
}

function removeMathEditorDisplayBreaks(rendered) {
	if (!rendered.querySelector('mjx-container[display="true"]')) return;
	for (var direction = -1; direction <= 1; direction += 2) {
		var sibling = direction < 0 ? rendered.previousSibling : rendered.nextSibling;
		while (sibling && ((sibling.nodeType === 1 && sibling.hasAttribute('data-math-caret')) || (sibling.nodeType === 3 && !sibling.nodeValue.trim()))) {
			sibling = direction < 0 ? sibling.previousSibling : sibling.nextSibling;
		}
		if (sibling && sibling.nodeType === 1 && sibling.matches('br')) sibling.remove();
	}
}

function renderMathEquation(rendered, math) {
	ensureMathEditorCarets(rendered);
	if (typeof MathJax !== 'undefined' && typeof MathJax.typesetClear === 'function') MathJax.typesetClear([rendered]);
	rendered.setAttribute('data-math-source', math);
	rendered.textContent = math;
	if (typeof MathJax === 'undefined' || typeof MathJax.typesetPromise !== 'function') {
		queueMathEditorEquation(rendered, math);
		return;
	}
	MathJax.typesetPromise([rendered]).then(function() {
		syncMathJaxEditorStyles();
		removeMathEditorDisplayBreaks(rendered);
		if (rendered.classList.contains('math-editor-selected')) selectMathEquation(rendered);
	}).catch(function() {});
}

function initRenderedMathEquation(rendered) {
	if (rendered._mathEditorInitialized) return;
	rendered._mathEditorInitialized = true;
	rendered.addEventListener('click', function(event) {
		event.preventDefault();
		event.stopPropagation();
		selectMathEquation(rendered);
		var bounds = rendered.getBoundingClientRect();
		placeMathEditorCaret(rendered, event.clientX >= bounds.left + bounds.width / 2);
	});
	rendered.addEventListener('dblclick', function(event) {
		event.preventDefault();
		event.stopPropagation();
		showFullEditorMathDialog(window.MATH_EDITOR_LABELS || {}, rendered);
	});
}

function isEscapedMathDelimiter(text, index) {
	var slashCount = 0;
	while (index > 0 && text[--index] === '\\') slashCount++;
	return slashCount % 2 === 1;
}

function findMathDelimiter(text, start, opening, closing, standaloneDollar) {
	var index = text.indexOf(closing, start + opening.length);
	while (index !== -1) {
		if (!isEscapedMathDelimiter(text, index) && (!standaloneDollar || (text[index - 1] !== '$' && text[index + 1] !== '$'))) return index + closing.length;
		index = text.indexOf(closing, index + closing.length);
	}
	return -1;
}

function findMathRanges(text) {
	var ranges = [];
	for (var index = 0; index < text.length; index++) {
		if (isEscapedMathDelimiter(text, index)) continue;
		var end = -1;
		if (text.slice(index, index + 2) === '$$') {
			end = findMathDelimiter(text, index, '$$', '$$');
		} else if (text[index] === '$' && text[index - 1] !== '$' && text[index + 1] !== '$') {
			end = findMathDelimiter(text, index, '$', '$', true);
		} else if (text.slice(index, index + 2) === '\\[') {
			end = findMathDelimiter(text, index, '\\[', '\\]');
		} else if (text.slice(index, index + 2) === '\\(') {
			end = findMathDelimiter(text, index, '\\(', '\\)');
		} else if (text.slice(index, index + 7) === '\\begin{') {
			var environment = /^\\begin\{([A-Za-z0-9*]+)\}/.exec(text.slice(index));
			if (environment) end = findMathDelimiter(text, index, environment[0], '\\end{' + environment[1] + '}');
		}
		if (end !== -1) {
			ranges.push({ start: index, end: end });
			index = end - 1;
		}
	}
	return ranges;
}

function renderMathEditorContent() {
	if (!wysiwyg || !editdoc || !editdoc.body) return;
	initMathEditorSelection();

	var nodes = [];
	var walker = editdoc.createTreeWalker(editdoc.body, 4);
	var node;
	while ((node = walker.nextNode())) {
		if (node.parentNode.closest('.math-editor-rendered, code, pre, script, style')) continue;
		if (findMathRanges(node.nodeValue).length) nodes.push(node);
	}

	for (var i = 0; i < nodes.length; i++) {
		var text = nodes[i].nodeValue;
		var ranges = findMathRanges(text);
		var fragment = editdoc.createDocumentFragment();
		var formulas = [];
		var index = 0;
		for (var rangeIndex = 0; rangeIndex < ranges.length; rangeIndex++) {
			var range = ranges[rangeIndex];
			if (range.start > index) fragment.appendChild(editdoc.createTextNode(text.slice(index, range.start)));
			var math = text.slice(range.start, range.end);
			var rendered = editdoc.createElement('span');
			rendered.className = 'math-editor-rendered';
			rendered.setAttribute('data-math-source', math);
			rendered.setAttribute('contenteditable', 'false');
			initRenderedMathEquation(rendered);
			fragment.appendChild(rendered);
			formulas.push({ rendered: rendered, math: math });
			index = range.end;
		}
		if (index < text.length) fragment.appendChild(editdoc.createTextNode(text.slice(index)));
		nodes[i].parentNode.replaceChild(fragment, nodes[i]);
		for (var j = 0; j < formulas.length; j++) {
			renderMathEquation(formulas[j].rendered, formulas[j].math);
		}
	}
}

function insertMathEquation(math) {
	if (!wysiwyg || !editdoc) {
		insertText(math, false);
		return;
	}

	var id = 'math_editor_render_' + Date.now() + '_' + Math.floor(Math.random() * 10000);
	var formula = '<span id="' + id + '" class="math-editor-rendered" data-math-source="' + escapeMathHtml(math) + '" contenteditable="false">' + escapeMathHtml(math) + '</span>';
	insertText(formula, false);
	var rendered = editdoc.getElementById(id);
	if (!rendered) return;
	rendered.removeAttribute('id');
	initRenderedMathEquation(rendered);
	renderMathEquation(rendered, math);
}

function showFullEditorMathDialog(labels, rendered) {
	var selected = getMathDialogValue(rendered ? rendered.getAttribute('data-math-source') : (getSel() || ''));
	var title = labels.title || 'Insert/Edit Math';
	var equationLabel = labels.equation || 'Math equation';
	var wrapLabel = labels.wrap || 'Text wrap';
	var inlineLabel = (labels.inline || 'Inline') + ' ($...$)';
	var displayLabel = (labels.display || 'Display') + ' ($$...$$)';
	var content = '<div class="math-editor-dialog">' +
		'<p><label for="math_editor_equation">' + escapeMathHtml(equationLabel) + '</label>' +
		'<textarea id="math_editor_equation" class="pt" rows="8"></textarea></p>' +
		'<p><label for="math_editor_wrap">' + escapeMathHtml(wrapLabel) + '</label>' +
		'<select id="math_editor_wrap"><option value="inline">' + escapeMathHtml(inlineLabel) + '</option>' +
		'<option value="display">' + escapeMathHtml(displayLabel) + '</option></select></p>' +
		'<div id="math_editor_preview" class="math-editor-preview" aria-live="polite"></div>' +
		'</div>';

	showDialog(content, 'confirm', title, function() {
		var equation = document.getElementById('math_editor_equation');
		var wrap = document.getElementById('math_editor_wrap');
		if (!equation || !wrap || equation.value === '') return;
		var math = wrap.value === 'display' ? '$$' + equation.value + '$$' : '$' + equation.value + '$';
		if (rendered) {
			renderMathEquation(rendered, math);
		} else {
			insertMathEquation(math);
		}
	}, 1, null, '', labels.save || 'Save', labels.cancel || 'Cancel');

	setTimeout(function() {
		var equation = document.getElementById('math_editor_equation');
		var wrap = document.getElementById('math_editor_wrap');
		var preview = document.getElementById('math_editor_preview');
		if (!equation || !wrap || !preview) return;
		var previewVersion = 0;
		var updatePreview = function() {
			var math = wrap.value === 'display' ? '$$' + equation.value + '$$' : '$' + equation.value + '$';
			preview.textContent = math;
			if (typeof MathJax === 'undefined' || typeof MathJax.typesetPromise !== 'function') return;
			if (typeof MathJax.typesetClear === 'function') MathJax.typesetClear([preview]);
			var version = ++previewVersion;
			MathJax.typesetPromise([preview]).catch(function() {
				if (version === previewVersion) preview.textContent = math;
			});
		};
		equation.value = selected.equation;
		wrap.value = selected.wrap;
		equation.addEventListener('input', updatePreview);
		wrap.addEventListener('change', updatePreview);
		updatePreview();
		equation.focus();
		equation.select();
	}, 0);
}

function initMathJaxPreview() {
	renderFastTexSmilies();
	initFullEditorMathEntry();
}

initMathJaxPreview();
window.renderMathEditorContent = renderMathEditorContent;
