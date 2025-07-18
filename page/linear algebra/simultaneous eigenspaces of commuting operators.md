# simultaneous eigenspaces of commuting operators
Mathlib [directSum_isInternal_of_commute](https://leanprover-community.github.io/mathlib4_docs/find/?pattern=LinearMap.IsSymmetric.directSum_isInternal_of_commute#doc)
\begin{theorem}
Given a commuting pair of symmetric linear operators on a finite dimensional inner product space, the space decomposes as an internal direct sum of simultaneous eigenspaces of these operators.
\end{theorem}
To arrive at this conclusion, recall the following key results from linear algebra (assuming the inner product space is over $\mathbb{R}$ or $\mathbb{C}$, as is standard; symmetric operators are self-adjoint with respect to the inner product):

1. **Spectral theorem for a single self-adjoint operator**: A symmetric (self-adjoint) linear operator $A$ on a finite-dimensional inner product space $V$ is diagonalizable. That is, $V$ decomposes as an orthogonal direct sum of eigenspaces of $A$, and there exists an orthonormal basis of $V$ consisting of eigenvectors of $A$.

2. **Commuting self-adjoint operators**: If two symmetric operators $A$ and $B$ on $V$ commute (i.e., $AB = BA$), then they are simultaneously diagonalizable. This means there exists an orthonormal basis of $V$ consisting of simultaneous eigenvectors (each basis vector is an eigenvector of both $A$ and $B$).

3. **Decomposition into simultaneous eigenspaces**: For each pair of eigenvalues $(\lambda, \mu)$ where $\lambda$ is an eigenvalue of $A$ and $\mu$ is an eigenvalue of $B$, define the simultaneous eigenspace $E_{\lambda,\mu} = \{ v \in V \mid Av = \lambda v, Bv = \mu v \}$. The simultaneous diagonalizability implies that $V = \bigoplus_{(\lambda,\mu)} E_{\lambda,\mu}$, where the sum is orthogonal (hence internal and direct) and taken over all distinct pairs $(\lambda, \mu)$ for which $E_{\lambda,\mu} \neq \{0\}$. Vectors in different simultaneous eigenspaces are orthogonal due to the self-adjointness.
\begin{proof}
The commuting condition ensures that the eigenspaces of one operator are invariant under the other, allowing refinement of the decomposition via induction or the existence of a common eigenbasis. For a proof, one can start by diagonalizing $A$, then restrict $B$ to each eigenspace of $A$ (where $B$ remains self-adjoint and thus diagonalizable), yielding the simultaneous decomposition.
\end{proof}
One might suspect that without the assumption of diagonalizability, we might be able to simultaneously Jordanize $T$ and $U$ (that is, put them into simultaneous Jordan form). Find a counterexample showing that this is false.

Let $T$ and $U$ be linear operators on $\mathbb{R}^3$ (or $\mathbb{C}^3$) with respect to the standard basis $e_1, e_2, e_3$, defined by the matrices
$$
[T] = \begin{pmatrix} 0 & 1 & 0 \\ 0 & 0 & 1 \\ 0 & 0 & 0 \end{pmatrix}, \quad [U] = [T]^2 = \begin{pmatrix} 0 & 0 & 1 \\ 0 & 0 & 0 \\ 0 & 0 & 0 \end{pmatrix}.
$$
These operators commute since $[T, U] = [T, T^2] = 0$.

The Jordan canonical form of $T$ is $[T]$ itself (a single Jordan block of size 3 for eigenvalue 0), so $T$ is not diagonalizable.

The Jordan canonical form of $U$ is similar to
\begin{pmatrix} 0 & 1 & 0 \\ 0 & 0 & 0 \\ 0 & 0 & 0 \end{pmatrix}
(up to reordering of the blocks; Jordan blocks of sizes 2 and 1 for eigenvalue 0), so $U$ is not diagonalizable.

To see that there is no basis in which both are in Jordan canonical form simultaneously, note that any Jordan basis for $T$ consists of a single chain $v_3, v_2 = T v_3, v_1 = T v_2$ with $T v_1 = 0$. In this basis (up to scaling and choice of upper/lower triangular form), the action of $U = T^2$ satisfies $U v_3 = v_1$, $U v_2 = 0$, $U v_1 = 0$.

For both to be in Jordan form, the basis must be ordered such that the matrix of $T$ has 1's only on the superdiagonal (or subdiagonal) in consecutive positions, requiring $v_3, v_2, v_1$ (or reverse) to be in sequential order. However, $U$ connects $v_3$ to $v_1$ directly, skipping $v_2$, so in such an ordering, the matrix of $U$ has a 1 off the superdiagonal/subdiagonal (specifically, separated by one position), which is not a Jordan form. No reordering preserves the consecutive chain for $T$ while making the chain for $U$ consecutive.