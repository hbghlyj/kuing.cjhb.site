# pseudoinverse
\begin{theorem}
Let $V$ be a finite-dimensional vector space over a field.
Let $A: V \rightarrow V$ be a linear map.

$\exists$ linear map $B: V \rightarrow V$ such that $A B A=A$ and $BAB=B$.
\end{theorem}
\begin{proof}
To show that there exists a linear map $B: V \to V$ such that $A B A = A$, proceed as follows.

Since $V$ is finite-dimensional, the kernel $\operatorname{Ker} A$ has a complement $U$ in $V$, so $V = \operatorname{Ker} A \oplus U$. The restriction $A|_U: U \to \operatorname{Im} A$ is an isomorphism. Let $R: \operatorname{Im} A \to U$ be the inverse of $A|_U$, so $A R = \operatorname{Id}_{\operatorname{Im} A}$.

The image $\operatorname{Im} A$ also has a complement $S$ in $V$, so $V = \operatorname{Im} A \oplus S$.

Define $B: V \to V$ by setting $B|_{\operatorname{Im} A} = R$ and $B|_S = 0$.

To verify $A B A = A$, note that for any $v \in V$, $A B A v = A (B (A v))$. Since $A v \in \operatorname{Im} A$, $B (A v) = R (A v)$, and $A (R (A v)) = (A R) (A v) = A v$. Thus, $A B A = A$.

Next, verify $B A B = B$. For any $v \in V$, write $v = w + s$ with $w \in \operatorname{Im} A$ and $s \in S$. Then $B v = B w + B s = R w + 0 = R w$.

Now, $A (B v) = A (R w) = w$ (since $A R = \operatorname{Id}_{\operatorname{Im} A}$).

Then $B (A (B v)) = B w = R w$.

But $R w =Bv$, so $B A Bv =Bv$ for all $v$, hence $BAB =B$.
\end{proof}
```
import Mathlib.LinearAlgebra.FiniteDimensional.Defs
variable {K V : Type u} [Field K] [AddCommGroup V] [Module K V]
variable (A : V →ₗ[K] V)

open LinearMap Submodule

theorem exists_ABA_eq_A : ∃ (B : V →ₗ[K] V), A.comp (B.comp A) = A := by
  -- 1. Decompose V into ker(A) and a complement W.
  obtain ⟨W, hW⟩ := Submodule.exists_isCompl (ker A)

  -- 2. Define the isomorphism from W to range(A).
  let A_on_W := A.domRestrict W
  let A_W_iso_range := LinearEquiv.ofInjective A_on_W (by
    rw [← ker_eq_bot, ker_domRestrict, eq_bot_iff]
    intro x hx
    apply Subtype.ext
    have : x.val ∈ ker A ⊓ W := ⟨hx, x.prop⟩
    rwa [hW.inf_eq_bot, mem_bot] at this)

  -- 3. Prove that the range of (A restricted to W) is the entire range of A.
  have h_range_eq : range A_on_W = range A := by
    calc
      range A_on_W = map A W := by simp [A_on_W]
      _ = ⊥ ⊔ map A W := by rw [bot_sup_eq]
      _ = map A (ker A) ⊔ map A W := by
          congr; ext y; constructor
          · rintro rfl; use 0; simp
          · rintro ⟨x, hx, rfl⟩; exact hx
      _ = map A (ker A ⊔ W) := by rw [Submodule.map_sup]
      _ = map A ⊤ := by rw [hW.sup_eq_top]
      _ = range A := Submodule.map_top A

  -- 4. Define the pseudo-inverse map B.
  obtain ⟨R_comp, hR_comp⟩ := Submodule.exists_isCompl (range A)

  -- Define the inverse map `f` from range(A) back to W (and then into V).
  let f : range A →ₗ[K] V :=
    (Submodule.subtype W).comp <| (A_W_iso_range.symm.toLinearMap).comp <|
      ((LinearEquiv.ofEq _ _ h_range_eq).symm : range A →ₗ[K] range A_on_W)

  -- Define B by specifying its behavior on `range A` and its complement `R_comp`.
  let B := (ofIsComplProdEquiv hR_comp) (f, 0)

  -- 5. Prove this B satisfies the equation.
  use B
  ext v

  -- Decompose v using projection maps. This version returns elements of the submodule types.
  let v_ker := Submodule.linearProjOfIsCompl (ker A) W hW v
  let v_W := Submodule.linearProjOfIsCompl W (ker A) hW.symm v

  have hv_decomp : v = v_ker + v_W := symm (linear_proj_add_linearProjOfIsCompl_eq_self hW v)
  simp [hv_decomp]

  -- The goal is `A (B (A ↑v_W)) = A ↑v_W`, which is true if `B (A ↑v_W) = ↑v_W`.
  suffices B (A ↑v_W) = ↑v_W by rw [this]

  let y := A ↑v_W
  have hy : y ∈ range A := mem_range_self A ↑v_W
  -- By construction, B applied to an element of `range A` is just our inverse map `f`.
  have B_y_eq_f_y : B y = f ⟨y, hy⟩ := by
    rw [← ofIsCompl_left_apply hR_comp _]
    rfl
  rw [B_y_eq_f_y]

  -- The final goal is to show f(A(v_W)) = v_W.
  -- We apply A_W_iso_range to both sides to simplify the inverses.
  simp [f, A_W_iso_range, A_on_W]
  exact
    LinearEquiv.symm_apply_apply
      (LinearEquiv.ofInjective (A.domRestrict W)
        (Eq.mpr (_root_.id (congrArg (fun _a => _a) (Eq.symm (propext ker_eq_bot))))
          (Eq.mpr (_root_.id (congrArg (fun _a => _a = ⊥) (ker_domRestrict W A)))
            (Eq.mpr (_root_.id (congrArg (fun _a => _a) (propext eq_bot_iff))) fun ⦃x⦄ hx =>
              Subtype.ext
                (have this := ⟨hx, Subtype.prop x⟩;
                Eq.mp (congrArg (fun _a => _a) (propext (mem_bot K)))
                  (Eq.mp (congrArg (fun _a => ↑x ∈ _a) (IsCompl.inf_eq_bot hW)) this))))))
      ((W.linearProjOfIsCompl (ker A) (IsCompl.symm hW)) v)
```