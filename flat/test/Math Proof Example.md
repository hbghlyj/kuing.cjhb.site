<style>
    .proof-container {
        border: 1px solid #dee2e6; /* Light gray border */
        border-radius: 0.25rem; /* Slightly rounded corners */
        padding: 1.5rem;
        margin-top: 2rem;
        background-color: #f8f9fa; /* Light background */
    }
    .proof-heading {
        border-bottom: 1px solid #dee2e6;
        padding-bottom: 0.5rem;
        margin-bottom: 1.5rem;
    }
    .proof-step {
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        /* border-bottom: 1px dashed #e9ecef; Optional dashed line between steps */
    }
    .statement-col, .reason-col {
        padding: 0.5rem;
    }
    .reason-col {
        font-style: italic;
        color: #6c757d; /* Muted color for reasons */
    }
</style>
<div class="container mt-5">
<p class="mb-4 text-center">Proof Example</h1>
<div class="proof-container shadow-sm">
<p class="proof-heading text-center">Proof of the Pythagorean Theorem</h2>
<div class="row fw-bold mb-3 text-primary">
<div class="col-md-6 statement-col">Statement</div>
<div class="col-md-6 reason-col">Reason</div>
</div>
<div class="row proof-step">
<div class="col-md-6 statement-col">
Consider a right-angled triangle with sides $a$, $b$, and hypotenuse $c$.
</div>
<div class="col-md-6 reason-col">
Given.
</div>
</div>
<div class="row proof-step">
<div class="col-md-6 statement-col">
Construct a square with side length $a+b$.
</div>
<div class="col-md-6 reason-col">
Geometric construction.
</div>
</div>
<div class="row proof-step">
<div class="col-md-6 statement-col">
Inside this square, arrange four copies of the triangle and a smaller square of side $c$.
</div>
<div class="col-md-6 reason-col">
Visual arrangement.
</div>
</div>
<div class="row proof-step">
<div class="col-md-6 statement-col">
The area of the large square is $(a+b)^2$.
</div>
<div class="col-md-6 reason-col">
Area of a square formula.
</div>
</div>
<div class="row proof-step">
<div class="col-md-6 statement-col">
The area of the four triangles is $4 \times \frac{1}{2}ab = 2ab$.
</div>
<div class="col-md-6 reason-col">
Area of a triangle formula.
</div>
</div>
<div class="row proof-step">
<div class="col-md-6 statement-col">
The area of the inner square is $c^2$.
</div>
<div class="col-md-6 reason-col">
Area of a square formula.
</div>
</div>
<div class="row proof-step">
<div class="col-md-6 statement-col">
The area of the large square is also the sum of the areas of the four triangles and the inner square:
$$ (a+b)^2 = 2ab + c^2 $$
</div>
<div class="col-md-6 reason-col">
Area decomposition principle.
</div>
</div>
<div class="row proof-step">
<div class="col-md-6 statement-col">
Expanding the left side:
$$ a^2 + 2ab + b^2 = 2ab + c^2 $$
</div>
<div class="col-md-6 reason-col">
Algebraic expansion.
</div>
</div>
<div class="row proof-step">
<div class="col-md-6 statement-col">
Subtract $2ab$ from both sides:
$$ a^2 + b^2 = c^2 $$
</div>
<div class="col-md-6 reason-col">
Algebraic manipulation.
</div>
</div>
</div>
</div>