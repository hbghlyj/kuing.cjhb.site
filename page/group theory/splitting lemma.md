# for abelian groups
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

## Proof of 2⇒3

One part of the splitting lemma states that if you have a short exact sequence of abelian groups:
$$0 \longrightarrow A \xrightarrow{f} B \xrightarrow{g} C \longrightarrow 0$$
and there exists a homomorphism $s: C \to B$ such that $g \circ s= \text{id}_C$ (a "right split"), then $B \cong A \oplus C$.

To prove this, we construct a map $\phi: A \oplus C \to B$ and show it's an isomorphism. The map is defined as:
$$\phi(a, c) = f(a) + s(c)$$
The issue arises when we try to prove that $\phi$ is a homomorphism. Let's check the homomorphism property:
$$\phi \left( (a_1, c_1) + (a_2, c_2) \right) = \phi(a_1 + a_2, c_1 + c_2) = f(a_1+a_2) + s(c_1+c_2)$$
Since $f$ and $s$ are homomorphisms, this becomes:
$$= f(a_1) + f(a_2) + s(c_1) + s(c_2)$$
Now let's compute the other side of the equation:
$$\phi(a_1, c_1) + \phi(a_2, c_2) = ( f(a_1) + s(c_1) ) + ( f(a_2) + s(c_2) )$$

For $\phi$ to be a homomorphism, these two results must be equal:
$$f(a_1) + f(a_2) + s(c_1) + s(c_2) = f(a_1) + s(c_1) + f(a_2) + s(c_2)$$
After cancelling $f(a_1)$ from the left and $s(c_2)$ from the right, we are left with the required condition:
$$f(a_2) + s(c_1) = s(c_1) + f(a_2)$$

This equation says that any element in the image of $f$, namely $f(a_2)$, must commute with any element in the image of $s$, namely $s(c_1)$.
## B is abelian is needed to prove that φ is a homomorphism
* **If B is abelian**: The condition $f(a_2) + s(c_1) = s(c_1) + f(a_2)$ is satisfied. The rest of the proof is to show that the map $\phi$ is a bijection.
<details><summary>To prove $\phi$ is injective, we must show that its kernel is trivial. That is, if $\phi(a, c) = 0$, then it must be that $a=0$ and $c=0$.</summary>Assume $\phi(a, c) = 0$.
This means by our definition that$$f(a) + s(c) = 0$$
Apply the homomorphism $g$ to this equation.
$$g(f(a)) + g(s(c)) = 0$$
From the exactness at $B$, $g(f(a)) = 0$.
From the definition of the splitting map $h$, we know that $g(s(c)) = c$.
Substitute these back into the equation, $c = 0$.
Our original assumption $f(a) + s(c) = 0$ becomes:
$$f(a) + s(0) = 0$$
Since $h$ is a homomorphism, $s(0)=0$. So, $f(a) = 0$.
From the exactness at $A$, we know $f$ is injective (its kernel is trivial). Therefore, if $f(a)=0$, it must be that $a=0$.
</details>
<details><summary>To prove $\phi$ is surjective, we must show that for any element $b \in B$, we can find an element $(a, c) \in A \oplus C$ such that $\phi(a, c) = b$.</summary>
Let $b$ be any element in $B$. We need to find its corresponding $(a,c)$.
Let's define $c = g(b)$. This is a valid element in $C$ since $g: B \to C$.
We are looking for an element $a \in A$ such that $\phi(a,c)=b$, which means $f(a) + s(c) = b$.
Rearranging this gives $f(a) = b - s(c)$.
Now, let's consider the element $b - s(c)$. If we can show this element is in the image of $f$, then an $a$ must exist. An element is in $\text{im}(f)$ if and only if it is in $\ker(g)$. So, let's apply $g$ to it:
$$g(b - s(c)) = g(b) - g(s(c))$$
We defined $c = g(b)$.
We know $g(s(c)) = c$.
So, the expression becomes:
$$g(b) - c = c - c = 0$$
Conclude that $a$ exists.
Therefore, there must exist an $a \in A$ such that $f(a) = b - s(c)$.</details>
Since $\phi$ is an injective and surjective homomorphism, it is an isomorphism, and therefore $B \cong A \oplus C$.

* **If B is not abelian**: There is no guarantee that an element from $\text{im}(f)$ will commute with an element from $\text{im}(h)$. If they don't commute, $\phi$ is not a homomorphism, and the proof fails. In this more general case, the splitting condition only proves that $B$ is a **semidirect product** of $A$ and $C$, written as $B \cong A \rtimes C$. The direct sum is the special case of the semidirect product where the group action is trivial, which corresponds to the elements commuting.

# Splitting in general
<div class="alert alert-danger">
<strong>Exercise</strong>
Show that the splitting lemma fails for the exact sequence
\[
1 \to A_3 \to S_3 \to\{ \pm 1\} \to 1,
\]
where the map $A_3 \to S_3$ is the inclusion.
<details><summary>condition (2) holds</summary>
Define a section $s:\{\pm1\}\to S_3$ by
\[
s(1)=e,\qquad s(-1)=(12).
\]
Since $g((12))=-1$, we get $g\circ s=\mathrm{id}_{\{\pm1\}}$.</details>
<details><summary>conditions (1),(3) do not hold</summary>
Assume that a retract homomorphism $r: S_3 \to A_3$ exists. $\ker(r)$ must be a normal subgroup of $S_3$. The normal subgroups of $S_3$ are: $\{(1)\},A_3,S_3$. By the definition of retract, for $(123)\in A_3$ we must have $r((123)) = (123)$. Since $r((123))$ is not the identity element, $(123)$ cannot be in the kernel of $r$, so the kernel cannot be $A_3,S_3$ because $A_3,S_3$ contain $(123)$. The only remaining possibility is that the kernel is the trivial group: $\ker(r) = \{(1)\}$. This implies that $|\text{im}(r)| = |S_3| = 6$, contradiction.</details>
</div>
Since the splitting lemma fails for non-abelian groups, we cannot define a split exact sequence using all of the non-equivalent conditions. Instead, we adopt (2) as our definition. Some authors call a sequence satisfying (2) right split, while a sequence satisfying (1) is called left split.
<div class="alert alert-danger">
<strong>Exercise</strong>
A short exact sequence
\[
1 \rightarrow H \xrightarrow{f} G \xrightarrow{g} K \rightarrow 1
\]
splits (that is, there exists a homomorphism $K \rightarrow G$ with $g s=\text{id}_K$) if and only if $G \cong H \rtimes K$ via an isomorphism which commutes with $f$ and $g$.
</div>
<https://kconrad.math.uconn.edu/blurbs/grouptheory/splittinggp.pdf>
So for exact sequences of non-abelian groups, splitting means that $G$ can be split as a semi-direct product of $H$ and $K$.

In the situation of the previous exercise, what is the automorphism that the semi-direct product is taken with respect to?

<div class="alert alert-danger">
<strong>Exercise</strong>
Show that the exact sequence
\[
1 \rightarrow\{ \pm 1\} \rightarrow Q \rightarrow V \rightarrow 1
\]
is not split, where $Q$ is the quaternion group, $V$ is the Klein four-group, and the homomorphism $\{ \pm 1\} \rightarrow Q$ is the inclusion.
<details><summary>Proof</summary>
Assume that a section $s: V \to Q$ exists. Then $s((12))$ must be an element of order 2 in $Q$. The only elements of order 2 in $Q$ is $-1$. Thus, we must have $s((12)) = -1$. But then $s((34)) = -1$ as well, which contradicts the fact that $(12)$ and $(34)$ are distinct elements in $V$.
</details>
</div>