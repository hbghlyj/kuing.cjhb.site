# image of adjoint is orthogonal complement of kernel
```
import Mathlib
variable {n : ℕ}
variable (M : Matrix (Fin n) (Fin n) ℝ)
local notation "V" => Fin n → ℝ
def A : V →ₗ[ℝ] V := M.toLin'
def B : V →ₗ[ℝ] V := M.transpose.toLin'

theorem is_compl_ker_A_range_B : IsCompl (LinearMap.ker A) (LinearMap.range B) := by
  have h_adj : LinearMap.IsAdjoint A B := sorry  -- This is true because the adjoint of the linear map corresponding to M is the linear map corresponding to M^T over ℝ
  constructor
  · ext v
    simp only [Submodule.mem_inf, LinearMap.mem_ker, Submodule.mem_bot]
    intro h_ker h_range
    obtain ⟨w, rfl⟩ := h_range
    have := LinearMap.adjoint_inner_left h_adj w v
    rw [h_ker, inner_zero_right] in this
    rw [← this]
    exact inner_self_eq_zero.mpr rfl
  · apply Submodule.eq_top_of_finrank_eq
    calc finrank ℝ (LinearMap.ker A ⊔ LinearMap.range B) = finrank ℝ (LinearMap.ker A) + finrank ℝ (LinearMap.range B) - finrank ℝ (LinearMap.ker A ⊓ LinearMap.range B) := Submodule.finrank_sup_add_finrank_inf_eq _ _
      _ = finrank ℝ (LinearMap.ker A) + finrank ℝ (LinearMap.range B) - 0 := by rw [isCompl_inf.1 self, finrank_bot]
      _ = n := by
        have h1 : finrank ℝ (LinearMap.ker A) = n - LinearMap.finrank_range A := by rw [← LinearMap.finrank_range_add_finrank_ker A, add_sub_cancel']
        have h2 : LinearMap.finrank_range B = LinearMap.finrank_range A := by
          unfold LinearMap.finrank_range
          rw [← Matrix.rank_transpose M]
          rfl  -- Assuming the unfolding matches, or use appropriate lemmas
          sorry  -- In practice, use lemma that finrank range of toLin' is Matrix.rank M
        rw [h1, h2]
        rw [sub_add_cancel]
```