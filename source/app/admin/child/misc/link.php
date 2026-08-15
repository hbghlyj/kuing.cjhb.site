<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

if(!submitcheck('linksubmit')) {

	$groupselectnew = '<select name="newgroup[{n}]">';
	for($i = 1; $i <= 4; $i++) {
		$groupselectnew .= '<option value="'.$i.'">'.cplang('misc_link_group'.$i).'</option>';
	}
	$groupselectnew .= '</select>';

	?>
	<script type="text/JavaScript">
		var rowtypedata = [
			[
				[1, '', 'td25'],
				[1, '<input type="text" class="txt" name="newdisplayorder[]" size="3">', 'td28'],
				[1, '<input type="text" class="txt" name="newname[]" size="15">'],
				[1, '<input type="text" class="txt" name="newurl[]" size="20">'],
				[1, '<input type="text" class="txt" name="newdescription[]" size="30">', 'td26'],
				[1, '<input type="text" class="txt" name="newlogo[]" size="20">'],
				[1, '<?php echo $groupselectnew; ?>']
			]
		]
	</script>
	<?php

	shownav('extended', 'misc_link');
	showsubmenu('nav_misc_links');
	/*search={"misc_link":"action=misc&operation=link"}*/
	showtips('misc_link_tips');
	/*search*/
	showformheader('misc&operation=link');
	showtableheader();
	showsubtitle(['', 'display_order', 'misc_link_edit_name', 'misc_link_edit_url', 'misc_link_edit_description', 'misc_link_edit_logo', 'misc_link_group']);

	$query = table_common_friendlink::t()->fetch_all_by_displayorder();
	foreach($query as $forumlink) {
		$grouptd = '<select class="txt" name="group['.$forumlink['id'].']">';
		for($i = 1; $i <= 4; $i++) {
			$grouptd .= '<option value="'.$i.'"'.($forumlink['type'] == $i ? ' selected="selected"' : '').'>'.cplang('misc_link_group'.$i).'</option>';
		}
		$grouptd .= '</select>';
		showtablerow('', ['class="td25"', 'class="td28"', '', '', 'class="td26"'], [
			'<input type="checkbox" class="checkbox" name="delete[]" value="'.$forumlink['id'].'">',
			'<input type="text" class="txt" name="displayorder['.$forumlink['id'].']" value="'.$forumlink['displayorder'].'" size="3">',
			'<input type="text" class="txt" name="name['.$forumlink['id'].']" value="'.$forumlink['name'].'" size="15">',
			'<input type="text" class="txt" name="url['.$forumlink['id'].']" value="'.$forumlink['url'].'" size="20">',
			'<input type="text" class="txt" name="description['.$forumlink['id'].']" value="'.$forumlink['description'].'" size="30">',
			'<input type="text" class="txt" name="logo['.$forumlink['id'].']" value="'.$forumlink['logo'].'" size="20">',
			$grouptd,
		]);
	}

	echo '<tr><td></td><td colspan="3"><div><a href="###" onclick="addrow(this, 0)" class="addtr">'.$lang['misc_link_add'].'</a></div></td></tr>';
	showsubmit('linksubmit', 'submit', 'del');
	showtablefooter();
	showformfooter();

} else {

	if($_GET['delete']) {
		table_common_friendlink::t()->delete($_GET['delete']);
	}

	if(is_array($_GET['name'])) {
		foreach($_GET['name'] as $id => $val) {
			$query = table_common_friendlink::t()->update($id, [
				'displayorder' => $_GET['displayorder'][$id],
				'name' => $_GET['name'][$id],
				'url' => $_GET['url'][$id],
				'description' => $_GET['description'][$id],
				'logo' => $_GET['logo'][$id],
				'type' => intval($_GET['group'][$id]) ? intval($_GET['group'][$id]) : 1,
			]);
		}
	}

	if(is_array($_GET['newname'])) {
		foreach($_GET['newname'] as $key => $value) {
			if($value) {
				table_common_friendlink::t()->insert([
					'displayorder' => $_GET['newdisplayorder'][$key],
					'name' => $value,
					'url' => $_GET['newurl'][$key],
					'description' => $_GET['newdescription'][$key],
					'logo' => $_GET['newlogo'][$key],
					'type' => intval($_GET['newgroup'][$key]) ? intval($_GET['newgroup'][$key]) : 1,
				]);
			}
		}
	}

	updatecache('forumlinks');
	cpmsg('forumlinks_succeed', 'action=misc&operation=link', 'succeed');

}
