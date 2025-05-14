<?php

$_SESSION['page_id'] = '67e76d769b34c';

$html = new DocPHT\Lib\DocPHT(['圆锥曲线']);
$values = [
$html->title('圆锥曲线','圆锥曲线'), 
$html->markdown(<<<'a'
\begin{proposition}
设$PT$是过圆锥曲线$\rho(\theta)=\frac{ep}{1-e\cos\theta}$焦点$F$的弦，若$PT$的倾斜角为$\alpha$，则有$\abs{PT}=\frac{2ep}{\abs{1-e^2\cos^2\alpha}}$。
\end{proposition}
\begin{proof}
由极径的几何意义知
$$
\abs{PT}
=\abs{\rho(\alpha)+\rho(\pi+\alpha)}
=\abs{\frac{ep}{1-e\cos\alpha}+\frac{ep}{1-e\cos(\pi+\alpha)}}
=\frac{2ep}{\abs{1-e^2\cos^2\alpha}}
$$
\end{proof}
a),
$html->markdown(<<<'d'
\begin{proposition}[焦点三角形的面积]
设$F_1(-c,0), F_2(c,0)$为圆锥曲线的两个焦点，$P$为曲线上一点且与顶点不重合，$\angle F_1PF_2 = \theta$，则
\begin{enumerate}
\item 在椭圆$\frac{x^2}{a^2}+\frac{y^2}{b^2} = 1 (a > b > 0)$中有$S_{\triangle PF_1F_2} = b^2 \tan \frac{\theta}{2}$。
\item 在双曲线$\frac{x^2}{a^2}-\frac{y^2}{b^2} = 1 (a, b > 0)$中有$S_{\triangle PF_1F_2} = b^2 \cot \frac{\theta}{2}$。
\end{enumerate}
\end{proposition}
\begin{proof}
\begin{enumerate}
\item 由椭圆的定义有$PF_1+PF_2 = 2a$，所以$PF_1^2+PF_2^2+2PF_1 \cdot PF_2 = 4a^2$，由余弦定理有$(2c)^2 = F_1F_2^2 = PF_1^2+PF_2^2-2PF_1 \cdot PF_2 \cdot \cos\theta$，联立可解出$PF_1 \cdot PF_2 = \frac{2(a^2-c^2)}{1+\cos\theta}$，所以
\\[S_{\triangle PF_1F_2} = \frac{1}{2}PF_1 \cdot PF_2 \cdot \sin\theta = \frac{(a^2-c^2)\sin\theta}{1+\cos\theta} = b^2\tan\frac{\theta}{2}\\]
\item 由双曲线的定义有$\abs{PF_1-PF_2} = 2a$，所以$PF_1^2+PF_2^2-2PF_1 \cdot PF_2 = 4a^2$，由余弦定理有$(2c)^2 = F_1F_2^2 = PF_1^2+PF_2^2-2PF_1 \cdot PF_2 \cdot \cos\theta$，联立可解出$PF_1 \cdot PF_2 = \frac{2(a^2-c^2)}{-1+\cos\theta}$，所以
\\[S_{\triangle PF_1F_2} = \frac{1}{2}PF_1 \cdot PF_2 \cdot \sin\theta = \frac{(a^2-c^2)\sin\theta}{-1+\cos\theta} = b^2\cot\frac{\theta}{2}\\]
\end{enumerate}
\end{proof}

d),
$html->markdown(<<<'a'
\begin{proposition}
设完全四边形$A_1A_2A_3A_4$的两组对边的斜率之积相等，则三组对边的斜率之积都相等。
\end{proposition}
\begin{proof}
设点$A_i$的坐标为$(x_i,y_i)$，直线$A_iA_j$的斜率为$k_{ij}$，不妨设$k_{12} \cdot k_{34} = k_{13} \cdot k_{24}$，即$\frac{y_2-y_1}{x_2-x_1} \cdot \frac{y_4-y_3}{x_4-x_3} = \frac{y_3-y_1}{x_3-x_1} \cdot \frac{y_4-y_2}{x_4-x_2}$，展开即有
$$\frac{y_1y_3+y_2y_4-y_1y_4-y_2y_3}{x_1x_3+x_2x_4-x_1x_4-x_2x_3} = \frac{y_1y_2+y_3y_4-y_1y_4-y_2y_3}{x_1x_2+x_3x_4-x_1x_4-x_2x_3}$$

由等比性质将分子分母分别相减即有
$$\frac{y_1y_3+y_2y_4-y_1y_4-y_2y_3}{x_1x_3+x_2x_4-x_1x_4-x_2x_3} = \frac{y_1y_2+y_3y_4-y_1y_4-y_2y_3}{x_1x_2+x_3x_4-x_1x_4-x_2x_3} = \frac{y_1y_3+y_2y_4-y_1y_2-y_3y_4}{x_1x_3+x_2x_4-x_1x_2-x_3x_4} = \frac{y_1-y_4}{x_1-x_4} \cdot \frac{y_3-y_2}{x_3-x_2}$$

即$\frac{y_2-y_1}{x_2-x_1} \cdot \frac{y_4-y_3}{x_4-x_3} = \frac{y_3-y_1}{x_3-x_1} \cdot \frac{y_4-y_2}{x_4-x_2} = \frac{y_1-y_4}{x_1-x_4} \cdot \frac{y_3-y_2}{x_3-x_2}$，也即$k_{12} \cdot k_{34} = k_{13} \cdot k_{24} = k_{13} \cdot k_{24}$。
\end{proof}
a),
$html->addButton(),
];
$GLOBALS["page_author"] = 'hbghlyj 2025-05-14 11:04';