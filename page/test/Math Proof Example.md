<style>
    .proof-heading {
        border-bottom: 1px solid #dee2e6;
        padding-bottom: 0.5rem;
        margin-bottom: 1.5rem;
    }
    .statement-col, .reason-col {
        padding: 0.5rem;
    }
    .reason-col {
        font-style: italic;
        color: #6c757d; /* Muted color for reasons */
    }
</style>
# Proof of the Pythagorean Theorem
<table>
<tr class="fw-bold mb-3 text-primary">
<td class="col-md-6 statement-col">Statement</td>
<td class="col-md-6 reason-col">Reason</td>
</tr>
<tr>
<td class="col-md-6 statement-col">
Consider a right-angled triangle with sides $a$, $b$, and hypotenuse $c$.
</td>
<td class="col-md-6 reason-col">
Given.
</td>
</tr>
<tr>
<td class="col-md-6 statement-col">
Construct a square with side length $a+b$.
</td>
<td class="col-md-6 reason-col">
Geometric construction.
</td>
</tr>
<tr>
<td class="col-md-6 statement-col">
Inside this square, arrange four copies of the triangle and a smaller square of side $c$.
</td>
<td class="col-md-6 reason-col">
Visual arrangement.
</td>
</tr>
<tr>
<td class="col-md-6 statement-col">
The area of the large square is $(a+b)^2$.
</td>
<td class="col-md-6 reason-col">
Area of a square formula.
</td>
</tr>
<tr>
<td class="col-md-6 statement-col">
The area of the four triangles is $4 \times \frac{1}{2}ab = 2ab$.
</td>
<td class="col-md-6 reason-col">
Area of a triangle formula.
</td>
</tr>
<tr>
<td class="col-md-6 statement-col">
The area of the inner square is $c^2$.
</td>
<td class="col-md-6 reason-col">
Area of a square formula.
</td>
</tr>
<tr>
<td class="col-md-6 statement-col">
The area of the large square is also the sum of the areas of the four triangles and the inner square:
$$ (a+b)^2 = 2ab + c^2 $$
</td>
<td class="col-md-6 reason-col">
Area decomposition principle.
</td>
</tr>
<tr>
<td class="col-md-6 statement-col">
Expanding the left side:
$$ a^2 + 2ab + b^2 = 2ab + c^2 $$
</td>
<td class="col-md-6 reason-col">
Algebraic expansion.
</td>
</tr>
<tr>
<td class="col-md-6 statement-col">
Subtract $2ab$ from both sides:
$$ a^2 + b^2 = c^2 $$
</td>
<td class="col-md-6 reason-col">
Algebraic manipulation.
</td>
</tr>
</table>