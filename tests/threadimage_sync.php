<?php
require __DIR__ . '/../config/config_global.php';
require_once __DIR__ . '/../source/class/class_core.php';
$discuz = C::app();
$discuz->init();
require_once libfile('function/delete');
require_once libfile('function/forum');

$tid = 20001;
$uid = 1;

// insert attachment metadata
$aid = C::t('forum_attachment')->insert([
    'tid' => $tid,
    'pid' => 0,
    'uid' => $uid,
    'tableid' => 0,
    'downloads' => 0,
], true);

// insert attachment content row
C::t('forum_attachment_n')->insert_attachment(0, [
    'aid' => $aid,
    'tid' => $tid,
    'pid' => 0,
    'uid' => $uid,
    'dateline' => time(),
    'filename' => 'test.jpg',
    'filesize' => 1234,
    'attachment' => 'test.jpg',
    'remote' => 0,
    'description' => '',
    'readperm' => 0,
    'price' => 0,
    'isimage' => 1,
    'width' => 0,
    'height' => 0,
    'thumb' => 0,
    'picid' => 0,
    'sha1' => '',
]);

// threadimage entry
C::t('forum_threadimage')->insert([
    'tid' => $tid,
    'attachment' => 'test.jpg',
    'remote' => 0,
], false, true);

$beforeAttachments = C::t('forum_attachment_n')->count_image_by_id(0, 'tid', $tid);
$beforeThreadimage = DB::result_first('SELECT COUNT(*) FROM %t WHERE tid=%d', ['forum_threadimage', $tid]);

echo "Before delete: attachments=$beforeAttachments, threadimage=$beforeThreadimage\n";

deleteattach($tid, 'tid');

$afterAttachments = C::t('forum_attachment_n')->count_image_by_id(0, 'tid', $tid);
$afterThreadimage = DB::result_first('SELECT COUNT(*) FROM %t WHERE tid=%d', ['forum_threadimage', $tid]);

echo "After delete: attachments=$afterAttachments, threadimage=$afterThreadimage\n";

if ($afterAttachments == 0 && $afterThreadimage == 0) {
    echo "SYNC_OK\n";
} else {
    echo "SYNC_FAIL\n";
}
