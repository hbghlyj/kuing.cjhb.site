# adjoint map
The adjoint of the linear map corresponding to M is the linear map corresponding to M^T over ℝ

Note: The classical adjugate (transpose of cofactor matrix) satisfies `det(M) I = M * adj(M) = adj(M) * M` and is unrelated to the adjoint here.
```lean
import Mathlib.Analysis.InnerProductSpace.Adjoint
import Mathlib.LinearAlgebra.Matrix.ToLin
import Mathlib.LinearAlgebra.Matrix.Transpose

variable {n : ℕ}

theorem adjoint_toLin_eq_toLin_transpose (M : Matrix (Fin n) (Fin n) ℝ) :
    LinearMap.adjoint (Matrix.toLin' M) = Matrix.toLin' Mᵀ := by
  ext u v
  simp_rw [LinearMap.adjoint_inner_right, real_inner_eq, Matrix.toLin'_apply,
    Matrix.mulVec_apply, mul_comm (u _)]
  rw [← Finset.sum_mul_sum_eq_sum_mul_mul]
  congr with i
  congr with j
  simp_rw [Matrix.transpose_apply]
```

To arrive at this formalization, start by identifying the key concepts: the "linear map corresponding to M" is `Matrix.toLin' M`, which represents matrix multiplication as a linear map on the finite-dimensional real vector space `Fin n → ℝ`. This space has a standard inner product given by the dot product, ∑ᵢ xᵢ yᵢ.

The adjoint of a linear map `f` satisfies ⟨f u, v⟩ = ⟨u, adjoint f v⟩ for all u, v, where ⟨·, ·⟩ is the inner product.

The goal is to show that the adjoint of `toLin' M` equals `toLin' Mᵀ`, where `Mᵀ` is the transpose `M.transpose`.

To prove this, extend the linear maps to act on vectors and equate the inner products:

Left side: `⟨(toLin' M) u, v⟩ = ∑ᵢ ((∑ⱼ M i j * u j) * v i) = ∑ᵢ∑ⱼ M i j * u j * v i`

Right side: `⟨u, (toLin' Mᵀ) v⟩ = ∑ⱼ (u j * (∑ᵢ (Mᵀ) j i * v i)) = ∑ⱼ∑ᵢ u j * M i j * v i (since (Mᵀ) j i = M i j)`

The expressions are equal after commuting multiplication and relabeling indices, confirming the adjoint is indeed the transpose over ℝ.