# A property of integers not prime powers
We proved that if an integer $n > 1$ is not a power of a prime, then there exists an integer $x$ such that $n \mid (x^2 - x)$ but $n \nmid x$ and $n \nmid (x - 1)$. This $x$ is a non-trivial idempotent modulo $n$. The proof relies on decomposing $n$ into two coprime factors $a, b > 1$ and using the Chinese Remainder Theorem to find $x$ such that $x \equiv 1 \pmod a$ and $x \equiv 0 \pmod b$.
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
If an integer $n > 1$ is not a power of a prime, then there exists an integer $x$ such that $n \mid (x^2 - x)$ but $n \nmid x$ and $n \nmid (x - 1)$.
-/
theorem exists_nontrivial_idempotent_of_not_isPrimePow (n : ℕ) (h1 : 1 < n) (h2 : ¬ IsPrimePow n) : ∃ x : ℕ, n ∣ (x^2 - x) ∧ ¬ n ∣ x ∧ ¬ n ∣ (x - 1) := by
  -- If $n$ is not a power of a prime, then $n$ can be written as a product of two coprime integers greater than 1.
  obtain ⟨a, b, ha, hb, hab⟩ : ∃ a b : ℕ, 1 < a ∧ 1 < b ∧ a.gcd b = 1 ∧ a * b = n := by
    -- Since $n$ is not a power of a prime, it must have at least two distinct prime factors.
    obtain ⟨p, hp⟩ : ∃ p : ℕ, Nat.Prime p ∧ p ∣ n := by
      exact Nat.exists_prime_and_dvd h1.ne';
    -- Since $p$ divides $n$, we can write $n = p^k * m$ where $k \geq 1$ and $m$ is not divisible by $p$.
    obtain ⟨k, m, hk, hm⟩ : ∃ k m : ℕ, 1 ≤ k ∧ ¬p ∣ m ∧ n = p^k * m := by
      exact ⟨ Nat.factorization n p, n / p ^ Nat.factorization n p, Nat.succ_le_of_lt ( Nat.pos_of_ne_zero ( Finsupp.mem_support_iff.mp ( by aesop ) ) ), Nat.not_dvd_ordCompl ( by aesop ) ( by aesop ), by rw [ Nat.mul_div_cancel' ( Nat.ordProj_dvd _ _ ) ] ⟩;
    -- If $m = 1$, then $n = p^k$, contradicting the assumption that $n$ is not a power of a prime.
    by_cases hm1 : m = 1;
    · exact False.elim <| h2 <| by rw [ hm.2, hm1, mul_one ] ; exact hp.1.isPrimePow.pow <| by linarith;
    · exact ⟨ p ^ k, m, one_lt_pow₀ hp.1.one_lt ( by linarith ), lt_of_le_of_ne ( Nat.pos_of_ne_zero ( by aesop_cat ) ) ( Ne.symm hm1 ), Nat.Coprime.pow_left _ ( hp.1.coprime_iff_not_dvd.mpr hm.1 ), hm.2.symm ⟩;
  -- By the Chinese Remainder Theorem, there exists an integer $x$ such that $x \equiv 1 \pmod{a}$ and $x \equiv 0 \pmod{b}$.
  obtain ⟨x, hx⟩ : ∃ x : ℕ, x ≡ 1 [MOD a] ∧ x ≡ 0 [MOD b] := by
    have := Nat.chineseRemainder hab.1;
    exact ⟨ _, this 1 0 |>.2 ⟩;
  -- Then $x(x-1) \equiv 1 \cdot 0 = 0 \pmod{a}$ and $x(x-1) \equiv 0 \cdot (-1) = 0 \pmod{b}$.
  have h_mul : n ∣ (x^2 - x) := by
    convert Nat.Coprime.mul_dvd_of_dvd_of_dvd hab.1 _ _ using 1;
    · linarith;
    · rw [ ← Nat.modEq_zero_iff_dvd ] ; simp_all +decide [ ← ZMod.natCast_eq_natCast_iff, Nat.cast_sub ( show x ^ 2 ≥ x from Nat.le_self_pow ( by linarith ) _ ) ] ;
    · exact Nat.dvd_sub' ( dvd_pow ( Nat.dvd_of_mod_eq_zero hx.2 ) two_ne_zero ) ( Nat.dvd_of_mod_eq_zero hx.2 );
  refine' ⟨ x, h_mul, _, _ ⟩ <;> intro H <;> rcases x with ( _ | _ | x ) <;> simp_all +decide [ Nat.ModEq, Nat.dvd_iff_mod_eq_zero ] ;
  · rcases a with ( _ | _ | a ) <;> rcases b with ( _ | _ | b ) <;> simp_all +decide [ Nat.mod_eq_of_lt ];
    have := Nat.dvd_of_mod_eq_zero H; rcases this with ⟨ k, hk ⟩ ; simp_all +decide [ Nat.add_mod, Nat.mul_mod ] ;
    norm_num [ ← hab.2, Nat.mul_mod ] at hx;
  · have := Nat.dvd_of_mod_eq_zero H; rcases this with ⟨ k, hk ⟩ ; simp_all +decide [ Nat.add_mod, Nat.pow_mod, Nat.mul_mod ] ;
    rcases a with ( _ | _ | a ) <;> rcases b with ( _ | _ | b ) <;> simp_all +decide [ Nat.mod_eq_of_lt ];
    norm_num [ ← hab.2, Nat.add_mod, Nat.mul_mod ] at hx
```