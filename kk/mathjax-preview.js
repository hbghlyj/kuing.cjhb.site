const input = $("inputText");
input.shili = `行内公式：$c=\\sqrt{a^2+b^2}$
行间公式：\\[c=\\sqrt{a^2+b^2}\\]
多行公式：align* 环境（在 & 处对齐）
\\begin{align*}
f(x)&=ax^2+bx+c\\\\
&=a(x-x_1)(x-x_2)\\\\
&=\\cdots
\\end{align*}`;
input.yl = true;
input.oninput = function() {
	if (input.yl) {
		MathJax.texReset();
		var ltx = input.value.replace(/</g,'&lt;').replace(/>/g,'&gt;')
							.replace(/(\\\]|\\end\{align\*?\}|\\end\{gather\*?\}|\\end\{equation\*?\}|\$\$) *\n/g,'$1');
		$("output").innerHTML=ltx;
		MathJax.typesetPromise([$("output")]).catch((err) => console.log('Typeset failed: ' + err.message));
	}
};

//按钮
var ctrls = [[
	{ "n":"暂停预览", "o":"this.innerHTML=((input.yl = !input.yl) ? (input.oninput(), '暂停') : '继续')+'预览'" },
	{ "n":"清空", "o":"input.tmp_input=input.value;input.value='';input.oninput()" },
	{ "n":"示例", "o":`input.tmp_input=input.value;input.value=input.shili;input.oninput()` },
	{ "n":"撤销", "o":"input.value=input.tmp_input;input.oninput()" },
	{ "n":"{}", "o":"input.setSelectionRange((function(){let brace=-1,i=input.selectionStart;do{switch(input.value[--i]){case '{':brace++;break;case '}':brace--;}}while(brace!=0&&i>0)return i})(),(function(){let brace=1,i=input.selectionEnd;do{switch(input.value[i++]){case '{':brace++;break;case '}':brace--;}}while(brace!=0&&i<input.value.length)return i})());input.focus()" },
	//{ "n":"复制代码", "o":"input.select();document.execCommand('Copy');" }
	{ "n":"<span style='color:blue;'>加入编辑框</span>", "o":"input.fangru()" }
],[],['align*','gather*','cases'].map(v=>{ return{"n":v,"o":'input.cha(["\\\\begin{'+v+'}\\n","\\n\\\\end{'+v+'}",0,0])'} })];
ctrls[2].push({ "n":'array',"o":String.raw`const numRows = prompt("Enter the number of rows:");
	const numCols = prompt("Enter the number of columns:");
	if (numRows === null || numCols === null || isNaN(numRows) || isNaN(numCols) || parseInt(numRows) <= 0 || parseInt(numCols) <= 0) {
		alert("Invalid input. Please enter valid positive numbers for rows and columns.");
		return "";
	}
	let latexCode = "\\begin{array}{",n = 0;
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
input.cha([latexCode.slice(0,n),latexCode.slice(n),0,0]);` })
for(v of [
	{ "o":["$ "," $"], "n":"行内公式" },
	{ "o":["\\\\[ "," \\\\]"], "n":"行间公式" },
	{ "o":["\\\\frac{",'}{}',2], "n":"分式" },
	{ "o":["\\\\dfrac{",'}{}',2], "n":"d分式" },
	{ "o":["\\\\sqrt{","}"] , "n":"√▔" },
	{ "o":['\\\\sqrt[]{','}',-2,-2], "n":"<sup style='margin-right:-6px;line-height:14px;'>□</sup>√▔" },
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
//  { "o":"\\\\pqd ", "n":"<sup style='margin-right:-10px;font-size:12px;'>//</sup><sub>=</sub>" },
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
input.cha = function(va) {
	input.tmp_input = input.value;
	var hasSelection = (input.selectionStart || input.selectionStart === 0);
	if (va instanceof Array) {
        [va, vb, n, m] = va;
		if (hasSelection) {
            var start = input.selectionStart,
            end = input.selectionEnd,
            scrollTop = input.scrollTop;
			input.value = input.value.substring(0, start)
            + va
            + input.value.substring(start, end)
            + vb
            + input.value.substring(end);
			input.focus();
			input.scrollTop = scrollTop;
			if (start === end) {
                input.selectionStart =
				input.selectionEnd = start + va.length + m;
			} else if (n === 0) {
                input.selectionStart = start;
				input.selectionEnd = end + va.length + vb.length;
			} else if (n > 0) {
                input.selectionStart =
				input.selectionEnd = end + va.length + n;
			} else {
                input.selectionStart =
				input.selectionEnd = start + va.length + n;
			}
		} else {
            input.value += va + vb;
			input.focus();
		}
    } else {
		if (hasSelection) {
			var start = input.selectionStart,
					end = input.selectionEnd,
					scrollTop = input.scrollTop;
			input.value = input.value.substring(0, start)
										+ va
										+ input.value.substring(end);
			input.focus();
			input.scrollTop = scrollTop;
			input.selectionStart =
			input.selectionEnd = start + va.length;
		} else {
			input.value += va;
			input.focus();
		}
	}
	input.oninput();
};
// Windows 10, 11 has built-in emoji picker, hold the Windows key down and press either the period (.) or semicolon (;) key
if(navigator.userAgent.match(/Windows 7|Windows 8|Windows NT 6|Windows NT 10\.0.*?Chrome\/10[0-9]/)) {
	ctrls[2].push({ "o":"show_emoji_window('#inputText')", "n":"&#x1F603;" });
}
for(annius of ctrls) {
	for(var i = 0; i < annius.length; i++){
        var bt=document.createElement("button");
        bt.type="button";
        bt.className="anniu";
        bt.setAttribute("onclick",annius[i].o);
        bt.innerHTML=annius[i].n;
        $("inputWrap").appendChild(bt);
	}
	$("inputWrap").appendChild(document.createElement("div"));
}

//放入编辑器框
input.fangru=function() {
	var textarea = document.querySelector("#postmessage,#e_textarea,#fastpostmessage");
	if (textarea) {
		textarea.value += input.value;
		textarea.focus();
	} else {alert("找不到编辑框")}
}

input.tmp_input = "";//撤销用
$("output").style.minHeight = $("inputWrap").offsetHeight + "px";
