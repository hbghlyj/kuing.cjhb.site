<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class tag {
	/**
	 * 添加标签
	 *
	 * @param string $tags 标签字符串
	 * @param int $itemid 关联内容ID
	 * @param string $idtype 内容类型
	 * @param int $returnarray 返回格式
	 * @return mixed
	 */
	public function add_tag($tags, $itemid, $idtype = 'tid', $returnarray = 0) {
		global $_G;

		if($tags == '' || !in_array($idtype, ['', 'tid', 'blogid', 'articleid', 'doid'])) {
			return;
		}

		$tags = str_replace(array(chr(0xa3).chr(0xac), chr(0xa1).chr(0x41), chr(0xef).chr(0xbc).chr(0x8c)), ',', censor($tags));
		$tagarray = array_unique(explode(',', $tags));
		$tagcount = 0;
		$return = null;
		foreach($tagarray as $tagname) {
			$tagname = trim($tagname);
			$tagLength = mb_strlen($tagname, 'UTF-8');
			if($tagname !== '' && $tagLength >= 2 && $tagLength <= 35 && preg_match('/^[^\x00-\x1F\x7F,]+$/u', $tagname)) {
				$status = 0;
				$result = table_common_tag::t()->get_bytagname($tagname, $idtype);
				if($result['tagid']) {
					if($result['status'] == $status) {
						$tagid = $result['tagid'];
					}
				} else {
					$tagid = table_common_tag::t()->insert_tag($tagname, $status);
				}
				if($tagid) {
					if($itemid) {
						if(!table_common_tagitem::t()->select($tagid, $itemid, $idtype)) {
							table_common_tagitem::t()->replace($tagid, $itemid, $idtype);

							// 增加标签关联计数并触发热度计算
							table_common_tag::t()->increase($tagid, ['related_count' => 1]);

							// 异步更新热度，避免影响主线程性能
							//if(empty($_G['setting']['tag_hot_async']) || $_G['setting']['tag_hot_async'] != 1) {
							//} else {
							//todo 使用Discuz!的任务队列机制异步更新热度
							//}
						}
					}
					$tagcount++;
					if(!$returnarray) {
						$return .= $tagid.','.$tagname."\t";
					} else {
						$return[$tagid] = $tagname;
					}
				}
				if($tagcount > 4) {
					unset($tagarray);
					break;
				}
			}
		}
		return $return;
	}

	/**
	 * 更新标签字段
	 *
	 * @param string $tags 标签字符串
	 * @param int $itemid 内容ID
	 * @param string $idtype 内容类型
	 * @param array $typeinfo 类型信息
	 * @return string 更新后的标签字符串
	 */
	public function update_field($tags, $itemid, $idtype = 'tid', $typeinfo = [], $logMod = true) {
		$olditems = table_common_tagitem::t()->select(0, $itemid, $idtype);
		$tagidarray = array_column($olditems, 'tagid');
		$oldTagNames = [];
		if($tagidarray) {
			$oldTagsData = table_common_tag::t()->fetch_all($tagidarray);
			$oldTagNames = array_column($oldTagsData, 'tagname');
		}
		$tags = $this->add_tag($tags, $itemid, $idtype, 1) ?? [];
		$tagstr = '';
		foreach($tags as $tagid => $tagname) {
			$tagstr .= $tagid.','.$tagname."\t";
		}
		$removedTags = array_diff($tagidarray, array_keys($tags));
		if($removedTags) {
			table_common_tagitem::t()->delete_tagitem($removedTags, $itemid, $idtype);
		}
		// 更新被删除标签的关联数
		foreach($removedTags as $tagid) {
			table_common_tag::t()->increase($tagid, ['related_count' => -1]);
		}

		if($logMod && $idtype == 'tid' && $itemid) {
			$newTagNames = array_values($tags);
			$added = array_diff($newTagNames, $oldTagNames);
			$removed = array_diff($oldTagNames, $newTagNames);
			if($added || $removed) {
				$diffParts = [];
				if($added) {
					$diffParts[] = '+'.implode(',', $added);
				}
				if($removed) {
					$diffParts[] = '-'.implode(',', $removed);
				}
				$diffStr = cutstr(implode(' ', $diffParts), 40);
				updatemodlog($itemid, 'TAG', 0, 0, $diffStr);
			}
		}

		return $tagstr;
	}

	public function copy_tag($oldid, $newid, $idtype = 'tid') {
		$results = table_common_tagitem::t()->select(0, $oldid, $idtype);
		foreach($results as $result) {
			table_common_tagitem::t()->insert([
				'tagid' => $result['tagid'],
				'itemid' => $newid,
				'idtype' => $idtype,
				'created_at' => TIMESTAMP
			]);
		}
	}

	/**
	 * Update denormalized tag references without changing tag associations.
	 *
	 * @param int $tagid
	 * @param string $oldtag
	 * @param string $newtag
	 * @return void
	 */
	private function update_tag_references($tagid, $oldtag, $newtag) {
		$oldTagRef = $tagid.','.$oldtag."\t";
		$newTagRef = $tagid.','.$newtag."\t";

		$referenceFields = [
			['table' => 'forum_thread', 'field' => 'tags'],
			['table' => 'home_blogfield', 'field' => 'tag'],
			['table' => 'portal_article_title', 'field' => 'tags'],
		];
		foreach($referenceFields as $reference) {
			DB::query('UPDATE %t SET '.$reference['field'].'=REPLACE('.$reference['field'].', %s, %s) WHERE INSTR('.$reference['field'].', %s) > 0', [
				$reference['table'], $oldTagRef, $newTagRef, $oldTagRef
			]);
		}

		// Doing tags are stored as a JSON object in home_doing.fields.
		$doingItems = table_common_tagitem::t()->select($tagid, 0, 'doid');
		$doids = array_values(array_unique(array_map('intval', array_column($doingItems, 'itemid'))));
		if(!$doids) {
			return;
		}
		$doings = DB::fetch_all('SELECT doid, fields FROM %t WHERE doid IN (%n)', ['home_doing', $doids]);
		foreach($doings as $doing) {
			$fields = json_decode($doing['fields'], true);
			if(!is_array($fields) || !isset($fields['tags']) || !is_array($fields['tags']) || !array_key_exists($tagid, $fields['tags'])) {
				continue;
			}
			$fields['tags'][$tagid] = $newtag;
			table_home_doing::t()->update($doing['doid'], ['fields' => json_encode($fields, JSON_UNESCAPED_UNICODE)]);
		}
	}

	public function rename_tag($tagidarray, $newtag) {
		$tagidarray = array_values(array_unique(array_map('intval', (array)$tagidarray)));
		if(count($tagidarray) !== 1) {
			return 'tag_rename_select_one';
		}

		$tagid = $tagidarray[0];
		$tag = table_common_tag::t()->fetch_by_tagid($tagid);
		if(!$tag) {
			return 'tag_rename_not_found';
		}

		$newtag = trim($newtag);
		if($newtag === '' || strpos($newtag, ',') !== false || mb_strlen($newtag, 'UTF-8') < 2 || mb_strlen($newtag, 'UTF-8') > 35 || !preg_match('/^[^\x00-\x1F\x7F,]+$/u', $newtag)) {
			return 'tag_rename_invalid';
		}
		if($newtag === $tag['tagname']) {
			return 'succeed';
		}

		$existing = table_common_tag::t()->fetch_by_tagname($newtag);
		if($existing && intval($existing['tagid']) !== $tagid) {
			return 'tag_rename_exists';
		}

		$this->update_tag_references($tagid, $tag['tagname'], $newtag);
		table_common_tag::t()->update($tagid, ['tagname' => $newtag, 'updated_at' => TIMESTAMP]);

		return 'succeed';
	}

	public function merge_tag($tagidarray, $newtag, $idtype = '') {
		$newtag = trim($newtag);
		$newtagLength = mb_strlen($newtag, 'UTF-8');
		if($newtag === '' || strpos($newtag, ',') !== false || $newtagLength < 2 || $newtagLength > 35 || !preg_match('/^[^\x00-\x1F\x7F,]+$/u', $newtag)) {
			return 'tag_empty';
		}
		if(!table_common_tag::t()->get_bytagname($newtag, $idtype)) {
			return 'tag_merge_nonexistence';
		}
		if($newtag !== '') {
			$tidarray = $blogidarray = $articleidarray = [];
			$newtaginfo = $this->add_tag($newtag, 0, $idtype, 1);
			foreach($newtaginfo as $tagid => $tagname) {
				$newid = $tagid;
			}
			$tagidarray = array_diff((array)$tagidarray, (array)$newid);
				$tagnames = table_common_tag::t()->get_byids($tagidarray);
				$results = table_common_tagitem::t()->select($tagidarray);
				foreach($results as $result) {
					$result['tagname'] = addslashes($tagnames[$result['tagid']]['tagname']);
					if($result['idtype'] == 'tid') {
						$itemid = $result['itemid'];
						if(!isset($tidarray[$itemid])) {
							$thread = C::t('forum_thread')->fetch($itemid);
							$tidarray[$itemid] = $thread['tags'];
						}
						$tidarray[$itemid] = str_replace("{$result['tagid']},{$result['tagname']}\t", '', $tidarray[$itemid]);
					} elseif($result['idtype'] == 'blogid') {
						$itemid = $result['itemid'];
						if(!isset($blogidarray[$itemid])) {
							$blogfield = table_home_blogfield::t()->fetch($itemid);
							$blogidarray[$itemid] = $blogfield['tag'];
						}
						$blogidarray[$itemid] = str_replace("{$result['tagid']},{$result['tagname']}\t", '', $blogidarray[$itemid]);
					} elseif($result['idtype'] == 'articleid') {
						$itemid = $result['itemid'];
						if(!isset($articleidarray[$itemid])) {
							$article = table_portal_article_title::t()->fetch($itemid);
							$articleidarray[$itemid] = $article['tags'];
						}
						$articleidarray[$itemid] = str_replace("{$result['tagid']},{$result['tagname']}\t", '', $articleidarray[$itemid]);
					}
				}
			$checkunique = [];
			$checktagids = $tagidarray;
			$checktagids[] = $newid;
			$results = table_common_tagitem::t()->select($checktagids);
			foreach($results as $row) {
				if($checkunique[$row['itemid'].'_'.$row['idtype']] == 'deleted' || empty($checkunique[$row['itemid'].'_'.$row['idtype']])) {
					if(empty($checkunique[$row['itemid'].'_'.$row['idtype']])) {
						$checkunique[$row['itemid'].'_'.$row['idtype']] = 1;
					}
				} else {
					table_common_tagitem::t()->unique($row['tagid'], $row['itemid'], $row['idtype']);
					$checkunique[$row['itemid'].'_'.$row['idtype']] = 'deleted';
				}
			}
			table_common_tagitem::t()->merge_by_tagids($newid, $tagidarray);
			table_common_tag::t()->delete_byids($tagidarray);
			$relatedCount = table_common_tagitem::t()->count_by_tagid($newid);
			table_common_tag::t()->update($newid, ['related_count' => $relatedCount]);

			if($tidarray) {
				foreach($tidarray as $key => $var) {
					C::t('forum_thread')->update($key, ['tags' => $var]);
					if(strpos($var, "$newid,$newtag\t") === false) {
						C::t('forum_thread')->concat_tags_by_tid($key, "$newid,$newtag\t");
					}
				}
			}
			if($blogidarray) {
				foreach($blogidarray as $key => $var) {
					table_home_blogfield::t()->update($key, ['tag' => $var.$newid.','.$newtag."\t"]);
				}
			}
			if($articleidarray) {
				foreach($articleidarray as $key => $var) {
					table_portal_article_title::t()->update($key, ['tags' => $var]);
				}
			}
		} else {
			return 'tag_length';
		}
		return 'succeed';
	}

	public function delete_tag($tagidarray, $idtype = '') {
		$tidarray = $blogidarray = $articleidarray = [];
		if(!is_array($tagidarray)) {
			return false;
		}
			$tagnames = table_common_tag::t()->get_byids($tagidarray);
			$items = table_common_tagitem::t()->select($tagidarray);
			foreach($items as $result) {
				$result['tagname'] = addslashes($tagnames[$result['tagid']]['tagname']);
				if($result['idtype'] == 'tid') {
					$itemid = $result['itemid'];
					if(!isset($tidarray[$itemid])) {
						$thread = C::t('forum_thread')->fetch($itemid);
						$tidarray[$itemid] = $thread['tags'];
					}
					$tidarray[$itemid] = str_replace("{$result['tagid']},{$result['tagname']}\t", '', $tidarray[$itemid]);
				} elseif($result['idtype'] == 'blogid') {
					$itemid = $result['itemid'];
					if(!isset($blogidarray[$itemid])) {
						$blogfield = table_home_blogfield::t()->fetch($itemid);
						$blogidarray[$itemid] = $blogfield['tag'];
					}
					$blogidarray[$itemid] = str_replace("{$result['tagid']},{$result['tagname']}\t", '', $blogidarray[$itemid]);
				} elseif($result['idtype'] == 'articleid') {
					$itemid = $result['itemid'];
					if(!isset($articleidarray[$itemid])) {
						$article = table_portal_article_title::t()->fetch($itemid);
						$articleidarray[$itemid] = $article['tags'];
					}
					$articleidarray[$itemid] = str_replace("{$result['tagid']},{$result['tagname']}\t", '', $articleidarray[$itemid]);
				}
			}

		if($tidarray) {
			foreach($tidarray as $key => $var) {
				C::t('forum_thread')->update($key, ['tags' => $var]);
			}
		}
		if($blogidarray) {
			foreach($blogidarray as $key => $var) {
				table_home_blogfield::t()->update($key, ['tag' => $var]);
			}
		}
		if($articleidarray) {
			foreach($articleidarray as $key => $var) {
				table_portal_article_title::t()->update($key, ['tags' => $var]);
			}
		}
		table_common_tag::t()->delete_byids($tagidarray);
		table_common_tagitem::t()->delete_tagitem($tagidarray);
		return true;
	}

	public static function getthreadsbytids($tidarray) {
		global $_G;

		$threadlist = [];
		if(!empty($tidarray)) {
			loadcache('forums');
			include_once libfile('function_misc', 'function');
			$fids = [];
			foreach(table_forum_thread::t()->fetch_all_by_tid($tidarray) as $result) {
				if($result['displayorder'] >= 0) {
					if(!isset($_G['cache']['forums'][$result['fid']]['name'])) {
						$fids[$result['fid']][] = $result['tid'];
					} else {
						$result['name'] = $_G['cache']['forums'][$result['fid']]['name'];
					}
					$threadlist[$result['tid']] = procthread($result);
				}
			}
			if(!empty($fids)) {
				foreach(table_forum_forum::t()->fetch_all_by_fid(array_keys($fids)) as $fid => $forum) {
					foreach($fids[$fid] as $tid) {
						$threadlist[$tid]['forumname'] = $forum['name'];
					}
				}
			}
		}
		return $threadlist;
	}

	public static function getarticlebyid($articleidarray) {
		global $_G, $summarylen;

		$articlelist = [];
		if(!empty($articleidarray)) {
			$data_article = table_portal_article_title::t()->fetch_all_for_search($articleidarray, 'dateline', 'DESC',0, 99);

			require_once libfile('function/home');
			foreach($data_article as $curarticleid => $result) {
				$result['dateline'] = dgmdate($result['dateline']);
				$result['summary'] = preg_replace('/&[a-z]+\;/i', '', $result['summary']);
				if($result['pic']) {
					$result['pic'] = pic_get($result['pic'], '', $result['thumb'], $result['remote'], 1, 1);
				}
				$articlelist[] = $result;
			}
		}
		return $articlelist;
	}

	public static function getblogbyid($blogidarray) {
		global $_G, $summarylen;

		$bloglist = [];
		if(!empty($blogidarray)) {
			$data_blog = table_home_blog::t()->fetch_all_blog($blogidarray, 'dateline', 'DESC');
			$data_blogfield = table_home_blogfield::t()->fetch_all($blogidarray);

			require_once libfile('function/spacecp');
			require_once libfile('function/home');
			$classarr = [];
			foreach($data_blog as $curblogid => $result) {
				$result = array_merge($result, (array)$data_blogfield[$curblogid]);
				$result['dateline'] = dgmdate($result['dateline']);
				$classarr = getclassarr($result['uid']);
				$result['classname'] = $classarr[$result['classid']]['classname'];
				if($result['friend'] == 4) {
					$result['message'] = $result['pic'] = '';
				} else {
					$result['message'] = getstr($result['message'], $summarylen, 0, 0, 0, -1);
				}
				$result['message'] = preg_replace('/&[a-z]+\;/i', '', $result['message']);
				if($result['pic']) {
					$result['pic'] = pic_cover_get($result['pic'], $result['picflag']);
				}
				$bloglist[] = $result;
			}
		}
		return $bloglist;
	}
	public static function getdoingbyid($doidarray) {
		global $_G, $summarylen;

		$dolist = [];
		if(!empty($doidarray)) {
			$data_doing = table_home_doing::t()->fetch_all_search(0, 20, 1, [], null, null, 0, 0, 0, 0, $doidarray, '');
			require_once libfile('function/home');
			foreach($data_doing as $doid => $result) {
				$result['dateline'] = dgmdate($result['dateline']);
				$result['message'] = getstr($result['message'], 120, 0, 0, 0, -1);
				$result['message'] = preg_replace('/&[a-z]+\;/i', '', $result['message']);
				$dolist[] = $result;
			}
		}
		return $dolist;
	}
}
