# Sufficient conditions for complex differentiability based on the Cauchy-Riemann equations
We proved two main theorems:
1. `complex_differentiable_of_real_differentiable_and_CR`: If a function is real-differentiable and satisfies the Cauchy-Riemann equations (in terms of the Fréchet derivative), then it is complex-differentiable.
2. `complex_differentiable_of_continuous_partials_and_CR_components`: If the real and imaginary parts of a function have continuous partial derivatives (i.e., the function is $C^1$ over $\mathbb{R}$) and satisfy the component-wise Cauchy-Riemann equations, then the function is complex-differentiable.

Note that the existence of partial derivatives alone (without continuity or real differentiability) is not sufficient for complex differentiability (e.g., $f(z) = \sqrt{|xy|}$ at $z=0$). The standard theorem requires continuous partial derivatives, which we have formalized.
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

/-
If a function is real-differentiable and satisfies the Cauchy-Riemann equations, it is complex-differentiable.
-/
def satisfies_CR (f : ℂ → ℂ) (z : ℂ) : Prop :=
  fderiv ℝ f z Complex.I = Complex.I * fderiv ℝ f z 1

theorem complex_differentiable_of_real_differentiable_and_CR {f : ℂ → ℂ} {z : ℂ}
    (h_real : DifferentiableAt ℝ f z) (h_CR : satisfies_CR f z) :
    DifferentiableAt ℂ f z := by
  refine' ⟨ _, hasFDerivAt_iff_tendsto.mpr _ ⟩;
  exact?;
  convert h_real.hasFDerivAt.isLittleO.tendsto_div_nhds_zero.norm using 1;
  · simp +decide [ div_eq_inv_mul, norm_mul, mul_comm ];
  · norm_num [ Norm.norm ]

/-
If a function has continuous partial derivatives (is C^1 over reals) and satisfies the Cauchy-Riemann equations, it is complex-differentiable.
-/
theorem complex_differentiable_of_ContDiffAt_real_and_CR {f : ℂ → ℂ} {z : ℂ}
    (h_C1 : ContDiffAt ℝ 1 f z) (h_CR : satisfies_CR f z) :
    DifferentiableAt ℂ f z := by
  -- By definition of `satisfies_CR`, we know that `f` satisfies the Cauchy-Riemann equations at `z`.
  apply complex_differentiable_of_real_differentiable_and_CR (h_C1.differentiableAt (by norm_num)) h_CR

/-
If the real and imaginary parts of a complex function have continuous partial derivatives and satisfy the Cauchy-Riemann equations, then the function is complex-differentiable.
-/
def u (f : ℂ → ℂ) (x y : ℝ) : ℝ := (f (x + y * Complex.I)).re
def v (f : ℂ → ℂ) (x y : ℝ) : ℝ := (f (x + y * Complex.I)).im

def satisfies_CR_components (f : ℂ → ℂ) (x y : ℝ) : Prop :=
  ∃ (ux uy vx vy : ℝ),
    HasDerivAt (fun x' => u f x' y) ux x ∧
    HasDerivAt (fun y' => u f x y') uy y ∧
    HasDerivAt (fun x' => v f x' y) vx x ∧
    HasDerivAt (fun y' => v f x y') vy y ∧
    ux = vy ∧ uy = -vx

theorem complex_differentiable_of_continuous_partials_and_CR_components
    (f : ℂ → ℂ) (x y : ℝ)
    (h_cont_partials : ContDiffAt ℝ 1 (fun p : ℝ × ℝ => f (p.1 + p.2 * Complex.I)) (x, y))
    (h_CR : satisfies_CR_components f x y) :
    DifferentiableAt ℂ f (x + y * Complex.I) := by
  -- By definition of `satisfies_CR_components`, we know that the partial derivatives of `f` satisfy the Cauchy-Riemann equations at `z = x + y * Complex.I`.
  have h_CR_at_z : satisfies_CR f (x + y * Complex.I) := by
    have h_cont_diff : ContDiffAt ℝ 1 f (x + y * Complex.I) := by
      have : ContDiffAt ℝ 1 (fun p : ℝ × ℝ => f ((p.1 : ℂ) + (p.2 : ℂ) * Complex.I)) (Complex.re (x + y * Complex.I), Complex.im (x + y * Complex.I)) := by
        aesop;
      convert this.comp ( x + y * Complex.I ) ( Complex.reCLM.contDiff.contDiffAt.prodMk ( Complex.imCLM.contDiff.contDiffAt ) ) using 1 ; aesop;
    obtain ⟨ ux, uy, vx, vy, hu, hv, hw, hx, rfl, rfl ⟩ := h_CR;
    have h_cont_diff : HasFDerivAt f (fderiv ℝ f (x + y * Complex.I)) (x + y * Complex.I) := by
      exact h_cont_diff.differentiableAt le_rfl |> DifferentiableAt.hasFDerivAt;
    have h_cont_diff : HasDerivAt (fun x' : ℝ => f (x' + y * Complex.I)) (fderiv ℝ f (x + y * Complex.I) 1) x ∧ HasDerivAt (fun y' : ℝ => f (x + y' * Complex.I)) (fderiv ℝ f (x + y * Complex.I) Complex.I) y := by
      constructor;
      · convert HasFDerivAt.comp_hasDerivAt _ _ _ using 1;
        · exact h_cont_diff;
        · convert HasDerivAt.add ( HasDerivAt.ofReal_comp ( hasDerivAt_id x ) ) ( hasDerivAt_const _ _ ) using 1 ; norm_num;
      · convert HasFDerivAt.comp_hasDerivAt _ _ _ using 1;
        · exact h_cont_diff;
        · simpa using HasDerivAt.const_add ( x : ℂ ) ( HasDerivAt.mul ( hasDerivAt_id y |> HasDerivAt.ofReal_comp ) ( hasDerivAt_const _ Complex.I ) );
    have h_cont_diff : HasDerivAt (fun x' : ℝ => u f x' y + Complex.I * v f x' y) (fderiv ℝ f (x + y * Complex.I) 1) x ∧ HasDerivAt (fun y' : ℝ => u f x y' + Complex.I * v f x y') (fderiv ℝ f (x + y * Complex.I) Complex.I) y := by
      convert h_cont_diff using 2;
      · ext; simp [u, v];
        simp +decide [ Complex.ext_iff ];
      · ext; simp +decide [ Complex.ext_iff, u, v ] ;
    have h_cont_diff : (fderiv ℝ f (x + y * Complex.I)) 1 = ux + Complex.I * vx ∧ (fderiv ℝ f (x + y * Complex.I)) Complex.I = -vx + Complex.I * ux := by
      have h_cont_diff : HasDerivAt (fun x' : ℝ => u f x' y + Complex.I * v f x' y) (ux + Complex.I * vx) x ∧ HasDerivAt (fun y' : ℝ => u f x y' + Complex.I * v f x y') (-vx + Complex.I * ux) y := by
        exact ⟨ by simpa using HasDerivAt.add ( HasDerivAt.ofReal_comp hu ) ( HasDerivAt.const_mul Complex.I ( HasDerivAt.ofReal_comp hw ) ), by simpa using HasDerivAt.add ( HasDerivAt.ofReal_comp hv ) ( HasDerivAt.const_mul Complex.I ( HasDerivAt.ofReal_comp hx ) ) ⟩;
      exact ⟨ HasDerivAt.unique ( by tauto ) ( by tauto ), HasDerivAt.unique ( by tauto ) ( by tauto ) ⟩;
    exact Eq.symm ( by rw [ show ( fderiv ℝ f ( x + y * Complex.I ) ) Complex.I = Complex.I * ( fderiv ℝ f ( x + y * Complex.I ) ) 1 by simp +decide [ h_cont_diff, Complex.ext_iff ] ] );
  apply_rules [ complex_differentiable_of_ContDiffAt_real_and_CR ];
  have h_cont_diff : ContDiffAt ℝ 1 (fun p : ℂ => f p) (x + y * Complex.I) := by
    have h_cont_diff_real : ContDiffAt ℝ 1 (fun p : ℝ × ℝ => f (p.1 + p.2 * Complex.I)) (x, y) := h_cont_partials
    have h_cont_diff_real : ContDiffAt ℝ 1 (fun p : ℂ => f (p.re + p.im * Complex.I)) (x + y * Complex.I) := by
      have h_cont_diff_real : ContDiffAt ℝ 1 (fun p : ℝ × ℝ => f (p.1 + p.2 * Complex.I)) (x, y) := h_cont_diff_real
      have h_cont_diff_real : ContDiffAt ℝ 1 (fun p : ℂ => f (p.re + p.im * Complex.I)) (x + y * Complex.I) := by
        have h_cont_diff_real : ContDiffAt ℝ 1 (fun p : ℝ × ℝ => f (p.1 + p.2 * Complex.I)) (Complex.re (x + y * Complex.I), Complex.im (x + y * Complex.I)) := by
          aesop
        have h_cont_diff_real : ContDiffAt ℝ 1 (fun p : ℂ => (p.re, p.im)) (x + y * Complex.I) := by
          exact ContDiffAt.prodMk ( Complex.reCLM.contDiff.contDiffAt ) ( Complex.imCLM.contDiff.contDiffAt );
        convert ‹ContDiffAt ℝ 1 ( fun p : ℝ × ℝ => f ( p.1 + p.2 * Complex.I ) ) ( ( x + y * Complex.I |> Complex.re ), ( x + y * Complex.I |> Complex.im ) ) ›.comp ( x + y * Complex.I ) h_cont_diff_real using 1
      exact h_cont_diff_real;
    convert h_cont_diff_real using 1 ; ext ; simp +decide;
  exact h_cont_diff
```