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
	"SimSun","STZhongsong","NSimSun","STSong","STFangsong",
	"SimHei","STHeiti","STXihei","Heiti SC","WenQuanYi Zen Hei","WenQuanYi Micro Hei",
	"Microsoft YaHei","DengXian","PingFang SC","Source Han Sans SC",
	"KaiTi","FangSong","STKaiti","STXingkai",
	"STCaiyun","STHupo","STXinwei",
	"STLiti","LiSu","Yu Gothic","YouYuan",
	// Traditional
	"MingLiU","PMingLiU","Microsoft JhengHei","DFKai-SB",
	"PingFang TC","PingFang HK","Heiti TC","LiHei Pro Medium","LiSong Pro Light","Source Han Sans TC",
	// Google Fonts
	"Noto Sans SC","Noto Serif TC","Noto Serif HK"
];

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
zh_bold_select.addEventListener("change", () => {
	if (zh_bold_select.value !== "Default")
		setCookie("zh_bold", zh_bold_select.value, 365);
	else
		setCookie("zh_bold", "", -1);
	update_zh_font();
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
	if (v!="Default" && localChineseFonts.includes(v)) {
		zh_font_style.textContent = `@font-face {font-family: zh; src: local("${v}"); font-weight: normal; }`;
	} else {
		zh_font_style.textContent = default_zh_font_style;
	}
	const bold = zh_bold_select.value;
	if (bold != "Default" && localChineseFonts.includes(bold)) {
		zh_font_style.textContent += `@font-face {font-family: zh; src: local("${bold}"); font-weight: bold; }`;
	}
}
zh_font_select.addEventListener('click', async () => {
	try {
		const text = "床前明月光，疑是地上霜。举头望明月，低头思故乡。"; // reliable detection for Chinese fonts
		const canvas = document.createElement("canvas");
		const context = canvas.getContext("2d");

		// Measure text with a generic fallback font (once)
		context.font = "72px serif"; // Using serif as a generic fallback
		const defaultMetrics = context.measureText(text);

		const found = ["Default"];
		for (const name of localChineseFonts) {
			// Measure text with the target font, falling back to serif
			context.font = `72px "${name}", serif`;
			const testMetrics = context.measureText(text);

			// Compare a set of TextMetrics properties
			let isAvailable = false;
			if (defaultMetrics.width !== testMetrics.width) isAvailable = true;
			else if (defaultMetrics.actualBoundingBoxAscent !== testMetrics.actualBoundingBoxAscent) isAvailable = true;
			else if (defaultMetrics.actualBoundingBoxDescent !== testMetrics.actualBoundingBoxDescent) isAvailable = true;
			else if (defaultMetrics.actualBoundingBoxLeft !== testMetrics.actualBoundingBoxLeft) isAvailable = true;
			else if (defaultMetrics.actualBoundingBoxRight !== testMetrics.actualBoundingBoxRight) isAvailable = true;

			if (isAvailable) {
				found.push(name);
			}
		}
		zh_font_select.innerHTML = zh_bold_select.innerHTML = ''; // Clear existing options
		for (const font of found) {
			const option = document.createElement('option');
			option.value = option.innerText = font;
			zh_font_select.appendChild(option);
			zh_bold_select.appendChild(option.cloneNode(true));
		}
		zh_font_select.value = (zh_font_cookie && found.includes(zh_font_cookie)) ? zh_font_cookie : "Default";
		zh_bold_select.value = (zh_bold_cookie && found.includes(zh_bold_cookie)) ? zh_bold_cookie : "Default";
		zh_bold_select.style.display = '';
	} catch (e) {
		showError('Error checking local fonts:', e);
	}
}, { once: true });