# Primary Decomposition Theorem
$F$ is a field, $V$ is a finite-dimensional vector space over $F$, and $T: V \rightarrow V$ is a linear map.
\begin{lemma}
Suppose that $f(T)=0$, where $f \in F[x]$. Suppose also that $f(x)=g(x) h(x)$, where $g, h \in F[x]$ and $g, h$ are coprime. Then there are $T$-invariant subspaces $U, W$ of $V$ such that $V=U \oplus W$ and $g\left(\left.T\right|_U\right)=0, h\left(\left.T\right|_W\right)=0$.
\end{lemma}
\begin{proof}
Since $g$ and $h$ are coprime, by Bézout's identity there exist polynomials $r, s \in F[x]$ such that $r(x) g(x) + s(x) h(x) = 1$. Substituting $T$ gives
$$
r(T) g(T) + s(T) h(T) = I,
$$
where $I$ is the identity transformation on $V$.

Define $U = \ker g(T)$ and $W = \ker h(T)$. For any $v \in V$,
$$
v = I v = r(T) g(T) v + s(T) h(T) v.
$$
Let $u = s(T) h(T) v$ and $w = r(T) g(T) v$. Then $g(T) u = g(T) s(T) h(T) v = s(T) g(T) h(T) v = s(T) f(T) v = 0$, so $u \in U$. Similarly, $h(T) w = h(T) r(T) g(T) v = r(T) h(T) g(T) v = r(T) f(T) v = 0$, so $w \in W$. Thus, $V = U + W$.

To show the sum is direct, suppose $v \in U \cap W$. Then $g(T) v = 0$ and $h(T) v = 0$, so
$$
v = r(T) g(T) v) + s(T) v = 0 + 0 = 0.
$$
Thus, $U \cap W = \{0\}$, and $V = U \oplus W$.

Now, $U$ is $T$-invariant: if $u \in U$, then $g(T) (T u) = T g(T) u = T 0 = 0$ (since polynomials in $T$ commute), so $T u \in U$. Similarly, $W$ is $T$-invariant. By construction, $g(\left.T\right|_U) = 0$ and $h(\left.T\right|_W) = 0$.
\end{proof}
**Challenge.** Let $P$ be the projection of $V$ onto $U$ along $W$. Express $P$ as $p(T)$ for some $p \in F[x]$.

From the proof, the component of $v$ in $U$ is $s(T) h(T) v$. Thus, $P = s(T) h(T) = p(T)$, where $p(x) = s(x) h(x)$.

\begin{lemma}
If $m_T(x)=g(x) h(x)$ where $g, h \in F[x]$ are monic and co-prime, then $g$ is the minimal polynomial of $\left.T\right|_U$ and $h$ is the minimal polynomial of $\left.T\right|_W$.
\end{lemma}
\begin{proof}
By lemma 1 (applied to $f = m_T$), there exist $T$-invariant $U, W \leq V$ with $V = U \oplus W$, $g(\left.T\right|_U) = 0$, and $h(\left.T\right|_W) = 0$. Let $\mu_U$ be the minimal polynomial of $\left.T\right|_U$ and $\mu_W$ the minimal polynomial of $\left.T\right|_W$. Then $\mu_U$ divides $g$ and $\mu_W$ divides $h$.

Since $U, W$ are $T$-invariant and $V = U \oplus W$, a polynomial $q \in F[x]$ satisfies $q(T) = 0$ if and only if $q(\left.T\right|_U) = 0$ and $q(\left.T\right|_W) = 0$. Thus, $m_T$ is the monic least common multiple of $\mu_U$ and $\mu_W$: $m_T = \operatorname{lcm}(\mu_U, \mu_W)$.

Since $g, h$ are coprime and monic, $\gcd(g, h) = 1$, and $\mu_U | g$, $\mu_W | h$ imply $\gcd(\mu_U, \mu_W) = 1$. Therefore, $\operatorname{lcm}(\mu_U, \mu_W) = \mu_U \mu_W$. So
$$
\mu_U \mu_W = g h.
$$
Since all polynomials are monic, $\deg \mu_U + \deg \mu_W = \deg g + \deg h$, $\deg \mu_U \leq \deg g$, and $\deg \mu_W \leq \deg h$. Equality holds if and only if $\deg \mu_U = \deg g$ and $\deg \mu_W = \deg h$, so $\mu_U = g$ and $\mu_W = h$.
\end{proof}
\begin{example}
If $m_T(x)=x^2-x$ then (as we already know) there exist $U, W \leqslant V$ such that $V=U \oplus W,\left.T\right|_U=I_U$ and $\left.T\right|_W=0_W$.
\end{example}
\begin{theorem}
Suppose that
$$
m_T(x)=f_1(x)^{m_1} f_2(x)^{m_2} \ldots f_k(x)^{m_k}
$$
where $f_1, f_2, \ldots, f_k$ are distinct monic irreducible polynomials over $F$. Then
$$
V=V_1 \oplus V_2 \oplus \cdots \oplus V_k
$$
where $V_1, V_2, \ldots, V_k$ are $T$-invariant subspaces and the minimal polynomial of $\left.T\right|_{V_i}$ is $f_i^{m_i}$ for $1 \leqslant i \leqslant k$.
\end{theorem}
\begin{proof}
Proceed by induction on $k$. If $k=1$, then $V_1 = V$ and the minimal polynomial of $\left.T\right|_{V_1}$ is $f_1^{m_1}$.

Assume the result holds for decompositions with fewer than $k$ factors ($k \geq 2$). Let $g(x) = f_1(x)^{m_1}$ and $h(x) = f_2(x)^{m_2} \cdots f_k(x)^{m_k}$. Then $m_T(x) = g(x) h(x)$, and $g, h$ are coprime (since the $f_i$ are distinct irreducibles). By Mark 2, there exist $T$-invariant $U, W \leq V$ with $V = U \oplus W$ and minimal polynomials $\mu_U = g = f_1^{m_1}$, $\mu_W = h = f_2^{m_2} \cdots f_k^{m_k}$.

By the induction hypothesis applied to $W$, there exist $T$-invariant subspaces $V_2, \dots, V_k$ of $W$ such that $W = V_2 \oplus \cdots \oplus V_k$ and the minimal polynomial of $\left.T\right|_{V_i}$ is $f_i^{m_i}$ for $2 \leq i \leq k$. Thus, $V = U \oplus V_2 \oplus \cdots \oplus V_k$. Set $V_1 = U$.
\end{proof}
# Diagonalizable iff minimal polynomial splits and has distinct roots
\begin{theorem}
$T$ is diagonalizable if and only if $m_T(x)$ may be factorized as a product of distinct linear factors in $F[x]$.
\end{theorem}
\begin{proof}
($\Rightarrow$) Suppose $T$ is diagonalizable. Then there exists a basis of $V$ with respect to which the matrix of $T$ is diagonal, say with entries $\lambda_1, \dots, \lambda_n \in F$. The minimal polynomial $m_T$ divides the characteristic polynomial $\prod (x - \lambda_i)$, so $m_T$ is the product of distinct $(x - \mu_j)$ over the distinct eigenvalues $\mu_j$.

($\Leftarrow$) Suppose $m_T(x) = \prod_{i=1}^k (x - \lambda_i)$, where the $\lambda_i \in F$ are distinct (so the linear factors $f_i(x) = x - \lambda_i$ are distinct irreducibles with exponents $m_i =1$). By Primary Decomposition Theorem,
$$
V = V_1 \oplus \cdots \oplus V_k,
$$
where each $V_i$ is $T$-invariant and the minimal polynomial of $\left.T\right|_{V_i}$ is $x - \lambda_i$. Thus, $\left.T\right|_{V_i} = \lambda_i I_{V_i}$, so every vector in $V_i$ is an eigenvector of $T$ with eigenvalue $\lambda_i$. Taking a union of bases for the $V_i$ gives a basis of eigenvectors for $V$, so $T$ is diagonalizable.
\end{proof}