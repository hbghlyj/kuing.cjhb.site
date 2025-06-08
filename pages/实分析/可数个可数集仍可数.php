<?php

$_SESSION['page_id'] = '67fb5902bdcac';

$html = new DocPHT\Lib\DocPHT(['可数个可数集仍可数']);
$values = [
$html->title('可数个可数集仍可数','可数个可数集仍可数'), 
$html->markdown(<<<'k'
\begin{theorem}
可数个可数集仍可数。
\end{theorem}
\begin{proof}
不妨设这些可数集都非空且两两不相交。设
$$
\begin{cases}
S_0=\{a_{00}, a_{01}, a_{02}, \cdots\}\\
S_1=\{a_{10}, a_{11}, a_{12}, \cdots\}\\
\cdots
\end{cases}
$$
若$S_i=\{a_{i0}, a_{i1}, \cdots, a_{ij}\}$为有限集，则令$a_{ij}=a_{i(j+1)}=a_{i(j+2)}=\cdots$，以此将$S_i$扩充为可数无限集。

将所有元素排列为：
$$
\begin{matrix}
a_{00} & \rightarrow & a_{01} & ~ & a_{02} & ~ & a_{03} & \cdots \\
~ & \swarrow & ~ & \swarrow & ~ & \swarrow & ~ & \\
a_{10} & ~ & a_{11} & ~ & a_{12} & ~ & a_{13} & \cdots \\
~ & \swarrow & ~ & \swarrow & ~ & \swarrow & ~ & \\
a_{20} & ~ & a_{21} & ~ & a_{22} & ~ & a_{23} & \cdots \\
~ & \swarrow & ~ & \swarrow & ~ & \swarrow & ~ & \\
a_{30} & ~ & a_{31} & ~ & a_{32} & ~ & a_{33} & \cdots \\
\cdots & ~ & \cdots & ~ & \cdots & ~ & \cdots & \cdots 
\end{matrix}
$$
按对角线法，能将$S=\bigcup_{i=0}^{\infty}S_i$排列成序列$a_{00},a_{01},a_{10},a_{02},a_{11},a_{20},\cdots$，所以$S$是可数集。
\end{proof}
k),
$html->addButton(),
];
$GLOBALS["page_author"] = 'hbghlyj 2025-06-08 14:53';