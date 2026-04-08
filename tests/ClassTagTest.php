<?php

use PHPUnit\Framework\TestCase;

if (!defined('IN_DISCUZ')) {
    define('IN_DISCUZ', true);
}
if (!defined('TIMESTAMP')) {
    define('TIMESTAMP', time());
}

if (!function_exists('censor')) {
    function censor($string) {
        return $string;
    }
}

require_once __DIR__ . '/../source/class/class_tag.php';

// Wrap the real stub classes in conditional checks so they don't break if loaded elsewhere later
if (!class_exists('table_common_tag')) {
    class table_common_tag {
        public static $instance;
        public static function t() {
            if (!self::$instance) self::$instance = new self();
            return self::$instance;
        }
        public function get_bytagname($tagname, $idtype) { return []; }
        public function insert_tag($tagname, $status) { return 1; }
        public function increase($tagid, $data) {}
        public function fetch_by_tagid($tagId) { return []; }
        public function update($tagId, $data) {}
        public function delete_byids($tagidarray) {}
    }
}

if (!class_exists('table_common_tagitem')) {
    class table_common_tagitem {
        public static $instance;
        public static function t() {
            if (!self::$instance) self::$instance = new self();
            return self::$instance;
        }
        public function select($tagid, $itemid = null, $idtype = null) { return []; }
        public function replace($tagid, $itemid, $idtype) {}
        public function fetch_all_by_tagid_and_time($tagId, $startTime) { return []; }
        public function delete_tagitem($tagidarray, $itemid = null, $idtype = '') {}
    }
}

class ClassTagTest extends TestCase {

    protected function tearDown(): void {
        if (class_exists('table_common_tag')) {
            table_common_tag::$instance = null;
        }
        if (class_exists('table_common_tagitem')) {
            table_common_tagitem::$instance = null;
        }
    }

    public function testGetContentWeight() {
        $reflectionMethod = new ReflectionMethod('tag', 'getContentWeight');
        $reflectionMethod->setAccessible(true);

        $this->assertEquals(1.0, $reflectionMethod->invoke(null, 'tid'));
        $this->assertEquals(0.5, $reflectionMethod->invoke(null, 'pid'));
        $this->assertEquals(1.2, $reflectionMethod->invoke(null, 'articleid'));
        $this->assertEquals(0.5, $reflectionMethod->invoke(null, 'commentid'));
        $this->assertEquals(1.0, $reflectionMethod->invoke(null, 'blogid'));
        $this->assertEquals(0.5, $reflectionMethod->invoke(null, 'doid'));
        $this->assertEquals(1.0, $reflectionMethod->invoke(null, 'unknown_type'));
    }

    public function testCalculateNewContentHot() {
        $reflectionMethod = new ReflectionMethod('tag', 'calculate_new_content_hot');
        $reflectionMethod->setAccessible(true);

        $mockTagItem = $this->createMock(table_common_tagitem::class);
        table_common_tagitem::$instance = $mockTagItem;

        $records = [
            ['idtype' => 'tid', 'created_at' => TIMESTAMP - 3600], // 1 hour ago
            ['idtype' => 'pid', 'created_at' => TIMESTAMP - 7200], // 2 hours ago
        ];

        $mockTagItem->method('fetch_all_by_tagid_and_time')->willReturn($records);

        $hotScore = $reflectionMethod->invoke(null, 1, 0);
        $this->assertEqualsWithDelta(11.2, $hotScore, 0.01, "Hot score calculation should match expected value with decay");
    }

    public function testCalculateNewContentHotEmptyRecords() {
        $reflectionMethod = new ReflectionMethod('tag', 'calculate_new_content_hot');
        $reflectionMethod->setAccessible(true);

        $mockTagItem = $this->createMock(table_common_tagitem::class);
        table_common_tagitem::$instance = $mockTagItem;

        $mockTagItem->method('fetch_all_by_tagid_and_time')->willReturn([]);

        $hotScore = $reflectionMethod->invoke(null, 1, 0);
        $this->assertEquals(0.0, $hotScore);
    }

    public function testUpdateTagHotScoreTagNotFound() {
        $mockTag = $this->createMock(table_common_tag::class);
        table_common_tag::$instance = $mockTag;

        $mockTag->method('fetch_by_tagid')->willReturn([]);

        $result = tag::update_tag_hot_score(999);
        $this->assertFalse($result);
    }

    public function testUpdateTagHotScoreInitialCalculation() {
        $mockTag = $this->createMock(table_common_tag::class);
        table_common_tag::$instance = $mockTag;

        $mockTagItem = $this->createMock(table_common_tagitem::class);
        table_common_tagitem::$instance = $mockTagItem;

        $mockTag->method('fetch_by_tagid')->willReturn(['tagid' => 1]);

        $mockTagItem->method('fetch_all_by_tagid_and_time')->willReturn([
            ['idtype' => 'tid', 'created_at' => TIMESTAMP - 3600]
        ]);

        $mockTag->expects($this->once())->method('update')->with(1, $this->callback(function($data) {
            return abs($data['hot_score'] - 8.0) < 0.01 && isset($data['updated_at']);
        }));

        $result = tag::update_tag_hot_score(1);
        $this->assertEqualsWithDelta(8.0, $result, 0.01);
    }

    public function testUpdateTagHotScoreWithExistingDecay() {
        $mockTag = $this->createMock(table_common_tag::class);
        table_common_tag::$instance = $mockTag;

        $mockTagItem = $this->createMock(table_common_tagitem::class);
        table_common_tagitem::$instance = $mockTagItem;

        $mockTag->method('fetch_by_tagid')->willReturn([
            'tagid' => 1,
            'hot_score' => 10.0,
            'updated_at' => TIMESTAMP - 3600
        ]);

        $mockTagItem->method('fetch_all_by_tagid_and_time')->willReturn([
            ['idtype' => 'tid', 'created_at' => TIMESTAMP - 1800]
        ]);

        $mockTag->expects($this->once())->method('update')->with(1, $this->callback(function($data) {
            return abs($data['hot_score'] - 16.94) < 0.01;
        }));

        $result = tag::update_tag_hot_score(1);
        $this->assertEqualsWithDelta(16.94, $result, 0.01);
    }

    public function testAddTagInvalidIdType() {
        $tagObj = new tag();
        $result = $tagObj->add_tag("test", 1, "invalid_type");
        $this->assertNull($result);
    }

    public function testAddTagEmptyTags() {
        $tagObj = new tag();
        $result = $tagObj->add_tag("", 1, "tid");
        $this->assertNull($result);
    }

    public function testAddTagSuccess() {
        $mockTag = $this->getMockBuilder(table_common_tag::class)
            ->onlyMethods(['get_bytagname', 'increase', 'fetch_by_tagid', 'update'])
            ->getMock();
        table_common_tag::$instance = $mockTag;

        $mockTagItem = $this->getMockBuilder(table_common_tagitem::class)
            ->onlyMethods(['select', 'replace'])
            ->getMock();
        table_common_tagitem::$instance = $mockTagItem;

        // Mock getting an existing tag
        $mockTag->method('get_bytagname')->willReturn(['tagid' => 123, 'status' => 0]);
        // Mock that the item is not yet tagged
        $mockTagItem->method('select')->willReturn(false);

        $mockTagItem->expects($this->once())->method('replace')->with(123, 1, 'tid');
        $mockTag->expects($this->once())->method('increase')->with(123, ['related_count' => 1]);

        // Let update_tag_hot_score mock run smoothly by fetching tag
        $mockTag->method('fetch_by_tagid')->willReturn(['tagid' => 123, 'hot_score' => 5.0, 'updated_at' => TIMESTAMP]);

        $tagObj = new tag();
        $result = $tagObj->add_tag("mytag", 1, "tid", 0);

        $this->assertStringContainsString("123,mytag\t", $result);
    }

    public function testAddTagReturnArray() {
        $mockTag = $this->getMockBuilder(table_common_tag::class)
            ->onlyMethods(['get_bytagname', 'increase', 'fetch_by_tagid', 'update'])
            ->getMock();
        table_common_tag::$instance = $mockTag;

        $mockTagItem = $this->getMockBuilder(table_common_tagitem::class)
            ->onlyMethods(['select', 'replace'])
            ->getMock();
        table_common_tagitem::$instance = $mockTagItem;

        $mockTag->method('get_bytagname')->willReturn(['tagid' => 123, 'status' => 0]);
        $mockTagItem->method('select')->willReturn(false);
        $mockTag->method('fetch_by_tagid')->willReturn(['tagid' => 123, 'hot_score' => 5.0, 'updated_at' => TIMESTAMP]);

        $tagObj = new tag();
        $result = $tagObj->add_tag("mytag", 1, "tid", 1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey(123, $result);
        $this->assertEquals("mytag", $result[123]);
    }

    public function testDeleteTagNotArray() {
        $tagObj = new tag();
        $result = $tagObj->delete_tag("not_array", "tid");
        $this->assertFalse($result);
    }
}
