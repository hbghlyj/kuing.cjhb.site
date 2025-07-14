# Lagrange interpolation
\begin{theorem}
Given distinct real numbers $x_1, \dots, x_n$ and any real numbers $y_1, \dots, y_n$, there exists a unique polynomial $P$ of degree $<n$ such that $\forall i,P(x_i) = y_i$.
\end{theorem}
```
import Mathlib
open Polynomial

theorem exists_unique_polynomial {n : ℕ} (x y : Fin n → ℝ) (hx : ∀ i j, x i = x j → i = j) :
    ∃! P : Polynomial ℝ, P.degree < n ∧ ∀ i, P.eval (x i) = y i := by
  let V := degreeLT ℝ n
  let W := Fin n → ℝ
  have hV : Module.finrank ℝ V = n := by
    apply Module.finrank_eq_of_rank_eq
    rw [(degreeLTEquiv ℝ n).rank_eq, rank_fun']
    simp [Fintype.card_fin]
  have hW : Module.finrank ℝ W = n := Module.finrank_eq_of_rank_eq (rank_fin_fun n)
  let T : V →ₗ[ℝ] W := LinearMap.pi fun i =>
    { toFun := fun p => (p : Polynomial ℝ).eval (x i),
      map_add' := fun _ _ => eval_add,
      map_smul' := fun _ _ => eval_smul _ _ _ }
  have h_inj : Function.Injective T := by
    apply LinearMap.ker_eq_bot.mp
    ext v
    rw [Submodule.mem_bot]
    constructor
    · intro h_zero
      by_contra h_nonzero
      let p := ↑v
      have hp_ne0 : p ≠ 0 := by
        simp_all only [Module.finrank_fintype_fun_eq_card, Fintype.card_fin, ne_eq, not_false_eq_true, V, W, p]
      have h_natdeg : p.1.natDegree < n :=by
        rw [natDegree_lt_iff_degree_lt]
        rw [← hV]
        rw [← mem_degreeLT]
        simp_all only [Module.finrank_fintype_fun_eq_card, Fintype.card_fin, ne_eq, not_false_eq_true, SetLike.coe_mem,
          V, W, p]
        simp_all only [Module.finrank_fintype_fun_eq_card, Fintype.card_fin, ne_eq, not_false_eq_true,
          ZeroMemClass.coe_eq_zero, V, W, p]
      have h_card_roots : p.1.roots.card ≤ p.1.natDegree := card_roots' p.1
      have h_set_ncard : (p.1.roots.toFinset : Set ℝ).ncard ≤ p.1.roots.card := by
        simpa using Multiset.toFinset_card_le _
      have h_set_le_natdeg : (p.1.roots.toFinset : Set ℝ).ncard ≤ p.1.natDegree := by linarith
      have h_set_eq : (p.1.roots.toFinset : Set ℝ) = {t | p.1.eval t = 0} := by
        simp_all [V, W, p]
        obtain ⟨val, property⟩ := v
        simp_all only [Submodule.mk_eq_zero, V]
        ext v : 1
        simp_all only [Finset.mem_coe, Multiset.mem_toFinset, mem_roots', ne_eq, not_false_eq_true, IsRoot.def,
          true_and, Set.mem_setOf_eq]
      rw [h_set_eq] at h_set_le_natdeg
      have h_range_subset : ↑(Finset.univ.image x) ⊆ {t | p.1.eval t = 0} := by
        intro t ht
        rw [Set.mem_setOf_eq]
        obtain ⟨i, hi1,hi2⟩ := Finset.mem_image.mp ht
        have:T v i = 0:= congr_fun h_zero i
        subst hi2
        simp_all only [Module.finrank_fintype_fun_eq_card, Fintype.card_fin, ne_eq, not_false_eq_true,
          Finset.mem_univ, LinearMap.pi_apply, LinearMap.coe_mk, AddHom.coe_mk,
          Finset.coe_image, Finset.coe_univ, Set.image_univ, Set.mem_range, exists_apply_eq_apply, V, W, p, T]
      have h_card_image : (Finset.univ.image x : Set ℝ).ncard = n := by
        rw [Finset.coe_image, Set.ncard_image_of_injective _ hx]
        simp_all only [Module.finrank_fintype_fun_eq_card, Fintype.card_fin, ne_eq, not_false_eq_true,
          Finset.coe_image, Finset.coe_univ, Set.image_univ, Set.ncard_univ,
          Nat.card_eq_fintype_card, V, W, p]
      have h_ncard_ge : {t | p.1.eval t = 0}.ncard ≥ n := by
        rw [← h_card_image]
        refine Set.ncard_le_ncard h_range_subset ?_
        rw [← h_set_eq]
        exact Finset.finite_toSet p.1.roots.toFinset
      linarith [h_set_le_natdeg]
    · intro a
      subst a
      simp

  have hT : Function.Surjective T := by
    refine (LinearMap.injective_iff_surjective_of_finrank_eq_finrank ?_).mp h_inj
    rw [hV,hW]

  rcases hT y with ⟨p, hp⟩
  refine' existsUnique_of_exists_of_unique _ fun Q hQ => _
  · use ↑p
    constructor
    · apply mem_degreeLT.mp
      exact Submodule.coe_mem p
    · exact fun i =>
      (Real.ext_cauchy (congrArg Real.cauchy (congrFun hp.symm i))).symm
  · intro ⟨left, right⟩ ⟨left_1, right_1⟩
    let Q_v : V := ⟨Q, mem_degreeLT.mpr left⟩
    let hQ_v : V := ⟨hQ, mem_degreeLT.mpr left_1⟩
    have h_T_eq : T Q_v = T hQ_v := by
      subst hp
      simp_all only [Module.finrank_fintype_fun_eq_card, Fintype.card_fin, V, W, T, Q_v, hQ_v]
      obtain ⟨val, property⟩ := p
      simp_all only [V]
      ext x_1 : 1
      simp_all only [LinearMap.pi_apply, LinearMap.coe_mk, AddHom.coe_mk, V]
    have h_v_eq : Q_v = hQ_v := h_inj h_T_eq
    exact congr_arg Subtype.val h_v_eq
```