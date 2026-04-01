# matrix representation of an endomorphism transforms via conjugation under a change of basis
We proved that the matrix representation of an endomorphism transforms via conjugation under a change of basis: $M' = g^{-1} M g$, where $g$ is the change of basis matrix. This was formalized as `matrix_transform_conjugation''` using the helper lemma `basis_toMatrix_inv` and the Mathlib theorem `basis_toMatrix_mul_linearMap_toMatrix_mul_basis_toMatrix`.
```lean
import Mathlib

set_option linter.mathlibStandardSet false

open scoped BigOperators
open scoped Real
open scoped Nat
open scoped Classical
open scoped Pointwise

set_option maxHeartbeats 0
set_option maxRecDepth 4000
set_option synthInstance.maxHeartbeats 20000
set_option synthInstance.maxSize 128

set_option relaxedAutoImplicit false
set_option autoImplicit false

noncomputable section

#check Module.Basis

/-
The matrix representation of an endomorphism transforms via conjugation under a change of basis: $M' = g^{-1} M g$, where $g$ is the change of basis matrix.
-/
theorem matrix_transform_conjugation {R : Type*} [CommRing R] {M : Type*} [AddCommGroup M] [Module R M]
    {n : Type*} [Fintype n] [DecidableEq n]
    (b b' : Module.Basis n R M) (f : M →ₗ[R] M) :
    LinearMap.toMatrix b' b' f = (b.toMatrix b')⁻¹ * LinearMap.toMatrix b b f * b.toMatrix b' := by
  rw [ Matrix.inv_eq_left_inv ];
  rw [ basis_toMatrix_mul_linearMap_toMatrix_mul_basis_toMatrix ];
  exact?

/-
The inverse of the change of basis matrix from $b$ to $b'$ is the change of basis matrix from $b'$ to $b$.
-/
theorem basis_toMatrix_inv {R : Type*} [CommRing R] {M : Type*} [AddCommGroup M] [Module R M]
    {n : Type*} [Fintype n] [DecidableEq n]
    (b b' : Module.Basis n R M) :
    (b.toMatrix b')⁻¹ = b'.toMatrix b := by
  -- By definition of matrix multiplication and change of basis, we know that $(b'.toMatrix b) * (b.toMatrix b') = 1$.
  have h_inv_mul : (b'.toMatrix b) * (b.toMatrix b') = 1 := by
    convert b'.toMatrix_mul_toMatrix_flip b;
  rw [ Matrix.inv_eq_left_inv h_inv_mul ]

/-
The matrix representation of an endomorphism transforms via conjugation under a change of basis: $M' = g^{-1} M g$, where $g$ is the change of basis matrix.
-/
theorem matrix_transform_conjugation' {R : Type*} [CommRing R] {M : Type*} [AddCommGroup M] [Module R M]
    {n : Type*} [Fintype n] [DecidableEq n]
    (b b' : Module.Basis n R M) (f : M →ₗ[R] M) :
    LinearMap.toMatrix b' b' f = (b.toMatrix b')⁻¹ * LinearMap.toMatrix b b f * b.toMatrix b' := by
  exact?

/-
The matrix representation of an endomorphism transforms via conjugation under a change of basis: $M' = g^{-1} M g$, where $g$ is the change of basis matrix.
-/
theorem matrix_transform_conjugation'' {R : Type*} [CommRing R] {M : Type*} [AddCommGroup M] [Module R M]
    {n : Type*} [Fintype n] [DecidableEq n]
    (b b' : Module.Basis n R M) (f : M →ₗ[R] M) :
    LinearMap.toMatrix b' b' f = (b.toMatrix b')⁻¹ * LinearMap.toMatrix b b f * b.toMatrix b' := by
  rw [basis_toMatrix_inv]
  rw [basis_toMatrix_mul_linearMap_toMatrix_mul_basis_toMatrix]
```