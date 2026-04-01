# F. Riesz theorem
\begin{theorem}
Let $V$ be a normed vector space over $\Bbb R$ and assume its closed unit ball $B$ is compact.
Then $V$ is finite-dimensional.
\end{theorem}
\begin{proof}
There exists $x\in\Bbb R$ such that $0 < |x| < 1$.

Cover $B$ with finitely many open balls of radius $r = |x|$ with centers $a_1, ..., a_N$. It suffices to show that any $v$ in $B$ is in the span of $a_1,\dots, a_N$. So let $v\in B$ and define a sequence $u_n$ by $u_0 = v$ and $u_{n+1} = 1/x (u_n - b_n)$, where $b_n$ is of the $a_1,\dots,a_N$ such that $|u_n - b_n| < r$. Then $v_n = b_0 + x b_1 +\dots+ x^n b_n$ satisfies $|v_n - v| < r^{n+1}$ and hence $v_n$ converges to $v$ and since the span of a finite set is closed over a complete field this implies that $v$ is in the span of $a_1, \dots, a_N$.
\end{proof}
```lean
import Mathlib

open Metric

variable [NormedAddCommGroup V]

/-- Finite cover of the closed unit ball by small balls and an associated choice function. -/
lemma finite_cover_choice
    (Hcomp : IsCompact (closedBall (0 : V) (1 : ℝ)))
    {r : ℝ} (hr : 0 < r) :
    ∃ (A : Set V), A ⊆ closedBall (0 : V) 1 ∧ A.Finite ∧
      ∃ f : V → V, (∀ y ∈ closedBall (0 : V) 1, f y ∈ A ∧ dist y (f y) < r) := by
  -- Extract a finite subcover of {ball a r | a ∈ closedBall 0 1} using compactness.
  -- Then define f by choosing a witness a ∈ A for each y ∈ B.
  -- Apply the definition of total boundedness to the closed unit ball.
  have h_tot_bounded : TotallyBounded (closedBall (0 : V) 1) := by
    exact Hcomp.totallyBounded;
  rw [ totallyBounded_iff_subset ] at h_tot_bounded;
  -- Apply the hypothesis `h_tot_bounded` with the entourage corresponding to the distance `r`.
  obtain ⟨t, ht₁, ht₂, ht₃⟩ := h_tot_bounded {p : V × V | dist p.1 p.2 < r} (by
  exact dist_mem_uniformity hr);
  -- For each $y \in \text{closedBall } 0 1$, there exists $a \in t$ such that $dist y a < r$.
  have h_choice : ∀ y ∈ closedBall (0 : V) 1, ∃ a ∈ t, dist y a < r := by
    simp_all +decide [ Set.subset_def ];
  choose! f hf₁ hf₂ using h_choice;
  exact ⟨ t, ht₁, ht₂, f, fun y hy => ⟨ hf₁ y hy, hf₂ y hy ⟩ ⟩

variable [NormedSpace ℝ V]

/-- Over ℝ, there exists a scalar with norm strictly between 0 and 1 (e.g. 1/2). -/
lemma exists_real_norm_lt_one : ∃ x : ℝ, 0 < ‖x‖ ∧ ‖x‖ < 1 := by
  refine ⟨(1 : ℝ) / 2, ?_, ?_⟩
  · have : 0 < (1 : ℝ) / 2 := by norm_num
    simp
  · have : (1 : ℝ) / 2 < 1 := by norm_num
    simpa

/-- If a function maps into `A`, then finite `ℕ`-indexed sums of its values lie in `span k A`.
This will be used for `v_approx` once we know each `f (u i) ∈ A`. -/
lemma sum_smul_image_mem_span
    (A : Set V) (x : ℝ) (f : V → V) (u : ℕ → V)
    (hfin : ∀ i, f (u i) ∈ A) :
    ∀ n, (∑ i : Fin (n+1), (x^(i : ℕ)) • f (u i)) ∈ (Submodule.span ℝ A : Submodule ℝ V) := by
  intro n
  -- Since each term $x^i • f (u i)$ is a scalar multiple of $f (u i)$, and $f (u i) \in A$, it follows that $x^i • f (u i) \in \text{span } A$.
  have h_term : ∀ i : Fin (n + 1), x ^ (i : ℕ) • f (u (i : ℕ)) ∈ Submodule.span ℝ A := by
    -- Since each term $x^i • f (u i)$ is a scalar multiple of $f (u i)$, and $f (u i) \in A$, it follows that $x^i • f (u i) \in \text{span } A$ by the definition of the span.
    intros i
    apply Submodule.smul_mem
    apply Submodule.subset_span
    apply hfin;
  exact Submodule.sum_mem _ fun i _ => h_term i

/-- Define the auxiliary iteration and the partial approximants. -/
noncomputable def u_iter (x : ℝ) (f : V → V) (v : V) : ℕ → V
  | 0     => v
  | (n+1) => (1/x) • ((u_iter x f v n) - f (u_iter x f v n))

noncomputable def v_approx (x : ℝ) (f : V → V) (v : V) : ℕ → V :=
  fun n => ∑ i : Fin (n+1), (x^(i : ℕ)) • f (u_iter x f v i)

/-- Geometric error bound for the approximation sequence. -/
lemma geom_error_bound
    {x : ℝ} (hx0 : 0 < ‖x‖)
    {A : Set V}
    (f : V → V)
    (hf : ∀ y ∈ closedBall (0 : V) 1, f y ∈ A ∧ dist y (f y) < ‖x‖)
    (v : V) (hv : v ∈ closedBall (0 : V) 1) :
    ∀ n, ‖v - v_approx x f v n‖ ≤ ‖x‖^(n+1) := by
  -- Prove by induction using u_iter recurrence and the choice function property.
  -- By expanding the sum, we can relate the norm of the difference to the norm of the last term.
  have hv_expand : ∀ n, v - v_approx x f v n = (x^(n+1)) • (u_iter x f v (n+1)) := by
    -- We proceed by induction on $n$.
    intro n
    induction' n with n ih;
    · -- By definition of $v_approx$ and $u_iter$, we have $v - v_approx x f v 0 = v - f v$ and $x^(0+1) • u_iter x f v (0+1) = x • ((1/x) • (v - f v))$.
      simp [v_approx, u_iter];
      rw [ ← smul_assoc, smul_eq_mul, mul_inv_cancel₀ ( by aesop ), one_smul ];
    · -- Substitute the induction hypothesis into the expression.
      have h_sub : v - v_approx x f v (n + 1) = (x^(n+1) • u_iter x f v (n+1)) - x^(n+1) • f (u_iter x f v (n+1)) := by
        -- Substitute the definition of `v_approx` into the left-hand side.
        have h_sub : v - v_approx x f v (n + 1) = v - (v_approx x f v n + x^(n+1) • f (u_iter x f v (n + 1))) := by
          simp +decide [ v_approx, Fin.sum_univ_castSucc ];
        rw [ h_sub, ← ih, sub_add_eq_sub_sub ];
      convert h_sub using 1;
      norm_num +zetaDelta at *;
      erw [ show u_iter x f v ( n + 2 ) = ( 1 / x ) • ( u_iter x f v ( n + 1 ) - f ( u_iter x f v ( n + 1 ) ) ) by rfl ] ; simp +decide [ hx0, pow_succ, mul_assoc, smul_smul ];
      rw [ smul_sub ];
  -- Since $u_iter$ is in the closed unit ball, its norm is ≤ 1.
  have hu_iter_norm : ∀ n, ‖u_iter x f v n‖ ≤ 1 := by
    -- By induction on $n$, we can show that $‖u_iter x f v n‖ ≤ 1$ for all $n$.
    intro n
    induction' n with n ih;
    · aesop;
    · -- By definition of $u_iter$, we have $u_iter x f v (n + 1) = (1/x) • (u_iter x f v n - f (u_iter x f v n))$.
      rw [show u_iter x f v (n + 1) = (1/x) • (u_iter x f v n - f (u_iter x f v n)) from rfl];
      have := hf ( u_iter x f v n ) ?_ <;> simp_all +decide [ dist_eq_norm ];
      rw [ norm_smul, Real.norm_eq_abs, abs_inv ];
      rw [ inv_mul_le_iff₀ ( abs_pos.mpr hx0 ) ] ; linarith [ hf ( u_iter x f v n ) ih ];
  -- Using the expansion and the norm bound on $u_iter$, we get:
  intros n
  rw [hv_expand n]
  simp [norm_smul];
  exact mul_le_of_le_one_right ( by positivity ) ( hu_iter_norm _ )

/-- The span of a finite set is closed. -/
lemma span_closed_of_finite {A : Set V} (hA : A.Finite) [T2Space V] :
    IsClosed ((Submodule.span ℝ A : Submodule ℝ V) : Set V) := by
  have hFD : FiniteDimensional ℝ (Submodule.span ℝ A : Submodule ℝ V) := by
    exact Module.Finite.span_of_finite ℝ hA
  refine Submodule.closed_of_finiteDimensional (Submodule.span ℝ A)

/-- If the closed unit ball is contained in the span of a set, the whole space is spanned. -/
lemma ball_span_top {A : Set V}
    (hB : closedBall (0 : V) 1 ⊆ (Submodule.span ℝ A : Submodule ℝ V)) :
    (Submodule.span ℝ A : Submodule ℝ V) = ⊤ := by
  -- For any w, choose y : k with ‖y‖ > ‖w‖ (powers of an element with ‖·‖ > 1),
  -- then (1/y) • w ∈ closedBall 0 1 ⊆ span, hence w ∈ span by scaling.
  refine' eq_top_iff.mpr fun v hv => _;
  -- Choose $r = \frac{1}{\|v\| + 1}$.
  obtain ⟨r, hr₀, hr₁⟩ : ∃ r : ℝ, 0 < r ∧ r * ‖v‖ < 1 := by
    exact ⟨ 1 / ( ‖v‖ + 1 ), by positivity, by rw [ div_mul_eq_mul_div, div_lt_iff₀ ] <;> linarith [ norm_nonneg v ] ⟩;
  have := hB ( show r • v ∈ closedBall 0 1 from ?_ );
  · simpa [ hr₀.ne' ] using Submodule.smul_mem _ ( r⁻¹ ) this;
  · simpa [ norm_smul, abs_of_nonneg hr₀.le ] using by nlinarith [ norm_nonneg v ]

/-- Main theorem: a compact closed unit ball forces finite dimensionality.
    Strategy: choose x with 0 < ‖x‖ < 1; finite cover A; define choice function f;
    build contraction iteration u_iter and partial sums v_approx with geometric error bound;
    use closedness of finite span and a ball→top span lemma to conclude finite dimensionality. -/
theorem compact_unit_ball_implies_finite_dim
    (Hcomp : IsCompact (closedBall (0 : V) (1 : ℝ))) :
    FiniteDimensional ℝ V := by
  -- Real-field version: follow the micro-lemma plan above.
  -- Assume the closed unit ball is compact. Choose x with ‖x‖ < 1, then find a finite cover A ⊆ closed ball of radius 1 with balls centered at points in A.
  obtain ⟨x : ℝ, h₀, h₁⟩ : ∃ x : ℝ, 0 < ‖x‖ ∧ ‖x‖ < 1 := by
    exact exists_real_norm_lt_one
  obtain ⟨A, hA₁, hA₂, hA₃⟩ : ∃ (A : Set V), (A ⊆ closedBall (0 : V) 1 ∧ A.Finite ∧ ∃ f : V → V, (∀ y ∈ closedBall (0 : V) 1, f y ∈ A ∧ dist y (f y) < ‖x‖)) := by
    -- Apply the finite cover choice lemma to obtain the finite set A and the function f.
    exact finite_cover_choice Hcomp h₀
  -- Prove that the closed unit ball is contained in the span of `A`.
  have hB : ∀ v ∈ closedBall (0 : V) 1, v ∈ (Submodule.span ℝ A : Submodule ℝ V) := by
    -- Define the sequence $u_iter$ and show that it converges to $v$.
    have h_seq_conv : ∀ v ∈ closedBall (0 : V) 1, Filter.Tendsto (fun n => v_approx x (hA₃.choose) v n) Filter.atTop (nhds v) := by
      intro v hv
      have h_seq_conv : Filter.Tendsto (fun n => v - v_approx x (hA₃.choose) v n) Filter.atTop (nhds 0) := by
        have h_seq_conv : ∀ n, ‖v - v_approx x (hA₃.choose) v n‖ ≤ ‖x‖^(n+1) := by
          -- Apply the geometric error bound lemma with the chosen x and the function f.
          apply geom_error_bound h₀ hA₃.choose;
          exacts [ hA₃.choose_spec, hv ];
        exact squeeze_zero_norm h_seq_conv ( tendsto_pow_atTop_nhds_zero_of_lt_one ( by positivity ) h₁ |> Filter.Tendsto.comp <| Filter.tendsto_add_atTop_nat 1 );
      simpa using h_seq_conv.neg.const_add v;
    -- Since the closed span of a finite set is closed, and the sequence converges to $v$, we have $v \in \overline{\operatorname{span}(A)}$.
    have h_span_closed : IsClosed ((Submodule.span ℝ A : Submodule ℝ V) : Set V) := by
      exact span_closed_of_finite hA₂;

    exact fun v hv => h_span_closed.mem_of_tendsto ( h_seq_conv v hv ) ( Filter.Eventually.of_forall fun n => sum_smul_image_mem_span A x hA₃.choose ( u_iter x hA₃.choose v ) ( fun i => hA₃.choose_spec ( u_iter x hA₃.choose v i ) ( by
      induction' i with i ih <;> simp_all +decide [ u_iter ];
      have := hA₃.choose_spec ( u_iter x hA₃.choose v i ) ( by simpa using ih );
      simp_all +decide [ norm_smul, dist_eq_norm ];
      rw [ inv_mul_le_iff₀ ] <;> linarith [ abs_pos.mpr h₀ ] ) |>.1 ) n );
  -- Prove that the span of `A` is the entire space `V`.
  have h_span : (Submodule.span ℝ A : Submodule ℝ V) = ⊤ := by
    exact ball_span_top hB;
  exact ⟨ hA₂.toFinset, by simpa ⟩
```