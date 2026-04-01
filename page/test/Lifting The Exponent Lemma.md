# Lifting The Exponent Lemma
\begin{proposition}
For any positive integer $n$ and distinct integers $a, b$, if $n \mid a^n - b^n$, then $n \mid \frac{a^n - b^n}{a - b}$.
\end{proposition}
The proof relies on the Lifting The Exponent Lemma (LTE) and careful analysis of prime power divisibility.
We formalized this by showing that for every prime $p$, the valuation $v_p(a^n - b^n)$ is at least $v_p(n) + v_p(a - b)$.
This was done by splitting into cases based on whether $p \mid a - b$ and whether $p \mid b$.
The main helper lemmas are `lte_lower_bound`, `easy_case_bound`, and `pow_dvd_of_dvd_pow_sub`.
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
Lower bound for the valuation of $a^n - b^n$ using LTE.
-/
lemma lte_lower_bound {n : ℕ} {a b : ℤ} (p : ℕ) (hp : Nat.Prime p) (hn : n ≠ 0) (hab : a ≠ b) (h_div : ↑p ∣ a - b) (h_ndiv : ¬ ↑p ∣ b) :
    (p : ℤ) ^ (padicValNat p n + padicValNat p (Int.natAbs (a - b))) ∣ a ^ n - b ^ n := by
      -- We use the Lifting The Exponent Lemma (LTE).
      have h_lte : ∀ k : ℕ, (↑p : ℤ) ^ (k + padicValNat p (Int.natAbs (a - b))) ∣ a ^ (p ^ k) - b ^ (p ^ k) := by
        intro k; induction' k with k ih;
        · haveI := Fact.mk hp; rw [ ← Int.natAbs_dvd_natAbs ] ; simp +decide [ ← ZMod.intCast_zmod_eq_zero_iff_dvd, padicValNat_dvd_iff ] ;
        · -- We can factor $a^{p^{k+1}} - b^{p^{k+1}}$ as $(a^{p^k} - b^{p^k})(a^{p^k(p-1)} + a^{p^k(p-2)}b + \cdots + b^{p^k(p-1)})$.
          have h_factor : a ^ p ^ (k + 1) - b ^ p ^ (k + 1) = (a ^ p ^ k - b ^ p ^ k) * (∑ i ∈ Finset.range p, a ^ (i * p ^ k) * b ^ ((p - 1 - i) * p ^ k)) := by
            have := geom_sum₂_mul ( a ^ p ^ k ) ( b ^ p ^ k ) p; ring_nf at *; aesop;
          -- We need to show that the second factor is divisible by $p$.
          have h_second_factor : (p : ℤ) ∣ ∑ i ∈ Finset.range p, a ^ (i * p ^ k) * b ^ ((p - 1 - i) * p ^ k) := by
            haveI := Fact.mk hp; simp_all +decide [ ← ZMod.intCast_zmod_eq_zero_iff_dvd, pow_mul' ] ;
            simp_all +decide [ sub_eq_iff_eq_add ];
            simp +decide [ ← pow_add, add_comm, ← Finset.sum_range_reflect ];
          convert mul_dvd_mul ih h_second_factor using 1 ; ring;
      -- Let $k$ be such that $n = p^k * m$ where $m$ is not divisible by $p$.
      obtain ⟨k, m, hm⟩ : ∃ k m : ℕ, n = p ^ k * m ∧ ¬(p : ℕ) ∣ m := by
        exact ⟨ Nat.factorization n p, n / p ^ Nat.factorization n p, by rw [ Nat.mul_div_cancel' ( Nat.ordProj_dvd _ _ ) ], Nat.not_dvd_ordCompl ( by aesop ) ( by aesop ) ⟩;
      simp_all +decide [ pow_mul, padicValNat.mul ];
      -- Since $p$ does not divide $m$, we have $v_p(m) = 0$.
      have h_vp_m : padicValNat p m = 0 := by
        simp_all +decide [ Nat.Prime.dvd_iff_not_coprime hp ];
      exact dvd_trans ( by simpa [ h_vp_m, add_comm, add_left_comm, add_assoc ] using h_lte k ) ( sub_dvd_pow_sub_pow _ _ _ )

/-
Lower bound for the valuation of $a^n - b^n$ when $p$ divides both $a$ and $b$.
-/
lemma easy_case_bound {n : ℕ} {a b : ℤ} (p : ℕ) (hp : Nat.Prime p) (hn : n ≠ 0) (hab : a ≠ b) (h_div_a : ↑p ∣ a) (h_div_b : ↑p ∣ b) :
    (p : ℤ) ^ (padicValNat p n + padicValNat p (Int.natAbs (a - b))) ∣ a ^ n - b ^ n := by
      -- Let $k = \min(v_p(a), v_p(b)) \ge 1$.
      obtain ⟨k, hk⟩ : ∃ k : ℕ, k ≥ 1 ∧ (p : ℤ) ^ k ∣ a ∧ (p : ℤ) ^ k ∣ b ∧ ¬((p : ℤ) ^ (k + 1) ∣ a) ∨ k ≥ 1 ∧ (p : ℤ) ^ k ∣ a ∧ (p : ℤ) ^ k ∣ b ∧ ¬((p : ℤ) ^ (k + 1) ∣ b) := by
        by_cases h_cases : ∀ k : ℕ, (p : ℤ) ^ k ∣ a ∧ (p : ℤ) ^ k ∣ b;
        · have h_contra : a = 0 ∧ b = 0 := by
            have h_contra : ∀ k : ℕ, (p : ℤ) ^ k ∣ a := by
              exact fun k => h_cases k |>.1
            have h_contra' : ∀ k : ℕ, (p : ℤ) ^ k ∣ b := by
              exact fun k => h_cases k |>.2;
            exact ⟨ by exact Classical.not_not.1 fun ha => absurd ( h_contra ( Nat.log p ( Int.natAbs a ) + 1 ) ) ( by exact fun h => absurd ( Int.natAbs_dvd_natAbs.2 h ) ( by simpa [ Int.natAbs_pow ] using Nat.not_dvd_of_pos_of_lt ( Int.natAbs_pos.2 ha ) ( Nat.lt_pow_succ_log_self hp.one_lt _ ) ) ), by exact Classical.not_not.1 fun hb => absurd ( h_contra' ( Nat.log p ( Int.natAbs b ) + 1 ) ) ( by exact fun h => absurd ( Int.natAbs_dvd_natAbs.2 h ) ( by simpa [ Int.natAbs_pow ] using Nat.not_dvd_of_pos_of_lt ( Int.natAbs_pos.2 hb ) ( Nat.lt_pow_succ_log_self hp.one_lt _ ) ) ) ⟩;
          aesop;
        · -- Let $k$ be the largest integer such that $p^k$ divides both $a$ and $b$.
          obtain ⟨k, hk⟩ : ∃ k : ℕ, (p : ℤ) ^ k ∣ a ∧ (p : ℤ) ^ k ∣ b ∧ ¬((p : ℤ) ^ (k + 1) ∣ a) ∨ (p : ℤ) ^ k ∣ a ∧ (p : ℤ) ^ k ∣ b ∧ ¬((p : ℤ) ^ (k + 1) ∣ b) := by
            contrapose! h_cases;
            exact fun k => Nat.recOn k ( by norm_num ) fun k ih => by have := h_cases k; tauto;
          use k;
          rcases k with ( _ | k ) <;> simp_all +decide;
      -- Then $a = p^k u, b = p^k v$ where $p \nmid u$ or $p \nmid v$.
      obtain ⟨u, v, hu, hv⟩ : ∃ u v : ℤ, a = (p : ℤ)^k * u ∧ b = (p : ℤ)^k * v ∧ (¬((p : ℤ) ∣ u) ∨ ¬((p : ℤ) ∣ v)) := by
        rcases hk with ( ⟨ hk₁, hk₂, hk₃, hk₄ ⟩ | ⟨ hk₁, hk₂, hk₃, hk₄ ⟩ ) <;> obtain ⟨ u, rfl ⟩ := hk₂ <;> obtain ⟨ v, rfl ⟩ := hk₃ <;> use u, v <;> simp_all +decide [ pow_add, mul_dvd_mul_iff_left ];
      by_cases huv : u = v <;> simp_all +decide [ mul_pow, ← mul_sub ]-- $nk + v_p(u^n - v^n) \ge v_p(n) + v_p(p^k(u-v)) = v_p(n) + k + v_p(u-v)$.
      -- $(n-1)k + v_p(u^n - v^n) \ge v_p(n) + v_p(u-v)$.
      have h_val : (p : ℤ) ^ ((padicValNat p n) + (padicValNat p (Int.natAbs (u - v))) + k) ∣ (p : ℤ) ^ (n * k) * (u ^ n - v ^ n) := by
        -- If $p \nmid u$ and $p \nmid v$, and $p \mid u-v$, we use LTE: $v_p(u^n - v^n) \ge v_p(n) + v_p(u-v)$.
        by_cases h_lte : (p : ℤ) ∣ u - v ∧ ¬(p : ℤ) ∣ u ∧ ¬(p : ℤ) ∣ v;
        · have h_lte : (p : ℤ) ^ (padicValNat p n + padicValNat p (Int.natAbs (u - v))) ∣ (u ^ n - v ^ n) := by
            convert lte_lower_bound p hp hn huv h_lte.1 h_lte.2.2 using 1;
          rw [ pow_add ];
          rw [ mul_comm ] ; gcongr;
          nlinarith [ Nat.pos_of_ne_zero hn ];
        · -- If $p \nmid u$ and $p \nmid v$, and $p \nmid u-v$, then $v_p(u-v)=0$.
          by_cases h_not_div : ¬(p : ℤ) ∣ u - v;
          · -- Since $p \nmid u - v$, we have $v_p(u - v) = 0$.
            have h_val_zero : padicValNat p (Int.natAbs (u - v)) = 0 := by
              simp_all +decide [ padicValNat.eq_zero_of_not_dvd, ← Int.natCast_dvd_natCast ];
            -- Since $p \nmid u - v$, we have $v_p(u^n - v^n) \ge 0$.
            have h_val_nonneg : padicValNat p n ≤ (n - 1) * k := by
              have h_val_nonneg : padicValNat p n ≤ Nat.log p n := by
                exact?;
              rcases n with ( _ | _ | n ) <;> simp_all +decide;
              refine le_trans h_val_nonneg ?_;
              refine' Nat.le_trans ( Nat.log_mono_right <| show n + 1 + 1 ≤ p ^ ( n + 1 ) from _ ) _;
              · exact Nat.recOn n ( by norm_num; linarith [ hp.two_le ] ) fun n ihn => by rw [ pow_succ' ] ; nlinarith [ hp.two_le ] ;
              · rw [ Nat.log_pow hp.one_lt ] ; nlinarith [ show k > 0 from by omega ];
            exact dvd_mul_of_dvd_left ( pow_dvd_pow _ <| by nlinarith [ Nat.sub_add_cancel ( Nat.one_le_iff_ne_zero.mpr hn ) ] ) _;
          · cases hv.2 <;> simp_all +decide [ dvd_sub_right, dvd_sub_left ];
      convert h_val using 1 <;> norm_num [ Int.natAbs_mul, Int.natAbs_pow ] ; ring;
      · haveI := Fact.mk hp; simp +decide [ ← pow_add, padicValNat.mul, hp.ne_zero, hn, huv ] ; ring;
        rw [ padicValNat.mul ] <;> norm_num [ hp.ne_zero, hn, huv ] ; ring;
        exact sub_ne_zero_of_ne huv;
      · exact Or.inl ( by ring )

/-
For any prime $p$, the valuation of $a^n - b^n$ is at least $v_p(n) + v_p(a - b)$.
-/
lemma pow_dvd_of_dvd_pow_sub {n : ℕ} {a b : ℤ} (p : ℕ) (hp : Nat.Prime p) (hn : n ≠ 0) (hab : a ≠ b) (h : ↑n ∣ a ^ n - b ^ n) :
    (p : ℤ) ^ (padicValNat p n + padicValNat p (Int.natAbs (a - b))) ∣ a ^ n - b ^ n := by
      -- We split into cases based on whether $p \mid a - b$.
      by_cases h_div : (p : ℤ) ∣ (a - b);
      · by_cases h_div_b : (p : ℤ) ∣ b;
        · convert easy_case_bound p hp hn hab _ _ using 1;
          · simpa using dvd_add h_div h_div_b;
          · assumption;
        · convert lte_lower_bound p hp hn hab h_div h_div_b using 1;
      · -- Since $p$ does not divide $a - b$, we have $v_p(a - b) = 0$.
        have h_vp_zero : padicValNat p (Int.natAbs (a - b)) = 0 := by
          simp_all +decide [ ← Int.natCast_dvd_natCast, padicValNat.eq_zero_of_not_dvd ];
        have h_div_n : (p : ℤ) ^ (padicValNat p n) ∣ n := by
          norm_cast;
          exact?;
        exact dvd_trans ( by simpa [ h_vp_zero ] using h_div_n ) h

/-
For any positive integer $n$ and distinct integers $a, b$, if $n \mid a^n - b^n$, then $n \mid \frac{a^n - b^n}{a - b}$.
-/
theorem divides_div_sub_of_div_pow_sub {n : ℕ} {a b : ℤ} (hn : 0 < n) (hab : a ≠ b) (h : ↑n ∣ a ^ n - b ^ n) :
    ↑n ∣ (a ^ n - b ^ n) / (a - b) := by
      by_contra h_not_div;
      -- Since $a^n - b^n$ is divisible by $n$, we have that for every prime $p$, $v_p(n) \le v_p(a^n - b^n) - v_p(a - b)$.
      have h_val : ∀ p : ℕ, Nat.Prime p → padicValNat p n ≤ padicValNat p (Int.natAbs (a ^ n - b ^ n)) - padicValNat p (Int.natAbs (a - b)) := by
        intro p pp
        have h_div : (p : ℤ) ^ (padicValNat p n + padicValNat p (Int.natAbs (a - b))) ∣ a ^ n - b ^ n := by
          convert pow_dvd_of_dvd_pow_sub p pp hn.ne' hab h using 1;
        have h_val : padicValNat p (Int.natAbs (a ^ n - b ^ n)) ≥ padicValNat p n + padicValNat p (Int.natAbs (a - b)) := by
          obtain ⟨ k, hk ⟩ := h_div;
          by_cases hk0 : k = 0 <;> simp_all +decide [ Int.natAbs_mul, padicValNat.mul ];
        exact le_tsub_of_add_le_right h_val;
      -- Therefore, $n \mid \frac{a^n - b^n}{a - b}$.
      have h_div : (n : ℤ) ∣ (a ^ n - b ^ n) / (a - b) := by
        have h_div_abs : (n : ℕ) ∣ Int.natAbs ((a ^ n - b ^ n) / (a - b)) := by
          rw [ ← Nat.factorization_le_iff_dvd ];
          · intro p; by_cases hp : Nat.Prime p <;> simp_all +decide [ Nat.factorization ] ;
            convert h_val p hp using 1;
            haveI := Fact.mk hp; rw [ Int.natAbs_ediv_of_dvd ];
            · rw [ padicValNat.div_of_dvd ];
              exact Int.natAbs_dvd_natAbs.mpr ( sub_dvd_pow_sub_pow a b n );
            · exact sub_dvd_pow_sub_pow a b n;
          · positivity;
          · simp_all +decide [ sub_eq_iff_eq_add ];
            exact fun h => h_not_div <| h.symm ▸ dvd_zero _
        exact?;
      contradiction
```