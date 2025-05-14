<?php

$_SESSION['page_id'] = '6824136beb689';

$html = new DocPHT\Lib\DocPHT(['命题']);
$values = [
$html->title('命题','命题'), 
$html->markdown(<<<'c'
\begin{proposition}
设椭圆$\Gamma: \frac{x^2}{a^2}+\frac{y^2}{b^2}=1$上有两点$A,B$，点$C$是$AB$的中点，则$k_{OC}\cdot k_{AB}=-\frac{b^2}{a^2}$。
\end{proposition}
\begin{proof}
考察仿射变换$\varphi: (x,y)\mapsto(\frac{x}{a},\frac{y}{a})$，在$\varphi$的作用下，椭圆$\Gamma: \frac{x^2}{a^2}+\frac{y^2}{b^2}=1$变为单位圆$\Gamma': x'^2+y'^2=1$。$\varphi$将直线$Ax+By+C=0$变为$Aax'+Bby'+C=0$，因此斜率由$k=-\frac{A}{B}$变为$k'=-\frac{Aa}{Bb}$，因此$k=\frac{b}{a}k'$。

注意仿射变换保持单比，因此$AB$的中点$C$变为$A'B'$的中点$C'$。在$\Gamma'$中显然有$OC'\perp A'B'$，于是$k_{OC'}\cdot k_{A'B'}=-1$，注意$k=\frac{b}{a}k'$，因此$k_{OC}=\frac{b}{a}k_{OC'}, k_{AB}=\frac{b}{a}k_{A'B'}$，于是$k_{OC}\cdot k_{AB}=-\frac{b^2}{a^2}$。
\end{proof}
c),
$html->addButton(),
];
$GLOBALS["page_author"] = 'abababa 2025-05-14 04:52';