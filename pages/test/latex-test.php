<?php

$_SESSION['page_id'] = '67e228acbb820';

$html = new DocPHT\Lib\DocPHT(['LaTeX 测试']);
$values = [
$html->title('LaTeX 测试','LaTeX 测试'), 
$html->markdown(<<<'j'
将 `$...$` 或 `\\(...\\)` 中的 LaTeX 转换为 随文方程 $ax^2+bx+c=0$

将 `$$...$$` 或 `\\[...\\]` 中的 LaTeX 转换为 展示方程
$$
x_{1,2} = {-b\pm\sqrt{b^2 - 4ac} \over 2a}.
$$
用 `\begin{align}...\end{align}` 或 `$$\begin{align}...\end{align}$$` 对齐多行公式
\begin{align}
 y &= x^4 + 4 =\\\\
   &= (x^2+2)^2 - 4x^2 \le\\\\
   &\le (x^2+2)^2
\end{align}
可以用 `\tag{}` 和 `\tag*{}` 指定方程编号（但没有自动编号和引用支持）：
\begin{align}
|\vec{A}|=\sqrt{A_x^2 + A_y^2 + A_z^2}.\tag{1}
\end{align}
公式编号中可以使用任何文本
\\[
|\vec{B}|=\sqrt{B_x^2 + B_y^2 + B_z^2}.\tag*{[2⭐]}
\\]

写入中文：
\begin{CJK}{UTF8}{gbsn}
\text{你好, 这是中文。}
\end{CJK}
可以使用矩阵：

$$T^{\mu\nu}=\begin{pmatrix}
\varepsilon&0&0&0\\
0&\varepsilon/3&0&0\\
0&0&\varepsilon/3&0\\
0&0&0&\varepsilon/3
\end{pmatrix},$$

积分：

$$P_\omega={n_\omega\over 2}\hbar\omega\,{1+R\over 1-v^2}\int\limits_{-1}^{1}dx\,(x-v)|x-v|,$$

酷炫的 tikz 图片：

$$
\usetikzlibrary{decorations.pathmorphing}
\begin{tikzpicture}[line width=0.2mm,scale=1.0545]\small
\tikzset{>=stealth}
\tikzset{snake it/.style={->,semithick,
decoration={snake,amplitude=.3mm,segment length=2.5mm,post length=0.9mm},decorate}}
\def\h{3}
\def\d{0.2}
\def\ww{1.4}
\def\w{1+\ww}
\def\p{1.5}
\def\r{0.7}
\coordinate[label=below:$A_1$] (A1) at (\ww,\p);
\coordinate[label=above:$B_1$] (B1) at (\ww,\p+\h);
\coordinate[label=below:$A_2$] (A2) at (\w,\p);
\coordinate[label=above:$B_2$] (B2) at (\w,\p+\h);
\coordinate[label=left:$C$] (C1) at (0,0);
\coordinate[label=left:$D$] (D) at (0,\h);
\draw[fill=blue!14](A2)--(B2)-- ++(\d,0)-- ++(0,-\h)--cycle;
\draw[gray,thin](C1)-- +(\w+\d,0);
\draw[dashed,gray,fill=blue!5](A1)-- (B1)-- ++(\d,0)-- ++(0,-\h)-- cycle;
\draw[dashed,line width=0.14mm](A1)--(C1)--(D)--(B1);
\draw[snake it](C1)--(A2) node[pos=0.6,below] {$c\Delta t$};
\draw[->,semithick](\ww,\p+0.44*\h)-- +(\w-\ww,0) node[pos=0.6,above] {$v\Delta t$};
\draw[snake it](D)--(B2);
\draw[thin](\r,0) arc (0:atan2(\p,\w):\r) node[midway,right,yshift=0.06cm] {$\theta$};
\draw[opacity=0](-0.40,-0.14)-- ++(0,5.06);
\end{tikzpicture}$$

图象：

$$\begin{tikzpicture}[scale=1.0544]\small
\begin{axis}[axis line style=gray,
	samples=120,
	width=9.0cm,height=6.4cm,
	xmin=-1.5, xmax=1.5,
	ymin=0, ymax=1.8,
	restrict y to domain=-0.2:2,
	ytick={1},
	xtick={-1,1},
	axis equal,
	axis x line=center,
	axis y line=center,
	xlabel=$x$,ylabel=$y$]
\addplot[red,domain=-2:1,semithick]{exp(x)};
\addplot[black]{x+1};
\addplot[] coordinates {(1,1.5)} node{$y=x+1$};
\addplot[red] coordinates {(-1,0.6)} node{$y=e^x$};
\path (axis cs:0,0) node [anchor=north west,yshift=-0.07cm] {0};
\end{axis}
\end{tikzpicture}$$

以及 [LaTeX 的其他功能](https://en.wikibooks.org/wiki/LaTeX/Mathematics)。

采用服务器端渲染 LaTeX 的好处是图片会被浏览器缓存，所以第一次可能加载比较慢，但之后就会很快。

缺点是 \newcommand 只能在一个公式内部使用，后续需要改进如何定义全局命令🤔
j),
$html->addButton(),
];
$GLOBALS["page_author"] = 'hbghlyj 2025-07-04 09:16';