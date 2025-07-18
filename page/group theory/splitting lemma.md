\begin{theorem}
Let
$$0 \to A \xrightarrow{f} B \xrightarrow{g} C \to 0$$
be a short exact sequence of abelian groups. Then the following are equivalent:
\begin{enumerate}
\item $\exists$ homomorphism $r: B \to A$ such that $rf = \text{id}_A$.
\item $\exists$ homomorphism $s: C \to B$ such that $gs = \text{id}_C$.
\item $\exists$ isomorphism $h: B \to A \oplus C$ such that the diagram
\begin{tikzcd}
0 \arrow[r] & A \arrow[r, "f"] \arrow[dr, "\iota_A"'] & B \arrow[r, "g"] \arrow[d, "h"] & C \arrow[r] & 0 \\
& & A \oplus C \arrow[ur, "\pi_C"'] & & 
\end{tikzcd}
commutes. (That is, $\iota_A = hf$ and $\pi_C = gh^{-1}$.)
\end{enumerate}
\end{theorem}
A short exact sequence satisfying any of these equivalent conditions is called split. In practice, we usually check that an exact sequence is split by showing that the surjection $g$ is right invertible. The importance of an exact sequence splitting is evident; when an exact sequence splits, the group in the middle splits as a direct sum of the groups on either side.

Most exact sequences, however, do not split. This reflects the difficulty of the extension problem. In general, there are many extensions of a given abelian group $C$ by another abelian group $A$ other than $A \oplus C$.

<div class="alert alert-danger">
<strong>Exercise</strong>
Show that the exact sequence
\[
0 \rightarrow \mathbb{Z} \xrightarrow{\times 2} \mathbb{Z} \rightarrow \mathbb{Z} / 2 \mathbb{Z} \rightarrow 0
\]
does not split using all three equivalent characterizations of a split exact sequence.
</div>

The condition that the groups are abelian is crucial for ensuring the result is a **direct sum** (or direct product) rather than a more general structure.

The abelian property is used to guarantee that elements from two key subgroups commute with each other. Let's look at the most common proof of the splitting lemma.

---

## Proof of 2⇒3

One part of the splitting lemma states that if you have a short exact sequence of abelian groups:
$$0 \longrightarrow A \xrightarrow{f} B \xrightarrow{g} C \longrightarrow 0$$
and there exists a homomorphism $h: C \to B$ such that $g \circ h = \text{id}_C$ (a "right split"), then $B \cong A \oplus C$.

To prove this, we construct a map $\phi: A \oplus C \to B$ and show it's an isomorphism. The map is defined as:
$$\phi(a, c) = f(a) + h(c)$$
*(Note: We use additive notation because the groups are abelian).*

The issue arises when we try to prove that $\phi$ is a homomorphism. Let's check the homomorphism property:
$$\phi \left( (a_1, c_1) + (a_2, c_2) \right) = \phi(a_1 + a_2, c_1 + c_2) = f(a_1+a_2) + h(c_1+c_2)$$
Since $f$ and $h$ are homomorphisms, this becomes:
$$= f(a_1) + f(a_2) + h(c_1) + h(c_2)$$
Now let's compute the other side of the equation:
$$\phi(a_1, c_1) + \phi(a_2, c_2) = ( f(a_1) + h(c_1) ) + ( f(a_2) + h(c_2) )$$

For $\phi$ to be a homomorphism, these two results must be equal:
$$f(a_1) + f(a_2) + h(c_1) + h(c_2) = f(a_1) + h(c_1) + f(a_2) + h(c_2)$$
After cancelling $f(a_1)$ from the left and $h(c_2)$ from the right, we are left with the required condition:
$$f(a_2) + h(c_1) = h(c_1) + f(a_2)$$

This equation says that any element in the image of $f$, namely $f(a_2)$, must commute with any element in the image of $h$, namely $h(c_1)$.

---

## Why the Abelian Condition Matters

* **If B is abelian**: The condition $f(a_2) + h(c_1) = h(c_1) + f(a_2)$ is **automatically satisfied**. In an abelian group, all elements commute by definition. Since $\text{im}(f)$ and $\text{im}(h)$ are subgroups of $B$, their elements must commute. Therefore, $\phi$ is a homomorphism, and the rest of the proof (showing it's a bijection) follows, leading to the conclusion $B \cong A \oplus C$. ✅

* **If B is not abelian**: There is no guarantee that an element from $\text{im}(f)$ will commute with an element from $\text{im}(h)$. If they don't commute, $\phi$ is not a homomorphism, and the proof fails. In this more general case, the splitting condition only proves that $B$ is a **semidirect product** of $A$ and $C$, written as $B \cong A \rtimes C$. The direct sum is the special case of the semidirect product where the group action is trivial, which corresponds to the elements commuting. 🤷‍♂️

In summary, the assumption that **B is abelian is used to justify the commutativity** needed to prove that the constructed map $\phi$ is a homomorphism.

# Splitting in general
<div class="alert alert-danger">
<strong>Exercise</strong>
Show that the splitting lemma fails for the exact sequence
\[
1 \to A_3 \to S_3 \to\{ \pm 1\} \to 1,
\]
where the map $A_3 \to S_3$ is the inclusion. Specifically, show that condition (2) holds, but the other conditions do not.
</div>
Since the splitting lemma fails for non-abelian groups, we cannot define a split exact sequence using all of the non-equivalent conditions. Instead, we adopt (2) as our definition. Some authors call a sequence satisfying (2) right split, while a sequence satisfying (1) is called left split.
<div class="alert alert-danger">
<strong>Exercise</strong>
A short exact sequence
\[
1 \rightarrow H \xrightarrow{f} G \xrightarrow{g} K \rightarrow 1
\]
splits (that is, there exists a homomorphism $K \rightarrow G$ with $g s=\operatorname{id}_K$ ) if and only if $G \cong H \rtimes K$ via an isomorphism which commutes with $f$ and $g$ (as in the splitting lemma.)
</div>
So for exact sequences of non-abelian groups, splitting means that $G$ can be split as a semi-direct product of $H$ and $K$.

In the situation of the previous exercise, what is the automorphism that the semi-direct product is taken with respect to?

<div class="alert alert-danger">
<strong>Exercise</strong>
Show that the exact sequence
\[
1 \rightarrow\{ \pm 1\} \rightarrow Q \rightarrow V \rightarrow 1
\]
is not split, where $Q$ is the quaternion group, $V$ is the Klein four-group, and the homomorphism $\{ \pm 1\} \rightarrow Q$ is the inclusion.
</div>