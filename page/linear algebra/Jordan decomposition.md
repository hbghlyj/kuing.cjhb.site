# generalized eigenspaces
Let $F$ be a field where the characteristic polynomial of a linear operator $T: V \rightarrow V$ on a finite-dimensional vector space $V$ over $F$ splits into linear factors, say $p_T(x) = (-1)^n (x - \lambda_1)^{\alpha_1} \cdots (x - \lambda_m)^{\alpha_m}$, where $\lambda_1, \dots, \lambda_m \in F$ are distinct eigenvalues and $\alpha_i$ are their algebraic multiplicities. By [[diagonalizable#Primary-Decomposition-Theorem]], $$V = \ker (T - \lambda_1 I)^{\alpha_1} \oplus \cdots \oplus \ker (T - \lambda_m I)^{\alpha_m}$$where each $\ker (T - \lambda_i I)^{\alpha_i}$ is the generalized eigenspace of $T$ corresponding to $\lambda_i$, and is $T$-invariant.
# Jordan decomposition
\begin{theorem}
Let $F$ be a field where the characteristic polynomial of a linear operator $T: V \rightarrow V$ on a finite-dimensional vector space $V$ over $F$ splits into linear factors. There exist unique linear operators $S, N: V \rightarrow V$ such that:
1. $T = S + N$,
2. $S N = N S$,
3. $S$ is diagonalizable,
4. $N$ is nilpotent.

Moreover, $S$ and $N$ are polynomials in $T$.\end{theorem}
\begin{proof}
By the previous theorem, $V = \bigoplus_{i=1}^m \ker (T - \lambda_i I)^{\alpha_i}$, where $\lambda_i$ are the distinct eigenvalues of $T$. Define $S: V \rightarrow V$ such that for $v = v_1 + \cdots + v_m$ with $v_i \in \ker (T - \lambda_i I)^{\alpha_i}$, we have $S v = \sum_{i=1}^m \lambda_i v_i$. Define $N = T - S$. We verify the properties:

1. By definition, $T = S + N$.
2. For $v_i \in \ker (T - \lambda_i I)^{\alpha_i}$, compute $N v_i = T v_i - S v_i = T v_i - \lambda_i v_i$. Since $v_i \in \ker (T - \lambda_i I)^{\alpha_i}$, we have $(T - \lambda_i I)^{\alpha_i} v_i = 0$, so $T v_i = \lambda_i v_i + w_i$ where $w_i = (T - \lambda_i I) v_i$ satisfies $(T - \lambda_i I)^{\alpha_i - 1} w_i = 0$. Thus, $N v_i = w_i \in \ker (T - \lambda_i I)^{\alpha_i - 1} \subseteq \ker (T - \lambda_i I)^{\alpha_i}$. Hence, each generalized eigenspace is $N$-invariant. Now, $S N v_i = S w_i = \lambda_i w_i$ (since $w_i \in \ker (T - \lambda_i I)^{\alpha_i - 1}$), and $N S v_i = N (\lambda_i v_i) = \lambda_i N v_i = \lambda_i w_i$. Thus, $S N = N S$.
3. In each $\ker (T - \lambda_i I)^{\alpha_i}$, $S$ acts as multiplication by $\lambda_i$. Thus, $\ker (T - \lambda_i I)^{\alpha_i}$ is contained in the eigenspace of $S$ with eigenvalue $\lambda_i$. Since $V$ is the direct sum of these spaces, $S$ is diagonalizable.
4. Since $N v_i = (T - \lambda_i I) v_i$ for $v_i \in \ker (T - \lambda_i I)^{\alpha_i}$, we have $(T - \lambda_i I)^{\alpha_i} v_i = 0$, so $N^{\alpha_i} v_i = (T - \lambda_i I)^{\alpha_i} v_i = 0$. Let $k = \max {\alpha_i}$. Then for any $v = \sum v_i$, $N^k v = \sum N^k v_i = 0$, so $N^k = 0$.

**To arrive at the uniqueness:** Assume another decomposition $T = S + N$ with the properties. The key is to show $S$ must act as scalar multiplication by $\lambda_j$ on each generalized eigenspace of $T$. Rewrite $S - \lambda_j I = (T - \lambda_j I) - N$. Raise to a high power (here $2n$, sufficient to exceed the nilpotency index and algebraic multiplicities). Expand via binomial theorem, leveraging commutativity. Terms vanish due to nilpotency of $N$ and the definition of the generalized eigenspace. The result $(S - \lambda_j I)^{2n} x = 0$ for such $x$ implies, by diagonalizability of $S$, that $x$ is an eigenvector of $S$ with eigenvalue $\lambda_j$. The direct sum decomposition then forces uniqueness of $S$.

**Uniqueness:** Suppose $T = S + N$ where $SN = NS$, $S$ is diagonalizable, and $N$ is nilpotent with $N^n = 0$ (where $n = \dim V$). We show that $Sx = \lambda_j x$ for all vectors $x \in \ker(T - \lambda_j I)^{\alpha_j}$ and all $j = 1, \dots, m$.

Fix $j$. Note that $S - \lambda_j I = (T - \lambda_j I) - N$. Since $SN = NS$, it follows that $TN = NT$, so $N$ commutes with $T - \lambda_j I$. By the binomial theorem (applicable since they commute),
$$
(S - \lambda_j I)^{2n} = \sum_{l=0}^{2n} \binom{2n}{l} (T - \lambda_j I)^{2n-l} (-N)^l.
$$
Using $N^n = 0$, the terms with $l \geq n$ vanish, so
$$
(S - \lambda_j I)^{2n} = \sum_{l=0}^{n-1} \binom{2n}{l} (T - \lambda_j I)^{2n-l} (-N)^l.
$$
Suppose $x \in \ker(T - \lambda_j I)^{\alpha_j}$. Since $\alpha_j \leq n$, for each $l = 0, \dots, n-1$, the exponent $2n - l \geq n + 1 > \alpha_j$, so $(T - \lambda_j I)^{2n-l} x = 0$. This implies $(S - \lambda_j I)^{2n} x = 0$. Since $S$ is diagonalizable, its generalized eigenspaces coincide with its eigenspaces. Thus, if $(S - \lambda_j I)^{2n} x = 0$, then $x$ is in the eigenspace of $S$ for eigenvalue $\lambda_j$, so $(S - \lambda_j I) x = 0$, i.e., $Sx = \lambda_j x$.

Since this holds for all $j$ and
$$
V = \ker(T - \lambda_1 I)^{\alpha_1} \oplus \cdots \oplus \ker(T - \lambda_m I)^{\alpha_m},
$$
there is exactly one linear operator $S$ such that $Sx = \lambda_j x$ for $x \in \ker(T - \lambda_j I)^{\alpha_j}$ and $j = 1, \dots, m$. Then $N = T - S$ is also unique.

**Polynomials in $T$:** To construct $S$ globally as a polynomial in $T$, by the Chinese Remainder Theorem $\exists$ a polynomial $p(x)$ such that:$$\forall i, p(x) \equiv \lambda_i \pmod{(x - \lambda_i)^{\alpha_i}}$$To verify $S = p(T)$: On the generalized eigenspace for $\lambda_i$, $p(T) - \lambda_i I$ is a multiple of $(T - \lambda_i I)^{\alpha_i}$, so $(p(T) - \lambda_i I)v = 0$ for any $v$ in that space. Now $N = T - S = T - p(T)$ is a polynomial in $T$. Commutativity $SN = NS$ holds automatically since both are polynomials in $T$.
\end{proof}
Mathlib [Module.End.exists_isNilpotent_isSemisimple](https://leanprover-community.github.io/mathlib4_docs/find/?pattern=Module.End.exists_isNilpotent_isSemisimple#doc)