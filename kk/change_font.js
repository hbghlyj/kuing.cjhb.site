const mathfont_list = {
    "Default":  "Default",
    "Asana": "Asana",
    "Cambria": "Cambria (local only)",
    "DejaVu": "DejaVu",
    "Euler": "Euler Math",
    "FiraMath": "FiraMath",
    "Garamond": "Garamond",
    "GFS_NeoHellenic": "GFS NeoHellenic",
    "LatinModern": "Latin Modern",
    "LeteSansMath": "Lete Sans Math",
    "Libertinus": "Libertinus",
    "LucidaBright": "Lucida Bright (local only)",
    "Minion": "Minion (local only)",
    "NewComputerModern": "New Computer Modern",
    "NewComputerModernSans": "New Computer Modern Sans",
    "NotoSans": "Noto Sans",
    "Plex": "IBM Plex",
    "STIX": "STIX",
    "TeXGyreBonum": "TeX Gyre Bonum",
    "TeXGyrePagella": "TeX Gyre Pagella",
    "TeXGyreSchola": "TeX Gyre Schola",
    "TeXGyreTermes": "TeX Gyre Termes",
    "XITS": "XITS"};
const localFonts = ["Georgia","Arial", "Verdana", "Helvetica", "Tahoma", "TrebuchetMS"];
const localChineseFonts = [
	//GoogleFonts
	"Noto Serif CJK SC","Noto Sans CJK SC","Noto Serif SC","Noto Sans SC",
	// Simplified
	"DengXian","SimSun","PingFangSC","Microsoft YaHei",
	"STZhongsong","STSong","STFangsong",
	"SimHei","STHeiti","STXihei","HeitiSC",
	"KaiTi","FangSong","STKaiti","STXingkai",
	"STCaiyun","STHupo","STXinwei",
	"STLiti","LiSu","YouYuan",
	//Traditional
	"MingLiU","PMingLiU","Microsoft JhengHei","DFKai-SB",
	"PingFangTC","PingFangHK","HeitiTC","MS Gothic"
];
const boldChineseFonts = new Map([
	['Microsoft YaHei', 'Microsoft YaHei Bold'],
	['Microsoft JhengHei', 'Microsoft JhengHei Bold'],
	['DengXian', 'DengXian-Bold'],
	['PingFangSC', 'PingFangSC-Bold']
]);

function setCookie(name, value, days) {
	var expires = "";
	if (days) {
		var date = new Date();
		date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
		expires = "; expires=" + date.toUTCString();
	}
	document.cookie = name + "=" + (value || "") + expires + "; path=/";
}

function getCookie(name) {
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	for (var i = 0; i < ca.length; i++) {
		var c = ca[i];
		while (c.charAt(0) == ' ') c = c.substring(1, c.length);
		if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
	}
	return null;
}

const en_font_style = document.getElementById("en_font_style");
default_en_font_style = en_font_style.textContent;
document.head.appendChild(en_font_style);
const zh_font_style = document.getElementById("zh_font_style");
default_zh_font_style = zh_font_style.textContent;
const zh_bold_style = document.getElementById("zh_bold_style");
const en_font_select = document.getElementById("en_font_select");
const zh_font_select = document.getElementById("zh_font_select");
const zh_bold_select = document.getElementById("zh_bold_select");
const en_font_cookie = getCookie("en_font");
const zh_font_cookie  = getCookie("zh_font");
const zh_bold_cookie = getCookie("zh_bold");

for (let font in mathfont_list) {
	let option = document.createElement("option");
	option.value = font;
	option.innerText = mathfont_list[font];
	en_font_select.appendChild(option);
}
for (let font of localFonts) {
	let option = document.createElement("option");
	option.value = option.innerText = font;
	en_font_select.appendChild(option);
}
for (let font of localChineseFonts) {
	let option = document.createElement("option");
	option.value = option.innerText = font;
	zh_font_select.appendChild(option);
}
if (en_font_cookie) {
	en_font_select.value = en_font_cookie;
	update_en_font();
}
if (zh_font_cookie) {
	zh_font_select.value = zh_font_cookie;
	update_zh_font();
}
if (zh_bold_cookie) {
	zh_bold_select.style.display = '';
	let option = document.createElement("option");
	option.value = option.innerText = zh_bold_cookie;
	zh_bold_select.appendChild(option);
	option.selected = true;
	update_zh_bold();
}
en_font_select.addEventListener("change", () => {
	if (en_font_select.value !== "Default")
		setCookie('en_font',  en_font_select.value, 365);
	else
		setCookie('en_font', '', -1);
	update_en_font();
});
zh_font_select.addEventListener("change", () => {
	if (zh_font_select.value !== "Default")
		setCookie("zh_font", zh_font_select.value, 365);
	else
		setCookie("zh_font", "", -1);
	update_zh_font();
});
zh_bold_select.addEventListener("change", () => {
	if (zh_bold_select.value !== "Default")
		setCookie("zh_bold", zh_bold_select.value, 365);
	else
		setCookie("zh_bold", "", -1);
	update_zh_bold();
});
function update_en_font() {
	const v = en_font_select.value;
	if (v == "Default") {
		en_font_style.textContent = default_en_font_style;
	} else if (localFonts.includes(v)) {
		en_font_style.textContent = `:root { --common-font: "${v}"; }`;
	} else if (mathfont_list[v]) {
		en_font_style.textContent = `@import url(/static/MathFonts/${v}/mathfonts.css);:root { --common-font: "${v}"; }`;
	} else {
		en_font_style.textContent = default_en_font_style;
	}
}
function update_zh_font() {
	const v = zh_font_select.value;
	if (v) {
		zh_font_style.textContent = `@font-face {font-family: zh; src: local("${v}"); `+(zh_bold_select.value?"font-weight: normal;":"")+"}";
	} else {
		zh_font_style.textContent = default_zh_font_style;
	}
}
function update_zh_bold() {
	const bold = zh_bold_select.value;
	if (bold) {
		zh_bold_style.textContent = `@font-face {font-family: zh; src: local("${bold}"); font-weight: bold;}`;
	} else {
		zh_bold_style.textContent = "";
	}
}
zh_font_select.addEventListener('click', async () => {
	try {
		// Create an SVG element
		const svg = document.createElementNS("http://www.w3.org/2000/svg", 'svg');
		svg.setAttribute('width', 500);
		svg.setAttribute('height', 200);
		svg.style.visibility = 'hidden';
		// Create a text element in SVG
		const textElementSVG = document.createElementNS("http://www.w3.org/2000/svg", 'text');
		textElementSVG.setAttribute('x', 0);
		textElementSVG.setAttribute('y', 100);
		textElementSVG.textContent = '(中文测试)';
		textElementSVG.style.fontSize = '72px';
		textElementSVG.style['font-synthesis-weight'] = 'none';
		svg.appendChild(textElementSVG);
		
		// Add the SVG element to the DOM to make `measureText` work
		document.body.appendChild(svg);
		// Measure text with a generic fallback font in SVG
		textElementSVG.setAttribute('font-family','monospace');
		const defaultWidth = textElementSVG.getComputedTextLength();
		
		zh_font_select.innerHTML = zh_bold_select.innerHTML = '';
		for (const fontName of localChineseFonts) {
			// Test normal font weight
			textElementSVG.setAttribute('font-family', `${fontName}, monospace`);
			const testWidth = textElementSVG.getComputedTextLength();
			if (testWidth == defaultWidth) continue;
			
			const option = document.createElement('option');
			option.value = option.innerText = fontName;
			zh_font_select.appendChild(option);
			if (zh_font_cookie && zh_font_cookie == fontName) {
				option.selected = true;
			}
			
			// Test bold font weight
			let boldFontName = fontName;
			const boldOption = document.createElement('option');
			if(fontName.startsWith('Noto')){
				// For Noto fonts, the bold font is the same as the normal font since they are variable fonts
				boldOption.value = '';
			}else{
				if(!boldChineseFonts.has(fontName)) continue;
				boldFontName = boldChineseFonts.get(fontName);
				const normalExtent = textElementSVG.getExtentOfChar(0);
				textElementSVG.setAttribute('font-family', boldFontName);
				const boldExtent = textElementSVG.getExtentOfChar(0);
				if (boldExtent.width != normalExtent.width) continue;
				boldOption.value = boldFontName;
			}
			boldOption.innerText = boldFontName;
			zh_bold_select.appendChild(boldOption);
			if (zh_bold_cookie && zh_bold_cookie == boldFontName) {
				boldOption.selected = true;
			}
		}
		document.body.removeChild(svg);
		if(zh_font_cookie) zh_font_select.value = zh_font_cookie;
		if(zh_bold_cookie) zh_bold_select.value = zh_bold_cookie;
		zh_bold_select.style.display = '';
	} catch (e) {
		showError('Error checking local fonts:', e);
	}
}, { once: true });