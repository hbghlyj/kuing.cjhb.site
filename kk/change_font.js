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
	// Simplified
	"SimSun","NSimSun","Microsoft YaHei","DengXian","SimHei","KaiTi","FangSong",
	"PingFang SC","Heiti SC","STCaiyun","STFangsong","STHupo","STKaiti","STLiti","STSong","STXingkai","STXinwei","STZhongsong","STHeiti","STXihei","Hiragino Sans GB",
	"Source Han Sans SC","WenQuanYi Zen Hei","WenQuanYi Micro Hei","YouYuan","LiSu","Yu Gothic",
	// Traditional
	"MingLiU","PMingLiU","Microsoft JhengHei","DFKai-SB",
	"PingFang TC","PingFang HK","Heiti TC","LiHei Pro Medium","LiSong Pro Light","Source Han Sans TC",
	// Google Fonts
	"Noto Sans SC","Noto Serif TC","Noto Serif HK"
];

function isFontAvailable(fontName) {
    const text = "床前明月光，疑是地上霜。举头望明月，低头思故乡。"; // reliable detection for Chinese fonts
    const canvas = document.createElement("canvas");
    const context = canvas.getContext("2d");

    // Measure text with a generic fallback font
    context.font = "72px serif"; // Using serif as a generic fallback
    const defaultMetrics = context.measureText(text);

    // Measure text with the target font, falling back to serif
    context.font = `72px "${fontName}", serif`;
    const testMetrics = context.measureText(text);

    // Compare a set of TextMetrics properties
    // If any of these differ, the font is considered available and distinct
    if (defaultMetrics.width !== testMetrics.width) return true;
    if (defaultMetrics.actualBoundingBoxAscent !== testMetrics.actualBoundingBoxAscent) return true;
    if (defaultMetrics.actualBoundingBoxDescent !== testMetrics.actualBoundingBoxDescent) return true;
    if (defaultMetrics.actualBoundingBoxLeft !== testMetrics.actualBoundingBoxLeft) return true;
    if (defaultMetrics.actualBoundingBoxRight !== testMetrics.actualBoundingBoxRight) return true;

    return false; // If all compared metrics are the same, assume font is not available or not distinct enough
}

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
const zh_font_style = document.getElementById("zh_font_style");
default_zh_font_style = zh_font_style.textContent;
const en_font_select = document.getElementById("en_font_select");
const zh_font_select = document.getElementById("zh_font_select");
const en_font_cookie = getCookie("en_font");
const zh_font_cookie  = getCookie("zh_font");

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
for (let font of ["Default", ...localChineseFonts]) {
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
function update_en_font() {
	const v = en_font_select.value;
	if (v == "Default") {
		en_font_style.textContent = default_en_font_style;
	} else if (localFonts.includes(v)) {
		en_font_style.textContent = `body, input, button, select, textarea, .xst, .ts, #thread_subject { font-family: ${v}, "Twemoji Country Flags",zh,"Noto Colr Emoji Glyf"; }`;
	} else if (mathfont_list[v]) {
		en_font_style.textContent = `@import url(/static/MathFonts/${v}/mathfonts.css);`;
	} else {
		en_font_style.textContent = default_en_font_style;
	}
}
function update_zh_font() {
	const v = zh_font_select.value;
	if (v == "Default") {
		zh_font_style.textContent = default_zh_font_style;
	} else if (localChineseFonts.includes(v)) {
		zh_font_style.textContent = `@font-face {font-family: 'zh';src: local('${v}');}`;
	} else {
		zh_font_style.textContent = default_zh_font_style;
	}
}
zh_font_select.addEventListener('click', async () => {
	try {
		const found = ["Default"];
		for (const name of localChineseFonts) {
			if (isFontAvailable(name)) {
				found.push(name);
			}
		}
		zh_font_select.innerHTML = ''; // Clear existing options
		for (const font of found) {
			const option = document.createElement('option');
			option.value = option.innerText = font;
			zh_font_select.appendChild(option);
		}
		if(zh_font_cookie && found.includes(zh_font_cookie)) {
			zh_font_select.value = zh_font_cookie;
		}
	} catch (e) {
		showError('Error checking local fonts:', e);
	}
}, { once: true });