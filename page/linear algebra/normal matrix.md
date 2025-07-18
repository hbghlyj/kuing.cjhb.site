# normal matrix
```
import Mathlib

def IsUpperTriangular (A : Matrix (Fin n) (Fin n) ℝ) : Prop :=
  ∀ i j, i < j → A i j = 0

def IsLowerTriangular (A : Matrix (Fin n) (Fin n) ℝ) : Prop :=
  ∀ i j, i > j → A i j = 0

-- A matrix that is both upper and lower triangular is diagonal.
theorem isDiag_of_lower_and_upper_triangular
    {A : Matrix (Fin n) (Fin n) ℝ}
    (h_upper : IsUpperTriangular A) (h_lower : IsLowerTriangular A) :
    Matrix.IsDiag A := by
  -- The definition of a diagonal matrix is that A i j = 0 for all i ≠ j.
  intro i j hij
  -- For any two distinct indices i and j, either i < j or i > j.
  rcases lt_or_gt_of_ne hij with h_lt | h_gt
  · -- If i < j, the entry is 0 because the matrix is upper triangular.
    exact h_upper i j h_lt
  · -- If i > j, the entry is 0 because the matrix is lower triangular.
    exact h_lower i j h_gt

theorem IsStarNormal.isDiag_of_isUpperTriangular {A : Matrix (Fin n) (Fin n) ℝ}
    (h_normal : IsStarNormal A) (h_triu : IsUpperTriangular A) : Matrix.IsDiag A := by
  suffices IsLowerTriangular A from
    isDiag_of_lower_and_upper_triangular h_triu this

  -- For a real matrix, IsStarNormal means A * Aᵀ = Aᵀ * A.
  have h_comm : A * Aᵀ = Aᵀ * A := IsStarNormal.comm h_normal

  -- This implies the diagonal entries of A * Aᵀ and Aᵀ * A are equal.
  -- This gives us an equality on the sum of squares of rows and columns.
  have h_sum_sq_eq (i : Fin n) : ∑ k, (A i k) ^ 2 = ∑ k, (A k i) ^ 2 := by
    have := congr_fun (congr_arg Matrix.diag h_comm) i
    simp_rw [Matrix.diag_apply, Matrix.mul_apply, Matrix.transpose_apply, ← pow_two] at this
    exact this

  -- We will show A is lower triangular (A i j = 0 for i > j) by strong induction on the column j.
  -- The proposition P(j) is that all entries in column j below the diagonal are zero.
  suffices ∀ j i, j < i → A i j = 0 by
    intro i j hij; exact this j i hij

  intro j; apply Fin.strongInductionOn j; clear j
  intro j ih
  -- The induction hypothesis `ih` is: ∀ k < j, all entries in column k below the diagonal are zero.
  -- Formally: ih : ∀ (k : Fin n), k < j → ∀ (i : Fin n), k < i → A i k = 0
  -- Our goal is to prove: ∀ i, j < i → A i j = 0

  -- Consider the sum of squares equality for index `j`.
  have h_eq_j := h_sum_sq_eq j

  -- We rewrite both sides of the equality.
  -- LHS: ∑ k, (A j k) ^ 2
  -- RHS: ∑ k, (A k j) ^ 2
  rw [sum_over_univ_eq_sum_over_lt_add_sum_over_ge j,
      sum_over_univ_eq_sum_over_lt_add_sum_over_ge j] at h_eq_j

  -- On both sides, the sum of terms with index k < j can be shown to be zero.
  have sum_lt_lhs_zero : ∑ k in Finset.filter (fun k => k < j) Finset.univ, A j k ^ 2 = 0 := by
    apply Finset.sum_eq_zero; intro k hk
    -- For k < j, we have j > k. The term is A j k.
    -- By the induction hypothesis `ih`, since k < j, all entries in column k below the diagonal are 0.
    -- Since j > k, A j k is one of those entries.
    rw [Finset.mem_filter] at hk
    specialize ih k hk.2 j (by assumption) -- ih k (k<j) j (k<j) -> A j k = 0
    simp [ih]

  have sum_lt_rhs_zero : ∑ k in Finset.filter (fun k => k < j) Finset.univ, A k j ^ 2 = 0 := by
    apply Finset.sum_eq_zero; intro k hk
    -- For k < j, the term is A k j.
    -- Since A is upper triangular (`h_triu`), and k < j, this entry must be 0.
    rw [Finset.mem_filter] at hk
    simp [h_triu k j hk.2]

  -- Substitute these zero sums back into the main equality.
  rw [sum_lt_lhs_zero, zero_add, sum_lt_rhs_zero, zero_add] at h_eq_j
  -- The equality simplifies to: ∑_{k ≥ j} (A j k)² = ∑_{k ≥ j} (A k j)²
  -- We can split the sums at j.
  rw [sum_over_ge_eq_sum_over_gt_add_self j (fun k => A j k ^ 2),
      sum_over_ge_eq_sum_over_gt_add_self j (fun k => A k j ^ 2)] at h_eq_j

  -- The diagonal terms (A j j)^2 cancel out.
  rw [add_left_cancel_iff] at h_eq_j
  -- Now we have: ∑_{k > j} (A j k)² = ∑_{k > j} (A k j)²

  -- Let's simplify the LHS of this new equality.
  have lhs_sum_zero : ∑ k in Finset.filter (fun k => j < k) Finset.univ, A j k ^ 2 = 0 := by
    apply Finset.sum_eq_zero; intro k hk
    -- For k > j, we have j < k. The term is A j k.
    -- Since A is upper triangular (`h_triu`), this term is 0.
    rw [Finset.mem_filter] at hk
    simp [h_triu j k hk.2]

  -- This simplifies our main equality to: 0 = ∑_{k > j} (A k j)²
  rw [lhs_sum_zero] at h_eq_j
  rw [eq_comm] at h_eq_j

  -- A sum of non-negative squares is zero iff each term is zero.
  have h_off_diag_zero := (sum_eq_zero_iff_of_nonneg (fun k _ => sq_nonneg (A k j))).mp h_eq_j

  -- This gives `A i j = 0` for `i > j`, which is what we wanted to prove for the induction step.
  intro i hi_gt_j
  specialize h_off_diag_zero i (by simp [hi_gt_j])
  exact pow_eq_zero h_off_diag_zero
```