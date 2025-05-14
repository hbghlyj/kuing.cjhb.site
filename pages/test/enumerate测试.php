<?php

use DocPHT\Lib\DocPHT;

$_SESSION['page_id'] = '67e3d058d6608';

$html = new DocPHT(['theorem测试']);
$values = [
$html->title('theorem测试','theorem测试'), 
$html->markdown('\\begin{definition}
A fibration is a mapping between two topological spaces that has the homotopy lifting property for every space $X$.
\\end{definition}
\\begin{theorem}
Let $f$ be a function whose derivative exists in every point, then $f$ is a continuous function.
\\end{theorem}
\\begin{proposition}
Let $f$ be a function whose derivative exists in every point, then $f$ is a continuous function.
\\end{proposition}
\\begin{lemma}
Given two line segments whose lengths are $a$ and $b$ respectively there is a 
real number $r$ such that $b=ra$.
\\end{lemma}
\\begin{remark}
This statement is true, I guess.
\\end{remark}
\\begin{proof}
\\end{proof}
\\begin{solution}
\\end{solution}
\\begin{corollary}
There\'s no right rectangle whose sides measure 3cm, 4cm, and 6cm.
\\end{corollary}
\\begin{example}
This is an example of an example.
\\end{example}
\\begin{exercise}
This is an exercise that involves an equation,
\\begin{equation}
    x^2 + y^2 = z^2.
\\end{equation}
\\end{exercise}
\\begin{problem}
Given $n$ coupons, how many coupons do you expect you need to draw with replacement before having drawn each coupon at least once?
\\end{problem}'), 
$html->addButton(),
];