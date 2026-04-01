# Cubic polynomials are point symmetric
https://www.codewars.com/kata/63bbf3289a074d004d054aa6
```
import Mathlib

lemma cubic_symmetric (a b c d : ℝ) (ha : a ≠ 0) {f : ℝ → ℝ} (hf : f = fun x ↦ (a * x^3 + b * x^2 + c * x + d)) : ∃ (x y : ℝ), ∀ (h : ℝ), f (x + h) - y = -(f (x - h) - y) := by
  let x:= -b/(3*a)
  use x,f x
  intro h
  rw [hf]
  grind
```