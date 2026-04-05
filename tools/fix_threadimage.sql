USE ultrax;
ALTER TABLE pre_forum_threadimage DROP INDEX tid;
ALTER TABLE pre_forum_threadimage ADD UNIQUE KEY tid (tid);

-- remove threadimage entries that have no matching attachment
DELETE ti FROM pre_forum_threadimage ti
LEFT JOIN (
    SELECT tid FROM pre_forum_attachment_0 WHERE isimage IN (1,-1)
    UNION SELECT tid FROM pre_forum_attachment_1 WHERE isimage IN (1,-1)
    UNION SELECT tid FROM pre_forum_attachment_2 WHERE isimage IN (1,-1)
    UNION SELECT tid FROM pre_forum_attachment_3 WHERE isimage IN (1,-1)
    UNION SELECT tid FROM pre_forum_attachment_4 WHERE isimage IN (1,-1)
    UNION SELECT tid FROM pre_forum_attachment_5 WHERE isimage IN (1,-1)
    UNION SELECT tid FROM pre_forum_attachment_6 WHERE isimage IN (1,-1)
    UNION SELECT tid FROM pre_forum_attachment_7 WHERE isimage IN (1,-1)
    UNION SELECT tid FROM pre_forum_attachment_8 WHERE isimage IN (1,-1)
    UNION SELECT tid FROM pre_forum_attachment_9 WHERE isimage IN (1,-1)
) a ON ti.tid=a.tid
WHERE a.tid IS NULL;

-- update existing records based on latest attachment
-- table 0
UPDATE pre_forum_threadimage ti
JOIN (
    SELECT a.tid, a.attachment, a.remote
    FROM pre_forum_attachment_0 a
    JOIN (
        SELECT tid, MAX(aid) AS aid
        FROM pre_forum_attachment_0
        WHERE isimage IN (1,-1)
        GROUP BY tid
    ) m ON a.tid=m.tid AND a.aid=m.aid
    WHERE a.isimage IN (1,-1)
) u ON ti.tid=u.tid
SET ti.attachment=u.attachment, ti.remote=u.remote
WHERE ti.tid%10=0;
INSERT INTO pre_forum_threadimage(tid,attachment,remote)
SELECT u.tid, u.attachment, u.remote
FROM (
    SELECT a.tid, a.attachment, a.remote
    FROM pre_forum_attachment_0 a
    JOIN (
        SELECT tid, MAX(aid) AS aid
        FROM pre_forum_attachment_0
        WHERE isimage IN (1,-1)
        GROUP BY tid
    ) m ON a.tid=m.tid AND a.aid=m.aid
    WHERE a.isimage IN (1,-1)
) u
LEFT JOIN pre_forum_threadimage ti ON ti.tid=u.tid
WHERE ti.tid IS NULL AND u.tid%10=0;

-- table 1
UPDATE pre_forum_threadimage ti
JOIN (
    SELECT a.tid, a.attachment, a.remote
    FROM pre_forum_attachment_1 a
    JOIN (
        SELECT tid, MAX(aid) AS aid
        FROM pre_forum_attachment_1
        WHERE isimage IN (1,-1)
        GROUP BY tid
    ) m ON a.tid=m.tid AND a.aid=m.aid
    WHERE a.isimage IN (1,-1)
) u ON ti.tid=u.tid
SET ti.attachment=u.attachment, ti.remote=u.remote
WHERE ti.tid%10=1;
INSERT INTO pre_forum_threadimage(tid,attachment,remote)
SELECT u.tid, u.attachment, u.remote
FROM (
    SELECT a.tid, a.attachment, a.remote
    FROM pre_forum_attachment_1 a
    JOIN (
        SELECT tid, MAX(aid) AS aid
        FROM pre_forum_attachment_1
        WHERE isimage IN (1,-1)
        GROUP BY tid
    ) m ON a.tid=m.tid AND a.aid=m.aid
    WHERE a.isimage IN (1,-1)
) u
LEFT JOIN pre_forum_threadimage ti ON ti.tid=u.tid
WHERE ti.tid IS NULL AND u.tid%10=1;

UPDATE pre_forum_threadimage ti
JOIN (
    SELECT a.tid, a.attachment, a.remote
    FROM pre_forum_attachment_2 a
    JOIN (
        SELECT tid, MAX(aid) AS aid
        FROM pre_forum_attachment_2
        WHERE isimage IN (1,-1)
        GROUP BY tid
    ) m ON a.tid=m.tid AND a.aid=m.aid
    WHERE a.isimage IN (1,-1)
) u ON ti.tid=u.tid
SET ti.attachment=u.attachment, ti.remote=u.remote
WHERE ti.tid%10=2;
INSERT INTO pre_forum_threadimage(tid,attachment,remote)
SELECT u.tid, u.attachment, u.remote
FROM (
    SELECT a.tid, a.attachment, a.remote
    FROM pre_forum_attachment_2 a
    JOIN (
        SELECT tid, MAX(aid) AS aid
        FROM pre_forum_attachment_2
        WHERE isimage IN (1,-1)
        GROUP BY tid
    ) m ON a.tid=m.tid AND a.aid=m.aid
    WHERE a.isimage IN (1,-1)
) u
LEFT JOIN pre_forum_threadimage ti ON ti.tid=u.tid
WHERE ti.tid IS NULL AND u.tid%10=2;

UPDATE pre_forum_threadimage ti
JOIN (
    SELECT a.tid, a.attachment, a.remote
    FROM pre_forum_attachment_3 a
    JOIN (
        SELECT tid, MAX(aid) AS aid
        FROM pre_forum_attachment_3
        WHERE isimage IN (1,-1)
        GROUP BY tid
    ) m ON a.tid=m.tid AND a.aid=m.aid
    WHERE a.isimage IN (1,-1)
) u ON ti.tid=u.tid
SET ti.attachment=u.attachment, ti.remote=u.remote
WHERE ti.tid%10=3;
INSERT INTO pre_forum_threadimage(tid,attachment,remote)
SELECT u.tid, u.attachment, u.remote
FROM (
    SELECT a.tid, a.attachment, a.remote
    FROM pre_forum_attachment_3 a
    JOIN (
        SELECT tid, MAX(aid) AS aid
        FROM pre_forum_attachment_3
        WHERE isimage IN (1,-1)
        GROUP BY tid
    ) m ON a.tid=m.tid AND a.aid=m.aid
    WHERE a.isimage IN (1,-1)
) u
LEFT JOIN pre_forum_threadimage ti ON ti.tid=u.tid
WHERE ti.tid IS NULL AND u.tid%10=3;

UPDATE pre_forum_threadimage ti
JOIN (
    SELECT a.tid, a.attachment, a.remote
    FROM pre_forum_attachment_4 a
    JOIN (
        SELECT tid, MAX(aid) AS aid
        FROM pre_forum_attachment_4
        WHERE isimage IN (1,-1)
        GROUP BY tid
    ) m ON a.tid=m.tid AND a.aid=m.aid
    WHERE a.isimage IN (1,-1)
) u ON ti.tid=u.tid
SET ti.attachment=u.attachment, ti.remote=u.remote
WHERE ti.tid%10=4;
INSERT INTO pre_forum_threadimage(tid,attachment,remote)
SELECT u.tid, u.attachment, u.remote
FROM (
    SELECT a.tid, a.attachment, a.remote
    FROM pre_forum_attachment_4 a
    JOIN (
        SELECT tid, MAX(aid) AS aid
        FROM pre_forum_attachment_4
        WHERE isimage IN (1,-1)
        GROUP BY tid
    ) m ON a.tid=m.tid AND a.aid=m.aid
    WHERE a.isimage IN (1,-1)
) u
LEFT JOIN pre_forum_threadimage ti ON ti.tid=u.tid
WHERE ti.tid IS NULL AND u.tid%10=4;

UPDATE pre_forum_threadimage ti
JOIN (
    SELECT a.tid, a.attachment, a.remote
    FROM pre_forum_attachment_5 a
    JOIN (
        SELECT tid, MAX(aid) AS aid
        FROM pre_forum_attachment_5
        WHERE isimage IN (1,-1)
        GROUP BY tid
    ) m ON a.tid=m.tid AND a.aid=m.aid
    WHERE a.isimage IN (1,-1)
) u ON ti.tid=u.tid
SET ti.attachment=u.attachment, ti.remote=u.remote
WHERE ti.tid%10=5;
INSERT INTO pre_forum_threadimage(tid,attachment,remote)
SELECT u.tid, u.attachment, u.remote
FROM (
    SELECT a.tid, a.attachment, a.remote
    FROM pre_forum_attachment_5 a
    JOIN (
        SELECT tid, MAX(aid) AS aid
        FROM pre_forum_attachment_5
        WHERE isimage IN (1,-1)
        GROUP BY tid
    ) m ON a.tid=m.tid AND a.aid=m.aid
    WHERE a.isimage IN (1,-1)
) u
LEFT JOIN pre_forum_threadimage ti ON ti.tid=u.tid
WHERE ti.tid IS NULL AND u.tid%10=5;

UPDATE pre_forum_threadimage ti
JOIN (
    SELECT a.tid, a.attachment, a.remote
    FROM pre_forum_attachment_6 a
    JOIN (
        SELECT tid, MAX(aid) AS aid
        FROM pre_forum_attachment_6
        WHERE isimage IN (1,-1)
        GROUP BY tid
    ) m ON a.tid=m.tid AND a.aid=m.aid
    WHERE a.isimage IN (1,-1)
) u ON ti.tid=u.tid
SET ti.attachment=u.attachment, ti.remote=u.remote
WHERE ti.tid%10=6;
INSERT INTO pre_forum_threadimage(tid,attachment,remote)
SELECT u.tid, u.attachment, u.remote
FROM (
    SELECT a.tid, a.attachment, a.remote
    FROM pre_forum_attachment_6 a
    JOIN (
        SELECT tid, MAX(aid) AS aid
        FROM pre_forum_attachment_6
        WHERE isimage IN (1,-1)
        GROUP BY tid
    ) m ON a.tid=m.tid AND a.aid=m.aid
    WHERE a.isimage IN (1,-1)
) u
LEFT JOIN pre_forum_threadimage ti ON ti.tid=u.tid
WHERE ti.tid IS NULL AND u.tid%10=6;

UPDATE pre_forum_threadimage ti
JOIN (
    SELECT a.tid, a.attachment, a.remote
    FROM pre_forum_attachment_7 a
    JOIN (
        SELECT tid, MAX(aid) AS aid
        FROM pre_forum_attachment_7
        WHERE isimage IN (1,-1)
        GROUP BY tid
    ) m ON a.tid=m.tid AND a.aid=m.aid
    WHERE a.isimage IN (1,-1)
) u ON ti.tid=u.tid
SET ti.attachment=u.attachment, ti.remote=u.remote
WHERE ti.tid%10=7;
INSERT INTO pre_forum_threadimage(tid,attachment,remote)
SELECT u.tid, u.attachment, u.remote
FROM (
    SELECT a.tid, a.attachment, a.remote
    FROM pre_forum_attachment_7 a
    JOIN (
        SELECT tid, MAX(aid) AS aid
        FROM pre_forum_attachment_7
        WHERE isimage IN (1,-1)
        GROUP BY tid
    ) m ON a.tid=m.tid AND a.aid=m.aid
    WHERE a.isimage IN (1,-1)
) u
LEFT JOIN pre_forum_threadimage ti ON ti.tid=u.tid
WHERE ti.tid IS NULL AND u.tid%10=7;

UPDATE pre_forum_threadimage ti
JOIN (
    SELECT a.tid, a.attachment, a.remote
    FROM pre_forum_attachment_8 a
    JOIN (
        SELECT tid, MAX(aid) AS aid
        FROM pre_forum_attachment_8
        WHERE isimage IN (1,-1)
        GROUP BY tid
    ) m ON a.tid=m.tid AND a.aid=m.aid
    WHERE a.isimage IN (1,-1)
) u ON ti.tid=u.tid
SET ti.attachment=u.attachment, ti.remote=u.remote
WHERE ti.tid%10=8;
INSERT INTO pre_forum_threadimage(tid,attachment,remote)
SELECT u.tid, u.attachment, u.remote
FROM (
    SELECT a.tid, a.attachment, a.remote
    FROM pre_forum_attachment_8 a
    JOIN (
        SELECT tid, MAX(aid) AS aid
        FROM pre_forum_attachment_8
        WHERE isimage IN (1,-1)
        GROUP BY tid
    ) m ON a.tid=m.tid AND a.aid=m.aid
    WHERE a.isimage IN (1,-1)
) u
LEFT JOIN pre_forum_threadimage ti ON ti.tid=u.tid
WHERE ti.tid IS NULL AND u.tid%10=8;

UPDATE pre_forum_threadimage ti
JOIN (
    SELECT a.tid, a.attachment, a.remote
    FROM pre_forum_attachment_9 a
    JOIN (
        SELECT tid, MAX(aid) AS aid
        FROM pre_forum_attachment_9
        WHERE isimage IN (1,-1)
        GROUP BY tid
    ) m ON a.tid=m.tid AND a.aid=m.aid
    WHERE a.isimage IN (1,-1)
) u ON ti.tid=u.tid
SET ti.attachment=u.attachment, ti.remote=u.remote
WHERE ti.tid%10=9;
INSERT INTO pre_forum_threadimage(tid,attachment,remote)
SELECT u.tid, u.attachment, u.remote
FROM (
    SELECT a.tid, a.attachment, a.remote
    FROM pre_forum_attachment_9 a
    JOIN (
        SELECT tid, MAX(aid) AS aid
        FROM pre_forum_attachment_9
        WHERE isimage IN (1,-1)
        GROUP BY tid
    ) m ON a.tid=m.tid AND a.aid=m.aid
    WHERE a.isimage IN (1,-1)
) u
LEFT JOIN pre_forum_threadimage ti ON ti.tid=u.tid
WHERE ti.tid IS NULL AND u.tid%10=9;

