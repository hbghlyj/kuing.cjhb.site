<?php exit('Access Denied');?>
<!--{template common/header}-->
    <script type="text/javascript" src="{$_G[setting][iconfont]}?{VERHASH}"></script>
	<!--{subtemplate home/spacecp_header}-->

            <caption>
                <h2 class="mbm xs3 xw1 pt10">
                    {lang action_account_title_security}
                </h2>
            </caption>

            <table class="tfm b_b cp0">
                <tr>
                    <th>{lang action_account_security_type_rename}</th>
                    <td>
                        <p class="y">
                            <a href="home.php?mod=spacecp&ac=account&op=verify&method=chgusername&formhash={FORMHASH}"  style="color: green;" class="dialog">{lang action_account_operate_chg}</a>
                        </p>
                        {$_G['member']['username']}
                        <p class="d xs1 xg1">
                            {lang action_account_security_type_rename_comment}
                        </p>
                    </td>
				</tr>
                <!--{if $_G[member][loginname] != $_G[member][username]}-->
                <tr>
                    <th>{lang loginname}</th>
                    <td>{$_G['member']['loginname']}</td>
                </tr>
                <!--{/if}-->
                <!--{if $_G['setting']['security_password']}-->
                <tr>
                    <th>{lang action_account_security_type_password}</th>
                    <td>
                        <p class="y">
                            <a href="home.php?mod=spacecp&ac=account&op=verify&method=chgpassword&formhash={FORMHASH}" style="color: green;" class="dialog">{lang action_account_operate_password_modify}</a>
                        </p>
                        ******
                        <p class="d xs1 xg1">{lang action_account_security_type_password_comment}</p>
                        <!--{if $_G['member']['freeze'] == 1}-->
                        <strong class="xi1">{lang freeze_pw_tips}</strong>
                        <!--{/if}-->
                    </td>
                </tr>
                <!--{/if}-->
                <!--{if $_G['setting']['security_question']}-->
                <tr>
                    <th>{lang action_account_security_type_question}</th>
                    <td>
                        <p class="y">
                            <a href="home.php?mod=spacecp&ac=account&op=verify&method=chgquestion&formhash={FORMHASH}" style="color: green;" class="dialog">{lang action_account_operate_chg}</a>
                        </p>
                        <span class="d xs1 xg1">{lang action_account_security_type_question_comment}</span>
                    </td>
                </tr>
                <!--{/if}-->
                <!--{if $_G['setting']['security_email']}-->
                <tr>
                    <th>{lang action_account_security_type_email}</th>
                    <td>
                        <p class="y">
                            <a href="home.php?mod=spacecp&ac=account&op=verify&method=chgemail&formhash={FORMHASH}" style="color: green;" class="dialog">{lang action_account_operate_chg}</a>
                        </p>
                        <!--{if $_G['member']['email']}-->
                            {$_G['member']['email']}
                            <!--{if $_G['member']['emailstatus']}-->
                                <span style="color: green;">({lang action_account_status_active})</span>
                            <!--{else}-->
                                <a href="home.php?mod=spacecp&ac=account&op=verifyemail&method=resend&formhash={FORMHASH}" style="color: red;">({lang action_account_operate_email_active})</a>
                            <!--{/if}-->
                        <!--{else}-->
                            {lang action_account_security_type_data_empty}
                        <!--{/if}-->
                        <p class="d xs1 xg1">{lang action_account_security_type_email_comment}</p>
                        <!--{if $_G['member']['freeze'] == 2}-->
                        <strong class="xi1 xs1">{lang freeze_email_tips}</strong>
                        <!--{/if}-->
                        </p>
                    </td>
                </tr>
                <!--{/if}-->
                <!--{if $_G['setting']['security_mobile']}-->
                <tr>
                    <th>{lang action_account_security_type_mobile}</th>
                    <td>
                        <p class="y">
                            <!--{if !$_G['member']['secmobile']}-->
                            <a href="home.php?mod=spacecp&ac=account&op=verify&method=bindmobile&formhash={FORMHASH}" style="color: green;" class="dialog">{lang action_account_operate_bind}</a>
                            <!--{else}-->
                            <a href="home.php?mod=spacecp&ac=account&op=verify&method=unbindmobile&formhash={FORMHASH}" style="color: red;" class="dialog">{lang action_account_operate_unbind}</a>
                            <!--{/if}-->
                        </p>
                        <!--{if $_G['member']['secmobile']}-->
                        <!--{if $_G['member']['secmobicc']}-->+{$_G['member']['secmobicc']} <!--{/if}-->
                        {$_G['member']['secmobile']}
                        <!--{else}-->
                        {lang action_account_security_type_data_empty}
                        <!--{/if}-->
                        <p class="d xs1 xg1">{lang action_account_security_type_mobile_comment}</p>
                    </td>
                </tr>
                <!--{/if}-->
                <!--{if $_G['setting']['security_logoff']}-->
                <tr>
                    <th>{lang action_account_security_type_logoff}</th>
                    <td>
                        <p class="y">
                            <a href="home.php?mod=spacecp&ac=account&op=verify&method=logoff&formhash={FORMHASH}" style="color: green;" class="dialog">{lang action_account_operate_logoff_set}</a>
                        </p>
                    </td>
                </tr>
                <!--{/if}-->
                <!--{if $_G['member']['freeze'] == 2 || $_G['member']['freeze'] == -1}-->
                <tr>
                    <th>{lang action_account_security_type_freeze}</th>
                    <td>
                        <p class="y">
                            <a href="home.php?mod=spacecp&ac=account&op=verify&method=freeze&formhash={FORMHASH}" onclick="showWindow('security_verify', this.href, 'get', 0);return false;" style="color: green;" class="dialog">{lang action_account_security_type_freeze_reason_submit}</a>
                        </p>
                        <!--{if $_G['member']['freeze'] == 2}--><span class="d xs1 xg1">{lang freeze_reason_comment}</span>
                        <!--{elseif $_G['member']['freeze'] == -1}-->
                        <p class="xs1"><strong class="xi1">{lang freeze_admincp_tips}</strong></p>
                        <!--{/if}-->
                    </td>
                </tr>
                <!--{/if}-->
            </table>
            <!--{if $list}-->
            <caption>
                <h2 class="mbm xs3 xw1 pt10">
                    {lang action_account_title_third_login_method}
                </h2>
            </caption>
            <table class="tfm cp0">
                <tr>
                    <th>{lang action_account_type}</th>
                    <th>{lang action_account_bindname}</th>
                    <th>{lang action_account_operate}</th>
                </tr>
                <!--{eval $i = 0;}-->
                <!--{loop $list $key $value}-->
                <!--{eval $i++;}-->
                <tr{if $i % 2 == 0} class="alt"{/if}>
                <td>{cell account/icon} {$value[1]}</td>
                <!--{if !empty($account_list[$value[0]]['account'])}-->
                    <td>{$account_list[$value[0]]['bindname']}</td>
                    <td><a href="home.php?mod=spacecp&ac=account&op=unbind&method={$value[0]}&formhash={FORMHASH}" style="color: red;">{lang action_account_operate_unbind}</a></td>
                <!--{else}-->
                    <td></td>
                    <td><a href="$third_login_bind_urls[$value[0]]" style="color: green;">{lang action_account_operate_bind}</a></td>
                <!--{/if}-->
                </tr>
                <!--{/loop}-->
            </table>
            <!--{/if}-->
</div>
<!--{template common/footer}-->
