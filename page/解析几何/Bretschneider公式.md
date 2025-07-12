# Bretschneider公式
边长分别为 $a,b,c,d$（按此顺序）和对角线长度为 $p, q$ 的四边形的面积 $[ABCD]=\frac{1}{4} \cdot \sqrt{4p^2q^2-(b^2+d^2-a^2-c^2)^2}$。
\begin{proof}
假设一个四边形的边为 $\vec{a}, \vec{b}, \vec{c}, \vec{d}$，满足 $\vec{a} + \vec{b} + \vec{c} + \vec{d} = \vec{0}$，并且该四边形的对角线为 $\vec{p} = \vec{b} + \vec{c} = -\vec{a} - \vec{d}$ 和 $\vec{q} = \vec{a} + \vec{b} = -\vec{c} - \vec{d}$。任何这样的四边形的面积为 $K=\frac{1}{2} |\vec{p} \times \vec{q}|$。

Lagrange's Identity states that $|\vec{a}|^2|\vec{b}|^2-(\vec{a}\cdot\vec{b})^2=|\vec{a}\times\vec{b}|^2 \implies \sqrt{|\vec{a}|^2|\vec{b}|^2-(\vec{a}\cdot\vec{b})^2}=|\vec{a}\times\vec{b}|$. Therefore:

$K = \frac{1}{2} \sqrt{|\vec{p}|^2|\vec{q}|^2 - (\vec{p} \cdot \vec{q})^2}\\
= \frac{1}{4} \sqrt{4 |\vec{p}|^2|\vec{q}|^2 - (2 \vec{p} \cdot \vec{q})^2}\\
= \frac{1}{4} \sqrt{4 |\vec{p}|^2|\vec{q}|^2 - [2 (\vec{b} + \vec{c}) \cdot (\vec{a} + \vec{b})]^2}\\
= \frac{1}{4} \sqrt{4 |\vec{p}|^2|\vec{q}|^2 - [2 \vec{b} \cdot (\vec{a} + \vec{b}) + 2 \vec{c} \cdot (\vec{a} + \vec{b})]^2}\\
= \frac{1}{4} \sqrt{4 |\vec{p}|^2|\vec{q}|^2 - [-2 \vec{b} \cdot (\vec{c} + \vec{d}) + 2 \vec{c} \cdot (\vec{a} + \vec{b})]^2}\\
= \frac{1}{4} \sqrt{4 |\vec{p}|^2|\vec{q}|^2 - [-2 \vec{b} \cdot \vec{c} - 2 \vec{b} \cdot \vec{d} + 2 \vec{a} \cdot \vec{c} + 2 \vec{b} \cdot \vec{c}]^2}\\
= \frac{1}{4} \sqrt{4 |\vec{p}|^2|\vec{q}|^2 - [2 \vec{a} \cdot \vec{c} - 2 \vec{b} \cdot \vec{d}]^2}\\
= \frac{1}{4} \sqrt{4 |\vec{p}|^2|\vec{q}|^2 - ([(\vec{a} + \vec{c})\cdot(\vec{a} + \vec{c}) - \vec{a}\cdot\vec{a} - \vec{c}\cdot\vec{c}] - [(\vec{b} + \vec{d})\cdot(\vec{b} + \vec{d}) - \vec{b}\cdot\vec{b} - \vec{d}\cdot\vec{d}])^2}\\
= \frac{1}{4} \sqrt{4 |\vec{p}|^2|\vec{q}|^2 - [|\vec{b}|^2 + |\vec{d}|^2 - |\vec{a}|^2 - |\vec{c}|^2 + (\vec{a} + \vec{c})\cdot(\vec{a} + \vec{c}) - (\vec{b} + \vec{d})\cdot(\vec{b} + \vec{d})]^2}\\
= \frac{1}{4} \sqrt{4 |\vec{p}|^2|\vec{q}|^2 - [|\vec{b}|^2 + |\vec{d}|^2 - |\vec{a}|^2 - |\vec{c}|^2 + |\vec{a} + \vec{c}|^2 - |\vec{b} + \vec{d}|^2]^2}\\
= \frac{1}{4} \sqrt{4 |\vec{p}|^2|\vec{q}|^2 - [|\vec{b}|^2 + |\vec{d}|^2 - |\vec{a}|^2 - |\vec{c}|^2 + |\vec{a} + \vec{c}|^2 - |-(\vec{a} + \vec{c})|^2]^2}\\
= \frac{1}{4} \sqrt{4 |\vec{p}|^2|\vec{q}|^2 - [|\vec{b}|^2 + |\vec{d}|^2 - |\vec{a}|^2 - |\vec{c}|^2]^2}$

Then if $a, b, c, d$ represent $|\vec{a}|, |\vec{b}|, |\vec{c}|, |\vec{d}|$ (and are thus the side lengths) while $p, q$ represent $|\vec{p}|, |\vec{q}|$ (and are thus the diagonal lengths), the area of a quadrilateral is:$K = \frac{1}{4} \sqrt{4p^2q^2 - (b^2 + d^2 - a^2 - c^2)^2}$
\end{proof}