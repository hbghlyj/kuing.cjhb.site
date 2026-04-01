# an inequality relating G, A, and L mean
We prove the inequality $L^{4/5} \le \frac{2}{3} G^{4/5} + \frac{1}{3} A^{4/5}$ for the logarithmic, geometric, and arithmetic means of two positive real numbers.
We reduce the inequality to a single variable $y = (b/a)^{1/5} \ge 1$.
We define a helper polynomial $P(y)$ and prove two main bounds:
1. $\frac{2}{3}y^2 + \frac{1}{3}\left(\frac{y^5+1}{2}\right)^{4/5} \ge (P(y))^4$ (`lemma_bound_2`).
2. $5 \ln y \ge \frac{y^5 - 1}{(P(y))^5}$ (`lemma_bound_3`).

`lemma_bound_2` is proved by analyzing the logarithmic derivatives of both sides. We show that the logarithmic derivative of the LHS is greater than or equal to that of the RHS (`log_deriv_ineq`), which reduces to a polynomial inequality (`poly_ineq_reduced`).
`lemma_bound_3` is proved by analyzing the derivative of the difference function.
Combining these bounds yields the main result.

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
Definitions of Geometric Mean (G), Arithmetic Mean (A), Logarithmic Mean (L), and a helper polynomial P(y).
-/
noncomputable def G (a b : ℝ) : ℝ := Real.sqrt (a * b)

noncomputable def A (a b : ℝ) : ℝ := (a + b) / 2

noncomputable def L (a b : ℝ) : ℝ :=
  if a = b then a else (b - a) / (Real.log b - Real.log a)

noncomputable def P (y : ℝ) : ℝ := (7 * y^3 + 5 * y^2 + 5 * y + 7) / (12 * y^2 + 12)

/-
Define the LHS and RHS of the inequality in lemma_bound_2.
-/
noncomputable def bound2_lhs (y : ℝ) : ℝ := (2 / 3) * y^2 + (1 / 3) * ((y^5 + 1) / 2) ^ (4 / 5 : ℝ)
noncomputable def bound2_rhs (y : ℝ) : ℝ := (P y)^4

/-
Definitions of `Q` and the logarithmic derivatives of the LHS and RHS of the inequality in `lemma_bound_2`.
-/
noncomputable def Q (y : ℝ) : ℝ := ((y^5 + 1) / 2) ^ (1 / 5 : ℝ)

noncomputable def log_deriv_lhs (y : ℝ) : ℝ :=
  (4 * y * Q y + 2 * y^4) / (2 * y^2 * Q y + (Q y)^5)

noncomputable def log_deriv_rhs (y : ℝ) : ℝ :=
  4 * (1 / (y + 1) + (14 * y - 2) / (7 * y^2 - 2 * y + 7) - 2 * y / (y^2 + 1))

/-
The derivative of the log of the LHS of the bound is equal to `log_deriv_lhs`.
-/
lemma log_deriv_lhs_eq (y : ℝ) (hy : 1 ≤ y) :
  deriv (fun x => Real.log (bound2_lhs x)) y = log_deriv_lhs y := by
    convert HasDerivAt.deriv ( HasDerivAt.log ( HasDerivAt.add ( HasDerivAt.const_mul ( 2 / 3 ) ( hasDerivAt_pow 2 y ) ) ( HasDerivAt.const_mul ( 1 / 3 ) ( HasDerivAt.rpow_const ( HasDerivAt.div_const ( HasDerivAt.add ( hasDerivAt_pow 5 y ) ( hasDerivAt_const _ _ ) ) _ ) _ ) ) ) _ ) using 1 <;> norm_num;
    · unfold log_deriv_lhs; norm_num [ Real.rpow_neg ( by positivity : ( ( y^5 + 1 ) / 2 ) ≥ 0 ) ] ; ring;
      rw [ show Q y = ( ( y ^ 5 + 1 ) / 2 ) ^ ( 1 / 5 : ℝ ) by rfl ] ; ring;
      field_simp;
      rw [ ← Real.rpow_natCast _ 4, ← Real.rpow_mul ( by positivity ) ] ; norm_num;
    · positivity;
    · positivity

/-
The derivative of the log of the RHS of the bound is equal to `log_deriv_rhs`.
-/
lemma log_deriv_rhs_eq (y : ℝ) (hy : 1 ≤ y) :
  deriv (fun x => Real.log (bound2_rhs x)) y = log_deriv_rhs y := by
    unfold bound2_rhs log_deriv_rhs;
    unfold P; norm_num [ mul_comm ];
    field_simp;
    norm_num [ show y * ( 7 * y - 2 ) + 7 ≠ 0 by nlinarith, show y * ( y * ( y * 7 + 5 ) + 5 ) + 7 ≠ 0 by nlinarith [ sq_nonneg ( y - 1 ) ], show y ^ 2 + 1 ≠ 0 by nlinarith, show y * ( y * ( y * 7 + 5 ) + 5 ) + 7 ≠ 0 by nlinarith [ sq_nonneg ( y - 1 ) ], show y ^ 2 + 1 ≠ 0 by nlinarith, mul_assoc, mul_comm, mul_left_comm ];
    field_simp
    ring;
    nlinarith [ inv_mul_cancel_left₀ ( by nlinarith : ( 7 - y * 2 + y ^ 2 * 7 ) ≠ 0 ) y, inv_mul_cancel₀ ( by nlinarith : ( 7 - y * 2 + y ^ 2 * 7 ) ≠ 0 ), pow_pos ( by linarith : 0 < y ) 3, pow_pos ( by linarith : 0 < y ) 4, pow_pos ( by linarith : 0 < y ) 5, pow_pos ( by linarith : 0 < y ) 6, pow_pos ( by linarith : 0 < y ) 7 ]

/-
Definitions of polynomials `R_den` and `R_num` used in the logarithmic derivative of the RHS.
-/
noncomputable def R_den (y : ℝ) : ℝ := (y + 1) * (7 * y^2 - 2 * y + 7) * (y^2 + 1)
noncomputable def R_num (y : ℝ) : ℝ := (7 * y^2 - 2 * y + 7) * (y^2 + 1) + (14 * y - 2) * (y + 1) * (y^2 + 1) - 2 * y * (y + 1) * (7 * y^2 - 2 * y + 7)

/-
Definitions of polynomials `A_poly` and `B_poly` used to express the inequality of logarithmic derivatives.
-/
noncomputable def A_poly (y : ℝ) : ℝ := 2 * y * R_den y - 4 * y^2 * R_num y
noncomputable def B_poly (y : ℝ) : ℝ := (y^5 + 1) * R_num y - y^4 * R_den y

/-
The denominator polynomial `R_den` is positive for $y \ge 1$.
-/
lemma R_den_pos (y : ℝ) (hy : 1 ≤ y) : 0 < R_den y := by
  exact mul_pos ( mul_pos ( by linarith ) ( by nlinarith ) ) ( by nlinarith )

/-
The logarithmic derivative of the RHS can be expressed as a rational function using `R_num` and `R_den`.
-/
lemma log_deriv_rhs_eq_poly (y : ℝ) (hy : 1 ≤ y) :
  log_deriv_rhs y = 4 * R_num y / R_den y := by
    unfold log_deriv_rhs R_num R_den
    field_simp [hy];
    rw [ add_div', div_mul_eq_mul_div, div_sub', div_eq_div_iff ] <;> nlinarith [ sq_nonneg ( y - 1 ) ]

/-
The polynomial `A_poly` is non-positive for $y \ge 1$.
-/
lemma A_poly_neg (y : ℝ) (hy : 1 ≤ y) : A_poly y ≤ 0 := by
  -- By definition of $A_poly$, we have $A_poly y = 2 * y * R_den y - 4 * y^2 * R_num y$.
  unfold A_poly R_den R_num;
  nlinarith [ sq_nonneg ( y^2 - 1 ), sq_nonneg ( y^3 - y^2 ), sq_nonneg ( y^4 - y^3 ), sq_nonneg ( y^5 - y^4 ) ]

/-
The polynomial `B_poly` is non-positive for $y \ge 1$.
-/
lemma B_poly_neg (y : ℝ) (hy : 1 ≤ y) : B_poly y ≤ 0 := by
  -- Substitute the definitions of `R_num` and `R_den` into the inequality.
  unfold B_poly R_num R_den;
  nlinarith [ sq_nonneg ( y^2 - 1 ), sq_nonneg ( y^3 - y^2 ), sq_nonneg ( y^4 - y^3 ), sq_nonneg ( y^5 - y^4 ) ]

/-
Definitions of `A_pos` and `B_pos`.
-/
noncomputable def A_pos (y : ℝ) : ℝ := 14 * y^5 + 4 * y^4 + 44 * y^3 + 4 * y^2 + 14 * y
noncomputable def B_pos (y : ℝ) : ℝ := 5 * y^7 + y^6 + 17 * y^5 + 17 * y^4 + 17 * y^3 + 17 * y^2 + y + 5

/-
Factorization of `A_poly`.
-/
lemma A_poly_eq_neg_mul (y : ℝ) : A_poly y = (y - 1) * (-A_pos y) := by
  unfold A_poly A_pos R_den R_num; ring;

/-
Factorization of `B_poly`.
-/
lemma B_poly_eq_neg_mul (y : ℝ) : B_poly y = (y - 1) * (-B_pos y) := by
  unfold B_poly B_pos; ring;
  unfold R_den R_num; ring;

/-
The reduced polynomial inequality holds for $y \ge 1$.
-/
lemma poly_ineq_reduced (y : ℝ) (hy : 1 ≤ y) :
  2 * (B_pos y)^5 ≥ (y^5 + 1) * (A_pos y)^5 := by
    unfold B_pos A_pos;
    -- By substituting $y = x + 1$ where $x \geq 0$, we can transform the inequality into a polynomial in $x$ with non-negative coefficients.
    suffices h_subst : ∀ x : ℝ, 0 ≤ x → 2 * (5 * (x + 1) ^ 7 + (x + 1) ^ 6 + 17 * (x + 1) ^ 5 + 17 * (x + 1) ^ 4 + 17 * (x + 1) ^ 3 + 17 * (x + 1) ^ 2 + (x + 1) + 5) ^ 5 ≥ ((x + 1) ^ 5 + 1) * (14 * (x + 1) ^ 5 + 4 * (x + 1) ^ 4 + 44 * (x + 1) ^ 3 + 4 * (x + 1) ^ 2 + 14 * (x + 1)) ^ 5 by
      convert h_subst ( y - 1 ) ( by linarith ) using 1 <;> ring;
    intro x hxring_nf;
    exact le_of_sub_nonneg ( by ring_nf; positivity )

/-
Auxiliary lemma connecting the polynomial inequality to the logarithmic derivative inequality.
-/
lemma lemma_bound_2_aux (y : ℝ) (hy : 1 ≤ y) :
  Q y * A_poly y ≥ B_poly y := by
    rw [ A_poly_eq_neg_mul, B_poly_eq_neg_mul ];
    -- Since $A_{pos}(y) \ge 0$ and $B_{pos}(y) \ge 0$ and $Q(y) \ge 0$, this is equivalent to $(Q(y))^5 (A_{pos}(y))^5 \le (B_{pos}(y))^5$.
    have h_equiv : (Q y)^5 * (A_pos y)^5 ≤ (B_pos y)^5 := by
      have h_equiv : (Q y)^5 = (y^5 + 1) / 2 := by
        rw [ show Q y = ( ( y ^ 5 + 1 ) / 2 ) ^ ( 1 / 5 : ℝ ) by rfl, ← Real.rpow_natCast, ← Real.rpow_mul ( by positivity ) ] ; norm_num;
      exact h_equiv.symm ▸ by linarith [ poly_ineq_reduced y hy ] ;
    -- Since $Q(y) \ge 0$, $A_{pos}(y) \ge 0$, and $B_{pos}(y) \ge 0$, we can take the fifth root of both sides of the inequality.
    have h_root : Q y * A_pos y ≤ B_pos y := by
      contrapose! h_equiv;
      simpa only [ ← mul_pow ] using pow_lt_pow_left₀ h_equiv ( by exact le_of_lt ( show 0 < B_pos y from by unfold B_pos; positivity ) ) ( by positivity );
    nlinarith

/-
The logarithmic derivative inequality follows from the auxiliary polynomial inequality.
-/
lemma log_deriv_ineq (y : ℝ) (hy : 1 ≤ y) :
  log_deriv_lhs y ≥ log_deriv_rhs y := by
    -- By multiplying both sides of the inequality $Q(y) * A_poly y ≥ B_poly y$ by $R_den$ (which is positive for $y ≥ 1$), we can relate the logarithmic derivatives.
    have h_mul : Q y * (2 * y * R_den y - 4 * y^2 * R_num y) ≥ (y^5 + 1) * R_num y - y^4 * R_den y := by
      convert lemma_bound_2_aux y hy using 1;
    -- Recall that $log_deriv_lhs y = \frac{4yQ + 2y^4}{2y^2Q + Q^5}$ and $log_deriv_rhs y = \frac{4R_num}{R_den}$.
    have h_log_lhs : log_deriv_lhs y = (4 * y * Q y + 2 * y^4) / (2 * y^2 * Q y + (Q y)^5) := by
      unfold log_deriv_lhs Q; ring
    have h_log_rhs : log_deriv_rhs y = 4 * R_num y / R_den y := by
      exact?;
    rw [ h_log_lhs, h_log_rhs, ge_iff_le, div_le_div_iff₀ ];
    · rw [ show Q y ^ 5 = ( y ^ 5 + 1 ) / 2 by rw [ show Q y = ( ( y ^ 5 + 1 ) / 2 ) ^ ( 1 / 5 : ℝ ) by rfl ] ; rw [ ← Real.rpow_natCast _ 5, ← Real.rpow_mul ( by positivity ) ] ; norm_num ] at * ; nlinarith [ pow_pos ( by positivity : 0 < y ) 3, pow_pos ( by positivity : 0 < y ) 4 ];
    · exact R_den_pos y hy;
    · exact add_pos_of_nonneg_of_pos ( mul_nonneg ( by positivity ) ( Real.rpow_nonneg ( by positivity ) _ ) ) ( pow_pos ( Real.rpow_pos_of_pos ( by positivity ) _ ) _ )

/-
Proof of `lemma_bound_2` using the logarithmic derivative inequality.
-/
lemma lemma_bound_2 (y : ℝ) (hy : 1 ≤ y) :
  (2 / 3) * y^2 + (1 / 3) * ((y^5 + 1) / 2) ^ (4 / 5 : ℝ) ≥ (P y)^4 := by
    -- By the logarithmic derivative inequality, we have $\log(\text{bound2\_lhs}(y)) \ge \log(\text{bound2\_rhs}(y))$ for all $y \ge 1$.
    have h_log_ineq : Real.log (bound2_lhs y) ≥ Real.log (bound2_rhs y) := by
      -- By the logarithmic derivative inequality, we have $\log(\text{bound2\_lhs}(y)) \ge \log(\text{bound2\_rhs}(y))$ for all $y \ge 1$. We'll use the fact that the derivative of the difference is non-negative.
      have h_log_deriv_nonneg : ∀ y : ℝ, 1 ≤ y → deriv (fun x => Real.log (bound2_lhs x) - Real.log (bound2_rhs x)) y ≥ 0 := by
        intro y hy
        have h_log_deriv_nonneg : deriv (fun x => Real.log (bound2_lhs x)) y - deriv (fun x => Real.log (bound2_rhs x)) y ≥ 0 := by
          rw [ log_deriv_lhs_eq y hy, log_deriv_rhs_eq y hy ] ; exact sub_nonneg_of_le <| log_deriv_ineq y hy;
        convert h_log_deriv_nonneg using 1;
        convert deriv_sub _ _ using 1;
        · apply_rules [ DifferentiableAt.log, DifferentiableAt.add, DifferentiableAt.mul, DifferentiableAt.rpow ] <;> norm_num;
          · exact differentiableAt_const _;
          · exact differentiableAt_const _;
          · positivity;
          · exact ne_of_gt ( add_pos_of_pos_of_nonneg ( by positivity ) ( by exact mul_nonneg ( by positivity ) ( Real.rpow_nonneg ( by positivity ) _ ) ) );
        · apply_rules [ DifferentiableAt.log, DifferentiableAt.rpow ] <;> norm_num;
          · apply_rules [ DifferentiableAt.div, DifferentiableAt.pow, DifferentiableAt.add, differentiableAt_id, differentiableAt_const ];
            · norm_num;
            · norm_num;
            · exact differentiableAt_id.const_mul _;
            · norm_num [ mul_comm ];
            · positivity;
          · exact ne_of_gt ( by exact pow_pos ( by exact div_pos ( by positivity ) ( by positivity ) ) _ );
      by_contra h_contra;
      -- Apply the mean value theorem to the interval $[1, y]$.
      obtain ⟨c, hc⟩ : ∃ c ∈ Set.Ioo 1 y, deriv (fun x => Real.log (bound2_lhs x) - Real.log (bound2_rhs x)) c = (Real.log (bound2_lhs y) - Real.log (bound2_rhs y) - (Real.log (bound2_lhs 1) - Real.log (bound2_rhs 1))) / (y - 1) := by
        apply_rules [ exists_deriv_eq_slope ];
        · exact hy.lt_of_ne ( by rintro rfl; norm_num [ bound2_lhs, bound2_rhs, P ] at h_contra );
        · refine' ContinuousOn.sub _ _;
          · refine' ContinuousOn.log _ _;
            · exact ContinuousOn.add ( continuousOn_const.mul ( continuousOn_pow 2 ) ) ( continuousOn_const.mul ( ContinuousOn.rpow ( ContinuousOn.div_const ( ContinuousOn.add ( continuousOn_pow 5 ) continuousOn_const ) _ ) continuousOn_const <| by norm_num ) );
            · exact fun x hx => ne_of_gt <| add_pos_of_pos_of_nonneg ( mul_pos ( by norm_num ) <| sq_pos_of_pos <| by linarith [ hx.1 ] ) <| mul_nonneg ( by norm_num ) <| Real.rpow_nonneg ( by nlinarith [ hx.1, pow_nonneg ( by linarith [ hx.1 ] : 0 ≤ x ) 5 ] ) _;
          · refine' ContinuousOn.log _ _;
            · exact ContinuousOn.pow ( ContinuousOn.div ( Continuous.continuousOn ( by continuity ) ) ( Continuous.continuousOn ( by continuity ) ) fun x hx => by nlinarith [ hx.1 ] ) _;
            · exact fun x hx => ne_of_gt <| pow_pos ( by exact div_pos ( by nlinarith [ hx.1, pow_two_nonneg ( x^2 ) ] ) ( by nlinarith [ hx.1, pow_two_nonneg ( x^2 ) ] ) ) _;
        · refine' fun x hx => DifferentiableAt.differentiableWithinAt _;
          refine' DifferentiableAt.sub _ _;
          · refine' DifferentiableAt.log _ _;
            · exact DifferentiableAt.add ( DifferentiableAt.mul ( differentiableAt_const _ ) ( differentiableAt_id.pow 2 ) ) ( DifferentiableAt.mul ( differentiableAt_const _ ) ( DifferentiableAt.rpow ( by norm_num ) ( by norm_num ) ( by nlinarith [ hx.1, pow_pos ( sub_pos.mpr hx.1 ) 2, pow_pos ( sub_pos.mpr hx.1 ) 3, pow_pos ( sub_pos.mpr hx.1 ) 4 ] ) ) );
            · exact ne_of_gt ( add_pos_of_pos_of_nonneg ( mul_pos ( by norm_num ) ( sq_pos_of_pos ( by linarith [ hx.1 ] ) ) ) ( mul_nonneg ( by norm_num ) ( Real.rpow_nonneg ( by nlinarith [ hx.1, pow_pos ( by linarith [ hx.1 ] : 0 < x ) 5 ] ) _ ) ) );
          · refine' DifferentiableAt.log _ _ <;> norm_num [ bound2_rhs ];
            · exact DifferentiableAt.pow ( show DifferentiableAt ℝ ( fun x => ( 7 * x ^ 3 + 5 * x ^ 2 + 5 * x + 7 ) / ( 12 * x ^ 2 + 12 ) ) x from DifferentiableAt.div ( by norm_num [ mul_comm ] ) ( by norm_num [ mul_comm ] ) ( by nlinarith [ hx.1 ] ) ) _;
            · exact ne_of_gt ( div_pos ( by nlinarith [ hx.1, pow_pos ( sub_pos.mpr hx.1 ) 3 ] ) ( by nlinarith [ hx.1, pow_pos ( sub_pos.mpr hx.1 ) 2 ] ) );
      norm_num +zetaDelta at *;
      rw [ eq_div_iff ] at hc <;> nlinarith [ h_log_deriv_nonneg c hc.1.1.le, show Real.log ( bound2_lhs 1 ) = Real.log ( bound2_rhs 1 ) by unfold bound2_lhs bound2_rhs; norm_num [ P ] ];
    contrapose! h_log_ineq;
    apply_rules [ Real.log_lt_log ];
    exact add_pos_of_pos_of_nonneg ( mul_pos ( by norm_num ) ( sq_pos_of_pos ( by positivity ) ) ) ( mul_nonneg ( by norm_num ) ( Real.rpow_nonneg ( by positivity ) _ ) )

/-
Proof of the second bound inequality using derivatives.
-/
lemma lemma_bound_3 (y : ℝ) (hy : 1 ≤ y) :
  5 * Real.log y ≥ (y^5 - 1) / (P y)^5 := by
    -- By the properties of logarithms, we can rewrite the inequality as $5 \ln y \geq \frac{y^5 - 1}{P y^5}$.
    suffices h_log : ∀ y : ℝ, 1 ≤ y → 5 * Real.log y ≥ (y^5 - 1) / ((7 * y^3 + 5 * y^2 + 5 * y + 7) / (12 * (y^2 + 1)))^5 by
      convert h_log y hy using 3 ; unfold P ; ring;
    field_simp;
    intro y hy
    have h_deriv : ∀ y : ℝ, 1 < y → deriv (fun y => 5 * Real.log y - (y^5 - 1) * 12^5 * (y^2 + 1)^5 / ((y * (y * (y * 7 + 5) + 5) + 7)^5)) y ≥ 0 := by
      intro y hy;
      norm_num [ show y ≠ 0 by linarith, show y * ( y * ( y * 7 + 5 ) + 5 ) + 7 ≠ 0 by positivity ];
      field_simp;
      nlinarith [ pow_pos ( sub_pos.mpr hy ) 2, pow_pos ( sub_pos.mpr hy ) 3, pow_pos ( sub_pos.mpr hy ) 4, pow_pos ( sub_pos.mpr hy ) 5, pow_pos ( sub_pos.mpr hy ) 6, pow_pos ( sub_pos.mpr hy ) 7, pow_pos ( sub_pos.mpr hy ) 8, pow_pos ( sub_pos.mpr hy ) 9, pow_pos ( sub_pos.mpr hy ) 10 ];
    by_contra h_contra;
    -- Apply the mean value theorem to the interval $[1, y]$.
    obtain ⟨c, hc⟩ : ∃ c ∈ Set.Ioo 1 y, deriv (fun y => 5 * Real.log y - (y^5 - 1) * 12^5 * (y^2 + 1)^5 / ((y * (y * (y * 7 + 5) + 5) + 7)^5)) c = (5 * Real.log y - (y^5 - 1) * 12^5 * (y^2 + 1)^5 / ((y * (y * (y * 7 + 5) + 5) + 7)^5) - (5 * Real.log 1 - (1^5 - 1) * 12^5 * (1^2 + 1)^5 / ((1 * (1 * (1 * 7 + 5) + 5) + 7)^5))) / (y - 1) := by
      apply_rules [ exists_deriv_eq_slope ];
      · exact hy.lt_of_ne ( by rintro rfl; norm_num at h_contra );
      · exact continuousOn_of_forall_continuousAt fun x hx => by exact ContinuousAt.sub ( ContinuousAt.mul continuousAt_const ( Real.continuousAt_log ( by linarith [ hx.1 ] ) ) ) ( ContinuousAt.div ( Continuous.continuousAt ( by continuity ) ) ( Continuous.continuousAt ( by continuity ) ) ( by exact ne_of_gt ( pow_pos ( by nlinarith [ hx.1, hx.2, pow_nonneg ( by linarith [ hx.1 ] : 0 ≤ x ) 3 ] ) _ ) ) ) ;
      · exact fun x hx => DifferentiableAt.differentiableWithinAt ( by norm_num [ show x ≠ 0 by linarith [ hx.1 ], show x * ( x * ( x * 7 + 5 ) + 5 ) + 7 ≠ 0 by nlinarith [ hx.1, hx.2, pow_pos ( by linarith [ hx.1 ] : 0 < x ) 3 ] ] );
    norm_num at *;
    rw [ eq_div_iff ] at hc <;> nlinarith [ h_deriv c hc.1.1 ]

/-
The main theorem: $L^{4/5} \le \frac{2}{3} G^{4/5} + \frac{1}{3} A^{4/5}$ for $a, b > 0$.
-/
theorem logarithmic_mean_inequality (a b : ℝ) (ha : 0 < a) (hb : 0 < b) :
  (L a b) ^ (4/5 : ℝ) ≤ (2/3) * (G a b) ^ (4/5 : ℝ) + (1/3) * (A a b) ^ (4/5 : ℝ) := by
    -- Assume without loss of generality that $a \le b$.
    suffices h_wlog : ∀ {a b : ℝ}, 0 < a → 0 < b → a ≤ b → (L a b) ^ (4 / 5 : ℝ) ≤ (2 / 3) * (G a b) ^ (4 / 5 : ℝ) + (1 / 3) * (A a b) ^ (4 / 5 : ℝ) by
      field_simp;
      by_cases hab : a ≤ b;
      · linarith [ h_wlog ha hb hab ];
      · convert mul_le_mul_of_nonneg_right ( h_wlog hb ha ( le_of_not_ge hab ) ) zero_le_three using 1 <;> ring;
        · unfold L; ring;
          rw [ show -Real.log b + Real.log a = - ( Real.log b - Real.log a ) by ring, inv_neg ] ; ring;
          grind;
        · unfold G A; ring;
    -- Let $x = b/a > 1$. Let $y = x^{1/5}$. Then $y > 1$.
    intro a b ha hb hab
    by_cases hx : a = b;
    · unfold L G A; norm_num [ hx ] ; ring_nf; norm_num;
      rw [ Real.sqrt_sq hb.le ] ; ring_nf ; norm_num;
    · -- Let $x = b/a > 1$. Let $y = x^{1/5}$. Then $y > 1$. We express $L, G, A$ in terms of $a$ and $y$.
      obtain ⟨x, y, hx, hy⟩ : ∃ x y : ℝ, 1 < x ∧ y = x ^ (1 / 5 : ℝ) ∧ b = a * x := by
        exact ⟨ b / a, _, by rw [ lt_div_iff₀ ha ] ; contrapose! hx; linarith, rfl, by rw [ mul_div_cancel₀ _ ha.ne' ] ⟩;
      -- The inequality simplifies to showing $(\frac{y^5-1}{5 \ln y})^{4/5} \le \frac{2}{3} y^2 + \frac{1}{3} (\frac{y^5+1}{2})^{4/5}$.
      suffices h_simp : ((y^5 - 1) / (5 * Real.log y)) ^ (4 / 5 : ℝ) ≤ (2 / 3) * y ^ 2 + (1 / 3) * ((y^5 + 1) / 2) ^ (4 / 5 : ℝ) by
        convert mul_le_mul_of_nonneg_left h_simp ( show 0 ≤ a ^ ( 4 / 5 : ℝ ) by positivity ) using 1 <;> norm_num [ hy ] ; ring;
        · unfold L; norm_num [ Real.log_rpow ( by linarith : 0 < x ) ] ; ring;
          rw [ ← Real.rpow_natCast, ← Real.rpow_mul ( by linarith ) ] ; norm_num [ Real.log_mul, ha.ne', hb.ne' ] ; ring;
          rw [ if_neg ( by linarith ), Real.log_mul ( by linarith ) ( by linarith ) ] ; ring;
          rw [ ← Real.mul_rpow ( by positivity ) ( by nlinarith [ inv_pos.mpr ( Real.log_pos hx ) ] ) ] ; ring;
        · unfold G A; ring; norm_num [ ha.le, hb.le ] ; ring;
          rw [ Real.mul_rpow ( by positivity ) ( by positivity ), Real.sqrt_eq_rpow, ← Real.rpow_mul ( by positivity ) ] ; ring; norm_num [ ← Real.rpow_natCast _ 5, ← Real.rpow_mul ( by positivity : 0 ≤ x ) ] ; ring;
          rw [ show ( 1 / 2 + x * ( 1 / 2 ) ) = ( 1 / 2 ) * ( 1 + x ) by ring, Real.mul_rpow ( by positivity ) ( by positivity ) ] ; norm_num [ sq, ← Real.rpow_add ( by positivity : 0 < x ) ] ; ring;
          rw [ ← Real.mul_rpow ( by positivity ) ( by positivity ), ← Real.mul_rpow ( by positivity ) ( by positivity ) ] ; ring;
      -- This is equivalent to $5 \ln y \ge \frac{y^5-1}{(R(y))^{5/4}}$.
      suffices h_eq : 5 * Real.log y ≥ (y ^ 5 - 1) / ((2 / 3) * y ^ 2 + (1 / 3) * ((y ^ 5 + 1) / 2) ^ (4 / 5 : ℝ)) ^ (5 / 4 : ℝ) by
        refine' le_trans ( Real.rpow_le_rpow ( div_nonneg ( sub_nonneg.mpr <| one_le_pow₀ <| by linarith [ show 1 ≤ y by exact hy.1.symm ▸ Real.one_le_rpow hx.le ( by norm_num ) ] ) <| mul_nonneg ( by norm_num ) <| Real.log_nonneg <| by linarith [ show 1 ≤ y by exact hy.1.symm ▸ Real.one_le_rpow hx.le ( by norm_num ) ] ) ( div_le_div_of_nonneg_left _ _ h_eq ) <| by norm_num ) _;
        · exact sub_nonneg_of_le ( one_le_pow₀ ( by rw [ hy.1 ] ; exact Real.one_le_rpow hx.le ( by norm_num ) ) );
        · exact div_pos ( sub_pos.mpr ( one_lt_pow₀ ( by rw [ hy.1 ] ; exact Real.one_lt_rpow hx ( by norm_num ) ) ( by norm_num ) ) ) ( Real.rpow_pos_of_pos ( by exact add_pos_of_pos_of_nonneg ( mul_pos ( by norm_num ) ( sq_pos_of_pos ( by rw [ hy.1 ] ; exact Real.rpow_pos_of_pos ( by linarith ) _ ) ) ) ( mul_nonneg ( by norm_num ) ( Real.rpow_nonneg ( by exact div_nonneg ( add_nonneg ( pow_nonneg ( by rw [ hy.1 ] ; exact Real.rpow_nonneg ( by linarith ) _ ) _ ) zero_le_one ) zero_le_two ) _ ) ) ) _ );
        · rw [ div_div_cancel₀ ] <;> norm_num;
          · rw [ ← Real.rpow_mul ( by exact add_nonneg ( mul_nonneg ( by norm_num ) ( sq_nonneg _ ) ) ( mul_nonneg ( by norm_num ) ( Real.rpow_nonneg ( by exact div_nonneg ( add_nonneg ( pow_nonneg ( by linarith [ show 0 ≤ y by exact hy.1.symm ▸ Real.rpow_nonneg ( by linarith ) _ ] ) _ ) zero_le_one ) zero_le_two ) _ ) ) ) ] ; norm_num;
          · exact ne_of_gt ( sub_pos_of_lt ( one_lt_pow₀ ( by rw [ hy.1 ] ; exact Real.one_lt_rpow hx ( by norm_num ) ) ( by norm_num ) ) );
      -- From `lemma_bound_2`, we have $R(y) \ge (P(y))^4$.
      have h_bound2 : (2 / 3) * y ^ 2 + (1 / 3) * ((y ^ 5 + 1) / 2) ^ (4 / 5 : ℝ) ≥ (P y) ^ 4 := by
        convert lemma_bound_2 y ( show 1 ≤ y by rw [ hy.1 ] ; exact Real.one_le_rpow hx.le ( by norm_num ) ) using 1;
      -- Since $y \ge 1$, $P(y) > 0$ and $R(y) > 0$.
      have h_pos : 0 < P y ∧ 0 < (2 / 3) * y ^ 2 + (1 / 3) * ((y ^ 5 + 1) / 2) ^ (4 / 5 : ℝ) := by
        exact ⟨ by rw [ show P y = ( 7 * y ^ 3 + 5 * y ^ 2 + 5 * y + 7 ) / ( 12 * y ^ 2 + 12 ) by rfl ] ; exact div_pos ( by nlinarith [ show 0 < y by exact hy.1.symm ▸ by positivity ] ) ( by nlinarith [ show 0 < y by exact hy.1.symm ▸ by positivity ] ), by exact add_pos_of_pos_of_nonneg ( by nlinarith [ show 0 < y by exact hy.1.symm ▸ by positivity ] ) ( by exact mul_nonneg ( by norm_num ) ( Real.rpow_nonneg ( by nlinarith [ show 0 < y by exact hy.1.symm ▸ by positivity, pow_pos ( show 0 < y by exact hy.1.symm ▸ by positivity ) 5 ] ) _ ) ) ⟩;
      -- Raising to the power $5/4$ (which is monotonic for positive bases), we get $(R(y))^{5/4} \ge (P(y))^5$.
      have h_bound3 : ((2 / 3) * y ^ 2 + (1 / 3) * ((y ^ 5 + 1) / 2) ^ (4 / 5 : ℝ)) ^ (5 / 4 : ℝ) ≥ (P y) ^ 5 := by
        refine' le_trans _ ( Real.rpow_le_rpow ( by exact pow_nonneg h_pos.1.le _ ) h_bound2 ( by norm_num ) );
        rw [ ← Real.rpow_natCast _ 4, ← Real.rpow_mul h_pos.1.le ] ; norm_num;
      -- From `lemma_bound_3`, we have $5 \ln y \ge \frac{y^5-1}{(P(y))^5}$.
      have h_bound3 : 5 * Real.log y ≥ (y ^ 5 - 1) / (P y) ^ 5 := by
        convert lemma_bound_3 y ( by rw [ hy.1 ] ; exact Real.one_le_rpow hx.le ( by norm_num ) ) using 1;
      refine le_trans ?_ h_bound3;
      gcongr;
      · exact sub_nonneg_of_le ( one_le_pow₀ ( by rw [ hy.1 ] ; exact Real.one_le_rpow hx.le ( by norm_num ) ) );
      · exact pow_pos h_pos.1 _
```