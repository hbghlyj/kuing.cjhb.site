USE ultrax;

-- remove threadimage entries that have no matching attachment
DELETE ti FROM pre_forum_threadimage ti
LEFT JOIN (
    SELECT tid FROM pre_forum_attachment_0
    UNION SELECT tid FROM pre_forum_attachment_1
    UNION SELECT tid FROM pre_forum_attachment_2
    UNION SELECT tid FROM pre_forum_attachment_3
    UNION SELECT tid FROM pre_forum_attachment_4
    UNION SELECT tid FROM pre_forum_attachment_5
    UNION SELECT tid FROM pre_forum_attachment_6
    UNION SELECT tid FROM pre_forum_attachment_7
    UNION SELECT tid FROM pre_forum_attachment_8
    UNION SELECT tid FROM pre_forum_attachment_9
) a ON ti.tid=a.tid
WHERE a.tid IS NULL;

-- update existing records based on latest attachment
-- table 0
UPDATE pre_forum_threadimage ti JOIN (SELECT a.tid, a.attachment, a.remote FROM pre_forum_attachment_0 a JOIN (SELECT tid, MAX(aid) AS aid FROM pre_forum_attachment_0 GROUP BY tid) m ON a.tid=m.tid AND a.aid=m.aid) u ON ti.tid=u.tid SET ti.attachment=u.attachment, ti.remote=u.remote WHERE ti.tid%10=0;

-- table 1
UPDATE pre_forum_threadimage ti JOIN (SELECT a.tid, a.attachment, a.remote FROM pre_forum_attachment_1 a JOIN (SELECT tid, MAX(aid) AS aid FROM pre_forum_attachment_1 GROUP BY tid) m ON a.tid=m.tid AND a.aid=m.aid) u ON ti.tid=u.tid SET ti.attachment=u.attachment, ti.remote=u.remote WHERE ti.tid%10=1;

-- table 2
UPDATE pre_forum_threadimage ti JOIN (SELECT a.tid, a.attachment, a.remote FROM pre_forum_attachment_2 a JOIN (SELECT tid, MAX(aid) AS aid FROM pre_forum_attachment_2 GROUP BY tid) m ON a.tid=m.tid AND a.aid=m.aid) u ON ti.tid=u.tid SET ti.attachment=u.attachment, ti.remote=u.remote WHERE ti.tid%10=2;

-- table 3
UPDATE pre_forum_threadimage ti JOIN (SELECT a.tid, a.attachment, a.remote FROM pre_forum_attachment_3 a JOIN (SELECT tid, MAX(aid) AS aid FROM pre_forum_attachment_3 GROUP BY tid) m ON a.tid=m.tid AND a.aid=m.aid) u ON ti.tid=u.tid SET ti.attachment=u.attachment, ti.remote=u.remote WHERE ti.tid%10=3;

-- table 4
UPDATE pre_forum_threadimage ti JOIN (SELECT a.tid, a.attachment, a.remote FROM pre_forum_attachment_4 a JOIN (SELECT tid, MAX(aid) AS aid FROM pre_forum_attachment_4 GROUP BY tid) m ON a.tid=m.tid AND a.aid=m.aid) u ON ti.tid=u.tid SET ti.attachment=u.attachment, ti.remote=u.remote WHERE ti.tid%10=4;

-- table 5
UPDATE pre_forum_threadimage ti JOIN (SELECT a.tid, a.attachment, a.remote FROM pre_forum_attachment_5 a JOIN (SELECT tid, MAX(aid) AS aid FROM pre_forum_attachment_5 GROUP BY tid) m ON a.tid=m.tid AND a.aid=m.aid) u ON ti.tid=u.tid SET ti.attachment=u.attachment, ti.remote=u.remote WHERE ti.tid%10=5;

-- table 6
UPDATE pre_forum_threadimage ti JOIN (SELECT a.tid, a.attachment, a.remote FROM pre_forum_attachment_6 a JOIN (SELECT tid, MAX(aid) AS aid FROM pre_forum_attachment_6 GROUP BY tid) m ON a.tid=m.tid AND a.aid=m.aid) u ON ti.tid=u.tid SET ti.attachment=u.attachment, ti.remote=u.remote WHERE ti.tid%10=6;

-- table 7
UPDATE pre_forum_threadimage ti JOIN (SELECT a.tid, a.attachment, a.remote FROM pre_forum_attachment_7 a JOIN (SELECT tid, MAX(aid) AS aid FROM pre_forum_attachment_7 GROUP BY tid) m ON a.tid=m.tid AND a.aid=m.aid) u ON ti.tid=u.tid SET ti.attachment=u.attachment, ti.remote=u.remote WHERE ti.tid%10=7;

-- table 8
UPDATE pre_forum_threadimage ti JOIN (SELECT a.tid, a.attachment, a.remote FROM pre_forum_attachment_8 a JOIN (SELECT tid, MAX(aid) AS aid FROM pre_forum_attachment_8 GROUP BY tid) m ON a.tid=m.tid AND a.aid=m.aid) u ON ti.tid=u.tid SET ti.attachment=u.attachment, ti.remote=u.remote WHERE ti.tid%10=8;

-- table 9
UPDATE pre_forum_threadimage ti JOIN (SELECT a.tid, a.attachment, a.remote FROM pre_forum_attachment_9 a JOIN (SELECT tid, MAX(aid) AS aid FROM pre_forum_attachment_9 GROUP BY tid) m ON a.tid=m.tid AND a.aid=m.aid) u ON ti.tid=u.tid SET ti.attachment=u.attachment, ti.remote=u.remote WHERE ti.tid%10=9;

