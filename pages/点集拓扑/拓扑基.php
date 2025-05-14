<?php

$_SESSION['page_id'] = '67e4b892010d1';

$html = new DocPHT\Lib\DocPHT(['拓扑基']);
$values = [
$html->title('拓扑基','拓扑基'), 
$html->markdown(<<<'a'
\begin{definition}
设 $(X,\mathscr{T})$ 是[[拓扑空间]]，$\mathscr{B} \subset \mathscr{T}$，如果 $\mathscr{T}$ 中的任意元素都可以表成 $\mathscr{B}$ 中元素的并，即
$$
\forall U \in \mathscr{T}, \exists \mathscr{B}_1 \subset \mathscr{B}, \text{使得 } U = \bigcup_{B \in \mathscr{B}_1} B
$$
则称 $\mathscr{B}$ 是一个**拓扑基**。
\end{definition}
\begin{example}
设 $(X,\rho)$ 是[[度量空间]]，则所有的[[球形邻域]]是其一组拓扑基：任意开集 $U$ 都可以表示成球形邻域 $B(x,\epsilon_x),x\in U$ 的并。
\end{example}
\begin{example}
离散空间中的所有单点集是其一组拓扑基。
\end{example}
\begin{theorem}
设 $(X,\mathscr{T})$ 是拓扑空间，$\mathscr{B} \subset \mathscr{T}$。则 $\mathscr{B}$ 是一组拓扑基的充分必要条件是：对任意的 $x \in X, U \in \mathscr{U}_x$，存在 $B \in \mathscr{B}$ 使得 $x \in B \subset U$。
\end{theorem}
\begin{proof}
必要性. $\forall x \in X, U \in \mathscr{U}_x$，存在 $V_x \in \mathscr{T}$ 使得 $x \in V_x \subset U$，由 $\mathscr{B}$ 是拓扑基可知 $\exists \mathscr{B}_1 \subset \mathscr{B}$ 使得 $V_x = \bigcup_{B \in \mathscr{B}_1} B$，于是存在某个 $W_x \in \mathscr{B}_1$ 使得
$$
x \in W_x \subset \bigcup_{B \in \mathscr{B}_1} B = V_x \subset U.
$$
充分性. 任取 $U \in \mathscr{T}$，则 $\forall x \in U, U \in \mathscr{U}_x$，于是 $\exists B_x \in \mathscr{B}$ 使得 $x \in B_x \subset U$。于是
$$
U = \bigcup_{x \in U} \{x\} \subset \bigcup_{x \in U} B_x \subset U.
$$
这就是
$$
U = \bigcup_{x \in U} B_x.
$$
以上即言 $X$ 中任意的开集都可表示成 $\mathscr{B}$ 中元素的并，从而 $\mathscr{B}$ 是一组拓扑基。
\end{proof}
\begin{remark}
拓扑基的定义是一种全局的概念，而定理1等价地给出了一种局部的定义方式。
\end{remark}

\begin{theorem}
设 $(X,\mathscr{T})$ 是拓扑空间，$\mathscr{B}$ 是其拓扑基。则
\begin{enumerate}
    \item $X = \bigcup_{B \in \mathscr{B}} B$；
    \item $\forall B_1, B_2 \in \mathscr{B}, x \in B_1 \cap B_2, \exists B \in \mathscr{B}, \text{使得 } x \in B \subset B_1 \cap B_2$。
\end{enumerate}
\end{theorem}

\begin{proof}
(1) 显然。

(2) 由开集的交是开集，知 $B_1 \cap B_2 \in \mathscr{U}_x$。由定理1即得结论。
\end{proof}

\begin{theorem}
设 $X$ 是集合，$\mathscr{B} \subset \mathcal{P}(X)$，如果 $\mathscr{B}$ 满足
\begin{enumerate}
    \item $X = \bigcup_{B \in \mathscr{B}} B$；
    \item $\forall B_1, B_2 \in \mathscr{B}, x \in B_1 \cap B_2, \exists B \in \mathscr{B}, \text{使得 } x \in B \subset B_1 \cap B_2$，
\end{enumerate}
则存在 $X$ 上唯一的拓扑，使得 $\mathscr{B}$ 恰好是 $X$ 的拓扑基。
\end{theorem}

\begin{proof}
令
$$
\mathscr{T} = \{ U \subset X \mid \mathscr{B}_U \subset \mathscr{B}, U = \bigcup_{B \in \mathscr{B}_U} B \}.
$$
先证它是一个拓扑。

首先显然 $\emptyset, X \in \mathscr{T}$（空集自然，$X \in \mathscr{T}$ 是由(1)）。

任取 $U_1, U_2 \in \mathscr{T}$，则 $U_i = \bigcup_{B_i \in \mathscr{B}_i} B_i, i = 1, 2$，于是
$$
U_1 \cap U_2 = \left(\bigcup_{B_1 \in \mathscr{B}_1} B_1\right) \cap \left(\bigcup_{B_2 \in \mathscr{B}_2} B_2\right) = \bigcup_{B_1 \in \mathscr{B}_1, B_2 \in \mathscr{B}_2} (B_1 \cap B_2).
$$
而由(2)可知固定 $B_1, B_2$，$\forall x \in B_1 \cap B_2$，存在 $B_x \in \mathscr{B}$ 使得 $x \in B_x \subset B_1 \cap B_2$，由定理1中类似的讨论可知
$$
B_1 \cap B_2 = \bigcup_{x \in B_1 \cap B_2} B_x，
$$
于是
$$
U_1 \cap U_2 = \bigcup_{B_1 \in \mathscr{B}_1, B_2 \in \mathscr{B}_2} \bigcup_{x \in B_1 \cap B_2} B_x \in \mathscr{T}。
$$

任取 $\mathscr{T}_1 = \{ U_i \mid i \in I \} \subset \mathscr{T}$，则对每个 $U_i$，$\exists \mathscr{B}_i \subset \mathscr{B}$ 使得 $U_i = \bigcup_{B_i \in \mathscr{B}_i} B_i$，从而
$$
\bigcup_{i \in I} U_i = \bigcup_{i \in I} \bigcup_{B_i \in \mathscr{B}_i} B_i \in \mathscr{T}.
$$
因此 $\mathscr{T}$ 确实是一个拓扑。由拓扑基的定义，立即得到 $\mathscr{B}$ 是其一组拓扑基。

最后证唯一性。假设还有另一个拓扑 $\mathscr{T}^*$ 以 $\mathscr{B}$ 为拓扑基，则
$$
U \in \mathscr{T} \Leftrightarrow U = \bigcup_{B \in \mathscr{B}} B \Leftrightarrow U \in \mathscr{T}^*。
$$
上式即言 $\mathscr{T} = \mathscr{T}^*$。
\end{proof}
a),
$html->addButton(),
];
$GLOBALS["page_author"] = 'hbghlyj 2025-03-29 00:56';